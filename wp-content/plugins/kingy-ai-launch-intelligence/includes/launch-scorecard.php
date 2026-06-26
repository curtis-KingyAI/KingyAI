<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_ai_launch_scorecard', 'kingy_ali_shortcode_ai_launch_scorecard');

function kingy_ali_ai_launch_scorecard_categories() {
    $weights = kingy_ali_ai_launch_scorecard_weights();

    return array(
        'product_clarity' => array(
            'title' => __('Product clarity', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['product_clarity'],
            'summary' => __('Can a founder, buyer, creator, or editor explain what changed in one sentence?', 'kingy-ai-launch-intelligence'),
            'missing' => __('The product is vague or sounds like a generic AI wrapper.', 'kingy-ai-launch-intelligence'),
            'partial' => __('The core idea is visible, but the outcome or difference is still fuzzy.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The product, use case, and outcome are immediately understandable.', 'kingy-ai-launch-intelligence'),
        ),
        'audience_clarity' => array(
            'title' => __('Audience clarity', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['audience_clarity'],
            'summary' => __('Is the first target user specific enough for outreach, SEO, and creator matching?', 'kingy-ai-launch-intelligence'),
            'missing' => __('The launch tries to speak to everyone.', 'kingy-ai-launch-intelligence'),
            'partial' => __('A broad audience is named, but the urgent user is not sharp yet.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The launch names a clear buyer, builder, team, or workflow.', 'kingy-ai-launch-intelligence'),
        ),
        'demo_quality' => array(
            'title' => __('Demo quality', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['demo_quality'],
            'summary' => __('Can someone see the product working in a real workflow without guessing?', 'kingy-ai-launch-intelligence'),
            'missing' => __('No useful demo, walkthrough, screenshots, or working example is easy to find.', 'kingy-ai-launch-intelligence'),
            'partial' => __('A demo exists, but it is too slow, abstract, cropped, or hard to trust.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The demo quickly shows the before, workflow, output, and why it matters.', 'kingy-ai-launch-intelligence'),
        ),
        'website_quality' => array(
            'title' => __('Website / launch page quality', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['website_quality'],
            'summary' => __('Does the page answer the launch questions that editors, creators, and buyers need?', 'kingy-ai-launch-intelligence'),
            'missing' => __('The page is thin, confusing, broken, or missing essential launch context.', 'kingy-ai-launch-intelligence'),
            'partial' => __('The page works, but proof, screenshots, positioning, or CTAs need polish.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The page is fast, credible, specific, mobile-friendly, and easy to cite.', 'kingy-ai-launch-intelligence'),
        ),
        'pricing_clarity' => array(
            'title' => __('Pricing clarity', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['pricing_clarity'],
            'summary' => __('Can a visitor understand whether the product is free, paid, usage-based, or sales-led?', 'kingy-ai-launch-intelligence'),
            'missing' => __('Pricing, free-plan status, or sales motion is absent.', 'kingy-ai-launch-intelligence'),
            'partial' => __('Some pricing clues exist, but a buyer still has to hunt.', 'kingy-ai-launch-intelligence'),
            'strong' => __('Pricing, free plan, trial, API, or contact-sales path is explicit.', 'kingy-ai-launch-intelligence'),
        ),
        'launch_distribution_readiness' => array(
            'title' => __('Launch distribution readiness', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['launch_distribution_readiness'],
            'summary' => __('Are the launch channels, assets, timing, and follow-up plan ready?', 'kingy-ai-launch-intelligence'),
            'missing' => __('There is no clear plan beyond publishing and hoping.', 'kingy-ai-launch-intelligence'),
            'partial' => __('A few channels are planned, but assets and follow-up are incomplete.', 'kingy-ai-launch-intelligence'),
            'strong' => __('Product Hunt, community, newsletter, creator, social, and founder follow-up are prepared.', 'kingy-ai-launch-intelligence'),
        ),
        'founder_company_visibility' => array(
            'title' => __('Founder / company visibility', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['founder_company_visibility'],
            'summary' => __('Can editors and creators verify who is behind the product and why they are credible?', 'kingy-ai-launch-intelligence'),
            'missing' => __('The team is anonymous or hard to verify.', 'kingy-ai-launch-intelligence'),
            'partial' => __('A founder or company exists, but the story and proof are light.', 'kingy-ai-launch-intelligence'),
            'strong' => __('Founder profiles, company page, source links, and public context are easy to verify.', 'kingy-ai-launch-intelligence'),
        ),
        'traction_signals' => array(
            'title' => __('Traction signals', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['traction_signals'],
            'summary' => __('Does the launch show useful adoption, community, shipping, or demand signals?', 'kingy-ai-launch-intelligence'),
            'missing' => __('No visible users, waitlist, GitHub, Product Hunt, revenue, usage, or community proof.', 'kingy-ai-launch-intelligence'),
            'partial' => __('Some signals exist, but they are scattered or not explained.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The launch has credible public traction, source-backed proof, or active community momentum.', 'kingy-ai-launch-intelligence'),
        ),
        'seo_comparison_potential' => array(
            'title' => __('SEO / comparison potential', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['seo_comparison_potential'],
            'summary' => __('Can Kingy AI or a founder turn the launch into useful search pages and comparisons?', 'kingy-ai-launch-intelligence'),
            'missing' => __('No category, alternatives, search intent, or comparison angle is obvious.', 'kingy-ai-launch-intelligence'),
            'partial' => __('A category exists, but the alternatives and query angles need sharper framing.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The product has clear category, alternatives, jobs-to-be-done, and comparison angles.', 'kingy-ai-launch-intelligence'),
        ),
        'creator_coverage_fit' => array(
            'title' => __('Creator coverage fit', 'kingy-ai-launch-intelligence'),
            'weight' => $weights['creator_coverage_fit'],
            'summary' => __('Would a creator have a credible reason to explain, test, or review the product?', 'kingy-ai-launch-intelligence'),
            'missing' => __('The product is hard to show or would make a thin video.', 'kingy-ai-launch-intelligence'),
            'partial' => __('There is an angle, but the demo, story, or audience hook needs work.', 'kingy-ai-launch-intelligence'),
            'strong' => __('The product has a visible workflow, strong before/after, and useful audience lesson.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_shortcode_ai_launch_scorecard() {
    kingy_ali_enqueue_assets();

    if (kingy_ali_shortcode_request_value('kingy_launch_scorecard_lead', 10) === '1') {
        return kingy_ali_render_ai_launch_scorecard_success();
    }

    $selected_interest = kingy_ali_normalize_visibility_interest(kingy_ali_shortcode_request_value('kingy_interest', 40));
    $categories = kingy_ali_ai_launch_scorecard_categories();
    $is_managed_scorecard_page = kingy_ali_current_page_path_is('ai-launch-scorecard');
    $heading_tag = $is_managed_scorecard_page ? 'h2' : 'h1';

    ob_start();
    ?>
    <article class="kingy-ali-ai-launch-scorecard" data-kingy-ai-launch-scorecard>
        <section class="kingy-ali-scorecard-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI launch tool', 'kingy-ai-launch-intelligence'); ?></p>
                <?php if (!$is_managed_scorecard_page) : ?>
                    <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('AI Launch Scorecard', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
                <?php endif; ?>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Score whether your AI product is ready for Product Hunt, search discovery, creator coverage, newsletter pickup, and Kingy AI Launch Intelligence review.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-scorecard-proof-row" aria-label="<?php esc_attr_e('Launch readiness signals', 'kingy-ai-launch-intelligence'); ?>">
                    <span><?php esc_html_e('100-point readiness score', 'kingy-ai-launch-intelligence'); ?></span>
                    <span><?php esc_html_e('Founder-facing fixes', 'kingy-ai-launch-intelligence'); ?></span>
                    <span><?php esc_html_e('SEO and video angles', 'kingy-ai-launch-intelligence'); ?></span>
                </div>
            </div>
            <aside class="kingy-ali-scorecard-hero-card">
                <strong><span data-scorecard-hero-score>0</span>/100</strong>
                <span data-scorecard-hero-tier><?php esc_html_e('Invisible Launch', 'kingy-ai-launch-intelligence'); ?></span>
                <p><?php esc_html_e('Answer ten launch-readiness questions. Your score updates instantly and the review form captures the final result.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </section>

        <nav class="kingy-ali-scorecard-nav" aria-label="<?php esc_attr_e('AI Launch Scorecard sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#kingy-ai-launch-scorecard-tool"><?php esc_html_e('Scorecard', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-ai-launch-scorecard-review"><?php esc_html_e('Request review', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-ai-launch-scorecard-method"><?php esc_html_e('Method', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-ai-launch-scorecard-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section class="kingy-ali-scorecard-section">
            <div class="kingy-ali-section-heading">
                <h2><?php esc_html_e('Who this scorecard is for', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use it if you are an AI startup founder, indie hacker, AI app builder, open-source AI builder, model/tool maintainer, or AI SaaS team trying to decide whether the launch story is ready for public attention.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-scorecard-explain-grid">
                <div>
                    <h3><?php esc_html_e('What the score means', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e('How Kingy AI evaluates launches', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Kingy AI looks for launches that can be verified, explained, demonstrated, compared, and matched to a real audience. Strong launches make it easy for editors, creators, and buyers to understand why the product matters now.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e('Why these signals matter', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Demos create trust, pricing clarity lowers friction, target audience sharpens positioning, comparison angles support SEO, founder visibility improves credibility, and distribution planning turns a product announcement into a real launch.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e('What the score does not decide', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('A high score is not an approval, ranking promise, sponsorship quote, investment signal, safety audit, or statement that the product is better than alternatives.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <form id="kingy-ai-launch-scorecard-tool" class="kingy-ali-scorecard-tool" method="post">
            <?php wp_nonce_field('kingy_ali_visibility_score_lead', 'kingy_ali_visibility_score_lead_nonce'); ?>
            <input type="hidden" name="kingy_ali_action" value="visibility_score_lead">
            <input type="hidden" name="kingy_ali_visibility_source" value="ai_launch_scorecard">
            <label class="kingy-ali-hp" aria-hidden="true">
                <span><?php esc_html_e('Leave this field empty', 'kingy-ai-launch-intelligence'); ?></span>
                <input type="text" name="kingy_ali_company_site">
            </label>

            <div class="kingy-ali-scorecard-workbench">
                <div class="kingy-ali-scorecard-questions">
                    <div class="kingy-ali-section-heading">
                        <h2><?php esc_html_e('Score your launch readiness', 'kingy-ai-launch-intelligence'); ?></h2>
                        <p><?php esc_html_e('Choose Missing, Partial, or Strong for each category. Be honest: the highest scores usually come from launches that are specific, demonstrable, and easy to verify.', 'kingy-ai-launch-intelligence'); ?></p>
                    </div>

                    <?php $index = 0; ?>
                    <?php foreach ($categories as $key => $category) : ?>
                        <?php $index++; ?>
                        <fieldset class="kingy-ali-scorecard-category" data-scorecard-category data-scorecard-key="<?php echo esc_attr($key); ?>" data-scorecard-label="<?php echo esc_attr($category['title']); ?>" data-scorecard-weight="<?php echo esc_attr($category['weight']); ?>">
                            <legend>
                                <span><?php echo esc_html($index); ?>. <?php echo esc_html($category['title']); ?></span>
                                <em><?php echo esc_html(sprintf(__('%d points', 'kingy-ai-launch-intelligence'), absint($category['weight']))); ?></em>
                            </legend>
                            <p><?php echo esc_html($category['summary']); ?></p>
                            <div class="kingy-ali-scorecard-options">
                                <?php foreach (array('0' => 'missing', '0.5' => 'partial', '1' => 'strong') as $value => $state) : ?>
                                    <?php $id = 'kingy-ai-launch-scorecard-' . sanitize_key($key) . '-' . sanitize_key($state); ?>
                                    <label>
                                        <input id="<?php echo esc_attr($id); ?>" type="radio" name="kingy_ali_scorecard_scores[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" data-scorecard-input<?php checked($value, '0'); ?>>
                                        <span class="kingy-ali-scorecard-option kingy-ali-scorecard-option--<?php echo esc_attr(sanitize_key($state)); ?>">
                                            <strong><?php echo esc_html(ucfirst($state)); ?></strong>
                                            <small><?php echo esc_html($category[$state]); ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>

                <aside class="kingy-ali-scorecard-result" data-scorecard-result aria-live="polite">
                    <div class="kingy-ali-scorecard-result__top">
                        <p class="kingy-ali-kicker"><?php esc_html_e('Your result', 'kingy-ai-launch-intelligence'); ?></p>
                        <p class="kingy-ali-scorecard-result-brand"><?php esc_html_e('Kingy AI Launch Scorecard', 'kingy-ai-launch-intelligence'); ?></p>
                        <strong><span data-scorecard-score>0</span>/100</strong>
                        <span class="kingy-ali-scorecard-tier" data-scorecard-tier><?php esc_html_e('Invisible Launch', 'kingy-ai-launch-intelligence'); ?></span>
                        <div class="kingy-ali-scorecard-bar" aria-hidden="true"><span data-scorecard-bar></span></div>
                        <p data-scorecard-verdict><?php esc_html_e('Your launch is still mostly invisible. Start with a sharper product story, a concrete audience, and a demo people can understand without a call.', 'kingy-ai-launch-intelligence'); ?></p>
                    </div>

                    <div class="kingy-ali-scorecard-result-grid">
                        <div>
                            <h3><?php esc_html_e('Top strengths', 'kingy-ai-launch-intelligence'); ?></h3>
                            <ul data-scorecard-strengths></ul>
                        </div>
                        <div>
                            <h3><?php esc_html_e('Top weaknesses', 'kingy-ai-launch-intelligence'); ?></h3>
                            <ul data-scorecard-weaknesses></ul>
                        </div>
                    </div>

                    <div class="kingy-ali-scorecard-result-block">
                        <h3><?php esc_html_e('7-day launch fix list', 'kingy-ai-launch-intelligence'); ?></h3>
                        <ol data-scorecard-fixes></ol>
                    </div>

                    <div class="kingy-ali-scorecard-angles">
                        <div>
                            <h3><?php esc_html_e('Best SEO angle', 'kingy-ai-launch-intelligence'); ?></h3>
                            <p data-scorecard-seo-angle></p>
                        </div>
                        <div>
                            <h3><?php esc_html_e('Best YouTube/video angle', 'kingy-ai-launch-intelligence'); ?></h3>
                            <p data-scorecard-video-angle></p>
                        </div>
                        <div>
                            <h3><?php esc_html_e('Best founder launch angle', 'kingy-ai-launch-intelligence'); ?></h3>
                            <p data-scorecard-founder-angle></p>
                        </div>
                    </div>

                    <div class="kingy-ali-scorecard-result-block">
                        <h3><?php esc_html_e('Recommended next pages to create', 'kingy-ai-launch-intelligence'); ?></h3>
                        <ul data-scorecard-pages></ul>
                    </div>

                    <div class="kingy-ali-scorecard-actions">
                        <button type="button" data-scorecard-copy><?php esc_html_e('Copy my score summary', 'kingy-ai-launch-intelligence'); ?></button>
                        <p data-scorecard-copy-status aria-live="polite"></p>
                    </div>

                    <div class="kingy-ali-cta-row">
                        <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit product from AI Launch Scorecard', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_result" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit to Kingy AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Request creator coverage fit review from AI Launch Scorecard', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_result" href="<?php echo esc_url(home_url('/ai-launch-scorecard/?kingy_interest=creator_coverage#kingy-ai-launch-scorecard-review')); ?>"><?php esc_html_e('Request creator coverage fit review', 'kingy-ai-launch-intelligence'); ?></a>
                        <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Use ROI calculator from AI Launch Scorecard', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_result" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Use the AI Sponsored Video ROI Calculator', 'kingy-ai-launch-intelligence'); ?></a>
                    </div>

                    <p class="kingy-ali-scorecard-disclaimer"><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?></p>
                </aside>
            </div>

            <section id="kingy-ai-launch-scorecard-review" class="kingy-ali-scorecard-review">
                <div class="kingy-ali-section-heading">
                    <h2><?php esc_html_e('Request a Kingy AI launch review', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p><?php esc_html_e('Send the score, product URL, and notes to Kingy AI for editorial review, launch visibility feedback, creator coverage fit, or creator campaign review.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div class="kingy-ali-form-grid">
                    <label>
                        <span><?php esc_html_e('Product name', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="text" name="kingy_ali_visibility_lead[product_name]" required>
                    </label>
                    <label>
                        <span><?php esc_html_e('Founder/contact name', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="text" name="kingy_ali_visibility_lead[contact_name]">
                    </label>
                    <label>
                        <span><?php esc_html_e('Email', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="email" name="kingy_ali_visibility_lead[email]" required>
                    </label>
                    <label>
                        <span><?php esc_html_e('Official URL', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="url" name="kingy_ali_visibility_lead[official_url]">
                    </label>
                    <label>
                        <span><?php esc_html_e('Score', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="text" name="kingy_ali_visibility_lead[submitted_score]" value="0 / 100 - Invisible Launch" data-scorecard-form-score readonly>
                    </label>
                    <label>
                        <span><?php esc_html_e('Review interest', 'kingy-ai-launch-intelligence'); ?></span>
                        <select name="kingy_ali_visibility_lead[interest]" data-scorecard-interest>
                            <option value="visibility_score"<?php selected($selected_interest, 'visibility_score'); ?>><?php esc_html_e('Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="creator_coverage"<?php selected($selected_interest, 'creator_coverage'); ?>><?php esc_html_e('Creator coverage fit', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="creator_campaign"<?php selected($selected_interest, 'creator_campaign'); ?>><?php esc_html_e('Creator campaign review', 'kingy-ai-launch-intelligence'); ?></option>
                        </select>
                    </label>
                    <label class="kingy-ali-scorecard-notes-field">
                        <span><?php esc_html_e('Notes', 'kingy-ai-launch-intelligence'); ?></span>
                        <textarea name="kingy_ali_visibility_lead[notes]" rows="4" placeholder="<?php esc_attr_e('Share launch date, Product Hunt plan, demo link, creator goals, or the main gap you want reviewed.', 'kingy-ai-launch-intelligence'); ?>"></textarea>
                    </label>
                </div>
                <button type="submit"><?php esc_html_e('Request Kingy AI review', 'kingy-ai-launch-intelligence'); ?></button>
                <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
                <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
            </section>
        </form>

        <section id="kingy-ai-launch-scorecard-method" class="kingy-ali-scorecard-section">
            <div class="kingy-ali-section-heading">
                <h2><?php esc_html_e('The five score tiers', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The tiers describe how ready the launch looks before deeper editorial review, source checking, and hands-on product evaluation.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-scorecard-tier-grid">
                <div><strong><?php esc_html_e('0-39', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Invisible Launch', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div><strong><?php esc_html_e('40-59', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Launchable, But Weak', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div><strong><?php esc_html_e('60-74', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Promising Launch', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div><strong><?php esc_html_e('75-89', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Coverage-Ready Launch', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div><strong><?php esc_html_e('90-100', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Breakout Launch Candidate', 'kingy-ai-launch-intelligence'); ?></span></div>
            </div>
            <div class="kingy-ali-scorecard-explain-grid">
                <div>
                    <h3><?php esc_html_e('Source policy', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Strong records should point to official pages, demos, public repositories, Product Hunt, Hugging Face, press kits, funding announcements, or other reviewable sources. Unsupported claims should stay draft, noindexed, or clearly labeled until checked.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e('Index readiness', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php echo esc_html(kingy_ali_index_readiness_summary()); ?></p>
                </div>
                <div>
                    <h3><?php esc_html_e('Data handling', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
                </div>
            </div>
        </section>

        <section class="kingy-ali-scorecard-section">
            <div class="kingy-ali-section-heading">
                <h2><?php esc_html_e('Keep building the launch surface', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use these Kingy AI pages to turn the scorecard result into better launch assets, category pages, and creator-ready proof.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-scorecard-link-grid">
                <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><strong><?php esc_html_e('AI Launch Tracker', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Study how launch records are structured and categorized.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><strong><?php esc_html_e('Submit an AI Launch', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Send the product for Launch Intelligence review.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><strong><?php esc_html_e('AI Sponsored Video ROI Calculator', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Estimate creator campaign economics before buying reach.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-search-visibility-calculator/')); ?>"><strong><?php esc_html_e('AI Search Visibility Calculator', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Think through search discovery and comparison-page potential.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-agent-directory/')); ?>"><strong><?php esc_html_e('AI Agent Directory & Readiness Scorecard', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Compare agent launches and readiness signals.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-launches/ai-video-tools/')); ?>"><strong><?php esc_html_e('AI Video Tool Launches', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('See demo-heavy launches built for visual explanation.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-launches/ai-coding-tools/')); ?>"><strong><?php esc_html_e('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Review developer-tool positioning and proof patterns.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/ai-launches/ai-agents/')); ?>"><strong><?php esc_html_e('AI Agent Launches', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Track agent launch categories, demos, and use cases.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><strong><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Explore creator-led education and campaign fit.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><strong><?php esc_html_e('Contact', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Reach Kingy AI about launch review or creator fit.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="kingy-ai-launch-scorecard-faq" class="kingy-ali-scorecard-section">
            <div class="kingy-ali-section-heading">
                <h2><?php esc_html_e('AI Launch Scorecard FAQ', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <details>
                    <summary><?php esc_html_e('What is the AI Launch Scorecard?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('It is a 100-point educational readiness tool for AI founders preparing a product launch, Product Hunt campaign, SEO push, newsletter pitch, creator review, or Kingy AI Launch Intelligence submission.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Does a high score guarantee Kingy AI coverage?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('No. The score helps founders diagnose launch readiness. Kingy AI coverage, rankings, revenue, creator interest, Product Hunt performance, product quality, product safety, and buyer adoption are never guaranteed.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('What usually improves an AI launch score fastest?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('The fastest improvements usually come from a clearer audience, a short product demo, visible pricing or free-plan status, founder/company proof, and a comparison angle that helps buyers understand the category.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Why does creator coverage fit matter?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Creators need a product that can be shown, explained, tested, and tied to a useful audience lesson. A launch with no demo, no before-and-after, or no clear user story is harder to cover well.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('How should creator coverage be disclosed?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('What data does the review form collect?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Should I submit before fixing every weakness?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('You can submit early, especially if the product is timely, but the strongest submissions include official sources, a working demo, pricing context, a clear audience, traction signals, and notes on what changed.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
            </div>
        </section>

        <section class="kingy-ali-scorecard-section kingy-ali-scorecard-disclaimer-panel">
            <h2><?php esc_html_e('Editorial disclaimer', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?> <?php esc_html_e('Always verify claims, pricing, availability, disclosures, and product behavior before publishing, pitching, or buying creator coverage.', 'kingy-ai-launch-intelligence'); ?></p>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_ai_launch_scorecard_success() {
    ob_start();
    ?>
    <div class="kingy-ali-success kingy-ali-scorecard-success">
        <h2><?php esc_html_e('Thanks - your AI Launch Scorecard review request has been sent.', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Kingy AI can use the product URL, score details, and notes to prioritize launch visibility feedback, creator coverage fit, and creator campaign review follow-up.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit full launch after scorecard lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_success" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit the full launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI after scorecard lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_success" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI after scorecard lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_success" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse launches after scorecard lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="ai_launch_scorecard_success" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
