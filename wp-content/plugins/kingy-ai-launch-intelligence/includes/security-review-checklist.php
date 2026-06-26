<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_security_review_checklist', 'kingy_ali_shortcode_security_review_checklist');
add_filter('the_content', 'kingy_ali_maybe_replace_security_review_checklist', 20);
add_filter('wpseo_title', 'kingy_ali_security_review_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_security_review_seo_description');
add_filter('document_title_parts', 'kingy_ali_security_review_document_title');
add_action('wp_head', 'kingy_ali_security_review_schema');

function kingy_ali_is_security_review_page() {
    return kingy_ali_is_rendering_page_slug('17-security-review-checklist');
}

function kingy_ali_maybe_replace_security_review_checklist($content) {
    if (!kingy_ali_is_security_review_page()) {
        return $content;
    }

    return kingy_ali_shortcode_security_review_checklist();
}

function kingy_ali_security_review_seo_title($title) {
    if (!kingy_ali_is_security_review_page()) {
        return $title;
    }

    return __('Security Review Checklist for AI Coding Agents, Codex, Apps', 'kingy-ai-launch-intelligence');
}

function kingy_ali_security_review_seo_description($description) {
    if (!kingy_ali_is_security_review_page()) {
        return $description;
    }

    return __('Use this interactive security review checklist, risk profiler, safe Codex prompt builder, red flags, templates, and printable pre-deployment approval checklist.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_security_review_document_title($parts) {
    if (kingy_ali_is_security_review_page()) {
        $parts['title'] = __('Security Review Checklist', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_security_review_phases() {
    return array(
        array('title' => __('Before Prompting', 'kingy-ai-launch-intelligence'), 'why' => __('A safer AI task starts with scope, context, and limits before any code changes happen.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Name the exact outcome, user flow, and page or feature being reviewed.', 'kingy-ai-launch-intelligence'), 'why' => __('Vague work invites broad rewrites and missed risk.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: “Review checkout success page copy and tracking only.” Fail: “Make the site better.”', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to restate the task, assumptions, affected files, and unknowns before editing.', 'kingy-ai-launch-intelligence')),
            array('text' => __('List what the AI must not change.', 'kingy-ai-launch-intelligence'), 'why' => __('Boundaries protect routes, offers, tracking, content, and production behavior.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: “Do not change auth, pricing, schema, or database tables.” Fail: no forbidden-change list.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to confirm excluded systems and stop if the request touches them.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Before Editing', 'kingy-ai-launch-intelligence'), 'why' => __('Inspection-first work keeps AI from fixing the wrong layer.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Work from a branch, staging copy, preview deployment, backup, or copied snippet.', 'kingy-ai-launch-intelligence'), 'why' => __('A recoverable workspace turns mistakes into normal edits instead of incidents.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: branch plus preview URL. Fail: direct live edits with no backup.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to identify the current branch, deployment target, and rollback path.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Have Codex inspect relevant files, settings, URLs, and errors before proposing changes.', 'kingy-ai-launch-intelligence'), 'why' => __('Security review depends on evidence, not guesses.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: it reads templates, env usage, package files, and routes. Fail: it edits immediately.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask for a short inspection summary and risk list before implementation.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Secrets & Environment', 'kingy-ai-launch-intelligence'), 'why' => __('Most beginner security failures are exposed keys, copied credentials, or mixed environments.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Do not paste API keys, passwords, tokens, cookies, or private URLs into prompts or commits.', 'kingy-ai-launch-intelligence'), 'why' => __('AI tools and repos are not a secrets manager.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: use variable names only. Fail: real OPENAI_API_KEY or Stripe secret in chat.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to scan diffs and public assets for credential-like strings without printing secrets.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Confirm client-visible code cannot read server-only environment variables.', 'kingy-ai-launch-intelligence'), 'why' => __('Browser bundles, Custom HTML blocks, and front-end scripts are public.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: secret calls happen server-side. Fail: private key in JavaScript or page source.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to trace each external API call and mark browser-visible values.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Data & Privacy', 'kingy-ai-launch-intelligence'), 'why' => __('AI-built forms and tools often collect more data than the job requires.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Use the minimum data needed for the workflow.', 'kingy-ai-launch-intelligence'), 'why' => __('Less collection means less breach, policy, and compliance exposure.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: email and project type for a lead magnet. Fail: unnecessary address, birthday, or client records.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to list every field collected, where it goes, and whether it is necessary.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Check privacy, consent, retention, and third-party sharing against actual behavior.', 'kingy-ai-launch-intelligence'), 'why' => __('A page is risky when policy promises and tracking behavior disagree.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: forms, pixels, analytics, and embeds match the privacy notice. Fail: hidden tracking or vague consent.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to compare visible disclosures with forms, scripts, embeds, and network calls.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Dependencies', 'kingy-ai-launch-intelligence'), 'why' => __('Packages and snippets can add supply-chain, licensing, speed, and maintenance risk.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Review any new package, CDN script, iframe, plugin, or embed before adding it.', 'kingy-ai-launch-intelligence'), 'why' => __('External code changes your trust boundary.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: named source, reason, version, permissions, and fallback. Fail: “install whatever works.”', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to justify each dependency and offer a no-new-dependency option.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Run available package audit, lint, and build checks after dependency changes.', 'kingy-ai-launch-intelligence'), 'why' => __('Automated checks catch known issues and broken builds before launch.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: npm audit/build/lint or platform equivalent. Fail: visual check only.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to run the repo’s standard checks and summarize risk, not just success.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Auth & Permissions', 'kingy-ai-launch-intelligence'), 'why' => __('Authentication, roles, and admin access are high-impact even when the UI change looks small.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Treat login, roles, sessions, admin screens, and account changes as high risk.', 'kingy-ai-launch-intelligence'), 'why' => __('A small permission bug can expose private data or privileged actions.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: explicit role matrix and tests. Fail: auth changes with no logged-in/logged-out checks.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to map allowed users, denied users, and required tests before editing auth.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Check server-side authorization, not just hidden buttons.', 'kingy-ai-launch-intelligence'), 'why' => __('Client-side hiding is not access control.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: protected endpoints enforce roles. Fail: admin button hidden with CSS only.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to inspect routes, handlers, nonces, capabilities, middleware, and API guards.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Payments/API Keys', 'kingy-ai-launch-intelligence'), 'why' => __('Money flows and external APIs need explicit test mode, secrets handling, and failure states.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Confirm payment and billing work uses test mode until human approval.', 'kingy-ai-launch-intelligence'), 'why' => __('Real charges, refunds, and invoices should not be test data.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: sandbox keys, test card, webhook test event. Fail: live key during development.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to identify mode, webhook path, idempotency, and test evidence.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Define API rate limits, error states, and fallback copy.', 'kingy-ai-launch-intelligence'), 'why' => __('Failures should degrade clearly instead of leaking errors or retrying forever.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: safe error message and retry plan. Fail: raw exception or blank result.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to test failed API responses and document user-visible fallback behavior.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Testing & QA', 'kingy-ai-launch-intelligence'), 'why' => __('Security review is incomplete until the actual risky paths are exercised.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Run the repo’s available lint, test, build, and browser checks.', 'kingy-ai-launch-intelligence'), 'why' => __('Checks turn confidence into evidence.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: commands and results are listed. Fail: “looks good” with no checks.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to find available checks, run them, and explain failures or skipped checks.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Test mobile, keyboard, copy buttons, forms, empty states, and error states.', 'kingy-ai-launch-intelligence'), 'why' => __('Interactive pages fail in edges, not just the happy path.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: desktop/mobile and keyboard basics verified. Fail: only one desktop click.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to verify rendered behavior and capture unresolved UX/security risks.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Deployment', 'kingy-ai-launch-intelligence'), 'why' => __('Deployments can change environment, caching, indexes, routes, and public exposure.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Use a preview deployment or staging URL for final review.', 'kingy-ai-launch-intelligence'), 'why' => __('Build output can differ from local or editor preview.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: preview URL smoke-tested. Fail: publish straight from local assumptions.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to compare local, preview, and production assumptions before release.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Check headers, redirects, indexing, caching, and public assets after deploy.', 'kingy-ai-launch-intelligence'), 'why' => __('Security and SEO failures often live in deployment settings.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: HTTPS, redirects, noindex/canonical, and asset exposure checked. Fail: deploy-only settings ignored.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to inspect deployment config and public rendered output.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Monitoring', 'kingy-ai-launch-intelligence'), 'why' => __('Some problems only appear when real visitors, crawlers, and integrations arrive.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Watch logs, analytics, forms, payments, and error tracking after launch.', 'kingy-ai-launch-intelligence'), 'why' => __('Fast detection lowers damage.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: named owner and 24-hour/7-day check rhythm. Fail: nobody watching.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to create a post-launch monitoring checklist from the changed surface.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Record known risks and unresolved follow-ups.', 'kingy-ai-launch-intelligence'), 'why' => __('Documented risk is easier to manage than remembered risk.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: critical/high/medium list with owners. Fail: risks buried in chat.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to summarize remaining risks, owners, and retest dates.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Rollback', 'kingy-ai-launch-intelligence'), 'why' => __('A rollback path turns a bad release into a controlled recovery.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Write rollback triggers before publishing.', 'kingy-ai-launch-intelligence'), 'why' => __('Teams move faster when they know when to revert instead of debating live.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: broken payment, form, auth, or indexing triggers are named. Fail: “we will figure it out.”', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to draft rollback triggers based on the changed systems.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Confirm the exact restore, revert, disable, or removal steps.', 'kingy-ai-launch-intelligence'), 'why' => __('Rollback must be executable by a tired human under pressure.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: command, dashboard path, backup, or draft restore named. Fail: vague undo plan.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to document the fastest safe undo path and who can run it.', 'kingy-ai-launch-intelligence')),
        )),
        array('title' => __('Human Approval', 'kingy-ai-launch-intelligence'), 'why' => __('AI can assist review, but people own claims, risk, privacy, money, and publishing.', 'kingy-ai-launch-intelligence'), 'items' => array(
            array('text' => __('Get human approval for secrets, privacy, auth, payments, client work, and production changes.', 'kingy-ai-launch-intelligence'), 'why' => __('High-impact decisions need accountable review.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: named approver and approval note. Fail: AI self-approves production release.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to produce an approval summary with changed files, checks, risks, and rollback.', 'kingy-ai-launch-intelligence')),
            array('text' => __('Review all public claims, links, pricing, recommendations, and screenshots.', 'kingy-ai-launch-intelligence'), 'why' => __('Security includes trust: false or stale information can harm users.', 'kingy-ai-launch-intelligence'), 'pass' => __('Pass: verified links and claims. Fail: fake testimonials, fake prices, or unsupported promises.', 'kingy-ai-launch-intelligence'), 'ask' => __('Ask Codex to flag claims it cannot verify and remove unsupported language.', 'kingy-ai-launch-intelligence')),
        )),
    );
}

function kingy_ali_security_review_templates() {
    return array(
        array('title' => __('Pre-edit inspection', 'kingy-ai-launch-intelligence'), 'text' => __("Before editing, inspect the relevant files, URLs, settings, dependencies, and recent errors for [TASK]. Summarize the current behavior, likely files, security risks, unknowns, and what you will not change. Do not edit yet.", 'kingy-ai-launch-intelligence')),
        array('title' => __('Dependency review', 'kingy-ai-launch-intelligence'), 'text' => __("Review the proposed dependency or external script: [NAME]. Explain why it is needed, safer alternatives, permissions, bundle impact, supply-chain risk, license concerns, failure states, and how to remove it if it causes trouble.", 'kingy-ai-launch-intelligence')),
        array('title' => __('Secrets audit', 'kingy-ai-launch-intelligence'), 'text' => __("Audit this change for exposed secrets without printing secret values. Check commits, diffs, browser bundles, page source, env usage, logs, screenshots, docs, and examples. Report locations and remediation steps.", 'kingy-ai-launch-intelligence')),
        array('title' => __('PR review', 'kingy-ai-launch-intelligence'), 'text' => __("Review this PR for security, privacy, auth, data handling, dependency, deployment, test coverage, and rollback risks. Lead with blockers and high-risk issues, then summarize checks passed and remaining questions.", 'kingy-ai-launch-intelligence')),
        array('title' => __('QA pass', 'kingy-ai-launch-intelligence'), 'text' => __("Run the available lint, test, build, and browser checks for this change. Verify desktop, mobile, keyboard basics, forms/buttons, copy states, error states, and console output. Summarize evidence and gaps.", 'kingy-ai-launch-intelligence')),
        array('title' => __('Rollback', 'kingy-ai-launch-intelligence'), 'text' => __("Create a rollback plan for this change. Include triggers, exact revert/restore steps, owner, affected services, cache or deployment steps, data concerns, and post-rollback checks.", 'kingy-ai-launch-intelligence')),
        array('title' => __('Human approval', 'kingy-ai-launch-intelligence'), 'text' => __("Prepare a human approval note: outcome, changed files/settings, security/privacy risks, tests run, links verified, secrets check, deployment target, rollback plan, and any claims that need manual verification.", 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_security_review_red_flags() {
    return array(
        array(__('Pasting secrets into a prompt', 'kingy-ai-launch-intelligence'), __('Use placeholder names and ask for env variable wiring without sharing values.', 'kingy-ai-launch-intelligence')),
        array(__('Editing production directly', 'kingy-ai-launch-intelligence'), __('Use a branch, preview, staging copy, backup, or copied snippet first.', 'kingy-ai-launch-intelligence')),
        array(__('“Fix security” with no scope', 'kingy-ai-launch-intelligence'), __('Name the exact system, files, risks, tests, and approval gate.', 'kingy-ai-launch-intelligence')),
        array(__('Broad rewrite for a small risk', 'kingy-ai-launch-intelligence'), __('Ask for inspection, smallest safe fix, and a rollback note.', 'kingy-ai-launch-intelligence')),
        array(__('Fake links, pricing, stats, or testimonials', 'kingy-ai-launch-intelligence'), __('Only publish verified claims or label unknowns for human review.', 'kingy-ai-launch-intelligence')),
        array(__('Unreviewed package install', 'kingy-ai-launch-intelligence'), __('Require reason, source, version, alternatives, audit, and removal path.', 'kingy-ai-launch-intelligence')),
        array(__('Auth changes without role tests', 'kingy-ai-launch-intelligence'), __('Test allowed, denied, logged-in, logged-out, and expired-session states.', 'kingy-ai-launch-intelligence')),
        array(__('No rollback plan', 'kingy-ai-launch-intelligence'), __('Write the exact restore, revert, disable, or removal path before publish.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_security_review_roles() {
    return array(
        array(__('Beginner Site Owner', 'kingy-ai-launch-intelligence'), __('Start with branch/backup, no secrets, page source check, mobile QA, and rollback note.', 'kingy-ai-launch-intelligence')),
        array(__('WordPress/No-Code Builder', 'kingy-ai-launch-intelligence'), __('Check Custom HTML, embeds, forms, plugins, roles, backups, caching, and SEO settings.', 'kingy-ai-launch-intelligence')),
        array(__('Marketer', 'kingy-ai-launch-intelligence'), __('Verify claims, links, pixels, forms, lead magnets, consent, downloads, and CRM handoff.', 'kingy-ai-launch-intelligence')),
        array(__('Founder', 'kingy-ai-launch-intelligence'), __('Focus on payments, API keys, customer data, launch monitoring, support, and rollback owner.', 'kingy-ai-launch-intelligence')),
        array(__('Developer Using AI Agents', 'kingy-ai-launch-intelligence'), __('Require inspect-first workflow, diffs, tests, dependency review, auth checks, and PR approval.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_security_review_faqs() {
    return array(
        array('question' => __('Can I trust AI-generated code after this checklist?', 'kingy-ai-launch-intelligence'), 'answer' => __('No. Treat AI output as a draft. The checklist helps you scope, inspect, test, and approve work, but humans still own security, privacy, claims, and production release decisions.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is the most important AI coding agent security rule?', 'kingy-ai-launch-intelligence'), 'answer' => __('Do not share secrets and do not let the agent edit production without a recoverable path. Use placeholders, environment variables, branches, previews, backups, and human approval.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Should beginners let Codex change authentication or payments?', 'kingy-ai-launch-intelligence'), 'answer' => __('Only with extra review. Auth, payments, API keys, private data, webhooks, and admin permissions are high-risk surfaces that need tests, logs, sandbox mode, and human approval.', 'kingy-ai-launch-intelligence')),
        array('question' => __('How is this different from a website launch checklist?', 'kingy-ai-launch-intelligence'), 'answer' => __('A launch checklist checks whether the site works. This security review focuses on AI-assisted change safety: prompts, permissions, secrets, dependencies, data handling, production risk, approval, and rollback.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I use this for client work?', 'kingy-ai-launch-intelligence'), 'answer' => __('Yes, but client projects need written scope, approval, data boundaries, backups, and a rollback path. Do not paste client secrets, private records, credentials, or confidential strategy into AI prompts.', 'kingy-ai-launch-intelligence')),
        array('question' => __('When do I need professional security help?', 'kingy-ai-launch-intelligence'), 'answer' => __('Get qualified help for regulated data, healthcare, finance, children’s data, enterprise SSO, complex permissions, payment architecture, incident response, or any launch where a breach would cause serious harm.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_security_review_default_prompt() {
    return __("/goal Safely review and improve [PROJECT / PAGE / FEATURE].\n\nContext:\n- Branch or safe workspace: [BRANCH / STAGING / BACKUP]\n- Files or URLs to inspect: [FILES / URLS]\n- Allowed changes: [ALLOWED]\n- Must not change: [FORBIDDEN]\n- Known risks: [SECRETS / AUTH / PAYMENTS / DATA / DEPENDENCIES / PRODUCTION]\n- Test commands or checks: [TESTS]\n- Done when: [DONE-WHEN]\n- Rollback path: [ROLLBACK]\n\nRules:\n1. Inspect before editing. Summarize current behavior, relevant files, unknowns, and risk level.\n2. Do not expose or print secrets, tokens, private data, client records, or credentials.\n3. Do not add packages, external scripts, auth changes, payment changes, database changes, or production changes without calling them out first.\n4. Make the smallest safe change that satisfies the goal.\n5. Run available checks and verify desktop/mobile behavior when relevant.\n6. Summarize changed files, security/privacy risks, tests run, remaining gaps, and rollback steps.", 'kingy-ai-launch-intelligence');
}

function kingy_ali_security_review_schema() {
    if (!kingy_ali_is_security_review_page()) {
        return;
    }

    $faqs = kingy_ali_security_review_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('Security Review Checklist', 'kingy-ai-launch-intelligence'),
                'description' => __('An interactive security review checklist for AI-assisted coding, Codex tasks, app launches, website changes, pre-deployment checks, and human approval.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to run a security review before using an AI coding agent', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Scope the task and inspect before editing.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Protect secrets, private data, permissions, dependencies, and payments.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Run tests, QA, deployment checks, monitoring, and rollback planning.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Get human approval before production release.', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array('@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => array('@type' => 'Answer', 'text' => $faq['answer']));
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_shortcode_security_review_checklist() {
    kingy_ali_enqueue_assets();

    $phases = kingy_ali_security_review_phases();
    $templates = kingy_ali_security_review_templates();
    $red_flags = kingy_ali_security_review_red_flags();
    $roles = kingy_ali_security_review_roles();
    $faqs = kingy_ali_security_review_faqs();
    $total = 0;
    foreach ($phases as $phase) {
        $total += count($phase['items']);
    }

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-security-guide" data-kingy-security-review-guide>
        <header class="kingy-ali-academy-hero kingy-ali-security-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive AI safety tool', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Security Review Checklist', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Review secrets, permissions, dependencies, data, auth, payments, testing, deployment, monitoring, rollback, and human approval before asking Codex or another AI coding agent to change real projects.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#security-review-profiler"><?php esc_html_e('Start Review', 'kingy-ai-launch-intelligence'); ?></a>
                    <button type="button" data-security-copy-default><?php esc_html_e('Copy Safe Prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-security-print><?php esc_html_e('Download/Print Checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
            <aside class="kingy-ali-decision-card kingy-ali-security-progress-card" aria-label="<?php esc_attr_e('Security review progress', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Review progress', 'kingy-ai-launch-intelligence'); ?></h2>
                <strong><span data-security-count>0</span> / <?php echo esc_html((string) $total); ?></strong>
                <span data-security-status><?php esc_html_e('Start with scope, secrets, and rollback.', 'kingy-ai-launch-intelligence'); ?></span>
                <progress max="<?php echo esc_attr((string) $total); ?>" value="0" data-security-progress></progress>
                <p><?php esc_html_e('Progress saves in this browser. Export Markdown when you need an approval record.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Security review sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#security-review-profiler"><?php esc_html_e('Risk profiler', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#security-review-checklist"><?php esc_html_e('Checklist', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#security-review-prompt"><?php esc_html_e('Prompt builder', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#security-review-red-flags"><?php esc_html_e('Red flags', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#security-review-templates"><?php esc_html_e('Templates', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#security-review-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section class="kingy-ali-academy-section kingy-ali-answer-block">
            <p class="kingy-ali-kicker"><?php esc_html_e('Short answer', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('What should an AI coding security review include?', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('A useful security review checklist covers prompt scope, inspect-first workflow, secrets, environment variables, data collection, privacy, dependencies, permissions, auth, payments, API keys, tests, deployment, monitoring, rollback, and human approval. It should produce evidence, not just confidence.', 'kingy-ai-launch-intelligence'); ?></p>
        </section>

        <section id="security-review-profiler" class="kingy-ali-academy-section kingy-ali-security-profiler">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Risk profiler', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Choose the review depth before Codex edits anything', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-security-risk-grid" role="group" aria-label="<?php esc_attr_e('Security risk factors', 'kingy-ai-launch-intelligence'); ?>">
                <?php
                $risks = array(
                    array('label' => __('Content-only or static page', 'kingy-ai-launch-intelligence'), 'points' => 1),
                    array('label' => __('Production site or live customer path', 'kingy-ai-launch-intelligence'), 'points' => 4),
                    array('label' => __('Collects user data or form submissions', 'kingy-ai-launch-intelligence'), 'points' => 3),
                    array('label' => __('Uses auth, roles, sessions, or admin actions', 'kingy-ai-launch-intelligence'), 'points' => 4),
                    array('label' => __('Touches payments, billing, webhooks, or pricing', 'kingy-ai-launch-intelligence'), 'points' => 5),
                    array('label' => __('Uses API keys, external APIs, or private integrations', 'kingy-ai-launch-intelligence'), 'points' => 4),
                    array('label' => __('Adds packages, plugins, scripts, embeds, or iframes', 'kingy-ai-launch-intelligence'), 'points' => 3),
                    array('label' => __('Lets an AI agent edit files or run commands', 'kingy-ai-launch-intelligence'), 'points' => 3),
                );
                foreach ($risks as $index => $risk) :
                    $risk_id = 'security-risk-' . absint($index);
                    ?>
                    <label for="<?php echo esc_attr($risk_id); ?>" class="kingy-ali-security-risk-option">
                        <input id="<?php echo esc_attr($risk_id); ?>" type="checkbox" data-security-risk data-risk-points="<?php echo esc_attr((string) $risk['points']); ?>">
                        <span><?php echo esc_html($risk['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result kingy-ali-security-risk-result" data-security-risk-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Risk readout', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Select the surfaces this task touches', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('The deeper review is for work involving secrets, users, payments, auth, production, dependencies, or agent permissions.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-codex-actions">
                <button type="button" data-security-clear-risk><?php esc_html_e('Clear risk choices', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="security-review-checklist" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Guided checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Run the review by phase', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Each item includes the why, a pass/fail example, and a direct way to ask Codex for evidence.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-security-checklist" data-security-checklist>
                <?php foreach ($phases as $phase_index => $phase) : ?>
                    <details open data-security-category>
                        <summary class="kingy-ali-security-category-head">
                            <div>
                                <h3><?php echo esc_html($phase['title']); ?></h3>
                                <p><?php echo esc_html($phase['why']); ?></p>
                            </div>
                            <div class="kingy-ali-security-category-score">
                                <strong><span data-security-category-count>0</span> / <?php echo esc_html((string) count($phase['items'])); ?></strong>
                                <progress max="<?php echo esc_attr((string) count($phase['items'])); ?>" value="0" data-security-category-progress></progress>
                            </div>
                        </summary>
                        <?php foreach ($phase['items'] as $item_index => $item) : ?>
                            <?php $check_id = 'security-review-' . absint($phase_index) . '-' . absint($item_index); ?>
                            <label class="kingy-ali-security-item" for="<?php echo esc_attr($check_id); ?>" data-security-item data-category-title="<?php echo esc_attr($phase['title']); ?>" data-item-title="<?php echo esc_attr($item['text']); ?>" data-item-why="<?php echo esc_attr($item['why']); ?>" data-item-pass="<?php echo esc_attr($item['pass']); ?>" data-item-ask="<?php echo esc_attr($item['ask']); ?>">
                                <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-security-check>
                                <span>
                                    <strong><?php echo esc_html($item['text']); ?></strong>
                                    <small><?php echo esc_html($item['why']); ?></small>
                                    <em><?php echo esc_html($item['pass']); ?></em>
                                    <code><?php echo esc_html($item['ask']); ?></code>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
                <div class="kingy-ali-codex-actions">
                    <button type="button" data-security-reset><?php esc_html_e('Reset checklist', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-security-copy-markdown><?php esc_html_e('Copy Markdown review', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" data-security-print><?php esc_html_e('Print checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="security-review-prompt" class="kingy-ali-academy-section kingy-ali-security-prompt-tool">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Safe Codex prompt builder', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Turn the review into a scoped task prompt', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-security-prompt-grid">
                <form data-security-prompt-form>
                    <?php
                    $fields = array(
                        'project' => __('Project or feature', 'kingy-ai-launch-intelligence'),
                        'branch' => __('Branch, staging, backup, or preview', 'kingy-ai-launch-intelligence'),
                        'files' => __('Files or URLs to inspect', 'kingy-ai-launch-intelligence'),
                        'allowed' => __('Allowed changes', 'kingy-ai-launch-intelligence'),
                        'forbidden' => __('Forbidden changes', 'kingy-ai-launch-intelligence'),
                        'risks' => __('Known risks', 'kingy-ai-launch-intelligence'),
                        'tests' => __('Test commands or QA checks', 'kingy-ai-launch-intelligence'),
                        'done' => __('Done-when criteria', 'kingy-ai-launch-intelligence'),
                        'rollback' => __('Rollback plan', 'kingy-ai-launch-intelligence'),
                    );
                    foreach ($fields as $key => $label) :
                        ?>
                        <label>
                            <span><?php echo esc_html($label); ?></span>
                            <textarea rows="2" data-security-prompt-field="<?php echo esc_attr($key); ?>"></textarea>
                        </label>
                    <?php endforeach; ?>
                    <div class="kingy-ali-codex-actions">
                        <button type="button" data-security-generate-prompt><?php esc_html_e('Generate prompt', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="button" data-security-reset-prompt><?php esc_html_e('Clear fields', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-security-prompt-output">
                    <label for="security-review-generated-prompt"><?php esc_html_e('Generated safe task prompt', 'kingy-ai-launch-intelligence'); ?></label>
                    <textarea id="security-review-generated-prompt" rows="18" data-security-prompt-output><?php echo esc_textarea(kingy_ali_security_review_default_prompt()); ?></textarea>
                    <button type="button" data-security-copy-output="#security-review-generated-prompt"><?php esc_html_e('Copy generated prompt', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="security-review-red-flags" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Red flags', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Unsafe requests and safer rewrites', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-comparison-table-wrap">
                <table class="kingy-ali-comparison-table">
                    <thead><tr><th><?php esc_html_e('Red flag', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Safer move', 'kingy-ai-launch-intelligence'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($red_flags as $flag) : ?>
                            <tr><td><?php echo esc_html($flag[0]); ?></td><td><?php echo esc_html($flag[1]); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Role paths', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Use the right checklist lens for your job', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($roles as $role) : ?>
                    <div class="kingy-ali-link-panel"><h3><?php echo esc_html($role[0]); ?></h3><p><?php echo esc_html($role[1]); ?></p></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="security-review-templates" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Copyable templates', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Mini-prompts for safer AI review', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-security-template-grid">
                <?php foreach ($templates as $index => $template) : ?>
                    <?php $template_id = 'security-template-' . absint($index); ?>
                    <div class="kingy-ali-security-template">
                        <h3><?php echo esc_html($template['title']); ?></h3>
                        <pre id="<?php echo esc_attr($template_id); ?>"><?php echo esc_html($template['text']); ?></pre>
                        <button type="button" data-security-copy-text="#<?php echo esc_attr($template_id); ?>"><?php esc_html_e('Copy template', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('What good looks like before you ship', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-spec-grid">
                <div><h3><?php esc_html_e('Safe request', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Inspect the lead magnet page and related form code. Do not edit yet. Tell me if any secrets, privacy mismatches, broken mobile states, fake claims, or rollback gaps exist.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Risky request rewritten', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Instead of “fix auth and deploy,” ask for an auth inspection, role matrix, test plan, branch-only fix, and human approval summary before production.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><h3><?php esc_html_e('Filled-out review sample', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Branch: security-review. Files: form template, env docs, analytics script. Must not change: pricing or auth. Tests: lint, form submit, mobile, console. Rollback: revert commit and restore previous form block.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Printable version', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Compact approval checklist', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-security-printable">
                <ol>
                    <li><?php esc_html_e('Safe workspace, branch, backup, or preview exists.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Scope, allowed changes, forbidden changes, and done-when criteria are written.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('No secrets, private data, credentials, or unsupported claims are in prompts or commits.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Dependencies, auth, payments, data, and API surfaces are reviewed if touched.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Tests, QA, deployment checks, monitoring, rollback, and human approval are documented.', 'kingy-ai-launch-intelligence'); ?></li>
                </ol>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Resources', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Keep security review connected to the build workflow', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/')); ?>"><strong><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Return to the course hub.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn review notes into a scoped task.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/build-ai-academy-toolkit/')); ?>"><strong><?php esc_html_e('Academy Toolkit', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Find supporting checklists and worksheets.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/ship-ai-built-projects/')); ?>"><strong><?php esc_html_e('Shipping Path', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use previews, QA notes, and rollback plans.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/')); ?>"><strong><?php esc_html_e('AI App Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Scope the next beginner app.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/project-library/')); ?>"><strong><?php esc_html_e('Project Library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Choose a safer next build.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="security-review-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Security review checklist questions', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details><summary><?php echo esc_html($faq['question']); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}
