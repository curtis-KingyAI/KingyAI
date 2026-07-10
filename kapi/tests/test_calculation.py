from __future__ import annotations

import copy
import hashlib
import json
import unittest
from datetime import datetime, timedelta, timezone
from decimal import Decimal, localcontext
from pathlib import Path

from kapi.calculation import CalculationError, calculate_index, chain_link


PROFILE_SPECS = [
    ("extraction", 2000, 200, ["structured_output"]),
    ("analysis", 4000, 500, []),
    ("rag", 10000, 800, []),
    ("summarization", 25000, 1500, []),
    ("code", 12000, 3000, []),
    ("workflow", 30000, 6000, ["tool_calling"]),
]

BASE_PRICES = {
    "A": ("2", "8"),
    "B": ("1", "12"),
    "C": ("3", "6"),
    "D": ("0.8", "20"),
}

CURRENT_PRICES = {
    "A": ("1", "5"),
    "B": ("0.5", "6"),
    "C": ("2.5", "5"),
    "D": ("0.4", "10"),
}


def _hash(label: str) -> str:
    return hashlib.sha256(label.encode("utf-8")).hexdigest()


def _at(moment: datetime) -> str:
    return moment.astimezone(timezone.utc).isoformat().replace("+00:00", "Z")


def make_methodology() -> dict:
    editorial = {
        "extraction": "20",
        "analysis": "20",
        "rag": "20",
        "summarization": "15",
        "code": "15",
        "workflow": "10",
    }
    return {
        "methodology_id": "kapi-sw-test",
        "version": "0.1.0",
        "base_period_weeks": 13,
        "capability": {
            "metric": "ECI",
            "headline_threshold": "130",
            "sensitivity_thresholds": ["125", "130", "135"],
        },
        "evidence_policy": {
            "official_grades": ["A"],
            "research_grades": ["A", "B", "C"],
            "excluded_grades": ["D"],
        },
        "eligibility": {
            "region": "US",
            "currency": "USD",
            "unit": "per_million_tokens",
            "required_endpoint_flags": [
                "public",
                "ga",
                "synchronous",
                "on_demand",
                "available_us",
                "standard_list_price",
            ],
            "allowed_alias_types": ["immutable", "resolved"],
            "reasoning_mode": "disabled",
            "seasoning_days": 7,
        },
        "selection": {
            "provider_count": 3,
            "creator_count": 3,
            "method": "median_independent_three",
            "tie_break": ["median", "total", "endpoint_ids"],
        },
        "concentration": {
            "warning_share": "0.35",
            "warning_profile_count": 3,
            "withhold_share": "0.50",
            "withhold_profile_count": 4,
        },
        "profiles": [
            {
                "id": profile_id,
                "name": profile_id,
                "count": 10,
                "weight": {"numerator": 1, "denominator": 6},
                "input_target_tokens": input_tokens,
                "output_target_tokens": output_tokens,
                "input_payload_path": f"payloads/{profile_id}.input.txt",
                "output_payload_path": f"payloads/{profile_id}.output.txt",
                "input_payload_sha256": _hash(f"{profile_id}:input"),
                "output_payload_sha256": _hash(f"{profile_id}:output"),
                "required_features": features,
            }
            for profile_id, input_tokens, output_tokens, features in PROFILE_SPECS
        ],
        "sensitivities": {"editorial_weights": editorial},
    }


def make_bundle(number_of_weeks: int = 14) -> dict:
    first_cutoff = datetime(2026, 1, 2, 16, tzinfo=timezone.utc)
    weeks = [
        {"id": f"w{number + 1:02d}", "cutoff_at": _at(first_cutoff + timedelta(days=7 * number))}
        for number in range(number_of_weeks)
    ]
    source_capability = {
        "id": "source-capability",
        "url": "https://example.test/eci",
        "retrieved_at": "2025-12-01T00:00:00Z",
        "evidence_grade": "A",
        "media_type": "application/json",
        "content_sha256": _hash("source-capability"),
        "snapshot_path": "sources/eci.json",
        "license_note": "test",
    }
    source_prices = {
        "id": "source-prices",
        "url": "https://example.test/prices",
        "retrieved_at": "2025-12-01T00:00:00Z",
        "evidence_grade": "A",
        "media_type": "text/html",
        "content_sha256": _hash("source-prices"),
        "snapshot_path": "sources/prices.html",
        "license_note": "test",
    }
    providers = [{"id": f"provider-{letter}", "name": letter} for letter in "ABCD"]
    creators = [{"id": f"creator-{letter}", "name": letter} for letter in "ABCD"]
    models = [
        {
            "id": f"model-{letter}",
            "creator_id": f"creator-{letter}",
            "version": f"{letter}-1.0",
            "alias_type": "immutable",
            "immutable_version": True,
        }
        for letter in "ABCD"
    ]
    endpoints = [
        {
            "id": letter,
            "provider_id": f"provider-{letter}",
            "model_id": f"model-{letter}",
            "configuration_id": f"config-{letter}",
            "region": "US",
            "tier": "standard",
            "public": True,
            "ga": True,
            "synchronous": True,
            "on_demand": True,
            "available_us": True,
            "standard_list_price": True,
            "reasoning_mode": "disabled",
            "billing_tokenizer": f"tokenizer-{letter}",
            "tokenizer_reproducible": True,
            "features": ["structured_output", "tool_calling"],
            "available_from": "2025-01-01T00:00:00Z",
            "available_until": None,
        }
        for letter in "ABCD"
    ]
    capability_evidence = [
        {
            "id": f"capability-{letter}",
            "model_id": f"model-{letter}",
            "endpoint_id": letter,
            "metric": "ECI",
            "metric_version": "2025-11",
            "score": "140",
            "configuration_id": f"config-{letter}",
            "evaluated_at": "2025-11-15T00:00:00Z",
            "data_vintage": "2025-11",
            "source_id": "source-capability",
            "evidence_grade": "A",
        }
        for letter in "ABCD"
    ]
    methodology = make_methodology()
    token_counts = []
    for endpoint in endpoints:
        for profile in methodology["profiles"]:
            token_counts.append(
                {
                    "id": f"tokens-{endpoint['id']}-{profile['id']}",
                    "endpoint_id": endpoint["id"],
                    "profile_id": profile["id"],
                    "input_tokens": profile["input_target_tokens"],
                    "output_tokens": profile["output_target_tokens"],
                    "input_payload_sha256": profile["input_payload_sha256"],
                    "output_payload_sha256": profile["output_payload_sha256"],
                    "billing_tokenizer": endpoint["billing_tokenizer"],
                    "size_variant": "100x100",
                }
            )

    observations = []
    for week_number, week in enumerate(weeks):
        cutoff = datetime.fromisoformat(week["cutoff_at"].replace("Z", "+00:00"))
        prices = BASE_PRICES if week_number < 13 else CURRENT_PRICES
        for letter, (input_price, output_price) in prices.items():
            for component, amount in (("input", input_price), ("output", output_price)):
                observations.append(
                    {
                        "id": f"price-{week['id']}-{letter}-{component}",
                        "endpoint_id": letter,
                        "week_id": week["id"],
                        "component": component,
                        "amount_per_million": amount,
                        "currency": "USD",
                        "unit": "per_million_tokens",
                        "region": "US",
                        "tier": "standard",
                        "context_min_tokens": 0,
                        "context_max_tokens": None,
                        "effective_at": _at(cutoff - timedelta(days=1)),
                        "observed_at": _at(cutoff - timedelta(hours=1)),
                        "source_id": "source-prices",
                        "evidence_grade": "A",
                        "supersedes_observation_id": None,
                    }
                )
    return {
        "schema_version": "0.1.0",
        "dataset_id": "synthetic-a-d",
        "dataset_kind": "synthetic",
        "weeks": weeks,
        "providers": providers,
        "creators": creators,
        "models": models,
        "endpoints": endpoints,
        "source_artifacts": [source_capability, source_prices],
        "capability_evidence": capability_evidence,
        "token_counts": token_counts,
        "price_observations": observations,
        "corrections": [],
    }


def profile_for(week: dict, profile_id: str) -> dict:
    return next(profile for profile in week["profiles"] if profile["profile_id"] == profile_id)


def candidate_for(profile: dict, endpoint_id: str) -> dict:
    return next(candidate for candidate in profile["eligible_candidates"] if candidate["endpoint_id"] == endpoint_id)


def reset_current_prices_to_base(bundle: dict) -> None:
    current_week = bundle["weeks"][-1]["id"]
    for observation in bundle["price_observations"]:
        if observation["week_id"] != current_week:
            continue
        input_price, output_price = BASE_PRICES[observation["endpoint_id"]]
        observation["amount_per_million"] = input_price if observation["component"] == "input" else output_price


class CalculationTests(unittest.TestCase):
    def test_synthetic_hand_calculation_and_diagnostic_withhold(self) -> None:
        result = calculate_index(make_bundle(), make_methodology())
        current = result["weeks"][-1]

        self.assertTrue(result["not_for_publication"])
        self.assertFalse(result["published"])
        self.assertFalse(result["deployed"])
        self.assertFalse(result["citation"]["permitted"])
        self.assertIn("SYNTHETIC KAPI PROTOTYPE", result["notice"])
        self.assertEqual("synthetic_official_policy_simulation", result["series_type"])
        self.assertEqual(result["basket_profile_count"], 60)
        self.assertEqual(result["base_period"]["week_ids"], [f"w{number:02d}" for number in range(1, 14)])
        self.assertAlmostEqual(Decimal(result["base_period"]["basket_mean_cost"]), Decimal("0.0412666667"), places=10)
        self.assertAlmostEqual(Decimal(current["basket_unit_cost"]), Decimal("0.0222166667"), places=10)
        self.assertAlmostEqual(Decimal(current["index_level"]), Decimal("53.8368"), places=4)
        self.assertAlmostEqual(Decimal(result["base_period"]["basket_60_mean_cost"]), Decimal("2.476"), places=10)
        self.assertAlmostEqual(Decimal(current["basket_60_cost"]), Decimal("1.333"), places=10)
        self.assertAlmostEqual(Decimal(current["geometric_index"]), Decimal("52.5915"), places=4)
        self.assertEqual(Decimal(current["frontier_index"]), Decimal("50"))
        self.assertEqual(current["calculation_status"], "complete")
        self.assertEqual(current["release_status"], "withheld_concentration")
        self.assertAlmostEqual(Decimal(current["week_over_week_percent"]), Decimal("-46.1632"), places=4)
        self.assertEqual(current["since_base_percent"], current["week_over_week_percent"])
        self.assertIsNone(current["year_over_year_percent"])
        contributions = sum(
            (Decimal(profile["contribution_percentage_points"]) for profile in current["profiles"]),
            Decimal(0),
        )
        self.assertAlmostEqual(contributions, Decimal(current["week_over_week_percent"]), places=20)
        self.assertTrue(current["lineage"]["observation_ids"])
        self.assertEqual(set(current["lineage"]["source_ids"]), {"source-capability", "source-prices"})

    def test_cost_then_endpoint_id_deterministically_sets_tied_median(self) -> None:
        result = calculate_index(make_bundle(), make_methodology())
        extraction = profile_for(result["weeks"][0], "extraction")
        self.assertEqual(extraction["selected_triple_endpoint_ids"], ["A", "B", "D"])
        self.assertEqual(extraction["selected_cost_order"], ["B", "A", "D"])
        self.assertEqual(extraction["price_setter_endpoint_id"], "A")
        self.assertEqual(Decimal(extraction["headline_price"]), Decimal("0.0056"))

    def test_independence_requires_three_providers_and_three_creators(self) -> None:
        bundle = make_bundle()
        for endpoint in bundle["endpoints"]:
            endpoint["provider_id"] = "provider-A"
        result = calculate_index(bundle, make_methodology())
        self.assertEqual(result["status"], "pending_base")
        self.assertEqual(result["weeks"][0]["calculation_status"], "incomplete")
        self.assertIn("no_independent_three_provider_creator_triple", profile_for(result["weeks"][0], "extraction")["diagnostics"])

    def test_entry_and_exit_are_applied_at_each_week_cutoff(self) -> None:
        bundle = make_bundle()
        current_cutoff = bundle["weeks"][-1]["cutoff_at"]
        current_dt = datetime.fromisoformat(current_cutoff.replace("Z", "+00:00"))
        next(endpoint for endpoint in bundle["endpoints"] if endpoint["id"] == "D")["available_from"] = _at(current_dt - timedelta(days=8))
        next(endpoint for endpoint in bundle["endpoints"] if endpoint["id"] == "A")["available_until"] = current_cutoff
        result = calculate_index(bundle, make_methodology())
        first_exclusions = profile_for(result["weeks"][0], "extraction")["endpoint_exclusions"]
        current_exclusions = profile_for(result["weeks"][-1], "extraction")["endpoint_exclusions"]
        self.assertIn("not_yet_available", next(item for item in first_exclusions if item["endpoint_id"] == "D")["reasons"])
        self.assertIn("no_longer_available", next(item for item in current_exclusions if item["endpoint_id"] == "A")["reasons"])
        self.assertEqual(result["weeks"][-1]["calculation_status"], "complete")

    def test_missing_retained_source_evidence_withholds(self) -> None:
        bundle = make_bundle()
        next(source for source in bundle["source_artifacts"] if source["id"] == "source-prices").pop("snapshot_path")
        result = calculate_index(bundle, make_methodology())
        self.assertEqual(result["weeks"][0]["release_status"], "withheld_incomplete")
        exclusions = profile_for(result["weeks"][0], "extraction")["endpoint_exclusions"]
        self.assertTrue(all("missing_grade_eligible_input_price" in item["reasons"] for item in exclusions))

    def test_applicable_long_context_tier_is_used(self) -> None:
        bundle = make_bundle()
        current_week = bundle["weeks"][-1]["id"]
        cutoff = datetime.fromisoformat(bundle["weeks"][-1]["cutoff_at"].replace("Z", "+00:00"))
        short = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == current_week and record["endpoint_id"] == "B" and record["component"] == "input"
        )
        short["context_max_tokens"] = 9999
        long_record = copy.deepcopy(short)
        long_record.update(
            {
                "id": "price-current-B-input-long",
                "amount_per_million": "1",
                "context_min_tokens": 10000,
                "context_max_tokens": None,
                "effective_at": _at(cutoff - timedelta(days=1)),
            }
        )
        bundle["price_observations"].append(long_record)
        result = calculate_index(bundle, make_methodology())
        extraction = profile_for(result["weeks"][-1], "extraction")
        workflow = profile_for(result["weeks"][-1], "workflow")
        self.assertEqual(Decimal(extraction["headline_price"]), Decimal("0.0028"))
        self.assertEqual(Decimal(workflow["headline_price"]), Decimal("0.066"))

    def test_grade_bc_never_enters_official_but_can_form_research_series(self) -> None:
        bundle = make_bundle()
        next(source for source in bundle["source_artifacts"] if source["id"] == "source-prices")["evidence_grade"] = "B"
        for observation in bundle["price_observations"]:
            observation["evidence_grade"] = "B"
        official = calculate_index(bundle, make_methodology())
        research = calculate_index(bundle, make_methodology(), evidence_mode="research")
        self.assertEqual(official["weeks"][0]["release_status"], "withheld_incomplete")
        self.assertEqual(research["series_type"], "synthetic_research_policy_simulation")
        self.assertEqual(research["weeks"][0]["calculation_status"], "complete")
        self.assertFalse(any(week["release_status"] == "publishable" for week in research["weeks"]))

    def test_rolling_alias_is_excluded_without_bridging(self) -> None:
        bundle = make_bundle()
        next(model for model in bundle["models"] if model["id"] == "model-A")["alias_type"] = "rolling"
        result = calculate_index(bundle, make_methodology())
        exclusions = profile_for(result["weeks"][0], "extraction")["endpoint_exclusions"]
        self.assertIn("alias_type", next(item for item in exclusions if item["endpoint_id"] == "A")["reasons"])

    def test_concentration_share_boundary_is_strictly_greater_than(self) -> None:
        bundle = make_bundle()
        method = make_methodology()
        first = calculate_index(bundle, method)
        share = first["weeks"][0]["concentration"]["providers"]["provider-A"]["share"]
        method["concentration"]["withhold_share"] = share
        method["concentration"]["withhold_profile_count"] = 7
        result = calculate_index(bundle, method)
        self.assertEqual(result["weeks"][0]["release_status"], "publishable")
        self.assertEqual(result["weeks"][-1]["release_status"], "withheld_concentration")

    def test_base_uses_earliest_thirteen_consecutive_complete_weeks(self) -> None:
        bundle = make_bundle(15)
        bundle["weeks"][0]["complete"] = False
        result = calculate_index(bundle, make_methodology())
        self.assertEqual(result["base_period"]["week_ids"], [f"w{number:02d}" for number in range(2, 15)])
        self.assertIsNotNone(result["weeks"][-1]["index_level"])

    def test_fewer_than_thirteen_complete_weeks_leaves_index_pending(self) -> None:
        result = calculate_index(make_bundle(12), make_methodology())
        self.assertEqual(result["status"], "pending_base")
        self.assertEqual(result["base_period"]["week_ids"], [])
        self.assertIsNone(result["weeks"][-1]["index_level"])

    def test_conflicting_price_observations_exclude_only_affected_endpoint(self) -> None:
        bundle = make_bundle()
        original = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == "w01" and record["endpoint_id"] == "A" and record["component"] == "input"
        )
        conflict = copy.deepcopy(original)
        conflict["id"] = "conflicting-A-input"
        conflict["amount_per_million"] = "99"
        bundle["price_observations"].append(conflict)
        result = calculate_index(bundle, make_methodology())
        exclusions = profile_for(result["weeks"][0], "extraction")["endpoint_exclusions"]
        self.assertIn("conflicting_input_price", next(item for item in exclusions if item["endpoint_id"] == "A")["reasons"])
        self.assertEqual(result["weeks"][0]["calculation_status"], "complete")

    def test_explicit_supersession_resolves_a_price_change_and_preserves_lineage(self) -> None:
        bundle = make_bundle()
        original = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == "w01" and record["endpoint_id"] == "A" and record["component"] == "input"
        )
        replacement = copy.deepcopy(original)
        replacement["id"] = "replacement-A-input"
        replacement["amount_per_million"] = "2.25"
        replacement["supersedes_observation_id"] = original["id"]
        bundle["price_observations"].append(replacement)
        result = calculate_index(bundle, make_methodology())
        extraction = profile_for(result["weeks"][0], "extraction")
        self.assertIn("replacement-A-input", extraction["observation_ids"])
        self.assertNotIn(original["id"], extraction["observation_ids"])

    def test_correction_envelope_supersedes_observation_and_is_in_lineage(self) -> None:
        bundle = make_bundle()
        original = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == "w01" and record["endpoint_id"] == "A" and record["component"] == "input"
        )
        replacement = copy.deepcopy(original)
        replacement["id"] = "corrected-A-input"
        replacement["amount_per_million"] = "2.25"
        replacement["supersedes_observation_id"] = original["id"]
        bundle["price_observations"].append(replacement)
        bundle["corrections"].append(
            {
                "id": "correction-A-input",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
            }
        )
        result = calculate_index(bundle, make_methodology())
        extraction = profile_for(result["weeks"][0], "extraction")
        self.assertIn("corrected-A-input", extraction["observation_ids"])
        self.assertNotIn(original["id"], extraction["observation_ids"])
        self.assertIn("correction-A-input", extraction["lineage"]["correction_ids"])

    def test_capability_threshold_hooks_are_calculated_independently(self) -> None:
        bundle = make_bundle()
        for record in bundle["capability_evidence"]:
            if record["endpoint_id"] in {"C", "D"}:
                record["score"] = "128"
        result = calculate_index(bundle, make_methodology())
        self.assertEqual(result["sensitivities"]["capability_thresholds"]["125"]["status"], "calculated")
        self.assertEqual(result["sensitivities"]["capability_thresholds"]["130"]["status"], "pending_base")
        self.assertEqual(result["sensitivities"]["capability_thresholds"]["135"]["status"], "pending_base")
        sensitivity_week = result["sensitivities"]["capability_thresholds"]["125"]["weeks"][0]
        self.assertEqual(sensitivity_week["week_id"], result["weeks"][0]["week_id"])
        self.assertEqual(
            {
                "week_id",
                "cutoff_at",
                "calculation_status",
                "release_status",
                "basket_unit_cost",
                "basket_60_cost",
                "index_level",
                "geometric_index",
                "frontier_index",
                "mean_three_index",
                "week_over_week_percent",
                "four_week_percent",
                "since_base_percent",
                "year_over_year_percent",
                "concentration",
            },
            set(sensitivity_week),
        )

    def test_editorial_weight_sensitivity_and_weight_override(self) -> None:
        method = make_methodology()
        result = calculate_index(make_bundle(), method)
        editorial = result["sensitivities"]["editorial_weights"]
        self.assertEqual(editorial["status"], "calculated")
        self.assertNotEqual(editorial["weeks"][-1]["index_level"], result["weeks"][-1]["index_level"])
        override = {profile_id: "1" for profile_id, *_ in PROFILE_SPECS}
        overridden = calculate_index(make_bundle(), method, weights=override)
        self.assertEqual(overridden["weights"], result["weights"])

    def test_reasoning_and_token_hash_are_exact_eligibility_requirements(self) -> None:
        bundle = make_bundle()
        next(endpoint for endpoint in bundle["endpoints"] if endpoint["id"] == "A")["reasoning_mode"] = "dynamic"
        next(record for record in bundle["token_counts"] if record["endpoint_id"] == "B" and record["profile_id"] == "extraction")["input_payload_sha256"] = _hash("wrong")
        result = calculate_index(bundle, make_methodology())
        exclusions = profile_for(result["weeks"][0], "extraction")["endpoint_exclusions"]
        self.assertIn("reasoning_not_disabled", next(item for item in exclusions if item["endpoint_id"] == "A")["reasons"])
        self.assertIn("invalid_token_count", next(item for item in exclusions if item["endpoint_id"] == "B")["reasons"])
        self.assertEqual(result["weeks"][0]["release_status"], "withheld_incomplete")

    def test_chain_link_and_invalid_overlap(self) -> None:
        self.assertEqual(chain_link("87.5", "12", "10"), Decimal("105.0"))
        with self.assertRaises(CalculationError):
            chain_link("87.5", "12", "0")

    def test_binary_float_input_is_rejected(self) -> None:
        with self.assertRaises(CalculationError):
            calculate_index(make_bundle(), make_methodology(), capability_threshold=130.0)

    def test_price_and_correction_supersession_cycles_are_rejected(self) -> None:
        price_cycle = make_bundle()
        original = price_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = "cycle-price-replacement"
        original["supersedes_observation_id"] = replacement["id"]
        replacement["supersedes_observation_id"] = original["id"]
        price_cycle["price_observations"].append(replacement)
        with self.assertRaisesRegex(CalculationError, "contains a cycle"):
            calculate_index(price_cycle, make_methodology())

        correction_cycle = make_bundle()
        original = correction_cycle["price_observations"][0]
        replacement = copy.deepcopy(original)
        replacement["id"] = "corrected-cycle-price"
        replacement["supersedes_observation_id"] = original["id"]
        correction_cycle["price_observations"].append(replacement)
        correction_cycle["corrections"] = [
            {
                "id": "correction-cycle-1",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-cycle-2",
            },
            {
                "id": "correction-cycle-2",
                "superseded_observation_id": original["id"],
                "replacement_observation_id": replacement["id"],
                "supersedes_correction_id": "correction-cycle-1",
            },
        ]
        with self.assertRaisesRegex(CalculationError, "contains a cycle"):
            calculate_index(correction_cycle, make_methodology())

        missing_token_id = make_bundle()
        missing_token_id["token_counts"][0].pop("id")
        with self.assertRaisesRegex(CalculationError, "must be a stable string"):
            calculate_index(missing_token_id, make_methodology())

        orphan_correction = make_bundle()
        orphan_correction["corrections"] = [{"id": "orphan-correction"}]
        with self.assertRaisesRegex(
            CalculationError, "must link superseded and replacement"
        ):
            calculate_index(orphan_correction, make_methodology())

    def test_no_price_change_produces_index_one_hundred(self) -> None:
        bundle = make_bundle()
        reset_current_prices_to_base(bundle)
        result = calculate_index(bundle, make_methodology())
        current = result["weeks"][-1]
        self.assertEqual(Decimal(current["index_level"]), Decimal("100"))
        self.assertEqual(Decimal(current["geometric_index"]), Decimal("100"))
        self.assertEqual(Decimal(current["frontier_index"]), Decimal("100"))
        self.assertEqual(Decimal(current["mean_three_index"]), Decimal("100"))
        self.assertEqual(Decimal(current["week_over_week_percent"]), Decimal("0"))

    def test_zero_hit_cache_components_are_ignored_by_headline(self) -> None:
        bundle = make_bundle()
        cached = copy.deepcopy(bundle["price_observations"][0])
        cached.update(
            {
                "id": "cache-read-price",
                "component": "cache_read",
                "amount_per_million": "0.01",
            }
        )
        bundle["price_observations"].append(cached)
        with_cache = calculate_index(bundle, make_methodology())
        without_cache = calculate_index(make_bundle(), make_methodology())
        self.assertEqual(with_cache["weeks"][-1]["index_level"], without_cache["weeks"][-1]["index_level"])

    def test_input_only_price_change_scales_with_input_volume(self) -> None:
        bundle = make_bundle()
        reset_current_prices_to_base(bundle)
        current_week = bundle["weeks"][-1]["id"]
        observation = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == current_week and record["endpoint_id"] == "A" and record["component"] == "input"
        )
        observation["amount_per_million"] = CURRENT_PRICES["A"][0]
        result = calculate_index(bundle, make_methodology())
        base_extraction = candidate_for(profile_for(result["weeks"][-2], "extraction"), "A")
        current_extraction = candidate_for(profile_for(result["weeks"][-1], "extraction"), "A")
        base_code = candidate_for(profile_for(result["weeks"][-2], "code"), "A")
        current_code = candidate_for(profile_for(result["weeks"][-1], "code"), "A")
        self.assertEqual(current_extraction["output_cost"], base_extraction["output_cost"])
        self.assertEqual(current_code["output_cost"], base_code["output_cost"])
        self.assertEqual(
            Decimal(current_extraction["cost"]) - Decimal(base_extraction["cost"]),
            Decimal("-0.002"),
        )
        self.assertEqual(
            Decimal(current_code["cost"]) - Decimal(base_code["cost"]),
            Decimal("-0.012"),
        )

    def test_output_only_price_change_scales_with_output_volume(self) -> None:
        bundle = make_bundle()
        reset_current_prices_to_base(bundle)
        current_week = bundle["weeks"][-1]["id"]
        observation = next(
            record
            for record in bundle["price_observations"]
            if record["week_id"] == current_week and record["endpoint_id"] == "A" and record["component"] == "output"
        )
        observation["amount_per_million"] = CURRENT_PRICES["A"][1]
        result = calculate_index(bundle, make_methodology())
        base_extraction = candidate_for(profile_for(result["weeks"][-2], "extraction"), "A")
        current_extraction = candidate_for(profile_for(result["weeks"][-1], "extraction"), "A")
        base_code = candidate_for(profile_for(result["weeks"][-2], "code"), "A")
        current_code = candidate_for(profile_for(result["weeks"][-1], "code"), "A")
        self.assertEqual(current_extraction["input_cost"], base_extraction["input_cost"])
        self.assertEqual(current_code["input_cost"], base_code["input_cost"])
        self.assertEqual(
            Decimal(current_extraction["cost"]) - Decimal(base_extraction["cost"]),
            Decimal("-0.0006"),
        )
        self.assertEqual(
            Decimal(current_code["cost"]) - Decimal(base_code["cost"]),
            Decimal("-0.009"),
        )

    def test_committed_methodology_and_fixture_builder_integrate(self) -> None:
        from kapi.fixtures.build_synthetic import build_bundle

        repository = Path(__file__).resolve().parents[2]
        methodology = json.loads((repository / "kapi/config/methodology-v0.1.0.json").read_text(encoding="utf-8"))
        result = calculate_index(build_bundle(), methodology)
        current = result["weeks"][-1]
        self.assertEqual(result["status"], "withheld_concentration")
        self.assertFalse(result["base_period"]["release_usable"])
        self.assertTrue(result["base_period"]["diagnostic_only"])
        self.assertAlmostEqual(Decimal(current["index_level"]), Decimal("53.8368"), places=4)
        self.assertEqual(set(result["sensitivities"]["payload_sizes"]), {
            "075x075", "075x100", "075x125", "100x075",
            "100x125", "125x075", "125x100", "125x125",
        })
        self.assertEqual(set(result["sensitivities"]["capability_thresholds"]), {"125", "130", "135"})
        self.assertEqual(result["sensitivities"]["editorial_weights"]["status"], "calculated")
        payload_sizes = result["sensitivities"]["payload_sizes"]
        payload_range = result["sensitivities"]["payload_size_range"]
        latest_values = {
            variant_id: Decimal(series["weeks"][-1]["index_level"])
            for variant_id, series in payload_sizes.items()
        }
        minimum = min(latest_values.values())
        maximum = max(latest_values.values())
        self.assertEqual(payload_range["status"], "complete")
        self.assertEqual(payload_range["cell_count"], 8)
        self.assertEqual(payload_range["variant_ids"], sorted(payload_sizes))
        self.assertEqual(payload_range["latest"]["week_id"], current["week_id"])
        self.assertEqual(
            Decimal(payload_range["latest"]["minimum_index_level"]), minimum
        )
        self.assertEqual(
            Decimal(payload_range["latest"]["maximum_index_level"]), maximum
        )
        self.assertEqual(
            payload_range["latest"]["minimum_variant_ids"],
            sorted(
                variant_id
                for variant_id, value in latest_values.items()
                if value == minimum
            ),
        )
        self.assertEqual(
            payload_range["latest"]["maximum_variant_ids"],
            sorted(
                variant_id
                for variant_id, value in latest_values.items()
                if value == maximum
            ),
        )
        with localcontext() as context:
            context.prec = 50
            expected_range = maximum - minimum
        self.assertEqual(
            Decimal(payload_range["latest"]["range_index_points"]),
            expected_range,
        )


if __name__ == "__main__":
    unittest.main()
