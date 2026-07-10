# Implementation summary

## Workspace

- Source repository: /Users/curtispyke/Documents/Codex/2026-07-09/i-am-looking-to-do-this/work/KingyAI-kapi-prototype
- Isolated worktree: /Users/curtispyke/Documents/Codex/2026-07-09/goal-implement-a-bounded-zero-spend/work/KingyAI-kapi-v020-remediation
- Isolated branch: agent/kapi-v020-zero-spend-remediation-20260710
- Starting commit: 1d59b964a5c895fc9a02a0b9ba58d4200f704419

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
| kapi/validation.py | Method, payload, schema, identity, evidence, price, tier, and conflict gates |
| kapi/calculation.py | Pure Decimal eligibility, selection, index, sensitivity, concentration, contribution, and lineage engine |
| kapi/exporter.py | Frozen CSV/JSON release bundle, provenance manifest, and byte-exact reproduction |
| kapi/store.py | Transactional append-only SQLite ingestion and canonical dump |

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
| kapi/profiles/* | 36 frozen synthetic input/output payloads across six profiles and 75/100/125 size variants |
| kapi/fixtures/build_payloads.py | Offline deterministic exact construction-payload generator/checker |
| kapi/fixtures/build_synthetic.py | Offline deterministic fixture generator/checker |
| kapi/fixtures/synthetic-hand-example-v1.json | Four-provider, 14-week, 216-token-count, 112-price-observation regression bundle |
| kapi/schema/001_initial.sql | Normalized source-to-release schema and 29-table append-only triggers |

### Tests

| File | Coverage |
|---|---|
| kapi/tests/test_calculation.py | 25 calculation, eligibility, price, selection, base, sensitivity, correction-cycle, and concentration regressions |
| kapi/tests/test_store.py | 12 schema, required-ID/link, source-hash, append-only, rollback, conflict, supersession/correction-cycle, lineage, and round-trip tests |
| kapi/tests/test_fixtures.py | 12 methodology, version-preservation, official-evidence, payload, hash, grid, offline-generation, and hand-example tests |
| kapi/tests/test_validation.py | 14 strict source-byte, candidate-evidence, evidence-hash, runtime-gate, cross-record, payload-grid, Decimal, identity, supersession-cycle, and conflict tests |
| kapi/tests/test_exporter.py | 8 required-artifact, labeling, coverage, manifest/inventory-tamper, weight-reproduction, and deterministic export tests |
| kapi/tests/test_cli.py | 2 end-to-end CLI, database, export, and reproduction tests |
| kapi/tests/test_week0_controls.py | 5 truthful-label, base-policy, independent-check, lifecycle, and 10x-unit drill regressions |

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

### Generated synthetic releases

The historical directory `kapi/outputs/sample-release` retains the v0.2.0
sample vintage. The current-code directory
`kapi/outputs/sample-release-v0.2.1` contains:

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
