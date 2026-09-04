# Threat model

## Asset and safety objective

The protected asset is the workflow's verified progress: source collection, extracted claims, draft, fact check, and review packet. The objective is to avoid reusing partial or corrupted work and to avoid rerunning unchanged upstream work after a crash.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Workflow input | Versioned local fixture | Fingerprinted at the stage that consumes it. |
| Stage artifact | Potentially missing or tampered local file | Rehashed and compared before reuse. |
| Checkpoint state | Durable teaching state | Contains status, input/output fingerprints, and artifact path. |
| Crash injection | Test-only failure | Occurs only after a checkpoint commit. |
| Transition log | Evidence output | Records completed, reused, invalidated, and injected-crash transitions. |

## Failure actions demonstrated

- Lose the process immediately after any committed stage.
- Restart from persisted state.
- Change source document content after a completed run.
- Corrupt a completed draft artifact.
- Attempt to claim readiness before fact checking has completed.

## Non-goals and residual risk

- There is no distributed queue, worker lease, lock, transaction manager, scheduling system, or concurrent execution test.
- JSON writes are not a substitute for a transactional database or tamper-evident artifact store.
- This lab has no external write side effect; checkpointing alone does not make email, payment, deployment, or publishing actions safe to retry.
- Fingerprints show content equality in this lab; they do not prove source authenticity, correctness, or security.

Use a durable workflow system with atomic transitions, ownership/lease rules, retention, monitoring, and idempotent external-action handling in a production implementation.
