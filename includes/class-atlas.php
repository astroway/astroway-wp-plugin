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
					'q'     => $q,
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
