<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rocket-safe live discovery transport.
 *
 * The route is POST-only because Rocket's public GET layer injects a reusable
 * 30-day shared TTL. It exposes published launch intelligence only, never
 * accepts mutation authority, and reuses the canonical launch index and card
 * renderer instead of maintaining a second search implementation.
 */

add_action('rest_api_init', 'kingy_ali_register_live_launch_index_route');
add_filter('kingy_ali_launch_filter_base_url', 'kingy_ali_live_launch_filter_base_url');

function kingy_ali_register_live_launch_index_route() {
    register_rest_route(
        'kingy-ali/v1',
        '/launch-index-live',
        array(
            'methods' => 'POST',
            'callback' => 'kingy_ali_live_launch_index_response',
            'permission_callback' => 'kingy_ali_live_launch_index_permission',
        )
    );
}

function kingy_ali_live_launch_index_permission($request) {
    $method = is_object($request) && method_exists($request, 'get_method')
        ? strtoupper((string) $request->get_method())
        : '';
    if ($method !== 'POST') {
        return new WP_Error(
            'kingy_ali_live_method_not_allowed',
            __('The live launch index accepts POST requests only.', 'kingy-ai-launch-intelligence'),
            array('status' => 405)
        );
    }

    $origin = is_object($request) && method_exists($request, 'get_header')
        ? trim((string) $request->get_header('origin'))
        : '';
    if ($origin === '') {
        return true;
    }

    $home = wp_parse_url(home_url('/'));
    $source = wp_parse_url($origin);
    $same_scheme = !empty($home['scheme']) && !empty($source['scheme']) && strtolower($home['scheme']) === strtolower($source['scheme']);
    $same_host = !empty($home['host']) && !empty($source['host']) && strtolower($home['host']) === strtolower($source['host']);
    $home_port = isset($home['port']) ? (int) $home['port'] : 0;
    $source_port = isset($source['port']) ? (int) $source['port'] : 0;
    if (!$same_scheme || !$same_host || $home_port !== $source_port) {
        return new WP_Error(
            'kingy_ali_live_origin_forbidden',
            __('Cross-origin live launch requests are not allowed.', 'kingy-ai-launch-intelligence'),
            array('status' => 403)
        );
    }

    return kingy_ali_live_launch_rate_limit($request);
}

function kingy_ali_live_launch_allowed_keys() {
    return array(
        'page',
        'per_page',
        'sort',
        'q',
        'period',
        'category',
        'launch_type',
        'audience',
        'attribute',
        'free_plan',
        'api_available',
        'open_source_or_open_weight',
        'video_demo',
        'youtube_potential',
        'youtube_worthy',
        'creator_coverage',
        'related_tool_id',
        'related_company_id',
        'base_path',
    );
}

function kingy_ali_live_launch_request_error($code, $message, $status = 400) {
    return new WP_Error(
        sanitize_key((string) $code),
        sanitize_text_field((string) $message),
        array('status' => absint($status))
    );
}

function kingy_ali_live_launch_rate_limit($request) {
    unset($request);
    $address = isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR'])
        ? trim((string) $_SERVER['REMOTE_ADDR'])
        : '';
    if ($address === '' || filter_var($address, FILTER_VALIDATE_IP) === false) {
        // WP-CLI and trusted internal test calls have no client address.
        return true;
    }

    $window = 60;
    $limit = (int) apply_filters('kingy_ali_live_launch_rate_limit', 90);
    $limit = min(300, max(10, $limit));
    $salt = function_exists('wp_salt') ? wp_salt('nonce') : (defined('AUTH_SALT') ? AUTH_SALT : __FILE__);
    $key = 'request_' . substr(hash_hmac('sha256', $address, (string) $salt), 0, 40);
    $count = 0;

    if (function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache()) {
        if (wp_cache_add($key, 1, 'kingy_ali_live_rate', $window)) {
            return true;
        }
        $count = wp_cache_incr($key, 1, 'kingy_ali_live_rate');
        if ($count === false) {
            wp_cache_set($key, 2, 'kingy_ali_live_rate', $window);
            $count = 2;
        }
    } else {
        $state = get_transient('kingy_ali_live_' . $key);
        $count = is_numeric($state) ? (int) $state + 1 : 1;
        set_transient('kingy_ali_live_' . $key, $count, $window);
    }

    if ($count > $limit) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_rate_limited',
            __('Too many live launch requests. Please retry shortly.', 'kingy-ai-launch-intelligence'),
            429
        );
    }
    return true;
}

function kingy_ali_live_launch_sanitize_path($path) {
    if (!is_scalar($path)) {
        return '/ai-launches/';
    }
    $path = wp_parse_url((string) $path, PHP_URL_PATH);
    if (!is_string($path) || !preg_match('#^/(?:ai-launches|ai-tools|ai-companies)(?:/[a-z0-9-]+)*/?$#', $path)) {
        return '/ai-launches/';
    }
    return user_trailingslashit($path);
}

function kingy_ali_live_launch_sanitize_request($request) {
    $content_type = is_object($request) && method_exists($request, 'get_header')
        ? strtolower((string) $request->get_header('content-type'))
        : '';
    if (strpos($content_type, 'application/json') !== 0) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_json_required',
            __('Use an application/json request body.', 'kingy-ai-launch-intelligence'),
            415
        );
    }

    $body = is_object($request) && method_exists($request, 'get_body') ? (string) $request->get_body() : '';
    if (strlen($body) > 8192) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_request_too_large',
            __('The live launch request is too large.', 'kingy-ai-launch-intelligence'),
            413
        );
    }

    $params = is_object($request) && method_exists($request, 'get_json_params')
        ? $request->get_json_params()
        : null;
    if (!is_array($params)) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_invalid_json',
            __('The live launch request must be a JSON object.', 'kingy-ai-launch-intelligence')
        );
    }

    $unknown = array_diff(array_keys($params), kingy_ali_live_launch_allowed_keys());
    if ($unknown) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_unknown_parameter',
            __('The live launch request contains an unsupported parameter.', 'kingy-ai-launch-intelligence')
        );
    }
    foreach ($params as $value) {
        if (is_array($value) || is_object($value)) {
            return kingy_ali_live_launch_request_error(
                'kingy_ali_live_nested_parameter',
                __('Nested live launch parameters are not allowed.', 'kingy-ai-launch-intelligence')
            );
        }
    }

    $text = function ($key, $maximum = 100) use ($params) {
        $value = isset($params[$key]) && is_scalar($params[$key]) ? trim((string) $params[$key]) : '';
        if (strlen($value) > $maximum) {
            return null;
        }
        return sanitize_text_field($value);
    };
    $text_limits = array(
        'q' => 200,
        'period' => 20,
        'category' => 100,
        'launch_type' => 100,
        'audience' => 100,
        'attribute' => 100,
        'free_plan' => 10,
        'api_available' => 10,
        'open_source_or_open_weight' => 10,
    );
    $text_values = array();
    foreach ($text_limits as $key => $maximum) {
        $text_values[$key] = $text($key, $maximum);
        if ($text_values[$key] === null) {
            return kingy_ali_live_launch_request_error(
                'kingy_ali_live_parameter_too_long',
                __('A live launch filter exceeds its allowed length.', 'kingy-ai-launch-intelligence')
            );
        }
    }
    $query = $text_values['q'];
    if ($query === null) {
        return kingy_ali_live_launch_request_error(
            'kingy_ali_live_search_too_long',
            __('The live launch search is too long.', 'kingy-ai-launch-intelligence')
        );
    }

    $sanitized = array(
        'page' => kingy_ali_sanitize_launch_page(isset($params['page']) ? $params['page'] : 1),
        'per_page' => min(48, max(1, absint(isset($params['per_page']) ? $params['per_page'] : 18))),
        'sort' => kingy_ali_sanitize_launch_sort(isset($params['sort']) ? $params['sort'] : 'newest'),
        'q' => $query,
        'period' => sanitize_key((string) $text_values['period']),
        'category' => sanitize_title((string) $text_values['category']),
        'launch_type' => sanitize_title((string) $text_values['launch_type']),
        'audience' => sanitize_title((string) $text_values['audience']),
        'attribute' => sanitize_title((string) $text_values['attribute']),
        'free_plan' => sanitize_key((string) $text_values['free_plan']),
        'api_available' => sanitize_key((string) $text_values['api_available']),
        'open_source_or_open_weight' => sanitize_key((string) $text_values['open_source_or_open_weight']),
        'video_demo' => kingy_ali_sanitize_launch_boolean(isset($params['video_demo']) ? $params['video_demo'] : false),
        'youtube_potential' => kingy_ali_sanitize_launch_boolean(isset($params['youtube_potential']) ? $params['youtube_potential'] : false),
        'youtube_worthy' => kingy_ali_sanitize_launch_boolean(isset($params['youtube_worthy']) ? $params['youtube_worthy'] : false),
        'creator_coverage' => kingy_ali_sanitize_launch_boolean(isset($params['creator_coverage']) ? $params['creator_coverage'] : false),
        'related_tool_id' => absint(isset($params['related_tool_id']) ? $params['related_tool_id'] : 0),
        'related_company_id' => absint(isset($params['related_company_id']) ? $params['related_company_id'] : 0),
        'base_path' => kingy_ali_live_launch_sanitize_path(isset($params['base_path']) ? $params['base_path'] : '/ai-launches/'),
    );

    if (!in_array($sanitized['period'], array('', 'today', 'week'), true)) {
        return kingy_ali_live_launch_request_error('kingy_ali_live_invalid_period', __('Invalid launch period.', 'kingy-ai-launch-intelligence'));
    }
    foreach (array('free_plan', 'api_available', 'open_source_or_open_weight') as $key) {
        if (!in_array($sanitized[$key], array('', 'yes', 'no'), true)) {
            return kingy_ali_live_launch_request_error('kingy_ali_live_invalid_filter', __('Invalid yes/no launch filter.', 'kingy-ai-launch-intelligence'));
        }
    }

    return $sanitized;
}

function kingy_ali_live_launch_filter_base_url($url) {
    if (empty($GLOBALS['kingy_ali_live_launch_base_path'])) {
        return $url;
    }
    return home_url($GLOBALS['kingy_ali_live_launch_base_path']);
}

function kingy_ali_live_launch_item($post_id) {
    $post_id = absint($post_id);
    $categories = wp_get_object_terms($post_id, 'kingy_launch_category', array('fields' => 'names'));
    if (is_wp_error($categories)) {
        $categories = array();
    }
    return array(
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'summary' => kingy_ali_public_profile_meta_text($post_id, 'what_launched', get_the_excerpt($post_id)),
        'launch_date' => kingy_ali_public_profile_meta_text($post_id, 'launch_date'),
        'categories' => array_values(array_map('sanitize_text_field', (array) $categories)),
        'trust' => kingy_ali_launch_trust_snapshot($post_id),
    );
}

function kingy_ali_live_launch_index_response($request) {
    $args = kingy_ali_live_launch_sanitize_request($request);
    if (is_wp_error($args)) {
        return $args;
    }

    $query = kingy_ali_launch_index_run(
        array(
            'limit' => $args['per_page'],
            'page' => $args['page'],
            'sort' => $args['sort'],
            'q' => $args['q'],
            'period' => $args['period'],
            'category' => $args['category'],
            'launch_type' => $args['launch_type'],
            'audience' => $args['audience'],
            'attribute' => $args['attribute'],
            'free_plan' => $args['free_plan'],
            'api_available' => $args['api_available'],
            'open_source_or_open_weight' => $args['open_source_or_open_weight'],
            'video_demo' => $args['video_demo'],
            'youtube_potential' => $args['youtube_potential'],
            'youtube_worthy' => $args['youtube_worthy'],
            'creator_coverage' => $args['creator_coverage'],
            'related_tool_id' => $args['related_tool_id'],
            'related_company_id' => $args['related_company_id'],
            'no_found_rows' => false,
        )
    );

    $items = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if ($post_id) {
            $items[] = kingy_ali_live_launch_item($post_id);
        }
    }

    $filters = $args;
    unset($filters['per_page'], $filters['base_path']);
    $GLOBALS['kingy_ali_live_launch_base_path'] = $args['base_path'];
    try {
        $html = kingy_ali_render_launch_collection_contents($query, $filters);
    } finally {
        unset($GLOBALS['kingy_ali_live_launch_base_path']);
    }

    $freshness = function_exists('kingy_ali_launch_freshness_snapshot')
        ? kingy_ali_launch_freshness_snapshot()
        : array();
    $generation = function_exists('kingy_ali_launch_data_generation')
        ? kingy_ali_launch_data_generation()
        : '';
    if (!function_exists('kingy_ali_launch_data_generation_is_valid') || !kingy_ali_launch_data_generation_is_valid($generation)) {
        return new WP_Error(
            'kingy_ali_generation_unavailable',
            __('The live launch index generation is unavailable.', 'kingy-ai-launch-intelligence'),
            array('status' => 503)
        );
    }
    $payload = array(
        'schema_version' => 1,
        'data_generation' => $generation,
        'generated_at' => gmdate('c'),
        'last_mutation_gmt' => isset($freshness['last_mutation_gmt']) ? $freshness['last_mutation_gmt'] : '',
        'latest_launch_date' => isset($freshness['latest_launch_date']) ? $freshness['latest_launch_date'] : '',
        'coverage_lag_days' => isset($freshness['coverage_lag_days']) ? $freshness['coverage_lag_days'] : null,
        'total_count' => count(kingy_ali_launch_index_published_ids()),
        'result_count' => isset($query->found_posts) ? absint($query->found_posts) : count($items),
        'page_count' => count($items),
        'page' => $args['page'],
        'per_page' => $args['per_page'],
        'total_pages' => isset($query->max_num_pages) ? absint($query->max_num_pages) : 0,
        'sort' => $args['sort'],
        'items' => $items,
        'html' => $html,
    );

    $response = new WP_REST_Response($payload, 200);
    $headers = array(
        'Cache-Control' => 'private, no-store, no-cache, max-age=0, must-revalidate',
        'CDN-Cache-Control' => 'no-store',
        'Cloudflare-CDN-Cache-Control' => 'no-store',
        'Surrogate-Control' => 'no-store',
        'Pragma' => 'no-cache',
        'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT',
        'X-Kingy-Launch-Cache-Policy' => 'live-post-no-store',
        'X-Kingy-Launch-Generation' => $generation,
        'X-Content-Type-Options' => 'nosniff',
        'X-Robots-Tag' => 'noindex, nofollow',
    );
    foreach ($headers as $name => $value) {
        $response->header($name, $value);
    }

    return $response;
}
