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

		$allowed = array_intersect_key( $params, array_flip( $config['params'] ) );
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
