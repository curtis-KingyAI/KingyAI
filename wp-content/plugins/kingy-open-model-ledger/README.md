# Kingy Open Model Ledger

An additive WordPress plugin that upgrades the existing `kingy_ai_model` custom post type into a curated, evidence-led open-model decision ledger. It deliberately does not create a second model post type or competing canonical URLs.

## Surface ownership

- `/ai-models/`: KALI's exhaustive canonical model directory.
- `/open-models/`: the additive Open Model Ledger for curated and review-pending open-weight records.
- `/ai-models/{model}/`: canonical release record. The ledger template activates only after a record is explicitly marked `curated`; legacy records retain the existing template.
- `/ai-launches/open-weight-models/`: chronological release/change feed. The new event template fails closed until at least one ledger event exists and the existing feed page has a featured image.
- `/model-fit/`: calculator page. Activation creates it as a **draft** when absent; it is never auto-published.
- `/ai-hardware/...`: existing curated RAM, VRAM and buying guides; the calculator links to these rather than duplicating them.

## Model boundary

One record represents a publisher-recognized release family or materially changed checkpoint. Variants, quantizations, community conversions, runtimes and API offers remain child data on that record. They do not receive indexable pages.

## Openness vocabulary

Weight access and legal rights are independent. Public labels include:

- Open Source AI — Kingy assessment against OSAID 1.0
- Open weights — permissive terms
- Open weights — custom terms
- Open weights — commercial/use restricted
- Source available — weights unavailable or partial
- Insufficient evidence

The plugin never says “OSI-certified.”

## Structured data

Scalar fields cover announcement and weight-availability dates, access, rights, OSAID outcome, license, commercial use, total/active parameters, architecture, context, modalities, lifecycle, replacement, canonical repository/revision and verification.

Repeatable data covers:

- Official variants
- Exact artifacts, revisions, formats, quantization and byte sizes
- Component-level license assessments
- Runtime support with version, backend and evidence status
- Estimated and observed hardware fit
- Provider-specific API offers and effective-dated prices
- Field-level evidence with source, locator, method, confidence and verification date
- Append-only change events

## Safety behavior

- Existing model records are not migrated, published or silently relabeled on activation.
- Legacy open-weight records remain visible in the directory as “Ledger review pending.”
- The single-record ledger template applies only to explicitly curated records.
- Curated record, feed, and calculator templates render the selected featured image exactly once; the editor must still inspect the final pixels and body references for duplication before publication.
- `/model-fit/` is created as a draft if absent.
- The event-feed override requires both reviewed events and a featured image on the feed page.
- Changes made through the ledger meta box append a before/after event rather than erasing history.

## Verification

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/frontend-smoke.php
node --check assets/model-fit.js
```
