<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tarot cards and spreads, drawn here from the api's draw.
 *
 * A face is not the Rider-Waite picture, which the api does not send and a
 * plugin should not carry 78 of: it is the card's number and its suit's
 * emblem, in the suit's colour, turned upside down when the card came out
 * reversed. The name and the reading stand beside it as text.
 *
 * The api reads the card twice, upright and reversed, and translates both
 * into `localized`. Prose the api marks as untranslated is left out on a
 * page in another language rather than shown in English as if it were a
 * translation (api contract docs/WP-TAROT-CHINESE-CONTRACT.md); the card's
 * name, an identifier as much as a word, falls back to English.
 *
 * @since 2.3.0
 */
class Tarot {

	private const ROMAN = [ '0', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX', 'XXI' ];

	/** Suit emblems in a 100 x 170 face, centred on 50, 85. */
	private const EMBLEMS = [
		'wands'     => '<path d="M40 112L60 58"/><path d="M47 93q-9-2-11-10q9 1 11 10zM54 75q9 1 12-6q-9-1-12 6z"/>',
		'cups'      => '<path d="M34 62h32q0 24-16 27v14h9v5H41v-5h9V89q-16-3-16-27z"/>',
		'swords'    => '<path d="M50 54l4 8v36h-8V62z"/><path d="M38 98h24M50 98v10"/><circle cx="50" cy="111" r="3"/>',
		'pentacles' => '<circle cx="50" cy="85" r="19"/><path d="M50 69l9.4 29-24.7-17.9h30.6L40.6 98z"/>',
		'major'     => '<path d="M50 60l5 18 18-5-13 12 13 12-18-5-5 18-5-18-18 5 13-12-13-12 18 5z"/>',
	];

	/**
	 * One card's face.
	 *
	 * @param array $drawn A `drawn[]` item: `card` and `reversed`.
	 */
	public static function face( array $drawn ): string {
		$card  = (array) ( $drawn['card'] ?? [] );
		$suit  = self::suit( $card );
		$index = self::index( $card );
		$court = 'court' === ( $card['category'] ?? '' );

		$svg  = '<svg class="astroway-tarot__face" viewBox="0 0 100 170" aria-hidden="true" focusable="false" data-suit="' . esc_attr( $suit ) . '">';
		$svg .= '<rect class="astroway-tarot__edge" x="1" y="1" width="98" height="168" rx="7"/>';
		$svg .= '<g' . ( empty( $drawn['reversed'] ) ? '' : ' transform="rotate(180 50 85)"' ) . '>';
		$svg .= '<rect class="astroway-tarot__frame" x="7" y="7" width="86" height="156" rx="3"/>';
		if ( '' !== $index ) {
			$svg .= '<text class="astroway-tarot__index" x="50" y="30">' . esc_html( $index ) . '</text>';
		}
		if ( $court ) {
			$svg .= '<path class="astroway-tarot__crown" d="M38 136l4-10 8 6 8-6 4 10z"/>';
		}
		$svg .= '<g class="astroway-tarot__emblem">' . self::EMBLEMS[ $suit ] . '</g>';
		$svg .= '</g></svg>';
		return $svg;
	}

	/** major, wands, cups, swords or pentacles. */
	private static function suit( array $card ): string {
		$suit = strtolower( (string) ( $card['suit'] ?? '' ) );
		return isset( self::EMBLEMS[ $suit ] ) && 'major' !== $suit ? $suit : 'major';
	}

	/** Roman for the major arcana, the pip's number for the minor, nothing for a court card. */
	private static function index( array $card ): string {
		$number = (int) ( $card['number'] ?? -1 );
		switch ( $card['category'] ?? '' ) {
			case 'major':
				return self::ROMAN[ $number ] ?? '';
			case 'minor':
				return $number >= 1 && $number <= 10 ? (string) $number : '';
		}
		return '';
	}

	/**
	 * What to print for one drawn card, in the language asked for when the api
	 * has it.
	 *
	 * @return array{name: string, keywords: string[], meaning: string, reversed: bool, position: string, position_meaning: string}
	 */
	public static function read( array $drawn, array $localized, bool $english ): array {
		$card     = (array) ( $drawn['card'] ?? [] );
		$reversed = ! empty( $drawn['reversed'] );
		$side     = $reversed ? 'reversed' : 'upright';
		$loc      = (array) ( $localized['cards'][ (string) ( $card['slug'] ?? '' ) ] ?? [] );

		$name = ! empty( $loc['nameLocalized'] ) && '' !== trim( (string) ( $loc['name'] ?? '' ) ) ? (string) $loc['name'] : (string) ( $card['name'] ?? '' );
		// Which reading applies is how the card came out: the wrong branch reverses the meaning.
		$reading = [];
		if ( ! empty( $loc['textLocalized'] ) && is_array( $loc[ $side ] ?? null ) ) {
			$reading = $loc[ $side ];
		} elseif ( $english ) {
			$reading = (array) ( $card[ $side ] ?? [] );
		}

		$pos   = $english ? (array) ( $drawn['position'] ?? [] ) : [];
		$index = (int) ( $drawn['position']['index'] ?? -1 );
		foreach ( (array) ( $localized['positions'] ?? [] ) as $p ) {
			if ( ! empty( $localized['textLocalized'] ) && is_array( $p ) && (int) ( $p['index'] ?? -2 ) === $index ) {
				$pos = $p;
				break;
			}
		}

		return [
			'name'             => trim( $name ),
			'keywords'         => array_values( array_filter( array_map( 'strval', (array) ( $reading['keywords'] ?? [] ) ), 'strlen' ) ),
			'meaning'          => trim( (string) ( $reading['meaning'] ?? '' ) ),
			'reversed'         => $reversed,
			'position'         => trim( (string) ( $pos['name'] ?? '' ) ),
			'position_meaning' => trim( (string) ( $pos['meaning'] ?? '' ) ),
		];
	}

	/** The card's name, with "reversed" after it when it came out that way. */
	public static function title( array $read ): string {
		return $read['reversed']
			/* translators: %s = tarot card name */
			? sprintf( __( '%s (reversed)', 'astroway' ), $read['name'] )
			: $read['name'];
	}

	/**
	 * One card of a spread with its position, as a list item: the face, then
	 * where it lies and what it says there.
	 */
	public static function item( array $drawn, array $localized, bool $english, int $number = 0 ): string {
		$read = self::read( $drawn, $localized, $english );
		if ( '' === $read['name'] ) {
			return '';
		}
		$html  = '<li class="astroway-spread__card">';
		$html .= self::face( $drawn );
		$html .= '<div class="astroway-spread__text">';
		if ( '' !== $read['position'] ) {
			$html .= '<p class="astroway-spread__pos">' . ( $number > 0 ? '<span class="astroway-spread__num" aria-hidden="true"><span>' . $number . '</span></span> ' : '' ) . '<span class="astroway-spread__where-name">' . esc_html( $read['position'] ) . '</span></p>';
		}
		$html .= '<h4 class="astroway-spread__name">' . esc_html( self::title( $read ) ) . '</h4>';
		if ( ! empty( $read['keywords'] ) ) {
			$html .= '<p class="astroway-card__keywords">' . esc_html( implode( ', ', $read['keywords'] ) ) . '</p>';
		}
		if ( '' !== $read['meaning'] ) {
			$html .= '<p class="astroway-spread__meaning">' . esc_html( $read['meaning'] ) . '</p>';
		}
		if ( '' !== $read['position_meaning'] ) {
			$html .= '<p class="astroway-spread__where">' . esc_html( $read['position_meaning'] ) . '</p>';
		}
		return $html . '</div></li>';
	}

	/**
	 * The Celtic Cross laid out: the cross of six with the second card across
	 * the first, and the staff of four beside it, bottom to top. Numbers only;
	 * the list under it says what each card is.
	 *
	 * @param array $drawn The ten `drawn[]` items in their order.
	 */
	public static function cross( array $drawn ): string {
		$html = '';
		foreach ( array_values( $drawn ) as $i => $one ) {
			if ( $i > 9 || ! is_array( $one ) ) {
				break;
			}
			$html .= '<div class="astroway-cross__slot astroway-cross__slot--' . ( $i + 1 ) . '">' . self::face( $one ) . '<span class="astroway-cross__num"><span>' . ( $i + 1 ) . '</span></span></div>';
		}
		return '<div class="astroway-cross" aria-hidden="true">' . $html . '</div>';
	}
}
