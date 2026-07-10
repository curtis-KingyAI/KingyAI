# KAPI Methodology Decision Record v0.2.2

Date: 2026-07-10
Status: implemented for local review only; not effective
Supersedes: v0.2.1

## Decision

Separate deterministic payload reproduction from complete tokenizer-asset
proof:

1. Portable reproduction uses the canonical repository-contained
   `o200k-construction-manifest-v1.json`. It contains exactly the 12 approved
   construction chunk byte strings and their ranks, plus the complete source
   asset SHA-256 and derivation metadata.
2. Complete source-asset proof remains a separate explicit operation. The
   operator must provide `--asset-path` or `KAPI_O200K_ASSET_PATH`; repository
   code has no workstation-specific default.
3. The complete `o200k_base` asset and `tiktoken` package code remain outside
   the repository. Neither is downloaded or installed by KAPI.
4. Portable reproduction and complete source proof retain the same nonclaims:
   they do not establish model-tokenizer mapping, provider request counts,
   billed usage counts, or billing equivalence.

## Rationale

Methodology v0.2.1 could reproduce payloads only on the original workstation
because its generator read a hard-coded retained-asset path. That made the full
standard-library test suite unsuitable for a fresh checkout or isolated CI
runner. The 36 payloads depend on only 12 approved one-token chunks. Freezing
that minimal derived proof makes byte reproduction portable without vendoring
the complete tokenizer asset or weakening the original source-asset proof.

## Verification Modes

Portable payload verification:

    env -u KAPI_O200K_ASSET_PATH \
      python3 kapi/fixtures/build_payloads.py --check-frozen-manifest

Complete retained-asset proof:

    python3 kapi/fixtures/build_payloads.py --verify-source-asset \
      --asset-path /explicit/local/path/to/o200k_base.tiktoken

The second mode verifies the full asset SHA-256, derives all 12 entries from
the asset bytes, and requires byte equality with the frozen canonical manifest.

## Compatibility

- Methodology v0.2.0 and v0.2.1 remain byte-for-byte unchanged.
- All 36 payload files and their hashes remain byte-for-byte unchanged.
- Candidate configuration and official-document evidence findings are
  unchanged from v0.2.1.
- Construction count tolerance remains zero.

## Readiness

This amendment resolves only local/CI portability. Provider preflight counts,
billed usage, independent review, backup continuity, redundant storage,
observed dry run, and Shadow Week 1 remain failed or unverified. Curtis
self-review is not independent. Technical status remains **NO-GO**.
