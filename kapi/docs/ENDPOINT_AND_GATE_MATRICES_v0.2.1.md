# KAPI v0.2.1 Endpoint and Gate Matrices

Status: local synthetic review only. No provider calls were made.

## Candidate Endpoint Matrix

| Candidate | Priced configuration | Official documentation | Eligibility |
|---|---|---|---|
| `gpt-5.4-mini-2026-03-17` | reasoning `none` | Failed: exact dated id unverified | Blocked |
| `gemini-2.5-flash` | `thinkingBudget=0` | Configuration and pricing supported | Review candidate only |
| `claude-sonnet-4-6` | omit `thinking` parameter | Active/priced; thinking off by omission | Review candidate only |

The Google and Anthropic documentation results do not satisfy provider
preflight or billed usage requirements. The undated OpenAI family entry does
not substitute for the blocked dated candidate.

## Documentation Evidence Matrix

| Evidence | Rows or actions | Status |
|---|---:|---|
| Retained official source records | 11 | Frozen ids, URLs, timestamps, and SHA-256 values |
| Provider/model/API calls | 0 | Not performed |
| Credential or account checks | 0 | Not performed |
| Billing checks | 0 | Not performed |
| External spend | $0 | Passed |

## Count Evidence Matrix

| Evidence class | Rows | Status | Provider calls |
|---|---:|---|---:|
| Synthetic payload construction counts | 36 | Exact local reference construction counts for JSON `content` field | 0 |
| Synthetic endpoint/profile/size rows | 216 | Construction-count fixtures only | 0 |
| Provider preflight request counts | 0 verified | Unverified | 0 |
| Provider billed usage counts | 0 verified | Unverified | 0 |

## Gate B / Readiness Matrix

| Gate | Status |
|---|---|
| Technical GO | Failed / NO-GO |
| Independent review | Failed; self-review is not independent |
| Backup continuity | Failed / not verified |
| Redundant storage | Failed / not verified |
| Observed dry run | Failed / not authorized / not performed |
| Shadow Week 1 | Failed / not authorized / not started |
| External spend | Passed at `$0` |
