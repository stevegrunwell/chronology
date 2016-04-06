<?php
/**
 * Tests for the core plugin functionality.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology\Core;

use Chronology;
use Mockery;
use WP_Mock as M;

class CoreTest extends Chronology\TestCase {

	protected $testFiles = array(
		'core.php',
	);

	public function test_default_post_type_support() {
		M::wpFunction( 'add_post_type_support', array(
			'times'  => 1,
			'args'   => array( 'foo', 'chronology' ),
		) );

		M::wpFunction( 'add_post_type_support', array(
			'times'  => 1,
			'args'   => array( 'bar', 'chronology' ),
		) );

		M::onFilter( 'chronology_default_post_types' )
			->with( array() )
			->reply( array( 'foo', 'bar' ) );

		default_post_type_support();
	}
}
