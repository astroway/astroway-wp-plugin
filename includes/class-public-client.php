<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PublicClient {

	public static function embed_url( string $widget, array $params = [] ): ?string {
		$config = RendererDecisions::get( $widget );
		if ( ! $config ) {
			return null;
		}

		$allowed  = array_intersect_key( $params, array_flip( $config['params'] ) );
		$filtered = array_filter(
			$allowed,
			static function ( $v ) {
				return null !== $v && '' !== $v;
			}
		);

		// Opt-in legal disclaimer in the widget footer (Settings → AstroWay).
		// The api renders it when disclaimer=1; off by default to protect conversion.
		$opts = (array) get_option( Admin::OPTION_KEY, [] );
		if ( ! empty( $opts['widget_disclaimer'] ) ) {
			$filtered['disclaimer'] = '1';
		}

		$base = ASTROWAY_API_BASE . '/embed/' . $config['embed_path'];
		return empty( $filtered ) ? $base : add_query_arg( $filtered, $base );
	}

	public static function embed_iframe( string $widget, array $params = [], array $overrides = [] ): string {
		$config = RendererDecisions::get( $widget );
		if ( ! $config ) {
			return '';
		}

		$url = self::embed_url( $widget, $params );
		if ( ! $url ) {
			return '';
		}

		// No probe here on purpose. The probe measures this server's IP quota,
		// but the iframe is fetched by the visitor's browser against the
		// visitor's own quota. Suppressing the render on a server-side signal
		// blanked widgets for every visitor whose quota was untouched.
		$attrs       = array_merge( $config['iframe_attrs'], $overrides );
		$widget_slug = str_replace( '_', '-', $widget );

		$iframe = sprintf(
			'<iframe src="%s" width="%s" height="%s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" frameborder="0" scrolling="no" title="%s" class="astroway-embed__iframe"></iframe>',
			esc_url( $url ),
			esc_attr( (string) $attrs['width'] ),
			esc_attr( (string) $attrs['height'] ),
			esc_attr( self::iframe_title( $widget ) )
		);

		return sprintf(
			'<div class="astroway-embed astroway-embed--%s">%s</div>',
			esc_attr( $widget_slug ),
			$iframe
		);
	}

	/**
	 * Probe the public embed endpoint to learn whether the anonymous tier is
	 * exhausted (x-ratelimit-remaining < 3 OR HTTP 429). Skipped entirely when
	 * a paid API key is configured (Tier::current() != 'anonymous').
	 *
	 * Cached for 5 min in a transient so we don't add an api hit per call.
	 * Reflects the WP server's own IP quota, not visitors', so it may only
	 * drive the admin notice. Front-end rendering must never depend on it.
	 * Called from the admin notice path only, which is why the front end no
	 * longer spends up to 12 requests per hour of the server quota on probes.
	 */
	public static function rate_limit_active(): bool {
		// Skip the probe for paid tiers (Tier class arrives in v0.7.0). When the
		// class isn't loaded yet — pre-v0.7.0 ZIPs — we still probe everyone,
		// which is fine because paid tiers have rate caps high enough that
		// `remaining < 3` essentially never trips.
		if ( class_exists( __NAMESPACE__ . '\\Tier' ) && 'anonymous' !== Tier::current() ) {
			return false;
		}

		$cached = get_transient( 'astroway_rate_limit_probe' );
		if ( false !== $cached ) {
			return 'limited' === $cached;
		}

		$probe_url = ASTROWAY_API_BASE . '/embed/wheel?date=2000-01-01&time=12:00&lat=0&lon=0';
		$res       = wp_remote_head( $probe_url, [ 'timeout' => 3 ] );

		if ( is_wp_error( $res ) ) {
			set_transient( 'astroway_rate_limit_probe', 'ok', 5 * MINUTE_IN_SECONDS );
			return false;
		}

		$code      = (int) wp_remote_retrieve_response_code( $res );
		$remaining = wp_remote_retrieve_header( $res, 'x-ratelimit-remaining' );
		$remaining = '' === $remaining ? null : (int) $remaining;

		$limited = ( 429 === $code ) || ( null !== $remaining && $remaining < 3 );
		set_transient( 'astroway_rate_limit_probe', $limited ? 'limited' : 'ok', 5 * MINUTE_IN_SECONDS );

		if ( $limited ) {
			update_option( 'astroway_rate_limit_hit_at', time(), false );
		}

		return $limited;
	}

	private static function iframe_title( string $widget ): string {
		$titles = [
			'natal'           => __( 'Natal chart wheel', 'astroway' ),
			'daily_horoscope' => __( 'Daily horoscope', 'astroway' ),
			'moon_phase'      => __( 'Moon phase', 'astroway' ),
			'bodygraph'       => __( 'Human Design bodygraph', 'astroway' ),
			'tarot_daily'     => __( 'Daily Tarot card', 'astroway' ),
			'kundli'          => __( 'Vedic kundli chart', 'astroway' ),
			'transit'         => __( 'Transit sky snapshot', 'astroway' ),
			'panchang'        => __( 'Daily Panchang', 'astroway' ),
			'numerology'      => __( 'Numerology core numbers', 'astroway' ),
			'synastry'        => __( 'Synastry compatibility', 'astroway' ),
		];
		return $titles[ $widget ] ?? __( 'AstroWay widget', 'astroway' );
	}
}
