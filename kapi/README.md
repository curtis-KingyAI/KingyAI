# KAPI-SW isolated research prototype

This directory implements Phases 1–3 of the Kingy AI Price Index plan:

1. an append-only normalized data foundation;
2. a versioned methodology and frozen synthetic workload profiles;
3. a deterministic local calculation, export, and reproduction pipeline.

It is a **synthetic, unpublished prototype**. It is not an official KAPI
reading, does not run AI models, and must not be connected to WordPress
production without separate authorization.

## Boundaries

- External spend: $0.
- Paid API/model calls: none.
- Production credentials: none.
- WordPress or production writes: none.
- Deployment/publication/shadow operation: none.
- Runtime dependencies: Python standard library and SQLite only. Portable
  payload reproduction reads a frozen 12-entry construction manifest. Full
  `o200k_base` proof is a separate explicit local-asset operation; neither the
  complete asset nor package code is installed or vendored.
- Methodology v0.2.2 adds CI-portable construction reproduction without
  changing the v0.2.1 provider-evidence findings. It does not verify model
  mapping, provider runtime behavior, request counts, or billed usage.

## Quick start

Run commands from the repository root:

    python3 -m kapi validate-method \
      --method kapi/config/methodology-v0.2.2.json

    python3 -m kapi validate \
      --bundle kapi/fixtures/synthetic-hand-example-v1.json \
      --method kapi/config/methodology-v0.2.2.json

    python3 -m kapi calculate \
      --bundle kapi/fixtures/synthetic-hand-example-v1.json \
      --method kapi/config/methodology-v0.2.2.json \
      --output kapi/outputs/sample/calculation.json

    python3 -m kapi export \
      --bundle kapi/fixtures/synthetic-hand-example-v1.json \
      --method kapi/config/methodology-v0.2.2.json \
      --output-dir kapi/outputs/sample-release-v0.2.2

    python3 -m kapi reproduce \
      --release-dir kapi/outputs/sample-release-v0.2.2

Exercise append-only SQLite storage:

    python3 -m kapi init-db --database kapi/.tmp/kapi.sqlite3

    python3 -m kapi ingest \
      --database kapi/.tmp/kapi.sqlite3 \
      --bundle kapi/fixtures/synthetic-hand-example-v1.json \
      --method kapi/config/methodology-v0.2.2.json

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

The same fixture breaches the pre-approved concentration cap. The calculation
therefore retains the diagnostic values but marks the release
withheld_concentration. This is intentional fail-closed behavior.

## Directory map

- CONTRACT.md — internal prototype contract.
- config/ — immutable methodology configuration.
- evidence/ — frozen local official-document metadata and candidate findings.
- profiles/ — frozen synthetic payloads, exact construction counts, and hashes.
- fixtures/ — deterministic normalized input data.
- schema/ — append-only SQLite schema and triggers.
- calculation.py — pure Decimal calculation engine.
- validation.py — schema and cross-reference gates.
- store.py — append-only SQLite ingestion/dump layer.
- exporter.py — CSV/JSON/release/manifest renderer.
- cli.py — validate, calculate, export, reproduce, and storage commands.
- tests/ — unit, regression, integration, and reproduction tests.
- docs/ — architecture, integration, operations, limitations, and controls.
- outputs/ — generated synthetic sample artifacts.

## Safety

The exporter labels every artifact as synthetic and not for publication.
Nothing in this directory publishes content or talks to a provider API.
