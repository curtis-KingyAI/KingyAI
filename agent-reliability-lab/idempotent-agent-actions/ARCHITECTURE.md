# Architecture

```text
request + idempotency key + content fingerprint
                     |
                     v
            IdempotentActionService
              |                  |
              v                  v
   file-backed idempotency   outbox evidence log
   entry: pending/completed        |
              |                    v
              +--------------> MockPublisher
                                durable publication state
```

1. The service atomically writes a local `pending` entry containing the exact command and an outbox state.
2. It asks the publisher whether that key was already accepted.
3. If not, it publishes once. The demo then intentionally raises an ambiguous timeout before `completed` is recorded.
4. A restarted service sees the pending key, finds the existing publisher record, completes the entry as `publisher-reconciliation`, and returns the original result.
5. Later retries return the completed result without calling the publisher.

The store and publisher use write-then-rename for a single local JSON file. That supports this single-process teaching scenario. A production implementation needs an actual transactional database, uniqueness constraint, transactional outbox, locking/lease policy, and provider-level idempotency where available.
