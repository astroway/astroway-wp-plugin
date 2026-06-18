<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public static function register(): void {
		add_shortcode( 'astroway_natal', self::gated( 'natal', [ __CLASS__, 'render_natal' ] ) );
		add_shortcode( 'astroway_daily_horoscope', self::gated( 'daily_horoscope', [ __CLASS__, 'render_daily_horoscope' ] ) );
		add_shortcode( 'astroway_moon_phase', self::gated( 'moon_phase', [ __CLASS__, 'render_moon_phase' ] ) );
		add_shortcode( 'astroway_bodygraph', self::gated( 'bodygraph', [ __CLASS__, 'render_bodygraph' ] ) );
		add_shortcode( 'astroway_tarot_card', self::gated( 'daily_tarot', [ __CLASS__, 'render_tarot_card' ] ) );

		/**
		 * Fires after core shortcodes are registered.
		 * Addons should hook here to call add_shortcode() for their own astroway_* shortcodes.
		 *
		 * @since 0.6.1
		 */
		do_action( 'astroway_register_shortcodes' );
	}

	/**
	 * Wrap a shortcode callback with a Tier::can() gate. v0.7.4 swaps the
	 * inline CTA for Tier::render_upgrade_cta() helper.
	 *
	 * @since 0.7.3
	 */
	private static function gated( string $feature, callable $callback ): callable {
		return static function ( $atts ) use ( $feature, $callback ) {
			if ( ! Tier::can( $feature ) ) {
				return Tier::render_upgrade_cta( $feature );
			}
			return call_user_func( $callback, $atts );
		};
	}

	public static function render_natal( $atts ): string {
		$atts           = shortcode_atts(
			[
				'date' => '',
				'time' => '',
				'lat'  => '',
				'lon'  => '',
				'name' => '',
				'tz'   => '',
				'lang' => '',
			],
			(array) $atts,
			'astroway_natal'
		);
		$params         = self::sanitize_chart_params( $atts );
		$params['lang'] = self::resolve_lang( $atts['lang'] );
		return PublicClient::embed_iframe( 'natal', $params );
	}

	public static function render_daily_horoscope( $atts ): string {
		$atts = shortcode_atts(
			[
				'sign' => '',
				'lang' => '',
			],
			(array) $atts,
			'astroway_daily_horoscope'
		);
		return PublicClient::embed_iframe(
			'daily_horoscope',
			[
				'sign' => self::sanitize_sign( $atts['sign'] ),
				'lang' => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	public static function render_moon_phase( $atts ): string {
		$atts = shortcode_atts(
			[
				'date' => '',
				'lang' => '',
			],
			(array) $atts,
			'astroway_moon_phase'
		);
		return PublicClient::embed_iframe(
			'moon_phase',
			[
				'date' => self::sanitize_date( $atts['date'] ),
				'lang' => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	public static function render_bodygraph( $atts ): string {
		$atts           = shortcode_atts(
			[
				'date' => '',
				'time' => '',
				'lat'  => '',
				'lon'  => '',
				'name' => '',
				'tz'   => '',
				'lang' => '',
			],
			(array) $atts,
			'astroway_bodygraph'
		);
		$params         = self::sanitize_chart_params( $atts );
		$params['lang'] = self::resolve_lang( $atts['lang'] );
		return PublicClient::embed_iframe( 'bodygraph', $params );
	}

	public static function render_tarot_card( $atts ): string {
		$atts = shortcode_atts(
			[
				'type' => 'daily',
				'deck' => 'rider-waite',
				'lang' => '',
			],
			(array) $atts,
			'astroway_tarot_card'
		);
		return PublicClient::embed_iframe(
			'tarot_daily',
			[
				'deck' => self::sanitize_deck( $atts['deck'] ),
				'lang' => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Resolve the language for an api request:
	 *   1. Explicit shortcode/block param (must be in Plugin::SUPPORTED_LANGS)
	 *   2. Fallback to the site locale normalised to a 2-letter code
	 *
	 * Invalid codes silently fall through to the site-locale fallback —
	 * no user-facing error for typos.
	 */
	private static function resolve_lang( $raw ): string {
		$raw = strtolower( trim( (string) $raw ) );
		if ( '' !== $raw && in_array( $raw, Plugin::SUPPORTED_LANGS, true ) ) {
			return $raw;
		}
		return Plugin::normalize_locale( get_locale() );
	}

	private static function sanitize_chart_params( array $atts ): array {
		return [
			'date' => self::sanitize_date( $atts['date'] ),
			'time' => self::sanitize_time( $atts['time'] ),
			'lat'  => self::sanitize_coord( $atts['lat'], -90, 90 ),
			'lon'  => self::sanitize_coord( $atts['lon'], -180, 180 ),
			'name' => sanitize_text_field( $atts['name'] ),
			'tz'   => self::sanitize_tz( $atts['tz'] ),
		];
	}

	public static function sanitize_date( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	public static function sanitize_time( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $value ) ? $value : '';
	}

	public static function sanitize_coord( $value, float $min, float $max ): string {
		$value = trim( (string) $value );
		if ( ! is_numeric( $value ) ) {
			return '';
		}
		$float = (float) $value;
		if ( $float < $min || $float > $max ) {
			return '';
		}
		return (string) $float;
	}

	public static function sanitize_tz( $value ): string {
		$value = trim( (string) $value );
		// Accept IANA names (Europe/Kyiv) or fixed offsets (+03:00, -05:30)
		if ( preg_match( '#^[A-Za-z]+(?:/[A-Za-z_]+)+$#', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^[+-]\d{2}:\d{2}$/', $value ) ) {
			return $value;
		}
		return '';
	}

	public static function sanitize_sign( $value ): string {
		$sign  = strtolower( sanitize_key( (string) $value ) );
		$valid = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
		return in_array( $sign, $valid, true ) ? $sign : '';
	}

	public static function sanitize_deck( $value ): string {
		$deck  = sanitize_key( (string) $value );
		$valid = [ 'rider-waite', 'marseille', 'lenormand' ];
		return in_array( $deck, $valid, true ) ? $deck : 'rider-waite';
	}
}
