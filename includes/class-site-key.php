<?php
/**
 * Site key: the plugin proves it controls its domain and gets a key for the
 * free set, counted per site at 300 calls an hour, instead of leaning on a
 * header anyone can fill with an invented domain.
 *
 * Contract: api.astroway.info/docs/WP-SITE-KEY-PLAN.md, v1.1.
 *
 * @package AstroWay\WPPlugin
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SiteKey {

	public const OPTION = 'astroway_site_key';
	public const EVENT  = 'astroway_site_key_issue';
	private const PROOF = 'astroway_proof_';

	/** The api issues three keys a day per installation; failed checks count per IP instead. */
	private const DAILY_ISSUES = 3;

	/** A lock older than this belongs to a request that died. */
	private const LOCK_TTL = 120;

	public static function register(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
		add_action( self::EVENT, [ __CLASS__, 'issue' ] );
		// An update never runs the activation hook, so the dashboard is where
		// an existing install asks for its key. Scheduled, never inline: the
		// api calls this site back, and a PHP worker waiting on itself can
		// deadlock a host with one worker.
		add_action( 'admin_init', [ __CLASS__, 'schedule' ] );
	}

	public static function schedule(): void {
		// A site on its owner's key does not need one; removing that key brings the ask back.
		if ( '' === self::key() && '' === Key::current() && ! wp_next_scheduled( self::EVENT ) && self::next_try( time() ) <= time() ) {
			wp_schedule_single_event( time() + 5, self::EVENT );
		}
	}

	public static function routes(): void {
		register_rest_route(
			'astroway/v1',
			'/verify',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( $request ) {
					if ( ! defined( 'DONOTCACHEPAGE' ) ) {
						define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- the name page caches read.
					}
					[ $status, $body ] = self::proof( (string) $request->get_param( 'nonce' ) );
					$response          = new \WP_REST_Response( 200 === $status ? $body : [ 'code' => 'not_found' ], $status );
					$response->header( 'Cache-Control', 'no-store' );
					return $response;
				},
			]
		);
	}

	/** [status, body] for the api's callback. A proof is good for one read. */
	public static function proof( string $id ): array {
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id ) ) {
			return [ 404, [] ];
		}
		$proof = get_site_transient( self::PROOF . $id );
		if ( ! is_string( $proof ) || '' === $proof ) {
			return [ 404, [] ];
		}
		delete_site_transient( self::PROOF . $id );
		return [
			200,
			[
				'proof'    => $proof,
				'site_url' => self::origin()[0],
			],
		];
	}

	/** The site key for this host, or '' when there is none yet. */
	public static function key(): string {
		$sealed = self::entry()['key'] ?? '';
		if ( ! is_string( $sealed ) || '' === $sealed ) {
			return '';
		}
		return (string) Key::open( $sealed );
	}

	/** What the admin shows: whether there is a key, why not, and when the next try is. */
	public static function status( ?int $now = null ): array {
		$now = null === $now ? time() : $now;
		return [
			'has_key' => '' !== self::key(),
			'reason'  => (string) ( self::entry()['reason'] ?? '' ),
			'retry'   => self::next_try( $now ),
		];
	}

	/** Words for a reason issue() or note() records, for the admin. */
	public static function reason_text( string $reason ): string {
		$texts = [
			'offline'          => __( 'this site could not reach api.astroway.info; the host may block outgoing requests.', 'astroway' ),
			'unreachable'      => __( 'api.astroway.info could not reach this site.', 'astroway' ),
			'timeout'          => __( 'this site took too long to answer api.astroway.info.', 'astroway' ),
			'status'           => __( 'this site answered the check with an error; a security plugin or firewall that closes the REST API to visitors would do that.', 'astroway' ),
			'mismatch'         => __( 'the check came back with the wrong answer, often a page cache in front of the REST API.', 'astroway' ),
			'too_large'        => __( 'the check came back with a whole page instead of a short answer.', 'astroway' ),
			'INVALID_SITE_URL' => __( 'the site address is not a public https address.', 'astroway' ),
			'revoked'          => __( 'the previous key stopped working.', 'astroway' ),
			'domain_mismatch'  => __( 'the previous key belongs to another address.', 'astroway' ),
			'unreadable'       => __( 'api.astroway.info sent an answer this site could not read.', 'astroway' ),
		];
		return $texts[ $reason ] ?? __( 'api.astroway.info refused the request.', 'astroway' );
	}

	/**
	 * Ask the api for a key. 'have' when there is one, 'waiting' inside a
	 * back-off or while another request is asking, 'issued' or 'failed'.
	 */
	public static function issue( ?int $now = null ): string {
		$now = null === $now ? time() : $now;
		if ( '' !== self::key() ) {
			return 'have';
		}
		if ( self::next_try( $now ) > $now || ! self::lock( $now ) ) {
			return 'waiting';
		}

		try {
			return self::ask( $now );
		} finally {
			delete_site_option( self::option() . '_lock' );
		}
	}

	private static function ask( int $now ): string {
		$entry                     = self::entry();
		[ $site_url, $verify_url ] = self::origin();
		$id                        = wp_generate_uuid4();
		$proof                     = rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- base64url of random bytes, as the contract asks.
		set_site_transient( self::PROOF . $id, $proof, 600 );

		$response = wp_remote_post(
			rtrim( ASTROWAY_API_BASE, '/' ) . '/site-keys',
			[
				'timeout' => 15,
				'headers' => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body'    => (string) wp_json_encode(
					[
						'site_url'       => $site_url,
						'proof_id'       => $id,
						'proof'          => $proof,
						'verify_url'     => $verify_url,
						'plugin_version' => defined( 'ASTROWAY_WP_PLUGIN_VERSION' ) ? ASTROWAY_WP_PLUGIN_VERSION : '',
					]
				),
			]
		);
		// Read or not, the proof has done its job once the api has answered.
		delete_site_transient( self::PROOF . $id );

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$body = is_wp_error( $response ) ? [] : json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];
		// The api answers in an {ok, data} envelope; the contract's examples omit it.
		$data = is_array( $body['data'] ?? null ) ? $body['data'] : $body;

		if ( 201 === $code ) {
			$entry['issued']   = array_merge( self::recent( $entry['issued'] ?? [], $now ), [ $now ] );
			$entry['fails']    = 0;
			$entry['retry_at'] = 0;
			if ( is_string( $data['key'] ?? null ) && 0 === strpos( $data['key'], 'aw_' ) ) {
				$entry['key']    = Key::seal( $data['key'] );
				$entry['reason'] = '';
				self::save( $entry );
				return 'issued';
			}
			// Issued but unreadable still spends one of the day's three.
			$entry['reason'] = 'unreadable';
			self::save( $entry );
			return 'failed';
		}

		$error   = is_array( $body['error'] ?? null ) ? $body['error'] : [];
		$details = is_array( $error['details'] ?? null ) ? $error['details'] : [];
		$fails   = (int) ( $entry['fails'] ?? 0 ) + 1;

		$entry['fails']  = $fails;
		$entry['reason'] = 0 === $code ? 'offline' : (string) ( $details['reason'] ?? $error['code'] ?? 'status_' . $code );
		// One hour, then two, four, up to a day; the api's own retry_after wins when longer.
		$wait              = min( DAY_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** min( $fails - 1, 5 ) ) );
		$entry['retry_at'] = $now + max( $wait, (int) ( $details['retry_after'] ?? 0 ) );
		self::save( $entry );
		return 'failed';
	}

	/**
	 * Called with every api answer to a request that carried the site key.
	 * Only the key that was refused is dropped: a newer one saved while the
	 * request was out is left alone. A route outside the free set is a
	 * missing plan, not a bad key.
	 */
	public static function note( int $status, string $code, string $sent ): void {
		if ( '' === $sent || self::key() !== $sent ) {
			return;
		}
		if ( 401 === $status || ( 403 === $status && 'DOMAIN_MISMATCH' === $code ) ) {
			$entry = self::entry();
			unset( $entry['key'] );
			$entry['reason'] = 401 === $status ? 'revoked' : 'domain_mismatch';
			self::save( $entry );
		}
	}

	/** Drop the site key once the owner's key has replaced it. */
	public static function retire(): void {
		$entry = self::entry();
		unset( $entry['key'], $entry['reason'] );
		self::save( $entry );
	}

	/** When the next ask may go out: after any back-off, and within the day's three issues. */
	private static function next_try( int $now ): int {
		$entry  = self::entry();
		$next   = (int) ( $entry['retry_at'] ?? 0 );
		$issued = self::recent( $entry['issued'] ?? [], $now );
		if ( count( $issued ) >= self::DAILY_ISSUES ) {
			$next = max( $next, min( $issued ) + DAY_IN_SECONDS );
		}
		return $next;
	}

	private static function recent( $stamps, int $now ): array {
		return array_values( array_filter( array_map( 'intval', (array) $stamps ), static fn( $t ) => $t > $now - DAY_IN_SECONDS ) );
	}

	/** One ask at a time per host: two concurrent asks revoke each other's key. */
	private static function lock( int $now ): bool {
		$name = self::option() . '_lock';
		if ( add_site_option( $name, $now ) ) {
			return true;
		}
		if ( (int) get_site_option( $name, 0 ) < $now - self::LOCK_TTL ) {
			delete_site_option( $name );
			return add_site_option( $name, $now );
		}
		return false;
	}

	/**
	 * [site_url, verify_url]. The api keys an installation by host and home
	 * path, so every site of a network asks on its own address.
	 *
	 * The proof route goes as ?rest_route=: WordPress serves it under any
	 * permalink setting and any REST prefix, and the api takes that form.
	 */
	private static function origin(): array {
		$home = home_url();
		return [ $home, trailingslashit( $home ) . '?rest_route=/astroway/v1/verify' ];
	}

	private static function host( string $url ): string {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return 0 === strpos( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}

	/** One option per installation (host and home path), so two saving at once cannot drop each other. */
	private static function option(): string {
		$path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		return self::OPTION . '_' . preg_replace( '/[^a-z0-9._-]/', '', self::host( home_url() ) . ( '' === $path ? '' : '_' . str_replace( '/', '_', strtolower( $path ) ) ) );
	}

	private static function entry(): array {
		$one = get_site_option( self::option(), [] );
		return is_array( $one ) ? $one : [];
	}

	private static function save( array $entry ): void {
		update_site_option( self::option(), $entry );
	}
}
