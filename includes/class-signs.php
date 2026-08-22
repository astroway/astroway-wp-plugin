<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Readers for the three keyed endpoints that answer about signs.
 *
 * Companion to Sky, which answers about the moment. These answer about a sign,
 * a pair of signs, or the animal of a birth year, and none of them depends on
 * what time it is, which is why their answers are cached for as long as they
 * stay true rather than to a clock boundary.
 *
 * @since 1.4.0
 */
class Signs {

	/**
	 * The year ahead for one sign, or null.
	 *
	 * Only the daily, weekly and monthly horoscopes have anonymous routes, so
	 * this one needs a key like the rest of the keyed half.
	 */
	public static function yearly_horoscope( array $params, ?int $now = null ): ?array {
		$now  = null === $now ? time() : $now;
		$sign = Shortcodes::sanitize_sign( $params['sign'] ?? '' );
		if ( '' === $sign ) {
			return null;
		}

		$body = [
			'sign'     => $sign,
			'language' => self::language( $params ),
		];
		$date = self::date( (string) ( $params['date'] ?? '' ) );
		if ( '' !== $date ) {
			$body['date'] = $date;
		}

		return ( new ApiClient() )->cached_call( 'POST', '/horoscope/yearly', $body, self::ttl_to_year_end( $now ), 'sig_' );
	}

	/**
	 * How two signs read together, or null.
	 *
	 * The answer is an essay and carries no score. The competitor prints a
	 * percentage; ours would be a number with no calculation behind it, so the
	 * card prints what the api actually returns.
	 */
	public static function compatibility( array $params ): ?array {
		$one = Shortcodes::sanitize_sign( $params['sign1'] ?? '' );
		$two = Shortcodes::sanitize_sign( $params['sign2'] ?? '' );
		if ( '' === $one || '' === $two ) {
			return null;
		}

		return ( new ApiClient() )->cached_call(
			'POST',
			'/horoscope/compatibility',
			[
				'sign1'    => $one,
				'sign2'    => $two,
				'language' => self::language( $params ),
			],
			PublicData::ttl_for( 'static' ),
			'sig_'
		);
	}

	/**
	 * The Chinese animal, element and pillar of a birth moment, or null.
	 *
	 * The hour matters at the edges: the animal year turns at the start of
	 * spring rather than on 1 January, and the pillar reads the hour branch, so
	 * a birth time and an offset are worth sending when the author has them.
	 */
	public static function chinese_zodiac( array $params ): ?array {
		$date = self::date( (string) ( $params['date'] ?? '' ) );
		if ( '' === $date ) {
			return null;
		}

		$body = [ 'date' => $date ];
		$time = self::time( (string) ( $params['time'] ?? '' ) );
		if ( '' !== $time ) {
			$body['time']           = $time;
			$body['timezoneOffset'] = Sky::offset( $params['timezone_offset'] ?? '' );
		}
		$year = (int) ( $params['solar_year'] ?? 0 );
		if ( $year > 0 ) {
			$body['solarYear'] = $year;
		}

		return ( new ApiClient() )->cached_call( 'POST', '/chinese/zodiac/animal', $body, PublicData::ttl_for( 'static' ), 'sig_' );
	}

	/**
	 * The language to ask in.
	 *
	 * `language` as well as `lang`, because the generated shortcodes shipped in
	 * 1.2.0 named it the first way and those names still have to work.
	 */
	private static function language( array $params ): string {
		$given = trim( (string) ( $params['language'] ?? '' ) );
		return Plugin::resolve_lang( '' !== $given ? $given : ( $params['lang'] ?? '' ) );
	}

	/** Seconds until the new year, never less than a minute. */
	public static function ttl_to_year_end( int $now ): int {
		$boundary = (int) gmmktime( 0, 0, 0, 1, 1, ( (int) gmdate( 'Y', $now ) ) + 1 );
		return (int) max( MINUTE_IN_SECONDS, $boundary - $now );
	}

	/** YYYY-MM-DD that is also a real date, or ''. */
	private static function date( string $raw ): string {
		$raw = trim( $raw );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $raw : '';
	}

	/** HH:mm:ss, or '' when there is no usable time. */
	private static function time( string $raw ): string {
		$raw = trim( $raw );
		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $raw ) ) {
			return $raw . ':00';
		}
		return preg_match( '/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $raw ) ? $raw : '';
	}
}
