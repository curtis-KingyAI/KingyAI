<?php
/**
 * Plugin Name: Kingy AI Launch Intelligence
 * Description: Structured AI launch database, searchable launch hub, founder submissions, scoring, analytics, and import tools for Kingy AI.
 * Version: 0.1.263
 * Author: Kingy AI
 * Text Domain: kingy-ai-launch-intelligence
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KINGY_ALI_VERSION', '0.1.263');
if (!defined('KINGY_ALI_ENABLE_REPAIRED_STRATEGIC_ROUTES')) {
    define('KINGY_ALI_ENABLE_REPAIRED_STRATEGIC_ROUTES', true);
}
if (!defined('KINGY_ALI_PLUGIN_FILE')) {
    define('KINGY_ALI_PLUGIN_FILE', __FILE__);
}
if (!defined('KINGY_ALI_PLUGIN_DIR')) {
    define('KINGY_ALI_PLUGIN_DIR', plugin_dir_path(KINGY_ALI_PLUGIN_FILE));
}
if (!defined('KINGY_ALI_PLUGIN_URL')) {
    define('KINGY_ALI_PLUGIN_URL', plugin_dir_url(KINGY_ALI_PLUGIN_FILE));
}

require_once KINGY_ALI_PLUGIN_DIR . 'includes/post-types.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/taxonomies.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/meta-fields.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/attributes.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/scoring.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/analytics.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/companies.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/tools.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/public-trust.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/search.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/directories.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/models.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/shortcodes.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/launches-of-week.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/launch-scorecard.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/microsoft-copilot-course.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/ai-launch-academy.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/codex-zero-to-hero-fix.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/custom-html-safety.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/website-qa-checklist.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/seo-qa-checklist.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/security-review-checklist.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/agent-skills-worksheet.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/submissions.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/schema.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/campaign-breakdowns.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/setup-pages.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/article-generator.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/editorial-queues.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/maintenance.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/admin-columns.php';
require_once KINGY_ALI_PLUGIN_DIR . 'includes/admin-exporter.php';
if (!function_exists('kingy_ali_product_graph_reports_dir')) {
    require_once KINGY_ALI_PLUGIN_DIR . 'includes/product-graph-review.php';
    $kingy_ali_product_graph_overlay_file = KINGY_ALI_PLUGIN_DIR . 'includes/product-graph-review-overlay.php';
    if (file_exists($kingy_ali_product_graph_overlay_file)) {
        require_once $kingy_ali_product_graph_overlay_file;
    }
}
require_once KINGY_ALI_PLUGIN_DIR . 'includes/admin-importer.php';

if (!defined('KINGY_ALI_EMBEDDED') || !KINGY_ALI_EMBEDDED) {
    register_activation_hook(KINGY_ALI_PLUGIN_FILE, 'kingy_ali_activate');
    register_deactivation_hook(KINGY_ALI_PLUGIN_FILE, 'kingy_ali_deactivate');
}

function kingy_ali_activate() {
    kingy_ali_register_post_types();
    kingy_ali_register_taxonomies();
    kingy_ali_run_install_tasks(true);
    flush_rewrite_rules();
}

function kingy_ali_run_install_tasks($install_pages = false) {
    kingy_ali_seed_default_terms();
    kingy_ali_seed_company_directory_profiles();
    kingy_ali_backfill_company_profile_evidence();
    kingy_ali_create_analytics_table();
    if ($install_pages) {
        kingy_ali_install_recommended_pages(false);
        update_option('kingy_ali_pages_checked_version', KINGY_ALI_VERSION, false);
    } else {
        if (function_exists('kingy_ali_ensure_seo_health_pages')) {
            kingy_ali_ensure_seo_health_pages();
        }
        if (function_exists('kingy_ali_ensure_ai_launch_scorecard_page')) {
            kingy_ali_ensure_ai_launch_scorecard_page();
        }
        if (function_exists('kingy_ali_ensure_model_intelligence_pages')) {
            kingy_ali_ensure_model_intelligence_pages();
        }
    }
    update_option('kingy_ali_installed_version', KINGY_ALI_VERSION, false);
}

function kingy_ali_deactivate() {
    flush_rewrite_rules();
}

add_action('init', 'kingy_ali_maybe_run_activation_free_upgrade', 20);
function kingy_ali_maybe_run_activation_free_upgrade() {
    $installed_version = get_option('kingy_ali_installed_version', '');
    if ($installed_version === KINGY_ALI_VERSION) {
        return;
    }

    kingy_ali_run_install_tasks(false);

    if (!get_option('kingy_ali_flush_rewrite_rules_deferred', false)) {
        update_option('kingy_ali_flush_rewrite_rules_deferred', '1', false);
    }
}

add_action('admin_init', 'kingy_ali_maybe_check_managed_pages_without_activation');
function kingy_ali_maybe_check_managed_pages_without_activation() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->base, array('post', 'post-new'), true)) {
        return;
    }

    $checked_version = get_option('kingy_ali_pages_checked_version', '');
    if ($checked_version === KINGY_ALI_VERSION) {
        return;
    }

    kingy_ali_install_recommended_pages(true);
    update_option('kingy_ali_pages_checked_version', KINGY_ALI_VERSION, false);
}

add_action('admin_init', 'kingy_ali_maybe_flush_rewrite_rules_deferred', 100);
function kingy_ali_maybe_flush_rewrite_rules_deferred() {
    if (!get_option('kingy_ali_flush_rewrite_rules_deferred', false)) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->base, array('post', 'post-new'), true)) {
        return;
    }

    flush_rewrite_rules(false);
    delete_option('kingy_ali_flush_rewrite_rules_deferred');
}

add_action('wp_enqueue_scripts', 'kingy_ali_register_assets');
add_action('wp_enqueue_scripts', 'kingy_ali_enqueue_public_page_assets', 20);
add_action('wp_head', 'kingy_ali_output_critical_layout_css', 0);
add_action('wp_head', 'kingy_ali_output_clients_tools_visual_system_css', PHP_INT_MAX);
add_action('template_redirect', 'kingy_ali_capture_singular_admin_bar_edit_id', 1);
add_action('admin_bar_menu', 'kingy_ali_restore_singular_admin_bar_edit_link', PHP_INT_MAX);
add_action('wp_before_admin_bar_render', 'kingy_ali_restore_singular_admin_bar_edit_link_before_render', PHP_INT_MAX);
add_action('admin_enqueue_scripts', 'kingy_ali_register_admin_assets');
add_action('enqueue_block_editor_assets', 'kingy_ali_dequeue_conflicting_editor_assets', PHP_INT_MAX);
add_action('admin_print_scripts-post.php', 'kingy_ali_dequeue_conflicting_editor_assets', 0);
add_action('admin_print_scripts-post-new.php', 'kingy_ali_dequeue_conflicting_editor_assets', 0);
add_action('admin_print_footer_scripts-post.php', 'kingy_ali_dequeue_conflicting_editor_assets', 0);
add_action('admin_print_footer_scripts-post-new.php', 'kingy_ali_dequeue_conflicting_editor_assets', 0);
add_filter('use_block_editor_for_post_type', 'kingy_ali_use_classic_editor_for_stable_post_creation', 20, 2);
add_filter('use_block_editor_for_post_type', 'kingy_ali_restore_block_editor_for_core_post_types', PHP_INT_MAX, 2);
add_filter('use_block_editor_for_post', 'kingy_ali_restore_block_editor_for_core_posts', PHP_INT_MAX, 2);
add_filter('use_block_editor_for_post', 'kingy_ali_use_classic_editor_for_agent_use_cases_page', PHP_INT_MAX, 2);

/* kingy-ali-launch-collection-cache-purge-20260625 */
add_action('save_post_kingy_ai_launch', 'kingy_ali_maybe_purge_launch_collection_caches_for_post', 50, 3);
add_action('deleted_post', 'kingy_ali_maybe_purge_launch_collection_caches_for_deleted_post', 50, 2);
add_action('rest_api_init', 'kingy_ali_register_launch_collection_cache_purge_route');

function kingy_ali_maybe_purge_launch_collection_caches_for_post($post_id, $post, $update) {
    unset($update);

    $post_id = absint($post_id);
    if (!$post_id || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!is_object($post) || !isset($post->post_type) || $post->post_type !== 'kingy_ai_launch') {
        return;
    }

    kingy_ali_purge_launch_collection_caches($post_id);
}

function kingy_ali_maybe_purge_launch_collection_caches_for_deleted_post($post_id, $post) {
    $post_id = absint($post_id);
    if (!$post_id || !is_object($post) || !isset($post->post_type) || $post->post_type !== 'kingy_ai_launch') {
        return;
    }

    kingy_ali_purge_launch_collection_caches($post_id);
}

function kingy_ali_register_launch_collection_cache_purge_route() {
    register_rest_route(
        'kingy-ali/v1',
        '/purge-launch-collections',
        array(
            'methods' => 'POST',
            'callback' => 'kingy_ali_rest_purge_launch_collection_caches',
            'permission_callback' => function () {
                return current_user_can('publish_posts') || current_user_can('manage_options');
            },
        )
    );
}

function kingy_ali_rest_purge_launch_collection_caches() {
    $result = kingy_ali_purge_launch_collection_caches(0);
    return rest_ensure_response($result);
}

function kingy_ali_launch_collection_cache_paths() {
    return array(
        '/',
        '/ai-launches/',
        '/ai-launches/today/',
        '/ai-launches/this-week/',
        '/ai-launches/launches-of-the-week/',
        '/ai-launches/ai-agents/',
        '/ai-launches/ai-video-tools/',
        '/ai-launches/ai-coding-tools/',
        '/ai-launches/ai-image-tools/',
        '/ai-launches/open-weight-models/',
        '/ai-launches/ai-search-and-research-tools/',
        '/ai-launches/ai-coding-agents-and-ides/',
        '/ai-launches/funding/',
    );
}

function kingy_ali_delete_launch_collection_transients() {
    global $wpdb;

    if (!isset($wpdb) || !is_object($wpdb) || empty($wpdb->options)) {
        return 0;
    }

    $deleted = 0;
    foreach (array(
        'kingy_ali_daily_radar_posts_v3_',
        'kingy_ali_latest_launches_of_week_edition_cta_v2',
        'kingy_ali_homepage_latest_launch_items_v3_',
    ) as $prefix) {
        $like = $wpdb->esc_like('_transient_' . $prefix) . '%';
        $timeout_like = $wpdb->esc_like('_transient_timeout_' . $prefix) . '%';
        $deleted += (int) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
        $deleted += (int) $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout_like));
    }

    return $deleted;
}

function kingy_ali_purge_launch_collection_caches($launch_post_id = 0) {
    $launch_post_id = absint($launch_post_id);
    $paths = kingy_ali_launch_collection_cache_paths();
    $urls = array();

    foreach ($paths as $path) {
        $url = home_url($path);
        $urls[] = $url;
        $page_id = url_to_postid($url);
        if ($page_id) {
            clean_post_cache($page_id);
            if (function_exists('wp_cache_post_change')) {
                wp_cache_post_change($page_id);
            }
        }
    }

    if ($launch_post_id) {
        clean_post_cache($launch_post_id);
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($launch_post_id);
        }
    }

    $transients_deleted = kingy_ali_delete_launch_collection_transients();

    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }

    if (function_exists('prune_super_cache') && isset($GLOBALS['cache_path']) && is_string($GLOBALS['cache_path']) && $GLOBALS['cache_path'] !== '') {
        prune_super_cache($GLOBALS['cache_path'], true);
    }

    foreach ($urls as $url) {
        do_action('litespeed_purge_url', $url);
        if (function_exists('w3tc_flush_url')) {
            w3tc_flush_url($url);
        }
    }

    if (function_exists('rocket_clean_files')) {
        rocket_clean_files($urls);
    }

    return array(
        'purged' => true,
        'launch_post_id' => $launch_post_id,
        'paths' => $paths,
        'transients_deleted' => $transients_deleted,
        'wp_super_cache_available' => function_exists('wp_cache_clear_cache') || function_exists('prune_super_cache'),
    );
}

function kingy_ali_capture_singular_admin_bar_edit_id() {
    if (is_admin()) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ($post_id <= 0 || !is_singular()) {
        $post_id = kingy_ali_current_request_post_id();
    }

    if ($post_id > 0) {
        $GLOBALS['kingy_ali_singular_admin_bar_edit_id'] = $post_id;
    }
}

function kingy_ali_current_request_post_id() {
    if (empty($_SERVER['REQUEST_URI'])) {
        return 0;
    }

    $request_uri = wp_unslash((string) $_SERVER['REQUEST_URI']);
    $request_path = parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($request_path) || $request_path === '') {
        return 0;
    }

    return (int) url_to_postid(home_url($request_path));
}

function kingy_ali_restore_singular_admin_bar_edit_link($wp_admin_bar) {
    if (
        is_admin()
        || !is_user_logged_in()
        || !is_singular()
        || !is_object($wp_admin_bar)
        || !method_exists($wp_admin_bar, 'add_node')
    ) {
        return;
    }

    $request_post_id = kingy_ali_current_request_post_id();
    $post_id = $request_post_id > 0
        ? $request_post_id
        : (isset($GLOBALS['kingy_ali_singular_admin_bar_edit_id'])
        ? (int) $GLOBALS['kingy_ali_singular_admin_bar_edit_id']
        : (int) get_queried_object_id());
    if ($post_id <= 0 || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    $post_type_object = get_post_type_object($post->post_type);
    if (!$post_type_object || !is_post_type_viewable($post_type_object)) {
        return;
    }

    if (method_exists($wp_admin_bar, 'remove_node')) {
        $wp_admin_bar->remove_node('edit');
    }

    $label = !empty($post_type_object->labels->edit_item)
        ? $post_type_object->labels->edit_item
        : __('Edit Post', 'kingy-ai-launch-intelligence');

    $wp_admin_bar->add_node(
        array(
            'id' => 'edit',
            'title' => $label,
            'href' => admin_url('post.php?post=' . $post_id . '&action=edit'),
        )
    );
}

function kingy_ali_restore_singular_admin_bar_edit_link_before_render() {
    global $wp_admin_bar;

    kingy_ali_restore_singular_admin_bar_edit_link($wp_admin_bar);
}

function kingy_ali_use_classic_editor_for_agent_use_cases_page($use_block_editor, $post) {
    if (!is_admin() || !is_object($post) || (int) $post->ID !== 15886) {
        return $use_block_editor;
    }

    return false;
}

function kingy_ali_register_assets() {
    $kingy_ali_launch_asset_version = KINGY_ALI_VERSION . '-funding-style-sync-20260626';

    wp_register_style(
        'kingy-ali-launch-intelligence',
        KINGY_ALI_PLUGIN_URL . 'assets/css/launch-intelligence.css',
        array(),
        $kingy_ali_launch_asset_version
    );

    wp_register_script(
        'kingy-ali-launch-filters',
        KINGY_ALI_PLUGIN_URL . 'assets/js/launch-filters.js',
        array(),
        $kingy_ali_launch_asset_version,
        true
    );

    wp_register_style(
        'kingy-ali-model-intelligence',
        KINGY_ALI_PLUGIN_URL . 'assets/css/model-intelligence.css',
        array('kingy-ali-launch-intelligence'),
        KINGY_ALI_VERSION
    );

    wp_register_script(
        'kingy-ali-model-intelligence',
        KINGY_ALI_PLUGIN_URL . 'assets/js/model-intelligence.js',
        array(),
        KINGY_ALI_VERSION,
        true
    );

    wp_localize_script(
        'kingy-ali-launch-filters',
        'KingyALI',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kingy_ali_track_click'),
            'visibilityNonce' => wp_create_nonce('kingy_ali_visibility_score'),
        )
    );
}

function kingy_ali_enqueue_assets() {
    wp_enqueue_style('kingy-ali-launch-intelligence');
    wp_enqueue_script('kingy-ali-launch-filters');
}

function kingy_ali_enqueue_model_assets() {
    kingy_ali_enqueue_assets();
    wp_enqueue_style('kingy-ali-model-intelligence');
    wp_enqueue_script('kingy-ali-model-intelligence');
}

function kingy_ali_output_clients_tools_visual_system_css() {
    if (!is_page(15869) && !is_page('clients')) {
        return;
    }

    $css_file = KINGY_ALI_PLUGIN_DIR . 'assets/css/launch-intelligence.css';
    if (!is_readable($css_file)) {
        return;
    }

    $css = file_get_contents($css_file);
    if (!is_string($css) || $css === '') {
        return;
    }

    $marker = '/* Clients page / AI Tools visual system bridge. */';
    $marker_position = strpos($css, $marker);
    if ($marker_position === false) {
        return;
    }

    echo "\n<style id=\"kingy-ali-clients-tools-visual-system-css\">\n";
    echo trim(substr($css, $marker_position));
    echo "\n</style>\n";
}

function kingy_ali_is_public_launch_intelligence_surface() {
    return function_exists('kingy_ali_current_launch_collection_meta')
        && (
            kingy_ali_current_launch_collection_meta()
            || kingy_ali_current_launch_action_page_meta()
            || kingy_ali_current_directory_archive_type()
            || kingy_ali_current_related_page_meta()
            || (
                is_singular('post')
                && function_exists('kingy_ali_radar_post_title_matches')
                && kingy_ali_radar_post_title_matches(get_queried_object_id())
            )
            || (function_exists('kingy_ali_is_model_intelligence_page') && kingy_ali_is_model_intelligence_page())
            || is_singular(array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'))
        );
}

function kingy_ali_enqueue_public_page_assets() {
    if (kingy_ali_is_public_launch_intelligence_surface()) {
        if (function_exists('kingy_ali_is_model_intelligence_page') && kingy_ali_is_model_intelligence_page()) {
            kingy_ali_enqueue_model_assets();
        } else {
            kingy_ali_enqueue_assets();
        }
    }
}

function kingy_ali_use_classic_editor_for_stable_post_creation($use_block_editor, $post_type) {
    if (defined('KINGY_ALI_FORCE_BLOCK_EDITOR') && KINGY_ALI_FORCE_BLOCK_EDITOR) {
        return $use_block_editor;
    }

    $classic_post_types = array();
    if (defined('KINGY_ALI_FORCE_CLASSIC_EDITOR') && KINGY_ALI_FORCE_CLASSIC_EDITOR) {
        $classic_post_types = array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model');
    }

    if (defined('KINGY_ALI_FORCE_CLASSIC_EDITOR_FOR_POSTS') && KINGY_ALI_FORCE_CLASSIC_EDITOR_FOR_POSTS) {
        $classic_post_types[] = 'post';
    }

    $classic_post_types = apply_filters('kingy_ali_classic_editor_post_types', array_unique($classic_post_types));

    if (is_admin() && in_array($post_type, $classic_post_types, true)) {
        return false;
    }

    return $use_block_editor;
}

function kingy_ali_restore_block_editor_for_core_post_types($use_block_editor, $post_type) {
    if (!is_admin() || !in_array($post_type, array('post', 'page'), true)) {
        return $use_block_editor;
    }

    return true;
}

function kingy_ali_restore_block_editor_for_core_posts($use_block_editor, $post) {
    $post_type = is_object($post) && isset($post->post_type) ? (string) $post->post_type : '';

    if (!is_admin() || !in_array($post_type, array('post', 'page'), true)) {
        return $use_block_editor;
    }

    return true;
}

function kingy_ali_output_critical_layout_css() {
    $is_launch_surface = kingy_ali_is_public_launch_intelligence_surface();
    $is_phase1_surface = function_exists('kingy_ali_is_phase1_public_quality_surface') && kingy_ali_is_phase1_public_quality_surface();

    if (!$is_launch_surface && !$is_phase1_surface) {
        return;
    }
    ?>
<style id="kingy-ali-critical-layout-css">
body.kingy-ali-phase1-quality-surface .jeg_search_result,body.kingy-ali-phase1-quality-surface .jeg_header_sticky,body.kingy-ali-phase1-quality-surface .jeg_footer_primary{display:none!important}
body.kingy-ali-launch-intelligence-page .entry-header{display:block!important;margin:0 0 clamp(20px,3vw,32px)}
body.kingy-ali-launch-intelligence-page,.kingy-ali-template,.kingy-ali-hub,.kingy-ali-single{color:#172026;font-family:Roboto,Helvetica,Arial,sans-serif}
body.kingy-ali-launch-intelligence-page .entry-header .jeg_post_title,.kingy-ali-page-title,.kingy-ali-hero h1,.kingy-ali-hero h2,.kingy-ali-single h1,.kingy-ali-coverage-intro h1,.kingy-ali-coverage-intro h2{color:#172026;font-size:clamp(2rem,4vw,3.8rem);letter-spacing:0;line-height:1.05;margin:0 0 14px}
body.kingy-ali-launch-intelligence-page .entry-header .jeg_meta_container{display:none}
body.page.kingy-ali-launch-intelligence-page .jeg_main .jeg_main_content>.entry-content>.content-inner{box-sizing:border-box;margin:0 auto;max-width:1240px;padding:clamp(20px,3vw,36px) clamp(20px,4vw,48px) clamp(48px,6vw,80px)}
body.page.kingy-ali-launch-intelligence-page .jeg_main .jeg_main_content>.entry-content>.content-inner>:first-child{margin-top:0}
body.page.kingy-ali-launch-intelligence-page .jeg_main .jeg_main_content>.entry-content>.content-inner>:last-child{margin-bottom:0}
body.single-kingy_ai_launch.kingy-ali-launch-intelligence-page .kingy-ali-template,body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-template{box-sizing:border-box;margin-inline:auto;max-width:1180px;padding:clamp(18px,3vw,34px) clamp(16px,4vw,32px) clamp(44px,6vw,72px);width:100%}
.kingy-ali-page-title{box-sizing:border-box;margin:clamp(20px,3vw,36px) 0 clamp(20px,3vw,32px)}.kingy-ali-page-title--injected{padding-left:calc(clamp(28px,4vw,44px) + clamp(22px,4vw,42px) + 1px);padding-right:calc(clamp(28px,4vw,44px) + clamp(22px,4vw,42px) + 1px)}
.kingy-ali-hero,.kingy-ali-single__header,.kingy-ali-coverage-intro{background:#f3f7f4;border-bottom:1px solid #dbe5de;padding:clamp(28px,5vw,64px)}
.kingy-ali-hero p,.kingy-ali-single__header p,.kingy-ali-coverage-intro p{font-size:1.08rem;line-height:1.6;margin:0;max-width:860px}
.kingy-ali-single__header{border:1px solid #dbe5de;border-radius:8px;margin-bottom:24px}.kingy-ali-single__header-inner{display:grid;gap:clamp(22px,4vw,44px);grid-template-columns:minmax(0,1fr) minmax(260px,360px)}.kingy-ali-single__actions{align-items:center;display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}.kingy-ali-single__hero-facts{align-self:stretch;background:#fff;border:1px solid #d7e2dc;border-radius:8px;box-shadow:0 10px 30px rgba(23,32,38,.06);padding:18px}.kingy-ali-single__hero-facts dl{display:grid;gap:12px;margin:0}.kingy-ali-single__hero-facts div{border-bottom:1px solid #edf2ef;display:grid;gap:4px;padding-bottom:12px}.kingy-ali-single__hero-facts div:last-child{border-bottom:0;padding-bottom:0}.kingy-ali-single__hero-facts dt{color:#607068;font-size:.82rem;font-weight:700;text-transform:uppercase}.kingy-ali-single__hero-facts dd{color:#172026;font-weight:700;line-height:1.35;margin:0}
body.single-kingy_ai_launch.kingy-ali-launch-intelligence-page .kingy-ali-single__header,body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-single__header{margin-bottom:clamp(24px,4vw,40px)}
body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single{align-items:start;display:grid;gap:clamp(18px,3vw,28px);grid-template-columns:minmax(0,1fr) minmax(280px,360px);max-width:100%}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>*{min-width:0}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>:is(.kingy-ali-single__header,.kingy-ali-content-band,.kingy-ali-content-grid,.kingy-ali-link-panel,.kingy-ali-launch-history,.kingy-ali-cta-row){grid-column:1/-1;margin-bottom:0;margin-top:0}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>.kingy-ali-facts{align-self:start;background:transparent;border:0;grid-column:1;margin:0;padding:0}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>.kingy-ali-trust-panel{align-self:start;gap:16px;grid-column:2;grid-template-columns:1fr;margin:0;padding:clamp(18px,2.5vw,24px)}
.kingy-ali-kicker{color:#2d6b58;font-size:.82rem;font-weight:700;letter-spacing:0;margin:0 0 10px;text-transform:uppercase}
.kingy-ali-facts{background:#fbfcfb;border:1px solid #dbe5de;display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin:24px 0;padding:18px}
.kingy-ali-facts div{align-items:center;display:flex;gap:12px;justify-content:space-between}
.kingy-ali-facts dt{color:#607068;font-weight:600}.kingy-ali-facts dd{margin:0;text-align:right}
body.single-kingy_ai_launch.kingy-ali-launch-intelligence-page .kingy-ali-snapshot{margin:0 0 clamp(24px,4vw,40px)}body.single-kingy_ai_launch.kingy-ali-launch-intelligence-page .kingy-ali-section-heading{margin-bottom:clamp(14px,2vw,22px)}
.kingy-ali-section-heading{margin-bottom:16px}.kingy-ali-section-heading h2{font-size:clamp(1.45rem,2.5vw,2rem);letter-spacing:0;line-height:1.18;margin:0}.kingy-ali-source-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}.kingy-ali-source-grid a{background:#fbfcfb;border:1px solid #dbe5de;border-radius:8px;color:#172026;display:grid;gap:6px;min-height:92px;padding:15px;text-decoration:none}.kingy-ali-source-grid span{color:#607068;font-size:.88rem;overflow-wrap:anywhere}
.kingy-ali-score-panel,.kingy-ali-link-panel,.kingy-ali-content-band,.kingy-ali-text-panel,.kingy-ali-trust-panel{background:#fff;border:1px solid #dde4e0;border-radius:8px;box-shadow:0 1px 2px rgba(15,23,42,.05);margin:24px 0;padding:22px}body.page.kingy-ali-launch-intelligence-page .kingy-ali-content-band,body.page.kingy-ali-launch-intelligence-page .kingy-ali-submit-form,body.page.kingy-ali-launch-intelligence-page .kingy-ali-calculator,body.page.kingy-ali-launch-intelligence-page .kingy-ali-empty{padding:clamp(28px,4vw,44px)}
body.kingy-ali-launch-intelligence-page :is(.kingy-ali-content-band,.kingy-ali-link-panel,.kingy-ali-text-panel,.kingy-ali-score-panel,.kingy-ali-trust-panel,.kingy-ali-empty)>:first-child{margin-top:0}body.kingy-ali-launch-intelligence-page :is(.kingy-ali-content-band,.kingy-ali-link-panel,.kingy-ali-text-panel,.kingy-ali-score-panel,.kingy-ali-trust-panel,.kingy-ali-empty)>:last-child{margin-bottom:0}body.kingy-ali-launch-intelligence-page :is(.kingy-ali-content-band,.kingy-ali-link-panel,.kingy-ali-text-panel,.kingy-ali-score-panel,.kingy-ali-trust-panel,.kingy-ali-empty)>:is(h2,h3){line-height:1.18;margin-bottom:clamp(12px,2vw,16px)}body.kingy-ali-launch-intelligence-page :is(.kingy-ali-content-band,.kingy-ali-link-panel,.kingy-ali-text-panel,.kingy-ali-score-panel,.kingy-ali-trust-panel,.kingy-ali-empty)>p{margin-bottom:clamp(12px,2vw,16px)}body.kingy-ali-launch-intelligence-page .kingy-ali-link-panel>.kingy-ali-link-list,body.kingy-ali-launch-intelligence-page .kingy-ali-section-heading+.kingy-ali-content-grid,body.kingy-ali-launch-intelligence-page .kingy-ali-section-heading+.kingy-ali-link-list,body.kingy-ali-launch-intelligence-page .kingy-ali-content-grid+.kingy-ali-link-list{margin-top:clamp(14px,2vw,18px)}
body.kingy-ali-launch-intelligence-page .kingy-ali-company-path,body.kingy-ali-launch-intelligence-page .kingy-ali-empty{box-sizing:border-box;max-width:100%;overflow:hidden;padding:clamp(28px,5vw,44px)}body.kingy-ali-launch-intelligence-page .kingy-ali-company-path .kingy-ali-section-heading,body.kingy-ali-launch-intelligence-page .kingy-ali-empty>p{max-width:920px}body.kingy-ali-launch-intelligence-page .kingy-ali-company-path .kingy-ali-cta-row,body.kingy-ali-launch-intelligence-page .kingy-ali-empty .kingy-ali-cta-row{align-items:stretch;gap:12px;margin-top:clamp(18px,3vw,28px);max-width:100%;width:100%}body.kingy-ali-launch-intelligence-page .kingy-ali-company-path .kingy-ali-cta-row a,body.kingy-ali-launch-intelligence-page .kingy-ali-empty .kingy-ali-cta-row a{align-items:center;box-sizing:border-box;flex:1 1 220px;font-size:clamp(.95rem,1.4vw,1rem);line-height:1.25;max-width:100%;min-height:46px;min-width:min(100%,220px);padding:11px 16px;text-align:center;white-space:normal}@media(max-width:540px){body.kingy-ali-launch-intelligence-page .kingy-ali-company-path .kingy-ali-cta-row a,body.kingy-ali-launch-intelligence-page .kingy-ali-empty .kingy-ali-cta-row a{flex-basis:100%}}
.kingy-ali-card__actions,.kingy-ali-cta-row,.kingy-ali-link-list{display:flex;flex-wrap:wrap;gap:10px}
.kingy-ali-card__actions,.kingy-ali-cta-row{align-items:center;margin-top:16px}
.kingy-ali-card__actions a,.kingy-ali-cta-row a,.kingy-ali-link-list a,.kingy-ali-single__actions a{background:#172026;border:1px solid #172026;border-radius:6px;color:#fff;display:inline-flex;font-weight:700;justify-content:center;padding:10px 14px;text-decoration:none}
.kingy-ali-card__actions a+a,.kingy-ali-link-list a,.kingy-ali-single__actions a+a{background:#fff;color:#172026}
body.kingy-ali-launch-intelligence-page ins.adsbygoogle,body.kingy-ali-launch-intelligence-page .ai-fallback-adsense,body.kingy-ali-launch-intelligence-page .code-block:has(ins.adsbygoogle),body.kingy-ali-launch-intelligence-page .code-block:has(.adsbygoogle),body.kingy-ali-launch-intelligence-page .code-block:has(.ai-fallback-adsense){display:block;min-height:280px}
@media(max-width:900px){body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single{grid-template-columns:1fr}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>:is(.kingy-ali-facts,.kingy-ali-trust-panel){grid-column:1/-1}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>.kingy-ali-trust-panel{grid-template-columns:minmax(0,1.2fr) minmax(240px,.8fr)}}
@media(max-width:720px){body.kingy-ali-launch-intelligence-page ins.adsbygoogle,body.kingy-ali-launch-intelligence-page .ai-fallback-adsense,body.kingy-ali-launch-intelligence-page .code-block:has(ins.adsbygoogle),body.kingy-ali-launch-intelligence-page .code-block:has(.adsbygoogle),body.kingy-ali-launch-intelligence-page .code-block:has(.ai-fallback-adsense){min-height:250px}.kingy-ali-single__header{padding:22px}.kingy-ali-single__header-inner{grid-template-columns:1fr}body.single-kingy_ai_tool.kingy-ali-launch-intelligence-page .kingy-ali-tool-single>.kingy-ali-trust-panel{grid-template-columns:1fr}.kingy-ali-score-list div,.kingy-ali-facts div{align-items:flex-start;flex-direction:column}.kingy-ali-score-list dd,.kingy-ali-facts dd{text-align:left}}
</style>
    <?php
}

function kingy_ali_register_admin_assets($hook_suffix) {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_plugin_admin = strpos((string) $hook_suffix, 'kingy-ali') !== false;
    $is_record_editor = $screen && isset($screen->post_type) && in_array($screen->post_type, array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model'), true);

    if (!$is_plugin_admin && !$is_record_editor) {
        return;
    }

    kingy_ali_register_assets();
    wp_register_script(
        'kingy-ali-admin',
        KINGY_ALI_PLUGIN_URL . 'assets/js/admin.js',
        array(),
        KINGY_ALI_VERSION,
        true
    );

    wp_enqueue_style('kingy-ali-launch-intelligence');
    wp_enqueue_script('kingy-ali-admin');
}

function kingy_ali_dequeue_conflicting_editor_assets() {
    if (defined('KINGY_ALI_DEQUEUE_CONFLICTING_EDITOR_ASSETS') && !KINGY_ALI_DEQUEUE_CONFLICTING_EDITOR_ASSETS) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->base, array('post', 'post-new'), true)) {
        return;
    }

    $post_type = isset($screen->post_type) ? (string) $screen->post_type : '';
    $target_post_types = array('kingy_ai_launch', 'kingy_ai_tool', 'kingy_ai_company', 'kingy_ai_model');
    if (defined('KINGY_ALI_DEQUEUE_CONFLICTING_EDITOR_ASSETS_FOR_POSTS') && KINGY_ALI_DEQUEUE_CONFLICTING_EDITOR_ASSETS_FOR_POSTS) {
        $target_post_types[] = 'post';
    }
    $target_post_types = apply_filters('kingy_ali_dequeue_conflicting_editor_assets_post_types', $target_post_types);
    if (!in_array($post_type, $target_post_types, true)) {
        return;
    }

    $handles = array(
        'jetpack-blocks-editor',
        'jetpack-blocks-assets-base-url',
        'jetpack-connection',
        'jetpack-external-media-editor',
        'jetpack-form-editor',
        'jetpack-promote-editor',
        'jetpack-script-data',
        'jetpack-social-editor',
        'jp-forms-ai-plugin',
        'jp-forms-blocks',
        'jp-paypal-payments-blocks',
        'jp-paypal-payments-ncps-blocks',
        'jp-tracks',
        'jp-tracks-functions',
        'jptracks',
        'videopress-add-resumable-upload-support',
        'videopress-video-editor-script',
        'wp-jp-i18n-loader',
    );

    $handles = array_unique($handles);

    foreach ($handles as $handle) {
        wp_dequeue_script($handle);
    }
}

add_filter('template_include', 'kingy_ali_template_include', 99);
function kingy_ali_template_include($template) {
    if (is_singular('kingy_ai_launch')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/single-ai-launch.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_singular('kingy_ai_tool')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/single-ai-tool.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_singular('kingy_ai_company')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/single-ai-company.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_singular('kingy_ai_model')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/single-ai-model.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_post_type_archive('kingy_ai_model')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/archive-ai-models.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_post_type_archive('kingy_ai_tool')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/archive-ai-tools.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_post_type_archive('kingy_ai_company')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/archive-ai-companies.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    if (is_post_type_archive('kingy_ai_launch') || is_tax('kingy_launch_category')) {
        $plugin_template = KINGY_ALI_PLUGIN_DIR . 'templates/archive-launch-hub.php';
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    return $template;
}
