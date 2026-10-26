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

	/** The picture sets the public routes take, with the names an editor picks from. */
	private const ARTS = [
		'rws-1909'     => 'Rider-Waite-Smith 1909',
		'rws-restored' => 'RWS restored',
		'rws-vector'   => 'RWS vector',
	];

	/** Only our own CDN goes into an img src on someone else's page. */
	private const IMAGE_HOST = 'cdn.astroway.info';

	/**
	 * The Rider-Waite-Smith picture sets the api lists, falling back to the
	 * three known ones. Only the admin asks for the list, where the editors build
	 * their fields; a page reads what is kept, so a render never waits on it.
	 *
	 * @return array<string, string> Set id => name.
	 */
	public static function arts(): array {
		$data = is_admin() ? PublicData::get( 'tarot_decks' ) : PublicData::cached( 'tarot_decks' );
		$live = [];
		foreach ( (array) ( $data['items'] ?? [] ) as $deck ) {
			if ( ! is_array( $deck ) || 'rider-waite' !== ( $deck['deck'] ?? '' ) ) {
				continue;
			}
			foreach ( (array) ( $deck['art'] ?? [] ) as $art ) {
				$id = is_array( $art ) ? (string) ( $art['id'] ?? '' ) : '';
				if ( preg_match( '/^[a-z0-9][a-z0-9-]{1,40}$/', $id ) ) {
					$live[ $id ] = trim( (string) ( $art['name'] ?? $id ) );
				}
			}
		}
		return isset( $live['rws-1909'] ) ? $live : self::ARTS;
	}

	/**
	 * A picture set the api will accept: '' for the drawn face, an unknown one
	 * as the default, since the route answers INVALID_ART and blanks the card.
	 */
	public static function sanitize_art( $value ): string {
		$value = strtolower( trim( (string) $value ) );
		// "none" is what an editor's select stores; a builder drops an empty value.
		if ( '' === $value || 'none' === $value ) {
			return '';
		}
		return isset( self::arts()[ $value ] ) ? $value : 'rws-1909';
	}

	/**
	 * One card's face: its picture when there is one and pictures are asked
	 * for, otherwise the drawn face.
	 *
	 * @param array $drawn    A `drawn[]` item: `card` and `reversed`.
	 * @param bool  $pictures Whether the shortcode asked for a picture set.
	 */
	public static function face( array $drawn, bool $pictures = false ): string {
		$card = (array) ( $drawn['card'] ?? [] );
		if ( $pictures ) {
			$img = self::image( (array) ( $card['image'] ?? [] ), 'astroway-tarot__face astroway-tarot__face--image' . ( empty( $drawn['reversed'] ) ? '' : ' astroway-tarot__face--reversed' ) );
			if ( '' !== $img ) {
				return $img;
			}
		}
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

	/**
	 * The back every card shows while it lies face down: the set's own, or a
	 * drawn one when the set has none.
	 *
	 * @param array $back The spread's `back`, or an empty array.
	 */
	public static function back( array $back ): string {
		$img = self::image( $back, 'astroway-tarot__back astroway-tarot__back--image' );
		if ( '' !== $img ) {
			return $img;
		}
		return '<svg class="astroway-tarot__back" viewBox="0 0 100 170" aria-hidden="true" focusable="false">'
			. '<rect class="astroway-tarot__edge" x="1" y="1" width="98" height="168" rx="7"/>'
			. '<rect class="astroway-tarot__frame" x="7" y="7" width="86" height="156" rx="3"/>'
			. '<g class="astroway-tarot__emblem">' . self::EMBLEMS['major'] . '</g></svg>';
	}

	/** An img for a picture from our CDN, or '' for anything else. */
	private static function image( array $image, string $classes ): string {
		$url = (string) ( $image['url'] ?? '' );
		if ( ! self::ours( $url ) ) {
			return '';
		}
		$width  = (int) ( $image['width'] ?? 0 );
		$height = (int) ( $image['height'] ?? 0 );
		// A card is never drawn wider than 200px: by width a 1x screen takes the
		// 300px file and a 3x the 600px one, where by density a phone fetched 1200px.
		$sources = [];
		if ( $width > 0 ) {
			$small = (string) ( $image['urlSmall'] ?? '' );
			$url2x = (string) ( $image['url2x'] ?? '' );
			if ( self::ours( $small ) ) {
				$sources[] = esc_url( $small ) . ' ' . intdiv( $width, 2 ) . 'w';
			}
			$sources[] = esc_url( $url ) . ' ' . $width . 'w';
			if ( self::ours( $url2x ) ) {
				$sources[] = esc_url( $url2x ) . ' ' . ( 2 * $width ) . 'w';
			}
		}
		$srcset = count( $sources ) > 1 ? implode( ', ', $sources ) : '';
		return sprintf(
			'<img class="%1$s" src="%2$s"%3$s%4$s loading="lazy" decoding="async" alt="">',
			esc_attr( $classes ),
			esc_url( $url ),
			'' === $srcset ? '' : ' srcset="' . esc_attr( $srcset ) . '" sizes="200px"',
			$width > 0 && $height > 0 ? ' width="' . $width . '" height="' . $height . '"' : ''
		);
	}

	private static function ours( string $url ): bool {
		return 0 === strpos( $url, 'https://' ) && self::IMAGE_HOST === strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
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

	/**
	 * Arcana, suit and the side the card came out on, as tags.
	 *
	 * @param array $drawn     A `drawn[]` item.
	 * @param array $localized The api's `localized` block.
	 */
	public static function kind_tags( array $drawn, array $localized ): array {
		$card = (array) ( $drawn['card'] ?? [] );
		$tags = [ 'major' === ( $card['category'] ?? '' ) ? __( 'Major arcana', 'astroway' ) : __( 'Minor arcana', 'astroway' ) ];
		$suit = self::suit( $card );
		if ( 'major' !== $suit ) {
			$loc    = (array) ( $localized['cards'][ (string) ( $card['slug'] ?? '' ) ] ?? [] );
			$suits  = [
				'wands'     => __( 'Wands', 'astroway' ),
				'cups'      => __( 'Cups', 'astroway' ),
				'swords'    => __( 'Swords', 'astroway' ),
				'pentacles' => __( 'Pentacles', 'astroway' ),
			];
			$tags[] = '' !== trim( (string) ( $loc['suit'] ?? '' ) ) ? (string) $loc['suit'] : $suits[ $suit ];
		}
		$tags[] = empty( $drawn['reversed'] ) ? __( 'Upright', 'astroway' ) : __( 'Reversed', 'astroway' );
		return $tags;
	}

	/**
	 * Element, astrology, number and the yes-or-no answer, where the api sent them.
	 *
	 * @param array $card    The drawn card.
	 * @param bool  $english Whether the page is in English: an astrology value
	 *                       the plugin cannot translate is shown only there.
	 * @return array Items for UI::facts().
	 */
	public static function facts( array $card, bool $english ): array {
		$items    = [];
		$elements = [
			'fire'  => __( 'Fire', 'astroway' ),
			'water' => __( 'Water', 'astroway' ),
			'air'   => __( 'Air', 'astroway' ),
			'earth' => __( 'Earth', 'astroway' ),
		];
		$element  = strtolower( trim( (string) ( $card['element'] ?? '' ) ) );
		if ( isset( $elements[ $element ] ) ) {
			$items[] = [
				'label' => __( 'Element', 'astroway' ),
				'value' => $elements[ $element ],
			];
		}
		$astrology = self::astrology( trim( (string) ( $card['astrology'] ?? '' ) ), $english );
		if ( '' !== $astrology ) {
			$items[] = [
				'label' => __( 'Astrology', 'astroway' ),
				'value' => $astrology,
			];
		}
		if ( isset( $card['numerology'] ) && is_numeric( $card['numerology'] ) ) {
			$items[] = [
				'label' => __( 'Numerology', 'astroway' ),
				'value' => (string) (int) $card['numerology'],
			];
		}
		$answers = [
			'yes'   => __( 'Yes', 'astroway' ),
			'no'    => __( 'No', 'astroway' ),
			'maybe' => __( 'Maybe', 'astroway' ),
		];
		$yes     = strtolower( trim( (string) ( $card['yes'] ?? '' ) ) );
		if ( isset( $answers[ $yes ] ) ) {
			$items[] = [
				'label' => __( 'Yes or no', 'astroway' ),
				'value' => $answers[ $yes ],
			];
		}
		return $items;
	}

	/** "Mercury in Virgo", or one sign or planet, in the page's language. */
	private static function astrology( string $raw, bool $english ): string {
		if ( '' === $raw ) {
			return '';
		}
		$signs = array_map( 'strtolower', [ 'Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces' ] );
		if ( preg_match( '/^([A-Za-z]+) in ([A-Za-z]+)$/', $raw, $m ) && in_array( strtolower( $m[2] ), $signs, true ) ) {
			/* translators: 1: planet, 2: zodiac sign, as in "Mercury in Virgo" */
			return sprintf( __( '%1$s in %2$s', 'astroway' ), Render::planet_label( ucfirst( strtolower( $m[1] ) ) ), Render::sign_label( strtolower( $m[2] ) ) );
		}
		if ( in_array( strtolower( $raw ), $signs, true ) ) {
			return Render::sign_label( strtolower( $raw ) );
		}
		$word   = ucfirst( strtolower( $raw ) );
		$planet = Render::planet_label( $word );
		// A label equal to the input means the plugin had no translation for it.
		$translated = $word !== $planet;
		return $translated || $english ? $planet : '';
	}

	/**
	 * What the card would have said the other way up: its keywords and meaning,
	 * read from the same answer.
	 *
	 * @return array{title: string, keywords: string[], meaning: string}
	 */
	public static function other_side( array $drawn, array $localized, bool $english ): array {
		$flipped             = $drawn;
		$flipped['reversed'] = empty( $drawn['reversed'] );
		$read                = self::read( $flipped, $localized, $english );
		return [
			'title'    => $flipped['reversed'] ? __( 'If it came out reversed', 'astroway' ) : __( 'If it came out upright', 'astroway' ),
			'keywords' => $read['keywords'],
			'meaning'  => $read['meaning'],
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
	 *
	 * Face down (`reveal`), the card is a button holding the back and the face,
	 * and what it says waits under its position until it is turned. All of it
	 * is printed: without the script the spread reads face up, as before.
	 *
	 * @param array $opts `pictures` (bool), `reveal` (bool), `back` (markup of the back), `index` (1-based place in the spread).
	 */
	public static function item( array $drawn, array $localized, bool $english, int $number = 0, array $opts = [] ): string {
		$read = self::read( $drawn, $localized, $english );
		if ( '' === $read['name'] ) {
			return '';
		}
		$face   = self::face( $drawn, ! empty( $opts['pictures'] ) );
		$reveal = ! empty( $opts['reveal'] );
		if ( $reveal ) {
			$face = sprintf(
				'<button type="button" class="astroway-tarot__flip" aria-pressed="false" aria-label="%1$s"><span class="astroway-tarot__side astroway-tarot__side--back">%2$s</span><span class="astroway-tarot__side astroway-tarot__side--face">%3$s</span></button>',
				esc_attr__( 'Turn the card over', 'astroway' ),
				(string) ( $opts['back'] ?? self::back( [] ) ),
				$face
			);
		}
		// The script matches a list card to its slot in the cross by this number.
		$index = $reveal ? (int) ( $opts['index'] ?? $number ) : 0;
		$html  = '<li class="astroway-spread__card"' . ( $index > 0 ? ' data-astroway-card="' . $index . '"' : '' ) . '>';
		$html .= $face;
		// The row of pictures names each place under its card; the text below repeats it for readers.
		if ( ! empty( $opts['pictures'] ) && '' !== $read['position'] ) {
			$html .= '<span class="astroway-tarot__label" aria-hidden="true">' . esc_html( $read['position'] ) . '</span>';
		}
		$html .= '<div class="astroway-spread__text">';
		if ( '' !== $read['position'] ) {
			$html .= '<p class="astroway-spread__pos">' . ( $number > 0 ? '<span class="astroway-spread__num" aria-hidden="true"><span>' . $number . '</span></span> ' : '' ) . '<span class="astroway-spread__where-name">' . esc_html( $read['position'] ) . '</span></p>';
		}
		$html .= $reveal ? '<div class="astroway-spread__said">' : '';
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
		// Inside the part a closed card hides, so it cannot name the card before it is turned.
		$about = UI::shown( 'about' ) ? self::about( $drawn, (array) ( $opts['texts'] ?? [] ) ) : '';
		if ( '' !== $about ) {
			$html .= UI::accordion(
				[
					[
						'title'     => __( 'About the card', 'astroway' ),
						'body_html' => $about,
					],
				]
			);
		}
		$html .= $reveal ? '</div>' : '';
		return $html . '</div></li>';
	}

	/**
	 * The api's texts on these cards in this language, keyed by slug, or []
	 * when it has none to give in it: an English text on another language's
	 * page is worse than none. Each card is kept for a week on its own, so a
	 * page reads a few kilobytes rather than the whole deck.
	 *
	 * @param string[] $slugs The cards on the page.
	 */
	public static function texts( string $lang, array $slugs ): array {
		$lang  = Plugin::resolve_lang( $lang );
		$slugs = array_values( array_unique( array_filter( array_map( 'strval', $slugs ), 'strlen' ) ) );
		$base  = 'tarot_text_' . $lang . '_';
		if ( ! $slugs || false !== Cache::get( $base . 'none' ) || false !== Cache::get( $base . 'neg' ) ) {
			return [];
		}
		$texts = [];
		$need  = [];
		foreach ( $slugs as $slug ) {
			$kept = Cache::get( $base . md5( $slug ) );
			if ( is_array( $kept ) ) {
				$texts[ $slug ] = $kept;
			} else {
				$need[] = $slug;
			}
		}
		if ( ! $need ) {
			return $texts;
		}
		$data = PublicData::get(
			'tarot_texts',
			[
				'lang'  => $lang,
				'cards' => implode( ',', $need ),
			],
			false
		);
		if ( null === $data ) {
			Cache::set( $base . 'neg', 'fail', PublicData::NEGATIVE_TTL );
			return $texts;
		}
		if ( empty( $data['textLocalized'] ) && 'en' !== $lang ) {
			// Not translated yet: asked again tomorrow, not on every render.
			Cache::set( $base . 'none', 1, DAY_IN_SECONDS );
			return [];
		}
		foreach ( $need as $slug ) {
			$card = $data['cards'][ $slug ] ?? null;
			if ( is_array( $card ) ) {
				Cache::set( $base . md5( $slug ), $card, PublicData::ttl_for( 'week' ) );
				$texts[ $slug ] = $card;
			}
		}
		return $texts;
	}

	/** What the picture shows, then the card in five spheres on the side it came out; '' without a text. */
	public static function about( array $drawn, array $texts ): string {
		$t = $texts[ (string) ( $drawn['card']['slug'] ?? '' ) ] ?? null;
		if ( ! is_array( $t ) ) {
			return '';
		}
		$html = '';
		foreach ( preg_split( '/\n\s*\n/', trim( (string) ( $t['description'] ?? '' ) ) ) as $para ) {
			$html .= '' === trim( $para ) ? '' : '<p>' . esc_html( trim( $para ) ) . '</p>';
		}
		$side   = empty( $drawn['reversed'] ) ? 'upright' : 'reversed';
		$areas  = is_array( $t[ $side ] ?? null ) ? $t[ $side ] : [];
		$labels = [
			'love'    => __( 'Love', 'astroway' ),
			'career'  => __( 'Career', 'astroway' ),
			'finance' => __( 'Money', 'astroway' ),
			'health'  => __( 'Health', 'astroway' ),
			'spirit'  => __( 'Spirit', 'astroway' ),
		];
		foreach ( $labels as $key => $label ) {
			$text = trim( (string) ( $areas[ $key ] ?? '' ) );
			if ( '' !== $text ) {
				$html .= '<p><strong>' . esc_html( $label ) . '.</strong> ' . esc_html( $text ) . '</p>';
			}
		}
		return '' === $html ? '' : UI::prose( $html );
	}

	/**
	 * The Celtic Cross laid out: the cross of six with the second card across
	 * the first, and the staff of four beside it, bottom to top. Numbers only;
	 * the list under it says what each card is.
	 *
	 * @param array $drawn The ten `drawn[]` items in their order.
	 * @param array $opts  As for item(): face down, each slot shows the back until its card is turned.
	 */
	public static function cross( array $drawn, array $opts = [] ): string {
		$html = '';
		foreach ( array_values( $drawn ) as $i => $one ) {
			if ( $i > 9 || ! is_array( $one ) ) {
				break;
			}
			$face = self::face( $one, ! empty( $opts['pictures'] ) );
			if ( ! empty( $opts['reveal'] ) ) {
				$face = '<span class="astroway-tarot__side astroway-tarot__side--back">' . (string) ( $opts['back'] ?? self::back( [] ) ) . '</span><span class="astroway-tarot__side astroway-tarot__side--face">' . $face . '</span>';
			}
			$html .= '<div class="astroway-cross__slot astroway-cross__slot--' . ( $i + 1 ) . '"' . ( empty( $opts['reveal'] ) ? '' : ' data-astroway-card="' . ( $i + 1 ) . '"' ) . '>' . $face . '<span class="astroway-cross__num"><span>' . ( $i + 1 ) . '</span></span></div>';
		}
		return '<div class="astroway-cross" aria-hidden="true">' . $html . '</div>';
	}
}
