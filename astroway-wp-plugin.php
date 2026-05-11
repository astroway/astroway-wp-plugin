<?php
/**
 * Plugin Name:       AstroWay
 * Plugin URI:        https://astroway.info/wp-plugin
 * Description:       Natal charts, synastry, transits, Tarot, Numerology, Human Design, AI horoscopes — shortcodes + Gutenberg blocks. Powered by api.astroway.info.
 * Version:           0.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            AstroWay
 * Author URI:        https://astroway.info
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       astroway-wp-plugin
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASTROWAY_WP_PLUGIN_VERSION', '0.1.0' );
define( 'ASTROWAY_WP_PLUGIN_FILE', __FILE__ );
define( 'ASTROWAY_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASTROWAY_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASTROWAY_API_BASE', 'https://api.astroway.info/v1' );

require_once ASTROWAY_WP_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\\AstroWay\\WPPlugin\\Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', [ '\\AstroWay\\WPPlugin\\Plugin', 'boot' ] );
