# Implementation summary

## Workspace

- Isolated worktree: /Users/curtispyke/Documents/Codex/2026-07-09/goal-implement-a-bounded-zero-spend/work/KingyAI-kapi-ci-workflow-local
- Isolated branch: agent/kapi-ci-workflow-local
- Starting commit: 2f1b1d61520b115bd09797a5b7318cff9e090e11

The original dirty worktree was not edited, cleaned, reset, committed, or
overwritten.

## File-by-file implementation

### Package and commands

| File | Purpose |
|---|---|
| kapi/__init__.py | Package identity and prototype version |
| kapi/__main__.py | python3 -m kapi entry point |
| kapi/cli.py | validate, calculate, export, reproduce, init-db, ingest, and dump commands |
| kapi/util.py | Canonical JSON/CSV, SHA-256, Decimal, UTC, and atomic-write helpers |
| kapi/validation.py | Method, payload, identity, evidence, price, tier, and conflict gates plus closed forward object/type/scalar schemas, exact current/historical vintage hashes, bounded-decoder residual rejection, and recursive/split claim-carrier defense |
| kapi/calculation.py | Pure Decimal eligibility, selection, index, sensitivity, concentration, contribution, and lineage engine with an exact-v0.3-only active entry-point gate and internal arithmetic test harness |
| kapi/exporter.py | Frozen CSV/JSON release bundle, exact-v0.3-only pre-write gate, content-derived release identity, authoritative validation/calculation recomputation, recursive governance-envelope enforcement, provenance manifest, and byte-exact reproduction |
| kapi/store.py | Transactional append-only SQLite ingestion and canonical dump |
| kapi/governance.py | Separated governance records, append-only reviewer supersession/revocation, complete review attribution, labels, and fail-closed transition API |
| kapi/config/governance-policy-v1.0.0.json | Current staged-hybrid target, registry lifecycle, immutable binding rules, diagnostic claim controls, and hard-disabled operator/verifier adapters |
| kapi/schema/002_governance.sql | Forward-only unreviewed governance migration, exact calculation-diagnostic insert/binding guards, exact binding/child-set freeze, reviewer-lifecycle guards, and transition guards |

### Data and methodology

| File/group | Purpose |
|---|---|
| kapi/config/methodology-v0.1.0.json | Prior immutable methodology vintage |
| kapi/config/methodology-v0.2.0.json | Proposed local-review amendment for exact construction counts, candidate remediation, and evidence-class separation |
| kapi/docs/DECISION_RECORD_v0.2.0.md | Human-readable v0.2.0 decision record |
| kapi/config/methodology-v0.2.1.json | Local-review candidate-evidence amendment preserving the v0.2.0 construction vintage |
| kapi/evidence/official-provider-configuration-evidence-2026-07-10.json | Frozen official-document source ids, URLs, timestamps, hashes, findings, and zero-action limits |
| kapi/docs/DECISION_RECORD_v0.2.1.md | Human-readable v0.2.1 candidate and evidence decision record |
| kapi/docs/ENDPOINT_AND_GATE_MATRICES_v0.2.1.md | v0.2.1 documentation, count-evidence, and readiness matrices |
| kapi/config/methodology-v0.2.2.json | CI-portability amendment separating frozen-manifest reproduction from full source-asset proof |
| kapi/config/methodology-v0.3.0.json | Current forward governance vintage; calculation rules unchanged, ambiguous review terminology removed, policy v1.0.0 pinned fail-closed |
| kapi/secondary.py | Standalone implementation-isolated arithmetic cross-check; not human review and not accepted as caller-supplied lifecycle evidence in policy v1.0.0 |
| kapi/independent.py | Byte-immutable v0.2.x historical module retained only for manifest hash continuity; not imported by active code |
| kapi/fixtures/o200k-construction-manifest-v1.json | Byte-immutable historical v0.2.2 12-entry manifest; retained for exact reproduction |
| kapi/fixtures/o200k-construction-manifest-v2.json | Current 12-entry pinned chunk/rank manifest; no complete tokenizer asset |
| kapi/docs/DECISION_RECORD_v0.2.2.md | Human-readable v0.2.2 portability decision record |
| kapi/docs/ENDPOINT_AND_GATE_MATRICES_v0.2.2.md | v0.2.2 construction-evidence and readiness matrices |
| kapi/profiles/* | 36 frozen synthetic input/output payloads across six profiles and 75/100/125 size variants |
| kapi/fixtures/build_payloads.py | Portable payload generator/checker plus explicit full source-asset proof mode |
| kapi/fixtures/build_synthetic.py | Offline deterministic fixture generator/checker |
| kapi/fixtures/synthetic-hand-example-v1.json | Byte-immutable historical four-provider regression bundle retained for v0.2.x reproduction |
| kapi/fixtures/build_forward_bundle.py | Deterministic forward-only projection generator/checker; removes caller result/spend and redundant derived-week metadata, and pins the exact v0.3.0 methodology |
| kapi/fixtures/synthetic-forward-governance-v0.3.0.json | Sole accepted bounded synthetic v0.3.0 bundle, canonical-hash pinned with closed public scalar/type grammar and no caller oracle/generation fields |
| kapi/schema/001_initial.sql | Normalized source-to-release schema and 29-table append-only triggers |

### Tests

| File | Coverage |
|---|---|
| kapi/tests/test_calculation.py | 25 calculation, eligibility, price, selection, base, sensitivity, correction-cycle, and concentration regressions |
| kapi/tests/test_store.py | 12 schema, required-ID/link, source-hash, append-only, rollback, conflict, supersession/correction-cycle, lineage, and round-trip tests |
| kapi/tests/test_fixtures.py | 16 methodology, portability, version-preservation, official-evidence, payload, hash, grid, offline-generation, and hand-example tests |
| kapi/tests/test_validation.py | 21 strict source-byte, portability-contract, closed forward schema/keyset/type/scalar grammar, bounded-fixture and historical-vintage identity, coordinated downgrade rejection, recursive normalized claim-key/prose and split-carrier, candidate-evidence, runtime-gate, cross-record, payload-grid, Decimal, supersession-cycle, and conflict tests |
| kapi/tests/test_exporter.py | 14 required-artifact, pre-render recursive/split claim-carrier, exact bounded-fixture identity, coordinated downgrade/direct fail-closed calculation/render/export, recursive governance-label, coverage, stable-ID, manifest/inventory-tamper, weight-reproduction, and deterministic export tests |
| kapi/tests/test_cli.py | 2 end-to-end CLI, database, export, and reproduction tests |
| kapi/tests/test_week0_controls.py | 6 truthful-label, base-policy, standalone secondary-check, absence-only secondary-input, exact diagnostic-schema/claim-carrier, raw-SQL/schema-v1 migration, lifecycle, and 10x-unit drill regressions |
| kapi/tests/test_governance.py | 10 identity, role, disclosure, registry rotation/revocation, complete-attribution, exact-binding, child-set freeze, signature-claim, transition, hard-gate, and historical-immutability regressions |

### Documentation

| File | Purpose |
|---|---|
| kapi/CONTRACT.md | Shared internal bundle/method/calculation contract |
| kapi/README.md | Boundaries, quick start, expected diagnostics, and directory guide |
| kapi/docs/ARCHITECTURE.md | Source-to-output architecture and determinism |
| kapi/docs/SCHEMA.md | Normalized entities, constraints, triggers, and store API |
| kapi/docs/KALI_INTEGRATION.md | Future artifact-consumer integration without production changes |
| kapi/docs/OPERATOR_RUNBOOK.md | Local validate/calculate/export/reproduce and incident procedure |
| kapi/docs/LIMITATIONS.md | Data, tokenizer, quality, statistical, and engineering limits |
| kapi/docs/SPENDING_REPORT.md | $0 limits and actual external actions |
| kapi/docs/VERIFICATION.md | Final command, test, hash, and boundary evidence |
| kapi/docs/GOVERNANCE_POLICY_v1.0.0.md | Current unreviewed boundary and named-reviewer activation standard |

### Generated synthetic releases

The historical directories `kapi/outputs/sample-release`,
`kapi/outputs/sample-release-v0.2.1`, and
`kapi/outputs/sample-release-v0.2.2` retain their prior vintages. The
current-code directory `kapi/outputs/sample-release-governance-v1.0.0` contains:

- frozen dataset and methodology inputs;
- calculation.json;
- release.json;
- latest.json;
- history.csv;
- components.csv;
- provenance-manifest.json.

Every file says or inherits that the result is synthetic and not for
publication. The latest diagnostic is withheld_concentration. The vintages are
kept separate because each provenance manifest pins its own code and methodology
hashes.

## Functional result

- Base representative profile: 0.0412666666666666… USD.
- Current representative profile: 0.0222166666666666… USD.
- Diagnostic index: 53.8368336025…; display 53.8.
- 60-profile basket: 2.476 USD to 1.333 USD.
- Geometric sensitivity: 52.5914757482….
- Absolute-frontier sensitivity: 50.
- Non-headline payload-size range: 52.7551020408… to 53.8368336025….
- Strict release status: withheld_concentration.

## Explicitly not done

- No model/API call.
- No paid or free-tier external inference.
- No production credential.
- No WordPress or KALI production write.
- No deployment, publication, or shadow release.
- No commit, push, or pull request.
