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
	 * Available actions for this queue.
	 *
	 * @var array
	 */
	protected $actions;

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
	 * The returned array should use WordPress actions as keys, with the values set to arrays
	 * containing information about the action:
	 * - label: A label to describe the action.
	 * - description: Optional. A longer description explaining what the action does.
	 *
	 * Example:
	 *
	 *   $actions = array(
	 *     'publish_post' => array(
	 *       'label'       => 'Publish Post',
	 *       'description' => 'Make this post available to the public',
	 *     ),
	 *   );
	 *
	 * @return array An array of available actions for Chronology events, where the key is the action
	 *               slug and the value is an array containing (at least) a 'label' key/value pair.
	 */
	public function get_actions() {
		if ( ! $this->actions ) {
			$actions = array();

			/**
			 * Filter the default list of actions available for Chronology callbacks.
			 *
			 * @param array $actions Currently-available actions.
			 * @param int   $post_id The ID of the current post.
			 */
			$actions = apply_filters( 'chronology_default_actions', $actions, $this->post_id );

			/**
			 * Filter the default list of actions available for Chronology callbacks for $post_type.
			 *
			 * @param array $actions Currently-available actions.
			 * @param int   $post_id The ID of the current post.
			 */
			$filter = sprintf( 'chronology_default_actions_%s', get_post_type( $this->post_id ) );
			$actions = apply_filters( $filter, $actions, $this->post_id );

			// Save the result in $this->actions.
			$this->actions = $actions;
		}

		return $this->actions;
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
