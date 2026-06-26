<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_seo_qa_checklist', 'kingy_ali_shortcode_seo_qa_checklist');
add_filter('the_content', 'kingy_ali_maybe_replace_seo_qa_checklist', 20);
add_filter('wpseo_title', 'kingy_ali_seo_qa_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_seo_qa_seo_description');
add_filter('document_title_parts', 'kingy_ali_seo_qa_document_title');
add_action('wp_head', 'kingy_ali_seo_qa_schema');

function kingy_ali_is_seo_qa_page() {
    return kingy_ali_is_rendering_page_slug('15-seo-qa-checklist');
}

function kingy_ali_maybe_replace_seo_qa_checklist($content) {
    if (!kingy_ali_is_seo_qa_page()) {
        return $content;
    }

    return kingy_ali_shortcode_seo_qa_checklist();
}

function kingy_ali_seo_qa_seo_title($title) {
    if (!kingy_ali_is_seo_qa_page()) {
        return $title;
    }

    return __('SEO QA Checklist: Interactive Technical SEO Launch Tool', 'kingy-ai-launch-intelligence');
}

function kingy_ali_seo_qa_seo_description($description) {
    if (!kingy_ali_is_seo_qa_page()) {
        return $description;
    }

    return __('Run an interactive SEO QA checklist for indexability, canonicals, redirects, schema, Core Web Vitals, analytics, launch monitoring, and rollback.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_seo_qa_document_title($parts) {
    if (kingy_ali_is_seo_qa_page()) {
        $parts['title'] = __('SEO QA Checklist', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_seo_qa_phases() {
    return array(
        array(
            'title' => 'Pre-QA setup',
            'why' => 'Define scope, evidence, owners, and rollback before anyone starts changing production SEO settings.',
            'items' => array(
                array('task' => 'Confirm the exact URLs, templates, markets, and launch window being tested.', 'why' => 'SEO QA fails when teams test a sample page but ship a different template, locale, or production route.', 'verify' => 'Compare the QA URL list with the CMS, redirect map, sitemap draft, and deployment plan.', 'tools' => 'CMS export, crawl seed list, launch brief', 'evidence' => 'Every launch URL has a template, owner, target query or intent, and status.', 'severity' => 'Critical', 'owner' => 'Project lead', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Create an evidence log before fixes start.', 'why' => 'Screenshots, crawl exports, and timestamps make launch decisions auditable instead of opinion driven.', 'verify' => 'Open a shared sheet or document with columns for issue, URL, owner, severity, evidence, fix, and retest.', 'tools' => 'Google Sheets, Notion, Jira, Linear', 'evidence' => 'The log has a row for each critical check and a named approver.', 'severity' => 'High', 'owner' => 'QA owner', 'use_cases' => 'blog saas ecommerce migration local'),
            ),
        ),
        array(
            'title' => 'Crawl and indexability',
            'why' => 'Search engines must be able to fetch, render, and index the pages that deserve organic traffic.',
            'items' => array(
                array('task' => 'Verify important pages return 200 status codes and are not blocked from crawling.', 'why' => 'A perfect page is invisible if Googlebot receives a block, error, login wall, or broken response.', 'verify' => 'Crawl the URL list and inspect server responses for 200, 3xx chains, 4xx, 5xx, and blocked resources.', 'tools' => 'Screaming Frog, Sitebulb, curl, Google URL Inspection', 'evidence' => 'Indexable pages return 200 and render core content without blocked critical resources.', 'severity' => 'Critical', 'owner' => 'Developer', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Check noindex, X-Robots-Tag, login gates, and environment banners.', 'why' => 'Staging settings are one of the fastest ways to erase organic visibility after launch.', 'verify' => 'Inspect page source, HTTP headers, SEO plugin settings, and deployment environment variables.', 'tools' => 'View source, HTTP header checker, GSC URL Inspection', 'evidence' => 'Production pages intended for search have no noindex directive and no staging-only blockers.', 'severity' => 'Critical', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce migration local'),
            ),
        ),
        array(
            'title' => 'Robots, canonicals, and duplication',
            'why' => 'Robots rules and canonical tags decide which URLs compete, consolidate, or disappear.',
            'items' => array(
                array('task' => 'Review robots.txt for accidental disallows and stale staging rules.', 'why' => 'One broad disallow can keep an entire folder, product line, or relaunch out of search.', 'verify' => 'Open robots.txt, test representative URLs, and compare rules against the launch scope.', 'tools' => 'robots.txt, Search Console robots tester, crawler', 'evidence' => 'Important folders are crawlable and intentionally excluded folders are documented.', 'severity' => 'Critical', 'owner' => 'SEO owner', 'use_cases' => 'saas ecommerce migration local'),
                array('task' => 'Validate self-referencing canonicals and intentional cross-canonicals.', 'why' => 'Wrong canonicals can transfer signals to old, filtered, staging, or irrelevant pages.', 'verify' => 'Crawl canonical targets and inspect source for every important template.', 'tools' => 'Screaming Frog, Sitebulb, SEO browser extension', 'evidence' => 'Canonical URLs are absolute, indexable, 200-status, and match the intended preferred page.', 'severity' => 'Critical', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce migration local'),
            ),
        ),
        array(
            'title' => 'Sitemaps and URL hygiene',
            'why' => 'Sitemaps and URL rules help crawlers discover the clean version of what changed.',
            'items' => array(
                array('task' => 'Confirm XML sitemaps contain only canonical, indexable, production URLs.', 'why' => 'Sitemaps polluted with redirects, noindex pages, staging hosts, or parameters waste crawl attention.', 'verify' => 'Open each sitemap, crawl it, and compare counts with the approved URL inventory.', 'tools' => 'XML sitemap, Search Console, crawler', 'evidence' => 'Sitemap URLs are 200-status, canonical, indexable, and submitted after launch.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Check URL casing, trailing slashes, parameters, pagination, and faceted variants.', 'why' => 'Uncontrolled variants create duplicate pages and split internal link equity.', 'verify' => 'Test alternate URL forms and crawl common parameters, filters, and pagination routes.', 'tools' => 'Crawler, server redirects, analytics landing pages', 'evidence' => 'Variants redirect, canonicalize, or index according to a documented rule.', 'severity' => 'High', 'owner' => 'Developer', 'use_cases' => 'ecommerce migration saas'),
            ),
        ),
        array(
            'title' => 'Redirects and migrations',
            'why' => 'Redirects preserve rankings, links, and users when URLs move.',
            'items' => array(
                array('task' => 'Map every retired or changed URL to the closest relevant live destination.', 'why' => 'Bulk redirecting to the homepage wastes relevance and can turn a relaunch into a traffic drop.', 'verify' => 'Compare old crawl, backlinks, analytics landing pages, and the final redirect map.', 'tools' => 'GA4, Search Console, Ahrefs/Semrush, crawl exports', 'evidence' => 'High-traffic and linked old URLs have one-hop 301 redirects to equivalent pages.', 'severity' => 'Critical', 'owner' => 'SEO owner', 'use_cases' => 'migration ecommerce saas local'),
                array('task' => 'Test redirect chains, loops, HTTP-to-HTTPS, www/non-www, and locale paths.', 'why' => 'Chains slow crawlers and users; loops or wrong locales break discovery outright.', 'verify' => 'Crawl old URLs and inspect final destinations, status codes, and hop counts.', 'tools' => 'Screaming Frog, httpstatus.io, curl', 'evidence' => 'Critical redirects resolve in one hop to the correct 200-status canonical URL.', 'severity' => 'Critical', 'owner' => 'Developer', 'use_cases' => 'migration ecommerce local'),
            ),
        ),
        array(
            'title' => 'Metadata and SERP appearance',
            'why' => 'Titles, descriptions, headings, and snippets shape relevance and clicks.',
            'items' => array(
                array('task' => 'Check title tags, meta descriptions, H1s, and open graph fields on key pages.', 'why' => 'Duplicate, truncated, or generic metadata makes pages look unfinished and weakens query alignment.', 'verify' => 'Review source and crawl exports for missing, duplicate, overly long, or mismatched fields.', 'tools' => 'Crawler, SERP preview tool, SEO plugin', 'evidence' => 'Priority pages have unique metadata aligned to search intent and visible page copy.', 'severity' => 'High', 'owner' => 'Content owner', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Review favicon, sitelinks anchors, breadcrumbs, dates, authors, and preview images.', 'why' => 'SERP trust is affected by small signals users see before they ever land on the page.', 'verify' => 'Inspect rendered page, structured data, social previews, and branded navigation anchors.', 'tools' => 'Rich Results Test, social debuggers, browser source', 'evidence' => 'SERP-facing assets are accurate, current, and consistent with the visible page.', 'severity' => 'Medium', 'owner' => 'Marketer', 'use_cases' => 'blog saas ecommerce local'),
            ),
        ),
        array(
            'title' => 'Content quality and helpfulness',
            'why' => 'Technical health will not save thin, outdated, or untrustworthy content.',
            'items' => array(
                array('task' => 'Verify the page answers the primary query better than competing results.', 'why' => 'SEO QA should catch content gaps before rankings depend on thin or generic copy.', 'verify' => 'Compare the page to the top results for sections, examples, tools, freshness, and actionability.', 'tools' => 'SERP review, content brief, subject matter expert review', 'evidence' => 'The page includes a clear answer, unique utility, examples, caveats, and current links.', 'severity' => 'High', 'owner' => 'Content owner', 'use_cases' => 'blog saas local'),
                array('task' => 'Fact-check claims, prices, dates, screenshots, product details, and legal/medical/financial caveats.', 'why' => 'Wrong claims damage trust and can create compliance risk.', 'verify' => 'Review source links, product pages, screenshots, and dated statements against current evidence.', 'tools' => 'Official docs, source URLs, editorial checklist', 'evidence' => 'Claims are sourced, dated where needed, and not overstated.', 'severity' => 'High', 'owner' => 'Editor', 'use_cases' => 'blog saas ecommerce local'),
            ),
        ),
        array(
            'title' => 'Internal links',
            'why' => 'Internal links help users and crawlers understand priority, context, and relationships.',
            'items' => array(
                array('task' => 'Add relevant internal links from high-authority pages to the launch URL.', 'why' => 'New or changed pages need discoverable paths before crawlers and users can value them.', 'verify' => 'Review navigation, hub pages, related articles, breadcrumbs, and contextual links.', 'tools' => 'Internal link crawl, CMS search, Search Console links report', 'evidence' => 'Priority pages have descriptive internal links from relevant existing pages.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Check outgoing internal links for anchor quality, relevance, and broken targets.', 'why' => 'A strong page can leak users into irrelevant, stale, or broken internal paths.', 'verify' => 'Click or crawl all in-content, card, nav, footer, and CTA links.', 'tools' => 'Crawler, browser QA, link checker', 'evidence' => 'Internal links resolve cleanly and anchor text describes the destination.', 'severity' => 'Medium', 'owner' => 'Content owner', 'use_cases' => 'blog saas ecommerce local'),
            ),
        ),
        array(
            'title' => 'Structured data',
            'why' => 'Schema helps search systems understand content, but invalid markup creates noise and false confidence.',
            'items' => array(
                array('task' => 'Validate Article, BreadcrumbList, Product, LocalBusiness, FAQ, or HowTo schema only where visible content supports it.', 'why' => 'Markup that does not match visible content can be ignored and may create quality issues.', 'verify' => 'Run validation and compare every marked-up claim to visible page content.', 'tools' => 'Google Rich Results Test, Schema.org validator', 'evidence' => 'Structured data is valid, relevant, and mirrors visible page content.', 'severity' => 'High', 'owner' => 'Developer', 'use_cases' => 'blog ecommerce local'),
                array('task' => 'Check schema identifiers, breadcrumbs, prices, availability, ratings, author, dates, and organization data.', 'why' => 'Stale schema can show the wrong business, product, author, or page hierarchy.', 'verify' => 'Inspect JSON-LD and compare values with CMS fields and visible content.', 'tools' => 'View source, schema validator, CMS fields', 'evidence' => 'Schema values are current, crawlable, and do not invent unsupported claims.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'blog ecommerce local saas'),
            ),
        ),
        array(
            'title' => 'Images and video',
            'why' => 'Media affects rankings, accessibility, snippets, performance, and conversion.',
            'items' => array(
                array('task' => 'Check image filenames, alt text, dimensions, lazy loading, compression, and CDN URLs.', 'why' => 'Oversized or unlabeled media slows pages and makes visual content harder to understand.', 'verify' => 'Inspect rendered images, network requests, and accessibility names.', 'tools' => 'Lighthouse, WebPageTest, browser dev tools', 'evidence' => 'Images have useful alt text where needed, stable dimensions, and appropriate file sizes.', 'severity' => 'High', 'owner' => 'Designer', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Verify video embeds, thumbnails, transcripts, captions, and video structured data if used.', 'why' => 'Video can support search visibility only when crawlers and users can understand it.', 'verify' => 'Test playback, transcript availability, thumbnail rendering, and schema validity.', 'tools' => 'Rich Results Test, browser QA, video platform settings', 'evidence' => 'Videos load reliably and include accessible supporting text.', 'severity' => 'Medium', 'owner' => 'Content owner', 'use_cases' => 'blog saas ecommerce'),
            ),
        ),
        array(
            'title' => 'Core Web Vitals and performance',
            'why' => 'Performance problems can suppress conversion and make crawlers waste time.',
            'items' => array(
                array('task' => 'Measure LCP, INP, CLS, TTFB, render-blocking assets, and third-party scripts on priority templates.', 'why' => 'A launch can pass visually while the real user experience is slow or unstable.', 'verify' => 'Run lab tests and compare with field data where available.', 'tools' => 'PageSpeed Insights, Lighthouse, CrUX, WebPageTest', 'evidence' => 'Critical templates have documented metrics and no obvious launch-blocking regressions.', 'severity' => 'High', 'owner' => 'Developer', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Confirm caching, preloading, font loading, image sizing, and script loading are production-ready.', 'why' => 'Small asset mistakes can create large regressions after a CMS or theme change.', 'verify' => 'Inspect headers, network waterfall, cache behavior, and layout shift sources.', 'tools' => 'Browser dev tools, CDN dashboard, Lighthouse', 'evidence' => 'Core assets are cacheable, stable, and not blocking primary content unnecessarily.', 'severity' => 'High', 'owner' => 'Developer', 'use_cases' => 'saas ecommerce migration blog'),
            ),
        ),
        array(
            'title' => 'Mobile and accessibility',
            'why' => 'Search traffic is user traffic; SEO QA must include the experience people actually use.',
            'items' => array(
                array('task' => 'Test mobile layout, tap targets, sticky elements, menus, forms, filters, and tables.', 'why' => 'Mobile breakage often hides below the fold or inside interactive states.', 'verify' => 'Use responsive dev tools and at least one real mobile device when possible.', 'tools' => 'Chrome DevTools, Safari responsive mode, real device', 'evidence' => 'Primary tasks are readable, tappable, and not covered by sticky UI.', 'severity' => 'High', 'owner' => 'QA owner', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Check headings, labels, alt text, focus states, contrast, landmarks, and keyboard navigation.', 'why' => 'Accessibility fixes also improve crawlable structure and user trust.', 'verify' => 'Run an automated audit and manually tab through key flows.', 'tools' => 'Lighthouse, axe DevTools, browser accessibility tree', 'evidence' => 'No unlabeled critical controls, broken heading order, or keyboard traps remain.', 'severity' => 'High', 'owner' => 'Designer', 'use_cases' => 'blog saas ecommerce local'),
            ),
        ),
        array(
            'title' => 'Analytics and conversion tracking',
            'why' => 'Post-launch SEO decisions depend on clean measurement.',
            'items' => array(
                array('task' => 'Verify GA4, Search Console, tag manager, consent mode, and pixels load once on production.', 'why' => 'Missing or duplicated tags distort traffic, conversion, and attribution reporting.', 'verify' => 'Use preview/debug tools and realtime reports on a production visit.', 'tools' => 'GA4 DebugView, Tag Assistant, GSC settings', 'evidence' => 'Tags fire once, consent behavior is documented, and properties match production.', 'severity' => 'Critical', 'owner' => 'Analytics owner', 'use_cases' => 'blog saas ecommerce local'),
                array('task' => 'Test organic landing-page conversions, calls, forms, checkout, demos, downloads, and lead magnets.', 'why' => 'A ranking win is wasted if conversion events or destination systems fail.', 'verify' => 'Trigger each event and confirm it appears in analytics, CRM, email, or payment systems.', 'tools' => 'GA4 events, CRM, email platform, payment logs', 'evidence' => 'Every priority conversion has a recorded event and destination record.', 'severity' => 'Critical', 'owner' => 'Growth owner', 'use_cases' => 'saas ecommerce local blog'),
            ),
        ),
        array(
            'title' => 'Local, international, and ecommerce',
            'why' => 'Specialized page types need extra QA beyond a generic content checklist.',
            'items' => array(
                array('task' => 'For local or international pages, verify NAP, service areas, hreflang, language, currency, and localized canonicals.', 'why' => 'Wrong location or locale signals send users and crawlers to the wrong version.', 'verify' => 'Compare visible copy, schema, GBP links, hreflang clusters, and regional URL rules.', 'tools' => 'Hreflang checker, GBP, schema validator, crawler', 'evidence' => 'Each market or location has consistent visible and structured signals.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'local migration saas ecommerce'),
                array('task' => 'For ecommerce pages, check product/category copy, variants, stock, prices, reviews, breadcrumbs, filters, and product schema.', 'why' => 'Ecommerce SEO failures often come from dynamic data, faceted URLs, and stale product markup.', 'verify' => 'Test product states, category filters, schema values, and canonical behavior.', 'tools' => 'Rich Results Test, crawl filters, merchandising system', 'evidence' => 'Indexable product and category pages show accurate data and controlled variants.', 'severity' => 'High', 'owner' => 'Ecommerce owner', 'use_cases' => 'ecommerce migration'),
            ),
        ),
        array(
            'title' => 'Launch-day checks',
            'why' => 'The final smoke test catches deployment-only issues before crawlers and customers do.',
            'items' => array(
                array('task' => 'Run a production smoke crawl immediately after publish.', 'why' => 'Deployment, caching, DNS, and CMS settings can differ from staging.', 'verify' => 'Crawl priority URLs and old URLs, then compare status, canonical, noindex, title, and redirects.', 'tools' => 'Crawler, Search Console URL Inspection, deployment logs', 'evidence' => 'Critical URLs pass crawl, indexability, canonical, and redirect checks in production.', 'severity' => 'Critical', 'owner' => 'QA owner', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Submit or refresh sitemaps and request inspection for the highest-risk URLs.', 'why' => 'Search engines need clean discovery signals after meaningful changes.', 'verify' => 'Confirm sitemap availability and use URL Inspection for priority changed pages.', 'tools' => 'Google Search Console, Bing Webmaster Tools', 'evidence' => 'Sitemaps are submitted and priority URLs show the expected crawl/index signals.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce migration local'),
            ),
        ),
        array(
            'title' => '7-day monitoring',
            'why' => 'Some SEO launch problems only appear after real crawlers, users, and integrations hit the site.',
            'items' => array(
                array('task' => 'Monitor Search Console indexing, crawl stats, 404s, rankings, organic landings, and conversions daily for 7 days.', 'why' => 'Early detection lets teams fix or roll back before a small issue becomes a prolonged traffic drop.', 'verify' => 'Review dashboards each day and add anomalies to the evidence log.', 'tools' => 'GSC, GA4, rank tracker, server logs, error tracking', 'evidence' => 'Daily notes document index coverage, traffic, conversion, and error changes.', 'severity' => 'High', 'owner' => 'SEO owner', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Re-crawl the changed URL set after caching, redirects, and content updates settle.', 'why' => 'The second crawl catches issues hidden by initial cache state or missed deployment tasks.', 'verify' => 'Run the same crawl configuration used before launch and compare deltas.', 'tools' => 'Crawler, link checker, log files', 'evidence' => 'New errors are triaged, assigned, and retested.', 'severity' => 'High', 'owner' => 'QA owner', 'use_cases' => 'saas ecommerce migration local blog'),
            ),
        ),
        array(
            'title' => 'Rollback plan',
            'why' => 'A rollback plan turns panic into a known operating procedure.',
            'items' => array(
                array('task' => 'Define rollback triggers for noindex accidents, broken redirects, broken conversions, or severe traffic loss.', 'why' => 'Teams need to know when to fix forward and when to restore the last known good state.', 'verify' => 'Write thresholds, decision makers, and the exact rollback path before launch.', 'tools' => 'Deployment dashboard, CMS revisions, redirect backups, incident doc', 'evidence' => 'The launch brief names rollback triggers, owners, and steps.', 'severity' => 'Critical', 'owner' => 'Project lead', 'use_cases' => 'blog saas ecommerce migration local'),
                array('task' => 'Back up redirect rules, metadata exports, templates, tracking settings, and sitemap state.', 'why' => 'A rollback is only useful if the old SEO-critical state can be restored quickly.', 'verify' => 'Export or snapshot settings and confirm who can restore each one.', 'tools' => 'CMS export, hosting backup, SEO plugin export, Git', 'evidence' => 'Backups exist for code, content, redirects, tracking, and SEO settings.', 'severity' => 'Critical', 'owner' => 'Developer', 'use_cases' => 'saas ecommerce migration local blog'),
            ),
        ),
    );
}

function kingy_ali_seo_qa_faqs() {
    return array(
        array('question' => 'What is SEO QA?', 'answer' => 'SEO QA is the pre-launch and post-launch process of checking whether pages are crawlable, indexable, technically correct, useful, trackable, and safe to publish. It is narrower and more launch-focused than a full SEO audit.'),
        array('question' => 'How is SEO QA different from an SEO audit?', 'answer' => 'An SEO audit diagnoses the overall health and opportunity of a site. SEO QA is a release gate: it checks the specific pages, templates, redirects, metadata, schema, tracking, and monitoring needed for a launch or content change.'),
        array('question' => 'When should a team run an SEO QA checklist?', 'answer' => 'Run SEO QA before publishing new strategic pages, redesigns, migrations, product launches, ecommerce updates, local pages, and any change that affects URLs, metadata, internal links, schema, tracking, or templates.'),
        array('question' => 'What should I check first if traffic drops after launch?', 'answer' => 'Check noindex and robots directives, canonical tags, redirect maps, server errors, sitemap changes, internal links, analytics tracking, and Search Console coverage for the affected URLs.'),
        array('question' => 'Can automated tools replace SEO QA?', 'answer' => 'Automated crawlers and validators catch many issues, but humans still need to judge search intent, content quality, business impact, legal claims, conversion paths, and whether a redirect destination is truly relevant.'),
    );
}

function kingy_ali_seo_qa_schema() {
    if (!kingy_ali_is_seo_qa_page()) {
        return;
    }

    $faqs = kingy_ali_seo_qa_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('SEO QA Checklist', 'kingy-ai-launch-intelligence'),
                'description' => __('An interactive SEO QA checklist for pre-launch technical SEO testing, launch-day checks, monitoring, and rollback.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'BreadcrumbList',
                'itemListElement' => array(
                    array('@type' => 'ListItem', 'position' => 1, 'name' => __('Home', 'kingy-ai-launch-intelligence'), 'item' => home_url('/')),
                    array('@type' => 'ListItem', 'position' => 2, 'name' => __('SEO QA Checklist', 'kingy-ai-launch-intelligence'), 'item' => get_permalink()),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_seo_qa_templates() {
    return array(
        'brief' => array('title' => 'SEO QA brief', 'lines' => array('Launch/project:', 'Primary pages and templates:', 'Target queries or intents:', 'Markets/locales:', 'Risk level:', 'Owners:', 'Launch window:', 'Rollback approver:', 'Evidence log URL:')),
        'redirect' => array('title' => 'Redirect-map template', 'lines' => array('Old URL | New URL | Reason | Traffic/backlinks | Status | Owner | Retest evidence', '/old-page/ | /new-page/ | Closest equivalent | High | 301 one hop | SEO | Screenshot/crawl row')),
        'signoff' => array('title' => 'Launch sign-off', 'lines' => array('Critical SEO QA passed:', 'Known risks:', 'Deferred fixes:', 'Analytics verified:', 'Sitemap submitted:', 'Rollback path confirmed:', 'Approver and timestamp:')),
        'evidence' => array('title' => 'Evidence log', 'lines' => array('Issue | URL | Severity | Owner | Evidence | Fix | Retest result | Date', 'Wrong canonical | /example/ | Critical | SEO | Screenshot | Updated tag | Passed | YYYY-MM-DD')),
        'monitoring' => array('title' => 'GSC/GA4 monitoring checklist', 'lines' => array('Day 1: index coverage, organic sessions, conversions, 404s, sitemap fetch', 'Day 3: crawl stats, ranking deltas, landing page anomalies, server errors', 'Day 7: compare baseline, document fixes, schedule retro')),
        'rollback' => array('title' => 'Rollback checklist', 'lines' => array('Trigger:', 'Decision maker:', 'Restore code/theme:', 'Restore redirects:', 'Restore metadata/schema:', 'Purge cache/CDN:', 'Pause campaigns if needed:', 'Confirm recovery evidence:')),
    );
}

function kingy_ali_shortcode_seo_qa_checklist() {
    kingy_ali_enqueue_assets();

    $phases = kingy_ali_seo_qa_phases();
    $faqs = kingy_ali_seo_qa_faqs();
    $templates = kingy_ali_seo_qa_templates();
    $total = 0;
    $critical_total = 0;
    $owners = array();
    foreach ($phases as $phase) {
        foreach ($phase['items'] as $item) {
            $total++;
            if ($item['severity'] === 'Critical') {
                $critical_total++;
            }
            $owners[$item['owner']] = true;
        }
    }
    $owner_names = array_keys($owners);
    sort($owner_names);
    $fast_paths = array(
        array('key' => 'blog', 'title' => 'Single blog post', 'checks' => array('Metadata matches intent', 'Helpful content beats the SERP', 'Article schema is valid', 'Internal links point both ways', 'Images, author, dates, and tracking are clean')),
        array('key' => 'saas', 'title' => 'SaaS landing page', 'checks' => array('Indexable production URL', 'Conversion events and CRM handoff work', 'Schema and social previews match visible claims', 'Core Web Vitals have no launch regression')),
        array('key' => 'ecommerce', 'title' => 'Ecommerce page', 'checks' => array('Product/category schema is current', 'Faceted URLs are controlled', 'Stock, price, variants, reviews, and breadcrumbs are accurate', 'Checkout and analytics events work')),
        array('key' => 'migration', 'title' => 'Site migration or relaunch', 'checks' => array('Old URLs have one-hop relevant 301s', 'Canonicals and sitemaps point to the new production URLs', 'GSC and analytics monitoring is scheduled for 7 days')),
        array('key' => 'local', 'title' => 'Local business page', 'checks' => array('NAP, service area, local schema, map links, and GBP links are consistent', 'Location-specific content is visible and useful', 'Calls, forms, and directions tracking work')),
    );
    $mistakes = array('Production noindex left on', 'Canonical points to staging or an old URL', 'Redirect map sends everything to the homepage', 'Sitemap includes noindex, redirecting, or parameter URLs', 'FAQ/Product schema marks up invisible or stale content', 'GA4 or GSC was not verified before launch', 'Mobile sticky UI covers the conversion path', 'Launch team has no rollback trigger');
    $traffic_drop = array('Inspect affected URLs for noindex, robots blocks, canonicals, and 200 status.', 'Crawl old URLs to find broken redirects, chains, loops, and 404s.', 'Compare sitemap, internal links, title tags, and content changes against the pre-launch baseline.', 'Check GSC coverage, crawl stats, server logs, and GA4 organic landing pages.', 'Decide whether to fix forward or roll back based on business impact and recovery speed.');

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-seo-qa-guide" data-kingy-seo-qa-guide data-critical-total="<?php echo esc_attr((string) $critical_total); ?>">
        <header class="kingy-ali-academy-hero kingy-ali-seo-qa-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive technical SEO launch tool', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('SEO QA Checklist', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Use this pre-launch and post-launch SEO quality assurance checklist to catch indexability, canonical, redirect, schema, content, performance, tracking, and rollback issues before they cost traffic.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-hero-meta" aria-label="<?php esc_attr_e('Checklist summary', 'kingy-ai-launch-intelligence'); ?>">
                    <span><?php esc_html_e('For SEOs, developers, founders, and content teams', 'kingy-ai-launch-intelligence'); ?></span>
                    <span><?php esc_html_e('Use before launches, redesigns, migrations, and major page updates', 'kingy-ai-launch-intelligence'); ?></span>
                    <span><?php echo esc_html((string) $total); ?> <?php esc_html_e('checks across', 'kingy-ai-launch-intelligence'); ?> <?php echo esc_html((string) count($phases)); ?> <?php esc_html_e('phases', 'kingy-ai-launch-intelligence'); ?></span>
                </div>
                <div class="kingy-ali-cta-row">
                    <a href="#seo-qa-checklist"><?php esc_html_e('Start checklist', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#seo-qa-templates"><?php esc_html_e('Copy templates', 'kingy-ai-launch-intelligence'); ?></a>
                    <button type="button" data-seo-qa-copy-checklist><?php esc_html_e('Copy checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-seo-qa-print><?php esc_html_e('Print', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
            <aside class="kingy-ali-decision-card kingy-ali-seo-qa-progress-card" aria-label="<?php esc_attr_e('SEO QA progress', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Launch readiness', 'kingy-ai-launch-intelligence'); ?></h2>
                <strong><span data-seo-qa-count>0</span> / <?php echo esc_html((string) $total); ?></strong>
                <progress max="<?php echo esc_attr((string) $total); ?>" value="0" data-seo-qa-progress></progress>
                <p><span data-seo-qa-score>0</span><?php esc_html_e('% readiness. ', 'kingy-ai-launch-intelligence'); ?><span data-seo-qa-status><?php esc_html_e('Start with the critical checks.', 'kingy-ai-launch-intelligence'); ?></span></p>
                <small><span data-seo-qa-critical>0</span> / <?php echo esc_html((string) $critical_total); ?> <?php esc_html_e('critical checks complete. Progress saves in this browser.', 'kingy-ai-launch-intelligence'); ?></small>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav kingy-ali-seo-qa-nav" aria-label="<?php esc_attr_e('SEO QA sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#seo-qa-definition"><?php esc_html_e('Definition', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#seo-qa-checklist"><?php esc_html_e('Checklist', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#seo-qa-fast-paths"><?php esc_html_e('Fast paths', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#seo-qa-templates"><?php esc_html_e('Templates', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#seo-qa-drop"><?php esc_html_e('Traffic drop', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#seo-qa-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="seo-qa-definition" class="kingy-ali-academy-section kingy-ali-answer-block">
            <p class="kingy-ali-kicker"><?php esc_html_e('Short answer', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('SEO QA is a release gate, not a vague audit', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('SEO quality assurance checks whether a specific launch is safe for organic search: pages can be crawled and indexed, redirects preserve relevance, metadata and schema match visible content, internal links point to the right places, performance and accessibility are acceptable, and analytics can prove what happened after launch.', 'kingy-ai-launch-intelligence'); ?></p>
            <p><?php esc_html_e('Use a full SEO audit to find broad opportunities. Use this SEO QA checklist when a page, template, migration, product update, or local page is about to ship and mistakes would be expensive.', 'kingy-ai-launch-intelligence'); ?></p>
        </section>

        <section id="seo-qa-checklist" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Run the SEO QA pass', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Filter by phase, severity, owner, or use case. Each item includes what to test, why it matters, how to verify it, useful tools, and what evidence counts as passing.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-seo-qa-controls" aria-label="<?php esc_attr_e('SEO QA filters', 'kingy-ai-launch-intelligence'); ?>">
                <label><?php esc_html_e('Phase', 'kingy-ai-launch-intelligence'); ?><select data-seo-qa-filter-phase><option value="all"><?php esc_html_e('All phases', 'kingy-ai-launch-intelligence'); ?></option><?php foreach ($phases as $phase) : ?><option value="<?php echo esc_attr(sanitize_title($phase['title'])); ?>"><?php echo esc_html($phase['title']); ?></option><?php endforeach; ?></select></label>
                <label><?php esc_html_e('Severity', 'kingy-ai-launch-intelligence'); ?><select data-seo-qa-filter-severity><option value="all"><?php esc_html_e('All risks', 'kingy-ai-launch-intelligence'); ?></option><option value="Critical"><?php esc_html_e('Critical', 'kingy-ai-launch-intelligence'); ?></option><option value="High"><?php esc_html_e('High', 'kingy-ai-launch-intelligence'); ?></option><option value="Medium"><?php esc_html_e('Medium', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                <label><?php esc_html_e('Owner', 'kingy-ai-launch-intelligence'); ?><select data-seo-qa-filter-owner><option value="all"><?php esc_html_e('All owners', 'kingy-ai-launch-intelligence'); ?></option><?php foreach ($owner_names as $owner) : ?><option value="<?php echo esc_attr(sanitize_title($owner)); ?>"><?php echo esc_html($owner); ?></option><?php endforeach; ?></select></label>
                <label><?php esc_html_e('Use case', 'kingy-ai-launch-intelligence'); ?><select data-seo-qa-filter-use-case><option value="all"><?php esc_html_e('All use cases', 'kingy-ai-launch-intelligence'); ?></option><option value="blog"><?php esc_html_e('Blog post', 'kingy-ai-launch-intelligence'); ?></option><option value="saas"><?php esc_html_e('SaaS landing page', 'kingy-ai-launch-intelligence'); ?></option><option value="ecommerce"><?php esc_html_e('Ecommerce', 'kingy-ai-launch-intelligence'); ?></option><option value="migration"><?php esc_html_e('Migration', 'kingy-ai-launch-intelligence'); ?></option><option value="local"><?php esc_html_e('Local page', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                <label class="kingy-ali-seo-qa-toggle"><input type="checkbox" data-seo-qa-show-incomplete> <?php esc_html_e('Show incomplete only', 'kingy-ai-launch-intelligence'); ?></label>
            </div>
            <div class="kingy-ali-seo-qa-checklist" data-seo-qa-checklist>
                <?php foreach ($phases as $phase_index => $phase) : ?>
                    <?php $phase_slug = sanitize_title($phase['title']); ?>
                    <details open data-seo-qa-phase data-phase="<?php echo esc_attr($phase_slug); ?>">
                        <summary class="kingy-ali-seo-qa-phase-head">
                            <div><h3><?php echo esc_html($phase['title']); ?></h3><p><?php echo esc_html($phase['why']); ?></p></div>
                            <div class="kingy-ali-seo-qa-phase-score"><strong><span data-seo-qa-phase-count>0</span> / <?php echo esc_html((string) count($phase['items'])); ?></strong><progress max="<?php echo esc_attr((string) count($phase['items'])); ?>" value="0" data-seo-qa-phase-progress></progress></div>
                        </summary>
                        <?php foreach ($phase['items'] as $item_index => $item) : ?>
                            <?php $check_id = 'seo-qa-' . absint($phase_index) . '-' . absint($item_index); ?>
                            <label class="kingy-ali-seo-qa-item" for="<?php echo esc_attr($check_id); ?>" data-seo-qa-item data-phase="<?php echo esc_attr($phase_slug); ?>" data-phase-title="<?php echo esc_attr($phase['title']); ?>" data-item-title="<?php echo esc_attr($item['task']); ?>" data-severity="<?php echo esc_attr($item['severity']); ?>" data-owner="<?php echo esc_attr($item['owner']); ?>" data-owner-slug="<?php echo esc_attr(sanitize_title($item['owner'])); ?>" data-use-cases="<?php echo esc_attr($item['use_cases']); ?>" data-why="<?php echo esc_attr($item['why']); ?>" data-verify="<?php echo esc_attr($item['verify']); ?>" data-tools="<?php echo esc_attr($item['tools']); ?>" data-evidence="<?php echo esc_attr($item['evidence']); ?>">
                                <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-seo-qa-check>
                                <span class="kingy-ali-seo-qa-item-body">
                                    <span class="kingy-ali-seo-qa-item-top"><strong><?php echo esc_html($item['task']); ?></strong><em class="kingy-ali-risk-<?php echo esc_attr(strtolower($item['severity'])); ?>"><?php echo esc_html($item['severity']); ?></em></span>
                                    <small><b><?php esc_html_e('Owner:', 'kingy-ai-launch-intelligence'); ?></b> <?php echo esc_html($item['owner']); ?> <b><?php esc_html_e('Tools:', 'kingy-ai-launch-intelligence'); ?></b> <?php echo esc_html($item['tools']); ?></small>
                                    <small><b><?php esc_html_e('Why:', 'kingy-ai-launch-intelligence'); ?></b> <?php echo esc_html($item['why']); ?></small>
                                    <small><b><?php esc_html_e('Verify:', 'kingy-ai-launch-intelligence'); ?></b> <?php echo esc_html($item['verify']); ?></small>
                                    <small><b><?php esc_html_e('Pass evidence:', 'kingy-ai-launch-intelligence'); ?></b> <?php echo esc_html($item['evidence']); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
                <div class="kingy-ali-codex-actions">
                    <button type="button" data-seo-qa-reset><?php esc_html_e('Reset checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-seo-qa-copy-checklist><?php esc_html_e('Copy checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-seo-qa-print><?php esc_html_e('Print checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="seo-qa-fast-paths" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Fast paths', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Use the checklist differently by launch type', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($fast_paths as $path) : ?>
                    <div class="kingy-ali-link-panel"><h3><?php echo esc_html($path['title']); ?></h3><ul><?php foreach ($path['checks'] as $check) : ?><li><?php echo esc_html($check); ?></li><?php endforeach; ?></ul><button type="button" data-seo-qa-apply-use-case="<?php echo esc_attr($path['key']); ?>"><?php esc_html_e('Filter to this path', 'kingy-ai-launch-intelligence'); ?></button></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="seo-qa-templates" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Copyable artifacts', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Templates for handoff, evidence, monitoring, and rollback', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-seo-qa-template-grid">
                <?php foreach ($templates as $key => $template) : ?>
                    <section class="kingy-ali-seo-qa-template"><h3><?php echo esc_html($template['title']); ?></h3><pre data-seo-qa-template="<?php echo esc_attr($key); ?>"><?php echo esc_html(implode("\n", $template['lines'])); ?></pre><button type="button" data-seo-qa-copy-template="<?php echo esc_attr($key); ?>"><?php esc_html_e('Copy template', 'kingy-ai-launch-intelligence'); ?></button></section>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Critical mistakes', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('The misses that usually hurt most', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-website-qa-failures"><?php foreach ($mistakes as $mistake) : ?><span><?php echo esc_html($mistake); ?></span><?php endforeach; ?></div>
        </section>

        <section id="seo-qa-drop" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Emergency triage', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('What to check first if rankings or organic traffic drop after launch', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <ol class="kingy-ali-seo-qa-drop-list"><?php foreach ($traffic_drop as $step) : ?><li><?php echo esc_html($step); ?></li><?php endforeach; ?></ol>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('References and related workflows', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Use official docs, then keep the work connected', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="https://developers.google.com/search/docs/fundamentals/seo-starter-guide"><strong><?php esc_html_e('Google SEO Starter Guide', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Baseline guidance for search-friendly pages.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag"><strong><?php esc_html_e('Google robots meta tag docs', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use when checking noindex and crawl directives.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data"><strong><?php esc_html_e('Google structured data docs', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Validate markup against visible content.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/12-website-qa-checklist/')); ?>"><strong><?php esc_html_e('Website QA Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Pair SEO checks with forms, accessibility, security, and browser QA.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn the punch list into a scoped implementation prompt.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/10-wordpress-custom-html-safety-checklist/')); ?>"><strong><?php esc_html_e('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Check pasted HTML, CSS, scripts, and embeds before publishing.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="seo-qa-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('SEO QA checklist questions', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-faq-list"><?php foreach ($faqs as $faq) : ?><details><summary><?php echo esc_html($faq['question']); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?></div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}
