<?php
/**
 * Read-only public REST API for published ledger records.
 *
 * @package Kingy_Open_Model_Ledger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KOML_REST {
	const REST_NAMESPACE = 'kingy-open-model-ledger/v1';

	/**
	 * Register hooks.
	 */
	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register collection and item GET routes. No write routes are exposed.
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/models',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_models' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page' => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) { return is_numeric( $value ) && (int) $value >= 1; },
					),
					'per_page' => array(
						'default'           => 20,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) { return is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 100; },
					),
					'search' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'rights_profile' => array( 'sanitize_callback' => 'sanitize_key' ),
					'lifecycle_status' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/models/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_model' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) { return is_numeric( $value ) && (int) $value > 0; },
					),
				),
			)
		);
	}

	/**
	 * Return published models with pagination headers.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_models( $request ) {
		$args = array(
			'post_type'              => KOML_Data::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => (int) $request->get_param( 'per_page' ),
			'paged'                  => (int) $request->get_param( 'page' ),
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'no_found_rows'          => false,
			'update_post_term_cache' => false,
		);

		$search = $request->get_param( 'search' );
		if ( $search ) {
			$args['s'] = $search;
		}

		$meta_query = array();
		$meta_query[] = array(
			'key'     => '_koml_scope_status',
			'value'   => 'curated',
			'compare' => '=',
		);
		foreach ( array( 'rights_profile', 'lifecycle_status' ) as $filter ) {
			$value = $request->get_param( $filter );
			if ( $value ) {
				$meta_query[] = array(
					'key'     => KOML_Data::meta_key( $filter ),
					'value'   => sanitize_key( $value ),
					'compare' => '=',
				);
			}
		}
		$args['meta_query'] = $meta_query;

		$query = new WP_Query( $args );
		$models = array();
		foreach ( $query->posts as $post ) {
			$models[] = self::prepare_model( $post );
		}

		$response = rest_ensure_response( $models );
		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );
		return $response;
	}

	/**
	 * Return one published model record.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_model( $request ) {
		$post = get_post( (int) $request->get_param( 'id' ) );
		if (
			! $post ||
			KOML_Data::POST_TYPE !== $post->post_type ||
			'publish' !== $post->post_status ||
			'curated' !== get_post_meta( $post->ID, '_koml_scope_status', true )
		) {
			return new WP_Error(
				'koml_model_not_found',
				__( 'Published model record not found.', 'kingy-open-model-ledger' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( self::prepare_model( $post ) );
	}

	/**
	 * Shape a stable REST representation.
	 *
	 * @param WP_Post $post Model post.
	 * @return array
	 */
	private static function prepare_model( $post ) {
		$ledger = KOML_Data::get_ledger( $post->ID, true );
		if ( ! empty( $ledger['change_log'] ) && is_array( $ledger['change_log'] ) ) {
			foreach ( $ledger['change_log'] as &$event ) {
				if ( is_array( $event ) ) {
					unset( $event['actor_id'] );
				}
			}
			unset( $event );
		}
		return array(
			'id'           => (int) $post->ID,
			'slug'         => $post->post_name,
			'title'        => get_the_title( $post ),
			'url'          => get_permalink( $post ),
			'modified_gmt' => get_post_modified_time( 'c', true, $post ),
			'ledger'       => $ledger,
		);
	}
}
