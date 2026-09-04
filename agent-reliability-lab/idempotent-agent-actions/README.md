# Idempotent Agent Actions

A small, offline Kingy Agent Reliability Lab showing how retries can create one intended external action rather than duplicates.

## Control demonstrated

An idempotency key and payload fingerprint identify one intended mocked publication. A durable local entry records a pending outbox command before the publisher is called. On retry, the service either returns the stored result or reconciles publisher state before it can publish again.

## Failure injected

The first request is deliberately timed out after the mock publisher accepts the publication but before the action service records completion. The same request is then sent nine more times.

## Evidence produced

The demo writes `idempotency-store.json`, `outbox.jsonl`, `publisher-events.jsonl`, `publisher-state.json`, `operation-events.jsonl`, `deduplication-report.md`, and `run-summary.json` beneath `artifacts/`.

## Not proven

This is a local JSON teaching implementation, not a production database transaction, distributed lock, external provider guarantee, retention policy, or live CMS integration.

## Quickstart

Requires Node.js 22 LTS. There are no package dependencies, credentials, or network calls.

```sh
npm test
npm run demo
```

The demo's retry key is attempted ten times. Attempt one reports `AMBIGUOUS_TIMEOUT`; attempt two reconciles the durable mock-publisher state; attempts three through ten replay the stored result. The retry key produces exactly one mocked publication.

The demo also proves two intentional distinctions:

- Reusing a key with a different content fingerprint returns `IDEMPOTENCY_KEY_CONFLICT`.
- A revised `v2` key creates a separate, intentional publication.

Inspect `artifacts/run-summary.json` and `artifacts/deduplication-report.md`, then count `artifacts/publisher-events.jsonl` for the retry key.

```sh
npm run clean
```

This removes generated files from this repository's `artifacts/` directory only.

## Design choices

- A key is scoped to a publication intent: `publish:launch-aurora:v1`.
- The fingerprint protects against reusing a key for a materially different payload.
- Pending entries include an outbox command before the first publish attempt.
- The retry checks durable publisher state before calling `publish`, so reconciliation does not invoke a second publish call.
- The clock, IDs, fixtures, and timestamps are fixed for repeatability.

## Repository map

```text
src/          idempotency store, publisher, action service, report and evidence contract
fixtures/     one deterministic publication request
scripts/      ten-attempt timeout/retry demo and artifact cleanup
test/         retry, timeout, conflict, versioning, and restart tests
artifacts/    generated evidence; ignored except .gitkeep
```

Read [ARCHITECTURE.md](ARCHITECTURE.md), [THREAT-MODEL.md](THREAT-MODEL.md), and [RUNBOOK.md](RUNBOOK.md) before adapting the pattern. Code is Apache-2.0; prose and diagrams are CC BY 4.0.
