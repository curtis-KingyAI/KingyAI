# Agent Cost Circuit Breaker

A small, offline Kingy Agent Reliability Lab that proves an agent can stop safely before a loop consumes an unbounded budget.

## Control demonstrated

A persisted governor tracks actual and estimated credits, checkpoint reserve, tokens, model calls, tool calls, source count, retries, elapsed time, no-progress streak, selected model, and scope. It warns at 70% committed budget, narrows scope and chooses a cheaper eligible model at a projected 85%, then checkpoints and stops at 100%.

## Failure injected

One fixture drives a fake metered research model toward a hard budget cap. A second fixture produces two consecutive no-progress results, modelling a poor-source retry loop.

## Evidence produced

The demo writes `budget-policy.json`, `cost-ledger.jsonl`, `decision-log.jsonl`, `provider-events.jsonl`, `workflow-state.json`, `partial-result.md`, `cost-circuit-breaker-report.md`, and `run-summary.json` under `artifacts/`.

## Not proven

This is fake-credit, local accounting. It does not prove provider billing, organization-wide spend enforcement, rate limiting, live model pricing, production checkpoint costs, or incident response behavior.

## Quickstart

Requires Node.js 22 LTS. There are no package dependencies, credentials, real model calls, or network connections.

```sh
npm test
npm run demo
```

The hard-budget scenario reaches 70 credits committed after two calls, selects a cheap/narrow path before the next expensive call would reach 100, then stops with 95 credits spent plus a 5-credit checkpoint reserve. Its fake provider receives four calls only; a new workflow context sees the terminal ledger and makes no fifth call.

The no-progress scenario stops after two consecutive no-progress results at 35 committed credits, below the 100-credit cap. The separate resume-continuity scenario pauses after one call and resumes with the original remaining allowance rather than a fresh 100-credit budget.

Inspect `artifacts/run-summary.json`, then `artifacts/cost-circuit-breaker-report.md` and the scenario-specific ledger and decision logs.

```sh
npm run clean
```

The clean command removes generated files from this repository's `artifacts/` directory only.

## Design choices

- The checkpoint reserve is booked from the start, so normal work cannot consume the entire committed budget.
- Local checkpoint writing is free in this mock; the reserve is an accounting model that must become a real reservation policy in production.
- The fake provider records every invocation, which makes post-cap non-invocation directly testable.
- The workflow fingerprint prevents a resume from swapping inputs to obtain a new budget.
- A resumed context reads the same local ledger and preserves all consumed/committed credits.

## Repository map

```text
src/          persisted governor, local store, fake metered model, artifacts and report
fixtures/     deterministic budget and no-progress work queues
scripts/      all-scenario demo and artifact cleanup
test/         threshold, stop, no-progress, continuity, and input-change tests
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting the pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
