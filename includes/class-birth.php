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
}
