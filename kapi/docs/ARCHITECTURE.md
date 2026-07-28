# Architecture

## Objective

The prototype proves that KAPI can calculate a reproducible posted-price
standard-workload index without using mutable WordPress postmeta as its system
of record.

## Data flow

Official-source or synthetic evidence enters an append-only normalized bundle.
Validation checks identities, source hashes, evidence grades, prices, tiers,
payload hashes, billing token counts, capability evidence, and cross-record
references. The pure calculation engine selects eligible endpoints and produces
weekly diagnostic results. The exporter freezes inputs and emits versioned CSV,
JSON, release, and provenance files. Reproduce verifies every hash and
recalculates the release from the frozen inputs.

## Components

### Immutable input domain

- provider, creator, model, and endpoint identities;
- exact immutable model/configuration identity;
- source artifacts with retrieval time, grade, URL, and SHA-256;
- append-only price observations with effective/observed times and
  supersession links;
- capability evidence pinned to model, endpoint configuration, source, metric
  version, and data vintage;
- frozen canonical profile payloads and endpoint billing-token counts;
- correction records linking superseded and replacement observations.

### SQLite research store

The SQL schema normalizes the bundle and installs UPDATE/DELETE blocking
triggers on immutable evidence tables. Imports are transactional. An existing
ID with different canonical content is a conflict, not an update.

SQLite is a local prototype choice, not a production infrastructure decision.

### Validation

Validation fails closed on:

- missing or duplicate IDs;
- broken references;
- non-Decimal money;
- malformed UTC timestamps or SHA-256 values;
- retained source bytes that do not match their declared hashes;
- unknown evidence grades;
- inconsistent source/observation/capability grades or endpoint links;
- unresolved conflicting prices;
- cyclic/branching supersession and invalid correction-envelope linkage;
- mismatched tokenizers or payload hashes;
- invalid payload-grid IDs or alternate payload files;
- wrong basket weights/counts;
- missing canonical payloads;
- methodology values outside the adopted D1–D20 defaults.
- undeclared object keys/paths in a closed v0.3.0 bundle schema; future fields
  require a new schema vintage;
- any wrong container/leaf type or public string outside its path-specific
  exact value, enum, hash, timestamp, reference, or narrow identifier grammar,
  including arbitrary license/status prose and non-ASCII values;
- caller `expected_result` oracles, caller `generation` spend/provider metadata,
  or a methodology binding that differs from the exact committed document;
- any canonical object hash other than the deterministic bounded forward
  fixture; dynamic and observed inputs require a new reviewed vintage;
- percent, HTML-entity, or literal-codepoint encoding that remains after the
  bounded four-round claim decoder;
- normalized claim-bearing keys, assertion-like governance/publication prose,
  same-record cross-depth splits, or subject-bearing list splits anywhere in a
  v0.3.0 bundle; no current claim-key allowlist entries exist.

### Calculation

The engine has no I/O. It uses Decimal arithmetic and:

1. qualifies exact immutable endpoints;
2. applies capability, commercial, evidence, feature, and context rules;
3. selects applicable input/output tariff tiers;
4. computes endpoint/profile costs;
5. enumerates three-provider/three-creator-diverse triples;
6. selects by median, total, and endpoint-ID tie-breaks;
7. aggregates six equal-weight profiles;
8. establishes the first 13 complete weeks as base;
9. calculates headline diagnostics and sensitivities;
10. applies coverage and concentration withholding rules;
11. returns observation/source lineage.

### Export and reproduction

The release directory contains:

- frozen dataset and methodology inputs;
- calculation.json;
- release.json;
- latest.json;
- history.csv;
- components.csv;
- provenance-manifest.json.

The manifest hashes all other files and implementation modules, and records the
verified content hash for every source artifact used. Reproduce rejects
missing/changed bytes, compares a fresh calculation/render byte-for-byte, and
regenerates the complete manifest to authenticate its identity, lineage,
spending state, notice, and implementation metadata. Spending is explicitly
`not_measured_not_evidenced` with scope
`artifact_generation_spend_and_provider_activity_not_bound`; caller bundle
metadata cannot convert that into a zero-spend or provider-activity claim.
Files not declared by that manifest are rejected during reproduction.
The release ID is derived from the frozen dataset, exact methodology, and
mathematical calculation content. Governance/presentation fields are excluded
from that identity, so a later governance event cannot create a different ID
for the same mathematical release. The release-artifact membership set and
methodology/snapshot/calculation child sets freeze when their parent is bound
to governance.
Export also treats its caller as untrusted: it recomputes the validation report
and the complete calculation from the frozen inputs, requiring canonical byte
equality before any caller-supplied object is copied into an artifact. The
forward methodology must exactly match its committed document. Forward input
bundles reject every undeclared object key/path, wrong container/leaf type, and
value outside the closed path grammar. Recursive normalized claim-key/prose,
encoded-value, and split-carrier scans remain defense in depth; export repeats
the checks before writing frozen input bytes. A final canonical fixture-identity
gate rejects even in-grammar permutations or hash-repaired embedded snapshots.
The active calculation and renderer admit only the exact current v0.3.0 pair.
Legacy v0.2.x validation is restricted to the pinned historical bundle and
methodology hashes, while exact historical release evidence is reproduced from
its corresponding old code checkout. Current code cannot render a fresh legacy
artifact, including after coordinated schema/dataset/method marker rewrites.
Manifest construction separately rerenders the complete seven-file payload
and refuses any filename or byte mismatch, so a caller cannot obtain provenance
for a forged release/latest document, release ID, or CSV.

## Determinism

- JSON uses UTF-8, sorted keys, compact separators, and a final newline.
- CSV uses a fixed column order and LF line endings.
- Money and scores remain decimal strings.
- IDs and full-precision Decimal costs break ties.
- No wall-clock timestamp enters calculated content; fixture cutoffs provide
  deterministic release time.
- No network, environment secret, model response, or random value is used.

## Publication boundary

There is no WordPress adapter, scheduler, HTTP POST, deployment configuration,
or publishing credential. A later consumer would read a signed/frozen release
artifact after separate authorization and publication gates.

Governance policy v1.0.0 separates calculation disposition, methodology/release
assurance, and publication authorization in schema v2. The local process has
neither a trusted operator-identity adapter nor a cryptographic signature-
verifier adapter. Current output is therefore unreviewed/not-authorized, with
stronger edges unreachable. The local ContextVar/SQLite binding protects test
flow integrity only; it is not an identity security boundary.

Reviewer credentials are versioned as append-only registration, supersession,
and revocation events. A review signs and stores one exact event ID; later
signature claims and modeled claim-bearing transitions require that event to
remain the latest active state at their own timestamp. Public attribution shows
both the bound event and latest event, including revocation, rather than hiding
post-review credential changes.
