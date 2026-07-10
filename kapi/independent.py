"""Independent arithmetic and selection checker for frozen KAPI calculations.

This implementation intentionally does not import the primary calculation
engine.  It recomputes selected triples, medians, basket costs and index levels
from the candidate ledger embedded in the frozen calculation artifact.
"""

from __future__ import annotations

from decimal import Decimal, InvalidOperation, localcontext
from itertools import combinations
from typing import Any, Mapping


class IndependentCheckError(ValueError):
    """Raised when the frozen calculation lacks independently checkable data."""


def _decimal(value: Any, label: str) -> Decimal:
    if isinstance(value, float) or isinstance(value, bool):
        raise IndependentCheckError(f"{label} is not an exact decimal")
    try:
        result = Decimal(str(value))
    except (InvalidOperation, ValueError) as error:
        raise IndependentCheckError(f"{label} is not an exact decimal") from error
    if not result.is_finite():
        raise IndependentCheckError(f"{label} is not finite")
    return result


def _text(value: Decimal) -> str:
    if value == 0:
        return "0"
    result = format(value, "f")
    return result.rstrip("0").rstrip(".") if "." in result else result


def _best_triple(candidates: list[Mapping[str, Any]]) -> tuple[list[str], Decimal, Decimal]:
    eligible = []
    for candidate in candidates:
        eligible.append(
            {
                "endpoint_id": str(candidate["endpoint_id"]),
                "provider_id": str(candidate["provider_id"]),
                "creator_id": str(candidate["creator_id"]),
                "cost": _decimal(candidate["cost"], "candidate.cost"),
            }
        )
    triples = []
    for triple in combinations(eligible, 3):
        if len({item["provider_id"] for item in triple}) != 3:
            continue
        if len({item["creator_id"] for item in triple}) != 3:
            continue
        ordered_ids = sorted(item["endpoint_id"] for item in triple)
        total = sum((item["cost"] for item in triple), Decimal(0))
        triples.append((total, ordered_ids, triple))
    if not triples:
        raise IndependentCheckError("candidate ledger has no independent triple")
    total, ordered_ids, triple = min(triples, key=lambda item: (item[0], item[1]))
    cost_order = sorted(triple, key=lambda item: (item["cost"], item["endpoint_id"]))
    return ordered_ids, total, cost_order[1]["cost"]


def _check_calculation(calculation: Mapping[str, Any]) -> dict[str, Any]:

    weeks = calculation.get("weeks")
    weights = calculation.get("weights")
    base = calculation.get("base_period")
    if not isinstance(weeks, list) or not isinstance(weights, Mapping) or not isinstance(base, Mapping):
        raise IndependentCheckError("calculation is missing weeks, weights, or base_period")
    discrepancies: list[dict[str, Any]] = []
    recomputed: dict[str, dict[str, Any]] = {}
    for week in weeks:
        week_id = str(week.get("week_id"))
        profiles = week.get("profiles")
        if not isinstance(profiles, list):
            raise IndependentCheckError(f"week {week_id} has no profile ledger")
        basket = Decimal(0)
        complete = True
        profile_prices: dict[str, Decimal] = {}
        for profile in profiles:
            profile_id = str(profile.get("profile_id"))
            if profile.get("calculation_status") != "complete":
                complete = False
                continue
            candidates = profile.get("eligible_candidates")
            if not isinstance(candidates, list):
                raise IndependentCheckError(
                    f"week {week_id} profile {profile_id} has no candidate ledger"
                )
            selected_ids, selected_total, median = _best_triple(candidates)
            recorded_ids = sorted(str(item) for item in profile.get("selected_triple_endpoint_ids", []))
            recorded_total = _decimal(
                profile.get("selected_total_cost"),
                f"week {week_id} profile {profile_id} selected_total_cost",
            )
            recorded_price = _decimal(
                profile.get("headline_price"),
                f"week {week_id} profile {profile_id} headline_price",
            )
            for field, expected, actual in (
                ("selected_triple_endpoint_ids", selected_ids, recorded_ids),
                ("selected_total_cost", _text(selected_total), _text(recorded_total)),
                ("headline_price", _text(median), _text(recorded_price)),
            ):
                if expected != actual:
                    discrepancies.append(
                        {
                            "week_id": week_id,
                            "profile_id": profile_id,
                            "field": field,
                            "expected": expected,
                            "actual": actual,
                        }
                    )
            weight = _decimal(weights.get(profile_id), f"weights.{profile_id}")
            basket += weight * median
            profile_prices[profile_id] = median
        recorded_basket = week.get("basket_unit_cost")
        if complete and recorded_basket is not None:
            actual_basket = _decimal(recorded_basket, f"week {week_id} basket_unit_cost")
            if basket != actual_basket:
                discrepancies.append(
                    {
                        "week_id": week_id,
                        "field": "basket_unit_cost",
                        "expected": _text(basket),
                        "actual": _text(actual_basket),
                    }
                )
        recomputed[week_id] = {
            "complete": complete,
            "basket": basket if complete else None,
            "profile_prices": profile_prices,
        }

    base_status = base.get("status")
    maximum_index_difference = Decimal(0)
    if base_status == "complete":
        base_ids = base.get("week_ids")
        if not isinstance(base_ids, list) or not base_ids:
            raise IndependentCheckError("complete base has no week IDs")
        base_baskets = []
        for week_id in base_ids:
            entry = recomputed.get(str(week_id))
            if not entry or not entry["complete"]:
                raise IndependentCheckError("base references an incomplete recomputation")
            base_baskets.append(entry["basket"])
        base_basket = sum(base_baskets, Decimal(0)) / Decimal(len(base_baskets))
        for week in weeks:
            week_id = str(week.get("week_id"))
            entry = recomputed[week_id]
            recorded = week.get("index_level")
            if not entry["complete"] or recorded is None:
                continue
            expected = Decimal(100) * entry["basket"] / base_basket
            actual = _decimal(recorded, f"week {week_id} index_level")
            difference = abs(expected - actual)
            maximum_index_difference = max(maximum_index_difference, difference)
            if difference > Decimal("0.01"):
                discrepancies.append(
                    {
                        "week_id": week_id,
                        "field": "index_level",
                        "expected": _text(expected),
                        "actual": _text(actual),
                        "difference": _text(difference),
                    }
                )
    return {
        "implementation": "kapi.independent.v1",
        "independent_of_primary_module": True,
        "status": "pass" if not discrepancies else "fail",
        "tolerance_index_points": "0.01",
        "maximum_index_difference": _text(maximum_index_difference),
        "checked_week_count": len(weeks),
        "discrepancies": discrepancies,
    }


def check_calculation(calculation: Mapping[str, Any]) -> dict[str, Any]:
    """Return a deterministic independent comparison report."""

    with localcontext() as context:
        context.prec = 50
        return _check_calculation(calculation)
