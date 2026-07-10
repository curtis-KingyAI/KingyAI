from __future__ import annotations

import copy
import json
import sqlite3
import tempfile
import unittest
from pathlib import Path

from kapi.calculation import calculate_index
from kapi.drills import detect_price_unit_jumps
from kapi.independent import check_calculation
from kapi.lifecycle import LifecycleError, append_weekly_vintage, register_methodology
from kapi.store import ingest_bundle, init_database


ROOT = Path(__file__).resolve().parents[2]
BUNDLE_PATH = ROOT / "kapi/outputs/sample-release/inputs/dataset.json"
METHOD_PATH = ROOT / "kapi/config/methodology-v0.2.2.json"
CALCULATION_PATH = ROOT / "kapi/outputs/sample-release/calculation.json"


def load(path: Path):
    return json.loads(path.read_text(encoding="utf-8"))


class WeekZeroControlTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.bundle = load(BUNDLE_PATH)
        cls.method = load(METHOD_PATH)
        cls.calculation = load(CALCULATION_PATH)

    def test_observed_artifacts_are_truthfully_labeled_shadow(self) -> None:
        bundle = copy.deepcopy(self.bundle)
        bundle["dataset_kind"] = "observed"
        result = calculate_index(bundle, self.method)
        self.assertEqual(
            result["notice"],
            "UNPUBLISHED KAPI SHADOW — NOT AN OFFICIAL OR PUBLIC INDEX",
        )
        self.assertFalse(result["citation"]["permitted"])
        self.assertTrue(result["not_for_publication"])

    def test_policy_withheld_weeks_can_be_noncounting_for_base(self) -> None:
        method = copy.deepcopy(self.method)
        method["base_eligibility"] = {
            "noncounting_release_statuses": ["withheld_concentration"]
        }
        result = calculate_index(self.bundle, method)
        self.assertEqual(result["status"], "pending_base")
        self.assertEqual(result["base_period"]["week_ids"], [])
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_task_out"]),
            sorted(profile["id"] for profile in self.method["profiles"]),
        )
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_provider_out"]),
            sorted(provider["id"] for provider in self.bundle["providers"]),
        )
        self.assertEqual(
            sorted(result["sensitivities"]["leave_one_creator_out"]),
            sorted(creator["id"] for creator in self.bundle["creators"]),
        )
        self.assertTrue(
            result["sensitivities"]["first_party_only"]["structural_fragility"]
        )
        self.assertIn(
            "frozen_endpoint_ids", result["sensitivities"]["constant_universe"]
        )

    def test_independent_implementation_passes_and_detects_tampering(self) -> None:
        report = check_calculation(self.calculation)
        self.assertEqual(report["status"], "pass")
        self.assertTrue(report["independent_of_primary_module"])
        tampered = copy.deepcopy(self.calculation)
        tampered["weeks"][-1]["profiles"][0]["headline_price"] = "999"
        self.assertEqual(check_calculation(tampered)["status"], "fail")

    def test_lifecycle_is_append_only_and_final_release_fails_closed(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            connection = init_database(Path(directory) / "lifecycle.sqlite")
            ingest_bundle(connection, self.bundle)
            register_methodology(
                connection, self.method, effective_from="2026-07-03T20:00:00Z"
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
            }
            summary = append_weekly_vintage(connection, envelope)
            self.assertEqual(summary["calculation_status"], "pending_base")
            with self.assertRaises(LifecycleError):
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
            with self.assertRaisesRegex(LifecycleError, "required signoffs"):
                append_weekly_vintage(connection, final_envelope)
            connection.close()

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
