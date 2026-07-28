from __future__ import annotations

import copy
import csv
import hashlib
import json
import tempfile
import unittest
from pathlib import Path
from unittest import mock

import kapi.exporter as exporter_module

from kapi.calculation import (
    CalculationError,
    _calculate_index_unchecked,
    calculate_index,
)
from kapi.exporter import (
    ExportError,
    _coverage_profile_count,
    build_manifest,
    derive_release_id,
    export_release,
    render_release_files,
    reproduce_release,
)
from kapi.governance import (
    CURRENT_OPERATOR_REVIEW_LABEL,
    CURRENT_UNREVIEWED_LABEL,
    EXTERNAL_RELEASE_REVIEW_LABEL,
    METHODOLOGY_REVIEWED_OPERATOR_LABEL,
    REQUIRED_METHODOLOGY_REVIEW_SCOPE,
)
from kapi.util import canonical_json_bytes, sha256_file
from kapi.validation import validate_or_raise


ROOT = Path(__file__).resolve().parents[2]
BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-forward-governance-v0.3.0.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.3.0.json"


class ExporterTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = json.loads(BUNDLE_PATH.read_text(encoding="utf-8"))
        cls.methodology = json.loads(METHOD_PATH.read_text(encoding="utf-8"))
        cls.validation = validate_or_raise(
            cls.bundle, cls.methodology, repository_root=ROOT
        )
        cls.calculation = calculate_index(cls.bundle, cls.methodology)

    def test_export_contains_frozen_inputs_csv_json_and_manifest(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            expected = {
                "inputs/dataset.json",
                "inputs/methodology.json",
                "calculation.json",
                "release.json",
                "latest.json",
                "history.csv",
                "components.csv",
                "provenance-manifest.json",
            }
            actual = {
                path.relative_to(directory).as_posix()
                for path in Path(directory).rglob("*")
                if path.is_file()
            }
            self.assertEqual(expected, actual)
            self.assertEqual(8, summary["files_written"])

            release = json.loads(
                (Path(directory) / "release.json").read_text(encoding="utf-8")
            )
            self.assertTrue(release["not_for_publication"])
            self.assertFalse(release["deployed"])
            self.assertFalse(release["published"])
            self.assertEqual(
                release["review_label"],
                CURRENT_UNREVIEWED_LABEL,
            )
            self.assertEqual(release["governance_state"], "unreviewed")
            self.assertEqual(release["publication_state"], "not_authorized")
            self.assertFalse(release["publication_eligible"])
            self.assertEqual(
                "withheld_concentration", release["latest"]["release_status"]
            )
            manifest = json.loads(
                (Path(directory) / "provenance-manifest.json").read_text(
                    encoding="utf-8"
                )
            )
            implementation_paths = {
                entry["path"] for entry in manifest["implementation"]
            }
            self.assertIn("kapi/secondary.py", implementation_paths)
            self.assertNotIn("kapi/independent.py", implementation_paths)
            self.assertNotIn(
                "independent",
                (Path(directory) / "inputs/methodology.json")
                .read_text(encoding="utf-8")
                .lower(),
            )
            self.assertEqual(
                manifest["spending"],
                {
                    "scope": "artifact_generation_spend_and_provider_activity_not_bound",
                    "status": "not_measured_not_evidenced",
                },
            )
            self.assertEqual(
                "53.836833602584814216478190630048465266558966074314",
                release["latest"]["index_level"],
            )

            calculation = json.loads(
                (Path(directory) / "calculation.json").read_text(encoding="utf-8")
            )
            self.assertTrue(calculation["not_for_publication"])
            self.assertEqual(
                "synthetic_official_policy_simulation", calculation["series_type"]
            )
            for filename in ("history.csv", "components.csv"):
                with (Path(directory) / filename).open(
                    newline="", encoding="utf-8"
                ) as handle:
                    rows = list(csv.DictReader(handle))
                self.assertTrue(rows)
                self.assertTrue(
                    all(row["not_for_publication"] == "True" for row in rows)
                )
                self.assertTrue(
                    all(row["citation_permitted"] == "False" for row in rows)
                )
                if filename == "history.csv":
                    self.assertTrue(
                        all(
                            row["review_label"] == CURRENT_UNREVIEWED_LABEL
                            for row in rows
                        )
                    )
                    self.assertTrue(
                        all(row["publication_eligible"] == "False" for row in rows)
                    )
                self.assertTrue(all(row["dataset_kind"] == "synthetic" for row in rows))
                self.assertTrue(
                    all("SYNTHETIC KAPI PROTOTYPE" in row["notice"] for row in rows)
                )

            externally_claimed = copy.deepcopy(self.calculation)
            externally_claimed["governance_state"] = "external_release_reviewed"
            externally_claimed["review_label"] = EXTERNAL_RELEASE_REVIEW_LABEL
            externally_claimed.pop("governance_attribution", None)
            with self.assertRaisesRegex(
                ExportError, "unconditionally rejects external-release"
            ):
                render_release_files(self.bundle, self.methodology, externally_claimed)

            method_sha = hashlib.sha256(
                canonical_json_bytes(self.methodology)
            ).hexdigest()
            complete_self_supplied_attribution = {
                "review_record_id": "claimed-method-review",
                "review_kind": "methodology",
                "release_id": None,
                "reviewer_full_name": "Claimed Reviewer",
                "reviewer_affiliation": "Claimed External Firm",
                "scope": list(REQUIRED_METHODOLOGY_REVIEW_SCOPE),
                "conflict_status": "clear",
                "relationship_status": "none",
                "compensation_status": "fixed_review_fee",
                "methodology_id": self.methodology["methodology_id"],
                "methodology_version": self.methodology["version"],
                "methodology_sha256": method_sha,
                "code_commit": "c" * 40,
                "review_artifact_manifest_sha256": "d" * 64,
                "evidence_record_id": "claimed-evidence",
                "signature_scheme": "minisign-ed25519",
                "signature_key_id": "claimed-key",
                "signature_evidence_sha256": "e" * 64,
                "signed_payload_sha256": "f" * 64,
                "reviewed_at": "2026-07-03T20:03:00Z",
                "signature_verification": {
                    "verification_record_id": "claimed-verification",
                    "status": "verified_out_of_band",
                    "verification_evidence_sha256": "a" * 64,
                    "verified_at": "2026-07-03T20:04:00Z",
                    "verifier_full_name": "Claimed Verifier",
                    "verifier_affiliation": "Claimed Registrar",
                },
            }
            routine_claim = copy.deepcopy(self.calculation)
            routine_claim["governance_state"] = "operator_reviewed"
            routine_claim["review_label"] = METHODOLOGY_REVIEWED_OPERATOR_LABEL
            routine_claim["governance_attribution"] = {
                "methodology_review": complete_self_supplied_attribution
            }
            with mock.patch.object(
                exporter_module,
                "TRUSTED_VERIFIER_ADAPTER_ENABLED",
                True,
                create=True,
            ):
                with self.assertRaisesRegex(
                    ExportError, "unconditionally rejects external-methodology"
                ):
                    render_release_files(self.bundle, self.methodology, routine_claim)
                with self.assertRaisesRegex(
                    ExportError, "unconditionally rejects external-release"
                ):
                    render_release_files(
                        self.bundle, self.methodology, externally_claimed
                    )

            operator_claim = copy.deepcopy(self.calculation)
            operator_claim["governance_state"] = "operator_reviewed"
            operator_claim["review_label"] = CURRENT_OPERATOR_REVIEW_LABEL
            with self.assertRaisesRegex(
                ExportError, "no trusted operator identity adapter"
            ):
                render_release_files(self.bundle, self.methodology, operator_claim)
            forged_ready = copy.deepcopy(self.calculation)
            forged_ready["publication_state"] = "ready"
            forged_ready["publication_eligible"] = True
            with self.assertRaisesRegex(
                ExportError, "cannot export publication readiness"
            ):
                render_release_files(self.bundle, self.methodology, forged_ready)
            injected_validation = copy.deepcopy(self.validation)
            injected_validation["human_review_status"] = "approved"
            with self.assertRaisesRegex(
                ExportError, "validation report does not exactly match"
            ):
                render_release_files(
                    self.bundle,
                    self.methodology,
                    self.calculation,
                    validation_report=injected_validation,
                )
            self.assertEqual(
                release["review_label"],
                CURRENT_UNREVIEWED_LABEL,
            )

    def test_release_id_ignores_governance_envelope_but_not_math(self) -> None:
        expected = derive_release_id(self.bundle, self.methodology, self.calculation)
        changed_governance = copy.deepcopy(self.calculation)
        changed_governance.update(
            {
                "governance_state": "future-state",
                "review_label": "future label",
                "publication_state": "future-state",
                "publication_eligible": True,
                "governance_attribution": {"claimed": True},
            }
        )
        for week in changed_governance["weeks"]:
            week["governance_state"] = "future-state"
            week["review_label"] = "future label"
            week["publication_state"] = "future-state"
            week["publication_eligible"] = True
        self.assertEqual(
            expected,
            derive_release_id(self.bundle, self.methodology, changed_governance),
        )

        changed_math = copy.deepcopy(self.calculation)
        changed_math["weeks"][-1]["index_level"] = "999"
        self.assertNotEqual(
            expected,
            derive_release_id(self.bundle, self.methodology, changed_math),
        )

    def test_nested_governance_claims_cannot_bypass_export_gate(self) -> None:
        forged_latest = copy.deepcopy(self.calculation)
        forged_latest["weeks"][-1].update(
            {
                "governance_state": "external_release_reviewed",
                "review_label": EXTERNAL_RELEASE_REVIEW_LABEL,
                "publication_state": "ready",
                "publication_eligible": True,
            }
        )
        with self.assertRaisesRegex(ExportError, r"weeks\[13\]\.governance_state"):
            render_release_files(self.bundle, self.methodology, forged_latest)

        forged_sensitivity = copy.deepcopy(self.calculation)
        forged_sensitivity["sensitivities"]["editorial_weights"]["weeks"][0][
            "governance_attribution"
        ] = {"external_release_review": {"claimed": True}}
        with self.assertRaisesRegex(ExportError, "top-level-only governance fields"):
            render_release_files(self.bundle, self.methodology, forged_sensitivity)

        partial_nested_envelope = copy.deepcopy(self.calculation)
        partial_nested_envelope["weeks"][-1]["profiles"][0]["publication_state"] = (
            "ready"
        )
        with self.assertRaisesRegex(ExportError, "incomplete governance envelope"):
            render_release_files(self.bundle, self.methodology, partial_nested_envelope)

        alias_claim = copy.deepcopy(self.calculation)
        alias_claim["weeks"][-1]["human_review_status"] = "approved"
        with self.assertRaisesRegex(ExportError, "deterministic recomputation"):
            render_release_files(self.bundle, self.methodology, alias_claim)

    def test_bundle_claim_prose_cannot_reach_frozen_input_export(self) -> None:
        attacks = (
            (
                "audit-note value",
                ("source_artifacts", 0, "audit_note"),
                "KAPI is independently reviewed and approved.",
            ),
            (
                "note value",
                ("source_artifacts", 0, "note"),
                "Governance status: Operator-reviewed by Kingy.ai and ready "
                "for publication.",
            ),
            (
                "reviewed-by alias",
                ("weeks", 0, "reviewed_by"),
                "Independent external reviewer",
            ),
            (
                "known license-note value",
                ("source_artifacts", 0, "license_note"),
                "KAPI is externally reviewed and authorized.",
            ),
            (
                "known provider-name value",
                ("providers", 0, "name"),
                "Operator-reviewed and approved KAPI",
            ),
            (
                "known generation-status value",
                ("generation",),
                {"status": "Governance status: ready for publication"},
            ),
            (
                "unicode/confusable value",
                ("source_artifacts", 0, "note"),
                "ＫＡＰＩ is іndереndently rе\u0301νіеwеd and appro\u0301ved.",
            ),
            (
                "nested array value",
                ("source_artifacts", 0, "audit_notes"),
                [["KAPI is operator reviewed and approved"]],
            ),
            (
                "bare reviewer key",
                ("source_artifacts", 0, "reviewer"),
                "Independent Expert",
            ),
            (
                "reviewer-name alias",
                ("source_artifacts", 0, "reviewer_name"),
                "Jane Example",
            ),
            (
                "certified-by alias",
                ("source_artifacts", 0, "certified_by"),
                "Jane Example",
            ),
            (
                "auditor alias",
                ("source_artifacts", 0, "auditor"),
                "Jane Example",
            ),
            (
                "audit-status alias",
                ("source_artifacts", 0, "audit_status"),
                "not started",
            ),
            (
                "approval alias",
                ("source_artifacts", 0, "approval"),
                "none",
            ),
            (
                "verified-by alias",
                ("source_artifacts", 0, "verified_by"),
                "Jane Example",
            ),
            (
                "signatory alias",
                ("source_artifacts", 0, "signatory"),
                "Jane Example",
            ),
            (
                "signed-by alias",
                ("source_artifacts", 0, "signed_by"),
                "Jane Example",
            ),
            (
                "assurance-status alias",
                ("source_artifacts", 0, "assurance_status"),
                "none",
            ),
            (
                "value-only go-live readiness",
                ("source_artifacts", 0, "note"),
                "KAPI cleared to go live",
            ),
            (
                "value-only generic verification badge",
                ("source_artifacts", 0, "note"),
                "KAPI verified",
            ),
            (
                "value-only completed verification badge",
                ("source_artifacts", 0, "note"),
                "KAPI verification complete",
            ),
            (
                "value-only reviewer attribution",
                ("source_artifacts", 0, "note"),
                "KAPI reviewer: Jane",
            ),
            (
                "value-only generic assurance",
                ("source_artifacts", 0, "note"),
                "KAPI is independent",
            ),
            (
                "value-only operator attribution",
                ("source_artifacts", 0, "note"),
                "KAPI operator checked",
            ),
            (
                "value-only bare readiness",
                ("source_artifacts", 0, "note"),
                "KAPI ready",
            ),
            (
                "value-only published state",
                ("source_artifacts", 0, "note"),
                "KAPI published",
            ),
            (
                "nested KAPI status map",
                ("source_artifacts", 0, "kapi"),
                {"status": "verified"},
            ),
            (
                "sibling split map",
                ("source_artifacts", 0, "source_metadata"),
                {"subject": "KAPI", "status": "verified"},
            ),
            (
                "split string list",
                ("source_artifacts", 0, "source_metadata"),
                ["KAPI", "verified"],
            ),
            (
                "split nested list",
                ("source_artifacts", 0, "source_metadata"),
                ["KAPI", {"status": "ready"}],
            ),
            (
                "split allowed-field list",
                ("endpoints", 0, "features"),
                ["KAPI", "verified"],
            ),
            (
                "split operator review list",
                ("endpoints", 0, "features"),
                ["operator", "reviewed"],
            ),
            (
                "split independent review list",
                ("endpoints", 0, "features"),
                ["independent", "reviewed"],
            ),
            (
                "split external review list",
                ("endpoints", 0, "features"),
                ["external", "reviewed"],
            ),
            (
                "split external reviewer list",
                ("endpoints", 0, "features"),
                ["independent", "external", "reviewer"],
            ),
        )
        for label, segments, value in attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = value
                with self.assertRaisesRegex(
                    ExportError,
                    "claim-bearing key or assertion-like string",
                ):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaisesRegex(
                        ExportError,
                        "claim-bearing key or assertion-like string",
                    ):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                    )
                    self.assertEqual([], list(Path(directory).rglob("*")))

        mapping_split_attacks = tuple(
            attack
            for actor in ("operator", "independent", "external")
            for attack in (
                (
                    f"{actor} URL plus reviewed license",
                    {
                        "url": f"https://example.invalid/{actor}",
                        "license_note": "reviewed",
                    },
                ),
                (
                    f"{actor} license plus reviewed media type",
                    {"license_note": actor, "media_type": "reviewed"},
                ),
            )
        )
        for label, updates in mapping_split_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                with self.assertRaisesRegex(
                    ExportError,
                    "claim-bearing key or assertion-like string",
                ):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaisesRegex(
                        ExportError,
                        "claim-bearing key or assertion-like string",
                    ):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                        )
                    self.assertEqual([], list(Path(directory).rglob("*")))

        def assert_direct_export_blocked(attacked: dict) -> None:
            with self.assertRaisesRegex(
                ExportError,
                "claim-bearing key or assertion-like string",
            ):
                render_release_files(
                    attacked,
                    self.methodology,
                    self.calculation,
                    repository_root=ROOT,
                )
            with tempfile.TemporaryDirectory() as directory:
                with self.assertRaisesRegex(
                    ExportError,
                    "claim-bearing key or assertion-like string",
                ):
                    export_release(
                        attacked,
                        self.methodology,
                        self.calculation,
                        directory,
                        repository_root=ROOT,
                    )
                self.assertEqual([], list(Path(directory).rglob("*")))

        encoded_mapping_attacks = (
            (
                "percent-encoded operator in URL",
                {
                    "url": "https://example.invalid/op%65rator",
                    "license_note": "reviewed",
                },
            ),
            (
                "percent-encoded review in URL",
                {
                    "url": "https://example.invalid/rev%69ewed",
                    "license_note": "operator",
                },
            ),
            (
                "double-percent operator fragment in URL",
                {
                    "url": "https://example.invalid/op%2565rator",
                    "license_note": "reviewed",
                },
            ),
            (
                "HTML-entity operator in license",
                {"license_note": "op&#101;rator", "media_type": "text/reviewed"},
            ),
            (
                "HTML-entity review in one license value",
                {"license_note": "operator rev&#105;ewed"},
            ),
            (
                "percent-encoded independent in URL",
                {
                    "url": "https://example.invalid/%69ndependent",
                    "license_note": "reviewed",
                },
            ),
            (
                "fully double-percent-encoded operator in URL",
                {
                    "url": "https://example.invalid/"
                    "%256f%2570%2565%2572%2561%2574%256f%2572",
                    "license_note": "reviewed",
                },
            ),
            (
                "fully numeric-entity operator in license",
                {
                    "license_note": "&#111;&#112;&#101;&#114;&#097;&#116;&#111;&#114;",
                    "media_type": "text/reviewed",
                },
            ),
            (
                "double-HTML-entity operator in license",
                {
                    "license_note": "op&amp;#101;rator",
                    "media_type": "text/reviewed",
                },
            ),
            (
                "encoded review in URL query",
                {
                    "url": "https://example.invalid/source?state=rev%69ewed",
                    "license_note": "operator",
                },
            ),
            (
                "encoded operator in URL fragment",
                {
                    "url": "https://example.invalid/source#op%65rator",
                    "license_note": "reviewed",
                },
            ),
        )
        for label, updates in encoded_mapping_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                assert_direct_export_blocked(attacked)

        semantic_claims = (
            "approved",
            "verified",
            "audited",
            "reviewed",
            "reviewer",
            "signoff",
            "certified",
            "authorized",
            "ready",
            "passed",
            "status: approved",
            "status=verified",
            "ready to go",
            "greenlit",
            "cleared",
            "published",
            "live",
            "reviewed by Jane Example",
            "reviewed by an outside expert",
            "outside expert reviewed",
            "expert reviewed",
            "peer reviewed",
            "human reviewed",
            "editor reviewed",
            "audited by Jane",
            "certified by assessor",
            "certified by consultant",
            "attested by consultant",
            "signed by consultant",
            "review complete",
            "externally checked",
        )
        for claim in semantic_claims:
            with self.subTest(semantic_claim=claim):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0]["license_note"] = claim
                assert_direct_export_blocked(attacked)

        encoded_leaf_attacks = (
            (
                "percent-encoded KAPI verification",
                ("source_artifacts", 0, "license_note"),
                "K%41PI ver%69fied",
            ),
            (
                "double-percent KAPI review",
                ("source_artifacts", 0, "license_note"),
                "K%2541PI%2520rev%2569ewed",
            ),
            (
                "double-entity KAPI readiness",
                ("source_artifacts", 0, "license_note"),
                "K&amp;#65;PI ready",
            ),
            (
                "small-cap-v KAPI review",
                ("source_artifacts", 0, "license_note"),
                "KAPI reᴠiewed",
            ),
            (
                "small-cap-i KAPI review",
                ("source_artifacts", 0, "license_note"),
                "KAPI revɪewed",
            ),
            (
                "literal Unicode escape KAPI verification",
                ("source_artifacts", 0, "license_note"),
                r"KAPI \u0076erified",
            ),
            (
                "encoded provider-name assertion",
                ("providers", 0, "name"),
                "op%65rator rev%69ewed",
            ),
            (
                "encoded list assertion",
                ("endpoints", 0, "features"),
                ["K%41PI", "rev%69ewed"],
            ),
            (
                "percent-encoded claim key",
                ("source_artifacts", 0, "r%65viewer"),
                "Jane Example",
            ),
            (
                "double-entity claim key",
                ("source_artifacts", 0, "r&amp;#101;viewer"),
                "Jane Example",
            ),
        )
        for label, segments, value in encoded_leaf_attacks:
            with self.subTest(label=label):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = value
                assert_direct_export_blocked(attacked)

        semantic_split_lists = (
            ["reviewed by", "Jane Example"],
            ["outside expert", "reviewed"],
            ["audited", "by Jane"],
            ["certified", "by assessor"],
        )
        for carrier in semantic_split_lists:
            with self.subTest(semantic_split_list=carrier):
                attacked = copy.deepcopy(self.bundle)
                attacked["endpoints"][0]["features"] = carrier
                assert_direct_export_blocked(attacked)

        semantic_split_mappings = (
            {
                "url": "https://example.invalid/audited",
                "license_note": "by Jane",
            },
            {"license_note": "expert", "media_type": "text/reviewed"},
            {
                "license_note": "KAPI status",
                "url": "https://example.invalid/verified",
            },
            {
                "license_note": "publication status",
                "url": "https://example.invalid/ready",
            },
            {
                "license_note": "KAPI status",
                "url": "https://example.invalid/independent",
            },
        )
        for updates in semantic_split_mappings:
            with self.subTest(semantic_split_mapping=updates):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0].update(updates)
                assert_direct_export_blocked(attacked)

        cross_depth_split = copy.deepcopy(self.bundle)
        cross_depth_artifact = next(
            artifact
            for artifact in cross_depth_split["source_artifacts"]
            if "regime" in artifact["synthetic_content"]
        )
        cross_depth_artifact["license_note"] = "KAPI"
        cross_depth_artifact["synthetic_content"]["regime"] = "verified"
        with self.assertRaisesRegex(
            ExportError,
            "claim-bearing key or assertion-like string",
        ):
            render_release_files(
                cross_depth_split,
                self.methodology,
                self.calculation,
                repository_root=ROOT,
            )

        formerly_benign = copy.deepcopy(self.bundle)
        formerly_benign["source_artifacts"][0]["license_note"] = (
            "Source hash verified against retained bytes; license reviewed for "
            "archival completeness."
        )
        formerly_benign["providers"][0]["name"] = "Audit Trail Research"
        formerly_benign["creators"][0]["name"] = "Approved License Research"
        with self.assertRaisesRegex(
            ExportError, "closed public scalar grammar"
        ):
            render_release_files(
                formerly_benign,
                self.methodology,
                self.calculation,
                repository_root=ROOT,
            )

    def test_forward_input_oracles_spend_claims_and_scalar_carriers_fail_closed(
        self,
    ) -> None:
        oracle_attacks = (
            {
                "expected_result": {
                    "strict_headline": {
                        "status": "approved",
                        "reason": "all_gates_passed",
                    }
                }
            },
            {"expected_result": {"strict_headline": {"numeric_oracle": 1}}},
            {
                "generation": {
                    "model_calls_performed": 5,
                    "network_access_used": True,
                    "paid_calls_performed": 5,
                    "total_external_spend_usd": "123.45",
                }
            },
            {
                "base_period_week_ids": [
                    week["id"] for week in self.bundle["weeks"][:13]
                ]
            },
            {"current_week_id": self.bundle["weeks"][-1]["id"]},
        )
        for additions in oracle_attacks:
            with self.subTest(additions=additions):
                attacked = copy.deepcopy(self.bundle)
                attacked.update(additions)
                with self.assertRaises(CalculationError):
                    calculate_index(attacked, self.methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaises(ExportError):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                        )
                    self.assertEqual([], list(Path(directory).rglob("*")))

        binding_attacks = (
            ("id", "not-kapi"),
            ("version", "9.9.9"),
            ("config_path", "kapi/config/other.json"),
            ("config_sha256", "0" * 64),
        )
        for field, value in binding_attacks:
            with self.subTest(binding_field=field):
                attacked = copy.deepcopy(self.bundle)
                attacked["methodology"][field] = value
                with self.assertRaises(CalculationError):
                    calculate_index(attacked, self.methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )

        public_scalar_claims = (
            "fully approved",
            "Jane completed the review",
            "publication eligible",
            "not unreviewed",
            "KAPI rev1ewed",
        )
        for claim in public_scalar_claims:
            with self.subTest(public_scalar_claim=claim):
                attacked = copy.deepcopy(self.bundle)
                attacked["source_artifacts"][0]["license_note"] = claim
                with self.assertRaisesRegex(
                    CalculationError, "closed public scalar grammar"
                ):
                    calculate_index(attacked, self.methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaises(ExportError):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                        )
                    self.assertEqual([], list(Path(directory).rglob("*")))

        type_attacks = (
            ("provider name bool", ("providers", 0, "name"), True),
            ("provider name null", ("providers", 0, "name"), None),
            ("provider name int", ("providers", 0, "name"), 7),
            (
                "count note bool",
                ("token_counts", 0, "synthetic_count_note"),
                True,
            ),
            ("feature bool", ("endpoints", 0, "features", 0), True),
        )
        for label, segments, replacement in type_attacks:
            with self.subTest(type_attack=label):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                target[segments[-1]] = replacement
                with self.assertRaisesRegex(
                    CalculationError, "closed public scalar grammar"
                ):
                    calculate_index(attacked, self.methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaises(ExportError):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                        )
                    self.assertEqual([], list(Path(directory).rglob("*")))

        regime_attack = copy.deepcopy(self.bundle)
        regime_artifact = next(
            artifact
            for artifact in regime_attack["source_artifacts"]
            if "regime" in artifact["synthetic_content"]
        )
        regime_artifact["synthetic_content"]["regime"] = True
        regime_artifact["content_sha256"] = hashlib.sha256(
            canonical_json_bytes(regime_artifact["synthetic_content"])
        ).hexdigest()
        with self.assertRaisesRegex(
            CalculationError, "closed public scalar grammar"
        ):
            calculate_index(regime_attack, self.methodology)
        with tempfile.TemporaryDirectory() as directory:
            with self.assertRaises(ExportError):
                export_release(
                    regime_attack,
                    self.methodology,
                    self.calculation,
                    directory,
                    repository_root=ROOT,
                )
            self.assertEqual([], list(Path(directory).rglob("*")))

        def assert_omission_blocked(attacked: dict) -> None:
            with self.assertRaisesRegex(
                CalculationError, "closed public scalar grammar"
            ):
                calculate_index(attacked, self.methodology)
            with self.assertRaises(ExportError):
                render_release_files(
                    attacked,
                    self.methodology,
                    self.calculation,
                    repository_root=ROOT,
                )
            with tempfile.TemporaryDirectory() as directory:
                with self.assertRaises(ExportError):
                    export_release(
                        attacked,
                        self.methodology,
                        self.calculation,
                        directory,
                        repository_root=ROOT,
                    )
                self.assertEqual([], list(Path(directory).rglob("*")))

        omission_paths = (
            ("providers", 0, "synthetic"),
            ("models", 0, "version"),
            ("endpoints", 0, "tokenizer_reproducible"),
            ("token_counts", 0, "synthetic_count_note"),
        )
        for segments in omission_paths:
            with self.subTest(omission=segments):
                attacked = copy.deepcopy(self.bundle)
                target = attacked
                for segment in segments[:-1]:
                    target = target[segment]
                del target[segments[-1]]
                assert_omission_blocked(attacked)

        for field in ("model_calls_performed", "network_access_used"):
            with self.subTest(source_content_omission=field):
                attacked = copy.deepcopy(self.bundle)
                artifact = next(
                    item
                    for item in attacked["source_artifacts"]
                    if "capability_scope" in item["synthetic_content"]
                )
                del artifact["synthetic_content"][field]
                artifact["content_sha256"] = hashlib.sha256(
                    canonical_json_bytes(artifact["synthetic_content"])
                ).hexdigest()
                assert_omission_blocked(attacked)

    def test_bounded_fixture_identity_blocks_in_grammar_export_permutations(
        self,
    ) -> None:
        attacks: list[tuple[str, dict]] = []
        provider_swap = copy.deepcopy(self.bundle)
        provider_swap["providers"][0]["name"] = provider_swap["providers"][1][
            "name"
        ]
        attacks.append(("provider name swap", provider_swap))

        model_version_swap = copy.deepcopy(self.bundle)
        model_version_swap["models"][0]["version"] = model_version_swap["models"][
            1
        ]["version"]
        attacks.append(("model version swap", model_version_swap))

        capability_source_change = copy.deepcopy(self.bundle)
        capability_source = next(
            item
            for item in capability_source_change["source_artifacts"]
            if "capability_scope" in item["synthetic_content"]
        )
        capability_source["synthetic_content"]["score"] = "149"
        capability_source["content_sha256"] = hashlib.sha256(
            canonical_json_bytes(capability_source["synthetic_content"])
        ).hexdigest()
        attacks.append(("capability snapshot", capability_source_change))

        price_source_change = copy.deepcopy(self.bundle)
        price_source = next(
            item
            for item in price_source_change["source_artifacts"]
            if "regime" in item["synthetic_content"]
        )
        price_source["synthetic_content"]["input_amount_per_million"] = "999"
        price_source["content_sha256"] = hashlib.sha256(
            canonical_json_bytes(price_source["synthetic_content"])
        ).hexdigest()
        attacks.append(("price snapshot", price_source_change))

        for label, attacked in attacks:
            with self.subTest(identity_permutation=label):
                with self.assertRaisesRegex(
                    CalculationError, "canonical bounded forward fixture"
                ):
                    calculate_index(attacked, self.methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        attacked,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    with self.assertRaises(ExportError):
                        export_release(
                            attacked,
                            self.methodology,
                            self.calculation,
                            directory,
                            repository_root=ROOT,
                        )
                    self.assertEqual([], list(Path(directory).rglob("*")))

    def test_forward_contract_downgrades_fail_before_any_export_write(self) -> None:
        valid_files = render_release_files(
            self.bundle,
            self.methodology,
            self.calculation,
            repository_root=ROOT,
        )
        cases: list[tuple[str, dict, dict]] = []
        for version in ("0.2.0", "0.2.1", "0.2.2"):
            older_method = json.loads(
                (ROOT / f"kapi/config/methodology-v{version}.json").read_text(
                    encoding="utf-8"
                )
            )
            cases.append(
                (f"forward bundle with method {version}", self.bundle, older_method)
            )
            for dataset_id in (
                "synthetic-hand-example-v1",
                "arbitrary-legacy-looking-dataset",
            ):
                rewritten = copy.deepcopy(self.bundle)
                rewritten["schema_version"] = "kapi-bundle-v0.1.0"
                rewritten["dataset_id"] = dataset_id
                rewritten["methodology"] = {
                    "config_path": f"kapi/config/methodology-v{version}.json",
                    "config_sha256": hashlib.sha256(
                        canonical_json_bytes(older_method)
                    ).hexdigest(),
                    "id": older_method["methodology_id"],
                    "version": version,
                }
                cases.append(
                    (
                        f"full marker rewrite {version} {dataset_id}",
                        rewritten,
                        older_method,
                    )
                )

        historical_bundle = json.loads(
            (ROOT / "kapi/fixtures/synthetic-hand-example-v1.json").read_text(
                encoding="utf-8"
            )
        )
        cases.append(
            (
                "historical bundle with method 0.3.0",
                historical_bundle,
                self.methodology,
            )
        )

        newer_schema = copy.deepcopy(self.bundle)
        newer_schema["schema_version"] = "kapi-bundle-v0.3.1"
        cases.append(("mismatched v0.3 schema", newer_schema, self.methodology))

        disguised_schema = copy.deepcopy(self.bundle)
        disguised_schema["schema_version"] = "kapi-bundle-v0.2.2"
        older_method = json.loads(
            (ROOT / "kapi/config/methodology-v0.2.2.json").read_text(
                encoding="utf-8"
            )
        )
        cases.append(
            ("forward fixture with disguised schema", disguised_schema, older_method)
        )

        for label, bundle, methodology in cases:
            with self.subTest(downgrade=label):
                with self.assertRaisesRegex(
                    CalculationError, "exact v0.3.0 bundle/methodology pair"
                ):
                    calculate_index(bundle, methodology)
                with self.assertRaises(ExportError):
                    render_release_files(
                        bundle,
                        methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with self.assertRaises(ExportError):
                    build_manifest(
                        valid_files,
                        bundle,
                        methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )
                with tempfile.TemporaryDirectory() as directory:
                    destination = Path(directory) / "release"
                    with self.assertRaises(ExportError):
                        export_release(
                            bundle,
                            methodology,
                            self.calculation,
                            destination,
                            repository_root=ROOT,
                        )
                    self.assertFalse(destination.exists())

        for relative in (
            "inputs/dataset.json",
            "inputs/methodology.json",
            "calculation.json",
            "release.json",
            "latest.json",
            "history.csv",
            "components.csv",
        ):
            with self.subTest(manifest_input_mismatch=relative):
                mismatched_files = dict(valid_files)
                mismatched_files[relative] = valid_files[relative] + b" "
                with self.assertRaisesRegex(ExportError, "authoritative render"):
                    build_manifest(
                        mismatched_files,
                        self.bundle,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )

        forged_release = json.loads(valid_files["release.json"])
        forged_release.update(
            {
                "release_id": "forged-release",
                "governance_state": "external_release_reviewed",
                "review_label": "KAPI independently reviewed",
                "publication_state": "ready",
                "publication_eligible": True,
            }
        )
        forged_latest = json.loads(valid_files["latest.json"])
        forged_latest.update(
            {
                "governance_state": "external_release_reviewed",
                "review_label": "KAPI independently reviewed",
                "publication_state": "ready",
                "publication_eligible": True,
            }
        )
        semantic_file_attacks = {
            "release.json": canonical_json_bytes(forged_release),
            "latest.json": canonical_json_bytes(forged_latest),
            "history.csv": valid_files["history.csv"] + b"forged,row\n",
            "components.csv": (
                valid_files["components.csv"] + b"KAPI independently reviewed\n"
            ),
        }
        for relative, forged_bytes in semantic_file_attacks.items():
            with self.subTest(manifest_semantic_forgery=relative):
                forged_files = dict(valid_files)
                forged_files[relative] = forged_bytes
                with self.assertRaisesRegex(ExportError, "authoritative render"):
                    build_manifest(
                        forged_files,
                        self.bundle,
                        self.methodology,
                        self.calculation,
                        repository_root=ROOT,
                    )

    def test_reproduce_is_byte_exact_and_detects_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertTrue(report["reproduced"])
            self.assertEqual([], report["mismatches"])

            history = Path(directory) / "history.csv"
            history.write_bytes(history.read_bytes() + b"tampered\n")
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                "history.csv",
                {item["path"] for item in report["mismatches"]},
            )

    def test_two_exports_are_identical(self) -> None:
        with (
            tempfile.TemporaryDirectory() as first,
            tempfile.TemporaryDirectory() as second,
        ):
            first_summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                first,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            second_summary = export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                second,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            self.assertEqual(first_summary["release_id"], second_summary["release_id"])
            for first_path in sorted(Path(first).rglob("*")):
                if not first_path.is_file():
                    continue
                relative = first_path.relative_to(first)
                second_path = Path(second) / relative
                self.assertTrue(second_path.is_file(), relative.as_posix())
                self.assertEqual(
                    sha256_file(first_path),
                    sha256_file(second_path),
                    relative.as_posix(),
                )

    def test_manifest_metadata_tampering_is_detected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            manifest_path = Path(directory) / "provenance-manifest.json"
            manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
            manifest["notice"] = "tampered"
            manifest["spending"]["status"] = "measured_zero"
            manifest["source_lineage"] = []
            manifest["implementation"] = []
            manifest_path.write_bytes(canonical_json_bytes(manifest))

            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                "provenance-manifest.json",
                {item["path"] for item in report["mismatches"]},
            )

    def test_nondefault_weight_export_reproduces(self) -> None:
        weights = {
            "analysis-reasoning": "20",
            "code-repair": "15",
            "grounded-rag": "20",
            "structured-extraction": "20",
            "summarization-transformation": "15",
            "tool-workflow": "10",
        }
        calculation = calculate_index(self.bundle, self.methodology, weights=weights)
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertTrue(report["reproduced"], report["mismatches"])

    def test_incomplete_week_csv_coverage_counts_complete_profiles_only(self) -> None:
        bundle = json.loads(
            (ROOT / "kapi/fixtures/synthetic-hand-example-v1.json").read_text(
                encoding="utf-8"
            )
        )
        methodology = json.loads(
            (ROOT / "kapi/config/methodology-v0.2.2.json").read_text(
                encoding="utf-8"
            )
        )
        first_week_id = bundle["weeks"][0]["id"]
        bundle["price_observations"] = [
            observation
            for observation in bundle["price_observations"]
            if observation["week_id"] != first_week_id
        ]
        calculation = _calculate_index_unchecked(bundle, methodology)
        self.assertEqual(
            0,
            _coverage_profile_count(calculation["weeks"][0]["profiles"]),
        )

    def test_unmanifested_release_file_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            (Path(directory) / "unmanifested.txt").write_text(
                "not part of the release", encoding="utf-8"
            )
            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                {"path": "unmanifested.txt", "problem": "unmanifested_file"},
                report["mismatches"],
            )

    def test_required_file_removed_from_manifest_and_disk_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            export_release(
                self.bundle,
                self.methodology,
                self.calculation,
                directory,
                repository_root=ROOT,
                validation_report=self.validation,
            )
            dataset_path = Path(directory) / "inputs/dataset.json"
            dataset_path.unlink()
            manifest_path = Path(directory) / "provenance-manifest.json"
            manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
            manifest["files"] = [
                entry
                for entry in manifest["files"]
                if entry["path"] != "inputs/dataset.json"
            ]
            manifest_path.write_bytes(canonical_json_bytes(manifest))

            report = reproduce_release(directory, repository_root=ROOT)
            self.assertFalse(report["reproduced"])
            self.assertIn(
                {
                    "path": "inputs/dataset.json",
                    "problem": "required_manifest_entry_missing",
                },
                report["mismatches"],
            )


if __name__ == "__main__":
    unittest.main()
