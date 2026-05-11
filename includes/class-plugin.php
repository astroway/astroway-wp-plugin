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

		Shortcodes::register();
		Blocks::register();
		Admin::register();

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
		add_action( 'admin_notices', [ __CLASS__, 'maybe_activation_notice' ] );
		add_action( 'wp_ajax_astroway_dismiss_activation_notice', [ __CLASS__, 'dismiss_activation_notice' ] );
	}

	public static function enqueue_frontend(): void {
		wp_enqueue_style(
			'astroway-widgets',
			ASTROWAY_WP_PLUGIN_URL . 'assets/css/astroway-widgets.css',
			[],
			ASTROWAY_WP_PLUGIN_VERSION
		);
	}

	public static function maybe_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'astroway_wp_plugin_notice_dismissed', true ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=astroway_dismiss_activation_notice' ),
			'astroway_dismiss_activation_notice'
		);

		printf(
			'<div class="notice notice-info is-dismissible" data-astroway-dismiss="%1$s"><p>%2$s</p></div>',
			esc_url( $dismiss_url ),
			wp_kses(
				sprintf(
					/* translators: %1$s: opening <a> tag, %2$s: closing </a> tag */
					__( 'AstroWay is active. Shortcodes work without an API key (30 requests/hour per visitor IP). For higher limits and Pro features, %1$sget a free API key%2$s.', 'astroway-wp-plugin' ),
					'<a href="https://api.astroway.info/dashboard/sign-up?source=wp_plugin" target="_blank" rel="noopener">',
					'</a>'
				),
				[
					'a' => [
						'href'   => true,
						'target' => true,
						'rel'    => true,
					],
				]
			)
		);
	}

	public static function dismiss_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		check_admin_referer( 'astroway_dismiss_activation_notice' );
		update_user_meta( get_current_user_id(), 'astroway_wp_plugin_notice_dismissed', 1 );
		wp_send_json_success();
	}

	public static function activate(): void {
		if ( false === get_option( 'astroway_wp_plugin_activated_at' ) ) {
			update_option( 'astroway_wp_plugin_activated_at', time() );
		}
	}

	public static function deactivate(): void {
		// Per-user dismiss flag intentionally kept across deactivation cycles.
	}
}
