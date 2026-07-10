# Verification report

Verification date: 2026-07-10
Environment: existing local Python 3.14 standard library and SQLite
External spend: $0

## Automated suite

Command:

    python3 -m unittest discover -s kapi/tests -v

Result:

- 83 tests run.
- 83 passed.
- 0 failures.
- 0 errors.

The suite includes focused v0.2.1 checks proving that the exact dated OpenAI
candidate remains blocked, Google documentation support does not clear runtime
gates, Anthropic thinking uses omission rather than an explicit disabled value,
the Sonnet lifecycle statement is active-not-deprecated, and the official
evidence record hash is frozen. Five v0.2.2 regressions additionally prove the
frozen 12-entry manifest, no-asset portable operation, explicit full-asset
failure behavior, prior-version preservation, and fail-closed methodology
validation.

The same 83-test suite passed from a copied checkout with no `.git` directory,
`KAPI_O200K_ASSET_PATH` removed, a separate empty home directory, and no
complete tokenizer asset in the checkout.

## Input validation

The local-review methodology v0.2.2 and frozen synthetic bundle validate with:

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

- Methodology v0.2.0, preserved byte-for-byte:
  `8f9442b9cd38acd46602446a9bbcc848a29fd079dfc63fefc0cb24125eaacd59`
- Methodology v0.2.1:
  `1cb3cdc12139dad6a6bbaefc31f5023323d1672ba4fba69c531312f5a8a275b0`
- Methodology v0.2.2:
  `f75219ff27d059b7cc417ba2b2dc3d4e280ccf8e7d2ab0a2b1a38085a99a8ba8`
- Frozen 12-entry construction manifest:
  `660cf9990ad347334442622758757023f6f1b9b463273b9cfe5768bd358a918e`
- Official-provider evidence record:
  `086d020a9aa40981c95ea2181655d7dcadaf9c1c682449d504641c56c34bdb91`
- Synthetic fixture:
  `6cba82133f26cf3da4642f60e0006682f8ee190517cac34e2f673be06bb9e8d7`
- `o200k_base` asset:
  `446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d`

The payload generator and fixture generator both reproduce their frozen files
byte-for-byte. All 36 payloads retain exact construction counts with zero
tolerance for the JSON `content` field. Portable reproduction passed without
the full tokenizer asset. A separate explicit full-asset proof derived the
same frozen manifest from the approved retained asset.

The 11 source metadata rows in the local evidence record match the retained
official-source manifest exactly for source id, URL, retrieval timestamp, and
SHA-256. This proves provenance equality only; it is not provider runtime or
billing evidence.

## Synthetic arithmetic

Independent arithmetic remains unchanged:

- base sum of six profile prices: `0.2476`;
- current sum of six profile prices: `0.1333`;
- base/current 60-profile basket: `2.4760` / `1.3330`;
- synthetic diagnostic index:
  `53.8368336025848142164781906300484652665589660743134087237480`.

The required concentration rule keeps the result at `withheld_concentration`.
Numeric values remain synthetic diagnostics only and are not a KAPI release.

## Release reproduction

The current-code sample is preserved separately at
`kapi/outputs/sample-release-v0.2.2`.

Generated release ID:

    kapi-prototype-bacfc3ee6f5344c9

Manifest SHA-256:

    924a003eda77401b896c34110c0ceee6d6e9608d952ca07134f4694c39e08953

Reproduce result:

- reproduced: true;
- checked release files: 7;
- mismatches: 0;
- calculation SHA-256:
  `a82d92de6987e74bf61d7e35564480678ca2058e3658c0411c6aedbc03f1665f`.

The prior `kapi/outputs/sample-release` and
`kapi/outputs/sample-release-v0.2.1` vintages are retained unchanged. Their
manifests pin earlier validation-code hashes; reproduce those historical
vintages only with their frozen code vintages.

## Boundary confirmation

- Provider/model/network calls: none.
- Credential, account, and billing checks: none.
- Provider preflight request counts verified: 0.
- Provider billed usage rows verified: 0.
- Paid API/service activity: none.
- Production/WordPress writes: none.
- Deployment/publication: none.
- Observed dry run: not performed.
- Shadow Week 1: not started.
- Git commit/push/PR: none.
- Technical status: NO-GO.
