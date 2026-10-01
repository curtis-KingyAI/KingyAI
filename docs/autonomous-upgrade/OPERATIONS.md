# Release, recovery and scheduler handoff

**Prepared only. Do not interpret these instructions as an executed production release.**

## Release prerequisites

The new plugin is in `wp-content/plugins/kingy-autonomous-upgrade/`. Three KALI files are patched: companion metadata, companion disclosure template, and live pricing module. This repository is incomplete compared with production. First retrieve the affected deployed files and relevant MU/plugin source, hosting release configuration, database backup capability, provider integration and existing scheduler registrations through authorized administration. Rebase the three small patches against deployed source; do not sync the whole checkout over WordPress.

Capture a restorable database export, affected content/metadata, theme/MU/plugin files, relevant options and scheduler configuration, with secrets stored only in the existing secure backup system. Verify restore in isolated staging and record backup identifiers and checksums. The current local staging export/import rehearsal proves only local recovery. Test existing authentication, editorial URLs, sitemap, canonical metadata, native workbench, analytics and preferences on the restored deployed theme/plugins.

Implement `ARCHITECTURE.md` adapters against actual maintained functions. Establish the channel identity and evidence. Resolve existing source failures and dated-claim discrepancies; the public worker already reports overdue claims, so this is not a badge change. Determine why Brief delivery is paused from provider/worker records, then preserve documented restrictions and suppression state.

## Local validation commands

Disposable staging uses PHP 8.3 CLI/WordPress and MariaDB 11.4 containers, bound to `127.0.0.1:8081`. Staging credentials are separate, permission 600, outside the repository. Docker Hub anonymous quota was unavailable; official public ECR library mirrors were used. No production credentials were copied.

```sh
node --test wp-content/plugins/kingy-autonomous-upgrade/tests/workflow.test.mjs
python wp-content/plugins/kingy-autonomous-upgrade/tests/browser.py
```

Run `tests/staging.php` only inside WordPress with `WP_ENVIRONMENT_TYPE=local`. It deliberately creates synthetic fixtures and fake provider adapters; it throws outside disposable local environments. Do not run it on staging cloned from production subscriber data or on production.

```sh
wp eval-file wp-content/plugins/kingy-autonomous-upgrade/tests/staging.php
wp kingy-upgrade tick --test
wp kingy-upgrade status
```

The test tick does not publish or send, but writes test job and exception records. Existing production adapters must honor `context.test` and the deadline without remote paid generation. A completed local edition-preparation result is not provider validation.

## Guarded rollout

1. Checkpoint and disable new features. Deploy only the reconciled extension and affected patches through the verified existing hosting release mechanism. Activation is inert; preserve current WordPress and scheduler operation.
2. After the production backup has actually been restored in staging and verified, run `wp kingy-upgrade migrate --backup-restored`. This creates six additive tables. Record database evidence; the flag is an operator assertion, not an automated verification of a backup.
3. Keep jobs/email off. Enable workflow/stack/changes/sponsor one at a time with `kau_feature_flags`, and compare before/after public journeys. Place `[kingy_product_commercial]` on the existing Make This page, `[kingy_my_stack]` on the existing stack entry page, and `[kingy_verified_changes]` in the maintained coverage surface. Preserve all existing content and canonical URLs. Set `kau_stack_url`, `kau_workflow_url` and `kau_follow_url` to the actual established routes.
4. Insert `[kingy_sponsor_upgrade]` into the existing commercial entry page, preserving approved terms and the existing inquiry CTA. The copy draft is additive. Insert `kau_sponsor_detail_fields()` **inside** the maintained inquiry form. After its existing consent/validation/spam gates and successful storage, call `kau_validate_sponsor_details()` then `kau_store_sponsor_details()`; bind validity to the existing durable inquiry type. Do not add another mail-only handler. Send only an explicitly designated owner test inquiry, verify real private storage/confirmation/inbox receipt, invalid/spam rejection and editorial route separation.
5. Check mobile/desktop keyboard journeys, real product price propagation, anonymous and account persistence, ownership, deletion, sitemap/canonical consistency and credential leakage. Verify companion assets/disclosures/chapter links before publication. Existing KALI gates remain in place.
6. Validate the established provider sandbox or established owner test address: rendering, archive links, preferences, unsubscribe, suppression, opt-in policy, account restrictions and ambiguous-send reconciliation. Supply the maintained postal identity. Only then record evidence and set `kau_email_checks` and the email flag. A large list needs the established bulk/segment integration before enabling the default job. Recheck current first-edition facts and schedule; never replay the October 2 draft as a new edition after expiration.
7. Install only schedules with confirmed ownership. Run safe test modes and verify scheduler registration separately; then verify at least one actual bounded production execution of every assigned job. Record publication, provider acceptance and confirmed delivery separately. Attach independent outage alerting through an existing available monitor.

## Schedule template

`ops/kingy-upgrade.service` and `.timer` are server-trigger templates, **not installed units**. Replace their explicitly invalid path/user placeholders with verified existing host values. Prefer an equivalent supported existing server/provider scheduler. Do not install both. The minute trigger invokes `wp kingy-upgrade tick`; the worker computes Vancouver civil-time slots, stores UTC execution times, deduplicates repeated fall-back hours, records missed runs and refuses expired newsletters. DISABLE_WP_CRON or the existing host's trigger strategy must be reconciled rather than changed blindly.

| Job | America/Vancouver rule | Current project status |
|---|---|---|
| YouTube discovery | 00:00, 06:00, 12:00, 18:00 | Not installed; blocked official identity/access |
| Launch collection | 06:00, 12:00, 18:00 daily | Not installed; canonical adapter pending |
| Priority changes | 07:00 daily | Not installed; canonical adapter pending |
| Catalog freshness | Monday 06:30 | Not installed; reconcile existing equivalent |
| Kingy Brief | Friday 09:00 | Not installed; provider checks/consent pending |
| Stack digest | 08:00 daily, relevant new events only | Not installed; provider/follow integration pending |
| Health | Hourly | Not installed; reuse existing checker where equivalent |

The extension is assigned a job only when `kau_job_bindings[name]` is `extension`. Keep existing equivalent owners untouched. Default unassigned state performs no production jobs. No terminal process from this workspace is a scheduler.

Retries are three attempts with bounded exponential backoff; novel code defects fail the affected job for operator review. The overall tick deadline is 240 seconds and individual adapters must use bounded timeouts. Lock ownership prevents stale workers from unlocking a replacement. Missing adapters remain blocked. After repairing a blocked job, use a new safe test slot or explicitly inspect/reset only that held state; do not reset accepted/uncertain outbox rows.

## Rollback

On material regression, disable the affected `kau_feature_flags` first. Stop only this extension's assigned trigger if needed; preserve unrelated existing jobs. Never reset the production subscriber list, suppressions, opt-ins or provider delivery history. Keep outbox records for reconciliation; uncertain sends stay held.

Restore reconciled affected source files/content from the release checkpoint through the existing release mechanism; deactivate the extension if necessary. The additive tables can remain for recovery. Do not drop them or restore a full database over newer inquiries/preferences without a reviewed selective recovery plan. A failed individual companion update restores its previous post and metadata in code; a recorded rollback failure requires restoring the checkpoint before another attempt.

Re-run relevant production journeys and existing release health. Record rollback commit/deployment ID, affected flag, cause, test evidence and next operator action. Diagnose and repair before enabling again. There is no promise of unlimited unattended code repair.
