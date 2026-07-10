-- KAPI-SW append-only data foundation, schema version 1.
-- Decimal values are stored as TEXT and validated more precisely by kapi.store.

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS datasets (
    singleton INTEGER PRIMARY KEY CHECK (singleton = 1),
    id TEXT NOT NULL UNIQUE CHECK (length(trim(id)) > 0),
    schema_version TEXT NOT NULL CHECK (length(trim(schema_version)) > 0),
    dataset_kind TEXT NOT NULL CHECK (dataset_kind IN ('synthetic', 'observed')),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0)
);

CREATE TABLE IF NOT EXISTS weeks (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    cutoff_at TEXT NOT NULL UNIQUE CHECK (substr(cutoff_at, -1) = 'Z'),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0)
);

CREATE TABLE IF NOT EXISTS providers (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    name TEXT,
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (dataset_id, name)
);

CREATE TABLE IF NOT EXISTS creators (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    name TEXT,
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (dataset_id, name)
);

CREATE TABLE IF NOT EXISTS models (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    creator_id TEXT NOT NULL REFERENCES creators(id),
    version TEXT NOT NULL CHECK (length(trim(version)) > 0),
    alias_type TEXT NOT NULL CHECK (length(trim(alias_type)) > 0),
    immutable_version INTEGER NOT NULL CHECK (immutable_version IN (0, 1)),
    released_at TEXT CHECK (released_at IS NULL OR substr(released_at, -1) = 'Z'),
    retired_at TEXT CHECK (
        retired_at IS NULL OR
        (substr(retired_at, -1) = 'Z' AND (released_at IS NULL OR retired_at > released_at))
    ),
    tokenizer TEXT,
    modality TEXT,
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (creator_id, version, alias_type)
);

CREATE TABLE IF NOT EXISTS endpoints (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    provider_id TEXT NOT NULL REFERENCES providers(id),
    model_id TEXT NOT NULL REFERENCES models(id),
    configuration_id TEXT NOT NULL CHECK (length(trim(configuration_id)) > 0),
    region TEXT NOT NULL CHECK (length(trim(region)) > 0),
    tier TEXT NOT NULL CHECK (length(trim(tier)) > 0),
    is_public INTEGER NOT NULL CHECK (is_public IN (0, 1)),
    is_ga INTEGER NOT NULL CHECK (is_ga IN (0, 1)),
    is_synchronous INTEGER NOT NULL CHECK (is_synchronous IN (0, 1)),
    is_on_demand INTEGER NOT NULL CHECK (is_on_demand IN (0, 1)),
    is_available_us INTEGER NOT NULL CHECK (is_available_us IN (0, 1)),
    is_standard_list_price INTEGER NOT NULL CHECK (is_standard_list_price IN (0, 1)),
    reasoning_mode TEXT NOT NULL CHECK (length(trim(reasoning_mode)) > 0),
    billing_tokenizer TEXT NOT NULL CHECK (length(trim(billing_tokenizer)) > 0),
    tokenizer_reproducible INTEGER NOT NULL CHECK (tokenizer_reproducible IN (0, 1)),
    available_from TEXT NOT NULL CHECK (substr(available_from, -1) = 'Z'),
    available_until TEXT CHECK (
        available_until IS NULL OR
        (substr(available_until, -1) = 'Z' AND available_until > available_from)
    ),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (
        provider_id, model_id, configuration_id, region, tier, reasoning_mode,
        available_from
    )
);

CREATE TABLE IF NOT EXISTS endpoint_features (
    endpoint_id TEXT NOT NULL REFERENCES endpoints(id),
    feature_name TEXT NOT NULL CHECK (length(trim(feature_name)) > 0),
    PRIMARY KEY (endpoint_id, feature_name)
);

CREATE TABLE IF NOT EXISTS source_artifacts (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    url TEXT NOT NULL CHECK (length(trim(url)) > 0),
    retrieved_at TEXT NOT NULL CHECK (substr(retrieved_at, -1) = 'Z'),
    effective_at TEXT CHECK (effective_at IS NULL OR substr(effective_at, -1) = 'Z'),
    evidence_grade TEXT NOT NULL CHECK (evidence_grade IN ('A', 'B', 'C', 'D')),
    media_type TEXT NOT NULL CHECK (length(trim(media_type)) > 0),
    content_sha256 TEXT NOT NULL CHECK (
        length(content_sha256) = 64 AND
        content_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    snapshot_path TEXT NOT NULL CHECK (length(trim(snapshot_path)) > 0),
    license_note TEXT NOT NULL,
    reviewer TEXT,
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (url, retrieved_at, content_sha256)
);

CREATE TABLE IF NOT EXISTS capability_evidence (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    model_id TEXT NOT NULL REFERENCES models(id),
    endpoint_id TEXT NOT NULL REFERENCES endpoints(id),
    metric TEXT NOT NULL CHECK (length(trim(metric)) > 0),
    metric_version TEXT NOT NULL CHECK (length(trim(metric_version)) > 0),
    score TEXT NOT NULL CHECK (typeof(score) = 'text' AND length(trim(score)) > 0),
    score_lower TEXT CHECK (
        score_lower IS NULL OR (typeof(score_lower) = 'text' AND length(trim(score_lower)) > 0)
    ),
    score_upper TEXT CHECK (
        score_upper IS NULL OR (typeof(score_upper) = 'text' AND length(trim(score_upper)) > 0)
    ),
    threshold_score TEXT CHECK (
        threshold_score IS NULL OR
        (typeof(threshold_score) = 'text' AND length(trim(threshold_score)) > 0)
    ),
    configuration_id TEXT NOT NULL CHECK (length(trim(configuration_id)) > 0),
    evaluated_at TEXT NOT NULL CHECK (substr(evaluated_at, -1) = 'Z'),
    data_vintage TEXT NOT NULL CHECK (length(trim(data_vintage)) > 0),
    source_id TEXT NOT NULL REFERENCES source_artifacts(id),
    evidence_grade TEXT NOT NULL CHECK (evidence_grade IN ('A', 'B', 'C', 'D')),
    qualification_from TEXT CHECK (
        qualification_from IS NULL OR substr(qualification_from, -1) = 'Z'
    ),
    qualification_until TEXT CHECK (
        qualification_until IS NULL OR
        (substr(qualification_until, -1) = 'Z' AND
         (qualification_from IS NULL OR qualification_until > qualification_from))
    ),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    UNIQUE (
        endpoint_id, metric, metric_version, configuration_id, evaluated_at,
        data_vintage, source_id
    )
);

CREATE TABLE IF NOT EXISTS token_counts (
    id TEXT NOT NULL UNIQUE CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    endpoint_id TEXT NOT NULL REFERENCES endpoints(id),
    profile_id TEXT NOT NULL CHECK (length(trim(profile_id)) > 0),
    input_tokens INTEGER NOT NULL CHECK (input_tokens >= 0),
    output_tokens INTEGER NOT NULL CHECK (output_tokens >= 0),
    input_payload_sha256 TEXT NOT NULL CHECK (
        length(input_payload_sha256) = 64 AND
        input_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    output_payload_sha256 TEXT NOT NULL CHECK (
        length(output_payload_sha256) = 64 AND
        output_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    billing_tokenizer TEXT NOT NULL CHECK (length(trim(billing_tokenizer)) > 0),
    size_variant TEXT NOT NULL CHECK (length(trim(size_variant)) > 0),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    PRIMARY KEY (endpoint_id, profile_id, size_variant)
);

CREATE TABLE IF NOT EXISTS price_observations (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    endpoint_id TEXT NOT NULL REFERENCES endpoints(id),
    week_id TEXT NOT NULL REFERENCES weeks(id),
    component TEXT NOT NULL CHECK (length(trim(component)) > 0),
    amount_per_million TEXT NOT NULL CHECK (
        typeof(amount_per_million) = 'text' AND length(trim(amount_per_million)) > 0
    ),
    currency TEXT NOT NULL CHECK (
        length(currency) = 3 AND currency = upper(currency)
    ),
    unit TEXT NOT NULL CHECK (length(trim(unit)) > 0),
    region TEXT NOT NULL CHECK (length(trim(region)) > 0),
    tier TEXT NOT NULL CHECK (length(trim(tier)) > 0),
    context_min_tokens INTEGER NOT NULL CHECK (context_min_tokens >= 0),
    context_max_tokens INTEGER NOT NULL CHECK (context_max_tokens >= context_min_tokens),
    cache_treatment TEXT,
    batch_treatment TEXT,
    priority_treatment TEXT,
    tool_fee_treatment TEXT,
    effective_at TEXT NOT NULL CHECK (substr(effective_at, -1) = 'Z'),
    observed_at TEXT NOT NULL CHECK (substr(observed_at, -1) = 'Z'),
    source_id TEXT NOT NULL REFERENCES source_artifacts(id),
    evidence_grade TEXT NOT NULL CHECK (evidence_grade IN ('A', 'B', 'C', 'D')),
    supersedes_observation_id TEXT REFERENCES price_observations(id)
        DEFERRABLE INITIALLY DEFERRED,
    supersedes_present INTEGER NOT NULL DEFAULT 0 CHECK (supersedes_present IN (0, 1)),
    metadata_json TEXT NOT NULL DEFAULT '{}' CHECK (length(metadata_json) > 0),
    CHECK (supersedes_observation_id IS NULL OR supersedes_observation_id <> id),
    UNIQUE (
        endpoint_id, week_id, component, currency, unit, region, tier,
        context_min_tokens, context_max_tokens, effective_at, amount_per_million,
        source_id, observed_at
    )
);

CREATE TABLE IF NOT EXISTS incidents (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    detected_at TEXT NOT NULL CHECK (substr(detected_at, -1) = 'Z'),
    status TEXT NOT NULL CHECK (status IN ('open', 'resolved', 'closed')),
    summary TEXT NOT NULL CHECK (length(trim(summary)) > 0),
    impact TEXT NOT NULL DEFAULT '',
    resolution TEXT,
    closed_at TEXT CHECK (
        closed_at IS NULL OR (substr(closed_at, -1) = 'Z' AND closed_at >= detected_at)
    )
);

-- Correction envelopes remain exact and immutable while the common lineage
-- fields are normalized for joins and audit queries.
CREATE TABLE IF NOT EXISTS corrections (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    detected_at TEXT CHECK (detected_at IS NULL OR substr(detected_at, -1) = 'Z'),
    impact TEXT,
    resolution TEXT,
    approved_by TEXT,
    new_vintage TEXT,
    incident_id TEXT REFERENCES incidents(id),
    supersedes_correction_id TEXT REFERENCES corrections(id)
        DEFERRABLE INITIALLY DEFERRED,
    superseded_observation_id TEXT NOT NULL REFERENCES price_observations(id),
    replacement_observation_id TEXT NOT NULL REFERENCES price_observations(id),
    record_json TEXT NOT NULL CHECK (length(record_json) > 0),
    CHECK (supersedes_correction_id IS NULL OR supersedes_correction_id <> id),
    CHECK (
        superseded_observation_id <> replacement_observation_id
    )
);

-- Versioned methodology and recipe definitions.
CREATE TABLE IF NOT EXISTS methodology_versions (
    methodology_id TEXT NOT NULL CHECK (length(trim(methodology_id)) > 0),
    version TEXT NOT NULL CHECK (length(trim(version)) > 0),
    claim TEXT NOT NULL,
    scope TEXT NOT NULL,
    calendar_json TEXT NOT NULL,
    evidence_policy_json TEXT NOT NULL,
    selection_rules_json TEXT NOT NULL,
    concentration_thresholds_json TEXT NOT NULL,
    correction_policy_json TEXT NOT NULL,
    effective_from TEXT NOT NULL CHECK (substr(effective_from, -1) = 'Z'),
    effective_until TEXT CHECK (
        effective_until IS NULL OR
        (substr(effective_until, -1) = 'Z' AND effective_until > effective_from)
    ),
    PRIMARY KEY (methodology_id, version)
);

CREATE TABLE IF NOT EXISTS methodology_base_weeks (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    week_id TEXT NOT NULL REFERENCES weeks(id),
    position INTEGER NOT NULL CHECK (position >= 1),
    PRIMARY KEY (methodology_id, methodology_version, week_id),
    UNIQUE (methodology_id, methodology_version, position),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version)
);

CREATE TABLE IF NOT EXISTS methodology_thresholds (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    metric TEXT NOT NULL,
    metric_version TEXT NOT NULL,
    minimum_score TEXT NOT NULL CHECK (
        typeof(minimum_score) = 'text' AND length(trim(minimum_score)) > 0
    ),
    PRIMARY KEY (methodology_id, methodology_version, metric, metric_version),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version)
);

CREATE TABLE IF NOT EXISTS task_profiles (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    profile_id TEXT NOT NULL CHECK (length(trim(profile_id)) > 0),
    name TEXT NOT NULL,
    profile_count INTEGER NOT NULL CHECK (profile_count > 0),
    weight_numerator INTEGER NOT NULL CHECK (weight_numerator > 0),
    weight_denominator INTEGER NOT NULL CHECK (weight_denominator > 0),
    construction_input_tokens INTEGER NOT NULL CHECK (construction_input_tokens >= 0),
    construction_output_tokens INTEGER NOT NULL CHECK (construction_output_tokens >= 0),
    input_payload_path TEXT NOT NULL,
    input_payload_sha256 TEXT NOT NULL CHECK (
        length(input_payload_sha256) = 64 AND
        input_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    output_payload_path TEXT NOT NULL,
    output_payload_sha256 TEXT NOT NULL CHECK (
        length(output_payload_sha256) = 64 AND
        output_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    reference_tokenizer TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    grader_json TEXT NOT NULL,
    effective_from TEXT NOT NULL CHECK (substr(effective_from, -1) = 'Z'),
    effective_until TEXT CHECK (
        effective_until IS NULL OR
        (substr(effective_until, -1) = 'Z' AND effective_until > effective_from)
    ),
    PRIMARY KEY (methodology_id, methodology_version, profile_id),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version)
);

CREATE TABLE IF NOT EXISTS task_profile_features (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    profile_id TEXT NOT NULL,
    feature_name TEXT NOT NULL,
    PRIMARY KEY (methodology_id, methodology_version, profile_id, feature_name),
    FOREIGN KEY (methodology_id, methodology_version, profile_id)
        REFERENCES task_profiles(methodology_id, methodology_version, profile_id)
);

CREATE TABLE IF NOT EXISTS methodology_sensitivities (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    sensitivity_id TEXT NOT NULL,
    kind TEXT NOT NULL,
    parameters_json TEXT NOT NULL,
    PRIMARY KEY (methodology_id, methodology_version, sensitivity_id),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version)
);

-- A snapshot freezes the exact input IDs and hashes used by a calculation.
CREATE TABLE IF NOT EXISTS weekly_snapshots (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    dataset_id TEXT NOT NULL REFERENCES datasets(id),
    week_id TEXT NOT NULL REFERENCES weeks(id),
    cutoff_at TEXT NOT NULL CHECK (substr(cutoff_at, -1) = 'Z'),
    created_at TEXT NOT NULL CHECK (substr(created_at, -1) = 'Z'),
    input_manifest_sha256 TEXT NOT NULL CHECK (
        length(input_manifest_sha256) = 64 AND
        input_manifest_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    UNIQUE (dataset_id, week_id, input_manifest_sha256)
);

CREATE TABLE IF NOT EXISTS snapshot_inputs (
    snapshot_id TEXT NOT NULL REFERENCES weekly_snapshots(id),
    input_kind TEXT NOT NULL CHECK (
        input_kind IN ('source', 'capability', 'token_count', 'price', 'recipe', 'other')
    ),
    input_id TEXT NOT NULL CHECK (length(trim(input_id)) > 0),
    content_sha256 TEXT NOT NULL CHECK (
        length(content_sha256) = 64 AND
        content_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    PRIMARY KEY (snapshot_id, input_kind, input_id)
);

CREATE TABLE IF NOT EXISTS calculations (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    snapshot_id TEXT NOT NULL REFERENCES weekly_snapshots(id),
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    code_commit TEXT NOT NULL CHECK (length(trim(code_commit)) > 0),
    environment_sha256 TEXT NOT NULL CHECK (
        length(environment_sha256) = 64 AND
        environment_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    calculated_at TEXT NOT NULL CHECK (substr(calculated_at, -1) = 'Z'),
    status TEXT NOT NULL CHECK (status IN ('pending_base', 'complete', 'withheld', 'invalid')),
    index_value TEXT CHECK (
        index_value IS NULL OR
        (typeof(index_value) = 'text' AND length(trim(index_value)) > 0)
    ),
    basket_cost TEXT CHECK (
        basket_cost IS NULL OR
        (typeof(basket_cost) = 'text' AND length(trim(basket_cost)) > 0)
    ),
    diagnostics_json TEXT NOT NULL,
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version),
    UNIQUE (snapshot_id, methodology_id, methodology_version, code_commit)
);

CREATE TABLE IF NOT EXISTS calculation_profile_results (
    calculation_id TEXT NOT NULL REFERENCES calculations(id),
    profile_id TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('complete', 'withheld', 'invalid')),
    profile_cost TEXT CHECK (
        profile_cost IS NULL OR
        (typeof(profile_cost) = 'text' AND length(trim(profile_cost)) > 0)
    ),
    frontier_cost TEXT CHECK (
        frontier_cost IS NULL OR
        (typeof(frontier_cost) = 'text' AND length(trim(frontier_cost)) > 0)
    ),
    selected_total_cost TEXT CHECK (
        selected_total_cost IS NULL OR
        (typeof(selected_total_cost) = 'text' AND length(trim(selected_total_cost)) > 0)
    ),
    detail_json TEXT NOT NULL,
    PRIMARY KEY (calculation_id, profile_id)
);

CREATE TABLE IF NOT EXISTS calculation_selected_endpoints (
    calculation_id TEXT NOT NULL,
    profile_id TEXT NOT NULL,
    endpoint_id TEXT NOT NULL REFERENCES endpoints(id),
    rank INTEGER NOT NULL CHECK (rank BETWEEN 1 AND 3),
    cost TEXT NOT NULL CHECK (typeof(cost) = 'text' AND length(trim(cost)) > 0),
    is_price_setter INTEGER NOT NULL CHECK (is_price_setter IN (0, 1)),
    PRIMARY KEY (calculation_id, profile_id, endpoint_id),
    UNIQUE (calculation_id, profile_id, rank),
    FOREIGN KEY (calculation_id, profile_id)
        REFERENCES calculation_profile_results(calculation_id, profile_id)
);

CREATE TABLE IF NOT EXISTS calculation_validations (
    calculation_id TEXT NOT NULL REFERENCES calculations(id),
    check_name TEXT NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('pass', 'fail', 'warning')),
    detail_json TEXT NOT NULL,
    PRIMARY KEY (calculation_id, check_name)
);

CREATE TABLE IF NOT EXISTS releases (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    calculation_id TEXT NOT NULL REFERENCES calculations(id),
    week_id TEXT NOT NULL REFERENCES weeks(id),
    data_vintage TEXT NOT NULL CHECK (length(trim(data_vintage)) > 0),
    status TEXT NOT NULL CHECK (
        status IN ('draft', 'provisional', 'final', 'corrected', 'withdrawn')
    ),
    released_at TEXT CHECK (released_at IS NULL OR substr(released_at, -1) = 'Z'),
    permanent_path TEXT NOT NULL CHECK (length(trim(permanent_path)) > 0),
    supersedes_release_id TEXT REFERENCES releases(id) DEFERRABLE INITIALLY DEFERRED,
    CHECK (supersedes_release_id IS NULL OR supersedes_release_id <> id),
    UNIQUE (week_id, data_vintage)
);

CREATE TABLE IF NOT EXISTS release_artifacts (
    release_id TEXT NOT NULL REFERENCES releases(id),
    path TEXT NOT NULL,
    media_type TEXT NOT NULL,
    content_sha256 TEXT NOT NULL CHECK (
        length(content_sha256) = 64 AND
        content_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    PRIMARY KEY (release_id, path)
);

CREATE TABLE IF NOT EXISTS release_signoffs (
    release_id TEXT NOT NULL REFERENCES releases(id),
    role TEXT NOT NULL,
    approver TEXT NOT NULL,
    signed_at TEXT NOT NULL CHECK (substr(signed_at, -1) = 'Z'),
    note TEXT NOT NULL DEFAULT '',
    PRIMARY KEY (release_id, role, approver)
);

CREATE TABLE IF NOT EXISTS correction_releases (
    correction_id TEXT NOT NULL REFERENCES corrections(id),
    release_id TEXT NOT NULL REFERENCES releases(id),
    relation TEXT NOT NULL CHECK (relation IN ('affected', 'replacement')),
    PRIMARY KEY (correction_id, release_id, relation)
);

-- Append-only enforcement lives in the database, not merely in the Python API.
-- Corrections, finalizations, and replacements are represented by new rows.
CREATE TRIGGER IF NOT EXISTS datasets_no_update BEFORE UPDATE ON datasets
BEGIN SELECT RAISE(ABORT, 'append-only: datasets cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS datasets_no_delete BEFORE DELETE ON datasets
BEGIN SELECT RAISE(ABORT, 'append-only: datasets cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS weeks_no_update BEFORE UPDATE ON weeks
BEGIN SELECT RAISE(ABORT, 'append-only: weeks cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS weeks_no_delete BEFORE DELETE ON weeks
BEGIN SELECT RAISE(ABORT, 'append-only: weeks cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS providers_no_update BEFORE UPDATE ON providers
BEGIN SELECT RAISE(ABORT, 'append-only: providers cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS providers_no_delete BEFORE DELETE ON providers
BEGIN SELECT RAISE(ABORT, 'append-only: providers cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS creators_no_update BEFORE UPDATE ON creators
BEGIN SELECT RAISE(ABORT, 'append-only: creators cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS creators_no_delete BEFORE DELETE ON creators
BEGIN SELECT RAISE(ABORT, 'append-only: creators cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS models_no_update BEFORE UPDATE ON models
BEGIN SELECT RAISE(ABORT, 'append-only: models cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS models_no_delete BEFORE DELETE ON models
BEGIN SELECT RAISE(ABORT, 'append-only: models cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS endpoints_no_update BEFORE UPDATE ON endpoints
BEGIN SELECT RAISE(ABORT, 'append-only: endpoints cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS endpoints_no_delete BEFORE DELETE ON endpoints
BEGIN SELECT RAISE(ABORT, 'append-only: endpoints cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS endpoint_features_no_update BEFORE UPDATE ON endpoint_features
BEGIN SELECT RAISE(ABORT, 'append-only: endpoint_features cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS endpoint_features_no_delete BEFORE DELETE ON endpoint_features
BEGIN SELECT RAISE(ABORT, 'append-only: endpoint_features cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS source_artifacts_no_update BEFORE UPDATE ON source_artifacts
BEGIN SELECT RAISE(ABORT, 'append-only: source_artifacts cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS source_artifacts_no_delete BEFORE DELETE ON source_artifacts
BEGIN SELECT RAISE(ABORT, 'append-only: source_artifacts cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS capability_evidence_no_update BEFORE UPDATE ON capability_evidence
BEGIN SELECT RAISE(ABORT, 'append-only: capability_evidence cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS capability_evidence_no_delete BEFORE DELETE ON capability_evidence
BEGIN SELECT RAISE(ABORT, 'append-only: capability_evidence cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS token_counts_no_update BEFORE UPDATE ON token_counts
BEGIN SELECT RAISE(ABORT, 'append-only: token_counts cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS token_counts_no_delete BEFORE DELETE ON token_counts
BEGIN SELECT RAISE(ABORT, 'append-only: token_counts cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS price_observations_no_update BEFORE UPDATE ON price_observations
BEGIN SELECT RAISE(ABORT, 'append-only: price_observations cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS price_observations_no_delete BEFORE DELETE ON price_observations
BEGIN SELECT RAISE(ABORT, 'append-only: price_observations cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS incidents_no_update BEFORE UPDATE ON incidents
BEGIN SELECT RAISE(ABORT, 'append-only: incidents cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS incidents_no_delete BEFORE DELETE ON incidents
BEGIN SELECT RAISE(ABORT, 'append-only: incidents cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS corrections_no_update BEFORE UPDATE ON corrections
BEGIN SELECT RAISE(ABORT, 'append-only: corrections cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS corrections_no_delete BEFORE DELETE ON corrections
BEGIN SELECT RAISE(ABORT, 'append-only: corrections cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS methodology_versions_no_update BEFORE UPDATE ON methodology_versions
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_versions cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS methodology_versions_no_delete BEFORE DELETE ON methodology_versions
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_versions cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS methodology_base_weeks_no_update BEFORE UPDATE ON methodology_base_weeks
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_base_weeks cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS methodology_base_weeks_no_delete BEFORE DELETE ON methodology_base_weeks
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_base_weeks cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS methodology_thresholds_no_update BEFORE UPDATE ON methodology_thresholds
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_thresholds cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS methodology_thresholds_no_delete BEFORE DELETE ON methodology_thresholds
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_thresholds cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS task_profiles_no_update BEFORE UPDATE ON task_profiles
BEGIN SELECT RAISE(ABORT, 'append-only: task_profiles cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS task_profiles_no_delete BEFORE DELETE ON task_profiles
BEGIN SELECT RAISE(ABORT, 'append-only: task_profiles cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS task_profile_features_no_update BEFORE UPDATE ON task_profile_features
BEGIN SELECT RAISE(ABORT, 'append-only: task_profile_features cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS task_profile_features_no_delete BEFORE DELETE ON task_profile_features
BEGIN SELECT RAISE(ABORT, 'append-only: task_profile_features cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS methodology_sensitivities_no_update BEFORE UPDATE ON methodology_sensitivities
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_sensitivities cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS methodology_sensitivities_no_delete BEFORE DELETE ON methodology_sensitivities
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_sensitivities cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS weekly_snapshots_no_update BEFORE UPDATE ON weekly_snapshots
BEGIN SELECT RAISE(ABORT, 'append-only: weekly_snapshots cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS weekly_snapshots_no_delete BEFORE DELETE ON weekly_snapshots
BEGIN SELECT RAISE(ABORT, 'append-only: weekly_snapshots cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS snapshot_inputs_no_update BEFORE UPDATE ON snapshot_inputs
BEGIN SELECT RAISE(ABORT, 'append-only: snapshot_inputs cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS snapshot_inputs_no_delete BEFORE DELETE ON snapshot_inputs
BEGIN SELECT RAISE(ABORT, 'append-only: snapshot_inputs cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS calculations_no_update BEFORE UPDATE ON calculations
BEGIN SELECT RAISE(ABORT, 'append-only: calculations cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS calculations_no_delete BEFORE DELETE ON calculations
BEGIN SELECT RAISE(ABORT, 'append-only: calculations cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS calculation_profile_results_no_update BEFORE UPDATE ON calculation_profile_results
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_profile_results cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS calculation_profile_results_no_delete BEFORE DELETE ON calculation_profile_results
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_profile_results cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS calculation_selected_endpoints_no_update BEFORE UPDATE ON calculation_selected_endpoints
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_selected_endpoints cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS calculation_selected_endpoints_no_delete BEFORE DELETE ON calculation_selected_endpoints
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_selected_endpoints cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS calculation_validations_no_update BEFORE UPDATE ON calculation_validations
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_validations cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS calculation_validations_no_delete BEFORE DELETE ON calculation_validations
BEGIN SELECT RAISE(ABORT, 'append-only: calculation_validations cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS releases_no_update BEFORE UPDATE ON releases
BEGIN SELECT RAISE(ABORT, 'append-only: releases cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS releases_no_delete BEFORE DELETE ON releases
BEGIN SELECT RAISE(ABORT, 'append-only: releases cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS release_artifacts_no_update BEFORE UPDATE ON release_artifacts
BEGIN SELECT RAISE(ABORT, 'append-only: release_artifacts cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS release_artifacts_no_delete BEFORE DELETE ON release_artifacts
BEGIN SELECT RAISE(ABORT, 'append-only: release_artifacts cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS release_signoffs_no_update BEFORE UPDATE ON release_signoffs
BEGIN SELECT RAISE(ABORT, 'append-only: release_signoffs cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS release_signoffs_no_delete BEFORE DELETE ON release_signoffs
BEGIN SELECT RAISE(ABORT, 'append-only: release_signoffs cannot be deleted'); END;
CREATE TRIGGER IF NOT EXISTS correction_releases_no_update BEFORE UPDATE ON correction_releases
BEGIN SELECT RAISE(ABORT, 'append-only: correction_releases cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS correction_releases_no_delete BEFORE DELETE ON correction_releases
BEGIN SELECT RAISE(ABORT, 'append-only: correction_releases cannot be deleted'); END;

PRAGMA user_version = 1;
