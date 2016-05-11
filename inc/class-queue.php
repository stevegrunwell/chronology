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
	 * @param array $items Events to be saved.
	 */
	public function save_items( $items ) {
		$current = $this->get_items();
		$queue   = array();

		foreach ( $items as $item ) {
			if ( ! isset( $item['timestamp'], $item['action'] ) ) {
				continue;

			} elseif ( empty( $item['timestamp'] ) || empty( $item['action'] ) ) {
				continue;
			}

			$event = array(
				'timestamp' => get_gmt_from_date( $item['timestamp'], 'U' ),
				'action'    => sanitize_text_field( $item['action'] ),
			);

			// Scheduling the corresponding cron event.
			$this->schedule_event( $event['timestamp'], $event['action'] );

			$queue[] = $event;
		}

		// Remove events that are no longer present.
		foreach ( array_diff( $current, $queue ) as $old_event ) {
			$this->unschedule_event( $old_event['timestamp'], $old_event['action'] );
		}

		update_post_meta( $this->post_id, self::QUEUE_META_KEY, $queue );
	}

	/**
	 * Build the $args array that will be used to schedule/unschedule events.
	 *
	 * @return array Arguments to be passed to the scheduled event callback and generally used to
	 *               identify an event.
	 */
	protected function build_cron_args() {
		return array(
			'post_id' => $this->post_id,
		);
	}

	/**
	 * Schedule an event within WP-Cron.
	 *
	 * @param int    $timestamp The Unix timestamp of when the event should be executed.
	 * @param string $action    The action to be executed when the event is run.
	 * @return boolean True if the event was scheduled successfully, false otherwise.
	 */
	protected function schedule_event( $timestamp, $action ) {
		$args = $this->build_cron_args();

		return false === wp_schedule_single_event( $timestamp, $action, $args ) ? false : true;
	}

	/**
	 * Schedule an event within WP-Cron.
	 *
	 * @param int    $timestamp The Unix timestamp of when the event should be executed.
	 * @param string $action    The action to be executed when the event is run.
	 * @return boolean True if the event was scheduled successfully, false otherwise.
	 */
	protected function unschedule_event( $timestamp, $action ) {
		$args = $this->build_cron_args();

		return false === wp_unschedule_event( $timestamp, $action, $args ) ? false : true;
	}
}
