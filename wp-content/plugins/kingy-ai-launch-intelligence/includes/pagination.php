<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'kingy_ali_enforce_directory_archive_pagination_bounds', 5);

/**
 * Return the requested archive page without clamping it to the result set.
 */
function kingy_ali_archive_requested_page($legacy_query_key = '') {
    $page = absint(get_query_var('paged'));
    if (!$page) {
        $page = absint(get_query_var('page'));
    }
    if (!$page && $legacy_query_key !== '' && function_exists('kingy_ali_request_get_value')) {
        $page = absint(kingy_ali_request_get_value($legacy_query_key));
    }

    return max(1, $page);
}

/**
 * A first page is always a valid empty state; only later nonexistent pages 404.
 */
function kingy_ali_archive_page_is_out_of_range($requested_page, $total_pages) {
    $requested_page = max(1, absint($requested_page));
    $total_pages = absint($total_pages);

    return $requested_page > 1 && ($total_pages < 1 || $requested_page > $total_pages);
}

function kingy_ali_mark_current_request_404() {
    global $wp_query;

    if (is_object($wp_query) && method_exists($wp_query, 'set_404')) {
        $wp_query->set_404();
    } elseif (is_object($wp_query)) {
        $wp_query->is_404 = true;
    }

    status_header(404);
    nocache_headers();
}

function kingy_ali_model_directory_total_pages($per_page = 24) {
    $per_page = max(1, absint($per_page));
    $filters = kingy_ali_model_request_filters();
    $query = kingy_ali_query_model_directory(
        array_merge(
            $filters,
            array(
                'limit' => 0,
                'track_search' => false,
            )
        )
    );
    $total = is_object($query) && isset($query->posts) ? count((array) $query->posts) : 0;

    return $total > 0 ? (int) ceil($total / $per_page) : 0;
}

function kingy_ali_launch_taxonomy_total_pages($requested_page, $per_page = 18) {
    $filters = kingy_ali_request_filters();
    $filters['page'] = max(1, absint($requested_page));

    if (empty($filters['category'])) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term) && isset($term->slug)) {
            $filters['category'] = $term->slug;
        }
    }

    $query = kingy_ali_query_launches(
        array_merge(
            $filters,
            array(
                'limit' => max(1, absint($per_page)),
                'track_search' => false,
            )
        )
    );

    return is_object($query) && isset($query->max_num_pages)
        ? absint($query->max_num_pages)
        : 0;
}

/**
 * Restore normal WordPress 404 handling for nonexistent custom archive pages.
 */
function kingy_ali_enforce_directory_archive_pagination_bounds() {
    if (function_exists('is_404') && is_404()) {
        return false;
    }

    if (is_post_type_archive('kingy_ai_model')) {
        $requested_page = kingy_ali_archive_requested_page('kali_model_page');
        if ($requested_page > 1 && kingy_ali_archive_page_is_out_of_range($requested_page, kingy_ali_model_directory_total_pages(24))) {
            kingy_ali_mark_current_request_404();
            return true;
        }
    }

    if (is_tax('kingy_launch_category')) {
        $requested_page = kingy_ali_archive_requested_page('kali_page');
        if ($requested_page > 1 && kingy_ali_archive_page_is_out_of_range($requested_page, kingy_ali_launch_taxonomy_total_pages($requested_page, 18))) {
            kingy_ali_mark_current_request_404();
            return true;
        }
    }

    return false;
}
