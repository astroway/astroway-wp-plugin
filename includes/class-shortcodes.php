<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public static function register(): void {
		add_shortcode( 'astroway_natal', [ __CLASS__, 'render_natal' ] );
		add_shortcode( 'astroway_daily_horoscope', [ __CLASS__, 'render_daily_horoscope' ] );
		add_shortcode( 'astroway_moon_phase', [ __CLASS__, 'render_moon_phase' ] );
		add_shortcode( 'astroway_bodygraph', [ __CLASS__, 'render_bodygraph' ] );
		add_shortcode( 'astroway_tarot_card', [ __CLASS__, 'render_tarot_card' ] );
	}

	public static function render_natal( $atts ): string {
		$atts = shortcode_atts(
			[
				'date' => '',
				'time' => '',
				'lat'  => '',
				'lon'  => '',
				'name' => '',
				'tz'   => '',
			],
			(array) $atts,
			'astroway_natal'
		);
		return PublicClient::embed_iframe( 'natal', self::sanitize_chart_params( $atts ) );
	}

	public static function render_daily_horoscope( $atts ): string {
		$atts = shortcode_atts( [ 'sign' => '' ], (array) $atts, 'astroway_daily_horoscope' );
		$sign = self::sanitize_sign( $atts['sign'] );
		return PublicClient::embed_iframe( 'daily_horoscope', [ 'sign' => $sign ] );
	}

	public static function render_moon_phase( $atts ): string {
		$atts = shortcode_atts( [ 'date' => '' ], (array) $atts, 'astroway_moon_phase' );
		$date = self::sanitize_date( $atts['date'] );
		return PublicClient::embed_iframe( 'moon_phase', [ 'date' => $date ] );
	}

	public static function render_bodygraph( $atts ): string {
		$atts = shortcode_atts(
			[
				'date' => '',
				'time' => '',
				'lat'  => '',
				'lon'  => '',
				'name' => '',
				'tz'   => '',
			],
			(array) $atts,
			'astroway_bodygraph'
		);
		return PublicClient::embed_iframe( 'bodygraph', self::sanitize_chart_params( $atts ) );
	}

	public static function render_tarot_card( $atts ): string {
		$atts = shortcode_atts(
			[
				'type' => 'daily',
				'deck' => 'rider-waite',
			],
			(array) $atts,
			'astroway_tarot_card'
		);
		$deck = self::sanitize_deck( $atts['deck'] );
		return PublicClient::embed_iframe( 'tarot_daily', [ 'deck' => $deck ] );
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

	private static function sanitize_date( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	private static function sanitize_time( $value ): string {
		$value = trim( (string) $value );
		return preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $value ) ? $value : '';
	}

	private static function sanitize_coord( $value, float $min, float $max ): string {
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

	private static function sanitize_tz( $value ): string {
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

	private static function sanitize_sign( $value ): string {
		$sign  = strtolower( sanitize_key( (string) $value ) );
		$valid = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
		return in_array( $sign, $valid, true ) ? $sign : '';
	}

	private static function sanitize_deck( $value ): string {
		$deck  = sanitize_key( (string) $value );
		$valid = [ 'rider-waite', 'marseille', 'lenormand' ];
		return in_array( $deck, $valid, true ) ? $deck : 'rider-waite';
	}
}
