# Limitations and unresolved decisions

## Data

- All calculated example prices, identities, capability records, and source
  artifacts are synthetic.
- No official current or historical KAPI series was created.
- No current price was projected backward.
- The existing KALI free-text records were not imported as price history.

## Canonical payloads and tokenizers

- Payload files are deterministic synthetic fixtures.
- Their o200k_base counts are design targets, not verified counts, because the
  o200k tokenizer package/data is not present in the approved local runtime and
  no dependency was installed.
- Synthetic endpoint billing-token counts are explicit test data. They are not
  measurements of a real tokenizer or invoice.
- A real endpoint is ineligible until its exact billing tokenizer/count method
  and frozen-payload counts are reproducible.

## Capability and task quality

- ECI 130 is encoded as a general capability gate, not proof of success on any
  individual profile.
- No model was run; no accuracy, reliability, latency, retry, reasoning-token,
  or billed-usage result was measured.
- Dynamic/hidden reasoning is excluded.
- The prototype cannot support “cost per successful task.”

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
- Approve/obtain exact tokenizer assets and licensing.
- Approve any real source-retention/storage design.
- Complete rights, legal, trademark, and independent methodology review.
- Confirm 12-month operating capacity.
- Provide separate authorization before shadow collection, spending,
  production work, deployment, or publication.
