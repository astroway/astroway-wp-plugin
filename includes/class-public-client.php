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

		// Anonymous tier may be over the 30/h per-IP limit — skip rendering ugly
		// JSON error in iframe; admin notice instead points the owner at signup.
		if ( self::rate_limit_active() ) {
			update_option( 'astroway_rate_limit_hit_at', time(), false );
			return '';
		}

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
	 * Cached for 5 min in a transient so we don't add an api hit per shortcode
	 * render. Reflects the WP server's own IP rate limit, not visitors' — the
	 * api side needs to send postMessage on rate-limit for fully accurate
	 * per-visitor hiding.
	 */
	private static function rate_limit_active(): bool {
		if ( Tier::current() !== 'anonymous' ) {
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

		return $limited;
	}

	private static function iframe_title( string $widget ): string {
		$titles = [
			'natal'           => __( 'Natal chart wheel', 'astroway' ),
			'daily_horoscope' => __( 'Daily horoscope', 'astroway' ),
			'moon_phase'      => __( 'Moon phase', 'astroway' ),
			'bodygraph'       => __( 'Human Design bodygraph', 'astroway' ),
			'tarot_daily'     => __( 'Daily Tarot card', 'astroway' ),
		];
		return $titles[ $widget ] ?? __( 'AstroWay widget', 'astroway' );
	}
}
