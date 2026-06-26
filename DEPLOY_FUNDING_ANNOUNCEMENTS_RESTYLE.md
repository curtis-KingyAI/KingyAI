# Funding Announcements Restyle Deployment Note

Date: 2026-06-26
Page: https://kingy.ai/ai-launches/funding-announcements/

## Summary

Restyled the AI Funding Announcements page to match the AI Tools directory visual system and fixed the funding table so wide columns scroll inside the table wrapper instead of expanding the page.

## Source Changes

- Added a page-scoped Funding Announcements style block in `wp-content/plugins/kingy-ai-launch-intelligence/assets/css/launch-intelligence.css`.
- Updated the Launch Intelligence asset version suffix in `wp-content/plugins/kingy-ai-launch-intelligence/kingy-ai-launch-intelligence.php` to force a CSS cache refresh on deploy.
- Preserved existing funding content, source data, ordering, filters, and table sorting behavior.

## Live Page Note

The available WordPress REST credentials could update the page content but could not directly edit the live plugin/theme stylesheet. To verify the live rendered page immediately, the same scoped CSS was synced into the page's existing style block. The plugin stylesheet contains the source version for the next deployment.

## Verification

- Checked live rendering at 1440, 1024, 768, and 390 px viewport widths.
- Confirmed no page-level horizontal overflow at each width.
- Confirmed the funding table scrolls horizontally inside `.kingy-funding-table-wrap`.
- Confirmed the Source column is reachable after internal table scroll.
- Confirmed only one visible H1 remains; the duplicate theme page title is hidden.
- Confirmed 390 px mobile hero text fits inside the card.
- Confirmed page status remained `publish`.
- Confirmed featured media remained set: `916489`.
- Confirmed visible page text hash stayed unchanged during the final live update.

## Included Evidence

- `output/playwright/funding-announcements/funding-1440.png`
- `output/playwright/funding-announcements/funding-1024.png`
- `output/playwright/funding-announcements/funding-768.png`
- `output/playwright/funding-announcements/funding-390.png`
- `kingy-ai-launch-system/data/reports/2026-06-26T08-01-35-435Z-funding-announcements-mobile-heading-refine-apply.json`

## Deployment Steps

1. Deploy the updated plugin files.
2. Clear WordPress page cache and Elementor cache.
3. Reload the funding page and confirm the plugin stylesheet version includes `funding-style-sync-20260626`.
4. Spot-check desktop and mobile table scrolling after cache clear.
