<?php

if (!defined('ABSPATH')) {
    exit;
}

final class KOML_Frontend {
    public static function boot() {
        add_filter('template_include', array(__CLASS__, 'template_include'), 999);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'), 40);
        add_filter('body_class', array(__CLASS__, 'body_classes'));
        add_filter('document_title_parts', array(__CLASS__, 'title_parts'));
        add_shortcode('kingy_model_fit', array(__CLASS__, 'model_fit_shortcode'));
    }

    public static function template_include($template) {
        if (is_singular('kingy_ai_model') && self::scope_status(get_queried_object_id()) === 'curated') {
            return KOML_DIR . 'templates/single-model-ledger.php';
        }

        if (is_post_type_archive('kingy_ai_model')) {
            return KOML_DIR . 'templates/archive-model-ledger.php';
        }

        if (self::is_open_weight_feed() && self::has_feed_events() && self::feed_has_featured_image()) {
            return KOML_DIR . 'templates/open-weight-feed.php';
        }

        if (is_page('model-fit') && has_post_thumbnail(get_queried_object_id())) {
            return KOML_DIR . 'templates/model-fit.php';
        }

        return $template;
    }

    public static function enqueue_assets() {
        if (!self::is_surface()) {
            return;
        }

        wp_enqueue_style(
            'kingy-open-model-ledger',
            KOML_URL . 'assets/ledger.css',
            array(),
            KOML_VERSION
        );

        if (is_page('model-fit')) {
            wp_enqueue_script(
                'kingy-open-model-fit',
                KOML_URL . 'assets/model-fit.js',
                array(),
                KOML_VERSION,
                true
            );
        }
    }

    public static function body_classes($classes) {
        if (self::is_surface()) {
            $classes[] = 'koml-surface';
        }
        if (is_post_type_archive('kingy_ai_model')) {
            $classes[] = 'koml-directory';
        }
        if (is_singular('kingy_ai_model')) {
            $classes[] = 'koml-record';
        }
        return array_unique($classes);
    }

    public static function title_parts($parts) {
        if (is_post_type_archive('kingy_ai_model')) {
            $parts['title'] = __('Open Model Ledger', 'kingy-open-model-ledger');
        } elseif (self::is_open_weight_feed() && self::has_feed_events() && self::feed_has_featured_image()) {
            $parts['title'] = __('Open-Weight Model Release & Change Feed', 'kingy-open-model-ledger');
        } elseif (is_page('model-fit') && has_post_thumbnail(get_queried_object_id())) {
            $parts['title'] = __('Model Fit Calculator', 'kingy-open-model-ledger');
        }
        return $parts;
    }

    public static function is_surface() {
        return (is_singular('kingy_ai_model') && self::scope_status(get_queried_object_id()) === 'curated')
            || is_post_type_archive('kingy_ai_model')
            || self::is_open_weight_feed()
            || is_page('model-fit');
    }

    public static function is_open_weight_feed() {
        $path = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = (string) wp_parse_url($path, PHP_URL_PATH);
        return untrailingslashit($path) === '/ai-launches/open-weight-models';
    }

    public static function has_feed_events() {
        static $has_events = null;
        if ($has_events === null) {
            $has_events = !empty(self::model_events(1));
        }
        return $has_events;
    }

    public static function feed_has_featured_image() {
        $page = get_page_by_path('ai-launches/open-weight-models', OBJECT, 'page');
        if (!$page) {
            $page = get_page_by_path('open-weight-models', OBJECT, 'page');
        }
        return $page && has_post_thumbnail((int) $page->ID);
    }

    /**
     * Whether the public calculator is ready to receive directory traffic.
     *
     * Publication and a featured image are both required. Pixel-level image
     * review remains an editorial sign-off performed before publication.
     */
    public static function model_fit_is_ready() {
        $page = get_page_by_path('model-fit', OBJECT, 'page');
        return $page
            && 'publish' === get_post_status((int) $page->ID)
            && has_post_thumbnail((int) $page->ID);
    }

    public static function meta($post_id, $key, $default = '') {
        $value = get_post_meta($post_id, '_koml_' . $key, true);
        return $value === '' ? $default : $value;
    }

    public static function legacy($post_id, $key, $default = '') {
        $value = get_post_meta($post_id, '_kingy_ali_' . $key, true);
        return $value === '' ? $default : $value;
    }

    public static function first_value($post_id, $ledger_key, $legacy_key = '', $default = '') {
        $value = self::meta($post_id, $ledger_key, '');
        if ($value !== '') {
            return $value;
        }
        if ($legacy_key !== '') {
            return self::legacy($post_id, $legacy_key, $default);
        }
        return $default;
    }

    public static function openness($post_id) {
        $rights = self::meta($post_id, 'rights_profile', '');
        $access = self::meta($post_id, 'weight_access', '');
        $osaid = self::meta($post_id, 'osaid_outcome', '');

        if ($osaid === 'meets') {
            return array(
                'key' => 'open_source',
                'label' => __('Open Source AI — Kingy assessment against OSAID 1.0', 'kingy-open-model-ledger'),
            );
        }
        if ($access === 'public' || $access === 'click_through' || $access === 'gated_auto' || $access === 'gated_manual') {
            if ($rights === 'permissive') {
                return array('key' => 'open_weight', 'label' => __('Open weights — permissive terms', 'kingy-open-model-ledger'));
            }
            if (in_array($rights, array('restricted', 'noncommercial', 'proprietary'), true)) {
                return array('key' => 'restricted', 'label' => __('Open weights — commercial/use restricted', 'kingy-open-model-ledger'));
            }
            return array('key' => 'custom', 'label' => __('Open weights — custom terms', 'kingy-open-model-ledger'));
        }
        if ($access === 'source_only' || $access === 'partial') {
            return array('key' => 'source_available', 'label' => __('Source available — weights unavailable or partial', 'kingy-open-model-ledger'));
        }
        if (self::legacy($post_id, 'open_weight', '') === 'yes') {
            return array('key' => 'review', 'label' => __('Open-weight claim — ledger review pending', 'kingy-open-model-ledger'));
        }
        return array('key' => 'review', 'label' => __('Insufficient evidence', 'kingy-open-model-ledger'));
    }

    public static function scope_status($post_id) {
        $scope = self::meta($post_id, 'scope_status', '');
        if ($scope !== '') {
            return $scope;
        }
        return self::legacy($post_id, 'open_weight', '') === 'yes' ? 'legacy_review' : 'out_of_scope';
    }

    public static function evidence($post_id, $field_key) {
        $rows = self::meta($post_id, 'evidence', array());
        if (!is_array($rows)) {
            return array();
        }
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['field']) && (string) $row['field'] === (string) $field_key) {
                return $row;
            }
        }
        return array();
    }

    public static function source_note($post_id, $field_key) {
        $evidence = self::evidence($post_id, $field_key);
        if (!$evidence) {
            return '<span class="koml-source koml-source--missing">' . esc_html__('Field verification pending', 'kingy-open-model-ledger') . '</span>';
        }

        $confidence = isset($evidence['confidence']) ? sanitize_key($evidence['confidence']) : 'unverified';
        $verified = isset($evidence['verified_on']) ? (string) $evidence['verified_on'] : '';
        $label = trim(ucfirst(str_replace('_', ' ', $confidence)) . ($verified ? ' · ' . $verified : ''));
        $url = isset($evidence['source_url']) ? esc_url($evidence['source_url']) : '';
        $locator = isset($evidence['locator']) ? trim((string) $evidence['locator']) : '';
        if ($locator !== '') {
            $label .= ' · ' . $locator;
        }

        if ($url) {
            return '<a class="koml-source koml-source--' . esc_attr($confidence) . '" href="' . $url . '" rel="noopener noreferrer" target="_blank">' . esc_html($label) . '</a>';
        }
        return '<span class="koml-source koml-source--' . esc_attr($confidence) . '">' . esc_html($label) . '</span>';
    }

    public static function fact($post_id, $label, $value, $field_key, $class = '') {
        $value = self::display_value($value);
        echo '<div class="koml-fact ' . esc_attr($class) . '">';
        echo '<dt>' . esc_html($label) . '</dt>';
        echo '<dd><strong>' . esc_html($value) . '</strong>' . wp_kses_post(self::source_note($post_id, $field_key)) . '</dd>';
        echo '</div>';
    }

    public static function display_value($value) {
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('strval', $value)));
        }
        $value = trim((string) $value);
        if ($value === '') {
            return __('Unknown', 'kingy-open-model-ledger');
        }
        return $value;
    }

    public static function format_parameter_count($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return __('Unknown', 'kingy-open-model-ledger');
        }
        if (is_numeric($value)) {
            $number = (float) $value;
            if ($number >= 1000000000) {
                return rtrim(rtrim(number_format($number / 1000000000, 2, '.', ''), '0'), '.') . 'B';
            }
            if ($number >= 1000000) {
                return rtrim(rtrim(number_format($number / 1000000, 2, '.', ''), '0'), '.') . 'M';
            }
        }
        return $value;
    }

    public static function announced_on($post_id) {
        return self::first_value($post_id, 'announced_on', 'release_date', '');
    }

    public static function weight_date($post_id) {
        return self::meta($post_id, 'weights_available_on', '');
    }

    public static function date_lag($post_id) {
        $announced = self::announced_on($post_id);
        $weights = self::weight_date($post_id);
        if (!$announced || !$weights) {
            return '';
        }
        $a = strtotime($announced);
        $w = strtotime($weights);
        if (!$a || !$w) {
            return '';
        }
        $days = (int) floor(($w - $a) / DAY_IN_SECONDS);
        if ($days === 0) {
            return __('Weights available the same day', 'kingy-open-model-ledger');
        }
        if ($days > 0) {
            return sprintf(_n('%d day after announcement', '%d days after announcement', $days, 'kingy-open-model-ledger'), $days);
        }
        return sprintf(_n('%d day before announcement', '%d days before announcement', abs($days), 'kingy-open-model-ledger'), abs($days));
    }

    public static function curated_query_args() {
        $search = isset($_GET['ledger_q']) ? sanitize_text_field(wp_unslash($_GET['ledger_q'])) : '';
        $openness = isset($_GET['ledger_openness']) ? sanitize_key(wp_unslash($_GET['ledger_openness'])) : '';
        $lifecycle = isset($_GET['ledger_lifecycle']) ? sanitize_key(wp_unslash($_GET['ledger_lifecycle'])) : '';
        $format = isset($_GET['ledger_format']) ? sanitize_key(wp_unslash($_GET['ledger_format'])) : '';
        $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

        $scope_query = array(
            'relation' => 'OR',
            array('key' => '_koml_scope_status', 'value' => array('curated', 'under_review'), 'compare' => 'IN'),
            array('key' => '_kingy_ali_open_weight', 'value' => 'yes'),
        );
        $meta_query = array($scope_query);

        if ($lifecycle !== '') {
            $meta_query[] = array('key' => '_koml_lifecycle_status', 'value' => $lifecycle);
        }
        if ($openness === 'osaid') {
            $meta_query[] = array('key' => '_koml_osaid_outcome', 'value' => 'meets');
        } elseif ($openness === 'permissive') {
            $meta_query[] = array('key' => '_koml_rights_profile', 'value' => 'permissive');
        } elseif ($openness === 'restricted') {
            $meta_query[] = array('key' => '_koml_rights_profile', 'value' => 'restricted');
        }
        if ($format !== '') {
            $meta_query[] = array('key' => '_koml_artifacts', 'value' => $format, 'compare' => 'LIKE');
        }

        return array(
            'post_type' => 'kingy_ai_model',
            'post_status' => 'publish',
            'posts_per_page' => 24,
            'paged' => $paged,
            's' => $search,
            'meta_query' => $meta_query,
            'orderby' => array('modified' => 'DESC', 'title' => 'ASC'),
            'no_found_rows' => false,
        );
    }

    public static function format_bytes($bytes) {
        if (!is_numeric($bytes) || (float) $bytes <= 0) {
            return __('Unknown', 'kingy-open-model-ledger');
        }
        return size_format((float) $bytes, 2);
    }

    public static function model_fit_shortcode() {
        if (is_page('model-fit') && !has_post_thumbnail(get_queried_object_id())) {
            if (current_user_can('edit_post', get_queried_object_id())) {
                return '<div class="koml-callout koml-callout--warning"><strong>' . esc_html__('Model Fit is not ready for publication', 'kingy-open-model-ledger') . '</strong><p>' . esc_html__('Select and visually verify a story-specific featured image before enabling the public calculator.', 'kingy-open-model-ledger') . '</p></div>';
            }
            return '';
        }
        ob_start();
        include KOML_DIR . 'templates/partials/model-fit-app.php';
        return (string) ob_get_clean();
    }

    public static function open_weight_posts($limit = 100) {
        return get_posts(array(
            'post_type' => 'kingy_ai_model',
            'post_status' => 'publish',
            'posts_per_page' => (int) $limit,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => '_koml_scope_status', 'value' => array('curated', 'under_review'), 'compare' => 'IN'),
                array('key' => '_kingy_ali_open_weight', 'value' => 'yes'),
            ),
        ));
    }

    public static function model_events($limit = 40) {
        $events = array();
        foreach (self::open_weight_posts(150) as $model) {
            $post_id = (int) $model->ID;
            if (self::scope_status($post_id) !== 'curated') {
                continue;
            }
            $history = self::meta($post_id, 'change_log', array());
            if (is_array($history)) {
                foreach ($history as $event) {
                    if (!is_array($event)) {
                        continue;
                    }
                    $events[] = array(
                        'post_id' => $post_id,
                        'title' => get_the_title($post_id),
                        'url' => get_permalink($post_id),
                        'event_type' => isset($event['event_type']) ? (string) $event['event_type'] : 'record_updated',
                        'effective_on' => isset($event['effective_on']) ? (string) $event['effective_on'] : (isset($event['recorded_at']) ? substr((string) $event['recorded_at'], 0, 10) : ''),
                        'recorded_at' => isset($event['recorded_at']) ? (string) $event['recorded_at'] : '',
                        'summary' => isset($event['summary']) ? (string) $event['summary'] : __('Ledger fields updated.', 'kingy-open-model-ledger'),
                        'source_url' => isset($event['source_url']) ? (string) $event['source_url'] : '',
                    );
                }
            }

            $weights_on = self::weight_date($post_id);
            if ($weights_on) {
                $events[] = array(
                    'post_id' => $post_id,
                    'title' => get_the_title($post_id),
                    'url' => get_permalink($post_id),
                    'event_type' => 'weights_available',
                    'effective_on' => $weights_on,
                    'recorded_at' => self::meta($post_id, 'last_verified', get_post_modified_time('c', true, $post_id)),
                    'summary' => __('Official weights first verified as downloadable.', 'kingy-open-model-ledger'),
                    'source_url' => self::meta($post_id, 'repository_url', self::legacy($post_id, 'weights_url', '')),
                );
            }
        }

        usort($events, function ($left, $right) {
            $left_time = strtotime(isset($left['effective_on']) ? $left['effective_on'] : '') ?: 0;
            $right_time = strtotime(isset($right['effective_on']) ? $right['effective_on'] : '') ?: 0;
            return $right_time <=> $left_time;
        });

        return array_slice($events, 0, (int) $limit);
    }
}
