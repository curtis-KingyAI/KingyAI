"""Append-only Week 0 lifecycle controls for KAPI shadow artifacts.

This module records immutable snapshots, calculations, signoffs, releases and
incidents.  It deliberately has no network or publication capability.
"""

from __future__ import annotations

import sqlite3
from collections.abc import Mapping, Sequence
from typing import Any

from .util import canonical_json_bytes, canonical_json_text, sha256_bytes


class LifecycleError(ValueError):
    """Raised when a lifecycle envelope fails a fail-closed control."""


_HEX = frozenset("0123456789abcdef")
_REQUIRED_FINAL_SIGNOFFS = frozenset(
    {"primary_operator", "independent_reviewer", "methodology_owner"}
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


def _text(value: Any, label: str) -> str:
    if not isinstance(value, str) or not value.strip():
        raise LifecycleError(f"{label} must be a non-empty string")
    return value


def _utc(value: Any, label: str) -> str:
    value = _text(value, label)
    if not value.endswith("Z"):
        raise LifecycleError(f"{label} must be an ISO-8601 UTC timestamp ending in Z")
    return value


def _sha(value: Any, label: str) -> str:
    value = _text(value, label)
    if len(value) != 64 or any(character not in _HEX for character in value):
        raise LifecycleError(f"{label} must be a lowercase SHA-256 hex digest")
    return value


def register_methodology(
    connection: sqlite3.Connection,
    methodology: Mapping[str, Any],
    *,
    effective_from: str,
) -> None:
    """Append a methodology identity and its policy envelope."""

    methodology_id = _text(methodology.get("methodology_id"), "methodology_id")
    version = _text(methodology.get("version"), "methodology.version")
    effective_from = _utc(effective_from, "effective_from")
    values = (
        methodology_id,
        version,
        canonical_json_text(methodology.get("claim", {})).rstrip("\n"),
        canonical_json_text(methodology.get("scope", {})).rstrip("\n"),
        canonical_json_text(methodology.get("calendar", {})).rstrip("\n"),
        canonical_json_text(methodology.get("evidence_policy", {})).rstrip("\n"),
        canonical_json_text(methodology.get("selection", {})).rstrip("\n"),
        canonical_json_text(methodology.get("concentration", {})).rstrip("\n"),
        canonical_json_text(methodology.get("corrections", {})).rstrip("\n"),
        effective_from,
        None,
    )
    try:
        connection.execute(
            "INSERT INTO methodology_versions VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            values,
        )
    except sqlite3.IntegrityError as error:
        raise LifecycleError(f"methodology registration conflicts with append-only store: {error}") from error


def append_weekly_vintage(
    connection: sqlite3.Connection, envelope: Mapping[str, Any]
) -> dict[str, Any]:
    """Atomically append one immutable calculation/release vintage.

    ``release_kind`` is retained in calculation diagnostics so the unchanged
    schema can distinguish ``pending_base``, ``provisional_base``,
    ``final_base``, ``weekly`` and ``correction`` vintages.
    """

    release_kind = _text(envelope.get("release_kind"), "release_kind")
    if release_kind not in {
        "pending_base",
        "provisional_base",
        "final_base",
        "weekly",
        "correction",
    }:
        raise LifecycleError("release_kind is not a governed lifecycle state")
    dataset_id = _text(envelope.get("dataset_id"), "dataset_id")
    week_id = _text(envelope.get("week_id"), "week_id")
    snapshot_id = _text(envelope.get("snapshot_id"), "snapshot_id")
    calculation_id = _text(envelope.get("calculation_id"), "calculation_id")
    release_id = _text(envelope.get("release_id"), "release_id")
    cutoff_at = _utc(envelope.get("cutoff_at"), "cutoff_at")
    created_at = _utc(envelope.get("created_at"), "created_at")
    calculated_at = _utc(envelope.get("calculated_at"), "calculated_at")
    methodology_id = _text(envelope.get("methodology_id"), "methodology_id")
    methodology_version = _text(
        envelope.get("methodology_version"), "methodology_version"
    )
    code_commit = _text(envelope.get("code_commit"), "code_commit")
    environment_sha256 = _sha(
        envelope.get("environment_sha256"), "environment_sha256"
    )
    inputs = envelope.get("inputs")
    if not isinstance(inputs, list) or not inputs:
        raise LifecycleError("inputs must be a non-empty list")
    normalized_inputs: list[dict[str, str]] = []
    for position, item in enumerate(inputs):
        if not isinstance(item, Mapping):
            raise LifecycleError(f"inputs[{position}] must be an object")
        kind = _text(item.get("input_kind"), f"inputs[{position}].input_kind")
        if kind not in {"source", "capability", "token_count", "price", "recipe", "other"}:
            raise LifecycleError(f"inputs[{position}].input_kind is invalid")
        normalized_inputs.append(
            {
                "input_kind": kind,
                "input_id": _text(item.get("input_id"), f"inputs[{position}].input_id"),
                "content_sha256": _sha(
                    item.get("content_sha256"), f"inputs[{position}].content_sha256"
                ),
            }
        )
    normalized_inputs.sort(key=lambda item: (item["input_kind"], item["input_id"]))
    if len({(item["input_kind"], item["input_id"]) for item in normalized_inputs}) != len(normalized_inputs):
        raise LifecycleError("inputs contain duplicate identities")
    manifest_sha = sha256_bytes(canonical_json_bytes(normalized_inputs))

    result = envelope.get("calculation")
    if not isinstance(result, Mapping):
        raise LifecycleError("calculation must be an object")
    result_status = _text(result.get("status"), "calculation.status")
    database_status = (
        "pending_base"
        if result_status == "pending_base"
        else "withheld"
        if result_status.startswith("withheld")
        else "complete"
        if result_status == "complete"
        else "invalid"
    )
    release_status = _text(envelope.get("release_status"), "release_status")
    if release_status not in {"draft", "provisional", "final", "corrected", "withdrawn"}:
        raise LifecycleError("release_status is invalid")
    signoffs = envelope.get("signoffs", [])
    if not isinstance(signoffs, list):
        raise LifecycleError("signoffs must be a list")
    normalized_signoffs: list[dict[str, str]] = []
    for position, signoff in enumerate(signoffs):
        if not isinstance(signoff, Mapping):
            raise LifecycleError(f"signoffs[{position}] must be an object")
        normalized_signoffs.append(
            {
                "role": _text(signoff.get("role"), f"signoffs[{position}].role"),
                "approver": _text(
                    signoff.get("approver"), f"signoffs[{position}].approver"
                ),
                "signed_at": _utc(
                    signoff.get("signed_at"), f"signoffs[{position}].signed_at"
                ),
                "note": str(signoff.get("note", "")),
            }
        )
    signoff_roles = {item["role"] for item in normalized_signoffs}
    approvers = {item["approver"] for item in normalized_signoffs}
    if release_status in {"final", "corrected"}:
        missing = sorted(_REQUIRED_FINAL_SIGNOFFS - signoff_roles)
        if missing:
            raise LifecycleError(f"final release is missing required signoffs: {missing}")
        if len(approvers) < 2:
            raise LifecycleError("final release requires at least two independent humans")
    if release_kind == "final_base":
        base_states = envelope.get("base_week_states")
        if not isinstance(base_states, list) or len(base_states) != 13:
            raise LifecycleError("final_base requires exactly 13 base_week_states")
        disallowed = [state for state in base_states if state in _NONCOUNTING_BASE_STATES]
        if disallowed:
            raise LifecycleError(
                f"final_base contains noncounting week states: {sorted(set(disallowed))}"
            )
        if any(state != "counting" for state in base_states):
            raise LifecycleError("final_base week states must all be counting")

    diagnostics = dict(result.get("diagnostics", {})) if isinstance(result.get("diagnostics", {}), Mapping) else {}
    diagnostics.update(
        {
            "release_kind": release_kind,
            "base_week_states": envelope.get("base_week_states", []),
            "independent_check": envelope.get("independent_check", {}),
            "publication_authorized": False,
        }
    )
    released_at = envelope.get("released_at")
    if released_at is not None:
        released_at = _utc(released_at, "released_at")
    artifacts = envelope.get("artifacts", [])
    if not isinstance(artifacts, list):
        raise LifecycleError("artifacts must be a list")

    savepoint = "kapi_lifecycle"
    connection.execute(f"SAVEPOINT {savepoint}")
    try:
        connection.execute(
            "INSERT INTO weekly_snapshots VALUES(?, ?, ?, ?, ?, ?)",
            (snapshot_id, dataset_id, week_id, cutoff_at, created_at, manifest_sha),
        )
        connection.executemany(
            "INSERT INTO snapshot_inputs VALUES(?, ?, ?, ?)",
            [
                (snapshot_id, item["input_kind"], item["input_id"], item["content_sha256"])
                for item in normalized_inputs
            ],
        )
        connection.execute(
            "INSERT INTO calculations VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                calculation_id,
                snapshot_id,
                methodology_id,
                methodology_version,
                code_commit,
                environment_sha256,
                calculated_at,
                database_status,
                result.get("index_value"),
                result.get("basket_cost"),
                canonical_json_text(diagnostics).rstrip("\n"),
            ),
        )
        connection.execute(
            "INSERT INTO releases VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
            (
                release_id,
                calculation_id,
                week_id,
                _text(envelope.get("data_vintage"), "data_vintage"),
                release_status,
                released_at,
                _text(envelope.get("permanent_path"), "permanent_path"),
                envelope.get("supersedes_release_id"),
            ),
        )
        for position, artifact in enumerate(artifacts):
            if not isinstance(artifact, Mapping):
                raise LifecycleError(f"artifacts[{position}] must be an object")
            connection.execute(
                "INSERT INTO release_artifacts VALUES(?, ?, ?, ?)",
                (
                    release_id,
                    _text(artifact.get("path"), f"artifacts[{position}].path"),
                    _text(
                        artifact.get("media_type"), f"artifacts[{position}].media_type"
                    ),
                    _sha(
                        artifact.get("content_sha256"),
                        f"artifacts[{position}].content_sha256",
                    ),
                ),
            )
        for signoff in normalized_signoffs:
            connection.execute(
                "INSERT INTO release_signoffs VALUES(?, ?, ?, ?, ?)",
                (
                    release_id,
                    signoff["role"],
                    signoff["approver"],
                    signoff["signed_at"],
                    signoff["note"],
                ),
            )
        connection.execute(f"RELEASE SAVEPOINT {savepoint}")
    except Exception as error:
        connection.execute(f"ROLLBACK TO SAVEPOINT {savepoint}")
        connection.execute(f"RELEASE SAVEPOINT {savepoint}")
        if isinstance(error, LifecycleError):
            raise
        raise LifecycleError(f"lifecycle envelope conflicts with append-only store: {error}") from error
    return {
        "snapshot_id": snapshot_id,
        "input_manifest_sha256": manifest_sha,
        "calculation_id": calculation_id,
        "calculation_status": database_status,
        "release_id": release_id,
        "release_kind": release_kind,
        "release_status": release_status,
        "publication_authorized": False,
    }


def record_incident(connection: sqlite3.Connection, incident: Mapping[str, Any]) -> str:
    """Append an incident state; resolution is a new incident row, not update."""

    incident_id = _text(incident.get("id"), "incident.id")
    status = _text(incident.get("status"), "incident.status")
    if status not in {"open", "resolved", "closed"}:
        raise LifecycleError("incident.status is invalid")
    try:
        connection.execute(
            "INSERT INTO incidents VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
            (
                incident_id,
                _text(incident.get("dataset_id"), "incident.dataset_id"),
                _utc(incident.get("detected_at"), "incident.detected_at"),
                status,
                _text(incident.get("summary"), "incident.summary"),
                str(incident.get("impact", "")),
                incident.get("resolution"),
                _utc(incident.get("closed_at"), "incident.closed_at")
                if incident.get("closed_at") is not None
                else None,
            ),
        )
    except sqlite3.IntegrityError as error:
        raise LifecycleError(f"incident conflicts with append-only store: {error}") from error
    return incident_id
