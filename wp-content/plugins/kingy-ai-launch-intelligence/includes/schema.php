<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'kingy_ali_redirect_quarantined_strategic_routes', 0);
add_action('template_redirect', 'kingy_ali_redirect_legacy_launch_intelligence_urls', 1);
add_action('template_redirect', 'kingy_ali_redirect_news_comments_feed', -40);
add_action('wp_head', 'kingy_ali_output_schema');
add_action('wp_head', 'kingy_ali_output_noindex');
add_action('wp_head', 'kingy_ali_output_filtered_results_robots');
add_action('wp_head', 'kingy_ali_output_launch_meta_description', 1);
add_action('wp_head', 'kingy_ali_output_news_feed_link', 2);
add_action('wp_head', 'kingy_ali_phase1_social_image_meta', 30);
add_action('wp_head', 'kingy_ali_phase3a_output_june8_canonical', 0);
add_action('wp', 'kingy_ali_prepare_home_feed_link');
add_filter('document_title_parts', 'kingy_ali_launch_document_title');
add_filter('wpseo_title', 'kingy_ali_launch_wpseo_title');
add_filter('wpseo_metadesc', 'kingy_ali_launch_wpseo_meta_description');
add_filter('wpseo_canonical', 'kingy_ali_launch_wpseo_canonical');
add_filter('get_canonical_url', 'kingy_ali_launch_wpseo_canonical');
add_filter('wpseo_robots', 'kingy_ali_launch_wpseo_robots');
add_filter('wpseo_next_rel_link', 'kingy_ali_launch_wpseo_rel_link');
add_filter('wpseo_prev_rel_link', 'kingy_ali_launch_wpseo_rel_link');
add_filter('wpseo_opengraph_title', 'kingy_ali_launch_wpseo_title');
add_filter('wpseo_opengraph_desc', 'kingy_ali_launch_wpseo_meta_description');
add_filter('wpseo_opengraph_url', 'kingy_ali_launch_wpseo_canonical');
add_filter('wpseo_twitter_title', 'kingy_ali_launch_wpseo_title');
add_filter('wpseo_twitter_description', 'kingy_ali_launch_wpseo_meta_description');
add_filter('wpseo_opengraph_title', 'kingy_ali_phase1_social_title', 20);
add_filter('wpseo_opengraph_desc', 'kingy_ali_phase1_social_description', 20);
add_filter('wpseo_twitter_title', 'kingy_ali_phase1_social_title', 20);
add_filter('wpseo_twitter_description', 'kingy_ali_phase1_social_description', 20);
add_filter('wpseo_opengraph_image', 'kingy_ali_phase1_social_image', 20);
add_filter('wpseo_twitter_image', 'kingy_ali_phase1_social_image', 20);
add_filter('wpseo_opengraph_image_alt', 'kingy_ali_phase1_social_image_alt', 20);
add_filter('wpseo_twitter_image_alt', 'kingy_ali_phase1_social_image_alt', 20);
add_filter('wpseo_sitemap_exclude_taxonomy', 'kingy_ali_exclude_faceted_taxonomy_from_yoast_sitemap', 10, 2);
add_filter('wpseo_sitemap_entry', 'kingy_ali_filter_launch_intelligence_sitemap_entry', 10, 3);
add_filter('wpseo_sitemap_post_type_archive_link', 'kingy_ali_launch_sitemap_post_type_archive_link', 10, 2);
add_filter('wp_sitemaps_taxonomies', 'kingy_ali_filter_core_sitemap_taxonomies');
add_filter('body_class', 'kingy_ali_launch_body_class');
add_filter('the_content', 'kingy_ali_launch_action_content_title', 5);
add_action('template_redirect', 'kingy_ali_start_homepage_template_cleanup_buffer', 0);
add_action('get_footer', 'kingy_ali_render_phase1_search_404_rescue_panel', 1);
add_action('wp_footer', 'kingy_ali_render_phase1_search_404_rescue_panel', 1);

function kingy_ali_repaired_strategic_routes_enabled() {
    return defined('KINGY_ALI_ENABLE_REPAIRED_STRATEGIC_ROUTES') && KINGY_ALI_ENABLE_REPAIRED_STRATEGIC_ROUTES;
}

function kingy_ali_phase1_public_quality_page_paths() {
    return array(
        'ai-courses',
        'ai-sponsored-video-roi-calculator',
        'clients',
        'contact',
        'sponsor-kingy-ai',
        'subscribe',
    );
}

function kingy_ali_is_phase1_public_quality_surface() {
    if (
        is_admin()
        || wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (function_exists('wp_is_json_request') && wp_is_json_request())
        || is_feed()
        || is_preview()
    ) {
        return false;
    }

    if (is_front_page() && (int) get_queried_object_id() === 914004) {
        return true;
    }

    if (in_array(kingy_ali_current_request_path(), array('news', 'ai-news'), true)) {
        return true;
    }

    if (
        kingy_ali_current_launch_collection_meta()
        || kingy_ali_current_launch_action_page_meta()
        || kingy_ali_current_directory_archive_type()
        || kingy_ali_current_related_page_meta()
    ) {
        return true;
    }

    if (
        is_singular('post')
        && function_exists('kingy_ali_radar_post_title_matches')
        && kingy_ali_radar_post_title_matches(get_queried_object_id())
    ) {
        return true;
    }

    if (is_search() || is_404() || is_category(array('ai-launches', 'news'))) {
        return true;
    }

    if (!is_page()) {
        return false;
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';

    return $path !== '' && in_array($path, kingy_ali_phase1_public_quality_page_paths(), true);
}

function kingy_ali_quarantined_strategic_route_targets() {
    return array(
        'ai-launches/today' => home_url('/ai-launches/'),
        'ai-launches/this-week' => home_url('/ai-launches/'),
        'ai-models' => home_url('/ai-tools/'),
        'compare-ai-models' => home_url('/ai-tools/'),
    );
}

function kingy_ali_get_quarantined_strategic_route_target() {
    if (kingy_ali_repaired_strategic_routes_enabled()) {
        return '';
    }

    $targets = kingy_ali_quarantined_strategic_route_targets();
    $path = kingy_ali_current_request_path();
    return isset($targets[$path]) ? $targets[$path] : '';
}

function kingy_ali_redirect_quarantined_strategic_routes() {
    if (
        is_admin()
        || wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (function_exists('wp_is_json_request') && wp_is_json_request())
        || is_feed()
        || is_preview()
    ) {
        return;
    }

    $target = kingy_ali_get_quarantined_strategic_route_target();
    if ($target === '') {
        return;
    }

    // Temporary protection while strategic routes are repaired behind a disabled-by-default test flag.
    wp_safe_redirect($target, 302);
    exit;
}

// Temporary scoped fail-open guard for strategic pages that must never show a public Internal Error.
function kingy_ali_emergency_safe_mode_is_active() {
    if (
        is_admin()
        || wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || is_feed()
        || is_preview()
    ) {
        return false;
    }

    if (kingy_ali_get_quarantined_strategic_route_target() !== '') {
        return true;
    }

    if (kingy_ali_repaired_strategic_routes_enabled()) {
        return false;
    }

    return is_post_type_archive('kingy_ai_model');
}

function kingy_ali_emergency_safe_mode_log($context, $throwable = null) {
    if (!function_exists('error_log')) {
        return;
    }

    $message = is_scalar($context) ? sanitize_key((string) $context) : 'unknown';
    if ($throwable instanceof Throwable) {
        $message .= ': ' . sanitize_text_field($throwable->getMessage());
    }

    error_log('Kingy AI Launch Intelligence emergency safe mode: ' . $message);
}

function kingy_ali_emergency_safe_mode_schema() {
    $path = '';
    if (is_page()) {
        $post_id = get_queried_object_id();
        $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    }

    if ($path === 'ai-launches/today' || $path === 'ai-launches/this-week') {
        $pages = kingy_ali_launch_collection_pages_meta();
        $meta = isset($pages[$path]) ? $pages[$path] : array();
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => isset($meta['title']) ? $meta['title'] : get_the_title(),
            'description' => isset($meta['description']) ? $meta['description'] : '',
            'url' => isset($meta['url']) ? $meta['url'] : get_permalink(),
            'publisher' => kingy_ali_schema_publisher(),
            'mainEntity' => kingy_ali_schema_empty_item_list(),
        );
    }

    if (is_post_type_archive('kingy_ai_model')) {
        $meta = kingy_ali_directory_archive_meta('models');
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => isset($meta['title']) ? $meta['title'] : __('AI Model Intelligence Hub', 'kingy-ai-launch-intelligence'),
            'description' => isset($meta['description']) ? $meta['description'] : '',
            'url' => isset($meta['url']) ? $meta['url'] : home_url('/ai-models/'),
            'publisher' => kingy_ali_schema_publisher(),
            'mainEntity' => kingy_ali_schema_empty_item_list(),
        );
    }

    if ($path === 'compare-ai-models') {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => __('Compare AI Models', 'kingy-ai-launch-intelligence'),
            'description' => __('Compare source-backed AI model profiles by provider, access, pricing, modality, open-weight status, benchmark caveats, and verification notes.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/compare-ai-models/'),
            'publisher' => kingy_ali_schema_publisher(),
        );
    }

    return array();
}

function kingy_ali_start_homepage_template_cleanup_buffer() {
    if (!kingy_ali_should_clean_strategic_hub_template_clutter()) {
        return;
    }

    ob_start('kingy_ali_filter_homepage_template_clutter');
}

function kingy_ali_should_clean_strategic_hub_template_clutter() {
    return kingy_ali_is_phase1_public_quality_surface();
}

function kingy_ali_strategic_hub_template_html_marker_present($html) {
    foreach (array('page-id-914004', 'kingy-ali-launch-intelligence-page', 'kingy-ali-phase1-quality-surface', 'category-ai-launches', 'jeg_search_result') as $marker) {
        if (strpos($html, $marker) !== false) {
            return true;
        }
    }

    return false;
}

function kingy_ali_filter_homepage_template_clutter($html) {
    if (!is_string($html) || $html === '' || !kingy_ali_strategic_hub_template_html_marker_present($html)) {
        return $html;
    }

    $html = preg_replace_callback(
        '/<h1([^>]*class=(["\'])(?=[^"\']*\bsite-title\b)[^"\']*\2[^>]*)>(.*?)<\/h1>/is',
        static function ($matches) {
            return '<div' . $matches[1] . '>' . $matches[3] . '</div>';
        },
        $html,
        -1
    );

    $html = preg_replace_callback(
        '/(<div[^>]+id=(["\'])headerimg\2[^>]*>\s*)<h1\b[^>]*>(.*?)<\/h1>/is',
        static function ($matches) {
            return $matches[1] . '<div class="kingy-ali-cleaned-site-title">' . $matches[3] . '</div>';
        },
        $html,
        1
    );

    $html = preg_replace(
        '/\s*<div[^>]+id=(["\'])header\1[^>]*\s+role=(["\'])banner\2[^>]*>\s*<div[^>]+id=(["\'])headerimg\3[^>]*>.*?<\/div>\s*<\/div>\s*<hr\s*\/?>/is',
        '',
        $html,
        1
    );

    $html = preg_replace(
        '/\s*<hr\s*\/?>\s*<div[^>]+id=(["\'])footer\1[^>]*\s+role=(["\'])contentinfo\2[^>]*>.*?<\/div>/is',
        '',
        $html,
        1
    );

    $html = preg_replace(
        '/\s*(?:<!--\s*jeg_search_hide[^>]*?-->\s*)?<div class="jeg_search_result[^"]*">\s*<div class="search-result-wrapper">\s*<\/div>\s*<div class="search-link search-noresult">\s*No Result\s*<\/div>\s*<div class="search-link search-all-button">\s*<i class="fa fa-search"><\/i>\s*View All Result\s*<\/div>\s*<\/div>/is',
        '',
        $html
    );

    $html = preg_replace(
        '/\s*<div class="jeg_header_sticky">.*?(<div class="jeg_navbar_mobile_wrapper)/is',
        '$1',
        $html,
        1
    );

    $html = preg_replace(
        '/\s*<div class="jeg_footer_primary clearfix">.*?(<div class="jeg_footer_secondary clearfix">)/is',
        '$1',
        $html,
        1
    );

    $html = kingy_ali_filter_phase1_legacy_internal_links($html);
    $html = kingy_ali_filter_phase1_roi_heading_balance($html);
    $html = kingy_ali_filter_phase1_clients_heading_balance($html);

    return $html;
}

function kingy_ali_filter_phase1_clients_heading_balance($html) {
    if (!is_string($html) || $html === '' || kingy_ali_current_request_path() !== 'clients') {
        return $html;
    }

    $h1_count = preg_match_all('/<h1\b[^>]*>.*?<\/h1>/is', $html);
    if ($h1_count <= 1) {
        return $html;
    }

    return preg_replace_callback(
        '/<h1([^>]*class=(["\'])(?=[^"\']*\bkai-sponsor-title\b)[^"\']*\2[^>]*)>(.*?)<\/h1>/is',
        static function ($matches) {
            return '<h2' . $matches[1] . '>' . $matches[3] . '</h2>';
        },
        $html,
        1
    );
}

function kingy_ali_filter_phase1_roi_heading_balance($html) {
    if (!is_string($html) || $html === '') {
        return $html;
    }

    $path = kingy_ali_current_request_path();
    if (!in_array($path, array('ai-sponsored-video-roi-calculator', 'ai-launches/creator-campaign-roi-calculator'), true)) {
        return $html;
    }

    $h1_count = preg_match_all('/<h1\b[^>]*>.*?<\/h1>/is', $html);
    if ($h1_count === 0) {
        $upgraded = preg_replace_callback(
            '/<h2\b([^>]*)>(.*?)<\/h2>/is',
            static function ($matches) {
                $attributes = isset($matches[1]) ? (string) $matches[1] : '';
                $heading_text = function_exists('kingy_ali_normalize_heading_text')
                    ? kingy_ali_normalize_heading_text(isset($matches[2]) ? $matches[2] : '')
                    : strtolower(trim(wp_strip_all_tags(isset($matches[2]) ? $matches[2] : '')));

                if (
                    strpos($attributes, 'kingy-ali-page-title--injected') === false
                    && $heading_text !== 'ai sponsored video roi calculator'
                ) {
                    return $matches[0];
                }

                return '<h1' . $attributes . '>' . $matches[2] . '</h1>';
            },
            $html,
            1
        );

        return is_string($upgraded) ? $upgraded : $html;
    }

    if ($h1_count < 2) {
        return $html;
    }

    $balanced = preg_replace_callback(
        '/<h1\b([^>]*)>(.*?)<\/h1>/is',
        static function ($matches) {
            $attributes = isset($matches[1]) ? (string) $matches[1] : '';
            $heading_text = function_exists('kingy_ali_normalize_heading_text')
                ? kingy_ali_normalize_heading_text(isset($matches[2]) ? $matches[2] : '')
                : strtolower(trim(wp_strip_all_tags(isset($matches[2]) ? $matches[2] : '')));

            if (
                strpos($attributes, 'kingy-ali-page-title--injected') === false
                && $heading_text !== 'ai sponsored video roi calculator'
            ) {
                return $matches[0];
            }

            return '<h2' . $attributes . '>' . $matches[2] . '</h2>';
        },
        $html,
        1
    );

    return is_string($balanced) ? $balanced : $html;
}

function kingy_ali_filter_phase1_legacy_internal_links($html) {
    return preg_replace_callback(
        '/(\s+href\s*=\s*)(["\'])(.*?)\2/is',
        static function ($matches) {
            $replacement = kingy_ali_phase1_rewrite_legacy_internal_url($matches[3]);
            if ($replacement === '') {
                return $matches[0];
            }

            return $matches[1] . $matches[2] . esc_url($replacement) . $matches[2];
        },
        $html
    );
}

function kingy_ali_phase1_rewrite_legacy_internal_url($url) {
    $url = trim(html_entity_decode((string) $url, ENT_QUOTES, get_bloginfo('charset') ? get_bloginfo('charset') : 'UTF-8'));
    if ($url === '' || strpos($url, '#') === 0 || preg_match('/^(?:mailto|tel|javascript):/i', $url)) {
        return '';
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts)) {
        return '';
    }

    $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
    if ($host !== '' && !kingy_ali_phase1_is_internal_link_host($host)) {
        return '';
    }

    $path = isset($parts['path']) ? $parts['path'] : '';
    $target_path = kingy_ali_phase1_legacy_internal_link_target_path($path);
    if ($target_path === '') {
        return '';
    }

    $rewritten = home_url('/' . trim($target_path, '/') . '/');
    if (!empty($parts['query'])) {
        $rewritten .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $rewritten .= '#' . $parts['fragment'];
    }

    return $rewritten;
}

function kingy_ali_phase1_is_internal_link_host($host) {
    $host = strtolower(trim((string) $host));
    $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    $site_host = strtolower((string) wp_parse_url(site_url('/'), PHP_URL_HOST));

    return $host !== '' && in_array($host, array_filter(array($home_host, $site_host, 'kingy.ai', 'www.kingy.ai')), true);
}

function kingy_ali_phase1_legacy_internal_link_target_path($path, $title = '') {
    $path = trim((string) $path, '/');
    $path = preg_replace('#/+#', '/', $path);
    $title = trim((string) $title);
    $title_key = function_exists('mb_strtolower') ? mb_strtolower($title) : strtolower($title);

    if ($path === 'ai' && $title_key === 'ai courses') {
        return 'ai-courses';
    }

    $exact_map = array(
        'ai-news' => 'news',
        'category/ai-news' => 'news',
        'newsletter' => 'subscribe',
        'news/ai-ai-launch-tracker' => 'ai-launches',
        'ai-launch-tracker' => 'ai-launches',
    );
    if (!kingy_ali_repaired_strategic_routes_enabled()) {
        $exact_map['ai-models'] = 'ai-tools';
        $exact_map['compare-ai-models'] = 'ai-tools';
    }

    return isset($exact_map[$path]) ? $exact_map[$path] : '';
}

function kingy_ali_redirect_legacy_launch_intelligence_urls() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $path = kingy_ali_current_request_path();
    $redirects = array(
        'news/ai-ai-launch-tracker' => home_url('/ai-launches/'),
        'ai-news' => home_url('/news/'),
        'newsletter' => home_url('/subscribe/'),
    );

    if (isset($redirects[$path])) {
        wp_safe_redirect($redirects[$path], 301);
        exit;
    }

    if ($path === 'ai-launch-tracker' && is_post_type_archive('kingy_ai_launch')) {
        wp_safe_redirect(home_url('/ai-launches/'), 301);
        exit;
    }
}

function kingy_ali_current_request_path() {
    $uri = isset($_SERVER['REQUEST_URI']) && is_scalar($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = is_scalar($uri) ? (string) wp_parse_url((string) $uri, PHP_URL_PATH) : '';
    return trim((string) $path, '/');
}

function kingy_ali_news_feed_url() {
    $term = get_category_by_slug('news');
    if ($term && !is_wp_error($term)) {
        $feed_url = get_category_feed_link((int) $term->term_id);
        if (is_string($feed_url) && $feed_url !== '') {
            return $feed_url;
        }
    }

    return home_url('/category/news/feed/');
}

function kingy_ali_redirect_news_comments_feed() {
    if (is_admin() || wp_doing_ajax() || kingy_ali_current_request_path() !== 'news/feed') {
        return;
    }

    wp_safe_redirect(kingy_ali_news_feed_url(), 301, 'Kingy AI News Feed');
    exit;
}

function kingy_ali_output_news_feed_link() {
    if (!kingy_ali_is_news_directory()) {
        return;
    }

    echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__('Kingy AI News feed', 'kingy-ai-launch-intelligence') . '" href="' . esc_url(kingy_ali_news_feed_url()) . '">' . "\n";
}

function kingy_ali_prepare_home_feed_link() {
    if (!is_front_page()) {
        return;
    }

    remove_action('wp_head', 'feed_links', 2);
    add_action('wp_head', 'kingy_ali_output_home_feed_link', 2);
}

function kingy_ali_output_home_feed_link() {
    if (!is_front_page()) {
        return;
    }

    echo '<link rel="alternate" type="application/rss+xml" title="' . esc_attr__('Kingy AI feed', 'kingy-ai-launch-intelligence') . '" href="' . esc_url(get_feed_link()) . '">' . "\n";
}

function kingy_ali_launch_body_class($classes) {
    if (!is_array($classes)) {
        $classes = array();
    }

    if (
        kingy_ali_current_launch_collection_meta()
        || kingy_ali_current_launch_action_page_meta()
        || kingy_ali_current_directory_archive_type()
        || kingy_ali_current_related_page_meta()
        || is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company'))
        || (function_exists('kingy_ali_is_model_intelligence_page') && kingy_ali_is_model_intelligence_page())
    ) {
        $classes[] = 'kingy-ali-launch-intelligence-page';
    }

    if (kingy_ali_is_phase1_public_quality_surface()) {
        $classes[] = 'kingy-ali-phase1-quality-surface';
    }

    return array_values(array_unique($classes));
}

function kingy_ali_render_phase1_search_404_rescue_panel() {
    static $rendered = false;

    if ($rendered || (!is_search() && !is_404())) {
        return;
    }

    $rendered = true;
    $is_search_page = is_search();
    $query = $is_search_page ? get_search_query(false) : '';
    $found_posts = 0;
    if ($is_search_page && isset($GLOBALS['wp_query']) && $GLOBALS['wp_query'] instanceof WP_Query) {
        $found_posts = (int) $GLOBALS['wp_query']->found_posts;
    }

    if (is_404()) {
        $heading = __('This Kingy AI page is not available', 'kingy-ai-launch-intelligence');
        $body = __('The URL may have moved, the record may still be in review, or the page may no longer be public. Start with the main Kingy AI hubs below.', 'kingy-ai-launch-intelligence');
    } elseif ($found_posts > 0) {
        $heading = __('Keep exploring Kingy AI', 'kingy-ai-launch-intelligence');
        $body = __('If these search results are not the right fit, use the main Kingy AI hubs to browse launches, tools, companies, courses, and news directly.', 'kingy-ai-launch-intelligence');
    } else {
        $heading = __('No exact Kingy AI match yet', 'kingy-ai-launch-intelligence');
        $body = __('Try a broader search or use the main hubs to find AI launches, tools, companies, courses, news, sponsor paths, and the Launch Radar signup.', 'kingy-ai-launch-intelligence');
    }

    $links = array(
        array('label' => __('AI Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/')),
        array('label' => __('AI Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-tools/')),
        array('label' => __('AI Companies', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-companies/')),
        array('label' => __('AI Courses', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-courses/')),
        array('label' => __('AI News', 'kingy-ai-launch-intelligence'), 'url' => home_url('/news/')),
        array('label' => __('Subscribe', 'kingy-ai-launch-intelligence'), 'url' => home_url('/subscribe/')),
        array('label' => __('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'), 'url' => home_url('/sponsor-kingy-ai/')),
    );

    ?>
    <section class="kingy-ali-phase1-rescue" aria-labelledby="kingy-ali-phase1-rescue-heading">
        <div class="kingy-ali-phase1-rescue__inner">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI navigation', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-ali-phase1-rescue-heading"><?php echo esc_html($heading); ?></h2>
                <p><?php echo esc_html($body); ?></p>
            </div>
            <form class="kingy-ali-phase1-rescue__search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <label for="kingy-ali-phase1-search"><?php esc_html_e('Search Kingy AI', 'kingy-ai-launch-intelligence'); ?></label>
                <div>
                    <input id="kingy-ali-phase1-search" type="search" name="s" value="<?php echo esc_attr($query); ?>" placeholder="<?php esc_attr_e('Search AI launches, tools, companies, guides...', 'kingy-ai-launch-intelligence'); ?>">
                    <button type="submit"><?php esc_html_e('Search', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </form>
            <nav class="kingy-ali-phase1-rescue__links" aria-label="<?php esc_attr_e('Kingy AI fallback links', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($links as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>
    <?php
}

function kingy_ali_launch_action_content_title($content) {
    if (is_admin() || !is_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    if ($path !== 'ai-sponsored-video-roi-calculator') {
        return $content;
    }

    $visible_content = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $content);
    if (is_string($visible_content) && stripos($visible_content, '<h1') !== false) {
        return $content;
    }

    if (stripos($content, 'kai-roi-title') !== false) {
        $updated_content = preg_replace(
            '/<h2([^>]*class=(["\'])[^"\']*\bkai-roi-title\b[^"\']*\2[^>]*)>(.*?)<\/h2>/is',
            '<h1$1>$3</h1>',
            $content,
            1
        );

        if (is_string($updated_content) && $updated_content !== $content) {
            return $updated_content;
        }
    }

    return '<h1 class="kingy-ali-page-title kingy-ali-page-title--injected">' . esc_html__('AI Sponsored Video ROI Calculator', 'kingy-ai-launch-intelligence') . '</h1>' . $content;
}

function kingy_ali_output_schema() {
    if (kingy_ali_emergency_safe_mode_is_active()) {
        try {
            kingy_ali_output_schema_inner();
        } catch (Throwable $throwable) {
            kingy_ali_emergency_safe_mode_log('schema_output_failed', $throwable);
            $schema = kingy_ali_emergency_safe_mode_schema();
            if ($schema) {
                echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
            }
        }
        return;
    }

    kingy_ali_output_schema_inner();
}

function kingy_ali_output_schema_inner() {
    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta) {
        $schema = kingy_ali_launch_collection_schema($launch_collection_meta);
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        return;
    }

    $launch_action_page_meta = kingy_ali_current_launch_action_page_meta();
    if ($launch_action_page_meta) {
        $schema = kingy_ali_launch_action_page_schema($launch_action_page_meta);
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        return;
    }

    $directory_archive_type = kingy_ali_current_directory_archive_type();
    if ($directory_archive_type) {
        $schema = kingy_ali_directory_archive_schema($directory_archive_type);
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        return;
    }

    $related_page_meta = kingy_ali_current_related_page_meta();
    if ($related_page_meta) {
        $schema = kingy_ali_related_page_schema($related_page_meta);
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        return;
    }

    if (
        is_singular('post')
        && function_exists('kingy_ali_radar_post_title_matches')
        && kingy_ali_radar_post_title_matches(get_queried_object_id())
    ) {
        // Yoast already owns the Article node for standard posts. Keep this
        // fallback only for installations where Yoast is unavailable.
        if (!defined('WPSEO_VERSION') && !class_exists('WPSEO_Options')) {
            $schema = kingy_ali_daily_radar_article_schema(get_queried_object_id());
            echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
        }
        return;
    }

    if (!is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))) {
        return;
    }

    $post_id = get_the_ID();
    if (kingy_ali_profile_noindex_fail_open($post_id)) {
        return;
    }

    if (is_singular('kingy_ai_launch')) {
        $schema = kingy_ali_launch_schema($post_id);
    } elseif (is_singular('kingy_ai_tool')) {
        $schema = kingy_ali_tool_schema($post_id);
    } elseif (is_singular('kingy_ai_model')) {
        $schema = kingy_ali_model_schema($post_id);
    } else {
        $schema = kingy_ali_company_schema($post_id);
    }

    echo "\n<script type=\"application/ld+json\">" . wp_json_encode(kingy_ali_schema_filter($schema), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}

function kingy_ali_schema_url($url) {
    if (!is_scalar($url)) {
        return '';
    }

    if (function_exists('kingy_ali_sanitize_public_profile_link_url')) {
        return kingy_ali_sanitize_public_profile_link_url($url);
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

    $scheme = strtolower((string) $parts['scheme']);
    return in_array($scheme, array('http', 'https'), true) ? $url : '';
}

function kingy_ali_schema_text($value, $default = '') {
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

function kingy_ali_schema_meta_text($post_id, $key, $default = '') {
    if (function_exists('kingy_ali_public_profile_meta_text')) {
        return kingy_ali_public_profile_meta_text($post_id, $key, $default);
    }

    return kingy_ali_schema_text(kingy_ali_get_meta($post_id, $key, $default), $default);
}

function kingy_ali_schema_related_id($value) {
    if (function_exists('kingy_ali_public_profile_id')) {
        return kingy_ali_public_profile_id($value);
    }

    return is_scalar($value) ? absint($value) : 0;
}

function kingy_ali_indexable_url_meta($post_id, $key) {
    return kingy_ali_schema_url(kingy_ali_get_meta($post_id, $key));
}

function kingy_ali_launch_schema($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));
    $demo_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'demo_url'));
    $company = kingy_ali_schema_meta_text($post_id, 'company');
    $launch_date = kingy_ali_schema_meta_text($post_id, 'launch_date');
    $description = kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'meta_description', kingy_ali_schema_meta_text($post_id, 'what_launched', get_the_excerpt($post_id))));
    $related_tool_id = kingy_ali_schema_related_id(kingy_ali_get_meta($post_id, 'related_tool_id'));
    $related_tool = kingy_ali_related_post_is_public_index_ready($related_tool_id, 'kingy_ai_tool') ? get_post($related_tool_id) : null;

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        '@id' => kingy_ali_schema_entity_id($post_id, 'launch'),
        'name' => get_the_title($post_id),
        'description' => $description,
        'url' => get_permalink($post_id),
        'sameAs' => $official_url,
        'dateCreated' => $launch_date,
        'creator' => kingy_ali_schema_launch_organizer($post_id, $company),
        'about' => $related_tool ? kingy_ali_schema_software_application($related_tool->ID) : '',
        'keywords' => kingy_ali_schema_keywords($post_id),
        'audience' => kingy_ali_schema_audience($post_id),
        'image' => kingy_ali_schema_featured_image($post_id),
        'citation' => kingy_ali_schema_citations($post_id),
        'review' => kingy_ali_schema_kingy_review($post_id),
        'potentialAction' => kingy_ali_schema_actions(
            array(
                array('type' => 'ViewAction', 'name' => __('View official launch page', 'kingy-ai-launch-intelligence'), 'url' => $official_url),
                array('type' => 'WatchAction', 'name' => __('Watch demo', 'kingy-ai-launch-intelligence'), 'url' => $demo_url),
            )
        ),
        'mainEntityOfPage' => get_permalink($post_id),
        'publisher' => kingy_ali_schema_publisher(),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_time('c', $post_id),
    );

    return $schema;
}

function kingy_ali_current_launch_seo_meta($key) {
    if (!is_singular('kingy_ai_launch')) {
        return '';
    }

    $post_id = get_queried_object_id();
    return $post_id ? kingy_ali_schema_meta_text($post_id, $key) : '';
}

function kingy_ali_current_profile_seo_title() {
    if (!is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))) {
        return '';
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return '';
    }

    if (get_post_type($post_id) === 'kingy_ai_launch') {
        return kingy_ali_schema_meta_text($post_id, 'seo_title');
    }

    $title = get_the_title($post_id);
    if (!$title) {
        return '';
    }

    if (get_post_type($post_id) === 'kingy_ai_tool') {
        return sprintf(__('%s AI Tool Profile: Pricing, Demos, and Launches', 'kingy-ai-launch-intelligence'), $title);
    }

    if (get_post_type($post_id) === 'kingy_ai_model') {
        $custom_title = kingy_ali_schema_meta_text($post_id, 'seo_title');
        return $custom_title ? $custom_title : sprintf(__('%s AI Model Profile: Capabilities, Pricing, Sources, and Caveats', 'kingy-ai-launch-intelligence'), $title);
    }

    return sprintf(__('%s AI Company Profile: Tools, Funding, and Launches', 'kingy-ai-launch-intelligence'), $title);
}

function kingy_ali_current_profile_meta_description() {
    if (!is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))) {
        return '';
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return '';
    }

    $post_type = get_post_type($post_id);
    if ($post_type === 'kingy_ai_launch') {
        return kingy_ali_schema_meta_text($post_id, 'meta_description');
    }

    if ($post_type === 'kingy_ai_tool') {
        $description = kingy_ali_schema_meta_text($post_id, 'what_it_does');
        if (!$description) {
            $description = sprintf(
                __('Kingy AI profile for %s with company context, pricing, demo links, launch history, official sources, and last-verified notes.', 'kingy-ai-launch-intelligence'),
                get_the_title($post_id)
            );
        } else {
            $description .= ' ' . __('Includes pricing, demo links, company context, and linked launch history.', 'kingy-ai-launch-intelligence');
        }

        return kingy_ali_meta_description_excerpt($description);
    }

    if ($post_type === 'kingy_ai_model') {
        $description = kingy_ali_schema_meta_text($post_id, 'meta_description');
        if (!$description) {
            $description = kingy_ali_schema_meta_text($post_id, 'model_overview');
        }
        if (!$description) {
            $description = sprintf(
                __('Kingy AI model profile for %s with provider context, capabilities, access notes, benchmark caveats, official sources, and last-verified status.', 'kingy-ai-launch-intelligence'),
                get_the_title($post_id)
            );
        } else {
            $description .= ' ' . __('Includes access notes, source links, benchmark caveats, and last-verified status.', 'kingy-ai-launch-intelligence');
        }

        return kingy_ali_meta_description_excerpt($description);
    }

    $description = kingy_ali_schema_meta_text($post_id, 'company_summary');
    if (!$description) {
        $description = sprintf(
            __('Kingy AI company profile for %s with related tools, launch history, funding notes, official links, and last-verified context.', 'kingy-ai-launch-intelligence'),
            get_the_title($post_id)
        );
    } else {
        $description .= ' ' . __('Includes related tools, launch history, official links, and creator coverage signals.', 'kingy-ai-launch-intelligence');
    }

    return kingy_ali_meta_description_excerpt($description);
}

function kingy_ali_meta_description_excerpt($description, $max_length = 155) {
    $description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags(kingy_ali_schema_text($description))));
    $max_length = max(80, absint($max_length));
    if ($description === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($description) <= $max_length) {
        return $description;
    }

    if (!function_exists('mb_strlen') && strlen($description) <= $max_length) {
        return $description;
    }

    $trimmed = function_exists('mb_substr') ? mb_substr($description, 0, $max_length - 1) : substr($description, 0, $max_length - 1);
    $last_space = strrpos($trimmed, ' ');
    if ($last_space !== false && $last_space > 80) {
        $trimmed = substr($trimmed, 0, $last_space);
    }

    return rtrim($trimmed, " \t\n\r\0\x0B.,;:") . '...';
}

function kingy_ali_is_launch_coverage_archive() {
    return !is_admin() && is_category('ai-launch-tracker');
}

function kingy_ali_is_news_directory() {
    return !is_admin() && is_page('news');
}

function kingy_ali_public_directory_page_number() {
    if (kingy_ali_is_launch_coverage_archive()) {
        return max(1, absint(get_query_var('paged', 1)));
    }

    if (kingy_ali_is_news_directory()) {
        $value = function_exists('kingy_ali_request_get_value')
            ? kingy_ali_request_get_value('query-207101-page')
            : (isset($_GET['query-207101-page']) ? wp_unslash($_GET['query-207101-page']) : 1);
        return max(1, absint($value));
    }

    return 1;
}

function kingy_ali_public_directory_max_pages() {
    $slug = kingy_ali_is_launch_coverage_archive() ? 'ai-launch-tracker' : (kingy_ali_is_news_directory() ? 'news' : '');
    if ($slug === '') {
        return 1;
    }

    $term = get_category_by_slug($slug);
    if (!$term || is_wp_error($term)) {
        return 1;
    }

    return max(1, (int) ceil(absint($term->count) / 10));
}

function kingy_ali_public_directory_page_is_valid() {
    return kingy_ali_public_directory_page_number() <= kingy_ali_public_directory_max_pages();
}

function kingy_ali_public_directory_canonical_url() {
    $page = kingy_ali_public_directory_page_number();
    if (kingy_ali_is_launch_coverage_archive()) {
        $base = function_exists('kingy_ali_launch_coverage_archive_url')
            ? kingy_ali_launch_coverage_archive_url()
            : home_url('/ai-launches/coverage/');
        return $page > 1 && kingy_ali_public_directory_page_is_valid()
            ? trailingslashit($base) . 'page/' . $page . '/'
            : $base;
    }

    if (kingy_ali_is_news_directory()) {
        $base = home_url('/news/');
        return $page > 1 && kingy_ali_public_directory_page_is_valid()
            ? add_query_arg('query-207101-page', $page, $base)
            : $base;
    }

    return '';
}

function kingy_ali_public_directory_pagination_title() {
    $page = kingy_ali_public_directory_page_number();
    if (kingy_ali_is_launch_coverage_archive()) {
        return $page > 1
            ? sprintf(__('AI Launch Tracker Editorial Coverage — Page %d', 'kingy-ai-launch-intelligence'), $page)
            : __('AI Launch Tracker Editorial Coverage', 'kingy-ai-launch-intelligence');
    }

    if (kingy_ali_is_news_directory()) {
        return $page > 1
            ? sprintf(__('AI News — Page %d', 'kingy-ai-launch-intelligence'), $page)
            : __('AI News', 'kingy-ai-launch-intelligence');
    }

    return '';
}

function kingy_ali_launch_document_title($parts) {
    $directory_title = kingy_ali_public_directory_pagination_title();
    if ($directory_title !== '') {
        $parts['title'] = $directory_title;
        return $parts;
    }

    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta) {
        $parts['title'] = $launch_collection_meta['title'];
        return $parts;
    }

    $launch_action_page_meta = kingy_ali_current_launch_action_page_meta();
    if ($launch_action_page_meta) {
        $parts['title'] = $launch_action_page_meta['title'];
        return $parts;
    }

    $directory_meta = kingy_ali_current_directory_archive_meta();
    if ($directory_meta) {
        $parts['title'] = $directory_meta['title'];
        return $parts;
    }

    $related_page_meta = kingy_ali_current_related_page_meta();
    if ($related_page_meta) {
        $parts['title'] = $related_page_meta['title'];
        return $parts;
    }

    $seo_title = kingy_ali_current_profile_seo_title();
    if ($seo_title) {
        $parts['title'] = $seo_title;
    }

    return $parts;
}

function kingy_ali_launch_wpseo_title($title) {
    $directory_title = kingy_ali_public_directory_pagination_title();
    if ($directory_title !== '') {
        return $directory_title;
    }

    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta) {
        return $launch_collection_meta['title'];
    }

    $launch_action_page_meta = kingy_ali_current_launch_action_page_meta();
    if ($launch_action_page_meta) {
        return $launch_action_page_meta['title'];
    }

    $directory_meta = kingy_ali_current_directory_archive_meta();
    if ($directory_meta) {
        return $directory_meta['title'];
    }

    $related_page_meta = kingy_ali_current_related_page_meta();
    if ($related_page_meta) {
        return $related_page_meta['title'];
    }

    $seo_title = kingy_ali_current_profile_seo_title();
    return $seo_title ? $seo_title : $title;
}

function kingy_ali_phase1_social_meta() {
    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta && !empty($launch_collection_meta['social_title'])) {
        return $launch_collection_meta;
    }
    $directory_meta = kingy_ali_current_directory_archive_meta();
    if ($directory_meta && !empty($directory_meta['social_title'])) {
        return $directory_meta;
    }
    return array();
}

function kingy_ali_phase1_social_title($title) {
    $meta = kingy_ali_phase1_social_meta();
    return !empty($meta['social_title']) ? $meta['social_title'] : $title;
}

function kingy_ali_phase1_social_description($description) {
    $meta = kingy_ali_phase1_social_meta();
    return !empty($meta['social_description']) ? $meta['social_description'] : $description;
}

function kingy_ali_phase1_social_image($image) {
    if (kingy_ali_current_directory_archive_type() === 'tools') {
        return 'https://kingy.ai/wp-content/uploads/2026/07/kingy-ai-product-records-social.png';
    }
    $meta = kingy_ali_phase1_social_meta();
    if (!empty($meta['url']) && untrailingslashit($meta['url']) === untrailingslashit(home_url('/ai-launches/'))) {
        return 'https://kingy.ai/wp-content/uploads/2026/07/kingy-ai-launch-command-center-editorial-2026.jpg';
    }
    return $image;
}

function kingy_ali_phase1_social_image_alt($alt) {
    if (kingy_ali_current_directory_archive_type() === 'tools') {
        return __('A technology researcher checks product documentation, release history, pricing and hands-on evidence for an AI product record.', 'kingy-ai-launch-intelligence');
    }
    $meta = kingy_ali_phase1_social_meta();
    if (!empty($meta['url']) && untrailingslashit($meta['url']) === untrailingslashit(home_url('/ai-launches/'))) {
        return __('A product evaluation team reviews AI launch evidence, workflow notes and pricing information.', 'kingy-ai-launch-intelligence');
    }
    return $alt;
}

function kingy_ali_phase1_social_image_meta() {
    if (kingy_ali_current_directory_archive_type() !== 'tools') {
        return;
    }
    $image = 'https://kingy.ai/wp-content/uploads/2026/07/kingy-ai-product-records-social.png';
    $alt = __('A technology researcher checks product documentation, release history, pricing and hands-on evidence for an AI product record.', 'kingy-ai-launch-intelligence');
    echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    echo '<meta property="og:image:secure_url" content="' . esc_url($image) . '">' . "\n";
    echo '<meta property="og:image:type" content="image/png">' . "\n";
    echo '<meta property="og:image:width" content="1672">' . "\n";
    echo '<meta property="og:image:height" content="941">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($alt) . '">' . "\n";
}

function kingy_ali_launch_wpseo_meta_description($description) {
    if (kingy_ali_is_launch_coverage_archive()) {
        return __('Browse Kingy AI launch reporting, explainers, analysis, and daily AI Launch Radar coverage.', 'kingy-ai-launch-intelligence');
    }

    if (kingy_ali_is_news_directory()) {
        return __('Browse the latest AI news, analysis, product updates, and industry reporting from Kingy AI.', 'kingy-ai-launch-intelligence');
    }

    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta) {
        return $launch_collection_meta['description'];
    }

    $launch_action_page_meta = kingy_ali_current_launch_action_page_meta();
    if ($launch_action_page_meta) {
        return $launch_action_page_meta['description'];
    }

    $directory_meta = kingy_ali_current_directory_archive_meta();
    if ($directory_meta) {
        return $directory_meta['description'];
    }

    $related_page_meta = kingy_ali_current_related_page_meta();
    if ($related_page_meta) {
        return $related_page_meta['description'];
    }

    $meta_description = kingy_ali_current_profile_meta_description();
    return $meta_description ? $meta_description : $description;
}

function kingy_ali_model_page_canonical_url() {
    if (function_exists('kingy_ali_current_page_path') && function_exists('kingy_ali_model_page_paths')) {
        try {
            $model_page_path = kingy_ali_current_page_path();
            if ($model_page_path !== '' && in_array($model_page_path, kingy_ali_model_page_paths(), true)) {
                return home_url('/' . trim($model_page_path, '/') . '/');
            }
        } catch (Throwable $throwable) {
            kingy_ali_schema_fail_open_log('model_page_canonical_failed', $throwable);
        }
    }

    return '';
}

function kingy_ali_phase3a_output_june8_canonical() {
    $post_id = 913525;

    if (
        !is_singular('post')
        || get_queried_object_id() !== $post_id
        || '1' !== (string) get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true)
    ) {
        return;
    }

    $canonical = (string) get_post_meta($post_id, '_yoast_wpseo_canonical', true);
    if ($canonical === '') {
        $canonical = get_permalink($post_id);
    }

    if ($canonical !== '') {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
}

function kingy_ali_launch_wpseo_canonical($url) {
    $directory_canonical = kingy_ali_public_directory_canonical_url();
    if ($directory_canonical !== '') {
        return $directory_canonical;
    }

    $faceted_canonical = kingy_ali_faceted_taxonomy_canonical_url();
    if ($faceted_canonical) {
        return $faceted_canonical;
    }

    if (kingy_ali_is_filtered_or_search_results_page()) {
        return kingy_ali_filtered_results_canonical_url();
    }

    $model_page_canonical = kingy_ali_model_page_canonical_url();
    if ($model_page_canonical) {
        return $model_page_canonical;
    }

    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    if ($launch_collection_meta && !empty($launch_collection_meta['url'])) {
        return kingy_ali_launch_collection_page_canonical_url($launch_collection_meta['url']);
    }

    $launch_action_page_meta = kingy_ali_current_launch_action_page_meta();
    if ($launch_action_page_meta && !empty($launch_action_page_meta['url'])) {
        return $launch_action_page_meta['url'];
    }

    $directory_meta = kingy_ali_current_directory_archive_meta();
    if ($directory_meta && !empty($directory_meta['url'])) {
        return $directory_meta['url'];
    }

    $related_page_meta = kingy_ali_current_related_page_meta();
    if ($related_page_meta && !empty($related_page_meta['url'])) {
        return $related_page_meta['url'];
    }

    return $url;
}

function kingy_ali_launch_collection_page_canonical_url($base_url) {
    $page = function_exists('kingy_ali_request_get_value')
        ? kingy_ali_sanitize_launch_page(kingy_ali_request_get_value('kali_page'))
        : 1;
    return $page > 1 ? add_query_arg('kali_page', $page, $base_url) : $base_url;
}

function kingy_ali_launch_wpseo_robots($robots) {
    if (kingy_ali_emergency_safe_mode_is_active()) {
        try {
            return kingy_ali_launch_wpseo_robots_inner($robots);
        } catch (Throwable $throwable) {
            kingy_ali_emergency_safe_mode_log('wpseo_robots_failed', $throwable);
            return $robots;
        }
    }

    return kingy_ali_launch_wpseo_robots_inner($robots);
}

function kingy_ali_launch_wpseo_robots_inner($robots) {
    if (kingy_ali_is_launch_coverage_archive()) {
        return is_404()
            || !kingy_ali_public_directory_page_is_valid()
            || kingy_ali_public_directory_page_number() > 1
            ? 'noindex, follow'
            : 'index, follow';
    }

    if (kingy_ali_is_news_directory()) {
        return kingy_ali_public_directory_page_is_valid() ? 'index, follow' : 'noindex, follow';
    }

    if (is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))) {
        $post_id = get_queried_object_id();
        if ($post_id && kingy_ali_profile_noindex_fail_open($post_id)) {
            return 'noindex, follow';
        }
    }

    if (kingy_ali_model_page_noindex_fail_open()) {
        return 'noindex, follow';
    }

    if (kingy_ali_launch_collection_should_noindex()) {
        return 'noindex, follow';
    }

    if (kingy_ali_is_filtered_or_search_results_page()) {
        return 'noindex, follow';
    }

    return $robots;
}

function kingy_ali_exclude_faceted_taxonomy_from_yoast_sitemap($excluded, $taxonomy = '') {
    if (in_array((string) $taxonomy, kingy_ali_noindex_taxonomy_names(), true)) {
        return true;
    }

    return $excluded;
}

function kingy_ali_filter_launch_intelligence_sitemap_entry($url, $type, $object) {
    $loc = '';
    if (is_array($url) && isset($url['loc']) && is_scalar($url['loc'])) {
        $loc = (string) $url['loc'];
    } elseif (is_string($url)) {
        $loc = $url;
    }

    if ($loc && untrailingslashit($loc) === untrailingslashit(home_url('/ai-launch-tracker/'))) {
        return false;
    }

    if ($type !== 'term' || !is_object($object) || empty($object->taxonomy)) {
        return $url;
    }

    if (in_array((string) $object->taxonomy, kingy_ali_noindex_taxonomy_names(), true)) {
        return false;
    }

    return $url;
}

function kingy_ali_launch_sitemap_post_type_archive_link($archive_url, $post_type) {
    if ((string) $post_type === 'kingy_ai_launch') {
        return false;
    }

    return $archive_url;
}

function kingy_ali_filter_core_sitemap_taxonomies($taxonomies) {
    if (!is_array($taxonomies)) {
        return $taxonomies;
    }

    foreach (kingy_ali_noindex_taxonomy_names() as $taxonomy) {
        unset($taxonomies[$taxonomy]);
    }

    return $taxonomies;
}

function kingy_ali_launch_wpseo_rel_link($link) {
    if (!is_string($link) || !is_tax('kingy_launch_category')) {
        return $link;
    }

    $term = get_queried_object();
    if (!$term || is_wp_error($term) || empty($term->slug)) {
        return $link;
    }

    $legacy_base = home_url('/ai-launches/' . $term->slug . '/');
    $current_base = home_url('/ai-launch-category/' . $term->slug . '/');

    return str_replace($legacy_base, $current_base, $link);
}

function kingy_ali_output_launch_meta_description() {
    $launch_collection_meta = kingy_ali_current_launch_collection_meta();
    $launch_action_page_meta = $launch_collection_meta ? array() : kingy_ali_current_launch_action_page_meta();
    $directory_meta = ($launch_collection_meta || $launch_action_page_meta) ? array() : kingy_ali_current_directory_archive_meta();
    $related_page_meta = ($launch_collection_meta || $launch_action_page_meta || $directory_meta) ? array() : kingy_ali_current_related_page_meta();
    $meta_description = $launch_collection_meta
        ? $launch_collection_meta['description']
        : ($launch_action_page_meta ? $launch_action_page_meta['description'] : ($directory_meta ? $directory_meta['description'] : ($related_page_meta ? $related_page_meta['description'] : kingy_ali_current_profile_meta_description())));
    if (!$meta_description || defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
        return;
    }

    echo "\n<meta name=\"description\" content=\"" . esc_attr($meta_description) . "\">\n";

    $title = '';
    if ($launch_collection_meta) {
        $title = $launch_collection_meta['title'];
    } elseif ($launch_action_page_meta) {
        $title = $launch_action_page_meta['title'];
    } elseif ($directory_meta) {
        $title = $directory_meta['title'];
    } elseif ($related_page_meta) {
        $title = $related_page_meta['title'];
    }

    if ($title) {
        echo "<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">\n";
        echo "<meta property=\"og:description\" content=\"" . esc_attr($meta_description) . "\">\n";
        echo "<meta name=\"twitter:title\" content=\"" . esc_attr($title) . "\">\n";
        echo "<meta name=\"twitter:description\" content=\"" . esc_attr($meta_description) . "\">\n";
    }
}

function kingy_ali_tool_schema($post_id) {
    $schema = kingy_ali_schema_software_application($post_id);
    $schema['@context'] = 'https://schema.org';
    $schema['mainEntityOfPage'] = get_permalink($post_id);
    $schema['publisher'] = kingy_ali_schema_publisher();

    return $schema;
}

function kingy_ali_model_schema($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));
    $announcement_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_announcement_url'));
    $docs_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_docs_url'));
    $model_card_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'model_card_url'));
    $provider = kingy_ali_schema_meta_text($post_id, 'provider_name');
    if (!$provider && function_exists('kingy_ali_model_terms_to_string')) {
        $provider = kingy_ali_model_terms_to_string($post_id, 'model_provider');
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        '@id' => kingy_ali_schema_entity_id($post_id, 'model-profile'),
        'headline' => get_the_title($post_id),
        'name' => get_the_title($post_id),
        'description' => kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'model_overview', get_the_excerpt($post_id))),
        'url' => get_permalink($post_id),
        'about' => array(
            '@type' => 'Thing',
            'name' => get_the_title($post_id),
            'description' => kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'model_overview', get_the_excerpt($post_id))),
            'url' => $official_url ? $official_url : get_permalink($post_id),
            'sameAs' => array_values(array_filter(array($official_url, $announcement_url, $docs_url, $model_card_url))),
            'manufacturer' => $provider ? array('@type' => 'Organization', 'name' => $provider) : '',
        ),
        'articleSection' => __('AI Model Intelligence', 'kingy-ai-launch-intelligence'),
        'keywords' => kingy_ali_schema_keywords($post_id),
        'citation' => kingy_ali_schema_citations($post_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'publisher' => kingy_ali_schema_publisher(),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_time('c', $post_id),
    );
}

function kingy_ali_company_schema($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => kingy_ali_schema_entity_id($post_id, 'company'),
        'name' => get_the_title($post_id),
        'description' => kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'company_summary', get_the_excerpt($post_id))),
        'url' => $official_url ? $official_url : get_permalink($post_id),
        'sameAs' => $official_url && $official_url !== get_permalink($post_id) ? $official_url : '',
        'image' => kingy_ali_schema_featured_image($post_id),
        'keywords' => kingy_ali_schema_keywords($post_id),
        'audience' => kingy_ali_schema_audience($post_id),
        'citation' => kingy_ali_schema_citations($post_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'publisher' => kingy_ali_schema_publisher(),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_time('c', $post_id),
    );
}

function kingy_ali_related_pages_meta() {
    $pages = array(
        'ai-courses' => array(
            'title' => __('AI Courses: Practical Kingy AI Learning Paths', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse Kingy AI courses and learning paths for Codex, Microsoft Copilot, AI app building, AI workflows, AI search visibility, and beginner-safe AI tools.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-courses/'),
            'type' => 'CollectionPage',
        ),
        'ai/build-with-ai-academy/tools/codex-prompt-builder' => array(
            'title' => __('Codex Prompt Builder: Write Better AI Build Prompts', 'kingy-ai-launch-intelligence'),
            'description' => __('Use the Codex Prompt Builder to turn an AI build idea into a scoped prompt with goals, files, constraints, tests, safety checks, and clear done criteria.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/'),
            'type' => 'WebPage',
        ),
        'ai/build-with-ai-academy/articles/lovable-vs-replit-vs-bolt-vs-bubble-vs-softr' => array(
            'title' => __('Lovable vs Replit vs Bolt vs Bubble vs Softr: AI App Builder Comparison', 'kingy-ai-launch-intelligence'),
            'description' => __('Compare Lovable, Replit, Bolt, Bubble, and Softr for beginner AI app building by workflow, strengths, limits, pricing model, and best first project fit.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/articles/lovable-vs-replit-vs-bolt-vs-bubble-vs-softr/'),
            'type' => 'Article',
        ),
    );

    if (function_exists('kingy_ali_ai_launch_academy_related_pages_meta')) {
        $pages = array_merge($pages, kingy_ali_ai_launch_academy_related_pages_meta());
    }

    $pages['compare-ai-models'] = array(
        'title' => __('Compare AI Models: Capabilities, Pricing, Access, and Caveats', 'kingy-ai-launch-intelligence'),
        'description' => __('Compare source-backed AI model profiles by provider, modality, context, API access, pricing notes, open-weight status, benchmark caveats, and last verification date.', 'kingy-ai-launch-intelligence'),
        'url' => home_url('/compare-ai-models/'),
        'type' => 'WebPage',
    );

    if (function_exists('kingy_ali_best_model_page_configs')) {
        foreach (kingy_ali_best_model_page_configs() as $path => $config) {
            $title = isset($config['title']) ? $config['title'] : ucwords(str_replace('-', ' ', $path));
            $pages[$path] = array(
                'title' => sprintf(__('%s: Source-Backed Kingy AI Shortlist', 'kingy-ai-launch-intelligence'), $title),
                'description' => sprintf(__('Review %s using Kingy AI model profile data with access notes, source links, benchmark caveats, and last-verified status.', 'kingy-ai-launch-intelligence'), $title),
                'url' => home_url('/' . trim($path, '/') . '/'),
                'type' => 'CollectionPage',
            );
        }
    }

    return $pages;
}

function kingy_ali_current_related_page_meta() {
    if (is_category('ai-launches')) {
        $category = get_queried_object();
        $url = $category && !is_wp_error($category) ? get_category_link($category) : home_url('/category/ai-launches/');
        if (is_wp_error($url)) {
            $url = home_url('/category/ai-launches/');
        }

        return array(
            'title' => __('AI Launches Archive: Launch Intelligence Articles and Updates', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse Kingy AI articles connected to AI launches, launch intelligence, tool updates, founder coverage, and practical AI product discovery.', 'kingy-ai-launch-intelligence'),
            'url' => $url,
            'type' => 'CollectionPage',
        );
    }

    if (!is_page()) {
        return array();
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    if ($path === '') {
        return array();
    }

    $pages = kingy_ali_related_pages_meta();
    return isset($pages[$path]) ? $pages[$path] : array();
}

function kingy_ali_related_page_schema($meta) {
    return array(
        '@context' => 'https://schema.org',
        '@type' => isset($meta['type']) ? $meta['type'] : 'WebPage',
        'name' => isset($meta['title']) ? $meta['title'] : get_the_title(),
        'description' => isset($meta['description']) ? $meta['description'] : '',
        'url' => isset($meta['url']) ? $meta['url'] : get_permalink(),
        'publisher' => kingy_ali_schema_publisher(),
        'mainEntityOfPage' => isset($meta['url']) ? $meta['url'] : get_permalink(),
    );
}

function kingy_ali_launch_collection_pages_meta() {
    return array(
        'ai-launches' => array(
            'title' => __('AI Launch Intelligence: Changes & Evidence | Kingy AI', 'kingy-ai-launch-intelligence'),
            'description' => __('Track AI launches, model updates, pricing changes and tested workflows. See official sources, company claims, evidence and what remains unverified.', 'kingy-ai-launch-intelligence'),
            'social_title' => __('What launched, what changed, what it costs, and what worked', 'kingy-ai-launch-intelligence'),
            'social_description' => __('Follow source-linked AI launches, product changes, pricing and workflow evidence without treating every company claim as verified.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/'),
            'query_args' => array('limit' => 18),
        ),
        'ai-launches/today' => array(
            'title' => __('Today\'s AI Launches: New AI Tools and Model Releases', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse today\'s structured AI launch records with source links, categories, audience fit, demo signals, pricing notes, and Kingy AI editorial scores.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/today/'),
            'query_args' => array('period' => 'today'),
        ),
        'ai-launches/this-week' => array(
            'title' => __('This Week\'s AI Launches: Important New AI Tools and Updates', 'kingy-ai-launch-intelligence'),
            'description' => __('Review this week\'s most important AI launches, including tools, agents, model releases, video tools, coding tools, open-weight launches, and funding signals.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/this-week/'),
            'query_args' => array('period' => 'week'),
        ),
        'ai-launches/ai-agents' => array(
            'title' => __('AI Agent Launches: New Agent Tools, Workflows, and Platforms', 'kingy-ai-launch-intelligence'),
            'description' => __('Track new AI agent launches with official links, launch dates, audience fit, demo quality, API availability, and Kingy AI launch context.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-agents/'),
            'query_args' => array('category' => 'ai-agents'),
        ),
        'ai-launches/ai-video-tools' => array(
            'title' => __('AI Video Tool Launches: New Video AI Products and Demos', 'kingy-ai-launch-intelligence'),
            'description' => __('Find new AI video tool launches, demo-ready products, creator-friendly workflows, pricing notes, source links, and YouTube potential signals.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-video-tools/'),
            'query_args' => array('category' => 'ai-video-tools'),
        ),
        'ai-launches/ai-coding-tools' => array(
            'title' => __('AI Coding Tool Launches: New Developer Tools and Code Agents', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse AI coding tool launches with GitHub signals, API availability, open-source or open-weight notes, developer audience fit, and source-backed launch data.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-coding-tools/'),
            'query_args' => array('category' => 'ai-coding-tools'),
        ),
        'ai-launches/ai-image-tools' => array(
            'title' => __('AI Image Tool Launches: New Image Generation and Design AI Tools', 'kingy-ai-launch-intelligence'),
            'description' => __('Explore new AI image tool launches with categories, audiences, demos, pricing notes, creator usefulness, and Kingy AI launch scores.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-image-tools/'),
            'query_args' => array('category' => 'ai-image-tools'),
        ),
        'ai-launches/open-weight-models' => array(
            'title' => __('AI Open-Weight Model Launches: New Open AI Models and Releases', 'kingy-ai-launch-intelligence'),
            'description' => __('Track open-weight AI model launches with Hugging Face, GitHub, source, audience, pricing, API, and creator or developer usefulness signals.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/open-weight-models/'),
            'query_args' => array('category' => 'ai-open-weight-models'),
        ),
        'ai-launches/ai-search-research-tools' => array(
            'title' => __('AI Search and Research Tool Launches: Source-Backed AI Research Products', 'kingy-ai-launch-intelligence'),
            'description' => __('Compare AI search and research launches with retrieval, citations, official source links, API availability, report workflows, and editorial trust notes.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-search-research-tools/'),
            'query_args' => array('category' => 'ai-search-research-tools'),
        ),
        'ai-launches/ai-app-builders' => array(
            'title' => __('AI App Builder and Vibe Coding Launches: New AI Software Builders', 'kingy-ai-launch-intelligence'),
            'description' => __('Track AI app builders, vibe coding tools, AI IDEs, coding agents, and prompt-to-app launches with source-backed context and production-readiness notes.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/ai-app-builders/'),
            'query_args' => array('category' => 'ai-coding-tools', 'limit' => 18),
        ),
        'ai-launches/youtube-worthy-ai-tools' => array(
            'title' => __('YouTube-Worthy AI Tools: AI Launches With Strong Demo Potential', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse AI launches with strong demo quality, clear use cases, creator-friendly angles, and high YouTube content potential for reviews and explainers.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/youtube-worthy-ai-tools/'),
            'query_args' => array('youtube_worthy' => true),
        ),
        'ai-launches/founder-submitted-ai-tools' => array(
            'title' => __('Founder-Submitted AI Tools: New Launches From AI Founders', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse founder-submitted AI launch records with official links, launch dates, categories, audience fit, demo notes, pricing clarity, and Kingy AI editorial context.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/founder-submitted-ai-tools/'),
            'query_args' => array('attribute' => 'founder-submitted'),
        ),
        'ai-launches/founder-submitted-ai-tools-2' => array(
            'title' => __('Founder-Submitted AI Tools: New Launches From AI Founders', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse founder-submitted AI launch records with official links, launch dates, categories, audience fit, demo notes, pricing clarity, and Kingy AI editorial context.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/founder-submitted-ai-tools-2/'),
            'query_args' => array('attribute' => 'founder-submitted'),
        ),
        'ai-launches/funding-announcements' => array(
            'title' => __('AI Funding Announcements: Funded AI Startups and Launch Signals', 'kingy-ai-launch-intelligence'),
            'description' => __('Track AI funding announcements connected to structured launch records, source links, categories, audience fit, traction notes, and Kingy AI editorial context.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/funding-announcements/'),
            'query_args' => array('attribute' => 'funding-announced'),
        ),
        'ai-launches/creator-coverage-ai-launches' => array(
            'title' => __('AI Launches With Creator Coverage Potential', 'kingy-ai-launch-intelligence'),
            'description' => __('Discover AI companies and launches that may support demos, reviews, creator education, founder storytelling, and practical product explainers.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/creator-coverage-ai-launches/'),
            'query_args' => array('creator_coverage' => true),
        ),
    );
}

function kingy_ali_current_launch_collection_meta() {
    if (is_post_type_archive('kingy_ai_launch')) {
        $pages = kingy_ali_launch_collection_pages_meta();
        return $pages['ai-launches'];
    }

    if (is_tax('kingy_launch_category')) {
        $term = get_queried_object();
        if (!$term || is_wp_error($term)) {
            return array();
        }

        $term_url = home_url('/ai-launch-category/' . $term->slug . '/');

        return array(
            'title' => sprintf(__('%s AI Launches: New Tools, Demos, and Updates', 'kingy-ai-launch-intelligence'), $term->name),
            'description' => sprintf(__('Browse source-backed %s launch records with launch dates, audience fit, demo signals, pricing notes, and Kingy AI editorial context.', 'kingy-ai-launch-intelligence'), $term->name),
            'url' => $term_url,
            'query_args' => array('category' => $term->slug),
        );
    }

    if (!is_page()) {
        return array();
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    if ($path === '') {
        return array();
    }

    $pages = kingy_ali_launch_collection_pages_meta();
    return isset($pages[$path]) ? $pages[$path] : array();
}

function kingy_ali_launch_collection_should_noindex() {
    static $checking = false;
    if ($checking) {
        return false;
    }

    $checking = true;
    try {
        return kingy_ali_launch_collection_should_noindex_inner();
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('collection_noindex_failed', $throwable);
        return false;
    } finally {
        $checking = false;
    }
}

function kingy_ali_launch_collection_should_noindex_inner() {
    if (is_tax('kingy_launch_category')) {
        return true;
    }

    $meta = kingy_ali_current_launch_collection_meta();
    if (!$meta || empty($meta['query_args']) || !is_array($meta['query_args'])) {
        return false;
    }

    $query_args = $meta['query_args'];
    $requested_page = function_exists('kingy_ali_request_get_value')
        ? kingy_ali_sanitize_launch_page(kingy_ali_request_get_value('kali_page'))
        : 1;
    if ($requested_page > 1) {
        $query_args['limit'] = !empty($query_args['limit']) ? absint($query_args['limit']) : 12;
        $query_args['page'] = $requested_page;
        $page_query = kingy_ali_query_launches($query_args);
        $page_is_empty = empty($page_query->posts);
        wp_reset_postdata();
        if ($page_is_empty) {
            return true;
        }
    }

    if (kingy_ali_launch_collection_path_has_useful_fallback(kingy_ali_current_request_path())) {
        return false;
    }

    $query_args['limit'] = 1;
    $query = kingy_ali_query_launches($query_args);
    $is_empty = empty($query->posts);
    wp_reset_postdata();

    return $is_empty;
}

function kingy_ali_launch_collection_path_has_useful_fallback($path) {
    static $checking = false;
    if ($checking) {
        return false;
    }

    $checking = true;
    try {
        return kingy_ali_launch_collection_path_has_useful_fallback_inner($path);
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('collection_fallback_check_failed', $throwable);
        return false;
    } finally {
        $checking = false;
    }
}

function kingy_ali_launch_collection_path_has_useful_fallback_inner($path) {
    if (!in_array((string) $path, array('ai-launches/today', 'ai-launches/this-week'), true)) {
        return false;
    }

    if (function_exists('kingy_ali_latest_daily_launch_radar_post_id') && kingy_ali_latest_daily_launch_radar_post_id()) {
        return true;
    }

    $fallback_query = kingy_ali_query_launches(array('limit' => 1));
    $has_fallback_record = !empty($fallback_query->posts);
    wp_reset_postdata();

    return $has_fallback_record;
}

function kingy_ali_schema_empty_item_list() {
    return array(
        '@type' => 'ItemList',
        'numberOfItems' => 0,
        'itemListElement' => array(),
    );
}

function kingy_ali_schema_breadcrumb_list($items) {
    $elements = array();
    $position = 1;
    foreach ((array) $items as $item) {
        if (empty($item['name']) || empty($item['url'])) {
            continue;
        }

        $elements[] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => wp_strip_all_tags((string) $item['name']),
            'item' => esc_url_raw($item['url']),
        );
        $position++;
    }

    return array(
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    );
}

function kingy_ali_launch_collection_breadcrumb($meta) {
    $url = isset($meta['url']) ? $meta['url'] : home_url('/ai-launches/');
    $items = array(
        array('name' => get_bloginfo('name'), 'url' => home_url('/')),
        array('name' => __('AI Launch Command Center', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/')),
    );

    if (untrailingslashit($url) !== untrailingslashit(home_url('/ai-launches/'))) {
        $items[] = array(
            'name' => isset($meta['title']) ? $meta['title'] : __('AI Launches', 'kingy-ai-launch-intelligence'),
            'url' => $url,
        );
    }

    return kingy_ali_schema_breadcrumb_list($items);
}

function kingy_ali_schema_fail_open_log($context, $throwable = null) {
    if (function_exists('kingy_ali_log_public_query_failure')) {
        kingy_ali_log_public_query_failure($context, $throwable);
        return;
    }

    if (!function_exists('error_log')) {
        return;
    }

    $message = is_scalar($context) ? sanitize_key((string) $context) : 'unknown';
    if ($throwable instanceof Throwable) {
        $message .= ': ' . sanitize_text_field($throwable->getMessage());
    }

    error_log('Kingy AI Launch Intelligence schema fail-open: ' . $message);
}

function kingy_ali_launch_action_pages_meta() {
    return array(
        'ai-launches/submit' => array(
            'title' => __('Submit an AI Launch to Kingy AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
            'description' => __('Send a new AI launch, model release, funding announcement, or product update to Kingy AI for editorial review and possible Launch Intelligence coverage.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/submit/'),
            'action_name' => __('Submit an AI launch', 'kingy-ai-launch-intelligence'),
        ),
        'ai-launches/launch-visibility-score' => array(
            'title' => __('AI Launch Visibility Score Calculator: Check Launch Readiness', 'kingy-ai-launch-intelligence'),
            'description' => __('Score an AI launch across demo quality, pricing clarity, traction signals, founder visibility, audience fit, comparison angle, and launch distribution readiness.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/launch-visibility-score/'),
            'action_name' => __('Calculate launch visibility', 'kingy-ai-launch-intelligence'),
        ),
        'ai-launch-scorecard' => array(
            'title' => __('AI Launch Scorecard: 100-Point Launch Readiness Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Use the Kingy AI Launch Scorecard to rate product clarity, demo quality, pricing, founder visibility, SEO potential, creator coverage fit, and launch readiness.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launch-scorecard/'),
            'action_name' => __('Calculate AI launch readiness', 'kingy-ai-launch-intelligence'),
            'app' => true,
            'schema_type' => 'SoftwareApplication',
            'application_category' => 'BusinessApplication',
            'feature_list' => array(
                __('100-point AI launch readiness score', 'kingy-ai-launch-intelligence'),
                __('Missing, Partial, and Strong category scoring', 'kingy-ai-launch-intelligence'),
                __('Launch tier, verdict, strengths, weaknesses, and 7-day fix list', 'kingy-ai-launch-intelligence'),
                __('SEO, YouTube, and founder launch angle suggestions', 'kingy-ai-launch-intelligence'),
                __('Score summary copy button and Kingy AI review request form', 'kingy-ai-launch-intelligence'),
            ),
            'faq' => array(
                array(
                    'question' => __('What is the AI Launch Scorecard?', 'kingy-ai-launch-intelligence'),
                    'answer' => __('It is a 100-point educational readiness tool for AI founders preparing a product launch, Product Hunt campaign, SEO push, newsletter pitch, creator review, or Kingy AI Launch Intelligence submission.', 'kingy-ai-launch-intelligence'),
                ),
                array(
                    'question' => __('Does a high score guarantee Kingy AI coverage?', 'kingy-ai-launch-intelligence'),
                    'answer' => __('No. The score helps founders diagnose launch readiness. Kingy AI coverage, rankings, revenue, creator interest, and Product Hunt performance are never guaranteed.', 'kingy-ai-launch-intelligence'),
                ),
                array(
                    'question' => __('What usually improves an AI launch score fastest?', 'kingy-ai-launch-intelligence'),
                    'answer' => __('The fastest improvements usually come from a clearer audience, a short product demo, visible pricing or free-plan status, founder/company proof, and a comparison angle that helps buyers understand the category.', 'kingy-ai-launch-intelligence'),
                ),
                array(
                    'question' => __('Why does creator coverage fit matter?', 'kingy-ai-launch-intelligence'),
                    'answer' => __('Creators need a product that can be shown, explained, tested, and tied to a useful audience lesson. A launch with no demo, no before-and-after, or no clear user story is harder to cover well.', 'kingy-ai-launch-intelligence'),
                ),
                array(
                    'question' => __('Should I submit before fixing every weakness?', 'kingy-ai-launch-intelligence'),
                    'answer' => __('You can submit early, especially if the product is timely, but the strongest submissions include official sources, a working demo, pricing context, a clear audience, traction signals, and notes on what changed.', 'kingy-ai-launch-intelligence'),
                ),
            ),
        ),
        'ai-launches/creator-campaign-roi-calculator' => array(
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'description' => __('Calculate sponsored YouTube video ROI for an AI product using creator cost, views, CTR, conversion rate, customer value, CAC, CPM, break-even customers, and shareable results.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/creator-campaign-roi-calculator/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'ai-launches/creator-campaign-roi-calculator-2' => array(
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'description' => __('Calculate sponsored YouTube video ROI for an AI product using creator cost, views, CTR, conversion rate, customer value, CAC, CPM, break-even customers, and shareable results.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/creator-campaign-roi-calculator-2/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'ai-launches/youtube-sponsorship-roi-calculator' => array(
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'description' => __('Estimate YouTube sponsorship ROI, CAC, CPM, break-even customers, and next steps before buying a creator video or integration for an AI product.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-launches/youtube-sponsorship-roi-calculator/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'ai-sponsored-video-roi-calculator' => array(
            'title' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'description' => __('Calculate sponsored YouTube video ROI for an AI product using creator cost, views, CTR, conversion rate, customer value, CAC, CPM, break-even customers, and shareable results.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-sponsored-video-roi-calculator/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'youtube-sponsorship-roi-calculator' => array(
            'title' => __('YouTube Sponsorship ROI Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Estimate YouTube sponsorship ROI, CAC, CPM, break-even customers, and next steps before buying a creator video or integration.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/youtube-sponsorship-roi-calculator/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'youtube-sponsorship-roi-calculator-2' => array(
            'title' => __('YouTube Sponsorship ROI Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Estimate YouTube sponsorship ROI, CAC, CPM, break-even customers, and next steps before buying a creator video or integration.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/youtube-sponsorship-roi-calculator-2/'),
            'action_name' => __('Calculate YouTube sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'influencer-marketing-cac-calculator' => array(
            'title' => __('Influencer Marketing CAC Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Calculate influencer marketing CAC from expected reach, CTR, conversion rate, customer value, and campaign cost for AI and SaaS creator campaigns.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/influencer-marketing-cac-calculator/'),
            'action_name' => __('Calculate influencer marketing CAC', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'creator-sponsorship-payback-calculator' => array(
            'title' => __('Creator Sponsorship Payback Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Model creator sponsorship payback, required customers, estimated clicks, CAC, CPM, and ROI before committing campaign budget.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/creator-sponsorship-payback-calculator/'),
            'action_name' => __('Calculate creator sponsorship payback', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'ai-product-sponsorship-calculator' => array(
            'title' => __('AI Product Sponsorship Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Plan AI product sponsorship economics with editable assumptions for creator fee, YouTube views, funnel conversion, customer value, CAC, and ROI.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-product-sponsorship-calculator/'),
            'action_name' => __('Calculate AI product sponsorship ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
        'youtube-sponsorship-rate-vs-roi-calculator' => array(
            'title' => __('YouTube Sponsorship Rate vs ROI Calculator', 'kingy-ai-launch-intelligence'),
            'description' => __('Compare a proposed YouTube sponsorship rate against expected ROI, CAC, CPM, break-even customers, and funnel value for an AI product.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/youtube-sponsorship-rate-vs-roi-calculator/'),
            'action_name' => __('Compare YouTube sponsorship rate and ROI', 'kingy-ai-launch-intelligence'),
            'app' => true,
        ),
    );
}

function kingy_ali_current_launch_action_page_meta() {
    if (!is_page()) {
        return array();
    }

    $post_id = get_queried_object_id();
    $path = $post_id ? trim((string) get_page_uri($post_id), '/') : '';
    if ($path === '') {
        return array();
    }

    $pages = kingy_ali_launch_action_pages_meta();
    return isset($pages[$path]) ? $pages[$path] : array();
}

function kingy_ali_launch_action_page_schema($meta) {
    $url = isset($meta['url']) ? $meta['url'] : home_url('/ai-launches/');
    $site_icon = function_exists('get_site_icon_url') ? kingy_ali_schema_url(get_site_icon_url(512)) : '';

    $web_page = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => isset($meta['title']) ? $meta['title'] : __('Kingy AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
        'description' => isset($meta['description']) ? $meta['description'] : '',
        'url' => $url,
        'publisher' => kingy_ali_schema_publisher(),
        'potentialAction' => array(
            '@type' => 'Action',
            'name' => isset($meta['action_name']) ? $meta['action_name'] : __('Use Launch Intelligence tool', 'kingy-ai-launch-intelligence'),
            'target' => $url,
        ),
    );

    if (empty($meta['app'])) {
        return $web_page;
    }

    $schema_type = isset($meta['schema_type']) && in_array($meta['schema_type'], array('SoftwareApplication', 'WebApplication'), true) ? $meta['schema_type'] : 'WebApplication';
    $feature_list = isset($meta['feature_list']) && is_array($meta['feature_list']) ? $meta['feature_list'] : array(
        __('60-second sponsorship ROI model', 'kingy-ai-launch-intelligence'),
        __('CAC, CPM, ROI, and break-even customer estimates', 'kingy-ai-launch-intelligence'),
        __('Editable AI company presets', 'kingy-ai-launch-intelligence'),
        __('Creator deal evaluator', 'kingy-ai-launch-intelligence'),
        __('Shareable result URL and CSV export', 'kingy-ai-launch-intelligence'),
    );

    $app = array(
        '@context' => 'https://schema.org',
        '@type' => $schema_type,
        '@id' => trailingslashit($url) . '#calculator',
        'name' => isset($meta['title']) ? $meta['title'] : __('Kingy AI Launch Intelligence Tool', 'kingy-ai-launch-intelligence'),
        'description' => isset($meta['description']) ? $meta['description'] : '',
        'url' => $url,
        'applicationCategory' => isset($meta['application_category']) ? $meta['application_category'] : 'BusinessApplication',
        'operatingSystem' => 'Web',
        'isAccessibleForFree' => true,
        'image' => $site_icon,
        'screenshot' => $site_icon,
        'featureList' => $feature_list,
        'publisher' => kingy_ali_schema_publisher(),
    );

    $web_page['mainEntity'] = array('@id' => $app['@id']);
    $graph = array($web_page, $app);

    if (!empty($meta['faq']) && is_array($meta['faq'])) {
        $faq_items = array();
        foreach ($meta['faq'] as $item) {
            if (empty($item['question']) || empty($item['answer'])) {
                continue;
            }

            $faq_items[] = array(
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ),
            );
        }

        if ($faq_items) {
            $graph[] = array(
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                '@id' => trailingslashit($url) . '#faq',
                'url' => $url,
                'mainEntity' => $faq_items,
            );
        }
    }

    return array(
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    );
}

function kingy_ali_launch_collection_schema($meta) {
    $url = isset($meta['url']) ? $meta['url'] : home_url('/ai-launches/');
    $url = kingy_ali_launch_collection_page_canonical_url($url);
    $item_list = kingy_ali_schema_empty_item_list();
    try {
        $item_list = kingy_ali_launch_collection_item_list($meta);
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('collection_item_list_failed', $throwable);
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => isset($meta['title']) ? $meta['title'] : __('AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
        'description' => isset($meta['description']) ? $meta['description'] : '',
        'url' => $url,
        'publisher' => kingy_ali_schema_publisher(),
        'breadcrumb' => kingy_ali_launch_collection_breadcrumb($meta),
        'mainEntity' => $item_list,
        'potentialAction' => array(
            '@type' => 'SearchAction',
            'target' => home_url('/ai-launches/') . '?kali_q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ),
    );
}

function kingy_ali_daily_radar_article_schema($post_id) {
    $post_id = absint($post_id);
    $description = get_the_excerpt($post_id);
    if ($description === '') {
        $description = wp_trim_words(wp_strip_all_tags((string) get_post_field('post_content', $post_id)), 32);
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title($post_id),
        'description' => $description,
        'url' => get_permalink($post_id),
        'mainEntityOfPage' => get_permalink($post_id),
        'datePublished' => get_post_time(DATE_W3C, true, $post_id),
        'dateModified' => get_post_modified_time(DATE_W3C, true, $post_id),
        'articleSection' => __('Daily AI Launch Radar', 'kingy-ai-launch-intelligence'),
        'isAccessibleForFree' => true,
        'publisher' => kingy_ali_schema_publisher(),
        'author' => kingy_ali_schema_publisher(),
        'breadcrumb' => kingy_ali_schema_breadcrumb_list(
            array(
                array('name' => get_bloginfo('name'), 'url' => home_url('/')),
                array('name' => __('AI Launch Command Center', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/')),
                array('name' => get_the_title($post_id), 'url' => get_permalink($post_id)),
            )
        ),
    );
}

function kingy_ali_launch_collection_item_list($meta, $limit = 12) {
    $query_args = isset($meta['query_args']) && is_array($meta['query_args']) ? $meta['query_args'] : array();
    $query_args['limit'] = !empty($query_args['limit']) ? max(1, absint($query_args['limit'])) : max(1, absint($limit));
    if (function_exists('kingy_ali_request_filters')) {
        $request_filters = kingy_ali_request_filters();
        $query_args['page'] = $request_filters['page'];
        $query_args['sort'] = $request_filters['sort'];
    }
    try {
        $query = kingy_ali_query_launches($query_args);
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('collection_item_list_query_failed', $throwable);
        return kingy_ali_schema_empty_item_list();
    }

    if (!is_object($query) || !isset($query->posts)) {
        return kingy_ali_schema_empty_item_list();
    }

    $items = array();
    $page = isset($query_args['page']) ? kingy_ali_sanitize_launch_page($query_args['page']) : 1;
    $position = (($page - 1) * $query_args['limit']) + 1;
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id) {
            continue;
        }
        $item = array(
            '@type' => 'ListItem',
            'position' => $position,
            'url' => get_permalink($post_id),
            'name' => get_the_title($post_id),
        );

        try {
            $item['item'] = kingy_ali_schema_launch_summary($post_id);
        } catch (Throwable $throwable) {
            kingy_ali_schema_fail_open_log('collection_item_summary_failed', $throwable);
        }

        $items[] = $item;
        $position++;
    }

    wp_reset_postdata();

    return array(
        '@type' => 'ItemList',
        'numberOfItems' => isset($query->found_posts) ? absint($query->found_posts) : count($items),
        'itemListElement' => $items,
    );
}

function kingy_ali_schema_launch_summary($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));

    return array(
        '@type' => 'CreativeWork',
        '@id' => kingy_ali_schema_entity_id($post_id, 'launch'),
        'name' => get_the_title($post_id),
        'description' => kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'what_launched', get_the_excerpt($post_id))),
        'url' => get_permalink($post_id),
        'sameAs' => $official_url,
        'dateCreated' => kingy_ali_schema_meta_text($post_id, 'launch_date'),
        'creator' => kingy_ali_schema_launch_organizer($post_id, kingy_ali_schema_meta_text($post_id, 'company')),
        'keywords' => kingy_ali_schema_keywords($post_id),
        'audience' => kingy_ali_schema_audience($post_id),
        'citation' => kingy_ali_schema_citations($post_id),
    );
}

function kingy_ali_current_directory_archive_type() {
    if (is_post_type_archive('kingy_ai_model')) {
        return 'models';
    }

    if (is_post_type_archive('kingy_ai_tool')) {
        return 'tools';
    }

    if (is_post_type_archive('kingy_ai_company')) {
        return 'companies';
    }

    return '';
}

function kingy_ali_directory_archive_meta($archive_type) {
    $meta = array(
        'models' => array(
            'title' => __('AI Model Intelligence Hub: Compare AI Models, Access, Pricing, and Sources', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse source-backed AI model profiles by provider, family, modality, access type, license, use case, context notes, pricing, benchmark caveats, and last verification date.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-models/'),
            'post_type' => 'kingy_ai_model',
        ),
        'tools' => array(
            'title' => __('AI Product Records: Pricing, Launches & Evidence | Kingy AI', 'kingy-ai-launch-intelligence'),
            'description' => __('Search AI product records with launch history, pricing status, demos, audiences and source notes. See what is verified, claimed or still unknown.', 'kingy-ai-launch-intelligence'),
            'social_title' => __('Compare AI products by launches, pricing and evidence', 'kingy-ai-launch-intelligence'),
            'social_description' => __('Search durable AI product records and inspect what changed, what it costs and which claims have supporting evidence.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-tools/'),
            'post_type' => 'kingy_ai_tool',
        ),
        'companies' => array(
            'title' => __('AI Company Directory: Founder, Funding, Tool, and Launch Profiles', 'kingy-ai-launch-intelligence'),
            'description' => __('Browse AI company and founder profiles connected to launch history, tool portfolios, funding notes, creator coverage potential, and audience focus.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai-companies/'),
            'post_type' => 'kingy_ai_company',
        ),
    );

    return isset($meta[$archive_type]) ? $meta[$archive_type] : array();
}

function kingy_ali_current_directory_archive_meta() {
    $archive_type = kingy_ali_current_directory_archive_type();
    return $archive_type ? kingy_ali_directory_archive_meta($archive_type) : array();
}

function kingy_ali_directory_archive_schema($archive_type) {
    $meta = kingy_ali_directory_archive_meta($archive_type);
    if (!$meta) {
        return array();
    }

    $item_list = kingy_ali_schema_empty_item_list();
    try {
        $item_list = kingy_ali_directory_archive_item_list($meta['post_type'], $archive_type);
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('directory_archive_item_list_failed', $throwable);
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $meta['title'],
        'description' => $meta['description'],
        'url' => $meta['url'],
        'publisher' => kingy_ali_schema_publisher(),
        'mainEntity' => $item_list,
        'potentialAction' => array(
            '@type' => 'SearchAction',
            'target' => $meta['url'] . '?kali_q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ),
    );

    return $schema;
}

function kingy_ali_directory_archive_item_list($post_type, $archive_type, $limit = 12) {
    $limit = max(1, absint($limit));
    $query_args = array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => kingy_ali_public_query_batch_size($limit),
        'fields' => 'ids',
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );
    kingy_ali_apply_public_noindex_meta_constraint($query_args);

    $query = kingy_ali_run_public_filtered_query(
        $query_args,
        $limit,
        function ($post) use ($archive_type) {
            $post_id = kingy_ali_public_query_post_id($post);
            if (!$post_id) {
                return false;
            }

            if ($archive_type === 'companies' && function_exists('kingy_ali_company_directory_card_is_public')) {
                return kingy_ali_company_directory_card_is_public($post_id);
            }

            return kingy_ali_public_query_accepts_index_ready_post($post_id);
        }
    );

    $items = array();
    $position = 1;
    foreach ((array) $query->posts as $post_id) {
        $post_id = absint($post_id);
        try {
            if ($archive_type !== 'companies' && kingy_ali_profile_should_noindex($post_id)) {
                continue;
            }

            $item = array(
                '@type' => 'ListItem',
                'position' => $position,
                'url' => get_permalink($post_id),
                'name' => get_the_title($post_id),
            );

            if ($archive_type === 'tools') {
                $item['item'] = kingy_ali_schema_software_application($post_id);
            } elseif ($archive_type === 'models') {
                $item['item'] = kingy_ali_model_schema($post_id);
            } elseif (!kingy_ali_profile_should_noindex($post_id)) {
                $item['item'] = kingy_ali_company_schema($post_id);
            }
        } catch (Throwable $throwable) {
            kingy_ali_schema_fail_open_log('directory_archive_item_failed', $throwable);
            $item = array(
                '@type' => 'ListItem',
                'position' => $position,
                'url' => get_permalink($post_id),
                'name' => get_the_title($post_id),
            );
        }

        $items[] = $item;
        $position++;
    }

    return array(
        '@type' => 'ItemList',
        'numberOfItems' => count($items),
        'itemListElement' => $items,
    );
}

function kingy_ali_schema_software_application($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));
    $demo_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'demo_url'));
    $company = kingy_ali_schema_meta_text($post_id, 'company');
    $category = kingy_ali_schema_primary_category($post_id);
    $description = get_post_type($post_id) === 'kingy_ai_tool'
        ? kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'what_it_does', get_the_excerpt($post_id)))
        : kingy_ali_schema_description(kingy_ali_schema_meta_text($post_id, 'what_launched', get_the_excerpt($post_id)));

    $schema = array(
        '@type' => 'SoftwareApplication',
        '@id' => kingy_ali_schema_entity_id($post_id, 'tool'),
        'name' => get_the_title($post_id),
        'description' => $description,
        'url' => get_permalink($post_id),
        'sameAs' => $official_url,
        'applicationCategory' => $category,
        'operatingSystem' => 'Web',
        'creator' => kingy_ali_schema_tool_creator($post_id, $company),
        'keywords' => kingy_ali_schema_keywords($post_id),
        'audience' => kingy_ali_schema_audience($post_id),
        'isAccessibleForFree' => kingy_ali_schema_yes_no_boolean(kingy_ali_schema_meta_text($post_id, 'free_plan')),
        'image' => kingy_ali_schema_featured_image($post_id),
        'citation' => kingy_ali_schema_citations($post_id),
        'potentialAction' => kingy_ali_schema_actions(
            array(
                array('type' => 'UseAction', 'name' => __('Open official tool', 'kingy-ai-launch-intelligence'), 'url' => $official_url),
                array('type' => 'WatchAction', 'name' => __('Watch demo', 'kingy-ai-launch-intelligence'), 'url' => $demo_url),
            )
        ),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_time('c', $post_id),
    );

    $schema['offers'] = kingy_ali_schema_offer($post_id);

    return $schema;
}

function kingy_ali_schema_entity_id($post_id, $fragment) {
    return get_permalink($post_id) . '#' . sanitize_key($fragment);
}

function kingy_ali_schema_launch_organizer($post_id, $company) {
    $company = kingy_ali_schema_text($company);
    $related_company_id = kingy_ali_schema_related_id(kingy_ali_get_meta($post_id, 'related_company_id'));
    if (kingy_ali_related_post_is_public_index_ready($related_company_id, 'kingy_ai_company')) {
        return kingy_ali_schema_company_reference($related_company_id);
    }

    return $company ? array(
        '@type' => 'Organization',
        'name' => $company,
    ) : '';
}

function kingy_ali_schema_tool_creator($post_id, $company) {
    $company = kingy_ali_schema_text($company);
    $related_company_id = kingy_ali_schema_related_id(kingy_ali_get_meta($post_id, 'related_company_id'));
    if (kingy_ali_related_post_is_public_index_ready($related_company_id, 'kingy_ai_company')) {
        return kingy_ali_schema_company_reference($related_company_id);
    }

    return $company ? array(
        '@type' => 'Organization',
        'name' => $company,
    ) : '';
}

function kingy_ali_schema_company_reference($post_id) {
    $official_url = kingy_ali_schema_url(kingy_ali_get_meta($post_id, 'official_url'));

    return array(
        '@type' => 'Organization',
        '@id' => kingy_ali_schema_entity_id($post_id, 'company'),
        'name' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'sameAs' => $official_url,
    );
}

function kingy_ali_schema_offer($post_id) {
    $pricing = kingy_ali_schema_meta_text($post_id, 'pricing');
    if (!$pricing) {
        return '';
    }

    return array(
        '@type' => 'Offer',
        'name' => __('Pricing summary', 'kingy-ai-launch-intelligence'),
        'priceSpecification' => array(
            '@type' => 'PriceSpecification',
            'description' => $pricing,
        ),
    );
}

function kingy_ali_schema_actions($actions) {
    $items = array();
    foreach ($actions as $action) {
        $url = isset($action['url']) ? kingy_ali_schema_url($action['url']) : '';
        if ($url === '') {
            continue;
        }

        $items[] = array(
            '@type' => $action['type'],
            'name' => $action['name'],
            'target' => $url,
        );
    }

    return count($items) === 1 ? $items[0] : $items;
}

function kingy_ali_schema_citations($post_id) {
    if (!function_exists('kingy_ali_public_source_links')) {
        return '';
    }

    $citations = array();
    foreach (kingy_ali_public_source_links($post_id) as $source) {
        $url = isset($source['url']) ? kingy_ali_schema_url($source['url']) : '';
        if ($url === '') {
            continue;
        }

        $label = isset($source['label']) ? kingy_ali_schema_text($source['label'], $url) : $url;
        $citations[] = array(
            '@type' => 'CreativeWork',
            'name' => $label,
            'url' => $url,
        );
    }

    return $citations;
}

function kingy_ali_schema_featured_image($post_id) {
    $image_id = get_post_thumbnail_id($post_id);
    if (!$image_id) {
        return '';
    }

    $image_url = wp_get_attachment_image_url($image_id, 'full');
    return $image_url ? kingy_ali_schema_url($image_url) : '';
}

function kingy_ali_schema_keywords($post_id) {
    $keywords = array();
    foreach (array('kingy_launch_category', 'kingy_audience', 'kingy_tool_attribute', 'kingy_launch_type') as $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        foreach ((array) $terms as $term) {
            if (
                $taxonomy === 'kingy_tool_attribute'
                && get_post_type($post_id) === 'kingy_ai_launch'
                && function_exists('kingy_ali_public_launch_attribute_is_visible')
                && (!isset($term->slug) || !kingy_ali_public_launch_attribute_is_visible($post_id, $term->slug))
            ) {
                continue;
            }
            if (isset($term->name)) {
                $keywords[] = $term->name;
            }
        }
    }

    $target_query = kingy_ali_schema_meta_text($post_id, 'target_search_query');
    if ($target_query) {
        $keywords[] = $target_query;
    }

    $keywords = array_map('kingy_ali_schema_text', $keywords);
    $keywords = array_values(array_unique(array_filter(array_map('trim', $keywords))));
    return $keywords ? implode(', ', $keywords) : '';
}

function kingy_ali_schema_audience($post_id) {
    $terms = get_the_terms($post_id, 'kingy_audience');
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    $audiences = array();
    foreach ($terms as $term) {
        $audiences[] = array(
            '@type' => 'Audience',
            'audienceType' => $term->name,
        );
    }

    return $audiences;
}

function kingy_ali_schema_yes_no_boolean($value) {
    if ($value === 'yes') {
        return true;
    }

    if ($value === 'no') {
        return false;
    }

    return '';
}

function kingy_ali_schema_kingy_review($post_id) {
    if (function_exists('kingy_ali_launch_score_snapshot')) {
        $score_snapshot = kingy_ali_launch_score_snapshot($post_id);
        $score = isset($score_snapshot['kingy']['value']) ? $score_snapshot['kingy']['value'] : null;
    } else {
        $raw_score = kingy_ali_schema_meta_text($post_id, 'kingy_launch_score');
        $suppressed = function_exists('kingy_ali_quality_truthy_meta')
            ? kingy_ali_quality_truthy_meta($post_id, 'scores_suppressed')
            : false;
        $score = !$suppressed && $raw_score !== '' && is_numeric($raw_score) ? (float) $raw_score : null;
    }
    $review_body = kingy_ali_schema_meta_text($post_id, 'kingy_verdict');
    $has_score = $score !== null && is_numeric($score);
    if (!$has_score && $review_body === '') {
        return '';
    }

    $review = array(
        '@type' => 'Review',
        'name' => __('Kingy AI launch take', 'kingy-ai-launch-intelligence'),
        'author' => kingy_ali_schema_publisher(),
        'datePublished' => get_the_date('c', $post_id),
        'reviewBody' => $review_body,
    );

    if ($has_score) {
        $review['reviewRating'] = array(
            '@type' => 'Rating',
            'name' => __('Kingy Launch Score', 'kingy-ai-launch-intelligence'),
            'ratingValue' => (float) $score,
            'bestRating' => 10,
            'worstRating' => 0,
        );
    }

    return $review;
}

function kingy_ali_schema_primary_category($post_id) {
    $category_terms = get_the_terms($post_id, 'kingy_launch_category');
    return (!is_wp_error($category_terms) && !empty($category_terms)) ? $category_terms[0]->name : '';
}

function kingy_ali_schema_description($description) {
    return wp_strip_all_tags(kingy_ali_schema_text($description));
}

function kingy_ali_schema_publisher() {
    return array(
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
    );
}

function kingy_ali_schema_filter($value, $parent_key = '') {
    if (!is_array($value)) {
        return $value;
    }

    $filtered = array();
    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $item = kingy_ali_schema_filter($item, $key);
        }

        if ($item === '' || $item === null) {
            continue;
        }

        if ($item === array() && $key !== 'itemListElement' && $parent_key !== 'itemListElement') {
            continue;
        }

        $filtered[$key] = $item;
    }

    return $filtered;
}

function kingy_ali_output_noindex() {
    if (kingy_ali_emergency_safe_mode_is_active()) {
        try {
            kingy_ali_output_noindex_inner();
        } catch (Throwable $throwable) {
            kingy_ali_emergency_safe_mode_log('noindex_output_failed', $throwable);
        }
        return;
    }

    kingy_ali_output_noindex_inner();
}

function kingy_ali_output_noindex_inner() {
    if (kingy_ali_model_page_noindex_fail_open()) {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
            $model_page_canonical = kingy_ali_model_page_canonical_url();
            if ($model_page_canonical) {
                echo '<link rel="canonical" href="' . esc_url($model_page_canonical) . '">' . "\n";
            }
        } else {
            echo "\n<meta name=\"robots\" content=\"noindex,follow\">\n";
        }
        return;
    }

    if (!is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))) {
        return;
    }

    if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
        return;
    }

    $post_id = get_the_ID();
    if (kingy_ali_profile_noindex_fail_open($post_id)) {
        echo "\n<meta name=\"robots\" content=\"noindex,follow\">\n";
    }
}

function kingy_ali_profile_noindex_fail_open($post_id) {
    static $checking = array();

    $post_id = absint($post_id);
    if (!$post_id || !function_exists('kingy_ali_profile_should_noindex')) {
        return false;
    }

    if (!empty($checking[$post_id])) {
        return false;
    }

    $checking[$post_id] = true;
    try {
        return (bool) kingy_ali_profile_should_noindex($post_id);
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('profile_noindex_check_failed', $throwable);
        return false;
    } finally {
        unset($checking[$post_id]);
    }
}

function kingy_ali_model_page_noindex_fail_open() {
    static $checking = false;

    if ($checking || !function_exists('kingy_ali_model_page_should_noindex')) {
        return false;
    }

    $checking = true;
    try {
        return (bool) kingy_ali_model_page_should_noindex();
    } catch (Throwable $throwable) {
        kingy_ali_schema_fail_open_log('model_page_noindex_check_failed', $throwable);
        return false;
    } finally {
        $checking = false;
    }
}

function kingy_ali_entity_quality_gate_should_noindex($post_id, $refresh = false) {
    static $cache = array();
    static $checking = array();

    $post_id = absint($post_id);
    if (!$post_id) {
        return false;
    }

    if ($refresh) {
        unset($cache[$post_id], $checking[$post_id]);
    }

    if (array_key_exists($post_id, $cache)) {
        return $cache[$post_id];
    }

    if (!empty($checking[$post_id])) {
        return true;
    }

    $post_type = get_post_type($post_id);
    if (!in_array($post_type, array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'), true)) {
        $cache[$post_id] = false;
        return false;
    }

    $checking[$post_id] = true;

    $explicit_noindex = kingy_ali_schema_meta_text($post_id, 'noindex');
    if ($explicit_noindex) {
        unset($checking[$post_id]);
        $cache[$post_id] = true;
        return true;
    }

    if (
        $post_type === 'kingy_ai_model'
        && (
            kingy_ali_schema_meta_text($post_id, 'verification_status') === 'rumored'
            || kingy_ali_model_has_term_slug($post_id, 'model_status', 'rumored')
        )
    ) {
        unset($checking[$post_id]);
        $cache[$post_id] = true;
        return true;
    }

    // Keep thin records out of public indexes until the trust fields documented in Settings/README are present.
    if ($post_type === 'kingy_ai_company') {
        $required = array(
            kingy_ali_indexable_url_meta($post_id, 'official_url'),
            kingy_ali_schema_meta_text($post_id, 'company_summary'),
            kingy_ali_schema_meta_text($post_id, 'ai_evidence'),
            (
                kingy_ali_schema_meta_text($post_id, 'source_notes') !== ''
                || kingy_ali_schema_meta_text($post_id, 'sources') !== ''
                || (function_exists('kingy_ali_company_directory_has_public_graph') && kingy_ali_company_directory_has_public_graph($post_id))
            ) ? 'yes' : '',
            kingy_ali_schema_meta_text($post_id, 'last_verified'),
        );
    } elseif ($post_type === 'kingy_ai_tool') {
        $required = array(
            kingy_ali_indexable_url_meta($post_id, 'official_url'),
            kingy_ali_schema_meta_text($post_id, 'what_it_does'),
            kingy_ali_pricing_is_indexable(kingy_ali_schema_meta_text($post_id, 'pricing')) ? 'yes' : '',
            kingy_ali_tool_has_public_launch_history($post_id) ? 'yes' : '',
            kingy_ali_schema_meta_text($post_id, 'last_verified'),
        );
    } elseif ($post_type === 'kingy_ai_model') {
        $required = array(
            kingy_ali_model_indexing_provider_value($post_id),
            kingy_ali_model_has_required_term($post_id, 'model_modality') ? 'yes' : '',
            kingy_ali_model_has_official_indexable_source($post_id) ? 'yes' : '',
            kingy_ali_schema_meta_text($post_id, 'model_overview'),
            kingy_ali_model_release_or_status_value($post_id),
            kingy_ali_model_access_or_pricing_value($post_id),
            kingy_ali_schema_meta_text($post_id, 'benchmark_caveat'),
            kingy_ali_schema_meta_text($post_id, 'verification_status'),
            kingy_ali_schema_meta_text($post_id, 'last_verified'),
            function_exists('kingy_ali_public_source_links') && count(kingy_ali_public_source_links($post_id)) > 0 ? 'yes' : '',
        );
    } else {
        $required = array(
            kingy_ali_indexable_url_meta($post_id, 'official_url'),
            kingy_ali_schema_meta_text($post_id, 'launch_date'),
            kingy_ali_schema_meta_text($post_id, 'what_launched'),
            kingy_ali_launch_indexing_audience_value($post_id),
            kingy_ali_pricing_is_indexable(kingy_ali_schema_meta_text($post_id, 'pricing')) ? 'yes' : '',
            kingy_ali_schema_meta_text($post_id, 'kingy_verdict'),
            kingy_ali_schema_meta_text($post_id, 'last_verified'),
            kingy_ali_launch_has_useful_related_link($post_id) ? 'yes' : '',
        );
    }
    $has_category = $post_type === 'kingy_ai_model'
        ? kingy_ali_model_has_required_term($post_id, 'model_provider')
        : kingy_ali_model_has_required_term($post_id, 'kingy_launch_category');
    $cache[$post_id] = in_array('', $required, true) || !$has_category;
    unset($checking[$post_id]);

    return $cache[$post_id];
}

function kingy_ali_tool_has_public_launch_history($post_id) {
    $latest_launch_id = kingy_ali_schema_related_id(kingy_ali_get_meta($post_id, 'latest_launch_id'));
    if (kingy_ali_related_post_is_public_index_ready($latest_launch_id, 'kingy_ai_launch')) {
        return true;
    }

    if (function_exists('kingy_ali_tool_launch_rollup')) {
        $rollup = kingy_ali_tool_launch_rollup($post_id);
        return !empty($rollup['count']);
    }

    return false;
}

function kingy_ali_model_has_required_term($post_id, $taxonomy) {
    $terms = get_the_terms($post_id, $taxonomy);
    return !is_wp_error($terms) && !empty($terms);
}

function kingy_ali_model_has_term_slug($post_id, $taxonomy, $slug) {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return false;
    }

    $slug = sanitize_title($slug);
    foreach ($terms as $term) {
        if (isset($term->slug) && $term->slug === $slug) {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_indexing_provider_value($post_id) {
    $provider = kingy_ali_schema_meta_text($post_id, 'provider_name');
    if ($provider !== '') {
        return $provider;
    }

    return kingy_ali_model_has_required_term($post_id, 'model_provider') ? 'yes' : '';
}

function kingy_ali_model_has_official_indexable_source($post_id) {
    foreach (array('official_url', 'official_announcement_url', 'official_docs_url', 'model_card_url') as $key) {
        if (kingy_ali_indexable_url_meta($post_id, $key) !== '') {
            return true;
        }
    }

    return false;
}

function kingy_ali_model_release_or_status_value($post_id) {
    $release_date = kingy_ali_schema_meta_text($post_id, 'release_date');
    if ($release_date !== '') {
        return $release_date;
    }

    return kingy_ali_schema_meta_text($post_id, 'model_status_note');
}

function kingy_ali_model_access_or_pricing_value($post_id) {
    foreach (array('pricing', 'api_pricing', 'license_notes', 'hardware_requirements') as $key) {
        $value = kingy_ali_schema_meta_text($post_id, $key);
        if ($value !== '') {
            return $value;
        }
    }

    foreach (array('api_available', 'web_app_available', 'local_available', 'open_weight', 'open_source') as $key) {
        $value = kingy_ali_schema_meta_text($post_id, $key);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function kingy_ali_company_has_public_graph_links($post_id) {
    if (function_exists('kingy_ali_company_public_related_count')) {
        return kingy_ali_company_public_related_count($post_id, 'kingy_ai_launch') > 0
            || kingy_ali_company_public_related_count($post_id, 'kingy_ai_tool') > 0;
    }

    foreach (array('kingy_ai_launch', 'kingy_ai_tool') as $post_type) {
        $query = new WP_Query(
            array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => kingy_ali_meta_key('related_company_id'),
                        'value' => absint($post_id),
                        'compare' => '=',
                    ),
                ),
            )
        );

        if (!empty($query->posts)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_pricing_is_indexable($pricing) {
    $pricing = trim(wp_strip_all_tags(kingy_ali_schema_text($pricing)));
    if ($pricing === '') {
        return false;
    }

    $normalized = strtolower($pricing);
    $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
    $normalized = trim(preg_replace('/\s+/', ' ', $normalized));
    if ($normalized === '') {
        return false;
    }

    $unknown_values = array(
        'unknown',
        'unknown pricing',
        'pricing unknown',
        'unclear',
        'unclear pricing',
        'pricing unclear',
        'not listed',
        'not disclosed',
        'not available',
        'not provided',
        'not specified',
        'undisclosed',
        'n a',
        'na',
        'tbd',
        'tba',
        'to be announced',
        'coming soon',
    );

    return !in_array($normalized, $unknown_values, true);
}

function kingy_ali_launch_indexing_audience_value($post_id) {
    $audience = kingy_ali_schema_meta_text($post_id, 'who_it_is_for');
    if ($audience !== '') {
        return $audience;
    }

    $audience_terms = get_the_terms($post_id, 'kingy_audience');
    return !is_wp_error($audience_terms) && !empty($audience_terms) ? 'yes' : '';
}

function kingy_ali_launch_has_useful_related_link($post_id) {
    foreach (kingy_ali_launch_related_link_meta_keys() as $key) {
        if (kingy_ali_indexable_url_meta($post_id, $key) !== '') {
            return true;
        }
    }

    if (function_exists('kingy_ali_public_source_links')) {
        $official_url = untrailingslashit(kingy_ali_indexable_url_meta($post_id, 'official_url'));
        foreach (kingy_ali_public_source_links($post_id) as $source) {
            if (empty($source['url'])) {
                continue;
            }

            $source_url = untrailingslashit(kingy_ali_schema_url($source['url']));
            if ($source_url !== '' && $source_url !== $official_url) {
                return true;
            }
        }
    }

    foreach (array('related_tool_id' => 'kingy_ai_tool', 'related_company_id' => 'kingy_ai_company') as $key => $post_type) {
        $related_id = kingy_ali_schema_related_id(kingy_ali_get_meta($post_id, $key));
        if (kingy_ali_related_post_is_public_index_ready($related_id, $post_type)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_launch_related_link_meta_keys() {
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
    );
}

function kingy_ali_output_filtered_results_robots() {
    if (!kingy_ali_is_filtered_or_search_results_page()) {
        return;
    }

    if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
        // Yoast suppresses its canonical element on these noindex query
        // surfaces. Keep one explicit canonical; rendered acceptance checks
        // guard against either a missing or duplicate element.
        echo '<link rel="canonical" href="' . esc_url(kingy_ali_filtered_results_canonical_url()) . '">' . "\n";
        return;
    }

    echo "\n<meta name=\"robots\" content=\"noindex,follow\">\n";
    echo '<link rel="canonical" href="' . esc_url(kingy_ali_filtered_results_canonical_url()) . '">' . "\n";
}

function kingy_ali_is_filtered_or_search_results_page() {
    if (is_search()) {
        return true;
    }

    if (kingy_ali_is_noindex_taxonomy_archive()) {
        return true;
    }

    $query_values = kingy_ali_filtered_query_values();
    if (!$query_values) {
        return false;
    }

    foreach ($query_values as $key => $value) {
        if ((string) $key === 'kali_page') {
            continue;
        }

        if ((string) $key === 'kali_sort' && kingy_ali_sanitize_launch_sort($value) === 'newest') {
            continue;
        }

        if (strpos((string) $key, 'kali_') === 0 && kingy_ali_filtered_query_value_present($value)) {
            return true;
        }

        if (kingy_ali_query_key_is_faceted_taxonomy($key) && kingy_ali_filtered_query_value_present($value)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_faceted_noindex_taxonomy_names() {
    if (function_exists('kingy_ali_faceted_noindex_taxonomies')) {
        return kingy_ali_faceted_noindex_taxonomies();
    }

    return array('kingy_audience', 'kingy_tool_attribute', 'kingy_launch_type');
}

function kingy_ali_noindex_taxonomy_names() {
    return array_values(
        array_unique(
            array_merge(
                kingy_ali_faceted_noindex_taxonomy_names(),
                array('kingy_launch_category')
            )
        )
    );
}

function kingy_ali_query_key_is_faceted_taxonomy($key) {
    return in_array((string) $key, kingy_ali_noindex_taxonomy_names(), true);
}

function kingy_ali_is_noindex_taxonomy_archive() {
    foreach (kingy_ali_noindex_taxonomy_names() as $taxonomy) {
        if (is_tax($taxonomy)) {
            return true;
        }
    }

    return false;
}

function kingy_ali_current_faceted_taxonomy_name() {
    foreach (kingy_ali_noindex_taxonomy_names() as $taxonomy) {
        if (is_tax($taxonomy)) {
            return $taxonomy;
        }
    }

    foreach (kingy_ali_filtered_query_values() as $key => $value) {
        if (kingy_ali_query_key_is_faceted_taxonomy($key) && kingy_ali_filtered_query_value_present($value)) {
            return (string) $key;
        }
    }

    return '';
}

function kingy_ali_faceted_taxonomy_canonical_url() {
    $taxonomy = kingy_ali_current_faceted_taxonomy_name();
    if (!$taxonomy) {
        return '';
    }

    if ($taxonomy === 'kingy_launch_category') {
        return kingy_ali_launch_category_canonical_url();
    }

    if ($taxonomy === 'kingy_tool_attribute') {
        return home_url('/ai-tools/');
    }

    return home_url('/ai-launches/');
}

function kingy_ali_launch_category_canonical_url() {
    $slug = '';
    if (is_tax('kingy_launch_category')) {
        $term = get_queried_object();
        $slug = $term && !is_wp_error($term) && !empty($term->slug) ? sanitize_title($term->slug) : '';
    }

    if (!$slug) {
        $values = kingy_ali_filtered_query_values();
        if (isset($values['kingy_launch_category']) && is_scalar($values['kingy_launch_category'])) {
            $slug = sanitize_title(wp_unslash($values['kingy_launch_category']));
        }
    }

    $curated_pages = array(
        'ai-agents' => '/ai-launches/ai-agents/',
        'ai-coding-tools' => '/ai-launches/ai-coding-tools/',
        'ai-video-tools' => '/ai-launches/ai-video-tools/',
        'ai-image-tools' => '/ai-launches/ai-image-tools/',
        'ai-open-weight-models' => '/ai-launches/open-weight-models/',
        'open-weight-models' => '/ai-launches/open-weight-models/',
        'ai-search-tools' => '/ai-launches/ai-search-research-tools/',
        'ai-research-tools' => '/ai-launches/ai-search-research-tools/',
    );

    if ($slug && isset($curated_pages[$slug])) {
        return home_url($curated_pages[$slug]);
    }

    return home_url('/ai-launches/');
}

function kingy_ali_filtered_results_canonical_url() {
    $faceted_canonical = kingy_ali_faceted_taxonomy_canonical_url();
    if ($faceted_canonical) {
        return $faceted_canonical;
    }

    return kingy_ali_current_url_without_query();
}

function kingy_ali_filtered_query_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_filtered_query_value_present($value) {
    if (is_array($value)) {
        foreach ($value as $item) {
            if (kingy_ali_filtered_query_value_present($item)) {
                return true;
            }
        }

        return false;
    }

    if (!is_scalar($value)) {
        return false;
    }

    $value = wp_unslash($value);
    return is_scalar($value) && trim((string) $value) !== '';
}

function kingy_ali_current_url_without_query() {
    global $wp;

    if (!empty($wp->request)) {
        return user_trailingslashit(home_url($wp->request));
    }

    if (is_front_page()) {
        return home_url('/');
    }

    return home_url('/');
}

/**
 * Collapse stale homepage pagination routes onto the canonical homepage.
 *
 * The current static homepage renders the same content at /page/N/ while
 * declaring the homepage as canonical. A permanent redirect removes those
 * duplicate, indexable routes without deleting any post or archive content.
 */
function kingy_ali_redirect_stale_homepage_pagination(): void {
	$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	if ( is_front_page() && $paged > 1 ) {
		wp_safe_redirect( home_url( '/' ), 301, 'Kingy AI' );
		exit;
	}
}
add_action( 'template_redirect', 'kingy_ali_redirect_stale_homepage_pagination', -50 );
