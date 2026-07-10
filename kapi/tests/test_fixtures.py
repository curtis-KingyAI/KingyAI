"""Tests for the frozen KAPI methodology and synthetic hand-example fixture."""

from __future__ import annotations

import hashlib
import importlib.util
import json
import os
import subprocess
import sys
import tempfile
import unittest
from collections import Counter
from decimal import Decimal
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
CONFIG_PATH = REPO_ROOT / "kapi/config/methodology-v0.2.0.json"
V021_CONFIG_PATH = REPO_ROOT / "kapi/config/methodology-v0.2.1.json"
V022_CONFIG_PATH = REPO_ROOT / "kapi/config/methodology-v0.2.2.json"
OFFICIAL_EVIDENCE_PATH = (
    REPO_ROOT
    / "kapi/evidence/official-provider-configuration-evidence-2026-07-10.json"
)
PAYLOAD_GENERATOR_PATH = REPO_ROOT / "kapi/fixtures/build_payloads.py"
CONSTRUCTION_MANIFEST_PATH = (
    REPO_ROOT / "kapi/fixtures/o200k-construction-manifest-v1.json"
)
GENERATOR_PATH = REPO_ROOT / "kapi/fixtures/build_synthetic.py"
FIXTURE_PATH = REPO_ROOT / "kapi/fixtures/synthetic-hand-example-v1.json"
EXPECTED_CONFIG_SHA256 = (
    "8f9442b9cd38acd46602446a9bbcc848a29fd079dfc63fefc0cb24125eaacd59"
)
EXPECTED_V021_CONFIG_SHA256 = (
    "1cb3cdc12139dad6a6bbaefc31f5023323d1672ba4fba69c531312f5a8a275b0"
)
EXPECTED_V022_CONFIG_SHA256 = (
    "f75219ff27d059b7cc417ba2b2dc3d4e280ccf8e7d2ab0a2b1a38085a99a8ba8"
)
EXPECTED_CONSTRUCTION_MANIFEST_SHA256 = (
    "660cf9990ad347334442622758757023f6f1b9b463273b9cfe5768bd358a918e"
)
EXPECTED_OFFICIAL_EVIDENCE_SHA256 = (
    "086d020a9aa40981c95ea2181655d7dcadaf9c1c682449d504641c56c34bdb91"
)
EXPECTED_FIXTURE_SHA256 = (
    "6cba82133f26cf3da4642f60e0006682f8ee190517cac34e2f673be06bb9e8d7"
)
O200K_ASSET_SHA256 = (
    "446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d"
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
        json.dumps(value, ensure_ascii=False, separators=(",", ":"), sort_keys=True)
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


def load_generator(path: Path, name: str):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"unable to load generator: {path}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class MethodologyFixtureTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.config_bytes, cls.config = load_canonical(CONFIG_PATH)
        cls.v021_config_bytes, cls.v021_config = load_canonical(V021_CONFIG_PATH)
        cls.v022_config_bytes, cls.v022_config = load_canonical(V022_CONFIG_PATH)
        cls.construction_manifest_bytes, cls.construction_manifest = load_canonical(
            CONSTRUCTION_MANIFEST_PATH
        )
        cls.official_evidence_bytes, cls.official_evidence = load_canonical(
            OFFICIAL_EVIDENCE_PATH
        )
        cls.fixture_bytes, cls.fixture = load_canonical(FIXTURE_PATH)
        cls.generator = load_generator(GENERATOR_PATH, "kapi_synthetic_generator")
        cls.payload_generator = load_generator(
            PAYLOAD_GENERATOR_PATH, "kapi_payload_generator"
        )

    def test_versioned_config_and_fixture_hashes_are_frozen(self) -> None:
        self.assertEqual(sha256_bytes(self.config_bytes), EXPECTED_CONFIG_SHA256)
        self.assertEqual(sha256_bytes(self.fixture_bytes), EXPECTED_FIXTURE_SHA256)
        self.assertEqual(
            self.fixture["methodology"]["config_sha256"], EXPECTED_CONFIG_SHA256
        )
        self.assertEqual(self.config["version"], "0.2.0")
        self.assertEqual(self.fixture["methodology"]["version"], "0.2.0")

    def test_v021_amendment_preserves_v020_construction_vintage(self) -> None:
        self.assertEqual(
            sha256_bytes(self.v021_config_bytes), EXPECTED_V021_CONFIG_SHA256
        )
        self.assertEqual(
            sha256_bytes(self.official_evidence_bytes),
            EXPECTED_OFFICIAL_EVIDENCE_SHA256,
        )
        self.assertEqual(self.v021_config["version"], "0.2.1")
        self.assertEqual(
            self.v021_config["official_provider_evidence"]["record_sha256"],
            EXPECTED_OFFICIAL_EVIDENCE_SHA256,
        )
        v020_shared = dict(self.config)
        v021_shared = dict(self.v021_config)
        for field in (
            "candidate_configurations",
            "methodology_amendment",
            "official_provider_evidence",
            "version",
        ):
            v020_shared.pop(field, None)
            v021_shared.pop(field, None)
        self.assertEqual(v021_shared, v020_shared)

    def test_v021_candidate_evidence_is_precise_and_fail_closed(self) -> None:
        candidates = {
            row["candidate_id"]: row
            for row in self.v021_config["candidate_configurations"]
        }
        openai = candidates["openai-gpt54mini-reasoning-none"]
        self.assertEqual(
            openai["official_documentation"]["status"],
            "failed_exact_model_id_unverified",
        )
        self.assertEqual(
            openai["eligibility_status"],
            "blocked_official_configuration_evidence",
        )
        google = candidates["google-gemini25flash-thinking-budget-0"]
        self.assertEqual(google["priced_configuration"], {"thinkingBudget": 0})
        self.assertEqual(
            google["official_documentation"]["status"],
            "supported_configuration_and_pricing",
        )
        anthropic = candidates[
            "anthropic-claude-sonnet-4-6-thinking-omitted"
        ]
        self.assertEqual(
            anthropic["priced_configuration"], {"thinking_parameter": "omitted"}
        )
        self.assertNotIn("stability_risk", anthropic)
        self.assertEqual(
            anthropic["lifecycle_status"]["status_at_snapshot"],
            "active_not_deprecated",
        )
        self.assertTrue(
            all(
                candidate["provider_preflight_status"]
                == "unverified_no_provider_call"
                and candidate["billed_usage_status"]
                == "unverified_no_billing_or_provider_call"
                for candidate in candidates.values()
            )
        )
        self.assertEqual(
            self.v021_config["readiness_gates"]["technical_go"],
            "failed_no_go",
        )

    def test_v022_adds_only_the_ci_portability_amendment(self) -> None:
        self.assertEqual(
            sha256_bytes(self.v022_config_bytes), EXPECTED_V022_CONFIG_SHA256
        )
        self.assertEqual(self.v022_config["version"], "0.2.2")
        self.assertEqual(
            self.v022_config["construction_manifest"],
            {
                "entry_count": 12,
                "path": "kapi/fixtures/o200k-construction-manifest-v1.json",
                "sha256": EXPECTED_CONSTRUCTION_MANIFEST_SHA256,
                "source_asset_vendored": False,
                "status": "frozen_derived_subset_for_portable_construction_only",
            },
        )
        self.assertFalse(
            self.v022_config["construction_reference"]["portable_reproduction"]
            ["full_source_asset_required"]
        )
        self.assertTrue(
            self.v022_config["construction_reference"]["source_asset_proof"]
            ["full_source_asset_required"]
        )
        path_configuration = self.v022_config["construction_reference"][
            "full_source_asset_path_configuration"
        ]
        self.assertEqual(path_configuration["cli_option"], "--asset-path")
        self.assertEqual(
            path_configuration["environment_variable"], "KAPI_O200K_ASSET_PATH"
        )
        self.assertIsNone(path_configuration["repository_default"])
        self.assertEqual(
            self.v022_config["readiness_gates"], self.v021_config["readiness_gates"]
        )

        v021_shared = dict(self.v021_config)
        v022_shared = dict(self.v022_config)
        for field in (
            "construction_manifest",
            "construction_reference",
            "methodology_amendment",
            "reference_tokenizer",
            "version",
        ):
            v021_shared.pop(field, None)
            v022_shared.pop(field, None)
        self.assertEqual(v022_shared, v021_shared)

    def test_frozen_construction_manifest_is_minimal_and_complete(self) -> None:
        self.assertEqual(
            sha256_bytes(self.construction_manifest_bytes),
            EXPECTED_CONSTRUCTION_MANIFEST_SHA256,
        )
        self.assertEqual(len(self.construction_manifest["entries"]), 12)
        self.assertFalse(
            self.construction_manifest["derivation"]
            ["portable_payload_generation_requires_full_source_asset"]
        )
        self.assertEqual(
            self.construction_manifest["derivation"]["source_asset_sha256"],
            O200K_ASSET_SHA256,
        )
        expected = {
            (profile_id, direction): chunk
            for profile_id, directions in self.payload_generator.CHUNKS.items()
            for direction, chunk in directions.items()
        }
        actual = {
            (entry["profile_id"], entry["direction"]): entry["chunk"]
            for entry in self.construction_manifest["entries"]
        }
        self.assertEqual(actual, expected)
        self.assertEqual(
            self.construction_manifest["nonclaims"],
            [
                "not_verified_model_tokenizer_mapping",
                "not_provider_preflight_request_count_evidence",
                "not_billed_usage_count_evidence",
                "not_billing_equivalence",
            ],
        )

    def test_methodology_encodes_v020_policy(self) -> None:
        self.assertEqual(self.config["capability"]["metric"], "ECI")
        self.assertFalse(
            self.config["capability"]["configuration_specific_score_allowed"]
        )
        self.assertEqual(self.config["evidence_policy"]["official_grades"], ["A"])
        self.assertEqual(
            self.config["evidence_policy"]["research_grades"], ["A", "B", "C"]
        )
        self.assertEqual(self.config["base_period_weeks"], 13)
        self.assertFalse(self.config["expense_controls"]["network_allowed"])
        self.assertFalse(self.config["expense_controls"]["model_calls_allowed"])
        self.assertEqual(
            self.config["expense_controls"]["total_external_spend_usd"], "0"
        )

        reference = self.config["reference_tokenizer"]
        self.assertEqual(reference["id"], "o200k_base")
        self.assertEqual(reference["asset_sha256"], O200K_ASSET_SHA256)
        self.assertEqual(reference["package_version"], "0.13.0")
        self.assertIn("not_model_mapping", reference["verification_status"])

        construction = self.config["construction_reference"]
        self.assertTrue(construction["counts_verified"])
        self.assertEqual(construction["tolerance_tokens"], 0)
        self.assertFalse(construction["model_mapping_verified"])
        self.assertIn("never a billing-token substitute", construction["role"])

    def test_evidence_classes_candidates_and_gates_fail_closed(self) -> None:
        classes = self.config["evidence_classes"]
        self.assertEqual(
            classes["construction_counts"]["status"], "verified_local_reference_only"
        )
        self.assertEqual(
            classes["provider_preflight_request_counts"]["status"],
            "unverified_no_provider_call",
        )
        self.assertEqual(
            classes["billed_usage_counts"]["status"],
            "unverified_no_billing_or_provider_call",
        )
        self.assertFalse(
            self.config["endpoint_specific_billing_counts"][
                "construction_reference_may_substitute_for_billing_counts"
            ]
        )
        self.assertEqual(
            self.config["endpoint_specific_billing_counts"]["verified_billing_rows"], 0
        )

        candidates = {
            row["candidate_id"]: row for row in self.config["candidate_configurations"]
        }
        self.assertIn("openai-gpt54mini-reasoning-none", candidates)
        self.assertIn("google-gemini25flash-thinking-budget-0", candidates)
        self.assertIn("anthropic-claude-sonnet-4-6-thinking-disabled", candidates)
        self.assertEqual(
            candidates["google-gemini25flash-thinking-budget-0"]["model_id"],
            "gemini-2.5-flash",
        )
        self.assertNotIn(
            "gemini-2.5-pro",
            {row["model_id"] for row in self.config["candidate_configurations"]},
        )
        self.assertEqual(
            candidates["anthropic-claude-sonnet-4-6-thinking-disabled"][
                "stability_risk"
            ],
            "deprecation_recorded",
        )
        gates = self.config["readiness_gates"]
        self.assertEqual(gates["technical_go"], "failed_no_go")
        self.assertIn("failed", gates["independent_review"])
        self.assertIn("failed", gates["observed_dry_run"])
        self.assertIn("failed", gates["shadow_week_1"])

    def test_profile_payload_hashes_and_exact_construction_counts(self) -> None:
        self.assertEqual(len(self.config["profiles"]), 6)
        total_count = 0
        rational_numerator = 0
        for profile in self.config["profiles"]:
            profile_id = profile["id"]
            self.assertEqual(profile["count"], 10)
            total_count += profile["count"]
            self.assertEqual(profile["weight"], {"denominator": 6, "numerator": 1})
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
                    raw, document = load_canonical(REPO_ROOT / payload["path"])
                    self.assertEqual(sha256_bytes(raw), payload["sha256"])
                    self.assertTrue(document["o200k_base_count_verified"])
                    self.assertEqual(document["model_calls_performed"], 0)
                    self.assertFalse(document["network_access_used"])
                    self.assertEqual(document["o200k_base_asset_sha256"], O200K_ASSET_SHA256)
                    self.assertEqual(document["construction_count_tolerance_tokens"], 0)
                    self.assertEqual(
                        document["construction_token_count"],
                        payload["construction_token_count"],
                    )
                    self.assertEqual(
                        document["construction_token_count"],
                        payload["reference_token_design_target"],
                    )
                    self.assertEqual(
                        document["content"],
                        document["single_token_chunk"]
                        * document["construction_token_count"],
                    )
                    self.assertEqual(
                        document["token_count_status"],
                        "exact_o200k_base_construction_count_not_billing",
                    )
            self.assertEqual(
                profile["input_payload_path"], f"kapi/profiles/{profile_id}/input-100.json"
            )
            self.assertEqual(
                profile["output_payload_path"], f"kapi/profiles/{profile_id}/output-100.json"
            )
        self.assertEqual(total_count, 60)
        self.assertEqual(rational_numerator, 6)

    def test_payload_and_bundle_generators_are_deterministic(self) -> None:
        self.assertEqual(self.generator.build_bytes(), self.fixture_bytes)
        self.assertEqual(self.generator.build_bytes(), self.generator.build_bytes())
        for script in (PAYLOAD_GENERATOR_PATH, GENERATOR_PATH):
            completed = subprocess.run(
                [sys.executable, str(script), "--check"],
                cwd=REPO_ROOT,
                check=False,
                capture_output=True,
                text=True,
            )
            self.assertEqual(
                completed.returncode,
                0,
                msg=f"{script}\nstdout={completed.stdout}\nstderr={completed.stderr}",
            )

    def test_portable_payload_check_does_not_require_source_asset(self) -> None:
        environment = dict(os.environ)
        environment.pop("KAPI_O200K_ASSET_PATH", None)
        completed = subprocess.run(
            [sys.executable, str(PAYLOAD_GENERATOR_PATH), "--check-frozen-manifest"],
            cwd=REPO_ROOT,
            check=False,
            capture_output=True,
            text=True,
            env=environment,
        )
        self.assertEqual(
            completed.returncode,
            0,
            msg=f"stdout={completed.stdout}\nstderr={completed.stderr}",
        )

    def test_full_source_asset_proof_is_explicit_and_fails_closed(self) -> None:
        environment = dict(os.environ)
        environment.pop("KAPI_O200K_ASSET_PATH", None)
        missing = subprocess.run(
            [sys.executable, str(PAYLOAD_GENERATOR_PATH), "--verify-source-asset"],
            cwd=REPO_ROOT,
            check=False,
            capture_output=True,
            text=True,
            env=environment,
        )
        self.assertEqual(missing.returncode, 2)
        self.assertIn("requires --asset-path", missing.stderr)

        with tempfile.TemporaryDirectory() as directory:
            wrong_asset = Path(directory) / "o200k_base.tiktoken"
            wrong_asset.write_bytes(b"not the approved asset\n")
            wrong = subprocess.run(
                [
                    sys.executable,
                    str(PAYLOAD_GENERATOR_PATH),
                    "--verify-source-asset",
                    "--asset-path",
                    str(wrong_asset),
                ],
                cwd=REPO_ROOT,
                check=False,
                capture_output=True,
                text=True,
                env=environment,
            )
        self.assertEqual(wrong.returncode, 2)
        self.assertIn("unexpected o200k_base asset hash", wrong.stderr)

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
        self.assertEqual(self.fixture["generation"]["model_calls_performed"], 0)
        self.assertFalse(self.fixture["generation"]["network_access_used"])
        self.assertEqual(self.fixture["generation"]["total_external_spend_usd"], "0")
        for endpoint in self.fixture["endpoints"]:
            self.assertEqual(endpoint["reasoning_mode"], "disabled")
            self.assertEqual(
                endpoint["billing_tokenizer"], "provider-billing-counts-unverified"
            )
            self.assertEqual(
                endpoint["construction_tokenizer"],
                "tiktoken-0.13.0:o200k_base(explicit_construction)",
            )

    def test_grade_a_synthetic_sources_and_eci_scope(self) -> None:
        self.assertEqual(len(self.fixture["source_artifacts"]), 60)
        for source in self.fixture["source_artifacts"]:
            self.assertEqual(source["evidence_grade"], "A")
            self.assertTrue(source["url"].startswith("synthetic://"))
            self.assertEqual(
                source["content_sha256"],
                sha256_bytes(canonical_bytes(source["synthetic_content"])),
            )
        self.assertEqual(len(self.fixture["capability_evidence"]), 4)
        for record in self.fixture["capability_evidence"]:
            self.assertEqual(record["evidence_grade"], "A")
            self.assertEqual(record["metric"], "ECI")
            self.assertFalse(record["score_is_configuration_specific"])
            self.assertGreaterEqual(
                Decimal(record["score"]),
                Decimal(self.config["capability"]["headline_threshold"]),
            )

    def test_endpoint_specific_counts_cover_grid_but_billing_is_unverified(self) -> None:
        token_counts = self.fixture["token_counts"]
        self.assertEqual(len(token_counts), 4 * 6 * 9)
        record_counts = Counter(
            (record["endpoint_id"], record["profile_id"]) for record in token_counts
        )
        self.assertEqual(set(record_counts.values()), {9})
        headline = [record for record in token_counts if record["size_variant"] == "100x100"]
        self.assertEqual(len(headline), 4 * 6)
        for record in headline:
            expected_input, expected_output = HEADLINE_COUNTS[record["profile_id"]]
            self.assertEqual(record["input_tokens"], expected_input)
            self.assertEqual(record["output_tokens"], expected_output)
            self.assertEqual(record["billing_tokenizer"], "provider-billing-counts-unverified")
            self.assertEqual(record["billing_usage_count_status"], "unverified_no_provider_call")
            self.assertEqual(record["construction_count_evidence_class"], "construction_count")
            self.assertIn("not a provider preflight count", record["synthetic_count_note"])

    def test_thirteen_identical_base_fridays_and_current_prices(self) -> None:
        weeks = self.fixture["weeks"]
        self.assertEqual([week["id"] for week in weeks[:13]], self.fixture["base_period_week_ids"])
        self.assertEqual(weeks[-1]["id"], self.fixture["current_week_id"])
        observations = self.fixture["price_observations"]
        self.assertEqual(len(observations), 14 * 4 * 2)
        by_key = {
            (record["week_id"], record["endpoint_id"], record["component"]): record
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
                    self.assertEqual(record["unit"], "per_million_native_tokens")

    def test_hand_example_values_and_expected_withhold(self) -> None:
        expected = self.fixture["expected_result"]
        diagnostic = expected["diagnostic_hand_calculation"]
        self.assertEqual(diagnostic["base_sixty_profile_basket_cost"], "2.476")
        self.assertEqual(diagnostic["current_sixty_profile_basket_cost"], "1.333")
        self.assertEqual(
            diagnostic["current_index_full_precision"],
            "53.8368336025848142164781906300",
        )
        self.assertEqual(diagnostic["current_index_display"], "53.8")
        strict = expected["strict_headline"]
        self.assertEqual(strict["status"], "withheld_concentration")
        self.assertFalse(strict["base_period_usable"])
        self.assertTrue(strict["all_weeks_withheld"])
        self.assertTrue(expected["concentration"]["base"]["withhold"])
        self.assertTrue(expected["concentration"]["current"]["withhold"])


if __name__ == "__main__":
    unittest.main()
