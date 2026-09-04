# Agent Permissions Gate

A small, offline Kingy Agent Reliability Lab that proves an agent cannot authorize its own consequential actions.

## Control demonstrated

An action gateway enforces a policy at the tool boundary. The fictional `launch-research-agent` may collect an official source and create a draft, but publishing needs a one-use approval grant scoped to one action and one record.

## Failure injected

The local fixture contains hostile prompt text telling the agent to publish, read an unrelated private file, and change policy. A direct publish attempt is also made without approval.

## Evidence produced

The demo writes `policy.json`, `approval-grants.json`, `audit.jsonl`, `decision-report.md`, `cms-state.json`, `approval-state.json`, and `run-summary.json` beneath `artifacts/`.

## Not proven

This is a mock CMS and fixture grant store. It is not RBAC/ABAC infrastructure, an identity system, cryptographic approval credential, production CMS audit, or security certification.

## Quickstart

Requires Node.js 22 LTS. The lab has no package dependencies and makes no network calls.

```sh
npm test
npm run demo
```

Expected protected behavior:

- Source collection and draft creation are allowed.
- Direct publishing is denied with `APPROVAL_REQUIRED`.
- A scoped grant allows exactly one publication of `launch-aurora`.
- Replay, target substitution, and expiry are denied with distinct stable reason codes.

Inspect `artifacts/run-summary.json` first, then `artifacts/decision-report.md` and `artifacts/audit.jsonl`.

```sh
npm run clean
```

The clean command removes generated files from this repository's `artifacts/` directory only.

## Design choices

- Authorization is made by `src/action-gateway.js`, not by the prompt.
- The prompt is neither a policy input nor an audit-log field.
- Grants contain a principal, action, resource, expiry, and nonce. Their one-use state is tracked separately.
- The demo uses an injected clock and fixed correlation IDs, so output is repeatable.
- The mock service is reachable only through the gateway in the demonstrated workflow.

## Repository map

```text
src/          gateway, policy evaluator, grant store, audit logger, mock CMS
fixtures/     local policy, grants, and hostile launch request
scripts/      deterministic demo and local artifact cleanup
test/         node:test verification of allowed and denied paths
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting the pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
