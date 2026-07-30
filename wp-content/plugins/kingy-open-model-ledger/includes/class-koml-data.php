<?php
/**
 * Ledger field definitions, validation, persistence, and history.
 *
 * @package Kingy_Open_Model_Ledger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KOML_Data {
	const POST_TYPE = 'kingy_ai_model';

	/**
	 * Register hooks.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	/**
	 * Scalar ledger fields.
	 *
	 * @return array
	 */
	public static function scalar_fields() {
		return array(
			'scope_status'         => array(
				'label'   => __( 'Ledger scope', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'curated'      => __( 'Curated', 'kingy-open-model-ledger' ),
					'under_review' => __( 'Under review', 'kingy-open-model-ledger' ),
					'excluded'     => __( 'Excluded', 'kingy-open-model-ledger' ),
				),
			),
			'announced_on'         => array( 'label' => __( 'Announcement date', 'kingy-open-model-ledger' ), 'type' => 'date' ),
			'weights_available_on' => array( 'label' => __( 'Weight availability date', 'kingy-open-model-ledger' ), 'type' => 'date' ),
			'weight_access'        => array(
				'label'   => __( 'Weight access', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'public'        => __( 'Public download', 'kingy-open-model-ledger' ),
					'click_through' => __( 'Click-through terms', 'kingy-open-model-ledger' ),
					'gated_auto'    => __( 'Automatically approved gate', 'kingy-open-model-ledger' ),
					'gated_manual'  => __( 'Manually approved gate', 'kingy-open-model-ledger' ),
					'partial'       => __( 'Partial weights', 'kingy-open-model-ledger' ),
					'source_only'   => __( 'Source only', 'kingy-open-model-ledger' ),
					'hosted_only'   => __( 'Hosted API only', 'kingy-open-model-ledger' ),
					'unavailable'   => __( 'Unavailable', 'kingy-open-model-ledger' ),
					'unknown'       => __( 'Unknown', 'kingy-open-model-ledger' ),
				),
			),
			'rights_profile'       => array(
				'label'   => __( 'Rights profile', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'permissive'    => __( 'Permissive', 'kingy-open-model-ledger' ),
					'custom'        => __( 'Custom terms', 'kingy-open-model-ledger' ),
					'restricted'    => __( 'Restricted', 'kingy-open-model-ledger' ),
					'noncommercial' => __( 'Noncommercial', 'kingy-open-model-ledger' ),
					'unclear'       => __( 'Unclear', 'kingy-open-model-ledger' ),
					'proprietary'   => __( 'Proprietary', 'kingy-open-model-ledger' ),
				),
			),
			'osaid_outcome'        => array(
				'label'   => __( 'OSAID assessment', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'meets'         => __( 'Meets OSAID', 'kingy-open-model-ledger' ),
					'does_not_meet' => __( 'Does not meet OSAID', 'kingy-open-model-ledger' ),
					'insufficient_evidence' => __( 'Insufficient evidence', 'kingy-open-model-ledger' ),
					'not_assessed'          => __( 'Not assessed', 'kingy-open-model-ledger' ),
				),
			),
			'license_name'         => array( 'label' => __( 'Primary license', 'kingy-open-model-ledger' ), 'type' => 'text' ),
			'commercial_use'       => array(
				'label'   => __( 'Commercial use', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'allowed'    => __( 'Allowed', 'kingy-open-model-ledger' ),
					'restricted' => __( 'Restricted', 'kingy-open-model-ledger' ),
					'prohibited' => __( 'Prohibited', 'kingy-open-model-ledger' ),
					'unclear'    => __( 'Unclear', 'kingy-open-model-ledger' ),
				),
			),
			'total_parameters'     => array( 'label' => __( 'Total parameters', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
			'active_parameters'    => array( 'label' => __( 'Active parameters', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
			'architecture'         => array( 'label' => __( 'Architecture', 'kingy-open-model-ledger' ), 'type' => 'text' ),
			'native_context_tokens'=> array( 'label' => __( 'Native context tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
			'input_modalities'     => array( 'label' => __( 'Input modalities', 'kingy-open-model-ledger' ), 'type' => 'csv' ),
			'output_modalities'    => array( 'label' => __( 'Output modalities', 'kingy-open-model-ledger' ), 'type' => 'csv' ),
			'lifecycle_status'     => array(
				'label'   => __( 'Lifecycle', 'kingy-open-model-ledger' ),
				'type'    => 'select',
				'options' => array(
					'current'             => __( 'Current', 'kingy-open-model-ledger' ),
					'superseded'          => __( 'Superseded', 'kingy-open-model-ledger' ),
					'deprecated'          => __( 'Deprecated', 'kingy-open-model-ledger' ),
					'withdrawn'           => __( 'Withdrawn', 'kingy-open-model-ledger' ),
					'weights_unavailable' => __( 'Weights unavailable', 'kingy-open-model-ledger' ),
					'api_unavailable'     => __( 'API unavailable', 'kingy-open-model-ledger' ),
					'announced'           => __( 'Announced', 'kingy-open-model-ledger' ),
				),
			),
			'replaced_by'           => array( 'label' => __( 'Replacement model post ID', 'kingy-open-model-ledger' ), 'type' => 'post_id' ),
			'repository_url'        => array( 'label' => __( 'Canonical repository URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
			'repository_revision'   => array( 'label' => __( 'Canonical repository revision', 'kingy-open-model-ledger' ), 'type' => 'text' ),
			'last_verified'         => array( 'label' => __( 'Record last verified', 'kingy-open-model-ledger' ), 'type' => 'date' ),
			'curation_note'         => array( 'label' => __( 'Curation note', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
		);
	}

	/**
	 * Repeatable ledger schemas.
	 *
	 * @return array
	 */
	public static function repeatable_fields() {
		return array(
			'variants' => array(
				'label'  => __( 'Model variants', 'kingy-open-model-ledger' ),
				'fields' => array(
					'name'              => array( 'label' => __( 'Name', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'role'              => array( 'label' => __( 'Role', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'total_parameters'  => array( 'label' => __( 'Total parameters', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'active_parameters' => array( 'label' => __( 'Active parameters', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'context_tokens'    => array( 'label' => __( 'Context tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'input_modalities'  => array( 'label' => __( 'Input modalities', 'kingy-open-model-ledger' ), 'type' => 'csv' ),
					'output_modalities' => array( 'label' => __( 'Output modalities', 'kingy-open-model-ledger' ), 'type' => 'csv' ),
				),
			),
			'license_components' => array(
				'label'  => __( 'License components', 'kingy-open-model-ledger' ),
				'fields' => array(
					'component'      => array( 'label' => __( 'Component', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'license'        => array( 'label' => __( 'License', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'terms_url'      => array( 'label' => __( 'Terms URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'commercial_use' => array( 'label' => __( 'Commercial use', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'modification'   => array( 'label' => __( 'Modification', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'redistribution' => array( 'label' => __( 'Redistribution', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'restrictions'   => array( 'label' => __( 'Restrictions', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
					'osaid_status'   => array( 'label' => __( 'OSAID status', 'kingy-open-model-ledger' ), 'type' => 'text' ),
				),
			),
			'artifacts' => array(
				'label'  => __( 'Repositories and artifacts', 'kingy-open-model-ledger' ),
				'fields' => array(
					'name'           => array( 'label' => __( 'Artifact', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'url'            => array( 'label' => __( 'Artifact URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'revision'       => array( 'label' => __( 'Revision', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'path'           => array( 'label' => __( 'Path / filename', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'format'         => array( 'label' => __( 'Format', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'quantization'   => array( 'label' => __( 'Quantization', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'size_bytes'     => array( 'label' => __( 'Size in bytes', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'sha256'         => array( 'label' => __( 'SHA-256', 'kingy-open-model-ledger' ), 'type' => 'sha256' ),
					'provenance'     => array( 'label' => __( 'Provenance', 'kingy-open-model-ledger' ), 'type' => 'text' ),
				),
			),
			'evidence' => array(
				'label'  => __( 'Field-level evidence', 'kingy-open-model-ledger' ),
				'fields' => array(
					'field'       => array( 'label' => __( 'Ledger field', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'method'      => array( 'label' => __( 'Verification method', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'source_title'=> array( 'label' => __( 'Source title', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'source_url'  => array( 'label' => __( 'Source URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'locator'     => array( 'label' => __( 'Source locator', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'confidence'  => array( 'label' => __( 'Confidence', 'kingy-open-model-ledger' ), 'type' => 'confidence' ),
					'verified_on' => array( 'label' => __( 'Verified on', 'kingy-open-model-ledger' ), 'type' => 'date' ),
					'note'        => array( 'label' => __( 'Evidence note', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
				),
			),
			'runtime_support' => array(
				'label'  => __( 'Runtime support', 'kingy-open-model-ledger' ),
				'fields' => array(
					'runtime'       => array( 'label' => __( 'Runtime', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'version'       => array( 'label' => __( 'Version', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'backend'       => array( 'label' => __( 'Backend', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'artifact'      => array( 'label' => __( 'Artifact', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'status'        => array( 'label' => __( 'Support status', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'format'        => array( 'label' => __( 'Format', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'quantization'  => array( 'label' => __( 'Quantization', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'source_url'    => array( 'label' => __( 'Source URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'verified_on'   => array( 'label' => __( 'Verified on', 'kingy-open-model-ledger' ), 'type' => 'date' ),
					'notes'         => array( 'label' => __( 'Notes', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
				),
			),
			'hardware_fit' => array(
				'label'  => __( 'Hardware fit', 'kingy-open-model-ledger' ),
				'fields' => array(
					'artifact'       => array( 'label' => __( 'Artifact', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'runtime'        => array( 'label' => __( 'Runtime', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'hardware'       => array( 'label' => __( 'Hardware', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'basis'          => array( 'label' => __( 'Basis (approximate/observed)', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'context_tokens' => array( 'label' => __( 'Context tested', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'batch'          => array( 'label' => __( 'Batch', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'peak_memory_gb' => array( 'label' => __( 'Peak memory GB', 'kingy-open-model-ledger' ), 'type' => 'decimal' ),
					'tokens_per_second' => array( 'label' => __( 'Tokens / second', 'kingy-open-model-ledger' ), 'type' => 'decimal' ),
					'fit'            => array( 'label' => __( 'Fit', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'source_url'     => array( 'label' => __( 'Source URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'verified_on'    => array( 'label' => __( 'Verified on', 'kingy-open-model-ledger' ), 'type' => 'date' ),
					'notes'          => array( 'label' => __( 'Notes', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
				),
			),
			'api_offers' => array(
				'label'  => __( 'API providers and pricing', 'kingy-open-model-ledger' ),
				'fields' => array(
					'provider'                 => array( 'label' => __( 'Provider', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'model_id'                 => array( 'label' => __( 'Provider model ID', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'base_url'                 => array( 'label' => __( 'API URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'context_tokens'           => array( 'label' => __( 'Context tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'output_tokens'            => array( 'label' => __( 'Maximum output tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal_integer' ),
					'input_price'              => array( 'label' => __( 'Input / 1M tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal' ),
					'output_price'             => array( 'label' => __( 'Output / 1M tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal' ),
					'cached_input_price'       => array( 'label' => __( 'Cached input / 1M tokens', 'kingy-open-model-ledger' ), 'type' => 'decimal' ),
					'currency'                 => array( 'label' => __( 'Currency', 'kingy-open-model-ledger' ), 'type' => 'currency' ),
					'effective_on'             => array( 'label' => __( 'Effective on', 'kingy-open-model-ledger' ), 'type' => 'date' ),
					'status'                   => array( 'label' => __( 'Offer status', 'kingy-open-model-ledger' ), 'type' => 'text' ),
					'source_url'               => array( 'label' => __( 'Source URL', 'kingy-open-model-ledger' ), 'type' => 'url' ),
					'verified_on'              => array( 'label' => __( 'Verified on', 'kingy-open-model-ledger' ), 'type' => 'date' ),
					'notes'                    => array( 'label' => __( 'Notes', 'kingy-open-model-ledger' ), 'type' => 'textarea' ),
				),
			),
		);
	}

	/**
	 * Register protected post meta on the existing model CPT.
	 */
	public static function register_meta() {
		foreach ( self::scalar_fields() as $name => $definition ) {
			register_post_meta(
				self::POST_TYPE,
				self::meta_key( $name ),
				array(
					'single'            => true,
					'type'              => 'string',
					'show_in_rest'      => false,
					'sanitize_callback' => array( __CLASS__, 'sanitize_registered_meta' ),
					'auth_callback'     => array( __CLASS__, 'can_edit_meta' ),
				)
			);
		}

		foreach ( self::repeatable_fields() as $name => $definition ) {
			register_post_meta(
				self::POST_TYPE,
				self::meta_key( $name ),
				array(
					'single'            => true,
					'type'              => 'array',
					'show_in_rest'      => false,
					'sanitize_callback' => array( __CLASS__, 'sanitize_registered_meta' ),
					'auth_callback'     => array( __CLASS__, 'can_edit_meta' ),
				)
			);
		}

		register_post_meta(
			self::POST_TYPE,
			'_koml_change_log',
			array(
				'single'        => true,
				'type'          => 'array',
				'show_in_rest'  => false,
				'auth_callback' => array( __CLASS__, 'can_edit_meta' ),
			)
		);
	}

	/**
	 * Protected meta authorization callback.
	 *
	 * @param bool   $allowed Existing result.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id Post ID.
	 * @return bool
	 */
	public static function can_edit_meta( $allowed = false, $meta_key = '', $post_id = 0 ) {
		return $post_id > 0 && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Sanitize registered metadata when it is written outside the ledger form.
	 *
	 * @param mixed  $value Value.
	 * @param string $meta_key Meta key.
	 * @return mixed
	 */
	public static function sanitize_registered_meta( $value, $meta_key ) {
		$name = preg_replace( '/^_koml_/', '', $meta_key );
		$scalars = self::scalar_fields();
		if ( isset( $scalars[ $name ] ) ) {
			return self::sanitize_defined_value( $value, $scalars[ $name ] );
		}

		return self::sanitize_rows( $name, $value );
	}

	/**
	 * Save a complete ledger form and append an immutable audit entry.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $raw_scalars Scalar form values.
	 * @param array $raw_repeatables Repeatable form values.
	 * @param string $source Change source.
	 * @return array Changes made.
	 */
	public static function save_ledger( $post_id, $raw_scalars, $raw_repeatables, $source = 'admin' ) {
		$changes = array();
		$audit_note = '';

		if (
			isset( $raw_scalars['scope_status'] ) &&
			'curated' === sanitize_key( $raw_scalars['scope_status'] ) &&
			! has_post_thumbnail( $post_id )
		) {
			$raw_scalars['scope_status'] = 'under_review';
			$audit_note = __( 'Curated status was rejected because the model record has no featured image; the record was saved as under review.', 'kingy-open-model-ledger' );
		}

		foreach ( self::scalar_fields() as $name => $definition ) {
			$raw = isset( $raw_scalars[ $name ] ) ? $raw_scalars[ $name ] : '';
			$new = self::sanitize_defined_value( $raw, $definition );
			self::persist_value( $post_id, self::meta_key( $name ), $new, $changes );
		}

		foreach ( self::repeatable_fields() as $name => $definition ) {
			$raw = isset( $raw_repeatables[ $name ] ) ? $raw_repeatables[ $name ] : array();
			$new = self::sanitize_rows( $name, $raw );
			self::persist_value( $post_id, self::meta_key( $name ), $new, $changes );
		}

		if ( ! empty( $changes ) ) {
			self::append_change_log( $post_id, $changes, $source, $audit_note );
		}

		return $changes;
	}

	/**
	 * Return all ledger data with stable public field names.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $include_history Include change history.
	 * @return array
	 */
	public static function get_ledger( $post_id, $include_history = true ) {
		$data = array();

		foreach ( self::scalar_fields() as $name => $definition ) {
			$data[ $name ] = get_post_meta( $post_id, self::meta_key( $name ), true );
		}

		foreach ( self::repeatable_fields() as $name => $definition ) {
			$rows = get_post_meta( $post_id, self::meta_key( $name ), true );
			$data[ $name ] = is_array( $rows ) ? array_values( $rows ) : array();
		}

		if ( $include_history ) {
			$history = get_post_meta( $post_id, '_koml_change_log', true );
			$data['change_log'] = is_array( $history ) ? array_values( $history ) : array();
		}

		return $data;
	}

	/**
	 * Build a protected meta key.
	 *
	 * @param string $name Field name.
	 * @return string
	 */
	public static function meta_key( $name ) {
		return '_koml_' . $name;
	}

	/**
	 * Sanitize rows according to a declared schema and discard blank rows.
	 *
	 * @param string $group Group name.
	 * @param mixed  $rows Raw rows.
	 * @return array
	 */
	public static function sanitize_rows( $group, $rows ) {
		$groups = self::repeatable_fields();
		if ( ! isset( $groups[ $group ] ) || ! is_array( $rows ) ) {
			return array();
		}

		$clean_rows = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean_row = array();
			$has_value = false;
			foreach ( $groups[ $group ]['fields'] as $field => $definition ) {
				$value = isset( $row[ $field ] ) ? self::sanitize_value( $row[ $field ], $definition['type'] ) : '';
				$clean_row[ $field ] = $value;
				if ( '' !== $value && null !== $value ) {
					$has_value = true;
				}
			}

			if ( $has_value ) {
				$clean_rows[] = $clean_row;
			}
		}

		return $clean_rows;
	}

	/**
	 * Sanitize one value without relying on platform integer width.
	 *
	 * @param mixed  $value Value.
	 * @param string $type Declared type.
	 * @return string
	 */
	public static function sanitize_value( $value, $type ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		switch ( $type ) {
			case 'url':
				return esc_url_raw( $value, array( 'http', 'https' ) );
			case 'date':
				return self::sanitize_date( $value );
			case 'decimal_integer':
				if ( ! preg_match( '/^\d+$/', $value ) ) {
					return '';
				}
				$normalized_integer = ltrim( $value, '0' );
				return '' === $normalized_integer ? '0' : $normalized_integer;
			case 'post_id':
				return ctype_digit( $value ) && (int) $value > 0 ? (string) absint( $value ) : '';
			case 'decimal':
				return preg_match( '/^\d+(?:\.\d+)?$/', $value ) ? $value : '';
			case 'sha256':
				return preg_match( '/^[a-fA-F0-9]{64}$/', $value ) ? strtolower( $value ) : '';
			case 'confidence':
				$value = strtolower( sanitize_text_field( $value ) );
				return in_array( $value, array( 'high', 'medium', 'low', 'unverified' ), true ) ? $value : '';
			case 'currency':
				$value = strtoupper( sanitize_text_field( $value ) );
				return preg_match( '/^[A-Z]{3}$/', $value ) ? $value : '';
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'csv':
				$parts = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $value ) ) ) );
				return implode( ', ', array_values( array_unique( $parts ) ) );
			case 'select':
			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize a value and enforce a declared option allowlist when present.
	 *
	 * @param mixed $value Value.
	 * @param array $definition Field definition.
	 * @return string
	 */
	private static function sanitize_defined_value( $value, $definition ) {
		$clean = self::sanitize_value( $value, $definition['type'] );
		if ( 'select' === $definition['type'] && '' !== $clean ) {
			if ( empty( $definition['options'] ) || ! array_key_exists( $clean, $definition['options'] ) ) {
				return '';
			}
		}

		return $clean;
	}

	/**
	 * Strict ISO calendar date validation.
	 *
	 * @param string $value Date value.
	 * @return string
	 */
	private static function sanitize_date( $value ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches ) ) {
			return '';
		}

		return checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ? $value : '';
	}

	/**
	 * Update or delete one value and capture the exact delta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $new New value.
	 * @param array  $changes Change accumulator.
	 */
	private static function persist_value( $post_id, $meta_key, $new, &$changes ) {
		$old = get_post_meta( $post_id, $meta_key, true );
		$old_for_compare = is_array( $old ) ? array_values( $old ) : (string) $old;
		$new_for_compare = is_array( $new ) ? array_values( $new ) : (string) $new;

		if ( wp_json_encode( $old_for_compare ) === wp_json_encode( $new_for_compare ) ) {
			return;
		}

		$changes[ preg_replace( '/^_koml_/', '', $meta_key ) ] = array(
			'old' => $old_for_compare,
			'new' => $new_for_compare,
		);

		if ( '' === $new || ( is_array( $new ) && empty( $new ) ) ) {
			delete_post_meta( $post_id, $meta_key );
		} else {
			update_post_meta( $post_id, $meta_key, $new );
		}
	}

	/**
	 * Append, never truncate, a change event.
	 *
	 * @param int    $post_id Post ID.
	 * @param array  $changes Field changes.
	 * @param string $source Change source.
	 * @param string $note Audit note.
	 */
	private static function append_change_log( $post_id, $changes, $source, $note = '' ) {
		$history = get_post_meta( $post_id, '_koml_change_log', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$event_type = 'record_updated';
		$effective_on = gmdate( 'Y-m-d' );
		if ( isset( $changes['weights_available_on']['new'] ) && $changes['weights_available_on']['new'] ) {
			$event_type = 'weights_available';
			$effective_on = $changes['weights_available_on']['new'];
		} elseif ( isset( $changes['announced_on']['new'] ) && $changes['announced_on']['new'] ) {
			$event_type = 'announcement';
			$effective_on = $changes['announced_on']['new'];
		} elseif ( isset( $changes['lifecycle_status'] ) ) {
			$event_type = 'lifecycle_changed';
		} elseif ( isset( $changes['artifacts'] ) ) {
			$event_type = 'artifacts_changed';
		} elseif ( isset( $changes['api_offers'] ) ) {
			$event_type = 'api_offers_changed';
		}

		$field_names = array_keys( $changes );
		$summary = sprintf(
			/* translators: %s: comma-separated ledger field names. */
			__( 'Ledger updated: %s.', 'kingy-open-model-ledger' ),
			implode( ', ', $field_names )
		);

		$history[] = array(
			'id'           => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'koml_', true ),
			'event_type'   => $event_type,
			'effective_on' => $effective_on,
			'recorded_at'  => gmdate( 'c' ),
			'actor_id'     => get_current_user_id(),
			'source'       => sanitize_key( $source ),
			'summary'      => $summary,
			'source_url'   => isset( $changes['repository_url']['new'] ) ? esc_url_raw( $changes['repository_url']['new'] ) : '',
			'note'         => sanitize_text_field( $note ),
			'changes'      => $changes,
		);

		update_post_meta( $post_id, '_koml_change_log', $history );
	}
}
