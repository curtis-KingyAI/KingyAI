# Runbook

## Detect

Run `npm test` and `npm run demo`. Treat any provider event after terminal budget exhaustion, a ledger total that does not equal the sum of recorded calls plus reserve, missing warning/degradation events, or a resume that lowers spent credits as release-blocking.

Inspect `artifacts/hard-budget/cost-ledger.jsonl`, `decision-log.jsonl`, `provider-events.jsonl`, `workflow-state.json`, and `partial-result.md`.

## Contain

For a real incident, disable new work claims for the affected run, retain the run ID and budget ledger, and block further provider/tool calls at the policy enforcement point. Do not restart the agent with a new budget or a new identity to bypass the cap.

In this lab, `npm run clean` resets generated local artifacts only.

## Recover

1. Load the persisted ledger and confirm the request/workload identity.
2. Reconcile provider usage and any unrecorded in-flight calls before releasing new budget.
3. Return the partial-result packet to a human owner.
4. Require an explicit decision to add budget, narrow scope, provide a better source, or end the run.
5. Resume only with the persisted ledger and a documented policy change.

## Verify

Recovery is verified when the ledger is traceable, provider events match the expected call count, no new call occurs after a hard cap, and the handoff names completed and skipped work. A human must own any budget expansion or public consequence.

## Escalate

Escalate if provider billing cannot be reconciled, a cap is bypassed, an unknown caller resets state, an organization-wide budget is affected, or customer/production data is involved. This repository is a local reference, not a billing or incident-response system.
