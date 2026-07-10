# KAPI v0.2.2 Endpoint and Gate Matrices

Status: local synthetic review only. No provider calls were made.

## Construction Evidence Matrix

| Evidence | Scope | Portable | Status |
|---|---:|---:|---|
| Frozen derived manifest | 12 approved chunk/rank pairs | Yes | Canonical and repository-contained |
| Complete `o200k_base` source asset | SHA-256 `446a9538...b1a2d` | No | External; explicit path required for full proof |
| Synthetic payloads | 36 files | Yes | Exact construction counts; zero tolerance |
| Endpoint/profile/size rows | 216 rows | Yes | Construction fixtures only |
| Provider preflight request counts | 0 verified | No | Unverified |
| Provider billed usage counts | 0 verified | No | Unverified |

The 12-entry manifest is not a tokenizer package, complete tokenizer asset,
model mapping, provider count, or billing-equivalence record.

## Candidate Endpoint Matrix

| Candidate | Priced configuration | Official documentation | Eligibility |
|---|---|---|---|
| `gpt-5.4-mini-2026-03-17` | reasoning `none` | Failed: exact dated id unverified | Blocked |
| `gemini-2.5-flash` | `thinkingBudget=0` | Configuration and pricing supported | Review candidate only |
| `claude-sonnet-4-6` | omit `thinking` parameter | Active/priced; thinking off by omission | Review candidate only |

These documentation findings are inherited unchanged from v0.2.1 and do not
satisfy provider preflight or billed usage requirements.

## Readiness Matrix

| Gate | Status |
|---|---|
| Portable frozen-manifest reproduction | Passed locally |
| Complete retained-asset proof | Passed locally against the exact approved asset |
| CI workflow | Not created or executed |
| Technical GO | Failed / NO-GO |
| Independent review | Failed; Curtis self-review is not independent |
| Backup continuity | Failed / not verified |
| Redundant storage | Failed / not verified |
| Observed dry run | Failed / not authorized / not performed |
| Shadow Week 1 | Failed / not authorized / not started |
| External spend | Passed at `$0` |
