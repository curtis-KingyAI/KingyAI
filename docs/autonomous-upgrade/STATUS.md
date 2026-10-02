# Autonomous upgrade — 2026-10-01

Owner: Curtis Pyke. Status: PARTIALLY COMPLETE; production acceptance is not complete. See REPORT.md for retained evidence and remaining dependencies.

## Confirmed access and baseline

- Authorized GitHub repository: `curtis-KingyAI/KingyAI`, base commit recorded in `evidence/base-commit.txt`. Isolated branch: `codex/autonomous-upgrade-20261001`. Draft review: https://github.com/curtis-KingyAI/KingyAI/pull/23. Prepared implementation commit: `9d2cf42ef143163119a964bb79435dc7516c98c6`.
- Repository recovery bundle: `/workspace/kingy-upgrade/discovery/repository-before.bundle`. This is a code backup, not a production database backup.
- Live WordPress REST API and public pages are readable. Site timezone is already America/Vancouver.
- Runtime reports no configured secrets, outbound identities, or provider credentials. No authenticated WordPress, server, database, Beehiiv, or channel session was supplied.
- Connected Sites projects include Local Lab and a private CRM prototype; none is the WordPress property. Do not create a replacement site or deploy this task into those projects.
- The production property has plugins/MU plugins absent from this repository: canonical source engine, follow preferences, sponsor inquiry, workbench, release monitoring, source rate refresh. Full deployed-source backup and comparison are required before installation.
- Real traffic/conversion baselines: **baseline unavailable**. Public audience text is not an analytics baseline.

## Current findings

Public readbacks taken October 1, 2026 (UTC timestamps retained in evidence):

- `/brief/`: one-off Proof Lab pilot released September 20 Pacific; recurring delivery explicitly paused. Cause and provider restrictions cannot be determined from public output.
- `/ai-launches/coverage/`: latest displayed article July 30, 2026. The broader launch database and news surfaces are separate.
- Existing canonical product source worker reports a five-minute interval, 5,034 completed cycles, 9 pending source reviews and 1,613 overdue claims. Its successful fetches explicitly do not establish verification.
- Distribution source monitor: 110 sources, 66 healthy original sources, 20 failing, 24 review-required, and 433 pending changes. Reuse this registry; do not seed a second registry.
- Existing `/videos/` companions carry public commercial disclosures; database creation order is a bulk import and cannot establish newest YouTube publication order.
- Official YouTube channel URL linked by Kingy: `https://www.youtube.com/@kingy-ai`. Requests to YouTube channel, oEmbed and feeds were denied by the proxy. Channel ID, newest five and transcripts remain unconfirmed.
- Existing sponsor inquiry posts `kingy_sponsor_inquiry` to WordPress admin-post, states private durable storage and owner delivery to `info@kingy.ai`. Public markup lacks explicit requested format, budget/currency and usage-rights fields. A browser page load cannot verify delivery.

## Work queue

| Work | Status | Next operation |
|---|---|---|
| Architecture/protection | Local recovery verified; production blocked | Retrieve deployed source/config/database through authenticated host; restore production database in staging |
| Shared records and operations | Implemented and locally tested | Bind canonical source, release and provider contracts against deployed systems |
| Video companions | Prepared pipeline; evidence dependency pending | Obtain permitted official feed/channel identity/transcripts; reconcile newest five and retained artifacts |
| Product-commercial journey | Complete local journey verified | Place on existing Make This after compatible staging and release checks |
| Source coverage and Brief | Frozen editions, independent outbox recovery and gates tested; first edition drafted | Resolve deployed source failures and paused provider cause; test established opt-in/suppression/provider paths |
| My AI Stack | Device and six native-account journey groups verified locally | Validate deployed authentication/follow flow, account journeys and digest integration |
| Sponsor journey | Additive fields/copy prepared | Integrate existing handler; verify durable owner test inquiry and inbox/CRM delivery |
| Release and schedules | Templates prepared; none installed | Existing hosting/scheduler administration and production rollback checkpoint required |

## Continuation checkpoint

Version 0.2.0 requires schema 2. A local database export/import checkpoint preceded the additive outbox recovery migration. The 68 foundation checks, 43 recovery checks, 5 Node tests, four existing browser journey groups and six native-auth account groups passed. Production access remains unconfigured. Adapter code defects pause only the affected email stream; uncertain sends remain held. See REPORT.md and the new recovery/native-account evidence.

## Decisions

1. Extend the existing WordPress property. Add no accounts, hosting projects, duplicate catalogs, source registries, mailing lists, or authentication systems.
2. New runtime starts disabled. Activation does not create tables, pages, schedules, or subscribers. Explicit additive migration runs only after a verified backup/restore; enable individual features afterward.
3. Use existing `kingy_ai_tool` / `kingy_ai_model` post IDs as stable product identities; preserve companion publication snapshots.
4. Reuse Kingy's existing cost calculation code, captured from the public authorized property with its fingerprint. Structured prompts and outlines are templates; no remote generation is performed.
5. Provider/registry/follow adapters must resolve existing systems. Missing adapters produce blocked job evidence, never a successful empty result or claimed delivery.
6. Never advance a general product-verification date from one price observation. Price evidence has its own observation and verification timestamps.

## Resume instruction

Read this file, `REPORT.md`, `ARCHITECTURE.md` and `OPERATIONS.md`. Check branch, durable job/outbox records and existing deployments before retrying. Run only prepared independent checks until authenticated access exists. Do not resume recurring emails without provider checks and applicable opt-in/suppression evidence.
