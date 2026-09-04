# Checkpoint recovery report

| Forced crash after | Reused on restart | Completed on restart |
| --- | --- | --- |
| collect | collect | extract, outline, draft, fact-check, ready-for-review |
| extract | collect, extract | outline, draft, fact-check, ready-for-review |
| outline | collect, extract, outline | draft, fact-check, ready-for-review |
| draft | collect, extract, outline, draft | fact-check, ready-for-review |
| fact-check | collect, extract, outline, draft, fact-check | ready-for-review |
| ready-for-review | collect, extract, outline, draft, fact-check, ready-for-review | none |

## Input-change scenario

When a source document changed, the runner reused: collect.
It recomputed: extract, outline, draft, fact-check, ready-for-review.

A checkpoint is reused only when its stored input fingerprint and output artifact both verify.
