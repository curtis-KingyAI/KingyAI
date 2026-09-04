# Kingy Agent Reliability Lab

Six small, runnable repositories for the controls that turn an impressive agent demo into a bounded, inspectable workflow.

> Clone a lab, trigger one failure on purpose, add the control, and inspect the evidence that the agent now refuses, resumes, retries safely, evaluates correctly, or stops within budget.

This hub is the local index and editorial package for the collection. Each lab is offline, mock-only, deterministic, and Node 22-targeted. None is a production framework, security certification, or authorization to connect a live account.

## Start here

If the agent can act on the world, begin with **authority**. If it can retry or run over time, add **idempotency**, **checkpoints**, and **cost limits**. If it consumes tool output or writes editorial content, add the **MCP boundary** and **editorial evals**.

| Lab | Production question | Runnable proof |
| --- | --- | --- |
| [Permissions gate](../agent-permissions-gate/README.md) | What may the agent do? | Only a scoped, one-use approval allows one mocked publication. |
| [Idempotent actions](../idempotent-agent-actions/README.md) | Can retries duplicate side effects? | Ten attempts after an ambiguous timeout create one mocked publication. |
| [Checkpoint recovery](../agent-checkpoint-recovery/README.md) | Where does a crash resume? | Every crash point restarts from the last verified stage. |
| [Cost circuit breaker](../agent-cost-circuit-breaker/README.md) | Can a loop overrun budget? | Warning, degradation, checkpointed cap stop, and no-progress stop are visible. |
| [MCP security](../mcp-security-lab/README.md) | Can tools expand host authority? | Untrusted output is constrained by allowlists, schemas, boundaries, revocation, and approvals. |
| [Editorial evals](../agent-editorial-evals/README.md) | Can unsafe drafts reach review? | Frozen cases block unsupported claims, citation failures, stale pricing, and missed escalations. |

## Navigate the package

- [Shared cross-lab quickstart](QUICKSTART.md)
- [Control matrix](CONTROL-MATRIX.md)
- [Evidence methodology](EVIDENCE-METHODOLOGY.md)
- [Editorial launch outline](EDITORIAL-LAUNCH.md)

## The shared workflow

```text
trigger -> source collection -> extraction -> draft -> check -> approval -> mocked publish
                       |           |            |        |        |
                     MCP       checkpoints    evals    permissions + idempotency
                       \__________________ cost governor __________________/
```

The labs are intentionally separate. Each isolates one control, keeps the failure legible, and avoids requiring users to adopt a particular agent framework.

## Evidence boundary

Every lab supplies a README, architecture, threat model, runbook, fixtures, test suite, and generated `run-summary.json`. The evidence shows how the included local implementation behaved under stated fixtures. It does not establish universal reliability, security, regulatory compliance, or live-provider behavior. See [Evidence methodology](EVIDENCE-METHODOLOGY.md).

## Hub verification

Requires Node 22 LTS.

```sh
npm test
npm run verify-labs
```

`npm test` confirms the hub’s six expected repositories and their required documentation. `npm run verify-labs` runs each lab’s tests and demo from its own directory.
