<?php
/**
 * Hardens WordPress collaborative sync endpoints.
 *
 * The Gutenberg/WordPress collaboration server validates required route args
 * before it reaches its own permission callback. This guard makes anonymous
 * wp-sync requests fail during REST authentication instead.
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('rest_authentication_errors', 'kingy_ali_guard_wp_sync_rest_namespace', 20);

function kingy_ali_guard_wp_sync_rest_namespace($result) {
    if (!empty($result)) {
        return $result;
    }

    if (!kingy_ali_is_wp_sync_rest_request()) {
        return $result;
    }

    if (current_user_can('edit_posts')) {
        return $result;
    }

    return new WP_Error(
        'kingy_ali_wp_sync_forbidden',
        __('You do not have permission to access WordPress sync endpoints.', 'kingy-ai-launch-intelligence'),
        array('status' => rest_authorization_required_code())
    );
}

function kingy_ali_is_wp_sync_rest_request() {
    $raw_rest_route = isset($_GET['rest_route']) ? wp_unslash($_GET['rest_route']) : '';
    if (is_string($raw_rest_route) && strpos(ltrim($raw_rest_route, '/'), 'wp-sync/v1') === 0) {
        return true;
    }

    $raw_request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    if (!is_string($raw_request_uri) || $raw_request_uri === '') {
        return false;
    }

    $request_path = wp_parse_url($raw_request_uri, PHP_URL_PATH);
    if (!is_string($request_path) || $request_path === '') {
        return false;
    }

    $request_path = '/' . ltrim($request_path, '/');
    $wp_sync_path = '/' . trim(rest_get_url_prefix(), '/') . '/wp-sync/v1';

    return $request_path === $wp_sync_path || strpos($request_path, $wp_sync_path . '/') === 0;
}
