# Verification report

Verification date: 2026-07-09  
Environment: existing local Python 3.14 standard library and SQLite  
External spend: $0

## Automated suite

Command:

    python3 -m unittest discover -s kapi/tests -v

Result:

- 68 tests run.
- 68 passed.
- 0 failures.
- 0 errors.

The suite covers every explicit requested scenario:

- unchanged prices;
- input-only and output-only price changes;
- provider/model entry and exit;
- rolling alias exclusion;
- missing and conflicting evidence;
- context-tier selection;
- provider/creator independence;
- tied median price setters;
- concentration warnings and withholding;
- observation supersession and correction lineage;
- 13-week base calculation;
- prospective chain-linking;
- Grade A versus B/C research evidence;
- payload hashes and tokenizer-count eligibility;
- 3x3 payload and editorial/ECI sensitivities;
- append-only database triggers and transaction rollback;
- database canonical round-trip;
- deterministic CSV/JSON export;
- detached-artifact synthetic/not-for-publication labeling;
- retained embedded-source content hashing and cross-record evidence gates;
- non-branching supersession-chain enforcement;
- price/correction cycle rejection and correction-envelope identity checks;
- aligned sensitivity schemas and payload-grid min/max reporting;
- incomplete-week CSV coverage and truthful CLI status reporting;
- non-default-weight reproduction;
- file, implementation, and full-manifest metadata tamper detection;
- rejection of unmanifested release files and unsafe manifest paths;
- byte-exact reproduction.

## Input validation

The committed methodology and bundle validate with:

- zero errors;
- zero warnings;
- six profiles and 60 fixed profile instances;
- 14 consecutive Friday cutoffs;
- four providers, creators, models, and endpoints;
- 60 synthetic source artifacts;
- four capability records;
- 216 endpoint/profile/size token-count records;
- 112 weekly price observations.

Frozen hashes:

- Methodology: 6d2d4b26c1a1c6413a974b361fed9ee700173f0b6f9a923e70b714826df288e8
- Fixture: f940b7ebc75064f9169e370626f8f655acaf6482923d9c05f4a83483c63d06e3

The offline fixture generator reproduces the committed fixture byte-for-byte.

## Calculation

The required equal-weight hand example reproduces:

- base representative-profile cost: 0.041266666666666666666666666666666666666666666666667;
- current representative-profile cost: 0.022216666666666666666666666666666666666666666666667;
- index: 53.836833602584814216478190630048465266558966074314;
- 60-profile base/current basket: 2.476 / 1.333;
- geometric index: 52.591475748249293315951762402309079230320951943563;
- frontier index: 50.
- non-headline payload-grid latest range: 52.7551020408163265… to
  53.8368336025848142… (1.0817315617684877… index points).

The approved concentration rule correctly sets release_status to
withheld_concentration. Numeric values remain diagnostics only.

## Append-only store

- Transactional fixture ingestion succeeds.
- Canonical dump equals the input bundle exactly.
- All 29 declared domain tables have UPDATE and DELETE blocking triggers.
- Direct price UPDATE/DELETE attempts fail.
- Duplicate IDs, duplicate imports, unlinked conflicting prices, and failed
  imports roll back without changing stored data.
- Branching supersession graphs with multiple active heads are rejected.
- Direct store ingestion independently rejects altered embedded source bytes;
  callers cannot bypass the source-content hash gate by skipping the CLI.
- Superseding observations and corrections are inserted as new linked rows.

## Release reproduction

Generated release ID:

    kapi-prototype-e64032f0e7fc1bd3

Manifest SHA-256:

    06504d978a92cd4889feb63a41f19a163114ee4e6565b8b4192c6c16aed7755c

Reproduce result:

- reproduced: true;
- checked release files: 7;
- mismatches: 0;
- calculation SHA-256:
  39c697a74a61da3864aa495aa581ab28ed4bdbf8aef3df03ecb1609687a35451.

Reproduction also regenerates the complete manifest and compares its canonical
bytes, so changes to release identity, top-level hashes, source lineage,
implementation inventory, notices, or spending metadata fail verification.

## Static and repository checks

- All KAPI Python modules compile.
- All JSON files parse.
- SQLite schema initializes with foreign keys enabled.
- Git diff whitespace check passes.
- The isolated branch has no commit and was not pushed.
- The original dirty worktree remains at its starting commit with its original
  unrelated changes intact.

## Boundary confirmation

- Paid API/service activity: none.
- Network/model activity for prototype generation: none.
- Production/WordPress writes: none.
- Deployment/publication: none.
- Shadow operation: not started.
- Git commit/push/PR: none.
