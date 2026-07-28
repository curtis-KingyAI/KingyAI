# Verification report

Verification date: 2026-07-13
Environment: existing local Python 3.14 standard library and SQLite
External spend: $0

## Automated suite

Command:

    python3 -m unittest discover -s kapi/tests -v

Result:

- 106 tests run.
- 106 passed.
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

Ten governance tests directly exercise identity/role separation, exact
methodology/commit/review-manifest binding, append-only reviewer key rotation and
revocation, latest-state eligibility, complete evidence attribution, expired
reviews, routine and exact-release code parity, the hard-failed verifier gate,
impossible unreviewed-to-operator/publication transitions, monotonic event time,
and late-insert rejection across every governed child set. Fourteen exporter
tests include stable content-derived release identity, a pre-render frozen-input
claim scan, and recursive rejection of forged governance fields in headline and
sensitivity weeks. Twenty-one validation tests include a closed nested-object
schema plus recursive normalized claim-key, assertion-prose, same-record
cross-depth, and subject-bearing list rejection for aliases, bare reviewer-like
metadata, Unicode/confusable spelling, and go-live synonyms while retaining
neutral evidence prose. The lifecycle test
also proves an exact empty caller-diagnostic schema, generic `note`/`message`
carriers, nested arrays, case and punctuation variants, Unicode normalization
and confusables, and controlled base-week states. Actual checker reports,
fabricated plausible/implausible pass records, wrapped/alias objects, arbitrary
types, and mixed-script/unknown secondary identifiers all fail before any row is
written because policy v1.0.0 accepts absence only.

The sixth Week 0 test exercises the database boundary directly. Raw calculation
inserts with and without actor context reject malformed/noncanonical JSON,
aliases/extras, false review/governance/publication/secondary fields,
status/disposition mismatches, and invalid release/base states with zero rows.
A mismatched binding disposition is also rejected. A connection missing the
registered validator cannot insert a calculation or bind a release. A migrated
schema-v1 claim-bearing row remains byte-unchanged and readable, receives no
inferred backfill, and is rejected by both raw and normal initial governance-
binding paths with zero binding/transition rows.

The earlier 83-test portability suite passed from a copied checkout with no `.git` directory,
`KAPI_O200K_ASSET_PATH` removed, a separate empty home directory, and no
complete tokenizer asset in the checkout.

## Input validation

The forward-governance methodology v0.3.0 and frozen synthetic bundle validate
with:

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

- Governance policy v1.0.0:
  `d3321a0c12a0eac8bfa04e87ed1ffc73cee3814caecf68faebe97be79d5267c4`
- Methodology v0.3.0:
  `85668be220af2c724ae8c1cd68cf53eeec6d547259c066acb28c8a8185a97e04`
- Bounded forward fixture v0.3.0:
  `d4a6fb3a0f51da7468ea684004ad7a84b8b2159e3653b62e677abc2f36703053`

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
same frozen manifest from the designated retained asset.

The 11 source metadata rows in the local evidence record match the retained
official-source manifest exactly for source id, URL, retrieval timestamp, and
SHA-256. This proves provenance equality only; it is not provider runtime or
billing evidence.

## Synthetic arithmetic

Implementation-isolated arithmetic remains unchanged:

- base sum of six profile prices: `0.2476`;
- current sum of six profile prices: `0.1333`;
- base/current 60-profile basket: `2.4760` / `1.3330`;
- synthetic diagnostic index:
  `53.8368336025848142164781906300484652665589660743134087237480`.

The required concentration rule keeps the result at `withheld_concentration`.
Numeric values remain synthetic diagnostics only and are not a KAPI release.

## Release reproduction

The current-code governance sample is preserved separately at
`kapi/outputs/sample-release-governance-v1.0.0`.

Generated release ID:

    kapi-content-38fecaf601e28698

Manifest SHA-256:

    50ae855f3b360ed6ba600aececadf63fb377116287d43e88048b96f90202512f

Reproduce result:

- reproduced: true;
- checked release files: 7;
- mismatches: 0;
- calculation SHA-256:
  `15679e05cdccd9e6a0027ce626c4728d336daedfb51457fff13757128030ecd5`.

The prior `kapi/outputs/sample-release`, `sample-release-v0.2.1`, and
`sample-release-v0.2.2` vintages are retained unchanged. Reproduction was run
for each using its exact pinned code vintage and returned `reproduced=true`,
seven checked files, and zero mismatches. Their release IDs are respectively:

- `kapi-prototype-503f9b34cae83d13`;
- `kapi-prototype-1385968fb6caf0e0`; and
- `kapi-prototype-bacfc3ee6f5344c9`.

Running the current v0.3.0 code against each legacy directory correctly returns
`reproduced=false` with only `code_hash_mismatch` findings. This is intentional:
reproduction never claims that changed governance code recalculated historical
bytes. The frozen historical manifest hashes remain:

- `c2784936b6e092d9645cdf28289982a2ec0bc83f561b501ea663f4c4ea06d661`;
- `b55cd3e9bafbed058b67cd6dd9f380404636bf7a6772a00e1b5234238c1f4018`;
  and
- `924a003eda77401b896c34110c0ceee6d6e9608d952ca07134f4694c39e08953`.

## Boundary confirmation

- Provider/model/network calls: none.
- Credential, account, and billing checks: none.
- Provider preflight verification: not performed; the gate failed closed.
- Provider billed-usage verification: not performed; the gate failed closed.
- Paid API/service activity: none.
- Production/WordPress writes: none.
- Deployment/publication: none.
- Observed dry run: not performed.
- Shadow Week 1: not started.
- Git commit/push/PR: none.
- Technical status: NO-GO.

## Governance resolution verification

- Current publication-candidate label is exactly: `Governance status: Unreviewed draft.
  Automated validation completed for this artifact; no operator or external
  methodology review is complete.`
- The forward methodology records `adopted_policy_principles` and the scoped
  `construction_reference_count_status=verified_exact_local_reference_counts_only`;
  it does not use an unscoped `approved_principles` or `counts_verified=true`
  assertion. Historical v0.2.x bytes retain their original fields unchanged.
- Calculation disposition, governance assurance, and publication state are
  separate fields.
- Legacy free-text signoffs cannot authorize governance-v2 transitions.
- Stable identity evidence, registrar/appointer separation, disclosures,
  exact methodology/release hashes, strict UTC/commit validation, findings,
  unresolved issues, evidence IDs, and structural signature fields are tested.
- Reviewer qualification/key registration, supersession, and revocation form a
  single immutable event chain. Reviews bind the exact current event; signature
  claims and all future claim-bearing transitions recheck latest active state.
  Public attribution exposes the bound/latest events and any revocation plus all
  identity, appointment, disclosure, decision, signature, and attestation
  evidence without describing an untrusted local claim as verification.
- The local actor binding is explicitly not authentication. The initial event
  is `unreviewed`; there is no edge from that state to operator review, no
  publication transition, and the public API rejects `mark_published`.
- `trusted_verifier_gate` is constrained to `failed`, while stronger future
  edges require `passed`. Complete self-supplied review and signature-claim
  chains therefore remain non-authorizing.
- Methodology, snapshot, calculation, and release child memberships reject late
  inserts after their governed parent is fixed. Release binding recomputes the
  exact artifact-set digest.
- Both modeled publication-ready branches require release `code_commit` equality
  with the methodology gate `implementation_commit`. A routine release must
  explicitly bind the exact current methodology review; it never inherits an
  external-release review or an unbound methodology-review claim.
- Release IDs are derived from frozen data, exact methodology, and mathematical
  output; changing governance prose leaves the ID stable, while changing index
  math changes it.
- Export recursively validates every headline and sensitivity week, rejects
  nested review attribution/publication flags, recomputes the authoritative
  validation report and complete calculation, rejects caller-added alias
  fields, and unconditionally rejects operator, external-methodology, exact-
  release, and publication-ready claims.
- The v0.3.0 input validator uses exact object-key sets and a complete
  path-specific container/leaf type and scalar grammar. Every public string is
  an exact safe value, closed enum, hash, timestamp, reference, or narrow
  machine identifier; values are ASCII-only. Caller `expected_result` oracles
  and `generation` spend/provider metadata are forbidden, and the exact
  methodology binding is mandatory. Validation and export recursively
  normalize all bundle keys and strings. With no current claim-key allowlist
  entries, semantic reviewer,
  auditor, certification, approval, verification, signature, governance, and
  publication/readiness aliases fail closed at any depth; assertion prose and
  go-live synonyms fail under neutral carriers too. Same-record recursive and
  list-local aggregation reject exact-subject splits and high-confidence
  actor-plus-review/decision splits without combining unrelated records. Source
  media types are fixed to `application/json`. Four bounded decode rounds are
  followed by rejection of residual percent/HTML/literal-escape syntax. Closed
  grammar is the primary carrier control; semantic scans are defense in depth.
  The canonical object hash must equal the deterministic bounded forward
  fixture, so in-grammar ID/name/version swaps and hash-repaired embedded-source
  contradictions also fail. Dynamic or observed data needs a new reviewed
  vintage.
- Active calculation, rendering, export, and CLI release generation admit only
  the exact canonical v0.3.0 bundle/methodology pair. All three real v0.2.x
  methodologies paired with the forward bundle fail validation and direct
  calculation; coordinated rewrites of schema, dataset, and methodology
  markers also fail. Export creates neither a destination nor files. v0.2.x
  compatibility validation accepts only the exact retained bundle and exact
  committed methodology hashes, while historical releases are reproduced from
  their pinned old code checkouts.
- Manifest composition rerenders all seven pre-manifest files from the bound
  v0.3.0 inputs/calculation and requires exact filename and byte equality.
  Forged governance state, review label, release ID, latest document, history
  CSV, component CSV, or frozen-input bytes therefore fail before a manifest
  or destination directory is written.
- Lifecycle diagnostics accept only an exact empty caller object. Recursive
  normalized content checks and controlled base-week states prevent generic
  claim carriers. Every caller-supplied secondary report is rejected regardless
  of shape or apparent result; only absence produces `not_supplied`. The
  standalone checker is non-authorizing until a future trusted path recomputes
  from and hash-binds the exact full calculation.
- A registered exact canonical validator guards every new calculation row and
  separately revalidates the stored row before initial release binding.
  Missing callback registration, direct SQL, and nonconforming schema-v1 rows
  fail closed. Migration never rewrites or backfills historical diagnostics.
- The current v0.3.0 methodology and current release directory contain no use
  of the legacy assurance term. Remaining repository occurrences are confined
  to byte-immutable historical vintages/module names, compatibility validation,
  and adversarial regression tests.
