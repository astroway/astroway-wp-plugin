<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The 2.0 component library: pure functions that return escaped markup.
 *
 * Every card is built from these, so a card looks like its neighbours and a
 * fix lands everywhere at once. Plain strings are escaped here; a parameter
 * whose name ends in `_html` is markup the caller built, usually from another
 * function in this class, and is printed as it is.
 *
 * Nothing here needs JavaScript to be read. Tabs show every panel under its
 * own heading until the script runs, filters only appear with it, and the
 * sign picker and the date arrows are links.
 *
 * @since 2.0.0
 */
class UI {

	public const LOOKS = [ 'instrument', 'night', 'native' ];

	private static int $uid = 0;

	/** @var string[] Looks asked for by the shortcodes and blocks being rendered, innermost last. */
	private static array $looks = [];

	/** Id unique to this request, for tabs and the elements they point at. */
	public static function uid(): string {
		return 'astroway-u' . ( ++self::$uid );
	}

	/** Test hook. */
	public static function reset(): void {
		self::$uid   = 0;
		self::$looks = [];
		Glyphs::reset();
	}

	/**
	 * The look to render: the one asked for, else the nearest shortcode or
	 * block around this card that asked for one, else the site's default,
	 * else Instrument. Anything unknown counts as not asked.
	 */
	public static function look( string $requested = '' ): string {
		if ( in_array( $requested, self::LOOKS, true ) ) {
			return $requested;
		}
		foreach ( array_reverse( self::$looks ) as $around ) {
			if ( in_array( $around, self::LOOKS, true ) ) {
				return $around;
			}
		}
		if ( class_exists( __NAMESPACE__ . '\\Admin' ) && function_exists( 'get_option' ) ) {
			$site = (string) ( ( (array) get_option( Admin::OPTION_KEY, [] ) )['look'] ?? '' );
			if ( in_array( $site, self::LOOKS, true ) ) {
				return $site;
			}
		}
		return 'instrument';
	}

	/** A look for everything rendered until the matching pop_look(). */
	public static function push_look( string $look ): void {
		self::$looks[] = sanitize_key( $look );
	}

	public static function pop_look(): void {
		array_pop( self::$looks );
	}

	/**
	 * `look="night"` on any of our shortcodes, the generated ones included,
	 * without each handler knowing about it. Runs last, so a shortcode another
	 * filter has already answered is not pushed and never needs popping.
	 *
	 * @param false|string $short_circuit Output from an earlier filter, or false.
	 */
	public static function shortcode_start( $short_circuit, $tag, $attr ) {
		if ( false === $short_circuit && is_string( $tag ) && 0 === strpos( $tag, 'astroway_' ) ) {
			self::push_look( is_array( $attr ) ? (string) ( $attr['look'] ?? '' ) : '' );
		}
		return $short_circuit;
	}

	/** @param string $output */
	public static function shortcode_end( $output, $tag ) {
		if ( is_string( $tag ) && 0 === strpos( $tag, 'astroway_' ) ) {
			self::pop_look();
		}
		return $output;
	}

	/**
	 * The card root. Prints the glyph symbols the card used at its end.
	 *
	 * @param string $tag      Custom element name, `astroway-*-card`.
	 * @param string $modifier Card kind, for `astroway-card--{modifier}`; several separated by spaces.
	 * @param string $inner_html Card content.
	 * @param array  $opts     `lang`, `look`.
	 */
	public static function card( string $tag, string $modifier, string $inner_html, array $opts = [] ): string {
		if ( class_exists( __NAMESPACE__ . '\\Plugin' ) ) {
			Plugin::use_styles();
		}
		$tag       = preg_match( '/^(astroway-[a-z0-9-]+|div)$/', $tag ) ? $tag : 'astroway-card';
		$look      = self::look( (string) ( $opts['look'] ?? '' ) );
		$modifiers = array_filter( array_map( 'sanitize_key', preg_split( '/\s+/', trim( $modifier ) ) ) );
		$class     = 'astroway-card';
		foreach ( $modifiers as $name ) {
			$class .= ' astroway-card--' . $name;
		}
		$attrs = [ 'class' => $class ];
		if ( 'instrument' !== $look ) {
			$attrs['data-astroway-look'] = $look;
		}
		if ( '' !== (string) ( $opts['lang'] ?? '' ) ) {
			$attrs['lang'] = (string) $opts['lang'];
		}
		return '<' . $tag . self::attrs( $attrs ) . '>' . $inner_html . Glyphs::defs() . '</' . $tag . '>';
	}

	/**
	 * Medallion, title, meta line and actions.
	 *
	 * @param array $a `title`, `level` (2-4, default 3), `medal` (glyph id),
	 *                 `meta_html`, `actions_html`.
	 */
	public static function header( array $a ): string {
		$level = (int) ( $a['level'] ?? 3 );
		$level = $level >= 2 && $level <= 4 ? $level : 3;
		$html  = '<header class="astroway-card__header">';
		if ( '' !== (string) ( $a['medal'] ?? '' ) ) {
			$html .= self::medal( (string) $a['medal'] );
		}
		$html .= '<div class="astroway-card__heading">';
		$html .= sprintf( '<h%1$d class="astroway-card__title">%2$s</h%1$d>', $level, esc_html( (string) ( $a['title'] ?? '' ) ) );
		if ( '' !== (string) ( $a['meta_html'] ?? '' ) ) {
			$html .= '<p class="astroway-card__meta">' . $a['meta_html'] . '</p>';
		}
		$html .= '</div>';
		if ( '' !== (string) ( $a['actions_html'] ?? '' ) ) {
			$html .= '<div class="astroway-card__actions">' . $a['actions_html'] . '</div>';
		}
		return $html . '</header>';
	}

	/** A glyph in an engraved ring with twelve ticks, like a clock face. */
	public static function medal( string $glyph ): string {
		$ticks = '';
		for ( $i = 0; $i < 12; $i++ ) {
			$a      = deg2rad( $i * 30 );
			$ticks .= sprintf(
				'<line class="astroway-medal__tick" x1="%s" y1="%s" x2="%s" y2="%s"/>',
				self::n( 25 + 21 * cos( $a ) ),
				self::n( 25 + 21 * sin( $a ) ),
				self::n( 25 + 23.5 * cos( $a ) ),
				self::n( 25 + 23.5 * sin( $a ) )
			);
		}
		$ref = Glyphs::ref( $glyph );
		$use = '' === $ref ? '' : '<use class="astroway-medal__glyph" href="' . esc_attr( $ref ) . '" x="8.88" y="8.88" width="32.24" height="32.24"></use>';
		return '<span class="astroway-medal" aria-hidden="true"><svg viewBox="0 0 50 50" focusable="false">'
			. '<circle class="astroway-medal__ring" cx="25" cy="25" r="24"/>'
			. '<circle class="astroway-medal__ring2" cx="25" cy="25" r="19.5"/>'
			. $ticks . $use . '</svg></span>';
	}

	/**
	 * Label and value pairs. `trio` sets three side by side, the big three of
	 * a chart; `list` is rows with the value at the end.
	 *
	 * @param array  $items   Each `label`, `value`, optional `glyph`, `sub`.
	 * @param string $variant `list` or `trio`.
	 */
	public static function facts( array $items, string $variant = 'list' ): string {
		$variant = 'trio' === $variant ? 'trio' : 'list';
		$html    = '';
		foreach ( $items as $item ) {
			$value  = '' === (string) ( $item['glyph'] ?? '' ) ? '' : Glyphs::icon( (string) $item['glyph'] ) . ' ';
			$value .= esc_html( (string) ( $item['value'] ?? '' ) );
			if ( '' !== (string) ( $item['sub'] ?? '' ) ) {
				$value .= '<span class="astroway-facts__sub">' . esc_html( (string) $item['sub'] ) . '</span>';
			}
			$html .= '<div class="astroway-facts__item"><dt>' . esc_html( (string) ( $item['label'] ?? '' ) ) . '</dt><dd>' . $value . '</dd></div>';
		}
		return '' === $html ? '' : '<dl class="astroway-facts astroway-facts--' . $variant . '">' . $html . '</dl>';
	}

	/**
	 * Tabs inside a card. Without JavaScript every panel shows under its own
	 * heading, which is also what a crawler reads.
	 *
	 * @param string $label  Accessible name of the tab list.
	 * @param array  $panels Each `label`, `html`, optional `count`.
	 */
	public static function tabs( string $label, array $panels, int $selected = 0 ): string {
		$panels = array_values( array_filter( $panels, static fn( $p ) => '' !== (string) ( $p['html'] ?? '' ) ) );
		if ( empty( $panels ) ) {
			return '';
		}
		// One panel is not a set of tabs: a strip with a single button does nothing.
		if ( 1 === count( $panels ) ) {
			return (string) $panels[0]['html'];
		}
		$selected = isset( $panels[ $selected ] ) ? $selected : 0;
		$id       = self::uid();
		$flag     = self::use_script();
		$buttons  = '';
		$bodies   = '';
		foreach ( $panels as $i => $panel ) {
			$on       = $i === $selected;
			$name     = esc_html( (string) ( $panel['label'] ?? '' ) );
			$count    = isset( $panel['count'] ) ? '<span class="astroway-tabs__count">' . esc_html( (string) $panel['count'] ) . '</span>' : '';
			$buttons .= sprintf(
				'<button type="button" class="astroway-tabs__tab" role="tab" id="%1$s-t%2$d" aria-controls="%1$s-p%2$d" aria-selected="%3$s" tabindex="%4$d">%5$s%6$s</button>',
				$id,
				$i,
				$on ? 'true' : 'false',
				$on ? 0 : -1,
				$name,
				$count
			);
			$bodies  .= sprintf(
				'<section class="astroway-tabs__panel%1$s" role="tabpanel" id="%2$s-p%3$d" aria-labelledby="%2$s-t%3$d" tabindex="0"><h4 class="astroway-tabs__heading">%4$s</h4>%5$s</section>',
				$on ? ' is-selected' : '',
				$id,
				$i,
				$name,
				$panel['html']
			);
		}
		return $flag . '<div class="astroway-tabs"><div class="astroway-tabs__list" role="tablist" aria-label="' . esc_attr( $label ) . '">' . $buttons . '</div>' . $bodies . '</div>';
	}

	/** A titled part of a card, set off by a rule. */
	public static function section( string $title, string $inner_html, string $intro = '' ): string {
		$html  = '<section class="astroway-card__section"><h4 class="astroway-card__subtitle">' . esc_html( $title ) . '</h4>';
		$html .= '' === $intro ? '' : '<p class="astroway-card__intro">' . esc_html( $intro ) . '</p>';
		return $html . $inner_html . '</section>';
	}

	/**
	 * Disclosure rows, one open at a time.
	 *
	 * @param array $items Each `title`, `body_html`, optional `icon_html`,
	 *                     `meta_html`, `open`.
	 */
	public static function accordion( array $items ): string {
		$group = self::uid();
		$html  = '';
		foreach ( $items as $item ) {
			$summary  = '' === (string) ( $item['icon_html'] ?? '' ) ? '' : '<span class="astroway-acc__icon">' . $item['icon_html'] . '</span>';
			$summary .= '<span class="astroway-acc__title">' . esc_html( (string) ( $item['title'] ?? '' ) ) . '</span>';
			$summary .= '' === (string) ( $item['meta_html'] ?? '' ) ? '' : '<span class="astroway-acc__meta">' . $item['meta_html'] . '</span>';
			$summary .= '<svg class="astroway-acc__chevron" viewBox="0 0 14 14" aria-hidden="true" focusable="false"><path d="M3 5l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			$html    .= sprintf(
				'<details class="astroway-acc__item" name="%1$s"%2$s><summary class="astroway-acc__summary">%3$s</summary><div class="astroway-acc__body">%4$s</div></details>',
				$group,
				empty( $item['open'] ) ? '' : ' open',
				$summary,
				(string) ( $item['body_html'] ?? '' )
			);
		}
		return '' === $html ? '' : '<div class="astroway-acc">' . $html . '</div>';
	}

	/**
	 * Aspect rows: mark, words, orb. `kind` is h (harmonious), t (tense) or
	 * n (neither), and the filter chips read it.
	 *
	 * @param array  $rows       Each `kind`, `glyph`, `text`, optional `orb`.
	 * @param string $empty_text Shown by the filter when it hides every row.
	 */
	public static function aspects( array $rows, string $empty_text = '' ): string {
		$html = '';
		foreach ( $rows as $row ) {
			$kind  = self::kind( (string) ( $row['kind'] ?? '' ) );
			$html .= sprintf(
				'<li data-astroway-kind="%1$s">%2$s<span>%3$s</span><span class="astroway-aspects__orb">%4$s</span></li>',
				$kind,
				self::mark( (string) ( $row['glyph'] ?? '' ), $kind ),
				esc_html( (string) ( $row['text'] ?? '' ) ),
				esc_html( (string) ( $row['orb'] ?? '' ) )
			);
		}
		if ( '' === $html ) {
			return '';
		}
		$empty = '' === $empty_text ? '' : '<p class="astroway-card__intro" data-astroway-empty hidden>' . esc_html( $empty_text ) . '</p>';
		return '<ul class="astroway-aspects">' . $html . '</ul>' . $empty;
	}

	/** Aspect glyph in a circle (harmony) or a square (tension). */
	public static function mark( string $glyph, string $kind ): string {
		return '<span class="astroway-mark astroway-mark--' . self::kind( $kind ) . '" aria-hidden="true">' . Glyphs::icon( $glyph ) . '</span>';
	}

	/**
	 * Filter chips over the rows of another element. JavaScript only, so
	 * hidden until the script runs.
	 *
	 * @param string $label  Accessible name of the group.
	 * @param string $target Id of the element whose `[data-astroway-kind]` rows it filters.
	 * @param array  $chips  Each `value` (all, h, t, n), `label`, optional `count`.
	 */
	public static function chips( string $label, string $target, array $chips ): string {
		$flag = self::use_script();
		$html = '';
		foreach ( array_values( $chips ) as $i => $chip ) {
			$value = (string) ( $chip['value'] ?? 'all' );
			$line  = in_array( $value, [ 'h', 't' ], true ) ? self::line( $value ) : '';
			$count = isset( $chip['count'] ) ? ' <span class="astroway-chip__count">' . esc_html( (string) $chip['count'] ) . '</span>' : '';
			$html .= sprintf(
				'<button type="button" class="astroway-chip" data-astroway-value="%1$s" aria-pressed="%2$s">%3$s%4$s%5$s</button>',
				esc_attr( $value ),
				0 === $i ? 'true' : 'false',
				$line,
				esc_html( (string) ( $chip['label'] ?? '' ) ),
				$count
			);
		}
		return $flag . '<div class="astroway-chips" role="group" aria-label="' . esc_attr( $label ) . '" data-astroway-filter="' . esc_attr( $target ) . '">' . $html . '</div>';
	}

	/**
	 * What the lines and marks mean.
	 *
	 * @param array $items Each `label` and either `line` (h or t) or `glyph`.
	 */
	public static function legend( array $items ): string {
		$html = '';
		foreach ( $items as $item ) {
			$sign = '';
			if ( in_array( $item['line'] ?? '', [ 'h', 't' ], true ) ) {
				$sign = self::line( $item['line'] );
			} elseif ( '' !== (string) ( $item['glyph'] ?? '' ) ) {
				$sign = Glyphs::icon( (string) $item['glyph'] );
			}
			$html .= '<li>' . $sign . esc_html( (string) ( $item['label'] ?? '' ) ) . '</li>';
		}
		return '' === $html ? '' : '<ul class="astroway-legend">' . $html . '</ul>';
	}

	/** A short sample of the harmonious or the tense aspect line. */
	public static function line( string $kind ): string {
		return '<svg class="astroway-line" viewBox="0 0 26 8" aria-hidden="true" focusable="false"><line class="astroway-line--' . ( 't' === $kind ? 't' : 'h' ) . '" x1="1" y1="4" x2="25" y2="4"/></svg>';
	}

	/** The retrograde mark after a planet's name; the label is what a screen reader says. */
	public static function rx( string $label ): string {
		return '<span class="astroway-rx">' . Glyphs::icon( 'retrograde' ) . '<span class="astroway-sr">' . esc_html( $label ) . '</span></span>';
	}

	/** Small inline status word: "today", "retrograde". */
	public static function badge( string $text, string $tone = '' ): string {
		$tone = in_array( $tone, [ 'tens', 'muted' ], true ) ? ' astroway-badge--' . $tone : '';
		return '<span class="astroway-badge' . $tone . '">' . esc_html( $text ) . '</span>';
	}

	/**
	 * A data table in a horizontal scroller.
	 *
	 * @param array $a `caption`, `head` (column names), `rows`, `stack` (turn
	 *                 rows into cards on a narrow column), `numeric` (indexes
	 *                 of columns that hold figures, set flush end), `row_header`
	 *                 (default true: the first cell names the row). A row is a
	 *                 list of cells or `['cells' => [...], 'current' => bool,
	 *                 'angle' => bool]`; a cell is a string or `['html' => ...]`.
	 */
	public static function table( array $a ): string {
		$head       = array_values( (array) ( $a['head'] ?? [] ) );
		$stack      = ! empty( $a['stack'] );
		$row_header = $a['row_header'] ?? true;
		$numeric    = array_map( 'intval', (array) ( $a['numeric'] ?? [] ) );
		$html       = '<table class="astroway-table' . ( $stack ? ' astroway-table--stack' : '' ) . '">';
		if ( '' !== (string) ( $a['caption'] ?? '' ) ) {
			$html .= '<caption>' . esc_html( (string) $a['caption'] ) . '</caption>';
		}
		if ( ! empty( $head ) ) {
			$html .= '<thead><tr>';
			foreach ( $head as $i => $name ) {
				$html .= '<th scope="col"' . ( in_array( $i, $numeric, true ) ? ' class="astroway-num"' : '' ) . '>' . esc_html( (string) $name ) . '</th>';
			}
			$html .= '</tr></thead>';
		}
		$html .= '<tbody>';
		foreach ( (array) ( $a['rows'] ?? [] ) as $row ) {
			$cells = isset( $row['cells'] ) ? (array) $row['cells'] : (array) $row;
			$class = [];
			if ( ! empty( $row['current'] ) ) {
				$class[] = 'is-current';
			}
			if ( ! empty( $row['angle'] ) ) {
				$class[] = 'is-angle';
			}
			// `aria-current` as well as the class: "the row you are in" is a fact
			// about the table, not a colour, and the sky cards said it out loud
			// before they shared this component.
			$now   = empty( $row['current'] ) ? '' : ' aria-current="true"';
			$html .= empty( $class ) ? '<tr>' : '<tr class="' . implode( ' ', $class ) . '"' . $now . '>';
			foreach ( array_values( $cells ) as $i => $cell ) {
				$content = is_array( $cell ) ? (string) ( $cell['html'] ?? '' ) : esc_html( (string) $cell );
				if ( 0 === $i && $row_header ) {
					$html .= '<th scope="row">' . $content . '</th>';
					continue;
				}
				$label = $stack && isset( $head[ $i ] ) ? ' data-label="' . esc_attr( (string) $head[ $i ] ) . '"' : '';
				$num   = in_array( $i, $numeric, true ) ? ' class="astroway-num"' : '';
				$html .= '<td' . $num . $label . '>' . $content . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		return self::use_script() . '<div class="astroway-scroll">' . $html . '</div>';
	}

	/** Two letters of a name, for a cell that has no glyph to show. */
	private static function short( string $name ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 2 ) : substr( $name, 0, 2 );
	}

	/**
	 * Heat grid: elements by modality, planets in the cells.
	 *
	 * @param array $a `caption`, `cols`, `total_label`, `rows`: each `label`,
	 *                 `total`, `dominant`, `cells`: each `glyphs` (ids),
	 *                 `names` (what the glyphs say), `dominant`.
	 */
	public static function matrix( array $a ): string {
		$html = '<table class="astroway-matrix">';
		if ( '' !== (string) ( $a['caption'] ?? '' ) ) {
			$html .= '<caption class="astroway-sr">' . esc_html( (string) $a['caption'] ) . '</caption>';
		}
		$html .= '<thead><tr><td></td>';
		foreach ( (array) ( $a['cols'] ?? [] ) as $col ) {
			$html .= '<th scope="col">' . esc_html( (string) $col ) . '</th>';
		}
		$html .= '<th scope="col">' . esc_html( (string) ( $a['total_label'] ?? '' ) ) . '</th></tr></thead><tbody>';
		foreach ( (array) ( $a['rows'] ?? [] ) as $row ) {
			$dominant = ! empty( $row['dominant'] ) ? ' class="is-dominant"' : '';
			// The total rides along in the row header too: a narrow column hides
			// the totals column, and "Earth leads" should not point at nothing.
			$html .= '<tr><th scope="row"' . $dominant . '>' . esc_html( (string) ( $row['label'] ?? '' ) ) . ' <span class="astroway-matrix__n">' . esc_html( (string) ( $row['total'] ?? '' ) ) . '</span></th>';
			foreach ( (array) ( $row['cells'] ?? [] ) as $cell ) {
				$glyphs = '';
				foreach ( (array) ( $cell['glyphs'] ?? [] ) as $glyph ) {
					$glyphs .= Glyphs::icon( (string) $glyph );
				}
				foreach ( (array) ( $cell['text'] ?? [] ) as $word ) {
					$glyphs .= '<span class="astroway-matrix__word">' . esc_html( self::short( (string) $word ) ) . '</span>';
				}
				$names   = implode( ', ', array_map( 'strval', (array) ( $cell['names'] ?? [] ) ) );
				$glyphs .= '' === $names ? '' : '<span class="astroway-sr">' . esc_html( $names ) . '</span>';
				$html   .= '<td' . ( empty( $cell['dominant'] ) ? '' : ' class="is-dominant"' ) . '>' . $glyphs . '</td>';
			}
			$html .= '<td class="astroway-matrix__total' . ( empty( $row['dominant'] ) ? '' : ' is-dominant' ) . '">' . esc_html( (string) ( $row['total'] ?? '' ) ) . '</td></tr>';
		}
		// Never squeezed below a readable width: on a narrow column it scrolls.
		return self::use_script() . '<div class="astroway-scroll">' . $html . '</tbody></table></div>';
	}

	/**
	 * The aspectarian: a staircase of planets with the aspect between each
	 * pair where they meet. Each filled cell also says in words what it
	 * shows, so the grid is the wheel's aspects for a screen reader too.
	 *
	 * @param array  $planets Ordered: each `id` (glyph id) and `name`.
	 * @param array  $aspects Each `a`, `b` (planet ids), `glyph` (aspect glyph id),
	 *                        `kind` (h, t, n), `text` (the pair in words), optional `orb`.
	 * @param string $caption What the grid is.
	 */
	public static function aspect_grid( array $planets, array $aspects, string $caption ): string {
		$planets = array_values( $planets );
		$index   = [];
		foreach ( $planets as $i => $p ) {
			$index[ (string) ( $p['id'] ?? '' ) ] = $i;
		}
		$cells = [];
		foreach ( $aspects as $a ) {
			$i = $index[ (string) ( $a['a'] ?? '' ) ] ?? null;
			$j = $index[ (string) ( $a['b'] ?? '' ) ] ?? null;
			if ( null === $i || null === $j || $i === $j ) {
				continue;
			}
			$cells[ max( $i, $j ) ][ min( $i, $j ) ] = $a;
		}
		if ( empty( $cells ) ) {
			return '';
		}

		$html = '<table class="astroway-grid"><caption class="astroway-sr">' . esc_html( $caption ) . '</caption><tbody>';
		foreach ( $planets as $row => $p ) {
			if ( 0 === $row ) {
				continue;
			}
			$html .= '<tr><th scope="row">' . Glyphs::icon( (string) $p['id'] ) . '<span class="astroway-sr">' . esc_html( (string) ( $p['name'] ?? '' ) ) . '</span></th>';
			for ( $col = 0; $col < $row; $col++ ) {
				$a = $cells[ $row ][ $col ] ?? null;
				if ( null === $a ) {
					$html .= '<td></td>';
					continue;
				}
				$kind  = self::kind( (string) ( $a['kind'] ?? '' ) );
				$orb   = (string) ( $a['orb'] ?? '' );
				$words = (string) ( $a['text'] ?? '' ) . ( '' === $orb ? '' : ', ' . $orb );
				$html .= '<td data-astroway-kind="' . $kind . '" title="' . esc_attr( $words ) . '">' . self::mark( (string) ( $a['glyph'] ?? '' ), $kind ) . '<span class="astroway-sr">' . esc_html( $words ) . '</span></td>';
			}
			$html .= '</tr>';
		}
		$html .= '<tr><td></td>';
		$last  = count( $planets ) - 1;
		for ( $col = 0; $col < $last; $col++ ) {
			$html .= '<th scope="col">' . Glyphs::icon( (string) $planets[ $col ]['id'] ) . '<span class="astroway-sr">' . esc_html( (string) ( $planets[ $col ]['name'] ?? '' ) ) . '</span></th>';
		}
		$html .= '</tr></tbody></table>';
		return self::use_script() . '<div class="astroway-scroll">' . $html . '</div>';
	}

	/** One figure, what it means, and the same figure as a bar. */
	public static function score( int $value, string $label, string $unit = '%', int $max = 100 ): string {
		$max   = max( 1, $max );
		$value = max( 0, min( $max, $value ) );
		$unit  = '' === $unit ? '' : '<span>' . esc_html( $unit ) . '</span>';
		return '<div class="astroway-score"><p class="astroway-score__value">' . esc_html( (string) $value ) . $unit . '</p>'
			. '<p class="astroway-score__label">' . esc_html( $label ) . '</p>'
			. self::meter( $value / $max ) . '</div>';
	}

	/** The bar is the figure drawn again, so it is hidden from assistive tech. */
	public static function meter( float $ratio ): string {
		$pct = self::n( max( 0, min( 1, $ratio ) ) * 100 );
		return '<span class="astroway-meter" aria-hidden="true"><span style="inline-size:' . $pct . '%"></span></span>';
	}

	/** A figure inside a ring that fills to it. */
	public static function ring( int $value, int $max = 100, string $label = '' ): string {
		$max   = max( 1, $max );
		$value = max( 0, min( $max, $value ) );
		$pct   = self::n( $value / $max * 100 );
		$name  = '' === $label ? '' : '<span class="astroway-sr">' . esc_html( $label ) . '</span>';
		return '<div class="astroway-ring"><svg viewBox="0 0 100 100" aria-hidden="true" focusable="false">'
			. '<circle class="astroway-ring__track" cx="50" cy="50" r="45"/>'
			. '<circle class="astroway-ring__bar" cx="50" cy="50" r="45" pathLength="100" stroke-dasharray="' . $pct . ' 100"/></svg>'
			. '<p class="astroway-ring__value">' . esc_html( (string) $value ) . $name . '</p></div>';
	}

	/**
	 * Labelled bars: compatibility by sphere.
	 *
	 * @param array $rows Each `label`, `value`, `max`, optional `text` for the end column.
	 */
	public static function bars( array $rows ): string {
		$html = '';
		foreach ( $rows as $row ) {
			$max   = max( 1, (float) ( $row['max'] ?? 100 ) );
			$value = (float) ( $row['value'] ?? 0 );
			$text  = (string) ( $row['text'] ?? self::n( $value ) );
			$html .= '<li><span>' . esc_html( (string) ( $row['label'] ?? '' ) ) . '</span>' . self::meter( $value / $max ) . '<span class="astroway-bars__value">' . esc_html( $text ) . '</span></li>';
		}
		return '' === $html ? '' : '<ul class="astroway-bars">' . $html . '</ul>';
	}

	/**
	 * Dates on a rail. The past is quieter, today is filled.
	 *
	 * @param array $items Each `day` (the big number), `sub` (month or weekday),
	 *                     optional `date` (Y-m-d for the time element),
	 *                     `title_html`, `text`, `state` (past, now), `badge`.
	 */
	public static function timeline( array $items ): string {
		$html = '';
		foreach ( $items as $item ) {
			$state = in_array( $item['state'] ?? '', [ 'past', 'now' ], true ) ? ' is-' . $item['state'] : '';
			$date  = '<b>' . esc_html( (string) ( $item['day'] ?? '' ) ) . '</b>' . esc_html( (string) ( $item['sub'] ?? '' ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $item['date'] ?? '' ) ) ) {
				$date = '<time datetime="' . esc_attr( $item['date'] ) . '">' . $date . '</time>';
			}
			$title = (string) ( $item['title_html'] ?? '' );
			if ( '' !== (string) ( $item['badge'] ?? '' ) ) {
				$title .= self::badge( (string) $item['badge'] );
			}
			$body  = '' === $title ? '' : '<p class="astroway-timeline__title">' . $title . '</p>';
			$body .= '' === (string) ( $item['text'] ?? '' ) ? '' : '<p>' . esc_html( (string) $item['text'] ) . '</p>';
			$html .= '<li class="astroway-timeline__item' . $state . '"><div class="astroway-timeline__date">' . $date . '</div><div class="astroway-timeline__body">' . $body . '</div></li>';
		}
		return '' === $html ? '' : '<ol class="astroway-timeline">' . $html . '</ol>';
	}

	/**
	 * The sky today in one strip.
	 *
	 * @param array $items Each `text` and optional `icon_html`.
	 */
	public static function sky( array $items ): string {
		$html = '';
		foreach ( $items as $item ) {
			$icon  = (string) ( $item['icon_html'] ?? '' );
			$icon  = '' === $icon ? '' : '<span class="astroway-sky__icon">' . $icon . '</span>';
			$html .= '<li>' . $icon . '<span>' . esc_html( (string) ( $item['text'] ?? '' ) ) . '</span></li>';
		}
		return '' === $html ? '' : '<ul class="astroway-sky">' . $html . '</ul>';
	}

	/**
	 * The Moon as lit: a half disc and an elliptical terminator.
	 *
	 * @param float $fraction Illuminated fraction, 0 to 1.
	 * @param bool  $waxing   Lit on the right, as seen from the northern hemisphere.
	 */
	public static function moon( float $fraction, bool $waxing = true ): string {
		$k  = max( 0, min( 1, $fraction ) );
		$r  = 11;
		$rx = self::n( abs( 2 * $k - 1 ) * $r );
		// The outer arc takes the lit limb, the terminator closes it: bulging
		// out past the centre when more than half is lit, back in when less.
		$outer = $waxing ? 1 : 0;
		$inner = ( $k > 0.5 ) === $waxing ? 1 : 0;
		return '<svg class="astroway-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
			. '<circle class="astroway-moon__dark" cx="12" cy="12" r="11"/>'
			. sprintf( '<path class="astroway-moon__lit" d="M12,1 A11,11 0 0 %1$d 12,23 A%2$s,11 0 0 %3$d 12,1 Z"/>', $outer, $rx, $inner )
			. '</svg>';
	}

	/**
	 * The twelve signs as links, so choosing one works without JavaScript.
	 *
	 * @param array $a `label` (nav name), `current` (sign id), `links` (sign
	 *                 id => URL), `names` (sign id => name).
	 */
	public static function signs( array $a ): string {
		$html = '';
		foreach ( (array) ( $a['links'] ?? [] ) as $sign => $url ) {
			$name    = (string) ( $a['names'][ $sign ] ?? $sign );
			$current = ( $a['current'] ?? '' ) === $sign ? ' aria-current="page"' : '';
			$html   .= sprintf(
				'<a class="astroway-signs__sign" href="%1$s" title="%2$s"%3$s>%4$s</a>',
				esc_url( (string) $url ),
				esc_attr( $name ),
				$current,
				Glyphs::icon( (string) $sign, $name )
			);
		}
		return '' === $html ? '' : '<nav class="astroway-signs" aria-label="' . esc_attr( (string) ( $a['label'] ?? '' ) ) . '">' . $html . '</nav>';
	}

	/**
	 * Previous and next arrows. A missing URL draws a disabled arrow, so the
	 * pair keeps its place.
	 */
	public static function datenav( string $label, string $prev_url, string $prev_label, string $next_url, string $next_label ): string {
		$arrow = static function ( string $url, string $name, string $path ): string {
			$svg = '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="' . $path . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			if ( '' === $url ) {
				return '<span class="astroway-iconbtn is-disabled" aria-disabled="true" role="link" aria-label="' . esc_attr( $name ) . '">' . $svg . '</span>';
			}
			return '<a class="astroway-iconbtn" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $name ) . '">' . $svg . '</a>';
		};
		return '<nav class="astroway-card__actions" aria-label="' . esc_attr( $label ) . '">'
			. $arrow( $prev_url, $prev_label, 'M10 3L5 8l5 5' )
			. $arrow( $next_url, $next_label, 'M6 3l5 5-5 5' )
			. '</nav>';
	}

	/** Running text with a measure, lists with diamond bullets. */
	public static function prose( string $inner_html ): string {
		return '<div class="astroway-prose">' . $inner_html . '</div>';
	}

	/**
	 * A highlighted remark. Tone `tens` for a warning. The icon says which it
	 * is, so the Theme colours look, which has no colours, still tells them apart.
	 */
	public static function callout( string $inner_html, string $tone = '' ): string {
		$warn = 'tens' === $tone;
		return '<div class="astroway-callout' . ( $warn ? ' astroway-callout--tens' : '' ) . '">' . self::icon( $warn ? 'warn' : 'info' ) . '<div>' . $inner_html . '</div></div>';
	}

	/** The two message icons, drawn in the current colour. */
	private static function icon( string $kind ): string {
		$path = 'warn' === $kind
			? '<path d="M8 1.8 15 14H1z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M8 6v3.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="11.9" r="0.95" fill="currentColor"/>'
			: '<circle cx="8" cy="8" r="6.6" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M8 7.2v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="8" cy="4.9" r="0.95" fill="currentColor"/>';
		return '<svg class="astroway-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}

	/** A glyph and the word it stands for, kept on one line. */
	public static function named( string $glyph, string $name ): string {
		$icon = Glyphs::icon( $glyph );
		return '<span class="astroway-named">' . ( '' === $icon ? '' : $icon . ' ' ) . esc_html( $name ) . '</span>';
	}

	/**
	 * Empty or failed: what happened and what to do, never an apology.
	 *
	 * @param string $kind `empty` or `error`.
	 */
	public static function state( string $kind, string $title, string $text = '', string $action_url = '', string $action_label = '' ): string {
		$error = 'error' === $kind;
		$html  = '<div class="astroway-state' . ( $error ? ' astroway-state--error' : '' ) . '"' . ( $error ? ' role="status"' : '' ) . '>';
		$html .= '<p class="astroway-state__title">' . ( $error ? self::icon( 'warn' ) : '' ) . esc_html( $title ) . '</p>';
		$html .= '' === $text ? '' : '<p>' . esc_html( $text ) . '</p>';
		if ( '' !== $action_url && '' !== $action_label ) {
			$html .= '<a href="' . esc_url( $action_url ) . '">' . esc_html( $action_label ) . '</a>';
		}
		return $html . '</div>';
	}

	/** Placeholder lines while a fragment loads. */
	public static function skeleton( int $lines = 3 ): string {
		$widths = [ '92%', '100%', '78%', '96%', '64%' ];
		$html   = '';
		$count  = max( 1, min( 5, $lines ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$html .= '<span class="astroway-skeleton__line" style="--astroway-w:' . $widths[ $i ] . '"></span>';
		}
		return '<div class="astroway-skeleton" aria-hidden="true">' . $html . '</div>';
	}

	/** Small print at the foot of a card. */
	public static function note( string $text ): string {
		$text = trim( $text );
		return '' === $text ? '' : '<footer class="astroway-card__note"><small>' . esc_html( $text ) . '</small></footer>';
	}

	/**
	 * Asks for the script and, when the page is already past its head, marks
	 * the document as scripted right here, before the component paints: a
	 * classic theme prints content after wp_head, and the script then lands in
	 * the footer, after a stacked set of panels has been drawn.
	 */
	private static function use_script(): string {
		if ( ! class_exists( __NAMESPACE__ . '\\Plugin' ) ) {
			return '';
		}
		return Plugin::use_ui_script();
	}

	private static function kind( string $kind ): string {
		return in_array( $kind, [ 'h', 't' ], true ) ? $kind : 'n';
	}

	/** Attribute string from a map, escaped. */
	private static function attrs( array $attrs ): string {
		$html = '';
		foreach ( $attrs as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}
		return $html;
	}

	/** Number for SVG and CSS: two decimals at most, no trailing zeros, a dot. */
	private static function n( float $value ): string {
		$text = rtrim( rtrim( sprintf( '%.2F', $value ), '0' ), '.' );
		return '-0' === $text ? '0' : $text;
	}
}
