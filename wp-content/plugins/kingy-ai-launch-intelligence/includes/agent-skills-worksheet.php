<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_agent_skills_worksheet', 'kingy_ali_shortcode_agent_skills_worksheet');
add_filter('the_content', 'kingy_ali_maybe_replace_agent_skills_worksheet', 20);
add_filter('wpseo_title', 'kingy_ali_agent_skills_worksheet_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_agent_skills_worksheet_seo_description');
add_filter('document_title_parts', 'kingy_ali_agent_skills_worksheet_document_title');
add_action('wp_head', 'kingy_ali_agent_skills_worksheet_schema');

function kingy_ali_is_agent_skills_worksheet_page() {
    return kingy_ali_is_rendering_page_slug('24-agent-skills-planning-worksheet');
}

function kingy_ali_maybe_replace_agent_skills_worksheet($content) {
    if (!kingy_ali_is_agent_skills_worksheet_page()) {
        return $content;
    }

    return kingy_ali_shortcode_agent_skills_worksheet();
}

function kingy_ali_agent_skills_worksheet_seo_title($title) {
    if (!kingy_ali_is_agent_skills_worksheet_page()) {
        return $title;
    }

    return __('Agent Skills Planning Worksheet: Interactive Skill Brief Builder', 'kingy-ai-launch-intelligence');
}

function kingy_ali_agent_skills_worksheet_seo_description($description) {
    if (!kingy_ali_is_agent_skills_worksheet_page()) {
        return $description;
    }

    return __('Plan an AI agent skill with an interactive worksheet, Codex goal prompt, SKILL.md outline, review checklist, Markdown and JSON downloads, QA gates, and rollback notes.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_agent_skills_worksheet_document_title($parts) {
    if (kingy_ali_is_agent_skills_worksheet_page()) {
        $parts['title'] = __('Agent Skills Planning Worksheet', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_agent_skills_worksheet_fields() {
    return array(
        array('key' => 'skill_name', 'label' => __('Skill name', 'kingy-ai-launch-intelligence'), 'type' => 'text', 'placeholder' => __('WordPress launch QA skill', 'kingy-ai-launch-intelligence'), 'step' => __('Skill identity', 'kingy-ai-launch-intelligence')),
        array('key' => 'audience', 'label' => __('Who will use it?', 'kingy-ai-launch-intelligence'), 'type' => 'text', 'placeholder' => __('Beginner site owners using Codex on WordPress pages', 'kingy-ai-launch-intelligence'), 'step' => __('Skill identity', 'kingy-ai-launch-intelligence')),
        array('key' => 'trigger', 'label' => __('When should the agent use this skill?', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Use this when the user asks to QA a launch page, improve a thin worksheet, or publish a WordPress guide safely.', 'kingy-ai-launch-intelligence'), 'step' => __('Activation', 'kingy-ai-launch-intelligence')),
        array('key' => 'purpose', 'label' => __('Purpose and outcome', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Help the agent gather context, make scoped edits, run checks, and produce a human approval note.', 'kingy-ai-launch-intelligence'), 'step' => __('Activation', 'kingy-ai-launch-intelligence')),
        array('key' => 'inputs', 'label' => __('Inputs the skill needs', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('URL, repo path, screenshots, target audience, existing page copy, constraints, test commands.', 'kingy-ai-launch-intelligence'), 'step' => __('Context', 'kingy-ai-launch-intelligence')),
        array('key' => 'context', 'label' => __('Repo, page, or product context to inspect', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Inspect plugin includes, shortcode registration, CSS/JS assets, live page source, and existing patterns first.', 'kingy-ai-launch-intelligence'), 'step' => __('Context', 'kingy-ai-launch-intelligence')),
        array('key' => 'tools', 'label' => __('Tools, files, or references the skill may use', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('rg, php -l, browser verification, WordPress REST, official docs, existing SKILL.md references.', 'kingy-ai-launch-intelligence'), 'step' => __('Workflow', 'kingy-ai-launch-intelligence')),
        array('key' => 'constraints', 'label' => __('Constraints and safety rules', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Keep edits scoped, no fake claims, protect secrets, inspect before editing, preserve public URLs.', 'kingy-ai-launch-intelligence'), 'step' => __('Workflow', 'kingy-ai-launch-intelligence')),
        array('key' => 'forbidden', 'label' => __('What the skill must not change', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Do not rename routes, remove analytics attributes, change pricing claims, expose credentials, or rewrite unrelated pages.', 'kingy-ai-launch-intelligence'), 'step' => __('Boundaries', 'kingy-ai-launch-intelligence')),
        array('key' => 'examples', 'label' => __('Good and bad examples', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Good: inspect current shortcode then add scoped checklist. Bad: paste a giant unsourced HTML blob into production.', 'kingy-ai-launch-intelligence'), 'step' => __('Boundaries', 'kingy-ai-launch-intelligence')),
        array('key' => 'success', 'label' => __('Success criteria', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('The page is useful, interactive, accessible, downloadable, mobile-safe, and verified with clear evidence.', 'kingy-ai-launch-intelligence'), 'step' => __('Acceptance', 'kingy-ai-launch-intelligence')),
        array('key' => 'tests', 'label' => __('Tests and QA checks', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Run lint/build checks, verify copy/download/reset, test desktop/mobile, check schema and rendered markers.', 'kingy-ai-launch-intelligence'), 'step' => __('Acceptance', 'kingy-ai-launch-intelligence')),
        array('key' => 'approval', 'label' => __('Human approval gate', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Human reviews changed files, claims, links, security/privacy risk, tests, and deployment target before publish.', 'kingy-ai-launch-intelligence'), 'step' => __('Release', 'kingy-ai-launch-intelligence')),
        array('key' => 'rollback', 'label' => __('Rollback path', 'kingy-ai-launch-intelligence'), 'type' => 'textarea', 'placeholder' => __('Revert commit, restore prior page content or plugin include, purge cache, and retest the old page.', 'kingy-ai-launch-intelligence'), 'step' => __('Release', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_agent_skills_worksheet_checklist() {
    return array(
        array('title' => __('Scope and trigger', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('The skill has a narrow job and a clear activation phrase or situation.', 'kingy-ai-launch-intelligence'),
            __('The target user, workflow, and expected output are named.', 'kingy-ai-launch-intelligence'),
            __('The skill says when not to use it.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('Context and tools', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('The agent knows what files, URLs, docs, examples, or source material to inspect first.', 'kingy-ai-launch-intelligence'),
            __('Required tools are listed without exposing secrets or private credentials.', 'kingy-ai-launch-intelligence'),
            __('The workflow favors evidence before recommendations or edits.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('Boundaries and quality', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Forbidden changes, risky surfaces, and human approval gates are explicit.', 'kingy-ai-launch-intelligence'),
            __('The skill includes good and bad examples so the agent can calibrate quality.', 'kingy-ai-launch-intelligence'),
            __('The output format is specific enough to be reviewed by a human.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('Testing and release', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('The skill names tests, QA checks, acceptance criteria, and evidence to report.', 'kingy-ai-launch-intelligence'),
            __('Rollback, revert, or removal steps are documented before production use.', 'kingy-ai-launch-intelligence'),
            __('The final summary must include changed files, checks run, risks, gaps, and next actions.', 'kingy-ai-launch-intelligence'),
        )),
    );
}

function kingy_ali_agent_skills_worksheet_faqs() {
    return array(
        array('question' => __('What is an agent skill?', 'kingy-ai-launch-intelligence'), 'answer' => __('An agent skill is a reusable instruction package that tells an AI agent when to activate, what context to gather, which tools or files to use, what quality bar to meet, and how to verify the result.')),
        array('question' => __('When should I create a skill instead of a normal prompt?', 'kingy-ai-launch-intelligence'), 'answer' => __('Create a skill when the workflow repeats, has quality or safety rules, needs specific files or tools, or benefits from consistent tests and review steps. Use a normal prompt for one-off work.')),
        array('question' => __('What makes a skill useful?', 'kingy-ai-launch-intelligence'), 'answer' => __('A useful skill has a clear trigger, a narrow outcome, required context, constraints, examples, verification steps, and a human approval gate for risky work.')),
        array('question' => __('Can a beginner write a skill?', 'kingy-ai-launch-intelligence'), 'answer' => __('Yes. Start by describing the workflow in plain language, then add the examples, do-not-change rules, tests, and rollback notes that would help another person do the work safely.')),
        array('question' => __('Should a skill include secrets or private data?', 'kingy-ai-launch-intelligence'), 'answer' => __('No. Skills should reference placeholder names, environment variable names, or approved docs. Do not include real keys, tokens, passwords, client records, private URLs, or confidential material.')),
    );
}

function kingy_ali_agent_skills_worksheet_default_goal() {
    return __("/goal Create or improve an agent skill from this planning worksheet.\n\nContext:\n- Skill name: [Skill name]\n- Audience: [Who will use it]\n- Trigger: [When the agent should use it]\n- Purpose: [Outcome]\n- Inputs and context to inspect: [Inputs, files, URLs, docs]\n- Tools allowed: [Tools]\n- Constraints: [Rules]\n- Must not change: [Forbidden changes]\n- Examples: [Good and bad examples]\n- Success criteria: [Done when]\n- Tests and QA: [Checks]\n- Human approval: [Approval gate]\n- Rollback: [Rollback path]\n\nRules:\n1. Inspect relevant context before writing or editing the skill.\n2. Keep the skill narrow, reusable, and easy for another agent to follow.\n3. Do not include secrets, private data, fake claims, or unsupported references.\n4. Include activation guidance, workflow steps, quality bar, examples, verification, and rollback.\n5. Summarize changed files, tests run, remaining risks, and human approval needs.", 'kingy-ai-launch-intelligence');
}

function kingy_ali_agent_skills_worksheet_schema() {
    if (!kingy_ali_is_agent_skills_worksheet_page()) {
        return;
    }

    $faqs = kingy_ali_agent_skills_worksheet_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('Agent Skills Planning Worksheet', 'kingy-ai-launch-intelligence'),
                'description' => __('An interactive worksheet for planning AI agent skills, generating a Codex goal prompt, drafting a SKILL.md outline, and reviewing quality, testing, approval, and rollback.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to plan an AI agent skill', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Define the skill name, audience, trigger, and purpose.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('List required inputs, context, tools, constraints, and forbidden changes.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Generate a goal prompt, SKILL.md outline, and review checklist.', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Download the worksheet and run tests, human approval, and rollback planning.', 'kingy-ai-launch-intelligence')),
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

function kingy_ali_shortcode_agent_skills_worksheet() {
    kingy_ali_enqueue_assets();

    $fields = kingy_ali_agent_skills_worksheet_fields();
    $checklist = kingy_ali_agent_skills_worksheet_checklist();
    $faqs = kingy_ali_agent_skills_worksheet_faqs();
    $total_checks = 0;
    foreach ($checklist as $section) {
        $total_checks += count($section['items']);
    }

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-agent-skills-guide" data-kingy-agent-skills-worksheet>
        <header class="kingy-ali-academy-hero kingy-ali-agent-skills-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive worksheet', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Agent Skills Planning Worksheet', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Turn a fuzzy repeatable workflow into a practical agent skill brief, Codex goal prompt, SKILL.md outline, review checklist, and downloadable planning record.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#agent-skills-planner" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Start worksheet', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="agent_skills_worksheet_hero"><?php esc_html_e('Start worksheet', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#agent-skills-outputs" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('View generated outputs', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="agent_skills_worksheet_hero"><?php esc_html_e('View outputs', 'kingy-ai-launch-intelligence'); ?></a>
                    <button type="button" data-agent-skills-download="markdown" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Download Markdown worksheet', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="agent_skills_worksheet_hero"><?php esc_html_e('Download Markdown', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
            <aside class="kingy-ali-decision-card kingy-ali-agent-skills-progress-card" aria-label="<?php esc_attr_e('Worksheet progress', 'kingy-ai-launch-intelligence'); ?>">
                <span><?php esc_html_e('Review progress', 'kingy-ai-launch-intelligence'); ?></span>
                <strong><span data-agent-skills-count>0</span> / <?php echo esc_html((string) $total_checks); ?></strong>
                <span data-agent-skills-status aria-live="polite"><?php esc_html_e('Start by naming the workflow.', 'kingy-ai-launch-intelligence'); ?></span>
                <progress max="<?php echo esc_attr((string) $total_checks); ?>" value="0" data-agent-skills-progress></progress>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Agent skills worksheet sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#agent-skills-planner"><?php esc_html_e('Planner', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-skills-outputs"><?php esc_html_e('Outputs', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-skills-review"><?php esc_html_e('Review', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-skills-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-skills-rollout"><?php esc_html_e('Rollout', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-skills-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="agent-skills-planner" class="kingy-ali-academy-section kingy-ali-agent-skills-planner">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Skill brief builder', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Fill in the parts an agent needs before it can work well', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The outputs update as you type. Use short plain-language notes first; polish the skill after the workflow is clear.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <form class="kingy-ali-agent-skills-form" data-agent-skills-form>
                <?php foreach ($fields as $field) : ?>
                    <?php $field_id = 'agent-skills-' . sanitize_html_class($field['key']); ?>
                    <label class="kingy-ali-agent-skills-field" for="<?php echo esc_attr($field_id); ?>">
                        <span><strong><?php echo esc_html($field['label']); ?></strong><small><?php echo esc_html($field['step']); ?></small></span>
                        <?php if ($field['type'] === 'textarea') : ?>
                            <textarea id="<?php echo esc_attr($field_id); ?>" rows="4" data-agent-skill-field="<?php echo esc_attr($field['key']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"></textarea>
                        <?php else : ?>
                            <input id="<?php echo esc_attr($field_id); ?>" type="text" data-agent-skill-field="<?php echo esc_attr($field['key']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <div class="kingy-ali-codex-actions">
                    <button type="button" data-agent-skills-load-sample><?php esc_html_e('Load sample', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="reset" data-agent-skills-clear><?php esc_html_e('Clear worksheet', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </form>
        </section>

        <section id="agent-skills-outputs" class="kingy-ali-academy-section kingy-ali-agent-skills-outputs">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Generated outputs', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Copy or download the working materials', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-agent-skills-output-grid">
                <section class="kingy-ali-agent-skills-output">
                    <h3><?php esc_html_e('Codex /goal prompt', 'kingy-ai-launch-intelligence'); ?></h3>
                    <textarea id="agent-skills-goal-output" rows="18" data-agent-skills-output="goal"><?php echo esc_textarea(kingy_ali_agent_skills_worksheet_default_goal()); ?></textarea>
                    <button type="button" data-agent-skills-copy="#agent-skills-goal-output"><?php esc_html_e('Copy goal prompt', 'kingy-ai-launch-intelligence'); ?></button>
                </section>
                <section class="kingy-ali-agent-skills-output">
                    <h3><?php esc_html_e('SKILL.md outline', 'kingy-ai-launch-intelligence'); ?></h3>
                    <textarea id="agent-skills-skill-output" rows="18" data-agent-skills-output="skill"></textarea>
                    <button type="button" data-agent-skills-copy="#agent-skills-skill-output"><?php esc_html_e('Copy SKILL.md', 'kingy-ai-launch-intelligence'); ?></button>
                </section>
                <section class="kingy-ali-agent-skills-output">
                    <h3><?php esc_html_e('Review checklist', 'kingy-ai-launch-intelligence'); ?></h3>
                    <textarea id="agent-skills-review-output" rows="18" data-agent-skills-output="review"></textarea>
                    <button type="button" data-agent-skills-copy="#agent-skills-review-output"><?php esc_html_e('Copy checklist', 'kingy-ai-launch-intelligence'); ?></button>
                </section>
            </div>
            <div class="kingy-ali-codex-actions" aria-live="polite">
                <button type="button" data-agent-skills-download="markdown"><?php esc_html_e('Download Markdown', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-agent-skills-download="json"><?php esc_html_e('Download JSON', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-agent-skills-print><?php esc_html_e('Print worksheet', 'kingy-ai-launch-intelligence'); ?></button>
                <span data-agent-skills-status-line><?php esc_html_e('Downloads use only your browser data.', 'kingy-ai-launch-intelligence'); ?></span>
            </div>
        </section>

        <section id="agent-skills-review" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Quality gate', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Review the skill before using it on real work', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-agent-skills-checklist">
                <?php foreach ($checklist as $section_index => $section) : ?>
                    <details open data-agent-skills-section>
                        <summary class="kingy-ali-agent-skills-section-head">
                            <div><h3><?php echo esc_html($section['title']); ?></h3></div>
                            <div class="kingy-ali-agent-skills-section-score" aria-live="polite">
                                <strong><span data-agent-skills-section-count>0</span> / <?php echo esc_html((string) count($section['items'])); ?></strong>
                                <progress max="<?php echo esc_attr((string) count($section['items'])); ?>" value="0" data-agent-skills-section-progress></progress>
                            </div>
                        </summary>
                        <?php foreach ($section['items'] as $item_index => $item) : ?>
                            <?php $check_id = 'agent-skills-check-' . absint($section_index) . '-' . absint($item_index); ?>
                            <label class="kingy-ali-agent-skills-check" for="<?php echo esc_attr($check_id); ?>" data-agent-skills-item data-item-title="<?php echo esc_attr($item); ?>" data-section-title="<?php echo esc_attr($section['title']); ?>">
                                <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-agent-skills-check>
                                <span><?php echo esc_html($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </details>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-codex-actions">
                <button type="button" data-agent-skills-reset><?php esc_html_e('Reset checklist', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="agent-skills-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Examples and red flags', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Use a skill when repetition and judgment matter', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-agent-skills-card-grid">
                <article><h3><?php esc_html_e('Good skill fit', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('A repeatable workflow with known context sources, safety rules, quality examples, and checks: launch QA, PR review, content publishing, security triage, or workbook generation.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article><h3><?php esc_html_e('Bad skill fit', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('A one-time question, vague brainstorming task, or workflow whose rules are still unknown. Start with a normal prompt until the repeatable pattern appears.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article><h3><?php esc_html_e('Red flags', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('The skill asks the agent to edit production directly, includes secrets, skips inspection, installs tools without review, or lets AI approve its own risky output.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article><h3><?php esc_html_e('Strong examples', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Show the agent what a good result looks like, what a weak result looks like, and what evidence must be included before the work is considered complete.', 'kingy-ai-launch-intelligence'); ?></p></article>
            </div>
        </section>

        <section id="agent-skills-rollout" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Rollout checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Ship the skill like a small operating procedure', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <ol class="kingy-ali-agent-skills-rollout">
                <li><?php esc_html_e('Test the skill on a safe sample task before using it on production or client work.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Compare output against the review checklist and revise unclear instructions.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Document which agent surfaces, repos, or teams may use it.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Keep a rollback path: remove the skill, disable activation, or restore the prior version.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Review the skill after real use and update examples, tests, and boundaries.', 'kingy-ai-launch-intelligence'); ?></li>
            </ol>
        </section>

        <section class="kingy-ali-academy-section">
            <h2><?php esc_html_e('Related Academy tools', 'kingy-ai-launch-intelligence'); ?></h2>
            <div class="kingy-ali-codex-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/codex-zero-to-hero/')); ?>"><strong><?php esc_html_e('Codex Zero to Hero', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use the course hub for the broader Codex learning path.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/codex-prompt-library/')); ?>"><strong><?php esc_html_e('Prompt Library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Pair skills with reusable prompts and review patterns.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/17-security-review-checklist/')); ?>"><strong><?php esc_html_e('Security Review Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Review secrets, permissions, dependencies, and rollback.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/12-website-qa-checklist/')); ?>"><strong><?php esc_html_e('Website QA Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use this when a skill changes public pages or forms.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="agent-skills-faq" class="kingy-ali-academy-section">
            <h2><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></h2>
            <?php foreach ($faqs as $faq) : ?>
                <details class="kingy-ali-card">
                    <summary><?php echo esc_html($faq['question']); ?></summary>
                    <p><?php echo esc_html($faq['answer']); ?></p>
                </details>
            <?php endforeach; ?>
        </section>
    </article>
    <?php

    return ob_get_clean();
}
