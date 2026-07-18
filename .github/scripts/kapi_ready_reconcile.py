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
    stage_events = [
        event
        for event in timeline
        if event.get("event") in {"ready_for_review", "convert_to_draft"}
        and event.get("created_at")
    ]
    if not stage_events:
        return None
    return max(stage_events, key=lambda event: parse_time(event["created_at"]))


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

    results: list[dict[str, Any]] = []
    exit_code = 0
    now = dt.datetime.now(UTC)
    for raw in raw_pull_requests:
        if bool(raw.get("draft")):
            continue
        pr_number = int(raw["number"])
        pr_api = GitHubApi(
            token=token,
            repository=repository,
            pr_number=pr_number,
            pr_node_id=str(raw["node_id"]),
            api_url=api_url,
        )
        try:
            stage = _latest_stage_event(pr_api.list_timeline())
            if stage is None or stage.get("event") != "ready_for_review":
                actor_id = 0
                actor_login = "unknown"
                event_time = now
            else:
                actor = stage.get("actor") or {}
                actor_id = int(actor.get("id") or 0)
                actor_login = str(actor.get("login") or "unknown")
                event_time = parse_time(stage["created_at"])
            event = PullRequestEvent(
                repository_id=repository_id,
                pr_number=pr_number,
                pr_node_id=str(raw["node_id"]),
                base_sha=str(raw["base"]["sha"]),
                trusted_governance_sha=trusted_governance_sha,
                head_sha=str(raw["head"]["sha"]),
                head_repository_id=int((raw["head"].get("repo") or {}).get("id") or 0),
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
                now,
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
            results.append({"pr": pr_number, "status": "operational_failure", "error": str(exc)})
            exit_code = 2

    print(json.dumps(results, sort_keys=True))
    return exit_code


if __name__ == "__main__":
    raise SystemExit(main())
