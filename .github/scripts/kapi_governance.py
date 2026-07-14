#!/usr/bin/env python3
"""Pure, dependency-free KAPI governance policy and workflow checks.

The privileged workflow imports this module from an explicitly checked-out base
SHA. Pull-request content is metadata only in that workflow and is never
executed. The same decision functions are exercised by local unit tests.
"""

from __future__ import annotations

import argparse
import dataclasses
import datetime as dt
import hashlib
import json
import pathlib
import re
import secrets
import sys
from typing import Any, Iterable, Sequence


AUTH_MARKER = "kapi-ready-authorization-v1"
AUDIT_MARKER = "kapi-ready-audit-v1"
AUTH_SCHEMA = "kapi-ready-authorization/v1"
AUDIT_SCHEMA = "kapi-ready-audit/v1"
POLICY_SCHEMA = "kapi-governance-policy/v1"
REQUIRED_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/kapi-governance-actions-advisory-v1.yml"
)
LEGACY_BOOTSTRAP_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/kapi.yml"
)
GUARD_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/ready-for-review-guard.yml"
)
LEGACY_BOOTSTRAP_CHECK_NAME = "verify"
PR4_BOOTSTRAP_BASE_SHA = "6070f10c9ab5611c0966056a43eb24ae6beda7ce"
PROTECTED_GOVERNANCE_PATHS = (
    pathlib.PurePosixPath(".github/scripts/kapi_governance.py"),
    pathlib.PurePosixPath(".github/scripts/kapi_ready_guard.py"),
    pathlib.PurePosixPath(".github/scripts/kapi_ready_reconcile.py"),
    pathlib.PurePosixPath(".github/tests/test_kapi_governance.py"),
    LEGACY_BOOTSTRAP_WORKFLOW,
    REQUIRED_WORKFLOW,
    GUARD_WORKFLOW,
)
SHA_RE = re.compile(r"^[0-9a-f]{40}$")
NONCE_RE = re.compile(r"^[0-9a-f]{32}$")
UTC = dt.timezone.utc


class GovernanceError(ValueError):
    """Raised for malformed policy or evidence."""


@dataclasses.dataclass(frozen=True)
class Policy:
    repository_id: int
    default_branch: str
    activation_state: str
    allowed_ready_actor_ids: frozenset[int]
    human_authorizer_actor_ids: frozenset[int]
    automation_actor_ids: frozenset[int]
    credential_separation_attested: bool
    dedicated_verifier_attested: bool
    dedicated_verifier_integration_id: int | None
    audit_actor_id: int
    audit_actor_login: str
    authorization_ttl_seconds: int
    clock_skew_seconds: int
    actions_advisory_check_name: str
    production_required_check_name: str

    @classmethod
    def load(cls, path: pathlib.Path | str) -> "Policy":
        raw = json.loads(pathlib.Path(path).read_text(encoding="utf-8"))
        expected = {
            "actions_advisory_check_name",
            "activation_state",
            "allowed_ready_actor_ids",
            "audit_actor_id",
            "audit_actor_login",
            "automation_actor_ids",
            "authorization_ttl_seconds",
            "clock_skew_seconds",
            "credential_separation_attested",
            "dedicated_verifier_attested",
            "dedicated_verifier_integration_id",
            "default_branch",
            "human_authorizer_actor_ids",
            "repository_id",
            "production_required_check_name",
            "schema",
        }
        if set(raw) != expected:
            raise GovernanceError("policy keys do not match the v1 schema")
        if raw["schema"] != POLICY_SCHEMA:
            raise GovernanceError("unsupported policy schema")
        ready = raw["allowed_ready_actor_ids"]
        authorizers = raw["human_authorizer_actor_ids"]
        automation = raw["automation_actor_ids"]
        if not isinstance(ready, list) or not ready:
            raise GovernanceError("at least one stable ready actor id is required")
        for label, values in {
            "ready": ready,
            "human authorizer": authorizers,
            "automation": automation,
        }.items():
            if not isinstance(values, list) or any(
                not isinstance(value, int) or value <= 0 for value in values
            ):
                raise GovernanceError(f"{label} actor ids must be positive integers")
        activation_state = str(raw["activation_state"])
        if activation_state not in {
            "active",
            "blocked_pending_external_verifier_and_human_authorizer",
        }:
            raise GovernanceError("unsupported governance activation state")
        separation = raw["credential_separation_attested"]
        if not isinstance(separation, bool):
            raise GovernanceError("credential separation attestation must be boolean")
        verifier_attested = raw["dedicated_verifier_attested"]
        verifier_id = raw["dedicated_verifier_integration_id"]
        if not isinstance(verifier_attested, bool):
            raise GovernanceError("dedicated verifier attestation must be boolean")
        if verifier_id is not None and (
            not isinstance(verifier_id, int) or verifier_id <= 0
        ):
            raise GovernanceError("dedicated verifier integration id must be positive")
        overlap = set(authorizers) & (set(ready) | set(automation))
        if overlap:
            raise GovernanceError(
                "human authorizer ids must not overlap ready or automation identities"
            )
        if activation_state == "active":
            if not authorizers or not separation:
                raise GovernanceError(
                    "active policy requires a separate human authorizer and credential attestation"
                )
            if not verifier_attested or verifier_id is None or verifier_id == 15368:
                raise GovernanceError(
                    "active policy requires an attested verifier integration distinct from GitHub Actions"
                )
        ttl = raw["authorization_ttl_seconds"]
        if not isinstance(ttl, int) or ttl < 30 or ttl > 600:
            raise GovernanceError("authorization TTL must be between 30 and 600 seconds")
        skew = raw["clock_skew_seconds"]
        if not isinstance(skew, int) or skew < 0 or skew > 30:
            raise GovernanceError("clock skew must be between 0 and 30 seconds")
        advisory_name = raw["actions_advisory_check_name"]
        production_name = raw["production_required_check_name"]
        for label, check_name in {
            "Actions advisory": advisory_name,
            "production required": production_name,
        }.items():
            if not isinstance(check_name, str) or not re.fullmatch(
                r"[a-z0-9][a-z0-9-]{7,80}", check_name
            ):
                raise GovernanceError(f"{label} check name must be unique and versioned")
        if advisory_name == production_name:
            raise GovernanceError("Actions advisory and production check names must differ")
        return cls(
            repository_id=int(raw["repository_id"]),
            default_branch=str(raw["default_branch"]),
            activation_state=activation_state,
            allowed_ready_actor_ids=frozenset(ready),
            human_authorizer_actor_ids=frozenset(authorizers),
            automation_actor_ids=frozenset(automation),
            credential_separation_attested=separation,
            dedicated_verifier_attested=verifier_attested,
            dedicated_verifier_integration_id=verifier_id,
            audit_actor_id=int(raw["audit_actor_id"]),
            audit_actor_login=str(raw["audit_actor_login"]),
            authorization_ttl_seconds=ttl,
            clock_skew_seconds=skew,
            actions_advisory_check_name=advisory_name,
            production_required_check_name=production_name,
        )


@dataclasses.dataclass(frozen=True)
class Comment:
    id: int
    author_id: int
    author_login: str
    author_type: str
    body: str
    created_at: dt.datetime
    updated_at: dt.datetime

    @classmethod
    def from_api(cls, raw: dict[str, Any]) -> "Comment":
        return cls(
            id=int(raw["id"]),
            author_id=int(raw["user"]["id"]),
            author_login=str(raw["user"]["login"]),
            author_type=str(raw["user"]["type"]),
            body=str(raw.get("body") or ""),
            created_at=parse_time(raw["created_at"]),
            updated_at=parse_time(raw["updated_at"]),
        )


@dataclasses.dataclass(frozen=True)
class PullRequestEvent:
    repository_id: int
    pr_number: int
    pr_node_id: str
    head_sha: str
    head_repository_id: int
    actor_id: int
    actor_login: str
    event_time: dt.datetime
    run_id: int
    run_attempt: int

    @property
    def fingerprint(self) -> str:
        return digest_payload(
            {
                "action": "ready_for_review",
                "actor_id": self.actor_id,
                "event_time": format_time(self.event_time),
                "head_sha": self.head_sha,
                "pr_number": self.pr_number,
                "repository_id": self.repository_id,
            }
        )


@dataclasses.dataclass(frozen=True)
class PullRequestSnapshot:
    head_sha: str
    head_repository_id: int
    base_repository_id: int
    base_ref: str
    state: str
    is_draft: bool

    @classmethod
    def from_api(cls, raw: dict[str, Any]) -> "PullRequestSnapshot":
        head_repo = raw["head"].get("repo") or {}
        return cls(
            head_sha=str(raw["head"]["sha"]),
            head_repository_id=int(head_repo.get("id") or 0),
            base_repository_id=int(raw["base"]["repo"]["id"]),
            base_ref=str(raw["base"]["ref"]),
            state=str(raw["state"]),
            is_draft=bool(raw["draft"]),
        )


@dataclasses.dataclass(frozen=True)
class AuthorizationEvidence:
    comment_id: int
    repository_id: int
    pr_number: int
    head_sha: str
    authorizer_actor_id: int
    ready_actor_id: int
    expires_at: dt.datetime
    nonce: str
    payload_hash: str


@dataclasses.dataclass(frozen=True)
class AuditRecord:
    action: str
    repository_id: int
    pr_number: int
    head_sha: str
    actor_id: int
    authorization_comment_id: int | None
    authorization_hash: str | None
    nonce: str | None
    event_fingerprint: str
    reason: str


@dataclasses.dataclass(frozen=True)
class Decision:
    action: str
    reason: str
    event_fingerprint: str
    evidence: AuthorizationEvidence | None = None


def parse_time(value: str | dt.datetime) -> dt.datetime:
    if isinstance(value, dt.datetime):
        parsed = value
    else:
        normalized = str(value).strip()
        if normalized.endswith("Z"):
            normalized = normalized[:-1] + "+00:00"
        parsed = dt.datetime.fromisoformat(normalized)
    if parsed.tzinfo is None:
        raise GovernanceError("timestamps must include a timezone")
    return parsed.astimezone(UTC)


def format_time(value: dt.datetime) -> str:
    return parse_time(value).isoformat(timespec="seconds").replace("+00:00", "Z")


def canonical_json(payload: dict[str, Any]) -> str:
    return json.dumps(payload, sort_keys=True, separators=(",", ":"))


def digest_payload(payload: dict[str, Any]) -> str:
    return hashlib.sha256(canonical_json(payload).encode("utf-8")).hexdigest()


def make_marker(marker: str, payload: dict[str, Any]) -> str:
    return f"<!-- {marker} {canonical_json(payload)} -->"


def extract_marker_payloads(body: str, marker: str) -> list[dict[str, Any]]:
    prefix = f"<!-- {marker} "
    payloads: list[dict[str, Any]] = []
    for raw_line in body.splitlines():
        line = raw_line.strip()
        if not line.startswith(prefix) or not line.endswith(" -->"):
            continue
        serialized = line[len(prefix) : -4]
        try:
            payload = json.loads(serialized)
        except json.JSONDecodeError as exc:
            raise GovernanceError(f"malformed {marker} JSON") from exc
        if not isinstance(payload, dict):
            raise GovernanceError(f"{marker} payload must be an object")
        payloads.append(payload)
    return payloads


def parse_authorization(comment: Comment) -> AuthorizationEvidence | None:
    payloads = extract_marker_payloads(comment.body, AUTH_MARKER)
    if not payloads:
        return None
    if len(payloads) != 1:
        raise GovernanceError("one authorization comment must contain exactly one marker")
    payload = payloads[0]
    expected = {
        "authorizer_actor_id",
        "expires_at",
        "head_sha",
        "nonce",
        "pr_number",
        "ready_actor_id",
        "repository_id",
        "schema",
    }
    if set(payload) != expected or payload.get("schema") != AUTH_SCHEMA:
        raise GovernanceError("authorization keys do not match the v1 schema")
    head_sha = str(payload["head_sha"])
    nonce = str(payload["nonce"])
    if not SHA_RE.fullmatch(head_sha):
        raise GovernanceError("authorization head SHA must be 40 lowercase hex characters")
    if not NONCE_RE.fullmatch(nonce):
        raise GovernanceError("authorization nonce must be 32 lowercase hex characters")
    return AuthorizationEvidence(
        comment_id=comment.id,
        repository_id=int(payload["repository_id"]),
        pr_number=int(payload["pr_number"]),
        head_sha=head_sha,
        authorizer_actor_id=int(payload["authorizer_actor_id"]),
        ready_actor_id=int(payload["ready_actor_id"]),
        expires_at=parse_time(payload["expires_at"]),
        nonce=nonce,
        payload_hash=digest_payload(payload),
    )


def parse_audit(comment: Comment, policy: Policy) -> AuditRecord | None:
    if (
        comment.author_id != policy.audit_actor_id
        or comment.author_login != policy.audit_actor_login
        or comment.author_type != "Bot"
        or comment.updated_at != comment.created_at
    ):
        return None
    payloads = extract_marker_payloads(comment.body, AUDIT_MARKER)
    if not payloads:
        return None
    if len(payloads) != 1:
        raise GovernanceError("one audit comment must contain exactly one marker")
    payload = payloads[0]
    expected = {
        "action",
        "actor_id",
        "authorization_comment_id",
        "authorization_hash",
        "event_fingerprint",
        "head_sha",
        "nonce",
        "pr_number",
        "reason",
        "recorded_at",
        "repository_id",
        "run_attempt",
        "run_id",
        "schema",
    }
    if set(payload) != expected or payload.get("schema") != AUDIT_SCHEMA:
        raise GovernanceError("audit keys do not match the v1 schema")
    action = str(payload["action"])
    if action not in {"consumed", "denied"}:
        raise GovernanceError("unsupported audit action")
    parse_time(payload["recorded_at"])
    return AuditRecord(
        action=action,
        repository_id=int(payload["repository_id"]),
        pr_number=int(payload["pr_number"]),
        head_sha=str(payload["head_sha"]),
        actor_id=int(payload["actor_id"]),
        authorization_comment_id=(
            int(payload["authorization_comment_id"])
            if payload["authorization_comment_id"] is not None
            else None
        ),
        authorization_hash=(
            str(payload["authorization_hash"])
            if payload["authorization_hash"] is not None
            else None
        ),
        nonce=str(payload["nonce"]) if payload["nonce"] is not None else None,
        event_fingerprint=str(payload["event_fingerprint"]),
        reason=str(payload["reason"]),
    )


def _authorization_error(
    evidence: AuthorizationEvidence,
    comment: Comment,
    event: PullRequestEvent,
    policy: Policy,
    now: dt.datetime,
) -> str | None:
    if comment.author_type != "User":
        return "authorization_author_not_human_user"
    if comment.updated_at != comment.created_at:
        return "authorization_was_edited"
    if evidence.authorizer_actor_id != comment.author_id:
        return "authorization_authorizer_mismatch"
    if evidence.authorizer_actor_id not in policy.human_authorizer_actor_ids:
        return "authorization_authorizer_not_allowed"
    if evidence.authorizer_actor_id in policy.automation_actor_ids:
        return "authorization_authorizer_is_automation"
    if evidence.ready_actor_id != event.actor_id:
        return "authorization_ready_actor_mismatch"
    if evidence.repository_id != event.repository_id:
        return "authorization_repository_mismatch"
    if evidence.pr_number != event.pr_number:
        return "authorization_pr_mismatch"
    if evidence.head_sha != event.head_sha:
        return "authorization_stale_head_sha"
    skew = dt.timedelta(seconds=policy.clock_skew_seconds)
    ttl = dt.timedelta(seconds=policy.authorization_ttl_seconds)
    if comment.created_at > event.event_time + skew:
        return "authorization_created_after_event"
    if evidence.expires_at <= comment.created_at:
        return "authorization_invalid_expiry"
    if evidence.expires_at - comment.created_at > ttl + skew:
        return "authorization_expiry_exceeds_policy"
    if event.event_time > evidence.expires_at + skew or now > evidence.expires_at + skew:
        return "authorization_expired"
    return None


def decide_transition(
    event: PullRequestEvent,
    snapshot: PullRequestSnapshot,
    comments: Sequence[Comment],
    policy: Policy,
    now: dt.datetime,
) -> Decision:
    now = parse_time(now)
    fingerprint = event.fingerprint
    if (
        policy.activation_state != "active"
        or not policy.credential_separation_attested
        or not policy.dedicated_verifier_attested
        or policy.dedicated_verifier_integration_id in {None, 15368}
    ):
        return Decision("deny", "governance_policy_not_activated", fingerprint)
    if event.repository_id != policy.repository_id:
        return Decision("deny", "event_repository_mismatch", fingerprint)
    if snapshot.base_repository_id != policy.repository_id:
        return Decision("deny", "base_repository_mismatch", fingerprint)
    if snapshot.base_ref != policy.default_branch:
        return Decision("deny", "base_branch_mismatch", fingerprint)
    if snapshot.state != "open":
        return Decision("deny", "pull_request_not_open", fingerprint)
    if snapshot.head_sha != event.head_sha:
        return Decision("deny", "current_head_sha_mismatch", fingerprint)
    if snapshot.head_repository_id != event.head_repository_id:
        return Decision("deny", "head_repository_mismatch", fingerprint)
    if event.actor_id not in policy.allowed_ready_actor_ids:
        return Decision("deny", "event_actor_not_allowed", fingerprint)

    audits: list[AuditRecord] = []
    for comment in comments:
        try:
            audit = parse_audit(comment, policy)
        except GovernanceError:
            continue
        if audit is not None:
            audits.append(audit)

    # The same delivered event or workflow rerun is idempotent even if its
    # short-lived authorization has since expired. A later transition produces
    # a different event timestamp/fingerprint and is treated as replay.
    for audit in audits:
        if (
            audit.action == "consumed"
            and audit.repository_id == event.repository_id
            and audit.pr_number == event.pr_number
            and audit.head_sha == event.head_sha
            and audit.actor_id == event.actor_id
            and audit.event_fingerprint == fingerprint
        ):
            return Decision("duplicate", "already_consumed_for_same_event", fingerprint)

    candidates: list[AuthorizationEvidence] = []
    observed_errors: list[str] = []
    for comment in comments:
        try:
            evidence = parse_authorization(comment)
        except GovernanceError:
            if comment.author_id in policy.human_authorizer_actor_ids:
                observed_errors.append("malformed_authorization")
            continue
        if evidence is None:
            continue
        error = _authorization_error(evidence, comment, event, policy, now)
        if error:
            observed_errors.append(error)
            continue
        prior_consumptions = [
            audit
            for audit in audits
            if audit.action == "consumed"
            and (
                audit.authorization_comment_id == evidence.comment_id
                or audit.authorization_hash == evidence.payload_hash
                or audit.nonce == evidence.nonce
            )
        ]
        if prior_consumptions:
            observed_errors.append("authorization_replay")
            continue
        candidates.append(evidence)

    if len(candidates) == 1:
        return Decision("consume", "valid_one_use_authorization", fingerprint, candidates[0])
    if len(candidates) > 1:
        return Decision("deny", "ambiguous_authorization", fingerprint)

    priority = [
        "authorization_replay",
        "authorization_stale_head_sha",
        "authorization_expired",
        "authorization_ready_actor_mismatch",
        "authorization_authorizer_not_allowed",
        "authorization_authorizer_is_automation",
        "authorization_was_edited",
        "malformed_authorization",
    ]
    for reason in priority:
        if reason in observed_errors:
            return Decision("deny", reason, fingerprint)
    return Decision("deny", observed_errors[0] if observed_errors else "missing_authorization", fingerprint)


def validate_production_check_source(
    policy: Policy, check_name: str, integration_id: int
) -> list[str]:
    """Validate the status source that a production ruleset must bind.

    GitHub required checks identify Actions jobs by job name and App source, not
    by workflow path or trigger. Integration 15368 is the shared GitHub Actions
    App and is therefore intentionally rejected as the production verifier.
    """
    errors: list[str] = []
    if policy.activation_state != "active" or not policy.dedicated_verifier_attested:
        errors.append("governance policy is not active with an attested verifier")
    if check_name != policy.production_required_check_name:
        errors.append("production required-check name does not match policy")
    if integration_id == 15368:
        errors.append("GitHub Actions App cannot be the dedicated production verifier")
    if integration_id != policy.dedicated_verifier_integration_id:
        errors.append("required-check integration id does not match the dedicated verifier")
    return errors


def make_audit_record(
    action: str,
    event: PullRequestEvent,
    reason: str,
    recorded_at: dt.datetime,
    evidence: AuthorizationEvidence | None = None,
) -> str:
    if action not in {"consumed", "denied"}:
        raise GovernanceError("audit action must be consumed or denied")
    payload = {
        "action": action,
        "actor_id": event.actor_id,
        "authorization_comment_id": evidence.comment_id if evidence else None,
        "authorization_hash": evidence.payload_hash if evidence else None,
        "event_fingerprint": event.fingerprint,
        "head_sha": event.head_sha,
        "nonce": evidence.nonce if evidence else None,
        "pr_number": event.pr_number,
        "reason": reason,
        "recorded_at": format_time(recorded_at),
        "repository_id": event.repository_id,
        "run_attempt": event.run_attempt,
        "run_id": event.run_id,
        "schema": AUDIT_SCHEMA,
    }
    return make_marker(AUDIT_MARKER, payload)


def make_authorization_record(
    policy: Policy,
    pr_number: int,
    head_sha: str,
    authorizer_actor_id: int,
    ready_actor_id: int,
    now: dt.datetime,
    nonce: str | None = None,
    expires_in: int | None = None,
) -> str:
    if not SHA_RE.fullmatch(head_sha):
        raise GovernanceError("head SHA must be 40 lowercase hex characters")
    if (
        policy.activation_state != "active"
        or not policy.credential_separation_attested
        or not policy.dedicated_verifier_attested
        or policy.dedicated_verifier_integration_id in {None, 15368}
    ):
        raise GovernanceError("governance policy is not activated")
    if authorizer_actor_id not in policy.human_authorizer_actor_ids:
        raise GovernanceError("human authorizer actor id is not allowed by policy")
    if authorizer_actor_id in policy.automation_actor_ids:
        raise GovernanceError("automation identity cannot authorize readiness")
    if ready_actor_id not in policy.allowed_ready_actor_ids:
        raise GovernanceError("ready actor id is not allowed by policy")
    lifetime = expires_in or policy.authorization_ttl_seconds
    if lifetime < 30 or lifetime > policy.authorization_ttl_seconds:
        raise GovernanceError("requested expiry exceeds policy")
    nonce_value = nonce or secrets.token_hex(16)
    if not NONCE_RE.fullmatch(nonce_value):
        raise GovernanceError("nonce must be 32 lowercase hex characters")
    now = parse_time(now)
    payload = {
        "authorizer_actor_id": authorizer_actor_id,
        "expires_at": format_time(now + dt.timedelta(seconds=lifetime)),
        "head_sha": head_sha,
        "nonce": nonce_value,
        "pr_number": pr_number,
        "ready_actor_id": ready_actor_id,
        "repository_id": policy.repository_id,
        "schema": AUTH_SCHEMA,
    }
    return make_marker(AUTH_MARKER, payload)


def _top_level_permissions(text: str) -> dict[str, str] | None:
    """Return a simple explicit top-level permissions map, or None if unsafe."""
    lines = text.splitlines()
    indexes = [
        index
        for index, line in enumerate(lines)
        if re.fullmatch(r"permissions:\s*(?:#.*)?", line)
    ]
    if len(indexes) != 1:
        return None
    permissions: dict[str, str] = {}
    for line in lines[indexes[0] + 1 :]:
        if not line.strip():
            continue
        if not line.startswith((" ", "\t")):
            break
        match = re.fullmatch(
            r"  ([a-z-]+):\s*([a-z-]+)\s*(?:#.*)?", line
        )
        if match is None:
            return None
        permissions[match.group(1)] = match.group(2)
    return permissions


def scan_workflows(
    root: pathlib.Path | str,
    trusted_root: pathlib.Path | str | None = None,
) -> list[str]:
    root_path = pathlib.Path(root)
    workflow_dir = root_path / ".github" / "workflows"
    errors: list[str] = []
    if not workflow_dir.is_dir():
        return ["missing .github/workflows directory"]

    if trusted_root is not None:
        trusted_path = pathlib.Path(trusted_root)
        for relative in PROTECTED_GOVERNANCE_PATHS:
            trusted_file = trusted_path / relative
            candidate_file = root_path / relative
            if not trusted_file.is_file():
                errors.append(f"trusted base is missing protected governance file {relative}")
                continue
            if not candidate_file.is_file():
                errors.append(f"candidate removes protected governance file {relative}")
                continue
            if candidate_file.read_bytes() != trusted_file.read_bytes():
                errors.append(
                    f"candidate changes protected governance file {relative}; "
                    "use the documented controlled two-phase update"
                )

    files = sorted(
        path
        for path in workflow_dir.iterdir()
        if path.is_file() and path.suffix in {".yml", ".yaml"}
    )
    by_relative = {
        pathlib.PurePosixPath(path.relative_to(root_path).as_posix()): path for path in files
    }
    if REQUIRED_WORKFLOW not in by_relative:
        errors.append(f"missing always-emitted advisory workflow {REQUIRED_WORKFLOW}")
    if LEGACY_BOOTSTRAP_WORKFLOW not in by_relative:
        errors.append(f"missing legacy bootstrap workflow {LEGACY_BOOTSTRAP_WORKFLOW}")
    if GUARD_WORKFLOW not in by_relative:
        errors.append(f"missing guard workflow {GUARD_WORKFLOW}")
    policy_file = root_path / ".github" / "kapi-governance" / "policy-v1.json"
    candidate_policy: Policy | None = None
    if not policy_file.is_file():
        errors.append("missing .github/kapi-governance/policy-v1.json")
    else:
        try:
            candidate_policy = Policy.load(policy_file)
        except (GovernanceError, OSError, json.JSONDecodeError) as exc:
            errors.append(f"invalid KAPI governance policy: {exc}")

    advisory_name = (
        candidate_policy.actions_advisory_check_name
        if candidate_policy
        else "kapi-governance-actions-advisory-v1"
    )
    production_name = (
        candidate_policy.production_required_check_name
        if candidate_policy
        else "kapi-governance-external-v1"
    )
    advisory_name_occurrences: list[str] = []
    production_name_occurrences: list[str] = []
    legacy_bootstrap_name_occurrences: list[str] = []
    dangerous_patterns = {
        r"\bgh\s+pr\s+ready\b": "workflow may not mark a PR ready",
        r"markPullRequestReadyForReview": "workflow may not call the ready mutation",
        r"submitPullRequestReview": "workflow may not submit approval reviews",
        r"\bgh\s+pr\s+review\b[^\n]*--approve": "workflow may not approve a PR",
        r"[\"']event[\"']?\s*:\s*[\"']?APPROVE": "workflow may not submit APPROVE",
    }
    write_permission_re = re.compile(
        r"(?m)^\s*([a-z-]+):\s*(write|write-all)\s*(?:#.*)?$"
    )
    reserved_job_re_template = r"(?m)^\s{{4}}name:\s*[\"']?{}[\"']?\s*(?:#.*)?$"

    for relative, path in by_relative.items():
        text = path.read_text(encoding="utf-8")
        if _top_level_permissions(text) != {"contents": "read"}:
            errors.append(
                f"{relative}: top-level token permissions must be explicitly contents: read"
            )
        advisory_re = re.compile(
            reserved_job_re_template.format(re.escape(advisory_name))
        )
        production_re = re.compile(
            reserved_job_re_template.format(re.escape(production_name))
        )
        if advisory_re.search(text):
            advisory_name_occurrences.append(str(relative))
        if production_re.search(text):
            production_name_occurrences.append(str(relative))
        legacy_bootstrap_re = re.compile(
            reserved_job_re_template.format(
                re.escape(LEGACY_BOOTSTRAP_CHECK_NAME)
            )
        )
        if legacy_bootstrap_re.search(text):
            legacy_bootstrap_name_occurrences.append(str(relative))

        if re.search(r"(?m)^\s*permissions:\s*write-all\s*$", text):
            errors.append(f"{relative}: write-all token permission is forbidden")
        for match in write_permission_re.finditer(text):
            permission = match.group(1)
            if relative == GUARD_WORKFLOW and permission == "pull-requests":
                continue
            errors.append(f"{relative}: unauthorized {permission}: write permission")

        for pattern, message in dangerous_patterns.items():
            if re.search(pattern, text, re.IGNORECASE):
                errors.append(f"{relative}: {message}")
        if "convertPullRequestToDraft" in text and relative != GUARD_WORKFLOW:
            errors.append(f"{relative}: draft mutation is reserved to the guard")
        if re.search(r"(?m)^\s*pull_request_target:\s*$", text) and relative != GUARD_WORKFLOW:
            errors.append(f"{relative}: pull_request_target is reserved to the guard")
        for uses in re.finditer(r"(?m)^\s*-?\s*uses:\s*([^\s#]+)", text):
            action = uses.group(1).strip("\"'")
            if action.startswith("./"):
                continue
            reference = action.rsplit("@", 1)[-1] if "@" in action else ""
            if not re.fullmatch(r"[0-9a-f]{40}", reference):
                errors.append(f"{relative}: action must be pinned to a full commit SHA: {action}")

    if advisory_name_occurrences != [str(REQUIRED_WORKFLOW)]:
        errors.append(
            "reserved Actions advisory check name must occur exactly once in "
            f"{REQUIRED_WORKFLOW}; found {advisory_name_occurrences}"
        )
    if production_name_occurrences:
        errors.append(
            "production required-check name is reserved to the dedicated external "
            f"verifier and must not be emitted by Actions; found {production_name_occurrences}"
        )
    if legacy_bootstrap_name_occurrences != [str(LEGACY_BOOTSTRAP_WORKFLOW)]:
        errors.append(
            "legacy verify check name must occur exactly once in "
            f"{LEGACY_BOOTSTRAP_WORKFLOW}; found {legacy_bootstrap_name_occurrences}"
        )

    legacy_path = by_relative.get(LEGACY_BOOTSTRAP_WORKFLOW)
    if legacy_path:
        legacy_text = legacy_path.read_text(encoding="utf-8")
        if not re.search(r"(?m)^\s{2}pull_request:\s*$", legacy_text):
            errors.append(f"{LEGACY_BOOTSTRAP_WORKFLOW}: pull_request trigger is required")
        if re.search(r"(?m)^\s+(paths|paths-ignore):", legacy_text):
            errors.append(
                f"{LEGACY_BOOTSTRAP_WORKFLOW}: path filters can suppress legacy verify"
            )
        legacy_invariants = {
            "contents: read": "legacy verify token must remain read-only",
            "ref: ${{ github.event.pull_request.base.sha }}": "legacy verify must checkout base SHA",
            "path: .kapi-trusted": "legacy verify must isolate trusted base files",
            "ref: ${{ github.event.pull_request.head.sha }}": "legacy verify must bind exact PR head",
            "path: .kapi-candidate": "legacy verify must isolate candidate as inert data",
            f"BOOTSTRAP_BASE_SHA: {PR4_BOOTSTRAP_BASE_SHA}": "PR #4 bootstrap base must stay pinned",
            'test "$PR_NUMBER" = "4"': "bootstrap exception must be restricted to PR #4",
            'test "$HEAD_REPOSITORY_ID" = "$REPOSITORY_ID"': "bootstrap must reject forks",
            "python3 .kapi-trusted/.github/scripts/kapi_governance.py": "post-bootstrap scan must execute trusted base code",
            "-s .kapi-trusted/.github/tests": "post-bootstrap tests must execute trusted base code",
            "sha256sum .kapi-candidate/.github/workflows/kapi.yml": "bootstrap must expose candidate workflow hash",
        }
        for needle, message in legacy_invariants.items():
            if needle not in legacy_text:
                errors.append(f"{LEGACY_BOOTSTRAP_WORKFLOW}: {message}")
        if "pull_request_target:" in legacy_text:
            errors.append(
                f"{LEGACY_BOOTSTRAP_WORKFLOW}: legacy verify must not be privileged"
            )
        if re.search(
            r"(?m)(python\d*|bash|sh|node|source|\.)\s+\.kapi-candidate|"
            r"(npm[^\n]*--prefix|make[^\n]*-C)\s+\.kapi-candidate",
            legacy_text,
        ):
            errors.append(
                f"{LEGACY_BOOTSTRAP_WORKFLOW}: candidate code must never be executed"
            )
        if re.search(r"(?m)^\s*working-directory:\s*\.kapi-candidate", legacy_text):
            errors.append(
                f"{LEGACY_BOOTSTRAP_WORKFLOW}: candidate cannot be a working directory"
            )

    required_path = by_relative.get(REQUIRED_WORKFLOW)
    if required_path:
        required_text = required_path.read_text(encoding="utf-8")
        if not re.search(r"(?m)^\s{2}pull_request:\s*$", required_text):
            errors.append(f"{REQUIRED_WORKFLOW}: pull_request trigger is required")
        if re.search(r"(?m)^\s+(paths|paths-ignore):", required_text):
            errors.append(f"{REQUIRED_WORKFLOW}: path filters can suppress the required check")
        if "contents: read" not in required_text:
            errors.append(f"{REQUIRED_WORKFLOW}: contents must remain read-only")
        if "pull_request_target:" in required_text:
            errors.append(f"{REQUIRED_WORKFLOW}: required check must not be privileged")
        trusted_invariants = {
            "ref: ${{ github.event.pull_request.base.sha }}": "advisory must checkout base SHA",
            "path: .kapi-trusted": "advisory must isolate trusted base files",
            "ref: ${{ github.event.pull_request.head.sha }}": "candidate must be explicit PR head data",
            "path: .kapi-candidate": "candidate must be isolated as inert data",
            "python3 .kapi-trusted/.github/scripts/kapi_governance.py": "scanner must execute from base",
            "python3 -m unittest discover -s .kapi-trusted/.github/tests": "tests must execute from base",
        }
        for needle, message in trusted_invariants.items():
            if needle not in required_text:
                errors.append(f"{REQUIRED_WORKFLOW}: {message}")
        if re.search(
            r"(?m)(python\d*|bash|sh|node|source|\.)\s+\.kapi-candidate|"
            r"(npm[^\n]*--prefix|make[^\n]*-C)\s+\.kapi-candidate",
            required_text,
        ):
            errors.append(f"{REQUIRED_WORKFLOW}: candidate code must never be executed")
        if re.search(r"(?m)^\s*working-directory:\s*\.kapi-candidate", required_text):
            errors.append(f"{REQUIRED_WORKFLOW}: candidate cannot be a working directory")

    guard_path = by_relative.get(GUARD_WORKFLOW)
    if guard_path:
        guard_text = guard_path.read_text(encoding="utf-8")
        invariants = {
            "pull_request_target:": "metadata guard must use pull_request_target",
            "branches: [main]": "guard must target main",
            "types: [ready_for_review]": "guard must handle ready_for_review only",
            "schedule:": "guard must schedule missed-event reconciliation",
            'cron: "*/5 * * * *"': "guard reconciliation must run every five minutes",
            "workflow_dispatch:": "guard reconciliation must support manual execution",
            "pull-requests: write": "guard needs job-scoped metadata write",
            "ref: ${{ github.event.pull_request.base.sha }}": "guard must checkout base SHA",
            "persist-credentials: false": "checkout credentials must not persist",
            "cancel-in-progress: false": "per-PR authorization events must serialize",
            "timeout-minutes:": "guard must have a timeout",
            "python3 .github/scripts/kapi_ready_guard.py": "guard must run trusted policy code",
            "python3 .github/scripts/kapi_ready_reconcile.py": "guard must reconcile missed events",
        }
        for needle, message in invariants.items():
            if needle not in guard_text:
                errors.append(f"{GUARD_WORKFLOW}: {message}")
        if re.search(
            r"ref:\s*\$\{\{\s*github\.event\.pull_request\.head", guard_text
        ):
            errors.append(f"{GUARD_WORKFLOW}: guard must never checkout PR head code")
        if "github.actor !=" in guard_text or "github.actor ==" in guard_text:
            errors.append(f"{GUARD_WORKFLOW}: login-only authorization is forbidden")

    return errors


def _command_scan(args: argparse.Namespace) -> int:
    errors = scan_workflows(args.root, args.trusted_root)
    if errors:
        for error in errors:
            print(f"ERROR: {error}", file=sys.stderr)
        return 1
    print("KAPI workflow governance scan passed")
    return 0


def _command_authorize(args: argparse.Namespace) -> int:
    policy = Policy.load(args.policy)
    marker = make_authorization_record(
        policy=policy,
        pr_number=args.pr_number,
        head_sha=args.head_sha,
        authorizer_actor_id=args.authorizer_actor_id,
        ready_actor_id=args.ready_actor_id,
        now=dt.datetime.now(UTC),
        nonce=args.nonce,
        expires_in=args.expires_in,
    )
    print(marker)
    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description=__doc__)
    subparsers = parser.add_subparsers(dest="command", required=True)
    scan = subparsers.add_parser("scan-workflows")
    scan.add_argument("--root", default=".")
    scan.add_argument("--trusted-root")
    scan.set_defaults(func=_command_scan)

    authorize = subparsers.add_parser("authorize-ready")
    authorize.add_argument(
        "--policy", default=".github/kapi-governance/policy-v1.json"
    )
    authorize.add_argument("--pr-number", type=int, required=True)
    authorize.add_argument("--head-sha", required=True)
    authorize.add_argument("--authorizer-actor-id", type=int, required=True)
    authorize.add_argument("--ready-actor-id", type=int, required=True)
    authorize.add_argument("--nonce")
    authorize.add_argument("--expires-in", type=int)
    authorize.set_defaults(func=_command_authorize)
    return parser


def main(argv: Iterable[str] | None = None) -> int:
    args = build_parser().parse_args(list(argv) if argv is not None else None)
    try:
        return int(args.func(args))
    except (GovernanceError, OSError, json.JSONDecodeError) as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    raise SystemExit(main())
