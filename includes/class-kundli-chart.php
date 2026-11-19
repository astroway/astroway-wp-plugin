<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The kundli as Indian astrologers draw it, from the chart the card already has:
 * North Indian (houses fixed, the lagna at the top, signs numbered in them) or
 * South Indian (signs fixed, the lagna marked). Drawn here rather than taken as
 * the api's SVG, so it is in the theme's colours and the plugin's glyphs, and it
 * costs nothing beyond the kundli itself.
 */
class KundliChart {

	public const SIZE = 400;

	private const PAD = 10;

	/** North Indian: where each house's contents centre, on a 400 square with 10 of margin. */
	private const NORTH = [
		1  => [ 200, 105, 'diamond' ],
		2  => [ 105, 50, 'tri' ],
		3  => [ 50, 105, 'tri' ],
		4  => [ 105, 200, 'diamond' ],
		5  => [ 50, 295, 'tri' ],
		6  => [ 105, 350, 'tri' ],
		7  => [ 200, 295, 'diamond' ],
		8  => [ 295, 350, 'tri' ],
		9  => [ 350, 295, 'tri' ],
		10 => [ 295, 200, 'diamond' ],
		11 => [ 350, 105, 'tri' ],
		12 => [ 295, 50, 'tri' ],
	];

	/** South Indian: the cell of each sign (0 = Aries) as [column, row] on a 4 by 4 grid. */
	private const SOUTH = [
		11 => [ 0, 0 ],
		0  => [ 1, 0 ],
		1  => [ 2, 0 ],
		2  => [ 3, 0 ],
		3  => [ 3, 1 ],
		4  => [ 3, 2 ],
		5  => [ 3, 3 ],
		6  => [ 2, 3 ],
		7  => [ 1, 3 ],
		8  => [ 0, 3 ],
		9  => [ 0, 2 ],
		10 => [ 0, 1 ],
	];

	/**
	 * @param string $style  'north' or 'south'.
	 * @param int    $lagna  Sign index of the lagna, 0 = Aries.
	 * @param array  $bodies Each [ 'glyph' => id, 'name' => label, 'sign' => 0-11, 'retro' => bool ].
	 * @param array  $a11y   'title', 'desc', 'lagna_mark' (the short word for the lagna on a South chart).
	 */
	public static function draw( string $style, int $lagna, array $bodies, array $a11y = [] ): string {
		if ( $lagna < 0 || $lagna > 11 ) {
			return '';
		}
		$by_sign = array_fill( 0, 12, [] );
		foreach ( $bodies as $b ) {
			$sign = (int) ( $b['sign'] ?? -1 );
			if ( $sign >= 0 && $sign < 12 ) {
				$by_sign[ $sign ][] = $b;
			}
		}
		$id   = UI::uid();
		$svg  = sprintf( '<svg class="astroway-kundli__svg" viewBox="0 0 %1$d %1$d" role="img" aria-labelledby="%2$s-t %2$s-d" focusable="false">', self::SIZE, $id );
		$svg .= '<title id="' . $id . '-t">' . esc_html( (string) ( $a11y['title'] ?? '' ) ) . '</title><desc id="' . $id . '-d">' . esc_html( (string) ( $a11y['desc'] ?? '' ) ) . '</desc>';
		$svg .= 'south' === $style ? self::south( $lagna, $by_sign, (string) ( $a11y['lagna_mark'] ?? '' ) ) : self::north( $lagna, $by_sign );
		return $svg . '</svg>';
	}

	private static function north( int $lagna, array $by_sign ): string {
		$p   = self::PAD;
		$e   = self::SIZE - self::PAD;
		$m   = self::SIZE / 2;
		$out = '<g class="astroway-kundli__lines">'
			. sprintf( '<rect x="%1$d" y="%1$d" width="%2$d" height="%2$d"/>', $p, $e - $p )
			. sprintf( '<path d="M%1$d %1$dL%2$d %2$dM%2$d %1$dL%1$d %2$dM%3$s %1$dL%2$d %3$sL%3$s %2$dL%1$d %3$sZ"/>', $p, $e, self::n( $m ) )
			. '</g>';
		foreach ( self::NORTH as $house => [ $x, $y, $shape ] ) {
			$sign = ( $lagna + $house - 1 ) % 12;
			$out .= self::house( $x, $y, (string) ( $sign + 1 ), $by_sign[ $sign ], 'tri' === $shape ? 2 : 3, 1 === $house, 'tri' === $shape );
		}
		return $out;
	}

	private static function south( int $lagna, array $by_sign, string $mark ): string {
		$p    = self::PAD;
		$cell = ( self::SIZE - 2 * $p ) / 4;
		$out  = '<g class="astroway-kundli__lines">';
		foreach ( self::SOUTH as $sign => [ $c, $r ] ) {
			$out .= sprintf( '<rect x="%1$s" y="%2$s" width="%3$s" height="%3$s"/>', self::n( $p + $c * $cell ), self::n( $p + $r * $cell ), self::n( $cell ) );
		}
		$out .= '</g>';
		foreach ( self::SOUTH as $sign => [ $c, $r ] ) {
			$x0 = $p + $c * $cell;
			$y0 = $p + $r * $cell;
			if ( $sign === $lagna ) {
				// The lagna's cell carries the traditional slash across its corner.
				$out .= sprintf( '<path class="astroway-kundli__lagna-line" d="M%1$s %2$sL%3$s %4$s"/>', self::n( $x0 ), self::n( $y0 + 24 ), self::n( $x0 + 24 ), self::n( $y0 ) );
				if ( '' !== $mark ) {
					$out .= sprintf( '<text class="astroway-kundli__lagna" x="%1$s" y="%2$s">%3$s</text>', self::n( $x0 + $cell - 8 ), self::n( $y0 + 16 ), esc_html( $mark ) );
				}
			}
			$out .= self::house( $x0 + $cell / 2, $y0 + $cell / 2 + 4, '', $by_sign[ $sign ], 3, false, count( $by_sign[ $sign ] ) > 4 );
			$out .= sprintf( '<g transform="translate(%1$s %2$s)" class="astroway-kundli__sign">%3$s</g>', self::n( $x0 + 14 ), self::n( $y0 + $cell - 14 ), self::glyph( Render::sign_slug( $sign ), 0, 0, 11 ) );
		}
		return $out;
	}

	/** A house's sign number on top and its planets in rows under it. */
	/** $tight: a small cell (a North triangle, a crowded South square) where more than a few planets shrink to fit. */
	private static function house( float $x, float $y, string $number, array $bodies, int $per_row, bool $first, bool $tight = false ): string {
		$crowd = $tight && count( $bodies ) > 3;
		$em    = $crowd ? 12 : 18;
		$step  = $crowd ? 17 : 26;
		$line  = $crowd ? 16 : 25;
		if ( $crowd ) {
			$per_row = 3;
		}
		$out = '';
		$top = $bodies ? $y - 18 : $y;
		if ( '' !== $number ) {
			$out .= sprintf( '<text class="astroway-kundli__num%1$s" x="%2$s" y="%3$s">%4$s</text>', $first ? ' is-lagna' : '', self::n( $x ), self::n( $top ), esc_html( $number ) );
		}
		$rows = array_chunk( $bodies, $per_row );
		$y0   = $y + ( '' === $number ? -( count( $rows ) - 1 ) * $line / 2 : 6 );
		foreach ( $rows as $i => $row ) {
			$x0 = $x - ( count( $row ) - 1 ) * $step / 2;
			foreach ( $row as $j => $b ) {
				$cx   = $x0 + $j * $step;
				$cy   = $y0 + $i * $line;
				$out .= '<g class="astroway-kundli__body' . ( empty( $b['retro'] ) ? '' : ' is-retro' ) . '"><title>' . esc_html( (string) ( $b['name'] ?? '' ) ) . '</title>'
					. self::glyph( (string) ( $b['glyph'] ?? '' ), $cx, $cy, $em )
					. ( empty( $b['retro'] ) ? '' : sprintf( '<text class="astroway-kundli__retro" x="%1$s" y="%2$s">R</text>', self::n( $cx + 9 ), self::n( $cy + 8 ) ) )
					. '</g>';
			}
		}
		return $out;
	}

	/** A glyph centred on a point, $em tall in chart units. */
	private static function glyph( string $id, float $x, float $y, float $em ): string {
		$ref = Glyphs::ref( $id );
		if ( '' === $ref ) {
			return '';
		}
		$box = explode( ' ', Glyphs::viewbox( $id ) );
		$h   = $em * 1.24;
		$w   = $h * (float) ( $box[2] ?? 1 ) / max( 1.0, (float) ( $box[3] ?? 1 ) );
		return sprintf(
			'<use href="%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s"/>',
			esc_attr( $ref ),
			self::n( $x - $w / 2 ),
			self::n( $y - $h / 2 ),
			self::n( $w ),
			self::n( $h )
		);
	}

	private static function n( float $v ): string {
		return rtrim( rtrim( number_format( $v, 2, '.', '' ), '0' ), '.' );
	}
}
