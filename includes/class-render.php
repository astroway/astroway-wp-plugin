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
	];

	/**
	 * Server-rendered widget, or the iframe when the data is unavailable.
	 *
	 * @param string $widget Widget key, as used by PublicData and RendererDecisions.
	 * @param array  $params Shortcode params, already sanitised by the caller.
	 */
	public static function widget( string $widget, array $params = [] ): string {
		if ( 'iframe' === self::mode() || ! PublicData::supports( $widget ) || ! isset( self::CARDS[ $widget ] ) ) {
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
		return self::admin_note(
			sprintf(
				/* translators: %s = widget name */
				__( 'The %s widget could not be loaded just now. Only administrators see this note.', 'astroway' ),
				$widget
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
		}
		return '';
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
		$index   = (int) floor( $lon / 30 );
		$in_sign = $lon - ( $index * 30 );
		$degrees = (int) floor( $in_sign );
		$minutes = (int) round( ( $in_sign - $degrees ) * 60 );
		if ( 60 === $minutes ) {
			$minutes = 0;
			++$degrees;
		}
		$names = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
		return sprintf( '%s %d°%02d\'', self::sign_label( $names[ $index ] ?? '' ), $degrees, $minutes );
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
}
