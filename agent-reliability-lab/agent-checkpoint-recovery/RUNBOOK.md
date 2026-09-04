# Runbook

## Detect

Run `npm test` and `npm run demo`. Treat a reused checkpoint with a changed input, missing artifact, or fingerprint mismatch as a release-blocking defect. A crash/restart scenario must never recompute unchanged completed stages or skip an invalid stage.

Inspect `artifacts/crash-recovery-report.md`, the scenario's `workflow-state.json`, and its `transition-log.jsonl`.

## Contain

For a real workflow, stop duplicate workers from claiming the same run and preserve the run ID, input version, checkpoint state, artifact references, queue records, and logs. Do not restart a job by deleting state until the validity of completed side effects has been assessed.

In this lab, `npm run clean` resets generated local artifacts only.

## Recover

1. Load the latest checkpoint state for the workflow run.
2. Recompute expected inputs in stage order and validate each artifact/fingerprint.
3. Invalidate the first bad checkpoint and every downstream checkpoint.
4. Resume from that stage with the original run identity and current input version.
5. If the run has external effects, reconcile them through a separate idempotent-action control before issuing a new action.

## Verify

Recovery is verified when the transition log shows unchanged upstream stages reused, invalid/downstream stages recomputed, and the final state includes a valid `ready-for-review` checkpoint. A human owner should review any public-facing output before publication.

## Escalate

Escalate when state cannot be read, artifacts cannot be validated, two workers may have run concurrently, an external side effect is ambiguous, or any production data/credential is involved. This repository is a local reference, not a live workflow-recovery tool.
