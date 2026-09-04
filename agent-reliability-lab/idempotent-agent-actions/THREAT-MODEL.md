# Threat model

## Asset and safety objective

The protected asset is the external side effect: a mocked publication. The objective is to make retries, duplicate scheduling, and an ambiguous timeout resolve to one intended publication for one idempotency key and payload fingerprint.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Job request | Potentially duplicated or retried | Idempotency key plus fingerprint. |
| Idempotency key | Caller-supplied identifier | Stored with exact fingerprint; conflicting reuse is rejected. |
| Local store | Durable teaching state | Pending/completed state is written before acknowledgement. |
| Mock publisher | External side-effect stand-in | Durable lookup by idempotency key before republishing. |
| JSON Lines artifacts | Evidence output | Contains identifiers and hashes only, never raw article content or credentials. |

## Attacker/failure actions demonstrated

- Send the same request ten times.
- Lose the client response after the publisher accepted a request.
- Restart the action service while an idempotency entry is pending.
- Reuse a key with different content.
- Intentionally submit a revised payload using a new versioned key.

## Non-goals and residual risk

- There is no concurrency, file locking, queue, transaction coordinator, crash-consistency audit, or distributed worker lease.
- File rename is not equivalent to an external-service transaction.
- The mock publisher's lookup behavior is an explicit test assumption; real providers differ.
- A caller can still choose a poor key; production systems need key format, retention, and authorization policies.
- This lab does not test payments, emails, databases, webhooks, live CMS behavior, or provider outage recovery.

Use a database uniqueness constraint plus transactional outbox in a real system, retain keys long enough for the retry horizon, authorize callers, and reconcile real provider state with provider-specific identifiers.
