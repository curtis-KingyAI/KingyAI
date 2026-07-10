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
- The construction counts are not model-tokenizer mapping evidence, provider
  request serialization evidence, preflight count evidence, or billing usage
  evidence.
- Synthetic endpoint token-count rows are construction-count fixtures. All
  provider preflight and billed-usage rows remain unverified with zero provider
  calls.
- A real endpoint is ineligible until its exact priced configuration is backed
  by official provider evidence and its frozen-payload preflight/billed counts
  are independently reproducible after separate authorization.

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

- The required hand example reproduces the approved arithmetic diagnostics.
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
- The prototype has no authentication, network collector, scheduler, hosted
  monitoring, signing key, WordPress adapter, or deployment pipeline.

## Owner decisions before later phases

- Name accountable owner, operator, backup, and independent reviewer.
- Resolve the blocked exact OpenAI configuration with exact official evidence
  or a separately approved candidate amendment.
- Obtain provider preflight request counts and billed usage counts after
  separate authorization.
- Approve any real source-retention/storage design.
- Complete rights, legal, trademark, and independent methodology review.
- Confirm 12-month operating capacity.
- Provide separate authorization before shadow collection, spending,
  production work, deployment, or publication.
