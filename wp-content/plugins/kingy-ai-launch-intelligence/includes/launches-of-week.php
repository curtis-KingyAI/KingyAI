<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_launches_of_week_hub', 'kingy_ali_shortcode_launches_of_week_hub');
add_action('init', 'kingy_ali_register_launches_of_week_post_meta');
add_action('add_meta_boxes', 'kingy_ali_add_launches_of_week_post_meta_box');
add_action('save_post_post', 'kingy_ali_save_launches_of_week_post_meta');
add_action('admin_menu', 'kingy_ali_register_launches_of_week_admin_page');
add_filter('wpseo_title', 'kingy_ali_launches_of_week_post_wpseo_title');
add_filter('wpseo_metadesc', 'kingy_ali_launches_of_week_post_wpseo_description');

function kingy_ali_launches_of_week_award_options() {
    return array(
        '' => __('No weekly award selected', 'kingy-ai-launch-intelligence'),
        'best_overall_ai_launch' => __('Best Overall AI Launch', 'kingy-ai-launch-intelligence'),
        'best_ai_agent_launch' => __('Best AI Agent Launch', 'kingy-ai-launch-intelligence'),
        'best_ai_coding_tool_launch' => __('Best AI Coding Tool Launch', 'kingy-ai-launch-intelligence'),
        'best_ai_video_tool_launch' => __('Best AI Video Tool Launch', 'kingy-ai-launch-intelligence'),
        'best_open_weight_model_launch' => __('Best Open-Weight / Open-Source Model Launch', 'kingy-ai-launch-intelligence'),
        'best_founder_submitted_launch' => __('Best Founder-Submitted Launch', 'kingy-ai-launch-intelligence'),
        'best_demo' => __('Best Demo', 'kingy-ai-launch-intelligence'),
        'best_pricing_clarity' => __('Best Pricing Clarity', 'kingy-ai-launch-intelligence'),
        'best_creator_coverage_fit' => __('Best Creator Coverage Fit', 'kingy-ai-launch-intelligence'),
        'most_under_the_radar_launch' => __('Most Under-the-Radar Launch', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_launches_of_week_editorial_note() {
    return __('Kingy AI Launches of the Week is an editorial selection based on public launch information, product clarity, demo quality, buyer usefulness, category relevance, and creator-coverage potential. Companies do not pay to be selected, and no company is required to link back to Kingy AI.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_launches_of_week_score_items() {
    return array(
        __('Clear launch announcement', 'kingy-ai-launch-intelligence') => 15,
        __('Product usefulness', 'kingy-ai-launch-intelligence') => 15,
        __('Demo quality', 'kingy-ai-launch-intelligence') => 15,
        __('Buyer clarity', 'kingy-ai-launch-intelligence') => 10,
        __('Pricing clarity', 'kingy-ai-launch-intelligence') => 10,
        __('Category importance', 'kingy-ai-launch-intelligence') => 10,
        __('Founder/company credibility', 'kingy-ai-launch-intelligence') => 10,
        __('Creator coverage potential', 'kingy-ai-launch-intelligence') => 10,
        __('Under-the-radar value', 'kingy-ai-launch-intelligence') => 5,
    );
}

function kingy_ali_shortcode_launches_of_week_hub() {
    kingy_ali_enqueue_assets();

    $heading_tag = function_exists('kingy_ali_current_page_path_is') && kingy_ali_current_page_path_is('ai-launches/launches-of-the-week') ? 'h2' : 'h1';
    $awards = kingy_ali_launches_of_week_award_options();
    unset($awards['']);

    ob_start();
    ?>
    <section class="kingy-ali-hub kingy-ali-launches-week-hub">
        <div class="kingy-ali-hero">
            <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI editorial awards', 'kingy-ai-launch-intelligence'); ?></p>
            <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
            <p><?php esc_html_e('A weekly editorial roundup of standout AI tools, agents, coding tools, video tools, open-weight models, founder-submitted products, demos, pricing clarity, creator coverage fit, and under-the-radar launches.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit launch from Launches of the Week hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_hub" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit your AI launch', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse this week launches from awards hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_hub" href="<?php echo esc_url(home_url('/ai-launches/this-week/')); ?>"><?php esc_html_e('Browse this week', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Score launch readiness from awards hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_hub" href="<?php echo esc_url(home_url('/ai-launch-scorecard/')); ?>"><?php esc_html_e('Score launch readiness', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </div>

        <section class="kingy-ali-content-band">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('What this is', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('A recognition layer for useful AI launches', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Each edition turns the launch database into a reader-friendly shortlist, with context for buyers, creators, founders, and operators who want to know what was actually worth noticing this week.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-content-grid">
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('For readers', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Get a compact weekly view of the AI launches with the clearest use cases, strongest demos, most useful pricing signals, and best source-backed product context.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('For founders', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Submit a launch with official links, a demo, pricing context, and honest limitations so Kingy AI can evaluate it for editorial coverage and possible weekly recognition.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('For sponsors', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Sponsorship and creator-campaign conversations stay separate from awards. Paid relationships do not guarantee placement in editorial winners.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
            </div>
        </section>

        <section class="kingy-ali-content-band">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Award categories', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Weekly winners', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The core set stays consistent so readers and companies understand the recurring editorial lens.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-tile-grid">
                <?php foreach ($awards as $award_key => $award_label) : ?>
                    <article class="kingy-ali-tile kingy-ali-tile--static">
                        <strong><?php echo esc_html($award_label); ?></strong>
                        <span><?php echo esc_html(kingy_ali_launches_of_week_award_description($award_key)); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-content-band">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Editorial criteria', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('100-point guidance model', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Scores are editorial heuristics. They help structure review; they are not scientific benchmarks, product-quality guarantees, or promises of coverage.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-table-wrap">
                <table class="kingy-ali-awards-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Signal', 'kingy-ai-launch-intelligence'); ?></th>
                            <th scope="col"><?php esc_html_e('Points', 'kingy-ai-launch-intelligence'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (kingy_ali_launches_of_week_score_items() as $label => $points) : ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td><?php echo esc_html((string) $points); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="kingy-ali-content-band kingy-ali-editorial-note">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Editorial note', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('White-hat recognition rules', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php echo esc_html(kingy_ali_launches_of_week_editorial_note()); ?></p>
            </div>
            <div class="kingy-ali-content-grid">
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('No required backlinks', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Featured companies may share the article if useful, but Kingy AI does not require links, reciprocal mentions, badge embeds, or payment for selection.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('Sponsored work is separate', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Sponsor inquiries are welcome, but they are disclosed and handled away from editorial awards so the weekly list stays useful and trustworthy.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
            </div>
        </section>

        <?php echo kingy_ali_render_launches_of_week_editions(); ?>
        <?php echo kingy_ali_render_launches_of_week_related_links(); ?>
        <?php echo kingy_ali_render_launches_of_week_sponsor_band(); ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_launches_of_week_award_description($award_key) {
    $descriptions = array(
        'best_overall_ai_launch' => __('The strongest launch across usefulness, clarity, source quality, and market relevance.', 'kingy-ai-launch-intelligence'),
        'best_ai_agent_launch' => __('A standout agent, workflow automation, browser agent, or autonomous work product.', 'kingy-ai-launch-intelligence'),
        'best_ai_coding_tool_launch' => __('A notable developer workflow, IDE agent, code model, PR, repo, testing, or debugging launch.', 'kingy-ai-launch-intelligence'),
        'best_ai_video_tool_launch' => __('A video generation, editing, avatar, demo, or creator workflow launch worth watching.', 'kingy-ai-launch-intelligence'),
        'best_open_weight_model_launch' => __('A model release with inspectable or downloadable artifacts and useful license context.', 'kingy-ai-launch-intelligence'),
        'best_founder_submitted_launch' => __('The strongest founder-submitted product after editorial review and source checking.', 'kingy-ai-launch-intelligence'),
        'best_demo' => __('The launch with the clearest, most useful, or most reviewable public demo.', 'kingy-ai-launch-intelligence'),
        'best_pricing_clarity' => __('A launch that makes pricing, free-plan status, access, or API details easy to understand.', 'kingy-ai-launch-intelligence'),
        'best_creator_coverage_fit' => __('A product with strong explainability, visual proof, workflow value, or creator-friendly story.', 'kingy-ai-launch-intelligence'),
        'most_under_the_radar_launch' => __('A useful launch that deserves more attention than it appears to be getting.', 'kingy-ai-launch-intelligence'),
    );

    return isset($descriptions[$award_key]) ? $descriptions[$award_key] : __('A recurring editorial award category for weekly AI launch recognition.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_render_launches_of_week_editions() {
    $query = kingy_ali_launches_of_week_query_editions(8);

    ob_start();
    ?>
    <section class="kingy-ali-content-band">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Archive', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Prior weekly editions', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Published weekly award posts marked as Launches of the Week editions appear here automatically.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <?php if ($query->have_posts()) : ?>
            <div class="kingy-ali-grid kingy-ali-awards-editions">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <article class="kingy-ali-card">
                        <div class="kingy-ali-card__meta">
                            <span><?php esc_html_e('Weekly edition', 'kingy-ai-launch-intelligence'); ?></span>
                            <?php echo kingy_ali_launches_of_week_edition_date_label(get_the_ID()); ?>
                        </div>
                        <h3><a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p>
                        <div class="kingy-ali-card__actions">
                            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Open Launches of the Week edition', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_archive" href="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Read edition', 'kingy-ai-launch-intelligence'); ?></a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="kingy-ali-empty">
                <h3><?php esc_html_e('No weekly editions are published yet.', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('The pilot system is ready. Mark each published post as a Launches of the Week edition in the post editor and it will appear here.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_launches_of_week_related_links() {
    $links = array(
        __('AI Launch Intelligence', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/'),
        __('AI Launch Tracker', 'kingy-ai-launch-intelligence') => home_url('/ai-launch-tracker/'),
        __('AI Tools', 'kingy-ai-launch-intelligence') => home_url('/ai-tools/'),
        __('AI Agent Launches', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/ai-agents/'),
        __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/ai-coding-tools/'),
        __('AI Video Tool Launches', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/ai-video-tools/'),
        __('AI Open-Weight Model Launches', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/open-weight-models/'),
        __('Founder-Submitted Tools', 'kingy-ai-launch-intelligence') => home_url('/ai-launches/founder-submitted-ai-tools/'),
        __('AI Launch Scorecard', 'kingy-ai-launch-intelligence') => home_url('/ai-launch-scorecard/'),
        __('Subscribe', 'kingy-ai-launch-intelligence') => home_url('/subscribe/'),
    );

    ob_start();
    ?>
    <section class="kingy-ali-content-band">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Related Kingy AI links', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Use the weekly awards with the launch database', 'kingy-ai-launch-intelligence'); ?></h2>
        </div>
        <div class="kingy-ali-link-list">
            <?php foreach ($links as $label => $url) : ?>
                <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($label); ?>" data-event-surface="launches_of_week_related" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_launches_of_week_sponsor_band() {
    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-company-path">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Sponsor path', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Sponsorship is separate from editorial awards', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('AI companies can contact Kingy AI about creator-led demos, education, or campaign work, but sponsored work does not buy weekly award placement.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Sponsor Kingy AI from awards hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_sponsor" href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from awards hub', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launches_of_week_sponsor" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_launches_of_week_query_editions($limit = 8) {
    return new WP_Query(
        array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($limit)),
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key('launches_of_week_edition'),
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        )
    );
}

function kingy_ali_launches_of_week_edition_date_label($post_id) {
    $start = get_post_meta($post_id, kingy_ali_meta_key('launches_of_week_start_date'), true);
    $end = get_post_meta($post_id, kingy_ali_meta_key('launches_of_week_end_date'), true);
    $start_label = kingy_ali_launches_of_week_admin_date_label($start);
    $end_label = kingy_ali_launches_of_week_admin_date_label($end);
    if (!$start_label && !$end_label) {
        return '<time datetime="' . esc_attr(get_the_date('c', $post_id)) . '">' . esc_html(get_the_date('', $post_id)) . '</time>';
    }

    $datetime = $start ? $start : get_the_date('Y-m-d', $post_id);
    $label = $start_label && $end_label ? sprintf(__('%1$s to %2$s', 'kingy-ai-launch-intelligence'), $start_label, $end_label) : ($start_label ? $start_label : $end_label);
    return '<time datetime="' . esc_attr($datetime) . '">' . esc_html($label) . '</time>';
}

function kingy_ali_launches_of_week_admin_date_label($value) {
    if (!is_scalar($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
        return '';
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : '';
}

function kingy_ali_register_launches_of_week_post_meta() {
    $fields = array(
        'launches_of_week_edition' => 'boolean',
        'launches_of_week_start_date' => 'string',
        'launches_of_week_end_date' => 'string',
    );

    foreach ($fields as $key => $type) {
        register_post_meta(
            'post',
            kingy_ali_meta_key($key),
            array(
                'single' => true,
                'type' => $type,
                'show_in_rest' => false,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
                'sanitize_callback' => function ($value) use ($key) {
                    if ($key === 'launches_of_week_edition') {
                        return !empty($value) ? '1' : '';
                    }
                    $value = is_scalar($value) ? sanitize_text_field((string) $value) : '';
                    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
                },
            )
        );
    }
}

function kingy_ali_add_launches_of_week_post_meta_box() {
    add_meta_box(
        'kingy_ali_launches_of_week_post',
        __('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'),
        'kingy_ali_render_launches_of_week_post_meta_box',
        'post',
        'side',
        'default'
    );
}

function kingy_ali_render_launches_of_week_post_meta_box($post) {
    wp_nonce_field('kingy_ali_launches_of_week_post_meta', 'kingy_ali_launches_of_week_post_meta_nonce');
    $is_edition = get_post_meta($post->ID, kingy_ali_meta_key('launches_of_week_edition'), true);
    $start_date = get_post_meta($post->ID, kingy_ali_meta_key('launches_of_week_start_date'), true);
    $end_date = get_post_meta($post->ID, kingy_ali_meta_key('launches_of_week_end_date'), true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="kingy_ali_launches_of_week_edition" value="1"<?php checked((bool) $is_edition, true); ?>>
            <?php esc_html_e('Mark as a Launches of the Week edition', 'kingy-ai-launch-intelligence'); ?>
        </label>
    </p>
    <p>
        <label for="kingy_ali_launches_of_week_start_date"><?php esc_html_e('Week start date', 'kingy-ai-launch-intelligence'); ?></label>
        <input class="widefat" id="kingy_ali_launches_of_week_start_date" type="date" name="kingy_ali_launches_of_week_start_date" value="<?php echo esc_attr($start_date); ?>">
    </p>
    <p>
        <label for="kingy_ali_launches_of_week_end_date"><?php esc_html_e('Week end date', 'kingy-ai-launch-intelligence'); ?></label>
        <input class="widefat" id="kingy_ali_launches_of_week_end_date" type="date" name="kingy_ali_launches_of_week_end_date" value="<?php echo esc_attr($end_date); ?>">
    </p>
    <p class="description"><?php esc_html_e('Marked editions appear automatically on the Launches of the Week hub. These fields are admin-only.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php
}

function kingy_ali_save_launches_of_week_post_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $nonce = isset($_POST['kingy_ali_launches_of_week_post_meta_nonce']) && is_scalar($_POST['kingy_ali_launches_of_week_post_meta_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['kingy_ali_launches_of_week_post_meta_nonce']))
        : '';

    if (!wp_verify_nonce($nonce, 'kingy_ali_launches_of_week_post_meta')) {
        return;
    }

    $is_edition = isset($_POST['kingy_ali_launches_of_week_edition']) ? '1' : '';
    $start_date = isset($_POST['kingy_ali_launches_of_week_start_date']) && is_scalar($_POST['kingy_ali_launches_of_week_start_date'])
        ? sanitize_text_field(wp_unslash($_POST['kingy_ali_launches_of_week_start_date']))
        : '';
    $end_date = isset($_POST['kingy_ali_launches_of_week_end_date']) && is_scalar($_POST['kingy_ali_launches_of_week_end_date'])
        ? sanitize_text_field(wp_unslash($_POST['kingy_ali_launches_of_week_end_date']))
        : '';

    $start_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) ? $start_date : '';
    $end_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date) ? $end_date : '';

    $is_edition ? update_post_meta($post_id, kingy_ali_meta_key('launches_of_week_edition'), '1') : delete_post_meta($post_id, kingy_ali_meta_key('launches_of_week_edition'));
    $start_date ? update_post_meta($post_id, kingy_ali_meta_key('launches_of_week_start_date'), $start_date) : delete_post_meta($post_id, kingy_ali_meta_key('launches_of_week_start_date'));
    $end_date ? update_post_meta($post_id, kingy_ali_meta_key('launches_of_week_end_date'), $end_date) : delete_post_meta($post_id, kingy_ali_meta_key('launches_of_week_end_date'));
}

function kingy_ali_register_launches_of_week_admin_page() {
    add_submenu_page(
        'edit.php?post_type=kingy_ai_launch',
        __('Launches of the Week', 'kingy-ai-launch-intelligence'),
        __('Launches of the Week', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-launches-of-week',
        'kingy_ali_render_launches_of_week_admin_page'
    );
}

function kingy_ali_render_launches_of_week_admin_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'kingy-ai-launch-intelligence'));
    }

    ?>
    <div class="wrap kingy-ali-admin-doc">
        <h1><?php esc_html_e('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Use this pilot workflow to publish weekly editorial awards, mark the post as an edition, notify featured companies, and track response signals without requiring backlinks.', 'kingy-ai-launch-intelligence'); ?></p>

        <h2><?php esc_html_e('Weekly publishing checklist', 'kingy-ai-launch-intelligence'); ?></h2>
        <ol>
            <li><?php esc_html_e('Review candidate launch records from this week, founder submissions, category pages, demos, pricing pages, and official sources.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Choose winners using the 100-point editorial guidance model. Treat scores as heuristics, not scientific benchmarks.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Publish a post titled "Kingy AI Launches of the Week: [Date Range]" and mark it as a Launches of the Week edition in the post editor.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Update each winner launch record with award category, edition URL, outreach contact, notification date, and response fields.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Send the notification email. Do not ask for backlinks, reciprocal links, or required badge embeds.', 'kingy-ai-launch-intelligence'); ?></li>
            <li><?php esc_html_e('Generate optional downloadable share images only. No forced HTML backlink badge code.', 'kingy-ai-launch-intelligence'); ?></li>
        </ol>

        <h2><?php esc_html_e('Reusable weekly post template', 'kingy-ai-launch-intelligence'); ?></h2>
        <textarea class="large-text code" rows="22" readonly><?php echo esc_textarea(kingy_ali_launches_of_week_post_template_text()); ?></textarea>

        <h2><?php esc_html_e('Notification email template', 'kingy-ai-launch-intelligence'); ?></h2>
        <textarea class="large-text code" rows="16" readonly><?php echo esc_textarea(kingy_ali_launches_of_week_notification_email_text()); ?></textarea>

        <h2><?php esc_html_e('Share asset prompts', 'kingy-ai-launch-intelligence'); ?></h2>
        <textarea class="large-text code" rows="12" readonly><?php echo esc_textarea(kingy_ali_launches_of_week_share_asset_prompt_text()); ?></textarea>

        <h2><?php esc_html_e('Editorial safety rule', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><strong><?php echo esc_html(kingy_ali_launches_of_week_editorial_note()); ?></strong></p>
    </div>
    <?php
}

function kingy_ali_launches_of_week_post_template_text() {
    return "SEO title: Kingy AI Launches of the Week: [Date Range]\n"
        . "Meta description: The best new AI launches this week across AI tools, agents, coding tools, video tools, open-weight models, demos, pricing clarity, founder submissions, and under-the-radar products.\n\n"
        . "# Kingy AI Launches of the Week: [Date Range]\n\n"
        . "Intro: This weekly Kingy AI roundup highlights useful, interesting, and demo-worthy AI launches across agents, coding tools, video tools, open models, infrastructure, research, productivity, and founder-submitted products.\n\n"
        . "## TL;DR: This Week's Winners\n\n"
        . "| Award | Winner | Category | Why it won | Best for |\n"
        . "| --- | --- | --- | --- | --- |\n"
        . "| Best Overall AI Launch | [Product] | [Category] | [Reason] | [Audience] |\n\n"
        . "## Best Overall AI Launch\n"
        . "- Product:\n- Company:\n- Category:\n- Launch date:\n- Official source:\n- Product link:\n- Demo link:\n- Pricing page:\n- What launched:\n- What it does:\n- Why it matters:\n- Who should care:\n- Pricing clarity:\n- Demo quality:\n- Creator coverage fit:\n- What feels promising:\n- What feels unproven:\n- Kingy AI verdict:\n\n"
        . "Repeat the same structure for Best AI Agent Launch, Best AI Coding Tool Launch, Best AI Video Tool Launch, Best Open-Weight / Open-Source Model Launch, Best Founder-Submitted Launch, Best Demo, Best Pricing Clarity, Best Creator Coverage Fit, and Most Under-the-Radar Launch.\n\n"
        . "## Honorable Mentions\n\n"
        . "## Market Signal of the Week\n\n"
        . "## What Founders Can Learn From This Week's Winners\n\n"
        . "## Related Kingy AI Links\n\n"
        . "## Submit Your AI Launch\n\n"
        . "## Newsletter CTA\n\n"
        . "Editorial note: " . kingy_ali_launches_of_week_editorial_note() . "\n";
}

function kingy_ali_launches_of_week_notification_email_text() {
    return "Subject: Kingy AI included [Product] in this week's Launches of the Week\n\n"
        . "Hi [Name],\n\n"
        . "Congrats - we included [Product] in this week's Kingy AI Launches of the Week.\n\n"
        . "You were selected for:\n\n"
        . "[Award Category]\n\n"
        . "Here's the feature:\n[URL]\n\n"
        . "We included a short summary of what launched, why it matters, who it is for, and what still needs more testing or verification.\n\n"
        . "Feel free to share it with your team, investors, customers, community, Discord, LinkedIn, X, newsletter, or press page if useful.\n\n"
        . "No action needed from you - just wanted to make sure you saw it.\n\n"
        . "Also, if anything in the listing is inaccurate or you have an official launch source, pricing page, demo video, or changelog you want us to review, feel free to send it over.\n\n"
        . "Best,\nCurtis\nKingy AI\n\n"
        . "Footer:\nKingy AI\n[Business mailing address or PO box]\nYou received this because your company/product was editorially mentioned by Kingy AI. Reply \"no updates\" and we will not send future editorial mention notifications.\n";
}

function kingy_ali_launches_of_week_share_asset_prompt_text() {
    return "Create a clean Kingy AI editorial recognition graphic. Text: Kingy AI Launches of the Week. Award: [Award Category]. Winner: [Product Name]. Week of [Date Range]. Format: 16:9 LinkedIn/X image. Style: crisp editorial tech graphic, high contrast, readable type, no official product logo unless permission is confirmed.\n\n"
        . "Create a square Kingy AI editorial recognition graphic for LinkedIn/company pages. Text: Kingy AI Launches of the Week. [Award Category]. Winner: [Product Name]. Week of [Date Range]. Keep typography readable at mobile sizes.\n\n"
        . "Create a small downloadable PNG badge. Text: Selected by Kingy AI. [Award Category]. [Year]. Do not include HTML embed code, dofollow link instructions, keyword-rich anchors, or any required backlink language.\n";
}

function kingy_ali_launches_of_week_post_is_edition($post_id = 0) {
    $post_id = $post_id ? absint($post_id) : get_queried_object_id();
    return $post_id && get_post_type($post_id) === 'post' && get_post_meta($post_id, kingy_ali_meta_key('launches_of_week_edition'), true);
}

function kingy_ali_launches_of_week_post_wpseo_title($title) {
    if (!is_singular('post') || !kingy_ali_launches_of_week_post_is_edition()) {
        return $title;
    }

    return get_the_title() . ' - Kingy AI';
}

function kingy_ali_launches_of_week_post_wpseo_description($description) {
    if (!is_singular('post') || !kingy_ali_launches_of_week_post_is_edition()) {
        return $description;
    }

    $start = get_post_meta(get_queried_object_id(), kingy_ali_meta_key('launches_of_week_start_date'), true);
    $end = get_post_meta(get_queried_object_id(), kingy_ali_meta_key('launches_of_week_end_date'), true);
    $range = $start && $end ? sprintf(__(' for %1$s to %2$s', 'kingy-ai-launch-intelligence'), $start, $end) : '';

    return sprintf(__('Kingy AI Launches of the Week%s: editorial awards for standout AI tools, agents, coding tools, video tools, open-weight models, demos, pricing clarity, and under-the-radar launches.', 'kingy-ai-launch-intelligence'), $range);
}
