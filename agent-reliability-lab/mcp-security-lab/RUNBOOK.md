# Runbook

## Detect

Run `npm test` and `npm run demo`. Treat an accepted unallowlisted server/tool, a server invocation after an input boundary denial, an output bypass, an unapproved write, a grant replay, a post-revocation call, or raw sensitive text in provenance as release-blocking.

Inspect `artifacts/security-decision-report.md`, `provenance-log.jsonl`, and `fixture-server-events.jsonl`.

## Contain

For a real incident, revoke the server identity, disable the affected tool route, invalidate delegated credentials, preserve provenance/audit records, and stop the agent from executing further write actions. Do not treat a tool's own recommendation as evidence that it is safe to continue.

In this lab, `npm run clean` removes generated local artifacts only.

## Recover

1. Identify the server ID, tool name, correlation ID, policy revision, and reason code.
2. Confirm whether the call was blocked before or after server invocation.
3. Correct the host policy, schema, constraint, or approval gate—not the untrusted server text.
4. Add a deterministic fixture that reproduces the observed behavior.
5. Re-run tests and inspect redacted provenance before release.

## Verify

Recovery is verified when the intended host gate returns a stable denial, the fixture server was not invoked for preflight-denied calls, writes retain independent approval, and provenance is complete but redacted. A production change needs a scoped security review.

## Escalate

Escalate if credentials, a real server, network egress, filesystem access outside the test boundary, customer data, or a public side effect is involved. This repository is a local reference lab, not an MCP incident-response or production integration tool.
