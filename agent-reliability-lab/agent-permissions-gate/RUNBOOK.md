# Runbook

## Detect

Run `npm test` and inspect `artifacts/audit.jsonl` after `npm run demo`.

Treat any unexpected `allow`, a missing audit event, an unrecognised reason code, or a publication count above one as a release-blocking defect. The `decision-report.md` should show one allowed publish and denials for direct publish, replay, target substitution, and expiry.

## Contain

For a real agent, immediately disable write-capable tools or revoke the affected principal/grant. Preserve audit events and the correlation IDs before retrying work. Do not attempt to correct a public or consequential side effect through the same unrestricted agent.

In this lab, run `npm run clean` to remove generated local artifacts and reset the fixture state.

## Recover

1. Identify the policy rule and request correlation ID associated with the unexpected decision.
2. Confirm whether the action was allowed by policy, by a grant, or by an implementation bypass.
3. Correct the gateway/policy/grant validation—not the agent prompt.
4. Add a deterministic fixture reproducing the fault.
5. Run `npm test`, then `npm run demo`, and inspect the new audit trail.

## Verify

The fix is verified only when the unsafe fixture is denied by the gateway, the audit event records the reason, and the mock CMS state proves no unintended publish happened. A human should review the generated `run-summary.json` before calling the result a release candidate.

## Escalate

Escalate immediately if a live credential, production account, personal data, or real external action is involved. This repository is intentionally not a live-incident response tool and should not be pointed at a production CMS.
