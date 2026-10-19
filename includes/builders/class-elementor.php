<?php
namespace AstroWay\WPPlugin\Builders;

use AstroWay\WPPlugin\WidgetManifest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every widget as an Elementor element.
 *
 * Hooks only: without Elementor they never fire, and the widget class, which
 * extends Elementor's, is never loaded.
 *
 * @since 2.5.0
 */
final class Elementor {

	/** Names the AstroWay for Elementor addon gave its widgets; pages built on it keep working. */
	public const LEGACY = [
		'natal-chart'     => 'astroway-natal',
		'daily-tarot'     => 'astroway-tarot-card',
		'daily-horoscope' => 'astroway-daily-horoscope',
		'moon-phase'      => 'astroway-moon-phase',
		'bodygraph'       => 'astroway-bodygraph',
	];

	public static function register(): void {
		add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
		add_action( 'admin_notices', [ __CLASS__, 'addon_notice' ] );
	}

	public static function name_for( string $slug ): string {
		return self::LEGACY[ $slug ] ?? 'astroway-' . $slug;
	}

	/**
	 * Whether the old AstroWay for Elementor addon is running.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $active Whether the addon's loader class is defined.
	 */
	public static function addon_active(): bool {
		return (bool) apply_filters( 'astroway_elementor_addon_active', class_exists( '\AstroWay\Elementor\Loader' ) );
	}

	/** @param object $elements Elementor's elements manager. */
	public static function register_category( $elements ): void {
		if ( self::addon_active() || ! is_object( $elements ) || ! method_exists( $elements, 'add_category' ) ) {
			return;
		}
		$elements->add_category(
			'astroway',
			[
				'title' => __( 'AstroWay', 'astroway' ),
				'icon'  => 'eicon-star',
			]
		);
	}

	/** @param object $manager Elementor's widgets manager. */
	public static function register_widgets( $manager ): void {
		// Two widgets under one name break the Elementor editor.
		if ( self::addon_active() || ! class_exists( '\Elementor\Widget_Base' ) || ! is_object( $manager ) || ! method_exists( $manager, 'register' ) ) {
			return;
		}
		require_once __DIR__ . '/class-elementor-widget.php';
		foreach ( array_keys( WidgetManifest::widgets() ) as $slug ) {
			$manager->register( new ElementorWidget( [], [ 'astroway_slug' => $slug ] ) );
		}
	}

	public static function addon_notice(): void {
		if ( ! self::addon_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__( 'AstroWay now has every widget in Elementor itself. The AstroWay for Elementor addon is no longer needed: deactivate it and your pages keep their widgets.', 'astroway' )
		);
	}
}
