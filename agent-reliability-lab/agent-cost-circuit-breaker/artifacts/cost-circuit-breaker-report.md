# Cost circuit-breaker report

| Scenario | Terminal status | Model calls | Actual spend | Committed budget |
| --- | --- | --- | --- | --- |
| Hard budget | BUDGET_EXHAUSTED | 4 | 95 | 100 / 100 |
| No progress | NO_PROGRESS_LIMIT | 3 | 30 | 35 / 100 |
| Resume continuity | paused | 2 | 65 | 70 / 100 |

Hard-budget flow: warning fires at 70% committed budget; a cheaper model and narrowed scope are selected at the 85% projected threshold; the workflow checkpoints and stops at the 100% committed cap.
No-progress flow: two consecutive no-progress results stop the workflow below the financial cap.
Resume continuity: a fresh context reads the original persisted ledger and continues with its remaining allowance rather than a new budget.
