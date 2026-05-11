<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cache {

	public const PREFIX = 'astroway_v1_';

	public const TTL_CHART     = HOUR_IN_SECONDS;
	public const TTL_MOON      = DAY_IN_SECONDS;
	public const TTL_REFERENCE = WEEK_IN_SECONDS;
	public const TTL_KEYS_ME   = 30 * MINUTE_IN_SECONDS;

	public static function get( string $key ) {
		return get_transient( self::PREFIX . $key );
	}

	public static function set( string $key, $value, int $ttl = self::TTL_CHART ): bool {
		return set_transient( self::PREFIX . $key, $value, $ttl );
	}

	public static function delete( string $key ): bool {
		return delete_transient( self::PREFIX . $key );
	}

	public static function purge_all(): int {
		global $wpdb;
		$like_value   = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		$like_timeout = $wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%';
		$count        = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_value,
				$like_timeout
			)
		);
		// Each transient = 2 rows (value + timeout)
		return intdiv( $count, 2 );
	}

	public static function stats(): array {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT LENGTH(option_value) AS sz FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like
			)
		);
		return [
			'count' => is_array( $rows ) ? count( $rows ) : 0,
			'bytes' => is_array( $rows ) ? (int) array_sum( array_column( $rows, 'sz' ) ) : 0,
		];
	}
}
