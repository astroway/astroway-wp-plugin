<?php
namespace AstroWay\WPPlugin\Builders;

use AstroWay\WPPlugin\WidgetManifest;
use AstroWay\WPPlugin\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every widget in the WPBakery element list.
 *
 * WPBakery stores the element as our shortcode, so rendering and the head
 * stylesheet need nothing from here.
 *
 * @since 2.5.0
 */
final class WpBakery {

	public static function register(): void {
		add_action( 'vc_before_init', [ __CLASS__, 'map' ] );
	}

	public static function map(): void {
		if ( ! function_exists( 'vc_map' ) ) {
			return;
		}
		foreach ( WidgetManifest::widgets() as $slug => $spec ) {
			vc_map(
				[
					'name'     => $spec['title'],
					'base'     => Widgets::tag( $slug ),
					'category' => __( 'AstroWay', 'astroway' ),
					'icon'     => 'dashicons dashicons-' . $spec['icon'],
					'params'   => self::params( $slug ),
				]
			);
		}
	}

	/** Manifest fields as WPBakery params; its dropdowns take label => value. */
	public static function params( string $slug ): array {
		$out = [];
		foreach ( (array) ( WidgetManifest::widget( $slug )['fields'] ?? [] ) as $f ) {
			$param = [
				'param_name' => $f['name'],
				'heading'    => $f['label'],
				'type'       => 'textfield',
			];
			if ( 'select' === $f['type'] ) {
				$param['type']  = 'dropdown';
				$param['value'] = array_flip( array_map( 'strval', $f['options'] ) );
				$param['std']   = (string) array_key_first( $f['options'] );
			}
			$out[] = $param;
		}
		return $out;
	}
}
