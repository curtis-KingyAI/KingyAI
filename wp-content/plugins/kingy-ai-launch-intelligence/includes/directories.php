<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_tool_directory', 'kingy_ali_shortcode_tool_directory');
add_shortcode('kingy_company_directory', 'kingy_ali_shortcode_company_directory');

function kingy_ali_shortcode_tool_directory($atts = array()) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 24, 'heading' => 'yes'), $atts, 'kingy_tool_directory');
    $filters = kingy_ali_directory_request_filters();
    $query = kingy_ali_query_tool_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_directory_has_filters($filters),
            )
        )
    );

    ob_start();
    echo '<section class="kingy-ali-directory kingy-ali-tool-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            __('AI Tool Directory', 'kingy-ai-launch-intelligence'),
            __('Browse permanent AI tool profiles connected to structured launch history, categories, audiences, demos, pricing, and company records.', 'kingy-ai-launch-intelligence')
        );
    }
    echo kingy_ali_render_directory_filters($filters, 'tools');
    echo kingy_ali_render_tool_directory_grid($query);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_shortcode_company_directory($atts = array()) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 160, 'heading' => 'yes'), $atts, 'kingy_company_directory');
    $filters = kingy_ali_directory_request_filters();
    $query = kingy_ali_query_company_directory(
        array_merge(
            $filters,
            array(
                'limit' => absint($atts['limit']),
                'track_search' => kingy_ali_directory_has_filters($filters),
            )
        )
    );

    ob_start();
    echo '<section class="kingy-ali-directory kingy-ali-company-directory">';
    if ($atts['heading'] !== 'no') {
        kingy_ali_render_directory_hero(
            __('AI Company Directory', 'kingy-ai-launch-intelligence'),
            __('Browse AI company and founder profiles connected to launch history, tool portfolios, funding notes, categories, and audience focus.', 'kingy-ai-launch-intelligence')
        );
    }
    echo kingy_ali_render_directory_filters($filters, 'companies');
    echo kingy_ali_render_company_directory_grid($query);
    echo '</section>';

    return ob_get_clean();
}

function kingy_ali_directory_request_filters() {
    return array(
        'q' => kingy_ali_sanitize_public_search_query(kingy_ali_request_get_value('kali_q')),
        'category' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_category')),
        'audience' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_audience')),
        'attribute' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_attribute')),
        'free_plan' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_free_plan')),
        'api_available' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_api_available')),
        'open_source_or_open_weight' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_open_weight')),
        'video_demo' => kingy_ali_sanitize_yes_filter(kingy_ali_request_get_value('kali_video_demo')),
    );
}

function kingy_ali_directory_has_filters($filters) {
    return (bool) array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    });
}

function kingy_ali_query_tool_directory($args = array()) {
    $defaults = array(
        'limit' => 24,
        'q' => '',
        'category' => '',
        'audience' => '',
        'attribute' => '',
        'free_plan' => '',
        'api_available' => '',
        'open_source_or_open_weight' => '',
        'video_demo' => '',
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    $limit = absint($args['limit']);

    $query_args = array(
        'post_type' => 'kingy_ai_tool',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_directory_matching_post_ids('kingy_ai_tool', kingy_ali_tool_search_meta_keys(), array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    kingy_ali_apply_directory_tax_filters($query_args, $args);
    kingy_ali_apply_directory_meta_filters($query_args, $args, true);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) use ($args) {
            if (!kingy_ali_public_query_accepts_index_ready_post($post)) {
                return false;
            }

            return empty($args['video_demo']) || kingy_ali_public_query_accepts_valid_url_meta($post, array('demo_url'));
        }
    );
    if ($args['track_search']) {
        kingy_ali_track_directory_search('tool_directory', $args, $query);
    }

    return $query;
}

function kingy_ali_query_company_directory($args = array()) {
    $defaults = array(
        'limit' => 160,
        'q' => '',
        'category' => '',
        'audience' => '',
        'attribute' => '',
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    $limit = absint($args['limit']);

    $query_args = array(
        'post_type' => 'kingy_ai_company',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_directory_matching_post_ids('kingy_ai_company', kingy_ali_company_search_meta_keys(), array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    kingy_ali_apply_directory_tax_filters($query_args, $args);
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) {
            return kingy_ali_company_directory_card_is_public(kingy_ali_public_query_post_id($post));
        }
    );
    if ($args['track_search']) {
        kingy_ali_track_directory_search('company_directory', $args, $query);
    }

    return $query;
}

function kingy_ali_filter_company_directory_query_posts($query, $limit = 0) {
    if (!is_object($query) || !isset($query->posts)) {
        return $query;
    }

    $posts = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id || !kingy_ali_company_directory_card_is_public($post_id)) {
            continue;
        }

        $posts[] = $post;
    }

    $limit = absint($limit);
    if ($limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    return kingy_ali_replace_query_posts($query, $posts, $limit);
}

function kingy_ali_company_directory_card_is_public($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || !kingy_ali_related_post_is_public($post_id, 'kingy_ai_company')) {
        return false;
    }

    if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($post_id)) {
        return false;
    }

    return kingy_ali_company_directory_has_profile_basics($post_id)
        || kingy_ali_company_directory_has_public_graph($post_id);
}

function kingy_ali_company_directory_has_profile_basics($post_id) {
    $summary = kingy_ali_public_profile_meta_text($post_id, 'company_summary', get_the_excerpt($post_id));
    $official_url = kingy_ali_sanitize_public_source_url(kingy_ali_get_meta($post_id, 'official_url'));

    return $summary !== '' && $official_url !== '';
}

function kingy_ali_company_directory_has_public_graph($post_id) {
    if (!function_exists('kingy_ali_company_related_count')) {
        return false;
    }

    return kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch') > 0
        || kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool') > 0;
}

function kingy_ali_track_directory_search($context, $args, $query) {
    static $tracked = array();

    $filters = kingy_ali_directory_event_filters($context, $args);
    $tracking_key = $context . ':' . md5(wp_json_encode($filters));
    if (isset($tracked[$tracking_key])) {
        return;
    }

    kingy_ali_track_event(
        $query->found_posts === 0 ? 'zero_result_search' : 'search',
        array(
            'event_label' => $context,
            'query_text' => isset($args['q']) ? $args['q'] : '',
            'filters' => $filters,
            'result_count' => (int) $query->found_posts,
        )
    );

    $tracked[$tracking_key] = true;
}

function kingy_ali_directory_event_filters($context, $args) {
    $filters = array(
        'context' => $context,
        'category' => isset($args['category']) ? $args['category'] : '',
        'audience' => isset($args['audience']) ? $args['audience'] : '',
        'attribute' => isset($args['attribute']) ? $args['attribute'] : '',
    );

    if ($context === 'tool_directory') {
        $filters['free_plan'] = isset($args['free_plan']) ? $args['free_plan'] : '';
        $filters['api_available'] = isset($args['api_available']) ? $args['api_available'] : '';
        $filters['open_source_or_open_weight'] = isset($args['open_source_or_open_weight']) ? $args['open_source_or_open_weight'] : '';
        $filters['video_demo'] = isset($args['video_demo']) ? $args['video_demo'] : '';
    }

    return array_filter($filters, function ($value) {
        return $value !== '' && $value !== null;
    });
}

function kingy_ali_directory_matching_post_ids($post_type, $meta_keys, $taxonomies, $query) {
    $ids = array_merge(
        kingy_ali_search_post_ids($post_type, $query),
        kingy_ali_search_meta_post_ids($post_type, $meta_keys, $query),
        kingy_ali_search_taxonomy_post_ids($post_type, $taxonomies, $query)
    );

    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
    sort($ids);
    return $ids;
}

function kingy_ali_apply_directory_tax_filters(&$query_args, $args) {
    $tax_query = array();
    foreach (array(
        'kingy_launch_category' => isset($args['category']) ? $args['category'] : '',
        'kingy_audience' => isset($args['audience']) ? $args['audience'] : '',
        'kingy_tool_attribute' => isset($args['attribute']) ? $args['attribute'] : '',
    ) as $taxonomy => $slug) {
        if ($slug) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => function_exists('kingy_ali_public_filter_slug_terms') ? kingy_ali_public_filter_slug_terms($taxonomy, $slug) : $slug,
            );
        }
    }

    if ($tax_query) {
        $query_args['tax_query'] = $tax_query;
    }
}

function kingy_ali_apply_directory_meta_filters(&$query_args, $args, $include_demo_filter = false) {
    $meta_query = array();
    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        if (!empty($args[$key])) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($key),
                'value' => $args[$key],
                'compare' => '=',
            );
        }
    }

    if ($include_demo_filter && !empty($args['video_demo'])) {
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('demo_url'),
            'value' => '',
            'compare' => '!=',
        );
    }

    if ($meta_query) {
        $query_args['meta_query'] = $meta_query;
    }
}

function kingy_ali_render_directory_hero($title, $description) {
    ?>
    <div class="kingy-ali-hero">
        <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></p>
        <h1><?php echo esc_html($title); ?></h1>
        <p><?php echo esc_html($description); ?></p>
    </div>
    <?php
}

function kingy_ali_render_directory_filters($filters, $context = 'tools') {
    $categories = kingy_ali_public_filter_terms('kingy_launch_category');
    $audiences = kingy_ali_public_filter_terms('kingy_audience');
    $attributes = kingy_ali_public_filter_terms('kingy_tool_attribute');
    $has_filters = kingy_ali_directory_has_filters($filters);
    $reset_url = $context === 'companies' ? home_url('/ai-companies/') : home_url('/ai-tools/');
    $reset_surface = $context === 'companies' ? 'company_directory_filters' : 'tool_directory_filters';

    ob_start();
    ?>
    <form class="kingy-ali-search" method="get">
        <div class="kingy-ali-search__bar">
            <label class="screen-reader-text" for="kingy-ali-directory-q"><?php esc_html_e('Search directory', 'kingy-ai-launch-intelligence'); ?></label>
            <input id="kingy-ali-directory-q" type="search" name="kali_q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="<?php echo esc_attr($context === 'companies' ? __('Search companies, founders, funding, categories, and audiences...', 'kingy-ai-launch-intelligence') : __('Search tools, companies, categories, demos, pricing, and use cases...', 'kingy-ai-launch-intelligence')); ?>">
            <button type="submit"><?php esc_html_e('Search', 'kingy-ai-launch-intelligence'); ?></button>
            <?php if ($has_filters) : ?>
                <a class="kingy-ali-search__reset" data-kingy-ali-track="clicked_directory_reset" data-event-label="<?php esc_attr_e('Reset directory filters', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($reset_surface); ?>" href="<?php echo esc_url($reset_url); ?>"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
        <div class="kingy-ali-filter-grid">
            <?php kingy_ali_render_term_select('kali_category', __('Category', 'kingy-ai-launch-intelligence'), $categories, $filters['category']); ?>
            <?php kingy_ali_render_term_select('kali_audience', __('Audience', 'kingy-ai-launch-intelligence'), $audiences, $filters['audience']); ?>
            <?php kingy_ali_render_term_select('kali_attribute', __('Attribute', 'kingy-ai-launch-intelligence'), $attributes, $filters['attribute']); ?>
            <?php if ($context !== 'companies') : ?>
                <?php kingy_ali_render_yes_no_select('kali_free_plan', __('Free plan', 'kingy-ai-launch-intelligence'), $filters['free_plan']); ?>
                <?php kingy_ali_render_yes_no_select('kali_api_available', __('API', 'kingy-ai-launch-intelligence'), $filters['api_available']); ?>
                <?php kingy_ali_render_yes_no_select('kali_open_weight', __('Open source/weight', 'kingy-ai-launch-intelligence'), $filters['open_source_or_open_weight']); ?>
                <label>
                    <span><?php esc_html_e('Demo', 'kingy-ai-launch-intelligence'); ?></span>
                    <select name="kali_video_demo">
                        <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="yes" <?php selected($filters['video_demo'], 'yes'); ?>><?php esc_html_e('Demo available', 'kingy-ai-launch-intelligence'); ?></option>
                    </select>
                </label>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_tool_directory_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_tool_directory_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_directory_empty_state('tools');
    }

    return ob_get_clean();
}

function kingy_ali_render_company_directory_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_company_directory_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_directory_empty_state('companies');
    }

    return ob_get_clean();
}

function kingy_ali_render_directory_empty_state($context) {
    $is_company_context = $context === 'companies';
    $surface = $is_company_context ? 'company_no_results' : 'tool_no_results';
    $browse_url = $is_company_context ? home_url('/ai-companies/') : home_url('/ai-tools/');
    $browse_label = $is_company_context ? __('Browse all companies', 'kingy-ai-launch-intelligence') : __('Browse all tools', 'kingy-ai-launch-intelligence');
    $heading = $is_company_context ? __('No matching company profiles yet.', 'kingy-ai-launch-intelligence') : __('No matching tool profiles yet.', 'kingy-ai-launch-intelligence');
    $body = $is_company_context
        ? __('That gap is useful signal for company coverage, founder research, and partner-fit planning. Submit a related launch, request a visibility review, or reset to the full company directory.', 'kingy-ai-launch-intelligence')
        : __('That gap is useful signal for the tool graph and Launch Intelligence roadmap. Submit a missing launch, request a visibility review, or reset to the full tool directory.', 'kingy-ai-launch-intelligence');
    $submit_label = $is_company_context ? __('Submit a related launch', 'kingy-ai-launch-intelligence') : __('Submit a missing launch', 'kingy-ai-launch-intelligence');
    $submit_event = $is_company_context ? __('Submit missing company launch from no results', 'kingy-ai-launch-intelligence') : __('Submit missing tool launch from no results', 'kingy-ai-launch-intelligence');
    $visibility_event = $is_company_context ? __('Get visibility score from company no results', 'kingy-ai-launch-intelligence') : __('Get visibility score from tool no results', 'kingy-ai-launch-intelligence');
    $browse_event = $is_company_context ? __('Browse all companies from no results', 'kingy-ai-launch-intelligence') : __('Browse all tools from no results', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <div class="kingy-ali-empty">
        <h3><?php echo esc_html($heading); ?></h3>
        <p><?php echo esc_html($body); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php echo esc_attr($submit_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php echo esc_html($submit_label); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php echo esc_attr($visibility_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_directory_reset" data-event-label="<?php echo esc_attr($browse_event); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($browse_url); ?>"><?php echo esc_html($browse_label); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_tool_directory_card($post_id) {
    $latest_launch_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
    if (!kingy_ali_related_post_is_public_index_ready($latest_launch_id, 'kingy_ai_launch')) {
        $latest_launch_id = 0;
    }
    $summary = kingy_ali_public_profile_meta_text($post_id, 'what_it_does', get_the_excerpt($post_id));
    $company = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'company'));
    $pricing = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'pricing'));

    ob_start();
    ?>
    <article class="kingy-ali-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_directory_terms_to_string($post_id, 'kingy_launch_category', __('Uncategorized', 'kingy-ai-launch-intelligence'))); ?></span>
        </div>
        <h3><a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words($summary, 28)); ?></p>
        <div class="kingy-ali-badges">
            <?php kingy_ali_render_fact_badge(__('Free', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'free_plan')); ?>
            <?php kingy_ali_render_fact_badge(__('API', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'api_available')); ?>
            <?php kingy_ali_render_fact_badge(__('Open', 'kingy-ai-launch-intelligence'), kingy_ali_public_profile_meta_text($post_id, 'open_source_or_open_weight')); ?>
        </div>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Company', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($company !== '' ? $company : __('Company not confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
            <div><dt><?php esc_html_e('Pricing', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($pricing !== '' ? $pricing : __('Not publicly confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_tool" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('View tool', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($latest_launch_id) : ?>
                <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($latest_launch_id); ?>" data-event-surface="tool_directory_card" href="<?php echo esc_url(get_permalink($latest_launch_id)); ?>"><?php esc_html_e('Latest launch', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_company_directory_card($post_id) {
    $launch_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch');
    $tool_count = kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool');
    $summary = kingy_ali_public_profile_meta_text($post_id, 'company_summary', get_the_excerpt($post_id));
    $funding = kingy_ali_directory_public_fact_value(kingy_ali_public_profile_meta_text($post_id, 'funding'));
    $launch_count_label = sprintf(_n('%s launch', '%s launches', $launch_count, 'kingy-ai-launch-intelligence'), number_format_i18n($launch_count));
    $tool_count_label = sprintf(_n('%s tool', '%s tools', $tool_count, 'kingy-ai-launch-intelligence'), number_format_i18n($tool_count));
    $graph_status = ($launch_count > 0 || $tool_count > 0)
        ? sprintf(__('%1$s / %2$s', 'kingy-ai-launch-intelligence'), $launch_count_label, $tool_count_label)
        : __('Needs verified launch/tool links', 'kingy-ai-launch-intelligence');
    $profile_status = ($launch_count > 0 || $tool_count > 0)
        ? __('Connected profile', 'kingy-ai-launch-intelligence')
        : __('Needs verification', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <article class="kingy-ali-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_directory_terms_to_string($post_id, 'kingy_launch_category', __('Uncategorized', 'kingy-ai-launch-intelligence'))); ?></span>
            <span><?php echo esc_html($profile_status); ?></span>
        </div>
        <h3><a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="company_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words($summary, 28)); ?></p>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Funding', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($funding !== '' ? $funding : __('Not publicly confirmed', 'kingy-ai-launch-intelligence')); ?></dd></div>
            <div><dt><?php esc_html_e('Graph', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($graph_status); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_company" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="company_directory_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('View company', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_directory_public_fact_value($value) {
    $value = trim(wp_strip_all_tags((string) $value));
    $normalized = strtolower(preg_replace('/\s+/', ' ', $value));

    if (in_array($normalized, array('unknown', 'n/a', 'na', 'none', 'tbd', 'to be confirmed', 'not known'), true)) {
        return '';
    }

    return $value;
}

function kingy_ali_directory_terms_to_string($post_id, $taxonomy, $fallback = '') {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return $fallback;
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function kingy_ali_directory_related_count($post_type, $meta_key, $post_id) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key($meta_key),
                    'value' => absint($post_id),
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    return (int) $query->post_count;
}
