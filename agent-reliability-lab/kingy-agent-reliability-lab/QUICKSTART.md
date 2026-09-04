# Shared cross-lab quickstart

## Preconditions

- Node.js 22 LTS (`.nvmrc` is included in every runnable repository).
- A local clone of this collection.
- No API key, package install, network connection, model account, CMS, or production data.

The labs use Node’s built-in test runner and local fixtures. They write generated evidence only beneath their own `artifacts/` directory.

## Run one lab

From this hub directory, choose a control and run its tests plus demonstration:

```sh
cd ../agent-permissions-gate
npm test
npm run demo
```

Read `artifacts/run-summary.json` first. It names the fixture, result, assertions, evidence paths, and known limitations. Then inspect the control-specific report and event log listed in the lab README.

## Recommended learning sequence

1. **Permissions gate** — establish who can do what, and where a human approves.
2. **Idempotent actions** — make retried side effects safe.
3. **Checkpoint recovery** — persist progress and validate it before reuse.
4. **Cost circuit breaker** — impose financial and loop stop conditions.
5. **MCP security** — treat every server/tool response as untrusted input.
6. **Editorial evals** — prove drafts meet explicit source and escalation rules.

This order tracks a useful operating model: authority before action, safe action before recovery, bounded recovery before tool expansion, and evaluation before public handoff.

## Run the complete collection

```sh
cd kingy-agent-reliability-lab
npm test
npm run verify-labs
```

The verification command runs all six local test suites and demos. It does not call an external service.

## What to inspect

| Control | First evidence file |
| --- | --- |
| Permissions | `agent-permissions-gate/artifacts/run-summary.json` |
| Idempotency | `idempotent-agent-actions/artifacts/deduplication-report.md` |
| Checkpoints | `agent-checkpoint-recovery/artifacts/crash-recovery-report.md` |
| Cost | `agent-cost-circuit-breaker/artifacts/cost-circuit-breaker-report.md` |
| MCP | `mcp-security-lab/artifacts/security-decision-report.md` |
| Editorial evals | `agent-editorial-evals/artifacts/scorecard.json` |

## Reset a lab

Each lab has the same bounded cleanup command:

```sh
npm run clean
```

It removes generated files only from that lab’s own `artifacts/` directory. Never point a lab at production credentials, production state, customer data, or a real write-capable account.
