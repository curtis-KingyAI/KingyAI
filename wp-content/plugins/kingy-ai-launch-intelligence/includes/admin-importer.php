<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'kingy_ali_admin_menu');
add_action('admin_init', 'kingy_ali_register_settings');
add_action('admin_post_kingy_ali_import', 'kingy_ali_handle_admin_import');
add_action('admin_post_kingy_ali_download_import_template', 'kingy_ali_handle_download_import_template');
add_action('admin_post_kingy_ali_download_model_import_template', 'kingy_ali_handle_download_model_import_template');
add_action('admin_post_kingy_ali_download_starter_seed', 'kingy_ali_handle_download_starter_seed');

function kingy_ali_admin_menu() {
    add_menu_page(
        __('AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
        __('AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-dashboard',
        'kingy_ali_render_admin_dashboard',
        'dashicons-chart-line',
        26
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Scores Dashboard', 'kingy-ai-launch-intelligence'),
        __('Scores Dashboard', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-dashboard',
        'kingy_ali_render_admin_dashboard'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Search Analytics', 'kingy-ai-launch-intelligence'),
        __('Search Analytics', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-analytics',
        'kingy_ali_render_analytics_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('AI Launch Scorecard Leads', 'kingy-ai-launch-intelligence'),
        __('Scorecard Leads', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-scorecard-leads',
        'kingy_ali_render_scorecard_leads_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Editorial Queues', 'kingy-ai-launch-intelligence'),
        __('Editorial Queues', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-editorial-queues',
        'kingy_ali_render_editorial_queues_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Founder Submissions', 'kingy-ai-launch-intelligence'),
        __('Submissions', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-submissions',
        'kingy_ali_render_submissions_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Article Draft Generator', 'kingy-ai-launch-intelligence'),
        __('Article Draft Generator', 'kingy-ai-launch-intelligence'),
        'edit_posts',
        'kingy-ali-article-generator',
        'kingy_ali_render_article_generator_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Import CSV/JSON', 'kingy-ai-launch-intelligence'),
        __('Import CSV/JSON', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-import',
        'kingy_ali_render_import_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Setup Pages', 'kingy-ai-launch-intelligence'),
        __('Setup Pages', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-setup-pages',
        'kingy_ali_render_setup_pages_admin'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Maintenance', 'kingy-ai-launch-intelligence'),
        __('Maintenance', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-maintenance',
        'kingy_ali_render_maintenance_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Kingy Product Graph Review', 'kingy-ai-launch-intelligence'),
        __('Product Graph', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-product-graph',
        'kingy_ali_render_product_graph_review_page'
    );

    add_submenu_page(
        'kingy-ali-dashboard',
        __('Settings', 'kingy-ai-launch-intelligence'),
        __('Settings', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-settings',
        'kingy_ali_render_settings_page'
    );
}

function kingy_ali_register_settings() {
    register_setting(
        'kingy_ali_settings',
        'kingy_ali_contact_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'kingy_ali_sanitize_contact_url',
            'default' => '',
        )
    );

    register_setting(
        'kingy_ali_settings',
        'kingy_ali_client_examples_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'kingy_ali_sanitize_contact_url',
            'default' => '',
        )
    );
}

function kingy_ali_sanitize_contact_url($value) {
    return function_exists('kingy_ali_sanitize_public_cta_url')
        ? kingy_ali_sanitize_public_cta_url($value)
        : '';
}

function kingy_ali_render_admin_dashboard() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $dashboard_stats = kingy_ali_admin_dashboard_stats();
    $high_youtube = $dashboard_stats['high_youtube'];
    $high_sponsor = $dashboard_stats['high_sponsor'];
    $missing_pricing = $dashboard_stats['missing_pricing'];
    $needs_updates = $dashboard_stats['needs_updates'];
    $founder_submissions = $dashboard_stats['founder_submissions'];
    $visibility_leads = $dashboard_stats['visibility_leads'];
    $scorecard_leads = $dashboard_stats['scorecard_leads'];
    $sponsor_roi_leads = $dashboard_stats['sponsor_roi_leads'];
    $tools_missing_demo = $dashboard_stats['tools_missing_demo'];
    $companies_missing_contact = $dashboard_stats['companies_missing_contact'];
    $coverage_companies_needing_follow_up = $dashboard_stats['coverage_companies_needing_follow_up'];
    $submit_page_status = $dashboard_stats['submit_page_status'];
    $visibility_page_status = $dashboard_stats['visibility_page_status'];
    $scorecard_page_status = $dashboard_stats['scorecard_page_status'];

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></h1>
        <div class="kingy-ali-admin-actions">
            <?php kingy_ali_admin_quick_link(__('All Launches', 'kingy-ai-launch-intelligence'), admin_url('edit.php?post_type=kingy_ai_launch'), __('Review structured launch records', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_quick_link(__('Add New Launch', 'kingy-ai-launch-intelligence'), admin_url('post-new.php?post_type=kingy_ai_launch'), __('Create a Launch Radar record', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_quick_link(__('Tools', 'kingy-ai-launch-intelligence'), admin_url('edit.php?post_type=kingy_ai_tool'), __('Maintain permanent tool profiles', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_quick_link(__('Companies', 'kingy-ai-launch-intelligence'), admin_url('edit.php?post_type=kingy_ai_company'), __('Review company and founder profiles', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_quick_link(__('Submissions', 'kingy-ai-launch-intelligence'), admin_url('admin.php?page=kingy-ali-submissions'), __('Triage founder-submitted launches', 'kingy-ai-launch-intelligence')); ?>
            <?php if (current_user_can('manage_options')) : ?>
                <?php kingy_ali_admin_quick_link(__('Scorecard Leads', 'kingy-ai-launch-intelligence'), admin_url('admin.php?page=kingy-ali-scorecard-leads'), __('Review AI Launch Scorecard requests', 'kingy-ai-launch-intelligence')); ?>
            <?php endif; ?>
            <?php kingy_ali_admin_quick_link(__('Setup Pages', 'kingy-ai-launch-intelligence'), admin_url('admin.php?page=kingy-ali-setup-pages'), __('Create or repair MVP public pages', 'kingy-ai-launch-intelligence')); ?>
        </div>
        <div class="kingy-ali-admin-cards">
            <?php kingy_ali_admin_stat_card(__('Highest YouTube-score launches', 'kingy-ai-launch-intelligence'), $high_youtube); ?>
            <?php kingy_ali_admin_stat_card(__('Highest internal partner-fit launches', 'kingy-ai-launch-intelligence'), $high_sponsor); ?>
            <?php kingy_ali_admin_stat_card(__('Founder submissions', 'kingy-ai-launch-intelligence'), $founder_submissions); ?>
            <?php kingy_ali_admin_stat_card(__('Visibility score leads this week', 'kingy-ai-launch-intelligence'), $visibility_leads); ?>
            <?php kingy_ali_admin_stat_card(__('AI Launch Scorecard leads this week', 'kingy-ai-launch-intelligence'), $scorecard_leads); ?>
            <?php kingy_ali_admin_stat_card(__('Creator campaign ROI leads this week', 'kingy-ai-launch-intelligence'), $sponsor_roi_leads); ?>
            <?php kingy_ali_admin_stat_card(__('Submission form page', 'kingy-ai-launch-intelligence'), $submit_page_status['ready'] ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs setup', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_stat_card(__('Visibility calculator page', 'kingy-ai-launch-intelligence'), $visibility_page_status['ready'] ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs setup', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_stat_card(__('AI Launch Scorecard page', 'kingy-ai-launch-intelligence'), $scorecard_page_status['ready'] ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs setup', 'kingy-ai-launch-intelligence')); ?>
            <?php kingy_ali_admin_stat_card(__('Tools missing pricing', 'kingy-ai-launch-intelligence'), $missing_pricing); ?>
            <?php kingy_ali_admin_stat_card(__('Tools missing demos', 'kingy-ai-launch-intelligence'), $tools_missing_demo); ?>
            <?php kingy_ali_admin_stat_card(__('Companies missing contact URLs', 'kingy-ai-launch-intelligence'), $companies_missing_contact); ?>
            <?php kingy_ali_admin_stat_card(__('Coverage company follow-ups', 'kingy-ai-launch-intelligence'), $coverage_companies_needing_follow_up); ?>
            <?php kingy_ali_admin_stat_card(__('Launches needing updates', 'kingy-ai-launch-intelligence'), $needs_updates); ?>
        </div>
        <?php kingy_ali_render_admin_score_shortlists(); ?>
        <?php kingy_ali_render_admin_data_quality_queues(); ?>
        <?php kingy_ali_render_admin_dashboard_insights(); ?>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('post-new.php?post_type=kingy_ai_launch')); ?>"><?php esc_html_e('Add New Launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=kingy-ali-editorial-queues')); ?>"><?php esc_html_e('Open Editorial Queues', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=kingy-ali-analytics')); ?>"><?php esc_html_e('Review Search Analytics', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=kingy-ali-setup-pages')); ?>"><?php esc_html_e('Setup Public Pages', 'kingy-ai-launch-intelligence'); ?></a>
        </p>
    </div>
    <?php
}

function kingy_ali_admin_dashboard_stats() {
    $cache_key = 'kingy_ali_admin_dashboard_stats_' . (defined('KINGY_ALI_VERSION') ? KINGY_ALI_VERSION : '1');
    $cached = get_transient($cache_key);
    if (is_array($cached) && isset($cached['high_youtube'])) {
        return $cached;
    }

    $recommended_pages = kingy_ali_recommended_pages();
    $stats = array(
        'high_youtube' => kingy_ali_admin_launch_query_count(
            array(
                array(
                    'key' => kingy_ali_meta_key('youtube_score'),
                    'value' => 7,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            )
        ),
        'high_sponsor' => kingy_ali_admin_launch_query_count(
            array(
                array(
                    'key' => kingy_ali_meta_key('sponsor_fit_score_internal'),
                    'value' => 7,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            )
        ),
        'missing_pricing' => kingy_ali_admin_tool_missing_pricing_count(),
        'needs_updates' => count(kingy_ali_launches_needing_verification_update_ids()),
        'founder_submissions' => kingy_ali_admin_founder_submission_count(),
        'visibility_leads' => kingy_ali_admin_analytics_event_count('visibility_score_lead', 7),
        'scorecard_leads' => kingy_ali_admin_analytics_event_count('ai_launch_scorecard_lead', 7),
        'sponsor_roi_leads' => kingy_ali_admin_analytics_event_count('sponsor_roi_lead', 7),
        'tools_missing_demo' => count(kingy_ali_admin_tool_ids_missing_meta('demo_url', 0)),
        'companies_missing_contact' => count(kingy_ali_admin_company_ids_missing_meta('contact_url', 0)),
        'coverage_companies_needing_follow_up' => count(kingy_ali_admin_company_ids_needing_coverage_follow_up(50)),
        'submit_page_status' => kingy_ali_recommended_page_status($recommended_pages['submit']),
        'visibility_page_status' => kingy_ali_recommended_page_status($recommended_pages['visibility_score']),
        'scorecard_page_status' => kingy_ali_recommended_page_status($recommended_pages['ai_launch_scorecard']),
    );

    set_transient($cache_key, $stats, 10 * MINUTE_IN_SECONDS);
    return $stats;
}

function kingy_ali_admin_launch_query_count($meta_query) {
    return kingy_ali_admin_post_query_count('kingy_ai_launch', $meta_query);
}

function kingy_ali_admin_post_query_count($post_type, $meta_query) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
        )
    );

    return (int) $query->found_posts;
}

function kingy_ali_admin_top_scored_launches($score_key, $limit = 5, $minimum_score = 0) {
    $score_key = sanitize_key($score_key);
    $allowed_score_keys = array('kingy_launch_score', 'demo_quality_score', 'youtube_score', 'seo_score', 'sponsor_fit_score_internal');
    if (!in_array($score_key, $allowed_score_keys, true)) {
        return array();
    }

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => max(1, absint($limit)),
            'fields' => 'ids',
            'meta_key' => kingy_ali_meta_key($score_key),
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key($score_key),
                    'value' => (float) $minimum_score,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            ),
        )
    );

    return is_array($query->posts) ? array_map('absint', $query->posts) : array();
}

function kingy_ali_admin_tool_ids_missing_meta($meta_key, $limit = 5) {
    $meta_key = sanitize_key($meta_key);
    if (!in_array($meta_key, array('pricing', 'demo_url'), true)) {
        return array();
    }

    if ($meta_key === 'pricing') {
        return kingy_ali_admin_tool_ids_missing_pricing($limit);
    }

    return kingy_ali_admin_post_ids_missing_valid_url_meta('kingy_ai_tool', $meta_key, $limit);
}

function kingy_ali_admin_tool_missing_pricing_count() {
    return count(kingy_ali_admin_tool_ids_missing_pricing(0));
}

function kingy_ali_admin_tool_ids_missing_pricing($limit = 5) {
    $limit = absint($limit);
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_tool',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        )
    );

    $ids = array();
    foreach ($query->posts as $post_id) {
        $post_id = absint($post_id);
        $pricing = kingy_ali_get_meta($post_id, 'pricing');
        $is_ready = function_exists('kingy_ali_pricing_is_indexable') ? kingy_ali_pricing_is_indexable($pricing) : trim((string) $pricing) !== '';
        if ($is_ready) {
            continue;
        }

        $ids[] = $post_id;
        if ($limit && count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_admin_company_ids_missing_meta($meta_key, $limit = 5) {
    $meta_key = sanitize_key($meta_key);
    if (!in_array($meta_key, array('contact_url'), true)) {
        return array();
    }

    return kingy_ali_admin_post_ids_missing_valid_url_meta('kingy_ai_company', $meta_key, $limit);
}

function kingy_ali_admin_post_ids_missing_valid_url_meta($post_type, $meta_key, $limit = 5) {
    $query = new WP_Query(
        array(
            'post_type' => sanitize_key($post_type),
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        )
    );

    $ids = array();
    $limit = absint($limit);
    $meta_key = sanitize_key($meta_key);
    foreach ((array) $query->posts as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id || kingy_ali_admin_url_meta_is_valid($post_id, $meta_key)) {
            continue;
        }

        $ids[] = $post_id;
        if ($limit && count($ids) >= $limit) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_admin_url_meta_is_valid($post_id, $meta_key) {
    if (function_exists('kingy_ali_public_meta_url_is_valid')) {
        return kingy_ali_public_meta_url_is_valid($post_id, $meta_key);
    }

    return kingy_ali_admin_public_url_value(kingy_ali_get_meta($post_id, $meta_key)) !== '';
}

function kingy_ali_admin_public_url_value($url) {
    if (function_exists('kingy_ali_public_url_value')) {
        return kingy_ali_public_url_value($url);
    }

    if (function_exists('kingy_ali_schema_url')) {
        return kingy_ali_schema_url($url);
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url);
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

function kingy_ali_admin_post_ids_missing_meta($post_type, $meta_key, $limit = 5) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => max(1, absint($limit)),
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => kingy_ali_admin_missing_meta_query($meta_key),
        )
    );

    return is_array($query->posts) ? array_map('absint', $query->posts) : array();
}

function kingy_ali_admin_missing_meta_query($meta_key) {
    $meta_key = sanitize_key($meta_key);
    return array(
        'relation' => 'OR',
        array(
            'key' => kingy_ali_meta_key($meta_key),
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key' => kingy_ali_meta_key($meta_key),
            'value' => '',
            'compare' => '=',
        ),
    );
}

function kingy_ali_admin_company_ids_needing_coverage_follow_up($limit = 5) {
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_company',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 100,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC',
        )
    );

    $ids = array();
    foreach ((array) $query->posts as $company_id) {
        $company_id = absint($company_id);
        $outreach_status = kingy_ali_get_meta($company_id, 'outreach_status');
        if (
            in_array($outreach_status, array('contacted', 'replied', 'not_fit'), true)
            || !kingy_ali_company_has_creator_coverage_signal($company_id)
        ) {
            continue;
        }

        $ids[] = $company_id;
        if (count($ids) >= max(1, absint($limit))) {
            break;
        }
    }

    return $ids;
}

function kingy_ali_admin_stat_card($label, $value) {
    echo '<div class="kingy-ali-admin-card"><strong>' . esc_html((string) $value) . '</strong><span>' . esc_html($label) . '</span></div>';
}

function kingy_ali_admin_quick_link($label, $url, $description) {
    echo '<a class="kingy-ali-admin-action" href="' . esc_url($url) . '"><strong>' . esc_html($label) . '</strong><span>' . esc_html($description) . '</span></a>';
}

function kingy_ali_render_admin_score_shortlists() {
    echo '<h2>' . esc_html__('Scoring shortlists', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<div class="kingy-ali-admin-insights">';
    kingy_ali_render_dashboard_launch_score_panel(
        __('Highest YouTube-score launches', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_top_scored_launches('youtube_score', 5),
        'youtube_score'
    );
    kingy_ali_render_dashboard_launch_score_panel(
        __('Highest internal partner-fit launches', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_top_scored_launches('sponsor_fit_score_internal', 5),
        'sponsor_fit_score_internal'
    );
    echo '</div>';
}

function kingy_ali_render_admin_data_quality_queues() {
    echo '<h2>' . esc_html__('Data quality queues', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<div class="kingy-ali-admin-insights">';
    kingy_ali_render_dashboard_launch_update_panel(
        __('Launches needing updates', 'kingy-ai-launch-intelligence'),
        kingy_ali_launches_needing_verification_update_ids(5)
    );
    kingy_ali_render_dashboard_tool_quality_panel(
        __('Tools missing pricing', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_tool_ids_missing_meta('pricing', 5),
        'pricing'
    );
    kingy_ali_render_dashboard_tool_quality_panel(
        __('Tools missing official demos', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_tool_ids_missing_meta('demo_url', 5),
        'demo_url'
    );
    kingy_ali_render_dashboard_company_quality_panel(
        __('Companies missing contact URLs', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_company_ids_missing_meta('contact_url', 5),
        'contact_url'
    );
    kingy_ali_render_dashboard_company_quality_panel(
        __('Creator-coverage companies needing follow-up', 'kingy-ai-launch-intelligence'),
        kingy_ali_admin_company_ids_needing_coverage_follow_up(5),
        'coverage_follow_up'
    );
    kingy_ali_render_dashboard_model_quality_panel();
    echo '</div>';
}

function kingy_ali_render_admin_dashboard_insights() {
    $recent_events = kingy_ali_recent_events(7);

    echo '<h2>' . esc_html__('Demand and engagement signals', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<div class="kingy-ali-admin-insights">';
    kingy_ali_render_dashboard_search_panel(__('Top searches', 'kingy-ai-launch-intelligence'), kingy_ali_admin_top_searches(5), true);
    kingy_ali_render_dashboard_search_panel(__('Zero-result searches', 'kingy-ai-launch-intelligence'), kingy_ali_admin_zero_result_searches(5), false);
    kingy_ali_render_dashboard_count_panel(__('High-intent searches', 'kingy-ai-launch-intelligence'), kingy_ali_high_intent_searches($recent_events), __('Search query', 'kingy-ai-launch-intelligence'));
    kingy_ali_render_dashboard_count_panel(__('Popular category filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'category'), __('Category', 'kingy-ai-launch-intelligence'), 'kingy_launch_category');
    kingy_ali_render_dashboard_count_panel(__('Popular launch type filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'launch_type'), __('Launch type', 'kingy-ai-launch-intelligence'), 'kingy_launch_type');
    kingy_ali_render_dashboard_count_panel(__('Popular audience filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'audience'), __('Audience', 'kingy-ai-launch-intelligence'), 'kingy_audience');
    kingy_ali_render_dashboard_filter_combination_panel(__('Search/filter combinations', 'kingy-ai-launch-intelligence'), kingy_ali_admin_filter_combinations($recent_events, 5));
    kingy_ali_render_dashboard_clicked_object_panel(__('Clicked launches, tools, and companies', 'kingy-ai-launch-intelligence'), kingy_ali_admin_clicked_objects(5));
    kingy_ali_render_dashboard_count_panel(__('Clicked category paths', 'kingy-ai-launch-intelligence'), kingy_ali_admin_category_path_clicks(5), __('Path', 'kingy-ai-launch-intelligence'));
    kingy_ali_render_dashboard_event_type_panel(__('Conversion CTA clicks', 'kingy-ai-launch-intelligence'), kingy_ali_admin_conversion_click_totals(7));
    kingy_ali_render_dashboard_count_panel(__('Founder submission categories', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'category', 'founder_submission'), __('Category', 'kingy-ai-launch-intelligence'), 'kingy_launch_category');
    kingy_ali_render_dashboard_count_panel(__('Founder submission launch types', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'launch_type', 'founder_submission'), __('Launch type', 'kingy-ai-launch-intelligence'), 'kingy_launch_type');
    echo '</div>';
}

function kingy_ali_render_dashboard_launch_score_panel($title, $post_ids, $score_key) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Launch', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Score', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Category', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Date', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Status', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($post_ids) {
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            $title_text = get_the_title($post_id);
            $edit_link = get_edit_post_link($post_id);
            echo '<tr>';
            echo '<td>';
            if ($edit_link) {
                echo '<a href="' . esc_url($edit_link) . '">' . esc_html($title_text) . '</a>';
            } else {
                echo esc_html($title_text);
            }
            $company = kingy_ali_get_meta($post_id, 'company');
            if ($company !== '') {
                echo '<br><small>' . esc_html($company) . '</small>';
            }
            echo '</td>';
            echo '<td>' . esc_html(kingy_ali_format_score(kingy_ali_get_meta($post_id, $score_key))) . '</td>';
            echo '<td>' . esc_html(kingy_ali_admin_primary_term_label($post_id, 'kingy_launch_category')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_admin_date_label(kingy_ali_get_meta($post_id, 'launch_date'))) . '</td>';
            echo '<td>' . esc_html(kingy_ali_admin_post_status_label($post_id)) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">' . esc_html__('No scored launches yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_launch_update_panel($title, $post_ids) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Launch', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Verification', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Freshness', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Sources', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($post_ids) {
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            echo '<tr>';
            echo '<td>' . kingy_ali_admin_post_edit_link($post_id) . '<br><small>' . esc_html(kingy_ali_admin_date_label(kingy_ali_get_meta($post_id, 'launch_date'))) . '</small></td>';
            echo '<td>' . esc_html(kingy_ali_verification_label($post_id)) . '</td>';
            echo '<td>' . esc_html(kingy_ali_verification_freshness_label($post_id)) . '</td>';
            echo '<td>' . esc_html((string) kingy_ali_source_count($post_id)) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">' . esc_html__('No stale launch records in the current queue.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_tool_quality_panel($title, $post_ids, $missing_key) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Tool', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Company', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Category', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Latest launch', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($post_ids) {
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            $latest_launch_id = kingy_ali_admin_related_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
            echo '<tr>';
            echo '<td>' . kingy_ali_admin_post_edit_link($post_id) . '<br><small>' . esc_html(kingy_ali_admin_missing_field_label($missing_key)) . '</small></td>';
            echo '<td>' . esc_html(kingy_ali_admin_meta_label($post_id, 'company')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_admin_primary_term_label($post_id, 'kingy_launch_category')) . '</td>';
            echo '<td>' . (kingy_ali_related_post_is_valid($latest_launch_id, 'kingy_ai_launch') ? kingy_ali_admin_post_edit_link($latest_launch_id) : esc_html__('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">' . esc_html__('No matching tool records in this queue.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_company_quality_panel($title, $post_ids, $reason_key) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Company', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Graph', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Contact', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Outreach', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($post_ids) {
        foreach ($post_ids as $post_id) {
            $post_id = absint($post_id);
            $launch_count = kingy_ali_company_related_count($post_id, 'kingy_ai_launch');
            $tool_count = kingy_ali_company_related_count($post_id, 'kingy_ai_tool');

            echo '<tr>';
            echo '<td>' . kingy_ali_admin_post_edit_link($post_id) . '<br><small>' . esc_html(kingy_ali_admin_company_quality_reason_label($reason_key)) . '</small></td>';
            echo '<td>' . esc_html(sprintf(__('%1$d launches / %2$d tools', 'kingy-ai-launch-intelligence'), $launch_count, $tool_count)) . '</td>';
            echo '<td>' . kingy_ali_admin_company_contact_link($post_id) . '</td>';
            echo '<td>' . esc_html(kingy_ali_admin_meta_label($post_id, 'outreach_status')) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">' . esc_html__('No matching company records in this queue.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_model_quality_panel() {
    $report = kingy_ali_model_readiness_report_data(5);

    echo '<section class="kingy-ali-admin-insight kingy-ali-admin-insight--wide">';
    echo '<h3>' . esc_html__('AI model readiness', 'kingy-ai-launch-intelligence') . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Queue', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Count', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Sample records', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach (kingy_ali_model_readiness_queue_definitions() as $queue_key => $queue) {
        $row = isset($report['queues'][$queue_key]) ? $report['queues'][$queue_key] : array('count' => 0, 'sample_ids' => array());
        echo '<tr>';
        echo '<td><strong>' . esc_html($queue['label']) . '</strong><br><small>' . esc_html($queue['description']) . '</small></td>';
        echo '<td>' . esc_html(number_format_i18n((int) $row['count'])) . '</td>';
        echo '<td>' . kingy_ali_model_readiness_sample_links($row['sample_ids']) . '</td>';
        echo '</tr>';
    }

    if (!$report['total']) {
        echo '<tr><td colspan="3">' . esc_html__('No AI model profiles have been imported yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_model_readiness_report() {
    $report = kingy_ali_model_readiness_report_data(5);

    echo '<h2>' . esc_html__('AI Model Profile Readiness', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<p>' . esc_html__('This report mirrors the model noindex gates and comparison readiness checks so editors can import safely, enrich records, and publish only source-backed profiles.', 'kingy-ai-launch-intelligence') . '</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Readiness queue', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Records', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Sample edit links', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach (kingy_ali_model_readiness_queue_definitions() as $queue_key => $queue) {
        $row = isset($report['queues'][$queue_key]) ? $report['queues'][$queue_key] : array('count' => 0, 'sample_ids' => array());
        echo '<tr>';
        echo '<td><strong>' . esc_html($queue['label']) . '</strong><br><small>' . esc_html($queue['description']) . '</small></td>';
        echo '<td>' . esc_html(number_format_i18n((int) $row['count'])) . '</td>';
        echo '<td>' . kingy_ali_model_readiness_sample_links($row['sample_ids']) . '</td>';
        echo '</tr>';
    }

    if (!$report['total']) {
        echo '<tr><td colspan="3">' . esc_html__('No AI model profiles have been imported yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';

    kingy_ali_render_model_source_review_report();
}

function kingy_ali_model_readiness_queue_definitions() {
    return array(
        'selectable_for_comparison' => array(
            'label' => __('Selectable for comparison', 'kingy-ai-launch-intelligence'),
            'description' => __('Published, index-ready, and clear of comparison-readiness warnings.', 'kingy-ai-launch-intelligence'),
        ),
        'index_ready' => array(
            'label' => __('Index-ready', 'kingy-ai-launch-intelligence'),
            'description' => __('Passes the current model noindex gate. Publish only after editorial approval.', 'kingy-ai-launch-intelligence'),
        ),
        'noindexed' => array(
            'label' => __('Noindexed by readiness gate', 'kingy-ai-launch-intelligence'),
            'description' => __('Missing one or more required trust fields, or explicitly marked noindex/rumored.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_sources' => array(
            'label' => __('Missing sources', 'kingy-ai-launch-intelligence'),
            'description' => __('Needs at least one public source link and an official source URL.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_last_verified' => array(
            'label' => __('Missing last verified', 'kingy-ai-launch-intelligence'),
            'description' => __('Needs a dated human verification before public profile or comparison review.', 'kingy-ai-launch-intelligence'),
        ),
        'soon_stale_verification' => array(
            'label' => __('Verification soon stale', 'kingy-ai-launch-intelligence'),
            'description' => __('Last verified date is approaching the freshness window and should be reviewed soon.', 'kingy-ai-launch-intelligence'),
        ),
        'stale_verification' => array(
            'label' => __('Stale verification', 'kingy-ai-launch-intelligence'),
            'description' => __('Last verified date is outside the freshness window; refresh sources before relying on comparisons.', 'kingy-ai-launch-intelligence'),
        ),
        'rumored' => array(
            'label' => __('Rumored', 'kingy-ai-launch-intelligence'),
            'description' => __('Marked rumored by verification status or model status taxonomy; forced noindex.', 'kingy-ai-launch-intelligence'),
        ),
        'outdated' => array(
            'label' => __('Outdated or stale', 'kingy-ai-launch-intelligence'),
            'description' => __('Marked outdated or missing/stale last verified date.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_benchmark_caveat' => array(
            'label' => __('Missing benchmark caveat', 'kingy-ai-launch-intelligence'),
            'description' => __('Needs the benchmark caveat used on model and comparison surfaces.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_pricing_source' => array(
            'label' => __('Pricing needs source review', 'kingy-ai-launch-intelligence'),
            'description' => __('Pricing or API pricing is entered without a clear official pricing/API source.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_context_source' => array(
            'label' => __('Context/output needs source review', 'kingy-ai-launch-intelligence'),
            'description' => __('Context window or output limit is entered without a clear official limit source.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_license_source' => array(
            'label' => __('License/weights need source review', 'kingy-ai-launch-intelligence'),
            'description' => __('Open-weight, open-source, or custom license claims need license, weights, or model-card sources.', 'kingy-ai-launch-intelligence'),
        ),
        'missing_critical_fields' => array(
            'label' => __('Missing critical comparison fields', 'kingy-ai-launch-intelligence'),
            'description' => __('The record has too little verified comparison detail for cautious editorial guidance.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_model_readiness_report_data($sample_limit = 5) {
    $sample_limit = max(1, absint($sample_limit));
    $queues = array();

    foreach (kingy_ali_model_readiness_queue_definitions() as $queue_key => $queue) {
        $queues[$queue_key] = array(
            'count' => 0,
            'sample_ids' => array(),
        );
    }

    $model_ids = kingy_ali_model_readiness_post_ids();
    foreach ($model_ids as $model_id) {
        $flags = kingy_ali_model_readiness_flags($model_id);
        foreach ($flags as $queue_key => $matches) {
            if (!$matches || !isset($queues[$queue_key])) {
                continue;
            }

            $queues[$queue_key]['count']++;
            if (count($queues[$queue_key]['sample_ids']) < $sample_limit) {
                $queues[$queue_key]['sample_ids'][] = absint($model_id);
            }
        }
    }

    return array(
        'total' => count($model_ids),
        'queues' => $queues,
    );
}

function kingy_ali_model_readiness_post_ids() {
    return get_posts(
        array(
            'post_type' => 'kingy_ai_model',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        )
    );
}

function kingy_ali_model_readiness_flags($post_id) {
    $post_id = absint($post_id);
    $verification_status = sanitize_key(kingy_ali_get_meta($post_id, 'verification_status'));
    $should_noindex = function_exists('kingy_ali_profile_should_noindex') ? kingy_ali_profile_should_noindex($post_id) : true;
    $last_verified_missing = kingy_ali_model_readiness_last_verified_missing($post_id);
    $last_verified_stale = !$last_verified_missing && function_exists('kingy_ali_last_verified_is_stale') && kingy_ali_last_verified_is_stale($post_id);
    $is_rumored = $verification_status === 'rumored'
        || (function_exists('kingy_ali_model_has_term_slug') && kingy_ali_model_has_term_slug($post_id, 'model_status', 'rumored'));
    $is_outdated = $verification_status === 'outdated'
        || $last_verified_missing
        || $last_verified_stale;
    $source_review = kingy_ali_model_source_review_items($post_id);
    $source_flags = array();
    foreach ($source_review as $item) {
        if (!empty($item['queue'])) {
            $source_flags[$item['queue']] = empty($item['complete']);
        }
    }
    $comparison_issues = function_exists('kingy_ali_model_comparison_readiness_issues')
        ? kingy_ali_model_comparison_readiness_issues($post_id)
        : array();

    return array_merge(
        array(
            'selectable_for_comparison' => function_exists('kingy_ali_model_is_comparison_selectable') && kingy_ali_model_is_comparison_selectable($post_id) && !$comparison_issues,
            'index_ready' => !$should_noindex,
            'noindexed' => $should_noindex,
            'missing_sources' => kingy_ali_model_readiness_missing_sources($post_id),
            'missing_last_verified' => $last_verified_missing,
            'soon_stale_verification' => kingy_ali_model_readiness_last_verified_soon_stale($post_id),
            'stale_verification' => $last_verified_stale,
            'rumored' => $is_rumored,
            'outdated' => $is_outdated,
            'missing_benchmark_caveat' => trim((string) kingy_ali_get_meta($post_id, 'benchmark_caveat')) === '',
            'missing_critical_fields' => function_exists('kingy_ali_model_comparison_signal_count') && kingy_ali_model_comparison_signal_count($post_id) < 8,
        ),
        $source_flags
    );
}

function kingy_ali_model_readiness_missing_sources($post_id) {
    $source_count = function_exists('kingy_ali_source_count') ? kingy_ali_source_count($post_id) : 0;
    $has_official_source = function_exists('kingy_ali_model_has_official_indexable_source')
        ? kingy_ali_model_has_official_indexable_source($post_id)
        : kingy_ali_model_readiness_has_official_source_fallback($post_id);

    return $source_count < 1 || !$has_official_source;
}

function kingy_ali_model_readiness_has_official_source_fallback($post_id) {
    foreach (kingy_ali_model_import_official_source_columns() as $key) {
        $url = kingy_ali_get_meta($post_id, $key);
        if (is_scalar($url) && kingy_ali_import_is_absolute_http_url((string) $url)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_render_model_source_review_report() {
    $model_ids = array_slice(kingy_ali_model_readiness_post_ids(), 0, 50);

    echo '<h3>' . esc_html__('Model Source Review Checklist', 'kingy-ai-launch-intelligence') . '</h3>';
    echo '<p>' . esc_html__('Use this maintenance view to spot records that may need source refreshes, cautious copy, or comparison holdbacks. Unknown fields are acceptable when they are not source-backed.', 'kingy-ai-launch-intelligence') . '</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Model', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Verification', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Source review', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Comparison', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if (!$model_ids) {
        echo '<tr><td colspan="4">' . esc_html__('No AI model profiles have been imported yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    foreach ($model_ids as $post_id) {
        $items = kingy_ali_model_source_review_items($post_id);
        $missing = array_filter(
            $items,
            function ($item) {
                return empty($item['complete']);
            }
        );
        $issues = function_exists('kingy_ali_model_comparison_readiness_issues')
            ? kingy_ali_model_comparison_readiness_issues($post_id)
            : array();
        $selectable = function_exists('kingy_ali_model_is_comparison_selectable') && kingy_ali_model_is_comparison_selectable($post_id) && !$issues;
        $last_verified = trim((string) kingy_ali_get_meta($post_id, 'last_verified'));
        $verification_status = trim((string) kingy_ali_get_meta($post_id, 'verification_status'));
        $verification_label = $verification_status !== '' ? $verification_status : __('Missing status', 'kingy-ai-launch-intelligence');
        $verification_label .= ' - ' . ($last_verified !== '' ? $last_verified : __('missing date', 'kingy-ai-launch-intelligence'));

        if (kingy_ali_model_readiness_last_verified_soon_stale($post_id)) {
            $verification_label .= ' - ' . __('soon stale', 'kingy-ai-launch-intelligence');
        } elseif (function_exists('kingy_ali_last_verified_is_stale') && kingy_ali_last_verified_is_stale($post_id)) {
            $verification_label .= ' - ' . __('stale', 'kingy-ai-launch-intelligence');
        }

        echo '<tr>';
        echo '<td><strong>' . kingy_ali_admin_post_edit_link($post_id) . '</strong><br><small>' . esc_html(get_post_status($post_id)) . '</small></td>';
        echo '<td>' . esc_html($verification_label) . '</td>';
        echo '<td>' . kingy_ali_model_source_review_summary_html($items, $missing) . '</td>';
        echo '<td>';
        echo $selectable
            ? esc_html__('Selectable based on currently verified fields.', 'kingy-ai-launch-intelligence')
            : esc_html__('Held back from comparison selection until review items are resolved.', 'kingy-ai-launch-intelligence');
        if ($issues) {
            echo '<br><small>' . esc_html(implode('; ', array_slice($issues, 0, 3))) . '</small>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_model_source_review_summary_html($items, $missing) {
    if (!$missing) {
        return esc_html__('Source checklist looks complete. Unknown fields may remain unknown when not source-backed.', 'kingy-ai-launch-intelligence');
    }

    $labels = array();
    foreach (array_slice($missing, 0, 4) as $item) {
        $labels[] = isset($item['label']) ? $item['label'] : __('Review item', 'kingy-ai-launch-intelligence');
    }

    $summary = implode('; ', $labels);
    if (count($missing) > 4) {
        $summary .= '; ' . sprintf(
            _n('%d more item', '%d more items', count($missing) - 4, 'kingy-ai-launch-intelligence'),
            count($missing) - 4
        );
    }

    return esc_html($summary);
}

function kingy_ali_model_source_review_items($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_type($post_id) !== 'kingy_ai_model') {
        return array();
    }

    return array(
        kingy_ali_model_source_review_item(
            __('Official source present', 'kingy-ai-launch-intelligence'),
            !kingy_ali_model_readiness_missing_sources($post_id),
            'missing_sources'
        ),
        kingy_ali_model_source_review_item(
            __('Pricing/API source present when pricing is entered', 'kingy-ai-launch-intelligence'),
            !kingy_ali_model_readiness_missing_pricing_source($post_id),
            'missing_pricing_source'
        ),
        kingy_ali_model_source_review_item(
            __('Context/output source present when limits are entered', 'kingy-ai-launch-intelligence'),
            !kingy_ali_model_readiness_missing_context_source($post_id),
            'missing_context_source'
        ),
        kingy_ali_model_source_review_item(
            __('License/weights source present when open-weight or license claims are entered', 'kingy-ai-launch-intelligence'),
            !kingy_ali_model_readiness_missing_license_source($post_id),
            'missing_license_source'
        ),
        kingy_ali_model_source_review_item(
            __('Benchmark caveat present', 'kingy-ai-launch-intelligence'),
            trim((string) kingy_ali_get_meta($post_id, 'benchmark_caveat')) !== '',
            'missing_benchmark_caveat'
        ),
        kingy_ali_model_source_review_item(
            __('Verification status present', 'kingy-ai-launch-intelligence'),
            trim((string) kingy_ali_get_meta($post_id, 'verification_status')) !== '',
            ''
        ),
        kingy_ali_model_source_review_item(
            __('Last verified date present', 'kingy-ai-launch-intelligence'),
            !kingy_ali_model_readiness_last_verified_missing($post_id),
            'missing_last_verified'
        ),
        kingy_ali_model_source_review_item(
            __('Unknown fields allowed when not source-backed', 'kingy-ai-launch-intelligence'),
            true,
            ''
        ),
    );
}

function kingy_ali_model_source_review_item($label, $complete, $queue = '') {
    return array(
        'label' => $label,
        'complete' => (bool) $complete,
        'queue' => sanitize_key($queue),
    );
}

function kingy_ali_model_readiness_last_verified_missing($post_id) {
    return !function_exists('kingy_ali_last_verified_timestamp') || !kingy_ali_last_verified_timestamp($post_id);
}

function kingy_ali_model_readiness_last_verified_soon_stale($post_id) {
    if (!function_exists('kingy_ali_last_verified_timestamp') || !function_exists('kingy_ali_verification_stale_days')) {
        return false;
    }

    $timestamp = kingy_ali_last_verified_timestamp($post_id);
    if (!$timestamp) {
        return false;
    }

    $stale_days = max(1, absint(kingy_ali_verification_stale_days()));
    $warning_days = max(1, min(7, $stale_days));
    $age_days = floor((current_time('timestamp') - $timestamp) / DAY_IN_SECONDS);

    return $age_days >= ($stale_days - $warning_days) && $age_days < $stale_days;
}

function kingy_ali_model_readiness_missing_pricing_source($post_id) {
    if (!kingy_ali_model_readiness_has_any_meta_value($post_id, array('pricing', 'api_pricing'))) {
        return false;
    }

    return !kingy_ali_model_readiness_has_any_source_url($post_id, array('pricing_url', 'api_reference_url', 'official_docs_url', 'license_url', 'weights_url'));
}

function kingy_ali_model_readiness_missing_context_source($post_id) {
    if (!kingy_ali_model_readiness_has_any_meta_value($post_id, array('context_window', 'output_limit'))) {
        return false;
    }

    return !kingy_ali_model_readiness_has_any_source_url($post_id, array('context_source_url', 'api_reference_url', 'official_docs_url', 'model_card_url', 'system_card_url'));
}

function kingy_ali_model_readiness_missing_license_source($post_id) {
    if (!kingy_ali_model_readiness_has_license_claim($post_id)) {
        return false;
    }

    return !kingy_ali_model_readiness_has_any_source_url($post_id, array('license_url', 'weights_url', 'model_card_url', 'official_docs_url'));
}

function kingy_ali_model_readiness_has_any_meta_value($post_id, $keys) {
    foreach ($keys as $key) {
        $value = kingy_ali_get_meta($post_id, $key);
        if (is_scalar($value) && trim((string) $value) !== '') {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_readiness_has_any_source_url($post_id, $keys) {
    foreach ($keys as $key) {
        $url = kingy_ali_get_meta($post_id, $key);
        if (is_scalar($url) && kingy_ali_import_is_absolute_http_url((string) $url)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_readiness_has_license_claim($post_id) {
    foreach (array('open_weight', 'open_source') as $key) {
        $value = strtolower(trim((string) kingy_ali_get_meta($post_id, $key)));
        if (in_array($value, array('yes', 'true', '1', 'open', 'available'), true)) {
            return true;
        }
    }

    $terms = get_the_terms($post_id, 'model_license_type');
    if (!is_wp_error($terms) && $terms) {
        foreach ($terms as $term) {
            $needle = strtolower($term->slug . ' ' . $term->name);
            if (preg_match('/open|weight|apache|mit|llama|community|research|custom|non[- ]?commercial/', $needle)) {
                return true;
            }
        }
    }

    $notes = strtolower(trim((string) kingy_ali_get_meta($post_id, 'license_notes')));
    return $notes !== '' && preg_match('/open[- ]?weight|open[- ]?source|apache|mit|llama|community|research|custom|non[- ]?commercial|weights?/', $notes);
}

function kingy_ali_model_readiness_sample_links($post_ids) {
    $links = array();
    foreach ((array) $post_ids as $post_id) {
        $links[] = kingy_ali_admin_post_edit_link($post_id);
    }

    return $links ? implode(', ', $links) : esc_html__('—', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_post_edit_link($post_id) {
    $post_id = absint($post_id);
    $title = get_the_title($post_id);
    if ($title === '') {
        $title = __('Untitled record', 'kingy-ai-launch-intelligence');
    }

    $edit_link = get_edit_post_link($post_id);
    if (!$edit_link) {
        return esc_html($title);
    }

    return '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a>';
}

function kingy_ali_admin_meta_label($post_id, $meta_key) {
    $value = kingy_ali_get_meta($post_id, $meta_key);
    return $value === '' ? __('—', 'kingy-ai-launch-intelligence') : $value;
}

function kingy_ali_admin_missing_field_label($missing_key) {
    $labels = array(
        'pricing' => __('Missing pricing', 'kingy-ai-launch-intelligence'),
        'demo_url' => __('Missing official demo', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$missing_key]) ? $labels[$missing_key] : __('Missing field', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_company_quality_reason_label($reason_key) {
    $labels = array(
        'contact_url' => __('Missing contact URL', 'kingy-ai-launch-intelligence'),
        'coverage_follow_up' => __('Coverage signal needs outreach review', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$reason_key]) ? $labels[$reason_key] : __('Company quality check', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_company_contact_link($post_id) {
    $contact_url = kingy_ali_admin_public_url_value(kingy_ali_get_meta($post_id, 'contact_url'));

    if ($contact_url) {
        return '<a href="' . esc_url($contact_url) . '" target="_blank" rel="nofollow noopener">' . esc_html__('Open contact URL', 'kingy-ai-launch-intelligence') . '</a>';
    }

    $email = kingy_ali_get_meta($post_id, 'founder_contact_email');
    if ($email) {
        return '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
    }

    return esc_html__('—', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_primary_term_label($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (empty($terms) || is_wp_error($terms)) {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    $term = reset($terms);
    return isset($term->name) ? $term->name : __('—', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_date_label($date) {
    if (!$date) {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    $timestamp = strtotime((string) $date);
    if (!$timestamp) {
        return sanitize_text_field($date);
    }

    return date_i18n(get_option('date_format'), $timestamp);
}

function kingy_ali_admin_post_status_label($post_id) {
    $status = get_post_status($post_id);
    if (!$status) {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    $status_object = get_post_status_object($status);
    if ($status_object && !empty($status_object->label)) {
        return $status_object->label;
    }

    return ucfirst(str_replace(array('_', '-'), ' ', sanitize_key($status)));
}

function kingy_ali_admin_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_admin_top_searches($limit = 20, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT query_text, event_label, COUNT(*) AS total, AVG(result_count) AS avg_results
            FROM {$table}
            WHERE created_at >= %s AND event_type IN ('search', 'zero_result_search') AND query_text != ''
            GROUP BY query_text, event_label
            ORDER BY total DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_admin_zero_result_searches($limit = 20, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT query_text, event_label, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s AND event_type = 'zero_result_search' AND query_text != ''
            GROUP BY query_text, event_label
            ORDER BY total DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_admin_category_path_clicks($limit = 20, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s AND event_type = 'clicked_category_path'
            GROUP BY event_label
            ORDER BY total DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_admin_clicked_objects($limit = 20, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_type, object_id, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s AND event_type IN ('clicked_launch', 'clicked_tool', 'clicked_company') AND object_id IS NOT NULL
            GROUP BY event_type, object_id
            ORDER BY total DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_admin_filter_combinations($events, $limit = 20) {
    $rows = array();

    foreach ($events as $event) {
        if (!in_array($event->event_type, array('search', 'zero_result_search'), true)) {
            continue;
        }

        $filters = json_decode((string) $event->filters, true);
        $filters = is_array($filters) ? $filters : array();
        $summary = kingy_ali_admin_filter_summary($filters);
        if ($summary === __('No filters', 'kingy-ai-launch-intelligence') && trim((string) $event->query_text) === '') {
            continue;
        }

        $surface = sanitize_key((string) $event->event_label);
        $query = sanitize_text_field((string) $event->query_text);
        $key = strtolower($surface . '|' . $query . '|' . $summary);
        if (empty($rows[$key])) {
            $rows[$key] = array(
                'query' => $query,
                'surface' => $surface,
                'filters' => $summary,
                'total' => 0,
                'zero_results' => 0,
                'result_sum' => 0,
            );
        }

        $rows[$key]['total']++;
        $rows[$key]['result_sum'] += isset($event->result_count) ? (int) $event->result_count : 0;
        if ($event->event_type === 'zero_result_search' || (isset($event->result_count) && (int) $event->result_count === 0)) {
            $rows[$key]['zero_results']++;
        }
    }

    uasort(
        $rows,
        function ($a, $b) {
            if ($a['zero_results'] === $b['zero_results']) {
                if ($a['total'] === $b['total']) {
                    return strcasecmp($a['filters'], $b['filters']);
                }

                return $a['total'] < $b['total'] ? 1 : -1;
            }

            return $a['zero_results'] < $b['zero_results'] ? 1 : -1;
        }
    );

    return array_slice(array_values($rows), 0, max(1, absint($limit)));
}

function kingy_ali_admin_filter_summary($filters) {
    $filter_labels = array(
        'category' => array('label' => __('Category', 'kingy-ai-launch-intelligence'), 'taxonomy' => 'kingy_launch_category'),
        'launch_type' => array('label' => __('Launch type', 'kingy-ai-launch-intelligence'), 'taxonomy' => 'kingy_launch_type'),
        'audience' => array('label' => __('Audience', 'kingy-ai-launch-intelligence'), 'taxonomy' => 'kingy_audience'),
        'attribute' => array('label' => __('Attribute', 'kingy-ai-launch-intelligence'), 'taxonomy' => 'kingy_tool_attribute'),
        'period' => array('label' => __('Period', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
        'collection' => array('label' => __('Collection', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
        'free_plan' => array('label' => __('Free plan', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
        'api_available' => array('label' => __('API', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
        'open_source_or_open_weight' => array('label' => __('Open source/weight', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
        'video_demo' => array('label' => __('Demo', 'kingy-ai-launch-intelligence'), 'taxonomy' => ''),
    );
    $parts = array();

    foreach ($filter_labels as $key => $config) {
        $value = kingy_ali_admin_event_filter_value($filters, $key);
        if ($value === '') {
            continue;
        }

        $parts[] = sprintf(
            '%1$s: %2$s',
            $config['label'],
            kingy_ali_admin_filter_value_label($value, $config['taxonomy'])
        );
    }

    return $parts ? implode('; ', $parts) : __('No filters', 'kingy-ai-launch-intelligence');
}

function kingy_ali_admin_filter_value_label($value, $taxonomy = '') {
    if (!is_scalar($value)) {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    $value = sanitize_text_field((string) $value);
    if ($value === '') {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    if ($taxonomy) {
        $term = get_term_by('slug', $value, $taxonomy);
        if ($term && !is_wp_error($term)) {
            return $term->name;
        }
    }

    $labels = array(
        'yes' => __('Yes', 'kingy-ai-launch-intelligence'),
        'no' => __('No', 'kingy-ai-launch-intelligence'),
        'today' => __('Today', 'kingy-ai-launch-intelligence'),
        'week' => __('This week', 'kingy-ai-launch-intelligence'),
        'youtube_worthy' => __('YouTube-worthy launches', 'kingy-ai-launch-intelligence'),
        'creator_coverage' => __('Creator coverage shortlist', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$value]) ? $labels[$value] : ucfirst(str_replace(array('_', '-'), ' ', $value));
}

function kingy_ali_conversion_click_event_types() {
    return array(
        'clicked_submit_cta',
        'clicked_sponsorship_cta',
        'clicked_roi_calculator',
        'clicked_visibility_score_cta',
        'clicked_contact_cta',
        'clicked_client_examples_cta',
    );
}

function kingy_ali_admin_conversion_click_totals($days = 7) {
    $totals = array_fill_keys(kingy_ali_conversion_click_event_types(), 0);
    foreach (kingy_ali_recent_events($days) as $event) {
        $event_type = sanitize_key($event->event_type);
        if (array_key_exists($event_type, $totals)) {
            $totals[$event_type]++;
        }
    }

    return $totals;
}

function kingy_ali_admin_click_event_rows($limit = 20, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_type, event_label, MAX(filters) AS filters, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s
                AND event_type LIKE 'clicked_%%'
                AND event_type NOT IN ('clicked_launch', 'clicked_tool', 'clicked_company')
            GROUP BY event_type, event_label
            ORDER BY total DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_render_dashboard_search_panel($title, $rows, $include_average = true) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Query', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    if ($include_average) {
        echo '<th>' . esc_html__('Avg results', 'kingy-ai-launch-intelligence') . '</th>';
    }
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->query_text) . '</td>';
            echo '<td>' . esc_html(kingy_ali_search_surface_label($row->event_label)) . '</td>';
            echo '<td>' . esc_html((string) $row->total) . '</td>';
            if ($include_average) {
                echo '<td>' . esc_html(number_format_i18n((float) $row->avg_results, 1)) . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . esc_attr($include_average ? '4' : '3') . '">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_event_type_panel($title, $counts) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Signal', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($counts as $event_type => $total) {
        echo '<tr><td>' . esc_html(kingy_ali_event_type_label($event_type)) . '</td><td>' . esc_html((string) $total) . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_count_panel($title, $rows, $label_heading, $taxonomy = '') {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html($label_heading) . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach (array_slice($rows, 0, 5, true) as $key => $row) {
            if (is_object($row)) {
                $label = isset($row->event_label) ? (string) $row->event_label : '';
                $total = isset($row->total) ? (int) $row->total : 0;
            } else {
                $label = (string) $key;
                $total = (int) $row;
                if ($taxonomy) {
                    $term = get_term_by('slug', $label, $taxonomy);
                    if ($term && !is_wp_error($term)) {
                        $label = $term->name;
                    }
                }
            }

            echo '<tr><td>' . esc_html($label ? $label : __('—', 'kingy-ai-launch-intelligence')) . '</td><td>' . esc_html((string) $total) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="2">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_clicked_object_panel($title, $rows) {
    echo '<section class="kingy-ali-admin-insight">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Object', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Type', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $object_id = isset($row->object_id) ? absint($row->object_id) : 0;
            $title_text = $object_id ? get_the_title($object_id) : '';
            if ($title_text === '') {
                $title_text = __('Deleted or unavailable object', 'kingy-ai-launch-intelligence');
            }

            echo '<tr><td>';
            if ($object_id && get_post($object_id)) {
                echo '<a href="' . esc_url(get_edit_post_link($object_id)) . '">' . esc_html($title_text) . '</a>';
            } else {
                echo esc_html($title_text);
            }
            echo '</td><td>' . esc_html(kingy_ali_clicked_object_type_label($row->event_type)) . '</td><td>' . esc_html((string) $row->total) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="3">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_render_dashboard_filter_combination_panel($title, $rows) {
    echo '<section class="kingy-ali-admin-insight kingy-ali-admin-insight--wide">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Query', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Filters used', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Zero-result', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Avg results', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $total = isset($row['total']) ? absint($row['total']) : 0;
            $avg_results = $total ? ((float) $row['result_sum'] / $total) : 0;
            echo '<tr>';
            echo '<td>' . esc_html($row['query'] ? $row['query'] : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html($row['filters']) . '</td>';
            echo '<td>' . esc_html(kingy_ali_search_surface_label($row['surface'])) . '</td>';
            echo '<td>' . esc_html((string) $total) . '</td>';
            echo '<td>' . esc_html((string) absint($row['zero_results'])) . '</td>';
            echo '<td>' . esc_html(number_format_i18n($avg_results, 1)) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">' . esc_html__('No filter combinations tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</section>';
}

function kingy_ali_clicked_object_type_label($event_type) {
    $labels = array(
        'clicked_launch' => __('Launch', 'kingy-ai-launch-intelligence'),
        'clicked_tool' => __('Tool', 'kingy-ai-launch-intelligence'),
        'clicked_company' => __('Company', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$event_type]) ? $labels[$event_type] : kingy_ali_event_type_label($event_type);
}

function kingy_ali_admin_founder_submission_count() {
    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('pending', 'draft'),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => kingy_ali_meta_key('founder_submitted'),
                    'value' => '1',
                ),
            ),
        )
    );

    return (int) $query->found_posts;
}

function kingy_ali_admin_analytics_event_count($event_type, $days = 7) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
            FROM {$table}
            WHERE created_at >= %s AND event_type = %s",
            $since,
            sanitize_key($event_type)
        )
    );
}

function kingy_ali_render_analytics_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    global $wpdb;
    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since(7);
    $recent_events = kingy_ali_recent_events(7);

    $top_searches = kingy_ali_admin_top_searches(20);
    $zero_results = kingy_ali_admin_zero_result_searches(20);
    $cta_clicks = kingy_ali_admin_click_event_rows(20);
    $category_path_clicks = kingy_ali_admin_category_path_clicks(20);
    $clicked_objects = kingy_ali_admin_clicked_objects(20);
    $visibility_scores = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, COUNT(*) AS total, AVG(result_count) AS avg_score, MAX(result_count) AS max_score
        FROM {$table}
        WHERE created_at >= %s AND event_type = 'visibility_score_calculated'
        GROUP BY event_label
        ORDER BY total DESC
        LIMIT 20",
            $since
        )
    );
    $corrections = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, object_id, filters, created_at
        FROM {$table}
        WHERE created_at >= %s AND event_type = 'correction_suggested'
        ORDER BY created_at DESC
        LIMIT 20",
            $since
        )
    );
    $founder_submission_events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, object_id, filters, created_at
        FROM {$table}
        WHERE created_at >= %s AND event_type = 'founder_submission'
        ORDER BY created_at DESC
        LIMIT 20",
            $since
        )
    );
    $visibility_leads = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, query_text, filters, result_count, created_at
        FROM {$table}
        WHERE created_at >= %s AND event_type = 'visibility_score_lead'
        ORDER BY created_at DESC
        LIMIT 20",
            $since
        )
    );
    $sponsor_roi_leads = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_label, query_text, filters, result_count, created_at
        FROM {$table}
        WHERE created_at >= %s AND event_type = 'sponsor_roi_lead'
        ORDER BY created_at DESC
        LIMIT 20",
            $since
        )
    );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Search Analytics', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Signals from the last 7 days. Use zero-result and high-intent searches as the content roadmap.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?> <?php esc_html_e('Set and document a site-level analytics retention policy before public launch.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php kingy_ali_render_content_roadmap_table(__('Content roadmap from search demand', 'kingy-ai-launch-intelligence'), kingy_ali_content_roadmap_items($zero_results, kingy_ali_high_intent_searches($recent_events), 12)); ?>
        <?php kingy_ali_render_search_demand_table(__('Top searches this week', 'kingy-ai-launch-intelligence'), $top_searches, true); ?>
        <?php kingy_ali_render_search_demand_table(__('Zero-result searches', 'kingy-ai-launch-intelligence'), $zero_results, false); ?>
        <?php kingy_ali_render_count_table(__('High-intent searches', 'kingy-ai-launch-intelligence'), kingy_ali_high_intent_searches($recent_events), __('Search query', 'kingy-ai-launch-intelligence')); ?>
        <?php kingy_ali_render_count_table(__('Popular category filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'category'), __('Category', 'kingy-ai-launch-intelligence'), 'kingy_launch_category'); ?>
        <?php kingy_ali_render_count_table(__('Popular launch type filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'launch_type'), __('Launch type', 'kingy-ai-launch-intelligence'), 'kingy_launch_type'); ?>
        <?php kingy_ali_render_count_table(__('Popular audience filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'audience'), __('Audience', 'kingy-ai-launch-intelligence'), 'kingy_audience'); ?>
        <?php kingy_ali_render_count_table(__('Popular attribute filters', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'attribute'), __('Attribute', 'kingy-ai-launch-intelligence'), 'kingy_tool_attribute'); ?>
        <?php kingy_ali_render_filter_combination_table(__('Search/filter combinations', 'kingy-ai-launch-intelligence'), kingy_ali_admin_filter_combinations($recent_events, 20)); ?>
        <?php kingy_ali_render_admin_table(__('Clicked category paths', 'kingy-ai-launch-intelligence'), $category_path_clicks, array('event_label' => 'Path', 'total' => 'Total')); ?>
        <?php kingy_ali_render_count_table(__('Founder submission categories', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'category', 'founder_submission'), __('Category', 'kingy-ai-launch-intelligence'), 'kingy_launch_category'); ?>
        <?php kingy_ali_render_count_table(__('Founder submission launch types', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'launch_type', 'founder_submission'), __('Launch type', 'kingy-ai-launch-intelligence'), 'kingy_launch_type'); ?>
        <?php kingy_ali_render_count_table(__('Founder submission audiences', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'audience', 'founder_submission'), __('Audience', 'kingy-ai-launch-intelligence'), 'kingy_audience'); ?>
        <?php kingy_ali_render_count_table(__('Founder YouTube coverage interest', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'youtube_interest', 'founder_submission'), __('Interest', 'kingy-ai-launch-intelligence')); ?>
        <?php kingy_ali_render_count_table(__('Founder visibility-score interest', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'visibility_score_interest', 'founder_submission'), __('Interest', 'kingy-ai-launch-intelligence')); ?>
        <?php kingy_ali_render_count_table(__('Founder creator-coverage interest', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'creator_coverage_interest', 'founder_submission'), __('Interest', 'kingy-ai-launch-intelligence')); ?>
        <?php kingy_ali_render_count_table(__('Founder creator-campaign interest', 'kingy-ai-launch-intelligence'), kingy_ali_aggregate_event_filters($recent_events, 'sponsorship_interest', 'founder_submission'), __('Interest', 'kingy-ai-launch-intelligence')); ?>
        <?php kingy_ali_render_founder_submission_events_table(__('Recent founder submissions', 'kingy-ai-launch-intelligence'), $founder_submission_events); ?>
        <?php kingy_ali_render_admin_table(__('Launch Visibility Score calculations', 'kingy-ai-launch-intelligence'), $visibility_scores, array('event_label' => 'Band', 'total' => 'Total', 'avg_score' => 'Average score', 'max_score' => 'Max score')); ?>
        <?php kingy_ali_render_visibility_lead_table(__('Launch Visibility Score leads', 'kingy-ai-launch-intelligence'), $visibility_leads); ?>
        <?php kingy_ali_render_sponsor_roi_lead_table(__('Creator campaign ROI leads', 'kingy-ai-launch-intelligence'), $sponsor_roi_leads); ?>
        <?php kingy_ali_render_correction_suggestions_table(__('Recent correction suggestions', 'kingy-ai-launch-intelligence'), $corrections); ?>
        <?php kingy_ali_render_clicked_object_table(__('Most clicked launches, tools, and companies', 'kingy-ai-launch-intelligence'), $clicked_objects); ?>
        <?php kingy_ali_render_click_event_table(__('CTA and content clicks', 'kingy-ai-launch-intelligence'), $cta_clicks); ?>
    </div>
    <?php
}

function kingy_ali_render_scorecard_leads_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $recent_rows = kingy_ali_scorecard_lead_rows(200);
    $total_count = kingy_ali_scorecard_lead_count(0);
    $week_count = kingy_ali_scorecard_lead_count(7);
    $month_count = kingy_ali_scorecard_lead_count(30);
    $export_url = wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_export&type=scorecard_leads'), 'kingy_ali_export');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('AI Launch Scorecard Leads', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Private operations view for AI Launch Scorecard review requests captured from the public scorecard form.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?> <?php esc_html_e('Limit access to editors/operators who need lead review details.', 'kingy-ai-launch-intelligence'); ?></p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Export Scorecard Leads CSV', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(home_url('/ai-launch-scorecard/')); ?>"><?php esc_html_e('Open public scorecard', 'kingy-ai-launch-intelligence'); ?></a>
        </p>
        <div class="kingy-ali-admin-cards">
            <?php kingy_ali_admin_stat_card(__('Scorecard leads this week', 'kingy-ai-launch-intelligence'), $week_count); ?>
            <?php kingy_ali_admin_stat_card(__('Scorecard leads this month', 'kingy-ai-launch-intelligence'), $month_count); ?>
            <?php kingy_ali_admin_stat_card(__('Scorecard leads total', 'kingy-ai-launch-intelligence'), $total_count); ?>
        </div>
        <?php kingy_ali_render_scorecard_lead_table(__('Recent AI Launch Scorecard leads', 'kingy-ai-launch-intelligence'), $recent_rows, true); ?>
    </div>
    <?php
}

function kingy_ali_scorecard_lead_count($days = 0) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $days = absint($days);

    if ($days > 0) {
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$table}
                WHERE event_type = 'ai_launch_scorecard_lead' AND created_at >= %s",
                kingy_ali_analytics_since($days)
            )
        );
    }

    return (int) $wpdb->get_var(
        "SELECT COUNT(*)
        FROM {$table}
        WHERE event_type = 'ai_launch_scorecard_lead'"
    );
}

function kingy_ali_scorecard_lead_rows($limit = 200) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $limit = max(1, min(2000, absint($limit)));

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, event_label, query_text, filters, result_count, created_at
            FROM {$table}
            WHERE event_type = 'ai_launch_scorecard_lead'
            ORDER BY created_at DESC
            LIMIT %d",
            $limit
        )
    );
}

function kingy_ali_scorecard_lead_row_data($row) {
    $filters = isset($row->filters) ? json_decode((string) $row->filters, true) : array();
    $filters = is_array($filters) ? $filters : array();
    $official_url = kingy_ali_admin_public_url_value(kingy_ali_admin_event_filter_value($filters, 'official_url'));
    $product_name = isset($row->query_text) && $row->query_text !== ''
        ? sanitize_text_field((string) $row->query_text)
        : sanitize_text_field(kingy_ali_admin_event_filter_value($filters, 'product_name'));
    $contact_name = sanitize_text_field(kingy_ali_admin_event_filter_value($filters, 'contact_name'));
    $email = sanitize_email(kingy_ali_admin_event_filter_value($filters, 'email'));
    $notes = sanitize_textarea_field(kingy_ali_admin_event_filter_value($filters, 'notes'));
    $interest = kingy_ali_event_filter_label($filters, 'interest');
    $source = sanitize_key(kingy_ali_admin_event_filter_value($filters, 'source'));
    $tier = isset($row->event_label) ? sanitize_text_field((string) $row->event_label) : '';
    $created_at = isset($row->created_at) ? sanitize_text_field((string) $row->created_at) : '';
    $score = isset($row->result_count) ? (int) $row->result_count : 0;

    return array(
        'id' => isset($row->id) ? absint($row->id) : 0,
        'created_at' => $created_at,
        'product_name' => $product_name,
        'contact_name' => $contact_name,
        'email' => $email,
        'official_url' => $official_url,
        'score' => $score,
        'tier' => $tier,
        'interest' => $interest,
        'notes' => $notes,
        'source' => $source ? $source : 'ai_launch_scorecard',
        'scores' => kingy_ali_scorecard_lead_category_scores($filters),
    );
}

function kingy_ali_scorecard_lead_category_scores($filters) {
    $scores = array();
    if (!function_exists('kingy_ali_ai_launch_scorecard_weights')) {
        return $scores;
    }

    foreach (kingy_ali_ai_launch_scorecard_weights() as $key => $weight) {
        $value = kingy_ali_admin_event_filter_value($filters, $key);
        $scores[$key] = $value === '' ? '' : (string) max(0, min(1, (float) $value));
    }

    return $scores;
}

function kingy_ali_render_scorecard_lead_table($title, $rows, $include_notes = false) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Submitted', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Product', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Score', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Review interest', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Contact name', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Email', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Official URL', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Source', 'kingy-ai-launch-intelligence') . '</th>';
    if ($include_notes) {
        echo '<th>' . esc_html__('Notes', 'kingy-ai-launch-intelligence') . '</th>';
    }
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $lead = kingy_ali_scorecard_lead_row_data($row);
            echo '<tr>';
            echo '<td>' . esc_html(kingy_ali_scorecard_lead_created_label($lead['created_at'])) . '</td>';
            echo '<td>' . esc_html($lead['product_name'] ? $lead['product_name'] : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html(sprintf(__('%1$d / 100 (%2$s)', 'kingy-ai-launch-intelligence'), $lead['score'], $lead['tier'] ? $lead['tier'] : __('Unscored', 'kingy-ai-launch-intelligence'))) . '</td>';
            echo '<td>' . esc_html($lead['interest']) . '</td>';
            echo '<td>' . esc_html($lead['contact_name'] ? $lead['contact_name'] : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . ($lead['email'] ? '<a href="mailto:' . esc_attr($lead['email']) . '">' . esc_html($lead['email']) . '</a>' : esc_html__('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . kingy_ali_scorecard_lead_url_link($lead['official_url']) . '</td>';
            echo '<td>' . esc_html($lead['source']) . '</td>';
            if ($include_notes) {
                echo '<td>' . esc_html($lead['notes'] ? $lead['notes'] : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . esc_attr($include_notes ? '9' : '8') . '">' . esc_html__('No AI Launch Scorecard leads tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_scorecard_lead_created_label($created_at) {
    $timestamp = strtotime((string) $created_at);
    if (!$timestamp) {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
}

function kingy_ali_scorecard_lead_url_link($official_url) {
    $official_url = kingy_ali_admin_public_url_value($official_url);
    if (!$official_url) {
        return esc_html__('—', 'kingy-ai-launch-intelligence');
    }

    $host = wp_parse_url($official_url, PHP_URL_HOST);
    $label = $host ? $host : $official_url;
    return '<a href="' . esc_url($official_url) . '">' . esc_html($label) . '</a>';
}

function kingy_ali_content_roadmap_items($zero_result_rows, $high_intent_counts, $limit = 12) {
    $items = array();
    foreach ((array) $zero_result_rows as $row) {
        $query = isset($row->query_text) ? sanitize_text_field($row->query_text) : '';
        if ($query === '') {
            continue;
        }

        $surface = isset($row->event_label) ? sanitize_key($row->event_label) : 'launch_hub';
        $key = strtolower($surface . '|' . $query);
        $total = isset($row->total) ? absint($row->total) : 0;
        $high_intent_total = isset($high_intent_counts[$query]) ? absint($high_intent_counts[$query]) : 0;
        $action = kingy_ali_content_roadmap_action($query, $surface, true);
        $items[$key] = array(
            'query' => $query,
            'surface' => $surface,
            'signal' => $high_intent_total ? __('Zero-result + high-intent', 'kingy-ai-launch-intelligence') : __('Zero-result search', 'kingy-ai-launch-intelligence'),
            'demand' => $total,
            'priority' => $total * 3,
            'action_label' => $action['label'],
            'action_url' => $action['url'],
            'suggestion' => $action['suggestion'],
        );
    }

    foreach ((array) $high_intent_counts as $query => $total) {
        $query = sanitize_text_field($query);
        if ($query === '') {
            continue;
        }

        $matched_existing = false;
        foreach ($items as $item_key => $item) {
            if (strtolower($item['query']) === strtolower($query)) {
                $items[$item_key]['demand'] += absint($total);
                $items[$item_key]['priority'] += absint($total) * 2;
                $items[$item_key]['signal'] = __('Zero-result + high-intent', 'kingy-ai-launch-intelligence');
                $matched_existing = true;
            }
        }
        if ($matched_existing) {
            continue;
        }

        $action = kingy_ali_content_roadmap_action($query, 'launch_hub', false);
        $key = 'high_intent|' . strtolower($query);
        $items[$key] = array(
            'query' => $query,
            'surface' => 'launch_hub',
            'signal' => __('High-intent search', 'kingy-ai-launch-intelligence'),
            'demand' => absint($total),
            'priority' => absint($total),
            'action_label' => $action['label'],
            'action_url' => $action['url'],
            'suggestion' => $action['suggestion'],
        );
    }

    uasort(
        $items,
        function ($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return strcasecmp($a['query'], $b['query']);
            }

            return $a['priority'] < $b['priority'] ? 1 : -1;
        }
    );

    return array_slice(array_values($items), 0, max(1, absint($limit)));
}

function kingy_ali_content_roadmap_action($query, $surface, $zero_result = false) {
    $query_lc = strtolower((string) $query);
    $surface = sanitize_key($surface);

    if ($surface === 'tool_directory') {
        return array(
            'label' => __('Open tool profiles', 'kingy-ai-launch-intelligence'),
            'url' => admin_url('edit.php?post_type=kingy_ai_tool'),
            'suggestion' => __('Create or enrich the matching AI Tool profile, then connect the latest launch record.', 'kingy-ai-launch-intelligence'),
        );
    }

    if ($surface === 'company_directory') {
        return array(
            'label' => __('Open company profiles', 'kingy-ai-launch-intelligence'),
            'url' => admin_url('edit.php?post_type=kingy_ai_company'),
            'suggestion' => __('Create or enrich the matching AI Company profile with founder, funding, and launch history notes.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (strpos($query_lc, 'sponsor') !== false || strpos($query_lc, 'coverage') !== false || strpos($query_lc, 'youtube') !== false || strpos($query_lc, 'roi') !== false || strpos($query_lc, 'demo') !== false) {
        return array(
            'label' => __('Open editorial queues', 'kingy-ai-launch-intelligence'),
            'url' => admin_url('admin.php?page=kingy-ali-editorial-queues'),
            'suggestion' => __('Review creator-coverage, YouTube-worthy, or internal partner-fit candidates and fill demo/traction fields.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (strpos($query_lc, 'alternative') !== false || strpos($query_lc, 'alternatives') !== false || strpos($query_lc, ' vs ') !== false || strpos($query_lc, 'compare') !== false) {
        return array(
            'label' => __('Draft article', 'kingy-ai-launch-intelligence'),
            'url' => admin_url('admin.php?page=kingy-ali-article-generator'),
            'suggestion' => __('Turn this into a comparison, alternatives page, or Launch Radar section with linked tool profiles.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (strpos($query_lc, 'free') !== false || strpos($query_lc, 'api') !== false || strpos($query_lc, 'open source') !== false || strpos($query_lc, 'open weight') !== false) {
        return array(
            'label' => __('Import or add launches', 'kingy-ai-launch-intelligence'),
            'url' => admin_url('admin.php?page=kingy-ali-import'),
            'suggestion' => __('Backfill structured launch/tool records with the requested pricing, API, or open-source attributes.', 'kingy-ai-launch-intelligence'),
        );
    }

    return array(
        'label' => $zero_result ? __('Add launch record', 'kingy-ai-launch-intelligence') : __('Review launches', 'kingy-ai-launch-intelligence'),
        'url' => $zero_result ? admin_url('post-new.php?post_type=kingy_ai_launch') : admin_url('edit.php?post_type=kingy_ai_launch'),
        'suggestion' => $zero_result
            ? __('Add or import structured launch records that directly answer this query.', 'kingy-ai-launch-intelligence')
            : __('Review matching launch records and strengthen titles, categories, audiences, and internal links.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_render_content_roadmap_table($title, $items) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Search demand', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Signal', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Priority', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Suggested next step', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Action', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($items) {
        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item['query']) . '<br><small>' . esc_html(sprintf(__('Demand events: %d', 'kingy-ai-launch-intelligence'), absint($item['demand']))) . '</small></td>';
            echo '<td>' . esc_html(kingy_ali_search_surface_label($item['surface'])) . '</td>';
            echo '<td>' . esc_html($item['signal']) . '</td>';
            echo '<td>' . esc_html((string) absint($item['priority'])) . '</td>';
            echo '<td>' . esc_html($item['suggestion']) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($item['action_url']) . '">' . esc_html($item['action_label']) . '</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">' . esc_html__('No roadmap signals yet. Searches, filters, and zero-result events will appear here after visitors use the hub.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_admin_table($title, $rows, $columns) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    foreach ($columns as $label) {
        echo '<th>' . esc_html($label) . '</th>';
    }
    echo '</tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($columns as $key => $label) {
                echo '<td>' . esc_html(isset($row->$key) ? (string) $row->$key : '') . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . esc_attr((string) count($columns)) . '">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }
    echo '</tbody></table>';
}

function kingy_ali_event_type_label($event_type) {
    $event_type = sanitize_key($event_type);
    $labels = array(
        'clicked_submit_cta' => __('Submit CTA', 'kingy-ai-launch-intelligence'),
        'clicked_sponsorship_cta' => __('Creator review CTA', 'kingy-ai-launch-intelligence'),
        'clicked_roi_calculator' => __('ROI calculator CTA', 'kingy-ai-launch-intelligence'),
        'clicked_visibility_score_cta' => __('Launch Visibility Score CTA', 'kingy-ai-launch-intelligence'),
        'clicked_contact_cta' => __('Contact CTA', 'kingy-ai-launch-intelligence'),
        'clicked_client_examples_cta' => __('Client examples CTA', 'kingy-ai-launch-intelligence'),
        'clicked_correction_cta' => __('Correction CTA', 'kingy-ai-launch-intelligence'),
        'clicked_filter' => __('Launch filter', 'kingy-ai-launch-intelligence'),
        'clicked_filter_reset' => __('Launch filter reset', 'kingy-ai-launch-intelligence'),
        'clicked_category_path' => __('Category path', 'kingy-ai-launch-intelligence'),
        'clicked_directory_reset' => __('Directory reset', 'kingy-ai-launch-intelligence'),
        'clicked_launch' => __('Launch profile', 'kingy-ai-launch-intelligence'),
        'clicked_tool' => __('Tool profile', 'kingy-ai-launch-intelligence'),
        'clicked_company' => __('Company profile', 'kingy-ai-launch-intelligence'),
        'clicked_source_link' => __('Source link', 'kingy-ai-launch-intelligence'),
        'clicked_official_tool_link' => __('Official tool link', 'kingy-ai-launch-intelligence'),
        'clicked_academy_cta' => __('Academy CTA', 'kingy-ai-launch-intelligence'),
        'clicked_codex_article_resource' => __('Codex article resource', 'kingy-ai-launch-intelligence'),
        'clicked_replit_resource' => __('Replit resource', 'kingy-ai-launch-intelligence'),
        'clicked_safe_agent_resource' => __('Safe agent resource', 'kingy-ai-launch-intelligence'),
        'clicked_vibe_resource' => __('Vibe coding resource', 'kingy-ai-launch-intelligence'),
    );

    return isset($labels[$event_type]) ? $labels[$event_type] : ucfirst(str_replace('_', ' ', $event_type));
}

function kingy_ali_render_click_event_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Signal', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Label', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Target', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $filters = json_decode((string) $row->filters, true);
            $filters = is_array($filters) ? $filters : array();
            $surface = kingy_ali_event_filter_label($filters, 'surface');
            $target_url = kingy_ali_admin_public_url_value(kingy_ali_admin_event_filter_value($filters, 'target_url'));

            echo '<tr>';
            echo '<td>' . esc_html(kingy_ali_event_type_label($row->event_type)) . '</td>';
            echo '<td>' . esc_html($row->event_label ? $row->event_label : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html($surface) . '</td>';
            echo '<td>' . kingy_ali_admin_click_target_link($target_url) . '</td>';
            echo '<td>' . esc_html((string) $row->total) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">' . esc_html__('No click events tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_admin_click_target_link($target_url) {
    $target_url = kingy_ali_admin_public_url_value($target_url);
    if (!$target_url) {
        return esc_html__('—', 'kingy-ai-launch-intelligence');
    }

    $path = wp_parse_url($target_url, PHP_URL_PATH);
    $query = wp_parse_url($target_url, PHP_URL_QUERY);
    $label = $path ? $path : $target_url;
    if ($query) {
        $label .= '?' . $query;
    }

    return '<a href="' . esc_url($target_url) . '">' . esc_html($label) . '</a>';
}

function kingy_ali_render_search_demand_table($title, $rows, $include_average = true) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Search query', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    if ($include_average) {
        echo '<th>' . esc_html__('Average results', 'kingy-ai-launch-intelligence') . '</th>';
    }
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->query_text) . '</td>';
            echo '<td>' . esc_html(kingy_ali_search_surface_label($row->event_label)) . '</td>';
            echo '<td>' . esc_html((string) $row->total) . '</td>';
            if ($include_average) {
                echo '<td>' . esc_html(number_format_i18n((float) $row->avg_results, 1)) . '</td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="' . esc_attr($include_average ? '4' : '3') . '">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_search_surface_label($event_label) {
    switch ($event_label) {
        case 'launch_hub':
            return __('Launch hub', 'kingy-ai-launch-intelligence');
        case 'launch_today':
            return __('Today launches', 'kingy-ai-launch-intelligence');
        case 'launch_week':
            return __('This week launches', 'kingy-ai-launch-intelligence');
        case 'creator_coverage':
            return __('Creator coverage shortlist', 'kingy-ai-launch-intelligence');
        case 'youtube_worthy':
            return __('YouTube-worthy launches', 'kingy-ai-launch-intelligence');
        case 'tool_directory':
            return __('Tool directory', 'kingy-ai-launch-intelligence');
        case 'company_directory':
            return __('Company directory', 'kingy-ai-launch-intelligence');
        default:
            return __('Launch hub', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_render_count_table($title, $counts, $label_heading, $taxonomy = '') {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr><th>' . esc_html($label_heading) . '</th><th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th></tr></thead><tbody>';

    if ($counts) {
        foreach (array_slice($counts, 0, 20, true) as $value => $total) {
            $label = $value;
            if ($taxonomy) {
                $term = get_term_by('slug', $value, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $label = $term->name;
                }
            }
            echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html((string) $total) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="2">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_filter_combination_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Search query', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Filters used', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Surface', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Zero-result', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Average results', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $total = isset($row['total']) ? absint($row['total']) : 0;
            $avg_results = $total ? ((float) $row['result_sum'] / $total) : 0;
            echo '<tr>';
            echo '<td>' . esc_html($row['query'] ? $row['query'] : __('—', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html($row['filters']) . '</td>';
            echo '<td>' . esc_html(kingy_ali_search_surface_label($row['surface'])) . '</td>';
            echo '<td>' . esc_html((string) $total) . '</td>';
            echo '<td>' . esc_html((string) absint($row['zero_results'])) . '</td>';
            echo '<td>' . esc_html(number_format_i18n($avg_results, 1)) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">' . esc_html__('No filter combinations tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_founder_submission_events_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Submission', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Category', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Launch type', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Audience', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('YouTube', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Creator review', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Creator campaign', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Created', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $filters = json_decode((string) $row->filters, true);
            $filters = is_array($filters) ? $filters : array();
            echo '<tr>';
            echo '<td>' . kingy_ali_founder_submission_event_link($row) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'category', 'kingy_launch_category')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'launch_type', 'kingy_launch_type')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'audience', 'kingy_audience')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'youtube_interest')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'creator_coverage_interest')) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'sponsorship_interest')) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($row->created_at))) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8">' . esc_html__('No founder submissions tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_visibility_lead_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Product', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Score', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Interest', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Contact', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Official URL', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Created', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $filters = json_decode((string) $row->filters, true);
            $filters = is_array($filters) ? $filters : array();
            $product_name = $row->query_text ? $row->query_text : kingy_ali_event_filter_label($filters, 'product_name');
            $official_url = kingy_ali_admin_public_url_value(kingy_ali_admin_event_filter_value($filters, 'official_url'));
            $email = sanitize_email(kingy_ali_admin_event_filter_value($filters, 'email'));
            $contact = trim(
                sprintf(
                    '%s %s',
                    kingy_ali_event_filter_label($filters, 'contact_name'),
                    $email ? '<' . $email . '>' : ''
                )
            );

            echo '<tr>';
            echo '<td>' . esc_html($product_name) . '</td>';
            echo '<td>' . esc_html(sprintf(__('%1$d / 100 (%2$s)', 'kingy-ai-launch-intelligence'), (int) $row->result_count, $row->event_label)) . '</td>';
            echo '<td>' . esc_html(kingy_ali_event_filter_label($filters, 'interest')) . '</td>';
            echo '<td>' . esc_html($contact) . '</td>';
            echo '<td>';
            if ($official_url) {
                echo '<a href="' . esc_url($official_url) . '">' . esc_html(wp_parse_url($official_url, PHP_URL_HOST) ? wp_parse_url($official_url, PHP_URL_HOST) : $official_url) . '</a>';
            } else {
                echo esc_html__('—', 'kingy-ai-launch-intelligence');
            }
            echo '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($row->created_at))) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6">' . esc_html__('No visibility score leads tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_sponsor_roi_lead_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Company/product', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('ROI', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Projected value', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Cost', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Contact', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Official URL', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Created', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $filters = json_decode((string) $row->filters, true);
            $filters = is_array($filters) ? $filters : array();
            $company_name = $row->query_text ? $row->query_text : kingy_ali_event_filter_label($filters, 'company_name');
            $official_url = kingy_ali_admin_public_url_value(kingy_ali_admin_event_filter_value($filters, 'official_url'));
            $email = sanitize_email(kingy_ali_admin_event_filter_value($filters, 'email'));
            $contact = trim(
                sprintf(
                    '%s %s',
                    kingy_ali_event_filter_label($filters, 'contact_name'),
                    $email ? '<' . $email . '>' : ''
                )
            );
            $roi_value = kingy_ali_admin_event_filter_value($filters, 'roi');
            $revenue_value = kingy_ali_admin_event_filter_value($filters, 'revenue');
            $cost_value = kingy_ali_admin_event_filter_value($filters, 'sponsorship_cost');
            $roi = $roi_value !== '' ? (float) $roi_value : (float) $row->result_count;
            $revenue = $revenue_value !== '' ? (float) $revenue_value : 0;
            $cost = $cost_value !== '' ? (float) $cost_value : 0;

            echo '<tr>';
            echo '<td>' . esc_html($company_name) . '</td>';
            echo '<td>' . esc_html(sprintf(__('%1$s%% (%2$s)', 'kingy-ai-launch-intelligence'), number_format_i18n($roi, 1), $row->event_label)) . '</td>';
            echo '<td>' . esc_html('$' . number_format_i18n($revenue, 0)) . '</td>';
            echo '<td>' . esc_html('$' . number_format_i18n($cost, 0)) . '</td>';
            echo '<td>' . esc_html($contact) . '</td>';
            echo '<td>';
            if ($official_url) {
                echo '<a href="' . esc_url($official_url) . '">' . esc_html(wp_parse_url($official_url, PHP_URL_HOST) ? wp_parse_url($official_url, PHP_URL_HOST) : $official_url) . '</a>';
            } else {
                echo esc_html__('—', 'kingy-ai-launch-intelligence');
            }
            echo '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($row->created_at))) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">' . esc_html__('No creator campaign ROI leads tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_correction_suggestions_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Record', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Correction', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Contact', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Created', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $filters = json_decode((string) $row->filters, true);
            $filters = is_array($filters) ? $filters : array();
            $note = sanitize_textarea_field(kingy_ali_admin_event_filter_value($filters, 'note'));
            $email = sanitize_email(kingy_ali_admin_event_filter_value($filters, 'email'));
            $record_url = kingy_ali_admin_public_url_value(kingy_ali_admin_event_filter_value($filters, 'record_url'));
            $record_type = sanitize_key(kingy_ali_admin_event_filter_value($filters, 'record_type'));

            echo '<tr>';
            echo '<td>';
            echo kingy_ali_correction_record_link($row, $record_url);
            if ($record_type) {
                echo '<br><small>' . esc_html($record_type) . '</small>';
            }
            echo '</td>';
            echo '<td>' . esc_html($note ? wp_trim_words($note, 28) : __('No note captured for older correction event.', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html($email ? $email : __('Not provided', 'kingy-ai-launch-intelligence')) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($row->created_at))) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">' . esc_html__('No correction suggestions tracked yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_correction_record_link($row, $record_url = '') {
    $label = $row->event_label ? $row->event_label : __('Unknown record', 'kingy-ai-launch-intelligence');
    if (!empty($row->object_id) && get_post(absint($row->object_id))) {
        return '<a href="' . esc_url(get_edit_post_link(absint($row->object_id))) . '">' . esc_html($label) . '</a>';
    }

    $record_url = kingy_ali_admin_public_url_value($record_url);
    if ($record_url) {
        return '<a href="' . esc_url($record_url) . '">' . esc_html($label) . '</a>';
    }

    return esc_html($label);
}

function kingy_ali_founder_submission_event_link($row) {
    $label = $row->event_label ? $row->event_label : __('Untitled submission', 'kingy-ai-launch-intelligence');
    if (!empty($row->object_id) && get_post($row->object_id)) {
        return '<a href="' . esc_url(get_edit_post_link(absint($row->object_id))) . '">' . esc_html($label) . '</a>';
    }

    return esc_html($label);
}

function kingy_ali_event_filter_label($filters, $key, $taxonomy = '') {
    $value = kingy_ali_admin_event_filter_value($filters, $key);
    if ($value === '') {
        return __('—', 'kingy-ai-launch-intelligence');
    }

    $value = sanitize_text_field($value);
    if ($key === 'interest' && function_exists('kingy_ali_visibility_interest_label')) {
        return kingy_ali_visibility_interest_label($value);
    }

    if ($taxonomy) {
        $term = get_term_by('slug', $value, $taxonomy);
        if ($term && !is_wp_error($term)) {
            return $term->name;
        }
    }

    return $value === '' ? __('—', 'kingy-ai-launch-intelligence') : ucfirst(str_replace(array('_', '-'), ' ', $value));
}

function kingy_ali_admin_event_filter_value($filters, $key) {
    if (!is_array($filters) || !isset($filters[$key]) || !is_scalar($filters[$key])) {
        return '';
    }

    return (string) $filters[$key];
}

function kingy_ali_render_clicked_object_table($title, $rows) {
    echo '<h2>' . esc_html($title) . '</h2>';
    echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Type', 'kingy-ai-launch-intelligence') . '</th><th>' . esc_html__('Object', 'kingy-ai-launch-intelligence') . '</th><th>' . esc_html__('Total', 'kingy-ai-launch-intelligence') . '</th></tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $title_text = $row->object_id ? get_the_title(absint($row->object_id)) : '';
            if ($title_text === '') {
                $title_text = __('Deleted or unavailable object', 'kingy-ai-launch-intelligence');
            }
            echo '<tr><td>' . esc_html(kingy_ali_clicked_object_type_label($row->event_type)) . '</td><td>' . esc_html($title_text) . '</td><td>' . esc_html((string) $row->total) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="3">' . esc_html__('No data yet.', 'kingy-ai-launch-intelligence') . '</td></tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_render_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $summary = kingy_ali_import_summary_from_request();
    $diagnostics = $summary ? kingy_ali_get_last_import_diagnostics() : array();
    $summary_type = $summary && !empty($summary['type']) ? $summary['type'] : 'launches';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import Launch and Model Records', 'kingy-ai-launch-intelligence'); ?></h1>
        <?php if ($summary) : ?>
            <div class="notice <?php echo esc_attr(($summary['failed'] || $summary['skipped']) ? 'notice-warning' : 'notice-success'); ?>">
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('%1$s import complete. Created: %2$d. Updated: %3$d. Skipped: %4$d. Failed: %5$d. Total rows: %6$d.', 'kingy-ai-launch-intelligence'),
                            kingy_ali_import_type_label($summary_type),
                            $summary['created'],
                            $summary['updated'],
                            $summary['skipped'],
                            $summary['failed'],
                            $summary['total']
                        )
                    );
                    ?>
                </p>
            </div>
            <?php kingy_ali_render_import_diagnostics($diagnostics); ?>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kingy_ali_import', 'kingy_ali_import_nonce'); ?>
            <input type="hidden" name="action" value="kingy_ali_import">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="kingy_ali_import_type"><?php esc_html_e('Import type', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td>
                            <select id="kingy_ali_import_type" name="kingy_ali_import_type">
                                <?php foreach (kingy_ali_import_type_options() as $type => $label) : ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Choose AI Model profiles for /ai-models/ CSV files. Model rows default to draft and remain noindexed until readiness gates pass.', 'kingy-ai-launch-intelligence'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><input type="file" name="kingy_ali_import_file" accept=".csv,.json" required></p>
            <p><button class="button button-primary" type="submit"><?php esc_html_e('Import CSV/JSON', 'kingy-ai-launch-intelligence'); ?></button></p>
        </form>
        <p class="description"><?php esc_html_e('Import diagnostics warn when rows are missing fields used by indexability checks. Launch rows need launch/audience/pricing/verdict/source fields. Model rows need provider, modality, official sources, overview, access/pricing, benchmark caveats, verification, and last-verified fields.', 'kingy-ai-launch-intelligence'); ?></p>
        <h2><?php esc_html_e('Starter Seed Pack', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('The MVP target is 50 launch records, 25 tool profiles, at least 10 categories, 5 curated category pages, 1 submission form, and 1 visibility score calculator. The starter CSV keeps the 50/25 record shape while covering 20 category slugs so the broader Launch Intelligence taxonomy can be tested before verified data is imported.', 'kingy-ai-launch-intelligence'); ?></p>
        <p>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_download_import_template'), 'kingy_ali_download_import_template')); ?>"><?php esc_html_e('Download Blank Import Template', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_download_model_import_template'), 'kingy_ali_download_model_import_template')); ?>"><?php esc_html_e('Download Model Import Template', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_download_starter_seed'), 'kingy_ali_download_starter_seed')); ?>"><?php esc_html_e('Download 50-Row Starter Seed CSV', 'kingy-ai-launch-intelligence'); ?></a>
        </p>
        <?php kingy_ali_render_seed_checklist(); ?>
        <?php kingy_ali_render_model_readiness_report(); ?>
        <h2><?php esc_html_e('Export', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Launch and model CSV exports use supported import columns and can be re-imported after spreadsheet edits. Tool and company CSV exports are audit sheets for review, enrichment planning, and manual profile updates.', 'kingy-ai-launch-intelligence'); ?></p>
        <p>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_export&type=launches'), 'kingy_ali_export')); ?>"><?php esc_html_e('Export Launch CSV', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_export&type=models'), 'kingy_ali_export')); ?>"><?php esc_html_e('Export Model CSV', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_export&type=tools'), 'kingy_ali_export')); ?>"><?php esc_html_e('Export Tool CSV', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kingy_ali_export&type=companies'), 'kingy_ali_export')); ?>"><?php esc_html_e('Export Company CSV', 'kingy-ai-launch-intelligence'); ?></a>
        </p>
        <h2><?php esc_html_e('Supported columns', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><strong><?php esc_html_e('Launch columns', 'kingy-ai-launch-intelligence'); ?></strong><br><code><?php echo esc_html(implode(', ', kingy_ali_import_column_keys())); ?></code></p>
        <p><strong><?php esc_html_e('Model columns', 'kingy-ai-launch-intelligence'); ?></strong><br><code><?php echo esc_html(implode(', ', kingy_ali_model_import_column_keys())); ?></code></p>
    </div>
    <?php
}

function kingy_ali_import_column_keys() {
    return array(
        'product_name',
        'launch_name',
        'tool_name',
        'company',
        'company_profile',
        'launch_date',
        'launch_type',
        'category',
        'audience',
        'official_url',
        'what_launched',
        'who_it_is_for',
        'pricing',
        'free_plan',
        'api_available',
        'open_source_or_open_weight',
        'demo_url',
        'product_hunt_url',
        'github_url',
        'huggingface_url',
        'x_url',
        'youtube_url',
        'funding',
        'press_kit_url',
        'founder_team',
        'reddit_signal',
        'youtube_signal',
        'traction_notes',
        'kingy_launch_score',
        'demo_quality_score',
        'youtube_score',
        'seo_score',
        'sponsor_fit_score_internal',
        'kingy_verdict',
        'what_feels_promising',
        'what_feels_unproven',
        'related_article_url',
        'related_course_url',
        'related_review_url',
        'related_alternatives_url',
        'related_calculator_url',
        'best_next_link_url',
        'best_next_link_label',
        'youtube_interest',
        'creator_coverage_interest',
        'sponsorship_interest',
        'visibility_score_interest',
        'budget_likelihood_internal',
        'founder_notes',
        'internal_notes',
        'sources',
        'seo_title',
        'meta_description',
        'target_search_query',
        'featured_snippet_answer',
        'status',
        'verification_status',
        'last_verified',
    );
}

function kingy_ali_model_import_column_keys() {
    return array(
        'model_name',
        'provider_name',
        'model_provider',
        'model_family_name',
        'model_family',
        'release_date',
        'model_status',
        'model_status_note',
        'model_modality',
        'model_use_case',
        'model_access_type',
        'model_license_type',
        'official_url',
        'official_announcement_url',
        'official_docs_url',
        'api_reference_url',
        'model_card_url',
        'system_card_url',
        'evals_url',
        'pricing_url',
        'license_url',
        'weights_url',
        'sources',
        'context_window',
        'context_source_url',
        'output_limit',
        'tool_calling',
        'fine_tuning',
        'api_available',
        'web_app_available',
        'local_available',
        'open_weight',
        'open_source',
        'pricing',
        'api_pricing',
        'hardware_requirements',
        'license_notes',
        'model_overview',
        'what_changed',
        'best_for',
        'skip_if',
        'strengths',
        'weaknesses',
        'agent_suitability',
        'coding_notes',
        'reasoning_notes',
        'creative_notes',
        'research_notes',
        'benchmark_summary',
        'benchmark_caveat',
        'benchmark_url',
        'kingy_verdict',
        'related_launch_id',
        'related_tool_id',
        'related_company_id',
        'alternatives_url',
        'related_article_url',
        'related_course_url',
        'seo_title',
        'meta_description',
        'target_search_query',
        'verification_status',
        'last_verified',
        'noindex',
        'status',
        'internal_notes',
    );
}

function kingy_ali_import_type_options() {
    return array(
        'launches' => __('Launch records', 'kingy-ai-launch-intelligence'),
        'models' => __('AI model profiles', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_import_type_label($type) {
    $type = kingy_ali_sanitize_import_type($type);
    $options = kingy_ali_import_type_options();
    return isset($options[$type]) ? $options[$type] : $options['launches'];
}

function kingy_ali_sanitize_import_type($type) {
    $type = sanitize_key((string) $type);
    return in_array($type, array('launches', 'models'), true) ? $type : 'launches';
}

function kingy_ali_import_type_from_post() {
    return kingy_ali_sanitize_import_type(kingy_ali_import_post_value('kingy_ali_import_type'));
}

function kingy_ali_import_type_from_request() {
    return kingy_ali_sanitize_import_type(kingy_ali_import_summary_request_value('import_type'));
}

function kingy_ali_import_supported_column_keys($import_type = 'launches') {
    return kingy_ali_sanitize_import_type($import_type) === 'models'
        ? kingy_ali_model_import_column_keys()
        : kingy_ali_import_column_keys();
}

function kingy_ali_import_column_aliases($import_type = 'launches') {
    if (kingy_ali_sanitize_import_type($import_type) === 'models') {
        return array(
            'model' => 'model_name',
            'name' => 'model_name',
            'title' => 'model_name',
            'provider' => 'model_provider',
            'provider_taxonomy' => 'model_provider',
            'provider_term' => 'model_provider',
            'provider_company' => 'provider_name',
            'family' => 'model_family',
            'model_family_term' => 'model_family',
            'modality' => 'model_modality',
            'modalities' => 'model_modality',
            'use_case' => 'model_use_case',
            'use_cases' => 'model_use_case',
            'access' => 'model_access_type',
            'access_type' => 'model_access_type',
            'license' => 'model_license_type',
            'license_type' => 'model_license_type',
            'model_license' => 'model_license_type',
            'status_term' => 'model_status',
            'model_status_term' => 'model_status',
            'release' => 'release_date',
            'released' => 'release_date',
            'official_link' => 'official_url',
            'official_website' => 'official_url',
            'website' => 'official_url',
            'announcement' => 'official_announcement_url',
            'announcement_url' => 'official_announcement_url',
            'docs' => 'official_docs_url',
            'documentation' => 'official_docs_url',
            'documentation_url' => 'official_docs_url',
            'api_docs_url' => 'api_reference_url',
            'api_reference' => 'api_reference_url',
            'model_card' => 'model_card_url',
            'system_card' => 'system_card_url',
            'safety_card_url' => 'system_card_url',
            'evals' => 'evals_url',
            'evaluations_url' => 'evals_url',
            'price_url' => 'pricing_url',
            'pricing_link' => 'pricing_url',
            'license_link' => 'license_url',
            'weights' => 'weights_url',
            'weights_link' => 'weights_url',
            'source' => 'sources',
            'source_links' => 'sources',
            'context' => 'context_window',
            'context_length' => 'context_window',
            'context_window_source' => 'context_source_url',
            'max_output' => 'output_limit',
            'function_calling' => 'tool_calling',
            'tools' => 'tool_calling',
            'fine_tune' => 'fine_tuning',
            'api' => 'api_available',
            'has_api' => 'api_available',
            'web_app' => 'web_app_available',
            'local' => 'local_available',
            'self_hosted' => 'local_available',
            'open_weights' => 'open_weight',
            'open_source_model' => 'open_source',
            'api_price' => 'api_pricing',
            'hardware' => 'hardware_requirements',
            'overview' => 'model_overview',
            'description' => 'model_overview',
            'what_it_is' => 'model_overview',
            'what_changed_at_launch' => 'what_changed',
            'best_for_summary' => 'best_for',
            'skip_if_summary' => 'skip_if',
            'limits' => 'weaknesses',
            'caveats' => 'weaknesses',
            'agent_notes' => 'agent_suitability',
            'benchmark' => 'benchmark_summary',
            'benchmark_notes' => 'benchmark_summary',
            'benchmark_source_url' => 'benchmark_url',
            'benchmark_caveats' => 'benchmark_caveat',
            'kingy_take' => 'kingy_verdict',
            'kingy_ai_take' => 'kingy_verdict',
            'verdict' => 'kingy_verdict',
            'related_launch' => 'related_launch_id',
            'related_tool' => 'related_tool_id',
            'related_company' => 'related_company_id',
            'related_article' => 'related_article_url',
            'related_course' => 'related_course_url',
            'alternatives' => 'alternatives_url',
            'comparison_url' => 'alternatives_url',
            'seo_meta_title' => 'seo_title',
            'seo_description' => 'meta_description',
            'target_query' => 'target_search_query',
            'verification' => 'verification_status',
            'verified_date' => 'last_verified',
            'publish_status' => 'status',
            'internal_note' => 'internal_notes',
        );
    }

    return array(
        'product' => 'product_name',
        'product_title' => 'product_name',
        'launch_title' => 'launch_name',
        'tool' => 'tool_name',
        'company_name' => 'company',
        'company_profile_name' => 'company_profile',
        'official_link' => 'official_url',
        'official_website' => 'official_url',
        'website' => 'official_url',
        'launch_url' => 'official_url',
        'what_was_launched' => 'what_launched',
        'who_is_it_for' => 'who_it_is_for',
        'target_audience' => 'audience',
        'has_free_plan' => 'free_plan',
        'api' => 'api_available',
        'has_api' => 'api_available',
        'open_source' => 'open_source_or_open_weight',
        'open_weight' => 'open_source_or_open_weight',
        'open_source_open_weight' => 'open_source_or_open_weight',
        'demo_link' => 'demo_url',
        'demo_video' => 'demo_url',
        'demo_video_link' => 'demo_url',
        'product_hunt' => 'product_hunt_url',
        'product_hunt_link' => 'product_hunt_url',
        'github' => 'github_url',
        'github_link' => 'github_url',
        'git_hub_url' => 'github_url',
        'git_hub_link' => 'github_url',
        'hugging_face' => 'huggingface_url',
        'hugging_face_url' => 'huggingface_url',
        'hugging_face_link' => 'huggingface_url',
        'hf_url' => 'huggingface_url',
        'twitter_url' => 'x_url',
        'x_social' => 'x_url',
        'x_social_link' => 'x_url',
        'youtube_link' => 'youtube_url',
        'youtube_demo' => 'youtube_url',
        'youtube_demo_link' => 'youtube_url',
        'you_tube_url' => 'youtube_url',
        'you_tube_link' => 'youtube_url',
        'you_tube_demo' => 'youtube_url',
        'you_tube_demo_link' => 'youtube_url',
        'funding_announcement' => 'funding',
        'funding_announcement_link' => 'funding',
        'funding_url' => 'funding',
        'press_kit' => 'press_kit_url',
        'press_kit_link' => 'press_kit_url',
        'founder' => 'founder_team',
        'founders' => 'founder_team',
        'community_signal' => 'reddit_signal',
        'kingy_take' => 'kingy_verdict',
        'kingy_ai_take' => 'kingy_verdict',
        'verdict' => 'kingy_verdict',
        'promising' => 'what_feels_promising',
        'unproven' => 'what_feels_unproven',
        'launch_score' => 'kingy_launch_score',
        'kingy_score' => 'kingy_launch_score',
        'demo_score' => 'demo_quality_score',
        'youtube_content_score' => 'youtube_score',
        'youtube_potential_score' => 'youtube_score',
        'you_tube_content_score' => 'youtube_score',
        'you_tube_potential_score' => 'youtube_score',
        'seo_content_score' => 'seo_score',
        'partner_fit_score' => 'sponsor_fit_score_internal',
        'internal_partner_fit_score' => 'sponsor_fit_score_internal',
        'creator_campaign_score' => 'sponsor_fit_score_internal',
        'related_article' => 'related_article_url',
        'related_course' => 'related_course_url',
        'related_review' => 'related_review_url',
        'related_alternatives' => 'related_alternatives_url',
        'related_calculator' => 'related_calculator_url',
        'best_next_link' => 'best_next_link_url',
        'best_next_url' => 'best_next_link_url',
        'youtube_coverage_interest' => 'youtube_interest',
        'creator_campaign_interest' => 'sponsorship_interest',
        'creator_campaign_review_interest' => 'sponsorship_interest',
        'creator_coverage_review_interest' => 'creator_coverage_interest',
        'visibility_interest' => 'visibility_score_interest',
        'launch_visibility_score_interest' => 'visibility_score_interest',
        'budget_likelihood' => 'budget_likelihood_internal',
        'founder_note' => 'founder_notes',
        'internal_note' => 'internal_notes',
        'source' => 'sources',
        'source_links' => 'sources',
        'seo_meta_title' => 'seo_title',
        'seo_description' => 'meta_description',
        'target_query' => 'target_search_query',
        'featured_snippet' => 'featured_snippet_answer',
        'snippet_answer' => 'featured_snippet_answer',
        'publish_status' => 'status',
        'verification' => 'verification_status',
        'verified_date' => 'last_verified',
    );
}

function kingy_ali_import_normalize_column_key($key, $import_type = 'launches') {
    $raw = trim((string) $key);
    $raw = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $raw);
    $raw = str_replace('&', ' and ', $raw);
    $normalized = strtolower((string) $raw);
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
    $normalized = trim((string) $normalized, '_');

    $aliases = kingy_ali_import_column_aliases($import_type);
    if (isset($aliases[$normalized])) {
        return $aliases[$normalized];
    }

    return sanitize_key($normalized);
}

function kingy_ali_render_seed_checklist() {
    $items = kingy_ali_seed_checklist_items();

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Seed target', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Current', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Target', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Status', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($items as $item) {
        $complete = $item['current'] >= $item['target'];
        echo '<tr>';
        echo '<td>' . esc_html($item['label']) . '</td>';
        echo '<td>' . esc_html((string) $item['current']) . '</td>';
        echo '<td>' . esc_html((string) $item['target']) . '</td>';
        echo '<td>' . esc_html($complete ? __('Ready', 'kingy-ai-launch-intelligence') : __('Needs more data', 'kingy-ai-launch-intelligence')) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_seed_checklist_items() {
    return array(
        array(
            'label' => __('Launch records', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_post_count('kingy_ai_launch'),
            'target' => 50,
        ),
        array(
            'label' => __('Tool profiles', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_post_count('kingy_ai_tool'),
            'target' => 25,
        ),
        array(
            'label' => __('Launch categories', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_term_count('kingy_launch_category'),
            'target' => 10,
        ),
        array(
            'label' => __('Curated category pages', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_curated_page_count(),
            'target' => 5,
        ),
        array(
            'label' => __('Submission form page', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_shortcode_page_count('ai-launches/submit', 'kingy_launch_submit_form'),
            'target' => 1,
        ),
        array(
            'label' => __('Launch Visibility Score calculator page', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_shortcode_page_count('ai-launches/launch-visibility-score', 'kingy_launch_visibility_score'),
            'target' => 1,
        ),
        array(
            'label' => __('AI Launch Scorecard page', 'kingy-ai-launch-intelligence'),
            'current' => kingy_ali_seed_shortcode_page_count('ai-launch-scorecard', 'kingy_ai_launch_scorecard'),
            'target' => 1,
        ),
    );
}

function kingy_ali_seed_post_count($post_type) {
    $counts = wp_count_posts($post_type);
    if (!$counts) {
        return 0;
    }

    $total = 0;
    foreach (array('publish', 'pending', 'draft') as $status) {
        $total += isset($counts->$status) ? (int) $counts->$status : 0;
    }

    return $total;
}

function kingy_ali_seed_term_count($taxonomy) {
    $count = wp_count_terms(
        array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        )
    );

    return is_wp_error($count) ? 0 : (int) $count;
}

function kingy_ali_seed_curated_page_paths() {
    return array_keys(kingy_ali_seed_curated_page_shortcodes());
}

function kingy_ali_seed_curated_page_shortcodes() {
    return array(
        'ai-launches/ai-agents' => 'kingy_launch_grid',
        'ai-launches/ai-video-tools' => 'kingy_launch_grid',
        'ai-launches/ai-coding-tools' => 'kingy_launch_grid',
        'ai-launches/ai-image-tools' => 'kingy_launch_grid',
        'ai-launches/open-weight-models' => 'kingy_launch_grid',
        'ai-sponsored-video-roi-calculator' => 'kingy_creator_campaign_roi_calculator',
        'youtube-sponsorship-roi-calculator' => 'kingy_sponsorship_roi_comparison_page',
        'influencer-marketing-cac-calculator' => 'kingy_sponsorship_roi_comparison_page',
        'creator-sponsorship-payback-calculator' => 'kingy_sponsorship_roi_comparison_page',
        'ai-product-sponsorship-calculator' => 'kingy_sponsorship_roi_comparison_page',
        'youtube-sponsorship-rate-vs-roi-calculator' => 'kingy_sponsorship_roi_comparison_page',
    );
}

function kingy_ali_seed_curated_page_count() {
    $count = 0;
    foreach (kingy_ali_seed_curated_page_shortcodes() as $path => $shortcode_tag) {
        if (kingy_ali_seed_page_is_ready($path, $shortcode_tag)) {
            $count++;
        }
    }

    return $count;
}

function kingy_ali_seed_shortcode_page_count($path, $shortcode_tag) {
    return kingy_ali_seed_page_is_ready($path, $shortcode_tag) ? 1 : 0;
}

function kingy_ali_seed_page_is_ready($path, $shortcode_tag = '') {
    $page = function_exists('kingy_ali_find_page_by_path') ? kingy_ali_find_page_by_path($path) : get_page_by_path($path, OBJECT, 'page');
    if (!$page) {
        return false;
    }

    if (isset($page->post_status) && $page->post_status !== 'publish') {
        return false;
    }

    $shortcode_tag = sanitize_key($shortcode_tag);
    if ($shortcode_tag === '') {
        return true;
    }

    $content = isset($page->post_content) ? (string) $page->post_content : '';
    if (function_exists('has_shortcode') && has_shortcode($content, $shortcode_tag)) {
        return true;
    }

    return strpos($content, '[' . $shortcode_tag) !== false;
}

function kingy_ali_handle_download_import_template() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download the import template.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_download_import_template');

    $filename = 'kingy-ai-launch-intelligence-import-template.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }

    kingy_ali_fputcsv($output, kingy_ali_import_column_keys());
    fclose($output);
    exit;
}

function kingy_ali_handle_download_model_import_template() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download the model import template.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_download_model_import_template');

    $filename = 'kingy-ai-model-import-template.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }

    kingy_ali_fputcsv($output, kingy_ali_model_import_column_keys());
    fclose($output);
    exit;
}

function kingy_ali_handle_download_starter_seed() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download the starter seed.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_download_starter_seed');

    $filename = 'kingy-ai-launch-intelligence-starter-seed.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }

    $columns = kingy_ali_import_column_keys();
    kingy_ali_fputcsv($output, $columns);

    foreach (kingy_ali_starter_seed_records() as $record) {
        $row = array();
        foreach ($columns as $column) {
            $row[] = isset($record[$column]) ? $record[$column] : '';
        }
        kingy_ali_fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function kingy_ali_starter_seed_records() {
    $records = array();
    $tools = kingy_ali_starter_seed_tools();
    $launch_types = array('new-product', 'major-update', 'model-release', 'open-source-release', 'funding');
    $base_timestamp = current_time('timestamp');

    foreach ($tools as $index => $tool) {
        $first_date = date_i18n('Y-m-d', $base_timestamp - ($index * DAY_IN_SECONDS));
        $second_date = date_i18n('Y-m-d', $base_timestamp - (($index + 25) * DAY_IN_SECONDS));
        $secondary_type = $launch_types[$index % count($launch_types)];

        $records[] = kingy_ali_starter_seed_record($tool, $first_date, 'new-product', __('Starter Launch', 'kingy-ai-launch-intelligence'), $index);
        $records[] = kingy_ali_starter_seed_record($tool, $second_date, $secondary_type, __('Follow-Up Update', 'kingy-ai-launch-intelligence'), $index + 25);
    }

    return $records;
}

function kingy_ali_starter_seed_tools() {
    return array(
        array('product' => 'Example AgentOps Studio', 'company' => 'Example Labs', 'category' => 'ai-agents', 'audience' => 'founders', 'use_case' => 'repeatable agent workflows', 'pricing' => 'Free plan plus paid team tier', 'open_weight' => 'no'),
        array('product' => 'Example Video Remix Desk', 'company' => 'SampleFrame AI', 'category' => 'ai-video-tools', 'audience' => 'creators', 'use_case' => 'short-form video experiments', 'pricing' => 'Paid plan with trial', 'open_weight' => 'no'),
        array('product' => 'Example Code Review Pilot', 'company' => 'Draftware', 'category' => 'ai-coding-tools', 'audience' => 'developers', 'use_case' => 'pull request review', 'pricing' => 'Usage based developer plan', 'open_weight' => 'no'),
        array('product' => 'Example Open Model Forge', 'company' => 'Open Sample Collective', 'category' => 'open-weight-models', 'audience' => 'researchers', 'use_case' => 'local model evaluation', 'pricing' => 'Open weights with hosted option', 'open_weight' => 'yes'),
        array('product' => 'Example Image Brief Builder', 'company' => 'Visual Seed Co', 'category' => 'ai-image-tools', 'audience' => 'marketers', 'use_case' => 'campaign image briefs', 'pricing' => 'Freemium creative plan', 'open_weight' => 'no'),
        array('product' => 'Example Research Searcher', 'company' => 'QueryFoundry', 'category' => 'ai-research-tools', 'audience' => 'researchers', 'use_case' => 'source-backed research', 'pricing' => 'Free research tier', 'open_weight' => 'no'),
        array('product' => 'Example Browser Agent Kit', 'company' => 'TabPilot Systems', 'category' => 'ai-browser-agents', 'audience' => 'agencies', 'use_case' => 'browser task automation', 'pricing' => 'Team plan', 'open_weight' => 'no'),
        array('product' => 'Example Productivity Copilot', 'company' => 'Flowpad AI', 'category' => 'ai-productivity-tools', 'audience' => 'small-business-owners', 'use_case' => 'daily operations planning', 'pricing' => 'Free personal tier', 'open_weight' => 'no'),
        array('product' => 'Example Writing Refiner', 'company' => 'ClearDraft', 'category' => 'ai-writing-tools', 'audience' => 'marketers', 'use_case' => 'launch copy refinement', 'pricing' => 'Paid workspace plan', 'open_weight' => 'no'),
        array('product' => 'Example Voice Demo Lab', 'company' => 'Sonic Sample AI', 'category' => 'ai-voice-audio-tools', 'audience' => 'creators', 'use_case' => 'voiceover prototyping', 'pricing' => 'Creator plan', 'open_weight' => 'no'),
        array('product' => 'Example Inference Monitor', 'company' => 'Stack Signal', 'category' => 'ai-infrastructure', 'audience' => 'developers', 'use_case' => 'model operations monitoring', 'pricing' => 'Usage based infrastructure plan', 'open_weight' => 'no'),
        array('product' => 'Example Campaign Agent', 'company' => 'MarketLoop AI', 'category' => 'ai-marketing-tools', 'audience' => 'agencies', 'use_case' => 'campaign workflow planning', 'pricing' => 'Agency plan', 'open_weight' => 'no'),
        array('product' => 'Example Sales Call Summarizer', 'company' => 'Revenue Notes', 'category' => 'ai-automation-tools', 'audience' => 'small-business-owners', 'use_case' => 'customer call follow-up automation', 'pricing' => 'Seat based plan', 'open_weight' => 'no'),
        array('product' => 'Example Data Agent Builder', 'company' => 'TableMind', 'category' => 'ai-agents', 'audience' => 'enterprises', 'use_case' => 'internal data workflows', 'pricing' => 'Enterprise pilot', 'open_weight' => 'no'),
        array('product' => 'Example Shorts Generator', 'company' => 'ClipCircuit', 'category' => 'ai-video-tools', 'audience' => 'youtubers', 'use_case' => 'YouTube Shorts repurposing', 'pricing' => 'Creator subscription', 'open_weight' => 'no'),
        array('product' => 'Example Local Code Agent', 'company' => 'LocalPatch', 'category' => 'ai-developer-tools', 'audience' => 'developers', 'use_case' => 'local coding assistance', 'pricing' => 'Open source core plus pro', 'open_weight' => 'yes'),
        array('product' => 'Example Compact Reasoner', 'company' => 'Tiny Model Works', 'category' => 'ai-local-models', 'audience' => 'students', 'use_case' => 'small local model experiments', 'pricing' => 'Open model release', 'open_weight' => 'yes'),
        array('product' => 'Example Citation Scout', 'company' => 'SourceGrid', 'category' => 'ai-search-tools', 'audience' => 'researchers', 'use_case' => 'citation discovery', 'pricing' => 'Researcher plan', 'open_weight' => 'no'),
        array('product' => 'Example Inbox Agent', 'company' => 'MailPilot AI', 'category' => 'ai-agents', 'audience' => 'small-business-owners', 'use_case' => 'inbox triage', 'pricing' => 'Paid plan with trial', 'open_weight' => 'no'),
        array('product' => 'Example Meeting Clip Maker', 'company' => 'Recap Studio', 'category' => 'ai-video-tools', 'audience' => 'marketers', 'use_case' => 'webinar clip generation', 'pricing' => 'Team subscription', 'open_weight' => 'no'),
        array('product' => 'Example Docs Automation SDK', 'company' => 'DocuAgent Labs', 'category' => 'ai-developer-tools', 'audience' => 'developers', 'use_case' => 'document automation SDKs', 'pricing' => 'API usage plan', 'open_weight' => 'no'),
        array('product' => 'Example Music Stem Mixer', 'company' => 'AudioClean AI', 'category' => 'ai-music-tools', 'audience' => 'creators', 'use_case' => 'music stem cleanup and remixing', 'pricing' => 'Creator plan', 'open_weight' => 'no'),
        array('product' => 'Example Safety Red Teamer', 'company' => 'RankFlow', 'category' => 'ai-security-tools', 'audience' => 'enterprises', 'use_case' => 'AI app risk review', 'pricing' => 'Security workspace', 'open_weight' => 'no'),
        array('product' => 'Example Robot Route Planner', 'company' => 'MotionDock', 'category' => 'ai-robotics', 'audience' => 'enterprises', 'use_case' => 'robot fleet planning demos', 'pricing' => 'Pilot pricing', 'open_weight' => 'no'),
        array('product' => 'Example Edge AI Camera Kit', 'company' => 'SensorForge', 'category' => 'ai-hardware', 'audience' => 'developers', 'use_case' => 'edge AI hardware prototypes', 'pricing' => 'Hardware kit plus cloud plan', 'open_weight' => 'no'),
    );
}

function kingy_ali_starter_seed_record($tool, $date, $launch_type, $suffix, $index) {
    $product = $tool['product'];
    $company = $tool['company'];
    $score_offset = ($index % 5) / 10;
    $official_slug = sanitize_title($product);

    return array(
        'product_name' => $product,
        'launch_name' => sprintf('%1$s %2$s', $product, $suffix),
        'tool_name' => $product,
        'company' => $company,
        'launch_date' => $date,
        'launch_type' => $launch_type,
        'category' => $tool['category'],
        'audience' => $tool['audience'],
        'official_url' => 'https://example.com/' . $official_slug,
        'what_launched' => sprintf('Draft starter row for %1$s focused on %2$s. Replace this with verified launch details before publishing.', $product, $tool['use_case']),
        'who_it_is_for' => sprintf('Built for %1$s exploring %2$s.', str_replace('-', ' ', $tool['audience']), $tool['use_case']),
        'pricing' => $tool['pricing'],
        'free_plan' => strpos(strtolower($tool['pricing']), 'free') !== false ? 'yes' : 'no',
        'api_available' => in_array($tool['audience'], array('developers', 'enterprises'), true) ? 'yes' : '',
        'open_source_or_open_weight' => $tool['open_weight'],
        'demo_url' => 'https://example.com/' . $official_slug . '/demo',
        'product_hunt_url' => $index % 4 === 0 ? 'https://example.com/' . $official_slug . '/product-hunt' : '',
        'github_url' => $tool['open_weight'] === 'yes' ? 'https://example.com/' . $official_slug . '/code' : '',
        'huggingface_url' => $tool['open_weight'] === 'yes' ? 'https://example.com/' . $official_slug . '/model' : '',
        'x_url' => 'https://example.com/' . $official_slug . '/social',
        'youtube_url' => 'https://example.com/' . $official_slug . '/video',
        'funding' => '',
        'press_kit_url' => 'https://example.com/' . $official_slug . '/press',
        'founder_team' => $company . ' founder team',
        'reddit_signal' => 'Replace with verified community signal if available.',
        'youtube_signal' => 'Demo angle available for creator review.',
        'traction_notes' => 'Starter placeholder for traction notes.',
        'kingy_launch_score' => round(7.0 + $score_offset, 1),
        'demo_quality_score' => round(6.8 + $score_offset, 1),
        'youtube_score' => round(7.1 + $score_offset, 1),
        'seo_score' => round(6.9 + $score_offset, 1),
        'sponsor_fit_score_internal' => round(6.6 + $score_offset, 1),
        'kingy_verdict' => 'Promising starter record for Launch Radar planning.',
        'what_feels_promising' => 'Clear use case and demo angle for editorial testing.',
        'what_feels_unproven' => 'Needs verified sources and real traction before publishing.',
        'related_article_url' => '',
        'related_course_url' => '',
        'related_review_url' => '',
        'related_alternatives_url' => '',
        'related_calculator_url' => '',
        'best_next_link_url' => 'https://example.com/' . $official_slug,
        'best_next_link_label' => 'Official launch page',
        'youtube_interest' => 'yes',
        'creator_coverage_interest' => 'yes',
        'sponsorship_interest' => '',
        'visibility_score_interest' => 'yes',
        'budget_likelihood_internal' => 'medium',
        'founder_notes' => 'Starter row generated for MVP seed testing.',
        'internal_notes' => 'Demo seed record. Replace with verified launch data before publishing.',
        'sources' => 'https://example.com/source',
        'seo_title' => sprintf('%s launch details and creator coverage angle', $product),
        'meta_description' => sprintf('Draft Launch Intelligence record for %1$s by %2$s, including launch facts, pricing, demo links, traction signals, and Kingy AI scores.', $product, $company),
        'target_search_query' => strtolower($product) . ' launch',
        'featured_snippet_answer' => sprintf('%1$s is a draft starter record for %2$s. Replace this answer with verified launch facts before publishing.', $product, $tool['use_case']),
        'status' => 'draft',
        'verification_status' => 'partially_verified',
        'last_verified' => $date,
    );
}

function kingy_ali_import_summary_from_request() {
    if (kingy_ali_import_summary_request_value('kingy_ali_import_complete') === '') {
        return array();
    }

    return array(
        'created' => absint(kingy_ali_import_summary_request_value('created')),
        'updated' => absint(kingy_ali_import_summary_request_value('updated')),
        'skipped' => absint(kingy_ali_import_summary_request_value('skipped')),
        'failed' => absint(kingy_ali_import_summary_request_value('failed')),
        'total' => absint(kingy_ali_import_summary_request_value('total')),
        'type' => kingy_ali_import_type_from_request(),
    );
}

function kingy_ali_import_summary_request_value($key) {
    $values = kingy_ali_import_summary_request_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_import_post_value($key) {
    $values = kingy_ali_import_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_import_uploaded_file($key) {
    $files = kingy_ali_import_uploaded_files();
    return isset($files[$key]) && is_array($files[$key]) ? $files[$key] : array();
}

function kingy_ali_import_summary_request_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_import_post_values() {
    return is_array($_POST) ? $_POST : array();
}

function kingy_ali_import_uploaded_files() {
    return is_array($_FILES) ? $_FILES : array();
}

function kingy_ali_import_diagnostics_key() {
    return 'kingy_ali_import_diagnostics_' . absint(get_current_user_id());
}

function kingy_ali_get_last_import_diagnostics() {
    $diagnostics = get_transient(kingy_ali_import_diagnostics_key());
    delete_transient(kingy_ali_import_diagnostics_key());

    return is_array($diagnostics) ? $diagnostics : array();
}

function kingy_ali_render_import_diagnostics($diagnostics) {
    if (empty($diagnostics)) {
        return;
    }

    echo '<h2>' . esc_html__('Import diagnostics', 'kingy-ai-launch-intelligence') . '</h2>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>' . esc_html__('Row', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Status', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Record', 'kingy-ai-launch-intelligence') . '</th>';
    echo '<th>' . esc_html__('Message', 'kingy-ai-launch-intelligence') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($diagnostics as $diagnostic) {
        echo '<tr>';
        echo '<td>' . esc_html((string) absint(kingy_ali_import_diagnostic_value($diagnostic, 'row'))) . '</td>';
        echo '<td>' . esc_html(ucfirst(sanitize_key(kingy_ali_import_diagnostic_value($diagnostic, 'status')))) . '</td>';
        echo '<td>' . esc_html(sanitize_text_field(kingy_ali_import_diagnostic_value($diagnostic, 'record'))) . '</td>';
        echo '<td>' . esc_html(sanitize_text_field(kingy_ali_import_diagnostic_value($diagnostic, 'message'))) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function kingy_ali_import_diagnostic_value($diagnostic, $key) {
    if (!is_array($diagnostic) || !isset($diagnostic[$key]) || !is_scalar($diagnostic[$key])) {
        return '';
    }

    return (string) $diagnostic[$key];
}

function kingy_ali_handle_admin_import() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to import launch records.', 'kingy-ai-launch-intelligence'));
    }

    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_import_post_value('kingy_ali_import_nonce')), 'kingy_ali_import')) {
        wp_die(esc_html__('Import nonce check failed.', 'kingy-ai-launch-intelligence'));
    }

    $file = kingy_ali_import_uploaded_file('kingy_ali_import_file');
    $upload_error = kingy_ali_import_uploaded_file_error($file);
    if ($upload_error) {
        wp_die(esc_html($upload_error));
    }

    $import_type = kingy_ali_import_type_from_post();
    $filename = isset($file['name']) ? sanitize_file_name($file['name']) : '';
    $tmp_name = isset($file['tmp_name']) && is_scalar($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    $diagnostics = array();
    $records = kingy_ali_read_import_records($tmp_name, $diagnostics, $filename, $import_type);
    $first_data_row = kingy_ali_import_first_data_row_number($filename);

    $summary = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => kingy_ali_import_diagnostic_count($diagnostics, 'failed'),
        'total' => count($records),
    );

    foreach ($records as $index => $record) {
        if (!is_array($record) || !kingy_ali_import_is_associative_array($record)) {
            $summary['failed']++;
            $diagnostics[] = array(
                'row' => $index + $first_data_row,
                'status' => 'failed',
                'record' => '',
                'message' => __('Import rows must be objects or associative arrays.', 'kingy-ai-launch-intelligence'),
            );
            continue;
        }

        $result = $import_type === 'models'
            ? kingy_ali_import_model_record_result($record)
            : kingy_ali_import_launch_record_result($record);
        $status = isset($result['status']) ? sanitize_key($result['status']) : 'failed';
        if (!isset($summary[$status])) {
            $status = 'failed';
        }

        $summary[$status]++;
        if (in_array($status, array('skipped', 'failed'), true)) {
            $diagnostics[] = array(
                'row' => $index + $first_data_row,
                'status' => $status,
                'record' => isset($result['record']) ? $result['record'] : '',
                'message' => isset($result['message']) ? $result['message'] : __('Unable to import this row.', 'kingy-ai-launch-intelligence'),
            );
        }
    }

    if ($diagnostics) {
        set_transient(kingy_ali_import_diagnostics_key(), array_slice($diagnostics, 0, 50), 10 * MINUTE_IN_SECONDS);
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'kingy_ali_import_complete' => '1',
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'skipped' => $summary['skipped'],
                'failed' => $summary['failed'],
                'total' => $summary['total'],
                'import_type' => $import_type,
            ),
            admin_url('admin.php?page=kingy-ali-import')
        )
    );
    exit;
}

function kingy_ali_import_allowed_file_extensions() {
    return array('csv', 'json');
}

function kingy_ali_import_file_extension($filename) {
    return strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
}

function kingy_ali_import_max_file_size() {
    $size = 5 * 1024 * 1024;
    if (function_exists('apply_filters')) {
        $size = apply_filters('kingy_ali_import_max_file_size', $size);
    }

    return max(1024, absint($size));
}

function kingy_ali_import_upload_error_message($error_code) {
    $messages = array(
        UPLOAD_ERR_INI_SIZE => __('The import file exceeds the server upload limit.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_FORM_SIZE => __('The import file exceeds the form upload limit.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_PARTIAL => __('The import file was only partially uploaded.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_NO_FILE => __('No import file was uploaded.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_NO_TMP_DIR => __('The server is missing a temporary upload folder.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_CANT_WRITE => __('The import file could not be written to disk.', 'kingy-ai-launch-intelligence'),
        UPLOAD_ERR_EXTENSION => __('A server extension stopped the import upload.', 'kingy-ai-launch-intelligence'),
    );

    return isset($messages[$error_code]) ? $messages[$error_code] : __('The import file upload failed.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_import_uploaded_file_error($file) {
    if (!is_array($file)) {
        return __('No import file was uploaded.', 'kingy-ai-launch-intelligence');
    }

    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK;
    if ($error !== UPLOAD_ERR_OK) {
        return kingy_ali_import_upload_error_message($error);
    }

    $filename = isset($file['name']) && is_scalar($file['name']) ? sanitize_file_name($file['name']) : '';
    $tmp_name = isset($file['tmp_name']) && is_scalar($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    if ($filename === '' || $tmp_name === '') {
        return __('No import file was uploaded.', 'kingy-ai-launch-intelligence');
    }

    $extension = kingy_ali_import_file_extension($filename);
    if (!in_array($extension, kingy_ali_import_allowed_file_extensions(), true)) {
        return __('Import file must be a CSV or JSON file.', 'kingy-ai-launch-intelligence');
    }

    if (!is_file($tmp_name) || !is_readable($tmp_name)) {
        return __('Import file could not be read.', 'kingy-ai-launch-intelligence');
    }

    $size = isset($file['size']) && is_scalar($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0 && is_readable($tmp_name)) {
        $filesize = filesize($tmp_name);
        $size = $filesize === false ? 0 : (int) $filesize;
    }

    if ($size <= 0) {
        return __('Import file is empty.', 'kingy-ai-launch-intelligence');
    }

    if ($size > kingy_ali_import_max_file_size()) {
        return __('Import file is too large. Upload a smaller CSV or JSON file.', 'kingy-ai-launch-intelligence');
    }

    return '';
}

function kingy_ali_import_diagnostic_count($diagnostics, $status) {
    $count = 0;
    foreach ($diagnostics as $diagnostic) {
        if (isset($diagnostic['status']) && $diagnostic['status'] === $status) {
            $count++;
        }
    }

    return $count;
}

function kingy_ali_read_import_records($path, &$diagnostics = array(), $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    $extension = kingy_ali_import_file_extension($filename);
    if ($extension === 'json') {
        return kingy_ali_read_json_records($path, $diagnostics, $filename, $import_type);
    }

    if ($extension === 'csv') {
        return kingy_ali_read_csv_records($path, $diagnostics, $filename, $import_type);
    }

    $diagnostics[] = array(
        'row' => 0,
        'status' => 'failed',
        'record' => sanitize_file_name((string) $filename),
        'message' => __('Import file must be a CSV or JSON file.', 'kingy-ai-launch-intelligence'),
    );
    return array();
}

function kingy_ali_import_first_data_row_number($filename = '') {
    $extension = kingy_ali_import_file_extension($filename);
    return $extension === 'json' ? 1 : 2;
}

function kingy_ali_read_json_records($path, &$diagnostics = array(), $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    $contents = file_get_contents($path);
    if ($contents === false) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'failed',
            'record' => $filename,
            'message' => __('JSON file could not be opened.', 'kingy-ai-launch-intelligence'),
        );
        return array();
    }

    $decoded = json_decode($contents, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'failed',
            'record' => $filename,
            'message' => sprintf(
                __('JSON import failed: %s.', 'kingy-ai-launch-intelligence'),
                json_last_error_msg()
            ),
        );
        return array();
    }

    $records = kingy_ali_normalize_json_import_records($decoded, $diagnostics, $filename, $import_type);
    $diagnostics = array_merge($diagnostics, kingy_ali_import_records_preflight_diagnostics($records, $filename, $import_type));

    return $records;
}

function kingy_ali_normalize_json_import_records($decoded, &$diagnostics = array(), $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    if (!is_array($decoded)) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'failed',
            'record' => $filename,
            'message' => __('JSON import must contain records as objects.', 'kingy-ai-launch-intelligence'),
        );
        return array();
    }

    $records = $decoded;
    foreach ($decoded as $container_key => $container_records) {
        if (in_array(kingy_ali_import_normalize_column_key($container_key, $import_type), array('records', 'launches', 'models', 'rows', 'data'), true) && is_array($container_records)) {
            $records = $container_records;
            break;
        }
    }

    if (kingy_ali_import_is_associative_array($records) && kingy_ali_import_record_has_supported_key($records, $import_type)) {
        $records = array($records);
    }

    if (!is_array($records) || kingy_ali_import_is_associative_array($records)) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'failed',
            'record' => $filename,
            'message' => __('JSON import must be an array of record objects or an object with records, launches, rows, or data.', 'kingy-ai-launch-intelligence'),
        );
        return array();
    }

    $normalized = array();
    foreach ($records as $record) {
        $normalized[] = kingy_ali_normalize_json_import_record($record, $import_type);
    }

    return $normalized;
}

function kingy_ali_normalize_json_import_record($record, $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    if (!is_array($record) || !kingy_ali_import_is_associative_array($record)) {
        return $record;
    }

    $normalized = array();
    foreach ($record as $key => $value) {
        $column_key = kingy_ali_import_normalize_column_key($key, $import_type);
        $normalized[$column_key] = kingy_ali_normalize_json_import_cell($value, $column_key);
    }

    return $normalized;
}

function kingy_ali_normalize_json_import_cell($value, $key = '') {
    if (is_null($value)) {
        return '';
    }

    if (is_bool($value)) {
        return $value ? 'yes' : 'no';
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    if (!is_array($value)) {
        return '';
    }

    if (substr($key, -4) === '_url' || in_array($key, array('official_url', 'demo_url', 'product_hunt_url', 'github_url', 'huggingface_url', 'x_url', 'youtube_url'), true)) {
        $url = kingy_ali_first_json_import_url($value);
        if ($url) {
            return $url;
        }
    }

    $items = kingy_ali_json_import_scalar_items($value);
    $separator = in_array($key, array('category', 'audience', 'launch_type', 'model_provider', 'model_family', 'model_modality', 'model_use_case', 'model_access_type', 'model_license_type', 'model_status'), true) ? ', ' : "\n";

    return implode($separator, $items);
}

function kingy_ali_first_json_import_url($value) {
    if (is_string($value) && preg_match('#^https?://#i', $value)) {
        return $value;
    }

    if (!is_array($value)) {
        return '';
    }

    foreach (array('url', 'href', 'link') as $url_key) {
        if (!empty($value[$url_key]) && is_string($value[$url_key])) {
            return $value[$url_key];
        }
    }

    foreach ($value as $child) {
        $url = kingy_ali_first_json_import_url($child);
        if ($url) {
            return $url;
        }
    }

    return '';
}

function kingy_ali_json_import_scalar_items($value) {
    if (is_null($value)) {
        return array();
    }

    if (is_bool($value)) {
        return array($value ? 'yes' : 'no');
    }

    if (is_scalar($value)) {
        $text = trim((string) $value);
        return $text === '' ? array() : array($text);
    }

    if (!is_array($value)) {
        return array();
    }

    $items = array();
    foreach ($value as $child_key => $child_value) {
        if (kingy_ali_import_is_associative_array($value) && is_scalar($child_value)) {
            $text = trim((string) $child_value);
            if ($text !== '') {
                $items[] = sanitize_key($child_key) . ': ' . $text;
            }
            continue;
        }

        $items = array_merge($items, kingy_ali_json_import_scalar_items($child_value));
    }

    return $items;
}

function kingy_ali_import_record_has_supported_key($record, $import_type = 'launches') {
    if (!is_array($record)) {
        return false;
    }

    $supported = kingy_ali_import_supported_column_keys($import_type);
    foreach (array_keys($record) as $key) {
        if (in_array(kingy_ali_import_normalize_column_key($key, $import_type), $supported, true)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_import_is_associative_array($value) {
    if (!is_array($value)) {
        return false;
    }

    if ($value === array()) {
        return false;
    }

    return array_keys($value) !== range(0, count($value) - 1);
}

function kingy_ali_read_csv_records($path, &$diagnostics = array(), $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    $handle = fopen($path, 'r');
    if (!$handle) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'failed',
            'record' => $filename,
            'message' => __('CSV file could not be opened.', 'kingy-ai-launch-intelligence'),
        );
        return array();
    }

    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('CSV file has no header row.', 'kingy-ai-launch-intelligence'),
        );
        return array();
    }

    $headers = array_map(
        function ($header) use ($import_type) {
            return kingy_ali_import_normalize_column_key($header, $import_type);
        },
        $headers
    );
    $diagnostics = array_merge($diagnostics, kingy_ali_import_header_diagnostics($headers, $filename, $import_type));

    $records = array();
    while (($row = fgetcsv($handle)) !== false) {
        $record = array();
        foreach ($headers as $index => $header) {
            $record[$header] = isset($row[$index]) ? $row[$index] : '';
        }
        $records[] = $record;
    }
    fclose($handle);

    $diagnostics = array_merge($diagnostics, kingy_ali_import_indexability_value_diagnostics($records, $filename, 2, $import_type));

    return $records;
}

function kingy_ali_import_required_columns() {
    return array('product_name');
}

function kingy_ali_import_recommended_columns() {
    return array('launch_date', 'category', 'official_url', 'what_launched', 'pricing', 'kingy_verdict', 'last_verified');
}

function kingy_ali_import_header_diagnostics($headers, $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    $headers = array_values(
        array_filter(
            array_unique(
                array_map(
                    function ($header) use ($import_type) {
                        return kingy_ali_import_normalize_column_key($header, $import_type);
                    },
                    (array) $headers
                )
            )
        )
    );
    if ($import_type === 'models') {
        return kingy_ali_model_import_header_diagnostics($headers, $filename);
    }

    $supported = kingy_ali_import_column_keys();
    $diagnostics = array();
    $export_shape_warning = kingy_ali_import_export_shape_warning($headers, $filename, $import_type);
    if ($export_shape_warning) {
        $diagnostics[] = $export_shape_warning;
    }

    $unknown = array_values(array_diff($headers, $supported));
    if ($unknown) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Unsupported columns will be ignored: %s.', 'kingy-ai-launch-intelligence'),
                implode(', ', array_slice($unknown, 0, 12))
            ),
        );
    }

    $missing_required = array_values(array_diff(kingy_ali_import_required_columns(), $headers));
    if ($missing_required) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Missing required column: %s. Rows without product_name will be skipped.', 'kingy-ai-launch-intelligence'),
                implode(', ', $missing_required)
            ),
        );
    }

    $missing_recommended = array_values(array_diff(kingy_ali_import_recommended_columns(), $headers));
    if ($missing_recommended) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Recommended columns are missing: %s. Imports can continue, but the launch records may need manual completion.', 'kingy-ai-launch-intelligence'),
                implode(', ', $missing_recommended)
            ),
        );
    }

    if (!array_intersect($headers, kingy_ali_import_audience_readiness_columns())) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('Audience readiness columns are missing: include audience or who_it_is_for so launch profiles can satisfy indexability checks.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (!array_intersect($headers, kingy_ali_import_useful_link_readiness_columns())) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('Useful related/source link columns are missing: include at least one of demo_url, press_kit_url, sources, or a related/best-next link so launch profiles can satisfy indexability checks.', 'kingy-ai-launch-intelligence'),
        );
    }

    return $diagnostics;
}

function kingy_ali_model_import_header_diagnostics($headers, $filename = '') {
    $headers = array_values(array_filter(array_unique((array) $headers)));
    $supported = kingy_ali_model_import_column_keys();
    $diagnostics = array();
    $export_shape_warning = kingy_ali_import_export_shape_warning($headers, $filename, 'models');
    if ($export_shape_warning) {
        $diagnostics[] = $export_shape_warning;
    }

    $unknown = array_values(array_diff($headers, $supported));
    if ($unknown) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Unsupported model columns will be ignored: %s.', 'kingy-ai-launch-intelligence'),
                implode(', ', array_slice($unknown, 0, 12))
            ),
        );
    }

    $missing_required = array_values(array_diff(kingy_ali_model_import_required_columns(), $headers));
    if ($missing_required) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Missing required model column: %s. Rows without model_name will be skipped.', 'kingy-ai-launch-intelligence'),
                implode(', ', $missing_required)
            ),
        );
    }

    $missing_recommended = array_values(array_diff(kingy_ali_model_import_recommended_columns(), $headers));
    if ($missing_recommended) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('Recommended model columns are missing: %s. Imports can continue, but model profiles may remain noindexed until enriched.', 'kingy-ai-launch-intelligence'),
                implode(', ', $missing_recommended)
            ),
        );
    }

    if (!array_intersect($headers, kingy_ali_model_import_official_source_columns())) {
        $diagnostics[] = array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('Official source columns are missing: include official_url, official_announcement_url, official_docs_url, or model_card_url.', 'kingy-ai-launch-intelligence'),
        );
    }

    return $diagnostics;
}

function kingy_ali_import_export_shape_warning($headers, $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    $headers = array_values(
        array_filter(
            array_unique(
                array_map(
                    function ($header) use ($import_type) {
                        return kingy_ali_import_normalize_column_key($header, $import_type);
                    },
                    (array) $headers
                )
            )
        )
    );
    if ($import_type === 'models') {
        if (in_array('model_name', $headers, true)) {
            return array();
        }

        if (in_array('product_name', $headers, true) || in_array('launch_name', $headers, true)) {
            return array(
                'row' => 1,
                'status' => 'warning',
                'record' => $filename,
                'message' => __('This looks like a Launch CSV. Choose Launch records as the import type, or use the Model Import Template for AI model profiles.', 'kingy-ai-launch-intelligence'),
            );
        }
    }

    if (in_array('product_name', $headers, true)) {
        return array();
    }

    if (in_array('model_name', $headers, true)) {
        return array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('This looks like a Model CSV. Choose AI model profiles as the import type, or use Export Launch CSV for launch records.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (in_array('tool_name', $headers, true) && in_array('latest_launch', $headers, true)) {
        return array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('This looks like a Tool CSV export. Import accepts launch CSV/JSON rows only; use Export Launch CSV for re-importable spreadsheet edits, or update tool profiles in the editor.', 'kingy-ai-launch-intelligence'),
        );
    }

    if (in_array('company_name', $headers, true) && (in_array('launch_count', $headers, true) || in_array('tool_count', $headers, true))) {
        return array(
            'row' => 1,
            'status' => 'warning',
            'record' => $filename,
            'message' => __('This looks like a Company CSV export. Import accepts launch CSV/JSON rows only; use Export Launch CSV for re-importable spreadsheet edits, or update company profiles in the editor.', 'kingy-ai-launch-intelligence'),
        );
    }

    return array();
}

function kingy_ali_import_records_preflight_diagnostics($records, $filename = '', $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    if (empty($records)) {
        return array(
            array(
                'row' => 0,
                'status' => 'warning',
                'record' => $filename,
                'message' => __('Import file did not contain any records.', 'kingy-ai-launch-intelligence'),
            ),
        );
    }

    $headers = array();
    foreach ($records as $record) {
        if (is_array($record)) {
            $headers = array_merge($headers, array_keys($record));
        }
    }

    return array_merge(
        kingy_ali_import_header_diagnostics($headers, $filename, $import_type),
        kingy_ali_import_indexability_value_diagnostics($records, $filename, kingy_ali_import_first_data_row_number($filename), $import_type)
    );
}

function kingy_ali_model_import_required_columns() {
    return array('model_name');
}

function kingy_ali_model_import_recommended_columns() {
    return array('provider_name', 'model_provider', 'model_modality', 'official_url', 'model_overview', 'pricing', 'benchmark_caveat', 'verification_status', 'last_verified');
}

function kingy_ali_model_import_official_source_columns() {
    return array('official_url', 'official_announcement_url', 'official_docs_url', 'model_card_url');
}

function kingy_ali_import_audience_readiness_columns() {
    return array('audience', 'who_it_is_for');
}

function kingy_ali_import_useful_link_readiness_columns() {
    return array(
        'demo_url',
        'press_kit_url',
        'product_hunt_url',
        'github_url',
        'huggingface_url',
        'x_url',
        'youtube_url',
        'related_article_url',
        'related_course_url',
        'related_review_url',
        'related_alternatives_url',
        'related_calculator_url',
        'best_next_link_url',
        'sources',
    );
}

function kingy_ali_import_indexability_value_diagnostics($records, $filename = '', $first_data_row = 1, $import_type = 'launches') {
    $import_type = kingy_ali_sanitize_import_type($import_type);
    if ($import_type === 'models') {
        return kingy_ali_model_import_indexability_value_diagnostics($records, $filename, $first_data_row);
    }

    if (empty($records)) {
        return array();
    }

    $problem_rows = array();
    $missing_seen = array();

    foreach ($records as $index => $record) {
        if (!is_array($record) || !kingy_ali_import_is_associative_array($record)) {
            continue;
        }

        $missing = kingy_ali_import_indexability_missing_labels($record);
        if (!$missing) {
            continue;
        }

        $problem_rows[] = absint($index + $first_data_row);
        foreach ($missing as $label) {
            $missing_seen[$label] = true;
        }
    }

    if (!$problem_rows) {
        return array();
    }

    return array(
        array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('%1$d import rows are missing fields used by launch profile indexability checks. First rows: %2$s. Missing groups seen: %3$s. Imports can continue, but those profiles may stay noindexed until enriched.', 'kingy-ai-launch-intelligence'),
                count($problem_rows),
                implode(', ', array_slice($problem_rows, 0, 10)),
                implode(', ', array_keys($missing_seen))
            ),
        ),
    );
}

function kingy_ali_import_indexability_missing_labels($record) {
    $missing = array();
    $required = array(
        'official_url' => 'official_url',
        'launch_date' => 'launch_date',
        'what_launched' => 'what_launched',
        'category' => 'category',
        'pricing' => 'pricing',
        'kingy_verdict' => 'kingy_verdict',
        'last_verified' => 'last_verified',
    );

    foreach ($required as $key => $label) {
        if ($key === 'official_url' ? !kingy_ali_import_record_has_indexable_url($record, $key) : !kingy_ali_import_record_has_value($record, $key)) {
            $missing[] = $label;
        }
    }

    if (
        kingy_ali_import_record_has_value($record, 'pricing')
        && function_exists('kingy_ali_pricing_is_indexable')
        && !kingy_ali_pricing_is_indexable($record['pricing'])
    ) {
        $missing[] = 'known pricing';
    }

    if (!kingy_ali_import_record_has_any_value($record, kingy_ali_import_audience_readiness_columns())) {
        $missing[] = 'audience or who_it_is_for';
    }

    if (!kingy_ali_import_record_has_useful_link($record)) {
        $missing[] = 'useful related/source link';
    }

    return $missing;
}

function kingy_ali_model_import_indexability_value_diagnostics($records, $filename = '', $first_data_row = 1) {
    if (empty($records)) {
        return array();
    }

    $problem_rows = array();
    $missing_seen = array();
    $rumored_rows = array();
    $outdated_rows = array();
    $noindex_rows = array();

    foreach ($records as $index => $record) {
        if (!is_array($record) || !kingy_ali_import_is_associative_array($record)) {
            continue;
        }

        $missing = kingy_ali_model_import_indexability_missing_labels($record);
        if ($missing) {
            $problem_rows[] = absint($index + $first_data_row);
            foreach ($missing as $label) {
                $missing_seen[$label] = true;
            }
        }

        if (kingy_ali_model_import_record_is_rumored($record)) {
            $rumored_rows[] = absint($index + $first_data_row);
        }

        if (kingy_ali_model_import_record_is_outdated_or_stale($record)) {
            $outdated_rows[] = absint($index + $first_data_row);
        }

        if (kingy_ali_model_import_record_is_truthy($record, 'noindex')) {
            $noindex_rows[] = absint($index + $first_data_row);
        }
    }

    $diagnostics = array();
    if ($problem_rows) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('%1$d model rows are missing fields used by model profile indexability checks. First rows: %2$s. Missing groups seen: %3$s. Imports can continue, but those profiles stay hidden/noindexed until enriched.', 'kingy-ai-launch-intelligence'),
                count($problem_rows),
                implode(', ', array_slice($problem_rows, 0, 10)),
                implode(', ', array_keys($missing_seen))
            ),
        );
    }

    if ($rumored_rows) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('%1$d model rows are marked rumored. First rows: %2$s. Rumored model profiles are forced noindex even if other fields are present.', 'kingy-ai-launch-intelligence'),
                count($rumored_rows),
                implode(', ', array_slice($rumored_rows, 0, 10))
            ),
        );
    }

    if ($outdated_rows) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('%1$d model rows are marked outdated or have stale/invalid last_verified dates. First rows: %2$s. Refresh source review before relying on these records for comparisons.', 'kingy-ai-launch-intelligence'),
                count($outdated_rows),
                implode(', ', array_slice($outdated_rows, 0, 10))
            ),
        );
    }

    if ($noindex_rows) {
        $diagnostics[] = array(
            'row' => 0,
            'status' => 'warning',
            'record' => $filename,
            'message' => sprintf(
                __('%1$d model rows are explicitly noindexed. First rows: %2$s. They should remain hidden until noindex is cleared and readiness gates pass.', 'kingy-ai-launch-intelligence'),
                count($noindex_rows),
                implode(', ', array_slice($noindex_rows, 0, 10))
            ),
        );
    }

    return $diagnostics;
}

function kingy_ali_model_import_indexability_missing_labels($record) {
    $missing = array();

    if (!kingy_ali_import_record_has_value($record, 'model_name')) {
        $missing[] = 'model_name';
    }
    if (!kingy_ali_import_record_has_any_value($record, array('provider_name', 'model_provider'))) {
        $missing[] = 'provider_name or model_provider';
    }
    if (!kingy_ali_import_record_has_value($record, 'model_modality')) {
        $missing[] = 'model_modality';
    }
    if (!kingy_ali_model_import_record_has_official_source($record)) {
        $missing[] = 'official source';
    }
    if (!kingy_ali_import_record_has_value($record, 'model_overview')) {
        $missing[] = 'model_overview';
    }
    if (!kingy_ali_model_import_record_has_release_or_status($record)) {
        $missing[] = 'release date or status note';
    }
    if (!kingy_ali_model_import_record_has_access_or_pricing($record)) {
        $missing[] = 'access or pricing notes';
    }
    if (!kingy_ali_import_record_has_value($record, 'benchmark_caveat')) {
        $missing[] = 'benchmark_caveat';
    }
    if (!kingy_ali_import_record_has_value($record, 'verification_status')) {
        $missing[] = 'verification_status';
    }
    if (!kingy_ali_import_record_has_value($record, 'last_verified')) {
        $missing[] = 'last_verified';
    }

    return array_values(array_unique(array_merge($missing, kingy_ali_model_import_missing_conditional_source_labels($record))));
}

function kingy_ali_model_import_missing_conditional_source_labels($record) {
    $missing = array();

    if (
        kingy_ali_import_record_has_any_value($record, array('pricing', 'api_pricing'))
        && !kingy_ali_model_import_record_has_any_source_url($record, array('pricing_url', 'api_reference_url', 'official_docs_url', 'license_url', 'weights_url'))
    ) {
        $missing[] = 'pricing/API source';
    }

    if (
        kingy_ali_import_record_has_any_value($record, array('context_window', 'output_limit'))
        && !kingy_ali_model_import_record_has_any_source_url($record, array('context_source_url', 'api_reference_url', 'official_docs_url', 'model_card_url', 'system_card_url'))
    ) {
        $missing[] = 'context/output source';
    }

    if (
        kingy_ali_model_import_record_has_license_claim($record)
        && !kingy_ali_model_import_record_has_any_source_url($record, array('license_url', 'weights_url', 'model_card_url', 'official_docs_url'))
    ) {
        $missing[] = 'license/weights source';
    }

    if (
        kingy_ali_import_record_has_value($record, 'benchmark_summary')
        && !kingy_ali_import_record_has_indexable_url($record, 'benchmark_url')
    ) {
        $missing[] = 'benchmark source';
    }

    return $missing;
}

function kingy_ali_model_import_record_has_official_source($record) {
    foreach (kingy_ali_model_import_official_source_columns() as $key) {
        if (kingy_ali_import_record_has_indexable_url($record, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_import_record_has_release_or_status($record) {
    return kingy_ali_import_record_has_any_value($record, array('release_date', 'model_status_note', 'model_status'));
}

function kingy_ali_model_import_record_has_access_or_pricing($record) {
    return kingy_ali_import_record_has_any_value(
        $record,
        array('pricing', 'api_pricing', 'license_notes', 'hardware_requirements')
    );
}

function kingy_ali_model_import_record_is_rumored($record) {
    $verification = isset($record['verification_status']) ? sanitize_key($record['verification_status']) : '';
    $status = isset($record['model_status']) ? strtolower((string) $record['model_status']) : '';
    return $verification === 'rumored' || strpos($status, 'rumored') !== false;
}

function kingy_ali_model_import_record_is_outdated_or_stale($record) {
    $verification = isset($record['verification_status']) ? sanitize_key($record['verification_status']) : '';
    if ($verification === 'outdated') {
        return true;
    }

    if (!kingy_ali_import_record_has_value($record, 'last_verified')) {
        return false;
    }

    $timestamp = strtotime((string) $record['last_verified']);
    if (!$timestamp) {
        return true;
    }

    $days = function_exists('kingy_ali_verification_stale_days') ? kingy_ali_verification_stale_days() : 30;
    return $timestamp < (current_time('timestamp') - (max(1, absint($days)) * DAY_IN_SECONDS));
}

function kingy_ali_model_import_record_is_truthy($record, $key) {
    if (!isset($record[$key])) {
        return false;
    }

    return in_array(strtolower(trim((string) $record[$key])), array('1', 'yes', 'true', 'on', 'noindex'), true);
}

function kingy_ali_model_import_record_has_any_source_url($record, $keys) {
    foreach ($keys as $key) {
        if (kingy_ali_import_record_has_indexable_url($record, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_import_record_has_license_claim($record) {
    foreach (array('open_weight', 'open_source') as $key) {
        if (kingy_ali_model_import_record_is_truthy($record, $key)) {
            return true;
        }
    }

    $license_type = isset($record['model_license_type']) ? strtolower((string) $record['model_license_type']) : '';
    if ($license_type !== '' && preg_match('/open|weight|apache|mit|llama|community|research|custom|non[- ]?commercial/', $license_type)) {
        return true;
    }

    $notes = isset($record['license_notes']) ? strtolower((string) $record['license_notes']) : '';
    return $notes !== '' && preg_match('/open[- ]?weight|open[- ]?source|apache|mit|llama|community|research|custom|non[- ]?commercial|weights?/', $notes);
}

function kingy_ali_import_record_has_any_value($record, $keys) {
    foreach ($keys as $key) {
        if (kingy_ali_import_record_has_value($record, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_import_record_has_value($record, $key) {
    return isset($record[$key]) && trim((string) $record[$key]) !== '';
}

function kingy_ali_import_record_has_indexable_url($record, $key) {
    if (!isset($record[$key]) || trim((string) $record[$key]) === '') {
        return false;
    }

    return kingy_ali_import_is_absolute_http_url($record[$key]);
}

function kingy_ali_import_record_has_useful_link($record) {
    foreach (kingy_ali_import_useful_link_readiness_columns() as $key) {
        if ($key === 'sources') {
            if (!empty($record['sources']) && kingy_ali_import_sources_have_valid_url($record['sources'])) {
                return true;
            }
            continue;
        }

        if (kingy_ali_import_record_has_indexable_url($record, $key)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_import_sources_have_valid_url($sources) {
    $tokens = preg_split('/[\s,]+/', (string) $sources);
    foreach ((array) $tokens as $token) {
        $candidate = trim($token, " \t\n\r\0\x0B<>\"'()[]{}.,;");
        if ($candidate !== '' && kingy_ali_import_is_absolute_http_url($candidate)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_import_launch_record($record) {
    $result = kingy_ali_import_launch_record_result($record);
    return in_array($result['status'], array('created', 'updated'), true);
}

function kingy_ali_import_launch_record_result($record) {
    $product_name = isset($record['product_name']) ? sanitize_text_field($record['product_name']) : '';
    if (!$product_name) {
        return array(
            'status' => 'skipped',
            'record' => '',
            'message' => __('Missing product_name.', 'kingy-ai-launch-intelligence'),
        );
    }

    $url_error = kingy_ali_import_record_url_error($record);
    if ($url_error) {
        return array(
            'status' => 'failed',
            'record' => $product_name,
            'message' => $url_error,
        );
    }

    $title = !empty($record['launch_name']) ? sanitize_text_field($record['launch_name']) : kingy_ali_import_launch_title($record, $product_name);
    $slug = kingy_ali_import_launch_slug($record, $product_name);
    $existing = get_page_by_path($slug, OBJECT, 'kingy_ai_launch');
    $was_existing = (bool) $existing;
    $status = isset($record['status']) && $record['status'] ? sanitize_key($record['status']) : 'draft';
    if (!in_array($status, array('publish', 'pending', 'draft'), true)) {
        $status = 'draft';
    }

    $initial_status = $existing ? get_post_status($existing->ID) : 'draft';
    if (!in_array($initial_status, array('publish', 'pending', 'draft'), true)) {
        $initial_status = 'draft';
    }

    $post_data = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => $initial_status,
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => isset($record['what_launched']) ? sanitize_textarea_field($record['what_launched']) : '',
        'post_excerpt' => isset($record['what_launched']) ? wp_trim_words(sanitize_textarea_field($record['what_launched']), 30) : '',
    );

    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        return array(
            'status' => 'failed',
            'record' => $title,
            'message' => $post_id->get_error_message(),
        );
    }

    $fields = kingy_ali_launch_meta_fields();
    foreach ($fields as $key => $field) {
        if (!array_key_exists($key, $record)) {
            continue;
        }

        $value = kingy_ali_sanitize_meta_value($record[$key], $field);
        if ($value === '') {
            delete_post_meta($post_id, kingy_ali_meta_key($key));
        } else {
            update_post_meta($post_id, kingy_ali_meta_key($key), $value);
        }
    }

    if (!empty($record['category'])) {
        kingy_ali_import_terms($post_id, $record['category'], 'kingy_launch_category');
    }
    if (!empty($record['audience'])) {
        kingy_ali_import_terms($post_id, $record['audience'], 'kingy_audience');
    }
    if (!empty($record['launch_type'])) {
        kingy_ali_import_terms($post_id, $record['launch_type'], 'kingy_launch_type');
    }

    $tool_name = !empty($record['tool_name']) ? sanitize_text_field($record['tool_name']) : $product_name;
    $tool_id = kingy_ali_sync_tool_from_launch($post_id, $tool_name);
    kingy_ali_sync_derived_attributes($post_id);
    if ($tool_id) {
        kingy_ali_sync_derived_attributes($tool_id);
    }

    if (get_post_status($post_id) !== $status) {
        $status_update = wp_update_post(
            array(
                'ID' => $post_id,
                'post_status' => $status,
            ),
            true
        );

        if (is_wp_error($status_update)) {
            return array(
                'status' => 'failed',
                'record' => $title,
                'message' => $status_update->get_error_message(),
            );
        }
    }

    return array(
        'status' => $was_existing ? 'updated' : 'created',
        'record' => $title,
        'message' => $was_existing ? __('Existing launch updated.', 'kingy-ai-launch-intelligence') : __('New launch created.', 'kingy-ai-launch-intelligence'),
        'post_id' => $post_id,
    );
}

function kingy_ali_import_model_record_result($record) {
    $model_name = isset($record['model_name']) ? sanitize_text_field($record['model_name']) : '';
    if (!$model_name) {
        return array(
            'status' => 'skipped',
            'record' => '',
            'message' => __('Missing model_name.', 'kingy-ai-launch-intelligence'),
        );
    }

    $url_error = kingy_ali_import_record_url_error($record, 'models');
    if ($url_error) {
        return array(
            'status' => 'failed',
            'record' => $model_name,
            'message' => $url_error,
        );
    }

    $record = kingy_ali_import_model_prepare_related_ids($record);
    $slug = sanitize_title($model_name);
    $existing = get_page_by_path($slug, OBJECT, 'kingy_ai_model');
    $was_existing = (bool) $existing;
    $status = isset($record['status']) && $record['status'] ? sanitize_key($record['status']) : 'draft';
    if (!in_array($status, array('publish', 'pending', 'draft'), true)) {
        $status = 'draft';
    }

    $initial_status = $existing ? get_post_status($existing->ID) : 'draft';
    if (!in_array($initial_status, array('publish', 'pending', 'draft'), true)) {
        $initial_status = 'draft';
    }

    $overview = isset($record['model_overview']) ? sanitize_textarea_field($record['model_overview']) : '';
    $post_data = array(
        'post_type' => 'kingy_ai_model',
        'post_status' => $initial_status,
        'post_title' => $model_name,
        'post_name' => $slug,
        'post_content' => $overview,
        'post_excerpt' => $overview !== '' ? wp_trim_words($overview, 30) : '',
    );

    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        return array(
            'status' => 'failed',
            'record' => $model_name,
            'message' => $post_id->get_error_message(),
        );
    }

    $fields = kingy_ali_model_meta_fields();
    foreach ($fields as $key => $field) {
        if (!array_key_exists($key, $record)) {
            continue;
        }

        $value = kingy_ali_sanitize_meta_value($record[$key], $field);
        if ($value === '') {
            delete_post_meta($post_id, kingy_ali_meta_key($key));
        } else {
            update_post_meta($post_id, kingy_ali_meta_key($key), $value);
        }
    }

    foreach (kingy_ali_model_import_taxonomies() as $taxonomy) {
        if (!empty($record[$taxonomy])) {
            kingy_ali_import_terms($post_id, $record[$taxonomy], $taxonomy);
        }
    }

    if (get_post_status($post_id) !== $status) {
        $status_update = wp_update_post(
            array(
                'ID' => $post_id,
                'post_status' => $status,
            ),
            true
        );

        if (is_wp_error($status_update)) {
            return array(
                'status' => 'failed',
                'record' => $model_name,
                'message' => $status_update->get_error_message(),
            );
        }
    }

    return array(
        'status' => $was_existing ? 'updated' : 'created',
        'record' => $model_name,
        'message' => $was_existing ? __('Existing model updated.', 'kingy-ai-launch-intelligence') : __('New model created.', 'kingy-ai-launch-intelligence'),
        'post_id' => $post_id,
    );
}

function kingy_ali_model_import_taxonomies() {
    return array(
        'model_provider',
        'model_family',
        'model_modality',
        'model_use_case',
        'model_access_type',
        'model_license_type',
        'model_status',
    );
}

function kingy_ali_import_model_prepare_related_ids($record) {
    foreach (
        array(
            'related_launch_id' => 'kingy_ai_launch',
            'related_tool_id' => 'kingy_ai_tool',
            'related_company_id' => 'kingy_ai_company',
        ) as $key => $post_type
    ) {
        if (empty($record[$key])) {
            continue;
        }

        $record[$key] = kingy_ali_import_related_record_id($record[$key], $post_type);
    }

    return $record;
}

function kingy_ali_import_related_record_id($value, $post_type) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (ctype_digit($value) && kingy_ali_related_post_is_valid((int) $value, $post_type)) {
        return (int) $value;
    }

    $slug_match = get_page_by_path(sanitize_title($value), OBJECT, $post_type);
    if ($slug_match) {
        return (int) $slug_match->ID;
    }

    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 20,
            's' => $value,
            'fields' => 'ids',
            'no_found_rows' => true,
        )
    );

    foreach ((array) $query->posts as $post_id) {
        if (strcasecmp(trim(wp_strip_all_tags(get_the_title($post_id))), $value) === 0) {
            return (int) $post_id;
        }
    }

    return '';
}

function kingy_ali_import_url_columns($import_type = 'launches') {
    if (kingy_ali_sanitize_import_type($import_type) === 'models') {
        return array(
            'official_url',
            'official_announcement_url',
            'official_docs_url',
            'api_reference_url',
            'model_card_url',
            'system_card_url',
            'evals_url',
            'pricing_url',
            'license_url',
            'weights_url',
            'context_source_url',
            'benchmark_url',
            'alternatives_url',
            'related_article_url',
            'related_course_url',
        );
    }

    return array(
        'official_url',
        'demo_url',
        'product_hunt_url',
        'github_url',
        'huggingface_url',
        'x_url',
        'youtube_url',
        'funding',
        'press_kit_url',
        'related_article_url',
        'related_course_url',
        'related_review_url',
        'related_alternatives_url',
        'related_calculator_url',
        'best_next_link_url',
    );
}

function kingy_ali_import_record_url_error($record, $import_type = 'launches') {
    foreach (kingy_ali_import_url_columns($import_type) as $key) {
        if (!array_key_exists($key, $record) || trim((string) $record[$key]) === '') {
            continue;
        }

        if (!kingy_ali_import_is_absolute_http_url($record[$key])) {
            return sprintf(
                /* translators: %s is an import column name. */
                __('Invalid URL in %s. Use an absolute http or https URL.', 'kingy-ai-launch-intelligence'),
                $key
            );
        }
    }

    if (!empty($record['sources'])) {
        $bad_source_url = kingy_ali_import_first_invalid_source_url($record['sources']);
        if ($bad_source_url) {
            return sprintf(
                /* translators: %s is an invalid URL found inside the sources column. */
                __('Invalid URL in sources: %s. Use absolute http or https URLs for source links.', 'kingy-ai-launch-intelligence'),
                $bad_source_url
            );
        }
    }

    return '';
}

function kingy_ali_import_first_invalid_source_url($sources) {
    $tokens = preg_split('/[\s,]+/', (string) $sources);
    foreach ((array) $tokens as $token) {
        $candidate = trim($token, " \t\n\r\0\x0B<>\"'()[]{}.,;");
        if ($candidate === '' || !kingy_ali_import_source_token_is_url_like($candidate)) {
            continue;
        }

        if (!kingy_ali_import_is_absolute_http_url($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function kingy_ali_import_source_token_is_url_like($token) {
    $token = trim((string) $token);
    if ($token === '') {
        return false;
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $token)) {
        return true;
    }

    $scheme = strtolower((string) wp_parse_url($token, PHP_URL_SCHEME));
    if (in_array($scheme, array('mailto', 'javascript', 'ftp', 'file', 'data'), true)) {
        return true;
    }

    return preg_match('#^www\.#i', $token) === 1;
}

function kingy_ali_import_is_absolute_http_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    $sanitized = esc_url_raw($url, array('http', 'https'));
    $parts = wp_parse_url($sanitized);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '';
}

function kingy_ali_import_launch_title($record, $product_name) {
    $pieces = array($product_name);
    if (!empty($record['launch_type'])) {
        $pieces[] = ucwords(str_replace(array('-', '_'), ' ', sanitize_text_field($record['launch_type'])));
    } else {
        $pieces[] = __('Launch', 'kingy-ai-launch-intelligence');
    }

    if (!empty($record['launch_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['launch_date'])) {
        $pieces[] = date_i18n('M Y', strtotime($record['launch_date']));
    }

    return implode(' — ', array_filter($pieces));
}

function kingy_ali_import_launch_slug($record, $product_name) {
    $parts = array($product_name);

    if (!empty($record['launch_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['launch_date'])) {
        $parts[] = $record['launch_date'];
    }

    if (!empty($record['launch_type'])) {
        $parts[] = $record['launch_type'];
    }

    return sanitize_title(implode(' ', array_filter($parts)));
}

function kingy_ali_import_terms($post_id, $value, $taxonomy) {
    $terms = array_filter(array_map('trim', explode(',', $value)));
    $slugs = array();

    foreach ($terms as $term_name) {
        $slug = sanitize_title($term_name);
        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($term_name, $taxonomy, array('slug' => $slug));
        }
        $slugs[] = $slug;
    }

    if ($slugs) {
        wp_set_object_terms($post_id, $slugs, $taxonomy, false);
    }
}

function kingy_ali_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Launch Intelligence Settings', 'kingy-ai-launch-intelligence'); ?></h1>
        <?php settings_errors('kingy_ali_settings'); ?>
        <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
            <?php settings_fields('kingy_ali_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="kingy_ali_contact_url"><?php esc_html_e('Contact CTA URL', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td>
                            <input class="regular-text" id="kingy_ali_contact_url" type="url" name="kingy_ali_contact_url" value="<?php echo esc_attr(get_option('kingy_ali_contact_url', '')); ?>" placeholder="<?php echo esc_attr(home_url('/contact/')); ?>">
                            <p class="description"><?php esc_html_e('Used by Launch Visibility Score and submission success CTAs. Leave empty to use /contact/.', 'kingy-ai-launch-intelligence'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kingy_ali_client_examples_url"><?php esc_html_e('Client examples CTA URL', 'kingy-ai-launch-intelligence'); ?></label></th>
                        <td>
                            <input class="regular-text" id="kingy_ali_client_examples_url" type="url" name="kingy_ali_client_examples_url" value="<?php echo esc_attr(get_option('kingy_ali_client_examples_url', '')); ?>" placeholder="<?php echo esc_attr(home_url('/client-examples/')); ?>">
                            <p class="description"><?php esc_html_e('Optional. When set, founder submission success screens include a tracked client examples CTA.', 'kingy-ai-launch-intelligence'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(__('Save Settings', 'kingy-ai-launch-intelligence')); ?>
        </form>
        <h2><?php esc_html_e('Launch readiness guardrails', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?></p>
        <p><?php echo esc_html(kingy_ali_index_readiness_summary()); ?></p>
        <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        <p><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
        <h2><?php esc_html_e('Shortcodes', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><code>[kingy_launch_hub]</code></p>
        <p><code>[kingy_launch_search]</code></p>
        <p><code>[kingy_launch_grid period="today"]</code></p>
        <p><code>[kingy_launch_grid period="week"]</code></p>
        <p><code>[kingy_launch_grid category="ai-agents"]</code></p>
        <p><code>[kingy_launch_submit_form]</code></p>
        <p><code>[kingy_launch_visibility_score]</code></p>
        <p><code>[kingy_creator_campaign_roi_calculator]</code></p>
        <p><code>[kingy_tool_directory]</code></p>
        <p><code>[kingy_company_directory]</code></p>
        <p><code>[kingy_trending_launches]</code></p>
        <p><code>[kingy_youtube_worthy_launches]</code></p>
        <p><code>[kingy_creator_coverage_launches]</code></p>
        <p><code>[kingy_codex_prompt_builder]</code></p>
        <p><code>[kingy_codex_prompt_article_tools]</code></p>
        <p><code>[kingy_app_builder_comparison]</code></p>
        <p><code>[kingy_ai_lead_magnet_guide]</code></p>
        <p><code>[kingy_ai_landing_page_guide]</code></p>
        <p><code>[kingy_safe_ai_agent_guide]</code></p>
        <p><code>[kingy_vibe_coding_beginner_hub]</code></p>
        <p><code>[kingy_replit_beginner_guide]</code></p>
        <p><code>[kingy_microsoft_copilot_course]</code></p>
        <p><code>[kingy_custom_html_safety_checklist]</code></p>
        <p><code>[kingy_website_qa_checklist]</code></p>
        <p><code>[kingy_seo_qa_checklist]</code></p>
        <p><code>[kingy_security_review_checklist]</code></p>
    </div>
    <?php
}
