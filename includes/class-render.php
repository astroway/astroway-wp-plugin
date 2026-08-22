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
		'daily_horoscope'   => [ 'astroway-horoscope-card', 'horoscope' ],
		'weekly_horoscope'  => [ 'astroway-horoscope-card', 'horoscope' ],
		'monthly_horoscope' => [ 'astroway-horoscope-card', 'horoscope' ],
		'moon_phase'        => [ 'astroway-moon-card', 'moon' ],
		'tarot_daily'       => [ 'astroway-tarot-card', 'tarot' ],
		'planet_of_day'     => [ 'astroway-planet-card', 'planet' ],
		'natal'             => [ 'astroway-natal-card', 'natal' ],
		'moon_sign'         => [ 'astroway-sign-card', 'moon-sign' ],
		'rising_sign'       => [ 'astroway-sign-card', 'rising-sign' ],
		'bodygraph'         => [ 'astroway-bodygraph-card', 'bodygraph' ],
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
	private static function phase_label( string $raw ): string {
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

	private static function planet_label( string $raw ): string {
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

	private static function horoscope_card( string $widget, array $data, string $lang ): string {
		$body = trim( (string) ( $data['horoscope'] ?? '' ) );
		if ( '' === $body ) {
			return '';
		}

		$sign  = self::sign_label( (string) ( $data['sign'] ?? '' ) );
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
