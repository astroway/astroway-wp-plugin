<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public const PAGE_API_KEY    = 'astroway';
	public const PAGE_SETTINGS   = 'astroway-settings';
	public const PAGE_SHORTCODES = 'astroway-shortcodes';
	public const OPTION_KEY      = 'astroway_settings';

	// Back-compat: the old monolithic page slug now aliases the API Key page.
	public const PAGE_SLUG = self::PAGE_API_KEY;

	public static function register(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_astroway_verify_key', [ __CLASS__, 'ajax_verify_key' ] );
		add_action( 'wp_ajax_astroway_ping_health', [ __CLASS__, 'ajax_ping_health' ] );
		add_action( 'wp_ajax_astroway_purge_cache', [ __CLASS__, 'ajax_purge_cache' ] );
		add_action( 'wp_ajax_astroway_atlas_search', [ __CLASS__, 'ajax_atlas_search' ] );

		$basename = plugin_basename( ASTROWAY_WP_PLUGIN_FILE );
		add_filter( 'plugin_action_links_' . $basename, [ __CLASS__, 'plugin_action_links' ] );
	}

	public static function register_menu(): void {
		add_menu_page(
			__( 'AstroWay', 'astroway' ),
			__( 'AstroWay', 'astroway' ),
			'manage_options',
			self::PAGE_API_KEY,
			[ __CLASS__, 'render_api_key_page' ],
			self::menu_icon_data_uri(),
			58
		);
		// Override the auto-duplicated first submenu (which would read "AstroWay") with "API Key".
		add_submenu_page(
			self::PAGE_API_KEY,
			__( 'API Key', 'astroway' ),
			__( 'API Key', 'astroway' ),
			'manage_options',
			self::PAGE_API_KEY,
			[ __CLASS__, 'render_api_key_page' ]
		);
		add_submenu_page(
			self::PAGE_API_KEY,
			__( 'AstroWay Settings', 'astroway' ),
			__( 'Settings', 'astroway' ),
			'manage_options',
			self::PAGE_SETTINGS,
			[ __CLASS__, 'render_settings_page' ]
		);
		add_submenu_page(
			self::PAGE_API_KEY,
			__( 'AstroWay Shortcodes', 'astroway' ),
			__( 'Shortcodes', 'astroway' ),
			'manage_options',
			self::PAGE_SHORTCODES,
			[ __CLASS__, 'render_shortcodes_page' ]
		);
	}

	private static function menu_icon_data_uri(): string {
		// WP rewrites SVG fill colors to current admin scheme; outline disc + star cutout (evenodd fill-rule)
		// reads as "star inside orbit" silhouette regardless of which solid color WP injects.
		$svg = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">'
			. '<path fill="#fff" fill-rule="evenodd" d="M10 1A9 9 0 1 0 10 19A9 9 0 1 0 10 1ZM10 5L11.32 7.98L14.58 8.29L12.12 10.44L12.85 13.63L10 11.96L7.15 13.63L7.88 10.44L5.42 8.29L8.68 7.98Z"/>'
			. '</svg>';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- legit inline SVG embed for admin menu icon.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public static function register_settings(): void {
		register_setting(
			self::PAGE_API_KEY,
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
				'default'           => [ 'api_key' => '' ],
			]
		);
	}

	public const RENDER_MODES = [ 'auto', 'iframe', 'client' ];

	public static function sanitize_settings( $input ): array {
		$existing = (array) get_option( self::OPTION_KEY, [] );

		if ( isset( $input['api_key'] ) ) {
			$key = trim( (string) $input['api_key'] );
			if ( '' === $key || preg_match( '/^aw_[a-zA-Z0-9_]{4,}$/', $key ) ) {
				$existing['api_key'] = $key;
				// Invalidate any cached /me payload for the previous key
				if ( '' !== $key ) {
					Cache::delete( 'keys_me_' . md5( $key ) );
				}
			} else {
				add_settings_error(
					self::PAGE_API_KEY,
					'invalid_key',
					__( 'API key must start with "aw_" and contain only letters, digits, and underscores.', 'astroway' )
				);
			}
		}

		if ( isset( $input['render_mode'] ) ) {
			$mode                    = (string) $input['render_mode'];
			$existing['render_mode'] = in_array( $mode, self::RENDER_MODES, true ) ? $mode : 'auto';
		}

		if ( isset( $input['spend_cap_usd'] ) ) {
			$cap                       = (int) $input['spend_cap_usd'];
			$existing['spend_cap_usd'] = max( 0, min( 100000, $cap ) );
		}

		return $existing;
	}

	// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- WP option-get convention.
	public static function get( string $key, $default = null ) {
		$opts = (array) get_option( self::OPTION_KEY, [] );
		return $opts[ $key ] ?? $default;
	}

	public static function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'admin.php?page=' . self::PAGE_API_KEY );
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
		$hook_api_key    = 'toplevel_page_' . self::PAGE_API_KEY;
		$hook_settings   = self::PAGE_API_KEY . '_page_' . self::PAGE_SETTINGS;
		$hook_shortcodes = self::PAGE_API_KEY . '_page_' . self::PAGE_SHORTCODES;

		if ( ! in_array( $hook, [ $hook_api_key, $hook_settings, $hook_shortcodes ], true ) ) {
			return;
		}

		// Shared CSS — all 3 pages reuse hero/panel/btn/typography tokens.
		wp_enqueue_style(
			'astroway-admin',
			ASTROWAY_WP_PLUGIN_URL . 'assets/css/astroway-admin.css',
			[],
			ASTROWAY_WP_PLUGIN_VERSION
		);

		// Page-specific JS + localized i18n.
		if ( $hook === $hook_api_key ) {
			wp_enqueue_script(
				'astroway-admin-api-key',
				ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-admin-api-key.js',
				[ 'jquery' ],
				ASTROWAY_WP_PLUGIN_VERSION,
				true
			);
			wp_localize_script(
				'astroway-admin-api-key',
				'astrowayAdmin',
				[
					'nonce' => wp_create_nonce( 'astroway_admin' ),
					'i18n'  => [
						'verifying'    => __( 'Verifying…', 'astroway' ),
						'fallback'     => __( 'limited info', 'astroway' ),
						'plan'         => __( 'Plan', 'astroway' ),
						'creditsUsed'  => __( 'Used', 'astroway' ),
						'rateLimit'    => __( 'Rate', 'astroway' ),
						'domain'       => __( 'Bound to', 'astroway' ),
						'invalidKey'   => __( 'Enter a valid API key first.', 'astroway' ),
						'networkError' => __( 'Network error', 'astroway' ),
					],
				]
			);
		} elseif ( $hook === $hook_settings ) {
			wp_enqueue_script(
				'astroway-admin-settings',
				ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-admin-settings.js',
				[ 'jquery' ],
				ASTROWAY_WP_PLUGIN_VERSION,
				true
			);
			wp_localize_script(
				'astroway-admin-settings',
				'astrowayAdmin',
				[
					'nonce' => wp_create_nonce( 'astroway_admin' ),
					'i18n'  => [
						'pinging'      => __( 'Pinging…', 'astroway' ),
						'healthy'      => __( 'API is healthy', 'astroway' ),
						'unreachable'  => __( 'API unreachable', 'astroway' ),
						'confirmPurge' => __( 'Purge all cached data?', 'astroway' ),
						'purged'       => __( 'Cache cleared:', 'astroway' ),
						'copied'       => __( 'copied!', 'astroway' ),
					],
				]
			);
		} else { // $hook_shortcodes
			wp_enqueue_script(
				'astroway-admin-shortcodes',
				ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-admin-shortcodes.js',
				[],
				ASTROWAY_WP_PLUGIN_VERSION,
				true
			);
			wp_localize_script(
				'astroway-admin-shortcodes',
				'astrowayAdmin',
				[
					'nonce'   => wp_create_nonce( 'astroway_admin' ),
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => [
						'copied'      => __( 'copied!', 'astroway' ),
						'searching'   => __( 'Searching…', 'astroway' ),
						'noResults'   => __( 'No cities found.', 'astroway' ),
						'searchError' => __( 'Search error. Try again.', 'astroway' ),
						'minChars'    => __( 'Type at least 2 characters.', 'astroway' ),
						'pickCity'    => __( 'Pick a city to fill in lat / lon / tz', 'astroway' ),
					],
				]
			);
		}
	}

	public static function render_api_key_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'astroway' ) );
		}
		$api_key = (string) self::get( 'api_key', '' );

		// Pre-fetch /v1/auth/keys/me for the Status panel — uses TTL_KEYS_ME transient cache (30min).
		$status_data = null;
		if ( '' !== $api_key ) {
			$client      = new ApiClient();
			$status_data = $client->get_keys_me();
		}

		require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/admin-api-key.php';
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'astroway' ) );
		}
		$api_key = (string) self::get( 'api_key', '' );
		$stats   = Cache::stats();
		require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/admin-settings.php';
	}

	public static function render_shortcodes_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'astroway' ) );
		}
		$api_key = (string) self::get( 'api_key', '' );
		require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/admin-shortcodes.php';
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

	/**
	 * Proxy for app.astroway.info/api/atlas/search — server-side curl avoids
	 * the cross-origin restriction (the upstream allows only its own origin).
	 * Returns the JSON envelope verbatim. Cached 24h per query.
	 */
	public static function ajax_atlas_search(): void {
		check_ajax_referer( 'astroway_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( strlen( $q ) < 2 ) {
			wp_send_json_success( [ 'results' => [] ] );
		}
		$cache_key = 'atlas_' . md5( strtolower( $q ) );
		$cached    = Cache::get( $cache_key );
		if ( false !== $cached ) {
			wp_send_json_success( $cached );
		}
		$resp = wp_remote_get(
			add_query_arg(
				[
					'q'     => $q,
					'limit' => 6,
				],
				'https://app.astroway.info/api/atlas/search'
			),
			[
				'timeout' => 10,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);
		if ( is_wp_error( $resp ) ) {
			wp_send_json_error( [ 'message' => $resp->get_error_message() ] );
		}
		$code = wp_remote_retrieve_response_code( $resp );
		if ( 200 !== $code ) {
			wp_send_json_error( [ 'message' => 'upstream ' . $code ] );
		}
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $body ) ) {
			wp_send_json_error( [ 'message' => 'invalid upstream response' ] );
		}
		Cache::set( $cache_key, $body, DAY_IN_SECONDS );
		wp_send_json_success( $body );
	}
}
