<?php
namespace AstroWay\WPPlugin\Builders;

use AstroWay\WPPlugin\WidgetManifest;

if ( ! defined( 'ABSPATH' ) || ! class_exists( '\FLBuilderModule' ) ) {
	return;
}

/**
 * One class for all the modules; Beaver Builder clones the registered
 * instance, so each keeps its own slug.
 *
 * @since 2.5.0
 */
class BeaverModule extends \FLBuilderModule {

	/** @var bool */
	public $clone_instance = true;

	/** @var string */
	public $astroway_slug = '';

	public function __construct( string $slug = '' ) {
		$this->astroway_slug = $slug;
		$spec                = WidgetManifest::widget( $slug );
		parent::__construct(
			[
				'name'            => (string) ( $spec['title'] ?? $slug ),
				'description'     => '',
				'category'        => __( 'AstroWay', 'astroway' ),
				'slug'            => 'astroway-' . $slug,
				'dir'             => __DIR__ . '/beaver-module/',
				'url'             => plugins_url( 'beaver-module/', __FILE__ ),
				'icon'            => (string) ( $spec['icon'] ?? '' ),
				'partial_refresh' => true,
			]
		);
	}
}
