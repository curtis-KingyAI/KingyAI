<?php

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_shortcode_replit_beginner_guide() {
    kingy_ali_enqueue_assets();

    $workspace_cards = kingy_ali_replit_workspace_cards();
    $projects = kingy_ali_replit_first_projects();
    $prompts = kingy_ali_replit_prompts();
    $debug_steps = kingy_ali_replit_debug_steps();
    $qa_checks = kingy_ali_replit_qa_checks();
    $resources = kingy_ali_replit_resources();
    $faqs = kingy_ali_replit_beginner_faqs();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-vibe-guide kingy-ali-replit-guide" data-kingy-vibe-guide data-kingy-replit-guide>
        <header class="kingy-ali-academy-hero kingy-ali-vibe-hero kingy-ali-replit-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Replit beginner guide', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Replit for Beginners: Build Your First AI App Without Getting Lost', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('A practical, copy-ready guide for turning one small idea into a working Replit app, understanding the files enough to edit safely, debugging the first errors, and publishing only after the important checks pass.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#replit-first-project" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Pick first project', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="replit_hero"><?php esc_html_e('Pick a First Project', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#replit-prompts" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Copy Replit prompts', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="replit_hero"><?php esc_html_e('Copy Prompts', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#replit-qa" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Run Replit QA', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="replit_hero"><?php esc_html_e('Run Launch QA', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card kingy-ali-replit-decision" aria-label="<?php esc_attr_e('Quick Replit recommendation', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use Replit when you want AI help plus a visible code workspace: files, preview, logs, packages, environment variables, and deployment checks in one browser tab.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul>
                    <li><?php esc_html_e('Best first app: one page, one form, one useful output.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best learning habit: ask what changed before asking for more features.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best safety rule: never paste API keys into code; use Secrets.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Replit beginner guide sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#replit-basics"><?php esc_html_e('Basics', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-workspace"><?php esc_html_e('Workspace', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-first-project"><?php esc_html_e('First App', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-prompts"><?php esc_html_e('Prompts', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-debugging"><?php esc_html_e('Debugging', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-secrets-deploy"><?php esc_html_e('Secrets', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-qa"><?php esc_html_e('QA', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#replit-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="replit-basics" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Beginner map', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('What Replit is actually good for', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Replit is strongest when a beginner wants to learn by building. It keeps the app running in the browser while still showing enough of the real project to understand files, errors, data, and deployment. That makes it more educational than a pure visual builder, but also more demanding.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-practical-grid kingy-ali-replit-fit-grid">
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Choose Replit when', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('You want to build simple web apps, scripts, APIs, AI demos, dashboards, learning projects, or prototypes you can inspect and later hand off as code.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Avoid Replit when', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('You only want drag-and-drop screens, a complex production app with no technical owner, or a workflow that already belongs in a no-code database tool.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Beginner success metric', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('After the first session, you should be able to explain the main file, how to run the app, where errors appear, what data is stored, and what still needs review.', 'kingy-ai-launch-intelligence'); ?></p></article>
            </div>
        </section>

        <section id="replit-workspace" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Workspace tour', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Know these parts before you ask AI for more features', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('You do not need to become a professional developer on day one. You do need to know where the app runs, where errors show up, where private keys belong, and which files AI changed.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-replit-workspace-grid">
                <?php foreach ($workspace_cards as $card) : ?>
                    <article class="kingy-ali-replit-workspace-card">
                        <h3><?php echo esc_html($card['title']); ?></h3>
                        <p><?php echo esc_html($card['body']); ?></p>
                        <p><strong><?php esc_html_e('Beginner move:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($card['move']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="replit-first-project" class="kingy-ali-builder-chooser kingy-ali-replit-projects">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('First app picker', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Start with a tiny app that teaches the whole loop', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A great first Replit project is not impressive because it has many features. It is impressive because it runs, can be tested, and teaches you how code, preview, logs, data, and prompts fit together.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-replit-project-grid">
                <?php foreach ($projects as $project) : ?>
                    <article class="kingy-ali-replit-project-card">
                        <p class="kingy-ali-kicker"><?php echo esc_html($project['level']); ?></p>
                        <h3><?php echo esc_html($project['title']); ?></h3>
                        <p><?php echo esc_html($project['best']); ?></p>
                        <dl>
                            <div><dt><?php esc_html_e('Version-one scope', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($project['scope']); ?></dd></div>
                            <div><dt><?php esc_html_e('Do not add yet', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($project['avoid']); ?></dd></div>
                            <div><dt><?php esc_html_e('Test', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($project['test']); ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="replit-prompts" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Copy-ready prompts', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Prompts that keep Replit builds readable and testable', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The prompts below are written to avoid the common beginner problem: asking for a whole startup before the first button works. Copy one, replace the brackets, and keep each request narrow.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-vibe-prompt-grid kingy-ali-replit-prompt-grid">
                <?php foreach ($prompts as $index => $prompt) : ?>
                    <?php $prompt_id = 'kingy-replit-prompt-' . absint($index); ?>
                    <article class="kingy-ali-copy-prompt kingy-ali-vibe-prompt-card kingy-ali-replit-prompt-card">
                        <div><p class="kingy-ali-kicker"><?php echo esc_html($prompt['stage']); ?></p><h3><?php echo esc_html($prompt['title']); ?></h3></div>
                        <pre><code id="<?php echo esc_attr($prompt_id); ?>"><?php echo esc_html($prompt['text']); ?></code></pre>
                        <button type="button" data-vibe-copy-target="#<?php echo esc_attr($prompt_id); ?>"><?php esc_html_e('Copy Prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="replit-debugging" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Debugging loop', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('When the app breaks, slow down and fix one thing', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Beginner debugging is mostly evidence collection. Capture what you clicked, what you expected, what happened, and the exact error from the log or browser console. Then ask for the smallest fix.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-vibe-workflow kingy-ali-replit-debug-flow">
                <?php foreach ($debug_steps as $index => $step) : ?>
                    <article class="kingy-ali-agent-step"><span><?php echo esc_html(absint($index) + 1); ?></span><h3><?php echo esc_html($step['title']); ?></h3><p><?php echo esc_html($step['body']); ?></p></article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="replit-secrets-deploy" class="kingy-ali-test-project kingy-ali-replit-safety">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Secrets and publishing', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Treat private keys and deployment as real product work', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Replit is beginner-friendly, but an app that uses APIs, accounts, saved data, or customer inputs still needs security and publishing discipline. Use Secrets for API keys and tokens, confirm the deployment type fits the app, and retest the live version after publishing.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-replit-safety-grid">
                <article>
                    <h3><?php esc_html_e('Secrets rule', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Never paste API keys, model keys, database URLs, passwords, or tokens into visible code. Store them as environment variables through Replit Secrets, then access them from the app code.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e('Data rule', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Use sample data until you know where records are stored, how they are backed up, who can access them, and what happens when the app is redeployed.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article>
                    <h3><?php esc_html_e('Deployment rule', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Verify current Replit deployment options, plan limits, environment variables, run commands, custom domain needs, and whether your live app behaves differently from the preview.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
            </div>
        </section>

        <section id="replit-qa" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Launch checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Do not publish until the boring checks pass', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('This checklist is designed for a beginner Replit prototype. It catches the mistakes that make an AI-built app look done while still being fragile.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-codex-checklist kingy-ali-vibe-checklist kingy-ali-replit-checklist" data-vibe-checklist="replit-qa">
                <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                    <strong><span data-vibe-check-count>0</span> / <?php echo esc_html(count($qa_checks)); ?></strong>
                    <span data-vibe-check-status><?php esc_html_e('Needs review', 'kingy-ai-launch-intelligence'); ?></span>
                    <progress max="<?php echo esc_attr(count($qa_checks)); ?>" value="0" data-vibe-check-progress></progress>
                </div>
                <div class="kingy-ali-vibe-check-grid">
                    <?php foreach ($qa_checks as $index => $check) : ?>
                        <?php $check_id = 'kingy-replit-qa-' . absint($index); ?>
                        <label for="<?php echo esc_attr($check_id); ?>">
                            <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-vibe-check>
                            <span><strong><?php echo esc_html($check['title']); ?></strong><?php echo esc_html($check['body']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="kingy-ali-codex-secondary" data-vibe-check-reset><?php esc_html_e('Reset Checklist', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="replit-resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Next resources', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep learning from the right source', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($resources as $resource) : ?>
                    <a class="kingy-ali-codex-resource" href="<?php echo esc_url($resource['url']); ?>" <?php echo !empty($resource['external']) ? 'rel="nofollow noopener" target="_blank"' : ''; ?> data-kingy-ali-track="clicked_replit_resource" data-event-label="<?php echo esc_attr($resource['label']); ?>" data-event-surface="replit_resources">
                        <strong><?php echo esc_html($resource['label']); ?></strong>
                        <span><?php echo esc_html($resource['description']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="replit-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Beginner questions about Replit AI apps', 'kingy-ai-launch-intelligence'); ?></h2>
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

function kingy_ali_replit_workspace_cards() {
    return array(
        array('title' => __('Files', 'kingy-ai-launch-intelligence'), 'body' => __('The project files are the app. Beginners should identify the main page, server file, styles, package file, and any data files before making big changes.', 'kingy-ai-launch-intelligence'), 'move' => __('Ask AI to summarize the file tree and name the three files most likely to change.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Preview', 'kingy-ai-launch-intelligence'), 'body' => __('The preview shows the running app, but it is not proof that every path works. Click the actual buttons, submit forms, refresh, and test mobile width.', 'kingy-ai-launch-intelligence'), 'move' => __('After each change, test the user path before asking for another feature.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Console and logs', 'kingy-ai-launch-intelligence'), 'body' => __('Errors tell you where to look. Copy exact error text, not a vague summary, when asking AI to debug.', 'kingy-ai-launch-intelligence'), 'move' => __('Paste the error, what you clicked, expected behavior, and actual behavior.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Run command', 'kingy-ai-launch-intelligence'), 'body' => __('The app needs a clear way to start. If the Run button fails, the first task is setup, not redesign.', 'kingy-ai-launch-intelligence'), 'move' => __('Ask AI to inspect the run command and dependencies before editing features.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Packages', 'kingy-ai-launch-intelligence'), 'body' => __('Packages are external code your app depends on. Adding too many packages makes beginner projects harder to maintain.', 'kingy-ai-launch-intelligence'), 'move' => __('Ask whether a feature can be done with the existing stack before installing more.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Secrets', 'kingy-ai-launch-intelligence'), 'body' => __('API keys, database URLs, tokens, and passwords belong in Secrets or environment variables, not visible source files.', 'kingy-ai-launch-intelligence'), 'move' => __('Before adding an API, ask AI which environment variables the app needs.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Database or storage', 'kingy-ai-launch-intelligence'), 'body' => __('Saved records create responsibility. A prototype can often start with sample data or local-only storage before a real database.', 'kingy-ai-launch-intelligence'), 'move' => __('Ask where each record is stored and what happens on refresh, restart, and deploy.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Deployment', 'kingy-ai-launch-intelligence'), 'body' => __('A published app can behave differently from preview because of build commands, secrets, storage, domains, and plan limits.', 'kingy-ai-launch-intelligence'), 'move' => __('Retest the live URL separately and keep rollback notes before sharing widely.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_replit_first_projects() {
    return array(
        array('level' => __('Fastest win', 'kingy-ai-launch-intelligence'), 'title' => __('Study Quiz Generator', 'kingy-ai-launch-intelligence'), 'best' => __('A learner enters a topic and gets five review questions with answers.', 'kingy-ai-launch-intelligence'), 'scope' => __('One input, generated or rule-based questions, answer reveal, reset button, and mobile layout.', 'kingy-ai-launch-intelligence'), 'avoid' => __('Accounts, grade books, payments, file uploads, or student data.', 'kingy-ai-launch-intelligence'), 'test' => __('Blank topic, very long topic, answer reveal, reset, refresh, and mobile view.', 'kingy-ai-launch-intelligence')),
        array('level' => __('Best business starter', 'kingy-ai-launch-intelligence'), 'title' => __('Lead Intake Helper', 'kingy-ai-launch-intelligence'), 'best' => __('A visitor describes a need and receives a clean summary the business owner can copy.', 'kingy-ai-launch-intelligence'), 'scope' => __('A short form, validation, generated summary, copy button, and no hidden submission until consent is reviewed.', 'kingy-ai-launch-intelligence'), 'avoid' => __('Real CRM sync, sensitive data, unsupported promises, or automatic emails.', 'kingy-ai-launch-intelligence'), 'test' => __('Required fields, privacy copy, long notes, copy output, and what happens to submitted data.', 'kingy-ai-launch-intelligence')),
        array('level' => __('Best code learning', 'kingy-ai-launch-intelligence'), 'title' => __('Idea Tracker Dashboard', 'kingy-ai-launch-intelligence'), 'best' => __('A simple board where you add ideas, choose status, edit notes, and copy a weekly summary.', 'kingy-ai-launch-intelligence'), 'scope' => __('Sample records, add/edit/status flow, list view, empty state, and manual export.', 'kingy-ai-launch-intelligence'), 'avoid' => __('Multi-user auth, team permissions, notifications, or production customer records.', 'kingy-ai-launch-intelligence'), 'test' => __('Add item, edit status, delete or archive, refresh persistence, empty state, and export text.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_replit_prompts() {
    return array(
        array('stage' => __('Plan', 'kingy-ai-launch-intelligence'), 'title' => __('Turn my idea into a Replit MVP', 'kingy-ai-launch-intelligence'), 'text' => __("I am a beginner using Replit. Turn this idea into the smallest safe MVP: [APP IDEA].\n\nReturn:\n1. The user and one job the app does.\n2. Version-one scope with one page, one input flow, and one output.\n3. Files or app parts I should expect.\n4. Data storage recommendation for prototype vs real launch.\n5. What not to add yet.\n6. A Replit build prompt.\n7. A QA checklist I can run in preview and after deployment.", 'kingy-ai-launch-intelligence')),
        array('stage' => __('Build', 'kingy-ai-launch-intelligence'), 'title' => __('Ask Replit Agent for the first working version', 'kingy-ai-launch-intelligence'), 'text' => __("Build the smallest working Replit app for: [APP IDEA].\n\nRules:\n- Keep it beginner-friendly and explain the file structure after you build.\n- Use one page, clear labels, readable typography, and mobile-friendly layout.\n- Include only the minimum data needed for the prototype.\n- Do not add login, payments, file uploads, external APIs, or a database unless required for this exact MVP.\n- After building, tell me how to run it, what files changed, and what to test first.", 'kingy-ai-launch-intelligence')),
        array('stage' => __('Improve', 'kingy-ai-launch-intelligence'), 'title' => __('Add one feature safely', 'kingy-ai-launch-intelligence'), 'text' => __("Inspect the current Replit project first. Add only this feature: [FEATURE].\n\nBefore changing code, identify the files likely to change and the risk.\nAfter changing code, summarize exactly what changed, how to test it, and what behavior did not change.\nKeep the UI readable, accessible, and consistent with the current app.", 'kingy-ai-launch-intelligence')),
        array('stage' => __('Debug', 'kingy-ai-launch-intelligence'), 'title' => __('Fix one Replit error', 'kingy-ai-launch-intelligence'), 'text' => __("My Replit app is not working.\n\nExpected behavior: [EXPECTED]\nActual behavior: [ACTUAL]\nSteps to reproduce: [STEPS]\nExact error or log text: [ERROR]\nRecent change: [WHAT CHANGED]\n\nFind the likely cause, make the smallest fix, avoid unrelated rewrites, and tell me exactly what to retest.", 'kingy-ai-launch-intelligence')),
        array('stage' => __('Secrets', 'kingy-ai-launch-intelligence'), 'title' => __('Move private keys out of code', 'kingy-ai-launch-intelligence'), 'text' => __("Audit this Replit project for secrets and environment variables.\n\nFind any API keys, tokens, passwords, database URLs, model keys, or provider credentials that are hard-coded or likely to be exposed.\nExplain which values should move to Replit Secrets, what variable names the code expects, and how I can verify the app still works after moving them.\nDo not print secret values back to me.", 'kingy-ai-launch-intelligence')),
        array('stage' => __('Deploy', 'kingy-ai-launch-intelligence'), 'title' => __('Pre-publish Replit check', 'kingy-ai-launch-intelligence'), 'text' => __("Run a deployment readiness review for this Replit app.\n\nCheck:\n- Start/build commands.\n- Required Secrets and environment variables.\n- Data storage and refresh behavior.\n- Mobile layout.\n- Broken links and buttons.\n- Console or server errors.\n- Privacy and unsupported claims.\n- Differences between preview and the live deployment.\n\nReturn a pass/fail list, fixes required before sharing, and rollback notes.", 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_replit_debug_steps() {
    return array(
        array('title' => __('Reproduce it', 'kingy-ai-launch-intelligence'), 'body' => __('Write the exact clicks or inputs that cause the problem. If you cannot repeat it, AI cannot reliably fix it.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Copy the evidence', 'kingy-ai-launch-intelligence'), 'body' => __('Use the exact error message from the log, console, preview, or deployment page. Screenshots help, but text is easier to debug.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Name the expectation', 'kingy-ai-launch-intelligence'), 'body' => __('Tell AI what should have happened. Many bugs are unclear because the desired behavior was never stated.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Fix the smallest cause', 'kingy-ai-launch-intelligence'), 'body' => __('Ask for the narrowest fix, not a rewrite. Rewrites can hide the original bug and create three new ones.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Retest the path', 'kingy-ai-launch-intelligence'), 'body' => __('After the fix, run the original failing steps, a normal path, an empty input, and a mobile check.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Record the lesson', 'kingy-ai-launch-intelligence'), 'body' => __('Ask what caused the bug in plain English so the next prompt can avoid the same pattern.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_replit_qa_checks() {
    return array(
        array('title' => __('The app runs from a clean start. ', 'kingy-ai-launch-intelligence'), 'body' => __('Stop and restart the app, then confirm the preview still loads without manual fixes.', 'kingy-ai-launch-intelligence')),
        array('title' => __('The main user path works. ', 'kingy-ai-launch-intelligence'), 'body' => __('Complete the primary action from blank page to useful result.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Bad inputs are handled. ', 'kingy-ai-launch-intelligence'), 'body' => __('Try blank fields, long text, weird characters, huge numbers, and repeated clicks.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Text is readable. ', 'kingy-ai-launch-intelligence'), 'body' => __('Check contrast, prompt boxes, buttons, labels, long words, and mobile wrapping.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Errors are visible and useful. ', 'kingy-ai-launch-intelligence'), 'body' => __('Users should get plain-language feedback instead of a silent failure.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Secrets are not in code. ', 'kingy-ai-launch-intelligence'), 'body' => __('API keys, tokens, database URLs, and provider credentials are stored as environment variables.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Data behavior is understood. ', 'kingy-ai-launch-intelligence'), 'body' => __('You know what is saved, where it is saved, what resets, and what happens after deploy.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Deployment matches preview. ', 'kingy-ai-launch-intelligence'), 'body' => __('The live URL has been tested separately from the Replit preview.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Claims are honest. ', 'kingy-ai-launch-intelligence'), 'body' => __('The app does not promise guaranteed results, legal/medical/financial advice, or unverified AI accuracy.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Rollback is possible. ', 'kingy-ai-launch-intelligence'), 'body' => __('You know the last working state, recent changes, and what to undo if publishing breaks.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_replit_resources() {
    return array(
        array('label' => __('Official Replit Docs', 'kingy-ai-launch-intelligence'), 'description' => __('Verify current product, workspace, deployment, and billing details before publishing.', 'kingy-ai-launch-intelligence'), 'url' => 'https://docs.replit.com/', 'external' => true),
        array('label' => __('Replit Agent Docs', 'kingy-ai-launch-intelligence'), 'description' => __('Use plain-language app-building help, then inspect the plan and code before trusting it.', 'kingy-ai-launch-intelligence'), 'url' => 'https://docs.replit.com/core-concepts/agent/', 'external' => true),
        array('label' => __('Replit Secrets Docs', 'kingy-ai-launch-intelligence'), 'description' => __('Store API keys, tokens, and database connection strings as encrypted environment variables.', 'kingy-ai-launch-intelligence'), 'url' => 'https://docs.replit.com/replit-workspace/workspace-features/secrets', 'external' => true),
        array('label' => __('Replit Deployments Docs', 'kingy-ai-launch-intelligence'), 'description' => __('Review deployment types and requirements before sharing a live app.', 'kingy-ai-launch-intelligence'), 'url' => 'https://docs.replit.com/cloud-services/deployments/about-deployments', 'external' => true),
        array('label' => __('Vibe Coding for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Choose the right AI app builder path before committing to one tool.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/vibe-coding-for-beginners-ai-app-builder/')),
        array('label' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'), 'description' => __('Turn a rough build request into a scoped implementation brief.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')),
    );
}

function kingy_ali_replit_beginner_faqs() {
    return array(
        array('question' => __('Is Replit good for beginners?', 'kingy-ai-launch-intelligence'), 'answer' => __('Yes, especially for beginners who want to learn how an app works while building it. It is less abstract than pure no-code tools because files, logs, packages, and deployment details stay visible.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What should I build first in Replit?', 'kingy-ai-launch-intelligence'), 'answer' => __('Start with a study quiz, lead intake helper, idea tracker, calculator, or simple generator. The first app should have one page, one input flow, one output, and a short QA checklist.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Should I use Replit Agent for the first build?', 'kingy-ai-launch-intelligence'), 'answer' => __('Replit Agent can be useful for turning plain language into a working app, but beginners should still ask for a plan, inspect changed files, run the app, read errors, and test behavior before adding features.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I build an AI app in Replit without coding?', 'kingy-ai-launch-intelligence'), 'answer' => __('You can get far with plain-language prompts, but the best beginner outcome is learning enough to understand the main files, run command, logs, data handling, and deployment checks.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Where do API keys go in Replit?', 'kingy-ai-launch-intelligence'), 'answer' => __('API keys, tokens, database URLs, and other private values should go in Replit Secrets or environment variables, not inside visible source code.', 'kingy-ai-launch-intelligence')),
        array('question' => __('When is a Replit prototype ready to publish?', 'kingy-ai-launch-intelligence'), 'answer' => __('Publish only after the main path works, mobile layout is readable, bad inputs are handled, secrets are protected, data behavior is understood, the live deployment is tested, and rollback notes are clear.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Is Replit better than Lovable, Bolt, Bubble, or Softr?', 'kingy-ai-launch-intelligence'), 'answer' => __('Replit is usually better when learning and code ownership matter. Lovable and Bolt are often faster for visual MVPs, Bubble is stronger for no-code workflows, and Softr is strong for portals over structured data.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is the biggest Replit beginner mistake?', 'kingy-ai-launch-intelligence'), 'answer' => __('The biggest mistake is adding features before the first version is understood and tested. Build one narrow path, confirm it works, then add one feature at a time.', 'kingy-ai-launch-intelligence')),
    );
}
