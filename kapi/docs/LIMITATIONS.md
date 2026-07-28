# Limitations and unresolved decisions

## Data

- All calculated example prices, identities, capability records, and source
  artifacts are synthetic.
- No official current or historical KAPI series was created.
- No current price was projected backward.
- The existing KALI free-text records were not imported as price history.

## Canonical payloads and tokenizers

- Payload files are deterministic synthetic fixtures.
- Their construction counts are exact local reference counts for the JSON
  `content` field under explicitly selected `o200k_base`, using the already
  acquired `tiktoken 0.13.0` package metadata and hashed asset only.
- Portable reproduction relies on a frozen derived manifest containing only
  the 12 pinned chunk byte strings and ranks. Complete source-asset proof is
  separate and requires an explicit local path; no source asset is vendored.
- The derived manifest proves only that those chunks and ranks match the exact
  hashed retained asset. It does not reproduce or replace the complete
  tokenizer vocabulary.
- The construction counts are not model-tokenizer mapping evidence, provider
  request serialization evidence, preflight count evidence, or billing usage
  evidence.
- Synthetic endpoint token-count rows are construction-count fixtures. All
  provider preflight and billed-usage rows remain unverified with zero provider
  calls.
- A real endpoint is ineligible until its exact priced configuration is backed
  by official provider evidence and its frozen-payload preflight/billed counts
  pass a separately implemented reproducibility check after authorization.

## Capability and task quality

- ECI 130 is encoded as a coarse model-level or best-across-settings capability
  screen, not proof of a priced configuration or success on any individual
  profile.
- No model was run; no accuracy, reliability, latency, retry, reasoning-token,
  or billed-usage result was measured.
- Dynamic/hidden reasoning is excluded.
- The prototype cannot support “cost per successful task.”

## Candidate documentation evidence

- The retained 2026-07-10 official sources do not verify the exact dated
  OpenAI id `gpt-5.4-mini-2026-03-17`; that candidate remains blocked.
- The retained sources support `gemini-2.5-flash` with `thinkingBudget=0` as a
  documented configuration, but not its provider preflight or billed usage.
- The retained sources support `claude-sonnet-4-6` with thinking off by omitting
  the `thinking` parameter. They do not establish an explicit disabled value.
- Claude Sonnet 4.6 was active and not deprecated at the snapshot date, with a
  tentative retirement date no sooner than 2027-02-17. This must be reverified
  before any later operational step.
- Official documentation cannot satisfy provider preflight request counts,
  billed usage, account access, runtime behavior, or any readiness gate.

## Synthetic hand example

- The required hand example reproduces the configured arithmetic diagnostics.
- Its price setters breach the concentration withhold threshold.
- The release must therefore remain withheld_concentration even though the
  diagnostic index is 53.8. A future releaseable fixture may be added without
  changing this required regression case.

## Statistical scope

- Equal weights are transparent reference weights, not usage or expenditure
  shares.
- There is no statistical sampling confidence interval.
- The 13-week base is synthetic and does not start a real shadow period.
- Research evidence grades B/C remain separate from grade-A official logic.

## Engineering

- SQLite is appropriate for this isolated prototype but is not a production
  storage decision.
- Embedded synthetic snapshot content is canonicalized and checked against its
  retained SHA-256. No real provider pages were archived into the prototype.
- The prototype has no trusted authentication adapter, cryptographic signature
  verifier, network collector, scheduler, hosted monitoring, signing key,
  WordPress adapter, or deployment pipeline. Its private ContextVar and SQLite
  callbacks are local test scaffolding, not an identity security boundary.
- Structural reviewer/signature records do not prove identity, independence,
  or signature validity. The current hard-failed trusted-verifier gate prevents
  them from producing external-review claims or publication readiness.
- Reviewer registrations, key/qualification supersessions, and revocations now
  form an immutable event chain, and review/signature/future-transition checks
  use the latest event at the relevant time. This closes stale-key application
  paths but remains an application-integrity control until a separately
  administered service authenticates and delivers those events.
- Public review attribution is evidence-complete when a status references a
  review, including identity/appointment, qualifications, bound/latest registry
  state and revocation, disclosures, scope, decision evidence, signatures, and
  verifier-attestation evidence. It does not convert an
  `untrusted_local_claim` into cryptographic verification.
- Caller calculation diagnostics are intentionally an exact empty object. New
  technical diagnostic fields require an explicit reviewed schema change;
  free-form notes are not persisted through the lifecycle path.
- Forward v0.3.0 dataset bundles use closed object-key/path sets and a complete
  path-specific container/leaf type and scalar grammar. They are ASCII-only and
  accept no arbitrary license/status prose; percent, HTML, and literal-codepoint
  decoding is bounded, with residual encoding syntax rejected. Recursive claim
  scans remain defense in depth. Supporting new prose, internationalized text,
  or broader IDs requires a reviewed schema vintage and confusable policy. This
  is a bounded carrier defense, not a substitute for a trusted identity,
  review, or publication-authority service.
- v0.3.0 accepts exactly one canonical deterministic synthetic forward fixture.
  This prevents in-grammar provenance permutations but is intentionally not a
  general ingestion contract. Dynamic or observed KAPI data needs a new
  reviewed schema vintage with explicit source-to-record bindings.
- Current calculation/render/export supports only that exact v0.3.0 pair.
  Historical v0.2.x validation is limited to exact retained fixture and
  methodology hashes, and current code will not mint a new v0.2.x artifact.
  Historical releases require their pinned historical checkout for byte-exact
  reproduction. This deliberately removes a coordinated marker-rewrite
  downgrade path at the cost of general legacy calculation support.
- Release manifests do not measure or prove generation spend or provider
  activity. They report `not_measured_not_evidenced`; the separate local action
  log is not an input oracle and cannot be promoted through bundle metadata.
- The exact stored policy-v1 diagnostic is guarded by a per-connection SQLite
  validator at calculation insert and release binding. A direct SQLite
  connection without that registered function cannot perform governed writes.
  This is application integrity, not protection from a database owner who can
  alter or remove schema objects.
- Schema-v1 calculations are never rewritten or backfilled during migration.
  Nonconforming historical diagnostics remain readable but are quarantined from
  policy-v1 governance binding.
- Policy v1.0.0 rejects every caller-supplied secondary recalculation report.
  The standalone checker remains useful for local comparison but is not bound
  lifecycle evidence. A future path must recompute internally from the exact
  full frozen calculation and hash-bind the result.
- SQLite constraints and registered hash callbacks provide application-level
  fail-closed integrity, not host security. A process or administrator that
  controls the database file, schema, executable code, or operating-system
  account can replace those controls. Production authorization therefore needs
  a separately administered identity/signature service and append-only audit
  storage outside the publishing process's control.
- Policy v1.0.0 cannot attest that an operator reviewed a release. Every current
  artifact must remain exactly `unreviewed` and `not_authorized` even when all
  automated calculations and local checks pass.

## Owner decisions before later phases

- Name accountable owner, operator, backup, external methodology reviewer, and
  a separately trusted identity/signature verifier.
- Resolve the blocked exact OpenAI configuration with exact official evidence
  or a separately approved candidate amendment.
- Obtain provider preflight request counts and billed usage counts after
  separate authorization.
- Approve any real source-retention/storage design.
- Complete rights, legal, trademark, and named external methodology review
  under a trusted verifier process.
- Confirm 12-month operating capacity.
- Provide separate authorization before shadow collection, spending,
  production work, deployment, or publication.
