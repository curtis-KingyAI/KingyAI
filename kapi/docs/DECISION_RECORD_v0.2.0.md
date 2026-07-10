# KAPI Methodology Decision Record v0.2.0

Status: proposed for local review only.
Scope: sole-operator internal research.
External spend: $0.

## Decision

Methodology v0.2.0 amends the synthetic prototype so construction sizing,
provider preflight request counts, and billed usage counts are separate
evidence classes.

The pinned `tiktoken 0.13.0` package metadata and hashed `o200k_base` asset are
used only for deterministic construction sizing. This does not verify model
mapping, provider request serialization, billing equivalence, or any KAPI value.

## Rules

1. Epoch ECI may be used only as a coarse model-level or best-across-settings
   capability screen.
2. Exact priced-configuration support requires separate official provider
   evidence before technical GO.
3. `gemini-2.5-pro` with thinking disabled is not a valid candidate here and is
   replaced by `gemini-2.5-flash` with `thinkingBudget=0`, subject to all other
   gates.
4. `gpt-5.4-mini-2026-03-17` with reasoning `none` and `claude-sonnet-4-6` with
   thinking disabled remain review candidates; Claude Sonnet 4.6 deprecation is
   recorded as a stability risk.
5. Every synthetic 075/100/125 payload must have exact counted content with
   zero tolerance unless a later amendment authorizes a bounded deterministic
   tolerance.
6. Construction counts cannot substitute for provider preflight or billed usage
   counts.
7. Technical GO, independent review, backup continuity, redundant storage,
   observed dry run, and Shadow Week 1 remain failed.

## Consequence

The prototype remains a technical NO-GO. It may validate deterministic local
construction payloads and synthetic arithmetic, but it must not publish a KAPI
value, start a dry run, start Shadow Week 1, call providers, spend money,
commit, push, deploy, or write to production without separate authorization.
