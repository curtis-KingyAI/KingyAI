<?php
/**
 * Privacy-bounded adoption measurement for the 30-day feeds validation window.
 *
 * This is internal aggregate telemetry, not a public data API. It stores no
 * raw IP address, user agent, referrer URL, query text, cookie, or article URL.
 * Client hashes are salted, scoped to one measurement window, and retained for
 * only 60 days so repeat polling can be estimated without durable identifiers.
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kingy_ali_feed_metrics_maybe_schedule', 120);
add_action('send_headers', 'kingy_ali_feed_metrics_capture_rss_request', 25);
add_action('wp_ajax_kingy_ali_feed_metric', 'kingy_ali_feed_metrics_ajax');
add_action('wp_ajax_nopriv_kingy_ali_feed_metric', 'kingy_ali_feed_metrics_ajax');
add_action('kingy_ali_feed_metrics_health_check', 'kingy_ali_feed_metrics_health_check');
add_action('kingy_ali_feed_metrics_cleanup', 'kingy_ali_feed_metrics_cleanup');

function kingy_ali_feed_metrics_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'kingy_ali_feed_metrics';
}

function kingy_ali_feed_metrics_schema_version() {
    return '2026-07-28-v1';
}

function kingy_ali_feed_metrics_create_table() {
    global $wpdb;
    $version = kingy_ali_feed_metrics_schema_version();
    if (get_option('kingy_ali_feed_metrics_schema', '') === $version) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = kingy_ali_feed_metrics_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        window_id varchar(20) NOT NULL,
        metric_type varchar(32) NOT NULL,
        route_kind varchar(32) NOT NULL,
        route_slug varchar(100) NOT NULL DEFAULT '',
        client_hash char(64) NOT NULL,
        client_class varchar(24) NOT NULL DEFAULT 'unknown',
        client_family varchar(40) NOT NULL DEFAULT 'unknown',
        source_hash char(64) NOT NULL,
        source_host varchar(191) NOT NULL DEFAULT '',
        requests bigint(20) unsigned NOT NULL DEFAULT 0,
        active_days bigint(20) unsigned NOT NULL DEFAULT 0,
        first_seen datetime NOT NULL,
        last_seen datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY metric_client (window_id, metric_type, route_kind, route_slug(50), client_hash, source_hash),
        KEY window_metric (window_id, metric_type),
        KEY route_lookup (route_kind, route_slug(50)),
        KEY last_seen (last_seen)
    ) {$charset_collate};";
    dbDelta($sql);
    update_option('kingy_ali_feed_metrics_schema', $version, false);
}

function kingy_ali_feed_metrics_window() {
    $start = (string) get_option('kingy_ali_feed_metrics_start_gmt', '');
    $end = (string) get_option('kingy_ali_feed_metrics_end_gmt', '');
    $start_ts = $start !== '' ? strtotime($start . ' UTC') : 0;
    $end_ts = $end !== '' ? strtotime($end . ' UTC') : 0;
    return array(
        'id' => $start_ts ? gmdate('Y-m-d', $start_ts) : '',
        'start' => $start,
        'end' => $end,
        'start_ts' => $start_ts,
        'end_ts' => $end_ts,
    );
}

function kingy_ali_feed_metrics_window_active() {
    $window = kingy_ali_feed_metrics_window();
    $now = time();
    return $window['start_ts'] > 0 && $window['end_ts'] > $window['start_ts']
        && $now >= $window['start_ts'] && $now < $window['end_ts'];
}

function kingy_ali_feed_metrics_maybe_schedule() {
    if (kingy_ali_feed_metrics_window_active() && !wp_next_scheduled('kingy_ali_feed_metrics_health_check')) {
        wp_schedule_event(time() + 300, 'hourly', 'kingy_ali_feed_metrics_health_check');
    }
    if (!wp_next_scheduled('kingy_ali_feed_metrics_cleanup')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'kingy_ali_feed_metrics_cleanup');
    }
}

function kingy_ali_feed_metrics_request_method_is_get() {
    $method = isset($_SERVER['REQUEST_METHOD']) && is_scalar($_SERVER['REQUEST_METHOD'])
        ? strtoupper((string) wp_unslash($_SERVER['REQUEST_METHOD']))
        : 'GET';
    return $method === 'GET';
}

function kingy_ali_feed_metrics_request_method_is_readable() {
    $method = isset($_SERVER['REQUEST_METHOD']) && is_scalar($_SERVER['REQUEST_METHOD'])
        ? strtoupper((string) wp_unslash($_SERVER['REQUEST_METHOD']))
        : 'GET';
    return in_array($method, array('GET', 'HEAD'), true);
}

function kingy_ali_feed_metrics_client_classification() {
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT'])
        ? strtolower((string) wp_unslash($_SERVER['HTTP_USER_AGENT']))
        : '';
    if (is_user_logged_in()) {
        return array('class' => 'internal', 'family' => 'kingy_editor');
    }

    $readers = array(
        'feedly' => 'feedly',
        'feedbin' => 'feedbin',
        'inoreader' => 'inoreader',
        'newsblur' => 'newsblur',
        'freshrss' => 'freshrss',
        'miniflux' => 'miniflux',
        'tiny tiny rss' => 'tt_rss',
        'tt-rss' => 'tt_rss',
        'netnewswire' => 'netnewswire',
        'reeder' => 'reeder',
        'thunderbird' => 'thunderbird',
        'feedfetcher' => 'feedfetcher',
    );
    foreach ($readers as $needle => $family) {
        if (strpos($user_agent, $needle) !== false) {
            return array('class' => 'feed_reader', 'family' => $family);
        }
    }

    if (preg_match('/kingyfeedcontractqa|kingy feed contract|codex|curl\/|wget\/|postman|insomnia/', $user_agent)) {
        return array('class' => 'qa', 'family' => 'qa_tool');
    }
    if (preg_match('/kingyfeedshealth|uptime|pingdom|gtmetrix|lighthouse|pagespeed|monitor/', $user_agent)) {
        return array('class' => 'monitor', 'family' => 'health_monitor');
    }
    if (preg_match('/bot|crawler|spider|slurp|facebookexternalhit|slackbot|discordbot|preview/', $user_agent)) {
        return array('class' => 'crawler', 'family' => 'crawler');
    }
    if (preg_match('/python-requests|python-urllib|go-http-client|okhttp|libwww|java\//', $user_agent)) {
        return array('class' => 'library', 'family' => 'http_library');
    }
    if (preg_match('/mozilla|chrome|safari|firefox|edg\//', $user_agent)) {
        return array('class' => 'browser', 'family' => 'browser');
    }
    return array('class' => 'unknown', 'family' => $user_agent === '' ? 'missing' : 'other');
}

function kingy_ali_feed_metrics_client_hash($window_id, $system = false) {
    if ($system) {
        return hash_hmac('sha256', 'system-health-check', wp_salt('auth') . '|' . $window_id);
    }
    $ip = function_exists('kingy_ali_request_remote_addr') ? kingy_ali_request_remote_addr() : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) && is_scalar($_SERVER['HTTP_USER_AGENT'])
        ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT'])
        : '';
    return hash_hmac('sha256', $ip . '|' . $user_agent, wp_salt('auth') . '|kingy-feed-window|' . $window_id);
}

function kingy_ali_feed_metrics_source_host($value) {
    if (!is_scalar($value)) {
        return '';
    }
    $host = strtolower(trim((string) $value));
    $host = preg_replace('/^www\./', '', $host);
    if ($host === '' || $host === 'direct' || $host === 'unknown') {
        return $host === '' ? 'direct' : $host;
    }
    if (strlen($host) > 191 || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host)) {
        return 'invalid';
    }
    return $host;
}

function kingy_ali_feed_metrics_record($metric_type, $route_kind, $route_slug = '', $source_host = '', $system = false) {
    global $wpdb;
    if (!kingy_ali_feed_metrics_window_active()) {
        return false;
    }
    kingy_ali_feed_metrics_create_table();
    $allowed_metrics = array('feed_request', 'widget_view', 'widget_click', 'stale_incident', 'stale_recovered');
    $allowed_routes = array('launches', 'stack_changes', 'pricing_access', 'founder', 'category', 'audience', 'widget');
    $metric_type = sanitize_key((string) $metric_type);
    $route_kind = sanitize_key((string) $route_kind);
    if (!in_array($metric_type, $allowed_metrics, true) || !in_array($route_kind, $allowed_routes, true)) {
        return false;
    }
    $route_slug = sanitize_title((string) $route_slug);
    if (strlen($route_slug) > 100) {
        $route_slug = substr($route_slug, 0, 100);
    }
    $source_host = kingy_ali_feed_metrics_source_host($source_host);
    $window = kingy_ali_feed_metrics_window();
    $classification = $system
        ? array('class' => 'system', 'family' => 'health_check')
        : kingy_ali_feed_metrics_client_classification();
    $client_hash = kingy_ali_feed_metrics_client_hash($window['id'], $system);
    $source_hash = hash_hmac('sha256', $source_host, wp_salt('auth') . '|kingy-feed-source|' . $window['id']);
    $now = current_time('mysql', true);
    $day_index = max(0, min(62, (int) floor((time() - $window['start_ts']) / DAY_IN_SECONDS)));
    $active_day = 1 << $day_index;
    $table = kingy_ali_feed_metrics_table_name();

    $sql = "INSERT INTO {$table}
        (window_id, metric_type, route_kind, route_slug, client_hash, client_class, client_family, source_hash, source_host, requests, active_days, first_seen, last_seen)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, 1, %d, %s, %s)
        ON DUPLICATE KEY UPDATE
            requests = requests + 1,
            active_days = active_days | VALUES(active_days),
            last_seen = VALUES(last_seen),
            client_class = VALUES(client_class),
            client_family = VALUES(client_family),
            source_host = VALUES(source_host)";
    return false !== $wpdb->query(
        $wpdb->prepare(
            $sql,
            $window['id'],
            $metric_type,
            $route_kind,
            $route_slug,
            $client_hash,
            $classification['class'],
            $classification['family'],
            $source_hash,
            $source_host,
            $active_day,
            $now,
            $now
        )
    );
}

function kingy_ali_feed_metrics_route_from_request() {
    $feed = sanitize_key((string) get_query_var('feed'));
    if ($feed === 'kingy-ai-launches') {
        return array('kind' => 'launches', 'slug' => '');
    }
    if ($feed === 'kingy-ai-stack-changes') {
        return array('kind' => 'stack_changes', 'slug' => '');
    }
    $category = sanitize_title((string) get_query_var('kingy_launch_category'));
    if ($category !== '') {
        return array('kind' => 'category', 'slug' => $category);
    }
    $audience = sanitize_title((string) get_query_var('kingy_audience'));
    if ($audience !== '') {
        return array('kind' => 'audience', 'slug' => $audience);
    }
    $attribute = sanitize_title((string) get_query_var('kingy_tool_attribute'));
    if ($attribute === 'founder-submitted') {
        return array('kind' => 'founder', 'slug' => 'founder-submitted');
    }
    $post_type = get_query_var('post_type');
    if ($post_type === 'kingy_ai_launch'
        || (is_array($post_type) && in_array('kingy_ai_launch', $post_type, true))) {
        return array('kind' => 'launches', 'slug' => '');
    }
    return array('kind' => '', 'slug' => '');
}

function kingy_ali_feed_metrics_no_store_headers() {
    if (!kingy_ali_feed_metrics_window_active() || headers_sent()) {
        return;
    }
    header('CDN-Cache-Control: no-store', true);
    header('Cloudflare-CDN-Cache-Control: no-store', true);
    header('Surrogate-Control: no-store', true);
    header('Cache-Control: private, no-store, max-age=0', true);
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);
    header('X-Kingy-Feeds-Measurement: active-no-store', true);
}

function kingy_ali_feed_metrics_capture_rss_request() {
    static $captured = false;
    if ($captured || !kingy_ali_feed_metrics_window_active() || !kingy_ali_feed_metrics_request_method_is_readable()) {
        return;
    }
    if (!function_exists('kingy_ali_feeds_hub_is_summary_feed_request') || !kingy_ali_feeds_hub_is_summary_feed_request()) {
        return;
    }
    $route = kingy_ali_feed_metrics_route_from_request();
    if ($route['kind'] === '') {
        return;
    }
    kingy_ali_feed_metrics_no_store_headers();
    $captured = true;
    if (kingy_ali_feed_metrics_request_method_is_get()) {
        kingy_ali_feed_metrics_record('feed_request', $route['kind'], $route['slug']);
    }
}

function kingy_ali_feed_metrics_capture_pricing_request() {
    if (!kingy_ali_feed_metrics_request_method_is_readable()) {
        return;
    }
    kingy_ali_feed_metrics_no_store_headers();
    if (kingy_ali_feed_metrics_request_method_is_get()) {
        kingy_ali_feed_metrics_record('feed_request', 'pricing_access');
    }
}

function kingy_ali_feed_metrics_widget_config() {
    return array(
        'endpoint' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kingy_ali_feed_metric'),
    );
}

function kingy_ali_feed_metrics_ajax() {
    if (!kingy_ali_feed_metrics_window_active()) {
        wp_send_json_success(array('recorded' => false));
    }
    check_ajax_referer('kingy_ali_feed_metric', 'nonce');
    $values = is_array($_POST) ? wp_unslash($_POST) : array();
    $metric = isset($values['metric']) && is_scalar($values['metric']) ? sanitize_key((string) $values['metric']) : '';
    $content = isset($values['content']) && is_scalar($values['content']) ? sanitize_key((string) $values['content']) : '';
    $source = isset($values['source']) && is_scalar($values['source']) ? (string) $values['source'] : '';
    if (!in_array($metric, array('widget_view', 'widget_click'), true)) {
        wp_send_json_error(array('message' => 'Invalid metric.'), 400);
    }
    if ($metric === 'widget_view') {
        $content = 'embed';
    } elseif (!in_array($content, array('launch', 'credit'), true)) {
        wp_send_json_error(array('message' => 'Invalid content.'), 400);
    }
    $recorded = kingy_ali_feed_metrics_record($metric, 'widget', $content, $source);
    nocache_headers();
    wp_send_json_success(array('recorded' => (bool) $recorded));
}

function kingy_ali_feed_metrics_health_surfaces() {
    return array(
        'launches' => home_url('/feed/kingy-ai-launches/'),
        'stack_changes' => home_url('/feed/kingy-ai-stack-changes/'),
        'pricing_access' => home_url('/feeds/pricing-access/'),
        'founder' => home_url('/feeds/founder-submitted/'),
        'widget' => home_url('/feeds/widget/'),
    );
}

function kingy_ali_feed_metrics_health_check() {
    if (!kingy_ali_feed_metrics_window_active()) {
        wp_clear_scheduled_hook('kingy_ali_feed_metrics_health_check');
        return;
    }
    $previous = get_option('kingy_ali_feed_metrics_health_state', array());
    $previous = is_array($previous) ? $previous : array();
    $next = array();

    foreach (kingy_ali_feed_metrics_health_surfaces() as $surface => $url) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
                'redirection' => 5,
                'headers' => array(
                    'Accept' => $surface === 'widget' ? 'text/html' : 'application/rss+xml, application/xml;q=0.9',
                    'User-Agent' => 'KingyFeedsHealth/1.0',
                ),
            )
        );
        $reason = '';
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            $reason = 'http_error';
        } else {
            $body = (string) wp_remote_retrieve_body($response);
            if ($surface === 'widget') {
                if (strpos($body, 'This Week in AI') === false) {
                    $reason = 'widget_invalid';
                }
            } elseif (strpos($body, '<rss') === false || strpos($body, '<channel>') === false || strpos($body, '<content:encoded') !== false) {
                $reason = 'rss_invalid';
            }
        }
        if ($surface === 'launches' && $reason === '' && function_exists('kingy_ali_launch_freshness_snapshot')) {
            $snapshot = kingy_ali_launch_freshness_snapshot(true);
            if (!function_exists('kingy_ali_launch_data_generation_is_valid') || !kingy_ali_launch_data_generation_is_valid(kingy_ali_launch_data_generation())) {
                $reason = 'generation_invalid';
            } elseif (isset($snapshot['coverage_lag_days']) && $snapshot['coverage_lag_days'] !== null && (int) $snapshot['coverage_lag_days'] > 2) {
                $reason = 'coverage_lag_exceeded';
            }
        }

        $old_reason = isset($previous[$surface]['reason']) ? sanitize_key((string) $previous[$surface]['reason']) : '';
        if ($reason !== '' && $old_reason === '') {
            kingy_ali_feed_metrics_record('stale_incident', $surface, $reason, '', true);
        } elseif ($reason === '' && $old_reason !== '') {
            kingy_ali_feed_metrics_record('stale_recovered', $surface, $old_reason, '', true);
        } elseif ($reason !== '' && $old_reason !== '' && $reason !== $old_reason) {
            kingy_ali_feed_metrics_record('stale_recovered', $surface, $old_reason, '', true);
            kingy_ali_feed_metrics_record('stale_incident', $surface, $reason, '', true);
        }
        $next[$surface] = array(
            'status' => $reason === '' ? 'healthy' : 'stale',
            'reason' => $reason,
            'checked_at_gmt' => current_time('mysql', true),
        );
    }
    update_option('kingy_ali_feed_metrics_health_state', $next, false);
}

function kingy_ali_feed_metrics_cleanup() {
    global $wpdb;
    kingy_ali_feed_metrics_create_table();
    $cutoff = gmdate('Y-m-d H:i:s', time() - (60 * DAY_IN_SECONDS));
    return $wpdb->query(
        $wpdb->prepare(
            'DELETE FROM ' . kingy_ali_feed_metrics_table_name() . ' WHERE last_seen < %s',
            $cutoff
        )
    );
}

function kingy_ali_feed_metrics_report() {
    global $wpdb;
    kingy_ali_feed_metrics_create_table();
    $window = kingy_ali_feed_metrics_window();
    $table = kingy_ali_feed_metrics_table_name();
    $external_classes = "'feed_reader','library','unknown','browser'";
    $measurement_incidents = get_option('kingy_ali_feed_metrics_integrity_incidents', array());
    $measurement_incidents = is_array($measurement_incidents) ? array_values($measurement_incidents) : array();
    $report = array(
        'window' => $window,
        'generated_at_gmt' => gmdate('c'),
        'origin_feed_counts_are' => $measurement_incidents
            ? 'complete_except_declared_measurement_incidents'
            : 'complete_during_active_no_store_window',
        'measurement_incidents' => $measurement_incidents,
        'request_totals' => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT metric_type, route_kind, route_slug, client_class, client_family, SUM(requests) AS requests, COUNT(DISTINCT client_hash) AS client_proxies, BIT_COUNT(BIT_OR(active_days)) AS active_days FROM {$table} WHERE window_id = %s GROUP BY metric_type, route_kind, route_slug, client_class, client_family ORDER BY metric_type, requests DESC",
                $window['id']
            ),
            ARRAY_A
        ),
        'filter_use' => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT route_kind, route_slug, SUM(requests) AS requests, COUNT(DISTINCT client_hash) AS client_proxies, BIT_COUNT(BIT_OR(active_days)) AS active_days FROM {$table} WHERE window_id = %s AND metric_type = 'feed_request' AND route_kind IN ('category','audience','pricing_access','founder') AND client_class IN ({$external_classes}) GROUP BY route_kind, route_slug ORDER BY requests DESC",
                $window['id']
            ),
            ARRAY_A
        ),
        'qualified_feed_consumers' => (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM (SELECT client_hash FROM {$table} WHERE window_id = %s AND metric_type = 'feed_request' AND client_class IN ({$external_classes}) GROUP BY client_hash HAVING BIT_COUNT(BIT_OR(active_days)) >= 3 AND DATEDIFF(MAX(last_seen), MIN(first_seen)) >= 7) qualified",
                $window['id']
            )
        ),
        'widget_domains' => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT source_host, SUM(requests) AS views, COUNT(DISTINCT client_hash) AS client_proxies, BIT_COUNT(BIT_OR(active_days)) AS active_days FROM {$table} WHERE window_id = %s AND metric_type = 'widget_view' AND source_host NOT IN ('', 'direct', 'unknown', 'invalid', 'kingy.ai') AND client_class IN ({$external_classes}) GROUP BY source_host ORDER BY views DESC",
                $window['id']
            ),
            ARRAY_A
        ),
        'widget_referrals' => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT route_slug AS content, source_host, SUM(requests) AS clicks, COUNT(DISTINCT client_hash) AS client_proxies, BIT_COUNT(BIT_OR(active_days)) AS active_days FROM {$table} WHERE window_id = %s AND metric_type = 'widget_click' AND client_class IN ({$external_classes}) GROUP BY route_slug, source_host ORDER BY clicks DESC",
                $window['id']
            ),
            ARRAY_A
        ),
        'stale_transitions' => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT metric_type, route_kind, route_slug AS reason, SUM(requests) AS transitions, MIN(first_seen) AS first_seen, MAX(last_seen) AS last_seen FROM {$table} WHERE window_id = %s AND metric_type IN ('stale_incident','stale_recovered') GROUP BY metric_type, route_kind, route_slug ORDER BY first_seen ASC",
                $window['id']
            ),
            ARRAY_A
        ),
        'current_health' => get_option('kingy_ali_feed_metrics_health_state', array()),
    );

    $traffic_table = $wpdb->prefix . 'kingy_traffic_pulse_buckets';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $traffic_table));
    $report['feeds_page_human_views'] = $table_exists === $traffic_table
        ? (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(views), 0) FROM {$traffic_table} WHERE path = '/feeds/' AND is_bot = 0 AND bucket_start >= %s AND bucket_start < %s",
                $window['start'],
                $window['end']
            )
        )
        : null;
    return $report;
}
