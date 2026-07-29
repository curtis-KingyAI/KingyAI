#!/usr/bin/env python3
"""Execute the trusted, metadata-only KAPI ready-for-review guard."""

from __future__ import annotations

import dataclasses
import datetime as dt
import json
import os
import pathlib
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from typing import Any, Sequence

from kapi_governance import (
    Comment,
    Decision,
    GovernanceError,
    Policy,
    PullRequestEvent,
    PullRequestSnapshot,
    SHA_RE,
    UTC,
    decide_transition,
    governance_file_bindings,
    make_audit_record,
    parse_audit,
    parse_time,
)


class ApiError(RuntimeError):
    """A GitHub API operation failed after bounded retries."""


@dataclasses.dataclass(frozen=True)
class ExecutionResult:
    status: str
    reason: str
    decision: Decision | None
    restoration: str = "not_required"
    errors: tuple[str, ...] = ()


class GitHubApi:
    def __init__(
        self,
        token: str,
        repository: str,
        pr_number: int,
        pr_node_id: str,
        api_url: str = "https://api.github.com",
        attempts: int = 4,
    ) -> None:
        parsed_api_url = urllib.parse.urlsplit(api_url)
        if (
            parsed_api_url.scheme != "https"
            or not parsed_api_url.netloc
            or parsed_api_url.username is not None
            or parsed_api_url.password is not None
            or parsed_api_url.query
            or parsed_api_url.fragment
        ):
            raise GovernanceError("GitHub API URL must be a credential-free HTTPS origin")
        if attempts < 1:
            raise GovernanceError("GitHub API attempts must be positive")
        self.token = token
        self.repository = repository
        self.pr_number = pr_number
        self.pr_node_id = pr_node_id
        self.api_url = api_url.rstrip("/")
        self.attempts = attempts

    def _request(
        self,
        method: str,
        path: str,
        payload: dict[str, Any] | None = None,
        attempts: int | None = None,
    ) -> Any:
        if not path.startswith("/") or path.startswith("//"):
            raise ApiError("GitHub API request path must remain on the configured origin")
        url = f"{self.api_url}{path}"
        data = None if payload is None else json.dumps(payload).encode("utf-8")
        headers = {
            "Accept": "application/vnd.github+json",
            "Authorization": f"Bearer {self.token}",
            "User-Agent": "kingyai-kapi-ready-guard-v1",
            "X-GitHub-Api-Version": "2022-11-28",
        }
        limit = attempts if attempts is not None else self.attempts
        last_error: BaseException | None = None
        for attempt in range(limit):
            request = urllib.request.Request(
                url=url, data=data, headers=headers, method=method
            )
            try:
                with urllib.request.urlopen(request, timeout=20) as response:
                    body = response.read()
                    return json.loads(body) if body else None
            except urllib.error.HTTPError as exc:
                body = exc.read().decode("utf-8", errors="replace")
                last_error = ApiError(f"{method} {url} returned {exc.code}: {body[:500]}")
                if exc.code not in {409, 429, 500, 502, 503, 504}:
                    break
            except (urllib.error.URLError, TimeoutError, OSError) as exc:
                last_error = exc
            if attempt + 1 < limit:
                time.sleep(min(2**attempt, 4))
        raise ApiError(str(last_error or f"{method} {url} failed"))

    def get_pull_request(self) -> PullRequestSnapshot:
        raw = self._request(
            "GET", f"/repos/{self.repository}/pulls/{self.pr_number}"
        )
        return PullRequestSnapshot.from_api(raw)

    def list_open_pull_requests(self) -> list[dict[str, Any]]:
        pull_requests: list[dict[str, Any]] = []
        page = 1
        while True:
            raw = self._request(
                "GET",
                f"/repos/{self.repository}/pulls?state=open&per_page=100&page={page}",
            )
            pull_requests.extend(raw)
            if len(raw) < 100:
                return pull_requests
            page += 1

    def list_timeline(self) -> list[dict[str, Any]]:
        events: list[dict[str, Any]] = []
        page = 1
        while True:
            raw = self._request(
                "GET",
                f"/repos/{self.repository}/issues/{self.pr_number}/timeline"
                f"?per_page=100&page={page}",
            )
            events.extend(raw)
            if len(raw) < 100:
                return events
            page += 1

    def list_comments(self) -> list[Comment]:
        comments: list[Comment] = []
        page = 1
        while True:
            raw = self._request(
                "GET",
                f"/repos/{self.repository}/issues/{self.pr_number}/comments"
                f"?per_page=100&page={page}",
            )
            comments.extend(Comment.from_api(item) for item in raw)
            if len(raw) < 100:
                return comments
            page += 1

    def post_comment(self, body: str) -> None:
        # GitHub issue comments have no idempotency key. Before every retry,
        # look for the exact marker so an acknowledged-but-disconnected POST
        # does not create an avoidable duplicate audit record.
        last_error: BaseException | None = None
        for attempt in range(self.attempts):
            try:
                self._request(
                    "POST",
                    f"/repos/{self.repository}/issues/{self.pr_number}/comments",
                    {"body": body},
                    attempts=1,
                )
                return
            except ApiError as exc:
                last_error = exc
                try:
                    if any(comment.body == body for comment in self.list_comments()):
                        return
                except ApiError:
                    pass
                if attempt + 1 < self.attempts:
                    time.sleep(min(2**attempt, 4))
        raise ApiError(str(last_error or "failed to append audit comment"))

    def add_denied_label(self) -> None:
        self._request(
            "POST",
            f"/repos/{self.repository}/issues/{self.pr_number}/labels",
            {"labels": ["invalid"]},
        )


def _audit_body(action: str, reason: str, marker: str) -> str:
    """Human-readable prose for an audit record.

    Every action is handled explicitly and an unknown one raises. This function
    previously had two branches -- "consumed" and an `else` that said "denied" --
    so when the "permitted" action was added for the operator-reviewed state, a
    *permitted* transition was announced to humans as **denied**, with an
    instruction to restore draft. The machine-readable marker was correct
    throughout; only the prose lied, which is the harder kind of wrong to notice.

    A silent default is what caused that, so there is no default here.
    """
    if action == "consumed":
        summary = "KAPI governance consumed a one-use ready authorization."
        recovery = ""
    elif action == "permitted":
        summary = (
            "KAPI governance **permitted** this ready-for-review transition: "
            f"`{reason}`."
        )
        recovery = (
            "\n\nNo human authorization is required or claimed in the "
            "operator-reviewed activation state, and every integrity check passed: "
            "repository identity, base and head SHA agreement, trusted-governance "
            "SHA, open state, allowed ready actor, policy and protected-file hashes."
            "\n\nNo action is required. The merge boundary is the external required "
            "check bound to the dedicated verifier, not this guard."
        )
    elif action == "denied":
        summary = f"KAPI governance denied ready-for-review transition: `{reason}`."
        recovery = (
            "\n\nAutomatic draft restoration is not an available control. "
            "A human operator must return this pull request to draft if it remains ready."
        )
    else:
        raise GovernanceError(
            f"no audit prose defined for action {action!r}; add a branch rather than "
            "letting it fall through to denial language"
        )
    return f"{summary}{recovery}\n\n{marker}"


def _audit_is_confirmed(
    comments: Sequence[Comment], policy: Policy, decision: Decision
) -> bool:
    for comment in comments:
        try:
            record = parse_audit(comment, policy)
        except GovernanceError:
            continue
        if (
            record is not None
            and record.action == "consumed"
            and record.event_fingerprint == decision.event_fingerprint
            and decision.evidence is not None
            and record.authorization_comment_id == decision.evidence.comment_id
            and record.authorization_hash == decision.evidence.payload_hash
        ):
            return True
    return False


def _denial_audit_is_confirmed(
    comments: Sequence[Comment],
    policy: Policy,
    event: PullRequestEvent,
    reason: str,
) -> bool:
    for comment in comments:
        try:
            record = parse_audit(comment, policy)
        except GovernanceError:
            continue
        if (
            record is not None
            and record.action == "denied"
            and record.event_fingerprint == event.fingerprint
            and record.reason == reason
            and record.base_sha == event.base_sha
            and record.head_sha == event.head_sha
            and record.policy_sha256 == event.policy_sha256
            and record.protected_files_sha256 == event.protected_files_sha256
        ):
            return True
    return False


def _deny_and_record(
    api: Any,
    event: PullRequestEvent,
    snapshot: PullRequestSnapshot | None,
    policy: Policy,
    reason: str,
    now: dt.datetime,
    decision: Decision | None,
) -> ExecutionResult:
    errors: list[str] = []
    restoration = (
        "state_check_required"
        if snapshot is None
        else "already_draft"
        if snapshot.is_draft
        else "manual_operator_required"
    )
    try:
        api.add_denied_label()
    except Exception as exc:
        errors.append(f"add_denied_label: {exc}")
    try:
        if not _denial_audit_is_confirmed(
            api.list_comments(), policy, event, reason
        ):
            marker = make_audit_record("denied", event, reason, now)
            api.post_comment(_audit_body("denied", reason, marker))
            if not _denial_audit_is_confirmed(
                api.list_comments(), policy, event, reason
            ):
                raise ApiError("denial audit was not observable")
    except Exception as exc:
        errors.append(f"append_denied_audit: {exc}")
    if errors:
        return ExecutionResult(
            "operational_failure", reason, decision, restoration, tuple(errors)
        )
    return ExecutionResult("denied", reason, decision, restoration)


def execute_guard(
    api: Any,
    event: PullRequestEvent,
    policy: Policy,
    now: dt.datetime | None = None,
    allow_new_consumption: bool = True,
) -> ExecutionResult:
    now = parse_time(now or dt.datetime.now(UTC))
    snapshot: PullRequestSnapshot | None = None
    try:
        snapshot = api.get_pull_request()
        comments = api.list_comments()
        decision = decide_transition(event, snapshot, comments, policy, now)
    except Exception:
        return _deny_and_record(
            api,
            event,
            snapshot,
            policy,
            "guard_read_failure",
            now,
            None,
        )

    if decision.action == "duplicate":
        return ExecutionResult("authorized_duplicate", decision.reason, decision)

    if decision.action == "permit":
        # Operator-reviewed mode. Nothing is consumed because no authorization
        # exists; the record says exactly that rather than borrowing the vocabulary
        # of a control that is not in force. Idempotent by event fingerprint, so a
        # reconciliation pass over the same ready event will not repost.
        try:
            marker = make_audit_record("permitted", event, decision.reason, now, None)
            api.post_comment(_audit_body("permitted", decision.reason, marker))
            return ExecutionResult("authorized", decision.reason, decision)
        except Exception:
            return _deny_and_record(
                api, event, snapshot, policy, "permit_audit_failure", now, decision
            )

    if decision.action == "consume" and decision.evidence is not None:
        if not allow_new_consumption:
            return _deny_and_record(
                api,
                event,
                snapshot,
                policy,
                "reconciliation_missing_consumed_authorization",
                now,
                decision,
            )
        try:
            marker = make_audit_record(
                "consumed", event, decision.reason, now, decision.evidence
            )
            api.post_comment(_audit_body("consumed", decision.reason, marker))
            if not _audit_is_confirmed(api.list_comments(), policy, decision):
                raise ApiError("consumed authorization audit was not observable")
            return ExecutionResult("authorized", decision.reason, decision)
        except Exception:
            return _deny_and_record(
                api,
                event,
                snapshot,
                policy,
                "authorization_consumption_failed",
                now,
                decision,
            )

    return _deny_and_record(
        api, event, snapshot, policy, decision.reason, now, decision
    )


def _required_env(name: str) -> str:
    value = os.environ.get(name)
    if not value:
        raise GovernanceError(f"missing required environment variable {name}")
    return value


def event_from_environment(policy_path: str) -> PullRequestEvent:
    root = pathlib.Path(policy_path).resolve().parents[2]
    policy_sha256, protected_files_sha256 = governance_file_bindings(root)
    base_sha = _required_env("EVENT_BASE_SHA")
    trusted_governance_sha = _required_env("TRUSTED_GOVERNANCE_SHA")
    head_sha = _required_env("EVENT_HEAD_SHA")
    for label, value in {
        "event base": base_sha,
        "trusted governance": trusted_governance_sha,
        "event head": head_sha,
    }.items():
        if not SHA_RE.fullmatch(value):
            raise GovernanceError(f"{label} SHA must be 40 lowercase hex characters")
    return PullRequestEvent(
        repository_id=int(_required_env("GITHUB_REPOSITORY_ID")),
        pr_number=int(_required_env("PR_NUMBER")),
        pr_node_id=_required_env("PR_NODE_ID"),
        base_sha=base_sha,
        trusted_governance_sha=trusted_governance_sha,
        head_sha=head_sha,
        head_repository_id=int(_required_env("EVENT_HEAD_REPOSITORY_ID")),
        actor_id=int(_required_env("EVENT_ACTOR_ID")),
        actor_login=_required_env("EVENT_ACTOR_LOGIN"),
        event_time=parse_time(_required_env("EVENT_TIME")),
        run_id=int(_required_env("GITHUB_RUN_ID")),
        run_attempt=int(_required_env("GITHUB_RUN_ATTEMPT")),
        policy_sha256=policy_sha256,
        protected_files_sha256=protected_files_sha256,
    )


def main() -> int:
    try:
        policy_path = _required_env("KAPI_GOVERNANCE_POLICY")
        policy = Policy.load(policy_path)
        event = event_from_environment(policy_path)
        api = GitHubApi(
            token=_required_env("GITHUB_TOKEN"),
            repository=_required_env("GITHUB_REPOSITORY"),
            pr_number=event.pr_number,
            pr_node_id=event.pr_node_id,
            api_url=os.environ.get("GITHUB_API_URL", "https://api.github.com"),
        )
        result = execute_guard(api, event, policy)
    except Exception as exc:
        print(f"KAPI ready guard bootstrap failure: {exc}", file=sys.stderr)
        return 2

    print(
        json.dumps(
            {
                "errors": result.errors,
                "reason": result.reason,
                "restoration": result.restoration,
                "status": result.status,
            },
            sort_keys=True,
        )
    )
    if result.status in {"authorized", "authorized_duplicate"}:
        return 0
    if result.status == "denied":
        # A recorded denial remains a visible red workflow run until a human
        # operator restores draft state or the PR is otherwise resolved.
        return 1
    return 2


if __name__ == "__main__":
    raise SystemExit(main())
