<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kingy_ali_tool_terms_to_string')) {
    function kingy_ali_tool_terms_to_string($terms) {
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return implode(', ', wp_list_pluck($terms, 'name'));
    }
}

if (!function_exists('kingy_ali_tool_fact')) {
    function kingy_ali_tool_fact($label, $value) {
        $value = kingy_ali_public_profile_text($value);
        if ($value === '') {
            $value = __('Unknown', 'kingy-ai-launch-intelligence');
        }

        echo '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }
}

if (!function_exists('kingy_ali_tool_text_panel')) {
    function kingy_ali_tool_text_panel($title, $body) {
        $body = kingy_ali_public_profile_text($body);
        if (!$body) {
            return;
        }

        echo '<div class="kingy-ali-text-panel"><h3>' . esc_html($title) . '</h3><p>' . esc_html($body) . '</p></div>';
    }
}

if (!function_exists('kingy_ali_tool_external_link')) {
    function kingy_ali_tool_external_link($label, $url, $surface = 'tool_profile_links') {
        $url = kingy_ali_sanitize_public_profile_link_url($url);
        if (!$url) {
            return;
        }

        $rel = kingy_ali_source_link_target_attrs($url);

        echo '<a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="' . esc_attr($surface) . '" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a>';
    }
}

if (!function_exists('kingy_ali_tool_profile_content')) {
    function kingy_ali_tool_profile_content($post_id) {
        $content = apply_filters('the_content', get_the_content(null, false, $post_id));
        $title = trim(wp_strip_all_tags(get_the_title($post_id)));
        if ($title === '' || !is_string($content) || stripos($content, '<h1') === false) {
            return $content;
        }

        $removed = false;
        return preg_replace_callback(
            '/<h1\b[^>]*>.*?<\/h1>/is',
            function ($matches) use ($title, &$removed) {
                if ($removed) {
                    return $matches[0];
                }

                $heading = trim(wp_strip_all_tags($matches[0]));
                if (html_entity_decode($heading, ENT_QUOTES, get_bloginfo('charset')) === html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset'))) {
                    $removed = true;
                    return '';
                }

                return $matches[0];
            },
            $content
        );
    }
}

get_header();
?>
<main id="primary" class="site-main kingy-ali-template">
    <?php
    while (have_posts()) :
        the_post();
        $post_id = get_the_ID();
        kingy_ali_enqueue_assets();
        $official_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'official_url'));
        $demo_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'demo_url'));
        $alternatives_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'alternatives_url'));
        $related_article_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_article_url'));
        $related_course_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_course_url'));
        $related_review_url = kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_review_url'));
        $latest_launch_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
        if (!kingy_ali_related_post_is_public_index_ready($latest_launch_id, 'kingy_ai_launch')) {
            $latest_launch_id = 0;
        }
        $related_company_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_company_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_company_id, 'kingy_ai_company')) {
            $related_company_id = 0;
        }
        $launch_rollup = kingy_ali_tool_launch_rollup($post_id);
        $launches = kingy_ali_query_tool_launches($post_id, 12);
        $what_it_does = kingy_ali_public_profile_meta_text($post_id, 'what_it_does');
        $main_competitors = kingy_ali_public_profile_meta_text($post_id, 'main_competitors');
        $tool_body_text = trim(wp_strip_all_tags(get_the_content(null, false, $post_id)));
        $has_detailed_tool_body = $what_it_does && str_word_count($tool_body_text) > 120;
        ?>
        <article <?php post_class('kingy-ali-single kingy-ali-tool-single'); ?>>
            <header class="kingy-ali-single__header">
                <p class="kingy-ali-kicker"><?php esc_html_e('AI Tool Profile', 'kingy-ai-launch-intelligence'); ?></p>
                <h1><?php the_title(); ?></h1>
                <?php if ($what_it_does) : ?>
                    <p><?php echo esc_html($what_it_does); ?></p>
                <?php elseif (has_excerpt()) : ?>
                    <p><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
            </header>

            <?php echo kingy_ali_render_profile_featured_image($post_id); ?>

            <section class="kingy-ali-facts">
                <?php kingy_ali_tool_fact(__('Company', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'company')); ?>
                <?php kingy_ali_tool_fact(__('Primary category', 'kingy-ai-launch-intelligence'), kingy_ali_tool_terms_to_string(get_the_terms($post_id, 'kingy_launch_category'))); ?>
                <?php kingy_ali_tool_fact(__('Best for', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'best_for')); ?>
                <?php kingy_ali_tool_fact(__('Pricing', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'pricing')); ?>
                <?php kingy_ali_tool_fact(__('Free plan', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'free_plan', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
                <?php kingy_ali_tool_fact(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'api_available', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
                <?php kingy_ali_tool_fact(__('Open source/open weight', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'open_source_or_open_weight', __('Unknown', 'kingy-ai-launch-intelligence'))); ?>
                <?php kingy_ali_tool_fact(__('Linked launches', 'kingy-ai-launch-intelligence'), (string) $launch_rollup['count']); ?>
                <?php kingy_ali_tool_fact(__('Latest launch date', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_date_label($launch_rollup['latest_date'])); ?>
                <?php kingy_ali_tool_fact(__('Last verified', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'last_verified')); ?>
            </section>

            <?php kingy_ali_render_trust_panel($post_id, 'tool'); ?>

            <section class="kingy-ali-content-band">
                <h2><?php esc_html_e('What It Does', 'kingy-ai-launch-intelligence'); ?></h2>
                <?php if ($what_it_does) : ?>
                    <p><?php echo esc_html($what_it_does); ?></p>
                <?php else : ?>
                    <?php echo kingy_ali_tool_profile_content($post_id); ?>
                <?php endif; ?>
            </section>

            <?php if ($has_detailed_tool_body) : ?>
                <section class="kingy-ali-content-band kingy-ali-tool-body">
                    <h2><?php esc_html_e('Full Guide', 'kingy-ai-launch-intelligence'); ?></h2>
                    <?php echo kingy_ali_tool_profile_content($post_id); ?>
                </section>
            <?php endif; ?>

            <?php if ($main_competitors || $alternatives_url) : ?>
                <section class="kingy-ali-content-grid">
                    <?php kingy_ali_tool_text_panel(__('Main competitors', 'kingy-ai-launch-intelligence'), $main_competitors); ?>
                    <?php if ($alternatives_url) : ?>
                        <div class="kingy-ali-text-panel">
                            <h3><?php esc_html_e('Alternatives', 'kingy-ai-launch-intelligence'); ?></h3>
                            <p><?php kingy_ali_tool_external_link(__('View alternatives page', 'kingy-ai-launch-intelligence'), $alternatives_url, 'tool_profile_comparison'); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($official_url || $demo_url || $latest_launch_id || $related_company_id) : ?>
                <section class="kingy-ali-link-panel">
                    <h2><?php esc_html_e('Tool Links', 'kingy-ai-launch-intelligence'); ?></h2>
                    <div class="kingy-ali-link-list">
                        <?php kingy_ali_tool_external_link(__('Official site', 'kingy-ai-launch-intelligence'), $official_url); ?>
                        <?php kingy_ali_tool_external_link(__('Official demo', 'kingy-ai-launch-intelligence'), $demo_url); ?>
                        <?php if ($latest_launch_id) : ?>
                            <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($latest_launch_id); ?>" data-event-surface="tool_profile_links" href="<?php echo esc_url(get_permalink($latest_launch_id)); ?>"><?php esc_html_e('Latest launch profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <?php if ($related_company_id) : ?>
                            <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($related_company_id); ?>" data-event-surface="tool_profile_links" href="<?php echo esc_url(get_permalink($related_company_id)); ?>"><?php esc_html_e('Company profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($related_article_url || $related_course_url || $related_review_url) : ?>
                <section class="kingy-ali-link-panel">
                    <h2><?php esc_html_e('Related Kingy Links', 'kingy-ai-launch-intelligence'); ?></h2>
                    <div class="kingy-ali-link-list">
                        <?php kingy_ali_tool_external_link(__('Related article', 'kingy-ai-launch-intelligence'), $related_article_url, 'tool_profile_related'); ?>
                        <?php kingy_ali_tool_external_link(__('Related course', 'kingy-ai-launch-intelligence'), $related_course_url, 'tool_profile_related'); ?>
                        <?php kingy_ali_tool_external_link(__('Related review', 'kingy-ai-launch-intelligence'), $related_review_url, 'tool_profile_related'); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (function_exists('kingy_ali_render_related_model_profile_panel')) : ?>
                <?php kingy_ali_render_related_model_profile_panel($post_id, 'related_tool_id', 'tool_profile_related_models'); ?>
            <?php endif; ?>

            <?php if (function_exists('kingy_ali_render_tool_companion_videos')) : ?>
                <?php echo kingy_ali_render_tool_companion_videos($post_id, 6); ?>
            <?php endif; ?>

            <section class="kingy-ali-content-band kingy-ali-launch-history">
                <h2><?php esc_html_e('Launch History', 'kingy-ai-launch-intelligence'); ?></h2>
                <?php if ($launches->have_posts()) : ?>
                    <div class="kingy-ali-grid">
                        <?php
                        while ($launches->have_posts()) :
                            $launches->the_post();
                            echo kingy_ali_render_launch_card(get_the_ID());
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('No structured launch records are linked to this tool yet.', 'kingy-ai-launch-intelligence'); ?></p>
                <?php endif; ?>
            </section>

            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="tool_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="tool_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Sponsor Kingy AI from tool profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="tool_profile_cta" href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI from tool profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="tool_profile_cta" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator ROI', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from tool profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="tool_profile_cta" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
