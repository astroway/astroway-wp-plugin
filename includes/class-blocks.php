<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blocks {

	private const EDITOR_HANDLE = 'astroway-blocks-editor';

	private static function blocks(): array {
		return [
			'natal-chart'     => [ Shortcodes::class, 'render_natal' ],
			'daily-horoscope' => [ Shortcodes::class, 'render_daily_horoscope' ],
			'moon-phase'      => [ Shortcodes::class, 'render_moon_phase' ],
			'bodygraph'       => [ Shortcodes::class, 'render_bodygraph' ],
			'daily-tarot'     => [ Shortcodes::class, 'render_tarot_card' ],
		];
	}

	/**
	 * Map block slug → feature name for tier-gating.
	 */
	private static function feature_for( string $slug ): string {
		$map = [
			'natal-chart'     => 'natal',
			'daily-horoscope' => 'daily_horoscope',
			'moon-phase'      => 'moon_phase',
			'bodygraph'       => 'bodygraph',
			'daily-tarot'     => 'daily_tarot',
		];
		return $map[ $slug ] ?? $slug;
	}

	/**
	 * Wrap a block callback with a Tier::can() gate. v0.7.4 swaps the
	 * inline CTA for Tier::render_upgrade_cta() helper.
	 *
	 * @since 0.7.2
	 */
	private static function gated( string $feature, callable $callback ): callable {
		return static function ( $atts ) use ( $feature, $callback ) {
			if ( ! Tier::can( $feature ) ) {
				return Tier::render_upgrade_cta( $feature );
			}
			return call_user_func( $callback, $atts );
		};
	}

	public static function register(): void {
		add_action( 'init', [ __CLASS__, 'register_assets_and_blocks' ] );
	}

	public static function register_assets_and_blocks(): void {
		wp_register_script(
			self::EDITOR_HANDLE,
			ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-blocks-editor.js',
			[ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ],
			ASTROWAY_WP_PLUGIN_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				self::EDITOR_HANDLE,
				'astroway',
				ASTROWAY_WP_PLUGIN_DIR . 'languages'
			);
		}

		foreach ( self::blocks() as $slug => $callback ) {
			$block_dir = ASTROWAY_WP_PLUGIN_DIR . 'blocks/' . $slug;
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type(
					$block_dir,
					[ 'render_callback' => self::gated( self::feature_for( $slug ), $callback ) ]
				);
			}
		}

		/**
		 * Fires after core Gutenberg blocks are registered.
		 * Addons hook here to call register_block_type() for their own astroway/* blocks.
		 *
		 * @since 0.6.2
		 */
		do_action( 'astroway_register_blocks' );
	}
}
