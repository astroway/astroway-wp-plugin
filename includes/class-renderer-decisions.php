<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RendererDecisions {

	public static function widgets(): array {
		/**
		 * Filters the widget registry. Addons append their own widget configs here.
		 *
		 * Each widget entry must be an array with keys:
		 *   - embed_path: string (relative path appended to /v1/embed/)
		 *   - iframe_attrs: array with at least 'width' and 'height'
		 *   - params: array of allowed query params
		 *
		 * @since 0.6.3
		 *
		 * @param array $widgets Default widget registry.
		 */
		return apply_filters( 'astroway_widgets', self::widgets_default() );
	}

	private static function widgets_default(): array {
		return [
			'natal'           => [
				'embed_path'   => 'wheel',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '520',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'name', 'tz', 'lang' ],
			],
			'daily_horoscope' => [
				'embed_path'   => 'daily-horoscope',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '320',
				],
				'params'       => [ 'sign', 'lang' ],
			],
			'moon_phase'      => [
				'embed_path'   => 'moon-phase',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '320',
				],
				'params'       => [ 'date', 'lang' ],
			],
			'bodygraph'       => [
				'embed_path'   => 'bodygraph',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '620',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'name', 'tz', 'lang' ],
			],
			'tarot_daily'     => [
				'embed_path'   => 'daily-tarot',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '480',
				],
				'params'       => [ 'deck', 'lang' ],
			],
			// today_in_sky and fortune_cookie are intentionally absent: api-calc
			// has no /v1/embed/today-in-sky or /v1/embed/fortune-cookie route, so
			// both rendered an iframe whose only content was a 401 JSON body.
			// Restore them here once the routes exist api-side.
			'kundli'          => [
				'embed_path'   => 'kundli',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '520',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'tz', 'theme', 'lang' ],
			],
			'transit'         => [
				'embed_path'   => 'transit',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '440',
				],
				'params'       => [ 'date', 'theme', 'lang' ],
			],
			'panchang'        => [
				'embed_path'   => 'panchang',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '420',
				],
				'params'       => [ 'date', 'lat', 'lng', 'tz', 'theme', 'lang' ],
			],
			'numerology'      => [
				'embed_path'   => 'numerology',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '380',
				],
				'params'       => [ 'name', 'date', 'system', 'theme', 'lang' ],
			],
			'synastry'        => [
				'embed_path'   => 'synastry',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '420',
				],
				'params'       => [ 'date_a', 'time_a', 'lat_a', 'lng_a', 'tz_a', 'date_b', 'time_b', 'lat_b', 'lng_b', 'tz_b', 'theme', 'lang' ],
			],
		];
	}

	public static function get( string $widget ): ?array {
		$widgets = self::widgets();
		return $widgets[ $widget ] ?? null;
	}
}
