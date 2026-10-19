<?php
namespace AstroWay\WPPlugin\Builders;

use AstroWay\WPPlugin\WidgetManifest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every widget as a Beaver Builder module, Lite included.
 *
 * @since 2.5.0
 */
final class Beaver {

	public static function register(): void {
		add_action( 'init', [ __CLASS__, 'register_modules' ], 20 );
	}

	public static function register_modules(): void {
		// Older versions rebuild a module with `new $class()`, which would lose the slug.
		if ( ! class_exists( '\FLBuilder' ) || ! class_exists( '\FLBuilderModule' ) || ! property_exists( '\FLBuilderModule', 'clone_instance' ) ) {
			return;
		}
		require_once __DIR__ . '/class-beaver-module.php';
		foreach ( array_keys( WidgetManifest::widgets() ) as $slug ) {
			\FLBuilder::register_module( new BeaverModule( $slug ), self::form( $slug ) );
		}
	}

	/** The module's settings form, in Beaver Builder's tab, section, fields shape. */
	public static function form( string $slug ): array {
		$spec   = WidgetManifest::widget( $slug );
		$fields = [];
		foreach ( (array) ( $spec['fields'] ?? [] ) as $f ) {
			$field = [
				'type'    => $f['type'],
				'label'   => $f['label'],
				'default' => '',
			];
			if ( 'select' === $f['type'] ) {
				$field['options'] = $f['options'];
				$field['default'] = (string) array_key_first( $f['options'] );
			}
			$fields[ $f['name'] ] = $field;
		}
		return [
			'general' => [
				'title'    => __( 'Settings', 'astroway' ),
				'sections' => [
					'astroway' => [
						'title'  => (string) ( $spec['panel'] ?? '' ),
						'fields' => $fields,
					],
				],
			],
		];
	}
}
