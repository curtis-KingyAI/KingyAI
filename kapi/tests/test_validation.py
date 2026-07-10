from __future__ import annotations

import copy
import json
import unittest
from pathlib import Path

from kapi.validation import validate_bundle, validate_methodology


ROOT = Path(__file__).resolve().parents[2]
BUNDLE_PATH = ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.2.2.json"


class ValidationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = json.loads(BUNDLE_PATH.read_text(encoding="utf-8"))
        cls.methodology = json.loads(METHOD_PATH.read_text(encoding="utf-8"))

    def test_committed_methodology_and_bundle_are_valid(self) -> None:
        method_report = validate_methodology(
            self.methodology, repository_root=ROOT
        )
        bundle_report = validate_bundle(
            self.bundle, self.methodology, repository_root=ROOT
        )
        self.assertTrue(method_report["valid"], method_report["errors"])
        self.assertTrue(bundle_report["valid"], bundle_report["errors"])
        self.assertEqual(60, method_report["stats"]["basket_count"])
        self.assertEqual(14, bundle_report["stats"]["weeks"])
        self.assertEqual(216, bundle_report["stats"]["token_counts"])

    def test_v022_portability_contract_fails_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["construction_manifest"]["entry_count"] = 11
        method["construction_reference"]["portable_reproduction"][
            "full_source_asset_required"
        ] = True
        method["construction_reference"]["full_source_asset_path_configuration"][
            "repository_default"
        ] = "/workstation/path"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("construction_manifest", report["issue_counts"])
        self.assertIn("construction_portability", report["issue_counts"])
        self.assertIn("construction_source_path", report["issue_counts"])

    def test_changed_payload_bytes_fail_hash_validation(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["input_payload_sha256"] = "0" * 64
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])

    def test_duplicate_identity_and_broken_reference_fail(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["providers"].append(copy.deepcopy(bundle["providers"][0]))
        bundle["models"][0]["creator_id"] = "creator-does-not-exist"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("duplicate_id", report["issue_counts"])
        self.assertIn("unknown_reference", report["issue_counts"])

    def test_unresolved_conflicting_price_observation_fails(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        conflict = copy.deepcopy(bundle["price_observations"][0])
        conflict["id"] += "-conflict"
        conflict["amount_per_million"] = "999"
        bundle["price_observations"].append(conflict)
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("conflicting_observation", report["issue_counts"])

    def test_grade_and_decimal_must_be_valid(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["source_artifacts"][0]["evidence_grade"] = "Z"
        bundle["price_observations"][0]["amount_per_million"] = "NaN"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("evidence_grade", report["issue_counts"])
        self.assertIn("invalid_decimal", report["issue_counts"])

    def test_embedded_source_content_must_match_retained_hash(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["source_artifacts"][0]["synthetic_content"]["score"] = "999"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("source_content_hash", report["issue_counts"])

    def test_capability_and_token_records_must_match_linked_records(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        capability = bundle["capability_evidence"][0]
        capability["model_id"] = bundle["models"][1]["id"]
        capability["configuration_id"] = "different-configuration"
        capability["evidence_grade"] = "B"
        bundle["token_counts"][0]["input_payload_sha256"] = "0" * 64
        bundle["token_counts"][1]["id"] = bundle["token_counts"][0]["id"]
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("capability_endpoint", report["issue_counts"])
        self.assertIn("source_grade", report["issue_counts"])
        self.assertIn("token_payload", report["issue_counts"])
        self.assertIn("duplicate_id", report["issue_counts"])

    def test_v021_evidence_classes_and_candidates_fail_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["evidence_classes"]["billed_usage_counts"]["status"] = "verified"
        method["endpoint_specific_billing_counts"]["verified_billing_rows"] = 1
        method["candidate_configurations"][1]["model_id"] = "gemini-2.5-pro"
        method["readiness_gates"]["technical_go"] = "passed"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("evidence_classes", report["issue_counts"])
        self.assertIn("billing_counts_unverified", report["issue_counts"])
        self.assertIn("candidate_substitution", report["issue_counts"])
        self.assertIn("readiness_gates", report["issue_counts"])

    def test_v021_official_documentation_cannot_promote_runtime_evidence(self) -> None:
        method = copy.deepcopy(self.methodology)
        candidates = {
            row["candidate_id"]: row for row in method["candidate_configurations"]
        }
        openai = candidates["openai-gpt54mini-reasoning-none"]
        openai["official_documentation"]["status"] = (
            "supported_configuration_and_pricing"
        )
        openai["eligibility_status"] = "review_candidate_only"
        anthropic = candidates[
            "anthropic-claude-sonnet-4-6-thinking-omitted"
        ]
        anthropic["priced_configuration"] = {"thinking": "disabled"}
        candidates["google-gemini25flash-thinking-budget-0"][
            "provider_preflight_status"
        ] = "verified"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("candidate_official_evidence", report["issue_counts"])
        self.assertIn("candidate_runtime_evidence", report["issue_counts"])

    def test_v021_evidence_record_hash_is_frozen(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["official_provider_evidence"]["record_sha256"] = "0" * 64
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])

    def test_v020_payload_and_token_count_evidence_fail_closed(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["payloads"]["input"][0][
            "construction_token_count"
        ] += 1
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_construction_count", report["issue_counts"])

        bundle = copy.deepcopy(self.bundle)
        bundle["token_counts"][0]["billing_usage_count_status"] = "verified"
        bundle["token_counts"][0]["construction_count_evidence_class"] = "billing"
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("billing_counts_unverified", report["issue_counts"])
        self.assertIn("evidence_class_separation", report["issue_counts"])

    def test_alternate_payloads_and_grid_ids_are_verified(self) -> None:
        method = copy.deepcopy(self.methodology)
        method["profiles"][0]["payloads"]["input"][0]["sha256"] = "0" * 64
        method["sensitivities"]["payload_size_grid"][0]["id"] = "wrong-grid-id"
        report = validate_methodology(method, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("payload_hash", report["issue_counts"])
        self.assertIn("payload_grid", report["issue_counts"])

    def test_binary_floats_and_mixed_dataset_kind_are_rejected(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["dataset_kind"] = "mixed"
        bundle["price_observations"][0]["amount_per_million"] = 2.5
        bundle["capability_evidence"][0]["score"] = 150.0
        report = validate_bundle(bundle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("dataset_kind", report["issue_counts"])
        self.assertGreaterEqual(report["issue_counts"].get("invalid_decimal", 0), 2)

    def test_supersession_cycles_and_invalid_correction_links_fail(self) -> None:
        price_cycle = copy.deepcopy(self.bundle)
        original = price_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = f"{original['id']}-replacement"
        original["supersedes_observation_id"] = replacement["id"]
        replacement["supersedes_observation_id"] = original["id"]
        price_cycle["price_observations"].append(replacement)
        report = validate_bundle(price_cycle, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("supersession_cycle", report["issue_counts"])

        correction_cycle = copy.deepcopy(self.bundle)
        original = correction_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = f"{original['id']}-replacement"
        replacement["supersedes_observation_id"] = original["id"]
        correction_cycle["price_observations"].append(replacement)
        correction_cycle["corrections"] = [
            {
                "id": "correction-1",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-2",
            },
            {
                "id": "correction-2",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-1",
            },
        ]
        report = validate_bundle(
            correction_cycle, self.methodology, repository_root=ROOT
        )
        self.assertFalse(report["valid"])
        self.assertIn("supersession_cycle", report["issue_counts"])

        invalid_link = copy.deepcopy(correction_cycle)
        invalid_link["corrections"] = [invalid_link["corrections"][0]]
        invalid_link["corrections"][0].pop("supersedes_correction_id")
        invalid_link["price_observations"][-1]["effective_at"] = (
            "2026-04-03T00:00:01Z"
        )
        report = validate_bundle(invalid_link, self.methodology, repository_root=ROOT)
        self.assertFalse(report["valid"])
        self.assertIn("correction_linkage", report["issue_counts"])


if __name__ == "__main__":
    unittest.main()
