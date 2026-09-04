# Threat model

## Asset and safety objective

The protected asset is editorial integrity: a draft should not reach a reviewer or public workflow with unsupported claims, false citations, stale pricing presented as current, omitted material limits, hidden disclosure, unmarked uncertainty, or an ignored escalation boundary.

## Trust boundaries

| Boundary | Treated as | Control |
| --- | --- | --- |
| Source snapshots | Frozen, versioned test input | Case-specific allowed claim/citation/date expectations. |
| Candidate draft | Potentially polished but unsafe output | Deterministic hard assertions before disposition. |
| Subjective quality judgment | Optional non-authoritative signal | Excluded from the release gate. |
| Review packet | Editor handoff evidence | Includes only hard-passing constrained traces and labels escalation. |
| Fixture version | Change-management input | Stored in trace and scorecard metadata. |

## Failure actions demonstrated

- State an unsupported market or performance claim.
- Cite a missing or wrong source.
- Present a historical price as current.
- Omit a material limitation or disclosure.
- Use malformed source data.
- Ignore conflicting sources, uncertain availability, an out-of-scope request, or a sponsor claim that needs review.
- Duplicate a content section.

## Non-goals and residual risk

- Fixtures cannot prove a claim is true on the live web or current after the snapshot date.
- Structured fields can be deliberately misrepresented by a malicious system; production needs source capture, review, and trace integrity controls.
- This is not legal/compliance advice, a plagiarism system, a factuality guarantee, or a model evaluation benchmark.
- No model, browser, CMS, external source, customer data, or production publication is used.

Production use requires maintained source snapshots, editorial policy ownership, robust citation capture, change review, human sign-off, and evaluation expansion based on real observed failure modes.
