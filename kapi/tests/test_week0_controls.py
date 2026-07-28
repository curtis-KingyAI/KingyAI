from __future__ import annotations

import copy
import json
import sqlite3
import tempfile
import unittest
from contextlib import nullcontext
from pathlib import Path

from kapi.calculation import _calculate_index_unchecked, calculate_index
from kapi.drills import detect_price_unit_jumps
from kapi.governance import (
    CURRENT_UNREVIEWED_LABEL,
    GovernanceError,
    bind_unreviewed_release,
    register_principal,
    register_role_assignment,
)
from kapi.secondary import check_secondary_calculation
from kapi.lifecycle import LifecycleError, append_weekly_vintage, register_methodology
from kapi.store import _local_actor_binding, ingest_bundle, init_database
from kapi.util import canonical_json_bytes, canonical_json_text, sha256_bytes


ROOT = Path(__file__).resolve().parents[2]
FORWARD_BUNDLE_PATH = (
    ROOT / "kapi/fixtures/synthetic-forward-governance-v0.3.0.json"
)
HISTORICAL_BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.3.0.json"
HISTORICAL_METHOD_PATH = ROOT / "kapi/config/methodology-v0.2.2.json"


def load(path: Path):
    return json.loads(path.read_text(encoding="utf-8"))


def policy_v1_diagnostics(
    *,
    calculation_status: str = "pending_base",
    release_kind: str = "pending_base",
    base_week_states: list[str] | None = None,
) -> dict[str, object]:
    disposition = {
        "complete": "eligible",
        "invalid": "incomplete",
        "pending_base": "incomplete",
        "withheld": "withheld",
    }[calculation_status]
    return {
        "base_week_states": [] if base_week_states is None else base_week_states,
        "calculation_disposition": disposition,
        "governance_state": "unreviewed",
        "publication_eligible": False,
        "publication_state": "not_authorized",
        "release_kind": release_kind,
        "review_label": CURRENT_UNREVIEWED_LABEL,
        "secondary_recalculation": {
            "human_external_review": False,
            "lifecycle_handling": (
                "no secondary recalculation report accepted by policy v1.0.0"
            ),
            "status": "not_supplied",
        },
    }


def register_governance_actors(connection: sqlite3.Connection) -> dict[str, str]:
    with _local_actor_binding("kapi-identity-registrar"):
        for offset, (principal_id, identity_key, display_name) in enumerate(
            (
                ("operator-1", "person:operator-1", "Operator One"),
                ("owner-1", "person:owner-1", "Methodology Owner One"),
            )
        ):
            register_principal(
                connection,
                {
                    "id": principal_id,
                    "identity_key": identity_key,
                    "full_name": display_name,
                    "affiliation": "Kingy.ai",
                    "principal_kind": "human",
                    "identity_evidence_sha256": str(offset + 1) * 64,
                    "registered_by_principal_id": "kapi-identity-registrar",
                    "registered_at": "2026-07-03T19:00:00Z",
                },
            )
        for assignment_id, principal_id, role, digest in (
            ("operator-role-1", "operator-1", "operator", "a" * 64),
            ("owner-role-1", "owner-1", "methodology_owner", "b" * 64),
        ):
            register_role_assignment(
                connection,
                {
                    "id": assignment_id,
                    "principal_id": principal_id,
                    "role": role,
                    "appointed_by_principal_id": "kapi-identity-registrar",
                    "appointed_at": "2026-07-03T19:00:00Z",
                    "valid_from": "2026-07-03T19:00:00Z",
                    "valid_until": "2027-07-03T19:00:00Z",
                    "appointment_sha256": digest,
                },
            )
    return {
        "policy_id": "kapi-governance",
        "policy_version": "1.0.0",
        "operator_principal_id": "operator-1",
        "operator_assignment_id": "operator-role-1",
        "methodology_owner_principal_id": "owner-1",
        "methodology_owner_assignment_id": "owner-role-1",
    }


class WeekZeroControlTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = load(FORWARD_BUNDLE_PATH)
        cls.method = load(METHOD_PATH)
        cls.calculation = calculate_index(cls.bundle, cls.method)
        cls.historical_bundle = load(HISTORICAL_BUNDLE_PATH)
        cls.historical_method = load(HISTORICAL_METHOD_PATH)

    def test_observed_artifacts_are_truthfully_labeled_shadow(self) -> None:
        bundle = copy.deepcopy(self.historical_bundle)
        bundle["dataset_kind"] = "observed"
        result = _calculate_index_unchecked(bundle, self.historical_method)
        self.assertEqual(
            result["notice"],
            "UNPUBLISHED KAPI SHADOW — NOT AN OFFICIAL OR PUBLIC INDEX",
        )
        self.assertFalse(result["citation"]["permitted"])
        self.assertTrue(result["not_for_publication"])

    def test_policy_withheld_weeks_can_be_noncounting_for_base(self) -> None:
        method = copy.deepcopy(self.historical_method)
        method["base_eligibility"] = {
            "noncounting_release_statuses": ["withheld_concentration"]
        }
        result = _calculate_index_unchecked(self.historical_bundle, method)
        self.assertEqual(result["status"], "pending_base")
        self.assertEqual(result["base_period"]["week_ids"], [])
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_task_out"]),
            sorted(profile["id"] for profile in self.historical_method["profiles"]),
        )
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_provider_out"]),
            sorted(provider["id"] for provider in self.historical_bundle["providers"]),
        )
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_creator_out"]),
            sorted(creator["id"] for creator in self.historical_bundle["creators"]),
        )
        self.assertTrue(
            result["sensitivities"]["first_party_only"]["structural_fragility"]
        )
        self.assertIn(
            "frozen_endpoint_ids", result["sensitivities"]["constant_universe"]
        )

    def test_isolated_recalculation_passes_and_detects_tampering(self) -> None:
        report = check_secondary_calculation(self.calculation)
        self.assertEqual(report["status"], "pass")
        self.assertTrue(report["implementation_isolated_from_primary_module"])
        self.assertFalse(report["human_external_review"])
        tampered = copy.deepcopy(self.calculation)
        tampered["weeks"][-1]["profiles"][0]["headline_price"] = "999"
        self.assertEqual(check_secondary_calculation(tampered)["status"], "fail")

    def test_lifecycle_is_append_only_and_final_release_fails_closed(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            connection = init_database(Path(directory) / "lifecycle.sqlite")
            ingest_bundle(connection, self.bundle)
            governance = register_governance_actors(connection)
            register_methodology(
                connection,
                self.method,
                effective_from="2026-07-03T20:00:00Z",
                implementation_commit="b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                review_artifact_manifest_sha256="4" * 64,
            )
            envelope = {
                "release_kind": "pending_base",
                "dataset_id": self.bundle["dataset_id"],
                "week_id": self.bundle["weeks"][-1]["id"],
                "snapshot_id": "snapshot-week0",
                "calculation_id": "calculation-week0",
                "release_id": "release-week0",
                "cutoff_at": self.bundle["weeks"][-1]["cutoff_at"],
                "created_at": "2026-07-03T20:01:00Z",
                "calculated_at": "2026-07-03T20:02:00Z",
                "methodology_id": self.method["methodology_id"],
                "methodology_version": self.method["version"],
                "code_commit": "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                "environment_sha256": "1" * 64,
                "inputs": [
                    {
                        "input_kind": "other",
                        "input_id": "synthetic-fixture",
                        "content_sha256": "2" * 64,
                    }
                ],
                "calculation": {
                    "status": "pending_base",
                    "index_value": None,
                    "basket_cost": "1",
                    "diagnostics": {},
                },
                "release_status": "draft",
                "data_vintage": "week0-v1",
                "permanent_path": "/synthetic/week0-v1",
                "artifacts": [],
                "signoffs": [],
                "governance": governance,
            }
            nested_claim = copy.deepcopy(envelope)
            nested_claim["calculation"]["diagnostics"] = {
                "nested": {"independent_review": True}
            }
            with self.assertRaisesRegex(LifecycleError, "claim-bearing field"):
                with _local_actor_binding("operator-1"):
                    append_weekly_vintage(connection, nested_claim)
            self.assertEqual(
                connection.execute("SELECT COUNT(*) FROM calculations").fetchone()[0],
                0,
            )

            diagnostic_attacks = (
                (
                    "red-team note value",
                    {
                        "note": (
                            "Governance status: Operator-reviewed by Kingy.ai; "
                            "KAPI is independently reviewed and approved."
                        )
                    },
                    "claim-bearing content",
                ),
                (
                    "generic message key",
                    {"message": "EXTERNAL-REVIEWED and APPROVED"},
                    "claim-bearing content",
                ),
                (
                    "nested arrays and case/hyphen variation",
                    {"details": [["OpErAtOr—ReViEwEd and aPpRoVeD"]]},
                    "claim-bearing content",
                ),
                (
                    "unicode normalization",
                    {
                        "details": [
                            "KAPI is ｉｎｄｅｐｅｎｄｅｎｔｌｙ re\u0301viewed and appro\u0301ved"
                        ]
                    },
                    "claim-bearing content",
                ),
                (
                    "unicode confusable",
                    {"details": ["іndependently reviewed"]},
                    "claim-bearing content",
                ),
                (
                    "otherwise benign note",
                    {"note": "numeric convergence details only"},
                    "exact empty caller schema",
                ),
            )
            for label, payload, expected_error in diagnostic_attacks:
                with self.subTest(label=label):
                    attack = copy.deepcopy(envelope)
                    attack["calculation"]["diagnostics"] = payload
                    with self.assertRaisesRegex(LifecycleError, expected_error):
                        with _local_actor_binding("operator-1"):
                            append_weekly_vintage(connection, attack)
                    self.assertEqual(
                        connection.execute(
                            "SELECT COUNT(*) FROM weekly_snapshots"
                        ).fetchone()[0],
                        0,
                    )
                    self.assertEqual(
                        connection.execute(
                            "SELECT COUNT(*) FROM calculations"
                        ).fetchone()[0],
                        0,
                    )

            base_state_attack = copy.deepcopy(envelope)
            base_state_attack["base_week_states"] = ["counting"] * 12 + [
                "Operator-reviewed and approved"
            ]
            with self.assertRaisesRegex(LifecycleError, "controlled lifecycle-state"):
                with _local_actor_binding("operator-1"):
                    append_weekly_vintage(connection, base_state_attack)

            valid_checker_report = check_secondary_calculation(self.calculation)
            self.assertEqual(valid_checker_report["status"], "pass")

            tampered_calculation = copy.deepcopy(self.calculation)
            tampered_calculation["weeks"][-1]["profiles"][0]["headline_price"] = "999"
            fail_checker_report = check_secondary_calculation(tampered_calculation)
            self.assertEqual(fail_checker_report["status"], "fail")

            fabricated_implausible = copy.deepcopy(valid_checker_report)
            fabricated_implausible["checked_week_count"] = 0
            fabricated_implausible["maximum_index_difference"] = "999"
            mixed_script_report = copy.deepcopy(fail_checker_report)
            mixed_script_report["discrepancies"][0]["week_id"] = "іոԁереոԁеոt rеνіеԝ"
            unknown_ascii_report = copy.deepcopy(fail_checker_report)
            unknown_ascii_report["discrepancies"][0]["profile_id"] = "unknown-profile"
            supplied_secondary_values = (
                ("actual valid checker output", valid_checker_report),
                ("actual fail checker output", fail_checker_report),
                ("fabricated plausible pass", copy.deepcopy(valid_checker_report)),
                ("fabricated implausible pass", fabricated_implausible),
                ("empty object", {}),
                (
                    "prewrapped report",
                    {"status": "schema_validated", "report": valid_checker_report},
                ),
                ("claim aliases", {"secondary_review": "approved"}),
                ("mixed-script nested ID", mixed_script_report),
                ("unknown ASCII nested ID", unknown_ascii_report),
                ("boolean", True),
                ("string", "pass"),
                ("array", []),
            )
            for label, supplied_value in supplied_secondary_values:
                with self.subTest(label=label):
                    secondary_attack = copy.deepcopy(envelope)
                    secondary_attack["secondary_recalculation"] = supplied_value
                    with self.assertRaisesRegex(
                        LifecycleError,
                        "rejects every caller-supplied secondary_recalculation",
                    ):
                        with _local_actor_binding("operator-1"):
                            append_weekly_vintage(connection, secondary_attack)
                    self.assertEqual(
                        connection.execute(
                            "SELECT COUNT(*) FROM weekly_snapshots"
                        ).fetchone()[0],
                        0,
                    )
                    self.assertEqual(
                        connection.execute(
                            "SELECT COUNT(*) FROM calculations"
                        ).fetchone()[0],
                        0,
                    )
            with _local_actor_binding("operator-1"):
                summary = append_weekly_vintage(connection, envelope)
            self.assertEqual(summary["calculation_status"], "pending_base")
            self.assertEqual(summary["review_label"], CURRENT_UNREVIEWED_LABEL)
            self.assertEqual(summary["governance_state"], "unreviewed")
            self.assertEqual(summary["publication_state"], "not_authorized")
            self.assertFalse(summary["publication_eligible"])
            diagnostics = json.loads(
                connection.execute(
                    "SELECT diagnostics_json FROM calculations WHERE id = 'calculation-week0'"
                ).fetchone()[0]
            )
            self.assertEqual(
                diagnostics["secondary_recalculation"],
                {
                    "human_external_review": False,
                    "lifecycle_handling": (
                        "no secondary recalculation report accepted by policy v1.0.0"
                    ),
                    "status": "not_supplied",
                },
            )
            rendered_diagnostics = json.dumps(diagnostics, sort_keys=True)
            self.assertNotIn("Operator-reviewed", rendered_diagnostics)
            self.assertNotIn("independently", rendered_diagnostics)
            self.assertNotIn("approved", rendered_diagnostics)
            self.assertEqual(
                set(diagnostics),
                {
                    "base_week_states",
                    "calculation_disposition",
                    "governance_state",
                    "publication_eligible",
                    "publication_state",
                    "release_kind",
                    "review_label",
                    "secondary_recalculation",
                },
            )
            with self.assertRaises(LifecycleError):
                with _local_actor_binding("operator-1"):
                    append_weekly_vintage(connection, envelope)

            final_envelope = copy.deepcopy(envelope)
            final_envelope.update(
                {
                    "snapshot_id": "snapshot-final",
                    "calculation_id": "calculation-final",
                    "release_id": "release-final",
                    "release_kind": "final_base",
                    "release_status": "final",
                    "data_vintage": "final-v1",
                    "base_week_states": ["counting"] * 13,
                }
            )
            with self.assertRaisesRegex(LifecycleError, "only as draft"):
                with _local_actor_binding("operator-1"):
                    append_weekly_vintage(connection, final_envelope)
            connection.close()

    def test_raw_sql_diagnostics_guard_and_schema_v1_migration_fail_closed(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as directory:
            database_path = Path(directory) / "raw-diagnostics.sqlite"
            connection = init_database(database_path)
            ingest_bundle(connection, self.bundle)
            governance = register_governance_actors(connection)
            register_methodology(
                connection,
                self.method,
                effective_from="2026-07-03T20:00:00Z",
                implementation_commit="b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                review_artifact_manifest_sha256="4" * 64,
            )
            connection.execute(
                "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
                (
                    "snapshot-raw",
                    self.bundle["dataset_id"],
                    self.bundle["weeks"][-1]["id"],
                    self.bundle["weeks"][-1]["cutoff_at"],
                    "2026-07-03T20:01:00Z",
                    "3" * 64,
                ),
            )
            connection.execute(
                "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
                ("snapshot-raw", "other", "raw-fixture", "2" * 64),
            )

            valid = policy_v1_diagnostics()
            extra_key = copy.deepcopy(valid)
            extra_key["independent_review"] = True
            false_label = copy.deepcopy(valid)
            false_label["review_label"] = "Operator-reviewed and approved"
            false_governance = copy.deepcopy(valid)
            false_governance["governance_state"] = "operator_reviewed"
            false_publication = copy.deepcopy(valid)
            false_publication["publication_state"] = "ready"
            false_publication["publication_eligible"] = True
            false_secondary = copy.deepcopy(valid)
            false_secondary["secondary_recalculation"] = {
                "human_external_review": True,
                "lifecycle_handling": "accepted",
                "status": "pass",
            }
            secondary_extra = copy.deepcopy(valid)
            secondary_extra["secondary_recalculation"]["reviewer"] = "named-person"
            disposition_mismatch = copy.deepcopy(valid)
            disposition_mismatch["calculation_disposition"] = "eligible"
            invalid_release_kind = copy.deepcopy(valid)
            invalid_release_kind["release_kind"] = "ready"
            invalid_base_state = copy.deepcopy(valid)
            invalid_base_state["base_week_states"] = ["approved"] * 13
            final_base_short = copy.deepcopy(valid)
            final_base_short["release_kind"] = "final_base"
            weekly_with_base = copy.deepcopy(valid)
            weekly_with_base["release_kind"] = "weekly"
            weekly_with_base["base_week_states"] = ["counting"] * 13
            complete_status_mismatch = copy.deepcopy(valid)

            attacks = (
                ("malformed JSON without actor", "{", "pending_base", False),
                (
                    "noncanonical JSON with actor",
                    json.dumps(valid, indent=2, sort_keys=True),
                    "pending_base",
                    True,
                ),
                (
                    "claim alias or extra key",
                    canonical_json_text(extra_key).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "false review label",
                    canonical_json_text(false_label).rstrip("\n"),
                    "pending_base",
                    True,
                ),
                (
                    "false governance state",
                    canonical_json_text(false_governance).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "false publication state and flag",
                    canonical_json_text(false_publication).rstrip("\n"),
                    "pending_base",
                    True,
                ),
                (
                    "false secondary flags",
                    canonical_json_text(false_secondary).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "secondary extra field",
                    canonical_json_text(secondary_extra).rstrip("\n"),
                    "pending_base",
                    True,
                ),
                (
                    "calculation disposition mismatch",
                    canonical_json_text(disposition_mismatch).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "calculation status mismatch",
                    canonical_json_text(complete_status_mismatch).rstrip("\n"),
                    "complete",
                    True,
                ),
                (
                    "uncontrolled release kind",
                    canonical_json_text(invalid_release_kind).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "uncontrolled base states",
                    canonical_json_text(invalid_base_state).rstrip("\n"),
                    "pending_base",
                    True,
                ),
                (
                    "short final base",
                    canonical_json_text(final_base_short).rstrip("\n"),
                    "pending_base",
                    False,
                ),
                (
                    "weekly release carrying base states",
                    canonical_json_text(weekly_with_base).rstrip("\n"),
                    "pending_base",
                    True,
                ),
            )
            for index, (label, diagnostics_json, status, bind_actor) in enumerate(
                attacks
            ):
                with self.subTest(label=label):
                    actor_context = (
                        _local_actor_binding("operator-1")
                        if bind_actor
                        else nullcontext()
                    )
                    with actor_context:
                        with self.assertRaisesRegex(
                            sqlite3.IntegrityError,
                            "exact policy-v1 document",
                        ):
                            connection.execute(
                                "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                (
                                    f"calculation-attack-{index}",
                                    "snapshot-raw",
                                    self.method["methodology_id"],
                                    self.method["version"],
                                    "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                                    "1" * 64,
                                    "2026-07-03T20:02:00Z",
                                    status,
                                    None,
                                    "1",
                                    diagnostics_json,
                                ),
                            )
                    self.assertEqual(
                        connection.execute(
                            "SELECT COUNT(*) FROM calculations WHERE id = ?",
                            (f"calculation-attack-{index}",),
                        ).fetchone()[0],
                        0,
                    )

            valid_json = canonical_json_text(valid).rstrip("\n")
            connection.execute(
                "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "calculation-raw-valid",
                    "snapshot-raw",
                    self.method["methodology_id"],
                    self.method["version"],
                    "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                    "1" * 64,
                    "2026-07-03T20:02:00Z",
                    "pending_base",
                    None,
                    "1",
                    valid_json,
                ),
            )
            connection.execute(
                "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "release-raw-valid",
                    "calculation-raw-valid",
                    self.bundle["weeks"][-1]["id"],
                    "raw-valid",
                    "draft",
                    None,
                    "/synthetic/raw-valid",
                    None,
                ),
            )
            raw_artifacts = [
                {
                    "path": "release.json",
                    "media_type": "application/json",
                    "content_sha256": "5" * 64,
                }
            ]
            connection.execute(
                "INSERT INTO release_artifacts VALUES(?, ?, ?, ?)",
                (
                    "release-raw-valid",
                    raw_artifacts[0]["path"],
                    raw_artifacts[0]["media_type"],
                    raw_artifacts[0]["content_sha256"],
                ),
            )
            methodology_sha256 = connection.execute(
                "SELECT methodology_sha256 FROM methodology_governance_gates "
                "WHERE methodology_id = ? AND methodology_version = ?",
                (self.method["methodology_id"], self.method["version"]),
            ).fetchone()[0]
            with _local_actor_binding("operator-1"):
                with self.assertRaisesRegex(
                    sqlite3.IntegrityError,
                    "binding calculation disposition",
                ):
                    connection.execute(
                        "INSERT INTO release_governance_bindings VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        (
                            "release-raw-valid",
                            "kapi-governance",
                            "1.0.0",
                            governance["operator_principal_id"],
                            governance["operator_assignment_id"],
                            governance["methodology_owner_principal_id"],
                            governance["methodology_owner_assignment_id"],
                            self.method["methodology_id"],
                            self.method["version"],
                            methodology_sha256,
                            "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                            sha256_bytes(canonical_json_bytes(raw_artifacts)),
                            "eligible",
                            "2026-07-03T20:03:00Z",
                        ),
                    )
            self.assertEqual(
                connection.execute(
                    "SELECT COUNT(*) FROM release_governance_bindings "
                    "WHERE release_id = 'release-raw-valid'"
                ).fetchone()[0],
                0,
            )
            with _local_actor_binding("operator-1"):
                raw_status = bind_unreviewed_release(
                    connection,
                    {
                        **governance,
                        "release_id": "release-raw-valid",
                        "methodology_id": self.method["methodology_id"],
                        "methodology_version": self.method["version"],
                        "code_commit": "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                        "artifact_manifest_sha256": sha256_bytes(
                            canonical_json_bytes(raw_artifacts)
                        ),
                        "calculation_disposition": "incomplete",
                        "bound_at": "2026-07-03T20:03:00Z",
                    },
                )
            self.assertEqual(raw_status["governance_state"], "unreviewed")
            self.assertEqual(raw_status["publication_state"], "not_authorized")

            connection.execute(
                "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
                (
                    "snapshot-no-validator",
                    self.bundle["dataset_id"],
                    self.bundle["weeks"][-1]["id"],
                    self.bundle["weeks"][-1]["cutoff_at"],
                    "2026-07-03T20:04:00Z",
                    "6" * 64,
                ),
            )
            connection.execute(
                "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
                ("snapshot-no-validator", "other", "no-validator", "7" * 64),
            )
            connection.execute(
                "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
                (
                    "snapshot-binding-no-validator",
                    self.bundle["dataset_id"],
                    self.bundle["weeks"][-1]["id"],
                    self.bundle["weeks"][-1]["cutoff_at"],
                    "2026-07-03T20:04:00Z",
                    "a" * 64,
                ),
            )
            connection.execute(
                "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
                (
                    "snapshot-binding-no-validator",
                    "other",
                    "binding-no-validator",
                    "b" * 64,
                ),
            )
            connection.execute(
                "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "calculation-binding-no-validator",
                    "snapshot-binding-no-validator",
                    self.method["methodology_id"],
                    self.method["version"],
                    "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                    "1" * 64,
                    "2026-07-03T20:05:00Z",
                    "pending_base",
                    None,
                    "1",
                    valid_json,
                ),
            )
            connection.execute(
                "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "release-binding-no-validator",
                    "calculation-binding-no-validator",
                    self.bundle["weeks"][-1]["id"],
                    "binding-no-validator",
                    "draft",
                    None,
                    "/synthetic/binding-no-validator",
                    None,
                ),
            )
            connection.commit()
            connection.close()

            unregistered = sqlite3.connect(database_path)
            unregistered.execute("PRAGMA foreign_keys = ON")
            with self.assertRaisesRegex(
                sqlite3.Error,
                "no such function: kapi_validate_calculation_diagnostics",
            ):
                unregistered.execute(
                    "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    (
                        "calculation-no-validator",
                        "snapshot-no-validator",
                        self.method["methodology_id"],
                        self.method["version"],
                        "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                        "1" * 64,
                        "2026-07-03T20:05:00Z",
                        "pending_base",
                        None,
                        "1",
                        valid_json,
                    ),
                )
            self.assertEqual(
                unregistered.execute(
                    "SELECT COUNT(*) FROM calculations "
                    "WHERE id = 'calculation-no-validator'"
                ).fetchone()[0],
                0,
            )
            with self.assertRaisesRegex(
                sqlite3.Error,
                "no such function: kapi_validate_calculation_diagnostics",
            ):
                unregistered.execute(
                    "INSERT INTO release_governance_bindings VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    (
                        "release-binding-no-validator",
                        "kapi-governance",
                        "1.0.0",
                        governance["operator_principal_id"],
                        governance["operator_assignment_id"],
                        governance["methodology_owner_principal_id"],
                        governance["methodology_owner_assignment_id"],
                        self.method["methodology_id"],
                        self.method["version"],
                        methodology_sha256,
                        "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                        sha256_bytes(canonical_json_bytes([])),
                        "incomplete",
                        "2026-07-03T20:06:00Z",
                    ),
                )
            self.assertEqual(
                unregistered.execute(
                    "SELECT COUNT(*) FROM release_governance_bindings "
                    "WHERE release_id = 'release-binding-no-validator'"
                ).fetchone()[0],
                0,
            )
            unregistered.close()

            legacy_path = Path(directory) / "schema-v1.sqlite"
            legacy = sqlite3.connect(legacy_path)
            legacy.row_factory = sqlite3.Row
            legacy.execute("PRAGMA foreign_keys = ON")
            legacy.executescript(
                (ROOT / "kapi/schema/001_initial.sql").read_text(encoding="utf-8")
            )
            legacy.execute(
                "INSERT INTO datasets VALUES(?, ?, ?, ?, ?)",
                (
                    1,
                    self.bundle["dataset_id"],
                    self.bundle["schema_version"],
                    self.bundle["dataset_kind"],
                    "{}",
                ),
            )
            legacy.execute(
                "INSERT INTO weeks VALUES(?, ?, ?, ?)",
                (
                    self.bundle["weeks"][-1]["id"],
                    self.bundle["dataset_id"],
                    self.bundle["weeks"][-1]["cutoff_at"],
                    "{}",
                ),
            )
            legacy.execute(
                "INSERT INTO methodology_versions VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    canonical_json_text(self.method.get("claim", {})).rstrip("\n"),
                    canonical_json_text(self.method.get("scope", {})).rstrip("\n"),
                    canonical_json_text(self.method.get("calendar", {})).rstrip("\n"),
                    canonical_json_text(self.method.get("evidence_policy", {})).rstrip(
                        "\n"
                    ),
                    canonical_json_text(self.method.get("selection", {})).rstrip("\n"),
                    canonical_json_text(self.method.get("concentration", {})).rstrip(
                        "\n"
                    ),
                    canonical_json_text(self.method.get("corrections", {})).rstrip(
                        "\n"
                    ),
                    "2026-07-03T20:00:00Z",
                    None,
                ),
            )
            legacy.execute(
                "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
                (
                    "snapshot-legacy-malicious",
                    self.bundle["dataset_id"],
                    self.bundle["weeks"][-1]["id"],
                    self.bundle["weeks"][-1]["cutoff_at"],
                    "2026-07-03T20:01:00Z",
                    "8" * 64,
                ),
            )
            legacy.execute(
                "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
                (
                    "snapshot-legacy-malicious",
                    "other",
                    "legacy-malicious",
                    "9" * 64,
                ),
            )
            legacy_diagnostics = copy.deepcopy(valid)
            legacy_diagnostics.update(
                {
                    "governance_state": "operator_reviewed",
                    "publication_eligible": True,
                    "publication_state": "ready",
                    "review_label": "Operator-reviewed and approved",
                }
            )
            legacy_diagnostics_json = canonical_json_text(legacy_diagnostics).rstrip(
                "\n"
            )
            legacy.execute(
                "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "calculation-legacy-malicious",
                    "snapshot-legacy-malicious",
                    self.method["methodology_id"],
                    self.method["version"],
                    "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                    "1" * 64,
                    "2026-07-03T20:02:00Z",
                    "pending_base",
                    None,
                    "1",
                    legacy_diagnostics_json,
                ),
            )
            legacy.execute(
                "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "release-legacy-malicious",
                    "calculation-legacy-malicious",
                    self.bundle["weeks"][-1]["id"],
                    "legacy-malicious",
                    "draft",
                    None,
                    "/synthetic/legacy-malicious",
                    None,
                ),
            )
            legacy.commit()

            migrated = init_database(legacy)
            self.assertEqual(migrated.execute("PRAGMA user_version").fetchone()[0], 2)
            self.assertEqual(
                migrated.execute(
                    "SELECT diagnostics_json FROM calculations "
                    "WHERE id = 'calculation-legacy-malicious'"
                ).fetchone()[0],
                legacy_diagnostics_json,
            )
            self.assertEqual(
                migrated.execute(
                    "SELECT COUNT(*) FROM release_governance_bindings"
                ).fetchone()[0],
                0,
            )
            migrated.execute(
                "INSERT INTO methodology_governance_gates VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    "kapi-governance",
                    "1.0.0",
                    "passed",
                    "passed",
                    "failed",
                    "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                    "4" * 64,
                    sha256_bytes(canonical_json_bytes(self.method)),
                    "2026-07-03T20:00:00Z",
                ),
            )
            migrated_governance = register_governance_actors(migrated)
            empty_artifact_manifest = sha256_bytes(canonical_json_bytes([]))
            raw_binding_values = (
                "release-legacy-malicious",
                "kapi-governance",
                "1.0.0",
                migrated_governance["operator_principal_id"],
                migrated_governance["operator_assignment_id"],
                migrated_governance["methodology_owner_principal_id"],
                migrated_governance["methodology_owner_assignment_id"],
                self.method["methodology_id"],
                self.method["version"],
                sha256_bytes(canonical_json_bytes(self.method)),
                "b358961d01558c26301a1aa4f9c6585fc5a3a61d",
                empty_artifact_manifest,
                "incomplete",
                "2026-07-03T20:03:00Z",
            )
            with self.assertRaisesRegex(
                sqlite3.IntegrityError,
                "stored calculation diagnostics",
            ):
                migrated.execute(
                    "INSERT INTO release_governance_bindings VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    raw_binding_values,
                )
            with _local_actor_binding("operator-1"):
                with self.assertRaisesRegex(
                    GovernanceError,
                    "stored calculation diagnostics",
                ):
                    bind_unreviewed_release(
                        migrated,
                        {
                            **migrated_governance,
                            "release_id": "release-legacy-malicious",
                            "methodology_id": self.method["methodology_id"],
                            "methodology_version": self.method["version"],
                            "code_commit": ("b358961d01558c26301a1aa4f9c6585fc5a3a61d"),
                            "artifact_manifest_sha256": empty_artifact_manifest,
                            "calculation_disposition": "incomplete",
                            "bound_at": "2026-07-03T20:03:00Z",
                        },
                    )
            self.assertEqual(
                migrated.execute(
                    "SELECT COUNT(*) FROM release_governance_bindings"
                ).fetchone()[0],
                0,
            )
            self.assertEqual(
                migrated.execute(
                    "SELECT COUNT(*) FROM governance_transition_events"
                ).fetchone()[0],
                0,
            )
            self.assertEqual(
                migrated.execute(
                    "SELECT diagnostics_json FROM calculations "
                    "WHERE id = 'calculation-legacy-malicious'"
                ).fetchone()[0],
                legacy_diagnostics_json,
            )
            migrated.close()

    def test_ten_times_unit_jump_is_held_for_review(self) -> None:
        findings = detect_price_unit_jumps(
            [
                {
                    "id": "p1",
                    "endpoint_id": "e1",
                    "component": "input",
                    "amount_per_million": "2",
                    "effective_at": "2026-07-03T00:00:00Z",
                },
                {
                    "id": "p2",
                    "endpoint_id": "e1",
                    "component": "input",
                    "amount_per_million": "20",
                    "effective_at": "2026-07-10T00:00:00Z",
                },
            ]
        )
        self.assertEqual(len(findings), 1)
        self.assertEqual(findings[0]["multiple"], "10")
        self.assertEqual(
            findings[0]["disposition"],
            "hold_for_manual_unit_and_source_review",
        )


if __name__ == "__main__":
    unittest.main()
