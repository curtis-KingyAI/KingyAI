# Kingy AI Launch Intelligence

WordPress-first MVP for a structured AI launch database.

## What It Provides

- Custom post types: `AI Launch`, `AI Tool`, and `AI Company`
- Taxonomies: launch category, audience, tool attribute, launch type, including the founder, creator, marketer, developer, designer, sales, operator, research, and enterprise audiences from the Launch Intelligence brief
- Request-source helpers reject malformed scalar and array fields before unslashing, with a second post-unslash shape check on scalar, array, and remote-address paths
- Structured launch/tool fields for sources, scoring, pricing, verification, SEO planning, best-next-link routing, and internal partnership notes, with public REST metadata withheld from noindexed/incomplete profiles, invalid legacy URL and numeric values filtered out of public REST metadata, non-scalar stored meta skipped before public REST casting or relationship checks, and public REST relationship IDs limited to index-ready records of the expected type
- Traction and intake fields for Reddit/community signals, creator coverage interest, creator campaign interest, budget likelihood, and private notes
- Score Helper for suggested Kingy Launch, Demo, YouTube, SEO, and internal partner-fit scores, with scalar-only source meta presence checks, array-only score-helper request sources, and scalar-only admin apply requests/notices
- Automatic launch-to-tool linking so imports and founder submissions create/update permanent `/ai-tools/` profiles with linked-launch rollups, latest-launch dates, scalar-only relationship IDs, and scalar-only copied profile fields
- Automatic launch/tool-to-company linking so company profiles collect related tools, launch history, scalar-only relationship IDs, scalar-only copied company fields, and scalar-only creator-coverage signal values
- Derived attribute tagging for free plans, APIs, valid demo/source URLs, funding, GitHub/Product Hunt traction, high YouTube potential, beginner/creator/business/developer fit, traction signals, and coverage candidates, with scalar-only source values and scores before derived slugs are assigned
- Public search across launch titles, structured fields, taxonomy terms, launch types, related tool profiles, and related company profiles, with array-only public filter sources, bounded search/filter request values, valid-URL-aware demo filters, related profile matches, scalar-only public launch/tool/company card rendering, profile relationship links, schema references, and graph counts filled from the index-ready records used by noindex, REST, and schema safeguards
- Hub methodology section explaining source-backed records, signal weighting, creator usefulness, and why low-information records stay out of search indexes until improved
- Shortcodes for hub, search, launch grids, tool/company directories, founder submissions, YouTube-worthy launches, the Launch Visibility Score calculator, and the YouTube creator campaign ROI calculator with soft creator-campaign form/data attributes and browser calculation helpers, backwards-compatible legacy lead aliases, optional array-only lead request sources, scalar-only bounded lead capture, array-only server address sources for lead rate limits, array-only calculator query flag sources, scalar-only calculator query flags, explicit lead email and absolute http/https URL validation, front-end-only same-site public form redirects, and tracked submit/visibility/contact next steps
- AI Launch Scorecard shortcode and managed page for a 100-point launch-readiness rubric, with explicit methodology, non-guarantee language, source policy, creator disclosure notes, and privacy reminders for review requests
- Launch Visibility Score inputs cover category, stage, website, demo, pricing/free plan, Product Hunt, GitHub/Hugging Face, founder visibility, YouTube potential, audience/use-case clarity, comparison angle, and launch distribution budget clarity
- Launch Visibility Score next steps route founders toward full launch submission, creator coverage review, creator campaign review, creator campaign ROI estimation, and direct contact, with legacy interest links normalized to the public creator-campaign label
- Codex Prompt Builder, article toolkit, app builder comparison, SEO QA, website QA, security review, and WordPress Custom HTML safety checklist shortcodes for Build With AI Academy pages, including prompt presets, dropdown suggestions, generated output, resource links, readiness checks, comparison links, interactive scorecards, filters, reset, code smell helpers, and copy actions
- Admin setup and activation-free upgrade checks that create missing managed pages and nested parent pages, store managed shortcode content as WordPress shortcode blocks, repair package-managed pages on version checks, relocate or republish package-managed pages when a nested path changes, summarize MVP page readiness, render neutral setup action aliases with legacy form compatibility, use array-only setup request sources, scalar-only repair and completion-summary requests, and avoid overwriting custom `/ai-launches/` content
- Pending-review founder submissions with launch type, category, audience, array-only request sources and payload handling, scalar-only bounded field intake and success flags, declared select/taxonomy option validation, explicit email and absolute http/https URL validation, front-end-only same-site redirect fallbacks, funding announcement and press kit links copied into source links, admin email notification, honeypot, array-only server address sources and scalar-only remote-address rate limiting, queue moderation actions that stamp approved submissions with a review date, and post-submit next steps for visibility scoring, creator campaign ROI, optional client examples, and contact
- Editor tabs with profile-readiness checks, known-pricing and useful-link checks aligned with noindex safeguards, scalar-only readiness text/date/relationship checks for malformed legacy meta, array-only editor request sources, scalar-only nonce and array-only payload handling for editor meta saves, absolute http/https validation for structured URL fields and URL-based readiness gates, index-ready related-record checks, and dropdown relationship selectors for launch, tool, and company records before public profile review
- Search/click/CTA analytics table with weekly demand, zero-result, high-intent, normalized launch/category/audience filters, search/filter combination, founder-intake, visibility-score lead, creator campaign ROI lead, calculator, filter, filter-reset, creator-review CTA, optional client-examples CTA, scalar-only public CTA URL safeguards for site-relative or absolute http/https links, array-only AJAX request sources, scalar-only analytics labels/search text, scalar-only allowlisted AJAX click events with absolute http/https target URLs and array-only server address sources for scalar-only remote-address hashing, scalar-only admin analytics filter labels/URLs/emails/numeric values for malformed legacy rows, absolute http/https filtering before admin analytics URL rendering, clicked-object dashboards, and explicit surfaces for launch cards, directory cards, and profile relationship/source links
- Public launch no-results states route visitors toward submitting a missing launch, requesting a Launch Visibility Score, or browsing the full hub
- Hub tile analytics for clicked category/collection paths, with cleaner labels for submit and Launch Visibility Score CTAs
- Trending launch shortcode surfaces records with Product Hunt, GitHub, Hugging Face, X/social, Reddit, YouTube, or manual traction notes, while YouTube-worthy launches remain scored separately
- Admin command center for launches, tools, companies, submissions, analytics, and setup
- Public creator-coverage shortlist with filter chips for strong demos, clear use cases, creator/business/developer fit, founder submissions, funding, Product Hunt traction, and video demos
- Sortable admin list columns for launch dates, companies, scores, verification, tool demos, latest launches, outreach status, and company graph counts, with scalar-only text/date/score/relationship rendering for malformed legacy meta
- Maintenance tool to rebuild launch-to-tool/company links, refresh tool latest-launch pointers, derived attributes, analytics table, and optional suggested scores after imports or schema changes, with declared request-source selection, scalar-only relationship IDs, scalar-only score-apply requests, and bounded completion-result notices
- Editorial queues for graph integrity issues, founder submissions, verification backlog, full article candidates, YouTube candidates, internal partner-fit candidates, missing/invalid launch demos, tool profiles missing/invalid demos, and missing pricing, with scalar-only graph/cell/moderation filter values for malformed legacy meta, array-only founder-submission moderation request sources, scalar-only founder-submission moderation requests/notices, and validated moderation redirects
- CSV/JSON launch importer plus a generated matching blank import template and launch CSV export for re-importable spreadsheet backfills, with explicit upload error, readable-file, file-size, CSV/JSON extension checks, array-only import request sources, scalar-only import nonce, array-only upload handling, scalar-only import diagnostic rendering, array-only export request sources, declared export-type, and completion-summary requests, scalar-only relationship-title checks in launch/tool CSV exports, explicit non-scalar CSV cell fallback handling, and tool/company CSV exports for audits and profile review
- MVP seed checklist plus a generated 50-row draft starter CSV for testing the 50 launch / 25 tool / at least 10 category / 5 curated page / submission form / visibility calculator workflow safely, with starter rows spread across 20 category slugs, published shortcode-ready page checks for curated/intake/calculator surfaces, and checked CSV output-stream handling
- Import summaries that distinguish created, updated, skipped, and failed rows, with preflight warnings for missing/unsupported columns, launch-profile indexability readiness gaps including unknown pricing text and missing valid useful-link URL evidence, row-level absolute http/https URL failures, and row-level diagnostics for bad backfill data
- Launch Radar draft generator that turns date-based or hand-picked structured launch records into editable WordPress post drafts using the required field order from category through funding, press kit, verification/source checks, and best next link, uses array-only generator request sources, scalar-only generated draft values and relationship IDs, validates declared period/status/date/limit/launch-ID inputs, defaults published-only drafts to public-ready launch records, keeps all-status drafts for internal editorial review, filters generated draft links to absolute http/https URLs, chooses the first valid absolute http/https best-next, related, or profile fallback link, adds per-record launch/tool/alternatives/correction/submission links, and backfills empty or invalid launch article links to the generated draft
- Single launch/tool/company templates, launch/tool/company archive templates, citation-backed launch-as-event and tool-as-software JSON-LD with absolute http/https URL filtering, scalar-only schema text/date/relationship/score values, launch collection CollectionPage/ItemList schema for the hub and curated public launch pages, WebPage schema/meta for managed submission and calculator pages, legacy bad URL filtering before public profile link rendering and link-section visibility, scalar-only public profile text/date/relationship rendering, and shared scalar-only noindex/schema safeguards for incomplete launch, tool, and company profiles, including invalid public URLs, launch profiles missing pricing/unknown-pricing text, or missing useful related/source links
- Launch profiles include linked launch type, category, audience, daily/weekly context, hub filters, tool/company links, related article/course/review/alternatives/calculator content, source links, and correction/submission CTAs to support the internal launch graph
- Noindex/canonical safeguards for arbitrary filtered/search result URLs, with array-only filtered-query sources and array-safe filtered-query detection
- Public verification/source panels with key source checks, URL-bearing funding notes, press kits, and correction suggestion forms on launch, tool, and company profiles, including scalar-only source-link parsing, scalar-only verification freshness/status labels, array-only correction request sources, scalar-only correction IDs/success flags, absolute http/https source-link validation, explicit optional email validation, bounded correction notes, and rate-limited correction intake
- Recent correction suggestions dashboard with captured bounded note, optional validated contact email, absolute http/https-filtered record link, and submitted date for editorial follow-up

## Core Shortcodes

```text
[kingy_launch_hub]
[kingy_launch_search]
[kingy_launch_grid period="today"]
[kingy_launch_grid period="week"]
[kingy_launch_grid category="ai-agents"]
[kingy_launch_submit_form]
[kingy_launch_visibility_score]
[kingy_ai_launch_scorecard]
[kingy_creator_campaign_roi_calculator]
[kingy_sponsorship_roi_comparison_page angle="youtube-sponsorship-roi"]
[kingy_tool_directory]
[kingy_company_directory]
[kingy_trending_launches]
[kingy_youtube_worthy_launches]
[kingy_creator_coverage_launches]
[kingy_codex_prompt_builder]
[kingy_codex_prompt_article_tools]
[kingy_app_builder_comparison]
[kingy_ai_lead_magnet_guide]
[kingy_ai_landing_page_guide]
[kingy_safe_ai_agent_guide]
[kingy_vibe_coding_beginner_hub]
[kingy_replit_beginner_guide]
[kingy_microsoft_copilot_course]
[kingy_custom_html_safety_checklist]
[kingy_website_qa_checklist]
[kingy_seo_qa_checklist]
[kingy_security_review_checklist]
```

## Recommended Pages

Use a non-plain permalink structure, such as **Post name**, for the exact public MVP URL shape. Plain permalinks still load WordPress content through query URLs, but they will not expose launch profiles at `/ai-launch/{launch-slug}/` or the public hub pages at `/ai-launches/...`.

The `/ai-launches/...` namespace is reserved for curated public pages created by **Setup Pages**. Launch category taxonomy terms remain internal filters so term archives do not intercept pages like `/ai-launches/today/` or `/ai-launches/ai-agents/`.

Tool and company directories are handled by the public custom post type archives at `/ai-tools/` and `/ai-companies/`, with individual profiles under `/ai-tools/{tool-slug}/` and `/ai-companies/{company-slug}/`.

Keep public creator-coverage and launch-intelligence CTA language soft: use “AI companies and launches with strong creator coverage potential,” “creator campaign review,” and “creator campaign ROI” on visitor-facing surfaces, including academy examples and helper tools. Keep direct sponsorship wording to private admin notes, internal queues, stable import/export fields, and internal analytics identifiers.

- `/ai-launches/` with `[kingy_launch_hub]`
- `/ai-launches/today/` with `[kingy_launch_grid period="today"]`
- `/ai-launches/this-week/` with `[kingy_launch_grid period="week"]`
- `/ai-launches/ai-agents/` with `[kingy_launch_grid category="ai-agents"]`
- `/ai-launches/ai-video-tools/` with `[kingy_launch_grid category="ai-video-tools"]`
- `/ai-launches/ai-coding-tools/` with `[kingy_launch_grid category="ai-coding-tools"]`
- `/ai-launches/ai-image-tools/` with `[kingy_launch_grid category="ai-image-tools"]`
- `/ai-launches/open-weight-models/` with `[kingy_launch_grid category="open-weight-models"]` for AI open-weight model launches
- `/ai-launches/youtube-worthy-ai-tools/` with `[kingy_youtube_worthy_launches]`
- `/ai-launches/founder-submitted-ai-tools/` with `[kingy_launch_grid attribute="founder-submitted"]`
- `/ai-launches/funding-announcements/` with `[kingy_launch_grid attribute="funding-announced"]`
- `/ai-launches/creator-coverage-ai-launches/` with `[kingy_creator_coverage_launches]`
- `/ai-launches/submit/` with `[kingy_launch_submit_form]`
- `/ai-launches/launch-visibility-score/` with `[kingy_launch_visibility_score]`
- `/ai-launch-scorecard/` with `[kingy_ai_launch_scorecard]`
- `/ai-launches/creator-campaign-roi-calculator/` with `[kingy_creator_campaign_roi_calculator]`
- `/ai-sponsored-video-roi-calculator/` with `[kingy_creator_campaign_roi_calculator]`
- `/youtube-sponsorship-roi-calculator/` with `[kingy_sponsorship_roi_comparison_page angle="youtube-sponsorship-roi"]`
- `/influencer-marketing-cac-calculator/` with `[kingy_sponsorship_roi_comparison_page angle="influencer-marketing-cac"]`
- `/creator-sponsorship-payback-calculator/` with `[kingy_sponsorship_roi_comparison_page angle="creator-sponsorship-payback"]`
- `/ai-product-sponsorship-calculator/` with `[kingy_sponsorship_roi_comparison_page angle="ai-product-sponsorship"]`
- `/youtube-sponsorship-rate-vs-roi-calculator/` with `[kingy_sponsorship_roi_comparison_page angle="youtube-sponsorship-rate-vs-roi"]`
- `/ai/build-with-ai-academy/tools/codex-prompt-builder/` with `[kingy_codex_prompt_builder]`
- `/vibe-coding-for-beginners-ai-app-builder/` with `[kingy_vibe_coding_beginner_hub]`
- `/replit-for-beginners-ai-apps/` with `[kingy_replit_beginner_guide]`
- `/microsoft-copilot-course/` with `[kingy_microsoft_copilot_course]`
- `/ai/build-with-ai-academy/articles/lovable-vs-replit-vs-bolt-vs-bubble-vs-softr/` with `[kingy_app_builder_comparison]`
- `/ai/build-with-ai-academy/articles/how-to-build-a-lead-magnet-with-ai/` with `[kingy_ai_lead_magnet_guide]`
- `/ai/build-with-ai-academy/articles/how-to-build-a-landing-page-with-ai/` with `[kingy_ai_landing_page_guide]`
- `/ai/build-with-ai-academy/articles/how-to-build-an-ai-agent-safely/` with `[kingy_safe_ai_agent_guide]`
- `/10-wordpress-custom-html-safety-checklist/` with `[kingy_custom_html_safety_checklist]`
- `/12-website-qa-checklist/` with `[kingy_website_qa_checklist]`
- `/15-seo-qa-checklist/` with `[kingy_seo_qa_checklist]`
- `/17-security-review-checklist/` with `[kingy_security_review_checklist]`
- `/ai-tools/` is the built-in searchable tool archive powered by `[kingy_tool_directory]`
- `/ai-companies/` is the built-in searchable company archive powered by `[kingy_company_directory]`

## Import Columns

```text
product_name, launch_name, tool_name, company, company_profile, launch_date, launch_type, category, audience, official_url, what_launched, who_it_is_for, pricing, free_plan, api_available, open_source_or_open_weight, demo_url, product_hunt_url, github_url, huggingface_url, x_url, youtube_url, funding, press_kit_url, founder_team, reddit_signal, youtube_signal, traction_notes, kingy_launch_score, demo_quality_score, youtube_score, seo_score, sponsor_fit_score_internal, kingy_verdict, what_feels_promising, what_feels_unproven, related_article_url, related_course_url, related_review_url, related_alternatives_url, related_calculator_url, best_next_link_url, best_next_link_label, youtube_interest, creator_coverage_interest, sponsorship_interest, visibility_score_interest, budget_likelihood_internal, founder_notes, internal_notes, sources, seo_title, meta_description, target_search_query, featured_snippet_answer, status, verification_status, last_verified
```

`launch_name` is optional. If it is empty, imports create a readable event title from `product_name`, `launch_type`, and `launch_date`. Re-imports update the same event when product/date/type match, while separate launches for the same tool can coexist and still roll up to one tool profile.

Launch CSV exports preserve that split: `product_name` is the permanent product/tool identity and `launch_name` is the launch event title, so exported audit sheets can be re-imported without turning event titles into duplicate products.

Launch exports also include `company_profile` as read-only context for spreadsheet audits. Re-imports accept and ignore that column without warning; company relationships are rebuilt from the structured `company` field and synced tool/company links.

Tool and company CSV exports are audit sheets, not import templates. Use the Launch CSV export when you want a spreadsheet that can be edited and re-imported; use the tool/company exports to review profile coverage, plan enrichment, or make manual editor updates. Exported data cells that could be interpreted as spreadsheet formulas are prefixed as plain text.

JSON imports accept either an array of record objects or an object with a `records`, `launches`, `rows`, or `data` array. JSON object keys are normalized to the same supported import columns, arrays in category/audience/launch-type fields become comma-separated terms, URL objects use the first URL-like value, and other arrays are flattened into line-separated notes.

CSV and JSON imports also normalize common human-readable headers such as `Product Name`, `Official link`, `Demo video link`, `YouTube content score`, `creator campaign interest`, and `Press kit link` to the canonical supported columns.

## Launch Scoring Methodology

Kingy AI launch scores are readiness heuristics. They measure whether a launch is clear, source-backed, demonstrable, searchable, and useful for editorial or creator review. They do not measure product quality, product safety, retention, revenue, market share, Product Hunt rank, investor interest, buyer adoption, or guaranteed Kingy AI coverage.

The AI Launch Scorecard is the founder-facing rubric. It scores product clarity, audience clarity, demo quality, website/launch-page quality, pricing clarity, launch distribution readiness, founder/company visibility, traction signals, SEO/comparison potential, and creator coverage fit on a 100-point scale.

The Launch Visibility Score is a more tactical surface-readiness calculator for search and creator discovery. The admin Score Helper is an editorial convenience tool based mostly on filled fields and should be reviewed manually before publishing scores.

## Source, Privacy, And Disclosure Policy

Strong launch records should point to official pages, demos, public repositories, Product Hunt, Hugging Face, press kits, funding announcements, or other reviewable sources. Unsupported claims should stay draft, noindexed, or clearly labeled until checked.

Form submissions, correction notes, score details, URLs, and analytics events may be stored for editorial review, spam prevention, product improvement, and follow-up. Do not send secrets, private customer data, unreleased financials, or regulated personal data through public forms. The plugin stores analytics events with a salted IP hash and timestamp, but it does not currently enforce automatic analytics purging; set a retention policy before public launch.

Creator coverage and creator campaign reviews are planning signals only. Any paid, gifted, affiliate, or otherwise materially supported creator coverage should be disclosed clearly in the published content, creator brief, and campaign tracking.

## Index Readiness

Launch profiles are intended to stay noindexed until they have an official URL, launch date, clear launch description, audience, known pricing, editorial verdict, recent verification date, at least one useful source or related link, and a launch category.

Tool profiles are intended to stay noindexed until they have an official URL, clear product description, known pricing, public launch history, last verification date, and category.

Company profiles are intended to stay noindexed until they have an official URL, summary, public launch/tool graph links, last verification date, and category.

AI model profiles are intended to stay noindexed until they have a provider, model family where known, modality, release/status signal, API/web/local availability notes, open-weight/open-source status, pricing or access notes, hardware requirements where relevant, official source links, benchmark caveats, verification status, recent last-verified date, and at least one public source link. Rumored, stale, unsupported, or thin model records should stay draft or noindexed until editorial review can verify the claims.

## Admin Workflow

1. Replace the existing code files in place when updating an existing site; the package runs one-time upgrade checks on load, so the update is just a file replacement.
2. Use **Setup Pages** to create or repair the public MVP pages if you want to run the page check manually. Missing parent pages for nested managed URLs are created automatically; existing custom pages are left untouched, and package-managed pages can be moved to their expected nested path.
3. Confirm **Setup Pages > Public URL Readiness** shows pretty permalinks as active before promoting `/ai-launch/` or `/ai-launches/` links.
4. Use **Import CSV/JSON** to backfill Launch Radar records from the spreadsheet template.
5. Use the **Starter Seed Pack** on the import page when you want a safe draft dataset for testing 50 launch records, 25 tool profiles, at least 10 categories, 5 curated pages, the submission form, and the Launch Visibility Score calculator before importing verified rows.
6. Review the import summary for created, updated, skipped, and failed rows, then fix any preflight column warnings or row-level diagnostics before re-importing.
7. Use **Maintenance** after large imports or field changes to rebuild tool/company links, refresh tool latest-launch pointers, derived attributes, and optionally suggested scores.
8. Use **Submissions** to publish strong founder-submitted launches, move uncertain ones to draft research, or mark not-fit records out of the active review queue. Use **Editorial Queues** for graph integrity issues, verification backlog, stale records older than 30 days, full article candidates, YouTube candidates, internal partner-fit candidates, missing launch demos, and tool profiles missing pricing or demos.
9. Use the launch editor **Score Helper** to apply suggested scores, then adjust the editorial judgment fields manually when needed.
10. Use **Article Draft Generator** to turn selected daily or weekly launch records into editable Launch Radar post drafts.
11. Use **Search Analytics** to turn zero-result, high-intent, visibility-score lead, creator campaign ROI lead, calculator, and correction signals into new category pages, tool profiles, articles, and outreach ideas.
12. Use **Settings** to set the Contact CTA URL and optional Client Examples CTA URL used by Launch Visibility Score and submission success next steps.
13. Use **Export Launch CSV** when you want a re-importable spreadsheet for launch backfills. Use **Export Tool CSV** and **Export Company CSV** as audit sheets for reviewing profile coverage and planning manual enrichment.

## Verification

Run the lightweight plugin check before packaging a new ZIP:

```sh
sh tools/verify-plugin.sh
```

The check runs PHP syntax linting across the plugin and scans for duplicate function declarations inside the plugin source.

## Maintainability Notes

The highest-priority files to split later are `includes/shortcodes.php`, `assets/js/launch-filters.js`, `assets/css/launch-intelligence.css`, and `includes/admin-importer.php`. Keep this pass scoped to trust, scoring, SEO safety, and verification before large refactors.

Do not fabricate launch records for demos. Use the generated starter seed only as draft test data and replace starter rows with verified launch records before public indexing.

## Include-Based Loading

For sites that want a pure include-based path, load the package from existing site code:

```php
require_once WP_CONTENT_DIR . '/plugins/kingy-ai-launch-intelligence/bootstrap.php';
```

The bootstrap defines the package as embedded code, skips activation/deactivation hooks, and lets normal one-time upgrade checks create or update terms, analytics tables, rewrite rules, and managed page checks.

Company directory cards use a lighter public-card readiness check than individual company profile indexing, so sourced company records can appear in the directory while thin company profiles still receive noindex safeguards.
