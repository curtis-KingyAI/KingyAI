# Control matrix

| Control | Primary failure | Enforcement point | Visible evidence | Production translation | Lab |
| --- | --- | --- | --- | --- | --- |
| Authority | Agent publishes, reads, deletes, or changes policy beyond its job | Action gateway before every tool action | Policy decision, approval state, audit record | Verified workload identity, ABAC/RBAC, scoped credentials, durable approval service | [Permissions gate](../agent-permissions-gate/README.md) |
| Safe retries | Timeout, duplicate scheduler event, or webhook produces duplicate publication | Idempotency key, fingerprint, durable pending/completed record | Publisher event log, outbox evidence, deduplication report | Database uniqueness constraint, transactional outbox, provider idempotency | [Idempotent actions](../idempotent-agent-actions/README.md) |
| Recovery | Crash reruns work, loses work, or trusts partial/corrupt output | Stage checkpoint with input/output fingerprints | Workflow state, stage artifacts, transition log | Durable workflow state, leases, queues, transactional transitions | [Checkpoint recovery](../agent-checkpoint-recovery/README.md) |
| Bounded cost | Retry/search loop consumes unbounded spend or time | Persisted budget governor before every call | Ledger, decision log, partial result, provider event count | Shared budget service, reservation, provider reconciliation, rate limits | [Cost circuit breaker](../agent-cost-circuit-breaker/README.md) |
| MCP boundary | Tool output injects instructions or expands paths/domains/authority | Host policy before and after tool invocation | Allowlist, schema result, redacted provenance, revocation decision | Authenticated SDK transport, sandbox, secret isolation, network control | [MCP security](../mcp-security-lab/README.md) |
| Editorial quality | Fluent draft contains unsupported, stale, incomplete, or out-of-scope content | Deterministic evaluation before review handoff | Source fixtures, per-case trace, taxonomy, scorecard | Maintained evidence corpus, editorial ownership, human review | [Editorial evals](../agent-editorial-evals/README.md) |

## How controls compose

The controls have different jobs and should not be substituted for one another:

- Permissions decide whether an action may happen.
- Idempotency decides whether a permitted action already happened.
- Checkpoints decide where a workflow can resume.
- Cost limits decide whether work may continue.
- MCP boundaries decide whether an integration can influence the host.
- Evals decide whether an output may be handed to an editor or other reviewer.

For a mocked Kingy launch-record workflow, a safe order is: source collection under MCP constraints, draft under a finite budget, checkpointed progress, deterministic editorial evaluation, human approval, then a one-time permitted and idempotent mocked publish.
