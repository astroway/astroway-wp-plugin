<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Vedic chart and the numerology chart, fetched for a card in the page.
 *
 * Both answers depend only on the birth data, so they are kept like the natal
 * chart. Two things move with time and are put into the request so the cache
 * key moves with them: the dasha in force (asked for on the 1st of the month)
 * and the personal year (asked for the current year).
 *
 * @since 2.6.0
 */
final class Birth {

	public const KUNDLI = '/vedic/kundli';

	public const DASHA = '/vedic/dashas/vimshottari/antar';

	public const DASHA_MAHA = '/vedic/dashas/vimshottari/maha';

	public const NAVAMSA = '/vedic/varga/D9';

	public const PROGRESSIONS = '/progressions';

	public const HARMONICS = '/harmonics';

	public const FIXED_STARS = '/fixed-stars';

	public const NUMEROLOGY_SYSTEMS = [ 'pythagorean', 'chaldean', 'kabbalistic', 'kabbalistic-strict', 'vedic' ];

	public static function numerology_path( string $system ): string {
		return '/numerology/' . ( in_array( $system, self::NUMEROLOGY_SYSTEMS, true ) ? $system : 'pythagorean' ) . '/chart';
	}

	/**
	 * Whether the card can be drawn: the owner's key opens every system, the
	 * site key only the Pythagorean one. A visitor's own calculation goes on
	 * the site key alone, so their names never spend the owner's credits.
	 */
	public static function can_read( string $path, bool $visitor = false ): bool {
		$client = new ApiClient( $visitor ? '' : null );
		return $client->can_read( $path );
	}

	/**
	 * @param array $params `date` Y-m-d, `time` H:i, `lat`, `lon`, `offset` hours.
	 */
	public static function kundli( array $params, ?int $now = null ): ?array {
		$now  = null === $now ? time() : $now;
		$date = (string) ( $params['date'] ?? '' );
		$time = (string) ( $params['time'] ?? '' );
		if ( '' === $date || ! preg_match( '/^\d{2}:\d{2}$/', $time ) || ! is_numeric( $params['lat'] ?? null ) || ! is_numeric( $params['lon'] ?? null ) ) {
			return null;
		}
		$body = [
			'date'           => $date,
			'time'           => $time,
			'timezoneOffset' => (float) ( $params['offset'] ?? 0 ),
			'latitude'       => (float) $params['lat'],
			'longitude'      => (float) $params['lon'],
			'targetDate'     => gmdate( 'Y-m-01', $now ),
		];
		// Without a language the api names the nakshatras in Ukrainian.
		$lang = Plugin::resolve_lang( (string) ( $params['lang'] ?? '' ) );
		$data = ( new ApiClient() )->cached_call( 'POST', self::KUNDLI, $body, PublicData::ttl_for( 'month', $now ), 'ved_', $lang );
		return is_array( $data ) && is_array( $data['grahas'] ?? null ) ? $data : null;
	}

	/**
	 * @param array $params `name`, `date` Y-m-d, `system`.
	 */
	public static function numerology( array $params, bool $visitor = false, ?int $now = null ): ?array {
		$now  = null === $now ? time() : $now;
		$name = trim( (string) ( $params['name'] ?? '' ) );
		$date = (string) ( $params['date'] ?? '' );
		if ( '' === $name || '' === $date ) {
			return null;
		}
		$body   = [
			'name' => $name,
			'date' => $date,
			'year' => (int) gmdate( 'Y', $now ),
		];
		$path   = self::numerology_path( (string) ( $params['system'] ?? '' ) );
		$lang   = Plugin::resolve_lang( (string) ( $params['lang'] ?? '' ) );
		$client = new ApiClient( $visitor ? '' : null );
		if ( $visitor ) {
			// A visitor's name and birth date are not kept, as the readme promises.
			$response = $client->call( 'POST', $path, $body, $lang );
			$data     = 200 === (int) ( $response['status'] ?? 0 ) ? ( $response['data']['data'] ?? null ) : null;
		} else {
			$data = $client->cached_call( 'POST', $path, $body, PublicData::ttl_for( 'static' ), 'num_', $lang );
		}
		return is_array( $data ) && is_array( $data['lifePath'] ?? null ) ? $data : null;
	}

	/**
	 * The birth moment in the body every chart route reads, or null when a
	 * field is missing. These routes take no `lang`: names are the plugin's.
	 */
	private static function body( array $params ): ?array {
		$date = (string) ( $params['date'] ?? '' );
		$time = (string) ( $params['time'] ?? '' );
		// HH:mm as the cards are written, HH:mm:ss as pages of 1.2.0 were; the api gets one form.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $time ) || ! is_numeric( $params['lat'] ?? null ) || ! is_numeric( $params['lon'] ?? null ) ) {
			return null;
		}
		$body = [
			'date'      => $date,
			'time'      => strlen( $time ) === 5 ? $time . ':00' : $time,
			'latitude'  => (float) $params['lat'],
			'longitude' => (float) $params['lon'],
		];
		// No zone given: the api finds it from the place, summer time included.
		if ( is_numeric( $params['offset'] ?? null ) ) {
			$body['timezoneOffset'] = (float) $params['offset'];
		} else {
			$body['timezone'] = 'auto';
		}
		// Chart options a page of 1.2.0 may carry, already checked by the shortcode.
		return $body + ( is_array( $params['options'] ?? null ) ? $params['options'] : [] );
	}

	/**
	 * The running mahadasha and its nine antardashas, asked for the site's day.
	 * Kept until the antardasha ends, never past the site's midnight, so "now"
	 * on the card moves with the calendar.
	 */
	public static function dasha( array $params, ?int $now = null ): ?array {
		$now  = null === $now ? time() : $now;
		$body = self::body( $params );
		if ( null === $body ) {
			return null;
		}
		$body['targetDate'] = PublicData::site_day( 'day', $now );
		$data               = ( new ApiClient() )->cached_call( 'POST', self::DASHA, $body, PublicData::ttl_for( 'day', $now ), 'das_' );
		return is_array( $data ) && is_array( $data['currentMaha'] ?? null ) && is_array( $data['periods'] ?? null ) ? $data : null;
	}

	/** All nine mahadashas from birth: the same for a birth forever. */
	public static function dasha_timeline( array $params ): ?array {
		$body = self::body( $params );
		if ( null === $body ) {
			return null;
		}
		$data = ( new ApiClient() )->cached_call( 'POST', self::DASHA_MAHA, $body, PublicData::ttl_for( 'static' ), 'das_' );
		return is_array( $data ) && is_array( $data['periods'] ?? null ) ? $data : null;
	}

	public static function navamsa( array $params ): ?array {
		$body = self::body( $params );
		if ( null === $body ) {
			return null;
		}
		$data = ( new ApiClient() )->cached_call( 'POST', self::NAVAMSA, $body, PublicData::ttl_for( 'static' ), 'ved_' );
		return is_array( $data ) && is_array( $data['grahas'] ?? null ) ? $data : null;
	}

	/** The progressed chart for the site's day: the progressed Moon moves about a degree a month. */
	public static function progressions( array $params, ?int $now = null ): ?array {
		$now  = null === $now ? time() : $now;
		$body = self::body( $params );
		if ( null === $body ) {
			return null;
		}
		// A date the author fixed never changes; today's moves at the site's midnight.
		$fixed              = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $params['target'] ?? '' ) );
		$body['targetDate'] = $fixed ? (string) $params['target'] : PublicData::site_day( 'day', $now );
		$data               = ( new ApiClient() )->cached_call( 'POST', self::PROGRESSIONS, $body, PublicData::ttl_for( $fixed ? 'static' : 'day', $now ), 'prg_' );
		return is_array( $data ) && is_array( $data['planets'] ?? null ) ? $data : null;
	}

	public static function harmonics( array $params ): ?array {
		$body     = self::body( $params );
		$harmonic = (int) ( $params['harmonic'] ?? 0 );
		if ( null === $body || $harmonic < 2 || $harmonic > 180 ) {
			return null;
		}
		$body['harmonic'] = $harmonic;
		$data             = ( new ApiClient() )->cached_call( 'POST', self::HARMONICS, $body, PublicData::ttl_for( 'static' ), 'hrm_' );
		return is_array( $data ) && is_array( $data['planets'] ?? null ) ? $data : null;
	}

	/** The bright stars at the birth moment and the natal planets in conjunction with them, within a degree. */
	public static function fixed_stars( array $params ): ?array {
		$body = self::body( $params );
		if ( null === $body ) {
			return null;
		}
		$data = ( new ApiClient() )->cached_call( 'POST', self::FIXED_STARS, $body, PublicData::ttl_for( 'static' ), 'fxs_' );
		return is_array( $data ) && is_array( $data['conjunctions'] ?? null ) ? $data : null;
	}
}
