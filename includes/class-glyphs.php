<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Astrological glyphs as SVG, never as Unicode: iOS and macOS draw the zodiac
 * as emoji, and a phone showing purple squares where Leo should be is the
 * first thing a visitor notices.
 *
 * A glyph is a `<use>` of a symbol, and every card carries the symbols it used
 * at its end, under ids unique to that card. Sharing one set per page would be
 * smaller, but a page is rendered more than once when an SEO plugin builds its
 * description from the content, and the copy that reaches the reader would then
 * point at symbols printed into a string that was thrown away.
 *
 * @since 2.0.0
 */
class Glyphs {

	/** @var array<string, array{v: string, d: string}>|null */
	private static ?array $data = null;

	/** Counts cards, so each card's symbol ids are its own. */
	private static int $scope = 1;

	/** @var array<string, true> Glyphs used since the last defs(). */
	private static array $used = [];

	/** @return array<string, array{v: string, d: string}> */
	private static function data(): array {
		if ( null === self::$data ) {
			$data       = require __DIR__ . '/ui/glyph-data.php';
			self::$data = is_array( $data ) ? $data : [];
		}
		return self::$data;
	}

	public static function exists( string $id ): bool {
		return isset( self::data()[ $id ] );
	}

	/** The glyph's viewBox, for a caller drawing it inside its own SVG. */
	public static function viewbox( string $id ): string {
		return self::data()[ $id ]['v'] ?? '0 0 0 0';
	}

	/**
	 * Fragment id for a `<use>`, recorded so the card prints the symbol.
	 * Empty for an unknown glyph.
	 */
	public static function ref( string $id ): string {
		if ( ! self::exists( $id ) ) {
			return '';
		}
		self::$used[ $id ] = true;
		return '#astroway-g' . self::$scope . '-' . $id;
	}

	/**
	 * Inline glyph. Decorative by default, since a glyph almost always sits
	 * next to the word it stands for; pass a label when it stands alone.
	 */
	public static function icon( string $id, string $label = '' ): string {
		$ref = self::ref( $id );
		if ( '' === $ref ) {
			return '' === $label ? '' : esc_html( $label );
		}
		$a11y = '' === $label
			? 'aria-hidden="true"'
			: 'role="img" aria-label="' . esc_attr( $label ) . '"';
		// A <use> without x and y places the symbol at 0,0, so the outer box
		// starts there too; the symbol's own viewBox maps the glyph into it.
		$box = explode( ' ', self::viewbox( $id ) );
		return sprintf(
			'<svg class="astroway-glyph" viewBox="0 0 %1$s %2$s" %3$s focusable="false"><use href="%4$s"></use></svg>',
			esc_attr( $box[2] ?? '0' ),
			esc_attr( $box[3] ?? '0' ),
			$a11y,
			esc_attr( $ref )
		);
	}

	/**
	 * The symbols used since the last call, then a fresh scope for the next
	 * card. Empty when the card used none.
	 */
	public static function defs(): string {
		if ( empty( self::$used ) ) {
			return '';
		}
		$data    = self::data();
		$symbols = '';
		foreach ( array_keys( self::$used ) as $id ) {
			$symbols .= sprintf(
				'<symbol id="astroway-g%1$d-%2$s" viewBox="%3$s"><path d="%4$s"/></symbol>',
				self::$scope,
				esc_attr( $id ),
				esc_attr( $data[ $id ]['v'] ),
				esc_attr( $data[ $id ]['d'] )
			);
		}
		self::$used = [];
		++self::$scope;
		return '<svg class="astroway-defs" aria-hidden="true" focusable="false"><defs>' . $symbols . '</defs></svg>';
	}

	/** Test hook: forget what was used and start the ids over. */
	public static function reset(): void {
		self::$used  = [];
		self::$scope = 1;
	}
}
