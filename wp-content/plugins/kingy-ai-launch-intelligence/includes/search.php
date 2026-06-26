<?php

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_request_filters() {
    return array(
        'q' => kingy_ali_sanitize_public_search_query(kingy_ali_request_get_value('kali_q')),
        'period' => kingy_ali_sanitize_period_filter(kingy_ali_request_get_value('kali_period')),
        'category' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_category')),
        'launch_type' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_launch_type')),
        'audience' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_audience')),
        'attribute' => kingy_ali_sanitize_slug_filter(kingy_ali_request_get_value('kali_attribute')),
        'free_plan' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_free_plan')),
        'api_available' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_api_available')),
        'open_source_or_open_weight' => kingy_ali_sanitize_yes_no_filter(kingy_ali_request_get_value('kali_open_weight')),
        'video_demo' => kingy_ali_sanitize_yes_filter(kingy_ali_request_get_value('kali_video_demo')),
        'youtube_potential' => kingy_ali_sanitize_yes_filter(kingy_ali_request_get_value('kali_youtube_potential')),
    );
}

function kingy_ali_request_get_value($key) {
    $values = kingy_ali_request_get_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_request_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_sanitize_public_search_query($query) {
    $query = sanitize_text_field((string) $query);
    $query = preg_replace('/\s+/', ' ', $query);
    $query = trim((string) $query);
    if (strlen($query) > 120) {
        $query = wp_html_excerpt($query, 120, '');
    }

    return $query;
}

function kingy_ali_sanitize_slug_filter($slug) {
    $slug = sanitize_title((string) $slug);
    return strlen($slug) <= 120 ? $slug : '';
}

function kingy_ali_sanitize_yes_no_filter($value) {
    $value = sanitize_key((string) $value);
    return in_array($value, array('yes', 'no'), true) ? $value : '';
}

function kingy_ali_sanitize_yes_filter($value) {
    return sanitize_key((string) $value) === 'yes' ? 'yes' : '';
}

function kingy_ali_launch_has_filters($filters) {
    return (bool) array_filter($filters, function ($value) {
        return $value !== '' && $value !== null && $value !== false;
    });
}

function kingy_ali_sanitize_period_filter($period) {
    $period = sanitize_key($period);
    return in_array($period, array('today', 'week'), true) ? $period : '';
}

function kingy_ali_public_query_batch_size($limit = 0) {
    $limit = absint($limit);
    $batch_size = $limit > 0 ? max(24, $limit * 3) : 60;
    $batch_size = min(120, $batch_size);

    return max(1, (int) apply_filters('kingy_ali_public_query_batch_size', $batch_size, $limit));
}

function kingy_ali_public_query_scan_limit($limit = 0) {
    $limit = absint($limit);
    $scan_limit = $limit > 0 ? max(200, $limit * 30) : 600;
    $scan_limit = min(1000, $scan_limit);

    return max(1, (int) apply_filters('kingy_ali_public_query_scan_limit', $scan_limit, $limit));
}

function kingy_ali_append_meta_query_constraint(&$query_args, $constraint) {
    if (empty($constraint) || !is_array($constraint)) {
        return;
    }

    if (empty($query_args['meta_query']) || !is_array($query_args['meta_query'])) {
        $query_args['meta_query'] = array($constraint);
        return;
    }

    $query_args['meta_query'] = array(
        'relation' => 'AND',
        $query_args['meta_query'],
        $constraint,
    );
}

function kingy_ali_apply_public_noindex_meta_constraint(&$query_args) {
    kingy_ali_append_meta_query_constraint(
        $query_args,
        array(
            'relation' => 'OR',
            array(
                'key' => kingy_ali_meta_key('noindex'),
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => kingy_ali_meta_key('noindex'),
                'value' => '',
                'compare' => '=',
            ),
        )
    );
}

function kingy_ali_public_query_post_id($post) {
    return is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
}

function kingy_ali_public_query_accepts_index_ready_post($post) {
    $post_id = kingy_ali_public_query_post_id($post);
    if (!$post_id) {
        return false;
    }

    return !function_exists('kingy_ali_profile_should_noindex') || !kingy_ali_profile_should_noindex($post_id);
}

function kingy_ali_public_query_accepts_valid_url_meta($post, $meta_keys) {
    $post_id = kingy_ali_public_query_post_id($post);
    if (!$post_id) {
        return false;
    }

    foreach ((array) $meta_keys as $key) {
        if (kingy_ali_public_meta_url_is_valid($post_id, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_run_public_filtered_query($query_args, $limit, $predicate) {
    $limit = absint($limit);
    $batch_size = kingy_ali_public_query_batch_size($limit);
    $scan_limit = kingy_ali_public_query_scan_limit($limit);
    $offset = 0;
    $scanned = 0;
    $posts = array();
    $query = null;

    while ($scanned < $scan_limit) {
        $remaining_scan = $scan_limit - $scanned;
        $paged_args = $query_args;
        $paged_args['posts_per_page'] = min($batch_size, $remaining_scan);
        $paged_args['offset'] = $offset;
        $paged_args['no_found_rows'] = true;
        $paged_args['ignore_sticky_posts'] = true;

        $query = new WP_Query($paged_args);
        $batch_posts = isset($query->posts) ? (array) $query->posts : array();
        if (!$batch_posts) {
            break;
        }

        foreach ($batch_posts as $post) {
            $scanned++;
            if (call_user_func($predicate, $post)) {
                $posts[] = $post;
                if ($limit > 0 && count($posts) >= $limit) {
                    break 2;
                }
            }

            if ($scanned >= $scan_limit) {
                break 2;
            }
        }

        if (count($batch_posts) < $paged_args['posts_per_page']) {
            break;
        }

        $offset += $paged_args['posts_per_page'];
    }

    if (!$query) {
        $query = new WP_Query();
    }

    return kingy_ali_replace_query_posts($query, $posts, $limit);
}

function kingy_ali_replace_query_posts($query, $posts, $limit = 0) {
    $limit = absint($limit);
    $posts = array_values((array) $posts);
    if ($limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    $total_posts = count($posts);
    $query->posts = $posts;
    $query->post_count = $total_posts;
    $query->found_posts = $total_posts;
    $query->max_num_pages = $limit > 0 && $total_posts > 0 ? (int) ceil($total_posts / $limit) : ($total_posts > 0 ? 1 : 0);
    $query->current_post = -1;
    $query->in_the_loop = false;

    return $query;
}

function kingy_ali_empty_launch_query($limit = 0) {
    return kingy_ali_replace_query_posts(new WP_Query(), array(), absint($limit));
}

function kingy_ali_log_public_query_failure($context, $throwable = null) {
    if (!function_exists('error_log')) {
        return;
    }

    $message = is_scalar($context) ? sanitize_key((string) $context) : 'unknown';
    if ($throwable instanceof Throwable) {
        $message .= ': ' . sanitize_text_field($throwable->getMessage());
    }

    error_log('Kingy AI Launch Intelligence public query fail-open: ' . $message);
}

function kingy_ali_public_filter_terms($taxonomy) {
    static $cache = array();

    $taxonomy = sanitize_key($taxonomy);
    if ($taxonomy === '') {
        return array();
    }

    if (!array_key_exists($taxonomy, $cache)) {
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
        $cache[$taxonomy] = is_wp_error($terms) ? array() : $terms;
    }

    return $cache[$taxonomy];
}

function kingy_ali_query_launches($args = array()) {
    $defaults = array(
        'limit' => 12,
        'period' => '',
        'category' => '',
        'launch_type' => '',
        'audience' => '',
        'attribute' => '',
        'q' => '',
        'free_plan' => '',
        'api_available' => '',
        'open_source_or_open_weight' => '',
        'video_demo' => '',
        'youtube_potential' => '',
        'youtube_worthy' => false,
        'creator_coverage' => false,
        'track_search' => false,
    );
    $args = wp_parse_args($args, $defaults);
    if (!empty($args['youtube_potential'])) {
        $args['youtube_worthy'] = true;
    }
    $limit = absint($args['limit']);
    $period = isset($args['period']) && is_scalar($args['period']) ? sanitize_key((string) $args['period']) : '';
    if ($period !== '' && !in_array($period, array('today', 'week'), true)) {
        return kingy_ali_empty_launch_query($limit);
    }
    $args['period'] = $period;

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'meta_key' => kingy_ali_meta_key('launch_date'),
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    if ($args['q']) {
        $matching_ids = kingy_ali_search_matching_launch_ids($args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
    }

    $tax_query = array('relation' => 'AND');
    foreach (array(
        'kingy_launch_category' => $args['category'],
        'kingy_launch_type' => $args['launch_type'],
        'kingy_audience' => $args['audience'],
        'kingy_tool_attribute' => $args['attribute'],
    ) as $taxonomy => $slug) {
        if ($slug) {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => kingy_ali_public_filter_slug_terms($taxonomy, $slug),
            );
        }
    }

    if ($args['creator_coverage']) {
        $tax_query[] = kingy_ali_creator_coverage_tax_query();
    }

    if (count($tax_query) > 1) {
        $query_args['tax_query'] = $tax_query;
    }

    $meta_query = array();
    if ($args['period'] === 'today') {
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('launch_date'),
            'value' => current_time('Y-m-d'),
            'compare' => '=',
        );
    } elseif ($args['period'] === 'week') {
        $today = current_time('Y-m-d');
        $week_start = date_i18n('Y-m-d', strtotime('-6 days', current_time('timestamp')));
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('launch_date'),
            'value' => array($week_start, $today),
            'compare' => 'BETWEEN',
        );
    }

    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        if ($args[$key]) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($key),
                'value' => $args[$key],
                'compare' => '=',
            );
        }
    }

    if ($args['video_demo']) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => kingy_ali_meta_key('demo_url'),
                'value' => '',
                'compare' => '!=',
            ),
            array(
                'key' => kingy_ali_meta_key('youtube_url'),
                'value' => '',
                'compare' => '!=',
            ),
        );
    }

    if ($args['youtube_worthy']) {
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('youtube_score'),
            'value' => 7,
            'compare' => '>=',
            'type' => 'NUMERIC',
        );
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = kingy_ali_meta_key('youtube_score');
    }

    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $video_meta_keys = array('demo_url', 'youtube_url');
    try {
        $query = kingy_ali_run_public_filtered_query(
            $query_args,
            $limit,
            function ($post) use ($args, $video_meta_keys) {
                if (!kingy_ali_public_query_accepts_index_ready_post($post)) {
                    return false;
                }

                return empty($args['video_demo']) || kingy_ali_public_query_accepts_valid_url_meta($post, $video_meta_keys);
            }
        );
    } catch (Throwable $throwable) {
        kingy_ali_log_public_query_failure('query_launches', $throwable);
        return kingy_ali_empty_launch_query($limit);
    }

    if ($args['track_search']) {
        static $tracked_searches = array();
        $tracking_filters = kingy_ali_launch_search_event_filters($args);
        $tracking_key = md5(wp_json_encode($tracking_filters));

        if (empty($tracked_searches[$tracking_key])) {
            kingy_ali_track_event(
                $query->found_posts === 0 ? 'zero_result_search' : 'search',
                array(
                    'query_text' => $args['q'],
                    'event_label' => kingy_ali_launch_search_surface($args),
                    'filters' => $tracking_filters,
                    'result_count' => (int) $query->found_posts,
                )
            );
            $tracked_searches[$tracking_key] = true;
        }
    }

    return $query;
}

function kingy_ali_filter_public_query_index_ready_posts($query, $limit = 0) {
    if (!is_object($query) || !isset($query->posts)) {
        return $query;
    }

    $posts = array();
    $limit = absint($limit);
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id) {
            continue;
        }

        if (function_exists('kingy_ali_profile_should_noindex') && kingy_ali_profile_should_noindex($post_id)) {
            continue;
        }

        $posts[] = $post;
        if ($limit > 0 && count($posts) >= $limit) {
            break;
        }
    }

    $total_posts = count($posts);
    if ($limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    $query->posts = $posts;
    $query->post_count = count($posts);
    $query->found_posts = $total_posts;
    $query->max_num_pages = $limit > 0 && $total_posts > 0 ? (int) ceil($total_posts / $limit) : ($total_posts > 0 ? 1 : 0);

    return $query;
}

function kingy_ali_filter_public_query_by_valid_url_meta($query, $meta_keys, $limit = 0) {
    if (!is_object($query) || !isset($query->posts)) {
        return $query;
    }

    $meta_keys = array_values(array_filter(array_map('sanitize_key', (array) $meta_keys)));
    if (!$meta_keys) {
        return $query;
    }

    $posts = array();
    $limit = absint($limit);
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id) {
            continue;
        }

        foreach ($meta_keys as $key) {
            if (kingy_ali_public_meta_url_is_valid($post_id, $key)) {
                $posts[] = $post;
                if ($limit > 0 && count($posts) >= $limit) {
                    break 2;
                }
                continue 2;
            }
        }
    }

    $total_posts = count($posts);
    if ($limit > 0) {
        $posts = array_slice($posts, 0, $limit);
    }

    $query->posts = $posts;
    $query->post_count = count($posts);
    $query->found_posts = $total_posts;
    $query->max_num_pages = $limit > 0 && $total_posts > 0 ? (int) ceil($total_posts / $limit) : ($total_posts > 0 ? 1 : 0);

    return $query;
}

function kingy_ali_public_meta_url_is_valid($post_id, $key) {
    return kingy_ali_public_url_value(kingy_ali_get_meta($post_id, $key)) !== '';
}

function kingy_ali_public_url_value($url) {
    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($url);
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url);
    }

    if (function_exists('kingy_ali_rest_public_url_value')) {
        return kingy_ali_rest_public_url_value($url);
    }

    if (!is_scalar($url)) {
        return '';
    }

    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url, array('http', 'https'));
    if ($url === '') {
        return '';
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true) ? $url : '';
}

function kingy_ali_launch_search_event_filters($args) {
    $filters = array(
        'q' => isset($args['q']) ? $args['q'] : '',
        'period' => isset($args['period']) ? $args['period'] : '',
        'category' => isset($args['category']) ? $args['category'] : '',
        'launch_type' => isset($args['launch_type']) ? $args['launch_type'] : '',
        'audience' => isset($args['audience']) ? $args['audience'] : '',
        'attribute' => isset($args['attribute']) ? $args['attribute'] : '',
        'free_plan' => isset($args['free_plan']) ? $args['free_plan'] : '',
        'api_available' => isset($args['api_available']) ? $args['api_available'] : '',
        'open_source_or_open_weight' => isset($args['open_source_or_open_weight']) ? $args['open_source_or_open_weight'] : '',
        'video_demo' => isset($args['video_demo']) ? $args['video_demo'] : '',
        'youtube_potential' => isset($args['youtube_potential']) ? $args['youtube_potential'] : '',
    );

    if (!empty($args['youtube_worthy'])) {
        $filters['collection'] = 'youtube_worthy';
    }

    if (!empty($args['creator_coverage'])) {
        $filters['collection'] = 'creator_coverage';
    }

    return array_filter(
        $filters,
        function ($value) {
            return $value !== '' && $value !== false && $value !== null;
        }
    );
}

function kingy_ali_launch_search_surface($args) {
    if (!empty($args['creator_coverage'])) {
        return 'creator_coverage';
    }

    if (!empty($args['youtube_worthy'])) {
        return 'youtube_worthy';
    }

    if (!empty($args['period'])) {
        return 'launch_' . sanitize_key($args['period']);
    }

    return 'launch_hub';
}

function kingy_ali_creator_coverage_tax_query() {
    return array(
        'relation' => 'OR',
        array(
            'taxonomy' => 'kingy_tool_attribute',
            'field' => 'slug',
            'terms' => kingy_ali_creator_coverage_attribute_slugs(),
            'operator' => 'IN',
        ),
        array(
            'taxonomy' => 'kingy_audience',
            'field' => 'slug',
            'terms' => kingy_ali_creator_coverage_audience_slugs(),
            'operator' => 'IN',
        ),
        array(
            'taxonomy' => 'kingy_launch_category',
            'field' => 'slug',
            'terms' => kingy_ali_creator_coverage_category_slugs(),
            'operator' => 'IN',
        ),
    );
}

function kingy_ali_creator_coverage_attribute_slugs() {
    return array(
        'creator-coverage-candidate',
        'strong-demo',
        'clear-use-case',
        'video-demo-available',
        'creator-friendly',
        'business-friendly',
        'developer-friendly',
        'traction-signal',
        'product-hunt-traction',
        'founder-submitted',
        'funding-announced',
    );
}

function kingy_ali_creator_coverage_audience_slugs() {
    return array(
        'creators',
        'youtubers',
        'founders',
        'marketers',
        'small-business-owners',
        'agencies',
        'developers',
    );
}

function kingy_ali_creator_coverage_category_slugs() {
    return array(
        'ai-video-tools',
        'ai-agents',
        'ai-coding-tools',
        'open-weight-models',
        'ai-search-tools',
        'ai-browser-agents',
        'ai-marketing-tools',
        'ai-productivity-tools',
        'ai-image-tools',
        'ai-voice-audio-tools',
        'ai-music-tools',
        'ai-writing-tools',
        'ai-infrastructure',
        'ai-developer-tools',
        'ai-local-models',
        'ai-automation-tools',
        'ai-research-tools',
        'ai-security-tools',
        'ai-robotics',
        'ai-hardware',
    );
}

function kingy_ali_search_matching_launch_ids($query) {
    $query = kingy_ali_sanitize_public_search_query($query);
    if ($query === '') {
        return array();
    }

    $ids = array();
    $ids = array_merge($ids, kingy_ali_search_post_ids('kingy_ai_launch', $query));
    $ids = array_merge($ids, kingy_ali_search_meta_post_ids('kingy_ai_launch', kingy_ali_launch_search_meta_keys(), $query));
    $ids = array_merge($ids, kingy_ali_search_taxonomy_post_ids('kingy_ai_launch', array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute', 'kingy_launch_type'), $query));

    $tool_ids = array_unique(
        array_merge(
            kingy_ali_search_post_ids('kingy_ai_tool', $query),
            kingy_ali_search_meta_post_ids('kingy_ai_tool', kingy_ali_tool_search_meta_keys(), $query),
            kingy_ali_search_taxonomy_post_ids('kingy_ai_tool', array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $query)
        )
    );
    $tool_ids = kingy_ali_filter_public_index_ready_related_ids($tool_ids, 'kingy_ai_tool');
    $ids = array_merge($ids, kingy_ali_search_launch_ids_for_related_records('related_tool_id', $tool_ids));

    $company_ids = array_unique(
        array_merge(
            kingy_ali_search_post_ids('kingy_ai_company', $query),
            kingy_ali_search_meta_post_ids('kingy_ai_company', kingy_ali_company_search_meta_keys(), $query),
            kingy_ali_search_taxonomy_post_ids('kingy_ai_company', array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute'), $query)
        )
    );
    $company_ids = kingy_ali_filter_public_index_ready_related_ids($company_ids, 'kingy_ai_company');
    $ids = array_merge($ids, kingy_ali_search_launch_ids_for_related_records('related_company_id', $company_ids));

    $ids = array_filter(array_map('absint', $ids));
    $ids = array_values(array_unique($ids));
    rsort($ids);

    return $ids;
}

function kingy_ali_filter_public_index_ready_related_ids($post_ids, $post_type) {
    $post_type = sanitize_key($post_type);
    $filtered_ids = array();
    foreach ((array) $post_ids as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id) {
            continue;
        }

        if (kingy_ali_related_post_is_public_index_ready($post_id, $post_type)) {
            $filtered_ids[] = $post_id;
        }
    }

    $filtered_ids = array_values(array_unique($filtered_ids));
    sort($filtered_ids);
    return $filtered_ids;
}

function kingy_ali_launch_search_meta_keys() {
    return array(
        'company',
        'official_url',
        'launch_date',
        'what_launched',
        'who_it_is_for',
        'pricing',
        'pricing_url',
        'funding',
        'press_kit_url',
        'founder_team',
        'media_urls',
        'sources',
        'reddit_signal',
        'youtube_signal',
        'traction_notes',
        'kingy_verdict',
        'what_feels_promising',
        'what_feels_unproven',
    );
}

function kingy_ali_tool_search_meta_keys() {
    return array(
        'company',
        'official_url',
        'demo_url',
        'what_it_does',
        'best_for',
        'pricing',
        'main_competitors',
        'alternatives_url',
        'related_article_url',
        'related_course_url',
        'related_review_url',
    );
}

function kingy_ali_company_search_meta_keys() {
    return array(
        'official_url',
        'company_summary',
        'founder_team',
        'funding',
        'contact_url',
    );
}

function kingy_ali_search_post_ids($post_type, $query) {
    global $wpdb;

    $like = '%' . $wpdb->esc_like($query) . '%';
    $sql = "
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = %s
            AND post_status = 'publish'
            AND (post_title LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s)
    ";

    return array_map('absint', $wpdb->get_col($wpdb->prepare($sql, $post_type, $like, $like, $like)));
}

function kingy_ali_search_meta_post_ids($post_type, $meta_keys, $query) {
    global $wpdb;

    $meta_keys = array_map('kingy_ali_meta_key', $meta_keys);
    if (empty($meta_keys)) {
        return array();
    }

    $placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
    $like = '%' . $wpdb->esc_like($query) . '%';
    $sql = "
        SELECT DISTINCT pm.post_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key IN ({$placeholders})
            AND pm.meta_value LIKE %s
    ";

    $args = array_merge(array($post_type), $meta_keys, array($like));
    return array_map('absint', $wpdb->get_col(kingy_ali_wpdb_prepare($sql, $args)));
}

function kingy_ali_search_taxonomy_post_ids($post_type, $taxonomies, $query) {
    global $wpdb;

    if (empty($taxonomies)) {
        return array();
    }

    $placeholders = implode(', ', array_fill(0, count($taxonomies), '%s'));
    $like = '%' . $wpdb->esc_like($query) . '%';
    $sql = "
        SELECT DISTINCT tr.object_id
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
        WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND tt.taxonomy IN ({$placeholders})
            AND (t.name LIKE %s OR t.slug LIKE %s)
    ";

    $args = array_merge(array($post_type), $taxonomies, array($like, $like));
    return array_map('absint', $wpdb->get_col(kingy_ali_wpdb_prepare($sql, $args)));
}

function kingy_ali_search_launch_ids_for_related_records($meta_key, $related_ids) {
    global $wpdb;

    $related_ids = array_filter(array_map('absint', $related_ids));
    if (empty($related_ids)) {
        return array();
    }

    $placeholders = implode(', ', array_fill(0, count($related_ids), '%d'));
    $sql = "
        SELECT DISTINCT pm.post_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.post_type = 'kingy_ai_launch'
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            AND CAST(pm.meta_value AS UNSIGNED) IN ({$placeholders})
    ";

    $args = array_merge(array(kingy_ali_meta_key($meta_key)), $related_ids);
    return array_map('absint', $wpdb->get_col(kingy_ali_wpdb_prepare($sql, $args)));
}

function kingy_ali_wpdb_prepare($sql, $args) {
    global $wpdb;
    return call_user_func_array(array($wpdb, 'prepare'), array_merge(array($sql), $args));
}

function kingy_ali_render_launch_search($filters = array()) {
    kingy_ali_enqueue_assets();

    $filters = wp_parse_args($filters, kingy_ali_request_filters());
    $has_filters = kingy_ali_launch_has_filters($filters);
    $categories = kingy_ali_public_filter_terms('kingy_launch_category');
    $launch_types = kingy_ali_public_filter_terms('kingy_launch_type');
    $audiences = kingy_ali_public_filter_terms('kingy_audience');
    $attributes = kingy_ali_public_filter_terms('kingy_tool_attribute');
    $advanced_open = !empty($filters['launch_type']) || !empty($filters['audience']) || !empty($filters['attribute']);

    ob_start();
    ?>
    <form class="kingy-ali-search" method="get">
        <div class="kingy-ali-search__bar">
            <label class="screen-reader-text" for="kingy-ali-q"><?php esc_html_e('Search AI launches', 'kingy-ai-launch-intelligence'); ?></label>
            <input id="kingy-ali-q" type="search" name="kali_q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="<?php esc_attr_e('Search AI launches, tools, companies, models, categories, and use cases...', 'kingy-ai-launch-intelligence'); ?>">
            <button type="submit"><?php esc_html_e('Search', 'kingy-ai-launch-intelligence'); ?></button>
            <?php if ($has_filters) : ?>
                <a class="kingy-ali-search__reset" data-kingy-ali-track="clicked_filter_reset" data-event-label="<?php esc_attr_e('Reset launch filters', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_search" href="<?php echo esc_url(kingy_ali_launch_filter_base_url()); ?>"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
        <div class="kingy-ali-filter-grid">
            <?php kingy_ali_render_term_select('kali_category', __('Category', 'kingy-ai-launch-intelligence'), $categories, $filters['category']); ?>
            <?php kingy_ali_render_period_select($filters['period']); ?>
            <?php kingy_ali_render_yes_no_select('kali_free_plan', __('Free plan', 'kingy-ai-launch-intelligence'), $filters['free_plan']); ?>
            <?php kingy_ali_render_yes_no_select('kali_api_available', __('API', 'kingy-ai-launch-intelligence'), $filters['api_available']); ?>
            <?php kingy_ali_render_yes_no_select('kali_open_weight', __('Open source/weight', 'kingy-ai-launch-intelligence'), $filters['open_source_or_open_weight']); ?>
            <label>
                <span><?php esc_html_e('Demo', 'kingy-ai-launch-intelligence'); ?></span>
                <select name="kali_video_demo">
                    <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
                    <option value="yes" <?php selected($filters['video_demo'], 'yes'); ?>><?php esc_html_e('Video/demo available', 'kingy-ai-launch-intelligence'); ?></option>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('YouTube potential', 'kingy-ai-launch-intelligence'); ?></span>
                <select name="kali_youtube_potential">
                    <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
                    <option value="yes" <?php selected($filters['youtube_potential'], 'yes'); ?>><?php esc_html_e('High potential', 'kingy-ai-launch-intelligence'); ?></option>
                </select>
            </label>
        </div>
        <details class="kingy-ali-advanced-filters"<?php echo $advanced_open ? ' open' : ''; ?>>
            <summary><?php esc_html_e('Advanced filters', 'kingy-ai-launch-intelligence'); ?></summary>
            <div class="kingy-ali-filter-grid">
                <?php kingy_ali_render_term_select('kali_launch_type', __('Launch type', 'kingy-ai-launch-intelligence'), $launch_types, $filters['launch_type']); ?>
                <?php kingy_ali_render_term_select('kali_audience', __('Audience', 'kingy-ai-launch-intelligence'), $audiences, $filters['audience']); ?>
                <?php kingy_ali_render_term_select('kali_attribute', __('Signal / attribute', 'kingy-ai-launch-intelligence'), $attributes, $filters['attribute']); ?>
            </div>
            <?php echo kingy_ali_render_launch_signal_filter_chips($filters); ?>
        </details>
        <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_period_select($selected_value) {
    ?>
    <label>
        <span><?php esc_html_e('Launch date', 'kingy-ai-launch-intelligence'); ?></span>
        <select name="kali_period">
            <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
            <option value="today" <?php selected($selected_value, 'today'); ?>><?php esc_html_e('Today', 'kingy-ai-launch-intelligence'); ?></option>
            <option value="week" <?php selected($selected_value, 'week'); ?>><?php esc_html_e('This week', 'kingy-ai-launch-intelligence'); ?></option>
        </select>
    </label>
    <?php
}

function kingy_ali_render_term_select($name, $label, $terms, $selected_slug) {
    $options = kingy_ali_public_filter_term_options($terms, $selected_slug);
    ?>
    <label>
        <span><?php echo esc_html($label); ?></span>
        <select name="<?php echo esc_attr($name); ?>">
            <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
            <?php foreach ($options as $option) : ?>
                <option value="<?php echo esc_attr($option['slug']); ?>" <?php selected($selected_slug, $option['slug']); ?>><?php echo esc_html($option['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}

function kingy_ali_public_filter_slug_terms($taxonomy, $slug) {
    $taxonomy = sanitize_key($taxonomy);
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        return array();
    }

    $aliases = array(
        'kingy_launch_category' => array(
            'ai-open-weight-models' => array('ai-open-weight-models', 'open-weight-models'),
            'open-weight-models' => array('ai-open-weight-models', 'open-weight-models'),
            'ai-search-research-tools' => array('ai-search-tools', 'ai-research-tools'),
        ),
    );

    if (isset($aliases[$taxonomy][$slug])) {
        return array_values(array_unique(array_map('sanitize_title', $aliases[$taxonomy][$slug])));
    }

    return array($slug);
}

function kingy_ali_public_filter_term_options($terms, $selected_slug = '') {
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    $selected_slug = sanitize_title((string) $selected_slug);
    $options = array();
    $seen_labels = array();
    $seen_slugs = array();

    foreach ($terms as $term) {
        if (!is_object($term) || empty($term->slug)) {
            continue;
        }

        $slug = sanitize_title($term->slug);
        $taxonomy = isset($term->taxonomy) ? sanitize_key($term->taxonomy) : '';
        $label = kingy_ali_public_filter_term_label($term, $taxonomy);
        if ($label === '') {
            continue;
        }

        $label_key = strtolower($label);
        if (isset($seen_slugs[$slug])) {
            continue;
        }

        if (isset($seen_labels[$label_key]) && $slug !== $selected_slug) {
            continue;
        }

        $seen_labels[$label_key] = true;
        $seen_slugs[$slug] = true;
        $options[] = array(
            'slug' => $slug,
            'label' => $label,
        );
    }

    usort(
        $options,
        function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        }
    );

    return $options;
}

function kingy_ali_public_filter_term_label($term, $taxonomy = '') {
    $slug = isset($term->slug) ? sanitize_title($term->slug) : '';
    $raw_label = isset($term->name) && is_scalar($term->name) ? (string) $term->name : '';
    $label = kingy_ali_normalize_public_filter_label($raw_label);

    $canonical = kingy_ali_public_filter_canonical_labels($taxonomy);
    if ($slug && isset($canonical[$slug])) {
        return $canonical[$slug];
    }

    if (!kingy_ali_public_filter_label_is_clean($label)) {
        return '';
    }

    return $label;
}

function kingy_ali_public_filter_canonical_labels($taxonomy = '') {
    $labels = array(
        'kingy_launch_category' => array(
            'ai-agents' => __('AI Agents', 'kingy-ai-launch-intelligence'),
            'ai-automation-tools' => __('AI Automation Tools', 'kingy-ai-launch-intelligence'),
            'ai-browser-agents' => __('AI Browser Agents', 'kingy-ai-launch-intelligence'),
            'ai-coding-tools' => __('AI Coding Tools', 'kingy-ai-launch-intelligence'),
            'ai-developer-tools' => __('AI Developer Tools', 'kingy-ai-launch-intelligence'),
            'ai-ecommerce-tools' => __('AI Ecommerce Tools', 'kingy-ai-launch-intelligence'),
            'ai-hardware' => __('AI Hardware', 'kingy-ai-launch-intelligence'),
            'ai-image-tools' => __('AI Image Tools', 'kingy-ai-launch-intelligence'),
            'ai-infrastructure' => __('AI Infrastructure', 'kingy-ai-launch-intelligence'),
            'ai-local-models' => __('AI Local Models', 'kingy-ai-launch-intelligence'),
            'ai-marketing-tools' => __('AI Marketing Tools', 'kingy-ai-launch-intelligence'),
            'ai-models' => __('AI Models', 'kingy-ai-launch-intelligence'),
            'ai-music-tools' => __('AI Music Tools', 'kingy-ai-launch-intelligence'),
            'ai-open-weight-models' => __('AI Open-Weight Models', 'kingy-ai-launch-intelligence'),
            'open-weight-models' => __('AI Open-Weight Models', 'kingy-ai-launch-intelligence'),
            'ai-productivity-tools' => __('AI Productivity Tools', 'kingy-ai-launch-intelligence'),
            'ai-research-tools' => __('AI Research Tools', 'kingy-ai-launch-intelligence'),
            'ai-robotics' => __('AI Robotics', 'kingy-ai-launch-intelligence'),
            'ai-search-tools' => __('AI Search Tools', 'kingy-ai-launch-intelligence'),
            'ai-security-tools' => __('AI Security Tools', 'kingy-ai-launch-intelligence'),
            'ai-video-tools' => __('AI Video Tools', 'kingy-ai-launch-intelligence'),
            'ai-voice-audio-tools' => __('AI Voice/Audio Tools', 'kingy-ai-launch-intelligence'),
            'ai-writing-tools' => __('AI Writing Tools', 'kingy-ai-launch-intelligence'),
            'foundation-models' => __('Foundation Models', 'kingy-ai-launch-intelligence'),
            'open-source-ai' => __('Open-Source AI', 'kingy-ai-launch-intelligence'),
            'needs-verification' => __('Needs Verification', 'kingy-ai-launch-intelligence'),
        ),
        'kingy_audience' => array(
            'agencies' => __('Agencies', 'kingy-ai-launch-intelligence'),
            'creators' => __('Creators', 'kingy-ai-launch-intelligence'),
            'designers' => __('Designers', 'kingy-ai-launch-intelligence'),
            'developers' => __('Developers', 'kingy-ai-launch-intelligence'),
            'enterprises' => __('Enterprises', 'kingy-ai-launch-intelligence'),
            'founders' => __('Founders', 'kingy-ai-launch-intelligence'),
            'marketers' => __('Marketers', 'kingy-ai-launch-intelligence'),
            'operators' => __('Operators', 'kingy-ai-launch-intelligence'),
            'researchers' => __('Researchers', 'kingy-ai-launch-intelligence'),
            'sales-teams' => __('Sales Teams', 'kingy-ai-launch-intelligence'),
            'small-business-owners' => __('Small Business Owners', 'kingy-ai-launch-intelligence'),
            'students' => __('Students', 'kingy-ai-launch-intelligence'),
            'youtubers' => __('YouTubers', 'kingy-ai-launch-intelligence'),
        ),
        'kingy_tool_attribute' => array(
            'api-available' => __('API Available', 'kingy-ai-launch-intelligence'),
            'beginner-friendly' => __('Beginner-Friendly', 'kingy-ai-launch-intelligence'),
            'business-friendly' => __('Business-Friendly', 'kingy-ai-launch-intelligence'),
            'clear-use-case' => __('Clear Use Case', 'kingy-ai-launch-intelligence'),
            'creator-coverage-candidate' => __('Creator Coverage Candidate', 'kingy-ai-launch-intelligence'),
            'creator-friendly' => __('Creator-Friendly', 'kingy-ai-launch-intelligence'),
            'developer-first' => __('Developer-First', 'kingy-ai-launch-intelligence'),
            'developer-friendly' => __('Developer-Friendly', 'kingy-ai-launch-intelligence'),
            'enterprise-ready' => __('Enterprise-Ready', 'kingy-ai-launch-intelligence'),
            'founder-submitted' => __('Founder-Submitted', 'kingy-ai-launch-intelligence'),
            'free-plan' => __('Free Plan', 'kingy-ai-launch-intelligence'),
            'funding-announced' => __('Funding Announced', 'kingy-ai-launch-intelligence'),
            'github-traction' => __('GitHub Traction', 'kingy-ai-launch-intelligence'),
            'high-youtube-potential' => __('High YouTube Potential', 'kingy-ai-launch-intelligence'),
            'local-first' => __('Local-First', 'kingy-ai-launch-intelligence'),
            'no-code' => __('No-Code', 'kingy-ai-launch-intelligence'),
            'open-source' => __('Open Source', 'kingy-ai-launch-intelligence'),
            'open-weight' => __('Open Weight', 'kingy-ai-launch-intelligence'),
            'paid-only' => __('Paid Only', 'kingy-ai-launch-intelligence'),
            'product-hunt-traction' => __('Product Hunt Traction', 'kingy-ai-launch-intelligence'),
            'self-hosted' => __('Self-Hosted', 'kingy-ai-launch-intelligence'),
            'sponsor-candidate' => __('Creator Campaign Candidate', 'kingy-ai-launch-intelligence'),
            'strong-demo' => __('Strong Demo', 'kingy-ai-launch-intelligence'),
            'traction-signal' => __('Traction Signal', 'kingy-ai-launch-intelligence'),
            'video-demo-available' => __('Video Demo Available', 'kingy-ai-launch-intelligence'),
        ),
        'kingy_launch_type' => array(
            'founder-submitted' => __('Founder Submitted', 'kingy-ai-launch-intelligence'),
            'funding' => __('Funding', 'kingy-ai-launch-intelligence'),
            'major-update' => __('Major Update', 'kingy-ai-launch-intelligence'),
            'model-release' => __('Model Release', 'kingy-ai-launch-intelligence'),
            'new-product' => __('New Product', 'kingy-ai-launch-intelligence'),
            'open-source-release' => __('Open-Source Release', 'kingy-ai-launch-intelligence'),
        ),
    );

    $taxonomy = sanitize_key($taxonomy);
    return isset($labels[$taxonomy]) ? $labels[$taxonomy] : array();
}

function kingy_ali_normalize_public_filter_label($label) {
    $label = wp_strip_all_tags((string) $label);
    $label = html_entity_decode($label, ENT_QUOTES, get_bloginfo('charset'));
    $label = preg_replace('/\s+/', ' ', $label);
    return trim((string) $label);
}

function kingy_ali_public_filter_label_is_clean($label) {
    $label = trim((string) $label);
    if ($label === '') {
        return false;
    }

    if (strlen($label) > 48) {
        return false;
    }

    if (preg_match('/[.!?]\s*$/', $label)) {
        return false;
    }

    if (preg_match('/^(and|or|for|with|using|to)\s+/i', $label)) {
        return false;
    }

    return true;
}

function kingy_ali_render_yes_no_select($name, $label, $selected_value) {
    ?>
    <label>
        <span><?php echo esc_html($label); ?></span>
        <select name="<?php echo esc_attr($name); ?>">
            <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
            <option value="yes" <?php selected($selected_value, 'yes'); ?>><?php esc_html_e('Yes', 'kingy-ai-launch-intelligence'); ?></option>
            <option value="no" <?php selected($selected_value, 'no'); ?>><?php esc_html_e('No', 'kingy-ai-launch-intelligence'); ?></option>
        </select>
    </label>
    <?php
}

function kingy_ali_launch_signal_filter_options() {
    return array(
        '' => __('All signals', 'kingy-ai-launch-intelligence'),
        'founder-submitted' => __('Founder submitted', 'kingy-ai-launch-intelligence'),
        'funding-announced' => __('Funding announced', 'kingy-ai-launch-intelligence'),
        'product-hunt-traction' => __('Product Hunt traction', 'kingy-ai-launch-intelligence'),
        'github-traction' => __('GitHub traction', 'kingy-ai-launch-intelligence'),
        'high-youtube-potential' => __('YouTube potential', 'kingy-ai-launch-intelligence'),
        'beginner-friendly' => __('Beginner-friendly', 'kingy-ai-launch-intelligence'),
        'creator-friendly' => __('Creator-friendly', 'kingy-ai-launch-intelligence'),
        'developer-friendly' => __('Developer-friendly', 'kingy-ai-launch-intelligence'),
        'business-friendly' => __('Business-friendly', 'kingy-ai-launch-intelligence'),
        'video-demo-available' => __('Video demo available', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_render_launch_signal_filter_chips($filters) {
    $selected = isset($filters['attribute']) ? sanitize_title($filters['attribute']) : '';
    $base_url = kingy_ali_launch_filter_base_url();
    $base_args = kingy_ali_launch_filter_url_args($filters);
    $options = kingy_ali_launch_signal_filter_options();

    ob_start();
    echo '<nav class="kingy-ali-filter-chips" aria-label="' . esc_attr__('Launch signal filters', 'kingy-ai-launch-intelligence') . '">';
    foreach ($options as $slug => $label) {
        $args = $base_args;
        if ($slug) {
            $args['kali_attribute'] = $slug;
        } else {
            unset($args['kali_attribute']);
        }
        $url = $args ? add_query_arg($args, $base_url) : $base_url;
        $is_active = $selected === $slug || ($selected === '' && $slug === '');
        echo '<a class="kingy-ali-filter-chip' . ($is_active ? ' is-active' : '') . '" data-kingy-ali-track="clicked_filter" data-event-label="' . esc_attr($label) . '" data-event-surface="launch_signal_filter" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</nav>';

    return ob_get_clean();
}

function kingy_ali_launch_filter_url_args($filters) {
    $map = array(
        'q' => 'kali_q',
        'period' => 'kali_period',
        'category' => 'kali_category',
        'launch_type' => 'kali_launch_type',
        'audience' => 'kali_audience',
        'attribute' => 'kali_attribute',
        'free_plan' => 'kali_free_plan',
        'api_available' => 'kali_api_available',
        'open_source_or_open_weight' => 'kali_open_weight',
        'video_demo' => 'kali_video_demo',
        'youtube_potential' => 'kali_youtube_potential',
    );

    $args = array();
    foreach ($map as $filter_key => $query_key) {
        if (!isset($filters[$filter_key]) || $filters[$filter_key] === '') {
            continue;
        }
        $args[$query_key] = $filters[$filter_key];
    }

    return $args;
}

function kingy_ali_launch_filter_base_url() {
    global $wp;

    if (!empty($wp->request)) {
        return user_trailingslashit(home_url($wp->request));
    }

    return home_url('/ai-launches/');
}

function kingy_ali_render_launch_grid($query) {
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="kingy-ali-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo kingy_ali_render_launch_card(get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo kingy_ali_render_launch_empty_state();
    }

    return ob_get_clean();
}

function kingy_ali_render_launch_empty_state() {
    ob_start();
    ?>
    <div class="kingy-ali-empty">
        <h3><?php esc_html_e('No matching launch records yet.', 'kingy-ai-launch-intelligence'); ?></h3>
        <p><?php esc_html_e('That gap is useful signal for the Launch Intelligence roadmap. Submit a missing launch, request a visibility review, or reset to the full launch hub.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit missing launch from no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_no_results" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a missing launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get visibility score from no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_no_results" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse all launches from no results', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_no_results" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse all launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_launch_card($post_id) {
    $categories = get_the_terms($post_id, 'kingy_launch_category');
    $category = (!is_wp_error($categories) && !empty($categories)) ? $categories[0]->name : __('Uncategorized', 'kingy-ai-launch-intelligence');
    $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
    $summary = kingy_ali_public_profile_meta_text($post_id, 'what_launched', get_the_excerpt($post_id));
    $free_plan = kingy_ali_public_profile_meta_text($post_id, 'free_plan');
    $api_available = kingy_ali_public_profile_meta_text($post_id, 'api_available');
    $open_weight = kingy_ali_public_profile_meta_text($post_id, 'open_source_or_open_weight');
    $launch_score = kingy_ali_public_profile_meta_text($post_id, 'kingy_launch_score');
    $demo_score = kingy_ali_public_profile_meta_text($post_id, 'demo_quality_score');
    $youtube_score = kingy_ali_public_profile_meta_text($post_id, 'youtube_score');
    $verdict = kingy_ali_public_profile_meta_text($post_id, 'kingy_verdict');
    $tool_id = kingy_ali_public_profile_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
    if (!kingy_ali_related_post_is_public_index_ready($tool_id, 'kingy_ai_tool')) {
        $tool_id = 0;
    }
    $tool_url = kingy_ali_launch_card_tool_url($post_id, $tool_id);
    $source_link = kingy_ali_launch_card_source_link($post_id);
    $launch_date_label = kingy_ali_public_profile_date_label($launch_date);

    ob_start();
    ?>
    <article class="kingy-ali-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html($category); ?></span>
            <?php if ($launch_date_label) : ?>
                <time datetime="<?php echo esc_attr($launch_date); ?>"><?php echo esc_html($launch_date_label); ?></time>
            <?php endif; ?>
        </div>
        <h3><a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="launch_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <?php if ($summary) : ?>
            <p><?php echo esc_html(wp_trim_words($summary, 28)); ?></p>
        <?php endif; ?>
        <div class="kingy-ali-badges">
            <?php kingy_ali_render_fact_badge(__('Free', 'kingy-ai-launch-intelligence'), $free_plan); ?>
            <?php kingy_ali_render_fact_badge(__('API', 'kingy-ai-launch-intelligence'), $api_available); ?>
            <?php kingy_ali_render_fact_badge(__('Open', 'kingy-ai-launch-intelligence'), $open_weight); ?>
        </div>
        <?php echo kingy_ali_render_public_signal_badges($post_id); ?>
        <dl class="kingy-ali-score-list">
            <div><dt><?php esc_html_e('Kingy', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_format_score($launch_score)); ?></dd></div>
            <div><dt><?php esc_html_e('Demo', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_format_score($demo_score)); ?></dd></div>
            <div><dt><?php esc_html_e('YouTube', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_score_band($youtube_score)); ?></dd></div>
        </dl>
        <?php if ($verdict) : ?>
            <p class="kingy-ali-verdict"><?php echo esc_html(wp_trim_words($verdict, 24)); ?></p>
        <?php endif; ?>
        <div class="kingy-ali-card__actions">
            <a data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="launch_card" href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php esc_html_e('View launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="<?php echo esc_attr($tool_id ? 'clicked_tool' : 'clicked_category_path'); ?>" data-object-id="<?php echo esc_attr($tool_id); ?>" data-event-label="<?php esc_attr_e('View tool profile from launch card', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_card" href="<?php echo esc_url($tool_url); ?>"><?php esc_html_e('View tool profile', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($source_link) : ?>
                <a data-kingy-ali-track="clicked_source_link" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php echo esc_attr($source_link['label']); ?>" data-event-surface="launch_card" href="<?php echo esc_url($source_link['url']); ?>"<?php echo kingy_ali_source_link_target_attrs($source_link['url']); ?>><?php esc_html_e('Source', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a data-kingy-ali-track="clicked_correction_cta" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-label="<?php esc_attr_e('Suggest correction from launch card', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="launch_card" href="<?php echo esc_url(get_permalink($post_id) . '#kingy-ali-correction-' . $post_id); ?>"><?php esc_html_e('Suggest correction', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_launch_card_source_link($post_id) {
    if (!function_exists('kingy_ali_public_source_links')) {
        return array();
    }

    $source_links = kingy_ali_public_source_links($post_id);
    if (!is_array($source_links) || empty($source_links)) {
        return array();
    }

    foreach ($source_links as $source_link) {
        if (empty($source_link['url']) || empty($source_link['label'])) {
            continue;
        }

        $url = kingy_ali_public_url_value($source_link['url']);
        if ($url === '') {
            continue;
        }

        return array(
            'label' => sanitize_text_field($source_link['label']),
            'url' => $url,
        );
    }

    return array();
}

function kingy_ali_launch_card_tool_url($post_id, $tool_id = 0) {
    $tool_id = kingy_ali_public_profile_id($tool_id);
    if (kingy_ali_related_post_is_public_index_ready($tool_id, 'kingy_ai_tool')) {
        return get_permalink($tool_id);
    }

    return add_query_arg('kali_q', get_the_title($post_id), home_url('/ai-tools/'));
}

function kingy_ali_render_public_signal_badges($post_id) {
    $terms = get_the_terms($post_id, 'kingy_tool_attribute');
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    $slugs = wp_list_pluck($terms, 'slug');
    $labels = array(
        'creator-coverage-candidate' => __('Creator coverage', 'kingy-ai-launch-intelligence'),
        'product-hunt-traction' => __('Product Hunt traction', 'kingy-ai-launch-intelligence'),
        'strong-demo' => __('Strong demo', 'kingy-ai-launch-intelligence'),
        'clear-use-case' => __('Clear use case', 'kingy-ai-launch-intelligence'),
        'video-demo-available' => __('Video demo', 'kingy-ai-launch-intelligence'),
        'beginner-friendly' => __('Beginner-friendly', 'kingy-ai-launch-intelligence'),
        'creator-friendly' => __('Creator-friendly', 'kingy-ai-launch-intelligence'),
        'business-friendly' => __('Business-friendly', 'kingy-ai-launch-intelligence'),
        'developer-friendly' => __('Developer-friendly', 'kingy-ai-launch-intelligence'),
        'github-traction' => __('GitHub traction', 'kingy-ai-launch-intelligence'),
        'high-youtube-potential' => __('High YouTube potential', 'kingy-ai-launch-intelligence'),
        'traction-signal' => __('Traction signal', 'kingy-ai-launch-intelligence'),
        'founder-submitted' => __('Founder-submitted', 'kingy-ai-launch-intelligence'),
        'funding-announced' => __('Funding', 'kingy-ai-launch-intelligence'),
    );

    $badges = array();
    foreach ($labels as $slug => $label) {
        if (in_array($slug, $slugs, true)) {
            $badges[] = $label;
        }
    }

    $badges = array_slice($badges, 0, 4);
    if (!$badges) {
        return '';
    }

    ob_start();
    echo '<div class="kingy-ali-signal-badges">';
    foreach ($badges as $badge) {
        echo '<span class="kingy-ali-signal-badge">' . esc_html($badge) . '</span>';
    }
    echo '</div>';

    return ob_get_clean();
}

function kingy_ali_render_fact_badge($label, $value) {
    $value = kingy_ali_public_profile_text($value, 'unknown');
    $value = $value !== '' ? $value : 'unknown';
    echo '<span class="kingy-ali-badge kingy-ali-badge--' . esc_attr(sanitize_title($value)) . '">' . esc_html($label . ': ' . ucfirst($value)) . '</span>';
}
