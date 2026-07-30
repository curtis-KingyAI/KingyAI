<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('manage_kingy_ai_launch_posts_columns', 'kingy_ali_launch_admin_columns');
add_action('manage_kingy_ai_launch_posts_custom_column', 'kingy_ali_render_launch_admin_column', 10, 2);
add_filter('manage_edit-kingy_ai_launch_sortable_columns', 'kingy_ali_launch_sortable_columns');

add_filter('manage_kingy_ai_tool_posts_columns', 'kingy_ali_tool_admin_columns');
add_action('manage_kingy_ai_tool_posts_custom_column', 'kingy_ali_render_tool_admin_column', 10, 2);
add_filter('manage_edit-kingy_ai_tool_sortable_columns', 'kingy_ali_tool_sortable_columns');

add_filter('manage_kingy_ai_company_posts_columns', 'kingy_ali_company_admin_columns');
add_action('manage_kingy_ai_company_posts_custom_column', 'kingy_ali_render_company_admin_column', 10, 2);
add_filter('manage_edit-kingy_ai_company_sortable_columns', 'kingy_ali_company_sortable_columns');

add_filter('manage_kingy_ai_model_posts_columns', 'kingy_ali_model_admin_columns');
add_action('manage_kingy_ai_model_posts_custom_column', 'kingy_ali_render_model_admin_column', 10, 2);
add_filter('manage_edit-kingy_ai_model_sortable_columns', 'kingy_ali_model_sortable_columns');

add_action('pre_get_posts', 'kingy_ali_admin_columns_orderby');

function kingy_ali_launch_admin_columns($columns) {
    return kingy_ali_insert_admin_columns(
        $columns,
        array(
            'kingy_company' => __('Company', 'kingy-ai-launch-intelligence'),
            'kingy_launch_date' => __('Launch date', 'kingy-ai-launch-intelligence'),
            'kingy_launch_score' => __('Launch score', 'kingy-ai-launch-intelligence'),
            'kingy_youtube_score' => __('YouTube', 'kingy-ai-launch-intelligence'),
            'kingy_verification' => __('Verification', 'kingy-ai-launch-intelligence'),
            'kingy_related' => __('Related', 'kingy-ai-launch-intelligence'),
        )
    );
}

function kingy_ali_tool_admin_columns($columns) {
    return kingy_ali_insert_admin_columns(
        $columns,
        array(
            'kingy_company' => __('Company', 'kingy-ai-launch-intelligence'),
            'kingy_pricing' => __('Pricing', 'kingy-ai-launch-intelligence'),
            'kingy_demo' => __('Demo', 'kingy-ai-launch-intelligence'),
            'kingy_latest_launch' => __('Latest launch', 'kingy-ai-launch-intelligence'),
            'kingy_company_profile' => __('Company profile', 'kingy-ai-launch-intelligence'),
            'kingy_last_verified' => __('Last verified', 'kingy-ai-launch-intelligence'),
        )
    );
}

function kingy_ali_company_admin_columns($columns) {
    return kingy_ali_insert_admin_columns(
        $columns,
        array(
            'kingy_official_url' => __('Official', 'kingy-ai-launch-intelligence'),
            'kingy_funding' => __('Funding', 'kingy-ai-launch-intelligence'),
            'kingy_sponsor_score' => __('Partner-fit score', 'kingy-ai-launch-intelligence'),
            'kingy_outreach_status' => __('Outreach', 'kingy-ai-launch-intelligence'),
            'kingy_related_counts' => __('Graph', 'kingy-ai-launch-intelligence'),
            'kingy_last_verified' => __('Last verified', 'kingy-ai-launch-intelligence'),
        )
    );
}

function kingy_ali_model_admin_columns($columns) {
    return kingy_ali_insert_admin_columns(
        $columns,
        array(
            'kingy_model_provider' => __('Provider', 'kingy-ai-launch-intelligence'),
            'kingy_model_release_date' => __('Release date', 'kingy-ai-launch-intelligence'),
            'kingy_model_access' => __('Access', 'kingy-ai-launch-intelligence'),
            'kingy_verification' => __('Verification', 'kingy-ai-launch-intelligence'),
            'kingy_last_verified' => __('Last verified', 'kingy-ai-launch-intelligence'),
        )
    );
}

function kingy_ali_insert_admin_columns($columns, $insert) {
    $ordered = array();
    foreach ($columns as $key => $label) {
        $ordered[$key] = $label;
        if ($key === 'title') {
            foreach ($insert as $insert_key => $insert_label) {
                $ordered[$insert_key] = $insert_label;
            }
        }
    }

    return $ordered;
}

function kingy_ali_launch_sortable_columns($columns) {
    $columns['kingy_company'] = 'kingy_company';
    $columns['kingy_launch_date'] = 'kingy_launch_date';
    $columns['kingy_launch_score'] = 'kingy_launch_score';
    $columns['kingy_youtube_score'] = 'kingy_youtube_score';
    $columns['kingy_verification'] = 'kingy_verification';
    return $columns;
}

function kingy_ali_tool_sortable_columns($columns) {
    $columns['kingy_company'] = 'kingy_company';
    $columns['kingy_pricing'] = 'kingy_pricing';
    $columns['kingy_last_verified'] = 'kingy_last_verified';
    return $columns;
}

function kingy_ali_company_sortable_columns($columns) {
    $columns['kingy_funding'] = 'kingy_funding';
    $columns['kingy_sponsor_score'] = 'kingy_sponsor_score';
    $columns['kingy_outreach_status'] = 'kingy_outreach_status';
    $columns['kingy_last_verified'] = 'kingy_last_verified';
    return $columns;
}

function kingy_ali_model_sortable_columns($columns) {
    $columns['kingy_model_provider'] = 'kingy_model_provider';
    $columns['kingy_model_release_date'] = 'kingy_model_release_date';
    $columns['kingy_verification'] = 'kingy_verification';
    $columns['kingy_last_verified'] = 'kingy_last_verified';
    return $columns;
}

function kingy_ali_render_launch_admin_column($column, $post_id) {
    switch ($column) {
        case 'kingy_company':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'company'));
            break;
        case 'kingy_launch_date':
            echo esc_html(kingy_ali_admin_column_date($post_id, 'launch_date'));
            break;
        case 'kingy_launch_score':
            echo esc_html(kingy_ali_admin_column_score($post_id, 'kingy_launch_score'));
            break;
        case 'kingy_youtube_score':
            echo esc_html(kingy_ali_admin_column_score_band($post_id, 'youtube_score'));
            break;
        case 'kingy_verification':
            echo esc_html(kingy_ali_verification_label($post_id));
            break;
        case 'kingy_related':
            kingy_ali_render_admin_related_links($post_id);
            break;
    }
}

function kingy_ali_render_tool_admin_column($column, $post_id) {
    switch ($column) {
        case 'kingy_company':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'company'));
            break;
        case 'kingy_pricing':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'pricing'));
            break;
        case 'kingy_demo':
            echo kingy_ali_admin_column_link(kingy_ali_get_meta($post_id, 'demo_url'));
            break;
        case 'kingy_latest_launch':
            echo kingy_ali_admin_column_post_link(kingy_ali_get_meta($post_id, 'latest_launch_id'), 'kingy_ai_launch');
            break;
        case 'kingy_company_profile':
            echo kingy_ali_admin_column_post_link(kingy_ali_get_meta($post_id, 'related_company_id'), 'kingy_ai_company');
            break;
        case 'kingy_last_verified':
            echo esc_html(kingy_ali_admin_column_date($post_id, 'last_verified'));
            break;
    }
}

function kingy_ali_render_company_admin_column($column, $post_id) {
    switch ($column) {
        case 'kingy_official_url':
            echo kingy_ali_admin_column_link(kingy_ali_get_meta($post_id, 'official_url'));
            break;
        case 'kingy_funding':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'funding'));
            break;
        case 'kingy_sponsor_score':
            echo esc_html(kingy_ali_admin_column_score($post_id, 'sponsor_fit_score_internal'));
            break;
        case 'kingy_outreach_status':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'outreach_status'));
            break;
        case 'kingy_related_counts':
            echo esc_html(
                sprintf(
                    __('%1$d launches / %2$d tools', 'kingy-ai-launch-intelligence'),
                    kingy_ali_admin_related_count('kingy_ai_launch', 'related_company_id', $post_id),
                    kingy_ali_admin_related_count('kingy_ai_tool', 'related_company_id', $post_id)
                )
            );
            break;
        case 'kingy_last_verified':
            echo esc_html(kingy_ali_admin_column_date($post_id, 'last_verified'));
            break;
    }
}

function kingy_ali_render_model_admin_column($column, $post_id) {
    switch ($column) {
        case 'kingy_model_provider':
            echo esc_html(kingy_ali_admin_column_text($post_id, 'provider_name'));
            break;
        case 'kingy_model_release_date':
            echo esc_html(kingy_ali_admin_column_date($post_id, 'release_date'));
            break;
        case 'kingy_model_access':
            echo esc_html(sprintf(
                __('API: %1$s / Open: %2$s / Local: %3$s', 'kingy-ai-launch-intelligence'),
                kingy_ali_admin_column_text($post_id, 'api_available'),
                kingy_ali_admin_column_text($post_id, 'open_weight'),
                kingy_ali_admin_column_text($post_id, 'local_available')
            ));
            break;
        case 'kingy_verification':
            echo esc_html(kingy_ali_verification_label($post_id));
            break;
        case 'kingy_last_verified':
            echo esc_html(kingy_ali_admin_column_date($post_id, 'last_verified'));
            break;
    }
}

function kingy_ali_admin_column_text($post_id, $key) {
    $value = kingy_ali_admin_column_meta_text($post_id, $key);
    return $value === '' ? __('—', 'kingy-ai-launch-intelligence') : wp_trim_words((string) $value, 8);
}

function kingy_ali_admin_column_date($post_id, $key) {
    $value = kingy_ali_admin_column_meta_text($post_id, $key);
    if ($value === '') {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    if (function_exists('kingy_ali_public_profile_date_label')) {
        $label = kingy_ali_public_profile_date_label($value);
        return $label !== '' ? $label : $value;
    }

    $timestamp = strtotime($value);
    return $timestamp ? date_i18n(get_option('date_format'), $timestamp) : $value;
}

function kingy_ali_admin_column_score($post_id, $key) {
    return kingy_ali_format_score(kingy_ali_admin_column_meta_text($post_id, $key));
}

function kingy_ali_admin_column_score_band($post_id, $key) {
    return kingy_ali_score_band(kingy_ali_admin_column_meta_text($post_id, $key));
}

function kingy_ali_admin_column_meta_text($post_id, $key, $default = '') {
    return kingy_ali_admin_column_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_admin_column_text_value($value, $default = '') {
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

function kingy_ali_admin_column_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_admin_column_link($url) {
    if (function_exists('kingy_ali_public_url_value')) {
        $url = kingy_ali_public_url_value($url);
    } elseif (function_exists('kingy_ali_schema_url')) {
        $url = kingy_ali_schema_url($url);
    } elseif (is_scalar($url)) {
        $parts = wp_parse_url((string) $url);
        $url = is_array($parts) && !empty($parts['scheme']) && !empty($parts['host']) && in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true) ? (string) $url : '';
    } else {
        $url = '';
    }

    if (!$url) {
        return esc_html__('—', 'kingy-ai-launch-intelligence');
    }

    return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . esc_html__('Open', 'kingy-ai-launch-intelligence') . '</a>';
}

function kingy_ali_admin_column_post_link($post_id, $post_type = '') {
    $post_id = kingy_ali_admin_column_related_id($post_id);
    if (!$post_id) {
        return esc_html__('—', 'kingy-ai-launch-intelligence');
    }
    if ($post_type && !kingy_ali_related_post_is_valid($post_id, $post_type)) {
        return esc_html__('—', 'kingy-ai-launch-intelligence');
    }

    return '<a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html(get_the_title($post_id)) . '</a>';
}

function kingy_ali_render_admin_related_links($post_id) {
    $links = array();
    $tool_id = kingy_ali_admin_column_related_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
    $company_id = kingy_ali_admin_column_related_id(kingy_ali_get_meta($post_id, 'related_company_id'));

    if (kingy_ali_related_post_is_valid($tool_id, 'kingy_ai_tool')) {
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(get_edit_post_link($tool_id)),
            esc_html__('Tool', 'kingy-ai-launch-intelligence')
        );
    }

    if (kingy_ali_related_post_is_valid($company_id, 'kingy_ai_company')) {
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(get_edit_post_link($company_id)),
            esc_html__('Company', 'kingy-ai-launch-intelligence')
        );
    }

    echo $links ? implode(' / ', $links) : esc_html__('—', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_related_count($post_type, $meta_key, $post_id) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 1,
            'fields' => 'ids',
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

    return (int) $query->found_posts;
}

function kingy_ali_admin_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');
    $map = array(
        'kingy_company' => array('company', 'CHAR'),
        // ISO month values (YYYY-MM) and exact dates (YYYY-MM-DD) are both
        // lexicographically sortable, while a DATE cast would coerce month-only data.
        'kingy_launch_date' => array('launch_date', 'CHAR'),
        'kingy_launch_score' => array('kingy_launch_score', 'NUMERIC'),
        'kingy_youtube_score' => array('youtube_score', 'NUMERIC'),
        'kingy_verification' => array('verification_status', 'CHAR'),
        'kingy_pricing' => array('pricing', 'CHAR'),
        'kingy_last_verified' => array('last_verified', 'DATE'),
        'kingy_funding' => array('funding', 'CHAR'),
        'kingy_sponsor_score' => array('sponsor_fit_score_internal', 'NUMERIC'),
        'kingy_outreach_status' => array('outreach_status', 'CHAR'),
        'kingy_model_provider' => array('provider_name', 'CHAR'),
        'kingy_model_release_date' => array('release_date', 'DATE'),
    );

    if (!isset($map[$orderby])) {
        return;
    }

    $query->set(
        'meta_query',
        array(
            'relation' => 'OR',
            'kingy_sort_value' => array(
                'key' => kingy_ali_meta_key($map[$orderby][0]),
                'compare' => 'EXISTS',
                'type' => $map[$orderby][1],
            ),
            'kingy_sort_missing' => array(
                'key' => kingy_ali_meta_key($map[$orderby][0]),
                'compare' => 'NOT EXISTS',
            ),
        )
    );
    $query->set('orderby', array('kingy_sort_value' => $query->get('order') ? $query->get('order') : 'ASC'));
}
