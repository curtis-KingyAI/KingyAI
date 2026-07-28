"""Regression tests for the forward-only KAPI governance vintage."""

from __future__ import annotations

import copy
import hashlib
import json
import sqlite3
import unittest
from pathlib import Path

from kapi.governance import (
    CURRENT_OPERATOR_REVIEW_LABEL,
    CURRENT_UNREVIEWED_LABEL,
    EXTERNAL_RELEASE_REVIEW_LABEL,
    METHODOLOGY_REVIEWED_OPERATOR_LABEL,
    GovernanceError,
    REQUIRED_METHODOLOGY_REVIEW_SCOPE,
    REQUIRED_RELEASE_REVIEW_SCOPE,
    append_governance_transition,
    governance_review_attribution,
    governance_status,
    record_external_review,
    record_signature_verification_claim,
    register_external_reviewer,
    register_principal,
    register_role_assignment,
    revoke_external_reviewer,
    supersede_external_reviewer,
)
from kapi.lifecycle import append_weekly_vintage, register_methodology
from kapi.store import _local_actor_binding, ingest_bundle, init_database


ROOT = Path(__file__).resolve().parents[2]
BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-forward-governance-v0.3.0.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.3.0.json"
REGISTRAR = "kapi-identity-registrar"


class GovernanceTests(unittest.TestCase):
    def setUp(self) -> None:
        self.connection = init_database(":memory:")
        self.bundle = json.loads(BUNDLE_PATH.read_text(encoding="utf-8"))
        self.method_template = json.loads(METHOD_PATH.read_text(encoding="utf-8"))
        ingest_bundle(self.connection, self.bundle)
        self._register_actors()
        self.method = self._register_method("main", technical="passed", operational="passed")
        self.release_id = self._append_release(self.method, "main")

    def tearDown(self) -> None:
        self.connection.close()

    def _register_actors(self) -> None:
        with _local_actor_binding(REGISTRAR):
            for index, (principal_id, affiliation) in enumerate(
                (
                    ("operator-1", "Kingy.ai"),
                    ("owner-1", "Kingy.ai"),
                    ("reviewer-1", "Outside Review Co"),
                    ("verifier-authorizer", "Kingy.ai"),
                ),
                start=1,
            ):
                register_principal(
                    self.connection,
                    {
                        "id": principal_id,
                        "identity_key": f"person-controller:{principal_id}",
                        "full_name": principal_id.replace("-", " ").title(),
                        "affiliation": affiliation,
                        "principal_kind": "human",
                        "identity_evidence_sha256": str(index) * 64,
                        "registered_by_principal_id": REGISTRAR,
                        "registered_at": "2026-07-03T18:00:00Z",
                    },
                )
            for assignment_id, principal_id, role, digest in (
                ("operator-role", "operator-1", "operator", "4" * 64),
                ("owner-role", "owner-1", "methodology_owner", "5" * 64),
                ("reviewer-role", "reviewer-1", "external_reviewer", "6" * 64),
                ("release-role", "owner-1", "release_authorizer", "7" * 64),
                ("publication-role", "owner-1", "publication_authorizer", "8" * 64),
                (
                    "verifier-registrar-role",
                    "verifier-authorizer",
                    "reviewer_registrar",
                    "c" * 64,
                ),
                (
                    "verifier-publication-role",
                    "verifier-authorizer",
                    "publication_authorizer",
                    "d" * 64,
                ),
            ):
                register_role_assignment(
                    self.connection,
                    {
                        "id": assignment_id,
                        "principal_id": principal_id,
                        "role": role,
                        "appointed_by_principal_id": REGISTRAR,
                        "appointed_at": "2026-07-03T18:05:00Z",
                        "valid_from": "2026-07-03T18:05:00Z",
                        "valid_until": "2027-07-03T18:05:00Z",
                        "appointment_sha256": digest,
                    },
                )
            register_external_reviewer(
                self.connection,
                {
                    "principal_id": "reviewer-1",
                    "qualifications_summary": "Index methodology and evidence review.",
                    "qualifications_sha256": "9" * 64,
                    "registered_at": "2026-07-03T18:10:00Z",
                    "valid_until": "2027-07-03T18:10:00Z",
                    "registered_by_principal_id": REGISTRAR,
                    "registrar_evidence_sha256": "a" * 64,
                    "signature_scheme": "minisign-ed25519",
                    "signature_key_id": "reviewer-1-key-2026",
                    "public_key_sha256": "b" * 64,
                    "status": "active",
                },
            )

    def _register_method(
        self, suffix: str, *, technical: str, operational: str
    ) -> dict:
        method = copy.deepcopy(self.method_template)
        method["version"] = f"governance-{suffix}"
        method["readiness_gates"]["technical_go"] = technical
        method["readiness_gates"]["operational_go"] = operational
        method["governance_policy"] = {
            "policy_id": "kapi-governance",
            "version": "1.0.0",
        }
        register_methodology(
            self.connection,
            method,
            effective_from="2026-07-03T19:00:00Z",
            implementation_commit="c" * 40,
            review_artifact_manifest_sha256="8" * 64,
        )
        return method

    def _append_release(self, method: dict, suffix: str) -> str:
        release_id = f"release-{suffix}"
        with _local_actor_binding("operator-1"):
            summary = append_weekly_vintage(
                self.connection,
                {
                "release_kind": "weekly",
                "dataset_id": self.bundle["dataset_id"],
                "week_id": self.bundle["weeks"][-1]["id"],
                "snapshot_id": f"snapshot-{suffix}",
                "calculation_id": f"calculation-{suffix}",
                "release_id": release_id,
                "cutoff_at": self.bundle["weeks"][-1]["cutoff_at"],
                "created_at": "2026-07-03T20:00:00Z",
                "calculated_at": "2026-07-03T20:01:00Z",
                "methodology_id": method["methodology_id"],
                "methodology_version": method["version"],
                "code_commit": "c" * 40,
                "environment_sha256": "d" * 64,
                "inputs": [
                    {
                        "input_kind": "other",
                        "input_id": f"fixture-{suffix}",
                        "content_sha256": "e" * 64,
                    }
                ],
                "calculation": {
                    "status": "complete",
                    "index_value": "100",
                    "basket_cost": "1",
                    "diagnostics": {},
                },
                "release_status": "draft",
                "data_vintage": f"governance-{suffix}",
                "permanent_path": f"/governance/{suffix}",
                "artifacts": [
                    {
                        "path": "release.json",
                        "media_type": "application/json",
                        "content_sha256": "f" * 64,
                    }
                ],
                "signoffs": [],
                "governance": {
                    "policy_id": "kapi-governance",
                    "policy_version": "1.0.0",
                    "operator_principal_id": "operator-1",
                    "operator_assignment_id": "operator-role",
                    "methodology_owner_principal_id": "owner-1",
                    "methodology_owner_assignment_id": "owner-role",
                },
                },
            )
        self.assertEqual(summary["review_label"], CURRENT_UNREVIEWED_LABEL)
        self.assertEqual(summary["governance_state"], "unreviewed")
        self.assertFalse(summary["publication_eligible"])
        return release_id

    def _review(self, release_id: str, review_id: str, kind: str) -> dict:
        binding = self.connection.execute(
            "SELECT * FROM release_governance_bindings WHERE release_id = ?",
            (release_id,),
        ).fetchone()
        gate = self.connection.execute(
            "SELECT implementation_commit, review_artifact_manifest_sha256 "
            "FROM methodology_governance_gates "
            "WHERE methodology_id = ? AND methodology_version = ?",
            (binding["methodology_id"], binding["methodology_version"]),
        ).fetchone()
        return {
            "id": review_id,
            "review_kind": kind,
            "release_id": release_id if kind == "release" else None,
            "reviewer_principal_id": "reviewer-1",
            "reviewer_appointment_id": "reviewer-role",
            "methodology_owner_principal_id": "owner-1",
            "scope": list(
                REQUIRED_RELEASE_REVIEW_SCOPE
                if kind == "release"
                else REQUIRED_METHODOLOGY_REVIEW_SCOPE
            ),
            "conflict_declaration": {
                "status": "clear",
                "statement": "No authorship, ownership, reporting-line, or outcome conflict.",
                "declared_at": "2026-07-03T20:02:00Z",
            },
            "relationship_disclosure": {
                "status": "none",
                "statement": "No employment, ownership, family, or reporting relationship.",
                "declared_at": "2026-07-03T20:02:00Z",
            },
            "compensation_disclosure": {
                "status": "fixed_review_fee",
                "statement": "Fixed fee independent of findings or publication outcome.",
                "declared_at": "2026-07-03T20:02:00Z",
            },
            "methodology_id": binding["methodology_id"],
            "methodology_version": binding["methodology_version"],
            "methodology_sha256": binding["methodology_sha256"],
            "code_commit": (
                binding["code_commit"]
                if kind == "release"
                else gate["implementation_commit"]
            ),
            "review_artifact_manifest_sha256": (
                binding["artifact_manifest_sha256"]
                if kind == "release"
                else gate["review_artifact_manifest_sha256"]
            ),
            "outcome": "approved",
            "report_sha256": "b" * 64,
            "findings": [{"id": "finding-1", "disposition": "pass"}],
            "unresolved_issues": [],
            "evidence_record_id": f"evidence:{review_id}",
            "signature_scheme": "minisign-ed25519",
            "signature_key_id": "reviewer-1-key-2026",
            "signature_evidence_sha256": "d" * 64,
            "reviewed_at": "2026-07-03T20:03:00Z",
            "valid_until": "2026-07-03T22:00:00Z",
            "recorded_at": "2026-07-03T20:04:00Z",
        }

    def _accept_release_review(self, release_id: str, review_id: str, at: str) -> dict:
        with _local_actor_binding("owner-1"):
            return append_governance_transition(
                self.connection,
                {
                    "id": f"{release_id}:accept:{review_id}",
                    "release_id": release_id,
                    "action": "accept_external_release_review",
                    "actor_principal_id": "owner-1",
                    "actor_assignment_id": "release-role",
                    "release_review_record_id": review_id,
                    "transitioned_at": at,
                    "reason": "Accept the exact-release review record.",
                },
            )

    def _authorize(self, release_id: str, method_review_id: str, suffix: str) -> dict:
        with _local_actor_binding("owner-1"):
            return append_governance_transition(
                self.connection,
                {
                    "id": f"{release_id}:authorize:{suffix}",
                    "release_id": release_id,
                    "action": "authorize_publication",
                    "actor_principal_id": "owner-1",
                    "actor_assignment_id": "publication-role",
                    "methodology_review_record_id": method_review_id,
                    "transitioned_at": "2026-07-03T20:06:00Z",
                    "reason": "All separately modeled publication gates pass.",
                },
            )

    def _record_and_verify(self, review: dict) -> None:
        with _local_actor_binding("reviewer-1"):
            record_external_review(self.connection, review)
        row = self.connection.execute(
            "SELECT * FROM external_review_records WHERE id = ?", (review["id"],)
        ).fetchone()
        with _local_actor_binding(REGISTRAR):
            record_signature_verification_claim(
                self.connection,
                {
                    "id": f"signature-verification:{review['id']}",
                    "review_record_id": review["id"],
                    "verifier_principal_id": REGISTRAR,
                    "verifier_assignment_id": "kapi-identity-registrar:reviewer-role",
                    "signature_scheme": row["signature_scheme"],
                    "signature_key_id": row["signature_key_id"],
                    "signed_payload_sha256": row["signed_payload_sha256"],
                    "signature_evidence_sha256": row[
                        "signature_evidence_sha256"
                    ],
                    "verification_evidence_sha256": "e" * 64,
                    "verified_at": "2026-07-03T20:04:30Z",
                    "status": "untrusted_local_claim",
                },
            )

    def test_unreviewed_label_and_separated_states_are_exact(self) -> None:
        status = governance_status(self.connection, self.release_id)
        self.assertEqual(status["review_label"], CURRENT_UNREVIEWED_LABEL)
        self.assertEqual(status["calculation_disposition"], "eligible")
        self.assertEqual(status["governance_state"], "unreviewed")
        self.assertEqual(status["publication_state"], "not_authorized")
        self.assertFalse(status["publication_eligible"])
        self.assertNotIn("independent", json.dumps(status).lower())

    def test_stable_identity_and_registrar_controls_block_alias_and_self_action(self) -> None:
        alias = {
            "id": "operator-alias",
            "identity_key": "person-controller:operator-alias",
            "full_name": "Operator Alias",
            "affiliation": "Kingy.ai",
            "principal_kind": "human",
            "identity_evidence_sha256": "1" * 64,
            "registered_by_principal_id": REGISTRAR,
            "registered_at": "2026-07-03T18:01:00Z",
        }
        untrusted = dict(alias)
        untrusted.update(
            {
                "id": "untrusted-registrant",
                "identity_key": "person-controller:untrusted-registrant",
                "identity_evidence_sha256": "f" * 64,
            }
        )
        with self.assertRaisesRegex(GovernanceError, "actor binding missing"):
            register_principal(self.connection, untrusted)

        with _local_actor_binding(REGISTRAR):
            with self.assertRaisesRegex(GovernanceError, "UNIQUE"):
                register_principal(self.connection, alias)

        self_record = dict(alias)
        self_record.update(
            {
                "id": "self-registrant",
                "identity_key": "person-controller:self-registrant",
                "identity_evidence_sha256": "0" * 63 + "1",
                "registered_by_principal_id": "self-registrant",
            }
        )
        with _local_actor_binding("self-registrant"):
            with self.assertRaisesRegex(GovernanceError, "self-register"):
                register_principal(self.connection, self_record)

        with _local_actor_binding("owner-1"):
            with self.assertRaisesRegex(GovernanceError, "self-appoint"):
                register_role_assignment(
                    self.connection,
                    {
                    "id": "owner-self-role",
                    "principal_id": "owner-1",
                    "role": "publication_authorizer",
                    "appointed_by_principal_id": "owner-1",
                    "appointed_at": "2026-07-03T18:06:00Z",
                    "valid_from": "2026-07-03T18:06:00Z",
                    "valid_until": "2027-07-03T18:06:00Z",
                    "appointment_sha256": "e" * 64,
                    },
                )

        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "self-register"):
                register_external_reviewer(
                    self.connection,
                    {
                    "principal_id": "reviewer-1",
                    "qualifications_summary": "Self registered.",
                    "qualifications_sha256": "a" * 64,
                    "registered_at": "2026-07-03T18:10:00Z",
                    "valid_until": "2027-07-03T18:10:00Z",
                    "registered_by_principal_id": "reviewer-1",
                    "registrar_evidence_sha256": "b" * 64,
                    "signature_scheme": "minisign-ed25519",
                    "signature_key_id": "reviewer-1-key-2026",
                    "public_key_sha256": "c" * 64,
                    "status": "active",
                    },
                )

    def test_review_fields_scope_vintage_timestamp_and_commit_fail_closed(self) -> None:
        conflict = self._review(self.release_id, "review-conflict", "release")
        conflict["conflict_declaration"]["status"] = "disclosed"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "status must be"):
                record_external_review(self.connection, conflict)

        relationship = self._review(self.release_id, "review-relationship", "release")
        relationship["relationship_disclosure"]["status"] = "current"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "status must be"):
                record_external_review(self.connection, relationship)

        malformed_scope = self._review(self.release_id, "review-scope", "methodology")
        malformed_scope["scope"] = ["methodology"]
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "must equal"):
                record_external_review(self.connection, malformed_scope)

        wrong_hash = self._review(self.release_id, "review-wrong-hash", "release")
        wrong_hash["review_artifact_manifest_sha256"] = "0" * 64
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "binding mismatch"):
                record_external_review(self.connection, wrong_hash)

        wrong_commit = self._review(self.release_id, "review-wrong-commit", "release")
        wrong_commit["code_commit"] = "ABC"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "40- or 64-character"):
                record_external_review(self.connection, wrong_commit)

        wrong_method_commit = self._review(
            self.release_id, "method-review-wrong-commit", "methodology"
        )
        wrong_method_commit["code_commit"] = "0" * 40
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "binding mismatch"):
                record_external_review(self.connection, wrong_method_commit)

        wrong_method_artifact = self._review(
            self.release_id, "method-review-wrong-artifact", "methodology"
        )
        wrong_method_artifact["review_artifact_manifest_sha256"] = "0" * 64
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "binding mismatch"):
                record_external_review(self.connection, wrong_method_artifact)

        wrong_release_id = self._review(
            self.release_id, "release-review-wrong-release", "release"
        )
        wrong_release_id["release_id"] = "release-not-the-bound-content"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "binding mismatch"):
                record_external_review(self.connection, wrong_release_id)

        wrong_time = self._review(self.release_id, "review-wrong-time", "methodology")
        wrong_time["reviewed_at"] = "2026-07-03T20:03:00+00:00"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "canonical ISO-8601 UTC"):
                record_external_review(self.connection, wrong_time)

        unsupported = self._review(self.release_id, "review-scheme", "methodology")
        unsupported["signature_scheme"] = "claimed-signature"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "minisign-ed25519"):
                record_external_review(self.connection, unsupported)

        unverified = self._review(self.release_id, "review-unverified", "release")
        with _local_actor_binding("reviewer-1"):
            record_external_review(self.connection, unverified)
        row = self.connection.execute(
            "SELECT * FROM external_review_records WHERE id = 'review-unverified'"
        ).fetchone()
        with _local_actor_binding("verifier-authorizer"):
            with self.assertRaisesRegex(
                GovernanceError, "cannot hold a review or Kingy authorizer role"
            ):
                record_signature_verification_claim(
                    self.connection,
                    {
                        "id": "authorizer-signature-verification",
                        "review_record_id": "review-unverified",
                        "verifier_principal_id": "verifier-authorizer",
                        "verifier_assignment_id": "verifier-registrar-role",
                        "signature_scheme": row["signature_scheme"],
                        "signature_key_id": row["signature_key_id"],
                        "signed_payload_sha256": row["signed_payload_sha256"],
                        "signature_evidence_sha256": row[
                            "signature_evidence_sha256"
                        ],
                        "verification_evidence_sha256": "e" * 64,
                        "verified_at": "2026-07-03T20:04:30Z",
                        "status": "untrusted_local_claim",
                    },
                )
        with _local_actor_binding(REGISTRAR):
            with self.assertRaisesRegex(GovernanceError, "does not match review"):
                record_signature_verification_claim(
                    self.connection,
                    {
                        "id": "wrong-signature-verification",
                        "review_record_id": "review-unverified",
                        "verifier_principal_id": REGISTRAR,
                        "verifier_assignment_id": "kapi-identity-registrar:reviewer-role",
                        "signature_scheme": row["signature_scheme"],
                        "signature_key_id": "wrong-key",
                        "signed_payload_sha256": row["signed_payload_sha256"],
                        "signature_evidence_sha256": row["signature_evidence_sha256"],
                        "verification_evidence_sha256": "f" * 64,
                        "verified_at": "2026-07-03T20:04:30Z",
                        "status": "untrusted_local_claim",
                    },
                )
        with self.assertRaisesRegex(GovernanceError, "edge or role"):
            self._accept_release_review(
                self.release_id, "review-unverified", "2026-07-03T20:05:00Z"
            )

    def test_expired_release_review_cannot_be_accepted(self) -> None:
        review = self._review(self.release_id, "review-expiring", "release")
        review["valid_until"] = "2026-07-03T20:10:00Z"
        self._record_and_verify(review)
        with self.assertRaisesRegex(GovernanceError, "edge or role"):
            self._accept_release_review(
                self.release_id, "review-expiring", "2026-07-03T20:11:00Z"
            )

    def test_reviewer_registry_lifecycle_is_append_only_and_auditable(self) -> None:
        initial_review = self._review(
            self.release_id, "review-before-key-rotation", "methodology"
        )
        self._record_and_verify(initial_review)

        with _local_actor_binding(REGISTRAR):
            supersede_external_reviewer(
                self.connection,
                {
                    "id": "reviewer-1:registry:2",
                    "principal_id": "reviewer-1",
                    "effective_at": "2026-07-03T20:05:00Z",
                    "recorded_at": "2026-07-03T20:05:00Z",
                    "valid_until": "2026-07-03T20:07:30Z",
                    "signature_key_id": "reviewer-1-key-2026-rotated",
                    "public_key_sha256": "1" * 64,
                    "event_evidence_sha256": "2" * 64,
                    "reason": "Scheduled reviewer key rotation.",
                },
            )

        initial_event = self.connection.execute(
            "SELECT * FROM external_reviewer_registry_events "
            "WHERE id = 'reviewer-1:registry:1'"
        ).fetchone()
        rotated_event = self.connection.execute(
            "SELECT * FROM external_reviewer_registry_events "
            "WHERE id = 'reviewer-1:registry:2'"
        ).fetchone()
        self.assertEqual(initial_event["signature_key_id"], "reviewer-1-key-2026")
        self.assertEqual(rotated_event["sequence"], 2)
        self.assertEqual(rotated_event["supersedes_event_id"], initial_event["id"])

        stale_key_review = self._review(
            self.release_id, "review-with-stale-key", "methodology"
        )
        stale_key_review["reviewed_at"] = "2026-07-03T20:06:00Z"
        stale_key_review["recorded_at"] = "2026-07-03T20:07:00Z"
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "latest active credential"):
                record_external_review(self.connection, stale_key_review)

        rotated_key_review = self._review(
            self.release_id, "review-with-rotated-key", "methodology"
        )
        rotated_key_review.update(
            {
                "signature_key_id": "reviewer-1-key-2026-rotated",
                "reviewed_at": "2026-07-03T20:06:00Z",
                "recorded_at": "2026-07-03T20:07:00Z",
            }
        )
        with _local_actor_binding("reviewer-1"):
            record_external_review(self.connection, rotated_key_review)
        rotated_review_row = self.connection.execute(
            "SELECT * FROM external_review_records WHERE id = ?",
            (rotated_key_review["id"],),
        ).fetchone()
        self.assertEqual(
            rotated_review_row["reviewer_registry_event_id"],
            "reviewer-1:registry:2",
        )

        with _local_actor_binding(REGISTRAR):
            revoke_external_reviewer(
                self.connection,
                {
                    "id": "reviewer-1:registry:3",
                    "principal_id": "reviewer-1",
                    "effective_at": "2026-07-03T20:08:00Z",
                    "recorded_at": "2026-07-03T20:08:00Z",
                    "event_evidence_sha256": "3" * 64,
                    "reason": "Reviewer credential revoked after key compromise report.",
                },
            )

        with _local_actor_binding(REGISTRAR):
            with self.assertRaisesRegex(GovernanceError, "does not match review"):
                record_signature_verification_claim(
                    self.connection,
                    {
                        "id": "verification-after-revocation",
                        "review_record_id": rotated_key_review["id"],
                        "verifier_principal_id": REGISTRAR,
                        "verifier_assignment_id": "kapi-identity-registrar:reviewer-role",
                        "signature_scheme": rotated_review_row["signature_scheme"],
                        "signature_key_id": rotated_review_row["signature_key_id"],
                        "signed_payload_sha256": rotated_review_row[
                            "signed_payload_sha256"
                        ],
                        "signature_evidence_sha256": rotated_review_row[
                            "signature_evidence_sha256"
                        ],
                        "verification_evidence_sha256": "4" * 64,
                        "verified_at": "2026-07-03T20:09:00Z",
                        "status": "untrusted_local_claim",
                    },
                )

        after_revocation = self._review(
            self.release_id, "review-after-revocation", "methodology"
        )
        after_revocation.update(
            {
                "signature_key_id": "reviewer-1-key-2026-rotated",
                "reviewed_at": "2026-07-03T20:09:00Z",
                "recorded_at": "2026-07-03T20:10:00Z",
            }
        )
        with _local_actor_binding("reviewer-1"):
            with self.assertRaisesRegex(GovernanceError, "latest active credential"):
                record_external_review(self.connection, after_revocation)

        attribution = governance_review_attribution(
            self.connection, initial_review["id"]
        )
        self.assertEqual(
            attribution["reviewer"]["registry"]["bound_event"]["event_id"],
            "reviewer-1:registry:1",
        )
        self.assertFalse(attribution["reviewer"]["registry"]["bound_event_is_latest"])
        revocation = attribution["reviewer"]["registry"]["revocation"]
        self.assertEqual(revocation["event_id"], "reviewer-1:registry:3")
        self.assertEqual(revocation["status"], "revoked")
        self.assertIn("qualifications_summary", revocation)
        self.assertIn("identity_evidence_sha256", attribution["reviewer"])
        self.assertIn("appointment_sha256", attribution["reviewer"]["appointment"])
        self.assertIn("statement", attribution["disclosures"]["conflict"]["declaration"])
        self.assertIn("findings", attribution["decision"])
        self.assertEqual(
            attribution["signature_verification_attestation"]["status"],
            "untrusted_local_claim",
        )

        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute(
                "UPDATE external_reviewer_registry_events SET reason = 'changed' "
                "WHERE id = 'reviewer-1:registry:1'"
            )
        with self.assertRaisesRegex(sqlite3.IntegrityError, "append-only"):
            self.connection.execute(
                "DELETE FROM external_reviewer_registry_events "
                "WHERE id = 'reviewer-1:registry:1'"
            )

    def test_future_ready_paths_bind_code_and_current_reviewer_event(self) -> None:
        schema = (ROOT / "kapi/schema/002_governance.sql").read_text(
            encoding="utf-8"
        )
        exact_ready_branch = schema.split(
            "NEW.from_governance_state = 'external_release_reviewed'", 1
        )[1].split("NEW.governance_state = 'withdrawn'", 1)[0]
        self.assertIn(
            "binding.code_commit = gate.implementation_commit", exact_ready_branch
        )
        self.assertIn(
            "registry.id = methodology_review.reviewer_registry_event_id",
            exact_ready_branch,
        )
        self.assertIn(
            "registry.id = release_review.reviewer_registry_event_id",
            exact_ready_branch,
        )
        self.assertGreaterEqual(
            schema.count("SELECT MAX(latest.sequence)"),
            6,
            "review creation, signature claims, and every future transition must use latest state",
        )

    def test_staged_hybrid_is_modeled_but_current_vintage_fails_closed(self) -> None:
        method_review = self._review(self.release_id, "method-review-main", "methodology")
        self._record_and_verify(method_review)
        with self.assertRaisesRegex(GovernanceError, "current governance state"):
            self._authorize(self.release_id, method_review["id"], "routine")
        routine_status = governance_status(self.connection, self.release_id)
        self.assertEqual(routine_status["review_label"], CURRENT_UNREVIEWED_LABEL)
        self.assertEqual(routine_status["governance_state"], "unreviewed")
        self.assertEqual(routine_status["publication_state"], "not_authorized")
        self.assertFalse(routine_status["publication_eligible"])

        exact_release_id = self._append_release(self.method, "exact")
        release_review = self._review(exact_release_id, "release-review-exact", "release")
        self._record_and_verify(release_review)
        with self.assertRaisesRegex(GovernanceError, "edge or role"):
            self._accept_release_review(
                exact_release_id, release_review["id"], "2026-07-03T20:05:00Z"
            )
        exact_status = governance_status(self.connection, exact_release_id)
        self.assertEqual(exact_status["review_label"], CURRENT_UNREVIEWED_LABEL)
        self.assertNotEqual(exact_status["review_label"], EXTERNAL_RELEASE_REVIEW_LABEL)
        self.assertFalse(exact_status["publication_eligible"])

        for suffix, technical, operational in (
            ("technical-fail", "failed", "passed"),
            ("operational-fail", "passed", "failed"),
        ):
            method = self._register_method(
                suffix, technical=technical, operational=operational
            )
            release_id = self._append_release(method, suffix)
            review = self._review(release_id, f"method-review-{suffix}", "methodology")
            self._record_and_verify(review)
            with self.assertRaisesRegex(GovernanceError, "current governance state"):
                self._authorize(release_id, review["id"], suffix)
            self.assertFalse(governance_status(self.connection, release_id)["publication_eligible"])

        gates = self.connection.execute(
            "SELECT technical_gate, operational_gate, trusted_verifier_gate "
            "FROM methodology_governance_gates WHERE methodology_version = ?",
            (self.method["version"],),
        ).fetchone()
        self.assertEqual(tuple(gates), ("passed", "passed", "failed"))

    def test_direct_final_and_direct_transition_are_blocked(self) -> None:
        with self.assertRaisesRegex(sqlite3.IntegrityError, "direct final"):
            self.connection.execute(
                "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "release-direct-final",
                    "calculation-main",
                    self.bundle["weeks"][-1]["id"],
                    "direct-final",
                    "final",
                    None,
                    "/direct-final",
                    None,
                ),
            )

        binding = self.connection.execute(
            "SELECT * FROM release_governance_bindings WHERE release_id = ?",
            (self.release_id,),
        ).fetchone()
        with self.assertRaisesRegex(sqlite3.IntegrityError, "actor binding missing"):
            self.connection.execute(
                "INSERT INTO governance_transition_events("
                "id, release_id, sequence, from_governance_state, from_publication_state, "
                "governance_state, publication_state, calculation_disposition, review_label, "
                "actor_principal_id, actor_assignment_id, artifact_manifest_sha256, "
                "methodology_review_record_id, release_review_record_id, transitioned_at, reason"
                ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                (
                    "direct-transition",
                    self.release_id,
                    2,
                    "operator_reviewed",
                    "not_authorized",
                    "operator_reviewed",
                    "ready",
                    binding["calculation_disposition"],
                    METHODOLOGY_REVIEWED_OPERATOR_LABEL,
                    "owner-1",
                    "publication-role",
                    binding["artifact_manifest_sha256"],
                    None,
                    None,
                    "2026-07-03T20:05:00Z",
                    "Unauthorized direct SQL transition.",
                ),
            )

        with _local_actor_binding("operator-1"):
            with self.assertRaisesRegex(
                sqlite3.IntegrityError, "edge or role"
            ):
                self.connection.execute(
                    "INSERT INTO governance_transition_events("
                    "id, release_id, sequence, from_governance_state, "
                    "from_publication_state, governance_state, publication_state, "
                    "calculation_disposition, review_label, actor_principal_id, "
                    "actor_assignment_id, artifact_manifest_sha256, "
                    "methodology_review_record_id, release_review_record_id, "
                    "transitioned_at, reason"
                    ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    (
                        "direct-context-operator-promotion",
                        self.release_id,
                        2,
                        "unreviewed",
                        "not_authorized",
                        "operator_reviewed",
                        "not_authorized",
                        binding["calculation_disposition"],
                        CURRENT_OPERATOR_REVIEW_LABEL,
                        "operator-1",
                        "operator-role",
                        binding["artifact_manifest_sha256"],
                        None,
                        None,
                        "2026-07-03T20:05:00Z",
                        "Caller-controlled local context cannot prove review.",
                    ),
                )

        with _local_actor_binding("owner-1"):
            with self.assertRaisesRegex(GovernanceError, "predecessor mismatch"):
                append_governance_transition(
                    self.connection,
                    {
                        "id": "backdated-withdrawal",
                        "release_id": self.release_id,
                        "action": "withdraw",
                        "actor_principal_id": "owner-1",
                        "actor_assignment_id": "release-role",
                        "transitioned_at": "2026-07-03T19:59:59Z",
                        "reason": "Backdating must fail.",
                    },
                )
            with self.assertRaisesRegex(
                GovernanceError, "does not implement a published transition"
            ):
                append_governance_transition(
                    self.connection,
                    {
                        "id": "unreachable-published",
                        "release_id": self.release_id,
                        "action": "mark_published",
                        "actor_principal_id": "owner-1",
                        "actor_assignment_id": "publication-role",
                        "transitioned_at": "2026-07-03T20:06:00Z",
                        "reason": "Current policy cannot publish.",
                    },
                )

    def test_governed_child_sets_reject_every_late_insert(self) -> None:
        cases = (
            (
                "methodology_base_weeks",
                "INSERT INTO methodology_base_weeks VALUES(?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    self.bundle["weeks"][0]["id"],
                    1,
                ),
            ),
            (
                "methodology_thresholds",
                "INSERT INTO methodology_thresholds VALUES(?, ?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    "ECI",
                    "late",
                    "130",
                ),
            ),
            (
                "task_profiles",
                "INSERT INTO task_profiles(methodology_id, methodology_version, profile_id) "
                "VALUES(?, ?, ?)",
                (self.method["methodology_id"], self.method["version"], "late"),
            ),
            (
                "task_profile_features",
                "INSERT INTO task_profile_features VALUES(?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    "late",
                    "late",
                ),
            ),
            (
                "methodology_sensitivities",
                "INSERT INTO methodology_sensitivities VALUES(?, ?, ?, ?, ?)",
                (
                    self.method["methodology_id"],
                    self.method["version"],
                    "late",
                    "late",
                    "{}",
                ),
            ),
            (
                "snapshot_inputs",
                "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
                ("snapshot-main", "other", "late", "1" * 64),
            ),
            (
                "calculation_profile_results",
                "INSERT INTO calculation_profile_results(calculation_id, profile_id) "
                "VALUES(?, ?)",
                ("calculation-main", "late"),
            ),
            (
                "calculation_selected_endpoints",
                "INSERT INTO calculation_selected_endpoints(calculation_id, profile_id, endpoint_id) "
                "VALUES(?, ?, ?)",
                ("calculation-main", "late", self.bundle["endpoints"][0]["id"]),
            ),
            (
                "calculation_validations",
                "INSERT INTO calculation_validations(calculation_id, check_name) "
                "VALUES(?, ?)",
                ("calculation-main", "late"),
            ),
            (
                "release_artifacts",
                "INSERT INTO release_artifacts VALUES(?, ?, ?, ?)",
                (self.release_id, "late.json", "application/json", "2" * 64),
            ),
            (
                "release_signoffs",
                "INSERT INTO release_signoffs(release_id, role) VALUES(?, ?)",
                (self.release_id, "late"),
            ),
            (
                "correction_releases",
                "INSERT INTO correction_releases VALUES(?, ?, ?)",
                ("late-correction", self.release_id, "affected"),
            ),
        )
        for table, sql, values in cases:
            with self.subTest(table=table):
                with self.assertRaisesRegex(sqlite3.IntegrityError, "frozen"):
                    self.connection.execute(sql, values)

    def test_historical_release_vintages_remain_byte_immutable(self) -> None:
        self.assertEqual(
            hashlib.sha256((ROOT / "kapi/independent.py").read_bytes()).hexdigest(),
            "3d196439c125e0ecdfbf8c924e59ddbd216de84b572ff06b81f644c7b11b1282",
        )
        expected_manifest_hashes = {
            "sample-release": "c2784936b6e092d9645cdf28289982a2ec0bc83f561b501ea663f4c4ea06d661",
            "sample-release-v0.2.1": "b55cd3e9bafbed058b67cd6dd9f380404636bf7a6772a00e1b5234238c1f4018",
            "sample-release-v0.2.2": "924a003eda77401b896c34110c0ceee6d6e9608d952ca07134f4694c39e08953",
        }
        for directory_name, expected_hash in expected_manifest_hashes.items():
            directory = ROOT / "kapi/outputs" / directory_name
            manifest_path = directory / "provenance-manifest.json"
            self.assertEqual(hashlib.sha256(manifest_path.read_bytes()).hexdigest(), expected_hash)
            manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
            for entry in manifest["files"]:
                with self.subTest(vintage=directory_name, path=entry["path"]):
                    self.assertEqual(
                        hashlib.sha256((directory / entry["path"]).read_bytes()).hexdigest(),
                        entry["sha256"],
                    )


if __name__ == "__main__":
    unittest.main()
