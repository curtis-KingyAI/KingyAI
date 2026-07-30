<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_kingy_ali_export', 'kingy_ali_handle_admin_export');

function kingy_ali_handle_admin_export() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to export Launch Intelligence records.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_export');

    $type = kingy_ali_export_request_type();
    if ($type === 'models') {
        kingy_ali_export_models_csv();
        exit;
    }

    if ($type === 'companies') {
        kingy_ali_export_companies_csv();
        exit;
    }

    if ($type === 'tools') {
        kingy_ali_export_tools_csv();
        exit;
    }

    if ($type === 'scorecard_leads') {
        kingy_ali_export_scorecard_leads_csv();
        exit;
    }

    kingy_ali_export_launches_csv();
    exit;
}

function kingy_ali_export_request_type() {
    $values = kingy_ali_export_request_values();
    if (!isset($values['type'])) {
        return 'launches';
    }

    if (!is_scalar($values['type'])) {
        return 'launches';
    }

    $type = wp_unslash($values['type']);
    if (!is_scalar($type)) {
        return 'launches';
    }

    $type = sanitize_key((string) $type);
    return in_array($type, array('launches', 'models', 'tools', 'companies', 'scorecard_leads'), true) ? $type : 'launches';
}

function kingy_ali_export_request_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_export_scorecard_leads_csv() {
    if (!function_exists('kingy_ali_scorecard_lead_rows') || !function_exists('kingy_ali_scorecard_lead_row_data')) {
        wp_die(esc_html__('Scorecard lead export is unavailable.', 'kingy-ai-launch-intelligence'));
    }

    $columns = array(
        'submitted_at',
        'product_name',
        'founder_contact_name',
        'email',
        'official_url',
        'score',
        'tier',
        'review_interest',
        'notes',
        'source',
    );

    if (function_exists('kingy_ali_ai_launch_scorecard_labels')) {
        foreach (kingy_ali_ai_launch_scorecard_labels() as $key => $label) {
            $columns[] = $key;
        }
    }

    kingy_ali_send_csv_headers('kingy-ai-launch-scorecard-leads.csv');
    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }
    kingy_ali_fputcsv($output, $columns);

    foreach (kingy_ali_scorecard_lead_rows(2000) as $row) {
        $lead = kingy_ali_scorecard_lead_row_data($row);
        $csv_row = array(
            'submitted_at' => $lead['created_at'],
            'product_name' => $lead['product_name'],
            'founder_contact_name' => $lead['contact_name'],
            'email' => $lead['email'],
            'official_url' => $lead['official_url'],
            'score' => $lead['score'],
            'tier' => $lead['tier'],
            'review_interest' => $lead['interest'],
            'notes' => $lead['notes'],
            'source' => $lead['source'],
        );

        foreach (array_slice($columns, 10) as $score_key) {
            $csv_row[$score_key] = isset($lead['scores'][$score_key]) ? $lead['scores'][$score_key] : '';
        }

        kingy_ali_fputcsv($output, kingy_ali_ordered_csv_row($columns, $csv_row));
    }

    fclose($output);
}

function kingy_ali_export_launches_csv() {
    if (!function_exists('kingy_ali_import_column_keys')) {
        wp_die(esc_html__('Launch export columns are unavailable.', 'kingy-ai-launch-intelligence'));
    }

    $columns = kingy_ali_import_column_keys();

    kingy_ali_send_csv_headers('kingy-ai-launches.csv');
    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }
    kingy_ali_fputcsv($output, $columns);

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        )
    );

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $related_tool_id = kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
        $related_tool_title = kingy_ali_export_related_title($related_tool_id, 'kingy_ai_tool');
        $related_company_title = kingy_ali_export_related_title(kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_company_id')), 'kingy_ai_company');
        $product_name = $related_tool_title ? $related_tool_title : get_the_title($post_id);
        $row = array(
            'product_name' => $product_name,
            'launch_name' => get_the_title($post_id),
            'tool_name' => $related_tool_title ? $related_tool_title : $product_name,
            'company' => kingy_ali_get_meta($post_id, 'company'),
            'company_profile' => $related_company_title,
            'launch_date' => kingy_ali_get_meta($post_id, 'launch_date'),
            'launch_type' => kingy_ali_export_term_names($post_id, 'kingy_launch_type'),
            'category' => kingy_ali_export_term_names($post_id, 'kingy_launch_category'),
            'audience' => kingy_ali_export_term_names($post_id, 'kingy_audience'),
            'official_url' => kingy_ali_get_meta($post_id, 'official_url'),
            'official_announcement_url' => kingy_ali_get_meta($post_id, 'official_announcement_url'),
            'official_docs_url' => kingy_ali_get_meta($post_id, 'official_docs_url'),
            'what_launched' => kingy_ali_get_meta($post_id, 'what_launched'),
            'who_it_is_for' => kingy_ali_get_meta($post_id, 'who_it_is_for'),
            'pricing' => kingy_ali_get_meta($post_id, 'pricing'),
            'pricing_url' => kingy_ali_get_meta($post_id, 'pricing_url'),
            'free_plan' => kingy_ali_get_meta($post_id, 'free_plan'),
            'api_available' => kingy_ali_get_meta($post_id, 'api_available'),
            'open_source_or_open_weight' => kingy_ali_get_meta($post_id, 'open_source_or_open_weight'),
            'demo_url' => kingy_ali_get_meta($post_id, 'demo_url'),
            'product_hunt_url' => kingy_ali_get_meta($post_id, 'product_hunt_url'),
            'github_url' => kingy_ali_get_meta($post_id, 'github_url'),
            'huggingface_url' => kingy_ali_get_meta($post_id, 'huggingface_url'),
            'x_url' => kingy_ali_get_meta($post_id, 'x_url'),
            'youtube_url' => kingy_ali_get_meta($post_id, 'youtube_url'),
            'funding' => kingy_ali_get_meta($post_id, 'funding'),
            'press_kit_url' => kingy_ali_get_meta($post_id, 'press_kit_url'),
            'founder_team' => kingy_ali_get_meta($post_id, 'founder_team'),
            'reddit_signal' => kingy_ali_get_meta($post_id, 'reddit_signal'),
            'youtube_signal' => kingy_ali_get_meta($post_id, 'youtube_signal'),
            'traction_notes' => kingy_ali_get_meta($post_id, 'traction_notes'),
            'kingy_launch_score' => kingy_ali_get_meta($post_id, 'kingy_launch_score'),
            'demo_quality_score' => kingy_ali_get_meta($post_id, 'demo_quality_score'),
            'youtube_score' => kingy_ali_get_meta($post_id, 'youtube_score'),
            'seo_score' => kingy_ali_get_meta($post_id, 'seo_score'),
            'sponsor_fit_score_internal' => kingy_ali_get_meta($post_id, 'sponsor_fit_score_internal'),
            'kingy_verdict' => kingy_ali_get_meta($post_id, 'kingy_verdict'),
            'what_feels_promising' => kingy_ali_get_meta($post_id, 'what_feels_promising'),
            'what_feels_unproven' => kingy_ali_get_meta($post_id, 'what_feels_unproven'),
            'related_article_url' => kingy_ali_get_meta($post_id, 'related_article_url'),
            'related_course_url' => kingy_ali_get_meta($post_id, 'related_course_url'),
            'related_review_url' => kingy_ali_get_meta($post_id, 'related_review_url'),
            'related_alternatives_url' => kingy_ali_get_meta($post_id, 'related_alternatives_url'),
            'related_calculator_url' => kingy_ali_get_meta($post_id, 'related_calculator_url'),
            'best_next_link_url' => kingy_ali_get_meta($post_id, 'best_next_link_url'),
            'best_next_link_label' => kingy_ali_get_meta($post_id, 'best_next_link_label'),
            'youtube_interest' => kingy_ali_get_meta($post_id, 'youtube_interest'),
            'creator_coverage_interest' => kingy_ali_get_meta($post_id, 'creator_coverage_interest'),
            'sponsorship_interest' => kingy_ali_get_meta($post_id, 'sponsorship_interest'),
            'visibility_score_interest' => kingy_ali_get_meta($post_id, 'visibility_score_interest'),
            'budget_likelihood_internal' => kingy_ali_get_meta($post_id, 'budget_likelihood_internal'),
            'founder_notes' => kingy_ali_get_meta($post_id, 'founder_notes'),
            'internal_notes' => kingy_ali_get_meta($post_id, 'internal_notes'),
            'sources' => kingy_ali_get_meta($post_id, 'sources'),
            'seo_title' => kingy_ali_get_meta($post_id, 'seo_title'),
            'meta_description' => kingy_ali_get_meta($post_id, 'meta_description'),
            'target_search_query' => kingy_ali_get_meta($post_id, 'target_search_query'),
            'featured_snippet_answer' => kingy_ali_get_meta($post_id, 'featured_snippet_answer'),
            'status' => get_post_status($post_id),
            'verification_status' => kingy_ali_get_meta($post_id, 'verification_status'),
            'last_verified' => kingy_ali_get_meta($post_id, 'last_verified'),
        );
        kingy_ali_fputcsv($output, kingy_ali_ordered_csv_row($columns, $row));
    }
    wp_reset_postdata();
    fclose($output);
}

function kingy_ali_ordered_csv_row($columns, $row) {
    $ordered = array();
    foreach ($columns as $column) {
        $ordered[] = isset($row[$column]) ? $row[$column] : '';
    }

    return $ordered;
}

function kingy_ali_fputcsv($output, $row) {
    return fputcsv($output, array_map('kingy_ali_escape_csv_cell', $row), ',', '"', '\\');
}

function kingy_ali_escape_csv_cell($value) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value);
        if (!is_string($value)) {
            $value = '';
        }
    }

    $value = (string) $value;
    $trimmed = ltrim($value);
    if ($trimmed !== '' && in_array($trimmed[0], array('=', '+', '-', '@'), true)) {
        return "'" . $value;
    }

    return $value;
}

function kingy_ali_export_tools_csv() {
    $columns = array(
        'tool_name',
        'company',
        'company_profile',
        'category',
        'audience',
        'official_url',
        'demo_url',
        'what_it_does',
        'best_for',
        'pricing',
        'free_plan',
        'api_available',
        'open_source_or_open_weight',
        'main_competitors',
        'alternatives_url',
        'related_article_url',
        'related_course_url',
        'related_review_url',
        'latest_launch',
        'status',
        'last_verified',
    );

    kingy_ali_send_csv_headers('kingy-ai-tools.csv');
    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }
    kingy_ali_fputcsv($output, $columns);

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_tool',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        )
    );

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $latest_launch_id = kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
        $related_company_title = kingy_ali_export_related_title(kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_company_id')), 'kingy_ai_company');
        $row = array(
            'tool_name' => get_the_title($post_id),
            'company' => kingy_ali_get_meta($post_id, 'company'),
            'company_profile' => $related_company_title,
            'category' => kingy_ali_export_term_names($post_id, 'kingy_launch_category'),
            'audience' => kingy_ali_export_term_names($post_id, 'kingy_audience'),
            'official_url' => kingy_ali_get_meta($post_id, 'official_url'),
            'demo_url' => kingy_ali_get_meta($post_id, 'demo_url'),
            'what_it_does' => kingy_ali_get_meta($post_id, 'what_it_does'),
            'best_for' => kingy_ali_get_meta($post_id, 'best_for'),
            'pricing' => kingy_ali_get_meta($post_id, 'pricing'),
            'free_plan' => kingy_ali_get_meta($post_id, 'free_plan'),
            'api_available' => kingy_ali_get_meta($post_id, 'api_available'),
            'open_source_or_open_weight' => kingy_ali_get_meta($post_id, 'open_source_or_open_weight'),
            'main_competitors' => kingy_ali_get_meta($post_id, 'main_competitors'),
            'alternatives_url' => kingy_ali_get_meta($post_id, 'alternatives_url'),
            'related_article_url' => kingy_ali_get_meta($post_id, 'related_article_url'),
            'related_course_url' => kingy_ali_get_meta($post_id, 'related_course_url'),
            'related_review_url' => kingy_ali_get_meta($post_id, 'related_review_url'),
            'latest_launch' => kingy_ali_export_related_title($latest_launch_id, 'kingy_ai_launch'),
            'status' => get_post_status($post_id),
            'last_verified' => kingy_ali_get_meta($post_id, 'last_verified'),
        );
        kingy_ali_fputcsv($output, $row);
    }
    wp_reset_postdata();
    fclose($output);
}

function kingy_ali_export_models_csv() {
    if (!function_exists('kingy_ali_model_import_column_keys')) {
        wp_die(esc_html__('Model export columns are unavailable.', 'kingy-ai-launch-intelligence'));
    }

    $columns = kingy_ali_model_import_column_keys();

    kingy_ali_send_csv_headers('kingy-ai-models.csv');
    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }
    kingy_ali_fputcsv($output, $columns);

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_model',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        )
    );

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $row = array(
            'model_name' => get_the_title($post_id),
            'provider_name' => kingy_ali_get_meta($post_id, 'provider_name'),
            'model_provider' => kingy_ali_export_term_names($post_id, 'model_provider'),
            'model_family_name' => kingy_ali_get_meta($post_id, 'model_family_name'),
            'model_family' => kingy_ali_export_term_names($post_id, 'model_family'),
            'release_date' => kingy_ali_get_meta($post_id, 'release_date'),
            'model_status' => kingy_ali_export_term_names($post_id, 'model_status'),
            'model_status_note' => kingy_ali_get_meta($post_id, 'model_status_note'),
            'model_modality' => kingy_ali_export_term_names($post_id, 'model_modality'),
            'model_use_case' => kingy_ali_export_term_names($post_id, 'model_use_case'),
            'model_access_type' => kingy_ali_export_term_names($post_id, 'model_access_type'),
            'model_license_type' => kingy_ali_export_term_names($post_id, 'model_license_type'),
            'status' => get_post_status($post_id),
        );

        foreach (kingy_ali_model_import_column_keys() as $column) {
            if (isset($row[$column])) {
                continue;
            }

            if ($column === 'related_launch_id') {
                $row[$column] = kingy_ali_export_related_title(kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_launch_id')), 'kingy_ai_launch');
                continue;
            }

            if ($column === 'related_tool_id') {
                $row[$column] = kingy_ali_export_related_title(kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_tool_id')), 'kingy_ai_tool');
                continue;
            }

            if ($column === 'related_company_id') {
                $row[$column] = kingy_ali_export_related_title(kingy_ali_export_related_id(kingy_ali_get_meta($post_id, 'related_company_id')), 'kingy_ai_company');
                continue;
            }

            $row[$column] = kingy_ali_get_meta($post_id, $column);
        }

        kingy_ali_fputcsv($output, kingy_ali_ordered_csv_row($columns, $row));
    }
    wp_reset_postdata();
    fclose($output);
}

function kingy_ali_export_companies_csv() {
    $columns = array(
        'company_name',
        'category',
        'audience',
        'official_url',
        'company_summary',
        'founder_team',
        'funding',
        'contact_url',
        'launch_count',
        'tool_count',
        'outreach_status',
        'sponsor_fit_score_internal',
        'budget_likelihood_internal',
        'internal_notes',
        'status',
        'last_verified',
    );

    kingy_ali_send_csv_headers('kingy-ai-companies.csv');
    $output = fopen('php://output', 'w');
    if (!$output) {
        wp_die(esc_html__('Unable to open CSV output stream.', 'kingy-ai-launch-intelligence'));
    }
    kingy_ali_fputcsv($output, $columns);

    $query = new WP_Query(
        array(
            'post_type' => 'kingy_ai_company',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        )
    );

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $row = array(
            'company_name' => get_the_title($post_id),
            'category' => kingy_ali_export_term_names($post_id, 'kingy_launch_category'),
            'audience' => kingy_ali_export_term_names($post_id, 'kingy_audience'),
            'official_url' => kingy_ali_get_meta($post_id, 'official_url'),
            'company_summary' => kingy_ali_get_meta($post_id, 'company_summary'),
            'founder_team' => kingy_ali_get_meta($post_id, 'founder_team'),
            'funding' => kingy_ali_get_meta($post_id, 'funding'),
            'contact_url' => kingy_ali_get_meta($post_id, 'contact_url'),
            'launch_count' => kingy_ali_company_related_count($post_id, 'kingy_ai_launch'),
            'tool_count' => kingy_ali_company_related_count($post_id, 'kingy_ai_tool'),
            'outreach_status' => kingy_ali_get_meta($post_id, 'outreach_status'),
            'sponsor_fit_score_internal' => kingy_ali_get_meta($post_id, 'sponsor_fit_score_internal'),
            'budget_likelihood_internal' => kingy_ali_get_meta($post_id, 'budget_likelihood_internal'),
            'internal_notes' => kingy_ali_get_meta($post_id, 'internal_notes'),
            'status' => get_post_status($post_id),
            'last_verified' => kingy_ali_get_meta($post_id, 'last_verified'),
        );
        kingy_ali_fputcsv($output, $row);
    }
    wp_reset_postdata();
    fclose($output);
}

function kingy_ali_send_csv_headers($filename) {
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
}

function kingy_ali_export_related_id($value) {
    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_export_related_title($post_id, $post_type) {
    $post_id = kingy_ali_export_related_id($post_id);
    if (!kingy_ali_related_post_is_valid($post_id, $post_type)) {
        return '';
    }

    return get_the_title($post_id);
}

function kingy_ali_export_term_names($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}
