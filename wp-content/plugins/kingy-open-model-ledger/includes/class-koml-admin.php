<?php
/**
 * WordPress editor UI for the model ledger.
 *
 * @package Kingy_Open_Model_Ledger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KOML_Admin {
	const NONCE_ACTION = 'koml_save_ledger';
	const NONCE_NAME   = 'koml_ledger_nonce';

	/**
	 * Register editor hooks only in wp-admin.
	 */
	public static function boot() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes_' . KOML_Data::POST_TYPE, array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . KOML_Data::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'featured_image_notice' ) );
	}

	/**
	 * Register the ledger meta box.
	 */
	public static function add_meta_box() {
		add_meta_box(
			'koml-ledger',
			__( 'Open Model Ledger', 'kingy-open-model-ledger' ),
			array( __CLASS__, 'render' ),
			KOML_Data::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render scalar, repeatable, and history controls.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$ledger = KOML_Data::get_ledger( $post->ID, true );
		?>
		<p>
			<?php esc_html_e( 'Record material model-family releases and exact evidence. Parameter counts and byte sizes are stored as decimal strings so large values remain exact.', 'kingy-open-model-ledger' ); ?>
		</p>
		<div class="koml-scalar-grid">
			<?php foreach ( KOML_Data::scalar_fields() as $name => $definition ) : ?>
				<?php self::render_scalar( $name, $definition, isset( $ledger[ $name ] ) ? $ledger[ $name ] : '' ); ?>
			<?php endforeach; ?>
		</div>

		<?php foreach ( KOML_Data::repeatable_fields() as $group => $schema ) : ?>
			<?php $rows = isset( $ledger[ $group ] ) && is_array( $ledger[ $group ] ) ? $ledger[ $group ] : array(); ?>
			<details class="koml-repeatable" open>
				<summary><strong><?php echo esc_html( $schema['label'] ); ?></strong> <span class="description">(<?php echo esc_html( number_format_i18n( count( $rows ) ) ); ?>)</span></summary>
				<div class="koml-table-wrap">
					<table class="widefat striped koml-table" data-koml-group="<?php echo esc_attr( $group ); ?>">
						<thead><tr>
							<?php foreach ( $schema['fields'] as $field ) : ?>
								<th scope="col"><?php echo esc_html( $field['label'] ); ?></th>
							<?php endforeach; ?>
							<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'kingy-open-model-ledger' ); ?></span></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $rows as $index => $row ) : ?>
								<?php self::render_repeatable_row( $group, (string) $index, $row, $schema ); ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p><button type="button" class="button koml-add-row" data-koml-group="<?php echo esc_attr( $group ); ?>"><?php esc_html_e( 'Add row', 'kingy-open-model-ledger' ); ?></button></p>
				<template id="koml-template-<?php echo esc_attr( $group ); ?>">
					<?php self::render_repeatable_row( $group, '__INDEX__', array(), $schema ); ?>
				</template>
			</details>
		<?php endforeach; ?>

		<details class="koml-history">
			<summary><strong><?php esc_html_e( 'Append-only change history', 'kingy-open-model-ledger' ); ?></strong> <span class="description">(<?php echo esc_html( number_format_i18n( count( $ledger['change_log'] ) ) ); ?>)</span></summary>
			<?php if ( empty( $ledger['change_log'] ) ) : ?>
				<p><?php esc_html_e( 'No ledger changes have been recorded yet.', 'kingy-open-model-ledger' ); ?></p>
			<?php else : ?>
				<ol class="koml-history-list">
					<?php foreach ( array_reverse( $ledger['change_log'] ) as $event ) : ?>
						<li>
							<strong><?php echo esc_html( isset( $event['changed_at'] ) ? $event['changed_at'] : '' ); ?></strong>
							<?php if ( ! empty( $event['source'] ) ) : ?>
								&mdash; <?php echo esc_html( $event['source'] ); ?>
							<?php endif; ?>
							<details><summary><?php esc_html_e( 'Inspect exact changes', 'kingy-open-model-ledger' ); ?></summary><pre><?php echo esc_html( wp_json_encode( isset( $event['changes'] ) ? $event['changes'] : array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre></details>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</details>

		<style>
			.koml-scalar-grid{display:grid;grid-template-columns:repeat(2,minmax(240px,1fr));gap:12px 20px;margin-bottom:20px}.koml-field label{display:block;font-weight:600;margin-bottom:4px}.koml-field input,.koml-field select,.koml-field textarea{width:100%}.koml-field--wide{grid-column:1/-1}.koml-repeatable,.koml-history{margin:14px 0;border:1px solid #c3c4c7;padding:10px}.koml-repeatable summary,.koml-history summary{cursor:pointer}.koml-table-wrap{overflow-x:auto;margin-top:10px}.koml-table{min-width:900px}.koml-table input,.koml-table textarea{min-width:130px;width:100%}.koml-table textarea{min-height:58px}.koml-table .koml-remove-row{color:#b32d2e}.koml-history-list pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#f6f7f7;padding:8px}@media(max-width:782px){.koml-scalar-grid{grid-template-columns:1fr}.koml-field--wide{grid-column:auto}}
		</style>
		<script>
		(function(){
			'use strict';
			document.addEventListener('click',function(event){
				var addButton=event.target.closest('.koml-add-row');
				if(addButton){
					var group=addButton.getAttribute('data-koml-group');
					var table=document.querySelector('.koml-table[data-koml-group="'+group+'"] tbody');
					var template=document.getElementById('koml-template-'+group);
					if(table&&template){
						var index=String(Date.now())+String(Math.floor(Math.random()*1000));
						var html=template.innerHTML.replace(/__INDEX__/g,index);
						table.insertAdjacentHTML('beforeend',html);
					}
				}
				var removeButton=event.target.closest('.koml-remove-row');
				if(removeButton){
					var row=removeButton.closest('tr');
					if(row){row.remove();}
				}
			});
		}());
		</script>
		<?php
	}

	/**
	 * Render a scalar control.
	 *
	 * @param string $name Field name.
	 * @param array  $definition Field definition.
	 * @param mixed  $value Current value.
	 */
	private static function render_scalar( $name, $definition, $value ) {
		$is_wide = 'textarea' === $definition['type'];
		?>
		<div class="koml-field<?php echo $is_wide ? ' koml-field--wide' : ''; ?>">
			<label for="koml-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $definition['label'] ); ?></label>
			<?php if ( 'select' === $definition['type'] ) : ?>
				<select id="koml-<?php echo esc_attr( $name ); ?>" name="koml_scalars[<?php echo esc_attr( $name ); ?>]">
					<option value=""><?php esc_html_e( 'Not recorded', 'kingy-open-model-ledger' ); ?></option>
					<?php foreach ( $definition['options'] as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ( 'textarea' === $definition['type'] ) : ?>
				<textarea id="koml-<?php echo esc_attr( $name ); ?>" name="koml_scalars[<?php echo esc_attr( $name ); ?>]" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
			<?php else : ?>
				<input id="koml-<?php echo esc_attr( $name ); ?>" name="koml_scalars[<?php echo esc_attr( $name ); ?>]" type="<?php echo 'date' === $definition['type'] ? 'date' : 'text'; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo in_array( $definition['type'], array( 'decimal', 'decimal_integer', 'post_id' ), true ) ? 'inputmode="decimal"' : ''; ?>>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render one repeatable table row.
	 *
	 * @param string $group Group name.
	 * @param string $index Row index.
	 * @param array  $row Values.
	 * @param array  $schema Schema.
	 */
	private static function render_repeatable_row( $group, $index, $row, $schema ) {
		?>
		<tr>
			<?php foreach ( $schema['fields'] as $field_name => $definition ) : ?>
				<?php $value = isset( $row[ $field_name ] ) ? $row[ $field_name ] : ''; ?>
				<td data-label="<?php echo esc_attr( $definition['label'] ); ?>">
					<label class="screen-reader-text"><?php echo esc_html( $definition['label'] ); ?></label>
					<?php if ( 'textarea' === $definition['type'] ) : ?>
						<textarea name="koml_repeatables[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $index ); ?>][<?php echo esc_attr( $field_name ); ?>]" rows="2"><?php echo esc_textarea( $value ); ?></textarea>
					<?php else : ?>
						<input name="koml_repeatables[<?php echo esc_attr( $group ); ?>][<?php echo esc_attr( $index ); ?>][<?php echo esc_attr( $field_name ); ?>]" type="<?php echo 'date' === $definition['type'] ? 'date' : 'text'; ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo in_array( $definition['type'], array( 'decimal', 'decimal_integer' ), true ) ? 'inputmode="decimal"' : ''; ?>>
					<?php endif; ?>
				</td>
			<?php endforeach; ?>
			<td><button type="button" class="button-link-delete koml-remove-row"><?php esc_html_e( 'Remove', 'kingy-open-model-ledger' ); ?></button></td>
		</tr>
		<?php
	}

	/**
	 * Verify and persist the ledger form.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$scalars = isset( $_POST['koml_scalars'] ) && is_array( $_POST['koml_scalars'] ) ? wp_unslash( $_POST['koml_scalars'] ) : array();
		$repeatables = isset( $_POST['koml_repeatables'] ) && is_array( $_POST['koml_repeatables'] ) ? wp_unslash( $_POST['koml_repeatables'] ) : array();
		if ( isset( $scalars['scope_status'] ) && 'curated' === sanitize_key( $scalars['scope_status'] ) && ! has_post_thumbnail( $post_id ) ) {
			set_transient( 'koml_featured_image_notice_' . get_current_user_id(), (int) $post_id, 60 );
		}
		KOML_Data::save_ledger( $post_id, $scalars, $repeatables, 'admin' );
	}

	/**
	 * Explain a fail-closed curation demotion after the editor redirect.
	 */
	public static function featured_image_notice() {
		$transient_key = 'koml_featured_image_notice_' . get_current_user_id();
		$post_id = (int) get_transient( $transient_key );
		if ( ! $post_id ) {
			return;
		}

		delete_transient( $transient_key );
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Open Model Ledger: this record was saved as “Under review,” not “Curated,” because it has no featured image. Select and visually verify a story-specific featured image before curating the record.', 'kingy-open-model-ledger' ); ?></p>
		</div>
		<?php
	}
}
