<?php
/**
 * Chronology Queue class, used for managing scheduled events for posts.
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology;

/**
 * Queue manager for Chronology events.
 */
class Queue {

	/**
	 * The items in this queue.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * The post ID this queue is attached to.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * The post meta key used to store queue data.
	 */
	const QUEUE_META_KEY = '_chronology_queue';

	/**
	 * Instantiate the Queue object.
	 *
	 * @param int|WP_Post Either a post ID or post object.
	 */
	public function __construct( $post ) {
		if ( $post instanceof \WP_Post ) {
			$post = $post->ID;
		}

		$this->post_id = intval( $post );
	}

	/**
	 * Retrieve an array of available actions.
	 *
	 * @return array An array of available actions for Chronology events, where the key is the action
	 *               slug and the value is an array containing (at least) a 'label' key/value pair.
	 *
	 * @todo remove the hard-coded list and instead pass a default list through an array.
	 */
	public function get_actions() {
		return array(
			'foo' => array(
				'label' => 'Foo',
				'description' => 'This is foo',
			),
			'bar' => array(
				'label' => 'Bar',
				'description' => 'This is bar',
			),
		);
	}

	/**
	 * Retrieve the current queue items.
	 *
	 * @return array $items The contents of the queue.
	 */
	public function get_items() {
		if ( null === $this->items ) {
			$this->items = (array) get_post_meta( $this->post_id, self::QUEUE_META_KEY, true );
		}

		return $this->items;
	}

	/**
	 * Save items for this queue.
	 *
	 * @param array $items
	 */
	public function save_items( $items ) {
		$queue = array();

		foreach ( $items as $item ) {
			if ( ! isset( $item['timestamp'], $item['action'] ) ) {
				continue;

			} elseif ( empty( $item['timestamp'] ) || empty( $item['action'] ) ) {
				continue;
			}

			$queue[] = array(
				'timestamp' => get_gmt_from_date( $item['timestamp'], 'U' ),
				'action'    => sanitize_text_field( $item['action'] ),
			);
		}

		update_post_meta( $this->post_id, self::QUEUE_META_KEY, $queue );
	}

}
