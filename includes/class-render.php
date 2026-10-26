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
		'mini_chart'           => [ 'astroway-natal-card', 'mini-chart' ],
		'moon_sign'            => [ 'astroway-sign-card', 'moon-sign' ],
		'rising_sign'          => [ 'astroway-sign-card', 'rising-sign' ],
		'bodygraph'            => [ 'astroway-bodygraph-card', 'bodygraph' ],
		'retrograde'           => [ 'astroway-retrograde-card', 'retrograde' ],
		'retrogrades'          => [ 'astroway-retrograde-card', 'retrogrades' ],
		'transit'              => [ 'astroway-transit-card', 'transit' ],
		'moon_voc'             => [ 'astroway-voc-card', 'moon-voc' ],
		'planetary_hours'      => [ 'astroway-hours-card', 'planetary-hours' ],
		'yearly_horoscope'     => [ 'astroway-horoscope-card', 'horoscope' ],
		'zodiac_compatibility' => [ 'astroway-compat-card', 'zodiac-compatibility' ],
		'chinese_zodiac'       => [ 'astroway-chinese-card', 'chinese-zodiac' ],
		'synastry'             => [ 'astroway-synastry-card', 'synastry' ],
		'zodiac_matrix'        => [ 'astroway-compat-card', 'zodiac-matrix' ],
		'tarot_three_card'     => [ 'astroway-tarot-card', 'tarot-spread' ],
		'tarot_celtic_cross'   => [ 'astroway-tarot-card', 'tarot-cross' ],
		'solar_return'         => [ 'astroway-natal-card', 'natal solar-return' ],
		'lunar_return'         => [ 'astroway-natal-card', 'natal lunar-return' ],
		'davison'              => [ 'astroway-natal-card', 'natal davison' ],
		'kundli'               => [ 'astroway-kundli-card', 'kundli' ],
		'numerology'           => [ 'astroway-numerology-card', 'numerology' ],
	];

	/**
	 * Server-rendered widget, or the iframe when the data is unavailable.
	 *
	 * @param string $widget Widget key, as used by PublicData and RendererDecisions.
	 * @param array  $params Shortcode params, already sanitised by the caller.
	 */
	public static function widget( string $widget, array $params = [] ): string {
		// No sign is a question for the visitor, not an answer: the frame behind
		// it used to read Aries as if the author had chosen it.
		if ( in_array( $widget, self::HOROSCOPE_PERIODS, true ) && '' === trim( (string) ( $params['sign'] ?? '' ) ) ) {
			return self::sign_prompt( $widget, $params );
		}

		// "In an iframe" is a preference, and it only applies where a frame is
		// actually an option. The moon and rising sign cards have no embed route
		// behind them, so honouring the setting there would render nothing at all.
		$framed = null !== RendererDecisions::get( $widget );
		if ( ( $framed && 'iframe' === self::mode() ) || ! PublicData::supports( $widget ) || ! isset( self::CARDS[ $widget ] ) ) {
			return PublicClient::embed_iframe( $widget, $params );
		}

		// Without the owner's key the reading is the api's own spread of the day,
		// read for free; its cards replace the site's, since the reading is of them.
		// The Celtic Cross is read on the owner's key only; without it a reading slot would only fail.
		if ( 'tarot_celtic_cross' === $widget && ! empty( $params['reading'] ) && '' === ( new ApiClient() )->key() ) {
			unset( $params['reading'] );
		}
		if ( 'tarot_three_card' === $widget && ! empty( $params['reading'] ) && '' === ( new ApiClient() )->key() ) {
			$daily = TarotReading::daily( Plugin::resolve_lang( $params['lang'] ?? '' ), (string) ( $params['art'] ?? '' ) );
			if ( null === $daily ) {
				unset( $params['reading'] );
			} else {
				if ( 'click' === ( $params['reveal'] ?? '' ) && empty( $daily['data']['back'] ) ) {
					// Before api 2.228.4 the back was not in the answer; the site's own spread has it.
					$own                   = PublicData::get( $widget, $params );
					$daily['data']['back'] = is_array( $own['back'] ?? null ) ? $own['back'] : [];
				}
				return self::with_data( $widget, $daily['data'], [ 'reading_html' => $daily['html'] ] + $params );
			}
		}

		return self::with_data( $widget, PublicData::get( $widget, $params, empty( $params['nocache'] ) ), $params );
	}

	/** The card for an answer already in hand: the form asks once and reads it twice. */
	public static function with_data( string $widget, ?array $data, array $params ): string {
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
	public static function mode(): string {
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
		Plugin::use_styles();
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
				return self::horoscope_card( $widget, $data, $lang, $params );
			case 'moon_phase':
				return self::moon_card( $data, $lang );
			case 'tarot_daily':
				return self::tarot_card( $data, $lang, $params );
			case 'planet_of_day':
				return self::planet_card( $data, $lang );
			case 'natal':
				return self::natal_card( $data, $lang, $params );
			case 'mini_chart':
				return self::mini_card( $data, $lang, $params );
			case 'moon_sign':
				return self::placement_card( 'moon_sign', $data, $lang, $params );
			case 'rising_sign':
				return self::placement_card( 'rising_sign', $data, $lang, $params );
			case 'bodygraph':
				return self::bodygraph_card( $data, $lang, $params );
			case 'synastry':
				return self::synastry_card( $data, $lang, $params );
			case 'zodiac_matrix':
				return self::matrix_card( $data, $lang, $params );
			case 'tarot_three_card':
			case 'tarot_celtic_cross':
				return self::spread_card( $widget, $data, $lang, $params );
			case 'solar_return':
			case 'lunar_return':
				return self::return_card( $widget, $data, $lang, $params );
			case 'davison':
				return self::davison_card( $data, $lang, $params );
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
			// Houses turn with the clock: without the time this one would be noon's.
			$house = empty( $params['time_unknown'] ) ? self::house_of( $longitude, $cusps ) : null;
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

		$inner = UI::header(
			[
				'title'     => self::sign_label( $sign ),
				'medal'     => $sign,
				'meta_html' => self::kind_meta( $title, (string) ( $params['date'] ?? '' ) ),
			]
		);

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

		$inner .= self::rows_as_facts( $rows );

		return self::shell( $widget, $lang, $inner );
	}

	/** Label and value pairs the cards already had, in the 2.0 facts component. */
	private static function rows_as_facts( array $rows, string $variant = 'list' ): string {
		$items = [];
		foreach ( $rows as $label => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$items[] = [
				'label' => (string) $label,
				'value' => (string) $value,
			];
		}
		return empty( $items ) ? '' : UI::facts( $items, $variant );
	}

	/**
	 * Human Design chart: the facts as text, then the bodygraph, drawn here.
	 *
	 * `/v1/embed/bodygraph` draws nothing: checked against the live response,
	 * it is the same handful of facts as a text table with emoji for the
	 * centres. Keeping it would print the reading twice on the page, the second
	 * copy in someone else's stylesheet and invisible to a crawler.
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

		$meta = self::natal_meta( $params );
		if ( is_numeric( $data['designJd'] ?? null ) ) {
			// The design moment, 88 degrees of the Sun before birth, from its Julian day.
			$stamp = (int) round( ( (float) $data['designJd'] - 2440587.5 ) * DAY_IN_SECONDS );
			/* translators: %s = date of the design moment */
			$design = sprintf( __( 'Design: %s', 'astroway' ), wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $stamp ) );
			$meta  .= ' <span class="astroway-card__when"><span aria-hidden="true">·</span>' . esc_html( $design ) . '</span>';
		}
		$inner  = UI::header(
			[
				'title'     => $title,
				'meta_html' => $meta,
			]
		);
		$inner .= '<p class="astroway-card__lead">' . esc_html( self::hd_type_label( $type ) ) . '</p>';
		$inner .= UI::shown( 'tags' ) ? self::hd_tags( $type, (string) ( $data['notSelfTheme'] ?? '' ) ) : '';

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
		if ( ! empty( $data['cross']['name'] ) ) {
			// The cross names are an open set of hundreds, so they stay as sent.
			$cross = (string) $data['cross']['name'];
			$gates = array_values( array_filter( (array) ( $data['cross']['gates'] ?? [] ), 'is_numeric' ) );
			if ( 4 === count( $gates ) ) {
				// Personality Sun and Earth, then design Sun and Earth, as charts write it.
				$cross .= sprintf( ' (%d/%d | %d/%d)', ...array_map( 'intval', $gates ) );
			}
			$rows[ __( 'Incarnation cross', 'astroway' ) ] = $cross;
		}
		$inner .= UI::shown( 'facts' ) ? self::detail_list( $rows ) : '';

		$texts  = self::hd_texts( $lang );
		$inner .= UI::shown( 'reading' ) ? self::hd_reading( $data, $texts ) : '';

		$channels = is_array( $data['channels'] ?? null ) ? $data['channels'] : [];
		$inner   .= UI::tabs(
			__( 'Human Design', 'astroway' ),
			[
				[
					'label' => __( 'Bodygraph', 'astroway' ),
					'key'   => 'bodygraph',
					'html'  => self::hd_bodygraph( $data, $title ),
				],
				[
					'label' => __( 'Centres', 'astroway' ),
					'key'   => 'centres',
					'html'  => self::hd_centres( $data['centers'] ?? [], $texts ),
				],
				[
					'label' => __( 'Channels', 'astroway' ),
					'key'   => 'channels',
					'html'  => self::hd_channels( $channels, $texts ),
				],
			]
		);

		return self::shell( 'bodygraph', $lang, $inner );
	}

	/** The HD planets in the order every chart lists them, with their glyphs. */
	private const HD_PLANETS = [
		'Sun'     => 'sun',
		'Earth'   => 'planet-earth',
		'NNode'   => 'north-node',
		'SNode'   => 'south-node',
		'Moon'    => 'moon',
		'Mercury' => 'mercury',
		'Venus'   => 'venus',
		'Mars'    => 'mars',
		'Jupiter' => 'jupiter',
		'Saturn'  => 'saturn',
		'Uranus'  => 'uranus',
		'Neptune' => 'neptune',
		'Pluto'   => 'pluto',
	];

	/**
	 * The drawing over its two columns of activations: design on the left,
	 * personality on the right, as on every printed chart. Under it rather
	 * than beside it: beside, the card would need about 650px before a column
	 * head such as "Persönlichkeit" fits.
	 */
	private static function hd_bodygraph( array $data, string $title ): string {
		$centres = [];
		$defined = [];
		foreach ( (array) ( $data['centers'] ?? [] ) as $centre ) {
			if ( is_array( $centre ) && in_array( $centre['name'] ?? '', Bodygraph::centres(), true ) ) {
				$centres[ $centre['name'] ] = ! empty( $centre['defined'] );
				if ( ! empty( $centre['defined'] ) ) {
					$defined[] = self::hd_centre_label( (string) $centre['name'] );
				}
			}
		}
		if ( empty( $centres ) ) {
			return '';
		}

		$gates = [];
		$sides = [
			'd' => '',
			'p' => '',
		];
		foreach ( [
			'd' => 'designActivations',
			'p' => 'personalityActivations',
		] as $side => $key ) {
			$by_planet = [];
			foreach ( (array) ( $data[ $key ] ?? [] ) as $act ) {
				if ( is_array( $act ) && is_string( $act['planet'] ?? null ) && isset( self::HD_PLANETS[ $act['planet'] ], $act['gate'], $act['line'] ) ) {
					$by_planet[ $act['planet'] ] = $act;
				}
			}
			foreach ( self::HD_PLANETS as $planet => $glyph ) {
				if ( ! isset( $by_planet[ $planet ] ) ) {
					continue;
				}
				$act                     = $by_planet[ $planet ];
				$gate                    = (int) $act['gate'];
				$line                    = (int) $act['line'];
				$gates[ $gate ][ $side ] = true;
				$ctb                     = '';
				if ( isset( $act['color'], $act['tone'], $act['base'] ) ) {
					$ctb = '<small class="astroway-bodygraph__ctb"><span aria-hidden="true">'
						/* translators: abbreviated colour, tone and base of a Human Design activation, e.g. C5 T6 B4 */
						. esc_html( sprintf( __( 'C%1$d T%2$d B%3$d', 'astroway' ), (int) $act['color'], (int) $act['tone'], (int) $act['base'] ) )
						. '</span><span class="astroway-sr">'
						/* translators: colour, tone and base of a Human Design activation */
						. esc_html( sprintf( __( 'colour %1$d, tone %2$d, base %3$d', 'astroway' ), (int) $act['color'], (int) $act['tone'], (int) $act['base'] ) )
						. '</span></small>';
				}
				$sides[ $side ] .= '<li>' . Glyphs::icon( $glyph, self::hd_planet_label( $planet ) )
					. '<span aria-hidden="true">' . $gate . '.' . $line . '</span><span class="astroway-sr">'
					/* translators: 1: Human Design gate number, 2: line number within the gate */
					. esc_html( sprintf( __( 'gate %1$d, line %2$d', 'astroway' ), $gate, $line ) ) . '</span>' . $ctb . '</li>';
			}
		}

		$names = [];
		foreach ( (array) ( $data['channels'] ?? [] ) as $channel ) {
			if ( is_array( $channel ) && isset( $channel['gate1'], $channel['gate2'] ) ) {
				$names[] = (int) $channel['gate1'] . '-' . (int) $channel['gate2'];
			}
		}
		$desc = empty( $defined )
			? __( 'No centre is defined.', 'astroway' )
			/* translators: %s = list of centre names */
			: sprintf( __( 'Defined centres: %s.', 'astroway' ), implode( ', ', $defined ) );
		if ( ! empty( $names ) ) {
			/* translators: %s = list of channels such as 13-33 */
			$desc .= ' ' . sprintf( __( 'Channels: %s.', 'astroway' ), implode( ', ', $names ) );
		}

		$side = static function ( string $kind, string $head, string $items ): string {
			if ( '' === $items ) {
				return '';
			}
			return '<div class="astroway-bodygraph__side astroway-bodygraph__side--' . $kind . '"><p class="astroway-bodygraph__head">' . esc_html( $head ) . '</p><ul>' . $items . '</ul></div>';
		};

		$html  = '<figure class="astroway-bodygraph">';
		$html .= '<div class="astroway-bodygraph__graph">' . Bodygraph::draw(
			[
				'centres' => $centres,
				'gates'   => $gates,
				'title'   => $title,
				'desc'    => $desc,
			]
		) . '</div>';
		$cols  = $side( 'design', __( 'Design', 'astroway' ), $sides['d'] ) . $side( 'personality', __( 'Personality', 'astroway' ), $sides['p'] );
		$html .= '' === $cols ? '' : '<div class="astroway-bodygraph__sides">' . $cols . '</div>';
		$html .= '<figcaption>' . UI::legend(
			[
				[
					'sample' => 'design',
					'label'  => __( 'Design (unconscious)', 'astroway' ),
				],
				[
					'sample' => 'personality',
					'label'  => __( 'Personality (conscious)', 'astroway' ),
				],
				[
					'sample' => 'both',
					'label'  => __( 'Both', 'astroway' ),
				],
			]
		) . '</figcaption>';
		return $html . '</figure>';
	}

	private static function hd_planet_label( string $raw ): string {
		switch ( $raw ) {
			case 'Earth':
				return _x( 'Earth', 'planet', 'astroway' );
			case 'NNode':
				return self::planet_label( 'North Node' );
			case 'SNode':
				return self::planet_label( 'South Node' );
		}
		return self::planet_label( $raw );
	}

	/** Defined centres named, undefined ones counted: nine lines would drown the card. */
	/** Signature and not-self theme, the pair every type is told by. */
	private static function hd_tags( string $type, string $not_self ): string {
		$signatures = [
			'Generator'             => __( 'Satisfaction', 'astroway' ),
			'Manifesting Generator' => __( 'Satisfaction', 'astroway' ),
			'Projector'             => __( 'Success', 'astroway' ),
			'Manifestor'            => __( 'Peace', 'astroway' ),
			'Reflector'             => __( 'Surprise', 'astroway' ),
		];
		$tags       = [];
		if ( isset( $signatures[ $type ] ) ) {
			/* translators: %s = Human Design signature, e.g. Success */
			$tags[] = sprintf( __( 'Signature: %s', 'astroway' ), $signatures[ $type ] );
		}
		if ( '' !== trim( $not_self ) ) {
			/* translators: %s = Human Design not-self theme, e.g. Bitterness */
			$tags[] = sprintf( __( 'Not-self: %s', 'astroway' ), self::hd_theme_label( $not_self ) );
		}
		$html = UI::tags( $tags );
		return '' === $html ? '' : str_replace( 'class="astroway-tags"', 'class="astroway-tags astroway-hd__tags"', $html );
	}

	/** Motors, awareness and pressure centres: fixed by the system, not by the chart. */
	private const HD_CENTRE_KINDS = [
		'Head'        => [ 'pressure' ],
		'Ajna'        => [ 'awareness' ],
		'Throat'      => [],
		'G'           => [],
		'Heart'       => [ 'motor' ],
		'Spleen'      => [ 'awareness' ],
		'SolarPlexus' => [ 'motor', 'awareness' ],
		'Sacral'      => [ 'motor' ],
		'Root'        => [ 'motor', 'pressure' ],
	];

	/** The nine centres, top to bottom, each a row that opens on its kind and gates. */
	/**
	 * The api's Human Design texts in this language, or [] when it has none to
	 * give in it. One request per language, kept for a week.
	 */
	private static function hd_texts( string $lang ): array {
		$lang = Plugin::resolve_lang( $lang );
		$data = PublicData::get( 'hd_texts', [ 'lang' => $lang ] );
		return is_array( $data ) && ( ! empty( $data['textLocalized'] ) || 'en' === $lang ) ? $data : [];
	}

	/** A text from the dictionary, or '' when the api sent something else there. */
	private static function hd_text( array $texts, string $group, string $key, string $field = '' ): string {
		$value = $texts[ $group ][ $key ] ?? null;
		if ( '' !== $field ) {
			$value = is_array( $value ) ? ( $value[ $field ] ?? null ) : null;
		}
		return is_string( $value ) ? trim( $value ) : '';
	}

	/** Type and strategy, authority, definition, profile and its lines, the gates of the cross. */
	private static function hd_reading( array $data, array $texts ): string {
		if ( empty( $texts ) ) {
			return '';
		}
		$p     = static fn( string $t ): string => '' === $t ? '' : '<p>' . esc_html( $t ) . '</p>';
		$items = [];

		$type = (string) ( $data['type'] ?? '' );
		$body = $p( self::hd_text( $texts, 'types', $type, 'summary' ) );
		$name = self::hd_text( $texts, 'types', $type, 'strategyName' );
		$how  = self::hd_text( $texts, 'types', $type, 'strategy' );
		if ( '' !== $how ) {
			$body .= '<p>' . ( '' === $name ? '' : '<strong>' . esc_html( $name ) . '.</strong> ' ) . esc_html( $how ) . '</p>';
		}
		if ( '' !== $body ) {
			$items[] = [
				'title'     => self::hd_type_label( $type ),
				'body_html' => UI::prose( $body ),
			];
		}

		$auth = (string) ( $data['authority'] ?? '' );
		$body = $p( self::hd_text( $texts, 'authorities', $auth, 'text' ) );
		if ( '' !== $body ) {
			$items[] = [
				/* translators: %s = Human Design authority, such as Emotional */
				'title'     => sprintf( __( 'Authority: %s', 'astroway' ), self::hd_authority_label( $auth ) ),
				'body_html' => UI::prose( $body ),
			];
		}

		$def  = (string) ( $data['definition'] ?? '' );
		$body = $p( self::hd_text( $texts, 'definition', $def ) );
		if ( '' !== $body ) {
			$items[] = [
				// The label already names it: "Split definition".
				'title'     => self::hd_definition_label( $def ),
				'body_html' => UI::prose( $body ),
			];
		}

		$profile = (string) ( $data['profile']['profile'] ?? '' );
		$body    = $p( self::hd_text( $texts, 'profiles', $profile ) );
		foreach ( [ 'personalityLine', 'designLine' ] as $side ) {
			$line = (int) ( $data['profile'][ $side ] ?? 0 );
			$text = $line > 0 ? self::hd_text( $texts, 'lines', (string) $line ) : '';
			if ( '' !== $text ) {
				/* translators: %d = line number, 1 to 6 */
				$body .= '<p><strong>' . esc_html( sprintf( __( 'Line %d', 'astroway' ), $line ) ) . '.</strong> ' . esc_html( $text ) . '</p>';
			}
		}
		if ( '' !== $body ) {
			$items[] = [
				/* translators: %s = Human Design profile, such as 6/2 */
				'title'     => sprintf( __( 'Profile %s', 'astroway' ), $profile ),
				'body_html' => UI::prose( $body ),
			];
		}

		$body = '';
		foreach ( (array) ( $data['cross']['gates'] ?? [] ) as $gate ) {
			$text = is_numeric( $gate ) ? self::hd_text( $texts, 'gates', (string) (int) $gate, 'text' ) : '';
			if ( '' !== $text ) {
				/* translators: %d = gate number, 1 to 64 */
				$body .= '<p><strong>' . esc_html( sprintf( __( 'Gate %d', 'astroway' ), (int) $gate ) ) . '.</strong> ' . esc_html( $text ) . '</p>';
			}
		}
		if ( '' !== $body ) {
			$items[] = [
				'title'     => __( 'Incarnation cross', 'astroway' ),
				'body_html' => UI::prose( $body ),
			];
		}

		if ( ! $items ) {
			return '';
		}
		$items[0]['open'] = true;
		return UI::section( __( 'What the chart says', 'astroway' ), UI::accordion( $items ) );
	}

	private static function hd_centres( $centres, array $texts = [] ): string {
		if ( ! is_array( $centres ) || empty( $centres ) ) {
			return '';
		}
		$kinds = [
			'motor'     => __( 'Motor', 'astroway' ),
			'awareness' => __( 'Awareness', 'astroway' ),
			'pressure'  => __( 'Pressure', 'astroway' ),
		];
		$by    = [];
		foreach ( $centres as $centre ) {
			if ( is_array( $centre ) && isset( self::HD_CENTRE_KINDS[ (string) ( $centre['name'] ?? '' ) ] ) ) {
				$by[ (string) $centre['name'] ] = $centre;
			}
		}
		$items = [];
		foreach ( self::HD_CENTRE_KINDS as $name => $kind ) {
			if ( ! isset( $by[ $name ] ) ) {
				continue;
			}
			$centre  = $by[ $name ];
			$defined = ! empty( $centre['defined'] );
			$tags    = [ $defined ? __( 'Defined', 'astroway' ) : __( 'Open', 'astroway' ) ];
			foreach ( $kind as $k ) {
				$tags[] = $kinds[ $k ];
			}
			$body  = UI::tags( $tags );
			$theme = self::hd_text( $texts, 'centers', $name, 'theme' );
			$text  = self::hd_text( $texts, 'centers', $name, $defined ? 'defined' : 'open' );
			if ( '' !== $theme . $text ) {
				$body .= UI::prose( ( '' === $theme ? '' : '<p><strong>' . esc_html( $theme ) . '.</strong></p>' ) . ( '' === $text ? '' : '<p>' . esc_html( $text ) . '</p>' ) );
			}
			$all    = array_map( 'intval', array_filter( (array) ( $centre['gates'] ?? [] ), 'is_numeric' ) );
			$active = array_map( 'intval', array_filter( (array) ( $centre['activeGates'] ?? [] ), 'is_numeric' ) );
			if ( $all ) {
				/* translators: %s = list of gate numbers */
				$body .= '<p class="astroway-hd__gates">' . esc_html( sprintf( __( 'Gates: %s', 'astroway' ), implode( ', ', $all ) ) ) . '</p>';
			}
			if ( $active ) {
				/* translators: %s = list of gate numbers */
				$body .= '<p class="astroway-hd__gates">' . esc_html( sprintf( __( 'Active gates: %s', 'astroway' ), implode( ', ', $active ) ) ) . '</p>';
			}
			$items[] = [
				'title'     => self::hd_centre_label( $name ),
				'meta_html' => esc_html( $defined ? __( 'Defined', 'astroway' ) : __( 'Open', 'astroway' ) ),
				'body_html' => $body,
			];
		}
		return UI::accordion( $items );
	}

	/**
	 * The circuit each of the 36 channels belongs to. The integration channels
	 * stand apart from the three circuit groups.
	 */
	private const HD_CIRCUITS = [
		'integration' => [ '10-20', '10-34', '10-57', '20-34', '20-57', '34-57' ],
		'individual'  => [ '1-8', '2-14', '3-60', '12-22', '23-43', '24-61', '28-38', '39-55', '25-51' ],
		'tribal'      => [ '19-49', '37-40', '21-45', '26-44', '32-54', '6-59', '27-50' ],
		'collective'  => [ '4-63', '7-31', '5-15', '9-52', '16-48', '17-62', '18-58', '11-56', '13-33', '29-46', '30-41', '35-36', '42-53', '47-64' ],
	];

	/** Defined channels grouped by circuit, each opening on its centres and which side made it. */
	private static function hd_channels( $channels, array $texts = [] ): string {
		if ( ! is_array( $channels ) || empty( $channels ) ) {
			return '';
		}
		$groups = [
			'individual'  => __( 'Individual circuitry', 'astroway' ),
			'tribal'      => __( 'Tribal circuitry', 'astroway' ),
			'collective'  => __( 'Collective circuitry', 'astroway' ),
			'integration' => __( 'Integration channels', 'astroway' ),
		];
		$sides  = [
			'personality' => __( 'Personality', 'astroway' ),
			'design'      => __( 'Design', 'astroway' ),
		];
		$filed  = [];
		foreach ( $channels as $channel ) {
			if ( ! is_array( $channel ) || ! isset( $channel['gate1'], $channel['gate2'] ) ) {
				continue;
			}
			$a       = (int) $channel['gate1'];
			$b       = (int) $channel['gate2'];
			$key     = min( $a, $b ) . '-' . max( $a, $b );
			$circuit = '';
			foreach ( self::HD_CIRCUITS as $name => $list ) {
				if ( in_array( $key, $list, true ) ) {
					$circuit = $name;
					break;
				}
			}
			$tags = [
				self::hd_centre_label( (string) ( $channel['centerA'] ?? '' ) ),
				self::hd_centre_label( (string) ( $channel['centerB'] ?? '' ) ),
			];
			foreach ( (array) ( $channel['activatedBy'] ?? [] ) as $by ) {
				if ( isset( $sides[ $by ] ) ) {
					$tags[] = $sides[ $by ];
				}
			}
			$filed[ $circuit ][] = [
				'title'     => sprintf(
					/* translators: 1: first gate number, 2: second gate number, 3: first centre, 4: second centre */
					__( '%1$d-%2$d, %3$s to %4$s', 'astroway' ),
					$a,
					$b,
					self::hd_centre_label( (string) ( $channel['centerA'] ?? '' ) ),
					self::hd_centre_label( (string) ( $channel['centerB'] ?? '' ) )
				),
				'body_html' => UI::tags( $tags ) . ( '' === self::hd_text( $texts, 'channels', $key, 'text' ) ? '' : UI::prose( '<p>' . esc_html( self::hd_text( $texts, 'channels', $key, 'text' ) ) . '</p>' ) ),
			];
		}
		$html = '';
		foreach ( $groups + [ '' => __( 'Channels', 'astroway' ) ] as $circuit => $label ) {
			if ( ! empty( $filed[ $circuit ] ) ) {
				$html .= '<h5 class="astroway-hd__circuit">' . esc_html( $label ) . '</h5>' . UI::accordion( $filed[ $circuit ] );
			}
		}
		return $html;
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
			'Ego Manifested'           => __( 'Ego manifested', 'astroway' ),
			'Ego Projected'            => __( 'Ego projected', 'astroway' ),
			'Self-Projected'           => __( 'Self-projected', 'astroway' ),
			'Mental (Environment)'     => __( 'Mental (environment)', 'astroway' ),
			'Mental/Environmental'     => __( 'Mental (environment)', 'astroway' ),
			'Lunar Cycle'              => __( 'Lunar cycle', 'astroway' ),
			'None (Lunar)'             => __( 'Lunar cycle', 'astroway' ),
			'Lunar (None)'             => __( 'Lunar cycle', 'astroway' ),
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
	/**
	 * Planets, houses, cusps and aspects of a chart, with what an unknown birth
	 * time cannot know taken out. Shared by the natal card and the mini chart.
	 */
	private static function natal_inputs( array $data, array $params ): array {
		$planets = isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [];
		$houses  = isset( $data['houses'] ) && is_array( $data['houses'] ) ? $data['houses'] : [];
		$cusps   = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];
		// Houses and the Ascendant turn with the clock: without the time they
		// would be those of noon, drawn as if known.
		// The Moon moves up to 15 degrees a day, as wide as an aspect's orb, so
		// its aspects at noon are not a fact about this birth either.
		$aspects_in = is_array( $data['aspects'] ?? null ) ? $data['aspects'] : [];
		if ( ! empty( $params['time_unknown'] ) ) {
			$houses     = [];
			$cusps      = [];
			$aspects_in = array_values(
				array_filter(
					$aspects_in,
					static function ( $aspect ): bool {
						return ! is_array( $aspect ) || ! in_array( 'Moon', [ $aspect['planet1'] ?? '', $aspect['planet2'] ?? '' ], true );
					}
				)
			);
		}
		return [ $planets, $houses, $cusps, $aspects_in ];
	}

	/** The natal wheel alone, for a sidebar or a narrow column. */
	private static function mini_card( array $data, string $lang, array $params ): string {
		list( $planets, $houses, $cusps, $aspects_in ) = self::natal_inputs( $data, $params );
		if ( empty( $planets ) ) {
			return '';
		}
		$title = __( 'Natal chart', 'astroway' );
		$name  = trim( (string) ( $params['name'] ?? '' ) );
		if ( '' !== $name ) {
			/* translators: %s = person's name */
			$title = sprintf( __( 'Natal chart: %s', 'astroway' ), $name );
		}
		$wheel = self::natal_wheel( $planets, $houses, $cusps, self::natal_aspects( $aspects_in ), $title );
		if ( '' === $wheel ) {
			return '';
		}
		$inner = UI::header(
			[
				'title'     => $title,
				'meta_html' => self::natal_meta( $params ),
			]
		);
		return self::shell( 'mini_chart', $lang, $inner . $wheel );
	}

	private static function natal_card( array $data, string $lang, array $params ): string {
		list( $planets, $houses, $cusps, $aspects_in ) = self::natal_inputs( $data, $params );
		if ( empty( $planets ) ) {
			return '';
		}
		$unknown = ! empty( $params['time_unknown'] );

		$name  = trim( (string) ( $params['name'] ?? '' ) );
		$title = __( 'Natal chart', 'astroway' );
		if ( '' !== $name ) {
			/* translators: %s = person's name */
			$title = sprintf( __( 'Natal chart: %s', 'astroway' ), $name );
		}

		$aspects = self::natal_aspects( $aspects_in );
		$sect    = $unknown ? '' : (string) ( $data['chartSect'] ?? '' );
		$points  = self::natal_points( $planets, $houses, $sect );

		$sun = null;
		foreach ( $planets as $planet ) {
			if ( is_array( $planet ) && 'Sun' === ( $planet['name'] ?? '' ) && isset( $planet['longitude'] ) ) {
				$sun = self::sign_of( (float) $planet['longitude'] );
				break;
			}
		}

		$inner  = UI::header(
			[
				'title'     => $title,
				'medal'     => (string) $sun,
				'meta_html' => self::natal_meta( $params ),
			]
		);
		$inner .= UI::shown( 'big-three' ) ? self::natal_big_three( $planets, $houses ) : '';
		$inner .= UI::shown( 'summary' ) ? self::natal_summary( $planets, $aspects_in, $sect ) : '';
		if ( $unknown ) {
			$inner .= UI::callout( esc_html__( 'Birth time unknown: the chart is drawn for noon, without houses, the Ascendant or the Moon\'s aspects, and the Moon may be up to 7 degrees from where it was.', 'astroway' ) );
		}
		$inner .= UI::tabs(
			__( 'Natal chart', 'astroway' ),
			[
				[
					'label' => __( 'Wheel', 'astroway' ),
					'key'   => 'wheel',
					'html'  => self::natal_wheel( $planets, $houses, $cusps, $aspects, $title, '', $points ),
				],
				[
					'label' => __( 'Grid', 'astroway' ),
					'key'   => 'grid',
					'html'  => UI::aspect_grid( self::natal_planet_ids( $planets ), $aspects, __( 'Aspects between planets', 'astroway' ) ),
				],
				[
					'label' => __( 'Placements', 'astroway' ),
					'key'   => 'placements',
					'html'  => self::natal_placements( $planets, $cusps, $houses, $points ),
				],
				[
					'label' => __( 'Aspects', 'astroway' ),
					'key'   => 'aspects',
					'html'  => UI::aspects( $aspects ),
					'count' => count( $aspects ),
				],
				[
					'label' => __( 'Transits', 'astroway' ),
					'key'   => 'transits',
					'html'  => self::natal_transits( $planets, $houses, $cusps, $params, $title ),
				],
			]
		);
		$matrix = self::natal_matrix( $planets );
		// Its own block with a heading: under the tabs it read as the tail of whichever tab was open.
		$inner .= '' === $matrix ? '' : UI::section( __( 'Elements by mode', 'astroway' ), $matrix, '', 'elements' );

		$figures = ChartFacts::patterns( $planets, $unknown ? [] : $cusps );
		$keys    = self::natal_text_keys( $planets, $cusps, $houses, $aspects_in );
		foreach ( $figures as $figure ) {
			$keys[] = 'pattern.' . $figure['kind'];
		}
		$texts  = PublicData::natal_texts( array_values( array_unique( $keys ) ), $lang );
		$inner .= self::natal_patterns( $figures, $texts );
		$inner .= self::natal_reading( $keys, $texts );

		return self::shell( 'natal', $lang, $inner );
	}

	/** Bodies the natal texts cover, in the order the reading lists them. */
	private const TEXT_BODIES = [ 'sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter', 'saturn', 'uranus', 'neptune', 'pluto', 'chiron' ];

	/** Keys for /v1/natal-texts: body in sign, body in house, then aspects. */
	public static function natal_text_keys( array $planets, array $cusps, array $houses, $aspects ): array {
		$keys = [];
		foreach ( self::TEXT_BODIES as $body ) {
			foreach ( $planets as $planet ) {
				if ( ! is_array( $planet ) || ! isset( $planet['longitude'] ) || self::planet_id( (string) ( $planet['name'] ?? '' ) ) !== $body ) {
					continue;
				}
				$keys[] = $body . '.' . self::sign_of( (float) $planet['longitude'] );
				$house  = self::house_of( (float) $planet['longitude'], $cusps );
				if ( null !== $house ) {
					$keys[] = $body . '.h' . $house;
				}
			}
		}
		if ( isset( $houses['ascendant'] ) ) {
			$keys[] = 'ascendant.' . self::sign_of( (float) $houses['ascendant'] );
		}
		// Asked for so they appear the day the api writes them; a missing key is cached as missing.
		foreach ( $planets as $planet ) {
			$id = is_array( $planet ) && isset( $planet['longitude'] ) ? self::planet_id( (string) ( $planet['name'] ?? '' ) ) : '';
			if ( in_array( $id, [ 'north-node', 'lilith' ], true ) ) {
				$keys[] = str_replace( '-', '_', $id ) . '.' . self::sign_of( (float) $planet['longitude'] );
			}
			// The South Node is the same axis from its other end, in the opposite sign.
			if ( 'north-node' === $id ) {
				$keys[] = 'south_node.' . self::sign_of( (float) $planet['longitude'] + 180 );
			}
		}

		// Pairs among the ten planets, in the api's fixed order, major aspects only.
		$order = array_flip( array_slice( self::TEXT_BODIES, 0, 10 ) );
		foreach ( is_array( $aspects ) ? $aspects : [] as $aspect ) {
			$a    = self::planet_id( (string) ( $aspect['planet1'] ?? '' ) );
			$b    = self::planet_id( (string) ( $aspect['planet2'] ?? '' ) );
			$type = strtolower( trim( (string) ( $aspect['type']['name'] ?? '' ) ) );
			if ( ! isset( $order[ $a ], $order[ $b ] ) || $a === $b || ! in_array( $type, [ 'conjunction', 'opposition', 'trine', 'square', 'sextile' ], true ) ) {
				continue;
			}
			$pair   = $order[ $a ] < $order[ $b ] ? $a . '_' . $b : $b . '_' . $a;
			$keys[] = $pair . '.' . $type;
		}
		return array_values( array_unique( $keys ) );
	}

	/** The row's mark: the body's glyph, the Ascendant's sign, an aspect's own glyph. */
	private static function reading_mark( string $key ): string {
		[ $subject, $what ] = array_pad( explode( '.', $key, 2 ), 2, '' );
		if ( 'ascendant' === $subject ) {
			return $what;
		}
		if ( 'north_node' === $subject || 'south_node' === $subject ) {
			return str_replace( '_', '-', $subject );
		}
		return false === strpos( $subject, '_' ) ? $subject : $what;
	}

	/**
	 * "What it means": one row per body with its sign text, its house text
	 * under it when there is one, then one row per aspect. A key with no text
	 * is left out rather than filled in from another language.
	 */
	public static function natal_reading( array $keys, array $texts ): string {
		// Blocks are split by a blank line. Inside one, a short first line
		// followed by more is its heading, "• " lines are a list, the rest prose.
		$paras = static function ( string $body ): string {
			$out = '';
			foreach ( preg_split( '/\n\s*\n/', trim( $body ) ) as $block ) {
				$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $block ) ), 'strlen' ) );
				if ( count( $lines ) > 1 && 0 !== strpos( $lines[0], '•' ) && mb_strlen( $lines[0] ) <= 60 ) {
					// The card draws its own marks; a colour emoji differs per OS and sits outside the palette.
					$out .= '<h5>' . esc_html( (string) preg_replace( '/^[\p{Extended_Pictographic}\x{FE0F}\x{200D}\s]+/u', '', array_shift( $lines ) ) ) . '</h5>';
				}
				$list = '';
				foreach ( $lines as $line ) {
					if ( 0 === strpos( $line, '•' ) ) {
						$list .= '<li>' . esc_html( trim( substr( $line, strlen( '•' ) ) ) ) . '</li>';
						continue;
					}
					$out .= '' === $list ? '' : '<ul>' . $list . '</ul>';
					$list = '';
					$out .= '<p>' . esc_html( $line ) . '</p>';
				}
				$out .= '' === $list ? '' : '<ul>' . $list . '</ul>';
			}
			return $out;
		};

		$items = [];
		foreach ( $keys as $key ) {
			// A figure's text reads under the figure, not as a row here.
			if ( ! isset( $texts[ $key ] ) || preg_match( '/\.h\d+$/', $key ) || 0 === strpos( $key, 'pattern.' ) ) {
				continue;
			}
			[ $subject ] = explode( '.', $key );
			$body_html   = $paras( $texts[ $key ]['body'] );
			foreach ( $keys as $house_key ) {
				if ( 0 === strpos( $house_key, $subject . '.h' ) && isset( $texts[ $house_key ] ) ) {
					$body_html .= '<h4>' . esc_html( $texts[ $house_key ]['title'] ) . '</h4>' . $paras( $texts[ $house_key ]['body'] );
				}
			}
			$items[] = [
				'title'     => $texts[ $key ]['title'],
				'icon_html' => Glyphs::icon( self::reading_mark( $key ) ),
				'body_html' => '<div class="astroway-prose">' . $body_html . '</div>',
			];
		}

		if ( $items ) {
			// The first row open says at a glance that the rows hold text; open="none" closes it.
			$items[0]['open'] = true;
		}
		return $items ? UI::section( __( 'What it means', 'astroway' ), UI::accordion( $items ), '', 'meaning' ) : '';
	}

	/** "Moon sign · 15 May 1990": what the card is, then when it is for. */
	private static function kind_meta( string $label, string $date ): string {
		$time = self::date_line( $date );
		$time = str_replace( ' class="astroway-card__meta"', '', $time );
		if ( '' === $time ) {
			return esc_html( $label );
		}
		return esc_html( $label ) . ' <span class="astroway-card__when"><span aria-hidden="true">·</span>' . $time . '</span>';
	}

	/** When and where the chart is for, as one machine-readable line. */
	private static function natal_meta( array $params ): string {
		$date = self::sanitised_date( (string) ( $params['date'] ?? '' ) );
		if ( '' === $date ) {
			return '';
		}
		$time  = empty( $params['time_unknown'] ) ? (string) ( $params['time'] ?? '' ) : '';
		$time  = preg_match( '/^\d{2}:\d{2}$/', $time ) ? $time : '';
		$stamp = strtotime( $date . ' 00:00:00 UTC' );
		$shown = false === $stamp ? $date : wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $stamp );
		$shown = '' === $time ? (string) $shown : $shown . ', ' . $time;

		$html = sprintf(
			'<time datetime="%s">%s</time>',
			esc_attr( '' === $time ? $date : $date . 'T' . $time ),
			esc_html( $shown )
		);
		return empty( $params['time_unknown'] ) ? $html : $html . ' <span class="astroway-card__when"><span aria-hidden="true">·</span>' . esc_html__( 'time unknown', 'astroway' ) . '</span>';
	}

	private static function sanitised_date( string $date ): string {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
	}

	/**
	 * The Part of Fortune, the vertex and the South Node, ready for the wheel
	 * and the placements table.
	 *
	 * @return list<array{id: string, name: string, lon: float, text?: string}>
	 */
	private static function natal_points( array $planets, array $houses, string $sect ): array {
		$names = [
			'fortune'    => __( 'Part of Fortune', 'astroway' ),
			'vertex'     => __( 'Vertex', 'astroway' ),
			'south-node' => __( 'South Node', 'astroway' ),
		];
		$out   = [];
		foreach ( ChartFacts::points( $planets, $houses, $sect ) as $id => $lon ) {
			$point = [
				'id'   => $id,
				'name' => $names[ $id ],
				'lon'  => $lon,
			];
			// The vertex has no glyph of its own; astrologers write it Vx.
			if ( 'vertex' === $id ) {
				$point['text'] = 'Vx';
			}
			$out[] = $point;
		}
		return $out;
	}

	/**
	 * One line of pills: the element and mode the chart leans on, day or night,
	 * how many aspects are harmonious and how many tense, which planets are
	 * retrograde.
	 */
	private static function natal_summary( array $planets, array $aspects_in, string $sect ): string {
		$elements = [
			'fire'  => __( 'Fire', 'astroway' ),
			'earth' => __( 'Earth', 'astroway' ),
			'air'   => __( 'Air', 'astroway' ),
			'water' => __( 'Water', 'astroway' ),
		];
		$modes    = [
			'cardinal' => __( 'Cardinal', 'astroway' ),
			'fixed'    => __( 'Fixed', 'astroway' ),
			'mutable'  => __( 'Mutable', 'astroway' ),
		];
		$dominant = ChartFacts::dominant( $planets );
		$pills    = [];
		if ( ! empty( $dominant['element'] ) ) {
			/* translators: %s = element or elements, e.g. "Earth" or "Earth, Water" */
			$pills[] = esc_html( sprintf( __( 'Element: %s', 'astroway' ), implode( ', ', array_map( static fn( $k ) => $elements[ $k ], $dominant['element'] ) ) ) );
		}
		if ( ! empty( $dominant['mode'] ) ) {
			/* translators: %s = mode or modes, e.g. "Fixed" */
			$pills[] = esc_html( sprintf( __( 'Mode: %s', 'astroway' ), implode( ', ', array_map( static fn( $k ) => $modes[ $k ], $dominant['mode'] ) ) ) );
		}
		if ( 'diurnal' === $sect ) {
			$pills[] = esc_html__( 'Day chart', 'astroway' );
		} elseif ( 'nocturnal' === $sect ) {
			$pills[] = esc_html__( 'Night chart', 'astroway' );
		}
		$major   = array_filter( $aspects_in, static fn( $a ) => is_array( $a ) && ! empty( $a['type']['isMajor'] ) );
		$balance = ChartFacts::balance( $major );
		if ( $balance['h'] + $balance['t'] > 0 ) {
			/* translators: %d = number of aspects */
			$pills[] = esc_html( sprintf( __( 'Harmonious %d', 'astroway' ), $balance['h'] ) );
			/* translators: %d = number of aspects */
			$pills[] = esc_html( sprintf( __( 'Challenging %d', 'astroway' ), $balance['t'] ) );
		}
		$retro = '';
		foreach ( $planets as $planet ) {
			$id = is_array( $planet ) ? self::planet_id( (string) ( $planet['name'] ?? '' ) ) : '';
			// The nodes and Lilith move backwards by their nature; naming them says nothing.
			if ( ! empty( $planet['isRetrograde'] ) && ! in_array( $id, [ 'north-node', 'south-node', 'lilith' ], true ) ) {
				$name   = self::planet_label( (string) ( $planet['name'] ?? '' ) );
				$retro .= Glyphs::exists( $id ) ? Glyphs::icon( $id, $name ) : esc_html( $name ) . ' ';
			}
		}
		if ( '' !== $retro ) {
			$pills[] = esc_html__( 'Retrograde', 'astroway' ) . ' <span class="astroway-natal__rx">' . $retro . '</span>';
		}
		if ( empty( $pills ) ) {
			return '';
		}
		return '<ul class="astroway-tags astroway-natal__summary"><li>' . implode( '</li><li>', $pills ) . '</li></ul>';
	}

	/** Chart patterns as cards: the figure's name, its planets with the apex marked, what it is. */
	/**
	 * @param array $figures From ChartFacts::patterns().
	 * @param array $texts   The natal texts; a figure's own text reads under its first one.
	 */
	private static function natal_patterns( array $figures, array $texts = [] ): string {
		if ( ! UI::shown( 'patterns' ) ) {
			return '';
		}
		$names = [
			'grand_cross' => __( 'Grand cross', 'astroway' ),
			't_square'    => __( 'T-square', 'astroway' ),
			'grand_trine' => __( 'Grand trine', 'astroway' ),
			'yod'         => __( 'Yod', 'astroway' ),
			'stellium'    => __( 'Stellium', 'astroway' ),
		];
		$about = [
			'grand_cross' => __( 'Four planets in two oppositions, each square to the next.', 'astroway' ),
			't_square'    => __( 'Two planets in opposition, both square to a third, the apex.', 'astroway' ),
			'grand_trine' => __( 'Three planets a trine apart, closing a triangle.', 'astroway' ),
			'yod'         => __( 'Two planets in sextile, both quincunx to a third, the apex.', 'astroway' ),
		];
		$cards = '';
		$read  = [];
		foreach ( $figures as $figure ) {
			$tags = [];
			foreach ( $figure['bodies'] as $id ) {
				$label  = self::planet_label( ucfirst( $id ) );
				$tags[] = ( $figure['apex'] ?? '' ) === $id
					/* translators: %s = planet name, the apex of a chart pattern */
					? sprintf( __( '%s (apex)', 'astroway' ), $label )
					: $label;
			}
			if ( 'stellium' === $figure['kind'] ) {
				$desc = isset( $figure['sign'] )
					/* translators: 1: number of planets, 2: sign name */
					? sprintf( __( '%1$d planets in %2$s.', 'astroway' ), count( $figure['bodies'] ), self::sign_label( $figure['sign'] ) )
					/* translators: 1: number of planets, 2: house number */
					: sprintf( __( '%1$d planets in house %2$d.', 'astroway' ), count( $figure['bodies'] ), (int) $figure['house'] );
			} else {
				$desc = $about[ $figure['kind'] ];
			}
			$own = $texts[ 'pattern.' . $figure['kind'] ] ?? null;
			if ( is_array( $own ) && '' !== trim( (string) ( $own['body'] ?? '' ) ) && ! isset( $read[ $figure['kind'] ] ) ) {
				// Under the tiles, once per kind: a text inside a tile would stretch its row.
				$read[ $figure['kind'] ] = [
					'title'     => '' !== trim( (string) ( $own['title'] ?? '' ) ) ? (string) $own['title'] : $names[ $figure['kind'] ],
					'body_html' => '<div class="astroway-prose">' . self::paragraphs( (string) $own['body'] ) . '</div>',
				];
			}
			$cards .= '<li class="astroway-patterns__item"><h5 class="astroway-patterns__name">' . esc_html( $names[ $figure['kind'] ] ) . '</h5>'
				. UI::tags( $tags ) . '<p class="astroway-patterns__about">' . esc_html( $desc ) . '</p></li>';
		}
		$more = $read ? UI::accordion( array_values( $read ) ) : '';
		return '' === $cards ? '' : UI::section( __( 'Chart patterns', 'astroway' ), '<ul class="astroway-patterns">' . $cards . '</ul>' . $more, '', 'patterns' );
	}

	/**
	 * Sun, Moon and Ascendant: the three placements a reader looks for first,
	 * and the ones every other astrology site puts at the top.
	 */
	private static function natal_big_three( array $planets, array $houses ): string {
		$by_name = [];
		foreach ( $planets as $planet ) {
			if ( is_array( $planet ) && isset( $planet['name'], $planet['longitude'] ) ) {
				$by_name[ strtolower( (string) $planet['name'] ) ] = (float) $planet['longitude'];
			}
		}

		$items    = [];
		$luminary = [
			'sun'  => __( 'Sun', 'astroway' ),
			'moon' => __( 'Moon', 'astroway' ),
		];
		foreach ( $luminary as $key => $label ) {
			if ( ! isset( $by_name[ $key ] ) ) {
				continue;
			}
			$items[] = [
				'label' => $label,
				'glyph' => self::sign_of( $by_name[ $key ] ),
				'value' => self::sign_label( self::sign_of( $by_name[ $key ] ) ),
				'sub'   => self::position_label( $by_name[ $key ] ),
			];
		}
		if ( isset( $houses['ascendant'] ) ) {
			$asc     = (float) $houses['ascendant'];
			$items[] = [
				'label' => __( 'Ascendant', 'astroway' ),
				'glyph' => self::sign_of( $asc ),
				'value' => self::sign_label( self::sign_of( $asc ) ),
				'sub'   => self::position_label( $asc ),
			];
		}

		return empty( $items ) ? '' : UI::facts( $items, 'trio' );
	}

	/**
	 * Aspects in the shape both the wheel and the grid read. Major only: the
	 * minor ones triple the list without helping a reader.
	 */
	private static function natal_aspects( $aspects ): array {
		if ( ! is_array( $aspects ) ) {
			return [];
		}
		$out = [];
		foreach ( $aspects as $aspect ) {
			if ( ! is_array( $aspect ) || empty( $aspect['type']['isMajor'] ) ) {
				continue;
			}
			$type  = (string) ( $aspect['type']['name'] ?? '' );
			$glyph = strtolower( trim( $type ) );
			$orb   = isset( $aspect['orb'] ) ? sprintf( '%s°', self::number( (float) $aspect['orb'], 1 ) ) : '';
			$out[] = [
				'a'     => self::planet_id( (string) ( $aspect['planet1'] ?? '' ) ),
				'b'     => self::planet_id( (string) ( $aspect['planet2'] ?? '' ) ),
				'glyph' => $glyph,
				'kind'  => self::aspect_kind( $type ),
				'orb'   => $orb,
				'trend' => isset( $aspect['isApplying'] ) ? ( empty( $aspect['isApplying'] ) ? __( 'Separating', 'astroway' ) : __( 'Applying', 'astroway' ) ) : '',
				'text'  => sprintf(
					/* translators: 1: first planet, 2: aspect name, 3: second planet */
					__( '%1$s %2$s %3$s', 'astroway' ),
					self::planet_label( (string) ( $aspect['planet1'] ?? '' ) ),
					self::aspect_label( $type ),
					self::planet_label( (string) ( $aspect['planet2'] ?? '' ) )
				),
			];
		}
		return $out;
	}

	/** Harmony, tension, or neither: what colours the line and the mark. */
	private static function aspect_kind( string $type ): string {
		switch ( strtolower( trim( $type ) ) ) {
			case 'trine':
			case 'sextile':
				return 'h';
			case 'square':
			case 'opposition':
				return 't';
			default:
				return 'n';
		}
	}

	/**
	 * Glyph id for a body as the api spells it. Swiss Ephemeris returns
	 * "true Node" and "mean Apogee", which fold to nothing on their own: the
	 * matrix counted them and drew an empty cell, so a row read "Air 1" beside
	 * three blanks.
	 */
	private static function planet_id( string $name ): string {
		$id      = strtolower( trim( $name ) );
		$aliases = [
			'true node'         => 'north-node',
			'mean node'         => 'north-node',
			'node'              => 'north-node',
			'north node'        => 'north-node',
			'south node'        => 'south-node',
			'mean apogee'       => 'lilith',
			'osc. apogee'       => 'lilith',
			'apogee'            => 'lilith',
			'black moon lilith' => 'lilith',
		];
		if ( isset( $aliases[ $id ] ) ) {
			return $aliases[ $id ];
		}
		return str_replace( ' ', '-', $id );
	}

	private static function natal_planet_ids( array $planets ): array {
		$out = [];
		foreach ( $planets as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['name'] ) ) {
				continue;
			}
			$out[] = [
				'id'   => self::planet_id( (string) $planet['name'] ),
				'name' => self::planet_label( (string) $planet['name'] ),
			];
		}
		return $out;
	}

	/**
	 * The chart drawn here rather than fetched: the wheel used to be an iframe,
	 * which put the chart on a document the page's own stylesheet and a reader
	 * without scripts could not reach.
	 */
	private static function natal_wheel( array $planets, array $houses, array $cusps, array $aspects, string $title, string $desc = '', array $points = [] ): string {
		$drawn = [];
		foreach ( $planets as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['longitude'], $planet['name'] ) ) {
				continue;
			}
			$lon     = (float) $planet['longitude'];
			$house   = self::house_of( $lon, $cusps );
			$label   = self::planet_label( (string) $planet['name'] ) . ', ' . self::position_label( $lon );
			$label  .= null === $house ? '' : ', ' . sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house );
			$drawn[] = [
				'id'    => self::planet_id( (string) $planet['name'] ),
				'lon'   => $lon,
				'rx'    => ! empty( $planet['isRetrograde'] ),
				'label' => $label,
			];
		}
		if ( empty( $drawn ) ) {
			return '';
		}
		foreach ( $points as $point ) {
			$house   = self::house_of( $point['lon'], $cusps );
			$label   = $point['name'] . ', ' . self::position_label( $point['lon'] );
			$label  .= null === $house ? '' : ', ' . sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house );
			$drawn[] = [
				'id'    => $point['id'],
				'lon'   => $point['lon'],
				'rx'    => false,
				'label' => $label,
				'text'  => $point['text'] ?? '',
			];
		}

		return Wheel::natal(
			[
				'asc'       => (float) ( $houses['ascendant'] ?? 0 ),
				'mc'        => (float) ( $houses['mc'] ?? 0 ),
				// No Ascendant, no angles: drawn at 0° they would be invented.
				'no_angles' => ! isset( $houses['ascendant'] ),
				'cusps'     => $cusps,
				'planets'   => $drawn,
				'aspects'   => $aspects,
				'title'     => $title,
				'desc'      => '' !== $desc ? $desc : __( 'Natal chart: signs, houses, planets and the aspects between them.', 'astroway' ),
				'angles'    => [
					'asc' => __( 'ASC', 'astroway' ),
					'ic'  => __( 'IC', 'astroway' ),
					'dsc' => __( 'DSC', 'astroway' ),
					'mc'  => __( 'MC', 'astroway' ),
				],
			]
		);
	}

	/**
	 * A Solar or Lunar Return: the chart of the moment the Sun or the Moon comes
	 * back to its birth place in the zodiac, read like a natal chart.
	 *
	 * Drawn only when the moment is the one asked for. The api's search can land
	 * on the neighbouring Solar Return for birthdays around the new year, and a
	 * Lunar Return before the date it was searched from; a card titled 2027 over
	 * the chart of 2026 would be worse than no card.
	 */
	private static function return_card( string $widget, array $data, string $lang, array $params ): string {
		$planets = isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [];
		$moment  = self::return_moment( $data );
		if ( empty( $planets ) || null === $moment ) {
			return '';
		}

		$solar = 'solar_return' === $widget;
		$year  = (int) ( $params['year'] ?? 0 );
		if ( $solar ) {
			$birthday = self::birthday_in( (string) ( $params['date'] ?? '' ), $year );
			if ( null === $birthday || abs( $moment - $birthday ) > 2 * DAY_IN_SECONDS ) {
				return self::admin_note( __( 'The Solar Return found for this year falls in another year, so the card is left out. Only administrators see this note.', 'astroway' ) );
			}
		} else {
			$after = strtotime( (string) ( $params['after'] ?? '' ) . ' 00:00:00 UTC' );
			if ( false === $after || $moment < $after ) {
				return self::admin_note( __( 'The Lunar Return found falls before the date it was searched from, so the card is left out. Only administrators see this note.', 'astroway' ) );
			}
		}

		$houses  = isset( $data['houses'] ) && is_array( $data['houses'] ) ? $data['houses'] : [];
		$cusps   = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];
		$aspects = self::natal_aspects( is_array( $data['aspects'] ?? null ) ? $data['aspects'] : [] );
		$name    = trim( (string) ( $params['name'] ?? '' ) );

		if ( $solar ) {
			/* translators: %d = year */
			$title = sprintf( __( 'Solar Return %d', 'astroway' ), $year );
			/* translators: 1: year, 2: person's name */
			$title = '' === $name ? $title : sprintf( __( 'Solar Return %1$d: %2$s', 'astroway' ), $year, $name );
			$desc  = __( 'Solar Return chart: signs, houses, planets and the aspects between them at the moment the Sun returns to its birth position.', 'astroway' );
		} else {
			$title = __( 'Lunar Return', 'astroway' );
			/* translators: %s = person's name */
			$title = '' === $name ? $title : sprintf( __( 'Lunar Return: %s', 'astroway' ), $name );
			$desc  = __( 'Lunar Return chart: signs, houses, planets and the aspects between them at the moment the Moon returns to its birth position.', 'astroway' );
		}

		$asc    = isset( $houses['ascendant'] ) ? self::sign_of( (float) $houses['ascendant'] ) : '';
		$inner  = UI::header(
			[
				'title'     => $title,
				'medal'     => $asc,
				'meta_html' => self::moment_meta( $moment, $params ),
			]
		);
		$inner .= self::natal_big_three( $planets, $houses );
		$inner .= self::chart_tabs( $title, $planets, $houses, $cusps, $aspects, $desc );

		return self::shell( $widget, $lang, $inner );
	}

	/**
	 * The Davison chart of a pair: one chart cast for the midpoint between the
	 * two births in time and in place.
	 *
	 * The api averaged longitudes as plain numbers until app fixed it, which puts
	 * a pair across the 180th meridian on the far side of the Earth. The card
	 * checks the midpoint against the shorter arc itself and stays out until the
	 * two agree, so it starts drawing on its own once the fix is live.
	 */
	private static function davison_card( array $data, string $lang, array $params ): string {
		$planets  = isset( $data['planets'] ) && is_array( $data['planets'] ) ? $data['planets'] : [];
		$midpoint = is_array( $data['midpoint'] ?? null ) ? $data['midpoint'] : [];
		$utc      = strtotime( (string) ( $midpoint['utc'] ?? '' ) );
		if ( empty( $planets ) || false === $utc || ! isset( $midpoint['lng'] ) ) {
			return '';
		}
		$expected = self::mid_longitude( (float) ( $params['lng_a'] ?? 0 ), (float) ( $params['lng_b'] ?? 0 ) );
		$apart    = abs( fmod( (float) $midpoint['lng'] - $expected + 540.0, 360.0 ) - 180.0 );
		if ( $apart > 0.5 ) {
			return self::admin_note( __( 'The Davison chart for these two places is not drawn yet: the midpoint the api returned is on the wrong side of the 180th meridian. Only administrators see this note.', 'astroway' ) );
		}

		$houses  = isset( $data['houses'] ) && is_array( $data['houses'] ) ? $data['houses'] : [];
		$cusps   = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];
		$aspects = self::natal_aspects( is_array( $data['aspects'] ?? null ) ? $data['aspects'] : [] );

		$first  = trim( (string) ( $params['name_a'] ?? '' ) );
		$second = trim( (string) ( $params['name_b'] ?? '' ) );
		$title  = __( 'Davison chart', 'astroway' );
		if ( '' !== $first && '' !== $second ) {
			/* translators: 1: first person's name, 2: second person's name */
			$title = sprintf( __( 'Davison chart: %1$s and %2$s', 'astroway' ), $first, $second );
		}

		$sun = '';
		foreach ( $planets as $planet ) {
			if ( is_array( $planet ) && 'Sun' === ( $planet['name'] ?? '' ) && isset( $planet['longitude'] ) ) {
				$sun = self::sign_of( (float) $planet['longitude'] );
				break;
			}
		}
		// Two items of the flex meta row: the dotted `when` wrapper clips the
		// second line of a date long enough to wrap inside it.
		$meta   = '<span>' . esc_html__( 'Midpoint', 'astroway' ) . ' ' . self::moment_meta( (int) $utc, [] ) . '</span>';
		$inner  = UI::header(
			[
				'title'     => $title,
				'medal'     => $sun,
				'meta_html' => $meta,
			]
		);
		$inner .= self::natal_big_three( $planets, $houses );
		$inner .= self::chart_tabs( $title, $planets, $houses, $cusps, $aspects, __( 'Davison chart: one chart cast for the midpoint between two births in time and place.', 'astroway' ) );

		return self::shell( 'davison', $lang, $inner );
	}

	/** Wheel, grid, placements and aspects of a chart, as the natal card lays them out. */
	private static function chart_tabs( string $title, array $planets, array $houses, array $cusps, array $aspects, string $desc ): string {
		return UI::tabs(
			$title,
			[
				[
					'label' => __( 'Wheel', 'astroway' ),
					'html'  => self::natal_wheel( $planets, $houses, $cusps, $aspects, $title, $desc ),
				],
				[
					'label' => __( 'Grid', 'astroway' ),
					'html'  => UI::aspect_grid( self::natal_planet_ids( $planets ), $aspects, __( 'Aspects between planets', 'astroway' ) ),
				],
				[
					'label' => __( 'Placements', 'astroway' ),
					'html'  => self::natal_placements( $planets, $cusps, $houses ),
				],
				[
					'label' => __( 'Aspects', 'astroway' ),
					'html'  => UI::aspects( $aspects ),
					'count' => count( $aspects ),
				],
			]
		);
	}

	/** The return moment as a Unix time: `returnMoment`, or `julianDay` from an older api. */
	private static function return_moment( array $data ): ?int {
		$iso = (string) ( $data['returnMoment'] ?? '' );
		if ( '' !== $iso ) {
			$stamp = strtotime( $iso );
			return false === $stamp ? null : $stamp;
		}
		if ( isset( $data['julianDay'] ) && is_numeric( $data['julianDay'] ) ) {
			return (int) round( ( (float) $data['julianDay'] - 2440587.5 ) * DAY_IN_SECONDS );
		}
		return null;
	}

	/** Noon UTC of the birthday in a given year; 29 February falls on the 28th in a common year. */
	private static function birthday_in( string $date, int $year ): ?int {
		if ( ! preg_match( '/^\d{4}-(\d{2})-(\d{2})$/', $date, $m ) || $year < 1 ) {
			return null;
		}
		$day = ( '02' === $m[1] && '29' === $m[2] && ! checkdate( 2, 29, $year ) ) ? 28 : (int) $m[2];
		return (int) gmmktime( 12, 0, 0, (int) $m[1], $day, $year );
	}

	/** Midpoint of two longitudes along the shorter arc, in (-180, 180]. */
	public static function mid_longitude( float $a, float $b ): float {
		$d = fmod( $b - $a + 540.0, 360.0 ) - 180.0;
		$m = fmod( $a + $d / 2 + 540.0, 360.0 ) - 180.0;
		return -180.0 === $m ? 180.0 : $m;
	}

	/**
	 * When a moment fell, as a `<time>`: in the zone of the place when it is
	 * known (`zone`, an IANA name from the atlas, or `return_tz` in hours),
	 * otherwise in UTC and marked so.
	 */
	private static function moment_meta( int $utc, array $params ): string {
		$zone  = null;
		$named = (string) ( $params['zone'] ?? '' );
		if ( '' !== $named ) {
			try {
				$zone = new \DateTimeZone( $named );
			} catch ( \Exception $e ) {
				$zone = null;
			}
		}
		$hours = trim( (string) ( $params['return_tz'] ?? '' ) );
		if ( null === $zone && is_numeric( $hours ) ) {
			$seconds = (int) round( (float) $hours * HOUR_IN_SECONDS );
			$zone    = new \DateTimeZone( sprintf( '%s%02d:%02d', $seconds < 0 ? '-' : '+', intdiv( abs( $seconds ), 3600 ), intdiv( abs( $seconds ) % 3600, 60 ) ) );
		}
		$utc_zone = new \DateTimeZone( 'UTC' );
		// The zone is always named: a bare 06:34 beside 18:24 UTC reads as the same clock.
		$shown = wp_date( (string) get_option( 'date_format', 'Y-m-d' ) . ', H:i', $utc, $zone ?? $utc_zone ) . ' '
			. ( null === $zone ? 'UTC' : self::zone_label( $zone, $utc ) );
		return sprintf( '<time datetime="%s">%s</time>', esc_attr( gmdate( 'Y-m-d\TH:i:s\Z', $utc ) ), esc_html( (string) $shown ) );
	}

	/** EEST for a named zone, UTC+3 or UTC+5:30 for a bare offset. */
	private static function zone_label( \DateTimeZone $zone, int $utc ): string {
		$at = ( new \DateTimeImmutable( '@' . $utc ) )->setTimezone( $zone );
		if ( false !== strpos( $zone->getName(), '/' ) ) {
			$abbr = $at->format( 'T' );
			// Zones without a letter abbreviation print an offset like "+03"; say UTC.
			if ( preg_match( '/^[A-Z]{2,5}$/', $abbr ) ) {
				return $abbr;
			}
		}
		$offset = $at->getOffset();
		if ( 0 === $offset ) {
			return 'UTC';
		}
		$label = 'UTC' . ( $offset < 0 ? '-' : '+' ) . intdiv( abs( $offset ), 3600 );
		return 0 === abs( $offset ) % 3600 ? $label : $label . ':' . sprintf( '%02d', intdiv( abs( $offset ) % 3600, 60 ) );
	}

	/** Orb of a transit worth listing: tight, because the sky moves on tomorrow. */
	private const TRANSIT_ORB = 3.0;

	/** The five major aspects by angle. */
	private const MAJOR_ANGLES = [
		'Conjunction' => 0,
		'Sextile'     => 60,
		'Square'      => 90,
		'Trine'       => 120,
		'Opposition'  => 180,
	];

	/**
	 * The sky of one day on the outer ring of the chart, and the aspects it
	 * makes to the birth planets. Only when the author asks, with
	 * `transits="today"` or a date: it costs one more call a day.
	 *
	 * The sky is taken at noon UTC. The transiting Moon moves about thirteen
	 * degrees a day, so it is drawn but kept out of the list; everything else
	 * moves under two degrees and holds for the day.
	 */
	private static function natal_transits( array $planets, array $houses, array $cusps, array $params, string $title ): string {
		$asked = (string) ( $params['transits'] ?? '' );
		if ( '' === $asked ) {
			return '';
		}
		$day = 'today' === $asked ? wp_date( 'Y-m-d' ) : $asked;
		$sky = PublicData::get(
			'sky',
			[
				'date' => $day,
				'time' => '12:00',
				'lat'  => '',
				'lng'  => '',
				'tz'   => '0',
			],
			empty( $params['nocache'] )
		);
		$now = is_array( $sky['planets'] ?? null ) ? $sky['planets'] : [];
		if ( empty( $now ) ) {
			return '';
		}

		$classical = [ 'Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto' ];
		$lon_of    = static function ( array $bodies, array $skip ) use ( $classical ): array {
			$out = [];
			foreach ( $bodies as $p ) {
				$name = is_array( $p ) ? (string) ( $p['name'] ?? '' ) : '';
				if ( in_array( $name, $classical, true ) && ! in_array( $name, $skip, true ) && isset( $p['longitude'] ) ) {
					$out[ $name ] = (float) $p['longitude'];
				}
			}
			return $out;
		};
		// Without the birth time the natal Moon is a guess, as on the other tabs.
		$natal   = $lon_of( $planets, empty( $params['time_unknown'] ) ? [] : [ 'Moon' ] );
		$transit = $lon_of( $now, [ 'Moon' ] );

		$found = [];
		foreach ( $transit as $t_name => $t_lon ) {
			foreach ( $natal as $n_name => $n_lon ) {
				$gap = abs( fmod( $t_lon - $n_lon + 540, 360 ) - 180 );
				foreach ( self::MAJOR_ANGLES as $type => $angle ) {
					$orb = abs( $gap - $angle );
					if ( $orb <= self::TRANSIT_ORB ) {
						$found[] = [ $t_name, $type, $n_name, $orb ];
					}
				}
			}
		}
		usort( $found, static fn( $a, $b ) => $a[3] <=> $b[3] );

		$stamp = strtotime( $day . ' 12:00:00 UTC' );
		$shown = false === $stamp ? $day : wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $stamp );
		$ring  = static function ( array $bodies, string $whose ): array {
			$out = [];
			foreach ( $bodies as $p ) {
				if ( ! is_array( $p ) || ! isset( $p['longitude'], $p['name'] ) ) {
					continue;
				}
				$lon   = (float) $p['longitude'];
				$out[] = [
					'id'    => self::planet_id( (string) $p['name'] ),
					'lon'   => $lon,
					'rx'    => ! empty( $p['isRetrograde'] ),
					/* translators: 1: planet, 2: person's name, 3: its position, e.g. "Leo 24°22'" */
					'label' => sprintf( __( '%1$s (%2$s), %3$s', 'astroway' ), self::planet_label( (string) $p['name'] ), $whose, self::position_label( $lon ) ),
				];
			}
			return $out;
		};

		$lines = [];
		$rows  = [];
		foreach ( $found as list( $t_name, $type, $n_name, $orb ) ) {
			$lines[] = [
				'a'    => self::planet_id( $n_name ),
				'b'    => self::planet_id( $t_name ),
				'kind' => self::aspect_kind( $type ),
				'orb'  => $orb,
			];
			$rows[]  = [
				'glyph' => strtolower( $type ),
				'kind'  => self::aspect_kind( $type ),
				'orb'   => sprintf( '%s°', self::number( $orb, 1 ) ),
				'text'  => sprintf(
					/* translators: 1: first planet, 2: aspect name, 3: second planet */
					__( '%1$s %2$s %3$s', 'astroway' ),
					self::owned( self::planet_label( $t_name ), __( 'transit', 'astroway' ) ),
					self::aspect_label( $type ),
					self::owned( self::planet_label( $n_name ), __( 'natal', 'astroway' ) )
				),
			];
		}

		/* translators: %s = a date */
		$sky_name = sprintf( __( 'sky on %s', 'astroway' ), $shown );
		$html     = '<p class="astroway-card__intro">' . esc_html(
			sprintf(
				/* translators: 1: a date, 2: an orb in degrees */
				__( 'The planets at noon UTC on %1$s against the birth chart, aspects within %2$s°. The transiting Moon moves too fast to list.', 'astroway' ),
				$shown,
				self::number( self::TRANSIT_ORB, 0 )
			)
		) . '</p>';
		$html .= Wheel::bi(
			[
				'asc'       => (float) ( $houses['ascendant'] ?? 0 ),
				'mc'        => (float) ( $houses['mc'] ?? 0 ),
				'no_angles' => ! isset( $houses['ascendant'] ),
				'cusps'     => $cusps,
				'planets'   => $ring( $planets, __( 'natal', 'astroway' ) ),
				'outer'     => $ring( $now, __( 'transit', 'astroway' ) ),
				'aspects'   => $lines,
				'title'     => $title,
				/* translators: %s = a date */
				'desc'      => sprintf( __( 'Transit wheel: the birth chart inside, the sky on %s on the outer ring.', 'astroway' ), $shown ),
				'angles'    => [
					'asc' => __( 'ASC', 'astroway' ),
					'ic'  => __( 'IC', 'astroway' ),
					'dsc' => __( 'DSC', 'astroway' ),
					'mc'  => __( 'MC', 'astroway' ),
				],
			]
		);
		$html .= UI::legend(
			[
				[
					'ring'  => 'in',
					'label' => __( 'Inner ring: birth chart', 'astroway' ),
				],
				[
					'ring'  => 'out',
					/* translators: %s = "sky on <date>" */
					'label' => sprintf( __( 'Outer ring: %s', 'astroway' ), $sky_name ),
				],
			]
		);
		return $html . ( empty( $rows ) ? '<p class="astroway-card__intro">' . esc_html__( 'No close transits on this day.', 'astroway' ) . '</p>' : UI::aspects( $rows ) );
	}

	/** Every placement as a table, angles included. */
	private static function natal_placements( array $planets, array $cusps, array $houses, array $points = [] ): string {
		$rows = [];
		foreach ( $planets as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['longitude'], $planet['name'] ) ) {
				continue;
			}
			$lon    = (float) $planet['longitude'];
			$house  = self::house_of( $lon, $cusps );
			$id     = self::planet_id( (string) $planet['name'] );
			$name   = self::planet_label( (string) $planet['name'] );
			$rows[] = [
				'cells' => [
					[ 'html' => Glyphs::icon( $id, $name ) . ' ' . esc_html( $name ) ],
					self::position_label( $lon ),
					null === $house ? '' : sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house ),
					empty( $planet['isRetrograde'] ) ? '' : __( 'retrograde', 'astroway' ),
				],
			];
		}

		foreach ( $points as $point ) {
			$house  = self::house_of( $point['lon'], $cusps );
			$icon   = Glyphs::exists( $point['id'] ) ? Glyphs::icon( $point['id'], $point['name'] ) . ' ' : '';
			$rows[] = [
				'cells' => [
					[ 'html' => $icon . esc_html( $point['name'] ) ],
					self::position_label( $point['lon'] ),
					null === $house ? '' : sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house ),
					'',
				],
			];
		}

		$angles = [
			'ascendant' => __( 'Ascendant', 'astroway' ),
			'mc'        => __( 'Midheaven', 'astroway' ),
		];
		foreach ( $angles as $key => $label ) {
			if ( ! isset( $houses[ $key ] ) ) {
				continue;
			}
			$rows[] = [
				'angle' => true,
				'cells' => [
					[ 'html' => esc_html( $label ) ],
					self::position_label( (float) $houses[ $key ] ),
					'',
					'',
				],
			];
		}

		if ( empty( $rows ) ) {
			return '';
		}

		return UI::table(
			[
				'caption' => __( 'Placements', 'astroway' ),
				'head'    => [ __( 'Body', 'astroway' ), __( 'Position', 'astroway' ), __( 'House', 'astroway' ), __( 'Motion', 'astroway' ) ],
				'rows'    => $rows,
				'stack'   => true,
			]
		);
	}

	/** Which element and which mode the chart leans on, counted from the planets. */
	private static function natal_matrix( array $planets ): string {
		$elements = [
			'fire'  => [
				'label' => __( 'Fire', 'astroway' ),
				'signs' => [ 'aries', 'leo', 'sagittarius' ],
			],
			'earth' => [
				'label' => __( 'Earth', 'astroway' ),
				'signs' => [ 'taurus', 'virgo', 'capricorn' ],
			],
			'air'   => [
				'label' => __( 'Air', 'astroway' ),
				'signs' => [ 'gemini', 'libra', 'aquarius' ],
			],
			'water' => [
				'label' => __( 'Water', 'astroway' ),
				'signs' => [ 'cancer', 'scorpio', 'pisces' ],
			],
		];
		$modes    = [
			'cardinal' => [
				'label' => __( 'Cardinal', 'astroway' ),
				'signs' => [ 'aries', 'cancer', 'libra', 'capricorn' ],
			],
			'fixed'    => [
				'label' => __( 'Fixed', 'astroway' ),
				'signs' => [ 'taurus', 'leo', 'scorpio', 'aquarius' ],
			],
			'mutable'  => [
				'label' => __( 'Mutable', 'astroway' ),
				'signs' => [ 'gemini', 'virgo', 'sagittarius', 'pisces' ],
			],
		];

		$cells  = [];
		$totals = [];
		$any    = false;
		foreach ( $planets as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['longitude'], $planet['name'] ) ) {
				continue;
			}
			$sign = self::sign_of( (float) $planet['longitude'] );
			foreach ( $elements as $e => $element ) {
				if ( ! in_array( $sign, $element['signs'], true ) ) {
					continue;
				}
				foreach ( $modes as $m => $mode ) {
					if ( ! in_array( $sign, $mode['signs'], true ) ) {
						continue;
					}
					$cells[ $e ][ $m ][] = [
						'id'   => self::planet_id( (string) $planet['name'] ),
						'name' => self::planet_label( (string) $planet['name'] ),
					];
					$totals[ $e ]        = ( $totals[ $e ] ?? 0 ) + 1;
					$any                 = true;
				}
			}
		}
		if ( ! $any ) {
			return '';
		}

		$top  = max( $totals );
		$rows = [];
		foreach ( $elements as $e => $element ) {
			$row = [
				'label'    => $element['label'],
				'total'    => (string) ( $totals[ $e ] ?? 0 ),
				'dominant' => ( $totals[ $e ] ?? 0 ) === $top,
				'cells'    => [],
			];
			foreach ( array_keys( $modes ) as $m ) {
				$in_cell  = $cells[ $e ][ $m ] ?? [];
				$drawable = [];
				$written  = [];
				foreach ( $in_cell as $body ) {
					if ( '' === Glyphs::ref( (string) $body['id'] ) ) {
						$written[] = (string) $body['name'];
						continue;
					}
					$drawable[] = $body;
				}
				$row['cells'][] = [
					'glyphs'   => array_column( $drawable, 'id' ),
					'names'    => array_column( $in_cell, 'name' ),
					// A body with no glyph is named in the cell instead of
					// vanishing from it while its count stays in the total.
					'text'     => $written,
					'dominant' => count( $in_cell ) > 1,
				];
			}
			$rows[] = $row;
		}

		return UI::matrix(
			[
				'caption'     => __( 'Elements by mode', 'astroway' ),
				'cols'        => array_column( $modes, 'label' ),
				'total_label' => __( 'Total', 'astroway' ),
				'rows'        => $rows,
			]
		);
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
			'Rahu'        => __( 'Rahu', 'astroway' ),
			'Ketu'        => __( 'Ketu', 'astroway' ),
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
		$raw  = (string) ( $data['sign'] ?? ( $params['sign'] ?? '' ) );
		$slug = strtolower( trim( $raw ) );
		$sign = self::sign_label( $raw );

		$panels = self::horoscope_panels( $widget, $data, $slug, $params );
		// Tabs put the period on the strip, so a lone card says it in the meta
		// line instead; the sign is the heading either way.
		$single = 1 === count( $panels );

		$inner  = UI::header(
			[
				'title'        => $sign,
				'medal'        => $slug,
				'meta_html'    => self::horoscope_meta( $widget, (string) ( $data['date'] ?? '' ), $single ),
				'actions_html' => self::horoscope_datenav( $widget, $data, $params ),
			]
		);
		$inner .= UI::shown( 'signs' ) ? self::sign_switch( $slug, $params ) : '';
		$inner .= 'daily_horoscope' === $widget && UI::shown( 'sky' ) ? self::sky_today( (string) ( $data['date'] ?? '' ), $params ) : '';
		$inner .= UI::tabs( __( 'Period', 'astroway' ), $panels, self::horoscope_selected( $widget, $panels ) );
		$inner .= UI::note( (string) ( $data['disclaimer'] ?? '' ) );

		return self::shell( $widget, $lang, $inner );
	}

	/**
	 * The card for a horoscope with no sign: the twelve signs as links, the
	 * same ones the card switches with, so it works without a script and
	 * behind a page cache.
	 */
	private static function sign_prompt( string $widget, array $params ): string {
		$links = [];
		$names = [];
		foreach ( self::sign_slugs() as $slug ) {
			$links[ $slug ] = add_query_arg( 'aw_sign', $slug );
			$names[ $slug ] = self::sign_label( $slug );
		}
		$inner  = UI::header(
			[
				'title'     => __( 'Horoscope', 'astroway' ),
				'meta_html' => esc_html( self::period_label( (string) array_search( $widget, self::HOROSCOPE_PERIODS, true ) ) ),
			]
		);
		$inner .= '<p class="astroway-card__intro">' . esc_html__( 'Pick your sign', 'astroway' ) . '</p>';
		$inner .= UI::signs(
			[
				'label'   => __( 'Zodiac sign', 'astroway' ),
				'current' => '',
				'links'   => $links,
				'names'   => $names,
			]
		);
		return self::shell( $widget, Plugin::resolve_lang( $params['lang'] ?? '' ), $inner );
	}

	/** The Moon's sign and phase on the horoscope's day, from the cached moon card. */
	private static function sky_today( string $date, array $params ): string {
		$moon  = PublicData::get(
			'moon_phase',
			[
				'date' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '',
				'lang' => $params['lang'] ?? '',
			]
		);
		$phase = (string) ( $moon['phaseName'] ?? '' );
		$sign  = (string) ( $moon['moonSign'] ?? '' );
		if ( '' === $phase || '' === $sign ) {
			return '';
		}
		$lit  = isset( $moon['illuminationPercent'] ) ? (float) $moon['illuminationPercent'] / 100 : 0.5;
		$text = sprintf(
			/* translators: 1: zodiac sign, 2: moon phase, as in "Moon in Leo · Waning Crescent" */
			__( 'Moon in %1$s · %2$s', 'astroway' ),
			self::localised( $moon, 'moonSign', self::sign_label( $sign ) ),
			self::localised( $moon, 'phaseName', self::phase_label( $phase ) )
		);
		return '<div class="astroway-horoscope__sky"><span class="astroway-horoscope__sky-label">' . esc_html__( 'Sky today', 'astroway' ) . '</span>'
			. UI::sky(
				[
					[
						'icon_html' => UI::moon( $lit, ! empty( $moon['waxing'] ) ),
						'text'      => $text,
					],
				]
			) . '</div>';
	}

	/**
	 * A bold heading line right before a list is the reading's advice; set it
	 * off so it reads as the practical part. Without that shape, nothing changes.
	 */
	private static function advice( string $html ): string {
		return (string) preg_replace_callback(
			'#<p><strong>([^<]{1,80})</strong></p>(<ul>.*?</ul>)#su',
			static fn( $m ) => UI::callout( '<p class="astroway-callout__title"><strong>' . $m[1] . '</strong></p>' . $m[2], 'advice' ),
			$html
		);
	}

	/** Period key per horoscope widget, in the order the tabs show them. */
	private const HOROSCOPE_PERIODS = [
		'day'   => 'daily_horoscope',
		'week'  => 'weekly_horoscope',
		'month' => 'monthly_horoscope',
	];

	/**
	 * One panel per period the shortcode asked for. The period already fetched
	 * is reused; the rest are read through the same cache, so a card with three
	 * tabs costs three calls a day, not three a view.
	 */
	private static function horoscope_panels( string $widget, array $data, string $slug, array $params ): array {
		$wanted = (array) ( $params['periods'] ?? [] );
		$panels = [];

		foreach ( self::HOROSCOPE_PERIODS as $period => $key ) {
			if ( $key !== $widget && ! in_array( $period, $wanted, true ) ) {
				continue;
			}
			$payload = $key === $widget ? $data : PublicData::get(
				$key,
				[
					'sign' => $slug,
					'date' => $params['date'] ?? '',
					'lang' => $params['lang'] ?? '',
				]
			);
			$text    = trim( (string) ( $payload['horoscope'] ?? '' ) );
			if ( '' === $text ) {
				continue;
			}
			$lang     = Plugin::resolve_lang( (string) ( $params['lang'] ?? '' ) );
			$panels[] = [
				'label' => self::period_label( $period ),
				'html'  => self::horoscope_body( $payload, $text, $lang ) . self::horoscope_extras( $payload, $lang ),
				'key'   => $key,
			];
		}

		if ( empty( $panels ) ) {
			$panels[] = [
				'label' => self::period_label( 'day' ),
				'html'  => UI::prose( self::lead( self::paragraphs( trim( (string) ( $data['horoscope'] ?? '' ) ) ) ) ),
				'key'   => $widget,
			];
		}

		return $panels;
	}

	/**
	 * The reading itself. With the api's sections (the same text split by
	 * sphere) it is laid out by sphere: the overview as the lead, the spheres
	 * as rows, the advice set off. Without them, or in another language than
	 * the page's, the text reads as one piece, as before.
	 */
	private static function horoscope_body( array $payload, string $text, string $lang ): string {
		$sections = is_array( $payload['sections'] ?? null ) ? $payload['sections'] : [];
		$same     = Plugin::resolve_lang( (string) ( $payload['language'] ?? 'en' ) ) === $lang;
		$marked   = self::marked_parts( $text );
		if ( ( ! $same || '' === trim( (string) ( $sections['overview'] ?? '' ) ) ) && count( $marked ) > 1 && UI::shown( 'spheres' ) ) {
			return self::marked_body( $marked );
		}
		if ( ! $same || '' === trim( (string) ( $sections['overview'] ?? '' ) ) || ! UI::shown( 'spheres' ) ) {
			$plain = empty( $marked ) ? $text : implode( "\n\n", array_column( $marked, 1 ) );
			return UI::prose( self::advice( self::lead( self::paragraphs( $plain ) ) ) );
		}
		$html  = UI::prose( self::lead( self::paragraphs( (string) $sections['overview'] ) ) );
		$items = [];
		foreach ( [
			'love'    => __( 'Love', 'astroway' ),
			'career'  => __( 'Career', 'astroway' ),
			'health'  => __( 'Health', 'astroway' ),
			'finance' => __( 'Money', 'astroway' ),
		] as $key => $label ) {
			$part = trim( (string) ( $sections[ $key ] ?? '' ) );
			if ( '' !== $part ) {
				$items[] = [
					'title'     => $label,
					'body_html' => UI::prose( self::paragraphs( $part ) ),
				];
			}
		}
		$html  .= UI::accordion( $items );
		$advice = trim( (string) ( $sections['advice'] ?? '' ) );
		if ( '' !== $advice ) {
			$html .= UI::callout( '<p class="astroway-callout__title"><strong>' . esc_html__( 'Advice', 'astroway' ) . '</strong></p>' . self::paragraphs( $advice ), 'advice' );
		}
		return $html;
	}

	/**
	 * The text split at the api's "[[Heading]]" markers, as [ heading, body ]
	 * pairs; [] when it carries none. Other languages than English come with
	 * no sections but with the spheres marked inside the text this way.
	 *
	 * @return list<array{0: string, 1: string}>
	 */
	private static function marked_parts( string $text ): array {
		$bits = preg_split( '/\[\[([^\[\]\n]{1,60})\]\]/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $bits ) || count( $bits ) < 3 ) {
			return [];
		}
		$parts = [];
		$lead  = trim( (string) array_shift( $bits ) );
		if ( '' !== $lead ) {
			$parts[] = [ '', $lead ];
		}
		$n = count( $bits );
		for ( $i = 0; $i + 1 < $n; $i += 2 ) {
			$body = trim( (string) $bits[ $i + 1 ] );
			if ( '' !== $body ) {
				$parts[] = [ trim( (string) $bits[ $i ] ), $body ];
			}
		}
		return $parts;
	}

	/**
	 * The marked text laid out like the sections. Text before the first marker
	 * leads; six marked parts are overview, four spheres and advice, as the api
	 * orders them. Any other count keeps every part under its own title.
	 */
	private static function marked_body( array $parts ): string {
		$intro  = '' === $parts[0][0] ? array_shift( $parts ) : null;
		$advice = 6 === count( $parts ) ? array_pop( $parts ) : null;
		$first  = $intro ?? ( null !== $advice ? array_shift( $parts ) : null );
		$html   = null !== $first ? UI::prose( self::lead( self::paragraphs( $first[1] ) ) ) : '';
		$items  = [];
		foreach ( $parts as [ $title, $body ] ) {
			$items[] = [
				'title'     => $title,
				'body_html' => UI::prose( self::paragraphs( $body ) ),
			];
		}
		$html .= UI::accordion( $items );
		if ( null !== $advice ) {
			$html .= UI::callout( '<p class="astroway-callout__title"><strong>' . esc_html( $advice[0] ) . '</strong></p>' . self::paragraphs( $advice[1] ), 'advice' );
		}
		return $html;
	}

	/**
	 * Energy of the day, lucky planet, day and number, compatible signs. The
	 * api names the planet, the day and the signs in the page's language; the
	 * energy factors arrive in English, so the plugin words them itself.
	 */
	private static function horoscope_extras( array $payload, string $lang ): string {
		$html   = '';
		$energy = is_array( $payload['energy'] ?? null ) ? $payload['energy'] : [];
		if ( UI::shown( 'energy' ) && is_numeric( $energy['score'] ?? null ) ) {
			$factors = '';
			foreach ( (array) ( $energy['factors'] ?? [] ) as $f ) {
				$label = is_array( $f ) ? self::energy_factor( $f, 'en' === $lang ) : '';
				if ( '' === $label ) {
					continue;
				}
				$effect   = (int) ( $f['effect'] ?? 0 );
				$state    = $effect > 0 ? 'up' : ( $effect < 0 ? 'down' : 'flat' );
				$sign     = $effect > 0 ? '+' : ( $effect < 0 ? '−' : '·' );
				$factors .= '<li class="astroway-horoscope__factor is-' . $state . '"><span aria-hidden="true">' . $sign . '</span> '
					. esc_html( $label ) . '</li>';
			}
			$body  = UI::score( (int) $energy['score'], __( 'out of 10', 'astroway' ), '', 10 );
			$body .= '' === $factors ? '' : '<ul class="astroway-horoscope__factors">' . $factors . '</ul>';
			$html .= UI::section( __( 'Energy of the day', 'astroway' ), $body );
		}

		$lucky = is_array( $payload['lucky'] ?? null ) ? $payload['lucky'] : [];
		if ( UI::shown( 'lucky' ) && ! empty( $lucky ) ) {
			$facts = [];
			if ( '' !== (string) ( $lucky['planet'] ?? '' ) ) {
				$facts[] = [
					'label' => __( 'Lucky planet', 'astroway' ),
					'glyph' => self::planet_id( (string) $lucky['planet'] ),
					'value' => '' !== trim( (string) ( $lucky['planetName'] ?? '' ) ) ? trim( (string) $lucky['planetName'] ) : self::planet_label( (string) $lucky['planet'] ),
				];
			}
			if ( '' !== trim( (string) ( $lucky['dayName'] ?? '' ) ) ) {
				$facts[] = [
					'label' => __( 'Lucky day', 'astroway' ),
					'value' => trim( (string) $lucky['dayName'] ),
				];
			}
			if ( is_numeric( $lucky['number'] ?? null ) ) {
				$facts[] = [
					'label' => __( 'Lucky number', 'astroway' ),
					'value' => (string) (int) $lucky['number'],
				];
			}
			$html .= UI::facts( $facts, 'trio' );
		}

		$signs = [];
		foreach ( (array) ( $payload['compatibleSigns'] ?? [] ) as $c ) {
			if ( is_array( $c ) && '' !== (string) ( $c['sign'] ?? '' ) ) {
				$signs[] = '' !== trim( (string) ( $c['signName'] ?? '' ) ) ? trim( (string) $c['signName'] ) : self::sign_label( (string) $c['sign'] );
			}
		}
		if ( UI::shown( 'compatible' ) && $signs ) {
			$html .= UI::section( __( 'Compatible signs', 'astroway' ), UI::tags( $signs ) );
		}
		return $html;
	}

	/**
	 * One energy factor in the page's language, from its parts since api
	 * 2.229.0 and from its English before. A kind the plugin does not know is
	 * shown only on an English page.
	 */
	private static function energy_factor( array $f, bool $english ): string {
		$kind = (string) ( $f['kind'] ?? '' );
		if ( 'aspect' === $kind && '' !== (string) ( $f['planet1'] ?? '' ) && '' !== (string) ( $f['planet2'] ?? '' ) ) {
			return sprintf(
				/* translators: 1: planet, 2: aspect such as "opposite", 3: planet; one of the two rules the sign */
				__( '%1$s %2$s %3$s (the sign’s ruler)', 'astroway' ),
				self::planet_label( (string) $f['planet1'] ),
				self::aspect_label( ucfirst( strtolower( (string) ( $f['aspect'] ?? '' ) ) ) ),
				self::planet_label( (string) $f['planet2'] )
			);
		}
		if ( 'house' === $kind && '' !== (string) ( $f['planet'] ?? '' ) && is_numeric( $f['house'] ?? null ) ) {
			/* translators: 1: planet, 2: house number counted from the sign */
			return sprintf( __( '%1$s in solar house %2$d', 'astroway' ), self::planet_label( (string) $f['planet'] ), (int) $f['house'] );
		}
		if ( 'moon' === $kind && 'sameElement' === ( $f['relation'] ?? '' ) ) {
			return __( 'Moon in a sign of the same element', 'astroway' );
		}
		if ( 'moon' === $kind && in_array( $f['relation'] ?? '', [ 'square', 'opposition' ], true ) ) {
			return __( 'Moon square or opposite the sign', 'astroway' );
		}
		$raw = (string) ( $f['factor'] ?? '' );
		if ( preg_match( '/^(\w+) (conjunction|opposition|trine|square|sextile) (\w+) \(ruler\)$/', $raw, $m ) ) {
			return sprintf(
				/* translators: 1: planet, 2: aspect such as "opposite", 3: planet; one of the two rules the sign */
				__( '%1$s %2$s %3$s (the sign’s ruler)', 'astroway' ),
				self::planet_label( $m[1] ),
				self::aspect_label( ucfirst( $m[2] ) ),
				self::planet_label( $m[3] )
			);
		}
		if ( preg_match( '/^(\w+) in solar house (\d{1,2})$/', $raw, $m ) ) {
			/* translators: 1: planet, 2: house number counted from the sign */
			return sprintf( __( '%1$s in solar house %2$d', 'astroway' ), self::planet_label( $m[1] ), (int) $m[2] );
		}
		if ( 'Moon in a sign of the same element' === $raw ) {
			return __( 'Moon in a sign of the same element', 'astroway' );
		}
		if ( 'Moon square or opposite the sign' === $raw ) {
			return __( 'Moon square or opposite the sign', 'astroway' );
		}
		return $english ? trim( $raw ) : '';
	}

	/** Which tab opens: the period the shortcode itself stands for. */
	private static function horoscope_selected( string $widget, array $panels ): int {
		foreach ( $panels as $i => $panel ) {
			if ( ( $panel['key'] ?? '' ) === $widget ) {
				return $i;
			}
		}
		return 0;
	}

	/** The opening paragraph carries the reading, so it is set a touch larger. */
	private static function lead( string $html ): string {
		return (string) preg_replace( '/<p>/', '<p class="astroway-lead">', $html, 1 );
	}

	private static function period_label( string $period ): string {
		switch ( $period ) {
			case 'week':
				return __( 'Week', 'astroway' );
			case 'month':
				return __( 'Month', 'astroway' );
			case 'year':
				return __( 'Year', 'astroway' );
			default:
				return __( 'Today', 'astroway' );
		}
	}

	/** Period and date under the sign, e.g. "Horoscope for the week - 21 Sep 2026". */
	private static function horoscope_meta( string $widget, string $date, bool $with_period ): string {
		$date_html = self::date_line( $date );
		$date_html = str_replace( ' class="astroway-card__meta"', '', $date_html );
		if ( ! $with_period ) {
			return $date_html;
		}
		$phrase = self::horoscope_phrase( $widget );
		if ( '' === $date_html ) {
			return esc_html( $phrase );
		}
		return esc_html( $phrase ) . ' <span class="astroway-card__when"><span aria-hidden="true">·</span>' . $date_html . '</span>';
	}

	private static function horoscope_phrase( string $widget ): string {
		switch ( $widget ) {
			case 'weekly_horoscope':
				return __( 'Horoscope for the week', 'astroway' );
			case 'monthly_horoscope':
				return __( 'Horoscope for the month', 'astroway' );
			case 'yearly_horoscope':
				return __( 'Horoscope for the year', 'astroway' );
			default:
				return __( 'Horoscope for today', 'astroway' );
		}
	}

	/**
	 * Twelve links, not a script: the switch has to survive a page cache and a
	 * reader without JavaScript, and the result has to be shareable.
	 */
	private static function sign_switch( string $current, array $params ): string {
		if ( empty( $params['switch'] ) ) {
			return '';
		}
		$links = [];
		$names = [];
		foreach ( self::sign_slugs() as $slug ) {
			$links[ $slug ] = add_query_arg( 'aw_sign', $slug );
			$names[ $slug ] = self::sign_label( $slug );
		}
		return UI::signs(
			[
				'label'   => __( 'Zodiac sign', 'astroway' ),
				'current' => $current,
				'links'   => $links,
				'names'   => $names,
			]
		);
	}

	/**
	 * Step back and forth by the period the card is showing. The api answers any
	 * date, so the arrows are links: the visitor can read yesterday, and the URL
	 * they land on is the one they can share.
	 */
	private static function horoscope_datenav( string $widget, array $data, array $params ): string {
		if ( empty( $params['switch'] ) ) {
			return '';
		}
		$date = (string) ( $data['date'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}

		switch ( $widget ) {
			case 'weekly_horoscope':
				$step  = '7 days';
				$label = __( 'Weeks', 'astroway' );
				$back  = __( 'Previous week', 'astroway' );
				$fwd   = __( 'Next week', 'astroway' );
				break;
			case 'monthly_horoscope':
				$step  = '1 month';
				$label = __( 'Months', 'astroway' );
				$back  = __( 'Previous month', 'astroway' );
				$fwd   = __( 'Next month', 'astroway' );
				break;
			case 'daily_horoscope':
				$step  = '1 day';
				$label = __( 'Days', 'astroway' );
				$back  = __( 'Previous day', 'astroway' );
				$fwd   = __( 'Next day', 'astroway' );
				break;
			default:
				return '';
		}

		$stamp = strtotime( $date . ' 00:00:00 UTC' );
		if ( false === $stamp ) {
			return '';
		}
		$prev = gmdate( 'Y-m-d', (int) strtotime( '-' . $step, $stamp ) );
		$next = gmdate( 'Y-m-d', (int) strtotime( '+' . $step, $stamp ) );

		return UI::datenav(
			$label,
			add_query_arg( 'aw_date', $prev ),
			$back,
			add_query_arg( 'aw_date', $next ),
			$fwd
		);
	}

	private static function sign_slugs(): array {
		return [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
	}

	private static function moon_card( array $data, string $lang ): string {
		$phase = (string) ( $data['phaseName'] ?? '' );
		if ( '' === $phase ) {
			return '';
		}

		$lit    = isset( $data['illuminationPercent'] ) ? (float) $data['illuminationPercent'] : null;
		$waxing = ! empty( $data['waxing'] );
		$sign   = (string) ( $data['moonSign'] ?? '' );
		$name   = self::localised( $data, 'phaseName', self::phase_label( $phase ) );

		$facts = [];
		if ( '' !== $sign ) {
			$facts[] = [
				'label' => __( 'Moon in', 'astroway' ),
				'glyph' => strtolower( $sign ),
				'value' => self::localised( $data, 'moonSign', self::sign_label( $sign ) ),
				'sub'   => is_numeric( $data['moonDegree'] ?? null ) ? self::arc( (float) $data['moonDegree'] ) : '',
			];
		}
		if ( isset( $data['ageDays'] ) ) {
			$facts[] = [
				'label' => __( 'Age', 'astroway' ),
				'value' => sprintf(
					/* translators: %s = number of days, may be fractional */
					__( '%s days', 'astroway' ),
					self::number( (float) $data['ageDays'], 1 )
				),
			];
		}
		if ( ! empty( $data['sunSign'] ) ) {
			$facts[] = [
				'label' => __( 'Sun in', 'astroway' ),
				'glyph' => strtolower( (string) $data['sunSign'] ),
				'value' => self::localised( $data, 'sunSign', self::sign_label( (string) $data['sunSign'] ) ),
			];
		}

		$inner = UI::header(
			[
				'title'     => $name,
				'medal'     => '' === $sign ? '' : strtolower( $sign ),
				'meta_html' => self::date_line( (string) ( $data['date'] ?? '' ) ),
			]
		);

		// The drawing carries the state the numbers describe: lit fraction and
		// which limb it sits on, so the picture cannot disagree with the figure.
		// The word under it is for a reader who does not know which limb means growing.
		$row  = '<div class="astroway-card__moon">' . UI::moon( null === $lit ? 0.5 : $lit / 100, $waxing );
		$row .= '<p class="astroway-card__phase">' . esc_html( $waxing ? __( 'Waxing', 'astroway' ) : __( 'Waning', 'astroway' ) ) . '</p></div>';
		if ( null !== $lit ) {
			$row .= UI::score( (int) round( $lit ), __( 'Illuminated', 'astroway' ) );
		}
		$inner .= '<div class="astroway-card__moonrow">' . $row . '</div>';
		$inner .= UI::shown( 'facts' ) ? UI::facts( $facts, 'trio' ) : '';
		$inner .= UI::shown( 'meaning' ) ? self::moon_meaning( $data, $lang ) : '';
		$inner .= self::moon_cycle( $data );
		$inner .= self::moon_void( $data );

		return self::shell( 'moon_phase', $lang, $inner );
	}

	/**
	 * The void of course windows the api lists for the day: from the Moon's last
	 * aspect to its entry into the next sign, when old astrology says not to
	 * start things. A day the api lists none for says so; a payload from before
	 * the field shows nothing.
	 */
	private static function moon_void( array $data ): string {
		if ( ! array_key_exists( 'voidOfCourse', $data ) || ! is_array( $data['voidOfCourse'] ) ) {
			return '';
		}
		/* translators: PHP date format for a day within the coming month, such as "Oct 10" */
		$day  = _x( 'M j', 'moon cycle date', 'astroway' );
		$time = (string) get_option( 'time_format', 'H:i' );
		$at   = static function ( string $iso, int $stamp, bool $date ) use ( $day, $time ): string {
			return '<time datetime="' . esc_attr( $iso ) . '">' . ( $date ? '<span class="astroway-nowrap">' . esc_html( (string) wp_date( $day, $stamp ) ) . '</span> ' : '' )
				. '<span class="astroway-nowrap">' . esc_html( (string) wp_date( $time, $stamp ) ) . '</span></time>';
		};
		$rows = '';
		foreach ( $data['voidOfCourse'] as $w ) {
			$start = is_array( $w ) && is_string( $w['start'] ?? null ) && preg_match( '/^\d{4}-\d{2}-\d{2}T[\d:]+Z$/', $w['start'] ) ? strtotime( $w['start'] ) : false;
			$end   = is_array( $w ) && is_string( $w['end'] ?? null ) && preg_match( '/^\d{4}-\d{2}-\d{2}T[\d:]+Z$/', $w['end'] ) ? strtotime( $w['end'] ) : false;
			if ( false === $start || false === $end ) {
				continue;
			}
			// The sign leads in the narrow column and the window takes the wide one;
			// the other way round it broke into four lines at 375px.
			$next     = is_string( $w['nextSign'] ?? null ) ? self::sign_label( $w['nextSign'] ) : '';
			$same_day = wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end );
			$rows    .= '<div class="astroway-facts__item"><dt>' . ( '' === $next ? '' : esc_html( sprintf( /* translators: %s = zodiac sign the Moon enters when the window ends */ __( 'Next sign: %s', 'astroway' ), $next ) ) ) . '</dt><dd>'
				. $at( $w['start'], $start, true ) . ' – ' . $at( $w['end'], $end, ! $same_day ) . '</dd></div>';
		}
		$body = '' === $rows
			? '<p class="astroway-card__intro">' . esc_html__( 'No void of course Moon today.', 'astroway' ) . '</p>'
			: '<dl class="astroway-facts astroway-facts--list">' . $rows . '</dl>';
		return UI::section( __( 'Void of course Moon', 'astroway' ), $body, '', 'voc' );
	}

	/** Degrees and minutes inside a sign, floored so 29.99 never reads as 30. */
	private static function arc( float $degree ): string {
		$minutes = (int) floor( max( 0.0, $degree ) * 60 );
		return sprintf( '%d°%02d′', intdiv( $minutes, 60 ) % 30, $minutes % 60 );
	}

	/**
	 * What the phase means. Until the api has translated it, `localized` repeats
	 * the English, and an English sentence on another language's page is worse
	 * than no sentence, so the part waits for the translation.
	 */
	private static function moon_meaning( array $data, string $lang ): string {
		$english  = (string) ( $data['meaning'] ?? '' );
		$en_words = is_array( $data['keywords'] ?? null ) ? $data['keywords'] : [];
		if ( 'en' === Plugin::resolve_lang( $lang ) ) {
			$meaning = $english;
			$words   = $en_words;
		} else {
			$local   = is_array( $data['localized'] ?? null ) ? $data['localized'] : [];
			$meaning = is_string( $local['meaning'] ?? null ) && $local['meaning'] !== $english ? $local['meaning'] : '';
			$words   = is_array( $local['keywords'] ?? null ) && $local['keywords'] !== $en_words ? $local['keywords'] : [];
		}
		$words = array_filter( $words, 'is_string' );
		$html  = '' === trim( $meaning ) ? '' : '<div class="astroway-card__body"><p>' . esc_html( trim( $meaning ) ) . '</p></div>';
		return $html . UI::tags( $words );
	}

	/** The next four main phases, soonest first, and how far the Moon is. */
	private static function moon_cycle( array $data ): string {
		$names = [
			'newMoon'      => __( 'New Moon', 'astroway' ),
			'firstQuarter' => __( 'First Quarter', 'astroway' ),
			'fullMoon'     => __( 'Full Moon', 'astroway' ),
			'lastQuarter'  => __( 'Last Quarter', 'astroway' ),
		];
		$next  = is_array( $data['next'] ?? null ) ? $data['next'] : [];
		$found = [];
		foreach ( $names as $key => $label ) {
			$iso = $next[ $key ] ?? null;
			if ( ! is_string( $iso ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?Z$/', $iso ) ) {
				continue;
			}
			$stamp = strtotime( $iso );
			if ( false !== $stamp ) {
				$found[] = [ $stamp, $iso, $label ];
			}
		}
		usort( $found, static fn( $a, $b ) => $a[0] <=> $b[0] );

		// All four fall within a month, so the year is noise and the site's long
		// date format wrapped the time onto a line of its own at 375px.
		/* translators: PHP date format for a day within the coming month, such as "Oct 10" */
		$day  = _x( 'M j', 'moon cycle date', 'astroway' );
		$time = (string) get_option( 'time_format', 'H:i' );
		$rows = '';
		foreach ( $found as [ $stamp, $iso, $label ] ) {
			$rows .= '<div class="astroway-facts__item"><dt>' . esc_html( $label ) . '</dt><dd><time datetime="' . esc_attr( $iso ) . '">'
				. '<span class="astroway-nowrap">' . esc_html( (string) wp_date( $day, $stamp ) ) . '</span> '
				. '<span class="astroway-facts__sub">' . esc_html( (string) wp_date( $time, $stamp ) ) . '</span></time></dd></div>';
		}
		$tithi = is_array( $data['tithi'] ?? null ) ? $data['tithi'] : [];
		if ( is_string( $tithi['name'] ?? null ) && '' !== trim( $tithi['name'] ) ) {
			$paksha = [
				'krishna' => __( 'Krishna, waning', 'astroway' ),
				'shukla'  => __( 'Shukla, waxing', 'astroway' ),
			][ $tithi['paksha'] ?? '' ] ?? '';
			$number = is_numeric( $tithi['number'] ?? null ) ? (int) $tithi['number'] : 0;
			$sub    = $number > 0 ? sprintf( /* translators: %d = tithi number, 1 to 30 */ __( '%d of 30', 'astroway' ), $number ) : '';
			// Sanskrit names, the same in every language, as Vedic calendars print them.
			// The fortnight on a row of its own: beside the name it ran past a 320px card.
			$lead = '<div class="astroway-facts__item"><dt>' . esc_html__( 'Tithi', 'astroway' ) . '</dt><dd>' . esc_html( trim( $tithi['name'] ) )
				. ( '' === $sub ? '' : ' <span class="astroway-facts__sub">' . esc_html( $sub ) . '</span>' ) . '</dd></div>';
			if ( '' !== $paksha ) {
				$lead .= '<div class="astroway-facts__item"><dt>' . esc_html__( 'Paksha', 'astroway' ) . '</dt><dd>' . esc_html( $paksha ) . '</dd></div>';
			}
			$rows = $lead . $rows;
		}
		if ( is_numeric( $data['distanceKm'] ?? null ) && (float) $data['distanceKm'] > 0 ) {
			$rows .= '<div class="astroway-facts__item"><dt>' . esc_html__( 'Distance', 'astroway' ) . '</dt><dd>'
				. esc_html(
					sprintf(
						/* translators: %s = distance in kilometres */
						__( '%s km', 'astroway' ),
						self::number( (float) $data['distanceKm'], 0 )
					)
				) . '</dd></div>';
		}
		if ( '' === $rows ) {
			return '';
		}
		return UI::section( __( 'Moon cycle', 'astroway' ), '<dl class="astroway-facts astroway-facts--list">' . $rows . '</dl>', '', 'phases' );
	}

	private static function tarot_card( array $data, string $lang, array $params = [] ): string {
		$drawn = $data['drawn'][0] ?? null;
		if ( ! is_array( $drawn ) || ! is_array( $drawn['card'] ?? null ) ) {
			return '';
		}
		$read = Tarot::read( $drawn, is_array( $data['localized'] ?? null ) ? $data['localized'] : [], 'en' === Plugin::resolve_lang( $lang ) );
		if ( '' === $read['name'] ) {
			return '';
		}

		$inner     = UI::header(
			[
				'title'     => __( 'Card of the day', 'astroway' ),
				'meta_html' => self::kind_meta( __( 'Rider-Waite-Smith', 'astroway' ), (string) ( $data['date'] ?? '' ) ),
			]
		);
		$localized = is_array( $data['localized'] ?? null ) ? $data['localized'] : [];
		$english   = 'en' === Plugin::resolve_lang( $lang );
		$pictures  = '' !== (string) ( $params['art'] ?? '' );
		$inner    .= '<div class="astroway-tarot">' . Tarot::face( $drawn, $pictures ) . '<div class="astroway-tarot__text">';
		$inner    .= '<p class="astroway-card__lead">' . esc_html( Tarot::title( $read ) ) . '</p>';
		$inner    .= UI::shown( 'tags' ) ? UI::tags( Tarot::kind_tags( $drawn, $localized ) ) : '';
		if ( '' !== $read['meaning'] ) {
			$inner .= '<div class="astroway-card__body"><p>' . esc_html( $read['meaning'] ) . '</p></div>';
		}
		$inner .= UI::shown( 'keywords' ) ? UI::tags( $read['keywords'] ) : '';
		$inner .= '</div></div>';
		$inner .= UI::shown( 'facts' ) ? UI::facts( Tarot::facts( (array) $drawn['card'], $english ) ) : '';

		$about = UI::shown( 'about' ) ? Tarot::about( $drawn, Tarot::texts( $lang, [ (string) ( $drawn['card']['slug'] ?? '' ) ] ) ) : '';
		if ( '' !== $about ) {
			$inner .= UI::accordion(
				[
					[
						'title'     => __( 'About the card', 'astroway' ),
						'body_html' => $about,
						'open'      => true,
					],
				]
			);
		}

		// The other way up stays closed: it is for study, not the answer of the day.
		$other = Tarot::other_side( $drawn, $localized, $english );
		if ( UI::shown( 'other-side' ) && ( '' !== $other['meaning'] || ! empty( $other['keywords'] ) ) ) {
			$body   = '' === $other['meaning'] ? '' : '<p>' . esc_html( $other['meaning'] ) . '</p>';
			$inner .= UI::accordion(
				[
					[
						'title'     => $other['title'],
						'body_html' => $body . UI::tags( $other['keywords'] ),
					],
				]
			);
		}

		return self::shell( 'tarot_daily', $lang, $inner );
	}

	/**
	 * A spread of the day: three cards in a row, or the Celtic Cross laid out
	 * with the ten cards listed under it.
	 */
	private static function spread_card( string $widget, array $data, string $lang, array $params = [] ): string {
		$drawn = array_values( array_filter( (array) ( $data['drawn'] ?? [] ), 'is_array' ) );
		$cross = 'tarot_celtic_cross' === $widget;
		if ( count( $drawn ) !== ( $cross ? 10 : 3 ) ) {
			return '';
		}
		$localized = is_array( $data['localized'] ?? null ) ? $data['localized'] : [];

		$english = 'en' === Plugin::resolve_lang( $lang );
		$opts    = [
			'pictures' => '' !== (string) ( $params['art'] ?? '' ),
			'reveal'   => 'click' === ( $params['reveal'] ?? '' ),
		];
		if ( $opts['reveal'] ) {
			$opts['back'] = Tarot::back( $opts['pictures'] && is_array( $data['back'] ?? null ) ? $data['back'] : [] );
		}
		// Read once for the whole spread, not once per card.
		$opts['texts'] = UI::shown( 'about' ) ? Tarot::texts( $lang, array_map( static fn( $one ) => (string) ( $one['card']['slug'] ?? '' ), array_filter( $drawn, 'is_array' ) ) ) : [];
		$items         = '';
		foreach ( $drawn as $i => $one ) {
			$items .= Tarot::item( $one, $localized, $english, $cross ? $i + 1 : 0, $opts + [ 'index' => $i + 1 ] );
		}
		if ( '' === $items ) {
			return '';
		}

		$title = $english ? (string) ( $data['spread']['name'] ?? '' ) : '';
		if ( ! empty( $localized['textLocalized'] ) && '' !== trim( (string) ( $localized['spread']['name'] ?? '' ) ) ) {
			$title = (string) $localized['spread']['name'];
		}

		$redrawn = ! empty( $params['redrawn'] );
		$inner   = UI::header(
			[
				'title'     => '' === trim( $title ) ? __( 'Tarot spread', 'astroway' ) : $title,
				'meta_html' => $redrawn ? esc_html__( 'New draw', 'astroway' ) : esc_html__( 'Spread of the day', 'astroway' ),
			]
		);
		$inner  .= $cross ? Tarot::cross( $drawn, $opts ) : '';
		$inner  .= '<ol class="astroway-spread astroway-spread--' . ( $cross ? 'cross' : 'three' ) . ( $opts['reveal'] ? ' astroway-spread--reveal' : '' ) . '">' . $items . '</ol>';
		if ( $opts['reveal'] ) {
			$inner .= '<p class="astroway-tarot__open-all"><button type="button" class="astroway-button" data-astroway-open-all><span>' . esc_html__( 'Turn all the cards over', 'astroway' ) . '</span></button></p>';
		}
		if ( '' !== (string) ( $params['reading_html'] ?? '' ) ) {
			// Already read: the page only shows it, after the last card when they lie face down.
			$inner .= '<div class="astroway-tarot__reading" data-astroway-ready' . ( $opts['reveal'] ? ' hidden' : '' ) . '>' . $params['reading_html'] . '</div>';
		} elseif ( ! empty( $params['reading'] ) ) {
			$inner .= self::reading_slot( $widget, $data, $lang, $params );
		}
		if ( $opts['reveal'] || ( ! empty( $params['reading'] ) && '' === (string) ( $params['reading_html'] ?? '' ) ) ) {
			wp_enqueue_script( Plugin::TAROT_HANDLE );
		}
		if ( '' !== (string) ( $params['redraw_id'] ?? '' ) ) {
			$inner .= Form::redraw_control( (string) $params['redraw_id'], $redrawn, (string) ( $params['redraw_note'] ?? '' ) );
		}

		return self::shell( $widget, $lang, $inner );
	}

	/**
	 * Where the reading of the spread arrives once the cards are open: what to
	 * ask for is this very draw, the seed the api drew it with.
	 */
	private static function reading_slot( string $widget, array $data, string $lang, array $params ): string {
		return sprintf(
			'<div class="astroway-tarot__reading" data-astroway-reading="%1$s" data-astroway-seed="%2$d" data-astroway-lang="%3$s" data-astroway-art="%4$s" data-astroway-endpoint="%5$s" data-astroway-wait="%6$s" data-astroway-failed="%7$s"%8$s aria-live="polite"></div>',
			esc_attr( $widget ),
			(int) ( $data['seed'] ?? 0 ),
			esc_attr( $lang ),
			esc_attr( (string) ( $params['art'] ?? '' ) ),
			esc_url( rest_url( TarotReading::ROUTE_NS . TarotReading::ROUTE ) ),
			esc_attr__( 'Reading the spread…', 'astroway' ),
			esc_attr__( 'The reading could not be had just now.', 'astroway' ),
			// Only an administrator's request carries the nonce, so only they are told why it failed.
			function_exists( 'wp_create_nonce' ) && current_user_can( 'manage_options' ) ? ' data-astroway-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"' : ''
		);
	}

	private static function planet_card( array $data, string $lang ): string {
		$planet = (string) ( $data['planet'] ?? '' );
		if ( '' === $planet ) {
			return '';
		}

		$name = self::localised( $data, 'planet', self::planet_label( $planet ) );

		$inner = UI::header(
			[
				'title'     => $name,
				'medal'     => self::planet_id( $planet ),
				'meta_html' => self::kind_meta( __( 'Planet of the day', 'astroway' ), (string) ( $data['date'] ?? '' ) ),
			]
		);

		$themes = isset( $data['themes'] ) && is_array( $data['themes'] ) ? $data['themes'] : [];
		if ( ! empty( $themes ) ) {
			$items = '';
			foreach ( $themes as $theme ) {
				$items .= '<li>' . esc_html( (string) $theme ) . '</li>';
			}
			$inner .= UI::section( __( 'Themes of the day', 'astroway' ), UI::prose( '<ul>' . $items . '</ul>' ) );
		}

		return self::shell( 'planet_of_day', $lang, $inner );
	}

	private static function shell( string $widget, string $lang, string $inner ): string {
		list( $tag, $modifier ) = self::CARDS[ $widget ];
		return UI::card( $tag, $modifier, $inner, [ 'lang' => $lang ] );
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
	public static function sign_label( string $raw ): string {
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
						$items .= '<li>' . self::inline( (string) preg_replace( '/^\s*[-*–•]\s+/u', '', $line ) ) . '</li>';
					}
					$html .= '<ul>' . $items . '</ul>';
				} else {
					$prose = array_merge( $prose, $bullets );
				}
				$bullets = [];
			};

			foreach ( $lines as $line ) {
				$line = (string) $line;
				if ( preg_match( '/^\s*[-*–•]\s+\S/u', $line ) ) {
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
		$items = [];
		foreach ( $rows as $label => $value ) {
			$items[] = [
				'label' => (string) $label,
				'value' => (string) $value,
			];
		}
		return UI::facts( $items );
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
		if ( ! ( new ApiClient() )->can_read_sky() ) {
			return self::admin_note(
				sprintf(
					/* translators: %s = shortcode tag */
					__( '%s needs an API key: this site asks for its own automatically, or paste yours in AstroWay, API Key. Only administrators see this note.', 'astroway' ),
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
			case 'transit':
				$data   = Sky::planets( (string) ( $params['date'] ?? '' ), $now );
				$markup = is_array( $data ) ? self::transit_card( $data, $lang, $params, $now ) : '';
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

		$state = null !== $current
			? UI::badge( __( 'Retrograde', 'astroway' ), 'tens' )
			: UI::badge( __( 'Direct', 'astroway' ), 'muted' );
		$inner = UI::header(
			[
				'title'     => $name,
				'medal'     => $slug,
				'meta_html' => $state . ' ' . self::kind_meta( __( 'Retrograde watch', 'astroway' ), PublicData::site_day( 'day', $now ) ),
			]
		);

		if ( null !== $current ) {
			$inner .= UI::callout(
				'<p>' . esc_html(
					sprintf(
						/* translators: %s = planet name */
						__( 'Yes, %s is retrograde right now.', 'astroway' ),
						$name
					)
				) . '</p>',
				'tens'
			);
			$rows = [
				__( 'Retrograde since', 'astroway' ) => self::sky_date( $current['start'], $offset ),
				__( 'Direct again on', 'astroway' )  => self::sky_date( $current['end'], $offset ),
				__( 'Days left', 'astroway' )        => self::days_between( $now, $current['end'] ),
			];
		} else {
			$inner .= UI::callout(
				'<p>' . esc_html(
					sprintf(
						/* translators: %s = planet name */
						__( 'No, %s is direct right now.', 'astroway' ),
						$name
					)
				) . '</p>'
			);
			$rows = [
				__( 'Next retrograde', 'astroway' ) => self::sky_date( $next['start'], $offset ),
				__( 'Ends', 'astroway' )            => self::sky_date( $next['end'], $offset ),
				__( 'Days until', 'astroway' )      => self::days_between( $now, $next['start'] ),
			];
		}

		return self::shell( 'retrograde', $lang, $inner . self::rows_as_facts( $rows ) );
	}

	/** Every planet that stations, and what each is doing today. */
	/** Mean daily motion in degrees; under a tenth of it, a planet reads as standing at its station. */
	private const MEAN_SPEED = [
		'mercury' => 1.383,
		'venus'   => 1.2,
		'mars'    => 0.524,
		'jupiter' => 0.083,
		'saturn'  => 0.0335,
		'uranus'  => 0.0117,
		'neptune' => 0.006,
		'pluto'   => 0.004,
	];

	/**
	 * Where the ten bodies stand on a day: sign and degree, and which way they move.
	 *
	 * Drawn into the page from the owner's key; it replaces the frame, which a
	 * visitor past the hourly limit saw fold away to nothing.
	 */
	private static function transit_card( array $data, string $lang, array $params, int $now ): string {
		$rows = [];
		foreach ( (array) ( $data['planets'] ?? [] ) as $planet ) {
			if ( ! is_array( $planet ) || ! isset( $planet['longitude'], $planet['name'] ) || (int) ( $planet['id'] ?? 99 ) > 9 ) {
				continue;
			}
			$id    = self::planet_id( (string) $planet['name'] );
			$name  = self::planet_label( (string) $planet['name'] );
			$speed = (float) ( $planet['speedLong'] ?? 0 );
			$mean  = self::MEAN_SPEED[ $id ] ?? 0.0;
			if ( $mean > 0 && abs( $speed ) < $mean / 10 ) {
				$motion = esc_html__( 'Stationary', 'astroway' );
			} elseif ( ! empty( $planet['isRetrograde'] ) ) {
				$motion = '<span class="astroway-nowrap"><span aria-hidden="true">℞</span> ' . esc_html__( 'Retrograde', 'astroway' ) . '</span>';
			} else {
				$motion = esc_html__( 'Direct', 'astroway' );
			}
			$rows[] = [
				'cells' => [
					[ 'html' => Glyphs::icon( $id, $name ) . ' ' . esc_html( $name ) ],
					self::position_label( (float) $planet['longitude'] ),
					[ 'html' => $motion ],
				],
			];
		}
		if ( empty( $rows ) ) {
			return '';
		}
		$date   = (string) ( $params['date'] ?? '' );
		$inner  = UI::header(
			[
				'title'     => __( 'Planets in the sky', 'astroway' ),
				'medal'     => 'sun',
				'meta_html' => self::date_line( '' !== $date ? $date : PublicData::site_day( 'day', $now ) ),
			]
		);
		$inner .= self::sky_table( [ __( 'Planet', 'astroway' ), __( 'Position', 'astroway' ), __( 'Motion', 'astroway' ) ], $rows );
		return self::shell( 'transit', $lang, $inner );
	}

	/** "Turns direct at Aquarius 3°04'", from the longitude of a station; '' without one. */
	private static function station_line( ?float $longitude, string $which ): string {
		if ( null === $longitude ) {
			return '';
		}
		$where = self::position_label( $longitude );
		$text  = 'direct' === $which
			/* translators: %s = sign and degree, such as Aquarius 3°04' */
			? sprintf( __( 'Turns direct at %s', 'astroway' ), $where )
			/* translators: %s = sign and degree, such as Scorpio 8°29' */
			: sprintf( __( 'Turns retrograde at %s', 'astroway' ), $where );
		return '<span class="astroway-retro__station">' . esc_html( $text ) . '</span>';
	}

	/** The shadow: the stretch of sky the planet covers three times, before and after the loop. */
	private static function shadow_line( string $format, int $moment, float $offset ): string {
		return '<span class="astroway-retro__station astroway-retro__shadow">' . esc_html( sprintf( $format, self::sky_date( $moment, $offset ) ) ) . '</span>';
	}

	private static function retrograde_board( array $data, string $lang, int $now ): string {
		$offset    = Sky::offset( '', $now );
		$rows      = [];
		$backwards = [];

		foreach ( Sky::PLANETS as $slug => $planet_id ) {
			$name                   = self::planet_label( ucfirst( $slug ) );
			$periods                = Sky::periods_of( $data, $planet_id );
			list( $current, $next ) = Sky::state_at( $periods, $now );

			$now_cell  = esc_html__( 'Direct', 'astroway' );
			$next_cell = '';
			$shadow    = null === $current ? Sky::shadow_at( $periods, $now ) : null;
			if ( null !== $shadow ) {
				$now_cell .= self::shadow_line(
					'pre' === $shadow[0]
						/* translators: %s = date */
						? __( 'In the pre-retrograde shadow since %s', 'astroway' )
						/* translators: %s = date */
						: __( 'In the post-retrograde shadow until %s', 'astroway' ),
					$shadow[1],
					$offset
				);
			}
			if ( null !== $current ) {
				$backwards[] = $name;
				$total       = max( 1, (int) ceil( ( $current['end'] - $current['start'] ) / DAY_IN_SECONDS ) );
				$day         = min( $total, (int) floor( ( $now - $current['start'] ) / DAY_IN_SECONDS ) + 1 );
				$now_cell    = '<strong>' . esc_html__( 'Retrograde', 'astroway' ) . '</strong> '
					. '<span class="astroway-retro__dates">' . esc_html( self::sky_range( $current['start'], $current['end'], $offset ) ) . '</span>'
					/* translators: 1: day number, 2: days in the retrograde period */
					. '<span class="astroway-retro__progress">' . esc_html( sprintf( __( 'Day %1$d of %2$d', 'astroway' ), $day, $total ) ) . UI::meter( $day / $total ) . '</span>'
					. self::station_line( $current['station_direct'] ?? null, 'direct' );
				if ( null !== ( $current['shadow_end'] ?? null ) ) {
					/* translators: %s = date */
					$now_cell .= self::shadow_line( __( 'Leaves the shadow on %s', 'astroway' ), $current['shadow_end'], $offset );
				}
			}
			if ( null !== $next ) {
				$next_cell = '<span class="astroway-retro__dates">' . esc_html( self::sky_range( $next['start'], $next['end'], $offset ) ) . '</span>'
					. self::station_line( $next['station_retro'] ?? null, 'retro' );
				if ( null !== ( $next['shadow_start'] ?? null ) && $next['shadow_start'] > $now ) {
					/* translators: %s = date */
					$next_cell .= self::shadow_line( __( 'Shadow from %s', 'astroway' ), $next['shadow_start'], $offset );
				}
			}

			$rows[] = [
				// One block per cell: a stacked cell is a label column and a value column.
				'cells'   => [ $name, [ 'html' => '<div class="astroway-retro">' . $now_cell . '</div>' ], [ 'html' => '' === $next_cell ? '' : '<div class="astroway-retro">' . $next_cell . '</div>' ] ],
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

		$inner  = UI::header(
			[
				'title'     => __( 'Retrograde planets', 'astroway' ),
				'medal'     => 'retrograde',
				'meta_html' => self::date_line( PublicData::site_day( 'day', $now ) ),
			]
		);
		$inner .= UI::callout( '<p>' . esc_html( $lead ) . '</p>', empty( $backwards ) ? '' : 'tens' );
		$inner .= self::sky_table(
			[
				__( 'Planet', 'astroway' ),
				// The period in force and the one to come apart: one shared column
				// read a direct planet's coming retrograde as the present one.
				__( 'Now', 'astroway' ),
				__( 'Next retrograde', 'astroway' ),
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

		$inner  = UI::header(
			[
				'title'     => __( 'Void of course Moon', 'astroway' ),
				'medal'     => 'moon',
				'meta_html' => self::date_line( Sky::local_date( $offset, $now ) ),
			]
		);
		$inner .= UI::callout( '<p>' . esc_html( $lead ) . '</p>', null === $open ? '' : 'tens' );
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

		$inner = UI::header(
			[
				'title'     => __( 'Planetary hours', 'astroway' ),
				'medal'     => self::planet_id( (string) ( $data['dayRulerPlanetName'] ?? '' ) ),
				'meta_html' => self::date_line( $date ),
			]
		);
		if ( '' !== $lead ) {
			$inner .= UI::callout( '<p>' . esc_html( $lead ) . '</p>' );
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
		$inner  = UI::header(
			[
				'title'     => __( 'Planetary hours', 'astroway' ),
				'meta_html' => self::date_line( $date ),
			]
		);
		$inner .= UI::callout(
			'<p>' . esc_html(
				'polar-day' === $state
					? __( 'The Sun does not set at this latitude on this date, so there are no planetary hours to divide.', 'astroway' )
					: __( 'The Sun does not rise at this latitude on this date, so there are no planetary hours to divide.', 'astroway' )
			) . '</p>'
		);

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

		$named_a = trim( (string) ( $params['name_a'] ?? '' ) );
		$named_b = trim( (string) ( $params['name_b'] ?? '' ) );
		$first   = '' === $named_a ? __( 'First chart', 'astroway' ) : $named_a;
		$second  = '' === $named_b ? __( 'Second chart', 'astroway' ) : $named_b;
		$title   = '' !== $named_a && '' !== $named_b
			/* translators: 1: first person's name, 2: second person's name */
			? sprintf( __( '%1$s and %2$s', 'astroway' ), $named_a, $named_b )
			: __( 'Synastry', 'astroway' );

		$spheres = is_array( $data['spheres'] ?? null ) ? $data['spheres'] : [];
		$inner   = UI::header( [ 'title' => $title ] );
		$inner  .= self::synastry_verdict( $score, (string) ( $data['label'] ?? '' ), isset( $data['count'] ) && is_numeric( $data['count'] ) ? (int) $data['count'] : count( $aspects ) );
		$inner  .= UI::shown( 'summary' ) ? self::synastry_summary( $aspects ) : '';
		$inner  .= self::synastry_spheres( $spheres );
		$inner  .= self::synastry_strengths( $spheres );

		// Since api 2.234.1 the pair's answer carries both charts; an answer
		// cached before that sends each chart for on its own.
		$remember = empty( $params['nocache'] );
		$chart_a  = self::synastry_chart( $params, 'a', $remember, $data['chart1'] ?? null );
		$chart_b  = self::synastry_chart( $params, 'b', $remember, $data['chart2'] ?? null );

		foreach ( [ [ $first, $chart_a, 'a' ], [ $second, $chart_b, 'b' ] ] as list( $who, $chart, $side ) ) {
			if ( null === $chart ) {
				continue;
			}
			$when   = self::natal_meta( self::side( $params, $side ) );
			$inner .= '<h4 class="astroway-card__subtitle">' . esc_html( $who ) . ( '' === $when ? '' : ' <span class="astroway-card__aside">' . $when . '</span>' ) . '</h4>';
			$inner .= self::natal_big_three( $chart['planets'], $chart['houses'] );
		}
		foreach ( [ [ $first, 'a' ], [ $second, 'b' ] ] as list( $who, $side ) ) {
			if ( ! self::known_time( $params, $side ) ) {
				$inner .= UI::callout(
					esc_html(
						sprintf(
							/* translators: %s = person's name, or "First chart" */
							__( '%s: birth time unknown. No houses or Ascendant for this chart, and its Moon may be up to 7 degrees from where it was.', 'astroway' ),
							$who
						)
					)
				);
			}
		}

		$close  = self::classical_only( $aspects );
		$panes  = [
			[
				'label' => __( 'Wheel', 'astroway' ),
				'key'   => 'wheel',
				'html'  => self::synastry_wheel( $chart_a, $chart_b, $close, $first, $second, $title ),
			],
			[
				'label' => __( 'Aspects', 'astroway' ),
				'key'   => 'aspects',
				'html'  => self::synastry_aspects( array_slice( $close, 0, self::aspect_count( $params ) ), $first, $second ),
			],
			[
				'label' => __( 'Houses', 'astroway' ),
				'key'   => 'houses',
				'html'  => self::synastry_houses( $chart_a, $chart_b, $first, $second ),
			],
			[
				// Two wordings were wrong before this one. "Points included" collides
				// with the word for a score in half the target languages, and "Show
				// all %d aspects" put the number next to a noun four Slavic locales
				// must decline. The count agrees with nothing, so it is right in all.
				'label' => __( 'All aspects', 'astroway' ),
				'key'   => 'all-aspects',
				'html'  => self::aspect_grid( $first, $second, self::aspect_rows( $aspects, 0 ) ),
				'count' => count( $aspects ),
			],
		];
		$inner .= UI::tabs( __( 'Synastry', 'astroway' ), $panes );

		return self::shell( 'synastry', $lang, $inner );
	}

	/** One partner's attributes under the names a single chart uses. */
	private static function side( array $params, string $side ): array {
		return [
			'date'         => (string) ( $params[ 'date_' . $side ] ?? '' ),
			'time'         => (string) ( $params[ 'time_' . $side ] ?? '' ),
			'lat'          => (string) ( $params[ 'lat_' . $side ] ?? '' ),
			'lng'          => (string) ( $params[ 'lng_' . $side ] ?? '' ),
			'tz'           => (string) ( $params[ 'tz_' . $side ] ?? '' ),
			'lang'         => (string) ( $params['lang'] ?? '' ),
			'time_unknown' => ! self::known_time( $params, $side ),
		];
	}

	private static function known_time( array $params, string $side ): bool {
		return (bool) preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', trim( (string) ( $params[ 'time_' . $side ] ?? '' ) ) );
	}

	/**
	 * One partner's chart: planets, and houses only when the time and the
	 * place are both known, or null when the api did not answer. Asked at noon
	 * when the time is unknown, as the pair itself is.
	 */
	private static function synastry_chart( array $params, string $side, bool $remember, $given = null ): ?array {
		$one = self::side( $params, $side );
		if ( $one['time_unknown'] ) {
			$one['time'] = '12:00';
		}
		$data    = is_array( $given ) && ! empty( $given['planets'] ) && is_array( $given['planets'] ) ? $given : PublicData::get( 'natal', $one, $remember );
		$planets = is_array( $data['planets'] ?? null ) ? $data['planets'] : [];
		if ( empty( $planets ) ) {
			return null;
		}
		$placed = ! $one['time_unknown'] && '' !== trim( $one['lat'] ) && '' !== trim( $one['lng'] );
		$houses = $placed && is_array( $data['houses'] ?? null ) ? $data['houses'] : [];
		return [
			'planets' => $planets,
			'houses'  => $houses,
		];
	}

	/**
	 * The score split by sphere, as bars: the aspects that touch each sphere's
	 * planet, scored by the same model as the whole (api v2.204.0). A sphere
	 * with no aspect has no score, and draws none: an empty sphere is not an
	 * average one. Keys past the six known are left out rather than untranslated.
	 */
	private static function synastry_spheres( array $spheres ): string {
		$names = [
			'identity'      => [ 'sun', __( 'Identity', 'astroway' ) ],
			'emotional'     => [ 'moon', __( 'Emotions', 'astroway' ) ],
			'communication' => [ 'mercury', __( 'Communication', 'astroway' ) ],
			'love'          => [ 'venus', __( 'Love', 'astroway' ) ],
			'passion'       => [ 'mars', __( 'Passion', 'astroway' ) ],
			'commitment'    => [ 'saturn', __( 'Commitment', 'astroway' ) ],
		];
		$rows  = [];
		foreach ( $spheres as $sphere ) {
			$key = is_array( $sphere ) ? (string) ( $sphere['key'] ?? '' ) : '';
			if ( ! isset( $names[ $key ] ) ) {
				continue;
			}
			$score  = is_numeric( $sphere['score'] ?? null ) ? max( 0, min( 100, (int) round( (float) $sphere['score'] ) ) ) : null;
			$word   = self::compat_label( (string) ( $sphere['label'] ?? '' ) );
			$count  = is_numeric( $sphere['count'] ?? null ) ? (int) $sphere['count'] : null;
			$rows[] = [
				'glyph' => $names[ $key ][0],
				'label' => $names[ $key ][1],
				'value' => $score,
				'text'  => null === $score ? __( 'no aspects', 'astroway' ) : (string) $score,
				// A label with a figure, not a sentence that agrees with it: no msgid_plural.
				/* translators: 1: tone word such as "Harmonious", 2: number of aspects */
				'note'  => null === $score || '' === $word || null === $count ? '' : sprintf( __( '%1$s, aspects: %2$d', 'astroway' ), $word, $count ),
			];
		}
		return empty( $rows ) ? '' : UI::section( __( 'By sphere', 'astroway' ), UI::bars( $rows ), '', 'spheres' );
	}

	/** The score in a ring, its word, and how many aspects it rests on. */
	private static function synastry_verdict( ?int $score, string $label, int $total ): string {
		$named = self::compat_label( $label );
		$text  = '' === $named ? '' : '<p class="astroway-verdict__word">' . esc_html( $named ) . '</p>';
		if ( $total > 0 ) {
			// Phrased as a label with a figure rather than a sentence that agrees
			// with it: the plugin has never carried a msgid_plural.
			$text .= '<p class="astroway-verdict__note">' . esc_html(
				sprintf(
					/* translators: %d = how many aspects the two charts make between them */
					__( 'Aspects between the two charts: %d', 'astroway' ),
					$total
				)
			) . '</p>';
		}
		$ring = null === $score ? '' : UI::ring( max( 0, min( 100, $score ) ), 100, __( 'out of 100', 'astroway' ) );
		return '' === $ring . $text ? '' : '<div class="astroway-verdict">' . $ring . '<div>' . $text . '</div></div>';
	}

	/**
	 * The first chart inside with its houses, the second on the outer ring,
	 * and the closest classical aspects between them as lines.
	 */
	private static function synastry_wheel( ?array $chart_a, ?array $chart_b, array $aspects, string $first, string $second, string $title ): string {
		if ( null === $chart_a || null === $chart_b ) {
			return '';
		}
		$ring = static function ( array $planets, string $who ): array {
			$out = [];
			foreach ( $planets as $planet ) {
				if ( ! is_array( $planet ) || ! isset( $planet['longitude'], $planet['name'] ) ) {
					continue;
				}
				$lon   = (float) $planet['longitude'];
				$out[] = [
					'id'    => self::planet_id( (string) $planet['name'] ),
					'lon'   => $lon,
					'rx'    => ! empty( $planet['isRetrograde'] ),
					/* translators: 1: planet, 2: person's name, 3: its position, e.g. "Leo 24°22'" */
					'label' => sprintf( __( '%1$s (%2$s), %3$s', 'astroway' ), self::planet_label( (string) $planet['name'] ), $who, self::position_label( $lon ) ),
				];
			}
			return $out;
		};

		$lines = [];
		foreach ( $aspects as $aspect ) {
			$lines[] = [
				'a'    => self::planet_id( (string) ( $aspect['planetA'] ?? '' ) ),
				'b'    => self::planet_id( (string) ( $aspect['planetB'] ?? '' ) ),
				'kind' => self::aspect_kind( (string) ( $aspect['aspect'] ?? '' ) ),
				'orb'  => (float) ( $aspect['orb'] ?? 0 ),
			];
		}

		$houses = $chart_a['houses'];
		$cusps  = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];
		$wheel  = Wheel::bi(
			[
				'asc'       => (float) ( $houses['ascendant'] ?? 0 ),
				'mc'        => (float) ( $houses['mc'] ?? 0 ),
				'no_angles' => ! isset( $houses['ascendant'] ),
				'cusps'     => $cusps,
				'planets'   => $ring( $chart_a['planets'], $first ),
				'outer'     => $ring( $chart_b['planets'], $second ),
				'aspects'   => $lines,
				'title'     => $title,
				/* translators: 1: first person's name, 2: second person's name */
				'desc'      => sprintf( __( 'Synastry wheel: %1$s inside, %2$s on the outer ring, and the aspects between them.', 'astroway' ), $first, $second ),
				'angles'    => [
					'asc' => __( 'ASC', 'astroway' ),
					'ic'  => __( 'IC', 'astroway' ),
					'dsc' => __( 'DSC', 'astroway' ),
					'mc'  => __( 'MC', 'astroway' ),
				],
			]
		);

		return $wheel . UI::legend(
			[
				[
					'ring'  => 'in',
					/* translators: %s = person's name, or "First chart" */
					'label' => sprintf( __( 'Inner ring: %s', 'astroway' ), $first ),
				],
				[
					'ring'  => 'out',
					/* translators: %s = person's name, or "Second chart" */
					'label' => sprintf( __( 'Outer ring: %s', 'astroway' ), $second ),
				],
				[
					'line'  => 'h',
					'label' => __( 'Harmony', 'astroway' ),
				],
				[
					'line'  => 't',
					'label' => __( 'Tension', 'astroway' ),
				],
			]
		);
	}

	/**
	 * The closest aspects between the two, each saying whose planet is whose
	 * and, in a line, what that kind of contact does between two people.
	 */
	private static function synastry_aspects( array $aspects, string $first, string $second ): string {
		$items = [];
		foreach ( $aspects as $i => $aspect ) {
			$type    = (string) ( $aspect['aspect'] ?? '' );
			$meaning = self::synastry_meaning( $type );
			$items[] = [
				'title'     => sprintf(
					/* translators: 1: first planet, 2: aspect name, 3: second planet */
					__( '%1$s %2$s %3$s', 'astroway' ),
					self::owned( self::planet_label( (string) ( $aspect['planetA'] ?? '' ) ), $first ),
					self::aspect_label( $type ),
					self::owned( self::planet_label( (string) ( $aspect['planetB'] ?? '' ) ), $second )
				),
				'icon_html' => UI::mark( strtolower( trim( $type ) ), self::aspect_kind( $type ) ),
				'meta_html' => esc_html(
					sprintf( '%s°', self::number( (float) ( $aspect['orb'] ?? 0 ), 1 ) )
					. ( is_numeric( $aspect['strength'] ?? null )
						/* translators: %d = how strong an aspect is, 0 to 100 */
						? ' · ' . sprintf( __( 'strength %d', 'astroway' ), max( 0, min( 100, (int) $aspect['strength'] ) ) )
						: '' )
				),
				// Until the api writes a text per pair, the row opens on what the aspect does.
				'body_html' => '' === $meaning ? '' : '<p class="astroway-acc__text">' . esc_html( $meaning ) . '</p>',
				'open'      => 0 === $i,
			];
		}
		return UI::accordion( $items );
	}

	/** Harmonious, challenging and conjunctions between the two charts, as pills. */
	private static function synastry_summary( array $aspects ): string {
		$balance = ChartFacts::balance( $aspects );
		$conj    = count( array_filter( $aspects, static fn( $a ) => is_array( $a ) && 'conjunction' === strtolower( trim( (string) ( $a['aspect'] ?? '' ) ) ) ) );
		if ( 0 === $balance['h'] + $balance['t'] + $conj ) {
			return '';
		}
		return '<ul class="astroway-tags astroway-synastry__summary"><li>'
			/* translators: %d = number of aspects */
			. esc_html( sprintf( __( 'Harmonious %d', 'astroway' ), $balance['h'] ) ) . '</li><li>'
			/* translators: %d = number of aspects */
			. esc_html( sprintf( __( 'Challenging %d', 'astroway' ), $balance['t'] ) ) . '</li><li>'
			/* translators: %d = number of conjunctions */
			. esc_html( sprintf( __( 'Conjunctions %d', 'astroway' ), $conj ) ) . '</li></ul>';
	}

	/**
	 * The two spheres that score highest and the two lowest, each with a
	 * phrase. Four scored spheres at least, or the columns would repeat.
	 */
	private static function synastry_strengths( array $spheres ): string {
		if ( ! UI::shown( 'strengths' ) ) {
			return '';
		}
		$names  = [
			'identity'      => __( 'Identity', 'astroway' ),
			'emotional'     => __( 'Emotions', 'astroway' ),
			'communication' => __( 'Communication', 'astroway' ),
			'love'          => __( 'Love', 'astroway' ),
			'passion'       => __( 'Passion', 'astroway' ),
			'commitment'    => __( 'Commitment', 'astroway' ),
		];
		$strong = [
			'identity'      => __( 'You let each other be who you are.', 'astroway' ),
			'emotional'     => __( 'You read each other\'s moods without words.', 'astroway' ),
			'communication' => __( 'Talking comes easily and settles things.', 'astroway' ),
			'love'          => __( 'Affection and taste are shared.', 'astroway' ),
			'passion'       => __( 'Desire and drive meet in the middle.', 'astroway' ),
			'commitment'    => __( 'Promises hold and plans last.', 'astroway' ),
		];
		$grow   = [
			'identity'      => __( 'Leave each other room to be different.', 'astroway' ),
			'emotional'     => __( 'Say what you feel rather than expect it to be guessed.', 'astroway' ),
			'communication' => __( 'Check what was heard, not only what was said.', 'astroway' ),
			'love'          => __( 'Show care in the way the other one receives it.', 'astroway' ),
			'passion'       => __( 'Agree on pace before friction turns into a fight.', 'astroway' ),
			'commitment'    => __( 'Make plans explicit and keep them small at first.', 'astroway' ),
		];
		$scored = [];
		foreach ( $spheres as $sphere ) {
			$key = is_array( $sphere ) ? (string) ( $sphere['key'] ?? '' ) : '';
			if ( isset( $names[ $key ] ) && is_numeric( $sphere['score'] ?? null ) ) {
				$scored[ $key ] = (float) $sphere['score'];
			}
		}
		if ( count( $scored ) < 4 ) {
			return '';
		}
		arsort( $scored );
		$list   = static function ( array $keys, array $phrases ) use ( $names ): string {
			$html = '';
			foreach ( $keys as $key ) {
				$html .= '<li><strong>' . esc_html( $names[ $key ] ) . '</strong> ' . esc_html( $phrases[ $key ] ) . '</li>';
			}
			return '<ul>' . $html . '</ul>';
		};
		$top    = array_slice( array_keys( $scored ), 0, 2 );
		$bottom = array_reverse( array_slice( array_keys( $scored ), -2 ) );
		return '<div class="astroway-synastry__columns">'
			. '<div class="astroway-synastry__strengths"><h4 class="astroway-card__subtitle">' . esc_html__( 'Strengths', 'astroway' ) . '</h4>' . $list( $top, $strong ) . '</div>'
			. '<div class="astroway-synastry__growth"><h4 class="astroway-card__subtitle">' . esc_html__( 'Growth areas', 'astroway' ) . '</h4>' . $list( $bottom, $grow ) . '</div>'
			. '</div>';
	}

	/**
	 * Each partner's planets in the other's houses, from the two charts the
	 * card already has. Needs the other one's cusps, so its birth time; the
	 * three houses with the most planets are marked.
	 */
	private static function synastry_houses( ?array $chart_a, ?array $chart_b, string $first, string $second ): string {
		if ( null === $chart_a || null === $chart_b ) {
			return '';
		}
		$bodies = [ 'Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto' ];
		$table  = function ( array $planets, array $houses, string $owner, string $host ) use ( $bodies ): string {
			$cusps = isset( $houses['cusps'] ) && is_array( $houses['cusps'] ) ? array_values( $houses['cusps'] ) : [];
			if ( 12 !== count( $cusps ) ) {
				return '';
			}
			$placed = [];
			foreach ( $planets as $planet ) {
				if ( is_array( $planet ) && in_array( (string) ( $planet['name'] ?? '' ), $bodies, true ) && isset( $planet['longitude'] ) ) {
					$placed[] = [ (string) $planet['name'], self::house_of( (float) $planet['longitude'], $cusps ) ];
				}
			}
			$counts = array_count_values( array_filter( array_column( $placed, 1 ), 'is_int' ) );
			arsort( $counts );
			$busy = array_slice( array_keys( array_filter( $counts, static fn( $n ) => $n > 1 ) ), 0, 3 );
			$rows = [];
			foreach ( $placed as list( $name, $house ) ) {
				$id     = self::planet_id( $name );
				$label  = self::planet_label( $name );
				$rows[] = [
					'angle' => in_array( $house, $busy, true ),
					'cells' => [
						[ 'html' => Glyphs::icon( $id, $label ) . ' ' . esc_html( $label ) ],
						null === $house ? '' : sprintf( /* translators: %d = house number */ __( 'House %d', 'astroway' ), $house ),
					],
				];
			}
			return UI::table(
				[
					/* translators: 1: whose planets, 2: whose houses */
					'caption' => sprintf( __( 'Planets of %1$s in the houses of %2$s', 'astroway' ), $owner, $host ),
					'head'    => [ __( 'Planet', 'astroway' ), __( 'House', 'astroway' ) ],
					'rows'    => $rows,
				]
			);
		};
		return $table( $chart_a['planets'], $chart_b['houses'], $first, $second ) . $table( $chart_b['planets'], $chart_a['houses'], $second, $first );
	}

	/**
	 * "Venus (Olena)": whose planet, bound to the planet. A line that breaks
	 * before the bracket leaves "(natal)" alone on the next one.
	 */
	private static function owned( string $planet, string $owner ): string {
		/* translators: 1: planet, 2: person's name, or the word "natal" or "transit" */
		return (string) preg_replace( '/ \(/u', "\u{00A0}(", sprintf( __( '%1$s (%2$s)', 'astroway' ), $planet, $owner ), 1 );
	}

	/** What each major aspect does between two people, in a phrase. */
	private static function synastry_meaning( string $type ): string {
		$meanings = [
			'conjunction' => __( 'One force: felt strongly by both, for better or worse.', 'astroway' ),
			'trine'       => __( 'Flows easily between you, without effort.', 'astroway' ),
			'sextile'     => __( 'Helps you both once one of you makes a move.', 'astroway' ),
			'square'      => __( 'Rubs, and pushes you both to change.', 'astroway' ),
			'opposition'  => __( 'Pulls you apart and draws you together.', 'astroway' ),
		];
		return $meanings[ strtolower( trim( $type ) ) ] ?? '';
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
		if ( empty( $rows ) ) {
			return '';
		}
		// No row header: stacked on a phone, the first planet keeps its owner's
		// name as a label like the second does, instead of an unnamed bold line.
		return UI::table(
			[
				'head'       => [
					$first,
					__( 'Aspect', 'astroway' ),
					$second,
					__( 'Orb', 'astroway' ),
					__( 'Trend', 'astroway' ),
				],
				'rows'       => $rows,
				'stack'      => true,
				'row_header' => false,
			]
		);
	}

	/**
	 * The word the api puts on a score.
	 *
	 * The vocabulary is closed since api v2.204.0 (docs/WP-COMPAT-CONTRACT.md):
	 * harmonious, balanced, mixed, challenging. Anything else is printed as it
	 * arrived: an English word in a German card is poor, an empty space where
	 * the verdict should be is worse.
	 */
	private static function compat_label( string $raw ): string {
		$labels = [
			'harmonious'  => __( 'Harmonious', 'astroway' ),
			'balanced'    => __( 'Balanced', 'astroway' ),
			'mixed'       => __( 'Mixed', 'astroway' ),
			'challenging' => __( 'Challenging', 'astroway' ),
		];
		$key    = strtolower( trim( $raw ) );
		return $labels[ $key ] ?? ucfirst( $key );
	}

	private const ZODIAC = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];

	/**
	 * Every pair of signs, or one sign against the other eleven.
	 *
	 * The api sends a relation and a tone and no number, on purpose: a pair of
	 * Sun signs has nothing to weigh, so a percentage would be invented. The
	 * grid draws the relation as its aspect glyph and the tone as its colour;
	 * two signs one or five apart "do not see each other" and take the
	 * semisextile and quincunx marks, drawn neutral.
	 */
	private static function matrix_card( array $data, string $lang, array $params ): string {
		$pairs = [];
		foreach ( (array) ( $data['pairs'] ?? [] ) as $pair ) {
			if ( is_array( $pair ) && in_array( $pair['signA'] ?? '', self::ZODIAC, true ) && in_array( $pair['signB'] ?? '', self::ZODIAC, true ) ) {
				$pairs[ $pair['signA'] . '|' . $pair['signB'] ] = $pair;
			}
		}
		if ( count( $pairs ) < 66 ) {
			return '';
		}

		$sign = strtolower( trim( (string) ( $params['sign'] ?? '' ) ) );
		$sign = in_array( $sign, self::ZODIAC, true ) ? $sign : '';

		$inner  = UI::header(
			[
				'title' => '' === $sign ? __( 'Sign compatibility', 'astroway' )
					/* translators: %s = zodiac sign name */
					: sprintf( __( '%s and the other signs', 'astroway' ), self::sign_label( $sign ) ),
				'medal' => $sign,
			]
		);
		$inner .= '<p class="astroway-card__intro">' . esc_html__( 'How each pair of signs stands in the zodiac: the classical relation, harmonious, challenging or unrelated. It is a relation of signs, not a score; for two people, their charts say far more.', 'astroway' ) . '</p>';

		if ( '' !== $sign ) {
			$rows = [];
			foreach ( self::ZODIAC as $other ) {
				if ( $other === $sign ) {
					continue;
				}
				$pair   = self::matrix_pair( $pairs, $sign, $other );
				$rows[] = [
					'glyph' => self::relation_glyph( $pair ),
					'kind'  => self::tone_kind( (string) ( $pair['tone'] ?? '' ) ),
					'text'  => sprintf(
						/* translators: 1: zodiac sign, 2: how the two signs relate, e.g. "trine" */
						__( '%1$s: %2$s', 'astroway' ),
						self::sign_label( $other ),
						self::relation_label( (string) ( $pair['relation'] ?? '' ) )
					),
					'note'  => self::relation_note( (string) ( $pair['relation'] ?? '' ) ),
				];
			}
			$inner .= UI::aspects( $rows );
		} else {
			$signs = [];
			foreach ( self::ZODIAC as $one ) {
				$signs[] = [
					'id'   => $one,
					'name' => self::sign_label( $one ),
				];
			}
			$cells = [];
			foreach ( $pairs as $pair ) {
				if ( $pair['signA'] === $pair['signB'] ) {
					continue;
				}
				$cells[] = [
					'a'     => $pair['signA'],
					'b'     => $pair['signB'],
					'glyph' => self::relation_glyph( $pair ),
					'kind'  => self::tone_kind( (string) ( $pair['tone'] ?? '' ) ),
					'text'  => sprintf(
						/* translators: 1: zodiac sign, 2: zodiac sign, 3: how they relate, e.g. "trine" */
						__( '%1$s and %2$s: %3$s', 'astroway' ),
						self::sign_label( $pair['signA'] ),
						self::sign_label( $pair['signB'] ),
						self::relation_label( (string) ( $pair['relation'] ?? '' ) )
					),
				];
			}
			$inner .= UI::aspect_grid( $signs, $cells, __( 'Sign compatibility', 'astroway' ) );
		}
		$inner .= UI::legend(
			[
				[
					'line'  => 'h',
					'label' => __( 'Harmony', 'astroway' ),
				],
				[
					'line'  => 't',
					'label' => __( 'Tension', 'astroway' ),
				],
				[
					'glyph' => 'quincunx',
					'label' => __( 'Do not see each other', 'astroway' ),
				],
			]
		);
		return self::shell( 'zodiac_matrix', $lang, $inner );
	}

	/** The pair in either order: the api sends each once, the earlier sign first. */
	private static function matrix_pair( array $pairs, string $one, string $two ): array {
		return $pairs[ $one . '|' . $two ] ?? $pairs[ $two . '|' . $one ] ?? [];
	}

	private static function relation_glyph( array $pair ): string {
		$relation = (string) ( $pair['relation'] ?? '' );
		if ( 'aversion' === $relation ) {
			return 1 === (int) ( $pair['distance'] ?? 0 ) || 11 === (int) ( $pair['distance'] ?? 0 ) ? 'semisextile' : 'quincunx';
		}
		return 'same' === $relation ? 'conjunction' : $relation;
	}

	private static function tone_kind( string $tone ): string {
		return 'harmonious' === $tone ? 'h' : ( 'challenging' === $tone ? 't' : 'n' );
	}

	private static function relation_label( string $relation ): string {
		$labels = [
			'same'       => __( 'the same sign', 'astroway' ),
			'sextile'    => __( 'sextile', 'astroway' ),
			'square'     => __( 'square', 'astroway' ),
			'trine'      => __( 'trine', 'astroway' ),
			'opposition' => __( 'opposite', 'astroway' ),
			'aversion'   => __( 'aversion', 'astroway' ),
		];
		return $labels[ $relation ] ?? $relation;
	}

	/** What each relation means for two signs, in a phrase. */
	private static function relation_note( string $relation ): string {
		$notes = [
			'sextile'    => __( 'Two signs apart: friendly elements that help each other.', 'astroway' ),
			'square'     => __( 'Three signs apart: the same drive in clashing elements.', 'astroway' ),
			'trine'      => __( 'Four signs apart: the same element, easy understanding.', 'astroway' ),
			'opposition' => __( 'Opposite signs: attraction and pull in one.', 'astroway' ),
			'aversion'   => __( 'One or five signs apart: classically they do not see each other.', 'astroway' ),
		];
		return $notes[ $relation ] ?? '';
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

	/** The earthly branch of each animal, the character the sign is written with. */
	private const BRANCHES = [
		'rat'     => '子',
		'ox'      => '丑',
		'tiger'   => '寅',
		'rabbit'  => '卯',
		'dragon'  => '辰',
		'snake'   => '巳',
		'horse'   => '午',
		'goat'    => '未',
		'monkey'  => '申',
		'rooster' => '酉',
		'dog'     => '戌',
		'pig'     => '亥',
	];

	/** The animal written in full, the character a reader recognises, with its reading in tones. */
	private const ANIMALS = [
		'rat'     => [ '鼠', 'shǔ' ],
		'ox'      => [ '牛', 'niú' ],
		'tiger'   => [ '虎', 'hǔ' ],
		'rabbit'  => [ '兔', 'tù' ],
		'dragon'  => [ '龍', 'lóng' ],
		'snake'   => [ '蛇', 'shé' ],
		'horse'   => [ '馬', 'mǎ' ],
		'goat'    => [ '羊', 'yáng' ],
		'monkey'  => [ '猴', 'hóu' ],
		'rooster' => [ '雞', 'jī' ],
		'dog'     => [ '狗', 'gǒu' ],
		'pig'     => [ '豬', 'zhū' ],
	];

	/** Stems and branches by the toneless pinyin the api sends, with the tones put back. */
	private const TONES = [
		'jia'  => 'jiǎ',
		'yi'   => 'yǐ',
		'bing' => 'bǐng',
		'ding' => 'dīng',
		'ji'   => 'jǐ',
		'geng' => 'gēng',
		'xin'  => 'xīn',
		'ren'  => 'rén',
		'gui'  => 'guǐ',
		'zi'   => 'zǐ',
		'chou' => 'chǒu',
		'yin'  => 'yín',
		'mao'  => 'mǎo',
		'chen' => 'chén',
		'si'   => 'sì',
		'wei'  => 'wèi',
		'shen' => 'shēn',
		'you'  => 'yǒu',
		'xu'   => 'xū',
		'hai'  => 'hài',
	];

	/** The ten heavenly stems by the pinyin the api sends. */
	private const STEMS = [
		'jia'  => '甲',
		'yi'   => '乙',
		'bing' => '丙',
		'ding' => '丁',
		'wu'   => '戊',
		'ji'   => '己',
		'geng' => '庚',
		'xin'  => '辛',
		'ren'  => '壬',
		'gui'  => '癸',
	];

	/**
	 * The Chinese animal of a birth year: its branch character in the colour of
	 * the year's element, the pillar of stem and branch, and the api's reading
	 * of the animal and of the element that colours it.
	 *
	 * The api sends the animal as an emoji too. It is not printed: iOS and
	 * Android draw it in their own styles, and the character is the sign.
	 */
	private static function chinese_card( array $data, string $lang ): string {
		$animal = trim( (string) ( $data['animal'] ?? '' ) );
		if ( '' === $animal ) {
			return '';
		}
		$slug    = strtolower( $animal );
		$element = is_array( $data['element'] ?? null ) ? $data['element'] : [];
		$year_el = strtolower( trim( (string) ( $element['cycling'] ?? '' ) ) );
		$name    = self::animal_label( $animal );

		$year  = (int) ( $data['solarYear'] ?? 0 );
		$title = $name;
		if ( '' !== $year_el && $year > 0 ) {
			/* translators: 1: the year's element, such as Metal, 2: the animal, such as Horse, 3: the year */
			$title = sprintf( __( '%1$s %2$s, %3$d', 'astroway' ), self::element_label( ucfirst( $year_el ) ), $name, $year );
		}
		$inner = UI::header(
			[
				'title'     => $title,
				'meta_html' => esc_html__( 'Chinese zodiac', 'astroway' ),
			]
		);

		$pillar = '';
		$parts  = explode( '-', (string) ( $data['pillar'] ?? '' ) );
		if ( 2 === count( $parts ) ) {
			// The api writes the characters and the tones since 2.234.1; the tables are for an older answer.
			// One CJK character or nothing: anything else from the api falls back to the table.
			$given  = static fn( string $field ): string => is_string( $data[ $field ] ?? null ) && preg_match( '/^\p{Han}$/u', trim( $data[ $field ] ) ) ? trim( $data[ $field ] ) : '';
			$spoken = static fn( string $field ): string => is_string( $data[ $field ] ?? null ) ? trim( $data[ $field ] ) : '';
			$cells  = [
				[ __( 'Heavenly stem', 'astroway' ), '' !== $given( 'stemHanzi' ) ? $given( 'stemHanzi' ) : ( self::STEMS[ strtolower( $parts[0] ) ] ?? '' ), $parts[0], $spoken( 'stemPinyin' ) ],
				[ __( 'Earthly branch', 'astroway' ), '' !== $given( 'branchHanzi' ) ? $given( 'branchHanzi' ) : ( self::BRANCHES[ $slug ] ?? '' ), $parts[1], $spoken( 'branchPinyin' ) ],
			];
			foreach ( $cells as [ $label, $char, $pinyin, $sent ] ) {
				// Wu the stem and wu the branch differ only by tone; the branch is read wǔ.
				$key     = strtolower( $pinyin );
				$toned   = '' !== $sent ? $sent : ( self::TONES[ $key ] ?? ( 'wu' === $key ? ( self::BRANCHES['horse'] === $char ? 'wǔ' : 'wù' ) : $pinyin ) );
				$pillar .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . ( '' === $char ? '' : '<span class="astroway-chinese__stem" lang="zh">' . esc_html( $char ) . '</span> ' ) . '<span lang="zh-Latn">' . esc_html( $toned ) . '</span></dd></div>';
			}
			$pillar = '<dl class="astroway-chinese__pillar">' . $pillar . '</dl>';
		}

		if ( isset( self::BRANCHES[ $slug ] ) ) {
			$inner .= '<div class="astroway-chinese"' . ( '' === $year_el ? '' : ' data-astroway-element="' . esc_attr( $year_el ) . '"' ) . '>';
			// The facts below name the animal; a screen reader need not guess a Chinese reading of it.
			$inner .= '<span class="astroway-chinese__seal" aria-hidden="true" lang="zh">' . self::BRANCHES[ $slug ] . '</span>';

			$hanzi = is_string( $data['animalHanziTraditional'] ?? null ) && preg_match( '/^\p{Han}$/u', trim( $data['animalHanziTraditional'] ) ) ? trim( $data['animalHanziTraditional'] ) : ( self::ANIMALS[ $slug ][0] ?? '' );
			$sound = is_string( $data['animalPinyin'] ?? null ) && '' !== trim( $data['animalPinyin'] ) ? trim( $data['animalPinyin'] ) : ( self::ANIMALS[ $slug ][1] ?? '' );
			if ( '' !== $hanzi ) {
				$inner .= '<span class="astroway-chinese__name" aria-hidden="true"><span class="astroway-chinese__animal" lang="zh">' . esc_html( $hanzi ) . '</span><span class="astroway-chinese__pinyin" lang="zh-Latn">' . esc_html( $sound ) . '</span></span>';
			}
			$inner .= $pillar . '</div>';
		} else {
			$inner .= $pillar;
		}

		// Capsules rather than a label column: "Year element" wrapped in the narrow column on a phone.
		$tags = [];
		if ( ! empty( $element['fixed'] ) ) {
			/* translators: %s = one of the five Chinese elements */
			$tags[] = sprintf( __( 'Element: %s', 'astroway' ), self::element_label( (string) $element['fixed'] ) );
		}
		if ( ! empty( $element['cycling'] ) ) {
			// The element of the year itself, which turns every two years and is
			// what "Metal Horse" names; the fixed one belongs to the animal.
			/* translators: %s = one of the five Chinese elements */
			$tags[] = sprintf( __( 'Year element: %s', 'astroway' ), self::element_label( (string) $element['cycling'] ) );
		}
		if ( isset( $element['yin'] ) ) {
			/* translators: %s = Yin or Yang */
			$tags[] = sprintf( __( 'Polarity: %s', 'astroway' ), $element['yin'] ? __( 'Yin', 'astroway' ) : __( 'Yang', 'astroway' ) );
		}
		if ( '' === $pillar && ! empty( $data['pillar'] ) ) {
			/* translators: %s = the year's stem and branch, such as Geng-Wu */
			$tags[] = sprintf( __( 'Pillar: %s', 'astroway' ), (string) $data['pillar'] );
		}
		$inner .= UI::tags( array_merge( $tags, self::chinese_relations( $data ) ) );
		if ( $year > 0 ) {
			$from = self::beijing( $data['yearStart']['utc'] ?? null );
			$to   = self::beijing( $data['yearEnd']['utc'] ?? null );
			$line = '' !== $from && '' !== $to
				/* translators: 1: the year, 2: the moment it begins, 3: the moment it ends, both in Beijing time */
				? sprintf( __( 'The %1$d animal year runs from %2$s to %3$s, Beijing time: it begins at Lichun, the start of spring, not on 1 January.', 'astroway' ), $year, $from, $to )
				/* translators: %d = the year */
				: sprintf( __( 'The %d animal year begins at the start of spring (Lichun, about 4 February), not on 1 January.', 'astroway' ), $year );
			$inner .= '<p class="astroway-chinese__boundary">' . esc_html( $line ) . '</p>';
		}
		$inner .= self::chinese_reading( $slug, $year_el, $lang );

		return self::shell( 'chinese_zodiac', $lang, $inner );
	}

	/**
	 * The animal's double hour and the animals around it (api 2.234.1), named
	 * in the plugin's own words, as capsules beside the elements; an answer
	 * from before has none of it.
	 */
	private static function chinese_relations( array $data ): array {
		$tags  = [];
		$hours = is_array( $data['hours'] ?? null ) ? $data['hours'] : [];
		if ( preg_match( '/^\d{2}:\d{2}$/', (string) ( $hours['start'] ?? '' ) ) && preg_match( '/^\d{2}:\d{2}$/', (string) ( $hours['end'] ?? '' ) ) ) {
			/* translators: %s = the animal's double hour, such as 11:00-13:00 */
			$tags[] = sprintf( __( 'Hours: %s', 'astroway' ), $hours['start'] . '-' . $hours['end'] );
		}
		$trine = [];
		foreach ( (array) ( $data['trine']['animals'] ?? [] ) as $one ) {
			if ( is_array( $one ) && '' !== trim( (string) ( $one['animal'] ?? '' ) ) ) {
				$trine[] = self::animal_label( trim( (string) $one['animal'] ) );
			}
		}
		if ( $trine ) {
			/* translators: %s = the three animals of the triad (san he), such as Tiger, Horse, Dog */
			$tags[] = sprintf( __( 'Triad: %s', 'astroway' ), implode( ', ', $trine ) );
		}
		$pairs = [
			/* translators: %s = an animal, the secret friend (liu he) of this one */
			'secretFriend' => __( 'Secret friend: %s', 'astroway' ),
			/* translators: %s = an animal that clashes (liu chong) with this one */
			'clash'        => __( 'Clash: %s', 'astroway' ),
			/* translators: %s = an animal that harms (liu hai) this one */
			'harm'         => __( 'Harm: %s', 'astroway' ),
		];
		foreach ( $pairs as $field => $label ) {
			$animal = is_array( $data[ $field ] ?? null ) ? trim( (string) ( $data[ $field ]['animal'] ?? '' ) ) : '';
			if ( '' !== $animal ) {
				$tags[] = sprintf( $label, self::animal_label( $animal ) );
			}
		}
		return $tags;
	}

	/** A UTC moment from the api as date and time in Beijing, where the calendar is kept; '' if it does not parse. */
	private static function beijing( $iso ): string {
		if ( ! is_string( $iso ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?Z$/', $iso ) ) {
			return '';
		}
		$stamp = strtotime( $iso );
		if ( false === $stamp ) {
			return '';
		}
		$format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );
		return (string) wp_date( (string) $format, $stamp, new \DateTimeZone( '+08:00' ) );
	}

	/**
	 * The api's texts on the animal and on the year's element. Seventeen texts,
	 * not sixty: a Metal Horse is the Horse coloured by Metal, as the tradition
	 * reads it. A text the api has not translated is left out rather than
	 * shown in another language.
	 */
	private static function chinese_reading( string $animal, string $element, string $lang ): string {
		$texts = PublicData::get( 'chinese_texts', [ 'lang' => $lang ] );
		if ( ! is_array( $texts ) || ( empty( $texts['textLocalized'] ) && 'en' !== Plugin::resolve_lang( $lang ) ) ) {
			return '';
		}

		$html = '';
		$a    = is_array( $texts['animals'][ $animal ] ?? null ) ? $texts['animals'][ $animal ] : [];
		$body = '';
		if ( '' !== trim( (string) ( $a['character'] ?? '' ) ) ) {
			$body .= '<p>' . esc_html( trim( (string) $a['character'] ) ) . '</p>';
		}
		// Sentences, not values: a term over its paragraph rather than the facts list.
		$terms = '';
		foreach ( [
			'strengths' => __( 'Strengths', 'astroway' ),
			'caution'   => __( 'Watch for', 'astroway' ),
		] as $key => $label ) {
			if ( '' !== trim( (string) ( $a[ $key ] ?? '' ) ) ) {
				$terms .= '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( trim( (string) $a[ $key ] ) ) . '</dd>';
			}
		}
		if ( '' !== $terms ) {
			$body .= '<dl class="astroway-chinese__reading">' . $terms . '</dl>';
		}
		if ( '' !== $body ) {
			$html .= UI::section( __( 'Character', 'astroway' ), UI::prose( $body ) );
		}

		$e = is_array( $texts['elements'][ $element ] ?? null ) ? $texts['elements'][ $element ] : [];
		if ( '' !== trim( (string) ( $e['text'] ?? '' ) ) ) {
			$html .= UI::section(
				/* translators: %s = one of the five Chinese elements, such as Metal */
				sprintf( __( 'The year element: %s', 'astroway' ), self::element_label( ucfirst( $element ) ) ),
				UI::prose( '<p>' . esc_html( trim( (string) $e['text'] ) ) . '</p>' )
			);
		}
		return $html;
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

		// One table component for every card: the sky cards used to draw their
		// own, which is how they ended up with a different border, a different
		// scroll hint and no stacked layout on a phone.
		return UI::table(
			[
				'head'  => $head,
				'rows'  => $rows,
				'stack' => true,
			]
		);
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
		$slug = str_replace( '_', '-', (string) preg_replace( '/^astroway_/', '', $tag ) );
		return UI::card( 'div', 'generic ' . $slug, $body, [ 'lang' => $lang ] );
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

	/*
	 * ---------------------------------------------------------------------
	 * Charts read from birth data alone: the Vedic chart and the numbers.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * The card for `kundli` or `numerology`, or '' when it cannot be had, so
	 * the caller can fall back to the frame instead of leaving a gap.
	 *
	 * @param array $params Sanitised by the caller; `visitor` puts the call on the site key.
	 */
	public static function birth_card( string $widget, array $params, ?int $now = null ): string {
		$lang = Plugin::resolve_lang( (string) ( $params['lang'] ?? '' ) );
		if ( 'kundli' === $widget ) {
			$data = Birth::kundli( $params, $now );
			return is_array( $data ) ? self::kundli_card( $data, $lang, $params ) : '';
		}
		if ( 'numerology' === $widget ) {
			$data = Birth::numerology( $params, ! empty( $params['visitor'] ), $now );
			return is_array( $data ) ? self::numerology_card( $data, $lang ) : '';
		}
		return '';
	}

	/** Index of the sign a longitude falls in, 0 for Aries. */
	private static function sign_index( float $longitude ): int {
		return (int) floor( fmod( fmod( $longitude, 360 ) + 360, 360 ) / 30 ) % 12;
	}

	private static function kundli_card( array $data, string $lang, array $params ): string {
		$lagna      = is_array( $data['lagna'] ?? null ) && is_numeric( $data['lagna']['longitude'] ?? null ) ? (float) $data['lagna']['longitude'] : null;
		$lagna_sign = null === $lagna ? -1 : self::sign_index( $lagna );

		$rows = [];
		foreach ( (array) ( $data['grahas'] ?? [] ) as $graha ) {
			if ( ! is_array( $graha ) || ! is_string( $graha['name'] ?? null ) || ! is_numeric( $graha['longitude'] ?? null ) ) {
				continue;
			}
			$lon   = (float) $graha['longitude'];
			$name  = self::planet_label( $graha['name'] );
			$nodes = [
				'Rahu' => 'north-node',
				'Ketu' => 'south-node',
			];
			$glyph = $nodes[ $graha['name'] ] ?? self::planet_id( $graha['name'] );
			// Whole-sign houses, counted from the sign of the Lagna, as the tradition counts them.
			$house  = $lagna_sign < 0 ? '' : (string) ( ( self::sign_index( $lon ) - $lagna_sign + 12 ) % 12 + 1 );
			$motion = empty( $graha['isRetrograde'] )
				? esc_html__( 'Direct', 'astroway' )
				: '<span class="astroway-nowrap"><span aria-hidden="true">℞</span> ' . esc_html__( 'Retrograde', 'astroway' ) . '</span>';
			$rows[] = [
				'cells' => [
					[ 'html' => Glyphs::icon( $glyph, $name ) . ' ' . esc_html( $name ) ],
					self::position_label( $lon ),
					$house,
					[ 'html' => $motion ],
				],
			];
		}

		$facts = [];
		if ( null !== $lagna ) {
			$facts[] = [
				'label' => __( 'Lagna', 'astroway' ),
				'glyph' => self::sign_of( $lagna ),
				'value' => self::sign_label( self::sign_of( $lagna ) ),
				'sub'   => self::arc( fmod( fmod( $lagna, 360 ) + 360, 360 ) - 30 * $lagna_sign ),
			];
		}
		$nak    = is_array( $data['nakshatra'] ?? null ) ? $data['nakshatra'] : [];
		$fields = [
			'uk' => 'nameUk',
			'ru' => 'nameRu',
		];
		// nameLocalized since api 2.228.1; the per-language fields stay for answers cached before it.
		$local = $nak['nameLocalized'] ?? null;
		if ( ! is_string( $local ) || '' === trim( $local ) ) {
			$local = $nak[ $fields[ $lang ] ?? 'name' ] ?? null;
		}
		$nak_name = is_string( $local ) && '' !== trim( $local ) ? trim( $local ) : ( is_string( $nak['name'] ?? null ) ? trim( $nak['name'] ) : '' );
		if ( '' !== $nak_name ) {
			$facts[] = [
				'label' => __( 'Moon nakshatra', 'astroway' ),
				'value' => $nak_name,
				/* translators: %d = pada (quarter) of the nakshatra, 1 to 4 */
				'sub'   => is_numeric( $nak['pada'] ?? null ) ? sprintf( __( 'Pada %d', 'astroway' ), (int) $nak['pada'] ) : '',
			];
		}
		$dasha = is_array( $data['dasha'] ?? null ) ? $data['dasha'] : [];
		$maha  = self::dasha_period( $dasha['maha'] ?? null );
		$antar = self::dasha_period( $dasha['antar'] ?? null );
		if ( null !== $maha ) {
			$until   = null === $antar ? $maha['end'] : $antar['end'];
			$facts[] = [
				'label' => __( 'Dasha now', 'astroway' ),
				'value' => null === $antar ? $maha['planet'] : $maha['planet'] . ' · ' . $antar['planet'],
				/* translators: %s = date */
				'sub'   => '' === $until ? '' : sprintf( __( 'until %s', 'astroway' ), $until ),
			];
		}

		$inner  = UI::header(
			[
				'title'     => __( 'Vedic birth chart', 'astroway' ),
				'medal'     => null === $lagna ? '' : self::sign_of( $lagna ),
				'meta_html' => self::date_line( (string) ( $params['date'] ?? '' ) ),
			]
		);
		$inner .= UI::shown( 'facts' ) ? UI::facts( $facts, 'trio' ) : '';
		if ( ! empty( $rows ) ) {
			$inner .= UI::section( __( 'Grahas', 'astroway' ), self::sky_table( [ __( 'Graha', 'astroway' ), __( 'Position', 'astroway' ), __( 'House', 'astroway' ), __( 'Motion', 'astroway' ) ], $rows ), '', 'grahas' );
		}
		if ( null !== $maha ) {
			$periods = [
				[
					/* translators: %s = planet name */
					'label' => sprintf( __( 'Mahadasha of %s', 'astroway' ), $maha['planet'] ),
					'value' => $maha['range'],
				],
			];
			if ( null !== $antar ) {
				$periods[] = [
					/* translators: %s = planet name */
					'label' => sprintf( __( 'Antardasha of %s', 'astroway' ), $antar['planet'] ),
					'value' => $antar['range'],
				];
			}
			$inner .= UI::section( __( 'Vimshottari dasha', 'astroway' ), UI::facts( $periods ), '', 'dasha' );
		}
		$yogas  = self::named_present( $data['yogas']['present'] ?? [], self::yoga_labels() );
		$doshas = self::named_present( $data['doshas']['present'] ?? [], self::dosha_labels() );
		$found  = [
			[
				'label' => __( 'Yogas', 'astroway' ),
				'value' => empty( $yogas ) ? __( 'None found', 'astroway' ) : implode( ', ', $yogas ),
			],
			[
				'label' => __( 'Doshas', 'astroway' ),
				'value' => empty( $doshas ) ? __( 'None found', 'astroway' ) : implode( ', ', $doshas ),
			],
		];
		$inner .= UI::section( __( 'Yogas and doshas', 'astroway' ), UI::facts( $found ), '', 'yogas' );
		if ( is_numeric( $data['ayanamsa'] ?? null ) ) {
			/* translators: %s = the ayanamsa in degrees and minutes */
			$inner .= self::note_line( sprintf( __( 'Sidereal zodiac, ayanamsa %s.', 'astroway' ), self::arc( (float) $data['ayanamsa'] ) ) );
		}

		return self::shell( 'kundli', $lang, $inner );
	}

	/**
	 * A dasha period as the planet and its days.
	 *
	 * @param mixed $period One of the api's `maha` or `antar`.
	 * @return array{planet: string, end: string, range: string}|null
	 */
	private static function dasha_period( $period ): ?array {
		if ( ! is_array( $period ) || ! is_string( $period['planetName'] ?? null ) || '' === $period['planetName'] ) {
			return null;
		}
		$start = self::dasha_day( $period['startDate'] ?? null );
		$end   = self::dasha_day( $period['endDate'] ?? null );
		return [
			'planet' => self::planet_label( $period['planetName'] ),
			'end'    => $end,
			'range'  => '' !== $start && '' !== $end ? $start . ' – ' . $end : $start . $end,
		];
	}

	/**
	 * "2028-09-02 00:46", local time at birth, as a day: the day is what a reader plans by.
	 *
	 * @param mixed $value The api's date string.
	 */
	private static function dasha_day( $value ): string {
		if ( ! is_string( $value ) || ! preg_match( '/^(\d{4}-\d{2}-\d{2})/', $value, $m ) ) {
			return '';
		}
		$stamp = strtotime( $m[1] . ' 00:00:00 UTC' );
		return false === $stamp ? '' : (string) wp_date( (string) get_option( 'date_format', 'Y-m-d' ), $stamp, new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * The names in a `present` list, labelled where the plugin knows them.
	 *
	 * @param mixed $present The api's list.
	 */
	private static function named_present( $present, array $labels ): array {
		$out = [];
		foreach ( is_array( $present ) ? $present : [] as $key ) {
			if ( is_string( $key ) && '' !== trim( $key ) ) {
				$out[] = $labels[ $key ] ?? ucfirst( trim( $key ) );
			}
		}
		return array_values( array_unique( $out ) );
	}

	private static function yoga_labels(): array {
		return [
			'raja'               => __( 'Raja yoga', 'astroway' ),
			'dhana'              => __( 'Dhana yoga', 'astroway' ),
			'dharmaKarmadhipati' => __( 'Dharma Karmadhipati yoga', 'astroway' ),
			'panchaMahapurusha'  => __( 'Pancha Mahapurusha yoga', 'astroway' ),
			'gajakesari'         => __( 'Gajakesari yoga', 'astroway' ),
			'adhi'               => __( 'Adhi yoga', 'astroway' ),
		];
	}

	private static function dosha_labels(): array {
		return [
			'mangal'      => __( 'Mangal dosha', 'astroway' ),
			'kaalSarp'    => __( 'Kaal Sarp dosha', 'astroway' ),
			'pitru'       => __( 'Pitru dosha', 'astroway' ),
			'shrapit'     => __( 'Shrapit dosha', 'astroway' ),
			'grahan'      => __( 'Grahan dosha', 'astroway' ),
			'guruChandal' => __( 'Guru Chandal dosha', 'astroway' ),
		];
	}

	private static function numerology_card( array $data, string $lang ): string {
		$labels  = [
			'lifePath'     => __( 'Life Path', 'astroway' ),
			'expression'   => __( 'Expression', 'astroway' ),
			'soulUrge'     => __( 'Soul Urge', 'astroway' ),
			'personality'  => __( 'Personality', 'astroway' ),
			'birthday'     => __( 'Birthday', 'astroway' ),
			'personalYear' => __( 'Personal Year', 'astroway' ),
		];
		$numbers = [];
		$reads   = [];
		foreach ( $labels as $key => $label ) {
			$n = $data[ $key ] ?? null;
			if ( ! is_array( $n ) || ! is_numeric( $n['reduced'] ?? null ) ) {
				continue;
			}
			$reduced = (int) $n['reduced'];
			$steps   = array_values( array_filter( is_array( $n['steps'] ?? null ) ? $n['steps'] : [], 'is_numeric' ) );
			$sub     = count( $steps ) > 1 ? implode( ' → ', array_map( 'intval', $steps ) ) : '';
			if ( ! empty( $n['isMaster'] ) ) {
				$sub = trim( $sub . ' · ' . __( 'Master number', 'astroway' ), ' ·' );
			}
			$numbers[] = [
				'label' => $label,
				'value' => (string) $reduced,
				'sub'   => $sub,
			];
			$m         = self::number_meaning( $n, $lang );
			$read      = null === $m ? null : self::number_read( $m, $label, $reduced );
			if ( null !== $read ) {
				$read['open'] = empty( $reads );
				$reads[]      = $read;
			}
		}
		if ( empty( $numbers ) ) {
			return '';
		}

		$name   = is_string( $data['name'] ?? null ) ? trim( $data['name'] ) : '';
		$inner  = UI::header(
			[
				'title'     => '' === $name ? __( 'Numerology', 'astroway' ) : $name,
				'meta_html' => self::date_line( is_string( $data['date'] ?? null ) ? $data['date'] : '' ),
			]
		);
		$inner .= UI::shown( 'numbers' ) ? implode( '', array_map( static fn( $row ) => UI::facts( $row, 'trio' ), array_chunk( $numbers, 3 ) ) ) : '';
		if ( ! empty( $reads ) ) {
			$inner .= UI::section( __( 'What the numbers say', 'astroway' ), UI::accordion( $reads ), '', 'meaning' );
		}
		return self::shell( 'numerology', $lang, $inner );
	}

	/**
	 * A number's meaning in the page's language: meaningLocalized once the api
	 * has translated it (api 2.228.1), the English on an English page. An
	 * English paragraph on another language's page is worse than none.
	 */
	private static function number_meaning( array $n, string $lang ): ?array {
		$local = is_array( $n['meaningLocalized'] ?? null ) ? $n['meaningLocalized'] : null;
		if ( null !== $local && ! empty( $local['translated'] ) && ( $local['language'] ?? '' ) === $lang ) {
			return $local;
		}
		return 'en' === $lang && is_array( $n['meaning'] ?? null ) ? $n['meaning'] : null;
	}

	/** First letter raised, in any script: ucfirst() works on bytes and leaves Cyrillic as it is. */
	private static function first_up( string $text ): string {
		$text = trim( $text );
		return '' === $text ? '' : mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 );
	}

	/** One number's reading as an accordion row, or null when the api sent nothing to read. */
	private static function number_read( array $m, string $label, int $reduced ): ?array {
		$body = is_string( $m['shortRead'] ?? null ) && '' !== trim( $m['shortRead'] ) ? UI::prose( '<p>' . esc_html( self::first_up( $m['shortRead'] ) ) . '</p>' ) : '';
		// Pills outside the prose, which would draw them as a bulleted list.
		$body .= UI::tags( array_filter( is_array( $m['strengths'] ?? null ) ? $m['strengths'] : [], 'is_string' ) );
		$more  = [];
		foreach (
			[
				'challenges' => __( 'Challenges', 'astroway' ),
				'career'     => __( 'Work', 'astroway' ),
			] as $field => $title
		) {
			$list = array_filter( is_array( $m[ $field ] ?? null ) ? $m[ $field ] : [], 'is_string' );
			if ( ! empty( $list ) ) {
				$more[] = [
					'label' => $title,
					'value' => implode( ', ', $list ),
				];
			}
		}
		if ( is_string( $m['relationship'] ?? null ) && '' !== trim( $m['relationship'] ) ) {
			$more[] = [
				'label' => __( 'Love', 'astroway' ),
				'value' => self::first_up( $m['relationship'] ),
			];
		}
		$body .= UI::facts( $more );
		if ( '' === $body ) {
			return null;
		}
		$archetype = is_string( $m['archetype'] ?? null ) ? trim( $m['archetype'] ) : '';
		return [
			'title'     => $label . ' ' . $reduced . ( '' === $archetype ? '' : ': ' . $archetype ),
			'body_html' => $body,
		];
	}
}
