<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_kingy_ali_moderate_submission', 'kingy_ali_handle_submission_moderation');

function kingy_ali_render_editorial_queues_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Editorial Queues', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Use these queues to decide what to verify, publish, cover on YouTube, update, or route into founder outreach.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php
        kingy_ali_render_graph_integrity_queue();

        kingy_ali_render_launch_queue(
            __('Founder submissions waiting for review', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('pending', 'draft'),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => kingy_ali_meta_key('founder_submitted'),
                        'value' => '1',
                    ),
                    kingy_ali_submission_active_review_meta_query(),
                ),
            ),
            array('company', 'launch_date', 'category', 'youtube_interest', 'visibility_score_interest')
        );

        kingy_ali_render_launch_update_queue();

        kingy_ali_render_launch_queue(
            __('YouTube-worthy launch candidates', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'meta_key' => kingy_ali_meta_key('youtube_score'),
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => kingy_ali_meta_key('youtube_score'),
                        'value' => 7,
                        'compare' => '>=',
                        'type' => 'NUMERIC',
                    ),
                ),
            ),
            array('youtube_score', 'demo_quality_score', 'demo_url', 'category')
        );

        kingy_ali_render_launch_queue(
            __('Full article candidates', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'post__in' => kingy_ali_editorial_launch_ids_missing_valid_url_with_score('related_article_url', 'seo_score', 7, 10) ?: array(0),
                'orderby' => 'post__in',
            ),
            array('seo_score', 'kingy_launch_score', 'category', 'target_search_query', 'related_article_url')
        );

        kingy_ali_render_launch_queue(
            __('Internal partner-fit candidates', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => kingy_ali_meta_key('sponsor_fit_score_internal'),
                        'value' => 7,
                        'compare' => '>=',
                        'type' => 'NUMERIC',
                    ),
                    array(
                        'key' => kingy_ali_meta_key('sponsorship_interest'),
                        'value' => 'yes',
                        'compare' => '=',
                    ),
                    array(
                        'key' => kingy_ali_meta_key('budget_likelihood_internal'),
                        'value' => 'high',
                        'compare' => '=',
                    ),
                ),
            ),
            array('sponsor_fit_score_internal', 'budget_likelihood_internal', 'outreach_status', 'sponsorship_interest', 'founder_contact_email', 'company')
        );

        kingy_ali_render_launch_queue(
            __('Launches missing demo links', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'post__in' => kingy_ali_editorial_post_ids_missing_all_valid_url_meta('kingy_ai_launch', array('demo_url', 'youtube_url'), 10) ?: array(0),
                'orderby' => 'post__in',
            ),
            array('company', 'launch_date', 'youtube_score', 'official_url')
        );

        kingy_ali_render_tool_queue(
            __('Tool profiles missing pricing', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'post__in' => function_exists('kingy_ali_admin_tool_ids_missing_pricing') ? (kingy_ali_admin_tool_ids_missing_pricing(10) ?: array(0)) : array(0),
                'orderby' => 'post__in',
            ),
            array('company', 'pricing', 'official_url', 'latest_launch_id', 'last_verified')
        );

        kingy_ali_render_tool_queue(
            __('Tool profiles missing official demos', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('publish', 'pending', 'draft'),
                'post__in' => kingy_ali_editorial_post_ids_missing_all_valid_url_meta('kingy_ai_tool', array('demo_url'), 10) ?: array(0),
                'orderby' => 'post__in',
            ),
            array('company', 'official_url', 'demo_url', 'latest_launch_id', 'last_verified')
        );
        ?>
    </div>
    <?php
}

function kingy_ali_render_submissions_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Founder Submissions', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Review founder-submitted launches, verify source claims, and route strong candidates into Launch Radar, YouTube planning, or outreach.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php kingy_ali_render_submission_moderation_notice(); ?>
        <?php
        kingy_ali_render_launch_queue(
            __('Pending and draft founder submissions', 'kingy-ai-launch-intelligence'),
            array(
                'post_status' => array('pending', 'draft'),
                'posts_per_page' => 25,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => kingy_ali_meta_key('founder_submitted'),
                        'value' => '1',
                    ),
                    kingy_ali_submission_active_review_meta_query(),
                ),
            ),
            array('company', 'founder_contact_email', 'category', 'outreach_status', 'youtube_interest', 'creator_coverage_interest', 'sponsorship_interest', 'visibility_score_interest', 'official_url')
        );
        ?>
    </div>
    <?php
}

function kingy_ali_render_graph_integrity_queue() {
    $issues = kingy_ali_graph_integrity_issues(20);

    echo '<h2>' . esc_html__('Graph integrity checks', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Record', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Type', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Issue', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Action', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($issues) {
        foreach ($issues as $issue) {
            echo '<tr>';
            echo '<td>' . kingy_ali_graph_issue_record_link($issue) . '</td>';
            echo '<td>' . esc_html($issue['type']) . '</td>';
            echo '<td>' . esc_html($issue['message']) . '</td>';
            echo '<td>' . esc_html($issue['action']) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">' . esc_html__('No graph integrity issues found.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_graph_integrity_issues($limit = 20) {
    $limit = absint($limit);
    $issues = array();

    foreach (kingy_ali_graph_integrity_post_ids('kingy_ai_launch') as $launch_id) {
        $tool_id = kingy_ali_editorial_queue_related_id(kingy_ali_get_meta($launch_id, 'related_tool_id'));
        if (!$tool_id) {
            $issues[] = kingy_ali_graph_issue(
                $launch_id,
                __('Launch', 'kingy-ai-launch-intelligence'),
                __('Missing related tool profile.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or set Related tool post ID.', 'kingy-ai-launch-intelligence')
            );
        } elseif (!kingy_ali_graph_valid_related_post($tool_id, 'kingy_ai_tool')) {
            $issues[] = kingy_ali_graph_issue(
                $launch_id,
                __('Launch', 'kingy-ai-launch-intelligence'),
                __('Related tool points to a missing or invalid post.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or replace the Related tool post ID.', 'kingy-ai-launch-intelligence')
            );
        }

        $company = kingy_ali_editorial_queue_meta_text($launch_id, 'company');
        $company_id = kingy_ali_editorial_queue_related_id(kingy_ali_get_meta($launch_id, 'related_company_id'));
        if ($company !== '' && !$company_id) {
            $issues[] = kingy_ali_graph_issue(
                $launch_id,
                __('Launch', 'kingy-ai-launch-intelligence'),
                __('Company name is present but no company profile is linked.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or set Related company post ID.', 'kingy-ai-launch-intelligence')
            );
        } elseif ($company_id && !kingy_ali_graph_valid_related_post($company_id, 'kingy_ai_company')) {
            $issues[] = kingy_ali_graph_issue(
                $launch_id,
                __('Launch', 'kingy-ai-launch-intelligence'),
                __('Related company points to a missing or invalid post.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or replace the Related company post ID.', 'kingy-ai-launch-intelligence')
            );
        }

        if ($limit && count($issues) >= $limit) {
            return $issues;
        }
    }

    foreach (kingy_ali_graph_integrity_post_ids('kingy_ai_tool') as $tool_id) {
        $latest_launch_id = kingy_ali_editorial_queue_related_id(kingy_ali_get_meta($tool_id, 'latest_launch_id'));
        if (!$latest_launch_id) {
            $issues[] = kingy_ali_graph_issue(
                $tool_id,
                __('Tool', 'kingy-ai-launch-intelligence'),
                __('Missing latest launch link.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or link at least one launch to this tool.', 'kingy-ai-launch-intelligence')
            );
        } elseif (!kingy_ali_graph_valid_related_post($latest_launch_id, 'kingy_ai_launch')) {
            $issues[] = kingy_ali_graph_issue(
                $tool_id,
                __('Tool', 'kingy-ai-launch-intelligence'),
                __('Latest launch points to a missing or invalid launch.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or replace the Latest launch post ID.', 'kingy-ai-launch-intelligence')
            );
        }

        $company = kingy_ali_editorial_queue_meta_text($tool_id, 'company');
        $company_id = kingy_ali_editorial_queue_related_id(kingy_ali_get_meta($tool_id, 'related_company_id'));
        if ($company !== '' && !$company_id) {
            $issues[] = kingy_ali_graph_issue(
                $tool_id,
                __('Tool', 'kingy-ai-launch-intelligence'),
                __('Company name is present but no company profile is linked.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or set Related company post ID.', 'kingy-ai-launch-intelligence')
            );
        } elseif ($company_id && !kingy_ali_graph_valid_related_post($company_id, 'kingy_ai_company')) {
            $issues[] = kingy_ali_graph_issue(
                $tool_id,
                __('Tool', 'kingy-ai-launch-intelligence'),
                __('Related company points to a missing or invalid post.', 'kingy-ai-launch-intelligence'),
                __('Run Maintenance or replace the Related company post ID.', 'kingy-ai-launch-intelligence')
            );
        }

        if ($limit && count($issues) >= $limit) {
            return $issues;
        }
    }

    foreach (kingy_ali_graph_integrity_post_ids('kingy_ai_company') as $company_id) {
        $launch_count = kingy_ali_admin_related_count('kingy_ai_launch', 'related_company_id', $company_id);
        $tool_count = kingy_ali_admin_related_count('kingy_ai_tool', 'related_company_id', $company_id);
        if ($launch_count === 0 && $tool_count === 0) {
            $issues[] = kingy_ali_graph_issue(
                $company_id,
                __('Company', 'kingy-ai-launch-intelligence'),
                __('Company profile has no related launches or tools.', 'kingy-ai-launch-intelligence'),
                __('Link a launch/tool to this company or keep it in draft until connected.', 'kingy-ai-launch-intelligence')
            );
        }

        if ($limit && count($issues) >= $limit) {
            return $issues;
        }
    }

    return $issues;
}

function kingy_ali_graph_integrity_post_ids($post_type) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
        )
    );

    return array_map('absint', $query->posts);
}

function kingy_ali_graph_valid_related_post($post_id, $post_type) {
    return kingy_ali_related_post_is_valid($post_id, $post_type);
}

function kingy_ali_editorial_queue_meta_text($post_id, $key, $default = '') {
    return kingy_ali_editorial_queue_text_value(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_editorial_queue_text_value($value, $default = '') {
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

function kingy_ali_editorial_queue_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_graph_issue($post_id, $type, $message, $action) {
    return array(
        'post_id' => absint($post_id),
        'type' => $type,
        'message' => $message,
        'action' => $action,
    );
}

function kingy_ali_graph_issue_record_link($issue) {
    $post_id = absint($issue['post_id']);
    if (!$post_id || !get_post($post_id)) {
        return esc_html__('Unknown record', 'kingy-ai-launch-intelligence');
    }

    return '<a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html(get_the_title($post_id)) . '</a>';
}

function kingy_ali_render_launch_queue($title, $query_args, $columns) {
    $defaults = array(
        'post_type' => 'kingy_ai_launch',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    kingy_ali_render_editorial_queue_table($title, new WP_Query(wp_parse_args($query_args, $defaults)), $columns);
}

function kingy_ali_render_launch_update_queue() {
    $ids = kingy_ali_launches_needing_verification_update_ids(10);

    kingy_ali_render_launch_queue(
        __('Launches needing verification or updates', 'kingy-ai-launch-intelligence'),
        array(
            'post_status' => array('publish', 'pending', 'draft'),
            'post__in' => $ids ? $ids : array(0),
            'orderby' => 'post__in',
        ),
        array('verification_status', 'last_verified', 'sources', 'official_url')
    );
}

function kingy_ali_render_tool_queue($title, $query_args, $columns) {
    $defaults = array(
        'post_type' => 'kingy_ai_tool',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    kingy_ali_render_editorial_queue_table($title, new WP_Query(wp_parse_args($query_args, $defaults)), $columns);
}

function kingy_ali_editorial_post_ids_missing_all_valid_url_meta($post_type, $meta_keys, $limit = 10) {
    $query = new WP_Query(
        array(
            'post_type' => sanitize_key($post_type),
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        )
    );

    $ids = array();
    $limit = absint($limit);
    $meta_keys = array_values(array_filter(array_map('sanitize_key', (array) $meta_keys)));
    foreach ((array) $query->posts as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id) {
            continue;
        }

        $has_valid_url = false;
        foreach ($meta_keys as $key) {
            if (kingy_ali_editorial_url_meta_is_valid($post_id, $key)) {
                $has_valid_url = true;
                break;
            }
        }

        if ($has_valid_url) {
            continue;
        }

        $ids[] = $post_id;
        if ($limit && count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_editorial_launch_ids_missing_valid_url_with_score($url_meta_key, $score_meta_key, $minimum_score = 7, $limit = 10) {
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => kingy_ali_meta_key(sanitize_key($score_meta_key)),
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'no_found_rows' => true,
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key(sanitize_key($score_meta_key)),
                    'value' => (float) $minimum_score,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    $ids = array();
    $limit = absint($limit);
    $url_meta_key = sanitize_key($url_meta_key);
    foreach ((array) $query->posts as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id || kingy_ali_editorial_url_meta_is_valid($post_id, $url_meta_key)) {
            continue;
        }

        $ids[] = $post_id;
        if ($limit && count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_editorial_url_meta_is_valid($post_id, $key) {
    if (function_exists('kingy_ali_public_meta_url_is_valid')) {
        return kingy_ali_public_meta_url_is_valid($post_id, $key);
    }

    if (function_exists('kingy_ali_public_url_value')) {
        return kingy_ali_public_url_value(kingy_ali_get_meta($post_id, $key)) !== '';
    }

    $url = kingy_ali_get_meta($post_id, $key);
    if (!is_scalar($url)) {
        return false;
    }

    $parts = wp_parse_url((string) $url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true);
}

function kingy_ali_render_editorial_queue_table($title, $query, $columns) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Record', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Status', 'kingy-ai-launch-intelligence') . '</th>';
    foreach ($columns as $column) {
        echo '<th>' . esc_html(kingy_ali_queue_column_label($column)) . '</th>';
    }
    echo '<th>' . esc_html__('Actions', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            echo '<tr>';
            echo '<td><strong>' . esc_html(get_the_title($post_id)) . '</strong></td>';
            echo '<td>' . esc_html(get_post_status($post_id)) . '</td>';
            foreach ($columns as $column) {
                echo '<td>' . kingy_ali_queue_column_value($post_id, $column) . '</td>';
            }
            echo '<td>';
            echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html__('Edit', 'kingy-ai-launch-intelligence') . '</a>';
            if (get_post_status($post_id) === 'publish') {
                echo ' | <a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html__('View', 'kingy-ai-launch-intelligence') . '</a>';
            }
            kingy_ali_render_submission_moderation_actions($post_id);
            echo '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();
    } else {
        echo '<tr><td colspan="' . esc_attr((string) (count($columns) + 3)) . '">' . esc_html__('Nothing in this queue yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_queue_column_label($column) {
    $labels = array(
        'category' => __('Category', 'kingy-ai-launch-intelligence'),
        'company' => __('Company', 'kingy-ai-launch-intelligence'),
        'budget_likelihood_internal' => __('Budget likelihood', 'kingy-ai-launch-intelligence'),
        'creator_coverage_interest' => __('Creator review?', 'kingy-ai-launch-intelligence'),
        'demo_quality_score' => __('Demo score', 'kingy-ai-launch-intelligence'),
        'demo_url' => __('Demo', 'kingy-ai-launch-intelligence'),
        'founder_contact_email' => __('Founder email', 'kingy-ai-launch-intelligence'),
        'founder_notes' => __('Founder notes', 'kingy-ai-launch-intelligence'),
        'kingy_launch_score' => __('Launch score', 'kingy-ai-launch-intelligence'),
        'last_verified' => __('Last verified', 'kingy-ai-launch-intelligence'),
        'latest_launch_id' => __('Latest launch', 'kingy-ai-launch-intelligence'),
        'launch_date' => __('Launch date', 'kingy-ai-launch-intelligence'),
        'official_url' => __('Official URL', 'kingy-ai-launch-intelligence'),
        'outreach_status' => __('Outreach status', 'kingy-ai-launch-intelligence'),
        'related_article_url' => __('Related article', 'kingy-ai-launch-intelligence'),
        'seo_score' => __('SEO score', 'kingy-ai-launch-intelligence'),
        'sources' => __('Sources', 'kingy-ai-launch-intelligence'),
        'sponsorship_interest' => __('Creator campaign?', 'kingy-ai-launch-intelligence'),
        'sponsor_fit_score_internal' => __('Partner-fit score', 'kingy-ai-launch-intelligence'),
        'target_search_query' => __('Target query', 'kingy-ai-launch-intelligence'),
        'verification_status' => __('Verification', 'kingy-ai-launch-intelligence'),
        'visibility_score_interest' => __('Visibility score?', 'kingy-ai-launch-intelligence'),
        'youtube_interest' => __('YouTube?', 'kingy-ai-launch-intelligence'),
        'youtube_score' => __('YouTube score', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$column]) ? $labels[$column] : ucwords(str_replace('_', ' ', $column));
}

function kingy_ali_queue_column_value($post_id, $column) {
    if ($column === 'category') {
        $terms = get_the_terms($post_id, 'kingy_launch_category');
        if (is_wp_error($terms) || empty($terms)) {
            return '&mdash;';
        }
        return esc_html(implode(', ', wp_list_pluck($terms, 'name')));
    }

    if ($column === 'latest_launch_id') {
        $launch_id = kingy_ali_editorial_queue_related_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
        if (!kingy_ali_graph_valid_related_post($launch_id, 'kingy_ai_launch')) {
            return '&mdash;';
        }
        return '<a href="' . esc_url(get_edit_post_link($launch_id)) . '">' . esc_html(get_the_title($launch_id)) . '</a>';
    }

    if ($column === 'verification_status') {
        return esc_html(kingy_ali_verification_label($post_id));
    }

    if ($column === 'last_verified') {
        return esc_html(kingy_ali_verification_freshness_label($post_id));
    }

    $raw_value = kingy_ali_get_meta($post_id, $column);
    $value = kingy_ali_editorial_queue_text_value($raw_value);
    if ($value === '') {
        return '&mdash;';
    }

    $url = kingy_ali_editorial_queue_url_value($raw_value);
    if ($url) {
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . esc_html__('Open', 'kingy-ai-launch-intelligence') . '</a>';
    }

    return esc_html(wp_trim_words((string) $value, 10));
}

function kingy_ali_editorial_queue_url_value($url) {
    if (function_exists('kingy_ali_public_url_value')) {
        return kingy_ali_public_url_value($url);
    }

    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($url);
    }

    if (!is_scalar($url)) {
        return '';
    }

    $parts = wp_parse_url((string) $url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    return in_array(strtolower((string) $parts['scheme']), array('http', 'https'), true) ? (string) $url : '';
}

function kingy_ali_render_submission_moderation_notice() {
    $action = sanitize_key(kingy_ali_submission_moderation_request_value('kingy_ali_submission_moderated'));
    if ($action === '') {
        return;
    }

    $messages = array(
        'approve_publish' => __('Submission published and left available for follow-up.', 'kingy-ai-launch-intelligence'),
        'review_later' => __('Submission moved to draft and marked for research.', 'kingy-ai-launch-intelligence'),
        'mark_not_fit' => __('Submission kept in draft and marked not-fit.', 'kingy-ai-launch-intelligence'),
    );

    if (empty($messages[$action])) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$action]) . '</p></div>';
}

function kingy_ali_render_submission_moderation_actions($post_id) {
    if (!kingy_ali_submission_can_be_moderated($post_id)) {
        return;
    }

    $actions = array(
        'approve_publish' => __('Publish', 'kingy-ai-launch-intelligence'),
        'review_later' => __('Draft for research', 'kingy-ai-launch-intelligence'),
        'mark_not_fit' => __('Mark not-fit', 'kingy-ai-launch-intelligence'),
    );

    foreach ($actions as $action => $label) {
        $url = wp_nonce_url(
            admin_url(
                add_query_arg(
                    array(
                        'action' => 'kingy_ali_moderate_submission',
                        'post_id' => absint($post_id),
                        'submission_action' => $action,
                    ),
                    'admin-post.php'
                )
            ),
            'kingy_ali_moderate_submission_' . absint($post_id) . '_' . $action
        );

        echo ' | <a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
}

function kingy_ali_handle_submission_moderation() {
    $post_id = absint(kingy_ali_submission_moderation_request_value('post_id'));
    $submission_action = sanitize_key(kingy_ali_submission_moderation_request_value('submission_action'));
    $allowed_actions = array('approve_publish', 'review_later', 'mark_not_fit');

    if (!$post_id || !in_array($submission_action, $allowed_actions, true)) {
        wp_die(esc_html__('Invalid submission moderation request.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_moderate_submission_' . $post_id . '_' . $submission_action);

    if (!current_user_can('edit_post', $post_id) || !kingy_ali_submission_can_be_moderated($post_id)) {
        wp_die(esc_html__('You do not have permission to moderate this submission.', 'kingy-ai-launch-intelligence'));
    }

    $post_status = $submission_action === 'approve_publish' ? 'publish' : 'draft';
    $result = wp_update_post(
        array(
            'ID' => $post_id,
            'post_status' => $post_status,
        ),
        true
    );

    if (is_wp_error($result)) {
        wp_die(esc_html($result->get_error_message()));
    }

    if ($submission_action === 'approve_publish') {
        delete_post_meta($post_id, kingy_ali_meta_key('noindex'));
        if (kingy_ali_editorial_queue_meta_text($post_id, 'last_verified') === '') {
            update_post_meta($post_id, kingy_ali_meta_key('last_verified'), current_time('Y-m-d'));
        }
    } elseif ($submission_action === 'review_later') {
        update_post_meta($post_id, kingy_ali_meta_key('outreach_status'), 'researching');
        update_post_meta($post_id, kingy_ali_meta_key('noindex'), '1');
    } elseif ($submission_action === 'mark_not_fit') {
        update_post_meta($post_id, kingy_ali_meta_key('outreach_status'), 'not_fit');
        update_post_meta($post_id, kingy_ali_meta_key('noindex'), '1');
        kingy_ali_append_submission_internal_note($post_id, __('Marked not-fit from the founder submissions queue.', 'kingy-ai-launch-intelligence'));
    }

    update_post_meta($post_id, kingy_ali_meta_key('last_reviewed'), current_time('Y-m-d'));
    kingy_ali_sync_launch_relationships($post_id);

    kingy_ali_track_event(
        'founder_submission_moderated',
        array(
            'event_label' => get_the_title($post_id),
            'object_id' => $post_id,
            'filters' => kingy_ali_submission_moderation_filters($post_id, $submission_action),
        )
    );

    wp_safe_redirect(kingy_ali_submission_moderation_redirect($submission_action));
    exit;
}

function kingy_ali_submission_moderation_request_value($key) {
    $values = kingy_ali_submission_moderation_request_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_submission_moderation_request_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_submission_moderation_redirect($submission_action) {
    $fallback = admin_url('admin.php?page=kingy-ali-submissions');
    $redirect = wp_get_referer();
    if (!is_string($redirect) || $redirect === '') {
        $redirect = $fallback;
    }

    $redirect = wp_validate_redirect($redirect, $fallback);
    $redirect = remove_query_arg(array('kingy_ali_submission_moderated', '_wpnonce'), $redirect);

    return add_query_arg('kingy_ali_submission_moderated', sanitize_key($submission_action), $redirect);
}

function kingy_ali_submission_can_be_moderated($post_id) {
    $post_id = absint($post_id);
    return $post_id
        && get_post_type($post_id) === 'kingy_ai_launch'
        && get_post_meta($post_id, kingy_ali_meta_key('founder_submitted'), true) === '1'
        && kingy_ali_editorial_queue_meta_text($post_id, 'outreach_status') !== 'not_fit'
        && in_array(get_post_status($post_id), array('pending', 'draft'), true);
}

function kingy_ali_submission_active_review_meta_query() {
    return array(
        'relation' => 'OR',
        array(
            'key' => kingy_ali_meta_key('outreach_status'),
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key' => kingy_ali_meta_key('outreach_status'),
            'value' => 'not_fit',
            'compare' => '!=',
        ),
    );
}

function kingy_ali_append_submission_internal_note($post_id, $note) {
    $existing = kingy_ali_editorial_queue_meta_text($post_id, 'internal_notes');
    $dated_note = sprintf('[%1$s] %2$s', current_time('Y-m-d'), $note);
    $notes = trim($existing . "\n" . $dated_note);

    update_post_meta($post_id, kingy_ali_meta_key('internal_notes'), $notes);
}

function kingy_ali_submission_moderation_filters($post_id, $submission_action) {
    return array(
        'action' => $submission_action,
        'status' => get_post_status($post_id),
        'category' => kingy_ali_submission_first_term_slug($post_id, 'kingy_launch_category'),
        'audience' => kingy_ali_submission_first_term_slug($post_id, 'kingy_audience'),
        'youtube_interest' => kingy_ali_editorial_queue_meta_text($post_id, 'youtube_interest'),
        'creator_coverage_interest' => kingy_ali_editorial_queue_meta_text($post_id, 'creator_coverage_interest'),
        'sponsorship_interest' => kingy_ali_editorial_queue_meta_text($post_id, 'sponsorship_interest'),
        'visibility_score_interest' => kingy_ali_editorial_queue_meta_text($post_id, 'visibility_score_interest'),
    );
}

function kingy_ali_submission_first_term_slug($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return $terms[0]->slug;
}
