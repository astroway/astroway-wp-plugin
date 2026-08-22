<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a public payload into markup that lives in the page DOM.
 *
 * The point of the whole exercise: an iframe puts the text on a different
 * document, where the site's search engine, the site's stylesheet and a reader
 * without JavaScript all fail to reach it. These templates put the same text in
 * the page itself, wrapped in a custom element that is a styling hook and
 * nothing else. No script is registered for those elements and none is needed;
 * an unregistered hyphenated tag is a plain, styleable HTMLElement.
 *
 * When the data cannot be had the widget falls back to the iframe it used to
 * be, so a quota wall or a slow api degrades to the old behaviour instead of a
 * blank space. Widgets with no iframe equivalent render nothing at all for
 * visitors, and a short note for administrators.
 */
class Render {

	/** Custom element and modifier per widget. */
	private const CARDS = [
		'daily_horoscope'      => [ 'astroway-horoscope-card', 'horoscope' ],
		'weekly_horoscope'     => [ 'astroway-horoscope-card', 'horoscope' ],
		'monthly_horoscope'    => [ 'astroway-horoscope-card', 'horoscope' ],
		'moon_phase'           => [ 'astroway-moon-card', 'moon' ],
		'tarot_daily'          => [ 'astroway-tarot-card', 'tarot' ],
		'planet_of_day'        => [ 'astroway-planet-card', 'planet' ],
		'natal'                => [ 'astroway-natal-card', 'natal' ],
		'moon_sign'            => [ 'astroway-sign-card', 'moon-sign' ],
		'rising_sign'          => [ 'astroway-sign-card', 'rising-sign' ],
		'bodygraph'            => [ 'astroway-bodygraph-card', 'bodygraph' ],
		'retrograde'           => [ 'astroway-retrograde-card', 'retrograde' ],
		'retrogrades'          => [ 'astroway-retrograde-card', 'retrogrades' ],
		'moon_voc'             => [ 'astroway-voc-card', 'moon-voc' ],
		'planetary_hours'      => [ 'astroway-hours-card', 'planetary-hours' ],
		'yearly_horoscope'     => [ 'astroway-horoscope-card', 'horoscope' ],
		'zodiac_compatibility' => [ 'astroway-compat-card', 'zodiac-compatibility' ],
		'chinese_zodiac'       => [ 'astroway-chinese-card', 'chinese-zodiac' ],
		'synastry'             => [ 'astroway-synastry-card', 'synastry' ],
	];

	/**
	 * Server-rendered widget, or the iframe when the data is unavailable.
	 *
	 * @param string $widget Widget key, as used by PublicData and RendererDecisions.
	 * @param array  $params Shortcode params, already sanitised by the caller.
	 */
	public static function widget( string $widget, array $params = [] ): string {
		// "In an iframe" is a preference, and it only applies where a frame is
		// actually an option. The moon and rising sign cards have no embed route
		// behind them, so honouring the setting there would render nothing at all.
		$framed = null !== RendererDecisions::get( $widget );
		if ( ( $framed && 'iframe' === self::mode() ) || ! PublicData::supports( $widget ) || ! isset( self::CARDS[ $widget ] ) ) {
			return PublicClient::embed_iframe( $widget, $params );
		}

		$data = PublicData::get( $widget, $params );
		if ( null === $data ) {
			return self::fallback( $widget, $params );
		}

		$markup = self::card( $widget, $data, Plugin::resolve_lang( $params['lang'] ?? '' ), $params );
		return '' === $markup ? self::fallback( $widget, $params ) : $markup;
	}

	/**
	 * Render mode from Settings. Anything that is not an explicit `iframe` means
	 * server-side, which keeps a stored `client` from an older build (a mode that
	 * never worked, see plan item 3.4) from silently disabling the widgets.
	 */
	private static function mode(): string {
		if ( ! class_exists( __NAMESPACE__ . '\\Admin' ) ) {
			return 'auto';
		}
		$opts = (array) get_option( Admin::OPTION_KEY, [] );
		return 'iframe' === ( $opts['render_mode'] ?? 'auto' ) ? 'iframe' : 'auto';
	}

	/**
	 * The iframe is the fallback because it fails differently: it is fetched by
	 * the visitor's browser against the visitor's own quota, so a server that
	 * has run out still shows something.
	 */
	private static function fallback( string $widget, array $params ): string {
		if ( null !== RendererDecisions::get( $widget ) ) {
			return PublicClient::embed_iframe( $widget, $params );
		}
		// The rising sign is the one widget that can reach here by refusing rather
		// than failing, and telling an editor "try again" would send them looking
		// for a fault that is not there.
		if ( 'rising_sign' === $widget ) {
			return self::admin_note(
				__( 'The rising sign needs a latitude and a longitude: it is the horizon at a place, not a date. Only administrators see this note.', 'astroway' )
			);
		}

		return self::admin_note(
			sprintf(
				/* translators: %s = widget name, e.g. "moon sign" */
				__( 'The %s widget could not be loaded just now. Only administrators see this note.', 'astroway' ),
				str_replace( '_', ' ', $widget )
			)
		);
	}

	/**
	 * A note only administrators see. Visitors get nothing rather than an empty
	 * bordered box, which is the failure mode this plugin shipped for months.
	 */
	public static function admin_note( string $text ): string {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		return sprintf(
			'<div class="astroway-embed astroway-embed--unavailable"><p>%s</p></div>',
			esc_html( $text )
		);
	}

	private static function card( string $widget, array $data, string $lang, array $params = [] ): string {
		switch ( $widget ) {
			case 'daily_horoscope':
			case 'weekly_horoscope':
			case 'monthly_horoscope':
				return self::horoscope_card( $widget, $data, $lang );
			case 'moon_phase':
				return self::moon_card( $data, $lang );
			case 'tarot_daily':
				return self::tarot_card( $data, $lang );
			case 'planet_of_day':
				return self::planet_card( $data, $lang );
			case 'natal':
				return self::natal_card( $data, $lang, $params );
			case 'moon_sign':
				return self::placement_card( 'moon_sign', $data, $lang, $params );
			case 'rising_sign':
				return self::placement_card( 'rising_sign', $data, $lang, $params );
			case 'bodygraph':
				return self::bodygraph_card( $data, $lang, $params );
			case 'synastry':
				return self::synastry_card( $data, $lang, $params );
		}
		return '';
	}

	/**
	 * One placement out of a whole chart: the Moon's sign, or the rising sign.
	 *
	 * Both read the same payload the natal card does, so a page carrying all
	 * three spends one api call. The reason they exist as separate widgets is
	 * that "what's my moon sign" is a question people ask on its own, and
	 * answering it with the full placements table buries the answer.
	 */
	private static function placement_card( string $widget, array $data, string $lang, array $params ): string {
		$houses = isset( $data['houses'] ) && is_array( $data['houses'] ) ? $data['houses'] : [];
		$cusps  = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];

		if ( 'rising_sign' === $widget ) {
			if ( ! isset( $houses['ascendant'] ) ) {
				return '';
			}
			$longitude = (float) $houses['ascendant'];
			$title     = __( 'Rising sign', 'astroway' );
			$house     = null;
		} else {
			$moon = null;
			foreach ( ( isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [] ) as $planet ) {
				if ( is_array( $planet ) && 'Moon' === ( $planet['name'] ?? '' ) && isset( $planet['longitude'] ) ) {
					$moon = $planet;
					break;
				}
			}
			if ( null === $moon ) {
				return '';
			}
			$longitude = (float) $moon['longitude'];
			$title     = __( 'Moon sign', 'astroway' );
			$house     = self::house_of( $longitude, $cusps );
		}

		$sign = self::sign_of( $longitude );
		$name = trim( (string) ( $params['name'] ?? '' ) );
		if ( '' !== $name ) {
			$title = sprintf(
				/* translators: 1: heading such as "Moon sign", 2: person's name */
				__( '%1$s: %2$s', 'astroway' ),
				$title,
				$name
			);
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html( $title ) . '</h3>';
		$inner .= self::date_line( (string) ( $params['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( self::sign_label( $sign ) ) . '</p>';

		$rows = [ __( 'Position', 'astroway' ) => self::position_label( $longitude ) ];
		if ( null !== $house ) {
			/* translators: %d = house number */
			$rows[ __( 'House', 'astroway' ) ] = sprintf( __( 'House %d', 'astroway' ), $house );
		}

		if ( 'rising_sign' === $widget ) {
			// The chart ruler is the planet that rules the rising sign, and where
			// it sits is the first thing a reader is told to look at next.
			$ruler = self::ruler_of( $sign );
			if ( '' !== $ruler ) {
				$rows[ __( 'Chart ruler', 'astroway' ) ] = self::planet_label( $ruler );
				foreach ( ( isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [] ) as $planet ) {
					if ( is_array( $planet ) && ( $planet['name'] ?? '' ) === $ruler && isset( $planet['longitude'] ) ) {
						$rows[ __( 'Ruler position', 'astroway' ) ] = self::position_label( (float) $planet['longitude'] );
						break;
					}
				}
			}
			if ( isset( $houses['mc'] ) ) {
				$rows[ __( 'Midheaven', 'astroway' ) ] = self::position_label( (float) $houses['mc'] );
			}
		}

		$inner .= self::detail_list( $rows );

		return self::shell( $widget, $lang, $inner );
	}

	/**
	 * Human Design chart as text.
	 *
	 * Unlike the natal card this one drops the frame entirely rather than keeping
	 * it alongside. The natal frame draws a wheel, a picture the JSON does not
	 * carry. `/v1/embed/bodygraph` draws nothing: checked against the live
	 * response, it is the same handful of facts as a text table with emoji for
	 * the centres. Keeping it would print the reading twice on the page, the
	 * second copy in someone else's stylesheet and invisible to a crawler.
	 */
	private static function bodygraph_card( array $data, string $lang, array $params ): string {
		$type = trim( (string) ( $data['type'] ?? '' ) );
		if ( '' === $type ) {
			return '';
		}

		$name  = trim( (string) ( $params['name'] ?? '' ) );
		$title = __( 'Human Design', 'astroway' );
		if ( '' !== $name ) {
			/* translators: %s = person's name */
			$title = sprintf( __( 'Human Design: %s', 'astroway' ), $name );
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html( $title ) . '</h3>';
		$inner .= self::date_line( (string) ( $params['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( self::hd_type_label( $type ) ) . '</p>';

		$rows = [];
		if ( ! empty( $data['strategy'] ) ) {
			$rows[ __( 'Strategy', 'astroway' ) ] = self::hd_strategy_label( (string) $data['strategy'] );
		}
		if ( ! empty( $data['authority'] ) ) {
			$rows[ __( 'Authority', 'astroway' ) ] = self::hd_authority_label( (string) $data['authority'] );
		}
		if ( ! empty( $data['profile']['profile'] ) ) {
			$profile = (string) $data['profile']['profile'];
			if ( ! empty( $data['profile']['geometry'] ) ) {
				$profile = sprintf(
					/* translators: 1: profile such as 6/2, 2: incarnation geometry such as Left Angle */
					__( '%1$s, %2$s', 'astroway' ),
					$profile,
					self::hd_geometry_label( (string) $data['profile']['geometry'] )
				);
			}
			$rows[ __( 'Profile', 'astroway' ) ] = $profile;
		}
		if ( ! empty( $data['definition'] ) ) {
			$rows[ __( 'Definition', 'astroway' ) ] = self::hd_definition_label( (string) $data['definition'] );
		}
		if ( ! empty( $data['notSelfTheme'] ) ) {
			$rows[ __( 'Not-self theme', 'astroway' ) ] = self::hd_theme_label( (string) $data['notSelfTheme'] );
		}
		if ( ! empty( $data['cross']['name'] ) ) {
			// The cross names are an open set of hundreds, so they stay as sent.
			$rows[ __( 'Incarnation cross', 'astroway' ) ] = (string) $data['cross']['name'];
		}
		$inner .= self::detail_list( $rows );

		$inner .= self::hd_centres( $data['centers'] ?? [] );
		$inner .= self::hd_channels( $data['channels'] ?? [] );

		return self::shell( 'bodygraph', $lang, $inner );
	}

	/** Defined centres named, undefined ones counted: nine lines would drown the card. */
	private static function hd_centres( $centres ): string {
		if ( ! is_array( $centres ) || empty( $centres ) ) {
			return '';
		}
		$defined = [];
		$open    = [];
		foreach ( $centres as $centre ) {
			if ( ! is_array( $centre ) || empty( $centre['name'] ) ) {
				continue;
			}
			$label = self::hd_centre_label( (string) $centre['name'] );
			if ( ! empty( $centre['defined'] ) ) {
				$defined[] = $label;
			} else {
				$open[] = $label;
			}
		}
		if ( empty( $defined ) && empty( $open ) ) {
			return '';
		}

		$rows = [];
		if ( ! empty( $defined ) ) {
			$rows[ __( 'Defined centres', 'astroway' ) ] = implode( ', ', $defined );
		}
		if ( ! empty( $open ) ) {
			$rows[ __( 'Open centres', 'astroway' ) ] = implode( ', ', $open );
		}
		return '<h4 class="astroway-card__subtitle">' . esc_html__( 'Centres', 'astroway' ) . '</h4>' . self::detail_list( $rows );
	}

	private static function hd_channels( $channels ): string {
		if ( ! is_array( $channels ) || empty( $channels ) ) {
			return '';
		}
		$items = '';
		foreach ( $channels as $channel ) {
			if ( ! is_array( $channel ) || ! isset( $channel['gate1'], $channel['gate2'] ) ) {
				continue;
			}
			$items .= '<li>' . esc_html(
				sprintf(
					/* translators: 1: first gate number, 2: second gate number, 3: first centre, 4: second centre */
					__( '%1$d-%2$d, %3$s to %4$s', 'astroway' ),
					(int) $channel['gate1'],
					(int) $channel['gate2'],
					self::hd_centre_label( (string) ( $channel['centerA'] ?? '' ) ),
					self::hd_centre_label( (string) ( $channel['centerB'] ?? '' ) )
				)
			) . '</li>';
		}
		if ( '' === $items ) {
			return '';
		}
		return '<h4 class="astroway-card__subtitle">' . esc_html__( 'Channels', 'astroway' ) . '</h4><ul class="astroway-card__channels">' . $items . '</ul>';
	}

	private static function hd_type_label( string $raw ): string {
		$labels = [
			'Generator'             => __( 'Generator', 'astroway' ),
			'Manifesting Generator' => __( 'Manifesting Generator', 'astroway' ),
			'Manifestor'            => __( 'Manifestor', 'astroway' ),
			'Projector'             => __( 'Projector', 'astroway' ),
			'Reflector'             => __( 'Reflector', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function hd_strategy_label( string $raw ): string {
		$labels = [
			'Wait to Respond'         => __( 'Wait to respond', 'astroway' ),
			'Wait for the Invitation' => __( 'Wait for the invitation', 'astroway' ),
			'Inform before Acting'    => __( 'Inform before acting', 'astroway' ),
			'To Inform'               => __( 'Inform before acting', 'astroway' ),
			'Wait a Lunar Cycle'      => __( 'Wait a lunar cycle', 'astroway' ),
			'Respond, then Inform'    => __( 'Respond, then inform', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function hd_authority_label( string $raw ): string {
		$labels = [
			'Emotional (Solar Plexus)' => __( 'Emotional (Solar Plexus)', 'astroway' ),
			'Sacral'                   => __( 'Sacral', 'astroway' ),
			'Splenic'                  => __( 'Splenic', 'astroway' ),
			'Ego (Heart)'              => __( 'Ego (Heart)', 'astroway' ),
			'Self-Projected'           => __( 'Self-projected', 'astroway' ),
			'Mental (Environment)'     => __( 'Mental (environment)', 'astroway' ),
			'Lunar Cycle'              => __( 'Lunar cycle', 'astroway' ),
			'None (Lunar)'             => __( 'Lunar cycle', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function hd_definition_label( string $raw ): string {
		$labels = [
			'Single'          => __( 'Single definition', 'astroway' ),
			'Split'           => __( 'Split definition', 'astroway' ),
			'Triple Split'    => __( 'Triple split definition', 'astroway' ),
			'Quadruple Split' => __( 'Quadruple split definition', 'astroway' ),
			'No Definition'   => __( 'No definition', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function hd_theme_label( string $raw ): string {
		$labels = [
			'Frustration'    => __( 'Frustration', 'astroway' ),
			'Bitterness'     => __( 'Bitterness', 'astroway' ),
			'Anger'          => __( 'Anger', 'astroway' ),
			'Disappointment' => __( 'Disappointment', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function hd_geometry_label( string $raw ): string {
		$labels = [
			'Right Angle'   => __( 'Right Angle', 'astroway' ),
			'Left Angle'    => __( 'Left Angle', 'astroway' ),
			'Juxtaposition' => __( 'Juxtaposition', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	/** The nine centres, as the api spells them: "SolarPlexus" is one word there. */
	private static function hd_centre_label( string $raw ): string {
		$labels = [
			'Head'        => __( 'Head', 'astroway' ),
			'Ajna'        => __( 'Ajna', 'astroway' ),
			'Throat'      => __( 'Throat', 'astroway' ),
			'G'           => __( 'G (identity)', 'astroway' ),
			'Heart'       => __( 'Heart (ego)', 'astroway' ),
			'SolarPlexus' => __( 'Solar Plexus', 'astroway' ),
			'Spleen'      => __( 'Spleen', 'astroway' ),
			'Sacral'      => __( 'Sacral', 'astroway' ),
			'Root'        => __( 'Root', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	/**
	 * Natal chart as text, with the wheel kept alongside it.
	 *
	 * The JSON has no drawing in it, and a wheel is genuinely a picture, so the
	 * iframe stays: dropping it would take the visual away from every site that
	 * has the shortcode today. What the JSON adds is the part an iframe hides,
	 * the placements and aspects as readable, indexable text.
	 */
	private static function natal_card( array $data, string $lang, array $params ): string {
		$planets = isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [];
		if ( empty( $planets ) ) {
			return '';
		}

		$houses = isset( $data['houses'] ) && is_array( $data['houses'] ) ? $data['houses'] : [];
		$cusps  = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];

		$name  = trim( (string) ( $params['name'] ?? '' ) );
		$title = __( 'Natal chart', 'astroway' );
		if ( '' !== $name ) {
			/* translators: %s = person's name */
			$title = sprintf( __( 'Natal chart: %s', 'astroway' ), $name );
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html( $title ) . '</h3>';
		$inner .= self::date_line( (string) ( $params['date'] ?? '' ) );
		$inner .= '</header>';

		// The wheel, unchanged from what the shortcode rendered before.
		$inner .= PublicClient::embed_iframe( 'natal', $params );

		$angles = [];
		if ( isset( $houses['ascendant'] ) ) {
			$angles[ __( 'Ascendant', 'astroway' ) ] = self::position_label( (float) $houses['ascendant'] );
		}
		if ( isset( $houses['mc'] ) ) {
			$angles[ __( 'Midheaven', 'astroway' ) ] = self::position_label( (float) $houses['mc'] );
		}
		$inner .= self::detail_list( $angles );

		$rows = '';
		foreach ( $planets as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['longitude'] ) ) {
				continue;
			}
			$lon   = (float) $planet['longitude'];
			$house = self::house_of( $lon, $cusps );
			$rows .= '<tr><th scope="row">' . esc_html( self::planet_label( (string) ( $planet['name'] ?? '' ) ) ) . '</th>';
			$rows .= '<td>' . esc_html( self::position_label( $lon ) ) . '</td>';
			$rows .= '<td>' . ( null === $house ? '' : esc_html( sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house ) ) ) . '</td>';
			$rows .= '<td>' . ( empty( $planet['isRetrograde'] ) ? '' : esc_html__( 'retrograde', 'astroway' ) ) . '</td></tr>';
		}
		if ( '' !== $rows ) {
			$inner .= '<table class="astroway-card__placements"><caption>' . esc_html__( 'Placements', 'astroway' ) . '</caption><tbody>' . $rows . '</tbody></table>';
		}

		$inner .= self::aspect_list( $data['aspects'] ?? [] );

		return self::shell( 'natal', $lang, $inner );
	}

	/** Major aspects only: the minor ones triple the list without helping a reader. */
	private static function aspect_list( $aspects ): string {
		if ( ! is_array( $aspects ) ) {
			return '';
		}
		$items = '';
		foreach ( $aspects as $aspect ) {
			if ( ! is_array( $aspect ) || empty( $aspect['type']['isMajor'] ) ) {
				continue;
			}
			$items .= '<li>' . esc_html(
				sprintf(
					/* translators: 1: first planet, 2: aspect name, 3: second planet */
					__( '%1$s %2$s %3$s', 'astroway' ),
					self::planet_label( (string) ( $aspect['planet1'] ?? '' ) ),
					self::aspect_label( (string) ( $aspect['type']['name'] ?? '' ) ),
					self::planet_label( (string) ( $aspect['planet2'] ?? '' ) )
				)
			) . '</li>';
		}
		if ( '' === $items ) {
			return '';
		}
		return '<h4 class="astroway-card__subtitle">' . esc_html__( 'Major aspects', 'astroway' ) . '</h4><ul class="astroway-card__aspects">' . $items . '</ul>';
	}

	/** "Leo 24°22'" from an ecliptic longitude. */
	private static function position_label( float $longitude ): string {
		$lon     = fmod( fmod( $longitude, 360 ) + 360, 360 );
		$in_sign = $lon - ( ( (int) floor( $lon / 30 ) ) * 30 );
		$degrees = (int) floor( $in_sign );
		$minutes = (int) round( ( $in_sign - $degrees ) * 60 );
		if ( 60 === $minutes ) {
			$minutes = 0;
			++$degrees;
		}
		return sprintf( '%s %d°%02d\'', self::sign_label( self::sign_of( $longitude ) ), $degrees, $minutes );
	}

	/** Sign key an ecliptic longitude falls in, e.g. 296.39 → aquarius. */
	public static function sign_of( float $longitude ): string {
		$lon   = fmod( fmod( $longitude, 360 ) + 360, 360 );
		$names = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
		return $names[ (int) floor( $lon / 30 ) ] ?? '';
	}

	/**
	 * Modern ruler of a sign, spelled the way the api names its planets.
	 *
	 * Modern rather than traditional, because the chart the ruler is looked up
	 * in carries Uranus, Neptune and Pluto: naming Mars the ruler of Scorpio and
	 * then pointing at a chart that also has Pluto in it invites the question of
	 * which one the card means.
	 */
	public static function ruler_of( string $sign ): string {
		$rulers = [
			'aries'       => 'Mars',
			'taurus'      => 'Venus',
			'gemini'      => 'Mercury',
			'cancer'      => 'Moon',
			'leo'         => 'Sun',
			'virgo'       => 'Mercury',
			'libra'       => 'Venus',
			'scorpio'     => 'Pluto',
			'sagittarius' => 'Jupiter',
			'capricorn'   => 'Saturn',
			'aquarius'    => 'Uranus',
			'pisces'      => 'Neptune',
		];
		return $rulers[ strtolower( trim( $sign ) ) ] ?? '';
	}

	/**
	 * House containing a longitude, or null without usable cusps.
	 *
	 * Houses run forward through the zodiac and one of them always crosses 0°,
	 * so the containing test has to allow the wrapping interval rather than
	 * compare two numbers.
	 */
	private static function house_of( float $longitude, array $cusps ): ?int {
		if ( 12 !== count( $cusps ) ) {
			return null;
		}
		$lon = fmod( fmod( $longitude, 360 ) + 360, 360 );
		for ( $i = 0; $i < 12; $i++ ) {
			$start = fmod( fmod( (float) $cusps[ $i ], 360 ) + 360, 360 );
			$end   = fmod( fmod( (float) $cusps[ ( $i + 1 ) % 12 ], 360 ) + 360, 360 );
			$in    = $start <= $end
				? ( $lon >= $start && $lon < $end )
				: ( $lon >= $start || $lon < $end );
			if ( $in ) {
				return $i + 1;
			}
		}
		return null;
	}

	/**
	 * The api's own translation of a field, when it sent one.
	 *
	 * Since api 2.124.0 moon-phase and planet-of-day carry a `localized` object
	 * beside the English identifiers, and it follows the `lang` the call asked
	 * for. That is the difference that matters: the maps below are gettext, so
	 * they answer in the site's locale, and `[astroway_moon_phase lang="uk"]` on
	 * an English site printed "Waning Gibbous" over Ukrainian data.
	 *
	 * The maps stay as the fallback rather than being deleted. The natal card
	 * reads sign and planet names off a payload that has no `localized` object at
	 * all, a chart is cached for a month and a horoscope until midnight, so
	 * answers fetched before the api shipped this are still being served, and a
	 * missing or malformed entry must degrade to the English name rather than
	 * blank the line.
	 */
	private static function localised( array $data, string $field, string $fallback ): string {
		$value = $data['localized'][ $field ] ?? null;
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return $fallback;
		}
		return trim( $value );
	}

	/**
	 * The eight phase names the api returns, kept as the fallback for payloads
	 * that carry no `localized` object: cached ones, and anything the api has not
	 * translated yet.
	 */
	public static function phase_label( string $raw ): string {
		$labels = [
			'new moon'        => __( 'New Moon', 'astroway' ),
			'waxing crescent' => __( 'Waxing Crescent', 'astroway' ),
			'first quarter'   => __( 'First Quarter', 'astroway' ),
			'waxing gibbous'  => __( 'Waxing Gibbous', 'astroway' ),
			'full moon'       => __( 'Full Moon', 'astroway' ),
			'waning gibbous'  => __( 'Waning Gibbous', 'astroway' ),
			'last quarter'    => __( 'Last Quarter', 'astroway' ),
			'waning crescent' => __( 'Waning Crescent', 'astroway' ),
		];
		return $labels[ strtolower( trim( $raw ) ) ] ?? $raw;
	}

	public static function planet_label( string $raw ): string {
		$labels = [
			'Sun'         => __( 'Sun', 'astroway' ),
			'Moon'        => __( 'Moon', 'astroway' ),
			'Mercury'     => __( 'Mercury', 'astroway' ),
			'Venus'       => __( 'Venus', 'astroway' ),
			'Mars'        => __( 'Mars', 'astroway' ),
			'Jupiter'     => __( 'Jupiter', 'astroway' ),
			'Saturn'      => __( 'Saturn', 'astroway' ),
			'Uranus'      => __( 'Uranus', 'astroway' ),
			'Neptune'     => __( 'Neptune', 'astroway' ),
			'Pluto'       => __( 'Pluto', 'astroway' ),
			'Chiron'      => __( 'Chiron', 'astroway' ),
			'Lilith'      => __( 'Lilith', 'astroway' ),
			'North Node'  => __( 'North Node', 'astroway' ),
			'South Node'  => __( 'South Node', 'astroway' ),
			// The names Swiss Ephemeris actually returns for these two points.
			// Without them the table read "true Node" and "mean Apogee".
			'true Node'   => __( 'North Node', 'astroway' ),
			'mean Node'   => __( 'North Node', 'astroway' ),
			'mean Apogee' => __( 'Lilith', 'astroway' ),
			'osc. Apogee' => __( 'Lilith', 'astroway' ),
		];
		return $labels[ $raw ] ?? $raw;
	}

	private static function aspect_label( string $raw ): string {
		$labels = [
			'Conjunction' => __( 'conjunct', 'astroway' ),
			'Opposition'  => __( 'opposite', 'astroway' ),
			'Trine'       => __( 'trine', 'astroway' ),
			'Square'      => __( 'square', 'astroway' ),
			'Sextile'     => __( 'sextile', 'astroway' ),
		];
		return $labels[ $raw ] ?? strtolower( $raw );
	}

	private static function horoscope_card( string $widget, array $data, string $lang, array $params = [] ): string {
		$body = trim( (string) ( $data['horoscope'] ?? '' ) );
		if ( '' === $body ) {
			return '';
		}

		// The public routes echo the sign back, the keyed yearly one does not,
		// so the shortcode's own attribute is the fallback.
		$raw   = (string) ( $data['sign'] ?? ( $params['sign'] ?? '' ) );
		$sign  = self::sign_label( $raw );
		$title = self::horoscope_title( $widget, $sign );

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html( $title ) . '</h3>';
		$inner .= self::date_line( (string) ( $data['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<div class="astroway-card__body">' . self::paragraphs( $body ) . '</div>';
		$inner .= self::note_line( (string) ( $data['disclaimer'] ?? '' ) );

		return self::shell( $widget, $lang, $inner );
	}

	private static function moon_card( array $data, string $lang ): string {
		$phase = (string) ( $data['phaseName'] ?? '' );
		if ( '' === $phase ) {
			return '';
		}

		$rows = [];
		if ( isset( $data['illuminationPercent'] ) ) {
			$rows[ __( 'Illumination', 'astroway' ) ] = sprintf( '%s%%', self::number( (float) $data['illuminationPercent'], 1 ) );
		}
		if ( isset( $data['ageDays'] ) ) {
			$rows[ __( 'Age', 'astroway' ) ] = sprintf(
				/* translators: %s = number of days, may be fractional */
				__( '%s days', 'astroway' ),
				self::number( (float) $data['ageDays'], 1 )
			);
		}
		if ( ! empty( $data['moonSign'] ) ) {
			$rows[ __( 'Moon in', 'astroway' ) ] = self::localised( $data, 'moonSign', self::sign_label( (string) $data['moonSign'] ) );
		}
		if ( ! empty( $data['sunSign'] ) ) {
			$rows[ __( 'Sun in', 'astroway' ) ] = self::localised( $data, 'sunSign', self::sign_label( (string) $data['sunSign'] ) );
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Moon phase', 'astroway' ) . '</h3>';
		$inner .= self::date_line( (string) ( $data['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( self::localised( $data, 'phaseName', self::phase_label( $phase ) ) ) . '</p>';
		$inner .= self::detail_list( $rows );

		return self::shell( 'moon_phase', $lang, $inner );
	}

	private static function tarot_card( array $data, string $lang ): string {
		$drawn = $data['drawn'][0] ?? null;
		if ( ! is_array( $drawn ) || ! is_array( $drawn['card'] ?? null ) ) {
			return '';
		}

		$card        = $drawn['card'];
		$is_reversed = ! empty( $drawn['reversed'] );
		// The api ships both readings on every card; which one applies depends on
		// how it was drawn, so picking the wrong branch silently reverses the meaning.
		$reading = $is_reversed ? ( $card['reversed'] ?? [] ) : ( $card['upright'] ?? [] );

		$name = (string) ( $card['name'] ?? '' );
		if ( '' === $name ) {
			return '';
		}
		if ( $is_reversed ) {
			$name = sprintf(
				/* translators: %s = tarot card name */
				__( '%s (reversed)', 'astroway' ),
				$name
			);
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Card of the day', 'astroway' ) . '</h3>';
		$inner .= self::date_line( (string) ( $data['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( $name ) . '</p>';

		$keywords = isset( $reading['keywords'] ) && is_array( $reading['keywords'] ) ? $reading['keywords'] : [];
		if ( ! empty( $keywords ) ) {
			$inner .= '<p class="astroway-card__keywords">' . esc_html( implode( ', ', array_map( 'strval', $keywords ) ) ) . '</p>';
		}
		$meaning = trim( (string) ( $reading['meaning'] ?? '' ) );
		if ( '' !== $meaning ) {
			$inner .= '<div class="astroway-card__body"><p>' . esc_html( $meaning ) . '</p></div>';
		}

		return self::shell( 'tarot_daily', $lang, $inner );
	}

	private static function planet_card( array $data, string $lang ): string {
		$planet = (string) ( $data['planet'] ?? '' );
		if ( '' === $planet ) {
			return '';
		}

		$name  = self::localised( $data, 'planet', self::planet_label( $planet ) );
		$glyph = (string) ( $data['glyph'] ?? '' );
		$lead  = '' === $glyph
			? esc_html( $name )
			: '<span class="astroway-card__glyph" aria-hidden="true">' . esc_html( $glyph ) . '</span> ' . esc_html( $name );

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Planet of the day', 'astroway' ) . '</h3>';
		$inner .= self::date_line( (string) ( $data['date'] ?? '' ) );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead">' . $lead . '</p>';

		$themes = isset( $data['themes'] ) && is_array( $data['themes'] ) ? $data['themes'] : [];
		if ( ! empty( $themes ) ) {
			$items = '';
			foreach ( $themes as $theme ) {
				$items .= '<li>' . esc_html( (string) $theme ) . '</li>';
			}
			$inner .= '<ul class="astroway-card__themes">' . $items . '</ul>';
		}

		return self::shell( 'planet_of_day', $lang, $inner );
	}

	private static function shell( string $widget, string $lang, string $inner ): string {
		list( $tag, $modifier ) = self::CARDS[ $widget ];
		return sprintf(
			'<%1$s class="astroway-card astroway-card--%2$s" lang="%3$s">%4$s</%1$s>',
			$tag,
			esc_attr( $modifier ),
			esc_attr( $lang ),
			$inner
		);
	}

	private static function horoscope_title( string $widget, string $sign ): string {
		switch ( $widget ) {
			case 'weekly_horoscope':
				/* translators: %s = zodiac sign name */
				return sprintf( __( '%s: horoscope for the week', 'astroway' ), $sign );
			case 'monthly_horoscope':
				/* translators: %s = zodiac sign name */
				return sprintf( __( '%s: horoscope for the month', 'astroway' ), $sign );
			case 'yearly_horoscope':
				/* translators: %s = zodiac sign name */
				return sprintf( __( '%s: horoscope for the year', 'astroway' ), $sign );
			default:
				/* translators: %s = zodiac sign name */
				return sprintf( __( '%s: horoscope for today', 'astroway' ), $sign );
		}
	}

	/** Translated sign name; falls back to whatever the api sent when unrecognised. */
	private static function sign_label( string $raw ): string {
		$labels = [
			'aries'       => __( 'Aries', 'astroway' ),
			'taurus'      => __( 'Taurus', 'astroway' ),
			'gemini'      => __( 'Gemini', 'astroway' ),
			'cancer'      => __( 'Cancer', 'astroway' ),
			'leo'         => __( 'Leo', 'astroway' ),
			'virgo'       => __( 'Virgo', 'astroway' ),
			'libra'       => __( 'Libra', 'astroway' ),
			'scorpio'     => __( 'Scorpio', 'astroway' ),
			'sagittarius' => __( 'Sagittarius', 'astroway' ),
			'capricorn'   => __( 'Capricorn', 'astroway' ),
			'aquarius'    => __( 'Aquarius', 'astroway' ),
			'pisces'      => __( 'Pisces', 'astroway' ),
		];
		$key    = strtolower( trim( $raw ) );
		return $labels[ $key ] ?? $raw;
	}

	/** `<time>` element, or nothing when the payload carried no date. */
	private static function date_line( string $date ): string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}
		$stamp = strtotime( $date . ' 00:00:00 UTC' );
		$shown = false === $stamp ? $date : wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $stamp );
		return sprintf(
			'<time class="astroway-card__meta" datetime="%s">%s</time>',
			esc_attr( $date ),
			esc_html( (string) $shown )
		);
	}

	private static function note_line( string $note ): string {
		$note = trim( $note );
		if ( '' === $note ) {
			return '';
		}
		return '<footer class="astroway-card__note"><small>' . esc_html( $note ) . '</small></footer>';
	}

	/**
	 * Blank-line separated text becomes paragraphs and lists.
	 *
	 * The weekly and monthly readings come back as Markdown: bold runs, emphasis
	 * and hyphen bullets. The endpoint does not declare a format, so rather than
	 * trust the model to keep emitting one, the renderer understands a small
	 * subset and would print plain text unchanged. Printing it raw is not an
	 * option: a page of literal asterisks reads as a bug.
	 */
	private static function paragraphs( string $text ): string {
		$blocks = preg_split( '/\R\s*\R/u', $text );
		$html   = '';
		foreach ( (array) $blocks as $block ) {
			$block = trim( (string) $block );
			if ( '' === $block ) {
				continue;
			}

			// A run of two or more bullet lines is a list wherever it sits, because
			// the api routinely puts one straight after a sentence with no blank
			// line between, and requiring a whole block of bullets printed those
			// hyphens inline as prose. A single bullet line still stays prose:
			// that guard exists so a dash used mid-sentence is not promoted.
			$lines   = (array) preg_split( '/\R/u', $block );
			$prose   = [];
			$bullets = [];

			$flush = function () use ( &$prose, &$bullets, &$html ) {
				if ( count( $bullets ) > 1 ) {
					if ( ! empty( $prose ) ) {
						$html .= '<p>' . nl2br( self::inline( implode( "\n", $prose ) ) ) . '</p>';
						$prose = [];
					}
					$items = '';
					foreach ( $bullets as $line ) {
						$items .= '<li>' . self::inline( (string) preg_replace( '/^\s*[-*]\s+/u', '', $line ) ) . '</li>';
					}
					$html .= '<ul>' . $items . '</ul>';
				} else {
					$prose = array_merge( $prose, $bullets );
				}
				$bullets = [];
			};

			foreach ( $lines as $line ) {
				$line = (string) $line;
				if ( preg_match( '/^\s*[-*]\s+\S/u', $line ) ) {
					$bullets[] = $line;
					continue;
				}
				$flush();
				if ( '' !== trim( $line ) ) {
					$prose[] = $line;
				}
			}
			$flush();

			// Single newlines inside a paragraph are wrapping, not structure.
			if ( ! empty( $prose ) ) {
				$html .= '<p>' . nl2br( self::inline( implode( "\n", $prose ) ) ) . '</p>';
			}
		}
		return $html;
	}

	/**
	 * Escapes first, then re-introduces the two inline marks the api uses. Order
	 * matters: escaping afterwards would neutralise the tags we just added, and
	 * emphasis before bold would eat the inner asterisks of a bold run.
	 */
	private static function inline( string $text ): string {
		$escaped = esc_html( $text );
		$escaped = (string) preg_replace( '/\*\*(?=\S)(.+?)(?<=\S)\*\*/su', '<strong>$1</strong>', $escaped );
		return (string) preg_replace( '/(?<!\*)\*(?=\S)([^*]+?)(?<=\S)\*(?!\*)/su', '<em>$1</em>', $escaped );
	}

	private static function detail_list( array $rows ): string {
		if ( empty( $rows ) ) {
			return '';
		}
		$html = '<dl class="astroway-card__details">';
		foreach ( $rows as $label => $value ) {
			$html .= '<dt>' . esc_html( (string) $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd>';
		}
		return $html . '</dl>';
	}

	private static function number( float $value, int $decimals ): string {
		return function_exists( 'number_format_i18n' )
			? number_format_i18n( $value, $decimals )
			: number_format( $value, $decimals );
	}
	/*
	 * ---------------------------------------------------------------------
	 * The sky right now: retrogrades, void of course Moon, planetary hours.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A hand-built card that needs an API key, or a note saying why there is none.
	 *
	 * The mirror of widget() for the keyed half of the api, differing in two
	 * ways. None of these endpoints has an embed route, so there is no iframe to
	 * degrade to and a failure means a note for the administrator and nothing
	 * for the visitor. And the reading is worked out here rather than stored:
	 * the payload says when Mercury stations, and whether that has happened yet
	 * is a question about the current second, not about the cached answer.
	 *
	 * @since 1.3.0
	 *
	 * @param string   $tag    Shortcode tag, used in the administrator notes.
	 * @param string   $widget One of the keyed widget keys handled below.
	 * @param array    $params Attributes, already sanitised by the caller.
	 * @param int|null $now    The moment to answer about. Overridable so a test
	 *                         can ask about a station it knows the date of.
	 */
	public static function keyed( string $tag, string $widget, array $params, ?int $now = null ): string {
		if ( ! ( new ApiClient() )->has_key() ) {
			return self::admin_note(
				sprintf(
					/* translators: %s = shortcode tag */
					__( '%s needs an API key: paste one in AstroWay, API Key. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']'
				)
			);
		}

		// Refusing is not the same as failing, and telling an editor "try again"
		// would send them looking for a fault that is not there.
		if ( 'planetary_hours' === $widget && ! Sky::has_coordinates( $params ) ) {
			return self::admin_note(
				sprintf(
					/* translators: 1: shortcode tag, 2: comma-separated attribute names */
					__( '%1$s is missing required attributes: %2$s. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']',
					'latitude, longitude'
				)
			);
		}

		$now    = null === $now ? time() : $now;
		$lang   = Plugin::resolve_lang( $params['lang'] ?? '' );
		$markup = '';

		switch ( $widget ) {
			case 'retrograde':
			case 'retrogrades':
				$data = Sky::retrogrades( $now );
				if ( is_array( $data ) ) {
					$markup = 'retrograde' === $widget
						? self::retrograde_card( $data, $lang, $params, $now )
						: self::retrograde_board( $data, $lang, $now );
				}
				break;
			case 'moon_voc':
				$data   = Sky::moon_voc( $params, $now );
				$markup = is_array( $data ) ? self::moon_voc_card( $data, $lang, $params, $now ) : '';
				break;
			case 'planetary_hours':
				$data   = Sky::planetary_hours( $params, $now );
				$markup = is_array( $data ) ? self::planetary_hours_card( $data, $lang, $params, $now ) : '';
				break;
			case 'yearly_horoscope':
				$data   = Signs::yearly_horoscope( $params, $now );
				$markup = is_array( $data ) ? self::horoscope_card( 'yearly_horoscope', $data, $lang, $params ) : '';
				break;
			case 'zodiac_compatibility':
				$data   = Signs::compatibility( $params );
				$markup = is_array( $data ) ? self::compatibility_card( $data, $lang, $params ) : '';
				break;
			case 'chinese_zodiac':
				$data   = Signs::chinese_zodiac( $params );
				$markup = is_array( $data ) ? self::chinese_card( $data, $lang ) : '';
				break;
		}

		if ( '' === $markup ) {
			return self::admin_note(
				sprintf(
					/* translators: %s = shortcode tag */
					__( '%s could not be loaded just now. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']'
				)
			);
		}
		return $markup;
	}

	/**
	 * Whether one planet is retrograde, and when that changes.
	 *
	 * The question the internet asks about Mercury, and the one this plugin has
	 * had no answer for. The same card answers it for the other seven that
	 * station, which is why the tag carries a planet attribute and the famous
	 * one has an alias of its own.
	 */
	private static function retrograde_card( array $data, string $lang, array $params, int $now ): string {
		$slug = strtolower( trim( (string) ( $params['planet'] ?? '' ) ) );
		if ( ! isset( Sky::PLANETS[ $slug ] ) ) {
			$slug = 'mercury';
		}
		$name   = self::planet_label( ucfirst( $slug ) );
		$offset = Sky::offset( '', $now );

		list( $current, $next ) = Sky::state_at( Sky::periods_of( $data, Sky::PLANETS[ $slug ] ), $now );

		if ( null === $current && null === $next ) {
			return '';
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html(
			sprintf(
				/* translators: %s = planet name */
				__( 'Is %s retrograde?', 'astroway' ),
				$name
			)
		) . '</h3></header>';

		if ( null !== $current ) {
			$inner .= '<p class="astroway-card__lead astroway-card__lead--yes">' . esc_html(
				sprintf(
					/* translators: %s = planet name */
					__( 'Yes, %s is retrograde right now.', 'astroway' ),
					$name
				)
			) . '</p>';
			$rows = [
				__( 'Retrograde since', 'astroway' ) => self::sky_date( $current['start'], $offset ),
				__( 'Direct again on', 'astroway' )  => self::sky_date( $current['end'], $offset ),
				__( 'Days left', 'astroway' )        => self::days_between( $now, $current['end'] ),
			];
		} else {
			$inner .= '<p class="astroway-card__lead astroway-card__lead--no">' . esc_html(
				sprintf(
					/* translators: %s = planet name */
					__( 'No, %s is direct right now.', 'astroway' ),
					$name
				)
			) . '</p>';
			$rows = [
				__( 'Next retrograde', 'astroway' ) => self::sky_date( $next['start'], $offset ),
				__( 'Ends', 'astroway' )            => self::sky_date( $next['end'], $offset ),
				__( 'Days until', 'astroway' )      => self::days_between( $now, $next['start'] ),
			];
		}

		return self::shell( 'retrograde', $lang, $inner . self::detail_list( $rows ) );
	}

	/** Every planet that stations, and what each is doing today. */
	private static function retrograde_board( array $data, string $lang, int $now ): string {
		$offset    = Sky::offset( '', $now );
		$rows      = [];
		$backwards = [];

		foreach ( Sky::PLANETS as $slug => $planet_id ) {
			$name                   = self::planet_label( ucfirst( $slug ) );
			list( $current, $next ) = Sky::state_at( Sky::periods_of( $data, $planet_id ), $now );

			if ( null !== $current ) {
				$backwards[] = $name;
				$status      = __( 'Retrograde', 'astroway' );
				$dates       = self::sky_range( $current['start'], $current['end'], $offset );
			} elseif ( null !== $next ) {
				$status = __( 'Direct', 'astroway' );
				$dates  = self::sky_range( $next['start'], $next['end'], $offset );
			} else {
				$status = __( 'Direct', 'astroway' );
				$dates  = '';
			}

			$rows[] = [
				'cells'   => [ $name, $status, $dates ],
				'current' => null !== $current,
			];
		}

		$lead = empty( $backwards )
			? __( 'Nothing is retrograde right now.', 'astroway' )
			: sprintf(
				/* translators: %s = comma-separated list of planet names */
				__( 'Retrograde right now: %s.', 'astroway' ),
				implode( ', ', $backwards )
			);

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Retrograde planets', 'astroway' ) . '</h3></header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( $lead ) . '</p>';
		$inner .= self::sky_table(
			[
				__( 'Planet', 'astroway' ),
				__( 'Status', 'astroway' ),
				// Named for what the dates are, not for where they sit: on a
				// direct planet the row shows the retrograde still to come, and
				// a column headed "Period" would read as the direct one.
				__( 'Retrograde period', 'astroway' ),
			],
			$rows
		);

		return self::shell( 'retrogrades', $lang, $inner );
	}

	/**
	 * Whether the Moon is void of course, and the windows around now.
	 *
	 * The api returns the windows and no opinion about which one contains this
	 * second, which is the right division of labour: the answer is cached and
	 * the second is not.
	 */
	private static function moon_voc_card( array $data, string $lang, array $params, int $now ): string {
		$offset  = Sky::offset( $params['timezone_offset'] ?? '', $now );
		$windows = [];

		foreach ( ( isset( $data['periods'] ) && is_array( $data['periods'] ) ? $data['periods'] : [] ) as $period ) {
			if ( ! is_array( $period ) ) {
				continue;
			}
			$start = strtotime( (string) ( $period['startDate'] ?? '' ) );
			$end   = strtotime( (string) ( $period['endDate'] ?? '' ) );
			if ( false === $start || false === $end ) {
				continue;
			}
			$windows[] = [
				'start'  => $start,
				'end'    => $end,
				'hours'  => (float) ( $period['durationHours'] ?? 0 ),
				'aspect' => is_array( $period['lastAspect'] ?? null ) ? $period['lastAspect'] : [],
				'sign'   => isset( $period['nextSignIndex'] ) ? (int) $period['nextSignIndex'] : -1,
			];
		}

		if ( empty( $windows ) ) {
			return '';
		}

		$open = null;
		$next = null;
		foreach ( $windows as $window ) {
			if ( $window['start'] <= $now && $now <= $window['end'] ) {
				$open = $window;
			} elseif ( $window['start'] > $now && null === $next ) {
				$next = $window;
			}
		}

		if ( null !== $open ) {
			$lead = sprintf(
				/* translators: %s = a time of day */
				__( 'The Moon is void of course until %s.', 'astroway' ),
				self::sky_time( $open['end'], $offset )
			);
		} elseif ( null !== $next ) {
			$lead = sprintf(
				/* translators: %s = a date and time */
				__( 'The Moon is not void of course. The next window opens %s.', 'astroway' ),
				self::sky_moment( $next['start'], $offset )
			);
		} else {
			$lead = __( 'The Moon is not void of course.', 'astroway' );
		}

		$rows = [];
		foreach ( $windows as $window ) {
			$rows[] = [
				'cells'   => [
					self::sky_moment( $window['start'], $offset ),
					self::sky_moment( $window['end'], $offset ),
					self::duration_label( $window['hours'] ),
					self::last_aspect_label( $window['aspect'] ),
					$window['sign'] >= 0 && $window['sign'] < 12
						? self::sign_label( self::sign_of( $window['sign'] * 30.0 ) )
						: '',
				],
				'current' => null !== $open && $window['start'] === $open['start'],
			];
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Void of course Moon', 'astroway' ) . '</h3></header>';
		$inner .= '<p class="astroway-card__lead">' . esc_html( $lead ) . '</p>';
		$inner .= self::sky_table(
			[
				__( 'Starts', 'astroway' ),
				__( 'Ends', 'astroway' ),
				__( 'Length', 'astroway' ),
				__( 'Last aspect', 'astroway' ),
				__( 'Next sign', 'astroway' ),
			],
			$rows
		);

		return self::shell( 'moon_voc', $lang, $inner );
	}

	/**
	 * The twenty-four planetary hours of a day, with the one we are in marked.
	 *
	 * The current hour is worked out here rather than read off the payload. The
	 * api will mark it, but only when the caller sends `atLocalHour`, and doing
	 * that would put the clock inside the cache key: one entry per hour per
	 * place instead of one per day, and the entry stale the moment the hour
	 * turned. The table keeps for the day, so the marking cannot.
	 *
	 * Order matters below. A polar day answers with an empty `hours`, so the
	 * state has to be read before the emptiness is judged, or the card silently
	 * renders nothing on exactly the dates it has something to explain.
	 */
	private static function planetary_hours_card( array $data, string $lang, array $params, int $now ): string {
		$date  = (string) ( $data['date'] ?? '' );
		$polar = (string) ( $data['sunTimes']['polarState'] ?? 'normal' );
		if ( '' !== $polar && 'normal' !== $polar ) {
			return self::polar_card( $date, $polar, $lang );
		}

		$hours = isset( $data['hours'] ) && is_array( $data['hours'] ) ? $data['hours'] : [];
		if ( empty( $hours ) ) {
			return '';
		}

		$offset   = Sky::offset( $params['timezone_offset'] ?? '', $now );
		$elapsed  = Sky::hour_of_day( $date, $offset, $now );
		$midnight = strtotime( $date . ' 00:00:00 UTC' );
		$midnight = false === $midnight ? $now : $midnight - (int) round( $offset * HOUR_IN_SECONDS );

		$rows    = [];
		$running = null;
		foreach ( $hours as $hour ) {
			if ( ! is_array( $hour ) ) {
				continue;
			}
			$from    = (float) ( $hour['startHour'] ?? 0 );
			$to      = (float) ( $hour['endHour'] ?? 0 );
			$planet  = self::planet_label( (string) ( $hour['planetName'] ?? '' ) );
			$is_now  = null !== $elapsed && $elapsed >= $from && $elapsed < $to;
			$running = $is_now ? [
				'planet' => $planet,
				'to'     => $to,
			] : $running;

			$rows[] = [
				'cells'   => [
					(string) ( $hour['number'] ?? '' ),
					$planet,
					self::sky_time( $midnight + (int) round( $from * HOUR_IN_SECONDS ), $offset ),
					self::sky_time( $midnight + (int) round( $to * HOUR_IN_SECONDS ), $offset ),
					empty( $hour['isDaytime'] ) ? __( 'Night', 'astroway' ) : __( 'Day', 'astroway' ),
				],
				'current' => $is_now,
			];
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$ruler = self::planet_label( (string) ( $data['dayRulerPlanetName'] ?? '' ) );
		$lead  = '' === $ruler
			? ''
			: sprintf(
				/* translators: %s = planet name */
				__( 'The day is ruled by %s.', 'astroway' ),
				$ruler
			);

		if ( null !== $running ) {
			$lead .= ( '' === $lead ? '' : ' ' ) . sprintf(
				/* translators: 1: planet name, 2: a time of day */
				__( 'The hour of %1$s runs until %2$s.', 'astroway' ),
				$running['planet'],
				self::sky_time( $midnight + (int) round( $running['to'] * HOUR_IN_SECONDS ), $offset )
			);
		}

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Planetary hours', 'astroway' ) . '</h3>';
		$inner .= self::date_line( $date );
		$inner .= '</header>';
		if ( '' !== $lead ) {
			$inner .= '<p class="astroway-card__lead">' . esc_html( $lead ) . '</p>';
		}

		$inner .= self::sky_table(
			[
				__( 'Hour', 'astroway' ),
				__( 'Ruler', 'astroway' ),
				__( 'From', 'astroway' ),
				__( 'To', 'astroway' ),
				__( 'Part of day', 'astroway' ),
			],
			$rows
		);

		return self::shell( 'planetary_hours', $lang, $inner );
	}

	/**
	 * What to say where the Sun neither rises nor sets.
	 *
	 * A planetary hour is a twelfth of the daylight, so above the Arctic circle
	 * in June there are no daylight hours to divide and in December there are no
	 * night ones. The api says so in `polarState` and sends an empty `hours`.
	 *
	 * Its own `warning` is a serviceable English sentence, and it is deliberately
	 * not printed: it names the Sun's altitude in degrees, which answers a
	 * question the reader did not ask, and it is the one string on this card that
	 * no locale could translate.
	 */
	private static function polar_card( string $date, string $state, string $lang ): string {
		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Planetary hours', 'astroway' ) . '</h3>';
		$inner .= self::date_line( $date );
		$inner .= '</header>';
		$inner .= '<p class="astroway-card__lead astroway-card__lead--no">' . esc_html(
			'polar-day' === $state
				? __( 'The Sun does not set at this latitude on this date, so there are no planetary hours to divide.', 'astroway' )
				: __( 'The Sun does not rise at this latitude on this date, so there are no planetary hours to divide.', 'astroway' )
		) . '</p>';

		return self::shell( 'planetary_hours', $lang, $inner );
	}

	/**
	 * Two birth charts read against each other: a score and the aspects behind it.
	 *
	 * Which aspects. The api sorts all of them by orb and says the first six are
	 * the six its own embed draws. Checked against both on the same pair on
	 * 2026-08-23, and they are not: the raw first six included Chiron opposite
	 * Neptune and the Node conjunct Lilith, and the embed showed neither. It
	 * filters to the ten classical bodies first, and filtering the same way here
	 * reproduces its six exactly. So that is what the table holds, and the whole
	 * matrix, points included, sits one click away.
	 *
	 * Names are the author's and never leave the server. The route takes a name
	 * per chart and does nothing with it, so sending them would only split the
	 * cache between two authors who wrote the same pair of birth moments.
	 */
	private static function synastry_card( array $data, string $lang, array $params ): string {
		$aspects = isset( $data['aspects'] ) && is_array( $data['aspects'] ) ? $data['aspects'] : [];
		$score   = isset( $data['score'] ) && is_numeric( $data['score'] ) ? (int) round( (float) $data['score'] ) : null;
		if ( null === $score && empty( $aspects ) ) {
			return '';
		}

		$first  = trim( (string) ( $params['name_a'] ?? '' ) );
		$second = trim( (string) ( $params['name_b'] ?? '' ) );
		$first  = '' === $first ? __( 'First chart', 'astroway' ) : $first;
		$second = '' === $second ? __( 'Second chart', 'astroway' ) : $second;

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Compatibility', 'astroway' ) . '</h3>';
		$inner .= '</header>';
		$inner .= self::score_block( $score, (string) ( $data['label'] ?? '' ) );

		// Phrased as a label with a figure rather than a sentence that agrees with
		// it. The plugin ships twenty locales and has never carried a msgid_plural;
		// the first one would go through a translation pipeline that has never
		// produced a msgstr[2], which is a poor place to find out.
		$total = isset( $data['count'] ) && is_numeric( $data['count'] ) ? (int) $data['count'] : count( $aspects );
		if ( $total > 0 ) {
			$inner .= '<div class="astroway-card__body"><p>' . esc_html(
				sprintf(
					/* translators: %d = how many aspects the two charts make between them */
					__( 'Aspects between the two charts: %d', 'astroway' ),
					$total
				)
			) . '</p></div>';
		}

		$wanted = self::aspect_count( $params );
		$close  = self::aspect_rows( self::classical_only( $aspects ), $wanted );
		if ( ! empty( $close ) ) {
			$inner .= '<h4 class="astroway-card__subtitle">' . esc_html__( 'Closest aspects', 'astroway' ) . '</h4>';
			$inner .= self::aspect_grid( $first, $second, $close );
		}

		if ( count( $aspects ) > count( $close ) ) {
			// Two wordings were wrong before this one. "Points included" collides
			// with the word for a score in half the target languages, and there is
			// a score printed directly above. "Show all %d aspects" then put the
			// number next to the noun, which four Slavic locales must decline: for
			// 45 the plural is aspektów, not aspekty. The count in brackets agrees
			// with nothing, so it is right for every language and every number.
			$inner .= '<details class="astroway-card__more"><summary>' . esc_html(
				sprintf(
					/* translators: %d = the full number of aspects between the two charts */
					__( 'Show all aspects (%d)', 'astroway' ),
					count( $aspects )
				)
			) . '</summary>' . self::aspect_grid( $first, $second, self::aspect_rows( $aspects, 0 ) ) . '</details>';
		}

		return self::shell( 'synastry', $lang, $inner );
	}

	/** The ten bodies the embed counts as planets, in the order the api sends them. */
	private static function classical_only( array $aspects ): array {
		$bodies = [ 'Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto' ];
		return array_values(
			array_filter(
				$aspects,
				static function ( $aspect ) use ( $bodies ) {
					return is_array( $aspect )
						&& in_array( (string) ( $aspect['planetA'] ?? '' ), $bodies, true )
						&& in_array( (string) ( $aspect['planetB'] ?? '' ), $bodies, true );
				}
			)
		);
	}

	/** Table rows out of aspect payloads. A count of zero means all of them. */
	private static function aspect_rows( array $aspects, int $limit ): array {
		$rows = [];
		foreach ( $aspects as $aspect ) {
			if ( ! is_array( $aspect ) ) {
				continue;
			}
			$rows[] = [
				'cells' => [
					self::planet_label( (string) ( $aspect['planetA'] ?? '' ) ),
					self::aspect_label( (string) ( $aspect['aspect'] ?? '' ) ),
					self::planet_label( (string) ( $aspect['planetB'] ?? '' ) ),
					sprintf( '%.1f°', (float) ( $aspect['orb'] ?? 0 ) ),
					empty( $aspect['applying'] ) ? __( 'Separating', 'astroway' ) : __( 'Applying', 'astroway' ),
				],
			];
			if ( $limit > 0 && count( $rows ) >= $limit ) {
				break;
			}
		}
		return $rows;
	}

	/**
	 * The aspect table, headed by whose planet is in which column.
	 *
	 * Without the two names it is unreadable: "Moon conjunct Mercury" says
	 * nothing about whose Moon, and in synastry that is the entire meaning.
	 */
	private static function aspect_grid( string $first, string $second, array $rows ): string {
		return self::sky_table(
			[
				$first,
				__( 'Aspect', 'astroway' ),
				$second,
				__( 'Orb', 'astroway' ),
				__( 'Trend', 'astroway' ),
			],
			$rows
		);
	}

	/**
	 * The score, as a figure and as a bar.
	 *
	 * The bar carries aria-hidden: it is the number that was just written out in
	 * words, drawn again, and a screen reader announcing it twice would be
	 * repeating itself rather than adding anything.
	 */
	private static function score_block( ?int $score, string $label ): string {
		if ( null === $score ) {
			return '';
		}
		$score = max( 0, min( 100, $score ) );
		$named = self::compat_label( $label );

		$html  = '<p class="astroway-card__score">';
		$html .= '<strong class="astroway-card__score-value">' . esc_html( (string) $score ) . '<span>%</span></strong>';
		if ( '' !== $named ) {
			$html .= '<span class="astroway-card__score-label">' . esc_html( $named ) . '</span>';
		}
		$html .= '</p>';

		return $html . sprintf(
			'<div class="astroway-card__meter" aria-hidden="true"><span style="inline-size:%d%%"></span></div>',
			$score
		);
	}

	/**
	 * The word the api puts on a score.
	 *
	 * The vocabulary is not in the spec. Thirteen live pairs spread from 37 to 92
	 * returned three words and no fourth, so those three are translated and
	 * anything else is printed as it arrived: an English word in a German card
	 * is poor, an empty space where the verdict should be is worse.
	 */
	private static function compat_label( string $raw ): string {
		$labels = [
			'harmonious' => __( 'Harmonious', 'astroway' ),
			'balanced'   => __( 'Balanced', 'astroway' ),
			'mixed'      => __( 'Mixed', 'astroway' ),
		];
		$key    = strtolower( trim( $raw ) );
		return $labels[ $key ] ?? ucfirst( $key );
	}

	/** How many rows the author asked for, between one and twenty. Six by default. */
	private static function aspect_count( array $params ): int {
		$raw = (int) ( $params['aspects'] ?? 0 );
		return $raw > 0 ? min( 20, $raw ) : 6;
	}

	/**
	 * Two signs read together.
	 *
	 * Prose and nothing else: the endpoint returns an essay and no number. A
	 * percentage would have to be invented here, and a figure with no
	 * calculation behind it is worse than no figure.
	 */
	private static function compatibility_card( array $data, string $lang, array $params ): string {
		$body = trim( (string) ( $data['horoscope'] ?? '' ) );
		if ( '' === $body ) {
			return '';
		}

		$one = self::sign_label( (string) ( $data['sign1'] ?? ( $params['sign1'] ?? '' ) ) );
		$two = self::sign_label( (string) ( $data['sign2'] ?? ( $params['sign2'] ?? '' ) ) );

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html(
			sprintf(
				/* translators: 1: zodiac sign name, 2: zodiac sign name */
				__( '%1$s and %2$s', 'astroway' ),
				$one,
				$two
			)
		) . '</h3></header>';
		$inner .= '<div class="astroway-card__body">' . self::paragraphs( $body ) . '</div>';

		return self::shell( 'zodiac_compatibility', $lang, $inner );
	}

	/**
	 * The Chinese animal of a birth year, with its element and pillar.
	 *
	 * The api sends the animal as an emoji beside its English name. The emoji
	 * is printed as decoration and hidden from assistive technology, because
	 * "horse face" read aloud in front of the word Horse is noise.
	 */
	private static function chinese_card( array $data, string $lang ): string {
		$animal = trim( (string) ( $data['animal'] ?? '' ) );
		if ( '' === $animal ) {
			return '';
		}

		$element = is_array( $data['element'] ?? null ) ? $data['element'] : [];
		$glyph   = trim( (string) ( $data['glyph'] ?? '' ) );

		$inner  = '<header class="astroway-card__header">';
		$inner .= '<h3 class="astroway-card__title">' . esc_html__( 'Chinese zodiac', 'astroway' ) . '</h3>';
		if ( ! empty( $data['solarYear'] ) ) {
			$inner .= '<span class="astroway-card__meta">' . esc_html( (string) (int) $data['solarYear'] ) . '</span>';
		}
		$inner .= '</header>';

		$inner .= '<p class="astroway-card__lead">';
		if ( '' !== $glyph ) {
			$inner .= '<span class="astroway-card__glyph" aria-hidden="true">' . esc_html( $glyph ) . '</span> ';
		}
		$inner .= esc_html( self::animal_label( $animal ) ) . '</p>';

		$rows = [];
		if ( ! empty( $element['fixed'] ) ) {
			$rows[ __( 'Element', 'astroway' ) ] = self::element_label( (string) $element['fixed'] );
		}
		if ( ! empty( $element['cycling'] ) ) {
			// The element of the year itself, which turns every two years and is
			// what "Metal Horse" names; the fixed one belongs to the animal.
			$rows[ __( 'Year element', 'astroway' ) ] = self::element_label( (string) $element['cycling'] );
		}
		if ( isset( $element['yin'] ) ) {
			$rows[ __( 'Polarity', 'astroway' ) ] = $element['yin'] ? __( 'Yin', 'astroway' ) : __( 'Yang', 'astroway' );
		}
		if ( ! empty( $data['pillar'] ) ) {
			$rows[ __( 'Pillar', 'astroway' ) ] = (string) $data['pillar'];
		}

		return self::shell( 'chinese_zodiac', $lang, $inner . self::detail_list( $rows ) );
	}

	/** The twelve animals of the cycle. */
	private static function animal_label( string $raw ): string {
		$labels = [
			'Rat'     => __( 'Rat', 'astroway' ),
			'Ox'      => __( 'Ox', 'astroway' ),
			'Tiger'   => __( 'Tiger', 'astroway' ),
			'Rabbit'  => __( 'Rabbit', 'astroway' ),
			'Dragon'  => __( 'Dragon', 'astroway' ),
			'Snake'   => __( 'Snake', 'astroway' ),
			'Horse'   => __( 'Horse', 'astroway' ),
			'Goat'    => __( 'Goat', 'astroway' ),
			'Monkey'  => __( 'Monkey', 'astroway' ),
			'Rooster' => __( 'Rooster', 'astroway' ),
			'Dog'     => __( 'Dog', 'astroway' ),
			'Pig'     => __( 'Pig', 'astroway' ),
		];
		return $labels[ trim( $raw ) ] ?? $raw;
	}

	/** The five phases. Not the four Western elements: there is no Air here. */
	private static function element_label( string $raw ): string {
		$labels = [
			'Wood'  => __( 'Wood', 'astroway' ),
			'Fire'  => __( 'Fire', 'astroway' ),
			'Earth' => __( 'Earth', 'astroway' ),
			'Metal' => __( 'Metal', 'astroway' ),
			'Water' => __( 'Water', 'astroway' ),
		];
		return $labels[ trim( $raw ) ] ?? $raw;
	}

	/**
	 * A table for the sky cards.
	 *
	 * Rows carry a `current` flag rather than a class name so the decision about
	 * what "now" looks like stays in the stylesheet. Wrapped in a scroller for
	 * the same reason the generic renderer wraps its tables: five columns on a
	 * 360px screen is wider than the screen, and a card that pushes the page
	 * sideways is worse than one that scrolls inside itself.
	 *
	 * @param array $head Column labels.
	 * @param array $rows Each `[ 'cells' => list<string>, 'current' => bool ]`.
	 */
	private static function sky_table( array $head, array $rows ): string {
		if ( empty( $rows ) ) {
			return '';
		}

		$html = '<div class="astroway-card__scroll"><table class="astroway-card__placements"><thead><tr>';
		foreach ( $head as $label ) {
			$html .= '<th scope="col">' . esc_html( (string) $label ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$current = ! empty( $row['current'] );
			$html   .= $current
				? '<tr class="astroway-card__row--current" aria-current="true">'
				: '<tr>';
			foreach ( (array) ( $row['cells'] ?? [] ) as $cell ) {
				$html .= '<td>' . esc_html( (string) $cell ) . '</td>';
			}
			$html .= '</tr>';
		}

		return $html . '</tbody></table></div>';
	}

	/**
	 * The offset the reader is being answered in, as a zone wp_date understands.
	 *
	 * Not the site's zone: an author may pin `timezone_offset` on the shortcode,
	 * and the hours were then computed for that horizon, so printing them in the
	 * site's own zone would move every one of them.
	 */
	private static function sky_zone( float $offset ): \DateTimeZone {
		$minutes = (int) round( $offset * 60 );
		$sign    = $minutes < 0 ? '-' : '+';
		$minutes = abs( $minutes );
		return new \DateTimeZone( sprintf( '%s%02d:%02d', $sign, intdiv( $minutes, 60 ), $minutes % 60 ) );
	}

	/** A date in the reader's own offset, in the format and language of the site. */
	private static function sky_date( int $utc, float $offset ): string {
		return (string) wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $utc, self::sky_zone( $offset ) );
	}

	/** A time of day in the reader's own offset. */
	private static function sky_time( int $utc, float $offset ): string {
		return (string) wp_date( (string) get_option( 'time_format', 'H:i' ), $utc, self::sky_zone( $offset ) );
	}

	/** Date and time together, for a moment that may not be today. */
	private static function sky_moment( int $utc, float $offset ): string {
		return self::sky_date( $utc, $offset ) . ', ' . self::sky_time( $utc, $offset );
	}

	/** Two dates as one range, collapsed when both fall on the same day. */
	private static function sky_range( int $from, int $to, float $offset ): string {
		$start = self::sky_date( $from, $offset );
		$end   = self::sky_date( $to, $offset );
		return $start === $end ? $start : $start . ' – ' . $end;
	}

	/** Whole days between two moments, rounded up, never negative. */
	private static function days_between( int $from, int $to ): string {
		return self::number( (float) max( 0, (int) ceil( ( $to - $from ) / DAY_IN_SECONDS ) ), 0 );
	}

	/**
	 * A length in hours as hours and minutes.
	 *
	 * Void of course windows run from half a minute to two days, so printing
	 * "0.48 hours" for one and "20.64 hours" for the next is a worse answer than
	 * either deserves.
	 */
	private static function duration_label( float $hours ): string {
		$minutes = (int) round( $hours * 60 );
		if ( $minutes < 60 ) {
			return sprintf(
				/* translators: %d = number of minutes */
				__( '%d min', 'astroway' ),
				$minutes
			);
		}
		return sprintf(
			/* translators: 1: whole hours, 2: minutes within the hour */
			__( '%1$d h %2$02d min', 'astroway' ),
			intdiv( $minutes, 60 ),
			$minutes % 60
		);
	}

	/**
	 * "trine Mercury": the Moon's last aspect before the window opened.
	 *
	 * The aspect first, the planet second, because the subject is the Moon and
	 * it is not named: written the other way round the cell reads "Mercury
	 * trine" and leaves the reader looking for what Mercury is trine to.
	 */
	private static function last_aspect_label( array $aspect ): string {
		$planet = self::planet_by_id( isset( $aspect['planetId'] ) ? (int) $aspect['planetId'] : -1 );
		$name   = self::aspect_label( (string) ( $aspect['aspectName'] ?? '' ) );
		if ( '' === $planet ) {
			return $name;
		}
		// Both halves are already translated and the join is a space. Given to
		// gettext it would be a msgid of two placeholders and nothing else,
		// which a machine translator has no way to improve and every way to
		// damage.
		return $name . ' ' . $planet;
	}

	/**
	 * Planet name for the numeric identifier the api uses.
	 *
	 * Only the void of course payload needs this: everywhere else the api sends
	 * the name beside the number, and this is the one place it sends the number
	 * alone.
	 */
	private static function planet_by_id( int $id ): string {
		$names = [ 'Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto' ];
		return isset( $names[ $id ] ) ? self::planet_label( $names[ $id ] ) : '';
	}

	/**
	 * Render an answer whose shape we were not told in advance.
	 *
	 * Used by the generated shortcodes: nearly seven hundred endpoints, each
	 * with its own response, none of them worth a hand-written template until it
	 * proves popular. What they have in common is JSON built out of four things,
	 * and each gets the presentation it deserves:
	 *
	 *   scalars                 a definition list
	 *   uniform object lists    a table, columns taken from the first row
	 *   nested objects          a subsection under a heading
	 *   long prose              a paragraph
	 *
	 * The rules are about shape, not about field names. A heuristic keyed on
	 * names ("call the field named `meaning` the lede") reads well on the
	 * endpoints it was written against and mislabels the rest, and there is no
	 * way to check seven hundred of them by eye.
	 *
	 * @since 1.2.0
	 *
	 * @param string $tag  Shortcode tag, used for the CSS hook only.
	 * @param array  $data Decoded `data` from the api.
	 * @param string $lang Language the answer was asked in.
	 */
	public static function generic( string $tag, array $data, string $lang ): string {
		self::$generic_lang = $lang;
		$body               = self::generic_body( $data, 0 );
		if ( '' === $body ) {
			return '';
		}
		$slug = str_replace( '_', '-', preg_replace( '/^astroway_/', '', $tag ) );
		return sprintf(
			'<div class="astroway-card astroway-card--generic astroway-card--%s" lang="%s">%s</div>',
			esc_attr( $slug ),
			esc_attr( $lang ),
			$body
		);
	}

	/**
	 * Language the current generic render was asked in.
	 *
	 * Held here rather than threaded through six recursive methods that have no
	 * other use for it. Safe because a render is synchronous and never nests:
	 * one shortcode finishes before the next begins.
	 */
	private static string $generic_lang = 'en';

	/** Deepest nesting rendered before the rest is folded away. */
	private const GENERIC_MAX_DEPTH = 3;

	/** Rows of a generated table beyond which the rest is not worth printing. */
	private const GENERIC_MAX_ROWS = 60;

	private static function generic_body( array $data, int $depth ): string {
		$scalars = [];
		$blocks  = '';

		foreach ( self::fold_localised( $data ) as $key => $value ) {
			$label = self::humanise( (string) $key );

			if ( is_scalar( $value ) || null === $value ) {
				$text = self::scalar_text( $value );
				if ( '' === $text ) {
					continue;
				}
				// A sentence in a definition list is unreadable; it wants to be
				// a paragraph. The cutoff is length, not the field's name.
				if ( strlen( $text ) > 120 ) {
					$blocks .= self::generic_prose( $label, $text, $depth );
				} else {
					$scalars[ $label ] = $text;
				}
				continue;
			}

			if ( is_array( $value ) && [] === $value ) {
				continue;
			}

			if ( is_array( $value ) && self::is_list( $value ) ) {
				$blocks .= self::generic_list( $label, $value, $depth );
				continue;
			}

			if ( is_array( $value ) ) {
				$blocks .= self::generic_section( $label, $value, $depth );
			}
		}

		return self::detail_list( $scalars ) . $blocks;
	}

	private static function generic_prose( string $label, string $text, int $depth ): string {
		return self::generic_heading( $label, $depth )
			. '<div class="astroway-card__body">' . self::paragraphs( $text ) . '</div>';
	}

	/** A list: of scalars, of uniform objects, or of something else. */
	private static function generic_list( string $label, array $values, int $depth ): string {
		$scalar_only = true;
		foreach ( $values as $v ) {
			if ( ! is_scalar( $v ) && null !== $v ) {
				$scalar_only = false;
				break;
			}
		}

		if ( $scalar_only ) {
			$items = '';
			foreach ( $values as $v ) {
				$text = self::scalar_text( $v );
				if ( '' !== $text ) {
					$items .= '<li>' . esc_html( $text ) . '</li>';
				}
			}
			return '' === $items
				? ''
				: self::generic_heading( $label, $depth ) . '<ul class="astroway-card__themes">' . $items . '</ul>';
		}

		$table = self::generic_table( $values );
		if ( '' !== $table ) {
			return self::generic_heading( $label, $depth ) . $table;
		}

		// Ragged list of objects: each entry becomes its own small section.
		if ( $depth >= self::GENERIC_MAX_DEPTH ) {
			return '';
		}
		$out = self::generic_heading( $label, $depth );
		foreach ( array_slice( $values, 0, self::GENERIC_MAX_ROWS ) as $i => $v ) {
			if ( is_array( $v ) ) {
				$out .= self::generic_body( $v, $depth + 1 );
			}
		}
		return $out;
	}

	/**
	 * Whether an array is a plain list.
	 *
	 * Not `array_is_list()`: that is PHP 8.1, and WordPress only polyfills it
	 * from 6.5. This plugin supports PHP 7.4 and WordPress 6.3, where calling it
	 * is a fatal error rather than a wrong answer.
	 */
	private static function is_list( array $value ): bool {
		$i = 0;
		foreach ( $value as $key => $unused ) {
			if ( $key !== $i ) {
				return false;
			}
			++$i;
		}
		return true;
	}

	/**
	 * Collapse `name`, `nameRu`, `nameUk` down to the one the reader asked for.
	 *
	 * Several endpoints ship translations beside the canonical value rather than
	 * instead of it, and a table that prints all of them is three columns of the
	 * same word. The suffix has to be a known language, otherwise `startDeg` and
	 * `endDeg` would read as a "start" field localised into Deg.
	 *
	 * @param array $row Associative row.
	 * @return array Same row, with localised siblings folded into their base.
	 */
	private static function fold_localised( array $row ): array {
		$lang    = strtolower( substr( self::$generic_lang, 0, 2 ) );
		$dropped = [];
		$chosen  = [];

		foreach ( array_keys( $row ) as $key ) {
			$key = (string) $key;
			if ( ! preg_match( '/^([a-z][A-Za-z0-9]*?)([A-Z][a-z])$/', $key, $m ) ) {
				continue;
			}
			$base   = $m[1];
			$suffix = strtolower( $m[2] );
			if ( ! isset( $row[ $base ] ) || ! in_array( $suffix, Plugin::SUPPORTED_LANGS, true ) ) {
				continue;
			}
			$dropped[] = $key;
			if ( $suffix === $lang && is_scalar( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
				$chosen[ $base ] = $row[ $key ];
			}
		}

		if ( empty( $dropped ) ) {
			return $row;
		}

		$out = [];
		foreach ( $row as $key => $value ) {
			if ( in_array( (string) $key, $dropped, true ) ) {
				continue;
			}
			$out[ $key ] = $chosen[ $key ] ?? $value;
		}
		return $out;
	}

	/**
	 * One row with its nested objects folded in as `parent.child` columns.
	 *
	 * Only one level deep, and only scalars: deeper than that a table stops
	 * being readable, and the section renderer handles it better.
	 */
	private static function flatten_row( array $row ): array {
		$out = [];
		foreach ( self::fold_localised( $row ) as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$out[ (string) $key ] = $value;
				continue;
			}
			if ( is_array( $value ) && ! self::is_list( $value ) ) {
				foreach ( self::fold_localised( $value ) as $sub_key => $sub ) {
					if ( is_scalar( $sub ) || null === $sub ) {
						$out[ $key . '.' . $sub_key ] = $sub;
					}
				}
			}
		}
		return $out;
	}

	/**
	 * A table, when every row is an object sharing the same columns.
	 *
	 * Rows are flattened one level first. Without that the most useful column is
	 * routinely the one that disappears: `/nakshatras` returns a planet per row
	 * with the nakshatra itself as a nested object, so a scalar-only table lists
	 * longitudes and pada numbers and omits the thing the endpoint is named
	 * after.
	 *
	 * Returns '' when the rows disagree about their shape, because a table with
	 * half its cells empty is worse than the sections it replaced.
	 */
	private static function generic_table( array $rows ): string {
		$flat    = [];
		$columns = null;
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || self::is_list( $row ) ) {
				return '';
			}
			$flat_row = self::flatten_row( $row );
			if ( empty( $flat_row ) ) {
				return '';
			}
			$keys = array_keys( $flat_row );
			if ( null === $columns ) {
				$columns = $keys;
			} elseif ( $columns !== $keys ) {
				return '';
			}
			$flat[] = $flat_row;
		}
		if ( empty( $columns ) ) {
			return '';
		}
		$rows = $flat;

		$head = '';
		foreach ( $columns as $c ) {
			$head .= '<th scope="col">' . esc_html( self::humanise( $c ) ) . '</th>';
		}

		$body  = '';
		$shown = 0;
		foreach ( $rows as $row ) {
			if ( $shown >= self::GENERIC_MAX_ROWS ) {
				break;
			}
			$cells = '';
			foreach ( $columns as $c ) {
				$cells .= '<td>' . esc_html( self::scalar_text( $row[ $c ] ?? '' ) ) . '</td>';
			}
			$body .= '<tr>' . $cells . '</tr>';
			++$shown;
		}

		$note = count( $rows ) > $shown
			? '<caption>' . esc_html(
				sprintf(
					/* translators: 1: rows shown, 2: rows in total */
					__( 'Showing %1$d of %2$d', 'astroway' ),
					$shown,
					count( $rows )
				)
			) . '</caption>'
			: '';

		// Wrapped because a generated table has as many columns as the endpoint
		// has fields, and thirteen of them will not fit a phone or a sidebar.
		// The page itself must never scroll sideways; the table may.
		return '<div class="astroway-card__scroll"><table class="astroway-card__placements">' . $note
			. '<thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table></div>';
	}

	/** A nested object: a subsection, or a folded one once it gets deep. */
	private static function generic_section( string $label, array $value, int $depth ): string {
		if ( $depth >= self::GENERIC_MAX_DEPTH ) {
			$inner = self::generic_body( $value, $depth + 1 );
			return '' === $inner
				? ''
				: '<details class="astroway-card__more"><summary>' . esc_html( $label ) . '</summary>' . $inner . '</details>';
		}
		$inner = self::generic_body( $value, $depth + 1 );
		return '' === $inner ? '' : self::generic_heading( $label, $depth ) . $inner;
	}

	private static function generic_heading( string $label, int $depth ): string {
		$tag = $depth > 0 ? 'h5' : 'h4';
		return sprintf( '<%1$s class="astroway-card__subtitle">%2$s</%1$s>', $tag, esc_html( $label ) );
	}

	/**
	 * `siderealLongitude` and `year_pillar` both become "Sidereal longitude"
	 * and "Year pillar". Field names are the only labels the api gives us.
	 */
	private static function humanise( string $key ): string {
		$key   = (string) preg_replace( '/(?<!^)(?=[A-Z][a-z])/', ' ', $key );
		$key   = str_replace( [ '_', '-', '.' ], ' ', $key );
		$key   = trim( (string) preg_replace( '/\s+/', ' ', $key ) );
		$words = [];
		foreach ( explode( ' ', $key ) as $word ) {
			// An all-caps run is an abbreviation the api chose (ID, MC, UTC) and
			// lowercasing it would read as a typo; anything else is a word.
			$words[] = ( strtoupper( $word ) === $word ) ? $word : strtolower( $word );
		}
		$key = implode( ' ', $words );
		return '' === $key ? $key : ucfirst( $key );
	}

	private static function scalar_text( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? __( 'yes', 'astroway' ) : __( 'no', 'astroway' );
		}
		if ( null === $value ) {
			return '';
		}
		if ( is_float( $value ) ) {
			// Ephemeris numbers arrive with fifteen decimals; two is what a
			// reader can use and what a degree is quoted to anywhere else here.
			return self::number( $value, 2 );
		}
		return trim( (string) $value );
	}
}
