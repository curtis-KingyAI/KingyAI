from __future__ import annotations

import dataclasses
import datetime as dt
import json
import os
import pathlib
import shutil
import sys
import tempfile
import unittest
from unittest import mock


ROOT = pathlib.Path(__file__).resolve().parents[2]
SCRIPTS = ROOT / ".github" / "scripts"
sys.path.insert(0, str(SCRIPTS))

from kapi_governance import (  # noqa: E402
    AUTH_MARKER,
    AUTH_SCHEMA,
    EXECUTABLE_GOVERNANCE_PATHS,
    NORMATIVE_GOVERNANCE_PATHS,
    VALIDATED_GOVERNANCE_PATHS,
    Comment,
    GovernanceError,
    Policy,
    PROTECTED_GOVERNANCE_PATHS,
    PullRequestEvent,
    PullRequestSnapshot,
    UTC,
    format_time,
    governance_file_bindings,
    make_audit_record,
    make_authorization_record,
    make_marker,
    parse_authorization,
    scan_workflows,
    validate_production_check_source,
)
from kapi_ready_guard import (  # noqa: E402
    ApiError,
    GitHubApi,
    event_from_environment,
    execute_guard,
)
from kapi_ready_reconcile import (  # noqa: E402
    _latest_stage_event,
    reconcile_pull_requests,
)


HEAD_SHA = "a" * 40
STALE_SHA = "b" * 40
BASE_SHA = "c" * 40
STALE_BASE_SHA = "d" * 40
BASE_REPOSITORY_ID = 1043002851
FORK_REPOSITORY_ID = 999000111
OPERATOR_ID = 227671038
HUMAN_AUTHORIZER_ID = 555000111
AUDIT_ID = 41898282


class FakeApi:
    def __init__(
        self,
        snapshot: PullRequestSnapshot,
        comments: list[Comment],
        policy: Policy,
        now: dt.datetime,
        failures: dict[str, int] | None = None,
        timeline: list[dict[str, object]] | None = None,
    ) -> None:
        self.snapshot = snapshot
        self.comments = list(comments)
        self.policy = policy
        self.now = now
        self.failures = dict(failures or {})
        self.timeline = list(timeline or [])
        self.calls: list[str] = []
        self.next_comment_id = max((comment.id for comment in comments), default=100) + 1

    def _maybe_fail(self, name: str) -> None:
        remaining = self.failures.get(name, 0)
        if remaining > 0:
            self.failures[name] = remaining - 1
            raise ApiError(f"synthetic {name} failure")

    def get_pull_request(self) -> PullRequestSnapshot:
        self.calls.append("get_pull_request")
        self._maybe_fail("get_pull_request")
        return self.snapshot

    def list_comments(self) -> list[Comment]:
        self.calls.append("list_comments")
        self._maybe_fail("list_comments")
        return list(self.comments)

    def list_timeline(self) -> list[dict[str, object]]:
        self.calls.append("list_timeline")
        self._maybe_fail("list_timeline")
        return list(self.timeline)

    def post_comment(self, body: str) -> None:
        self.calls.append("post_comment")
        self._maybe_fail("post_comment")
        self.comments.append(
            Comment(
                id=self.next_comment_id,
                author_id=self.policy.audit_actor_id,
                author_login=self.policy.audit_actor_login,
                author_type="Bot",
                body=body,
                created_at=self.now,
                updated_at=self.now,
            )
        )
        self.next_comment_id += 1

    def add_denied_label(self) -> None:
        self.calls.append("add_denied_label")
        self._maybe_fail("add_denied_label")


class GovernanceFixture(unittest.TestCase):
    def setUp(self) -> None:
        self.live_policy = Policy.load(
            ROOT / ".github" / "kapi-governance" / "policy-v1.json"
        )
        self.policy = dataclasses.replace(
            self.live_policy,
            activation_state="active",
            human_authorizer_actor_ids=frozenset({HUMAN_AUTHORIZER_ID}),
            credential_separation_attested=True,
            dedicated_verifier_attested=True,
            dedicated_verifier_integration_id=987654321,
        )
        self.now = dt.datetime(2026, 7, 13, 12, 0, 0, tzinfo=UTC)
        self.policy_sha256, self.protected_files_sha256 = governance_file_bindings(
            ROOT
        )

    def event(
        self,
        *,
        actor_id: int = OPERATOR_ID,
        event_time: dt.datetime | None = None,
        base_sha: str = BASE_SHA,
        trusted_governance_sha: str | None = None,
        head_sha: str = HEAD_SHA,
        head_repository_id: int = BASE_REPOSITORY_ID,
        run_id: int = 700,
        policy_sha256: str | None = None,
        protected_files_sha256: str | None = None,
    ) -> PullRequestEvent:
        return PullRequestEvent(
            repository_id=BASE_REPOSITORY_ID,
            pr_number=4,
            pr_node_id="PR_kwTEST",
            base_sha=base_sha,
            trusted_governance_sha=trusted_governance_sha or base_sha,
            head_sha=head_sha,
            head_repository_id=head_repository_id,
            actor_id=actor_id,
            actor_login="operator" if actor_id == OPERATOR_ID else "fork-author",
            event_time=event_time or self.now,
            run_id=run_id,
            run_attempt=1,
            policy_sha256=policy_sha256 or self.policy_sha256,
            protected_files_sha256=(
                protected_files_sha256 or self.protected_files_sha256
            ),
        )

    def snapshot(
        self,
        *,
        base_sha: str = BASE_SHA,
        head_sha: str = HEAD_SHA,
        head_repository_id: int = BASE_REPOSITORY_ID,
    ) -> PullRequestSnapshot:
        return PullRequestSnapshot(
            base_sha=base_sha,
            head_sha=head_sha,
            head_repository_id=head_repository_id,
            base_repository_id=BASE_REPOSITORY_ID,
            base_ref="main",
            state="open",
            is_draft=False,
        )

    def authorization(
        self,
        *,
        base_sha: str = BASE_SHA,
        head_sha: str = HEAD_SHA,
        author_id: int = HUMAN_AUTHORIZER_ID,
        created_at: dt.datetime | None = None,
        nonce: str = "1" * 32,
        edited: bool = False,
        policy_sha256: str | None = None,
        protected_files_sha256: str | None = None,
    ) -> Comment:
        created_at = created_at or self.now - dt.timedelta(seconds=60)
        marker = make_authorization_record(
            policy=self.policy,
            pr_number=4,
            base_sha=base_sha,
            head_sha=head_sha,
            authorizer_actor_id=HUMAN_AUTHORIZER_ID,
            ready_actor_id=OPERATOR_ID,
            now=created_at,
            policy_sha256=policy_sha256 or self.policy_sha256,
            protected_files_sha256=(
                protected_files_sha256 or self.protected_files_sha256
            ),
            nonce=nonce,
        )
        return Comment(
            id=101,
            author_id=author_id,
            author_login="named-human-authorizer",
            author_type="User",
            body=marker,
            created_at=created_at,
            updated_at=(created_at + dt.timedelta(seconds=1) if edited else created_at),
        )

    def consumed_comment(
        self,
        authorization: Comment,
        event: PullRequestEvent,
    ) -> Comment:
        evidence = parse_authorization(authorization)
        assert evidence is not None
        marker = make_audit_record(
            "consumed",
            event,
            "valid_one_use_authorization",
            self.now,
            evidence,
        )
        return Comment(
            id=202,
            author_id=AUDIT_ID,
            author_login="github-actions[bot]",
            author_type="Bot",
            body=f"Consumed.\n\n{marker}",
            created_at=self.now,
            updated_at=self.now,
        )


class ReadyGuardTests(GovernanceFixture):
    def test_live_policy_refuses_activation_without_separate_human_authorizer(self) -> None:
        api = FakeApi(self.snapshot(), [], self.live_policy, self.now)
        result = execute_guard(api, self.event(), self.live_policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("governance_policy_not_activated", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_manifest_validation_precedes_denial_audit_and_manual_recovery(self) -> None:
        environment = {
            "EVENT_ACTOR_ID": str(OPERATOR_ID),
            "EVENT_ACTOR_LOGIN": "curtis-KingyAI",
            "EVENT_BASE_SHA": BASE_SHA,
            "EVENT_HEAD_REPOSITORY_ID": str(BASE_REPOSITORY_ID),
            "EVENT_HEAD_SHA": HEAD_SHA,
            "EVENT_TIME": "2026-07-13T12:00:00Z",
            "GITHUB_REPOSITORY_ID": str(BASE_REPOSITORY_ID),
            "GITHUB_RUN_ATTEMPT": "1",
            "GITHUB_RUN_ID": "700",
            "PR_NODE_ID": "PR_kwTEST",
            "PR_NUMBER": "4",
            "TRUSTED_GOVERNANCE_SHA": BASE_SHA,
        }
        with mock.patch.dict(os.environ, environment, clear=True):
            event = event_from_environment(
                str(ROOT / ".github" / "kapi-governance" / "policy-v1.json")
            )

        api = FakeApi(self.snapshot(), [], self.live_policy, self.now)
        result = execute_guard(api, event, self.live_policy, self.now)

        self.assertEqual("denied", result.status)
        self.assertEqual("governance_policy_not_activated", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)
        self.assertIn("add_denied_label", api.calls)
        self.assertIn("post_comment", api.calls)
        self.assertIn(
            "A human operator must return this pull request to draft",
            api.comments[-1].body,
        )

    def test_unauthorized_actor_is_labeled_audited_and_denied(self) -> None:
        event = self.event(actor_id=87654321)
        api = FakeApi(self.snapshot(), [], self.policy, self.now)
        result = execute_guard(api, event, self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("event_actor_not_allowed", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertIn("add_denied_label", api.calls)
        self.assertFalse(api.snapshot.is_draft)
        self.assertIn("post_comment", api.calls)

    def test_owner_identity_without_authorization_is_not_enough(self) -> None:
        api = FakeApi(self.snapshot(), [], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("missing_authorization", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_agent_through_owner_cannot_forge_human_authorization(self) -> None:
        created_at = self.now - dt.timedelta(seconds=60)
        forged = make_marker(
            AUTH_MARKER,
            {
                "authorizer_actor_id": OPERATOR_ID,
                "base_sha": BASE_SHA,
                "expires_at": format_time(created_at + dt.timedelta(seconds=300)),
                "head_sha": HEAD_SHA,
                "nonce": "9" * 32,
                "policy_sha256": self.policy_sha256,
                "pr_number": 4,
                "protected_files_sha256": self.protected_files_sha256,
                "ready_actor_id": OPERATOR_ID,
                "repository_id": BASE_REPOSITORY_ID,
                "schema": AUTH_SCHEMA,
            },
        )
        comment = Comment(
            id=102,
            author_id=OPERATOR_ID,
            author_login="curtis-KingyAI",
            author_type="User",
            body=forged,
            created_at=created_at,
            updated_at=created_at,
        )
        api = FakeApi(self.snapshot(), [comment], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_authorizer_not_allowed", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_valid_one_use_human_authorization_is_consumed(self) -> None:
        authorization = self.authorization()
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("authorized", result.status)
        self.assertEqual("not_required", result.restoration)
        self.assertEqual(2, len(api.comments))
        self.assertIn("kapi-ready-audit-v1", api.comments[-1].body)
        self.assertIn('"action":"consumed"', api.comments[-1].body)

    def test_missed_event_reconciliation_requires_prior_consumption(self) -> None:
        authorization = self.authorization()
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(
            api,
            self.event(),
            self.policy,
            self.now,
            allow_new_consumption=False,
        )
        self.assertEqual("denied", result.status)
        self.assertEqual(
            "reconciliation_missing_consumed_authorization", result.reason
        )
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_fork_author_cannot_use_operator_authorization(self) -> None:
        authorization = self.authorization()
        event = self.event(
            actor_id=87654321,
            head_repository_id=FORK_REPOSITORY_ID,
        )
        api = FakeApi(
            self.snapshot(head_repository_id=FORK_REPOSITORY_ID),
            [authorization],
            self.policy,
            self.now,
        )
        result = execute_guard(api, event, self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("event_actor_not_allowed", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_stale_authorization_head_sha_is_rejected(self) -> None:
        authorization = self.authorization(head_sha=STALE_SHA)
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_stale_head_sha", result.reason)

    def test_stale_authorization_base_sha_is_rejected(self) -> None:
        authorization = self.authorization(base_sha=STALE_BASE_SHA)
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_stale_base_sha", result.reason)

    def test_current_base_change_is_rejected_before_authorization(self) -> None:
        authorization = self.authorization()
        api = FakeApi(
            self.snapshot(base_sha=STALE_BASE_SHA),
            [authorization],
            self.policy,
            self.now,
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("current_base_sha_mismatch", result.reason)

    def test_executable_governance_sha_must_match_base(self) -> None:
        authorization = self.authorization()
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(
            api,
            self.event(trusted_governance_sha=STALE_BASE_SHA),
            self.policy,
            self.now,
        )
        self.assertEqual("denied", result.status)
        self.assertEqual("trusted_governance_sha_mismatch", result.reason)

    def test_authorization_policy_hash_mismatch_is_rejected(self) -> None:
        authorization = self.authorization(policy_sha256="e" * 64)
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_policy_hash_mismatch", result.reason)

    def test_authorization_protected_manifest_mismatch_is_rejected(self) -> None:
        authorization = self.authorization(protected_files_sha256="f" * 64)
        api = FakeApi(self.snapshot(), [authorization], self.policy, self.now)
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual(
            "authorization_protected_files_hash_mismatch", result.reason
        )

    def test_current_head_change_is_rejected_before_authorization(self) -> None:
        authorization = self.authorization()
        api = FakeApi(
            self.snapshot(head_sha=STALE_SHA), [authorization], self.policy, self.now
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("current_head_sha_mismatch", result.reason)

    def test_replay_for_new_ready_event_is_rejected(self) -> None:
        authorization = self.authorization()
        prior_event = self.event(event_time=self.now - dt.timedelta(seconds=10), run_id=699)
        consumed = self.consumed_comment(authorization, prior_event)
        api = FakeApi(
            self.snapshot(), [authorization, consumed], self.policy, self.now
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_replay", result.reason)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertFalse(api.snapshot.is_draft)

    def test_duplicate_delivery_is_idempotently_authorized(self) -> None:
        authorization = self.authorization()
        event = self.event()
        consumed = self.consumed_comment(authorization, event)
        api = FakeApi(
            self.snapshot(), [authorization, consumed], self.policy, self.now
        )
        result = execute_guard(api, event, self.policy, self.now)
        self.assertEqual("authorized_duplicate", result.status)
        self.assertEqual("not_required", result.restoration)
        self.assertEqual(2, len(api.comments))

    def test_label_failure_keeps_audit_visible_and_fails_operationally(self) -> None:
        api = FakeApi(
            self.snapshot(),
            [],
            self.policy,
            self.now,
            failures={"add_denied_label": 1},
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("operational_failure", result.status)
        self.assertEqual("manual_operator_required", result.restoration)
        self.assertIn("add_denied_label", " ".join(result.errors))
        self.assertIn("post_comment", api.calls)

    def test_duplicate_denial_delivery_does_not_duplicate_audit(self) -> None:
        event = self.event(actor_id=87654321)
        api = FakeApi(self.snapshot(), [], self.policy, self.now)

        first = execute_guard(api, event, self.policy, self.now)
        second = execute_guard(api, event, self.policy, self.now)

        self.assertEqual("denied", first.status)
        self.assertEqual("denied", second.status)
        self.assertEqual(1, len(api.comments))
        self.assertEqual(1, api.calls.count("post_comment"))

    def test_already_draft_state_does_not_claim_automatic_restoration(self) -> None:
        api = FakeApi(
            dataclasses.replace(self.snapshot(), is_draft=True),
            [],
            self.policy,
            self.now,
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("already_draft", result.restoration)

    def test_edited_authorization_is_rejected(self) -> None:
        api = FakeApi(
            self.snapshot(), [self.authorization(edited=True)], self.policy, self.now
        )
        result = execute_guard(api, self.event(), self.policy, self.now)
        self.assertEqual("denied", result.status)
        self.assertEqual("authorization_was_edited", result.reason)


class GitHubApiSafetyTests(unittest.TestCase):
    def _api(self, **overrides: object) -> GitHubApi:
        values: dict[str, object] = {
            "token": "synthetic-token-not-a-credential",
            "repository": "owner/repository",
            "pr_number": 4,
            "pr_node_id": "PR_kwTEST",
            "attempts": 2,
        }
        values.update(overrides)
        return GitHubApi(**values)  # type: ignore[arg-type]

    def test_api_origin_must_be_credential_free_https(self) -> None:
        rejected = (
            "http://api.github.com",
            "https://user@example.invalid",
            "https://api.github.com?redirect=example.invalid",
        )
        for api_url in rejected:
            with self.subTest(api_url=api_url):
                with self.assertRaises(GovernanceError):
                    self._api(api_url=api_url)
        with self.assertRaises(GovernanceError):
            self._api(attempts=0)

    def test_request_path_cannot_escape_configured_origin(self) -> None:
        api = self._api()
        for path in ("https://example.invalid/token-capture", "//example.invalid/path"):
            with self.subTest(path=path):
                with mock.patch("kapi_ready_guard.urllib.request.urlopen") as urlopen:
                    with self.assertRaises(ApiError):
                        api._request("GET", path)
                    urlopen.assert_not_called()

    def test_comment_retry_is_idempotent_after_disconnected_post(self) -> None:
        api = self._api()
        body = "synthetic audit marker"
        existing = Comment(
            id=1,
            author_id=AUDIT_ID,
            author_login="github-actions[bot]",
            author_type="Bot",
            body=body,
            created_at=dt.datetime(2026, 7, 13, tzinfo=UTC),
            updated_at=dt.datetime(2026, 7, 13, tzinfo=UTC),
        )
        with mock.patch.object(
            api, "_request", side_effect=ApiError("synthetic disconnect")
        ) as request:
            with mock.patch.object(api, "list_comments", return_value=[existing]):
                api.post_comment(body)
        self.assertEqual(1, request.call_count)

    def test_guard_has_no_automatic_draft_mutation_path(self) -> None:
        source = (
            ROOT / ".github" / "scripts" / "kapi_ready_guard.py"
        ).read_text(encoding="utf-8")
        self.assertNotIn("convertPullRequestToDraft", source)
        self.assertNotIn('"/graphql"', source)
        self.assertIn('{"labels": ["invalid"]}', source)


class ReadyReconciliationTests(GovernanceFixture):
    def raw_pull_request(
        self,
        *,
        draft: bool = False,
        base_sha: str = BASE_SHA,
        head_sha: str = HEAD_SHA,
        head_repository_id: int = BASE_REPOSITORY_ID,
    ) -> dict[str, object]:
        return {
            "number": 4,
            "node_id": "PR_kwTEST",
            "draft": draft,
            "base": {"sha": base_sha},
            "head": {
                "sha": head_sha,
                "repo": {"id": head_repository_id},
            },
        }

    def reconcile(
        self,
        raw_pull_requests: list[dict[str, object]],
        api: FakeApi,
    ) -> tuple[list[dict[str, object]], int]:
        return reconcile_pull_requests(
            raw_pull_requests=raw_pull_requests,  # type: ignore[arg-type]
            policy=self.policy,
            repository="owner/repository",
            repository_id=BASE_REPOSITORY_ID,
            token="synthetic-token-not-a-credential",
            api_url="https://api.github.com",
            trusted_governance_sha=BASE_SHA,
            run_id=700,
            run_attempt=1,
            policy_sha256=self.policy_sha256,
            protected_files_sha256=self.protected_files_sha256,
            now=self.now,
            api_factory=lambda **_kwargs: api,
        )

    def test_latest_timeline_stage_is_selected(self) -> None:
        latest = {
            "event": "ready_for_review",
            "created_at": "2026-07-13T12:00:00Z",
        }
        timeline = [
            {
                "event": "convert_to_draft",
                "created_at": "2026-07-13T11:59:00Z",
            },
            latest,
        ]
        self.assertIs(latest, _latest_stage_event(timeline))

    def test_conflicting_latest_timeline_stages_fail_closed(self) -> None:
        timeline = [
            {
                "event": "ready_for_review",
                "created_at": "2026-07-13T12:00:00Z",
            },
            {
                "event": "convert_to_draft",
                "created_at": "2026-07-13T12:00:00Z",
            },
        ]
        with self.assertRaises(GovernanceError):
            _latest_stage_event(timeline)

    def test_draft_pull_request_is_not_reconciled(self) -> None:
        api = FakeApi(self.snapshot(), [], self.policy, self.now)
        results, exit_code = self.reconcile(
            [self.raw_pull_request(draft=True)], api
        )
        self.assertEqual([], results)
        self.assertEqual(0, exit_code)
        self.assertEqual([], api.calls)

    def test_malformed_pull_request_sha_fails_before_api_use(self) -> None:
        api = FakeApi(self.snapshot(), [], self.policy, self.now)
        results, exit_code = self.reconcile(
            [self.raw_pull_request(head_sha="not-a-sha")], api
        )
        self.assertEqual(2, exit_code)
        self.assertEqual("operational_failure", results[0]["status"])
        self.assertEqual([], api.calls)

    def test_ready_pr_without_prior_consumption_requires_manual_restoration(self) -> None:
        timeline = [
            {
                "event": "ready_for_review",
                "created_at": format_time(self.now),
                "actor": {"id": OPERATOR_ID, "login": "operator"},
            }
        ]
        api = FakeApi(
            self.snapshot(),
            [self.authorization()],
            self.policy,
            self.now,
            timeline=timeline,
        )
        results, exit_code = self.reconcile([self.raw_pull_request()], api)
        self.assertEqual(1, exit_code)
        self.assertEqual("denied", results[0]["status"])
        self.assertEqual(
            "reconciliation_missing_consumed_authorization", results[0]["reason"]
        )
        self.assertEqual("manual_operator_required", results[0]["restoration"])
        self.assertFalse(api.snapshot.is_draft)
        self.assertIn("add_denied_label", api.calls)
        self.assertIn("post_comment", api.calls)

    def test_duplicate_consumed_event_remains_authorized(self) -> None:
        event = self.event()
        authorization = self.authorization()
        timeline = [
            {
                "event": "ready_for_review",
                "created_at": format_time(self.now),
                "actor": {"id": OPERATOR_ID, "login": "operator"},
            }
        ]
        api = FakeApi(
            self.snapshot(),
            [authorization, self.consumed_comment(authorization, event)],
            self.policy,
            self.now,
            timeline=timeline,
        )
        results, exit_code = self.reconcile([self.raw_pull_request()], api)
        self.assertEqual(0, exit_code)
        self.assertEqual("authorized_duplicate", results[0]["status"])
        self.assertEqual("not_required", results[0]["restoration"])

    def test_timeline_read_failure_is_visible_and_fail_closed(self) -> None:
        api = FakeApi(
            self.snapshot(),
            [],
            self.policy,
            self.now,
            failures={"list_timeline": 1},
        )
        results, exit_code = self.reconcile([self.raw_pull_request()], api)
        self.assertEqual(2, exit_code)
        self.assertEqual("operational_failure", results[0]["status"])


class WorkflowScannerTests(unittest.TestCase):
    def _copy_workflow(self, root: pathlib.Path, name: str) -> None:
        target = root / ".github" / "workflows"
        target.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / ".github" / "workflows" / name, target / name)

    def _copy_github(self, root: pathlib.Path) -> None:
        shutil.copytree(ROOT / ".github", root / ".github")

    def test_current_workflows_pass(self) -> None:
        self.assertEqual([], scan_workflows(ROOT))

    def test_ready_guard_uses_complete_trusted_checkouts(self) -> None:
        workflow = (
            ROOT / ".github" / "workflows" / "ready-for-review-guard.yml"
        ).read_text(encoding="utf-8")

        self.assertEqual(2, workflow.count("uses: actions/checkout@"))
        self.assertNotIn("sparse-checkout:", workflow)
        self.assertNotIn("path: .kapi-candidate", workflow)
        self.assertNotRegex(
            workflow,
            r"ref:\s*\$\{\{\s*github\.event\.pull_request\.head",
        )
        for relative in PROTECTED_GOVERNANCE_PATHS:
            self.assertTrue((ROOT / relative).is_file(), str(relative))
        governance_file_bindings(ROOT)

    def test_ready_guard_workflow_cannot_claim_automatic_draft_restoration(self) -> None:
        variants = (
            ("authorize-or-deny:", "authorize-or-revert:"),
            (
                "Draft restoration is a manual operator action.",
                "Automatic draft restoration is enforced.",
            ),
            (
                "Validate one-use authorization or record denial",
                "Validate one-use authorization or restore draft state",
            ),
        )
        for current, stale in variants:
            with self.subTest(stale=stale):
                with tempfile.TemporaryDirectory() as temporary:
                    root = pathlib.Path(temporary)
                    self._copy_github(root)
                    workflow = (
                        root
                        / ".github"
                        / "workflows"
                        / "ready-for-review-guard.yml"
                    )
                    workflow.write_text(
                        workflow.read_text(encoding="utf-8").replace(
                            current, stale, 1
                        ),
                        encoding="utf-8",
                    )
                    errors = scan_workflows(root)
                self.assertTrue(
                    any(
                        "must not claim automatic draft restoration" in error
                        for error in errors
                    ),
                    errors,
                )

    def test_missing_protected_file_fails_manifest_validation_closed(self) -> None:
        missing = pathlib.PurePosixPath(".github/tests/test_kapi_governance.py")
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            for relative in PROTECTED_GOVERNANCE_PATHS:
                target = root / relative
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(ROOT / relative, target)
            (root / missing).unlink()

            with self.assertRaises(GovernanceError) as raised:
                governance_file_bindings(root)

        self.assertEqual(
            f"missing protected governance file {missing}", str(raised.exception)
        )

    def test_live_policy_pins_operator_reviewed_positioning(self) -> None:
        policy = Policy.load(
            ROOT / ".github" / "kapi-governance" / "policy-v1.json"
        )
        self.assertEqual("Operator-reviewed", policy.review_positioning)
        self.assertEqual("manual_operator", policy.draft_restoration_mode)
        self.assertFalse(policy.stronger_review_claims_allowed)

    def test_policy_rejects_unattested_automatic_draft_restoration(self) -> None:
        source = ROOT / ".github" / "kapi-governance" / "policy-v1.json"
        with tempfile.TemporaryDirectory() as temporary:
            policy = pathlib.Path(temporary) / "policy.json"
            policy.write_text(
                source.read_text(encoding="utf-8").replace(
                    '"draft_restoration_mode": "manual_operator"',
                    '"draft_restoration_mode": "automatic"',
                    1,
                ),
                encoding="utf-8",
            )
            with self.assertRaisesRegex(
                GovernanceError, "draft restoration mode must remain manual_operator"
            ):
                Policy.load(policy)

    def test_readme_documents_manual_recovery_and_external_merge_gate(self) -> None:
        readme = (
            ROOT / ".github" / "kapi-governance" / "README.md"
        ).read_text(encoding="utf-8")
        self.assertIn("`draft_restoration_mode` is pinned to `manual_operator`", readme)
        self.assertIn("A human operator must restore draft state", readme)
        self.assertIn("external required check", readme)
        self.assertNotIn("cause draft restoration", readme)
        self.assertNotIn("returned to draft", readme)

    def test_policy_rejects_stronger_review_positioning(self) -> None:
        source = ROOT / ".github" / "kapi-governance" / "policy-v1.json"
        variants = (
            ('"review_positioning": "Operator-reviewed"', '"review_positioning": "Autonomous"'),
            ('"stronger_review_claims_allowed": false', '"stronger_review_claims_allowed": true'),
        )
        for original, replacement in variants:
            with self.subTest(replacement=replacement):
                with tempfile.TemporaryDirectory() as temporary:
                    policy = pathlib.Path(temporary) / "policy.json"
                    policy.write_text(
                        source.read_text(encoding="utf-8").replace(
                            original, replacement, 1
                        ),
                        encoding="utf-8",
                    )
                    with self.assertRaises(GovernanceError):
                        Policy.load(policy)

    def test_missing_required_check_is_detected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_workflow(root, "ready-for-review-guard.yml")
            errors = scan_workflows(root)
        self.assertTrue(any("missing always-emitted advisory workflow" in error for error in errors))
        self.assertTrue(any("reserved Actions advisory check name" in error for error in errors))

    def test_check_name_collision_or_spoof_is_detected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_workflow(root, "ready-for-review-guard.yml")
            self._copy_workflow(root, "kapi-governance-actions-advisory-v1.yml")
            spoof = root / ".github" / "workflows" / "spoof.yml"
            spoof.write_text(
                'name: spoof\n"on":\n  pull_request:\npermissions:\n  contents: read\n'
                'jobs:\n  spoof:\n    name: kapi-governance-actions-advisory-v1\n'
                '    runs-on: ubuntu-latest\n    steps:\n      - run: true\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("reserved Actions advisory check name" in error for error in errors))

    def test_legacy_verify_check_collision_is_detected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            shutil.copytree(ROOT / ".github", root / ".github")
            spoof = root / ".github" / "workflows" / "legacy-verify-spoof.yml"
            spoof.write_text(
                'name: spoof\n"on":\n  pull_request:\npermissions:\n'
                '  contents: read\njobs:\n  spoof:\n    name: verify\n'
                '    runs-on: ubuntu-latest\n    steps:\n      - run: true\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("legacy verify check name" in error for error in errors))

    def test_legacy_verify_cannot_execute_candidate_code(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            shutil.copytree(ROOT / ".github", root / ".github")
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8")
                + "\n      - run: python3 .kapi-candidate/.github/scripts/kapi_governance.py\n",
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("candidate code must never be executed" in error for error in errors))

    def test_implicit_default_token_permissions_are_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            shutil.copytree(ROOT / ".github", root / ".github")
            implicit = root / ".github" / "workflows" / "implicit-token.yml"
            implicit.write_text(
                'name: implicit\n"on":\n  pull_request:\njobs:\n  inspect:\n'
                '    runs-on: ubuntu-latest\n    steps:\n      - run: true\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("top-level token permissions" in error for error in errors))

    def test_actions_source_cannot_spoof_production_verifier(self) -> None:
        policy = dataclasses.replace(
            Policy.load(ROOT / ".github" / "kapi-governance" / "policy-v1.json"),
            activation_state="active",
            human_authorizer_actor_ids=frozenset({HUMAN_AUTHORIZER_ID}),
            credential_separation_attested=True,
            dedicated_verifier_attested=True,
            dedicated_verifier_integration_id=987654321,
        )
        errors = validate_production_check_source(
            policy, policy.production_required_check_name, 15368
        )
        self.assertTrue(any("GitHub Actions App" in error for error in errors))
        self.assertEqual(
            [],
            validate_production_check_source(
                policy,
                policy.production_required_check_name,
                policy.dedicated_verifier_integration_id or 0,
            ),
        )

    def test_actions_job_cannot_claim_external_verifier_check_name(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            (root / ".github").mkdir(parents=True)
            shutil.copytree(ROOT / ".github" / "workflows", root / ".github" / "workflows")
            shutil.copytree(
                ROOT / ".github" / "kapi-governance",
                root / ".github" / "kapi-governance",
            )
            spoof = root / ".github" / "workflows" / "external-spoof.yml"
            spoof.write_text(
                'name: external-spoof\n"on":\n  pull_request:\npermissions:\n'
                '  contents: read\njobs:\n  spoof:\n'
                '    name: kapi-governance-external-v1\n    runs-on: ubuntu-latest\n'
                '    steps:\n      - run: true\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("reserved to the dedicated external verifier" in error for error in errors))

    def test_trusted_scanner_rejects_candidate_executable_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            trusted = root / "trusted"
            candidate = root / "candidate"
            shutil.copytree(ROOT / ".github", trusted / ".github")
            shutil.copytree(ROOT / ".github", candidate / ".github")
            candidate_script = candidate / ".github" / "scripts" / "kapi_ready_guard.py"
            candidate_script.write_text(
                candidate_script.read_text(encoding="utf-8") + "\n# unauthorized candidate change\n",
                encoding="utf-8",
            )
            errors = scan_workflows(candidate, trusted)
        self.assertTrue(any("candidate changes protected governance file" in error for error in errors))

    def test_unauthorized_workflow_write_and_ready_pattern_are_detected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_workflow(root, "ready-for-review-guard.yml")
            self._copy_workflow(root, "kapi-governance-actions-advisory-v1.yml")
            bad = root / ".github" / "workflows" / "bad.yml"
            bad.write_text(
                'name: bad\n"on":\n  workflow_dispatch:\npermissions:\n'
                '  pull-requests: write\njobs:\n  bad:\n    runs-on: ubuntu-latest\n'
                '    steps:\n      - run: gh pr ready 4\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("unauthorized pull-requests: write" in error for error in errors))
        self.assertTrue(any("may not mark a PR ready" in error for error in errors))

    def test_unexpected_workflow_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            extra = root / ".github" / "workflows" / "unexpected.yml"
            extra.write_text(
                'name: unexpected\n"on":\n  workflow_dispatch:\npermissions:\n'
                '  contents: read\njobs:\n  inspect:\n    runs-on: ubuntu-latest\n'
                '    steps:\n      - run: true\n',
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(
            any("workflow inventory must match approved allowlist" in error for error in errors)
        )

    def test_local_action_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8")
                + "\n      - uses: ./.github/actions/unapproved-local-action\n",
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("action inventory must match approved allowlist" in error for error in errors))

    def test_unapproved_full_sha_action_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8")
                + "\n      - uses: step-security/harden-runner@"
                + ("a" * 40)
                + "\n",
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("action inventory must match approved allowlist" in error for error in errors))

    def test_alternate_credential_paths_are_rejected_without_echoing_values(self) -> None:
        variants = (
            ("${{ secrets.KAPI_TOKEN }}", "secrets contexts are forbidden"),
            ("GH_TOKEN: masked-source", "alternate credential aliases are forbidden"),
            ("ghp_ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789", "PAT-like literals are forbidden"),
            ("secrets: inherit", "inherited secrets are forbidden"),
            (
                "actions/create-github-app-token@" + ("a" * 40),
                "GitHub App token minting actions are forbidden",
            ),
            (
                "/app/installations/123/access_tokens",
                "GitHub App token endpoints are forbidden",
            ),
            ("PRIVATE_KEY: masked-source", "private-key bindings are forbidden"),
            ("${{ vars.KAPI_TOKEN }}", "credential-like vars/env bindings are forbidden"),
            ("${{ secrets['KAPI_TOKEN'] }}", "secrets contexts are forbidden"),
            ("KAPI_CREDENTIAL: masked-source", "credential-like YAML key is forbidden"),
            ("export KAPI_API_KEY=masked-source", "credential-like shell assignment is forbidden"),
        )
        for injected, expected in variants:
            with self.subTest(injected=injected):
                with tempfile.TemporaryDirectory() as temporary:
                    root = pathlib.Path(temporary)
                    self._copy_github(root)
                    workflow = (
                        root
                        / ".github"
                        / "workflows"
                        / "kapi-governance-actions-advisory-v1.yml"
                    )
                    workflow.write_text(
                        workflow.read_text(encoding="utf-8") + "\n" + injected + "\n",
                        encoding="utf-8",
                    )
                    errors = scan_workflows(root)
                self.assertTrue(any(expected in error for error in errors), errors)
                self.assertNotIn("masked-source", "\n".join(errors))

    def test_every_checkout_must_disable_persisted_credentials(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8").replace(
                    "persist-credentials: false",
                    "persist-credentials: true",
                    1,
                ),
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(
            any("every checkout must set persist-credentials false" in error for error in errors)
        )

    def test_duplicate_checkout_credential_key_cannot_override_false(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8").replace(
                    "          persist-credentials: false",
                    "          persist-credentials: false\n"
                    "          persist-credentials: true",
                    1,
                ),
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(
            any("every checkout must set persist-credentials false" in error for error in errors)
        )

    def test_extra_github_token_binding_is_rejected(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "ready-for-review-guard.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8")
                + "\nGITHUB_TOKEN: ${{ github.token }}\n",
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(
            any("GITHUB_TOKEN binding count must be exactly 2" in error for error in errors)
        )

    def test_duplicate_github_token_key_cannot_override_approved_binding(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "ready-for-review-guard.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8").replace(
                    "          GITHUB_TOKEN: ${{ github.token }}",
                    "          GITHUB_TOKEN: ${{ github.token }}\n"
                    "          GITHUB_TOKEN: unapproved-source",
                    1,
                ),
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(
            any("GITHUB_TOKEN binding count must be exactly 2" in error for error in errors)
        )

    def test_sparse_checkout_must_include_codeowners(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            self._copy_github(root)
            workflow = root / ".github" / "workflows" / "kapi.yml"
            workflow.write_text(
                workflow.read_text(encoding="utf-8").replace(
                    "            .github/CODEOWNERS\n", "", 1
                ),
                encoding="utf-8",
            )
            errors = scan_workflows(root)
        self.assertTrue(any("trusted sparse checkout must match" in error for error in errors))

    def test_trusted_scanner_rejects_candidate_policy_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            trusted = root / "trusted"
            candidate = root / "candidate"
            self._copy_github(trusted)
            self._copy_github(candidate)
            policy = candidate / ".github" / "kapi-governance" / "policy-v1.json"
            policy.write_text(
                policy.read_text(encoding="utf-8").replace(
                    '"activation_state": "blocked_pending_external_verifier_and_human_authorizer"',
                    '"activation_state": "active"',
                    1,
                ),
                encoding="utf-8",
            )
            errors = scan_workflows(candidate, trusted)
        # Still rejected -- and for a stronger reason than before. The scanner no
        # longer refuses every policy diff on sight; the trusted-base validator
        # refuses an active policy that lacks its prerequisites. The blunt rule made
        # the policy unchangeable by any path, including step 9's own policy-only PR.
        self.assertTrue(
            any("fails the trusted validator" in error for error in errors), errors
        )
        self.assertTrue(
            any("requires a separate human authorizer" in error for error in errors),
            errors,
        )

    def test_trusted_scanner_rejects_candidate_codeowners_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            trusted = root / "trusted"
            candidate = root / "candidate"
            self._copy_github(trusted)
            self._copy_github(candidate)
            codeowners = candidate / ".github" / "CODEOWNERS"
            codeowners.write_text(
                codeowners.read_text(encoding="utf-8") + "\n# unauthorized candidate change\n",
                encoding="utf-8",
            )
            errors = scan_workflows(candidate, trusted)
        self.assertTrue(
            any(
                "candidate changes protected governance file .github/CODEOWNERS" in error
                for error in errors
            )
        )

    def test_trusted_scanner_rejects_governance_readme_tampering(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            trusted = root / "trusted"
            candidate = root / "candidate"
            self._copy_github(trusted)
            self._copy_github(candidate)
            readme = candidate / ".github" / "kapi-governance" / "README.md"
            readme.write_text(
                readme.read_text(encoding="utf-8") + "\nUnsupported claim.\n",
                encoding="utf-8",
            )
            errors = scan_workflows(candidate, trusted)
        self.assertTrue(
            any(
                "candidate changes protected governance file .github/kapi-governance/README.md"
                in error
                for error in errors
            )
        )


class EnvironmentParsingTests(unittest.TestCase):
    def _environment(self) -> dict[str, str]:
        return {
            "EVENT_ACTOR_ID": str(OPERATOR_ID),
            "EVENT_ACTOR_LOGIN": "curtis-KingyAI",
            "EVENT_BASE_SHA": BASE_SHA,
            "EVENT_HEAD_REPOSITORY_ID": str(BASE_REPOSITORY_ID),
            "EVENT_HEAD_SHA": HEAD_SHA,
            "EVENT_TIME": "2026-07-13T12:00:00Z",
            "GITHUB_REPOSITORY_ID": str(BASE_REPOSITORY_ID),
            "GITHUB_RUN_ATTEMPT": "1",
            "GITHUB_RUN_ID": "700",
            "PR_NODE_ID": "PR_kwTEST",
            "PR_NUMBER": "4",
            "TRUSTED_GOVERNANCE_SHA": BASE_SHA,
        }

    def test_missing_event_field_is_rejected(self) -> None:
        environment = self._environment()
        del environment["EVENT_HEAD_SHA"]
        with mock.patch.dict(os.environ, environment, clear=True):
            with self.assertRaises(GovernanceError):
                event_from_environment(
                    str(ROOT / ".github" / "kapi-governance" / "policy-v1.json")
                )

    def test_malformed_event_sha_is_rejected(self) -> None:
        environment = self._environment()
        environment["EVENT_BASE_SHA"] = "not-a-sha"
        with mock.patch.dict(os.environ, environment, clear=True):
            with self.assertRaises(GovernanceError):
                event_from_environment(
                    str(ROOT / ".github" / "kapi-governance" / "policy-v1.json")
                )


if __name__ == "__main__":
    unittest.main()


class PolicyChangeLaneTests(unittest.TestCase):
    """The tier split: policy may change, but only in ways the trusted validator allows.

    Every case here is judged by the *base* implementation. A pull request can never
    supply the code that judges it, which is the property the blunt rule was
    protecting and this lane preserves.
    """

    def _copy_github(self, root: pathlib.Path) -> None:
        shutil.copytree(ROOT / ".github", root / ".github")

    def _scan(self, mutate):
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            trusted = root / "trusted"
            candidate = root / "candidate"
            self._copy_github(trusted)
            self._copy_github(candidate)
            mutate(candidate)
            return scan_workflows(candidate, trusted)

    @staticmethod
    def _edit_policy(**changes):
        def mutate(candidate: pathlib.Path) -> None:
            path = candidate / ".github" / "kapi-governance" / "policy-v1.json"
            document = json.loads(path.read_text(encoding="utf-8"))
            document.update(changes)
            path.write_text(json.dumps(document, indent=2) + "\n", encoding="utf-8")

        return mutate

    # -- permitted ---------------------------------------------------------

    def test_recording_the_human_authorizer_is_permitted(self):
        self.assertEqual(self._scan(self._edit_policy(human_authorizer_actor_ids=[302366674])), [])

    def test_recording_the_verifier_and_attestations_is_permitted(self):
        self.assertEqual(
            self._scan(
                self._edit_policy(
                    human_authorizer_actor_ids=[302366674],
                    dedicated_verifier_integration_id=987654,
                    credential_separation_attested=True,
                    dedicated_verifier_attested=True,
                    activation_state="active",
                )
            ),
            [],
        )

    # -- refused by the validator -----------------------------------------

    def test_activation_without_attestations_is_refused(self):
        errors = self._scan(
            self._edit_policy(activation_state="active", human_authorizer_actor_ids=[302366674])
        )
        self.assertTrue(any("fails the trusted validator" in e for e in errors), errors)

    def test_actions_app_as_dedicated_verifier_is_refused(self):
        errors = self._scan(
            self._edit_policy(
                human_authorizer_actor_ids=[302366674],
                dedicated_verifier_integration_id=15368,
                credential_separation_attested=True,
                dedicated_verifier_attested=True,
                activation_state="active",
            )
        )
        self.assertTrue(errors)

    def test_authorizer_overlapping_automation_is_refused(self):
        errors = self._scan(self._edit_policy(human_authorizer_actor_ids=[227671038]))
        self.assertTrue(any("fails the trusted validator" in e for e in errors), errors)

    # -- refused by the field allowlist -----------------------------------

    def test_changing_who_may_act_is_refused(self):
        for field, value in (
            ("allowed_ready_actor_ids", [999999]),
            ("automation_actor_ids", [999999]),
            ("repository_id", 7),
            ("audit_actor_id", 7),
            ("authorization_ttl_seconds", 599),
        ):
            with self.subTest(field=field):
                errors = self._scan(self._edit_policy(**{field: value}))
                self.assertTrue(
                    any("may not change" in e or "fails the trusted validator" in e for e in errors),
                    (field, errors),
                )

    def test_reformatting_without_a_field_change_is_refused(self):
        def mutate(candidate: pathlib.Path) -> None:
            path = candidate / ".github" / "kapi-governance" / "policy-v1.json"
            document = json.loads(path.read_text(encoding="utf-8"))
            path.write_text(json.dumps(document, indent=4) + "\n", encoding="utf-8")

        errors = self._scan(mutate)
        self.assertTrue(any("no field value changed" in e for e in errors), errors)

    # -- isolation ---------------------------------------------------------

    def test_policy_plus_executable_in_one_pull_request_is_refused(self):
        def mutate(candidate: pathlib.Path) -> None:
            self._edit_policy(human_authorizer_actor_ids=[302366674])(candidate)
            script = candidate / ".github" / "scripts" / "kapi_ready_guard.py"
            script.write_text(script.read_text(encoding="utf-8") + "\n# rider\n", encoding="utf-8")

        errors = self._scan(mutate)
        self.assertTrue(any("same pull request" in e for e in errors), errors)
        self.assertTrue(any("kapi_ready_guard.py" in e for e in errors), errors)

    # -- the original rule still holds for everything else -----------------

    def test_executable_change_alone_is_still_refused(self):
        def mutate(candidate: pathlib.Path) -> None:
            script = candidate / ".github" / "scripts" / "kapi_governance.py"
            script.write_text(script.read_text(encoding="utf-8") + "\n# rider\n", encoding="utf-8")

        errors = self._scan(mutate)
        self.assertTrue(any("cannot be changed by an ordinary pull request" in e for e in errors), errors)

    def test_readme_change_alone_is_still_refused(self):
        def mutate(candidate: pathlib.Path) -> None:
            readme = candidate / ".github" / "kapi-governance" / "README.md"
            readme.write_text(readme.read_text(encoding="utf-8") + "\nUnsupported claim.\n", encoding="utf-8")

        errors = self._scan(mutate)
        self.assertTrue(errors, "the normative procedure document must stay strict")

    def test_protected_manifest_membership_is_unchanged_by_the_split(self):
        self.assertEqual(
            set(PROTECTED_GOVERNANCE_PATHS),
            set(EXECUTABLE_GOVERNANCE_PATHS)
            | set(NORMATIVE_GOVERNANCE_PATHS)
            | set(VALIDATED_GOVERNANCE_PATHS),
        )
        self.assertEqual(len(PROTECTED_GOVERNANCE_PATHS), 10)
