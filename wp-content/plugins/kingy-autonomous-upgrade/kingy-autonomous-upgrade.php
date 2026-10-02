<?php
/**
 * Plugin Name: Kingy Autonomous Upgrade
 * Description: Guarded commercial workflow, device stack and operations extensions for the existing Kingy property.
 * Version: 0.2.0
 * Author: Kingy AI
 */
if (!defined('ABSPATH')) { exit; }
define('KAU_DIR', __DIR__ . '/');
define('KAU_VERSION', '0.2.0');
define('KAU_SCHEMA_VERSION', '2');
require_once KAU_DIR . 'includes/validation.php';
require_once KAU_DIR . 'includes/store.php';
require_once KAU_DIR . 'includes/jobs.php';
require_once KAU_DIR . 'includes/publishing.php';
require_once KAU_DIR . 'includes/pipelines.php';
require_once KAU_DIR . 'includes/rest.php';
require_once KAU_DIR . 'includes/views.php';
require_once KAU_DIR . 'includes/sponsor.php';

function kau_enabled($feature) {
    $flags = get_option('kau_feature_flags', array());
    return is_array($flags) && !empty($flags[$feature]) && get_option('kau_schema_version') === KAU_SCHEMA_VERSION;
}

// No activation-time migrations, page creation, cron registration or email sends.
add_action('rest_api_init', 'kau_register_routes');
add_action('admin_menu', 'kau_admin_menu');
add_shortcode('kingy_product_commercial', 'kau_workflow_view');
add_shortcode('kingy_my_stack', 'kau_stack_view');
add_shortcode('kingy_sponsor_upgrade', 'kau_sponsor_view');
add_filter('script_loader_tag', function ($tag, $handle) {
    return $handle === 'kau-app' ? str_replace('<script ', '<script type="module" ', $tag) : $tag;
}, 10, 2);

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('kingy-upgrade migrate', function ($args, $assoc) {
        if (empty($assoc['backup-restored'])) { WP_CLI::error('Verify a database backup restore first; supply --backup-restored.'); }
        kau_migrate(); WP_CLI::success('Additive schema installed. Features and delivery remain disabled.');
    });
    WP_CLI::add_command('kingy-upgrade tick', function ($args, $assoc) {
        $result = kau_tick(!empty($assoc['test']));
        WP_CLI::log(wp_json_encode($result));
    });
    WP_CLI::add_command('kingy-upgrade status', function () {
        WP_CLI::log(wp_json_encode(kau_operations_status()));
    });
}
