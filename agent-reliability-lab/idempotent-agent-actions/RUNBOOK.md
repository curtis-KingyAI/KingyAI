# Runbook

## Detect

Run `npm test` and `npm run demo`. Treat any retry-key publication count other than one, a missing pending/completed transition, or a payload conflict that reaches the publisher as release-blocking.

Inspect `artifacts/deduplication-report.md`, `artifacts/idempotency-store.json`, and `artifacts/publisher-events.jsonl`. The retry key should have one publication event, even though it has ten attempts.

## Contain

For a real incident, pause the affected worker or write-capable queue consumer. Preserve idempotency keys, correlation IDs, queue delivery IDs, provider references, and timestamps. Do not repeatedly retry blindly: each retry should use the same original key.

In this lab, `npm run clean` removes generated evidence and restores an empty local state.

## Recover

1. Look up the idempotency entry by key and compare its fingerprint to the retry payload.
2. If completed, return the stored result.
3. If pending, query the downstream provider by the same idempotency key or a durable provider reference.
4. Complete the record from a known provider result, or dispatch exactly one safe recovery action under the system's transaction policy.
5. Add a deterministic fixture for the failure before changing retry behavior.

## Verify

The recovery is verified only when the entry reaches completed state, the returned result is stable across another retry, and provider records show one intended action for the key. In a production incident, a human owner must verify any public, financial, or customer-facing effect.

## Escalate

Escalate when provider state cannot be reconciled, the idempotency key/fingerprint is ambiguous, a duplicate external effect occurred, or a live credential/customer record is involved. This repository is a local reference, not a real-incident automation tool.
