"""Append-only Week 0 lifecycle controls for KAPI shadow artifacts.

This module records immutable snapshots, calculations, signoffs, releases and
incidents.  It deliberately has no network or publication capability.
"""

from __future__ import annotations

import re
import sqlite3
import unicodedata
from collections.abc import Mapping
from datetime import datetime, timezone
from typing import Any

from .governance import (
    CURRENT_UNREVIEWED_LABEL,
    GovernanceError,
    POLICY_ID,
    POLICY_VERSION,
    bind_unreviewed_release,
)
from .util import canonical_json_bytes, canonical_json_text, sha256_bytes


class LifecycleError(ValueError):
    """Raised when a lifecycle envelope fails a fail-closed control."""


_HEX = frozenset("0123456789abcdef")
_UTC = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$")
_COMMIT = re.compile(r"^(?:[0-9a-f]{40}|[0-9a-f]{64})$")
_NONCOUNTING_BASE_STATES = frozenset(
    {
        "incomplete",
        "unsigned",
        "irreproducible",
        "materially_corrected",
        "withheld_concentration",
    }
)
_ALLOWED_BASE_WEEK_STATES = _NONCOUNTING_BASE_STATES | {"counting"}
_CALLER_DIAGNOSTIC_KEYS: frozenset[str] = frozenset()
_CLAIM_BEARING_DIAGNOSTIC_FRAGMENTS = (
    "approv",
    "assur",
    "attest",
    "authoriz",
    "certif",
    "endorse",
    "external",
    "governance",
    "identity",
    "independen",
    "operator",
    "publish",
    "ready",
    "review",
    "signoff",
    "thirdparty",
    "verif",
)
_UNICODE_CONFUSABLES = str.maketrans(
    {
        "а": "a",
        "е": "e",
        "і": "i",
        "ј": "j",
        "к": "k",
        "о": "o",
        "р": "p",
        "с": "c",
        "у": "y",
        "х": "x",
        "α": "a",
        "β": "b",
        "ε": "e",
        "ι": "i",
        "κ": "k",
        "ο": "o",
        "ρ": "p",
        "τ": "t",
        "υ": "y",
        "χ": "x",
    }
)


def _text(value: Any, label: str) -> str:
    if not isinstance(value, str) or not value.strip():
        raise LifecycleError(f"{label} must be a non-empty string")
    return value.strip()


def _utc(value: Any, label: str) -> str:
    value = _text(value, label)
    if not _UTC.fullmatch(value):
        raise LifecycleError(
            f"{label} must be a canonical ISO-8601 UTC timestamp "
            "(YYYY-MM-DDTHH:MM:SSZ)"
        )
    try:
        parsed = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError as error:
        raise LifecycleError(f"{label} is not a valid UTC timestamp") from error
    if parsed.utcoffset() != timezone.utc.utcoffset(parsed):
        raise LifecycleError(f"{label} must use UTC")
    return value


def _sha(value: Any, label: str) -> str:
    value = _text(value, label)
    if len(value) != 64 or any(character not in _HEX for character in value):
        raise LifecycleError(f"{label} must be a lowercase SHA-256 hex digest")
    return value


def _commit(value: Any, label: str) -> str:
    value = _text(value, label)
    if not _COMMIT.fullmatch(value):
        raise LifecycleError(
            f"{label} must be an exact lowercase 40- or 64-character commit digest"
        )
    return value


def _diagnostic_claim_fragment(value: str) -> str | None:
    normalized = unicodedata.normalize("NFKD", value).casefold()
    normalized = "".join(
        character
        for character in normalized
        if not unicodedata.combining(character)
    ).translate(_UNICODE_CONFUSABLES)
    compact = "".join(character for character in normalized if character.isalnum())
    return next(
        (
            fragment
            for fragment in _CLAIM_BEARING_DIAGNOSTIC_FRAGMENTS
            if fragment in compact
        ),
        None,
    )


def _reject_claim_bearing_diagnostic_content(value: Any, label: str) -> None:
    if isinstance(value, Mapping):
        for key, child in value.items():
            if not isinstance(key, str):
                raise LifecycleError(f"{label} keys must be strings")
            if _diagnostic_claim_fragment(key) is not None:
                raise LifecycleError(
                    f"{label}.{key} is a claim-bearing field controlled by governance"
                )
            _reject_claim_bearing_diagnostic_content(child, f"{label}.{key}")
    elif isinstance(value, list):
        for index, child in enumerate(value):
            _reject_claim_bearing_diagnostic_content(child, f"{label}[{index}]")
    elif isinstance(value, str) and _diagnostic_claim_fragment(value) is not None:
        raise LifecycleError(
            f"{label} contains claim-bearing content controlled by governance"
        )


def _validate_caller_diagnostics(value: Any) -> dict[str, Any]:
    if not isinstance(value, Mapping):
        raise LifecycleError("calculation.diagnostics must be an object")
    _reject_claim_bearing_diagnostic_content(value, "calculation.diagnostics")
    if set(value) != _CALLER_DIAGNOSTIC_KEYS:
        raise LifecycleError(
            "calculation.diagnostics must use the exact empty caller schema; "
            f"unexpected={sorted(set(value))}"
        )
    return {}


def _validate_base_week_states(value: Any, release_kind: str) -> list[str]:
    if not isinstance(value, list):
        raise LifecycleError("base_week_states must be an array")
    if any(
        not isinstance(state, str) or state not in _ALLOWED_BASE_WEEK_STATES
        for state in value
    ):
        raise LifecycleError(
            "base_week_states must contain only controlled lifecycle-state values"
        )
    if release_kind == "final_base":
        if len(value) != 13:
            raise LifecycleError("final_base requires exactly 13 base_week_states")
        disallowed = [state for state in value if state in _NONCOUNTING_BASE_STATES]
        if disallowed:
            raise LifecycleError(
                f"final_base contains noncounting week states: {sorted(set(disallowed))}"
            )
        if any(state != "counting" for state in value):
            raise LifecycleError("final_base week states must all be counting")
    elif release_kind in {"pending_base", "provisional_base"}:
        if value and len(value) != 13:
            raise LifecycleError(
                f"{release_kind} base_week_states must be empty or contain 13 states"
            )
    elif value:
        raise LifecycleError(f"{release_kind} cannot carry base_week_states")
    return list(value)


def _validate_secondary_recalculation(value: Any) -> dict[str, Any]:
    if value is not None:
        raise LifecycleError(
            "policy v1.0.0 rejects every caller-supplied secondary_recalculation; "
            "a future trusted path must recompute from and hash-bind the exact full "
            "frozen calculation"
        )
    return {
        "status": "not_supplied",
        "lifecycle_handling": (
            "no secondary recalculation report accepted by policy v1.0.0"
        ),
        "human_external_review": False,
    }


def register_methodology(
    connection: sqlite3.Connection,
    methodology: Mapping[str, Any],
    *,
    effective_from: str,
    implementation_commit: str,
    review_artifact_manifest_sha256: str,
) -> None:
    """Append a methodology identity and its policy envelope."""

    methodology_id = _text(methodology.get("methodology_id"), "methodology_id")
    version = _text(methodology.get("version"), "methodology.version")
    effective_from = _utc(effective_from, "effective_from")
    implementation_commit = _commit(
        implementation_commit, "implementation_commit"
    )
    review_artifact_manifest_sha256 = _sha(
        review_artifact_manifest_sha256,
        "review_artifact_manifest_sha256",
    )
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
        readiness = methodology.get("readiness_gates", {})
        if not isinstance(readiness, Mapping):
            readiness = {}
        policy = methodology.get("governance_policy", {})
        if not isinstance(policy, Mapping):
            policy = {}
        technical_gate = (
            "passed"
            if readiness.get("technical_go") in {"passed", "passed_technical_go"}
            else "failed"
        )
        operational_gate = (
            "passed"
            if readiness.get("operational_go")
            in {"passed", "passed_operational_go"}
            else "failed"
        )
        connection.execute(
            "INSERT INTO methodology_governance_gates VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                methodology_id,
                version,
                str(policy.get("policy_id", "legacy-kapi-governance")),
                str(
                    policy.get(
                        "policy_version", policy.get("version", version)
                    )
                ),
                technical_gate,
                operational_gate,
                "failed",
                implementation_commit,
                review_artifact_manifest_sha256,
                sha256_bytes(canonical_json_bytes(methodology)),
                effective_from,
            ),
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
    code_commit = _commit(envelope.get("code_commit"), "code_commit")
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
    if release_status != "draft":
        raise LifecycleError(
            "governance v2 accepts new releases only as draft; later states are "
            "append-only governance transitions"
        )
    signoffs = envelope.get("signoffs", [])
    if not isinstance(signoffs, list):
        raise LifecycleError("signoffs must be a list")
    if signoffs:
        raise LifecycleError(
            "legacy free-text signoffs are not an authorization source in governance v2"
        )
    base_states = _validate_base_week_states(
        envelope.get("base_week_states", []), release_kind
    )

    artifacts = envelope.get("artifacts", [])
    if not isinstance(artifacts, list):
        raise LifecycleError("artifacts must be a list")
    normalized_artifacts: list[dict[str, str]] = []
    for position, artifact in enumerate(artifacts):
        if not isinstance(artifact, Mapping):
            raise LifecycleError(f"artifacts[{position}] must be an object")
        normalized_artifacts.append(
            {
                "path": _text(artifact.get("path"), f"artifacts[{position}].path"),
                "media_type": _text(
                    artifact.get("media_type"), f"artifacts[{position}].media_type"
                ),
                "content_sha256": _sha(
                    artifact.get("content_sha256"),
                    f"artifacts[{position}].content_sha256",
                ),
            }
        )
    normalized_artifacts.sort(key=lambda artifact: artifact["path"])
    if len({artifact["path"] for artifact in normalized_artifacts}) != len(
        normalized_artifacts
    ):
        raise LifecycleError("artifacts contain duplicate paths")
    artifact_manifest_sha256 = sha256_bytes(
        canonical_json_bytes(normalized_artifacts)
    )
    calculation_disposition = (
        "eligible"
        if result_status == "complete"
        else "withheld"
        if result_status.startswith("withheld")
        else "incomplete"
    )
    if calculation_disposition == "eligible" and not normalized_artifacts:
        raise LifecycleError("an eligible calculation requires at least one hashed artifact")

    governance = envelope.get("governance")
    if not isinstance(governance, Mapping):
        raise LifecycleError("governance must be an object")
    if governance.get("policy_id") != POLICY_ID or governance.get(
        "policy_version"
    ) != POLICY_VERSION:
        raise LifecycleError(
            f"governance policy must be {POLICY_ID} version {POLICY_VERSION}"
        )

    diagnostics = _validate_caller_diagnostics(result.get("diagnostics", {}))
    secondary_recalculation = _validate_secondary_recalculation(
        envelope.get("secondary_recalculation")
    )
    diagnostics.update(
        {
            "release_kind": release_kind,
            "base_week_states": base_states,
            "secondary_recalculation": secondary_recalculation,
            "calculation_disposition": calculation_disposition,
            "governance_state": "unreviewed",
            "review_label": CURRENT_UNREVIEWED_LABEL,
            "publication_state": "not_authorized",
            "publication_eligible": False,
        }
    )
    released_at = envelope.get("released_at")
    if released_at is not None:
        released_at = _utc(released_at, "released_at")
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
        for artifact in normalized_artifacts:
            connection.execute(
                "INSERT INTO release_artifacts VALUES(?, ?, ?, ?)",
                (
                    release_id,
                    artifact["path"],
                    artifact["media_type"],
                    artifact["content_sha256"],
                ),
            )
        status = bind_unreviewed_release(
            connection,
            {
                "release_id": release_id,
                "operator_principal_id": governance.get("operator_principal_id"),
                "operator_assignment_id": governance.get("operator_assignment_id"),
                "methodology_owner_principal_id": governance.get(
                    "methodology_owner_principal_id"
                ),
                "methodology_owner_assignment_id": governance.get(
                    "methodology_owner_assignment_id"
                ),
                "methodology_id": methodology_id,
                "methodology_version": methodology_version,
                "code_commit": code_commit,
                "artifact_manifest_sha256": artifact_manifest_sha256,
                "calculation_disposition": calculation_disposition,
                "bound_at": created_at,
            },
        )
        connection.execute(f"RELEASE SAVEPOINT {savepoint}")
    except Exception as error:
        connection.execute(f"ROLLBACK TO SAVEPOINT {savepoint}")
        connection.execute(f"RELEASE SAVEPOINT {savepoint}")
        if isinstance(error, LifecycleError):
            raise
        if isinstance(error, GovernanceError):
            raise LifecycleError(f"governance gate rejected lifecycle envelope: {error}") from error
        raise LifecycleError(f"lifecycle envelope conflicts with append-only store: {error}") from error
    return {
        "snapshot_id": snapshot_id,
        "input_manifest_sha256": manifest_sha,
        "calculation_id": calculation_id,
        "calculation_status": database_status,
        "release_id": release_id,
        "release_kind": release_kind,
        "release_status": release_status,
        "calculation_disposition": status["calculation_disposition"],
        "governance_state": status["governance_state"],
        "review_label": status["review_label"],
        "publication_state": status["publication_state"],
        "publication_eligible": bool(status["publication_eligible"]),
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
