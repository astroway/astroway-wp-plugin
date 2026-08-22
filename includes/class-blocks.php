<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blocks {

	private const EDITOR_HANDLE = 'astroway-blocks-editor';
	public const TABS_HANDLE    = 'astroway-tabs';

	private static function blocks(): array {
		return [
			'natal-chart'          => [ Shortcodes::class, 'render_natal' ],
			'daily-horoscope'      => [ Shortcodes::class, 'render_daily_horoscope' ],
			'weekly-horoscope'     => [ Shortcodes::class, 'render_weekly_horoscope' ],
			'monthly-horoscope'    => [ Shortcodes::class, 'render_monthly_horoscope' ],
			'planet-of-day'        => [ Shortcodes::class, 'render_planet_of_day' ],
			'moon-phase'           => [ Shortcodes::class, 'render_moon_phase' ],
			'bodygraph'            => [ Shortcodes::class, 'render_bodygraph' ],
			'daily-tarot'          => [ Shortcodes::class, 'render_tarot_card' ],
			'kundli'               => [ Shortcodes::class, 'render_kundli' ],
			'transit'              => [ Shortcodes::class, 'render_transit' ],
			'panchang'             => [ Shortcodes::class, 'render_panchang' ],
			'numerology'           => [ Shortcodes::class, 'render_numerology' ],
			'synastry'             => [ Shortcodes::class, 'render_synastry' ],
			'mini-chart'           => [ Shortcodes::class, 'render_mini_chart' ],
			'monthly-forecast'     => [ Shortcodes::class, 'render_monthly_forecast' ],
			'transit-timeline'     => [ Shortcodes::class, 'render_transit_timeline' ],
			'moon-sign'            => [ Shortcodes::class, 'render_moon_sign' ],
			'rising-sign'          => [ Shortcodes::class, 'render_rising_sign' ],
			'yearly-horoscope'     => [ Shortcodes::class, 'render_yearly_horoscope' ],
			'zodiac-compatibility' => [ Shortcodes::class, 'render_zodiac_compatibility' ],
			'chinese-zodiac'       => [ Shortcodes::class, 'render_chinese_zodiac' ],
			'retrograde'           => [ Shortcodes::class, 'render_retrograde' ],
			'retrogrades'          => [ Shortcodes::class, 'render_retrogrades' ],
			'moon-voc'             => [ Shortcodes::class, 'render_moon_voc' ],
			'planetary-hours'      => [ Shortcodes::class, 'render_planetary_hours' ],
			'astrology-section'    => [ __CLASS__, 'render_section' ],
		];
	}

	/**
	 * The container block: it renders its children and nothing of its own.
	 *
	 * Its whole job is `providesContext`, which WordPress hands to every
	 * descendant that asks for it. That is why the sign lives on the wrapper and
	 * not repeated on four horoscope blocks that are all about the same person.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $atts    Block attributes.
	 * @param string $content Inner blocks, already rendered.
	 */
	public static function render_section( $atts, string $content = '' ): string {
		if ( '' === trim( $content ) ) {
			return '';
		}
		$sign  = Shortcodes::sanitize_sign( $atts['sign'] ?? '' );
		$class = 'astroway-section';
		if ( '' !== $sign ) {
			$class .= ' astroway-section--' . $sign;
		}

		// Tabs are an enhancement over this markup, never a replacement for it.
		// The cards are all rendered, in order, and the script folds them into a
		// tablist once it runs. With JavaScript off the page is the stack it has
		// always been, which is also what a crawler reads.
		$tabs = 'tabs' === ( $atts['layout'] ?? 'stack' );
		if ( $tabs ) {
			$class .= ' astroway-section--tabs';
			wp_enqueue_script( self::TABS_HANDLE );
		}

		return sprintf(
			'<div class="%1$s"%2$s>%3$s</div>',
			esc_attr( $class ),
			$tabs ? ' data-astroway-tabs' : '',
			$content
		);
	}

	/**
	 * Map block slug → feature name for tier-gating.
	 */
	private static function feature_for( string $slug ): string {
		$map = [
			'natal-chart'          => 'natal',
			'daily-horoscope'      => 'daily_horoscope',
			'weekly-horoscope'     => 'weekly_horoscope',
			'monthly-horoscope'    => 'monthly_horoscope',
			'planet-of-day'        => 'planet_of_day',
			'moon-phase'           => 'moon_phase',
			'bodygraph'            => 'bodygraph',
			'daily-tarot'          => 'daily_tarot',
			'mini-chart'           => 'mini_chart',
			'monthly-forecast'     => 'monthly_forecast',
			'transit-timeline'     => 'transit_timeline',
			'moon-sign'            => 'moon_sign',
			'rising-sign'          => 'rising_sign',
			'retrograde'           => 'retrograde',
			'retrogrades'          => 'retrogrades',
			'moon-voc'             => 'moon_voc',
			'planetary-hours'      => 'planetary_hours',
			'yearly-horoscope'     => 'yearly_horoscope',
			'zodiac-compatibility' => 'zodiac_compatibility',
			'chinese-zodiac'       => 'chinese_zodiac',
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
		return static function ( $atts, $content = '', $block = null ) use ( $feature, $callback ) {
			if ( ! Tier::can( $feature ) ) {
				return Tier::render_upgrade_cta( $feature );
			}
			return call_user_func( $callback, self::with_context( (array) $atts, $block ), (string) $content );
		};
	}

	/**
	 * Attributes with the section's context filled in where the block left a
	 * blank.
	 *
	 * The block's own attribute wins: an author who set a sign on one horoscope
	 * inside a Leo section meant that one to differ, and silently overriding it
	 * would make the container impossible to opt out of.
	 *
	 * @since 1.1.0
	 *
	 * @param array $atts  Block attributes.
	 * @param mixed $block WP_Block instance, or null outside the block renderer.
	 */
	private static function with_context( array $atts, $block ): array {
		$context = ( is_object( $block ) && isset( $block->context ) && is_array( $block->context ) ) ? $block->context : [];
		foreach ( [ 'sign', 'lang' ] as $name ) {
			$inherited = trim( (string) ( $context[ 'astroway/' . $name ] ?? '' ) );
			if ( '' !== $inherited && '' === trim( (string) ( $atts[ $name ] ?? '' ) ) ) {
				$atts[ $name ] = $inherited;
			}
		}
		return $atts;
	}

	/** A title for a legacy block name, so the editor has something to show. */
	private static function legacy_title( string $name ): string {
		return 'AstroWay: ' . ucwords( str_replace( '-', ' ', substr( $name, strlen( 'astroway/' ) ) ) );
	}

	public static function register(): void {
		add_action( 'init', [ __CLASS__, 'register_assets_and_blocks' ] );
		add_filter( 'block_categories_all', [ __CLASS__, 'add_category' ] );
	}

	/**
	 * A category of its own for the generated blocks.
	 *
	 * There are seven hundred of them against nineteen hand-built ones, and in
	 * one list the nineteen would be unfindable. The hand-built ones stay under
	 * Widgets where they have always been.
	 *
	 * @since 1.2.0
	 *
	 * @param array $categories Registered block categories.
	 */
	public static function add_category( $categories ) {
		$categories   = is_array( $categories ) ? $categories : [];
		$categories[] = [
			'slug'  => 'astroway-generated',
			'title' => __( 'AstroWay: full API', 'astroway' ),
			'icon'  => null,
		];
		return $categories;
	}

	/**
	 * Block names 1.2.0 generated for endpoints a hand-built card has since
	 * taken over under a different name.
	 *
	 * The generator no longer emits them, so without this a page built on
	 * `astroway/horoscope-yearly` in 1.2.0 would render nothing at all after the
	 * upgrade. They render the same card as their replacement and stay out of
	 * the inserter, which is where the replacement belongs.
	 *
	 * @since 1.4.0
	 */
	private const LEGACY_BLOCKS = [
		'astroway/horoscope-yearly'        => [ Shortcodes::class, 'render_yearly_horoscope' ],
		'astroway/horoscope-compatibility' => [ Shortcodes::class, 'render_zodiac_compatibility' ],
		'astroway/chinese-zodiac-animal'   => [ Shortcodes::class, 'render_chinese_zodiac' ],
	];

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

		// Registered, not enqueued: only a section that asked for tabs pulls it
		// in, from its own render callback, so a page without one ships no
		// JavaScript at all.
		wp_register_script(
			self::TABS_HANDLE,
			ASTROWAY_WP_PLUGIN_URL . 'assets/js/astroway-tabs.js',
			[],
			ASTROWAY_WP_PLUGIN_VERSION,
			true
		);

		foreach ( self::blocks() as $slug => $callback ) {
			$block_dir = ASTROWAY_WP_PLUGIN_DIR . 'blocks/' . $slug;
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type(
					$block_dir,
					[ 'render_callback' => self::gated( self::feature_for( $slug ), $callback ) ]
				);
			}
		}

		foreach ( self::LEGACY_BLOCKS as $name => $callback ) {
			if ( \WP_Block_Type_Registry::get_instance()->is_registered( $name ) ) {
				continue;
			}
			register_block_type(
				$name,
				[
					'api_version'     => 3,
					'title'           => self::legacy_title( $name ),
					'category'        => 'widgets',
					'supports'        => [ 'inserter' => false ],
					'attributes'      => [
						'sign'            => [ 'type' => 'string' ],
						'sign1'           => [ 'type' => 'string' ],
						'sign2'           => [ 'type' => 'string' ],
						'date'            => [ 'type' => 'string' ],
						'time'            => [ 'type' => 'string' ],
						'timezone_offset' => [ 'type' => 'string' ],
						'solar_year'      => [ 'type' => 'string' ],
						'language'        => [ 'type' => 'string' ],
						'lang'            => [ 'type' => 'string' ],
					],
					'render_callback' => static function ( $atts ) use ( $callback ) {
						return call_user_func( $callback, (array) $atts );
					},
				]
			);
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
