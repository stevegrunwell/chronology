<?php
/**
 * Plugin Name: Chronology
 * Plugin URI:
 * Description: Scheduling framework for WordPress.
 * Version:     0.1.0
 * Author:      Steve Grunwell
 * Author URI:  https://stevegrunwell.com
 * License:     MIT
 * Text Domain: chronology
 * Domain Path: /languages
 *
 * @package Chronology
 * @author  Steve Grunwell
 */

namespace Chronology;

define( 'CHRONOLOGY_VERSION', '0.1.0' );

// Load plugin dependencies.
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/class-queue.php';

/**
 * Load the plugin textdomain.
 */
function load_textdomain() {
	load_plugin_textdomain( 'chronology', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\load_textdomain' );
