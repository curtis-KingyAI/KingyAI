<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_kingy_ali_track_click', 'kingy_ali_ajax_track_click');
add_action('wp_ajax_nopriv_kingy_ali_track_click', 'kingy_ali_ajax_track_click');

function kingy_ali_analytics_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'kingy_ali_events';
}

function kingy_ali_create_analytics_table() {
    global $wpdb;

    static $created_this_request = false;
    if ($created_this_request) {
        return;
    }

    $schema_version = defined('KINGY_ALI_VERSION') ? KINGY_ALI_VERSION : '1';
    if (get_option('kingy_ali_analytics_table_ready_version', '') === $schema_version) {
        $created_this_request = true;
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = kingy_ali_analytics_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_type varchar(60) NOT NULL,
        event_label varchar(191) NOT NULL DEFAULT '',
        query_text varchar(255) NOT NULL DEFAULT '',
        filters longtext NULL,
        object_id bigint(20) unsigned NULL,
        result_count int(11) NULL,
        ip_hash varchar(64) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY event_type (event_type),
        KEY object_id (object_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('kingy_ali_analytics_table_ready_version', $schema_version, false);
    $created_this_request = true;
}

function kingy_ali_sanitize_event_text($value, $max_length = 191) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = sanitize_text_field((string) $value);
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);
    $value = trim((string) $value);

    if ($max_length > 0 && strlen($value) > $max_length) {
        $value = wp_html_excerpt($value, $max_length, '');
    }

    return $value;
}

function kingy_ali_sanitize_event_filters($filters) {
    if (!is_array($filters)) {
        return array();
    }

    $clean = array();
    foreach ($filters as $key => $value) {
        if (count($clean) >= 40) {
            break;
        }

        $key = sanitize_key((string) $key);
        if ($key === '') {
            continue;
        }

        $value = kingy_ali_sanitize_event_filter_value($value);
        if ($value === '' || $value === null) {
            continue;
        }

        $clean[$key] = $value;
    }

    return $clean;
}

function kingy_ali_sanitize_event_filter_value($value) {
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value)) {
        return $value;
    }

    if (is_float($value)) {
        return is_finite($value) ? $value : null;
    }

    if (is_array($value) || is_object($value)) {
        return null;
    }

    return kingy_ali_sanitize_event_text($value, 500);
}

function kingy_ali_allowed_click_event_types() {
    return apply_filters(
        'kingy_ali_allowed_click_event_types',
        array(
            'academy_certificate_generated',
            'academy_certificate_printed',
            'academy_capstone_completed',
            'academy_dashboard_viewed',
            'academy_quiz_completed',
            'academy_quiz_retaken',
            'academy_quiz_started',
            'academy_template_copied',
            'academy_template_downloaded',
            'clicked_academy_cta',
            'clicked_category_path',
            'clicked_client_examples_cta',
            'clicked_codex_article_resource',
            'clicked_company',
            'clicked_contact_cta',
            'clicked_correction_cta',
            'clicked_directory_reset',
            'clicked_filter',
            'clicked_filter_reset',
            'clicked_launch',
            'clicked_official_tool_link',
            'clicked_replit_resource',
            'clicked_roi_calculator',
            'clicked_safe_agent_resource',
            'clicked_source_link',
            'clicked_sponsorship_cta',
            'clicked_submit_cta',
            'clicked_tool',
            'clicked_vibe_resource',
            'clicked_visibility_score_cta',
        )
    );
}

function kingy_ali_sanitize_click_event_type($event_type) {
    if (!is_scalar($event_type)) {
        return '';
    }

    $event_type = sanitize_key((string) $event_type);
    return in_array($event_type, kingy_ali_allowed_click_event_types(), true) ? $event_type : '';
}

function kingy_ali_sanitize_analytics_target_url($url) {
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

    $scheme = wp_parse_url($url, PHP_URL_SCHEME);
    $host = wp_parse_url($url, PHP_URL_HOST);
    if (!in_array($scheme, array('http', 'https'), true) || empty($host)) {
        return '';
    }

    return $url;
}

function kingy_ali_analytics_post_value($key) {
    $values = kingy_ali_analytics_post_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
        return '';
    }

    $value = wp_unslash($values[$key]);
    return is_scalar($value) ? (string) $value : '';
}

function kingy_ali_analytics_post_values() {
    return is_array($_POST) ? $_POST : array();
}

if (!function_exists('kingy_ali_request_server_values')) {
    function kingy_ali_request_server_values() {
        return is_array($_SERVER) ? $_SERVER : array();
    }
}

if (!function_exists('kingy_ali_request_remote_addr')) {
    function kingy_ali_request_remote_addr() {
        $values = kingy_ali_request_server_values();
        if (!isset($values['REMOTE_ADDR'])) {
            return '';
        }

        if (!is_scalar($values['REMOTE_ADDR'])) {
            return '';
        }

        $value = wp_unslash($values['REMOTE_ADDR']);
        if (!is_scalar($value)) {
            return '';
        }

        $value = sanitize_text_field((string) $value);
        return strlen($value) > 100 ? substr($value, 0, 100) : $value;
    }
}

function kingy_ali_track_event($event_type, $args = array()) {
    global $wpdb;

    $defaults = array(
        'event_label' => '',
        'query_text' => '',
        'filters' => array(),
        'object_id' => null,
        'result_count' => null,
    );
    $args = wp_parse_args($args, $defaults);

    $ip = kingy_ali_request_remote_addr();
    $ip_hash = $ip ? hash('sha256', wp_salt('nonce') . $ip) : '';

    $wpdb->insert(
        kingy_ali_analytics_table_name(),
        array(
            'event_type' => sanitize_key($event_type),
            'event_label' => kingy_ali_sanitize_event_text($args['event_label'], 191),
            'query_text' => kingy_ali_sanitize_event_text($args['query_text'], 255),
            'filters' => wp_json_encode(kingy_ali_sanitize_event_filters($args['filters'])),
            'object_id' => $args['object_id'] ? absint($args['object_id']) : null,
            'result_count' => $args['result_count'] === null ? null : (int) $args['result_count'],
            'ip_hash' => $ip_hash,
            'created_at' => current_time('mysql'),
        ),
        array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
    );
}

function kingy_ali_ajax_track_click() {
    check_ajax_referer('kingy_ali_track_click', 'nonce');

    $event_type = kingy_ali_sanitize_click_event_type(kingy_ali_analytics_post_value('eventType'));
    if ($event_type === '') {
        wp_send_json_error(array('message' => __('Invalid analytics event type.', 'kingy-ai-launch-intelligence')), 400);
    }

    $event_label = kingy_ali_sanitize_event_text(kingy_ali_analytics_post_value('eventLabel'), 191);
    $object_id = absint(kingy_ali_analytics_post_value('objectId'));
    $event_surface = sanitize_key(kingy_ali_analytics_post_value('eventSurface'));
    $target_url = kingy_ali_sanitize_analytics_target_url(kingy_ali_analytics_post_value('targetUrl'));

    kingy_ali_track_event(
        $event_type,
        array(
            'event_label' => $event_label,
            'object_id' => $object_id,
            'filters' => array_filter(
                array(
                    'surface' => $event_surface,
                    'target_url' => $target_url,
                )
            ),
        )
    );

    wp_send_json_success();
}

function kingy_ali_analytics_since($days = 7) {
    $days = max(1, absint($days));
    return date_i18n('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));
}

function kingy_ali_recent_events($days = 7, $limit = 1000) {
    global $wpdb;

    kingy_ali_create_analytics_table();
    $table = kingy_ali_analytics_table_name();
    $since = kingy_ali_analytics_since($days);

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT event_type, event_label, query_text, filters, object_id, result_count, created_at
            FROM {$table}
            WHERE created_at >= %s
            ORDER BY created_at DESC
            LIMIT %d",
            $since,
            absint($limit)
        )
    );
}

function kingy_ali_aggregate_event_filters($events, $filter_key, $event_type = '') {
    $counts = array();

    foreach ($events as $event) {
        if ($event_type && $event->event_type !== $event_type) {
            continue;
        }

        $filters = json_decode((string) $event->filters, true);
        if (!is_array($filters) || empty($filters[$filter_key])) {
            continue;
        }

        if (!is_scalar($filters[$filter_key])) {
            continue;
        }

        $value = sanitize_text_field($filters[$filter_key]);
        if ($value === '') {
            continue;
        }

        if (!isset($counts[$value])) {
            $counts[$value] = 0;
        }
        $counts[$value]++;
    }

    arsort($counts);
    return $counts;
}

function kingy_ali_high_intent_searches($events) {
    $intent_terms = array('sponsor', 'coverage', 'youtube', 'demo', 'pricing', 'roi', 'launch', 'founder', 'agency', 'alternatives', 'free', 'api', 'open source', 'open weight');
    $counts = array();

    foreach ($events as $event) {
        if (!in_array($event->event_type, array('search', 'zero_result_search'), true) || $event->query_text === '') {
            continue;
        }

        $query = strtolower((string) $event->query_text);
        foreach ($intent_terms as $term) {
            if (strpos($query, $term) !== false) {
                if (!isset($counts[$event->query_text])) {
                    $counts[$event->query_text] = 0;
                }
                $counts[$event->query_text]++;
                break;
            }
        }
    }

    arsort($counts);
    return $counts;
}
