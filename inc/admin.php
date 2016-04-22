<?php
/**
 * Scripting for WP-Admin.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology\Admin;

use Chronology;

/**
 * Enqueue assets used in the admin area.
 */
function enqueue_assets() {
	wp_register_script(
		'chronology-admin',
		plugins_url( 'assets/js/admin.min.js', __DIR__ ),
		array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ),
		CHRONOLOGY_VERSION,
		true
	);

	wp_register_style(
		'chronology-admin',
		plugins_url( 'assets/css/admin.min.css', __DIR__ ),
		null,
		CHRONOLOGY_VERSION
	);

	if ( 'post' === get_current_screen()->base ) {
		wp_enqueue_script( 'chronology-admin' );
		wp_enqueue_style( 'chronology-admin' );
	}
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );

/**
 * Register the post meta box for supported post types.
 */
function add_meta_boxes() {
	$post_types = get_post_types();

	foreach ( $post_types as $post_type ) {
		if ( ! post_type_supports( $post_type, 'chronology' ) ) {
			continue;
		}

		add_meta_box(
			'chronology',
			_x( 'Scheduled Events', 'meta box title', 'chronology' ),
			__NAMESPACE__ . '\meta_box_callback'
		);
	}
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_meta_boxes' );

/**
 * Callback to populate the meta box on the post edit screen.
 *
 * @param WP_Post $post The current WP_Post object.
 */
function meta_box_callback( $post ) {
	$queue = new Chronology\Queue( get_the_ID() );
?>

	<table class="form-table">
		<thead>
			<tr>
				<th scope="col"><?php echo esc_html( _x( 'Date', 'table header', 'chronology' ) ); ?></th>
				<th scope="col"><?php echo esc_html( _x( 'Action', 'table header', 'chronology' ) ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $queue->get_items() as $item ) : ?>

				<?php build_meta_table_row( $queue, $item ); ?>

			<?php endforeach; ?>

			<?php build_meta_table_row( $queue, array( 'guid' => '_template' ) ); ?>
		</tbody>
	</table>

	<button class="button chronology-add-event"><?php esc_html_e( 'Add Event', 'chronology' ); ?></button>

<?php

	wp_nonce_field( 'chronology-edit', '_chronology_nonce' );
}

/**
 * Helper to assemble a table row for the meta box callback.
 *
 * @param Queue $queue The current Queue object.
 * @param array $args  {
 *   Optional. Values that should be populated within the table row.
 *
 *   @var int    $timestamp The stored Unix timestamp (UTC) for the event. Default is null.
 *   @var string $action    The action that should be triggered at this event. Default is null.
 *   @var string $guid      A unique string that can be included in IDs to guarantee uniqueness.
 *                          Default is an automatically-generated unique ID.
 * }
 */
function build_meta_table_row( $queue, $args ) {
	$args = wp_parse_args( $args, array(
		'timestamp' => null,
		'action'    => null,
		'guid'      => uniqid(),
	) );
?>

	<tr id="chronology-row-<?php echo esc_attr( $args['guid'] ); ?>">
		<td>
			<label for="chronology-timestamp-<?php echo esc_attr( $args['guid'] ); ?>" class="screen-reader-text">
				<?php echo esc_html( _x( 'Date', 'schedule item date/time', 'chronology' ) ); ?>
			</label>
			<input type="datetime"
				name="chronology[<?php echo esc_attr( $args['guid'] ); ?>][timestamp]"
				id="chronology-date-<?php echo esc_attr( $args['guid'] ); ?>"
				value="<?php echo $args['timestamp'] ? esc_attr( date( 'Y-m-d H:i', $args['timestamp'] ) ) : ''; ?>"
			/>
		</td>
		<td>
			<label for="chronology-action-<?php echo esc_attr( $args['guid'] ); ?>" class="screen-reader-text">
				<?php echo esc_html( _x( 'Action', 'schedule item action hook', 'chronology' ) ); ?>
			</label>
			<select name="chronology[<?php echo esc_attr( $args['guid'] ); ?>][action]" id="chronology-action-<?php echo esc_attr( $args['guid'] ); ?>">
				<option value=""><?php echo esc_html( _x( '(none)', 'default schedule item hook', 'chronology' ) ); ?>
				<?php foreach ( $queue->get_actions() as $slug => $action ) : ?>

					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $args['action'] ); ?>>
						<?php echo esc_html( $action['label'] ); ?>
					</option>

				<?php endforeach; ?>
			</select>
		</td>
	</tr>

<?php
}

/**
 * Save the meta box contents.
 *
 * @param int $post_id The ID of the post being saved.
 */
function save_meta_box( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;

	} elseif ( ! isset( $_POST['_chronology_nonce'], $_POST['chronology'] ) ) {
		return;

	} elseif ( ! wp_verify_nonce( $_POST['_chronology_nonce'], 'chronology-edit' ) ) {
		return;

	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$post = new Chronology\Queue( $post_id );
	$post->save_items( $_POST['chronology'] );
}
add_action( 'save_post', __NAMESPACE__ . '\save_meta_box' );
