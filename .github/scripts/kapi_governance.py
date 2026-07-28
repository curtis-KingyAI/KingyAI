#!/usr/bin/env python3
"""Pure, dependency-free KAPI governance policy and workflow checks.

The privileged workflow imports this module from an explicitly checked-out base
SHA. Pull-request content is metadata only in that workflow and is never
executed. The same decision functions are exercised by local unit tests.
"""

from __future__ import annotations

import argparse
from collections import Counter
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
# Model v2. Operator-reviewed with independent mechanical verification: no human
# authorizer is required or claimed. The only prerequisite is an attested dedicated
# verifier distinct from the shared GitHub Actions App, which becomes the sole
# independent component. The stricter "active" state is retained verbatim and
# unreachable so a genuine two-key model can be adopted later without rebuilding it.
ACTIVE_OPERATOR_REVIEWED = "active_operator_reviewed"
REQUIRED_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/kapi-governance-actions-advisory-v1.yml"
)
LEGACY_BOOTSTRAP_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/kapi.yml"
)
GUARD_WORKFLOW = pathlib.PurePosixPath(
    ".github/workflows/ready-for-review-guard.yml"
)
POLICY_PATH = pathlib.PurePosixPath(".github/kapi-governance/policy-v1.json")
GOVERNANCE_README_PATH = pathlib.PurePosixPath(
    ".github/kapi-governance/README.md"
)
CODEOWNERS_PATH = pathlib.PurePosixPath(".github/CODEOWNERS")
CHECKOUT_ACTION = (
    "actions/checkout@9c091bb21b7c1c1d1991bb908d89e4e9dddfe3e0"
)
EXPECTED_WORKFLOWS = frozenset(
    {REQUIRED_WORKFLOW, LEGACY_BOOTSTRAP_WORKFLOW, GUARD_WORKFLOW}
)
EXPECTED_ACTIONS = {
    REQUIRED_WORKFLOW: Counter({CHECKOUT_ACTION: 2}),
    LEGACY_BOOTSTRAP_WORKFLOW: Counter({CHECKOUT_ACTION: 2}),
    GUARD_WORKFLOW: Counter({CHECKOUT_ACTION: 2}),
}
EXPECTED_GITHUB_TOKEN_BINDINGS = {
    REQUIRED_WORKFLOW: 0,
    LEGACY_BOOTSTRAP_WORKFLOW: 0,
    GUARD_WORKFLOW: 2,
}
EXPECTED_PULL_REQUEST_WRITE_BINDINGS = {
    REQUIRED_WORKFLOW: 0,
    LEGACY_BOOTSTRAP_WORKFLOW: 0,
    GUARD_WORKFLOW: 2,
}
EXPECTED_TRUSTED_SPARSE_PATHS = frozenset(
    {
        CODEOWNERS_PATH,
        pathlib.PurePosixPath(".github/kapi-governance"),
        pathlib.PurePosixPath(".github/scripts"),
        pathlib.PurePosixPath(".github/tests"),
        pathlib.PurePosixPath(".github/workflows"),
    }
)
LEGACY_BOOTSTRAP_CHECK_NAME = "verify"
PR4_BOOTSTRAP_BASE_SHA = "6070f10c9ab5611c0966056a43eb24ae6beda7ce"
# Governance files, split by kind.
#
# The anti-self-blessing rule -- a change must not be judged by logic derived from
# that same change -- applies to code that defines the check. It does not apply to
# a declarative document that the *trusted base* validator can judge, because the
# evaluating code is unchanged in that case. Applying the executable rule to
# declarative documents made the policy unchangeable by any path, including the
# policy-only pull request the README's own step 9 requires.
EXECUTABLE_GOVERNANCE_PATHS = (
    pathlib.PurePosixPath(".github/scripts/kapi_governance.py"),
    pathlib.PurePosixPath(".github/scripts/kapi_ready_guard.py"),
    pathlib.PurePosixPath(".github/scripts/kapi_ready_reconcile.py"),
    pathlib.PurePosixPath(".github/tests/test_kapi_governance.py"),
    LEGACY_BOOTSTRAP_WORKFLOW,
    REQUIRED_WORKFLOW,
    GUARD_WORKFLOW,
)

# Declarative, but not machine-validatable. CODEOWNERS routes review privilege;
# the README is the normative procedure operators act on. Nothing can check either
# for truthfulness, so a false claim added to them is exactly the threat, and they
# stay under the strict rule. They ride along in the same operator-reviewed change
# as the code they describe, which is where they belong anyway.
NORMATIVE_GOVERNANCE_PATHS = (CODEOWNERS_PATH, GOVERNANCE_README_PATH)

# Declarative AND machine-validatable. Policy.load, running from the trusted base,
# already refuses every dangerous shape of this document, so the safety property
# does not depend on blocking the file -- it depends on validating it.
VALIDATED_GOVERNANCE_PATHS = (POLICY_PATH,)

# The manifest hashes this set sorted by path, so regrouping does not change it.
PROTECTED_GOVERNANCE_PATHS = (
    EXECUTABLE_GOVERNANCE_PATHS + NORMATIVE_GOVERNANCE_PATHS + VALIDATED_GOVERNANCE_PATHS
)

STRICT_GOVERNANCE_PATHS = frozenset(EXECUTABLE_GOVERNANCE_PATHS + NORMATIVE_GOVERNANCE_PATHS)

# The only policy fields a declarative change may alter. Everything else --
# repository_id, audit_actor_id, allowed_ready_actor_ids, automation_actor_ids,
# draft_restoration_mode, the TTLs -- defines who may act rather than what has been
# attested, and stays under the strict rule.
MUTABLE_POLICY_FIELDS = frozenset(
    {
        "human_authorizer_actor_ids",
        "dedicated_verifier_integration_id",
        "credential_separation_attested",
        "dedicated_verifier_attested",
        "activation_state",
    }
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
    draft_restoration_mode: str
    audit_actor_id: int
    audit_actor_login: str
    authorization_ttl_seconds: int
    clock_skew_seconds: int
    actions_advisory_check_name: str
    production_required_check_name: str
    review_positioning: str
    stronger_review_claims_allowed: bool

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
            "draft_restoration_mode",
            "human_authorizer_actor_ids",
            "repository_id",
            "production_required_check_name",
            "review_positioning",
            "schema",
            "stronger_review_claims_allowed",
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
            ACTIVE_OPERATOR_REVIEWED,
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
        restoration_mode = raw["draft_restoration_mode"]
        if restoration_mode != "manual_operator":
            raise GovernanceError(
                "draft restoration mode must remain manual_operator until a "
                "separately controlled GitHub identity is capability-tested"
            )
        overlap = set(authorizers) & (set(ready) | set(automation))
        if overlap:
            raise GovernanceError(
                "human authorizer ids must not overlap ready or automation identities"
            )
        if activation_state == ACTIVE_OPERATOR_REVIEWED:
            if not verifier_attested or verifier_id is None or verifier_id == 15368:
                raise GovernanceError(
                    "operator-reviewed activation requires an attested verifier "
                    "integration distinct from GitHub Actions"
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
        review_positioning = raw["review_positioning"]
        if review_positioning != "Operator-reviewed":
            raise GovernanceError("KAPI review positioning must be Operator-reviewed")
        stronger_claims = raw["stronger_review_claims_allowed"]
        if stronger_claims is not False:
            raise GovernanceError("stronger KAPI review claims must remain disabled")
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
            draft_restoration_mode=restoration_mode,
            audit_actor_id=int(raw["audit_actor_id"]),
            audit_actor_login=str(raw["audit_actor_login"]),
            authorization_ttl_seconds=ttl,
            clock_skew_seconds=skew,
            actions_advisory_check_name=advisory_name,
            production_required_check_name=production_name,
            review_positioning=review_positioning,
            stronger_review_claims_allowed=stronger_claims,
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
    base_sha: str
    trusted_governance_sha: str
    head_sha: str
    head_repository_id: int
    actor_id: int
    actor_login: str
    event_time: dt.datetime
    run_id: int
    run_attempt: int
    policy_sha256: str
    protected_files_sha256: str

    @property
    def fingerprint(self) -> str:
        return digest_payload(
            {
                "action": "ready_for_review",
                "actor_id": self.actor_id,
                "base_sha": self.base_sha,
                "event_time": format_time(self.event_time),
                "head_sha": self.head_sha,
                "policy_sha256": self.policy_sha256,
                "pr_number": self.pr_number,
                "protected_files_sha256": self.protected_files_sha256,
                "repository_id": self.repository_id,
                "trusted_governance_sha": self.trusted_governance_sha,
            }
        )


@dataclasses.dataclass(frozen=True)
class PullRequestSnapshot:
    base_sha: str
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
            base_sha=str(raw["base"]["sha"]),
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
    base_sha: str
    head_sha: str
    authorizer_actor_id: int
    ready_actor_id: int
    expires_at: dt.datetime
    nonce: str
    policy_sha256: str
    protected_files_sha256: str
    payload_hash: str


@dataclasses.dataclass(frozen=True)
class AuditRecord:
    action: str
    repository_id: int
    pr_number: int
    base_sha: str
    head_sha: str
    actor_id: int
    authorization_comment_id: int | None
    authorization_hash: str | None
    nonce: str | None
    policy_sha256: str
    protected_files_sha256: str
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


def digest_file(path: pathlib.Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def governance_file_bindings(
    root: pathlib.Path | str,
) -> tuple[str, str]:
    """Return policy and protected-file manifest SHA-256 bindings.

    The manifest hashes every approved governance file by both path and content,
    then hashes the stable newline-delimited manifest. Missing files fail closed.
    """
    root_path = pathlib.Path(root)
    policy_sha256 = digest_file(root_path / POLICY_PATH)
    entries: list[str] = []
    for relative in sorted(PROTECTED_GOVERNANCE_PATHS, key=str):
        path = root_path / relative
        if not path.is_file():
            raise GovernanceError(f"missing protected governance file {relative}")
        entries.append(f"{digest_file(path)}  {relative.as_posix()}")
    manifest = "\n".join(entries) + "\n"
    return policy_sha256, hashlib.sha256(manifest.encode("utf-8")).hexdigest()


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
        "base_sha",
        "expires_at",
        "head_sha",
        "nonce",
        "policy_sha256",
        "pr_number",
        "protected_files_sha256",
        "ready_actor_id",
        "repository_id",
        "schema",
    }
    if set(payload) != expected or payload.get("schema") != AUTH_SCHEMA:
        raise GovernanceError("authorization keys do not match the v1 schema")
    base_sha = str(payload["base_sha"])
    head_sha = str(payload["head_sha"])
    nonce = str(payload["nonce"])
    policy_sha256 = str(payload["policy_sha256"])
    protected_files_sha256 = str(payload["protected_files_sha256"])
    if not SHA_RE.fullmatch(base_sha):
        raise GovernanceError("authorization base SHA must be 40 lowercase hex characters")
    if not SHA_RE.fullmatch(head_sha):
        raise GovernanceError("authorization head SHA must be 40 lowercase hex characters")
    if not NONCE_RE.fullmatch(nonce):
        raise GovernanceError("authorization nonce must be 32 lowercase hex characters")
    if not re.fullmatch(r"[0-9a-f]{64}", policy_sha256):
        raise GovernanceError("authorization policy hash must be 64 lowercase hex characters")
    if not re.fullmatch(r"[0-9a-f]{64}", protected_files_sha256):
        raise GovernanceError("authorization manifest hash must be 64 lowercase hex characters")
    return AuthorizationEvidence(
        comment_id=comment.id,
        repository_id=int(payload["repository_id"]),
        pr_number=int(payload["pr_number"]),
        base_sha=base_sha,
        head_sha=head_sha,
        authorizer_actor_id=int(payload["authorizer_actor_id"]),
        ready_actor_id=int(payload["ready_actor_id"]),
        expires_at=parse_time(payload["expires_at"]),
        nonce=nonce,
        policy_sha256=policy_sha256,
        protected_files_sha256=protected_files_sha256,
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
        "base_sha",
        "event_fingerprint",
        "head_sha",
        "nonce",
        "policy_sha256",
        "pr_number",
        "protected_files_sha256",
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
    if action not in {"consumed", "permitted", "denied"}:
        raise GovernanceError("unsupported audit action")
    parse_time(payload["recorded_at"])
    return AuditRecord(
        action=action,
        repository_id=int(payload["repository_id"]),
        pr_number=int(payload["pr_number"]),
        base_sha=str(payload["base_sha"]),
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
        policy_sha256=str(payload["policy_sha256"]),
        protected_files_sha256=str(payload["protected_files_sha256"]),
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
    if evidence.base_sha != event.base_sha:
        return "authorization_stale_base_sha"
    if evidence.head_sha != event.head_sha:
        return "authorization_stale_head_sha"
    if evidence.policy_sha256 != event.policy_sha256:
        return "authorization_policy_hash_mismatch"
    if evidence.protected_files_sha256 != event.protected_files_sha256:
        return "authorization_protected_files_hash_mismatch"
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
    verifier_ready = (
        policy.dedicated_verifier_attested
        and policy.dedicated_verifier_integration_id not in {None, 15368}
    )
    if policy.activation_state == "active":
        activated = verifier_ready and policy.credential_separation_attested
    elif policy.activation_state == ACTIVE_OPERATOR_REVIEWED:
        activated = verifier_ready
    else:
        activated = False
    if not activated:
        return Decision("deny", "governance_policy_not_activated", fingerprint)
    if event.repository_id != policy.repository_id:
        return Decision("deny", "event_repository_mismatch", fingerprint)
    if snapshot.base_repository_id != policy.repository_id:
        return Decision("deny", "base_repository_mismatch", fingerprint)
    if snapshot.base_ref != policy.default_branch:
        return Decision("deny", "base_branch_mismatch", fingerprint)
    if snapshot.base_sha != event.base_sha:
        return Decision("deny", "current_base_sha_mismatch", fingerprint)
    if event.trusted_governance_sha != event.base_sha:
        return Decision("deny", "trusted_governance_sha_mismatch", fingerprint)
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
            audit.action in {"consumed", "permitted"}
            and audit.repository_id == event.repository_id
            and audit.pr_number == event.pr_number
            and audit.base_sha == event.base_sha
            and audit.head_sha == event.head_sha
            and audit.actor_id == event.actor_id
            and audit.event_fingerprint == fingerprint
            and audit.policy_sha256 == event.policy_sha256
            and audit.protected_files_sha256 == event.protected_files_sha256
        ):
            return Decision("duplicate", "already_consumed_for_same_event", fingerprint)

    if policy.activation_state == ACTIVE_OPERATOR_REVIEWED:
        # No human authorization is required, and none is claimed. Every integrity
        # check above still applied: repository identity, base/head SHA agreement,
        # trusted-governance SHA, open state, and the allowed ready actor.
        #
        # The merge boundary in this mode is the external required check bound to
        # the dedicated verifier's App identity and enforced by the branch ruleset --
        # not this guard. A distinct action is used rather than reusing "consume" so
        # the audit trail never records an authorization that did not exist.
        return Decision(
            "permit", "operator_reviewed_no_authorization_required", fingerprint
        )

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
        "authorization_stale_base_sha",
        "authorization_stale_head_sha",
        "authorization_policy_hash_mismatch",
        "authorization_protected_files_hash_mismatch",
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
    if action not in {"consumed", "permitted", "denied"}:
        raise GovernanceError("audit action must be consumed, permitted or denied")
    payload = {
        "action": action,
        "actor_id": event.actor_id,
        "authorization_comment_id": evidence.comment_id if evidence else None,
        "authorization_hash": evidence.payload_hash if evidence else None,
        "base_sha": event.base_sha,
        "event_fingerprint": event.fingerprint,
        "head_sha": event.head_sha,
        "nonce": evidence.nonce if evidence else None,
        "policy_sha256": event.policy_sha256,
        "pr_number": event.pr_number,
        "protected_files_sha256": event.protected_files_sha256,
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
    base_sha: str,
    head_sha: str,
    authorizer_actor_id: int,
    ready_actor_id: int,
    now: dt.datetime,
    policy_sha256: str,
    protected_files_sha256: str,
    nonce: str | None = None,
    expires_in: int | None = None,
) -> str:
    if not SHA_RE.fullmatch(base_sha):
        raise GovernanceError("base SHA must be 40 lowercase hex characters")
    if not SHA_RE.fullmatch(head_sha):
        raise GovernanceError("head SHA must be 40 lowercase hex characters")
    if not re.fullmatch(r"[0-9a-f]{64}", policy_sha256):
        raise GovernanceError("policy hash must be 64 lowercase hex characters")
    if not re.fullmatch(r"[0-9a-f]{64}", protected_files_sha256):
        raise GovernanceError("protected-files hash must be 64 lowercase hex characters")
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
        "base_sha": base_sha,
        "expires_at": format_time(now + dt.timedelta(seconds=lifetime)),
        "head_sha": head_sha,
        "nonce": nonce_value,
        "policy_sha256": policy_sha256,
        "pr_number": pr_number,
        "protected_files_sha256": protected_files_sha256,
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


def _trusted_sparse_checkout_entries(
    text: str,
) -> list[frozenset[pathlib.PurePosixPath]]:
    lines = text.splitlines()
    blocks: list[frozenset[pathlib.PurePosixPath]] = []
    for index, line in enumerate(lines):
        match = re.fullmatch(r"(\s*)sparse-checkout:\s*\|\s*(?:#.*)?", line)
        if match is None:
            continue
        base_indent = len(match.group(1))
        entries: set[pathlib.PurePosixPath] = set()
        for candidate in lines[index + 1 :]:
            if not candidate.strip():
                continue
            indent = len(candidate) - len(candidate.lstrip())
            if indent <= base_indent:
                break
            value = candidate.strip()
            if not value.startswith("#"):
                entries.add(pathlib.PurePosixPath(value))
        blocks.append(frozenset(entries))
    return blocks


def validate_policy_change(
    root_path: pathlib.Path,
    trusted_path: pathlib.Path,
    changed: list[pathlib.PurePosixPath],
) -> list[str]:
    """Judge a policy-only governance change.

    Safe because the judging code is the trusted base, unchanged by the pull
    request, and because ``Policy.load`` already refuses a policy that would
    loosen the gate: ``active`` requires a separate human authorizer, both
    attestations, and a verifier integration distinct from GitHub Actions.

    Every failure path here is closed. Unreadable, unparseable, ambiguous, or
    outside the permitted field set all produce an error.
    """
    errors: list[str] = []
    if POLICY_PATH not in changed:
        return errors  # documentation-only change

    candidate_policy = root_path / POLICY_PATH
    trusted_policy = trusted_path / POLICY_PATH

    try:
        Policy.load(candidate_policy)
    except (GovernanceError, OSError, json.JSONDecodeError) as exc:
        errors.append(
            f"candidate {POLICY_PATH} fails the trusted validator: {exc}; "
            "the validator is unchanged by this pull request, so this is an "
            "invariant violation rather than a disagreement about rules"
        )
        return errors

    try:
        candidate = json.loads(candidate_policy.read_text(encoding="utf-8"))
        trusted = json.loads(trusted_policy.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError, UnicodeDecodeError) as exc:
        errors.append(f"could not compare {POLICY_PATH} against the trusted base: {exc}")
        return errors

    if not isinstance(candidate, dict) or not isinstance(trusted, dict):
        errors.append(f"{POLICY_PATH} must be a JSON object on both sides")
        return errors

    changed_fields = sorted(
        key
        for key in set(candidate) | set(trusted)
        if candidate.get(key) != trusted.get(key)
    )
    if not changed_fields:
        errors.append(
            f"{POLICY_PATH} differs from the trusted base but no field value changed; "
            "reformatting a protected governance file is not a permitted change"
        )
        return errors

    forbidden = [key for key in changed_fields if key not in MUTABLE_POLICY_FIELDS]
    if forbidden:
        permitted = ", ".join(sorted(MUTABLE_POLICY_FIELDS))
        errors.append(
            f"candidate {POLICY_PATH} changes fields that a policy-only pull request "
            f"may not change: {', '.join(forbidden)}; permitted fields are {permitted}"
        )
    return errors


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
        changed: list[pathlib.PurePosixPath] = []
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
                changed.append(relative)

        strict_changed = [r for r in changed if r in STRICT_GOVERNANCE_PATHS]
        validated_changed = [r for r in changed if r not in STRICT_GOVERNANCE_PATHS]

        for relative in strict_changed:
            errors.append(
                f"candidate changes protected governance file {relative}; "
                "executable and normative governance cannot be changed by an "
                "ordinary pull request. See .github/kapi-governance/README.md: it "
                "requires a new version evaluated by the external verifier"
            )

        if validated_changed and strict_changed:
            # Isolation. This is what preserves the anti-self-blessing property: a
            # change may touch declarative governance or executable governance, never
            # both, so declarative changes are always judged by unchanged code.
            errors.append(
                "candidate changes the policy and other protected governance in the "
                "same pull request; split them so the policy change is judged by "
                "unchanged code"
            )
        elif validated_changed:
            errors.extend(
                validate_policy_change(root_path, trusted_path, validated_changed)
            )

    files = sorted(
        path
        for path in workflow_dir.iterdir()
        if path.is_file() and path.suffix in {".yml", ".yaml"}
    )
    by_relative = {
        pathlib.PurePosixPath(path.relative_to(root_path).as_posix()): path for path in files
    }
    actual_workflows = frozenset(by_relative)
    if actual_workflows != EXPECTED_WORKFLOWS:
        missing = sorted(str(path) for path in EXPECTED_WORKFLOWS - actual_workflows)
        unexpected = sorted(str(path) for path in actual_workflows - EXPECTED_WORKFLOWS)
        errors.append(
            "workflow inventory must match approved allowlist; "
            f"missing={missing}; unexpected={unexpected}"
        )
    if REQUIRED_WORKFLOW not in by_relative:
        errors.append(f"missing always-emitted advisory workflow {REQUIRED_WORKFLOW}")
    if LEGACY_BOOTSTRAP_WORKFLOW not in by_relative:
        errors.append(f"missing legacy bootstrap workflow {LEGACY_BOOTSTRAP_WORKFLOW}")
    if GUARD_WORKFLOW not in by_relative:
        errors.append(f"missing guard workflow {GUARD_WORKFLOW}")
    policy_file = root_path / POLICY_PATH
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
    credential_patterns = (
        (r"\$\{\{\s*secrets(?:\.|\[)", "secrets contexts are forbidden"),
        (
            r"(?im)^\s*(?:GH_TOKEN|GITHUB_PAT|PERSONAL_ACCESS_TOKEN|PAT_TOKEN|API_TOKEN)\s*:",
            "alternate credential aliases are forbidden",
        ),
        (
            r"(?i)\b(?:gh[pousr]_[A-Za-z0-9_]{20,}|github_pat_[A-Za-z0-9_]{20,})\b",
            "PAT-like literals are forbidden",
        ),
        (
            r"(?im)^\s*secrets\s*:\s*inherit\s*(?:#.*)?$",
            "inherited secrets are forbidden",
        ),
        (
            r"(?i)actions/create-github-app-token(?:@|\b)",
            "GitHub App token minting actions are forbidden",
        ),
        (
            r"(?i)/app/installations/(?:\{[^}]+\}|[A-Za-z0-9_.$-]+)/access_tokens",
            "GitHub App token endpoints are forbidden",
        ),
        (
            r"(?im)^\s*(?:PRIVATE_KEY|APP_PRIVATE_KEY|GITHUB_APP_PRIVATE_KEY)\s*:",
            "private-key bindings are forbidden",
        ),
        (
            r"\$\{\{\s*(?:vars|env)\.[^}]*?(?:TOKEN|KEY|SECRET)[^}]*\}\}",
            "credential-like vars/env bindings are forbidden",
        ),
    )

    for relative, path in by_relative.items():
        text = path.read_text(encoding="utf-8")
        actions = Counter(
            match.group(1).strip("\"'")
            for match in re.finditer(r"(?m)^\s*-?\s*uses:\s*([^\s#]+)", text)
        )
        if actions != EXPECTED_ACTIONS.get(relative, Counter()):
            errors.append(f"{relative}: action inventory must match approved allowlist")
        token_bindings = len(
            re.findall(
                r"(?m)^\s*GITHUB_TOKEN:\s*\$\{\{\s*github\.token\s*\}\}\s*(?:#.*)?$",
                text,
            )
        )
        expected_token_bindings = EXPECTED_GITHUB_TOKEN_BINDINGS.get(relative, 0)
        total_token_keys = len(
            re.findall(r"(?m)^\s*GITHUB_TOKEN:\s*", text)
        )
        if (
            token_bindings != expected_token_bindings
            or total_token_keys != expected_token_bindings
        ):
            errors.append(
                f"{relative}: GITHUB_TOKEN binding count must be exactly "
                f"{expected_token_bindings}"
            )
        checkout_count = EXPECTED_ACTIONS.get(relative, Counter()).get(
            CHECKOUT_ACTION, 0
        )
        persist_false_count = len(
            re.findall(
                r"(?m)^\s*persist-credentials:\s*false\s*(?:#.*)?$",
                text,
            )
        )
        total_persist_keys = len(
            re.findall(r"(?m)^\s*persist-credentials:\s*", text)
        )
        if (
            persist_false_count != checkout_count
            or total_persist_keys != checkout_count
        ):
            errors.append(
                f"{relative}: every checkout must set persist-credentials false; "
                f"expected {checkout_count}, found {persist_false_count}"
            )
        pull_request_write_count = len(
            re.findall(
                r"(?m)^\s*pull-requests:\s*write\s*(?:#.*)?$",
                text,
            )
        )
        expected_pull_request_writes = EXPECTED_PULL_REQUEST_WRITE_BINDINGS.get(
            relative, 0
        )
        if pull_request_write_count != expected_pull_request_writes:
            errors.append(
                f"{relative}: pull-requests write binding count must be exactly "
                f"{expected_pull_request_writes}"
            )
        for pattern, message in credential_patterns:
            if re.search(pattern, text):
                errors.append(f"{relative}: {message}")
        credential_key_re = re.compile(
            r"(?im)^\s*([A-Z0-9_-]*(?:TOKEN|SECRET|PRIVATE[-_]KEY|"
            r"CREDENTIAL|PASSWORD|API[-_]KEY)[A-Z0-9_-]*)\s*:"
        )
        for match in credential_key_re.finditer(text):
            if match.group(1).upper() not in {
                "GITHUB_TOKEN",
                "PERSIST-CREDENTIALS",
            }:
                errors.append(
                    f"{relative}: credential-like YAML key is forbidden"
                )
        if re.search(
            r"(?im)\b(?:export\s+)?[A-Z0-9_]*(?:TOKEN|SECRET|PRIVATE_KEY|"
            r"CREDENTIAL|PASSWORD|API_KEY)[A-Z0-9_]*\s*=",
            text,
        ):
            errors.append(f"{relative}: credential-like shell assignment is forbidden")
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
        if _trusted_sparse_checkout_entries(legacy_text) != [
            EXPECTED_TRUSTED_SPARSE_PATHS
        ]:
            errors.append(
                f"{LEGACY_BOOTSTRAP_WORKFLOW}: trusted sparse checkout must match "
                "approved governance files"
            )
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
        if _trusted_sparse_checkout_entries(required_text) != [
            EXPECTED_TRUSTED_SPARSE_PATHS
        ]:
            errors.append(
                f"{REQUIRED_WORKFLOW}: trusted sparse checkout must match "
                "approved governance files"
            )
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
            "authorize-or-deny:": "guard job must describe denial without claiming reversion",
            "Draft restoration is a manual operator action.": "guard must document manual draft restoration",
            "Validate one-use authorization or record denial": "guard step must describe denial without claiming restoration",
            "ref: ${{ github.event.pull_request.base.sha }}": "guard must checkout base SHA",
            "EVENT_BASE_SHA: ${{ github.event.pull_request.base.sha }}": "guard event must bind base SHA",
            "TRUSTED_GOVERNANCE_SHA: ${{ github.event.pull_request.base.sha }}": "guard must bind executable governance SHA",
            "ref: ${{ github.sha }}": "reconciliation must checkout its triggering default-branch SHA",
            "TRUSTED_GOVERNANCE_SHA: ${{ github.sha }}": "reconciliation must bind its executable governance SHA",
            "Prove reconciliation uses the triggering default-branch SHA": "reconciliation checkout must be verified",
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
        if re.search(
            r"(?i)authorize-or-revert|restore draft state|automatic draft restoration",
            guard_text,
        ):
            errors.append(
                f"{GUARD_WORKFLOW}: workflow must not claim automatic draft restoration"
            )

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
    policy_sha256, protected_files_sha256 = governance_file_bindings(args.root)
    marker = make_authorization_record(
        policy=policy,
        pr_number=args.pr_number,
        base_sha=args.base_sha,
        head_sha=args.head_sha,
        authorizer_actor_id=args.authorizer_actor_id,
        ready_actor_id=args.ready_actor_id,
        now=dt.datetime.now(UTC),
        policy_sha256=policy_sha256,
        protected_files_sha256=protected_files_sha256,
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
    authorize.add_argument("--root", default=".")
    authorize.add_argument("--pr-number", type=int, required=True)
    authorize.add_argument("--base-sha", required=True)
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
