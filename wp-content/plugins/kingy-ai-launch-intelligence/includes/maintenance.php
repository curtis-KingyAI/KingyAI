<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_kingy_ali_run_maintenance', 'kingy_ali_handle_run_maintenance');

function kingy_ali_render_maintenance_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $results_value = kingy_ali_maintenance_request_value('get', 'maintenance_results', 1200);
    $results = $results_value !== '' ? kingy_ali_decode_maintenance_results($results_value) : array();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Launch Intelligence Maintenance', 'kingy-ai-launch-intelligence'); ?></h1>
        <p><?php esc_html_e('Rebuild relationships and derived data after large imports, schema changes, or manual cleanup.', 'kingy-ai-launch-intelligence'); ?></p>

        <?php if ($results) : ?>
            <div class="notice notice-success">
                <p>
                    <?php
                    echo esc_html(
                        sprintf(
                            __('Maintenance complete. Launches: %1$d. Tools: %2$d. Companies: %3$d. Scores applied: %4$d. Attribute syncs: %5$d. Latest-launch syncs: %6$d.', 'kingy-ai-launch-intelligence'),
                            absint($results['launches']),
                            absint($results['tools']),
                            absint($results['companies']),
                            absint($results['scores']),
                            absint($results['attributes']),
                            absint($results['latest_launches'])
                        )
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kingy_ali_run_maintenance', 'kingy_ali_run_maintenance_nonce'); ?>
            <input type="hidden" name="action" value="kingy_ali_run_maintenance">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Rebuild graph', 'kingy-ai-launch-intelligence'); ?></th>
                        <td>
                            <p><?php esc_html_e('Creates or repairs launch-to-tool, launch/tool-to-company, latest-launch, company graph, and derived attribute links across existing records.', 'kingy-ai-launch-intelligence'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Scores', 'kingy-ai-launch-intelligence'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="apply_suggested_scores" value="1">
                                <?php esc_html_e('Apply suggested Kingy scores to all launch records', 'kingy-ai-launch-intelligence'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><button class="button button-primary" type="submit"><?php esc_html_e('Run Maintenance', 'kingy-ai-launch-intelligence'); ?></button></p>
        </form>
    </div>
    <?php
}

function kingy_ali_handle_run_maintenance() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to run Launch Intelligence maintenance.', 'kingy-ai-launch-intelligence'));
    }

    if (!wp_verify_nonce(sanitize_text_field(kingy_ali_maintenance_request_value('post', 'kingy_ali_run_maintenance_nonce', 120)), 'kingy_ali_run_maintenance')) {
        wp_die(esc_html__('Maintenance nonce check failed.', 'kingy-ai-launch-intelligence'));
    }

    $apply_suggested_scores = kingy_ali_maintenance_request_value('post', 'apply_suggested_scores', 10) === '1';
    $results = kingy_ali_run_maintenance($apply_suggested_scores);
    wp_safe_redirect(
        add_query_arg(
            'maintenance_results',
            rawurlencode(wp_json_encode($results)),
            admin_url('admin.php?page=kingy-ali-maintenance')
        )
    );
    exit;
}

function kingy_ali_maintenance_request_value($source, $key, $max_length = 191) {
    $source_values = kingy_ali_maintenance_request_source($source);
    if (!isset($source_values[$key])) {
        return '';
    }

    if (!is_scalar($source_values[$key])) {
        return '';
    }

    $value = wp_unslash($source_values[$key]);
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

function kingy_ali_maintenance_request_source($source) {
    if ($source === 'post') {
        return is_array($_POST) ? $_POST : array();
    }

    if ($source === 'get') {
        return is_array($_GET) ? $_GET : array();
    }

    return array();
}

function kingy_ali_run_maintenance($apply_suggested_scores = false) {
    kingy_ali_seed_default_terms();
    kingy_ali_ensure_derived_attribute_terms();
    kingy_ali_create_analytics_table();

    $results = array(
        'launches' => 0,
        'tools' => 0,
        'companies' => 0,
        'scores' => 0,
        'attributes' => 0,
        'latest_launches' => 0,
    );

    $launch_ids = kingy_ali_maintenance_post_ids('kingy_ai_launch');
    foreach ($launch_ids as $launch_id) {
        $results['launches']++;
        $tool_id = kingy_ali_maintenance_sync_launch_tool($launch_id);
        $company_id = kingy_ali_sync_company_from_launch($launch_id);
        if ($company_id && $tool_id) {
            kingy_ali_link_tool_to_company($tool_id, $company_id);
        }

        if ($apply_suggested_scores) {
            foreach (kingy_ali_suggest_launch_scores($launch_id) as $key => $score) {
                update_post_meta($launch_id, kingy_ali_meta_key($key), $score);
            }
            $results['scores']++;
        }

        kingy_ali_sync_derived_attributes($launch_id);
        $results['attributes']++;

        if ($tool_id) {
            kingy_ali_sync_derived_attributes($tool_id);
            $results['attributes']++;
        }

        if ($company_id) {
            kingy_ali_sync_derived_attributes($company_id);
            $results['attributes']++;
        }
    }

    foreach (kingy_ali_maintenance_post_ids('kingy_ai_tool') as $tool_id) {
        $results['tools']++;
        $company_id = kingy_ali_sync_company_from_tool($tool_id);
        kingy_ali_update_tool_latest_launch($tool_id);
        $results['latest_launches']++;
        kingy_ali_sync_derived_attributes($tool_id);
        $results['attributes']++;
        if ($company_id) {
            kingy_ali_sync_derived_attributes($company_id);
            $results['attributes']++;
        }
    }

    foreach (kingy_ali_maintenance_post_ids('kingy_ai_company') as $company_id) {
        $results['companies']++;
        kingy_ali_sync_derived_attributes($company_id);
        $results['attributes']++;
    }

    return $results;
}

function kingy_ali_maintenance_post_ids($post_type) {
    $query = new WP_Query(
        array(
            'post_type' => $post_type,
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        )
    );

    return array_map('absint', $query->posts);
}

function kingy_ali_maintenance_sync_launch_tool($launch_id) {
    $tool_id = kingy_ali_maintenance_related_id(kingy_ali_get_meta($launch_id, 'related_tool_id'));
    if ($tool_id && get_post_type($tool_id) === 'kingy_ai_tool') {
        kingy_ali_link_launch_to_tool($launch_id, $tool_id);
        return $tool_id;
    }

    return kingy_ali_sync_tool_from_launch($launch_id);
}

function kingy_ali_maintenance_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_decode_maintenance_results($encoded) {
    if (!is_scalar($encoded) || strlen((string) $encoded) > 1200) {
        return array();
    }

    $decoded = json_decode(rawurldecode($encoded), true);
    if (!is_array($decoded)) {
        return array();
    }

    $results = array(
        'launches' => 0,
        'tools' => 0,
        'companies' => 0,
        'scores' => 0,
        'attributes' => 0,
        'latest_launches' => 0,
    );

    foreach ($results as $key => $default) {
        if (isset($decoded[$key]) && is_scalar($decoded[$key])) {
            $results[$key] = absint($decoded[$key]);
        }
    }

    return $results;
}
