# KAPI v0.2.0 Endpoint and Gate Matrices

Status: local synthetic review only. No provider calls were made.

## Candidate Endpoint Matrix

| Candidate | Priced configuration | Status |
|---|---|---|
| `gpt-5.4-mini-2026-03-17` | reasoning `none` | Review candidate; official priced-configuration evidence and billing counts unverified |
| `gemini-2.5-flash` | `thinkingBudget=0` | Review candidate replacing rejected `gemini-2.5-pro` thinking-disabled path |
| `claude-sonnet-4-6` | thinking disabled | Review candidate; deprecation recorded as stability risk |

## Construction Count Matrix

| Evidence class | Rows | Status | Provider calls |
|---|---:|---|---:|
| Synthetic payload construction counts | 36 | Exact local reference construction counts for JSON `content` field | 0 |
| Synthetic endpoint/profile/size rows | 216 | Construction-count fixtures only; not provider preflight or billing rows | 0 |
| Provider preflight request counts | 0 verified | Unverified | 0 |
| Provider billed usage counts | 0 verified | Unverified | 0 |

## Tokenizer Matrix

| Tokenizer asset | Package metadata | Allowed use | Explicit non-claims |
|---|---|---|---|
| `o200k_base`, SHA-256 `446a9538cb6c348e3516120d7c08b09f57c36495e2acfffe59a5bf8b0cfb1a2d` | `tiktoken 0.13.0` | Deterministic construction sizing only | No model mapping, request serialization, billing equivalence, or KAPI value |

## Gate B / Readiness Matrix

| Gate | Status |
|---|---|
| Technical GO | Failed / NO-GO |
| Independent review | Failed; self-review is not independent |
| Backup continuity | Failed / not verified |
| Redundant storage | Failed / not verified |
| Observed dry run | Failed / not authorized / not performed |
| Shadow Week 1 | Failed / not authorized / not started |
| External spend | Passed at `$0`, with no provider/model/network actions |
