# Architecture

```text
work item -> CostGovernedWorkflow -> persisted budget ledger -> fake metered model
                    |                         |                       |
                    |                         v                       v
                    +--> decision log     workflow checkpoint     provider event log
                    |
                    +--> partial-result.md at terminal stop
```

The budget uses committed credits:

```text
committed credits = actual model/tool spend + checkpoint reserve
```

The reserve is held before work begins. In the hard-budget fixture, the workflow spends 95 fake credits and retains 5 for a checkpoint, reaching the 100-credit committed cap. It does not make a fifth provider call.

Before each call, the governor calculates the projected cost. If the normal path would reach the degradation threshold, it switches to an eligible cheap model and narrows scope. After every call it persists ledger totals, checks the no-progress streak, then either continues or writes a terminal checkpoint and human review packet.

The write-then-rename local state protects this single-process lab. Production systems need provider billing reconciliation, authoritative quota controls, concurrency-safe state, rate limits, durable queues, and organization-level enforcement.
