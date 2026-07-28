# KAPI prototype data contract

This file fixes the internal contract shared by the isolated prototype modules.
It is not a production API and does not authorize publication.

## Design constraints

- Python standard library and SQLite only.
- Decimal strings for money and scores; never binary floating point.
- ISO-8601 UTC timestamps ending in Z.
- Canonical JSON is UTF-8, sorted keys, compact separators, and a final newline.
- All primary facts use stable string IDs.
- Source evidence and price observations are append-only.
- Grade A is the only live/headline evidence. B/C are research-only. D is invalid.
- Dynamic reasoning is disabled; endpoint-specific billing-token counts are supplied
  explicitly and tied to frozen payload hashes.
- Calculation, governance assurance, and publication authorization are separate
  axes. A technical recalculation is not a human external review.
- Policy v1.0.0 has no trusted operator-identity adapter and hard-fails the
  trusted-verifier gate. Only the exact unreviewed/not-authorized envelope may
  be exported; caller-supplied identity, attribution, or nested week fields
  cannot enable an operator, external-review, or publication claim.
- Every named review binds the exact latest active append-only reviewer-registry
  event. Supersession and revocation are new rows, and stale registry events
  cannot satisfy modeled signature or future transition gates.
- Caller diagnostics use an exact empty-object schema; all stored diagnostic
  keys are lifecycle-owned. Normalized recursive content checks and controlled
  base-week states provide additional claim barriers. Policy v1.0.0 rejects
  every caller-supplied secondary report and records only `not_supplied`. The
  standalone checker is a non-authorizing tool; a future lifecycle path must
  recompute internally from and hash-bind the exact full frozen calculation.
- New calculation rows must also pass the registered canonical policy-v1
  diagnostics validator in SQLite. Initial release binding separately
  revalidates the stored calculation, so direct SQL, missing validator
  registration, and nonconforming pre-migration rows fail closed.
- The v0.3.0 bundle uses exact allowed object-key sets and a closed
  path-specific scalar grammar. Every string path is classified as an exact
  safe value, closed enum, SHA-256, timestamp, reference, or narrow machine
  identifier; an unclassified string fails. Arbitrary source/license/status
  prose, `expected_result` caller oracles, and `generation` spend/provider
  metadata are not bundle fields. Values and keys are ASCII-only in this
  vintage. A four-round percent/HTML/literal-escape decoder rejects decoded
  claims and any residual encoding syntax rather than decoding without bound.
  Recursive claim-key/prose and record/list aggregate scans remain an additional
  barrier. Source media types are fixed to `application/json` for this fixture.
  The canonical object hash must also equal the reproducible generated forward
  fixture. Future fields, prose, character sets, grammars, dynamic data, or
  observed data require a new reviewed bundle-schema vintage. The calculator
  and exporter repeat the contract before output.
- Current calculation/render/export is v0.3.0-only and requires the exact
  canonical bundle plus exact committed methodology. The v0.2.x documents and
  fixture are accepted only as hash-pinned, read-only compatibility inputs;
  their releases are reproduced from their pinned historical code vintages,
  never newly rendered by current code. Coordinated schema, dataset, and
  methodology-marker rewrites do not create a downgrade lane.

## Bundle shape

The forward top-level bundle has exactly:

- schema_version
- dataset_id
- dataset_kind: synthetic or observed
- weeks: ordered objects with id and cutoff_at
- providers
- creators
- models
- endpoints
- source_artifacts
- capability_evidence
- token_counts
- price_observations
- corrections
- methodology

It has no `expected_result` or `generation` field. The committed v0.3.0
projection is synthetic-only; historical v0.2.x fixtures remain byte-immutable
and are validated under their original contracts. Base-period IDs and the
current week are derived from ordered weeks plus the pinned methodology; their
redundant historical input fields are deliberately absent.

Identity objects use stable IDs. A model declares creator_id, exact version,
alias_type, and immutable_version. An endpoint declares provider_id, model_id,
commercial/availability flags, region, tier, reasoning mode, billing tokenizer,
tokenizer reproducibility, and supported feature names.

Each source artifact includes id, URL, retrieved_at, evidence_grade, media_type,
content_sha256, snapshot_path, and license_note.

Each capability record includes id, model_id, endpoint_id, metric, metric_version,
score, configuration_id, evaluated_at, data_vintage, source_id, and evidence_grade.

Each token-count record includes endpoint_id, profile_id, input_tokens,
output_tokens, input_payload_sha256, output_payload_sha256, billing_tokenizer,
and size_variant. The headline variant is 100x100.

Each price observation includes id, endpoint_id, week_id, component,
amount_per_million, currency, unit, region, tier, context_min_tokens,
context_max_tokens, effective_at, observed_at, source_id, evidence_grade,
and optional supersedes_observation_id.

## Methodology shape

The methodology document has:

- methodology_id and version
- claim and scope
- calendar
- base_period_weeks
- capability thresholds
- evidence policy
- eligibility requirements
- selection and tie-break rules
- concentration thresholds
- profile definitions
- headline equal weights as rational numerator/denominator values
- editorial-weight and payload-size sensitivities
- correction/finalization policy

Each profile includes id, name, count, rational weight, construction targets,
payload paths and hashes, and required endpoint features.

## Calculation behavior

For every complete week and profile:

1. Filter exact immutable endpoints that satisfy scope, ECI threshold, required
   features, reproducible billing tokenizer, disabled reasoning, and grade-A
   live price evidence.
2. Price the frozen profile with endpoint-specific input/output billing-token
   counts and the applicable context tier.
3. Enumerate triples with three providers and three creators.
4. Select lexicographically by median cost, total cost, and sorted endpoint IDs.
5. Within the chosen triple sort by full-precision cost and endpoint ID; the
   second endpoint is the price setter.
6. Withhold incomplete weeks and weeks breaching concentration rules, while
   retaining diagnostic calculations.

The first thirteen consecutive complete weeks form the base. Their per-profile
arithmetic means are aggregated by frozen weights and set to index 100.

## Deterministic artifacts

The export bundle contains frozen input copies, calculation.json, release.json,
history.csv, components.csv, latest.json, and provenance-manifest.json. The
manifest hashes every other file. Reproduce verifies hashes, recalculates from
the frozen inputs (including recorded non-default weights), compares canonical
artifact bytes, and regenerates the full manifest for canonical comparison.
The release directory inventory must exactly equal the manifest file list plus
provenance-manifest.json; undeclared files are a reproduction failure.
Calculation and CSV artifacts carry explicit synthetic, citation-prohibited,
and not-for-publication labels so they remain safe when detached.
Before rendering, the exporter recomputes both validation and the complete
calculation from the frozen inputs and requires exact canonical equality. This
rejects caller-added alias fields as well as known governance fields.

External-review fields are forward schema only. Until a later migration adds a
trusted operator identity adapter and a separate cryptographic verifier
adapter, no operator-review label, external-review label, publication-ready
state, or publication eligibility is a valid output.
