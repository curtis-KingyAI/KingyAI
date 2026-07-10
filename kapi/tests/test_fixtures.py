"""Tests for the frozen KAPI methodology and synthetic hand-example fixture."""

from __future__ import annotations

import hashlib
import importlib.util
import json
import subprocess
import sys
import unittest
from collections import Counter
from decimal import Decimal
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
CONFIG_PATH = REPO_ROOT / "kapi/config/methodology-v0.1.0.json"
GENERATOR_PATH = REPO_ROOT / "kapi/fixtures/build_synthetic.py"
FIXTURE_PATH = REPO_ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
EXPECTED_CONFIG_SHA256 = (
    "6d2d4b26c1a1c6413a974b361fed9ee700173f0b6f9a923e70b714826df288e8"
)
EXPECTED_FIXTURE_SHA256 = (
    "f940b7ebc75064f9169e370626f8f655acaf6482923d9c05f4a83483c63d06e3"
)
HEADLINE_COUNTS = {
    "analysis-reasoning": (4000, 500),
    "code-repair": (12000, 3000),
    "grounded-rag": (10000, 800),
    "structured-extraction": (2000, 200),
    "summarization-transformation": (25000, 1500),
    "tool-workflow": (30000, 6000),
}
BASE_PRICES = {
    "endpoint-a-v1": {"input": "2", "output": "8"},
    "endpoint-b-v1": {"input": "1", "output": "12"},
    "endpoint-c-v1": {"input": "3", "output": "6"},
    "endpoint-d-v1": {"input": "0.8", "output": "20"},
}
CURRENT_PRICES = {
    "endpoint-a-v1": {"input": "1", "output": "5"},
    "endpoint-b-v1": {"input": "0.5", "output": "6"},
    "endpoint-c-v1": {"input": "2.5", "output": "5"},
    "endpoint-d-v1": {"input": "0.4", "output": "10"},
}


def canonical_bytes(value: Any) -> bytes:
    return (
        json.dumps(
            value,
            ensure_ascii=False,
            separators=(",", ":"),
            sort_keys=True,
        )
        + "\n"
    ).encode("utf-8")


def sha256_bytes(value: bytes) -> str:
    return hashlib.sha256(value).hexdigest()


def load_canonical(path: Path) -> tuple[bytes, dict[str, Any]]:
    raw = path.read_bytes()
    value = json.loads(raw)
    if raw != canonical_bytes(value):
        raise AssertionError(f"{path} is not canonical JSON")
    return raw, value


def load_generator():
    spec = importlib.util.spec_from_file_location(
        "kapi_synthetic_generator", GENERATOR_PATH
    )
    if spec is None or spec.loader is None:
        raise RuntimeError("unable to load synthetic fixture generator")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class MethodologyFixtureTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.config_bytes, cls.config = load_canonical(CONFIG_PATH)
        cls.fixture_bytes, cls.fixture = load_canonical(FIXTURE_PATH)
        cls.generator = load_generator()

    def test_versioned_config_and_fixture_hashes_are_frozen(self) -> None:
        self.assertEqual(
            sha256_bytes(self.config_bytes), EXPECTED_CONFIG_SHA256
        )
        self.assertEqual(
            sha256_bytes(self.fixture_bytes), EXPECTED_FIXTURE_SHA256
        )
        self.assertEqual(
            self.fixture["methodology"]["config_sha256"],
            EXPECTED_CONFIG_SHA256,
        )
        self.assertEqual(self.config["version"], "0.1.0")
        self.assertEqual(
            self.fixture["methodology"]["version"], self.config["version"]
        )

    def test_methodology_encodes_approved_policy(self) -> None:
        self.assertEqual(
            self.config["capability"],
            {
                "headline_threshold": "130",
                "metric": "ECI",
                "metric_version_policy": "pinned_per_release",
                "sensitivity_thresholds": ["125", "135"],
            },
        )
        self.assertEqual(self.config["evidence_policy"]["official_grades"], ["A"])
        self.assertEqual(
            self.config["evidence_policy"]["research_grades"], ["A", "B", "C"]
        )
        self.assertEqual(
            self.config["evidence_policy"]["excluded_grades"], ["D"]
        )
        self.assertEqual(self.config["base_period_weeks"], 13)
        self.assertEqual(
            self.config["selection"]["tie_break"],
            [
                "lowest full-precision median cost",
                "lowest full-precision triple total cost",
                "lexicographically sorted endpoint IDs",
            ],
        )
        self.assertEqual(self.config["selection"]["provider_count"], 3)
        self.assertEqual(self.config["selection"]["creator_count"], 3)
        self.assertEqual(self.config["concentration"]["warning_share"], "0.35")
        self.assertEqual(self.config["concentration"]["withhold_share"], "0.50")
        self.assertEqual(
            self.config["concentration"]["warning_profile_count"], 3
        )
        self.assertEqual(
            self.config["concentration"]["withhold_profile_count"], 4
        )
        self.assertEqual(
            self.config["corrections"]["finalization"]["timing"], "T+4"
        )
        self.assertEqual(
            self.config["corrections"]["provisional_release_cycles"], 4
        )
        self.assertFalse(
            self.config["corrections"]["silent_overwrite_allowed"]
        )
        self.assertFalse(self.config["expense_controls"]["network_allowed"])
        self.assertFalse(self.config["expense_controls"]["model_calls_allowed"])
        self.assertEqual(
            self.config["expense_controls"]["total_external_spend_usd"], "0"
        )

    def test_reference_tokenizer_is_explicitly_unverified(self) -> None:
        self.assertEqual(
            self.config["reference_tokenizer"],
            {
                "asset_sha256": None,
                "id": "o200k_base",
                "verification_status": "synthetic_targets_unverified",
            },
        )
        self.assertFalse(
            self.config["construction_reference"]["counts_verified"]
        )
        self.assertFalse(
            self.config["construction_reference"][
                "implementation_available_in_prototype"
            ]
        )
        self.assertFalse(
            self.config["endpoint_specific_billing_counts"][
                "construction_reference_may_substitute_for_billing_counts"
            ]
        )

    def test_profile_payload_hashes_and_design_targets(self) -> None:
        self.assertEqual(len(self.config["profiles"]), 6)
        total_count = 0
        rational_numerator = 0
        for profile in self.config["profiles"]:
            profile_id = profile["id"]
            self.assertEqual(profile["count"], 10)
            total_count += profile["count"]
            self.assertEqual(
                profile["weight"], {"denominator": 6, "numerator": 1}
            )
            rational_numerator += profile["weight"]["numerator"]
            expected_input, expected_output = HEADLINE_COUNTS[profile_id]
            self.assertEqual(profile["input_target_tokens"], expected_input)
            self.assertEqual(profile["output_target_tokens"], expected_output)
            for direction in ("input", "output"):
                payloads = profile["payloads"][direction]
                self.assertEqual(len(payloads), 3)
                self.assertEqual(
                    {payload["size_factor"] for payload in payloads},
                    {"0.75", "1.00", "1.25"},
                )
                for payload in payloads:
                    path = REPO_ROOT / payload["path"]
                    raw, document = load_canonical(path)
                    self.assertEqual(sha256_bytes(raw), payload["sha256"])
                    self.assertEqual(document["dataset_kind"], "synthetic")
                    self.assertEqual(document["model_calls_performed"], 0)
                    self.assertFalse(document["network_access_used"])
                    self.assertFalse(document["o200k_base_count_verified"])
                    self.assertEqual(
                        document["token_count_status"],
                        (
                            "synthetic_design_target_only_not_verified_by_"
                            "o200k_base"
                        ),
                    )
                    self.assertEqual(
                        document["reference_token_design_target"],
                        payload["reference_token_design_target"],
                    )
            self.assertEqual(
                profile["input_payload_path"],
                f"kapi/profiles/{profile_id}/input-100.json",
            )
            self.assertEqual(
                profile["output_payload_path"],
                f"kapi/profiles/{profile_id}/output-100.json",
            )
        self.assertEqual(total_count, 60)
        self.assertEqual(rational_numerator, 6)

    def test_editorial_and_full_three_by_three_sensitivities(self) -> None:
        editorial = self.config["sensitivities"]["editorial_weights"]
        self.assertEqual(editorial["total_count"], 100)
        self.assertEqual(
            editorial["profile_counts"],
            {
                "analysis-reasoning": 20,
                "code-repair": 15,
                "grounded-rag": 20,
                "structured-extraction": 20,
                "summarization-transformation": 15,
                "tool-workflow": 10,
            },
        )
        grid = self.config["sensitivities"]["payload_size_grid"]
        self.assertEqual(len(grid), 9)
        expected = {
            (input_factor, output_factor)
            for input_factor in ("0.75", "1.00", "1.25")
            for output_factor in ("0.75", "1.00", "1.25")
        }
        self.assertEqual(
            {(cell["input_factor"], cell["output_factor"]) for cell in grid},
            expected,
        )
        self.assertEqual(
            [cell["id"] for cell in grid if cell["headline"]], ["100x100"]
        )

    def test_generator_recreates_committed_fixture_byte_for_byte(self) -> None:
        first = self.generator.build_bytes()
        second = self.generator.build_bytes()
        self.assertEqual(first, second)
        self.assertEqual(first, self.fixture_bytes)
        completed = subprocess.run(
            [sys.executable, str(GENERATOR_PATH), "--check"],
            cwd=REPO_ROOT,
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(
            completed.returncode,
            0,
            msg=f"stdout={completed.stdout}\nstderr={completed.stderr}",
        )

    def test_bundle_identity_and_offline_generation_metadata(self) -> None:
        self.assertEqual(self.fixture["dataset_kind"], "synthetic")
        self.assertEqual(self.fixture["dataset_id"], "synthetic-hand-example-v1")
        self.assertEqual(len(self.fixture["weeks"]), 14)
        self.assertEqual(len(self.fixture["base_period_week_ids"]), 13)
        self.assertEqual(self.fixture["current_week_id"], "week-2026-07-03")
        self.assertEqual(len(self.fixture["providers"]), 4)
        self.assertEqual(len(self.fixture["creators"]), 4)
        self.assertEqual(len(self.fixture["models"]), 4)
        self.assertEqual(len(self.fixture["endpoints"]), 4)
        self.assertEqual(
            self.fixture["generation"],
            {
                "external_dependencies": [],
                "generator_path": "kapi/fixtures/build_synthetic.py",
                "model_calls_performed": 0,
                "network_access_used": False,
                "paid_calls_performed": 0,
                "total_external_spend_usd": "0",
            },
        )
        self.assertEqual(
            {endpoint["provider_id"] for endpoint in self.fixture["endpoints"]},
            {f"provider-{letter}" for letter in "abcd"},
        )
        self.assertEqual(
            {model["creator_id"] for model in self.fixture["models"]},
            {f"creator-{letter}" for letter in "abcd"},
        )
        for endpoint in self.fixture["endpoints"]:
            for flag in (
                "available_us",
                "ga",
                "on_demand",
                "public",
                "standard_list_price",
                "synchronous",
                "tokenizer_reproducible",
            ):
                self.assertIs(endpoint[flag], True)
            self.assertEqual(endpoint["reasoning_mode"], "disabled")
            self.assertEqual(
                endpoint["billing_tokenizer"],
                "synthetic-declared-counts-v1",
            )

    def test_grade_a_synthetic_sources_and_hashes(self) -> None:
        self.assertEqual(len(self.fixture["source_artifacts"]), 60)
        for source in self.fixture["source_artifacts"]:
            self.assertEqual(source["evidence_grade"], "A")
            self.assertTrue(source["url"].startswith("synthetic://"))
            self.assertEqual(
                source["content_sha256"],
                sha256_bytes(canonical_bytes(source["synthetic_content"])),
            )
            self.assertIn("not a real", source["license_note"])
        self.assertEqual(len(self.fixture["capability_evidence"]), 4)
        for record in self.fixture["capability_evidence"]:
            self.assertEqual(record["evidence_grade"], "A")
            self.assertEqual(record["metric"], "ECI")
            self.assertGreaterEqual(
                Decimal(record["score"]),
                Decimal(self.config["capability"]["headline_threshold"]),
            )

    def test_endpoint_specific_counts_cover_every_grid_cell(self) -> None:
        token_counts = self.fixture["token_counts"]
        self.assertEqual(len(token_counts), 4 * 6 * 9)
        record_counts = Counter(
            (record["endpoint_id"], record["profile_id"])
            for record in token_counts
        )
        self.assertEqual(set(record_counts.values()), {9})
        headline = [
            record
            for record in token_counts
            if record["size_variant"] == "100x100"
        ]
        self.assertEqual(len(headline), 4 * 6)
        for record in headline:
            expected_input, expected_output = HEADLINE_COUNTS[
                record["profile_id"]
            ]
            self.assertEqual(record["input_tokens"], expected_input)
            self.assertEqual(record["output_tokens"], expected_output)
            self.assertEqual(
                record["billing_tokenizer"],
                "synthetic-declared-counts-v1",
            )
            self.assertIn("not a verified o200k_base", record["synthetic_count_note"])

    def test_thirteen_identical_base_fridays_and_current_prices(self) -> None:
        weeks = self.fixture["weeks"]
        self.assertEqual(
            [week["id"] for week in weeks[:13]],
            self.fixture["base_period_week_ids"],
        )
        self.assertEqual(weeks[-1]["id"], self.fixture["current_week_id"])
        self.assertTrue(
            all(week["cutoff_at"].endswith("T16:00:00Z") for week in weeks)
        )
        observations = self.fixture["price_observations"]
        self.assertEqual(len(observations), 14 * 4 * 2)
        by_key = {
            (
                record["week_id"],
                record["endpoint_id"],
                record["component"],
            ): record
            for record in observations
        }
        for week in weeks:
            price_map = (
                CURRENT_PRICES
                if week["id"] == self.fixture["current_week_id"]
                else BASE_PRICES
            )
            for endpoint_id, components in price_map.items():
                for component, amount in components.items():
                    record = by_key[(week["id"], endpoint_id, component)]
                    self.assertEqual(record["amount_per_million"], amount)
                    self.assertEqual(record["evidence_grade"], "A")
                    self.assertEqual(record["currency"], "USD")
                    self.assertEqual(
                        record["unit"], "per_million_native_tokens"
                    )

    def test_hand_example_values_and_expected_withhold(self) -> None:
        expected = self.fixture["expected_result"]
        diagnostic = expected["diagnostic_hand_calculation"]
        self.assertEqual(
            diagnostic["base_sixty_profile_basket_cost"], "2.476"
        )
        self.assertEqual(
            diagnostic["current_sixty_profile_basket_cost"], "1.333"
        )
        self.assertEqual(
            diagnostic["current_index_full_precision"],
            "53.8368336025848142164781906300",
        )
        self.assertEqual(diagnostic["current_index_display"], "53.8")
        base_prices = {
            profile_id: result["selected_price"]
            for profile_id, result in diagnostic[
                "base_profile_results"
            ].items()
        }
        current_prices = {
            profile_id: result["selected_price"]
            for profile_id, result in diagnostic[
                "current_profile_results"
            ].items()
        }
        self.assertEqual(
            base_prices,
            {
                "analysis-reasoning": "0.012",
                "code-repair": "0.048",
                "grounded-rag": "0.024",
                "structured-extraction": "0.0056",
                "summarization-transformation": "0.05",
                "tool-workflow": "0.108",
            },
        )
        self.assertEqual(
            current_prices,
            {
                "analysis-reasoning": "0.0065",
                "code-repair": "0.027",
                "grounded-rag": "0.012",
                "structured-extraction": "0.0028",
                "summarization-transformation": "0.025",
                "tool-workflow": "0.06",
            },
        )
        strict = expected["strict_headline"]
        self.assertEqual(strict["status"], "withheld_concentration")
        self.assertFalse(strict["base_period_usable"])
        self.assertTrue(strict["all_weeks_withheld"])
        self.assertTrue(expected["concentration"]["base"]["withhold"])
        self.assertTrue(expected["concentration"]["current"]["withhold"])


if __name__ == "__main__":
    unittest.main()
