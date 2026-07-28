#!/usr/bin/env python3
"""Build the canonical, offline-only KAPI synthetic hand-example bundle."""

from __future__ import annotations

import argparse
import hashlib
import itertools
import json
from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP, localcontext
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[2]
CONFIG_RELATIVE_PATH = Path("kapi/config/methodology-v0.2.0.json")
OUTPUT_RELATIVE_PATH = Path("kapi/fixtures/synthetic-hand-example-v1.json")
CONFIG_PATH = REPO_ROOT / CONFIG_RELATIVE_PATH
OUTPUT_PATH = REPO_ROOT / OUTPUT_RELATIVE_PATH

BASE_START = date(2026, 4, 3)
BASE_WEEK_COUNT = 13
CURRENT_DATE = BASE_START + timedelta(weeks=BASE_WEEK_COUNT)
LETTERS = ("a", "b", "c", "d")
CAPABILITY_SCORES = {
    "a": "150",
    "b": "145",
    "c": "140",
    "d": "138",
}
BASE_PRICES = {
    "a": {"input": "2", "output": "8"},
    "b": {"input": "1", "output": "12"},
    "c": {"input": "3", "output": "6"},
    "d": {"input": "0.8", "output": "20"},
}
CURRENT_PRICES = {
    "a": {"input": "1", "output": "5"},
    "b": {"input": "0.5", "output": "6"},
    "c": {"input": "2.5", "output": "5"},
    "d": {"input": "0.4", "output": "10"},
}
ALL_FEATURES = [
    "function_calling",
    "structured_output",
    "text_input",
    "text_output",
]
CONSTRUCTION_TOKENIZER = "tiktoken-0.13.0:o200k_base(explicit_construction)"
BILLING_TOKENIZER = "provider-billing-counts-unverified"


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


def sha256_path(path: Path) -> str:
    return sha256_bytes(path.read_bytes())


def load_canonical_json(path: Path) -> dict[str, Any]:
    raw = path.read_bytes()
    value = json.loads(raw)
    if raw != canonical_bytes(value):
        raise ValueError(f"{path} is not canonical JSON")
    return value


def decimal_text(value: Decimal) -> str:
    text = format(value, "f")
    if "." in text:
        text = text.rstrip("0").rstrip(".")
    return text or "0"


def rounded_decimal_text(value: Decimal, places: int) -> str:
    quantum = Decimal(1).scaleb(-places)
    return format(value.quantize(quantum, rounding=ROUND_HALF_UP), "f")


def fixed_precision_text(value: Decimal, places: int = 28) -> str:
    quantum = Decimal(1).scaleb(-places)
    with localcontext() as context:
        context.prec = max(60, places + 32)
        return format(value.quantize(quantum, rounding=ROUND_HALF_UP), "f")


def iso_cutoff(day: date) -> str:
    return f"{day.isoformat()}T16:00:00Z"


def iso_effective(day: date) -> str:
    return f"{day.isoformat()}T00:00:00Z"


def make_source_artifact(
    *,
    source_id: str,
    retrieved_at: str,
    synthetic_content: dict[str, Any],
    url: str,
) -> dict[str, Any]:
    content_hash = sha256_bytes(canonical_bytes(synthetic_content))
    return {
        "content_sha256": content_hash,
        "evidence_grade": "A",
        "id": source_id,
        "license_note": (
            "Synthetic fixture evidence created locally for validation; "
            "not a real provider or benchmark source."
        ),
        "media_type": "application/json",
        "retrieved_at": retrieved_at,
        "snapshot_path": f"embedded://source_artifacts/{source_id}",
        "synthetic_content": synthetic_content,
        "url": url,
    }


def verify_methodology_and_payloads(
    methodology: dict[str, Any],
) -> tuple[str, dict[tuple[str, str, str], dict[str, Any]]]:
    if methodology["version"] != "0.2.0":
        raise ValueError("unexpected methodology version")
    if methodology["capability"].get("metric") != "ECI":
        raise ValueError("capability policy does not match the pinned fixture")
    if methodology["capability"].get("configuration_specific_score_allowed") is not False:
        raise ValueError("ECI must remain a coarse screen, not a configuration score")
    if methodology["base_period_weeks"] != BASE_WEEK_COUNT:
        raise ValueError("base period must contain thirteen weeks")
    if methodology["reference_tokenizer"].get("id") != "o200k_base":
        raise ValueError("reference tokenizer metadata is not explicit")
    if methodology["construction_reference"]["counts_verified"] is not True:
        raise ValueError("v0.2.0 requires local construction counts")
    evidence_classes = methodology.get("evidence_classes", {})
    if (
        evidence_classes.get("construction_counts", {}).get("status")
        != "verified_local_reference_only"
        or evidence_classes.get("provider_preflight_request_counts", {}).get("status")
        != "unverified_no_provider_call"
        or evidence_classes.get("billed_usage_counts", {}).get("status")
        != "unverified_no_billing_or_provider_call"
    ):
        raise ValueError("construction, preflight, and billed counts must stay separate")
    if methodology["endpoint_specific_billing_counts"]["verified_billing_rows"] != 0:
        raise ValueError("provider billing-count rows must remain unverified")

    grid = methodology["sensitivities"]["payload_size_grid"]
    expected_cells = {
        (input_factor, output_factor)
        for input_factor in ("0.75", "1.00", "1.25")
        for output_factor in ("0.75", "1.00", "1.25")
    }
    actual_cells = {
        (cell["input_factor"], cell["output_factor"]) for cell in grid
    }
    if len(grid) != 9 or actual_cells != expected_cells:
        raise ValueError("payload size sensitivity must be the full 3x3 grid")

    payload_index: dict[tuple[str, str, str], dict[str, Any]] = {}
    for profile in methodology["profiles"]:
        if profile["count"] != 10 or profile["weight"] != {
            "denominator": 6,
            "numerator": 1,
        }:
            raise ValueError(f"invalid headline weight for {profile['id']}")
        for direction in ("input", "output"):
            for payload in profile["payloads"][direction]:
                relative_path = Path(payload["path"])
                absolute_path = REPO_ROOT / relative_path
                if sha256_path(absolute_path) != payload["sha256"]:
                    raise ValueError(f"payload hash mismatch: {relative_path}")
                document = load_canonical_json(absolute_path)
                if document["o200k_base_count_verified"] is not True:
                    raise ValueError(f"payload lacks local construction count: {relative_path}")
                if document["construction_count_tolerance_tokens"] != 0:
                    raise ValueError(f"payload tolerance must be zero: {relative_path}")
                if (
                    document["construction_token_count"]
                    != payload["construction_token_count"]
                ):
                    raise ValueError(f"payload construction count mismatch: {relative_path}")
                if (
                    document["reference_token_design_target"]
                    != payload["reference_token_design_target"]
                ):
                    raise ValueError(f"payload target mismatch: {relative_path}")
                if (
                    document["content"]
                    != document["single_token_chunk"] * document["construction_token_count"]
                ):
                    raise ValueError(f"payload content is not deterministic: {relative_path}")
                payload_index[
                    (profile["id"], direction, payload["size_factor"])
                ] = payload
        headline_input = payload_index[(profile["id"], "input", "1.00")]
        headline_output = payload_index[(profile["id"], "output", "1.00")]
        if (
            profile["input_payload_path"] != headline_input["path"]
            or profile["input_payload_sha256"] != headline_input["sha256"]
            or profile["output_payload_path"] != headline_output["path"]
            or profile["output_payload_sha256"] != headline_output["sha256"]
        ):
            raise ValueError(f"flat headline payload fields drifted for {profile['id']}")

    return sha256_path(CONFIG_PATH), payload_index


def build_identities() -> tuple[
    list[dict[str, Any]],
    list[dict[str, Any]],
    list[dict[str, Any]],
    list[dict[str, Any]],
]:
    providers: list[dict[str, Any]] = []
    creators: list[dict[str, Any]] = []
    models: list[dict[str, Any]] = []
    endpoints: list[dict[str, Any]] = []
    for letter in LETTERS:
        upper = letter.upper()
        provider_id = f"provider-{letter}"
        creator_id = f"creator-{letter}"
        model_id = f"model-{letter}-v1"
        endpoint_id = f"endpoint-{letter}-v1"
        configuration_id = f"config-{letter}-reasoning-disabled"
        providers.append(
            {
                "id": provider_id,
                "name": f"Synthetic Provider {upper}",
                "synthetic": True,
            }
        )
        creators.append(
            {
                "id": creator_id,
                "name": f"Synthetic Creator {upper}",
                "synthetic": True,
            }
        )
        models.append(
            {
                "alias_type": "immutable",
                "creator_id": creator_id,
                "id": model_id,
                "immutable_version": True,
                "version": f"synthetic-{upper}-1.0.0",
            }
        )
        endpoints.append(
            {
                "available_from": "2026-03-01T00:00:00Z",
                "available_until": None,
                "available_us": True,
                "billing_tokenizer": BILLING_TOKENIZER,
                "construction_tokenizer": CONSTRUCTION_TOKENIZER,
                "configuration_id": configuration_id,
                "features": list(ALL_FEATURES),
                "ga": True,
                "id": endpoint_id,
                "model_id": model_id,
                "on_demand": True,
                "provider_id": provider_id,
                "public": True,
                "reasoning_mode": "disabled",
                "region": "US",
                "standard_list_price": True,
                "synchronous": True,
                "tier": "standard",
                "tokenizer_reproducible": True,
            }
        )
    return providers, creators, models, endpoints


def build_weeks() -> tuple[list[dict[str, str]], list[str], str]:
    base_days = [
        BASE_START + timedelta(weeks=offset)
        for offset in range(BASE_WEEK_COUNT)
    ]
    current_day = CURRENT_DATE
    all_days = [*base_days, current_day]
    weeks = [
        {
            "cutoff_at": iso_cutoff(day),
            "id": f"week-{day.isoformat()}",
        }
        for day in all_days
    ]
    return (
        weeks,
        [f"week-{day.isoformat()}" for day in base_days],
        f"week-{current_day.isoformat()}",
    )


def build_capability_evidence(
    endpoints: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    source_artifacts: list[dict[str, Any]] = []
    evidence: list[dict[str, Any]] = []
    evaluated_at = "2026-04-02T16:00:00Z"
    for endpoint in endpoints:
        letter = endpoint["id"].split("-")[1]
        source_id = f"source-capability-{letter}"
        score = CAPABILITY_SCORES[letter]
        source_content = {
            "capability_scope": "model_level_or_best_across_settings_coarse_screen",
            "configuration_id": endpoint["configuration_id"],
            "dataset_kind": "synthetic",
            "endpoint_id": endpoint["id"],
            "metric": "ECI",
            "metric_version": "synthetic-eci-v1",
            "model_calls_performed": 0,
            "network_access_used": False,
            "score": score,
            "score_is_configuration_specific": False,
        }
        source_artifacts.append(
            make_source_artifact(
                source_id=source_id,
                retrieved_at=evaluated_at,
                synthetic_content=source_content,
                url=f"synthetic://capability/{endpoint['id']}",
            )
        )
        evidence.append(
            {
                "configuration_id": endpoint["configuration_id"],
                "data_vintage": "synthetic-eci-2026-04-02",
                "endpoint_id": endpoint["id"],
                "evaluated_at": evaluated_at,
                "evidence_grade": "A",
                "id": f"capability-{letter}",
                "metric": "ECI",
                "metric_version": "synthetic-eci-v1",
                "model_id": endpoint["model_id"],
                "score": score,
                "score_is_configuration_specific": False,
                "source_id": source_id,
            }
        )
    return source_artifacts, evidence


def build_prices(
    weeks: list[dict[str, str]],
    endpoints: list[dict[str, Any]],
    current_week_id: str,
) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    source_artifacts: list[dict[str, Any]] = []
    observations: list[dict[str, Any]] = []
    for week in weeks:
        is_current = week["id"] == current_week_id
        regime = "current" if is_current else "base"
        prices = CURRENT_PRICES if is_current else BASE_PRICES
        effective_day = CURRENT_DATE if is_current else BASE_START
        for endpoint in endpoints:
            letter = endpoint["id"].split("-")[1]
            source_id = f"source-price-{week['id'][5:]}-{letter}"
            source_content = {
                "currency": "USD",
                "dataset_kind": "synthetic",
                "endpoint_id": endpoint["id"],
                "input_amount_per_million": prices[letter]["input"],
                "model_calls_performed": 0,
                "network_access_used": False,
                "output_amount_per_million": prices[letter]["output"],
                "regime": regime,
                "tier": "standard",
                "unit": "per_million_native_tokens",
                "week_id": week["id"],
            }
            source_artifacts.append(
                make_source_artifact(
                    source_id=source_id,
                    retrieved_at=week["cutoff_at"],
                    synthetic_content=source_content,
                    url=f"synthetic://prices/{endpoint['id']}/{week['id']}",
                )
            )
            for component in ("input", "output"):
                observations.append(
                    {
                        "amount_per_million": prices[letter][component],
                        "component": component,
                        "context_max_tokens": 100000,
                        "context_min_tokens": 0,
                        "currency": "USD",
                        "effective_at": iso_effective(effective_day),
                        "endpoint_id": endpoint["id"],
                        "evidence_grade": "A",
                        "id": (
                            f"price-{week['id'][5:]}-{letter}-{component}"
                        ),
                        "observed_at": week["cutoff_at"],
                        "region": "US",
                        "source_id": source_id,
                        "tier": "standard",
                        "unit": "per_million_native_tokens",
                        "week_id": week["id"],
                    }
                )
    return source_artifacts, observations


def build_token_counts(
    methodology: dict[str, Any],
    endpoints: list[dict[str, Any]],
    payload_index: dict[tuple[str, str, str], dict[str, Any]],
) -> list[dict[str, Any]]:
    records: list[dict[str, Any]] = []
    for endpoint in endpoints:
        for profile in methodology["profiles"]:
            profile_id = profile["id"]
            for cell in methodology["sensitivities"]["payload_size_grid"]:
                input_payload = payload_index[
                    (profile_id, "input", cell["input_factor"])
                ]
                output_payload = payload_index[
                    (profile_id, "output", cell["output_factor"])
                ]
                records.append(
                    {
                        "billing_tokenizer": BILLING_TOKENIZER,
                        "billing_usage_count_status": "unverified_no_provider_call",
                        "construction_count_evidence_class": "construction_count",
                        "construction_tokenizer": CONSTRUCTION_TOKENIZER,
                        "endpoint_id": endpoint["id"],
                        "id": (
                            f"tokens-{endpoint['id']}-{profile_id}-{cell['id']}"
                        ),
                        "input_payload_path": input_payload["path"],
                        "input_payload_sha256": input_payload["sha256"],
                        "input_tokens": input_payload["construction_token_count"],
                        "output_payload_path": output_payload["path"],
                        "output_payload_sha256": output_payload["sha256"],
                        "output_tokens": output_payload["construction_token_count"],
                        "profile_id": profile_id,
                        "size_variant": cell["id"],
                        "synthetic_count_note": (
                            "Exact local construction count under explicit o200k_base "
                            "chunk construction; not a provider preflight count, not "
                            "billing usage, and not obtained from a model call."
                        ),
                    }
                )
    return records


def select_profile_price(
    endpoint_costs: dict[str, Decimal],
) -> tuple[list[str], str, Decimal]:
    candidates: list[
        tuple[tuple[Decimal, Decimal, tuple[str, ...]], list[str], str, Decimal]
    ] = []
    for endpoint_ids in itertools.combinations(sorted(endpoint_costs), 3):
        ordered = sorted(
            endpoint_ids,
            key=lambda endpoint_id: (endpoint_costs[endpoint_id], endpoint_id),
        )
        median_endpoint = ordered[1]
        median_cost = endpoint_costs[median_endpoint]
        total_cost = sum(
            (endpoint_costs[endpoint_id] for endpoint_id in endpoint_ids),
            Decimal("0"),
        )
        key = (median_cost, total_cost, tuple(sorted(endpoint_ids)))
        candidates.append((key, ordered, median_endpoint, median_cost))
    _, selected, setter, selected_price = min(candidates, key=lambda row: row[0])
    return selected, setter, selected_price


def calculate_expected_regime(
    methodology: dict[str, Any],
    prices: dict[str, dict[str, str]],
) -> dict[str, Any]:
    profile_results: dict[str, Any] = {}
    setter_values: dict[str, Decimal] = {}
    setter_counts: dict[str, int] = {}
    total_profile_price = Decimal("0")
    for profile in methodology["profiles"]:
        profile_id = profile["id"]
        input_tokens = Decimal(str(profile["input_target_tokens"]))
        output_tokens = Decimal(str(profile["output_target_tokens"]))
        endpoint_costs: dict[str, Decimal] = {}
        for letter in LETTERS:
            endpoint_id = f"endpoint-{letter}-v1"
            endpoint_costs[endpoint_id] = (
                input_tokens * Decimal(prices[letter]["input"])
                + output_tokens * Decimal(prices[letter]["output"])
            ) / Decimal("1000000")
        selected, setter, selected_price = select_profile_price(endpoint_costs)
        setter_values[setter] = setter_values.get(setter, Decimal("0")) + (
            selected_price
        )
        setter_counts[setter] = setter_counts.get(setter, 0) + 1
        total_profile_price += selected_price
        profile_results[profile_id] = {
            "endpoint_costs": {
                endpoint_id: decimal_text(cost)
                for endpoint_id, cost in sorted(endpoint_costs.items())
            },
            "price_setter_endpoint_id": setter,
            "selected_price": decimal_text(selected_price),
            "selected_triple_in_cost_order": selected,
        }

    representative_cost = total_profile_price / Decimal("6")
    basket_cost = total_profile_price * Decimal("10")
    provider_shares: dict[str, str] = {}
    profile_counts: dict[str, int] = {}
    warning = False
    withhold = False
    warning_share = Decimal(methodology["concentration"]["warning_share"])
    withhold_share = Decimal(methodology["concentration"]["withhold_share"])
    for endpoint_id in sorted(set(setter_values) | set(setter_counts)):
        provider_id = endpoint_id.replace("endpoint", "provider").replace("-v1", "")
        share = setter_values.get(endpoint_id, Decimal("0")) / total_profile_price
        count = setter_counts.get(endpoint_id, 0)
        provider_shares[provider_id] = fixed_precision_text(share)
        profile_counts[provider_id] = count
        warning = warning or (
            share > warning_share
            or count >= methodology["concentration"]["warning_profile_count"]
        )
        withhold = withhold or (
            share > withhold_share
            or count >= methodology["concentration"]["withhold_profile_count"]
        )

    return {
        "concentration": {
            "creator_profile_counts": {
                key.replace("provider", "creator"): value
                for key, value in profile_counts.items()
            },
            "creator_shares": {
                key.replace("provider", "creator"): value
                for key, value in provider_shares.items()
            },
            "provider_profile_counts": profile_counts,
            "provider_shares": provider_shares,
            "warning": warning,
            "withhold": withhold,
        },
        "profile_results": profile_results,
        "representative_profile_cost": fixed_precision_text(representative_cost),
        "sixty_profile_basket_cost": decimal_text(basket_cost),
        "sum_of_six_profile_prices": decimal_text(total_profile_price),
    }


def build_expected_result(methodology: dict[str, Any]) -> dict[str, Any]:
    with localcontext() as context:
        context.prec = 60
        base = calculate_expected_regime(methodology, BASE_PRICES)
        current = calculate_expected_regime(methodology, CURRENT_PRICES)
        base_sum = Decimal(base["sum_of_six_profile_prices"])
        current_sum = Decimal(current["sum_of_six_profile_prices"])
        diagnostic_index = Decimal("100") * current_sum / base_sum
    all_weeks_withheld = (
        base["concentration"]["withhold"]
        and current["concentration"]["withhold"]
    )
    return {
        "concentration": {
            "base": base["concentration"],
            "current": current["concentration"],
        },
        "diagnostic_hand_calculation": {
            "base_profile_results": base["profile_results"],
            "base_representative_profile_cost": base[
                "representative_profile_cost"
            ],
            "base_sixty_profile_basket_cost": base[
                "sixty_profile_basket_cost"
            ],
            "current_index_display": rounded_decimal_text(
                diagnostic_index, places=1
            ),
            "current_index_full_precision": fixed_precision_text(
                diagnostic_index
            ),
            "current_profile_results": current["profile_results"],
            "current_representative_profile_cost": current[
                "representative_profile_cost"
            ],
            "current_sixty_profile_basket_cost": current[
                "sixty_profile_basket_cost"
            ],
            "note": (
                "The diagnostic calculation is retained for the mandated hand "
                "example even though the strict release is withheld."
            ),
        },
        "strict_headline": {
            "all_weeks_withheld": all_weeks_withheld,
            "base_period_usable": False,
            "reason": "concentration_withhold_threshold_breached",
            "status": "withheld_concentration",
        },
    }


def build_bundle() -> dict[str, Any]:
    methodology = load_canonical_json(CONFIG_PATH)
    config_sha256, payload_index = verify_methodology_and_payloads(methodology)
    providers, creators, models, endpoints = build_identities()
    weeks, base_week_ids, current_week_id = build_weeks()
    capability_sources, capability_evidence = build_capability_evidence(
        endpoints
    )
    price_sources, price_observations = build_prices(
        weeks, endpoints, current_week_id
    )
    token_counts = build_token_counts(methodology, endpoints, payload_index)
    return {
        "base_period_week_ids": base_week_ids,
        "capability_evidence": sorted(
            capability_evidence, key=lambda row: row["id"]
        ),
        "corrections": [],
        "creators": sorted(creators, key=lambda row: row["id"]),
        "current_week_id": current_week_id,
        "dataset_id": "synthetic-hand-example-v1",
        "dataset_kind": "synthetic",
        "endpoints": sorted(endpoints, key=lambda row: row["id"]),
        "expected_result": build_expected_result(methodology),
        "generation": {
            "external_dependencies": [],
            "generator_path": "kapi/fixtures/build_synthetic.py",
            "model_calls_performed": 0,
            "network_access_used": False,
            "paid_calls_performed": 0,
            "total_external_spend_usd": "0",
        },
        "methodology": {
            "config_path": CONFIG_RELATIVE_PATH.as_posix(),
            "config_sha256": config_sha256,
            "id": methodology["methodology_id"],
            "version": methodology["version"],
        },
        "models": sorted(models, key=lambda row: row["id"]),
        "price_observations": sorted(
            price_observations, key=lambda row: row["id"]
        ),
        "providers": sorted(providers, key=lambda row: row["id"]),
        "schema_version": "kapi-bundle-v0.1.0",
        "source_artifacts": sorted(
            [*capability_sources, *price_sources],
            key=lambda row: row["id"],
        ),
        "token_counts": sorted(token_counts, key=lambda row: row["id"]),
        "weeks": weeks,
    }


def build_bytes() -> bytes:
    return canonical_bytes(build_bundle())


def write_bundle(path: Path = OUTPUT_PATH) -> bytes:
    data = build_bytes()
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(data)
    return data


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Build the deterministic offline KAPI synthetic bundle."
    )
    parser.add_argument(
        "--check",
        action="store_true",
        help="compare generated canonical bytes with the committed fixture",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=OUTPUT_PATH,
        help="output path; defaults to the committed synthetic fixture",
    )
    args = parser.parse_args()

    generated = build_bytes()
    output = args.output
    if not output.is_absolute():
        output = REPO_ROOT / output
    if args.check:
        if not output.exists() or output.read_bytes() != generated:
            return 1
        return 0
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_bytes(generated)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
