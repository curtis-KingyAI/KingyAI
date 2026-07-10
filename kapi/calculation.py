"""Deterministic calculation engine for the isolated KAPI-SW prototype.

The module deliberately has no I/O and no third-party dependencies.  Input
money and scores are decimal strings; all arithmetic is performed with
``Decimal`` and result decimals are returned as plain decimal strings so the
export layer can canonicalize the result without ever passing through a
binary float.
"""

from __future__ import annotations

from collections import Counter, defaultdict
from datetime import datetime, timedelta, timezone
from decimal import Decimal, InvalidOperation, localcontext
from itertools import combinations, product
from typing import Any, Iterable, Mapping, Sequence

from .util import artifact_notice


class CalculationError(ValueError):
    """Raised when a bundle or methodology is structurally invalid."""


_PRECISION = 50
_MILLION = Decimal("1000000")
_HUNDRED = Decimal("100")
_GRADES = frozenset({"A", "B", "C", "D"})
_DISABLED_REASONING = frozenset({"disabled", "off", "none", "false", "0"})
_INPUT_COMPONENTS = frozenset({"input", "input_token", "input_tokens"})
_OUTPUT_COMPONENTS = frozenset({"output", "output_token", "output_tokens"})
_IGNORED_COMPONENTS = frozenset({"cache_read", "cache_write"})


def _decimal(value: Any, label: str) -> Decimal:
    if isinstance(value, bool) or isinstance(value, float):
        raise CalculationError(f"{label} must be a decimal string, not binary floating point")
    if isinstance(value, Decimal):
        result = value
    elif isinstance(value, (str, int)):
        try:
            result = Decimal(str(value))
        except InvalidOperation as exc:
            raise CalculationError(f"{label} is not a valid decimal") from exc
    else:
        raise CalculationError(f"{label} must be a decimal string")
    if not result.is_finite():
        raise CalculationError(f"{label} must be finite")
    return result


def _integer(value: Any, label: str, *, minimum: int = 0) -> int:
    if isinstance(value, bool):
        raise CalculationError(f"{label} must be an integer")
    if isinstance(value, int):
        result = value
    elif isinstance(value, str) and value.strip() and value.strip().lstrip("-").isdigit():
        result = int(value)
    else:
        raise CalculationError(f"{label} must be an integer")
    if result < minimum:
        raise CalculationError(f"{label} must be at least {minimum}")
    return result


def _timestamp(value: Any, label: str) -> datetime:
    if not isinstance(value, str) or not value.endswith("Z"):
        raise CalculationError(f"{label} must be an ISO-8601 UTC timestamp ending in Z")
    try:
        result = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError as exc:
        raise CalculationError(f"{label} is not a valid ISO-8601 timestamp") from exc
    if result.tzinfo is None or result.utcoffset() != timedelta(0):
        raise CalculationError(f"{label} must be UTC")
    return result.astimezone(timezone.utc)


def _decimal_text(value: Decimal) -> str:
    if value == 0:
        return "0"
    text = format(value, "f")
    if "." in text:
        text = text.rstrip("0").rstrip(".")
    return text


def _json_ready(value: Any) -> Any:
    if isinstance(value, Decimal):
        return _decimal_text(value)
    if isinstance(value, datetime):
        return value.astimezone(timezone.utc).isoformat().replace("+00:00", "Z")
    if isinstance(value, dict):
        return {key: _json_ready(item) for key, item in value.items()}
    if isinstance(value, tuple):
        return [_json_ready(item) for item in value]
    if isinstance(value, list):
        return [_json_ready(item) for item in value]
    return value


def _records_by_id(value: Any, label: str) -> dict[str, Mapping[str, Any]]:
    if not isinstance(value, list):
        raise CalculationError(f"bundle.{label} must be a list")
    result: dict[str, Mapping[str, Any]] = {}
    for position, record in enumerate(value):
        if not isinstance(record, Mapping):
            raise CalculationError(f"bundle.{label}[{position}] must be an object")
        record_id = record.get("id")
        if not isinstance(record_id, str) or not record_id:
            raise CalculationError(f"bundle.{label}[{position}].id must be a stable string")
        if record_id in result:
            raise CalculationError(f"duplicate {label} id: {record_id}")
        result[record_id] = record
    return result


def _bool(record: Mapping[str, Any], key: str) -> bool:
    return record.get(key) is True


def _grade(record: Mapping[str, Any], label: str) -> str:
    value = record.get("evidence_grade")
    if not isinstance(value, str) or value.upper() not in _GRADES:
        raise CalculationError(f"{label}.evidence_grade must be A, B, C, or D")
    return value.upper()


def _rational(value: Any, label: str) -> Decimal:
    if isinstance(value, Mapping):
        numerator = _decimal(value.get("numerator"), f"{label}.numerator")
        denominator = _decimal(value.get("denominator"), f"{label}.denominator")
        if denominator == 0:
            raise CalculationError(f"{label}.denominator cannot be zero")
        result = numerator / denominator
    elif isinstance(value, str) and "/" in value:
        numerator, denominator = value.split("/", 1)
        result = _decimal(numerator, f"{label}.numerator") / _decimal(
            denominator, f"{label}.denominator"
        )
    else:
        result = _decimal(value, label)
    if result < 0:
        raise CalculationError(f"{label} cannot be negative")
    return result


def _normalize_weights(raw: Mapping[str, Any], profile_ids: Sequence[str], label: str) -> dict[str, Decimal]:
    missing = [profile_id for profile_id in profile_ids if profile_id not in raw]
    extra = sorted(set(raw) - set(profile_ids))
    if missing or extra:
        raise CalculationError(f"{label} profile mismatch; missing={missing}, extra={extra}")
    parsed = {profile_id: _rational(raw[profile_id], f"{label}.{profile_id}") for profile_id in profile_ids}
    total = sum(parsed.values(), Decimal(0))
    if total <= 0:
        raise CalculationError(f"{label} must have positive total weight")
    return {profile_id: parsed[profile_id] / total for profile_id in profile_ids}


def _component_name(value: Any) -> str | None:
    if not isinstance(value, str):
        return None
    normalized = value.strip().lower().replace("-", "_").replace(" ", "_")
    if normalized in _INPUT_COMPONENTS:
        return "input"
    if normalized in _OUTPUT_COMPONENTS:
        return "output"
    return None


def _is_disabled_reasoning(value: Any) -> bool:
    if value is False:
        return True
    return isinstance(value, str) and value.strip().lower() in _DISABLED_REASONING


def _reject_cycles(edges: Mapping[str, str], label: str) -> None:
    for start in edges:
        seen: set[str] = set()
        node = start
        while node in edges:
            if node in seen:
                raise CalculationError(f"{label} contains a cycle")
            seen.add(node)
            node = edges[node]


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


class _Calculator:
    def __init__(self, bundle: Mapping[str, Any], methodology: Mapping[str, Any], evidence_mode: str):
        if not isinstance(bundle, Mapping):
            raise CalculationError("bundle must be an object")
        if not isinstance(methodology, Mapping):
            raise CalculationError("methodology must be an object")
        if evidence_mode not in {"official", "research"}:
            raise CalculationError("evidence_mode must be 'official' or 'research'")

        self.bundle = bundle
        self.methodology = methodology
        self.evidence_mode = evidence_mode
        self.providers = _records_by_id(bundle.get("providers"), "providers")
        self.creators = _records_by_id(bundle.get("creators"), "creators")
        self.models = _records_by_id(bundle.get("models"), "models")
        self.endpoints = _records_by_id(bundle.get("endpoints"), "endpoints")
        self.sources = _records_by_id(bundle.get("source_artifacts"), "source_artifacts")
        self.capabilities = _records_by_id(bundle.get("capability_evidence"), "capability_evidence")
        self.observations = _records_by_id(bundle.get("price_observations"), "price_observations")

        token_counts = bundle.get("token_counts")
        if not isinstance(token_counts, list):
            raise CalculationError("bundle.token_counts must be a list")
        self.token_counts: list[Mapping[str, Any]] = []
        token_count_ids: set[str] = set()
        for position, record in enumerate(token_counts):
            if not isinstance(record, Mapping):
                raise CalculationError(f"bundle.token_counts[{position}] must be an object")
            record_id = record.get("id")
            if not isinstance(record_id, str) or not record_id:
                raise CalculationError(
                    f"bundle.token_counts[{position}].id must be a stable string"
                )
            if record_id in token_count_ids:
                raise CalculationError(f"duplicate token_count id: {record_id}")
            token_count_ids.add(record_id)
            self.token_counts.append(record)

        corrections = bundle.get("corrections", [])
        if not isinstance(corrections, list):
            raise CalculationError("bundle.corrections must be a list")
        self.corrections: list[Mapping[str, Any]] = []
        correction_ids: set[str] = set()
        for position, record in enumerate(corrections):
            if not isinstance(record, Mapping):
                raise CalculationError(f"bundle.corrections[{position}] must be an object")
            correction_id = record.get("id")
            if not isinstance(correction_id, str) or not correction_id:
                raise CalculationError(f"bundle.corrections[{position}].id must be a stable string")
            if correction_id in correction_ids:
                raise CalculationError(f"duplicate correction id: {correction_id}")
            correction_ids.add(correction_id)
            self.corrections.append(record)
        correction_supersession: dict[str, str] = {}
        for correction in self.corrections:
            correction_id = str(correction["id"])
            prior_id = correction.get("supersedes_correction_id")
            if prior_id is not None:
                if prior_id not in correction_ids or prior_id == correction_id:
                    raise CalculationError(
                        f"correction {correction_id} has an invalid correction "
                        "supersession reference"
                    )
                correction_supersession[correction_id] = str(prior_id)
            target_id = correction.get("superseded_observation_id")
            replacement_id = correction.get("replacement_observation_id")
            if not isinstance(target_id, str) or not isinstance(
                replacement_id, str
            ):
                raise CalculationError(
                    f"correction {correction_id} must link superseded and "
                    "replacement observations"
                )
            if target_id not in self.observations or replacement_id not in self.observations:
                raise CalculationError(
                    f"correction {correction_id} has an unknown supersession reference"
                )
            target = self.observations[str(target_id)]
            replacement = self.observations[str(replacement_id)]
            if _price_identity(target) != _price_identity(replacement):
                raise CalculationError(
                    f"correction {correction_id} crosses price applicability identities"
                )
            if replacement.get("supersedes_observation_id") != target_id:
                raise CalculationError(
                    f"correction {correction_id} replacement must explicitly "
                    "supersede its target"
                )
        _reject_cycles(correction_supersession, "correction supersession")
        correction_parent_counts = Counter(correction_supersession.values())
        if any(count > 1 for count in correction_parent_counts.values()):
            raise CalculationError("correction supersession cannot branch")

        weeks = bundle.get("weeks")
        if not isinstance(weeks, list) or not weeks:
            raise CalculationError("bundle.weeks must be a non-empty list")
        self.weeks: list[dict[str, Any]] = []
        week_ids: set[str] = set()
        previous_cutoff: datetime | None = None
        for position, week in enumerate(weeks):
            if not isinstance(week, Mapping):
                raise CalculationError(f"bundle.weeks[{position}] must be an object")
            week_id = week.get("id")
            if not isinstance(week_id, str) or not week_id or week_id in week_ids:
                raise CalculationError(f"bundle.weeks[{position}].id must be unique and non-empty")
            cutoff = _timestamp(week.get("cutoff_at"), f"bundle.weeks[{position}].cutoff_at")
            if previous_cutoff is not None and cutoff <= previous_cutoff:
                raise CalculationError("bundle.weeks must be strictly ordered by cutoff_at")
            previous_cutoff = cutoff
            week_ids.add(week_id)
            self.weeks.append({"record": week, "id": week_id, "cutoff": cutoff})
        self.week_ids = week_ids

        profiles = methodology.get("profiles")
        if not isinstance(profiles, list) or not profiles:
            raise CalculationError("methodology.profiles must be a non-empty list")
        self.profiles: list[Mapping[str, Any]] = []
        self.profile_by_id: dict[str, Mapping[str, Any]] = {}
        for position, profile in enumerate(profiles):
            if not isinstance(profile, Mapping):
                raise CalculationError(f"methodology.profiles[{position}] must be an object")
            profile_id = profile.get("id")
            if not isinstance(profile_id, str) or not profile_id or profile_id in self.profile_by_id:
                raise CalculationError(f"methodology.profiles[{position}].id must be unique and non-empty")
            _integer(profile.get("count"), f"methodology.profiles[{position}].count", minimum=1)
            for side in ("input", "output"):
                digest = profile.get(f"{side}_payload_sha256")
                if not isinstance(digest, str) or not digest:
                    raise CalculationError(f"profile {profile_id} is missing {side}_payload_sha256")
            self.profiles.append(profile)
            self.profile_by_id[profile_id] = profile
        self.profile_ids = [str(profile["id"]) for profile in self.profiles]
        self.total_profile_count = sum(_integer(profile["count"], f"profile {profile['id']}.count", minimum=1) for profile in self.profiles)

        self._validate_references()
        self.accepted_grades = self._accepted_grades()
        self.eligibility = methodology.get("eligibility", {})
        if not isinstance(self.eligibility, Mapping):
            raise CalculationError("methodology.eligibility must be an object")
        self.capability = methodology.get("capability", {})
        if not isinstance(self.capability, Mapping):
            raise CalculationError("methodology.capability must be an object")
        self.metric = str(self.capability.get("metric", "ECI"))
        self.base_period_weeks = _integer(methodology.get("base_period_weeks", 13), "methodology.base_period_weeks", minimum=1)
        selection = methodology.get("selection", {})
        if not isinstance(selection, Mapping):
            raise CalculationError("methodology.selection must be an object")
        if _integer(selection.get("provider_count", 3), "selection.provider_count", minimum=1) != 3:
            raise CalculationError("prototype selection.provider_count must be 3")
        if _integer(selection.get("creator_count", 3), "selection.creator_count", minimum=1) != 3:
            raise CalculationError("prototype selection.creator_count must be 3")

    def _validate_references(self) -> None:
        for endpoint_id, endpoint in self.endpoints.items():
            if endpoint.get("provider_id") not in self.providers:
                raise CalculationError(f"endpoint {endpoint_id} references an unknown provider")
            if endpoint.get("model_id") not in self.models:
                raise CalculationError(f"endpoint {endpoint_id} references an unknown model")
        for model_id, model in self.models.items():
            if model.get("creator_id") not in self.creators:
                raise CalculationError(f"model {model_id} references an unknown creator")
        for source_id, source in self.sources.items():
            _grade(source, f"source_artifact {source_id}")
            _timestamp(source.get("retrieved_at"), f"source_artifact {source_id}.retrieved_at")
        for capability_id, record in self.capabilities.items():
            if record.get("endpoint_id") not in self.endpoints or record.get("model_id") not in self.models:
                raise CalculationError(f"capability evidence {capability_id} has an unknown identity reference")
            _grade(record, f"capability_evidence {capability_id}")
            _decimal(record.get("score"), f"capability_evidence {capability_id}.score")
            _timestamp(record.get("evaluated_at"), f"capability_evidence {capability_id}.evaluated_at")
        for position, record in enumerate(self.token_counts):
            if record.get("endpoint_id") not in self.endpoints:
                raise CalculationError(f"token_counts[{position}] references an unknown endpoint")
            if record.get("profile_id") not in self.profile_by_id:
                raise CalculationError(f"token_counts[{position}] references an unknown profile")
            _integer(record.get("input_tokens"), f"token_counts[{position}].input_tokens", minimum=1)
            _integer(record.get("output_tokens"), f"token_counts[{position}].output_tokens", minimum=1)
        observation_supersession: dict[str, str] = {}
        for observation_id, record in self.observations.items():
            if record.get("endpoint_id") not in self.endpoints:
                raise CalculationError(f"price observation {observation_id} references an unknown endpoint")
            if record.get("week_id") not in self.week_ids:
                raise CalculationError(f"price observation {observation_id} references an unknown week")
            raw_component = str(record.get("component", "")).strip().lower().replace("-", "_").replace(" ", "_")
            if _component_name(record.get("component")) is None and raw_component not in _IGNORED_COMPONENTS:
                raise CalculationError(f"price observation {observation_id} has an unsupported component")
            _grade(record, f"price_observation {observation_id}")
            _decimal(record.get("amount_per_million"), f"price_observation {observation_id}.amount_per_million")
            _timestamp(record.get("observed_at"), f"price_observation {observation_id}.observed_at")
            effective_at = record.get("effective_at") or record.get("observed_at")
            _timestamp(effective_at, f"price_observation {observation_id}.effective_at")
            superseded = record.get("supersedes_observation_id")
            if superseded is not None:
                if superseded not in self.observations:
                    raise CalculationError(
                        f"price observation {observation_id} supersedes an unknown observation"
                    )
                if superseded == observation_id:
                    raise CalculationError(
                        f"price observation {observation_id} cannot supersede itself"
                    )
                if _price_identity(record) != _price_identity(
                    self.observations[str(superseded)]
                ):
                    raise CalculationError(
                        f"price observation {observation_id} crosses applicability identities"
                    )
                observation_supersession[observation_id] = str(superseded)
        _reject_cycles(observation_supersession, "price observation supersession")

    def _accepted_grades(self) -> frozenset[str]:
        policy = self.methodology.get("evidence_policy", {})
        if not isinstance(policy, Mapping):
            raise CalculationError("methodology.evidence_policy must be an object")
        key = "official_grades" if self.evidence_mode == "official" else "research_grades"
        default = ["A"] if self.evidence_mode == "official" else ["A", "B", "C"]
        values = policy.get(key, default)
        if not isinstance(values, list) or not values:
            raise CalculationError(f"evidence_policy.{key} must be a non-empty list")
        grades = frozenset(str(value).upper() for value in values)
        if not grades <= _GRADES or "D" in grades:
            raise CalculationError(f"evidence_policy.{key} contains an invalid or excluded grade")
        if self.evidence_mode == "official" and grades != frozenset({"A"}):
            raise CalculationError("official evidence mode must be restricted to grade A")
        return grades

    def _weights(self, override: Mapping[str, Any] | None) -> dict[str, Decimal]:
        if override is not None:
            if not isinstance(override, Mapping):
                raise CalculationError("weights override must be an object keyed by profile id")
            return _normalize_weights(override, self.profile_ids, "weights")
        raw = {str(profile["id"]): profile.get("weight") for profile in self.profiles}
        if all(value is not None for value in raw.values()):
            return _normalize_weights(raw, self.profile_ids, "methodology profile weights")
        counts = {str(profile["id"]): profile.get("count") for profile in self.profiles}
        return _normalize_weights(counts, self.profile_ids, "methodology profile counts")

    def _source_usable(self, source_id: Any) -> bool:
        if not isinstance(source_id, str) or source_id not in self.sources:
            return False
        source = self.sources[source_id]
        if _grade(source, f"source_artifact {source_id}") not in self.accepted_grades:
            return False
        required = ("url", "content_sha256", "snapshot_path")
        return all(isinstance(source.get(field), str) and bool(source.get(field)) for field in required)

    def _source_lineage(self, source_ids: Iterable[str]) -> list[dict[str, Any]]:
        result = []
        for source_id in sorted(set(source_ids)):
            source = self.sources[source_id]
            result.append(
                {
                    "id": source_id,
                    "url": source.get("url"),
                    "retrieved_at": source.get("retrieved_at"),
                    "evidence_grade": _grade(source, f"source_artifact {source_id}"),
                    "content_sha256": source.get("content_sha256"),
                    "snapshot_path": source.get("snapshot_path"),
                }
            )
        return result

    def _profile_hash(self, profile: Mapping[str, Any], side: str, size_variant: str = "100x100") -> str:
        if size_variant == str(profile.get("headline_size_variant", "100x100")):
            return str(profile[f"{side}_payload_sha256"])
        sensitivities = self.methodology.get("sensitivities", {})
        grid = sensitivities.get("payload_size_grid", []) if isinstance(sensitivities, Mapping) else []
        grid_cell = next(
            (cell for cell in grid if isinstance(cell, Mapping) and cell.get("id") == size_variant),
            None,
        )
        if grid_cell is None:
            raise CalculationError(f"unknown payload size variant: {size_variant}")
        factor_key = "input_factor" if side == "input" else "output_factor"
        factor = str(grid_cell.get(factor_key))
        payloads = profile.get("payloads", {})
        side_payloads = payloads.get(side, []) if isinstance(payloads, Mapping) else []
        if not isinstance(side_payloads, list):
            raise CalculationError(f"profile {profile['id']}.payloads.{side} must be a list")
        fixture = next(
            (
                item
                for item in side_payloads
                if isinstance(item, Mapping)
                and _decimal(item.get("size_factor"), f"profile {profile['id']} {side} size_factor")
                == _decimal(factor, f"payload grid {size_variant}.{factor_key}")
            ),
            None,
        )
        if fixture is None or not isinstance(fixture.get("sha256"), str) or not fixture.get("sha256"):
            raise CalculationError(f"profile {profile['id']} has no hashed {side} fixture for {size_variant}")
        return str(fixture["sha256"])

    def _token_count(self, endpoint: Mapping[str, Any], profile: Mapping[str, Any], size_variant: str) -> tuple[dict[str, Any] | None, list[str]]:
        endpoint_id = str(endpoint["id"])
        profile_id = str(profile["id"])
        records = [
            record
            for record in self.token_counts
            if record.get("endpoint_id") == endpoint_id
            and record.get("profile_id") == profile_id
            and str(record.get("size_variant", "100x100")) == size_variant
        ]
        if not records:
            return None, ["missing_token_count"]
        valid = []
        for position, record in enumerate(records):
            reasons = []
            if record.get("billing_tokenizer") != endpoint.get("billing_tokenizer"):
                reasons.append("billing_tokenizer_mismatch")
            if record.get("input_payload_sha256") != self._profile_hash(profile, "input", size_variant):
                reasons.append("input_payload_hash_mismatch")
            if record.get("output_payload_sha256") != self._profile_hash(profile, "output", size_variant):
                reasons.append("output_payload_hash_mismatch")
            if not reasons:
                valid.append(
                    {
                        "record": record,
                        "input_tokens": _integer(record.get("input_tokens"), f"token_count {endpoint_id}/{profile_id}/{position}.input_tokens", minimum=1),
                        "output_tokens": _integer(record.get("output_tokens"), f"token_count {endpoint_id}/{profile_id}/{position}.output_tokens", minimum=1),
                    }
                )
        if not valid:
            return None, ["invalid_token_count"]
        signatures = {(item["input_tokens"], item["output_tokens"], item["record"].get("billing_tokenizer")) for item in valid}
        if len(signatures) != 1:
            return None, ["token_count_conflict"]
        first = sorted(valid, key=lambda item: str(item["record"].get("id", "")))[0]
        ids = sorted(str(item["record"]["id"]) for item in valid)
        return {
            "input_tokens": first["input_tokens"],
            "output_tokens": first["output_tokens"],
            "record_ids": ids,
            "billing_tokenizer": endpoint.get("billing_tokenizer"),
            "size_variant": size_variant,
            "input_payload_sha256": self._profile_hash(profile, "input", size_variant),
            "output_payload_sha256": self._profile_hash(profile, "output", size_variant),
        }, []

    def _endpoint_reasons(self, endpoint: Mapping[str, Any], model: Mapping[str, Any], week: Mapping[str, Any], profile: Mapping[str, Any], token_count: Mapping[str, Any]) -> list[str]:
        reasons: list[str] = []
        required_flags = self.eligibility.get(
            "required_endpoint_flags",
            ["public", "ga", "synchronous", "on_demand", "available_us", "standard_list_price"],
        )
        if isinstance(required_flags, Mapping):
            for flag, expected in sorted(required_flags.items()):
                if not isinstance(flag, str) or not isinstance(expected, bool):
                    raise CalculationError("eligibility.required_endpoint_flags must map strings to booleans")
                if endpoint.get(flag) is not expected:
                    reasons.append(f"endpoint_flag:{flag}")
        elif isinstance(required_flags, list):
            for flag in required_flags:
                if not isinstance(flag, str) or not _bool(endpoint, flag):
                    reasons.append(f"endpoint_flag:{flag}")
        else:
            raise CalculationError("eligibility.required_endpoint_flags must be a list or object")

        region = self.eligibility.get("region", "US")
        if endpoint.get("region") != region:
            reasons.append("region")
        allowed_aliases = self.eligibility.get("allowed_alias_types", ["immutable", "resolved"])
        if not isinstance(allowed_aliases, list):
            raise CalculationError("eligibility.allowed_alias_types must be a list")
        if model.get("alias_type") not in allowed_aliases:
            reasons.append("alias_type")
        if model.get("immutable_version") is not True or not isinstance(model.get("version"), str) or not model.get("version"):
            reasons.append("model_not_exact_immutable")

        required_reasoning = self.eligibility.get("reasoning_mode", "disabled")
        if str(required_reasoning).lower() in _DISABLED_REASONING and not _is_disabled_reasoning(endpoint.get("reasoning_mode")):
            reasons.append("reasoning_not_disabled")
        if not isinstance(endpoint.get("billing_tokenizer"), str) or not endpoint.get("billing_tokenizer"):
            reasons.append("billing_tokenizer_missing")
        if endpoint.get("tokenizer_reproducible") is not True:
            reasons.append("billing_tokenizer_not_reproducible")

        features = endpoint.get("features", [])
        if not isinstance(features, list):
            reasons.append("features_invalid")
            features = []
        required_features = profile.get("required_endpoint_features", profile.get("required_features", []))
        if not isinstance(required_features, list):
            raise CalculationError(f"profile {profile['id']}.required_features must be a list")
        missing_features = sorted(set(required_features) - set(features))
        if missing_features:
            reasons.append("missing_features:" + ",".join(missing_features))

        cutoff = week["cutoff"]
        available_from = endpoint.get("available_from")
        seasoning_days = _integer(self.eligibility.get("seasoning_days", 7), "eligibility.seasoning_days", minimum=0)
        if available_from is None:
            if endpoint.get("seasoned") is not True:
                reasons.append("availability_start_missing")
        else:
            start = _timestamp(available_from, f"endpoint {endpoint['id']}.available_from")
            if cutoff < start:
                reasons.append("not_yet_available")
            elif cutoff - start < timedelta(days=seasoning_days):
                reasons.append("not_seasoned")
        available_until = endpoint.get("available_until")
        if available_until is not None:
            end = _timestamp(available_until, f"endpoint {endpoint['id']}.available_until")
            if cutoff >= end:
                reasons.append("no_longer_available")

        input_tokens = int(token_count["input_tokens"])
        output_tokens = int(token_count["output_tokens"])
        if endpoint.get("max_input_tokens") is not None and input_tokens > _integer(endpoint.get("max_input_tokens"), f"endpoint {endpoint['id']}.max_input_tokens", minimum=1):
            reasons.append("input_limit")
        if endpoint.get("max_output_tokens") is not None and output_tokens > _integer(endpoint.get("max_output_tokens"), f"endpoint {endpoint['id']}.max_output_tokens", minimum=0):
            reasons.append("output_limit")
        if endpoint.get("max_context_tokens") is not None and input_tokens + output_tokens > _integer(endpoint.get("max_context_tokens"), f"endpoint {endpoint['id']}.max_context_tokens", minimum=1):
            reasons.append("context_limit")
        return sorted(set(reasons))

    def _capability_record(self, endpoint: Mapping[str, Any], model: Mapping[str, Any], threshold: Decimal) -> tuple[dict[str, Any] | None, list[str]]:
        candidates = []
        for record_id, record in self.capabilities.items():
            if record.get("endpoint_id") != endpoint.get("id") or record.get("model_id") != model.get("id"):
                continue
            if record.get("metric") != self.metric:
                continue
            if record.get("configuration_id") != endpoint.get("configuration_id"):
                continue
            if _grade(record, f"capability_evidence {record_id}") not in self.accepted_grades:
                continue
            if not self._source_usable(record.get("source_id")):
                continue
            if _grade(record, f"capability_evidence {record_id}") != _grade(
                self.sources[str(record["source_id"])], f"source_artifact {record['source_id']}"
            ):
                continue
            configured_version = self.capability.get("metric_version")
            configured_vintage = self.capability.get("data_vintage")
            if configured_version is not None and record.get("metric_version") != configured_version:
                continue
            if configured_vintage is not None and record.get("data_vintage") != configured_vintage:
                continue
            candidates.append(record)
        if not candidates:
            return None, ["missing_capability_evidence"]
        candidates.sort(key=lambda item: (_timestamp(item.get("evaluated_at"), f"capability {item['id']}.evaluated_at"), str(item["id"])), reverse=True)
        chosen = candidates[0]
        score = _decimal(chosen.get("score"), f"capability {chosen['id']}.score")
        if score < threshold:
            return None, ["capability_below_threshold"]
        source_id = str(chosen["source_id"])
        return {
            "id": chosen["id"],
            "score": score,
            "metric": chosen.get("metric"),
            "metric_version": chosen.get("metric_version"),
            "data_vintage": chosen.get("data_vintage"),
            "source_id": source_id,
            "source": self._source_lineage([source_id])[0],
        }, []

    def _correction_state(self) -> tuple[set[str], dict[str, str], dict[str, list[str]]]:
        voided: set[str] = set()
        superseded_by: dict[str, str] = {}
        lineage: dict[str, list[str]] = defaultdict(list)
        correction_by_id = {str(correction["id"]): correction for correction in self.corrections}
        superseded_correction_ids: set[str] = set()
        for correction in self.corrections:
            previous_id = correction.get("supersedes_correction_id")
            if previous_id is not None:
                if previous_id not in correction_by_id or previous_id == correction.get("id"):
                    raise CalculationError(f"correction {correction['id']} has an invalid correction supersession reference")
                superseded_correction_ids.add(str(previous_id))
        for correction in self.corrections:
            if str(correction["id"]) in superseded_correction_ids:
                continue
            if correction.get("applied", True) is False or str(correction.get("status", "applied")).lower() in {"rejected", "draft"}:
                continue
            correction_id = str(correction["id"])
            action = str(correction.get("action", "supersede")).lower()
            target = (
                correction.get("target_observation_id")
                or correction.get("superseded_observation_id")
                or correction.get("supersedes_observation_id")
            )
            replacement = correction.get("replacement_observation_id") or correction.get("observation_id")
            if action in {"void", "exclude", "invalidate"}:
                if target not in self.observations:
                    raise CalculationError(f"correction {correction_id} targets an unknown observation")
                voided.add(str(target))
                lineage[str(target)].append(correction_id)
            elif target is not None or replacement is not None:
                if target not in self.observations or replacement not in self.observations:
                    raise CalculationError(f"correction {correction_id} has an unknown supersession reference")
                superseded_by[str(target)] = str(replacement)
                lineage[str(target)].append(correction_id)
                lineage[str(replacement)].append(correction_id)
        return voided, superseded_by, lineage

    def _price_component(self, endpoint: Mapping[str, Any], week: Mapping[str, Any], component: str, context_tokens: int) -> tuple[dict[str, Any] | None, list[str]]:
        cutoff = week["cutoff"]
        scoped: list[Mapping[str, Any]] = []
        for record in self.observations.values():
            if record.get("endpoint_id") != endpoint.get("id") or record.get("week_id") != week["id"]:
                continue
            if _component_name(record.get("component")) != component:
                continue
            if record.get("currency") != self.eligibility.get("currency", "USD"):
                continue
            if record.get("unit") != self.eligibility.get("unit", "per_million_tokens"):
                continue
            if record.get("region") != endpoint.get("region") or record.get("tier") != endpoint.get("tier"):
                continue
            observed_at = _timestamp(record.get("observed_at"), f"price observation {record['id']}.observed_at")
            effective_at = _timestamp(record.get("effective_at") or record.get("observed_at"), f"price observation {record['id']}.effective_at")
            if observed_at > cutoff or effective_at > cutoff:
                continue
            minimum = _integer(record.get("context_min_tokens", 0), f"price observation {record['id']}.context_min_tokens", minimum=0)
            maximum_value = record.get("context_max_tokens")
            maximum = None if maximum_value in {None, ""} else _integer(maximum_value, f"price observation {record['id']}.context_max_tokens", minimum=0)
            if context_tokens < minimum or (maximum is not None and context_tokens > maximum):
                continue
            scoped.append(record)
        if not scoped:
            return None, [f"missing_{component}_price"]

        voided, corrected_supersession, correction_lineage = self._correction_state()
        superseded_by = dict(corrected_supersession)
        for record in scoped:
            old_id = record.get("supersedes_observation_id")
            if old_id is not None:
                superseded_by[str(old_id)] = str(record["id"])
        active = [record for record in scoped if str(record["id"]) not in voided and str(record["id"]) not in superseded_by]
        if not active:
            return None, [f"superseded_{component}_price_without_replacement"]

        evidence_usable = [
            record
            for record in active
            if _grade(record, f"price_observation {record['id']}") in self.accepted_grades
            and self._source_usable(record.get("source_id"))
            and _grade(record, f"price_observation {record['id']}")
            == _grade(self.sources[str(record["source_id"])], f"source_artifact {record['source_id']}")
        ]
        if not evidence_usable:
            return None, [f"missing_grade_eligible_{component}_price"]

        # Overlapping context tiers resolve to the most specific applicable tier:
        # highest lower bound, then lowest finite upper bound.
        highest_min = max(_integer(record.get("context_min_tokens", 0), f"price observation {record['id']}.context_min_tokens") for record in evidence_usable)
        evidence_usable = [record for record in evidence_usable if _integer(record.get("context_min_tokens", 0), f"price observation {record['id']}.context_min_tokens") == highest_min]
        finite_maxes = [
            _integer(record.get("context_max_tokens"), f"price observation {record['id']}.context_max_tokens")
            for record in evidence_usable
            if record.get("context_max_tokens") not in {None, ""}
        ]
        if finite_maxes:
            narrowest_max = min(finite_maxes)
            evidence_usable = [record for record in evidence_usable if record.get("context_max_tokens") not in {None, ""} and _integer(record.get("context_max_tokens"), f"price observation {record['id']}.context_max_tokens") == narrowest_max]

        latest_effective = max(_timestamp(record.get("effective_at") or record.get("observed_at"), f"price observation {record['id']}.effective_at") for record in evidence_usable)
        evidence_usable = [record for record in evidence_usable if _timestamp(record.get("effective_at") or record.get("observed_at"), f"price observation {record['id']}.effective_at") == latest_effective]
        amounts = {_decimal(record.get("amount_per_million"), f"price observation {record['id']}.amount_per_million") for record in evidence_usable}
        if len(amounts) != 1:
            return None, [f"conflicting_{component}_price"]
        amount = next(iter(amounts))
        if amount < 0:
            return None, [f"negative_{component}_price"]
        observation_ids = sorted(str(record["id"]) for record in evidence_usable)
        source_ids = sorted({str(record["source_id"]) for record in evidence_usable})
        correction_ids = sorted({correction_id for observation_id in observation_ids for correction_id in correction_lineage.get(observation_id, [])})
        return {
            "amount_per_million": amount,
            "observation_ids": observation_ids,
            "source_ids": source_ids,
            "sources": self._source_lineage(source_ids),
            "correction_ids": correction_ids,
            "effective_at": latest_effective,
            "context_min_tokens": highest_min,
            "context_max_tokens": min(finite_maxes) if finite_maxes else None,
        }, []

    def _candidate(self, endpoint: Mapping[str, Any], week: Mapping[str, Any], profile: Mapping[str, Any], threshold: Decimal, size_variant: str) -> tuple[dict[str, Any] | None, list[str]]:
        token_count, token_reasons = self._token_count(endpoint, profile, size_variant)
        if token_count is None:
            return None, token_reasons
        model = self.models[str(endpoint["model_id"])]
        reasons = self._endpoint_reasons(endpoint, model, week, profile, token_count)
        capability, capability_reasons = self._capability_record(endpoint, model, threshold)
        reasons.extend(capability_reasons)
        input_price, input_reasons = self._price_component(endpoint, week, "input", int(token_count["input_tokens"]))
        output_price, output_reasons = self._price_component(endpoint, week, "output", int(token_count["input_tokens"]))
        reasons.extend(input_reasons)
        reasons.extend(output_reasons)
        if reasons or capability is None or input_price is None or output_price is None:
            return None, sorted(set(reasons))
        input_cost = Decimal(int(token_count["input_tokens"])) * input_price["amount_per_million"] / _MILLION
        output_cost = Decimal(int(token_count["output_tokens"])) * output_price["amount_per_million"] / _MILLION
        source_ids = sorted(set(input_price["source_ids"] + output_price["source_ids"] + [str(capability["source_id"])]))
        observation_ids = sorted(input_price["observation_ids"] + output_price["observation_ids"])
        return {
            "endpoint_id": endpoint["id"],
            "provider_id": endpoint["provider_id"],
            "creator_id": model["creator_id"],
            "model_id": model["id"],
            "configuration_id": endpoint.get("configuration_id"),
            "cost": input_cost + output_cost,
            "input_cost": input_cost,
            "output_cost": output_cost,
            "input_tokens": token_count["input_tokens"],
            "output_tokens": token_count["output_tokens"],
            "input_price_per_million": input_price["amount_per_million"],
            "output_price_per_million": output_price["amount_per_million"],
            "lineage": {
                "token_count": token_count,
                "capability": capability,
                "input_price": input_price,
                "output_price": output_price,
                "observation_ids": observation_ids,
                "source_ids": source_ids,
                "sources": self._source_lineage(source_ids),
                "correction_ids": sorted(set(input_price["correction_ids"] + output_price["correction_ids"])),
            },
        }, []

    def _profile_calculation(
        self,
        week: Mapping[str, Any],
        profile: Mapping[str, Any],
        threshold: Decimal,
        size_variant: str,
        *,
        excluded_provider_ids: frozenset[str] = frozenset(),
        excluded_creator_ids: frozenset[str] = frozenset(),
        allowed_endpoint_ids: frozenset[str] | None = None,
        first_party_only: bool = False,
    ) -> dict[str, Any]:
        candidates = []
        exclusions = []
        for endpoint_id in sorted(self.endpoints):
            endpoint = self.endpoints[endpoint_id]
            model = self.models[str(endpoint["model_id"])]
            policy_reasons = []
            if endpoint.get("provider_id") in excluded_provider_ids:
                policy_reasons.append("excluded_provider_sensitivity")
            if model.get("creator_id") in excluded_creator_ids:
                policy_reasons.append("excluded_creator_sensitivity")
            if allowed_endpoint_ids is not None and endpoint_id not in allowed_endpoint_ids:
                policy_reasons.append("outside_constant_universe")
            if first_party_only and endpoint.get("first_party") is not True:
                policy_reasons.append("not_verified_first_party")
            if policy_reasons:
                exclusions.append({"endpoint_id": endpoint_id, "reasons": policy_reasons})
                continue
            candidate, reasons = self._candidate(endpoint, week, profile, threshold, size_variant)
            if candidate is None:
                exclusions.append({"endpoint_id": endpoint_id, "reasons": reasons})
            else:
                candidates.append(candidate)
        independent_triples = []
        for triple in combinations(candidates, 3):
            if len({item["provider_id"] for item in triple}) != 3:
                continue
            if len({item["creator_id"] for item in triple}) != 3:
                continue
            ordered = sorted(triple, key=lambda item: (item["cost"], item["endpoint_id"]))
            total = sum((item["cost"] for item in triple), Decimal(0))
            endpoint_ids = tuple(sorted(str(item["endpoint_id"]) for item in triple))
            independent_triples.append(
                {
                    "members": triple,
                    "ordered": ordered,
                    "median": ordered[1]["cost"],
                    "total": total,
                    "mean": total / Decimal(3),
                    "endpoint_ids": endpoint_ids,
                }
            )
        if not independent_triples:
            return {
                "profile_id": profile["id"],
                "status": "withheld_incomplete",
                "diagnostics": ["no_independent_three_provider_creator_triple"],
                "eligible_candidate_count": len(candidates),
                "candidates": candidates,
                "exclusions": exclusions,
            }

        selected = min(independent_triples, key=lambda item: (item["median"], item["total"], item["endpoint_ids"]))
        mean_selected = min(independent_triples, key=lambda item: (item["mean"], item["median"], item["total"], item["endpoint_ids"]))
        setter = selected["ordered"][1]
        frontier = min(candidates, key=lambda item: (item["cost"], item["endpoint_id"]))
        selected_lineage = {
            "observation_ids": sorted({observation_id for candidate in selected["members"] for observation_id in candidate["lineage"]["observation_ids"]}),
            "source_ids": sorted({source_id for candidate in selected["members"] for source_id in candidate["lineage"]["source_ids"]}),
            "token_count_record_ids": sorted({record_id for candidate in selected["members"] for record_id in candidate["lineage"]["token_count"]["record_ids"]}),
            "capability_evidence_ids": sorted({str(candidate["lineage"]["capability"]["id"]) for candidate in selected["members"]}),
            "correction_ids": sorted({correction_id for candidate in selected["members"] for correction_id in candidate["lineage"]["correction_ids"]}),
        }
        selected_lineage["sources"] = self._source_lineage(selected_lineage["source_ids"])
        calculation_lineage = {
            "observation_ids": sorted({observation_id for candidate in candidates for observation_id in candidate["lineage"]["observation_ids"]}),
            "source_ids": sorted({source_id for candidate in candidates for source_id in candidate["lineage"]["source_ids"]}),
            "token_count_record_ids": sorted({record_id for candidate in candidates for record_id in candidate["lineage"]["token_count"]["record_ids"]}),
            "capability_evidence_ids": sorted({str(candidate["lineage"]["capability"]["id"]) for candidate in candidates}),
            "correction_ids": sorted({correction_id for candidate in candidates for correction_id in candidate["lineage"]["correction_ids"]}),
        }
        calculation_lineage["sources"] = self._source_lineage(calculation_lineage["source_ids"])
        return {
            "profile_id": profile["id"],
            "status": "complete",
            "price": selected["median"],
            "price_setter_endpoint_id": setter["endpoint_id"],
            "price_setter_provider_id": setter["provider_id"],
            "price_setter_creator_id": setter["creator_id"],
            "selected_triple_endpoint_ids": list(selected["endpoint_ids"]),
            "selected_cost_order": [item["endpoint_id"] for item in selected["ordered"]],
            "selected_total_cost": selected["total"],
            "mean_three_price": mean_selected["mean"],
            "mean_three_endpoint_ids": list(mean_selected["endpoint_ids"]),
            "frontier_price": frontier["cost"],
            "frontier_endpoint_id": frontier["endpoint_id"],
            "eligible_candidate_count": len(candidates),
            "candidates": candidates,
            "exclusions": exclusions,
            "lineage": calculation_lineage,
            "selected_lineage": selected_lineage,
        }

    def _concentration(self, profile_results: Mapping[str, Mapping[str, Any]], basket: Decimal, weights: Mapping[str, Decimal]) -> dict[str, Any]:
        configuration = self.methodology.get("concentration", {})
        if not isinstance(configuration, Mapping):
            raise CalculationError("methodology.concentration must be an object")
        warning_share = _decimal(configuration.get("warning_share", "0.35"), "concentration.warning_share")
        warning_count = _integer(configuration.get("warning_profile_count", 3), "concentration.warning_profile_count", minimum=1)
        withhold_share = _decimal(configuration.get("withhold_share", "0.50"), "concentration.withhold_share")
        withhold_count = _integer(configuration.get("withhold_profile_count", 4), "concentration.withhold_profile_count", minimum=1)
        if basket <= 0:
            return {"status": "unavailable", "warnings": [], "withholds": [], "providers": {}, "creators": {}}

        def calculate(identity_key: str) -> dict[str, dict[str, Any]]:
            dollars: dict[str, Decimal] = defaultdict(Decimal)
            counts: dict[str, int] = defaultdict(int)
            profiles_by_identity: dict[str, list[str]] = defaultdict(list)
            for profile_id, result in profile_results.items():
                identity = str(result[identity_key])
                dollars[identity] += weights[profile_id] * result["price"]
                counts[identity] += 1
                profiles_by_identity[identity].append(profile_id)
            return {
                identity: {
                    "basket_dollars": dollars[identity],
                    "share": dollars[identity] / basket,
                    "profile_count": counts[identity],
                    "profile_ids": sorted(profiles_by_identity[identity]),
                    "warning": dollars[identity] / basket > warning_share or counts[identity] >= warning_count,
                    "withhold": dollars[identity] / basket > withhold_share or counts[identity] >= withhold_count,
                }
                for identity in sorted(dollars)
            }

        providers = calculate("price_setter_provider_id")
        creators = calculate("price_setter_creator_id")
        warnings = [f"provider:{identity}" for identity, value in providers.items() if value["warning"]]
        warnings += [f"creator:{identity}" for identity, value in creators.items() if value["warning"]]
        withholds = [f"provider:{identity}" for identity, value in providers.items() if value["withhold"]]
        withholds += [f"creator:{identity}" for identity, value in creators.items() if value["withhold"]]
        return {
            "status": "withhold" if withholds else ("warning" if warnings else "ok"),
            "warning_share_threshold": warning_share,
            "warning_profile_count_threshold": warning_count,
            "withhold_share_threshold": withhold_share,
            "withhold_profile_count_threshold": withhold_count,
            "warnings": sorted(warnings),
            "withholds": sorted(withholds),
            "providers": providers,
            "creators": creators,
        }

    def _run(
        self,
        threshold: Decimal,
        weights: Mapping[str, Decimal],
        size_variant: str,
        *,
        active_profile_ids: Sequence[str] | None = None,
        excluded_provider_ids: frozenset[str] = frozenset(),
        excluded_creator_ids: frozenset[str] = frozenset(),
        allowed_endpoint_ids: frozenset[str] | None = None,
        first_party_only: bool = False,
    ) -> dict[str, Any]:
        profile_ids = list(active_profile_ids or self.profile_ids)
        active_profile_count = sum(
            _integer(
                self.profile_by_id[profile_id]["count"],
                f"profile {profile_id}.count",
                minimum=1,
            )
            for profile_id in profile_ids
        )
        week_results = []
        for week in self.weeks:
            profiles = {
                profile_id: self._profile_calculation(
                    week,
                    self.profile_by_id[profile_id],
                    threshold,
                    size_variant,
                    excluded_provider_ids=excluded_provider_ids,
                    excluded_creator_ids=excluded_creator_ids,
                    allowed_endpoint_ids=allowed_endpoint_ids,
                    first_party_only=first_party_only,
                )
                for profile_id in profile_ids
            }
            declared_complete = week["record"].get("complete", True) is not False
            complete = declared_complete and all(result["status"] == "complete" for result in profiles.values())
            if complete:
                basket = sum((weights[profile_id] * profiles[profile_id]["price"] for profile_id in profile_ids), Decimal(0))
                frontier_basket = sum((weights[profile_id] * profiles[profile_id]["frontier_price"] for profile_id in profile_ids), Decimal(0))
                mean_three_basket = sum((weights[profile_id] * profiles[profile_id]["mean_three_price"] for profile_id in profile_ids), Decimal(0))
                concentration = self._concentration(profiles, basket, weights)
                status = "withheld_concentration" if concentration["status"] == "withhold" else "complete"
                if self.evidence_mode == "research" and status == "complete":
                    status = "research_only"
                lineage = {
                    "observation_ids": sorted({observation_id for result in profiles.values() for observation_id in result["lineage"]["observation_ids"]}),
                    "source_ids": sorted({source_id for result in profiles.values() for source_id in result["lineage"]["source_ids"]}),
                    "token_count_record_ids": sorted({record_id for result in profiles.values() for record_id in result["lineage"]["token_count_record_ids"]}),
                    "capability_evidence_ids": sorted({record_id for result in profiles.values() for record_id in result["lineage"]["capability_evidence_ids"]}),
                    "correction_ids": sorted({record_id for result in profiles.values() for record_id in result["lineage"]["correction_ids"]}),
                }
                lineage["sources"] = self._source_lineage(lineage["source_ids"])
            else:
                basket = frontier_basket = mean_three_basket = None
                concentration = {"status": "unavailable", "warnings": [], "withholds": [], "providers": {}, "creators": {}}
                status = "withheld_incomplete"
                lineage = {"observation_ids": [], "source_ids": [], "sources": [], "token_count_record_ids": [], "capability_evidence_ids": [], "correction_ids": []}
            week_results.append(
                {
                    "id": week["id"],
                    "cutoff_at": week["record"]["cutoff_at"],
                    "cutoff": week["cutoff"],
                    "declared_complete": declared_complete,
                    "complete": complete,
                    "publishable": complete and concentration["status"] != "withhold" and self.evidence_mode == "official",
                    "status": status,
                    "profiles": profiles,
                    "representative_profile_cost": basket,
                    "basket_profile_count": active_profile_count,
                    "basket_cost": None if basket is None else basket * Decimal(active_profile_count),
                    "frontier_representative_profile_cost": frontier_basket,
                    "mean_three_representative_profile_cost": mean_three_basket,
                    "concentration": concentration,
                    "lineage": lineage,
                    "index": None,
                    "sensitivities": {"geometric_index": None, "frontier_index": None, "mean_three_index": None},
                }
            )

        base_indices = self._find_base_indices(week_results)
        if base_indices is None:
            return {
                "threshold": threshold,
                "weights": dict(weights),
                "size_variant": size_variant,
                "status": "pending_base",
                "base_period": {"status": "pending", "required_weeks": self.base_period_weeks, "week_ids": []},
                "weeks": week_results,
            }
        base_weeks = [week_results[index] for index in base_indices]
        profile_means = {
            profile_id: sum((week["profiles"][profile_id]["price"] for week in base_weeks), Decimal(0)) / Decimal(self.base_period_weeks)
            for profile_id in profile_ids
        }
        frontier_means = {
            profile_id: sum((week["profiles"][profile_id]["frontier_price"] for week in base_weeks), Decimal(0)) / Decimal(self.base_period_weeks)
            for profile_id in profile_ids
        }
        mean_three_means = {
            profile_id: sum((week["profiles"][profile_id]["mean_three_price"] for week in base_weeks), Decimal(0)) / Decimal(self.base_period_weeks)
            for profile_id in profile_ids
        }
        base_basket = sum((weights[profile_id] * profile_means[profile_id] for profile_id in profile_ids), Decimal(0))
        frontier_base = sum((weights[profile_id] * frontier_means[profile_id] for profile_id in profile_ids), Decimal(0))
        mean_three_base = sum((weights[profile_id] * mean_three_means[profile_id] for profile_id in profile_ids), Decimal(0))
        if base_basket <= 0 or frontier_base <= 0 or mean_three_base <= 0:
            raise CalculationError("base-period basket costs must be positive")
        for week in week_results:
            if not week["complete"]:
                continue
            basket = week["representative_profile_cost"]
            week["index"] = _HUNDRED * basket / base_basket
            log_sum = Decimal(0)
            geometric_valid = True
            for profile_id in profile_ids:
                price = week["profiles"][profile_id]["price"]
                if price <= 0 or profile_means[profile_id] <= 0:
                    geometric_valid = False
                    break
                log_sum += weights[profile_id] * (price / profile_means[profile_id]).ln()
            week["sensitivities"]["geometric_index"] = _HUNDRED * log_sum.exp() if geometric_valid else None
            week["sensitivities"]["frontier_index"] = _HUNDRED * week["frontier_representative_profile_cost"] / frontier_base
            week["sensitivities"]["mean_three_index"] = _HUNDRED * week["mean_three_representative_profile_cost"] / mean_three_base
        return {
            "threshold": threshold,
            "weights": dict(weights),
            "size_variant": size_variant,
            "status": "calculated",
            "base_period": {
                "status": "complete",
                "required_weeks": self.base_period_weeks,
                "week_ids": [week["id"] for week in base_weeks],
                "start_cutoff_at": base_weeks[0]["cutoff_at"],
                "end_cutoff_at": base_weeks[-1]["cutoff_at"],
                "profile_means": profile_means,
                "representative_profile_cost": base_basket,
                "basket_cost": base_basket * Decimal(active_profile_count),
                "frontier_representative_profile_cost": frontier_base,
                "mean_three_representative_profile_cost": mean_three_base,
            },
            "weeks": week_results,
        }

    def _find_base_indices(self, week_results: Sequence[Mapping[str, Any]]) -> list[int] | None:
        policy = self.methodology.get("base_eligibility", {})
        if not isinstance(policy, Mapping):
            raise CalculationError("methodology.base_eligibility must be an object")
        raw_noncounting = policy.get("noncounting_release_statuses", [])
        if not isinstance(raw_noncounting, list) or not all(
            isinstance(item, str) and item for item in raw_noncounting
        ):
            raise CalculationError(
                "base_eligibility.noncounting_release_statuses must be a list of strings"
            )
        noncounting = set(raw_noncounting)
        run: list[int] = []
        previous_cutoff: datetime | None = None
        for index, week in enumerate(week_results):
            # The calculator applies deterministic calculation-state policy.
            # Human signoff, reproduction and material-correction states are
            # applied by the append-only lifecycle gate before finalization.
            if not week["complete"] or week.get("status") in noncounting:
                run = []
                previous_cutoff = None
                continue
            cutoff = week["cutoff"]
            if previous_cutoff is None or cutoff - previous_cutoff == timedelta(days=7):
                run.append(index)
            else:
                run = [index]
            previous_cutoff = cutoff
            if len(run) == self.base_period_weeks:
                return run
        return None

    def _editorial_weights(self) -> dict[str, Decimal] | None:
        sensitivities = self.methodology.get("sensitivities", {})
        if sensitivities is not None and not isinstance(sensitivities, Mapping):
            raise CalculationError("methodology.sensitivities must be an object")
        raw = None
        if isinstance(sensitivities, Mapping):
            raw = sensitivities.get("editorial_weights")
        if raw is None:
            raw = self.methodology.get("editorial_weights")
        if raw is None:
            return None
        if isinstance(raw, Mapping) and "profile_counts" in raw:
            raw = raw.get("profile_counts")
        if isinstance(raw, list):
            if len(raw) != len(self.profile_ids):
                raise CalculationError("editorial_weights list length must match methodology.profiles")
            raw = dict(zip(self.profile_ids, raw))
        if not isinstance(raw, Mapping):
            raise CalculationError("editorial_weights must be an object or ordered list")
        return _normalize_weights(raw, self.profile_ids, "editorial_weights")

    def _payload_variants(self) -> list[str]:
        sensitivities = self.methodology.get("sensitivities", {})
        variants: Any = None
        if isinstance(sensitivities, Mapping):
            variants = sensitivities.get("payload_size_variants")
            grid = sensitivities.get("payload_size_grid")
            if variants is None and grid is not None:
                if not isinstance(grid, list):
                    raise CalculationError("sensitivities.payload_size_grid must be a list")
                variants = [
                    cell.get("id")
                    for cell in grid
                    if isinstance(cell, Mapping) and cell.get("headline") is not True
                ]
            factors = sensitivities.get("payload_size_factors")
            if variants is None and factors is not None:
                if not isinstance(factors, list):
                    raise CalculationError("sensitivities.payload_size_factors must be a list")
                parsed = [_integer(value, "payload size factor", minimum=1) for value in factors]
                variants = [f"{input_factor}x{output_factor}" for input_factor, output_factor in product(parsed, repeat=2)]
        if variants is None:
            return []
        if not isinstance(variants, list) or not all(isinstance(value, str) and value for value in variants):
            raise CalculationError("payload size variants must be non-empty strings")
        return sorted(set(variants) - {"100x100"})

    @staticmethod
    def _condense(series: Mapping[str, Any]) -> dict[str, Any]:
        condensed_weeks = []
        weeks = series["weeks"]
        for index, week in enumerate(weeks):
            previous = weeks[index - 1] if index >= 1 else None
            previous_comparable = (
                previous is not None
                and previous["complete"]
                and week["complete"]
                and week["cutoff"] - previous["cutoff"] == timedelta(days=7)
                and previous["representative_profile_cost"] != 0
            )
            four_week_reference = weeks[index - 4] if index >= 4 else None
            four_week_comparable = (
                four_week_reference is not None
                and four_week_reference["complete"]
                and week["complete"]
                and week["cutoff"] - four_week_reference["cutoff"]
                == timedelta(days=28)
                and four_week_reference["representative_profile_cost"] != 0
            )
            year_reference = weeks[index - 52] if index >= 52 else None
            year_comparable = (
                year_reference is not None
                and year_reference["complete"]
                and week["complete"]
                and week["cutoff"] - year_reference["cutoff"]
                == timedelta(days=364)
                and year_reference["representative_profile_cost"] != 0
            )
            if not week["complete"]:
                release_status = "withheld_incomplete"
            elif week["status"] == "withheld_concentration":
                release_status = "withheld_concentration"
            elif week["status"] == "research_only":
                release_status = "research_only"
            else:
                release_status = "publishable"
            condensed_weeks.append(
                {
                    "week_id": week["id"],
                    "cutoff_at": week["cutoff_at"],
                    "calculation_status": (
                        "complete" if week["complete"] else "incomplete"
                    ),
                    "release_status": release_status,
                    "basket_unit_cost": week["representative_profile_cost"],
                    "basket_60_cost": week["basket_cost"],
                    "index_level": week["index"],
                    "geometric_index": week["sensitivities"]["geometric_index"],
                    "frontier_index": week["sensitivities"]["frontier_index"],
                    "mean_three_index": week["sensitivities"]["mean_three_index"],
                    "week_over_week_percent": (
                        _HUNDRED
                        * (
                            week["representative_profile_cost"]
                            / previous["representative_profile_cost"]
                            - Decimal(1)
                        )
                        if previous_comparable
                        else None
                    ),
                    "four_week_percent": (
                        _HUNDRED
                        * (
                            week["representative_profile_cost"]
                            / four_week_reference["representative_profile_cost"]
                            - Decimal(1)
                        )
                        if four_week_comparable
                        else None
                    ),
                    "since_base_percent": (
                        week["index"] - _HUNDRED
                        if week["index"] is not None
                        else None
                    ),
                    "year_over_year_percent": (
                        _HUNDRED
                        * (
                            week["representative_profile_cost"]
                            / year_reference["representative_profile_cost"]
                            - Decimal(1)
                        )
                        if year_comparable
                        else None
                    ),
                    "concentration": week["concentration"],
                }
            )
        return {
            "status": series["status"],
            "threshold": series["threshold"],
            "weights": series["weights"],
            "size_variant": series["size_variant"],
            "base_period": series["base_period"],
            "weeks": condensed_weeks,
        }

    @staticmethod
    def _payload_size_range(
        series_by_variant: Mapping[str, Mapping[str, Any]],
    ) -> dict[str, Any]:
        variant_ids = sorted(series_by_variant)
        if not variant_ids:
            return {
                "status": "not_configured",
                "cell_count": 0,
                "variant_ids": [],
                "weeks": [],
                "latest": None,
            }
        reference_weeks = series_by_variant[variant_ids[0]]["weeks"]
        range_weeks = []
        for index, reference_week in enumerate(reference_weeks):
            values: dict[str, Decimal] = {}
            missing_variant_ids = []
            for variant_id in variant_ids:
                variant_weeks = series_by_variant[variant_id]["weeks"]
                if (
                    len(variant_weeks) != len(reference_weeks)
                    or variant_weeks[index]["id"] != reference_week["id"]
                ):
                    raise CalculationError(
                        "payload-size sensitivity weeks are not aligned"
                    )
                value = variant_weeks[index]["index"]
                if value is None:
                    missing_variant_ids.append(variant_id)
                else:
                    values[variant_id] = value
            if values:
                minimum = min(values.values())
                maximum = max(values.values())
                minimum_variants = sorted(
                    variant_id
                    for variant_id, value in values.items()
                    if value == minimum
                )
                maximum_variants = sorted(
                    variant_id
                    for variant_id, value in values.items()
                    if value == maximum
                )
            else:
                minimum = maximum = None
                minimum_variants = maximum_variants = []
            range_weeks.append(
                {
                    "week_id": reference_week["id"],
                    "cutoff_at": reference_week["cutoff_at"],
                    "calculation_status": (
                        "complete"
                        if not missing_variant_ids
                        else "incomplete"
                    ),
                    "expected_cell_count": len(variant_ids),
                    "available_cell_count": len(values),
                    "missing_variant_ids": missing_variant_ids,
                    "minimum_index_level": minimum,
                    "minimum_variant_ids": minimum_variants,
                    "maximum_index_level": maximum,
                    "maximum_variant_ids": maximum_variants,
                    "range_index_points": (
                        maximum - minimum
                        if minimum is not None and maximum is not None
                        else None
                    ),
                }
            )
        return {
            "status": (
                "complete"
                if all(
                    week["calculation_status"] == "complete"
                    for week in range_weeks
                )
                else "incomplete"
            ),
            "cell_count": len(variant_ids),
            "variant_ids": variant_ids,
            "weeks": range_weeks,
            "latest": range_weeks[-1],
        }

    def _robustness_record(self, series: Mapping[str, Any]) -> dict[str, Any]:
        record = self._condense(series)
        incomplete_week_ids = [
            str(week["id"])
            for week in series["weeks"]
            if not week.get("complete")
        ]
        record["structural_fragility"] = bool(incomplete_week_ids) or series.get(
            "status"
        ) == "pending_base"
        record["incomplete_week_ids"] = incomplete_week_ids
        return record

    def calculate(self, capability_threshold: Any, weights_override: Mapping[str, Any] | None) -> dict[str, Any]:
        if capability_threshold is None:
            capability_threshold = self.capability.get("headline_threshold", "130")
        threshold = _decimal(capability_threshold, "capability_threshold")
        if threshold < 0:
            raise CalculationError("capability_threshold cannot be negative")
        weights = self._weights(weights_override)
        primary = self._run(threshold, weights, "100x100")

        sensitivity_results: dict[str, Any] = {
            "capability_thresholds": {},
            "editorial_weights": None,
            "payload_sizes": {},
            "payload_size_range": None,
            "leave_one_task_out": {},
            "leave_one_provider_out": {},
            "leave_one_creator_out": {},
            "first_party_only": None,
            "constant_universe": None,
        }
        thresholds = self.capability.get("sensitivity_thresholds", ["125", "130", "135"])
        if not isinstance(thresholds, list):
            raise CalculationError("capability.sensitivity_thresholds must be a list")
        threshold_set = {_decimal(item, "capability sensitivity threshold") for item in thresholds}
        threshold_set.add(threshold)
        for value in sorted(threshold_set):
            if value == threshold:
                series = primary
            else:
                series = self._run(value, weights, "100x100")
            sensitivity_results["capability_thresholds"][_decimal_text(value)] = self._condense(series)

        editorial_weights = self._editorial_weights()
        if editorial_weights is not None:
            sensitivity_results["editorial_weights"] = self._condense(self._run(threshold, editorial_weights, "100x100"))
        payload_series = {}
        for variant in self._payload_variants():
            series = self._run(threshold, weights, variant)
            payload_series[variant] = series
            sensitivity_results["payload_sizes"][variant] = self._condense(series)
        sensitivity_results["payload_size_range"] = self._payload_size_range(
            payload_series
        )

        for excluded_profile_id in self.profile_ids:
            active_ids = [
                profile_id
                for profile_id in self.profile_ids
                if profile_id != excluded_profile_id
            ]
            active_total = sum(weights[profile_id] for profile_id in active_ids)
            subset_weights = {
                profile_id: weights[profile_id] / active_total
                for profile_id in active_ids
            }
            series = self._run(
                threshold,
                subset_weights,
                "100x100",
                active_profile_ids=active_ids,
            )
            sensitivity_results["leave_one_task_out"][excluded_profile_id] = (
                self._robustness_record(series)
            )

        for provider_id in sorted(self.providers):
            series = self._run(
                threshold,
                weights,
                "100x100",
                excluded_provider_ids=frozenset({provider_id}),
            )
            sensitivity_results["leave_one_provider_out"][provider_id] = (
                self._robustness_record(series)
            )

        for creator_id in sorted(self.creators):
            series = self._run(
                threshold,
                weights,
                "100x100",
                excluded_creator_ids=frozenset({creator_id}),
            )
            sensitivity_results["leave_one_creator_out"][creator_id] = (
                self._robustness_record(series)
            )

        sensitivity_results["first_party_only"] = self._robustness_record(
            self._run(threshold, weights, "100x100", first_party_only=True)
        )
        first_cutoff = self.weeks[0]["cutoff"]
        constant_endpoint_ids = frozenset(
            endpoint_id
            for endpoint_id, endpoint in self.endpoints.items()
            if _timestamp(
                endpoint.get("available_from"),
                f"endpoint {endpoint_id}.available_from",
            )
            <= first_cutoff
        )
        constant_universe = self._robustness_record(
            self._run(
                threshold,
                weights,
                "100x100",
                allowed_endpoint_ids=constant_endpoint_ids,
            )
        )
        constant_universe["frozen_endpoint_ids"] = sorted(constant_endpoint_ids)
        sensitivity_results["constant_universe"] = constant_universe

        latest = primary["weeks"][-1]
        if primary["status"] == "pending_base":
            overall_status = "pending_base"
        else:
            overall_status = latest["status"]
        if primary["base_period"]["status"] == "complete":
            base_week_id_set = set(primary["base_period"]["week_ids"])
            base_release_usable = all(
                week["concentration"]["status"] != "withhold"
                for week in primary["weeks"]
                if week["id"] in base_week_id_set
            )
            public_base = {
                "status": "complete",
                "release_usable": base_release_usable,
                "diagnostic_only": not base_release_usable,
                "required_weeks": primary["base_period"]["required_weeks"],
                "week_ids": primary["base_period"]["week_ids"],
                "start_cutoff_at": primary["base_period"]["start_cutoff_at"],
                "end_cutoff_at": primary["base_period"]["end_cutoff_at"],
                "profile_mean_costs": primary["base_period"]["profile_means"],
                "basket_mean_cost": primary["base_period"]["representative_profile_cost"],
                "basket_60_mean_cost": primary["base_period"]["basket_cost"],
                "frontier_basket_mean_cost": primary["base_period"]["frontier_representative_profile_cost"],
                "mean_three_basket_mean_cost": primary["base_period"]["mean_three_representative_profile_cost"],
            }
        else:
            public_base = {
                "status": "pending",
                "release_usable": False,
                "diagnostic_only": False,
                "required_weeks": primary["base_period"]["required_weeks"],
                "week_ids": [],
                "profile_mean_costs": {},
                "basket_mean_cost": None,
                "basket_60_mean_cost": None,
                "frontier_basket_mean_cost": None,
                "mean_three_basket_mean_cost": None,
            }

        public_weeks = []
        for week_index, week in enumerate(primary["weeks"]):
            previous_week = primary["weeks"][week_index - 1] if week_index >= 1 else None
            previous_comparable = (
                previous_week is not None
                and previous_week["complete"]
                and week["complete"]
                and week["cutoff"] - previous_week["cutoff"] == timedelta(days=7)
            )
            public_profiles = []
            week_diagnostics: list[dict[str, Any]] = []
            for profile_id in self.profile_ids:
                profile = week["profiles"][profile_id]
                if profile["status"] == "complete":
                    candidate_costs = {
                        str(candidate["endpoint_id"]): candidate["cost"]
                        for candidate in profile["candidates"]
                    }
                    public_profile = {
                        "profile_id": profile_id,
                        "calculation_status": "complete",
                        "selected_triple_endpoint_ids": profile["selected_triple_endpoint_ids"],
                        "selected_triple_costs": [candidate_costs[endpoint_id] for endpoint_id in profile["selected_triple_endpoint_ids"]],
                        "selected_cost_order": profile["selected_cost_order"],
                        "selected_total_cost": profile["selected_total_cost"],
                        "price_setter_endpoint_id": profile["price_setter_endpoint_id"],
                        "price_setter_provider_id": profile["price_setter_provider_id"],
                        "price_setter_creator_id": profile["price_setter_creator_id"],
                        "headline_price": profile["price"],
                        "weight": weights[profile_id],
                        "contribution_percentage_points": (
                            _HUNDRED
                            * weights[profile_id]
                            * (profile["price"] - previous_week["profiles"][profile_id]["price"])
                            / previous_week["representative_profile_cost"]
                            if previous_comparable and previous_week["representative_profile_cost"] != 0
                            else None
                        ),
                        "frontier_price": profile["frontier_price"],
                        "frontier_endpoint_id": profile["frontier_endpoint_id"],
                        "mean_three_price": profile["mean_three_price"],
                        "mean_three_endpoint_ids": profile["mean_three_endpoint_ids"],
                        "observation_ids": profile["lineage"]["observation_ids"],
                        "source_ids": profile["lineage"]["source_ids"],
                        "lineage": profile["lineage"],
                        "selected_lineage": profile["selected_lineage"],
                        "eligible_candidates": [
                            {
                                "endpoint_id": candidate["endpoint_id"],
                                "provider_id": candidate["provider_id"],
                                "creator_id": candidate["creator_id"],
                                "cost": candidate["cost"],
                                "input_cost": candidate["input_cost"],
                                "output_cost": candidate["output_cost"],
                                "input_tokens": candidate["input_tokens"],
                                "output_tokens": candidate["output_tokens"],
                                "input_price_per_million": candidate["input_price_per_million"],
                                "output_price_per_million": candidate["output_price_per_million"],
                                "observation_ids": candidate["lineage"]["observation_ids"],
                                "source_ids": candidate["lineage"]["source_ids"],
                            }
                            for candidate in profile["candidates"]
                        ],
                        "diagnostics": [],
                        "endpoint_exclusions": profile["exclusions"],
                    }
                else:
                    public_profile = {
                        "profile_id": profile_id,
                        "calculation_status": "incomplete",
                        "selected_triple_endpoint_ids": [],
                        "selected_triple_costs": [],
                        "selected_cost_order": [],
                        "selected_total_cost": None,
                        "price_setter_endpoint_id": None,
                        "price_setter_provider_id": None,
                        "price_setter_creator_id": None,
                        "headline_price": None,
                        "weight": weights[profile_id],
                        "contribution_percentage_points": None,
                        "frontier_price": None,
                        "frontier_endpoint_id": None,
                        "mean_three_price": None,
                        "mean_three_endpoint_ids": [],
                        "observation_ids": [],
                        "source_ids": [],
                        "lineage": {},
                        "selected_lineage": {},
                        "eligible_candidates": [],
                        "diagnostics": profile["diagnostics"],
                        "endpoint_exclusions": profile["exclusions"],
                    }
                    week_diagnostics.append({"profile_id": profile_id, "reasons": profile["diagnostics"]})
                public_profiles.append(public_profile)

            if not week["complete"]:
                release_status = "withheld_incomplete"
            elif week["concentration"]["status"] == "withhold":
                release_status = "withheld_concentration"
                week_diagnostics.append({"concentration_withholds": week["concentration"]["withholds"]})
            elif self.evidence_mode == "research":
                release_status = "research_only"
            else:
                release_status = "publishable"
            week_over_week = (
                _HUNDRED * (week["representative_profile_cost"] / previous_week["representative_profile_cost"] - Decimal(1))
                if previous_comparable and previous_week["representative_profile_cost"] != 0
                else None
            )
            four_week_reference = primary["weeks"][week_index - 4] if week_index >= 4 else None
            four_week_comparable = (
                four_week_reference is not None
                and four_week_reference["complete"]
                and week["complete"]
                and week["cutoff"] - four_week_reference["cutoff"] == timedelta(days=28)
                and four_week_reference["representative_profile_cost"] != 0
            )
            year_reference = primary["weeks"][week_index - 52] if week_index >= 52 else None
            year_comparable = (
                year_reference is not None
                and year_reference["complete"]
                and week["complete"]
                and week["cutoff"] - year_reference["cutoff"] == timedelta(days=364)
                and year_reference["representative_profile_cost"] != 0
            )
            public_weeks.append(
                {
                    "week_id": week["id"],
                    "cutoff_at": week["cutoff_at"],
                    "calculation_status": "complete" if week["complete"] else "incomplete",
                    "release_status": release_status,
                    "basket_unit_cost": week["representative_profile_cost"],
                    "basket_60_cost": week["basket_cost"],
                    "index_level": week["index"],
                    "week_over_week_percent": week_over_week,
                    "four_week_percent": (
                        _HUNDRED * (week["representative_profile_cost"] / four_week_reference["representative_profile_cost"] - Decimal(1))
                        if four_week_comparable
                        else None
                    ),
                    "since_base_percent": week["index"] - _HUNDRED if week["index"] is not None else None,
                    "year_over_year_percent": (
                        _HUNDRED * (week["representative_profile_cost"] / year_reference["representative_profile_cost"] - Decimal(1))
                        if year_comparable
                        else None
                    ),
                    "geometric_index": week["sensitivities"]["geometric_index"],
                    "frontier_index": week["sensitivities"]["frontier_index"],
                    "mean_three_index": week["sensitivities"]["mean_three_index"],
                    "profiles": public_profiles,
                    "concentration": week["concentration"],
                    "lineage": week["lineage"],
                    "diagnostics": week_diagnostics,
                }
            )

        dataset_kind = self.bundle.get("dataset_kind")
        notice, citation_text = artifact_notice(dataset_kind)
        if dataset_kind == "synthetic":
            series_type = f"synthetic_{self.evidence_mode}_policy_simulation"
        else:
            series_type = (
                "official_observed"
                if self.evidence_mode == "official"
                else "research_reconstruction"
            )
        result = {
            "notice": notice,
            "not_for_publication": True,
            "published": False,
            "deployed": False,
            "citation": {
                "permitted": False,
                "text": citation_text,
            },
            "schema_version": self.bundle.get("schema_version"),
            "dataset_id": self.bundle.get("dataset_id"),
            "dataset_kind": dataset_kind,
            "methodology_id": self.methodology.get("methodology_id"),
            "methodology_version": self.methodology.get("version"),
            "version": self.methodology.get("version"),
            "series_type": series_type,
            "evidence_mode": self.evidence_mode,
            "capability_threshold": threshold,
            "size_variant": "100x100",
            "weights": weights,
            "basket_profile_count": self.total_profile_count,
            "status": overall_status,
            "base_period": public_base,
            "weeks": public_weeks,
            "sensitivities": sensitivity_results,
        }
        return _json_ready(result)


def calculate_index(
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
    *,
    evidence_mode: str = "official",
    capability_threshold: Any = None,
    weights: Mapping[str, Any] | None = None,
) -> dict[str, Any]:
    """Calculate a deterministic KAPI series from an in-memory frozen bundle.

    Calculation-level failures are represented by explicit withheld/pending
    states in the returned dictionary.  ``CalculationError`` is reserved for
    malformed or contradictory input contracts that cannot be interpreted
    deterministically.
    """

    with localcontext() as context:
        context.prec = _PRECISION
        return _Calculator(bundle, methodology, evidence_mode).calculate(capability_threshold, weights)


def chain_link(prior_index: Any, new_current_cost: Any, new_overlap_cost: Any) -> Decimal:
    """Prospectively chain-link a new basket at its overlap week."""

    with localcontext() as context:
        context.prec = _PRECISION
        prior = _decimal(prior_index, "prior_index")
        current = _decimal(new_current_cost, "new_current_cost")
        overlap = _decimal(new_overlap_cost, "new_overlap_cost")
        if prior < 0 or current < 0:
            raise CalculationError("prior_index and new_current_cost cannot be negative")
        if overlap <= 0:
            raise CalculationError("new_overlap_cost must be positive")
        return prior * current / overlap


__all__ = ["CalculationError", "calculate_index", "chain_link"]
