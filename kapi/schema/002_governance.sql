-- KAPI governance controls, schema version 2.
--
-- Version 1 rows remain immutable. New releases begin as draft records and
-- acquire governance/publication state only through adapter-bound append-only
-- events. Actor binding is local scaffolding, not authentication, so the
-- trusted-verifier gate is hard-failed in this vintage. The legacy
-- release_signoffs table is retained for historical reads;
-- it is not an authorization source for version 2.

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS governance_principals (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    identity_key TEXT NOT NULL UNIQUE COLLATE NOCASE CHECK (
        identity_key = lower(trim(identity_key)) AND
        identity_key NOT GLOB '*[[:space:]]*'
    ),
    full_name TEXT NOT NULL CHECK (length(trim(full_name)) > 0),
    affiliation TEXT NOT NULL CHECK (length(trim(affiliation)) > 0),
    principal_kind TEXT NOT NULL CHECK (principal_kind IN ('human', 'service')),
    identity_evidence_sha256 TEXT NOT NULL CHECK (
        length(identity_evidence_sha256) = 64 AND
        identity_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    registered_by_principal_id TEXT NOT NULL CHECK (
        length(trim(registered_by_principal_id)) > 0
    ),
    registered_at TEXT NOT NULL CHECK (substr(registered_at, -1) = 'Z'),
    UNIQUE (identity_evidence_sha256)
);

INSERT OR IGNORE INTO governance_principals VALUES(
    'kapi-identity-registrar',
    'service:kapi-identity-registrar',
    'KAPI Identity Registrar',
    'Kingy.ai governance control plane',
    'service',
    '0000000000000000000000000000000000000000000000000000000000000000',
    'system-bootstrap',
    '1970-01-01T00:00:00Z'
);

CREATE TABLE IF NOT EXISTS governance_role_assignments (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    role TEXT NOT NULL CHECK (
        role IN (
            'operator',
            'methodology_owner',
            'external_reviewer',
            'release_authorizer',
            'publication_authorizer',
            'identity_registrar',
            'reviewer_registrar'
        )
    ),
    appointed_by_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    appointed_at TEXT NOT NULL CHECK (substr(appointed_at, -1) = 'Z'),
    valid_from TEXT NOT NULL CHECK (substr(valid_from, -1) = 'Z'),
    valid_until TEXT CHECK (
        valid_until IS NULL OR
        (substr(valid_until, -1) = 'Z' AND valid_until >= valid_from)
    ),
    appointment_sha256 TEXT NOT NULL CHECK (
        length(appointment_sha256) = 64 AND
        appointment_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    UNIQUE (principal_id, role, valid_from, appointment_sha256)
);

INSERT OR IGNORE INTO governance_role_assignments VALUES(
    'kapi-identity-registrar:identity-role',
    'kapi-identity-registrar',
    'identity_registrar',
    'kapi-identity-registrar',
    '1970-01-01T00:00:00Z',
    '1970-01-01T00:00:00Z',
    NULL,
    '0000000000000000000000000000000000000000000000000000000000000000'
);

INSERT OR IGNORE INTO governance_role_assignments VALUES(
    'kapi-identity-registrar:reviewer-role',
    'kapi-identity-registrar',
    'reviewer_registrar',
    'kapi-identity-registrar',
    '1970-01-01T00:00:00Z',
    '1970-01-01T00:00:00Z',
    NULL,
    '0000000000000000000000000000000000000000000000000000000000000000'
);

CREATE TABLE IF NOT EXISTS external_reviewer_registry (
    principal_id TEXT PRIMARY KEY REFERENCES governance_principals(id),
    qualifications_summary TEXT NOT NULL CHECK (
        length(trim(qualifications_summary)) > 0
    ),
    qualifications_sha256 TEXT NOT NULL CHECK (
        length(qualifications_sha256) = 64 AND
        qualifications_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    registered_at TEXT NOT NULL CHECK (substr(registered_at, -1) = 'Z'),
    valid_until TEXT NOT NULL CHECK (
        substr(valid_until, -1) = 'Z' AND valid_until >= registered_at
    ),
    registered_by_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    registrar_evidence_sha256 TEXT NOT NULL CHECK (
        length(registrar_evidence_sha256) = 64 AND
        registrar_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    signature_scheme TEXT NOT NULL CHECK (signature_scheme = 'minisign-ed25519'),
    signature_key_id TEXT NOT NULL CHECK (length(trim(signature_key_id)) > 0),
    public_key_sha256 TEXT NOT NULL CHECK (
        length(public_key_sha256) = 64 AND
        public_key_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    status TEXT NOT NULL CHECK (status IN ('active', 'withdrawn'))
);

-- The base registry row above is the immutable initial registration. Every
-- later key rotation, qualification refresh, validity change, or revocation is
-- an append-only event. Review records bind the exact event that was current
-- when the reviewer acted; transition guards separately re-check that the
-- same event is still the latest eligible state when a claim would advance.
CREATE TABLE IF NOT EXISTS external_reviewer_registry_events (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    principal_id TEXT NOT NULL REFERENCES external_reviewer_registry(principal_id),
    sequence INTEGER NOT NULL CHECK (sequence >= 1),
    supersedes_event_id TEXT REFERENCES external_reviewer_registry_events(id),
    event_type TEXT NOT NULL CHECK (
        event_type IN ('registration', 'supersession', 'revocation')
    ),
    status TEXT NOT NULL CHECK (status IN ('active', 'revoked')),
    effective_at TEXT NOT NULL CHECK (substr(effective_at, -1) = 'Z'),
    valid_until TEXT NOT NULL CHECK (substr(valid_until, -1) = 'Z'),
    recorded_at TEXT NOT NULL CHECK (
        substr(recorded_at, -1) = 'Z' AND recorded_at >= effective_at
    ),
    recorded_by_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    recorder_assignment_id TEXT NOT NULL REFERENCES governance_role_assignments(id),
    event_evidence_sha256 TEXT NOT NULL CHECK (
        length(event_evidence_sha256) = 64 AND
        event_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    qualifications_summary TEXT NOT NULL CHECK (
        length(trim(qualifications_summary)) > 0
    ),
    qualifications_sha256 TEXT NOT NULL CHECK (
        length(qualifications_sha256) = 64 AND
        qualifications_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    signature_scheme TEXT NOT NULL CHECK (signature_scheme = 'minisign-ed25519'),
    signature_key_id TEXT NOT NULL CHECK (length(trim(signature_key_id)) > 0),
    public_key_sha256 TEXT NOT NULL CHECK (
        length(public_key_sha256) = 64 AND
        public_key_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    reason TEXT NOT NULL CHECK (length(trim(reason)) > 0),
    CHECK (status = 'revoked' OR valid_until >= effective_at),
    CHECK (
        (sequence = 1 AND event_type = 'registration' AND
         status = 'active' AND supersedes_event_id IS NULL) OR
        (sequence > 1 AND event_type = 'supersession' AND
         status = 'active' AND supersedes_event_id IS NOT NULL) OR
        (sequence > 1 AND event_type = 'revocation' AND
         status = 'revoked' AND supersedes_event_id IS NOT NULL)
    ),
    UNIQUE (principal_id, sequence)
);

CREATE TABLE IF NOT EXISTS methodology_governance_gates (
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    governance_policy_id TEXT NOT NULL CHECK (
        length(trim(governance_policy_id)) > 0
    ),
    governance_policy_version TEXT NOT NULL CHECK (
        length(trim(governance_policy_version)) > 0
    ),
    technical_gate TEXT NOT NULL CHECK (technical_gate IN ('passed', 'failed')),
    operational_gate TEXT NOT NULL CHECK (operational_gate IN ('passed', 'failed')),
    trusted_verifier_gate TEXT NOT NULL CHECK (trusted_verifier_gate = 'failed'),
    implementation_commit TEXT NOT NULL CHECK (
        length(implementation_commit) IN (40, 64) AND
        implementation_commit NOT GLOB '*[^0-9a-f]*'
    ),
    review_artifact_manifest_sha256 TEXT NOT NULL CHECK (
        length(review_artifact_manifest_sha256) = 64 AND
        review_artifact_manifest_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    methodology_sha256 TEXT NOT NULL CHECK (
        length(methodology_sha256) = 64 AND
        methodology_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    recorded_at TEXT NOT NULL CHECK (substr(recorded_at, -1) = 'Z'),
    PRIMARY KEY (methodology_id, methodology_version),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_versions(methodology_id, version)
);

CREATE TABLE IF NOT EXISTS release_governance_bindings (
    release_id TEXT PRIMARY KEY REFERENCES releases(id),
    governance_policy_id TEXT NOT NULL CHECK (
        governance_policy_id = 'kapi-governance'
    ),
    governance_policy_version TEXT NOT NULL CHECK (
        governance_policy_version = '1.0.0'
    ),
    operator_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    operator_assignment_id TEXT NOT NULL REFERENCES governance_role_assignments(id),
    methodology_owner_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    methodology_owner_assignment_id TEXT NOT NULL
        REFERENCES governance_role_assignments(id),
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    methodology_sha256 TEXT NOT NULL CHECK (
        length(methodology_sha256) = 64 AND
        methodology_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    code_commit TEXT NOT NULL CHECK (
        length(code_commit) IN (40, 64) AND code_commit NOT GLOB '*[^0-9a-f]*'
    ),
    artifact_manifest_sha256 TEXT NOT NULL CHECK (
        length(artifact_manifest_sha256) = 64 AND
        artifact_manifest_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    calculation_disposition TEXT NOT NULL CHECK (
        calculation_disposition IN ('eligible', 'withheld', 'incomplete')
    ),
    bound_at TEXT NOT NULL CHECK (substr(bound_at, -1) = 'Z'),
    FOREIGN KEY (methodology_id, methodology_version)
        REFERENCES methodology_governance_gates(methodology_id, methodology_version)
);

CREATE TABLE IF NOT EXISTS external_review_records (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    review_kind TEXT NOT NULL CHECK (review_kind IN ('methodology', 'release')),
    release_id TEXT REFERENCES release_governance_bindings(release_id),
    reviewer_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    reviewer_full_name TEXT NOT NULL CHECK (length(trim(reviewer_full_name)) > 0),
    reviewer_affiliation TEXT NOT NULL CHECK (
        length(trim(reviewer_affiliation)) > 0
    ),
    reviewer_appointment_id TEXT NOT NULL
        REFERENCES governance_role_assignments(id),
    reviewer_registry_event_id TEXT NOT NULL
        REFERENCES external_reviewer_registry_events(id),
    methodology_owner_principal_id TEXT NOT NULL
        REFERENCES governance_principals(id),
    scope_json TEXT NOT NULL CHECK (
        (review_kind = 'methodology' AND scope_json =
            '["evidence-policy","methodology","publication-claims","selection","validation"]') OR
        (review_kind = 'release' AND scope_json =
            '["artifact-manifest","calculation","recalculation","release-claims"]')
    ),
    scope_sha256 TEXT NOT NULL CHECK (
        length(scope_sha256) = 64 AND scope_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    conflict_status TEXT NOT NULL CHECK (conflict_status = 'clear'),
    conflict_declaration_json TEXT NOT NULL CHECK (
        length(trim(conflict_declaration_json)) > 2
    ),
    conflict_declaration_sha256 TEXT NOT NULL CHECK (
        length(conflict_declaration_sha256) = 64 AND
        conflict_declaration_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    conflict_declared_at TEXT NOT NULL CHECK (
        substr(conflict_declared_at, -1) = 'Z'
    ),
    relationship_status TEXT NOT NULL CHECK (relationship_status = 'none'),
    relationship_disclosure_json TEXT NOT NULL CHECK (
        length(trim(relationship_disclosure_json)) > 2
    ),
    relationship_disclosure_sha256 TEXT NOT NULL CHECK (
        length(relationship_disclosure_sha256) = 64 AND
        relationship_disclosure_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    compensation_status TEXT NOT NULL CHECK (
        compensation_status IN ('none', 'fixed_review_fee')
    ),
    compensation_disclosure_json TEXT NOT NULL CHECK (
        length(trim(compensation_disclosure_json)) > 2
    ),
    compensation_disclosure_sha256 TEXT NOT NULL CHECK (
        length(compensation_disclosure_sha256) = 64 AND
        compensation_disclosure_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    disclosures_declared_at TEXT NOT NULL CHECK (
        substr(disclosures_declared_at, -1) = 'Z'
    ),
    methodology_id TEXT NOT NULL,
    methodology_version TEXT NOT NULL,
    methodology_sha256 TEXT NOT NULL CHECK (
        length(methodology_sha256) = 64 AND
        methodology_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    code_commit TEXT NOT NULL CHECK (
        length(code_commit) IN (40, 64) AND code_commit NOT GLOB '*[^0-9a-f]*'
    ),
    review_artifact_manifest_sha256 TEXT NOT NULL CHECK (
        length(review_artifact_manifest_sha256) = 64 AND
        review_artifact_manifest_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    outcome TEXT NOT NULL CHECK (outcome IN ('approved', 'rejected')),
    report_sha256 TEXT NOT NULL CHECK (
        length(report_sha256) = 64 AND report_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    findings_json TEXT NOT NULL CHECK (length(trim(findings_json)) >= 2),
    findings_sha256 TEXT NOT NULL CHECK (
        length(findings_sha256) = 64 AND findings_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    unresolved_issues_json TEXT NOT NULL CHECK (
        length(trim(unresolved_issues_json)) >= 2 AND
        (outcome <> 'approved' OR unresolved_issues_json = '[]')
    ),
    unresolved_issues_sha256 TEXT NOT NULL CHECK (
        length(unresolved_issues_sha256) = 64 AND
        unresolved_issues_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    evidence_record_id TEXT NOT NULL CHECK (length(trim(evidence_record_id)) > 0),
    signature_scheme TEXT NOT NULL CHECK (length(trim(signature_scheme)) > 0),
    signature_key_id TEXT NOT NULL CHECK (length(trim(signature_key_id)) > 0),
    signature_evidence_sha256 TEXT NOT NULL CHECK (
        length(signature_evidence_sha256) = 64 AND
        signature_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    signed_payload_sha256 TEXT NOT NULL CHECK (
        length(signed_payload_sha256) = 64 AND
        signed_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    reviewed_at TEXT NOT NULL CHECK (substr(reviewed_at, -1) = 'Z'),
    valid_until TEXT NOT NULL CHECK (
        substr(valid_until, -1) = 'Z' AND valid_until >= reviewed_at
    ),
    recorded_at TEXT NOT NULL CHECK (
        substr(recorded_at, -1) = 'Z' AND recorded_at >= reviewed_at
    ),
    CHECK (
        (review_kind = 'methodology' AND release_id IS NULL) OR
        (review_kind = 'release' AND release_id IS NOT NULL)
    )
);

CREATE TABLE IF NOT EXISTS signature_verification_attestations (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    review_record_id TEXT NOT NULL UNIQUE REFERENCES external_review_records(id),
    verifier_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    verifier_assignment_id TEXT NOT NULL REFERENCES governance_role_assignments(id),
    signature_scheme TEXT NOT NULL CHECK (signature_scheme = 'minisign-ed25519'),
    signature_key_id TEXT NOT NULL CHECK (length(trim(signature_key_id)) > 0),
    signed_payload_sha256 TEXT NOT NULL CHECK (
        length(signed_payload_sha256) = 64 AND
        signed_payload_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    signature_evidence_sha256 TEXT NOT NULL CHECK (
        length(signature_evidence_sha256) = 64 AND
        signature_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    verification_evidence_sha256 TEXT NOT NULL CHECK (
        length(verification_evidence_sha256) = 64 AND
        verification_evidence_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    verified_at TEXT NOT NULL CHECK (substr(verified_at, -1) = 'Z'),
    status TEXT NOT NULL CHECK (status = 'untrusted_local_claim')
);

CREATE TABLE IF NOT EXISTS governance_transition_events (
    id TEXT PRIMARY KEY CHECK (length(trim(id)) > 0),
    release_id TEXT NOT NULL REFERENCES release_governance_bindings(release_id),
    sequence INTEGER NOT NULL CHECK (sequence >= 1),
    from_governance_state TEXT NOT NULL CHECK (
        from_governance_state IN (
            'draft', 'unreviewed', 'operator_reviewed',
            'external_release_reviewed', 'withdrawn'
        )
    ),
    from_publication_state TEXT NOT NULL CHECK (
        from_publication_state IN ('not_authorized', 'ready', 'published', 'withdrawn')
    ),
    governance_state TEXT NOT NULL CHECK (
        governance_state IN (
            'unreviewed', 'operator_reviewed',
            'external_release_reviewed', 'withdrawn'
        )
    ),
    publication_state TEXT NOT NULL CHECK (
        publication_state IN ('not_authorized', 'ready', 'published', 'withdrawn')
    ),
    calculation_disposition TEXT NOT NULL CHECK (
        calculation_disposition IN ('eligible', 'withheld', 'incomplete')
    ),
    review_label TEXT NOT NULL CHECK (
        (governance_state = 'unreviewed' AND
            review_label = 'Governance status: Unreviewed draft. Automated validation completed for this artifact; no operator or external methodology review is complete.') OR
        (governance_state = 'operator_reviewed' AND review_label IN (
            'Governance status: Operator-reviewed by Kingy.ai and automatically checked. No external methodology review is complete.',
            'Governance status: Operator-reviewed by Kingy.ai and automatically checked. External methodology review is complete; this release was not externally reviewed.'
        )) OR
        (governance_state = 'external_release_reviewed' AND
            review_label = 'Governance status: This exact release received named external review and recalculation under the recorded scope.') OR
        (governance_state = 'withdrawn' AND review_label = 'withdrawn')
    ),
    actor_principal_id TEXT NOT NULL REFERENCES governance_principals(id),
    actor_assignment_id TEXT NOT NULL REFERENCES governance_role_assignments(id),
    artifact_manifest_sha256 TEXT NOT NULL CHECK (
        length(artifact_manifest_sha256) = 64 AND
        artifact_manifest_sha256 NOT GLOB '*[^0-9a-f]*'
    ),
    methodology_review_record_id TEXT REFERENCES external_review_records(id),
    release_review_record_id TEXT REFERENCES external_review_records(id),
    transitioned_at TEXT NOT NULL CHECK (substr(transitioned_at, -1) = 'Z'),
    reason TEXT NOT NULL CHECK (length(trim(reason)) > 0),
    publication_eligible INTEGER GENERATED ALWAYS AS (
        CASE
            WHEN calculation_disposition = 'eligible'
             AND governance_state IN ('operator_reviewed', 'external_release_reviewed')
             AND publication_state = 'ready'
            THEN 1 ELSE 0
        END
    ) VIRTUAL,
    UNIQUE (release_id, sequence)
);

CREATE VIEW IF NOT EXISTS release_governance_status AS
SELECT
    event.release_id,
    event.calculation_disposition,
    event.governance_state,
    event.review_label,
    event.publication_state,
    event.publication_eligible,
    event.artifact_manifest_sha256,
    event.methodology_review_record_id,
    event.release_review_record_id,
    event.transitioned_at
FROM governance_transition_events AS event
WHERE NOT EXISTS (
    SELECT 1
    FROM governance_transition_events AS later
    WHERE later.release_id = event.release_id
      AND later.sequence > event.sequence
);

-- Legacy final/corrected rows are readable, but version 2 never creates more.
CREATE TRIGGER IF NOT EXISTS calculations_diagnostics_guard
BEFORE INSERT ON calculations
BEGIN
    SELECT RAISE(
        ABORT,
        'governance-v2: calculation diagnostics are not the exact policy-v1 document'
    )
    WHERE kapi_validate_calculation_diagnostics(
        NEW.diagnostics_json,
        NEW.status
    ) IS NOT 1;
END;

CREATE TRIGGER IF NOT EXISTS releases_governance_v2_no_direct_final
BEFORE INSERT ON releases
WHEN NEW.status IN ('final', 'corrected')
BEGIN
    SELECT RAISE(
        ABORT,
        'governance-v2: direct final/corrected release insert prohibited'
    );
END;

CREATE TRIGGER IF NOT EXISTS governance_principals_insert_guard
BEFORE INSERT ON governance_principals
WHEN NEW.id <> 'kapi-identity-registrar'
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.registered_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: natural-person controller cannot self-register')
    WHERE NEW.id = NEW.registered_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: identity registrar role is required')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.principal_id = NEW.registered_by_principal_id
          AND assignment.role = 'identity_registrar'
          AND assignment.valid_from <= NEW.registered_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.registered_at)
    );
END;

CREATE TRIGGER IF NOT EXISTS governance_role_assignments_insert_guard
BEFORE INSERT ON governance_role_assignments
WHEN NEW.id NOT IN (
    'kapi-identity-registrar:identity-role',
    'kapi-identity-registrar:reviewer-role'
)
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.appointed_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: principal cannot self-appoint')
    WHERE NEW.principal_id = NEW.appointed_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: role appointment chronology is invalid')
    WHERE NEW.appointed_at > NEW.valid_from
       OR (NEW.valid_until IS NOT NULL AND NEW.valid_from > NEW.valid_until)
       OR EXISTS (
           SELECT 1 FROM governance_principals AS principal
           WHERE principal.id = NEW.principal_id
             AND principal.registered_at > NEW.appointed_at
       );
    SELECT RAISE(ABORT, 'governance-v2: identity registrar must appoint roles')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.principal_id = NEW.appointed_by_principal_id
          AND assignment.role = 'identity_registrar'
          AND assignment.valid_from <= NEW.appointed_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.appointed_at)
    );
END;

CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_insert_guard
BEFORE INSERT ON external_reviewer_registry
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.registered_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: reviewer cannot self-register')
    WHERE NEW.principal_id = NEW.registered_by_principal_id;
    SELECT RAISE(ABORT, 'governance-v2: reviewer registration chronology is invalid')
    WHERE NEW.registered_at > NEW.valid_until
       OR EXISTS (
           SELECT 1 FROM governance_principals AS principal
           WHERE principal.id = NEW.principal_id
             AND principal.registered_at > NEW.registered_at
       );
    SELECT RAISE(ABORT, 'governance-v2: reviewer registrar role is required')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.principal_id = NEW.registered_by_principal_id
          AND assignment.role = 'reviewer_registrar'
          AND assignment.valid_from <= NEW.registered_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.registered_at)
    );
END;

CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_events_insert_guard
BEFORE INSERT ON external_reviewer_registry_events
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.recorded_by_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: reviewer registry event cannot be self-recorded')
    WHERE NEW.principal_id = NEW.recorded_by_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: reviewer registrar assignment is invalid')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.recorder_assignment_id
          AND assignment.principal_id = NEW.recorded_by_principal_id
          AND assignment.role = 'reviewer_registrar'
          AND assignment.valid_from <= NEW.recorded_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.recorded_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer registry event sequence is not append-only')
    WHERE NEW.sequence <> (
        SELECT COALESCE(MAX(existing.sequence), 0) + 1
        FROM external_reviewer_registry_events AS existing
        WHERE existing.principal_id = NEW.principal_id
    );

    SELECT RAISE(ABORT, 'governance-v2: initial reviewer registry event mismatch')
    WHERE NEW.sequence = 1 AND NOT EXISTS (
        SELECT 1 FROM external_reviewer_registry AS registry
        WHERE registry.principal_id = NEW.principal_id
          AND registry.status = 'active'
          AND registry.registered_at = NEW.effective_at
          AND registry.registered_at = NEW.recorded_at
          AND registry.valid_until = NEW.valid_until
          AND registry.registered_by_principal_id = NEW.recorded_by_principal_id
          AND registry.registrar_evidence_sha256 = NEW.event_evidence_sha256
          AND registry.qualifications_summary = NEW.qualifications_summary
          AND registry.qualifications_sha256 = NEW.qualifications_sha256
          AND registry.signature_scheme = NEW.signature_scheme
          AND registry.signature_key_id = NEW.signature_key_id
          AND registry.public_key_sha256 = NEW.public_key_sha256
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer registry predecessor mismatch')
    WHERE NEW.sequence > 1 AND NOT EXISTS (
        SELECT 1 FROM external_reviewer_registry_events AS previous
        WHERE previous.id = NEW.supersedes_event_id
          AND previous.principal_id = NEW.principal_id
          AND previous.sequence = NEW.sequence - 1
          AND previous.status = 'active'
          AND previous.recorded_at <= NEW.effective_at
    );

    SELECT RAISE(ABORT, 'governance-v2: revocation must preserve the credential snapshot')
    WHERE NEW.event_type = 'revocation' AND NOT EXISTS (
        SELECT 1 FROM external_reviewer_registry_events AS previous
        WHERE previous.id = NEW.supersedes_event_id
          AND previous.valid_until = NEW.valid_until
          AND previous.qualifications_summary = NEW.qualifications_summary
          AND previous.qualifications_sha256 = NEW.qualifications_sha256
          AND previous.signature_scheme = NEW.signature_scheme
          AND previous.signature_key_id = NEW.signature_key_id
          AND previous.public_key_sha256 = NEW.public_key_sha256
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer registry supersession is a no-op')
    WHERE NEW.event_type = 'supersession' AND EXISTS (
        SELECT 1 FROM external_reviewer_registry_events AS previous
        WHERE previous.id = NEW.supersedes_event_id
          AND previous.valid_until = NEW.valid_until
          AND previous.qualifications_summary = NEW.qualifications_summary
          AND previous.qualifications_sha256 = NEW.qualifications_sha256
          AND previous.signature_scheme = NEW.signature_scheme
          AND previous.signature_key_id = NEW.signature_key_id
          AND previous.public_key_sha256 = NEW.public_key_sha256
    );
END;

CREATE TRIGGER IF NOT EXISTS release_governance_bindings_guard
BEFORE INSERT ON release_governance_bindings
BEGIN
    SELECT RAISE(
        ABORT,
        'governance-v2: stored calculation diagnostics are not the exact policy-v1 document'
    )
    WHERE (
        SELECT kapi_validate_calculation_diagnostics(
            calculation.diagnostics_json,
            calculation.status
        )
        FROM releases AS release
        JOIN calculations AS calculation ON calculation.id = release.calculation_id
        WHERE release.id = NEW.release_id
    ) IS NOT 1;

    SELECT RAISE(
        ABORT,
        'governance-v2: binding calculation disposition does not match calculation status'
    )
    WHERE NOT EXISTS (
        SELECT 1
        FROM releases AS release
        JOIN calculations AS calculation ON calculation.id = release.calculation_id
        WHERE release.id = NEW.release_id
          AND NEW.calculation_disposition = CASE calculation.status
              WHEN 'complete' THEN 'eligible'
              WHEN 'withheld' THEN 'withheld'
              WHEN 'pending_base' THEN 'incomplete'
              WHEN 'invalid' THEN 'incomplete'
          END
    );

    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.operator_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: invalid operator appointment')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.operator_assignment_id
          AND assignment.principal_id = NEW.operator_principal_id
          AND assignment.role = 'operator'
          AND assignment.valid_from <= NEW.bound_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.bound_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: invalid methodology-owner appointment')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.methodology_owner_assignment_id
          AND assignment.principal_id = NEW.methodology_owner_principal_id
          AND assignment.role = 'methodology_owner'
          AND assignment.valid_from <= NEW.bound_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.bound_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: release binding does not match calculation')
    WHERE NOT EXISTS (
        SELECT 1
        FROM releases AS release
        JOIN calculations AS calculation ON calculation.id = release.calculation_id
        WHERE release.id = NEW.release_id
          AND release.status = 'draft'
          AND calculation.methodology_id = NEW.methodology_id
          AND calculation.methodology_version = NEW.methodology_version
          AND calculation.code_commit = NEW.code_commit
    );

    SELECT RAISE(ABORT, 'governance-v2: methodology digest mismatch')
    WHERE NOT EXISTS (
        SELECT 1 FROM methodology_governance_gates AS gate
        WHERE gate.methodology_id = NEW.methodology_id
          AND gate.methodology_version = NEW.methodology_version
          AND gate.methodology_sha256 = NEW.methodology_sha256
    );

    SELECT RAISE(ABORT, 'governance-v2: bound artifact set hash mismatch')
    WHERE kapi_release_artifact_manifest_sha256(NEW.release_id)
          <> NEW.artifact_manifest_sha256;
END;

CREATE TRIGGER IF NOT EXISTS release_artifacts_no_post_binding_insert
BEFORE INSERT ON release_artifacts
WHEN EXISTS (
    SELECT 1 FROM release_governance_bindings AS binding
    WHERE binding.release_id = NEW.release_id
)
BEGIN
    SELECT RAISE(
        ABORT,
        'governance-v2: release artifact set is frozen after governance binding'
    );
END;

-- Child-set membership is frozen before its parent can feed the next governed
-- stage. Existing v1 UPDATE/DELETE guards protect row bytes; these guards also
-- prevent a late INSERT from making a stored manifest or calculation stale.
CREATE TRIGGER IF NOT EXISTS methodology_base_weeks_no_post_gate_insert
BEFORE INSERT ON methodology_base_weeks
WHEN EXISTS (
    SELECT 1 FROM methodology_governance_gates AS gate
    WHERE gate.methodology_id = NEW.methodology_id
      AND gate.methodology_version = NEW.methodology_version
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: methodology child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS methodology_thresholds_no_post_gate_insert
BEFORE INSERT ON methodology_thresholds
WHEN EXISTS (
    SELECT 1 FROM methodology_governance_gates AS gate
    WHERE gate.methodology_id = NEW.methodology_id
      AND gate.methodology_version = NEW.methodology_version
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: methodology child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS task_profiles_no_post_gate_insert
BEFORE INSERT ON task_profiles
WHEN EXISTS (
    SELECT 1 FROM methodology_governance_gates AS gate
    WHERE gate.methodology_id = NEW.methodology_id
      AND gate.methodology_version = NEW.methodology_version
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: methodology child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS task_profile_features_no_post_gate_insert
BEFORE INSERT ON task_profile_features
WHEN EXISTS (
    SELECT 1 FROM methodology_governance_gates AS gate
    WHERE gate.methodology_id = NEW.methodology_id
      AND gate.methodology_version = NEW.methodology_version
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: methodology child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS methodology_sensitivities_no_post_gate_insert
BEFORE INSERT ON methodology_sensitivities
WHEN EXISTS (
    SELECT 1 FROM methodology_governance_gates AS gate
    WHERE gate.methodology_id = NEW.methodology_id
      AND gate.methodology_version = NEW.methodology_version
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: methodology child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS snapshot_inputs_no_post_calculation_insert
BEFORE INSERT ON snapshot_inputs
WHEN EXISTS (
    SELECT 1 FROM calculations AS calculation
    WHERE calculation.snapshot_id = NEW.snapshot_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: snapshot input set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS calculation_profile_results_no_post_release_insert
BEFORE INSERT ON calculation_profile_results
WHEN EXISTS (
    SELECT 1 FROM releases AS release
    WHERE release.calculation_id = NEW.calculation_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: calculation child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS calculation_selected_endpoints_no_post_release_insert
BEFORE INSERT ON calculation_selected_endpoints
WHEN EXISTS (
    SELECT 1 FROM releases AS release
    WHERE release.calculation_id = NEW.calculation_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: calculation child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS calculation_validations_no_post_release_insert
BEFORE INSERT ON calculation_validations
WHEN EXISTS (
    SELECT 1 FROM releases AS release
    WHERE release.calculation_id = NEW.calculation_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: calculation child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS release_signoffs_no_post_binding_insert
BEFORE INSERT ON release_signoffs
WHEN EXISTS (
    SELECT 1 FROM release_governance_bindings AS binding
    WHERE binding.release_id = NEW.release_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: release child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS correction_releases_no_post_binding_insert
BEFORE INSERT ON correction_releases
WHEN EXISTS (
    SELECT 1 FROM release_governance_bindings AS binding
    WHERE binding.release_id = NEW.release_id
)
BEGIN SELECT RAISE(ABORT, 'governance-v2: release child set is frozen'); END;

CREATE TRIGGER IF NOT EXISTS external_review_records_guard
BEFORE INSERT ON external_review_records
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.reviewer_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: reviewer is not separated from operators or owners')
    WHERE NEW.reviewer_principal_id = NEW.methodology_owner_principal_id
       OR EXISTS (
           SELECT 1 FROM governance_role_assignments AS assignment
           WHERE assignment.principal_id = NEW.reviewer_principal_id
             AND assignment.role IN (
                 'operator', 'methodology_owner',
                 'release_authorizer', 'publication_authorizer'
             )
             AND assignment.valid_from <= NEW.reviewed_at
             AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.reviewed_at)
       )
       OR EXISTS (
           SELECT 1 FROM release_governance_bindings AS binding
           WHERE NEW.review_kind = 'release'
             AND binding.release_id = NEW.release_id
             AND NEW.reviewer_principal_id = binding.operator_principal_id
       );

    SELECT RAISE(ABORT, 'governance-v2: reviewer identity snapshot mismatch')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_principals AS principal
        WHERE principal.id = NEW.reviewer_principal_id
          AND principal.full_name = NEW.reviewer_full_name
          AND principal.affiliation = NEW.reviewer_affiliation
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer registry event is not the latest active credential')
    WHERE NOT EXISTS (
        SELECT 1 FROM external_reviewer_registry_events AS registry
        JOIN governance_principals AS principal
          ON principal.id = registry.principal_id
        WHERE registry.id = NEW.reviewer_registry_event_id
          AND registry.principal_id = NEW.reviewer_principal_id
          AND principal.principal_kind = 'human'
          AND registry.status = 'active'
          AND registry.signature_scheme = NEW.signature_scheme
          AND registry.signature_key_id = NEW.signature_key_id
          AND registry.effective_at <= NEW.reviewed_at
          AND registry.recorded_at <= NEW.reviewed_at
          AND registry.valid_until >= NEW.reviewed_at
          AND registry.sequence = (
              SELECT MAX(latest.sequence)
              FROM external_reviewer_registry_events AS latest
              WHERE latest.principal_id = NEW.reviewer_principal_id
                AND latest.effective_at <= NEW.reviewed_at
                AND latest.recorded_at <= NEW.reviewed_at
          )
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer appointment is missing or expired')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.reviewer_appointment_id
          AND assignment.principal_id = NEW.reviewer_principal_id
          AND assignment.role = 'external_reviewer'
          AND assignment.valid_from <= NEW.reviewed_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.reviewed_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: methodology-owner appointment is missing')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.principal_id = NEW.methodology_owner_principal_id
          AND assignment.role = 'methodology_owner'
          AND assignment.valid_from <= NEW.reviewed_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.reviewed_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: methodology review binding mismatch')
    WHERE NEW.review_kind = 'methodology'
      AND NOT EXISTS (
          SELECT 1 FROM methodology_governance_gates AS gate
          WHERE gate.methodology_id = NEW.methodology_id
            AND gate.methodology_version = NEW.methodology_version
            AND gate.methodology_sha256 = NEW.methodology_sha256
            AND gate.implementation_commit = NEW.code_commit
            AND gate.review_artifact_manifest_sha256 =
                NEW.review_artifact_manifest_sha256
      );

    SELECT RAISE(ABORT, 'governance-v2: release review binding mismatch')
    WHERE NEW.review_kind = 'release'
      AND NOT EXISTS (
          SELECT 1 FROM release_governance_bindings AS binding
          WHERE binding.release_id = NEW.release_id
            AND binding.methodology_id = NEW.methodology_id
            AND binding.methodology_version = NEW.methodology_version
            AND binding.methodology_sha256 = NEW.methodology_sha256
            AND binding.code_commit = NEW.code_commit
            AND binding.artifact_manifest_sha256 =
                NEW.review_artifact_manifest_sha256
      );

    SELECT RAISE(ABORT, 'governance-v2: review scope hash mismatch')
    WHERE kapi_sha256(NEW.scope_json) <> NEW.scope_sha256;

    SELECT RAISE(ABORT, 'governance-v2: conflict declaration hash mismatch')
    WHERE kapi_sha256(NEW.conflict_declaration_json)
          <> NEW.conflict_declaration_sha256;

    SELECT RAISE(ABORT, 'governance-v2: relationship disclosure hash mismatch')
    WHERE kapi_sha256(NEW.relationship_disclosure_json)
          <> NEW.relationship_disclosure_sha256;

    SELECT RAISE(ABORT, 'governance-v2: compensation disclosure hash mismatch')
    WHERE kapi_sha256(NEW.compensation_disclosure_json)
          <> NEW.compensation_disclosure_sha256;

    SELECT RAISE(ABORT, 'governance-v2: findings hash mismatch')
    WHERE kapi_sha256(NEW.findings_json) <> NEW.findings_sha256;

    SELECT RAISE(ABORT, 'governance-v2: unresolved-issues hash mismatch')
    WHERE kapi_sha256(NEW.unresolved_issues_json)
          <> NEW.unresolved_issues_sha256;

    SELECT RAISE(ABORT, 'governance-v2: disclosure chronology is invalid')
    WHERE NEW.conflict_declared_at > NEW.reviewed_at
       OR NEW.disclosures_declared_at > NEW.reviewed_at
       OR NEW.reviewed_at > NEW.recorded_at;
END;

CREATE TRIGGER IF NOT EXISTS signature_verification_attestations_guard
BEFORE INSERT ON signature_verification_attestations
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.verifier_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: signature verifier appointment is invalid')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.verifier_assignment_id
          AND assignment.principal_id = NEW.verifier_principal_id
          AND assignment.role = 'reviewer_registrar'
          AND assignment.valid_from <= NEW.verified_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.verified_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: reviewer cannot verify own signature')
    WHERE EXISTS (
        SELECT 1 FROM external_review_records AS review
        WHERE review.id = NEW.review_record_id
          AND review.reviewer_principal_id = NEW.verifier_principal_id
    );

    SELECT RAISE(ABORT, 'governance-v2: signature verifier cannot hold a review or Kingy authorizer role')
    WHERE EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.principal_id = NEW.verifier_principal_id
          AND assignment.role IN (
              'operator', 'methodology_owner', 'external_reviewer',
              'release_authorizer', 'publication_authorizer'
          )
          AND assignment.valid_from <= NEW.verified_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.verified_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: signature verification does not match review')
    WHERE NOT EXISTS (
        SELECT 1 FROM external_review_records AS review
        WHERE review.id = NEW.review_record_id
          AND review.signature_scheme = NEW.signature_scheme
          AND review.signature_key_id = NEW.signature_key_id
          AND review.signed_payload_sha256 = NEW.signed_payload_sha256
          AND review.signature_evidence_sha256 = NEW.signature_evidence_sha256
          AND review.recorded_at <= NEW.verified_at
          AND EXISTS (
              SELECT 1
              FROM external_reviewer_registry_events AS registry
              WHERE registry.id = review.reviewer_registry_event_id
                AND registry.principal_id = review.reviewer_principal_id
                AND registry.status = 'active'
                AND registry.signature_scheme = review.signature_scheme
                AND registry.signature_key_id = review.signature_key_id
                AND registry.effective_at <= NEW.verified_at
                AND registry.recorded_at <= NEW.verified_at
                AND registry.valid_until >= NEW.verified_at
                AND registry.sequence = (
                    SELECT MAX(latest.sequence)
                    FROM external_reviewer_registry_events AS latest
                    WHERE latest.principal_id = review.reviewer_principal_id
                      AND latest.effective_at <= NEW.verified_at
                      AND latest.recorded_at <= NEW.verified_at
                )
          )
    );
END;

CREATE TRIGGER IF NOT EXISTS governance_transition_events_guard
BEFORE INSERT ON governance_transition_events
BEGIN
    SELECT RAISE(ABORT, 'governance-v2: trusted-adapter actor binding missing or mismatched')
    WHERE kapi_local_actor_binding() IS NULL
       OR kapi_local_actor_binding() <> NEW.actor_principal_id;

    SELECT RAISE(ABORT, 'governance-v2: transition actor appointment is invalid')
    WHERE NOT EXISTS (
        SELECT 1 FROM governance_role_assignments AS assignment
        WHERE assignment.id = NEW.actor_assignment_id
          AND assignment.principal_id = NEW.actor_principal_id
          AND assignment.valid_from <= NEW.transitioned_at
          AND (assignment.valid_until IS NULL OR assignment.valid_until >= NEW.transitioned_at)
    );

    SELECT RAISE(ABORT, 'governance-v2: transition release binding mismatch')
    WHERE NOT EXISTS (
        SELECT 1 FROM release_governance_bindings AS binding
        WHERE binding.release_id = NEW.release_id
          AND binding.calculation_disposition = NEW.calculation_disposition
          AND binding.artifact_manifest_sha256 = NEW.artifact_manifest_sha256
    );

    SELECT RAISE(ABORT, 'governance-v2: transition sequence is not append-only')
    WHERE NEW.sequence <> (
        SELECT COALESCE(MAX(existing.sequence), 0) + 1
        FROM governance_transition_events AS existing
        WHERE existing.release_id = NEW.release_id
    );

    SELECT RAISE(ABORT, 'governance-v2: transition predecessor mismatch')
    WHERE (
        NEW.sequence = 1 AND NOT (
            NEW.from_governance_state = 'draft' AND
            NEW.from_publication_state = 'not_authorized'
        )
    ) OR (
        NEW.sequence > 1 AND NOT EXISTS (
            SELECT 1 FROM governance_transition_events AS previous
            WHERE previous.release_id = NEW.release_id
              AND previous.sequence = NEW.sequence - 1
              AND previous.governance_state = NEW.from_governance_state
              AND previous.publication_state = NEW.from_publication_state
              AND previous.transitioned_at <= NEW.transitioned_at
        )
    );

    SELECT RAISE(ABORT, 'governance-v2: transition edge or role is not authorized')
    WHERE NOT (
        (
            NEW.sequence = 1 AND
            NEW.from_governance_state = 'draft' AND
            NEW.governance_state = 'unreviewed' AND
            NEW.publication_state = 'not_authorized' AND
            NEW.review_label = 'Governance status: Unreviewed draft. Automated validation completed for this artifact; no operator or external methodology review is complete.' AND
            NEW.methodology_review_record_id IS NULL AND
            NEW.release_review_record_id IS NULL AND
            EXISTS (
                SELECT 1 FROM release_governance_bindings AS binding
                WHERE binding.release_id = NEW.release_id
                  AND binding.operator_principal_id = NEW.actor_principal_id
                  AND binding.operator_assignment_id = NEW.actor_assignment_id
            )
        ) OR (
            NEW.from_governance_state = 'operator_reviewed' AND
            NEW.from_publication_state = 'not_authorized' AND
            NEW.governance_state = 'external_release_reviewed' AND
            NEW.publication_state = 'not_authorized' AND
            NEW.review_label = 'Governance status: This exact release received named external review and recalculation under the recorded scope.' AND
            NEW.release_review_record_id IS NOT NULL AND
            EXISTS (
                SELECT 1
                FROM release_governance_bindings AS binding
                JOIN methodology_governance_gates AS gate
                  ON gate.methodology_id = binding.methodology_id
                 AND gate.methodology_version = binding.methodology_version
                WHERE binding.release_id = NEW.release_id
                  AND gate.trusted_verifier_gate = 'passed'
            ) AND
            EXISTS (
                SELECT 1
                FROM governance_role_assignments AS assignment
                JOIN external_review_records AS review
                  ON review.id = NEW.release_review_record_id
                WHERE assignment.id = NEW.actor_assignment_id
                  AND assignment.role = 'release_authorizer'
                  AND review.review_kind = 'release'
                  AND review.release_id = NEW.release_id
                  AND review.outcome = 'approved'
                  AND review.unresolved_issues_json = '[]'
                  AND review.valid_until >= NEW.transitioned_at
                  AND review.reviewer_principal_id <> NEW.actor_principal_id
                  AND EXISTS (
                      SELECT 1
                      FROM external_reviewer_registry_events AS registry
                      WHERE registry.id = review.reviewer_registry_event_id
                        AND registry.principal_id = review.reviewer_principal_id
                        AND registry.status = 'active'
                        AND registry.signature_scheme = review.signature_scheme
                        AND registry.signature_key_id = review.signature_key_id
                        AND registry.effective_at <= NEW.transitioned_at
                        AND registry.recorded_at <= NEW.transitioned_at
                        AND registry.valid_until >= NEW.transitioned_at
                        AND registry.sequence = (
                            SELECT MAX(latest.sequence)
                            FROM external_reviewer_registry_events AS latest
                            WHERE latest.principal_id = review.reviewer_principal_id
                              AND latest.effective_at <= NEW.transitioned_at
                              AND latest.recorded_at <= NEW.transitioned_at
                        )
                  )
                  AND EXISTS (
                      SELECT 1 FROM signature_verification_attestations AS verification
                      WHERE verification.review_record_id = review.id
                        AND verification.status = 'verified_out_of_band'
                        AND verification.verified_at <= NEW.transitioned_at
                  )
            )
        ) OR (
            NEW.from_governance_state = 'operator_reviewed' AND
            NEW.from_publication_state = 'not_authorized' AND
            NEW.governance_state = 'operator_reviewed' AND
            NEW.publication_state = 'ready' AND
            NEW.review_label = 'Governance status: Operator-reviewed by Kingy.ai and automatically checked. External methodology review is complete; this release was not externally reviewed.' AND
            NEW.calculation_disposition = 'eligible' AND
            NEW.methodology_review_record_id IS NOT NULL AND
            NEW.release_review_record_id IS NULL AND
            EXISTS (
                SELECT 1 FROM governance_role_assignments AS assignment
                WHERE assignment.id = NEW.actor_assignment_id
                  AND assignment.role = 'publication_authorizer'
            ) AND
            EXISTS (
                SELECT 1
                FROM external_review_records AS review
                JOIN release_governance_bindings AS binding
                  ON binding.release_id = NEW.release_id
                WHERE review.id = NEW.methodology_review_record_id
                  AND review.review_kind = 'methodology'
                  AND review.release_id IS NULL
                  AND review.methodology_id = binding.methodology_id
                  AND review.methodology_version = binding.methodology_version
                  AND review.methodology_sha256 = binding.methodology_sha256
                  AND review.outcome = 'approved'
                  AND review.unresolved_issues_json = '[]'
                  AND review.valid_until >= NEW.transitioned_at
                  AND review.reviewer_principal_id <> NEW.actor_principal_id
                  AND EXISTS (
                      SELECT 1
                      FROM external_reviewer_registry_events AS registry
                      WHERE registry.id = review.reviewer_registry_event_id
                        AND registry.principal_id = review.reviewer_principal_id
                        AND registry.status = 'active'
                        AND registry.signature_scheme = review.signature_scheme
                        AND registry.signature_key_id = review.signature_key_id
                        AND registry.effective_at <= NEW.transitioned_at
                        AND registry.recorded_at <= NEW.transitioned_at
                        AND registry.valid_until >= NEW.transitioned_at
                        AND registry.sequence = (
                            SELECT MAX(latest.sequence)
                            FROM external_reviewer_registry_events AS latest
                            WHERE latest.principal_id = review.reviewer_principal_id
                              AND latest.effective_at <= NEW.transitioned_at
                              AND latest.recorded_at <= NEW.transitioned_at
                        )
                  )
                  AND EXISTS (
                      SELECT 1 FROM signature_verification_attestations AS verification
                      WHERE verification.review_record_id = review.id
                        AND verification.status = 'verified_out_of_band'
                        AND verification.verified_at <= NEW.transitioned_at
                  )
            ) AND
            EXISTS (
                SELECT 1
                FROM release_governance_bindings AS binding
                JOIN methodology_governance_gates AS gate
                  ON gate.methodology_id = binding.methodology_id
                 AND gate.methodology_version = binding.methodology_version
                WHERE binding.release_id = NEW.release_id
                  AND binding.code_commit = gate.implementation_commit
                  AND gate.technical_gate = 'passed'
                  AND gate.operational_gate = 'passed'
                  AND gate.trusted_verifier_gate = 'passed'
            ) AND
            EXISTS (
                SELECT 1 FROM release_artifacts AS artifact
                WHERE artifact.release_id = NEW.release_id
            )
        ) OR (
            NEW.from_governance_state = 'external_release_reviewed' AND
            NEW.from_publication_state = 'not_authorized' AND
            NEW.governance_state = 'external_release_reviewed' AND
            NEW.publication_state = 'ready' AND
            NEW.review_label = 'Governance status: This exact release received named external review and recalculation under the recorded scope.' AND
            NEW.calculation_disposition = 'eligible' AND
            NEW.methodology_review_record_id IS NOT NULL AND
            NEW.release_review_record_id IS NOT NULL AND
            EXISTS (
                SELECT 1 FROM governance_role_assignments AS assignment
                WHERE assignment.id = NEW.actor_assignment_id
                  AND assignment.role = 'publication_authorizer'
            ) AND
            EXISTS (
                SELECT 1
                FROM external_review_records AS methodology_review
                JOIN release_governance_bindings AS binding
                  ON binding.release_id = NEW.release_id
                WHERE methodology_review.id = NEW.methodology_review_record_id
                  AND methodology_review.review_kind = 'methodology'
                  AND methodology_review.methodology_id = binding.methodology_id
                  AND methodology_review.methodology_version = binding.methodology_version
                  AND methodology_review.methodology_sha256 = binding.methodology_sha256
                  AND methodology_review.outcome = 'approved'
                  AND methodology_review.unresolved_issues_json = '[]'
                  AND methodology_review.valid_until >= NEW.transitioned_at
                  AND methodology_review.reviewer_principal_id <> NEW.actor_principal_id
                  AND EXISTS (
                      SELECT 1
                      FROM external_reviewer_registry_events AS registry
                      WHERE registry.id = methodology_review.reviewer_registry_event_id
                        AND registry.principal_id = methodology_review.reviewer_principal_id
                        AND registry.status = 'active'
                        AND registry.signature_scheme = methodology_review.signature_scheme
                        AND registry.signature_key_id = methodology_review.signature_key_id
                        AND registry.effective_at <= NEW.transitioned_at
                        AND registry.recorded_at <= NEW.transitioned_at
                        AND registry.valid_until >= NEW.transitioned_at
                        AND registry.sequence = (
                            SELECT MAX(latest.sequence)
                            FROM external_reviewer_registry_events AS latest
                            WHERE latest.principal_id = methodology_review.reviewer_principal_id
                              AND latest.effective_at <= NEW.transitioned_at
                              AND latest.recorded_at <= NEW.transitioned_at
                        )
                  )
                  AND EXISTS (
                      SELECT 1 FROM signature_verification_attestations AS verification
                      WHERE verification.review_record_id = methodology_review.id
                        AND verification.status = 'verified_out_of_band'
                        AND verification.verified_at <= NEW.transitioned_at
                  )
            ) AND
            EXISTS (
                SELECT 1 FROM external_review_records AS release_review
                WHERE release_review.id = NEW.release_review_record_id
                  AND release_review.review_kind = 'release'
                  AND release_review.release_id = NEW.release_id
                  AND release_review.outcome = 'approved'
                  AND release_review.unresolved_issues_json = '[]'
                  AND release_review.valid_until >= NEW.transitioned_at
                  AND EXISTS (
                      SELECT 1
                      FROM external_reviewer_registry_events AS registry
                      WHERE registry.id = release_review.reviewer_registry_event_id
                        AND registry.principal_id = release_review.reviewer_principal_id
                        AND registry.status = 'active'
                        AND registry.signature_scheme = release_review.signature_scheme
                        AND registry.signature_key_id = release_review.signature_key_id
                        AND registry.effective_at <= NEW.transitioned_at
                        AND registry.recorded_at <= NEW.transitioned_at
                        AND registry.valid_until >= NEW.transitioned_at
                        AND registry.sequence = (
                            SELECT MAX(latest.sequence)
                            FROM external_reviewer_registry_events AS latest
                            WHERE latest.principal_id = release_review.reviewer_principal_id
                              AND latest.effective_at <= NEW.transitioned_at
                              AND latest.recorded_at <= NEW.transitioned_at
                        )
                  )
                  AND EXISTS (
                      SELECT 1 FROM signature_verification_attestations AS verification
                      WHERE verification.review_record_id = release_review.id
                        AND verification.status = 'verified_out_of_band'
                        AND verification.verified_at <= NEW.transitioned_at
                  )
            ) AND
            EXISTS (
                SELECT 1
                FROM release_governance_bindings AS binding
                JOIN methodology_governance_gates AS gate
                  ON gate.methodology_id = binding.methodology_id
                 AND gate.methodology_version = binding.methodology_version
                WHERE binding.release_id = NEW.release_id
                  AND binding.code_commit = gate.implementation_commit
                  AND gate.technical_gate = 'passed'
                  AND gate.operational_gate = 'passed'
                  AND gate.trusted_verifier_gate = 'passed'
            ) AND
            EXISTS (
                SELECT 1 FROM release_artifacts AS artifact
                WHERE artifact.release_id = NEW.release_id
            )
        ) OR (
            NEW.governance_state = 'withdrawn' AND
            NEW.publication_state = 'withdrawn' AND
            EXISTS (
                SELECT 1 FROM governance_role_assignments AS assignment
                WHERE assignment.id = NEW.actor_assignment_id
                  AND assignment.role IN ('release_authorizer', 'publication_authorizer')
            )
        )
    );
END;

CREATE TRIGGER IF NOT EXISTS governance_principals_no_update
BEFORE UPDATE ON governance_principals
BEGIN SELECT RAISE(ABORT, 'append-only: governance_principals cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS governance_principals_no_delete
BEFORE DELETE ON governance_principals
BEGIN SELECT RAISE(ABORT, 'append-only: governance_principals cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS governance_role_assignments_no_update
BEFORE UPDATE ON governance_role_assignments
BEGIN SELECT RAISE(ABORT, 'append-only: governance_role_assignments cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS governance_role_assignments_no_delete
BEFORE DELETE ON governance_role_assignments
BEGIN SELECT RAISE(ABORT, 'append-only: governance_role_assignments cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_no_update
BEFORE UPDATE ON external_reviewer_registry
BEGIN SELECT RAISE(ABORT, 'append-only: external_reviewer_registry cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_no_delete
BEFORE DELETE ON external_reviewer_registry
BEGIN SELECT RAISE(ABORT, 'append-only: external_reviewer_registry cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_events_no_update
BEFORE UPDATE ON external_reviewer_registry_events
BEGIN SELECT RAISE(ABORT, 'append-only: external_reviewer_registry_events cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS external_reviewer_registry_events_no_delete
BEFORE DELETE ON external_reviewer_registry_events
BEGIN SELECT RAISE(ABORT, 'append-only: external_reviewer_registry_events cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS methodology_governance_gates_no_update
BEFORE UPDATE ON methodology_governance_gates
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_governance_gates cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS methodology_governance_gates_no_delete
BEFORE DELETE ON methodology_governance_gates
BEGIN SELECT RAISE(ABORT, 'append-only: methodology_governance_gates cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS release_governance_bindings_no_update
BEFORE UPDATE ON release_governance_bindings
BEGIN SELECT RAISE(ABORT, 'append-only: release_governance_bindings cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS release_governance_bindings_no_delete
BEFORE DELETE ON release_governance_bindings
BEGIN SELECT RAISE(ABORT, 'append-only: release_governance_bindings cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS external_review_records_no_update
BEFORE UPDATE ON external_review_records
BEGIN SELECT RAISE(ABORT, 'append-only: external_review_records cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS external_review_records_no_delete
BEFORE DELETE ON external_review_records
BEGIN SELECT RAISE(ABORT, 'append-only: external_review_records cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS signature_verification_attestations_no_update
BEFORE UPDATE ON signature_verification_attestations
BEGIN SELECT RAISE(ABORT, 'append-only: signature_verification_attestations cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS signature_verification_attestations_no_delete
BEFORE DELETE ON signature_verification_attestations
BEGIN SELECT RAISE(ABORT, 'append-only: signature_verification_attestations cannot be deleted'); END;

CREATE TRIGGER IF NOT EXISTS governance_transition_events_no_update
BEFORE UPDATE ON governance_transition_events
BEGIN SELECT RAISE(ABORT, 'append-only: governance_transition_events cannot be updated'); END;
CREATE TRIGGER IF NOT EXISTS governance_transition_events_no_delete
BEFORE DELETE ON governance_transition_events
BEGIN SELECT RAISE(ABORT, 'append-only: governance_transition_events cannot be deleted'); END;

PRAGMA user_version = 2;
