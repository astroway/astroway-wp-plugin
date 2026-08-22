<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What the sky is doing at the moment: retrogrades, the void of course Moon and
 * the planetary hours.
 *
 * These read the keyed api rather than /v1/public/*, so every one of them needs
 * an API key, and a site without one gets the same treatment as the generated
 * shortcodes: a note for the administrator and nothing for the visitor.
 *
 * Two habits run through the whole class. Answers are cached by what actually
 * makes them go stale, not for a flat hour. And the status a reader sees is
 * worked out at render time from a cached payload, never stored: "is Mercury
 * retrograde" cached for a day would be wrong for up to a day after it stations,
 * while the list of periods it is derived from stays true for months.
 *
 * @since 1.3.0
 */
class Sky {

	/** The bodies that station. The Sun and the Moon never do. */
	public const PLANETS = [
		'mercury' => 2,
		'venus'   => 3,
		'mars'    => 4,
		'jupiter' => 5,
		'saturn'  => 6,
		'uranus'  => 7,
		'neptune' => 8,
		'pluto'   => 9,
	];

	/** Widest range of days the void of course card will look ahead. */
	private const VOC_MAX_DAYS = 30;

	/**
	 * Every retrograde period around now, or null when it cannot be had.
	 *
	 * Always asks for all eight planets over the same window, so the single
	 * planet card and the whole board share one cache entry and a page carrying
	 * both spends one call.
	 */
	public static function retrogrades( ?int $now = null ): ?array {
		$now    = null === $now ? time() : $now;
		$window = self::retrograde_window( $now );

		return ( new ApiClient() )->cached_call(
			'POST',
			'/retrograde-periods',
			[
				'startDate' => $window[0],
				'endDate'   => $window[1],
				'planetIds' => array_values( self::PLANETS ),
			],
			PublicData::ttl_for( 'month', $now ),
			'sky_'
		);
	}

	/**
	 * The range to ask for, quantised to the calendar month.
	 *
	 * Two reasons for the shape. The api selects periods by the date they begin
	 * rather than by overlap, so a window starting today finds nothing about
	 * Saturn, which has been retrograde since July; seven months of lead covers
	 * the longest retrograde there is (Pluto, around 165 days) with room to
	 * spare. And quantising to the first of the month keeps the request, and so
	 * the cache key, identical all month: asked for "today plus a year" instead,
	 * every day would be a fresh key and a fresh call.
	 *
	 * @return array{0: string, 1: string} Start and end, YYYY-MM-DD.
	 */
	public static function retrograde_window( int $now ): array {
		$year  = (int) gmdate( 'Y', $now );
		$month = (int) gmdate( 'n', $now );

		return [
			gmdate( 'Y-m-d', (int) gmmktime( 0, 0, 0, $month - 7, 1, $year ) ),
			gmdate( 'Y-m-d', (int) gmmktime( 0, 0, 0, $month + 14, 1, $year ) ),
		];
	}

	/**
	 * Void of course windows from a moment onwards, or null.
	 *
	 * @param array    $params date, time, timezone_offset, range_days.
	 * @param int|null $now    Overridable for tests.
	 */
	public static function moon_voc( array $params, ?int $now = null ): ?array {
		$now    = null === $now ? time() : $now;
		$offset = self::offset( $params['timezone_offset'] ?? '', $now );
		$pinned = self::date( (string) ( $params['date'] ?? '' ) );
		$date   = '' !== $pinned ? $pinned : self::local_date( $offset, $now );

		$days = (int) ( $params['range_days'] ?? 0 );
		$days = $days > 0 ? min( $days, self::VOC_MAX_DAYS ) : 7;

		return ( new ApiClient() )->cached_call(
			'POST',
			'/moon-voc',
			[
				'date'           => $date,
				// Rejected as 400 when it is HH:mm. The api wants seconds.
				'time'           => self::time( (string) ( $params['time'] ?? '' ) ),
				'timezoneOffset' => $offset,
				'rangeDays'      => $days,
			],
			'' !== $pinned ? PublicData::ttl_for( 'static', $now ) : self::ttl_to_local_midnight( $offset, $now ),
			'sky_'
		);
	}

	/**
	 * The twenty-four planetary hours of a day at a place, or null.
	 *
	 * Coordinates are not optional and have no sensible default: the hours are
	 * the day divided by that horizon's sunrise and sunset, so guessing a place
	 * would answer confidently about the wrong one.
	 *
	 * @param array    $params date, latitude, longitude, timezone_offset.
	 * @param int|null $now    Overridable for tests.
	 */
	public static function planetary_hours( array $params, ?int $now = null ): ?array {
		$now = null === $now ? time() : $now;

		$latitude  = self::coordinate( $params['latitude'] ?? '', 90.0 );
		$longitude = self::coordinate( $params['longitude'] ?? '', 180.0 );
		if ( null === $latitude || null === $longitude ) {
			return null;
		}

		$offset = self::offset( $params['timezone_offset'] ?? '', $now );
		$pinned = self::date( (string) ( $params['date'] ?? '' ) );
		$date   = '' !== $pinned ? $pinned : self::local_date( $offset, $now );

		return ( new ApiClient() )->cached_call(
			'POST',
			'/planetary-hours',
			[
				'date'           => $date,
				'latitude'       => $latitude,
				'longitude'      => $longitude,
				'timezoneOffset' => $offset,
			],
			'' !== $pinned ? PublicData::ttl_for( 'static', $now ) : self::ttl_to_local_midnight( $offset, $now ),
			'sky_'
		);
	}

	/**
	 * The site's offset from UTC in hours, or the attribute when one was given.
	 *
	 * Read from wp_timezone() rather than the gmt_offset option: a site set to a
	 * named zone has that option updated by core only on save, so half the year
	 * it is an hour out.
	 */
	public static function offset( $raw, ?int $now = null ): float {
		$raw = trim( (string) $raw );
		if ( '' !== $raw && is_numeric( $raw ) ) {
			return max( -14.0, min( 14.0, (float) $raw ) );
		}

		$now = null === $now ? time() : $now;
		if ( function_exists( 'wp_timezone' ) ) {
			$zone = wp_timezone();
			if ( $zone instanceof \DateTimeZone ) {
				return $zone->getOffset( new \DateTimeImmutable( '@' . $now ) ) / HOUR_IN_SECONDS;
			}
		}
		return (float) get_option( 'gmt_offset', 0 );
	}

	/** Today where the site lives, YYYY-MM-DD. */
	public static function local_date( float $offset, int $now ): string {
		return gmdate( 'Y-m-d', $now + (int) round( $offset * HOUR_IN_SECONDS ) );
	}

	/**
	 * How far into a local day a moment is, in hours, counting past 24.
	 *
	 * Planetary hours run from sunrise to the next sunrise, so the last of them
	 * ends around 29.9: an hour numbered 25.5 is half past one the following
	 * morning, and the reader is inside it at that time. Returns null when the
	 * moment is not on that local day at all, which is what an author who pinned
	 * a date in the past has asked for.
	 */
	public static function hour_of_day( string $date, float $offset, int $now ): ?float {
		$midnight = strtotime( $date . ' 00:00:00 UTC' );
		if ( false === $midnight ) {
			return null;
		}
		$elapsed = ( $now - ( $midnight - (int) round( $offset * HOUR_IN_SECONDS ) ) ) / HOUR_IN_SECONDS;
		return ( $elapsed < 0 || $elapsed > 30 ) ? null : $elapsed;
	}

	/** Seconds until midnight where the site lives, never less than a minute. */
	public static function ttl_to_local_midnight( float $offset, int $now ): int {
		$local = $now + (int) round( $offset * HOUR_IN_SECONDS );
		$since = $local - ( (int) floor( $local / DAY_IN_SECONDS ) ) * DAY_IN_SECONDS;
		return (int) max( MINUTE_IN_SECONDS, DAY_IN_SECONDS - $since );
	}

	/** One planet's retrograde periods out of the whole board's payload, in order. */
	public static function periods_of( array $data, int $planet_id ): array {
		$out = [];
		foreach ( ( isset( $data['periods'] ) && is_array( $data['periods'] ) ? $data['periods'] : [] ) as $period ) {
			if ( ! is_array( $period ) || (int) ( $period['planetId'] ?? -1 ) !== $planet_id ) {
				continue;
			}
			$start = strtotime( (string) ( $period['retroStart'] ?? '' ) );
			$end   = strtotime( (string) ( $period['retroEnd'] ?? '' ) );
			if ( false === $start || false === $end ) {
				continue;
			}
			$out[] = [
				'start' => $start,
				'end'   => $end,
			];
		}
		usort(
			$out,
			static function ( array $a, array $b ) {
				return $a['start'] <=> $b['start'];
			}
		);
		return $out;
	}

	/**
	 * The period a moment falls inside, and the first one after it.
	 *
	 * @return array{0: ?array, 1: ?array}
	 */
	public static function state_at( array $periods, int $now ): array {
		$current = null;
		$next    = null;
		foreach ( $periods as $period ) {
			if ( $period['start'] <= $now && $now <= $period['end'] ) {
				$current = $period;
			} elseif ( $period['start'] > $now && null === $next ) {
				$next = $period;
			}
		}
		return [ $current, $next ];
	}

	/**
	 * The planets retrograde at a moment, named and in board order.
	 *
	 * Ordered by the PLANETS list rather than by the order the api returned the
	 * periods in, so the sentence in the email and the marked rows in the card
	 * read the same way round.
	 *
	 * @return list<string>
	 */
	public static function retrograde_now( array $data, int $now ): array {
		$backwards = [];
		foreach ( self::PLANETS as $slug => $planet_id ) {
			list( $current ) = self::state_at( self::periods_of( $data, $planet_id ), $now );
			if ( null !== $current ) {
				$backwards[] = Render::planet_label( ucfirst( $slug ) );
			}
		}
		return $backwards;
	}

	/** Whether both coordinates were given and are usable. */
	public static function has_coordinates( array $params ): bool {
		return null !== self::coordinate( $params['latitude'] ?? '', 90.0 )
			&& null !== self::coordinate( $params['longitude'] ?? '', 180.0 );
	}

	/** YYYY-MM-DD that is also a real date, or ''. */
	private static function date( string $raw ): string {
		$raw = trim( $raw );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $raw : '';
	}

	/** HH:mm:ss, filling in the seconds the api insists on. */
	private static function time( string $raw ): string {
		$raw = trim( $raw );
		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $raw ) ) {
			return $raw . ':00';
		}
		return preg_match( '/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $raw ) ? $raw : '00:00:00';
	}

	/** A coordinate inside its range, or null when there is none to use. */
	private static function coordinate( $raw, float $bound ): ?float {
		$raw = trim( (string) $raw );
		if ( '' === $raw || ! is_numeric( $raw ) ) {
			return null;
		}
		$value = (float) $raw;
		return ( $value < -$bound || $value > $bound ) ? null : $value;
	}
}
