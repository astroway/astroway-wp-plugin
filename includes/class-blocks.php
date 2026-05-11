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
			'tarot-daily'     => [ Shortcodes::class, 'render_tarot_card' ],
		];
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
				'astroway-wp-plugin',
				ASTROWAY_WP_PLUGIN_DIR . 'languages'
			);
		}

		foreach ( self::blocks() as $slug => $callback ) {
			$block_dir = ASTROWAY_WP_PLUGIN_DIR . 'blocks/' . $slug;
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type(
					$block_dir,
					[ 'render_callback' => $callback ]
				);
			}
		}
	}
}
