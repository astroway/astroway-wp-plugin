<?php
/**
 * Stable public API for addon plugins.
 *
 * Addon developers should call only these static methods — internal classes
 * (RendererDecisions, ApiClient, Cache, Admin) may change without notice.
 * This class is BC-locked after v1.0.
 *
 * @since 0.6.4
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AddonAPI {

	/**
	 * Register a widget config from inside an `astroway_widgets` filter callback.
	 * Returns the widgets array with the new entry merged in.
	 *
	 * Example:
	 *   add_filter( 'astroway_widgets', function ( $widgets ) {
	 *       return AddonAPI::register_widget( $widgets, 'synastry_pro', [ ... ] );
	 *   } );
	 *
	 * @param array  $widgets Existing widgets array passed to the filter.
	 * @param string $slug    Widget identifier (snake_case).
	 * @param array  $config  Widget config — must contain embed_path, iframe_attrs, params.
	 * @return array Updated widgets array.
	 */
	public static function register_widget( array $widgets, string $slug, array $config ): array {
		$widgets[ $slug ] = $config;
		return $widgets;
	}

	/**
	 * Returns the user's current tier:
	 *   'anonymous' — no key set
	 *   'free' | 'indie' | 'starter' | 'pro' | 'business' | 'internal' — from /v1/auth/keys/me
	 *
	 * Cached via the existing keys_me transient (30 min TTL).
	 */
	public static function current_tier(): string {
		return Tier::current();
	}

	/**
	 * Base URL of api.astroway.info — `https://api.astroway.info/v1`.
	 */
	public static function api_base(): string {
		return ASTROWAY_API_BASE;
	}

	/**
	 * Whether the site has a saved API key (regardless of validity).
	 */
	public static function has_key(): bool {
		$client = new ApiClient();
		return $client->has_key();
	}

	/**
	 * Build a cache key compatible with the plugin's transient namespace.
	 *
	 * @param string $widget Widget slug.
	 * @param array  $params Param array to hash into the key.
	 */
	public static function cache_key( string $widget, array $params ): string {
		ksort( $params );
		return 'astroway_v1_' . $widget . '_' . md5( wp_json_encode( $params ) );
	}
}
