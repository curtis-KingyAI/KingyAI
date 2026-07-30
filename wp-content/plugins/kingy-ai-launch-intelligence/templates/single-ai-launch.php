<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kingy_ali_terms_to_string')) {
    function kingy_ali_terms_to_string($terms) {
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return implode(', ', wp_list_pluck($terms, 'name'));
    }
}

if (!function_exists('kingy_ali_render_single_fact')) {
    function kingy_ali_render_single_fact($label, $value, $field = '', $empty_label = '') {
        $value = kingy_ali_public_profile_text($value);
        if ($value === '') {
            $value = $empty_label !== ''
                ? $empty_label
                : __('Not publicly confirmed', 'kingy-ai-launch-intelligence');
        }

        $body = $field ? kingy_ali_launch_render_inline_internal_links($value, get_the_ID(), $field) : esc_html($value);
        echo '<div><dt>' . esc_html($label) . '</dt><dd>' . $body . '</dd></div>';
    }
}

if (!function_exists('kingy_ali_render_single_fact_html')) {
    function kingy_ali_render_single_fact_html($label, $html, $empty_label = '') {
        if ($html === '') {
            $html = esc_html($empty_label !== '' ? $empty_label : __('Not classified', 'kingy-ai-launch-intelligence'));
        }

        echo '<div><dt>' . esc_html($label) . '</dt><dd>' . wp_kses_post($html) . '</dd></div>';
    }
}

if (!function_exists('kingy_ali_launch_inline_internal_link_rules')) {
    function kingy_ali_launch_inline_internal_link_rules($post_id, $field) {
        $post_id = absint($post_id);
        $field = sanitize_key($field);
        if (!$post_id || $field === '') {
            return array();
        }

        $allowed_fields = array(
            'what_launched',
            'pricing',
            'kingy_verdict',
            'who_it_is_for',
            'what_feels_promising',
            'what_feels_unproven',
            'reddit_signal',
            'youtube_signal',
            'traction_notes',
            'audience',
        );
        if (!in_array($field, $allowed_fields, true)) {
            return array();
        }

        $raw_rules = kingy_ali_public_profile_text(kingy_ali_get_meta($post_id, 'inline_internal_links'));
        if ($raw_rules === '') {
            return array();
        }

        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $rules = array();
        foreach (preg_split('/\r\n|\r|\n/', $raw_rules) as $line) {
            $parts = array_map('trim', explode('|', $line, 3));
            if (count($parts) !== 3) {
                continue;
            }

            list($rule_field, $anchor, $url) = $parts;
            $rule_field = sanitize_key($rule_field);
            $anchor = trim(wp_strip_all_tags($anchor));
            $url = kingy_ali_sanitize_public_profile_link_url($url);
            $url_host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));

            if ($rule_field !== $field || $anchor === '' || $url === '' || $url_host !== $home_host) {
                continue;
            }

            $rules[] = array(
                'anchor' => $anchor,
                'url' => $url,
            );
        }

        return $rules;
    }
}

if (!function_exists('kingy_ali_launch_render_inline_internal_links')) {
    function kingy_ali_launch_render_inline_internal_links($text, $post_id, $field) {
        $text = kingy_ali_public_profile_text($text);
        if ($text === '') {
            return '';
        }

        $rules = kingy_ali_launch_inline_internal_link_rules($post_id, $field);
        if (!$rules) {
            return esc_html($text);
        }

        $matches = array();
        foreach ($rules as $rule) {
            $position = strpos($text, $rule['anchor']);
            if ($position === false) {
                $position = stripos($text, $rule['anchor']);
            }
            if ($position === false) {
                continue;
            }

            $length = strlen($rule['anchor']);
            $overlaps = false;
            foreach ($matches as $match) {
                if ($position < $match['end'] && ($position + $length) > $match['start']) {
                    $overlaps = true;
                    break;
                }
            }
            if ($overlaps) {
                continue;
            }

            $matches[] = array(
                'start' => $position,
                'end' => $position + $length,
                'url' => $rule['url'],
            );
        }

        if (!$matches) {
            return esc_html($text);
        }

        usort($matches, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $html = '';
        $cursor = 0;
        foreach ($matches as $match) {
            $html .= esc_html(substr($text, $cursor, $match['start'] - $cursor));
            $anchor = substr($text, $match['start'], $match['end'] - $match['start']);
            $html .= '<a data-kingy-ali-track="clicked_internal_link" data-event-surface="launch_profile_inline" href="' . esc_url($match['url']) . '">' . esc_html($anchor) . '</a>';
            $cursor = $match['end'];
        }
        $html .= esc_html(substr($text, $cursor));

        return $html;
    }
}

if (!function_exists('kingy_ali_render_launch_term_links')) {
    function kingy_ali_render_launch_term_links($terms, $taxonomy, $surface = 'launch_profile_fact') {
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        $links = array();
        foreach ($terms as $term) {
            $url = kingy_ali_launch_term_url($term, $taxonomy);
            $label = esc_html($term->name);
            if ($taxonomy === 'kingy_audience') {
                $inline_label = kingy_ali_launch_render_inline_internal_links($term->name, get_the_ID(), 'audience');
                if ($inline_label !== $label) {
                    $links[] = $inline_label;
                    continue;
                }
            }
            $links[] = '<a data-kingy-ali-track="clicked_filter" data-event-label="' . esc_attr($term->name) . '" data-event-surface="' . esc_attr($surface) . '" href="' . esc_url($url) . '">' . $label . '</a>';
        }

        return implode(', ', $links);
    }
}

if (!function_exists('kingy_ali_launch_term_url')) {
    function kingy_ali_launch_term_url($term, $taxonomy) {
        if ($taxonomy === 'kingy_launch_category') {
            $category_page = get_page_by_path('ai-launches/' . $term->slug, OBJECT, 'page');
            if ($category_page) {
                return get_permalink($category_page);
            }

            return add_query_arg('kali_category', $term->slug, home_url('/ai-launches/'));
        }

        if ($taxonomy === 'kingy_audience') {
            return add_query_arg('kali_audience', $term->slug, home_url('/ai-launches/'));
        }

        if ($taxonomy === 'kingy_launch_type') {
            return add_query_arg('kali_launch_type', $term->slug, home_url('/ai-launches/'));
        }

        return home_url('/ai-launches/');
    }
}

if (!function_exists('kingy_ali_render_text_panel')) {
    function kingy_ali_render_text_panel($title, $body, $field = '') {
        $body = kingy_ali_public_profile_text($body);
        if (!$body) {
            return;
        }

        echo '<div class="kingy-ali-text-panel"><h3>' . esc_html($title) . '</h3><p>' . kingy_ali_launch_render_inline_internal_links($body, get_the_ID(), $field) . '</p></div>';
    }
}

if (!function_exists('kingy_ali_launch_yes_no_label')) {
    function kingy_ali_launch_yes_no_label($value) {
        $value = strtolower(trim((string) kingy_ali_public_profile_text($value)));

        if (in_array($value, array('yes', 'y', 'true', '1'), true)) {
            return __('Yes', 'kingy-ai-launch-intelligence');
        }

        if (in_array($value, array('no', 'n', 'false', '0'), true)) {
            return __('No', 'kingy-ai-launch-intelligence');
        }

        return $value ? ucfirst($value) : __('Not publicly confirmed', 'kingy-ai-launch-intelligence');
    }
}

if (!function_exists('kingy_ali_launch_source_note_links')) {
    function kingy_ali_launch_source_note_links($source_notes) {
        $source_notes = kingy_ali_public_profile_text($source_notes);
        if (!$source_notes) {
            return array();
        }

        $links = array();
        $lines = preg_split('/\r\n|\r|\n/', $source_notes);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            $label = __('Source', 'kingy-ai-launch-intelligence');
            $url = '';
            if (preg_match('/^\s*([^:]+):\s*(https?:\/\/\S+)/i', $line, $matches)) {
                $label = trim($matches[1]);
                $url = trim($matches[2]);
            } elseif (preg_match('/(https?:\/\/\S+)/i', $line, $matches)) {
                $url = trim($matches[1]);
            }

            $url = kingy_ali_sanitize_public_profile_link_url($url);
            if (!$url) {
                continue;
            }

            $links[] = array(
                'label' => $label,
                'url' => $url,
            );
        }

        return $links;
    }
}

if (!function_exists('kingy_ali_launch_source_links')) {
    function kingy_ali_launch_source_links($official_url, $source_urls, $source_notes) {
        $links = array();
        if ($official_url) {
            $links[] = array(
                'label' => __('Official site', 'kingy-ai-launch-intelligence'),
                'url' => $official_url,
            );
        }

        $labels = array(
            'demo_url' => __('Demo', 'kingy-ai-launch-intelligence'),
            'product_hunt_url' => __('Product Hunt', 'kingy-ai-launch-intelligence'),
            'github_url' => __('GitHub', 'kingy-ai-launch-intelligence'),
            'huggingface_url' => __('Hugging Face', 'kingy-ai-launch-intelligence'),
            'x_url' => __('X/social', 'kingy-ai-launch-intelligence'),
            'youtube_url' => __('YouTube', 'kingy-ai-launch-intelligence'),
            'press_kit_url' => __('Press kit', 'kingy-ai-launch-intelligence'),
        );

        foreach ($labels as $key => $label) {
            if (!empty($source_urls[$key])) {
                $links[] = array(
                    'label' => $label,
                    'url' => $source_urls[$key],
                );
            }
        }

        $links = array_merge($links, kingy_ali_launch_source_note_links($source_notes));
        $seen = array();
        $deduped = array();
        foreach ($links as $link) {
            if (empty($link['url']) || isset($seen[$link['url']])) {
                continue;
            }

            $seen[$link['url']] = true;
            $deduped[] = $link;
        }

        return $deduped;
    }
}

if (!function_exists('kingy_ali_render_external_link')) {
    function kingy_ali_render_external_link($label, $url, $surface = 'launch_profile_sources') {
        $url = kingy_ali_sanitize_public_profile_link_url($url);
        if (!$url) {
            return;
        }

        $rel = kingy_ali_source_link_target_attrs($url);

        echo '<a data-kingy-ali-track="clicked_source_link" data-event-label="' . esc_attr($label) . '" data-event-surface="' . esc_attr($surface) . '" href="' . esc_url($url) . '"' . $rel . '>' . esc_html($label) . '</a>';
    }
}

if (!function_exists('kingy_ali_launch_has_attribute_slug')) {
    function kingy_ali_launch_has_attribute_slug($post_id, $slugs) {
        $terms = get_the_terms($post_id, 'kingy_tool_attribute');
        if (is_wp_error($terms) || empty($terms)) {
            return false;
        }

        return (bool) array_intersect($slugs, wp_list_pluck($terms, 'slug'));
    }
}

if (!function_exists('kingy_ali_launch_has_coverage_signal')) {
    function kingy_ali_launch_has_coverage_signal($post_id) {
        // These attributes are derived from source/taxonomy facts rather than
        // score metadata, so a stale score-derived term cannot trigger a CTA.
        $non_score_signal_slugs = array(
            'video-demo-available',
            'creator-friendly',
            'business-friendly',
            'developer-friendly',
            'product-hunt-traction',
            'funding-announced',
        );

        return kingy_ali_launch_has_attribute_slug($post_id, $non_score_signal_slugs)
            || (function_exists('kingy_ali_public_launch_score_meets') && kingy_ali_public_launch_score_meets($post_id, 'youtube', 7))
            || (function_exists('kingy_ali_public_launch_score_meets') && kingy_ali_public_launch_score_meets($post_id, 'demo', 7));
    }
}

if (!function_exists('kingy_ali_launch_is_editorially_approved')) {
    function kingy_ali_launch_is_editorially_approved($post_id) {
        $verification_status = sanitize_key((string) kingy_ali_get_meta($post_id, 'verification_status'));

        return in_array($verification_status, array('verified', 'partially_verified'), true);
    }
}

if (!function_exists('kingy_ali_render_launch_coverage_next_steps')) {
    function kingy_ali_render_launch_coverage_next_steps($post_id) {
        if (!kingy_ali_launch_is_editorially_approved($post_id) || !kingy_ali_launch_has_coverage_signal($post_id)) {
            return;
        }

        $youtube_signal = (function_exists('kingy_ali_public_launch_score_meets') && kingy_ali_public_launch_score_meets($post_id, 'youtube', 7))
            || kingy_ali_launch_has_attribute_slug($post_id, array('video-demo-available', 'creator-friendly'));
        ?>
        <section class="kingy-ali-link-panel kingy-ali-coverage-next">
            <h2><?php esc_html_e('Creator Coverage Next Steps', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('This launch has signals that may support demos, reviews, creator education, founder storytelling, or practical product explainers.', 'kingy-ai-launch-intelligence'); ?></p>
            <p><?php esc_html_e('Launching an AI product that needs clear demos, creator education, and buyer trust? Sponsor a Kingy AI video or launch feature.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-link-list">
                <?php if ($youtube_signal) : ?>
                    <a data-kingy-ali-track="clicked_category_path" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('View YouTube-worthy launch list', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_coverage" href="<?php echo esc_url(home_url('/ai-launches/youtube-worthy-ai-tools/')); ?>"><?php esc_html_e('View YouTube-worthy launch list', 'kingy-ai-launch-intelligence'); ?></a>
                <?php endif; ?>
                <a data-kingy-ali-track="clicked_sponsorship_cta" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Sponsor Kingy AI from launch profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_coverage" href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_sponsorship_cta" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_coverage" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_coverage')); ?>"><?php esc_html_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_roi_calculator" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_coverage" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('kingy_ali_render_launch_context_links')) {
    function kingy_ali_render_launch_context_links($post_id, $launch_date, $category_terms, $audience_terms, $launch_type_terms) {
        $links = array(
            array(
                'label' => __('Launch Intelligence hub', 'kingy-ai-launch-intelligence'),
                'url' => home_url('/ai-launches/'),
                'event' => 'clicked_category_path',
            ),
        );

        $has_day_precision = function_exists('kingy_ali_public_profile_date_has_day_precision')
            ? kingy_ali_public_profile_date_has_day_precision($launch_date)
            : preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $launch_date) === 1;
        if ($has_day_precision) {
            $links[] = array(
                'label' => __('Today\'s launches', 'kingy-ai-launch-intelligence'),
                'url' => home_url('/ai-launches/today/'),
                'event' => 'clicked_category_path',
            );
            $links[] = array(
                'label' => __('This week\'s launches', 'kingy-ai-launch-intelligence'),
                'url' => home_url('/ai-launches/this-week/'),
                'event' => 'clicked_category_path',
            );
        }

        foreach (array('kingy_launch_category' => $category_terms, 'kingy_audience' => $audience_terms, 'kingy_launch_type' => $launch_type_terms) as $taxonomy => $terms) {
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $links[] = array(
                    'label' => sprintf(__('More: %s', 'kingy-ai-launch-intelligence'), $term->name),
                    'url' => kingy_ali_launch_term_url($term, $taxonomy),
                    'event' => 'clicked_filter',
                );
            }
        }

        ?>
        <section class="kingy-ali-link-panel kingy-ali-launch-context">
            <h2><?php esc_html_e('Launch Context', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Use these links to move from this record into the broader Launch Intelligence database.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-link-list">
                <?php foreach ($links as $link) : ?>
                    <a data-kingy-ali-track="<?php echo esc_attr($link['event']); ?>" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="launch_profile_context" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
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
        $source_urls = array(
            'demo_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'demo_url')),
            'product_hunt_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'product_hunt_url')),
            'github_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'github_url')),
            'huggingface_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'huggingface_url')),
            'x_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'x_url')),
            'youtube_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'youtube_url')),
            'press_kit_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'press_kit_url')),
        );
        $related_urls = array(
            'related_article_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_article_url')),
            'related_course_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_course_url')),
            'related_review_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_review_url')),
            'related_alternatives_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_alternatives_url')),
            'related_calculator_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'related_calculator_url')),
            'best_next_link_url' => kingy_ali_sanitize_public_profile_link_url(kingy_ali_get_meta($post_id, 'best_next_link_url')),
        );
        $related_editorial_urls = function_exists('kingy_ali_related_editorial_urls_for_launch')
            ? kingy_ali_related_editorial_urls_for_launch($post_id)
            : array();
        $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
        $company = kingy_ali_public_profile_meta_text($post_id, 'company');
        $what_launched = kingy_ali_public_profile_meta_text($post_id, 'what_launched');
        $kingy_verdict = kingy_ali_public_profile_meta_text($post_id, 'kingy_verdict');
        $who_it_is_for = kingy_ali_public_profile_meta_text($post_id, 'who_it_is_for');
        $what_feels_promising = kingy_ali_public_profile_meta_text($post_id, 'what_feels_promising');
        $what_feels_unproven = kingy_ali_public_profile_meta_text($post_id, 'what_feels_unproven');
        $reddit_signal = kingy_ali_public_profile_meta_text($post_id, 'reddit_signal');
        $youtube_signal = kingy_ali_public_profile_meta_text($post_id, 'youtube_signal');
        $traction_notes = kingy_ali_public_profile_meta_text($post_id, 'traction_notes');
        $pricing = kingy_ali_public_profile_meta_text($post_id, 'pricing');
        $free_plan = kingy_ali_launch_yes_no_label(kingy_ali_public_profile_meta_text($post_id, 'free_plan'));
        $api_available = kingy_ali_launch_yes_no_label(kingy_ali_public_profile_meta_text($post_id, 'api_available'));
        $open_source = kingy_ali_launch_yes_no_label(kingy_ali_public_profile_meta_text($post_id, 'open_source_or_open_weight'));
        $source_notes = kingy_ali_public_profile_meta_text($post_id, 'sources');
        $best_next_label = kingy_ali_public_profile_meta_text($post_id, 'best_next_link_label', __('Best next link', 'kingy-ai-launch-intelligence'));
        $category_terms = get_the_terms($post_id, 'kingy_launch_category');
        $audience_terms = get_the_terms($post_id, 'kingy_audience');
        $launch_type_terms = get_the_terms($post_id, 'kingy_launch_type');
        $launch_type_html = kingy_ali_render_launch_term_links($launch_type_terms, 'kingy_launch_type');
        $category_html = kingy_ali_render_launch_term_links($category_terms, 'kingy_launch_category');
        $audience_html = kingy_ali_render_launch_term_links($audience_terms, 'kingy_audience');
        if ($audience_html === '' && $who_it_is_for !== '') {
            $audience_html = kingy_ali_launch_render_inline_internal_links($who_it_is_for, $post_id, 'who_it_is_for');
        }
        $source_links = kingy_ali_launch_source_links($official_url, $source_urls, $source_notes);
        $trust_snapshot = kingy_ali_launch_trust_snapshot($post_id);
        $verification_status = sanitize_key((string) kingy_ali_get_meta($post_id, 'verification_status'));
        $is_editorially_approved = kingy_ali_launch_is_editorially_approved($post_id);
        $is_founder_submission = !empty(kingy_ali_get_meta($post_id, 'founder_submitted'))
            || $verification_status === 'founder_submitted';
        $is_unverified_founder_submission = $is_founder_submission && !$is_editorially_approved;
        $launch_date_label = kingy_ali_public_profile_date_label($launch_date);
        if ($launch_date_label === '') {
            $launch_date_label = __('Not publicly confirmed', 'kingy-ai-launch-intelligence');
        }
        if ($verification_status === 'verified') {
            $source_panel_kicker = __('Source-backed record', 'kingy-ai-launch-intelligence');
            $source_panel_heading = __('Verified Sources', 'kingy-ai-launch-intelligence');
        } elseif ($is_unverified_founder_submission) {
            $source_panel_kicker = __('Founder-submitted record', 'kingy-ai-launch-intelligence');
            $source_panel_heading = __('Founder-supplied sources', 'kingy-ai-launch-intelligence');
        } else {
            $source_panel_kicker = __('Source list', 'kingy-ai-launch-intelligence');
            $source_panel_heading = __('Sources', 'kingy-ai-launch-intelligence');
        }
        $score_snapshot = $trust_snapshot['score'];
        $has_scores = !empty($score_snapshot['public']) && (
            $score_snapshot['kingy']['state'] !== 'not_scored'
            || $score_snapshot['demo']['state'] !== 'not_scored'
            || $score_snapshot['youtube']['state'] !== 'not_scored'
        );
        $related_tool_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_tool_id, 'kingy_ai_tool')) {
            $related_tool_id = 0;
        }
        $related_company_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_company_id'));
        if (!kingy_ali_related_post_is_public_index_ready($related_company_id, 'kingy_ai_company')) {
            $related_company_id = 0;
        }
        ?>
        <article <?php post_class('kingy-ali-single'); ?>>
            <header class="kingy-ali-single__header">
                <div class="kingy-ali-single__header-inner">
                    <div class="kingy-ali-single__headline">
                        <p class="kingy-ali-kicker"><?php esc_html_e('AI Launch Profile', 'kingy-ai-launch-intelligence'); ?></p>
                        <h1><?php the_title(); ?></h1>
                        <?php if ($what_launched) : ?>
                            <p><?php echo kingy_ali_launch_render_inline_internal_links($what_launched, $post_id, 'what_launched'); ?></p>
                        <?php endif; ?>
                        <div class="kingy-ali-single__actions">
                            <?php kingy_ali_render_external_link(__('Read official source', 'kingy-ai-launch-intelligence'), $official_url, 'launch_profile_hero'); ?>
                            <?php if ($related_tool_id) : ?>
                                <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($related_tool_id); ?>" data-event-surface="launch_profile_hero" href="<?php echo esc_url(get_permalink($related_tool_id)); ?>"><?php esc_html_e('Tool profile', 'kingy-ai-launch-intelligence'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <aside class="kingy-ali-single__hero-facts" aria-label="<?php esc_attr_e('Launch summary', 'kingy-ai-launch-intelligence'); ?>">
                        <dl>
                            <div><dt><?php esc_html_e('Company', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($company ? $company : __('Unknown', 'kingy-ai-launch-intelligence')); ?></dd></div>
                            <div><dt><?php esc_html_e('Launch date', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($launch_date_label); ?></dd></div>
                            <div><dt><?php esc_html_e('Category', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo wp_kses_post(kingy_ali_render_launch_term_links($category_terms, 'kingy_launch_category', 'launch_profile_hero')); ?></dd></div>
                            <div><dt><?php esc_html_e('Verification', 'kingy-ai-launch-intelligence'); ?></dt><dd data-verification-status="<?php echo esc_attr($trust_snapshot['status']); ?>"><?php echo esc_html($trust_snapshot['label']); ?></dd></div>
                        </dl>
                    </aside>
                </div>
            </header>

            <?php echo kingy_ali_render_profile_featured_image($post_id); ?>

            <section class="kingy-ali-snapshot">
                <div class="kingy-ali-section-heading">
                    <p class="kingy-ali-kicker"><?php esc_html_e('At a glance', 'kingy-ai-launch-intelligence'); ?></p>
                    <h2><?php esc_html_e('Launch Snapshot', 'kingy-ai-launch-intelligence'); ?></h2>
                </div>
                <dl class="kingy-ali-facts">
                    <?php kingy_ali_render_single_fact(__('Company', 'kingy-ai-launch-intelligence'), $company); ?>
                    <?php kingy_ali_render_single_fact(__('Launch date', 'kingy-ai-launch-intelligence'), $launch_date_label); ?>
                    <?php kingy_ali_render_single_fact_html(__('Launch type', 'kingy-ai-launch-intelligence'), $launch_type_html); ?>
                    <?php kingy_ali_render_single_fact_html(__('Category', 'kingy-ai-launch-intelligence'), $category_html); ?>
                    <?php kingy_ali_render_single_fact_html(__('Audience', 'kingy-ai-launch-intelligence'), $audience_html, __('Not specified', 'kingy-ai-launch-intelligence')); ?>
                    <?php kingy_ali_render_single_fact(__('Pricing', 'kingy-ai-launch-intelligence'), $pricing, 'pricing'); ?>
                    <?php kingy_ali_render_single_fact(__('Free plan', 'kingy-ai-launch-intelligence'), $free_plan); ?>
                    <?php kingy_ali_render_single_fact(__('API', 'kingy-ai-launch-intelligence'), $api_available); ?>
                    <?php kingy_ali_render_single_fact(__('Open weights/source', 'kingy-ai-launch-intelligence'), $open_source); ?>
                </dl>
            </section>

            <?php kingy_ali_render_launch_context_links($post_id, $launch_date, $category_terms, $audience_terms, $launch_type_terms); ?>

            <?php kingy_ali_render_trust_panel($post_id, 'launch'); ?>

            <?php if ($has_scores) : ?>
                <section class="kingy-ali-score-panel">
                    <h2><?php esc_html_e('Kingy Scores', 'kingy-ai-launch-intelligence'); ?></h2>
                    <p><?php esc_html_e('Scores are editorial review signals across launch quality, demo evidence, YouTube potential, and search readiness.', 'kingy-ai-launch-intelligence'); ?></p>
                    <dl class="kingy-ali-score-list kingy-ali-score-list--large">
                        <div><dt><?php esc_html_e('Launch Score', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($score_snapshot['kingy']['label']); ?></dd></div>
                        <div><dt><?php esc_html_e('Demo Quality', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($score_snapshot['demo']['label']); ?></dd></div>
                        <div><dt><?php esc_html_e('YouTube Potential', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($score_snapshot['youtube']['label']); ?></dd></div>
                    </dl>
                </section>
            <?php endif; ?>

            <?php kingy_ali_render_launch_coverage_next_steps($post_id); ?>

            <section class="kingy-ali-content-band">
                <?php
                // OpenClaw launch 926112 has a closed, evidence-reviewed editorial body that
                // already includes its Kingy verdict, methodology limitation, and disclosure.
                // Render that body once instead of duplicating the separate verdict meta.
                $render_openclaw_926112_body = absint($post_id) === 926112
                    && trim((string) get_post_field('post_content', $post_id, 'raw')) !== '';
                ?>
                <?php if ($render_openclaw_926112_body) : ?>
                    <style id="kingy-openclaw-926112-body-guard">
                        .kingy-ali-launch-body code {
                            overflow-wrap: anywhere;
                            white-space: normal;
                            word-break: break-word;
                        }
                    </style>
                    <div class="entry-content kingy-ali-launch-body"><?php the_content(); ?></div>
                <?php else : ?>
                    <h2><?php esc_html_e('Kingy AI Take', 'kingy-ai-launch-intelligence'); ?></h2>
                    <?php if ($kingy_verdict) : ?>
                        <p><?php echo esc_html($kingy_verdict); ?></p>
                    <?php else : ?>
                        <?php the_content(); ?>
                    <?php endif; ?>
                <?php endif; ?>
                <aside class="kingy-stack-radar-connection">
                    <strong><?php esc_html_e('Track changes to your AI stack', 'kingy-ai-launch-intelligence'); ?></strong>
                    <p><a href="<?php echo esc_url(home_url('/ai-stack-change-radar/')); ?>"><?php esc_html_e('Open the Kingy AI Stack Change Radar →', 'kingy-ai-launch-intelligence'); ?></a></p>
                </aside>
            </section>

            <?php if ($who_it_is_for || $what_feels_promising || $what_feels_unproven) : ?>
                <section class="kingy-ali-content-grid">
                    <?php kingy_ali_render_text_panel(__('Who it is for', 'kingy-ai-launch-intelligence'), $who_it_is_for, 'who_it_is_for'); ?>
                    <?php kingy_ali_render_text_panel(__('What feels promising', 'kingy-ai-launch-intelligence'), $what_feels_promising, 'what_feels_promising'); ?>
                    <?php kingy_ali_render_text_panel(__('What feels unproven', 'kingy-ai-launch-intelligence'), $what_feels_unproven, 'what_feels_unproven'); ?>
                </section>
            <?php endif; ?>

            <?php if ($reddit_signal || $youtube_signal || $traction_notes) : ?>
                <section class="kingy-ali-content-grid">
                    <?php kingy_ali_render_text_panel(__('Reddit/community signal', 'kingy-ai-launch-intelligence'), $reddit_signal, 'reddit_signal'); ?>
                    <?php kingy_ali_render_text_panel(__('YouTube/creator signal', 'kingy-ai-launch-intelligence'), $youtube_signal, 'youtube_signal'); ?>
                    <?php kingy_ali_render_text_panel(__('Traction notes', 'kingy-ai-launch-intelligence'), $traction_notes, 'traction_notes'); ?>
                </section>
            <?php endif; ?>

            <?php if ($source_links) : ?>
                <section class="kingy-ali-link-panel kingy-ali-source-panel">
                    <div class="kingy-ali-section-heading">
                        <p class="kingy-ali-kicker"><?php echo esc_html($source_panel_kicker); ?></p>
                        <h2><?php echo esc_html($source_panel_heading); ?></h2>
                    </div>
                    <div class="kingy-ali-source-grid">
                        <?php foreach ($source_links as $link) : ?>
                            <?php $rel = kingy_ali_source_link_target_attrs($link['url']); ?>
                            <a data-kingy-ali-track="clicked_source_link" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="launch_profile_sources" href="<?php echo esc_url($link['url']); ?>"<?php echo $rel; ?>>
                                <strong><?php echo esc_html($link['label']); ?></strong>
                                <span><?php echo esc_html(wp_parse_url($link['url'], PHP_URL_HOST)); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($related_tool_id || $related_company_id || $related_editorial_urls || array_filter($related_urls)) : ?>
                <section class="kingy-ali-link-panel">
                    <h2><?php esc_html_e('Related Kingy Links', 'kingy-ai-launch-intelligence'); ?></h2>
                    <div class="kingy-ali-link-list">
                        <?php if ($related_tool_id) : ?>
                            <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($related_tool_id); ?>" data-event-surface="launch_profile_related" href="<?php echo esc_url(get_permalink($related_tool_id)); ?>"><?php esc_html_e('View tool profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <?php if ($related_company_id) : ?>
                            <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($related_company_id); ?>" data-event-surface="launch_profile_related" href="<?php echo esc_url(get_permalink($related_company_id)); ?>"><?php esc_html_e('View company profile', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php endif; ?>
                        <?php kingy_ali_render_external_link(__('Related article', 'kingy-ai-launch-intelligence'), $related_urls['related_article_url'], 'launch_profile_related'); ?>
                        <?php foreach ($related_editorial_urls as $editorial_url => $editorial_post_id) : ?>
                            <?php if ($related_urls['related_article_url'] === $editorial_url) { continue; } ?>
                            <a data-kingy-ali-track="clicked_related_article" data-object-id="<?php echo esc_attr($editorial_post_id); ?>" data-event-label="<?php echo esc_attr(get_the_title($editorial_post_id)); ?>" data-event-surface="launch_profile_related" href="<?php echo esc_url($editorial_url); ?>"><?php echo esc_html(get_the_title($editorial_post_id)); ?></a>
                        <?php endforeach; ?>
                        <?php kingy_ali_render_external_link(__('Related course', 'kingy-ai-launch-intelligence'), $related_urls['related_course_url'], 'launch_profile_related'); ?>
                        <?php kingy_ali_render_external_link(__('Related review', 'kingy-ai-launch-intelligence'), $related_urls['related_review_url'], 'launch_profile_related'); ?>
                        <?php kingy_ali_render_external_link(__('Alternatives page', 'kingy-ai-launch-intelligence'), $related_urls['related_alternatives_url'], 'launch_profile_related'); ?>
                        <?php kingy_ali_render_external_link(__('Related calculator', 'kingy-ai-launch-intelligence'), $related_urls['related_calculator_url'], 'launch_profile_related'); ?>
                        <?php kingy_ali_render_external_link($best_next_label, $related_urls['best_next_link_url'], 'launch_profile_related'); ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (function_exists('kingy_ali_render_related_model_profile_panel')) : ?>
                <?php kingy_ali_render_related_model_profile_panel($post_id, 'related_launch_id', 'launch_profile_related_models'); ?>
            <?php endif; ?>

            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a related launch', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
                <?php if ($is_editorially_approved) : ?>
                    <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI from launch profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_cta" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator ROI', 'kingy-ai-launch-intelligence'); ?></a>
                <?php endif; ?>
                <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from launch profile', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_profile_cta" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
