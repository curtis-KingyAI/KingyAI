# Architecture and integration contract

## Ownership

The existing KALI `kingy_ai_tool` and `kingy_ai_model` posts remain canonical products. Stable references are `wp:kingy_ai_tool:123` and `wp:kingy_ai_model:456`. Videos keep `_kingy_youtube_video_id`; sources keep IDs from the existing source registry. A new graph database, independent source list, account system, catalog, newsletter list or sponsor endpoint is not introduced.

The extension owns only material-change projections, project payloads and its assigned operational state. `kau_changes` contains immutable event keys, product identity, source ID/URL/fingerprint/quotation, kind, publication/observation/verification times and optional price. All event timestamps are UTC ISO values. General verification dates are never refreshed by a fetch or a price-only observation. Companion snapshots remain historical; live pricing and material changes read the same verified projection used by budgets and followed feeds.

`kau_jobs` records slot/attempt/outcome, `kau_locks` stores expiring random ownership, `kau_exceptions` stores sanitized actionable reasons, `kau_projects` stores private owner-bound projects/stacks, and `kau_outbox` stores recipient references, policy keys, event keys, expiration and provider status. No raw email address or reference image is needed in these tables. Current schema version: 1; MySQL/MariaDB InnoDB, additive dbDelta migration. Activation performs no migration or publication.

## Required deployed adapters

Implement these in the maintained production integration/MU plugin after its actual source is retrieved. Missing callbacks return blocked outcomes. The staging harness supplies disposable fake adapters only inside the test process.

| Filter/action | Required behavior |
|---|---|
| `kau_collect_existing_sources($default, $stream, $context)` | Reuse registry fetch permissions, identities, deduplication and last-14-day observation queue; return `checked`, `failed`, `verified_changes`; never equate fetch success with supported facts |
| `kau_verify_canonical_change($default, $change)` | Return explicit `supported:true`, `official:true`, matching `source_id`, `source_sha256`, `product_id` only after retained quote, source identity, dates, availability, price unit and materiality verification |
| `kau_verified_product_change($change)` | Optional invalidation/notification of maintained surfaces; never create another event or rewrite filming snapshots |
| `kau_product_monitoring_coverage($default, $identity)` | Actual coverage state from existing engine; unsupported products remain unknown |
| `kau_prepare_video($default, $feedVideo, $context)` | Retained authorized transcript/test artifacts, verified duration/chapters/prompts/verdict/disclosure/asset rights, existing related products; feed identities/dates must match exactly |
| `kau_companion_evidence_check` / `kau_companion_technical_check` | Strict boolean true backed by actual evidence and rendered/download/metadata/canonical QA; never approve merely because schema parses |
| `kau_brief_evidence_extras($extras, $context)` | Actual test date and receipt, usable existing workflow, relevant stack material; omit unsupported sections |
| `kau_eligible_recipients($default, $stream, $context)` | Existing verified recipient references and followed product IDs, applicable recorded consent/frequency, suppressions and deletion; no new subscription or opt-in inferred from saved stack |
| `kau_recipient_policy($default, $outboxRow)` | Recheck explicit `verified_opt_in`, `suppressed:false`, matching `stream`, `frequency_eligible`, `events_eligible` immediately before send |
| `kau_email_transport($default, $row, $policy)` | Established provider only; documented budget and account restrictions; stable `delivery_key` as idempotency key; return `accepted` plus provider ID separately from confirmed `delivered` |
| `kau_email_reconcile($default, $row)` | Query established provider using retained identity/idempotency key. Return accepted/delivered, uncertain, or definitely_not_sent; uncertainty never triggers another send |
| `kau_mail_legal_address($default)` | Maintained sender postal identity and required provider/legal footer; placeholder is not suitable for real delivery |
| `kau_existing_follow_preferences($default, $nativeUserId, $body)` | Reuse native authentication and established verified preference flow; record explicit daily digest consent or opt-out, ownership, token expiration and suppression. Return recorded:true only after durable success |
| `kau_existing_inquiry_is_valid($default, $inquiryId)` | Confirm established sponsor inquiry post identity after its consent, validation and spam gates before enrichment |
| `kau_existing_system_health($default, $context)` | Check existing registry/queue/provider/release health, distinct source failure/zero result, stale records and overdue work; attach independent host alerting where available |

For recipient lists over 500, implement provider-supported bulk/segment delivery or durable pagination in the maintained integration before replacing the guarded job handler. The supplied default deliberately holds before creating a partially delivered edition. Verified provider callbacks/reconciliation must update accepted deliveries to delivered separately; acceptance alone is not a delivery claim. Do not log recipient addresses or provider response bodies.

## Features and release controls

`kau_feature_flags`: explicit booleans for workflow, stack, changes, sponsor, jobs, email. Every feature requires schema version 1. Absent options disable all new surfaces. `kau_job_bindings` maps each job to `extension` only after ownership reconciliation; equivalent existing jobs remain owned by the existing system. `kau_verified_channel` requires official `id` and retained `evidence_reference`. `kau_email_checks[stream]` requires provider_sandbox, rendering, archive_links, preferences, suppression, unsubscribe, reconciliation and account_restrictions to be strictly true. Record the corresponding real evidence outside these booleans; never set them by copying test fixtures.

Existing KALI publication/snapshot/indexing gates are preserved. Companion failures restore the previously working post and all post metadata, including retained relationship history. New unsuccessful drafts remain held. Product facts, subscriptions, authentication, analytics and URL ownership stay with existing systems.

## Workflow and device storage

No compulsory account. The workflow uses sessionStorage for tab refresh; optional device save uses localStorage with an explicit device boundary. JSON import validates version, size, identities, URLs, shot/prompt relationships and unsafe properties. Replacement keeps a previous device backup. The production pack is a Markdown download including every step, evidence dates, sources, assumptions and revision status. Incomplete/stale packs are blocked. Unknown rates remain unknown; currencies are never silently converted.

Camera planning reuses the public maintained `KingyVideoProject` schema and `kingyVideoProject.v2` / `kingyVideoHandoff.v2` contract. Existing video workspace is backed up before handoff. Reimport retains stable shot IDs and marks dependent prompt/budget outputs stale. Reference images accept PNG/JPEG/WebP, 5MB maximum, decoded type and dimension/pixel checks; they remain in browser memory, are deleted/revoked, and are excluded from account saves and analytics.

Native WordPress cookie/REST nonce auth is used only for optional cross-device project/stack persistence. Every server read/write/delete is owner-bound; projects use revisions and locks. Missing established follow integration returns 503 and does not fake preference success. The production authentication/preferences system still needs compatibility validation.

## Reused author-owned assets

Captured from Kingy public tool routes on October 1, 2026: the cost module and native video project model, copied unchanged into `assets/vendor/`. Fingerprints and public retrieval URLs are in `evidence/vendor-provenance.json`. Source is reused as authorized Kingy property logic; do not replace the maintained production modules blindly with these snapshots. No inference API or paid generation is called.

## Measurement definitions

The browser emits `kingy-upgrade-measurement` with a known event name only when DNT/GPC permit. Connect the existing collector after consent/analytics compatibility checks. Prepared names are discoverable in `assets/app.mjs`; they measure workflow completion/export, save actions and device stack changes. Attribution and retention need the established analytics system; no private content goes in analytics.

| Required metric | Definition and authoritative source |
|---|---|
| Companion visits from YouTube | Existing analytics sessions to canonical companion URLs with permitted YouTube referral/UTM attribution |
| Workflow completions / exports | Completed current dependency graph / successful production-pack download; separate JSON backup events |
| Returning visitors | Established privacy-compliant analytics definition, unchanged identifiers |
| Coverage freshness | Verified-at age and actual monitored products/sources; separate fetch attempt, failed source and held observation |
| Newsletter / digest clicks | Established provider attributed clicks with its privacy/filtering semantics; accepted is not delivered |
| Saved stacks | Explicit local/account save actions; anonymous devices are not inferred unique people |
| Qualified sponsor inquiries | Established handler durable valid non-spam sponsor inquiry, optional later qualification status; analytics records count/status only |

All pre-release traffic/conversion baselines are unavailable. No fabricated baseline or claimed production collector installation.
