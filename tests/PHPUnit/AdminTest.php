<?php
/**
 * Tests for the plugin's admin UI.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology\Admin;

use Chronology;
use Mockery;
use WP_Mock as M;

class AdminTest extends Chronology\TestCase {

	protected $testFiles = array(
		'admin.php',
	);

	public function test_add_meta_boxes() {
		M::wpFunction( 'get_post_types', array(
			'times'  => 1,
			'return' => array(
				'post', 'page',
			),
		) );

		M::wpFunction( 'post_type_supports', array(
			'times'  => 1,
			'args'   => array( 'post', 'chronology' ),
			'return' => false,
		) );

		M::wpFunction( 'post_type_supports', array(
			'times'  => 1,
			'args'   => array( 'page', 'chronology' ),
			'return' => true,
		) );

		M::wpFunction( 'add_meta_box', array(
			'times'  => 1,
			'args'   => array(
				'chronology',
				'*',
				__NAMESPACE__ . '\meta_box_callback',
			),
		) );

		M::wpPassthruFunction( '_x' );

		add_meta_boxes();
	}

	public function test_meta_box_callback() {
		$this->markTestIncomplete( 'Incomplete' );
	}

	public function test_save_meta_box() {
		$this->markTestIncomplete( 'Incomplete' );
	}
}
