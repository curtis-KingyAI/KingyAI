"""Forward-only KAPI governance registry and transition controls.

The control model intentionally separates three questions:

* was this exact methodology version externally reviewed;
* was this exact release externally reviewed and recalculated; and
* has an appointed Kingy.ai actor authorized publication?

Routine releases never inherit an external-release claim from a methodology
review. Writes require a private local actor-binding hook and append-only
database triggers. That hook is adapter scaffolding, not authentication; the
current policy leaves calculated releases unreviewed and hard-disables
operator-review, external-review, and publication-ready claims.
"""

from __future__ import annotations

import hashlib
import json
import re
import sqlite3
from collections.abc import Mapping, Sequence
from datetime import datetime, timezone
from typing import Any


POLICY_ID = "kapi-governance"
POLICY_VERSION = "1.0.0"
IDENTITY_REGISTRAR_ID = "kapi-identity-registrar"

CURRENT_UNREVIEWED_LABEL = (
    "Governance status: Unreviewed draft. Automated validation completed for this "
    "artifact; no operator or external methodology review is complete."
)

CURRENT_OPERATOR_REVIEW_LABEL = (
    "Governance status: Operator-reviewed by Kingy.ai and automatically checked. "
    "No external methodology review is complete."
)
METHODOLOGY_REVIEWED_OPERATOR_LABEL = (
    "Governance status: Operator-reviewed by Kingy.ai and automatically checked. "
    "External methodology review is complete; this release was not externally reviewed."
)
EXTERNAL_RELEASE_REVIEW_LABEL = (
    "Governance status: This exact release received named external review and "
    "recalculation under the recorded scope."
)

# Future-policy label aliases. Policy v1.0.0 cannot reach or export them.
OPERATOR_REVIEWED_LABEL = CURRENT_OPERATOR_REVIEW_LABEL
EXTERNAL_REVIEW_LABEL = EXTERNAL_RELEASE_REVIEW_LABEL

REQUIRED_METHODOLOGY_REVIEW_SCOPE = (
    "evidence-policy",
    "methodology",
    "publication-claims",
    "selection",
    "validation",
)
REQUIRED_RELEASE_REVIEW_SCOPE = (
    "artifact-manifest",
    "calculation",
    "recalculation",
    "release-claims",
)
# Compatibility alias: the term is deliberately release-scoped.
REQUIRED_EXTERNAL_REVIEW_SCOPE = REQUIRED_RELEASE_REVIEW_SCOPE

_HEX_SHA256 = re.compile(r"^[0-9a-f]{64}$")
_COMMIT = re.compile(r"^(?:[0-9a-f]{40}|[0-9a-f]{64})$")
_UTC = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$")
_ROLES = frozenset(
    {
        "operator",
        "methodology_owner",
        "external_reviewer",
        "release_authorizer",
        "publication_authorizer",
        "identity_registrar",
        "reviewer_registrar",
    }
)


class GovernanceError(ValueError):
    """Raised when a governance record or transition fails closed."""


def _text(value: Any, label: str) -> str:
    if not isinstance(value, str) or not value.strip():
        raise GovernanceError(f"{label} must be a non-empty string")
    return value.strip()


def _utc(value: Any, label: str) -> str:
    value = _text(value, label)
    if not _UTC.fullmatch(value):
        raise GovernanceError(
            f"{label} must be a canonical ISO-8601 UTC timestamp "
            "(YYYY-MM-DDTHH:MM:SSZ)"
        )
    try:
        parsed = datetime.fromisoformat(value[:-1] + "+00:00")
    except ValueError as error:
        raise GovernanceError(f"{label} is not a valid UTC timestamp") from error
    if parsed.tzinfo is None or parsed.utcoffset() != timezone.utc.utcoffset(parsed):
        raise GovernanceError(f"{label} must use UTC")
    return value


def _sha(value: Any, label: str) -> str:
    value = _text(value, label)
    if not _HEX_SHA256.fullmatch(value):
        raise GovernanceError(f"{label} must be a lowercase SHA-256 hex digest")
    return value


def _commit(value: Any, label: str) -> str:
    value = _text(value, label)
    if not _COMMIT.fullmatch(value):
        raise GovernanceError(
            f"{label} must be an exact lowercase 40- or 64-character commit digest"
        )
    return value


def _canonical_json(value: Any) -> str:
    try:
        return json.dumps(
            value,
            ensure_ascii=False,
            sort_keys=True,
            separators=(",", ":"),
            allow_nan=False,
        )
    except (TypeError, ValueError) as error:
        raise GovernanceError(f"governance value is not canonical JSON: {error}") from error


def _sha_text(value: str) -> str:
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


def _execute(connection: sqlite3.Connection, sql: str, values: tuple[Any, ...]) -> None:
    try:
        connection.execute(sql, values)
    except sqlite3.Error as error:
        raise GovernanceError(str(error)) from error


def _chronological(*moments: tuple[str, str]) -> None:
    parsed = [(_utc(value, label), label) for value, label in moments]
    for (earlier, earlier_label), (later, later_label) in zip(parsed, parsed[1:]):
        if earlier > later:
            raise GovernanceError(f"{earlier_label} must not be after {later_label}")


def register_principal(
    connection: sqlite3.Connection, principal: Mapping[str, Any]
) -> str:
    """Register a principal through a registrar-bound adapter context."""

    principal_id = _text(principal.get("id"), "principal.id")
    identity_key = _text(principal.get("identity_key"), "principal.identity_key").casefold()
    if any(character.isspace() for character in identity_key):
        raise GovernanceError("principal.identity_key must not contain whitespace")
    registrar_id = _text(
        principal.get("registered_by_principal_id", IDENTITY_REGISTRAR_ID),
        "principal.registered_by_principal_id",
    )
    registered_at = _utc(principal.get("registered_at"), "principal.registered_at")
    _execute(
        connection,
        "INSERT INTO governance_principals VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
        (
            principal_id,
            identity_key,
            _text(principal.get("full_name"), "principal.full_name"),
            _text(principal.get("affiliation"), "principal.affiliation"),
            _text(principal.get("principal_kind"), "principal.principal_kind"),
            _sha(
                principal.get("identity_evidence_sha256"),
                "principal.identity_evidence_sha256",
            ),
            registrar_id,
            registered_at,
        ),
    )
    return principal_id


def register_role_assignment(
    connection: sqlite3.Connection, assignment: Mapping[str, Any]
) -> str:
    """Append a registrar-appointed, time-bounded role assignment."""

    assignment_id = _text(assignment.get("id"), "assignment.id")
    role = _text(assignment.get("role"), "assignment.role")
    if role not in _ROLES:
        raise GovernanceError(f"assignment.role must be one of {sorted(_ROLES)}")
    appointer_id = _text(
        assignment.get("appointed_by_principal_id"),
        "assignment.appointed_by_principal_id",
    )
    appointed_at = _utc(assignment.get("appointed_at"), "assignment.appointed_at")
    valid_from = _utc(assignment.get("valid_from"), "assignment.valid_from")
    valid_until_value = assignment.get("valid_until")
    valid_until = (
        _utc(valid_until_value, "assignment.valid_until")
        if valid_until_value is not None
        else None
    )
    _chronological((appointed_at, "assignment.appointed_at"), (valid_from, "assignment.valid_from"))
    if valid_until is not None:
        _chronological((valid_from, "assignment.valid_from"), (valid_until, "assignment.valid_until"))
    _execute(
        connection,
        "INSERT INTO governance_role_assignments VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
        (
            assignment_id,
            _text(assignment.get("principal_id"), "assignment.principal_id"),
            role,
            appointer_id,
            appointed_at,
            valid_from,
            valid_until,
            _sha(assignment.get("appointment_sha256"), "assignment.appointment_sha256"),
        ),
    )
    return assignment_id


def register_external_reviewer(
    connection: sqlite3.Connection, registration: Mapping[str, Any]
) -> str:
    """Append an initial reviewer registration and its immutable event snapshot."""

    principal_id = _text(
        registration.get("principal_id"), "reviewer_registration.principal_id"
    )
    row = connection.execute(
        "SELECT principal_kind FROM governance_principals WHERE id = ?", (principal_id,)
    ).fetchone()
    if row is None or row[0] != "human":
        raise GovernanceError("external reviewer must be a registered human principal")
    registrar_id = _text(
        registration.get("registered_by_principal_id", IDENTITY_REGISTRAR_ID),
        "reviewer_registration.registered_by_principal_id",
    )
    registered_at = _utc(
        registration.get("registered_at"), "reviewer_registration.registered_at"
    )
    valid_until = _utc(
        registration.get("valid_until"), "reviewer_registration.valid_until"
    )
    _chronological(
        (registered_at, "reviewer_registration.registered_at"),
        (valid_until, "reviewer_registration.valid_until"),
    )
    signature_scheme = _text(
        registration.get("signature_scheme"),
        "reviewer_registration.signature_scheme",
    )
    if signature_scheme != "minisign-ed25519":
        raise GovernanceError(
            "reviewer_registration.signature_scheme must be minisign-ed25519"
        )
    qualifications_summary = _text(
        registration.get("qualifications_summary"),
        "reviewer_registration.qualifications_summary",
    )
    qualifications_sha = _sha(
        registration.get("qualifications_sha256"),
        "reviewer_registration.qualifications_sha256",
    )
    evidence_sha = _sha(
        registration.get("registrar_evidence_sha256"),
        "reviewer_registration.registrar_evidence_sha256",
    )
    signature_key_id = _text(
        registration.get("signature_key_id"),
        "reviewer_registration.signature_key_id",
    )
    public_key_sha = _sha(
        registration.get("public_key_sha256"),
        "reviewer_registration.public_key_sha256",
    )
    status = _text(
        registration.get("status", "active"), "reviewer_registration.status"
    )
    if status != "active":
        raise GovernanceError(
            "an initial reviewer registration must be active; use a later revocation event"
        )
    registrar_assignment_id = _text(
        registration.get(
            "recorder_assignment_id",
            f"{IDENTITY_REGISTRAR_ID}:reviewer-role",
        ),
        "reviewer_registration.recorder_assignment_id",
    )
    event_id = _text(
        registration.get("event_id", f"{principal_id}:registry:1"),
        "reviewer_registration.event_id",
    )
    reason = _text(
        registration.get("reason", "Initial external reviewer registration."),
        "reviewer_registration.reason",
    )

    savepoint = "kapi_reviewer_registration"
    connection.execute(f"SAVEPOINT {savepoint}")
    try:
        _execute(
            connection,
            "INSERT INTO external_reviewer_registry VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            (
                principal_id,
                qualifications_summary,
                qualifications_sha,
                registered_at,
                valid_until,
                registrar_id,
                evidence_sha,
                signature_scheme,
                signature_key_id,
                public_key_sha,
                status,
            ),
        )
        _execute(
            connection,
            "INSERT INTO external_reviewer_registry_events VALUES("
            + ", ".join("?" for _ in range(18))
            + ")",
            (
                event_id,
                principal_id,
                1,
                None,
                "registration",
                "active",
                registered_at,
                valid_until,
                registered_at,
                registrar_id,
                registrar_assignment_id,
                evidence_sha,
                qualifications_summary,
                qualifications_sha,
                signature_scheme,
                signature_key_id,
                public_key_sha,
                reason,
            ),
        )
    except Exception:
        connection.execute(f"ROLLBACK TO {savepoint}")
        connection.execute(f"RELEASE {savepoint}")
        raise
    connection.execute(f"RELEASE {savepoint}")
    return principal_id


def _append_reviewer_registry_event(
    connection: sqlite3.Connection,
    event: Mapping[str, Any],
    *,
    event_type: str,
) -> str:
    principal_id = _text(event.get("principal_id"), "reviewer_event.principal_id")
    previous = connection.execute(
        "SELECT * FROM external_reviewer_registry_events "
        "WHERE principal_id = ? ORDER BY sequence DESC LIMIT 1",
        (principal_id,),
    ).fetchone()
    if previous is None:
        raise GovernanceError("reviewer_event principal has no initial registration")
    if previous["status"] != "active":
        raise GovernanceError("reviewer_event cannot follow a revoked registry state")

    sequence = int(previous["sequence"]) + 1
    supplied_sequence = event.get("sequence")
    if supplied_sequence is not None and (
        isinstance(supplied_sequence, bool) or supplied_sequence != sequence
    ):
        raise GovernanceError("reviewer_event.sequence must be the next append-only sequence")
    supplied_predecessor = event.get("supersedes_event_id")
    if supplied_predecessor is not None and _text(
        supplied_predecessor, "reviewer_event.supersedes_event_id"
    ) != previous["id"]:
        raise GovernanceError("reviewer_event.supersedes_event_id is not the latest event")

    effective_at = _utc(event.get("effective_at"), "reviewer_event.effective_at")
    recorded_at = _utc(event.get("recorded_at"), "reviewer_event.recorded_at")
    _chronological(
        (previous["recorded_at"], "previous registry recorded_at"),
        (effective_at, "reviewer_event.effective_at"),
        (recorded_at, "reviewer_event.recorded_at"),
    )
    recorder_id = _text(
        event.get("recorded_by_principal_id", IDENTITY_REGISTRAR_ID),
        "reviewer_event.recorded_by_principal_id",
    )
    recorder_assignment_id = _text(
        event.get(
            "recorder_assignment_id",
            f"{IDENTITY_REGISTRAR_ID}:reviewer-role",
        ),
        "reviewer_event.recorder_assignment_id",
    )

    if event_type == "revocation":
        status = "revoked"
        valid_until = previous["valid_until"]
        qualifications_summary = previous["qualifications_summary"]
        qualifications_sha = previous["qualifications_sha256"]
        signature_scheme = previous["signature_scheme"]
        signature_key_id = previous["signature_key_id"]
        public_key_sha = previous["public_key_sha256"]
    elif event_type == "supersession":
        status = "active"
        valid_until = _utc(
            event.get("valid_until", previous["valid_until"]),
            "reviewer_event.valid_until",
        )
        qualifications_summary = _text(
            event.get("qualifications_summary", previous["qualifications_summary"]),
            "reviewer_event.qualifications_summary",
        )
        qualifications_sha = _sha(
            event.get("qualifications_sha256", previous["qualifications_sha256"]),
            "reviewer_event.qualifications_sha256",
        )
        signature_scheme = _text(
            event.get("signature_scheme", previous["signature_scheme"]),
            "reviewer_event.signature_scheme",
        )
        if signature_scheme != "minisign-ed25519":
            raise GovernanceError("reviewer_event.signature_scheme must be minisign-ed25519")
        signature_key_id = _text(
            event.get("signature_key_id", previous["signature_key_id"]),
            "reviewer_event.signature_key_id",
        )
        public_key_sha = _sha(
            event.get("public_key_sha256", previous["public_key_sha256"]),
            "reviewer_event.public_key_sha256",
        )
    else:  # pragma: no cover - private helper contract
        raise GovernanceError("reviewer registry event type is invalid")

    if event_type == "supersession":
        _chronological(
            (effective_at, "reviewer_event.effective_at"),
            (valid_until, "reviewer_event.valid_until"),
        )
    event_id = _text(
        event.get("id", f"{principal_id}:registry:{sequence}"),
        "reviewer_event.id",
    )
    values = (
        event_id,
        principal_id,
        sequence,
        previous["id"],
        event_type,
        status,
        effective_at,
        valid_until,
        recorded_at,
        recorder_id,
        recorder_assignment_id,
        _sha(event.get("event_evidence_sha256"), "reviewer_event.event_evidence_sha256"),
        qualifications_summary,
        qualifications_sha,
        signature_scheme,
        signature_key_id,
        public_key_sha,
        _text(event.get("reason"), "reviewer_event.reason"),
    )
    _execute(
        connection,
        "INSERT INTO external_reviewer_registry_events VALUES("
        + ", ".join("?" for _ in values)
        + ")",
        values,
    )
    return event_id


def supersede_external_reviewer(
    connection: sqlite3.Connection, event: Mapping[str, Any]
) -> str:
    """Append a new active qualifications/key/validity snapshot."""

    return _append_reviewer_registry_event(connection, event, event_type="supersession")


def revoke_external_reviewer(
    connection: sqlite3.Connection, event: Mapping[str, Any]
) -> str:
    """Append an immutable revocation while retaining the superseded snapshot."""

    return _append_reviewer_registry_event(connection, event, event_type="revocation")


def bind_unreviewed_release(
    connection: sqlite3.Connection, binding: Mapping[str, Any]
) -> dict[str, Any]:
    """Bind a draft release without asserting that a human reviewed it."""

    release_id = _text(binding.get("release_id"), "binding.release_id")
    operator_id = _text(binding.get("operator_principal_id"), "binding.operator_principal_id")
    bound_at = _utc(binding.get("bound_at"), "binding.bound_at")
    disposition = _text(
        binding.get("calculation_disposition"), "binding.calculation_disposition"
    )
    if disposition not in {"eligible", "withheld", "incomplete"}:
        raise GovernanceError("binding.calculation_disposition is invalid")
    methodology_id = _text(binding.get("methodology_id"), "binding.methodology_id")
    methodology_version = _text(
        binding.get("methodology_version"), "binding.methodology_version"
    )
    gate = connection.execute(
        "SELECT methodology_sha256 FROM methodology_governance_gates "
        "WHERE methodology_id = ? AND methodology_version = ?",
        (methodology_id, methodology_version),
    ).fetchone()
    if gate is None:
        raise GovernanceError("binding methodology has no governance gate")
    artifact_sha = _sha(
        binding.get("artifact_manifest_sha256"), "binding.artifact_manifest_sha256"
    )
    operator_assignment_id = _text(
        binding.get("operator_assignment_id"), "binding.operator_assignment_id"
    )
    _execute(
        connection,
        "INSERT INTO release_governance_bindings VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        (
            release_id,
            POLICY_ID,
            POLICY_VERSION,
            operator_id,
            operator_assignment_id,
            _text(
                binding.get("methodology_owner_principal_id"),
                "binding.methodology_owner_principal_id",
            ),
            _text(
                binding.get("methodology_owner_assignment_id"),
                "binding.methodology_owner_assignment_id",
            ),
            methodology_id,
            methodology_version,
            gate[0],
            _commit(binding.get("code_commit"), "binding.code_commit"),
            artifact_sha,
            disposition,
            bound_at,
        ),
    )
    _execute(
        connection,
        "INSERT INTO governance_transition_events("
        "id, release_id, sequence, from_governance_state, from_publication_state, "
        "governance_state, publication_state, calculation_disposition, review_label, "
        "actor_principal_id, actor_assignment_id, artifact_manifest_sha256, "
        "methodology_review_record_id, release_review_record_id, transitioned_at, reason"
        ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        (
            f"{release_id}:governance:1",
            release_id,
            1,
            "draft",
            "not_authorized",
            "unreviewed",
            "not_authorized",
            disposition,
            CURRENT_UNREVIEWED_LABEL,
            operator_id,
            operator_assignment_id,
            artifact_sha,
            None,
            None,
            bound_at,
            "Automated calculation recorded; operator review remains pending.",
        ),
    )
    return governance_status(connection, release_id)


def _disclosure(
    review: Mapping[str, Any], field: str, allowed_statuses: set[str]
) -> tuple[str, str, str, str]:
    value = review.get(field)
    if not isinstance(value, Mapping):
        raise GovernanceError(f"review.{field} must be an object")
    status = _text(value.get("status"), f"review.{field}.status")
    if status not in allowed_statuses:
        raise GovernanceError(
            f"review.{field}.status must be one of {sorted(allowed_statuses)}"
        )
    record = {
        "declared_at": _utc(value.get("declared_at"), f"review.{field}.declared_at"),
        "statement": _text(value.get("statement"), f"review.{field}.statement"),
        "status": status,
    }
    encoded = _canonical_json(record)
    return status, encoded, _sha_text(encoded), record["declared_at"]


def record_external_review(
    connection: sqlite3.Connection, review: Mapping[str, Any]
) -> str:
    """Append a signed methodology- or exact-release-review decision."""

    review_id = _text(review.get("id"), "review.id")
    review_kind = _text(review.get("review_kind"), "review.review_kind")
    expected_scope = {
        "methodology": REQUIRED_METHODOLOGY_REVIEW_SCOPE,
        "release": REQUIRED_RELEASE_REVIEW_SCOPE,
    }.get(review_kind)
    if expected_scope is None:
        raise GovernanceError("review.review_kind must be methodology or release")
    scope = review.get("scope")
    if not isinstance(scope, Sequence) or isinstance(scope, (str, bytes)):
        raise GovernanceError("review.scope must be an exact ordered array")
    if tuple(scope) != expected_scope:
        raise GovernanceError(f"review.scope must equal {list(expected_scope)!r}")
    scope_json = _canonical_json(list(expected_scope))
    scope_sha = _sha_text(scope_json)
    supplied_scope_sha = review.get("scope_sha256")
    if supplied_scope_sha is not None and _sha(
        supplied_scope_sha, "review.scope_sha256"
    ) != scope_sha:
        raise GovernanceError("review.scope_sha256 does not match review.scope")

    conflict_status, conflict_json, conflict_sha, conflict_at = _disclosure(
        review, "conflict_declaration", {"clear"}
    )
    relationship_status, relationship_json, relationship_sha, relationship_at = _disclosure(
        review, "relationship_disclosure", {"none"}
    )
    compensation_status, compensation_json, compensation_sha, compensation_at = _disclosure(
        review, "compensation_disclosure", {"none", "fixed_review_fee"}
    )
    disclosures_at = max(relationship_at, compensation_at)

    findings = review.get("findings")
    if not isinstance(findings, (list, Mapping)):
        raise GovernanceError("review.findings must be an array or object")
    findings_json = _canonical_json(findings)
    unresolved = review.get("unresolved_issues")
    if not isinstance(unresolved, list):
        raise GovernanceError("review.unresolved_issues must be an array")
    unresolved_json = _canonical_json(unresolved)
    outcome = _text(review.get("outcome"), "review.outcome")
    if outcome not in {"approved", "rejected"}:
        raise GovernanceError("review.outcome must be approved or rejected")
    if outcome == "approved" and unresolved:
        raise GovernanceError("an approved review cannot have unresolved issues")

    reviewer_id = _text(review.get("reviewer_principal_id"), "review.reviewer_principal_id")
    reviewer = connection.execute(
        "SELECT full_name, affiliation FROM governance_principals WHERE id = ?",
        (reviewer_id,),
    ).fetchone()
    if reviewer is None:
        raise GovernanceError("reviewer principal is not registered")
    reviewed_at = _utc(review.get("reviewed_at"), "review.reviewed_at")
    valid_until = _utc(review.get("valid_until"), "review.valid_until")
    recorded_at = _utc(review.get("recorded_at"), "review.recorded_at")
    _chronological((conflict_at, "review.conflict_declaration.declared_at"), (reviewed_at, "review.reviewed_at"))
    _chronological((disclosures_at, "review.disclosures_declared_at"), (reviewed_at, "review.reviewed_at"))
    _chronological((reviewed_at, "review.reviewed_at"), (recorded_at, "review.recorded_at"))
    _chronological((reviewed_at, "review.reviewed_at"), (valid_until, "review.valid_until"))

    release_id_value = review.get("release_id")
    release_id = (
        _text(release_id_value, "review.release_id")
        if release_id_value is not None
        else None
    )
    if (review_kind == "release") != (release_id is not None):
        raise GovernanceError("release reviews require release_id; methodology reviews forbid it")
    methodology_id = _text(review.get("methodology_id"), "review.methodology_id")
    methodology_version = _text(
        review.get("methodology_version"), "review.methodology_version"
    )
    methodology_sha = _sha(
        review.get("methodology_sha256"), "review.methodology_sha256"
    )
    artifact_sha = _sha(
        review.get("review_artifact_manifest_sha256", review.get("artifact_manifest_sha256")),
        "review.review_artifact_manifest_sha256",
    )
    reviewer_appointment_id = _text(
        review.get("reviewer_appointment_id"), "review.reviewer_appointment_id"
    )
    methodology_owner_id = _text(
        review.get("methodology_owner_principal_id"),
        "review.methodology_owner_principal_id",
    )
    code_commit = _commit(review.get("code_commit"), "review.code_commit")
    report_sha = _sha(review.get("report_sha256"), "review.report_sha256")
    findings_sha = _sha_text(findings_json)
    unresolved_sha = _sha_text(unresolved_json)
    evidence_record_id = _text(
        review.get("evidence_record_id"), "review.evidence_record_id"
    )
    signature_scheme = _text(review.get("signature_scheme"), "review.signature_scheme")
    if signature_scheme != "minisign-ed25519":
        raise GovernanceError("review.signature_scheme must be minisign-ed25519")
    signature_key_id = _text(
        review.get("signature_key_id"), "review.signature_key_id"
    )
    registry_event = connection.execute(
        "SELECT * FROM external_reviewer_registry_events "
        "WHERE principal_id = ? AND effective_at <= ? AND recorded_at <= ? "
        "ORDER BY sequence DESC LIMIT 1",
        (reviewer_id, reviewed_at, reviewed_at),
    ).fetchone()
    if (
        registry_event is None
        or registry_event["status"] != "active"
        or registry_event["valid_until"] < reviewed_at
        or registry_event["signature_scheme"] != signature_scheme
        or registry_event["signature_key_id"] != signature_key_id
    ):
        raise GovernanceError(
            "reviewer registry event is not the latest active credential for reviewed_at"
        )
    supplied_registry_event_id = review.get("reviewer_registry_event_id")
    if supplied_registry_event_id is not None and _text(
        supplied_registry_event_id, "review.reviewer_registry_event_id"
    ) != registry_event["id"]:
        raise GovernanceError(
            "review.reviewer_registry_event_id is not the latest active credential"
        )
    signature_evidence_sha = _sha(
        review.get("signature_evidence_sha256"),
        "review.signature_evidence_sha256",
    )
    signed_payload = {
        "code_commit": code_commit,
        "compensation_disclosure_sha256": compensation_sha,
        "conflict_declaration_sha256": conflict_sha,
        "evidence_record_id": evidence_record_id,
        "findings_sha256": findings_sha,
        "id": review_id,
        "methodology_id": methodology_id,
        "methodology_owner_principal_id": methodology_owner_id,
        "methodology_sha256": methodology_sha,
        "methodology_version": methodology_version,
        "outcome": outcome,
        "relationship_disclosure_sha256": relationship_sha,
        "release_id": release_id,
        "report_sha256": report_sha,
        "review_artifact_manifest_sha256": artifact_sha,
        "review_kind": review_kind,
        "reviewed_at": reviewed_at,
        "reviewer_affiliation": reviewer[1],
        "reviewer_appointment_id": reviewer_appointment_id,
        "reviewer_full_name": reviewer[0],
        "reviewer_principal_id": reviewer_id,
        "reviewer_registry_event_id": registry_event["id"],
        "scope_sha256": scope_sha,
        "unresolved_issues_sha256": unresolved_sha,
        "valid_until": valid_until,
    }
    signed_payload_sha = _sha_text(_canonical_json(signed_payload))
    supplied_payload_sha = review.get("signed_payload_sha256")
    if supplied_payload_sha is not None and _sha(
        supplied_payload_sha, "review.signed_payload_sha256"
    ) != signed_payload_sha:
        raise GovernanceError(
            "review.signed_payload_sha256 does not match the canonical review payload"
        )

    values = (
        review_id,
        review_kind,
        release_id,
        reviewer_id,
        reviewer[0],
        reviewer[1],
        reviewer_appointment_id,
        registry_event["id"],
        methodology_owner_id,
        scope_json,
        scope_sha,
        conflict_status,
        conflict_json,
        conflict_sha,
        conflict_at,
        relationship_status,
        relationship_json,
        relationship_sha,
        compensation_status,
        compensation_json,
        compensation_sha,
        disclosures_at,
        methodology_id,
        methodology_version,
        methodology_sha,
        code_commit,
        artifact_sha,
        outcome,
        report_sha,
        findings_json,
        findings_sha,
        unresolved_json,
        unresolved_sha,
        evidence_record_id,
        signature_scheme,
        signature_key_id,
        signature_evidence_sha,
        signed_payload_sha,
        reviewed_at,
        valid_until,
        recorded_at,
    )
    _execute(
        connection,
        "INSERT INTO external_review_records VALUES("
        + ", ".join("?" for _ in values)
        + ")",
        values,
    )
    return review_id


def record_signature_verification_claim(
    connection: sqlite3.Connection, attestation: Mapping[str, Any]
) -> str:
    """Record an untrusted local claim about detached-signature verification.

    The local prototype does not implement cryptographic verification. This
    record is retained only to exercise forward schema binding; it cannot
    authorize a transition. A later migration must add a trusted verifier
    adapter and a distinct verified attestation type.
    """

    attestation_id = _text(attestation.get("id"), "attestation.id")
    values = (
        attestation_id,
        _text(attestation.get("review_record_id"), "attestation.review_record_id"),
        _text(
            attestation.get("verifier_principal_id"),
            "attestation.verifier_principal_id",
        ),
        _text(
            attestation.get("verifier_assignment_id"),
            "attestation.verifier_assignment_id",
        ),
        _text(attestation.get("signature_scheme"), "attestation.signature_scheme"),
        _text(attestation.get("signature_key_id"), "attestation.signature_key_id"),
        _sha(
            attestation.get("signed_payload_sha256"),
            "attestation.signed_payload_sha256",
        ),
        _sha(
            attestation.get("signature_evidence_sha256"),
            "attestation.signature_evidence_sha256",
        ),
        _sha(
            attestation.get("verification_evidence_sha256"),
            "attestation.verification_evidence_sha256",
        ),
        _utc(attestation.get("verified_at"), "attestation.verified_at"),
        _text(
            attestation.get("status", "untrusted_local_claim"),
            "attestation.status",
        ),
    )
    if values[4] != "minisign-ed25519":
        raise GovernanceError("attestation.signature_scheme must be minisign-ed25519")
    _execute(
        connection,
        "INSERT INTO signature_verification_attestations VALUES("
        + ", ".join("?" for _ in values)
        + ")",
        values,
    )
    return attestation_id


def append_governance_transition(
    connection: sqlite3.Connection, transition: Mapping[str, Any]
) -> dict[str, Any]:
    """Append an authorized transition while deriving every claim-bearing field."""

    release_id = _text(transition.get("release_id"), "transition.release_id")
    action = _text(transition.get("action"), "transition.action")
    actor_id = _text(transition.get("actor_principal_id"), "transition.actor_principal_id")
    actor_assignment_id = _text(
        transition.get("actor_assignment_id"), "transition.actor_assignment_id"
    )
    transitioned_at = _utc(transition.get("transitioned_at"), "transition.transitioned_at")
    reason = _text(transition.get("reason"), "transition.reason")
    binding = connection.execute(
        "SELECT * FROM release_governance_bindings WHERE release_id = ?", (release_id,)
    ).fetchone()
    latest = connection.execute(
        "SELECT * FROM governance_transition_events WHERE release_id = ? "
        "ORDER BY sequence DESC LIMIT 1",
        (release_id,),
    ).fetchone()
    if binding is None or latest is None:
        raise GovernanceError("transition release has no initial governance binding")

    method_review_id = transition.get("methodology_review_record_id")
    release_review_id = transition.get("release_review_record_id")
    # Compatibility input name is accepted only for the exact-release edge.
    if release_review_id is None and action in {"accept_external_review", "accept_external_release_review"}:
        release_review_id = transition.get("review_record_id")
    if method_review_id is not None:
        method_review_id = _text(method_review_id, "transition.methodology_review_record_id")
    if release_review_id is not None:
        release_review_id = _text(release_review_id, "transition.release_review_record_id")

    if action in {"accept_external_review", "accept_external_release_review"}:
        if release_review_id is None:
            raise GovernanceError("accept_external_release_review requires release_review_record_id")
        governance_state = "external_release_reviewed"
        publication_state = "not_authorized"
        review_label = EXTERNAL_RELEASE_REVIEW_LABEL
        method_review_id = latest["methodology_review_record_id"]
    elif action == "authorize_publication":
        if method_review_id is None:
            method_review_id = latest["methodology_review_record_id"]
        if method_review_id is None:
            raise GovernanceError("authorize_publication requires methodology_review_record_id")
        governance_state = latest["governance_state"]
        publication_state = "ready"
        if governance_state == "operator_reviewed":
            release_review_id = None
            review_label = METHODOLOGY_REVIEWED_OPERATOR_LABEL
        elif governance_state == "external_release_reviewed":
            if release_review_id is None:
                release_review_id = latest["release_review_record_id"]
            review_label = EXTERNAL_RELEASE_REVIEW_LABEL
        else:
            raise GovernanceError("current governance state cannot be publication-authorized")
    elif action == "mark_published":
        raise GovernanceError(
            "policy v1.0.0 does not implement a published transition; "
            "a new reviewed policy/schema vintage is required"
        )
    elif action == "withdraw":
        governance_state = "withdrawn"
        publication_state = "withdrawn"
        method_review_id = latest["methodology_review_record_id"]
        release_review_id = latest["release_review_record_id"]
        review_label = "withdrawn"
    else:
        raise GovernanceError("transition.action is not a governed action")

    _execute(
        connection,
        "INSERT INTO governance_transition_events("
        "id, release_id, sequence, from_governance_state, from_publication_state, "
        "governance_state, publication_state, calculation_disposition, review_label, "
        "actor_principal_id, actor_assignment_id, artifact_manifest_sha256, "
        "methodology_review_record_id, release_review_record_id, transitioned_at, reason"
        ") VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        (
            _text(transition.get("id"), "transition.id"),
            release_id,
            latest["sequence"] + 1,
            latest["governance_state"],
            latest["publication_state"],
            governance_state,
            publication_state,
            binding["calculation_disposition"],
            review_label,
            actor_id,
            actor_assignment_id,
            binding["artifact_manifest_sha256"],
            method_review_id,
            release_review_id,
            transitioned_at,
            reason,
        ),
    )
    return governance_status(connection, release_id)


def governance_status(
    connection: sqlite3.Connection, release_id: str
) -> dict[str, Any]:
    """Return the latest separated governance axes for a release."""

    row = connection.execute(
        "SELECT * FROM release_governance_status WHERE release_id = ?",
        (_text(release_id, "release_id"),),
    ).fetchone()
    if row is None:
        raise GovernanceError("release has no governance status")
    return {key: row[key] for key in row.keys()}


def _required_governance_row(
    connection: sqlite3.Connection,
    sql: str,
    values: tuple[Any, ...],
    label: str,
) -> sqlite3.Row:
    row = connection.execute(sql, values).fetchone()
    if row is None:
        raise GovernanceError(f"review attribution is missing {label}")
    return row


def _principal_attribution(
    connection: sqlite3.Connection, principal_id: str
) -> dict[str, Any]:
    principal = _required_governance_row(
        connection,
        "SELECT * FROM governance_principals WHERE id = ?",
        (principal_id,),
        f"principal {principal_id}",
    )
    registrar = connection.execute(
        "SELECT id, identity_key, full_name, affiliation, identity_evidence_sha256 "
        "FROM governance_principals WHERE id = ?",
        (principal["registered_by_principal_id"],),
    ).fetchone()
    if registrar is None and principal["registered_by_principal_id"] != "system-bootstrap":
        raise GovernanceError(
            "review attribution is missing a non-bootstrap identity registrar"
        )
    registrar_attribution = (
        {
            "principal_id": registrar["id"],
            "identity_key": registrar["identity_key"],
            "full_name": registrar["full_name"],
            "affiliation": registrar["affiliation"],
            "identity_evidence_sha256": registrar["identity_evidence_sha256"],
            "bootstrap_anchor": False,
        }
        if registrar is not None
        else {
            "principal_id": principal["registered_by_principal_id"],
            "identity_key": None,
            "full_name": None,
            "affiliation": None,
            "identity_evidence_sha256": None,
            "bootstrap_anchor": True,
        }
    )
    return {
        "principal_id": principal["id"],
        "identity_key": principal["identity_key"],
        "full_name": principal["full_name"],
        "affiliation": principal["affiliation"],
        "principal_kind": principal["principal_kind"],
        "identity_evidence_sha256": principal["identity_evidence_sha256"],
        "registered_at": principal["registered_at"],
        "registered_by": registrar_attribution,
    }


def _assignment_attribution(
    connection: sqlite3.Connection, assignment_id: str
) -> dict[str, Any]:
    assignment = _required_governance_row(
        connection,
        "SELECT * FROM governance_role_assignments WHERE id = ?",
        (assignment_id,),
        f"role assignment {assignment_id}",
    )
    return {
        "assignment_id": assignment["id"],
        "principal_id": assignment["principal_id"],
        "role": assignment["role"],
        "appointed_by": _principal_attribution(
            connection, assignment["appointed_by_principal_id"]
        ),
        "appointed_at": assignment["appointed_at"],
        "valid_from": assignment["valid_from"],
        "valid_until": assignment["valid_until"],
        "appointment_sha256": assignment["appointment_sha256"],
    }


def _registry_event_attribution(
    connection: sqlite3.Connection, event: sqlite3.Row
) -> dict[str, Any]:
    return {
        "event_id": event["id"],
        "principal_id": event["principal_id"],
        "sequence": event["sequence"],
        "supersedes_event_id": event["supersedes_event_id"],
        "event_type": event["event_type"],
        "status": event["status"],
        "effective_at": event["effective_at"],
        "valid_until": event["valid_until"],
        "recorded_at": event["recorded_at"],
        "recorded_by": _principal_attribution(
            connection, event["recorded_by_principal_id"]
        ),
        "recorder_assignment": _assignment_attribution(
            connection, event["recorder_assignment_id"]
        ),
        "event_evidence_sha256": event["event_evidence_sha256"],
        "qualifications_summary": event["qualifications_summary"],
        "qualifications_sha256": event["qualifications_sha256"],
        "signature_scheme": event["signature_scheme"],
        "signature_key_id": event["signature_key_id"],
        "public_key_sha256": event["public_key_sha256"],
        "reason": event["reason"],
    }


def governance_review_attribution(
    connection: sqlite3.Connection, review_id: str
) -> dict[str, Any]:
    """Return every evidence field needed to audit one named review record."""

    review_id = _text(review_id, "review_id")
    review = _required_governance_row(
        connection,
        "SELECT * FROM external_review_records WHERE id = ?",
        (review_id,),
        f"review record {review_id}",
    )
    verification = _required_governance_row(
        connection,
        "SELECT * FROM signature_verification_attestations WHERE review_record_id = ?",
        (review_id,),
        f"signature-verification attestation for {review_id}",
    )
    bound_registry = _required_governance_row(
        connection,
        "SELECT * FROM external_reviewer_registry_events WHERE id = ?",
        (review["reviewer_registry_event_id"],),
        f"reviewer registry event {review['reviewer_registry_event_id']}",
    )
    latest_registry = _required_governance_row(
        connection,
        "SELECT * FROM external_reviewer_registry_events WHERE principal_id = ? "
        "ORDER BY sequence DESC LIMIT 1",
        (review["reviewer_principal_id"],),
        f"latest reviewer registry event for {review['reviewer_principal_id']}",
    )

    try:
        scope = json.loads(review["scope_json"])
        conflict = json.loads(review["conflict_declaration_json"])
        relationship = json.loads(review["relationship_disclosure_json"])
        compensation = json.loads(review["compensation_disclosure_json"])
        findings = json.loads(review["findings_json"])
        unresolved = json.loads(review["unresolved_issues_json"])
    except (TypeError, json.JSONDecodeError) as error:
        raise GovernanceError(
            f"review attribution contains malformed canonical JSON: {error}"
        ) from error

    reviewer = _principal_attribution(connection, review["reviewer_principal_id"])
    reviewer["review_record_identity_snapshot"] = {
        "full_name": review["reviewer_full_name"],
        "affiliation": review["reviewer_affiliation"],
    }
    reviewer["appointment"] = _assignment_attribution(
        connection, review["reviewer_appointment_id"]
    )
    bound_registry_output = _registry_event_attribution(connection, bound_registry)
    latest_registry_output = _registry_event_attribution(connection, latest_registry)
    reviewer["registry"] = {
        "bound_event": bound_registry_output,
        "latest_event": latest_registry_output,
        "bound_event_is_latest": bound_registry["id"] == latest_registry["id"],
        "revocation": (
            latest_registry_output if latest_registry["status"] == "revoked" else None
        ),
    }

    verifier = _principal_attribution(connection, verification["verifier_principal_id"])
    verifier["appointment"] = _assignment_attribution(
        connection, verification["verifier_assignment_id"]
    )

    return {
        "review_record_id": review["id"],
        "review_kind": review["review_kind"],
        "release_id": review["release_id"],
        "reviewer": reviewer,
        "methodology_owner": _principal_attribution(
            connection, review["methodology_owner_principal_id"]
        ),
        "scope": {
            "items": scope,
            "sha256": review["scope_sha256"],
        },
        "disclosures": {
            "conflict": {
                "status": review["conflict_status"],
                "declaration": conflict,
                "sha256": review["conflict_declaration_sha256"],
                "declared_at": review["conflict_declared_at"],
            },
            "relationship": {
                "status": review["relationship_status"],
                "disclosure": relationship,
                "sha256": review["relationship_disclosure_sha256"],
            },
            "compensation": {
                "status": review["compensation_status"],
                "disclosure": compensation,
                "sha256": review["compensation_disclosure_sha256"],
            },
            "declared_at": review["disclosures_declared_at"],
        },
        "review_subject": {
            "methodology_id": review["methodology_id"],
            "methodology_version": review["methodology_version"],
            "methodology_sha256": review["methodology_sha256"],
            "code_commit": review["code_commit"],
            "review_artifact_manifest_sha256": review[
                "review_artifact_manifest_sha256"
            ],
        },
        "decision": {
            "outcome": review["outcome"],
            "report_sha256": review["report_sha256"],
            "findings": findings,
            "findings_sha256": review["findings_sha256"],
            "unresolved_issues": unresolved,
            "unresolved_issues_sha256": review["unresolved_issues_sha256"],
            "evidence_record_id": review["evidence_record_id"],
        },
        "signature": {
            "scheme": review["signature_scheme"],
            "key_id": review["signature_key_id"],
            "signature_evidence_sha256": review["signature_evidence_sha256"],
            "signed_payload_sha256": review["signed_payload_sha256"],
        },
        "reviewed_at": review["reviewed_at"],
        "valid_until": review["valid_until"],
        "recorded_at": review["recorded_at"],
        "signature_verification_attestation": {
            "attestation_id": verification["id"],
            "review_record_id": verification["review_record_id"],
            "status": verification["status"],
            "signature_scheme": verification["signature_scheme"],
            "signature_key_id": verification["signature_key_id"],
            "signed_payload_sha256": verification["signed_payload_sha256"],
            "signature_evidence_sha256": verification[
                "signature_evidence_sha256"
            ],
            "verification_evidence_sha256": verification[
                "verification_evidence_sha256"
            ],
            "verified_at": verification["verified_at"],
            "verifier": verifier,
        },
    }


def governance_public_attribution(
    connection: sqlite3.Connection, release_id: str
) -> dict[str, Any]:
    """Build complete audit attribution for reviews bound to the current status."""

    status = governance_status(connection, release_id)
    result: dict[str, Any] = {}
    for output_key, status_key in (
        ("methodology_review", "methodology_review_record_id"),
        ("release_review", "release_review_record_id"),
    ):
        review_id = status.get(status_key)
        if review_id is not None:
            result[output_key] = governance_review_attribution(connection, review_id)
    return result
