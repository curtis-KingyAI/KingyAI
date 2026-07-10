# KAPI Methodology Decision Record v0.2.1

Status: implemented for local review only; not effective.
Scope: sole-operator internal research.
External spend: $0.

## Decision

Methodology v0.2.1 reconciles the v0.2.0 review candidates with the retained
official-provider documentation snapshot collected on 2026-07-10. It preserves
v0.2.0 as an immutable construction vintage and does not alter any synthetic
payload, payload hash, workload weight, arithmetic rule, or readiness gate.

Official documentation is a separate evidence layer. It cannot substitute for
provider preflight request counts, billed usage, independent review, continuity
controls, an observed dry run, or Shadow Week 1 evidence.

## Candidate Decisions

1. `gpt-5.4-mini-2026-03-17`, reasoning `none`, remains blocked. The retained
   official sources did not contain the exact dated model id, and the undated
   family entry is not an automatic substitute.
2. `gemini-2.5-flash`, `thinkingBudget=0`, is supported by the retained official
   configuration and pricing documentation. It remains a review candidate only
   because provider preflight and billed usage are unverified.
3. `claude-sonnet-4-6` remains a review candidate with thinking off by omission
   of the `thinking` parameter. v0.2.1 does not claim support for an explicit
   disabled value.
4. Claude Sonnet 4.6 is recorded as active and not deprecated at the snapshot
   date. Its tentative retirement date is no sooner than 2027-02-17, which is a
   future reverification risk rather than a current deprecation.

## Evidence

The concise evidence record is:

`kapi/evidence/official-provider-configuration-evidence-2026-07-10.json`

Its frozen SHA-256 is:

`086d020a9aa40981c95ea2181655d7dcadaf9c1c682449d504641c56c34bdb91`

The record contains source ids, URLs, retrieval timestamps, and hashes for the
retained official documentation. It records zero provider calls, zero billing
checks, and zero external spend.

## Consequence

The prototype remains a technical NO-GO. It must not publish a KAPI value,
contact a provider, use credentials, check billing, spend money, commit, push,
deploy, publish, begin an observed dry run, or start Shadow Week 1 without
separate authorization.
