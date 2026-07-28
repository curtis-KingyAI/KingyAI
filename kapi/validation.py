"""Strict structural and cross-reference validation for KAPI prototype inputs."""

from __future__ import annotations

import json
import re
import unicodedata
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass
from datetime import timedelta
from html import unescape
from pathlib import Path
from typing import Any, Iterable, Mapping
from urllib.parse import unquote, urlsplit

from .governance import CURRENT_UNREVIEWED_LABEL, POLICY_ID, POLICY_VERSION
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
MEDIA_TYPE_PATTERN = re.compile(
    r"^[A-Za-z0-9][A-Za-z0-9!#$%&'*+.^_`|~-]*/"
    r"[A-Za-z0-9][A-Za-z0-9!#$%&'*+.^_`|~-]*$"
)
CLAIM_DECODE_ROUNDS = 4
_LITERAL_CLAIM_ESCAPE_PATTERN = re.compile(
    r"\\(?:u([0-9A-Fa-f]{4})|U([0-9A-Fa-f]{8})|x([0-9A-Fa-f]{2}))"
)
GRADES = {"A", "B", "C", "D"}
PAYLOAD_FACTORS = ("0.75", "1.00", "1.25")
PAYLOAD_FACTOR_IDS = {"0.75": "075", "1.00": "100", "1.25": "125"}
CONTROLLED_METHODOLOGY_VERSIONS = {"0.2.0", "0.2.1", "0.2.2", "0.3.0"}
PORTABLE_METHODOLOGY_VERSIONS = {"0.2.2", "0.3.0"}
HISTORICAL_METHODOLOGY_VERSIONS = {"0.2.0", "0.2.1", "0.2.2"}
PINNED_METHODOLOGY_SHA256 = {
    "0.2.0": "8f9442b9cd38acd46602446a9bbcc848a29fd079dfc63fefc0cb24125eaacd59",
    "0.2.1": "1cb3cdc12139dad6a6bbaefc31f5023323d1672ba4fba69c531312f5a8a275b0",
    "0.2.2": "f75219ff27d059b7cc417ba2b2dc3d4e280ccf8e7d2ab0a2b1a38085a99a8ba8",
    "0.3.0": "85668be220af2c724ae8c1cd68cf53eeec6d547259c066acb28c8a8185a97e04",
}
HISTORICAL_BOUNDED_FIXTURE_SHA256 = (
    "6cba82133f26cf3da4642f60e0006682f8ee190517cac34e2f673be06bb9e8d7"
)
FORWARD_BUNDLE_SCHEMA_VERSION = "kapi-bundle-v0.3.0"
FORWARD_METHODOLOGY_PATH = "kapi/config/methodology-v0.3.0.json"
FORWARD_BOUNDED_FIXTURE_SHA256 = (
    "d4a6fb3a0f51da7468ea684004ad7a84b8b2159e3653b62e677abc2f36703053"
)
FORWARD_ID_PREFIXES = {
    "weeks": "week-",
    "providers": "provider-",
    "creators": "creator-",
    "models": "model-",
    "endpoints": "endpoint-",
    "source_artifacts": "source-",
    "capability_evidence": "capability-",
    "token_counts": "tokens-",
    "price_observations": "price-",
    "corrections": "correction-",
}


def requires_forward_governance_contract(
    bundle: Mapping[str, Any], methodology: Mapping[str, Any]
) -> bool:
    """Return whether either input attempts to enter the v0.3 governance lane.

    The decision cannot depend only on the supplied methodology version.  A
    caller could otherwise pair the canonical v0.3 bundle with an older valid
    methodology and silently disable every forward-only guard.  Forward bundle
    identity markers are included as defense in depth so changing only the
    schema/version strings cannot downgrade the bounded fixture either.
    """

    schema_version = bundle.get("schema_version")
    methodology_version = methodology.get("version")
    binding = bundle.get("methodology")
    return bool(
        (
            isinstance(schema_version, str)
            and schema_version.startswith("kapi-bundle-v0.3")
        )
        or (
            isinstance(methodology_version, str)
            and methodology_version.startswith("0.3")
        )
        or bundle.get("dataset_id") == "synthetic-forward-governance-v0.3.0"
        or (
            isinstance(binding, Mapping)
            and (
                binding.get("version") == "0.3.0"
                or binding.get("config_path") == FORWARD_METHODOLOGY_PATH
            )
        )
    )
FORWARD_BUNDLE_FIELDS = frozenset(
    {
        "schema_version",
        "dataset_id",
        "dataset_kind",
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
        "methodology",
    }
)
FORWARD_CAPABILITY_SYNTHETIC_CONTENT_FIELDS = frozenset(
    {
        "capability_scope",
        "configuration_id",
        "dataset_kind",
        "endpoint_id",
        "metric",
        "metric_version",
        "model_calls_performed",
        "network_access_used",
        "score",
        "score_is_configuration_specific",
    }
)
FORWARD_PRICE_SYNTHETIC_CONTENT_FIELDS = frozenset(
    {
        "currency",
        "dataset_kind",
        "endpoint_id",
        "input_amount_per_million",
        "model_calls_performed",
        "network_access_used",
        "output_amount_per_million",
        "regime",
        "tier",
        "unit",
        "week_id",
    }
)
FORWARD_BUNDLE_OBJECT_FIELDS = {
    "bundle": FORWARD_BUNDLE_FIELDS,
    "bundle.weeks[]": frozenset({"cutoff_at", "id"}),
    "bundle.providers[]": frozenset({"id", "name", "synthetic"}),
    "bundle.creators[]": frozenset({"id", "name", "synthetic"}),
    "bundle.models[]": frozenset(
        {"alias_type", "creator_id", "id", "immutable_version", "version"}
    ),
    "bundle.endpoints[]": frozenset(
        {
            "available_from",
            "available_until",
            "available_us",
            "billing_tokenizer",
            "configuration_id",
            "construction_tokenizer",
            "features",
            "ga",
            "id",
            "model_id",
            "on_demand",
            "provider_id",
            "public",
            "reasoning_mode",
            "region",
            "standard_list_price",
            "synchronous",
            "tier",
            "tokenizer_reproducible",
        }
    ),
    "bundle.source_artifacts[]": frozenset(
        {
            "content_sha256",
            "evidence_grade",
            "id",
            "license_note",
            "media_type",
            "retrieved_at",
            "snapshot_path",
            "synthetic_content",
            "url",
        }
    ),
    "bundle.source_artifacts[].synthetic_content": (
        FORWARD_CAPABILITY_SYNTHETIC_CONTENT_FIELDS
        | FORWARD_PRICE_SYNTHETIC_CONTENT_FIELDS
    ),
    "bundle.capability_evidence[]": frozenset(
        {
            "configuration_id",
            "data_vintage",
            "endpoint_id",
            "evaluated_at",
            "evidence_grade",
            "id",
            "metric",
            "metric_version",
            "model_id",
            "score",
            "score_is_configuration_specific",
            "source_id",
        }
    ),
    "bundle.token_counts[]": frozenset(
        {
            "billing_tokenizer",
            "billing_usage_count_status",
            "construction_count_evidence_class",
            "construction_tokenizer",
            "endpoint_id",
            "id",
            "input_payload_path",
            "input_payload_sha256",
            "input_tokens",
            "output_payload_path",
            "output_payload_sha256",
            "output_tokens",
            "profile_id",
            "size_variant",
            "synthetic_count_note",
        }
    ),
    "bundle.price_observations[]": frozenset(
        {
            "amount_per_million",
            "component",
            "context_max_tokens",
            "context_min_tokens",
            "currency",
            "effective_at",
            "endpoint_id",
            "evidence_grade",
            "id",
            "observed_at",
            "region",
            "source_id",
            "supersedes_observation_id",
            "tier",
            "unit",
            "week_id",
        }
    ),
    "bundle.corrections[]": frozenset(
        {
            "detected_at",
            "id",
            "impact",
            "new_vintage",
            "replacement_observation_id",
            "resolution",
            "superseded_observation_id",
            "supersedes_correction_id",
        }
    ),
    "bundle.methodology": frozenset(
        {"config_path", "config_sha256", "id", "version"}
    ),
}

# v0.3 is a bounded synthetic governance fixture, not an open-ended ingestion
# format. Every string that can be copied into a release is therefore either a
# closed value or a narrowly specified machine grammar. New prose/status paths
# require a new reviewed schema vintage instead of silently becoming assertion
# carriers in the current one.
_FORWARD_SAFE_LICENSE_NOTE = (
    "Synthetic fixture evidence created locally for validation; not a real "
    "provider or benchmark source."
)
_FORWARD_SAFE_COUNT_NOTE = (
    "Exact local construction count under explicit o200k_base chunk "
    "construction; not a provider preflight count, not billing usage, and not "
    "obtained from a model call."
)
_FORWARD_PROFILES = (
    "analysis-reasoning|code-repair|grounded-rag|structured-extraction|"
    "summarization-transformation|tool-workflow"
)
_FORWARD_TIMESTAMP_PATTERN = r"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z"
_FORWARD_WEEK_ID_PATTERN = r"week-\d{4}-\d{2}-\d{2}"
_FORWARD_PRICE_ID_PATTERN = (
    r"price-\d{4}-\d{2}-\d{2}-[a-d]-(?:input|output)"
)
_FORWARD_CORRECTION_ID_PATTERN = (
    r"correction-\d{4}-\d{2}-\d{2}-[0-9]+"
)
FORWARD_EXACT_STRING_VALUES: dict[str, frozenset[str]] = {
    "bundle.schema_version": frozenset({FORWARD_BUNDLE_SCHEMA_VERSION}),
    "bundle.dataset_id": frozenset({"synthetic-forward-governance-v0.3.0"}),
    "bundle.dataset_kind": frozenset({"synthetic"}),
    "bundle.providers[].name": frozenset(
        {f"Synthetic Provider {letter}" for letter in "ABCD"}
    ),
    "bundle.creators[].name": frozenset(
        {f"Synthetic Creator {letter}" for letter in "ABCD"}
    ),
    "bundle.models[].alias_type": frozenset({"immutable"}),
    "bundle.models[].version": frozenset(
        {f"synthetic-{letter}-1.0.0" for letter in "ABCD"}
    ),
    "bundle.endpoints[].billing_tokenizer": frozenset(
        {"provider-billing-counts-unverified"}
    ),
    "bundle.endpoints[].construction_tokenizer": frozenset(
        {"tiktoken-0.13.0:o200k_base(explicit_construction)"}
    ),
    "bundle.endpoints[].features[]": frozenset(
        {"function_calling", "structured_output", "text_input", "text_output"}
    ),
    "bundle.endpoints[].reasoning_mode": frozenset({"disabled"}),
    "bundle.endpoints[].region": frozenset({"US"}),
    "bundle.endpoints[].tier": frozenset({"standard"}),
    "bundle.source_artifacts[].evidence_grade": frozenset({"A"}),
    "bundle.source_artifacts[].license_note": frozenset(
        {_FORWARD_SAFE_LICENSE_NOTE}
    ),
    "bundle.source_artifacts[].media_type": frozenset({"application/json"}),
    "bundle.source_artifacts[].synthetic_content.capability_scope": frozenset(
        {"model_level_or_best_across_settings_coarse_screen"}
    ),
    "bundle.source_artifacts[].synthetic_content.currency": frozenset({"USD"}),
    "bundle.source_artifacts[].synthetic_content.dataset_kind": frozenset(
        {"synthetic"}
    ),
    "bundle.source_artifacts[].synthetic_content.metric": frozenset({"ECI"}),
    "bundle.source_artifacts[].synthetic_content.metric_version": frozenset(
        {"synthetic-eci-v1"}
    ),
    "bundle.source_artifacts[].synthetic_content.regime": frozenset(
        {"base", "current"}
    ),
    "bundle.source_artifacts[].synthetic_content.tier": frozenset({"standard"}),
    "bundle.source_artifacts[].synthetic_content.unit": frozenset(
        {"per_million_native_tokens"}
    ),
    "bundle.capability_evidence[].data_vintage": frozenset(
        {"synthetic-eci-2026-04-02"}
    ),
    "bundle.capability_evidence[].evidence_grade": frozenset({"A"}),
    "bundle.capability_evidence[].metric": frozenset({"ECI"}),
    "bundle.capability_evidence[].metric_version": frozenset(
        {"synthetic-eci-v1"}
    ),
    "bundle.token_counts[].billing_tokenizer": frozenset(
        {"provider-billing-counts-unverified"}
    ),
    "bundle.token_counts[].billing_usage_count_status": frozenset(
        {"unverified_no_provider_call"}
    ),
    "bundle.token_counts[].construction_count_evidence_class": frozenset(
        {"construction_count"}
    ),
    "bundle.token_counts[].construction_tokenizer": frozenset(
        {"tiktoken-0.13.0:o200k_base(explicit_construction)"}
    ),
    "bundle.token_counts[].profile_id": frozenset(_FORWARD_PROFILES.split("|")),
    "bundle.token_counts[].size_variant": frozenset(
        {
            f"{input_factor}x{output_factor}"
            for input_factor in ("075", "100", "125")
            for output_factor in ("075", "100", "125")
        }
    ),
    "bundle.token_counts[].synthetic_count_note": frozenset(
        {_FORWARD_SAFE_COUNT_NOTE}
    ),
    "bundle.price_observations[].component": frozenset({"input", "output"}),
    "bundle.price_observations[].currency": frozenset({"USD"}),
    "bundle.price_observations[].evidence_grade": frozenset({"A"}),
    "bundle.price_observations[].region": frozenset({"US"}),
    "bundle.price_observations[].tier": frozenset({"standard"}),
    "bundle.price_observations[].unit": frozenset(
        {"per_million_native_tokens"}
    ),
    "bundle.corrections[].impact": frozenset(
        {
            "current_index_recalculation_required",
            "historical_index_recalculation_required",
            "metadata_only_no_index_change",
        }
    ),
    "bundle.corrections[].resolution": frozenset(
        {"replacement_observation_supersedes_target"}
    ),
    "bundle.methodology.config_path": frozenset({FORWARD_METHODOLOGY_PATH}),
    "bundle.methodology.id": frozenset({"kapi-sw-methodology"}),
    "bundle.methodology.version": frozenset({"0.3.0"}),
}
FORWARD_STRING_PATTERNS: dict[str, re.Pattern[str]] = {
    "bundle.weeks[].cutoff_at": re.compile(_FORWARD_TIMESTAMP_PATTERN),
    "bundle.weeks[].id": re.compile(_FORWARD_WEEK_ID_PATTERN),
    "bundle.providers[].id": re.compile(r"provider-[a-d]"),
    "bundle.creators[].id": re.compile(r"creator-[a-d]"),
    "bundle.models[].creator_id": re.compile(r"creator-[a-d]"),
    "bundle.models[].id": re.compile(r"model-[a-d]-v1"),
    "bundle.endpoints[].available_from": re.compile(_FORWARD_TIMESTAMP_PATTERN),
    "bundle.endpoints[].available_until": re.compile(_FORWARD_TIMESTAMP_PATTERN),
    "bundle.endpoints[].configuration_id": re.compile(
        r"config-[a-d]-reasoning-disabled"
    ),
    "bundle.endpoints[].id": re.compile(r"endpoint-[a-d]-v1"),
    "bundle.endpoints[].model_id": re.compile(r"model-[a-d]-v1"),
    "bundle.endpoints[].provider_id": re.compile(r"provider-[a-d]"),
    "bundle.source_artifacts[].content_sha256": SHA256_PATTERN,
    "bundle.source_artifacts[].id": re.compile(
        r"source-(?:capability-[a-d]|price-\d{4}-\d{2}-\d{2}-[a-d])"
    ),
    "bundle.source_artifacts[].retrieved_at": re.compile(
        _FORWARD_TIMESTAMP_PATTERN
    ),
    "bundle.source_artifacts[].snapshot_path": re.compile(
        r"embedded://source_artifacts/source-(?:capability-[a-d]|"
        r"price-\d{4}-\d{2}-\d{2}-[a-d])"
    ),
    "bundle.source_artifacts[].url": re.compile(
        r"synthetic://(?:capability/endpoint-[a-d]-v1|"
        r"prices/endpoint-[a-d]-v1/week-\d{4}-\d{2}-\d{2})"
    ),
    "bundle.source_artifacts[].synthetic_content.configuration_id": re.compile(
        r"config-[a-d]-reasoning-disabled"
    ),
    "bundle.source_artifacts[].synthetic_content.endpoint_id": re.compile(
        r"endpoint-[a-d]-v1"
    ),
    "bundle.source_artifacts[].synthetic_content.input_amount_per_million": re.compile(
        r"(?:0|[1-9][0-9]*)(?:\.[0-9]+)?"
    ),
    "bundle.source_artifacts[].synthetic_content.output_amount_per_million": re.compile(
        r"(?:0|[1-9][0-9]*)(?:\.[0-9]+)?"
    ),
    "bundle.source_artifacts[].synthetic_content.score": re.compile(
        r"(?:0|[1-9][0-9]*)(?:\.[0-9]+)?"
    ),
    "bundle.source_artifacts[].synthetic_content.week_id": re.compile(
        _FORWARD_WEEK_ID_PATTERN
    ),
    "bundle.capability_evidence[].configuration_id": re.compile(
        r"config-[a-d]-reasoning-disabled"
    ),
    "bundle.capability_evidence[].endpoint_id": re.compile(r"endpoint-[a-d]-v1"),
    "bundle.capability_evidence[].evaluated_at": re.compile(
        _FORWARD_TIMESTAMP_PATTERN
    ),
    "bundle.capability_evidence[].id": re.compile(r"capability-[a-d]"),
    "bundle.capability_evidence[].model_id": re.compile(r"model-[a-d]-v1"),
    "bundle.capability_evidence[].score": re.compile(
        r"(?:0|[1-9][0-9]*)(?:\.[0-9]+)?"
    ),
    "bundle.capability_evidence[].source_id": re.compile(r"source-capability-[a-d]"),
    "bundle.token_counts[].endpoint_id": re.compile(r"endpoint-[a-d]-v1"),
    "bundle.token_counts[].id": re.compile(
        rf"tokens-endpoint-[a-d]-v1-(?:{_FORWARD_PROFILES})-"
        r"(?:075|100|125)x(?:075|100|125)"
    ),
    "bundle.token_counts[].input_payload_path": re.compile(
        rf"kapi/profiles/(?:{_FORWARD_PROFILES})/input-(?:075|100|125)\.json"
    ),
    "bundle.token_counts[].input_payload_sha256": SHA256_PATTERN,
    "bundle.token_counts[].output_payload_path": re.compile(
        rf"kapi/profiles/(?:{_FORWARD_PROFILES})/output-(?:075|100|125)\.json"
    ),
    "bundle.token_counts[].output_payload_sha256": SHA256_PATTERN,
    "bundle.price_observations[].amount_per_million": re.compile(
        r"(?:0|[1-9][0-9]*)(?:\.[0-9]+)?"
    ),
    "bundle.price_observations[].effective_at": re.compile(
        _FORWARD_TIMESTAMP_PATTERN
    ),
    "bundle.price_observations[].endpoint_id": re.compile(r"endpoint-[a-d]-v1"),
    "bundle.price_observations[].id": re.compile(_FORWARD_PRICE_ID_PATTERN),
    "bundle.price_observations[].observed_at": re.compile(
        _FORWARD_TIMESTAMP_PATTERN
    ),
    "bundle.price_observations[].source_id": re.compile(
        r"source-price-\d{4}-\d{2}-\d{2}-[a-d]"
    ),
    "bundle.price_observations[].supersedes_observation_id": re.compile(
        _FORWARD_PRICE_ID_PATTERN
    ),
    "bundle.price_observations[].week_id": re.compile(_FORWARD_WEEK_ID_PATTERN),
    "bundle.corrections[].detected_at": re.compile(_FORWARD_TIMESTAMP_PATTERN),
    "bundle.corrections[].id": re.compile(_FORWARD_CORRECTION_ID_PATTERN),
    "bundle.corrections[].new_vintage": re.compile(
        r"vintage-\d{4}-\d{2}-\d{2}"
    ),
    "bundle.corrections[].replacement_observation_id": re.compile(
        _FORWARD_PRICE_ID_PATTERN
    ),
    "bundle.corrections[].superseded_observation_id": re.compile(
        _FORWARD_PRICE_ID_PATTERN
    ),
    "bundle.corrections[].supersedes_correction_id": re.compile(
        _FORWARD_CORRECTION_ID_PATTERN
    ),
    "bundle.methodology.config_sha256": SHA256_PATTERN,
}
FORWARD_LIST_PATHS = frozenset(
    {
        "bundle.weeks",
        "bundle.providers",
        "bundle.creators",
        "bundle.models",
        "bundle.endpoints",
        "bundle.endpoints[].features",
        "bundle.source_artifacts",
        "bundle.capability_evidence",
        "bundle.token_counts",
        "bundle.price_observations",
        "bundle.corrections",
    }
)
FORWARD_EXACT_NON_STRING_VALUES: dict[
    str, tuple[tuple[type[Any], Any], ...]
] = {
    "bundle.providers[].synthetic": ((bool, True),),
    "bundle.creators[].synthetic": ((bool, True),),
    "bundle.models[].immutable_version": ((bool, True),),
    "bundle.endpoints[].available_us": ((bool, True),),
    "bundle.endpoints[].ga": ((bool, True),),
    "bundle.endpoints[].on_demand": ((bool, True),),
    "bundle.endpoints[].public": ((bool, True),),
    "bundle.endpoints[].standard_list_price": ((bool, True),),
    "bundle.endpoints[].synchronous": ((bool, True),),
    "bundle.endpoints[].tokenizer_reproducible": ((bool, True),),
    "bundle.source_artifacts[].synthetic_content.model_calls_performed": (
        (int, 0),
    ),
    "bundle.source_artifacts[].synthetic_content.network_access_used": (
        (bool, False),
    ),
    "bundle.source_artifacts[].synthetic_content.score_is_configuration_specific": (
        (bool, False),
    ),
    "bundle.capability_evidence[].score_is_configuration_specific": (
        (bool, False),
    ),
}
FORWARD_INTEGER_MINIMUMS: dict[str, int] = {
    "bundle.token_counts[].input_tokens": 1,
    "bundle.token_counts[].output_tokens": 1,
    "bundle.price_observations[].context_min_tokens": 0,
    "bundle.price_observations[].context_max_tokens": 0,
}
FORWARD_NULLABLE_SCALAR_PATHS = frozenset(
    {
        "bundle.endpoints[].available_until",
        "bundle.price_observations[].context_min_tokens",
        "bundle.price_observations[].context_max_tokens",
        "bundle.price_observations[].supersedes_observation_id",
        "bundle.corrections[].supersedes_correction_id",
    }
)
FORBIDDEN_INPUT_CLAIM_KEY_STEMS = frozenset(
    {
        "accredit",
        "approv",
        "assur",
        "attest",
        "audit",
        "authoriz",
        "certif",
        "clear",
        "deploy",
        "endorse",
        "golive",
        "governance",
        "independen",
        "kapi",
        "launch",
        "operator",
        "publicat",
        "publish",
        "ready",
        "review",
        "signat",
        "signedby",
        "signedoff",
        "signer",
        "signoff",
        "verif",
    }
)

# The current v0.3 bundle schema has no legitimate claim-bearing keys. Keep the
# allowlist explicit so a future schema addition must name an exact path and can
# be paired with value validation instead of weakening the recursive boundary.
ALLOWED_FORWARD_BUNDLE_CLAIM_KEY_PATHS: frozenset[str] = frozenset()


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


_CLAIM_CONFUSABLES = str.maketrans(
    {
        "а": "a",
        "ɑ": "a",
        "α": "a",
        "Ь": "b",
        "с": "c",
        "ԁ": "d",
        "е": "e",
        "ε": "e",
        "һ": "h",
        "і": "i",
        "ι": "i",
        "ј": "j",
        "κ": "k",
        "к": "k",
        "ⅼ": "l",
        "м": "m",
        "ո": "n",
        "о": "o",
        "ο": "o",
        "р": "p",
        "ρ": "p",
        "ѕ": "s",
        "τ": "t",
        "т": "t",
        "ν": "v",
        "ѵ": "v",
        "ԝ": "w",
        "х": "x",
        "χ": "x",
        "у": "y",
        "υ": "y",
    }
)


def _decode_literal_claim_escape(match: re.Match[str]) -> str:
    encoded = next(group for group in match.groups() if group is not None)
    codepoint = int(encoded, 16)
    if 0xD800 <= codepoint <= 0xDFFF or codepoint > 0x10FFFF:
        return match.group(0)
    return chr(codepoint)


def _decoded_claim_text(value: str) -> str:
    """Decode ordinary renderer/browser encodings with a strict round bound."""

    decoded = value
    for _ in range(CLAIM_DECODE_ROUNDS):
        expanded = _LITERAL_CLAIM_ESCAPE_PATTERN.sub(
            _decode_literal_claim_escape,
            unescape(unquote(decoded)),
        )
        if expanded == decoded:
            break
        decoded = expanded
    return decoded


_RESIDUAL_PERCENT_ESCAPE_PATTERN = re.compile(r"%[0-9A-Fa-f]{2}")
_RESIDUAL_HTML_ESCAPE_PATTERN = re.compile(
    r"&(?:#[0-9]+|#[xX][0-9A-Fa-f]+|[A-Za-z][A-Za-z0-9]+);"
)


def _has_residual_claim_encoding(value: str) -> bool:
    """Reject still-encoded input after the deliberately bounded decoder."""

    decoded = _decoded_claim_text(value)
    return bool(
        _RESIDUAL_PERCENT_ESCAPE_PATTERN.search(decoded)
        or _RESIDUAL_HTML_ESCAPE_PATTERN.search(decoded)
        or _LITERAL_CLAIM_ESCAPE_PATTERN.search(decoded)
    )


def _normalized_claim_text(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", _decoded_claim_text(value)).casefold()
    normalized = "".join(
        character for character in normalized if not unicodedata.combining(character)
    ).translate(_CLAIM_CONFUSABLES)
    return "".join(character for character in normalized if character.isalnum())


_NORMALIZED_FORBIDDEN_INPUT_CLAIM_KEY_STEMS = frozenset(
    _normalized_claim_text(stem) for stem in FORBIDDEN_INPUT_CLAIM_KEY_STEMS
)
_UNSCOPED_INPUT_CLAIM_VALUES = frozenset(
    {
        "approved",
        "audited",
        "authorized",
        "certified",
        "cleared",
        "deployed",
        "greenlit",
        "launched",
        "live",
        "passed",
        "published",
        "ready",
        "readytogo",
        "released",
        "reviewed",
        "reviewer",
        "signoff",
        "statusapproved",
        "statusverified",
        "verified",
    }
)


def _is_claim_bearing_text(value: str) -> bool:
    """Recognize assertion-like review/authorization prose, not isolated words.

    Evidence records legitimately use terms such as ``external``, ``verified``,
    ``reviewed``, ``audit``, and ``approved``. A value is rejected only when
    normalized signals combine into a governance/publication assertion. This
    leaves ordinary source and license prose available while closing free-form
    metadata carriers for stronger KAPI status claims.
    """

    raw_compact = _normalized_claim_text(value)
    if not raw_compact:
        return False
    if value in {
        "provider-billing-counts-unverified",
        "unverified_no_provider_call",
    }:
        return False
    ambiguous_negative = any(
        fragment in raw_compact
        for fragment in (
            "nolongernotreviewed",
            "nolongerunreviewed",
            "nolongerunverified",
            "notnotreviewed",
            "notnotverified",
            "notunreviewed",
            "notunverified",
            "notwithoutreview",
            "unreviewedno",
            "unverifiedno",
        )
    )
    if ambiguous_negative:
        return True
    scoped_technical_review = (
        "sourcehashverifiedagainstretainedbytes" in raw_compact
        and "licensereviewedforarchivalcompleteness" in raw_compact
    )
    compact = raw_compact
    for explicit_negative in (
        "notreviewed",
        "notverified",
        "unreviewed",
        "unverified",
    ):
        compact = compact.replace(explicit_negative, "")
    if not compact:
        return False
    if compact in _UNSCOPED_INPUT_CLAIM_VALUES:
        return True
    review = "review" in compact
    audit = "audit" in compact
    verified = "verif" in compact
    signoff = "signoff" in compact or "signedoff" in compact
    independent = "independen" in compact
    operator = "operator" in compact
    external = "external" in compact
    human_reviewer = any(
        fragment in compact
        for fragment in (
            "assessor",
            "consultant",
            "editor",
            "expert",
            "human",
            "outside",
            "peer",
        )
    )
    named_or_third_party = "namedreview" in compact or "thirdparty" in compact
    decision = any(
        fragment in compact
        for fragment in ("approv", "authoriz", "certif", "attest", "endors")
    )
    publication = any(
        fragment in compact
        for fragment in (
            "readyforpublication",
            "publicationready",
            "readyforrelease",
            "releaseready",
            "readytopublish",
            "readytogolive",
            "approvedforpublication",
            "approvedforrelease",
            "approvedtopublish",
            "approvedtogolive",
            "authorizedforpublication",
            "authorizedforrelease",
            "authorizedtopublish",
            "authorizedtogolive",
            "clearedforpublication",
            "clearedforrelease",
            "clearedforlaunch",
            "clearedtogolive",
            "greenlitforpublication",
            "greenlitforrelease",
            "greenlittogolive",
            "publishable",
        )
    )
    subject = any(
        fragment in compact for fragment in ("kapi", "governance", "publication")
    )
    strong_actor = independent or operator or named_or_third_party
    assurance_actor = strong_actor or external or human_reviewer
    attributed_assurance = any(
        fragment in compact
        for fragment in (
            "approvalby",
            "approvedby",
            "attestedby",
            "auditby",
            "auditedby",
            "authorizedby",
            "certifiedby",
            "endorsedby",
            "reviewby",
            "reviewedby",
            "signedby",
            "signoffby",
            "verificationby",
            "verifiedby",
        )
    )
    completed_assurance = any(
        fragment in compact
        for fragment in (
            "approvalcomplete",
            "attestationcomplete",
            "auditcomplete",
            "certificationcomplete",
            "reviewcomplete",
            "reviewcompleted",
            "reviewdone",
            "reviewfinished",
            "signoffcomplete",
            "verificationcomplete",
        )
    )
    generic_completion_assertion = any(
        fragment in compact
        for fragment in (
            "approvalgranted",
            "approvedstatus",
            "authorizationgranted",
            "certifiedcompliant",
            "externallyvalidated",
            "fullyapproved",
            "haspassed",
            "launchcleared",
            "productionready",
            "validatedby",
            "vettedby",
            "assessedby",
        )
    )
    generic_review_assertion = "reviewed" in compact and not scoped_technical_review
    subject_claim = subject and (
        review
        or audit
        or verified
        or signoff
        or independent
        or operator
        or decision
        or "ready" in compact
        or "publish" in compact
    )
    live_state = subject and any(
        fragment in compact
        for fragment in (
            "deployed",
            "golive",
            "launched",
            "published",
            "released",
        )
    )
    return (
        "governancestatus" in compact
        or publication
        or attributed_assurance
        or completed_assurance
        or generic_completion_assertion
        or generic_review_assertion
        or subject_claim
        or live_state
        or (review and (assurance_actor or decision))
        or (audit and (assurance_actor or decision or subject))
        or (
            verified
            and (subject or strong_actor or decision or (external and subject))
        )
        or (signoff and (assurance_actor or decision or subject or review))
        or (decision and subject)
        or (external and "check" in compact)
    )


_EXACT_CLAIM_SUBJECT_FRAGMENTS = frozenset({"governance", "kapi", "publication"})
_RECORD_LOCAL_CLAIM_ACTOR_FRAGMENTS = frozenset(
    {
        "assessor",
        "consultant",
        "editor",
        "expert",
        "external",
        "human",
        "independen",
        "namedreview",
        "operator",
        "outside",
        "peer",
        "thirdparty",
    }
)
_RECORD_LOCAL_CLAIM_ACTION_FRAGMENTS = frozenset(
    {
        "approv",
        "attest",
        "audit",
        "authoriz",
        "certif",
        "check",
        "endors",
        "review",
        "signed",
        "signoff",
        "signedoff",
        "verif",
    }
)


def _contains_split_claim(fragments: Iterable[str]) -> bool:
    parts = [part for part in fragments if part]
    return (
        len(parts) >= 2
        and any(
            _normalized_claim_text(part) in _EXACT_CLAIM_SUBJECT_FRAGMENTS
            for part in parts
        )
        and _is_claim_bearing_text(" ".join(parts))
    )


def _contains_record_local_claim(fragments: Iterable[str]) -> bool:
    """Reject a split claim within one record without combining unrelated records."""

    parts = [part for part in fragments if part]
    if len(parts) < 2:
        return False
    compact_parts = [_normalized_claim_text(part) for part in parts]
    compact = _normalized_claim_text(" ".join(parts))
    actor_and_action = any(
        fragment in compact for fragment in _RECORD_LOCAL_CLAIM_ACTOR_FRAGMENTS
    ) and any(
        fragment in compact for fragment in _RECORD_LOCAL_CLAIM_ACTION_FRAGMENTS
    )
    attributed_action = any(
        part.startswith("by") for part in compact_parts
    ) and any(
        fragment in compact for fragment in _RECORD_LOCAL_CLAIM_ACTION_FRAGMENTS
    )
    subject_and_claim = any(
        subject in part
        for subject in _EXACT_CLAIM_SUBJECT_FRAGMENTS
        for part in compact_parts
    ) and _is_claim_bearing_text(" ".join(parts))
    return (
        actor_and_action
        or attributed_action
        or subject_and_claim
        or _contains_split_claim(parts)
    )


def _nested_claim_fragments(value: Any) -> list[str]:
    fragments: list[str] = []
    if isinstance(value, Mapping):
        for key, item in value.items():
            if isinstance(key, str):
                fragments.append(key)
            fragments.extend(_nested_claim_fragments(item))
    elif isinstance(value, list):
        for item in value:
            fragments.extend(_nested_claim_fragments(item))
    elif isinstance(value, str):
        fragments.append(value)
    return fragments


def _record_claim_fragments(value: Any) -> list[str]:
    """Collect semantic record values while excluding fixed technical carriers."""

    fragments: list[str] = []
    if isinstance(value, Mapping):
        for key, item in value.items():
            normalized_key = (
                _normalized_claim_text(key) if isinstance(key, str) else ""
            )
            technical_carrier = (
                normalized_key == "id"
                or normalized_key.endswith("id")
                or normalized_key.endswith("ids")
                or normalized_key.endswith("path")
                or normalized_key.endswith("sha256")
                or "tokenizer" in normalized_key
            )
            if technical_carrier:
                continue
            fragments.extend(_record_claim_fragments(item))
    elif isinstance(value, list):
        for item in value:
            fragments.extend(_record_claim_fragments(item))
    elif isinstance(value, str):
        fragments.append(value)
    return fragments


def find_input_claim_paths(value: Any, *, path: str = "bundle") -> list[str]:
    """Return normalized claim-bearing key or string paths recursively.

    The boundary applies to forward/current input data, not the immutable v0.2.x
    files retained for historical hash continuity. A structured source
    schema has no claim-key allowlist entries: bare reviewer, auditor,
    certification, approval, signature, verification, governance, or
    publication metadata is rejected regardless of nesting. Assertion-like
    free-form prose is rejected even under otherwise neutral keys.
    """

    paths: set[str] = set()

    def visit(item: Any, item_path: str) -> None:
        if isinstance(item, Mapping):
            if item_path == path:
                root_fragments = [
                    key for key in item if isinstance(key, str)
                ] + [
                    child for child in item.values() if isinstance(child, str)
                ]
                contains_local_claim = _contains_split_claim(root_fragments)
            else:
                contains_local_claim = _contains_record_local_claim(
                    _record_claim_fragments(item)
                )
            if contains_local_claim:
                paths.add(item_path)
            for key, child_item in item.items():
                child_path = f"{item_path}.{key}"
                if isinstance(key, str):
                    normalized_key = _normalized_claim_text(key)
                    if not key.isascii() or _has_residual_claim_encoding(key) or (
                        child_path not in ALLOWED_FORWARD_BUNDLE_CLAIM_KEY_PATHS
                        and any(
                            stem in normalized_key
                            for stem in _NORMALIZED_FORBIDDEN_INPUT_CLAIM_KEY_STEMS
                        )
                    ):
                        paths.add(child_path)
                visit(child_item, child_path)
        elif isinstance(item, list):
            if any(isinstance(child_item, str) for child_item in item) and (
                _is_claim_bearing_text(
                    " ".join(_nested_claim_fragments(item))
                )
            ):
                paths.add(item_path)
            for position, child_item in enumerate(item):
                visit(child_item, f"{item_path}[{position}]")
        elif isinstance(item, str):
            if (
                not item.isascii()
                or _has_residual_claim_encoding(item)
                or _is_claim_bearing_text(item)
            ):
                paths.add(item_path)

    visit(value, path)
    return sorted(paths)


def _forward_bundle_object_schema() -> dict[str, frozenset[str]]:
    return dict(FORWARD_BUNDLE_OBJECT_FIELDS)


def _forward_required_object_fields(
    schema_path: str, item: Mapping[Any, Any]
) -> frozenset[str] | None:
    """Return the exact keyset, or None for an ambiguous source variant."""

    if schema_path != "bundle.source_artifacts[].synthetic_content":
        return FORWARD_BUNDLE_OBJECT_FIELDS.get(schema_path)
    keys = {key for key in item if isinstance(key, str)}
    common = (
        FORWARD_CAPABILITY_SYNTHETIC_CONTENT_FIELDS
        & FORWARD_PRICE_SYNTHETIC_CONTENT_FIELDS
    )
    has_capability_marker = bool(
        keys & (FORWARD_CAPABILITY_SYNTHETIC_CONTENT_FIELDS - common)
    )
    has_price_marker = bool(keys & (FORWARD_PRICE_SYNTHETIC_CONTENT_FIELDS - common))
    if has_capability_marker == has_price_marker:
        return None
    if has_capability_marker:
        return FORWARD_CAPABILITY_SYNTHETIC_CONTENT_FIELDS
    return FORWARD_PRICE_SYNTHETIC_CONTENT_FIELDS


def forward_bundle_schema_definition_gaps() -> list[str]:
    """Return declared v0.3 fields missing a container or scalar/type rule."""

    known_paths = (
        set(FORWARD_BUNDLE_OBJECT_FIELDS)
        | set(FORWARD_LIST_PATHS)
        | set(FORWARD_EXACT_STRING_VALUES)
        | set(FORWARD_STRING_PATTERNS)
        | set(FORWARD_EXACT_NON_STRING_VALUES)
        | set(FORWARD_INTEGER_MINIMUMS)
        | set(FORWARD_NULLABLE_SCALAR_PATHS)
    )
    return sorted(
        f"{parent}.{field}"
        for parent, fields in FORWARD_BUNDLE_OBJECT_FIELDS.items()
        for field in fields
        if f"{parent}.{field}" not in known_paths
    )


def find_forward_bundle_scalar_grammar_violations(
    value: Any, *, path: str = "bundle"
) -> list[str]:
    """Return paths outside the complete v0.3 container/leaf type grammar."""

    violations: set[str] = set()
    object_paths = frozenset(FORWARD_BUNDLE_OBJECT_FIELDS)
    scalar_paths = frozenset(
        set(FORWARD_EXACT_STRING_VALUES)
        | set(FORWARD_STRING_PATTERNS)
        | set(FORWARD_EXACT_NON_STRING_VALUES)
        | set(FORWARD_INTEGER_MINIMUMS)
        | set(FORWARD_NULLABLE_SCALAR_PATHS)
    )

    def visit(item: Any, schema_path: str, report_path: str) -> None:
        if schema_path in object_paths:
            if not isinstance(item, Mapping):
                violations.add(report_path)
                return
            required = _forward_required_object_fields(schema_path, item)
            if required is None:
                violations.add(report_path)
            else:
                actual_keys = {key for key in item if isinstance(key, str)}
                violations.update(
                    f"{report_path}.{field}" for field in required - actual_keys
                )
            for key, child_item in item.items():
                child_schema_path = f"{schema_path}.{key}"
                child_report_path = f"{report_path}.{key}"
                visit(child_item, child_schema_path, child_report_path)
            return
        if schema_path in FORWARD_LIST_PATHS:
            if not isinstance(item, list):
                violations.add(report_path)
                return
            for position, child_item in enumerate(item):
                visit(
                    child_item,
                    f"{schema_path}[]",
                    f"{report_path}[{position}]",
                )
            return
        if schema_path not in scalar_paths:
            violations.add(report_path)
            return
        if item is None:
            if schema_path not in FORWARD_NULLABLE_SCALAR_PATHS:
                violations.add(report_path)
            return
        if isinstance(item, (Mapping, list)):
            violations.add(report_path)
            return
        valid = False
        if isinstance(item, str):
            exact = FORWARD_EXACT_STRING_VALUES.get(schema_path)
            pattern = FORWARD_STRING_PATTERNS.get(schema_path)
            valid = (exact is not None and item in exact) or (
                pattern is not None and pattern.fullmatch(item) is not None
            )
        exact_non_string = FORWARD_EXACT_NON_STRING_VALUES.get(schema_path, ())
        valid = valid or any(
            type(item) is expected_type and item == expected_value
            for expected_type, expected_value in exact_non_string
        )
        minimum = FORWARD_INTEGER_MINIMUMS.get(schema_path)
        valid = valid or (
            minimum is not None and type(item) is int and item >= minimum
        )
        if not valid:
            violations.add(report_path)

    visit(value, path, path)
    return sorted(violations)


def _validate_forward_bundle_closed_schema(
    collector: _Collector,
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
) -> None:
    """Reject every undeclared object key/path in the forward bundle vintage."""

    schema = _forward_bundle_object_schema()

    def visit(item: Any, schema_path: str, report_path: str) -> None:
        if isinstance(item, Mapping):
            allowed = schema.get(schema_path)
            if allowed is None:
                collector.error(
                    "unexpected_forward_bundle_field",
                    report_path,
                    "v0.3.0 does not permit an object at this bundle path",
                )
                return
            required = _forward_required_object_fields(schema_path, item)
            if required is None:
                collector.error(
                    "forward_record_variant",
                    report_path,
                    "v0.3.0 synthetic content must match exactly one capability "
                    "or price record variant",
                )
                required = allowed
            actual_keys = {key for key in item if isinstance(key, str)}
            for missing_key in sorted(required - actual_keys):
                collector.error(
                    "missing_forward_bundle_field",
                    f"{report_path}.{missing_key}",
                    "v0.3.0 requires this field in the exact object keyset",
                )
            for key, child_item in item.items():
                child_report_path = f"{report_path}.{key}"
                if not isinstance(key, str) or key not in required:
                    collector.error(
                        "unexpected_forward_bundle_field",
                        child_report_path,
                        "v0.3.0 does not permit this field at this bundle path",
                    )
                    continue
                visit(child_item, f"{schema_path}.{key}", child_report_path)
        elif isinstance(item, list):
            for position, child_item in enumerate(item):
                visit(
                    child_item,
                    f"{schema_path}[]",
                    f"{report_path}[{position}]",
                )

    visit(bundle, "bundle", "bundle")


def _validate_forward_bundle_binding(
    collector: _Collector,
    bundle: Mapping[str, Any],
    methodology: Mapping[str, Any],
) -> None:
    collector.check(
        methodology.get("version") == "0.3.0",
        "forward_methodology_pair",
        "methodology.version",
        "a v0.3 forward bundle requires the exact v0.3.0 methodology",
    )
    collector.check(
        bundle.get("schema_version") == FORWARD_BUNDLE_SCHEMA_VERSION,
        "forward_bundle_binding",
        "schema_version",
        f"v0.3.0 requires {FORWARD_BUNDLE_SCHEMA_VERSION}",
    )
    collector.check(
        sha256_bytes(canonical_json_bytes(bundle))
        == FORWARD_BOUNDED_FIXTURE_SHA256,
        "bounded_fixture_identity",
        "bundle",
        "v0.3.0 accepts only the canonical deterministic forward fixture; "
        "dynamic or observed inputs require a new reviewed schema vintage",
    )
    collector.check(
        "expected_result" not in bundle and "generation" not in bundle,
        "forward_bundle_binding",
        "bundle",
        "forward/public inputs cannot contain a caller oracle or spend-success metadata",
    )
    binding = bundle.get("methodology")
    expected_binding = {
        "config_path": FORWARD_METHODOLOGY_PATH,
        "config_sha256": sha256_bytes(canonical_json_bytes(methodology)),
        "id": methodology.get("methodology_id"),
        "version": methodology.get("version"),
    }
    collector.check(
        isinstance(binding, Mapping) and dict(binding) == expected_binding,
        "forward_bundle_binding",
        "methodology",
        "bundle methodology binding must exactly match the supplied v0.3.0 document",
    )


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


def _load_payload_document(
    collector: _Collector, root: Path, relative: Any, path: str
) -> Mapping[str, Any] | None:
    if not isinstance(relative, str) or not relative:
        return None
    candidate = (root / relative).resolve()
    try:
        candidate.relative_to(root)
    except ValueError:
        return None
    if not candidate.is_file():
        return None
    raw = candidate.read_bytes()
    try:
        document = json.loads(raw)
    except json.JSONDecodeError as error:
        collector.error("payload_json", path, f"payload is not JSON: {error}")
        return None
    if not isinstance(document, Mapping):
        collector.error("payload_json", path, "payload must be a JSON object")
        return None
    collector.check(
        raw == canonical_json_bytes(document),
        "payload_canonical_json",
        path,
        "payload must use canonical JSON bytes",
    )
    return document


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
        "controlled prototype requires 13 base weeks",
    )

    capability = methodology.get("capability", {})
    if not isinstance(capability, Mapping):
        collector.error("object_type", "capability", "capability must be an object")
        capability = {}
    collector.check(
        capability.get("metric") == "ECI",
        "capability_metric",
        "capability.metric",
        "configured capability metric is ECI",
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
            "configured headline threshold is 130",
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
            "configured sensitivity thresholds are 125 and 135",
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
        isinstance(reference, Mapping) and reference.get("id") == "o200k_base",
        "reference_tokenizer",
        "reference_tokenizer.id",
        "construction reference must be o200k_base",
    )
    methodology_version = methodology.get("version")
    collector.check(
        methodology_version in CONTROLLED_METHODOLOGY_VERSIONS,
        "methodology_version",
        "version",
        "only the pinned v0.2.x historical vintages and current v0.3.0 "
        "methodology are recognized",
    )
    expected_methodology_hash = PINNED_METHODOLOGY_SHA256.get(
        methodology_version
    )
    if expected_methodology_hash is not None:
        collector.check(
            sha256_bytes(canonical_json_bytes(methodology))
            == expected_methodology_hash,
            "methodology_vintage_identity",
            "methodology",
            "recognized methodology versions must exactly match their pinned "
            "committed document",
        )
    if methodology_version in CONTROLLED_METHODOLOGY_VERSIONS:
        collector.check(
            capability.get("configuration_specific_score_allowed") is False,
            "eci_scope",
            "capability.configuration_specific_score_allowed",
            "ECI may be used only as a coarse screen, not a configuration score",
        )
        construction = methodology.get("construction_reference", {})
        if methodology_version == "0.3.0":
            construction_count_status_valid = (
                isinstance(construction, Mapping)
                and construction.get("construction_reference_count_status")
                == "verified_exact_local_reference_counts_only"
                and "counts_verified" not in construction
            )
        else:
            construction_count_status_valid = (
                isinstance(construction, Mapping)
                and construction.get("counts_verified") is True
            )
        collector.check(
            isinstance(construction, Mapping)
            and construction_count_status_valid
            and construction.get("tolerance_tokens") == 0,
            "construction_count_policy",
            "construction_reference",
            "controlled methodology vintages require the vintage-specific exact "
            "local construction-count status with zero tolerance",
        )
        collector.check(
            isinstance(reference, Mapping)
            and reference.get("asset_sha256")
            == "446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d"
            and reference.get("verification_status")
            == "explicit_construction_reference_only_not_model_mapping_or_billing_equivalence",
            "reference_tokenizer",
            "reference_tokenizer",
            "o200k_base may be verified only as an explicit construction reference",
        )
        if methodology_version in PORTABLE_METHODOLOGY_VERSIONS:
            manifest = methodology.get("construction_manifest", {})
            if not isinstance(manifest, Mapping):
                collector.error(
                    "construction_manifest",
                    "construction_manifest",
                    "portable methodology vintages require a frozen construction manifest",
                )
                manifest = {}
            collector.check(
                manifest.get("entry_count") == 12
                and manifest.get("status")
                == "frozen_derived_subset_for_portable_construction_only"
                and manifest.get("source_asset_vendored") is False,
                "construction_manifest",
                "construction_manifest",
                "portable methodology vintages require exactly 12 derived entries and must not vendor the source asset",
            )
            _validate_file_hash(
                collector,
                root,
                manifest.get("path"),
                manifest.get("sha256"),
                path_prefix="construction_manifest",
            )
            collector.check(
                reference.get("portable_construction_manifest_path")
                == manifest.get("path")
                and reference.get("portable_construction_manifest_sha256")
                == manifest.get("sha256"),
                "construction_manifest",
                "reference_tokenizer",
                "reference tokenizer must pin the same portable construction manifest",
            )
            portable = construction.get("portable_reproduction", {})
            source_proof = construction.get("source_asset_proof", {})
            path_configuration = construction.get(
                "full_source_asset_path_configuration", {}
            )
            collector.check(
                isinstance(portable, Mapping)
                and portable.get("full_source_asset_required") is False
                and portable.get("mode") == "frozen_12_chunk_manifest",
                "construction_portability",
                "construction_reference.portable_reproduction",
                "portable reproduction must use the frozen manifest without the full asset",
            )
            collector.check(
                isinstance(source_proof, Mapping)
                and source_proof.get("full_source_asset_required") is True
                and source_proof.get("mode") == "explicit_local_asset_path",
                "construction_source_proof",
                "construction_reference.source_asset_proof",
                "full source proof must remain a distinct explicit-asset operation",
            )
            collector.check(
                isinstance(path_configuration, Mapping)
                and path_configuration.get("cli_option") == "--asset-path"
                and path_configuration.get("environment_variable")
                == "KAPI_O200K_ASSET_PATH"
                and path_configuration.get("repository_default") is None,
                "construction_source_path",
                "construction_reference.full_source_asset_path_configuration",
                "repository code must not contain a default workstation asset path",
            )
        evidence_classes = methodology.get("evidence_classes", {})
        if not isinstance(evidence_classes, Mapping):
            collector.error(
                "evidence_classes",
                "evidence_classes",
                "controlled methodology vintages require distinct evidence classes",
            )
            evidence_classes = {}
        collector.check(
            evidence_classes.get("construction_counts", {}).get("status")
            == "verified_local_reference_only",
            "evidence_classes",
            "evidence_classes.construction_counts.status",
            "construction counts must be local reference evidence only",
        )
        collector.check(
            evidence_classes.get("provider_preflight_request_counts", {}).get("status")
            == "unverified_no_provider_call",
            "evidence_classes",
            "evidence_classes.provider_preflight_request_counts.status",
            "provider preflight counts must remain unverified",
        )
        collector.check(
            evidence_classes.get("billed_usage_counts", {}).get("status")
            == "unverified_no_billing_or_provider_call",
            "evidence_classes",
            "evidence_classes.billed_usage_counts.status",
            "billed usage counts must remain unverified",
        )
        billing_counts = methodology.get("endpoint_specific_billing_counts", {})
        collector.check(
            isinstance(billing_counts, Mapping)
            and billing_counts.get(
                "construction_reference_may_substitute_for_billing_counts"
            )
            is False
            and billing_counts.get("verified_billing_rows") == 0,
            "billing_counts_unverified",
            "endpoint_specific_billing_counts",
            "construction counts must not substitute for provider billing rows",
        )
        candidates = methodology.get("candidate_configurations", [])
        if not isinstance(candidates, list):
            collector.error(
                "candidate_configurations",
                "candidate_configurations",
                "candidate configurations must be an array",
            )
            candidates = []
        candidate_ids = {
            candidate.get("candidate_id")
            for candidate in candidates
            if isinstance(candidate, Mapping)
        }
        model_ids = {
            candidate.get("model_id")
            for candidate in candidates
            if isinstance(candidate, Mapping)
        }
        collector.check(
            "google-gemini25flash-thinking-budget-0" in candidate_ids
            and "gemini-2.5-flash" in model_ids
            and "gemini-2.5-pro" not in model_ids,
            "candidate_substitution",
            "candidate_configurations",
            "Gemini Pro thinking-disabled must be replaced by Gemini Flash thinkingBudget=0",
        )
        if methodology_version == "0.2.0":
            collector.check(
                "openai-gpt54mini-reasoning-none" in candidate_ids
                and "anthropic-claude-sonnet-4-6-thinking-disabled" in candidate_ids,
                "candidate_review_set",
                "candidate_configurations",
                "OpenAI and Claude review candidates must remain present",
            )
        else:
            candidates_by_id = {
                candidate.get("candidate_id"): candidate
                for candidate in candidates
                if isinstance(candidate, Mapping)
                and isinstance(candidate.get("candidate_id"), str)
            }
            openai = candidates_by_id.get("openai-gpt54mini-reasoning-none", {})
            google = candidates_by_id.get("google-gemini25flash-thinking-budget-0", {})
            anthropic = candidates_by_id.get(
                "anthropic-claude-sonnet-4-6-thinking-omitted", {}
            )
            openai_docs = openai.get("official_documentation", {})
            if not isinstance(openai_docs, Mapping):
                openai_docs = {}
            google_docs = google.get("official_documentation", {})
            if not isinstance(google_docs, Mapping):
                google_docs = {}
            anthropic_docs = anthropic.get("official_documentation", {})
            if not isinstance(anthropic_docs, Mapping):
                anthropic_docs = {}
            collector.check(
                len(candidates_by_id) == 3
                and "anthropic-claude-sonnet-4-6-thinking-disabled"
                not in candidates_by_id,
                "candidate_review_set",
                "candidate_configurations",
                "controlled vintages after v0.2.0 require exactly the blocked OpenAI, supported-doc Google, and thinking-omitted Anthropic review candidates",
            )
            collector.check(
                openai.get("model_id") == "gpt-5.4-mini-2026-03-17"
                and openai.get("priced_configuration") == {"reasoning": "none"}
                and openai.get("eligibility_status")
                == "blocked_official_configuration_evidence"
                and openai_docs.get("status") == "failed_exact_model_id_unverified",
                "candidate_official_evidence",
                "candidate_configurations.openai-gpt54mini-reasoning-none",
                "the exact dated OpenAI candidate must remain blocked and official-source unverified",
            )
            collector.check(
                google.get("model_id") == "gemini-2.5-flash"
                and google.get("priced_configuration") == {"thinkingBudget": 0}
                and google.get("eligibility_status") == "review_candidate_only"
                and google_docs.get("status") == "supported_configuration_and_pricing",
                "candidate_official_evidence",
                "candidate_configurations.google-gemini25flash-thinking-budget-0",
                "Gemini 2.5 Flash thinkingBudget=0 may be marked only as official-document supported",
            )
            anthropic_configuration = anthropic.get("priced_configuration", {})
            collector.check(
                anthropic.get("model_id") == "claude-sonnet-4-6"
                and anthropic_configuration == {"thinking_parameter": "omitted"}
                and "thinking" not in anthropic_configuration
                and anthropic.get("eligibility_status") == "review_candidate_only"
                and anthropic_docs.get("status") == "supported_when_parameter_omitted",
                "candidate_official_evidence",
                "candidate_configurations.anthropic-claude-sonnet-4-6-thinking-omitted",
                "Claude Sonnet 4.6 must represent thinking off by parameter omission, not an explicit disabled value",
            )
            lifecycle = anthropic.get("lifecycle_status", {})
            if not isinstance(lifecycle, Mapping):
                lifecycle = {}
            collector.check(
                lifecycle.get("status_at_snapshot") == "active_not_deprecated"
                and lifecycle.get("tentative_retirement_not_before") == "2027-02-17"
                and "stability_risk" not in anthropic,
                "candidate_lifecycle",
                "candidate_configurations.anthropic-claude-sonnet-4-6-thinking-omitted.lifecycle_status",
                "Claude Sonnet 4.6 must be recorded as active, not deprecated, at the evidence snapshot",
            )
            collector.check(
                all(
                    candidate.get("provider_preflight_status")
                    == "unverified_no_provider_call"
                    and candidate.get("billed_usage_status")
                    == "unverified_no_billing_or_provider_call"
                    for candidate in candidates_by_id.values()
                ),
                "candidate_runtime_evidence",
                "candidate_configurations",
                "official documentation cannot satisfy provider preflight or billed usage evidence",
            )
            official_evidence = methodology.get("official_provider_evidence", {})
            collector.check(
                isinstance(official_evidence, Mapping)
                and official_evidence.get("status")
                == "documentation_only_no_provider_call"
                and official_evidence.get("snapshot_date") == "2026-07-10"
                and official_evidence.get("provider_calls_performed") == 0
                and official_evidence.get("billing_checks_performed") == 0,
                "official_provider_evidence",
                "official_provider_evidence",
                "controlled vintages after v0.2.0 require documentation-only evidence with zero provider and billing actions",
            )
            if isinstance(official_evidence, Mapping):
                _validate_file_hash(
                    collector,
                    root,
                    official_evidence.get("record_path"),
                    official_evidence.get("record_sha256"),
                    path_prefix="official_provider_evidence",
                )
        gates = methodology.get("readiness_gates", {})
        common_gates_failed = (
            isinstance(gates, Mapping)
            and gates.get("technical_go") == "failed_no_go"
            and gates.get("observed_dry_run") == "failed_not_authorized_not_performed"
            and gates.get("shadow_week_1") == "failed_not_authorized_not_started"
        )
        if methodology_version == "0.3.0":
            pinned_method_path = root / "kapi/config/methodology-v0.3.0.json"
            try:
                pinned_method_bytes = pinned_method_path.read_bytes()
            except OSError as error:
                collector.error(
                    "forward_methodology_vintage",
                    "methodology",
                    f"could not read the pinned v0.3.0 document: {error}",
                )
            else:
                collector.check(
                    canonical_json_bytes(methodology) == pinned_method_bytes,
                    "forward_methodology_vintage",
                    "methodology",
                    "v0.3.0 must exactly match the committed pinned document",
                )
            collector.check(
                common_gates_failed
                and "independent_review" not in gates
                and gates.get("external_methodology_review")
                == "failed_no_trusted_identity_or_signature_verifier_adapter"
                and gates.get("trusted_verifier_adapter") == "failed_not_implemented"
                and gates.get("operator_review")
                == "failed_no_trusted_operator_identity_adapter",
                "readiness_gates",
                "readiness_gates",
                "v0.3.0 requires failed technical, operational, external-review, and trusted-verifier gates",
            )
            governance_policy = methodology.get("governance_policy", {})
            collector.check(
                isinstance(governance_policy, Mapping)
                and governance_policy.get("policy_id") == POLICY_ID
                and governance_policy.get("policy_version") == POLICY_VERSION
                and governance_policy.get("policy_path")
                == "kapi/config/governance-policy-v1.0.0.json"
                and governance_policy.get("current_review_label")
                == CURRENT_UNREVIEWED_LABEL
                and governance_policy.get("governance_state") == "unreviewed"
                and governance_policy.get("operator_review") == "not_complete"
                and governance_policy.get("external_methodology_review")
                == "not_complete"
                and governance_policy.get("publication_state") == "not_authorized"
                and governance_policy.get("publication_eligible") is False,
                "governance_policy",
                "governance_policy",
                "v0.3.0 must pin the fail-closed governance policy and exact unreviewed label",
            )
            if isinstance(governance_policy, Mapping):
                _validate_file_hash(
                    collector,
                    root,
                    governance_policy.get("policy_path"),
                    governance_policy.get("policy_sha256"),
                    path_prefix="governance_policy",
                )
            amendment = methodology.get("methodology_amendment", {})
            collector.check(
                isinstance(amendment, Mapping)
                and amendment.get("supersedes_version") == "0.2.2"
                and amendment.get("decision_record_path")
                == "kapi/docs/GOVERNANCE_POLICY_v1.0.0.md"
                and amendment.get("change_class")
                == "governance_terminology_and_authorization_control_only_no_index_math_change"
                and amendment.get("status")
                == "implemented_forward_governance_vintage_not_externally_reviewed",
                "methodology_amendment",
                "methodology_amendment",
                "v0.3.0 must identify its exact v0.2.2 predecessor and governance-only change class",
            )
            selection = methodology.get("selection", {})
            collector.check(
                isinstance(selection, Mapping)
                and selection.get("method")
                == "lowest feasible provider-and-creator-diverse three-endpoint median",
                "selection_method",
                "selection.method",
                "v0.3.0 must use precise provider/creator diversity terminology",
            )
        else:
            collector.check(
                common_gates_failed
                and gates.get("independent_review")
                == "failed_self_review_is_not_independent",
                "readiness_gates",
                "readiness_gates",
                "historical v0.2.x readiness gates must stay failed",
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
                f"configured value is {value!r}",
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
        "configured basket contains six profiles",
    )
    total_count = 0
    total_weight = 0
    for position, profile in enumerate(profiles):
        path = f"profiles[{position}]"
        count = profile.get("count")
        if not isinstance(count, int) or count <= 0:
            collector.error(
                "profile_count", f"{path}.count", "must be a positive integer"
            )
        else:
            total_count += count
        try:
            weight = rational_decimal(profile.get("weight", {}), field=f"{path}.weight")
            total_weight += weight
            collector.check(
                weight
                == rational_decimal(
                    {"numerator": 1, "denominator": 6}, field="configured weight"
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
                if methodology.get("version") in CONTROLLED_METHODOLOGY_VERSIONS:
                    document = _load_payload_document(
                        collector, root, entry.get("path"), entry_path
                    )
                    if document is not None:
                        collector.check(
                            document.get("o200k_base_count_verified") is True,
                            "payload_construction_count",
                            f"{entry_path}.path",
                            "payload must carry a local o200k_base construction count",
                        )
                        collector.check(
                            document.get("construction_count_tolerance_tokens") == 0,
                            "payload_construction_count",
                            f"{entry_path}.path",
                            "controlled methodology payload count tolerance must be zero",
                        )
                        collector.check(
                            document.get("construction_token_count")
                            == entry.get("construction_token_count")
                            == entry.get("reference_token_design_target"),
                            "payload_construction_count",
                            f"{entry_path}.reference_token_design_target",
                            "methodology target must equal payload construction count",
                        )
                        content = document.get("content")
                        chunk = document.get("single_token_chunk")
                        count = document.get("construction_token_count")
                        collector.check(
                            isinstance(content, str)
                            and isinstance(chunk, str)
                            and isinstance(count, int)
                            and content == chunk * count,
                            "payload_determinism",
                            f"{entry_path}.path",
                            "payload content must be deterministic repeated single-token chunks",
                        )
            collector.check(
                set(by_factor) == set(PAYLOAD_FACTORS),
                "payload_factor",
                f"{path}.payloads.{kind}",
                "must contain exactly one 0.75, 1.00, and 1.25 payload",
            )
            headline = by_factor.get("1.00", {})
            collector.check(
                headline.get("path") == relative and headline.get("sha256") == digest,
                "headline_payload",
                f"{path}.{kind}_payload",
                "headline path/hash must match the 1.00 payload definition",
            )
    collector.check(
        total_count == 60,
        "basket_count",
        "profiles",
        "configured fixed basket contains 60 profiles",
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
        "must contain the complete configured 3x3 factor grid",
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

    forward_contract = requires_forward_governance_contract(bundle, methodology)
    if forward_contract:
        for path in forward_bundle_schema_definition_gaps():
            collector.error(
                "forward_schema_definition",
                path,
                "declared v0.3.0 field lacks a container/leaf type rule",
            )
        _validate_forward_bundle_binding(collector, bundle, methodology)
        _validate_forward_bundle_closed_schema(collector, bundle, methodology)
        for path in find_forward_bundle_scalar_grammar_violations(bundle):
            collector.error(
                "forward_scalar_grammar",
                path,
                "v0.3.0 values must match the closed path-specific container/"
                "leaf type and scalar grammar",
            )
        for path in find_input_claim_paths(bundle):
            collector.error(
                "input_governance_claim",
                path,
                "frozen input data cannot carry a governance/review/publication "
                "claim key, assertion-like string, or non-ASCII public value",
            )
    elif methodology.get("version") in HISTORICAL_METHODOLOGY_VERSIONS:
        collector.check(
            sha256_bytes(canonical_json_bytes(bundle))
            == HISTORICAL_BOUNDED_FIXTURE_SHA256,
            "historical_fixture_identity",
            "bundle",
            "v0.2.x validation is read-only compatibility for the exact pinned "
            "historical fixture; it cannot validate new or altered input",
        )

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
    if forward_contract:
        collector.check(
            isinstance(bundle.get("dataset_id"), str)
            and bundle.get("dataset_id", "").startswith("synthetic-forward-"),
            "forward_id_namespace",
            "dataset_id",
            "v0.3.0 synthetic forward dataset IDs must start with synthetic-forward-",
        )
        for collection, prefix in FORWARD_ID_PREFIXES.items():
            for index, record in enumerate(arrays[collection]):
                collector.check(
                    isinstance(record.get("id"), str)
                    and record.get("id", "").startswith(prefix),
                    "forward_id_namespace",
                    f"{collection}[{index}].id",
                    f"v0.3.0 {collection} IDs must start with {prefix}",
                )

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
            collector.error(
                "invalid_timestamp", f"weeks[{index}].cutoff_at", str(error)
            )
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
        _require_reference(collector, model, "creator_id", indexes["creators"], path)
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
                isinstance(endpoint.get(flag), bool) and endpoint.get(flag) is expected,
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
        if "first_party" in endpoint:
            collector.check(
                isinstance(endpoint.get("first_party"), bool),
                "first_party",
                f"{path}.first_party",
                "must be boolean when supplied",
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
            and MEDIA_TYPE_PATTERN.fullmatch(source.get("media_type", ""))
            is not None,
            "source_metadata",
            f"{path}.media_type",
            "media type must use type/subtype syntax without parameters",
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
                snapshot_path == f"embedded://source_artifacts/{source.get('id')}",
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
    for index, capability in enumerate(arrays["capability_evidence"]):
        path = f"capability_evidence[{index}]"
        _require_reference(collector, capability, "model_id", indexes["models"], path)
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
            capability.get("configuration_id") == endpoint.get("configuration_id"),
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
            side_by_factor[side] = (
                {
                    str(entry.get("size_factor")): entry
                    for entry in entries
                    if isinstance(entry, Mapping)
                }
                if isinstance(entries, list)
                else {}
            )
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
            "must reference a configured payload-grid cell",
        )
        for field in ("input_tokens", "output_tokens"):
            collector.check(
                isinstance(token_count.get(field), int)
                and token_count.get(field, 0) > 0,
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
        if methodology.get("version") in CONTROLLED_METHODOLOGY_VERSIONS:
            collector.check(
                token_count.get("construction_count_evidence_class")
                == "construction_count",
                "evidence_class_separation",
                f"{path}.construction_count_evidence_class",
                "token row must identify construction-count evidence",
            )
            collector.check(
                token_count.get("billing_usage_count_status")
                == "unverified_no_provider_call",
                "billing_counts_unverified",
                f"{path}.billing_usage_count_status",
                "provider billing usage count must remain unverified",
            )
            collector.check(
                token_count.get("construction_tokenizer")
                == endpoint.get("construction_tokenizer"),
                "tokenizer_mismatch",
                f"{path}.construction_tokenizer",
                "construction tokenizer must match endpoint construction reference",
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
        _require_reference(collector, observation, "endpoint_id", endpoint_ids, path)
        _require_reference(collector, observation, "week_id", indexes["weeks"], path)
        _require_reference(collector, observation, "source_id", source_ids, path)
        collector.check(
            observation.get("component")
            in {"input", "output", "cache_read", "cache_write"},
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
        if isinstance(observation.get("context_min_tokens"), int) and isinstance(
            observation.get("context_max_tokens"), int
        ):
            collector.check(
                observation["context_min_tokens"] <= observation["context_max_tokens"],
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
                observation_supersession[str(observation.get("id"))] = str(supersedes)
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
                correction_supersession[str(correction_id)] = str(prior_correction_id)
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
        stats={name: len(indexes.get(name, arrays[name])) for name in names},
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
    errors = [asdict(issue) for issue in collector.issues if issue.severity == "error"]
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
