# Runbook

## Detect

Run `npm test` and `npm run demo`. Treat any constrained hard failure, missing trace, changed fixture version without a scorecard update, a candidate marked ready despite a violation, or a subjective score affecting the release gate as release-blocking.

Inspect `artifacts/scorecard.json`, relevant files under `artifacts/traces/`, and `failure-taxonomy.md`.

## Contain

For a real editorial workflow, prevent the candidate from entering the CMS review/publish path, preserve its source snapshot and trace, and route it to an editor when the disposition is `blocked` or `escalate`. Do not relax a hard rule to make a release appear green.

In this lab, `npm run clean` resets generated local artifacts only.

## Recover

1. Read the exact violation codes and fixture/source version.
2. Determine whether the candidate, source snapshot, evaluator rule, or editorial policy changed.
3. Correct the candidate strategy or fixture expectation with an explicit review.
4. Add a deterministic regression fixture if the behavior was not already covered.
5. Re-run the full suite and inspect the per-case trace before releasing.

## Verify

The candidate may proceed only when every hard assertion passes, each case has a trace, the scorecard release gate is true, and any required escalation is routed to a named human owner. A subjective quality score cannot alter this result.

## Escalate

Escalate if source provenance is unavailable, a material claim is legally/reputationally sensitive, policy expectations conflict, a live publication may already have occurred, or customer/private data is involved. This repository is a local reference, not an editorial publishing system.
