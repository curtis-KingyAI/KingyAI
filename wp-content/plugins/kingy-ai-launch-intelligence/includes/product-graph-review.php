<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kingy_ali_product_graph_maybe_download_artifact');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_review_overlay');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_overlay_cleanup_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_health_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_repair_planner_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_evidence_pack_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_review_dashboard_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_reviewer_progress_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_work_queue_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_opportunities_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_recommendations_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_readiness_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_plan_preview_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_dry_run_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_source_context_audit_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_review_batches_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_link_batch_progress_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_download_stage3_closeout_export');
add_action('admin_init', 'kingy_ali_product_graph_maybe_save_review_state');
add_action('admin_menu', 'kingy_ali_product_graph_register_admin_menu', 30);

function kingy_ali_product_graph_register_admin_menu() {
    global $submenu;

    $parent_slug = 'kingy-ali-dashboard';
    if (isset($submenu[$parent_slug]) && is_array($submenu[$parent_slug])) {
        foreach ($submenu[$parent_slug] as $item) {
            if (isset($item[2]) && $item[2] === 'kingy-ali-product-graph') {
                return;
            }
        }
    }

    add_submenu_page(
        $parent_slug,
        __('Kingy Product Graph Review', 'kingy-ai-launch-intelligence'),
        __('Product Graph', 'kingy-ai-launch-intelligence'),
        'manage_options',
        'kingy-ali-product-graph',
        'kingy_ali_render_product_graph_review_page'
    );
}

function kingy_ali_product_graph_reports_dir() {
    $workspace_root = dirname(dirname(dirname(KINGY_ALI_PLUGIN_DIR)));
    $reports_dir = trailingslashit($workspace_root) . 'kingy-ai-launch-system/data/reports';

    return apply_filters('kingy_ali_product_graph_reports_dir', $reports_dir);
}

function kingy_ali_product_graph_artifact_prefix() {
    return '2026-06-17-product-graph-review-';
}

function kingy_ali_product_graph_dataset_files() {
    $prefix = kingy_ali_product_graph_artifact_prefix();

    return array(
        'summary' => $prefix . 'admin-screen-summary.json',
        'nodes' => $prefix . 'nodes.json',
        'edges' => $prefix . 'edges.json',
        'resolver' => $prefix . 'resolver.json',
        'unresolved_queue' => $prefix . 'unresolved_queue.json',
        'model_inventory' => $prefix . 'model_inventory.json',
        'export_download' => $prefix . 'export_download.json',
    );
}

function kingy_ali_product_graph_downloadable_files() {
    $prefix = kingy_ali_product_graph_artifact_prefix();
    $families = array(
        'nodes',
        'edges',
        'resolver',
        'unresolved_queue',
        'model_inventory',
        'export_download',
        'admin-screen-summary',
    );
    $files = array();

    foreach ($families as $family) {
        $files[] = $prefix . $family . '.json';
        if ($family !== 'admin-screen-summary') {
            $files[] = $prefix . $family . '.csv';
        }
    }

    return $files;
}

function kingy_ali_product_graph_review_option_name() {
    return 'kingy_ali_product_graph_review_overlay';
}

function kingy_ali_product_graph_allowed_review_states() {
    return array(
        'unreviewed' => __('Unreviewed', 'kingy-ai-launch-intelligence'),
        'accepted' => __('Accepted', 'kingy-ai-launch-intelligence'),
        'rejected' => __('Rejected', 'kingy-ai-launch-intelligence'),
        'needs_source' => __('Needs source', 'kingy-ai-launch-intelligence'),
        'needs_refresh' => __('Needs refresh', 'kingy-ai-launch-intelligence'),
        'needs_canonical_review' => __('Needs canonical review', 'kingy-ai-launch-intelligence'),
        'model_inventory_blocked' => __('Model inventory blocked', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_product_graph_reviewable_tabs() {
    return array(
        'work_queue' => 'work_queue',
        'link_recommendations' => 'link_recommendation',
        'nodes' => 'node',
        'edges' => 'edge',
        'resolver' => 'resolver',
        'unresolved_queue' => 'unresolved_queue',
        'model_inventory' => 'model_inventory',
    );
}

function kingy_ali_product_graph_review_tab_for_type($row_type) {
    $tabs = kingy_ali_product_graph_reviewable_tabs();
    $row_type = sanitize_key($row_type);

    foreach ($tabs as $tab => $type) {
        if ($type === $row_type) {
            return $tab;
        }
    }

    return '';
}

function kingy_ali_product_graph_review_type_for_tab($tab) {
    $tabs = kingy_ali_product_graph_reviewable_tabs();
    $tab = sanitize_key($tab);

    return isset($tabs[$tab]) ? $tabs[$tab] : '';
}

function kingy_ali_product_graph_review_overlay() {
    $overlay = get_option(kingy_ali_product_graph_review_option_name(), array());

    return is_array($overlay) ? $overlay : array();
}

function kingy_ali_product_graph_review_record($row_type, $row_id) {
    $overlay = kingy_ali_product_graph_review_overlay();
    $row_type = sanitize_key($row_type);
    $row_id = kingy_ali_product_graph_sanitize_row_id($row_id);

    return isset($overlay[$row_type][$row_id]) && is_array($overlay[$row_type][$row_id])
        ? $overlay[$row_type][$row_id]
        : array();
}

function kingy_ali_product_graph_sanitize_row_id($row_id) {
    $row_id = is_scalar($row_id) ? sanitize_text_field((string) wp_unslash($row_id)) : '';
    if (function_exists('mb_strlen') && mb_strlen($row_id) > 500) {
        return mb_substr($row_id, 0, 500);
    }

    return strlen($row_id) > 500 ? substr($row_id, 0, 500) : $row_id;
}

function kingy_ali_product_graph_sanitize_review_notes($notes) {
    $notes = is_scalar($notes) ? sanitize_textarea_field((string) wp_unslash($notes)) : '';
    if (function_exists('mb_strlen') && mb_strlen($notes) > 1000) {
        return mb_substr($notes, 0, 1000);
    }

    return strlen($notes) > 1000 ? substr($notes, 0, 1000) : $notes;
}

function kingy_ali_product_graph_post_value($key, $max_length = 191) {
    if (!is_array($_POST) || !isset($_POST[$key]) || !is_scalar($_POST[$key])) {
        return '';
    }

    $value = wp_unslash($_POST[$key]);
    if (!is_scalar($value)) {
        return '';
    }

    $value = sanitize_text_field((string) $value);
    $max_length = absint($max_length);
    if ($max_length > 0 && function_exists('mb_strlen') && mb_strlen($value) > $max_length) {
        return mb_substr($value, 0, $max_length);
    }

    return $max_length > 0 && strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
}

function kingy_ali_product_graph_get_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_product_graph_get_value($key, $max_length = 191) {
    $values = kingy_ali_product_graph_get_values();
    if (!isset($values[$key]) || !is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    if (!is_scalar($value)) {
        return '';
    }

    $value = sanitize_text_field((string) $value);
    $max_length = absint($max_length);
    if ($max_length > 0 && function_exists('mb_strlen') && mb_strlen($value) > $max_length) {
        return mb_substr($value, 0, $max_length);
    }

    return $max_length > 0 && strlen($value) > $max_length ? substr($value, 0, $max_length) : $value;
}

function kingy_ali_product_graph_report_path($file) {
    $file = sanitize_file_name((string) $file);
    if (!in_array($file, kingy_ali_product_graph_downloadable_files(), true)) {
        return '';
    }

    $reports_dir = kingy_ali_product_graph_reports_dir();
    $base = realpath($reports_dir);
    if (!$base || !is_dir($base)) {
        return '';
    }

    $path = realpath(trailingslashit($base) . $file);
    if (!$path || !is_file($path)) {
        return '';
    }

    $base = trailingslashit($base);
    if (strpos($path, $base) !== 0) {
        return '';
    }

    return $path;
}

function kingy_ali_product_graph_json_file($dataset_key) {
    $files = kingy_ali_product_graph_dataset_files();
    if (!isset($files[$dataset_key])) {
        return '';
    }

    return kingy_ali_product_graph_report_path($files[$dataset_key]);
}

function kingy_ali_product_graph_read_json($dataset_key, $default = array()) {
    $path = kingy_ali_product_graph_json_file($dataset_key);
    if (!$path) {
        return $default;
    }

    $contents = file_get_contents($path);
    if (!is_string($contents) || $contents === '') {
        return $default;
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : $default;
}

function kingy_ali_product_graph_maybe_download_artifact() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $file = kingy_ali_product_graph_get_value('kingy_pg_download', 191);
    if ($file === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph artifacts.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_download_' . $file);

    $path = kingy_ali_product_graph_report_path($file);
    if (!$path) {
        wp_die(esc_html__('Product Graph artifact is unavailable or outside the allowed report path.', 'kingy-ai-launch-intelligence'));
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $content_type = $extension === 'csv' ? 'text/csv; charset=utf-8' : 'application/json; charset=utf-8';

    nocache_headers();
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function kingy_ali_product_graph_maybe_download_review_overlay() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_review_overlay_download', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph review metadata.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph review overlay export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_overlay_download_' . $format);

    $rows = kingy_ali_product_graph_flatten_review_overlay();
    $filename = 'kingy-product-graph-review-overlay-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('row_type', 'review_row_id', 'review_state', 'reviewer_notes', 'reviewer_user_id', 'reviewer_login', 'reviewer_display_name', 'reviewed_at_utc', 'source_artifact_id'));
        foreach ($rows as $row) {
            fputcsv(
                $out,
                array(
                    $row['row_type'],
                    $row['review_row_id'],
                    $row['review_state'],
                    $row['reviewer_notes'],
                    $row['reviewer_user_id'],
                    $row['reviewer_login'],
                    $row['reviewer_display_name'],
                    $row['reviewed_at_utc'],
                    $row['source_artifact_id'],
                )
            );
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'storage' => 'wordpress_option:' . kingy_ali_product_graph_review_option_name(),
            'records_count' => count($rows),
            'records' => $rows,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_overlay_cleanup_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_overlay_cleanup_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph overlay cleanup diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph overlay cleanup export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_overlay_cleanup_download_' . $format);

    $cleanup = kingy_ali_product_graph_overlay_cleanup_data();
    $rows = isset($cleanup['rows']) && is_array($cleanup['rows']) ? $cleanup['rows'] : array();
    $columns = kingy_ali_product_graph_overlay_cleanup_columns();
    $filename = 'kingy-product-graph-overlay-cleanup-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($rows as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = kingy_ali_product_graph_row_value($row, $column);
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_overlay_cleanup',
            'storage' => 'wordpress_option:' . kingy_ali_product_graph_review_option_name(),
            'cleanup' => $cleanup,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_health_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_health_export', 20);
    if ($format === '') {
        $format = kingy_ali_product_graph_get_value('kingy_pg_health_download', 20);
    }
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph health data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph health export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_health_download_' . $format);

    $health = kingy_ali_product_graph_health_data();
    $filename = 'kingy-product-graph-health-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('issue_type', 'priority', 'row_type', 'row_id', 'node_type', 'edge_type', 'review_state', 'title', 'detail'));
        foreach ($health['relationship_qa_queue'] as $row) {
            fputcsv(
                $out,
                array(
                    $row['issue_type'],
                    $row['priority'],
                    $row['row_type'],
                    $row['row_id'],
                    $row['node_type'],
                    $row['edge_type'],
                    $row['review_state'],
                    $row['title'],
                    $row['detail'],
                )
            );
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_health',
            'source' => 'existing_product_graph_review_datasets',
            'health' => $health,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_repair_planner_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_repair_planner_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph repair planner data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph repair planner export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_repair_planner_download_' . $format);

    $planner = kingy_ali_product_graph_repair_planner_data();
    $filename = 'kingy-product-graph-repair-planner-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_repair_planner_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($planner['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_repair_planner',
            'source' => 'existing_product_graph_health_opportunities_and_review_overlay',
            'planner' => $planner,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_evidence_pack_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_evidence_pack_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph evidence pack data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph evidence pack export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_evidence_pack_download_' . $format);

    $evidence = kingy_ali_product_graph_evidence_pack_data();
    $filename = 'kingy-product-graph-evidence-pack-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_evidence_pack_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($evidence['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_evidence_pack',
            'source' => 'existing_product_graph_datasets_opportunities_repair_planner_and_review_overlay',
            'evidence_pack' => $evidence,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_review_dashboard_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_review_dashboard_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph review dashboard data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph review dashboard export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_dashboard_download_' . $format);

    $dashboard = kingy_ali_product_graph_review_dashboard_data();
    $filename = 'kingy-product-graph-review-dashboard-' . gmdate('Ymd-His') . '.' . $format;

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('section', 'metric', 'value'));
        foreach (kingy_ali_product_graph_review_dashboard_csv_rows($dashboard) as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_review_dashboard',
            'source' => 'existing_product_graph_health_planner_evidence_opportunities_and_review_overlay',
            'dashboard' => $dashboard,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_reviewer_progress_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_reviewer_progress_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph reviewer progress data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph reviewer progress export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_reviewer_progress_download_' . $format);

    $progress = kingy_ali_product_graph_reviewer_progress_data();
    $filename = 'kingy-product-graph-reviewer-progress-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_reviewer_progress_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($progress['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_reviewer_progress',
            'source' => 'existing_product_graph_work_queue_opportunities_evidence_pack_and_review_overlay',
            'reviewer_progress' => $progress,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_work_queue_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_work_queue_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph work queue data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph work queue export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_work_queue_download_' . $format);

    $queue = kingy_ali_product_graph_work_queue_data();
    $filename = 'kingy-product-graph-work-queue-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_work_queue_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($queue['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_work_queue',
            'source' => 'existing_product_graph_health_repair_evidence_opportunities_and_review_overlay',
            'work_queue' => $queue,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_opportunities_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_opportunities_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph opportunities data.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph opportunities export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_opportunities_download_' . $format);

    $opportunities = kingy_ali_product_graph_opportunities_data();
    $filename = 'kingy-product-graph-opportunities-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_opportunity_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($opportunities['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_opportunities',
            'source' => 'existing_product_graph_review_datasets',
            'opportunities' => $opportunities,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_recommendations_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_recommendations_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link recommendations.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link recommendations export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_recommendations_download_' . $format);

    $recommendations = kingy_ali_product_graph_link_recommendations_data();
    $filename = 'kingy-product-graph-link-recommendations-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_recommendation_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($recommendations['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_internal_link_recommendations',
            'source' => 'existing_product_graph_edges_nodes_resolver_evidence_opportunities_and_review_overlay',
            'link_recommendations' => $recommendations,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_readiness_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_readiness_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link readiness diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link readiness export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_readiness_download_' . $format);

    $readiness = kingy_ali_product_graph_link_readiness_data();
    $filename = 'kingy-product-graph-link-readiness-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_readiness_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($readiness['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_internal_link_readiness',
            'source' => 'derived_from_link_recommendations_and_review_overlay',
            'link_readiness' => $readiness,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_plan_preview_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_plan_preview_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link plan preview diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link plan preview export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_plan_preview_download_' . $format);

    $preview = kingy_ali_product_graph_link_plan_preview_data();
    $filename = 'kingy-product-graph-link-plan-preview-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_plan_preview_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($preview['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_internal_link_plan_preview',
            'source' => 'derived_from_link_readiness_and_review_overlay',
            'link_plan_preview' => $preview,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_dry_run_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_dry_run_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link dry-run diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link dry-run export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_dry_run_download_' . $format);

    $dry_run = kingy_ali_product_graph_link_dry_run_data();
    $filename = 'kingy-product-graph-link-dry-run-package-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_dry_run_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($dry_run['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'no_write_product_graph_internal_link_insertion_dry_run_package',
            'source' => 'derived_from_link_plan_preview_source_context_audit_and_review_overlay',
            'link_dry_run_package' => $dry_run,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_source_context_audit_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_source_context_audit_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph source context audit diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph source context audit export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_source_context_audit_download_' . $format);

    $audit = kingy_ali_product_graph_source_context_audit_data();
    $filename = 'kingy-product-graph-source-context-audit-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_source_context_audit_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($audit['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_source_context_audit',
            'source' => 'derived_from_link_recommendations_readiness_review_overlay_and_read_only_wordpress_content',
            'source_context_audit' => $audit,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_review_batches_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_review_batches_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link review batch diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link review batch export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_review_batches_download_' . $format);

    $batches = kingy_ali_product_graph_link_review_batches_data();
    $filename = 'kingy-product-graph-link-review-batches-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_review_batch_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($batches['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_internal_link_review_batches',
            'source' => 'derived_from_link_recommendations_readiness_source_context_and_review_overlay',
            'link_review_batches' => $batches,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_link_batch_progress_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph link batch progress diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph link batch progress export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_link_batch_progress_download_' . $format);

    $progress = kingy_ali_product_graph_link_batch_progress_data();
    $filename = 'kingy-product-graph-link-batch-progress-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_link_batch_progress_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($progress['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_internal_link_batch_progress',
            'source' => 'derived_from_link_review_batches_readiness_source_context_and_review_overlay',
            'link_batch_progress' => $progress,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_download_stage3_closeout_export() {
    if (kingy_ali_product_graph_get_value('page', 80) !== 'kingy-ali-product-graph') {
        return;
    }

    $format = kingy_ali_product_graph_get_value('kingy_pg_stage3_closeout_export', 20);
    if ($format === '') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to download Product Graph Stage 3 closeout diagnostics.', 'kingy-ai-launch-intelligence'));
    }

    if (!in_array($format, array('json', 'csv'), true)) {
        wp_die(esc_html__('Unsupported Product Graph Stage 3 closeout export format.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_stage3_closeout_download_' . $format);

    $closeout = kingy_ali_product_graph_stage3_closeout_data();
    $filename = 'kingy-product-graph-stage3-closeout-' . gmdate('Ymd-His') . '.' . $format;
    $columns = kingy_ali_product_graph_stage3_closeout_columns();

    nocache_headers();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);
        foreach ($closeout['rows'] as $row) {
            $values = array();
            foreach ($columns as $column) {
                $values[] = isset($row[$column]) ? $row[$column] : '';
            }
            fputcsv($out, $values);
        }
        fclose($out);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode(
        array(
            'exported_at_utc' => gmdate('c'),
            'mode' => 'read_only_product_graph_stage3_closeout_stage4_readiness',
            'source' => 'derived_from_link_recommendations_readiness_plan_preview_source_context_batches_batch_progress_and_review_overlay',
            'stage3_closeout' => $closeout,
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function kingy_ali_product_graph_maybe_save_review_state() {
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
    if ($method !== 'POST') {
        return;
    }

    if (kingy_ali_product_graph_post_value('kingy_pg_review_action', 80) !== 'save_review_state') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to save Product Graph review metadata.', 'kingy-ai-launch-intelligence'));
    }

    check_admin_referer('kingy_ali_product_graph_review_state', 'kingy_pg_review_nonce');

    $row_type = sanitize_key(kingy_ali_product_graph_post_value('kingy_pg_row_type', 80));
    $row_id = kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_post_value('kingy_pg_review_row_id', 500));
    $review_state = sanitize_key(kingy_ali_product_graph_post_value('kingy_pg_review_state', 80));
    $notes = kingy_ali_product_graph_sanitize_review_notes(isset($_POST['kingy_pg_reviewer_notes']) ? $_POST['kingy_pg_reviewer_notes'] : '');
    $source_artifact_id = sanitize_file_name(kingy_ali_product_graph_post_value('kingy_pg_source_artifact_id', 191));

    $allowed_states = kingy_ali_product_graph_allowed_review_states();
    $tab = kingy_ali_product_graph_review_tab_for_type($row_type);

    $validation_error = '';
    if (!$tab) {
        $validation_error = 'invalid_row_type';
    } elseif ($row_id === '') {
        $validation_error = 'missing_row_id';
    } elseif (!isset($allowed_states[$review_state])) {
        $validation_error = 'invalid_review_state';
    } elseif (!kingy_ali_product_graph_review_save_row_is_valid($tab, $row_type, $row_id)) {
        $validation_error = 'invalid_review_row';
    }

    if ($validation_error !== '') {
        kingy_ali_product_graph_redirect_after_review_save($tab ? $tab : 'summary', false, $validation_error);
    }

    $user = wp_get_current_user();
    $record = array(
        'row_type' => $row_type,
        'review_row_id' => $row_id,
        'review_state' => $review_state,
        'reviewer_notes' => $notes,
        'reviewer_user_id' => absint($user->ID),
        'reviewer_login' => sanitize_user($user->user_login, true),
        'reviewer_display_name' => sanitize_text_field($user->display_name),
        'reviewed_at_utc' => gmdate('c'),
        'source_artifact_id' => $source_artifact_id,
    );

    $overlay = kingy_ali_product_graph_review_overlay();
    if (!isset($overlay[$row_type]) || !is_array($overlay[$row_type])) {
        $overlay[$row_type] = array();
    }
    $overlay[$row_type][$row_id] = $record;

    update_option(kingy_ali_product_graph_review_option_name(), $overlay, false);
    kingy_ali_product_graph_redirect_after_review_save($tab, true);
}

function kingy_ali_product_graph_redirect_after_review_save($tab, $saved, $error = '') {
    $error = sanitize_key($error);
    $return_tab = sanitize_key(kingy_ali_product_graph_post_value('kingy_pg_review_return_tab', 80));
    if ($return_tab === 'source_context_audit') {
        $audit_id = kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_post_value('kingy_pg_review_return_source_context_audit_id', 120));
        if ($audit_id !== '') {
            $args = array(
                'page' => 'kingy-ali-product-graph',
                'kingy_pg_tab' => 'source_context_audit',
                'kingy_pg_source_context_audit_id' => $audit_id,
                'kingy_pg_review_saved' => $saved ? '1' : '0',
            );
            if (!$saved && $error !== '') {
                $args['kingy_pg_review_error'] = $error;
            }
            $redirect = add_query_arg(
                $args,
                admin_url('admin.php')
            );

            wp_safe_redirect($redirect);
            exit;
        }
    }

    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => sanitize_key($tab),
        'kingy_pg_review_saved' => $saved ? '1' : '0',
    );
    if (!$saved && $error !== '') {
        $args['kingy_pg_review_error'] = $error;
    }

    $redirect = add_query_arg($args, admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}

function kingy_ali_product_graph_flatten_review_overlay() {
    $overlay = kingy_ali_product_graph_review_overlay();
    $rows = array();

    foreach ($overlay as $row_type => $records) {
        if (!is_array($records)) {
            continue;
        }

        foreach ($records as $row_id => $record) {
            if (!is_array($record)) {
                continue;
            }
            $rows[] = array(
                'row_type' => sanitize_key(isset($record['row_type']) ? $record['row_type'] : $row_type),
                'review_row_id' => kingy_ali_product_graph_sanitize_row_id(isset($record['review_row_id']) ? $record['review_row_id'] : $row_id),
                'review_state' => sanitize_key(isset($record['review_state']) ? $record['review_state'] : 'unreviewed'),
                'reviewer_notes' => kingy_ali_product_graph_sanitize_review_notes(isset($record['reviewer_notes']) ? $record['reviewer_notes'] : ''),
                'reviewer_user_id' => absint(isset($record['reviewer_user_id']) ? $record['reviewer_user_id'] : 0),
                'reviewer_login' => sanitize_user(isset($record['reviewer_login']) ? $record['reviewer_login'] : '', true),
                'reviewer_display_name' => sanitize_text_field(isset($record['reviewer_display_name']) ? $record['reviewer_display_name'] : ''),
                'reviewed_at_utc' => sanitize_text_field(isset($record['reviewed_at_utc']) ? $record['reviewed_at_utc'] : ''),
                'source_artifact_id' => sanitize_file_name(isset($record['source_artifact_id']) ? $record['source_artifact_id'] : ''),
            );
        }
    }

    return $rows;
}

function kingy_ali_product_graph_review_overlay_summary() {
    $rows = kingy_ali_product_graph_flatten_review_overlay();
    $allowed_states = kingy_ali_product_graph_allowed_review_states();
    $counts_by_state = array();
    $counts_by_type = array();
    $latest_reviewed_at = '';

    foreach ($allowed_states as $state => $label) {
        $counts_by_state[$state] = 0;
    }

    foreach (kingy_ali_product_graph_reviewable_tabs() as $row_type) {
        $counts_by_type[$row_type] = 0;
    }

    foreach ($rows as $row) {
        $state = isset($row['review_state']) && isset($counts_by_state[$row['review_state']]) ? $row['review_state'] : 'unreviewed';
        $row_type = isset($row['row_type']) ? sanitize_key($row['row_type']) : '';
        $reviewed_at = isset($row['reviewed_at_utc']) ? sanitize_text_field($row['reviewed_at_utc']) : '';

        $counts_by_state[$state]++;
        if ($row_type !== '') {
            if (!isset($counts_by_type[$row_type])) {
                $counts_by_type[$row_type] = 0;
            }
            $counts_by_type[$row_type]++;
        }
        if ($reviewed_at !== '' && ($latest_reviewed_at === '' || strcmp($reviewed_at, $latest_reviewed_at) > 0)) {
            $latest_reviewed_at = $reviewed_at;
        }
    }

    return array(
        'total_saved_review_records' => count($rows),
        'latest_reviewed_at_utc' => $latest_reviewed_at,
        'counts_by_state' => $counts_by_state,
        'counts_by_row_type' => $counts_by_type,
    );
}

function kingy_ali_product_graph_overlay_cleanup_columns() {
    return array(
        'cleanup_id',
        'row_type',
        'review_row_id',
        'review_state',
        'reviewer',
        'reviewed_at_utc',
        'source_artifact_id',
        'source_status',
        'note_present',
        'followup_state',
        'very_old_review',
        'reviewed_age_days',
        'recommended_safe_next_action',
    );
}

function kingy_ali_product_graph_overlay_cleanup_data() {
    $overlay_rows = kingy_ali_product_graph_flatten_review_overlay();
    $rows = array();
    $counts_by_type = array();
    $counts_by_state = array();
    $counts_by_source_status = array();
    $counts_by_note_present = array('yes' => 0, 'no' => 0);
    $followup_states = array('needs_source', 'needs_refresh', 'needs_canonical_review');
    $followup_count = 0;
    $very_old_count = 0;

    foreach ($overlay_rows as $overlay_row) {
        if (!is_array($overlay_row)) {
            continue;
        }

        $row = kingy_ali_product_graph_overlay_cleanup_row($overlay_row);
        $rows[] = $row;

        kingy_ali_product_graph_increment_count($counts_by_type, kingy_ali_product_graph_row_value($row, 'row_type'));
        kingy_ali_product_graph_increment_count($counts_by_state, kingy_ali_product_graph_row_value($row, 'review_state'));
        kingy_ali_product_graph_increment_count($counts_by_source_status, kingy_ali_product_graph_row_value($row, 'source_status'));
        $note_present = kingy_ali_product_graph_row_value($row, 'note_present') === 'yes' ? 'yes' : 'no';
        $counts_by_note_present[$note_present]++;
        if (in_array(kingy_ali_product_graph_row_value($row, 'review_state'), $followup_states, true)) {
            $followup_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'very_old_review') === 'yes') {
            $very_old_count++;
        }
    }

    usort($rows, 'kingy_ali_product_graph_sort_overlay_cleanup_rows');

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_overlay_cleanup',
        'total_overlay_records' => count($rows),
        'source_rows_existing' => isset($counts_by_source_status['source_exists']) ? absint($counts_by_source_status['source_exists']) : 0,
        'source_rows_missing_or_stale' => isset($counts_by_source_status['source_missing_or_stale']) ? absint($counts_by_source_status['source_missing_or_stale']) : 0,
        'source_rows_unverifiable' => isset($counts_by_source_status['source_unverifiable']) ? absint($counts_by_source_status['source_unverifiable']) : 0,
        'empty_note_records' => isset($counts_by_note_present['no']) ? absint($counts_by_note_present['no']) : 0,
        'followup_state_records' => $followup_count,
        'very_old_review_records' => $very_old_count,
        'counts_by_row_type' => $counts_by_type,
        'counts_by_review_state' => $counts_by_state,
        'counts_by_source_status' => $counts_by_source_status,
        'counts_by_note_present' => $counts_by_note_present,
        'rows' => $rows,
    );
}

function kingy_ali_product_graph_overlay_cleanup_row($overlay_row) {
    $row_type = sanitize_key(kingy_ali_product_graph_row_value($overlay_row, 'row_type'));
    $review_row_id = kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_row_value($overlay_row, 'review_row_id'));
    $review_state = sanitize_key(kingy_ali_product_graph_row_value($overlay_row, 'review_state', 'unreviewed'));
    $reviewer = kingy_ali_product_graph_row_value($overlay_row, 'reviewer_display_name', kingy_ali_product_graph_row_value($overlay_row, 'reviewer_login'));
    $reviewed_at = sanitize_text_field(kingy_ali_product_graph_row_value($overlay_row, 'reviewed_at_utc'));
    $source_artifact_id = sanitize_text_field(kingy_ali_product_graph_row_value($overlay_row, 'source_artifact_id'));
    $notes = kingy_ali_product_graph_sanitize_review_notes(kingy_ali_product_graph_row_value($overlay_row, 'reviewer_notes'));
    $note_present = trim($notes) !== '' ? 'yes' : 'no';
    $source_status = kingy_ali_product_graph_overlay_cleanup_source_status($row_type, $review_row_id);
    $age_days = kingy_ali_product_graph_overlay_cleanup_review_age_days($reviewed_at);
    $very_old_review = $age_days >= 180 ? 'yes' : 'no';
    $followup_state = in_array($review_state, array('needs_source', 'needs_refresh', 'needs_canonical_review'), true) ? 'yes' : 'no';
    $cleanup_id = 'overlay-cleanup:' . substr(md5(implode('|', array($row_type, $review_row_id, $review_state, $source_artifact_id))), 0, 16);

    return array(
        'cleanup_id' => $cleanup_id,
        'row_type' => $row_type,
        'review_row_id' => $review_row_id,
        'review_state' => $review_state,
        'reviewer' => sanitize_text_field($reviewer),
        'reviewed_at_utc' => $reviewed_at,
        'source_artifact_id' => $source_artifact_id,
        'source_status' => $source_status,
        'note_present' => $note_present,
        'followup_state' => $followup_state,
        'very_old_review' => $very_old_review,
        'reviewed_age_days' => $age_days >= 0 ? (string) $age_days : '',
        'recommended_safe_next_action' => kingy_ali_product_graph_overlay_cleanup_next_action($source_status, $note_present, $followup_state, $very_old_review),
        'reviewer_notes' => $notes,
    );
}

function kingy_ali_product_graph_overlay_cleanup_source_status($row_type, $review_row_id) {
    $tab = kingy_ali_product_graph_review_tab_for_type($row_type);
    if ($tab === '' || $review_row_id === '') {
        return 'source_unverifiable';
    }

    return kingy_ali_product_graph_row_exists($tab, $review_row_id) ? 'source_exists' : 'source_missing_or_stale';
}

function kingy_ali_product_graph_overlay_cleanup_review_age_days($reviewed_at) {
    if ($reviewed_at === '') {
        return -1;
    }

    $timestamp = strtotime($reviewed_at);
    if (!$timestamp) {
        return -1;
    }

    return max(0, (int) floor((time() - $timestamp) / DAY_IN_SECONDS));
}

function kingy_ali_product_graph_overlay_cleanup_next_action($source_status, $note_present, $followup_state, $very_old_review) {
    if ($source_status === 'source_missing_or_stale') {
        return __('Regenerate or inspect the current graph snapshot before deciding whether this overlay record is stale.', 'kingy-ai-launch-intelligence');
    }
    if ($source_status === 'source_unverifiable') {
        return __('Review the row type and source artifact manually; do not delete or rewrite overlay records from this screen.', 'kingy-ai-launch-intelligence');
    }
    if ($followup_state === 'yes') {
        return __('Review the saved follow-up state, gather evidence, then update reviewer metadata from the source review screen if needed.', 'kingy-ai-launch-intelligence');
    }
    if ($note_present === 'no') {
        return __('Optional: add reviewer notes from the source review screen before using this metadata in future planning.', 'kingy-ai-launch-intelligence');
    }
    if ($very_old_review === 'yes') {
        return __('Optional: re-check this older reviewer decision against a fresh graph snapshot before relying on it.', 'kingy-ai-launch-intelligence');
    }

    return __('No cleanup action needed; preserve this overlay record as reviewer metadata.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_product_graph_sort_overlay_cleanup_rows($a, $b) {
    $source_order = array('source_missing_or_stale' => 0, 'source_unverifiable' => 1, 'source_exists' => 2);
    $a_source = kingy_ali_product_graph_row_value($a, 'source_status');
    $b_source = kingy_ali_product_graph_row_value($b, 'source_status');
    $a_weight = isset($source_order[$a_source]) ? $source_order[$a_source] : 9;
    $b_weight = isset($source_order[$b_source]) ? $source_order[$b_source] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $a_followup = kingy_ali_product_graph_row_value($a, 'followup_state') === 'yes' ? 0 : 1;
    $b_followup = kingy_ali_product_graph_row_value($b, 'followup_state') === 'yes' ? 0 : 1;
    if ($a_followup !== $b_followup) {
        return $a_followup - $b_followup;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'review_row_id'), kingy_ali_product_graph_row_value($b, 'review_row_id'));
}

function kingy_ali_product_graph_row_value($row, $key, $default = '') {
    if (!is_array($row) || !array_key_exists($key, $row)) {
        return $default;
    }

    $value = $row[$key];
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_array($value)) {
        return kingy_ali_product_graph_display_value($value);
    }
    if (is_scalar($value)) {
        return (string) $value;
    }

    return $default;
}

function kingy_ali_product_graph_increment_count(&$counts, $key) {
    $key = sanitize_text_field((string) $key);
    if ($key === '') {
        $key = '(blank)';
    }
    if (!isset($counts[$key])) {
        $counts[$key] = 0;
    }
    $counts[$key]++;
}

function kingy_ali_product_graph_health_data() {
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $resolver = kingy_ali_product_graph_read_json('resolver', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $model_inventory = kingy_ali_product_graph_read_json('model_inventory', array());
    $overlay_summary = kingy_ali_product_graph_review_overlay_summary();
    $overlay_rows = kingy_ali_product_graph_flatten_review_overlay();

    $node_keys = array();
    $source_keys = array();
    $target_keys = array();
    $node_kind_counts = array();
    $node_entity_type_counts = array();
    $edge_type_counts = array();
    $edge_confidence_counts = array();
    $edge_status_counts = array();
    $url_backed_edge_status_counts = array();
    $duplicate_edge_candidates = array();
    $missing_source_edges = array();
    $missing_target_edges = array();
    $orphan_nodes = array();
    $nodes_without_outgoing = array();
    $relationship_queue = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key !== '') {
            $node_keys[$node_key] = $node;
        }
        kingy_ali_product_graph_increment_count($node_kind_counts, kingy_ali_product_graph_row_value($node, 'node_kind'));
        kingy_ali_product_graph_increment_count($node_entity_type_counts, kingy_ali_product_graph_row_value($node, 'entity_type'));
    }

    $edge_signatures = array();
    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $edge_type = kingy_ali_product_graph_row_value($edge, 'edge_type');
        $confidence = kingy_ali_product_graph_row_value($edge, 'confidence_class', kingy_ali_product_graph_row_value($edge, 'confidence'));
        $status = kingy_ali_product_graph_row_value($edge, 'status');
        $source = kingy_ali_product_graph_row_value($edge, 'source');
        $target = kingy_ali_product_graph_row_value($edge, 'target');
        $signature = implode('|', array($source, $target, $edge_type, kingy_ali_product_graph_row_value($edge, 'field'), kingy_ali_product_graph_row_value($edge, 'value')));

        kingy_ali_product_graph_increment_count($edge_type_counts, $edge_type);
        kingy_ali_product_graph_increment_count($edge_confidence_counts, $confidence);
        kingy_ali_product_graph_increment_count($edge_status_counts, $status);

        if ($source !== '') {
            $source_keys[$source] = true;
            if (!isset($node_keys[$source])) {
                $missing_source_edges[] = $edge;
            }
        }
        if ($target !== '') {
            $target_keys[$target] = true;
            if (!isset($node_keys[$target])) {
                $missing_target_edges[] = $edge;
            }
        }

        if (!isset($edge_signatures[$signature])) {
            $edge_signatures[$signature] = array();
        }
        $edge_signatures[$signature][] = $edge;

        if (strtolower($confidence) === 'url-backed' || stripos($edge_type, 'related_') === 0 || kingy_ali_product_graph_row_value($edge, 'canonical_resolution_status') !== '') {
            $resolution = kingy_ali_product_graph_row_value($edge, 'canonical_resolution_status');
            if ($resolution === '') {
                $resolution = kingy_ali_product_graph_row_value($edge, 'target_url') !== '' ? 'url_present_no_canonical_status' : 'missing_url_resolution';
            }
            kingy_ali_product_graph_increment_count($url_backed_edge_status_counts, $resolution);
        }
    }

    foreach ($edge_signatures as $signature => $signature_edges) {
        if (count($signature_edges) > 1) {
            $duplicate_edge_candidates[] = array(
                'signature' => $signature,
                'count' => count($signature_edges),
                'edges' => $signature_edges,
            );
        }
    }

    foreach ($node_keys as $node_key => $node) {
        if (!isset($source_keys[$node_key]) && !isset($target_keys[$node_key])) {
            $orphan_nodes[] = $node;
        }
        if (!isset($source_keys[$node_key])) {
            $nodes_without_outgoing[] = $node;
        }
    }

    $model_inventory_status = 'unknown';
    if (isset($model_inventory[0]) && is_array($model_inventory[0])) {
        $model_inventory_status = kingy_ali_product_graph_row_value($model_inventory[0], 'status', 'unknown');
    }

    foreach (array_slice($missing_source_edges, 0, 100) as $edge) {
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('missing_source_node', 'high', 'edge', kingy_ali_product_graph_row_value($edge, 'review_row_id', kingy_ali_product_graph_row_value($edge, 'edge_id')), kingy_ali_product_graph_row_value($edge, 'source_node_kind'), kingy_ali_product_graph_row_value($edge, 'edge_type'), kingy_ali_product_graph_row_value($edge, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($edge, 'source_title'), 'Edge source is not present in the loaded node map.');
    }
    foreach (array_slice($missing_target_edges, 0, 100) as $edge) {
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('missing_target_node', 'high', 'edge', kingy_ali_product_graph_row_value($edge, 'review_row_id', kingy_ali_product_graph_row_value($edge, 'edge_id')), kingy_ali_product_graph_row_value($edge, 'target_node_kind'), kingy_ali_product_graph_row_value($edge, 'edge_type'), kingy_ali_product_graph_row_value($edge, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($edge, 'target_title'), 'Edge target is not present in the loaded node map.');
    }
    foreach (array_slice($duplicate_edge_candidates, 0, 100) as $candidate) {
        $first_edge = isset($candidate['edges'][0]) && is_array($candidate['edges'][0]) ? $candidate['edges'][0] : array();
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('duplicate_edge_candidate', 'medium', 'edge', kingy_ali_product_graph_row_value($first_edge, 'review_row_id', kingy_ali_product_graph_row_value($first_edge, 'edge_id')), kingy_ali_product_graph_row_value($first_edge, 'source_node_kind'), kingy_ali_product_graph_row_value($first_edge, 'edge_type'), kingy_ali_product_graph_row_value($first_edge, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($first_edge, 'source_title'), sprintf('Duplicate signature appears %d times.', absint($candidate['count'])));
    }
    foreach (array_slice($orphan_nodes, 0, 100) as $node) {
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('orphan_node', 'medium', 'node', kingy_ali_product_graph_row_value($node, 'review_row_id', kingy_ali_product_graph_row_value($node, 'node_key')), kingy_ali_product_graph_row_value($node, 'node_kind'), '', kingy_ali_product_graph_row_value($node, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($node, 'title'), 'Node has no incoming or outgoing loaded graph edges.');
    }
    foreach (array_slice($nodes_without_outgoing, 0, 100) as $node) {
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('no_useful_outgoing_edges', 'low', 'node', kingy_ali_product_graph_row_value($node, 'review_row_id', kingy_ali_product_graph_row_value($node, 'node_key')), kingy_ali_product_graph_row_value($node, 'node_kind'), '', kingy_ali_product_graph_row_value($node, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($node, 'title'), 'Node has no outgoing loaded graph edges.');
    }
    foreach (array_slice($unresolved_queue, 0, 100) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('unresolved_url_backed_edge', 'medium', 'unresolved_queue', kingy_ali_product_graph_row_value($row, 'review_row_id', kingy_ali_product_graph_row_value($row, 'normalized_url')), kingy_ali_product_graph_row_value($row, 'node_kind', 'internal_url'), '', kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed'), kingy_ali_product_graph_row_value($row, 'title', kingy_ali_product_graph_row_value($row, 'normalized_url')), kingy_ali_product_graph_row_value($row, 'review_note', 'Internal URL remains review-only.'));
    }
    if ($model_inventory_status === 'model_inventory_gap') {
        $relationship_queue[] = kingy_ali_product_graph_health_queue_row('model_inventory_blocker', 'high', 'model_inventory', 'model-inventory:kingy_ai_model', 'kingy_ai_model', '', 'model_inventory_blocked', 'kingy_ai_model inventory', 'No loaded kingy_ai_model records are available in the current graph review dataset.');
    }
    foreach ($overlay_rows as $row) {
        $state = isset($row['review_state']) ? $row['review_state'] : '';
        if (in_array($state, array('needs_source', 'needs_refresh', 'needs_canonical_review'), true)) {
            $relationship_queue[] = kingy_ali_product_graph_health_queue_row('review_state_followup', 'medium', isset($row['row_type']) ? $row['row_type'] : '', isset($row['review_row_id']) ? $row['review_row_id'] : '', '', '', $state, isset($row['source_artifact_id']) ? $row['source_artifact_id'] : '', isset($row['reviewer_notes']) ? $row['reviewer_notes'] : '');
        }
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'metrics' => array(
            'total_nodes' => count($nodes),
            'total_edges' => count($edges),
            'total_resolved_urls' => count($resolver),
            'unresolved_queue_count' => count($unresolved_queue),
            'orphan_nodes' => count($orphan_nodes),
            'missing_source_nodes' => count($missing_source_edges),
            'missing_target_nodes' => count($missing_target_edges),
            'duplicate_edge_groups' => count($duplicate_edge_candidates),
            'nodes_without_useful_outgoing_edges' => count($nodes_without_outgoing),
            'model_inventory_status' => $model_inventory_status,
        ),
        'node_kind_counts' => $node_kind_counts,
        'node_entity_type_counts' => $node_entity_type_counts,
        'edge_type_counts' => $edge_type_counts,
        'edge_confidence_counts' => $edge_confidence_counts,
        'edge_status_counts' => $edge_status_counts,
        'url_backed_edge_status_counts' => $url_backed_edge_status_counts,
        'review_overlay_summary' => $overlay_summary,
        'relationship_qa_queue' => $relationship_queue,
    );
}

function kingy_ali_product_graph_health_queue_row($issue_type, $priority, $row_type, $row_id, $node_type, $edge_type, $review_state, $title, $detail) {
    return array(
        'issue_type' => sanitize_key($issue_type),
        'priority' => sanitize_key($priority),
        'row_type' => sanitize_key($row_type),
        'row_id' => kingy_ali_product_graph_sanitize_row_id($row_id),
        'node_type' => sanitize_key($node_type),
        'edge_type' => sanitize_key($edge_type),
        'review_state' => sanitize_key($review_state),
        'title' => sanitize_text_field((string) $title),
        'detail' => sanitize_text_field((string) $detail),
    );
}

function kingy_ali_product_graph_repair_planner_columns() {
    return array(
        'planner_id',
        'issue_family',
        'priority',
        'affected_row_count',
        'example_row_ids',
        'recommended_safe_next_action',
        'required_evidence',
        'blocked_by',
        'review_overlay_status_summary',
        'review_states',
    );
}

function kingy_ali_product_graph_repair_planner_data() {
    $health = kingy_ali_product_graph_health_data();
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $metrics = isset($health['metrics']) && is_array($health['metrics']) ? $health['metrics'] : array();
    $queue = isset($health['relationship_qa_queue']) && is_array($health['relationship_qa_queue']) ? $health['relationship_qa_queue'] : array();
    $opportunity_rows = isset($opportunities['rows']) && is_array($opportunities['rows']) ? $opportunities['rows'] : array();
    $groups = array();

    foreach ($queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $issue_type = kingy_ali_product_graph_row_value($row, 'issue_type');
        if (!isset($groups[$issue_type])) {
            $groups[$issue_type] = array();
        }
        $groups[$issue_type][] = $row;
    }

    $high_confidence_opportunities = array();
    foreach ($opportunity_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $confidence = kingy_ali_product_graph_row_value($row, 'confidence');
        $priority = kingy_ali_product_graph_row_value($row, 'priority');
        if (in_array($confidence, array('verified', 'url-backed'), true) || $priority === 'high') {
            $high_confidence_opportunities[] = $row;
        }
    }

    $rows = array(
        kingy_ali_product_graph_repair_planner_row(
            'missing_target_node_repairs',
            'high',
            isset($metrics['missing_target_nodes']) ? absint($metrics['missing_target_nodes']) : count(isset($groups['missing_target_node']) ? $groups['missing_target_node'] : array()),
            isset($groups['missing_target_node']) ? $groups['missing_target_node'] : array(),
            'row_id',
            __('Review missing target references, identify whether each target should be a typed node, taxonomy node, URL node, or stale relationship candidate.', 'kingy-ai-launch-intelligence'),
            __('Current edge row, target value, source artifact, resolver record, and source evidence for the intended target.', 'kingy-ai-launch-intelligence'),
            __('target_node_mapping', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'orphan_node_review_batch',
            'medium',
            isset($metrics['orphan_nodes']) ? absint($metrics['orphan_nodes']) : count(isset($groups['orphan_node']) ? $groups['orphan_node'] : array()),
            isset($groups['orphan_node']) ? $groups['orphan_node'] : array(),
            'row_id',
            __('Review whether each orphan node needs an incoming relationship, outgoing relationship, or should remain a standalone node.', 'kingy-ai-launch-intelligence'),
            __('Node source fields, existing URL or CPT identity, and at least one source-backed relationship candidate.', 'kingy-ai-launch-intelligence'),
            __('relationship_evidence', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'no_outgoing_edge_enrichment_batch',
            'low',
            isset($metrics['nodes_without_useful_outgoing_edges']) ? absint($metrics['nodes_without_useful_outgoing_edges']) : count(isset($groups['no_useful_outgoing_edges']) ? $groups['no_useful_outgoing_edges'] : array()),
            isset($groups['no_useful_outgoing_edges']) ? $groups['no_useful_outgoing_edges'] : array(),
            'row_id',
            __('Prioritize useful outgoing context only where it improves navigation, evaluation, or commercial routing.', 'kingy-ai-launch-intelligence'),
            __('Source-backed related tool, company, guide, course, calculator, video, sponsor path, or next-link evidence.', 'kingy-ai-launch-intelligence'),
            __('outgoing_relationship_evidence', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'unresolved_url_resolver_batch',
            'medium',
            isset($metrics['unresolved_queue_count']) ? absint($metrics['unresolved_queue_count']) : count(isset($groups['unresolved_url_backed_edge']) ? $groups['unresolved_url_backed_edge'] : array()),
            isset($groups['unresolved_url_backed_edge']) ? $groups['unresolved_url_backed_edge'] : array(),
            'row_id',
            __('Classify unresolved internal URLs as typed nodes, canonical resolver mappings, review-only historical references, or stale candidates.', 'kingy-ai-launch-intelligence'),
            __('Normalized URL, source row, canonical resolver evidence, and page-node classification evidence.', 'kingy-ai-launch-intelligence'),
            __('resolver_mapping', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'model_inventory_unblock_batch',
            'high',
            isset($metrics['model_inventory_status']) && $metrics['model_inventory_status'] === 'model_inventory_gap' ? 1 : 0,
            isset($groups['model_inventory_blocker']) ? $groups['model_inventory_blocker'] : array(),
            'row_id',
            __('Choose the source of truth for kingy_ai_model records before adding model nodes or model relationships.', 'kingy-ai-launch-intelligence'),
            __('Authenticated or local model inventory export, registered CPT field map, source hash, and reviewer acceptance.', 'kingy-ai-launch-intelligence'),
            __('model_inventory_source', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'high_confidence_opportunity_batch',
            'medium',
            count($high_confidence_opportunities),
            $high_confidence_opportunities,
            'opportunity_id',
            __('Review verified, URL-backed, and high-priority opportunity candidates before any future graph relationship sync work.', 'kingy-ai-launch-intelligence'),
            __('Opportunity detail context, source node, target candidate, resolver evidence, and reviewer overlay state.', 'kingy-ai-launch-intelligence'),
            __('human_review', 'kingy-ai-launch-intelligence')
        ),
        kingy_ali_product_graph_repair_planner_row(
            'reviewer_followup_batch',
            'medium',
            count(isset($groups['review_state_followup']) ? $groups['review_state_followup'] : array()),
            isset($groups['review_state_followup']) ? $groups['review_state_followup'] : array(),
            'row_id',
            __('Resolve saved reviewer follow-up states before promoting related graph work into implementation planning.', 'kingy-ai-launch-intelligence'),
            __('Reviewer note, source artifact ID, current row context, and updated evidence or refresh decision.', 'kingy-ai-launch-intelligence'),
            __('review_overlay_followup', 'kingy-ai-launch-intelligence')
        ),
    );

    $counts_by_family = array();
    $counts_by_priority = array();
    $counts_by_blocker = array();
    foreach ($rows as $row) {
        $affected_count = absint(isset($row['affected_row_count']) ? $row['affected_row_count'] : 0);
        $family = isset($row['issue_family']) ? $row['issue_family'] : '';
        $priority = isset($row['priority']) ? $row['priority'] : '';
        $blocker = isset($row['blocked_by']) ? $row['blocked_by'] : '';

        if ($family !== '') {
            $counts_by_family[$family] = isset($counts_by_family[$family]) ? $counts_by_family[$family] + $affected_count : $affected_count;
        }
        if ($priority !== '') {
            $counts_by_priority[$priority] = isset($counts_by_priority[$priority]) ? $counts_by_priority[$priority] + $affected_count : $affected_count;
        }
        if ($blocker !== '') {
            $counts_by_blocker[$blocker] = isset($counts_by_blocker[$blocker]) ? $counts_by_blocker[$blocker] + $affected_count : $affected_count;
        }
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_repair_planner',
        'row_count' => count($rows),
        'counts_by_family' => $counts_by_family,
        'counts_by_priority' => $counts_by_priority,
        'counts_by_blocker' => $counts_by_blocker,
        'rows' => $rows,
    );
}

function kingy_ali_product_graph_repair_planner_row($issue_family, $priority, $affected_row_count, $example_rows, $example_id_field, $safe_next_action, $required_evidence, $blocked_by) {
    $issue_family = sanitize_key($issue_family);
    $example_rows = is_array($example_rows) ? array_values(array_filter($example_rows, 'is_array')) : array();
    $example_row_ids = kingy_ali_product_graph_repair_planner_example_ids($example_rows, $example_id_field);
    $review_summary = kingy_ali_product_graph_repair_planner_review_summary($example_rows);

    return array(
        'planner_id' => 'repair-planner:' . substr(md5($issue_family . '|' . $blocked_by), 0, 16),
        'issue_family' => $issue_family,
        'priority' => sanitize_key($priority),
        'affected_row_count' => absint($affected_row_count),
        'example_row_ids' => $example_row_ids,
        'recommended_safe_next_action' => sanitize_text_field((string) $safe_next_action),
        'required_evidence' => sanitize_text_field((string) $required_evidence),
        'blocked_by' => sanitize_key($blocked_by),
        'review_overlay_status_summary' => $review_summary['summary'],
        'review_states' => $review_summary['states'],
        'example_rows' => array_slice($example_rows, 0, 25),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_repair_planner_example_ids($rows, $id_field) {
    $ids = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = kingy_ali_product_graph_row_value($row, $id_field);
        if ($id === '') {
            $id = kingy_ali_product_graph_row_value($row, 'row_id');
        }
        if ($id === '') {
            $id = kingy_ali_product_graph_row_value($row, 'review_row_id');
        }
        if ($id !== '') {
            $ids[] = $id;
        }
        if (count($ids) >= 5) {
            break;
        }
    }

    return implode(', ', array_map('kingy_ali_product_graph_sanitize_row_id', $ids));
}

function kingy_ali_product_graph_repair_planner_review_summary($rows) {
    $counts = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $state = kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed');
        if ($state === '') {
            $state = 'unreviewed';
        }
        kingy_ali_product_graph_increment_count($counts, $state);
    }

    if (!$counts) {
        return array(
            'summary' => __('no example review states', 'kingy-ai-launch-intelligence'),
            'states' => '',
        );
    }

    $parts = array();
    foreach ($counts as $state => $count) {
        $parts[] = $state . ': ' . absint($count);
    }

    return array(
        'summary' => implode(', ', $parts),
        'states' => implode(',', array_keys($counts)),
    );
}

function kingy_ali_product_graph_requested_repair_planner_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_repair_planner_id', 120));
}

function kingy_ali_product_graph_find_repair_planner_row($rows, $planner_id) {
    $planner_id = kingy_ali_product_graph_sanitize_row_id($planner_id);
    if ($planner_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'planner_id') === $planner_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_evidence_pack_columns() {
    return array(
        'evidence_pack_id',
        'evidence_type',
        'related_opportunity_id',
        'related_planner_id',
        'planner_family',
        'opportunity_type',
        'source_node_id',
        'source_title',
        'source_type',
        'target_candidate',
        'target_title',
        'target_type',
        'available_source_urls',
        'existing_edge_context',
        'missing_evidence',
        'recommended_reviewer_question',
        'safe_next_action',
        'review_state',
    );
}

function kingy_ali_product_graph_evidence_pack_data() {
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $resolver = kingy_ali_product_graph_read_json('resolver', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $repair_planner = kingy_ali_product_graph_repair_planner_data();
    $opportunity_rows = isset($opportunities['rows']) && is_array($opportunities['rows']) ? $opportunities['rows'] : array();
    $planner_rows = isset($repair_planner['rows']) && is_array($repair_planner['rows']) ? $repair_planner['rows'] : array();
    $node_map = array();
    $edges_by_source = array();
    $edges_by_target = array();
    $resolver_by_key = array();
    $unresolved_by_key = array();
    $rows = array();
    $counts_by_type = array();
    $counts_by_planner_family = array();
    $counts_by_opportunity_type = array();
    $counts_by_review_state = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key !== '') {
            $node_map[$node_key] = $node;
        }
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $source = kingy_ali_product_graph_row_value($edge, 'source');
        $target = kingy_ali_product_graph_row_value($edge, 'target');
        if ($source !== '') {
            if (!isset($edges_by_source[$source])) {
                $edges_by_source[$source] = array();
            }
            $edges_by_source[$source][] = $edge;
        }
        if ($target !== '') {
            if (!isset($edges_by_target[$target])) {
                $edges_by_target[$target] = array();
            }
            $edges_by_target[$target][] = $edge;
        }
    }

    foreach ($resolver as $record) {
        if (!is_array($record)) {
            continue;
        }
        $keys = array(
            kingy_ali_product_graph_row_value($record, 'entity_key'),
            kingy_ali_product_graph_row_value($record, 'normalized_url'),
            kingy_ali_product_graph_row_value($record, 'input_url'),
        );
        if (isset($record['candidate_source_keys']) && is_array($record['candidate_source_keys'])) {
            foreach ($record['candidate_source_keys'] as $candidate_key) {
                $keys[] = is_scalar($candidate_key) ? (string) $candidate_key : '';
            }
        }
        foreach ($keys as $key) {
            $key = kingy_ali_product_graph_sanitize_row_id($key);
            if ($key === '') {
                continue;
            }
            if (!isset($resolver_by_key[$key])) {
                $resolver_by_key[$key] = array();
            }
            $resolver_by_key[$key][] = $record;
        }
    }

    foreach ($unresolved_queue as $record) {
        if (!is_array($record)) {
            continue;
        }
        $keys = array(
            kingy_ali_product_graph_row_value($record, 'normalized_url'),
            kingy_ali_product_graph_row_value($record, 'input_url'),
        );
        if (isset($record['candidate_source_keys']) && is_array($record['candidate_source_keys'])) {
            foreach ($record['candidate_source_keys'] as $candidate_key) {
                $keys[] = is_scalar($candidate_key) ? (string) $candidate_key : '';
            }
        }
        foreach ($keys as $key) {
            $key = kingy_ali_product_graph_sanitize_row_id($key);
            if ($key === '') {
                continue;
            }
            if (!isset($unresolved_by_key[$key])) {
                $unresolved_by_key[$key] = array();
            }
            $unresolved_by_key[$key][] = $record;
        }
    }

    foreach ($opportunity_rows as $opportunity) {
        if (!is_array($opportunity)) {
            continue;
        }
        $row = kingy_ali_product_graph_evidence_pack_opportunity_row(
            $opportunity,
            $node_map,
            $edges_by_source,
            $edges_by_target,
            $resolver_by_key,
            $unresolved_by_key
        );
        $rows[] = $row;
    }

    foreach ($planner_rows as $planner_row) {
        if (!is_array($planner_row)) {
            continue;
        }
        $rows[] = kingy_ali_product_graph_evidence_pack_planner_row($planner_row);
    }

    foreach ($rows as $row) {
        kingy_ali_product_graph_increment_count($counts_by_type, kingy_ali_product_graph_row_value($row, 'evidence_type'));
        kingy_ali_product_graph_increment_count($counts_by_planner_family, kingy_ali_product_graph_row_value($row, 'planner_family'));
        kingy_ali_product_graph_increment_count($counts_by_opportunity_type, kingy_ali_product_graph_row_value($row, 'opportunity_type'));
        kingy_ali_product_graph_increment_count($counts_by_review_state, kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed'));
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_evidence_pack',
        'row_count' => count($rows),
        'counts_by_type' => $counts_by_type,
        'counts_by_planner_family' => $counts_by_planner_family,
        'counts_by_opportunity_type' => $counts_by_opportunity_type,
        'counts_by_review_state' => $counts_by_review_state,
        'rows' => $rows,
    );
}

function kingy_ali_product_graph_evidence_pack_opportunity_row($opportunity, $node_map, $edges_by_source, $edges_by_target, $resolver_by_key, $unresolved_by_key) {
    $opportunity_id = kingy_ali_product_graph_row_value($opportunity, 'opportunity_id');
    $source_node_id = kingy_ali_product_graph_row_value($opportunity, 'source_node');
    $target_candidate = kingy_ali_product_graph_row_value($opportunity, 'target_candidate');
    $source_node = isset($node_map[$source_node_id]) && is_array($node_map[$source_node_id]) ? $node_map[$source_node_id] : array();
    $outgoing_edges = isset($edges_by_source[$source_node_id]) ? $edges_by_source[$source_node_id] : array();
    $incoming_edges = isset($edges_by_target[$source_node_id]) ? $edges_by_target[$source_node_id] : array();
    $target_edges = isset($edges_by_target[$target_candidate]) ? $edges_by_target[$target_candidate] : array();
    $resolver_records = array_merge(
        isset($resolver_by_key[$source_node_id]) ? $resolver_by_key[$source_node_id] : array(),
        isset($resolver_by_key[$target_candidate]) ? $resolver_by_key[$target_candidate] : array()
    );
    $unresolved_records = array_merge(
        isset($unresolved_by_key[$source_node_id]) ? $unresolved_by_key[$source_node_id] : array(),
        isset($unresolved_by_key[$target_candidate]) ? $unresolved_by_key[$target_candidate] : array()
    );
    $guidance = kingy_ali_product_graph_opportunity_review_guidance($opportunity);
    $question = isset($guidance['questions'][0]) ? $guidance['questions'][0] : __('What source evidence would make this relationship useful?', 'kingy-ai-launch-intelligence');
    $safe_action = isset($guidance['safe_next_actions'][0]) ? $guidance['safe_next_actions'][0] : __('Review source evidence without changing content from this screen.', 'kingy-ai-launch-intelligence');
    $urls = kingy_ali_product_graph_evidence_pack_source_urls($source_node, $opportunity, $resolver_records, $unresolved_records);
    $opportunity_type = kingy_ali_product_graph_row_value($opportunity, 'opportunity_type');

    return array(
        'evidence_pack_id' => 'evidence-pack:' . substr(md5('opportunity|' . $opportunity_id), 0, 16),
        'evidence_type' => 'opportunity_evidence',
        'related_opportunity_id' => $opportunity_id,
        'related_planner_id' => '',
        'planner_family' => kingy_ali_product_graph_evidence_pack_planner_family_for_opportunity($opportunity_type),
        'opportunity_type' => $opportunity_type,
        'source_node_id' => $source_node_id,
        'source_title' => kingy_ali_product_graph_row_value($opportunity, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($opportunity, 'source_type'),
        'target_candidate' => $target_candidate,
        'target_title' => kingy_ali_product_graph_row_value($opportunity, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($opportunity, 'target_type'),
        'available_source_urls' => implode(', ', $urls),
        'existing_edge_context' => sprintf(
            'outgoing:%d, incoming:%d, target_edges:%d, resolver:%d, unresolved:%d',
            count($outgoing_edges),
            count($incoming_edges),
            count($target_edges),
            count($resolver_records),
            count($unresolved_records)
        ),
        'missing_evidence' => kingy_ali_product_graph_evidence_pack_missing_evidence($opportunity_type),
        'recommended_reviewer_question' => sanitize_text_field((string) $question),
        'safe_next_action' => sanitize_text_field((string) $safe_action),
        'review_state' => kingy_ali_product_graph_row_value($opportunity, 'review_state', 'unreviewed'),
        'reviewer_notes' => kingy_ali_product_graph_row_value($opportunity, 'reviewer_notes'),
        'context_rows' => array(
            'source_node' => $source_node ? array($source_node) : array(),
            'outgoing_edges' => array_slice($outgoing_edges, 0, 10),
            'incoming_edges' => array_slice($incoming_edges, 0, 10),
            'target_edges' => array_slice($target_edges, 0, 10),
            'resolver_records' => array_slice($resolver_records, 0, 10),
            'unresolved_records' => array_slice($unresolved_records, 0, 10),
        ),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_evidence_pack_planner_row($planner_row) {
    $planner_id = kingy_ali_product_graph_row_value($planner_row, 'planner_id');
    $family = kingy_ali_product_graph_row_value($planner_row, 'issue_family');

    return array(
        'evidence_pack_id' => 'evidence-pack:' . substr(md5('planner|' . $planner_id), 0, 16),
        'evidence_type' => 'repair_planner_batch_evidence',
        'related_opportunity_id' => '',
        'related_planner_id' => $planner_id,
        'planner_family' => $family,
        'opportunity_type' => '',
        'source_node_id' => kingy_ali_product_graph_row_value($planner_row, 'example_row_ids'),
        'source_title' => ucwords(str_replace('_', ' ', $family)),
        'source_type' => 'repair_planner_batch',
        'target_candidate' => kingy_ali_product_graph_row_value($planner_row, 'blocked_by'),
        'target_title' => kingy_ali_product_graph_row_value($planner_row, 'blocked_by'),
        'target_type' => 'blocker',
        'available_source_urls' => kingy_ali_product_graph_evidence_pack_urls_from_rows(isset($planner_row['example_rows']) && is_array($planner_row['example_rows']) ? $planner_row['example_rows'] : array()),
        'existing_edge_context' => sprintf(
            'affected_rows:%d, example_rows:%d',
            absint(kingy_ali_product_graph_row_value($planner_row, 'affected_row_count')),
            count(isset($planner_row['example_rows']) && is_array($planner_row['example_rows']) ? $planner_row['example_rows'] : array())
        ),
        'missing_evidence' => kingy_ali_product_graph_row_value($planner_row, 'required_evidence'),
        'recommended_reviewer_question' => kingy_ali_product_graph_evidence_pack_planner_question($family),
        'safe_next_action' => kingy_ali_product_graph_row_value($planner_row, 'recommended_safe_next_action'),
        'review_state' => kingy_ali_product_graph_evidence_pack_first_review_state(kingy_ali_product_graph_row_value($planner_row, 'review_states')),
        'reviewer_notes' => kingy_ali_product_graph_row_value($planner_row, 'review_overlay_status_summary'),
        'context_rows' => array(
            'example_rows' => array_slice(isset($planner_row['example_rows']) && is_array($planner_row['example_rows']) ? $planner_row['example_rows'] : array(), 0, 10),
        ),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_evidence_pack_source_urls($source_node, $opportunity, $resolver_records, $unresolved_records) {
    $urls = array();
    foreach (array(
        kingy_ali_product_graph_row_value($source_node, 'url'),
        kingy_ali_product_graph_row_value($opportunity, 'target_candidate'),
    ) as $url) {
        if (preg_match('#^https?://#', $url)) {
            $urls[$url] = true;
        }
    }

    foreach (array_merge($resolver_records, $unresolved_records) as $record) {
        if (!is_array($record)) {
            continue;
        }
        foreach (array('url', 'normalized_url', 'input_url') as $field) {
            $url = kingy_ali_product_graph_row_value($record, $field);
            if (preg_match('#^https?://#', $url)) {
                $urls[$url] = true;
            }
        }
    }

    return array_slice(array_keys($urls), 0, 5);
}

function kingy_ali_product_graph_evidence_pack_urls_from_rows($rows) {
    $urls = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        foreach (array('url', 'source_url', 'target_url', 'normalized_url', 'input_url', 'target_candidate') as $field) {
            $url = kingy_ali_product_graph_row_value($row, $field);
            if (preg_match('#^https?://#', $url)) {
                $urls[$url] = true;
            }
        }
    }

    return implode(', ', array_slice(array_keys($urls), 0, 5));
}

function kingy_ali_product_graph_evidence_pack_planner_family_for_opportunity($opportunity_type) {
    if ($opportunity_type === 'page_node_resolver_candidate') {
        return 'unresolved_url_resolver_batch';
    }
    if ($opportunity_type === 'model_inventory_gap') {
        return 'model_inventory_unblock_batch';
    }
    if (strpos($opportunity_type, 'missing_') === 0) {
        return 'high_confidence_opportunity_batch';
    }

    return 'high_confidence_opportunity_batch';
}

function kingy_ali_product_graph_evidence_pack_missing_evidence($opportunity_type) {
    switch ($opportunity_type) {
        case 'missing_tool_company_relationship':
        case 'missing_launch_company_relationship':
            return __('Official company/source evidence tying the node to the company target.', 'kingy-ai-launch-intelligence');
        case 'missing_launch_tool_relationship':
        case 'missing_tool_latest_launch':
            return __('Source-backed launch/tool relationship evidence and current target identity.', 'kingy-ai-launch-intelligence');
        case 'related_video_opportunity':
            return __('Official demo, YouTube, or creator video URL evidence.', 'kingy-ai-launch-intelligence');
        case 'related_guide_opportunity':
        case 'related_calculator_opportunity':
        case 'sponsor_path_opportunity':
        case 'newsletter_distribution_opportunity':
            return __('Existing Kingy surface, resolver record, and source-backed relationship rationale.', 'kingy-ai-launch-intelligence');
        case 'page_node_resolver_candidate':
            return __('Canonical resolver mapping or typed page-node classification evidence.', 'kingy-ai-launch-intelligence');
        case 'model_inventory_gap':
            return __('Accepted model inventory source export and current field map.', 'kingy-ai-launch-intelligence');
        default:
            return __('Reviewer-confirmed source evidence for the proposed relationship.', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_product_graph_evidence_pack_planner_question($family) {
    switch ($family) {
        case 'missing_target_node_repairs':
            return __('What target node type or canonical URL should this missing target become?', 'kingy-ai-launch-intelligence');
        case 'orphan_node_review_batch':
            return __('Does this orphan node need a source-backed incoming or outgoing relationship?', 'kingy-ai-launch-intelligence');
        case 'no_outgoing_edge_enrichment_batch':
            return __('Which outgoing relationship would materially help navigation or evaluation?', 'kingy-ai-launch-intelligence');
        case 'unresolved_url_resolver_batch':
            return __('Should this URL become a typed node, a resolver mapping, or remain review-only?', 'kingy-ai-launch-intelligence');
        case 'model_inventory_unblock_batch':
            return __('What source should provide the first usable kingy_ai_model records?', 'kingy-ai-launch-intelligence');
        case 'high_confidence_opportunity_batch':
            return __('Which high-confidence candidate has enough evidence for future implementation planning?', 'kingy-ai-launch-intelligence');
        case 'reviewer_followup_batch':
            return __('What saved reviewer state needs source, refresh, or canonical follow-up?', 'kingy-ai-launch-intelligence');
        default:
            return __('What evidence would unblock this graph repair batch?', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_product_graph_evidence_pack_first_review_state($states) {
    $states = is_scalar($states) ? explode(',', (string) $states) : array();
    foreach ($states as $state) {
        $state = sanitize_key($state);
        if ($state !== '') {
            return $state;
        }
    }

    return 'unreviewed';
}

function kingy_ali_product_graph_requested_evidence_pack_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_evidence_pack_id', 120));
}

function kingy_ali_product_graph_find_evidence_pack_row($rows, $evidence_pack_id) {
    $evidence_pack_id = kingy_ali_product_graph_sanitize_row_id($evidence_pack_id);
    if ($evidence_pack_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'evidence_pack_id') === $evidence_pack_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_review_dashboard_data() {
    $health = kingy_ali_product_graph_health_data();
    $planner = kingy_ali_product_graph_repair_planner_data();
    $evidence = kingy_ali_product_graph_evidence_pack_data();
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $overlay = kingy_ali_product_graph_review_overlay_summary();
    $metrics = isset($health['metrics']) && is_array($health['metrics']) ? $health['metrics'] : array();
    $planner_rows = isset($planner['rows']) && is_array($planner['rows']) ? $planner['rows'] : array();

    $saved_reviews = isset($overlay['total_saved_review_records']) ? absint($overlay['total_saved_review_records']) : 0;
    $counts_by_state = isset($overlay['counts_by_state']) && is_array($overlay['counts_by_state']) ? $overlay['counts_by_state'] : array();
    $needs_followup = 0;
    foreach (array('needs_source', 'needs_refresh', 'needs_canonical_review') as $state) {
        $needs_followup += isset($counts_by_state[$state]) ? absint($counts_by_state[$state]) : 0;
    }

    $current_blockers = array();
    if (kingy_ali_product_graph_row_value($metrics, 'model_inventory_status') === 'model_inventory_gap') {
        $current_blockers['model_inventory_gap'] = __('No local kingy_ai_model records are loaded yet.', 'kingy-ai-launch-intelligence');
    }
    if (!empty($metrics['missing_target_nodes'])) {
        $current_blockers['missing_target_nodes'] = sprintf(
            __('%d edge targets are not present in the loaded node map.', 'kingy-ai-launch-intelligence'),
            absint($metrics['missing_target_nodes'])
        );
    }
    if (!empty($metrics['unresolved_queue_count'])) {
        $current_blockers['unresolved_queue'] = sprintf(
            __('%d internal URL records remain review-only in the unresolved queue.', 'kingy-ai-launch-intelligence'),
            absint($metrics['unresolved_queue_count'])
        );
    }
    if ($needs_followup > 0) {
        $current_blockers['review_overlay_followup'] = sprintf(
            __('%d saved review overlay records need source, refresh, or canonical follow-up.', 'kingy-ai-launch-intelligence'),
            absint($needs_followup)
        );
    }

    $recommended_actions = array(
        __('Review the model inventory blocker before planning model node relationships.', 'kingy-ai-launch-intelligence'),
        __('Review missing target-node repairs with resolver evidence before any relationship sync work.', 'kingy-ai-launch-intelligence'),
        __('Review high-confidence opportunities in the Evidence Pack and Opportunity Detail views.', 'kingy-ai-launch-intelligence'),
        __('Resolve saved needs_source, needs_refresh, and needs_canonical_review metadata in the review overlay.', 'kingy-ai-launch-intelligence'),
        __('Do not insert links, create pages, approve rows, or edit WordPress content from this dashboard.', 'kingy-ai-launch-intelligence'),
    );

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_review_dashboard',
        'graph_health_snapshot' => array(
            'total_nodes' => isset($metrics['total_nodes']) ? absint($metrics['total_nodes']) : 0,
            'total_edges' => isset($metrics['total_edges']) ? absint($metrics['total_edges']) : 0,
            'unresolved_queue_count' => isset($metrics['unresolved_queue_count']) ? absint($metrics['unresolved_queue_count']) : 0,
            'orphan_nodes' => isset($metrics['orphan_nodes']) ? absint($metrics['orphan_nodes']) : 0,
            'missing_target_nodes' => isset($metrics['missing_target_nodes']) ? absint($metrics['missing_target_nodes']) : 0,
            'model_inventory_status' => kingy_ali_product_graph_row_value($metrics, 'model_inventory_status', 'unknown'),
        ),
        'repair_priority_snapshot' => array(
            'repair_planner_batch_count' => isset($planner['row_count']) ? absint($planner['row_count']) : count($planner_rows),
            'counts_by_priority' => isset($planner['counts_by_priority']) && is_array($planner['counts_by_priority']) ? $planner['counts_by_priority'] : array(),
            'counts_by_family' => isset($planner['counts_by_family']) && is_array($planner['counts_by_family']) ? $planner['counts_by_family'] : array(),
        ),
        'opportunity_snapshot' => array(
            'opportunity_count' => isset($opportunities['row_count']) ? absint($opportunities['row_count']) : 0,
            'counts_by_priority' => isset($opportunities['counts_by_priority']) && is_array($opportunities['counts_by_priority']) ? $opportunities['counts_by_priority'] : array(),
            'counts_by_confidence' => isset($opportunities['counts_by_confidence']) && is_array($opportunities['counts_by_confidence']) ? $opportunities['counts_by_confidence'] : array(),
        ),
        'evidence_coverage_snapshot' => array(
            'evidence_pack_row_count' => isset($evidence['row_count']) ? absint($evidence['row_count']) : 0,
            'counts_by_type' => isset($evidence['counts_by_type']) && is_array($evidence['counts_by_type']) ? $evidence['counts_by_type'] : array(),
            'counts_by_planner_family' => isset($evidence['counts_by_planner_family']) && is_array($evidence['counts_by_planner_family']) ? $evidence['counts_by_planner_family'] : array(),
        ),
        'review_overlay_progress' => array(
            'saved_review_overlay_count' => $saved_reviews,
            'counts_by_review_state' => $counts_by_state,
            'needs_followup_count' => $needs_followup,
            'latest_reviewed_at_utc' => isset($overlay['latest_reviewed_at_utc']) ? $overlay['latest_reviewed_at_utc'] : '',
        ),
        'current_blockers' => $current_blockers,
        'recommended_next_reviewer_actions' => $recommended_actions,
        'links' => kingy_ali_product_graph_review_dashboard_links(),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_review_dashboard_links() {
    $tabs = array(
        'graph_health' => __('Graph Health', 'kingy-ai-launch-intelligence'),
        'repair_planner' => __('Repair Planner', 'kingy-ai-launch-intelligence'),
        'evidence_pack' => __('Evidence Pack', 'kingy-ai-launch-intelligence'),
        'opportunities' => __('Opportunities', 'kingy-ai-launch-intelligence'),
        'review_overlay' => __('Review Overlay', 'kingy-ai-launch-intelligence'),
    );
    $links = array();

    foreach ($tabs as $tab => $label) {
        $links[$tab] = array(
            'label' => $label,
            'url' => add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => $tab), admin_url('admin.php')),
        );
    }

    return $links;
}

function kingy_ali_product_graph_review_dashboard_csv_rows($dashboard) {
    $rows = array();
    foreach (array('graph_health_snapshot', 'repair_priority_snapshot', 'opportunity_snapshot', 'evidence_coverage_snapshot', 'review_overlay_progress', 'current_blockers') as $section) {
        $items = isset($dashboard[$section]) && is_array($dashboard[$section]) ? $dashboard[$section] : array();
        foreach ($items as $metric => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_metric => $sub_value) {
                    $rows[] = array($section, $metric . '.' . $sub_metric, kingy_ali_product_graph_display_value($sub_value));
                }
                continue;
            }
            $rows[] = array($section, $metric, kingy_ali_product_graph_display_value($value));
        }
    }

    if (isset($dashboard['recommended_next_reviewer_actions']) && is_array($dashboard['recommended_next_reviewer_actions'])) {
        foreach ($dashboard['recommended_next_reviewer_actions'] as $index => $action) {
            $rows[] = array('recommended_next_reviewer_actions', 'action_' . ($index + 1), kingy_ali_product_graph_display_value($action));
        }
    }

    return $rows;
}

function kingy_ali_product_graph_reviewer_progress_columns() {
    return array(
        'progress_id',
        'section',
        'metric',
        'value',
        'reviewed_count',
        'unreviewed_count',
        'reviewer',
        'review_state',
        'priority',
        'issue_family',
        'detail',
        'safe_next_action',
    );
}

function kingy_ali_product_graph_reviewer_progress_data() {
    $queue = kingy_ali_product_graph_work_queue_data();
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $evidence = kingy_ali_product_graph_evidence_pack_data();
    $overlay_rows = kingy_ali_product_graph_flatten_review_overlay();
    $model_inventory = kingy_ali_product_graph_read_json('model_inventory', array());

    $queue_rows = isset($queue['rows']) && is_array($queue['rows']) ? $queue['rows'] : array();
    $opportunity_rows = isset($opportunities['rows']) && is_array($opportunities['rows']) ? $opportunities['rows'] : array();
    $evidence_rows = isset($evidence['rows']) && is_array($evidence['rows']) ? $evidence['rows'] : array();
    $followup_states = array('needs_source', 'needs_refresh', 'needs_canonical_review');

    $queue_counts_by_state = array();
    $queue_counts_by_priority = array();
    $queue_counts_by_family = array();
    $queue_reviewed = 0;
    $queue_unreviewed = 0;
    $reviewed_evidence_ids = array();

    foreach ($queue_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $state = kingy_ali_product_graph_row_value($row, 'current_review_state', 'unreviewed');
        $priority = kingy_ali_product_graph_row_value($row, 'priority', 'unknown');
        $family = kingy_ali_product_graph_row_value($row, 'issue_family', 'unknown');
        kingy_ali_product_graph_increment_count($queue_counts_by_state, $state);
        kingy_ali_product_graph_increment_count($queue_counts_by_priority, $priority);
        kingy_ali_product_graph_increment_count($queue_counts_by_family, $family);
        if ($state !== 'unreviewed') {
            $queue_reviewed++;
            $evidence_id = kingy_ali_product_graph_row_value($row, 'evidence_pack_id');
            if ($evidence_id !== '') {
                $reviewed_evidence_ids[$evidence_id] = true;
            }
        } else {
            $queue_unreviewed++;
        }
    }

    $opportunity_counts_by_state = array();
    $opportunity_reviewed = 0;
    $opportunity_unreviewed = 0;
    $reviewed_opportunity_ids = array();
    foreach ($opportunity_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $state = kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed');
        kingy_ali_product_graph_increment_count($opportunity_counts_by_state, $state);
        if ($state !== 'unreviewed') {
            $opportunity_reviewed++;
            $opportunity_id = kingy_ali_product_graph_row_value($row, 'opportunity_id');
            if ($opportunity_id !== '') {
                $reviewed_opportunity_ids[$opportunity_id] = true;
            }
        } else {
            $opportunity_unreviewed++;
        }
    }

    $evidence_with_review_metadata = 0;
    foreach ($evidence_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $evidence_id = kingy_ali_product_graph_row_value($row, 'evidence_pack_id');
        $opportunity_id = kingy_ali_product_graph_row_value($row, 'related_opportunity_id');
        if (($evidence_id !== '' && isset($reviewed_evidence_ids[$evidence_id])) || ($opportunity_id !== '' && isset($reviewed_opportunity_ids[$opportunity_id]))) {
            $evidence_with_review_metadata++;
        }
    }
    $evidence_without_review_metadata = max(0, count($evidence_rows) - $evidence_with_review_metadata);

    $overlay_counts_by_reviewer = array();
    $overlay_counts_by_state = array();
    $followup_counts = array();
    foreach ($overlay_rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $reviewer = kingy_ali_product_graph_row_value($row, 'reviewer_display_name', kingy_ali_product_graph_row_value($row, 'reviewer_login', 'unknown_reviewer'));
        $state = kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed');
        kingy_ali_product_graph_increment_count($overlay_counts_by_reviewer, $reviewer !== '' ? $reviewer : 'unknown_reviewer');
        kingy_ali_product_graph_increment_count($overlay_counts_by_state, $state);
        if (in_array($state, $followup_states, true)) {
            kingy_ali_product_graph_increment_count($followup_counts, $state);
        }
    }

    $followup_remaining = array_sum(array_map('absint', $followup_counts));
    $model_inventory_status = 'unknown';
    if (isset($model_inventory[0]) && is_array($model_inventory[0])) {
        $model_inventory_status = kingy_ali_product_graph_row_value($model_inventory[0], 'status', 'unknown');
    }

    $rows = array();
    $add_row = function ($section, $metric, $value, $reviewed_count, $unreviewed_count, $reviewer, $review_state, $priority, $issue_family, $detail, $safe_next_action) use (&$rows) {
        $section = sanitize_key($section);
        $metric = sanitize_key($metric);
        $reviewer = sanitize_text_field((string) $reviewer);
        $review_state = sanitize_key($review_state);
        $priority = sanitize_key($priority);
        $issue_family = sanitize_key($issue_family);
        $hash = substr(md5(implode('|', array($section, $metric, $reviewer, $review_state, $priority, $issue_family, (string) $value, (string) $detail))), 0, 16);
        $rows[] = array(
            'progress_id' => 'reviewer-progress:' . $hash,
            'section' => $section,
            'metric' => $metric,
            'value' => is_scalar($value) ? sanitize_text_field((string) $value) : kingy_ali_product_graph_display_value($value),
            'reviewed_count' => absint($reviewed_count),
            'unreviewed_count' => absint($unreviewed_count),
            'reviewer' => $reviewer,
            'review_state' => $review_state,
            'priority' => $priority,
            'issue_family' => $issue_family,
            'detail' => sanitize_text_field((string) $detail),
            'safe_next_action' => sanitize_text_field((string) $safe_next_action),
            'insertable' => false,
            'write_capable' => false,
        );
    };

    $add_row('overall_progress', 'work_queue_reviewed_vs_unreviewed', count($queue_rows), $queue_reviewed, $queue_unreviewed, '', '', '', '', __('Work Queue rows with saved or derived review states.', 'kingy-ai-launch-intelligence'), __('Keep triaging reviewer metadata only; do not mutate content or graph artifacts.', 'kingy-ai-launch-intelligence'));
    $add_row('opportunity_progress', 'opportunities_reviewed_vs_unreviewed', count($opportunity_rows), $opportunity_reviewed, $opportunity_unreviewed, '', '', '', '', __('Opportunity rows whose source review context is already marked reviewed.', 'kingy-ai-launch-intelligence'), __('Use Opportunity Detail and Evidence Pack before future graph implementation planning.', 'kingy-ai-launch-intelligence'));
    $add_row('evidence_coverage_progress', 'evidence_rows_with_related_review_metadata', count($evidence_rows), $evidence_with_review_metadata, $evidence_without_review_metadata, '', '', '', '', __('Evidence rows linked to reviewed Work Queue or Opportunity context.', 'kingy-ai-launch-intelligence'), __('Review evidence before any future relationship or link recommendation work.', 'kingy-ai-launch-intelligence'));
    $add_row('current_blockers', 'model_inventory_status', $model_inventory_status, 0, 0, '', $model_inventory_status === 'model_inventory_gap' ? 'model_inventory_blocked' : '', 'high', 'model_inventory_blocker', __('Model inventory status from the current local graph review dataset.', 'kingy-ai-launch-intelligence'), __('Choose a source of truth for kingy_ai_model records before model graph work.', 'kingy-ai-launch-intelligence'));

    foreach ($queue_counts_by_priority as $priority => $count) {
        $add_row('work_queue_progress', 'priority_' . $priority, $count, 0, 0, '', '', $priority, '', __('Work Queue rows grouped by priority.', 'kingy-ai-launch-intelligence'), __('Start with high priority rows and preserve review-only workflow boundaries.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($queue_counts_by_family as $family => $count) {
        $add_row('work_queue_progress', 'family_' . $family, $count, 0, 0, '', '', '', $family, __('Work Queue rows grouped by issue family.', 'kingy-ai-launch-intelligence'), __('Review the family in Work Queue or Evidence Pack before planning implementation.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($queue_counts_by_state as $state => $count) {
        $reviewed_count = $state !== 'unreviewed' ? $count : 0;
        $unreviewed_count = $state === 'unreviewed' ? $count : 0;
        $add_row('work_queue_progress', 'state_' . $state, $count, $reviewed_count, $unreviewed_count, '', $state, '', '', __('Work Queue rows grouped by current review state.', 'kingy-ai-launch-intelligence'), __('Use review metadata to clarify source, refresh, or canonical follow-up.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($opportunity_counts_by_state as $state => $count) {
        $reviewed_count = $state !== 'unreviewed' ? $count : 0;
        $unreviewed_count = $state === 'unreviewed' ? $count : 0;
        $add_row('opportunity_progress', 'state_' . $state, $count, $reviewed_count, $unreviewed_count, '', $state, '', '', __('Opportunity rows grouped by review state.', 'kingy-ai-launch-intelligence'), __('Use the read-only Opportunity Detail view for source and target context.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($overlay_counts_by_reviewer as $reviewer => $count) {
        $add_row('reviewer_activity', 'reviewer_' . sanitize_key($reviewer), $count, $count, 0, $reviewer, '', '', '', __('Saved review overlay records grouped by reviewer.', 'kingy-ai-launch-intelligence'), __('Continue using overlay metadata only; no content mutation is implied.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($overlay_counts_by_state as $state => $count) {
        $add_row('reviewer_activity', 'overlay_state_' . $state, $count, $state !== 'unreviewed' ? $count : 0, $state === 'unreviewed' ? $count : 0, '', $state, '', '', __('Saved review overlay records grouped by state.', 'kingy-ai-launch-intelligence'), __('Resolve follow-up states with evidence notes before future implementation work.', 'kingy-ai-launch-intelligence'));
    }
    foreach ($followup_counts as $state => $count) {
        $add_row('remaining_followups', 'followup_' . $state, $count, $count, 0, '', $state, 'high', 'review_overlay_followup', __('Saved review metadata still needs source, refresh, or canonical follow-up.', 'kingy-ai-launch-intelligence'), __('Open Review Overlay or Work Queue and add evidence-oriented reviewer notes only.', 'kingy-ai-launch-intelligence'));
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_reviewer_progress',
        'overall_progress' => array(
            'work_queue_total' => count($queue_rows),
            'work_queue_reviewed' => $queue_reviewed,
            'work_queue_unreviewed' => $queue_unreviewed,
            'saved_review_overlay_records' => count($overlay_rows),
            'followup_records_remaining' => $followup_remaining,
        ),
        'work_queue_progress' => array(
            'counts_by_priority' => $queue_counts_by_priority,
            'counts_by_review_state' => $queue_counts_by_state,
            'counts_by_issue_family' => $queue_counts_by_family,
        ),
        'opportunity_progress' => array(
            'opportunity_total' => count($opportunity_rows),
            'opportunity_reviewed' => $opportunity_reviewed,
            'opportunity_unreviewed' => $opportunity_unreviewed,
            'counts_by_review_state' => $opportunity_counts_by_state,
        ),
        'evidence_coverage_progress' => array(
            'evidence_pack_rows' => count($evidence_rows),
            'evidence_rows_with_related_review_metadata' => $evidence_with_review_metadata,
            'evidence_rows_without_related_review_metadata' => $evidence_without_review_metadata,
        ),
        'reviewer_activity' => array(
            'counts_by_reviewer' => $overlay_counts_by_reviewer,
            'counts_by_state' => $overlay_counts_by_state,
        ),
        'remaining_followups' => array(
            'followup_records_remaining' => $followup_remaining,
            'counts_by_followup_state' => $followup_counts,
        ),
        'current_blockers' => array(
            'model_inventory_status' => $model_inventory_status,
            'model_inventory_blocked' => $model_inventory_status === 'model_inventory_gap' ? 'yes' : 'no',
        ),
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_work_queue_columns() {
    return array(
        'queue_id',
        'priority',
        'issue_family',
        'source_row_id',
        'source_title',
        'target_candidate',
        'evidence_pack_id',
        'recommended_reviewer_action',
        'current_review_state',
        'reviewer_notes',
        'reviewed_at_utc',
        'reviewer_display_name',
        'blocker',
        'destination_tab',
        'destination_url',
    );
}

function kingy_ali_product_graph_work_queue_data() {
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $model_inventory = kingy_ali_product_graph_read_json('model_inventory', array());
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $evidence = kingy_ali_product_graph_evidence_pack_data();
    $overlay_rows = kingy_ali_product_graph_flatten_review_overlay();
    $opportunity_rows = isset($opportunities['rows']) && is_array($opportunities['rows']) ? $opportunities['rows'] : array();
    $evidence_rows = isset($evidence['rows']) && is_array($evidence['rows']) ? $evidence['rows'] : array();
    $node_map = array();
    $source_keys = array();
    $target_keys = array();
    $evidence_by_opportunity = array();
    $evidence_by_planner_family = array();
    $evidence_by_source = array();
    $rows = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key !== '') {
            $node_map[$node_key] = $node;
        }
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $source = kingy_ali_product_graph_row_value($edge, 'source');
        $target = kingy_ali_product_graph_row_value($edge, 'target');
        if ($source !== '') {
            $source_keys[$source] = true;
        }
        if ($target !== '') {
            $target_keys[$target] = true;
        }
    }

    foreach ($evidence_rows as $evidence_row) {
        if (!is_array($evidence_row)) {
            continue;
        }
        $evidence_id = kingy_ali_product_graph_row_value($evidence_row, 'evidence_pack_id');
        if ($evidence_id === '') {
            continue;
        }
        $opportunity_id = kingy_ali_product_graph_row_value($evidence_row, 'related_opportunity_id');
        $planner_family = kingy_ali_product_graph_row_value($evidence_row, 'planner_family');
        $source_id = kingy_ali_product_graph_row_value($evidence_row, 'source_node_id');
        $target = kingy_ali_product_graph_row_value($evidence_row, 'target_candidate');
        if ($opportunity_id !== '' && !isset($evidence_by_opportunity[$opportunity_id])) {
            $evidence_by_opportunity[$opportunity_id] = $evidence_id;
        }
        if ($planner_family !== '' && !isset($evidence_by_planner_family[$planner_family])) {
            $evidence_by_planner_family[$planner_family] = $evidence_id;
        }
        foreach (array($source_id, $target) as $key) {
            if ($key !== '' && !isset($evidence_by_source[$key])) {
                $evidence_by_source[$key] = $evidence_id;
            }
        }
    }

    if (isset($model_inventory[0]) && is_array($model_inventory[0]) && kingy_ali_product_graph_row_value($model_inventory[0], 'status') === 'model_inventory_gap') {
        $rows[] = kingy_ali_product_graph_work_queue_row(
            'high',
            'model_inventory_blocker',
            'model-inventory:kingy_ai_model',
            __('kingy_ai_model inventory', 'kingy-ai-launch-intelligence'),
            'kingy_ai_model:*',
            isset($evidence_by_planner_family['model_inventory_unblock_batch']) ? $evidence_by_planner_family['model_inventory_unblock_batch'] : '',
            __('Choose and review the source of truth for kingy_ai_model records before model graph work.', 'kingy-ai-launch-intelligence'),
            kingy_ali_product_graph_row_value($model_inventory[0], 'review_state', 'model_inventory_blocked'),
            'model_inventory_source',
            'model_inventory',
            kingy_ali_product_graph_tab_url('model_inventory'),
            10
        );
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $target = kingy_ali_product_graph_row_value($edge, 'target');
        if ($target === '' || isset($node_map[$target])) {
            continue;
        }
        $row_id = kingy_ali_product_graph_row_value($edge, 'review_row_id', kingy_ali_product_graph_row_value($edge, 'edge_id'));
        $rows[] = kingy_ali_product_graph_work_queue_row(
            'high',
            'missing_target_node_repair',
            $row_id,
            kingy_ali_product_graph_row_value($edge, 'source_title', kingy_ali_product_graph_row_value($edge, 'source')),
            $target !== '' ? $target : kingy_ali_product_graph_row_value($edge, 'target_title'),
            isset($evidence_by_planner_family['missing_target_node_repairs']) ? $evidence_by_planner_family['missing_target_node_repairs'] : '',
            __('Review the missing target and decide whether it should become a typed node, URL node, taxonomy node, or stale relationship candidate.', 'kingy-ai-launch-intelligence'),
            kingy_ali_product_graph_row_value($edge, 'review_state', 'unreviewed'),
            'target_node_mapping',
            'graph_health',
            kingy_ali_product_graph_tab_url('graph_health', array('kingy_pg_health_issue_type' => 'missing_target_node')),
            20
        );
    }

    foreach ($overlay_rows as $overlay_row) {
        if (!is_array($overlay_row)) {
            continue;
        }
        $state = kingy_ali_product_graph_row_value($overlay_row, 'review_state');
        if (!in_array($state, array('needs_source', 'needs_refresh', 'needs_canonical_review'), true)) {
            continue;
        }
        $row_id = kingy_ali_product_graph_row_value($overlay_row, 'review_row_id');
        $rows[] = kingy_ali_product_graph_work_queue_row(
            'high',
            'review_overlay_followup',
            $row_id,
            kingy_ali_product_graph_row_value($overlay_row, 'source_artifact_id', $row_id),
            '',
            '',
            __('Resolve the saved reviewer follow-up state with source evidence, refresh context, or canonical review.', 'kingy-ai-launch-intelligence'),
            $state,
            'review_overlay_followup',
            'review_overlay',
            kingy_ali_product_graph_tab_url('review_overlay', array('kingy_pg_overlay_state' => $state, 'kingy_pg_overlay_search' => $row_id)),
            30
        );
    }

    foreach ($opportunity_rows as $opportunity) {
        if (!is_array($opportunity)) {
            continue;
        }
        $confidence = kingy_ali_product_graph_row_value($opportunity, 'confidence');
        $priority = kingy_ali_product_graph_row_value($opportunity, 'priority');
        if (!in_array($confidence, array('verified', 'url-backed'), true) && $priority !== 'high') {
            continue;
        }
        $opportunity_id = kingy_ali_product_graph_row_value($opportunity, 'opportunity_id');
        $rows[] = kingy_ali_product_graph_work_queue_row(
            $priority !== '' ? $priority : 'medium',
            'high_confidence_opportunity',
            $opportunity_id,
            kingy_ali_product_graph_row_value($opportunity, 'source_title', kingy_ali_product_graph_row_value($opportunity, 'source_node')),
            kingy_ali_product_graph_row_value($opportunity, 'target_candidate'),
            isset($evidence_by_opportunity[$opportunity_id]) ? $evidence_by_opportunity[$opportunity_id] : '',
            __('Review source, target, and evidence context before any future graph relationship implementation planning.', 'kingy-ai-launch-intelligence'),
            kingy_ali_product_graph_row_value($opportunity, 'review_state', 'unreviewed'),
            'human_review',
            'opportunities',
            kingy_ali_product_graph_opportunity_detail_url($opportunity_id),
            40
        );
    }

    foreach ($unresolved_queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row_id = kingy_ali_product_graph_row_value($row, 'review_row_id', kingy_ali_product_graph_row_value($row, 'normalized_url'));
        $url = kingy_ali_product_graph_row_value($row, 'normalized_url', kingy_ali_product_graph_row_value($row, 'input_url'));
        $rows[] = kingy_ali_product_graph_work_queue_row(
            'medium',
            'unresolved_url_resolver_candidate',
            $row_id,
            kingy_ali_product_graph_row_value($row, 'title', $url),
            $url,
            isset($evidence_by_source[$url]) ? $evidence_by_source[$url] : (isset($evidence_by_planner_family['unresolved_url_resolver_batch']) ? $evidence_by_planner_family['unresolved_url_resolver_batch'] : ''),
            __('Classify the unresolved internal URL as a typed node, resolver mapping, review-only reference, or stale candidate.', 'kingy-ai-launch-intelligence'),
            kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed'),
            'resolver_mapping',
            'unresolved_queue',
            kingy_ali_product_graph_tab_url('unresolved_queue', array('kingy_pg_filter' => $url)),
            50
        );
    }

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key === '') {
            continue;
        }
        if (!isset($source_keys[$node_key]) && !isset($target_keys[$node_key])) {
            $rows[] = kingy_ali_product_graph_work_queue_row(
                'medium',
                'orphan_node_review_candidate',
                kingy_ali_product_graph_row_value($node, 'review_row_id', $node_key),
                kingy_ali_product_graph_row_value($node, 'title', $node_key),
                '',
                isset($evidence_by_source[$node_key]) ? $evidence_by_source[$node_key] : (isset($evidence_by_planner_family['orphan_node_review_batch']) ? $evidence_by_planner_family['orphan_node_review_batch'] : ''),
                __('Review whether this orphan node needs an incoming relationship, outgoing relationship, or should remain standalone.', 'kingy-ai-launch-intelligence'),
                kingy_ali_product_graph_row_value($node, 'review_state', 'unreviewed'),
                'relationship_evidence',
                'graph_health',
                kingy_ali_product_graph_tab_url('graph_health', array('kingy_pg_health_issue_type' => 'orphan_node')),
                60
            );
        }
        if (!isset($source_keys[$node_key])) {
            $rows[] = kingy_ali_product_graph_work_queue_row(
                'low',
                'no_outgoing_edge_enrichment_candidate',
                kingy_ali_product_graph_row_value($node, 'review_row_id', $node_key),
                kingy_ali_product_graph_row_value($node, 'title', $node_key),
                '',
                isset($evidence_by_source[$node_key]) ? $evidence_by_source[$node_key] : (isset($evidence_by_planner_family['no_outgoing_edge_enrichment_batch']) ? $evidence_by_planner_family['no_outgoing_edge_enrichment_batch'] : ''),
                __('Review whether this node needs useful outgoing context for navigation, evaluation, or commercial routing.', 'kingy-ai-launch-intelligence'),
                kingy_ali_product_graph_row_value($node, 'review_state', 'unreviewed'),
                'outgoing_relationship_evidence',
                'graph_health',
                kingy_ali_product_graph_tab_url('graph_health', array('kingy_pg_health_issue_type' => 'no_useful_outgoing_edges')),
                70
            );
        }
    }

    usort($rows, 'kingy_ali_product_graph_sort_work_queue_rows');

    $counts_by_family = array();
    $counts_by_priority = array();
    $counts_by_blocker = array();
    foreach ($rows as $row) {
        kingy_ali_product_graph_increment_count($counts_by_family, kingy_ali_product_graph_row_value($row, 'issue_family'));
        kingy_ali_product_graph_increment_count($counts_by_priority, kingy_ali_product_graph_row_value($row, 'priority'));
        kingy_ali_product_graph_increment_count($counts_by_blocker, kingy_ali_product_graph_row_value($row, 'blocker'));
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_work_queue',
        'row_count' => count($rows),
        'counts_by_family' => $counts_by_family,
        'counts_by_priority' => $counts_by_priority,
        'counts_by_blocker' => $counts_by_blocker,
        'rows' => $rows,
    );
}

function kingy_ali_product_graph_work_queue_row($priority, $issue_family, $source_row_id, $source_title, $target_candidate, $evidence_pack_id, $action, $review_state, $blocker, $destination_tab, $destination_url, $sort_weight) {
    $issue_family = sanitize_key($issue_family);
    $source_row_id = kingy_ali_product_graph_sanitize_row_id($source_row_id);
    $target_candidate = kingy_ali_product_graph_sanitize_row_id($target_candidate);
    $destination_tab = sanitize_key($destination_tab);
    $queue_id = 'work-queue:' . substr(md5(implode('|', array($issue_family, $source_row_id, $target_candidate, $destination_tab))), 0, 16);
    $record = kingy_ali_product_graph_review_record('work_queue', $queue_id);
    $allowed_states = kingy_ali_product_graph_allowed_review_states();
    $record_state = isset($record['review_state']) && isset($allowed_states[$record['review_state']]) ? $record['review_state'] : '';
    $current_state = $record_state !== '' ? $record_state : sanitize_key($review_state !== '' ? $review_state : 'unreviewed');

    return array(
        'queue_id' => $queue_id,
        'priority' => sanitize_key($priority),
        'issue_family' => $issue_family,
        'source_row_id' => $source_row_id,
        'source_title' => sanitize_text_field((string) $source_title),
        'target_candidate' => $target_candidate,
        'evidence_pack_id' => kingy_ali_product_graph_sanitize_row_id($evidence_pack_id),
        'recommended_reviewer_action' => sanitize_text_field((string) $action),
        'current_review_state' => $current_state,
        'reviewer_notes' => isset($record['reviewer_notes']) ? kingy_ali_product_graph_sanitize_review_notes($record['reviewer_notes']) : '',
        'reviewed_at_utc' => isset($record['reviewed_at_utc']) ? sanitize_text_field($record['reviewed_at_utc']) : '',
        'reviewer_display_name' => isset($record['reviewer_display_name']) ? sanitize_text_field($record['reviewer_display_name']) : '',
        'blocker' => sanitize_key($blocker),
        'destination_tab' => $destination_tab,
        'destination_url' => esc_url_raw($destination_url),
        'sort_weight' => absint($sort_weight),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_sort_work_queue_rows($a, $b) {
    $a_weight = isset($a['sort_weight']) ? absint($a['sort_weight']) : 999;
    $b_weight = isset($b['sort_weight']) ? absint($b['sort_weight']) : 999;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2);
    $a_priority = isset($priority_order[$a['priority']]) ? $priority_order[$a['priority']] : 9;
    $b_priority = isset($priority_order[$b['priority']]) ? $priority_order[$b['priority']] : 9;
    if ($a_priority !== $b_priority) {
        return $a_priority - $b_priority;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'source_title'), kingy_ali_product_graph_row_value($b, 'source_title'));
}

function kingy_ali_product_graph_tab_url($tab, $extra_args = array()) {
    $args = array_merge(
        array(
            'page' => 'kingy-ali-product-graph',
            'kingy_pg_tab' => sanitize_key($tab),
        ),
        is_array($extra_args) ? $extra_args : array()
    );

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_requested_work_queue_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_work_queue_id', 120));
}

function kingy_ali_product_graph_find_work_queue_row($rows, $queue_id) {
    $queue_id = kingy_ali_product_graph_sanitize_row_id($queue_id);
    if ($queue_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'queue_id') === $queue_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_opportunity_columns() {
    return array(
        'opportunity_id',
        'opportunity_type',
        'priority',
        'source_node',
        'source_title',
        'source_type',
        'target_candidate',
        'target_title',
        'target_type',
        'reason',
        'confidence',
        'review_state',
        'reviewer_notes',
    );
}

function kingy_ali_product_graph_opportunities_data() {
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $model_inventory = kingy_ali_product_graph_read_json('model_inventory', array());

    $edge_sources_by_type = array();
    $edge_targets_by_type = array();
    $edge_pairs = array();
    $opportunities = array();
    $counts_by_type = array();
    $counts_by_priority = array();
    $counts_by_confidence = array();

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        $edge_type = kingy_ali_product_graph_row_value($edge, 'edge_type');
        $source = kingy_ali_product_graph_row_value($edge, 'source');
        $target = kingy_ali_product_graph_row_value($edge, 'target');

        if ($edge_type === '') {
            continue;
        }
        if ($source !== '') {
            if (!isset($edge_sources_by_type[$edge_type])) {
                $edge_sources_by_type[$edge_type] = array();
            }
            $edge_sources_by_type[$edge_type][$source] = true;
        }
        if ($target !== '') {
            if (!isset($edge_targets_by_type[$edge_type])) {
                $edge_targets_by_type[$edge_type] = array();
            }
            $edge_targets_by_type[$edge_type][$target] = true;
        }
        $edge_pairs[$source . '|' . $target . '|' . $edge_type] = true;
    }

    $add = function ($type, $priority, $source_node, $source_title, $source_type, $target_candidate, $target_title, $target_type, $reason, $confidence, $review_state = 'unreviewed', $reviewer_notes = '') use (&$opportunities, &$counts_by_type, &$counts_by_priority, &$counts_by_confidence) {
        $row = kingy_ali_product_graph_opportunity_row($type, $priority, $source_node, $source_title, $source_type, $target_candidate, $target_title, $target_type, $reason, $confidence, $review_state, $reviewer_notes);
        $opportunities[] = $row;
        kingy_ali_product_graph_increment_count($counts_by_type, $row['opportunity_type']);
        kingy_ali_product_graph_increment_count($counts_by_priority, $row['priority']);
        kingy_ali_product_graph_increment_count($counts_by_confidence, $row['confidence']);
    };

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }

        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        $entity_type = kingy_ali_product_graph_row_value($node, 'entity_type');
        $node_kind = kingy_ali_product_graph_row_value($node, 'node_kind');
        $title = kingy_ali_product_graph_row_value($node, 'title', $node_key);
        $review = kingy_ali_product_graph_opportunity_review_context('node', $node);

        if ($entity_type === 'ai_tool') {
            if (empty($edge_sources_by_type['tool_made_by_company'][$node_key])) {
                $add('missing_tool_company_relationship', 'high', $node_key, $title, 'ai_tool', 'ai_company:*', 'Company relationship needed', 'ai_company', 'Tool node has no tool_made_by_company edge in the current graph.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['tool_latest_launch'][$node_key])) {
                $add('missing_tool_latest_launch', 'medium', $node_key, $title, 'ai_tool', 'ai_launch:*', 'Latest launch relationship needed', 'ai_launch', 'Tool node has no tool_latest_launch edge in the current graph.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['related_video'][$node_key])) {
                $add('related_video_opportunity', 'medium', $node_key, $title, 'ai_tool', 'external_url:youtube_or_demo', 'Demo or YouTube signal needed', 'video', 'Tool node has no related_video/demo URL edge.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['related_guide'][$node_key])) {
                $add('related_guide_opportunity', 'low', $node_key, $title, 'ai_tool', 'page:guide', 'Guide relationship candidate', 'guide', 'Tool node has no related_guide edge for deeper editorial coverage.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['related_calculator'][$node_key])) {
                $add('related_calculator_opportunity', 'low', $node_key, $title, 'ai_tool', 'page:calculator', 'Calculator relationship candidate', 'calculator', 'Tool node has no related_calculator edge.', 'candidate', $review['state'], $review['notes']);
            }
        }

        if ($entity_type === 'ai_launch') {
            if (empty($edge_sources_by_type['launch_profiles_tool'][$node_key])) {
                $add('missing_launch_tool_relationship', 'high', $node_key, $title, 'ai_launch', 'ai_tool:*', 'Profiled tool relationship needed', 'ai_tool', 'Launch node has no launch_profiles_tool edge in the current graph.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['launch_by_company'][$node_key])) {
                $add('missing_launch_company_relationship', 'high', $node_key, $title, 'ai_launch', 'ai_company:*', 'Launch company relationship needed', 'ai_company', 'Launch node has no launch_by_company edge in the current graph.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['best_next_link'][$node_key])) {
                $add('internal_link_recommendation_candidate', 'medium', $node_key, $title, 'ai_launch', 'best_next_link:*', 'Best next link candidate', 'internal_url', 'Launch node has no best_next_link edge for future read-only internal-link recommendation review.', 'candidate', $review['state'], $review['notes']);
            }
            if (empty($edge_sources_by_type['newsletter_distribution'][$node_key])) {
                $add('newsletter_distribution_opportunity', 'low', $node_key, $title, 'ai_launch', 'newsletter:*', 'Newsletter/Radar relationship candidate', 'newsletter', 'Launch node has no newsletter_distribution edge in the current graph.', 'candidate', $review['state'], $review['notes']);
            }
        }

        if (in_array($node_kind, array('page', 'guide', 'course', 'calculator', 'newsletter_distribution', 'model_surface'), true) || $entity_type === 'page') {
            if (empty($edge_sources_by_type['sponsor_path'][$node_key]) && empty($edge_targets_by_type['sponsor_path'][$node_key])) {
                $add('sponsor_path_opportunity', 'low', $node_key, $title, $node_kind !== '' ? $node_kind : 'page', 'page:sponsor_path', 'Sponsor path candidate', 'sponsor_path', 'Page-like graph node has no sponsor_path relationship. Review only before modeling commercial paths.', 'candidate', $review['state'], $review['notes']);
            }
        }
    }

    foreach ($unresolved_queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $review = kingy_ali_product_graph_opportunity_review_context('unresolved_queue', $row);
        $url = kingy_ali_product_graph_row_value($row, 'normalized_url', kingy_ali_product_graph_row_value($row, 'input_url'));
        $source_keys = kingy_ali_product_graph_display_value(isset($row['candidate_source_keys']) ? $row['candidate_source_keys'] : array());
        $add('page_node_resolver_candidate', 'medium', $source_keys !== '' ? $source_keys : 'unresolved_source', kingy_ali_product_graph_row_value($row, 'title', $url), 'internal_url', $url, $url, 'page_or_url_node', 'Internal URL is unresolved in the local resolver and may need a typed page node or canonical resolver mapping. Review-only; do not infer broken status without live evidence.', 'url-backed', $review['state'], $review['notes']);
    }

    $model_inventory_status = '';
    if (isset($model_inventory[0]) && is_array($model_inventory[0])) {
        $model_inventory_status = kingy_ali_product_graph_row_value($model_inventory[0], 'status');
    }
    if ($model_inventory_status === 'model_inventory_gap') {
        $review = kingy_ali_product_graph_opportunity_review_context('model_inventory', isset($model_inventory[0]) ? $model_inventory[0] : array());
        $add('model_inventory_gap', 'high', 'kingy_ai_model', 'kingy_ai_model inventory', 'model_inventory', 'kingy_ai_model:*', 'Model node inventory needed', 'ai_model', 'The kingy_ai_model CPT is registered, but zero model records are loaded in the current graph review dataset.', 'verified', $review['state'], $review['notes']);
    }

    usort($opportunities, 'kingy_ali_product_graph_sort_opportunities');

    return array(
        'generated_at_utc' => gmdate('c'),
        'row_count' => count($opportunities),
        'counts_by_type' => $counts_by_type,
        'counts_by_priority' => $counts_by_priority,
        'counts_by_confidence' => $counts_by_confidence,
        'rows' => $opportunities,
    );
}

function kingy_ali_product_graph_opportunity_row($type, $priority, $source_node, $source_title, $source_type, $target_candidate, $target_title, $target_type, $reason, $confidence, $review_state, $reviewer_notes) {
    $source_node = kingy_ali_product_graph_sanitize_row_id($source_node);
    $target_candidate = kingy_ali_product_graph_sanitize_row_id($target_candidate);
    $type = sanitize_key($type);
    $hash = substr(md5(implode('|', array($type, $source_node, $target_candidate, (string) $reason))), 0, 16);

    return array(
        'opportunity_id' => 'opportunity:' . $hash,
        'opportunity_type' => $type,
        'priority' => sanitize_key($priority),
        'source_node' => $source_node,
        'source_title' => sanitize_text_field((string) $source_title),
        'source_type' => sanitize_key($source_type),
        'target_candidate' => $target_candidate,
        'target_title' => sanitize_text_field((string) $target_title),
        'target_type' => sanitize_key($target_type),
        'reason' => sanitize_text_field((string) $reason),
        'confidence' => sanitize_key($confidence),
        'review_state' => sanitize_key($review_state !== '' ? $review_state : 'unreviewed'),
        'reviewer_notes' => kingy_ali_product_graph_sanitize_review_notes($reviewer_notes),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_opportunity_review_context($row_type, $row) {
    $row_id = is_array($row) ? kingy_ali_product_graph_row_value($row, 'review_row_id') : '';
    $state = is_array($row) ? kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed') : 'unreviewed';
    $notes = is_array($row) ? kingy_ali_product_graph_row_value($row, 'reviewer_notes') : '';
    $record = $row_id !== '' ? kingy_ali_product_graph_review_record($row_type, $row_id) : array();

    if ($record) {
        $state = isset($record['review_state']) ? sanitize_key($record['review_state']) : $state;
        $notes = isset($record['reviewer_notes']) ? kingy_ali_product_graph_sanitize_review_notes($record['reviewer_notes']) : $notes;
    }

    return array(
        'state' => $state !== '' ? $state : 'unreviewed',
        'notes' => $notes,
    );
}

function kingy_ali_product_graph_link_recommendation_columns() {
    return array(
        'recommendation_id',
        'source_url',
        'source_title',
        'source_type',
        'target_url',
        'target_title',
        'target_type',
        'suggested_anchor_text',
        'reason',
        'confidence',
        'edge_type',
        'evidence_pack_id',
        'resolver_status',
        'blockers',
        'review_state',
        'reviewer_notes',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_recommendations_data() {
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $resolver = kingy_ali_product_graph_read_json('resolver', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $evidence = kingy_ali_product_graph_evidence_pack_data();

    $node_map = array();
    $resolver_by_key = array();
    $resolver_by_url = array();
    $unresolved_urls = array();
    $evidence_by_source_target = array();
    $seen_pairs = array();
    $rows = array();
    $counts_by_edge_type = array();
    $counts_by_confidence = array();
    $counts_by_source_type = array();
    $counts_by_target_type = array();
    $exclusion_counts = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key !== '') {
            $node_map[$node_key] = $node;
        }
    }

    foreach ($resolver as $record) {
        if (!is_array($record)) {
            continue;
        }
        $entity_key = kingy_ali_product_graph_row_value($record, 'entity_key');
        $url = kingy_ali_product_graph_normalize_recommendation_url(kingy_ali_product_graph_row_value($record, 'normalized_url', kingy_ali_product_graph_row_value($record, 'url')));
        if ($entity_key !== '') {
            $resolver_by_key[$entity_key] = $record;
        }
        if ($url !== '') {
            $resolver_by_url[$url] = $record;
        }
    }

    foreach ($unresolved_queue as $record) {
        if (!is_array($record)) {
            continue;
        }
        $url = kingy_ali_product_graph_normalize_recommendation_url(kingy_ali_product_graph_row_value($record, 'normalized_url', kingy_ali_product_graph_row_value($record, 'url')));
        if ($url !== '') {
            $unresolved_urls[$url] = true;
        }
    }

    $evidence_rows = isset($evidence['rows']) && is_array($evidence['rows']) ? $evidence['rows'] : array();
    foreach ($evidence_rows as $evidence_row) {
        if (!is_array($evidence_row)) {
            continue;
        }
        $source = kingy_ali_product_graph_row_value($evidence_row, 'source_node_id');
        $target = kingy_ali_product_graph_row_value($evidence_row, 'target_candidate');
        $evidence_id = kingy_ali_product_graph_row_value($evidence_row, 'evidence_pack_id');
        if ($source !== '' && $target !== '' && $evidence_id !== '') {
            $evidence_by_source_target[$source . '|' . $target] = $evidence_id;
        }
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }

        $recommendation = kingy_ali_product_graph_link_recommendation_from_edge(
            $edge,
            $node_map,
            $resolver_by_key,
            $resolver_by_url,
            $unresolved_urls,
            $evidence_by_source_target,
            $seen_pairs,
            $exclusion_counts
        );

        if (!$recommendation) {
            continue;
        }

        $rows[] = $recommendation;
        kingy_ali_product_graph_increment_count($counts_by_edge_type, kingy_ali_product_graph_row_value($recommendation, 'edge_type'));
        kingy_ali_product_graph_increment_count($counts_by_confidence, kingy_ali_product_graph_row_value($recommendation, 'confidence'));
        kingy_ali_product_graph_increment_count($counts_by_source_type, kingy_ali_product_graph_row_value($recommendation, 'source_type'));
        kingy_ali_product_graph_increment_count($counts_by_target_type, kingy_ali_product_graph_row_value($recommendation, 'target_type'));
    }

    usort($rows, 'kingy_ali_product_graph_sort_link_recommendations');

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_internal_link_recommendations',
        'row_count' => count($rows),
        'counts_by_edge_type' => $counts_by_edge_type,
        'counts_by_confidence' => $counts_by_confidence,
        'counts_by_source_type' => $counts_by_source_type,
        'counts_by_target_type' => $counts_by_target_type,
        'exclusion_counts' => $exclusion_counts,
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_recommendation_from_edge($edge, $node_map, $resolver_by_key, $resolver_by_url, $unresolved_urls, $evidence_by_source_target, &$seen_pairs, &$exclusion_counts) {
    $source_key = kingy_ali_product_graph_row_value($edge, 'source');
    $target_key = kingy_ali_product_graph_row_value($edge, 'target');
    $source_node = isset($node_map[$source_key]) && is_array($node_map[$source_key]) ? $node_map[$source_key] : array();
    $target_node = isset($node_map[$target_key]) && is_array($node_map[$target_key]) ? $node_map[$target_key] : array();
    $source_url = kingy_ali_product_graph_normalize_recommendation_url(kingy_ali_product_graph_row_value($edge, 'source_url', kingy_ali_product_graph_row_value($source_node, 'url')));
    $target_url = kingy_ali_product_graph_normalize_recommendation_url(kingy_ali_product_graph_row_value($edge, 'target_url', kingy_ali_product_graph_row_value($target_node, 'url')));

    if ($source_url === '') {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'missing_source_url');
        return array();
    }
    if ($target_url === '') {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'missing_target_url');
        return array();
    }
    if ($source_url === $target_url) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'same_source_target_url');
        return array();
    }
    if (kingy_ali_product_graph_recommendation_url_is_known_unsafe($source_url) || kingy_ali_product_graph_recommendation_url_is_known_unsafe($target_url)) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'known_unsafe_url');
        return array();
    }
    if (kingy_ali_product_graph_recommendation_node_is_noindex_or_uncertain($source_node) || kingy_ali_product_graph_recommendation_node_is_noindex_or_uncertain($target_node)) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'noindex_or_canonical_uncertainty');
        return array();
    }
    if (isset($unresolved_urls[$target_url])) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'unresolved_target_url');
        return array();
    }

    $pair_key = $source_url . '|' . $target_url;
    if (isset($seen_pairs[$pair_key])) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'duplicate_source_target_url_pair');
        return array();
    }
    $seen_pairs[$pair_key] = true;

    $target_resolver = kingy_ali_product_graph_recommendation_resolver_record($target_key, $target_url, $resolver_by_key, $resolver_by_url);
    $resolver_status = $target_resolver ? kingy_ali_product_graph_row_value($target_resolver, 'canonical_resolution_status', 'resolved') : 'not_in_resolver_but_url_present';
    if ($resolver_status !== 'not_in_resolver_but_url_present' && strpos($resolver_status, 'unresolved') !== false) {
        kingy_ali_product_graph_increment_count($exclusion_counts, 'unresolved_target_resolver_status');
        return array();
    }

    $edge_id = kingy_ali_product_graph_row_value($edge, 'edge_id');
    $edge_type = kingy_ali_product_graph_row_value($edge, 'edge_type');
    $evidence_id = isset($evidence_by_source_target[$source_key . '|' . $target_key]) ? $evidence_by_source_target[$source_key . '|' . $target_key] : '';
    $confidence = kingy_ali_product_graph_row_value($edge, 'confidence_class', kingy_ali_product_graph_row_value($edge, 'confidence', 'candidate'));
    $target_title = kingy_ali_product_graph_row_value($edge, 'target_title', kingy_ali_product_graph_row_value($target_node, 'title', $target_url));
    $target_type = kingy_ali_product_graph_row_value($edge, 'target_entity_type', kingy_ali_product_graph_row_value($target_node, 'entity_type', kingy_ali_product_graph_row_value($target_node, 'node_kind')));
    $source_type = kingy_ali_product_graph_row_value($edge, 'source_entity_type', kingy_ali_product_graph_row_value($source_node, 'entity_type', kingy_ali_product_graph_row_value($source_node, 'node_kind')));
    $recommendation_blocker_parts = array();
    $recommendation_context = array(
        'source_type' => $source_type,
        'target_type' => $target_type,
        'target_url' => $target_url,
        'edge_type' => $edge_type,
    );
    if (kingy_ali_product_graph_link_target_is_external($recommendation_context)) {
        $recommendation_blocker_parts[] = 'target_is_external_url';
    }
    if (kingy_ali_product_graph_recommendation_is_rendered_relationship_duplicate($recommendation_context)) {
        $recommendation_blocker_parts[] = 'target_already_linked_from_source';
    }
    $recommendation_blockers = $recommendation_blocker_parts ? implode(', ', array_values(array_unique($recommendation_blocker_parts))) : 'none';
    $recommendation_id = 'link-rec:' . substr(md5(implode('|', array($edge_id, $source_url, $target_url, $edge_type))), 0, 16);
    $review_record = kingy_ali_product_graph_review_record('link_recommendation', $recommendation_id);
    $review_state = isset($review_record['review_state']) ? sanitize_key($review_record['review_state']) : 'unreviewed';
    $reviewer_notes = isset($review_record['reviewer_notes']) ? kingy_ali_product_graph_sanitize_review_notes($review_record['reviewer_notes']) : '';

    return array(
        'review_row_id' => $recommendation_id,
        'recommendation_id' => $recommendation_id,
        'source_url' => $source_url,
        'source_title' => kingy_ali_product_graph_row_value($edge, 'source_title', kingy_ali_product_graph_row_value($source_node, 'title', $source_key)),
        'source_type' => $source_type,
        'target_url' => $target_url,
        'target_title' => $target_title,
        'target_type' => $target_type,
        'suggested_anchor_text' => kingy_ali_product_graph_suggested_anchor_text($target_title, $target_url),
        'reason' => kingy_ali_product_graph_link_recommendation_reason($edge_type, kingy_ali_product_graph_row_value($edge, 'reason')),
        'confidence' => sanitize_key($confidence),
        'edge_type' => sanitize_key($edge_type),
        'evidence_pack_id' => $evidence_id,
        'resolver_status' => sanitize_key($resolver_status),
        'blockers' => $recommendation_blockers,
        'review_state' => $review_state !== '' ? $review_state : 'unreviewed',
        'reviewer_notes' => $reviewer_notes,
        'source_node_id' => $source_key,
        'target_node_id' => $target_key,
        'edge_id' => $edge_id,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_recommendation_resolver_record($node_key, $url, $resolver_by_key, $resolver_by_url) {
    if ($node_key !== '' && isset($resolver_by_key[$node_key]) && is_array($resolver_by_key[$node_key])) {
        return $resolver_by_key[$node_key];
    }
    if ($url !== '' && isset($resolver_by_url[$url]) && is_array($resolver_by_url[$url])) {
        return $resolver_by_url[$url];
    }

    return array();
}

function kingy_ali_product_graph_normalize_recommendation_url($url) {
    $url = is_scalar($url) ? trim((string) $url) : '';
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url);
    if ($url === '') {
        return '';
    }

    return rtrim($url, '/');
}

function kingy_ali_product_graph_recommendation_node_is_noindex_or_uncertain($node) {
    if (!is_array($node) || !$node) {
        return false;
    }

    if (kingy_ali_product_graph_row_value($node, 'should_noindex') === '1' || kingy_ali_product_graph_row_value($node, 'should_noindex') === 'true') {
        return true;
    }

    $index_status = kingy_ali_product_graph_row_value($node, 'index_status');
    if ($index_status === 'noindex' || strpos($index_status, 'uncertain') !== false) {
        return true;
    }

    return false;
}

function kingy_ali_product_graph_recommendation_url_is_known_unsafe($url) {
    $path = parse_url($url, PHP_URL_PATH);
    $path = is_string($path) ? trailingslashit($path) : '';
    $known_unsafe_paths = array(
        '/ai-launches/today/',
        '/ai-launches/this-week/',
        '/ai-models/',
        '/compare-ai-models/',
    );

    return in_array($path, $known_unsafe_paths, true);
}

function kingy_ali_product_graph_suggested_anchor_text($target_title, $target_url) {
    $target_title = trim(wp_strip_all_tags((string) $target_title));
    if ($target_title === '') {
        $path = parse_url($target_url, PHP_URL_PATH);
        $target_title = $path ? trim(str_replace(array('/', '-'), array(' ', ' '), (string) $path)) : $target_url;
    }

    if (function_exists('mb_strlen') && mb_strlen($target_title) > 90) {
        return mb_substr($target_title, 0, 87) . '...';
    }

    return strlen($target_title) > 90 ? substr($target_title, 0, 87) . '...' : $target_title;
}

function kingy_ali_product_graph_link_recommendation_reason($edge_type, $edge_reason) {
    $edge_type = sanitize_key($edge_type);
    switch ($edge_type) {
        case 'best_next_link':
            return __('Graph edge marks this target as the best next internal destination for the source.', 'kingy-ai-launch-intelligence');
        case 'related_article':
        case 'related_guide':
        case 'related_course':
        case 'related_calculator':
        case 'related_video':
            return __('Graph edge connects the source to a related educational or media surface.', 'kingy-ai-launch-intelligence');
        case 'sponsor_path':
            return __('Graph edge connects the source to a sponsor or creator campaign path.', 'kingy-ai-launch-intelligence');
        case 'newsletter_distribution':
            return __('Graph edge connects the source to newsletter or Radar distribution context.', 'kingy-ai-launch-intelligence');
        case 'launch_profiles_tool':
        case 'tool_latest_launch':
        case 'launch_by_company':
        case 'tool_made_by_company':
            return __('Graph edge connects launch, tool, and company entities that may deserve contextual navigation.', 'kingy-ai-launch-intelligence');
        default:
            return $edge_reason !== '' ? sanitize_text_field((string) $edge_reason) : __('Graph relationship suggests this internal destination may be useful for reviewers.', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_product_graph_sort_link_recommendations($a, $b) {
    $confidence_order = array('verified' => 0, 'url-backed' => 1, 'editorial' => 2, 'derived' => 3, 'candidate' => 4);
    $a_confidence = kingy_ali_product_graph_row_value($a, 'confidence');
    $b_confidence = kingy_ali_product_graph_row_value($b, 'confidence');
    $a_weight = isset($confidence_order[$a_confidence]) ? $confidence_order[$a_confidence] : 9;
    $b_weight = isset($confidence_order[$b_confidence]) ? $confidence_order[$b_confidence] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $edge_compare = strcmp(kingy_ali_product_graph_row_value($a, 'edge_type'), kingy_ali_product_graph_row_value($b, 'edge_type'));
    if ($edge_compare !== 0) {
        return $edge_compare;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'source_title'), kingy_ali_product_graph_row_value($b, 'source_title'));
}

function kingy_ali_product_graph_link_readiness_columns() {
    return array(
        'readiness_row_id',
        'recommendation_id',
        'readiness_bucket',
        'source_url',
        'source_title',
        'source_type',
        'target_url',
        'target_title',
        'target_type',
        'suggested_anchor_text',
        'usable_anchor_text',
        'confidence',
        'edge_type',
        'evidence_pack_id',
        'resolver_status',
        'blockers',
        'target_is_internal',
        'source_content_readable',
        'target_not_already_linked',
        'exact_anchor_found',
        'alternate_anchor_found',
        'alternate_anchor_text',
        'relationship_evidence_clear',
        'eligible_for_review_sprint',
        'quality_bucket',
        'quality_reason',
        'review_state',
        'reviewer_notes',
        'safe_next_action',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_readiness_data() {
    $recommendations = kingy_ali_product_graph_link_recommendations_data();
    $recommendation_rows = isset($recommendations['rows']) && is_array($recommendations['rows']) ? $recommendations['rows'] : array();
    $allowed_states = kingy_ali_product_graph_allowed_review_states();
    $rows = array();
    $counts_by_bucket = array();
    $counts_by_confidence = array();
    $counts_by_edge_type = array();
    $counts_by_source_type = array();
    $counts_by_target_type = array();
    $counts_by_review_state = array();
    $reviewed_count = 0;
    $with_blockers = 0;
    $missing_evidence = 0;
    $url_backed_or_verified = 0;
    $non_insertable_non_write_capable = 0;
    $internal_target_count = 0;
    $external_target_count = 0;
    $already_linked_count = 0;
    $exact_anchor_found_count = 0;
    $alternate_anchor_found_count = 0;
    $eligible_for_review_sprint_count = 0;
    $counts_by_quality_bucket = array();

    foreach ($allowed_states as $state => $label) {
        $counts_by_review_state[$state] = 0;
    }

    foreach ($recommendation_rows as $recommendation) {
        if (!is_array($recommendation)) {
            continue;
        }

        $review_state = kingy_ali_product_graph_row_value($recommendation, 'review_state', 'unreviewed');
        $confidence = kingy_ali_product_graph_row_value($recommendation, 'confidence');
        $target_already_linked = kingy_ali_product_graph_recommendation_target_already_linked_from_source($recommendation);
        $quality = kingy_ali_product_graph_internal_link_quality_context($recommendation, $target_already_linked);
        $blockers = kingy_ali_product_graph_link_readiness_effective_blockers($recommendation, $target_already_linked, $quality);
        $bucket = kingy_ali_product_graph_link_readiness_bucket($recommendation, $blockers);
        $evidence_pack_id = kingy_ali_product_graph_row_value($recommendation, 'evidence_pack_id');
        $insertable = kingy_ali_product_graph_row_value($recommendation, 'insertable');
        $write_capable = kingy_ali_product_graph_row_value($recommendation, 'write_capable');

        if (kingy_ali_product_graph_row_value($quality, 'target_is_internal') === 'yes') {
            $internal_target_count++;
        } else {
            $external_target_count++;
        }
        if ($target_already_linked === 'yes') {
            $already_linked_count++;
        }
        if (kingy_ali_product_graph_row_value($quality, 'exact_anchor_found') === 'yes') {
            $exact_anchor_found_count++;
        }
        if (kingy_ali_product_graph_row_value($quality, 'alternate_anchor_found') === 'yes') {
            $alternate_anchor_found_count++;
        }
        if (kingy_ali_product_graph_row_value($quality, 'eligible_for_review_sprint') === 'yes') {
            $eligible_for_review_sprint_count++;
        }

        if ($review_state !== 'unreviewed') {
            $reviewed_count++;
        }
        if ($blockers !== '' && $blockers !== 'none') {
            $with_blockers++;
        }
        if ($evidence_pack_id === '') {
            $missing_evidence++;
        }
        if (in_array($confidence, array('verified', 'url-backed'), true)) {
            $url_backed_or_verified++;
        }
        if ($insertable === 'false' && $write_capable === 'false') {
            $non_insertable_non_write_capable++;
        }

        kingy_ali_product_graph_increment_count($counts_by_bucket, $bucket);
        kingy_ali_product_graph_increment_count($counts_by_confidence, $confidence);
        kingy_ali_product_graph_increment_count($counts_by_edge_type, kingy_ali_product_graph_row_value($recommendation, 'edge_type'));
        kingy_ali_product_graph_increment_count($counts_by_source_type, kingy_ali_product_graph_row_value($recommendation, 'source_type'));
        kingy_ali_product_graph_increment_count($counts_by_target_type, kingy_ali_product_graph_row_value($recommendation, 'target_type'));
        kingy_ali_product_graph_increment_count($counts_by_review_state, $review_state);
        kingy_ali_product_graph_increment_count($counts_by_quality_bucket, kingy_ali_product_graph_row_value($quality, 'quality_bucket'));

        $rows[] = array(
            'readiness_row_id' => 'link-ready:' . substr(md5(kingy_ali_product_graph_row_value($recommendation, 'recommendation_id')), 0, 16),
            'recommendation_id' => kingy_ali_product_graph_row_value($recommendation, 'recommendation_id'),
            'readiness_bucket' => $bucket,
            'source_url' => kingy_ali_product_graph_row_value($recommendation, 'source_url'),
            'source_title' => kingy_ali_product_graph_row_value($recommendation, 'source_title'),
            'source_type' => kingy_ali_product_graph_row_value($recommendation, 'source_type'),
            'target_url' => kingy_ali_product_graph_row_value($recommendation, 'target_url'),
            'target_title' => kingy_ali_product_graph_row_value($recommendation, 'target_title'),
            'target_type' => kingy_ali_product_graph_row_value($recommendation, 'target_type'),
            'suggested_anchor_text' => kingy_ali_product_graph_row_value($recommendation, 'suggested_anchor_text'),
            'usable_anchor_text' => kingy_ali_product_graph_row_value($quality, 'usable_anchor_text'),
            'confidence' => $confidence,
            'edge_type' => kingy_ali_product_graph_row_value($recommendation, 'edge_type'),
            'evidence_pack_id' => $evidence_pack_id,
            'resolver_status' => kingy_ali_product_graph_row_value($recommendation, 'resolver_status'),
            'blockers' => $blockers,
            'target_already_linked_from_source' => $target_already_linked,
            'target_is_internal' => kingy_ali_product_graph_row_value($quality, 'target_is_internal'),
            'source_content_readable' => kingy_ali_product_graph_row_value($quality, 'source_content_readable'),
            'target_not_already_linked' => kingy_ali_product_graph_row_value($quality, 'target_not_already_linked'),
            'exact_anchor_found' => kingy_ali_product_graph_row_value($quality, 'exact_anchor_found'),
            'alternate_anchor_found' => kingy_ali_product_graph_row_value($quality, 'alternate_anchor_found'),
            'alternate_anchor_text' => kingy_ali_product_graph_row_value($quality, 'alternate_anchor_text'),
            'relationship_evidence_clear' => kingy_ali_product_graph_row_value($quality, 'relationship_evidence_clear'),
            'eligible_for_review_sprint' => kingy_ali_product_graph_row_value($quality, 'eligible_for_review_sprint'),
            'quality_bucket' => kingy_ali_product_graph_row_value($quality, 'quality_bucket'),
            'quality_reason' => kingy_ali_product_graph_row_value($quality, 'quality_reason'),
            'review_state' => $review_state,
            'reviewer_notes' => kingy_ali_product_graph_row_value($recommendation, 'reviewer_notes'),
            'safe_next_action' => kingy_ali_product_graph_link_readiness_safe_next_action($bucket),
            'insertable' => $insertable,
            'write_capable' => $write_capable,
        );
    }

    usort($rows, 'kingy_ali_product_graph_sort_link_readiness_rows');

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_internal_link_readiness',
        'row_count' => count($rows),
        'total_recommendations' => count($recommendation_rows),
        'reviewed_count' => $reviewed_count,
        'unreviewed_count' => max(0, count($recommendation_rows) - $reviewed_count),
        'recommendations_with_blockers' => $with_blockers,
        'recommendations_missing_evidence_pack' => $missing_evidence,
        'url_backed_or_verified_recommendations' => $url_backed_or_verified,
        'non_insertable_non_write_capable_recommendations' => $non_insertable_non_write_capable,
        'internal_target_recommendations' => $internal_target_count,
        'external_target_recommendations' => $external_target_count,
        'already_linked_recommendations' => $already_linked_count,
        'exact_anchor_found_recommendations' => $exact_anchor_found_count,
        'alternate_anchor_found_recommendations' => $alternate_anchor_found_count,
        'eligible_for_review_sprint_recommendations' => $eligible_for_review_sprint_count,
        'counts_by_readiness_bucket' => $counts_by_bucket,
        'counts_by_quality_bucket' => $counts_by_quality_bucket,
        'counts_by_confidence' => $counts_by_confidence,
        'counts_by_edge_type' => $counts_by_edge_type,
        'counts_by_source_type' => $counts_by_source_type,
        'counts_by_target_type' => $counts_by_target_type,
        'counts_by_review_state' => $counts_by_review_state,
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_recommendation_url_is_internal($url) {
    $host = parse_url((string) $url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return false;
    }

    $host = strtolower($host);
    return $host === 'kingy.ai' || substr($host, -strlen('.kingy.ai')) === '.kingy.ai';
}

function kingy_ali_product_graph_link_target_is_external($row) {
    if (sanitize_key(kingy_ali_product_graph_row_value($row, 'target_type')) === 'external_url') {
        return true;
    }

    $target_url = kingy_ali_product_graph_row_value($row, 'target_url');
    return $target_url !== '' && !kingy_ali_product_graph_recommendation_url_is_internal($target_url);
}

function kingy_ali_product_graph_link_readiness_effective_blockers($recommendation, $target_already_linked, $quality = null) {
    $blockers = trim(kingy_ali_product_graph_row_value($recommendation, 'blockers'));
    $parts = array();
    $quality = is_array($quality) ? $quality : kingy_ali_product_graph_internal_link_quality_context($recommendation, $target_already_linked);

    if ($blockers !== '' && $blockers !== 'none') {
        foreach (explode(',', $blockers) as $blocker) {
            $blocker = sanitize_key(trim($blocker));
            if ($blocker !== '') {
                $parts[] = $blocker;
            }
        }
    }
    if ($target_already_linked === 'yes') {
        $parts[] = 'target_already_linked_from_source';
    }
    if (kingy_ali_product_graph_link_target_is_external($recommendation)) {
        $parts[] = 'target_is_external_url';
    }
    if (kingy_ali_product_graph_row_value($quality, 'target_is_internal') === 'yes') {
        if (kingy_ali_product_graph_row_value($quality, 'source_content_readable') !== 'yes') {
            $parts[] = 'source_context_unreadable';
        }
        if (kingy_ali_product_graph_row_value($quality, 'target_not_already_linked') === 'yes'
            && kingy_ali_product_graph_row_value($quality, 'exact_anchor_found') !== 'yes'
            && kingy_ali_product_graph_row_value($quality, 'alternate_anchor_found') !== 'yes') {
            $parts[] = 'anchor_text_not_found';
        }
        if (kingy_ali_product_graph_row_value($quality, 'relationship_evidence_clear') !== 'yes') {
            $parts[] = 'relationship_evidence_unclear';
        }
    }

    $parts = array_values(array_unique($parts));
    return $parts ? implode(', ', $parts) : 'none';
}

function kingy_ali_product_graph_internal_link_quality_context($row, $target_already_linked = null, $source_id = null, $content = null, $plain_content = null) {
    $source_url = kingy_ali_product_graph_row_value($row, 'source_url');
    $target_url = kingy_ali_product_graph_row_value($row, 'target_url');
    $confidence = kingy_ali_product_graph_row_value($row, 'confidence');
    $target_is_internal = kingy_ali_product_graph_link_target_is_external($row) ? 'no' : 'yes';
    $source_id = $source_id === null ? kingy_ali_product_graph_resolve_source_object_id($source_url) : absint($source_id);

    if ($content === null || $plain_content === null) {
        $post = $source_id > 0 ? get_post($source_id) : null;
        $content = $post instanceof WP_Post && is_string($post->post_content) ? $post->post_content : '';
        $plain_content = trim(wp_strip_all_tags($content));
    }

    $content_length = function_exists('mb_strlen') ? mb_strlen((string) $plain_content) : strlen((string) $plain_content);
    $source_content_readable = $source_id > 0 && $content_length > 0 ? 'yes' : 'no';

    if ($target_already_linked === null) {
        if ($content !== '' && kingy_ali_product_graph_source_context_content_has_url($content, $target_url)) {
            $target_already_linked = 'yes';
        } elseif (kingy_ali_product_graph_recommendation_is_rendered_relationship_duplicate($row)) {
            $target_already_linked = 'yes';
        } elseif ($source_id > 0) {
            $target_already_linked = 'no';
        } else {
            $target_already_linked = 'unknown';
        }
    }

    $anchor_text = kingy_ali_product_graph_row_value($row, 'suggested_anchor_text');
    $exact_anchor_text = $anchor_text !== '' && $plain_content !== '' ? kingy_ali_product_graph_link_dry_run_first_anchor_occurrence($plain_content, $anchor_text) : '';
    $exact_anchor_found = $exact_anchor_text !== '' ? 'yes' : 'no';
    $alternate_anchor_text = '';

    if ($exact_anchor_found !== 'yes' && $plain_content !== '') {
        $alternate_anchor_text = kingy_ali_product_graph_find_present_anchor_candidate(
            $plain_content,
            kingy_ali_product_graph_internal_link_anchor_candidates($row)
        );
    }

    $alternate_anchor_found = $alternate_anchor_text !== '' ? 'yes' : 'no';
    $usable_anchor_text = $exact_anchor_found === 'yes' ? $exact_anchor_text : $alternate_anchor_text;
    $relationship_evidence_clear = in_array($confidence, array('verified', 'url-backed'), true) || kingy_ali_product_graph_row_value($row, 'evidence_pack_id') !== '' ? 'yes' : 'no';
    $not_insertable_not_write_capable = kingy_ali_product_graph_row_value($row, 'insertable') === 'false' && kingy_ali_product_graph_row_value($row, 'write_capable') === 'false' ? 'yes' : 'no';
    $eligible_for_review_sprint = $target_is_internal === 'yes'
        && $source_content_readable === 'yes'
        && $target_already_linked === 'no'
        && $usable_anchor_text !== ''
        && $relationship_evidence_clear === 'yes'
        && kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed') === 'unreviewed'
        && $not_insertable_not_write_capable === 'yes'
        ? 'yes'
        : 'no';

    $quality_bucket = 'not_review_sprint_ready';
    $quality_reason = __('Requires reviewer inspection before sprint use.', 'kingy-ai-launch-intelligence');
    if ($target_is_internal !== 'yes') {
        $quality_bucket = 'blocked_external_target';
        $quality_reason = __('External target; keep as evidence/reference only.', 'kingy-ai-launch-intelligence');
    } elseif ($source_content_readable !== 'yes') {
        $quality_bucket = 'source_context_unreadable';
        $quality_reason = __('Source content is not readable from a WordPress object.', 'kingy-ai-launch-intelligence');
    } elseif ($target_already_linked === 'yes') {
        $quality_bucket = 'target_already_linked';
        $quality_reason = __('Target already appears linked from the source.', 'kingy-ai-launch-intelligence');
    } elseif ($usable_anchor_text === '') {
        $quality_bucket = 'anchor_missing';
        $quality_reason = __('No exact or safe alternate anchor appears in the source content.', 'kingy-ai-launch-intelligence');
    } elseif ($relationship_evidence_clear !== 'yes') {
        $quality_bucket = 'relationship_evidence_unclear';
        $quality_reason = __('Relationship needs stronger evidence before reviewer sprint triage.', 'kingy-ai-launch-intelligence');
    } elseif (kingy_ali_product_graph_row_value($row, 'review_state', 'unreviewed') !== 'unreviewed') {
        $quality_bucket = 'already_reviewed';
        $quality_reason = __('Reviewer metadata already exists for this recommendation.', 'kingy-ai-launch-intelligence');
    } elseif ($eligible_for_review_sprint === 'yes') {
        $quality_bucket = 'eligible_for_review_sprint';
        $quality_reason = __('Internal target, readable source, no duplicate link, and source-contained anchor are available for metadata-only review.', 'kingy-ai-launch-intelligence');
    }

    return array(
        'target_is_internal' => $target_is_internal,
        'source_content_readable' => $source_content_readable,
        'target_not_already_linked' => $target_already_linked === 'no' ? 'yes' : 'no',
        'exact_anchor_found' => $exact_anchor_found,
        'alternate_anchor_found' => $alternate_anchor_found,
        'alternate_anchor_text' => $alternate_anchor_text,
        'usable_anchor_text' => $usable_anchor_text,
        'relationship_evidence_clear' => $relationship_evidence_clear,
        'eligible_for_review_sprint' => $eligible_for_review_sprint,
        'not_insertable_not_write_capable' => $not_insertable_not_write_capable,
        'quality_bucket' => $quality_bucket,
        'quality_reason' => $quality_reason,
    );
}

function kingy_ali_product_graph_internal_link_anchor_candidates($row) {
    $candidates = array(
        kingy_ali_product_graph_row_value($row, 'target_title'),
        kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
        kingy_ali_product_graph_slug_phrase_from_url(kingy_ali_product_graph_row_value($row, 'target_url')),
    );

    $target_title = kingy_ali_product_graph_row_value($row, 'target_title');
    foreach (preg_split('/[:|–—-]/', $target_title) as $part) {
        $candidates[] = $part;
    }

    $normalized = array();
    foreach ($candidates as $candidate) {
        $candidate = kingy_ali_product_graph_normalize_anchor_phrase($candidate);
        if ($candidate !== '') {
            $normalized[] = $candidate;
        }
    }

    return array_values(array_unique($normalized));
}

function kingy_ali_product_graph_normalize_anchor_phrase($value) {
    $value = trim(wp_strip_all_tags((string) $value));
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/', ' ', str_replace(array('_', '-'), ' ', $value));
    $value = trim(is_string($value) ? $value : '');
    if ($value === '' || strlen($value) < 3 || preg_match('/^https?:\/\//i', $value)) {
        return '';
    }

    return sanitize_text_field($value);
}

function kingy_ali_product_graph_slug_phrase_from_url($url) {
    $path = parse_url((string) $url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '';
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    if (!$segments) {
        return '';
    }

    return kingy_ali_product_graph_normalize_anchor_phrase(end($segments));
}

function kingy_ali_product_graph_find_present_anchor_candidate($plain_content, $candidates) {
    $plain_content = (string) $plain_content;
    if ($plain_content === '' || !is_array($candidates)) {
        return '';
    }

    foreach ($candidates as $candidate) {
        $candidate = kingy_ali_product_graph_normalize_anchor_phrase($candidate);
        if ($candidate === '') {
            continue;
        }
        $found = kingy_ali_product_graph_link_dry_run_first_anchor_occurrence($plain_content, $candidate);
        if ($found !== '') {
            return $found;
        }
    }

    return '';
}

function kingy_ali_product_graph_link_blockers_contain($blockers, $needle) {
    $needle = sanitize_key((string) $needle);
    if ($needle === '') {
        return false;
    }

    foreach (explode(',', (string) $blockers) as $blocker) {
        if (sanitize_key(trim($blocker)) === $needle) {
            return true;
        }
    }

    return false;
}

function kingy_ali_product_graph_recommendation_target_already_linked_from_source($recommendation) {
    $source_url = kingy_ali_product_graph_row_value($recommendation, 'source_url');
    $target_url = kingy_ali_product_graph_row_value($recommendation, 'target_url');
    if ($source_url === '' || $target_url === '') {
        return 'unknown';
    }

    $source_id = kingy_ali_product_graph_resolve_source_object_id($source_url);
    $post = $source_id > 0 ? get_post($source_id) : null;
    if ($post instanceof WP_Post && is_string($post->post_content) && $post->post_content !== '') {
        if (kingy_ali_product_graph_source_context_content_has_url($post->post_content, $target_url)) {
            return 'yes';
        }
    }

    if (kingy_ali_product_graph_recommendation_is_rendered_relationship_duplicate($recommendation)) {
        return 'yes';
    }

    return $post instanceof WP_Post ? 'no' : 'unknown';
}

function kingy_ali_product_graph_recommendation_is_rendered_relationship_duplicate($recommendation) {
    $source_type = kingy_ali_product_graph_row_value($recommendation, 'source_type');
    $target_type = kingy_ali_product_graph_row_value($recommendation, 'target_type');
    $edge_type = kingy_ali_product_graph_row_value($recommendation, 'edge_type');

    if ($source_type === 'ai_launch'
        && $target_type === 'ai_tool'
        && $edge_type === 'launch_profiles_tool') {
        return true;
    }

    return $source_type === 'ai_tool'
        && $target_type === 'ai_launch'
        && $edge_type === 'tool_latest_launch';
}

function kingy_ali_product_graph_link_readiness_bucket($recommendation, $effective_blockers = null) {
    $review_state = kingy_ali_product_graph_row_value($recommendation, 'review_state', 'unreviewed');
    $confidence = kingy_ali_product_graph_row_value($recommendation, 'confidence');
    $blockers = $effective_blockers === null ? kingy_ali_product_graph_row_value($recommendation, 'blockers') : (string) $effective_blockers;
    $evidence_pack_id = kingy_ali_product_graph_row_value($recommendation, 'evidence_pack_id');

    if (kingy_ali_product_graph_link_blockers_contain($blockers, 'target_is_external_url')) {
        return 'blocked_external_target';
    }
    if ($review_state === 'rejected') {
        return 'rejected_or_not_useful';
    }
    if (in_array($review_state, array('needs_source', 'needs_refresh', 'needs_canonical_review'), true)) {
        return $review_state;
    }
    if ($blockers !== '' && $blockers !== 'none') {
        return 'blocked_missing_evidence';
    }
    if ($review_state === 'accepted') {
        return 'ready_for_editor_review';
    }
    if ($evidence_pack_id === '' && !in_array($confidence, array('verified', 'url-backed'), true)) {
        return 'blocked_missing_evidence';
    }

    return 'not_reviewed';
}

function kingy_ali_product_graph_link_readiness_safe_next_action($bucket) {
    switch ($bucket) {
        case 'ready_for_editor_review':
            return __('Keep as review-ready metadata only until a separate insertion workflow is approved.', 'kingy-ai-launch-intelligence');
        case 'needs_source':
            return __('Find source evidence before considering this recommendation for editor review.', 'kingy-ai-launch-intelligence');
        case 'needs_refresh':
            return __('Refresh the graph snapshot or source context before further review.', 'kingy-ai-launch-intelligence');
        case 'needs_canonical_review':
            return __('Confirm canonical/indexability context before considering any future link workflow.', 'kingy-ai-launch-intelligence');
        case 'rejected_or_not_useful':
            return __('Leave out of future insertion planning unless a reviewer changes the metadata.', 'kingy-ai-launch-intelligence');
        case 'blocked_external_target':
            return __('Keep as evidence/reference relationship; not an internal-link insertion candidate.', 'kingy-ai-launch-intelligence');
        case 'blocked_missing_evidence':
            return __('Attach or identify evidence before promoting this beyond read-only review.', 'kingy-ai-launch-intelligence');
        default:
            return __('Review the recommendation detail and record reviewer metadata only; do not insert links.', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_product_graph_sort_link_readiness_rows($a, $b) {
    $bucket_order = array(
        'ready_for_editor_review' => 0,
        'needs_source' => 1,
        'needs_refresh' => 2,
        'needs_canonical_review' => 3,
        'blocked_external_target' => 4,
        'blocked_missing_evidence' => 5,
        'not_reviewed' => 6,
        'rejected_or_not_useful' => 7,
    );
    $a_bucket = kingy_ali_product_graph_row_value($a, 'readiness_bucket');
    $b_bucket = kingy_ali_product_graph_row_value($b, 'readiness_bucket');
    $a_weight = isset($bucket_order[$a_bucket]) ? $bucket_order[$a_bucket] : 9;
    $b_weight = isset($bucket_order[$b_bucket]) ? $bucket_order[$b_bucket] : 9;

    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $confidence_compare = strcmp(kingy_ali_product_graph_row_value($a, 'confidence'), kingy_ali_product_graph_row_value($b, 'confidence'));
    if ($confidence_compare !== 0) {
        return $confidence_compare;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'source_title'), kingy_ali_product_graph_row_value($b, 'source_title'));
}

function kingy_ali_product_graph_link_plan_preview_columns() {
    return array(
        'preview_id',
        'recommendation_id',
        'source_url',
        'source_title',
        'source_type',
        'target_url',
        'target_title',
        'target_type',
        'suggested_anchor_text',
        'usable_anchor_text',
        'confidence',
        'readiness_bucket',
        'review_state',
        'required_pre_insertion_checks',
        'blockers',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_plan_preview_data() {
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $readiness_rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $rows = array();
    $accepted_count = 0;
    $ready_for_editor_review_count = 0;
    $verified_or_url_backed_count = 0;
    $source_target_present_count = 0;
    $blockers_none_count = 0;
    $non_insertable_non_write_capable_count = 0;
    $counts_by_confidence = array();
    $counts_by_source_type = array();
    $counts_by_target_type = array();
    $counts_by_readiness_bucket = array();
    $counts_by_review_state = array();

    foreach ($readiness_rows as $readiness_row) {
        if (!is_array($readiness_row)) {
            continue;
        }

        $review_state = kingy_ali_product_graph_row_value($readiness_row, 'review_state', 'unreviewed');
        $readiness_bucket = kingy_ali_product_graph_row_value($readiness_row, 'readiness_bucket');
        $confidence = kingy_ali_product_graph_row_value($readiness_row, 'confidence');
        $source_url = kingy_ali_product_graph_row_value($readiness_row, 'source_url');
        $target_url = kingy_ali_product_graph_row_value($readiness_row, 'target_url');
        $blockers = kingy_ali_product_graph_row_value($readiness_row, 'blockers');
        $insertable = kingy_ali_product_graph_row_value($readiness_row, 'insertable');
        $write_capable = kingy_ali_product_graph_row_value($readiness_row, 'write_capable');

        if ($review_state === 'accepted') {
            $accepted_count++;
        }
        if ($readiness_bucket === 'ready_for_editor_review') {
            $ready_for_editor_review_count++;
        }
        if (in_array($confidence, array('verified', 'url-backed'), true)) {
            $verified_or_url_backed_count++;
        }
        if ($source_url !== '' && $target_url !== '') {
            $source_target_present_count++;
        }
        if ($blockers === 'none') {
            $blockers_none_count++;
        }
        if ($insertable === 'false' && $write_capable === 'false') {
            $non_insertable_non_write_capable_count++;
        }

        kingy_ali_product_graph_increment_count($counts_by_confidence, $confidence);
        kingy_ali_product_graph_increment_count($counts_by_source_type, kingy_ali_product_graph_row_value($readiness_row, 'source_type'));
        kingy_ali_product_graph_increment_count($counts_by_target_type, kingy_ali_product_graph_row_value($readiness_row, 'target_type'));
        kingy_ali_product_graph_increment_count($counts_by_readiness_bucket, $readiness_bucket);
        kingy_ali_product_graph_increment_count($counts_by_review_state, $review_state);

        if (!kingy_ali_product_graph_link_plan_preview_row_is_eligible($readiness_row)) {
            continue;
        }

        $recommendation_id = kingy_ali_product_graph_row_value($readiness_row, 'recommendation_id');
        $rows[] = array(
            'preview_id' => 'link-plan-preview:' . substr(md5($recommendation_id), 0, 16),
            'recommendation_id' => $recommendation_id,
            'source_url' => $source_url,
            'source_title' => kingy_ali_product_graph_row_value($readiness_row, 'source_title'),
            'source_type' => kingy_ali_product_graph_row_value($readiness_row, 'source_type'),
            'target_url' => $target_url,
            'target_title' => kingy_ali_product_graph_row_value($readiness_row, 'target_title'),
            'target_type' => kingy_ali_product_graph_row_value($readiness_row, 'target_type'),
            'suggested_anchor_text' => kingy_ali_product_graph_row_value($readiness_row, 'suggested_anchor_text'),
            'usable_anchor_text' => kingy_ali_product_graph_row_value($readiness_row, 'usable_anchor_text', kingy_ali_product_graph_row_value($readiness_row, 'suggested_anchor_text')),
            'confidence' => $confidence,
            'readiness_bucket' => $readiness_bucket,
            'review_state' => $review_state,
            'required_pre_insertion_checks' => kingy_ali_product_graph_link_plan_preview_required_checks(),
            'blockers' => 'none',
            'insertable' => 'false',
            'write_capable' => 'false',
        );
    }

    usort($rows, 'kingy_ali_product_graph_sort_link_plan_preview_rows');

    $total = count($readiness_rows);
    $current_blockers = array(
        'accepted_recommendations' => $accepted_count,
        'not_accepted_recommendations' => max(0, $total - $accepted_count),
        'ready_for_editor_review' => $ready_for_editor_review_count,
        'not_ready_for_editor_review' => max(0, $total - $ready_for_editor_review_count),
        'verified_or_url_backed' => $verified_or_url_backed_count,
        'missing_verified_or_url_backed_confidence' => max(0, $total - $verified_or_url_backed_count),
        'source_and_target_url_present' => $source_target_present_count,
        'missing_source_or_target_url' => max(0, $total - $source_target_present_count),
        'blockers_none' => $blockers_none_count,
        'rows_with_blockers' => max(0, $total - $blockers_none_count),
        'non_insertable_non_write_capable' => $non_insertable_non_write_capable_count,
        'link_insertion_workflow' => __('not enabled', 'kingy-ai-launch-intelligence'),
        'separate_human_write_authorization' => __('missing', 'kingy-ai-launch-intelligence'),
    );

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_internal_link_plan_preview',
        'source' => 'derived_from_link_readiness_and_review_overlay',
        'total_recommendations' => $total,
        'plan_preview_eligible' => count($rows),
        'row_count' => count($rows),
        'accepted_recommendations' => $accepted_count,
        'ready_for_editor_review_recommendations' => $ready_for_editor_review_count,
        'verified_or_url_backed_recommendations' => $verified_or_url_backed_count,
        'source_target_present_recommendations' => $source_target_present_count,
        'blockers_none_recommendations' => $blockers_none_count,
        'non_insertable_non_write_capable_recommendations' => $non_insertable_non_write_capable_count,
        'counts_by_confidence' => $counts_by_confidence,
        'counts_by_source_type' => $counts_by_source_type,
        'counts_by_target_type' => $counts_by_target_type,
        'counts_by_readiness_bucket' => $counts_by_readiness_bucket,
        'counts_by_review_state' => $counts_by_review_state,
        'filter_options' => array(
            'confidence' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_confidence),
            'source_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_source_type),
            'target_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_target_type),
            'readiness_bucket' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_readiness_bucket),
            'review_state' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_review_state),
        ),
        'eligibility_rules' => kingy_ali_product_graph_link_plan_preview_eligibility_rules(),
        'current_blockers' => $current_blockers,
        'required_human_review_before_future_write' => array(
            __('Reviewer must mark a recommendation accepted in the review overlay.', 'kingy-ai-launch-intelligence'),
            __('Readiness bucket must be ready_for_editor_review.', 'kingy-ai-launch-intelligence'),
            __('Confidence must be verified or URL-backed.', 'kingy-ai-launch-intelligence'),
            __('Source and target URLs must be present and safe.', 'kingy-ai-launch-intelligence'),
            __('A separate future write workflow, backup, duplicate-link scan, and human authorization would still be required.', 'kingy-ai-launch-intelligence'),
        ),
        'safe_next_actions' => array(
            __('Continue reviewer evidence review in Link Recommendations and Evidence Pack.', 'kingy-ai-launch-intelligence'),
            __('Record reviewer metadata only; do not insert links from this screen.', 'kingy-ai-launch-intelligence'),
            __('Revisit this preview only after accepted recommendations exist.', 'kingy-ai-launch-intelligence'),
            __('Keep graph source artifacts immutable until a separately approved workflow exists.', 'kingy-ai-launch-intelligence'),
        ),
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_plan_preview_row_is_eligible($row) {
    if (!is_array($row)) {
        return false;
    }

    return kingy_ali_product_graph_row_value($row, 'review_state') === 'accepted'
        && kingy_ali_product_graph_row_value($row, 'readiness_bucket') === 'ready_for_editor_review'
        && in_array(kingy_ali_product_graph_row_value($row, 'confidence'), array('verified', 'url-backed'), true)
        && kingy_ali_product_graph_row_value($row, 'blockers') === 'none'
        && !kingy_ali_product_graph_link_target_is_external($row)
        && kingy_ali_product_graph_row_value($row, 'source_content_readable') === 'yes'
        && kingy_ali_product_graph_row_value($row, 'target_not_already_linked') === 'yes'
        && kingy_ali_product_graph_row_value($row, 'relationship_evidence_clear') === 'yes'
        && kingy_ali_product_graph_row_value($row, 'usable_anchor_text') !== ''
        && kingy_ali_product_graph_row_value($row, 'source_url') !== ''
        && kingy_ali_product_graph_row_value($row, 'target_url') !== ''
        && kingy_ali_product_graph_row_value($row, 'insertable') === 'false'
        && kingy_ali_product_graph_row_value($row, 'write_capable') === 'false';
}

function kingy_ali_product_graph_link_plan_preview_required_checks() {
    return __('Human editor review; source and target HTTP/status check; canonical/indexability check; existing-link duplicate scan; anchor-context review; content backup; separate write authorization.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_product_graph_link_plan_preview_eligibility_rules() {
    return array(
        'review_state' => 'accepted',
        'readiness_bucket' => 'ready_for_editor_review',
        'confidence' => 'verified or url-backed',
        'blockers' => 'none',
        'target_already_linked_from_source' => 'no',
        'target_is_external_url' => 'no',
        'usable_anchor_text' => 'present in source content',
        'source_url' => 'present',
        'target_url' => 'present',
        'insertable' => 'false',
        'write_capable' => 'false',
    );
}

function kingy_ali_product_graph_link_plan_preview_sorted_keys($counts) {
    $keys = is_array($counts) ? array_keys($counts) : array();
    $keys = array_values(array_filter($keys, function ($key) {
        return $key !== '' && $key !== '(blank)';
    }));
    sort($keys);

    return $keys;
}

function kingy_ali_product_graph_sort_link_plan_preview_rows($a, $b) {
    $confidence_order = array('verified' => 0, 'url-backed' => 1);
    $a_confidence = kingy_ali_product_graph_row_value($a, 'confidence');
    $b_confidence = kingy_ali_product_graph_row_value($b, 'confidence');
    $a_weight = isset($confidence_order[$a_confidence]) ? $confidence_order[$a_confidence] : 9;
    $b_weight = isset($confidence_order[$b_confidence]) ? $confidence_order[$b_confidence] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'source_title'), kingy_ali_product_graph_row_value($b, 'source_title'));
}

function kingy_ali_product_graph_link_dry_run_columns() {
    return array(
        'dry_run_id',
        'recommendation_id',
        'source_url',
        'source_title',
        'source_type',
        'source_wp_object_id',
        'source_wp_object_type',
        'target_url',
        'target_title',
        'target_type',
        'suggested_anchor_text',
        'confidence',
        'reviewer_note',
        'source_context_status',
        'anchor_occurrence_count',
        'exact_proposed_anchor_occurrence',
        'before_snippet',
        'after_snippet_with_proposed_link_markup',
        'duplicate_link_check',
        'canonical_indexability_caution',
        'dry_run_status',
        'exclusion_reason',
        'dry_run_only',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_dry_run_data($limit = 10) {
    $preview = kingy_ali_product_graph_link_plan_preview_data();
    $preview_rows = isset($preview['rows']) && is_array($preview['rows']) ? $preview['rows'] : array();
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $readiness_rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $source_context = kingy_ali_product_graph_source_context_audit_data();
    $source_rows = isset($source_context['rows']) && is_array($source_context['rows']) ? $source_context['rows'] : array();

    $readiness_by_recommendation = array();
    foreach ($readiness_rows as $readiness_row) {
        if (!is_array($readiness_row)) {
            continue;
        }
        $recommendation_id = kingy_ali_product_graph_row_value($readiness_row, 'recommendation_id');
        if ($recommendation_id !== '') {
            $readiness_by_recommendation[$recommendation_id] = $readiness_row;
        }
    }

    $source_by_recommendation = array();
    foreach ($source_rows as $source_row) {
        if (!is_array($source_row)) {
            continue;
        }
        $recommendation_id = kingy_ali_product_graph_row_value($source_row, 'recommendation_id');
        if ($recommendation_id !== '') {
            $source_by_recommendation[$recommendation_id] = $source_row;
        }
    }

    $rows = array();
    $excluded_rows = array();
    $evaluated_count = 0;
    $limit = max(1, absint($limit));

    foreach ($preview_rows as $preview_row) {
        if (!is_array($preview_row) || $evaluated_count >= $limit) {
            continue;
        }

        $recommendation_id = kingy_ali_product_graph_row_value($preview_row, 'recommendation_id');
        $readiness_row = isset($readiness_by_recommendation[$recommendation_id]) ? $readiness_by_recommendation[$recommendation_id] : array();
        $source_row = isset($source_by_recommendation[$recommendation_id]) ? $source_by_recommendation[$recommendation_id] : array();
        $dry_run_row = kingy_ali_product_graph_link_dry_run_row($preview_row, $readiness_row, $source_row);
        $evaluated_count++;

        if (kingy_ali_product_graph_row_value($dry_run_row, 'dry_run_status') === 'included') {
            $rows[] = $dry_run_row;
        } else {
            $excluded_rows[] = $dry_run_row;
        }
    }

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'no_write_product_graph_internal_link_insertion_dry_run_package',
        'source' => 'derived_from_link_plan_preview_source_context_audit_and_review_overlay',
        'evaluated_recommendations' => $evaluated_count,
        'dry_run_package_rows_created' => count($rows),
        'excluded_recommendations' => count($excluded_rows),
        'eligible_recommendations_available' => isset($preview['plan_preview_eligible']) ? absint($preview['plan_preview_eligible']) : count($preview_rows),
        'dry_run_limit' => $limit,
        'rows' => $rows,
        'excluded_rows' => $excluded_rows,
        'guardrails' => array(
            'dry_run_only' => true,
            'insertable' => false,
            'write_capable' => false,
            'content_writes' => false,
            'link_insertion' => false,
            'insertion_jobs' => false,
            'graph_artifact_mutation' => false,
        ),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_dry_run_row($preview_row, $readiness_row, $source_row) {
    $recommendation_id = kingy_ali_product_graph_row_value($preview_row, 'recommendation_id');
    $source_url = kingy_ali_product_graph_row_value($preview_row, 'source_url');
    $target_url = kingy_ali_product_graph_row_value($preview_row, 'target_url');
    $anchor_text = kingy_ali_product_graph_row_value($preview_row, 'usable_anchor_text', kingy_ali_product_graph_row_value($preview_row, 'suggested_anchor_text'));
    $source_id = absint(kingy_ali_product_graph_row_value($source_row, 'source_wp_object_id'));
    $post = $source_id > 0 ? get_post($source_id) : null;
    $post_type = $post instanceof WP_Post ? $post->post_type : kingy_ali_product_graph_row_value($source_row, 'source_wp_object_type');
    $content = $post instanceof WP_Post && is_string($post->post_content) ? $post->post_content : '';
    $plain_content = trim(wp_strip_all_tags($content));
    $target_already_linked = $content !== '' && kingy_ali_product_graph_source_context_content_has_url($content, $target_url) ? 'yes' : kingy_ali_product_graph_row_value($source_row, 'target_already_linked_from_source');
    $anchor_occurrence_count = kingy_ali_product_graph_link_dry_run_anchor_occurrence_count($plain_content, $anchor_text);
    $exact_occurrence = $anchor_occurrence_count > 0 ? kingy_ali_product_graph_link_dry_run_first_anchor_occurrence($plain_content, $anchor_text) : '';
    $before_snippet = $anchor_occurrence_count > 0 ? kingy_ali_product_graph_source_context_excerpt($plain_content, $anchor_text) : '';
    $after_snippet = $before_snippet !== '' ? kingy_ali_product_graph_link_dry_run_markup_snippet($before_snippet, $anchor_text, $target_url) : '';
    $confidence = kingy_ali_product_graph_row_value($preview_row, 'confidence');
    $review_state = kingy_ali_product_graph_row_value($preview_row, 'review_state');
    $readiness_bucket = kingy_ali_product_graph_row_value($preview_row, 'readiness_bucket');
    $resolver_status = kingy_ali_product_graph_row_value($readiness_row, 'resolver_status');
    $exclusion_reasons = array();

    if (!$source_row) {
        $exclusion_reasons[] = 'source_context_missing';
    }
    if ($source_id <= 0 || !($post instanceof WP_Post)) {
        $exclusion_reasons[] = 'source_content_cannot_be_read';
    }
    if ($target_url === '') {
        $exclusion_reasons[] = 'target_url_missing';
    }
    if ($anchor_occurrence_count <= 0) {
        $exclusion_reasons[] = 'anchor_text_no_longer_appears';
    }
    if ($target_already_linked === 'yes') {
        $exclusion_reasons[] = 'target_already_linked_from_source';
    }
    if (kingy_ali_product_graph_link_target_is_external($preview_row)) {
        $exclusion_reasons[] = 'target_is_external_url';
    }
    if ($review_state !== 'accepted' || $readiness_bucket !== 'ready_for_editor_review' || !in_array($confidence, array('verified', 'url-backed'), true)) {
        $exclusion_reasons[] = 'relationship_not_clear_for_dry_run';
    }
    if ($resolver_status !== '' && strpos($resolver_status, 'unresolved') !== false) {
        $exclusion_reasons[] = 'canonical_or_resolver_uncertainty';
    }

    $canonical_caution = $resolver_status !== ''
        ? 'resolver_status=' . $resolver_status . '; no live HTTP/canonical fetch performed'
        : 'no live HTTP/canonical fetch performed; current graph/readiness data only';
    $dry_run_status = $exclusion_reasons ? 'excluded' : 'included';

    return array(
        'dry_run_id' => 'link-dry-run:' . substr(md5($recommendation_id . '|' . $source_url . '|' . $target_url), 0, 16),
        'recommendation_id' => $recommendation_id,
        'source_url' => $source_url,
        'source_title' => kingy_ali_product_graph_row_value($preview_row, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($preview_row, 'source_type'),
        'source_wp_object_id' => $source_id > 0 ? (string) $source_id : '',
        'source_wp_object_type' => $post_type,
        'target_url' => $target_url,
        'target_title' => kingy_ali_product_graph_row_value($preview_row, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($preview_row, 'target_type'),
        'suggested_anchor_text' => $anchor_text,
        'confidence' => $confidence,
        'reviewer_note' => kingy_ali_product_graph_row_value($readiness_row, 'reviewer_notes'),
        'source_context_status' => kingy_ali_product_graph_row_value($source_row, 'audit_status'),
        'anchor_occurrence_count' => (string) $anchor_occurrence_count,
        'exact_proposed_anchor_occurrence' => $exact_occurrence,
        'before_snippet' => $before_snippet,
        'after_snippet_with_proposed_link_markup' => $after_snippet,
        'duplicate_link_check' => $target_already_linked === 'yes' ? 'target_already_linked' : 'no_existing_target_link_detected',
        'canonical_indexability_caution' => $canonical_caution,
        'dry_run_status' => $dry_run_status,
        'exclusion_reason' => implode(', ', array_values(array_unique($exclusion_reasons))),
        'dry_run_only' => 'true',
        'insertable' => 'false',
        'write_capable' => 'false',
    );
}

function kingy_ali_product_graph_link_dry_run_anchor_occurrence_count($plain_content, $anchor_text) {
    $plain_content = (string) $plain_content;
    $anchor_text = trim((string) $anchor_text);
    if ($plain_content === '' || $anchor_text === '') {
        return 0;
    }

    $matches = array();
    $count = preg_match_all('/' . preg_quote($anchor_text, '/') . '/i', $plain_content, $matches);

    return $count === false ? 0 : absint($count);
}

function kingy_ali_product_graph_link_dry_run_first_anchor_occurrence($plain_content, $anchor_text) {
    $plain_content = (string) $plain_content;
    $anchor_text = trim((string) $anchor_text);
    if ($plain_content === '' || $anchor_text === '') {
        return '';
    }

    $matches = array();
    if (!preg_match('/' . preg_quote($anchor_text, '/') . '/i', $plain_content, $matches)) {
        return '';
    }

    return sanitize_text_field($matches[0]);
}

function kingy_ali_product_graph_link_dry_run_markup_snippet($snippet, $anchor_text, $target_url) {
    $snippet = (string) $snippet;
    $anchor_text = trim((string) $anchor_text);
    $target_url = esc_url_raw((string) $target_url);
    if ($snippet === '' || $anchor_text === '' || $target_url === '') {
        return '';
    }

    $replaced = preg_replace_callback(
        '/' . preg_quote($anchor_text, '/') . '/i',
        function ($matches) use ($target_url) {
            return '<a href="' . esc_url($target_url) . '">' . $matches[0] . '</a>';
        },
        $snippet,
        1
    );

    return is_string($replaced) ? $replaced : '';
}

function kingy_ali_product_graph_source_context_audit_columns() {
    return array(
        'audit_id',
        'recommendation_id',
        'source_url',
        'source_title',
        'source_type',
        'target_url',
        'target_title',
        'target_type',
        'suggested_anchor_text',
        'usable_anchor_text',
        'confidence',
        'review_state',
        'readiness_bucket',
        'source_wp_object_id',
        'source_wp_object_type',
        'source_content_readable',
        'target_already_linked_from_source',
        'suggested_anchor_appears_in_source_content',
        'alternate_anchor_found',
        'alternate_anchor_text',
        'usable_anchor_appears_in_source_content',
        'target_is_internal',
        'target_not_already_linked',
        'relationship_evidence_clear',
        'eligible_for_review_sprint',
        'quality_bucket',
        'quality_reason',
        'source_content_length',
        'audit_status',
        'blockers',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_source_context_audit_data() {
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $readiness_rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $rows = array();
    $counts_by_status = array();
    $counts_by_confidence = array();
    $counts_by_source_type = array();
    $counts_by_target_type = array();
    $counts_by_review_state = array();
    $counts_by_readiness_bucket = array();
    $source_resolved_count = 0;
    $source_unresolved_count = 0;
    $source_readable_count = 0;
    $target_already_linked_count = 0;
    $anchor_found_count = 0;
    $alternate_anchor_found_count = 0;
    $eligible_for_review_sprint_count = 0;
    $internal_target_count = 0;
    $external_target_count = 0;
    $empty_content_count = 0;
    $counts_by_quality_bucket = array();

    foreach ($readiness_rows as $readiness_row) {
        if (!is_array($readiness_row)) {
            continue;
        }

        $row = kingy_ali_product_graph_source_context_audit_row($readiness_row);
        $rows[] = $row;

        kingy_ali_product_graph_increment_count($counts_by_status, kingy_ali_product_graph_row_value($row, 'audit_status'));
        kingy_ali_product_graph_increment_count($counts_by_confidence, kingy_ali_product_graph_row_value($row, 'confidence'));
        kingy_ali_product_graph_increment_count($counts_by_source_type, kingy_ali_product_graph_row_value($row, 'source_type'));
        kingy_ali_product_graph_increment_count($counts_by_target_type, kingy_ali_product_graph_row_value($row, 'target_type'));
        kingy_ali_product_graph_increment_count($counts_by_review_state, kingy_ali_product_graph_row_value($row, 'review_state'));
        kingy_ali_product_graph_increment_count($counts_by_readiness_bucket, kingy_ali_product_graph_row_value($row, 'readiness_bucket'));
        kingy_ali_product_graph_increment_count($counts_by_quality_bucket, kingy_ali_product_graph_row_value($row, 'quality_bucket'));

        if (absint(kingy_ali_product_graph_row_value($row, 'source_wp_object_id')) > 0) {
            $source_resolved_count++;
        } else {
            $source_unresolved_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'source_content_readable') === 'yes') {
            $source_readable_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'target_already_linked_from_source') === 'yes') {
            $target_already_linked_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'suggested_anchor_appears_in_source_content') === 'yes') {
            $anchor_found_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'alternate_anchor_found') === 'yes') {
            $alternate_anchor_found_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'eligible_for_review_sprint') === 'yes') {
            $eligible_for_review_sprint_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'target_is_internal') === 'yes') {
            $internal_target_count++;
        } else {
            $external_target_count++;
        }
        if (kingy_ali_product_graph_row_value($row, 'audit_status') === 'source_content_empty') {
            $empty_content_count++;
        }
    }

    usort($rows, 'kingy_ali_product_graph_sort_source_context_audit_rows');

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_source_context_audit',
        'source' => 'derived_from_link_recommendations_readiness_review_overlay_and_read_only_wordpress_content',
        'row_count' => count($rows),
        'source_resolved_count' => $source_resolved_count,
        'source_unresolved_count' => $source_unresolved_count,
        'source_readable_count' => $source_readable_count,
        'target_already_linked_count' => $target_already_linked_count,
        'anchor_found_count' => $anchor_found_count,
        'alternate_anchor_found_count' => $alternate_anchor_found_count,
        'anchor_missing_count' => max(0, count($rows) - $anchor_found_count),
        'eligible_for_review_sprint_count' => $eligible_for_review_sprint_count,
        'internal_target_count' => $internal_target_count,
        'external_target_count' => $external_target_count,
        'empty_content_count' => $empty_content_count,
        'counts_by_status' => $counts_by_status,
        'counts_by_quality_bucket' => $counts_by_quality_bucket,
        'counts_by_confidence' => $counts_by_confidence,
        'counts_by_source_type' => $counts_by_source_type,
        'counts_by_target_type' => $counts_by_target_type,
        'counts_by_review_state' => $counts_by_review_state,
        'counts_by_readiness_bucket' => $counts_by_readiness_bucket,
        'filter_options' => array(
            'audit_status' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_status),
            'quality_bucket' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_quality_bucket),
            'source_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_source_type),
            'target_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_target_type),
            'confidence' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_confidence),
            'review_state' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_review_state),
        ),
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_review_batch_columns() {
    return array(
        'batch_id',
        'batch_type',
        'priority',
        'recommendation_count',
        'reviewed_count',
        'unreviewed_count',
        'accepted_count',
        'rejected_count',
        'needs_source_count',
        'needs_refresh_count',
        'needs_canonical_review_count',
        'model_inventory_blocked_count',
        'source_resolved_count',
        'source_unresolved_count',
        'existing_link_duplicate_count',
        'representative_recommendation_ids',
        'recommended_reviewer_action',
        'blocker',
        'safe_next_step',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_review_batches_data() {
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $readiness_rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $source_context = kingy_ali_product_graph_source_context_audit_data();
    $source_rows = isset($source_context['rows']) && is_array($source_context['rows']) ? $source_context['rows'] : array();
    $source_by_recommendation = array();

    foreach ($source_rows as $source_row) {
        if (!is_array($source_row)) {
            continue;
        }
        $recommendation_id = kingy_ali_product_graph_row_value($source_row, 'recommendation_id');
        if ($recommendation_id !== '') {
            $source_by_recommendation[$recommendation_id] = $source_row;
        }
    }

    $contexts = array();
    foreach ($readiness_rows as $readiness_row) {
        if (!is_array($readiness_row)) {
            continue;
        }

        $recommendation_id = kingy_ali_product_graph_row_value($readiness_row, 'recommendation_id');
        $source_row = isset($source_by_recommendation[$recommendation_id]) ? $source_by_recommendation[$recommendation_id] : array();
        $contexts[] = array(
            'readiness' => $readiness_row,
            'source_context' => $source_row,
        );
    }

    $definitions = kingy_ali_product_graph_link_review_batch_definitions();
    $rows = array();
    $counts_by_type = array();
    $counts_by_priority = array();
    $counts_by_blocker = array();
    $counts_by_review_state = array();

    foreach ($definitions as $definition) {
        $matching_contexts = kingy_ali_product_graph_link_review_batch_matching_contexts($contexts, $definition['batch_type']);
        $row = kingy_ali_product_graph_link_review_batch_row($definition, $matching_contexts);
        if (absint($row['recommendation_count']) === 0 && empty($definition['include_when_empty'])) {
            continue;
        }

        $rows[] = $row;
        kingy_ali_product_graph_increment_count($counts_by_type, $row['batch_type']);
        kingy_ali_product_graph_increment_count($counts_by_priority, $row['priority']);
        kingy_ali_product_graph_increment_count($counts_by_blocker, $row['blocker']);
        foreach (kingy_ali_product_graph_link_review_batch_review_state_counts($matching_contexts) as $state => $count) {
            if ($count > 0) {
                kingy_ali_product_graph_increment_count($counts_by_review_state, $state);
            }
        }
    }

    usort($rows, 'kingy_ali_product_graph_sort_link_review_batches');

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_internal_link_review_batches',
        'source' => 'derived_from_link_recommendations_readiness_source_context_and_review_overlay',
        'total_recommendations' => isset($readiness['total_recommendations']) ? absint($readiness['total_recommendations']) : count($readiness_rows),
        'reviewed_count' => isset($readiness['reviewed_count']) ? absint($readiness['reviewed_count']) : 0,
        'unreviewed_count' => isset($readiness['unreviewed_count']) ? absint($readiness['unreviewed_count']) : 0,
        'source_resolved_count' => isset($source_context['source_resolved_count']) ? absint($source_context['source_resolved_count']) : 0,
        'source_unresolved_count' => isset($source_context['source_unresolved_count']) ? absint($source_context['source_unresolved_count']) : 0,
        'existing_link_duplicate_count' => isset($source_context['target_already_linked_count']) ? absint($source_context['target_already_linked_count']) : 0,
        'row_count' => count($rows),
        'counts_by_batch_type' => $counts_by_type,
        'counts_by_priority' => $counts_by_priority,
        'counts_by_blocker' => $counts_by_blocker,
        'counts_by_review_state_presence' => $counts_by_review_state,
        'filter_options' => array(
            'batch_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_type),
            'priority' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_priority),
            'blocker' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_blocker),
            'review_state' => array_keys(kingy_ali_product_graph_allowed_review_states()),
        ),
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_review_batch_definitions() {
    return array(
        array(
            'batch_type' => 'needs_source_followup',
            'priority' => 'high',
            'recommended_reviewer_action' => __('Review saved needs_source notes and identify authoritative evidence before any editorial promotion.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'source_evidence_missing',
            'safe_next_step' => __('Open the recommendation or source context detail and add evidence-oriented reviewer metadata only.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'source_resolved_anchor_missing',
            'priority' => 'high',
            'recommended_reviewer_action' => __('Review source-resolved pages where the suggested anchor text is not present in the source content.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'anchor_context_missing',
            'safe_next_step' => __('Confirm whether a different anchor or source page should be considered later; do not edit content here.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'source_unresolved',
            'priority' => 'high',
            'recommended_reviewer_action' => __('Resolve or investigate recommendations whose source URL does not map to a readable WordPress object.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'source_unresolved',
            'safe_next_step' => __('Check resolver/source context and record reviewer metadata only.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'target_already_linked_duplicate_check',
            'priority' => 'high',
            'recommended_reviewer_action' => __('Review recommendations where the source already links to the target and mark duplicates as not useful if appropriate.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'duplicate_link_candidate',
            'safe_next_step' => __('Use the existing-link evidence to decide reviewer metadata; do not insert another link.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'verified_or_url_backed_unreviewed',
            'priority' => 'medium',
            'recommended_reviewer_action' => __('Review high-confidence unreviewed recommendations first because their graph evidence is strongest.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'human_review_missing',
            'safe_next_step' => __('Open recommendation details and Source Context Audit before changing review metadata.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'derived_or_editorial_evidence_needed',
            'priority' => 'medium',
            'recommended_reviewer_action' => __('Review derived/editorial recommendations for evidence gaps before they can be useful to editors.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'evidence_needed',
            'safe_next_step' => __('Find evidence or mark needs_source; keep graph artifacts immutable.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'high_confidence_ready_for_human_review',
            'priority' => 'medium',
            'recommended_reviewer_action' => __('Review verified or URL-backed recommendations that have not yet received human triage.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'human_review_missing',
            'safe_next_step' => __('Use recommendation/detail views for reviewer metadata only; no insertion workflow exists.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'batch_type' => 'rejected_or_not_useful',
            'priority' => 'low',
            'recommended_reviewer_action' => __('Keep rejected recommendations out of future planning unless a reviewer changes the metadata.', 'kingy-ai-launch-intelligence'),
            'blocker' => 'rejected_or_not_useful',
            'safe_next_step' => __('Audit rejected rows only if there is new evidence; do not create link jobs.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_product_graph_link_review_batch_matching_contexts($contexts, $batch_type) {
    $matches = array();
    foreach ($contexts as $context) {
        if (kingy_ali_product_graph_link_review_batch_context_matches($context, $batch_type)) {
            $matches[] = $context;
        }
    }

    return $matches;
}

function kingy_ali_product_graph_link_review_batch_context_matches($context, $batch_type) {
    $readiness = isset($context['readiness']) && is_array($context['readiness']) ? $context['readiness'] : array();
    $source_context = isset($context['source_context']) && is_array($context['source_context']) ? $context['source_context'] : array();
    $review_state = kingy_ali_product_graph_row_value($readiness, 'review_state', 'unreviewed');
    $confidence = kingy_ali_product_graph_row_value($readiness, 'confidence');
    $readiness_bucket = kingy_ali_product_graph_row_value($readiness, 'readiness_bucket');
    $audit_status = kingy_ali_product_graph_row_value($source_context, 'audit_status');
    $source_readable = kingy_ali_product_graph_row_value($source_context, 'source_content_readable');
    $target_already_linked = kingy_ali_product_graph_row_value($source_context, 'target_already_linked_from_source');

    switch ($batch_type) {
        case 'needs_source_followup':
            return $review_state === 'needs_source';
        case 'source_resolved_anchor_missing':
            return $source_readable === 'yes' && $audit_status === 'anchor_text_not_found';
        case 'source_unresolved':
            return $audit_status === 'source_unresolved';
        case 'target_already_linked_duplicate_check':
            return $target_already_linked === 'yes';
        case 'verified_or_url_backed_unreviewed':
            return $review_state === 'unreviewed' && in_array($confidence, array('verified', 'url-backed'), true);
        case 'derived_or_editorial_evidence_needed':
            return in_array($confidence, array('derived', 'editorial'), true) && in_array($readiness_bucket, array('blocked_missing_evidence', 'not_reviewed'), true);
        case 'high_confidence_ready_for_human_review':
            return $review_state === 'unreviewed' && in_array($confidence, array('verified', 'url-backed'), true) && $readiness_bucket === 'not_reviewed';
        case 'rejected_or_not_useful':
            return $review_state === 'rejected' || $readiness_bucket === 'rejected_or_not_useful';
    }

    return false;
}

function kingy_ali_product_graph_link_review_batch_row($definition, $contexts) {
    $review_state_counts = kingy_ali_product_graph_link_review_batch_review_state_counts($contexts);
    $source_resolved_count = 0;
    $source_unresolved_count = 0;
    $existing_link_duplicate_count = 0;
    $representative_ids = array();

    foreach ($contexts as $context) {
        $readiness = isset($context['readiness']) && is_array($context['readiness']) ? $context['readiness'] : array();
        $source_context = isset($context['source_context']) && is_array($context['source_context']) ? $context['source_context'] : array();
        $recommendation_id = kingy_ali_product_graph_row_value($readiness, 'recommendation_id');
        if ($recommendation_id !== '' && count($representative_ids) < 8) {
            $representative_ids[] = $recommendation_id;
        }
        if (absint(kingy_ali_product_graph_row_value($source_context, 'source_wp_object_id')) > 0) {
            $source_resolved_count++;
        } else {
            $source_unresolved_count++;
        }
        if (kingy_ali_product_graph_row_value($source_context, 'target_already_linked_from_source') === 'yes') {
            $existing_link_duplicate_count++;
        }
    }

    $batch_type = sanitize_key($definition['batch_type']);
    return array(
        'batch_id' => 'link-review-batch:' . substr(md5($batch_type), 0, 16),
        'batch_type' => $batch_type,
        'priority' => sanitize_key($definition['priority']),
        'recommendation_count' => (string) count($contexts),
        'reviewed_count' => (string) kingy_ali_product_graph_link_review_batch_reviewed_count($contexts),
        'unreviewed_count' => (string) (isset($review_state_counts['unreviewed']) ? absint($review_state_counts['unreviewed']) : 0),
        'accepted_count' => (string) (isset($review_state_counts['accepted']) ? absint($review_state_counts['accepted']) : 0),
        'rejected_count' => (string) (isset($review_state_counts['rejected']) ? absint($review_state_counts['rejected']) : 0),
        'needs_source_count' => (string) (isset($review_state_counts['needs_source']) ? absint($review_state_counts['needs_source']) : 0),
        'needs_refresh_count' => (string) (isset($review_state_counts['needs_refresh']) ? absint($review_state_counts['needs_refresh']) : 0),
        'needs_canonical_review_count' => (string) (isset($review_state_counts['needs_canonical_review']) ? absint($review_state_counts['needs_canonical_review']) : 0),
        'model_inventory_blocked_count' => (string) (isset($review_state_counts['model_inventory_blocked']) ? absint($review_state_counts['model_inventory_blocked']) : 0),
        'source_resolved_count' => (string) $source_resolved_count,
        'source_unresolved_count' => (string) $source_unresolved_count,
        'existing_link_duplicate_count' => (string) $existing_link_duplicate_count,
        'representative_recommendation_ids' => implode(', ', $representative_ids),
        'recommended_reviewer_action' => sanitize_text_field($definition['recommended_reviewer_action']),
        'blocker' => sanitize_key($definition['blocker']),
        'safe_next_step' => sanitize_text_field($definition['safe_next_step']),
        'insertable' => 'false',
        'write_capable' => 'false',
    );
}

function kingy_ali_product_graph_link_review_batch_review_state_counts($contexts) {
    $counts = array();
    foreach ($contexts as $context) {
        $readiness = isset($context['readiness']) && is_array($context['readiness']) ? $context['readiness'] : array();
        kingy_ali_product_graph_increment_count($counts, kingy_ali_product_graph_row_value($readiness, 'review_state', 'unreviewed'));
    }

    return $counts;
}

function kingy_ali_product_graph_link_review_batch_reviewed_count($contexts) {
    $count = 0;
    foreach ($contexts as $context) {
        $readiness = isset($context['readiness']) && is_array($context['readiness']) ? $context['readiness'] : array();
        if (kingy_ali_product_graph_row_value($readiness, 'review_state', 'unreviewed') !== 'unreviewed') {
            $count++;
        }
    }

    return $count;
}

function kingy_ali_product_graph_link_review_batch_detail_rows($batch_type) {
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $readiness_rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $source_context = kingy_ali_product_graph_source_context_audit_data();
    $source_rows = isset($source_context['rows']) && is_array($source_context['rows']) ? $source_context['rows'] : array();
    $source_by_recommendation = array();

    foreach ($source_rows as $source_row) {
        if (!is_array($source_row)) {
            continue;
        }
        $recommendation_id = kingy_ali_product_graph_row_value($source_row, 'recommendation_id');
        if ($recommendation_id !== '') {
            $source_by_recommendation[$recommendation_id] = $source_row;
        }
    }

    $rows = array();
    foreach ($readiness_rows as $readiness_row) {
        if (!is_array($readiness_row)) {
            continue;
        }

        $recommendation_id = kingy_ali_product_graph_row_value($readiness_row, 'recommendation_id');
        $source_row = isset($source_by_recommendation[$recommendation_id]) ? $source_by_recommendation[$recommendation_id] : array();
        $context = array(
            'readiness' => $readiness_row,
            'source_context' => $source_row,
        );
        if (!kingy_ali_product_graph_link_review_batch_context_matches($context, $batch_type)) {
            continue;
        }

        $rows[] = array(
            'recommendation_id' => $recommendation_id,
            'source_title' => kingy_ali_product_graph_row_value($readiness_row, 'source_title'),
            'source_url' => kingy_ali_product_graph_row_value($readiness_row, 'source_url'),
            'source_type' => kingy_ali_product_graph_row_value($readiness_row, 'source_type'),
            'target_title' => kingy_ali_product_graph_row_value($readiness_row, 'target_title'),
            'target_url' => kingy_ali_product_graph_row_value($readiness_row, 'target_url'),
            'target_type' => kingy_ali_product_graph_row_value($readiness_row, 'target_type'),
            'confidence' => kingy_ali_product_graph_row_value($readiness_row, 'confidence'),
            'review_state' => kingy_ali_product_graph_row_value($readiness_row, 'review_state', 'unreviewed'),
            'source_context_audit_status' => kingy_ali_product_graph_row_value($source_row, 'audit_status'),
            'safe_reviewer_question' => kingy_ali_product_graph_link_review_batch_reviewer_question($batch_type),
            'source_context_audit_id' => kingy_ali_product_graph_row_value($source_row, 'audit_id'),
        );
    }

    return $rows;
}

function kingy_ali_product_graph_link_review_batch_reviewer_question($batch_type) {
    switch ($batch_type) {
        case 'needs_source_followup':
            return __('What authoritative source would satisfy this saved needs_source review state?', 'kingy-ai-launch-intelligence');
        case 'source_resolved_anchor_missing':
            return __('Is there a better anchor phrase already present in the source content?', 'kingy-ai-launch-intelligence');
        case 'source_unresolved':
            return __('Should this source URL map to an existing WordPress object before review continues?', 'kingy-ai-launch-intelligence');
        case 'target_already_linked_duplicate_check':
            return __('Is this recommendation already satisfied by an existing source-to-target link?', 'kingy-ai-launch-intelligence');
        case 'verified_or_url_backed_unreviewed':
        case 'high_confidence_ready_for_human_review':
            return __('Does the source context support this high-confidence graph relationship?', 'kingy-ai-launch-intelligence');
        case 'derived_or_editorial_evidence_needed':
            return __('What evidence is needed before this derived/editorial recommendation becomes useful?', 'kingy-ai-launch-intelligence');
        case 'rejected_or_not_useful':
            return __('Is there new evidence that would justify revisiting this rejected recommendation?', 'kingy-ai-launch-intelligence');
    }

    return __('What should a human reviewer verify before this recommendation can move forward?', 'kingy-ai-launch-intelligence');
}

function kingy_ali_product_graph_sort_link_review_batches($a, $b) {
    $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2);
    $a_priority = kingy_ali_product_graph_row_value($a, 'priority');
    $b_priority = kingy_ali_product_graph_row_value($b, 'priority');
    $a_weight = isset($priority_order[$a_priority]) ? $priority_order[$a_priority] : 9;
    $b_weight = isset($priority_order[$b_priority]) ? $priority_order[$b_priority] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $a_count = absint(kingy_ali_product_graph_row_value($a, 'recommendation_count'));
    $b_count = absint(kingy_ali_product_graph_row_value($b, 'recommendation_count'));
    if ($a_count !== $b_count) {
        return $b_count - $a_count;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'batch_type'), kingy_ali_product_graph_row_value($b, 'batch_type'));
}

function kingy_ali_product_graph_link_batch_progress_columns() {
    return array(
        'batch_id',
        'batch_type',
        'priority',
        'recommendation_count',
        'reviewed_count',
        'unreviewed_count',
        'accepted_count',
        'rejected_count',
        'needs_source_count',
        'needs_refresh_count',
        'needs_canonical_review_count',
        'completion_percent',
        'source_resolved_percent',
        'completion_range',
        'blocker',
        'recommended_next_reviewer_action',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_link_batch_progress_data() {
    $batches = kingy_ali_product_graph_link_review_batches_data();
    $batch_rows = isset($batches['rows']) && is_array($batches['rows']) ? $batches['rows'] : array();
    $rows = array();
    $counts_by_batch_type = array();
    $counts_by_priority = array();
    $counts_by_blocker = array();
    $counts_by_completion_range = array();
    $counts_by_review_state_presence = array();
    $total_memberships = 0;
    $reviewed_memberships = 0;
    $unreviewed_memberships = 0;
    $high_priority_rows = 0;
    $high_priority_memberships = 0;
    $high_priority_reviewed = 0;
    $followup_counts = array(
        'accepted' => 0,
        'rejected' => 0,
        'needs_source' => 0,
        'needs_refresh' => 0,
        'needs_canonical_review' => 0,
        'model_inventory_blocked' => 0,
    );

    foreach ($batch_rows as $batch_row) {
        if (!is_array($batch_row)) {
            continue;
        }

        $row = kingy_ali_product_graph_link_batch_progress_row($batch_row);
        $rows[] = $row;
        $recommendation_count = absint($row['recommendation_count']);
        $reviewed_count = absint($row['reviewed_count']);
        $unreviewed_count = absint($row['unreviewed_count']);
        $total_memberships += $recommendation_count;
        $reviewed_memberships += $reviewed_count;
        $unreviewed_memberships += $unreviewed_count;

        if ($row['priority'] === 'high') {
            $high_priority_rows++;
            $high_priority_memberships += $recommendation_count;
            $high_priority_reviewed += $reviewed_count;
        }

        foreach ($followup_counts as $state => $count) {
            $field = $state . '_count';
            if (isset($row[$field])) {
                $followup_counts[$state] += absint($row[$field]);
            }
        }

        kingy_ali_product_graph_increment_count($counts_by_batch_type, $row['batch_type']);
        kingy_ali_product_graph_increment_count($counts_by_priority, $row['priority']);
        kingy_ali_product_graph_increment_count($counts_by_blocker, $row['blocker']);
        kingy_ali_product_graph_increment_count($counts_by_completion_range, $row['completion_range']);

        foreach (kingy_ali_product_graph_allowed_review_states() as $state => $label) {
            if (kingy_ali_product_graph_link_review_batch_row_state_count($row, $state) > 0) {
                kingy_ali_product_graph_increment_count($counts_by_review_state_presence, $state);
            }
        }
    }

    usort($rows, 'kingy_ali_product_graph_sort_link_batch_progress_rows');

    $recommended_next_batch = kingy_ali_product_graph_link_batch_progress_recommended_next_batch($rows);

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_internal_link_batch_progress',
        'source' => 'derived_from_link_review_batches_readiness_source_context_and_review_overlay',
        'total_recommendations' => isset($batches['total_recommendations']) ? absint($batches['total_recommendations']) : 0,
        'batch_row_count' => count($rows),
        'batch_membership_count' => $total_memberships,
        'reviewed_membership_count' => $reviewed_memberships,
        'unreviewed_membership_count' => $unreviewed_memberships,
        'overall_completion_percent' => kingy_ali_product_graph_link_batch_progress_percent($reviewed_memberships, $total_memberships),
        'high_priority_batch_rows' => $high_priority_rows,
        'high_priority_membership_count' => $high_priority_memberships,
        'high_priority_reviewed_count' => $high_priority_reviewed,
        'high_priority_completion_percent' => kingy_ali_product_graph_link_batch_progress_percent($high_priority_reviewed, $high_priority_memberships),
        'followup_counts' => $followup_counts,
        'counts_by_batch_type' => $counts_by_batch_type,
        'counts_by_priority' => $counts_by_priority,
        'counts_by_blocker' => $counts_by_blocker,
        'counts_by_completion_range' => $counts_by_completion_range,
        'counts_by_review_state_presence' => $counts_by_review_state_presence,
        'recommended_next_batch' => $recommended_next_batch,
        'filter_options' => array(
            'batch_type' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_batch_type),
            'priority' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_priority),
            'blocker' => kingy_ali_product_graph_link_plan_preview_sorted_keys($counts_by_blocker),
            'completion_range' => kingy_ali_product_graph_link_batch_progress_completion_range_options(),
            'review_state' => array_keys(kingy_ali_product_graph_allowed_review_states()),
        ),
        'rows' => $rows,
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_link_batch_progress_row($batch_row) {
    $recommendation_count = absint(kingy_ali_product_graph_row_value($batch_row, 'recommendation_count'));
    $reviewed_count = absint(kingy_ali_product_graph_row_value($batch_row, 'reviewed_count'));
    $source_resolved_count = absint(kingy_ali_product_graph_row_value($batch_row, 'source_resolved_count'));
    $completion_percent = kingy_ali_product_graph_link_batch_progress_percent($reviewed_count, $recommendation_count);
    $source_resolved_percent = kingy_ali_product_graph_link_batch_progress_percent($source_resolved_count, $recommendation_count);

    return array(
        'batch_id' => kingy_ali_product_graph_row_value($batch_row, 'batch_id'),
        'batch_type' => kingy_ali_product_graph_row_value($batch_row, 'batch_type'),
        'priority' => kingy_ali_product_graph_row_value($batch_row, 'priority'),
        'recommendation_count' => (string) $recommendation_count,
        'reviewed_count' => (string) $reviewed_count,
        'unreviewed_count' => kingy_ali_product_graph_row_value($batch_row, 'unreviewed_count'),
        'accepted_count' => kingy_ali_product_graph_row_value($batch_row, 'accepted_count'),
        'rejected_count' => kingy_ali_product_graph_row_value($batch_row, 'rejected_count'),
        'needs_source_count' => kingy_ali_product_graph_row_value($batch_row, 'needs_source_count'),
        'needs_refresh_count' => kingy_ali_product_graph_row_value($batch_row, 'needs_refresh_count'),
        'needs_canonical_review_count' => kingy_ali_product_graph_row_value($batch_row, 'needs_canonical_review_count'),
        'model_inventory_blocked_count' => kingy_ali_product_graph_row_value($batch_row, 'model_inventory_blocked_count'),
        'completion_percent' => $completion_percent,
        'source_resolved_percent' => $source_resolved_percent,
        'completion_range' => kingy_ali_product_graph_link_batch_progress_completion_range($completion_percent, $recommendation_count),
        'source_resolved_count' => kingy_ali_product_graph_row_value($batch_row, 'source_resolved_count'),
        'source_unresolved_count' => kingy_ali_product_graph_row_value($batch_row, 'source_unresolved_count'),
        'existing_link_duplicate_count' => kingy_ali_product_graph_row_value($batch_row, 'existing_link_duplicate_count'),
        'representative_recommendation_ids' => kingy_ali_product_graph_row_value($batch_row, 'representative_recommendation_ids'),
        'blocker' => kingy_ali_product_graph_row_value($batch_row, 'blocker'),
        'recommended_next_reviewer_action' => kingy_ali_product_graph_row_value($batch_row, 'recommended_reviewer_action'),
        'safe_next_step' => kingy_ali_product_graph_row_value($batch_row, 'safe_next_step'),
        'insertable' => 'false',
        'write_capable' => 'false',
    );
}

function kingy_ali_product_graph_link_batch_progress_percent($part, $total) {
    $total = absint($total);
    if ($total === 0) {
        return '0.0';
    }

    return (string) round((absint($part) / $total) * 100, 1);
}

function kingy_ali_product_graph_link_batch_progress_completion_range_options() {
    return array('not_started', '1_to_24', '25_to_49', '50_to_74', '75_to_99', 'complete');
}

function kingy_ali_product_graph_link_batch_progress_completion_range($completion_percent, $total) {
    if (absint($total) === 0) {
        return 'not_started';
    }

    $completion = (float) $completion_percent;
    if ($completion <= 0) {
        return 'not_started';
    }
    if ($completion >= 100) {
        return 'complete';
    }
    if ($completion < 25) {
        return '1_to_24';
    }
    if ($completion < 50) {
        return '25_to_49';
    }
    if ($completion < 75) {
        return '50_to_74';
    }

    return '75_to_99';
}

function kingy_ali_product_graph_link_batch_progress_recommended_next_batch($rows) {
    $best = array();
    foreach ($rows as $row) {
        if (!is_array($row) || absint(kingy_ali_product_graph_row_value($row, 'unreviewed_count')) === 0) {
            continue;
        }
        if (!$best || kingy_ali_product_graph_sort_link_batch_progress_rows($row, $best) < 0) {
            $best = $row;
        }
    }

    if (!$best) {
        return array(
            'batch_type' => 'none_available',
            'recommended_next_reviewer_action' => __('No unreviewed batch rows are available in the current dataset.', 'kingy-ai-launch-intelligence'),
        );
    }

    return array(
        'batch_id' => kingy_ali_product_graph_row_value($best, 'batch_id'),
        'batch_type' => kingy_ali_product_graph_row_value($best, 'batch_type'),
        'priority' => kingy_ali_product_graph_row_value($best, 'priority'),
        'unreviewed_count' => kingy_ali_product_graph_row_value($best, 'unreviewed_count'),
        'completion_percent' => kingy_ali_product_graph_row_value($best, 'completion_percent'),
        'blocker' => kingy_ali_product_graph_row_value($best, 'blocker'),
        'recommended_next_reviewer_action' => kingy_ali_product_graph_row_value($best, 'recommended_next_reviewer_action'),
    );
}

function kingy_ali_product_graph_sort_link_batch_progress_rows($a, $b) {
    $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2);
    $a_priority = kingy_ali_product_graph_row_value($a, 'priority');
    $b_priority = kingy_ali_product_graph_row_value($b, 'priority');
    $a_weight = isset($priority_order[$a_priority]) ? $priority_order[$a_priority] : 9;
    $b_weight = isset($priority_order[$b_priority]) ? $priority_order[$b_priority] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    $a_unreviewed = absint(kingy_ali_product_graph_row_value($a, 'unreviewed_count'));
    $b_unreviewed = absint(kingy_ali_product_graph_row_value($b, 'unreviewed_count'));
    if ($a_unreviewed !== $b_unreviewed) {
        return $b_unreviewed - $a_unreviewed;
    }

    $a_completion = (float) kingy_ali_product_graph_row_value($a, 'completion_percent');
    $b_completion = (float) kingy_ali_product_graph_row_value($b, 'completion_percent');
    if ($a_completion !== $b_completion) {
        return $a_completion < $b_completion ? -1 : 1;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'batch_type'), kingy_ali_product_graph_row_value($b, 'batch_type'));
}

function kingy_ali_product_graph_stage3_closeout_columns() {
    return array(
        'recommendation_count',
        'reviewed_count',
        'unreviewed_count',
        'accepted_count',
        'rejected_count',
        'needs_source_count',
        'needs_refresh_count',
        'needs_canonical_review_count',
        'source_resolved_count',
        'source_unresolved_count',
        'anchor_missing_count',
        'target_already_linked_count',
        'plan_preview_eligible_count',
        'batch_row_count',
        'batch_completion_percent',
        'recommended_next_batch',
        'non_insertable_non_write_capable_count',
        'insertable',
        'write_capable',
    );
}

function kingy_ali_product_graph_stage3_closeout_data() {
    $recommendations = kingy_ali_product_graph_link_recommendations_data();
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $preview = kingy_ali_product_graph_link_plan_preview_data();
    $audit = kingy_ali_product_graph_source_context_audit_data();
    $batches = kingy_ali_product_graph_link_review_batches_data();
    $progress = kingy_ali_product_graph_link_batch_progress_data();

    $review_state_counts = isset($readiness['counts_by_review_state']) && is_array($readiness['counts_by_review_state']) ? $readiness['counts_by_review_state'] : array();
    $recommended_next_batch = isset($progress['recommended_next_batch']) && is_array($progress['recommended_next_batch']) ? $progress['recommended_next_batch'] : array();
    $recommended_next_batch_type = kingy_ali_product_graph_row_value($recommended_next_batch, 'batch_type', 'source_unresolved');
    $recommendation_count = isset($readiness['total_recommendations']) ? absint($readiness['total_recommendations']) : (isset($recommendations['row_count']) ? absint($recommendations['row_count']) : 0);
    $non_insertable_non_write_capable_count = isset($readiness['non_insertable_non_write_capable_recommendations']) ? absint($readiness['non_insertable_non_write_capable_recommendations']) : 0;

    $row = array(
        'recommendation_count' => (string) $recommendation_count,
        'reviewed_count' => (string) (isset($readiness['reviewed_count']) ? absint($readiness['reviewed_count']) : 0),
        'unreviewed_count' => (string) (isset($readiness['unreviewed_count']) ? absint($readiness['unreviewed_count']) : 0),
        'accepted_count' => (string) (isset($review_state_counts['accepted']) ? absint($review_state_counts['accepted']) : 0),
        'rejected_count' => (string) (isset($review_state_counts['rejected']) ? absint($review_state_counts['rejected']) : 0),
        'needs_source_count' => (string) (isset($review_state_counts['needs_source']) ? absint($review_state_counts['needs_source']) : 0),
        'needs_refresh_count' => (string) (isset($review_state_counts['needs_refresh']) ? absint($review_state_counts['needs_refresh']) : 0),
        'needs_canonical_review_count' => (string) (isset($review_state_counts['needs_canonical_review']) ? absint($review_state_counts['needs_canonical_review']) : 0),
        'source_resolved_count' => (string) (isset($audit['source_resolved_count']) ? absint($audit['source_resolved_count']) : 0),
        'source_unresolved_count' => (string) (isset($audit['source_unresolved_count']) ? absint($audit['source_unresolved_count']) : 0),
        'anchor_missing_count' => (string) (isset($audit['anchor_missing_count']) ? absint($audit['anchor_missing_count']) : 0),
        'target_already_linked_count' => (string) (isset($audit['target_already_linked_count']) ? absint($audit['target_already_linked_count']) : 0),
        'plan_preview_eligible_count' => (string) (isset($preview['plan_preview_eligible']) ? absint($preview['plan_preview_eligible']) : 0),
        'batch_row_count' => (string) (isset($progress['batch_row_count']) ? absint($progress['batch_row_count']) : (isset($batches['row_count']) ? absint($batches['row_count']) : 0)),
        'batch_completion_percent' => isset($progress['overall_completion_percent']) ? (string) $progress['overall_completion_percent'] : '0.0',
        'recommended_next_batch' => $recommended_next_batch_type,
        'non_insertable_non_write_capable_count' => (string) $non_insertable_non_write_capable_count,
        'insertable' => 'false',
        'write_capable' => 'false',
    );

    return array(
        'generated_at_utc' => gmdate('c'),
        'mode' => 'read_only_product_graph_stage3_closeout_stage4_readiness',
        'source' => 'derived_from_link_recommendations_readiness_plan_preview_source_context_batches_batch_progress_and_review_overlay',
        'what_stage3_added' => kingy_ali_product_graph_stage3_closeout_added_items(),
        'current_recommendation_state' => array(
            'recommendation_count' => $row['recommendation_count'],
            'reviewed_count' => $row['reviewed_count'],
            'unreviewed_count' => $row['unreviewed_count'],
            'accepted_count' => $row['accepted_count'],
            'rejected_count' => $row['rejected_count'],
            'needs_source_count' => $row['needs_source_count'],
            'needs_refresh_count' => $row['needs_refresh_count'],
            'needs_canonical_review_count' => $row['needs_canonical_review_count'],
            'non_insertable_non_write_capable_count' => $row['non_insertable_non_write_capable_count'],
        ),
        'reviewer_progress' => array(
            'reviewed_count' => $row['reviewed_count'],
            'unreviewed_count' => $row['unreviewed_count'],
            'review_overlay_records' => count(kingy_ali_product_graph_flatten_review_overlay()),
        ),
        'source_context_state' => array(
            'source_resolved_count' => $row['source_resolved_count'],
            'source_unresolved_count' => $row['source_unresolved_count'],
            'anchor_missing_count' => $row['anchor_missing_count'],
            'target_already_linked_count' => $row['target_already_linked_count'],
        ),
        'batch_progress_state' => array(
            'batch_row_count' => $row['batch_row_count'],
            'batch_membership_count' => isset($progress['batch_membership_count']) ? (string) absint($progress['batch_membership_count']) : '0',
            'reviewed_membership_count' => isset($progress['reviewed_membership_count']) ? (string) absint($progress['reviewed_membership_count']) : '0',
            'unreviewed_membership_count' => isset($progress['unreviewed_membership_count']) ? (string) absint($progress['unreviewed_membership_count']) : '0',
            'batch_completion_percent' => $row['batch_completion_percent'],
            'recommended_next_batch' => $recommended_next_batch_type,
        ),
        'stage4_blockers' => kingy_ali_product_graph_stage3_closeout_blockers($row),
        'safe_next_human_actions' => kingy_ali_product_graph_stage3_closeout_safe_next_actions(),
        'what_this_screen_still_cannot_do' => kingy_ali_product_graph_stage3_closeout_cannot_do(),
        'rows' => array($row),
        'insertable' => false,
        'write_capable' => false,
    );
}

function kingy_ali_product_graph_stage3_closeout_added_items() {
    return array(
        __('Read-only internal link recommendation generation from Product Graph relationships.', 'kingy-ai-launch-intelligence'),
        __('Reviewer triage metadata for recommendations through the existing review overlay.', 'kingy-ai-launch-intelligence'),
        __('Link readiness diagnostics and non-insertable plan preview gating.', 'kingy-ai-launch-intelligence'),
        __('Read-only source context audit using safe WordPress reads only.', 'kingy-ai-launch-intelligence'),
        __('Source-context triage controls that still save metadata only.', 'kingy-ai-launch-intelligence'),
        __('Link review batches and batch progress prioritization.', 'kingy-ai-launch-intelligence'),
        __('Stage 4 readiness summary that keeps future writes blocked.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_product_graph_stage3_closeout_blockers($row) {
    return array(
        sprintf(__('Accepted recommendations: %s. Stage 4 remains blocked until accepted recommendations exist.', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'accepted_count')),
        sprintf(__('Plan-preview eligible recommendations: %s. No insertion plan can be generated yet.', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'plan_preview_eligible_count')),
        sprintf(__('Unresolved source context rows remain: %s.', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'source_unresolved_count')),
        sprintf(__('Needs-source follow-ups remain: %s.', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'needs_source_count')),
        __('No insertion workflow exists in Product Graph admin.', 'kingy-ai-launch-intelligence'),
        __('No content-write approval exists.', 'kingy-ai-launch-intelligence'),
        __('Source graph artifacts are read-only and immutable in this workflow.', 'kingy-ai-launch-intelligence'),
        __('Human editorial review is still required before any future Stage 4 design.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_product_graph_stage3_closeout_safe_next_actions() {
    return array(
        __('Review the source_unresolved batch first.', 'kingy-ai-launch-intelligence'),
        __('Review the source_resolved_anchor_missing batch next.', 'kingy-ai-launch-intelligence'),
        __('Use Source Context Audit details before accepting anything.', 'kingy-ai-launch-intelligence'),
        __('Record reviewer metadata only in the existing review overlay.', 'kingy-ai-launch-intelligence'),
        __('Do not insert links from Product Graph admin.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_product_graph_stage3_closeout_cannot_do() {
    return array(
        __('Cannot edit posts, pages, CPTs, menus, SEO, schema, redirects, robots, or routes.', 'kingy-ai-launch-intelligence'),
        __('Cannot insert internal links or create insertion jobs.', 'kingy-ai-launch-intelligence'),
        __('Cannot auto-approve recommendations.', 'kingy-ai-launch-intelligence'),
        __('Cannot mutate graph source JSON/CSV artifacts.', 'kingy-ai-launch-intelligence'),
        __('Cannot replace human editorial review or separate content-write authorization.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_product_graph_source_context_audit_row($readiness_row) {
    $recommendation_id = kingy_ali_product_graph_row_value($readiness_row, 'recommendation_id');
    $source_url = kingy_ali_product_graph_row_value($readiness_row, 'source_url');
    $target_url = kingy_ali_product_graph_row_value($readiness_row, 'target_url');
    $anchor_text = kingy_ali_product_graph_row_value($readiness_row, 'suggested_anchor_text');
    $source_id = kingy_ali_product_graph_resolve_source_object_id($source_url);
    $post = $source_id > 0 ? get_post($source_id) : null;
    $post_type = $post instanceof WP_Post ? $post->post_type : '';
    $content = $post instanceof WP_Post && is_string($post->post_content) ? $post->post_content : '';
    $plain_content = trim(wp_strip_all_tags($content));
    $content_length = function_exists('mb_strlen') ? mb_strlen($plain_content) : strlen($plain_content);
    $source_readable = $post instanceof WP_Post ? 'yes' : 'no';
    $target_already_linked = $content !== '' && kingy_ali_product_graph_source_context_content_has_url($content, $target_url) ? 'yes' : kingy_ali_product_graph_row_value($readiness_row, 'target_already_linked_from_source', 'no');
    $anchor_found = $plain_content !== '' && $anchor_text !== '' && stripos($plain_content, $anchor_text) !== false ? 'yes' : 'no';
    $quality = kingy_ali_product_graph_internal_link_quality_context($readiness_row, $target_already_linked, $source_id, $content, $plain_content);
    $usable_anchor_text = kingy_ali_product_graph_row_value($quality, 'usable_anchor_text');
    $usable_anchor_found = $usable_anchor_text !== '' ? 'yes' : 'no';
    $audit_status = kingy_ali_product_graph_source_context_audit_status($source_id, $content_length, $target_already_linked, $usable_anchor_found, kingy_ali_product_graph_row_value($readiness_row, 'review_state', 'unreviewed'));
    $blockers = kingy_ali_product_graph_source_context_blockers($audit_status, $source_id, $content_length, $target_already_linked, $usable_anchor_found, $readiness_row, $quality);
    $safe_next_action = kingy_ali_product_graph_link_target_is_external($readiness_row)
        ? __('Keep as evidence/reference relationship; not an internal-link insertion candidate.', 'kingy-ai-launch-intelligence')
        : kingy_ali_product_graph_source_context_safe_next_action($audit_status);

    return array(
        'audit_id' => 'source-context:' . substr(md5($recommendation_id . '|' . $source_url . '|' . $target_url), 0, 16),
        'recommendation_id' => $recommendation_id,
        'source_url' => $source_url,
        'source_title' => kingy_ali_product_graph_row_value($readiness_row, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($readiness_row, 'source_type'),
        'target_url' => $target_url,
        'target_title' => kingy_ali_product_graph_row_value($readiness_row, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($readiness_row, 'target_type'),
        'suggested_anchor_text' => $anchor_text,
        'usable_anchor_text' => $usable_anchor_text,
        'confidence' => kingy_ali_product_graph_row_value($readiness_row, 'confidence'),
        'review_state' => kingy_ali_product_graph_row_value($readiness_row, 'review_state', 'unreviewed'),
        'readiness_bucket' => kingy_ali_product_graph_row_value($readiness_row, 'readiness_bucket'),
        'source_wp_object_id' => $source_id > 0 ? (string) $source_id : '',
        'source_wp_object_type' => $post_type,
        'source_content_readable' => kingy_ali_product_graph_row_value($quality, 'source_content_readable', $source_readable),
        'target_already_linked_from_source' => $target_already_linked,
        'suggested_anchor_appears_in_source_content' => $anchor_found,
        'alternate_anchor_found' => kingy_ali_product_graph_row_value($quality, 'alternate_anchor_found'),
        'alternate_anchor_text' => kingy_ali_product_graph_row_value($quality, 'alternate_anchor_text'),
        'usable_anchor_appears_in_source_content' => $usable_anchor_found,
        'target_is_internal' => kingy_ali_product_graph_row_value($quality, 'target_is_internal'),
        'target_not_already_linked' => kingy_ali_product_graph_row_value($quality, 'target_not_already_linked'),
        'relationship_evidence_clear' => kingy_ali_product_graph_row_value($quality, 'relationship_evidence_clear'),
        'eligible_for_review_sprint' => kingy_ali_product_graph_row_value($quality, 'eligible_for_review_sprint'),
        'quality_bucket' => kingy_ali_product_graph_row_value($quality, 'quality_bucket'),
        'quality_reason' => kingy_ali_product_graph_row_value($quality, 'quality_reason'),
        'source_content_length' => (string) $content_length,
        'audit_status' => $audit_status,
        'blockers' => implode(', ', $blockers),
        'safe_next_action' => $safe_next_action,
        'anchor_context_excerpt' => $usable_anchor_found === 'yes' ? kingy_ali_product_graph_source_context_excerpt($plain_content, $usable_anchor_text) : '',
        'existing_target_link_evidence' => $target_already_linked === 'yes' ? kingy_ali_product_graph_source_context_link_evidence($content, $target_url) : '',
        'insertable' => 'false',
        'write_capable' => 'false',
    );
}

function kingy_ali_product_graph_resolve_source_object_id($url) {
    $url = esc_url_raw((string) $url);
    if ($url === '') {
        return 0;
    }

    $id = url_to_postid($url);
    if ($id > 0) {
        return absint($id);
    }

    $path = parse_url($url, PHP_URL_PATH);
    if (is_string($path) && $path !== '') {
        $id = url_to_postid(home_url($path));
        if ($id > 0) {
            return absint($id);
        }
        $id = url_to_postid(home_url(trailingslashit($path)));
        if ($id > 0) {
            return absint($id);
        }
    }

    return 0;
}

function kingy_ali_product_graph_source_context_content_has_url($content, $target_url) {
    $content = (string) $content;
    foreach (kingy_ali_product_graph_source_context_url_candidates($target_url) as $candidate) {
        if ($candidate !== '' && stripos($content, $candidate) !== false) {
            return true;
        }
    }

    return false;
}

function kingy_ali_product_graph_source_context_url_candidates($url) {
    $url = esc_url_raw((string) $url);
    if ($url === '') {
        return array();
    }

    $candidates = array($url, trailingslashit($url), untrailingslashit($url));
    $path = parse_url($url, PHP_URL_PATH);
    if (is_string($path) && $path !== '') {
        $candidates[] = $path;
        $candidates[] = trailingslashit($path);
        $candidates[] = untrailingslashit($path);
    }

    return array_values(array_unique(array_filter($candidates)));
}

function kingy_ali_product_graph_source_context_audit_status($source_id, $content_length, $target_already_linked, $anchor_found, $review_state) {
    if ($source_id <= 0) {
        return 'source_unresolved';
    }
    if ($content_length <= 0) {
        return 'source_content_empty';
    }
    if ($target_already_linked === 'yes') {
        return 'target_already_linked';
    }
    if ($anchor_found !== 'yes') {
        return 'anchor_text_not_found';
    }
    if ($review_state !== 'accepted') {
        return 'review_required';
    }

    return 'source_resolved_context_available';
}

function kingy_ali_product_graph_source_context_blockers($audit_status, $source_id, $content_length, $target_already_linked, $anchor_found, $readiness_row, $quality = null) {
    $blockers = array('not_insertable_not_write_capable');
    $quality = is_array($quality) ? $quality : kingy_ali_product_graph_internal_link_quality_context($readiness_row, $target_already_linked);
    if ($source_id <= 0) {
        $blockers[] = 'source_unresolved';
    }
    if ($content_length <= 0) {
        $blockers[] = 'source_content_empty';
    }
    if ($target_already_linked === 'yes') {
        $blockers[] = 'target_already_linked';
    }
    if (kingy_ali_product_graph_link_target_is_external($readiness_row)) {
        $blockers[] = 'target_is_external_url';
    }
    if ($anchor_found !== 'yes') {
        $blockers[] = 'anchor_text_not_found';
    }
    if (kingy_ali_product_graph_row_value($quality, 'relationship_evidence_clear') !== 'yes') {
        $blockers[] = 'relationship_evidence_unclear';
    }
    if (kingy_ali_product_graph_row_value($readiness_row, 'review_state', 'unreviewed') !== 'accepted') {
        $blockers[] = 'review_not_accepted';
    }
    if (kingy_ali_product_graph_row_value($readiness_row, 'readiness_bucket') !== 'ready_for_editor_review') {
        $blockers[] = 'not_ready_for_editor_review';
    }
    if ($audit_status === 'blocked_not_insertable') {
        $blockers[] = 'blocked_not_insertable';
    }

    return array_values(array_unique($blockers));
}

function kingy_ali_product_graph_source_context_safe_next_action($audit_status) {
    switch ($audit_status) {
        case 'source_unresolved':
            return __('Resolve the source URL to a WordPress object before considering any future insertion workflow.', 'kingy-ai-launch-intelligence');
        case 'source_content_empty':
            return __('Review the source object content manually; do not create or modify content from this screen.', 'kingy-ai-launch-intelligence');
        case 'target_already_linked':
            return __('Treat this as a possible duplicate and review the existing link context before any future planning.', 'kingy-ai-launch-intelligence');
        case 'anchor_text_not_found':
            return __('Review the source context and choose evidence-backed anchor language before any future planning.', 'kingy-ai-launch-intelligence');
        case 'review_required':
            return __('Complete reviewer evidence review and metadata triage before any future link plan preview.', 'kingy-ai-launch-intelligence');
        case 'source_resolved_context_available':
            return __('Context is available for reviewer inspection only; a separate future workflow would still be required before writes.', 'kingy-ai-launch-intelligence');
        default:
            return __('Keep this row read-only; do not insert links or mutate content from this screen.', 'kingy-ai-launch-intelligence');
    }
}

function kingy_ali_product_graph_source_context_excerpt($plain_content, $anchor_text) {
    $plain_content = preg_replace('/\s+/', ' ', (string) $plain_content);
    $anchor_text = trim((string) $anchor_text);
    if ($plain_content === '' || $anchor_text === '') {
        return '';
    }

    $pos = stripos($plain_content, $anchor_text);
    if ($pos === false) {
        return '';
    }

    $start = max(0, $pos - 120);
    $length = strlen($anchor_text) + 240;
    $excerpt = substr($plain_content, $start, $length);
    if ($start > 0) {
        $excerpt = '...' . $excerpt;
    }
    if ($start + $length < strlen($plain_content)) {
        $excerpt .= '...';
    }

    return sanitize_text_field($excerpt);
}

function kingy_ali_product_graph_source_context_link_evidence($content, $target_url) {
    $content = (string) $content;
    foreach (kingy_ali_product_graph_source_context_url_candidates($target_url) as $candidate) {
        $pos = stripos($content, $candidate);
        if ($pos === false) {
            continue;
        }
        $start = max(0, $pos - 80);
        $excerpt = substr($content, $start, strlen($candidate) + 160);
        $excerpt = wp_strip_all_tags($excerpt);
        return sanitize_text_field(preg_replace('/\s+/', ' ', $excerpt));
    }

    return '';
}

function kingy_ali_product_graph_sort_source_context_audit_rows($a, $b) {
    $status_order = array(
        'source_unresolved' => 0,
        'source_content_empty' => 1,
        'target_already_linked' => 2,
        'anchor_text_not_found' => 3,
        'review_required' => 4,
        'source_resolved_context_available' => 5,
        'blocked_not_insertable' => 6,
    );
    $a_status = kingy_ali_product_graph_row_value($a, 'audit_status');
    $b_status = kingy_ali_product_graph_row_value($b, 'audit_status');
    $a_weight = isset($status_order[$a_status]) ? $status_order[$a_status] : 9;
    $b_weight = isset($status_order[$b_status]) ? $status_order[$b_status] : 9;
    if ($a_weight !== $b_weight) {
        return $a_weight - $b_weight;
    }

    return strcmp(kingy_ali_product_graph_row_value($a, 'source_title'), kingy_ali_product_graph_row_value($b, 'source_title'));
}

function kingy_ali_product_graph_requested_source_context_audit_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_source_context_audit_id', 120));
}

function kingy_ali_product_graph_find_source_context_audit_row($rows, $audit_id) {
    $audit_id = kingy_ali_product_graph_sanitize_row_id($audit_id);
    if ($audit_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'audit_id') === $audit_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_requested_opportunity_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_opportunity_id', 120));
}

function kingy_ali_product_graph_find_opportunity_by_id($rows, $opportunity_id) {
    $opportunity_id = kingy_ali_product_graph_sanitize_row_id($opportunity_id);
    if ($opportunity_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'opportunity_id') === $opportunity_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_opportunity_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'opportunities',
    );

    foreach (array('kingy_pg_opp_type', 'kingy_pg_opp_source_type', 'kingy_pg_opp_confidence', 'kingy_pg_opp_review_state', 'kingy_pg_opp_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_opp_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_opportunity_detail_url($opportunity_id) {
    $args = kingy_ali_product_graph_opportunity_filter_query_args();
    $args['kingy_pg_opportunity_id'] = kingy_ali_product_graph_sanitize_row_id($opportunity_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_opportunity_back_url() {
    return add_query_arg(kingy_ali_product_graph_opportunity_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_array_contains_value($value, $needle) {
    if ($needle === '') {
        return false;
    }
    if (is_array($value)) {
        foreach ($value as $item) {
            if (is_array($item)) {
                if (kingy_ali_product_graph_array_contains_value($item, $needle)) {
                    return true;
                }
                continue;
            }
            if ((string) $item === $needle) {
                return true;
            }
        }
        return false;
    }

    return is_scalar($value) && (string) $value === $needle;
}

function kingy_ali_product_graph_opportunity_detail_context($opportunity) {
    $source_node_id = kingy_ali_product_graph_row_value($opportunity, 'source_node');
    $target_candidate = kingy_ali_product_graph_row_value($opportunity, 'target_candidate');
    $nodes = kingy_ali_product_graph_read_json('nodes', array());
    $edges = kingy_ali_product_graph_read_json('edges', array());
    $resolver = kingy_ali_product_graph_read_json('resolver', array());
    $unresolved_queue = kingy_ali_product_graph_read_json('unresolved_queue', array());
    $source_node = array();
    $target_node = array();
    $outgoing_edges = array();
    $incoming_edges = array();
    $resolver_records = array();
    $unresolved_records = array();

    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $node_key = kingy_ali_product_graph_row_value($node, 'node_key');
        if ($node_key === $source_node_id) {
            $source_node = $node;
        }
        if ($target_candidate !== '' && $node_key === $target_candidate) {
            $target_node = $node;
        }
    }

    foreach ($edges as $edge) {
        if (!is_array($edge)) {
            continue;
        }
        if (kingy_ali_product_graph_row_value($edge, 'source') === $source_node_id) {
            $outgoing_edges[] = $edge;
        }
        if (kingy_ali_product_graph_row_value($edge, 'target') === $source_node_id) {
            $incoming_edges[] = $edge;
        }
    }

    foreach ($resolver as $row) {
        if (!is_array($row)) {
            continue;
        }
        $matches_source = kingy_ali_product_graph_row_value($row, 'entity_key') === $source_node_id
            || kingy_ali_product_graph_array_contains_value(isset($row['candidate_source_keys']) ? $row['candidate_source_keys'] : array(), $source_node_id);
        $matches_target = $target_candidate !== ''
            && (
                kingy_ali_product_graph_row_value($row, 'entity_key') === $target_candidate
                || kingy_ali_product_graph_row_value($row, 'normalized_url') === $target_candidate
                || kingy_ali_product_graph_row_value($row, 'input_url') === $target_candidate
            );
        if ($matches_source || $matches_target) {
            $resolver_records[] = $row;
        }
    }

    foreach ($unresolved_queue as $row) {
        if (!is_array($row)) {
            continue;
        }
        $matches_source = kingy_ali_product_graph_array_contains_value(isset($row['candidate_source_keys']) ? $row['candidate_source_keys'] : array(), $source_node_id);
        $matches_target = $target_candidate !== ''
            && (
                kingy_ali_product_graph_row_value($row, 'normalized_url') === $target_candidate
                || kingy_ali_product_graph_row_value($row, 'input_url') === $target_candidate
            );
        if ($matches_source || $matches_target) {
            $unresolved_records[] = $row;
        }
    }

    return array(
        'source_node' => $source_node,
        'target_node' => $target_node,
        'outgoing_edges' => $outgoing_edges,
        'incoming_edges' => $incoming_edges,
        'resolver_records' => $resolver_records,
        'unresolved_records' => $unresolved_records,
    );
}

function kingy_ali_product_graph_opportunity_review_guidance($opportunity) {
    $type = kingy_ali_product_graph_row_value($opportunity, 'opportunity_type');
    $questions = array();
    $safe_next_actions = array(
        __('Review source evidence and existing graph context before changing any reviewer metadata.', 'kingy-ai-launch-intelligence'),
        __('If needed, use the existing review overlay workflow to record reviewer metadata only.', 'kingy-ai-launch-intelligence'),
        __('Refresh the graph snapshot later after source data is improved.', 'kingy-ai-launch-intelligence'),
        __('Do not insert links, create pages, or edit WordPress content from this screen.', 'kingy-ai-launch-intelligence'),
    );

    switch ($type) {
        case 'missing_tool_company_relationship':
            $questions[] = __('Is there an official source tying this tool to a company?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Does the existing company node already represent the maker, owner, or publisher?', 'kingy-ai-launch-intelligence');
            break;
        case 'missing_launch_company_relationship':
            $questions[] = __('Which company is officially responsible for this launch?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Does the launch evidence support a direct company relationship or only an editorial mention?', 'kingy-ai-launch-intelligence');
            break;
        case 'missing_launch_tool_relationship':
            $questions[] = __('Which tool does this launch actually profile?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Is the target tool already modeled, or does the graph need a future source-backed tool node?', 'kingy-ai-launch-intelligence');
            break;
        case 'missing_tool_latest_launch':
            $questions[] = __('Which launch is the most current source-backed launch for this tool?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Would an older launch create stale or misleading graph context?', 'kingy-ai-launch-intelligence');
            break;
        case 'related_video_opportunity':
            $questions[] = __('Is there an official demo, YouTube video, or creator video asset that should be modeled?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Is the video source useful enough for reviewers, buyers, or sponsor paths?', 'kingy-ai-launch-intelligence');
            break;
        case 'related_guide_opportunity':
            $questions[] = __('Is there an existing Kingy guide or article that should be modeled as the target?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Would the guide relationship help users understand use cases, category fit, or implementation?', 'kingy-ai-launch-intelligence');
            break;
        case 'related_calculator_opportunity':
            $questions[] = __('Is there an existing Kingy calculator that belongs with this node?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Would the calculator relationship support a useful evaluation or sponsor path?', 'kingy-ai-launch-intelligence');
            break;
        case 'page_node_resolver_candidate':
            $questions[] = __('Should this URL become a typed node or stay unresolved?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Is there enough local evidence to classify the URL without live fetching?', 'kingy-ai-launch-intelligence');
            break;
        case 'sponsor_path_opportunity':
            $questions[] = __('Is there a real sponsor, creator, or client path connected to this node?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Would modeling this relationship help route commercial intent without forcing an editorial link?', 'kingy-ai-launch-intelligence');
            break;
        case 'newsletter_distribution_opportunity':
            $questions[] = __('Which Radar or newsletter post, if any, distributed this launch?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Is the distribution relationship source-backed or only an editorial candidate?', 'kingy-ai-launch-intelligence');
            break;
        case 'internal_link_recommendation_candidate':
            $questions[] = __('What is the safest next page for a reader from this launch?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Would the recommendation create helpful context without duplicate or forced linking?', 'kingy-ai-launch-intelligence');
            break;
        case 'model_inventory_gap':
            $questions[] = __('What source should provide model records?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Should the model inventory remain blocked until a dedicated source export exists?', 'kingy-ai-launch-intelligence');
            break;
        default:
            $questions[] = __('What source evidence would make this relationship useful?', 'kingy-ai-launch-intelligence');
            $questions[] = __('Should this remain a candidate until a reviewer can confirm the relationship?', 'kingy-ai-launch-intelligence');
            break;
    }

    return array(
        'questions' => $questions,
        'safe_next_actions' => $safe_next_actions,
    );
}

function kingy_ali_product_graph_sort_opportunities($a, $b) {
    $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2);
    $a_priority = isset($priority_order[$a['priority']]) ? $priority_order[$a['priority']] : 9;
    $b_priority = isset($priority_order[$b['priority']]) ? $priority_order[$b['priority']] : 9;

    if ($a_priority !== $b_priority) {
        return $a_priority - $b_priority;
    }

    $type_compare = strcmp($a['opportunity_type'], $b['opportunity_type']);
    if ($type_compare !== 0) {
        return $type_compare;
    }

    return strcmp($a['source_title'], $b['source_title']);
}

function kingy_ali_product_graph_review_save_row_is_valid($tab, $row_type, $row_id) {
    $tab = sanitize_key($tab);
    $row_type = sanitize_key($row_type);
    $row_id = kingy_ali_product_graph_sanitize_row_id($row_id);

    if ($tab === 'link_recommendations' && $row_type === 'link_recommendation') {
        return (bool) preg_match('/^link-rec:[a-f0-9]{16}$/', $row_id);
    }

    return kingy_ali_product_graph_row_exists($tab, $row_id);
}

function kingy_ali_product_graph_row_exists($tab, $row_id) {
    $tabs = kingy_ali_product_graph_tabs();
    $tab = sanitize_key($tab);
    $row_id = kingy_ali_product_graph_sanitize_row_id($row_id);

    if ($tab === 'work_queue') {
        $queue = kingy_ali_product_graph_work_queue_data();
        $rows = isset($queue['rows']) && is_array($queue['rows']) ? $queue['rows'] : array();
        return (bool) kingy_ali_product_graph_find_work_queue_row($rows, $row_id);
    }

    if ($tab === 'link_recommendations') {
        $recommendations = kingy_ali_product_graph_link_recommendations_data();
        $rows = isset($recommendations['rows']) && is_array($recommendations['rows']) ? $recommendations['rows'] : array();
        return (bool) kingy_ali_product_graph_find_link_recommendation_row($rows, $row_id);
    }

    if (!isset($tabs[$tab]['dataset'])) {
        return false;
    }

    $rows = kingy_ali_product_graph_read_json($tabs[$tab]['dataset'], array());
    if (!is_array($rows)) {
        return false;
    }

    foreach ($rows as $row) {
        if (is_array($row) && isset($row['review_row_id']) && kingy_ali_product_graph_sanitize_row_id($row['review_row_id']) === $row_id) {
            return true;
        }
    }

    return false;
}

function kingy_ali_render_product_graph_review_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $tabs = kingy_ali_product_graph_tabs();
    $active_tab = kingy_ali_product_graph_active_tab($tabs);
    $summary = kingy_ali_product_graph_read_json('summary', array());
    ?>
    <div class="wrap kingy-ali-product-graph-review">
        <h1><?php esc_html_e('Kingy Product Graph Review', 'kingy-ai-launch-intelligence'); ?></h1>

        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Product Graph review metadata overlay. Review states and notes are saved separately from graph artifacts; internal links are not inserted and no WordPress content is changed from this screen.', 'kingy-ai-launch-intelligence'); ?>
            </p>
        </div>

        <?php kingy_ali_product_graph_render_review_save_notice(); ?>

        <?php if (!kingy_ali_product_graph_json_file('summary')) : ?>
            <?php kingy_ali_product_graph_render_missing_artifacts_notice(); ?>
        <?php endif; ?>

        <?php if (kingy_ali_product_graph_model_inventory_gap($summary)) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Model inventory is currently blocked: no local kingy_ai_model records are loaded in the review dataset.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        <?php endif; ?>

        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $key => $tab) : ?>
                <?php $url = add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => $key), admin_url('admin.php')); ?>
                <a class="nav-tab <?php echo $active_tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <?php kingy_ali_product_graph_render_tab($active_tab, $tabs[$active_tab], $summary); ?>
    </div>
    <?php
}

function kingy_ali_product_graph_tabs() {
    return array(
        'summary' => array(
            'label' => __('Summary', 'kingy-ai-launch-intelligence'),
            'dataset' => 'summary',
        ),
        'review_dashboard' => array(
            'label' => __('Review Dashboard', 'kingy-ai-launch-intelligence'),
        ),
        'reviewer_progress' => array(
            'label' => __('Reviewer Progress', 'kingy-ai-launch-intelligence'),
        ),
        'work_queue' => array(
            'label' => __('Work Queue', 'kingy-ai-launch-intelligence'),
        ),
        'link_recommendations' => array(
            'label' => __('Link Recommendations', 'kingy-ai-launch-intelligence'),
        ),
        'link_review_batches' => array(
            'label' => __('Link Review Batches', 'kingy-ai-launch-intelligence'),
        ),
        'link_batch_progress' => array(
            'label' => __('Batch Progress', 'kingy-ai-launch-intelligence'),
        ),
        'stage3_closeout' => array(
            'label' => __('Stage 3 Closeout', 'kingy-ai-launch-intelligence'),
        ),
        'source_context_audit' => array(
            'label' => __('Source Context Audit', 'kingy-ai-launch-intelligence'),
        ),
        'link_readiness' => array(
            'label' => __('Link Readiness', 'kingy-ai-launch-intelligence'),
        ),
        'link_plan_preview' => array(
            'label' => __('Link Plan Preview', 'kingy-ai-launch-intelligence'),
        ),
        'graph_health' => array(
            'label' => __('Graph Health', 'kingy-ai-launch-intelligence'),
        ),
        'repair_planner' => array(
            'label' => __('Repair Planner', 'kingy-ai-launch-intelligence'),
        ),
        'evidence_pack' => array(
            'label' => __('Evidence Pack', 'kingy-ai-launch-intelligence'),
        ),
        'opportunities' => array(
            'label' => __('Opportunities', 'kingy-ai-launch-intelligence'),
        ),
        'review_overlay' => array(
            'label' => __('Review Overlay', 'kingy-ai-launch-intelligence'),
        ),
        'overlay_cleanup' => array(
            'label' => __('Overlay Cleanup', 'kingy-ai-launch-intelligence'),
        ),
        'nodes' => array(
            'label' => __('Nodes', 'kingy-ai-launch-intelligence'),
            'dataset' => 'nodes',
            'columns' => array('node_key', 'entity_type', 'node_kind', 'title', 'url', 'status', 'review_state', 'suggested_review_state'),
            'filter_fields' => array('entity_type', 'node_kind', 'status', 'review_state'),
        ),
        'edges' => array(
            'label' => __('Edges', 'kingy-ai-launch-intelligence'),
            'dataset' => 'edges',
            'columns' => array('edge_id', 'edge_type', 'source_title', 'target_title', 'field', 'confidence_class', 'status', 'suggested_review_state'),
            'filter_fields' => array('edge_type', 'confidence_class', 'status', 'review_state'),
        ),
        'resolver' => array(
            'label' => __('URL Resolver', 'kingy-ai-launch-intelligence'),
            'dataset' => 'resolver',
            'columns' => array('normalized_url', 'entity_key', 'entity_type', 'node_kind', 'canonical_resolution_status', 'resolution_source', 'review_state'),
            'filter_fields' => array('entity_type', 'node_kind', 'canonical_resolution_status', 'resolution_source'),
        ),
        'unresolved_queue' => array(
            'label' => __('Unresolved Queue', 'kingy-ai-launch-intelligence'),
            'dataset' => 'unresolved_queue',
            'columns' => array('normalized_url', 'candidate_source_kinds', 'candidate_fields', 'canonical_resolution_status', 'review_note', 'review_only', 'insertable'),
            'filter_fields' => array('canonical_resolution_status', 'suggested_review_state'),
        ),
        'model_inventory' => array(
            'label' => __('Model Inventory', 'kingy-ai-launch-intelligence'),
            'dataset' => 'model_inventory',
            'columns' => array('status', 'model_records_loaded', 'plugin_cpt_registered', 'public_model_count', 'authenticated_model_count', 'review_state', 'notes'),
            'filter_fields' => array('status', 'review_state'),
        ),
        'exports' => array(
            'label' => __('Exports', 'kingy-ai-launch-intelligence'),
            'dataset' => 'export_download',
            'columns' => array('artifact_family', 'format', 'row_count', 'path'),
            'filter_fields' => array('artifact_family', 'format'),
        ),
    );
}

function kingy_ali_product_graph_active_tab($tabs) {
    $requested = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_tab', 80));
    return isset($tabs[$requested]) ? $requested : 'summary';
}

function kingy_ali_product_graph_render_tab($active_tab, $tab, $summary) {
    if ($active_tab === 'summary') {
        kingy_ali_product_graph_render_summary($summary);
        return;
    }

    if ($active_tab === 'review_overlay') {
        kingy_ali_product_graph_render_review_overlay_tab();
        return;
    }

    if ($active_tab === 'overlay_cleanup') {
        kingy_ali_product_graph_render_overlay_cleanup_tab();
        return;
    }

    if ($active_tab === 'review_dashboard') {
        kingy_ali_product_graph_render_review_dashboard_tab();
        return;
    }

    if ($active_tab === 'reviewer_progress') {
        kingy_ali_product_graph_render_reviewer_progress_tab();
        return;
    }

    if ($active_tab === 'work_queue') {
        kingy_ali_product_graph_render_work_queue_tab();
        return;
    }

    if ($active_tab === 'link_recommendations') {
        kingy_ali_product_graph_render_link_recommendations_tab();
        return;
    }

    if ($active_tab === 'link_review_batches') {
        kingy_ali_product_graph_render_link_review_batches_tab();
        return;
    }

    if ($active_tab === 'link_batch_progress') {
        kingy_ali_product_graph_render_link_batch_progress_tab();
        return;
    }

    if ($active_tab === 'stage3_closeout') {
        kingy_ali_product_graph_render_stage3_closeout_tab();
        return;
    }

    if ($active_tab === 'source_context_audit') {
        kingy_ali_product_graph_render_source_context_audit_tab();
        return;
    }

    if ($active_tab === 'link_readiness') {
        kingy_ali_product_graph_render_link_readiness_tab();
        return;
    }

    if ($active_tab === 'link_plan_preview') {
        kingy_ali_product_graph_render_link_plan_preview_tab();
        return;
    }

    if ($active_tab === 'graph_health') {
        kingy_ali_product_graph_render_health_tab();
        return;
    }

    if ($active_tab === 'repair_planner') {
        kingy_ali_product_graph_render_repair_planner_tab();
        return;
    }

    if ($active_tab === 'evidence_pack') {
        kingy_ali_product_graph_render_evidence_pack_tab();
        return;
    }

    if ($active_tab === 'opportunities') {
        kingy_ali_product_graph_render_opportunities_tab();
        return;
    }

    $rows = kingy_ali_product_graph_read_json($tab['dataset'], array());
    if (!$rows) {
        kingy_ali_product_graph_render_missing_dataset($tab['dataset']);
        return;
    }

    $rows = kingy_ali_product_graph_filter_rows($rows, isset($tab['filter_fields']) ? $tab['filter_fields'] : array());
    kingy_ali_product_graph_render_dataset_toolbar($active_tab, $tab, count($rows));
    kingy_ali_product_graph_render_table($rows, $tab['columns'], $active_tab, $tab['dataset']);
}

function kingy_ali_product_graph_render_summary($summary) {
    if (!$summary) {
        kingy_ali_product_graph_render_missing_dataset('summary');
        return;
    }

    $dataset_counts = isset($summary['dataset_counts']) && is_array($summary['dataset_counts']) ? $summary['dataset_counts'] : array();
    $source_counts = isset($summary['source_counts']) && is_array($summary['source_counts']) ? $summary['source_counts'] : array();
    $guardrails = isset($summary['guardrails']) && is_array($summary['guardrails']) ? $summary['guardrails'] : array();
    ?>
    <h2><?php esc_html_e('Summary', 'kingy-ai-launch-intelligence'); ?></h2>
    <div class="kingy-ali-admin-cards">
        <?php foreach ($dataset_counts as $label => $count) : ?>
            <?php kingy_ali_admin_stat_card(ucwords(str_replace('_', ' ', (string) $label)), absint($count)); ?>
        <?php endforeach; ?>
    </div>

    <h3><?php esc_html_e('Source Counts', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($source_counts); ?>

    <h3><?php esc_html_e('Guardrails', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($guardrails); ?>

    <?php kingy_ali_product_graph_render_review_overlay_summary(); ?>
    <?php kingy_ali_product_graph_render_download_list(); ?>
    <?php kingy_ali_product_graph_render_review_overlay_downloads(); ?>
    <?php
}

function kingy_ali_product_graph_render_review_overlay_summary() {
    $summary = kingy_ali_product_graph_review_overlay_summary();
    ?>
    <h3><?php esc_html_e('Review Overlay Summary', 'kingy-ai-launch-intelligence'); ?></h3>
    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Saved Review Records', 'kingy-ai-launch-intelligence'), absint($summary['total_saved_review_records'])); ?>
        <?php kingy_ali_admin_stat_card(__('Review States Used', 'kingy-ai-launch-intelligence'), count(array_filter($summary['counts_by_state']))); ?>
        <?php kingy_ali_admin_stat_card(__('Reviewed Row Types', 'kingy-ai-launch-intelligence'), count(array_filter($summary['counts_by_row_type']))); ?>
    </div>
    <p>
        <strong><?php esc_html_e('Latest reviewed timestamp:', 'kingy-ai-launch-intelligence'); ?></strong>
        <?php echo esc_html($summary['latest_reviewed_at_utc'] !== '' ? $summary['latest_reviewed_at_utc'] : __('No saved review metadata yet.', 'kingy-ai-launch-intelligence')); ?>
    </p>
    <h4><?php esc_html_e('Counts by State', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table($summary['counts_by_state']); ?>
    <h4><?php esc_html_e('Counts by Row Type', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table($summary['counts_by_row_type']); ?>
    <p>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'review_overlay'), admin_url('admin.php'))); ?>">
            <?php esc_html_e('Open Review Overlay', 'kingy-ai-launch-intelligence'); ?>
        </a>
    </p>
    <?php
}

function kingy_ali_product_graph_render_review_overlay_tab() {
    $rows = kingy_ali_product_graph_filter_review_overlay_rows(kingy_ali_product_graph_flatten_review_overlay());
    ?>
    <h2><?php esc_html_e('Review Overlay', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('This tab lists reviewer metadata only. Graph artifacts and WordPress content remain unchanged.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php kingy_ali_product_graph_render_review_overlay_filters(count($rows)); ?>

    <?php if (!$rows) : ?>
        <p><?php esc_html_e('No saved review metadata matches the current filters.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php return; ?>
    <?php endif; ?>

    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Row Type', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Source Row ID', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Review State', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Reviewer', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Reviewed Timestamp', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Reviewer Note', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Source Artifact', 'kingy-ai-launch-intelligence'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row['row_type']); ?></td>
                    <td><code><?php echo esc_html($row['review_row_id']); ?></code></td>
                    <td><?php echo esc_html($row['review_state']); ?></td>
                    <td><?php echo esc_html($row['reviewer_display_name'] !== '' ? $row['reviewer_display_name'] : $row['reviewer_login']); ?></td>
                    <td><?php echo esc_html($row['reviewed_at_utc']); ?></td>
                    <td><?php echo esc_html($row['reviewer_notes']); ?></td>
                    <td><code><?php echo esc_html($row['source_artifact_id']); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_overlay_cleanup_tab() {
    $cleanup = kingy_ali_product_graph_overlay_cleanup_data();
    $rows = isset($cleanup['rows']) && is_array($cleanup['rows']) ? $cleanup['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_overlay_cleanup_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_overlay_cleanup_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_overlay_cleanup_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Review Overlay Cleanup', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only diagnostics for reviewer metadata quality. This tab does not delete overlay records, approve graph changes, insert links, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Overlay Records', 'kingy-ai-launch-intelligence'), isset($cleanup['total_overlay_records']) ? absint($cleanup['total_overlay_records']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Source Exists', 'kingy-ai-launch-intelligence'), isset($cleanup['source_rows_existing']) ? absint($cleanup['source_rows_existing']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Missing/Stale Source', 'kingy-ai-launch-intelligence'), isset($cleanup['source_rows_missing_or_stale']) ? absint($cleanup['source_rows_missing_or_stale']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Empty Notes', 'kingy-ai-launch-intelligence'), isset($cleanup['empty_note_records']) ? absint($cleanup['empty_note_records']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Follow-up States', 'kingy-ai-launch-intelligence'), isset($cleanup['followup_state_records']) ? absint($cleanup['followup_state_records']) : 0); ?>
    </div>

    <h3><?php esc_html_e('Counts by Source Status', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($cleanup['counts_by_source_status']) ? $cleanup['counts_by_source_status'] : array()); ?>

    <h3><?php esc_html_e('Counts by Row Type', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($cleanup['counts_by_row_type']) ? $cleanup['counts_by_row_type'] : array()); ?>

    <h3><?php esc_html_e('Counts by Review State', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($cleanup['counts_by_review_state']) ? $cleanup['counts_by_review_state'] : array()); ?>

    <?php kingy_ali_product_graph_render_overlay_cleanup_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_overlay_cleanup_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Cleanup Diagnostics Queue', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_overlay_cleanup_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_overlay_cleanup_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_overlay_cleanup_export_links() {
    ?>
    <h3><?php esc_html_e('Overlay Cleanup Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current reviewer metadata diagnostics only. They do not read arbitrary files and do not mutate overlay records.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_overlay_cleanup_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_overlay_cleanup_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download overlay cleanup %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_overlay_cleanup_rows($rows) {
    $row_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_state', 80));
    $source_status = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_source_status', 80));
    $note_present = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_note_present', 20));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_cleanup_search', 120));

    if ($row_type === '' && $review_state === '' && $source_status === '' && $note_present === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($row_type !== '' && kingy_ali_product_graph_row_value($row, 'row_type') !== $row_type) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($source_status !== '' && kingy_ali_product_graph_row_value($row, 'source_status') !== $source_status) {
            continue;
        }
        if ($note_present !== '' && kingy_ali_product_graph_row_value($row, 'note_present') !== $note_present) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'cleanup_id'),
                kingy_ali_product_graph_row_value($row, 'row_type'),
                kingy_ali_product_graph_row_value($row, 'review_row_id'),
                kingy_ali_product_graph_row_value($row, 'review_state'),
                kingy_ali_product_graph_row_value($row, 'reviewer'),
                kingy_ali_product_graph_row_value($row, 'source_artifact_id'),
                kingy_ali_product_graph_row_value($row, 'recommended_safe_next_action'),
                kingy_ali_product_graph_row_value($row, 'reviewer_notes'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_overlay_cleanup_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_overlay_cleanup_filters($filtered_rows, $all_rows) {
    $row_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_state', 80));
    $source_status = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_source_status', 80));
    $note_present = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_cleanup_note_present', 20));
    $search = kingy_ali_product_graph_get_value('kingy_pg_cleanup_search', 120);
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="overlay_cleanup">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_cleanup_type', __('Row type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_overlay_cleanup_values($all_rows, 'row_type'), $row_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_cleanup_state', __('Review state', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_overlay_cleanup_values($all_rows, 'review_state'), $review_state); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_cleanup_source_status', __('Source status', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_overlay_cleanup_values($all_rows, 'source_status'), $source_status); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Note present', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_cleanup_note_present">
                <option value=""><?php esc_html_e('Any note state', 'kingy-ai-launch-intelligence'); ?></option>
                <option value="yes" <?php selected($note_present, 'yes'); ?>><?php esc_html_e('Yes', 'kingy-ai-launch-intelligence'); ?></option>
                <option value="no" <?php selected($note_present, 'no'); ?>><?php esc_html_e('No', 'kingy-ai-launch-intelligence'); ?></option>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search overlay cleanup rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_cleanup_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search row ID, reviewer, note', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('overlay_cleanup')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered cleanup rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_overlay_cleanup_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'overlay_cleanup',
    );

    foreach (array('kingy_pg_cleanup_type', 'kingy_pg_cleanup_state', 'kingy_pg_cleanup_source_status', 'kingy_pg_cleanup_note_present', 'kingy_pg_cleanup_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_cleanup_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_requested_overlay_cleanup_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_overlay_cleanup_id', 120));
}

function kingy_ali_product_graph_overlay_cleanup_detail_url($cleanup_id) {
    $args = kingy_ali_product_graph_overlay_cleanup_filter_query_args();
    $args['kingy_pg_overlay_cleanup_id'] = kingy_ali_product_graph_sanitize_row_id($cleanup_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_overlay_cleanup_back_url() {
    return add_query_arg(kingy_ali_product_graph_overlay_cleanup_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_find_overlay_cleanup_row($rows, $cleanup_id) {
    $cleanup_id = kingy_ali_product_graph_sanitize_row_id($cleanup_id);
    if ($cleanup_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'cleanup_id') === $cleanup_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_render_overlay_cleanup_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Overlay Cleanup Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No overlay cleanup row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_overlay_cleanup_back_url()); ?>"><?php esc_html_e('Back to overlay cleanup', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It plans cleanup only; it does not delete overlay records or mutate WordPress content, graph artifacts, links, routes, SEO, schema, or redirects.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_overlay_cleanup_back_url()); ?>"><?php esc_html_e('Back to overlay cleanup', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('review_overlay', array('kingy_pg_overlay_type' => kingy_ali_product_graph_row_value($row, 'row_type'), 'kingy_pg_overlay_search' => kingy_ali_product_graph_row_value($row, 'review_row_id')))); ?>"><?php esc_html_e('Open in Review Overlay', 'kingy-ai-launch-intelligence'); ?></a>
    </p>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'cleanup_id' => kingy_ali_product_graph_row_value($row, 'cleanup_id'),
        'row_type' => kingy_ali_product_graph_row_value($row, 'row_type'),
        'review_row_id' => kingy_ali_product_graph_row_value($row, 'review_row_id'),
        'review_state' => kingy_ali_product_graph_row_value($row, 'review_state'),
        'reviewer' => kingy_ali_product_graph_row_value($row, 'reviewer'),
        'reviewed_at_utc' => kingy_ali_product_graph_row_value($row, 'reviewed_at_utc'),
        'source_artifact_id' => kingy_ali_product_graph_row_value($row, 'source_artifact_id'),
        'source_status' => kingy_ali_product_graph_row_value($row, 'source_status'),
        'note_present' => kingy_ali_product_graph_row_value($row, 'note_present'),
        'followup_state' => kingy_ali_product_graph_row_value($row, 'followup_state'),
        'very_old_review' => kingy_ali_product_graph_row_value($row, 'very_old_review'),
        'reviewed_age_days' => kingy_ali_product_graph_row_value($row, 'reviewed_age_days'),
        'recommended_safe_next_action' => kingy_ali_product_graph_row_value($row, 'recommended_safe_next_action'),
        'reviewer_notes' => kingy_ali_product_graph_row_value($row, 'reviewer_notes'),
    )); ?>
    <?php
}

function kingy_ali_product_graph_render_overlay_cleanup_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No overlay cleanup rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_overlay_cleanup_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_overlay_cleanup_detail_url(kingy_ali_product_graph_row_value($row, 'cleanup_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('cleanup_id', 'review_row_id', 'source_artifact_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_review_dashboard_tab() {
    $dashboard = kingy_ali_product_graph_review_dashboard_data();
    $health = isset($dashboard['graph_health_snapshot']) && is_array($dashboard['graph_health_snapshot']) ? $dashboard['graph_health_snapshot'] : array();
    $repair = isset($dashboard['repair_priority_snapshot']) && is_array($dashboard['repair_priority_snapshot']) ? $dashboard['repair_priority_snapshot'] : array();
    $opportunities = isset($dashboard['opportunity_snapshot']) && is_array($dashboard['opportunity_snapshot']) ? $dashboard['opportunity_snapshot'] : array();
    $evidence = isset($dashboard['evidence_coverage_snapshot']) && is_array($dashboard['evidence_coverage_snapshot']) ? $dashboard['evidence_coverage_snapshot'] : array();
    $overlay = isset($dashboard['review_overlay_progress']) && is_array($dashboard['review_overlay_progress']) ? $dashboard['review_overlay_progress'] : array();
    ?>
    <h2><?php esc_html_e('Graph Review Dashboard', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only reviewer dashboard assembled from current Product Graph health, repair planner, evidence pack, opportunity, and review overlay data. This tab does not approve rows, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Nodes', 'kingy-ai-launch-intelligence'), isset($health['total_nodes']) ? absint($health['total_nodes']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Edges', 'kingy-ai-launch-intelligence'), isset($health['total_edges']) ? absint($health['total_edges']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Opportunities', 'kingy-ai-launch-intelligence'), isset($opportunities['opportunity_count']) ? absint($opportunities['opportunity_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Evidence Rows', 'kingy-ai-launch-intelligence'), isset($evidence['evidence_pack_row_count']) ? absint($evidence['evidence_pack_row_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Saved Reviews', 'kingy-ai-launch-intelligence'), isset($overlay['saved_review_overlay_count']) ? absint($overlay['saved_review_overlay_count']) : 0); ?>
    </div>

    <?php kingy_ali_product_graph_render_review_dashboard_export_links(); ?>

    <h3><?php esc_html_e('Graph Health Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($health); ?>
    <?php kingy_ali_product_graph_render_review_dashboard_section_link('graph_health'); ?>

    <h3><?php esc_html_e('Repair Priority Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'repair_planner_batch_count' => isset($repair['repair_planner_batch_count']) ? $repair['repair_planner_batch_count'] : 0,
    )); ?>
    <h4><?php esc_html_e('Repair Counts by Priority', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($repair['counts_by_priority']) ? $repair['counts_by_priority'] : array()); ?>
    <h4><?php esc_html_e('Repair Counts by Family', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($repair['counts_by_family']) ? $repair['counts_by_family'] : array()); ?>
    <?php kingy_ali_product_graph_render_review_dashboard_section_link('repair_planner'); ?>

    <h3><?php esc_html_e('Opportunity Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'opportunity_count' => isset($opportunities['opportunity_count']) ? $opportunities['opportunity_count'] : 0,
    )); ?>
    <h4><?php esc_html_e('Opportunity Counts by Priority', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($opportunities['counts_by_priority']) ? $opportunities['counts_by_priority'] : array()); ?>
    <h4><?php esc_html_e('Opportunity Counts by Confidence', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($opportunities['counts_by_confidence']) ? $opportunities['counts_by_confidence'] : array()); ?>
    <?php kingy_ali_product_graph_render_review_dashboard_section_link('opportunities'); ?>

    <h3><?php esc_html_e('Evidence Coverage Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'evidence_pack_row_count' => isset($evidence['evidence_pack_row_count']) ? $evidence['evidence_pack_row_count'] : 0,
    )); ?>
    <h4><?php esc_html_e('Evidence Counts by Type', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($evidence['counts_by_type']) ? $evidence['counts_by_type'] : array()); ?>
    <?php kingy_ali_product_graph_render_review_dashboard_section_link('evidence_pack'); ?>

    <h3><?php esc_html_e('Review Overlay Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'saved_review_overlay_count' => isset($overlay['saved_review_overlay_count']) ? $overlay['saved_review_overlay_count'] : 0,
        'needs_followup_count' => isset($overlay['needs_followup_count']) ? $overlay['needs_followup_count'] : 0,
        'latest_reviewed_at_utc' => isset($overlay['latest_reviewed_at_utc']) ? $overlay['latest_reviewed_at_utc'] : '',
    )); ?>
    <h4><?php esc_html_e('Counts by Review State', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($overlay['counts_by_review_state']) ? $overlay['counts_by_review_state'] : array()); ?>
    <?php kingy_ali_product_graph_render_review_dashboard_section_link('review_overlay'); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($dashboard['current_blockers']) ? $dashboard['current_blockers'] : array()); ?>

    <h3><?php esc_html_e('Recommended Next Reviewer Actions', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_review_dashboard_actions(isset($dashboard['recommended_next_reviewer_actions']) ? $dashboard['recommended_next_reviewer_actions'] : array()); ?>
    <?php
}

function kingy_ali_product_graph_render_review_dashboard_export_links() {
    ?>
    <h3><?php esc_html_e('Review Dashboard Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only graph review data and reviewer metadata overlay.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_review_dashboard_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_review_dashboard_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download review dashboard %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_review_dashboard_section_link($tab) {
    $links = kingy_ali_product_graph_review_dashboard_links();
    $tab = sanitize_key($tab);
    if (!isset($links[$tab])) {
        return;
    }
    ?>
    <p>
        <a class="button" href="<?php echo esc_url($links[$tab]['url']); ?>">
            <?php echo esc_html(sprintf(__('Open %s', 'kingy-ai-launch-intelligence'), $links[$tab]['label'])); ?>
        </a>
    </p>
    <?php
}

function kingy_ali_product_graph_render_review_dashboard_actions($actions) {
    if (!is_array($actions) || !$actions) {
        echo '<p>' . esc_html__('No recommended reviewer actions are available.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }
    ?>
    <ul style="list-style: disc; margin-left: 20px;">
        <?php foreach ($actions as $action) : ?>
            <li><?php echo esc_html($action); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_reviewer_progress_tab() {
    $progress = kingy_ali_product_graph_reviewer_progress_data();
    $rows = isset($progress['rows']) && is_array($progress['rows']) ? $progress['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_reviewer_progress_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_reviewer_progress_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_reviewer_progress_row($rows, $detail_id) : array();
    $overall = isset($progress['overall_progress']) && is_array($progress['overall_progress']) ? $progress['overall_progress'] : array();
    $opportunity = isset($progress['opportunity_progress']) && is_array($progress['opportunity_progress']) ? $progress['opportunity_progress'] : array();
    $evidence = isset($progress['evidence_coverage_progress']) && is_array($progress['evidence_coverage_progress']) ? $progress['evidence_coverage_progress'] : array();
    ?>
    <h2><?php esc_html_e('Reviewer Progress', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only progress diagnostics from Work Queue, Opportunities, Evidence Pack, and reviewer metadata overlay. This tab does not approve rows, insert links, create pages, delete overlay records, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Work Queue Rows', 'kingy-ai-launch-intelligence'), isset($overall['work_queue_total']) ? absint($overall['work_queue_total']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Work Queue Reviewed', 'kingy-ai-launch-intelligence'), isset($overall['work_queue_reviewed']) ? absint($overall['work_queue_reviewed']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Work Queue Unreviewed', 'kingy-ai-launch-intelligence'), isset($overall['work_queue_unreviewed']) ? absint($overall['work_queue_unreviewed']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Saved Reviews', 'kingy-ai-launch-intelligence'), isset($overall['saved_review_overlay_records']) ? absint($overall['saved_review_overlay_records']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Follow-Ups Remaining', 'kingy-ai-launch-intelligence'), isset($overall['followup_records_remaining']) ? absint($overall['followup_records_remaining']) : 0); ?>
    </div>

    <?php kingy_ali_product_graph_render_reviewer_progress_export_links(); ?>

    <h3><?php esc_html_e('Overall Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($overall); ?>

    <h3><?php esc_html_e('Work Queue Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <h4><?php esc_html_e('Counts by Priority', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['work_queue_progress']['counts_by_priority']) ? $progress['work_queue_progress']['counts_by_priority'] : array()); ?>
    <h4><?php esc_html_e('Counts by Review State', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['work_queue_progress']['counts_by_review_state']) ? $progress['work_queue_progress']['counts_by_review_state'] : array()); ?>
    <h4><?php esc_html_e('Counts by Issue Family', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['work_queue_progress']['counts_by_issue_family']) ? $progress['work_queue_progress']['counts_by_issue_family'] : array()); ?>

    <h3><?php esc_html_e('Opportunity Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'opportunity_total' => isset($opportunity['opportunity_total']) ? $opportunity['opportunity_total'] : 0,
        'opportunity_reviewed' => isset($opportunity['opportunity_reviewed']) ? $opportunity['opportunity_reviewed'] : 0,
        'opportunity_unreviewed' => isset($opportunity['opportunity_unreviewed']) ? $opportunity['opportunity_unreviewed'] : 0,
    )); ?>
    <h4><?php esc_html_e('Opportunity Counts by Review State', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($opportunity['counts_by_review_state']) ? $opportunity['counts_by_review_state'] : array()); ?>

    <h3><?php esc_html_e('Evidence Coverage Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($evidence); ?>

    <h3><?php esc_html_e('Reviewer Activity', 'kingy-ai-launch-intelligence'); ?></h3>
    <h4><?php esc_html_e('Counts by Reviewer', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['reviewer_activity']['counts_by_reviewer']) ? $progress['reviewer_activity']['counts_by_reviewer'] : array()); ?>
    <h4><?php esc_html_e('Counts by State', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['reviewer_activity']['counts_by_state']) ? $progress['reviewer_activity']['counts_by_state'] : array()); ?>

    <h3><?php esc_html_e('Remaining Follow-Ups', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['remaining_followups']) ? $progress['remaining_followups'] : array()); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['current_blockers']) ? $progress['current_blockers'] : array()); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_reviewer_progress_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Progress Drilldown Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_reviewer_progress_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_reviewer_progress_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_reviewer_progress_export_links() {
    ?>
    <h3><?php esc_html_e('Reviewer Progress Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current in-memory reviewer progress diagnostics only. They do not read arbitrary files or mutate graph artifacts, review overlay records, or WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_reviewer_progress_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_reviewer_progress_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download reviewer progress %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_reviewer_progress_rows($rows) {
    $reviewer = sanitize_text_field(kingy_ali_product_graph_get_value('kingy_pg_progress_reviewer', 120));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_state', 80));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_priority', 80));
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_family', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_progress_search', 120));

    if ($reviewer === '' && $review_state === '' && $priority === '' && $issue_family === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($reviewer !== '' && kingy_ali_product_graph_row_value($row, 'reviewer') !== $reviewer) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($priority !== '' && kingy_ali_product_graph_row_value($row, 'priority') !== $priority) {
            continue;
        }
        if ($issue_family !== '' && kingy_ali_product_graph_row_value($row, 'issue_family') !== $issue_family) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'progress_id'),
                kingy_ali_product_graph_row_value($row, 'section'),
                kingy_ali_product_graph_row_value($row, 'metric'),
                kingy_ali_product_graph_row_value($row, 'reviewer'),
                kingy_ali_product_graph_row_value($row, 'review_state'),
                kingy_ali_product_graph_row_value($row, 'priority'),
                kingy_ali_product_graph_row_value($row, 'issue_family'),
                kingy_ali_product_graph_row_value($row, 'detail'),
                kingy_ali_product_graph_row_value($row, 'safe_next_action'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_reviewer_progress_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_reviewer_progress_filters($filtered_rows, $all_rows) {
    $reviewer = sanitize_text_field(kingy_ali_product_graph_get_value('kingy_pg_progress_reviewer', 120));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_state', 80));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_priority', 80));
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_progress_family', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_progress_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="reviewer_progress">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_progress_reviewer', __('Reviewer', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_reviewer_progress_values($all_rows, 'reviewer'), $reviewer); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_progress_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_progress_priority', __('Priority', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_reviewer_progress_values($all_rows, 'priority'), $priority); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_progress_family', __('Issue family', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_reviewer_progress_values($all_rows, 'issue_family'), $issue_family); ?>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search reviewer progress rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_progress_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search progress, reviewer, state, action', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('reviewer_progress')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered progress rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_reviewer_progress_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'reviewer_progress',
    );

    foreach (array('kingy_pg_progress_reviewer', 'kingy_pg_progress_state', 'kingy_pg_progress_priority', 'kingy_pg_progress_family', 'kingy_pg_progress_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_progress_search' || $key === 'kingy_pg_progress_reviewer' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_requested_reviewer_progress_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_progress_id', 120));
}

function kingy_ali_product_graph_reviewer_progress_detail_url($progress_id) {
    $args = kingy_ali_product_graph_reviewer_progress_filter_query_args();
    $args['kingy_pg_progress_id'] = kingy_ali_product_graph_sanitize_row_id($progress_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_reviewer_progress_back_url() {
    return add_query_arg(kingy_ali_product_graph_reviewer_progress_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_find_reviewer_progress_row($rows, $progress_id) {
    $progress_id = kingy_ali_product_graph_sanitize_row_id($progress_id);
    if ($progress_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'progress_id') === $progress_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_render_reviewer_progress_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Reviewer Progress Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No reviewer progress row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_reviewer_progress_back_url()); ?>"><?php esc_html_e('Back to reviewer progress', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not delete overlay records, approve rows, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p><a class="button" href="<?php echo esc_url(kingy_ali_product_graph_reviewer_progress_back_url()); ?>"><?php esc_html_e('Back to reviewer progress', 'kingy-ai-launch-intelligence'); ?></a></p>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'progress_id' => kingy_ali_product_graph_row_value($row, 'progress_id'),
        'section' => kingy_ali_product_graph_row_value($row, 'section'),
        'metric' => kingy_ali_product_graph_row_value($row, 'metric'),
        'value' => kingy_ali_product_graph_row_value($row, 'value'),
        'reviewed_count' => kingy_ali_product_graph_row_value($row, 'reviewed_count'),
        'unreviewed_count' => kingy_ali_product_graph_row_value($row, 'unreviewed_count'),
        'reviewer' => kingy_ali_product_graph_row_value($row, 'reviewer'),
        'review_state' => kingy_ali_product_graph_row_value($row, 'review_state'),
        'priority' => kingy_ali_product_graph_row_value($row, 'priority'),
        'issue_family' => kingy_ali_product_graph_row_value($row, 'issue_family'),
        'detail' => kingy_ali_product_graph_row_value($row, 'detail'),
        'safe_next_action' => kingy_ali_product_graph_row_value($row, 'safe_next_action'),
    )); ?>
    <?php
}

function kingy_ali_product_graph_render_reviewer_progress_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No reviewer progress rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_reviewer_progress_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_reviewer_progress_detail_url(kingy_ali_product_graph_row_value($row, 'progress_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if ($column === 'progress_id') : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_work_queue_tab() {
    $queue = kingy_ali_product_graph_work_queue_data();
    $rows = isset($queue['rows']) && is_array($queue['rows']) ? $queue['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_work_queue_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_work_queue_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_work_queue_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Graph Review Work Queue', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only prioritized review tasks derived from graph health, repair planner, evidence pack, opportunities, and review overlay data. This queue does not approve rows, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Work Queue Rows', 'kingy-ai-launch-intelligence'), isset($queue['row_count']) ? absint($queue['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('High Priority', 'kingy-ai-launch-intelligence'), isset($queue['counts_by_priority']['high']) ? absint($queue['counts_by_priority']['high']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Medium Priority', 'kingy-ai-launch-intelligence'), isset($queue['counts_by_priority']['medium']) ? absint($queue['counts_by_priority']['medium']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Low Priority', 'kingy-ai-launch-intelligence'), isset($queue['counts_by_priority']['low']) ? absint($queue['counts_by_priority']['low']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
    </div>

    <h3><?php esc_html_e('Queue Counts by Issue Family', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($queue['counts_by_family']) ? $queue['counts_by_family'] : array()); ?>

    <h3><?php esc_html_e('Queue Counts by Blocker', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($queue['counts_by_blocker']) ? $queue['counts_by_blocker'] : array()); ?>

    <?php kingy_ali_product_graph_render_work_queue_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_work_queue_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Prioritized Review Queue', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_work_queue_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_work_queue_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_work_queue_export_links() {
    ?>
    <h3><?php esc_html_e('Work Queue Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only graph review data and reviewer metadata overlay.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_work_queue_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_work_queue_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download work queue %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_work_queue_rows($rows) {
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_priority', 80));
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_family', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_review_state', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_blocker', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_work_search', 120));

    if ($priority === '' && $issue_family === '' && $review_state === '' && $blocker === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($priority !== '' && kingy_ali_product_graph_row_value($row, 'priority') !== $priority) {
            continue;
        }
        if ($issue_family !== '' && kingy_ali_product_graph_row_value($row, 'issue_family') !== $issue_family) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'current_review_state') !== $review_state) {
            continue;
        }
        if ($blocker !== '' && kingy_ali_product_graph_row_value($row, 'blocker') !== $blocker) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'queue_id'),
                kingy_ali_product_graph_row_value($row, 'issue_family'),
                kingy_ali_product_graph_row_value($row, 'source_row_id'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_candidate'),
                kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
                kingy_ali_product_graph_row_value($row, 'recommended_reviewer_action'),
                kingy_ali_product_graph_row_value($row, 'blocker'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_work_queue_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_work_queue_filters($filtered_rows, $all_rows) {
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_priority', 80));
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_family', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_review_state', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_work_blocker', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_work_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="work_queue">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_work_priority', __('Priority', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_work_queue_values($all_rows, 'priority'), $priority); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_work_family', __('Issue family', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_work_queue_values($all_rows, 'issue_family'), $issue_family); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_work_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_work_blocker', __('Blocker', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_work_queue_values($all_rows, 'blocker'), $blocker); ?>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search work queue rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_work_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search queue, source, target, blocker', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('work_queue')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered work queue rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_work_queue_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'work_queue',
    );

    foreach (array('kingy_pg_work_priority', 'kingy_pg_work_family', 'kingy_pg_work_review_state', 'kingy_pg_work_blocker', 'kingy_pg_work_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_work_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_work_queue_detail_url($queue_id) {
    $args = kingy_ali_product_graph_work_queue_filter_query_args();
    $args['kingy_pg_work_queue_id'] = kingy_ali_product_graph_sanitize_row_id($queue_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_work_queue_back_url() {
    return add_query_arg(kingy_ali_product_graph_work_queue_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_render_work_queue_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Work Queue Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No work queue row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_work_queue_back_url()); ?>"><?php esc_html_e('Back to work queue', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not approve work, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_work_queue_back_url()); ?>"><?php esc_html_e('Back to work queue', 'kingy-ai-launch-intelligence'); ?></a>
        <?php if (kingy_ali_product_graph_row_value($row, 'destination_url') !== '') : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, 'destination_url')); ?>"><?php esc_html_e('Open destination tab', 'kingy-ai-launch-intelligence'); ?></a>
        <?php endif; ?>
        <?php if (kingy_ali_product_graph_row_value($row, 'evidence_pack_id') !== '') : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_detail_url(kingy_ali_product_graph_row_value($row, 'evidence_pack_id'))); ?>"><?php esc_html_e('Open evidence pack', 'kingy-ai-launch-intelligence'); ?></a>
        <?php endif; ?>
    </p>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'queue_id' => kingy_ali_product_graph_row_value($row, 'queue_id'),
        'priority' => kingy_ali_product_graph_row_value($row, 'priority'),
        'issue_family' => kingy_ali_product_graph_row_value($row, 'issue_family'),
        'source_row_id' => kingy_ali_product_graph_row_value($row, 'source_row_id'),
        'source_title' => kingy_ali_product_graph_row_value($row, 'source_title'),
        'target_candidate' => kingy_ali_product_graph_row_value($row, 'target_candidate'),
        'evidence_pack_id' => kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
        'recommended_reviewer_action' => kingy_ali_product_graph_row_value($row, 'recommended_reviewer_action'),
        'current_review_state' => kingy_ali_product_graph_row_value($row, 'current_review_state'),
        'blocker' => kingy_ali_product_graph_row_value($row, 'blocker'),
        'destination_tab' => kingy_ali_product_graph_row_value($row, 'destination_tab'),
        'destination_url' => kingy_ali_product_graph_row_value($row, 'destination_url'),
    )); ?>
    <h4><?php esc_html_e('Reviewer metadata overlay', 'kingy-ai-launch-intelligence'); ?></h4>
    <p><?php esc_html_e('This form saves reviewer metadata only to the Product Graph review overlay option. It does not change WordPress content, graph artifacts, URLs, SEO, redirects, schema, or links.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php
    $review_row = $row;
    $review_row['review_row_id'] = kingy_ali_product_graph_row_value($row, 'queue_id');
    $review_row['review_state'] = kingy_ali_product_graph_row_value($row, 'current_review_state', 'unreviewed');
    kingy_ali_product_graph_render_review_controls($review_row, 'work_queue', 'work_queue');
    ?>
    <?php
}

function kingy_ali_product_graph_render_work_queue_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No work queue rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_work_queue_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_work_queue_detail_url(kingy_ali_product_graph_row_value($row, 'queue_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if ($column === 'destination_url' && kingy_ali_product_graph_row_value($row, $column) !== '') : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>"><?php esc_html_e('Open destination', 'kingy-ai-launch-intelligence'); ?></a>
                            <?php elseif ($column === 'evidence_pack_id' && kingy_ali_product_graph_row_value($row, $column) !== '') : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_detail_url(kingy_ali_product_graph_row_value($row, $column))); ?>"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_link_recommendations_tab() {
    $recommendations = kingy_ali_product_graph_link_recommendations_data();
    $rows = isset($recommendations['rows']) && is_array($recommendations['rows']) ? $recommendations['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_link_recommendation_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_link_recommendation_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_link_recommendation_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Product Graph Link Recommendations', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only internal link recommendation candidates generated from existing Product Graph edges, resolver data, evidence packs, and reviewer overlay metadata. This tab does not insert links, approve rows, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), isset($recommendations['row_count']) ? absint($recommendations['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Edge Types', 'kingy-ai-launch-intelligence'), isset($recommendations['counts_by_edge_type']) && is_array($recommendations['counts_by_edge_type']) ? count($recommendations['counts_by_edge_type']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Excluded Missing Targets', 'kingy-ai-launch-intelligence'), isset($recommendations['exclusion_counts']['missing_target_url']) ? absint($recommendations['exclusion_counts']['missing_target_url']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Excluded Noindex/Uncertain', 'kingy-ai-launch-intelligence'), isset($recommendations['exclusion_counts']['noindex_or_canonical_uncertainty']) ? absint($recommendations['exclusion_counts']['noindex_or_canonical_uncertainty']) : 0); ?>
    </div>

    <h3><?php esc_html_e('Recommendation Counts by Edge Type', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($recommendations['counts_by_edge_type']) ? $recommendations['counts_by_edge_type'] : array()); ?>

    <h3><?php esc_html_e('Recommendation Counts by Confidence', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($recommendations['counts_by_confidence']) ? $recommendations['counts_by_confidence'] : array()); ?>

    <h3><?php esc_html_e('Exclusions Applied', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($recommendations['exclusion_counts']) ? $recommendations['exclusion_counts'] : array()); ?>

    <?php kingy_ali_product_graph_render_link_recommendation_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_link_recommendation_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Recommendation Queue', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_recommendation_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_link_recommendation_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_recommendation_export_links() {
    ?>
    <h3><?php esc_html_e('Link Recommendation Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from the current read-only recommendation set. They do not read arbitrary files, insert links, or mutate graph artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_recommendations_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_recommendations_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download link recommendations %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_link_recommendation_rows($rows) {
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_target_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_confidence', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_blocker', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_link_search', 120));

    if ($source_type === '' && $target_type === '' && $confidence === '' && $blocker === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($source_type !== '' && kingy_ali_product_graph_row_value($row, 'source_type') !== $source_type) {
            continue;
        }
        if ($target_type !== '' && kingy_ali_product_graph_row_value($row, 'target_type') !== $target_type) {
            continue;
        }
        if ($confidence !== '' && kingy_ali_product_graph_row_value($row, 'confidence') !== $confidence) {
            continue;
        }
        if ($blocker !== '' && kingy_ali_product_graph_row_value($row, 'blockers') !== $blocker) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'recommendation_id'),
                kingy_ali_product_graph_row_value($row, 'source_url'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_url'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'reason'),
                kingy_ali_product_graph_row_value($row, 'edge_type'),
                kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_link_recommendation_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_link_recommendation_filters($filtered_rows, $all_rows) {
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_target_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_confidence', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_blocker', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_link_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="link_recommendations">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_source_type', __('Source type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_recommendation_values($all_rows, 'source_type'), $source_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_target_type', __('Target type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_recommendation_values($all_rows, 'target_type'), $target_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_confidence', __('Confidence', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_recommendation_values($all_rows, 'confidence'), $confidence); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_blocker', __('Blocker', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_recommendation_values($all_rows, 'blockers'), $blocker); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_link_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search link recommendations', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_link_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search source, target, anchor, reason', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_recommendations')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered recommendations from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_link_recommendation_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'link_recommendations',
    );

    foreach (array('kingy_pg_link_source_type', 'kingy_pg_link_target_type', 'kingy_pg_link_confidence', 'kingy_pg_link_blocker', 'kingy_pg_link_review_state', 'kingy_pg_link_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_link_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_requested_link_recommendation_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_link_recommendation_id', 120));
}

function kingy_ali_product_graph_link_recommendation_detail_url($recommendation_id) {
    $args = kingy_ali_product_graph_link_recommendation_filter_query_args();
    $args['kingy_pg_link_recommendation_id'] = kingy_ali_product_graph_sanitize_row_id($recommendation_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_link_recommendation_back_url() {
    return add_query_arg(kingy_ali_product_graph_link_recommendation_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_find_link_recommendation_row($rows, $recommendation_id) {
    $recommendation_id = kingy_ali_product_graph_sanitize_row_id($recommendation_id);
    if ($recommendation_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'recommendation_id') === $recommendation_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_render_link_recommendation_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Link Recommendation Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No link recommendation matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_back_url()); ?>"><?php esc_html_e('Back to link recommendations', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not insert links, approve rows, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_back_url()); ?>"><?php esc_html_e('Back to link recommendations', 'kingy-ai-launch-intelligence'); ?></a>
        <?php if (kingy_ali_product_graph_row_value($row, 'evidence_pack_id') !== '') : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_detail_url(kingy_ali_product_graph_row_value($row, 'evidence_pack_id'))); ?>"><?php esc_html_e('Open evidence pack', 'kingy-ai-launch-intelligence'); ?></a>
        <?php endif; ?>
    </p>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'recommendation_id' => kingy_ali_product_graph_row_value($row, 'recommendation_id'),
        'source_url' => kingy_ali_product_graph_row_value($row, 'source_url'),
        'source_title' => kingy_ali_product_graph_row_value($row, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($row, 'source_type'),
        'target_url' => kingy_ali_product_graph_row_value($row, 'target_url'),
        'target_title' => kingy_ali_product_graph_row_value($row, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($row, 'target_type'),
        'suggested_anchor_text' => kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
        'reason' => kingy_ali_product_graph_row_value($row, 'reason'),
        'confidence' => kingy_ali_product_graph_row_value($row, 'confidence'),
        'edge_type' => kingy_ali_product_graph_row_value($row, 'edge_type'),
        'edge_id' => kingy_ali_product_graph_row_value($row, 'edge_id'),
        'evidence_pack_id' => kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
        'resolver_status' => kingy_ali_product_graph_row_value($row, 'resolver_status'),
        'blockers' => kingy_ali_product_graph_row_value($row, 'blockers'),
        'review_state' => kingy_ali_product_graph_row_value($row, 'review_state'),
        'insertable' => kingy_ali_product_graph_row_value($row, 'insertable'),
        'write_capable' => kingy_ali_product_graph_row_value($row, 'write_capable'),
    )); ?>
    <h4><?php esc_html_e('Reviewer metadata overlay', 'kingy-ai-launch-intelligence'); ?></h4>
    <p><?php esc_html_e('This form saves reviewer metadata only to the Product Graph review overlay option. It does not insert links, update WordPress content, change graph artifacts, approve recommendations, or modify SEO, routes, redirects, schema, or robots settings.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php
    $review_row = $row;
    $review_row['review_row_id'] = kingy_ali_product_graph_row_value($row, 'recommendation_id');
    kingy_ali_product_graph_render_review_controls($review_row, 'link_recommendation', 'link_recommendations');
    ?>
    <?php
}

function kingy_ali_product_graph_render_link_recommendation_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No link recommendations match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_recommendation_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_detail_url(kingy_ali_product_graph_row_value($row, 'recommendation_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('recommendation_id', 'evidence_pack_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_link_review_batches_tab() {
    $batches = kingy_ali_product_graph_link_review_batches_data();
    $rows = isset($batches['rows']) && is_array($batches['rows']) ? $batches['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_link_review_batch_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_link_review_batch_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_link_review_batch_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Internal Link Review Batches', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only reviewer batches derived from Link Recommendations, Link Readiness, Source Context Audit, and Review Overlay metadata. This tab does not insert links, create insertion jobs, approve recommendations, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), isset($batches['total_recommendations']) ? absint($batches['total_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Batch Rows', 'kingy-ai-launch-intelligence'), count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Reviewed', 'kingy-ai-launch-intelligence'), isset($batches['reviewed_count']) ? absint($batches['reviewed_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Unreviewed', 'kingy-ai-launch-intelligence'), isset($batches['unreviewed_count']) ? absint($batches['unreviewed_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Source Resolved', 'kingy-ai-launch-intelligence'), isset($batches['source_resolved_count']) ? absint($batches['source_resolved_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Existing Target Links', 'kingy-ai-launch-intelligence'), isset($batches['existing_link_duplicate_count']) ? absint($batches['existing_link_duplicate_count']) : 0); ?>
    </div>

    <h3><?php esc_html_e('Batch Counts by Type', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($batches['counts_by_batch_type']) ? $batches['counts_by_batch_type'] : array()); ?>

    <h3><?php esc_html_e('Batch Counts by Priority', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($batches['counts_by_priority']) ? $batches['counts_by_priority'] : array()); ?>

    <h3><?php esc_html_e('Current Batch Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($batches['counts_by_blocker']) ? $batches['counts_by_blocker'] : array()); ?>

    <h3><?php esc_html_e('Safe Batch Review Rules', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(array(
        __('Use these batches to decide what to review next; do not insert links from this screen.', 'kingy-ai-launch-intelligence'),
        __('Open recommendation and source-context details to record reviewer metadata only.', 'kingy-ai-launch-intelligence'),
        __('Accepted metadata still does not create an insertion job or content write.', 'kingy-ai-launch-intelligence'),
        __('Graph source artifacts, posts, pages, CPTs, SEO settings, routes, and redirects remain unchanged.', 'kingy-ai-launch-intelligence'),
    )); ?>

    <?php kingy_ali_product_graph_render_link_review_batches_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_link_review_batch_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Link Review Batch Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_review_batch_filters($filtered_rows, $rows, $batches); ?>
    <?php kingy_ali_product_graph_render_link_review_batch_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_review_batches_export_links() {
    ?>
    <h3><?php esc_html_e('Link Review Batches Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only batch diagnostics. They do not read arbitrary files, write graph artifacts, create insertion jobs, or mutate WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_review_batches_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_review_batches_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download link review batches %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_link_review_batch_rows($rows) {
    $batch_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_type', 100));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_priority', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_blocker', 100));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_link_batch_search', 120));

    if ($batch_type === '' && $priority === '' && $blocker === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($batch_type !== '' && kingy_ali_product_graph_row_value($row, 'batch_type') !== $batch_type) {
            continue;
        }
        if ($priority !== '' && kingy_ali_product_graph_row_value($row, 'priority') !== $priority) {
            continue;
        }
        if ($blocker !== '' && kingy_ali_product_graph_row_value($row, 'blocker') !== $blocker) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_link_review_batch_row_state_count($row, $review_state) === 0) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'batch_id'),
                kingy_ali_product_graph_row_value($row, 'batch_type'),
                kingy_ali_product_graph_row_value($row, 'representative_recommendation_ids'),
                kingy_ali_product_graph_row_value($row, 'recommended_reviewer_action'),
                kingy_ali_product_graph_row_value($row, 'blocker'),
                kingy_ali_product_graph_row_value($row, 'safe_next_step'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_link_review_batch_row_state_count($row, $review_state) {
    $map = array(
        'unreviewed' => 'unreviewed_count',
        'accepted' => 'accepted_count',
        'rejected' => 'rejected_count',
        'needs_source' => 'needs_source_count',
        'needs_refresh' => 'needs_refresh_count',
        'needs_canonical_review' => 'needs_canonical_review_count',
        'model_inventory_blocked' => 'model_inventory_blocked_count',
    );

    return isset($map[$review_state]) ? absint(kingy_ali_product_graph_row_value($row, $map[$review_state])) : 0;
}

function kingy_ali_product_graph_render_link_review_batch_filters($filtered_rows, $all_rows, $batches = array()) {
    $batch_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_type', 100));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_priority', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_blocker', 100));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_link_batch_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    $filter_options = isset($batches['filter_options']) && is_array($batches['filter_options']) ? $batches['filter_options'] : array();
    $batch_type_options = isset($filter_options['batch_type']) && is_array($filter_options['batch_type']) ? $filter_options['batch_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'batch_type');
    $priority_options = isset($filter_options['priority']) && is_array($filter_options['priority']) ? $filter_options['priority'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'priority');
    $blocker_options = isset($filter_options['blocker']) && is_array($filter_options['blocker']) ? $filter_options['blocker'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'blocker');
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="link_review_batches">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_type', __('Batch type', 'kingy-ai-launch-intelligence'), $batch_type_options, $batch_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_priority', __('Priority', 'kingy-ai-launch-intelligence'), $priority_options, $priority); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_blocker', __('Blocker', 'kingy-ai-launch-intelligence'), $blocker_options, $blocker); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state present', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_link_batch_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search link review batches', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_link_batch_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search batches, blockers, recommendations', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_review_batches')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing %1$d filtered batch rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_link_review_batch_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'link_review_batches',
    );

    foreach (array('kingy_pg_link_batch_type', 'kingy_pg_link_batch_priority', 'kingy_pg_link_batch_blocker', 'kingy_pg_link_batch_review_state', 'kingy_pg_link_batch_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_link_batch_search' ? 120 : 100);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_requested_link_review_batch_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_link_review_batch_id', 120));
}

function kingy_ali_product_graph_link_review_batch_detail_url($batch_id) {
    $args = kingy_ali_product_graph_link_review_batch_filter_query_args();
    $args['kingy_pg_link_review_batch_id'] = kingy_ali_product_graph_sanitize_row_id($batch_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_link_review_batch_back_url() {
    return add_query_arg(kingy_ali_product_graph_link_review_batch_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_find_link_review_batch_row($rows, $batch_id) {
    $batch_id = kingy_ali_product_graph_sanitize_row_id($batch_id);
    if ($batch_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'batch_id') === $batch_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_render_link_review_batch_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Link Review Batch Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No link review batch matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_link_review_batch_back_url()); ?>"><?php esc_html_e('Back to link review batches', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;

    $batch_type = kingy_ali_product_graph_row_value($row, 'batch_type');
    $detail_rows = kingy_ali_product_graph_link_review_batch_detail_rows($batch_type);
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This batch detail is read-only. It does not insert links, create insertion jobs, approve recommendations, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_link_review_batch_back_url()); ?>"><?php esc_html_e('Back to link review batches', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_recommendations')); ?>"><?php esc_html_e('Open Link Recommendations', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('source_context_audit')); ?>"><?php esc_html_e('Open Source Context Audit', 'kingy-ai-launch-intelligence'); ?></a>
    </p>
    <h4><?php esc_html_e('Batch Summary', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table($row); ?>
    <h4><?php esc_html_e('Representative Recommendations', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_link_review_batch_detail_table($detail_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_review_batch_detail_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No representative recommendations are available for this batch.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = array('recommendation_id', 'source_title', 'source_url', 'source_type', 'target_title', 'target_url', 'target_type', 'confidence', 'review_state', 'source_context_audit_status', 'safe_reviewer_question');
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></th>
                <th scope="col"><?php esc_html_e('Source Context', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 25) as $row) : ?>
                <tr>
                    <td><a href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_detail_url(kingy_ali_product_graph_row_value($row, 'recommendation_id'))); ?>"><?php esc_html_e('Open recommendation', 'kingy-ai-launch-intelligence'); ?></a></td>
                    <td>
                        <?php if (kingy_ali_product_graph_row_value($row, 'source_context_audit_id') !== '') : ?>
                            <a href="<?php echo esc_url(kingy_ali_product_graph_source_context_audit_detail_url(kingy_ali_product_graph_row_value($row, 'source_context_audit_id'))); ?>"><?php esc_html_e('Open source context', 'kingy-ai-launch-intelligence'); ?></a>
                        <?php else : ?>
                            <?php esc_html_e('No source context row', 'kingy-ai-launch-intelligence'); ?>
                        <?php endif; ?>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if ($column === 'recommendation_id') : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_link_review_batch_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No link review batches match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_review_batch_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td><a href="<?php echo esc_url(kingy_ali_product_graph_link_review_batch_detail_url(kingy_ali_product_graph_row_value($row, 'batch_id'))); ?>"><?php esc_html_e('View batch', 'kingy-ai-launch-intelligence'); ?></a></td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('batch_id', 'representative_recommendation_ids'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_link_batch_progress_tab() {
    $progress = kingy_ali_product_graph_link_batch_progress_data();
    $rows = isset($progress['rows']) && is_array($progress['rows']) ? $progress['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_link_batch_progress_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_link_batch_progress_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_link_batch_progress_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Internal Link Review Batch Progress', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only progress diagnostics across internal link review batches. Batch memberships can overlap, so progress totals describe review workload, not unique insertion candidates. This tab does not insert links, create insertion jobs, approve recommendations, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), isset($progress['total_recommendations']) ? absint($progress['total_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Batch Rows', 'kingy-ai-launch-intelligence'), isset($progress['batch_row_count']) ? absint($progress['batch_row_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Batch Memberships', 'kingy-ai-launch-intelligence'), isset($progress['batch_membership_count']) ? absint($progress['batch_membership_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Reviewed Memberships', 'kingy-ai-launch-intelligence'), isset($progress['reviewed_membership_count']) ? absint($progress['reviewed_membership_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Unreviewed Memberships', 'kingy-ai-launch-intelligence'), isset($progress['unreviewed_membership_count']) ? absint($progress['unreviewed_membership_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Overall Completion %', 'kingy-ai-launch-intelligence'), isset($progress['overall_completion_percent']) ? $progress['overall_completion_percent'] : '0.0'); ?>
    </div>

    <h3><?php esc_html_e('Overall Batch Review Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php
    kingy_ali_product_graph_render_key_value_table(array(
        'total_recommendations' => isset($progress['total_recommendations']) ? $progress['total_recommendations'] : 0,
        'batch_row_count' => isset($progress['batch_row_count']) ? $progress['batch_row_count'] : 0,
        'batch_membership_count' => isset($progress['batch_membership_count']) ? $progress['batch_membership_count'] : 0,
        'reviewed_membership_count' => isset($progress['reviewed_membership_count']) ? $progress['reviewed_membership_count'] : 0,
        'unreviewed_membership_count' => isset($progress['unreviewed_membership_count']) ? $progress['unreviewed_membership_count'] : 0,
        'overall_completion_percent' => isset($progress['overall_completion_percent']) ? $progress['overall_completion_percent'] : '0.0',
    ));
    ?>

    <h3><?php esc_html_e('High-Priority Batch Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php
    kingy_ali_product_graph_render_key_value_table(array(
        'high_priority_batch_rows' => isset($progress['high_priority_batch_rows']) ? $progress['high_priority_batch_rows'] : 0,
        'high_priority_membership_count' => isset($progress['high_priority_membership_count']) ? $progress['high_priority_membership_count'] : 0,
        'high_priority_reviewed_count' => isset($progress['high_priority_reviewed_count']) ? $progress['high_priority_reviewed_count'] : 0,
        'high_priority_completion_percent' => isset($progress['high_priority_completion_percent']) ? $progress['high_priority_completion_percent'] : '0.0',
    ));
    ?>

    <h3><?php esc_html_e('Follow-Up State Summary', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['followup_counts']) ? $progress['followup_counts'] : array()); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['counts_by_blocker']) ? $progress['counts_by_blocker'] : array()); ?>

    <h3><?php esc_html_e('Recommended Next Batch', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($progress['recommended_next_batch']) ? $progress['recommended_next_batch'] : array()); ?>

    <h3><?php esc_html_e('Safe Progress Review Rules', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(array(
        __('Use this dashboard to prioritize human review only; no insertion workflow exists here.', 'kingy-ai-launch-intelligence'),
        __('Completion percent means reviewer metadata coverage across overlapping batch memberships.', 'kingy-ai-launch-intelligence'),
        __('Open batch details to inspect representative recommendations before recording review metadata elsewhere.', 'kingy-ai-launch-intelligence'),
        __('Do not insert links, create pages, approve recommendations, or mutate WordPress content from this screen.', 'kingy-ai-launch-intelligence'),
    )); ?>

    <?php kingy_ali_product_graph_render_link_batch_progress_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_link_batch_progress_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Batch Progress Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_batch_progress_filters($filtered_rows, $rows, $progress); ?>
    <?php kingy_ali_product_graph_render_link_batch_progress_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_batch_progress_export_links() {
    ?>
    <h3><?php esc_html_e('Batch Progress Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only progress diagnostics. They do not read arbitrary files, write graph artifacts, create insertion jobs, or mutate WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_batch_progress_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_batch_progress_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download batch progress %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_link_batch_progress_rows($rows) {
    $batch_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_type', 100));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_priority', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_blocker', 100));
    $completion_range = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_completion', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_search', 120));

    if ($batch_type === '' && $priority === '' && $blocker === '' && $completion_range === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($batch_type !== '' && kingy_ali_product_graph_row_value($row, 'batch_type') !== $batch_type) {
            continue;
        }
        if ($priority !== '' && kingy_ali_product_graph_row_value($row, 'priority') !== $priority) {
            continue;
        }
        if ($blocker !== '' && kingy_ali_product_graph_row_value($row, 'blocker') !== $blocker) {
            continue;
        }
        if ($completion_range !== '' && kingy_ali_product_graph_row_value($row, 'completion_range') !== $completion_range) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_link_review_batch_row_state_count($row, $review_state) === 0) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'batch_id'),
                kingy_ali_product_graph_row_value($row, 'batch_type'),
                kingy_ali_product_graph_row_value($row, 'blocker'),
                kingy_ali_product_graph_row_value($row, 'recommended_next_reviewer_action'),
                kingy_ali_product_graph_row_value($row, 'safe_next_step'),
                kingy_ali_product_graph_row_value($row, 'representative_recommendation_ids'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_render_link_batch_progress_filters($filtered_rows, $all_rows, $progress = array()) {
    $batch_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_type', 100));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_priority', 80));
    $blocker = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_blocker', 100));
    $completion_range = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_completion', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    $filter_options = isset($progress['filter_options']) && is_array($progress['filter_options']) ? $progress['filter_options'] : array();
    $batch_type_options = isset($filter_options['batch_type']) && is_array($filter_options['batch_type']) ? $filter_options['batch_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'batch_type');
    $priority_options = isset($filter_options['priority']) && is_array($filter_options['priority']) ? $filter_options['priority'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'priority');
    $blocker_options = isset($filter_options['blocker']) && is_array($filter_options['blocker']) ? $filter_options['blocker'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'blocker');
    $completion_options = isset($filter_options['completion_range']) && is_array($filter_options['completion_range']) ? $filter_options['completion_range'] : kingy_ali_product_graph_link_batch_progress_completion_range_options();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="link_batch_progress">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_progress_type', __('Batch type', 'kingy-ai-launch-intelligence'), $batch_type_options, $batch_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_progress_priority', __('Priority', 'kingy-ai-launch-intelligence'), $priority_options, $priority); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_progress_blocker', __('Blocker', 'kingy-ai-launch-intelligence'), $blocker_options, $blocker); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_batch_progress_completion', __('Completion range', 'kingy-ai-launch-intelligence'), $completion_options, $completion_range); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state present', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_link_batch_progress_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search batch progress rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_link_batch_progress_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search progress, blockers, recommendations', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_batch_progress')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing %1$d filtered progress rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_link_batch_progress_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'link_batch_progress',
    );

    foreach (array('kingy_pg_link_batch_progress_type', 'kingy_pg_link_batch_progress_priority', 'kingy_pg_link_batch_progress_blocker', 'kingy_pg_link_batch_progress_completion', 'kingy_pg_link_batch_progress_review_state', 'kingy_pg_link_batch_progress_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_link_batch_progress_search' ? 120 : 100);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_requested_link_batch_progress_id() {
    return kingy_ali_product_graph_sanitize_row_id(kingy_ali_product_graph_get_value('kingy_pg_link_batch_progress_id', 120));
}

function kingy_ali_product_graph_link_batch_progress_detail_url($batch_id) {
    $args = kingy_ali_product_graph_link_batch_progress_filter_query_args();
    $args['kingy_pg_link_batch_progress_id'] = kingy_ali_product_graph_sanitize_row_id($batch_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_link_batch_progress_back_url() {
    return add_query_arg(kingy_ali_product_graph_link_batch_progress_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_find_link_batch_progress_row($rows, $batch_id) {
    $batch_id = kingy_ali_product_graph_sanitize_row_id($batch_id);
    if ($batch_id === '') {
        return array();
    }

    foreach ($rows as $row) {
        if (is_array($row) && kingy_ali_product_graph_row_value($row, 'batch_id') === $batch_id) {
            return $row;
        }
    }

    return array();
}

function kingy_ali_product_graph_render_link_batch_progress_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Batch Progress Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No batch progress row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_link_batch_progress_back_url()); ?>"><?php esc_html_e('Back to batch progress', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;

    $batch_type = kingy_ali_product_graph_row_value($row, 'batch_type');
    $detail_rows = kingy_ali_product_graph_link_review_batch_detail_rows($batch_type);
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This batch progress detail is read-only. It does not insert links, create insertion jobs, approve recommendations, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_link_batch_progress_back_url()); ?>"><?php esc_html_e('Back to batch progress', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_link_review_batch_detail_url(kingy_ali_product_graph_row_value($row, 'batch_id'))); ?>"><?php esc_html_e('Open Link Review Batch', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_readiness')); ?>"><?php esc_html_e('Open Link Readiness', 'kingy-ai-launch-intelligence'); ?></a>
    </p>
    <h4><?php esc_html_e('Progress Summary', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table($row); ?>
    <h4><?php esc_html_e('Current Review-State Mix', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php
    kingy_ali_product_graph_render_key_value_table(array(
        'accepted' => kingy_ali_product_graph_row_value($row, 'accepted_count'),
        'rejected' => kingy_ali_product_graph_row_value($row, 'rejected_count'),
        'needs_source' => kingy_ali_product_graph_row_value($row, 'needs_source_count'),
        'needs_refresh' => kingy_ali_product_graph_row_value($row, 'needs_refresh_count'),
        'needs_canonical_review' => kingy_ali_product_graph_row_value($row, 'needs_canonical_review_count'),
        'model_inventory_blocked' => kingy_ali_product_graph_row_value($row, 'model_inventory_blocked_count'),
        'unreviewed' => kingy_ali_product_graph_row_value($row, 'unreviewed_count'),
    ));
    ?>
    <h4><?php esc_html_e('Source Context Mix', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php
    kingy_ali_product_graph_render_key_value_table(array(
        'source_resolved_count' => kingy_ali_product_graph_row_value($row, 'source_resolved_count'),
        'source_unresolved_count' => kingy_ali_product_graph_row_value($row, 'source_unresolved_count'),
        'source_resolved_percent' => kingy_ali_product_graph_row_value($row, 'source_resolved_percent'),
        'existing_link_duplicate_count' => kingy_ali_product_graph_row_value($row, 'existing_link_duplicate_count'),
    ));
    ?>
    <h4><?php esc_html_e('Safe Reviewer Question', 'kingy-ai-launch-intelligence'); ?></h4>
    <p><?php echo esc_html(kingy_ali_product_graph_link_review_batch_reviewer_question($batch_type)); ?></p>
    <h4><?php esc_html_e('Top Representative Recommendations', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_link_review_batch_detail_table($detail_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_batch_progress_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No batch progress rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_batch_progress_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td><a href="<?php echo esc_url(kingy_ali_product_graph_link_batch_progress_detail_url(kingy_ali_product_graph_row_value($row, 'batch_id'))); ?>"><?php esc_html_e('View progress', 'kingy-ai-launch-intelligence'); ?></a></td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if ($column === 'batch_id') : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_stage3_closeout_tab() {
    $closeout = kingy_ali_product_graph_stage3_closeout_data();
    $row = isset($closeout['rows'][0]) && is_array($closeout['rows'][0]) ? $closeout['rows'][0] : array();
    ?>
    <h2><?php esc_html_e('Stage 3 Internal Link Review Closeout', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only closeout and Stage 4 readiness summary for Product Graph internal link recommendations. This tab does not insert links, create insertion jobs, approve recommendations, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'recommendation_count', '0')); ?>
        <?php kingy_ali_admin_stat_card(__('Reviewed', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'reviewed_count', '0')); ?>
        <?php kingy_ali_admin_stat_card(__('Accepted', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'accepted_count', '0')); ?>
        <?php kingy_ali_admin_stat_card(__('Plan Eligible', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'plan_preview_eligible_count', '0')); ?>
        <?php kingy_ali_admin_stat_card(__('Source Unresolved', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'source_unresolved_count', '0')); ?>
        <?php kingy_ali_admin_stat_card(__('Batch Completion %', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_row_value($row, 'batch_completion_percent', '0.0')); ?>
    </div>

    <div class="notice notice-info inline">
        <p><?php esc_html_e('Stage 4 is not ready yet. Human editorial review, accepted recommendations, source-context evidence, and separate content-write approval are still required before any future insertion workflow can even be designed.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>

    <?php kingy_ali_product_graph_render_stage3_closeout_nav_links(); ?>

    <h3><?php esc_html_e('What Stage 3 Added', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($closeout['what_stage3_added']) ? $closeout['what_stage3_added'] : array()); ?>

    <h3><?php esc_html_e('Current Recommendation State', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($closeout['current_recommendation_state']) ? $closeout['current_recommendation_state'] : array()); ?>

    <h3><?php esc_html_e('Reviewer Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($closeout['reviewer_progress']) ? $closeout['reviewer_progress'] : array()); ?>

    <h3><?php esc_html_e('Source Context State', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($closeout['source_context_state']) ? $closeout['source_context_state'] : array()); ?>

    <h3><?php esc_html_e('Batch Progress State', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($closeout['batch_progress_state']) ? $closeout['batch_progress_state'] : array()); ?>

    <h3><?php esc_html_e('Stage 4 Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($closeout['stage4_blockers']) ? $closeout['stage4_blockers'] : array()); ?>

    <h3><?php esc_html_e('Safe Next Human Actions', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($closeout['safe_next_human_actions']) ? $closeout['safe_next_human_actions'] : array()); ?>

    <h3><?php esc_html_e('What This Screen Still Cannot Do', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($closeout['what_this_screen_still_cannot_do']) ? $closeout['what_this_screen_still_cannot_do'] : array()); ?>

    <h3><?php esc_html_e('Closeout Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($row); ?>

    <?php kingy_ali_product_graph_render_stage3_closeout_export_links(); ?>
    <?php
}

function kingy_ali_product_graph_render_stage3_closeout_nav_links() {
    $links = array(
        'link_recommendations' => __('Open Link Recommendations', 'kingy-ai-launch-intelligence'),
        'link_readiness' => __('Open Link Readiness', 'kingy-ai-launch-intelligence'),
        'link_plan_preview' => __('Open Link Plan Preview', 'kingy-ai-launch-intelligence'),
        'source_context_audit' => __('Open Source Context Audit', 'kingy-ai-launch-intelligence'),
        'link_batch_progress' => __('Open Batch Progress', 'kingy-ai-launch-intelligence'),
        'review_overlay' => __('Open Review Overlay', 'kingy-ai-launch-intelligence'),
    );
    ?>
    <p>
        <?php foreach ($links as $tab => $label) : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url($tab)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </p>
    <?php
}

function kingy_ali_product_graph_render_stage3_closeout_export_links() {
    ?>
    <h3><?php esc_html_e('Stage 3 Closeout Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only derived diagnostics. They do not read arbitrary files, write graph artifacts, create insertion jobs, or mutate WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_stage3_closeout_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_stage3_closeout_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download Stage 3 closeout %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_link_readiness_tab() {
    $readiness = kingy_ali_product_graph_link_readiness_data();
    $rows = isset($readiness['rows']) && is_array($readiness['rows']) ? $readiness['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_link_readiness_rows($rows);
    ?>
    <h2><?php esc_html_e('Internal Link Recommendation Readiness', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only readiness diagnostics for the Product Graph internal link recommendation set. This tab does not insert links, approve recommendations, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), isset($readiness['total_recommendations']) ? absint($readiness['total_recommendations']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Reviewed', 'kingy-ai-launch-intelligence'), isset($readiness['reviewed_count']) ? absint($readiness['reviewed_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Unreviewed', 'kingy-ai-launch-intelligence'), isset($readiness['unreviewed_count']) ? absint($readiness['unreviewed_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Missing Evidence', 'kingy-ai-launch-intelligence'), isset($readiness['recommendations_missing_evidence_pack']) ? absint($readiness['recommendations_missing_evidence_pack']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Verified or URL-backed', 'kingy-ai-launch-intelligence'), isset($readiness['url_backed_or_verified_recommendations']) ? absint($readiness['url_backed_or_verified_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
    </div>

    <h3><?php esc_html_e('Recommendation Readiness Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'total_recommendations' => isset($readiness['total_recommendations']) ? $readiness['total_recommendations'] : 0,
        'reviewed_count' => isset($readiness['reviewed_count']) ? $readiness['reviewed_count'] : 0,
        'unreviewed_count' => isset($readiness['unreviewed_count']) ? $readiness['unreviewed_count'] : 0,
        'recommendations_with_blockers' => isset($readiness['recommendations_with_blockers']) ? $readiness['recommendations_with_blockers'] : 0,
        'non_insertable_non_write_capable_recommendations' => isset($readiness['non_insertable_non_write_capable_recommendations']) ? $readiness['non_insertable_non_write_capable_recommendations'] : 0,
    )); ?>

    <h3><?php esc_html_e('Internal Link Quality Snapshot', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'internal_targets' => isset($readiness['internal_target_recommendations']) ? $readiness['internal_target_recommendations'] : 0,
        'external_targets_blocked' => isset($readiness['external_target_recommendations']) ? $readiness['external_target_recommendations'] : 0,
        'already_linked_blocked' => isset($readiness['already_linked_recommendations']) ? $readiness['already_linked_recommendations'] : 0,
        'exact_anchor_found' => isset($readiness['exact_anchor_found_recommendations']) ? $readiness['exact_anchor_found_recommendations'] : 0,
        'alternate_anchor_found' => isset($readiness['alternate_anchor_found_recommendations']) ? $readiness['alternate_anchor_found_recommendations'] : 0,
        'eligible_for_review_sprint' => isset($readiness['eligible_for_review_sprint_recommendations']) ? $readiness['eligible_for_review_sprint_recommendations'] : 0,
    )); ?>
    <?php kingy_ali_product_graph_render_key_value_table(isset($readiness['counts_by_quality_bucket']) ? $readiness['counts_by_quality_bucket'] : array()); ?>

    <h3><?php esc_html_e('Review Progress', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($readiness['counts_by_review_state']) ? $readiness['counts_by_review_state'] : array()); ?>

    <h3><?php esc_html_e('Readiness Buckets', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($readiness['counts_by_readiness_bucket']) ? $readiness['counts_by_readiness_bucket'] : array()); ?>

    <h3><?php esc_html_e('Evidence Coverage', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'recommendations_missing_evidence_pack' => isset($readiness['recommendations_missing_evidence_pack']) ? $readiness['recommendations_missing_evidence_pack'] : 0,
        'url_backed_or_verified_recommendations' => isset($readiness['url_backed_or_verified_recommendations']) ? $readiness['url_backed_or_verified_recommendations'] : 0,
    )); ?>

    <h3><?php esc_html_e('Confidence Mix', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($readiness['counts_by_confidence']) ? $readiness['counts_by_confidence'] : array()); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'missing_evidence_pack' => isset($readiness['recommendations_missing_evidence_pack']) ? $readiness['recommendations_missing_evidence_pack'] : 0,
        'recommendations_with_blockers' => isset($readiness['recommendations_with_blockers']) ? $readiness['recommendations_with_blockers'] : 0,
        'link_insertion_workflow' => __('not enabled', 'kingy-ai-launch-intelligence'),
        'content_mutation' => __('not allowed from this screen', 'kingy-ai-launch-intelligence'),
    )); ?>

    <h3><?php esc_html_e('Safe Next Reviewer Actions', 'kingy-ai-launch-intelligence'); ?></h3>
    <ul>
        <li><?php esc_html_e('Open Link Recommendations to review individual recommendation details and record reviewer metadata only.', 'kingy-ai-launch-intelligence'); ?></li>
        <li><?php esc_html_e('Open Evidence Pack when a recommendation needs source support.', 'kingy-ai-launch-intelligence'); ?></li>
        <li><?php esc_html_e('Use Review Overlay to audit saved reviewer notes and states.', 'kingy-ai-launch-intelligence'); ?></li>
        <li><?php esc_html_e('Do not insert links, create pages, approve recommendations, or mutate WordPress content from this dashboard.', 'kingy-ai-launch-intelligence'); ?></li>
    </ul>

    <h3><?php esc_html_e('Read-only Navigation', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_readiness_nav_links(); ?>

    <?php kingy_ali_product_graph_render_link_readiness_export_links(); ?>

    <h3><?php esc_html_e('Readiness Diagnostics', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_readiness_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_link_readiness_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_readiness_nav_links() {
    $links = array(
        'link_recommendations' => __('Link Recommendations', 'kingy-ai-launch-intelligence'),
        'evidence_pack' => __('Evidence Pack', 'kingy-ai-launch-intelligence'),
        'review_overlay' => __('Review Overlay', 'kingy-ai-launch-intelligence'),
        'work_queue' => __('Work Queue', 'kingy-ai-launch-intelligence'),
    );
    ?>
    <p>
        <?php foreach ($links as $tab => $label) : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url($tab)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </p>
    <?php
}

function kingy_ali_product_graph_render_link_readiness_export_links() {
    ?>
    <h3><?php esc_html_e('Link Readiness Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from the current read-only readiness diagnostics. They do not read arbitrary files, insert links, or mutate graph artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_readiness_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_readiness_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download link readiness %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_link_readiness_rows($rows) {
    $bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_bucket', 80));
    $quality_bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_quality_bucket', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_confidence', 80));
    $edge_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_edge_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_link_ready_search', 120));

    if ($bucket === '' && $quality_bucket === '' && $confidence === '' && $edge_type === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($bucket !== '' && kingy_ali_product_graph_row_value($row, 'readiness_bucket') !== $bucket) {
            continue;
        }
        if ($quality_bucket !== '' && kingy_ali_product_graph_row_value($row, 'quality_bucket') !== $quality_bucket) {
            continue;
        }
        if ($confidence !== '' && kingy_ali_product_graph_row_value($row, 'confidence') !== $confidence) {
            continue;
        }
        if ($edge_type !== '' && kingy_ali_product_graph_row_value($row, 'edge_type') !== $edge_type) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'readiness_row_id'),
                kingy_ali_product_graph_row_value($row, 'recommendation_id'),
                kingy_ali_product_graph_row_value($row, 'source_url'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_url'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'usable_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'quality_bucket'),
                kingy_ali_product_graph_row_value($row, 'quality_reason'),
                kingy_ali_product_graph_row_value($row, 'safe_next_action'),
                kingy_ali_product_graph_row_value($row, 'reviewer_notes'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_link_readiness_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_link_readiness_filters($filtered_rows, $all_rows) {
    $bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_bucket', 80));
    $quality_bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_quality_bucket', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_confidence', 80));
    $edge_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_edge_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_ready_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_link_ready_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="link_readiness">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_ready_bucket', __('Readiness bucket', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_readiness_values($all_rows, 'readiness_bucket'), $bucket); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_ready_quality_bucket', __('Quality bucket', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_readiness_values($all_rows, 'quality_bucket'), $quality_bucket); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_ready_confidence', __('Confidence', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_readiness_values($all_rows, 'confidence'), $confidence); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_ready_edge_type', __('Edge type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_link_readiness_values($all_rows, 'edge_type'), $edge_type); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_link_ready_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search link readiness diagnostics', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_link_ready_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search readiness rows', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_readiness')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered readiness rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_render_link_readiness_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No link readiness rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_readiness_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_detail_url(kingy_ali_product_graph_row_value($row, 'recommendation_id'))); ?>">
                            <?php esc_html_e('Open recommendation', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('readiness_row_id', 'recommendation_id', 'evidence_pack_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_link_plan_preview_tab() {
    $preview = kingy_ali_product_graph_link_plan_preview_data();
    $rows = isset($preview['rows']) && is_array($preview['rows']) ? $preview['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_link_plan_preview_rows($rows);
    $dry_run = kingy_ali_product_graph_link_dry_run_data();
    ?>
    <h2><?php esc_html_e('Internal Link Insertion Plan Preview', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only preview diagnostics for a possible future internal-link insertion plan. This tab does not create insertion jobs, insert links, approve recommendations, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Recommendations', 'kingy-ai-launch-intelligence'), isset($preview['total_recommendations']) ? absint($preview['total_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Plan Preview Eligible', 'kingy-ai-launch-intelligence'), isset($preview['plan_preview_eligible']) ? absint($preview['plan_preview_eligible']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Accepted', 'kingy-ai-launch-intelligence'), isset($preview['accepted_recommendations']) ? absint($preview['accepted_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Ready for Editor Review', 'kingy-ai-launch-intelligence'), isset($preview['ready_for_editor_review_recommendations']) ? absint($preview['ready_for_editor_review_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Verified or URL-backed', 'kingy-ai-launch-intelligence'), isset($preview['verified_or_url_backed_recommendations']) ? absint($preview['verified_or_url_backed_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Preview Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
    </div>

    <?php if (empty($preview['plan_preview_eligible'])) : ?>
        <div class="notice notice-warning inline">
            <p><?php esc_html_e('plan_preview_eligible = 0. No insertion plan can be generated yet because reviewer acceptance and evidence review are required first.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
    <?php endif; ?>

    <h3><?php esc_html_e('Plan Preview Summary', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'total_recommendations' => isset($preview['total_recommendations']) ? $preview['total_recommendations'] : 0,
        'plan_preview_eligible' => isset($preview['plan_preview_eligible']) ? $preview['plan_preview_eligible'] : 0,
        'accepted_recommendations' => isset($preview['accepted_recommendations']) ? $preview['accepted_recommendations'] : 0,
        'ready_for_editor_review_recommendations' => isset($preview['ready_for_editor_review_recommendations']) ? $preview['ready_for_editor_review_recommendations'] : 0,
        'non_insertable_non_write_capable_recommendations' => isset($preview['non_insertable_non_write_capable_recommendations']) ? $preview['non_insertable_non_write_capable_recommendations'] : 0,
    )); ?>

    <h3><?php esc_html_e('Eligibility Rules', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($preview['eligibility_rules']) ? $preview['eligibility_rules'] : array()); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($preview['current_blockers']) ? $preview['current_blockers'] : array()); ?>

    <h3><?php esc_html_e('Required Human Review Before Any Future Write', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($preview['required_human_review_before_future_write']) ? $preview['required_human_review_before_future_write'] : array()); ?>

    <h3><?php esc_html_e('Safe Next Actions', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(isset($preview['safe_next_actions']) ? $preview['safe_next_actions'] : array()); ?>

    <h3><?php esc_html_e('Read-only Navigation', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_plan_preview_nav_links(); ?>

    <?php kingy_ali_product_graph_render_link_plan_preview_export_links(); ?>

    <h3><?php esc_html_e('Stage 4A No-write Dry-run Package', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('This package evaluates the first 10 accepted plan-preview recommendations and builds display-only before/after snippets. It does not create insertion jobs, insert links, edit content, update graph artifacts, or change WordPress settings.', 'kingy-ai-launch-intelligence'); ?></p>
    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Evaluated', 'kingy-ai-launch-intelligence'), isset($dry_run['evaluated_recommendations']) ? absint($dry_run['evaluated_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Dry-run Rows', 'kingy-ai-launch-intelligence'), isset($dry_run['dry_run_package_rows_created']) ? absint($dry_run['dry_run_package_rows_created']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Excluded', 'kingy-ai-launch-intelligence'), isset($dry_run['excluded_recommendations']) ? absint($dry_run['excluded_recommendations']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Eligible Available', 'kingy-ai-launch-intelligence'), isset($dry_run['eligible_recommendations_available']) ? absint($dry_run['eligible_recommendations_available']) : 0); ?>
    </div>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'dry_run_only' => 'true',
        'insertable' => 'false',
        'write_capable' => 'false',
        'content_writes' => 'false',
        'link_insertion' => 'false',
        'insertion_jobs' => 'false',
        'graph_artifact_mutation' => 'false',
    )); ?>
    <?php kingy_ali_product_graph_render_link_dry_run_export_links(); ?>
    <?php kingy_ali_product_graph_render_link_dry_run_table(isset($dry_run['rows']) && is_array($dry_run['rows']) ? $dry_run['rows'] : array(), __('Dry-run Package Rows', 'kingy-ai-launch-intelligence')); ?>
    <?php if (!empty($dry_run['excluded_rows']) && is_array($dry_run['excluded_rows'])) : ?>
        <?php kingy_ali_product_graph_render_link_dry_run_table($dry_run['excluded_rows'], __('Excluded Dry-run Rows', 'kingy-ai-launch-intelligence')); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Eligible Plan Preview Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_link_plan_preview_filters($filtered_rows, $rows, $preview); ?>
    <?php kingy_ali_product_graph_render_link_plan_preview_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_link_plan_preview_nav_links() {
    $links = array(
        'link_recommendations' => __('Link Recommendations', 'kingy-ai-launch-intelligence'),
        'link_readiness' => __('Link Readiness', 'kingy-ai-launch-intelligence'),
        'evidence_pack' => __('Evidence Pack', 'kingy-ai-launch-intelligence'),
        'review_overlay' => __('Review Overlay', 'kingy-ai-launch-intelligence'),
    );
    ?>
    <p>
        <?php foreach ($links as $tab => $label) : ?>
            <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url($tab)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </p>
    <?php
}

function kingy_ali_product_graph_render_link_plan_preview_export_links() {
    ?>
    <h3><?php esc_html_e('Link Plan Preview Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only plan-preview diagnostics. They do not read arbitrary files, create insertion jobs, insert links, or mutate graph artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_plan_preview_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_plan_preview_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download link plan preview %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_link_dry_run_export_links() {
    ?>
    <h4><?php esc_html_e('Stage 4A Dry-run Export', 'kingy-ai-launch-intelligence'); ?></h4>
    <p><?php esc_html_e('Exports are generated from current read-only dry-run diagnostics. They do not create insertion jobs, insert links, write WordPress content, or mutate graph artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_link_dry_run_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_link_dry_run_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download Stage 4A dry-run package %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_link_dry_run_table($rows, $heading) {
    ?>
    <h4><?php echo esc_html($heading); ?></h4>
    <?php
    if (!$rows) {
        echo '<p>' . esc_html__('No rows are available for this dry-run section.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_dry_run_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 10) as $row) : ?>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('dry_run_id', 'recommendation_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php elseif (in_array($column, array('before_snippet', 'after_snippet_with_proposed_link_markup', 'reviewer_note'), true)) : ?>
                                <pre style="white-space: pre-wrap; max-width: 520px;"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></pre>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_filter_link_plan_preview_rows($rows) {
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_confidence', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_target_type', 80));
    $bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_bucket', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_link_plan_search', 120));

    if ($confidence === '' && $source_type === '' && $target_type === '' && $bucket === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($confidence !== '' && kingy_ali_product_graph_row_value($row, 'confidence') !== $confidence) {
            continue;
        }
        if ($source_type !== '' && kingy_ali_product_graph_row_value($row, 'source_type') !== $source_type) {
            continue;
        }
        if ($target_type !== '' && kingy_ali_product_graph_row_value($row, 'target_type') !== $target_type) {
            continue;
        }
        if ($bucket !== '' && kingy_ali_product_graph_row_value($row, 'readiness_bucket') !== $bucket) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'preview_id'),
                kingy_ali_product_graph_row_value($row, 'recommendation_id'),
                kingy_ali_product_graph_row_value($row, 'source_url'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_url'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'required_pre_insertion_checks'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_link_plan_preview_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_link_plan_preview_filters($filtered_rows, $all_rows, $preview = array()) {
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_confidence', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_target_type', 80));
    $bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_bucket', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_link_plan_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_link_plan_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    $filter_options = isset($preview['filter_options']) && is_array($preview['filter_options']) ? $preview['filter_options'] : array();
    $confidence_options = isset($filter_options['confidence']) && is_array($filter_options['confidence']) ? $filter_options['confidence'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'confidence');
    $source_type_options = isset($filter_options['source_type']) && is_array($filter_options['source_type']) ? $filter_options['source_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'source_type');
    $target_type_options = isset($filter_options['target_type']) && is_array($filter_options['target_type']) ? $filter_options['target_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'target_type');
    $bucket_options = isset($filter_options['readiness_bucket']) && is_array($filter_options['readiness_bucket']) ? $filter_options['readiness_bucket'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'readiness_bucket');
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="link_plan_preview">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_plan_confidence', __('Confidence', 'kingy-ai-launch-intelligence'), $confidence_options, $confidence); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_plan_source_type', __('Source type', 'kingy-ai-launch-intelligence'), $source_type_options, $source_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_plan_target_type', __('Target type', 'kingy-ai-launch-intelligence'), $target_type_options, $target_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_link_plan_bucket', __('Readiness bucket', 'kingy-ai-launch-intelligence'), $bucket_options, $bucket); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_link_plan_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search link plan preview rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_link_plan_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search preview rows', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('link_plan_preview')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered preview rows from %2$d eligible rows.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_render_link_plan_preview_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No eligible link plan preview rows match the current filters. Reviewer acceptance and evidence review are required before any future plan preview rows can appear.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_link_plan_preview_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_detail_url(kingy_ali_product_graph_row_value($row, 'recommendation_id'))); ?>">
                            <?php esc_html_e('Open recommendation', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('preview_id', 'recommendation_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_source_context_audit_tab() {
    $audit = kingy_ali_product_graph_source_context_audit_data();
    $rows = isset($audit['rows']) && is_array($audit['rows']) ? $audit['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_source_context_audit_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_source_context_audit_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_source_context_audit_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Internal Link Source Context Audit', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only source-content context checks for internal link recommendations. This tab reads source WordPress objects only; it does not create insertion jobs, insert links, edit content, update graph artifacts, or change SEO, routes, redirects, schema, or robots settings.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Audit Rows', 'kingy-ai-launch-intelligence'), isset($audit['row_count']) ? absint($audit['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Source Resolved', 'kingy-ai-launch-intelligence'), isset($audit['source_resolved_count']) ? absint($audit['source_resolved_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Source Unresolved', 'kingy-ai-launch-intelligence'), isset($audit['source_unresolved_count']) ? absint($audit['source_unresolved_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Existing Target Links', 'kingy-ai-launch-intelligence'), isset($audit['target_already_linked_count']) ? absint($audit['target_already_linked_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Anchor Found', 'kingy-ai-launch-intelligence'), isset($audit['anchor_found_count']) ? absint($audit['anchor_found_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
    </div>

    <h3><?php esc_html_e('Source Context Summary', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($audit['counts_by_status']) ? $audit['counts_by_status'] : array()); ?>

    <h3><?php esc_html_e('Existing Link Duplicates', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'target_already_linked_count' => isset($audit['target_already_linked_count']) ? $audit['target_already_linked_count'] : 0,
    )); ?>

    <h3><?php esc_html_e('Anchor Availability', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'anchor_found_count' => isset($audit['anchor_found_count']) ? $audit['anchor_found_count'] : 0,
        'alternate_anchor_found_count' => isset($audit['alternate_anchor_found_count']) ? $audit['alternate_anchor_found_count'] : 0,
        'anchor_missing_count' => isset($audit['anchor_missing_count']) ? $audit['anchor_missing_count'] : 0,
    )); ?>

    <h3><?php esc_html_e('Internal Link Quality Summary', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'internal_targets' => isset($audit['internal_target_count']) ? $audit['internal_target_count'] : 0,
        'external_targets_blocked' => isset($audit['external_target_count']) ? $audit['external_target_count'] : 0,
        'target_already_linked' => isset($audit['target_already_linked_count']) ? $audit['target_already_linked_count'] : 0,
        'exact_anchor_found' => isset($audit['anchor_found_count']) ? $audit['anchor_found_count'] : 0,
        'alternate_anchor_found' => isset($audit['alternate_anchor_found_count']) ? $audit['alternate_anchor_found_count'] : 0,
        'eligible_for_review_sprint' => isset($audit['eligible_for_review_sprint_count']) ? $audit['eligible_for_review_sprint_count'] : 0,
    )); ?>
    <?php kingy_ali_product_graph_render_key_value_table(isset($audit['counts_by_quality_bucket']) ? $audit['counts_by_quality_bucket'] : array()); ?>

    <h3><?php esc_html_e('Source Resolution Gaps', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'source_resolved_count' => isset($audit['source_resolved_count']) ? $audit['source_resolved_count'] : 0,
        'source_unresolved_count' => isset($audit['source_unresolved_count']) ? $audit['source_unresolved_count'] : 0,
        'source_readable_count' => isset($audit['source_readable_count']) ? $audit['source_readable_count'] : 0,
        'empty_content_count' => isset($audit['empty_content_count']) ? $audit['empty_content_count'] : 0,
    )); ?>

    <h3><?php esc_html_e('Current Blockers', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'link_insertion_workflow' => __('not enabled', 'kingy-ai-launch-intelligence'),
        'all_rows_insertable' => __('false', 'kingy-ai-launch-intelligence'),
        'all_rows_write_capable' => __('false', 'kingy-ai-launch-intelligence'),
        'review_acceptance_required' => __('yes', 'kingy-ai-launch-intelligence'),
    )); ?>

    <h3><?php esc_html_e('Safe Next Reviewer Actions', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_list(array(
        __('Review source context and duplicate-link evidence before any future insertion planning.', 'kingy-ai-launch-intelligence'),
        __('Use Link Recommendations and Review Overlay to record reviewer metadata only.', 'kingy-ai-launch-intelligence'),
        __('Do not insert links, create pages, approve recommendations, or mutate WordPress content from this screen.', 'kingy-ai-launch-intelligence'),
    )); ?>

    <?php kingy_ali_product_graph_render_source_context_audit_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_source_context_audit_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Source Context Audit Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_source_context_audit_filters($filtered_rows, $rows, $audit); ?>
    <?php kingy_ali_product_graph_render_source_context_audit_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_source_context_audit_export_links() {
    ?>
    <h3><?php esc_html_e('Source Context Audit Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only audit diagnostics. They do not read arbitrary files, write graph artifacts, create insertion jobs, or mutate WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_source_context_audit_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_source_context_audit_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download source context audit %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_source_context_audit_rows($rows) {
    $audit_status = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_status', 80));
    $quality_bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_quality_bucket', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_target_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_confidence', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_source_context_search', 120));

    if ($audit_status === '' && $quality_bucket === '' && $source_type === '' && $target_type === '' && $confidence === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($audit_status !== '' && kingy_ali_product_graph_row_value($row, 'audit_status') !== $audit_status) {
            continue;
        }
        if ($quality_bucket !== '' && kingy_ali_product_graph_row_value($row, 'quality_bucket') !== $quality_bucket) {
            continue;
        }
        if ($source_type !== '' && kingy_ali_product_graph_row_value($row, 'source_type') !== $source_type) {
            continue;
        }
        if ($target_type !== '' && kingy_ali_product_graph_row_value($row, 'target_type') !== $target_type) {
            continue;
        }
        if ($confidence !== '' && kingy_ali_product_graph_row_value($row, 'confidence') !== $confidence) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'audit_id'),
                kingy_ali_product_graph_row_value($row, 'recommendation_id'),
                kingy_ali_product_graph_row_value($row, 'source_url'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_url'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'usable_anchor_text'),
                kingy_ali_product_graph_row_value($row, 'quality_bucket'),
                kingy_ali_product_graph_row_value($row, 'quality_reason'),
                kingy_ali_product_graph_row_value($row, 'blockers'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_render_source_context_audit_filters($filtered_rows, $all_rows, $audit = array()) {
    $audit_status = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_status', 80));
    $quality_bucket = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_quality_bucket', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_target_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_confidence', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_source_context_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_source_context_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    $filter_options = isset($audit['filter_options']) && is_array($audit['filter_options']) ? $audit['filter_options'] : array();
    $status_options = isset($filter_options['audit_status']) && is_array($filter_options['audit_status']) ? $filter_options['audit_status'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'audit_status');
    $quality_bucket_options = isset($filter_options['quality_bucket']) && is_array($filter_options['quality_bucket']) ? $filter_options['quality_bucket'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'quality_bucket');
    $source_type_options = isset($filter_options['source_type']) && is_array($filter_options['source_type']) ? $filter_options['source_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'source_type');
    $target_type_options = isset($filter_options['target_type']) && is_array($filter_options['target_type']) ? $filter_options['target_type'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'target_type');
    $confidence_options = isset($filter_options['confidence']) && is_array($filter_options['confidence']) ? $filter_options['confidence'] : kingy_ali_product_graph_unique_link_plan_preview_values($all_rows, 'confidence');
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="source_context_audit">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_source_context_status', __('Audit status', 'kingy-ai-launch-intelligence'), $status_options, $audit_status); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_source_context_quality_bucket', __('Quality bucket', 'kingy-ai-launch-intelligence'), $quality_bucket_options, $quality_bucket); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_source_context_source_type', __('Source type', 'kingy-ai-launch-intelligence'), $source_type_options, $source_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_source_context_target_type', __('Target type', 'kingy-ai-launch-intelligence'), $target_type_options, $target_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_source_context_confidence', __('Confidence', 'kingy-ai-launch-intelligence'), $confidence_options, $confidence); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_source_context_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search source context audit rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_source_context_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search source, target, anchor', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_tab_url('source_context_audit')); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered audit rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_source_context_audit_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'source_context_audit',
    );

    foreach (array('kingy_pg_source_context_status', 'kingy_pg_source_context_quality_bucket', 'kingy_pg_source_context_source_type', 'kingy_pg_source_context_target_type', 'kingy_pg_source_context_confidence', 'kingy_pg_source_context_review_state', 'kingy_pg_source_context_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_source_context_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_source_context_audit_detail_url($audit_id) {
    $args = kingy_ali_product_graph_source_context_audit_filter_query_args();
    $args['kingy_pg_source_context_audit_id'] = kingy_ali_product_graph_sanitize_row_id($audit_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_source_context_audit_back_url() {
    return add_query_arg(kingy_ali_product_graph_source_context_audit_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_render_source_context_audit_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Source Context Audit Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No source context audit row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_source_context_audit_back_url()); ?>"><?php esc_html_e('Back to source context audit', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not insert links, create insertion jobs, approve recommendations, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_source_context_audit_back_url()); ?>"><?php esc_html_e('Back to source context audit', 'kingy-ai-launch-intelligence'); ?></a>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_link_recommendation_detail_url(kingy_ali_product_graph_row_value($row, 'recommendation_id'))); ?>"><?php esc_html_e('Open link recommendation', 'kingy-ai-launch-intelligence'); ?></a>
    </p>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'audit_id' => kingy_ali_product_graph_row_value($row, 'audit_id'),
        'recommendation_id' => kingy_ali_product_graph_row_value($row, 'recommendation_id'),
        'source_url' => kingy_ali_product_graph_row_value($row, 'source_url'),
        'source_title' => kingy_ali_product_graph_row_value($row, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($row, 'source_type'),
        'source_wp_object_id' => kingy_ali_product_graph_row_value($row, 'source_wp_object_id'),
        'source_wp_object_type' => kingy_ali_product_graph_row_value($row, 'source_wp_object_type'),
        'target_url' => kingy_ali_product_graph_row_value($row, 'target_url'),
        'target_title' => kingy_ali_product_graph_row_value($row, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($row, 'target_type'),
        'suggested_anchor_text' => kingy_ali_product_graph_row_value($row, 'suggested_anchor_text'),
        'usable_anchor_text' => kingy_ali_product_graph_row_value($row, 'usable_anchor_text'),
        'confidence' => kingy_ali_product_graph_row_value($row, 'confidence'),
        'review_state' => kingy_ali_product_graph_row_value($row, 'review_state'),
        'readiness_bucket' => kingy_ali_product_graph_row_value($row, 'readiness_bucket'),
        'source_content_readable' => kingy_ali_product_graph_row_value($row, 'source_content_readable'),
        'target_already_linked_from_source' => kingy_ali_product_graph_row_value($row, 'target_already_linked_from_source'),
        'suggested_anchor_appears_in_source_content' => kingy_ali_product_graph_row_value($row, 'suggested_anchor_appears_in_source_content'),
        'alternate_anchor_found' => kingy_ali_product_graph_row_value($row, 'alternate_anchor_found'),
        'alternate_anchor_text' => kingy_ali_product_graph_row_value($row, 'alternate_anchor_text'),
        'usable_anchor_appears_in_source_content' => kingy_ali_product_graph_row_value($row, 'usable_anchor_appears_in_source_content'),
        'target_is_internal' => kingy_ali_product_graph_row_value($row, 'target_is_internal'),
        'target_not_already_linked' => kingy_ali_product_graph_row_value($row, 'target_not_already_linked'),
        'relationship_evidence_clear' => kingy_ali_product_graph_row_value($row, 'relationship_evidence_clear'),
        'eligible_for_review_sprint' => kingy_ali_product_graph_row_value($row, 'eligible_for_review_sprint'),
        'quality_bucket' => kingy_ali_product_graph_row_value($row, 'quality_bucket'),
        'quality_reason' => kingy_ali_product_graph_row_value($row, 'quality_reason'),
        'source_content_length' => kingy_ali_product_graph_row_value($row, 'source_content_length'),
        'audit_status' => kingy_ali_product_graph_row_value($row, 'audit_status'),
        'blockers' => kingy_ali_product_graph_row_value($row, 'blockers'),
        'safe_next_action' => kingy_ali_product_graph_row_value($row, 'safe_next_action'),
        'anchor_context_excerpt' => kingy_ali_product_graph_row_value($row, 'anchor_context_excerpt'),
        'existing_target_link_evidence' => kingy_ali_product_graph_row_value($row, 'existing_target_link_evidence'),
        'insertable' => kingy_ali_product_graph_row_value($row, 'insertable'),
        'write_capable' => kingy_ali_product_graph_row_value($row, 'write_capable'),
    )); ?>
    <h4><?php esc_html_e('Recommendation reviewer metadata overlay', 'kingy-ai-launch-intelligence'); ?></h4>
    <div class="notice notice-warning inline">
        <p><?php esc_html_e('This form saves reviewer metadata only to the existing link recommendation review overlay. It does not insert links, edit source content, create insertion jobs, approve a write workflow, update graph artifacts, or change WordPress content. Marking a recommendation accepted here still requires a later separately approved insertion workflow before any content write.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <?php
    $review_row = $row;
    $review_row['review_row_id'] = kingy_ali_product_graph_row_value($row, 'recommendation_id');
    kingy_ali_product_graph_render_review_controls(
        $review_row,
        'link_recommendation',
        'link_recommendations',
        array(
            'return_tab' => 'source_context_audit',
            'source_context_audit_id' => kingy_ali_product_graph_row_value($row, 'audit_id'),
        )
    );
    ?>
    <?php
}

function kingy_ali_product_graph_render_source_context_audit_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No source context audit rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_source_context_audit_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_source_context_audit_detail_url(kingy_ali_product_graph_row_value($row, 'audit_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td>
                            <?php if (in_array($column, array('audit_id', 'recommendation_id'), true)) : ?>
                                <code><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></code>
                            <?php elseif (in_array($column, array('source_url', 'target_url'), true)) : ?>
                                <a href="<?php echo esc_url(kingy_ali_product_graph_row_value($row, $column)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></a>
                            <?php else : ?>
                                <?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_health_tab() {
    $health = kingy_ali_product_graph_health_data();
    $metrics = isset($health['metrics']) && is_array($health['metrics']) ? $health['metrics'] : array();
    $queue = isset($health['relationship_qa_queue']) && is_array($health['relationship_qa_queue']) ? $health['relationship_qa_queue'] : array();
    $filtered_queue = kingy_ali_product_graph_filter_health_queue($queue);
    ?>
    <h2><?php esc_html_e('Graph Health', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only relationship QA computed from the current Product Graph review datasets and reviewer overlay. No graph artifacts or WordPress content are changed from this tab.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Nodes', 'kingy-ai-launch-intelligence'), isset($metrics['total_nodes']) ? absint($metrics['total_nodes']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Edges', 'kingy-ai-launch-intelligence'), isset($metrics['total_edges']) ? absint($metrics['total_edges']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Orphan Nodes', 'kingy-ai-launch-intelligence'), isset($metrics['orphan_nodes']) ? absint($metrics['orphan_nodes']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Missing Targets', 'kingy-ai-launch-intelligence'), isset($metrics['missing_target_nodes']) ? absint($metrics['missing_target_nodes']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Unresolved URLs', 'kingy-ai-launch-intelligence'), isset($metrics['unresolved_queue_count']) ? absint($metrics['unresolved_queue_count']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('QA Queue Rows', 'kingy-ai-launch-intelligence'), count($queue)); ?>
    </div>

    <h3><?php esc_html_e('Health Metrics', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table($metrics); ?>

    <h3><?php esc_html_e('Node Types', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($health['node_kind_counts']) ? $health['node_kind_counts'] : array()); ?>

    <h3><?php esc_html_e('Edge Types', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($health['edge_type_counts']) ? $health['edge_type_counts'] : array()); ?>

    <h3><?php esc_html_e('Edge Confidence', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($health['edge_confidence_counts']) ? $health['edge_confidence_counts'] : array()); ?>

    <h3><?php esc_html_e('URL-backed Edge Resolution', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($health['url_backed_edge_status_counts']) ? $health['url_backed_edge_status_counts'] : array()); ?>

    <h3><?php esc_html_e('Reviewer Overlay Counts', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php
    $overlay_summary = isset($health['review_overlay_summary']) && is_array($health['review_overlay_summary']) ? $health['review_overlay_summary'] : array();
    kingy_ali_product_graph_render_key_value_table(isset($overlay_summary['counts_by_state']) ? $overlay_summary['counts_by_state'] : array());
    ?>

    <?php kingy_ali_product_graph_render_health_export_links(); ?>

    <h3><?php esc_html_e('Relationship QA Queue', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Rows are advisory only. Use the review overlay to record editorial notes; this queue does not auto-fix edges, insert links, or mutate graph source files.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php kingy_ali_product_graph_render_health_filters($filtered_queue, $queue); ?>
    <?php kingy_ali_product_graph_render_health_queue_table($filtered_queue); ?>
    <?php
}

function kingy_ali_product_graph_render_health_export_links() {
    ?>
    <h3><?php esc_html_e('Graph Health Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only graph review datasets and reviewer metadata overlay.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_health_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_health_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download graph health %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_health_queue($rows) {
    $issue_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_issue_type', 80));
    $node_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_node_type', 80));
    $edge_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_edge_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_health_search', 120));

    if ($issue_type === '' && $node_type === '' && $edge_type === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($issue_type !== '' && kingy_ali_product_graph_row_value($row, 'issue_type') !== $issue_type) {
            continue;
        }
        if ($node_type !== '' && kingy_ali_product_graph_row_value($row, 'node_type') !== $node_type) {
            continue;
        }
        if ($edge_type !== '' && kingy_ali_product_graph_row_value($row, 'edge_type') !== $edge_type) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'issue_type'),
                kingy_ali_product_graph_row_value($row, 'row_id'),
                kingy_ali_product_graph_row_value($row, 'title'),
                kingy_ali_product_graph_row_value($row, 'detail'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_health_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_health_filters($filtered_rows, $all_rows) {
    $issue_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_issue_type', 80));
    $node_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_node_type', 80));
    $edge_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_edge_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_health_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_health_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="graph_health">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_health_issue_type', __('Issue type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_health_values($all_rows, 'issue_type'), $issue_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_health_node_type', __('Node type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_health_values($all_rows, 'node_type'), $node_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_health_edge_type', __('Edge type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_health_values($all_rows, 'edge_type'), $edge_type); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_health_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search graph health queue', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_health_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search issue, row, title, detail', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'graph_health'), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered QA rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_render_health_filter_select($name, $label, $values, $selected) {
    ?>
    <label style="margin-left: 8px;">
        <?php echo esc_html($label); ?>
        <select name="<?php echo esc_attr($name); ?>">
            <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
            <?php foreach ($values as $value) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected, $value); ?>><?php echo esc_html($value); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}

function kingy_ali_product_graph_render_health_queue_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No relationship QA rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }
    $columns = array('issue_type', 'priority', 'row_type', 'row_id', 'node_type', 'edge_type', 'review_state', 'title', 'detail');
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_repair_planner_tab() {
    $planner = kingy_ali_product_graph_repair_planner_data();
    $rows = isset($planner['rows']) && is_array($planner['rows']) ? $planner['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_repair_planner_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_repair_planner_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_repair_planner_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Graph Repair Planner', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only repair planning batches derived from Graph Health, Opportunities, and reviewer overlay metadata. This tab does not create pages, insert links, approve rows, or change graph source artifacts.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Planner Batches', 'kingy-ai-launch-intelligence'), isset($planner['row_count']) ? absint($planner['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('High Priority', 'kingy-ai-launch-intelligence'), isset($planner['counts_by_priority']['high']) ? absint($planner['counts_by_priority']['high']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Medium Priority', 'kingy-ai-launch-intelligence'), isset($planner['counts_by_priority']['medium']) ? absint($planner['counts_by_priority']['medium']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Low Priority', 'kingy-ai-launch-intelligence'), isset($planner['counts_by_priority']['low']) ? absint($planner['counts_by_priority']['low']) : 0); ?>
    </div>

    <h3><?php esc_html_e('Planner Counts by Family', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($planner['counts_by_family']) ? $planner['counts_by_family'] : array()); ?>

    <h3><?php esc_html_e('Planner Counts by Blocker', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($planner['counts_by_blocker']) ? $planner['counts_by_blocker'] : array()); ?>

    <?php kingy_ali_product_graph_render_repair_planner_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_repair_planner_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Planner Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_repair_planner_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_repair_planner_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_repair_planner_export_links() {
    ?>
    <h3><?php esc_html_e('Repair Planner Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only graph health, opportunity, and review overlay data.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_repair_planner_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_repair_planner_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download repair planner %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_repair_planner_rows($rows) {
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_family', 80));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_priority', 80));
    $blocked_by = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_blocked_by', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_repair_search', 120));

    if ($issue_family === '' && $priority === '' && $blocked_by === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($issue_family !== '' && kingy_ali_product_graph_row_value($row, 'issue_family') !== $issue_family) {
            continue;
        }
        if ($priority !== '' && kingy_ali_product_graph_row_value($row, 'priority') !== $priority) {
            continue;
        }
        if ($blocked_by !== '' && kingy_ali_product_graph_row_value($row, 'blocked_by') !== $blocked_by) {
            continue;
        }
        if ($review_state !== '' && strpos(',' . kingy_ali_product_graph_row_value($row, 'review_states') . ',', ',' . $review_state . ',') === false) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'planner_id'),
                kingy_ali_product_graph_row_value($row, 'issue_family'),
                kingy_ali_product_graph_row_value($row, 'example_row_ids'),
                kingy_ali_product_graph_row_value($row, 'recommended_safe_next_action'),
                kingy_ali_product_graph_row_value($row, 'required_evidence'),
                kingy_ali_product_graph_row_value($row, 'blocked_by'),
                kingy_ali_product_graph_row_value($row, 'review_overlay_status_summary'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_repair_planner_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_repair_planner_filters($filtered_rows, $all_rows) {
    $issue_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_family', 80));
    $priority = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_priority', 80));
    $blocked_by = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_blocked_by', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_repair_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_repair_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="repair_planner">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_repair_family', __('Issue family', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_repair_planner_values($all_rows, 'issue_family'), $issue_family); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_repair_priority', __('Priority', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_repair_planner_values($all_rows, 'priority'), $priority); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_repair_blocked_by', __('Blocked by', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_repair_planner_values($all_rows, 'blocked_by'), $blocked_by); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_repair_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search repair planner rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_repair_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search family, action, evidence, blocker', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'repair_planner'), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered planner rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_repair_planner_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'repair_planner',
    );

    foreach (array('kingy_pg_repair_family', 'kingy_pg_repair_priority', 'kingy_pg_repair_blocked_by', 'kingy_pg_repair_review_state', 'kingy_pg_repair_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_repair_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_repair_planner_detail_url($planner_id) {
    $args = kingy_ali_product_graph_repair_planner_filter_query_args();
    $args['kingy_pg_repair_planner_id'] = kingy_ali_product_graph_sanitize_row_id($planner_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_repair_planner_back_url() {
    return add_query_arg(kingy_ali_product_graph_repair_planner_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_render_repair_planner_detail_panel($detail_id, $row) {
    ?>
    <div class="notice notice-info" style="padding: 12px;">
        <?php if (!$row) : ?>
            <p>
                <?php echo esc_html(sprintf(__('No repair planner row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_repair_planner_back_url()); ?>"><?php esc_html_e('Back to repair planner', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        <?php else : ?>
            <h3><?php esc_html_e('Repair Planner Detail', 'kingy-ai-launch-intelligence'); ?></h3>
            <p><?php esc_html_e('This detail panel is read-only. It does not approve batches, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
            <p><a class="button" href="<?php echo esc_url(kingy_ali_product_graph_repair_planner_back_url()); ?>"><?php esc_html_e('Back to repair planner', 'kingy-ai-launch-intelligence'); ?></a></p>
            <?php kingy_ali_product_graph_render_key_value_table(array(
                'planner_id' => kingy_ali_product_graph_row_value($row, 'planner_id'),
                'issue_family' => kingy_ali_product_graph_row_value($row, 'issue_family'),
                'priority' => kingy_ali_product_graph_row_value($row, 'priority'),
                'affected_row_count' => kingy_ali_product_graph_row_value($row, 'affected_row_count'),
                'example_row_ids' => kingy_ali_product_graph_row_value($row, 'example_row_ids'),
                'recommended_safe_next_action' => kingy_ali_product_graph_row_value($row, 'recommended_safe_next_action'),
                'required_evidence' => kingy_ali_product_graph_row_value($row, 'required_evidence'),
                'blocked_by' => kingy_ali_product_graph_row_value($row, 'blocked_by'),
                'review_overlay_status_summary' => kingy_ali_product_graph_row_value($row, 'review_overlay_status_summary'),
            )); ?>
            <h4><?php esc_html_e('Example Rows', 'kingy-ai-launch-intelligence'); ?></h4>
            <?php kingy_ali_product_graph_render_repair_planner_example_rows(isset($row['example_rows']) && is_array($row['example_rows']) ? $row['example_rows'] : array()); ?>
        <?php endif; ?>
    </div>
    <?php
}

function kingy_ali_product_graph_render_repair_planner_example_rows($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No example rows are available for this planner batch.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = array('issue_type', 'opportunity_type', 'priority', 'row_type', 'row_id', 'opportunity_id', 'source_node', 'target_candidate', 'node_type', 'edge_type', 'confidence', 'review_state', 'title', 'source_title', 'target_title', 'detail', 'reason');
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 25) as $row) : ?>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_repair_planner_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No repair planner rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_repair_planner_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_repair_planner_detail_url(kingy_ali_product_graph_row_value($row, 'planner_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_evidence_pack_tab() {
    $evidence = kingy_ali_product_graph_evidence_pack_data();
    $rows = isset($evidence['rows']) && is_array($evidence['rows']) ? $evidence['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_evidence_pack_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_evidence_pack_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_evidence_pack_row($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Graph Evidence Pack', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only source evidence assembled from current graph nodes, edges, resolver records, unresolved queue rows, opportunities, repair planner batches, and reviewer overlay metadata. This tab does not approve rows, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Evidence Rows', 'kingy-ai-launch-intelligence'), isset($evidence['row_count']) ? absint($evidence['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('Opportunity Evidence', 'kingy-ai-launch-intelligence'), isset($evidence['counts_by_type']['opportunity_evidence']) ? absint($evidence['counts_by_type']['opportunity_evidence']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Planner Evidence', 'kingy-ai-launch-intelligence'), isset($evidence['counts_by_type']['repair_planner_batch_evidence']) ? absint($evidence['counts_by_type']['repair_planner_batch_evidence']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Filtered Rows', 'kingy-ai-launch-intelligence'), count($filtered_rows)); ?>
    </div>

    <h3><?php esc_html_e('Evidence Counts by Type', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($evidence['counts_by_type']) ? $evidence['counts_by_type'] : array()); ?>

    <h3><?php esc_html_e('Evidence Counts by Planner Family', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($evidence['counts_by_planner_family']) ? $evidence['counts_by_planner_family'] : array()); ?>

    <?php kingy_ali_product_graph_render_evidence_pack_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_evidence_pack_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Evidence Rows', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_evidence_pack_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_evidence_pack_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_evidence_pack_export_links() {
    ?>
    <h3><?php esc_html_e('Evidence Pack Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from current read-only graph, opportunity, repair planner, and review overlay data.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_evidence_pack_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_evidence_pack_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download evidence pack %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_evidence_pack_rows($rows) {
    $evidence_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_type', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_target_type', 80));
    $planner_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_planner_family', 80));
    $opportunity_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_opportunity_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_evidence_search', 120));

    if ($evidence_type === '' && $source_type === '' && $target_type === '' && $planner_family === '' && $opportunity_type === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($evidence_type !== '' && kingy_ali_product_graph_row_value($row, 'evidence_type') !== $evidence_type) {
            continue;
        }
        if ($source_type !== '' && kingy_ali_product_graph_row_value($row, 'source_type') !== $source_type) {
            continue;
        }
        if ($target_type !== '' && kingy_ali_product_graph_row_value($row, 'target_type') !== $target_type) {
            continue;
        }
        if ($planner_family !== '' && kingy_ali_product_graph_row_value($row, 'planner_family') !== $planner_family) {
            continue;
        }
        if ($opportunity_type !== '' && kingy_ali_product_graph_row_value($row, 'opportunity_type') !== $opportunity_type) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
                kingy_ali_product_graph_row_value($row, 'related_opportunity_id'),
                kingy_ali_product_graph_row_value($row, 'related_planner_id'),
                kingy_ali_product_graph_row_value($row, 'source_node_id'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_candidate'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'available_source_urls'),
                kingy_ali_product_graph_row_value($row, 'missing_evidence'),
                kingy_ali_product_graph_row_value($row, 'recommended_reviewer_question'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_evidence_pack_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_evidence_pack_filters($filtered_rows, $all_rows) {
    $evidence_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_type', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_source_type', 80));
    $target_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_target_type', 80));
    $planner_family = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_planner_family', 80));
    $opportunity_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_opportunity_type', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_evidence_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_evidence_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="evidence_pack">
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_evidence_type', __('Evidence type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_evidence_pack_values($all_rows, 'evidence_type'), $evidence_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_evidence_source_type', __('Source type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_evidence_pack_values($all_rows, 'source_type'), $source_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_evidence_target_type', __('Target type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_evidence_pack_values($all_rows, 'target_type'), $target_type); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_evidence_planner_family', __('Planner family', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_evidence_pack_values($all_rows, 'planner_family'), $planner_family); ?>
        <?php kingy_ali_product_graph_render_health_filter_select('kingy_pg_evidence_opportunity_type', __('Opportunity type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_evidence_pack_values($all_rows, 'opportunity_type'), $opportunity_type); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_evidence_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search evidence pack rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_evidence_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search ID, source, target, URL, evidence', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'evidence_pack'), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered evidence rows from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_evidence_pack_filter_query_args() {
    $args = array(
        'page' => 'kingy-ali-product-graph',
        'kingy_pg_tab' => 'evidence_pack',
    );

    foreach (array('kingy_pg_evidence_type', 'kingy_pg_evidence_source_type', 'kingy_pg_evidence_target_type', 'kingy_pg_evidence_planner_family', 'kingy_pg_evidence_opportunity_type', 'kingy_pg_evidence_review_state', 'kingy_pg_evidence_search') as $key) {
        $value = kingy_ali_product_graph_get_value($key, $key === 'kingy_pg_evidence_search' ? 120 : 80);
        if ($value !== '') {
            $args[$key] = $value;
        }
    }

    return $args;
}

function kingy_ali_product_graph_evidence_pack_detail_url($evidence_pack_id) {
    $args = kingy_ali_product_graph_evidence_pack_filter_query_args();
    $args['kingy_pg_evidence_pack_id'] = kingy_ali_product_graph_sanitize_row_id($evidence_pack_id);

    return add_query_arg($args, admin_url('admin.php'));
}

function kingy_ali_product_graph_evidence_pack_back_url() {
    return add_query_arg(kingy_ali_product_graph_evidence_pack_filter_query_args(), admin_url('admin.php'));
}

function kingy_ali_product_graph_render_evidence_pack_detail_panel($detail_id, $row) {
    ?>
    <h3><?php esc_html_e('Evidence Pack Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$row) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No evidence pack row matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_back_url()); ?>"><?php esc_html_e('Back to evidence pack', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not approve evidence, insert links, create pages, update graph artifacts, or change WordPress content.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_back_url()); ?>"><?php esc_html_e('Back to evidence pack', 'kingy-ai-launch-intelligence'); ?></a>
    </p>

    <?php kingy_ali_product_graph_render_key_value_table(array(
        'evidence_pack_id' => kingy_ali_product_graph_row_value($row, 'evidence_pack_id'),
        'evidence_type' => kingy_ali_product_graph_row_value($row, 'evidence_type'),
        'related_opportunity_id' => kingy_ali_product_graph_row_value($row, 'related_opportunity_id'),
        'related_planner_id' => kingy_ali_product_graph_row_value($row, 'related_planner_id'),
        'planner_family' => kingy_ali_product_graph_row_value($row, 'planner_family'),
        'opportunity_type' => kingy_ali_product_graph_row_value($row, 'opportunity_type'),
        'source_node_id' => kingy_ali_product_graph_row_value($row, 'source_node_id'),
        'source_title' => kingy_ali_product_graph_row_value($row, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($row, 'source_type'),
        'target_candidate' => kingy_ali_product_graph_row_value($row, 'target_candidate'),
        'target_title' => kingy_ali_product_graph_row_value($row, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($row, 'target_type'),
        'available_source_urls' => kingy_ali_product_graph_row_value($row, 'available_source_urls'),
        'existing_edge_context' => kingy_ali_product_graph_row_value($row, 'existing_edge_context'),
        'missing_evidence' => kingy_ali_product_graph_row_value($row, 'missing_evidence'),
        'recommended_reviewer_question' => kingy_ali_product_graph_row_value($row, 'recommended_reviewer_question'),
        'safe_next_action' => kingy_ali_product_graph_row_value($row, 'safe_next_action'),
        'review_state' => kingy_ali_product_graph_row_value($row, 'review_state'),
        'reviewer_notes' => kingy_ali_product_graph_row_value($row, 'reviewer_notes'),
    )); ?>

    <h4><?php esc_html_e('Context Rows', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_evidence_pack_context_rows(isset($row['context_rows']) && is_array($row['context_rows']) ? $row['context_rows'] : array()); ?>
    <?php
}

function kingy_ali_product_graph_render_evidence_pack_context_rows($context_rows) {
    if (!$context_rows) {
        echo '<p>' . esc_html__('No additional context rows are available for this evidence pack.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = array('node_key', 'edge_id', 'issue_type', 'opportunity_type', 'row_id', 'source', 'target', 'source_title', 'target_title', 'title', 'url', 'normalized_url', 'edge_type', 'confidence_class', 'canonical_resolution_status', 'review_state', 'detail', 'reason', 'review_note');
    foreach ($context_rows as $label => $rows) {
        if (!is_array($rows) || !$rows) {
            continue;
        }
        ?>
        <h5><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $label))); ?></h5>
        <?php kingy_ali_product_graph_render_opportunity_context_table($rows, $columns, __('No context rows are available.', 'kingy-ai-launch-intelligence')); ?>
        <?php
    }
}

function kingy_ali_product_graph_render_evidence_pack_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No evidence pack rows match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }

    $columns = kingy_ali_product_graph_evidence_pack_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_evidence_pack_detail_url(kingy_ali_product_graph_row_value($row, 'evidence_pack_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_opportunities_tab() {
    $opportunities = kingy_ali_product_graph_opportunities_data();
    $rows = isset($opportunities['rows']) && is_array($opportunities['rows']) ? $opportunities['rows'] : array();
    $filtered_rows = kingy_ali_product_graph_filter_opportunity_rows($rows);
    $detail_id = kingy_ali_product_graph_requested_opportunity_id();
    $detail_row = $detail_id !== '' ? kingy_ali_product_graph_find_opportunity_by_id($rows, $detail_id) : array();
    ?>
    <h2><?php esc_html_e('Graph Opportunities', 'kingy-ai-launch-intelligence'); ?></h2>
    <p><?php esc_html_e('Read-only opportunity candidates computed from the current Product Graph datasets and review overlay. This tab does not create pages, insert links, approve rows, or change graph source artifacts.', 'kingy-ai-launch-intelligence'); ?></p>

    <div class="kingy-ali-admin-cards">
        <?php kingy_ali_admin_stat_card(__('Opportunities', 'kingy-ai-launch-intelligence'), isset($opportunities['row_count']) ? absint($opportunities['row_count']) : count($rows)); ?>
        <?php kingy_ali_admin_stat_card(__('High Priority', 'kingy-ai-launch-intelligence'), isset($opportunities['counts_by_priority']['high']) ? absint($opportunities['counts_by_priority']['high']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Medium Priority', 'kingy-ai-launch-intelligence'), isset($opportunities['counts_by_priority']['medium']) ? absint($opportunities['counts_by_priority']['medium']) : 0); ?>
        <?php kingy_ali_admin_stat_card(__('Low Priority', 'kingy-ai-launch-intelligence'), isset($opportunities['counts_by_priority']['low']) ? absint($opportunities['counts_by_priority']['low']) : 0); ?>
    </div>

    <h3><?php esc_html_e('Opportunity Counts by Type', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_key_value_table(isset($opportunities['counts_by_type']) ? $opportunities['counts_by_type'] : array()); ?>

    <h3><?php esc_html_e('Opportunity Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Exports are generated from the current read-only graph datasets and reviewer metadata overlay.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php kingy_ali_product_graph_render_opportunity_export_links(); ?>

    <?php if ($detail_id !== '') : ?>
        <?php kingy_ali_product_graph_render_opportunity_detail_panel($detail_id, $detail_row); ?>
    <?php endif; ?>

    <h3><?php esc_html_e('Opportunity Queue', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php kingy_ali_product_graph_render_opportunity_filters($filtered_rows, $rows); ?>
    <?php kingy_ali_product_graph_render_opportunity_table($filtered_rows); ?>
    <?php
}

function kingy_ali_product_graph_render_opportunity_stat_chips($opportunity) {
    $chips = array(
        __('Priority', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'priority'),
        __('Confidence', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'confidence'),
        __('Review state', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'review_state'),
        __('Source type', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'source_type'),
        __('Target type', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'target_type'),
    );
    $priority = kingy_ali_product_graph_row_value($opportunity, 'priority');
    ?>
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin: 12px 0;">
        <?php foreach ($chips as $label => $value) : ?>
            <?php
            $is_high_priority = $label === __('Priority', 'kingy-ai-launch-intelligence') && $priority === 'high';
            $style = $is_high_priority
                ? 'display:inline-flex; gap:4px; align-items:center; border:1px solid #b91c1c; background:#fff1f2; color:#991b1b; border-radius:999px; padding:4px 10px; font-weight:600;'
                : 'display:inline-flex; gap:4px; align-items:center; border:1px solid #c3c4c7; background:#f6f7f7; color:#1d2327; border-radius:999px; padding:4px 10px;';
            ?>
            <span style="<?php echo esc_attr($style); ?>">
                <strong><?php echo esc_html($label); ?>:</strong>
                <?php echo esc_html($value !== '' ? $value : __('(blank)', 'kingy-ai-launch-intelligence')); ?>
            </span>
        <?php endforeach; ?>
    </div>
    <?php
}

function kingy_ali_product_graph_render_opportunity_quick_reference($opportunity) {
    $references = array(
        __('Opportunity ID', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'opportunity_id'),
        __('Source node ID', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'source_node'),
        __('Target candidate', 'kingy-ai-launch-intelligence') => kingy_ali_product_graph_row_value($opportunity, 'target_candidate'),
    );
    ?>
    <h4><?php esc_html_e('Opportunity Reference', 'kingy-ai-launch-intelligence'); ?></h4>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px; margin: 10px 0 16px;">
        <?php foreach ($references as $label => $value) : ?>
            <div style="border:1px solid #dcdcde; background:#fff; padding:10px;">
                <strong style="display:block; margin-bottom:6px;"><?php echo esc_html($label); ?></strong>
                <code style="display:block; white-space:normal; word-break:break-word;"><?php echo esc_html($value !== '' ? $value : __('(blank)', 'kingy-ai-launch-intelligence')); ?></code>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function kingy_ali_product_graph_render_opportunity_review_guidance($opportunity) {
    $guidance = kingy_ali_product_graph_opportunity_review_guidance($opportunity);
    $questions = isset($guidance['questions']) && is_array($guidance['questions']) ? $guidance['questions'] : array();
    $safe_next_actions = isset($guidance['safe_next_actions']) && is_array($guidance['safe_next_actions']) ? $guidance['safe_next_actions'] : array();
    ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin: 14px 0 18px;">
        <div style="border-left:4px solid #2271b1; background:#f6f7f7; padding:12px 14px;">
            <h4 style="margin-top:0;"><?php esc_html_e('Suggested Reviewer Questions', 'kingy-ai-launch-intelligence'); ?></h4>
            <ul style="list-style:disc; margin-left:18px;">
                <?php foreach ($questions as $question) : ?>
                    <li><?php echo esc_html($question); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div style="border-left:4px solid #00a32a; background:#f6f7f7; padding:12px 14px;">
            <h4 style="margin-top:0;"><?php esc_html_e('Safe Next Action', 'kingy-ai-launch-intelligence'); ?></h4>
            <ul style="list-style:disc; margin-left:18px;">
                <?php foreach ($safe_next_actions as $action) : ?>
                    <li><?php echo esc_html($action); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}

function kingy_ali_product_graph_render_opportunity_detail_panel($detail_id, $opportunity) {
    ?>
    <h3><?php esc_html_e('Opportunity Detail', 'kingy-ai-launch-intelligence'); ?></h3>
    <?php if (!$opportunity) : ?>
        <div class="notice notice-warning inline">
            <p>
                <?php echo esc_html(sprintf(__('No graph opportunity matched ID %s. Nothing was changed.', 'kingy-ai-launch-intelligence'), $detail_id)); ?>
                <a href="<?php echo esc_url(kingy_ali_product_graph_opportunity_back_url()); ?>"><?php esc_html_e('Back to opportunities', 'kingy-ai-launch-intelligence'); ?></a>
            </p>
        </div>
        <?php
        return;
    endif;

    $context = kingy_ali_product_graph_opportunity_detail_context($opportunity);
    ?>
    <div class="notice notice-info inline">
        <p><?php esc_html_e('This detail panel is read-only. It does not approve opportunities, insert links, create pages, or change graph source artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <p>
        <a class="button" href="<?php echo esc_url(kingy_ali_product_graph_opportunity_back_url()); ?>"><?php esc_html_e('Back to opportunities', 'kingy-ai-launch-intelligence'); ?></a>
    </p>

    <?php kingy_ali_product_graph_render_opportunity_stat_chips($opportunity); ?>
    <?php kingy_ali_product_graph_render_opportunity_quick_reference($opportunity); ?>
    <?php kingy_ali_product_graph_render_opportunity_review_guidance($opportunity); ?>

    <h4><?php esc_html_e('Opportunity', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_key_value_table(array(
        'opportunity_id' => kingy_ali_product_graph_row_value($opportunity, 'opportunity_id'),
        'opportunity_type' => kingy_ali_product_graph_row_value($opportunity, 'opportunity_type'),
        'priority' => kingy_ali_product_graph_row_value($opportunity, 'priority'),
        'source_node_id' => kingy_ali_product_graph_row_value($opportunity, 'source_node'),
        'source_title' => kingy_ali_product_graph_row_value($opportunity, 'source_title'),
        'source_type' => kingy_ali_product_graph_row_value($opportunity, 'source_type'),
        'target_candidate' => kingy_ali_product_graph_row_value($opportunity, 'target_candidate'),
        'target_title' => kingy_ali_product_graph_row_value($opportunity, 'target_title'),
        'target_type' => kingy_ali_product_graph_row_value($opportunity, 'target_type'),
        'reason' => kingy_ali_product_graph_row_value($opportunity, 'reason'),
        'confidence' => kingy_ali_product_graph_row_value($opportunity, 'confidence'),
        'review_state' => kingy_ali_product_graph_row_value($opportunity, 'review_state'),
        'reviewer_notes' => kingy_ali_product_graph_row_value($opportunity, 'reviewer_notes'),
    )); ?>

    <h4><?php esc_html_e('Source Node Record', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_opportunity_context_table(
        $context['source_node'] ? array($context['source_node']) : array(),
        array('node_key', 'entity_type', 'node_kind', 'title', 'url', 'status', 'review_state', 'suggested_review_state'),
        __('No matching source node record was found in the loaded node dataset.', 'kingy-ai-launch-intelligence')
    ); ?>

    <?php if (!empty($context['target_node'])) : ?>
        <h4><?php esc_html_e('Target Node Record', 'kingy-ai-launch-intelligence'); ?></h4>
        <?php kingy_ali_product_graph_render_opportunity_context_table(
            array($context['target_node']),
            array('node_key', 'entity_type', 'node_kind', 'title', 'url', 'status', 'review_state', 'suggested_review_state'),
            __('No matching target node record was found in the loaded node dataset.', 'kingy-ai-launch-intelligence')
        ); ?>
    <?php endif; ?>

    <h4><?php esc_html_e('Outgoing Edges from Source', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_opportunity_context_table(
        $context['outgoing_edges'],
        array('edge_id', 'edge_type', 'source_title', 'target_title', 'target', 'confidence_class', 'status', 'review_state'),
        __('No outgoing edges were found for this source node.', 'kingy-ai-launch-intelligence')
    ); ?>

    <h4><?php esc_html_e('Incoming Edges to Source', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_opportunity_context_table(
        $context['incoming_edges'],
        array('edge_id', 'edge_type', 'source_title', 'source', 'target_title', 'confidence_class', 'status', 'review_state'),
        __('No incoming edges were found for this source node.', 'kingy-ai-launch-intelligence')
    ); ?>

    <h4><?php esc_html_e('Related Resolver Records', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_opportunity_context_table(
        $context['resolver_records'],
        array('normalized_url', 'entity_key', 'entity_type', 'node_kind', 'canonical_resolution_status', 'resolution_source', 'review_state'),
        __('No related resolver records were found.', 'kingy-ai-launch-intelligence')
    ); ?>

    <h4><?php esc_html_e('Related Unresolved Queue Records', 'kingy-ai-launch-intelligence'); ?></h4>
    <?php kingy_ali_product_graph_render_opportunity_context_table(
        $context['unresolved_records'],
        array('normalized_url', 'candidate_source_keys', 'candidate_source_kinds', 'canonical_resolution_status', 'review_note', 'review_only', 'insertable'),
        __('No related unresolved queue records were found.', 'kingy-ai-launch-intelligence')
    ); ?>
    <?php
}

function kingy_ali_product_graph_render_opportunity_context_table($rows, $columns, $empty_message) {
    if (!$rows) {
        echo '<p>' . esc_html($empty_message) . '</p>';
        return;
    }
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 25) as $row) : ?>
                <?php if (!is_array($row)) { continue; } ?>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (count($rows) > 25) : ?>
        <p><?php echo esc_html(sprintf(__('Showing 25 of %d matching context rows.', 'kingy-ai-launch-intelligence'), count($rows))); ?></p>
    <?php endif; ?>
    <?php
}

function kingy_ali_product_graph_render_opportunity_export_links() {
    ?>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_opportunities_export' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_opportunities_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download opportunities %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_filter_opportunity_rows($rows) {
    $opportunity_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_type', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_source_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_confidence', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_review_state', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_opp_search', 120));

    if ($opportunity_type === '' && $source_type === '' && $confidence === '' && $review_state === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($opportunity_type !== '' && kingy_ali_product_graph_row_value($row, 'opportunity_type') !== $opportunity_type) {
            continue;
        }
        if ($source_type !== '' && kingy_ali_product_graph_row_value($row, 'source_type') !== $source_type) {
            continue;
        }
        if ($confidence !== '' && kingy_ali_product_graph_row_value($row, 'confidence') !== $confidence) {
            continue;
        }
        if ($review_state !== '' && kingy_ali_product_graph_row_value($row, 'review_state') !== $review_state) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                kingy_ali_product_graph_row_value($row, 'opportunity_id'),
                kingy_ali_product_graph_row_value($row, 'opportunity_type'),
                kingy_ali_product_graph_row_value($row, 'source_node'),
                kingy_ali_product_graph_row_value($row, 'source_title'),
                kingy_ali_product_graph_row_value($row, 'target_candidate'),
                kingy_ali_product_graph_row_value($row, 'target_title'),
                kingy_ali_product_graph_row_value($row, 'reason'),
                kingy_ali_product_graph_row_value($row, 'reviewer_notes'),
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_unique_opportunity_values($rows, $field) {
    $values = array();
    foreach ($rows as $row) {
        $value = is_array($row) ? kingy_ali_product_graph_row_value($row, $field) : '';
        if ($value !== '') {
            $values[$value] = true;
        }
    }
    $values = array_keys($values);
    sort($values);
    return $values;
}

function kingy_ali_product_graph_render_opportunity_filters($filtered_rows, $all_rows) {
    $opportunity_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_type', 80));
    $source_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_source_type', 80));
    $confidence = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_confidence', 80));
    $review_state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_opp_review_state', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_opp_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="opportunities">
        <?php kingy_ali_product_graph_render_opportunity_filter_select('kingy_pg_opp_type', __('Opportunity type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_opportunity_values($all_rows, 'opportunity_type'), $opportunity_type); ?>
        <?php kingy_ali_product_graph_render_opportunity_filter_select('kingy_pg_opp_source_type', __('Source type', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_opportunity_values($all_rows, 'source_type'), $source_type); ?>
        <?php kingy_ali_product_graph_render_opportunity_filter_select('kingy_pg_opp_confidence', __('Confidence', 'kingy-ai-launch-intelligence'), kingy_ali_product_graph_unique_opportunity_values($all_rows, 'confidence'), $confidence); ?>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_opp_review_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($review_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search graph opportunities', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_opp_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search source, target, reason', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'opportunities'), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %1$d filtered opportunities from %2$d total.', 'kingy-ai-launch-intelligence'), count($filtered_rows), count($all_rows))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_render_opportunity_filter_select($name, $label, $values, $selected) {
    ?>
    <label style="margin-left: 8px;">
        <?php echo esc_html($label); ?>
        <select name="<?php echo esc_attr($name); ?>">
            <option value=""><?php esc_html_e('Any', 'kingy-ai-launch-intelligence'); ?></option>
            <?php foreach ($values as $value) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected, $value); ?>><?php echo esc_html($value); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}

function kingy_ali_product_graph_render_opportunity_table($rows) {
    if (!$rows) {
        echo '<p>' . esc_html__('No graph opportunities match the current filters.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }
    $columns = kingy_ali_product_graph_opportunity_columns();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Details', 'kingy-ai-launch-intelligence'); ?></th>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(kingy_ali_product_graph_opportunity_detail_url(kingy_ali_product_graph_row_value($row, 'opportunity_id'))); ?>">
                            <?php esc_html_e('View details', 'kingy-ai-launch-intelligence'); ?>
                        </a>
                    </td>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php echo esc_html(kingy_ali_product_graph_row_value($row, $column)); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_review_overlay_filters($row_count) {
    $state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_overlay_state', 80));
    $row_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_overlay_type', 80));
    $search = kingy_ali_product_graph_get_value('kingy_pg_overlay_search', 120);
    $states = kingy_ali_product_graph_allowed_review_states();
    $row_types = array_unique(array_values(kingy_ali_product_graph_reviewable_tabs()));
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="review_overlay">
        <label>
            <?php esc_html_e('State', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_overlay_state">
                <option value=""><?php esc_html_e('Any state', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($states as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($state, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <?php esc_html_e('Row type', 'kingy-ai-launch-intelligence'); ?>
            <select name="kingy_pg_overlay_type">
                <option value=""><?php esc_html_e('Any row type', 'kingy-ai-launch-intelligence'); ?></option>
                <?php foreach ($row_types as $type) : ?>
                    <option value="<?php echo esc_attr($type); ?>" <?php selected($row_type, $type); ?>><?php echo esc_html($type); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label style="margin-left: 8px;">
            <span class="screen-reader-text"><?php esc_html_e('Search review overlay', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_overlay_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search row ID, reviewer, or note', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => 'review_overlay'), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;"><?php echo esc_html(sprintf(__('Showing up to 100 of %d saved review records.', 'kingy-ai-launch-intelligence'), absint($row_count))); ?></span>
    </form>
    <?php
}

function kingy_ali_product_graph_filter_review_overlay_rows($rows) {
    $state = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_overlay_state', 80));
    $row_type = sanitize_key(kingy_ali_product_graph_get_value('kingy_pg_overlay_type', 80));
    $search = strtolower(kingy_ali_product_graph_get_value('kingy_pg_overlay_search', 120));

    if ($state === '' && $row_type === '' && $search === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        if ($state !== '' && (!isset($row['review_state']) || $row['review_state'] !== $state)) {
            continue;
        }
        if ($row_type !== '' && (!isset($row['row_type']) || $row['row_type'] !== $row_type)) {
            continue;
        }
        if ($search !== '') {
            $haystack = strtolower(implode(' ', array(
                isset($row['review_row_id']) ? $row['review_row_id'] : '',
                isset($row['reviewer_notes']) ? $row['reviewer_notes'] : '',
                isset($row['reviewer_login']) ? $row['reviewer_login'] : '',
                isset($row['reviewer_display_name']) ? $row['reviewer_display_name'] : '',
                isset($row['source_artifact_id']) ? $row['source_artifact_id'] : '',
            )));
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }
        $filtered[] = $row;
    }

    return $filtered;
}

function kingy_ali_product_graph_render_review_save_notice() {
    $saved = kingy_ali_product_graph_get_value('kingy_pg_review_saved', 10);
    if ($saved === '') {
        return;
    }

    $class = $saved === '1' ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
    $message = $saved === '1'
        ? __('Product Graph review metadata saved. No graph artifact or WordPress content was changed.', 'kingy-ai-launch-intelligence')
        : __('Product Graph review metadata was not saved because the row, state, or nonce was invalid.', 'kingy-ai-launch-intelligence');
    ?>
    <div class="<?php echo esc_attr($class); ?>">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

function kingy_ali_product_graph_render_key_value_table($items) {
    if (!$items) {
        echo '<p>' . esc_html__('No data available.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }
    ?>
    <table class="widefat striped">
        <tbody>
            <?php foreach ($items as $key => $value) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $key))); ?></th>
                    <td><?php echo esc_html(kingy_ali_product_graph_display_value($value)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_list($items) {
    if (!$items || !is_array($items)) {
        echo '<p>' . esc_html__('No data available.', 'kingy-ai-launch-intelligence') . '</p>';
        return;
    }
    ?>
    <ul>
        <?php foreach ($items as $item) : ?>
            <li><?php echo esc_html(kingy_ali_product_graph_display_value($item)); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_dataset_toolbar($active_tab, $tab, $row_count) {
    $filter = kingy_ali_product_graph_get_value('kingy_pg_filter', 120);
    ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin: 16px 0;">
        <input type="hidden" name="page" value="kingy-ali-product-graph">
        <input type="hidden" name="kingy_pg_tab" value="<?php echo esc_attr($active_tab); ?>">
        <label>
            <span class="screen-reader-text"><?php esc_html_e('Filter rows', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="search" name="kingy_pg_filter" value="<?php echo esc_attr($filter); ?>" placeholder="<?php esc_attr_e('Filter visible dataset', 'kingy-ai-launch-intelligence'); ?>">
        </label>
        <button class="button" type="submit"><?php esc_html_e('Filter', 'kingy-ai-launch-intelligence'); ?></button>
        <a class="button" href="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => $active_tab), admin_url('admin.php'))); ?>"><?php esc_html_e('Clear', 'kingy-ai-launch-intelligence'); ?></a>
        <span style="margin-left: 10px;">
            <?php echo esc_html(sprintf(__('Showing up to 100 of %d rows.', 'kingy-ai-launch-intelligence'), absint($row_count))); ?>
        </span>
    </form>
    <?php
}

function kingy_ali_product_graph_filter_rows($rows, $filter_fields) {
    $filter = strtolower(kingy_ali_product_graph_get_value('kingy_pg_filter', 120));
    if ($filter === '') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $haystack = array();
        foreach ($filter_fields as $field) {
            $haystack[] = kingy_ali_product_graph_display_value(isset($row[$field]) ? $row[$field] : '');
        }
        $haystack[] = kingy_ali_product_graph_display_value(isset($row['title']) ? $row['title'] : '');
        $haystack[] = kingy_ali_product_graph_display_value(isset($row['url']) ? $row['url'] : '');
        $haystack[] = kingy_ali_product_graph_display_value(isset($row['normalized_url']) ? $row['normalized_url'] : '');

        if (strpos(strtolower(implode(' ', $haystack)), $filter) !== false) {
            $filtered[] = $row;
        }
    }

    return $filtered;
}

function kingy_ali_product_graph_render_table($rows, $columns, $active_tab = '', $dataset = '') {
    $rows = array_slice($rows, 0, 100);
    $row_type = kingy_ali_product_graph_review_type_for_tab($active_tab);
    $is_reviewable = $row_type !== '';
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th scope="col"><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $column))); ?></th>
                <?php endforeach; ?>
                <?php if ($is_reviewable) : ?>
                    <th scope="col"><?php esc_html_e('Review Overlay', 'kingy-ai-launch-intelligence'); ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row) : ?>
                <?php if (!is_array($row)) { continue; } ?>
                <tr>
                    <?php foreach ($columns as $column) : ?>
                        <td><?php kingy_ali_product_graph_render_cell(isset($row[$column]) ? $row[$column] : '', $column); ?></td>
                    <?php endforeach; ?>
                    <?php if ($is_reviewable) : ?>
                        <td><?php kingy_ali_product_graph_render_review_controls($row, $row_type, $dataset); ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function kingy_ali_product_graph_render_review_controls($row, $row_type, $dataset, $return_context = array()) {
    if (!is_array($row) || empty($row['review_row_id'])) {
        echo esc_html__('No review row ID available.', 'kingy-ai-launch-intelligence');
        return;
    }

    $files = kingy_ali_product_graph_dataset_files();
    $derived_artifact_ids = array(
        'work_queue' => 'derived-product-graph-work-queue',
        'link_recommendations' => 'derived-product-graph-link-recommendations',
    );
    $source_artifact_id = isset($files[$dataset]) ? $files[$dataset] : (isset($derived_artifact_ids[$dataset]) ? $derived_artifact_ids[$dataset] : '');
    $row_id = kingy_ali_product_graph_sanitize_row_id($row['review_row_id']);
    $record = kingy_ali_product_graph_review_record($row_type, $row_id);
    $allowed_states = kingy_ali_product_graph_allowed_review_states();
    $current_state = isset($record['review_state']) && isset($allowed_states[$record['review_state']])
        ? $record['review_state']
        : (isset($row['review_state']) && isset($allowed_states[$row['review_state']]) ? $row['review_state'] : 'unreviewed');
    $notes = isset($record['reviewer_notes']) ? (string) $record['reviewer_notes'] : '';
    ?>
    <form method="post" action="<?php echo esc_url(add_query_arg(array('page' => 'kingy-ali-product-graph', 'kingy_pg_tab' => kingy_ali_product_graph_review_tab_for_type($row_type)), admin_url('admin.php'))); ?>" style="min-width: 260px;">
        <?php wp_nonce_field('kingy_ali_product_graph_review_state', 'kingy_pg_review_nonce'); ?>
        <input type="hidden" name="kingy_pg_review_action" value="save_review_state">
        <input type="hidden" name="kingy_pg_row_type" value="<?php echo esc_attr($row_type); ?>">
        <input type="hidden" name="kingy_pg_review_row_id" value="<?php echo esc_attr($row_id); ?>">
        <input type="hidden" name="kingy_pg_source_artifact_id" value="<?php echo esc_attr($source_artifact_id); ?>">
        <?php if (is_array($return_context) && isset($return_context['return_tab']) && sanitize_key($return_context['return_tab']) === 'source_context_audit') : ?>
            <input type="hidden" name="kingy_pg_review_return_tab" value="source_context_audit">
            <input type="hidden" name="kingy_pg_review_return_source_context_audit_id" value="<?php echo esc_attr(isset($return_context['source_context_audit_id']) ? kingy_ali_product_graph_sanitize_row_id($return_context['source_context_audit_id']) : ''); ?>">
        <?php endif; ?>
        <p style="margin: 0 0 6px;">
            <code><?php echo esc_html($row_id); ?></code>
        </p>
        <label>
            <span class="screen-reader-text"><?php esc_html_e('Review state', 'kingy-ai-launch-intelligence'); ?></span>
            <select name="kingy_pg_review_state">
                <?php foreach ($allowed_states as $state => $label) : ?>
                    <option value="<?php echo esc_attr($state); ?>" <?php selected($current_state, $state); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p style="margin: 6px 0;">
            <label>
                <span class="screen-reader-text"><?php esc_html_e('Reviewer notes', 'kingy-ai-launch-intelligence'); ?></span>
                <textarea name="kingy_pg_reviewer_notes" rows="2" style="width: 100%; max-width: 360px;" placeholder="<?php esc_attr_e('Reviewer note', 'kingy-ai-launch-intelligence'); ?>"><?php echo esc_textarea($notes); ?></textarea>
            </label>
        </p>
        <button class="button button-small" type="submit"><?php esc_html_e('Save Review State', 'kingy-ai-launch-intelligence'); ?></button>
        <?php if ($record) : ?>
            <p style="margin: 6px 0 0;">
                <small>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('Saved by %1$s at %2$s UTC.', 'kingy-ai-launch-intelligence'),
                            isset($record['reviewer_display_name']) && $record['reviewer_display_name'] !== '' ? $record['reviewer_display_name'] : __('unknown reviewer', 'kingy-ai-launch-intelligence'),
                            isset($record['reviewed_at_utc']) ? $record['reviewed_at_utc'] : ''
                        )
                    );
                    ?>
                </small>
            </p>
        <?php endif; ?>
    </form>
    <?php
}

function kingy_ali_product_graph_render_cell($value, $column) {
    if ($column === 'path' && is_string($value)) {
        $file = basename($value);
        if (kingy_ali_product_graph_report_path($file)) {
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_download' => $file,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_download_' . $file
            );
            echo '<a href="' . esc_url($url) . '">' . esc_html($file) . '</a><br><code>' . esc_html($value) . '</code>';
            return;
        }
    }

    if (in_array($column, array('url', 'normalized_url', 'source_url', 'target_url'), true) && is_string($value) && preg_match('#^https?://#', $value)) {
        echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>';
        return;
    }

    if (in_array($column, array('node_key', 'edge_id', 'source', 'target', 'entity_key'), true)) {
        echo '<code>' . esc_html(kingy_ali_product_graph_display_value($value)) . '</code>';
        return;
    }

    echo esc_html(kingy_ali_product_graph_display_value($value));
}

function kingy_ali_product_graph_display_value($value) {
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_array($value)) {
        $parts = array();
        foreach ($value as $item) {
            $parts[] = is_scalar($item) ? (string) $item : wp_json_encode($item);
        }
        return implode(', ', $parts);
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    return '';
}

function kingy_ali_product_graph_render_missing_artifacts_notice() {
    ?>
    <div class="notice notice-warning">
        <p><?php esc_html_e('Product Graph review artifacts were not found on this server.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><code><?php echo esc_html('python3 tools/kingy_product_graph_review_screen_mvp.py'); ?></code></p>
        <p><?php esc_html_e('The admin page is read-only and will render datasets once the local artifacts exist under the configured reports directory.', 'kingy-ai-launch-intelligence'); ?></p>
    </div>
    <?php
}

function kingy_ali_product_graph_render_missing_dataset($dataset) {
    ?>
    <div class="notice notice-warning">
        <p>
            <?php
            echo esc_html(
                sprintf(
                    __('The Product Graph %s dataset is unavailable. Regenerate local review datasets and refresh this page.', 'kingy-ai-launch-intelligence'),
                    sanitize_key($dataset)
                )
            );
            ?>
        </p>
    </div>
    <?php
}

function kingy_ali_product_graph_model_inventory_gap($summary) {
    return is_array($summary)
        && isset($summary['model_inventory_status'])
        && $summary['model_inventory_status'] === 'model_inventory_gap';
}

function kingy_ali_product_graph_render_download_list() {
    $files = kingy_ali_product_graph_downloadable_files();
    ?>
    <h3><?php esc_html_e('Downloads', 'kingy-ai-launch-intelligence'); ?></h3>
    <p><?php esc_html_e('Downloads are constrained to Product Graph review JSON, CSV, and summary artifacts.', 'kingy-ai-launch-intelligence'); ?></p>
    <ul>
        <?php foreach ($files as $file) : ?>
            <?php if (!kingy_ali_product_graph_report_path($file)) { continue; } ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_download' => $file,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_download_' . $file
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($file); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function kingy_ali_product_graph_render_review_overlay_downloads() {
    $records_count = count(kingy_ali_product_graph_flatten_review_overlay());
    ?>
    <h3><?php esc_html_e('Review Overlay Export', 'kingy-ai-launch-intelligence'); ?></h3>
    <p>
        <?php
        echo esc_html(
            sprintf(
                __('Reviewer metadata is stored separately from graph artifacts. Current overlay records: %d.', 'kingy-ai-launch-intelligence'),
                absint($records_count)
            )
        );
        ?>
    </p>
    <ul>
        <?php foreach (array('json', 'csv') as $format) : ?>
            <?php
            $url = wp_nonce_url(
                add_query_arg(
                    array(
                        'page' => 'kingy-ali-product-graph',
                        'kingy_pg_review_overlay_download' => $format,
                    ),
                    admin_url('admin.php')
                ),
                'kingy_ali_product_graph_review_overlay_download_' . $format
            );
            ?>
            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html(sprintf(__('Download review overlay %s', 'kingy-ai-launch-intelligence'), strtoupper($format))); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}
