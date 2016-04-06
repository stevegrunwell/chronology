<?php
/**
 * Scripting for WP-Admin.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology\Core;

/**
 * Bulk-register post-type support for Chronology.
 */
function default_post_type_support() {

	/**
	 * Bulk-register post-type support for Chronology.
	 *
	 * @param array $post_types An array of post-type names that should support Chronology.
	 */
	$post_types = apply_filters( 'chronology_default_post_types', array() );

	foreach ( (array) $post_types as $post_type ) {
		add_post_type_support( $post_type, 'chronology' );
	}
}
add_action( 'init', __NAMESPACE__ . '\default_post_type_support' );
