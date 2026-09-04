# Threat model

## Asset and safety objective

The protected asset is the run budget and the ability to hand off useful partial work. The objective is to prevent an agent from continuing model/tool calls after a configured cap or after repeated lack of progress.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Work queue | Local fixture that may be broad or unproductive | Fixed input fingerprint and bounded queue. |
| Fake provider result | Metered external-work stand-in | Cost, tokens, tools, retries, and progress are recorded for every call. |
| Budget policy | Trusted local configuration | Thresholds and reserve are persisted with the run. |
| Workflow state | Durable teaching ledger | Resume uses prior spend; changed input cannot reset it. |
| Partial result | Operator handoff evidence | States completed/skipped work, spend, cap, and required decision. |

## Failure actions demonstrated

- Keep processing a research queue until the hard budget cap.
- Make a normally expensive call near the cap.
- Restart after the terminal cap and attempt to continue.
- Retry poor sources without progress.
- Pause and resume a run to attempt a budget reset.

## Non-goals and residual risk

- Fake credits are not a billing ledger, currency, provider invoice, or prepaid balance.
- The single-process JSON store has no locking, distributed lease, rate limiter, clock skew handling, or multi-agent budget arbitration.
- Actual provider calls can exceed estimates; production requires provider-specific reservations and reconciliation.
- The local checkpoint reserve is accounting-only and not a paid provider action.
- This lab does not test abuse prevention, authentication, authorization, or customer-level quotas.

Production systems need an authoritative shared budget service, atomic spend reservation, provider usage ingestion, alerts, rate limits, tenant controls, and incident ownership.
