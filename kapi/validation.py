"""Strict structural and cross-reference validation for KAPI prototype inputs."""

from __future__ import annotations

import re
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass
from datetime import timedelta
from pathlib import Path
from typing import Any, Iterable, Mapping
from urllib.parse import urlsplit

from .util import (
    canonical_json_bytes,
    parse_decimal,
    parse_utc,
    rational_decimal,
    sha256_bytes,
    sha256_file,
)


SHA256_PATTERN = re.compile(r"^[0-9a-f]{64}$")
ID_PATTERN = re.compile(r"^[a-zA-Z0-9][a-zA-Z0-9._:-]*$")
GRADES = {"A", "B", "C", "D"}
PAYLOAD_FACTORS = ("0.75", "1.00", "1.25")
PAYLOAD_FACTOR_IDS = {"0.75": "075", "1.00": "100", "1.25": "125"}


@dataclass(frozen=True)
class ValidationIssue:
    severity: str
    code: str
    path: str
    message: str


class ValidationError(ValueError):
    """Raised when validated inputs contain one or more errors."""

    def __init__(self, report: Mapping[str, Any]):
        self.report = dict(report)
        errors = self.report.get("errors", [])
        super().__init__(f"KAPI input validation failed with {len(errors)} error(s)")


class _Collector:
    def __init__(self) -> None:
        self.issues: list[ValidationIssue] = []

    def error(self, code: str, path: str, message: str) -> None:
        self.issues.append(ValidationIssue("error", code, path, message))

    def warning(self, code: str, path: str, message: str) -> None:
        self.issues.append(ValidationIssue("warning", code, path, message))

    def check(self, condition: bool, code: str, path: str, message: str) -> None:
        if not condition:
            self.error(code, path, message)


def _records(
    collector: _Collector, document: Mapping[str, Any], name: str
) -> list[Mapping[str, Any]]:
    value = document.get(name)
    if not isinstance(value, list):
        collector.error("required_array", name, f"{name} must be an array")
        return []
    records: list[Mapping[str, Any]] = []
    for index, record in enumerate(value):
        if not isinstance(record, Mapping):
            collector.error(
                "record_type", f"{name}[{index}]", "record must be an object"
            )
        else:
            records.append(record)
    return records


def _index_records(
    collector: _Collector,
    records: Iterable[Mapping[str, Any]],
    name: str,
    *,
    key: str = "id",
) -> dict[str, Mapping[str, Any]]:
    result: dict[str, Mapping[str, Any]] = {}
    for index, record in enumerate(records):
        value = record.get(key)
        path = f"{name}[{index}].{key}"
        if not isinstance(value, str) or not ID_PATTERN.match(value):
            collector.error("invalid_id", path, "must be a stable nonempty string ID")
            continue
        if value in result:
            collector.error("duplicate_id", path, f"duplicate {name} ID {value!r}")
        else:
            result[value] = record
    return result


def _require_reference(
    collector: _Collector,
    record: Mapping[str, Any],
    field: str,
    index: Mapping[str, Any],
    path: str,
) -> None:
    value = record.get(field)
    if value not in index:
        collector.error(
            "unknown_reference",
            f"{path}.{field}",
            f"{value!r} does not reference a known record",
        )


def _validate_sha(
    collector: _Collector, value: Any, path: str, *, allow_empty: bool = False
) -> None:
    if allow_empty and value in (None, ""):
        return
    if not isinstance(value, str) or not SHA256_PATTERN.match(value):
        collector.error("invalid_sha256", path, "must be a lowercase SHA-256 hex value")


def _validate_file_hash(
    collector: _Collector,
    root: Path,
    relative: Any,
    digest: Any,
    *,
    path_prefix: str,
) -> None:
    if not isinstance(relative, str) or not relative:
        collector.error("payload_path", f"{path_prefix}.path", "path is required")
        return
    candidate = (root / relative).resolve()
    try:
        candidate.relative_to(root)
    except ValueError:
        collector.error(
            "payload_path",
            f"{path_prefix}.path",
            "payload must remain inside repository root",
        )
        return
    _validate_sha(collector, digest, f"{path_prefix}.sha256")
    if not candidate.is_file():
        collector.error(
            "payload_missing",
            f"{path_prefix}.path",
            f"file does not exist: {relative}",
        )
    elif isinstance(digest, str) and SHA256_PATTERN.match(digest):
        collector.check(
            sha256_file(candidate) == digest,
            "payload_hash",
            f"{path_prefix}.sha256",
            "does not match frozen payload bytes",
        )


def _contains_cycle(edges: Mapping[str, str]) -> bool:
    for start in edges:
        seen: set[str] = set()
        node = start
        while node in edges:
            if node in seen:
                return True
            seen.add(node)
            node = edges[node]
    return False


def _price_identity(record: Mapping[str, Any]) -> tuple[Any, ...]:
    return (
        record.get("endpoint_id"),
        record.get("week_id"),
        record.get("component"),
        record.get("currency"),
        record.get("unit"),
        record.get("region"),
        record.get("tier"),
        record.get("context_min_tokens"),
        record.get("context_max_tokens"),
        record.get("effective_at"),
    )


def validate_methodology(
    methodology: Mapping[str, Any], *, repository_root: str | Path
) -> dict[str, Any]:
    collector = _Collector()
    root = Path(repository_root).resolve()

    for field in ("methodology_id", "version", "claim", "base_period_weeks"):
        if field not in methodology:
            collector.error("required_field", field, f"{field} is required")

    collector.check(
        methodology.get("base_period_weeks") == 13,
        "base_period",
        "base_period_weeks",
        "approved prototype requires 13 base weeks",
    )

    capability = methodology.get("capability", {})
    if not isinstance(capability, Mapping):
        collector.error("object_type", "capability", "capability must be an object")
        capability = {}
    collector.check(
        capability.get("metric") == "ECI",
        "capability_metric",
        "capability.metric",
        "approved capability metric is ECI",
    )
    try:
        threshold = parse_decimal(
            capability.get("headline_threshold"),
            field="capability.headline_threshold",
        )
        collector.check(
            threshold == 130,
            "capability_threshold",
            "capability.headline_threshold",
            "approved headline threshold is 130",
        )
    except ValueError as error:
        collector.error("invalid_decimal", "capability.headline_threshold", str(error))
    try:
        sensitivity_thresholds = {
            parse_decimal(value, field="capability.sensitivity_thresholds")
            for value in capability.get("sensitivity_thresholds", [])
        }
        collector.check(
            sensitivity_thresholds == {125, 135},
            "capability_sensitivities",
            "capability.sensitivity_thresholds",
            "approved sensitivity thresholds are 125 and 135",
        )
    except ValueError as error:
        collector.error(
            "invalid_decimal", "capability.sensitivity_thresholds", str(error)
        )

    evidence = methodology.get("evidence_policy", {})
    collector.check(
        isinstance(evidence, Mapping),
        "object_type",
        "evidence_policy",
        "evidence_policy must be an object",
    )
    if isinstance(evidence, Mapping):
        collector.check(
            evidence.get("official_grades") == ["A"],
            "official_grade",
            "evidence_policy.official_grades",
            "official calculations must use grade A only",
        )
        collector.check(
            set(evidence.get("research_grades", [])) == {"A", "B", "C"},
            "research_grades",
            "evidence_policy.research_grades",
            "research calculations may use A, B, and C",
        )
        collector.check(
            evidence.get("excluded_grades") == ["D"],
            "excluded_grades",
            "evidence_policy.excluded_grades",
            "grade D must be excluded",
        )

    reference = methodology.get("reference_tokenizer", {})
    collector.check(
        isinstance(reference, Mapping)
        and reference.get("id") == "o200k_base",
        "reference_tokenizer",
        "reference_tokenizer.id",
        "construction reference must be o200k_base",
    )

    concentration = methodology.get("concentration", {})
    if isinstance(concentration, Mapping):
        expected = {
            "warning_share": "0.35",
            "warning_profile_count": 3,
            "withhold_share": "0.50",
            "withhold_profile_count": 4,
        }
        for key, value in expected.items():
            collector.check(
                concentration.get(key) == value,
                "concentration_rule",
                f"concentration.{key}",
                f"approved value is {value!r}",
            )
    else:
        collector.error(
            "object_type", "concentration", "concentration must be an object"
        )

    profiles = _records(collector, methodology, "profiles")
    profile_index = _index_records(collector, profiles, "profiles")
    collector.check(
        len(profile_index) == 6,
        "profile_count",
        "profiles",
        "approved basket contains six profiles",
    )
    total_count = 0
    total_weight = 0
    for position, profile in enumerate(profiles):
        path = f"profiles[{position}]"
        count = profile.get("count")
        if not isinstance(count, int) or count <= 0:
            collector.error("profile_count", f"{path}.count", "must be a positive integer")
        else:
            total_count += count
        try:
            weight = rational_decimal(profile.get("weight", {}), field=f"{path}.weight")
            total_weight += weight
            collector.check(
                weight == rational_decimal(
                    {"numerator": 1, "denominator": 6}, field="approved weight"
                ),
                "profile_weight",
                f"{path}.weight",
                "beta headline uses equal 1/6 weights",
            )
        except ValueError as error:
            collector.error("profile_weight", f"{path}.weight", str(error))
        for target in ("input_target_tokens", "output_target_tokens"):
            collector.check(
                isinstance(profile.get(target), int) and profile.get(target, 0) > 0,
                "profile_target",
                f"{path}.{target}",
                "must be a positive integer construction target",
            )
        payloads = profile.get("payloads")
        if not isinstance(payloads, Mapping):
            collector.error(
                "payload_definitions", f"{path}.payloads", "must be an object"
            )
            payloads = {}
        for kind in ("input", "output"):
            relative = profile.get(f"{kind}_payload_path")
            digest = profile.get(f"{kind}_payload_sha256")
            _validate_file_hash(
                collector,
                root,
                relative,
                digest,
                path_prefix=f"{path}.{kind}_payload",
            )
            entries = payloads.get(kind, []) if isinstance(payloads, Mapping) else []
            if not isinstance(entries, list):
                collector.error(
                    "payload_definitions",
                    f"{path}.payloads.{kind}",
                    "must be an array",
                )
                continue
            by_factor: dict[str, Mapping[str, Any]] = {}
            for entry_index, entry in enumerate(entries):
                entry_path = f"{path}.payloads.{kind}[{entry_index}]"
                if not isinstance(entry, Mapping):
                    collector.error(
                        "payload_definitions", entry_path, "must be an object"
                    )
                    continue
                factor = entry.get("size_factor")
                collector.check(
                    isinstance(factor, str) and factor in PAYLOAD_FACTORS,
                    "payload_factor",
                    f"{entry_path}.size_factor",
                    "must be one of 0.75, 1.00, or 1.25",
                )
                if isinstance(factor, str):
                    if factor in by_factor:
                        collector.error(
                            "payload_factor",
                            f"{entry_path}.size_factor",
                            f"duplicate {kind} payload factor {factor}",
                        )
                    else:
                        by_factor[factor] = entry
                collector.check(
                    isinstance(entry.get("reference_token_design_target"), int)
                    and entry.get("reference_token_design_target", 0) > 0,
                    "profile_target",
                    f"{entry_path}.reference_token_design_target",
                    "must be a positive integer",
                )
                _validate_file_hash(
                    collector,
                    root,
                    entry.get("path"),
                    entry.get("sha256"),
                    path_prefix=entry_path,
                )
            collector.check(
                set(by_factor) == set(PAYLOAD_FACTORS),
                "payload_factor",
                f"{path}.payloads.{kind}",
                "must contain exactly one 0.75, 1.00, and 1.25 payload",
            )
            headline = by_factor.get("1.00", {})
            collector.check(
                headline.get("path") == relative
                and headline.get("sha256") == digest,
                "headline_payload",
                f"{path}.{kind}_payload",
                "headline path/hash must match the 1.00 payload definition",
            )
    collector.check(
        total_count == 60,
        "basket_count",
        "profiles",
        "approved fixed basket contains 60 profiles",
    )
    collector.check(
        total_weight == 1,
        "weight_sum",
        "profiles",
        "profile weights must sum exactly to one",
    )

    size_grid = (
        methodology.get("sensitivities", {})
        if isinstance(methodology.get("sensitivities"), Mapping)
        else {}
    ).get("payload_size_grid", [])
    if not isinstance(size_grid, list):
        collector.error(
            "payload_grid", "sensitivities.payload_size_grid", "must be an array"
        )
        size_grid = []
    normalized_grid: dict[str, tuple[str, str, bool]] = {}
    for position, item in enumerate(size_grid):
        path = f"sensitivities.payload_size_grid[{position}]"
        if not isinstance(item, Mapping):
            collector.error("payload_grid", path, "must be an object")
            continue
        variant_id = item.get("id")
        input_factor = item.get("input_factor")
        output_factor = item.get("output_factor")
        headline = item.get("headline")
        if not isinstance(variant_id, str) or not variant_id:
            collector.error("payload_grid", f"{path}.id", "stable ID is required")
            continue
        if variant_id in normalized_grid:
            collector.error("payload_grid", f"{path}.id", "duplicate grid ID")
        normalized_grid[variant_id] = (input_factor, output_factor, headline)
        valid_factors = (
            isinstance(input_factor, str)
            and input_factor in PAYLOAD_FACTORS
            and isinstance(output_factor, str)
            and output_factor in PAYLOAD_FACTORS
        )
        collector.check(
            valid_factors,
            "payload_grid",
            path,
            "factors must be exact strings from 0.75, 1.00, and 1.25",
        )
        if valid_factors:
            expected_id = (
                f"{PAYLOAD_FACTOR_IDS[input_factor]}x"
                f"{PAYLOAD_FACTOR_IDS[output_factor]}"
            )
            collector.check(
                variant_id == expected_id,
                "payload_grid",
                f"{path}.id",
                f"grid ID must be {expected_id}",
            )
            collector.check(
                headline is (expected_id == "100x100"),
                "payload_grid",
                f"{path}.headline",
                "only 100x100 may be the headline cell",
            )
    expected_grid = {
        f"{PAYLOAD_FACTOR_IDS[input_factor]}x{PAYLOAD_FACTOR_IDS[output_factor]}": (
            input_factor,
            output_factor,
            input_factor == "1.00" and output_factor == "1.00",
        )
        for input_factor in PAYLOAD_FACTORS
        for output_factor in PAYLOAD_FACTORS
    }
    collector.check(
        normalized_grid == expected_grid,
        "payload_grid",
        "sensitivities.payload_size_grid",
        "must contain the complete approved 3x3 factor grid",
    )

    return _report(
        collector,
        kind="methodology",
        document=methodology,
        stats={"profiles": len(profile_index), "basket_count": total_count},
    )


def validate_bundle(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    *,
    repository_root: str | Path,
) -> dict[str, Any]:
    collector = _Collector()
    root = Path(repository_root).resolve()
    methodology_report = validate_methodology(methodology, repository_root=root)
    for issue in methodology_report["errors"] + methodology_report["warnings"]:
        collector.issues.append(ValidationIssue(**issue))

    for field in ("schema_version", "dataset_id", "dataset_kind"):
        if field not in bundle:
            collector.error("required_field", field, f"{field} is required")
    collector.check(
        bundle.get("dataset_kind") in {"synthetic", "observed"},
        "dataset_kind",
        "dataset_kind",
        "must be synthetic or observed",
    )

    names = (
        "weeks",
        "providers",
        "creators",
        "models",
        "endpoints",
        "source_artifacts",
        "capability_evidence",
        "token_counts",
        "price_observations",
        "corrections",
    )
    arrays = {name: _records(collector, bundle, name) for name in names}
    indexes = {
        name: _index_records(collector, arrays[name], name)
        for name in (
            "weeks",
            "providers",
            "creators",
            "models",
            "endpoints",
            "source_artifacts",
            "capability_evidence",
            "token_counts",
            "price_observations",
            "corrections",
        )
    }

    week_times: list[tuple[str, Any]] = []
    for index, week in enumerate(arrays["weeks"]):
        try:
            cutoff = parse_utc(week.get("cutoff_at"), field=f"weeks[{index}].cutoff_at")
            week_times.append((str(week.get("id")), cutoff))
            collector.check(
                cutoff.weekday() == 4,
                "week_cutoff",
                f"weeks[{index}].cutoff_at",
                "weekly cutoff must be Friday",
            )
        except ValueError as error:
            collector.error("invalid_timestamp", f"weeks[{index}].cutoff_at", str(error))
    sorted_times = sorted(week_times, key=lambda item: item[1])
    collector.check(
        sorted_times == week_times,
        "week_order",
        "weeks",
        "weeks must be strictly ordered by cutoff",
    )
    for previous, current in zip(sorted_times, sorted_times[1:]):
        collector.check(
            current[1] - previous[1] == timedelta(days=7),
            "week_gap",
            f"weeks[{current[0]}]",
            "prototype weeks must be consecutive seven-day intervals",
        )

    for index, model in enumerate(arrays["models"]):
        path = f"models[{index}]"
        _require_reference(
            collector, model, "creator_id", indexes["creators"], path
        )
        collector.check(
            model.get("alias_type") in {"immutable", "resolved_immutable", "rolling"},
            "alias_type",
            f"{path}.alias_type",
            "must be immutable, resolved_immutable, or rolling",
        )
        collector.check(
            isinstance(model.get("immutable_version"), bool),
            "immutable_flag",
            f"{path}.immutable_version",
            "must be boolean",
        )

    endpoint_ids = indexes["endpoints"]
    raw_endpoint_flags = (
        methodology.get("eligibility", {}).get("required_endpoint_flags", [])
        if isinstance(methodology.get("eligibility"), Mapping)
        else []
    )
    if isinstance(raw_endpoint_flags, Mapping):
        required_endpoint_flags = dict(raw_endpoint_flags)
    elif isinstance(raw_endpoint_flags, list):
        required_endpoint_flags = {flag: True for flag in raw_endpoint_flags}
    else:
        required_endpoint_flags = {}
        collector.error(
            "endpoint_flags",
            "eligibility.required_endpoint_flags",
            "must be an object of expected booleans or an array of true flags",
        )
    for index, endpoint in enumerate(arrays["endpoints"]):
        path = f"endpoints[{index}]"
        _require_reference(
            collector, endpoint, "provider_id", indexes["providers"], path
        )
        _require_reference(collector, endpoint, "model_id", indexes["models"], path)
        for flag, expected in required_endpoint_flags.items():
            collector.check(
                isinstance(endpoint.get(flag), bool)
                and endpoint.get(flag) is expected,
                "endpoint_flag",
                f"{path}.{flag}",
                f"required eligibility flag must be {expected!r}",
            )
        collector.check(
            isinstance(endpoint.get("features"), list),
            "endpoint_features",
            f"{path}.features",
            "features must be an array",
        )
        collector.check(
            isinstance(endpoint.get("billing_tokenizer"), str)
            and bool(endpoint.get("billing_tokenizer")),
            "billing_tokenizer",
            f"{path}.billing_tokenizer",
            "billing tokenizer ID is required",
        )

    source_ids = indexes["source_artifacts"]
    for index, source in enumerate(arrays["source_artifacts"]):
        path = f"source_artifacts[{index}]"
        collector.check(
            source.get("evidence_grade") in GRADES,
            "evidence_grade",
            f"{path}.evidence_grade",
            "must be A, B, C, or D",
        )
        digest = source.get("content_sha256")
        _validate_sha(collector, digest, f"{path}.content_sha256")
        url = source.get("url")
        allowed_schemes = {"http", "https"}
        if bundle.get("dataset_kind") == "synthetic":
            allowed_schemes.add("synthetic")
        collector.check(
            isinstance(url, str)
            and bool(url)
            and urlsplit(url).scheme in allowed_schemes,
            "source_url",
            f"{path}.url",
            "must use HTTP(S), or synthetic: for a synthetic bundle",
        )
        collector.check(
            isinstance(source.get("media_type"), str)
            and bool(source.get("media_type")),
            "source_metadata",
            f"{path}.media_type",
            "media type is required",
        )
        collector.check(
            isinstance(source.get("license_note"), str),
            "source_metadata",
            f"{path}.license_note",
            "license note is required",
        )
        snapshot_path = source.get("snapshot_path")
        if not isinstance(snapshot_path, str) or not snapshot_path:
            collector.error(
                "source_snapshot", f"{path}.snapshot_path", "snapshot path is required"
            )
        elif snapshot_path.startswith("embedded://"):
            collector.check(
                bundle.get("dataset_kind") == "synthetic",
                "source_snapshot",
                f"{path}.snapshot_path",
                "embedded snapshots are allowed only in synthetic bundles",
            )
            collector.check(
                snapshot_path
                == f"embedded://source_artifacts/{source.get('id')}",
                "source_snapshot",
                f"{path}.snapshot_path",
                "embedded snapshot must identify its source artifact",
            )
            if "synthetic_content" not in source:
                collector.error(
                    "source_snapshot",
                    f"{path}.synthetic_content",
                    "embedded snapshot content is required",
                )
            elif isinstance(digest, str) and SHA256_PATTERN.match(digest):
                collector.check(
                    sha256_bytes(canonical_json_bytes(source.get("synthetic_content")))
                    == digest,
                    "source_content_hash",
                    f"{path}.content_sha256",
                    "does not match canonical embedded snapshot bytes",
                )
        else:
            candidate = (root / snapshot_path).resolve()
            try:
                candidate.relative_to(root)
            except ValueError:
                collector.error(
                    "source_snapshot",
                    f"{path}.snapshot_path",
                    "retained snapshot must remain inside repository root",
                )
            else:
                if not candidate.is_file():
                    collector.error(
                        "source_snapshot_missing",
                        f"{path}.snapshot_path",
                        f"retained snapshot does not exist: {snapshot_path}",
                    )
                elif isinstance(digest, str) and SHA256_PATTERN.match(digest):
                    collector.check(
                        sha256_file(candidate) == digest,
                        "source_content_hash",
                        f"{path}.content_sha256",
                        "does not match retained snapshot bytes",
                    )
        try:
            parse_utc(source.get("retrieved_at"), field=f"{path}.retrieved_at")
        except ValueError as error:
            collector.error("invalid_timestamp", f"{path}.retrieved_at", str(error))

    methodology_capability = methodology.get("capability", {})
    if not isinstance(methodology_capability, Mapping):
        methodology_capability = {}
    capability_ids = indexes["capability_evidence"]
    for index, capability in enumerate(arrays["capability_evidence"]):
        path = f"capability_evidence[{index}]"
        _require_reference(
            collector, capability, "model_id", indexes["models"], path
        )
        _require_reference(collector, capability, "endpoint_id", endpoint_ids, path)
        _require_reference(collector, capability, "source_id", source_ids, path)
        endpoint = endpoint_ids.get(capability.get("endpoint_id"), {})
        source = source_ids.get(capability.get("source_id"), {})
        collector.check(
            capability.get("model_id") == endpoint.get("model_id"),
            "capability_endpoint",
            f"{path}.model_id",
            "must match the endpoint model",
        )
        collector.check(
            capability.get("configuration_id")
            == endpoint.get("configuration_id"),
            "capability_endpoint",
            f"{path}.configuration_id",
            "must match the endpoint configuration",
        )
        collector.check(
            capability.get("metric") == methodology_capability.get("metric"),
            "capability_metric",
            f"{path}.metric",
            "must match the pinned methodology capability metric",
        )
        try:
            score = parse_decimal(capability.get("score"), field=f"{path}.score")
            collector.check(
                score >= 0, "capability_score", f"{path}.score", "must be nonnegative"
            )
        except ValueError as error:
            collector.error("invalid_decimal", f"{path}.score", str(error))
        collector.check(
            capability.get("evidence_grade") in GRADES,
            "evidence_grade",
            f"{path}.evidence_grade",
            "must be A, B, C, or D",
        )
        collector.check(
            capability.get("evidence_grade") == source.get("evidence_grade"),
            "source_grade",
            f"{path}.evidence_grade",
            "must match source artifact grade",
        )

    profile_index = {
        profile["id"]: profile
        for profile in methodology.get("profiles", [])
        if isinstance(profile, Mapping) and isinstance(profile.get("id"), str)
    }
    methodology_sensitivities = methodology.get("sensitivities", {})
    if not isinstance(methodology_sensitivities, Mapping):
        methodology_sensitivities = {}
    grid_by_id = {
        cell.get("id"): cell
        for cell in methodology_sensitivities.get("payload_size_grid", [])
        if isinstance(cell, Mapping) and isinstance(cell.get("id"), str)
    }
    canonical_payloads: dict[tuple[str, str], dict[str, Any]] = {}
    for profile_id, profile in profile_index.items():
        payloads = profile.get("payloads", {})
        if not isinstance(payloads, Mapping):
            continue
        side_by_factor: dict[str, dict[str, Mapping[str, Any]]] = {}
        for side in ("input", "output"):
            entries = payloads.get(side, [])
            side_by_factor[side] = {
                str(entry.get("size_factor")): entry
                for entry in entries
                if isinstance(entry, Mapping)
            } if isinstance(entries, list) else {}
        for variant_id, cell in grid_by_id.items():
            input_entry = side_by_factor["input"].get(str(cell.get("input_factor")))
            output_entry = side_by_factor["output"].get(str(cell.get("output_factor")))
            if input_entry is None or output_entry is None:
                continue
            canonical_payloads[(profile_id, variant_id)] = {
                "input_payload_sha256": input_entry.get("sha256"),
                "output_payload_sha256": output_entry.get("sha256"),
                "input_payload_path": input_entry.get("path"),
                "output_payload_path": output_entry.get("path"),
            }
    token_keys: set[tuple[str, str, str]] = set()
    for index, token_count in enumerate(arrays["token_counts"]):
        path = f"token_counts[{index}]"
        endpoint_id = token_count.get("endpoint_id")
        profile_id = token_count.get("profile_id")
        _require_reference(collector, token_count, "endpoint_id", endpoint_ids, path)
        if profile_id not in profile_index:
            collector.error(
                "unknown_reference",
                f"{path}.profile_id",
                f"{profile_id!r} does not reference a methodology profile",
            )
        variant = token_count.get("size_variant")
        key = (str(endpoint_id), str(profile_id), str(variant))
        if key in token_keys:
            collector.error("duplicate_token_count", path, f"duplicate token key {key}")
        token_keys.add(key)
        collector.check(
            isinstance(variant, str) and variant in grid_by_id,
            "payload_variant",
            f"{path}.size_variant",
            "must reference an approved payload-grid cell",
        )
        for field in ("input_tokens", "output_tokens"):
            collector.check(
                isinstance(token_count.get(field), int) and token_count.get(field, 0) > 0,
                "token_count",
                f"{path}.{field}",
                "must be a positive integer",
            )
        for field in ("input_payload_sha256", "output_payload_sha256"):
            _validate_sha(collector, token_count.get(field), f"{path}.{field}")
        endpoint = endpoint_ids.get(endpoint_id, {})
        collector.check(
            token_count.get("billing_tokenizer") == endpoint.get("billing_tokenizer"),
            "tokenizer_mismatch",
            f"{path}.billing_tokenizer",
            "must match endpoint billing tokenizer",
        )
        expected_payload = canonical_payloads.get((str(profile_id), str(variant)))
        if expected_payload is None:
            collector.error(
                "token_payload",
                path,
                "cannot resolve canonical methodology payloads for this profile/variant",
            )
        else:
            for field in (
                "input_payload_sha256",
                "output_payload_sha256",
                "input_payload_path",
                "output_payload_path",
            ):
                collector.check(
                    token_count.get(field) == expected_payload.get(field),
                    "token_payload",
                    f"{path}.{field}",
                    "must match the frozen methodology payload",
                )

    observation_ids = indexes["price_observations"]
    superseded_ids: set[str] = set()
    observation_supersession: dict[str, str] = {}
    observation_groups: defaultdict[tuple[Any, ...], list[Mapping[str, Any]]] = (
        defaultdict(list)
    )
    for index, observation in enumerate(arrays["price_observations"]):
        path = f"price_observations[{index}]"
        _require_reference(
            collector, observation, "endpoint_id", endpoint_ids, path
        )
        _require_reference(collector, observation, "week_id", indexes["weeks"], path)
        _require_reference(collector, observation, "source_id", source_ids, path)
        collector.check(
            observation.get("component") in {"input", "output", "cache_read", "cache_write"},
            "price_component",
            f"{path}.component",
            "unsupported price component",
        )
        try:
            amount = parse_decimal(
                observation.get("amount_per_million"),
                field=f"{path}.amount_per_million",
            )
            collector.check(
                amount >= 0,
                "price_amount",
                f"{path}.amount_per_million",
                "must be nonnegative",
            )
        except ValueError as error:
            collector.error("invalid_decimal", f"{path}.amount_per_million", str(error))
        collector.check(
            observation.get("evidence_grade") in GRADES,
            "evidence_grade",
            f"{path}.evidence_grade",
            "must be A, B, C, or D",
        )
        source = source_ids.get(observation.get("source_id"), {})
        collector.check(
            observation.get("evidence_grade") == source.get("evidence_grade"),
            "source_grade",
            f"{path}.evidence_grade",
            "must match source artifact grade",
        )
        for field in ("effective_at", "observed_at"):
            try:
                parse_utc(observation.get(field), field=f"{path}.{field}")
            except ValueError as error:
                collector.error("invalid_timestamp", f"{path}.{field}", str(error))
        for field in ("context_min_tokens", "context_max_tokens"):
            value = observation.get(field)
            if value is not None:
                collector.check(
                    isinstance(value, int) and value >= 0,
                    "context_tier",
                    f"{path}.{field}",
                    "must be null or a nonnegative integer",
                )
        if (
            isinstance(observation.get("context_min_tokens"), int)
            and isinstance(observation.get("context_max_tokens"), int)
        ):
            collector.check(
                observation["context_min_tokens"]
                <= observation["context_max_tokens"],
                "context_tier",
                path,
                "context minimum cannot exceed maximum",
            )
        supersedes = observation.get("supersedes_observation_id")
        if supersedes:
            if supersedes not in observation_ids:
                collector.error(
                    "unknown_supersession",
                    f"{path}.supersedes_observation_id",
                    "superseded observation does not exist",
                )
            elif supersedes == observation.get("id"):
                collector.error(
                    "self_supersession",
                    f"{path}.supersedes_observation_id",
                    "observation cannot supersede itself",
                )
            else:
                observation_supersession[str(observation.get("id"))] = str(
                    supersedes
                )
                superseded_ids.add(str(supersedes))
        group_key = _price_identity(observation)
        observation_groups[group_key].append(observation)

    collector.check(
        not _contains_cycle(observation_supersession),
        "supersession_cycle",
        "price_observations",
        "price observation supersession must be acyclic",
    )
    for group_key, observations in observation_groups.items():
        active = [
            observation
            for observation in observations
            if observation.get("id") not in superseded_ids
        ]
        if len(active) != 1:
            collector.error(
                "conflicting_observation",
                "price_observations",
                f"expected exactly one active observation, found {len(active)} "
                f"for key {group_key}",
            )
        for observation in observations:
            supersedes = observation.get("supersedes_observation_id")
            if supersedes and supersedes in observation_ids:
                previous = observation_ids[supersedes]
                previous_key = _price_identity(previous)
                collector.check(
                    previous_key == group_key,
                    "supersession_key",
                    f"price_observations[{observation.get('id')}]",
                    "supersession must preserve endpoint/week/component/tier key",
                )

    correction_ids = indexes["corrections"]
    correction_supersession: dict[str, str] = {}
    for index, correction in enumerate(arrays["corrections"]):
        path = f"corrections[{index}]"
        for field in ("superseded_observation_id", "replacement_observation_id"):
            _require_reference(collector, correction, field, observation_ids, path)
        collector.check(
            correction.get("superseded_observation_id")
            != correction.get("replacement_observation_id"),
            "correction_identity",
            path,
            "correction records must link two different observations",
        )
        correction_id = correction.get("id")
        prior_correction_id = correction.get("supersedes_correction_id")
        if prior_correction_id is not None:
            if prior_correction_id not in correction_ids:
                collector.error(
                    "unknown_supersession",
                    f"{path}.supersedes_correction_id",
                    "superseded correction does not exist",
                )
            elif prior_correction_id == correction_id:
                collector.error(
                    "self_supersession",
                    f"{path}.supersedes_correction_id",
                    "correction cannot supersede itself",
                )
            else:
                correction_supersession[str(correction_id)] = str(
                    prior_correction_id
                )
        superseded_id = correction.get("superseded_observation_id")
        replacement_id = correction.get("replacement_observation_id")
        superseded = observation_ids.get(superseded_id)
        replacement = observation_ids.get(replacement_id)
        if superseded is not None and replacement is not None:
            collector.check(
                _price_identity(superseded) == _price_identity(replacement),
                "correction_linkage",
                path,
                "correction observations must share one applicability identity",
            )
            collector.check(
                replacement.get("supersedes_observation_id") == superseded_id,
                "correction_linkage",
                f"{path}.replacement_observation_id",
                "replacement observation must explicitly supersede the target",
            )

    collector.check(
        not _contains_cycle(correction_supersession),
        "supersession_cycle",
        "corrections",
        "correction supersession must be acyclic",
    )
    correction_children = Counter(correction_supersession.values())
    collector.check(
        all(count == 1 for count in correction_children.values()),
        "branching_supersession",
        "corrections",
        "one correction cannot be superseded by multiple active branches",
    )

    return _report(
        collector,
        kind="bundle",
        document=bundle,
        stats={
            name: len(indexes.get(name, arrays[name]))
            for name in names
        },
    )


def validate_or_raise(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    *,
    repository_root: str | Path,
) -> dict[str, Any]:
    report = validate_bundle(bundle, methodology, repository_root=repository_root)
    if not report["valid"]:
        raise ValidationError(report)
    return report


def _report(
    collector: _Collector,
    *,
    kind: str,
    document: Mapping[str, Any],
    stats: Mapping[str, Any],
) -> dict[str, Any]:
    errors = [
        asdict(issue) for issue in collector.issues if issue.severity == "error"
    ]
    warnings = [
        asdict(issue) for issue in collector.issues if issue.severity == "warning"
    ]
    counts = Counter(issue.code for issue in collector.issues)
    return {
        "kind": kind,
        "valid": not errors,
        "document_sha256": sha256_bytes(canonical_json_bytes(document)),
        "errors": errors,
        "warnings": warnings,
        "issue_counts": dict(sorted(counts.items())),
        "stats": dict(stats),
    }
