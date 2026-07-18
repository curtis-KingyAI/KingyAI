#!/usr/bin/env python3
"""Fail-closed reconciliation for ready PRs whose event workflow was missed."""

from __future__ import annotations

import datetime as dt
import json
import os
import pathlib
import sys
from typing import Any

from kapi_governance import (
    GovernanceError,
    Policy,
    PullRequestEvent,
    SHA_RE,
    UTC,
    governance_file_bindings,
    parse_time,
)
from kapi_ready_guard import GitHubApi, execute_guard


def _required_env(name: str) -> str:
    value = os.environ.get(name)
    if not value:
        raise GovernanceError(f"missing required environment variable {name}")
    return value


def _latest_stage_event(timeline: list[dict[str, Any]]) -> dict[str, Any] | None:
    stage_events: list[tuple[dt.datetime, dict[str, Any]]] = []
    for event in timeline:
        if (
            event.get("event") in {"ready_for_review", "convert_to_draft"}
            and event.get("created_at")
        ):
            stage_events.append((parse_time(event["created_at"]), event))
    if not stage_events:
        return None
    latest_time = max(created_at for created_at, _ in stage_events)
    latest_events = [
        event for created_at, event in stage_events if created_at == latest_time
    ]
    if len({event["event"] for event in latest_events}) != 1:
        raise GovernanceError(
            "timeline has conflicting ready/draft events at the latest timestamp"
        )
    return latest_events[-1]


def _positive_integer(value: Any, label: str) -> int:
    if isinstance(value, bool) or not isinstance(value, int) or value < 1:
        raise GovernanceError(f"{label} must be a positive integer")
    return value


def reconcile_pull_requests(
    *,
    raw_pull_requests: list[dict[str, Any]],
    policy: Policy,
    repository: str,
    repository_id: int,
    token: str,
    api_url: str,
    trusted_governance_sha: str,
    run_id: int,
    run_attempt: int,
    policy_sha256: str,
    protected_files_sha256: str,
    now: dt.datetime | None = None,
    api_factory: Any = GitHubApi,
) -> tuple[list[dict[str, Any]], int]:
    """Reconcile ready PRs from already-fetched, metadata-only API records."""

    results: list[dict[str, Any]] = []
    exit_code = 0
    event_now = parse_time(now or dt.datetime.now(UTC))
    for raw in raw_pull_requests:
        pr_number: int | str = "unknown"
        try:
            if not isinstance(raw, dict):
                raise GovernanceError("pull request record must be an object")
            pr_number = _positive_integer(raw.get("number"), "pull request number")
            draft = raw.get("draft")
            if not isinstance(draft, bool):
                raise GovernanceError("pull request draft state must be boolean")
            if draft:
                continue
            node_id = raw.get("node_id")
            if not isinstance(node_id, str) or not node_id:
                raise GovernanceError("pull request node ID must be non-empty")
            base = raw.get("base")
            head = raw.get("head")
            if not isinstance(base, dict) or not isinstance(head, dict):
                raise GovernanceError("pull request base and head must be objects")
            base_sha = base.get("sha")
            head_sha = head.get("sha")
            if not isinstance(base_sha, str) or not SHA_RE.fullmatch(base_sha):
                raise GovernanceError(
                    "pull request base SHA must be 40 lowercase hex characters"
                )
            if not isinstance(head_sha, str) or not SHA_RE.fullmatch(head_sha):
                raise GovernanceError(
                    "pull request head SHA must be 40 lowercase hex characters"
                )
            head_repository = head.get("repo")
            if not isinstance(head_repository, dict):
                raise GovernanceError("pull request head repository must be an object")
            head_repository_id = _positive_integer(
                head_repository.get("id"), "pull request head repository ID"
            )

            pr_api = api_factory(
                token=token,
                repository=repository,
                pr_number=pr_number,
                pr_node_id=node_id,
                api_url=api_url,
            )
            stage = _latest_stage_event(pr_api.list_timeline())
            if stage is None or stage.get("event") != "ready_for_review":
                actor_id = 0
                actor_login = "unknown"
                event_time = event_now
            else:
                actor = stage.get("actor") or {}
                if not isinstance(actor, dict):
                    raise GovernanceError("ready event actor must be an object")
                actor_id = _positive_integer(actor.get("id"), "ready event actor ID")
                actor_login = actor.get("login")
                if not isinstance(actor_login, str) or not actor_login:
                    raise GovernanceError("ready event actor login must be non-empty")
                event_time = parse_time(stage["created_at"])
            event = PullRequestEvent(
                repository_id=repository_id,
                pr_number=pr_number,
                pr_node_id=node_id,
                base_sha=base_sha,
                trusted_governance_sha=trusted_governance_sha,
                head_sha=head_sha,
                head_repository_id=head_repository_id,
                actor_id=actor_id,
                actor_login=actor_login,
                event_time=event_time,
                run_id=run_id,
                run_attempt=run_attempt,
                policy_sha256=policy_sha256,
                protected_files_sha256=protected_files_sha256,
            )
            result = execute_guard(
                pr_api,
                event,
                policy,
                event_now,
                allow_new_consumption=False,
            )
            results.append(
                {"pr": pr_number, "reason": result.reason, "status": result.status}
            )
            if result.status == "operational_failure":
                exit_code = 2
            elif result.status not in {"authorized", "authorized_duplicate"}:
                exit_code = max(exit_code, 1)
        except Exception as exc:
            results.append(
                {"pr": pr_number, "status": "operational_failure", "error": str(exc)}
            )
            exit_code = 2
    return results, exit_code


def main() -> int:
    try:
        policy_path = _required_env("KAPI_GOVERNANCE_POLICY")
        policy = Policy.load(policy_path)
        root = pathlib.Path(policy_path).resolve().parents[2]
        policy_sha256, protected_files_sha256 = governance_file_bindings(root)
        trusted_governance_sha = _required_env("TRUSTED_GOVERNANCE_SHA")
        if not SHA_RE.fullmatch(trusted_governance_sha):
            raise GovernanceError(
                "trusted governance SHA must be 40 lowercase hex characters"
            )
        repository = _required_env("GITHUB_REPOSITORY")
        repository_id = int(_required_env("GITHUB_REPOSITORY_ID"))
        token = _required_env("GITHUB_TOKEN")
        run_id = int(_required_env("GITHUB_RUN_ID"))
        run_attempt = int(_required_env("GITHUB_RUN_ATTEMPT"))
        api_url = os.environ.get("GITHUB_API_URL", "https://api.github.com")
        repository_api = GitHubApi(
            token=token,
            repository=repository,
            pr_number=0,
            pr_node_id="unused",
            api_url=api_url,
        )
        raw_pull_requests = repository_api.list_open_pull_requests()
    except Exception as exc:
        print(f"KAPI ready reconciliation bootstrap failure: {exc}", file=sys.stderr)
        return 2

    results, exit_code = reconcile_pull_requests(
        raw_pull_requests=raw_pull_requests,
        policy=policy,
        repository=repository,
        repository_id=repository_id,
        token=token,
        api_url=api_url,
        trusted_governance_sha=trusted_governance_sha,
        run_id=run_id,
        run_attempt=run_attempt,
        policy_sha256=policy_sha256,
        protected_files_sha256=protected_files_sha256,
    )

    print(json.dumps(results, sort_keys=True))
    return exit_code


if __name__ == "__main__":
    raise SystemExit(main())
