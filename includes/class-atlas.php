<?php
/**
 * City lookup against app.astroway.info/api/atlas: coordinates and the IANA
 * zone of a place. One path for the admin's search and the visitor's form.
 *
 * @package AstroWay\WPPlugin
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Atlas {

	private const URL = 'https://app.astroway.info/api/atlas/search';

	/**
	 * ['results' => [...]] on success, ['error' => message, 'retry_after' => s]
	 * otherwise. Only a non-empty answer is cached, so a stream of made-up
	 * names cannot fill the options table.
	 */
	/** @param bool $remember False for what a visitor typed: asked fresh and not kept. */
	public const SCRIPT = 'astroway-places';

	/** Suggestions one visitor may ask in five minutes, and the site in an hour. */
	private const PER_VISITOR = 20;
	private const PER_SITE    = 100;

	public static function register(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'script' ] );
	}

	public static function script(): void {
		wp_register_script(
			self::SCRIPT,
			ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-places.js',
			[],
			ASTROWAY_WP_PLUGIN_VERSION,
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	/** The city field's attributes, and its script asked for: suggestions are an extra, never the way in. */
	public static function field_attrs(): array {
		wp_enqueue_script( self::SCRIPT );
		return [
			'autocomplete'         => 'off',
			'data-astroway-places' => add_query_arg( 'lang', Plugin::resolve_lang( '' ), rest_url( 'astroway/v1/places' ) ),
		];
	}

	public static function routes(): void {
		register_rest_route(
			'astroway/v1',
			'/places',
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => [ __CLASS__, 'suggest' ],
				'args'                => [
					'q'    => [
						'type'     => 'string',
						'required' => true,
					],
					'lang' => [ 'type' => 'string' ],
				],
			]
		);
	}

	/**
	 * Up to six place names for what the visitor typed. A lookup spends the
	 * site's atlas allowance, which the forms themselves need, so suggestions
	 * get a share of it and each visitor a small one; an answer is cached a day.
	 */
	public static function suggest( \WP_REST_Request $r ): \WP_REST_Response {
		$q    = trim( sanitize_text_field( (string) $r->get_param( 'q' ) ) );
		$lang = sanitize_key( (string) $r->get_param( 'lang' ) );
		$out  = static fn( array $places ) => new \WP_REST_Response( [ 'places' => $places ], 200, [ 'Cache-Control' => 'no-store' ] );
		if ( mb_strlen( $q ) < 3 || mb_strlen( $q ) > 80 ) {
			return $out( [] );
		}
		$cached = Cache::get( 'atlas_' . md5( strtolower( $q ) . '|6' ) );
		if ( ! is_array( $cached ) ) {
			$who = 'astroway_places_' . substr( wp_hash( Form::client() ), 0, 16 );
			if ( ! self::take( $who, self::PER_VISITOR, 5 * MINUTE_IN_SECONDS ) || ! self::take( 'astroway_places_site', self::PER_SITE, HOUR_IN_SECONDS ) ) {
				return $out( [] );
			}
		}
		$found  = self::search( $q, 6, true );
		$places = [];
		foreach ( (array) ( $found['results'] ?? [] ) as $place ) {
			$places[] = self::label( $place, '' !== $lang ? $lang : 'en' );
		}
		return $out( array_values( array_unique( $places ) ) );
	}

	public static function search( string $q, int $limit = 6, bool $remember = true ): array {
		$q = trim( $q );
		if ( mb_strlen( $q ) < 2 ) {
			return [ 'results' => [] ];
		}
		$cache_key = 'atlas_' . md5( strtolower( $q ) . '|' . $limit );
		$cached    = $remember ? Cache::get( $cache_key ) : null;
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$resp = wp_remote_get(
			add_query_arg(
				[
					'q'     => rawurlencode( $q ),
					'limit' => $limit,
				],
				self::URL
			),
			[
				'timeout' => 10,
				'headers' => [
					'Accept'              => 'application/json',
					// Without it the atlas counts every site sharing this server's
					// egress address in one bucket, which on shared hosting is
					// somebody else's traffic spending your allowance.
					'X-AstroWay-Site-URL' => home_url(),
				],
			]
		);
		if ( is_wp_error( $resp ) ) {
			return [ 'error' => $resp->get_error_message() ];
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		if ( 429 === $code ) {
			// Retrying before retry_after is refused anyway and keeps the window
			// sliding forward, so hand the wait back to the caller.
			$retry = (int) wp_remote_retrieve_header( $resp, 'retry-after' );
			return [
				'error'       => __( 'City lookup is busy, try again in a moment.', 'astroway' ),
				'retry_after' => $retry > 0 ? $retry : 60,
			];
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
		if ( 200 !== $code || ! is_array( $body ) ) {
			return [ 'error' => 'upstream ' . $code ];
		}
		$results = array_values( array_filter( (array) ( $body['results'] ?? [] ), [ __CLASS__, 'usable' ] ) );
		if ( $results && $remember ) {
			Cache::set( $cache_key, [ 'results' => $results ], DAY_IN_SECONDS );
		}
		return [ 'results' => $results ];
	}

	/**
	 * The place a visitor meant, or null when the name fits several: one
	 * result, or a first result named exactly as asked with no namesake after it.
	 */
	public static function pick( string $q, array $results ): ?array {
		if ( 1 === count( $results ) ) {
			return $results[0];
		}
		$want  = mb_strtolower( trim( $q ) );
		$exact = array_values( array_filter( $results, static fn( $r ) => in_array( $want, self::names( $r ), true ) ) );
		return 1 === count( $exact ) ? $exact[0] : null;
	}

	/**
	 * One more within a fixed window that started with the first one: the
	 * expiry is what is left of the window, so a steady trickle does not keep
	 * pushing the reset away.
	 */
	private static function take( string $key, int $max, int $window ): bool {
		$now  = time();
		$slot = get_transient( $key );
		$slot = is_array( $slot ) && (int) ( $slot[1] ?? 0 ) > $now - $window ? $slot : [ 0, $now ];
		if ( (int) $slot[0] >= $max ) {
			return false;
		}
		set_transient( $key, [ (int) $slot[0] + 1, (int) $slot[1] ], max( 1, (int) $slot[1] + $window - $now ) );
		return true;
	}

	/** The name part of what was typed: "Kyiv, UA" asks the atlas for "Kyiv". */
	public static function name_of( string $typed ): string {
		$name = trim( explode( ',', $typed, 2 )[0] );
		return '' !== $name ? $name : trim( $typed );
	}

	/** The one place whose suggestion label is exactly what was typed, if any. */
	public static function pick_label( string $typed, array $results, string $lang ): ?array {
		if ( false === strpos( $typed, ',' ) ) {
			return null;
		}
		$want  = mb_strtolower( trim( $typed ) );
		$match = array_values( array_filter( $results, static fn( $r ) => mb_strtolower( self::label( $r, $lang ) ) === $want || mb_strtolower( self::label( $r, 'en' ) ) === $want ) );
		return 1 === count( $match ) ? $match[0] : null;
	}

	/** The place's name in the site's language, the English one otherwise. */
	public static function label( array $place, string $lang ): string {
		$name = (string) ( $place[ 'name_' . $lang ] ?? $place['name_en'] ?? $place['name'] ?? '' );
		// A numeric region is a code only the atlas can read; a letter one ("MO") says something.
		$region = (string) ( $place['region'] ?? '' );
		$tail   = array_filter( [ ctype_digit( $region ) ? '' : $region, (string) ( $place['country'] ?? '' ) ] );
		return $tail ? $name . ', ' . implode( ', ', $tail ) : $name;
	}

	private static function names( array $place ): array {
		$out = [];
		foreach ( [ 'name', 'name_en', 'name_uk', 'name_ru' ] as $field ) {
			if ( isset( $place[ $field ] ) ) {
				$out[] = mb_strtolower( (string) $place[ $field ] );
			}
		}
		return $out;
	}

	private static function usable( $place ): bool {
		return is_array( $place ) && isset( $place['latitude'], $place['longitude'], $place['timezone'] ) && is_numeric( $place['latitude'] ) && is_numeric( $place['longitude'] );
	}
}
