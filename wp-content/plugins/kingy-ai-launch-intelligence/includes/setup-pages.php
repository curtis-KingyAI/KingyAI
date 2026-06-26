<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_kingy_ali_setup_pages', 'kingy_ali_handle_install_pages');
add_action('admin_post_kingy_ali_install_pages', 'kingy_ali_handle_install_pages');
add_action('admin_init', 'kingy_ali_maybe_ensure_model_landing_pages');
add_action('admin_init', 'kingy_ali_maybe_ensure_model_static_compare_pages');
add_filter('nav_menu_link_attributes', 'kingy_ali_fix_ai_courses_menu_link', 10, 4);

function kingy_ali_shortcode_block($shortcode) {
    return '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->';
}

function kingy_ali_creator_campaign_roi_page_content() {
    return <<<'HTML'
<!-- wp:html -->
<h1 class="kingy-ali-page-title kingy-ali-page-title--injected" style="box-sizing:border-box;padding-left:calc(clamp(28px,4vw,44px) + clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(28px,4vw,44px) + clamp(22px,4vw,42px) + 1px)">AI Sponsored Video ROI Calculator</h1>

<section class="kingy-ali-content-band" style="box-sizing:border-box;margin-left:clamp(28px,4vw,44px);margin-right:clamp(28px,4vw,44px);padding:clamp(22px,4vw,42px);padding-left:calc(clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(22px,4vw,42px) + 1px)">
    <p><strong>Use this YouTube sponsorship ROI calculator to decide whether a creator campaign can make economic sense for an AI product.</strong> The model helps you compare creator fee, expected views, click-through rate, conversion rate, customer value, CAC, payback, and the harder-to-measure value of trusted product education.</p>
    <p>It is built for AI founders, marketers, product-led growth teams, and creator partnerships teams that need a clearer way to price sponsored videos before committing budget. If you are still shaping the launch itself, start with the <a href="/ai-launch-scorecard/">AI Launch Scorecard</a> or the <a href="/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign">AI Launch Visibility Score Calculator</a>, then come back here to model the sponsorship economics.</p>
    <div class="kingy-ali-cta-row">
        <a href="/ai-launches/submit/">Submit your AI launch</a>
        <a href="/ai-launches/creator-coverage-ai-launches/">Browse creator-ready launches</a>
        <a href="/ai-launches/ai-video-tools/">Explore AI video tool launches</a>
        <a href="/contact/">Talk to Kingy AI</a>
    </div>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->[kingy_creator_campaign_roi_calculator]<!-- /wp:shortcode -->

<!-- wp:html -->
<section class="kingy-ali-content-band" style="box-sizing:border-box;margin-left:clamp(28px,4vw,44px);margin-right:clamp(28px,4vw,44px);padding:clamp(22px,4vw,42px);padding-left:calc(clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(22px,4vw,42px) + 1px)">
    <h2>How to use the AI sponsored video ROI calculator</h2>
    <p>Start with a realistic creator fee and recent video averages from the creator's channel. Subscriber count is less important than topic fit, audience trust, demo quality, and whether the creator can explain your AI product clearly. For technical products, a smaller but more qualified audience can outperform a broad audience with weak buying intent.</p>
    <p>Then replace the default assumptions with your own funnel data. Use your landing page conversion rate, trial-to-paid rate, customer lifetime value, gross margin, and payback target. The calculator is most useful when you compare multiple scenarios: a conservative case, a base case, and an upside case where the video produces long-tail discovery or sales enablement value.</p>
    <p>The output should not be treated as a promise. Treat it as a planning model for sponsorship negotiation, campaign structure, and post-launch measurement. After the video goes live, validate the model with UTM links, promo codes, branded search changes, direct traffic, retargeting audience growth, survey responses, CRM notes, and creator-specific landing page performance.</p>
</section>

<section class="kingy-ali-content-band" style="box-sizing:border-box;margin-left:clamp(28px,4vw,44px);margin-right:clamp(28px,4vw,44px);padding:clamp(22px,4vw,42px);padding-left:calc(clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(22px,4vw,42px) + 1px)">
    <h2>What makes creator sponsorship different for AI products?</h2>
    <p>AI products often need explanation before they need a hard sell. A good sponsored video can show the workflow, surface the use case, prove the product is real, and give buyers language they can repeat to a team. That means the value is not always captured by first-click attribution alone.</p>
    <p>For a low-price AI app, the model may show that you need high reach, strong retention, annual-plan packaging, or a very efficient signup funnel. For a higher-LTV AI SaaS product, a sponsored walkthrough may be easier to justify if it creates demo requests, retargeting audiences, sales proof, investor proof, or long-tail YouTube discovery.</p>
    <p>If the calculator shows weak economics, use the result as a strategy signal. Improve the offer, sharpen the landing page, choose a more qualified creator, negotiate usage rights, add a pinned comment or newsletter mention, or delay the campaign until the product story is clearer. The <a href="/ai-launches/">AI Launch Intelligence hub</a> can help you compare how other AI launches are positioned.</p>
</section>

<section class="kingy-ali-link-panel" style="box-sizing:border-box;margin-left:clamp(28px,4vw,44px);margin-right:clamp(28px,4vw,44px);padding:clamp(22px,4vw,42px);padding-left:calc(clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(22px,4vw,42px) + 1px)">
    <h2>Useful next steps inside Kingy AI</h2>
    <p>Use these internal tools and launch hubs to move from sponsorship math to a stronger launch plan.</p>
    <div class="kingy-ali-link-list">
        <a href="/ai-launch-scorecard/">Score your AI launch readiness</a>
        <a href="/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign">Check AI launch visibility</a>
        <a href="/ai-launches/submit/">Submit an AI launch</a>
        <a href="/ai-launches/creator-coverage-ai-launches/">Find creator coverage candidates</a>
        <a href="/ai-launches/ai-video-tools/">Browse AI video tool launches</a>
        <a href="/ai-tools/">Explore AI tools</a>
        <a href="/ai-companies/">Explore AI companies</a>
        <a href="/ai-launches/ai-agents/">Browse AI agent launches</a>
    </div>
</section>

<section class="kingy-ali-content-band" style="box-sizing:border-box;margin-left:clamp(28px,4vw,44px);margin-right:clamp(28px,4vw,44px);padding:clamp(22px,4vw,42px);padding-left:calc(clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(22px,4vw,42px) + 1px)">
    <h2>Sponsored video ROI FAQ</h2>
    <h3>What is a good sponsored video ROI?</h3>
    <p>A good ROI depends on your price point, margin, LTV, payback window, and the value of qualified attention. A consumer AI app may need high-volume conversion, while a sales-led AI platform may justify a campaign with fewer but more valuable customers or demo requests.</p>
    <h3>Should I compare creator sponsorships to paid ads?</h3>
    <p>Yes, but do it carefully. Paid ads are easier to control and test, while creator sponsorships can add trust, product education, long-tail search, and reusable proof. Use the calculator to keep the economics visible, then compare it with your paid acquisition CAC.</p>
    <h3>When should an AI company avoid a sponsored video?</h3>
    <p>Avoid or delay the campaign when the landing page is unclear, onboarding is weak, the creator audience is too broad, the offer is not ready, or the payback math only works under optimistic assumptions. In that case, improve launch readiness first with the <a href="/ai-launch-scorecard/">AI Launch Scorecard</a>.</p>
    <h3>What should I measure after launch?</h3>
    <p>Track clicks, signups, paid conversions, demo requests, promo code usage, branded search lift, direct traffic, retargeting audience growth, social mentions, sales-call references, and delayed conversions. The strongest sponsorship reviews combine hard CAC math with evidence of assisted demand.</p>
</section>
<!-- /wp:html -->
HTML;
}

function kingy_ali_recommended_pages() {
    $pages = array(
        'hub' => array(
            'path' => 'ai-launches',
            'title' => __('AI Launch Command Center', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_hub]'),
        ),
        'ai_courses' => array(
            'path' => 'ai-courses',
            'title' => __('AI Courses', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_ai_courses_hub]'),
        ),
        'today' => array(
            'path' => 'ai-launches/today',
            'title' => __('Today\'s AI Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid period="today"]'),
        ),
        'this_week' => array(
            'path' => 'ai-launches/this-week',
            'title' => __('This Week\'s AI Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid period="week"]'),
        ),
        'ai_agents' => array(
            'path' => 'ai-launches/ai-agents',
            'title' => __('AI Agent Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-agents"]'),
        ),
        'ai_video_tools' => array(
            'path' => 'ai-launches/ai-video-tools',
            'title' => __('AI Video Tool Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-video-tools"]'),
        ),
        'ai_coding_tools' => array(
            'path' => 'ai-launches/ai-coding-tools',
            'title' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-coding-tools"]'),
        ),
        'ai_image_tools' => array(
            'path' => 'ai-launches/ai-image-tools',
            'title' => __('AI Image Tool Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-image-tools"]'),
        ),
        'open_weight_models' => array(
            'path' => 'ai-launches/open-weight-models',
            'title' => __('AI Open-Weight Model Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-open-weight-models"]'),
        ),
        'ai_search_research_tools' => array(
            'path' => 'ai-launches/ai-search-research-tools',
            'title' => __('AI Search and Research Tool Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-search-tools" limit="12"]') . "\n\n" . kingy_ali_shortcode_block('[kingy_launch_grid category="ai-research-tools" limit="12"]'),
        ),
        'ai_app_builders' => array(
            'path' => 'ai-launches/ai-app-builders',
            'title' => __('AI App Builder and Vibe Coding Launches', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid category="ai-coding-tools" limit="18"]'),
        ),
        'youtube_worthy' => array(
            'path' => 'ai-launches/youtube-worthy-ai-tools',
            'title' => __('YouTube-Worthy AI Tools', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_youtube_worthy_launches]'),
        ),
        'founder_submitted' => array(
            'path' => 'ai-launches/founder-submitted-ai-tools',
            'title' => __('Founder-Submitted AI Tools', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid attribute="founder-submitted"]'),
        ),
        'funding_announcements' => array(
            'path' => 'ai-launches/funding-announcements',
            'title' => __('AI Funding Announcements', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_grid attribute="funding-announced"]'),
        ),
        'creator_coverage' => array(
            'path' => 'ai-launches/creator-coverage-ai-launches',
            'title' => __('AI Companies and Launches With Strong Creator Coverage Potential', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_creator_coverage_launches]'),
        ),
        'submit' => array(
            'path' => 'ai-launches/submit',
            'title' => __('Submit an AI Launch', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_submit_form]'),
        ),
        'visibility_score' => array(
            'path' => 'ai-launches/launch-visibility-score',
            'title' => __('AI Launch Visibility Score Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_launch_visibility_score]'),
        ),
        'ai_launch_scorecard' => array(
            'path' => 'ai-launch-scorecard',
            'title' => __('AI Launch Scorecard', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_ai_launch_scorecard]'),
        ),
        'creator_campaign_roi' => array(
            'path' => 'ai-launches/creator-campaign-roi-calculator',
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_creator_campaign_roi_page_content(),
        ),
        'ai_sponsored_video_roi_calculator' => array(
            'path' => 'ai-sponsored-video-roi-calculator',
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_creator_campaign_roi_page_content(),
        ),
        'youtube_sponsorship_roi_calculator' => array(
            'path' => 'youtube-sponsorship-roi-calculator',
            'title' => __('YouTube Sponsorship ROI Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_sponsorship_roi_comparison_page angle="youtube-sponsorship-roi"]'),
        ),
        'influencer_marketing_cac_calculator' => array(
            'path' => 'influencer-marketing-cac-calculator',
            'title' => __('Influencer Marketing CAC Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_sponsorship_roi_comparison_page angle="influencer-marketing-cac"]'),
        ),
        'creator_sponsorship_payback_calculator' => array(
            'path' => 'creator-sponsorship-payback-calculator',
            'title' => __('Creator Sponsorship Payback Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_sponsorship_roi_comparison_page angle="creator-sponsorship-payback"]'),
        ),
        'ai_product_sponsorship_calculator' => array(
            'path' => 'ai-product-sponsorship-calculator',
            'title' => __('AI Product Sponsorship Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_sponsorship_roi_comparison_page angle="ai-product-sponsorship"]'),
        ),
        'youtube_sponsorship_rate_vs_roi_calculator' => array(
            'path' => 'youtube-sponsorship-rate-vs-roi-calculator',
            'title' => __('YouTube Sponsorship Rate vs ROI Calculator', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_sponsorship_roi_comparison_page angle="youtube-sponsorship-rate-vs-roi"]'),
        ),
        'codex_prompt_builder' => array(
            'path' => 'ai/build-with-ai-academy/tools/codex-prompt-builder',
            'title' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_codex_prompt_builder]'),
        ),
        'vibe_coding_beginner_hub' => array(
            'path' => 'vibe-coding-for-beginners-ai-app-builder',
            'title' => __('Vibe Coding for Beginners: AI App Builder', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_vibe_coding_beginner_hub]'),
        ),
        'replit_beginner_guide' => array(
            'path' => 'replit-for-beginners-ai-apps',
            'title' => __('Replit for Beginners: Build Your First AI App', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_replit_beginner_guide]'),
        ),
        'microsoft_copilot_course' => array(
            'path' => 'microsoft-copilot-course',
            'title' => __('Microsoft Copilot Zero to Hero', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_microsoft_copilot_course]'),
        ),
        'app_builder_comparison' => array(
            'path' => 'ai/build-with-ai-academy/articles/lovable-vs-replit-vs-bolt-vs-bubble-vs-softr',
            'title' => __('Lovable vs Replit vs Bolt vs Bubble vs Softr', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_app_builder_comparison]'),
        ),
        'ai_lead_magnet_guide' => array(
            'path' => 'ai/build-with-ai-academy/articles/how-to-build-a-lead-magnet-with-ai',
            'title' => __('How to Build a Lead Magnet With AI', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_ai_lead_magnet_guide]'),
        ),
        'ai_landing_page_guide' => array(
            'path' => 'ai/build-with-ai-academy/articles/how-to-build-a-landing-page-with-ai',
            'title' => __('How to Build a Landing Page With AI', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_ai_landing_page_guide]'),
        ),
        'safe_ai_agent_guide' => array(
            'path' => 'ai/build-with-ai-academy/articles/how-to-build-an-ai-agent-safely',
            'title' => __('How to Build an AI Agent Safely', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_safe_ai_agent_guide]'),
        ),
        'custom_html_safety_checklist' => array(
            'path' => '10-wordpress-custom-html-safety-checklist',
            'title' => __('WordPress Custom HTML Safety Checklist', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_custom_html_safety_checklist]'),
        ),
        'website_qa_checklist' => array(
            'path' => '12-website-qa-checklist',
            'title' => __('Website QA Checklist', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_website_qa_checklist]'),
        ),
        'seo_qa_checklist' => array(
            'path' => '15-seo-qa-checklist',
            'title' => __('SEO QA Checklist', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_seo_qa_checklist]'),
        ),
        'security_review_checklist' => array(
            'path' => '17-security-review-checklist',
            'title' => __('Security Review Checklist', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_security_review_checklist]'),
        ),
        'agent_skills_worksheet' => array(
            'path' => '24-agent-skills-planning-worksheet',
            'title' => __('Agent Skills Planning Worksheet', 'kingy-ai-launch-intelligence'),
            'content' => kingy_ali_shortcode_block('[kingy_agent_skills_worksheet]'),
        ),
    );

    $pages['compare_ai_models'] = array(
        'path' => 'compare-ai-models',
        'title' => __('Compare AI Models', 'kingy-ai-launch-intelligence'),
        'content' => kingy_ali_shortcode_block('[kingy_ai_model_compare heading="no"]'),
        'post_status' => 'publish',
        'meta_input' => array(
            kingy_ali_meta_key('noindex') => '1',
        ),
    );

    if (function_exists('kingy_ali_best_model_page_configs')) {
        foreach (kingy_ali_best_model_page_configs() as $path => $config) {
            $shortcode_atts = array();
            foreach (array('use_case', 'modality', 'access_type') as $key) {
                if (!empty($config[$key])) {
                    $shortcode_atts[] = $key . '="' . esc_attr($config[$key]) . '"';
                }
            }

            $title = isset($config['title']) ? $config['title'] : kingy_ali_page_title_from_path($path);
            $pages['model_' . sanitize_key(str_replace('-', '_', $path))] = array(
                'path' => $path,
                'title' => $title,
                'content' => kingy_ali_shortcode_block('[kingy_best_ai_models title="' . esc_attr($title) . '" ' . implode(' ', $shortcode_atts) . ' heading="no"]'),
                'post_status' => !empty($config['post_status']) ? sanitize_key($config['post_status']) : 'draft',
                'meta_input' => array(
                    kingy_ali_meta_key('noindex') => '1',
                ),
            );
        }
    }

    if (function_exists('kingy_ali_model_landing_page_configs')) {
        foreach (kingy_ali_model_landing_page_configs() as $key => $config) {
            if (empty($config['path'])) {
                continue;
            }

            $meta_input = array();
            if (!empty($config['noindex'])) {
                $meta_input[kingy_ali_meta_key('noindex')] = '1';
            }

            $title = isset($config['title']) ? $config['title'] : kingy_ali_page_title_from_path($config['path']);
            $pages['model_landing_' . sanitize_key($key)] = array(
                'path' => $config['path'],
                'title' => $title,
                'content' => kingy_ali_shortcode_block('[kingy_ai_model_landing key="' . esc_attr($key) . '" heading="no"]'),
                'post_status' => 'publish',
                'meta_input' => $meta_input,
            );
        }
    }

    if (function_exists('kingy_ali_model_static_compare_page_configs')) {
        foreach (kingy_ali_model_static_compare_page_configs() as $key => $config) {
            if (empty($config['path'])) {
                continue;
            }

            $meta_input = array();
            if (!empty($config['noindex'])) {
                $meta_input[kingy_ali_meta_key('noindex')] = '1';
            }

            $title = isset($config['title']) ? $config['title'] : kingy_ali_page_title_from_path($config['path']);
            $pages['model_static_compare_' . sanitize_key($key)] = array(
                'path' => $config['path'],
                'title' => $title,
                'content' => kingy_ali_shortcode_block('[kingy_ai_model_static_compare key="' . esc_attr($key) . '" heading="no"]'),
                'post_status' => 'publish',
                'meta_input' => $meta_input,
            );
        }
    }

    if (function_exists('kingy_ali_ai_launch_academy_recommended_pages')) {
        $pages = array_merge($pages, kingy_ali_ai_launch_academy_recommended_pages());
    }

    return $pages;
}

function kingy_ali_apply_recommended_page_meta($post_id, $page_meta_input) {
    $updated = false;

    foreach ($page_meta_input as $meta_key => $meta_value) {
        if (!is_string($meta_key) || $meta_key === '') {
            continue;
        }

        if (get_post_meta($post_id, $meta_key, true) === $meta_value) {
            continue;
        }

        update_post_meta($post_id, $meta_key, $meta_value);
        $updated = true;
    }

    return $updated;
}

function kingy_ali_install_recommended_pages($repair_managed_pages = false) {
    $results = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    );

    foreach (kingy_ali_recommended_pages() as $key => $page) {
        $existing = kingy_ali_find_page_by_path($page['path']);
        $post_status = isset($page['post_status']) ? sanitize_key($page['post_status']) : 'publish';
        if (!in_array($post_status, array('publish', 'draft'), true)) {
            $post_status = 'publish';
        }
        $page_meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
        if ($existing) {
            $is_managed = get_post_meta($existing->ID, kingy_ali_meta_key('managed_page'), true);
            $meta_updated = kingy_ali_apply_recommended_page_meta($existing->ID, $page_meta_input);
            if ($repair_managed_pages && $is_managed) {
                wp_update_post(
                    array(
                        'ID' => $existing->ID,
                        'post_status' => $post_status,
                        'post_title' => $page['title'],
                        'post_content' => $page['content'],
                    )
                );
                update_post_meta($existing->ID, kingy_ali_meta_key('page_key'), $key);
                $results['updated']++;
            } else {
                $results[$meta_updated ? 'updated' : 'skipped']++;
            }
            continue;
        }

        $parent_id = kingy_ali_parent_page_id_for_path($page['path']);
        $slug_parts = explode('/', trim($page['path'], '/'));
        $slug = end($slug_parts);
        $managed_page = kingy_ali_find_managed_page_by_key($key);
        if ($managed_page) {
            $updated_page_id = wp_update_post(
                array(
                    'ID' => $managed_page->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_name' => sanitize_title($slug),
                    'post_parent' => $parent_id,
                    'post_content' => $page['content'],
                ),
                true
            );
            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }
            update_post_meta($managed_page->ID, kingy_ali_meta_key('page_key'), $key);
            kingy_ali_apply_recommended_page_meta($managed_page->ID, $page_meta_input);
            $results['updated']++;
            continue;
        }

        $meta_input = array_merge(
            array(
                kingy_ali_meta_key('managed_page') => '1',
                kingy_ali_meta_key('page_key') => $key,
            ),
            $page_meta_input
        );

        $post_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => $post_status,
                'post_title' => $page['title'],
                'post_name' => sanitize_title($slug),
                'post_parent' => $parent_id,
                'post_content' => $page['content'],
                'meta_input' => $meta_input,
            ),
            true
        );

        if (is_wp_error($post_id)) {
            $results['skipped']++;
            continue;
        }

        $results['created']++;
    }

    update_option('kingy_ali_page_install_results', $results, false);
    return $results;
}

function kingy_ali_ensure_seo_health_pages() {
    $pages = kingy_ali_recommended_pages();
    if (empty($pages['ai_courses'])) {
        return;
    }

    $page = $pages['ai_courses'];
    $existing = kingy_ali_find_page_by_path($page['path']);
    if ($existing) {
        return;
    }

    $managed_page = kingy_ali_find_managed_page_by_key('ai_courses');
    if ($managed_page) {
        return;
    }

    $post_id = wp_insert_post(
        array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => sanitize_title($page['path']),
            'post_content' => $page['content'],
            'meta_input' => array(
                kingy_ali_meta_key('managed_page') => '1',
                kingy_ali_meta_key('page_key') => 'ai_courses',
            ),
        ),
        true
    );

    if (!is_wp_error($post_id)) {
        update_option('kingy_ali_ai_courses_page_created', absint($post_id), false);
    }
}

function kingy_ali_ensure_ai_launch_scorecard_page() {
    $pages = kingy_ali_recommended_pages();
    if (empty($pages['ai_launch_scorecard'])) {
        return;
    }

    $page = $pages['ai_launch_scorecard'];
    $existing = kingy_ali_find_page_by_path($page['path']);
    if ($existing) {
        return;
    }

    $managed_page = kingy_ali_find_managed_page_by_key('ai_launch_scorecard');
    if ($managed_page) {
        return;
    }

    $post_id = wp_insert_post(
        array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => sanitize_title($page['path']),
            'post_content' => $page['content'],
            'meta_input' => array(
                kingy_ali_meta_key('managed_page') => '1',
                kingy_ali_meta_key('page_key') => 'ai_launch_scorecard',
            ),
        ),
        true
    );

    if (!is_wp_error($post_id)) {
        update_option('kingy_ali_ai_launch_scorecard_page_created', absint($post_id), false);
    }
}

function kingy_ali_ensure_model_growth_pages() {
    kingy_ali_ensure_model_landing_pages();
    kingy_ali_ensure_model_static_compare_pages();
}

function kingy_ali_ensure_model_intelligence_pages() {
    $pages = kingy_ali_recommended_pages();
    if (empty($pages['compare_ai_models'])) {
        kingy_ali_ensure_model_growth_pages();
        return;
    }

    $page = $pages['compare_ai_models'];
    $page_meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
    $page_meta_input = array_merge(
        array(
            kingy_ali_meta_key('managed_page') => '1',
            kingy_ali_meta_key('page_key') => 'compare_ai_models',
        ),
        $page_meta_input
    );

    $existing = kingy_ali_find_page_by_path($page['path']);
    if ($existing) {
        $is_managed = get_post_meta($existing->ID, kingy_ali_meta_key('managed_page'), true) === '1';
        $has_compare_shortcode = has_shortcode((string) $existing->post_content, 'kingy_ai_model_compare');
        $is_safe_to_repair = $is_managed || $has_compare_shortcode || $existing->post_status !== 'publish';

        if ($is_safe_to_repair) {
            wp_update_post(
                array(
                    'ID' => $existing->ID,
                    'post_status' => 'publish',
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                )
            );
        }

        kingy_ali_apply_recommended_page_meta($existing->ID, $page_meta_input);
        update_option('kingy_ali_compare_ai_models_page_checked', absint($existing->ID), false);
        kingy_ali_ensure_model_growth_pages();
        return;
    }

    $managed_page = kingy_ali_find_managed_page_by_key('compare_ai_models');
    if ($managed_page) {
        $updated_page_id = wp_update_post(
            array(
                'ID' => $managed_page->ID,
                'post_status' => 'publish',
                'post_title' => $page['title'],
                'post_name' => 'compare-ai-models',
                'post_parent' => 0,
                'post_content' => $page['content'],
            ),
            true
        );

        if (!is_wp_error($updated_page_id)) {
            kingy_ali_apply_recommended_page_meta($managed_page->ID, $page_meta_input);
            update_option('kingy_ali_compare_ai_models_page_checked', absint($managed_page->ID), false);
        }
        kingy_ali_ensure_model_growth_pages();
        return;
    }

    $post_id = wp_insert_post(
        array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => 'compare-ai-models',
            'post_content' => $page['content'],
            'meta_input' => $page_meta_input,
        ),
        true
    );

    if (!is_wp_error($post_id)) {
        update_option('kingy_ali_compare_ai_models_page_created', absint($post_id), false);
    }

    kingy_ali_ensure_model_growth_pages();
}

function kingy_ali_model_landing_pages_check_token() {
    $version = defined('KINGY_ALI_VERSION') ? KINGY_ALI_VERSION : 'unknown';
    return $version . ':model_landing_pages_v1';
}

function kingy_ali_maybe_ensure_model_landing_pages() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->base, array('post', 'post-new'), true)) {
        return;
    }

    if (!function_exists('kingy_ali_model_landing_page_configs') || !kingy_ali_model_landing_page_configs()) {
        return;
    }

    $check_token = kingy_ali_model_landing_pages_check_token();
    if (get_option('kingy_ali_model_landing_pages_checked', '') === $check_token) {
        return;
    }

    kingy_ali_ensure_model_landing_pages();
    update_option('kingy_ali_model_landing_pages_checked', $check_token, false);
}

function kingy_ali_model_landing_page_definitions() {
    $definitions = array();

    foreach (kingy_ali_recommended_pages() as $key => $page) {
        if (strpos((string) $key, 'model_landing_') !== 0 || empty($page['path'])) {
            continue;
        }

        $definitions[$key] = $page;
    }

    return $definitions;
}

function kingy_ali_model_landing_page_should_noindex($page) {
    $meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
    $noindex_key = kingy_ali_meta_key('noindex');
    return isset($meta_input[$noindex_key]) && (string) $meta_input[$noindex_key] === '1';
}

function kingy_ali_apply_model_landing_page_noindex_meta($post_id, $page, $safe_to_repair = true) {
    $post_id = absint($post_id);
    if (!$post_id || !$safe_to_repair) {
        return;
    }

    $noindex_key = kingy_ali_meta_key('noindex');
    if (kingy_ali_model_landing_page_should_noindex($page)) {
        update_post_meta($post_id, $noindex_key, '1');
    } else {
        delete_post_meta($post_id, $noindex_key);
    }
}

function kingy_ali_model_landing_page_slug_and_parent($path) {
    $path = trim((string) $path, '/');
    $slug_parts = explode('/', $path);
    $slug = sanitize_title(end($slug_parts));

    return array(
        'slug' => $slug,
        'parent_id' => kingy_ali_parent_page_id_for_path($path),
    );
}

function kingy_ali_ensure_model_landing_pages() {
    $results = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    );

    if (!function_exists('kingy_ali_model_landing_page_configs')) {
        return $results;
    }

    $pages = kingy_ali_model_landing_page_definitions();
    if (!$pages) {
        return $results;
    }

    foreach ($pages as $key => $page) {
        $path = isset($page['path']) ? trim((string) $page['path'], '/') : '';
        if ($path === '') {
            $results['skipped']++;
            continue;
        }

        $post_status = isset($page['post_status']) ? sanitize_key($page['post_status']) : 'publish';
        if (!in_array($post_status, array('publish', 'draft'), true)) {
            $post_status = 'publish';
        }

        $page_meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
        $managed_meta_input = array_merge(
            array(
                kingy_ali_meta_key('managed_page') => '1',
                kingy_ali_meta_key('page_key') => sanitize_key($key),
            ),
            $page_meta_input
        );
        $slug_data = kingy_ali_model_landing_page_slug_and_parent($path);

        $existing = kingy_ali_find_page_by_path($path);
        if ($existing) {
            $is_managed = get_post_meta($existing->ID, kingy_ali_meta_key('managed_page'), true) === '1';
            $has_landing_shortcode = function_exists('has_shortcode')
                ? has_shortcode((string) $existing->post_content, 'kingy_ai_model_landing')
                : strpos((string) $existing->post_content, '[kingy_ai_model_landing') !== false;
            if (!$has_landing_shortcode) {
                $has_landing_shortcode = strpos((string) $existing->post_content, '[kingy_ai_model_landing') !== false;
            }

            $is_safe_to_repair = $is_managed || $has_landing_shortcode || $existing->post_status !== 'publish';
            if (!$is_safe_to_repair) {
                $results['skipped']++;
                continue;
            }

            $updated_page_id = wp_update_post(
                array(
                    'ID' => $existing->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                ),
                true
            );

            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }

            kingy_ali_apply_recommended_page_meta($existing->ID, $managed_meta_input);
            kingy_ali_apply_model_landing_page_noindex_meta($existing->ID, $page, true);
            $results['updated']++;
            continue;
        }

        $managed_page = kingy_ali_find_managed_page_by_key($key);
        if ($managed_page) {
            $updated_page_id = wp_update_post(
                array(
                    'ID' => $managed_page->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_name' => $slug_data['slug'],
                    'post_parent' => $slug_data['parent_id'],
                    'post_content' => $page['content'],
                ),
                true
            );

            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }

            kingy_ali_apply_recommended_page_meta($managed_page->ID, $managed_meta_input);
            kingy_ali_apply_model_landing_page_noindex_meta($managed_page->ID, $page, true);
            $results['updated']++;
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => $post_status,
                'post_title' => $page['title'],
                'post_name' => $slug_data['slug'],
                'post_parent' => $slug_data['parent_id'],
                'post_content' => $page['content'],
                'meta_input' => $managed_meta_input,
            ),
            true
        );

        if (is_wp_error($post_id)) {
            $results['skipped']++;
            continue;
        }

        kingy_ali_apply_model_landing_page_noindex_meta($post_id, $page, true);
        $results['created']++;
    }

    update_option('kingy_ali_model_landing_page_install_results', $results, false);
    return $results;
}

function kingy_ali_model_static_compare_pages_check_token() {
    $version = defined('KINGY_ALI_VERSION') ? KINGY_ALI_VERSION : 'unknown';
    return $version . ':model_static_compare_pages_v1';
}

function kingy_ali_maybe_ensure_model_static_compare_pages() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->base, array('post', 'post-new'), true)) {
        return;
    }

    if (!function_exists('kingy_ali_model_static_compare_page_configs') || !kingy_ali_model_static_compare_page_configs()) {
        return;
    }

    $check_token = kingy_ali_model_static_compare_pages_check_token();
    if (get_option('kingy_ali_model_static_compare_pages_checked', '') === $check_token) {
        return;
    }

    kingy_ali_ensure_model_static_compare_pages();
    update_option('kingy_ali_model_static_compare_pages_checked', $check_token, false);
}

function kingy_ali_model_static_compare_page_definitions() {
    $definitions = array();

    foreach (kingy_ali_recommended_pages() as $key => $page) {
        if (strpos((string) $key, 'model_static_compare_') !== 0 || empty($page['path'])) {
            continue;
        }

        $definitions[$key] = $page;
    }

    return $definitions;
}

function kingy_ali_model_static_compare_page_should_noindex($page) {
    $meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
    $noindex_key = kingy_ali_meta_key('noindex');
    return isset($meta_input[$noindex_key]) && (string) $meta_input[$noindex_key] === '1';
}

function kingy_ali_apply_model_static_compare_page_noindex_meta($post_id, $page, $safe_to_repair = true) {
    $post_id = absint($post_id);
    if (!$post_id || !$safe_to_repair) {
        return;
    }

    $noindex_key = kingy_ali_meta_key('noindex');
    if (kingy_ali_model_static_compare_page_should_noindex($page)) {
        update_post_meta($post_id, $noindex_key, '1');
    } else {
        delete_post_meta($post_id, $noindex_key);
    }
}

function kingy_ali_ensure_model_static_compare_pages() {
    $results = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
    );

    if (!function_exists('kingy_ali_model_static_compare_page_configs')) {
        return $results;
    }

    $pages = kingy_ali_model_static_compare_page_definitions();
    if (!$pages) {
        return $results;
    }

    foreach ($pages as $key => $page) {
        $path = isset($page['path']) ? trim((string) $page['path'], '/') : '';
        if ($path === '') {
            $results['skipped']++;
            continue;
        }

        $post_status = isset($page['post_status']) ? sanitize_key($page['post_status']) : 'publish';
        if (!in_array($post_status, array('publish', 'draft'), true)) {
            $post_status = 'publish';
        }

        $page_meta_input = isset($page['meta_input']) && is_array($page['meta_input']) ? $page['meta_input'] : array();
        $managed_meta_input = array_merge(
            array(
                kingy_ali_meta_key('managed_page') => '1',
                kingy_ali_meta_key('page_key') => sanitize_key($key),
            ),
            $page_meta_input
        );
        $slug_data = kingy_ali_model_landing_page_slug_and_parent($path);

        $existing = kingy_ali_find_page_by_path($path);
        if ($existing) {
            $is_managed = get_post_meta($existing->ID, kingy_ali_meta_key('managed_page'), true) === '1';
            $has_compare_shortcode = function_exists('has_shortcode')
                ? has_shortcode((string) $existing->post_content, 'kingy_ai_model_static_compare')
                : strpos((string) $existing->post_content, '[kingy_ai_model_static_compare') !== false;
            if (!$has_compare_shortcode) {
                $has_compare_shortcode = strpos((string) $existing->post_content, '[kingy_ai_model_static_compare') !== false;
            }

            $is_safe_to_repair = $is_managed || $has_compare_shortcode || $existing->post_status !== 'publish';
            if (!$is_safe_to_repair) {
                $results['skipped']++;
                continue;
            }

            $updated_page_id = wp_update_post(
                array(
                    'ID' => $existing->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                ),
                true
            );

            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }

            kingy_ali_apply_recommended_page_meta($existing->ID, $managed_meta_input);
            kingy_ali_apply_model_static_compare_page_noindex_meta($existing->ID, $page, true);
            $results['updated']++;
            continue;
        }

        $managed_page = kingy_ali_find_managed_page_by_key($key);
        if ($managed_page) {
            $updated_page_id = wp_update_post(
                array(
                    'ID' => $managed_page->ID,
                    'post_status' => $post_status,
                    'post_title' => $page['title'],
                    'post_name' => $slug_data['slug'],
                    'post_parent' => $slug_data['parent_id'],
                    'post_content' => $page['content'],
                ),
                true
            );

            if (is_wp_error($updated_page_id)) {
                $results['skipped']++;
                continue;
            }

            kingy_ali_apply_recommended_page_meta($managed_page->ID, $managed_meta_input);
            kingy_ali_apply_model_static_compare_page_noindex_meta($managed_page->ID, $page, true);
            $results['updated']++;
            continue;
        }

        $post_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => $post_status,
                'post_title' => $page['title'],
                'post_name' => $slug_data['slug'],
                'post_parent' => $slug_data['parent_id'],
                'post_content' => $page['content'],
                'meta_input' => $managed_meta_input,
            ),
            true
        );

        if (is_wp_error($post_id)) {
            $results['skipped']++;
            continue;
        }

        kingy_ali_apply_model_static_compare_page_noindex_meta($post_id, $page, true);
        $results['created']++;
    }

    update_option('kingy_ali_model_static_compare_page_install_results', $results, false);
    return $results;
}

function kingy_ali_fix_ai_courses_menu_link($atts, $item, $args, $depth) {
    if (empty($atts['href']) || !is_object($item)) {
        return $atts;
    }

    $url = kingy_ali_sanitize_public_cta_url($atts['href']);
    if ($url === '') {
        return $atts;
    }

    $title = isset($item->title) ? trim(wp_strip_all_tags((string) $item->title)) : '';
    $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $target_path = kingy_ali_phase1_menu_link_target_path($path, $title);
    if ($target_path === '') {
        return $atts;
    }

    $atts['href'] = home_url('/' . trim($target_path, '/') . '/');
    return $atts;
}

function kingy_ali_phase1_menu_link_target_path($path, $title = '') {
    return function_exists('kingy_ali_phase1_legacy_internal_link_target_path')
        ? kingy_ali_phase1_legacy_internal_link_target_path($path, $title)
        : '';
}

function kingy_ali_find_page_by_path($path) {
    return get_page_by_path(trim($path, '/'), OBJECT, 'page');
}

function kingy_ali_find_managed_page_by_key($key) {
    $pages = get_posts(
        array(
            'post_type' => 'page',
            'post_status' => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => kingy_ali_meta_key('managed_page'),
                    'value' => '1',
                ),
                array(
                    'key' => kingy_ali_meta_key('page_key'),
                    'value' => sanitize_key($key),
                ),
            ),
        )
    );

    return $pages ? $pages[0] : null;
}

function kingy_ali_recommended_page_shortcode_tag($page) {
    $content = isset($page['content']) ? (string) $page['content'] : '';
    if (!preg_match('/\[\s*([a-zA-Z0-9_-]+)/', $content, $matches)) {
        return '';
    }

    return sanitize_key($matches[1]);
}

function kingy_ali_recommended_page_status($page) {
    $existing = kingy_ali_find_page_by_path($page['path']);
    if (!$existing) {
        return array(
            'state' => 'missing',
            'ready' => false,
            'label' => __('Missing', 'kingy-ai-launch-intelligence'),
            'post_id' => 0,
        );
    }

    if ($existing->post_status !== 'publish') {
        return array(
            'state' => 'not_published',
            'ready' => false,
            'label' => sprintf(__('Exists, not published (%s)', 'kingy-ai-launch-intelligence'), $existing->post_status),
            'post_id' => absint($existing->ID),
        );
    }

    $shortcode_tag = kingy_ali_recommended_page_shortcode_tag($page);
    $content = isset($existing->post_content) ? (string) $existing->post_content : '';
    $has_expected_shortcode = $shortcode_tag === '';
    if (!$has_expected_shortcode && function_exists('has_shortcode')) {
        $has_expected_shortcode = has_shortcode($content, $shortcode_tag);
    }
    if (!$has_expected_shortcode && $shortcode_tag !== '') {
        $has_expected_shortcode = strpos($content, '[' . $shortcode_tag) !== false;
    }

    if (!$has_expected_shortcode) {
        return array(
            'state' => 'shortcode_missing',
            'ready' => false,
            'label' => __('Exists, expected shortcode missing', 'kingy-ai-launch-intelligence'),
            'post_id' => absint($existing->ID),
        );
    }

    $is_managed = get_post_meta($existing->ID, kingy_ali_meta_key('managed_page'), true);
    return array(
        'state' => $is_managed ? 'managed' : 'custom',
        'ready' => true,
        'label' => $is_managed ? __('Exists, package-managed', 'kingy-ai-launch-intelligence') : __('Exists, left untouched', 'kingy-ai-launch-intelligence'),
        'post_id' => absint($existing->ID),
    );
}

function kingy_ali_mvp_page_groups() {
    return array(
        array(
            'label' => __('Launch hub', 'kingy-ai-launch-intelligence'),
            'keys' => array('hub'),
            'note' => __('Main searchable Launch Intelligence page.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Daily and weekly pages', 'kingy-ai-launch-intelligence'),
            'keys' => array('today', 'this_week'),
            'note' => __('Fresh launch views for today and this week.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Curated category pages', 'kingy-ai-launch-intelligence'),
            'keys' => array('ai_agents', 'ai_video_tools', 'ai_coding_tools', 'ai_image_tools', 'open_weight_models'),
            'note' => __('Five seeded category destinations for the first public launch hub.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Creator discovery pages', 'kingy-ai-launch-intelligence'),
            'keys' => array('youtube_worthy', 'creator_coverage'),
            'note' => __('YouTube-worthy and creator-coverage shortlist surfaces.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Launch signal pages', 'kingy-ai-launch-intelligence'),
            'keys' => array('founder_submitted', 'funding_announcements'),
            'note' => __('Clean launch-signal landing pages for founder-submitted and funding announcement records.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Founder intake pages', 'kingy-ai-launch-intelligence'),
            'keys' => array('submit', 'visibility_score', 'ai_launch_scorecard'),
            'note' => __('Submission form, Launch Visibility Score calculator, and AI Launch Scorecard.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Creator campaign ROI page', 'kingy-ai-launch-intelligence'),
            'keys' => array('creator_campaign_roi'),
            'note' => __('Optional ROI follow-up page connected from hub and calculator CTAs.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_mvp_page_readiness_rows() {
    $pages = kingy_ali_recommended_pages();
    $rows = array();

    foreach (kingy_ali_mvp_page_groups() as $group) {
        $ready = 0;
        $missing = array();
        foreach ($group['keys'] as $key) {
            if (empty($pages[$key])) {
                continue;
            }

            $status = kingy_ali_recommended_page_status($pages[$key]);
            if (!empty($status['ready'])) {
                $ready++;
            } else {
                $missing[] = $pages[$key]['title'];
            }
        }

        $target = count($group['keys']);
        $rows[] = array(
            'label' => $group['label'],
            'note' => $group['note'],
            'ready' => $ready,
            'target' => $target,
            'missing' => $missing,
        );
    }

    return $rows;
}

function kingy_ali_render_mvp_page_readiness_panel() {
    $rows = kingy_ali_mvp_page_readiness_rows();

    echo '<h2>' . esc_html__('MVP Page Readiness', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Surface group', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Ready', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Status', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Notes', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $is_ready = $row['ready'] >= $row['target'];
        echo '<tr>';
        echo '<td><strong>' . esc_html($row['label']) . '</strong></td>';
        echo '<td>' . esc_html(sprintf(__('%1$d / %2$d', 'kingy-ai-launch-intelligence'), absint($row['ready']), absint($row['target']))) . '</td>';
        echo '<td>' . esc_html($is_ready ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs page check', 'kingy-ai-launch-intelligence')) . '</td>';
        echo '<td>';
        echo esc_html($row['note']);
        if (!$is_ready && $row['missing']) {
            echo '<br><small>' . esc_html(sprintf(__('Needs attention: %s', 'kingy-ai-launch-intelligence'), implode(', ', $row['missing']))) . '</small>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_setup_page_action_links($page, $status) {
    if (empty($status['post_id'])) {
        return esc_html__('Create with page check', 'kingy-ai-launch-intelligence');
    }

    $post_id = absint($status['post_id']);
    $links = array();
    $edit_link = get_edit_post_link($post_id);
    if ($edit_link) {
        $links[] = '<a href="' . esc_url($edit_link) . '">' . esc_html__('Edit', 'kingy-ai-launch-intelligence') . '</a>';
    }

    $permalink = get_permalink($post_id);
    if ($permalink) {
        $links[] = '<a href="' . esc_url($permalink) . '">' . esc_html__('View', 'kingy-ai-launch-intelligence') . '</a>';
    }

    if (empty($status['ready'])) {
        $links[] = esc_html__('Run page check to repair managed content', 'kingy-ai-launch-intelligence');
    }

    return $links ? implode(' | ', $links) : esc_html__('—', 'kingy-ai-launch-intelligence');
}

function kingy_ali_parent_page_id_for_path($path) {
    $parts = explode('/', trim($path, '/'));
    array_pop($parts);
    if (empty($parts)) {
        return 0;
    }

    $parent_id = 0;
    $path_parts = array();
    foreach ($parts as $part) {
        $slug = sanitize_title($part);
        if ($slug === '') {
            continue;
        }

        $path_parts[] = $slug;
        $existing = kingy_ali_find_page_by_path(implode('/', $path_parts));
        if ($existing) {
            $parent_id = (int) $existing->ID;
            continue;
        }

        $created_parent_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => kingy_ali_managed_parent_page_title($slug),
                'post_name' => $slug,
                'post_parent' => $parent_id,
                'post_content' => '',
                'meta_input' => array(
                    kingy_ali_meta_key('managed_parent_page') => '1',
                ),
            ),
            true
        );

        if (is_wp_error($created_parent_id)) {
            return $parent_id;
        }

        $parent_id = (int) $created_parent_id;
    }

    return $parent_id;
}

function kingy_ali_managed_parent_page_title($slug) {
    $special_words = array(
        'ai' => 'AI',
        'qa' => 'QA',
        'roi' => 'ROI',
        'seo' => 'SEO',
    );
    $words = array();
    foreach (explode('-', sanitize_title($slug)) as $word) {
        if ($word === '') {
            continue;
        }

        $words[] = isset($special_words[$word]) ? $special_words[$word] : ucfirst($word);
    }

    return $words ? implode(' ', $words) : __('Managed Page', 'kingy-ai-launch-intelligence');
}

function kingy_ali_pretty_permalinks_enabled() {
    return trim((string) get_option('permalink_structure')) !== '';
}

function kingy_ali_public_url_examples() {
    return array(
        array(
            'label' => __('AI Launch profile', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-launch/{launch-slug}/',
            'source' => __('AI Launch post type rewrite', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Tool directory', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-tools/',
            'source' => __('AI Tool post type archive', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Tool profile', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-tools/{tool-slug}/',
            'source' => __('AI Tool post type rewrite', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Company directory', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-companies/',
            'source' => __('AI Company post type archive', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Company profile', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-companies/{company-slug}/',
            'source' => __('AI Company post type rewrite', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Model hub', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-models/',
            'source' => __('AI Model post type archive', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('AI Model profile', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-models/{model-slug}/',
            'source' => __('AI Model post type rewrite', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Compare AI Models', 'kingy-ai-launch-intelligence'),
            'path' => '/compare-ai-models/',
            'source' => __('Managed noindex comparison page until source-backed comparisons are ready', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Launch hub', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-launches/',
            'source' => __('Setup Pages hub', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Today', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-launches/today/',
            'source' => __('Setup Pages daily page', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('This week', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-launches/this-week/',
            'source' => __('Setup Pages weekly page', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'label' => __('Curated categories', 'kingy-ai-launch-intelligence'),
            'path' => '/ai-launches/{category-slug}/',
            'source' => __('Setup Pages category pages', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_render_permalink_readiness_panel() {
    $pretty_permalinks_enabled = kingy_ali_pretty_permalinks_enabled();
    ?>
    <h2><?php esc_html_e('Public URL Readiness', 'kingy-ai-launch-intelligence'); ?></h2>
    <?php if ($pretty_permalinks_enabled) : ?>
        <div class="notice notice-success inline">
            <p><?php esc_html_e('Pretty permalinks are active, so the MVP public paths can use the intended /ai-launch/ and /ai-launches/ URL structure.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
    <?php else : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php esc_html_e('Plain permalinks are active. Public MVP URLs such as', 'kingy-ai-launch-intelligence'); ?>
                <code>/ai-launch/example-launch/</code>
                <?php esc_html_e('and', 'kingy-ai-launch-intelligence'); ?>
                <code>/ai-launches/</code>
                <?php esc_html_e('need a non-plain permalink structure.', 'kingy-ai-launch-intelligence'); ?>
                <a href="<?php echo esc_url(admin_url('options-permalink.php')); ?>"><?php esc_html_e('Open Settings > Permalinks', 'kingy-ai-launch-intelligence'); ?></a>
                <?php esc_html_e('and save Post name or another pretty format.', 'kingy-ai-launch-intelligence'); ?>
            </p>
        </div>
    <?php endif; ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Surface', 'kingy-ai-launch-intelligence'); ?></th>
                <th><?php esc_html_e('Expected path', 'kingy-ai-launch-intelligence'); ?></th>
                <th><?php esc_html_e('Managed by', 'kingy-ai-launch-intelligence'); ?></th>
                <th><?php esc_html_e('Readiness', 'kingy-ai-launch-intelligence'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (kingy_ali_public_url_examples() as $example) : ?>
                <tr>
                    <td><?php echo esc_html($example['label']); ?></td>
                    <td><code><?php echo esc_html($example['path']); ?></code></td>
                    <td><?php echo esc_html($example['source']); ?></td>
                    <td>
                        <?php
                        echo $pretty_permalinks_enabled
                            ? esc_html__('Ready for pretty public URLs', 'kingy-ai-launch-intelligence')
                            : esc_html__('Needs pretty permalinks', 'kingy-ai-launch-intelligence');
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_handle_install_pages() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to create Launch Intelligence pages.', 'kingy-ai-launch-intelligence'));
    }

    $nonce = kingy_ali_setup_pages_post_value('kingy_ali_setup_pages_nonce');
    $nonce_action = 'kingy_ali_setup_pages';
    if ($nonce === '') {
        $nonce = kingy_ali_setup_pages_post_value('kingy_ali_install_pages_nonce');
        $nonce_action = 'kingy_ali_install_pages';
    }

    if (!wp_verify_nonce(sanitize_text_field($nonce), $nonce_action)) {
        wp_die(esc_html__('Page setup nonce check failed.', 'kingy-ai-launch-intelligence'));
    }

    $repair = kingy_ali_setup_pages_post_value('repair_managed_pages') === '1';
    $results = kingy_ali_install_recommended_pages($repair);

    wp_safe_redirect(
        add_query_arg(
            array(
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
            ),
            admin_url('admin.php?page=kingy-ali-setup-pages')
        )
    );
    exit;
}

function kingy_ali_setup_pages_post_value($key) {
    $values = kingy_ali_setup_pages_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_setup_pages_get_value($key) {
    $values = kingy_ali_setup_pages_get_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_setup_pages_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_setup_pages_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_render_setup_pages_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $created_count = kingy_ali_setup_pages_get_value('created');
    $updated_count = kingy_ali_setup_pages_get_value('updated');
    $skipped_count = kingy_ali_setup_pages_get_value('skipped');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Launch Intelligence Pages', 'kingy-ai-launch-intelligence'); ?></h1>
        <?php if ($created_count !== '' && $updated_count !== '' && $skipped_count !== '') : ?>
            <div class="notice notice-success">
                <p><?php echo esc_html(sprintf(__('Page setup complete. Created: %d. Updated: %d. Skipped: %d.', 'kingy-ai-launch-intelligence'), absint($created_count), absint($updated_count), absint($skipped_count))); ?></p>
            </div>
        <?php endif; ?>
        <p><?php esc_html_e('This creates missing public MVP pages without overwriting existing pages. Repair only updates package-managed pages.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php kingy_ali_render_permalink_readiness_panel(); ?>
        <?php kingy_ali_render_mvp_page_readiness_panel(); ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kingy_ali_setup_pages', 'kingy_ali_setup_pages_nonce'); ?>
            <input type="hidden" name="action" value="kingy_ali_setup_pages">
            <p>
                <label>
                    <input type="checkbox" name="repair_managed_pages" value="1">
                    <?php esc_html_e('Repair shortcode content on package-managed pages', 'kingy-ai-launch-intelligence'); ?>
                </label>
            </p>
            <p><button class="button button-primary" type="submit"><?php esc_html_e('Create Missing Pages', 'kingy-ai-launch-intelligence'); ?></button></p>
        </form>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Page', 'kingy-ai-launch-intelligence'); ?></th>
                    <th><?php esc_html_e('Path', 'kingy-ai-launch-intelligence'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'kingy-ai-launch-intelligence'); ?></th>
                    <th><?php esc_html_e('Status', 'kingy-ai-launch-intelligence'); ?></th>
                    <th><?php esc_html_e('Actions', 'kingy-ai-launch-intelligence'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (kingy_ali_recommended_pages() as $page) : ?>
                    <?php $status = kingy_ali_recommended_page_status($page); ?>
                    <tr>
                        <td><?php echo esc_html($page['title']); ?></td>
                        <td><code><?php echo esc_html('/' . trim($page['path'], '/') . '/'); ?></code></td>
                        <td><code><?php echo esc_html($page['content']); ?></code></td>
                        <td><?php echo esc_html($status['label']); ?></td>
                        <td><?php echo kingy_ali_setup_page_action_links($page, $status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
