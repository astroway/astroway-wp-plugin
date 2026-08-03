<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side reader for the anonymous JSON endpoints under /v1/public/*.
 *
 * Companion to PublicClient, which builds iframe URLs for the browser to load.
 * This one fetches the data here, on the server, so the widget's text ends up in
 * the page DOM: indexable, styleable by the theme, readable without JavaScript.
 *
 * Two things make that affordable. Every request declares the site through
 * X-AstroWay-Site-URL, which puts it in the api's per-site quota (300 an hour)
 * instead of the per-IP one (30), because a server fetching for every visitor is
 * not a visitor. And answers are cached until they actually change rather than
 * for a fixed hour: a daily horoscope is true until UTC midnight, a weekly one
 * until Monday, so a site showing all twelve signs spends twelve calls a day.
 *
 * Failures are cached too, for a minute. Without that, an exhausted quota or a
 * timing-out api turns every page render into another request, which keeps the
 * quota exhausted instead of letting it recover.
 */
class PublicData {

	/** How long a failure is remembered. Short enough to recover, long enough to stop a stampede. */
	public const NEGATIVE_TTL = MINUTE_IN_SECONDS;

	private const TIMEOUT = 5;

	/** Never cache for less than this, so a rollover at 23:59:50 cannot cause a burst. */
	private const MIN_TTL = MINUTE_IN_SECONDS;

	private const SIGNS = [
		'aries',
		'taurus',
		'gemini',
		'cancer',
		'leo',
		'virgo',
		'libra',
		'scorpio',
		'sagittarius',
		'capricorn',
		'aquarius',
		'pisces',
	];

	/**
	 * Endpoints we read server-side, with how long each answer stays true.
	 * `required` names params without a sensible default: absent one, we render
	 * nothing rather than guess (there is no reasonable stand-in for a sign).
	 */
	private const ENDPOINTS = [
		'daily_horoscope'   => [
			'path'      => '/public/horoscope/daily',
			'freshness' => 'day',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		'weekly_horoscope'  => [
			'path'      => '/public/horoscope/weekly',
			'freshness' => 'week',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		'monthly_horoscope' => [
			'path'      => '/public/horoscope/monthly',
			'freshness' => 'month',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		'tarot_daily'       => [
			'path'      => '/public/tarot/daily',
			'freshness' => 'day',
			'params'    => [ 'date' ],
			'required'  => [],
		],
		'moon_phase'        => [
			'path'      => '/public/moon-phase',
			'freshness' => 'day',
			'params'    => [ 'date' ],
			'required'  => [],
		],
		'planet_of_day'     => [
			'path'      => '/public/planet-of-day',
			'freshness' => 'day',
			'params'    => [ 'date' ],
			'required'  => [],
		],
		// The only POST among them, and the only one whose answer never changes:
		// a chart for a given birth moment is the same forever, so it is cached
		// for a month rather than to a calendar boundary. No `lang` either, the
		// payload is numbers.
		'natal'             => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
	];

	/** Widget keys this client can read. */
	public static function widgets(): array {
		return array_keys( self::ENDPOINTS );
	}

	public static function supports( string $widget ): bool {
		return isset( self::ENDPOINTS[ $widget ] );
	}

	/**
	 * Payload for a widget, or null when it cannot be had: unknown widget, a
	 * required param missing, a remembered failure, or the request failing now.
	 * Callers must handle null by falling back, never by rendering an empty box.
	 */
	public static function get( string $widget, array $params = [] ): ?array {
		$config = self::ENDPOINTS[ $widget ] ?? null;
		if ( null === $config ) {
			return null;
		}

		$query = self::query_for( $widget, $params );
		if ( null === $query ) {
			return null;
		}

		$key    = self::cache_key( $widget, $query );
		$cached = Cache::get( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		// A remembered failure short-circuits before the network, which is the
		// whole point: the api said no a moment ago and asking again on every
		// render is what keeps it saying no.
		if ( false !== Cache::get( $key . '_neg' ) ) {
			return null;
		}

		$payload = self::request( $config['path'], $query, $config['method'] ?? 'GET' );
		if ( null === $payload ) {
			Cache::set( $key . '_neg', 'fail', self::NEGATIVE_TTL );
			return null;
		}

		Cache::set( $key, $payload, self::ttl_for( $config['freshness'] ) );
		return $payload;
	}

	/**
	 * Seconds until the answer changes, measured in UTC because that is the
	 * calendar the api anchors on. Weekly answers are anchored to Monday and
	 * monthly ones to the 1st, so the boundary is not simply midnight.
	 */
	public static function ttl_for( string $freshness, ?int $now = null ): int {
		$now = null === $now ? time() : $now;
		// POSIX time counts UTC seconds with no leap seconds, so the modulo is
		// the last UTC midnight without needing a timezone at all.
		$midnight = $now - ( $now % DAY_IN_SECONDS );

		switch ( $freshness ) {
			case 'week':
				$dow      = (int) gmdate( 'N', $now ); // 1 = Monday .. 7 = Sunday
				$boundary = $midnight + ( 8 - $dow ) * DAY_IN_SECONDS;
				break;
			case 'month':
				$boundary = (int) gmmktime( 0, 0, 0, ( (int) gmdate( 'n', $now ) ) + 1, 1, (int) gmdate( 'Y', $now ) );
				break;
			case 'static':
				// Nothing about a past moment changes. A month is arbitrary but
				// keeps the transient table from holding entries forever.
				$boundary = $now + 30 * DAY_IN_SECONDS;
				break;
			default:
				$boundary = $midnight + DAY_IN_SECONDS;
				break;
		}

		return max( self::MIN_TTL, $boundary - $now );
	}

	/** Cache key over the exact query, so two signs or two languages never share an entry. */
	public static function cache_key( string $widget, array $query ): string {
		ksort( $query );
		return 'pub_' . $widget . '_' . md5( (string) wp_json_encode( $query ) );
	}

	/**
	 * Query for a widget, or null when a required param is unusable.
	 * A malformed date is dropped rather than refused: the api then answers for
	 * today, which beats blanking the widget over a typo in one attribute.
	 */
	private static function query_for( string $widget, array $params ): ?array {
		$config = self::ENDPOINTS[ $widget ];
		$query  = [];

		if ( in_array( 'chart', $config['params'], true ) ) {
			return self::chart_payload( $params, $config['required'] );
		}

		foreach ( $config['params'] as $name ) {
			$raw = isset( $params[ $name ] ) ? (string) $params[ $name ] : '';
			switch ( $name ) {
				case 'sign':
					$sign = strtolower( trim( $raw ) );
					if ( in_array( $sign, self::SIGNS, true ) ) {
						$query['sign'] = $sign;
					}
					break;
				case 'date':
					$date = self::sanitize_date( $raw );
					if ( '' !== $date ) {
						$query['date'] = $date;
					}
					break;
			}
		}

		foreach ( $config['required'] as $name ) {
			if ( ! isset( $query[ $name ] ) ) {
				return null;
			}
		}

		// Always explicit: without `lang` the api answers in Ukrainian.
		if ( false !== ( $config['lang'] ?? true ) ) {
			$query['lang'] = Plugin::resolve_lang( $params['lang'] ?? '' );
		}

		return $query;
	}

	/**
	 * Body for POST /public/chart.
	 *
	 * The field names are not the ones the embed routes use, and getting them
	 * wrong is silent: the schema passes unknown keys through and defaults
	 * latitude and longitude to 0, so sending `lat`/`lng`/`tz` returns a chart
	 * for the Gulf of Guinea under a 200. Read off the schema, not by analogy.
	 */
	private static function chart_payload( array $params, array $required ): ?array {
		$date = self::sanitize_date( (string) ( $params['date'] ?? '' ) );
		$time = trim( (string) ( $params['time'] ?? '' ) );
		if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			$time .= ':00'; // the schema wants HH:mm:ss and rejects HH:mm
		}
		if ( ! preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
			$time = '';
		}

		$have = [
			'date' => $date,
			'time' => $time,
		];
		foreach ( $required as $name ) {
			if ( '' === ( $have[ $name ] ?? '' ) ) {
				return null;
			}
		}

		return [
			'date'           => $date,
			'time'           => $time,
			'latitude'       => (float) ( $params['lat'] ?? 0 ),
			'longitude'      => (float) ( $params['lng'] ?? 0 ),
			'timezoneOffset' => (float) ( $params['tz'] ?? 0 ),
		];
	}

	/** YYYY-MM-DD that is also a real calendar date, or '' when it is neither. */
	private static function sanitize_date( string $raw ): string {
		$raw = trim( $raw );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $raw : '';
	}

	private static function request( string $path, array $payload, string $method = 'GET' ): ?array {
		$base = rtrim( ASTROWAY_API_BASE, '/' ) . $path;
		$args = [
			'timeout' => self::TIMEOUT,
			'headers' => [
				'Accept'              => 'application/json',
				// Declares which site this call renders for. The api reads it
				// and counts the anonymous quota per site instead of per IP,
				// which is the only reason server-side rendering fits inside
				// the free tier at all.
				'X-AstroWay-Site-URL' => home_url(),
			],
		];

		// Since api 2.124.0 these routes read a key when one is offered, and the
		// call is then counted against the plan instead of the 300-an-hour bucket
		// the whole site shares. Offered on every call rather than gated on the
		// tier: asking /v1/auth/keys/me first would cost a request to save one,
		// and an unknown or free-tier key falls back to the anonymous bucket
		// rather than being refused, so there is nothing to lose by trying.
		//
		// Server-side only. PublicClient::embed_url() must never carry it: that
		// URL is an iframe src, so it lands in the page's markup and in the
		// visitor's browser history.
		$key = ( new ApiClient() )->key();
		if ( '' !== $key ) {
			$args['headers']['X-Api-Key'] = $key;
		}

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $payload );
			$response                        = wp_remote_post( $base, $args );
		} else {
			$response = wp_remote_get( add_query_arg( $payload, $base ), $args );
		}

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		self::record_quota( $response, $status );

		if ( 200 !== $status ) {
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['ok'] ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return null;
		}

		return $body['data'];
	}

	/** Option holding the last quota reading taken from a real render. */
	public const QUOTA_OPTION = 'astroway_quota_seen';

	/**
	 * Remember what the api said about the quota on this call.
	 *
	 * Every render already carries the answer in its headers, so the admin
	 * notice does not need a request of its own. The old probe fired up to
	 * twelve an hour and, worse, measured a bucket nothing renders against:
	 * it sent no site header, so it read the 30-an-hour per-IP tier while the
	 * widgets were being served out of the 300-an-hour per-site one.
	 */
	private static function record_quota( $response, int $status ): void {
		$limit     = wp_remote_retrieve_header( $response, 'x-ratelimit-limit' );
		$remaining = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
		if ( '' === $limit && '' === $remaining && 429 !== $status ) {
			return;
		}

		update_option(
			self::QUOTA_OPTION,
			[
				'limit'     => '' === $limit ? null : (int) $limit,
				'remaining' => '' === $remaining ? null : (int) $remaining,
				'scope'     => (string) wp_remote_retrieve_header( $response, 'x-ratelimit-scope' ),
				'status'    => $status,
				'at'        => time(),
			],
			false
		);
	}

	/** Last quota reading, or null when nothing has been rendered recently. */
	public static function quota_seen( int $max_age = 900 ): ?array {
		$seen = get_option( self::QUOTA_OPTION, null );
		if ( ! is_array( $seen ) || empty( $seen['at'] ) ) {
			return null;
		}
		return ( time() - (int) $seen['at'] ) > $max_age ? null : $seen;
	}
}
