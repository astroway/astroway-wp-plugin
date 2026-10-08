<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Human Design bodygraph, drawn here as SVG: nine centres, 36 channels,
 * 64 gates, in the layout and palette every chart of the system uses.
 *
 * The api sends which centres are defined and which gates each side
 * activates, never a drawing, so the geometry below is the plugin's. A
 * channel is two halves, one per gate: design in red, personality in the
 * text colour, both as red under a dashed line, so a hanging gate reads as
 * half a channel and the chart still reads without colour.
 *
 * Gate numbers are set at 10 units, 11px at the drawing's full width; on a
 * narrower column they go, and the activation lists beside it carry them.
 *
 * @since 2.3.0
 */
class Bodygraph {

	public const WIDTH  = 360;
	public const HEIGHT = 604;

	/** Centre outlines. Squares are [x, y, side], the rest polygons. */
	private const CENTRES = [
		'Head'        => [ [ 180, 12 ], [ 226, 76 ], [ 134, 76 ] ],
		'Ajna'        => [ [ 134, 96 ], [ 226, 96 ], [ 180, 162 ] ],
		'Throat'      => [ 146, 184, 68 ],
		'G'           => [ [ 180, 274 ], [ 228, 322 ], [ 180, 370 ], [ 132, 322 ] ],
		'Heart'       => [ [ 240, 380 ], [ 296, 352 ], [ 292, 412 ] ],
		'Spleen'      => [ [ 20, 408 ], [ 106, 464 ], [ 20, 520 ] ],
		'SolarPlexus' => [ [ 340, 408 ], [ 340, 520 ], [ 254, 464 ] ],
		'Sacral'      => [ 146, 436, 68 ],
		'Root'        => [ 146, 526, 68 ],
	];

	/** Each gate's point on its centre's edge, where the channel meets it, which way is inside, and how far in its number sits when not the usual. */
	private const GATES = [
		64 => [ 157, 76, 'Head', 0, -1 ],
		61 => [ 180, 76, 'Head', 0, -1 ],
		63 => [ 203, 76, 'Head', 0, -1 ],
		47 => [ 157, 96, 'Ajna', 0, 1 ],
		24 => [ 180, 96, 'Ajna', 0, 1 ],
		4  => [ 203, 96, 'Ajna', 0, 1 ],
		17 => [ 159.3, 132.3, 'Ajna', 0.8, -0.6 ],
		43 => [ 180, 162, 'Ajna', 0, -1, 15 ],
		11 => [ 200.7, 132.3, 'Ajna', -0.8, -0.6 ],
		62 => [ 160, 184, 'Throat', 0, 1 ],
		23 => [ 180, 184, 'Throat', 0, 1 ],
		56 => [ 200, 184, 'Throat', 0, 1 ],
		16 => [ 146, 206, 'Throat', 1, 0 ],
		20 => [ 146, 230, 'Throat', 1, 0 ],
		35 => [ 214, 204, 'Throat', -1, 0 ],
		12 => [ 214, 218, 'Throat', -1, 0 ],
		45 => [ 214, 232, 'Throat', -1, 0 ],
		31 => [ 160, 252, 'Throat', 0, -1 ],
		8  => [ 180, 252, 'Throat', 0, -1 ],
		33 => [ 200, 252, 'Throat', 0, -1 ],
		7  => [ 163.2, 290.8, 'G', 0.6, 0.8 ],
		1  => [ 180, 274, 'G', 0, 1.3 ],
		13 => [ 196.8, 290.8, 'G', -0.6, 0.8 ],
		10 => [ 132, 322, 'G', 1.3, 0 ],
		25 => [ 228, 322, 'G', -1.3, 0 ],
		15 => [ 163.2, 353.2, 'G', 0.6, -0.8 ],
		2  => [ 180, 370, 'G', 0, -1.3 ],
		46 => [ 196.8, 353.2, 'G', -0.6, -0.8 ],
		21 => [ 273.6, 363.2, 'Heart', 0.1, 1 ],
		51 => [ 240, 380, 'Heart', 1, 0, 11 ],
		26 => [ 263.4, 394.4, 'Heart', 0.4, -1 ],
		40 => [ 294, 382, 'Heart', -1, 0 ],
		48 => [ 37.2, 419.2, 'Spleen', 0.2, 1 ],
		57 => [ 54.4, 430.4, 'Spleen', 0.2, 1 ],
		44 => [ 71.6, 441.6, 'Spleen', 0.1, 1 ],
		50 => [ 93.1, 455.6, 'Spleen', -0.6, 0.9 ],
		32 => [ 80.2, 480.8, 'Spleen', -0.3, -1 ],
		28 => [ 58.7, 494.8, 'Spleen', -0.1, -1 ],
		18 => [ 37.2, 508.8, 'Spleen', 0.1, -1 ],
		36 => [ 322.8, 419.2, 'SolarPlexus', -0.2, 1 ],
		22 => [ 305.6, 430.4, 'SolarPlexus', -0.2, 1 ],
		37 => [ 288.4, 441.6, 'SolarPlexus', -0.1, 1 ],
		6  => [ 266.9, 455.6, 'SolarPlexus', 0.6, 0.9 ],
		49 => [ 279.8, 480.8, 'SolarPlexus', 0.3, -1 ],
		55 => [ 301.3, 494.8, 'SolarPlexus', 0.1, -1 ],
		30 => [ 322.8, 508.8, 'SolarPlexus', -0.1, -1 ],
		5  => [ 160, 436, 'Sacral', 0, 1 ],
		14 => [ 180, 436, 'Sacral', 0, 1 ],
		29 => [ 200, 436, 'Sacral', 0, 1 ],
		34 => [ 146, 458, 'Sacral', 1, 0 ],
		27 => [ 146, 482, 'Sacral', 1, 0 ],
		59 => [ 214, 482, 'Sacral', -1, 0 ],
		42 => [ 160, 504, 'Sacral', 0, -1 ],
		3  => [ 180, 504, 'Sacral', 0, -1 ],
		9  => [ 200, 504, 'Sacral', 0, -1 ],
		53 => [ 160, 526, 'Root', 0, 1 ],
		60 => [ 180, 526, 'Root', 0, 1 ],
		52 => [ 200, 526, 'Root', 0, 1 ],
		54 => [ 146, 548, 'Root', 1, 0 ],
		38 => [ 146, 564, 'Root', 1, 0 ],
		58 => [ 146, 580, 'Root', 1, 0 ],
		19 => [ 214, 548, 'Root', -1, 0 ],
		39 => [ 214, 564, 'Root', -1, 0 ],
		41 => [ 214, 580, 'Root', -1, 0 ],
	];

	/** The 36 channels; a third point bends one around a centre it would cross. */
	private const CHANNELS = [
		[ 64, 47 ],
		[ 61, 24 ],
		[ 63, 4 ],
		[ 17, 62 ],
		[ 43, 23 ],
		[ 11, 56 ],
		[ 16, 48 ],
		[ 20, 57 ],
		[ 20, 34, [ 94, 340 ] ],
		[ 10, 20 ],
		[ 7, 31 ],
		[ 1, 8 ],
		[ 13, 33 ],
		[ 35, 36, [ 344, 280 ] ],
		[ 12, 22, [ 326, 290 ] ],
		[ 45, 21 ],
		[ 10, 57 ],
		[ 10, 34 ],
		[ 25, 51 ],
		[ 15, 5 ],
		[ 2, 14 ],
		[ 46, 29 ],
		[ 26, 44 ],
		[ 40, 37 ],
		[ 34, 57 ],
		[ 27, 50 ],
		[ 59, 6 ],
		[ 42, 53 ],
		[ 3, 60 ],
		[ 9, 52 ],
		[ 54, 32 ],
		[ 38, 28 ],
		[ 58, 18 ],
		[ 19, 49 ],
		[ 39, 55 ],
		[ 30, 41 ],
	];

	/** Distance from a gate's point to its number, inwards. */
	private const LABEL_IN = 9.5;

	/** @return array<int, int> Every gate number the drawing knows. */
	public static function gates(): array {
		return array_keys( self::GATES );
	}

	/** @return string[] The centre names, as the api spells them. */
	public static function centres(): array {
		return array_keys( self::CENTRES );
	}

	/**
	 * @param array $chart `centres`: name => defined; `gates`: number =>
	 *                     ['p' => personality, 'd' => design]; `title`, `desc`.
	 */
	public static function draw( array $chart ): string {
		$centres = (array) ( $chart['centres'] ?? [] );
		$gates   = (array) ( $chart['gates'] ?? [] );
		$id      = UI::uid();

		$svg  = sprintf( '<svg class="astroway-bodygraph__svg" viewBox="0 0 %1$d %2$d" role="img" aria-labelledby="%3$s-t %3$s-d" focusable="false">', self::WIDTH, self::HEIGHT, $id );
		$svg .= '<title id="' . $id . '-t">' . esc_html( (string) ( $chart['title'] ?? '' ) ) . '</title><desc id="' . $id . '-d">' . esc_html( (string) ( $chart['desc'] ?? '' ) ) . '</desc>';
		$svg .= self::channels( $gates ) . self::shapes( $centres ) . self::numbers( $centres, $gates );
		$svg .= '</svg>';

		return $svg;
	}

	private static function channels( array $gates ): string {
		$track = '';
		$lit   = '';
		foreach ( self::CHANNELS as $channel ) {
			$a      = self::GATES[ $channel[0] ];
			$b      = self::GATES[ $channel[1] ];
			$bend   = $channel[2] ?? null;
			$track .= '<path d="' . self::path( [ $a[0], $a[1] ], $bend, [ $b[0], $b[1] ] ) . '"/>';

			// Each half from its gate to the middle: split the curve at t = 0.5.
			$c   = $bend ?? [ ( $a[0] + $b[0] ) / 2, ( $a[1] + $b[1] ) / 2 ];
			$mid = [ ( $a[0] + 2 * $c[0] + $b[0] ) / 4, ( $a[1] + 2 * $c[1] + $b[1] ) / 4 ];
			foreach ( [ [ $channel[0], $a ], [ $channel[1], $b ] ] as [ $gate, $end ] ) {
				$on = $gates[ $gate ] ?? [];
				if ( empty( $on['p'] ) && empty( $on['d'] ) ) {
					continue;
				}
				$d    = self::path( [ $end[0], $end[1] ], null === $bend ? null : [ ( $end[0] + $c[0] ) / 2, ( $end[1] + $c[1] ) / 2 ], $mid );
				$lit .= sprintf( '<g class="astroway-bodygraph__half" data-gate="%d">', $gate );
				if ( ! empty( $on['d'] ) ) {
					$lit .= '<path class="astroway-bodygraph__design" d="' . $d . '"/>';
				}
				if ( ! empty( $on['p'] ) ) {
					$lit .= '<path class="astroway-bodygraph__personality' . ( empty( $on['d'] ) ? '' : ' is-both' ) . '" d="' . $d . '"/>';
				}
				$lit .= '</g>';
			}
		}
		return '<g class="astroway-bodygraph__tracks">' . $track . '</g><g class="astroway-bodygraph__lit">' . $lit . '</g>';
	}

	private static function shapes( array $centres ): string {
		$out = '';
		foreach ( self::CENTRES as $name => $shape ) {
			$class = 'astroway-bodygraph__centre astroway-bodygraph__centre--' . strtolower( $name ) . ( empty( $centres[ $name ] ) ? '' : ' is-defined' );
			if ( is_array( $shape[0] ) ) {
				$points = implode( ' ', array_map( static fn( $p ) => self::n( $p[0] ) . ',' . self::n( $p[1] ), $shape ) );
				$out   .= sprintf( '<polygon class="%s" points="%s"/>', $class, $points );
			} else {
				$out .= sprintf( '<rect class="%1$s" x="%2$s" y="%3$s" width="%4$s" height="%4$s" rx="5"/>', $class, self::n( $shape[0] ), self::n( $shape[1] ), self::n( $shape[2] ) );
			}
		}
		return '<g class="astroway-bodygraph__centres">' . $out . '</g>';
	}

	private static function numbers( array $centres, array $gates ): string {
		$out = '';
		foreach ( self::GATES as $gate => $g ) {
			$on    = $gates[ $gate ] ?? [];
			$class = 'astroway-bodygraph__gate' . ( empty( $centres[ $g[2] ] ) ? '' : ' is-in-defined' ) . ( empty( $on['p'] ) && empty( $on['d'] ) ? '' : ' is-on' );
			$len   = sqrt( $g[3] * $g[3] + $g[4] * $g[4] );
			$in    = $g[5] ?? self::LABEL_IN;
			$x     = $g[0] + $g[3] / $len * $in;
			$y     = $g[1] + $g[4] / $len * $in;
			$out  .= sprintf( '<text class="%1$s" x="%2$s" y="%3$s">%4$d</text>', $class, self::n( $x ), self::n( $y ), $gate );
		}
		return '<g class="astroway-bodygraph__gates" aria-hidden="true">' . $out . '</g>';
	}

	/** A line, or a quadratic curve through a bend. */
	private static function path( array $from, ?array $bend, array $to ): string {
		if ( null === $bend ) {
			return 'M' . self::n( $from[0] ) . ' ' . self::n( $from[1] ) . 'L' . self::n( $to[0] ) . ' ' . self::n( $to[1] );
		}
		return 'M' . self::n( $from[0] ) . ' ' . self::n( $from[1] ) . 'Q' . self::n( $bend[0] ) . ' ' . self::n( $bend[1] ) . ' ' . self::n( $to[0] ) . ' ' . self::n( $to[1] );
	}

	private static function n( float $v ): string {
		return rtrim( rtrim( number_format( $v, 1, '.', '' ), '0' ), '.' );
	}
}
