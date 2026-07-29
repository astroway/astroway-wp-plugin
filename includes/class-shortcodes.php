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
		add_shortcode( 'astroway_today_in_sky', self::gated( 'today_in_sky', [ __CLASS__, 'render_today_in_sky' ] ) );
		add_shortcode( 'astroway_fortune_cookie', self::gated( 'fortune_cookie', [ __CLASS__, 'render_fortune_cookie' ] ) );
		add_shortcode( 'astroway_kundli', self::gated( 'kundli', [ __CLASS__, 'render_kundli' ] ) );
		add_shortcode( 'astroway_transit', self::gated( 'transit', [ __CLASS__, 'render_transit' ] ) );
		add_shortcode( 'astroway_panchang', self::gated( 'panchang', [ __CLASS__, 'render_panchang' ] ) );
		add_shortcode( 'astroway_numerology', self::gated( 'numerology', [ __CLASS__, 'render_numerology' ] ) );
		add_shortcode( 'astroway_synastry', self::gated( 'synastry', [ __CLASS__, 'render_synastry' ] ) );

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
	 * Deprecated until api-calc gains the route. Kept registered so pages that
	 * already contain the shortcode do not print raw shortcode text.
	 */
	public static function render_today_in_sky( $atts ): string {
		unset( $atts );
		return self::unavailable_widget( 'astroway_today_in_sky' );
	}

	/**
	 * A widget whose api route does not exist renders nothing for visitors and
	 * one explanatory line for administrators, never a frame containing a 401.
	 */
	private static function unavailable_widget( string $shortcode ): string {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		return sprintf(
			'<div class="astroway-embed astroway-embed--unavailable"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s = shortcode name */
					__( 'The %s widget is temporarily unavailable, so it renders nothing. Only administrators see this note.', 'astroway' ),
					'[' . $shortcode . ']'
				)
			)
		);
	}

	public static function sanitize_bool_flag( $value ): string {
		return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true ) ? '1' : '0';
	}

	/**
	 * Daily cosmic quote. Deprecated until api-calc gains
	 * /v1/embed/fortune-cookie; the route was never implemented.
	 */
	public static function render_fortune_cookie( $atts ): string {
		unset( $atts );
		return self::unavailable_widget( 'astroway_fortune_cookie' );
	}

	/**
	 * Vedic kundli (North-Indian D1). Single subject; api ignores any style
	 * param and always renders the North-Indian diamond.
	 */
	public static function render_kundli( $atts ): string {
		$atts = shortcode_atts(
			[
				'date'  => '',
				'time'  => '',
				'lat'   => '',
				'lon'   => '',
				'tz'    => '',
				'theme' => '',
				'lang'  => '',
			],
			(array) $atts,
			'astroway_kundli'
		);
		$date = self::sanitize_date( $atts['date'] );
		$time = self::sanitize_time( $atts['time'] );
		return PublicClient::embed_iframe(
			'kundli',
			[
				'date'  => $date,
				'time'  => $time,
				'lat'   => self::sanitize_coord( $atts['lat'], -90, 90 ),
				'lng'   => self::sanitize_coord( $atts['lon'], -180, 180 ),
				'tz'    => self::tz_offset_hours( $atts['tz'], $date, $time ),
				'theme' => self::sanitize_theme( $atts['theme'] ),
				'lang'  => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Sky snapshot for a date — every classic body's sign + degree. No birth
	 * data, just the transiting positions.
	 */
	public static function render_transit( $atts ): string {
		$atts = shortcode_atts(
			[
				'date'  => '',
				'theme' => '',
				'lang'  => '',
			],
			(array) $atts,
			'astroway_transit'
		);
		return PublicClient::embed_iframe(
			'transit',
			[
				'date'  => self::sanitize_date( $atts['date'] ),
				'theme' => self::sanitize_theme( $atts['theme'] ),
				'lang'  => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Daily Panchang (tithi/nakshatra/yoga/karana/vara + rahu-kaal). Needs a
	 * location — the api errors if both lat and lng are empty.
	 */
	public static function render_panchang( $atts ): string {
		$atts = shortcode_atts(
			[
				'date'  => '',
				'lat'   => '',
				'lon'   => '',
				'tz'    => '',
				'theme' => '',
				'lang'  => '',
			],
			(array) $atts,
			'astroway_panchang'
		);
		$date = self::sanitize_date( $atts['date'] );
		return PublicClient::embed_iframe(
			'panchang',
			[
				'date'  => $date,
				'lat'   => self::sanitize_coord( $atts['lat'], -90, 90 ),
				'lng'   => self::sanitize_coord( $atts['lon'], -180, 180 ),
				'tz'    => self::tz_offset_hours( $atts['tz'], $date, '12:00' ),
				'theme' => self::sanitize_theme( $atts['theme'] ),
				'lang'  => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Numerology core numbers (Life Path / Expression / Soul Urge / Personality).
	 * api needs a name and a valid date or it returns an error card.
	 */
	public static function render_numerology( $atts ): string {
		$atts = shortcode_atts(
			[
				'name'   => '',
				'date'   => '',
				'system' => 'pythagorean',
				'theme'  => '',
				'lang'   => '',
			],
			(array) $atts,
			'astroway_numerology'
		);
		return PublicClient::embed_iframe(
			'numerology',
			[
				'name'   => sanitize_text_field( $atts['name'] ),
				'date'   => self::sanitize_date( $atts['date'] ),
				'system' => self::sanitize_num_system( $atts['system'] ),
				'theme'  => self::sanitize_theme( $atts['theme'] ),
				'lang'   => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Synastry compatibility — two subjects, flat _a/_b params in one GET URL.
	 * api needs both dates or it returns an error card.
	 */
	public static function render_synastry( $atts ): string {
		$atts   = shortcode_atts(
			[
				'date_a' => '',
				'time_a' => '',
				'lat_a'  => '',
				'lon_a'  => '',
				'tz_a'   => '',
				'date_b' => '',
				'time_b' => '',
				'lat_b'  => '',
				'lon_b'  => '',
				'tz_b'   => '',
				'theme'  => '',
				'lang'   => '',
			],
			(array) $atts,
			'astroway_synastry'
		);
		$date_a = self::sanitize_date( $atts['date_a'] );
		$time_a = self::sanitize_time( $atts['time_a'] );
		$date_b = self::sanitize_date( $atts['date_b'] );
		$time_b = self::sanitize_time( $atts['time_b'] );
		return PublicClient::embed_iframe(
			'synastry',
			[
				'date_a' => $date_a,
				'time_a' => $time_a,
				'lat_a'  => self::sanitize_coord( $atts['lat_a'], -90, 90 ),
				'lng_a'  => self::sanitize_coord( $atts['lon_a'], -180, 180 ),
				'tz_a'   => self::tz_offset_hours( $atts['tz_a'], $date_a, $time_a ),
				'date_b' => $date_b,
				'time_b' => $time_b,
				'lat_b'  => self::sanitize_coord( $atts['lat_b'], -90, 90 ),
				'lng_b'  => self::sanitize_coord( $atts['lon_b'], -180, 180 ),
				'tz_b'   => self::tz_offset_hours( $atts['tz_b'], $date_b, $time_b ),
				'theme'  => self::sanitize_theme( $atts['theme'] ),
				'lang'   => self::resolve_lang( $atts['lang'] ),
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
		$date = self::sanitize_date( $atts['date'] );
		$time = self::sanitize_time( $atts['time'] );
		// api chart endpoints read `lng` (not `lon`) and `tz` as numeric hours.
		return [
			'date' => $date,
			'time' => $time,
			'lat'  => self::sanitize_coord( $atts['lat'], -90, 90 ),
			'lng'  => self::sanitize_coord( $atts['lon'], -180, 180 ),
			'name' => sanitize_text_field( $atts['name'] ),
			'tz'   => self::tz_offset_hours( $atts['tz'], $date, $time ),
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

	public static function sanitize_num_system( $value ): string {
		return 'chaldean' === strtolower( trim( (string) $value ) ) ? 'chaldean' : 'pythagorean';
	}

	public static function sanitize_theme( $value ): string {
		$theme = strtolower( trim( (string) $value ) );
		// Empty falls through — api defaults to dark.
		return in_array( $theme, [ 'dark', 'light', 'console' ], true ) ? $theme : '';
	}

	/**
	 * Convert a timezone input into the numeric UTC offset (in hours) the api
	 * chart endpoints expect — they read `tz` via Number(), so IANA names and
	 * `+HH:MM` strings silently became 0. Accepts:
	 *   - plain decimal hours: "5.5", "-3", "+2"
	 *   - signed offset: "+05:30", "-03:00"
	 *   - IANA name: "Europe/Kyiv" (resolved at the given moment, DST-aware)
	 * Returns '' for anything unrecognised so the param is omitted.
	 */
	public static function tz_offset_hours( $raw, string $date = '', string $time = '' ): string {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( preg_match( '/^[+-]?\d{1,2}(\.\d+)?$/', $raw ) ) {
			return (string) (float) $raw;
		}
		if ( preg_match( '/^([+-])(\d{2}):(\d{2})$/', $raw, $m ) ) {
			$hours = (int) $m[2] + ( (int) $m[3] ) / 60;
			return (string) ( '-' === $m[1] ? -$hours : $hours );
		}
		if ( preg_match( '#^[A-Za-z]+(?:/[A-Za-z_]+)+$#', $raw ) ) {
			try {
				$tz   = new \DateTimeZone( $raw );
				$when = new \DateTime( ( '' !== $date ? $date : 'now' ) . ' ' . ( '' !== $time ? $time : '12:00' ), $tz );
				return (string) ( $tz->getOffset( $when ) / 3600 );
			} catch ( \Exception $e ) {
				return '';
			}
		}
		return '';
	}
}
