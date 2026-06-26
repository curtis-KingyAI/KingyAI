<?php

if (!defined('ABSPATH')) {
    exit;
}

function kingy_ali_find_or_create_tool($tool_name, $args = array()) {
    $tool_name = sanitize_text_field(kingy_ali_tool_text_value($tool_name));
    if ($tool_name === '') {
        return 0;
    }

    $what_it_does = isset($args['what_it_does']) ? kingy_ali_tool_text_value($args['what_it_does']) : '';
    $existing = get_page_by_path(sanitize_title($tool_name), OBJECT, 'kingy_ai_tool');
    if ($existing) {
        kingy_ali_update_tool_from_args($existing->ID, $args);
        return (int) $existing->ID;
    }

    $post_id = wp_insert_post(
        array(
            'post_type' => 'kingy_ai_tool',
            'post_status' => isset($args['post_status']) ? sanitize_key($args['post_status']) : 'draft',
            'post_title' => $tool_name,
            'post_name' => sanitize_title($tool_name),
            'post_content' => $what_it_does !== '' ? sanitize_textarea_field($what_it_does) : '',
            'post_excerpt' => $what_it_does !== '' ? wp_trim_words(sanitize_textarea_field($what_it_does), 30) : '',
        ),
        true
    );

    if (is_wp_error($post_id)) {
        return 0;
    }

    kingy_ali_update_tool_from_args($post_id, $args);
    return (int) $post_id;
}

function kingy_ali_update_tool_from_args($tool_id, $args) {
    if (!empty($args['post_status']) && $args['post_status'] === 'publish' && get_post_status($tool_id) !== 'publish') {
        wp_update_post(
            array(
                'ID' => absint($tool_id),
                'post_status' => 'publish',
            )
        );
    }

    $field_map = array(
        'company',
        'official_url',
        'demo_url',
        'what_it_does',
        'best_for',
        'pricing',
        'free_plan',
        'api_available',
        'open_source_or_open_weight',
        'alternatives_url',
        'related_article_url',
        'related_course_url',
        'related_review_url',
        'latest_launch_id',
        'last_verified',
    );

    $fields = kingy_ali_tool_meta_fields();
    foreach ($field_map as $key) {
        if (!isset($args[$key]) || $args[$key] === '') {
            continue;
        }

        $field = isset($fields[$key]) ? $fields[$key] : array('type' => 'text');
        $value = kingy_ali_sanitize_meta_value($args[$key], $field);
        if ($value === '' || $value === null) {
            continue;
        }

        update_post_meta($tool_id, kingy_ali_meta_key($key), $value);
    }

    if (!empty($args['category'])) {
        kingy_ali_set_tool_terms($tool_id, $args['category'], 'kingy_launch_category');
    }

    if (!empty($args['audience'])) {
        kingy_ali_set_tool_terms($tool_id, $args['audience'], 'kingy_audience');
    }

    if (!empty($args['attributes'])) {
        kingy_ali_set_tool_terms($tool_id, $args['attributes'], 'kingy_tool_attribute');
    }
}

function kingy_ali_tool_meta_text($post_id, $key, $default = '') {
    return kingy_ali_tool_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_tool_text_value($value, $default = '') {
    if (function_exists('kingy_ali_public_profile_text')) {
        return kingy_ali_public_profile_text($value, $default);
    }

    if (!is_scalar($value)) {
        return is_scalar($default) ? (string) $default : '';
    }

    $value = trim((string) $value);
    if ($value === '' && is_scalar($default)) {
        return (string) $default;
    }

    return $value;
}

function kingy_ali_tool_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_link_launch_to_tool($launch_id, $tool_id) {
    $launch_id = absint($launch_id);
    $tool_id = absint($tool_id);
    if (!$launch_id || !$tool_id) {
        return;
    }
    if (get_post_type($launch_id) !== 'kingy_ai_launch' || get_post_type($tool_id) !== 'kingy_ai_tool') {
        return;
    }

    $previous_tool_id = kingy_ali_tool_related_id(get_post_meta($launch_id, kingy_ali_meta_key('related_tool_id'), true));
    update_post_meta($launch_id, kingy_ali_meta_key('related_tool_id'), $tool_id);
    kingy_ali_update_tool_latest_launch($tool_id);
    if ($previous_tool_id && $previous_tool_id !== $tool_id && get_post_type($previous_tool_id) === 'kingy_ai_tool') {
        kingy_ali_update_tool_latest_launch($previous_tool_id);
    }

    if (get_post_status($launch_id) === 'publish' && get_post_status($tool_id) !== 'publish') {
        wp_update_post(
            array(
                'ID' => $tool_id,
                'post_status' => 'publish',
            )
        );
    }
}

function kingy_ali_update_tool_latest_launch($tool_id) {
    $tool_id = absint($tool_id);
    if (!$tool_id || get_post_type($tool_id) !== 'kingy_ai_tool') {
        return 0;
    }

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_key' => kingy_ali_meta_key('launch_date'),
            'orderby' => 'meta_value',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key('related_tool_id'),
                    'value' => $tool_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    $latest_launch_id = !empty($query->posts) ? absint($query->posts[0]) : 0;
    if ($latest_launch_id) {
        update_post_meta($tool_id, kingy_ali_meta_key('latest_launch_id'), $latest_launch_id);
    } else {
        delete_post_meta($tool_id, kingy_ali_meta_key('latest_launch_id'));
    }

    return $latest_launch_id;
}

function kingy_ali_sync_tool_from_launch($launch_id, $tool_name = '') {
    $launch_id = absint($launch_id);
    if (!$launch_id) {
        return 0;
    }

    if ($tool_name === '') {
        $tool_name = get_the_title($launch_id);
    }

    $category_terms = get_the_terms($launch_id, 'kingy_launch_category');
    $audience_terms = get_the_terms($launch_id, 'kingy_audience');
    $attribute_terms = get_the_terms($launch_id, 'kingy_tool_attribute');

    $tool_id = kingy_ali_find_or_create_tool(
        $tool_name,
        array(
            'post_status' => get_post_status($launch_id) === 'publish' ? 'publish' : 'draft',
            'company' => kingy_ali_tool_meta_text($launch_id, 'company'),
            'official_url' => kingy_ali_tool_meta_text($launch_id, 'official_url'),
            'demo_url' => kingy_ali_tool_meta_text($launch_id, 'demo_url', kingy_ali_tool_meta_text($launch_id, 'youtube_url')),
            'what_it_does' => kingy_ali_tool_meta_text($launch_id, 'what_launched'),
            'best_for' => kingy_ali_tool_meta_text($launch_id, 'who_it_is_for'),
            'pricing' => kingy_ali_tool_meta_text($launch_id, 'pricing'),
            'free_plan' => kingy_ali_tool_meta_text($launch_id, 'free_plan'),
            'api_available' => kingy_ali_tool_meta_text($launch_id, 'api_available'),
            'open_source_or_open_weight' => kingy_ali_tool_meta_text($launch_id, 'open_source_or_open_weight'),
            'alternatives_url' => kingy_ali_tool_meta_text($launch_id, 'related_alternatives_url'),
            'related_article_url' => kingy_ali_tool_meta_text($launch_id, 'related_article_url'),
            'related_course_url' => kingy_ali_tool_meta_text($launch_id, 'related_course_url'),
            'related_review_url' => kingy_ali_tool_meta_text($launch_id, 'related_review_url'),
            'latest_launch_id' => $launch_id,
            'last_verified' => kingy_ali_tool_meta_text($launch_id, 'last_verified'),
            'category' => kingy_ali_term_slugs($category_terms),
            'audience' => kingy_ali_term_slugs($audience_terms),
            'attributes' => kingy_ali_term_slugs($attribute_terms),
        )
    );

    if ($tool_id) {
        kingy_ali_link_launch_to_tool($launch_id, $tool_id);
        $company_id = kingy_ali_sync_company_from_launch($launch_id);
        if ($company_id) {
            kingy_ali_link_tool_to_company($tool_id, $company_id);
        }
        kingy_ali_sync_derived_attributes($launch_id);
        kingy_ali_sync_derived_attributes($tool_id);
    }

    return $tool_id;
}

function kingy_ali_set_tool_terms($tool_id, $terms, $taxonomy) {
    if (!is_array($terms)) {
        $terms = array_filter(array_map('trim', explode(',', (string) $terms)));
    }

    $slugs = array();
    foreach ($terms as $term) {
        $slug = sanitize_title($term);
        if ($slug === '') {
            continue;
        }

        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($term, $taxonomy, array('slug' => $slug));
        }
        $slugs[] = $slug;
    }

    if ($slugs) {
        wp_set_object_terms($tool_id, $slugs, $taxonomy, false);
    }
}

function kingy_ali_term_slugs($terms) {
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    return wp_list_pluck($terms, 'slug');
}

function kingy_ali_query_tool_launches($tool_id, $limit = 10) {
    $limit = absint($limit);
    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'meta_key' => kingy_ali_meta_key('launch_date'),
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_tool_id'),
                'value' => absint($tool_id),
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    return kingy_ali_run_public_filtered_query($query_args, $limit, 'kingy_ali_public_query_accepts_index_ready_post');
}

function kingy_ali_tool_launch_rollup($tool_id) {
    static $cache = array();

    $tool_id = absint($tool_id);
    if (!$tool_id || get_post_type($tool_id) !== 'kingy_ai_tool') {
        return array(
            'count' => 0,
            'latest_id' => 0,
            'latest_date' => '',
        );
    }

    if (isset($cache[$tool_id])) {
        return $cache[$tool_id];
    }

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size(0),
        'fields' => 'ids',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_key' => kingy_ali_meta_key('launch_date'),
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('related_tool_id'),
                'value' => $tool_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ),
        ),
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);
    $query = kingy_ali_run_public_filtered_query($query_args, 0, 'kingy_ali_public_query_accepts_index_ready_post');

    $latest_id = !empty($query->posts) ? absint($query->posts[0]) : 0;
    $cache[$tool_id] = array(
        'count' => (int) $query->post_count,
        'latest_id' => $latest_id,
        'latest_date' => $latest_id ? kingy_ali_get_meta($latest_id, 'launch_date') : '',
    );

    return $cache[$tool_id];
}
