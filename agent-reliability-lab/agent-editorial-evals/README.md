# Agent Editorial Evals

A small, offline Kingy Agent Reliability Lab that blocks unsafe launch-record drafts before editorial review.

## Control demonstrated

Frozen source snapshots and expectations drive deterministic hard checks for claims, citations, source dates, claim boundaries, limitations, disclosure, uncertainty, duplicate content, and required escalation. Every hard failure blocks the draft.

## Failure injected

The `weak` drafting strategy produces polished but unsafe candidates across twelve cases: unsupported claim, false/missing citation, stale price framed as current, missing limitation, missed escalation, malformed source use, disclosure omission, unmarked uncertainty, and duplicate content.

## Evidence produced

The demo writes versioned `source-snapshots.json`, `expectations.json`, per-case traces for each strategy, `scorecard.json`, `failure-taxonomy.md`, `editor-review-packet.md`, and `run-summary.json` under `artifacts/`.

## Not proven

This fixed local fixture set does not prove factual correctness on the open web, compliance, absence of hallucination, editorial quality for every audience, or the safety of a live model/provider.

## Quickstart

Requires Node.js 22 LTS. There are no package dependencies, credentials, model calls, or network connections.

```sh
npm test
npm run demo
```

The weak strategy is blocked on all twelve fixtures. The constrained strategy passes all hard checks; five fixtures safely route to escalation rather than an editorial-ready draft.

Inspect `artifacts/scorecard.json`, `artifacts/failure-taxonomy.md`, and `artifacts/editor-review-packet.md`. Each decision has an inspectable trace under `artifacts/traces/`.

```sh
npm run clean
```

The clean command removes generated files from this repository's `artifacts/` directory only.

## Design choices

- Claims are checked against fixture-specific allowed claim IDs, source IDs, source dates, and explicit boundaries.
- A historical price may be reported only with a historical boundary and a required current-price limitation.
- Conflict, missing-source, malformed-source, out-of-scope, and sponsor-claim fixtures must escalate instead of drafting.
- The optional subjective judge is represented only as metadata and is excluded from the release gate.
- The fixture version and candidate fingerprints appear in every trace and scorecard.

## Repository map

```text
src/          evaluator, deterministic strategies, runner, reports and evidence contract
fixtures/     twelve source-backed local cases and expected boundaries
scripts/      weak-versus-constrained demo and artifact cleanup
test/         all-fixture pass, regression, taxonomy, and determinism tests
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting the pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
