<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public const PAGE_SLUG  = 'astroway';
	public const OPTION_KEY = 'astroway_settings';

	public static function register(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_astroway_verify_key', [ __CLASS__, 'ajax_verify_key' ] );
		add_action( 'wp_ajax_astroway_ping_health', [ __CLASS__, 'ajax_ping_health' ] );
		add_action( 'wp_ajax_astroway_purge_cache', [ __CLASS__, 'ajax_purge_cache' ] );

		$basename = plugin_basename( ASTROWAY_WP_PLUGIN_FILE );
		add_filter( 'plugin_action_links_' . $basename, [ __CLASS__, 'plugin_action_links' ] );
	}

	public static function register_menu(): void {
		add_options_page(
			__( 'AstroWay', 'astroway' ),
			__( 'AstroWay', 'astroway' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	public static function register_settings(): void {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
				'default'           => [ 'api_key' => '' ],
			]
		);
	}

	public static function sanitize_settings( $input ): array {
		$existing = (array) get_option( self::OPTION_KEY, [] );
		$key      = isset( $input['api_key'] ) ? trim( (string) $input['api_key'] ) : '';

		if ( '' === $key || preg_match( '/^aw_[a-zA-Z0-9_]{4,}$/', $key ) ) {
			$existing['api_key'] = $key;
			// Invalidate any cached /me payload for the previous key
			if ( ! empty( $input['api_key'] ) ) {
				Cache::delete( 'keys_me_' . md5( $key ) );
			}
		} else {
			add_settings_error(
				self::PAGE_SLUG,
				'invalid_key',
				__( 'API key must start with "aw_" and contain only letters, digits, and underscores.', 'astroway' )
			);
		}
		return $existing;
	}

	public static function get( string $key, $default = null ) {
		$opts = (array) get_option( self::OPTION_KEY, [] );
		return $opts[ $key ] ?? $default;
	}

	public static function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		$new_links    = [
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'astroway' )
			),
			'getkey'   => sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( 'https://api.astroway.info/dashboard/sign-up?source=wp_plugin' ),
				esc_html__( 'Get API Key', 'astroway' )
			),
		];
		return array_merge( $new_links, $links );
	}

	public static function enqueue_admin_assets( $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script(
			'astroway-admin',
			ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-admin.js',
			[ 'jquery' ],
			ASTROWAY_WP_PLUGIN_VERSION,
			true
		);
		wp_localize_script(
			'astroway-admin',
			'astrowayAdmin',
			[
				'nonce' => wp_create_nonce( 'astroway_admin' ),
				'i18n'  => [
					'verifying'       => __( 'Verifying…', 'astroway' ),
					'fallback'        => __( 'limited info — full validation pending api update', 'astroway' ),
					'plan'            => __( 'Plan', 'astroway' ),
					'creditsUsed'     => __( 'Used', 'astroway' ),
					'rateLimit'       => __( 'Rate', 'astroway' ),
					'domain'          => __( 'Bound to', 'astroway' ),
					'pinging'         => __( 'Pinging…', 'astroway' ),
					'healthy'         => __( 'API is healthy', 'astroway' ),
					'unreachable'     => __( 'API unreachable', 'astroway' ),
					'confirmPurge'    => __( 'Purge all cached data?', 'astroway' ),
					'purged'          => __( 'Cache cleared:', 'astroway' ),
					'networkError'    => __( 'Network error', 'astroway' ),
					'invalidKey'      => __( 'Enter a valid API key first.', 'astroway' ),
				],
			]
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'astroway' ) );
		}
		$api_key = (string) self::get( 'api_key', '' );
		$stats   = Cache::stats();
		require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/admin-settings.php';
	}

	public static function ajax_verify_key(): void {
		check_ajax_referer( 'astroway_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$client = new ApiClient();
		if ( ! $client->has_key() ) {
			wp_send_json_error( [ 'message' => __( 'No API key — plugin runs in anonymous mode (30 requests/hour per visitor IP).', 'astroway' ) ] );
		}
		wp_send_json_success( $client->get_keys_me( true ) );
	}

	public static function ajax_ping_health(): void {
		check_ajax_referer( 'astroway_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$client = new ApiClient();
		wp_send_json_success( $client->ping_health() );
	}

	public static function ajax_purge_cache(): void {
		check_ajax_referer( 'astroway_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		wp_send_json_success( [ 'purged' => Cache::purge_all() ] );
	}
}
