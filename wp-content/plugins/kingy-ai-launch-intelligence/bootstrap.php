<?php
/**
 * Include loader for sites that do not want a plugin activation step.
 *
 * Require this file from existing site code, such as an active theme's
 * functions.php. It loads the same package without registering activation or
 * deactivation hooks; one-time setup checks run on normal WordPress load.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('KINGY_ALI_EMBEDDED')) {
    define('KINGY_ALI_EMBEDDED', true);
}
if (!defined('KINGY_ALI_PLUGIN_FILE')) {
    define('KINGY_ALI_PLUGIN_FILE', __DIR__ . '/kingy-ai-launch-intelligence.php');
}
if (!defined('KINGY_ALI_PLUGIN_DIR')) {
    define('KINGY_ALI_PLUGIN_DIR', trailingslashit(__DIR__));
}

if (!defined('KINGY_ALI_PLUGIN_URL')) {
    $kingy_ali_dir = function_exists('wp_normalize_path') ? wp_normalize_path(__DIR__) : str_replace('\\', '/', __DIR__);

    if (defined('WP_CONTENT_DIR') && defined('WP_CONTENT_URL')) {
        $kingy_ali_content_dir = function_exists('wp_normalize_path') ? wp_normalize_path(WP_CONTENT_DIR) : str_replace('\\', '/', WP_CONTENT_DIR);
    }

    if (!empty($kingy_ali_content_dir) && strpos($kingy_ali_dir, $kingy_ali_content_dir) === 0) {
        $kingy_ali_relative_dir = ltrim(substr($kingy_ali_dir, strlen($kingy_ali_content_dir)), '/');
        define('KINGY_ALI_PLUGIN_URL', trailingslashit(content_url($kingy_ali_relative_dir)));
    } else {
        define('KINGY_ALI_PLUGIN_URL', plugin_dir_url(__FILE__));
    }
}

require_once KINGY_ALI_PLUGIN_FILE;
