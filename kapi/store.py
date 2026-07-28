"""Append-only SQLite storage for canonical KAPI input bundles.

The public API deliberately has only three operations: initialize a database,
ingest one complete bundle, and dump that bundle in canonical list order.
Corrections and superseding observations are new rows; existing facts are never
updated or deleted.
"""

from __future__ import annotations

import hashlib
import json
import os
import re
import sqlite3
import uuid
from contextlib import contextmanager
from contextvars import ContextVar
from datetime import datetime
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any, Iterable, Mapping
from urllib.parse import urlsplit

from .util import canonical_json_bytes


__all__ = [
    "APPEND_ONLY_TABLES",
    "StoreError",
    "dump_bundle",
    "ingest_bundle",
    "init_database",
]


class StoreError(ValueError):
    """Raised when a bundle or store operation violates the KAPI contract."""


APPEND_ONLY_TABLES = (
    "datasets",
    "weeks",
    "providers",
    "creators",
    "models",
    "endpoints",
    "endpoint_features",
    "source_artifacts",
    "capability_evidence",
    "token_counts",
    "price_observations",
    "incidents",
    "corrections",
    "methodology_versions",
    "methodology_base_weeks",
    "methodology_thresholds",
    "task_profiles",
    "task_profile_features",
    "methodology_sensitivities",
    "weekly_snapshots",
    "snapshot_inputs",
    "calculations",
    "calculation_profile_results",
    "calculation_selected_endpoints",
    "calculation_validations",
    "releases",
    "release_artifacts",
    "release_signoffs",
    "correction_releases",
    "governance_principals",
    "governance_role_assignments",
    "external_reviewer_registry",
    "external_reviewer_registry_events",
    "methodology_governance_gates",
    "release_governance_bindings",
    "external_review_records",
    "signature_verification_attestations",
    "governance_transition_events",
)

_SCHEMA_PATHS = (
    Path(__file__).with_name("schema") / "001_initial.sql",
    Path(__file__).with_name("schema") / "002_governance.sql",
)
_SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
_LOCAL_ACTOR_BINDING: ContextVar[str | None] = ContextVar(
    "kapi_local_actor_binding", default=None
)
_CURRENT_UNREVIEWED_LABEL = (
    "Governance status: Unreviewed draft. Automated validation completed for this "
    "artifact; no operator or external methodology review is complete."
)
_CALCULATION_DIAGNOSTIC_KEYS = frozenset(
    {
        "base_week_states",
        "calculation_disposition",
        "governance_state",
        "publication_eligible",
        "publication_state",
        "release_kind",
        "review_label",
        "secondary_recalculation",
    }
)
_SECONDARY_RECALCULATION_NOT_SUPPLIED = {
    "human_external_review": False,
    "lifecycle_handling": (
        "no secondary recalculation report accepted by policy v1.0.0"
    ),
    "status": "not_supplied",
}
_CALCULATION_DISPOSITION_BY_STATUS = {
    "complete": "eligible",
    "invalid": "incomplete",
    "pending_base": "incomplete",
    "withheld": "withheld",
}
_RELEASE_KINDS = frozenset(
    {"pending_base", "provisional_base", "final_base", "weekly", "correction"}
)
_NONCOUNTING_BASE_STATES = frozenset(
    {
        "incomplete",
        "unsigned",
        "irreproducible",
        "materially_corrected",
        "withheld_concentration",
    }
)
_BASE_STATES = _NONCOUNTING_BASE_STATES | {"counting"}
_TOP_LEVEL_KEYS = {
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
}

_WEEK_FIELDS = {"id", "cutoff_at"}
_PARTY_FIELDS = {"id", "name"}
_MODEL_FIELDS = {"id", "creator_id", "version", "alias_type", "immutable_version"}
_ENDPOINT_FIELDS = {
    "id",
    "provider_id",
    "model_id",
    "configuration_id",
    "region",
    "tier",
    "public",
    "ga",
    "synchronous",
    "on_demand",
    "available_us",
    "standard_list_price",
    "reasoning_mode",
    "billing_tokenizer",
    "tokenizer_reproducible",
    "features",
    "available_from",
    "available_until",
}
_SOURCE_FIELDS = {
    "id",
    "url",
    "retrieved_at",
    "evidence_grade",
    "media_type",
    "content_sha256",
    "snapshot_path",
    "license_note",
}
_CAPABILITY_FIELDS = {
    "id",
    "model_id",
    "endpoint_id",
    "metric",
    "metric_version",
    "score",
    "configuration_id",
    "evaluated_at",
    "data_vintage",
    "source_id",
    "evidence_grade",
}
_TOKEN_FIELDS = {
    "id",
    "endpoint_id",
    "profile_id",
    "input_tokens",
    "output_tokens",
    "input_payload_sha256",
    "output_payload_sha256",
    "billing_tokenizer",
    "size_variant",
}
_PRICE_FIELDS = {
    "id",
    "endpoint_id",
    "week_id",
    "component",
    "amount_per_million",
    "currency",
    "unit",
    "region",
    "tier",
    "context_min_tokens",
    "context_max_tokens",
    "effective_at",
    "observed_at",
    "source_id",
    "evidence_grade",
    "supersedes_observation_id",
}


def _calculation_diagnostics_are_valid(
    diagnostics_json: Any, calculation_status: Any
) -> int:
    """Validate the exact lifecycle-owned policy-v1 diagnostics document.

    This callback is deliberately total: invalid input returns zero instead of
    raising through SQLite. The SQL triggers use ``IS NOT 1`` so a missing,
    unregistered, NULL-returning, or rejecting validator fails closed.
    """

    if type(diagnostics_json) is not str or type(calculation_status) is not str:
        return 0

    def reject_nonstandard_constant(value: str) -> None:
        raise ValueError(f"non-standard JSON constant: {value}")

    try:
        document = json.loads(
            diagnostics_json,
            parse_constant=reject_nonstandard_constant,
        )
    except (TypeError, ValueError, RecursionError):
        return 0
    if type(document) is not dict or set(document) != _CALCULATION_DIAGNOSTIC_KEYS:
        return 0

    try:
        canonical = canonical_json_bytes(document).decode("utf-8").rstrip("\n")
    except (TypeError, ValueError, OverflowError, RecursionError):
        return 0
    if diagnostics_json != canonical:
        return 0

    if (
        document.get("governance_state") != "unreviewed"
        or document.get("review_label") != _CURRENT_UNREVIEWED_LABEL
        or document.get("publication_state") != "not_authorized"
        or document.get("publication_eligible") is not False
        or document.get("secondary_recalculation")
        != _SECONDARY_RECALCULATION_NOT_SUPPLIED
    ):
        return 0

    expected_disposition = _CALCULATION_DISPOSITION_BY_STATUS.get(calculation_status)
    if (
        expected_disposition is None
        or document.get("calculation_disposition") != expected_disposition
    ):
        return 0

    release_kind = document.get("release_kind")
    base_states = document.get("base_week_states")
    if release_kind not in _RELEASE_KINDS or type(base_states) is not list:
        return 0
    if any(
        type(state) is not str or state not in _BASE_STATES for state in base_states
    ):
        return 0
    if release_kind == "final_base":
        if len(base_states) != 13 or any(state != "counting" for state in base_states):
            return 0
    elif release_kind in {"pending_base", "provisional_base"}:
        if base_states and len(base_states) != 13:
            return 0
    elif base_states:
        return 0
    return 1


def init_database(
    path_or_connection: str | os.PathLike[str] | sqlite3.Connection,
) -> sqlite3.Connection:
    """Initialize the current forward-only schema and return a connection.

    If *path_or_connection* is a path, this function owns opening but not closing
    the returned connection.  A supplied connection must not have a transaction
    open, because SQLite cannot enable foreign-key enforcement mid-transaction.
    """

    if isinstance(path_or_connection, sqlite3.Connection):
        connection = path_or_connection
    elif isinstance(path_or_connection, (str, os.PathLike)):
        connection = sqlite3.connect(os.fspath(path_or_connection))
    else:
        raise TypeError("path_or_connection must be a path or sqlite3.Connection")

    if connection.in_transaction:
        raise StoreError("initialize the KAPI schema outside an active transaction")

    connection.row_factory = sqlite3.Row
    connection.execute("PRAGMA foreign_keys = ON")
    if connection.execute("PRAGMA foreign_keys").fetchone()[0] != 1:
        raise StoreError("SQLite foreign-key enforcement could not be enabled")

    try:

        def release_artifact_manifest_sha256(release_id: Any) -> str:
            rows = connection.execute(
                "SELECT path, media_type, content_sha256 "
                "FROM release_artifacts WHERE release_id = ? ORDER BY path",
                (str(release_id),),
            ).fetchall()
            artifacts = [
                {
                    "path": row[0],
                    "media_type": row[1],
                    "content_sha256": row[2],
                }
                for row in rows
            ]
            return hashlib.sha256(canonical_json_bytes(artifacts)).hexdigest()

        connection.create_function(
            "kapi_local_actor_binding",
            0,
            lambda: _LOCAL_ACTOR_BINDING.get(),
        )
        connection.create_function(
            "kapi_sha256",
            1,
            lambda value: hashlib.sha256(str(value).encode("utf-8")).hexdigest(),
            deterministic=True,
        )
        connection.create_function(
            "kapi_release_artifact_manifest_sha256",
            1,
            release_artifact_manifest_sha256,
        )
        connection.create_function(
            "kapi_validate_calculation_diagnostics",
            2,
            _calculation_diagnostics_are_valid,
            deterministic=True,
        )
        for schema_path in _SCHEMA_PATHS:
            connection.executescript(schema_path.read_text(encoding="utf-8"))
    except (OSError, sqlite3.Error) as exc:
        raise StoreError(f"could not initialize KAPI database: {exc}") from exc
    return connection


@contextmanager
def _local_actor_binding(principal_id: str):
    """Bind an actor for trusted-adapter development and adversarial tests.

    This private ContextVar is not authentication and is not exposed by the
    CLI. A process/file owner can bypass it. Current fail-closed controls are
    the exact unreviewed initial state, absence of an operator-review or
    publication edge, and the hard-failed trusted-verifier gate; none is a
    production host-security boundary.
    """

    token = _LOCAL_ACTOR_BINDING.set(principal_id)
    try:
        yield
    finally:
        _LOCAL_ACTOR_BINDING.reset(token)


def ingest_bundle(connection: sqlite3.Connection, bundle: Mapping[str, Any]) -> None:
    """Validate and atomically append one canonical input bundle.

    Duplicate primary IDs, duplicate natural identities, and conflicting price
    facts fail the complete transaction.  Re-ingesting an identical bundle is
    intentionally an error rather than an upsert.
    """

    if not isinstance(connection, sqlite3.Connection):
        raise TypeError("connection must be sqlite3.Connection")
    normalized = _validate_bundle(bundle)
    _require_initialized(connection)

    savepoint = "kapi_ingest_" + uuid.uuid4().hex
    connection.execute(f"SAVEPOINT {savepoint}")
    try:
        _insert_bundle(connection, normalized)
        connection.execute(f"RELEASE SAVEPOINT {savepoint}")
    except Exception as exc:
        try:
            connection.execute(f"ROLLBACK TO SAVEPOINT {savepoint}")
            connection.execute(f"RELEASE SAVEPOINT {savepoint}")
        except sqlite3.Error:
            pass
        if isinstance(exc, StoreError):
            raise
        if isinstance(exc, sqlite3.IntegrityError):
            raise StoreError(f"bundle conflicts with append-only store: {exc}") from exc
        if isinstance(exc, sqlite3.Error):
            raise StoreError(f"bundle ingestion failed: {exc}") from exc
        raise


def dump_bundle(connection: sqlite3.Connection) -> dict[str, Any]:
    """Return the stored bundle with deterministic record and feature ordering."""

    if not isinstance(connection, sqlite3.Connection):
        raise TypeError("connection must be sqlite3.Connection")
    _require_initialized(connection)
    connection.row_factory = sqlite3.Row
    dataset = connection.execute(
        "SELECT id, schema_version, dataset_kind, metadata_json FROM datasets ORDER BY id"
    ).fetchall()
    if not dataset:
        raise StoreError("the KAPI store does not contain a dataset")
    if len(dataset) != 1:
        raise StoreError("the KAPI store contains more than one dataset")
    root = dataset[0]

    result: dict[str, Any] = _load_object(
        root["metadata_json"], "datasets.metadata_json"
    )
    normalized_root: dict[str, Any] = {
        "schema_version": root["schema_version"],
        "dataset_id": root["id"],
        "dataset_kind": root["dataset_kind"],
        "weeks": [],
        "providers": [],
        "creators": [],
        "models": [],
        "endpoints": [],
        "source_artifacts": [],
        "capability_evidence": [],
        "token_counts": [],
        "price_observations": [],
        "corrections": [],
    }
    overlap = set(result).intersection(normalized_root)
    if overlap:
        raise StoreError(
            "dataset metadata conflicts with normalized fields: "
            + ", ".join(sorted(overlap))
        )
    result.update(normalized_root)

    for row in connection.execute("SELECT * FROM weeks ORDER BY cutoff_at, id"):
        result["weeks"].append(
            _with_metadata({"id": row["id"], "cutoff_at": row["cutoff_at"]}, row)
        )

    for table, key in (("providers", "providers"), ("creators", "creators")):
        for row in connection.execute(f"SELECT * FROM {table} ORDER BY id"):
            record: dict[str, Any] = {"id": row["id"]}
            if row["name"] is not None:
                record["name"] = row["name"]
            result[key].append(_with_metadata(record, row))

    for row in connection.execute("SELECT * FROM models ORDER BY id"):
        result["models"].append(
            _with_metadata(
                {
                    "id": row["id"],
                    "creator_id": row["creator_id"],
                    "version": row["version"],
                    "alias_type": row["alias_type"],
                    "immutable_version": bool(row["immutable_version"]),
                },
                row,
            )
        )

    features: dict[str, list[str]] = {}
    for row in connection.execute(
        "SELECT endpoint_id, feature_name FROM endpoint_features "
        "ORDER BY endpoint_id, feature_name"
    ):
        features.setdefault(row["endpoint_id"], []).append(row["feature_name"])
    for row in connection.execute("SELECT * FROM endpoints ORDER BY id"):
        result["endpoints"].append(
            _with_metadata(
                {
                    "id": row["id"],
                    "provider_id": row["provider_id"],
                    "model_id": row["model_id"],
                    "configuration_id": row["configuration_id"],
                    "region": row["region"],
                    "tier": row["tier"],
                    "public": bool(row["is_public"]),
                    "ga": bool(row["is_ga"]),
                    "synchronous": bool(row["is_synchronous"]),
                    "on_demand": bool(row["is_on_demand"]),
                    "available_us": bool(row["is_available_us"]),
                    "standard_list_price": bool(row["is_standard_list_price"]),
                    "reasoning_mode": row["reasoning_mode"],
                    "billing_tokenizer": row["billing_tokenizer"],
                    "tokenizer_reproducible": bool(row["tokenizer_reproducible"]),
                    "features": features.get(row["id"], []),
                    "available_from": row["available_from"],
                    "available_until": row["available_until"],
                },
                row,
            )
        )

    for row in connection.execute("SELECT * FROM source_artifacts ORDER BY id"):
        result["source_artifacts"].append(
            _with_metadata(
                {
                    "id": row["id"],
                    "url": row["url"],
                    "retrieved_at": row["retrieved_at"],
                    "evidence_grade": row["evidence_grade"],
                    "media_type": row["media_type"],
                    "content_sha256": row["content_sha256"],
                    "snapshot_path": row["snapshot_path"],
                    "license_note": row["license_note"],
                },
                row,
            )
        )

    for row in connection.execute("SELECT * FROM capability_evidence ORDER BY id"):
        result["capability_evidence"].append(
            _with_metadata(
                {
                    "id": row["id"],
                    "model_id": row["model_id"],
                    "endpoint_id": row["endpoint_id"],
                    "metric": row["metric"],
                    "metric_version": row["metric_version"],
                    "score": row["score"],
                    "configuration_id": row["configuration_id"],
                    "evaluated_at": row["evaluated_at"],
                    "data_vintage": row["data_vintage"],
                    "source_id": row["source_id"],
                    "evidence_grade": row["evidence_grade"],
                },
                row,
            )
        )

    for row in connection.execute(
        "SELECT * FROM token_counts ORDER BY endpoint_id, profile_id, size_variant"
    ):
        record = {
            "endpoint_id": row["endpoint_id"],
            "profile_id": row["profile_id"],
            "input_tokens": row["input_tokens"],
            "output_tokens": row["output_tokens"],
            "input_payload_sha256": row["input_payload_sha256"],
            "output_payload_sha256": row["output_payload_sha256"],
            "billing_tokenizer": row["billing_tokenizer"],
            "size_variant": row["size_variant"],
        }
        if row["id"] is not None:
            record["id"] = row["id"]
        result["token_counts"].append(_with_metadata(record, row))

    for row in connection.execute("SELECT * FROM price_observations ORDER BY id"):
        record = {
            "id": row["id"],
            "endpoint_id": row["endpoint_id"],
            "week_id": row["week_id"],
            "component": row["component"],
            "amount_per_million": row["amount_per_million"],
            "currency": row["currency"],
            "unit": row["unit"],
            "region": row["region"],
            "tier": row["tier"],
            "context_min_tokens": row["context_min_tokens"],
            "context_max_tokens": row["context_max_tokens"],
            "effective_at": row["effective_at"],
            "observed_at": row["observed_at"],
            "source_id": row["source_id"],
            "evidence_grade": row["evidence_grade"],
        }
        if row["supersedes_present"]:
            record["supersedes_observation_id"] = row["supersedes_observation_id"]
        result["price_observations"].append(_with_metadata(record, row))

    for row in connection.execute("SELECT record_json FROM corrections ORDER BY id"):
        record = _load_object(row["record_json"], "corrections.record_json")
        result["corrections"].append(record)

    return result


def _validate_bundle(bundle: Mapping[str, Any]) -> dict[str, Any]:
    if not isinstance(bundle, Mapping):
        raise StoreError("bundle must be an object")
    missing = _TOP_LEVEL_KEYS.difference(bundle)
    if missing:
        raise StoreError("bundle is missing keys: " + ", ".join(sorted(missing)))

    schema_version = _text(bundle, "schema_version")
    dataset_id = _text(bundle, "dataset_id")
    dataset_kind = _text(bundle, "dataset_kind")
    if dataset_kind not in {"synthetic", "observed"}:
        raise StoreError("dataset_kind must be 'synthetic' or 'observed'")

    normalized: dict[str, Any] = {
        "schema_version": schema_version,
        "dataset_id": dataset_id,
        "dataset_kind": dataset_kind,
        "_metadata_json": _canonical_json(
            {key: value for key, value in bundle.items() if key not in _TOP_LEVEL_KEYS}
        ),
    }
    for key in _TOP_LEVEL_KEYS.difference(
        {"schema_version", "dataset_id", "dataset_kind"}
    ):
        value = bundle[key]
        if not isinstance(value, list):
            raise StoreError(f"{key} must be an array")
        normalized[key] = [
            _mapping(item, f"{key}[{index}]") for index, item in enumerate(value)
        ]

    _validate_unique_ids(normalized)
    _validate_records(normalized)
    return normalized


def _validate_unique_ids(bundle: Mapping[str, Any]) -> None:
    for key in (
        "weeks",
        "providers",
        "creators",
        "models",
        "endpoints",
        "source_artifacts",
        "capability_evidence",
        "price_observations",
        "corrections",
    ):
        seen: set[str] = set()
        for index, record in enumerate(bundle[key]):
            record_id = _text(record, "id", f"{key}[{index}]")
            if record_id in seen:
                raise StoreError(f"duplicate {key} id: {record_id}")
            seen.add(record_id)

    seen_tokens: set[tuple[str, str, str]] = set()
    seen_token_ids: set[str] = set()
    for index, record in enumerate(bundle["token_counts"]):
        identity = (
            _text(record, "endpoint_id", f"token_counts[{index}]"),
            _text(record, "profile_id", f"token_counts[{index}]"),
            _text(record, "size_variant", f"token_counts[{index}]"),
        )
        if identity in seen_tokens:
            raise StoreError("duplicate token_counts identity: " + "/".join(identity))
        seen_tokens.add(identity)
        record_id = _text(record, "id", f"token_counts[{index}]")
        if record_id in seen_token_ids:
            raise StoreError(f"duplicate token_counts id: {record_id}")
        seen_token_ids.add(record_id)


def _validate_records(bundle: Mapping[str, Any]) -> None:
    for index, record in enumerate(bundle["weeks"]):
        context = f"weeks[{index}]"
        _text(record, "id", context)
        _timestamp(record, "cutoff_at", context)

    for key in ("providers", "creators"):
        for index, record in enumerate(bundle[key]):
            context = f"{key}[{index}]"
            _text(record, "id", context)
            if "name" in record:
                _text(record, "name", context)

    creator_ids = {record["id"] for record in bundle["creators"]}
    for index, record in enumerate(bundle["models"]):
        context = f"models[{index}]"
        _text(record, "id", context)
        creator_id = _text(record, "creator_id", context)
        if creator_id not in creator_ids:
            raise StoreError(
                f"{context}.creator_id does not identify a bundled creator"
            )
        _text(record, "version", context)
        _text(record, "alias_type", context)
        _boolean(record, "immutable_version", context)
        released = None
        if record.get("released_at") is not None:
            released = _timestamp(record, "released_at", context)
        if record.get("retired_at") is not None:
            retired = _timestamp(record, "retired_at", context)
            if released is not None and retired <= released:
                raise StoreError(f"{context}.retired_at must follow released_at")
        for key in ("tokenizer", "modality"):
            if record.get(key) is not None:
                _text(record, key, context)

    provider_ids = {record["id"] for record in bundle["providers"]}
    model_ids = {record["id"] for record in bundle["models"]}
    endpoint_map: dict[str, Mapping[str, Any]] = {}
    endpoint_natural: set[tuple[Any, ...]] = set()
    for index, record in enumerate(bundle["endpoints"]):
        context = f"endpoints[{index}]"
        endpoint_id = _text(record, "id", context)
        endpoint_map[endpoint_id] = record
        provider_id = _text(record, "provider_id", context)
        model_id = _text(record, "model_id", context)
        if provider_id not in provider_ids:
            raise StoreError(
                f"{context}.provider_id does not identify a bundled provider"
            )
        if model_id not in model_ids:
            raise StoreError(f"{context}.model_id does not identify a bundled model")
        for key in (
            "configuration_id",
            "region",
            "tier",
            "reasoning_mode",
            "billing_tokenizer",
        ):
            _text(record, key, context)
        for key in (
            "public",
            "ga",
            "synchronous",
            "on_demand",
            "available_us",
            "standard_list_price",
            "tokenizer_reproducible",
        ):
            _boolean(record, key, context)
        if "first_party" in record:
            _boolean(record, "first_party", context)
        features = record.get("features")
        if not isinstance(features, list):
            raise StoreError(f"{context}.features must be an array")
        if any(not isinstance(item, str) or not item.strip() for item in features):
            raise StoreError(f"{context}.features must contain non-empty strings")
        if len(features) != len(set(features)):
            raise StoreError(f"{context}.features contains duplicates")
        available_from = _timestamp(record, "available_from", context)
        available_until = record.get("available_until")
        if available_until is not None:
            until = _timestamp(record, "available_until", context)
            if until <= available_from:
                raise StoreError(
                    f"{context}.available_until must follow available_from"
                )
        natural = (
            provider_id,
            model_id,
            record["configuration_id"],
            record["region"],
            record["tier"],
            record["reasoning_mode"],
            record["available_from"],
        )
        if natural in endpoint_natural:
            raise StoreError(f"conflicting endpoint identity at {context}")
        endpoint_natural.add(natural)

    source_grades: dict[str, str] = {}
    for index, record in enumerate(bundle["source_artifacts"]):
        context = f"source_artifacts[{index}]"
        source_id = _text(record, "id", context)
        url = _text(record, "url", context)
        allowed_schemes = {"http", "https"}
        if bundle["dataset_kind"] == "synthetic":
            allowed_schemes.add("synthetic")
        if urlsplit(url).scheme not in allowed_schemes:
            raise StoreError(
                f"{context}.url must be HTTP(S), or synthetic: in a synthetic bundle"
            )
        _timestamp(record, "retrieved_at", context)
        if record.get("effective_at") is not None:
            _timestamp(record, "effective_at", context)
        source_grades[source_id] = _grade(record, "evidence_grade", context)
        _text(record, "media_type", context)
        digest = _sha256(record, "content_sha256", context)
        snapshot_path = _text(record, "snapshot_path", context)
        if snapshot_path.startswith("embedded://"):
            if bundle["dataset_kind"] != "synthetic":
                raise StoreError(
                    f"{context}.snapshot_path embeds content in a non-synthetic bundle"
                )
            if snapshot_path != f"embedded://source_artifacts/{source_id}":
                raise StoreError(
                    f"{context}.snapshot_path must identify its source artifact"
                )
            if "synthetic_content" not in record:
                raise StoreError(f"{context}.synthetic_content is required")
            content_bytes = (
                _canonical_json(record["synthetic_content"]) + "\n"
            ).encode("utf-8")
            actual_digest = hashlib.sha256(content_bytes).hexdigest()
            if actual_digest != digest:
                raise StoreError(
                    f"{context}.content_sha256 does not match embedded snapshot bytes"
                )
        _string(record, "license_note", context)
        if record.get("reviewer") is not None:
            _text(record, "reviewer", context)

    for index, record in enumerate(bundle["capability_evidence"]):
        context = f"capability_evidence[{index}]"
        _text(record, "id", context)
        model_id = _text(record, "model_id", context)
        endpoint_id = _text(record, "endpoint_id", context)
        if endpoint_id not in endpoint_map:
            raise StoreError(
                f"{context}.endpoint_id does not identify a bundled endpoint"
            )
        endpoint = endpoint_map[endpoint_id]
        if model_id != endpoint["model_id"]:
            raise StoreError(f"{context}.model_id conflicts with the endpoint model")
        for key in ("metric", "metric_version", "configuration_id", "data_vintage"):
            _text(record, key, context)
        if record["configuration_id"] != endpoint["configuration_id"]:
            raise StoreError(f"{context}.configuration_id conflicts with the endpoint")
        _decimal(record, "score", context, nonnegative=True)
        for key in ("score_lower", "score_upper", "threshold_score"):
            if record.get(key) is not None:
                _decimal(record, key, context, nonnegative=True)
        _timestamp(record, "evaluated_at", context)
        for key in ("qualification_from", "qualification_until"):
            if record.get(key) is not None:
                _timestamp(record, key, context)
        source_id = _text(record, "source_id", context)
        if source_id not in source_grades:
            raise StoreError(f"{context}.source_id does not identify a bundled source")
        capability_grade = _grade(record, "evidence_grade", context)
        if capability_grade != source_grades[source_id]:
            raise StoreError(f"{context}.evidence_grade conflicts with its source")

    payloads: dict[tuple[str, str], tuple[str, str]] = {}
    for index, record in enumerate(bundle["token_counts"]):
        context = f"token_counts[{index}]"
        endpoint_id = _text(record, "endpoint_id", context)
        if endpoint_id not in endpoint_map:
            raise StoreError(
                f"{context}.endpoint_id does not identify a bundled endpoint"
            )
        profile_id = _text(record, "profile_id", context)
        size_variant = _text(record, "size_variant", context)
        _integer(record, "input_tokens", context, minimum=0)
        _integer(record, "output_tokens", context, minimum=0)
        input_hash = _sha256(record, "input_payload_sha256", context)
        output_hash = _sha256(record, "output_payload_sha256", context)
        tokenizer = _text(record, "billing_tokenizer", context)
        if tokenizer != endpoint_map[endpoint_id]["billing_tokenizer"]:
            raise StoreError(f"{context}.billing_tokenizer conflicts with the endpoint")
        payload_key = (profile_id, size_variant)
        payload_hashes = (input_hash, output_hash)
        prior = payloads.setdefault(payload_key, payload_hashes)
        if prior != payload_hashes:
            raise StoreError(
                f"{context} does not use the canonical payload hashes for {profile_id}"
            )

    week_ids = {record["id"] for record in bundle["weeks"]}
    price_by_id = {record["id"]: record for record in bundle["price_observations"]}
    price_ids = set(price_by_id)
    price_natural: dict[tuple[Any, ...], list[str]] = {}
    price_identity_by_id: dict[str, tuple[Any, ...]] = {}
    supersession: dict[str, str] = {}
    for index, record in enumerate(bundle["price_observations"]):
        context = f"price_observations[{index}]"
        price_id = _text(record, "id", context)
        endpoint_id = _text(record, "endpoint_id", context)
        if endpoint_id not in endpoint_map:
            raise StoreError(
                f"{context}.endpoint_id does not identify a bundled endpoint"
            )
        week_id = _text(record, "week_id", context)
        if week_id not in week_ids:
            raise StoreError(f"{context}.week_id does not identify a bundled week")
        for key in ("component", "unit", "region", "tier"):
            _text(record, key, context)
        _decimal(record, "amount_per_million", context, nonnegative=True)
        currency = _text(record, "currency", context)
        if len(currency) != 3 or currency != currency.upper():
            raise StoreError(
                f"{context}.currency must be a three-letter uppercase code"
            )
        context_min = _integer(record, "context_min_tokens", context, minimum=0)
        context_max = _integer(record, "context_max_tokens", context, minimum=0)
        if context_max < context_min:
            raise StoreError(
                f"{context}.context_max_tokens is below context_min_tokens"
            )
        for key in (
            "cache_treatment",
            "batch_treatment",
            "priority_treatment",
            "tool_fee_treatment",
        ):
            if record.get(key) is not None:
                _text(record, key, context)
        _timestamp(record, "effective_at", context)
        _timestamp(record, "observed_at", context)
        source_id = _text(record, "source_id", context)
        if source_id not in source_grades:
            raise StoreError(f"{context}.source_id does not identify a bundled source")
        price_grade = _grade(record, "evidence_grade", context)
        if price_grade != source_grades[source_id]:
            raise StoreError(f"{context}.evidence_grade conflicts with its source")
        endpoint = endpoint_map[endpoint_id]
        if record["region"] != endpoint["region"] or record["tier"] != endpoint["tier"]:
            raise StoreError(f"{context} region/tier conflicts with the endpoint")
        if "supersedes_observation_id" in record:
            target = record["supersedes_observation_id"]
            if target is not None:
                if not isinstance(target, str) or not target.strip():
                    raise StoreError(
                        f"{context}.supersedes_observation_id must be an ID or null"
                    )
                if target not in price_ids:
                    raise StoreError(f"{context} supersedes an unknown observation")
                if target == price_id:
                    raise StoreError(f"{context} cannot supersede itself")
                supersession[price_id] = target
        natural = (
            endpoint_id,
            week_id,
            record["component"],
            currency,
            record["unit"],
            record["region"],
            record["tier"],
            context_min,
            context_max,
            record["effective_at"],
        )
        price_identity_by_id[price_id] = natural
        price_natural.setdefault(natural, []).append(price_id)
    _reject_cycles(supersession, "price observation supersession")
    for child, parent in supersession.items():
        if price_identity_by_id.get(child) != price_identity_by_id.get(parent):
            raise StoreError(
                "price observation supersession must preserve the full applicability identity"
            )
    for identity_ids in price_natural.values():
        if len(identity_ids) <= 1:
            continue
        members = set(identity_ids)
        internal_edges = {
            child: parent
            for child, parent in supersession.items()
            if child in members and parent in members
        }
        active_heads = members - set(internal_edges.values())
        if len(internal_edges) != len(members) - 1 or len(active_heads) != 1:
            raise StoreError(
                "conflicting prices for one applicability identity require an explicit "
                "single supersession chain"
            )

    correction_ids = {record["id"] for record in bundle["corrections"]}
    correction_supersession: dict[str, str] = {}
    for index, record in enumerate(bundle["corrections"]):
        context = f"corrections[{index}]"
        correction_id = _text(record, "id", context)
        for key in ("detected_at",):
            if key in record and record[key] is not None:
                _timestamp(record, key, context)
        for key in ("impact", "resolution", "approved_by", "new_vintage"):
            if key in record and record[key] is not None:
                _string(record, key, context)
        prior = record.get("supersedes_correction_id")
        if prior is not None:
            if not isinstance(prior, str) or prior not in correction_ids:
                raise StoreError(f"{context}.supersedes_correction_id is unknown")
            if prior == correction_id:
                raise StoreError(f"{context} cannot supersede itself")
            correction_supersession[correction_id] = prior
        target_id = _text(record, "superseded_observation_id", context)
        replacement_id = _text(record, "replacement_observation_id", context)
        for key, value in (
            ("superseded_observation_id", target_id),
            ("replacement_observation_id", replacement_id),
        ):
            if value not in price_ids:
                raise StoreError(f"{context}.{key} is unknown")
        if target_id == replacement_id:
            raise StoreError(f"{context} must link two different observations")
        if price_identity_by_id[target_id] != price_identity_by_id[replacement_id]:
            raise StoreError(
                f"{context} observations do not share one applicability identity"
            )
        if price_by_id[replacement_id].get("supersedes_observation_id") != target_id:
            raise StoreError(
                f"{context}.replacement_observation_id must explicitly "
                "supersede the target"
            )
    _reject_cycles(correction_supersession, "correction supersession")
    correction_parent_counts: dict[str, int] = {}
    for parent in correction_supersession.values():
        correction_parent_counts[parent] = correction_parent_counts.get(parent, 0) + 1
    if any(count > 1 for count in correction_parent_counts.values()):
        raise StoreError("correction supersession cannot branch")


def _insert_bundle(connection: sqlite3.Connection, bundle: Mapping[str, Any]) -> None:
    dataset_id = bundle["dataset_id"]
    connection.execute(
        "INSERT INTO datasets(singleton, id, schema_version, dataset_kind, metadata_json) "
        "VALUES(1, ?, ?, ?, ?)",
        (
            dataset_id,
            bundle["schema_version"],
            bundle["dataset_kind"],
            bundle["_metadata_json"],
        ),
    )

    for record in bundle["weeks"]:
        connection.execute(
            "INSERT INTO weeks(id, dataset_id, cutoff_at, metadata_json) VALUES(?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["cutoff_at"],
                _extras(record, _WEEK_FIELDS),
            ),
        )
    for table in ("providers", "creators"):
        for record in bundle[table]:
            connection.execute(
                f"INSERT INTO {table}(id, dataset_id, name, metadata_json) VALUES(?, ?, ?, ?)",
                (
                    record["id"],
                    dataset_id,
                    record.get("name"),
                    _extras(record, _PARTY_FIELDS),
                ),
            )
    for record in bundle["models"]:
        connection.execute(
            "INSERT INTO models(id, dataset_id, creator_id, version, alias_type, "
            "immutable_version, released_at, retired_at, tokenizer, modality, "
            "metadata_json) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["creator_id"],
                record["version"],
                record["alias_type"],
                int(record["immutable_version"]),
                record.get("released_at"),
                record.get("retired_at"),
                record.get("tokenizer"),
                record.get("modality"),
                _extras(record, _MODEL_FIELDS),
            ),
        )
    for record in bundle["endpoints"]:
        connection.execute(
            "INSERT INTO endpoints("
            "id, dataset_id, provider_id, model_id, configuration_id, region, tier, "
            "is_public, is_ga, is_synchronous, is_on_demand, is_available_us, "
            "is_standard_list_price, reasoning_mode, billing_tokenizer, "
            "tokenizer_reproducible, available_from, available_until, metadata_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["provider_id"],
                record["model_id"],
                record["configuration_id"],
                record["region"],
                record["tier"],
                int(record["public"]),
                int(record["ga"]),
                int(record["synchronous"]),
                int(record["on_demand"]),
                int(record["available_us"]),
                int(record["standard_list_price"]),
                record["reasoning_mode"],
                record["billing_tokenizer"],
                int(record["tokenizer_reproducible"]),
                record["available_from"],
                record.get("available_until"),
                _extras(record, _ENDPOINT_FIELDS),
            ),
        )
        for feature in sorted(record["features"]):
            connection.execute(
                "INSERT INTO endpoint_features(endpoint_id, feature_name) VALUES(?, ?)",
                (record["id"], feature),
            )
    for record in bundle["source_artifacts"]:
        connection.execute(
            "INSERT INTO source_artifacts("
            "id, dataset_id, url, retrieved_at, effective_at, evidence_grade, media_type, "
            "content_sha256, snapshot_path, license_note, reviewer, metadata_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["url"],
                record["retrieved_at"],
                record.get("effective_at"),
                record["evidence_grade"],
                record["media_type"],
                record["content_sha256"],
                record["snapshot_path"],
                record["license_note"],
                record.get("reviewer"),
                _extras(record, _SOURCE_FIELDS),
            ),
        )
    for record in bundle["capability_evidence"]:
        connection.execute(
            "INSERT INTO capability_evidence("
            "id, dataset_id, model_id, endpoint_id, metric, metric_version, score, "
            "score_lower, score_upper, threshold_score, configuration_id, evaluated_at, "
            "data_vintage, source_id, evidence_grade, qualification_from, "
            "qualification_until, metadata_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["model_id"],
                record["endpoint_id"],
                record["metric"],
                record["metric_version"],
                record["score"],
                record.get("score_lower"),
                record.get("score_upper"),
                record.get("threshold_score"),
                record["configuration_id"],
                record["evaluated_at"],
                record["data_vintage"],
                record["source_id"],
                record["evidence_grade"],
                record.get("qualification_from"),
                record.get("qualification_until"),
                _extras(record, _CAPABILITY_FIELDS),
            ),
        )
    for record in bundle["token_counts"]:
        connection.execute(
            "INSERT INTO token_counts("
            "id, dataset_id, endpoint_id, profile_id, input_tokens, output_tokens, "
            "input_payload_sha256, output_payload_sha256, billing_tokenizer, "
            "size_variant, metadata_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record.get("id"),
                dataset_id,
                record["endpoint_id"],
                record["profile_id"],
                record["input_tokens"],
                record["output_tokens"],
                record["input_payload_sha256"],
                record["output_payload_sha256"],
                record["billing_tokenizer"],
                record["size_variant"],
                _extras(record, _TOKEN_FIELDS),
            ),
        )
    for record in bundle["price_observations"]:
        connection.execute(
            "INSERT INTO price_observations("
            "id, dataset_id, endpoint_id, week_id, component, amount_per_million, "
            "currency, unit, region, tier, context_min_tokens, context_max_tokens, "
            "cache_treatment, batch_treatment, priority_treatment, tool_fee_treatment, "
            "effective_at, observed_at, source_id, evidence_grade, "
            "supersedes_observation_id, supersedes_present, metadata_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record["endpoint_id"],
                record["week_id"],
                record["component"],
                record["amount_per_million"],
                record["currency"],
                record["unit"],
                record["region"],
                record["tier"],
                record["context_min_tokens"],
                record["context_max_tokens"],
                record.get("cache_treatment"),
                record.get("batch_treatment"),
                record.get("priority_treatment"),
                record.get("tool_fee_treatment"),
                record["effective_at"],
                record["observed_at"],
                record["source_id"],
                record["evidence_grade"],
                record.get("supersedes_observation_id"),
                int("supersedes_observation_id" in record),
                _extras(record, _PRICE_FIELDS),
            ),
        )
    for record in bundle["corrections"]:
        connection.execute(
            "INSERT INTO corrections("
            "id, dataset_id, detected_at, impact, resolution, approved_by, new_vintage, "
            "supersedes_correction_id, superseded_observation_id, "
            "replacement_observation_id, record_json"
            ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                record["id"],
                dataset_id,
                record.get("detected_at"),
                record.get("impact"),
                record.get("resolution"),
                record.get("approved_by"),
                record.get("new_vintage"),
                record.get("supersedes_correction_id"),
                record.get("superseded_observation_id"),
                record.get("replacement_observation_id"),
                _canonical_json(record),
            ),
        )


def _require_initialized(connection: sqlite3.Connection) -> None:
    connection.execute("PRAGMA foreign_keys = ON")
    if connection.execute("PRAGMA foreign_keys").fetchone()[0] != 1:
        raise StoreError("SQLite foreign-key enforcement is not enabled")
    row = connection.execute("PRAGMA user_version").fetchone()
    if row is None or row[0] != 2:
        raise StoreError("KAPI schema version 2 is not initialized")


def _mapping(value: Any, context: str) -> Mapping[str, Any]:
    if not isinstance(value, Mapping):
        raise StoreError(f"{context} must be an object")
    if not all(isinstance(key, str) for key in value):
        raise StoreError(f"{context} keys must be strings")
    return value


def _string(record: Mapping[str, Any], key: str, context: str = "bundle") -> str:
    if key not in record or not isinstance(record[key], str):
        raise StoreError(f"{context}.{key} must be a string")
    return record[key]


def _text(record: Mapping[str, Any], key: str, context: str = "bundle") -> str:
    value = _string(record, key, context)
    if not value.strip():
        raise StoreError(f"{context}.{key} must not be empty")
    return value


def _boolean(record: Mapping[str, Any], key: str, context: str) -> bool:
    if key not in record or type(record[key]) is not bool:
        raise StoreError(f"{context}.{key} must be a boolean")
    return record[key]


def _integer(record: Mapping[str, Any], key: str, context: str, *, minimum: int) -> int:
    if key not in record or type(record[key]) is not int:
        raise StoreError(f"{context}.{key} must be an integer")
    if record[key] < minimum:
        raise StoreError(f"{context}.{key} must be at least {minimum}")
    return record[key]


def _decimal(
    record: Mapping[str, Any], key: str, context: str, *, nonnegative: bool
) -> str:
    value = _text(record, key, context)
    try:
        parsed = Decimal(value)
    except InvalidOperation as exc:
        raise StoreError(f"{context}.{key} must be a Decimal string") from exc
    if not parsed.is_finite():
        raise StoreError(f"{context}.{key} must be finite")
    if nonnegative and parsed < 0:
        raise StoreError(f"{context}.{key} must not be negative")
    return value


def _timestamp(record: Mapping[str, Any], key: str, context: str) -> datetime:
    value = _text(record, key, context)
    if not value.endswith("Z"):
        raise StoreError(
            f"{context}.{key} must be an ISO-8601 UTC timestamp ending in Z"
        )
    try:
        parsed = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError as exc:
        raise StoreError(f"{context}.{key} is not a valid ISO-8601 timestamp") from exc
    if parsed.utcoffset() is None or parsed.utcoffset().total_seconds() != 0:
        raise StoreError(f"{context}.{key} must be UTC")
    return parsed


def _sha256(record: Mapping[str, Any], key: str, context: str) -> str:
    value = _text(record, key, context)
    if not _SHA256_RE.fullmatch(value):
        raise StoreError(f"{context}.{key} must be a lowercase SHA-256 hex digest")
    return value


def _grade(record: Mapping[str, Any], key: str, context: str) -> str:
    value = _text(record, key, context)
    if value not in {"A", "B", "C", "D"}:
        raise StoreError(f"{context}.{key} must be A, B, C, or D")
    return value


def _reject_cycles(edges: Mapping[str, str], context: str) -> None:
    for start in edges:
        seen: set[str] = set()
        node = start
        while node in edges:
            if node in seen:
                raise StoreError(f"{context} contains a cycle")
            seen.add(node)
            node = edges[node]


def _canonical_json(value: Any) -> str:
    try:
        return json.dumps(
            value,
            ensure_ascii=False,
            sort_keys=True,
            separators=(",", ":"),
            allow_nan=False,
        )
    except (TypeError, ValueError) as exc:
        raise StoreError(f"value is not canonical-JSON compatible: {exc}") from exc


def _extras(record: Mapping[str, Any], known_fields: Iterable[str]) -> str:
    known = set(known_fields)
    return _canonical_json(
        {key: value for key, value in record.items() if key not in known}
    )


def _load_object(value: str, context: str) -> dict[str, Any]:
    try:
        decoded = json.loads(value)
    except (TypeError, json.JSONDecodeError) as exc:
        raise StoreError(f"{context} is corrupt JSON") from exc
    if not isinstance(decoded, dict):
        raise StoreError(f"{context} is not a JSON object")
    return decoded


def _with_metadata(record: dict[str, Any], row: sqlite3.Row) -> dict[str, Any]:
    extras = _load_object(row["metadata_json"], "metadata_json")
    overlap = set(extras).intersection(record)
    if overlap:
        raise StoreError(
            "metadata conflicts with normalized fields: " + ", ".join(sorted(overlap))
        )
    extras.update(record)
    return extras
