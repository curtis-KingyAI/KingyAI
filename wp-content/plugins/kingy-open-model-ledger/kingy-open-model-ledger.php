<?php
/**
 * Plugin Name: Kingy Open Model Ledger
 * Description: Adds a sourced, revision-aware decision ledger to the existing Kingy AI Model Hub.
 * Version: 0.1.3
 * Author: Kingy.ai
 * License: GPL-2.0-or-later
 * Text Domain: kingy-open-model-ledger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'KOML_VERSION' ) ) {
	define( 'KOML_VERSION', '0.1.3' );
}

if ( ! defined( 'KOML_DIR' ) ) {
	define( 'KOML_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'KOML_URL' ) ) {
	define( 'KOML_URL', plugin_dir_url( __FILE__ ) );
}

require_once KOML_DIR . 'includes/class-koml-data.php';
require_once KOML_DIR . 'includes/class-koml-admin.php';
require_once KOML_DIR . 'includes/class-koml-rest.php';

/**
 * Start the plugin after all other plugins have loaded.
 *
 * The frontend class is optional so this core can be installed or tested on its
 * own while the public templates are developed independently.
 */
function koml_boot() {
	KOML_Data::boot();
	KOML_Admin::boot();
	KOML_REST::boot();

	$frontend_file = KOML_DIR . 'includes/class-koml-frontend.php';
	if ( file_exists( $frontend_file ) ) {
		require_once $frontend_file;
		if ( class_exists( 'KOML_Frontend' ) && is_callable( array( 'KOML_Frontend', 'boot' ) ) ) {
			KOML_Frontend::boot();
		}
	}
}
add_action( 'plugins_loaded', 'koml_boot' );

/**
 * Create the calculator landing page safely.
 *
 * Existing pages are never overwritten or republished. A missing page is
 * created as a draft so an editor can review the calculator before launch.
 */
function koml_activate() {
	$frontend_file = KOML_DIR . 'includes/class-koml-frontend.php';
	if ( ! class_exists( 'KOML_Frontend' ) && file_exists( $frontend_file ) ) {
		require_once $frontend_file;
	}
	if ( class_exists( 'KOML_Frontend' ) ) {
		KOML_Frontend::register_rewrite_rules();
	}
	flush_rewrite_rules();
	update_option( 'koml_rewrite_schema', '2026-07-30-v1', false );

	$model_fit_page = get_page_by_path( 'model-fit', OBJECT, 'page' );

	if ( ! $model_fit_page ) {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => __( 'Model Fit', 'kingy-open-model-ledger' ),
				'post_name'    => 'model-fit',
				'post_content' => '[kingy_model_fit]',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_koml_model_fit_page', '1' );
		}
	}
}
register_activation_hook( __FILE__, 'koml_activate' );
