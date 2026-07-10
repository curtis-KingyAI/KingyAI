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
- methodology values outside the approved D1–D20 defaults.

### Calculation

The engine has no I/O. It uses Decimal arithmetic and:

1. qualifies exact immutable endpoints;
2. applies capability, commercial, evidence, feature, and context rules;
3. selects applicable input/output tariff tiers;
4. computes endpoint/profile costs;
5. enumerates independent provider/creator triples;
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
spending, notice, and implementation metadata.
Files not declared by that manifest are rejected during reproduction.

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
