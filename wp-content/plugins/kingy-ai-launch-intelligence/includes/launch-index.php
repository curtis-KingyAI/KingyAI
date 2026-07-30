<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Canonical published-launch index.
 *
 * Collection discovery uses the same persisted SEO eligibility state as the
 * singular page, sitemaps, and every other public promotion surface.
 */

add_filter('posts_clauses', 'kingy_ali_launch_index_order_clauses', 20, 2);
add_filter('rest_kingy_ai_launch_query', 'kingy_ali_rest_launch_query_args', 20, 2);
add_filter('rest_kingy_ai_launch_collection_params', 'kingy_ali_rest_launch_collection_params');
add_action('pre_get_posts', 'kingy_ali_launch_index_prepare_feed_query', 20);
add_action('init', 'kingy_ali_register_launch_feed');
add_action('init', 'kingy_ali_maybe_schedule_launch_feed_rewrite_flush', 100);
add_action('wp_head', 'kingy_ali_output_launch_feed_link');
add_action('template_redirect', 'kingy_ali_redirect_launch_collection_comments_feed', -40);

function kingy_ali_launch_index_defaults() {
    return array(
        'limit' => 12,
        'page' => 1,
        'sort' => 'newest',
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
        'related_tool_id' => 0,
        'related_company_id' => 0,
        'post__in' => array(),
        'fields' => '',
        'no_found_rows' => false,
    );
}

function kingy_ali_sanitize_launch_sort($sort) {
    $sort = sanitize_key((string) $sort);
    return in_array($sort, array('newest', 'recently_added', 'score', 'verification'), true) ? $sort : 'newest';
}

function kingy_ali_sanitize_launch_page($page) {
    return min(10000, max(1, absint($page)));
}

function kingy_ali_launch_archive_current_page($request_page = 1) {
    $request_page = kingy_ali_sanitize_launch_page($request_page);
    if (!is_tax('kingy_launch_category')) {
        return $request_page;
    }

    $core_page = absint(get_query_var('paged'));
    if (!$core_page) {
        $core_page = absint(get_query_var('page'));
    }

    return $core_page ? kingy_ali_sanitize_launch_page($core_page) : $request_page;
}

function kingy_ali_sanitize_launch_boolean($value) {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    if (!is_scalar($value)) {
        return false;
    }

    return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
}

/**
 * Build the bounded score index used by public filters and ordering.
 *
 * There are currently 250 published launch records. Reading their canonical
 * snapshots in one request-local, generation-keyed pass is intentionally
 * preferred to re-implementing the quality gate in SQL, where it could drift.
 */
function kingy_ali_launch_index_published_ids() {
    static $cache = array();
    $generation = function_exists('kingy_ali_launch_collection_cache_generation')
        ? kingy_ali_launch_collection_cache_generation()
        : 'request';
    if (isset($cache[$generation])) {
        return $cache[$generation];
    }

    $published = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'suppress_filters' => true,
        )
    );
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) $published->posts))));
    if (function_exists('kingy_ali_entity_seo_is_indexable')) {
        $ids = array_values(array_filter($ids, 'kingy_ali_entity_seo_is_indexable'));
    }
    if ($ids && function_exists('update_meta_cache')) {
        update_meta_cache('post', $ids);
    }
    if ($ids && function_exists('update_object_term_cache')) {
        update_object_term_cache($ids, 'kingy_ai_launch');
    }

    $cache[$generation] = $ids;
    return $ids;
}

function kingy_ali_launch_index_public_score_values($kind = 'kingy') {
    $kind = sanitize_key((string) $kind);
    if (!in_array($kind, array('kingy', 'demo', 'youtube'), true)) {
        return array();
    }

    static $cache = array();
    $generation = function_exists('kingy_ali_launch_collection_cache_generation')
        ? kingy_ali_launch_collection_cache_generation()
        : 'request';
    $cache_key = $generation . ':' . $kind;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    $values = array();
    foreach (kingy_ali_launch_index_published_ids() as $post_id) {
        $value = function_exists('kingy_ali_public_launch_score_value')
            ? kingy_ali_public_launch_score_value($post_id, $kind)
            : null;
        if ($value !== null) {
            $values[$post_id] = (float) $value;
        }
    }

    $cache[$cache_key] = $values;
    return $values;
}

function kingy_ali_launch_index_term_slugs($post_id, $taxonomy) {
    $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
    return is_wp_error($terms)
        ? array()
        : array_values(array_unique(array_filter(array_map('sanitize_title', (array) $terms))));
}

function kingy_ali_launch_index_attribute_ids($requested_slugs) {
    $requested_slugs = array_values(array_unique(array_filter(array_map('sanitize_title', (array) $requested_slugs))));
    if (!$requested_slugs) {
        return array();
    }

    $eligible_ids = array();
    foreach (kingy_ali_launch_index_published_ids() as $post_id) {
        $public_slugs = function_exists('kingy_ali_public_launch_attribute_slugs')
            ? kingy_ali_public_launch_attribute_slugs($post_id)
            : kingy_ali_launch_index_term_slugs($post_id, 'kingy_tool_attribute');
        if (array_intersect($requested_slugs, $public_slugs)) {
            $eligible_ids[] = $post_id;
        }
    }
    return $eligible_ids;
}

function kingy_ali_launch_index_creator_coverage_ids() {
    $attribute_slugs = function_exists('kingy_ali_creator_coverage_attribute_slugs')
        ? kingy_ali_creator_coverage_attribute_slugs()
        : array();
    $audience_slugs = function_exists('kingy_ali_creator_coverage_audience_slugs')
        ? kingy_ali_creator_coverage_audience_slugs()
        : array();
    $category_slugs = function_exists('kingy_ali_creator_coverage_category_slugs')
        ? kingy_ali_creator_coverage_category_slugs()
        : array();

    $eligible_ids = array();
    foreach (kingy_ali_launch_index_published_ids() as $post_id) {
        $public_attributes = function_exists('kingy_ali_public_launch_attribute_slugs')
            ? kingy_ali_public_launch_attribute_slugs($post_id)
            : kingy_ali_launch_index_term_slugs($post_id, 'kingy_tool_attribute');
        if (
            array_intersect($attribute_slugs, $public_attributes)
            || array_intersect($audience_slugs, kingy_ali_launch_index_term_slugs($post_id, 'kingy_audience'))
            || array_intersect($category_slugs, kingy_ali_launch_index_term_slugs($post_id, 'kingy_launch_category'))
        ) {
            $eligible_ids[] = $post_id;
        }
    }
    return $eligible_ids;
}

function kingy_ali_launch_index_restrict_post_ids(&$query_args, $allowed_ids) {
    $allowed_ids = array_values(array_unique(array_filter(array_map('absint', (array) $allowed_ids))));
    if (isset($query_args['post__in'])) {
        $current_ids = array_values(array_unique(array_filter(array_map('absint', (array) $query_args['post__in']))));
        $allowed_ids = array_values(array_intersect($current_ids, $allowed_ids));
    }

    $query_args['post__in'] = $allowed_ids ? $allowed_ids : array(0);
}

function kingy_ali_launch_index_query_args($args = array()) {
    $args = wp_parse_args($args, kingy_ali_launch_index_defaults());
    $limit = absint($args['limit']);
    $page = kingy_ali_sanitize_launch_page($args['page']);
    $sort = kingy_ali_sanitize_launch_sort($args['sort']);
    $period = sanitize_key((string) $args['period']);
    $video_demo = kingy_ali_sanitize_launch_boolean($args['video_demo']);
    $youtube_potential = kingy_ali_sanitize_launch_boolean($args['youtube_potential']);
    $youtube_worthy = kingy_ali_sanitize_launch_boolean($args['youtube_worthy']);
    $creator_coverage = kingy_ali_sanitize_launch_boolean($args['creator_coverage']);

    if ($period !== '' && !in_array($period, array('today', 'week'), true)) {
        return array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'publish',
            'post__in' => array(0),
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'paged' => $page,
            'ignore_sticky_posts' => true,
            'no_found_rows' => !empty($args['no_found_rows']),
            'kingy_ali_launch_index' => 1,
            'kingy_ali_launch_sort' => $sort,
        );
    }

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'paged' => $page,
        'ignore_sticky_posts' => true,
        'no_found_rows' => !empty($args['no_found_rows']),
        'kingy_ali_launch_index' => 1,
        'kingy_ali_launch_sort' => $sort,
        'kingy_ali_launch_live_args' => array(
            'page' => $page,
            'per_page' => $limit > 0 ? min(48, $limit) : 48,
            'sort' => $sort,
            'q' => is_scalar($args['q']) ? (string) $args['q'] : '',
            'period' => $period,
            'category' => is_scalar($args['category']) ? (string) $args['category'] : '',
            'launch_type' => is_scalar($args['launch_type']) ? (string) $args['launch_type'] : '',
            'audience' => is_scalar($args['audience']) ? (string) $args['audience'] : '',
            'attribute' => is_scalar($args['attribute']) ? (string) $args['attribute'] : '',
            'free_plan' => is_scalar($args['free_plan']) ? (string) $args['free_plan'] : '',
            'api_available' => is_scalar($args['api_available']) ? (string) $args['api_available'] : '',
            'open_source_or_open_weight' => is_scalar($args['open_source_or_open_weight']) ? (string) $args['open_source_or_open_weight'] : '',
            'video_demo' => $video_demo,
            'youtube_potential' => $youtube_potential,
            'youtube_worthy' => $youtube_worthy,
            'creator_coverage' => $creator_coverage,
            'related_tool_id' => absint($args['related_tool_id']),
            'related_company_id' => absint($args['related_company_id']),
        ),
    );
    if (function_exists('kingy_ali_apply_public_noindex_meta_constraint')) {
        kingy_ali_apply_public_noindex_meta_constraint($query_args);
    }

    if ($sort === 'score') {
        $query_args['kingy_ali_launch_public_score_values'] = kingy_ali_launch_index_public_score_values('kingy');
    }

    if (!empty($args['fields'])) {
        $query_args['fields'] = sanitize_key((string) $args['fields']);
    }

    if (!empty($args['q'])) {
        $matching_ids = kingy_ali_search_matching_launch_ids($args['q']);
        $query_args['post__in'] = $matching_ids ? $matching_ids : array(0);
        $query_args['kingy_ali_exact_title'] = kingy_ali_sanitize_public_search_query($args['q']);
    } elseif (!empty($args['post__in'])) {
        $query_args['post__in'] = array_values(array_unique(array_filter(array_map('absint', (array) $args['post__in']))));
        if (!$query_args['post__in']) {
            $query_args['post__in'] = array(0);
        }
    }

    $attribute_slug = sanitize_title((string) $args['attribute']);
    $tax_query = array('relation' => 'AND');
    foreach (array(
        'kingy_launch_category' => $args['category'],
        'kingy_launch_type' => $args['launch_type'],
        'kingy_audience' => $args['audience'],
    ) as $taxonomy => $slug) {
        $slug = sanitize_title((string) $slug);
        if ($slug !== '') {
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => kingy_ali_public_filter_slug_terms($taxonomy, $slug),
            );
        }
    }

    if ($attribute_slug !== '') {
        kingy_ali_launch_index_restrict_post_ids(
            $query_args,
            kingy_ali_launch_index_attribute_ids(kingy_ali_public_filter_slug_terms('kingy_tool_attribute', $attribute_slug))
        );
    }

    if ($creator_coverage) {
        kingy_ali_launch_index_restrict_post_ids($query_args, kingy_ali_launch_index_creator_coverage_ids());
    }

    if (count($tax_query) > 1) {
        $query_args['tax_query'] = $tax_query;
    }

    $meta_query = array('relation' => 'AND');
    if ($period === 'today') {
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('launch_date'),
            'value' => current_time('Y-m-d'),
            'compare' => '=',
        );
    } elseif ($period === 'week') {
        $today = current_time('Y-m-d');
        $week_start = date_i18n('Y-m-d', strtotime('-6 days', current_time('timestamp')));
        $meta_query[] = array(
            'key' => kingy_ali_meta_key('launch_date'),
            'value' => array($week_start, $today),
            'compare' => 'BETWEEN',
            'type' => 'DATE',
        );
    }

    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        $value = sanitize_key((string) $args[$key]);
        if (in_array($value, array('yes', 'no'), true)) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($key),
                'value' => $value,
                'compare' => '=',
            );
        }
    }

    if ($video_demo) {
        $meta_query[] = array(
            'relation' => 'OR',
            array('key' => kingy_ali_meta_key('demo_url'), 'value' => '', 'compare' => '!='),
            array('key' => kingy_ali_meta_key('youtube_url'), 'value' => '', 'compare' => '!='),
        );
    }

    if ($youtube_potential || $youtube_worthy) {
        $youtube_values = kingy_ali_launch_index_public_score_values('youtube');
        $eligible_ids = array_keys(array_filter($youtube_values, function ($value) {
            return is_numeric($value) && (float) $value >= 7;
        }));
        kingy_ali_launch_index_restrict_post_ids($query_args, $eligible_ids);
    }

    foreach (array('related_tool_id', 'related_company_id') as $relationship_key) {
        $relationship_id = absint($args[$relationship_key]);
        if ($relationship_id) {
            $meta_query[] = array(
                'key' => kingy_ali_meta_key($relationship_key),
                'value' => $relationship_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            );
        }
    }

    if (count($meta_query) > 1) {
        foreach (array_slice($meta_query, 1) as $constraint) {
            kingy_ali_append_meta_query_constraint($query_args, $constraint);
        }
    }

    return apply_filters('kingy_ali_launch_index_query_args', $query_args, $args);
}

function kingy_ali_launch_index_run($args = array()) {
    return new WP_Query(kingy_ali_launch_index_query_args($args));
}

function kingy_ali_launch_index_meta_subquery($meta_key, $posts_alias) {
    global $wpdb;

    return $wpdb->prepare(
        "(SELECT MAX(kali_pm.meta_value) FROM {$wpdb->postmeta} kali_pm WHERE kali_pm.post_id = {$posts_alias}.ID AND kali_pm.meta_key = %s)",
        kingy_ali_meta_key($meta_key)
    );
}

function kingy_ali_launch_index_order_clauses($clauses, $query) {
    if (!is_object($query) || !$query->get('kingy_ali_launch_index')) {
        return $clauses;
    }

    global $wpdb;
    $posts_alias = $wpdb->posts;
    $launch_date = kingy_ali_launch_index_meta_subquery('launch_date', $posts_alias);
    $post_date_fallback = "DATE({$posts_alias}.post_date)";
    $newest_order = "COALESCE(NULLIF({$launch_date}, ''), {$post_date_fallback}) DESC, {$posts_alias}.ID DESC";
    $recently_added_order = "COALESCE(NULLIF({$posts_alias}.post_date_gmt, '0000-00-00 00:00:00'), {$posts_alias}.post_date) DESC, {$posts_alias}.ID DESC";
    $sort = kingy_ali_sanitize_launch_sort($query->get('kingy_ali_launch_sort'));

    if ($sort === 'recently_added') {
        $clauses['orderby'] = $recently_added_order;
    } elseif ($sort === 'score') {
        $score_values = $query->get('kingy_ali_launch_public_score_values');
        $score_cases = array();
        foreach ((array) $score_values as $post_id => $value) {
            $post_id = absint($post_id);
            if (!$post_id || !is_numeric($value) || (float) $value < 0 || (float) $value > 10) {
                continue;
            }
            $score_cases[] = 'WHEN ' . $post_id . ' THEN ' . number_format((float) $value, 2, '.', '');
        }
        $score_expression = $score_cases
            ? "CASE {$posts_alias}.ID " . implode(' ', $score_cases) . ' ELSE NULL END'
            : 'NULL';
        $clauses['orderby'] = "CASE WHEN {$score_expression} IS NULL THEN 1 ELSE 0 END ASC, {$score_expression} DESC, {$newest_order}";
    } elseif ($sort === 'verification') {
        $status = kingy_ali_launch_index_meta_subquery('verification_status', $posts_alias);
        $clauses['orderby'] = "CASE LOWER(REPLACE(COALESCE({$status}, ''), '-', '_')) WHEN 'verified' THEN 0 WHEN 'partially_verified' THEN 1 WHEN 'founder_submitted' THEN 2 WHEN 'outdated' THEN 4 ELSE 3 END ASC, {$newest_order}";
    } else {
        $clauses['orderby'] = $newest_order;
    }

    $exact_title = trim((string) $query->get('kingy_ali_exact_title'));
    if ($exact_title !== '') {
        $exact_sql = $wpdb->prepare("CASE WHEN {$posts_alias}.post_title = %s THEN 0 ELSE 1 END ASC", $exact_title);
        $clauses['orderby'] = $exact_sql . ', ' . $clauses['orderby'];
    }

    return $clauses;
}

function kingy_ali_rest_launch_collection_params($params) {
    $params['sort'] = array(
        'description' => __('Sort launches by launch date, recently added date, public score, or verification.', 'kingy-ai-launch-intelligence'),
        'type' => 'string',
        'enum' => array('newest', 'recently_added', 'score', 'verification'),
        'default' => 'newest',
    );
    foreach (array('period', 'category', 'launch_type', 'audience', 'attribute') as $key) {
        $params[$key] = array('type' => 'string', 'required' => false);
    }
    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        $params[$key] = array(
            'type' => 'string',
            'enum' => array('yes', 'no'),
            'required' => false,
        );
    }
    foreach (array('video_demo', 'youtube_potential', 'youtube_worthy', 'creator_coverage') as $key) {
        $params[$key] = array('type' => 'boolean', 'required' => false);
    }
    foreach (array('related_tool_id', 'related_company_id') as $key) {
        $params[$key] = array(
            'type' => 'integer',
            'minimum' => 1,
            'required' => false,
        );
    }
    return $params;
}

function kingy_ali_rest_launch_query_args($args, $request) {
    $value = function ($key, $default = '') use ($request) {
        return is_object($request) && isset($request[$key]) && is_scalar($request[$key]) ? $request[$key] : $default;
    };
    $canonical = kingy_ali_launch_index_query_args(
        array(
            'limit' => absint($value('per_page', isset($args['posts_per_page']) ? $args['posts_per_page'] : 10)),
            'page' => kingy_ali_sanitize_launch_page($value('page', 1)),
            'sort' => kingy_ali_sanitize_launch_sort($value('sort', 'newest')),
            'q' => $value('search'),
            'period' => $value('period'),
            'category' => $value('category'),
            'launch_type' => $value('launch_type'),
            'audience' => $value('audience'),
            'attribute' => $value('attribute'),
            'free_plan' => $value('free_plan'),
            'api_available' => $value('api_available'),
            'open_source_or_open_weight' => $value('open_source_or_open_weight'),
            'video_demo' => kingy_ali_sanitize_launch_boolean($value('video_demo', false)),
            'youtube_potential' => kingy_ali_sanitize_launch_boolean($value('youtube_potential', false)),
            'youtube_worthy' => kingy_ali_sanitize_launch_boolean($value('youtube_worthy', false)),
            'creator_coverage' => kingy_ali_sanitize_launch_boolean($value('creator_coverage', false)),
            'related_tool_id' => absint($value('related_tool_id', 0)),
            'related_company_id' => absint($value('related_company_id', 0)),
        )
    );

    unset($args['post__in'], $args['tax_query'], $args['meta_query'], $args['kingy_ali_launch_public_score_values']);
    foreach (array('posts_per_page', 'paged', 'post__in', 'tax_query', 'meta_query', 'kingy_ali_launch_index', 'kingy_ali_launch_sort', 'kingy_ali_launch_public_score_values', 'kingy_ali_exact_title') as $key) {
        if (isset($canonical[$key])) {
            $args[$key] = $canonical[$key];
        }
    }
    // Canonical cross-record search is represented by post__in. Leaving core's
    // native `s` clause would incorrectly discard tool/company-derived matches.
    unset($args['meta_key'], $args['s']);
    return $args;
}

function kingy_ali_launch_index_prepare_feed_query($query) {
    if (is_admin() || !is_object($query) || !$query->is_main_query() || !$query->is_feed()) {
        return;
    }

    $post_type = $query->get('post_type');
    $is_launch_feed = $post_type === 'kingy_ai_launch'
        || (is_array($post_type) && in_array('kingy_ai_launch', $post_type, true));
    if (!$is_launch_feed) {
        return;
    }

    $query->set('post_type', 'kingy_ai_launch');
    $query->set('post_status', 'publish');
    $query->set('kingy_ali_launch_index', 1);
    // RSS pubDate is the record publication time. Keep the SQL order aligned
    // with that field so readers do not reorder the response unpredictably.
    $query->set('kingy_ali_launch_sort', 'recently_added');
    $eligible_ids = kingy_ali_launch_index_published_ids();
    $requested_ids = array_values(array_unique(array_filter(array_map('absint', (array) $query->get('post__in')))));
    if ($requested_ids) {
        $eligible_ids = array_values(array_intersect($requested_ids, $eligible_ids));
    }
    $query->set('post__in', $eligible_ids ? $eligible_ids : array(0));
    $query->set('ignore_sticky_posts', true);
}

function kingy_ali_register_launch_feed() {
    add_feed('kingy-ai-launches', 'kingy_ali_render_launch_feed');
}

function kingy_ali_launch_feed_rewrite_schema_version() {
    return '2026-07-16-v1';
}

function kingy_ali_maybe_schedule_launch_feed_rewrite_flush() {
    $version = kingy_ali_launch_feed_rewrite_schema_version();
    if (get_option('kingy_ali_launch_feed_rewrite_schema', '') === $version) {
        return;
    }
    update_option('kingy_ali_launch_feed_rewrite_schema', $version, false);
    update_option('kingy_ali_flush_rewrite_rules_deferred', '1', false);
}

function kingy_ali_launch_feed_url() {
    return home_url('/feed/kingy-ai-launches/');
}

function kingy_ali_launch_collection_comments_feed_paths() {
    return array(
        'ai-launches/feed',
        'ai-launches/today/feed',
        'ai-launches/this-week/feed',
        'ai-launches/ai-agents/feed',
        'ai-launches/ai-video-tools/feed',
        'ai-launches/ai-coding-tools/feed',
        'ai-launches/ai-image-tools/feed',
        'ai-launches/open-weight-models/feed',
        'ai-launches/ai-search-research-tools/feed',
        'ai-launches/ai-app-builders/feed',
        'ai-launches/youtube-worthy-ai-tools/feed',
        'ai-launches/founder-submitted-ai-tools/feed',
        'ai-launches/funding-announcements/feed',
        'ai-launches/creator-coverage-ai-launches/feed',
        'ai-launches/launches-of-the-week/feed',
    );
}

function kingy_ali_redirect_launch_collection_comments_feed() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) && is_scalar($_SERVER['REQUEST_URI'])
        ? wp_unslash($_SERVER['REQUEST_URI'])
        : '';
    $request_path = trim((string) wp_parse_url((string) $request_uri, PHP_URL_PATH), '/');
    if (!in_array($request_path, kingy_ali_launch_collection_comments_feed_paths(), true)) {
        return;
    }

    wp_safe_redirect(kingy_ali_launch_feed_url(), 301, 'Kingy AI Launch Feed');
    exit;
}

function kingy_ali_output_launch_feed_link() {
    $is_launch_collection = function_exists('kingy_ali_current_launch_collection_meta')
        && kingy_ali_current_launch_collection_meta();
    $is_launches_of_week = function_exists('kingy_ali_current_request_path')
        && kingy_ali_current_request_path() === 'ai-launches/launches-of-the-week';
    if (!$is_launch_collection && !$is_launches_of_week) {
        return;
    }

    echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__('Kingy AI Launch Intelligence feed', 'kingy-ai-launch-intelligence') . '" href="' . esc_url(kingy_ali_launch_feed_url()) . '">' . "\n";
}

function kingy_ali_feed_launch_query($limit = 20) {
    return kingy_ali_launch_index_run(
        array(
            'limit' => max(1, min(100, absint($limit))),
            'sort' => 'recently_added',
            'no_found_rows' => false,
        )
    );
}

function kingy_ali_feed_launch_item($post_id) {
    $post_id = absint($post_id);
    $trust = kingy_ali_launch_trust_snapshot($post_id);
    $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
    $timestamp = (int) get_post_time('U', true, $post_id);
    if (!$timestamp) {
        $timestamp = (int) get_post_modified_time('U', true, $post_id);
    }
    return array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'description' => kingy_ali_public_profile_meta_text($post_id, 'what_launched', get_the_excerpt($post_id)),
        'timestamp' => $timestamp,
        'launch_date' => $launch_date,
        'trust' => $trust,
    );
}

function kingy_ali_launch_feed_last_build_timestamp($items) {
    if (function_exists('kingy_ali_launch_freshness_stored_mutation')) {
        $mutation = kingy_ali_launch_freshness_stored_mutation();
        if (!empty($mutation['timestamp'])) {
            return (int) $mutation['timestamp'];
        }
    }

    $modified = array();
    foreach ((array) $items as $item) {
        if (is_array($item) && !empty($item['id']) && function_exists('get_post_modified_time')) {
            $timestamp = (int) get_post_modified_time('U', true, absint($item['id']));
            if ($timestamp > 0) {
                $modified[] = $timestamp;
            }
        }
    }
    if ($modified) {
        return max($modified);
    }

    if (function_exists('get_lastpostmodified')) {
        $last_modified = get_lastpostmodified('GMT', 'kingy_ai_launch');
        $timestamp = $last_modified ? strtotime($last_modified . ' UTC') : 0;
        if ($timestamp > 0) {
            return $timestamp;
        }
    }
    return null;
}

function kingy_ali_render_launch_feed() {
    $query = kingy_ali_feed_launch_query((int) get_option('posts_per_rss', 20));
    $items = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if ($post_id) {
            $items[] = kingy_ali_feed_launch_item($post_id);
        }
    }
    $last_build = kingy_ali_launch_feed_last_build_timestamp($items);
    $data_generation = function_exists('kingy_ali_launch_data_generation')
        ? kingy_ali_launch_data_generation()
        : '';
    if (!function_exists('kingy_ali_launch_data_generation_is_valid') || !kingy_ali_launch_data_generation_is_valid($data_generation)) {
        status_header(503);
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        echo '<?xml version="1.0" encoding="UTF-8"?><error>Launch generation unavailable.</error>';
        return;
    }

    status_header(200);
    header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset', 'UTF-8'));
    echo '<?xml version="1.0" encoding="' . esc_attr(get_option('blog_charset', 'UTF-8')) . '"?>' . "\n";
    ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:kingy="https://kingy.ai/ns/launch-intelligence/1.0">
<channel>
    <title><?php echo esc_html(get_bloginfo('name') . ' — AI Launch Intelligence'); ?></title>
    <link><?php echo esc_url(home_url('/ai-launches/')); ?></link>
    <atom:link href="<?php echo esc_url(kingy_ali_launch_feed_url()); ?>" rel="self" type="application/rss+xml" />
    <description><?php esc_html_e('Source-backed AI launch records from Kingy AI.', 'kingy-ai-launch-intelligence'); ?></description>
    <kingy:dataGeneration><?php echo esc_html($data_generation); ?></kingy:dataGeneration>
    <?php if ($last_build) : ?><lastBuildDate><?php echo esc_html(gmdate(DATE_RSS, $last_build)); ?></lastBuildDate><?php endif; ?>
    <?php foreach ($items as $item) : ?>
    <item>
        <title><?php echo esc_html($item['title']); ?></title>
        <link><?php echo esc_url($item['url']); ?></link>
        <guid isPermaLink="true"><?php echo esc_url($item['url']); ?></guid>
        <pubDate><?php echo esc_html(gmdate(DATE_RSS, $item['timestamp'])); ?></pubDate>
        <?php if (!empty($item['launch_date'])) : ?><kingy:launchDate><?php echo esc_html($item['launch_date']); ?></kingy:launchDate><?php endif; ?>
        <description><?php echo esc_html(wp_trim_words($item['description'], 55)); ?></description>
        <kingy:verification status="<?php echo esc_attr($item['trust']['status']); ?>"><?php echo esc_html($item['trust']['label']); ?></kingy:verification>
        <?php if ($item['trust']['score']['kingy']['value'] !== null) : ?><kingy:score><?php echo esc_html((string) $item['trust']['score']['kingy']['value']); ?></kingy:score><?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
    <?php
}
