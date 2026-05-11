<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	public static function boot(): void {
		load_plugin_textdomain(
			'astroway-wp-plugin',
			false,
			dirname( plugin_basename( ASTROWAY_WP_PLUGIN_FILE ) ) . '/languages'
		);
	}

	public static function activate(): void {
		if ( get_option( 'astroway_wp_plugin_activated_at' ) === false ) {
			update_option( 'astroway_wp_plugin_activated_at', time() );
		}
	}

	public static function deactivate(): void {
		// no-op for now
	}
}
