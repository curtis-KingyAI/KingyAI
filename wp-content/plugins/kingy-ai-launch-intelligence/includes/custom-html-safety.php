<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_custom_html_safety_checklist', 'kingy_ali_shortcode_custom_html_safety_checklist');
add_filter('the_content', 'kingy_ali_maybe_replace_custom_html_safety_article', 20);
add_filter('wpseo_title', 'kingy_ali_custom_html_safety_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_custom_html_safety_seo_description');
add_filter('document_title_parts', 'kingy_ali_custom_html_safety_document_title');
add_action('wp_head', 'kingy_ali_custom_html_safety_schema');

function kingy_ali_is_custom_html_safety_page() {
    return kingy_ali_is_rendering_page_slug('10-wordpress-custom-html-safety-checklist');
}

function kingy_ali_maybe_replace_custom_html_safety_article($content) {
    if (!kingy_ali_is_custom_html_safety_page()) {
        return $content;
    }

    return kingy_ali_shortcode_custom_html_safety_checklist();
}

function kingy_ali_custom_html_safety_seo_title($title) {
    if (!kingy_ali_is_custom_html_safety_page()) {
        return $title;
    }

    return __('WordPress Custom HTML Safety Checklist: Blocks, CSS, JS, QA', 'kingy-ai-launch-intelligence');
}

function kingy_ali_custom_html_safety_seo_description($description) {
    if (!kingy_ali_is_custom_html_safety_page()) {
        return $description;
    }

    return __('Use this WordPress Custom HTML safety checklist, risk helper, code smell helper, examples, and Codex prompts before pasting HTML, CSS, or JavaScript live.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_custom_html_safety_document_title($parts) {
    if (kingy_ali_is_custom_html_safety_page()) {
        $parts['title'] = __('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_custom_html_safety_schema() {
    if (!kingy_ali_is_custom_html_safety_page()) {
        return;
    }

    $faqs = kingy_ali_custom_html_safety_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence'),
                'description' => __('A practical safety workbench for checking WordPress Custom HTML blocks, scoped CSS, JavaScript, embeds, forms, secrets, accessibility, mobile behavior, and rollback.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to check WordPress Custom HTML safely before publishing', 'kingy-ai-launch-intelligence'),
                'description' => __('A beginner-friendly review path for deciding whether code belongs in a Custom HTML block and testing it before it reaches a live WordPress page.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Decide whether the idea belongs in browser-only Custom HTML', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Wrap the block in one unique ID and scope CSS to that wrapper', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Keep JavaScript isolated and avoid secrets, payments, logins, and private data', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Review embeds, forms, accessibility, mobile layout, and performance', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Preview, test, back up, and document rollback before publishing', 'kingy-ai-launch-intelligence')),
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

function kingy_ali_custom_html_safety_categories() {
    return array(
        array('title' => __('1. Decide if Custom HTML is the right tool', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Safe fit: a front-end calculator, checklist, quiz, prompt library, comparison table, or static embed you can fully test in a draft.', 'kingy-ai-launch-intelligence'),
            __('Risky fit: anything that needs accounts, protected records, payments, private form storage, server secrets, or trusted backend validation.', 'kingy-ai-launch-intelligence'),
            __('Use a plugin, theme template, form block, embed block, or backend when browser-only code cannot safely own the job.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('2. Scope CSS so it cannot leak', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Wrap the whole block in one unique ID such as #kingy-wp-html-safety-tool.', 'kingy-ai-launch-intelligence'),
            __('Prefix selectors with that wrapper. Avoid styling body, main, .button, a, h2, input, or generic WordPress classes directly.', 'kingy-ai-launch-intelligence'),
            __('Test near the site header, footer, forms, and reusable blocks so you catch accidental layout changes.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('3. Keep JavaScript isolated', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Use an immediately-invoked function or DOMContentLoaded handler instead of global function names.', 'kingy-ai-launch-intelligence'),
            __('Attach listeners with querySelector inside the wrapper. Avoid inline onclick and document.write.', 'kingy-ai-launch-intelligence'),
            __('Check that button IDs, selectors, and script order still work after WordPress saves the page.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('4. Never expose secrets or protected actions', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Do not paste API keys, passwords, tokens, application passwords, private URLs, or customer data into page HTML or JavaScript.', 'kingy-ai-launch-intelligence'),
            __('Do not handle payments, logins, account changes, database writes, or private form submissions in a Custom HTML block alone.', 'kingy-ai-launch-intelligence'),
            __('If the feature needs a secret, it needs server-side code or an approved provider integration.', 'kingy-ai-launch-intelligence'),
        )),
        array('title' => __('5. Review embeds, forms, accessibility, and rollback', 'kingy-ai-launch-intelligence'), 'items' => array(
            __('Prefer WordPress Embed or Form blocks when they do the job without custom code.', 'kingy-ai-launch-intelligence'),
            __('Use semantic headings, labels, keyboard focus, readable contrast, and mobile tap targets.', 'kingy-ai-launch-intelligence'),
            __('Back up the old block, test in draft/staging, and write the exact rollback step before publishing.', 'kingy-ai-launch-intelligence'),
        )),
    );
}

function kingy_ali_custom_html_safety_glossary() {
    return array(
        __('Scoped CSS', 'kingy-ai-launch-intelligence') => __('CSS that only applies inside one wrapper so it does not restyle the rest of WordPress.', 'kingy-ai-launch-intelligence'),
        __('Vanilla JavaScript', 'kingy-ai-launch-intelligence') => __('Browser JavaScript without React, jQuery, npm packages, or external libraries.', 'kingy-ai-launch-intelligence'),
        __('Dependency', 'kingy-ai-launch-intelligence') => __('Outside code your block needs in order to work. Dependencies can break, slow the page, or add supply-chain risk.', 'kingy-ai-launch-intelligence'),
        __('API key', 'kingy-ai-launch-intelligence') => __('A private credential for a service. If visitors can view it in page source, it is exposed.', 'kingy-ai-launch-intelligence'),
        __('Backend', 'kingy-ai-launch-intelligence') => __('Server-side code that can safely use secrets, validate requests, store data, and enforce permissions.', 'kingy-ai-launch-intelligence'),
        __('Sanitization', 'kingy-ai-launch-intelligence') => __('Cleaning input by removing or limiting unsafe markup before it is stored or rendered.', 'kingy-ai-launch-intelligence'),
        __('Escaping', 'kingy-ai-launch-intelligence') => __('Encoding output for the exact context where it appears, such as HTML text, attributes, URLs, CSS, or JavaScript.', 'kingy-ai-launch-intelligence'),
        __('Nonce', 'kingy-ai-launch-intelligence') => __('A one-time-ish WordPress token used to help confirm an action came from the intended screen or form.', 'kingy-ai-launch-intelligence'),
        __('XSS', 'kingy-ai-launch-intelligence') => __('Cross-site scripting: an injection bug where attacker-controlled code runs in a visitor’s browser.', 'kingy-ai-launch-intelligence'),
        __('CSP', 'kingy-ai-launch-intelligence') => __('Content Security Policy, a browser security header that can reduce script risk but does not replace fixing unsafe code.', 'kingy-ai-launch-intelligence'),
        __('Rollback', 'kingy-ai-launch-intelligence') => __('The exact way to undo the block if it breaks layout, behavior, tracking, or trust.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_custom_html_safety_prompts() {
    return array(
        'plan' => array(
            'label' => __('Plan first', 'kingy-ai-launch-intelligence'),
            'text' => __('Do not write code yet. I want to build a WordPress Custom HTML block for [audience] that does [job]. First decide whether Custom HTML is the right tool. Then plan the wrapper ID, HTML structure, scoped CSS, JavaScript behavior, accessibility, mobile layout, risks, rollback path, and test checklist.', 'kingy-ai-launch-intelligence'),
        ),
        'build' => array(
            'label' => __('Build self-contained block', 'kingy-ai-launch-intelligence'),
            'text' => __('Build one self-contained WordPress Custom HTML block using HTML, scoped CSS under one unique wrapper ID, and vanilla JavaScript only. No external libraries, API calls, API keys, trackers, payments, logins, databases, or private data. Include empty states, validation, keyboard-friendly controls, mobile layout, and a short test checklist.', 'kingy-ai-launch-intelligence'),
        ),
        'review' => array(
            'label' => __('Safety review', 'kingy-ai-launch-intelligence'),
            'text' => __('Review this as a WordPress Custom HTML block before I paste or publish it. Check CSS scope, JavaScript isolation, inline event handlers, external scripts, secrets/API keys, forms, embeds, accessibility, mobile layout, console errors, WordPress stripping risk, and rollback. Give me pass/fail notes and the smallest safe fixes.', 'kingy-ai-launch-intelligence'),
        ),
        'debug' => array(
            'label' => __('Broken button debug', 'kingy-ai-launch-intelligence'),
            'text' => __('A button in my WordPress Custom HTML block does nothing. Diagnose the likely cause first. Check selector mismatch, duplicate IDs, script loading order, inline handler stripping, JavaScript errors, event listener scope, and whether WordPress changed the markup. Make the smallest fix possible and explain what to retest.', 'kingy-ai-launch-intelligence'),
        ),
        'mobile' => array(
            'label' => __('Mobile QA', 'kingy-ai-launch-intelligence'),
            'text' => __('Review this WordPress Custom HTML block on mobile. Do not redesign it. Check text wrapping, tap targets, horizontal scrolling, sticky elements, forms, result panels, code blocks, contrast, keyboard focus, and whether any text overlaps. Make focused CSS fixes under the existing wrapper only.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_custom_html_safety_faqs() {
    return array(
        array('question' => __('Is WordPress Custom HTML safe?', 'kingy-ai-launch-intelligence'), 'answer' => __('It can be safe for simple front-end content and tools when CSS is scoped, JavaScript is isolated, no secrets are exposed, and the block is tested in draft or staging. It is not the right place for private data, payments, logins, or server-trusted actions.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I use JavaScript in a WordPress Custom HTML block?', 'kingy-ai-launch-intelligence'), 'answer' => __('On self-hosted WordPress, users with the right capability can usually add scripts, but roles, hosts, security plugins, WordPress.com plans, and sanitization rules can strip or block script tags. Keep JavaScript small, local to the wrapper, and tested after saving.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I paste iframe embeds?', 'kingy-ai-launch-intelligence'), 'answer' => __('Sometimes. Prefer the native WordPress Embed block when it supports the provider. For iframes, review the source, permissions, dimensions, mobile behavior, loading cost, privacy expectations, and whether WordPress or your plan allows the tag.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I collect form submissions with Custom HTML?', 'kingy-ai-launch-intelligence'), 'answer' => __('A form UI can live in Custom HTML, but real submission handling needs a trusted endpoint, consent copy, spam controls, validation, privacy review, and error handling. A native Form block or approved form plugin is usually safer for beginners.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can I use API keys in Custom HTML?', 'kingy-ai-launch-intelligence'), 'answer' => __('No private API key belongs in browser-visible HTML or JavaScript. Visitors can inspect source and network requests. Use server-side code, environment variables, or an approved provider integration instead.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Why did WordPress strip my HTML?', 'kingy-ai-launch-intelligence'), 'answer' => __('WordPress may remove disallowed tags or attributes based on role capabilities, WordPress.com plan rules, security plugins, or the editor context. Tags such as script, style, iframe, form, object, and embed are common places to see stripping.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Why does it work in preview but not live?', 'kingy-ai-launch-intelligence'), 'answer' => __('Preview and live pages can differ because of theme CSS, caching, minification, security headers, role-based sanitization, plugin conflicts, missing assets, or scripts running before the saved markup exists. Test after saving while logged out too.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_shortcode_custom_html_safety_checklist() {
    kingy_ali_enqueue_assets();

    $categories = kingy_ali_custom_html_safety_categories();
    $glossary = kingy_ali_custom_html_safety_glossary();
    $prompts = kingy_ali_custom_html_safety_prompts();
    $faqs = kingy_ali_custom_html_safety_faqs();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-html-safety-guide" data-kingy-html-safety-guide>
        <header class="kingy-ali-academy-hero kingy-ali-html-safety-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('A practical workbench for checking whether HTML, CSS, JavaScript, embeds, forms, and AI-generated blocks are safe enough to paste into WordPress.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#html-safety-decision" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Use decision helper', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="custom_html_safety_hero"><?php esc_html_e('Check my idea', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#html-safety-code-smell" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Scan code smells', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="custom_html_safety_hero"><?php esc_html_e('Check code smells', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('Quick answer', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Custom HTML is a good fit for browser-only tools you can preview, test, replace, and roll back. It is risky when it touches secrets, private data, accounts, payments, databases, or trusted server actions.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul>
                    <li><?php esc_html_e('Safe: scoped CSS, isolated JS, public content, local calculations.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Risky: external scripts, iframes, forms, untrusted user input.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Wrong tool: API keys, logins, payments, private storage.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Custom HTML safety sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#html-safety-decision"><?php esc_html_e('Decision', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#html-safety-checklist"><?php esc_html_e('Checklist', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#html-safety-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#html-safety-code-smell"><?php esc_html_e('Code helper', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#html-safety-prompts"><?php esc_html_e('Prompts', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#html-safety-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="html-safety-decision" class="kingy-ali-builder-chooser kingy-ali-html-decision">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Decision helper', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Is this safe for a Custom HTML block?', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Pick the closest description. The goal is not fear; it is choosing the lowest-risk WordPress surface for the job.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('Custom HTML risk options', 'kingy-ai-launch-intelligence'); ?>">
                <button type="button" class="kingy-ali-choice-button" data-html-risk="safe"><strong><?php esc_html_e('Local browser tool', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Calculator, checklist, quiz, tabs, table, or prompt helper using public inputs only.', 'kingy-ai-launch-intelligence'); ?></span></button>
                <button type="button" class="kingy-ali-choice-button" data-html-risk="review"><strong><?php esc_html_e('Embed or form UI', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Iframe, third-party widget, newsletter form, contact form, or script from another service.', 'kingy-ai-launch-intelligence'); ?></span></button>
                <button type="button" class="kingy-ali-choice-button" data-html-risk="backend"><strong><?php esc_html_e('Trusted/private action', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('API key, login, payment, protected data, saved submissions, account change, or database write.', 'kingy-ai-launch-intelligence'); ?></span></button>
            </div>
            <div class="kingy-ali-choice-result" data-html-risk-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Start by choosing the risk shape', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('The answer changes depending on whether the block only changes the visitor’s browser or asks WordPress/server systems to trust it.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="html-safety-checklist" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Check the block before you paste or publish', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Complete this in a draft page, staging site, or copied block. A green-looking preview is not the same as a safe live block.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-html-checklist" data-html-safety-checklist>
                <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                    <strong><span data-html-safety-check-count>0</span> / 15</strong>
                    <span data-html-safety-check-status><?php esc_html_e('Draft review', 'kingy-ai-launch-intelligence'); ?></span>
                    <progress max="15" value="0" data-html-safety-check-progress></progress>
                </div>
                <?php foreach ($categories as $category_index => $category) : ?>
                    <section>
                        <h3><?php echo esc_html($category['title']); ?></h3>
                        <?php foreach ($category['items'] as $item_index => $item) : ?>
                            <?php $check_id = 'html-safety-check-' . absint($category_index) . '-' . absint($item_index); ?>
                            <label for="<?php echo esc_attr($check_id); ?>">
                                <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-html-safety-check>
                                <span><?php echo esc_html($item); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
                <button type="button" class="kingy-ali-codex-secondary" data-html-safety-reset><?php esc_html_e('Reset checklist', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="html-safety-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Safe vs risky patterns', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Tiny examples that prevent big messes', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-html-tabs">
                <div class="kingy-ali-html-tabs__nav" role="tablist" aria-label="<?php esc_attr_e('Code example tabs', 'kingy-ai-launch-intelligence'); ?>">
                    <button type="button" id="html-tab-safe" class="is-active" role="tab" aria-selected="true" aria-controls="html-panel-safe" data-html-tab="safe"><?php esc_html_e('Safer', 'kingy-ai-launch-intelligence'); ?></button>
                    <button type="button" id="html-tab-risky" role="tab" aria-selected="false" aria-controls="html-panel-risky" data-html-tab="risky"><?php esc_html_e('Risky', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
                <div id="html-panel-safe" role="tabpanel" aria-labelledby="html-tab-safe" data-html-panel="safe">
                    <pre><code><?php echo esc_html('<div id="kingy-example-tool">
  <button type="button" data-action="calculate">Calculate</button>
  <p data-result aria-live="polite"></p>
</div>
<style>
  #kingy-example-tool { max-width: 720px; }
  #kingy-example-tool button { min-height: 44px; }
</style>
<script>
(() => {
  const root = document.querySelector("#kingy-example-tool");
  if (!root) return;
  root.querySelector("[data-action]").addEventListener("click", () => {
    root.querySelector("[data-result]").textContent = "Result ready.";
  });
})();
</script>'); ?></code></pre>
                </div>
                <div id="html-panel-risky" role="tabpanel" aria-labelledby="html-tab-risky" data-html-panel="risky">
                    <pre><code><?php echo esc_html('<style>
  body, .button, input { font-size: 12px !important; }
</style>
<button onclick="calculate()">Pay now</button>
<script src="https://unknown.example/widget.js"></script>
<script>
  const API_KEY = "sk-live-private-key-do-not-paste";
  function calculate() {
    document.write(location.hash);
  }
</script>'); ?></code></pre>
                </div>
            </div>
        </section>

        <section id="html-safety-code-smell" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Code smell helper', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Paste a snippet and look for obvious risks', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('This is a quick helper, not a security scanner. It flags patterns that deserve human review before publishing.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-agent-tool kingy-ali-html-smell-tool">
                <form data-html-smell-form>
                    <label>
                        <span><?php esc_html_e('HTML/CSS/JS snippet', 'kingy-ai-launch-intelligence'); ?></span>
                        <textarea rows="12" data-html-smell-input placeholder="<?php esc_attr_e('Paste your Custom HTML block here...', 'kingy-ai-launch-intelligence'); ?>"></textarea>
                    </label>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Check snippet', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-agent-output" data-html-smell-output aria-live="polite">
                    <p class="kingy-ali-kicker"><?php esc_html_e('Review notes', 'kingy-ai-launch-intelligence'); ?></p>
                    <h3><?php esc_html_e('No snippet checked yet', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Paste code to flag common issues like external scripts, inline handlers, likely secrets, unscoped CSS, forms, iframes, and global functions.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <section id="html-safety-prompts" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Codex prompt generator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Ask AI to build and review the block safely', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-html-prompt-tool">
                <form data-html-prompt-form>
                    <label><span><?php esc_html_e('Prompt type', 'kingy-ai-launch-intelligence'); ?></span><select data-html-prompt-type><?php foreach ($prompts as $key => $prompt) : ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($prompt['label']); ?></option><?php endforeach; ?></select></label>
                    <label><span><?php esc_html_e('Audience', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-html-prompt-field="audience" placeholder="<?php esc_attr_e('Example: WordPress beginners', 'kingy-ai-launch-intelligence'); ?>"></label>
                    <label><span><?php esc_html_e('Thing to build or review', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-html-prompt-field="job" placeholder="<?php esc_attr_e('Example: an ROI calculator block', 'kingy-ai-launch-intelligence'); ?>"></label>
                </form>
                <div class="kingy-ali-agent-output">
                    <textarea rows="10" readonly data-html-prompt-output><?php echo esc_textarea($prompts['plan']['text']); ?></textarea>
                    <button type="button" data-html-copy-prompt><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
            <script type="application/json" data-html-prompt-library><?php echo wp_json_encode($prompts); ?></script>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Real risk vs security theater', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Spend attention where it actually lowers risk', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Real risk: exposed secrets', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('A private API key in page source is not private. Move the feature server-side.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Real risk: untrusted HTML', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('User-supplied HTML needs a sanitizer and context-aware output handling, not hope.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Theater: hiding complexity', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Renaming a button or minifying a script does not make dangerous browser code safe.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Theater: “it works in preview”', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Preview proves the happy path once. Safety needs save, live, logged-out, mobile, and rollback checks.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Glossary', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Plain-English terms you will see in safety reviews', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <dl class="kingy-ali-agent-glossary">
                <?php foreach ($glossary as $term => $definition) : ?>
                    <div><dt><?php echo esc_html($term); ?></dt><dd><?php echo esc_html($definition); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Resources and sources', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep the safety pass connected to the rest of the Academy', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/build-ai-academy-toolkit/')); ?>"><strong><?php esc_html_e('CSS Scoping Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use the toolkit hub for scoping, website QA, mobile QA, accessibility QA, SEO QA, and performance QA assets.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/beginner-safety-rules/')); ?>"><strong><?php esc_html_e('Codex Safety Checklist', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Review secrets, permissions, approval, and rollback before real site changes.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn this checklist into a scoped review or build prompt.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="https://wordpress.org/documentation/article/custom-html/"><strong><?php esc_html_e('WordPress.org Custom HTML block docs', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Official block behavior, role capability notes, and editor details.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="https://developer.wordpress.org/apis/security/escaping/"><strong><?php esc_html_e('WordPress escaping handbook', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Context-aware output escaping guidance for WordPress developers.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html"><strong><?php esc_html_e('OWASP XSS Prevention Cheat Sheet', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Reference for dangerous contexts, sanitization, encoding, and XSS prevention.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="html-safety-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Common WordPress Custom HTML safety questions', 'kingy-ai-launch-intelligence'); ?></h2>
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
