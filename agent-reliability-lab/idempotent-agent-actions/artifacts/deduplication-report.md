# Idempotent publication report

| Attempt | Outcome | Publication |
| --- | --- | --- |
| 1 | AMBIGUOUS_TIMEOUT | — |
| 2 | reconciled | mock-post-001 |
| 3 | replayed | mock-post-001 |
| 4 | replayed | mock-post-001 |
| 5 | replayed | mock-post-001 |
| 6 | replayed | mock-post-001 |
| 7 | replayed | mock-post-001 |
| 8 | replayed | mock-post-001 |
| 9 | replayed | mock-post-001 |
| 10 | replayed | mock-post-001 |

The retry key produced 1 mocked publication(s).
The first request is deliberately ambiguous after the publisher accepts it; the first retry reconciles durable publisher state rather than publishing again.
