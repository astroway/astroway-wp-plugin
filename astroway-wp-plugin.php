<?php
/**
 * Plugin Name:       AstroWay
 * Plugin URI:        https://github.com/astroway/astroway-wp-plugin
 * Description:       Natal charts, synastry, transits, Tarot, Numerology, Human Design, AI horoscopes — shortcodes + Gutenberg blocks. Powered by api.astroway.info.
 * Version:           0.3.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            AstroWay
 * Author URI:        https://astroway.info
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       astroway
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
define( 'ASTROWAY_WP_PLUGIN_VERSION', $astroway_plugin_data['Version'] );
unset( $astroway_plugin_data );
define( 'ASTROWAY_WP_PLUGIN_FILE', __FILE__ );
define( 'ASTROWAY_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASTROWAY_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASTROWAY_API_BASE', 'https://api.astroway.info/v1' );

require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-renderer-decisions.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-public-client.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-blocks.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-cache.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-api-client.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-admin.php';
require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ '\\AstroWay\\WPPlugin\\Plugin', 'boot' ] );

// Load bundled translations before WP 6.x just-in-time loader runs, so admin
// strings hit our /languages/ before anything else queries them.
add_action( 'init', static function () {
	load_plugin_textdomain( 'astroway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
