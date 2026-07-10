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

## Bundle shape

The top-level bundle has:

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
