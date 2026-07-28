# KAPI-SW isolated research prototype

This directory implements Phases 1–3 of the Kingy AI Price Index plan:

1. an append-only normalized data foundation;
2. a versioned methodology and frozen synthetic workload profiles;
3. a deterministic local calculation, export, and reproduction pipeline.

It is a **synthetic, unpublished prototype**. It is not an official KAPI
reading, does not run AI models, and must not be connected to WordPress
production without separate authorization.

## Boundaries

- Authorized external spend for this implementation task: $0; the local action
  log records no paid API/model call. Release artifacts do not infer that fact
  from caller metadata: their manifest says generation spend and provider
  activity are `not_measured_not_evidenced`.
- Production credentials: none.
- WordPress or production writes: none.
- Deployment/publication/shadow operation: none.
- Runtime dependencies: Python standard library and SQLite only. Portable
  payload reproduction reads a frozen 12-entry construction manifest. Full
  `o200k_base` proof is a separate explicit local-asset operation; neither the
  complete asset nor package code is installed or vendored.
- Methodology v0.3.0 is the current forward governance vintage. It preserves
  the v0.2.2 calculation and provider-evidence rules, replaces ambiguous legacy
  review terminology, and pins governance policy v1.0.0. It does not verify
  model mapping, provider runtime behavior, request counts, or billed usage.
- Governance policy v1.0.0 is forward-only and fail-closed. The local actor
  binding is not authentication; neither a trusted operator-identity adapter
  nor a trusted signature-verifier adapter exists. Current calculations remain
  explicitly unreviewed and not authorized for publication. See
  `docs/GOVERNANCE_POLICY_v1.0.0.md`.
- Reviewer registrations now have append-only key/qualification supersession
  and revocation events. Reviews bind one exact registry event, and modeled
  signature/claim transitions recheck the latest state. This prevents stale
  local credentials from advancing but does not make the local process trusted.
- The v0.3.0 input uses closed object-key sets and a closed path-specific scalar
  grammar. Every public string is an exact safe value, closed enum, hash,
  timestamp, reference, or narrowly defined machine identifier; arbitrary
  source/license/status prose is not accepted. Inputs are ASCII-only in this
  vintage. Bounded percent/HTML/literal-escape decoding rejects both decoded
  claims and residual encodings. Recursive claim scans remain defense in depth.
  Finally, the canonical object hash must equal the deterministic generated
  fixture. Future fields, prose, character sets, identifier grammars, dynamic
  data, or observed data require a new reviewed bundle-schema vintage.
- The active calculation, render, export, and CLI release paths accept only
  that exact v0.3.0 fixture/methodology pair. Historical v0.2.x validation is
  read-only and hash-pinned to the retained fixture and committed methodology
  documents; historical release reproduction uses each pinned old code
  checkout. Rewriting v0.3 markers to resemble a legacy bundle cannot mint a
  new artifact through current code.

## Quick start

Run commands from the repository root:

    python3 -m kapi validate-method \
      --method kapi/config/methodology-v0.3.0.json

    python3 -m kapi validate \
      --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
      --method kapi/config/methodology-v0.3.0.json

    python3 -m kapi calculate \
      --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
      --method kapi/config/methodology-v0.3.0.json \
      --output kapi/outputs/sample/calculation.json

    python3 -m kapi export \
      --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
      --method kapi/config/methodology-v0.3.0.json \
      --output-dir kapi/outputs/sample-release-governance-v1.0.0

    python3 -m kapi reproduce \
      --release-dir kapi/outputs/sample-release-governance-v1.0.0

Exercise append-only SQLite storage:

    python3 -m kapi init-db --database kapi/.tmp/kapi.sqlite3

    python3 -m kapi ingest \
      --database kapi/.tmp/kapi.sqlite3 \
      --bundle kapi/fixtures/synthetic-forward-governance-v0.3.0.json \
      --method kapi/config/methodology-v0.3.0.json

    python3 -m kapi dump \
      --database kapi/.tmp/kapi.sqlite3 \
      --output kapi/.tmp/roundtrip.json

Run the complete standard-library test suite:

    python3 -m unittest discover -s kapi/tests -v

Verify payloads in a fresh checkout without the full tokenizer asset:

    env -u KAPI_O200K_ASSET_PATH \
      python3 kapi/fixtures/build_payloads.py --check-frozen-manifest

Separately prove the frozen manifest against the retained full asset:

    python3 kapi/fixtures/build_payloads.py --verify-source-asset \
      --asset-path /explicit/local/path/to/o200k_base.tiktoken

## Expected synthetic diagnostic

The required hand example has thirteen identical base weeks and one current
week. With equal category weights it produces:

- base representative-profile cost: $0.0412666667;
- current representative-profile cost: $0.0222166667;
- diagnostic index: 53.8368, displayed as 53.8;
- 60-profile basket: $2.476 to $1.333;
- geometric sensitivity: approximately 52.59;
- absolute-frontier sensitivity: 50.0.

The same fixture breaches the configured concentration cap. The calculation
therefore retains the diagnostic values but marks the release
withheld_concentration. This is intentional fail-closed behavior.

## Directory map

- CONTRACT.md — internal prototype contract.
- config/ — immutable methodology configuration.
- evidence/ — frozen local official-document metadata and candidate findings.
- profiles/ — frozen synthetic payloads, exact construction counts, and hashes.
- fixtures/ — deterministic normalized input data; the current forward fixture
  is generated separately from the byte-immutable historical hand example.
- schema/ — append-only SQLite schema and triggers.
- calculation.py — pure Decimal calculation engine.
- validation.py — schema and cross-reference gates.
- store.py — append-only SQLite ingestion/dump layer.
- exporter.py — CSV/JSON/release/manifest renderer.
- cli.py — validate, calculate, export, reproduce, and storage commands.
- governance.py — forward-only review records and fail-closed transition model;
  governance mutation commands are intentionally not exposed by the CLI.
- secondary.py — implementation-isolated arithmetic cross-check; it is not a
  human review or governance authorization source, and policy v1.0.0 does not
  accept its caller-supplied output as lifecycle evidence.
- independent.py — byte-immutable v0.2.x implementation retained only because
  historical manifests pin its SHA-256; active code does not import it.
- tests/ — unit, regression, integration, and reproduction tests.
- docs/ — architecture, integration, operations, limitations, and controls.
- outputs/ — generated synthetic sample artifacts.

## Safety

The exporter labels every artifact as synthetic and not for publication.
Nothing in this directory publishes content or talks to a provider API.
