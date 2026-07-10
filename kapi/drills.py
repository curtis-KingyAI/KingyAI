"""Pure local drill helpers for synthetic readiness fixtures."""

from __future__ import annotations

from collections import defaultdict
from decimal import Decimal
from typing import Any, Mapping, Sequence


def detect_price_unit_jumps(
    observations: Sequence[Mapping[str, Any]], *, threshold_multiple: str = "5"
) -> list[dict[str, str]]:
    """Flag price changes large enough to require manual unit verification."""

    threshold = Decimal(threshold_multiple)
    grouped: dict[tuple[str, str], list[Mapping[str, Any]]] = defaultdict(list)
    for observation in observations:
        grouped[
            (str(observation.get("endpoint_id")), str(observation.get("component")))
        ].append(observation)
    findings = []
    for identity, records in sorted(grouped.items()):
        ordered = sorted(
            records,
            key=lambda item: (
                str(item.get("effective_at", "")),
                str(item.get("observed_at", "")),
                str(item.get("id", "")),
            ),
        )
        for previous, current in zip(ordered, ordered[1:]):
            before = Decimal(str(previous["amount_per_million"]))
            after = Decimal(str(current["amount_per_million"]))
            if before == 0 or after == 0:
                multiple = Decimal("Infinity") if before != after else Decimal(1)
            else:
                multiple = max(before / after, after / before)
            if multiple >= threshold:
                findings.append(
                    {
                        "endpoint_id": identity[0],
                        "component": identity[1],
                        "previous_observation_id": str(previous.get("id")),
                        "current_observation_id": str(current.get("id")),
                        "multiple": str(multiple),
                        "disposition": "hold_for_manual_unit_and_source_review",
                    }
                )
    return findings
