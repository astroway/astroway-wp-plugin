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
			// Heights are the fallback for a page without the embed script, which
			// otherwise sizes each frame to what it reports. Set to the content at
			// the full 460px width, so nothing is cut: 520 cut the wheel's last 17px.
			'natal'            => [
				'embed_path'   => 'wheel',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '540',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'name', 'tz', 'lang', 'theme' ],
			],
			'daily_horoscope'  => [
				'embed_path'   => 'daily-horoscope',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '320',
				],
				'params'       => [ 'sign', 'lang', 'theme' ],
			],
			// Registered so "In an iframe" does not simply delete this widget from
			// the page. Weekly and monthly horoscope get no entry on purpose: the
			// embed router has no route for them, and a frame pointed at a missing
			// route renders a 401 body rather than a fallback.
			'planet_of_day'    => [
				'embed_path'   => 'planet-of-day',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '320',
				],
				'params'       => [ 'date', 'lang', 'theme' ],
			],
			'moon_phase'       => [
				'embed_path'   => 'moon-phase',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '320',
				],
				'params'       => [ 'date', 'lang', 'theme' ],
			],
			'bodygraph'        => [
				'embed_path'   => 'bodygraph',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '620',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'name', 'tz', 'lang', 'theme' ],
			],
			'tarot_daily'      => [
				'embed_path'   => 'daily-tarot',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '480',
				],
				'params'       => [ 'deck', 'lang', 'theme' ],
			],
			// today_in_sky and fortune_cookie are intentionally absent: api-calc
			// has no /v1/embed/today-in-sky or /v1/embed/fortune-cookie route, so
			// both rendered an iframe whose only content was a 401 JSON body.
			// Restore them here once the routes exist api-side.
			'kundli'           => [
				'embed_path'   => 'kundli',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '530',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'tz', 'theme', 'lang' ],
			],
			'transit'          => [
				'embed_path'   => 'transit',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '440',
				],
				'params'       => [ 'date', 'theme', 'lang' ],
			],
			'panchang'         => [
				'embed_path'   => 'panchang',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '420',
				],
				'params'       => [ 'date', 'lat', 'lng', 'tz', 'theme', 'lang' ],
			],
			'numerology'       => [
				'embed_path'   => 'numerology',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '380',
				],
				'params'       => [ 'name', 'date', 'system', 'theme', 'lang' ],
			],
			'synastry'         => [
				'embed_path'   => 'synastry',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '420',
				],
				'params'       => [ 'date_a', 'time_a', 'lat_a', 'lng_a', 'tz_a', 'date_b', 'time_b', 'lat_b', 'lng_b', 'tz_b', 'theme', 'lang' ],
			],
			// The last three embed routes the api has and the plugin did not expose.
			// Parameters verified against live responses, not against the OpenAPI
			// spec, which documents none for any of them.
			'mini_chart'       => [
				'embed_path'   => 'mini-chart',
				'iframe_attrs' => [
					'width'  => '300',
					'height' => '300',
				],
				'params'       => [ 'date', 'time', 'lat', 'lng', 'tz', 'theme', 'lang' ],
			],
			'monthly_forecast' => [
				'embed_path'   => 'monthly-forecast',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '420',
				],
				'params'       => [ 'sign', 'date', 'theme', 'lang' ],
			],
			'transit_timeline' => [
				'embed_path'   => 'transit-timeline',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '380',
				],
				'params'       => [ 'date', 'theme', 'lang' ],
			],
		];
	}

	public static function get( string $widget ): ?array {
		$widgets = self::widgets();
		return $widgets[ $widget ] ?? null;
	}
}
