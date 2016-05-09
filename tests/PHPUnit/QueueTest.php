<?php
/**
 * Tests for the Queue class.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology;

use Mockery;
use ReflectionProperty;
use WP_Mock as M;

class QueueTest extends TestCase {

	protected $testFiles = array(
		'class-queue.php',
	);

	public function test__construct() {
		$instance = new Queue( 12345 );

		$prop = new ReflectionProperty( $instance, 'post_id' );
		$prop->setAccessible( true );

		$this->assertEquals( 12345, $prop->getValue( $instance ) );
	}

	public function test__construct_with_post_object() {
		$post     = Mockery::mock( '\WP_Post' )->makePartial();
		$post->ID = 12345;

		$instance = new Queue( $post );

		$prop = new ReflectionProperty( $instance, 'post_id' );
		$prop->setAccessible( true );

		$this->assertEquals( 12345, $prop->getValue( $instance ) );
	}

	public function test_get_actions() {
		$instance = new Queue( 123 );
		$expected = array(
			'my_action' => array(
				'label'       => 'My Action',
				'description' => 'It does stuff',
			),
		);

		M::wpFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( 123 ),
			'return' => 'post',
		) );

		M::onFilter( 'chronology_default_actions' )
			->with( array(), 123 )
			->reply( 'default_actions' );

		M::onFilter( 'chronology_default_actions_post' )
			->with( 'default_actions', 123 )
			->reply( $expected );

		$this->assertEquals( $expected, $instance->get_actions() );
	}

	public function test_get_actions_pulls_from_cache() {
		$instance = new Queue( 123 );
		$expected = array(
			'my_action' => array(
				'label'       => 'My Action',
				'description' => 'It does stuff',
			),
		);
		$property = new ReflectionProperty( $instance, 'actions' );
		$property->setAccessible( true );
		$property->setValue( $instance, $expected );

		$this->assertEquals( $expected, $instance->get_actions() );
	}

	public function test_get_items() {
		$instance = new Queue( 123 );

		M::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 123, Queue::QUEUE_META_KEY, true ),
			'return' => array( 'foo' ),
		) );

		$this->assertEquals( array( 'foo' ), $instance->get_items() );
	}

	public function test_get_items_casts_as_array() {
		$instance = new Queue( 123 );

		M::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( 123, Queue::QUEUE_META_KEY, true ),
			'return' => 'foo', // Note that this is now a string value.
		) );

		$this->assertInternalType( 'array', $instance->get_items() );
	}

	public function test_get_items_pulls_from_cache() {
		$instance = new Queue( 123 );

		$prop = new ReflectionProperty( $instance, 'items' );
		$prop->setAccessible( true );
		$prop->setValue( $instance, array( 'foo', 'bar' ) );

		M::wpFunction( 'get_post_meta', array(
			'times'  => 0,
		) );

		$this->assertEquals( array( 'foo', 'bar' ), $instance->get_items() );
	}

	public function test_save_items() {
		M::wpFunction( 'get_gmt_from_date', array(
			'times'  => 1,
			'args'   => array( 'TIMESTAMP', 'U' ),
			'return' => '%TIMESTAMP%'
		) );

		M::wpFunction( 'sanitize_text_field', array(
			'times'  => 1,
			'args'   => array( 'ACTION' ),
			'return' => '%ACTION%'
		) );

		M::wpFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 123, Queue::QUEUE_META_KEY, array(
				array(
					'timestamp' => '%TIMESTAMP%',
					'action'    => '%ACTION%',
				)
			) ),
		) );

		$instance = new Queue( 123 );
		$instance->save_items( array(
			array( 'timestamp' => 'TIMESTAMP', 'action' => 'ACTION' ),
		) );
	}

	public function test_save_skips_empty_values() {
		M::wpFunction( 'get_gmt_from_date', array(
			'times'  => 0,
		) );

		M::wpFunction( 'sanitize_text_field', array(
			'times'  => 0,
		) );

		M::wpFunction( 'update_post_meta', array(
			'times'  => 2,
			'args'   => array( 123, Queue::QUEUE_META_KEY, array() ),
		) );

		$instance = new Queue( 123 );

		// With timestamp, no action.
		$instance->save_items( array(
			array( 'timestamp' => '123', 'action' => '' ),
		) );

		// With action, no timestamp.
		$instance->save_items( array(
			array( 'timestamp' => '', 'action' => 'ACTION' ),
		) );
	}
}
