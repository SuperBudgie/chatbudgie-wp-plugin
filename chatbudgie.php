<?php
/**
 * Main plugin file for ChatBudgie.
 *
 * @package ChatBudgie
 * Plugin Name: ChatBudgie
 * Plugin URI: https://github.com/SuperBudgie/chatbudgie-wp-plugin
 * Description: Display a chat dialog on WordPress pages, allowing users to talk with a RAG-based Agent to get website-related answers
 * Version: 1.1.3
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.0
 * Author: SuperBudgie Team
 * Author URI: https://chatbudgie.superbudgie.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: chatbudgie
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Register ChatBudgie's bundled Action Scheduler only when another copy is not
// already active. Action Scheduler also has its own version-aware loader, but
// this guard avoids loading a second library copy into WordPress' shared space.
$chatbudgie_as_path = __DIR__ . '/lib/action-scheduler/action-scheduler.php';
if (
	! class_exists( 'ActionScheduler', false )
	&& ! class_exists( 'ActionScheduler_Versions', false )
	&& ! function_exists( 'as_enqueue_async_action' )
	&& file_exists( $chatbudgie_as_path )
) {
	require_once $chatbudgie_as_path;
}

// Load local vector library.
if ( file_exists( __DIR__ . '/lib/Vektor/Core/Config.php' ) ) {
	require_once __DIR__ . '/lib/Vektor/Core/Config.php';
	require_once __DIR__ . '/lib/Vektor/Core/HnswLogic.php';
	require_once __DIR__ . '/lib/Vektor/Core/Math.php';
	require_once __DIR__ . '/lib/Vektor/Storage/Binary/VectorFile.php';
	require_once __DIR__ . '/lib/Vektor/Storage/Binary/GraphFile.php';
	require_once __DIR__ . '/lib/Vektor/Storage/Binary/MetaFile.php';
	require_once __DIR__ . '/lib/Vektor/Services/Indexer.php';
	require_once __DIR__ . '/lib/Vektor/Services/Searcher.php';
	require_once __DIR__ . '/lib/Vektor/Services/Optimizer.php';
}

define( 'CHATBUDGIE_VERSION', '1.1.3' );
define( 'CHATBUDGIE_APP_NAME', 'chatbudgie' );
define( 'CHATBUDGIE_APP_KEY_OPTION', 'chatbudgie_app_key' );
// define('CHATBUDGIE_PAYPAL_CLIENT_ID', 'AekooxzVQrv7o8r58pnHigf0owNuUr0i8rXBQemNt1ADaCom1v-63rNhrxy48zYhNQBKbqttnm1yUpTE');  // Sandbox.
define( 'CHATBUDGIE_PAYPAL_CLIENT_ID', 'AX6SyMmo4bBB1N1B0GagjoB_gjAwk47HYQk0T64VAAwj_YGTfYAWF3D0cLpmXCtYNxGG9jOvEk2Hv8-M' );  // Live.
define( 'CHATBUDGIE_PLUGIN_FILE', __FILE__ );
define( 'CHATBUDGIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHATBUDGIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CHATBUDGIE_BASE_URL', 'https://chat.superbudgie.com/' );
// define('CHATBUDGIE_BASE_URL', 'https://docker.internal:8443/');  // Docker test.
// define('CHATBUDGIE_BASE_URL', 'https://localhost:8443/');    // Local test.

require_once __DIR__ . '/class-chatbudgie.php';

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	$chatbudgie = \SuperBudgie\ChatBudgie\ChatBudgie::get_instance();

	register_activation_hook( __FILE__, array( $chatbudgie, 'activate' ) );
	register_deactivation_hook( __FILE__, array( $chatbudgie, 'deactivate' ) );
}
