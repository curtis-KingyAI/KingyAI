# Agent Checkpoint Recovery

A small, offline Kingy Agent Reliability Lab that demonstrates safe restart from a verified checkpoint after a multi-stage research workflow crashes.

## Control demonstrated

The workflow writes a local checkpoint after each completed stage. A checkpoint carries its input fingerprint, output fingerprint, workflow version, and artifact path. Restart validates all of them before reuse.

## Failure injected

The demo injects a crash immediately after every one of six committed checkpoints: `collect`, `extract`, `outline`, `draft`, `fact-check`, and `ready-for-review`.

## Evidence produced

The demo writes one local scenario per crash point, including `workflow-state.json`, stage artifacts, `transition-log.jsonl`, `crash-recovery-report.md`, and `run-summary.json` under `artifacts/`.

## Not proven

This is a single-process JSON teaching implementation. It is not a distributed workflow engine, queue, worker lease, transactional database, or recovery system for live external side effects.

## Quickstart

Requires Node.js 22 LTS. No package installation, credentials, network connection, model provider, or external service is required.

```sh
npm test
npm run demo
```

For each forced crash, the restarted run reuses every stage through the committed checkpoint and completes only the later stages. For example, a crash after `draft` reuses `collect`, `extract`, `outline`, and `draft`; it resumes at `fact-check`.

The demo also changes one source document. Collection stays valid because the source manifest is unchanged; extraction and later work are recomputed because the source content input changed.

Inspect `artifacts/run-summary.json`, then `artifacts/crash-recovery-report.md`. Every scenario has its own `workflow-state.json` and `transition-log.jsonl` below `artifacts/crash-after-*`.

```sh
npm run clean
```

The clean command removes generated files from this repository's `artifacts/` directory only.

## Design choices

- A checkpoint is committed only after its artifact is written and fingerprinted.
- Missing, altered, or fingerprint-mismatched artifacts are not reused.
- Changing an input invalidates that stage and only downstream stages.
- A fixed clock, fixed fixture, and explicit run IDs make traces repeatable.
- The crash is injected after a checkpoint commit, modelling process loss between successful stages.

## Repository map

```text
src/          checkpoint engine, store, stage functions, hashing, evidence contract
fixtures/     deterministic launch-record workflow input
scripts/      all-stage crash/recovery demo and artifact cleanup
test/         every-stage crash, input change, corruption, and readiness tests
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting this pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
