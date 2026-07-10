# Week 0 readiness controls

These controls are engineering-only. They do not authorize an observed dry
run, a technical GO certificate, Shadow Week 1, publication, or citation.

## Artifact modes

- `dataset_kind=synthetic` renders `SYNTHETIC KAPI PROTOTYPE — NOT AN
  OFFICIAL OR PUBLISHED INDEX`.
- `dataset_kind=observed` renders `UNPUBLISHED KAPI SHADOW — NOT AN OFFICIAL OR
  PUBLIC INDEX`.
- Both modes force `not_for_publication=true`, `published=false`,
  `deployed=false`, and `citation.permitted=false`.

## Base-period lifecycle

The calculator can apply a versioned `base_eligibility` policy. The approved
configuration lists `withheld_concentration` as noncounting. The lifecycle
layer also refuses a `final_base` vintage unless it receives exactly thirteen
`counting` states. Incomplete, unsigned, irreproducible, materially corrected,
and concentration-withheld candidates must be represented as noncounting.

Weeks before base completion remain `pending_base` with no index level. A
provisional base, final base, weekly release, or correction is a new immutable
vintage. T+4 finalization changes state by appending a release; it never
overwrites an earlier row and does not invent additional observation weeks.

## Human gate

A `final` or `corrected` lifecycle release requires signoffs for:

- `primary_operator`;
- `independent_reviewer`; and
- `methodology_owner`.

At least two distinct human approvers are required. This is only a database
control; appointment evidence and operational independence remain governance
prerequisites outside the code.

## Robustness suite

The calculation artifact includes the governed core suite:

1. arithmetic/geometric aggregation;
2. absolute-cheapest frontier;
3. lowest-cost independent-triple mean;
4. editorial weights;
5. ECI thresholds;
6. the 3x3 payload grid;
7. leave-one-task-out;
8. leave-one-provider-out;
9. leave-one-creator-out;
10. first-party-only; and
11. constant-universe entry/exit.

Every exclusion run records `structural_fragility` and the incomplete week IDs.
First-party status is never inferred: an endpoint participates only when its
record explicitly contains `first_party: true`.

## Independent check

`python3 -m kapi independent-check --calculation CALCULATION.json` invokes
`kapi.independent`, which does not import the primary calculation engine. It
re-enumerates independent triples and recomputes medians, basket costs, and
index levels. Exact selection and cost equality are required; index differences
must be at most 0.01 points. This engineering implementation does not replace
the appointed Independent Reviewer.

## Append-only commands

The following commands operate only on local SQLite files:

```text
python3 -m kapi register-methodology --database DB --method METHOD.json \
  --effective-from 2026-07-03T20:00:00Z
python3 -m kapi append-vintage --database DB --envelope ENVELOPE.json
python3 -m kapi record-incident --database DB --incident INCIDENT.json
```

The commands have no network, deployment, or publication path.
