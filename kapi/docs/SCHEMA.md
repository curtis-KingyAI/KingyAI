# KAPI append-only schema

`001_initial.sql` is the local prototype foundation for the Kingy AI Price
Index. It stores exact endpoint identity, frozen evidence, native billing-token
counts, weekly prices, corrections, methodology versions, calculations, and
release lineage. It is a research data store; it does not publish a release or
modify WordPress.

## Store API

The public Python API is intentionally small and uses only the standard
library:

```python
from kapi.store import dump_bundle, ingest_bundle, init_database

connection = init_database("work/kapi.sqlite3")
ingest_bundle(connection, bundle)
canonical_bundle = dump_bundle(connection)
connection.close()
```

`init_database(path_or_connection)` accepts a filesystem path or a fresh
`sqlite3.Connection`, enables foreign keys, applies schema version 1
idempotently, and returns the configured connection. A path opened by the
function must be closed by the caller.

`ingest_bundle(connection, bundle)` validates all contract fields before
writing and uses a SQLite savepoint. A constraint failure rolls back every row
from the attempted bundle. Re-ingestion is deliberately rejected: the store is
an append-only ledger, not an upsert target.

`dump_bundle(connection)` returns a dictionary. Weeks are ordered by cutoff;
identity and fact lists are ordered by their stable IDs; token counts are
ordered by endpoint, profile, and size variant; feature names are sorted. The
synthetic hand fixture round-trips exactly, including its clearly labeled
fixture metadata.

The database holds one input dataset because `dump_bundle` has no dataset
selector. A second dataset fails the `datasets.singleton` constraint. Separate
SQLite files provide simple isolation between frozen vintages in this
prototype.

## Source-to-release lineage

| Layer | Tables | Purpose |
|---|---|---|
| Dataset calendar | `datasets`, `weeks` | Dataset identity, observed/synthetic status, and weekly cutoffs |
| Exact identity | `providers`, `creators`, `models`, `endpoints`, `endpoint_features` | Separates billing provider from model creator and pins version, configuration, region, tier, reasoning mode, tokenizer, features, and availability |
| Evidence facts | `source_artifacts`, `capability_evidence`, `token_counts`, `price_observations` | Retains source hashes/licenses, capability vintage, frozen-payload billing counts, and typed weekly tariff observations |
| Exception ledger | `incidents`, `corrections` | Records detected issues, replacement facts, approval, and correction vintages without changing old rows |
| Method definition | `methodology_versions`, `methodology_base_weeks`, `methodology_thresholds`, `task_profiles`, `task_profile_features`, `methodology_sensitivities` | Pins calendar/policies, base weeks, qualification gate, rational profile weights, payload hashes, settings, graders, and sensitivities |
| Frozen inputs | `weekly_snapshots`, `snapshot_inputs` | Connects a cutoff to an immutable manifest and the exact input IDs/hashes |
| Calculation | `calculations`, `calculation_profile_results`, `calculation_selected_endpoints`, `calculation_validations` | Links code/environment/method versions to profile results, selected triples, price setters, index values, and validation outcomes |
| Release | `releases`, `release_artifacts`, `release_signoffs`, `correction_releases` | Links a dated vintage to one calculation, permanent hashed artifacts, approvals, superseded releases, and corrections |

The intended lineage is:

```text
source artifact -> immutable observation -> weekly snapshot
                -> versioned calculation -> immutable release
                -> later correction/replacement rows when required
```

Polymorphic snapshot input IDs cannot have a single SQL foreign key, so
`snapshot_inputs.input_kind` is constrained and every input carries a SHA-256
digest. All non-polymorphic relationships use foreign keys.

## Bundle normalization

The required top-level contract keys are:

```text
schema_version, dataset_id, dataset_kind, weeks, providers, creators, models,
endpoints, source_artifacts, capability_evidence, token_counts,
price_observations, corrections
```

Methodology is a separate versioned document. Extra fixture/provenance keys are
stored as canonical `metadata_json` and returned unchanged. Core fields remain
normal columns so eligibility and price queries never depend on parsing
free-form JSON. Record-level forward-compatible fields are treated the same
way. The schema already has optional normalized columns for model
release/retirement and modality, source effective time/reviewer, capability
interval/threshold/qualification dates, and price cache/batch/priority/tool-fee
treatment.

Endpoint feature arrays are normalized into `endpoint_features`. The bundle's
feature array is reconstructed in sorted order. Token-count identity is
`(endpoint_id, profile_id, size_variant)`; the stable fixture ID is required and
also unique.

First-party ownership is an explicit endpoint field (`first_party: true` or
`false`) retained in canonical metadata. The robustness calculation never
infers ownership from provider or creator names; a missing value is treated as
unverified and excluded from the first-party-only sensitivity.

## Types and validation

- Money, index values, costs, capability scores, score intervals, and
  thresholds use SQLite `TEXT`. `kapi.store` parses them with
  `decimal.Decimal`, rejects non-finite/negative values where applicable, and
  preserves the source spelling (for example, `"2.500"`). Binary floats are
  never introduced.
- Timestamps are ISO-8601 UTC strings ending in `Z`. The store validates the
  timestamp, not only the suffix.
- Content and payload hashes are lowercase 64-character SHA-256 hex strings.
- Booleans are constrained to `0` or `1` in SQLite and must be actual JSON/Python
  booleans at ingestion.
- Evidence grades A, B, C, and D are retained for auditability. Grade D is
  invalid for calculation; the validator/calculator must exclude it rather
  than erasing the record.
- Observed source URLs must be HTTP(S). A `synthetic:` URI is accepted only in
  an explicitly synthetic dataset.
- Model, endpoint, source, capability, token-count, and price natural identities
  have uniqueness constraints. Conflicting prices for one applicability key
  must form a single explicit supersession chain.
- Capability model/configuration and token-count billing tokenizer must match
  their endpoint. All endpoints for a profile/size pair must reference the same
  canonical input and output payload hashes.

SQLite checks provide a second line of defense beneath Python validation.
Foreign keys are enabled on every initialized connection.

## Append-only and corrections

Every domain and lineage table has both a `BEFORE UPDATE` and `BEFORE DELETE`
trigger. Direct mutation raises an SQLite error whose message begins
`append-only:`. Inserts remain possible.

A tariff correction therefore works as follows:

1. Retain the original `price_observations` row.
2. Insert a replacement observation whose `supersedes_observation_id` points to
   the original.
3. Insert a `corrections` record linking the superseded and replacement IDs,
   the impact/resolution, approval, and new vintage.
4. If a release was affected, insert a new release and connect it through
   `correction_releases`; never edit or delete the old release/artifacts.

Self-supersession and supersession cycles are rejected. Exact repeated price
facts are unique, while a different replacement value is allowed only when the
bundle supplies an explicit chain.

Finalization follows the same rule: it is a new release row, optionally using
`supersedes_release_id`, with new artifact and sign-off rows.

## Operational boundaries

- The schema is prototype version 1 (`PRAGMA user_version = 1`); future changes
  require a new migration, not edits to a deployed database.
- Initialize a supplied connection outside an active transaction so SQLite can
  enable foreign keys.
- The store validates data integrity and lineage shape. Headline eligibility,
  Grade-A-only selection, concentration withholding, and index calculation
  belong to the validator/calculation modules.
- Canonical serialized JSON uses UTF-8, sorted keys, compact separators, and a
  final newline at the file-writing boundary. `dump_bundle` returns the object;
  the caller controls file output.
