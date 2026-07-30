<?php
/**
 * Public feed documentation, stable feed aliases, and the weekly embed.
 *
 * This module deliberately packages the existing WordPress/RSS records. It
 * does not expose a JSON API, CSV export, MCP server, or rolling calendar.
 *
 * @package Kingy_AI_Launch_Intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('kingy_feeds_hub', 'kingy_ali_shortcode_feeds_hub');
add_action('init', 'kingy_ali_feeds_hub_register_rewrites', 30);
add_action('init', 'kingy_ali_feeds_hub_maybe_schedule_rewrite_flush', 100);
add_filter('query_vars', 'kingy_ali_feeds_hub_query_vars');
add_action('template_redirect', 'kingy_ali_feeds_hub_template_redirect', -20);
add_action('pre_get_posts', 'kingy_ali_feeds_hub_apply_feed_constraints', 50);
add_action('send_headers', 'kingy_ali_feeds_hub_send_cache_headers', 20);
add_filter('option_rss_use_excerpt', 'kingy_ali_feeds_hub_force_feed_excerpts');
add_filter('the_excerpt_rss', 'kingy_ali_feeds_hub_filter_feed_excerpt', 20);
add_filter('body_class', 'kingy_ali_feeds_hub_body_class');
add_filter('wp_title_rss', 'kingy_ali_feeds_hub_filter_feed_title');
add_filter('get_bloginfo_rss', 'kingy_ali_feeds_hub_filter_feed_description', 20, 2);
add_filter('self_link', 'kingy_ali_feeds_hub_filter_feed_self_link');
add_action('rss2_ns', 'kingy_ali_feeds_hub_output_feed_namespace');
add_action('rss2_item', 'kingy_ali_feeds_hub_output_feed_item_metadata');
add_action('save_post_kingy_ai_launch', 'kingy_ali_feeds_hub_invalidate_health_cache', 999, 3);
add_action('save_post_post', 'kingy_ali_feeds_hub_invalidate_health_cache', 999, 3);
add_action('deleted_post', 'kingy_ali_feeds_hub_invalidate_health_cache', 999);
add_action('set_object_terms', 'kingy_ali_feeds_hub_invalidate_health_cache_for_terms', 999, 6);

/**
 * Existing editorial WordPress feeds promoted by the feeds hub.
 */
function kingy_ali_feeds_hub_editorial_feeds() {
    return array(
        'AI News' => array(
            'slug' => 'news',
            'description' => __('Breaking AI news and evidence-led reporting from Kingy AI.', 'kingy-ai-launch-intelligence'),
        ),
        'Analysis & Guides' => array(
            'slug' => 'blog',
            'description' => __('Evidence-led AI analysis, comparisons, explainers, and practical guides from Kingy AI.', 'kingy-ai-launch-intelligence'),
        ),
    );
}

/**
 * A deliberately small, editorially useful category set for the docs page.
 */
function kingy_ali_feeds_hub_categories() {
    return array(
        'AI agents' => 'ai-agents',
        'AI Productivity Tools' => 'ai-productivity-tools',
        'AI Infrastructure' => 'ai-infrastructure',
        'AI video tools' => 'ai-video-tools',
        'AI coding tools' => 'ai-coding-tools',
        'AI models' => 'ai-models',
        'Open-source AI' => 'open-source-ai',
        'AI image tools' => 'ai-image-tools',
        'AI search tools' => 'ai-search-tools',
        'AI developer tools' => 'ai-developer-tools',
    );
}

/**
 * A broad but bounded audience set for the docs page.
 */
function kingy_ali_feeds_hub_audiences() {
    return array(
        'Developers' => 'developers',
        'Operators' => 'operators',
        'Creators' => 'creators',
        'Founders' => 'founders',
        'Marketers' => 'marketers',
        'Researchers' => 'researchers',
        'Enterprises' => 'enterprises',
        'Small business owners' => 'small-business-owners',
    );
}

function kingy_ali_feeds_hub_specialized_audiences() {
    return array(
        'Agencies' => 'agencies',
    );
}

function kingy_ali_feeds_hub_all_audiences() {
    return array_merge(kingy_ali_feeds_hub_audiences(), kingy_ali_feeds_hub_specialized_audiences());
}

function kingy_ali_feeds_hub_launch_types() {
    return array(
        'New AI products' => 'new-product',
        'Major AI updates' => 'major-update',
        'Open-source AI releases' => 'open-source-release',
    );
}

function kingy_ali_feeds_hub_register_rewrites() {
    add_rewrite_rule('^feeds/launches/?$', 'index.php?kingy_ali_feeds_hub_route=launches', 'top');
    add_rewrite_rule('^feeds/category/([a-z0-9-]+)/?$', 'index.php?kingy_ali_feeds_hub_route=category&kingy_ali_feeds_hub_slug=$matches[1]', 'top');
    add_rewrite_rule('^feeds/audience/([a-z0-9-]+)/?$', 'index.php?kingy_ali_feeds_hub_route=audience&kingy_ali_feeds_hub_slug=$matches[1]', 'top');
    add_rewrite_rule('^feeds/type/([a-z0-9-]+)/?$', 'index.php?kingy_ali_feeds_hub_route=type&kingy_ali_feeds_hub_slug=$matches[1]', 'top');
    add_rewrite_rule('^feeds/founder-submitted/?$', 'index.php?kingy_ali_feeds_hub_route=founder-submitted', 'top');
    add_rewrite_rule('^feeds/pricing-access/?$', 'index.php?kingy_ali_feeds_hub_route=pricing-access', 'top');
    add_rewrite_rule('^feeds/widget/?$', 'index.php?kingy_ali_feeds_hub_route=widget', 'top');
    add_rewrite_rule('^feeds/widget\.js$', 'index.php?kingy_ali_feeds_hub_route=widget-js', 'top');
}

function kingy_ali_feeds_hub_query_vars($vars) {
    $vars[] = 'kingy_ali_feeds_hub_route';
    $vars[] = 'kingy_ali_feeds_hub_slug';
    return array_values(array_unique($vars));
}

function kingy_ali_feeds_hub_rewrite_schema_version() {
    return '2026-07-30-v2';
}

/**
 * Use the plugin's existing deferred flush path so normal front-end requests
 * never perform an expensive rewrite flush.
 */
function kingy_ali_feeds_hub_maybe_schedule_rewrite_flush() {
    $version = kingy_ali_feeds_hub_rewrite_schema_version();
    if (get_option('kingy_ali_feeds_hub_rewrite_schema', '') === $version) {
        return;
    }

    update_option('kingy_ali_feeds_hub_rewrite_schema', $version, false);
    update_option('kingy_ali_flush_rewrite_rules_deferred', '1', false);
}

function kingy_ali_feeds_hub_launch_feed_url() {
    return function_exists('kingy_ali_launch_feed_url')
        ? kingy_ali_launch_feed_url()
        : home_url('/feed/kingy-ai-launches/');
}

function kingy_ali_feeds_hub_stack_feed_url() {
    return home_url('/feed/kingy-ai-stack-changes/');
}

/**
 * Canonical WordPress CPT feeds remain query-style. Pretty /feeds/ aliases
 * redirect here, which keeps WordPress responsible for the feed body.
 */
function kingy_ali_feeds_hub_canonical_cpt_feed_url($taxonomy, $slug) {
    $taxonomy = sanitize_key((string) $taxonomy);
    $slug = sanitize_title((string) $slug);
    if (!in_array($taxonomy, array('kingy_launch_category', 'kingy_audience', 'kingy_launch_type', 'kingy_tool_attribute'), true) || $slug === '') {
        return '';
    }

    return add_query_arg(
        array(
            'feed' => 'rss2',
            'post_type' => 'kingy_ai_launch',
            $taxonomy => $slug,
        ),
        home_url('/')
    );
}

function kingy_ali_feeds_hub_term_is_valid($taxonomy, $slug) {
    $taxonomy = sanitize_key((string) $taxonomy);
    $slug = sanitize_title((string) $slug);
    if ($slug === '' || !taxonomy_exists($taxonomy)) {
        return false;
    }

    $term = get_term_by('slug', $slug, $taxonomy);
    return $term instanceof WP_Term;
}

function kingy_ali_feeds_hub_public_url($kind, $slug = '') {
    $kind = sanitize_key((string) $kind);
    $slug = sanitize_title((string) $slug);
    if (in_array($kind, array('category', 'audience', 'type'), true) && $slug !== '') {
        return home_url('/feeds/' . $kind . '/' . $slug . '/');
    }
    if ($kind === 'editorial' && $slug !== '') {
        return home_url('/category/' . $slug . '/feed/');
    }
    if (in_array($kind, array('launches', 'founder-submitted', 'pricing-access', 'widget'), true)) {
        return home_url('/feeds/' . $kind . '/');
    }
    return home_url('/feeds/');
}

/**
 * Describe a filtered WordPress launch feed using the public /feeds/ alias.
 *
 * Default WordPress feed metadata is site-wide, which made category and
 * audience subscriptions indistinguishable in feed readers.
 */
function kingy_ali_feeds_hub_filtered_feed_descriptor() {
    if (!function_exists('is_feed') || !is_feed()) {
        return array();
    }

    $post_type = get_query_var('post_type');
    $is_launch_feed = $post_type === 'kingy_ai_launch'
        || (is_array($post_type) && in_array('kingy_ai_launch', $post_type, true));
    if (!$is_launch_feed) {
        return array();
    }

    $requests = array(
        'category' => array(
            'taxonomy' => 'kingy_launch_category',
            'labels' => kingy_ali_feeds_hub_categories(),
            'description' => __('Source-backed AI launch records in the %s category, ordered by Kingy publication date.', 'kingy-ai-launch-intelligence'),
        ),
        'audience' => array(
            'taxonomy' => 'kingy_audience',
            'labels' => kingy_ali_feeds_hub_all_audiences(),
            'description' => __('Source-backed AI launch records selected for %s, ordered by Kingy publication date.', 'kingy-ai-launch-intelligence'),
        ),
        'type' => array(
            'taxonomy' => 'kingy_launch_type',
            'labels' => kingy_ali_feeds_hub_launch_types(),
            'description' => __('Source-backed %s from Kingy AI, ordered by Kingy publication date.', 'kingy-ai-launch-intelligence'),
        ),
    );

    foreach ($requests as $kind => $request) {
        $raw_slug = get_query_var($request['taxonomy']);
        $slug = is_scalar($raw_slug) ? sanitize_title((string) $raw_slug) : '';
        if ($slug === '') {
            continue;
        }
        $label = array_search($slug, $request['labels'], true);
        if ($label === false) {
            $term = get_term_by('slug', $slug, $request['taxonomy']);
            $label = $term instanceof WP_Term ? $term->name : ucwords(str_replace('-', ' ', $slug));
        }
        return array(
            'kind' => $kind,
            'slug' => $slug,
            'label' => (string) $label,
            'title' => get_bloginfo('name') . ' — ' . (string) $label,
            'description' => sprintf($request['description'], (string) $label),
            'url' => kingy_ali_feeds_hub_public_url($kind, $slug),
        );
    }

    $raw_attribute = get_query_var('kingy_tool_attribute');
    $attribute = is_scalar($raw_attribute) ? sanitize_title((string) $raw_attribute) : '';
    if ($attribute === 'founder-submitted') {
        return array(
            'kind' => 'founder-submitted',
            'slug' => $attribute,
            'label' => __('Founder-submitted launches', 'kingy-ai-launch-intelligence'),
            'title' => get_bloginfo('name') . ' — ' . __('Founder-submitted launches', 'kingy-ai-launch-intelligence'),
            'description' => __('Verified founder-submitted AI launch records from Kingy AI, ordered by publication date.', 'kingy-ai-launch-intelligence'),
            'url' => kingy_ali_feeds_hub_public_url('founder-submitted'),
        );
    }

    return array();
}

function kingy_ali_feeds_hub_filter_feed_title($title) {
    $descriptor = kingy_ali_feeds_hub_filtered_feed_descriptor();
    return !empty($descriptor['title']) ? $descriptor['title'] : $title;
}

function kingy_ali_feeds_hub_filter_feed_description($output, $show) {
    if ((string) $show !== 'description') {
        return $output;
    }
    $descriptor = kingy_ali_feeds_hub_filtered_feed_descriptor();
    return !empty($descriptor['description']) ? $descriptor['description'] : $output;
}

function kingy_ali_feeds_hub_filter_feed_self_link($url) {
    $descriptor = kingy_ali_feeds_hub_filtered_feed_descriptor();
    return !empty($descriptor['url']) ? $descriptor['url'] : $url;
}

function kingy_ali_feeds_hub_output_feed_namespace() {
    if (kingy_ali_feeds_hub_filtered_feed_descriptor()) {
        echo ' xmlns:kingy="https://kingy.ai/ns/launch-intelligence/1.0"';
    }
}

/**
 * Preserve the record's event/launch date without overloading RSS pubDate.
 */
function kingy_ali_feeds_hub_output_feed_item_metadata() {
    if (!kingy_ali_feeds_hub_filtered_feed_descriptor()) {
        return;
    }
    $post_id = get_the_ID();
    $launch_date = $post_id && function_exists('kingy_ali_public_profile_meta_text')
        ? kingy_ali_public_profile_meta_text($post_id, 'launch_date')
        : '';
    if ($launch_date !== '') {
        echo "\t\t<kingy:launchDate>" . esc_html($launch_date) . "</kingy:launchDate>\n";
    }
}

function kingy_ali_feeds_hub_body_class($classes) {
    if (function_exists('is_page') && is_page('feeds')) {
        $classes[] = 'kingy-ali-feeds-hub-page';
    }
    return array_values(array_unique($classes));
}

function kingy_ali_feeds_hub_request_method_is_readable() {
    $method = isset($_SERVER['REQUEST_METHOD']) && is_scalar($_SERVER['REQUEST_METHOD'])
        ? strtoupper((string) wp_unslash($_SERVER['REQUEST_METHOD']))
        : 'GET';
    return in_array($method, array('GET', 'HEAD'), true);
}

function kingy_ali_feeds_hub_is_head_request() {
    return isset($_SERVER['REQUEST_METHOD'])
        && is_scalar($_SERVER['REQUEST_METHOD'])
        && strtoupper((string) wp_unslash($_SERVER['REQUEST_METHOD'])) === 'HEAD';
}

function kingy_ali_feeds_hub_cache_headers() {
    if (headers_sent()) {
        return;
    }
    header('CDN-Cache-Control: public, max-age=300', true);
    header('Surrogate-Control: max-age=300', true);
    header('Cache-Control: public, max-age=300, s-maxage=300, stale-while-revalidate=60', true);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT', true);
    header('X-Content-Type-Options: nosniff', true);
    header('X-Kingy-Feeds-Cache-Policy: canonical-300', true);
}

function kingy_ali_feeds_hub_fail_closed($message = '') {
    status_header(404);
    if (function_exists('nocache_headers')) {
        nocache_headers();
    }
    header('Content-Type: text/plain; charset=' . get_option('blog_charset', 'UTF-8'), true);
    header('X-Robots-Tag: noindex, nofollow', true);
    if (!kingy_ali_feeds_hub_is_head_request()) {
        echo esc_html($message !== '' ? $message : __('Feed not found.', 'kingy-ai-launch-intelligence'));
    }
    exit;
}

function kingy_ali_feeds_hub_redirect($url) {
    $url = esc_url_raw((string) $url, array('http', 'https'));
    if ($url === '') {
        kingy_ali_feeds_hub_fail_closed();
    }
    kingy_ali_feeds_hub_cache_headers();
    wp_safe_redirect($url, 301, 'Kingy AI Feeds');
    exit;
}

function kingy_ali_feeds_hub_template_redirect() {
    $route = sanitize_key((string) get_query_var('kingy_ali_feeds_hub_route'));
    if ($route === '') {
        return;
    }

    if (!kingy_ali_feeds_hub_request_method_is_readable()) {
        status_header(405);
        header('Allow: GET, HEAD', true);
        exit;
    }

    if ($route === 'launches') {
        kingy_ali_feeds_hub_redirect(kingy_ali_feeds_hub_launch_feed_url());
    }

    if ($route === 'category' || $route === 'audience' || $route === 'type') {
        $slug = sanitize_title((string) get_query_var('kingy_ali_feeds_hub_slug'));
        $taxonomy = $route === 'category'
            ? 'kingy_launch_category'
            : ($route === 'audience' ? 'kingy_audience' : 'kingy_launch_type');
        if (!kingy_ali_feeds_hub_term_is_valid($taxonomy, $slug)) {
            kingy_ali_feeds_hub_fail_closed(__('Unknown feed term.', 'kingy-ai-launch-intelligence'));
        }
        kingy_ali_feeds_hub_redirect(kingy_ali_feeds_hub_canonical_cpt_feed_url($taxonomy, $slug));
    }

    if ($route === 'founder-submitted') {
        if (!kingy_ali_feeds_hub_term_is_valid('kingy_tool_attribute', 'founder-submitted')) {
            kingy_ali_feeds_hub_fail_closed(__('Founder-submitted feed is not configured.', 'kingy-ai-launch-intelligence'));
        }
        kingy_ali_feeds_hub_redirect(kingy_ali_feeds_hub_canonical_cpt_feed_url('kingy_tool_attribute', 'founder-submitted'));
    }

    if ($route === 'pricing-access') {
        kingy_ali_feeds_hub_render_pricing_access_feed();
        exit;
    }

    if ($route === 'widget') {
        kingy_ali_feeds_hub_render_widget();
        exit;
    }

    if ($route === 'widget-js') {
        kingy_ali_feeds_hub_render_widget_loader();
        exit;
    }

    kingy_ali_feeds_hub_fail_closed();
}

/**
 * Identify canonical existing feeds and the filtered CPT feeds packaged here.
 */
function kingy_ali_feeds_hub_is_summary_feed_request() {
    if (!function_exists('is_feed') || !is_feed()) {
        return false;
    }

    $feed = sanitize_key((string) get_query_var('feed'));
    if (in_array($feed, array('kingy-ai-launches', 'kingy-ai-stack-changes'), true)) {
        return true;
    }

    $category_name = sanitize_title((string) get_query_var('category_name'));
    foreach (kingy_ali_feeds_hub_editorial_feeds() as $editorial) {
        if (!empty($editorial['slug']) && $category_name === sanitize_title((string) $editorial['slug'])) {
            return true;
        }
    }

    $post_type = get_query_var('post_type');
    $is_launch_feed = $post_type === 'kingy_ai_launch'
        || (is_array($post_type) && in_array('kingy_ai_launch', $post_type, true));
    if (!$is_launch_feed) {
        return false;
    }

    // The long-standing query-style CPT URL is a first-class packaged feed,
    // including when no taxonomy filter is present.
    return true;
}

function kingy_ali_feeds_hub_send_cache_headers() {
    if (kingy_ali_feeds_hub_is_summary_feed_request()) {
        kingy_ali_feeds_hub_cache_headers();
    }
}

function kingy_ali_feeds_hub_force_feed_excerpts($value) {
    return kingy_ali_feeds_hub_is_summary_feed_request() ? 1 : $value;
}

/**
 * Keep query-style CPT feeds useful while ensuring they cannot disclose the
 * full post body even when the WordPress site setting requests full text.
 */
function kingy_ali_feeds_hub_filter_feed_excerpt($excerpt) {
    if (!kingy_ali_feeds_hub_is_summary_feed_request()) {
        return $excerpt;
    }

    $post_id = get_the_ID();
    $post_type = $post_id && function_exists('get_post_type') ? get_post_type($post_id) : '';
    $summary = $post_id
        && $post_type === 'kingy_ai_launch'
        && function_exists('kingy_ali_public_profile_meta_text')
        ? kingy_ali_public_profile_meta_text($post_id, 'what_launched', $excerpt)
        : $excerpt;
    $summary = trim(wp_strip_all_tags((string) $summary));
    return wp_trim_words($summary, 55, '&hellip;');
}

/**
 * Founder-submitted is promoted only after verification. This makes an empty
 * feed a truthful representation of the editorial queue, not a broad fallback.
 */
function kingy_ali_feeds_hub_apply_feed_constraints($query) {
    if (is_admin() || !$query instanceof WP_Query || !$query->is_main_query() || !$query->is_feed()) {
        return;
    }

    $post_type = $query->get('post_type');
    $is_launch_feed = $post_type === 'kingy_ai_launch'
        || (is_array($post_type) && in_array('kingy_ai_launch', $post_type, true));
    if (!$is_launch_feed || sanitize_title((string) $query->get('kingy_tool_attribute')) !== 'founder-submitted') {
        return;
    }

    $meta_query = $query->get('meta_query');
    $meta_query = is_array($meta_query) ? $meta_query : array();
    $meta_query[] = array(
        'key' => function_exists('kingy_ali_meta_key') ? kingy_ali_meta_key('verification_status') : '_kingy_ali_verification_status',
        'value' => 'verified',
        'compare' => '=',
    );
    $query->set('meta_query', $meta_query);
}

/**
 * Read only the three supported structured Stack Change Radar types. No title,
 * body, or prose matching is used to infer a pricing/access event.
 */
function kingy_ali_feeds_hub_pricing_access_events($limit = 50) {
    if (!function_exists('kingy_ali_stack_public_query') || !function_exists('kingy_ali_stack_get_event')) {
        return array();
    }

    $allowed_types = array('price_change', 'limit_change', 'access_change');
    $events = array();
    $per_type = min(100, max(1, absint($limit)));
    foreach ($allowed_types as $change_type) {
        $query = kingy_ali_stack_public_query(
            array(
                'change_type' => $change_type,
                'per_page' => $per_type,
            )
        );
        foreach ((array) $query->posts as $post) {
            $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
            if (!$post_id || (function_exists('kingy_ali_stack_is_public_event') && !kingy_ali_stack_is_public_event($post_id))) {
                continue;
            }
            $event = kingy_ali_stack_get_event($post_id);
            if (!is_array($event) || !in_array($event['change_type'], $allowed_types, true)) {
                continue;
            }
            $events[$post_id] = $event;
        }
    }

    uasort(
        $events,
        function ($left, $right) {
            $left_time = !empty($left['published']) ? strtotime($left['published']) : 0;
            $right_time = !empty($right['published']) ? strtotime($right['published']) : 0;
            if ($left_time === $right_time) {
                return (int) $right['id'] <=> (int) $left['id'];
            }
            return $right_time <=> $left_time;
        }
    );

    return array_slice(array_values($events), 0, min(100, max(1, absint($limit))));
}

function kingy_ali_feeds_hub_render_pricing_access_feed() {
    $events = kingy_ali_feeds_hub_pricing_access_events(50);
    status_header(200);
    kingy_ali_feeds_hub_cache_headers();
    if (function_exists('kingy_ali_feed_metrics_capture_pricing_request')) {
        kingy_ali_feed_metrics_capture_pricing_request();
    }
    header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset', 'UTF-8'), true);
    header('X-Robots-Tag: noindex, follow', true);
    if (kingy_ali_feeds_hub_is_head_request()) {
        return;
    }

    $self_url = kingy_ali_feeds_hub_public_url('pricing-access');
    $last_build = 0;
    foreach ($events as $event) {
        $last_build = max($last_build, !empty($event['published']) ? (int) strtotime($event['published']) : 0);
    }

    echo '<?xml version="1.0" encoding="' . esc_attr(get_option('blog_charset', 'UTF-8')) . '"?>' . "\n";
    ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?php echo esc_html(get_bloginfo('name') . ' — AI pricing and access changes'); ?></title>
    <link><?php echo esc_url(home_url('/ai-stack-change-radar/')); ?></link>
    <atom:link href="<?php echo esc_url($self_url); ?>" rel="self" type="application/rss+xml" />
    <description><?php esc_html_e('Reviewed AI vendor price, limit, and access changes from Kingy AI.', 'kingy-ai-launch-intelligence'); ?></description>
    <?php if ($last_build) : ?><lastBuildDate><?php echo esc_html(gmdate(DATE_RSS, $last_build)); ?></lastBuildDate><?php endif; ?>
    <?php foreach ($events as $event) :
        $timestamp = !empty($event['published']) ? strtotime($event['published']) : 0;
        $summary = trim((string) $event['source_summary']);
        if (!empty($event['required_action'])) {
            $summary .= ($summary !== '' ? ' ' : '') . __('Action:', 'kingy-ai-launch-intelligence') . ' ' . trim((string) $event['required_action']);
        }
        $summary = wp_trim_words(wp_strip_all_tags($summary), 55, '&hellip;');
        ?>
    <item>
        <title><?php echo esc_html($event['title']); ?></title>
        <link><?php echo esc_url($event['url']); ?></link>
        <guid isPermaLink="false">kingy-pricing-access-<?php echo absint($event['id']); ?></guid>
        <?php if ($timestamp) : ?><pubDate><?php echo esc_html(gmdate(DATE_RSS, $timestamp)); ?></pubDate><?php endif; ?>
        <category><?php echo esc_html(ucwords(str_replace('_', ' ', $event['change_type']))); ?></category>
        <description><?php echo esc_html($summary); ?></description>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
    <?php
}

function kingy_ali_feeds_hub_verified_weekly_launches($limit = 5) {
    if (!function_exists('kingy_ali_launch_index_run')) {
        return array();
    }

    $limit = min(8, max(1, absint($limit)));
    $query = kingy_ali_launch_index_run(
        array(
            'limit' => max(20, $limit * 4),
            'period' => 'week',
            'sort' => 'newest',
            'no_found_rows' => true,
        )
    );
    $items = array();
    foreach ((array) $query->posts as $post) {
        $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
        if (!$post_id || get_post_status($post_id) !== 'publish') {
            continue;
        }
        if (function_exists('kingy_ali_related_post_is_public_index_ready') && !kingy_ali_related_post_is_public_index_ready($post_id, 'kingy_ai_launch')) {
            continue;
        }
        $status = function_exists('kingy_ali_canonical_verification_status')
            ? kingy_ali_canonical_verification_status(kingy_ali_get_meta($post_id, 'verification_status'))
            : sanitize_key((string) get_post_meta($post_id, '_kingy_ali_verification_status', true));
        if ($status !== 'verified') {
            continue;
        }
        if (function_exists('kingy_ali_last_verified_needs_update') && kingy_ali_last_verified_needs_update($post_id)) {
            continue;
        }

        $summary = function_exists('kingy_ali_public_profile_meta_text')
            ? kingy_ali_public_profile_meta_text($post_id, 'what_launched', get_the_excerpt($post_id))
            : get_the_excerpt($post_id);
        $launch_date = function_exists('kingy_ali_public_profile_meta_text')
            ? kingy_ali_public_profile_meta_text($post_id, 'launch_date')
            : '';
        $items[] = array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'summary' => wp_trim_words(wp_strip_all_tags((string) $summary), 28, '&hellip;'),
            'date' => $launch_date,
        );
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function kingy_ali_feeds_hub_widget_tracking_url($url, $content = 'launch') {
    $url = esc_url_raw((string) $url, array('http', 'https'));
    if ($url === '') {
        return '';
    }
    return add_query_arg(
        array(
            'utm_source' => 'kingy-widget',
            'utm_medium' => 'embed',
            'utm_campaign' => 'this-week-in-ai',
            'utm_content' => sanitize_key((string) $content),
        ),
        $url
    );
}

function kingy_ali_feeds_hub_render_widget() {
    $items = kingy_ali_feeds_hub_verified_weekly_launches(5);
    status_header(200);
    kingy_ali_feeds_hub_cache_headers();
    header('Content-Type: text/html; charset=' . get_option('blog_charset', 'UTF-8'), true);
    header('X-Robots-Tag: noindex, nofollow', true);
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; connect-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors *", true);
    if (kingy_ali_feeds_hub_is_head_request()) {
        return;
    }
    $language = get_bloginfo('language') ? get_bloginfo('language') : 'en-US';
    $metric_config = function_exists('kingy_ali_feed_metrics_widget_config')
        ? kingy_ali_feed_metrics_widget_config()
        : array();
    ?>
<!doctype html>
<html lang="<?php echo esc_attr($language); ?>">
<head>
    <meta charset="<?php echo esc_attr(get_option('blog_charset', 'UTF-8')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('This Week in AI — Kingy AI', 'kingy-ai-launch-intelligence'); ?></title>
    <style>
        :root{color-scheme:light;font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}*{box-sizing:border-box}body{background:#f3f7f4;color:#172026;margin:0;padding:14px}.kingy-week{background:#fff;border:1px solid #dbe5de;border-radius:14px;box-shadow:0 8px 24px rgba(23,32,38,.08);margin:auto;max-width:720px;overflow:hidden}.kingy-week__head{background:#173f35;color:#fff;padding:18px 20px}.kingy-week__eyebrow{color:#a9dec8;font-size:11px;font-weight:800;letter-spacing:.08em;margin:0 0 5px;text-transform:uppercase}.kingy-week h1{font-size:22px;line-height:1.2;margin:0}.kingy-week__list{list-style:none;margin:0;padding:0}.kingy-week__item{border-top:1px solid #e4ebe7;padding:14px 20px}.kingy-week__item:first-child{border-top:0}.kingy-week__item a{color:#174c3f;font-size:16px;font-weight:750;line-height:1.3;text-decoration:none}.kingy-week__item a:hover,.kingy-week__item a:focus{text-decoration:underline}.kingy-week__item p{color:#52625a;font-size:13px;line-height:1.5;margin:6px 0 0}.kingy-week__date{color:#68766f;font-size:11px;font-weight:700;margin-left:7px;white-space:nowrap}.kingy-week__empty{color:#52625a;font-size:14px;line-height:1.55;margin:0;padding:24px 20px}.kingy-week__foot{align-items:center;background:#f7faf8;border-top:1px solid #dbe5de;display:flex;font-size:12px;justify-content:space-between;padding:11px 20px}.kingy-week__foot a{color:#174c3f;font-weight:800;text-decoration:none}.kingy-week__foot span{color:#68766f}@media(max-width:460px){body{padding:8px}.kingy-week__head,.kingy-week__item,.kingy-week__empty{padding-left:15px;padding-right:15px}.kingy-week__foot{align-items:flex-start;gap:4px;flex-direction:column;padding-left:15px;padding-right:15px}}
    </style>
</head>
<body>
<section class="kingy-week" aria-labelledby="kingy-week-title">
    <header class="kingy-week__head">
        <p class="kingy-week__eyebrow"><?php esc_html_e('Verified launch records', 'kingy-ai-launch-intelligence'); ?></p>
        <h1 id="kingy-week-title"><?php esc_html_e('This Week in AI', 'kingy-ai-launch-intelligence'); ?></h1>
    </header>
    <?php if ($items) : ?>
        <ol class="kingy-week__list">
            <?php foreach ($items as $item) : ?>
                <li class="kingy-week__item">
                    <a href="<?php echo esc_url(kingy_ali_feeds_hub_widget_tracking_url($item['url'], 'launch')); ?>" data-kingy-widget-metric="launch" target="_blank" rel="noopener noreferrer"><?php echo esc_html($item['title']); ?></a>
                    <?php if ($item['date']) : ?><time class="kingy-week__date" datetime="<?php echo esc_attr($item['date']); ?>"><?php echo esc_html(date_i18n('M j', strtotime($item['date']))); ?></time><?php endif; ?>
                    <p><?php echo esc_html($item['summary']); ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?>
        <p class="kingy-week__empty"><?php esc_html_e('No fresh, verified launch records are available for the last seven days. Check back soon.', 'kingy-ai-launch-intelligence'); ?></p>
    <?php endif; ?>
    <footer class="kingy-week__foot">
        <span><?php esc_html_e('Updated from reviewed Kingy records', 'kingy-ai-launch-intelligence'); ?></span>
        <a href="<?php echo esc_url(kingy_ali_feeds_hub_widget_tracking_url(home_url('/feeds/'), 'credit')); ?>" data-kingy-widget-metric="credit" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Powered by Kingy.ai', 'kingy-ai-launch-intelligence'); ?></a>
    </footer>
</section>
<?php if (!empty($metric_config['endpoint']) && !empty($metric_config['nonce'])) : ?>
<script>
(function () {
    'use strict';
    var endpoint = <?php echo wp_json_encode($metric_config['endpoint']); ?>;
    var nonce = <?php echo wp_json_encode($metric_config['nonce']); ?>;
    var source = 'direct';
    if (document.referrer) {
        try { source = new URL(document.referrer).hostname.toLowerCase() || 'direct'; } catch (error) {}
    }
    function send(metric, content) {
        var body = new URLSearchParams({action:'kingy_ali_feed_metric',nonce:nonce,metric:metric,content:content,source:source}).toString();
        if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([body], {type:'application/x-www-form-urlencoded;charset=UTF-8'}));
            return;
        }
        if (window.fetch) {
            window.fetch(endpoint, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:body,credentials:'omit',keepalive:true}).catch(function () {});
        }
    }
    send('widget_view', 'embed');
    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a[data-kingy-widget-metric]') : null;
        if (link) { send('widget_click', link.getAttribute('data-kingy-widget-metric') || ''); }
    });
}());
</script>
<?php endif; ?>
</body>
</html>
    <?php
}

function kingy_ali_feeds_hub_render_widget_loader() {
    status_header(200);
    kingy_ali_feeds_hub_cache_headers();
    header('Content-Type: application/javascript; charset=UTF-8', true);
    header('X-Robots-Tag: noindex, nofollow', true);
    if (kingy_ali_feeds_hub_is_head_request()) {
        return;
    }
    echo <<<'JS'
(function () {
    'use strict';
    var script = document.currentScript;
    if (!script || !script.src || script.getAttribute('data-kingy-loaded') === '1') {
        return;
    }
    script.setAttribute('data-kingy-loaded', '1');
    var source = new URL(script.src, document.baseURI);
    source.pathname = '/feeds/widget/';
    source.search = '';
    source.hash = '';
    var requestedHeight = parseInt(script.getAttribute('data-height'), 10);
    var height = requestedHeight >= 280 && requestedHeight <= 1000 ? requestedHeight : 520;
    var title = script.getAttribute('data-title') || 'This Week in AI from Kingy';
    var frame = document.createElement('iframe');
    frame.src = source.toString();
    frame.title = title;
    frame.loading = 'lazy';
    frame.width = '100%';
    frame.height = String(height);
    frame.setAttribute('scrolling', 'auto');
    frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
    frame.style.border = '0';
    frame.style.display = 'block';
    frame.style.maxWidth = '720px';
    frame.style.width = '100%';
    script.parentNode.insertBefore(frame, script.nextSibling);
}());
JS;
}

function kingy_ali_feeds_hub_health_cache_key() {
    return 'kingy_ali_feeds_hub_health_v3';
}

function kingy_ali_feeds_hub_invalidate_health_cache($post_id = 0, $post = null, $update = false) {
    unset($update);
    $post_id = absint($post_id);
    if ($post_id && function_exists('wp_is_post_revision') && wp_is_post_revision($post_id)) {
        return;
    }
    if (
        is_object($post)
        && isset($post->post_type)
        && !in_array((string) $post->post_type, array('kingy_ai_launch', 'post'), true)
    ) {
        return;
    }
    delete_transient(kingy_ali_feeds_hub_health_cache_key());
}

function kingy_ali_feeds_hub_invalidate_health_cache_for_terms($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    unset($terms, $tt_ids, $append, $old_tt_ids);
    if (
        absint($object_id)
        && in_array((string) $taxonomy, array('category', 'kingy_launch_category', 'kingy_audience', 'kingy_launch_type', 'kingy_tool_attribute'), true)
    ) {
        delete_transient(kingy_ali_feeds_hub_health_cache_key());
    }
}

function kingy_ali_feeds_hub_health_record($id, $label, $url, $conditional = false) {
    return array(
        'id' => sanitize_key((string) $id),
        'label' => (string) $label,
        'url' => esc_url_raw((string) $url, array('http', 'https')),
        'conditional' => (bool) $conditional,
        'item_count' => 0,
        'total_count' => 0,
        'latest_timestamp' => 0,
        'status' => 'empty',
    );
}

function kingy_ali_feeds_hub_health_add_item(&$record, $timestamp, $response_limit) {
    if (!is_array($record)) {
        return;
    }
    $record['total_count']++;
    if ($record['item_count'] < max(1, absint($response_limit))) {
        $record['item_count']++;
    }
    $record['latest_timestamp'] = max(absint($record['latest_timestamp']), absint($timestamp));
}

function kingy_ali_feeds_hub_health_finalize(&$record, $checked_at) {
    if (!is_array($record)) {
        return;
    }
    if (empty($record['item_count'])) {
        $record['status'] = 'empty';
        return;
    }
    $quiet_after = defined('DAY_IN_SECONDS') ? 30 * DAY_IN_SECONDS : 2592000;
    $record['status'] = !empty($record['latest_timestamp'])
        && absint($record['latest_timestamp']) < absint($checked_at) - $quiet_after
        ? 'quiet'
        : 'live';
}

/**
 * Build one cached, database-backed health manifest for every advertised feed.
 *
 * This avoids making loopback HTTP requests from the page while keeping its
 * status labels aligned with the same records used by the RSS responses.
 */
function kingy_ali_feeds_hub_health_manifest($force = false) {
    $cache_key = kingy_ali_feeds_hub_health_cache_key();
    if (!$force) {
        $cached = get_transient($cache_key);
        if (is_array($cached) && !empty($cached['feeds']) && !empty($cached['checked_at'])) {
            return $cached;
        }
    }

    $rss_limit = max(1, min(100, absint(get_option('posts_per_rss', 20))));
    $checked_at = function_exists('current_time') ? (int) current_time('timestamp', true) : time();
    $feeds = array(
        'launches' => kingy_ali_feeds_hub_health_record(
            'launches',
            __('All AI launches', 'kingy-ai-launch-intelligence'),
            kingy_ali_feeds_hub_public_url('launches')
        ),
        'stack' => kingy_ali_feeds_hub_health_record(
            'stack',
            __('AI Stack Change Radar', 'kingy-ai-launch-intelligence'),
            kingy_ali_feeds_hub_stack_feed_url()
        ),
        'pricing-access' => kingy_ali_feeds_hub_health_record(
            'pricing-access',
            __('AI pricing and access changes', 'kingy-ai-launch-intelligence'),
            kingy_ali_feeds_hub_public_url('pricing-access'),
            true
        ),
        'founder-submitted' => kingy_ali_feeds_hub_health_record(
            'founder-submitted',
            __('Founder-submitted launches', 'kingy-ai-launch-intelligence'),
            kingy_ali_feeds_hub_public_url('founder-submitted'),
            true
        ),
    );

    foreach (kingy_ali_feeds_hub_editorial_feeds() as $label => $editorial) {
        $slug = !empty($editorial['slug']) ? sanitize_title((string) $editorial['slug']) : '';
        if ($slug === '') {
            continue;
        }
        $key = 'editorial:' . $slug;
        $feeds[$key] = kingy_ali_feeds_hub_health_record(
            $key,
            $label,
            kingy_ali_feeds_hub_public_url('editorial', $slug)
        );
    }
    foreach (kingy_ali_feeds_hub_categories() as $label => $slug) {
        $key = 'category:' . $slug;
        $feeds[$key] = kingy_ali_feeds_hub_health_record(
            $key,
            $label,
            kingy_ali_feeds_hub_public_url('category', $slug)
        );
    }
    foreach (kingy_ali_feeds_hub_all_audiences() as $label => $slug) {
        $key = 'audience:' . $slug;
        $feeds[$key] = kingy_ali_feeds_hub_health_record(
            $key,
            $label,
            kingy_ali_feeds_hub_public_url('audience', $slug)
        );
    }
    foreach (kingy_ali_feeds_hub_launch_types() as $label => $slug) {
        $key = 'type:' . $slug;
        $feeds[$key] = kingy_ali_feeds_hub_health_record(
            $key,
            $label,
            kingy_ali_feeds_hub_public_url('type', $slug)
        );
    }

    foreach (kingy_ali_feeds_hub_editorial_feeds() as $editorial) {
        $slug = !empty($editorial['slug']) ? sanitize_title((string) $editorial['slug']) : '';
        $key = 'editorial:' . $slug;
        if ($slug === '' || !isset($feeds[$key])) {
            continue;
        }
        $editorial_query = new WP_Query(
            array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'category_name' => $slug,
                'posts_per_page' => $rss_limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'fields' => 'ids',
                'no_found_rows' => true,
            )
        );
        foreach ((array) $editorial_query->posts as $post_id) {
            kingy_ali_feeds_hub_health_add_item(
                $feeds[$key],
                (int) get_post_time('U', true, absint($post_id)),
                $rss_limit
            );
        }
    }

    if (function_exists('kingy_ali_launch_index_run')) {
        $query = kingy_ali_launch_index_run(
            array(
                'limit' => 0,
                'sort' => 'recently_added',
                'fields' => 'ids',
                'no_found_rows' => true,
            )
        );
        foreach (array_values(array_unique(array_filter(array_map('absint', (array) $query->posts)))) as $post_id) {
            $timestamp = (int) get_post_time('U', true, $post_id);
            kingy_ali_feeds_hub_health_add_item($feeds['launches'], $timestamp, $rss_limit);

            $terms = wp_get_object_terms(
                $post_id,
                array('kingy_launch_category', 'kingy_audience', 'kingy_launch_type', 'kingy_tool_attribute')
            );
            $terms = is_wp_error($terms) ? array() : (array) $terms;
            $term_slugs = array(
                'kingy_launch_category' => array(),
                'kingy_audience' => array(),
                'kingy_launch_type' => array(),
                'kingy_tool_attribute' => array(),
            );
            foreach ($terms as $term) {
                if (
                    $term instanceof WP_Term
                    && isset($term_slugs[$term->taxonomy])
                ) {
                    $term_slugs[$term->taxonomy][] = sanitize_title($term->slug);
                }
            }

            foreach (array_unique($term_slugs['kingy_launch_category']) as $slug) {
                $key = 'category:' . $slug;
                if (isset($feeds[$key])) {
                    kingy_ali_feeds_hub_health_add_item($feeds[$key], $timestamp, $rss_limit);
                }
            }
            foreach (array_unique($term_slugs['kingy_audience']) as $slug) {
                $key = 'audience:' . $slug;
                if (isset($feeds[$key])) {
                    kingy_ali_feeds_hub_health_add_item($feeds[$key], $timestamp, $rss_limit);
                }
            }
            foreach (array_unique($term_slugs['kingy_launch_type']) as $slug) {
                $key = 'type:' . $slug;
                if (isset($feeds[$key])) {
                    kingy_ali_feeds_hub_health_add_item($feeds[$key], $timestamp, $rss_limit);
                }
            }

            if (in_array('founder-submitted', $term_slugs['kingy_tool_attribute'], true)) {
                $verification = function_exists('kingy_ali_canonical_verification_status')
                    ? kingy_ali_canonical_verification_status(kingy_ali_get_meta($post_id, 'verification_status'))
                    : '';
                if ($verification === 'verified') {
                    kingy_ali_feeds_hub_health_add_item($feeds['founder-submitted'], $timestamp, $rss_limit);
                }
            }
        }
    }

    if (function_exists('kingy_ali_stack_public_query') && function_exists('kingy_ali_stack_is_public_event')) {
        $stack_query = kingy_ali_stack_public_query(array('per_page' => 100));
        foreach ((array) $stack_query->posts as $post) {
            $post_id = is_object($post) && isset($post->ID) ? absint($post->ID) : absint($post);
            if ($post_id && kingy_ali_stack_is_public_event($post_id)) {
                kingy_ali_feeds_hub_health_add_item(
                    $feeds['stack'],
                    (int) get_post_time('U', true, $post_id),
                    50
                );
            }
        }
    }

    foreach (kingy_ali_feeds_hub_pricing_access_events(50) as $event) {
        $timestamp = !empty($event['published']) ? strtotime($event['published']) : 0;
        kingy_ali_feeds_hub_health_add_item($feeds['pricing-access'], $timestamp, 50);
    }

    foreach ($feeds as &$feed) {
        kingy_ali_feeds_hub_health_finalize($feed, $checked_at);
    }
    unset($feed);

    $active_count = count(
        array_filter(
            $feeds,
            static function ($feed) {
                return !empty($feed['item_count']);
            }
        )
    );
    $manifest = array(
        'checked_at' => $checked_at,
        'active_count' => $active_count,
        'total_count' => count($feeds),
        'feeds' => $feeds,
    );
    $ttl = defined('MINUTE_IN_SECONDS') ? 5 * MINUTE_IN_SECONDS : 300;
    set_transient($cache_key, $manifest, $ttl);
    return $manifest;
}

function kingy_ali_feeds_hub_health_status_label($health) {
    $status = isset($health['status']) ? sanitize_key((string) $health['status']) : 'empty';
    if ($status === 'live') {
        return __('Live', 'kingy-ai-launch-intelligence');
    }
    if ($status === 'quiet') {
        return __('Quiet', 'kingy-ai-launch-intelligence');
    }
    return __('Empty', 'kingy-ai-launch-intelligence');
}

function kingy_ali_feeds_hub_health_text($health) {
    $count = isset($health['item_count']) ? absint($health['item_count']) : 0;
    $timestamp = isset($health['latest_timestamp']) ? absint($health['latest_timestamp']) : 0;
    if (!$count || !$timestamp) {
        return __('No qualifying items yet', 'kingy-ai-launch-intelligence');
    }
    $date = function_exists('wp_date')
        ? wp_date('M j, Y', $timestamp, wp_timezone())
        : date_i18n('M j, Y', $timestamp);
    $items = sprintf(
        _n('%d recent item', '%d recent items', $count, 'kingy-ai-launch-intelligence'),
        $count
    );
    return sprintf(
        /* translators: 1: latest item date, 2: item count. */
        __('Updated %1$s · %2$s', 'kingy-ai-launch-intelligence'),
        $date,
        $items
    );
}

function kingy_ali_feeds_hub_founder_verified_count() {
    $manifest = kingy_ali_feeds_hub_health_manifest();
    return isset($manifest['feeds']['founder-submitted']['total_count'])
        ? absint($manifest['feeds']['founder-submitted']['total_count'])
        : 0;
}

function kingy_ali_feeds_hub_enqueue_assets() {
    $version = defined('KINGY_ALI_VERSION') ? KINGY_ALI_VERSION : kingy_ali_feeds_hub_rewrite_schema_version();
    $base_url = defined('KINGY_ALI_PLUGIN_URL')
        ? KINGY_ALI_PLUGIN_URL
        : plugins_url('', dirname(__DIR__) . '/kingy-ai-launch-intelligence.php') . '/';
    $style_url = defined('KINGY_ALI_PLUGIN_URL')
        ? KINGY_ALI_PLUGIN_URL . 'assets/css/feeds-hub.css'
        : plugins_url('assets/css/feeds-hub.css', dirname(__DIR__) . '/kingy-ai-launch-intelligence.php');
    wp_enqueue_style('kingy-ali-feeds-hub', $style_url, array(), $version);
    wp_enqueue_script(
        'kingy-ali-feeds-hub',
        $base_url . 'assets/js/feeds-hub.js',
        array(),
        $version,
        true
    );
}

function kingy_ali_feeds_hub_render_status($health) {
    $status = isset($health['status']) ? sanitize_key((string) $health['status']) : 'empty';
    if (!in_array($status, array('live', 'quiet', 'empty'), true)) {
        $status = 'empty';
    }
    ?>
    <span class="kingy-feeds__status kingy-feeds__status--<?php echo esc_attr($status); ?>">
        <?php echo esc_html(kingy_ali_feeds_hub_health_status_label($health)); ?>
    </span>
    <?php
}

function kingy_ali_feeds_hub_render_health($health) {
    ?>
    <p class="kingy-feeds__health"><?php echo esc_html(kingy_ali_feeds_hub_health_text($health)); ?></p>
    <?php
}

function kingy_ali_feeds_hub_render_copy_button($value, $label, $button_text = '') {
    $value = (string) $value;
    if ($value === '') {
        return;
    }
    $button_text = $button_text !== '' ? (string) $button_text : __('Copy URL', 'kingy-ai-launch-intelligence');
    ?>
    <button
        class="kingy-feeds__copy"
        type="button"
        data-kingy-copy="<?php echo esc_attr($value); ?>"
        data-kingy-copy-label="<?php echo esc_attr($label); ?>"
    ><?php echo esc_html($button_text); ?></button>
    <?php
}

function kingy_ali_feeds_hub_render_actions($url, $copy_value = '', $copy_label = '') {
    $url = esc_url_raw((string) $url, array('http', 'https'));
    $copy_value = $copy_value !== '' ? (string) $copy_value : $url;
    $copy_label = $copy_label !== '' ? (string) $copy_label : __('feed URL', 'kingy-ai-launch-intelligence');
    ?>
    <div class="kingy-feeds__actions">
        <?php kingy_ali_feeds_hub_render_copy_button($copy_value, $copy_label); ?>
        <?php if ($url !== '') : ?>
            <a class="kingy-feeds__open" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Open', 'kingy-ai-launch-intelligence'); ?></a>
        <?php endif; ?>
    </div>
    <?php
}

function kingy_ali_feeds_hub_render_feed_card($health, $title, $description, $path, $url) {
    ?>
    <article class="kingy-feeds__card">
        <?php kingy_ali_feeds_hub_render_status($health); ?>
        <h3><?php echo esc_html($title); ?></h3>
        <p><?php echo esc_html($description); ?></p>
        <?php kingy_ali_feeds_hub_render_health($health); ?>
        <code><?php echo esc_html($path); ?></code>
        <?php kingy_ali_feeds_hub_render_actions($url, $url, sprintf(__('%s feed URL', 'kingy-ai-launch-intelligence'), $title)); ?>
    </article>
    <?php
}

function kingy_ali_feeds_hub_render_term_links($kind, $terms, $manifest) {
    $taxonomies = array(
        'category' => 'kingy_launch_category',
        'audience' => 'kingy_audience',
        'type' => 'kingy_launch_type',
    );
    $taxonomy = isset($taxonomies[$kind]) ? $taxonomies[$kind] : '';
    if ($taxonomy === '') {
        return;
    }
    foreach ((array) $terms as $label => $slug) {
        if (!kingy_ali_feeds_hub_term_is_valid($taxonomy, $slug)) {
            continue;
        }
        $key = $kind . ':' . $slug;
        $health = isset($manifest['feeds'][$key]) ? $manifest['feeds'][$key] : array();
        $url = kingy_ali_feeds_hub_public_url($kind, $slug);
        ?>
        <li>
            <div class="kingy-feeds__link-copy">
                <div class="kingy-feeds__link-title">
                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                    <?php kingy_ali_feeds_hub_render_status($health); ?>
                </div>
                <?php kingy_ali_feeds_hub_render_health($health); ?>
                <code>/feeds/<?php echo esc_html($kind . '/' . $slug); ?>/</code>
            </div>
            <?php kingy_ali_feeds_hub_render_actions($url, $url, sprintf(__('%s feed URL', 'kingy-ai-launch-intelligence'), $label)); ?>
        </li>
        <?php
    }
}

function kingy_ali_shortcode_feeds_hub() {
    kingy_ali_feeds_hub_enqueue_assets();
    $manifest = kingy_ali_feeds_hub_health_manifest();
    $feeds = isset($manifest['feeds']) && is_array($manifest['feeds']) ? $manifest['feeds'] : array();
    $launch_health = isset($feeds['launches']) ? $feeds['launches'] : array();
    $stack_health = isset($feeds['stack']) ? $feeds['stack'] : array();
    $pricing_health = isset($feeds['pricing-access']) ? $feeds['pricing-access'] : array();
    $founder_health = isset($feeds['founder-submitted']) ? $feeds['founder-submitted'] : array();
    $agencies_health = isset($feeds['audience:agencies']) ? $feeds['audience:agencies'] : array();
    $launch_url = kingy_ali_feeds_hub_public_url('launches');
    $stack_url = kingy_ali_feeds_hub_stack_feed_url();
    $pricing_url = kingy_ali_feeds_hub_public_url('pricing-access');
    $founder_url = kingy_ali_feeds_hub_public_url('founder-submitted');
    $agencies_url = kingy_ali_feeds_hub_public_url('audience', 'agencies');
    $widget_code = '<script async src="' . esc_url(home_url('/feeds/widget.js')) . '"></script>';
    $checked_at = !empty($manifest['checked_at']) ? absint($manifest['checked_at']) : time();
    $checked_label = function_exists('wp_date')
        ? wp_date('M j, Y g:i a', $checked_at, wp_timezone())
        : date_i18n('M j, Y g:i a', $checked_at);
    ob_start();
    ?>
    <div class="kingy-feeds" id="kingy-feeds">
        <p class="kingy-feeds__copy-status" data-kingy-copy-status aria-live="polite"></p>
        <section class="kingy-feeds__hero">
            <p class="kingy-feeds__kicker"><?php esc_html_e('Kingy data feeds', 'kingy-ai-launch-intelligence'); ?></p>
            <h2><?php esc_html_e('Put source-backed AI reporting and launch intelligence where your readers and teams already work', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Copy a summary-only RSS URL into your feed reader, newsletter workflow, community bot, or internal tool. Every item links back to its canonical Kingy page.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-feeds__notice">
                <strong><?php esc_html_e('Live health:', 'kingy-ai-launch-intelligence'); ?></strong>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: 1: feeds containing items, 2: advertised feed count, 3: health check time. */
                        __('%1$d of %2$d advertised RSS feeds currently contain items. Checked %3$s; responses may be cached for up to 5 minutes.', 'kingy-ai-launch-intelligence'),
                        isset($manifest['active_count']) ? absint($manifest['active_count']) : 0,
                        isset($manifest['total_count']) ? absint($manifest['total_count']) : 0,
                        $checked_label
                    )
                );
                ?>
            </div>
            <div class="kingy-feeds__hero-actions">
                <?php kingy_ali_feeds_hub_render_copy_button($launch_url, __('all-launches feed URL', 'kingy-ai-launch-intelligence'), __('Copy all-launches URL', 'kingy-ai-launch-intelligence')); ?>
                <a href="#kingy-filtered-feeds"><?php esc_html_e('Browse filtered feeds', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>

        <section class="kingy-feeds__section" aria-labelledby="kingy-core-feeds">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Start here', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-core-feeds"><?php esc_html_e('Recommended starting points', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Choose the broad launch feed, track operational changes, or add the weekly widget.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-feeds__cards kingy-feeds__cards--primary">
                <article class="kingy-feeds__card">
                    <?php kingy_ali_feeds_hub_render_status($launch_health); ?>
                    <h3><?php esc_html_e('All AI launches', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('New Kingy records ordered by publication time, with a bounded summary and canonical profile link.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php kingy_ali_feeds_hub_render_health($launch_health); ?>
                    <code>/feeds/launches/</code>
                    <?php kingy_ali_feeds_hub_render_actions($launch_url, $launch_url, __('all-launches feed URL', 'kingy-ai-launch-intelligence')); ?>
                </article>
                <article class="kingy-feeds__card">
                    <?php kingy_ali_feeds_hub_render_status($stack_health); ?>
                    <h3><?php esc_html_e('AI Stack Change Radar', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Reviewed AI vendor releases, retirements, breaking changes, deadlines, pricing updates, and access changes.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php kingy_ali_feeds_hub_render_health($stack_health); ?>
                    <code>/feed/kingy-ai-stack-changes/</code>
                    <?php kingy_ali_feeds_hub_render_actions($stack_url, $stack_url, __('Stack Change Radar feed URL', 'kingy-ai-launch-intelligence')); ?>
                </article>
                <article class="kingy-feeds__card">
                    <span class="kingy-feeds__status kingy-feeds__status--live"><?php esc_html_e('Live preview', 'kingy-ai-launch-intelligence'); ?></span>
                    <h3><?php esc_html_e('This Week in AI widget', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Embed up to five fresh, verified launch records with one script tag and visible Kingy attribution.', 'kingy-ai-launch-intelligence'); ?></p>
                    <p class="kingy-feeds__health"><?php esc_html_e('Preview and installation details below', 'kingy-ai-launch-intelligence'); ?></p>
                    <code>&lt;script src="/feeds/widget.js"&gt;</code>
                    <div class="kingy-feeds__actions">
                        <?php kingy_ali_feeds_hub_render_copy_button($widget_code, __('widget embed code', 'kingy-ai-launch-intelligence'), __('Copy embed code', 'kingy-ai-launch-intelligence')); ?>
                        <a class="kingy-feeds__open" href="#kingy-widget-title"><?php esc_html_e('Preview', 'kingy-ai-launch-intelligence'); ?></a>
                    </div>
                </article>
            </div>
        </section>

        <section class="kingy-feeds__section" aria-labelledby="kingy-editorial-feeds">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Follow the reporting', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-editorial-feeds"><?php esc_html_e('Kingy editorial feeds', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Subscribe to breaking AI reporting or slower evidence-led analysis without mixing those formats into the structured launch feeds.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-feeds__cards kingy-feeds__cards--editorial">
                <?php foreach (kingy_ali_feeds_hub_editorial_feeds() as $label => $editorial) : ?>
                    <?php
                    $slug = sanitize_title((string) $editorial['slug']);
                    $key = 'editorial:' . $slug;
                    $health = isset($feeds[$key]) ? $feeds[$key] : array();
                    $url = kingy_ali_feeds_hub_public_url('editorial', $slug);
                    kingy_ali_feeds_hub_render_feed_card(
                        $health,
                        $label,
                        $editorial['description'],
                        '/category/' . $slug . '/feed/',
                        $url
                    );
                    ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="kingy-feeds__section" aria-labelledby="kingy-launch-type-feeds">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Choose the change', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-launch-type-feeds"><?php esc_html_e('Launches by type', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('Separate genuinely new products, consequential updates, and open-source releases without relying on broad topic labels.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-feeds__cards kingy-feeds__cards--launch-types">
                <?php
                $type_descriptions = array(
                    'new-product' => __('New AI products entering the market, ordered by Kingy publication time.', 'kingy-ai-launch-intelligence'),
                    'major-update' => __('Material product, model, platform, and capability updates to existing AI products.', 'kingy-ai-launch-intelligence'),
                    'open-source-release' => __('Launch records explicitly classified as open-source releases.', 'kingy-ai-launch-intelligence'),
                );
                foreach (kingy_ali_feeds_hub_launch_types() as $label => $slug) {
                    $key = 'type:' . $slug;
                    $health = isset($feeds[$key]) ? $feeds[$key] : array();
                    $url = kingy_ali_feeds_hub_public_url('type', $slug);
                    kingy_ali_feeds_hub_render_feed_card(
                        $health,
                        $label,
                        isset($type_descriptions[$slug]) ? $type_descriptions[$slug] : '',
                        '/feeds/type/' . $slug . '/',
                        $url
                    );
                }
                ?>
            </div>
        </section>

        <section class="kingy-feeds__section kingy-feeds__conditional" aria-labelledby="kingy-specialized-feeds">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Focused and conditional', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-specialized-feeds"><?php esc_html_e('Specialized feeds', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('These routes serve narrower use cases. Empty and quiet states stay visible instead of falling back to unrelated items.', 'kingy-ai-launch-intelligence'); ?></p>
            </div>
            <div class="kingy-feeds__cards kingy-feeds__cards--conditional">
                <article class="kingy-feeds__card">
                    <?php kingy_ali_feeds_hub_render_status($pricing_health); ?>
                    <h3><?php esc_html_e('AI pricing and access changes', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Reviewed Stack Change Radar events explicitly classified as price, usage-limit, or access changes.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php kingy_ali_feeds_hub_render_health($pricing_health); ?>
                    <code>/feeds/pricing-access/</code>
                    <?php kingy_ali_feeds_hub_render_actions($pricing_url, $pricing_url, __('pricing and access feed URL', 'kingy-ai-launch-intelligence')); ?>
                </article>
                <article class="kingy-feeds__card">
                    <?php kingy_ali_feeds_hub_render_status($founder_health); ?>
                    <h3><?php esc_html_e('Founder-submitted launches', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Founder-submitted records appear only after editorial verification; pending submissions never enter the feed.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php kingy_ali_feeds_hub_render_health($founder_health); ?>
                    <code>/feeds/founder-submitted/</code>
                    <?php kingy_ali_feeds_hub_render_actions($founder_url, $founder_url, __('founder-submitted feed URL', 'kingy-ai-launch-intelligence')); ?>
                </article>
                <article class="kingy-feeds__card">
                    <?php kingy_ali_feeds_hub_render_status($agencies_health); ?>
                    <h3><?php esc_html_e('Agencies', 'kingy-ai-launch-intelligence'); ?></h3>
                    <p><?php esc_html_e('Launch records tagged for agency teams. This route remains valid while coverage is quiet.', 'kingy-ai-launch-intelligence'); ?></p>
                    <?php kingy_ali_feeds_hub_render_health($agencies_health); ?>
                    <code>/feeds/audience/agencies/</code>
                    <?php kingy_ali_feeds_hub_render_actions($agencies_url, $agencies_url, __('Agencies feed URL', 'kingy-ai-launch-intelligence')); ?>
                </article>
            </div>
        </section>

        <section class="kingy-feeds__section kingy-feeds__split" aria-labelledby="kingy-filtered-feeds">
            <div>
                <div class="kingy-feeds__heading">
                    <p class="kingy-feeds__kicker"><?php esc_html_e('Choose a beat', 'kingy-ai-launch-intelligence'); ?></p>
                    <h2 id="kingy-filtered-feeds"><?php esc_html_e('Launches by category', 'kingy-ai-launch-intelligence'); ?></h2>
                </div>
                <ul class="kingy-feeds__links">
                    <?php kingy_ali_feeds_hub_render_term_links('category', kingy_ali_feeds_hub_categories(), $manifest); ?>
                </ul>
            </div>
            <div>
                <div class="kingy-feeds__heading">
                    <p class="kingy-feeds__kicker"><?php esc_html_e('Choose readers', 'kingy-ai-launch-intelligence'); ?></p>
                    <h2><?php esc_html_e('Launches by audience', 'kingy-ai-launch-intelligence'); ?></h2>
                </div>
                <ul class="kingy-feeds__links">
                    <?php kingy_ali_feeds_hub_render_term_links('audience', kingy_ali_feeds_hub_audiences(), $manifest); ?>
                </ul>
            </div>
        </section>

        <section class="kingy-feeds__section kingy-feeds__widget" aria-labelledby="kingy-widget-title">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Embed', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-widget-title"><?php esc_html_e('This Week in AI', 'kingy-ai-launch-intelligence'); ?></h2>
                <p><?php esc_html_e('A compact, attributed iframe containing up to five fresh, verified launch records from the last seven days. Paste one line where your site accepts HTML.', 'kingy-ai-launch-intelligence'); ?></p>
                <p><a class="kingy-feeds__widget-link" href="<?php echo esc_url(kingy_ali_feeds_hub_public_url('widget')); ?>"><?php esc_html_e('Open the standalone widget', 'kingy-ai-launch-intelligence'); ?></a></p>
            </div>
            <div class="kingy-feeds__code-row">
                <pre><code><?php echo esc_html($widget_code); ?></code></pre>
                <?php kingy_ali_feeds_hub_render_copy_button($widget_code, __('widget embed code', 'kingy-ai-launch-intelligence'), __('Copy embed code', 'kingy-ai-launch-intelligence')); ?>
            </div>
            <p class="kingy-feeds__fineprint"><?php esc_html_e('The loader is 100% wide up to 720px and 520px high by default. Set data-height between 280 and 1000, or data-title for a custom accessible iframe title.', 'kingy-ai-launch-intelligence'); ?></p>
            <iframe class="kingy-feeds__preview" src="<?php echo esc_url(kingy_ali_feeds_hub_public_url('widget')); ?>" title="<?php esc_attr_e('This Week in AI from Kingy', 'kingy-ai-launch-intelligence'); ?>" loading="lazy" height="520"></iframe>
        </section>

        <section class="kingy-feeds__section kingy-feeds__usage" aria-labelledby="kingy-use-attribution">
            <div class="kingy-feeds__heading">
                <p class="kingy-feeds__kicker"><?php esc_html_e('Redistribute responsibly', 'kingy-ai-launch-intelligence'); ?></p>
                <h2 id="kingy-use-attribution"><?php esc_html_e('Use and attribution', 'kingy-ai-launch-intelligence'); ?></h2>
            </div>
            <ul>
                <li><?php esc_html_e('Credit Kingy.ai and link every redistributed item to its canonical Kingy record.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Preserve the verification status and do not present a pending, partial, or outdated record as verified.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Keep the “Powered by Kingy.ai” credit visible when using the widget.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Poll no more often than once every 5 minutes.', 'kingy-ai-launch-intelligence'); ?></li>
                <li><?php esc_html_e('Do not mirror or resell full text. Redistribute only the bounded feed summaries and links, not Kingy article bodies.', 'kingy-ai-launch-intelligence'); ?></li>
            </ul>
        </section>

        <details class="kingy-feeds__section kingy-feeds__guardrails">
            <summary><?php esc_html_e('Roadmap and feed limits', 'kingy-ai-launch-intelligence'); ?></summary>
            <div class="kingy-feeds__guardrails-content">
                <ul>
                    <li><strong><?php esc_html_e('Rolling 30-day ICS calendar:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('deferred until feed freshness is stable and the date model is verified.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><strong><?php esc_html_e('Public API or MCP server:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('deferred until these feeds demonstrate real external subscribers or referral traffic.', 'kingy-ai-launch-intelligence'); ?></li>
                    <li><strong><?php esc_html_e('Full text:', 'kingy-ai-launch-intelligence'); ?></strong> <?php esc_html_e('not included. Feeds provide bounded summaries and canonical Kingy links to reduce scraping and cannibalization.', 'kingy-ai-launch-intelligence'); ?></li>
                </ul>
            </div>
        </details>

        <section class="kingy-feeds__section kingy-feeds__closing" aria-labelledby="kingy-feeds-closing-title">
            <p class="kingy-feeds__kicker"><?php esc_html_e('Ready to subscribe', 'kingy-ai-launch-intelligence'); ?></p>
            <h2 id="kingy-feeds-closing-title"><?php esc_html_e('Start with every verified launch', 'kingy-ai-launch-intelligence'); ?></h2>
            <p><?php esc_html_e('Copy the all-launches URL into your feed reader or automation. You can switch to a narrower category or audience feed at any time.', 'kingy-ai-launch-intelligence'); ?></p>
            <div class="kingy-feeds__hero-actions">
                <?php kingy_ali_feeds_hub_render_copy_button($launch_url, __('all-launches feed URL', 'kingy-ai-launch-intelligence'), __('Copy all-launches URL', 'kingy-ai-launch-intelligence')); ?>
                <a href="<?php echo esc_url($launch_url); ?>"><?php esc_html_e('Open feed', 'kingy-ai-launch-intelligence'); ?></a>
            </div>
        </section>
    </div>
    <?php
    return ob_get_clean();
}
