<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public static function register(): void {
		add_shortcode( 'astroway_natal', self::gated( 'natal', [ __CLASS__, 'render_natal' ] ) );
		add_shortcode( 'astroway_daily_horoscope', self::gated( 'daily_horoscope', [ __CLASS__, 'render_daily_horoscope' ] ) );
		add_shortcode( 'astroway_weekly_horoscope', self::gated( 'weekly_horoscope', [ __CLASS__, 'render_weekly_horoscope' ] ) );
		add_shortcode( 'astroway_monthly_horoscope', self::gated( 'monthly_horoscope', [ __CLASS__, 'render_monthly_horoscope' ] ) );
		add_shortcode( 'astroway_planet_of_day', self::gated( 'planet_of_day', [ __CLASS__, 'render_planet_of_day' ] ) );
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
		add_shortcode( 'astroway_mini_chart', self::gated( 'mini_chart', [ __CLASS__, 'render_mini_chart' ] ) );
		add_shortcode( 'astroway_monthly_forecast', self::gated( 'monthly_forecast', [ __CLASS__, 'render_monthly_forecast' ] ) );
		add_shortcode( 'astroway_transit_timeline', self::gated( 'transit_timeline', [ __CLASS__, 'render_transit_timeline' ] ) );

		// Priority 10 lands after wpautop and shortcode_unautop, which core adds
		// at 10 before this file ever runs, and before do_shortcode at 11. The
		// shortcodes are still text at that point, which is the only moment the
		// paragraph can be removed rather than repaired.
		add_filter( 'the_content', [ __CLASS__, 'unautop_shortcode_run' ], 10 );

		/**
		 * Fires after core shortcodes are registered.
		 * Addons should hook here to call add_shortcode() for their own astroway_* shortcodes.
		 *
		 * @since 0.6.1
		 */
		do_action( 'astroway_register_shortcodes' );
	}

	/**
	 * Drop the paragraph WordPress wraps around a run of our shortcodes.
	 *
	 * `wpautop` decides the paragraphs while the shortcodes are still text, and
	 * core only undoes the wrapper when a paragraph holds exactly one shortcode.
	 * Two of them on consecutive lines become one `<p>` joined by `<br />`, which
	 * did no harm while every widget was an iframe. Since 1.0.0 a widget renders
	 * as a card built from `<header>`, `<dl>` and `<p>`, and a browser closes the
	 * enclosing paragraph at the first of those: the card element is left empty
	 * and its content spills out below it, unstyled.
	 *
	 * Only paragraphs made up entirely of `astroway_*` shortcodes are touched. A
	 * sentence wrapped around a widget is the author's formatting, and a run
	 * containing someone else's shortcode is not ours to reformat.
	 *
	 * @since 1.0.0
	 */
	public static function unautop_shortcode_run( $content ) {
		// Not typed as string: plugins and themes do call
		// apply_filters('the_content', null), and a typed callback turns that
		// into a fatal TypeError for the whole page. Core leaves its own
		// callbacks untyped for the same reason.
		if ( ! is_string( $content ) || false === strpos( $content, '[astroway_' ) ) {
			return $content;
		}

		/*
		 * The paragraph is matched with one lazy group and nothing nested, then
		 * inspected in PHP. Expressing the run itself as a repeated group cost a
		 * release candidate: that pattern repeated a sequence holding two
		 * adjacent whitespace quantifiers, and on a paragraph starting with our
		 * shortcodes and ending in something else ("[astroway_moon_phase] "
		 * twenty times followed by [gallery]) PCRE walked every way of splitting
		 * those spaces and hit its backtrack limit. preg_replace_callback then
		 * returns null, which the filter cast to an empty string: every post
		 * shaped like that lost all of its content, silently.
		 */
		$filtered = preg_replace_callback(
			'#<p>(.*?)</p>#s',
			static function ( array $matches ) {
				$run = self::shortcode_run( $matches[1] );
				return null === $run ? $matches[0] : "\n" . $run . "\n";
			},
			$content
		);

		// Any PCRE failure still returns null. The content is worth more than
		// the fix, so a failed pass means the paragraph stays as it was.
		return null === $filtered ? $content : $filtered;
	}

	/**
	 * The shortcodes of a paragraph that holds nothing else, one per line, or
	 * null when the paragraph holds anything besides them.
	 *
	 * Newlines rather than nothing between them: the content still goes through
	 * do_shortcode, and two shortcodes left on one line read as a single
	 * paragraph to any later pass.
	 */
	private static function shortcode_run( string $inner ): ?string {
		// Case-sensitive on purpose: WordPress matches shortcode tags that way,
		// so [ASTROWAY_MOON_PHASE] renders as the literal text its author typed
		// and their paragraph is not ours to take apart.
		$tag = '#\[astroway_[^\]\[]*\]#';

		if ( ! preg_match_all( $tag, $inner, $matches ) ) {
			return null;
		}

		// Decided by subtraction rather than by describing the run as a pattern:
		// take our shortcodes and the line breaks out, and whatever is left has
		// to be nothing. Prose, another plugin's shortcode and the leftover
		// brackets of an escaped [[astroway_moon_phase]] all survive the removal
		// and stop the rewrite, and separators between our own shortcodes never
		// have to be described at all, which is where the backtracking lived.
		$rest = preg_replace( $tag, '', $inner );
		$rest = null === $rest ? null : preg_replace( '#<br\s*/?>#i', '', $rest );
		if ( null === $rest || '' !== trim( $rest ) ) {
			return null;
		}

		return implode( "\n", $matches[0] );
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
				'date'  => '',
				'time'  => '',
				'lat'   => '',
				'lon'   => '',
				'name'  => '',
				'tz'    => '',
				'lang'  => '',
				'theme' => '',
			],
			(array) $atts,
			'astroway_natal'
		);
		$params         = self::sanitize_chart_params( $atts );
		$params['lang'] = self::resolve_lang( $atts['lang'] );
		// The wheel is a picture drawn by the api and cannot read the page it lands
		// on, so its palette has to be declared. Left empty it keeps the api default
		// the widget has always used, which is why upgrading changes no colours.
		$theme = self::sanitize_theme( $atts['theme'] );
		if ( '' !== $theme ) {
			$params['theme'] = $theme;
		}
		return Render::widget( 'natal', $params );
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
		return Render::widget(
			'daily_horoscope',
			[
				'sign' => self::sanitize_sign( $atts['sign'] ),
				'lang' => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/**
	 * Weekly and monthly horoscopes exist only as page-side cards: there is no
	 * /v1/embed/* route for either, so Render's fallback shows administrators a
	 * note and visitors nothing rather than an iframe that would 404.
	 */
	public static function render_weekly_horoscope( $atts ): string {
		return self::render_period_horoscope( $atts, 'weekly_horoscope', 'astroway_weekly_horoscope' );
	}

	public static function render_monthly_horoscope( $atts ): string {
		return self::render_period_horoscope( $atts, 'monthly_horoscope', 'astroway_monthly_horoscope' );
	}

	private static function render_period_horoscope( $atts, string $widget, string $tag ): string {
		$atts = shortcode_atts(
			[
				'sign' => '',
				'date' => '',
				'lang' => '',
			],
			(array) $atts,
			$tag
		);
		return Render::widget(
			$widget,
			[
				'sign' => self::sanitize_sign( $atts['sign'] ),
				'date' => self::sanitize_date( $atts['date'] ),
				'lang' => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	public static function render_planet_of_day( $atts ): string {
		$atts = shortcode_atts(
			[
				'date' => '',
				'lang' => '',
			],
			(array) $atts,
			'astroway_planet_of_day'
		);
		return Render::widget(
			'planet_of_day',
			[
				'date' => self::sanitize_date( $atts['date'] ),
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
		return Render::widget(
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
		$deck = self::sanitize_deck( $atts['deck'] );
		$lang = self::resolve_lang( $atts['lang'] );

		// The public JSON route draws from Rider-Waite and nothing else, so any
		// other deck stays on the iframe rather than being answered with cards
		// from the wrong deck under the right heading.
		if ( 'rider-waite' === $deck ) {
			return Render::widget( 'tarot_daily', [ 'lang' => $lang ] );
		}

		return PublicClient::embed_iframe(
			'tarot_daily',
			[
				'deck' => $deck,
				'lang' => $lang,
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
		return Render::admin_note(
			sprintf(
				/* translators: %s = shortcode name */
				__( 'The %s widget is temporarily unavailable, so it renders nothing. Only administrators see this note.', 'astroway' ),
				'[' . $shortcode . ']'
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
	/**
	 * Compact natal wheel for a sidebar. Same birth data as the full chart,
	 * drawn small; there is no page-side template, so it is always a frame.
	 */
	public static function render_mini_chart( $atts ): string {
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
			'astroway_mini_chart'
		);
		$date = self::sanitize_date( $atts['date'] );
		$time = self::sanitize_time( $atts['time'] );
		return PublicClient::embed_iframe(
			'mini_chart',
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

	/** Four-week themed forecast for one sign. */
	public static function render_monthly_forecast( $atts ): string {
		$atts = shortcode_atts(
			[
				'sign'  => '',
				'date'  => '',
				'theme' => '',
				'lang'  => '',
			],
			(array) $atts,
			'astroway_monthly_forecast'
		);
		return PublicClient::embed_iframe(
			'monthly_forecast',
			[
				'sign'  => self::sanitize_sign( $atts['sign'] ),
				'date'  => self::sanitize_date( $atts['date'] ),
				'theme' => self::sanitize_theme( $atts['theme'] ),
				'lang'  => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

	/** Seven-day Moon ingress timeline. Not sign-specific. */
	public static function render_transit_timeline( $atts ): string {
		$atts = shortcode_atts(
			[
				'date'  => '',
				'theme' => '',
				'lang'  => '',
			],
			(array) $atts,
			'astroway_transit_timeline'
		);
		return PublicClient::embed_iframe(
			'transit_timeline',
			[
				'date'  => self::sanitize_date( $atts['date'] ),
				'theme' => self::sanitize_theme( $atts['theme'] ),
				'lang'  => self::resolve_lang( $atts['lang'] ),
			]
		);
	}

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
		// Lives on Plugin now: it owns SUPPORTED_LANGS and normalize_locale, and
		// the server-side client needs the same resolution.
		return Plugin::resolve_lang( $raw );
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
