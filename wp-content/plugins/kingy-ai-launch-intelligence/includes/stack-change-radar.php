<?php
/**
 * Kingy AI Stack Change Radar beta.
 *
 * A deliberately small extension of the existing Launch Intelligence record
 * system. Events remain kingy_ai_launch posts and are identified by namespaced
 * post meta. Public responses expose only the reviewed field allowlist below.
 */

if (!defined('ABSPATH')) {
    exit;
}

const KINGY_ALI_STACK_EVENT_META = '_kingy_ali_stack_change_event';
const KINGY_ALI_STACK_REVIEWED_META = '_kingy_ali_stack_review_status';
const KINGY_ALI_STACK_DEDUPE_META = '_kingy_ali_stack_dedupe_key';

function kingy_ali_stack_field_definitions() {
    return array(
        'vendor' => array('label' => 'Vendor', 'type' => 'text'),
        'change_type' => array('label' => 'Change type', 'type' => 'select', 'options' => array(
            'release', 'retirement', 'price_change', 'limit_change', 'breaking_api_change',
            'feature_rollout', 'access_change', 'regional_change', 'migration_deadline',
        )),
        'severity' => array('label' => 'Severity', 'type' => 'select', 'options' => array('critical', 'high', 'medium', 'low')),
        'affected_components' => array('label' => 'Affected components', 'type' => 'text'),
        'affected_audience' => array('label' => 'Who is affected?', 'type' => 'textarea'),
        'required_action' => array('label' => 'What action is required?', 'type' => 'textarea'),
        'effective_date' => array('label' => 'Effective date', 'type' => 'date'),
        'migration_deadline' => array('label' => 'Migration deadline', 'type' => 'date'),
        'access_regional_impact' => array('label' => 'Access or regional impact', 'type' => 'textarea'),
        'recommended_alternative' => array('label' => 'Recommended alternative', 'type' => 'text'),
        'official_source' => array('label' => 'Official source', 'type' => 'url'),
        'first_detected' => array('label' => 'First detected', 'type' => 'date'),
        'last_verified' => array('label' => 'Last verified', 'type' => 'date'),
        'source_summary' => array('label' => 'What changed?', 'type' => 'textarea'),
    );
}

function kingy_ali_stack_meta_key($field) {
    return '_kingy_ali_stack_' . sanitize_key($field);
}

add_action('init', 'kingy_ali_stack_register', 12);
function kingy_ali_stack_register() {
    foreach (kingy_ali_stack_field_definitions() as $field => $definition) {
        register_post_meta('kingy_ai_launch', kingy_ali_stack_meta_key($field), array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => $definition['type'] === 'url' ? 'esc_url_raw' : 'sanitize_textarea_field',
            'auth_callback' => static function () {
                return current_user_can('edit_posts');
            },
        ));
    }
    register_post_meta('kingy_ai_launch', KINGY_ALI_STACK_EVENT_META, array(
        'type' => 'boolean', 'single' => true, 'show_in_rest' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'auth_callback' => static function () { return current_user_can('edit_posts'); },
    ));
    register_post_meta('kingy_ai_launch', KINGY_ALI_STACK_REVIEWED_META, array(
        'type' => 'string', 'single' => true, 'show_in_rest' => false,
        'sanitize_callback' => 'sanitize_key',
        'auth_callback' => static function () { return current_user_can('publish_posts'); },
    ));
    register_post_meta('kingy_ai_launch', KINGY_ALI_STACK_DEDUPE_META, array(
        'type' => 'string', 'single' => true, 'show_in_rest' => false,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => static function () { return current_user_can('edit_posts'); },
    ));

    add_rewrite_rule('^ai-stack-change-radar/calendar\.ics$', 'index.php?kingy_stack_radar_ics=1', 'top');
    add_rewrite_rule('^ai-stack-change-radar/?$', 'index.php?kingy_stack_radar=1', 'top');
    add_feed('kingy-ai-stack-changes', 'kingy_ali_stack_render_rss');
}

add_filter('query_vars', 'kingy_ali_stack_query_vars');
function kingy_ali_stack_query_vars($vars) {
    $vars[] = 'kingy_stack_radar';
    $vars[] = 'kingy_stack_radar_ics';
    return $vars;
}

add_filter('pre_get_document_title', 'kingy_ali_stack_document_title', 999);
add_filter('wpseo_title', 'kingy_ali_stack_document_title', 999);
function kingy_ali_stack_document_title($title = '') {
    return get_query_var('kingy_stack_radar') ? 'AI Stack Change Radar | Kingy AI' : $title;
}

add_filter('wpseo_canonical', 'kingy_ali_stack_canonical', 999);
function kingy_ali_stack_canonical($canonical) {
    return get_query_var('kingy_stack_radar') ? home_url('/ai-stack-change-radar/') : $canonical;
}

add_action('wp_head', 'kingy_ali_stack_fallback_canonical', 1);
add_action('wp_head', 'kingy_ali_stack_output_feed_link', 2);
function kingy_ali_stack_fallback_canonical() {
    if (get_query_var('kingy_stack_radar') && !defined('WPSEO_VERSION')) {
        echo '<link rel="canonical" href="' . esc_url(home_url('/ai-stack-change-radar/')) . '">' . "\n";
    }
}

function kingy_ali_stack_output_feed_link() {
    if (!get_query_var('kingy_stack_radar')) {
        return;
    }

    echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__('Kingy AI Stack Change Radar feed', 'kingy-ai-launch-intelligence') . '" href="' . esc_url(home_url('/feed/kingy-ai-stack-changes/')) . '">' . "\n";
}

function kingy_ali_stack_format_date($date) {
    $date = trim((string) $date);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone());
    return $parsed ? $parsed->format('M j, Y') : $date;
}

function kingy_ali_stack_get_event($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'kingy_ai_launch') {
        return null;
    }
    $event = array(
        'id' => (int) $post_id,
        'title' => get_the_title($post_id),
        'url' => home_url('/ai-stack-change-radar/#event-' . (int) $post_id),
        'published' => get_post_time('c', true, $post_id),
    );
    foreach (kingy_ali_stack_field_definitions() as $field => $definition) {
        $event[$field] = (string) get_post_meta($post_id, kingy_ali_stack_meta_key($field), true);
    }
    return $event;
}

function kingy_ali_stack_is_public_event($post_id) {
    return get_post_status($post_id) === 'publish'
        && get_post_type($post_id) === 'kingy_ai_launch'
        && (bool) get_post_meta($post_id, KINGY_ALI_STACK_EVENT_META, true)
        && get_post_meta($post_id, KINGY_ALI_STACK_REVIEWED_META, true) === 'reviewed'
        && kingy_ali_stack_validate_event($post_id) === array();
}

function kingy_ali_stack_official_host_allowed($url) {
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $allowed = array(
        'openai.com', 'developers.openai.com', 'platform.openai.com',
        'anthropic.com', 'www.anthropic.com', 'docs.anthropic.com', 'platform.claude.com',
        'ai.google.dev', 'cloud.google.com', 'docs.cloud.google.com',
    );
    return in_array($host, $allowed, true);
}

function kingy_ali_stack_validate_event($post_id) {
    if (!(bool) get_post_meta($post_id, KINGY_ALI_STACK_EVENT_META, true)) {
        return array();
    }
    $errors = array();
    $required = array(
        'vendor', 'change_type', 'severity', 'affected_components', 'affected_audience',
        'required_action', 'access_regional_impact', 'official_source', 'first_detected',
        'last_verified', 'source_summary',
    );
    foreach ($required as $field) {
        if (trim((string) get_post_meta($post_id, kingy_ali_stack_meta_key($field), true)) === '') {
            $errors[] = $field . ' is required';
        }
    }
    $source = get_post_meta($post_id, kingy_ali_stack_meta_key('official_source'), true);
    if ($source && (!wp_http_validate_url($source) || !kingy_ali_stack_official_host_allowed($source))) {
        $errors[] = 'official_source must use an allowlisted vendor documentation domain';
    }
    $type = get_post_meta($post_id, kingy_ali_stack_meta_key('change_type'), true);
    if (in_array($type, array('retirement', 'migration_deadline'), true)
        && !get_post_meta($post_id, kingy_ali_stack_meta_key('migration_deadline'), true)) {
        $errors[] = 'migration_deadline is required for dated retirement events';
    }
    if (get_post_meta($post_id, KINGY_ALI_STACK_REVIEWED_META, true) !== 'reviewed') {
        $errors[] = 'editorial review is required';
    }
    return $errors;
}

add_filter('wp_insert_post_data', 'kingy_ali_stack_publication_gate', 50, 4);
function kingy_ali_stack_publication_gate($data, $postarr, $unsanitized_postarr, $update) {
    if (($data['post_type'] ?? '') !== 'kingy_ai_launch' || ($data['post_status'] ?? '') !== 'publish') {
        return $data;
    }
    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
    $is_event = $post_id ? (bool) get_post_meta($post_id, KINGY_ALI_STACK_EVENT_META, true)
        : !empty($postarr['meta_input'][KINGY_ALI_STACK_EVENT_META]);
    if (!$is_event) {
        return $data;
    }
    if (!$post_id || kingy_ali_stack_validate_event($post_id)) {
        $data['post_status'] = 'draft';
        set_transient('kingy_ali_stack_gate_notice_' . get_current_user_id(), 1, 60);
    }
    return $data;
}

add_action('admin_notices', 'kingy_ali_stack_gate_notice');
function kingy_ali_stack_gate_notice() {
    $key = 'kingy_ali_stack_gate_notice_' . get_current_user_id();
    if (get_transient($key)) {
        delete_transient($key);
        echo '<div class="notice notice-error"><p>' .
            esc_html__('Stack Change Radar held this event as a draft because required evidence or editorial review is missing.', 'kingy-ai-launch-intelligence') .
            '</p></div>';
    }
}

add_action('add_meta_boxes', 'kingy_ali_stack_add_meta_box');
function kingy_ali_stack_add_meta_box() {
    add_meta_box('kingy-ali-stack-change', 'Stack Change Radar event', 'kingy_ali_stack_render_meta_box', 'kingy_ai_launch', 'normal', 'high');
}

function kingy_ali_stack_render_meta_box($post) {
    wp_nonce_field('kingy_ali_stack_save', 'kingy_ali_stack_nonce');
    $enabled = (bool) get_post_meta($post->ID, KINGY_ALI_STACK_EVENT_META, true);
    $review = get_post_meta($post->ID, KINGY_ALI_STACK_REVIEWED_META, true) ?: 'needs_review';
    echo '<p><label><input type="checkbox" name="kingy_ali_stack_event" value="1" ' . checked($enabled, true, false) . '> Structured Stack Change event</label></p>';
    echo '<p><label><strong>Editorial status</strong><br><select name="kingy_ali_stack_review_status">';
    foreach (array('needs_review' => 'Needs review', 'reviewed' => 'Reviewed') as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($review, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></label></p><table class="form-table"><tbody>';
    foreach (kingy_ali_stack_field_definitions() as $field => $definition) {
        $value = get_post_meta($post->ID, kingy_ali_stack_meta_key($field), true);
        echo '<tr><th><label for="kingy-stack-' . esc_attr($field) . '">' . esc_html($definition['label']) . '</label></th><td>';
        if ($definition['type'] === 'textarea') {
            echo '<textarea class="large-text" rows="3" id="kingy-stack-' . esc_attr($field) . '" name="kingy_ali_stack[' . esc_attr($field) . ']">' . esc_textarea($value) . '</textarea>';
        } elseif ($definition['type'] === 'select') {
            echo '<select id="kingy-stack-' . esc_attr($field) . '" name="kingy_ali_stack[' . esc_attr($field) . ']">';
            foreach ($definition['options'] as $option) {
                echo '<option value="' . esc_attr($option) . '" ' . selected($value, $option, false) . '>' . esc_html(ucwords(str_replace('_', ' ', $option))) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input class="large-text" type="' . esc_attr($definition['type']) . '" id="kingy-stack-' . esc_attr($field) . '" name="kingy_ali_stack[' . esc_attr($field) . ']" value="' . esc_attr($value) . '">';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table><p><em>Publishing is blocked until the record is reviewed, complete, and linked to an allowlisted official vendor source.</em></p>';
}

add_action('save_post_kingy_ai_launch', 'kingy_ali_stack_save_meta', 20, 2);
function kingy_ali_stack_save_meta($post_id, $post) {
    if (!isset($_POST['kingy_ali_stack_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kingy_ali_stack_nonce'])), 'kingy_ali_stack_save')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    update_post_meta($post_id, KINGY_ALI_STACK_EVENT_META, isset($_POST['kingy_ali_stack_event']) ? '1' : '');
    if (current_user_can('publish_posts')) {
        update_post_meta($post_id, KINGY_ALI_STACK_REVIEWED_META, sanitize_key(wp_unslash($_POST['kingy_ali_stack_review_status'] ?? 'needs_review')));
    }
    $posted = isset($_POST['kingy_ali_stack']) && is_array($_POST['kingy_ali_stack']) ? wp_unslash($_POST['kingy_ali_stack']) : array();
    foreach (kingy_ali_stack_field_definitions() as $field => $definition) {
        $value = $posted[$field] ?? '';
        $value = $definition['type'] === 'url' ? esc_url_raw($value) : sanitize_textarea_field($value);
        update_post_meta($post_id, kingy_ali_stack_meta_key($field), $value);
    }
    kingy_ali_stack_update_dedupe($post_id);
}

function kingy_ali_stack_update_dedupe($post_id) {
    $parts = array('vendor', 'change_type', 'affected_components', 'official_source', 'effective_date', 'migration_deadline');
    $values = array();
    foreach ($parts as $part) {
        $values[] = strtolower(trim((string) get_post_meta($post_id, kingy_ali_stack_meta_key($part), true)));
    }
    update_post_meta($post_id, KINGY_ALI_STACK_DEDUPE_META, hash('sha256', implode('|', $values)));
}

/**
 * Normalize and upsert a source-backed event.
 *
 * The importer fails closed unless a story-relevant featured media ID is
 * supplied. Imported rows are drafts unless explicitly reviewed and requested
 * for publication; the publication gate then performs a second validation.
 *
 * @return int|WP_Error
 */
function kingy_ali_stack_import_event($event, $publish_reviewed = false) {
    if (!is_array($event) || empty($event['title']) || empty($event['featured_media'])) {
        return new WP_Error('kingy_stack_incomplete_import', 'A title and reviewed featured media are required.');
    }
    $field_values = array();
    foreach (kingy_ali_stack_field_definitions() as $field => $definition) {
        $raw = $event[$field] ?? '';
        $field_values[$field] = $definition['type'] === 'url' ? esc_url_raw($raw) : sanitize_textarea_field($raw);
    }
    $parts = array(
        strtolower(trim($field_values['vendor'])),
        strtolower(trim($field_values['change_type'])),
        strtolower(trim($field_values['affected_components'])),
        strtolower(trim($field_values['official_source'])),
        strtolower(trim($field_values['effective_date'])),
        strtolower(trim($field_values['migration_deadline'])),
    );
    $dedupe = hash('sha256', implode('|', $parts));
    $existing = get_posts(array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => array('draft', 'pending', 'publish', 'future', 'private'),
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => KINGY_ALI_STACK_DEDUPE_META,
        'meta_value' => $dedupe,
    ));
    $post_id = $existing ? (int) $existing[0] : 0;
    $post_data = array(
        'ID' => $post_id,
        'post_type' => 'kingy_ai_launch',
        'post_title' => sanitize_text_field($event['title']),
        'post_excerpt' => $field_values['source_summary'],
        'post_content' => $field_values['source_summary'],
        'post_status' => 'draft',
    );
    $post_id = wp_insert_post($post_data, true);
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    set_post_thumbnail($post_id, absint($event['featured_media']));
    update_post_meta($post_id, KINGY_ALI_STACK_EVENT_META, '1');
    update_post_meta($post_id, KINGY_ALI_STACK_REVIEWED_META, !empty($event['reviewed']) ? 'reviewed' : 'needs_review');
    update_post_meta($post_id, KINGY_ALI_STACK_DEDUPE_META, $dedupe);
    foreach ($field_values as $field => $value) {
        update_post_meta($post_id, kingy_ali_stack_meta_key($field), $value);
    }
    $source_checks = array(array(
        'url' => $field_values['official_source'],
        'status' => 'verified',
        'checked_at' => $field_values['last_verified'],
        'note' => 'Official vendor documentation reviewed by Kingy AI.',
    ));
    $launch_meta = array(
        'launch_date' => $field_values['first_detected'],
        'official_url' => $field_values['official_source'],
        'sources' => $field_values['official_source'],
        'last_verified' => $field_values['last_verified'],
        'verification_date' => $field_values['last_verified'],
        'verification_status' => 'verified',
        'source_count' => '1',
        'source_checks' => wp_json_encode($source_checks),
        'canonical_fingerprint' => 'stack-change:' . $dedupe,
        'pricing' => 'not public',
        'scores_suppressed' => '1',
        'kingy_verdict' => $field_values['source_summary'] . ' Kingy’s action: ' . $field_values['required_action'],
        'feature_diff' => $field_values['source_summary'],
        'what_feels_promising' => 'The vendor provides a first-party source and a concrete migration path where one is available.',
        'what_feels_unproven' => 'Vendor schedules and compatibility guidance can change; verify again before a production cutover.',
    );
    foreach ($launch_meta as $key => $value) {
        update_post_meta($post_id, kingy_ali_meta_key($key), $value);
    }
    if (!empty($event['reviewed']) && $publish_reviewed && kingy_ali_stack_validate_event($post_id) === array()) {
        wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
    }
    clean_post_cache($post_id);
    return (int) $post_id;
}

function kingy_ali_stack_public_query($args = array()) {
    $meta_query = array(
        array('key' => KINGY_ALI_STACK_EVENT_META, 'value' => '1'),
        array('key' => KINGY_ALI_STACK_REVIEWED_META, 'value' => 'reviewed'),
    );
    foreach (array('vendor', 'change_type', 'severity') as $field) {
        if (!empty($args[$field])) {
            $meta_query[] = array('key' => kingy_ali_stack_meta_key($field), 'value' => sanitize_text_field($args[$field]));
        }
    }
    if (!empty($args['component'])) {
        $meta_query[] = array('key' => kingy_ali_stack_meta_key('affected_components'), 'value' => sanitize_text_field($args['component']), 'compare' => 'LIKE');
    }
    if (!empty($args['view']) && $args['view'] === 'upcoming') {
        $meta_query[] = array('key' => kingy_ali_stack_meta_key('migration_deadline'), 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'DATE');
    }
    if (!empty($args['view']) && $args['view'] === 'verified') {
        $meta_query[] = array('key' => kingy_ali_stack_meta_key('last_verified'), 'value' => gmdate('Y-m-d', strtotime('-14 days')), 'compare' => '>=', 'type' => 'DATE');
    }
    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => min(100, max(1, (int) ($args['per_page'] ?? 50))),
        'paged' => max(1, (int) ($args['page'] ?? 1)),
        'meta_query' => $meta_query,
        'meta_key' => kingy_ali_stack_meta_key('migration_deadline'),
        'orderby' => array('meta_value' => 'ASC', 'date' => 'DESC'),
        's' => sanitize_text_field($args['search'] ?? ''),
        'no_found_rows' => false,
    );
    return new WP_Query($query_args);
}

add_action('template_redirect', 'kingy_ali_stack_template_redirect', 0);
function kingy_ali_stack_template_redirect() {
    if (get_query_var('kingy_stack_radar_ics')) {
        kingy_ali_stack_render_ics();
        exit;
    }
    if (!get_query_var('kingy_stack_radar')) {
        return;
    }
    status_header(200);
    nocache_headers();
    add_filter('wp_robots', static function ($robots) {
        $robots['index'] = true;
        $robots['follow'] = true;
        return $robots;
    });
    get_header();
    kingy_ali_stack_render_page();
    get_footer();
    exit;
}

add_action('wp_enqueue_scripts', 'kingy_ali_stack_enqueue');
function kingy_ali_stack_enqueue() {
    if (!get_query_var('kingy_stack_radar')) {
        return;
    }
    wp_enqueue_style('kingy-ali-stack-radar', KINGY_ALI_PLUGIN_URL . 'assets/css/stack-change-radar.css', array(), KINGY_ALI_VERSION);
    wp_enqueue_script('kingy-ali-stack-radar', KINGY_ALI_PLUGIN_URL . 'assets/js/stack-change-radar.js', array(), KINGY_ALI_VERSION, true);
    wp_localize_script('kingy-ali-stack-radar', 'KingyStackRadar', array(
        'storageKey' => 'kingyStackChangeRadar.v1',
        'labels' => array('saved' => 'Saved to your stack', 'save' => 'Save to my stack'),
    ));
}

function kingy_ali_stack_request_filters() {
    $filters = array();
    foreach (array('vendor', 'change_type', 'severity', 'component', 'view', 'search') as $key) {
        $filters[$key] = isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
    }
    return $filters;
}

function kingy_ali_stack_render_page() {
    $filters = kingy_ali_stack_request_filters();
    $query = kingy_ali_stack_public_query($filters);
    $events = array_values(array_filter(array_map('kingy_ali_stack_get_event', wp_list_pluck($query->posts, 'ID'))));
    $vendors = array('OpenAI', 'Anthropic', 'Google');
    $types = array('release', 'retirement', 'price_change', 'limit_change', 'breaking_api_change', 'feature_rollout', 'access_change', 'regional_change', 'migration_deadline');
    $severities = array('critical', 'high', 'medium', 'low');
    ?>
    <main class="ksr" id="main-content">
        <section class="ksr-hero">
            <div class="ksr-wrap">
                <p class="ksr-eyebrow">Kingy AI Launch Intelligence · Public beta</p>
                <h1>AI Stack Change Radar</h1>
                <p class="ksr-deck">Official changes to the models, products and APIs your team depends on—normalized into clear deadlines and actions.</p>
                <div class="ksr-hero-actions">
                    <a class="ksr-button" href="#change-events">Browse changes</a>
                    <a class="ksr-text-link" href="#email-alerts">Get email alerts</a>
                </div>
                <p class="ksr-trust"><strong>Source-first:</strong> every public event is editorially reviewed and links to official vendor documentation.</p>
            </div>
        </section>
        <div class="ksr-wrap ksr-layout">
            <aside class="ksr-sidebar" aria-label="Radar controls">
                <section class="ksr-panel">
                    <h2>Your saved stack</h2>
                    <p>Save relevant vendors and components in this browser. No account is required.</p>
                    <div id="ksr-saved-summary" aria-live="polite">Nothing saved yet.</div>
                    <button class="ksr-secondary" id="ksr-show-saved" type="button" aria-pressed="false">Show my stack only</button>
                    <button class="ksr-clear" id="ksr-clear-saved" type="button">Clear saved stack</button>
                </section>
                <section class="ksr-panel" id="email-alerts">
                    <h2>Email alerts</h2>
                    <p>Choose only the cadence you want. MailPoet sends a confirmation before adding you.</p>
                    <?php kingy_ali_stack_render_subscribe_form(); ?>
                </section>
                <nav class="ksr-panel ksr-feeds" aria-label="Radar feeds">
                    <h2>Follow the feed</h2>
                    <a href="<?php echo esc_url(home_url('/feed/kingy-ai-stack-changes/')); ?>">RSS feed</a>
                    <a href="<?php echo esc_url(rest_url('kingy-ai/v1/stack-changes')); ?>">JSON feed</a>
                    <a href="<?php echo esc_url(home_url('/ai-stack-change-radar/calendar.ics')); ?>">Deadline calendar</a>
                </nav>
            </aside>
            <div class="ksr-main">
                <form class="ksr-filters" method="get" action="<?php echo esc_url(home_url('/ai-stack-change-radar/')); ?>" role="search">
                    <label class="ksr-search"><span>Search changes</span><input type="search" name="search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="Model, API, deadline…"></label>
                    <?php
                    kingy_ali_stack_filter_select('vendor', 'Vendor', $vendors, $filters['vendor']);
                    kingy_ali_stack_filter_select('change_type', 'Change type', $types, $filters['change_type']);
                    kingy_ali_stack_filter_select('severity', 'Severity', $severities, $filters['severity']);
                    ?>
                    <button class="ksr-button" type="submit">Apply filters</button>
                    <a class="ksr-reset" href="<?php echo esc_url(home_url('/ai-stack-change-radar/')); ?>">Reset</a>
                </form>
                <div class="ksr-view-tabs" aria-label="Radar views">
                    <a class="<?php echo !$filters['view'] ? 'is-active' : ''; ?>" href="<?php echo esc_url(home_url('/ai-stack-change-radar/')); ?>">All changes</a>
                    <a class="<?php echo $filters['view'] === 'upcoming' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'upcoming', home_url('/ai-stack-change-radar/'))); ?>">Upcoming deadlines</a>
                    <a class="<?php echo $filters['view'] === 'verified' ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('view', 'verified', home_url('/ai-stack-change-radar/'))); ?>">Recently verified</a>
                </div>
                <section id="change-events" aria-labelledby="ksr-results-title">
                    <div class="ksr-results-head">
                        <h2 id="ksr-results-title"><?php echo esc_html($query->found_posts); ?> verified change<?php echo $query->found_posts === 1 ? '' : 's'; ?></h2>
                        <p id="ksr-visible-count" aria-live="polite"></p>
                    </div>
                    <div class="ksr-events">
                        <?php if (!$events) : ?>
                            <div class="ksr-empty"><h3>No changes match these filters.</h3><p>Reset the filters or check the official feeds again soon.</p></div>
                        <?php endif; ?>
                        <?php foreach ($events as $event) { kingy_ali_stack_render_card($event); } ?>
                    </div>
                </section>
                <section class="ksr-method">
                    <h2>How verification works</h2>
                    <p>Kingy monitors official vendor documentation, normalizes each material change, and holds incomplete or uncertain imports for editorial review. “Last verified” is the latest date a Kingy editor checked the source—not a claim that the vendor will never revise it.</p>
                </section>
            </div>
        </div>
    </main>
    <?php
}

function kingy_ali_stack_filter_select($name, $label, $options, $current) {
    echo '<label><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '"><option value="">All</option>';
    foreach ($options as $option) {
        echo '<option value="' . esc_attr($option) . '" ' . selected($current, $option, false) . '>' . esc_html(ucwords(str_replace('_', ' ', $option))) . '</option>';
    }
    echo '</select></label>';
}

function kingy_ali_stack_render_card($event) {
    $deadline = $event['migration_deadline'] ?: '';
    $deadline_text = $deadline ? kingy_ali_stack_format_date($deadline) : 'No migration deadline announced';
    ?>
    <article class="ksr-card" id="event-<?php echo (int) $event['id']; ?>"
        data-vendor="<?php echo esc_attr(strtolower($event['vendor'])); ?>"
        data-components="<?php echo esc_attr(strtolower($event['affected_components'])); ?>">
        <div class="ksr-card-top">
            <div class="ksr-badges">
                <span class="ksr-vendor"><?php echo esc_html($event['vendor']); ?></span>
                <span class="ksr-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $event['change_type']))); ?></span>
                <span class="ksr-severity is-<?php echo esc_attr($event['severity']); ?>"><?php echo esc_html(ucfirst($event['severity'])); ?></span>
            </div>
            <button class="ksr-save" type="button" data-save-vendor="<?php echo esc_attr($event['vendor']); ?>" data-save-components="<?php echo esc_attr($event['affected_components']); ?>" aria-pressed="false">Save to my stack</button>
        </div>
        <h3><?php echo esc_html($event['title']); ?></h3>
        <p class="ksr-summary"><?php echo esc_html($event['source_summary']); ?></p>
        <dl class="ksr-facts">
            <div><dt>Who is affected?</dt><dd><?php echo esc_html($event['affected_audience']); ?></dd></div>
            <div><dt>Action required</dt><dd><?php echo esc_html($event['required_action']); ?></dd></div>
            <div><dt>By what date?</dt><dd><strong><?php echo esc_html($deadline_text); ?></strong></dd></div>
            <div><dt>Affected stack</dt><dd><?php echo esc_html($event['affected_components']); ?></dd></div>
            <div><dt>Access / region</dt><dd><?php echo esc_html($event['access_regional_impact']); ?></dd></div>
            <div><dt>Recommended alternative</dt><dd><?php echo esc_html($event['recommended_alternative'] ?: 'No vendor alternative announced'); ?></dd></div>
        </dl>
        <footer class="ksr-card-footer">
            <a href="<?php echo esc_url($event['official_source']); ?>" target="_blank" rel="noopener noreferrer">Official source <span aria-hidden="true">↗</span></a>
            <span>First detected <?php echo esc_html(kingy_ali_stack_format_date($event['first_detected'])); ?></span>
            <span>Kingy last verified <?php echo esc_html(kingy_ali_stack_format_date($event['last_verified'])); ?></span>
        </footer>
    </article>
    <?php
}

add_action('rest_api_init', 'kingy_ali_stack_register_rest');
function kingy_ali_stack_register_rest() {
    register_rest_route('kingy-ai/v1', '/stack-changes', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'kingy_ali_stack_rest_response',
        'permission_callback' => '__return_true',
        'args' => array(
            'vendor' => array('sanitize_callback' => 'sanitize_text_field'),
            'change_type' => array('sanitize_callback' => 'sanitize_key'),
            'severity' => array('sanitize_callback' => 'sanitize_key'),
            'component' => array('sanitize_callback' => 'sanitize_text_field'),
            'view' => array('sanitize_callback' => 'sanitize_key'),
            'search' => array('sanitize_callback' => 'sanitize_text_field'),
            'page' => array('sanitize_callback' => 'absint', 'default' => 1),
            'per_page' => array('sanitize_callback' => 'absint', 'default' => 50),
        ),
    ));
}

function kingy_ali_stack_rest_response(WP_REST_Request $request) {
    $query = kingy_ali_stack_public_query($request->get_params());
    $events = array();
    foreach ($query->posts as $post) {
        if (kingy_ali_stack_is_public_event($post->ID)) {
            $events[] = kingy_ali_stack_get_event($post->ID);
        }
    }
    $response = rest_ensure_response(array(
        'schema_version' => '1.0',
        'generated_at' => current_time('c', true),
        'total' => (int) $query->found_posts,
        'events' => $events,
    ));
    $response->header('X-WP-Total', (int) $query->found_posts);
    $response->header('X-WP-TotalPages', (int) $query->max_num_pages);
    $response->header('Cache-Control', 'public, max-age=300');
    return $response;
}

function kingy_ali_stack_render_rss() {
    $query = kingy_ali_stack_public_query(array('per_page' => 50));
    $posts = array_values(
        array_filter(
            (array) $query->posts,
            static function ($post) {
                return is_object($post) && kingy_ali_stack_is_public_event($post->ID);
            }
        )
    );
    usort(
        $posts,
        static function ($left, $right) {
            $left_time = (int) get_post_time('U', true, $left);
            $right_time = (int) get_post_time('U', true, $right);
            if ($left_time === $right_time) {
                return (int) $right->ID <=> (int) $left->ID;
            }
            return $right_time <=> $left_time;
        }
    );
    $last_build = 0;
    foreach ($posts as $post) {
        $last_build = max($last_build, (int) get_post_modified_time('U', true, $post));
    }
    header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0" encoding="' . esc_attr(get_option('blog_charset')) . '"?>';
    ?>
    <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>
        <title><?php echo esc_html(get_bloginfo('name') . ' · AI Stack Change Radar'); ?></title>
        <link><?php echo esc_url(home_url('/ai-stack-change-radar/')); ?></link>
        <atom:link href="<?php echo esc_url(home_url('/feed/kingy-ai-stack-changes/')); ?>" rel="self" type="application/rss+xml" />
        <description>Verified model, product and API changes across AI vendors.</description>
        <?php if ($last_build) : ?><lastBuildDate><?php echo esc_html(gmdate(DATE_RSS, $last_build)); ?></lastBuildDate><?php endif; ?>
        <?php foreach ($posts as $post) :
            $event = kingy_ali_stack_get_event($post->ID); ?>
            <item>
                <title><?php echo esc_html($event['title']); ?></title>
                <link><?php echo esc_url($event['url']); ?></link>
                <guid isPermaLink="false">kingy-stack-change-<?php echo (int) $event['id']; ?></guid>
                <pubDate><?php echo esc_html(get_post_time(DATE_RSS, true, $post)); ?></pubDate>
                <description><![CDATA[<?php echo wp_kses_post($event['source_summary'] . ' Action: ' . $event['required_action']); ?>]]></description>
            </item>
        <?php endforeach; ?>
    </channel></rss>
    <?php
}

function kingy_ali_stack_ics_escape($value) {
    return str_replace(array('\\', ';', ',', "\r\n", "\n", "\r"), array('\\\\', '\;', '\,', '\n', '\n', '\n'), $value);
}

function kingy_ali_stack_render_ics() {
    $query = kingy_ali_stack_public_query(array('view' => 'upcoming', 'per_page' => 100));
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="kingy-ai-stack-change-deadlines.ics"');
    header('Cache-Control: public, max-age=300');
    $lines = array('BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Kingy AI//Stack Change Radar//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH');
    foreach ($query->posts as $post) {
        if (!kingy_ali_stack_is_public_event($post->ID)) { continue; }
        $event = kingy_ali_stack_get_event($post->ID);
        if (!$event['migration_deadline']) { continue; }
        $date = gmdate('Ymd', strtotime($event['migration_deadline']));
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:kingy-stack-change-' . $event['id'] . '@kingy.ai';
        $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTART;VALUE=DATE:' . $date;
        $lines[] = 'DTEND;VALUE=DATE:' . gmdate('Ymd', strtotime($event['migration_deadline'] . ' +1 day'));
        $lines[] = 'SUMMARY:' . kingy_ali_stack_ics_escape($event['vendor'] . ': ' . $event['title']);
        $lines[] = 'DESCRIPTION:' . kingy_ali_stack_ics_escape($event['required_action'] . ' Official source: ' . $event['official_source']);
        $lines[] = 'URL:' . kingy_ali_stack_ics_escape($event['official_source']);
        $lines[] = 'END:VEVENT';
    }
    $lines[] = 'END:VCALENDAR';
    echo implode("\r\n", $lines) . "\r\n";
}

function kingy_ali_stack_mailpoet_lists() {
    return array(
        'urgent' => 'Stack Change Radar · Urgent',
        'daily' => 'Stack Change Radar · Daily digest',
        'weekly' => 'Stack Change Radar · Weekly digest',
    );
}

function kingy_ali_stack_render_subscribe_form() {
    $state = isset($_GET['stack_alert']) ? sanitize_key(wp_unslash($_GET['stack_alert'])) : '';
    if ($state === 'check_email') {
        echo '<p class="ksr-form-status is-success" role="status">Check your inbox to confirm your alert preference.</p>';
    } elseif ($state === 'error') {
        echo '<p class="ksr-form-status is-error" role="alert">We could not save that preference. Please try again later.</p>';
    }
    ?>
    <form class="ksr-alert-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="kingy_stack_change_subscribe">
        <?php wp_nonce_field('kingy_stack_change_subscribe', 'kingy_stack_nonce'); ?>
        <label><span>Email</span><input type="email" name="email" required autocomplete="email"></label>
        <label><span>Alert cadence</span><select name="frequency" required>
            <option value="urgent">Urgent changes only</option>
            <option value="daily">Daily digest</option>
            <option value="weekly">Weekly digest</option>
        </select></label>
        <label class="ksr-honeypot" aria-hidden="true">Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
        <button class="ksr-button" type="submit">Set alert preference</button>
        <small>Confirmation required. Unsubscribe at any time.</small>
    </form>
    <?php
}

add_action('admin_post_nopriv_kingy_stack_change_subscribe', 'kingy_ali_stack_handle_subscribe');
add_action('admin_post_kingy_stack_change_subscribe', 'kingy_ali_stack_handle_subscribe');
function kingy_ali_stack_handle_subscribe() {
    $redirect = home_url('/ai-stack-change-radar/');
    if (!isset($_POST['kingy_stack_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kingy_stack_nonce'])), 'kingy_stack_change_subscribe')
        || !empty($_POST['company'])) {
        wp_safe_redirect(add_query_arg('stack_alert', 'error', $redirect) . '#email-alerts');
        exit;
    }
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $frequency = sanitize_key(wp_unslash($_POST['frequency'] ?? ''));
    $lists = kingy_ali_stack_mailpoet_lists();
    if (!is_email($email) || !isset($lists[$frequency]) || !class_exists('\MailPoet\API\API')) {
        wp_safe_redirect(add_query_arg('stack_alert', 'error', $redirect) . '#email-alerts');
        exit;
    }
    try {
        $api = \MailPoet\API\API::MP('v1');
        $mailpoet_lists = $api->getLists();
        $list_id = 0;
        foreach ($mailpoet_lists as $list) {
            if (($list['name'] ?? '') === $lists[$frequency]) {
                $list_id = (int) $list['id'];
                break;
            }
        }
        if (!$list_id) {
            throw new RuntimeException('Radar list is not configured.');
        }
        try {
            $api->addSubscriber(array('email' => $email), array($list_id), array(
                'send_confirmation_email' => true,
                'schedule_welcome_email' => false,
            ));
        } catch (Throwable $existing_exception) {
            $subscriber = $api->getSubscriber($email);
            if (empty($subscriber['id'])) {
                throw $existing_exception;
            }
            $api->subscribeToList((int) $subscriber['id'], $list_id, array(
                'send_confirmation_email' => true,
                'schedule_welcome_email' => false,
            ));
        }
        wp_safe_redirect(add_query_arg('stack_alert', 'check_email', $redirect) . '#email-alerts');
    } catch (Throwable $exception) {
        error_log('Kingy Stack Change Radar MailPoet capture failed: ' . $exception->getMessage());
        wp_safe_redirect(add_query_arg('stack_alert', 'error', $redirect) . '#email-alerts');
    }
    exit;
}

function kingy_ali_stack_digest_items($frequency = 'daily') {
    $days = $frequency === 'weekly' ? 7 : 1;
    $query = kingy_ali_stack_public_query(array('per_page' => 50));
    $cutoff = strtotime('-' . $days . ' days');
    $items = array();
    foreach ($query->posts as $post) {
        $event = kingy_ali_stack_get_event($post->ID);
        if ($event && strtotime($event['last_verified']) >= $cutoff) {
            $items[] = $event;
        }
    }
    return $items;
}

add_filter('the_content', 'kingy_ali_stack_connect_records', 999);
function kingy_ali_stack_connect_records($content) {
    if (!is_singular('kingy_ai_model') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    return $content . '<aside class="kingy-stack-radar-connection"><strong>' . esc_html__('Track model deadlines and API changes', 'kingy-ai-launch-intelligence') . '</strong><p><a href="' .
        esc_url(home_url('/ai-stack-change-radar/')) . '">Open the Kingy AI Stack Change Radar →</a></p></aside>';
}
