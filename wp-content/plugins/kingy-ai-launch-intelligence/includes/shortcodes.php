<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/replit-beginner-guide.php';

add_shortcode('kingy_launch_hub', 'kingy_ali_shortcode_hub');
add_shortcode('kingy_launch_search', 'kingy_ali_shortcode_search');
add_shortcode('kingy_launch_grid', 'kingy_ali_shortcode_grid');
add_shortcode('kingy_launch_submit_form', 'kingy_ali_shortcode_submit_form');
add_shortcode('kingy_launch_visibility_score', 'kingy_ali_shortcode_visibility_score');
add_shortcode('kingy_creator_campaign_roi_calculator', 'kingy_ali_shortcode_sponsorship_roi_calculator');
add_shortcode('kingy_sponsorship_roi_calculator', 'kingy_ali_shortcode_sponsorship_roi_calculator');
add_shortcode('kingy_sponsorship_roi_comparison_page', 'kingy_ali_shortcode_sponsorship_roi_comparison_page');
add_shortcode('kingy_trending_launches', 'kingy_ali_shortcode_trending_launches');
add_shortcode('kingy_youtube_worthy_launches', 'kingy_ali_shortcode_youtube_worthy_launches');
add_shortcode('kingy_creator_coverage_launches', 'kingy_ali_shortcode_creator_coverage_launches');
add_shortcode('kingy_codex_prompt_builder', 'kingy_ali_shortcode_codex_prompt_builder');
add_shortcode('kingy_app_builder_comparison', 'kingy_ali_shortcode_app_builder_comparison');
add_shortcode('kingy_codex_prompt_article_tools', 'kingy_ali_shortcode_codex_prompt_article_tools');
add_shortcode('kingy_ai_lead_magnet_guide', 'kingy_ali_shortcode_ai_lead_magnet_guide');
add_shortcode('kingy_ai_landing_page_guide', 'kingy_ali_shortcode_ai_landing_page_guide');
add_shortcode('kingy_safe_ai_agent_guide', 'kingy_ali_shortcode_safe_ai_agent_guide');
add_shortcode('kingy_vibe_coding_beginner_hub', 'kingy_ali_shortcode_vibe_coding_beginner_hub');
add_shortcode('kingy_replit_beginner_guide', 'kingy_ali_shortcode_replit_beginner_guide');
add_shortcode('kingy_ai_courses_hub', 'kingy_ali_shortcode_ai_courses_hub');

add_filter('the_content', 'kingy_ali_maybe_replace_app_builder_comparison_article', 20);
add_filter('the_content', 'kingy_ali_maybe_replace_ai_lead_magnet_article', 20);
add_filter('the_content', 'kingy_ali_maybe_replace_ai_landing_page_article', 20);
add_filter('the_content', 'kingy_ali_maybe_replace_safe_ai_agent_article', 20);
add_filter('the_content', 'kingy_ali_maybe_replace_vibe_coding_beginner_hub', 20);
add_filter('the_content', 'kingy_ali_maybe_replace_replit_beginner_guide', 20);
add_filter('the_content', 'kingy_ali_maybe_clean_daily_launch_radar_content', 12);
add_filter('the_content', 'kingy_ali_maybe_append_launches_of_week_ctas', 24);
add_filter('the_content', 'kingy_ali_maybe_append_launch_coverage_path', 28);
add_filter('the_content', 'kingy_ali_maybe_clean_sponsorship_roi_content_h1', 12);
add_action('init', 'kingy_ali_register_launch_coverage_rewrite_rules', 8);
add_filter('query_vars', 'kingy_ali_register_launch_coverage_query_vars');
add_action('template_redirect', 'kingy_ali_render_launch_coverage_sitemap', 1);
add_action('template_redirect', 'kingy_ali_redirect_legacy_launch_coverage_archive', 2);
add_filter('category_link', 'kingy_ali_filter_launch_coverage_category_link', 10, 2);
add_filter('get_pagenum_link', 'kingy_ali_filter_launch_coverage_pagenum_link', 10, 2);
add_filter('wpseo_sitemap_index', 'kingy_ali_append_launch_coverage_sitemap_index');
add_filter('wpseo_prev_rel_link', 'kingy_ali_filter_launch_coverage_adjacent_rel_link');
add_filter('wpseo_next_rel_link', 'kingy_ali_filter_launch_coverage_adjacent_rel_link');
add_action('pre_get_posts', 'kingy_ali_stabilize_launch_coverage_archive_query');
add_action('loop_start', 'kingy_ali_render_launch_coverage_archive_intro');
add_filter('wpseo_title', 'kingy_ali_ai_lead_magnet_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_ai_lead_magnet_seo_description');
add_filter('wpseo_title', 'kingy_ali_ai_landing_page_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_ai_landing_page_seo_description');
add_filter('wpseo_title', 'kingy_ali_safe_ai_agent_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_safe_ai_agent_seo_description');
add_filter('wpseo_title', 'kingy_ali_vibe_coding_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_vibe_coding_seo_description');
add_filter('wpseo_title', 'kingy_ali_replit_beginner_seo_title');
add_filter('wpseo_metadesc', 'kingy_ali_replit_beginner_seo_description');
add_filter('document_title_parts', 'kingy_ali_ai_lead_magnet_document_title');
add_filter('document_title_parts', 'kingy_ali_ai_landing_page_document_title');
add_filter('document_title_parts', 'kingy_ali_safe_ai_agent_document_title');
add_filter('document_title_parts', 'kingy_ali_vibe_coding_document_title');
add_filter('document_title_parts', 'kingy_ali_replit_beginner_document_title');
add_action('wp_head', 'kingy_ali_ai_lead_magnet_schema');
add_action('wp_head', 'kingy_ali_ai_landing_page_schema');
add_action('wp_head', 'kingy_ali_safe_ai_agent_schema');
add_action('wp_head', 'kingy_ali_maybe_output_vibe_coding_schema');
add_action('wp_head', 'kingy_ali_replit_beginner_schema');

function kingy_ali_content_has_shortcode($content, $tag) {
    return is_string($content) && function_exists('has_shortcode') && has_shortcode($content, $tag);
}

function kingy_ali_launch_coverage_archive_url() {
    return home_url('/ai-launches/coverage/');
}

function kingy_ali_related_editorial_url_meta_key() {
    return kingy_ali_meta_key('related_editorial_url');
}

function kingy_ali_related_editorial_urls_for_launch($launch_id) {
    $launch_id = absint($launch_id);
    if (!$launch_id || get_post_type($launch_id) !== 'kingy_ai_launch') {
        return array();
    }

    $values = get_post_meta($launch_id, kingy_ali_related_editorial_url_meta_key(), false);

    $urls = array();
    foreach ((array) $values as $value) {
        if (!is_scalar($value)) {
            continue;
        }
        $url = esc_url_raw(trim((string) $value), array('http', 'https'));
        $article_id = $url ? url_to_postid($url) : 0;
        if (
            !$article_id
            || get_post_type($article_id) !== 'post'
            || get_post_status($article_id) !== 'publish'
            || !has_category('ai-launch-tracker', $article_id)
        ) {
            continue;
        }
        $canonical = get_permalink($article_id);
        if ($canonical) {
            $urls[$canonical] = $article_id;
        }
    }

    return $urls;
}

function kingy_ali_register_launch_coverage_rewrite_rules() {
    add_rewrite_rule(
        '^ai-launches/coverage/sitemap\.xml$',
        'index.php?kingy_ali_launch_coverage_sitemap=1',
        'top'
    );
    add_rewrite_rule(
        '^ai-launches/coverage/page/([0-9]+)/?$',
        'index.php?category_name=ai-launch-tracker&paged=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^ai-launches/coverage/feed/(feed|rdf|rss|rss2|atom)/?$',
        'index.php?category_name=ai-launch-tracker&feed=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^ai-launches/coverage/feed/?$',
        'index.php?category_name=ai-launch-tracker&feed=rss2',
        'top'
    );
    add_rewrite_rule(
        '^ai-launches/coverage/?$',
        'index.php?category_name=ai-launch-tracker',
        'top'
    );
}

function kingy_ali_register_launch_coverage_query_vars($vars) {
    $vars[] = 'kingy_ali_launch_coverage_sitemap';
    return array_values(array_unique($vars));
}

function kingy_ali_launch_coverage_latest_modified_gmt() {
    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'category_name' => 'ai-launch-tracker',
        'posts_per_page' => 1,
        'orderby' => array('modified' => 'DESC', 'ID' => 'DESC'),
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ));
    if (!$posts) {
        return '';
    }

    return (string) get_post_field('post_modified_gmt', $posts[0]);
}

function kingy_ali_launch_coverage_max_pages() {
    $category = get_category_by_slug('ai-launch-tracker');
    return $category && !is_wp_error($category)
        ? max(1, (int) ceil((int) $category->count / 10))
        : 0;
}

function kingy_ali_launch_coverage_sitemap_url() {
    return home_url('/ai-launches/coverage/sitemap.xml');
}

function kingy_ali_append_launch_coverage_sitemap_index($index) {
    $sitemap_url = kingy_ali_launch_coverage_sitemap_url();
    if (!$sitemap_url || strpos((string) $index, $sitemap_url) !== false) {
        return $index;
    }

    $lastmod_gmt = kingy_ali_launch_coverage_latest_modified_gmt();
    $lastmod = $lastmod_gmt ? mysql2date(DATE_W3C, $lastmod_gmt, false) : '';
    $entry = "\n\t<sitemap>\n\t\t<loc>" . esc_url($sitemap_url) . "</loc>";
    if ($lastmod) {
        $entry .= "\n\t\t<lastmod>" . esc_html($lastmod) . "</lastmod>";
    }
    $entry .= "\n\t</sitemap>\n";

    return (string) $index . $entry;
}

function kingy_ali_render_launch_coverage_sitemap() {
    if (!get_query_var('kingy_ali_launch_coverage_sitemap')) {
        return;
    }

    $max_pages = kingy_ali_launch_coverage_max_pages();
    if ($max_pages < 1) {
        status_header(404);
        exit;
    }

    $lastmod_gmt = kingy_ali_launch_coverage_latest_modified_gmt();
    $lastmod = $lastmod_gmt ? mysql2date(DATE_W3C, $lastmod_gmt, false) : '';
    status_header(200);
    header('Content-Type: application/xml; charset=' . get_option('blog_charset'));
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $url = kingy_ali_launch_coverage_archive_url();
    echo "\t<url>\n\t\t<loc>" . htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>";
    if ($lastmod) {
        echo "\n\t\t<lastmod>" . htmlspecialchars($lastmod, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</lastmod>";
    }
    echo "\n\t</url>\n";
    echo '</urlset>' . "\n";
    exit;
}

function kingy_ali_filter_launch_coverage_category_link($url, $term_id) {
    $term = get_term(absint($term_id), 'category');
    if ($term && !is_wp_error($term) && $term->slug === 'ai-launch-tracker') {
        return kingy_ali_launch_coverage_archive_url();
    }

    return $url;
}

function kingy_ali_filter_launch_coverage_pagenum_link($url, $page) {
    if (!is_category('ai-launch-tracker')) {
        return $url;
    }

    $page = max(1, absint($page));
    return $page > 1
        ? trailingslashit(kingy_ali_launch_coverage_archive_url()) . 'page/' . $page . '/'
        : kingy_ali_launch_coverage_archive_url();
}

function kingy_ali_filter_launch_coverage_adjacent_rel_link($output) {
    if (!is_category('ai-launch-tracker') || !is_string($output) || $output === '') {
        return $output;
    }

    return str_replace(
        home_url('/category/ai-launch-tracker/'),
        kingy_ali_launch_coverage_archive_url(),
        $output
    );
}

function kingy_ali_launch_coverage_request_path() {
    if (empty($_SERVER['REQUEST_URI'])) {
        return '';
    }

    $path = wp_parse_url(wp_unslash((string) $_SERVER['REQUEST_URI']), PHP_URL_PATH);
    return is_string($path) ? '/' . ltrim($path, '/') : '';
}

function kingy_ali_legacy_launch_coverage_request_page($path) {
    if (preg_match('#^/category/ai-launch-tracker/page/([0-9]+)/?$#', (string) $path, $matches)) {
        return max(1, absint($matches[1]));
    }

    return 1;
}

function kingy_ali_legacy_launch_coverage_request_feed_type($path) {
    if (preg_match('#/feed/(feed|rdf|rss|rss2|atom)/?$#', (string) $path, $matches)) {
        return sanitize_key($matches[1]);
    }

    return '';
}

function kingy_ali_redirect_legacy_launch_coverage_archive() {
    if (is_admin()) {
        return;
    }

    $path = kingy_ali_launch_coverage_request_path();
    if (!preg_match('#^/category/ai-launch-tracker(?:/page/[0-9]+|/feed(?:/(?:feed|rdf|rss|rss2|atom))?)?/?$#', $path)) {
        return;
    }

    $target = kingy_ali_launch_coverage_archive_url();
    if (is_feed() || preg_match('#/feed(?:/(?:feed|rdf|rss|rss2|atom))?/?$#', $path)) {
        $target = trailingslashit($target) . 'feed/';
        $feed_type = kingy_ali_legacy_launch_coverage_request_feed_type($path);
        if ($feed_type) {
            $target .= $feed_type . '/';
        }
    } else {
        $page = kingy_ali_legacy_launch_coverage_request_page($path);
        if ($page > 1) {
            $target = trailingslashit($target) . 'page/' . $page . '/';
        }
    }

    wp_safe_redirect($target, 301, 'Kingy AI Launch Coverage');
    exit;
}

function kingy_ali_stabilize_launch_coverage_archive_query($query) {
    if (
        is_admin()
        || !$query instanceof WP_Query
        || !$query->is_main_query()
        || !$query->is_category('ai-launch-tracker')
    ) {
        return;
    }

    $query->set('posts_per_page', 10);
    $query->set('ignore_sticky_posts', true);
    // Date alone is not deterministic when several launch posts share the
    // same publication timestamp. ID provides a stable boundary between
    // paginated result sets so records cannot repeat or disappear.
    $query->set('orderby', array('date' => 'DESC', 'ID' => 'DESC'));
}

function kingy_ali_maybe_append_launch_coverage_path($content) {
    if (
        is_admin()
        || !is_singular('post')
        || !in_the_loop()
        || !is_main_query()
        || !has_category('ai-launch-tracker', get_queried_object_id())
        || strpos((string) $content, 'data-kingy-launch-coverage-path=') !== false
    ) {
        return $content;
    }

    $archive_url = kingy_ali_launch_coverage_archive_url();
    $hub_url = home_url('/ai-launches/');
    $content .= kingy_ali_render_related_launch_records_for_article(get_queried_object_id(), $content);
    $path = sprintf(
        '<aside class="kingy-launch-coverage-path" data-kingy-launch-coverage-path="1" aria-label="%1$s"><p><strong>%2$s</strong> %3$s <a href="%4$s">%5$s</a> %6$s <a href="%7$s">%8$s</a>.</p></aside>',
        esc_attr__('AI launch coverage navigation', 'kingy-ai-launch-intelligence'),
        esc_html__('Keep exploring:', 'kingy-ai-launch-intelligence'),
        esc_html__('browse all', 'kingy-ai-launch-intelligence'),
        esc_url($archive_url),
        esc_html__('AI Launch Tracker editorial coverage', 'kingy-ai-launch-intelligence'),
        esc_html__('or search the complete', 'kingy-ai-launch-intelligence'),
        esc_url($hub_url),
        esc_html__('AI launch database', 'kingy-ai-launch-intelligence')
    );

    return $content . $path;
}

function kingy_ali_render_related_launch_records_for_article($post_id, $content = '') {
    $post_id = absint($post_id);
    $article_url = $post_id ? get_permalink($post_id) : '';
    if (!$article_url) {
        return '';
    }

    $query_args = array(
        'post_type' => 'kingy_ai_launch',
        'post_status' => 'publish',
        'posts_per_page' => 24,
        'orderby' => array('date' => 'DESC', 'ID' => 'DESC'),
        'no_found_rows' => true,
        'meta_value' => esc_url_raw($article_url),
    );
    $legacy_records = get_posts(
        array_merge($query_args, array('meta_key' => kingy_ali_meta_key('related_article_url')))
    );
    $editorial_records = get_posts(
        array_merge($query_args, array('meta_key' => kingy_ali_related_editorial_url_meta_key()))
    );
    $record_ids = array();
    foreach (array_merge($legacy_records, $editorial_records) as $record) {
        $record_ids[$record->ID] = $record->ID;
    }
    if (!$record_ids) {
        return '';
    }

    $records = get_posts(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'publish',
            'post__in' => array_values($record_ids),
            'posts_per_page' => count($record_ids),
            'orderby' => array('date' => 'DESC', 'ID' => 'DESC'),
            'no_found_rows' => true,
        )
    );

    $links = array();
    foreach ($records as $record) {
        $record_url = get_permalink($record);
        $record_path = $record_url ? wp_parse_url($record_url, PHP_URL_PATH) : '';
        if (!$record_url || ($record_path && strpos((string) $content, $record_path) !== false)) {
            continue;
        }
        $links[] = '<li><a href="' . esc_url($record_url) . '">' . esc_html(get_the_title($record)) . '</a></li>';
    }
    if (!$links) {
        return '';
    }

    return '<aside class="kingy-launch-record-links" data-kingy-launch-record-links="1" aria-label="' . esc_attr__('Related AI launch records', 'kingy-ai-launch-intelligence') . '"><p><strong>' . esc_html__('Related source-backed launch records', 'kingy-ai-launch-intelligence') . '</strong></p><ul>' . implode('', $links) . '</ul></aside>';
}

function kingy_ali_render_launch_coverage_archive_intro($query) {
    static $rendered = false;
    if (
        $rendered
        || is_admin()
        || !$query instanceof WP_Query
        || !$query->is_main_query()
        || !is_category('ai-launch-tracker')
    ) {
        return;
    }

    $rendered = true;
    echo '<section class="kingy-launch-coverage-intro" data-kingy-launch-coverage-intro="1">';
    echo '<p>' . esc_html__('Analysis, explainers, and daily launch reporting from Kingy AI. For the complete source-backed directory,', 'kingy-ai-launch-intelligence') . ' ';
    echo '<a href="' . esc_url(home_url('/ai-launches/')) . '">' . esc_html__('browse all AI launch records', 'kingy-ai-launch-intelligence') . '</a>.</p>';
    echo '</section>';
}

function kingy_ali_maybe_output_vibe_coding_schema() {
    if (function_exists('kingy_ali_vibe_coding_schema')) {
        kingy_ali_vibe_coding_schema();
    }
}

function kingy_ali_contact_url() {
    $contact_url = get_option('kingy_ali_contact_url', '');
    if (!$contact_url) {
        $contact_url = home_url('/contact/');
    }

    $contact_url = kingy_ali_sanitize_public_cta_url($contact_url);
    return kingy_ali_sanitize_public_cta_url(apply_filters('kingy_ali_contact_url', $contact_url));
}

function kingy_ali_client_examples_url() {
    $client_examples_url = get_option('kingy_ali_client_examples_url', '');
    if (!$client_examples_url) {
        return '';
    }

    $client_examples_url = kingy_ali_sanitize_public_cta_url($client_examples_url);
    return kingy_ali_sanitize_public_cta_url(apply_filters('kingy_ali_client_examples_url', $client_examples_url));
}

function kingy_ali_sanitize_public_cta_url($value) {
    if (!is_scalar($value)) {
        return '';
    }

    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $url = esc_url_raw($value, array('http', 'https'));
    return kingy_ali_public_cta_url_is_allowed($url) ? $url : '';
}

function kingy_ali_public_cta_url_is_allowed($url) {
    if (!is_scalar($url)) {
        return false;
    }

    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }

    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        return true;
    }

    $parts = wp_parse_url($url);
    if (!is_array($parts)) {
        return false;
    }

    $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
    $host = isset($parts['host']) ? trim((string) $parts['host']) : '';
    return in_array($scheme, array('http', 'https'), true) && $host !== '';
}

function kingy_ali_shortcode_request_value($key, $max_length = 191) {
    $values = kingy_ali_shortcode_request_values();
    if (!isset($values[$key])) {
        return '';
    }

    if (!is_scalar($values[$key])) {
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

function kingy_ali_shortcode_request_values() {
    return is_array($_GET) ? $_GET : array();
}

function kingy_ali_current_page_path_is($path) {
    if (is_admin() || !is_singular('page')) {
        return false;
    }

    $post_id = (int) get_queried_object_id();
    if (!$post_id) {
        return false;
    }

    return trim((string) get_page_uri($post_id), '/') === trim((string) $path, '/');
}

function kingy_ali_radar_post_title_matches($post_id) {
    $title = trim((string) get_the_title($post_id));
    return $title !== '' && stripos($title, 'Daily AI Launch Radar') !== false;
}

function kingy_ali_is_rendering_daily_launch_radar_post() {
    if (is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query()) {
        return false;
    }

    $post_id = get_queried_object_id();
    return $post_id && kingy_ali_radar_post_title_matches($post_id);
}

function kingy_ali_normalize_heading_text($value) {
    $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, get_bloginfo('charset'));
    $value = preg_replace('/\s+/', ' ', $value);
    return function_exists('mb_strtolower') ? mb_strtolower(trim((string) $value)) : strtolower(trim((string) $value));
}

function kingy_ali_remove_duplicate_radar_h1($content, $post_id) {
    if (!is_string($content) || trim($content) === '') {
        return $content;
    }

    $title = kingy_ali_normalize_heading_text(get_the_title($post_id));
    if ($title === '') {
        return $content;
    }

    return preg_replace_callback(
        '/^\s*(?:<!--\s*wp:heading[^>]*?-->\s*)?<h1\b[^>]*>(.*?)<\/h1>(?:\s*<!--\s*\/wp:heading\s*-->)?/is',
        static function ($matches) use ($title) {
            $heading = kingy_ali_normalize_heading_text($matches[1]);
            return $heading === $title ? '' : $matches[0];
        },
        $content,
        1
    );
}

function kingy_ali_render_launch_radar_newsletter_cta() {
    return kingy_ali_render_launch_intelligence_newsletter_module('daily_launch_radar_post')
        . kingy_ali_render_sponsor_path('daily_launch_radar_post');
}

function kingy_ali_maybe_clean_daily_launch_radar_content($content) {
    if (!kingy_ali_is_rendering_daily_launch_radar_post()) {
        return $content;
    }

    $post_id = get_queried_object_id();
    $content = kingy_ali_remove_duplicate_radar_h1($content, $post_id);
    $content = kingy_ali_clean_radar_raw_array_sections($content);

    if (stripos($content, 'kingy-ali-launch-radar-cta') === false && stripos($content, 'Join the Kingy AI Launch Radar') === false) {
        $content .= "\n" . kingy_ali_render_launch_radar_newsletter_cta();
    }

    return $content;
}

function kingy_ali_is_rendering_launches_of_week_post() {
    if (is_admin() || !is_singular('post')) {
        return false;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return false;
    }

    if (function_exists('kingy_ali_meta_key') && get_post_meta($post_id, kingy_ali_meta_key('launches_of_week_edition'), true) === '1') {
        return true;
    }

    $title = (string) get_the_title($post_id);
    return $title !== '' && stripos($title, 'Launches of the Week') !== false;
}

function kingy_ali_maybe_append_launches_of_week_ctas($content) {
    if (!kingy_ali_is_rendering_launches_of_week_post()) {
        return $content;
    }

    if (stripos($content, 'kingy-ali-sponsor-path') !== false || stripos($content, 'Sponsor Kingy AI') !== false) {
        return $content;
    }

    return $content . "\n" . kingy_ali_render_sponsor_path('launches_of_week_post') . "\n" . kingy_ali_render_launch_intelligence_newsletter_module('launches_of_week_post');
}

function kingy_ali_clean_radar_raw_array_sections($content) {
    if (!is_string($content) || $content === '') {
        return $content;
    }

    $content = preg_replace_callback(
        '/(?:<p[^>]*>)?\s*Array\s*\(\s*((?:\[\d+\]\s*=>\s*.*?)+)\s*\)\s*(?:<\/p>)?/is',
        static function ($matches) {
            $items = kingy_ali_parse_raw_array_items($matches[1]);
            return $items ? kingy_ali_render_clean_radar_list($items) : $matches[0];
        },
        $content
    );

    $content = preg_replace_callback(
        '/<p[^>]*>\s*(\[[^<]{12,2500}\])\s*<\/p>/is',
        static function ($matches) {
            $json = html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES, get_bloginfo('charset'));
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return $matches[0];
            }

            $items = array();
            foreach ($decoded as $item) {
                if (is_scalar($item)) {
                    $items[] = (string) $item;
                }
            }

            return $items ? kingy_ali_render_clean_radar_list($items) : $matches[0];
        },
        $content
    );

    return $content;
}

function kingy_ali_parse_raw_array_items($raw) {
    $items = array();
    if (!is_scalar($raw)) {
        return $items;
    }

    if (!preg_match_all('/\[\d+\]\s*=>\s*(.*?)(?=\s*\[\d+\]\s*=>|\s*$)/is', (string) $raw, $matches)) {
        return $items;
    }

    foreach ($matches[1] as $item) {
        $item = trim(html_entity_decode(wp_strip_all_tags((string) $item), ENT_QUOTES, get_bloginfo('charset')));
        $item = trim($item, " \t\n\r\0\x0B,;\"'");
        if ($item !== '') {
            $items[] = $item;
        }
    }

    return array_values(array_unique($items));
}

function kingy_ali_render_clean_radar_list($items) {
    $items = array_filter(array_map('trim', (array) $items));
    if (!$items) {
        return '';
    }

    $html = '<ul class="kingy-ali-clean-list">';
    foreach ($items as $item) {
        $html .= '<li>' . esc_html($item) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function kingy_ali_maybe_clean_sponsorship_roi_content_h1($content) {
    if (
        is_admin()
        || !is_page()
        || !in_the_loop()
        || !is_main_query()
        || (
            !kingy_ali_current_page_path_is('ai-sponsored-video-roi-calculator')
            && !kingy_ali_current_page_path_is('ai-launches/creator-campaign-roi-calculator')
        )
    ) {
        return $content;
    }

    return preg_replace_callback(
        '/<h1\b([^>]*)>(.*?)<\/h1>/is',
        static function ($matches) {
            $attributes = isset($matches[1]) ? (string) $matches[1] : '';
            $heading_text = kingy_ali_normalize_heading_text(isset($matches[2]) ? $matches[2] : '');
            $is_managed_title = strpos($attributes, 'kingy-ali-page-title--injected') !== false
                || $heading_text === kingy_ali_normalize_heading_text('AI Sponsored Video ROI Calculator');

            if (!$is_managed_title) {
                return $matches[0];
            }

            return '<h2' . $attributes . '>' . $matches[2] . '</h2>';
        },
        $content,
        1
    );
}

function kingy_ali_post_is_public_index_ready($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_status($post_id) !== 'publish') {
        return false;
    }

    if (trim((string) get_the_title($post_id)) === '') {
        return false;
    }

    $permalink = get_permalink($post_id);
    if (!$permalink || !wp_http_validate_url($permalink)) {
        return false;
    }

    $post_type = get_post_type($post_id);
    if (
        in_array($post_type, array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'), true)
        && function_exists('kingy_ali_profile_should_noindex')
        && kingy_ali_profile_should_noindex($post_id)
    ) {
        return false;
    }

    $yoast_noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
    if ($yoast_noindex === '1' || $yoast_noindex === 1) {
        return false;
    }

    $rank_math_robots = get_post_meta($post_id, 'rank_math_robots', true);
    if (is_array($rank_math_robots) && in_array('noindex', $rank_math_robots, true)) {
        return false;
    }
    if (is_string($rank_math_robots) && stripos($rank_math_robots, 'noindex') !== false) {
        return false;
    }

    return true;
}

function kingy_ali_post_summary_text($post_id, $meta_key = '') {
    $summary = '';
    if ($meta_key) {
        $summary = kingy_ali_public_profile_meta_text($post_id, $meta_key);
    }

    if ($summary === '') {
        $summary = get_the_excerpt($post_id);
    }

    if ($summary === '') {
        $summary = wp_strip_all_tags((string) get_post_field('post_content', $post_id));
    }

    return trim((string) wp_trim_words($summary, 30));
}

function kingy_ali_post_date_label($post_id) {
    $date = get_post_time('Y-m-d', false, $post_id);
    if (!$date) {
        return '';
    }

    return date_i18n(get_option('date_format'), strtotime($date));
}

function kingy_ali_daily_launch_radar_posts($limit = 5) {
    $limit = max(1, absint($limit));
    $cache_generation = function_exists('kingy_ali_launch_collection_cache_generation') ? kingy_ali_launch_collection_cache_generation() : '1';
    $cache_key = 'kingy_ali_daily_radar_posts_v3_' . $limit . '_' . $cache_generation;
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return array_map('absint', $cached);
    }

    $query_args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => max(30, $limit * 8),
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );

    $query = new WP_Query($query_args);
    $ids = array();
    foreach ((array) $query->posts as $post) {
        $post_id = isset($post->ID) ? absint($post->ID) : 0;
        if (!$post_id || !kingy_ali_radar_post_title_matches($post_id) || !kingy_ali_post_is_public_index_ready($post_id)) {
            continue;
        }

        $ids[] = $post_id;
        if (count($ids) >= $limit) {
            break;
        }
    }
    wp_reset_postdata();

    set_transient($cache_key, $ids, 10 * MINUTE_IN_SECONDS);
    return $ids;
}

function kingy_ali_today_daily_launch_radar_post_id() {
    $today = current_time('Y-m-d');
    foreach (kingy_ali_daily_launch_radar_posts(6) as $post_id) {
        if (get_post_time('Y-m-d', false, $post_id) === $today) {
            return absint($post_id);
        }
    }

    return 0;
}

function kingy_ali_latest_daily_launch_radar_post_id() {
    $posts = kingy_ali_daily_launch_radar_posts(1);
    return !empty($posts[0]) ? absint($posts[0]) : 0;
}

function kingy_ali_homepage_radar_cta() {
    $today_radar = kingy_ali_today_daily_launch_radar_post_id();
    if ($today_radar) {
        return array(
            'label' => __('Read Today\'s AI Launch Radar', 'kingy-ai-launch-intelligence'),
            'url' => get_permalink($today_radar),
            'date_label' => kingy_ali_post_date_label($today_radar),
            'state' => 'today',
        );
    }

    $latest_radar = kingy_ali_latest_daily_launch_radar_post_id();
    if ($latest_radar) {
        return array(
            'label' => __('Read the Latest AI Launch Radar', 'kingy-ai-launch-intelligence'),
            'url' => get_permalink($latest_radar),
            'date_label' => kingy_ali_post_date_label($latest_radar),
            'state' => 'latest',
        );
    }

    return array(
        'label' => __('Browse AI Launch Intelligence', 'kingy-ai-launch-intelligence'),
        'url' => home_url('/ai-launches/'),
        'date_label' => '',
        'state' => 'hub',
    );
}

function kingy_ali_render_homepage_radar_cta_link($class = 'kingy-home-button secondary') {
    $cta = kingy_ali_homepage_radar_cta();
    return '<a class="' . esc_attr($class) . '" href="' . esc_url($cta['url']) . '">' . esc_html($cta['label']) . '</a>';
}

function kingy_ali_homepage_add_launch_item(&$items, $item, &$seen_urls, $limit) {
    if (empty($item['url']) || empty($item['title']) || empty($item['summary'])) {
        return;
    }

    $url_key = strtolower(untrailingslashit((string) $item['url']));
    if (isset($seen_urls[$url_key])) {
        return;
    }

    $items[] = $item;
    $seen_urls[$url_key] = true;
    if (count($items) > $limit) {
        $items = array_slice($items, 0, $limit);
    }
}


function kingy_ali_latest_launches_of_week_edition_cta() {
    $fallback = array(
        'post_id' => 0,
        'date' => '',
        'title' => __('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'),
        'label' => __('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'),
        'short_label' => __('Read latest edition', 'kingy-ai-launch-intelligence'),
        'description' => __('Editorial weekly awards for standout AI launches.', 'kingy-ai-launch-intelligence'),
        'why' => __('Useful for recognition and market context.', 'kingy-ai-launch-intelligence'),
        'summary' => __('Browse the latest editorial shortlist of standout AI launches.', 'kingy-ai-launch-intelligence'),
        'url' => home_url('/ai-launches/launches-of-the-week/'),
    );

    if (!function_exists('kingy_ali_meta_key')) {
        return $fallback;
    }

    $cache_generation = function_exists('kingy_ali_launch_collection_cache_generation') ? kingy_ali_launch_collection_cache_generation() : '1';
    $cache_key = 'kingy_ali_latest_launches_of_week_edition_cta_v2_' . $cache_generation;
    $cached = get_transient($cache_key);
    if (is_array($cached) && !empty($cached['url']) && !empty($cached['label'])) {
        return array_merge($fallback, $cached);
    }

    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => array(
            array(
                'key' => kingy_ali_meta_key('launches_of_week_edition'),
                'value' => '1',
            ),
        ),
    ));

    if (!$query->have_posts()) {
        wp_reset_postdata();
        set_transient($cache_key, $fallback, 10 * MINUTE_IN_SECONDS);
        return $fallback;
    }

    $post_id = absint($query->posts[0]->ID);
    $title = get_the_title($post_id);
    $date_label = function_exists('kingy_ali_launches_of_week_edition_date_label') ? kingy_ali_launches_of_week_edition_date_label($post_id) : kingy_ali_post_date_label($post_id);
    $date_label = wp_strip_all_tags($date_label);
    $compact_date = trim((string) preg_replace('/^Kingy AI Launches of the Week:\s*/i', '', $title));
    if ($compact_date === '') {
        $compact_date = $date_label;
    }

    $cta = array(
        'post_id' => $post_id,
        'date' => $date_label,
        'title' => $title,
        'label' => $compact_date ? sprintf(__('%s Launches of the Week', 'kingy-ai-launch-intelligence'), $compact_date) : $title,
        'short_label' => $compact_date ? sprintf(__('Read %s edition', 'kingy-ai-launch-intelligence'), $compact_date) : __('Read latest edition', 'kingy-ai-launch-intelligence'),
        'description' => __('Latest Kingy AI weekly launch awards edition.', 'kingy-ai-launch-intelligence'),
        'why' => __('Useful for the latest editorial picks.', 'kingy-ai-launch-intelligence'),
        'summary' => kingy_ali_post_summary_text($post_id),
        'url' => get_permalink($post_id),
    );

    wp_reset_postdata();
    set_transient($cache_key, $cta, 10 * MINUTE_IN_SECONDS);
    return $cta;
}

function kingy_ali_latest_launches_of_week_edition_link($label_key = 'short_label') {
    $cta = kingy_ali_latest_launches_of_week_edition_cta();
    $label = !empty($cta[$label_key]) ? $cta[$label_key] : $cta['label'];

    return array(
        'label' => $label,
        'url' => $cta['url'],
    );
}

add_filter('the_content', 'kingy_ali_inject_homepage_launches_of_week_card', 22);
function kingy_ali_inject_homepage_launches_of_week_card($content) {
    if (is_admin() || !is_string($content) || $content === '') {
        return $content;
    }

    $is_homepage = is_front_page() || is_page(361);
    if (!$is_homepage || strpos($content, 'id="kingy-latest"') === false || strpos($content, 'kingy-ai-launches-of-the-week-june-') !== false) {
        return $content;
    }

    $latest_launches_of_week = kingy_ali_latest_launches_of_week_edition_cta();
    if (empty($latest_launches_of_week['post_id']) || empty($latest_launches_of_week['url'])) {
        return $content;
    }

    $needle = '<div class="kingy-grid three" aria-label="Latest AI launch cards">';
    if (strpos($content, $needle) === false) {
        $needle = '<div class="kingy-grid three" aria-label="Latest AI launch intelligence cards">';
    }
    if (strpos($content, $needle) === false) {
        return $content;
    }

    $card = '<article class="kingy-card kingy-ali-lotw-home-card">'
        . '<div class="kingy-launch-meta">'
        . (!empty($latest_launches_of_week['date']) ? '<span>' . esc_html($latest_launches_of_week['date']) . '</span>' : '')
        . '<span>' . esc_html__('Launches of the Week', 'kingy-ai-launch-intelligence') . '</span>'
        . '</div>'
        . '<h3><a href="' . esc_url($latest_launches_of_week['url']) . '">' . esc_html($latest_launches_of_week['title']) . '</a></h3>'
        . '<p>' . esc_html(wp_trim_words($latest_launches_of_week['summary'], 26)) . '</p>'
        . '<p><a class="kingy-card-cta" href="' . esc_url($latest_launches_of_week['url']) . '">' . esc_html($latest_launches_of_week['short_label']) . '</a></p>'
        . '</article>';

    return preg_replace('/' . preg_quote($needle, '/') . '/', $needle . $card, $content, 1);
}
function kingy_ali_homepage_latest_launch_items($limit = 6) {
    $limit = max(4, min(6, absint($limit)));
    $cache_generation = function_exists('kingy_ali_launch_collection_cache_generation') ? kingy_ali_launch_collection_cache_generation() : '1';
    $cache_key = 'kingy_ali_homepage_latest_launch_items_v3_' . $limit . '_' . current_time('Ymd') . '_' . $cache_generation;
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $items = array();
    $seen_urls = array();

    $today_radar = kingy_ali_today_daily_launch_radar_post_id();
    if ($today_radar) {
        kingy_ali_homepage_add_launch_item(
            $items,
            array(
                'date' => kingy_ali_post_date_label($today_radar),
                'type' => __('Daily AI Launch Radar', 'kingy-ai-launch-intelligence'),
                'title' => get_the_title($today_radar),
                'summary' => kingy_ali_post_summary_text($today_radar),
                'url' => get_permalink($today_radar),
                'cta' => __('Read radar', 'kingy-ai-launch-intelligence'),
            ),
            $seen_urls,
            $limit
        );
    }

    $latest_radar = kingy_ali_latest_daily_launch_radar_post_id();
    if ($latest_radar && $latest_radar !== $today_radar) {
        kingy_ali_homepage_add_launch_item(
            $items,
            array(
                'date' => kingy_ali_post_date_label($latest_radar),
                'type' => __('Latest AI Launch Radar', 'kingy-ai-launch-intelligence'),
                'title' => get_the_title($latest_radar),
                'summary' => kingy_ali_post_summary_text($latest_radar),
                'url' => get_permalink($latest_radar),
                'cta' => __('Read radar', 'kingy-ai-launch-intelligence'),
            ),
            $seen_urls,
            $limit
        );
    }

    $latest_launches_of_week = kingy_ali_latest_launches_of_week_edition_cta();
    if (!empty($latest_launches_of_week['post_id'])) {
        kingy_ali_homepage_add_launch_item(
            $items,
            array(
                'date' => $latest_launches_of_week['date'],
                'type' => __('Launches of the Week', 'kingy-ai-launch-intelligence'),
                'title' => $latest_launches_of_week['title'],
                'summary' => $latest_launches_of_week['summary'],
                'url' => $latest_launches_of_week['url'],
                'cta' => $latest_launches_of_week['short_label'],
            ),
            $seen_urls,
            $limit
        );
    }

    $launch_query = kingy_ali_query_launches(array('limit' => 2));
    foreach ((array) $launch_query->posts as $post) {
        $post_id = isset($post->ID) ? absint($post->ID) : 0;
        if (!$post_id || !kingy_ali_post_is_public_index_ready($post_id)) {
            continue;
        }

        $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
        $launch_date_label = $launch_date ? kingy_ali_public_profile_date_label($launch_date) : kingy_ali_post_date_label($post_id);
        kingy_ali_homepage_add_launch_item(
            $items,
            array(
                'date' => $launch_date_label,
                'type' => __('AI Launch Tracker', 'kingy-ai-launch-intelligence'),
                'title' => get_the_title($post_id),
                'summary' => kingy_ali_post_summary_text($post_id, 'what_launched'),
                'url' => get_permalink($post_id),
                'cta' => __('View launch', 'kingy-ai-launch-intelligence'),
            ),
            $seen_urls,
            $limit
        );
    }
    wp_reset_postdata();

    if (count($items) < $limit) {
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
        );
        $category = get_category_by_slug('ai-launches');
        if ($category && !is_wp_error($category)) {
            $query_args['cat'] = (int) $category->term_id;
        }

        $post_query = new WP_Query($query_args);
        foreach ((array) $post_query->posts as $post) {
            $post_id = isset($post->ID) ? absint($post->ID) : 0;
            if (!$post_id || !kingy_ali_post_is_public_index_ready($post_id)) {
                continue;
            }

            kingy_ali_homepage_add_launch_item(
                $items,
                array(
                    'date' => kingy_ali_post_date_label($post_id),
                    'type' => kingy_ali_radar_post_title_matches($post_id) ? __('Daily AI Launch Radar', 'kingy-ai-launch-intelligence') : __('AI Launch Analysis', 'kingy-ai-launch-intelligence'),
                    'title' => get_the_title($post_id),
                    'summary' => kingy_ali_post_summary_text($post_id),
                    'url' => get_permalink($post_id),
                    'cta' => kingy_ali_radar_post_title_matches($post_id) ? __('Read radar', 'kingy-ai-launch-intelligence') : __('Read analysis', 'kingy-ai-launch-intelligence'),
                ),
                $seen_urls,
                $limit
            );

            if (count($items) >= $limit) {
                break;
            }
        }
        wp_reset_postdata();
    }

    set_transient($cache_key, $items, 10 * MINUTE_IN_SECONDS);
    return $items;
}

function kingy_ali_render_homepage_latest_launch_intelligence() {
    $items = kingy_ali_homepage_latest_launch_items(6);
    $cta = kingy_ali_homepage_radar_cta();

    ob_start();
    ?>
    <section class="kingy-section" id="kingy-latest">
        <div class="kingy-section-header">
            <h2><?php esc_html_e('Latest AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Fresh, source-backed launch signals from the newest valid Radar, public launch records, and AI Launches coverage.', 'kingy-ai-launch-intelligence'); ?></p>
            <?php if (!empty($cta['date_label']) && $cta['state'] === 'latest') : ?>
                <p class="kingy-ali-policy-note"><?php echo esc_html(sprintf(__('Latest Radar currently available: %s.', 'kingy-ai-launch-intelligence'), $cta['date_label'])); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($items) : ?>
            <div class="kingy-grid three" aria-label="<?php esc_attr_e('Latest AI launch intelligence cards', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($items as $item) : ?>
                    <article class="kingy-card">
                        <div class="kingy-launch-meta">
                            <?php if (!empty($item['date'])) : ?><span><?php echo esc_html($item['date']); ?></span><?php endif; ?>
                            <span><?php echo esc_html($item['type']); ?></span>
                        </div>
                        <h3><a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a></h3>
                        <p><?php echo esc_html(wp_trim_words($item['summary'], 26)); ?></p>
                        <p><a class="kingy-card-cta" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['cta']); ?></a></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="kingy-card">
                <h3><?php esc_html_e('AI Launch Intelligence is refreshing.', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('No index-ready launch cards are available for this homepage slot right now. Browse the full launch hub for the current archive.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        <?php endif; ?>
        <div class="kingy-inline-links">
            <?php echo kingy_ali_render_homepage_radar_cta_link('kingy-home-button'); ?>
            <a class="kingy-home-button secondary" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI Launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_today_briefing_category_labels($query, $limit = 4) {
    $limit = max(1, absint($limit));
    if (!is_object($query) || empty($query->posts) || !is_array($query->posts)) {
        return array();
    }

    $labels = array();
    foreach ($query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id) {
            continue;
        }

        $terms = get_the_terms($post_id, 'kingy_launch_category');
        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            if (!isset($term->slug, $term->name)) {
                continue;
            }

            $labels[$term->slug] = $term->name;
            if (count($labels) >= $limit) {
                break 2;
            }
        }
    }

    return array_values($labels);
}

function kingy_ali_render_today_briefing_status($args = array()) {
    $args = wp_parse_args(
        $args,
        array(
            'has_today_launches' => false,
            'today_count' => 0,
            'shown_count' => 0,
            'category_labels' => array(),
            'latest_radar' => 0,
        )
    );

    $has_today_launches = !empty($args['has_today_launches']);
    $today_count = absint($args['today_count']);
    $shown_count = absint($args['shown_count']);
    $category_labels = is_array($args['category_labels']) ? array_filter(array_map('sanitize_text_field', $args['category_labels'])) : array();
    $latest_radar = absint($args['latest_radar']);
    $date_label = date_i18n(get_option('date_format'), current_time('timestamp'));
    $radar_date = $latest_radar ? kingy_ali_post_date_label($latest_radar) : '';
    $radar_title = $latest_radar ? get_the_title($latest_radar) : '';
    $today_status = $has_today_launches
        ? sprintf(
            _n('%d published launch record for today.', '%d published launch records for today.', $today_count, 'kingy-ai-launch-intelligence'),
            $today_count
        )
        : __('0 published launch records for today.', 'kingy-ai-launch-intelligence');
    $shown_label = $shown_count > 0
        ? sprintf(
            _n('%d latest published record shown', '%d latest published records shown', $shown_count, 'kingy-ai-launch-intelligence'),
            $shown_count
        )
        : __('0 latest published records shown', 'kingy-ai-launch-intelligence');
    $category_text = $category_labels ? implode(', ', $category_labels) : __('Watching for fresh categories', 'kingy-ai-launch-intelligence');

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-today-briefing" aria-labelledby="kingy-ali-today-briefing-title">
        <div class="kingy-ali-today-briefing__header">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Daily AI Launch Briefing', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-ali-today-briefing-title"><?php esc_html_e('Today\'s launch status', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php echo esc_html(sprintf(__('Source-backed launch intelligence for %s.', 'kingy-ai-launch-intelligence'), $date_label)); ?></p>
            </div>
            <?php if ($latest_radar) : ?>
                <a class="kingy-ali-today-briefing__radar" data-kingy-ali-track="clicked_daily_radar" data-event-label="<?php echo esc_attr($radar_title); ?>" data-event-surface="today_launch_briefing" href="<?php echo esc_url(get_permalink($latest_radar)); ?>">
                    <span><?php esc_html_e('Latest Radar', 'kingy-ai-launch-intelligence'); ?></span>
                    <strong><?php echo esc_html($radar_title); ?></strong>
                    <?php if ($radar_date) : ?><em><?php echo esc_html($radar_date); ?></em><?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
        <dl class="kingy-ali-today-briefing__stats">
            <div>
                <dt><?php esc_html_e('Date', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php echo esc_html($date_label); ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Today', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php echo esc_html($today_status); ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Latest records', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php echo esc_html($shown_label); ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('Category hints', 'kingy-ai-launch-intelligence'); ?></dt>
                <dd><?php echo esc_html($category_text); ?></dd>
            </div>
        </dl>
        <p class="kingy-ali-today-briefing__note">
            <?php
            echo esc_html(
                $has_today_launches
                    ? __('Today has published launch records, so the newest public cards are shown before the broader guide. Each card keeps its own verification state.', 'kingy-ai-launch-intelligence')
                    : __('No launch records have been published for today yet. Kingy AI treats that as a normal zero-count state and shows the latest published launch records instead.', 'kingy-ai-launch-intelligence')
            );
            ?>
        </p>
        <div class="kingy-ali-cta-row kingy-ali-today-briefing__actions">
            <a href="<?php echo esc_url(home_url('/ai-launches/this-week/')); ?>"><?php esc_html_e('View this week', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($latest_radar) : ?>
                <a href="<?php echo esc_url(get_permalink($latest_radar)); ?>"><?php esc_html_e('Read latest Radar', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse launch hub', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Split public, index-ready launch records into current and future date sets.
 *
 * Current/latest surfaces must not be led by a future operational date. Future
 * records remain public, but are surfaced separately as upcoming deadlines.
 */
function kingy_ali_public_launch_date_partitions($current_limit = 0, $upcoming_limit = 0) {
    $current_limit = absint($current_limit);
    $upcoming_limit = absint($upcoming_limit);
    $today = current_time('Y-m-d');
    $published_ids = function_exists('kingy_ali_launch_index_published_ids')
        ? kingy_ali_launch_index_published_ids()
        : get_posts(
            array(
                'post_type' => 'kingy_ai_launch',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
            )
        );
    $current = array();
    $upcoming = array();

    foreach ((array) $published_ids as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id) {
            continue;
        }

        $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
        $has_explicit_launch_date = false;
        if (is_string($launch_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $launch_date)) {
            $effective_date = $launch_date;
            $has_explicit_launch_date = true;
        } elseif (is_string($launch_date) && preg_match('/^\d{4}-\d{2}$/', $launch_date)) {
            $effective_date = $launch_date . '-01';
            $has_explicit_launch_date = true;
        } elseif (is_string($launch_date) && preg_match('/^\d{4}$/', $launch_date)) {
            $effective_date = $launch_date . '-01-01';
            $has_explicit_launch_date = true;
        } else {
            $effective_date = get_post_time('Y-m-d', false, $post_id);
        }
        $row = array(
            'id' => $post_id,
            'date' => $effective_date,
        );

        if ($has_explicit_launch_date && $effective_date > $today) {
            $upcoming[] = $row;
        } else {
            $current[] = $row;
        }
    }

    usort($current, static function ($left, $right) {
        $date_order = strcmp((string) $right['date'], (string) $left['date']);
        return $date_order !== 0 ? $date_order : ((int) $right['id'] <=> (int) $left['id']);
    });
    usort($upcoming, static function ($left, $right) {
        $date_order = strcmp((string) $left['date'], (string) $right['date']);
        return $date_order !== 0 ? $date_order : ((int) $left['id'] <=> (int) $right['id']);
    });

    $current_ids = array_column($current, 'id');
    $upcoming_ids = array_column($upcoming, 'id');

    return array(
        'current' => $current_limit > 0 ? array_slice($current_ids, 0, $current_limit) : $current_ids,
        'upcoming' => $upcoming_limit > 0 ? array_slice($upcoming_ids, 0, $upcoming_limit) : $upcoming_ids,
    );
}

function kingy_ali_public_launch_query_for_ordered_ids($post_ids) {
    $post_ids = array_values(array_unique(array_filter(array_map('absint', (array) $post_ids))));

    return new WP_Query(
        array(
            'post_type' => 'kingy_ai_launch',
            'post_status' => 'publish',
            'post__in' => $post_ids ? $post_ids : array(0),
            'posts_per_page' => $post_ids ? count($post_ids) : 1,
            'orderby' => 'post__in',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        )
    );
}

function kingy_ali_render_upcoming_deadlines_section($surface = 'launch_collection', $limit = 3) {
    $surface = sanitize_key((string) $surface);
    $partitions = kingy_ali_public_launch_date_partitions(0, max(1, absint($limit)));
    $upcoming_ids = $partitions['upcoming'];
    if (!$upcoming_ids) {
        return '';
    }

    ob_start();
    ?>
    <section id="kingy-ai-upcoming-deadlines" class="kingy-ali-content-band kingy-ali-upcoming-deadlines" aria-labelledby="kingy-ai-upcoming-deadlines-title">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Plan ahead', 'kingy-ai-launch-intelligence'); ?></p>
            <h2 id="kingy-ai-upcoming-deadlines-title"><?php esc_html_e('Upcoming Deadlines', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Future-dated migration, shutdown, and retirement records are separated from today\'s launches and latest-record lists. These dates are upcoming operational events, not launches that have already happened.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <?php if ($surface === 'command_center') : ?>
            <div class="kingy-ali-command-launch-grid">
                <?php foreach ($upcoming_ids as $post_id) : ?>
                    <?php echo kingy_ali_render_command_center_launch_card($post_id); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <?php echo kingy_ali_render_launch_grid(kingy_ali_public_launch_query_for_ordered_ids($upcoming_ids)); ?>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_today_launch_fallback() {
    $latest_radar = kingy_ali_latest_daily_launch_radar_post_id();
    $latest_query = new WP_Query();
    try {
        $partitions = kingy_ali_public_launch_date_partitions(5, 3);
        $latest_query = kingy_ali_public_launch_query_for_ordered_ids($partitions['current']);
    } catch (Throwable $throwable) {
        kingy_ali_log_launch_grid_fallback('today_fallback_latest_query_failed', $throwable);
    }

    $latest_count = is_object($latest_query) && isset($latest_query->post_count) ? absint($latest_query->post_count) : 0;
    $category_labels = kingy_ali_today_briefing_category_labels($latest_query);
    $freshness = function_exists('kingy_ali_render_launch_freshness_once')
        ? kingy_ali_render_launch_freshness_once('today_empty_fallback')
        : '';

    ob_start();
    echo $freshness;
    echo kingy_ali_render_today_briefing_status(
        array(
            'has_today_launches' => false,
            'today_count' => 0,
            'shown_count' => $latest_count,
            'category_labels' => $category_labels,
            'latest_radar' => $latest_radar,
        )
    );
    ?>
    <?php if ($latest_query->have_posts()) : ?>
        <section class="kingy-ali-content-band kingy-ali-today-records">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Latest published records', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Latest published launch records', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('These are the newest published launch records available while today remains at zero. Each card shows its own source and verification context.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <?php echo kingy_ali_render_launch_grid($latest_query); ?>
        </section>
    <?php endif; ?>
    <?php echo kingy_ali_render_upcoming_deadlines_section('today_fallback'); ?>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_week_launch_fallback() {
    $latest_radar = kingy_ali_latest_daily_launch_radar_post_id();
    $latest_query = new WP_Query();
    try {
        $latest_query = kingy_ali_query_launches(array('limit' => 5));
    } catch (Throwable $throwable) {
        kingy_ali_log_launch_grid_fallback('week_fallback_latest_query_failed', $throwable);
    }
    $freshness = function_exists('kingy_ali_render_launch_freshness_once')
        ? kingy_ali_render_launch_freshness_once('week_empty_fallback')
        : '';

    ob_start();
    echo $freshness;
    ?>
    <section class="kingy-ali-empty kingy-ali-week-fallback">
        <h3><?php esc_html_e('No AI launch records are available for this week yet.', 'kingy-ai-launch-intelligence'); ?></h3>
        <p><strong><?php esc_html_e('0 launch records published for this weekly window.', 'kingy-ai-launch-intelligence'); ?></strong></p>
        <p><?php esc_html_e('Kingy AI is showing the latest published launch records while this weekly window remains empty. Each record retains its own verification state.', 'kingy-ai-launch-intelligence'); ?></p>
        <?php if ($latest_radar) : ?>
            <p><strong><?php esc_html_e('Latest Radar:', 'kingy-ai-launch-intelligence'); ?></strong> <a href="<?php echo esc_url(get_permalink($latest_radar)); ?>"><?php echo esc_html(get_the_title($latest_radar)); ?></a><?php if (kingy_ali_post_date_label($latest_radar)) : ?> <span><?php echo esc_html('(' . kingy_ali_post_date_label($latest_radar) . ')'); ?></span><?php endif; ?></p>
        <?php endif; ?>
        <div class="kingy-ali-cta-row">
            <a href="<?php echo esc_url(home_url('/ai-launches/today/')); ?>"><?php esc_html_e('Check today', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse launch hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/ai-agents/')); ?>"><?php esc_html_e('AI Agents', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/ai-coding-tools/')); ?>"><?php esc_html_e('AI Coding Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/ai-video-tools/')); ?>"><?php esc_html_e('AI Video Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/open-weight-models/')); ?>"><?php esc_html_e('Open-Weight Models', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/ai-search-research-tools/')); ?>"><?php esc_html_e('AI Search and Research Tools', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get a Launch Visibility Score from week fallback', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="week_launch_fallback" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php if ($latest_query->have_posts()) : ?>
        <section class="kingy-ali-content-band">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Latest available records', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Recent AI launch records', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('These are the newest published launch records available while the weekly view remains at zero. Each card shows its own source and verification context.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <?php echo kingy_ali_render_launch_grid($latest_query); ?>
        </section>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_launch_collection_intro_safely($query_args, $include_company_path = true) {
    try {
        return kingy_ali_render_launch_collection_shortcode_intro($query_args, $include_company_path);
    } catch (Throwable $throwable) {
        kingy_ali_log_launch_grid_fallback('intro_render_failed', $throwable);
        return '';
    }
}

function kingy_ali_log_launch_grid_fallback($context, $throwable = null) {
    if (!function_exists('error_log')) {
        return;
    }

    $message = is_scalar($context) ? sanitize_key((string) $context) : 'unknown';
    if ($throwable instanceof Throwable) {
        $message .= ': ' . sanitize_text_field($throwable->getMessage());
    }

    error_log('Kingy AI Launch Intelligence grid fail-open fallback: ' . $message);
}

function kingy_ali_render_launch_grid_fail_open_fallback($query_args, $context = '', $throwable = null) {
    kingy_ali_log_launch_grid_fallback($context ? $context : 'grid_query_failed', $throwable);

    $period = isset($query_args['period']) ? sanitize_key($query_args['period']) : '';
    if ($period === 'today') {
        return kingy_ali_render_launch_period_fallback_safely('today', $query_args, $context, $throwable);
    }

    if ($period === 'week') {
        return kingy_ali_render_launch_period_fallback_safely('week', $query_args, $context, $throwable);
    }

    $intro = kingy_ali_render_launch_collection_intro_safely($query_args);

    ob_start();
    ?>
    <section class="kingy-ali-empty kingy-ali-grid-fallback">
        <h3><?php esc_html_e('AI Launch Intelligence is refreshing.', 'kingy-ai-launch-intelligence'); ?></h3>
        <p><?php esc_html_e('This launch collection is temporarily showing the safe hub fallback while fresh records are checked.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row">
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse launch hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return $intro . ob_get_clean();
}

function kingy_ali_render_launch_period_fallback_safely($period, $query_args = array(), $context = '', $throwable = null) {
    $period = sanitize_key((string) $period);

    try {
        if ($period === 'today') {
            $intro = kingy_ali_render_launch_collection_intro_safely($query_args, false);
            return kingy_ali_render_today_launch_fallback() . $intro . kingy_ali_render_company_visibility_path('today_launch_fallback');
        }

        $intro = kingy_ali_render_launch_collection_intro_safely($query_args);
        if ($period === 'week') {
            return $intro . kingy_ali_render_week_launch_fallback();
        }
    } catch (Throwable $fallback_throwable) {
        kingy_ali_log_launch_grid_fallback(($context ? $context . '_' : '') . 'period_fallback_failed', $fallback_throwable);
    }

    return kingy_ali_render_launch_basic_safe_fallback($period);
}

function kingy_ali_render_launch_basic_safe_fallback($period = '') {
    $period = sanitize_key((string) $period);
    $is_today = $period === 'today';
    $heading = $is_today
        ? __('No AI launch records have been published for today yet.', 'kingy-ai-launch-intelligence')
        : __('AI Launch Intelligence is refreshing.', 'kingy-ai-launch-intelligence');
    $copy = $is_today
        ? __('The count for today is 0. Browse the latest published launch records instead or check this week\'s AI launches.', 'kingy-ai-launch-intelligence')
        : __('This period currently has 0 published launch records. Browse the latest published records while the collection updates.', 'kingy-ai-launch-intelligence');
    $freshness = function_exists('kingy_ali_render_launch_freshness_once')
        ? kingy_ali_render_launch_freshness_once($is_today ? 'today_basic_fallback' : 'week_basic_fallback')
        : '';

    ob_start();
    echo $freshness;
    ?>
    <section class="kingy-ali-empty kingy-ali-emergency-safe">
        <h3><?php echo esc_html($heading); ?></h3>
        <p><?php echo esc_html($copy); ?></p>
        <div class="kingy-ali-cta-row">
            <?php if ($is_today) : ?>
                <a href="<?php echo esc_url(home_url('/ai-launches/this-week/')); ?>"><?php esc_html_e('View this week', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse launch hub', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_is_rendering_page_slug($slug) {
    if (is_admin() || !is_singular('page')) {
        return false;
    }

    $queried_id = (int) get_queried_object_id();
    if (!$queried_id) {
        return false;
    }

    $page = get_post($queried_id);
    return $page && $page->post_type === 'page' && $page->post_name === $slug;
}

function kingy_ali_maybe_replace_app_builder_comparison_article($content) {
    if (!kingy_ali_is_rendering_page_slug('lovable-vs-replit-vs-bolt-vs-bubble-vs-softr')) {
        return $content;
    }

    if (!kingy_ali_content_has_shortcode($content, 'kingy_app_builder_comparison')) {
        return $content;
    }

    return kingy_ali_shortcode_app_builder_comparison();
}

function kingy_ali_is_ai_lead_magnet_page() {
    return kingy_ali_is_rendering_page_slug('how-to-build-a-lead-magnet-with-ai');
}

function kingy_ali_maybe_replace_ai_lead_magnet_article($content) {
    if (!kingy_ali_is_ai_lead_magnet_page()) {
        return $content;
    }

    return kingy_ali_shortcode_ai_lead_magnet_guide();
}

function kingy_ali_is_ai_landing_page_page() {
    return kingy_ali_is_rendering_page_slug('how-to-build-a-landing-page-with-ai');
}

function kingy_ali_maybe_replace_ai_landing_page_article($content) {
    if (!kingy_ali_is_ai_landing_page_page()) {
        return $content;
    }

    return kingy_ali_shortcode_ai_landing_page_guide();
}

function kingy_ali_is_safe_ai_agent_page() {
    return kingy_ali_is_rendering_page_slug('how-to-build-an-ai-agent-safely');
}

function kingy_ali_maybe_replace_safe_ai_agent_article($content) {
    if (!kingy_ali_is_safe_ai_agent_page()) {
        return $content;
    }

    return kingy_ali_shortcode_safe_ai_agent_guide();
}

function kingy_ali_is_vibe_coding_page() {
    return kingy_ali_is_rendering_page_slug('vibe-coding-for-beginners-ai-app-builder');
}

function kingy_ali_maybe_replace_vibe_coding_beginner_hub($content) {
    if (!kingy_ali_is_vibe_coding_page()) {
        return $content;
    }

    return kingy_ali_shortcode_vibe_coding_beginner_hub();
}

function kingy_ali_is_replit_beginner_page() {
    return kingy_ali_is_rendering_page_slug('replit-for-beginners-ai-apps');
}

function kingy_ali_maybe_replace_replit_beginner_guide($content) {
    if (!kingy_ali_is_replit_beginner_page()) {
        return $content;
    }

    return kingy_ali_shortcode_replit_beginner_guide();
}

function kingy_ali_ai_lead_magnet_seo_title($title) {
    if (!kingy_ali_is_ai_lead_magnet_page()) {
        return $title;
    }

    return __('How to Build a Lead Magnet With AI: Generator, Examples, Templates', 'kingy-ai-launch-intelligence');
}

function kingy_ali_ai_lead_magnet_seo_description($description) {
    if (!kingy_ali_is_ai_lead_magnet_page()) {
        return $description;
    }

    return __('Build an AI lead magnet with a format selector, interactive architect, ROI calculator, examples, prompts, privacy notes, and launch QA checklist.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_ai_landing_page_seo_title($title) {
    if (!kingy_ali_is_ai_landing_page_page()) {
        return $title;
    }

    return __('How to Build a Landing Page With AI: Prompts, Examples, QA', 'kingy-ai-launch-intelligence');
}

function kingy_ali_ai_landing_page_seo_description($description) {
    if (!kingy_ali_is_ai_landing_page_page()) {
        return $description;
    }

    return __('Build a better AI landing page with a prompt builder, section generator, QA scorecard, examples, copy prompts, SEO guidance, and safety checks.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_safe_ai_agent_seo_title($title) {
    if (!kingy_ali_is_safe_ai_agent_page()) {
        return $title;
    }

    return __('How to Build an AI Agent Safely: Beginner Guide, Templates, Tools', 'kingy-ai-launch-intelligence');
}

function kingy_ali_safe_ai_agent_seo_description($description) {
    if (!kingy_ali_is_safe_ai_agent_page()) {
        return $description;
    }

    return __('Build a safe AI agent with a risk evaluator, permission calculator, brief builder, test plan generator, examples, checklists, glossary, and FAQs.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_vibe_coding_seo_title($title) {
    if (!kingy_ali_is_vibe_coding_page()) {
        return $title;
    }

    return __('Vibe Coding for Beginners: AI App Builder Guide, Planner, Prompts', 'kingy-ai-launch-intelligence');
}

function kingy_ali_vibe_coding_seo_description($description) {
    if (!kingy_ali_is_vibe_coding_page()) {
        return $description;
    }

    return __('Learn vibe coding for beginners with an AI app builder planner, first app idea generator, builder selector, copyable prompts, testing checklist, and launch approval gate.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_replit_beginner_seo_title($title) {
    if (!kingy_ali_is_replit_beginner_page()) {
        return $title;
    }

    return __('Replit for Beginners: Build Your First AI App, Prompts, QA', 'kingy-ai-launch-intelligence');
}

function kingy_ali_replit_beginner_seo_description($description) {
    if (!kingy_ali_is_replit_beginner_page()) {
        return $description;
    }

    return __('A beginner Replit guide for building a first AI app with workspace basics, first project ideas, copyable prompts, debugging workflow, secrets, deployment checks, and launch QA.', 'kingy-ai-launch-intelligence');
}

function kingy_ali_ai_lead_magnet_document_title($parts) {
    if (kingy_ali_is_ai_lead_magnet_page()) {
        $parts['title'] = __('How to Build a Lead Magnet With AI', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_ai_landing_page_document_title($parts) {
    if (kingy_ali_is_ai_landing_page_page()) {
        $parts['title'] = __('How to Build a Landing Page With AI', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_safe_ai_agent_document_title($parts) {
    if (kingy_ali_is_safe_ai_agent_page()) {
        $parts['title'] = __('How to Build an AI Agent Safely', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_vibe_coding_document_title($parts) {
    if (kingy_ali_is_vibe_coding_page()) {
        $parts['title'] = __('Vibe Coding for Beginners: AI App Builder', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_replit_beginner_document_title($parts) {
    if (kingy_ali_is_replit_beginner_page()) {
        $parts['title'] = __('Replit for Beginners: Build Your First AI App', 'kingy-ai-launch-intelligence');
    }

    return $parts;
}

function kingy_ali_ai_lead_magnet_schema() {
    if (!kingy_ali_is_ai_lead_magnet_page()) {
        return;
    }

    $faqs = kingy_ali_ai_lead_magnet_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'HowTo',
                'name' => __('How to build a lead magnet with AI', 'kingy-ai-launch-intelligence'),
                'description' => __('A privacy-aware workflow for choosing, generating, testing, and publishing an AI-assisted lead magnet.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Pick one narrow audience and problem', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Choose the format that gives value fastest', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Generate the promise, outline, landing copy, emails, and QA checklist', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Publish value before optional email capture', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Measure completions, opt-ins, qualified leads, and follow-up quality', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_ai_landing_page_schema() {
    if (!kingy_ali_is_ai_landing_page_page()) {
        return;
    }

    $faqs = kingy_ali_ai_landing_page_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('How to Build a Landing Page With AI', 'kingy-ai-launch-intelligence'),
                'description' => __('A practical guide with prompts, examples, an AI landing page prompt builder, section generator, and QA scorecard.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to build a landing page with AI', 'kingy-ai-launch-intelligence'),
                'description' => __('A beginner workflow for planning, prompting, building, checking, and publishing an AI-assisted landing page.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Define the audience, promise, proof, CTA, and constraints', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Generate a section-by-section landing page outline', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Draft the hero, proof, FAQ, and calls to action', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Ask Codex or an AI builder to implement the page using existing styles', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Run mobile, SEO, copy, link, schema, and trust QA before publishing', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_safe_ai_agent_schema() {
    if (!kingy_ali_is_safe_ai_agent_page()) {
        return;
    }

    $faqs = kingy_ali_safe_ai_agent_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('How to Build an AI Agent Safely', 'kingy-ai-launch-intelligence'),
                'description' => __('A beginner-friendly guide and toolkit for planning, scoping, testing, and supervising a safe AI agent.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'BreadcrumbList',
                'itemListElement' => array(
                    array('@type' => 'ListItem', 'position' => 1, 'name' => __('Build With AI Academy', 'kingy-ai-launch-intelligence'), 'item' => home_url('/ai/build-with-ai-academy/')),
                    array('@type' => 'ListItem', 'position' => 2, 'name' => __('How to Build an AI Agent Safely', 'kingy-ai-launch-intelligence'), 'item' => get_permalink()),
                ),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to build an AI agent safely', 'kingy-ai-launch-intelligence'),
                'description' => __('A safety-first process for choosing, briefing, permissioning, testing, and reviewing a beginner AI agent.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Choose a narrow, reviewable job', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Map the manual workflow before automating', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Define inputs, outputs, tools, data, and forbidden actions', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Add permission limits and human approval gates', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Test normal, edge, malicious, privacy, and rollback cases', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Monitor results and improve only after review', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_vibe_coding_schema() {
    if (!kingy_ali_is_vibe_coding_page()) {
        return;
    }

    $faqs = kingy_ali_vibe_coding_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('Vibe Coding for Beginners: AI App Builder', 'kingy-ai-launch-intelligence'),
                'description' => __('A beginner hub for choosing an AI app builder, planning a tiny MVP, copying strong prompts, testing the result, and launching only after human review.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to start vibe coding your first app', 'kingy-ai-launch-intelligence'),
                'description' => __('A plain-English workflow for turning a beginner app idea into a scoped AI-assisted build.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Pick one small app job for one audience', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Choose the simplest app type and builder path', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Write a first prompt with inputs, output, limits, and done criteria', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Build the smallest working version', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Test mobile, empty states, errors, copy controls, links, and claims', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Launch only after owner approval and rollback notes are clear', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_replit_beginner_schema() {
    if (!kingy_ali_is_replit_beginner_page()) {
        return;
    }

    $faqs = kingy_ali_replit_beginner_faqs();
    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'Article',
                'headline' => __('Replit for Beginners: Build Your First AI App', 'kingy-ai-launch-intelligence'),
                'description' => __('A practical beginner guide to planning, prompting, building, debugging, testing, and publishing a first Replit AI app.', 'kingy-ai-launch-intelligence'),
                'mainEntityOfPage' => get_permalink(),
            ),
            array(
                '@type' => 'HowTo',
                'name' => __('How to build your first AI app in Replit', 'kingy-ai-launch-intelligence'),
                'description' => __('A beginner workflow for using Replit to create a small, testable, AI-assisted app without hiding the code.', 'kingy-ai-launch-intelligence'),
                'step' => array(
                    array('@type' => 'HowToStep', 'name' => __('Choose one tiny app idea and write the success criteria', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Create or import the Replit project and identify the main files', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Ask AI for the smallest working version', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Run the app, inspect the preview, and read the logs', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Fix one error or missing behavior at a time', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Move API keys into Secrets and test deployment readiness', 'kingy-ai-launch-intelligence')),
                    array('@type' => 'HowToStep', 'name' => __('Publish only after mobile, data, link, privacy, and rollback checks pass', 'kingy-ai-launch-intelligence')),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                'mainEntity' => array_map(
                    function ($faq) {
                        return array(
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => $faq['answer'],
                            ),
                        );
                    },
                    $faqs
                ),
            ),
        ),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}

function kingy_ali_shortcode_hub() {
    kingy_ali_enqueue_assets();
    $filters = kingy_ali_request_filters();
    $filters['page'] = kingy_ali_launch_archive_current_page($filters['page']);
    if (is_tax('kingy_launch_category') && empty($filters['category'])) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $filters['category'] = $term->slug;
        }
    }
    $has_search = kingy_ali_launch_has_filters($filters);
    $query = kingy_ali_query_launches(
        array_merge(
            $filters,
            array(
                'limit' => 18,
                'track_search' => $has_search,
            )
        )
    );

    ob_start();
    ?>
    <section class="kingy-ali-hub kingy-ali-command-center">
        <?php echo kingy_ali_render_command_center_hero(is_singular('page') ? 'h2' : 'h1'); ?>
        <?php echo kingy_ali_render_command_center_today(); ?>
        <?php echo kingy_ali_render_launch_brief_inline_signup(); ?>
        <?php echo kingy_ali_render_command_center_weekly_awards(); ?>
        <?php echo kingy_ali_render_command_center_category_navigation(); ?>
        <section id="kingy-ai-launch-tracker" class="kingy-ali-content-band kingy-ali-tracker-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Launch tracker', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Browse the AI Launch Tracker', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Search source-backed launch records, then use common filters first and advanced filters only when you need a narrower view.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <?php echo kingy_ali_render_launch_search($filters); ?>
            <?php echo kingy_ali_render_launch_collection($query, $filters); ?>
        </section>
        <?php echo kingy_ali_render_hub_methodology(); ?>
        <?php echo kingy_ali_render_founder_submission_path('launch_command_center'); ?>
        <?php echo kingy_ali_render_sponsor_path('launch_command_center'); ?>
        <?php echo kingy_ali_render_launch_intelligence_newsletter_module('launch_command_center'); ?>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_search() {
    $filters = kingy_ali_request_filters();
    $query = kingy_ali_query_launches(array_merge($filters, array('limit' => 24, 'track_search' => kingy_ali_launch_has_filters($filters))));

    return kingy_ali_render_launch_search($filters) . kingy_ali_render_launch_collection($query, $filters);
}

function kingy_ali_shortcode_grid($atts) {
    kingy_ali_enqueue_assets();
    $request_filters = kingy_ali_request_filters();
    $atts = shortcode_atts(
        array(
            'period' => '',
            'category' => '',
            'launch_type' => '',
            'audience' => '',
            'attribute' => '',
            'limit' => 12,
            'youtube_worthy' => '',
        ),
        $atts,
        'kingy_launch_grid'
    );

    // The existing managed search/research page contains two legacy grids.
    // Normalize the first to the composite taxonomy alias and suppress the
    // second so the route has one query, result count, and paginator without a
    // live content mutation.
    $managed_path = function_exists('kingy_ali_current_launch_collection_page_path')
        ? kingy_ali_current_launch_collection_page_path()
        : '';
    if ($managed_path === 'ai-launches/ai-search-research-tools') {
        $legacy_category = sanitize_title((string) $atts['category']);
        if ($legacy_category === 'ai-search-tools') {
            $atts['category'] = 'ai-search-research-tools';
        } elseif ($legacy_category === 'ai-research-tools') {
            return '';
        }
    }

    $period = sanitize_key($atts['period']);
    if (!in_array($period, array('', 'today', 'week'), true)) {
        $period = '';
    }

    $limit = absint($atts['limit']);
    $limit = $limit > 0 ? min($limit, 48) : 12;

    $query_args = array(
        'period' => $period,
        'category' => sanitize_title($atts['category']),
        'launch_type' => sanitize_title($atts['launch_type']),
        'audience' => sanitize_title($atts['audience']),
        'attribute' => sanitize_title($atts['attribute']),
        'limit' => $limit,
        'youtube_worthy' => in_array(strtolower((string) $atts['youtube_worthy']), array('1', 'true', 'yes'), true),
        'page' => $request_filters['page'],
        'sort' => $request_filters['sort'],
    );
    $collection_filters = array_merge($request_filters, array_filter(array(
        'period' => $query_args['period'],
        'category' => $query_args['category'],
        'launch_type' => $query_args['launch_type'],
        'audience' => $query_args['audience'],
        'attribute' => $query_args['attribute'],
    )));

    try {
        $query = kingy_ali_query_launches($query_args);
    } catch (Throwable $throwable) {
        return kingy_ali_render_launch_grid_fail_open_fallback($query_args, 'query_exception', $throwable);
    }

    if (!is_object($query) || !isset($query->posts) || !isset($query->post_count)) {
        return kingy_ali_render_launch_grid_fail_open_fallback($query_args, 'invalid_query_result');
    }

    if ($query_args['period'] === 'today' && (int) $query->post_count === 0) {
        return kingy_ali_render_launch_period_fallback_safely('today', $query_args, 'today_empty_result');
    }

    if ($query_args['period'] === 'week' && (int) $query->post_count === 0) {
        return kingy_ali_render_launch_period_fallback_safely('week', $query_args, 'week_empty_result');
    }

    if ($query_args['period'] === 'today') {
        try {
            $grid = kingy_ali_render_launch_collection($query, $collection_filters);
        } catch (Throwable $throwable) {
            return kingy_ali_render_launch_grid_fail_open_fallback($query_args, 'grid_render_exception', $throwable);
        }

        return kingy_ali_render_today_briefing_status(
            array(
                'has_today_launches' => true,
                'today_count' => absint($query->post_count),
                'shown_count' => absint($query->post_count),
                'category_labels' => kingy_ali_today_briefing_category_labels($query),
                'latest_radar' => kingy_ali_latest_daily_launch_radar_post_id(),
            )
        ) . $grid . kingy_ali_render_upcoming_deadlines_section('today_launch_grid') . kingy_ali_render_launch_collection_intro_safely($query_args, false) . kingy_ali_render_company_visibility_path('today_launch_grid');
    }

    try {
        $grid = kingy_ali_render_launch_collection($query, $collection_filters);
    } catch (Throwable $throwable) {
        return kingy_ali_render_launch_grid_fail_open_fallback($query_args, 'grid_render_exception', $throwable);
    }

    return kingy_ali_render_launch_collection_intro_safely($query_args) . $grid;
}

function kingy_ali_render_launch_collection_shortcode_intro($query_args, $include_company_path = true) {
    $context = kingy_ali_launch_collection_intro_context($query_args);
    if (!$context) {
        return '';
    }

    static $rendered = array();
    if (isset($rendered[$context])) {
        return '';
    }
    $rendered[$context] = true;

    $intros = kingy_ali_launch_collection_intro_content();
    if (empty($intros[$context])) {
        return '';
    }

    $intro = $intros[$context];
    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-collection-guide">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Category guide', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php echo esc_html($intro['heading']); ?></h2>
            <p><?php echo esc_html($intro['intro']); ?></p>
        </div>
        <div class="kingy-ali-content-grid">
            <article class="kingy-ali-text-panel">
                <h3><?php esc_html_e('What belongs here', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php echo esc_html($intro['belongs']); ?></p>
            </article>
            <article class="kingy-ali-text-panel">
                <h3><?php esc_html_e('Why this matters', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php echo esc_html($intro['matters']); ?></p>
            </article>
        </div>
        <?php if (!empty($intro['links'])) : ?>
            <div class="kingy-ali-link-list">
                <?php foreach ($intro['links'] as $link) : ?>
                    <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($link['label']); ?>" data-event-surface="collection_guide" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php if ($include_company_path) : ?>
        <?php echo kingy_ali_render_company_visibility_path('collection_guide'); ?>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function kingy_ali_launch_collection_intro_context($query_args) {
    $path = kingy_ali_current_launch_collection_page_path();
    if ($path) {
        $path_context = str_replace('ai-launches/', '', $path);
        if ($path_context === 'ai-launches') {
            return '';
        }
        return $path_context;
    }

    if (!empty($query_args['period'])) {
        return $query_args['period'] === 'week' ? 'this-week' : $query_args['period'];
    }

    if (!empty($query_args['category'])) {
        return $query_args['category'];
    }

    if (!empty($query_args['attribute'])) {
        return $query_args['attribute'];
    }

    return '';
}

function kingy_ali_current_launch_collection_page_path() {
    if (!is_page()) {
        return '';
    }

    $post_id = get_queried_object_id();
    return $post_id ? trim((string) get_page_uri($post_id), '/') : '';
}

function kingy_ali_launch_collection_intro_content() {
    $hub = home_url('/ai-launches/');
    return array(
        'today' => array(
            'heading' => __('Today\'s AI launches', 'kingy-ai-launch-intelligence'),
            'intro' => __('A short daily view of new AI products, model releases, demos, funding notes, and useful updates that are ready for source-backed review.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Fresh launch records with clear dates, official links, categories, and enough context to decide whether to investigate now or save for later.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Daily tracking keeps the database useful for creators, founders, buyers, and SEO research without pretending every announcement is equally important.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('View this week\'s launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/this-week/')),
                array('label' => __('Back to AI Launch Intelligence', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'this-week' => array(
            'heading' => __('This week\'s AI launches', 'kingy-ai-launch-intelligence'),
            'intro' => __('A weekly scan of notable AI launches, grouped around product usefulness, demo value, source quality, and creator or buyer relevance.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Launches with enough signal to compare across categories, including agents, coding tools, models, video tools, image tools, and funding announcements.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Weekly context helps separate durable product movement from one-off announcement noise.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('View today\'s launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/today/')),
                array('label' => __('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/launches-of-the-week/')),
                kingy_ali_latest_launches_of_week_edition_link('short_label'),
                array('label' => __('Back to AI Launch Intelligence', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-agents' => array(
            'heading' => __('AI agent launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('AI agent launches cover tools that can plan, use tools, browse, code, operate workflows, or complete background tasks with some autonomy.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Browser agents, workflow agents, enterprise agent platforms, coding agents, background task agents, and agent infrastructure with verifiable product or release links.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Agent claims can be noisy, so source links, demos, permissions, API access, and clear human-review boundaries matter more than broad autonomy language.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Agent Adoption Playbook', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/ai-agent-adoption-playbook/')),
                array('label' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/')),
                array('label' => __('AI Search and Research Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-search-research-tools/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-app-builders' => array(
            'heading' => __('AI app builder and vibe coding context', 'kingy-ai-launch-intelligence'),
            'intro' => __('This page tracks prompt-to-app builders, vibe coding tools, and software-building workflows that help users move from an idea to a working app or prototype.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('AI app builders, no-code or low-code builders, code-generating workspaces, hosted app agents, and tools that help non-specialists or small teams ship software safely.', 'kingy-ai-launch-intelligence'),
            'matters' => __('The useful question is not only whether a tool can generate code, but whether it supports testing, deployment, secrets, maintenance, and realistic ownership after launch.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/')),
                array('label' => __('AI Agent Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-agents/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-coding-tools' => array(
            'heading' => __('AI coding tool launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('AI coding launches focus on developer workflows: IDE agents, repo understanding, debugging, pull requests, code review, testing, and cloud software tasks.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Coding assistants, autonomous coding agents, PR agents, debugging tools, model releases aimed at code, developer APIs, and cloud coding workspaces.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Developers need to know what changed, where the tool fits in the stack, whether it has source or repo evidence, and whether it can be safely reviewed.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI App Builder Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-app-builders/')),
                array('label' => __('AI Open-Weight Models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/open-weight-models/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-video-tools' => array(
            'heading' => __('AI video tool launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('AI video launches include generation, editing, avatars, demos, creator workflows, and production tools that can be evaluated visually.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Video generators, editing copilots, avatar tools, model releases for motion or media, demo workflows, and tools with useful YouTube review potential.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Video tools are easiest to overhype from short demos, so source links, visible output quality, workflow fit, and pricing clarity are especially important.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Image Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-image-tools/')),
                array('label' => __('Creator Coverage Potential', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/creator-coverage-ai-launches/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-image-tools' => array(
            'heading' => __('AI image tool launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('AI image launches track tools and models for image generation, editing, design, visual ideation, and campaign creative workflows.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Image generators, editing tools, design assistants, model updates, style systems, and visual workflows with official demos or source-backed product notes.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Useful image coverage connects output quality to real creative work: controls, licensing, consistency, editing, cost, and where the tool fits in a workflow.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Video Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-video-tools/')),
                array('label' => __('Creator Coverage Potential', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/creator-coverage-ai-launches/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'open-weight-models' => array(
            'heading' => __('AI open-weight model launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('Open-weight model launches focus on models whose weights or artifacts can be inspected, downloaded, deployed, or adapted under stated license terms.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Model releases with official posts, model cards, GitHub or Hugging Face links, license notes, deployment details, and clear caution where openness is limited.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Open-weight does not automatically mean unrestricted use, so licensing, local/private deployment, commercial terms, and source quality need to be visible.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/')),
                array('label' => __('AI Search and Research Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-search-research-tools/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-open-weight-models' => array(
            'heading' => __('AI open-weight model launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('Open-weight model launches focus on models whose weights or artifacts can be inspected, downloaded, deployed, or adapted under stated license terms.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Model releases with official posts, model cards, GitHub or Hugging Face links, license notes, deployment details, and clear caution where openness is limited.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Open-weight does not automatically mean unrestricted use, so licensing, local/private deployment, commercial terms, and source quality need to be visible.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/')),
                array('label' => __('AI Search and Research Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-search-research-tools/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'ai-search-research-tools' => array(
            'heading' => __('AI search and research launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('AI search and research launches cover tools that help users find, retrieve, cite, summarize, compare, and act on source-backed information.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('AI search engines, research assistants, citation tools, retrieval systems, report workflows, browser research agents, and APIs with clear source behavior.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Trust depends on citations, freshness, retrieval quality, source handling, and whether a user can verify the answer rather than accept a generated summary blindly.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('AI Agent Launches', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-agents/')),
                array('label' => __('AI Open-Weight Models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/open-weight-models/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'funding-announcements' => array(
            'heading' => __('AI funding announcement context', 'kingy-ai-launch-intelligence'),
            'intro' => __('Funding announcements are tracked as market and distribution signals, not as automatic proof that a product is useful or mature.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Verified funding news, credible company announcements, investor posts, acquisition or financing updates, and launch records where funding affects market context.', 'kingy-ai-launch-intelligence'),
            'matters' => __('Funding can explain momentum, hiring, and go-to-market pressure, but product quality still needs source-backed demos, pricing clarity, and real workflow usefulness.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('Creator Coverage Potential', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/creator-coverage-ai-launches/')),
                array('label' => __('Submit a launch', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/submit/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
        'founder-submitted-ai-tools' => array(
            'heading' => __('Founder-submitted AI launch context', 'kingy-ai-launch-intelligence'),
            'intro' => __('Founder-submitted launches are useful leads for coverage, but they still need editorial review, source links, and verification before high-confidence placement.', 'kingy-ai-launch-intelligence'),
            'belongs' => __('Submissions with official product URLs, launch details, demos, pricing notes, source links, and enough context to evaluate audience and creator coverage fit.', 'kingy-ai-launch-intelligence'),
            'matters' => __('The submission path gives founders a clear way into the database without weakening the standard for source-backed public records.', 'kingy-ai-launch-intelligence'),
            'links' => array(
                array('label' => __('Submit a launch', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/submit/')),
                array('label' => __('Launches of the Week', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/launches-of-the-week/')),
                array('label' => __('Score launch readiness', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launch-scorecard/')),
                array('label' => __('Back to hub', 'kingy-ai-launch-intelligence'), 'url' => $hub),
            ),
        ),
    );
}

function kingy_ali_shortcode_trending_launches($atts) {
    $atts = shortcode_atts(array('limit' => 8), $atts, 'kingy_trending_launches');
    $query = kingy_ali_query_launches(array('limit' => absint($atts['limit']), 'attribute' => 'traction-signal'));

    return kingy_ali_render_launch_grid($query);
}

function kingy_ali_shortcode_youtube_worthy_launches($atts) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 12), $atts, 'kingy_youtube_worthy_launches');
    $filters = kingy_ali_request_filters();
    $limit = absint($atts['limit']);
    $limit = $limit > 0 ? min($limit, 48) : 12;
    $query = kingy_ali_query_launches(
        array_merge(
            $filters,
            array(
                'limit' => $limit,
                'youtube_worthy' => true,
                'track_search' => kingy_ali_launch_has_filters($filters),
            )
        )
    );

    return kingy_ali_render_launch_collection($query, $filters);
}

function kingy_ali_shortcode_creator_coverage_launches($atts) {
    kingy_ali_enqueue_assets();
    $atts = shortcode_atts(array('limit' => 12, 'heading' => 'yes'), $atts, 'kingy_creator_coverage_launches');
    $filters = kingy_ali_request_filters();
    $has_filter = kingy_ali_launch_has_filters($filters);
    $limit = absint($atts['limit']);
    $limit = $limit > 0 ? min($limit, 48) : 12;
    $query = kingy_ali_query_launches(
        array_merge(
            $filters,
            array(
                'limit' => $limit,
                'creator_coverage' => true,
                'track_search' => $has_filter,
            )
        )
    );

    ob_start();
    if ($atts['heading'] !== 'no') {
        ?>
        <section class="kingy-ali-coverage-intro">
            <h2><?php esc_html_e('AI Companies and Launches With Strong Creator Coverage Potential', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('AI launches that appear well-suited for demos, reviews, creator education, founder storytelling, and practical product explainers.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-content-grid">
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('What belongs here', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Launches with strong demos, clear before-and-after workflows, useful founder stories, credible source links, or enough practical detail to support a YouTube review, tutorial, or SEO article.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
                <article class="kingy-ali-text-panel">
                    <h3><?php esc_html_e('Why this matters', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Creator-friendly does not mean automatically sponsor-ready. The shortlist helps separate products with explainable audience value from launches that still need clearer proof, demos, or positioning.', 'kingy-ai-launch-intelligence'); ?></p>
                </article>
            </div>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
            <?php echo kingy_ali_render_creator_coverage_filters($filters); ?>
            <div class="kingy-ali-cta-row">
                <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_coverage_intro" href="<?php echo esc_url(home_url('/ai-launch-scorecard/?kingy_interest=creator_coverage#kingy-ai-launch-scorecard-review')); ?>"><?php esc_html_e('Request creator coverage review', 'kingy-ai-launch-intelligence'); ?></a>
                <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_coverage_intro" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
        <?php
    }
    echo kingy_ali_render_launch_collection($query, $filters);

    return ob_get_clean();
}

function kingy_ali_creator_coverage_filter_options() {
    return array(
        '' => __('All coverage candidates', 'kingy-ai-launch-intelligence'),
        'strong-demo' => __('Strong demo', 'kingy-ai-launch-intelligence'),
        'clear-use-case' => __('Clear use case', 'kingy-ai-launch-intelligence'),
        'creator-friendly' => __('Creator-friendly', 'kingy-ai-launch-intelligence'),
        'business-friendly' => __('Business-friendly', 'kingy-ai-launch-intelligence'),
        'developer-friendly' => __('Developer-friendly', 'kingy-ai-launch-intelligence'),
        'founder-submitted' => __('Founder-submitted', 'kingy-ai-launch-intelligence'),
        'funding-announced' => __('Funding announced', 'kingy-ai-launch-intelligence'),
        'product-hunt-traction' => __('Product Hunt traction', 'kingy-ai-launch-intelligence'),
        'video-demo-available' => __('Video demo available', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_render_creator_coverage_filters($filters) {
    $selected = isset($filters['attribute']) ? sanitize_title($filters['attribute']) : '';
    $base_url = home_url('/ai-launches/creator-coverage-ai-launches/');
    $options = kingy_ali_creator_coverage_filter_options();

    ob_start();
    echo '<nav class="kingy-ali-filter-chips" aria-label="' . esc_attr__('Creator coverage filters', 'kingy-ai-launch-intelligence') . '">';
    foreach ($options as $slug => $label) {
        $url = $slug ? add_query_arg('kali_attribute', $slug, $base_url) : remove_query_arg('kali_attribute', $base_url);
        $is_active = $selected === $slug || ($selected === '' && $slug === '');
        echo '<a class="kingy-ali-filter-chip' . ($is_active ? ' is-active' : '') . '" data-kingy-ali-track="clicked_category_path" data-event-label="' . esc_attr($label) . '" data-event-surface="creator_coverage_filter" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    echo '</nav>';

    return ob_get_clean();
}

function kingy_ali_render_command_center_hero($heading_tag = 'h1') {
    $heading_tag = in_array($heading_tag, array('h1', 'h2'), true) ? $heading_tag : 'h1';

    ob_start();
    ?>
    <div class="kingy-ali-hero kingy-ali-command-hero">
        <p class="kingy-ali-kicker"><?php esc_html_e('Verified AI Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></p>
        <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('See what launched, what changed, and what it costs.', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
        <p><?php esc_html_e('Track AI product launches, model updates, pricing changes and tested workflows. Each record separates official sources, company claims, Kingy testing and third-party evidence so you can see what is known and what remains unverified.', 'kingy-ai-launch-intelligence'); ?></p>
        <div class="kingy-ali-cta-row kingy-ali-command-hero-actions">
            <a class="kingy-ali-command-hero-primary" data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('See today\'s launches', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_hero" href="<?php echo esc_url(home_url('/ai-launches/today/')); ?>"><?php esc_html_e('See today\'s launches', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="kingy-ali-command-hero-secondary" data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse launch records', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_hero" href="#kingy-ai-launch-tracker"><?php esc_html_e('Browse launch records', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="kingy-ali-command-hero-secondary" data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit a product launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_hero" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a product launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a class="kingy-ali-command-hero-secondary" data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Distribute your launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_hero" href="<?php echo esc_url(home_url('/sponsor-fit-review/')); ?>"><?php esc_html_e('Distribute your launch', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_command_center_launch_ids($query) {
    if (!is_object($query) || !isset($query->posts)) {
        return array();
    }

    $ids = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if ($post_id) {
            $ids[] = $post_id;
        }
    }

    return array_values(array_unique($ids));
}

function kingy_ali_render_command_center_today() {
    $latest_radar_id = kingy_ali_latest_daily_launch_radar_post_id();
    $launch_ids = array();
    $fallback_note = '';

    try {
        $partitions = kingy_ali_public_launch_date_partitions(6, 3);
        $today_query = kingy_ali_query_launches(array('period' => 'today', 'limit' => 6));
        $launch_ids = kingy_ali_command_center_launch_ids($today_query);
        if (count($launch_ids) < 6) {
            foreach ($partitions['current'] as $latest_post_id) {
                if (count($launch_ids) >= 6) {
                    break;
                }
                if (!in_array($latest_post_id, $launch_ids, true)) {
                    $launch_ids[] = $latest_post_id;
                }
            }
            if ($launch_ids && !$today_query->post_count) {
                $fallback_note = __('No source-ready records are tagged for today yet, so this section is showing the latest public launch records available in the tracker.', 'kingy-ai-launch-intelligence');
            } elseif (count($launch_ids) > $today_query->post_count) {
                $fallback_note = __('Today has fewer than six source-ready records, so the remaining cards show the latest public launch records available in the tracker.', 'kingy-ai-launch-intelligence');
            }
        }
    } catch (Throwable $throwable) {
        kingy_ali_log_launch_grid_fallback('command_center_today_failed', $throwable);
    }

    ob_start();
    ?>
    <section id="kingy-ai-launches-today" class="kingy-ali-content-band kingy-ali-command-today">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Daily radar', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Today\'s AI Launches', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('A Radar edition publishes only when the day has enough source-ready signal; the tracker continues to show the newest verified records.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <?php if ($latest_radar_id) : ?>
            <article class="kingy-ali-radar-card">
                <div>
                    <p class="kingy-ali-card__meta"><span><?php esc_html_e('Latest Daily AI Launch Radar', 'kingy-ai-launch-intelligence'); ?></span><?php echo esc_html(kingy_ali_post_date_label($latest_radar_id)); ?></p>
                    <h3><a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr(get_the_title($latest_radar_id)); ?>" data-event-surface="command_center_today" href="<?php echo esc_url(get_permalink($latest_radar_id)); ?>"><?php echo esc_html(get_the_title($latest_radar_id)); ?></a></h3>
                    <p><?php echo esc_html(kingy_ali_post_summary_text($latest_radar_id)); ?></p>
                </div>
                <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Read latest Daily AI Launch Radar', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_today" href="<?php echo esc_url(get_permalink($latest_radar_id)); ?>"><?php esc_html_e('Read Daily Radar', 'kingy-ai-launch-intelligence'); ?></a>
            </article>
        <?php endif; ?>
        <?php if ($fallback_note) : ?>
            <p class="kingy-ali-policy-note"><?php echo esc_html($fallback_note); ?></p>
        <?php endif; ?>
        <?php if ($launch_ids) : ?>
            <div class="kingy-ali-command-launch-grid">
                <?php foreach ($launch_ids as $post_id) : ?>
                    <?php echo kingy_ali_render_command_center_launch_card($post_id); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="kingy-ali-empty">
                <h3><?php esc_html_e('Launch records are being checked.', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Kingy AI only surfaces launch cards here after source, date, category, and public record checks have enough signal.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse the launch tracker', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php echo kingy_ali_render_upcoming_deadlines_section('command_center'); ?>
    <?php
    return ob_get_clean();
}

function kingy_ali_command_center_category_label($post_id) {
    $terms = get_the_terms($post_id, 'kingy_launch_category');
    if (!is_wp_error($terms) && !empty($terms)) {
        return $terms[0]->name;
    }

    return __('Uncategorized', 'kingy-ai-launch-intelligence');
}

function kingy_ali_command_center_launch_date_label($post_id) {
    $launch_date = kingy_ali_public_profile_meta_text($post_id, 'launch_date');
    $label = $launch_date ? kingy_ali_public_profile_date_label($launch_date) : '';
    return $label ? $label : kingy_ali_post_date_label($post_id);
}

function kingy_ali_command_center_pricing_status($post_id) {
    $pricing = kingy_ali_public_profile_meta_text($post_id, 'pricing');
    if ($pricing !== '') {
        return $pricing;
    }

    $free_plan = kingy_ali_public_profile_meta_text($post_id, 'free_plan');
    if ($free_plan === 'yes') {
        return __('Free plan reported', 'kingy-ai-launch-intelligence');
    }

    if ($free_plan === 'no') {
        return __('No free plan reported', 'kingy-ai-launch-intelligence');
    }

    $pricing_url = kingy_ali_public_url_value(kingy_ali_get_meta($post_id, 'pricing_url'));
    if ($pricing_url) {
        return __('Pricing page available', 'kingy-ai-launch-intelligence');
    }

    return __('Pricing unknown', 'kingy-ai-launch-intelligence');
}

function kingy_ali_command_center_source_status($post_id) {
    $verification = function_exists('kingy_ali_verification_label') ? kingy_ali_verification_label($post_id) : kingy_ali_public_profile_meta_text($post_id, 'verification_status', __('Needs verification', 'kingy-ai-launch-intelligence'));
    $source_count = function_exists('kingy_ali_source_count') ? kingy_ali_source_count($post_id) : 0;

    return sprintf(
        _n('%1$s; %2$d source', '%1$s; %2$d sources', $source_count, 'kingy-ai-launch-intelligence'),
        $verification,
        $source_count
    );
}

function kingy_ali_command_center_launch_cta_url($post_id) {
    return get_permalink($post_id);
}

function kingy_ali_command_center_related_coverage_url($post_id) {
    $post_id = absint($post_id);
    $canonical_url = $post_id ? get_permalink($post_id) : '';
    if (!$canonical_url) {
        return '';
    }

    $candidates = array();
    $legacy_url = kingy_ali_sanitize_public_cta_url(kingy_ali_get_meta($post_id, 'related_article_url'));
    if ($legacy_url) {
        $candidates[] = $legacy_url;
    }
    $candidates = array_merge($candidates, array_keys(kingy_ali_related_editorial_urls_for_launch($post_id)));

    foreach (array_unique($candidates) as $candidate_url) {
        $article_id = url_to_postid($candidate_url);
        if (
            !$article_id
            || get_post_type($article_id) !== 'post'
            || get_post_status($article_id) !== 'publish'
        ) {
            continue;
        }

        $coverage_url = get_permalink($article_id);
        if (
            $coverage_url
            && untrailingslashit($coverage_url) !== untrailingslashit($canonical_url)
        ) {
            return $coverage_url;
        }
    }

    return '';
}

function kingy_ali_render_command_center_launch_card($post_id) {
    $summary = kingy_ali_public_profile_meta_text($post_id, 'what_launched', get_the_excerpt($post_id));
    $why = kingy_ali_public_profile_meta_text($post_id, 'kingy_verdict');
    if ($why === '') {
        $why = kingy_ali_public_profile_meta_text($post_id, 'what_feels_promising');
    }
    if ($why === '') {
        $why = __('Worth reviewing when the source record, demo, category, and pricing context are clearer.', 'kingy-ai-launch-intelligence');
    }
    $title = get_the_title($post_id);
    $related_coverage_url = kingy_ali_command_center_related_coverage_url($post_id);

    ob_start();
    ?>
    <article class="kingy-ali-command-launch-card">
        <div class="kingy-ali-card__meta">
            <span><?php echo esc_html(kingy_ali_command_center_category_label($post_id)); ?></span>
            <?php $launch_date_label = kingy_ali_command_center_launch_date_label($post_id); ?>
            <?php if ($launch_date_label) : ?>
                <time><?php echo esc_html($launch_date_label); ?></time>
            <?php endif; ?>
        </div>
        <h3><?php echo esc_html($title); ?></h3>
        <?php if ($summary) : ?>
            <p><strong><?php esc_html_e('Summary:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html(wp_trim_words($summary, 26)); ?></p>
        <?php endif; ?>
        <p><strong><?php esc_html_e('Why it matters:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html(wp_trim_words($why, 24)); ?></p>
        <dl class="kingy-ali-command-facts">
            <div><dt><?php esc_html_e('Pricing', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_command_center_pricing_status($post_id)); ?></dd></div>
            <div><dt><?php esc_html_e('Verification', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html(kingy_ali_command_center_source_status($post_id)); ?></dd></div>
        </dl>
        <div class="kingy-ali-card__actions">
            <a aria-label="<?php echo esc_attr(sprintf(__('Open full record: %s', 'kingy-ai-launch-intelligence'), $title)); ?>" data-kingy-ali-track="clicked_launch" data-object-id="<?php echo esc_attr($post_id); ?>" data-event-surface="command_center_today" href="<?php echo esc_url(kingy_ali_command_center_launch_cta_url($post_id)); ?>"><?php esc_html_e('Open full record', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($related_coverage_url) : ?>
                <a aria-label="<?php echo esc_attr(sprintf(__('Read related coverage: %s', 'kingy-ai-launch-intelligence'), $title)); ?>" data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Read related coverage', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="command_center_today" href="<?php echo esc_url($related_coverage_url); ?>"><?php esc_html_e('Read related coverage', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_command_center_weekly_awards() {
    $latest = kingy_ali_latest_launches_of_week_edition_cta();
    $awards = array(
        __('Best overall AI launch', 'kingy-ai-launch-intelligence'),
        __('Best AI agent launch', 'kingy-ai-launch-intelligence'),
        __('Best AI coding tool launch', 'kingy-ai-launch-intelligence'),
        __('Best AI video tool launch', 'kingy-ai-launch-intelligence'),
        __('Best open-weight model launch', 'kingy-ai-launch-intelligence'),
        __('Best founder-submitted launch', 'kingy-ai-launch-intelligence'),
        __('Best demo', 'kingy-ai-launch-intelligence'),
        __('Best pricing clarity', 'kingy-ai-launch-intelligence'),
        __('Most under-the-radar launch', 'kingy-ai-launch-intelligence'),
        __('Best creator coverage fit', 'kingy-ai-launch-intelligence'),
    );

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-command-awards">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Weekly awards', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('This Week\'s Strongest AI Launches', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('The weekly awards edition is editorial, source-aware, and designed to separate useful launch signal from announcement noise.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <article class="kingy-ali-radar-card">
            <div>
                <p class="kingy-ali-card__meta"><span><?php esc_html_e('Latest edition', 'kingy-ai-launch-intelligence'); ?></span><?php echo esc_html($latest['date']); ?></p>
                <h3><a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($latest['title']); ?>" data-event-surface="command_center_awards" href="<?php echo esc_url($latest['url']); ?>"><?php echo esc_html($latest['title']); ?></a></h3>
                <p><?php echo esc_html(wp_trim_words($latest['summary'], 30)); ?></p>
            </div>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($latest['short_label']); ?>" data-event-surface="command_center_awards" href="<?php echo esc_url($latest['url']); ?>"><?php echo esc_html($latest['short_label']); ?></a>
        </article>
        <div class="kingy-ali-award-grid">
            <?php foreach ($awards as $award) : ?>
                <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($award); ?>" data-event-surface="command_center_awards" href="<?php echo esc_url($latest['url']); ?>"><?php echo esc_html($award); ?></a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_command_center_categories() {
    $hub = home_url('/ai-launches/');
    return array(
        array('label' => __('Editorial Launch Coverage', 'kingy-ai-launch-intelligence'), 'url' => kingy_ali_launch_coverage_archive_url(), 'description' => __('Daily launch reporting, explainers, analysis, and launch roundups from Kingy AI.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Agents', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-agents/'), 'description' => __('Agent platforms, workflow agents, browser agents, and task automation launches.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Coding Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/'), 'description' => __('Code agents, IDE assistants, repo tools, and developer workflow launches.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Video Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-video-tools/'), 'description' => __('Video generation, editing, avatars, demos, and creator workflow launches.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI App Builders / Vibe Coding', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-app-builders/'), 'description' => __('Prompt-to-app builders, no-code AI builders, and software-building workflows.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Search and Research Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-search-research-tools/'), 'description' => __('Research assistants, retrieval, citation, search, and source workflow products.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Image Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-image-tools/'), 'description' => __('Image generation, design, editing, and visual workflow launches.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Voice / Audio Tools', 'kingy-ai-launch-intelligence'), 'url' => add_query_arg('kali_category', 'ai-voice-audio-tools', $hub), 'description' => __('Voice generation, audio editing, music, speech, and podcast workflow launches.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Open-Weight Models', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/open-weight-models/'), 'description' => __('Open-weight model releases with license, source, and access context.', 'kingy-ai-launch-intelligence')),
        array('label' => __('AI Funding Announcements', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/funding-announcements/'), 'description' => __('Funding news connected to launch, hiring, and go-to-market signal.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Founder-Submitted Tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/founder-submitted-ai-tools/'), 'description' => __('Founder-submitted launch records and leads waiting for editorial review.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Creator Coverage Candidates', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/creator-coverage-ai-launches/'), 'description' => __('Launches with visible demo, explanation, tutorial, or YouTube review potential.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_render_command_center_category_navigation() {
    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-command-categories">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Category navigation', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Find AI launches by market, workflow, and source signal', 'kingy-ai-launch-intelligence'); ?></h2>
        </div>
        <div class="kingy-ali-tile-grid">
            <?php foreach (kingy_ali_command_center_categories() as $category) : ?>
                <a class="kingy-ali-tile" data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($category['label']); ?>" data-event-surface="command_center_category_nav" href="<?php echo esc_url($category['url']); ?>">
                    <strong><?php echo esc_html($category['label']); ?></strong>
                    <span><?php echo esc_html($category['description']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_newsletter_segments() {
    return array(
        __('Models', 'kingy-ai-launch-intelligence'),
        __('Agents & harnesses', 'kingy-ai-launch-intelligence'),
        __('Developer infrastructure', 'kingy-ai-launch-intelligence'),
        __('Products', 'kingy-ai-launch-intelligence'),
        __('Funding & company moves', 'kingy-ai-launch-intelligence'),
        __('Pricing watch', 'kingy-ai-launch-intelligence'),
        __('Under the radar', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_render_launch_brief_inline_signup() {
    if (!function_exists('kingy_brief_signup_markup') || !empty($GLOBALS['kingy_brief_signup_rendered'])) {
        return '';
    }

    return kingy_brief_signup_markup('compact', 'launches_inline');
}

function kingy_ali_render_launch_intelligence_newsletter_module($surface = 'launch_command_center') {
    $surface = sanitize_key($surface);
    $surface = $surface ? $surface : 'launch_command_center';

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-newsletter-module">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Newsletter', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Inside The Kingy Launch Brief', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('One concise Friday newsletter covering verified models, agents and harnesses, developer infrastructure, products, funding and company moves, pricing changes, and one under-the-radar launch.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-segment-list" aria-label="<?php esc_attr_e('Coverage inside The Kingy Launch Brief', 'kingy-ai-launch-intelligence'); ?>">
            <?php foreach (kingy_ali_newsletter_segments() as $segment) : ?>
                <span><?php echo esc_html($segment); ?></span>
            <?php endforeach; ?>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_newsletter_cta" data-event-label="<?php esc_attr_e('Get The Kingy Launch Brief', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/subscribe/')); ?>"><?php esc_html_e('Get The Kingy Launch Brief', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse launch archive from newsletter module', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse launch archive', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_founder_submission_path($surface = 'launch_command_center') {
    $surface = sanitize_key($surface);
    $surface = $surface ? $surface : 'launch_command_center';
    $fields = array(
        __('Product name', 'kingy-ai-launch-intelligence'),
        __('Website', 'kingy-ai-launch-intelligence'),
        __('Launch date', 'kingy-ai-launch-intelligence'),
        __('What launched', 'kingy-ai-launch-intelligence'),
        __('Official source links', 'kingy-ai-launch-intelligence'),
        __('Pricing page', 'kingy-ai-launch-intelligence'),
        __('Demo/video', 'kingy-ai-launch-intelligence'),
        __('Company info', 'kingy-ai-launch-intelligence'),
        __('Category', 'kingy-ai-launch-intelligence'),
        __('Public funding info', 'kingy-ai-launch-intelligence'),
        __('Screenshots/media', 'kingy-ai-launch-intelligence'),
    );

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-founder-path">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Founder submission', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Submit Your AI Launch', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Founders can send a launch for editorial review when the public source trail is ready to verify.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-content-grid">
            <article class="kingy-ali-text-panel">
                <h3><?php esc_html_e('What to include', 'kingy-ai-launch-intelligence'); ?></h3>
                <ul class="kingy-ali-clean-list">
                    <?php foreach ($fields as $field) : ?>
                        <li><?php echo esc_html($field); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="kingy-ali-text-panel">
                <h3><?php esc_html_e('Privacy note', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Do not submit secrets, unreleased financials, private customer data, or regulated personal data.', 'kingy-ai-launch-intelligence'); ?></p>
                <p><?php esc_html_e('Submit only public product, company, source, pricing, demo, and media details that Kingy AI can review without logging into private systems.', 'kingy-ai-launch-intelligence'); ?></p>
            </article>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit Your AI Launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit Your AI Launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Score launch readiness before submission', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launch-scorecard/')); ?>"><?php esc_html_e('Score launch readiness', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_sponsor_path($surface = 'launch_command_center') {
    $surface = sanitize_key($surface);
    $surface = $surface ? $surface : 'launch_command_center';
    $client_examples_url = kingy_ali_client_examples_url();

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-sponsor-path">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Sponsor path', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Sponsor Kingy AI launch coverage', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Launching an AI product that needs clear demos, creator education, and buyer trust? Sponsor a Kingy AI video or launch feature.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <?php if ($client_examples_url) : ?>
                <a data-kingy-ali-track="clicked_client_examples_cta" data-event-label="<?php esc_attr_e('See client examples', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url($client_examples_url); ?>"><?php esc_html_e('See client examples', 'kingy-ai-launch-intelligence'); ?></a>
            <?php endif; ?>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Use ROI calculator from sponsor path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate sponsor ROI', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_hub_tiles() {
    $tiles = array(
        array('label' => __('Today\'s AI Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Fresh records worth checking now.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for daily tracking.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/today/')),
        array('label' => __('This Week\'s Most Important AI Launches', 'kingy-ai-launch-intelligence'), 'description' => __('A weekly scan of launches with stronger signals.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for trend review.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/this-week/')),
        array('label' => __('Kingy AI Launches of the Week', 'kingy-ai-launch-intelligence'), 'description' => __('Editorial weekly awards for standout AI launches.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for recognition and market context.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/launches-of-the-week/')),
        array_merge(kingy_ali_latest_launches_of_week_edition_link('label'), array('description' => __('Latest Kingy AI weekly launch awards edition.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for the latest editorial picks.', 'kingy-ai-launch-intelligence'))),
        array('label' => __('AI Agent Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Agents, browser agents, workflow agents, and platforms.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for autonomy claims.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-agents/')),
        array('label' => __('AI App Builder and Vibe Coding Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Prompt-to-app tools and software-building workflows.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for builder comparisons.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-app-builders/')),
        array('label' => __('AI Coding Tool Launches', 'kingy-ai-launch-intelligence'), 'description' => __('IDE agents, code assistants, repo tools, and PR workflows.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for developer workflow.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-coding-tools/')),
        array('label' => __('AI Video Tool Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Video generation, editing, avatars, demos, and creator tools.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for YouTube review value.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-video-tools/')),
        array('label' => __('AI Image Tool Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Image generation, editing, design, and visual workflows.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for creative testing.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-image-tools/')),
        array('label' => __('AI Search and Research Tool Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Retrieval, citations, research assistants, and source workflows.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for trust checks.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/ai-search-research-tools/')),
        array('label' => __('AI Open-Weight Model Launches', 'kingy-ai-launch-intelligence'), 'description' => __('Models with downloadable or inspectable release artifacts.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for license caution.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/open-weight-models/')),
        array('label' => __('AI Funding Announcements', 'kingy-ai-launch-intelligence'), 'description' => __('Funding news connected to launch and market context.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful as signal, not proof.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/funding-announcements/')),
        array('label' => __('AI Launch Scorecard', 'kingy-ai-launch-intelligence'), 'description' => __('A 100-point launch readiness tool for AI founders.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful before Product Hunt, SEO, and creator outreach.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launch-scorecard/')),
        array('label' => __('Creator Coverage Potential', 'kingy-ai-launch-intelligence'), 'description' => __('Launches that may support demos, explainers, and reviews.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for creator planning.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/creator-coverage-ai-launches/')),
        array('label' => __('Founder-Submitted Tools', 'kingy-ai-launch-intelligence'), 'description' => __('Founder-submitted leads awaiting editorial review.', 'kingy-ai-launch-intelligence'), 'why' => __('Useful for intake follow-up.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-launches/founder-submitted-ai-tools/')),
    );

    ob_start();
    echo '<div class="kingy-ali-tile-grid">';
    foreach ($tiles as $tile) {
        echo '<a class="kingy-ali-tile" data-kingy-ali-track="clicked_category_path" data-event-label="' . esc_attr($tile['label']) . '" data-event-surface="hub_tile" href="' . esc_url($tile['url']) . '">';
        echo '<strong>' . esc_html($tile['label']) . '</strong>';
        echo '<span>' . esc_html($tile['description']) . '</span>';
        echo '<em>' . esc_html($tile['why']) . '</em>';
        echo '</a>';
    }
    echo '</div>';

    return ob_get_clean();
}

function kingy_ali_render_hub_ctas() {
    ob_start();
    ?>
    <div class="kingy-ali-cta-row">
        <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit an AI launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="hub_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit an AI launch', 'kingy-ai-launch-intelligence'); ?></a>
        <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Open Launches of the Week', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="hub_cta" href="<?php echo esc_url(home_url('/ai-launches/launches-of-the-week/')); ?>"><?php esc_html_e('Launches of the Week', 'kingy-ai-launch-intelligence'); ?></a>
        <?php $latest_launches_of_week_cta = kingy_ali_latest_launches_of_week_edition_cta(); ?>
        <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php echo esc_attr($latest_launches_of_week_cta['label']); ?>" data-event-surface="hub_cta" href="<?php echo esc_url($latest_launches_of_week_cta['url']); ?>"><?php echo esc_html($latest_launches_of_week_cta['short_label']); ?></a>
        <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Score AI launch readiness', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="hub_cta" href="<?php echo esc_url(home_url('/ai-launch-scorecard/')); ?>"><?php esc_html_e('Score launch readiness', 'kingy-ai-launch-intelligence'); ?></a>
        <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="hub_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
        <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="hub_cta" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_company_visibility_path($surface = 'launch_intelligence') {
    $surface = sanitize_key($surface);
    if ($surface === '') {
        $surface = 'launch_intelligence';
    }

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-company-path">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('For AI companies', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Turn a launch into source-backed visibility', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Kingy AI uses launch records, tool profiles, Daily Launch Radar coverage, creator-fit signals, and ROI tools to help AI companies move from announcement to useful discovery.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit launch from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit a launch for review', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('View Launches of the Week from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/launches-of-the-week/')); ?>"><?php esc_html_e('Launches of the Week', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Score readiness from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launch-scorecard/')); ?>"><?php esc_html_e('Score launch readiness', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Check visibility from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Check launch visibility', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator ROI from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from company path', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="<?php echo esc_attr($surface); ?>" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_hub_methodology() {
    $steps = array(
        array(
            'title' => __('Kingy score', 'kingy-ai-launch-intelligence'),
            'text' => __('A directional editorial score for launch clarity, source quality, audience fit, pricing visibility, demo usefulness, and practical buyer or creator value.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Demo score', 'kingy-ai-launch-intelligence'),
            'text' => __('A signal for whether the product can be shown, tested, explained, or compared without relying on vague announcement copy.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('YouTube potential', 'kingy-ai-launch-intelligence'),
            'text' => __('A creator-fit signal for demos, tutorials, before-and-after workflows, explainers, reviews, and audience-specific product education.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Source verification', 'kingy-ai-launch-intelligence'),
            'text' => __('Records distinguish verified, needs verification, and founder submitted status using official URLs, source links, last-verified dates, and correction paths.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Pricing clarity', 'kingy-ai-launch-intelligence'),
            'text' => __('Pricing signals favor launches with visible pricing, free-plan status, pricing pages, API access notes, or clear uncertainty when pricing is not public.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Use-case clarity', 'kingy-ai-launch-intelligence'),
            'text' => __('Launches are easier to evaluate when the audience, workflow, category, demo, and alternative comparison are obvious from public sources.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Founder and sponsor fit', 'kingy-ai-launch-intelligence'),
            'text' => __('Founder submissions and sponsor interest are routing signals only; editorial review still checks source quality, public claims, demo clarity, and usefulness.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Verification labels', 'kingy-ai-launch-intelligence'),
            'text' => __('Verified means enough public source evidence is present, needs verification means the record needs more checking, and founder submitted means the entry came through the submission path.', 'kingy-ai-launch-intelligence'),
        ),
    );

    ob_start();
    ?>
    <section class="kingy-ali-content-band kingy-ali-methodology">
        <div class="kingy-ali-section-heading">
            <p class="kingy-ali-kicker"><?php esc_html_e('Methodology', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('How Kingy AI Scores Launches', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Kingy AI scoring is designed for launch discovery, editorial review, creator planning, and buyer trust checks.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>
        <div class="kingy-ali-content-grid">
            <?php foreach ($steps as $step) : ?>
                <article class="kingy-ali-text-panel">
                    <h3><?php echo esc_html($step['title']); ?></h3>
                    <p><?php echo esc_html($step['text']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="kingy-ali-policy-note"><?php esc_html_e('Scores are editorial signals, not scientific benchmarks, paid placements, or guarantees.', 'kingy-ai-launch-intelligence'); ?></p>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_visibility_score() {
    kingy_ali_enqueue_assets();

    if (kingy_ali_shortcode_request_value('kingy_visibility_score_lead', 10) === '1') {
        return kingy_ali_render_visibility_score_success();
    }

    $selected_interest = kingy_ali_normalize_visibility_interest(kingy_ali_shortcode_request_value('kingy_interest', 40));

    $heading_tag = kingy_ali_current_page_path_is('ai-launches/launch-visibility-score') ? 'h2' : 'h1';

    ob_start();
    ?>
    <form class="kingy-ali-calculator" data-kingy-ali-calculator method="post">
        <<?php echo tag_escape($heading_tag); ?>><?php esc_html_e('AI Launch Visibility Score Calculator', 'kingy-ai-launch-intelligence'); ?></<?php echo tag_escape($heading_tag); ?>>
        <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_score_methodology_note()); ?></p>
        <?php wp_nonce_field('kingy_ali_visibility_score_lead', 'kingy_ali_visibility_score_lead_nonce'); ?>
        <input type="hidden" name="kingy_ali_action" value="visibility_score_lead">
        <label class="kingy-ali-hp" aria-hidden="true">
            <span><?php esc_html_e('Leave this field empty', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="text" name="kingy_ali_company_site">
        </label>
        <div class="kingy-ali-calculator__grid">
            <?php
            $fields = kingy_ali_visibility_score_labels();
            foreach ($fields as $key => $label) :
                ?>
                <label>
                    <span><?php echo esc_html($label); ?></span>
                    <select name="kingy_ali_visibility_scores[<?php echo esc_attr($key); ?>]" data-score-input="<?php echo esc_attr($key); ?>">
                        <option value="0"><?php esc_html_e('Missing', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="0.5"><?php esc_html_e('Partial', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="1"><?php esc_html_e('Strong', 'kingy-ai-launch-intelligence'); ?></option>
                    </select>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="kingy-ali-score-result" aria-live="polite">
            <strong><?php esc_html_e('Your Launch Visibility Score:', 'kingy-ai-launch-intelligence'); ?> <span data-score-output>0</span> / 100</strong>
            <span class="kingy-ali-score-band" data-score-band><?php esc_html_e('Needs work', 'kingy-ai-launch-intelligence'); ?></span>
            <div data-score-recommendations></div>
        </div>
        <div class="kingy-ali-lead-panel">
            <h3><?php esc_html_e('Want Kingy AI to review it?', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-form-grid">
                <label>
                    <span><?php esc_html_e('Product name', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="text" name="kingy_ali_visibility_lead[product_name]" required>
                </label>
                <label>
                    <span><?php esc_html_e('Founder/contact name', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="text" name="kingy_ali_visibility_lead[contact_name]">
                </label>
                <label>
                    <span><?php esc_html_e('Email', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="email" name="kingy_ali_visibility_lead[email]" required>
                </label>
                <label>
                    <span><?php esc_html_e('Official URL', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="url" name="kingy_ali_visibility_lead[official_url]">
                </label>
                <label>
                    <span><?php esc_html_e('Review interest', 'kingy-ai-launch-intelligence'); ?></span>
                    <select name="kingy_ali_visibility_lead[interest]">
                        <option value="visibility_score"<?php selected($selected_interest, 'visibility_score'); ?>><?php esc_html_e('Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="creator_coverage"<?php selected($selected_interest, 'creator_coverage'); ?>><?php esc_html_e('Creator coverage fit', 'kingy-ai-launch-intelligence'); ?></option>
                        <option value="creator_campaign"<?php selected($selected_interest, 'creator_campaign'); ?>><?php esc_html_e('Creator campaign review', 'kingy-ai-launch-intelligence'); ?></option>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Notes', 'kingy-ai-launch-intelligence'); ?></span>
                    <textarea name="kingy_ali_visibility_lead[notes]" rows="3"></textarea>
                </label>
            </div>
            <button type="submit"><?php esc_html_e('Request review', 'kingy-ai-launch-intelligence'); ?></button>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit to Launch Intelligence', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_cta" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit to Launch Intelligence', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Request creator campaign review from visibility score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign')); ?>"><?php esc_html_e('Request creator campaign review', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI from visibility score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_cta" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from visibility score', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_cta" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Check another launch', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_cta" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/')); ?>"><?php esc_html_e('Check another launch', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_render_visibility_score_success() {
    ob_start();
    ?>
    <div class="kingy-ali-success">
        <h2><?php esc_html_e('Thanks — your review request has been sent.', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Kingy AI can use the score details, product URL, and notes to prioritize launch visibility, creator coverage, and creator campaign follow-up.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit full launch after visibility lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_success" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit the full launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Request creator campaign review after visibility lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_success" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign')); ?>"><?php esc_html_e('Request creator campaign review', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_roi_calculator" data-event-label="<?php esc_attr_e('Estimate creator campaign ROI after visibility lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_success" href="<?php echo esc_url(home_url('/ai-sponsored-video-roi-calculator/')); ?>"><?php esc_html_e('Estimate creator campaign ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI after visibility lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_success" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse launches after visibility lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="visibility_score_success" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_ai_courses_hub() {
    kingy_ali_enqueue_assets();

    $courses = array(
        array(
            'title' => __('OpenAI Codex Course for Beginners', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Coding Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Build apps without coding by learning scoped prompts, review loops, GitHub basics, and safe shipping habits.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-course-for-beginners/'),
            'title_links' => array(
                'OpenAI' => home_url('/ai-companies/openai/'),
                'Codex' => home_url('/ai-tools/codex/'),
            ),
            'summary_links' => array(
                'GitHub' => home_url('/ai-companies/github/'),
            ),
        ),
        array(
            'title' => __('Codex Zero to Hero', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Coding Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Go deeper on Codex, Git, GitHub, Vercel, AI coding agents, and real-world software shipping.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/codex-zero-to-hero/'),
        ),
        array(
            'title' => __('Microsoft Copilot Zero to Hero', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Business Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Learn Copilot Chat, Microsoft 365 workflows, prompting, agents, Copilot Studio, governance, and adoption.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/microsoft-copilot-course/'),
            'title_links' => array(
                'Microsoft' => home_url('/ai-companies/microsoft/'),
            ),
        ),
        array(
            'title' => __('Vibe Coding for Beginners', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Builder Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Plan a small AI-assisted app, choose the right builder, copy safer prompts, and test before launch.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/vibe-coding-for-beginners-ai-app-builder/'),
        ),
        array(
            'title' => __('Replit for Beginners', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Beginner Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Turn one small idea into a working Replit app while learning files, debugging, QA, and publish checks.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/replit-for-beginners-ai-apps/'),
            'title_links' => array(
                'Replit' => home_url('/ai-companies/replit/'),
            ),
        ),
        array(
            'title' => __('AI Search Visibility Course', 'kingy-ai-launch-intelligence'),
            'track' => __('AI Founder Track', 'kingy-ai-launch-intelligence'),
            'summary' => __('Understand how AI search surfaces products and how to improve useful, source-backed visibility.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/ai-search-visibility-course-for-beginners/'),
        ),
    );

    $tracks = array();
    foreach ($courses as $course) {
        $track = isset($course['track']) ? (string) $course['track'] : __('AI Courses', 'kingy-ai-launch-intelligence');
        if (!isset($tracks[$track])) {
            $tracks[$track] = array();
        }
        $tracks[$track][] = $course;
    }

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-courses-hub" data-kingy-ai-courses-hub>
        <header class="kingy-ali-academy-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Kingy AI learning paths', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Kingy AI Academy starting point', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Practical courses and guides for learning AI tools, AI coding workflows, Copilot, search visibility, and beginner-safe app building. This is a clean course hub, not a claim that every future academy track is fully built yet.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </header>
        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-course-track-grid">
                <?php foreach ($tracks as $track => $track_courses) : ?>
                    <section class="kingy-ali-course-track">
                        <h3><?php echo esc_html($track); ?></h3>
                        <div class="kingy-ali-practical-grid">
                            <?php foreach ($track_courses as $course) : ?>
                                <article class="kingy-ali-practical-card">
                                    <h4><?php echo kingy_ali_render_course_hub_linked_text($course['title'], isset($course['title_links']) ? $course['title_links'] : array()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h4>
                                    <p><?php echo kingy_ali_render_course_hub_linked_text($course['summary'], isset($course['summary_links']) ? $course['summary_links'] : array()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                    <p><a href="<?php echo esc_url($course['url']); ?>"><?php esc_html_e('Open course', 'kingy-ai-launch-intelligence'); ?></a></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="kingy-ali-content-band">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Keep exploring', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Use the Academy with Kingy AI intelligence hubs', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Courses are most useful when they connect to real AI tools, launch examples, company profiles, and practical product discovery.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-cta-row">
                <a href="<?php echo esc_url(home_url('/ai-tools/')); ?>"><?php esc_html_e('Explore AI Tools', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI Launches', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/subscribe/')); ?>"><?php esc_html_e('Join Launch Radar', 'kingy-ai-launch-intelligence'); ?></a>
                <a href="<?php echo esc_url(home_url('/sponsor-kingy-ai/')); ?>"><?php esc_html_e('Sponsor Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}


function kingy_ali_render_course_hub_linked_text($text, $links = array()) {
    $html = esc_html($text);
    if (!is_array($links) || empty($links)) {
        return $html;
    }

    foreach ($links as $anchor => $url) {
        $anchor = (string) $anchor;
        $url = (string) $url;
        if ($anchor === '' || $url === '') {
            continue;
        }

        $pattern = '/(?<![A-Za-z0-9])' . preg_quote(esc_html($anchor), '/') . '(?![A-Za-z0-9])/';
        $replacement = '<a href="' . esc_url($url) . '">' . esc_html($anchor) . '</a>';
        $html = preg_replace($pattern, $replacement, $html, 1);
    }

    return wp_kses(
        $html,
        array(
            'a' => array(
                'href' => array(),
            ),
        )
    );
}

function kingy_ali_shortcode_sponsorship_roi_calculator($atts = array()) {
    kingy_ali_enqueue_assets();

    $atts = shortcode_atts(
        array(
            'angle' => '',
        ),
        is_array($atts) ? $atts : array(),
        'kingy_creator_campaign_roi_calculator'
    );

    $angle = sanitize_key($atts['angle']);
    $angle_context = kingy_ali_sponsorship_roi_angle_context($angle);
    $heading_tag = (
        kingy_ali_current_page_path_is('ai-sponsored-video-roi-calculator')
        || kingy_ali_current_page_path_is('ai-launches/creator-campaign-roi-calculator')
    ) ? 'h2' : 'h1';

    if (
        kingy_ali_shortcode_request_value('kingy_creator_campaign_roi_lead', 10) === '1'
        || kingy_ali_shortcode_request_value('kingy_sponsor_roi_lead', 10) === '1'
    ) {
        return kingy_ali_render_sponsorship_roi_success();
    }

    ob_start();
    ?>
    <form class="kingy-ali-calculator kingy-ali-roi-calculator" data-kingy-ali-creator-campaign-roi method="post">
        <div class="kingy-ali-roi-hero">
            <p class="kingy-ali-kicker"><?php esc_html_e('Free calculator - no signup required', 'kingy-ai-launch-intelligence'); ?></p>
            <<?php echo tag_escape($heading_tag); ?>><?php echo esc_html($angle_context['heading']); ?></<?php echo tag_escape($heading_tag); ?>>
            <p><?php echo esc_html($angle_context['lede']); ?></p>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
            <div class="kingy-ali-roi-proof-row" aria-label="<?php esc_attr_e('Calculator trust signals', 'kingy-ai-launch-intelligence'); ?>">
                <span><?php esc_html_e('60-second mode', 'kingy-ai-launch-intelligence'); ?></span>
                <span><?php esc_html_e('Editable AI-company presets', 'kingy-ai-launch-intelligence'); ?></span>
                <span><?php esc_html_e('Shareable URL and CSV export', 'kingy-ai-launch-intelligence'); ?></span>
            </div>
        </div>
        <?php wp_nonce_field('kingy_ali_creator_campaign_roi_lead', 'kingy_ali_creator_campaign_roi_lead_nonce'); ?>
        <input type="hidden" name="kingy_ali_action" value="creator_campaign_roi_lead">
        <label class="kingy-ali-hp" aria-hidden="true">
            <span><?php esc_html_e('Leave this field empty', 'kingy-ai-launch-intelligence'); ?></span>
            <input type="text" name="kingy_ali_creator_campaign_company_site">
        </label>

        <section class="kingy-ali-roi-quick" aria-labelledby="kingy-ali-roi-quick-heading">
            <div>
                <h2 id="kingy-ali-roi-quick-heading"><?php esc_html_e('60-second sponsorship ROI model', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Start with the six numbers that decide whether a sponsored YouTube video can clear CAC, payback, and break-even pressure.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-roi-actions">
                <button type="button" data-creator-campaign-roi-share><?php esc_html_e('Copy share link', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-creator-campaign-roi-csv><?php esc_html_e('Export CSV', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-creator-campaign-roi-send><?php esc_html_e('Send me this estimate', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
            <p class="kingy-ali-roi-status" data-creator-campaign-roi-status aria-live="polite"></p>
        </section>

        <div class="kingy-ali-roi-presets" aria-label="<?php esc_attr_e('Editable AI sponsorship presets', 'kingy-ai-launch-intelligence'); ?>">
            <button type="button" data-creator-campaign-roi-preset data-expected-views="90000" data-click-through-rate="1.4" data-conversion-rate="4.5" data-value-per-conversion="300" data-creator-campaign-cost="7000">
                <strong><?php esc_html_e('Prosumer AI app', 'kingy-ai-launch-intelligence'); ?></strong>
                <span><?php esc_html_e('Lower price, volume-sensitive, needs a strong offer.', 'kingy-ai-launch-intelligence'); ?></span>
            </button>
            <button type="button" data-creator-campaign-roi-preset data-expected-views="70000" data-click-through-rate="1.8" data-conversion-rate="6" data-value-per-conversion="650" data-creator-campaign-cost="9500">
                <strong><?php esc_html_e('Developer tool', 'kingy-ai-launch-intelligence'); ?></strong>
                <span><?php esc_html_e('Demo-led audience with higher trial intent.', 'kingy-ai-launch-intelligence'); ?></span>
            </button>
            <button type="button" data-creator-campaign-roi-preset data-expected-views="50000" data-click-through-rate="1.2" data-conversion-rate="5" data-value-per-conversion="1800" data-creator-campaign-cost="12000">
                <strong><?php esc_html_e('B2B AI SaaS', 'kingy-ai-launch-intelligence'); ?></strong>
                <span><?php esc_html_e('Higher LTV can support slower payback.', 'kingy-ai-launch-intelligence'); ?></span>
            </button>
            <button type="button" data-creator-campaign-roi-preset data-expected-views="35000" data-click-through-rate="0.8" data-conversion-rate="3.5" data-value-per-conversion="8000" data-creator-campaign-cost="18000">
                <strong><?php esc_html_e('High-ticket platform', 'kingy-ai-launch-intelligence'); ?></strong>
                <span><?php esc_html_e('Few customers can justify a premium walkthrough.', 'kingy-ai-launch-intelligence'); ?></span>
            </button>
            <button type="button" data-creator-campaign-roi-preset data-expected-views="160000" data-click-through-rate="1.1" data-conversion-rate="3" data-value-per-conversion="90" data-creator-campaign-cost="6000">
                <strong><?php esc_html_e('Consumer AI app', 'kingy-ai-launch-intelligence'); ?></strong>
                <span><?php esc_html_e('Requires reach, retention, and conversion volume.', 'kingy-ai-launch-intelligence'); ?></span>
            </button>
        </div>

        <p class="kingy-ali-roi-note"><?php esc_html_e('Preset values are editable planning examples, not promises or proprietary benchmarks. Use recent creator averages, your own funnel data, and conservative conversion assumptions.', 'kingy-ai-launch-intelligence'); ?></p>
        <p class="kingy-ali-roi-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>

        <div class="kingy-ali-calculator__grid kingy-ali-roi-input-grid">
            <?php foreach (kingy_ali_sponsor_roi_fields() as $key => $field) : ?>
                <?php $public_key = kingy_ali_creator_campaign_roi_public_field_key($key); ?>
                <label>
                    <span><?php echo esc_html($field['label']); ?></span>
                    <input
                        type="number"
                        name="kingy_ali_creator_campaign_roi[<?php echo esc_attr($public_key); ?>]"
                        value="<?php echo esc_attr((string) $field['default']); ?>"
                        min="<?php echo esc_attr((string) $field['min']); ?>"
                        max="<?php echo esc_attr((string) $field['max']); ?>"
                        step="<?php echo esc_attr((string) $field['step']); ?>"
                        data-creator-campaign-roi-input="<?php echo esc_attr($public_key); ?>"
                    >
                </label>
            <?php endforeach; ?>
        </div>
        <div class="kingy-ali-score-result kingy-ali-roi-result" aria-live="polite">
            <div class="kingy-ali-roi-result__primary">
                <strong><?php esc_html_e('Estimated ROI:', 'kingy-ai-launch-intelligence'); ?> <span data-creator-campaign-roi-output="roi">0%</span></strong>
                <span class="kingy-ali-score-band" data-creator-campaign-roi-output="band"><?php esc_html_e('Needs validation', 'kingy-ai-launch-intelligence'); ?></span>
            </div>
            <dl class="kingy-ali-roi-metrics">
                <div>
                    <dt><?php esc_html_e('Estimated clicks', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="clicks">0</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Estimated customers/leads', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="conversions">0</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Projected value', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="revenue">$0</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Projected profit', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="profit">$0</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Estimated CAC', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="cac">N/A</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Sponsorship CPM', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="cpm">N/A</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Break-even customers', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="breakeven">N/A</dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Next best action', 'kingy-ai-launch-intelligence'); ?></dt>
                    <dd data-creator-campaign-roi-output="next_action"><?php esc_html_e('Validate assumptions', 'kingy-ai-launch-intelligence'); ?></dd>
                </div>
            </dl>
        </div>

        <details class="kingy-ali-roi-details" open>
            <summary><?php esc_html_e('Creator deal evaluator', 'kingy-ai-launch-intelligence'); ?></summary>
            <div class="kingy-ali-roi-check-grid">
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Dedicated video"> <?php esc_html_e('Dedicated video or deep demo', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Integration"> <?php esc_html_e('Integration or sponsored segment', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Usage rights"> <?php esc_html_e('Usage rights for ads or sales clips', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Exclusivity"> <?php esc_html_e('Exclusivity period is priced clearly', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Pinned comment"> <?php esc_html_e('Pinned comment with tracked link', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Newsletter mention"> <?php esc_html_e('Newsletter or community mention', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Retargeting rights"> <?php esc_html_e('Retargeting audience rights', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Shorts"> <?php esc_html_e('Shorts or clipped follow-up assets', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-creator-campaign-roi-deal value="Follow-up integrations"> <?php esc_html_e('Follow-up integrations or renewal option', 'kingy-ai-launch-intelligence'); ?></label>
            </div>
            <p class="kingy-ali-roi-deal-readout" data-creator-campaign-roi-output="deal_readout"><?php esc_html_e('Add deal terms to see whether the package has enough reusable value beyond first-click attribution.', 'kingy-ai-launch-intelligence'); ?></p>
        </details>

        <details class="kingy-ali-roi-details">
            <summary><?php esc_html_e('What to track after launch', 'kingy-ai-launch-intelligence'); ?></summary>
            <ul class="kingy-ali-roi-tracking-list">
                <li><?php esc_html_e('UTM links for creator, video, format, and offer.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Promo code redemptions and checkout source notes.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Branded search lift before, during, and after publication.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Direct traffic and returning visitor changes.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Assisted conversions, CRM notes, and sales-call mentions.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Retargeting audience size and conversion rate.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Clicks, signups, and customers after day 30.', 'kingy-ai-launch-intelligence'); ?></li>
            </ul>
        </details>

        <section class="kingy-ali-roi-benchmark-panel" aria-labelledby="kingy-ali-roi-benchmark-heading">
            <h2 id="kingy-ali-roi-benchmark-heading"><?php esc_html_e('Why this calculator is built differently', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Strong ROI calculators in SaaS, ads, finance, and influencer marketing usually do three things well: they show the math, explain attribution limits, and make results easy to save or share. This model keeps the core acquisition math visible while reminding teams to validate creator-led demand with tracking data after launch.', 'kingy-ai-launch-intelligence'); ?></p>
        </section>

        <div class="kingy-ali-lead-panel">
            <h3><?php esc_html_e('Want Kingy AI to review the creator campaign fit?', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-form-grid">
                <label>
                    <span><?php esc_html_e('Company or product name', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="text" name="kingy_ali_creator_campaign_lead[company_name]" required>
                </label>
                <label>
                    <span><?php esc_html_e('Contact name', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="text" name="kingy_ali_creator_campaign_lead[contact_name]">
                </label>
                <label>
                    <span><?php esc_html_e('Email', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="email" name="kingy_ali_creator_campaign_lead[email]" required>
                </label>
                <label>
                    <span><?php esc_html_e('Official URL', 'kingy-ai-launch-intelligence'); ?></span>
                    <input type="url" name="kingy_ali_creator_campaign_lead[official_url]">
                </label>
                <label class="kingy-ali-field--textarea">
                    <span><?php esc_html_e('Campaign notes', 'kingy-ai-launch-intelligence'); ?></span>
                    <textarea name="kingy_ali_creator_campaign_lead[notes]" rows="3"></textarea>
                </label>
            </div>
            <button type="submit"><?php esc_html_e('Request creator campaign review', 'kingy-ai-launch-intelligence'); ?></button>
            <p class="kingy-ali-policy-note"><?php echo esc_html(kingy_ali_launch_data_privacy_note()); ?></p>
        </div>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_sponsorship_cta" data-event-label="<?php esc_attr_e('Submit launch for creator campaign review', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_calculator" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit launch details', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Check launch visibility first', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_calculator" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign')); ?>"><?php esc_html_e('Check launch visibility first', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI from creator campaign ROI calculator', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_calculator" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

function kingy_ali_sponsorship_roi_angle_context($angle) {
    $contexts = array(
        'youtube-sponsorship-roi' => array(
            'heading' => __('YouTube Sponsorship ROI Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'lede' => __('Estimate whether a sponsored YouTube video can produce enough clicks, signups, customers, CAC, and payback value to justify the creator fee.', 'kingy-ai-launch-intelligence'),
        ),
        'influencer-marketing-cac' => array(
            'heading' => __('Influencer Marketing CAC Calculator for AI Companies', 'kingy-ai-launch-intelligence'),
            'lede' => __('Turn creator reach, click-through rate, conversion rate, and customer value into a clear CAC and break-even readout.', 'kingy-ai-launch-intelligence'),
        ),
        'creator-sponsorship-payback' => array(
            'heading' => __('Creator Sponsorship Payback Calculator for AI Products', 'kingy-ai-launch-intelligence'),
            'lede' => __('Model how many customers, clicks, and conversions a creator campaign needs before it pays back the sponsorship cost.', 'kingy-ai-launch-intelligence'),
        ),
        'ai-product-sponsorship' => array(
            'heading' => __('AI Product Sponsorship Calculator', 'kingy-ai-launch-intelligence'),
            'lede' => __('Plan creator-led distribution for an AI product with editable assumptions for views, CTR, conversion rate, customer value, and campaign cost.', 'kingy-ai-launch-intelligence'),
        ),
        'youtube-sponsorship-rate-vs-roi' => array(
            'heading' => __('YouTube Sponsorship Rate vs ROI Calculator', 'kingy-ai-launch-intelligence'),
            'lede' => __('Compare a proposed creator rate against expected funnel value, break-even customers, CAC, CPM, and practical next steps.', 'kingy-ai-launch-intelligence'),
        ),
    );

    if (isset($contexts[$angle])) {
        return $contexts[$angle];
    }

    return $contexts['youtube-sponsorship-roi'];
}

function kingy_ali_shortcode_sponsorship_roi_comparison_page($atts = array()) {
    return kingy_ali_shortcode_sponsorship_roi_calculator($atts);
}

function kingy_ali_render_sponsorship_roi_success() {
    ob_start();
    ?>
    <div class="kingy-ali-success">
        <h2><?php esc_html_e('Thanks - your creator campaign review request has been sent.', 'kingy-ai-launch-intelligence'); ?></h2>
        <p><?php esc_html_e('Kingy AI can use the ROI estimate, product URL, and notes to prioritize creator education and creator campaign follow-up.', 'kingy-ai-launch-intelligence'); ?></p>
        <p><?php echo esc_html(kingy_ali_creator_disclosure_note()); ?></p>
        <div class="kingy-ali-cta-row">
            <a data-kingy-ali-track="clicked_submit_cta" data-event-label="<?php esc_attr_e('Submit launch after ROI lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_success" href="<?php echo esc_url(home_url('/ai-launches/submit/')); ?>"><?php esc_html_e('Submit the full launch', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_visibility_score_cta" data-event-label="<?php esc_attr_e('Get Launch Visibility Score after ROI lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_success" href="<?php echo esc_url(home_url('/ai-launches/launch-visibility-score/?kingy_interest=creator_campaign')); ?>"><?php esc_html_e('Get a Launch Visibility Score', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_contact_cta" data-event-label="<?php esc_attr_e('Contact Kingy AI after ROI lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_success" href="<?php echo esc_url(kingy_ali_contact_url()); ?>"><?php esc_html_e('Contact Kingy AI', 'kingy-ai-launch-intelligence'); ?></a>
            <a data-kingy-ali-track="clicked_category_path" data-event-label="<?php esc_attr_e('Browse launches after ROI lead', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="creator_campaign_roi_success" href="<?php echo esc_url(home_url('/ai-launches/')); ?>"><?php esc_html_e('Browse AI launches', 'kingy-ai-launch-intelligence'); ?></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_codex_prompt_builder() {
    kingy_ali_enqueue_assets();

    $fields = kingy_ali_codex_prompt_builder_fields();
    $presets = kingy_ali_codex_prompt_builder_presets();

    ob_start();
    ?>
    <section class="kingy-ali-codex-builder" data-kingy-codex-prompt-builder>
        <div class="kingy-ali-codex-builder__header">
            <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Turn a rough product idea into a practical Codex brief with scope, constraints, users, style, data, and testing baked in. Start with the required fields, then add optional context only when it changes the build decision.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>

        <div class="kingy-ali-codex-builder__guide" aria-label="<?php esc_attr_e('Codex prompt builder guide', 'kingy-ai-launch-intelligence'); ?>">
            <article>
                <strong><?php esc_html_e('Use it before a build', 'kingy-ai-launch-intelligence'); ?></strong>
                <p><?php esc_html_e('Best for turning a fuzzy app, page, plugin, dashboard, calculator, or workflow idea into a first implementation prompt that Codex can inspect, scope, build, and verify.', 'kingy-ai-launch-intelligence'); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Keep version one small', 'kingy-ai-launch-intelligence'); ?></strong>
                <p><?php esc_html_e('A better first prompt asks for one useful outcome, a short feature list, clear no-go areas, and checks that prove the result actually works.', 'kingy-ai-launch-intelligence'); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Make review easier', 'kingy-ai-launch-intelligence'); ?></strong>
                <p><?php esc_html_e('The generated prompt asks Codex to inspect the existing project first, keep changes scoped, run relevant checks, and summarize the final change so you can review it faster.', 'kingy-ai-launch-intelligence'); ?></p>
            </article>
        </div>

        <nav class="kingy-ali-codex-jump-links" aria-label="<?php esc_attr_e('Codex prompt builder sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#kingy-codex-builder-form"><?php esc_html_e('Build Prompt', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-codex-prompt-anatomy"><?php esc_html_e('Prompt Anatomy', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-codex-workflow"><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-codex-review"><?php esc_html_e('Review Checklist', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <div class="kingy-ali-codex-presets" aria-label="<?php esc_attr_e('Suggested Codex prompt starts', 'kingy-ai-launch-intelligence'); ?>">
            <?php foreach ($presets as $preset) : ?>
                <button
                    type="button"
                    class="kingy-ali-codex-preset"
                    data-codex-preset
                    <?php foreach ($fields as $key => $field) : ?>
                        data-<?php echo esc_attr($key); ?>="<?php echo esc_attr(isset($preset['values'][$key]) ? $preset['values'][$key] : ''); ?>"
                    <?php endforeach; ?>
                >
                    <strong><?php echo esc_html($preset['label']); ?></strong>
                    <span><?php echo esc_html($preset['summary']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <form id="kingy-codex-builder-form" class="kingy-ali-codex-form">
            <?php foreach ($fields as $key => $field) : ?>
                <div class="kingy-ali-codex-field">
                    <label for="kingy-codex-<?php echo esc_attr($key); ?>">
                        <span><?php echo esc_html($field['label']); ?></span>
                        <em><?php echo !empty($field['required']) ? esc_html__('Required', 'kingy-ai-launch-intelligence') : esc_html__('Optional', 'kingy-ai-launch-intelligence'); ?></em>
                    </label>
                    <?php if (!empty($field['help'])) : ?>
                        <p class="kingy-ali-codex-field__help"><?php echo esc_html($field['help']); ?></p>
                    <?php endif; ?>
                    <div class="kingy-ali-codex-control">
                        <?php if ($field['type'] === 'select') : ?>
                            <select id="kingy-codex-<?php echo esc_attr($key); ?>" name="kingy_codex_prompt[<?php echo esc_attr($key); ?>]" data-codex-field="<?php echo esc_attr($key); ?>" <?php echo !empty($field['required']) ? 'required aria-required="true"' : ''; ?>>
                                <?php foreach ($field['choices'] as $choice) : ?>
                                    <option value="<?php echo esc_attr($choice); ?>" <?php selected($choice, $field['default']); ?>><?php echo esc_html($choice); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] === 'textarea') : ?>
                            <textarea id="kingy-codex-<?php echo esc_attr($key); ?>" name="kingy_codex_prompt[<?php echo esc_attr($key); ?>]" rows="<?php echo esc_attr($field['rows']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" data-codex-field="<?php echo esc_attr($key); ?>" <?php echo !empty($field['required']) ? 'required aria-required="true"' : ''; ?>><?php echo esc_textarea($field['default']); ?></textarea>
                        <?php else : ?>
                            <input id="kingy-codex-<?php echo esc_attr($key); ?>" type="text" name="kingy_codex_prompt[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($field['default']); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>" data-codex-field="<?php echo esc_attr($key); ?>" <?php echo !empty($field['required']) ? 'required aria-required="true"' : ''; ?>>
                        <?php endif; ?>

                        <?php if (!empty($field['suggestions'])) : ?>
                            <select class="kingy-ali-codex-suggestion" data-codex-suggestion="<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr(sprintf(__('Suggested ideas for %s', 'kingy-ai-launch-intelligence'), $field['label'])); ?>">
                                <option value=""><?php esc_html_e('Add a suggestion', 'kingy-ai-launch-intelligence'); ?></option>
                                <?php foreach ($field['suggestions'] as $suggestion) : ?>
                                    <option value="<?php echo esc_attr($suggestion); ?>"><?php echo esc_html($suggestion); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="kingy-ali-codex-actions">
                <button type="button" data-codex-generate><?php esc_html_e('Generate Result', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
            </div>

            <div class="kingy-ali-codex-output">
                <label for="kingy-codex-output"><?php esc_html_e('Codex-ready prompt', 'kingy-ai-launch-intelligence'); ?></label>
                <textarea id="kingy-codex-output" rows="12" readonly data-codex-output></textarea>
                <button type="button" class="kingy-ali-codex-secondary" data-codex-copy><?php esc_html_e('Copy Prompt', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </form>

        <section id="kingy-codex-prompt-anatomy" class="kingy-ali-codex-anatomy">
            <h3><?php esc_html_e('What a strong Codex prompt gives the agent', 'kingy-ai-launch-intelligence'); ?></h3>
            <p><?php esc_html_e('Codex can gather context while it works, but the first prompt should still make the job reviewable. Think of the prompt as a compact brief: what to change, what context matters, what to avoid, and how everyone will know the work is finished.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-codex-anatomy__grid">
                <div><strong><?php esc_html_e('Goal', 'kingy-ai-launch-intelligence'); ?></strong><p><?php esc_html_e('Name the thing you want built or fixed in plain English. Smaller is better: one useful page, workflow, component, shortcode, import, or dashboard beats a giant app request.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><strong><?php esc_html_e('Context', 'kingy-ai-launch-intelligence'); ?></strong><p><?php esc_html_e('Point Codex toward the platform, audience, existing files, data shape, brand conventions, and examples that should guide the implementation.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><strong><?php esc_html_e('Constraints', 'kingy-ai-launch-intelligence'); ?></strong><p><?php esc_html_e('List anything that should stay untouched: routes, schema, public URLs, analytics, current styling, production data, paid services, or security-sensitive behavior.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div><strong><?php esc_html_e('Done when', 'kingy-ai-launch-intelligence'); ?></strong><p><?php esc_html_e('Tell Codex what to verify: syntax checks, existing tests, browser behavior, mobile layout, form states, copy buttons, empty states, and a short final summary.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section id="kingy-codex-workflow" class="kingy-ali-codex-workflow">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommended workflow', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Use the prompt in a small loop, not as a one-shot wish.', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Paste the generated prompt, let Codex inspect the project, and ask for a plan first when the task touches multiple files, data models, payments, authentication, publishing, or anything hard to undo. After implementation, have Codex report exactly what changed and which checks passed or could not run.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <ol>
                <li><?php esc_html_e('Start with the required fields: build target, platform, must-have scope, and tests.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Add optional fields only when they change a decision Codex would otherwise guess.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Ask Codex to inspect existing code, reuse current patterns, and keep edits scoped.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Review the diff, run the page or workflow yourself, and follow up with one focused fix at a time.', 'kingy-ai-launch-intelligence'); ?></li>
            </ol>
        </section>

        <section id="kingy-codex-review" class="kingy-ali-codex-checklist">
            <h3><?php esc_html_e('Quick review checklist before you paste', 'kingy-ai-launch-intelligence'); ?></h3>
            <p><?php esc_html_e('If a prompt fails this check, make it smaller or ask Codex to interview you before it edits files.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-ali-codex-checklist__items">
                <label><input type="checkbox"> <?php esc_html_e('The build target is specific enough that a reviewer would know what changed.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox"> <?php esc_html_e('The must-have scope is short enough for one focused implementation pass.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox"> <?php esc_html_e('Risky areas, public URLs, schema, secrets, payments, or production data are protected.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox"> <?php esc_html_e('The verification request includes concrete checks, not just "make sure it works."', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox"> <?php esc_html_e('The prompt asks for a final summary so you can review the work without guessing.', 'kingy-ai-launch-intelligence'); ?></label>
            </div>
        </section>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_shortcode_vibe_coding_beginner_hub() {
    kingy_ali_enqueue_assets();

    $builders = kingy_ali_vibe_coding_builders();
    $app_types = kingy_ali_vibe_coding_app_types();
    $audiences = kingy_ali_vibe_coding_audiences();
    $prompts = kingy_ali_vibe_coding_prompts();
    $faqs = kingy_ali_vibe_coding_faqs();
    $resources = kingy_ali_vibe_coding_resources();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-vibe-guide" data-kingy-vibe-guide>
        <header class="kingy-ali-academy-hero kingy-ali-vibe-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('AI app builder hub', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Vibe Coding for Beginners: Build Your First App With AI', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('A plain-English workbench for creators, founders, students, marketers, WordPress site owners, and small businesses who want to turn one useful idea into a tiny AI-assisted app they can test before they trust.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a href="#vibe-planner" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Use app planner', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="vibe_hero"><?php esc_html_e('Plan My First App', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#vibe-prompts" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Copy prompts', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="vibe_hero"><?php esc_html_e('Copy Prompts', 'kingy-ai-launch-intelligence'); ?></a>
                    <a href="#vibe-launch-gate" data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Check launch readiness', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="vibe_hero"><?php esc_html_e('Check Launch Readiness', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('First app promise', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Your first useful app plan', 'kingy-ai-launch-intelligence'); ?></h2>
                <ul>
                    <li><?php esc_html_e('A small app idea matched to a real audience.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('A tiny MVP scope that avoids version-one traps.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('A builder path, copyable prompt, QA checklist, and approval gate.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
                <p class="kingy-ali-small-note"><?php esc_html_e('Best first projects: calculators, quizzes, trackers, directories, generators, checklists, and internal tools.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Vibe coding guide sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#vibe-definition"><?php esc_html_e('Basics', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-planner"><?php esc_html_e('Planner', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-ideas"><?php esc_html_e('Ideas', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-builder-selector"><?php esc_html_e('Builders', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-workflow"><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-prompts"><?php esc_html_e('Prompts', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-testing"><?php esc_html_e('Testing', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#vibe-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="vibe-definition" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Plain-English definition', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('What is vibe coding?', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Vibe coding means describing the app you want in normal language, then working with AI to generate, edit, test, and improve the result. It is powerful, but it is not magic: the beginner still owns the scope, judgment, testing, and launch decision.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-practical-grid">
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Start small', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('The best first app solves one narrow problem with one clear output. A giant SaaS idea is a bad first step.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Use judgment', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('AI can produce convincing interfaces with broken buttons, weak logic, bad mobile layouts, or invented backend behavior.', 'kingy-ai-launch-intelligence'); ?></p></article>
                <article class="kingy-ali-practical-card"><h3><?php esc_html_e('Test repeatedly', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Small prompts, visible outputs, realistic inputs, and repeated QA beat one huge prompt almost every time.', 'kingy-ai-launch-intelligence'); ?></p></article>
            </div>
        </section>

        <section id="vibe-planner" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive planner', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Generate a beginner-safe app plan', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Choose the closest audience, app type, complexity, and constraint. The planner returns a builder path, MVP scope, first prompt, and testing checklist without pretending version one needs every advanced feature.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-vibe-planner">
                <form data-vibe-planner-form>
                    <label><span><?php esc_html_e('Audience', 'kingy-ai-launch-intelligence'); ?></span><select data-vibe-field="audience"><?php foreach ($audiences as $key => $audience) : ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($audience['label']); ?></option><?php endforeach; ?></select></label>
                    <label><span><?php esc_html_e('App type', 'kingy-ai-launch-intelligence'); ?></span><select data-vibe-field="type"><?php foreach ($app_types as $key => $type) : ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($type['label']); ?></option><?php endforeach; ?></select></label>
                    <label><span><?php esc_html_e('Complexity', 'kingy-ai-launch-intelligence'); ?></span><select data-vibe-field="complexity"><option value="tiny"><?php esc_html_e('Tiny: one page, one output', 'kingy-ai-launch-intelligence'); ?></option><option value="saved"><?php esc_html_e('Saved: needs records or a dashboard', 'kingy-ai-launch-intelligence'); ?></option><option value="public"><?php esc_html_e('Public: visitors or clients will use it', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                    <label><span><?php esc_html_e('Version-one constraint', 'kingy-ai-launch-intelligence'); ?></span><select data-vibe-field="constraint"><option value="no-login"><?php esc_html_e('Avoid login, payments, and databases', 'kingy-ai-launch-intelligence'); ?></option><option value="wordpress"><?php esc_html_e('Must fit a WordPress page', 'kingy-ai-launch-intelligence'); ?></option><option value="code"><?php esc_html_e('I want to learn and own code', 'kingy-ai-launch-intelligence'); ?></option><option value="visual"><?php esc_html_e('I need a fast visual prototype', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Generate App Plan', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-vibe-plan-output" data-vibe-planner-output aria-live="polite">
                    <p class="kingy-ali-kicker"><?php esc_html_e('Your plan', 'kingy-ai-launch-intelligence'); ?></p>
                    <h3><?php esc_html_e('A focused first-app plan appears here', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Start with the defaults or adjust the controls to match your first build.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <section id="vibe-ideas" class="kingy-ali-builder-chooser kingy-ali-vibe-idea-tool">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('First app idea generator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Pick an audience and get safe starter ideas', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('Audience idea choices', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($audiences as $key => $audience) : ?>
                    <button type="button" class="kingy-ali-choice-button" data-vibe-ideas="<?php echo esc_attr(wp_json_encode($audience)); ?>">
                        <strong><?php echo esc_html($audience['label']); ?></strong>
                        <span><?php echo esc_html($audience['hint']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result" data-vibe-idea-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Starter ideas', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Choose an audience above', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('The best first idea is specific enough to test in one sitting.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="vibe-builder-selector" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Builder chooser', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Choose by job, not hype', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('These are directional beginner notes. Verify current pricing, limits, exports, hosting, and official features before paying or publishing.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-builder-card-grid">
                <?php foreach ($builders as $builder) : ?>
                    <article class="kingy-ali-builder-card kingy-ali-vibe-builder-card">
                        <div class="kingy-ali-builder-card__header"><h3><?php echo esc_html($builder['name']); ?></h3><span><?php echo esc_html($builder['label']); ?></span></div>
                        <p><?php echo esc_html($builder['summary']); ?></p>
                        <dl>
                            <div><dt><?php esc_html_e('Best for', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($builder['best']); ?></dd></div>
                            <div><dt><?php esc_html_e('Avoid if', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($builder['avoid']); ?></dd></div>
                            <div><dt><?php esc_html_e('Beginner note', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($builder['note']); ?></dd></div>
                        </dl>
                        <a href="<?php echo esc_url($builder['url']); ?>" rel="nofollow noopener" target="_blank" data-kingy-ali-track="clicked_official_tool_link" data-event-label="<?php echo esc_attr($builder['name']); ?>" data-event-surface="vibe_builder_cards"><?php echo esc_html(sprintf(__('Visit %s', 'kingy-ai-launch-intelligence'), $builder['name'])); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-workflow" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Beginner workflow', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Build your first app one tiny step at a time', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-vibe-workflow">
                <?php foreach (kingy_ali_vibe_coding_workflow_steps() as $index => $step) : ?>
                    <article class="kingy-ali-agent-step"><span><?php echo esc_html(absint($index) + 1); ?></span><h3><?php echo esc_html($step['title']); ?></h3><p><?php echo esc_html($step['body']); ?></p></article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Expandable examples', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('App types, first prompts, and common fixes', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Open the pattern closest to your idea. Each example keeps the first version small, reviewable, and easy to test before you add accounts, payments, uploads, or databases.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-vibe-example-list">
                <?php foreach (kingy_ali_vibe_coding_examples() as $example) : ?>
                    <details>
                        <summary><?php echo esc_html($example['title']); ?></summary>
                        <div class="kingy-ali-vibe-example-body">
                            <p><strong><?php esc_html_e('Starter build:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($example['starter']); ?></p>
                            <p><strong><?php esc_html_e('Prompt angle:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($example['prompt']); ?></p>
                            <p><strong><?php esc_html_e('Common fix:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($example['fix']); ?></p>
                            <p><strong><?php esc_html_e('Do not add yet:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($example['avoid']); ?></p>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-prompts" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Prompt library', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Copy prompts for each stage', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Each prompt is designed to keep beginners scoped, readable, test-focused, and honest about what AI can and cannot safely do.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-vibe-prompt-grid">
                <?php foreach ($prompts as $index => $prompt) : ?>
                    <?php $prompt_id = 'kingy-vibe-prompt-' . absint($index); ?>
                    <article class="kingy-ali-copy-prompt kingy-ali-vibe-prompt-card">
                        <div><p class="kingy-ali-kicker"><?php echo esc_html($prompt['stage']); ?></p><h3><?php echo esc_html($prompt['title']); ?></h3></div>
                        <pre><code id="<?php echo esc_attr($prompt_id); ?>"><?php echo esc_html($prompt['text']); ?></code></pre>
                        <button type="button" data-vibe-copy-target="#<?php echo esc_attr($prompt_id); ?>"><?php esc_html_e('Copy Prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-testing" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Testing checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Test before publishing', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A polished-looking AI build can still fail in ordinary use. Check behavior, readability, links, and rollback before sending it to real users.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-codex-checklist kingy-ali-vibe-checklist" data-vibe-checklist="qa">
                <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                    <strong><span data-vibe-check-count>0</span> / <?php echo esc_html(count(kingy_ali_vibe_coding_qa_checks())); ?></strong>
                    <span data-vibe-check-status><?php esc_html_e('Needs review', 'kingy-ai-launch-intelligence'); ?></span>
                    <progress max="<?php echo esc_attr(count(kingy_ali_vibe_coding_qa_checks())); ?>" value="0" data-vibe-check-progress></progress>
                </div>
                <div class="kingy-ali-vibe-check-grid">
                    <?php foreach (kingy_ali_vibe_coding_qa_checks() as $index => $check) : ?>
                        <?php $check_id = 'kingy-vibe-qa-' . absint($index); ?>
                        <label for="<?php echo esc_attr($check_id); ?>">
                            <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-vibe-check>
                            <span><strong><?php echo esc_html($check['title']); ?></strong><?php echo esc_html($check['body']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="kingy-ali-codex-secondary" data-vibe-check-reset><?php esc_html_e('Reset Checklist', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="vibe-launch-gate" class="kingy-ali-test-project kingy-ali-vibe-launch-gate">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Human approval gate', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Launch only after these are true', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-vibe-warning"><?php esc_html_e('AI-generated work still needs owner review. Use this final gate before publishing, collecting data, or handing the app to a real user.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-checklist" data-vibe-checklist="approval">
                <?php foreach (kingy_ali_vibe_coding_approval_checks() as $index => $check) : ?>
                    <?php $approval_id = 'kingy-vibe-approval-' . absint($index); ?>
                    <label for="<?php echo esc_attr($approval_id); ?>"><input id="<?php echo esc_attr($approval_id); ?>" type="checkbox" data-vibe-check> <?php echo esc_html($check); ?></label>
                <?php endforeach; ?>
                <p><strong data-vibe-check-count>0</strong> <?php esc_html_e('approval checks complete', 'kingy-ai-launch-intelligence'); ?></p>
                <button type="button" class="kingy-ali-codex-secondary" data-vibe-check-reset><?php esc_html_e('Reset Gate', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="vibe-mistakes" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Beginner mistakes', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Avoid the traps that make first apps fall apart', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-practical-grid">
                <?php foreach (kingy_ali_vibe_coding_mistakes() as $mistake) : ?>
                    <article class="kingy-ali-practical-card"><h3><?php echo esc_html($mistake['title']); ?></h3><p><?php echo esc_html($mistake['body']); ?></p></article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Related Kingy AI learning path', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep building with the next right resource', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($resources as $resource) : ?>
                    <a class="kingy-ali-codex-resource" href="<?php echo esc_url($resource['url']); ?>" data-kingy-ali-track="clicked_vibe_resource" data-event-label="<?php echo esc_attr($resource['label']); ?>" data-event-surface="vibe_resources">
                        <strong><?php echo esc_html($resource['label']); ?></strong>
                        <span><?php echo esc_html($resource['description']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="vibe-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Beginner questions about vibe coding', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details>
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_vibe_coding_builders() {
    return array(
        array('name' => 'Codex', 'label' => __('Guided code agent', 'kingy-ai-launch-intelligence'), 'url' => 'https://openai.com/codex/', 'summary' => __('Best when you want an agent to inspect an existing project, make scoped changes, and report what changed.', 'kingy-ai-launch-intelligence'), 'best' => __('WordPress plugin work, repo-based apps, debugging, QA fixes, and guided implementation.', 'kingy-ai-launch-intelligence'), 'avoid' => __('You have no project files, no acceptance criteria, and only want a visual mockup.', 'kingy-ai-launch-intelligence'), 'note' => __('Ask it to inspect first, keep changes scoped, run checks, and explain rollback.', 'kingy-ai-launch-intelligence')),
        array('name' => 'Replit', 'label' => __('Browser code workspace', 'kingy-ai-launch-intelligence'), 'url' => 'https://replit.com/', 'summary' => __('A practical path for beginners who want to learn how a small app actually runs.', 'kingy-ai-launch-intelligence'), 'best' => __('Tiny full-stack trackers, APIs, scripts, learning projects, and code ownership.', 'kingy-ai-launch-intelligence'), 'avoid' => __('You do not want to see files, logs, dependencies, or deployment details.', 'kingy-ai-launch-intelligence'), 'note' => __('Great for learning, but still requires testing secrets, persistence, and deploy steps.', 'kingy-ai-launch-intelligence')),
        array('name' => 'Vercel / v0', 'label' => __('Polished web UI and deploys', 'kingy-ai-launch-intelligence'), 'url' => 'https://v0.dev/', 'summary' => __('Useful for polished interface drafts and web app front ends that may later deploy on Vercel.', 'kingy-ai-launch-intelligence'), 'best' => __('Landing tools, dashboards, calculators, and UI-heavy prototypes.', 'kingy-ai-launch-intelligence'), 'avoid' => __('Your first version depends on complex private data or unclear backend rules.', 'kingy-ai-launch-intelligence'), 'note' => __('Treat the generated UI as a draft until links, forms, data, and mobile behavior are verified.', 'kingy-ai-launch-intelligence')),
        array('name' => 'Lovable', 'label' => __('Fast product prototype', 'kingy-ai-launch-intelligence'), 'url' => 'https://lovable.dev/', 'summary' => __('A strong first stop when the goal is to see a product-shaped MVP quickly.', 'kingy-ai-launch-intelligence'), 'best' => __('Visual MVPs, SaaS-style drafts, and quick product flow experiments.', 'kingy-ai-launch-intelligence'), 'avoid' => __('You cannot explain the data model, permissions, hosting, or export path.', 'kingy-ai-launch-intelligence'), 'note' => __('Keep the first build to two or three screens and verify current platform details.', 'kingy-ai-launch-intelligence')),
        array('name' => 'Bubble', 'label' => __('No-code workflows', 'kingy-ai-launch-intelligence'), 'url' => 'https://bubble.io/', 'summary' => __('Better for workflow-heavy apps when roles, statuses, and database relationships matter.', 'kingy-ai-launch-intelligence'), 'best' => __('Portals, marketplaces, internal tools, and database-backed no-code products.', 'kingy-ai-launch-intelligence'), 'avoid' => __('You are not ready to map privacy rules, workflows, and ownership.', 'kingy-ai-launch-intelligence'), 'note' => __('Powerful for determined beginners, but overkill for a first calculator or quiz.', 'kingy-ai-launch-intelligence')),
        array('name' => 'WordPress', 'label' => __('Site-native tools', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/'), 'summary' => __('Useful when the app is a simple public tool that belongs inside an existing WordPress page.', 'kingy-ai-launch-intelligence'), 'best' => __('Calculators, quizzes, prompt tools, comparison helpers, and lead magnets.', 'kingy-ai-launch-intelligence'), 'avoid' => __('The app needs private API keys, payments, logins, saved records, or trusted server actions.', 'kingy-ai-launch-intelligence'), 'note' => __('Scope CSS and JavaScript tightly, test the theme context, and avoid browser-visible secrets.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_app_types() {
    return array(
        'calculator' => array('label' => __('Calculator', 'kingy-ai-launch-intelligence'), 'scope' => __('one form, one formula or scoring rule, one clear result, and a copy/reset control', 'kingy-ai-launch-intelligence'), 'tests' => array(__('empty values', 'kingy-ai-launch-intelligence'), __('large numbers', 'kingy-ai-launch-intelligence'), __('mobile result layout', 'kingy-ai-launch-intelligence'))),
        'quiz' => array('label' => __('Quiz', 'kingy-ai-launch-intelligence'), 'scope' => __('five to eight questions, simple scoring, friendly result copy, and a retake option', 'kingy-ai-launch-intelligence'), 'tests' => array(__('no answer selected', 'kingy-ai-launch-intelligence'), __('tie scores', 'kingy-ai-launch-intelligence'), __('long result text', 'kingy-ai-launch-intelligence'))),
        'tracker' => array('label' => __('Tracker', 'kingy-ai-launch-intelligence'), 'scope' => __('a simple add/list/update flow with sample data before real accounts or databases', 'kingy-ai-launch-intelligence'), 'tests' => array(__('add item', 'kingy-ai-launch-intelligence'), __('edit status', 'kingy-ai-launch-intelligence'), __('refresh behavior', 'kingy-ai-launch-intelligence'))),
        'directory' => array('label' => __('Directory', 'kingy-ai-launch-intelligence'), 'scope' => __('a small curated list, category filters, detail cards, and a no-results state', 'kingy-ai-launch-intelligence'), 'tests' => array(__('filter matches', 'kingy-ai-launch-intelligence'), __('no results', 'kingy-ai-launch-intelligence'), __('link review', 'kingy-ai-launch-intelligence'))),
        'generator' => array('label' => __('Generator', 'kingy-ai-launch-intelligence'), 'scope' => __('a few inputs, structured output, copy button, reset button, and clear limits', 'kingy-ai-launch-intelligence'), 'tests' => array(__('blank input', 'kingy-ai-launch-intelligence'), __('long input', 'kingy-ai-launch-intelligence'), __('copy output', 'kingy-ai-launch-intelligence'))),
        'internal' => array('label' => __('Internal tool', 'kingy-ai-launch-intelligence'), 'scope' => __('one repeatable workflow, sample records, admin notes, and a manual export or handoff path', 'kingy-ai-launch-intelligence'), 'tests' => array(__('realistic record', 'kingy-ai-launch-intelligence'), __('status change', 'kingy-ai-launch-intelligence'), __('handoff notes', 'kingy-ai-launch-intelligence'))),
    );
}

function kingy_ali_vibe_coding_audiences() {
    return array(
        'creator' => array('label' => __('Creator', 'kingy-ai-launch-intelligence'), 'hint' => __('Content, creator campaigns, and audience workflows.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Creator campaign rate calculator', 'kingy-ai-launch-intelligence'), __('YouTube title tester', 'kingy-ai-launch-intelligence'), __('Content idea tracker', 'kingy-ai-launch-intelligence'))),
        'founder' => array('label' => __('Founder', 'kingy-ai-launch-intelligence'), 'hint' => __('Validate demand before building too much.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Waitlist fit checker', 'kingy-ai-launch-intelligence'), __('Lead scoring tool', 'kingy-ai-launch-intelligence'), __('Pricing calculator', 'kingy-ai-launch-intelligence'))),
        'student' => array('label' => __('Student', 'kingy-ai-launch-intelligence'), 'hint' => __('Study helpers and planning tools.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Study quiz generator', 'kingy-ai-launch-intelligence'), __('Assignment planner', 'kingy-ai-launch-intelligence'), __('Flashcard helper', 'kingy-ai-launch-intelligence'))),
        'business' => array('label' => __('Small business', 'kingy-ai-launch-intelligence'), 'hint' => __('Simple customer-facing or internal helpers.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Quote estimator', 'kingy-ai-launch-intelligence'), __('Booking intake helper', 'kingy-ai-launch-intelligence'), __('Customer FAQ assistant', 'kingy-ai-launch-intelligence'))),
        'marketer' => array('label' => __('Marketer', 'kingy-ai-launch-intelligence'), 'hint' => __('Campaign planning and measurement helpers.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Campaign brief generator', 'kingy-ai-launch-intelligence'), __('Landing page grader', 'kingy-ai-launch-intelligence'), __('UTM builder', 'kingy-ai-launch-intelligence'))),
        'wordpress' => array('label' => __('WordPress site owner', 'kingy-ai-launch-intelligence'), 'hint' => __('Tools that can live inside existing pages.', 'kingy-ai-launch-intelligence'), 'ideas' => array(__('Custom calculator', 'kingy-ai-launch-intelligence'), __('Resource finder', 'kingy-ai-launch-intelligence'), __('Lead magnet quiz', 'kingy-ai-launch-intelligence'))),
    );
}

function kingy_ali_vibe_coding_workflow_steps() {
    return array(
        array('title' => __('Pick a tiny problem', 'kingy-ai-launch-intelligence'), 'body' => __('Choose one job a real person wants done, not a platform idea.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Choose the simplest app type', 'kingy-ai-launch-intelligence'), 'body' => __('Calculator, quiz, checklist, directory, tracker, generator, or internal tool.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Write the first prompt', 'kingy-ai-launch-intelligence'), 'body' => __('Name the user, goal, inputs, output, constraints, and done criteria.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Build the smallest working version', 'kingy-ai-launch-intelligence'), 'body' => __('Leave out login, payments, uploads, and databases unless version one truly needs them.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Test every path', 'kingy-ai-launch-intelligence'), 'body' => __('Try normal inputs, empty inputs, long text, mobile screens, copy/reset, errors, and refreshes.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Fix one issue at a time', 'kingy-ai-launch-intelligence'), 'body' => __('Give Codex the exact problem, expected behavior, and reproduction steps.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Publish only after review', 'kingy-ai-launch-intelligence'), 'body' => __('Owner approval, real links, no private data, and rollback notes come before launch.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_prompts() {
    return array(
        array('stage' => __('Planning', 'kingy-ai-launch-intelligence'), 'title' => __('AI app builder planner', 'kingy-ai-launch-intelligence'), 'text' => __('Help me turn this beginner app idea into a safe MVP: [APP IDEA]. Recommend the best builder path, define the user, inputs, outputs, feature list, risks, first build prompt, QA checklist, and launch notes. Avoid login, payments, file uploads, databases, and fake forms unless the MVP truly needs them.', 'kingy-ai-launch-intelligence')),
        array('stage' => __('First build', 'kingy-ai-launch-intelligence'), 'title' => __('First build request', 'kingy-ai-launch-intelligence'), 'text' => __('/goal Inspect the current project first. Build the smallest working version of [APP IDEA]. Keep the UI responsive, accessible, and beginner-friendly. Use scoped CSS, avoid unsupported claims, and give me a test checklist plus rollback note before publishing.', 'kingy-ai-launch-intelligence')),
        array('stage' => __('Debugging', 'kingy-ai-launch-intelligence'), 'title' => __('Fix one broken behavior', 'kingy-ai-launch-intelligence'), 'text' => __('Inspect the current implementation and fix only this issue: [BUG]. Reproduction steps: [STEPS]. Expected behavior: [EXPECTED]. Actual behavior: [ACTUAL]. Keep unrelated code unchanged and report files changed plus verification performed.', 'kingy-ai-launch-intelligence')),
        array('stage' => __('Redesign', 'kingy-ai-launch-intelligence'), 'title' => __('Improve readability and spacing', 'kingy-ai-launch-intelligence'), 'text' => __('Audit this page for contrast, typography, spacing, mobile layout, and cramped cards. Fix unreadable text, tight sections, overlapping elements, and weak copy-button states while preserving the existing content and brand voice.', 'kingy-ai-launch-intelligence')),
        array('stage' => __('Accessibility', 'kingy-ai-launch-intelligence'), 'title' => __('Accessibility pass', 'kingy-ai-launch-intelligence'), 'text' => __('Review keyboard navigation, labels, focus states, semantic headings, color contrast, form errors, button text, and screen-reader-friendly status messages. Fix issues and list what was verified.', 'kingy-ai-launch-intelligence')),
        array('stage' => __('Launch QA', 'kingy-ai-launch-intelligence'), 'title' => __('Pre-launch review', 'kingy-ai-launch-intelligence'), 'text' => __('Run a pre-launch QA pass for this beginner AI-built app. Check desktop, tablet, mobile, console errors, empty states, long inputs, copy/reset buttons, outbound links, fake claims, private data, SEO basics, and rollback notes. Return pass/fail notes before publishing.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_examples() {
    return array(
        array('title' => __('Calculator: creator campaign rate helper', 'kingy-ai-launch-intelligence'), 'starter' => __('A single form asks for audience size, average views, niche, deliverables, and returns a suggested rate range plus a negotiation note.', 'kingy-ai-launch-intelligence'), 'prompt' => __('Ask for transparent inputs, simple scoring rules, plain-English output, and a visible disclaimer that it is an estimate, not guaranteed pricing.', 'kingy-ai-launch-intelligence'), 'fix' => __('If results feel random, ask AI to show the formula, input weights, and edge-case behavior before changing the design.', 'kingy-ai-launch-intelligence'), 'avoid' => __('payment collection, account dashboards, private campaign data, or invented market benchmarks', 'kingy-ai-launch-intelligence')),
        array('title' => __('Quiz: best first app type finder', 'kingy-ai-launch-intelligence'), 'starter' => __('Five questions classify the user into calculator, quiz, tracker, directory, generator, or internal tool and show the safest next prompt.', 'kingy-ai-launch-intelligence'), 'prompt' => __('Ask for scoring logic, tie handling, reset behavior, mobile layout, and readable result cards.', 'kingy-ai-launch-intelligence'), 'fix' => __('If the quiz always recommends the same answer, ask AI to audit scoring thresholds and provide three test cases.', 'kingy-ai-launch-intelligence'), 'avoid' => __('email capture, personality claims, psychological profiling, or unreviewed recommendations', 'kingy-ai-launch-intelligence')),
        array('title' => __('Tracker: client request board', 'kingy-ai-launch-intelligence'), 'starter' => __('A sample-data board lets a user add a request, choose priority, update status, and copy a summary.', 'kingy-ai-launch-intelligence'), 'prompt' => __('Ask for one add/list/update flow, sample records, validation, empty state, and manual export notes.', 'kingy-ai-launch-intelligence'), 'fix' => __('If records vanish on refresh, decide whether version one is local-only or needs a real database before calling it production-ready.', 'kingy-ai-launch-intelligence'), 'avoid' => __('real customer data, logins, permissions, notifications, or hidden storage until data handling is reviewed', 'kingy-ai-launch-intelligence')),
        array('title' => __('Directory: vetted resource finder', 'kingy-ai-launch-intelligence'), 'starter' => __('A curated set of resources can be filtered by audience, difficulty, and app type, with a clear no-results state.', 'kingy-ai-launch-intelligence'), 'prompt' => __('Ask for filter chips, accessible cards, real links only, no-results copy, and a fast mobile layout.', 'kingy-ai-launch-intelligence'), 'fix' => __('If filters feel broken, test one known matching item, one no-results query, and one reset path.', 'kingy-ai-launch-intelligence'), 'avoid' => __('fake affiliate links, unsupported rankings, scraped data, or claims about current pricing', 'kingy-ai-launch-intelligence')),
        array('title' => __('Generator: launch checklist prompt builder', 'kingy-ai-launch-intelligence'), 'starter' => __('A few fields generate a scoped Codex prompt, QA checklist, rollback note, and owner approval reminder.', 'kingy-ai-launch-intelligence'), 'prompt' => __('Ask for structured output, copy button, reset button, empty-input guidance, and no fake form submission.', 'kingy-ai-launch-intelligence'), 'fix' => __('If copied text is incomplete, test long inputs, line breaks, special characters, and the copied-state label.', 'kingy-ai-launch-intelligence'), 'avoid' => __('saving private prompts, sending data to unknown endpoints, or pretending the generated plan was human-reviewed', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_qa_checks() {
    return array(
        array('title' => __('Empty inputs', 'kingy-ai-launch-intelligence'), 'body' => __(' show a clear next step instead of a broken result.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Realistic inputs', 'kingy-ai-launch-intelligence'), 'body' => __(' produce a useful result a beginner can understand.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Long text', 'kingy-ai-launch-intelligence'), 'body' => __(' wraps without overflow, overlap, or clipped controls.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Every button', 'kingy-ai-launch-intelligence'), 'body' => __(' has a real action, visible feedback, and keyboard access.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Mobile layout', 'kingy-ai-launch-intelligence'), 'body' => __(' stays readable without horizontal scrolling.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Refresh behavior', 'kingy-ai-launch-intelligence'), 'body' => __(' is intentional and does not lose important user context unexpectedly.', 'kingy-ai-launch-intelligence')),
        array('title' => __('No-results or error state', 'kingy-ai-launch-intelligence'), 'body' => __(' helps the user recover without blaming them.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Browser console', 'kingy-ai-launch-intelligence'), 'body' => __(' has no new JavaScript errors during the core flow.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Copy/reset controls', 'kingy-ai-launch-intelligence'), 'body' => __(' work and announce visible copied/reset states.', 'kingy-ai-launch-intelligence')),
        array('title' => __('No fake links or forms', 'kingy-ai-launch-intelligence'), 'body' => __(' appear anywhere in the live experience.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_approval_checks() {
    return array(
        __('A human owner tested the core flow with realistic inputs.', 'kingy-ai-launch-intelligence'),
        __('Every CTA, outbound link, and form destination is real.', 'kingy-ai-launch-intelligence'),
        __('No private data, API keys, tokens, cookies, or unsupported pricing claims are present.', 'kingy-ai-launch-intelligence'),
        __('Mobile layout, copy buttons, reset states, and no-results states have been checked.', 'kingy-ai-launch-intelligence'),
        __('The rollback or removal step is clear if the page misbehaves after publishing.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_vibe_coding_mistakes() {
    return array(
        array('title' => __('Overbuilding the first version', 'kingy-ai-launch-intelligence'), 'body' => __('Start with one workflow before adding accounts, teams, payments, notifications, or dashboards.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Writing vague prompts', 'kingy-ai-launch-intelligence'), 'body' => __('A good prompt names the user, job, inputs, output, constraints, and tests. Vibes alone are not enough.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Adding login too early', 'kingy-ai-launch-intelligence'), 'body' => __('Authentication increases privacy, security, and support burden. Prototype the core value first.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Trusting AI without testing', 'kingy-ai-launch-intelligence'), 'body' => __('AI can sound confident while shipping broken logic, invented claims, or inaccessible layouts.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Ignoring data ownership', 'kingy-ai-launch-intelligence'), 'body' => __('Before collecting real data, know where it is stored, who can access it, and how to export or delete it.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Publishing fake forms', 'kingy-ai-launch-intelligence'), 'body' => __('A form is not real until submission, validation, errors, privacy copy, and destination are verified.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_vibe_coding_resources() {
    return array(
        array('label' => __('Vibe Coding for Beginners Course', 'kingy-ai-launch-intelligence'), 'description' => __('Continue the beginner learning path.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/vibe-coding-for-beginners-course/')),
        array('label' => __('Codex for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Learn how to ask Codex to inspect, build, debug, and verify.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/codex-for-beginners/')),
        array('label' => __('Replit for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Build simple browser-based AI apps while learning app structure.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/replit-for-beginners-ai-apps/')),
        array('label' => __('Vercel for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Understand polished deployment paths for beginner AI apps.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/vercel-for-beginners-ai-apps/')),
        array('label' => __('AI Video Production Course', 'kingy-ai-launch-intelligence'), 'description' => __('Use Kingy AI video lessons when your first app supports content or launch assets.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/ai-video-production-course-for-beginners/')),
        array('label' => __('Build With AI Academy', 'kingy-ai-launch-intelligence'), 'description' => __('Return to the broader Kingy AI learning hub.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/')),
        array('label' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'), 'description' => __('Turn a build idea into a scoped implementation prompt.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')),
    );
}

function kingy_ali_vibe_coding_faqs() {
    return array(
        array('question' => __('What is vibe coding for beginners?', 'kingy-ai-launch-intelligence'), 'answer' => __('It is building software by describing what you want in plain English, then using AI to draft, edit, test, and improve the app. Beginners still need to scope, review, and test the work.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What should I build first with an AI app builder?', 'kingy-ai-launch-intelligence'), 'answer' => __('Start with a calculator, quiz, tracker, directory, generator, checklist, or internal tool. These are small enough to test without building a full SaaS product.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Which AI app builder is best for beginners?', 'kingy-ai-launch-intelligence'), 'answer' => __('There is no permanent overall winner. Codex is strong for repo-based guided work, Replit for learning code, Vercel/v0 for polished web UI, Lovable for fast product drafts, Bubble for no-code workflows, and WordPress for simple site-native tools.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Should my first AI-built app include login or payments?', 'kingy-ai-launch-intelligence'), 'answer' => __('Usually no. Login, payments, databases, and file uploads add privacy, security, support, and testing complexity. Add them only after the core value works.', 'kingy-ai-launch-intelligence')),
        array('question' => __('How do I know an AI-generated app is safe to publish?', 'kingy-ai-launch-intelligence'), 'answer' => __('Test the core flow with realistic inputs, check mobile layout, verify links and forms, inspect console errors, remove private data, avoid unsupported claims, and require human owner approval.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Can vibe coding replace learning to code?', 'kingy-ai-launch-intelligence'), 'answer' => __('It can help you build faster, but understanding files, data, errors, security, and testing still matters. The best beginner path uses AI while gradually learning how the app works.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_shortcode_app_builder_comparison() {
    kingy_ali_enqueue_assets();

    $builders = kingy_ali_app_builder_comparison_tools();
    $criteria = kingy_ali_app_builder_comparison_criteria();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article" data-kingy-app-builder-comparison>
        <header class="kingy-ali-academy-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Lovable vs Replit vs Bolt vs Bubble vs Softr', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Choose an AI app builder by the job you need it to do: prototype a product, learn real code, ship a database-backed workflow, or publish a client-facing portal.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Use the chooser', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="builder_comparison_hero" href="#builder-chooser"><?php esc_html_e('Use the chooser', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Jump to comparison table', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="builder_comparison_hero" href="#comparison-table"><?php esc_html_e('Compare tools', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Score your test build', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="builder_comparison_hero" href="#scorecard"><?php esc_html_e('Score your test', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('Fast recommendation', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('If you are new, test the same tiny project in two tools before committing: one form, one saved record, one results page, and one mobile pass.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul>
                    <li><?php esc_html_e('Fast visual prototype: Lovable or Bolt', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Learn and own code: Replit', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Complex workflows: Bubble', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Client portals and directories: Softr', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
                <p class="kingy-ali-small-note"><?php esc_html_e('Last reviewed guidance should be treated as directional. Verify pricing, limits, and current features on the official sites before paying or publishing.', 'kingy-ai-launch-intelligence'); ?></p>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Article sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#builder-chooser"><?php esc_html_e('Chooser', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#comparison-table"><?php esc_html_e('Matrix', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#tool-cards"><?php esc_html_e('Tool cards', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#test-project"><?php esc_html_e('Test project', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#scorecard"><?php esc_html_e('Scorecard', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#practical-guide"><?php esc_html_e('Guide', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#resources"><?php esc_html_e('Links', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="builder-chooser" class="kingy-ali-builder-chooser">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive chooser', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('What are you trying to build first?', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Pick the closest project type and the page will recommend a starting builder. This is a practical first-pass recommendation, not a claim about every current plan or feature.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('Project type', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach (kingy_ali_app_builder_comparison_recommendations() as $key => $recommendation) : ?>
                    <button
                        type="button"
                        class="kingy-ali-choice-button"
                        data-builder-choice="<?php echo esc_attr($key); ?>"
                        data-builder-recommendation="<?php echo esc_attr(wp_json_encode($recommendation)); ?>"
                    >
                        <strong><?php echo esc_html($recommendation['label']); ?></strong>
                        <span><?php echo esc_html($recommendation['hint']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result" data-builder-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Start with the same tiny test project', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Use the chooser above to get a focused recommendation, then verify current pricing, limits, hosting, and export options on the official product site before you publish or buy.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="comparison-table" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Decision matrix', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Compare by project fit, not hype', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('AI builder products change quickly. This table stays useful by comparing the kind of work each tool is usually best suited for, while sending readers to official pages for current product specifics.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-comparison-table-wrap">
                <table class="kingy-ali-comparison-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Tool', 'kingy-ai-launch-intelligence'); ?></th>
                            <?php foreach ($criteria as $criterion) : ?>
                                <th scope="col"><?php echo esc_html($criterion); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($builders as $builder) : ?>
                            <tr>
                                <th scope="row">
                                    <a href="<?php echo esc_url($builder['url']); ?>" data-kingy-ali-track="clicked_official_tool_link" data-event-label="<?php echo esc_attr($builder['name']); ?>" data-event-surface="builder_comparison_table" rel="nofollow noopener" target="_blank"><?php echo esc_html($builder['name']); ?></a>
                                </th>
                                <?php foreach ($criteria as $key => $criterion) : ?>
                                    <td><?php echo esc_html(isset($builder[$key]) ? $builder[$key] : ''); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="tool-cards" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Builder profiles', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Where each tool tends to fit', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-builder-card-grid">
                <?php foreach ($builders as $builder) : ?>
                    <article class="kingy-ali-builder-card">
                        <div class="kingy-ali-builder-card__header">
                            <h3><?php echo esc_html($builder['name']); ?></h3>
                            <span><?php echo esc_html($builder['label']); ?></span>
                        </div>
                        <p><?php echo esc_html($builder['summary']); ?></p>
                        <dl>
                            <div>
                                <dt><?php esc_html_e('Best first test', 'kingy-ai-launch-intelligence'); ?></dt>
                                <dd><?php echo esc_html($builder['test']); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Before you commit', 'kingy-ai-launch-intelligence'); ?></dt>
                                <dd><?php echo esc_html($builder['verify']); ?></dd>
                            </div>
                        </dl>
                        <a href="<?php echo esc_url($builder['url']); ?>" data-kingy-ali-track="clicked_official_tool_link" data-event-label="<?php echo esc_attr($builder['name']); ?>" data-event-surface="builder_card" rel="nofollow noopener" target="_blank"><?php echo esc_html(sprintf(__('Visit %s', 'kingy-ai-launch-intelligence'), $builder['name'])); ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="test-project" class="kingy-ali-test-project">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Fair comparison exercise', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Build the same tiny app in two tools', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The fairest comparison is a real build with the same scope. Try a client-request tracker, niche directory, or course resource list with one create flow, one saved record, one detail view, and one mobile QA pass.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-spec-grid">
                    <div>
                        <h3><?php esc_html_e('Required screens', 'kingy-ai-launch-intelligence'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Submit request', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Saved requests list', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Request detail view', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Edit status or notes', 'kingy-ai-launch-intelligence'); ?></li>
                        </ul>
                    </div>
                    <div>
                        <h3><?php esc_html_e('Required fields', 'kingy-ai-launch-intelligence'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Client name', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Request summary', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Priority', 'kingy-ai-launch-intelligence'); ?></li>
                            <li><?php esc_html_e('Status and internal notes', 'kingy-ai-launch-intelligence'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="kingy-ali-checklist">
                <label><input type="checkbox" data-builder-check> <?php esc_html_e('One form with required and optional inputs', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-builder-check> <?php esc_html_e('One saved record or generated output', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-builder-check> <?php esc_html_e('One edit or correction flow', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-builder-check> <?php esc_html_e('Mobile layout review', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-builder-check> <?php esc_html_e('Export, rollback, or handoff path checked', 'kingy-ai-launch-intelligence'); ?></label>
                <p><strong data-builder-check-count>0</strong> <?php esc_html_e('of 5 checks complete', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="scorecard" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Interactive scorecard', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Score the tools after your test build', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use this lightweight rubric after building the same tiny app in two or more tools. A higher score means the tool fit your project better, not that it is universally better.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-scorecard" data-builder-scorecard>
                <?php foreach ($builders as $builder) : ?>
                    <article class="kingy-ali-scorecard-card">
                        <h3><?php echo esc_html($builder['name']); ?></h3>
                        <?php foreach (kingy_ali_app_builder_scorecard_criteria() as $key => $label) : ?>
                            <label>
                                <span><?php echo esc_html($label); ?></span>
                                <select data-builder-score="<?php echo esc_attr($builder['name']); ?>" data-score-criterion="<?php echo esc_attr($key); ?>">
                                    <option value="0"><?php esc_html_e('0 - weak', 'kingy-ai-launch-intelligence'); ?></option>
                                    <option value="1"><?php esc_html_e('1 - workable', 'kingy-ai-launch-intelligence'); ?></option>
                                    <option value="2"><?php esc_html_e('2 - strong', 'kingy-ai-launch-intelligence'); ?></option>
                                </select>
                            </label>
                        <?php endforeach; ?>
                        <p class="kingy-ali-scorecard-total"><strong data-builder-score-total="<?php echo esc_attr($builder['name']); ?>">0</strong> / <?php echo esc_html(count(kingy_ali_app_builder_scorecard_criteria()) * 2); ?> <?php esc_html_e('points', 'kingy-ai-launch-intelligence'); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="practical-guide" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Practical guide', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Choose by risk, ownership, and maintenance', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-practical-grid">
                <?php foreach (kingy_ali_app_builder_practical_cards() as $card) : ?>
                    <article class="kingy-ali-practical-card">
                        <h3><?php echo esc_html($card['title']); ?></h3>
                        <p><?php echo esc_html($card['body']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Official and internal links', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Verify current details before acting', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <div class="kingy-ali-link-panel">
                    <h3><?php esc_html_e('Official product pages', 'kingy-ai-launch-intelligence'); ?></h3>
                    <div class="kingy-ali-link-list">
                        <?php foreach ($builders as $builder) : ?>
                            <a href="<?php echo esc_url($builder['url']); ?>" data-kingy-ali-track="clicked_official_tool_link" data-event-label="<?php echo esc_attr($builder['name']); ?>" data-event-surface="builder_resource_links" rel="nofollow noopener" target="_blank"><?php echo esc_html($builder['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="kingy-ali-link-panel">
                    <h3><?php esc_html_e('Keep building on Kingy AI', 'kingy-ai-launch-intelligence'); ?></h3>
                    <div class="kingy-ali-link-list">
                        <a href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/')); ?>"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></a>
                        <a href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/')); ?>"><?php esc_html_e('AI App Builder for Beginners', 'kingy-ai-launch-intelligence'); ?></a>
                        <a href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></a>
                        <a href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/beginner-safety-rules/')); ?>"><?php esc_html_e('Beginner Safety Rules', 'kingy-ai-launch-intelligence'); ?></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="kingy-ali-copy-prompt">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Copy-ready prompt', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Use this for a fair tool test', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <pre><code data-builder-prompt>Build a tiny client-request tracker so I can compare AI app builders fairly.

Scope:
- A form with client name, request, priority, status, and notes.
- A saved list view.
- A detail view.
- A simple edit flow.
- A mobile-friendly layout.

Rules:
- Keep the first version narrow and testable.
- Do not invent pricing or product claims.
- Tell me what data is stored, where it is stored, and how I can export or hand off the project.
- Give me a short QA checklist before I publish.</code></pre>
            <button type="button" data-builder-copy-prompt><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
        </section>

        <section id="faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Common beginner questions', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <details>
                    <summary><?php esc_html_e('Which tool is best overall?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('There is no permanent overall winner. The best choice depends on the app type, data needs, maintenance comfort, budget, and whether you want to own code.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Which is best for beginners?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('For a first visual MVP, start with Lovable or Bolt. If the beginner wants to learn how the app works under the hood, Replit is usually a better learning path.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Which gives the most code ownership?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Replit is the clearest fit in this set when owning and editing code is the priority. Always confirm export and deployment behavior before committing.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Which is best for complex workflows?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Bubble is usually the better fit for multi-step workflows, roles, statuses, and database-backed app logic, provided someone owns the workflow map and privacy rules.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Which is best for portals and directories?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Softr is usually strongest when the project is a structured portal, directory, or member-facing front end over existing data.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Should I test one tool or two?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Test two when the decision matters. Build the same tiny app in both, then compare speed, clarity, data handling, publishing, and handoff instead of judging from screenshots.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Should beginners care about export paths?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Yes. Before committing to a builder, check how you can export code, data, content, or customer records if the project grows beyond the first version.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Is it safe to publish an AI-built app immediately?', 'kingy-ai-launch-intelligence'); ?></summary>
                    <p><?php esc_html_e('Publish small experiments only after checking broken flows, mobile layout, data persistence, privacy, security, and any customer-facing claims.', 'kingy-ai-launch-intelligence'); ?></p>
                </details>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_app_builder_comparison_criteria() {
    return array(
        'best_fit' => __('Best fit', 'kingy-ai-launch-intelligence'),
        'learning_curve' => __('Learning curve', 'kingy-ai-launch-intelligence'),
        'data_backend' => __('Data/backend fit', 'kingy-ai-launch-intelligence'),
        'code_ownership' => __('Code ownership', 'kingy-ai-launch-intelligence'),
        'hosting_deployment' => __('Hosting/deployment', 'kingy-ai-launch-intelligence'),
        'maintenance' => __('Maintenance', 'kingy-ai-launch-intelligence'),
        'export_handoff' => __('Export/handoff', 'kingy-ai-launch-intelligence'),
        'beginner_fit' => __('Beginner fit', 'kingy-ai-launch-intelligence'),
        'production_risk' => __('Production risk', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_app_builder_comparison_tools() {
    return array(
        array(
            'name' => 'Lovable',
            'label' => __('AI product prototype', 'kingy-ai-launch-intelligence'),
            'url' => 'https://lovable.dev/',
            'best_fit' => __('Visual MVPs, SaaS-style prototypes, and fast iteration from plain-language prompts.', 'kingy-ai-launch-intelligence'),
            'learning_curve' => __('Low at the start; review gets harder as logic and data complexity grow.', 'kingy-ai-launch-intelligence'),
            'data_backend' => __('Good when the app can start with a simple database and clear screens.', 'kingy-ai-launch-intelligence'),
            'code_ownership' => __('Useful for product-shaped drafts; verify how much code and structure you can inspect or move.', 'kingy-ai-launch-intelligence'),
            'hosting_deployment' => __('Good for fast hosted prototypes; verify current deployment and domain options.', 'kingy-ai-launch-intelligence'),
            'maintenance' => __('Best when someone reviews generated structure before the app becomes business-critical.', 'kingy-ai-launch-intelligence'),
            'export_handoff' => __('Check export, repository, and handoff path before client or paid work.', 'kingy-ai-launch-intelligence'),
            'beginner_fit' => __('Strong for a first visible MVP if the scope stays narrow.', 'kingy-ai-launch-intelligence'),
            'production_risk' => __('Medium: test auth, data, integrations, and rollback before real users.', 'kingy-ai-launch-intelligence'),
            'summary' => __('Lovable is a strong first stop when the goal is to turn a product idea into a working-looking web app quickly.', 'kingy-ai-launch-intelligence'),
            'test' => __('A two-screen MVP with a form, database table, and dashboard.', 'kingy-ai-launch-intelligence'),
            'verify' => __('Check how the project is hosted, edited, exported, and connected to data.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'name' => 'Replit',
            'label' => __('Code workspace', 'kingy-ai-launch-intelligence'),
            'url' => 'https://replit.com/',
            'best_fit' => __('Learning real code, running full-stack experiments, and keeping more control over implementation.', 'kingy-ai-launch-intelligence'),
            'learning_curve' => __('Moderate: more concepts are visible, which is better for learning but slower at first.', 'kingy-ai-launch-intelligence'),
            'data_backend' => __('Flexible for code-first projects, APIs, scripts, and apps that need custom logic.', 'kingy-ai-launch-intelligence'),
            'code_ownership' => __('Strongest fit here when owning and editing the code matters.', 'kingy-ai-launch-intelligence'),
            'hosting_deployment' => __('Good for code-based apps; verify deployment, environment variables, and database setup.', 'kingy-ai-launch-intelligence'),
            'maintenance' => __('Best if you are willing to understand files, dependencies, logs, and deployments.', 'kingy-ai-launch-intelligence'),
            'export_handoff' => __('Good handoff if the repo, dependencies, and run steps are documented.', 'kingy-ai-launch-intelligence'),
            'beginner_fit' => __('Best for beginners who want durable learning, not only a quick visual result.', 'kingy-ai-launch-intelligence'),
            'production_risk' => __('Medium: generated code still needs security, secrets, and dependency review.', 'kingy-ai-launch-intelligence'),
            'summary' => __('Replit fits builders who want AI help without hiding the code, especially when the project may need custom logic later.', 'kingy-ai-launch-intelligence'),
            'test' => __('A small full-stack tracker with a simple API and persistent records.', 'kingy-ai-launch-intelligence'),
            'verify' => __('Check logs, secrets handling, deployment steps, and how easy it is to move the code elsewhere.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'name' => 'Bolt',
            'label' => __('Fast web app draft', 'kingy-ai-launch-intelligence'),
            'url' => 'https://bolt.new/',
            'best_fit' => __('Quick front-end-heavy apps, prototypes, and web UI experiments from a browser workspace.', 'kingy-ai-launch-intelligence'),
            'learning_curve' => __('Low for UI drafts; moderate once packages, persistence, or custom logic matter.', 'kingy-ai-launch-intelligence'),
            'data_backend' => __('Good for simple app flows; review persistence and integrations for production use.', 'kingy-ai-launch-intelligence'),
            'code_ownership' => __('Better when you inspect generated files and keep the stack understandable.', 'kingy-ai-launch-intelligence'),
            'hosting_deployment' => __('Good for quick browser-based iteration; verify deployment and stack limits.', 'kingy-ai-launch-intelligence'),
            'maintenance' => __('Best when you can inspect generated files and keep the app scope tight.', 'kingy-ai-launch-intelligence'),
            'export_handoff' => __('Check repo/export behavior and whether the app can be maintained outside the builder.', 'kingy-ai-launch-intelligence'),
            'beginner_fit' => __('Strong for quick prototypes and UI-heavy learning projects.', 'kingy-ai-launch-intelligence'),
            'production_risk' => __('Medium: review generated dependencies, persistence, and deployment assumptions.', 'kingy-ai-launch-intelligence'),
            'summary' => __('Bolt is useful when you want to sketch, run, and revise an app quickly, especially for UI-forward builds.', 'kingy-ai-launch-intelligence'),
            'test' => __('A polished single-purpose web tool with form input, generated output, and a copy action.', 'kingy-ai-launch-intelligence'),
            'verify' => __('Check persistence, responsive behavior, and what parts of the stack you can modify directly.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'name' => 'Bubble',
            'label' => __('No-code workflows', 'kingy-ai-launch-intelligence'),
            'url' => 'https://bubble.io/',
            'best_fit' => __('Custom workflows, dashboards, marketplaces, portals, and database-backed web apps.', 'kingy-ai-launch-intelligence'),
            'learning_curve' => __('Moderate to high: powerful, but data, privacy, and workflows need discipline.', 'kingy-ai-launch-intelligence'),
            'data_backend' => __('Strong fit when the data model and workflow rules matter more than code ownership.', 'kingy-ai-launch-intelligence'),
            'code_ownership' => __('Lower than code-first tools; choose it when no-code speed and workflows matter more.', 'kingy-ai-launch-intelligence'),
            'hosting_deployment' => __('Managed app deployment; verify current capacity, performance, and plan constraints.', 'kingy-ai-launch-intelligence'),
            'maintenance' => __('Best when someone owns the Bubble data model, privacy rules, and workflow map.', 'kingy-ai-launch-intelligence'),
            'export_handoff' => __('Handoff depends on Bubble expertise and documentation; verify export constraints early.', 'kingy-ai-launch-intelligence'),
            'beginner_fit' => __('Good for determined beginners building real workflows, but it rewards careful planning.', 'kingy-ai-launch-intelligence'),
            'production_risk' => __('Medium-high if privacy rules, plugin dependencies, and performance are not reviewed.', 'kingy-ai-launch-intelligence'),
            'summary' => __('Bubble is the heavyweight option for no-code apps with real workflows and data relationships.', 'kingy-ai-launch-intelligence'),
            'test' => __('A request portal with users, statuses, admin review, and email notifications.', 'kingy-ai-launch-intelligence'),
            'verify' => __('Check privacy rules, database structure, plugin risk, and long-term ownership.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'name' => 'Softr',
            'label' => __('Portal and directory builder', 'kingy-ai-launch-intelligence'),
            'url' => 'https://www.softr.io/',
            'best_fit' => __('Client portals, internal tools, directories, gated resources, and data-backed front ends.', 'kingy-ai-launch-intelligence'),
            'learning_curve' => __('Low to moderate when the app fits structured blocks and connected data.', 'kingy-ai-launch-intelligence'),
            'data_backend' => __('Good when your data can live in connected sources and the user experience is mostly structured blocks.', 'kingy-ai-launch-intelligence'),
            'code_ownership' => __('Lower code ownership; stronger when the source of truth is your connected data.', 'kingy-ai-launch-intelligence'),
            'hosting_deployment' => __('Managed front-end publishing; verify domains, membership, and data-source support.', 'kingy-ai-launch-intelligence'),
            'maintenance' => __('Best when you want a managed front end over a clear source of truth.', 'kingy-ai-launch-intelligence'),
            'export_handoff' => __('Handoff is strongest when the data source and permissions model are documented.', 'kingy-ai-launch-intelligence'),
            'beginner_fit' => __('Very good for directories, portals, and internal views that match its blocks.', 'kingy-ai-launch-intelligence'),
            'production_risk' => __('Lower for simple portals, higher if permissions or data sync are unclear.', 'kingy-ai-launch-intelligence'),
            'summary' => __('Softr is usually the cleanest path when the app is a portal, listing, or member-facing view over existing data.', 'kingy-ai-launch-intelligence'),
            'test' => __('A niche directory with filters, detail pages, member-only content, and a submit form.', 'kingy-ai-launch-intelligence'),
            'verify' => __('Check data sync, permissions, branding, membership, and export options.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_app_builder_comparison_recommendations() {
    return array(
        'prototype' => array(
            'label' => __('A visual MVP prototype', 'kingy-ai-launch-intelligence'),
            'hint' => __('You need screens fast and can review details later.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Lovable or Bolt',
            'why' => __('They are strong starting points for fast product-shaped prototypes, especially when your first win is seeing the workflow on screen.', 'kingy-ai-launch-intelligence'),
            'next' => __('Keep the scope to two or three screens, then verify hosting, data, and export paths.', 'kingy-ai-launch-intelligence'),
        ),
        'code' => array(
            'label' => __('A code-owned app', 'kingy-ai-launch-intelligence'),
            'hint' => __('You want to learn, inspect, and eventually customize the code.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Replit',
            'why' => __('Replit keeps the files, runtime, logs, and deployment closer to the surface, which is useful when code ownership matters.', 'kingy-ai-launch-intelligence'),
            'next' => __('Build one tiny full-stack app and check whether you understand the file structure before adding features.', 'kingy-ai-launch-intelligence'),
        ),
        'workflow' => array(
            'label' => __('A workflow-heavy product', 'kingy-ai-launch-intelligence'),
            'hint' => __('You need statuses, roles, permissions, and multi-step logic.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Bubble',
            'why' => __('Bubble is often a better fit when workflows and data relationships are the center of the app.', 'kingy-ai-launch-intelligence'),
            'next' => __('Map the database, privacy rules, and plugin dependencies before you build too much UI.', 'kingy-ai-launch-intelligence'),
        ),
        'portal' => array(
            'label' => __('A portal or directory', 'kingy-ai-launch-intelligence'),
            'hint' => __('You already have structured data and need a polished front end.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Softr',
            'why' => __('Softr is a strong fit for member portals, directories, and data-backed pages where the layout can use structured blocks.', 'kingy-ai-launch-intelligence'),
            'next' => __('Confirm your data source, permissions, member flow, and export needs before launch.', 'kingy-ai-launch-intelligence'),
        ),
        'internal' => array(
            'label' => __('An internal tool or client tracker', 'kingy-ai-launch-intelligence'),
            'hint' => __('You need repeatable operations, records, statuses, and light admin views.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Softr, Bubble, or Replit',
            'why' => __('Softr fits structured portals over existing data, Bubble fits custom no-code workflows, and Replit fits teams that want code control.', 'kingy-ai-launch-intelligence'),
            'next' => __('Decide whether the source of truth is a connected database, a no-code app model, or code you can maintain.', 'kingy-ai-launch-intelligence'),
        ),
        'learning' => array(
            'label' => __('A learning project', 'kingy-ai-launch-intelligence'),
            'hint' => __('You care more about understanding the app than shipping the fastest demo.', 'kingy-ai-launch-intelligence'),
            'tool' => 'Replit, then Bolt or Lovable',
            'why' => __('Replit exposes more of the actual app structure, while Bolt or Lovable can help you compare how AI-generated product scaffolds feel.', 'kingy-ai-launch-intelligence'),
            'next' => __('Write down what files, data, secrets, and deployment steps you understand after the first build.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_app_builder_scorecard_criteria() {
    return array(
        'speed' => __('Speed to first working version', 'kingy-ai-launch-intelligence'),
        'clarity' => __('I understand how it works', 'kingy-ai-launch-intelligence'),
        'data' => __('Data and permissions are clear', 'kingy-ai-launch-intelligence'),
        'mobile' => __('Mobile layout works', 'kingy-ai-launch-intelligence'),
        'handoff' => __('Export or handoff path is acceptable', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_app_builder_practical_cards() {
    return array(
        array(
            'title' => __('Best for fast MVPs', 'kingy-ai-launch-intelligence'),
            'body' => __('Use Lovable or Bolt when you need a visible product draft quickly and the first version can stay narrow.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Avoid if the data model is fuzzy', 'kingy-ai-launch-intelligence'),
            'body' => __('Do not scale a generated app until you can explain the records, permissions, backups, and what happens when a user edits data.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Verify before paying', 'kingy-ai-launch-intelligence'),
            'body' => __('Check official pricing, plan limits, export paths, domains, collaborators, database support, and whether your required integrations are included.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Verify before publishing', 'kingy-ai-launch-intelligence'),
            'body' => __('Test mobile layout, empty states, bad inputs, auth, secrets, privacy rules, rollback, analytics, and every public claim on the page.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Choose Replit for code ownership', 'kingy-ai-launch-intelligence'),
            'body' => __('If you want to learn, debug, move, or extend the app as code, Replit is usually the more durable starting point in this group.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'title' => __('Choose for client or business work', 'kingy-ai-launch-intelligence'),
            'body' => __('For clients, prefer the tool with the clearest maintenance owner: documented Bubble workflows, Softr data sources, or a Replit codebase someone can run.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_shortcode_safe_ai_agent_guide() {
    kingy_ali_enqueue_assets();

    $paths = kingy_ali_safe_ai_agent_paths();
    $steps = kingy_ali_safe_ai_agent_steps();
    $examples = kingy_ali_safe_ai_agent_examples();
    $risks = kingy_ali_safe_ai_agent_risks();
    $glossary = kingy_ali_safe_ai_agent_glossary();
    $faqs = kingy_ali_safe_ai_agent_faqs();
    $resources = kingy_ali_safe_ai_agent_resources();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-agent-guide" data-kingy-safe-agent-guide>
        <header class="kingy-ali-academy-hero kingy-ali-agent-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('How to Build an AI Agent Safely', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('A practical guide and toolkit for building one narrow AI worker you can scope, test, supervise, and improve without handing a model too much authority too soon.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Evaluate my agent idea', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="safe_agent_hero" href="#agent-evaluator"><?php esc_html_e('Evaluate my idea', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Build safe agent brief', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="safe_agent_hero" href="#agent-brief-builder"><?php esc_html_e('Build my brief', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Generate test plan', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="safe_agent_hero" href="#agent-test-plan"><?php esc_html_e('Generate tests', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('Quick answer', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A safe AI agent is not "a prompt with vibes." It is a narrow workflow with known inputs, allowed tools, forbidden actions, approval gates, tests, logs, and a rollback path.', 'kingy-ai-launch-intelligence'); ?></p>
                <p>
                    <?php esc_html_e('For the broader rollout plan, use Kingy\'s ', 'kingy-ai-launch-intelligence'); ?>
                    <a href="<?php echo esc_url(home_url('/ai/ai-agent-adoption-playbook/')); ?>"><?php esc_html_e('AI agent adoption playbook', 'kingy-ai-launch-intelligence'); ?></a>
                    <?php esc_html_e(' to map permissions, review gates, QA, costs, and a 30-day supervised launch.', 'kingy-ai-launch-intelligence'); ?>
                </p>
                <ul>
                    <li><?php esc_html_e('Start with one repeatable job, not an open-ended assistant.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Give read-only or draft-only access before autonomous actions.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Require human approval for publishing, sending, deleting, buying, or changing production data.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Safe AI agent guide sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#agent-paths"><?php esc_html_e('Paths', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-evaluator"><?php esc_html_e('Evaluator', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-brief-builder"><?php esc_html_e('Brief', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-permissions"><?php esc_html_e('Permissions', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-test-plan"><?php esc_html_e('Tests', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-readiness"><?php esc_html_e('Checklist', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-process"><?php esc_html_e('Process', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-red-team"><?php esc_html_e('Red team', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#agent-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="agent-paths" class="kingy-ali-builder-chooser kingy-ali-agent-paths">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Choose your path', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('The safest first agent is usually smaller than the idea in your head', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Pick the path closest to your current skill level and risk tolerance. The goal is a useful first version you can inspect before it touches real work.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('AI agent build paths', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($paths as $key => $path) : ?>
                    <button type="button" class="kingy-ali-choice-button" data-agent-path="<?php echo esc_attr($key); ?>" data-agent-path-payload="<?php echo esc_attr(wp_json_encode($path)); ?>">
                        <strong><?php echo esc_html($path['label']); ?></strong>
                        <span><?php echo esc_html($path['hint']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result" data-agent-path-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Start narrow, then earn more autonomy', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Choose a path above to see a safe first version, what to avoid, and what to test before expanding.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="agent-evaluator" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Agent idea evaluator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Should this task become an agent?', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Describe the job and select what the agent might touch. The evaluator returns a risk tier, safer first version, approval rule, and next step.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-agent-tool kingy-ali-agent-evaluator">
                <form data-agent-evaluator-form>
                    <label>
                        <span><?php esc_html_e('Task you want help with', 'kingy-ai-launch-intelligence'); ?></span>
                        <textarea rows="4" data-agent-evaluator-field="task" placeholder="<?php esc_attr_e('Example: review creator campaign briefs and draft a reply with next steps', 'kingy-ai-launch-intelligence'); ?>"></textarea>
                    </label>
                    <div class="kingy-ali-agent-check-grid" role="group" aria-label="<?php esc_attr_e('Agent idea risk signals', 'kingy-ai-launch-intelligence'); ?>">
                        <label><input type="checkbox" data-agent-evaluator-risk value="private_data"> <?php esc_html_e('Uses private or customer data', 'kingy-ai-launch-intelligence'); ?></label>
                        <label><input type="checkbox" data-agent-evaluator-risk value="external_action"> <?php esc_html_e('Sends, publishes, buys, deletes, or edits', 'kingy-ai-launch-intelligence'); ?></label>
                        <label><input type="checkbox" data-agent-evaluator-risk value="logged_in"> <?php esc_html_e('Needs logged-in browser or account access', 'kingy-ai-launch-intelligence'); ?></label>
                        <label><input type="checkbox" data-agent-evaluator-risk value="secrets"> <?php esc_html_e('Needs API keys or secrets', 'kingy-ai-launch-intelligence'); ?></label>
                        <label><input type="checkbox" data-agent-evaluator-risk value="production"> <?php esc_html_e('Touches production systems', 'kingy-ai-launch-intelligence'); ?></label>
                        <label><input type="checkbox" data-agent-evaluator-risk value="money"> <?php esc_html_e('Involves money, legal, health, or safety decisions', 'kingy-ai-launch-intelligence'); ?></label>
                    </div>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Evaluate Idea', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-agent-output" data-agent-evaluator-output aria-live="polite">
                    <p class="kingy-ali-kicker"><?php esc_html_e('Result', 'kingy-ai-launch-intelligence'); ?></p>
                    <h3><?php esc_html_e('Your risk readout appears here', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Start by describing a specific task and checking any systems the agent might touch.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <section id="agent-brief-builder" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Safe agent brief builder', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Generate the brief before you build', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A good agent brief defines the role, job, inputs, tools, forbidden actions, approval step, output format, and done criteria before any tool access is granted.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-agent-tool kingy-ali-agent-brief">
                <form data-agent-brief-form>
                    <div class="kingy-ali-agent-form-grid">
                        <?php foreach (kingy_ali_safe_ai_agent_brief_fields() as $key => $field) : ?>
                            <label class="<?php echo !empty($field['wide']) ? 'kingy-ali-field--textarea' : ''; ?>">
                                <span><?php echo esc_html($field['label']); ?></span>
                                <?php if (!empty($field['textarea'])) : ?>
                                    <textarea rows="<?php echo esc_attr($field['rows']); ?>" data-agent-brief-field="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>"></textarea>
                                <?php else : ?>
                                    <input type="text" data-agent-brief-field="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Generate Brief', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-agent-output">
                    <label for="kingy-agent-brief-output"><?php esc_html_e('Copy-ready safe agent prompt', 'kingy-ai-launch-intelligence'); ?></label>
                    <textarea id="kingy-agent-brief-output" rows="16" readonly data-agent-brief-output></textarea>
                    <button type="button" data-agent-copy-output="#kingy-agent-brief-output"><?php esc_html_e('Copy Brief', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="agent-permissions" class="kingy-ali-test-project kingy-ali-agent-permissions" data-agent-permission-calculator>
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Permission risk calculator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Score the authority before you connect tools', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Tool access is where many agent projects become risky. Check the capabilities you are considering, then use the mitigation list before moving beyond draft-only work.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-agent-check-grid">
                    <?php foreach (kingy_ali_safe_ai_agent_permission_options() as $key => $option) : ?>
                        <label><input type="checkbox" data-agent-permission-risk="<?php echo esc_attr($key); ?>" data-agent-permission-weight="<?php echo esc_attr($option['weight']); ?>"> <?php echo esc_html($option['label']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="kingy-ali-checklist kingy-ali-agent-risk-output" aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Risk tier', 'kingy-ai-launch-intelligence'); ?></p>
                <h3 data-agent-permission-tier><?php esc_html_e('Low risk', 'kingy-ai-launch-intelligence'); ?></h3>
                <p data-agent-permission-summary><?php esc_html_e('Draft-only work with public or sample data is usually safe enough for a beginner to test manually.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul data-agent-permission-mitigations>
                    <li><?php esc_html_e('Use sample data first.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Keep the agent draft-only until tests pass.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Write a rollback path before real use.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </div>
        </section>

        <section id="agent-test-plan" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Test plan generator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Test the agent like a workflow, not a demo', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Choose an agent type and generate test cases for normal use, bad input, prompt injection, privacy, permissions, hallucinations, and rollback.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-agent-tool kingy-ali-agent-test-plan">
                <form data-agent-test-form>
                    <div class="kingy-ali-agent-form-grid">
                        <label>
                            <span><?php esc_html_e('Agent type', 'kingy-ai-launch-intelligence'); ?></span>
                            <select data-agent-test-field="type">
                                <?php foreach ($examples as $key => $example) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($example['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span><?php esc_html_e('What the agent should do', 'kingy-ai-launch-intelligence'); ?></span>
                            <input type="text" data-agent-test-field="task" placeholder="<?php esc_attr_e('Example: draft a creator campaign reply from a brief', 'kingy-ai-launch-intelligence'); ?>">
                        </label>
                    </div>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Generate Test Plan', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-agent-output">
                    <label for="kingy-agent-test-output"><?php esc_html_e('Copy-ready safe agent test plan', 'kingy-ai-launch-intelligence'); ?></label>
                    <textarea id="kingy-agent-test-output" rows="14" readonly data-agent-test-output></textarea>
                    <button type="button" data-agent-copy-output="[data-agent-test-output]"><?php esc_html_e('Copy Test Plan', 'kingy-ai-launch-intelligence'); ?></button>
                </div>
            </div>
        </section>

        <section id="agent-readiness" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Progress checklist', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Do not use the agent on real work until these are true', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-codex-checklist kingy-ali-agent-readiness" data-agent-readiness>
                <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                    <strong><span data-agent-readiness-count>0</span> / <?php echo esc_html(count(kingy_ali_safe_ai_agent_launch_checks())); ?></strong>
                    <span data-agent-readiness-status><?php esc_html_e('Planning mode', 'kingy-ai-launch-intelligence'); ?></span>
                    <progress max="<?php echo esc_attr(count(kingy_ali_safe_ai_agent_launch_checks())); ?>" value="0" data-agent-readiness-progress></progress>
                </div>
                <div class="kingy-ali-codex-checklist__items">
                    <?php foreach (kingy_ali_safe_ai_agent_launch_checks() as $index => $check) : ?>
                        <?php $check_id = 'kingy-agent-readiness-' . absint($index); ?>
                        <label for="<?php echo esc_attr($check_id); ?>">
                            <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-agent-readiness-check>
                            <span><?php echo esc_html($check); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="kingy-ali-codex-secondary" data-agent-readiness-reset><?php esc_html_e('Reset Checklist', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
        </section>

        <section id="agent-decision-tree" class="kingy-ali-builder-chooser kingy-ali-agent-decision-tree">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Decision tree', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Should this be a prompt, checklist, script, automation, workflow, or agent?', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-practical-grid">
                <?php foreach (kingy_ali_safe_ai_agent_decision_cards() as $card) : ?>
                    <article class="kingy-ali-practical-card">
                        <h3><?php echo esc_html($card['title']); ?></h3>
                        <p><?php echo esc_html($card['body']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-process" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Safety-first build process', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('The 11-step beginner framework', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-agent-step-grid">
                <?php foreach ($steps as $index => $step) : ?>
                    <article class="kingy-ali-agent-step">
                        <span><?php echo esc_html(absint($index) + 1); ?></span>
                        <h3><?php echo esc_html($step['title']); ?></h3>
                        <p><?php echo esc_html($step['body']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Example library', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Six safe first-agent patterns', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-example-filter" role="group" aria-label="<?php esc_attr_e('Filter safe agent examples by risk', 'kingy-ai-launch-intelligence'); ?>">
                <button type="button" class="is-active" data-agent-example-filter="all"><?php esc_html_e('All', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-agent-example-filter="low"><?php esc_html_e('Low', 'kingy-ai-launch-intelligence'); ?></button>
                <button type="button" data-agent-example-filter="medium"><?php esc_html_e('Medium', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
            <div class="kingy-ali-builder-card-grid kingy-ali-agent-example-grid">
                <?php foreach ($examples as $key => $example) : ?>
                    <article class="kingy-ali-builder-card kingy-ali-agent-example-card" data-agent-example-risk="<?php echo esc_attr($example['risk_key']); ?>">
                        <div class="kingy-ali-builder-card__header">
                            <h3><?php echo esc_html($example['title']); ?></h3>
                            <span><?php echo esc_html($example['risk']); ?></span>
                        </div>
                        <p><?php echo esc_html($example['summary']); ?></p>
                        <dl>
                            <div><dt><?php esc_html_e('Safe inputs', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['inputs']); ?></dd></div>
                            <div><dt><?php esc_html_e('Allowed actions', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['allowed']); ?></dd></div>
                            <div><dt><?php esc_html_e('Forbidden actions', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['forbidden']); ?></dd></div>
                            <div><dt><?php esc_html_e('Ready when', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['ready']); ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-risks" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Risk library', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('What to guard before launch', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($risks as $risk) : ?>
                    <div class="kingy-ali-link-panel">
                        <h3><?php echo esc_html($risk['title']); ?></h3>
                        <p><?php echo esc_html($risk['body']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-red-team" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Red-team drills', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Try to break the agent before real users do', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A safe agent should fail politely, ask for review, or stop when the request crosses its rules. Use these drills as a quick pre-launch review.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach (kingy_ali_safe_ai_agent_red_team_drills() as $drill) : ?>
                    <div class="kingy-ali-link-panel">
                        <h3><?php echo esc_html($drill['question']); ?></h3>
                        <p><strong><?php esc_html_e('Failure example:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($drill['failure']); ?></p>
                        <p><strong><?php esc_html_e('Safe behavior:', 'kingy-ai-launch-intelligence'); ?></strong> <?php echo esc_html($drill['safe']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-templates" class="kingy-ali-copy-prompt kingy-ali-agent-template-pack">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Copy-ready template', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Starter safe-agent prompt', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <pre><code id="kingy-agent-starter-template">Before building, help me write a safe AI worker brief.

Include:
- Role
- Goal
- Audience
- Inputs
- Data sources
- Allowed tools
- Allowed actions
- Forbidden actions
- Output format
- Human approval step
- Tests
- Done criteria
- Rollback path

Rules:
- Keep version one narrow and reviewable.
- Use sample data before real data.
- Do not send, publish, delete, buy, or change production data without human approval.
- Explain the safest smaller first version if my idea is too broad.</code></pre>
            <button type="button" data-agent-copy-text="#kingy-agent-starter-template"><?php esc_html_e('Copy Template', 'kingy-ai-launch-intelligence'); ?></button>
        </section>

        <section id="agent-glossary" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Beginner glossary', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Terms that matter before you build', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <dl class="kingy-ali-agent-glossary">
                <?php foreach ($glossary as $term => $definition) : ?>
                    <div><dt><?php echo esc_html($term); ?></dt><dd><?php echo esc_html($definition); ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </section>

        <section id="agent-resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Related Academy assets', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Keep building with guardrails', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($resources as $resource) : ?>
                    <a class="kingy-ali-codex-resource" data-kingy-ali-track="clicked_safe_agent_resource" data-event-label="<?php echo esc_attr($resource['label']); ?>" data-event-surface="safe_agent_resources" href="<?php echo esc_url($resource['url']); ?>">
                        <strong><?php echo esc_html($resource['label']); ?></strong>
                        <span><?php echo esc_html($resource['description']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="agent-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Common beginner questions', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details>
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_safe_ai_agent_paths() {
    return array(
        'prompt' => array(
            'label' => __('Prompt-only AI worker', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best first step when the output is a draft you will review.', 'kingy-ai-launch-intelligence'),
            'start' => __('A reusable prompt that produces drafts, summaries, checklists, or recommendations from pasted safe inputs.', 'kingy-ai-launch-intelligence'),
            'avoid' => __('Avoid tool access, private data, and automatic sending until the prompt is reliable.', 'kingy-ai-launch-intelligence'),
            'test' => __('Test five real-ish examples, one blank input, one confusing input, and one malicious instruction.', 'kingy-ai-launch-intelligence'),
        ),
        'no_code' => array(
            'label' => __('No-code agent', 'kingy-ai-launch-intelligence'),
            'hint' => __('Useful for structured forms, drafts, notifications, and repeatable handoffs.', 'kingy-ai-launch-intelligence'),
            'start' => __('A draft-only workflow with sample data, clear approvals, and an owner who reviews every output.', 'kingy-ai-launch-intelligence'),
            'avoid' => __('Avoid connecting customer records or production automations until permissions and rollback are documented.', 'kingy-ai-launch-intelligence'),
            'test' => __('Test form validation, bad inputs, duplicate runs, notification failures, and manual override.', 'kingy-ai-launch-intelligence'),
        ),
        'browser' => array(
            'label' => __('Browser agent', 'kingy-ai-launch-intelligence'),
            'hint' => __('Good for research and comparison tasks, risky for logged-in actions.', 'kingy-ai-launch-intelligence'),
            'start' => __('A read-only research workflow that summarizes public pages and cites sources before any account login.', 'kingy-ai-launch-intelligence'),
            'avoid' => __('Avoid logged-in purchasing, account edits, publishing, or form submissions without human confirmation.', 'kingy-ai-launch-intelligence'),
            'test' => __('Test source quality, blocked pages, conflicting pages, malicious page text, and citation accuracy.', 'kingy-ai-launch-intelligence'),
        ),
        'code' => array(
            'label' => __('Coding/API agent', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when you can inspect files, logs, secrets, tests, and deployment steps.', 'kingy-ai-launch-intelligence'),
            'start' => __('A small local tool with explicit functions, typed inputs, fake credentials, and test fixtures.', 'kingy-ai-launch-intelligence'),
            'avoid' => __('Avoid broad filesystem access, real API keys, and production deploys until tests and reviews pass.', 'kingy-ai-launch-intelligence'),
            'test' => __('Test function arguments, permission failures, secrets handling, retries, logs, and rollback.', 'kingy-ai-launch-intelligence'),
        ),
        'internal' => array(
            'label' => __('Internal business agent', 'kingy-ai-launch-intelligence'),
            'hint' => __('Useful after a team agrees on data access, approvals, and ownership.', 'kingy-ai-launch-intelligence'),
            'start' => __('A supervised workflow for one team, one system, one output, and one clear escalation path.', 'kingy-ai-launch-intelligence'),
            'avoid' => __('Avoid cross-system autonomy, sensitive decisions, and unclear accountability.', 'kingy-ai-launch-intelligence'),
            'test' => __('Test role permissions, audit logs, data minimization, user handoff, and incident response.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_safe_ai_agent_brief_fields() {
    return array(
        'role' => array('label' => __('Agent role', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: creator campaign brief reviewer')),
        'goal' => array('label' => __('Goal', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: draft a reply and next-step checklist')),
        'audience' => array('label' => __('User or reviewer', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: creator partnerships manager')),
        'inputs' => array('label' => __('Inputs', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: pasted creator campaign brief, sample policy, public product URL'), 'textarea' => true, 'rows' => 3, 'wide' => true),
        'sources' => array('label' => __('Data sources', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: public pages only; no private CRM on version one'), 'textarea' => true, 'rows' => 3, 'wide' => true),
        'tools' => array('label' => __('Tools', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: browser research, text drafting, no email send'), 'textarea' => true, 'rows' => 3, 'wide' => true),
        'allowed' => array('label' => __('Allowed actions', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: summarize, classify, draft, cite, suggest'), 'textarea' => true, 'rows' => 3, 'wide' => true),
        'forbidden' => array('label' => __('Forbidden actions', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: send email, approve deal, use secrets, change records'), 'textarea' => true, 'rows' => 3, 'wide' => true),
        'output' => array('label' => __('Output format', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: risk rating, draft reply, checklist, questions')),
        'approval' => array('label' => __('Human approval step', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: human reviews before sending or logging anything')),
        'done' => array('label' => __('Done criteria', 'kingy-ai-launch-intelligence'), 'placeholder' => __('Example: output cites sources, flags uncertainty, passes test cases'), 'textarea' => true, 'rows' => 3, 'wide' => true),
    );
}

function kingy_ali_safe_ai_agent_permission_options() {
    return array(
        'browser' => array('label' => __('Browser access', 'kingy-ai-launch-intelligence'), 'weight' => 1),
        'email' => array('label' => __('Email or calendar access', 'kingy-ai-launch-intelligence'), 'weight' => 3),
        'files' => array('label' => __('Local or shared files', 'kingy-ai-launch-intelligence'), 'weight' => 2),
        'payments' => array('label' => __('Payments, purchasing, or refunds', 'kingy-ai-launch-intelligence'), 'weight' => 4),
        'publishing' => array('label' => __('Publishing or public posting', 'kingy-ai-launch-intelligence'), 'weight' => 3),
        'private_data' => array('label' => __('Private, customer, or employee data', 'kingy-ai-launch-intelligence'), 'weight' => 3),
        'api_keys' => array('label' => __('API keys, tokens, or secrets', 'kingy-ai-launch-intelligence'), 'weight' => 4),
        'production' => array('label' => __('Production systems or databases', 'kingy-ai-launch-intelligence'), 'weight' => 4),
    );
}

function kingy_ali_safe_ai_agent_steps() {
    return array(
        array('title' => __('Pick one narrow job', 'kingy-ai-launch-intelligence'), 'body' => __('Choose a repeatable task with a clear reviewer and obvious success criteria.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Do it manually first', 'kingy-ai-launch-intelligence'), 'body' => __('Write down each step, decision, input, exception, and handoff before automating.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Define inputs and outputs', 'kingy-ai-launch-intelligence'), 'body' => __('Name exactly what the agent receives and what it must produce.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Choose tools and data', 'kingy-ai-launch-intelligence'), 'body' => __('Start with public or sample data and draft-only tools whenever possible.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Set permissions', 'kingy-ai-launch-intelligence'), 'body' => __('Give the smallest access needed and keep dangerous actions behind approval.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Write the instructions', 'kingy-ai-launch-intelligence'), 'body' => __('Include role, goal, allowed actions, forbidden actions, output format, and uncertainty rules.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Add memory only if needed', 'kingy-ai-launch-intelligence'), 'body' => __('Use memory only when it improves a specific workflow and can be reviewed or cleared.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Test edge cases', 'kingy-ai-launch-intelligence'), 'body' => __('Test normal, empty, messy, malicious, private, and conflicting inputs.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Require approval gates', 'kingy-ai-launch-intelligence'), 'body' => __('Humans approve sends, publishes, purchases, deletions, and production changes.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Monitor and log', 'kingy-ai-launch-intelligence'), 'body' => __('Track inputs, outputs, failures, reviewer edits, and cost before expanding scope.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Document rollback', 'kingy-ai-launch-intelligence'), 'body' => __('Write how to pause the agent, undo outputs, rotate secrets, and notify owners.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_safe_ai_agent_examples() {
    return array(
        'support' => array('title' => __('Support triage agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Medium', 'kingy-ai-launch-intelligence'), 'risk_key' => 'medium', 'summary' => __('Sorts incoming support notes into categories and drafts suggested replies.', 'kingy-ai-launch-intelligence'), 'inputs' => __('Sample tickets or redacted messages.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Classify, summarize, draft, flag urgency.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Send replies, promise refunds, change accounts.', 'kingy-ai-launch-intelligence'), 'ready' => __('It matches human categories and flags uncertainty instead of guessing.', 'kingy-ai-launch-intelligence')),
        'research' => array('title' => __('Research brief agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Low-medium', 'kingy-ai-launch-intelligence'), 'risk_key' => 'medium', 'summary' => __('Turns public web research into a source-backed brief.', 'kingy-ai-launch-intelligence'), 'inputs' => __('Topic, source list, public URLs.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Read, compare, summarize, cite.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Invent sources, scrape logged-in pages, make current claims without sources.', 'kingy-ai-launch-intelligence'), 'ready' => __('Every important claim links to a source and uncertainty is visible.', 'kingy-ai-launch-intelligence')),
        'meeting' => array('title' => __('Meeting-notes agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Medium', 'kingy-ai-launch-intelligence'), 'risk_key' => 'medium', 'summary' => __('Turns pasted notes into recap, decisions, owners, risks, and next steps.', 'kingy-ai-launch-intelligence'), 'inputs' => __('Transcript or notes with sensitive details removed when possible.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Summarize, extract actions, draft follow-up.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Send calendar invites or emails without approval.', 'kingy-ai-launch-intelligence'), 'ready' => __('Owners, dates, and decisions match the source notes.', 'kingy-ai-launch-intelligence')),
        'content' => array('title' => __('Content QA agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Low', 'kingy-ai-launch-intelligence'), 'risk_key' => 'low', 'summary' => __('Checks draft content for claims, links, tone, accessibility, and SEO basics.', 'kingy-ai-launch-intelligence'), 'inputs' => __('Draft page, target query, internal links.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Flag issues, suggest edits, generate checklist.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Publish changes or fabricate product facts.', 'kingy-ai-launch-intelligence'), 'ready' => __('It finds seeded issues and separates facts from suggestions.', 'kingy-ai-launch-intelligence')),
        'spreadsheet' => array('title' => __('Spreadsheet cleanup agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Medium', 'kingy-ai-launch-intelligence'), 'risk_key' => 'medium', 'summary' => __('Suggests cleanup, dedupe, labels, and formulas for a sheet.', 'kingy-ai-launch-intelligence'), 'inputs' => __('CSV copy or sample rows.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Suggest transformations and draft formulas.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Overwrite source data without backup and approval.', 'kingy-ai-launch-intelligence'), 'ready' => __('It preserves original rows and explains every transformation.', 'kingy-ai-launch-intelligence')),
        'sponsor' => array('title' => __('Creator campaign brief agent', 'kingy-ai-launch-intelligence'), 'risk' => __('Medium', 'kingy-ai-launch-intelligence'), 'risk_key' => 'medium', 'summary' => __('Reviews a creator campaign brief and drafts fit notes, questions, and reply copy.', 'kingy-ai-launch-intelligence'), 'inputs' => __('Brief, public product URL, creator fit criteria.', 'kingy-ai-launch-intelligence'), 'allowed' => __('Score fit, draft questions, cite concerns.', 'kingy-ai-launch-intelligence'), 'forbidden' => __('Accept campaign terms, send terms, make legal or financial commitments.', 'kingy-ai-launch-intelligence'), 'ready' => __('It separates confirmed facts from fit assumptions and asks for missing details.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_safe_ai_agent_launch_checks() {
    return array(
        __('The agent has one narrow job and one human owner.', 'kingy-ai-launch-intelligence'),
        __('Inputs, outputs, sources, tools, and forbidden actions are written down.', 'kingy-ai-launch-intelligence'),
        __('Version one uses sample, public, redacted, or draft-only data.', 'kingy-ai-launch-intelligence'),
        __('Sending, publishing, deleting, buying, and production changes require approval.', 'kingy-ai-launch-intelligence'),
        __('Prompt injection and malicious input tests pass.', 'kingy-ai-launch-intelligence'),
        __('Privacy, secrets, cost, and permission failure tests pass.', 'kingy-ai-launch-intelligence'),
        __('The output flags uncertainty and does not invent missing facts.', 'kingy-ai-launch-intelligence'),
        __('A rollback, pause, and access-revocation path is documented.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_safe_ai_agent_risks() {
    return array(
        array('title' => __('Prompt injection', 'kingy-ai-launch-intelligence'), 'body' => __('Treat web pages, emails, documents, and user text as untrusted. The agent should ignore instructions inside sources that conflict with its system and tool rules.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Tool permissions', 'kingy-ai-launch-intelligence'), 'body' => __('Most risk comes from what the agent can do. Start read-only, draft-only, or sandboxed, then add authority one permission at a time.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Secrets and API keys', 'kingy-ai-launch-intelligence'), 'body' => __('Never paste secrets into prompts. Use environment variables, rotate exposed keys, and test with fake credentials first.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Private data', 'kingy-ai-launch-intelligence'), 'body' => __('Minimize data, redact what is not needed, and avoid sending sensitive customer, employee, health, legal, or financial details to tools that do not need them.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Browser automation', 'kingy-ai-launch-intelligence'), 'body' => __('Browser agents are useful for research but risky when logged in. Require confirmation before submitting forms, changing settings, or purchasing.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Hallucinations', 'kingy-ai-launch-intelligence'), 'body' => __('Require citations, uncertainty labels, and reviewer checks for factual, current, legal, medical, financial, or customer-facing output.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Cost runaway', 'kingy-ai-launch-intelligence'), 'body' => __('Set usage limits, stop conditions, retry caps, and alerts before the agent can loop through large files, APIs, or web pages.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Over-automation', 'kingy-ai-launch-intelligence'), 'body' => __('If the workflow is rare, high-stakes, or hard to review, a checklist or draft prompt may be safer than an autonomous agent.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_safe_ai_agent_red_team_drills() {
    return array(
        array(
            'question' => __('What happens if a source tells the agent to ignore its rules?', 'kingy-ai-launch-intelligence'),
            'failure' => __('The agent follows instructions hidden inside a webpage, email, or document.', 'kingy-ai-launch-intelligence'),
            'safe' => __('It treats source text as untrusted data and follows only the agent brief and tool rules.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What happens if the user asks for a forbidden action?', 'kingy-ai-launch-intelligence'),
            'failure' => __('The agent sends, publishes, deletes, buys, or changes production data without review.', 'kingy-ai-launch-intelligence'),
            'safe' => __('It drafts the action, explains the risk, and waits for human approval.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What happens when the facts are missing or contradictory?', 'kingy-ai-launch-intelligence'),
            'failure' => __('The agent invents a source, price, policy, promise, or decision to look complete.', 'kingy-ai-launch-intelligence'),
            'safe' => __('It says what is unknown, cites what is known, and asks a specific follow-up question.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What happens if private data is pasted into the input?', 'kingy-ai-launch-intelligence'),
            'failure' => __('The agent stores, repeats, exports, or sends sensitive details it did not need.', 'kingy-ai-launch-intelligence'),
            'safe' => __('It minimizes or redacts unnecessary sensitive data and warns the reviewer before use.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_safe_ai_agent_decision_cards() {
    return array(
        array('title' => __('Use a prompt', 'kingy-ai-launch-intelligence'), 'body' => __('Best when a human can paste context, review the answer, and no tool access is needed.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Use a checklist', 'kingy-ai-launch-intelligence'), 'body' => __('Best when the task is mostly human judgment and consistency matters more than speed.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Use a script', 'kingy-ai-launch-intelligence'), 'body' => __('Best when the steps are deterministic, such as formatting, moving files, or cleaning repeated data.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Use automation', 'kingy-ai-launch-intelligence'), 'body' => __('Best when a trigger reliably leads to the same action and exceptions are rare.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Use a workflow', 'kingy-ai-launch-intelligence'), 'body' => __('Best when humans and tools pass work through clear stages with approvals.', 'kingy-ai-launch-intelligence')),
        array('title' => __('Use an agent', 'kingy-ai-launch-intelligence'), 'body' => __('Best when the task needs language judgment, tool selection, and supervised action across a repeatable workflow.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_safe_ai_agent_glossary() {
    return array(
        __('Agent', 'kingy-ai-launch-intelligence') => __('An AI system that can plan steps, use tools, and produce results toward a goal.', 'kingy-ai-launch-intelligence'),
        __('Tool call', 'kingy-ai-launch-intelligence') => __('A structured request from the model to use a function, API, browser, file, or other capability.', 'kingy-ai-launch-intelligence'),
        __('Memory', 'kingy-ai-launch-intelligence') => __('Stored context the system may reuse across turns or runs.', 'kingy-ai-launch-intelligence'),
        __('Context', 'kingy-ai-launch-intelligence') => __('The information available to the model for this task.', 'kingy-ai-launch-intelligence'),
        __('MCP', 'kingy-ai-launch-intelligence') => __('Model Context Protocol, a way for AI apps to connect to tools and data sources.', 'kingy-ai-launch-intelligence'),
        __('API key', 'kingy-ai-launch-intelligence') => __('A secret credential that lets software access a service. Treat it like a password.', 'kingy-ai-launch-intelligence'),
        __('RAG', 'kingy-ai-launch-intelligence') => __('Retrieval-augmented generation: fetching relevant source material before generating an answer.', 'kingy-ai-launch-intelligence'),
        __('Guardrail', 'kingy-ai-launch-intelligence') => __('A rule, check, permission limit, or approval that reduces unsafe behavior.', 'kingy-ai-launch-intelligence'),
        __('Eval', 'kingy-ai-launch-intelligence') => __('A repeatable test that checks whether the agent behaves correctly.', 'kingy-ai-launch-intelligence'),
        __('Human-in-the-loop', 'kingy-ai-launch-intelligence') => __('A human reviews or approves important steps before the agent acts.', 'kingy-ai-launch-intelligence'),
        __('Sandbox', 'kingy-ai-launch-intelligence') => __('A safe test environment separated from real users, money, private data, or production systems.', 'kingy-ai-launch-intelligence'),
        __('Rollback', 'kingy-ai-launch-intelligence') => __('The plan for undoing or stopping the agent if something goes wrong.', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_safe_ai_agent_resources() {
    return array(
        array('label' => __('Build With AI Academy', 'kingy-ai-launch-intelligence'), 'description' => __('Return to the beginner learning path.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/')),
        array('label' => __('Build With AI Academy Toolkit', 'kingy-ai-launch-intelligence'), 'description' => __('Use checklists, prompt packs, and QA assets.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/build-ai-academy-toolkit/')),
        array('label' => __('Beginner Safety Rules', 'kingy-ai-launch-intelligence'), 'description' => __('Review privacy, secrets, publishing, and approval guardrails.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/beginner-safety-rules/')),
        array('label' => __('Prompt Library', 'kingy-ai-launch-intelligence'), 'description' => __('Find reusable prompts for scoping, testing, and improving AI builds.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/templates/')),
        array('label' => __('AI Agents for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Build a first AI worker without over-automating.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai-agents-for-beginners-build-your-first-ai-worker-without-coding/')),
        array('label' => __('AI Browser Agents for Beginners', 'kingy-ai-launch-intelligence'), 'description' => __('Use browser agents safely for public web tasks.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/ai-browser-agents-for-beginners/')),
        array('label' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'), 'description' => __('Turn this brief into a scoped Codex prompt.', 'kingy-ai-launch-intelligence'), 'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')),
    );
}

function kingy_ali_safe_ai_agent_faqs() {
    return array(
        array('question' => __('How do I build an AI agent safely as a beginner?', 'kingy-ai-launch-intelligence'), 'answer' => __('Start with one narrow, repeatable job; use sample or public data; keep the output draft-only; write forbidden actions; test edge cases; and require human approval before real actions.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is the safest first AI agent project?', 'kingy-ai-launch-intelligence'), 'answer' => __('A draft-only worker is safest: summarize notes, classify support messages, generate a research brief, or create a content QA checklist that a human reviews.', 'kingy-ai-launch-intelligence')),
        array('question' => __('When should I not build an AI agent?', 'kingy-ai-launch-intelligence'), 'answer' => __('Do not build an agent when the task is rare, high-stakes, hard to review, deterministic enough for a script, or dependent on sensitive data and permissions you cannot control.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What permissions should a beginner AI agent have?', 'kingy-ai-launch-intelligence'), 'answer' => __('Begin with read-only, draft-only, or sandboxed permissions. Add email, file, browser, publishing, payment, or production access only after tests, approvals, logs, and rollback are clear.', 'kingy-ai-launch-intelligence')),
        array('question' => __('How do I test an AI agent?', 'kingy-ai-launch-intelligence'), 'answer' => __('Test normal inputs, empty inputs, confusing inputs, malicious prompt-injection attempts, private data, permission failures, hallucination risks, cost limits, and rollback steps.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is an AI agent prompt template?', 'kingy-ai-launch-intelligence'), 'answer' => __('A useful template names the role, goal, inputs, data sources, tools, allowed actions, forbidden actions, output format, approval step, tests, and done criteria.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Are no-code AI agents safe?', 'kingy-ai-launch-intelligence'), 'answer' => __('They can be safe for narrow, supervised workflows, but risk rises when they touch private data, logged-in accounts, publishing, payments, or production records without review.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is human-in-the-loop for AI agents?', 'kingy-ai-launch-intelligence'), 'answer' => __('Human-in-the-loop means a person reviews or approves important outputs and actions, especially sending, publishing, buying, deleting, or changing production data.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_shortcode_codex_prompt_article_tools($atts = array()) {
    kingy_ali_enqueue_assets();

    $resources = kingy_ali_codex_article_resource_links();
    $checks = kingy_ali_codex_article_prompt_checks();
    $examples = kingy_ali_codex_article_examples();

    ob_start();
    ?>
    <section class="kingy-ali-codex-article-tools" data-kingy-codex-article-tools>
        <div class="kingy-ali-codex-article-tools__header">
            <p class="kingy-ali-kicker"><?php esc_html_e('Codex prompt lab', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Turn the lesson into a usable prompt', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('A strong prompt moves from a vague request to a work order Codex can inspect, edit, and verify.', 'kingy-ai-launch-intelligence'); ?></p>
        </div>

        <nav class="kingy-ali-codex-jump-links" aria-label="<?php esc_attr_e('Codex prompt article sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#kingy-codex-anatomy"><?php esc_html_e('Prompt anatomy', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-codex-examples"><?php esc_html_e('Copy examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#kingy-codex-checklist"><?php esc_html_e('Readiness check', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <div class="kingy-ali-codex-resource-grid" aria-label="<?php esc_attr_e('Related Build With AI resources', 'kingy-ai-launch-intelligence'); ?>">
            <?php foreach ($resources as $resource) : ?>
                <a
                    class="kingy-ali-codex-resource"
                    data-kingy-ali-track="clicked_codex_article_resource"
                    data-event-label="<?php echo esc_attr($resource['label']); ?>"
                    data-event-surface="codex_prompt_article_tools"
                    href="<?php echo esc_url($resource['url']); ?>"
                >
                    <strong><?php echo esc_html($resource['label']); ?></strong>
                    <span><?php echo esc_html($resource['description']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div id="kingy-codex-anatomy" class="kingy-ali-codex-anatomy">
            <h3><?php esc_html_e('Prompt anatomy', 'kingy-ai-launch-intelligence'); ?></h3>
            <div class="kingy-ali-codex-anatomy__grid">
                <div>
                    <strong><?php esc_html_e('Outcome', 'kingy-ai-launch-intelligence'); ?></strong>
                    <p><?php esc_html_e('What should be true when Codex is done?', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Context', 'kingy-ai-launch-intelligence'); ?></strong>
                    <p><?php esc_html_e('Which route, file, user, or existing pattern matters?', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Constraints', 'kingy-ai-launch-intelligence'); ?></strong>
                    <p><?php esc_html_e('What should stay unchanged, and what is out of scope?', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
                <div>
                    <strong><?php esc_html_e('Verification', 'kingy-ai-launch-intelligence'); ?></strong>
                    <p><?php esc_html_e('Which commands, browser checks, links, or screenshots prove it worked?', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </div>

        <div id="kingy-codex-examples" class="kingy-ali-codex-examples">
            <h3><?php esc_html_e('Copy a stronger starting point', 'kingy-ai-launch-intelligence'); ?></h3>
            <?php foreach ($examples as $index => $example) : ?>
                <?php $example_id = 'kingy-codex-example-' . absint($index); ?>
                <article class="kingy-ali-codex-example">
                    <div>
                        <p class="kingy-ali-codex-example__label"><?php esc_html_e('Weak request', 'kingy-ai-launch-intelligence'); ?></p>
                        <p><?php echo esc_html($example['weak']); ?></p>
                    </div>
                    <div>
                        <p class="kingy-ali-codex-example__label"><?php esc_html_e('Stronger Codex prompt', 'kingy-ai-launch-intelligence'); ?></p>
                        <textarea id="<?php echo esc_attr($example_id); ?>" readonly rows="7"><?php echo esc_textarea($example['strong']); ?></textarea>
                        <button type="button" data-codex-example-copy data-copy-source="<?php echo esc_attr($example_id); ?>"><?php esc_html_e('Copy Prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div id="kingy-codex-checklist" class="kingy-ali-codex-checklist">
            <div>
                <h3><?php esc_html_e('Prompt readiness check', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Most useful Codex prompts include a clear outcome, boundaries, safety rules, and verification before any code changes begin.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                <strong><span data-codex-check-count>0</span> / <?php echo esc_html(count($checks)); ?></strong>
                <span data-codex-check-status><?php esc_html_e('Needs structure', 'kingy-ai-launch-intelligence'); ?></span>
                <progress max="<?php echo esc_attr(count($checks)); ?>" value="0" data-codex-check-progress></progress>
            </div>
            <div class="kingy-ali-codex-checklist__items">
                <?php foreach ($checks as $index => $check) : ?>
                    <?php $check_id = 'kingy-codex-check-' . absint($index); ?>
                    <label for="<?php echo esc_attr($check_id); ?>">
                        <input id="<?php echo esc_attr($check_id); ?>" type="checkbox" data-codex-check>
                        <span><?php echo esc_html($check); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="button" class="kingy-ali-codex-secondary" data-codex-check-reset><?php esc_html_e('Reset Check', 'kingy-ai-launch-intelligence'); ?></button>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function kingy_ali_codex_article_resource_links() {
    return array(
        array(
            'label' => __('Build With AI Academy', 'kingy-ai-launch-intelligence'),
            'description' => __('Return to the main beginner build path.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/'),
        ),
        array(
            'label' => __('AI App Builder', 'kingy-ai-launch-intelligence'),
            'description' => __('Turn the topic into a scoped build plan.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/'),
        ),
        array(
            'label' => __('Codex Prompt Builder', 'kingy-ai-launch-intelligence'),
            'description' => __('Generate a structured Codex-ready prompt.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/'),
        ),
        array(
            'label' => __('Starter Pack PDF', 'kingy-ai-launch-intelligence'),
            'description' => __('Download the printable beginner checklist.', 'kingy-ai-launch-intelligence'),
            'url' => home_url('/wp-content/uploads/2026/06/build-with-ai-starter-pack.pdf'),
        ),
    );
}

function kingy_ali_codex_article_prompt_checks() {
    return array(
        __('Names the user-visible outcome', 'kingy-ai-launch-intelligence'),
        __('Points Codex to the relevant route, file, page, or feature', 'kingy-ai-launch-intelligence'),
        __('Lists what must stay unchanged', 'kingy-ai-launch-intelligence'),
        __('Defines the first version and non-goals', 'kingy-ai-launch-intelligence'),
        __('Includes safety rules for secrets, data, payments, or approvals', 'kingy-ai-launch-intelligence'),
        __('Asks for verification with commands or browser checks', 'kingy-ai-launch-intelligence'),
        __('Requests a concise summary of files changed and limits', 'kingy-ai-launch-intelligence'),
    );
}

function kingy_ali_codex_article_examples() {
    return array(
        array(
            'weak' => __('Make this page better.', 'kingy-ai-launch-intelligence'),
            'strong' => __("/goal Improve the article page so readers can act on it.\n\nContext:\n- Inspect the current article and existing Kingy AI styles first.\n- Add useful internal links, a copy-ready prompt, and one lightweight interactive element.\n\nConstraints:\n- Do not change site navigation or unrelated pages.\n- Reuse existing classes and tracking attributes when possible.\n\nVerification:\n- Check desktop and mobile layout, links, copy buttons, and any generated output.\n- Summarize files changed and anything that still needs editorial review.", 'kingy-ai-launch-intelligence'),
        ),
        array(
            'weak' => __('Build me an app.', 'kingy-ai-launch-intelligence'),
            'strong' => __("/goal Build a small first version of a beginner-friendly AI project planner.\n\nAudience:\n- Non-technical creators choosing a realistic first AI-built project.\n\nRequirements:\n- Include project type, target user, must-have pages, data needed, risks, and launch checklist.\n- Generate a Codex-ready prompt from the answers.\n\nNon-goals:\n- No accounts, payments, private data storage, or paid APIs in version one.\n\nVerification:\n- Test empty input, filled input, reset, copy action, and mobile layout.", 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_shortcode_ai_landing_page_guide() {
    kingy_ali_enqueue_assets();

    $section_presets = kingy_ali_ai_landing_page_section_presets();
    $examples = kingy_ali_ai_landing_page_examples();
    $prompts = kingy_ali_ai_landing_page_prompts();
    $faqs = kingy_ali_ai_landing_page_faqs();

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-landing-guide" data-kingy-landing-guide>
        <header class="kingy-ali-academy-hero kingy-ali-landing-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('How to Build a Landing Page With AI', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Use AI to turn a clear offer into a landing page plan, outline, copy, build prompt, and QA checklist without handing strategy, proof, or trust to the machine.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Open prompt builder', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="landing_page_hero" href="#landing-page-prompt-builder"><?php esc_html_e('Build my prompt', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Run QA scorecard', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="landing_page_hero" href="#landing-page-qa"><?php esc_html_e('Score my page', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('Quick answer', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('To build a landing page with AI, give it the audience, offer, painful problem, desired outcome, proof, tone, CTA, constraints, and existing site style. Ask for a section plan first, then copy, then implementation, then QA.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul>
                    <li><?php esc_html_e('Best first page: one audience, one promise, one CTA.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best AI use: structure, variants, checklists, and implementation prompts.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best human job: proof, positioning, claims, editing, and final review.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Landing page guide sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#landing-page-workflow"><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-anatomy"><?php esc_html_e('Anatomy', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-prompt-builder"><?php esc_html_e('Prompt Builder', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-sections"><?php esc_html_e('Section Generator', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-qa"><?php esc_html_e('QA', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#landing-page-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="landing-page-workflow" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Beginner workflow', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('The safe AI workflow: strategy first, build second', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Weak AI landing pages usually start with “make a page.” Better pages start with the business case and make AI show its work before writing code or copy.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-resource-grid">
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('1. Define the job', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Name the audience, problem, offer, outcome, proof, CTA, and one thing the page must not claim.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('2. Generate the outline', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Ask for section order, section purpose, proof needed, and mobile order before asking for polished copy.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('3. Draft and edit copy', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Create hero, problem, benefits, proof, objections, FAQ, CTA, SEO title, and meta description. Edit every claim.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('4. Build with context', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Tell Codex or your AI builder to inspect existing files, reuse styles, keep changes scoped, and verify the page.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('5. QA like a publisher', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Check mobile, speed, metadata, links, schema, accessibility, forms, copy buttons, claims, and privacy notes.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('6. Improve from evidence', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('After publishing, use search queries, form quality, heatmaps, and customer objections to refine the page.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section id="landing-page-anatomy" class="kingy-ali-builder-chooser">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Landing page anatomy', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('The sections AI should plan before it writes', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Every section should clarify, prove, reduce risk, or move the visitor to the next action.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-choice-grid">
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('Hero', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Audience, outcome, offer, CTA, and one trust signal above the fold.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('Problem', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Show the pain and stakes without exaggerating fear.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('How it works', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Explain the process, product, or next step in three to five beats.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('Proof', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Use real demos, screenshots, customer words, or transparent limitations.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('Objections', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Answer cost, time, fit, risk, data, support, and implementation questions.', 'kingy-ai-launch-intelligence'); ?></span></div>
                <div class="kingy-ali-choice-button"><strong><?php esc_html_e('Final CTA', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Repeat the action with a plain-English note about what happens next.', 'kingy-ai-launch-intelligence'); ?></span></div>
            </div>
        </section>

        <section id="landing-page-prompt-builder" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Landing Page Prompt Builder', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Generate a copy-ready AI prompt for your page', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-lead-architect kingy-ali-landing-prompt-tool">
                <form class="kingy-ali-lead-architect__form" data-landing-prompt-form>
                    <label><span><?php esc_html_e('Audience', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-landing-prompt-field="audience" placeholder="<?php esc_attr_e('Solo course creators launching their first cohort', 'kingy-ai-launch-intelligence'); ?>" required></label>
                    <label><span><?php esc_html_e('Offer', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-landing-prompt-field="offer" placeholder="<?php esc_attr_e('AI-assisted landing page sprint', 'kingy-ai-launch-intelligence'); ?>" required></label>
                    <label><span><?php esc_html_e('Problem', 'kingy-ai-launch-intelligence'); ?></span><textarea rows="3" data-landing-prompt-field="problem" placeholder="<?php esc_attr_e('They have traffic, but the page does not explain the value quickly.', 'kingy-ai-launch-intelligence'); ?>" required></textarea></label>
                    <label><span><?php esc_html_e('Outcome', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-landing-prompt-field="outcome" placeholder="<?php esc_attr_e('A publishable page with clear copy, sections, and QA', 'kingy-ai-launch-intelligence'); ?>"></label>
                    <label><span><?php esc_html_e('Proof available', 'kingy-ai-launch-intelligence'); ?></span><textarea rows="2" data-landing-prompt-field="proof" placeholder="<?php esc_attr_e('Demo screenshots, founder story, beta user quote, walkthrough', 'kingy-ai-launch-intelligence'); ?>"></textarea></label>
                    <label><span><?php esc_html_e('Tone', 'kingy-ai-launch-intelligence'); ?></span><select data-landing-prompt-field="tone"><option value="clear, practical, beginner-friendly"><?php esc_html_e('Clear and practical', 'kingy-ai-launch-intelligence'); ?></option><option value="confident, concise, B2B"><?php esc_html_e('Confident B2B', 'kingy-ai-launch-intelligence'); ?></option><option value="warm, encouraging, creator-led"><?php esc_html_e('Warm creator-led', 'kingy-ai-launch-intelligence'); ?></option></select></label>
                    <label><span><?php esc_html_e('Primary CTA', 'kingy-ai-launch-intelligence'); ?></span><input type="text" data-landing-prompt-field="cta" placeholder="<?php esc_attr_e('Book a landing page review', 'kingy-ai-launch-intelligence'); ?>"></label>
                    <label class="kingy-ali-field--textarea"><span><?php esc_html_e('Constraints', 'kingy-ai-launch-intelligence'); ?></span><textarea rows="3" data-landing-prompt-field="constraints" placeholder="<?php esc_attr_e('No fake testimonials, no revenue claims, avoid private data.', 'kingy-ai-launch-intelligence'); ?>"></textarea></label>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Generate Prompt', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-lead-architect__output" data-landing-prompt-output aria-live="polite">
                    <p class="kingy-ali-kicker"><?php esc_html_e('Your prompt', 'kingy-ai-launch-intelligence'); ?></p>
                    <h3><?php esc_html_e('Prompt appears here', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Add audience, offer, and problem to generate a complete AI landing page prompt.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <section id="landing-page-sections" class="kingy-ali-builder-chooser">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Landing Page Section Generator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Pick a page type and get the recommended structure', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('Landing page section presets', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($section_presets as $key => $preset) : ?>
                    <button type="button" class="kingy-ali-choice-button" data-landing-section-preset="<?php echo esc_attr($key); ?>" data-landing-section-payload="<?php echo esc_attr(wp_json_encode($preset)); ?>">
                        <strong><?php echo esc_html($preset['label']); ?></strong>
                        <span><?php echo esc_html($preset['hint']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result" data-landing-section-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommended structure', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Choose a landing page type', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Select a preset to see the section order and what AI should generate.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="landing-page-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Five landing page patterns you can adapt', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use these as strategy examples, not fake case studies. Replace proof and claims with your own evidence.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-builder-card-grid">
                <?php foreach ($examples as $example) : ?>
                    <article class="kingy-ali-builder-card kingy-ali-landing-example-card">
                        <div class="kingy-ali-builder-card__header"><h3><?php echo esc_html($example['title']); ?></h3><span><?php echo esc_html($example['type']); ?></span></div>
                        <p><?php echo esc_html($example['summary']); ?></p>
                        <dl>
                            <div><dt><?php esc_html_e('Sections', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['sections']); ?></dd></div>
                            <div><dt><?php esc_html_e('Proof to use', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['proof']); ?></dd></div>
                            <div><dt><?php esc_html_e('CTA', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['cta']); ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Bad vs better', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Turn generic AI output into useful landing page copy', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-comparison-table-wrap">
                <table class="kingy-ali-comparison-table">
                    <thead><tr><th><?php esc_html_e('Weak AI output', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Better direction', 'kingy-ai-launch-intelligence'); ?></th><th><?php esc_html_e('Why it works', 'kingy-ai-launch-intelligence'); ?></th></tr></thead>
                    <tbody>
                        <tr><td><?php esc_html_e('Transform your business with AI.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Build a launch-ready landing page plan for your first paid offer in under 10 minutes.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Specific audience, result, and time frame beat vague transformation language.', 'kingy-ai-launch-intelligence'); ?></td></tr>
                        <tr><td><?php esc_html_e('Trusted by thousands of customers.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Include real screenshots, a demo walkthrough, named customer words, or remove the claim.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('AI should organize proof, never invent it.', 'kingy-ai-launch-intelligence'); ?></td></tr>
                        <tr><td><?php esc_html_e('Click here to learn more.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('Get my landing page prompt, Book a review, or Start the checklist.', 'kingy-ai-launch-intelligence'); ?></td><td><?php esc_html_e('The CTA says what happens next and matches the page goal.', 'kingy-ai-launch-intelligence'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="landing-page-prompts" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Copy-ready prompts', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Prompts for strategy, copy, SEO, mobile QA, and Codex', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <?php foreach ($prompts as $index => $prompt) : ?>
                    <?php $prompt_id = 'kingy-landing-prompt-' . absint($index); ?>
                    <section class="kingy-ali-copy-prompt kingy-ali-landing-copy-prompt">
                        <div><p class="kingy-ali-kicker"><?php echo esc_html($prompt['label']); ?></p><h3><?php echo esc_html($prompt['title']); ?></h3></div>
                        <pre><code id="<?php echo esc_attr($prompt_id); ?>"><?php echo esc_html($prompt['prompt']); ?></code></pre>
                        <button type="button" data-landing-copy-target="#<?php echo esc_attr($prompt_id); ?>"><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="landing-page-qa" class="kingy-ali-test-project kingy-ali-landing-qa" data-landing-qa>
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('AI Landing Page QA Scorecard', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Score your page before you publish', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('AI can generate a convincing page that is still unclear, unsupported, inaccessible, or risky. Check every item before sending traffic.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-codex-checklist__score" aria-live="polite">
                    <strong><span data-landing-qa-count>0</span> / 10</strong>
                    <span data-landing-qa-status><?php esc_html_e('Needs review', 'kingy-ai-launch-intelligence'); ?></span>
                    <progress max="10" value="0" data-landing-qa-progress></progress>
                </div>
                <button type="button" class="kingy-ali-codex-secondary" data-landing-qa-reset><?php esc_html_e('Reset Score', 'kingy-ai-launch-intelligence'); ?></button>
            </div>
            <div class="kingy-ali-checklist">
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Hero says who it is for, what they get, and what to do next.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Every major claim is supported by real proof or removed.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('CTA is specific, repeated, and connected to a clear next step.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Mobile layout has no overlapping text, cramped controls, or broken tables.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Title, meta description, H1, headings, FAQ, and schema match search intent.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Private data, regulated claims, fake proof, and unreviewed AI output are handled safely.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Page speed is acceptable and no unnecessary heavy scripts were added.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('Buttons, forms, copy actions, links, and keyboard focus states work.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('FAQ answers real objections about price, fit, time, trust, and data.', 'kingy-ai-launch-intelligence'); ?></label>
                <label><input type="checkbox" data-landing-qa-check> <?php esc_html_e('The page can be explained in one sentence by someone who did not build it.', 'kingy-ai-launch-intelligence'); ?></label>
            </div>
        </section>

        <section class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Trust notes', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('What AI should not decide for you', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Proof', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Do not let AI invent testimonials, logos, customer counts, screenshots, awards, or revenue numbers. Use real proof or transparent placeholders.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Private data', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Do not paste customer lists, health data, legal facts, financial records, passwords, API keys, or private analytics into a prompt.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('Regulated claims', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Review legal, medical, financial, employment, housing, and performance claims with the right human expert before publishing.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section id="landing-page-resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('Keep building', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Related Build With AI resources', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/')); ?>"><strong><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Return to the beginner build path.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/')); ?>"><strong><?php esc_html_e('AI App Builder for Beginners', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Choose the right build surface.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn the landing page plan into a build prompt.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/articles/how-to-build-a-lead-magnet-with-ai/')); ?>"><strong><?php esc_html_e('Build a Lead Magnet With AI', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Add a useful follow-up offer.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/beginner-safety-rules/')); ?>"><strong><?php esc_html_e('Beginner Safety Rules', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Review privacy and publishing guardrails.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="landing-page-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading"><p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p><h2><?php esc_html_e('Common questions about AI landing pages', 'kingy-ai-launch-intelligence'); ?></h2></div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details><summary><?php echo esc_html($faq['question']); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_landing_page_section_presets() {
    return array(
        'saas' => array('label' => __('SaaS product', 'kingy-ai-launch-intelligence'), 'hint' => __('Trials, demos, beta waitlists, or feature launches.', 'kingy-ai-launch-intelligence'), 'sections' => array(__('Hero: audience, painful workflow, outcome, product promise, demo/trial CTA.', 'kingy-ai-launch-intelligence'), __('Workflow problem: show the old way and the cost of staying there.', 'kingy-ai-launch-intelligence'), __('Product walkthrough: three steps with screenshots or demo frames.', 'kingy-ai-launch-intelligence'), __('Proof: real users, usage screenshots, security notes, integrations, or founder credibility.', 'kingy-ai-launch-intelligence'), __('Objections: setup time, pricing, data handling, migration, support.', 'kingy-ai-launch-intelligence'), __('Final CTA: start trial, join beta, or book demo with next-step copy.', 'kingy-ai-launch-intelligence'))),
        'ai-tool' => array('label' => __('AI tool', 'kingy-ai-launch-intelligence'), 'hint' => __('Output quality, use cases, and safety need to be obvious.', 'kingy-ai-launch-intelligence'), 'sections' => array(__('Hero: generated outcome, input required, time saved, try-it CTA.', 'kingy-ai-launch-intelligence'), __('Example output: show a realistic before/after or sample result.', 'kingy-ai-launch-intelligence'), __('How it works: inputs, model behavior, review step, export/copy action.', 'kingy-ai-launch-intelligence'), __('Trust: privacy, limitations, no sensitive data, human review guidance.', 'kingy-ai-launch-intelligence'), __('Use cases: three concrete jobs the visitor can finish today.', 'kingy-ai-launch-intelligence'), __('FAQ and CTA: pricing, accuracy, data, limits, and start button.', 'kingy-ai-launch-intelligence'))),
        'course' => array('label' => __('Course or info product', 'kingy-ai-launch-intelligence'), 'hint' => __('Cohorts, workshops, templates, or paid guides.', 'kingy-ai-launch-intelligence'), 'sections' => array(__('Hero: learner identity, transformation, timeline, enrollment CTA.', 'kingy-ai-launch-intelligence'), __('Who it is for: qualify the right learner and disqualify poor fit.', 'kingy-ai-launch-intelligence'), __('Curriculum: modules, outcomes, assignments, examples, support.', 'kingy-ai-launch-intelligence'), __('Proof: sample lesson, instructor credibility, student work, real testimonials only.', 'kingy-ai-launch-intelligence'), __('Offer: price, bonuses, guarantee if real, start date, what happens after checkout.', 'kingy-ai-launch-intelligence'), __('FAQ and final CTA: time, skill level, access, refunds, support.', 'kingy-ai-launch-intelligence'))),
        'local' => array('label' => __('Local service', 'kingy-ai-launch-intelligence'), 'hint' => __('Quotes, inspections, bookings, or appointments.', 'kingy-ai-launch-intelligence'), 'sections' => array(__('Hero: city/service, urgent problem, service outcome, quote/book CTA.', 'kingy-ai-launch-intelligence'), __('Service fit: what you handle, where you serve, who you are best for.', 'kingy-ai-launch-intelligence'), __('Process: request, assessment, quote, work, follow-up.', 'kingy-ai-launch-intelligence'), __('Proof: licenses, photos, reviews, named neighborhoods, before/after if real.', 'kingy-ai-launch-intelligence'), __('Trust: insurance, response time, safety, pricing factors, no-pressure estimate.', 'kingy-ai-launch-intelligence'), __('FAQ and contact CTA: hours, service area, preparation, payment, urgency.', 'kingy-ai-launch-intelligence'))),
        'newsletter' => array('label' => __('Lead magnet or newsletter', 'kingy-ai-launch-intelligence'), 'hint' => __('Email opt-ins, downloads, waitlists, and audience growth.', 'kingy-ai-launch-intelligence'), 'sections' => array(__('Hero: who it helps, what they receive, cadence or format, subscribe/download CTA.', 'kingy-ai-launch-intelligence'), __('Sample value: preview issue, checklist, prompt, or excerpt before opt-in.', 'kingy-ai-launch-intelligence'), __('Why subscribe: topics, reader outcome, frequency, what is not included.', 'kingy-ai-launch-intelligence'), __('Trust: privacy note, unsubscribe promise, sender credibility, no spam.', 'kingy-ai-launch-intelligence'), __('Examples: recent topics, reader questions, or resource previews.', 'kingy-ai-launch-intelligence'), __('Final CTA: repeat the signup with a clear expectation of the first email.', 'kingy-ai-launch-intelligence'))),
    );
}

function kingy_ali_ai_landing_page_examples() {
    return array(
        array('type' => __('SaaS', 'kingy-ai-launch-intelligence'), 'title' => __('Customer Onboarding Checklist SaaS', 'kingy-ai-launch-intelligence'), 'summary' => __('A demo page for teams losing new users after signup.', 'kingy-ai-launch-intelligence'), 'sections' => __('Hero, churn problem, workflow demo, integrations, proof, security FAQ, demo CTA.', 'kingy-ai-launch-intelligence'), 'proof' => __('Product screenshots, onboarding template sample, support workflow, real usage note.', 'kingy-ai-launch-intelligence'), 'cta' => __('Book a 15-minute onboarding audit.', 'kingy-ai-launch-intelligence')),
        array('type' => __('AI tool', 'kingy-ai-launch-intelligence'), 'title' => __('AI Proposal Draft Generator', 'kingy-ai-launch-intelligence'), 'summary' => __('A landing page for consultants who need a proposal first draft from call notes.', 'kingy-ai-launch-intelligence'), 'sections' => __('Hero, sample input/output, workflow, privacy guardrails, use cases, FAQ, try CTA.', 'kingy-ai-launch-intelligence'), 'proof' => __('Redacted sample output, screenshots, limitations, human review reminder.', 'kingy-ai-launch-intelligence'), 'cta' => __('Generate a sample proposal outline.', 'kingy-ai-launch-intelligence')),
        array('type' => __('Course', 'kingy-ai-launch-intelligence'), 'title' => __('Beginner AI Automation Workshop', 'kingy-ai-launch-intelligence'), 'summary' => __('A course page for operators who want one safe automation in a week.', 'kingy-ai-launch-intelligence'), 'sections' => __('Hero, fit, curriculum, project outcomes, instructor proof, schedule, FAQ, enroll CTA.', 'kingy-ai-launch-intelligence'), 'proof' => __('Sample lesson, curriculum screenshot, instructor project history, real student work if available.', 'kingy-ai-launch-intelligence'), 'cta' => __('Join the next workshop.', 'kingy-ai-launch-intelligence')),
        array('type' => __('Local service', 'kingy-ai-launch-intelligence'), 'title' => __('Emergency Roof Inspection Page', 'kingy-ai-launch-intelligence'), 'summary' => __('A local page for homeowners who need to know if a leak is urgent.', 'kingy-ai-launch-intelligence'), 'sections' => __('Hero, service area, triage steps, repair process, photos, reviews, FAQ, call CTA.', 'kingy-ai-launch-intelligence'), 'proof' => __('Real job photos, licenses, service area, review snippets, clear pricing factors.', 'kingy-ai-launch-intelligence'), 'cta' => __('Request an inspection window.', 'kingy-ai-launch-intelligence')),
        array('type' => __('Newsletter', 'kingy-ai-launch-intelligence'), 'title' => __('Weekly AI Launch Brief', 'kingy-ai-launch-intelligence'), 'summary' => __('A signup page for builders who want useful AI product launches without noise.', 'kingy-ai-launch-intelligence'), 'sections' => __('Hero, sample issue, who reads it, topics, privacy, recent examples, signup CTA.', 'kingy-ai-launch-intelligence'), 'proof' => __('Sample issue, archive links, editorial criteria, sender bio, unsubscribe promise.', 'kingy-ai-launch-intelligence'), 'cta' => __('Get the next launch brief.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_ai_landing_page_prompts() {
    return array(
        array('label' => __('Strategy discovery', 'kingy-ai-launch-intelligence'), 'title' => __('Find the landing page angle', 'kingy-ai-launch-intelligence'), 'prompt' => __('Act as a landing page strategist. Interview me about my audience, offer, painful problem, desired outcome, real proof, objections, tone, CTA, constraints, and search intent. Ask one question at a time. After I answer, summarize the page promise, section strategy, proof needed, and risks to avoid.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Page outline', 'kingy-ai-launch-intelligence'), 'title' => __('Generate the section plan', 'kingy-ai-launch-intelligence'), 'prompt' => __('Create a landing page outline for [audience] considering [offer]. Goal: [CTA]. Include each section in order, the job of the section, copy points, proof needed, mobile notes, and what not to claim.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Hero and CTA', 'kingy-ai-launch-intelligence'), 'title' => __('Draft stronger above-the-fold copy', 'kingy-ai-launch-intelligence'), 'prompt' => __('Write 10 hero options for this landing page. Each option needs a specific headline, subhead, primary CTA, secondary CTA if useful, and one trust note. Avoid hype, fake proof, revenue claims, and generic phrases like transform your business.', 'kingy-ai-launch-intelligence')),
        array('label' => __('SEO and FAQ', 'kingy-ai-launch-intelligence'), 'title' => __('Create search-ready metadata and questions', 'kingy-ai-launch-intelligence'), 'prompt' => __('For this landing page, write an SEO title, meta description, H1, answer block for featured snippets, and 8 FAQ questions with concise answers. Match search intent, avoid filler, and do not invent proof or claims.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Mobile QA', 'kingy-ai-launch-intelligence'), 'title' => __('Check the page before publishing', 'kingy-ai-launch-intelligence'), 'prompt' => __('Review this landing page for mobile layout, clarity, CTA strength, proof, accessibility, speed, SEO metadata, schema, privacy, unsupported claims, broken links, and form behavior. Return a prioritized punch list with must-fix, should-fix, and optional improvements.', 'kingy-ai-launch-intelligence')),
        array('label' => __('Codex implementation', 'kingy-ai-launch-intelligence'), 'title' => __('Turn the plan into a build', 'kingy-ai-launch-intelligence'), 'prompt' => __("/goal Build or improve this AI landing page from the plan below.\n\nFirst inspect the existing repo/page and preserve the current visual system, SEO patterns, internal links, analytics attributes, and brand voice.\n\nRequirements:\n- Implement the landing page structure, copy, examples, FAQ, schema, metadata, mobile layout, and copy buttons.\n- Add useful interactive components only where they improve the page.\n- Do not invent testimonials, logos, stats, revenue claims, pricing claims, or case studies.\n- Include safety notes about private data, fake proof, regulated claims, and unreviewed AI output.\n\nVerification:\n- Run build/lint/test commands.\n- Check desktop and mobile.\n- Verify links, metadata, schema, forms, copy buttons, and interactive states.", 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_ai_landing_page_faqs() {
    return array(
        array('question' => __('Can AI build an entire landing page?', 'kingy-ai-launch-intelligence'), 'answer' => __('Yes, AI can draft the strategy, copy, layout, code, metadata, FAQ, and QA checklist. You still need to provide the real audience, offer, proof, claims, constraints, and final review.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What should I put in an AI landing page prompt?', 'kingy-ai-launch-intelligence'), 'answer' => __('Include the audience, offer, problem, desired outcome, proof, tone, CTA, site style, examples to match, constraints, SEO target, and verification steps.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What is the best AI landing page builder for beginners?', 'kingy-ai-launch-intelligence'), 'answer' => __('The best tool depends on the goal. ChatGPT is useful for strategy and copy, Codex is useful for editing an existing codebase, and visual builders are useful for quick prototypes when you do not need custom logic.', 'kingy-ai-launch-intelligence')),
        array('question' => __('How do I avoid generic AI landing page copy?', 'kingy-ai-launch-intelligence'), 'answer' => __('Force specificity. Add a narrow audience, real problem, concrete outcome, proof source, objections, examples, and banned phrases. Ask for bad-versus-better rewrites.', 'kingy-ai-launch-intelligence')),
        array('question' => __('Should AI write testimonials or statistics?', 'kingy-ai-launch-intelligence'), 'answer' => __('No. AI should help organize real proof, not invent it. If proof is missing, use product screenshots, demos, founder context, transparent limitations, or remove the claim.', 'kingy-ai-launch-intelligence')),
        array('question' => __('What should I check before publishing an AI-built landing page?', 'kingy-ai-launch-intelligence'), 'answer' => __('Check mobile layout, CTA clarity, proof, links, forms, accessibility, metadata, schema, page speed, privacy notes, and unsupported claims.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_shortcode_ai_lead_magnet_guide() {
    kingy_ali_enqueue_assets();

    $formats = kingy_ali_ai_lead_magnet_formats();
    $examples = kingy_ali_ai_lead_magnet_examples();
    $faqs = kingy_ali_ai_lead_magnet_faqs();
    $categories = array(
        'all' => __('All', 'kingy-ai-launch-intelligence'),
        'saas' => __('SaaS', 'kingy-ai-launch-intelligence'),
        'coaching' => __('Coaching', 'kingy-ai-launch-intelligence'),
        'agency' => __('Agency', 'kingy-ai-launch-intelligence'),
        'ecommerce' => __('Ecommerce', 'kingy-ai-launch-intelligence'),
        'local' => __('Local services', 'kingy-ai-launch-intelligence'),
        'real-estate' => __('Real estate', 'kingy-ai-launch-intelligence'),
        'education' => __('Courses', 'kingy-ai-launch-intelligence'),
        'newsletter' => __('Newsletters', 'kingy-ai-launch-intelligence'),
        'b2b' => __('B2B', 'kingy-ai-launch-intelligence'),
    );

    ob_start();
    ?>
    <article class="kingy-ali-academy-article kingy-ali-lead-magnet-guide" data-kingy-lead-magnet-guide>
        <header class="kingy-ali-academy-hero kingy-ali-lead-magnet-hero">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('How to Build a Lead Magnet With AI', 'kingy-ai-launch-intelligence'); ?></h2>
                <p class="kingy-ali-academy-lede"><?php esc_html_e('Use AI to turn one audience problem into a useful checklist, calculator, quiz, assessment, prompt pack, or mini tool that gives value before it asks for an email.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-cta-row">
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Open architect', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="lead_magnet_hero" href="#lead-magnet-architect"><?php esc_html_e('Build my lead magnet plan', 'kingy-ai-launch-intelligence'); ?></a>
                    <a data-kingy-ali-track="clicked_academy_cta" data-event-label="<?php esc_attr_e('Browse examples', 'kingy-ai-launch-intelligence'); ?>" data-event-surface="lead_magnet_hero" href="#lead-magnet-examples"><?php esc_html_e('Browse examples', 'kingy-ai-launch-intelligence'); ?></a>
                </div>
            </div>
            <aside class="kingy-ali-decision-card" aria-label="<?php esc_attr_e('Quick answer', 'kingy-ai-launch-intelligence'); ?>">
                <h2><?php esc_html_e('Quick answer', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A strong AI lead magnet solves one specific problem, delivers a useful result in under five minutes, and makes email capture optional, explicit, and connected to a clear follow-up promise.', 'kingy-ai-launch-intelligence'); ?></p>
                <ul>
                    <li><?php esc_html_e('Best first format: checklist, scorecard, calculator, or prompt pack.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best upgrade: personalize the result from visitor answers.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><?php esc_html_e('Best trust rule: show value before the form.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </aside>
        </header>

        <nav class="kingy-ali-jump-nav" aria-label="<?php esc_attr_e('Lead magnet guide sections', 'kingy-ai-launch-intelligence'); ?>">
            <a href="#lead-magnet-formats"><?php esc_html_e('Formats', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#lead-magnet-architect"><?php esc_html_e('Architect', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#lead-magnet-roi"><?php esc_html_e('ROI', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#lead-magnet-examples"><?php esc_html_e('Examples', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#lead-magnet-workflow"><?php esc_html_e('Workflow', 'kingy-ai-launch-intelligence'); ?></a>
            <a href="#lead-magnet-faq"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></a>
        </nav>

        <section id="lead-magnet-formats" class="kingy-ali-builder-chooser kingy-ali-lead-format-tool">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Format selector', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Choose the format by the job your visitor needs done', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('The mistake is starting with “I need a PDF.” Start with the visitor question, the buying stage, and the result they can use immediately.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-choice-grid" role="group" aria-label="<?php esc_attr_e('Lead magnet formats', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($formats as $key => $format) : ?>
                    <button
                        type="button"
                        class="kingy-ali-choice-button"
                        data-lead-format="<?php echo esc_attr($key); ?>"
                        data-lead-format-payload="<?php echo esc_attr(wp_json_encode($format)); ?>"
                    >
                        <strong><?php echo esc_html($format['label']); ?></strong>
                        <span><?php echo esc_html($format['hint']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-choice-result" data-lead-format-result aria-live="polite">
                <p class="kingy-ali-kicker"><?php esc_html_e('Recommendation', 'kingy-ai-launch-intelligence'); ?></p>
                <h3><?php esc_html_e('Start with the smallest useful result', 'kingy-ai-launch-intelligence'); ?></h3>
                <p><?php esc_html_e('Pick a format above to see when it fits, what AI should generate, and what to measure after launch.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
        </section>

        <section id="lead-magnet-architect" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('AI Lead Magnet Architect', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Generate the plan, copy, emails, prompt, and QA checklist', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Fill in the basics. The output stays in the browser and gives you a useful plan before any email opt-in exists.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-lead-architect">
                <form class="kingy-ali-lead-architect__form" data-lead-architect-form novalidate>
                    <label>
                        <span><?php esc_html_e('Audience', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="text" name="audience" placeholder="<?php esc_attr_e('Example: solo real estate agents', 'kingy-ai-launch-intelligence'); ?>" data-lead-architect-field="audience">
                    </label>
                    <label>
                        <span><?php esc_html_e('Painful problem', 'kingy-ai-launch-intelligence'); ?></span>
                        <textarea name="problem" rows="3" placeholder="<?php esc_attr_e('Example: they do not know which listings deserve paid promotion', 'kingy-ai-launch-intelligence'); ?>" data-lead-architect-field="problem"></textarea>
                    </label>
                    <label>
                        <span><?php esc_html_e('Paid offer it should lead toward', 'kingy-ai-launch-intelligence'); ?></span>
                        <input type="text" name="offer" placeholder="<?php esc_attr_e('Example: done-for-you listing launch campaign', 'kingy-ai-launch-intelligence'); ?>" data-lead-architect-field="offer">
                    </label>
                    <label>
                        <span><?php esc_html_e('Industry', 'kingy-ai-launch-intelligence'); ?></span>
                        <select name="industry" data-lead-architect-field="industry">
                            <option value="SaaS"><?php esc_html_e('SaaS', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Coaching"><?php esc_html_e('Coaching', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Agency"><?php esc_html_e('Agency', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Ecommerce"><?php esc_html_e('Ecommerce', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Real estate"><?php esc_html_e('Real estate', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Local services"><?php esc_html_e('Local services', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Course or education"><?php esc_html_e('Course or education', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="B2B consulting"><?php esc_html_e('B2B consulting', 'kingy-ai-launch-intelligence'); ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?php esc_html_e('Funnel stage', 'kingy-ai-launch-intelligence'); ?></span>
                        <select name="stage" data-lead-architect-field="stage">
                            <option value="Problem-aware"><?php esc_html_e('Problem-aware', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Solution-aware"><?php esc_html_e('Solution-aware', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Comparison-ready"><?php esc_html_e('Comparison-ready', 'kingy-ai-launch-intelligence'); ?></option>
                            <option value="Buying soon"><?php esc_html_e('Buying soon', 'kingy-ai-launch-intelligence'); ?></option>
                        </select>
                    </label>
                    <label>
                        <span><?php esc_html_e('Preferred format', 'kingy-ai-launch-intelligence'); ?></span>
                        <select name="format" data-lead-architect-field="format">
                            <?php foreach ($formats as $format) : ?>
                                <option value="<?php echo esc_attr($format['label']); ?>"><?php echo esc_html($format['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="kingy-ali-field--textarea">
                        <span><?php esc_html_e('Trust constraints', 'kingy-ai-launch-intelligence'); ?></span>
                        <textarea name="constraints" rows="3" placeholder="<?php esc_attr_e('Example: no hidden email capture, avoid financial promises, include unsubscribe copy', 'kingy-ai-launch-intelligence'); ?>" data-lead-architect-field="constraints"></textarea>
                    </label>
                    <div class="kingy-ali-codex-actions">
                        <button type="submit"><?php esc_html_e('Generate Plan', 'kingy-ai-launch-intelligence'); ?></button>
                        <button type="reset" class="kingy-ali-codex-secondary"><?php esc_html_e('Reset', 'kingy-ai-launch-intelligence'); ?></button>
                    </div>
                </form>
                <div class="kingy-ali-lead-architect__output" data-lead-architect-output aria-live="polite">
                    <p class="kingy-ali-kicker"><?php esc_html_e('Your output', 'kingy-ai-launch-intelligence'); ?></p>
                    <h3><?php esc_html_e('Lead magnet plan appears here', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Add audience, problem, offer, format, and constraints to generate a copy-ready plan.', 'kingy-ai-launch-intelligence'); ?></p>
                </div>
            </div>
        </section>

        <section id="lead-magnet-roi" class="kingy-ali-test-project kingy-ali-lead-roi" data-lead-roi>
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Simple ROI calculator', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Estimate the upside of replacing a static form', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Use this as planning math, not a guarantee. The real win is often better lead quality because the visitor answered useful qualification questions before opting in.', 'kingy-ai-launch-intelligence'); ?></p>
                <div class="kingy-ali-lead-roi__inputs">
                    <label><span><?php esc_html_e('Monthly visitors', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" step="100" value="2500" data-lead-roi-input="visitors"></label>
                    <label><span><?php esc_html_e('Current opt-in rate (%)', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" max="100" step="0.1" value="1.8" data-lead-roi-input="currentRate"></label>
                    <label><span><?php esc_html_e('Interactive opt-in rate (%)', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" max="100" step="0.1" value="4.5" data-lead-roi-input="newRate"></label>
                    <label><span><?php esc_html_e('Value per qualified lead ($)', 'kingy-ai-launch-intelligence'); ?></span><input type="number" min="0" step="10" value="75" data-lead-roi-input="leadValue"></label>
                </div>
            </div>
            <div class="kingy-ali-checklist kingy-ali-lead-roi__output">
                <dl class="kingy-ali-roi-metrics">
                    <div><dt><?php esc_html_e('Current leads', 'kingy-ai-launch-intelligence'); ?></dt><dd data-lead-roi-output="currentLeads">45</dd></div>
                    <div><dt><?php esc_html_e('Interactive leads', 'kingy-ai-launch-intelligence'); ?></dt><dd data-lead-roi-output="newLeads">113</dd></div>
                    <div><dt><?php esc_html_e('Extra leads', 'kingy-ai-launch-intelligence'); ?></dt><dd data-lead-roi-output="extraLeads">68</dd></div>
                    <div><dt><?php esc_html_e('Added monthly value', 'kingy-ai-launch-intelligence'); ?></dt><dd data-lead-roi-output="addedValue">$5,100</dd></div>
                </dl>
                <p><strong data-lead-roi-output="band"><?php esc_html_e('Worth testing', 'kingy-ai-launch-intelligence'); ?></strong></p>
            </div>
        </section>

        <section id="lead-magnet-examples" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Examples library', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('22 lead magnet ideas you can adapt', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Filter by market, then copy the pattern: audience, immediate result, format, and follow-up offer.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-ali-example-filter" role="group" aria-label="<?php esc_attr_e('Filter lead magnet examples', 'kingy-ai-launch-intelligence'); ?>">
                <?php foreach ($categories as $key => $label) : ?>
                    <button type="button" data-lead-example-filter="<?php echo esc_attr($key); ?>"<?php echo $key === 'all' ? ' class="is-active"' : ''; ?>><?php echo esc_html($label); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="kingy-ali-builder-card-grid kingy-ali-lead-example-grid">
                <?php foreach ($examples as $example) : ?>
                    <article class="kingy-ali-builder-card kingy-ali-lead-example-card" data-lead-example-category="<?php echo esc_attr($example['category']); ?>">
                        <div class="kingy-ali-builder-card__header">
                            <h3><?php echo esc_html($example['title']); ?></h3>
                            <span><?php echo esc_html($example['format']); ?></span>
                        </div>
                        <p><?php echo esc_html($example['summary']); ?></p>
                        <dl>
                            <div><dt><?php esc_html_e('Immediate result', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['result']); ?></dd></div>
                            <div><dt><?php esc_html_e('Follow-up offer', 'kingy-ai-launch-intelligence'); ?></dt><dd><?php echo esc_html($example['offer']); ?></dd></div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="lead-magnet-workflow" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Build workflow', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('From idea to published lead magnet', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('1. Pick the promise', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Write the sentence: “In five minutes, this helps [audience] get [specific result].” If that sentence is vague, the lead magnet is too broad.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('2. Generate the useful core', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Ask AI for the checklist, scoring rules, calculation formula, quiz result logic, outline, landing copy, and emails. Edit with real examples.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('3. Publish value first', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Let people see a result, preview, score, or useful sample before asking for an email. Make opt-in explicit and never prechecked.', 'kingy-ai-launch-intelligence'); ?></p></div>
                <div class="kingy-ali-link-panel"><h3><?php esc_html_e('4. Test the whole path', 'kingy-ai-launch-intelligence'); ?></h3><p><?php esc_html_e('Check empty inputs, unrealistic inputs, mobile layout, copy buttons, provider failure, unsubscribe copy, and whether the follow-up matches the promise.', 'kingy-ai-launch-intelligence'); ?></p></div>
            </div>
        </section>

        <section class="kingy-ali-copy-prompt">
            <div>
                <p class="kingy-ali-kicker"><?php esc_html_e('Copy-ready Codex prompt', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Turn your generated plan into a build', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <pre><code data-lead-copy-source>/goal Build a privacy-aware AI lead magnet page from the plan below.

Requirements:
- Inspect the existing site or repo before editing.
- Reuse current styles, forms, tracking attributes, metadata patterns, and internal links.
- Give visitors useful value before any email capture.
- Make email opt-in optional, explicit, unchecked by default, and connected to clear follow-up copy.
- Include the interactive tool, examples, copy buttons, empty states, error states, FAQ, schema, and mobile polish.
- Avoid fake proof, unsupported conversion claims, secrets, and sensitive data collection.

Verification:
- Test empty, partial, complete, reset, copy, print/download if present, desktop, mobile, links, metadata, and schema.
- Summarize files changed, tests run, and limitations.</code></pre>
            <button type="button" data-lead-copy-button data-copy-label="<?php esc_attr_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?>"><?php esc_html_e('Copy prompt', 'kingy-ai-launch-intelligence'); ?></button>
        </section>

        <section id="lead-magnet-resources" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('Keep building', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Related Build With AI resources', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-resource-grid">
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/')); ?>"><strong><?php esc_html_e('Build With AI Academy', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Return to the beginner build path.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/ai-app-builder-for-beginners/')); ?>"><strong><?php esc_html_e('AI App Builder for Beginners', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Turn this into a scoped build plan.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/tools/codex-prompt-builder/')); ?>"><strong><?php esc_html_e('Codex Prompt Builder', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Create a stronger build prompt.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/beginner-safety-rules/')); ?>"><strong><?php esc_html_e('Beginner Safety Rules', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Review privacy, secrets, and approval rules.', 'kingy-ai-launch-intelligence'); ?></span></a>
                <a class="kingy-ali-codex-resource" href="<?php echo esc_url(home_url('/ai/build-with-ai-academy/project-library/')); ?>"><strong><?php esc_html_e('Project Library', 'kingy-ai-launch-intelligence'); ?></strong><span><?php esc_html_e('Find the next beginner AI build.', 'kingy-ai-launch-intelligence'); ?></span></a>
            </div>
        </section>

        <section id="lead-magnet-faq" class="kingy-ali-academy-section">
            <div class="kingy-ali-section-heading">
                <p class="kingy-ali-kicker"><?php esc_html_e('FAQ', 'kingy-ai-launch-intelligence'); ?></p>
                <h2><?php esc_html_e('Common questions about AI lead magnets', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <div class="kingy-ali-faq-list">
                <?php foreach ($faqs as $faq) : ?>
                    <details>
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <p><?php echo esc_html($faq['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
    <?php
    return ob_get_clean();
}

function kingy_ali_ai_lead_magnet_formats() {
    return array(
        'pdf' => array(
            'label' => __('PDF guide', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when the visitor wants a portable reference or printable plan.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for field guides, swipe files, teardown collections, short playbooks, and printable starter packs.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for the outline, examples, page-by-page content, design notes, and a plain-text version for accessibility.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track downloads, saves, email delivery requests, and replies that mention a specific section.', 'kingy-ai-launch-intelligence'),
        ),
        'checklist' => array(
            'label' => __('Checklist', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when the visitor needs confidence they did not miss anything.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for launch prep, audits, onboarding, compliance-light reviews, and beginner workflows.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for categories, pass/fail criteria, examples, and a printable version.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track completions, downloads, and which checklist items expose buying intent.', 'kingy-ai-launch-intelligence'),
        ),
        'calculator' => array(
            'label' => __('Calculator', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when a number helps someone decide if action is worth it.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for ROI, savings, pricing, budget, time cost, traffic value, or capacity planning.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for the formula, default assumptions, edge cases, and plain-language result bands.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track start rate, result views, high-intent bands, and opt-ins after result preview.', 'kingy-ai-launch-intelligence'),
        ),
        'quiz' => array(
            'label' => __('Quiz', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when people want a personalized recommendation or type.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for product fit, style, maturity, readiness, routing, and next-step recommendations.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for questions, answer options, scoring logic, result types, and follow-up copy.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track completion rate, result distribution, email opt-ins, and sales-fit segments.', 'kingy-ai-launch-intelligence'),
        ),
        'assessment' => array(
            'label' => __('Assessment', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when the visitor wants to know how ready or mature they are.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for audits, readiness checks, maturity models, diagnostics, and scorecards.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for dimensions, scoring bands, risks, recommended next steps, and review notes.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track average score, low-score pain areas, high-fit respondents, and booked calls.', 'kingy-ai-launch-intelligence'),
        ),
        'prompt-pack' => array(
            'label' => __('Prompt pack', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when the visitor wants usable words or workflows immediately.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for creators, operators, sales teams, marketers, educators, and AI beginners.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for prompt categories, variables, examples, failure modes, and editing instructions.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track copies, downloads, saves, and replies asking for implementation help.', 'kingy-ai-launch-intelligence'),
        ),
        'mini-course' => array(
            'label' => __('Mini-course', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when trust needs several useful touches instead of one download.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for complex education, behavior change, launches, and service pre-selling.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for lesson sequence, daily exercises, emails, examples, and completion checkpoints.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track lesson opens, replies, completion, clicks, and conversion after the final lesson.', 'kingy-ai-launch-intelligence'),
        ),
        'ai-tool' => array(
            'label' => __('AI tool', 'kingy-ai-launch-intelligence'),
            'hint' => __('Best when the visitor needs a personalized draft, audit, or recommendation.', 'kingy-ai-launch-intelligence'),
            'best' => __('Use for generators, graders, audits, recommendation engines, intake assistants, and interactive reports.', 'kingy-ai-launch-intelligence'),
            'ai' => __('Ask AI for the inputs, output structure, guardrails, fallback copy, scoring logic, and review checklist.', 'kingy-ai-launch-intelligence'),
            'metric' => __('Track starts, completions, copy actions, opt-ins after result preview, and qualified follow-up requests.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_ai_lead_magnet_examples() {
    return array(
        array('category' => 'saas', 'title' => __('Churn Risk Scorecard', 'kingy-ai-launch-intelligence'), 'format' => __('Assessment', 'kingy-ai-launch-intelligence'), 'summary' => __('For SaaS founders who need to spot retention risk before renewal season.', 'kingy-ai-launch-intelligence'), 'result' => __('A churn-risk score with three fixes to try this week.', 'kingy-ai-launch-intelligence'), 'offer' => __('Retention audit or onboarding redesign.', 'kingy-ai-launch-intelligence')),
        array('category' => 'saas', 'title' => __('Pricing Page Leak Finder', 'kingy-ai-launch-intelligence'), 'format' => __('Checklist', 'kingy-ai-launch-intelligence'), 'summary' => __('For product marketers reviewing a pricing page before paid traffic.', 'kingy-ai-launch-intelligence'), 'result' => __('A prioritized list of pricing-page fixes.', 'kingy-ai-launch-intelligence'), 'offer' => __('Conversion copy review.', 'kingy-ai-launch-intelligence')),
        array('category' => 'saas', 'title' => __('Demo ROI Calculator', 'kingy-ai-launch-intelligence'), 'format' => __('Calculator', 'kingy-ai-launch-intelligence'), 'summary' => __('For buyers estimating the value of reducing manual workflow time.', 'kingy-ai-launch-intelligence'), 'result' => __('Monthly time and cost savings estimate.', 'kingy-ai-launch-intelligence'), 'offer' => __('Product demo or implementation call.', 'kingy-ai-launch-intelligence')),
        array('category' => 'coaching', 'title' => __('Client Readiness Quiz', 'kingy-ai-launch-intelligence'), 'format' => __('Quiz', 'kingy-ai-launch-intelligence'), 'summary' => __('For coaches who want to qualify fit without a cold application form.', 'kingy-ai-launch-intelligence'), 'result' => __('Best coaching path and one preparation step.', 'kingy-ai-launch-intelligence'), 'offer' => __('Strategy session.', 'kingy-ai-launch-intelligence')),
        array('category' => 'coaching', 'title' => __('90-Day Goal Planner', 'kingy-ai-launch-intelligence'), 'format' => __('Planner', 'kingy-ai-launch-intelligence'), 'summary' => __('For prospects who need a realistic plan before buying support.', 'kingy-ai-launch-intelligence'), 'result' => __('A simple milestone map and weekly action rhythm.', 'kingy-ai-launch-intelligence'), 'offer' => __('Group program enrollment.', 'kingy-ai-launch-intelligence')),
        array('category' => 'agency', 'title' => __('Ad Account Waste Calculator', 'kingy-ai-launch-intelligence'), 'format' => __('Calculator', 'kingy-ai-launch-intelligence'), 'summary' => __('For brands unsure whether their campaigns are leaking budget.', 'kingy-ai-launch-intelligence'), 'result' => __('Estimated wasted spend and top audit questions.', 'kingy-ai-launch-intelligence'), 'offer' => __('Paid media audit.', 'kingy-ai-launch-intelligence')),
        array('category' => 'agency', 'title' => __('Homepage Clarity Audit', 'kingy-ai-launch-intelligence'), 'format' => __('Scorecard', 'kingy-ai-launch-intelligence'), 'summary' => __('For service businesses comparing their homepage against conversion basics.', 'kingy-ai-launch-intelligence'), 'result' => __('A clarity score and rewrite priority list.', 'kingy-ai-launch-intelligence'), 'offer' => __('Website messaging sprint.', 'kingy-ai-launch-intelligence')),
        array('category' => 'agency', 'title' => __('Launch Email Prompt Pack', 'kingy-ai-launch-intelligence'), 'format' => __('Prompt pack', 'kingy-ai-launch-intelligence'), 'summary' => __('For founders who need launch emails but hate staring at a blank page.', 'kingy-ai-launch-intelligence'), 'result' => __('Five editable AI prompts for launch emails.', 'kingy-ai-launch-intelligence'), 'offer' => __('Launch copy package.', 'kingy-ai-launch-intelligence')),
        array('category' => 'ecommerce', 'title' => __('Product Finder Quiz', 'kingy-ai-launch-intelligence'), 'format' => __('Quiz', 'kingy-ai-launch-intelligence'), 'summary' => __('For shoppers overwhelmed by too many product choices.', 'kingy-ai-launch-intelligence'), 'result' => __('Recommended product, bundle, and buying reason.', 'kingy-ai-launch-intelligence'), 'offer' => __('First-purchase discount or bundle.', 'kingy-ai-launch-intelligence')),
        array('category' => 'ecommerce', 'title' => __('Bundle Builder', 'kingy-ai-launch-intelligence'), 'format' => __('Planner', 'kingy-ai-launch-intelligence'), 'summary' => __('For stores that can raise order value with personalized bundles.', 'kingy-ai-launch-intelligence'), 'result' => __('Suggested bundle and add-on logic.', 'kingy-ai-launch-intelligence'), 'offer' => __('Personalized cart link.', 'kingy-ai-launch-intelligence')),
        array('category' => 'real-estate', 'title' => __('Listing Launch Score', 'kingy-ai-launch-intelligence'), 'format' => __('Assessment', 'kingy-ai-launch-intelligence'), 'summary' => __('For agents preparing a listing before photos, ads, and open houses.', 'kingy-ai-launch-intelligence'), 'result' => __('Launch readiness score and missing prep steps.', 'kingy-ai-launch-intelligence'), 'offer' => __('Listing marketing plan.', 'kingy-ai-launch-intelligence')),
        array('category' => 'real-estate', 'title' => __('Buyer Budget Reality Check', 'kingy-ai-launch-intelligence'), 'format' => __('Calculator', 'kingy-ai-launch-intelligence'), 'summary' => __('For buyers who need a simple first estimate before lender conversations.', 'kingy-ai-launch-intelligence'), 'result' => __('Estimated monthly budget range with caveats.', 'kingy-ai-launch-intelligence'), 'offer' => __('Buyer consultation.', 'kingy-ai-launch-intelligence')),
        array('category' => 'local', 'title' => __('Roof Repair Triage', 'kingy-ai-launch-intelligence'), 'format' => __('Assessment', 'kingy-ai-launch-intelligence'), 'summary' => __('For homeowners deciding whether a roof issue is urgent.', 'kingy-ai-launch-intelligence'), 'result' => __('Urgency band and photos to collect before calling.', 'kingy-ai-launch-intelligence'), 'offer' => __('Inspection booking.', 'kingy-ai-launch-intelligence')),
        array('category' => 'local', 'title' => __('Event Catering Estimator', 'kingy-ai-launch-intelligence'), 'format' => __('Calculator', 'kingy-ai-launch-intelligence'), 'summary' => __('For event planners estimating portions, budget, and service style.', 'kingy-ai-launch-intelligence'), 'result' => __('Rough budget, serving plan, and next questions.', 'kingy-ai-launch-intelligence'), 'offer' => __('Catering quote request.', 'kingy-ai-launch-intelligence')),
        array('category' => 'education', 'title' => __('Course Fit Quiz', 'kingy-ai-launch-intelligence'), 'format' => __('Quiz', 'kingy-ai-launch-intelligence'), 'summary' => __('For learners deciding which course or module to start with.', 'kingy-ai-launch-intelligence'), 'result' => __('Recommended learning path and first lesson.', 'kingy-ai-launch-intelligence'), 'offer' => __('Course enrollment.', 'kingy-ai-launch-intelligence')),
        array('category' => 'education', 'title' => __('Study Plan Generator', 'kingy-ai-launch-intelligence'), 'format' => __('Planner', 'kingy-ai-launch-intelligence'), 'summary' => __('For students who need a schedule from available hours and deadline.', 'kingy-ai-launch-intelligence'), 'result' => __('A weekly study plan with checkpoints.', 'kingy-ai-launch-intelligence'), 'offer' => __('Tutoring or cohort program.', 'kingy-ai-launch-intelligence')),
        array('category' => 'newsletter', 'title' => __('Newsletter Topic Finder', 'kingy-ai-launch-intelligence'), 'format' => __('AI tool', 'kingy-ai-launch-intelligence'), 'summary' => __('For newsletter creators who need topic ideas tied to audience pain, not random prompts.', 'kingy-ai-launch-intelligence'), 'result' => __('A ranked list of issue ideas with angle, promise, and campaign fit.', 'kingy-ai-launch-intelligence'), 'offer' => __('Newsletter growth or creator campaign strategy.', 'kingy-ai-launch-intelligence')),
        array('category' => 'newsletter', 'title' => __('Subscriber Value Audit', 'kingy-ai-launch-intelligence'), 'format' => __('Scorecard', 'kingy-ai-launch-intelligence'), 'summary' => __('For operators checking whether a newsletter is useful enough to earn replies and referrals.', 'kingy-ai-launch-intelligence'), 'result' => __('A usefulness score and three improvements for the next issue.', 'kingy-ai-launch-intelligence'), 'offer' => __('Newsletter positioning sprint.', 'kingy-ai-launch-intelligence')),
        array('category' => 'b2b', 'title' => __('Procurement Readiness Checklist', 'kingy-ai-launch-intelligence'), 'format' => __('Checklist', 'kingy-ai-launch-intelligence'), 'summary' => __('For teams preparing to buy software without delaying legal or security review.', 'kingy-ai-launch-intelligence'), 'result' => __('Docs and stakeholders needed before vendor selection.', 'kingy-ai-launch-intelligence'), 'offer' => __('Implementation advisory.', 'kingy-ai-launch-intelligence')),
        array('category' => 'b2b', 'title' => __('AI Workflow Opportunity Finder', 'kingy-ai-launch-intelligence'), 'format' => __('Assessment', 'kingy-ai-launch-intelligence'), 'summary' => __('For ops leaders looking for safe first AI automation targets.', 'kingy-ai-launch-intelligence'), 'result' => __('Ranked workflow candidates and risk notes.', 'kingy-ai-launch-intelligence'), 'offer' => __('AI workflow workshop.', 'kingy-ai-launch-intelligence')),
        array('category' => 'b2b', 'title' => __('Sales Call Follow-Up Builder', 'kingy-ai-launch-intelligence'), 'format' => __('Prompt pack', 'kingy-ai-launch-intelligence'), 'summary' => __('For sales teams that need consistent recap and next-step emails.', 'kingy-ai-launch-intelligence'), 'result' => __('Prompts for recap, risks, next steps, and stakeholder notes.', 'kingy-ai-launch-intelligence'), 'offer' => __('Sales enablement sprint.', 'kingy-ai-launch-intelligence')),
        array('category' => 'ecommerce', 'title' => __('Gift Recommendation Engine', 'kingy-ai-launch-intelligence'), 'format' => __('Quiz', 'kingy-ai-launch-intelligence'), 'summary' => __('For seasonal shoppers who need a confident gift pick quickly.', 'kingy-ai-launch-intelligence'), 'result' => __('Gift shortlist by recipient, budget, and occasion.', 'kingy-ai-launch-intelligence'), 'offer' => __('Gift bundle or email reminder.', 'kingy-ai-launch-intelligence')),
    );
}

function kingy_ali_ai_lead_magnet_faqs() {
    return array(
        array(
            'question' => __('What is an AI lead magnet?', 'kingy-ai-launch-intelligence'),
            'answer' => __('An AI lead magnet is a useful free resource or interactive tool that uses AI during creation or personalization, such as a checklist, calculator, quiz, prompt pack, scorecard, or mini-course.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('Should a lead magnet require an email?', 'kingy-ai-launch-intelligence'),
            'answer' => __('Not always. The most trustworthy version gives meaningful value first, then offers email delivery, updates, or a deeper report as an optional next step.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What lead magnet format should beginners build first?', 'kingy-ai-launch-intelligence'),
            'answer' => __('Start with a checklist, prompt pack, simple calculator, or assessment because each can solve one narrow problem and be tested without a complex backend.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What makes an AI lead magnet convert?', 'kingy-ai-launch-intelligence'),
            'answer' => __('It converts when the promise is specific, the result is immediate, the format matches the visitor intent, and the follow-up offer is a natural next step.', 'kingy-ai-launch-intelligence'),
        ),
        array(
            'question' => __('What privacy rules matter most?', 'kingy-ai-launch-intelligence'),
            'answer' => __('Avoid hidden collection, prechecked consent, sensitive data, unsupported claims, and unclear provider handling. Explain what happens to the email address and how someone can unsubscribe.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

function kingy_ali_codex_prompt_builder_fields() {
    return array(
        'build' => array(
            'label' => __('What should Codex build?', 'kingy-ai-launch-intelligence'),
            'type' => 'text',
            'required' => true,
            'help' => __('Name the smallest useful thing you want built or changed.', 'kingy-ai-launch-intelligence'),
            'default' => '',
            'placeholder' => __('Dog rescue app', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Dog rescue app', 'kingy-ai-launch-intelligence'),
                __('Local service booking app', 'kingy-ai-launch-intelligence'),
                __('AI tool comparison directory', 'kingy-ai-launch-intelligence'),
                __('Customer intake dashboard', 'kingy-ai-launch-intelligence'),
                __('Course companion chatbot', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'platform' => array(
            'label' => __('Platform', 'kingy-ai-launch-intelligence'),
            'type' => 'select',
            'required' => true,
            'help' => __('Choose the closest surface so the prompt can mention the right kind of project.', 'kingy-ai-launch-intelligence'),
            'default' => 'Codex app',
            'choices' => array('Codex app', 'Codex CLI', 'Codex cloud task', 'ChatGPT', 'Cursor', 'Claude Code', 'Replit', 'Lovable', 'WordPress plugin', 'Next.js app'),
        ),
        'include' => array(
            'label' => __('What should it include?', 'kingy-ai-launch-intelligence'),
            'type' => 'textarea',
            'required' => true,
            'help' => __('List the must-have screens, behaviors, outputs, or acceptance criteria for version one.', 'kingy-ai-launch-intelligence'),
            'rows' => 3,
            'default' => '',
            'placeholder' => __('Core screens, user actions, admin controls, generated outputs...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Landing view, searchable list, detail pages, saved favorites, and a simple admin editor.', 'kingy-ai-launch-intelligence'),
                __('Authentication, onboarding, dashboard, empty states, error states, and responsive mobile layout.', 'kingy-ai-launch-intelligence'),
                __('CSV import/export, filters, sorting, status badges, and audit-friendly timestamps.', 'kingy-ai-launch-intelligence'),
                __('A polished form flow, generated recommendation text, copy button, and reset state.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'not_change' => array(
            'label' => __('What should it not change?', 'kingy-ai-launch-intelligence'),
            'type' => 'textarea',
            'required' => false,
            'help' => __('Optional, but useful when existing URLs, styling, schema, analytics, or workflows must be protected.', 'kingy-ai-launch-intelligence'),
            'rows' => 2,
            'default' => '',
            'placeholder' => __('Existing routes, brand colors, database schema, public URLs...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Do not rename existing routes, shortcodes, custom post types, or public URLs.', 'kingy-ai-launch-intelligence'),
                __('Do not remove existing analytics, tracking attributes, or admin workflows.', 'kingy-ai-launch-intelligence'),
                __('Do not introduce a new design system; extend the current styles.', 'kingy-ai-launch-intelligence'),
                __('Do not use external paid services unless they are already configured.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'user' => array(
            'label' => __('User', 'kingy-ai-launch-intelligence'),
            'type' => 'text',
            'required' => false,
            'help' => __('Optional audience context. Add it when the interface or language should change for a specific user.', 'kingy-ai-launch-intelligence'),
            'default' => '',
            'placeholder' => __('Solo founder, local nonprofit team, course student...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Solo founder who needs to launch quickly without a full engineering team', 'kingy-ai-launch-intelligence'),
                __('Non-technical admin who updates records weekly', 'kingy-ai-launch-intelligence'),
                __('Creator building an audience-facing AI education tool', 'kingy-ai-launch-intelligence'),
                __('Operations team that needs fast search and repeatable workflows', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'style' => array(
            'label' => __('Style to match', 'kingy-ai-launch-intelligence'),
            'type' => 'textarea',
            'required' => false,
            'help' => __('Optional visual guidance. Skip it if the project already has obvious styles Codex can inspect.', 'kingy-ai-launch-intelligence'),
            'rows' => 2,
            'default' => '',
            'placeholder' => __('Quiet SaaS dashboard, current Kingy AI styling, editorial cards...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Match the current Kingy AI forms: clean white panels, dark green-black buttons, restrained borders, readable spacing.', 'kingy-ai-launch-intelligence'),
                __('Use a quiet SaaS dashboard feel with dense but scannable controls.', 'kingy-ai-launch-intelligence'),
                __('Make it warm and editorial, but keep the primary workflow fast and obvious.', 'kingy-ai-launch-intelligence'),
                __('Use compact cards, clear labels, and mobile-first responsive spacing.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'data' => array(
            'label' => __('Data needed', 'kingy-ai-launch-intelligence'),
            'type' => 'textarea',
            'required' => false,
            'help' => __('Optional data context. Include sample records, APIs, tables, or fixtures when the build depends on them.', 'kingy-ai-launch-intelligence'),
            'rows' => 2,
            'default' => '',
            'placeholder' => __('Tables, API fields, sample rows, page content, local files...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Use existing WordPress posts, post meta, taxonomies, and plugin assets only.', 'kingy-ai-launch-intelligence'),
                __('Seed with 8-12 realistic sample records so empty states can be verified.', 'kingy-ai-launch-intelligence'),
                __('Read the current database schema and preserve existing IDs when possible.', 'kingy-ai-launch-intelligence'),
                __('Use local JSON or CSV fixtures for repeatable development and tests.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        'testing' => array(
            'label' => __('Testing to perform', 'kingy-ai-launch-intelligence'),
            'type' => 'textarea',
            'required' => true,
            'help' => __('Name the checks that would prove this worked, including any tests that should be run or manual states to verify.', 'kingy-ai-launch-intelligence'),
            'rows' => 2,
            'default' => '',
            'placeholder' => __('Lint PHP/JS, test form state, verify mobile layout...', 'kingy-ai-launch-intelligence'),
            'suggestions' => array(
                __('Run syntax checks, exercise the happy path, reset state, copy action, and mobile viewport.', 'kingy-ai-launch-intelligence'),
                __('Verify empty state, filled state, validation errors, keyboard navigation, and responsive wrapping.', 'kingy-ai-launch-intelligence'),
                __('Run existing automated tests, then summarize any tests that could not be run.', 'kingy-ai-launch-intelligence'),
                __('Use browser verification to confirm the page is not blank and controls do not overlap.', 'kingy-ai-launch-intelligence'),
            ),
        ),
    );
}

function kingy_ali_codex_prompt_builder_presets() {
    return array(
        array(
            'label' => __('Dog Rescue App', 'kingy-ai-launch-intelligence'),
            'summary' => __('Adoptions, foster intake, volunteer shifts, and admin review.', 'kingy-ai-launch-intelligence'),
            'values' => array(
                'build' => __('Dog rescue app', 'kingy-ai-launch-intelligence'),
                'platform' => 'Codex app',
                'include' => __('Dog profiles, adoption applications, foster applications, volunteer scheduling, searchable filters, and an admin review queue.', 'kingy-ai-launch-intelligence'),
                'not_change' => __('Do not require paid APIs or complex account setup for the first version.', 'kingy-ai-launch-intelligence'),
                'user' => __('Small rescue team with volunteers, foster families, and potential adopters.', 'kingy-ai-launch-intelligence'),
                'style' => __('Warm, trustworthy, and easy to scan; keep forms simple and mobile friendly.', 'kingy-ai-launch-intelligence'),
                'data' => __('Sample dogs, foster homes, volunteer roles, application statuses, and contact notes.', 'kingy-ai-launch-intelligence'),
                'testing' => __('Test application submission, filtering, admin status updates, reset states, and mobile layout.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        array(
            'label' => __('AI Launch Tracker', 'kingy-ai-launch-intelligence'),
            'summary' => __('Searchable launch database with scoring and editorial queues.', 'kingy-ai-launch-intelligence'),
            'values' => array(
                'build' => __('AI launch tracker dashboard', 'kingy-ai-launch-intelligence'),
                'platform' => 'WordPress plugin',
                'include' => __('Launch grid, search filters, scoring fields, source links, company/tool rollups, and editorial queues.', 'kingy-ai-launch-intelligence'),
                'not_change' => __('Do not rename existing post types, taxonomies, shortcodes, or public URLs.', 'kingy-ai-launch-intelligence'),
                'user' => __('Editor who reviews AI launches weekly and turns them into articles or videos.', 'kingy-ai-launch-intelligence'),
                'style' => __('Match existing Kingy AI Launch Intelligence cards, forms, badges, and buttons.', 'kingy-ai-launch-intelligence'),
                'data' => __('Use existing WordPress launch, tool, and company records plus post meta.', 'kingy-ai-launch-intelligence'),
                'testing' => __('Run PHP lint, verify shortcode output, test filters, and check desktop/mobile rendering.', 'kingy-ai-launch-intelligence'),
            ),
        ),
        array(
            'label' => __('Course Companion', 'kingy-ai-launch-intelligence'),
            'summary' => __('Lessons, prompts, resources, and learner progress.', 'kingy-ai-launch-intelligence'),
            'values' => array(
                'build' => __('Build With AI Academy course companion', 'kingy-ai-launch-intelligence'),
                'platform' => 'Next.js app',
                'include' => __('Lesson library, prompt templates, progress checklist, saved notes, resource links, and completion states.', 'kingy-ai-launch-intelligence'),
                'not_change' => __('Do not add authentication until the local prototype works without accounts.', 'kingy-ai-launch-intelligence'),
                'user' => __('Beginner AI builder who wants practical prompts and examples, not theory.', 'kingy-ai-launch-intelligence'),
                'style' => __('Focused learning workspace with clear controls, calm typography, and concise cards.', 'kingy-ai-launch-intelligence'),
                'data' => __('Use local lesson fixtures, prompt categories, difficulty levels, and resource URLs.', 'kingy-ai-launch-intelligence'),
                'testing' => __('Test lesson navigation, progress toggles, saved notes, reset state, and mobile layout.', 'kingy-ai-launch-intelligence'),
            ),
        ),
    );
}
