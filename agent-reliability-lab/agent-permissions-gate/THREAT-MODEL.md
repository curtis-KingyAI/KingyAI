# Threat model

## Asset and safety objective

The mocked CMS record and fixture file boundary are the protected assets. The objective is to ensure a launch-research agent cannot turn model output or untrusted prompt text into an unapproved publication, deletion, policy change, or unrelated file read.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Agent prompt and request content | Untrusted | Not used in policy evaluation. |
| Agent identity string | Fixture-only identity | Matched to a narrow principal policy. |
| Policy file | Trusted local configuration | Read by the gateway; no request may update it. |
| Approval grant | Privileged local fixture | Exact actor/action/resource match, expiry, and one-use tracking. |
| Mock CMS | Protected side-effect target | Invoked only after gateway allow decision. |
| Audit log | Evidence output | Fixed, redacted schema; no prompt or nonce is written. |

## Attacker actions demonstrated

- Ask the agent to ignore policy and publish.
- Attempt a direct publish without approval.
- Attempt an unrelated private file read, deletion, and policy update.
- Replay a consumed grant.
- Change the resource while retaining a grant for the original record.
- Use an expired grant.

## Non-goals and residual risk

- The fixture's actor identity is not authenticated.
- Grants are not signed, encrypted, or stored in a secure key-management system.
- File-backed demo state has no transactional durability or distributed locking.
- The mock CMS does not model a real CMS authorization model, race conditions, or API compromise.
- This lab does not test browser, shell, MCP, database, network egress, or supply-chain controls.

For a production system, replace the fixture actor with verified workload identity; put policy and grants behind a durable authorization service; rotate keys; audit access; isolate execution; and use atomic, observable side-effect handling.
