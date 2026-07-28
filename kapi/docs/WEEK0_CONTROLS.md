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

The calculator can apply a versioned `base_eligibility` policy. The configured
configuration lists `withheld_concentration` as noncounting. The lifecycle
layer also refuses a `final_base` vintage unless it receives exactly thirteen
`counting` states. Incomplete, unsigned, irreproducible, materially corrected,
and concentration-withheld candidates must be represented as noncounting.

Weeks before base completion remain `pending_base` with no index level. A
provisional base, final base, weekly release, or correction is a new immutable
vintage. T+4 finalization changes state by appending a release; it never
overwrites an earlier row and does not invent additional observation weeks.

## Governance gate

New lifecycle rows enter as `draft` and are immediately bound to the exact
release/content and artifact set under the current unreviewed label. The local
actor and role rows record test provenance only; they do not prove that a human
operator reviewed the release. Legacy free-text signoffs cannot authorize a
transition. Policy v1.0.0 has neither a trusted operator-identity adapter nor a
trusted signature-verifier adapter, so no operator-review label,
external-review label, `ready` state, or publication eligibility is reachable
in this vintage.

## Robustness suite

The calculation artifact includes the governed core suite:

1. arithmetic/geometric aggregation;
2. absolute-cheapest frontier;
3. lowest-cost three-provider/three-creator-diverse mean;
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

## Implementation-isolated secondary check

`python3 -m kapi secondary-check --calculation CALCULATION.json` invokes a
module that does not import the primary calculation engine. It re-enumerates
provider/creator-diverse triples and recomputes medians, basket costs, and index
levels. Exact selection and cost equality are required; index differences must
be at most 0.01 points. This is a technical cross-check, not human review.

The lifecycle API never copies an arbitrary caller object into this diagnostic.
Policy v1.0.0 records `status=not_supplied` when the field is absent and rejects
every non-null caller-supplied report, including actual checker output. A
hard-coded `status`, week count, maximum difference, or empty discrepancy list
cannot establish that recalculation ran. The standalone CLI output therefore
remains non-authorizing and is never copied into lifecycle diagnostics.

A future reviewed path must run the checker internally from the exact full
frozen calculation, bind both calculation and report hashes, and preserve the
result as first-class evidence. Schema or numeric consistency alone is not
proof of execution.

Caller calculation diagnostics use an exact empty-object schema: no caller key
or value is copied into the stored diagnostic. As defense in depth, the boundary
also scans nested keys and strings after Unicode normalization, case folding,
combining-mark removal, and common-confusable folding before rejecting claim
language. Base-week states come only from a controlled enum.

The v0.3.0 dataset bundle has a separate forward-input guard. Exact object-key
sets and a complete container/leaf type and scalar grammar reject undeclared
fields, type substitutions, arbitrary prose, non-ASCII values, and unclassified
identifiers; future changes require a new bundle-schema vintage. Bounded
percent/HTML/literal-escape decoding rejects residual encodings. After
Unicode/confusable normalization, recursive claim scans and record/list
aggregation remain defense in depth. The exporter reruns these guards before
rendering `inputs/dataset.json`; bare reviewer/auditor/signatory metadata,
go-live synonyms, result oracles, and caller spend metadata cannot bypass the
forward contract.

The SQLite insert trigger separately requires the exact canonical stored
policy-v1 document and validates release/base states plus calculation-status
disposition. The initial governance-binding trigger revalidates the stored row.
Tests exercise raw SQL with and without actor context, malformed and
noncanonical JSON, aliases/extras, false labels and secondary flags,
status/disposition and release/base mismatches, a mismatched binding
disposition, an unregistered validator, and direct binding of a nonconforming
schema-v1 row. No diagnostic backfill occurs: the legacy row stays readable but
cannot acquire a policy-v1 binding.

## Append-only commands

The following commands operate only on local SQLite files:

```text
python3 -m kapi register-methodology --database DB --method METHOD.json \
  --effective-from 2026-07-03T20:00:00Z \
  --implementation-commit FULL_COMMIT_SHA \
  --review-artifact-manifest-sha256 REVIEW_MANIFEST_SHA256
python3 -m kapi record-incident --database DB --incident INCIDENT.json
```

The public CLI exposes no governance mutation or publication path. Lifecycle
mutation remains an internal trusted-adapter development API; the hard-failed
verifier gate still prevents external claims/readiness.
