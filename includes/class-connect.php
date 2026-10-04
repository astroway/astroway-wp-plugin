<?php
/**
 * @package AstroWay\WPPlugin
 */

declare(strict_types=1);

namespace AstroWay\WPPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * Connecting the owner's AstroWay account in one click: the admin picks a key
 * on api.astroway.info, comes back with a code, and this site trades the code
 * for that key on its own site key. Contract: api.astroway.info
 * docs/WP-SITE-KEY-PLAN.md, section 4.
 */
class Connect {

	public const ACTION = 'astroway_connect';

	/** Where the admin chooses the key. */
	private const DASHBOARD = 'https://api.astroway.info/dashboard/connect';

	/** A code is good for five minutes; the state outlives it a little. */
	private const STATE_TTL = 15 * MINUTE_IN_SECONDS;

	private const RESULT = 'astroway_connect';

	public static function register(): void {
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'start' ] );
		add_action( 'admin_init', [ __CLASS__, 'finish' ] );
	}

	/** Only an installation holding its site key can trade a code, and only when no key is set. */
	public static function available(): bool {
		return ! Key::from_constant() && '' === Key::current() && '' !== SiteKey::key();
	}

	public static function start_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
	}

	public static function start(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'astroway' ) );
		}
		check_admin_referer( self::ACTION );
		// An old link must not replace a key the owner has saved since.
		if ( ! self::available() ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . Admin::PAGE_API_KEY ) );
			exit;
		}
		wp_redirect( self::authorize_url( self::remember_state() ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- the api's dashboard, a fixed address.
		exit;
	}

	public static function authorize_url( string $state ): string {
		// Built here rather than with add_query_arg(), which leaves values unencoded.
		return self::DASHBOARD . '?' . http_build_query(
			[
				'site'   => home_url(),
				'return' => admin_url( 'admin.php?page=' . Admin::PAGE_API_KEY ),
				'state'  => $state,
				'locale' => get_user_locale(),
				'source' => 'wp_plugin',
			],
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	/** A fresh state for this admin; a second tab replaces the first's. */
	public static function remember_state(): string {
		$state = rtrim( strtr( base64_encode( random_bytes( 24 ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- url-safe random token.
		set_transient( self::state_name(), $state, self::STATE_TTL );
		return $state;
	}

	/** The admin's return from the dashboard: trade the code, then drop it from the address. */
	public static function finish(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the state is this flow's nonce, checked below.
		if ( Admin::PAGE_API_KEY !== ( $_GET['page'] ?? '' ) || ! isset( $_GET['state'] ) || ( ! isset( $_GET['code'] ) && ! isset( $_GET['error'] ) ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$state = sanitize_text_field( wp_unslash( (string) $_GET['state'] ) );
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['code'] ) ) : '';
		$error = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( (string) $_GET['error'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$result = self::complete( $state, $code, $error );
		wp_safe_redirect( add_query_arg( self::RESULT, $result, admin_url( 'admin.php?page=' . Admin::PAGE_API_KEY ) ) );
		exit;
	}

	/**
	 * 'connected', or why not. The state is spent on the first return, so a
	 * reloaded or shared return address cannot trade a code again.
	 */
	public static function complete( string $state, string $code, string $error ): string {
		$expected = get_transient( self::state_name() );
		if ( ! is_string( $expected ) || '' === $state || ! hash_equals( $expected, $state ) ) {
			return 'state';
		}
		delete_transient( self::state_name() );
		if ( '' !== $error ) {
			return 'access_denied' === $error ? 'cancelled' : 'refused';
		}
		if ( ! preg_match( '/^[A-Za-z0-9_-]{20,128}$/', $code ) ) {
			return 'INVALID_CODE';
		}
		return self::exchange( $code );
	}

	private static function exchange( string $code ): string {
		$site_key = SiteKey::key();
		if ( '' === $site_key ) {
			return 'SITE_MISMATCH';
		}
		$response = wp_remote_post(
			rtrim( ASTROWAY_API_BASE, '/' ) . '/site-keys/connect/exchange',
			[
				'timeout' => 15,
				'headers' => [
					'Accept'              => 'application/json',
					'Content-Type'        => 'application/json',
					'X-Api-Key'           => $site_key,
					'X-AstroWay-Site-URL' => home_url(),
				],
				'body'    => (string) wp_json_encode(
					[
						'code'     => $code,
						'site_url' => home_url(),
					]
				),
			]
		);
		if ( is_wp_error( $response ) ) {
			return 'offline';
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data   = is_array( $body['data'] ?? null ) ? $body['data'] : ( is_array( $body ) ? $body : [] );
		$key    = (string) ( $data['key'] ?? '' );
		if ( 200 !== $status || ! preg_match( '/^aw_live_[A-Za-z0-9_]{4,}$/', $key ) ) {
			$code = (string) ( $body['error']['code'] ?? '' );
			return '' !== $code ? $code : 'refused';
		}

		Admin::save_key( $key, (string) ( $data['account'] ?? '' ) );
		// The api stops the site key a day after the trade; holding on to it would
		// leave a dead key to fall back on when the account is disconnected.
		SiteKey::retire();
		return 'connected';
	}

	/** The notice for a result the return redirect carried, '' for none. */
	public static function message( string $result ): string {
		switch ( $result ) {
			case '':
				return '';
			case 'connected':
				return __( 'Your AstroWay account is connected. This site now uses your key.', 'astroway' );
			case 'cancelled':
				return __( 'The connection was cancelled. Nothing changed.', 'astroway' );
			case 'state':
				return __( 'This connection link has expired or was opened in another browser. Start again from this page.', 'astroway' );
			case 'CODE_EXPIRED':
				return __( 'The connection took longer than five minutes. Start again from this page.', 'astroway' );
			case 'SITE_MISMATCH':
				return __( 'api.astroway.info issued this connection to another site, or this site has no key of its own yet. Start again from this page.', 'astroway' );
			case 'offline':
				return __( 'This site could not reach api.astroway.info to finish the connection. Try again in a moment.', 'astroway' );
			default:
				return __( 'api.astroway.info did not accept the connection. Start again from this page, or paste a key instead.', 'astroway' );
		}
	}

	private static function state_name(): string {
		return 'astroway_connect_state_' . get_current_user_id();
	}
}
