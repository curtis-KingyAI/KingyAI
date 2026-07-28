# KAPI append-only schema

`001_initial.sql` is the local prototype foundation for the Kingy AI Price
Index. It stores exact endpoint identity, frozen evidence, native billing-token
counts, weekly prices, corrections, methodology versions, calculations, and
release lineage. It is a research data store; it does not publish a release or
modify WordPress.

`002_governance.sql` is a forward-only migration. It separates calculation,
methodology/release assurance, and publication authorization; adds stable
controller identities, appointed roles, append-only reviewer registration,
supersession and revocation events, review/disclosure records, exact release
bindings, signature-verification claim records, and append-only state events.
Legacy `release_signoffs` rows remain readable but cannot authorize a schema-v2
transition.

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
`sqlite3.Connection`, enables foreign keys, applies schema versions 1 and 2
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
| Release | `releases`, `release_artifacts`, `correction_releases` | Links a dated vintage to one calculation, permanent hashed artifacts, superseded releases, and corrections |
| Historical signoffs | `release_signoffs` | Retained only for old-vintage reads; never a governance-v2 authorization source |
| Governance | `governance_principals`, `governance_role_assignments`, `external_reviewer_registry`, `external_reviewer_registry_events`, `methodology_governance_gates`, `release_governance_bindings`, `external_review_records`, `signature_verification_attestations`, `governance_transition_events` | Separates identity/appointment, reviewer credential lifecycle, methodology assurance, exact-release assurance, and publication authorization |

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
`supersedes_release_id`, with new artifacts and governance transition events.

## Governance-v2 hard boundary

The private local actor binding is test/adapter scaffolding, not
authentication. Schema v2 creates each bound release in `unreviewed`, exposes
no `unreviewed -> operator_reviewed` edge, constrains
`trusted_verifier_gate` to `failed`, and exposes no publication transition.
This makes operator-review, external-review, and publication claims unreachable
until a later reviewed migration adds trusted operator identity and separate
cryptographic signature verification.

Methodology child rows freeze after a governance gate exists; snapshot inputs
freeze after their snapshot is used by a calculation; calculation result,
selected-endpoint, and validation rows freeze after release binding; and
release artifacts/signoffs/correction links freeze after governance binding.
The binding trigger recomputes the exact release-artifact membership digest
through a registered SQLite function, so the stored digest cannot describe a
different set. Methodology review records additionally bind the exact
methodology ID, version, document hash, implementation commit, and review-
artifact manifest hash.

`external_reviewer_registry` retains the immutable initial registration.
`external_reviewer_registry_events` begins with a matching sequence-1 event and
then accepts only a single sequence of registrar-recorded `supersession` or
`revocation` events. Updates, deletes, self-recording, stale predecessors,
backdated chronology, no-op supersessions, and revocations that alter the prior
credential snapshot are rejected. A review stores `reviewer_registry_event_id`
for the latest active event at `reviewed_at`. Signature claims and all modeled
future external/ready transitions recompute latest-event eligibility at their
own timestamps, so a rotated or revoked key cannot advance a stale record.

Both modeled publication-ready branches require the release binding's
`code_commit` to equal the methodology gate's `implementation_commit`, in
addition to exact review and artifact bindings. The exact-release branch does
not receive a weaker commit rule than the routine branch.

Lifecycle calculation diagnostics accept an exact empty caller object; every
stored key is lifecycle-owned. Nested caller keys and strings are also scanned
after Unicode normalization as defense in depth. Base-week states are controlled
enum values. Policy v1.0.0 rejects every non-null caller-supplied secondary
recalculation report, including exact checker output, and stores only a
`not_supplied` lifecycle diagnostic. A future schema must represent internally
recomputed, exact-calculation-hash-bound evidence rather than trusting caller
status or count fields.

Schema v2 also validates the exact stored diagnostic below the Python lifecycle
API. `init_database` registers `kapi_validate_calculation_diagnostics` on the
connection. `calculations_diagnostics_guard` requires its result to be exactly
`1` before every new calculation insert; malformed or noncanonical JSON,
aliases/extras, false review/publication/secondary fields, invalid release/base
states, and status/disposition mismatches fail. The
`release_governance_bindings_guard` separately repeats the validation against
the calculation selected through the release and rejects a binding disposition
that differs from the status-derived disposition. The SQL predicates use `IS
NOT 1`, and a connection that never registered the function cannot execute
either write, so missing validator registration fails closed.

Migration is append-only: applying schema v2 to a schema-v1 database does not
scan, rewrite, infer, or backfill `calculations.diagnostics_json`. Existing rows
remain available as historical evidence. If an existing document does not equal
the exact policy-v1 lifecycle document, its release cannot receive a policy-v1
governance binding. This deliberate quarantine avoids turning legacy prose or a
missing field into a newly asserted review state. New rows are guarded from the
moment schema v2 is installed.

These are application-integrity controls, not a security boundary against the
owner of the SQLite file or host process. A production ledger must move trusted
identity, signature verification, and durable audit authority outside the
publisher-controlled process.

## Operational boundaries

- The schema is prototype version 2 (`PRAGMA user_version = 2`); future changes
  require a new migration, not edits to a deployed database.
- Initialize a supplied connection outside an active transaction so SQLite can
  enable foreign keys.
- Use `init_database` for every write-capable connection; opening the SQLite
  file directly leaves the required validator unregistered and governed writes
  fail closed.
- The store validates data integrity and lineage shape. Headline eligibility,
  Grade-A-only selection, concentration withholding, and index calculation
  belong to the validator/calculation modules.
- Canonical serialized JSON uses UTF-8, sorted keys, compact separators, and a
  final newline at the file-writing boundary. `dump_bundle` returns the object;
  the caller controls file output.
