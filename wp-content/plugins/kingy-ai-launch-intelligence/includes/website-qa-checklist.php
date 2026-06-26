<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_website_qa_checklist', 'kingy_ali_shortcode_website_qa_checklist');
add_filter('the_content', 'kingy_ali_maybe_replace_website_qa_checklist', 20);
add_filter('wpseo_title', 'kingy_ali_website_qa_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_website_qa_seo_description');
add_filter('document_title_parts', 'kingy_ali_website_qa_document_title');
add_action('wp_head', 'kingy_ali_website_qa_schema');

function kingy_ali_is_website_qa_page() {
    return kingy_ali_is_rendering_page_slug('12-website-qa-checklist');
}

function kingy_ali_maybe_replace_website_qa_checklist($content) {
    if (!kingy_ali_is_website_qa_page()) {
        return $content;
    }

    return kingy_ali_shortcode_website_qa_checklist();
}

function kingy_ali_website_qa_seo_title($title) {
    if (!kingy_ali_is_website_qa_page()) {
        return $title;
    }

    return __('Website QA Checklist: Interactive Pre-Launch Testing Tool', 'kingy-ai-launch-intelligence');
}

function kingy_ali_website_qa_seo_description($description) {
    if (!kingy_ali_is_website_qa_page()) {
        return $description;
    }

    return __('Use this interactive website QA checklist to test mobile layout, links, forms, SEO, accessibility, analytics, security, launch day, and rollback before publishing.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_website_qa_document_title($parts) {
    if (kingy_ali_is_website_qa_page()) {
        $parts['title'] = __('Website QA Checklist', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_website_qa_categories() {
    return array(
        array(
            'title' => __('Setup and safety', 'kingy-ai-launch-intelligence'),
            'why' => __('Start from a recoverable workspace so a simple QA fix does not become a production incident.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Confirm you are testing the correct staging, preview, or production URL.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Project lead', 'kingy-ai-launch-intelligence'), 'how' => __('Open the URL in a private window and compare it with the deployment or hosting dashboard.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Create or confirm a fresh backup, branch, restore point, or rollback plan.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Write where the rollback lives and who can trigger it before making changes.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Remove test credentials, fake orders, placeholder copy, and sample images.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Content owner', 'kingy-ai-launch-intelligence'), 'how' => __('Search pages, forms, CMS fields, and checkout settings for demo values.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Visual layout', 'kingy-ai-launch-intelligence'),
            'why' => __('A page can technically load while still feeling broken because spacing, hierarchy, or content states are wrong.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Review every key template for overlapping text, cropped images, and broken spacing.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Designer', 'kingy-ai-launch-intelligence'), 'how' => __('Check home, landing, article, product, search, 404, and contact views at normal zoom.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Test empty, long, and error states for cards, tables, filters, and forms.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Use real long names, missing images, no results, and validation errors.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm images, icons, and media communicate the real product or page state.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Marketer', 'kingy-ai-launch-intelligence'), 'how' => __('Replace vague stock visuals, broken embeds, and unclear thumbnails.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Responsive and mobile', 'kingy-ai-launch-intelligence'),
            'why' => __('Most launch embarrassment happens on the screen size nobody checked last.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Test the site at mobile, tablet, laptop, and wide desktop widths.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Use browser dev tools plus at least one real phone when possible.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Verify menus, sticky bars, popups, and CTAs do not cover content.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Designer', 'kingy-ai-launch-intelligence'), 'how' => __('Scroll from top to footer and rotate the device if supported.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Tap every primary action with a thumb-sized target.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Buttons and inputs should be reachable, readable, and not too close together.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Links and navigation', 'kingy-ai-launch-intelligence'),
            'why' => __('Broken links waste launch traffic and make a polished site feel unfinished.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Click header, footer, menu, breadcrumb, CTA, and in-content links.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Watch for 404s, wrong domains, staging URLs, and surprise new tabs.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm redirects for renamed, removed, or migrated pages.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('SEO owner', 'kingy-ai-launch-intelligence'), 'how' => __('Test old URLs and verify they land on the closest useful page.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Check search, filters, pagination, anchors, and table-of-contents jumps.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Use realistic queries and confirm URLs remain shareable when relevant.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Forms and conversions', 'kingy-ai-launch-intelligence'),
            'why' => __('A launch can look successful while every lead, signup, or sale silently disappears.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Submit every contact, signup, demo, checkout, and download form.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Growth owner', 'kingy-ai-launch-intelligence'), 'how' => __('Test success, validation, duplicate submission, and notification emails.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Verify CRM, email platform, payment, calendar, and automation handoffs.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Operations', 'kingy-ai-launch-intelligence'), 'how' => __('Confirm the submitted record appears in the destination with source data intact.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm thank-you pages and post-submit CTAs match the promise.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Marketer', 'kingy-ai-launch-intelligence'), 'how' => __('Check the next step, copy, tracking, and downloadable asset.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('SEO and indexing', 'kingy-ai-launch-intelligence'),
            'why' => __('Search issues often come from tiny launch settings: noindex, wrong canonicals, missing redirects, or thin metadata.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Check title tags, meta descriptions, H1s, canonicals, and robots settings.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('SEO owner', 'kingy-ai-launch-intelligence'), 'how' => __('View source or use an SEO extension and confirm production pages are indexable.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Generate and submit XML sitemaps after launch changes.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('SEO owner', 'kingy-ai-launch-intelligence'), 'how' => __('Open the sitemap, look for missing or staging URLs, then submit in Search Console.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Add structured data for FAQ, Article, Product, LocalBusiness, or HowTo when appropriate.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Validate schema and avoid marking up content that is not visible on the page.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Accessibility', 'kingy-ai-launch-intelligence'),
            'why' => __('Accessible pages are easier to use, easier to QA, and less likely to break for keyboard and assistive-tech users.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Navigate key flows using only the keyboard.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Tab through menus, forms, dialogs, and CTAs; confirm focus is visible and logical.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Check labels, alt text, headings, landmarks, and button names.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Designer', 'kingy-ai-launch-intelligence'), 'how' => __('Use a browser accessibility tree or audit tool and fix unlabeled controls.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm color contrast and reduced-motion behavior.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Designer', 'kingy-ai-launch-intelligence'), 'how' => __('Test text, links, disabled states, focus rings, and animated elements.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Performance and Core Web Vitals', 'kingy-ai-launch-intelligence'),
            'why' => __('Speed affects user trust, paid traffic efficiency, and search performance.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Run Lighthouse or PageSpeed for important pages.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Record LCP, CLS, INP, render-blocking assets, and image opportunities.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Compress, size, lazy-load, or replace oversized images and video.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Content owner', 'kingy-ai-launch-intelligence'), 'how' => __('Inspect the network panel for large files and missing dimensions.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Check caching, CDN, fonts, and third-party scripts.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Temporarily disable nonessential scripts if they dominate load time.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Analytics and tracking', 'kingy-ai-launch-intelligence'),
            'why' => __('Without tracking QA, you cannot tell whether launch traffic worked or where it leaked.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Verify analytics, pixels, consent mode, and tag manager containers load once.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Analytics owner', 'kingy-ai-launch-intelligence'), 'how' => __('Use realtime analytics, preview mode, and network requests.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Test conversion events for forms, calls, checkout, downloads, and key CTAs.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Growth owner', 'kingy-ai-launch-intelligence'), 'how' => __('Trigger each event and confirm event name, parameters, and attribution.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Exclude internal traffic and mark launch annotations.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Analytics owner', 'kingy-ai-launch-intelligence'), 'how' => __('Document the launch date and expected traffic sources.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Security and privacy', 'kingy-ai-launch-intelligence'),
            'why' => __('Launch QA should catch exposed secrets, mixed content, missing consent, and risky browser-visible code.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Check SSL, mixed-content warnings, security headers, and forced HTTPS.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Open dev tools security/network panels and test http to https redirects.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Search source and public JavaScript for API keys, tokens, and private data.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Inspect page source, built assets, environment exposure, and public repo diffs.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm privacy, cookie, terms, and consent language match actual tracking.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Operations', 'kingy-ai-launch-intelligence'), 'how' => __('Compare policy claims with analytics, embeds, forms, and pixels.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Browser and device testing', 'kingy-ai-launch-intelligence'),
            'why' => __('A site is not launched just because it works in the builder or your favorite browser.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Test Chrome, Safari, Firefox, and Edge where your audience uses them.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Prioritize browsers from analytics and include iOS Safari if mobile traffic matters.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Check logged-in, logged-out, incognito, and cached visitor states.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Clear cache and cookies, then repeat the most important flows.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Test embeds, video, maps, calendars, chat, and payment widgets.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Operations', 'kingy-ai-launch-intelligence'), 'how' => __('Watch for blocked third-party scripts, iframe sizing, and consent interactions.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Launch day', 'kingy-ai-launch-intelligence'),
            'why' => __('Launch day is mostly coordination: final checks, ownership, redirects, monitoring, and fast escalation.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Confirm DNS, hosting, CDN, caching, environment, and deployment status.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Developer', 'kingy-ai-launch-intelligence'), 'how' => __('Check dashboards after deployment and purge cache when needed.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Run a final smoke test of pages, forms, checkout, search, login, and analytics.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('QA owner', 'kingy-ai-launch-intelligence'), 'how' => __('Use a short written list and mark the exact time each pass was completed.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Assign a launch watcher and escalation channel for the first day.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Project lead', 'kingy-ai-launch-intelligence'), 'how' => __('Name who watches errors, support, analytics, Search Console, and payments.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Post-launch monitoring', 'kingy-ai-launch-intelligence'),
            'why' => __('Some issues only appear after real traffic, crawlers, ads, emails, and integrations hit the site.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Check uptime, logs, error tracking, Search Console, and analytics daily for 7 days.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Project lead', 'kingy-ai-launch-intelligence'), 'how' => __('Look for spikes in 404s, JS errors, form drops, crawl errors, and slow pages.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Review real submissions, purchases, leads, and support tickets.', 'kingy-ai-launch-intelligence'), 'priority' => 'High', 'owner' => __('Operations', 'kingy-ai-launch-intelligence'), 'how' => __('Confirm humans can follow up and no required fields are missing.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Schedule a 7-day QA retro and prioritize fixes by severity.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Project lead', 'kingy-ai-launch-intelligence'), 'how' => __('Separate must-fix launch bugs from improvements and future experiments.', 'kingy-ai-launch-intelligence')),
            ),
        ),
        array(
            'title' => __('Rollback', 'kingy-ai-launch-intelligence'),
            'why' => __('A calm rollback plan makes teams braver because the failure mode is already named.', 'kingy-ai-launch-intelligence'),
            'items' => array(
                array('text' => __('Define rollback triggers for broken payments, broken forms, indexing accidents, or major layout failures.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Project lead', 'kingy-ai-launch-intelligence'), 'how' => __('Write the threshold that turns a fix-forward into a rollback.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Confirm who can roll back code, content, DNS, redirects, and plugins.', 'kingy-ai-launch-intelligence'), 'priority' => 'Critical', 'owner' => __('Operations', 'kingy-ai-launch-intelligence'), 'how' => __('List names, accounts, and backup contact paths.', 'kingy-ai-launch-intelligence')),
                array('text' => __('Prepare a customer-facing incident note if the launch affects users.', 'kingy-ai-launch-intelligence'), 'priority' => 'Medium', 'owner' => __('Support', 'kingy-ai-launch-intelligence'), 'how' => __('Draft a short status update, workaround, and next-check time.', 'kingy-ai-launch-intelligence')),
            ),
        ),
    );
}

function kingy_ali_website_qa_faqs() {
    return array(
        array('question' => __('What is website QA?', 'kingy-ai-launch-intelligence'), 'answer' => __('Website QA is the pre-launch and post-launch process of testing whether a site works for real users: layout, mobile behavior, links, forms, SEO, accessibility, performance, analytics, privacy, and rollback.', 'kingy-ai-launch-intelligence')),
        array('question' => __('How long should website QA take?', 'kingy-ai-launch-intelligence'), 'answer' => __('A small site can get a useful 15-minute smoke test, but a full launch QA pass usually takes 60 minutes to several hours depending on forms, checkout, content volume, integrations, and migration risk.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What should I test before launching a website?', 'kingy-ai-launch-intelligence'), 'answer' => __('Test the first screen, navigation, mobile layout, forms, conversion paths, redirects, metadata, indexing, accessibility, page speed, analytics events, privacy notices, browser compatibility, and rollback plan.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can AI or Codex run website QA for me?', 'kingy-ai-launch-intelligence'), 'answer' => __('AI can inspect code, run automated checks, generate test plans, and find many issues. A human still needs to approve publishing, verify real business flows, and make judgment calls about design, claims, privacy, and customer impact.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is the most common website launch mistake?', 'kingy-ai-launch-intelligence'), 'answer' => __('The most expensive mistakes are usually hidden: forms that do not send, conversion events that do not fire, noindex left on production, broken redirects, exposed keys, and mobile layouts nobody tested on a real phone.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_website_qa_schema() {
    if (!kingy_ali_is_website_qa_page()) {
        return;
    }

    $faqs = kingy_ali_website_qa_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('Website QA Checklist', 'kingy-ai-launch-intelligence'),
                'description' => __('An interactive website QA checklist for pre-launch testing, launch-day checks, post-launch monitoring, and rollback planning.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to QA a website before launch', 'kingy-ai-launch-intelligence'),
                'description' => __('A practical workflow for checking a website before publishing.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Confirm setup, staging, backup, and rollback.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Test layout, mobile behavior, navigation, forms, and conversions.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Check SEO, accessibility, performance, analytics, security, and privacy.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Run launch-day smoke tests and monitor the first 7 days.', 'kingy-ai-launch-intelligence')),
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

function kingy_ali_website_qa_codex_prompt() {
    return __("/goal QA this website before launch.\n\nFirst inspect the current site, source, routes, forms, analytics, SEO metadata, schema, accessibility, mobile layout, build setup, and deployment path. Do not edit yet.\n\nWork safely:\n- Use a branch, staging site, preview deployment, or backup.\n- Do not expose secrets, tokens, API keys, customer data, or private analytics.\n- List files/settings that should not change.\n- Preserve existing brand, routes, tracking conventions, and CMS content unless a change is required.\n\nRun checks for: visual layout, responsive/mobile, links/navigation, forms/conversions, SEO/indexing, accessibility, performance/Core Web Vitals, analytics/tracking, security/privacy, browser/device behavior, launch-day smoke tests, 7-day monitoring, and rollback.\n\nReturn a prioritized punch list with Critical, High, Medium, and Optional. Implement only approved fixes, then rerun the relevant checks and summarize rollback steps.", 'kingy-ai-launch-intelligence');
}

function kingy_ali_shortcode_website_qa_checklist() {
    kingy_ali_enqueue_assets();

    $categories = kingy_ali_website_qa_categories();
    $faqs = kingy_ali_website_qa_faqs();
    $total = 0;
    foreach ($categories as $category) {
        $total += count($category['items']);
    }

    $paths = array(
        array('title' => __('15-minute quick QA', 'kingy-ai-launch-intelligence'), 'items' => array(__('Open the site on desktop and phone.', 'kingy-ai-launch-intelligence'), __('Click top nav, footer, main CTA, and key landing pages.', 'kingy-ai-launch-intelligence'), __('Submit forms and confirm notifications.', 'kingy-ai-launch-intelligence'), __('Check noindex, title, meta description, canonical, and analytics realtime.', 'kingy-ai-launch-intelligence'), __('Confirm rollback owner before publishing.', 'kingy-ai-launch-intelligence'))),
        array('title' => __('60-minute full QA', 'kingy-ai-launch-intelligence'), 'items' => array(__('Complete every interactive checklist category.', 'kingy-ai-launch-intelligence'), __('Run browser, mobile, accessibility, speed, schema, link, and form checks.', 'kingy-ai-launch-intelligence'), __('Document critical fixes, owners, and retest evidence.', 'kingy-ai-launch-intelligence'), __('Smoke-test after deployment and keep monitoring open.', 'kingy-ai-launch-intelligence'))),
        array('title' => __('Agency/client handoff QA', 'kingy-ai-launch-intelligence'), 'items' => array(__('Share owners for content, design, SEO, development, analytics, and approval.', 'kingy-ai-launch-intelligence'), __('Export the checklist as Markdown and attach screenshots for high-risk items.', 'kingy-ai-launch-intelligence'), __('Get written approval for remaining risks and rollback triggers.', 'kingy-ai-launch-intelligence'))),
    );

    $failures = array(
        __('Staging noindex left live', 'kingy-ai-launch-intelligence'),
        __('Broken forms or notifications', 'kingy-ai-launch-intelligence'),
        __('Missing redirects and 404s', 'kingy-ai-launch-intelligence'),
        __('Broken mobile navigation', 'kingy-ai-launch-intelligence'),
        __('Untracked conversions', 'kingy-ai-launch-intelligence'),
        __('Exposed API keys or tokens', 'kingy-ai-launch-intelligence'),
        __('Layout shift from unsized media', 'kingy-ai-launch-intelligence'),
        __('Cookie or privacy mismatch', 'kingy-ai-launch-intelligence'),
        __('Inaccessible buttons and fields', 'kingy-ai-launch-intelligence'),
        __('Mixed content and bad canonicals', 'kingy-ai-launch-intelligence'),
    );

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-website-qa-guide" data-kingy-website-qa-guide>
        <header class="kingy-ali-academy-hero kingy-ali-website-qa-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive pre-launch tool', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Website QA Checklist', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Catch broken links, mobile issues, form failures, SEO gaps, accessibility problems, tracking mistakes, security risks, and launch-day errors before publishing.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#website-qa-checklist"><?php esc_html_e('Start checklist', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#website-qa-codex"><?php esc_html_e('Copy Codex prompt', 'kingy-ai-launch-intelligence'); ?></a>
                    <button type="button" data-website-qa-copy-markdown><?php esc_html_e('Copy Markdown', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-website-qa-print><?php esc_html_e('Print checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
            <aside class="kingy-ali-decision-card kingy-ali-website-qa-progress-card" aria-label="<?php esc_attr_e('Checklist progress', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Launch readiness', 'kingy-ai-launch-intelligence'); ?></h2>
                <strong><span data-website-qa-count>0</span> / <?php echo esc_html((string) $total); ?></strong>
                <span data-website-qa-status><?php esc_html_e('Start with the critical checks.', 'kingy-ai-launch-intelligence'); ?></span>
                <progress max="<?php echo esc_attr((string) $total); ?>" value="0" data-website-qa-progress></progress>
                <p><?php esc_html_e('Progress saves in this browser. Use the Markdown export when you need a handoff record.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Website QA sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#website-qa-checklist"><?php esc_html_e('Checklist', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#website-qa-paths"><?php esc_html_e('QA paths', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#website-qa-codex"><?php esc_html_e('Codex', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#website-qa-failures"><?php esc_html_e('Failures', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#website-qa-rollback"><?php esc_html_e('Rollback', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#website-qa-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section class="kingy-ali-academy-section kingy-ali-answer-block">
            <p class="kingy-ali-kicker"><?php esc_html_e('Short answer', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('What should a website QA checklist include?', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('A complete website QA checklist covers setup safety, visual layout, responsive design, links, forms, SEO, accessibility, performance, analytics, security, browser testing, launch-day smoke tests, post-launch monitoring, and rollback. The fastest useful version checks the path from first visit to conversion on both desktop and mobile.', 'kingy-ai-launch-intelligence'); ?></p>
        </section>

        <section id="website-qa-checklist" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Run the QA pass before you publish', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Each item has a priority, owner, and practical way to test it. If you are not sure, mark it unresolved and assign an owner instead of guessing.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-website-qa-checklist" data-website-qa-checklist>
                <?php foreach ($categories as $category_index => $category) : ?>
                    <details open data-website-qa-category>
                        <summary class="kingy-ali-website-qa-category-head">
                            <div>
                                <h3><?php echo esc_html($category['title']); ?></h3>
                                <p><?php echo esc_html($category['why']); ?></p>
                            </div>
                            <div class="kingy-ali-website-qa-category-score" aria-live="polite">
                                <strong><span data-website-qa-category-count>0</span> / <?php echo esc_html((string) count($category['items'])); ?></strong>
                                <progress max="<?php echo esc_attr((string) count($category['items'])); ?>" value="0" data-website-qa-category-progress></progress>
                            </div>
                        </summary>
                        <?php foreach ($category['items'] as $item_index => $item) : ?>
                            <?php $check_id = 'website-qa-' . absint($category_index) . '-' . absint($item_index); ?>
                            <label class="kingy-ali-website-qa-item" for="<?php echo esc_attr($check_id); ?>" data-website-qa-item data-category-title="<?php echo esc_attr($category['title']); ?>" data-item-title="<?php echo esc_attr($item['text']); ?>" data-priority="<?php echo esc_attr($item['priority']); ?>" data-owner="<?php echo esc_attr($item['owner']); ?>" data-how="<?php echo esc_attr($item['how']); ?>">
                                <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-website-qa-check>
                                <span>
                                    <strong><?php echo esc_html($item['text']); ?></strong>
                                    <em><?php echo esc_html($item['priority']); ?> · <?php echo esc_html($item['owner']); ?></em>
                                    <small><?php echo esc_html($item['how']); ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
                <div class="kingy-ali-codex-actions">
                    <button type="button" data-website-qa-reset><?php esc_html_e('Reset checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-website-qa-copy-markdown><?php esc_html_e('Copy Markdown checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-website-qa-print><?php esc_html_e('Print checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="website-qa-paths" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Choose the right depth', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Quick QA, full QA, or handoff QA', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($paths as $path) : ?>
                    <div class="kingy-ali-link-panel">
                        <h3><?php echo esc_html($path['title']); ?></h3>
                        <ul>
                            <?php foreach ($path['items'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="website-qa-codex" class="kingy-ali-copy-prompt">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('AI-safe workflow', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Copy this Codex prompt before editing a site', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('This keeps AI work scoped: inspect first, protect secrets, preserve routes and tracking, report risks, then implement only approved fixes.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <pre id="website-qa-codex-prompt" data-website-qa-codex-prompt><?php echo esc_html(kingy_ali_website_qa_codex_prompt()); ?></pre>
            <button type="button" data-website-qa-copy-prompt data-copy-label="<?php esc_attr_e('Copy Codex prompt', 'kingy-ai-launch-intelligence'); ?>"><?php esc_html_e('Copy Codex prompt', 'kingy-ai-launch-intelligence'); ?></button>
            <div class="kingy-ali-spec-grid">
                <div><h3><?php esc_html_e('WordPress', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Ask Codex to inspect theme templates, plugin output, shortcodes, forms, caching, SEO plugin settings, and Custom HTML blocks.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Webflow', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Have it review published URLs, breakpoints, interactions, forms, redirects, custom code, and CMS collections.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Shopify', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Include product templates, cart, checkout handoffs, apps, tracking pixels, discounts, redirects, and theme backups.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Next.js/Vercel', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Ask for route, metadata, schema, env exposure, build, preview deployment, Core Web Vitals, and rollback checks.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Static HTML', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Focus on links, forms, metadata, assets, responsive CSS, security headers, hosting config, and deploy rollback.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section id="website-qa-failures" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Common launch failures', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Tiny misses that create expensive fixes', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-website-qa-failures">
                <?php foreach ($failures as $failure) : ?>
                    <span><?php echo esc_html($failure); ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="website-qa-rollback" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Rollback template', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Know how you will undo a bad launch before you need to', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-comparison-table-wrap">
                <table class="kingy-ali-comparison-table">
                    <thead><tr><th><?php esc_html_e('Risk', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Trigger', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Rollback action', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Owner', 'kingy-ai-launch-intelligence'); ?></th></tr></thead>
                    <tbody>
                        <tr><td><?php esc_html_e('Forms fail', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Submission does not arrive or autoresponder fails.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Restore previous form, disable campaign traffic, or route to backup form.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Growth / Ops', 'kingy-ai-launch-intelligence'); ?></td></tr>
                        <tr><td><?php esc_html_e('Indexing accident', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Production is noindex, canonicalized wrong, or missing sitemap URLs.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Fix metadata, resubmit sitemap, request recrawl, and monitor coverage.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('SEO', 'kingy-ai-launch-intelligence'); ?></td></tr>
                        <tr><td><?php esc_html_e('Revenue path breaks', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Checkout, booking, pricing, or payment handoff fails.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Revert deployment or theme, pause ads/email, publish status note.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Developer', 'kingy-ai-launch-intelligence'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Resources', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep this connected to the rest of the build workflow', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/')); ?>"><strong><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Return to the beginner build path.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/build-ai-academy-toolkit/')); ?>"><strong><?php esc_html_e('Build AI Academy Toolkit', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Find related QA, scoping, prompt, and shipping resources.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn the QA pass into a scoped implementation prompt.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/project-library/')); ?>"><strong><?php esc_html_e('Project Library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Choose the next calculator, directory, quiz, lead magnet, or app build to QA.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/ship-ai-built-projects/')); ?>"><strong><?php esc_html_e('Shipping Path', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use preview deployments, environment checks, QA notes, and rollback plans before production.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/10-wordpress-custom-html-safety-checklist/')); ?>"><strong><?php esc_html_e('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Check pasted HTML, CSS, JavaScript, embeds, and forms before publishing.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="website-qa-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Website QA checklist questions', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details>
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}
