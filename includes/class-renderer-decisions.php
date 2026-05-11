<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RendererDecisions {

	public static function widgets(): array {
		return [
			'natal' => [
				'embed_path'   => 'wheel',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '520',
				],
				'params'       => [ 'date', 'time', 'lat', 'lon', 'name', 'tz' ],
			],
			'daily_horoscope' => [
				'embed_path'   => 'daily-horoscope',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '320',
				],
				'params'       => [ 'sign' ],
			],
			'moon_phase' => [
				'embed_path'   => 'moon-phase',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '320',
				],
				'params'       => [ 'date' ],
			],
			'bodygraph' => [
				'embed_path'   => 'bodygraph',
				'iframe_attrs' => [
					'width'  => '460',
					'height' => '620',
				],
				'params'       => [ 'date', 'time', 'lat', 'lon', 'name', 'tz' ],
			],
			'tarot_daily' => [
				'embed_path'   => 'daily-tarot',
				'iframe_attrs' => [
					'width'  => '320',
					'height' => '480',
				],
				'params'       => [ 'deck' ],
			],
		];
	}

	public static function get( string $widget ): ?array {
		$widgets = self::widgets();
		return $widgets[ $widget ] ?? null;
	}
}
