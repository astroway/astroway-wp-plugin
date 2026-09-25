<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The natal wheel, drawn here as SVG rather than framed from the api.
 *
 * Ascendant on the left, as every astrologer expects. Rings from the outside
 * in: degrees with ticks, signs, houses, the aspect circle. Planets that
 * crowd each other are fanned out along the ring with a lead line back to
 * their true degree. Colour follows the look's tokens; harmony and tension
 * also differ by stroke, so the wheel reads in any look and without colour.
 *
 * On a column narrower than 520px the degrees, house numbers and retrograde
 * marks go (the positions table has all three) and what stays grows, so no
 * glyph or label on the wheel is set smaller than 11px on a 320px phone.
 *
 * @since 2.0.0
 */
class Wheel {

	private const C       = 200;
	private const R_OUTER = 196;
	private const R_SIGNS = [ 156, 184 ];
	private const R_ASP   = 92;
	private const MIN_SEP = 9.2;

	private static float $asc = 0.0;

	/**
	 * @param array $chart `asc`, `mc`, optional `cusps` (12 longitudes),
	 *                     `planets`: each `id` (glyph id), `lon`, `rx`, `label`
	 *                     (what a reader hears: "Sun in Taurus, 24°20′, house 9");
	 *                     `aspects`: each `a`, `b` (planet ids), `kind` (h, t, n), `orb`;
	 *                     `title`, `desc`; `angles`: labels for asc, ic, dsc, mc.
	 */
	public static function natal( array $chart ): string {
		self::$asc = (float) ( $chart['asc'] ?? 0 );
		$cusps     = array_values( array_map( 'floatval', (array) ( $chart['cusps'] ?? [] ) ) );
		if ( 12 !== count( $cusps ) ) {
			$cusps = [];
		}

		$title = (string) ( $chart['title'] ?? '' );
		$desc  = (string) ( $chart['desc'] ?? '' );
		$id    = UI::uid();

		$svg  = sprintf( '<svg class="astroway-wheel__svg" viewBox="-56 -56 512 512" role="img" aria-labelledby="%1$s-t %1$s-d" focusable="false">', $id );
		$svg .= '<title id="' . $id . '-t">' . esc_html( $title ) . '</title><desc id="' . $id . '-d">' . esc_html( $desc ) . '</desc>';
		$svg .= self::rings() . self::signs() . self::houses( $cusps ) . self::aspects( $chart ) . self::angles( $chart, $cusps ) . self::planets( $chart );
		$svg .= '</svg>';

		return '<figure class="astroway-wheel">' . $svg . '</figure>';
	}

	private static function rings(): string {
		list( $inner, $outer ) = self::R_SIGNS;
		$c                     = self::C;
		$html                  = sprintf(
			'<path class="astroway-wheel__band" fill-rule="evenodd" d="M%1$s,%2$s a%3$s,%3$s 0 1,0 %4$s,0 a%3$s,%3$s 0 1,0 -%4$s,0 Z M%5$s,%2$s a%6$s,%6$s 0 1,0 %7$s,0 a%6$s,%6$s 0 1,0 -%7$s,0 Z"/>',
			$c - $outer,
			$c,
			$outer,
			2 * $outer,
			$c - $inner,
			$inner,
			2 * $inner
		);
		foreach ( [ self::R_OUTER, $outer, $inner, 106, self::R_ASP ] as $r ) {
			$html .= '<circle class="astroway-wheel__circle" cx="' . $c . '" cy="' . $c . '" r="' . $r . '"/>';
		}
		$ticks = '';
		for ( $d = 0; $d < 360; $d++ ) {
			$len             = 0 === $d % 10 ? 8 : ( 0 === $d % 5 ? 5.5 : 3 );
			list( $x1, $y1 ) = self::at( $d, self::R_OUTER );
			list( $x2, $y2 ) = self::at( $d, self::R_OUTER - $len );
			$ticks          .= 'M' . self::n( $x1 ) . ',' . self::n( $y1 ) . 'L' . self::n( $x2 ) . ',' . self::n( $y2 );
		}
		return $html . '<path class="astroway-wheel__ticks" d="' . $ticks . '"/>';
	}

	private static function signs(): string {
		$signs                 = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];
		list( $inner, $outer ) = self::R_SIGNS;
		$html                  = '';
		foreach ( $signs as $i => $sign ) {
			list( $x1, $y1 ) = self::at( $i * 30, $outer );
			list( $x2, $y2 ) = self::at( $i * 30, $inner );
			$html           .= self::line( 'astroway-wheel__div', $x1, $y1, $x2, $y2 );
			list( $x, $y )   = self::at( $i * 30 + 15, ( $inner + $outer ) / 2 );
			$html           .= self::glyph( $sign, $x, $y, 16, 'astroway-wheel__sign' );
		}
		return $html;
	}

	private static function houses( array $cusps ): string {
		if ( empty( $cusps ) ) {
			return '';
		}
		$html = '';
		for ( $i = 0; $i < 12; $i++ ) {
			$span          = fmod( $cusps[ ( $i + 1 ) % 12 ] - $cusps[ $i ] + 360, 360 );
			list( $x, $y ) = self::at( $cusps[ $i ] + $span / 2, 99 );
			$html         .= '<text class="astroway-wheel__hnum" x="' . self::n( $x ) . '" y="' . self::n( $y ) . '">' . ( $i + 1 ) . '</text>';
			// The four angles draw their own, longer axis.
			if ( 0 !== $i % 3 ) {
				list( $x1, $y1 ) = self::at( $cusps[ $i ], self::R_SIGNS[0] );
				list( $x2, $y2 ) = self::at( $cusps[ $i ], self::R_ASP );
				$html           .= self::line( 'astroway-wheel__cusp', $x1, $y1, $x2, $y2 );
			}
		}
		return $html;
	}

	private static function aspects( array $chart ): string {
		$lon = [];
		foreach ( (array) ( $chart['planets'] ?? [] ) as $p ) {
			$lon[ (string) ( $p['id'] ?? '' ) ] = (float) ( $p['lon'] ?? 0 );
		}
		$html = '';
		foreach ( (array) ( $chart['aspects'] ?? [] ) as $a ) {
			$kind = in_array( $a['kind'] ?? '', [ 'h', 't' ], true ) ? $a['kind'] : '';
			$from = (string) ( $a['a'] ?? '' );
			$to   = (string) ( $a['b'] ?? '' );
			// A conjunction is two planets in one place: a line would be a dot.
			if ( '' === $kind || ! isset( $lon[ $from ], $lon[ $to ] ) ) {
				continue;
			}
			list( $x1, $y1 ) = self::at( $lon[ $from ], self::R_ASP );
			list( $x2, $y2 ) = self::at( $lon[ $to ], self::R_ASP );
			// Tighter aspects draw stronger.
			$strength = max( 0.4, 1 - (float) ( $a['orb'] ?? 0 ) / 8 );
			$html    .= sprintf(
				'<line class="astroway-wheel__asp astroway-line--%1$s" data-a="%2$s" data-b="%3$s" x1="%4$s" y1="%5$s" x2="%6$s" y2="%7$s" stroke-opacity="%8$s"/>',
				$kind,
				esc_attr( $from ),
				esc_attr( $to ),
				self::n( $x1 ),
				self::n( $y1 ),
				self::n( $x2 ),
				self::n( $y2 ),
				self::n( $strength )
			);
		}
		return '' === $html ? '' : '<g class="astroway-wheel__aspects">' . $html . '</g>';
	}

	private static function angles( array $chart, array $cusps ): string {
		$labels = (array) ( $chart['angles'] ?? [] );
		$asc    = self::$asc;
		$mc     = isset( $chart['mc'] ) ? (float) $chart['mc'] : ( $cusps[9] ?? null );
		$list   = [ [ $asc, $labels['asc'] ?? 'ASC' ], [ $asc + 180, $labels['dsc'] ?? 'DSC' ] ];
		if ( null !== $mc ) {
			$list[] = [ $mc, $labels['mc'] ?? 'MC' ];
			$list[] = [ $mc + 180, $labels['ic'] ?? 'IC' ];
		}
		$html = '';
		foreach ( $list as list( $lon, $label ) ) {
			list( $x1, $y1 ) = self::at( $lon, self::R_ASP );
			list( $x2, $y2 ) = self::at( $lon, 202 );
			// A label beside the wheel grows away from it, one above or below
			// sits centred on its axis: the ascendant's must never cross the ring.
			$side          = cos( deg2rad( 180 + ( $lon - self::$asc ) ) );
			$anchor        = $side < -0.5 ? 'end' : ( $side > 0.5 ? 'start' : 'middle' );
			list( $x, $y ) = self::at( $lon, 'middle' === $anchor ? 214 : 205 );
			$html         .= self::line( 'astroway-wheel__angle', $x1, $y1, $x2, $y2 );
			$html         .= '<text class="astroway-wheel__alabel" x="' . self::n( $x ) . '" y="' . self::n( $y ) . '" text-anchor="' . $anchor . '">' . esc_html( (string) $label ) . '</text>';
		}
		return $html;
	}

	private static function planets( array $chart ): string {
		$list = [];
		foreach ( (array) ( $chart['planets'] ?? [] ) as $p ) {
			if ( ! Glyphs::exists( (string) ( $p['id'] ?? '' ) ) ) {
				continue;
			}
			$list[] = [
				'id'    => (string) $p['id'],
				'lon'   => self::norm( (float) ( $p['lon'] ?? 0 ) ),
				'rx'    => ! empty( $p['rx'] ),
				'label' => (string) ( $p['label'] ?? '' ),
			];
		}
		$html = '';
		foreach ( self::spread( $list, self::MIN_SEP ) as $p ) {
			list( $m1x, $m1y ) = self::at( $p['lon'], self::R_SIGNS[0] );
			list( $m2x, $m2y ) = self::at( $p['lon'], 149 );
			$html             .= self::line( 'astroway-wheel__mark', $m1x, $m1y, $m2x, $m2y );
			$moved             = abs( fmod( $p['pos'] - $p['lon'] + 540, 360 ) - 180 ) > 1.5;
			if ( $moved ) {
				list( $lx, $ly ) = self::at( $p['pos'], 150 );
				$html           .= self::line( 'astroway-wheel__lead', $m2x, $m2y, $lx, $ly );
			}
			// Glyph, then its degree, inwards along one radius. Minutes live in
			// the table and the tooltip: on the wheel they crowded into each
			// other and into the house numbers.
			list( $gx, $gy ) = self::at( $p['pos'], 139 );
			list( $dx, $dy ) = self::at( $p['pos'], 121 );
			$deg             = (int) floor( fmod( $p['lon'], 30 ) );

			$html .= sprintf( '<g class="astroway-wheel__planet" data-planet="%1$s" data-label="%2$s">', esc_attr( $p['id'] ), esc_attr( $p['label'] ) );
			$html .= '<circle class="astroway-wheel__hit" cx="' . self::n( $gx ) . '" cy="' . self::n( $gy ) . '" r="22"/>';
			$html .= self::glyph( $p['id'], $gx, $gy, 19, 'astroway-wheel__glyph' );
			$html .= '<text class="astroway-wheel__deg" x="' . self::n( $dx ) . '" y="' . self::n( $dy ) . '">' . $deg . '°</text>';
			// Tucked against the glyph's lower right, where it cannot be read
			// as belonging to the planet next door.
			if ( $p['rx'] ) {
				$html .= self::glyph( 'retrograde', $gx + 10.5, $gy + 8, 9, 'astroway-wheel__rx' );
			}
			$html .= '</g>';
		}
		return $html;
	}

	/**
	 * Fans crowded planets out: neighbours closer than $min_sep degrees merge
	 * into a cluster that is spread evenly around its own centre, repeated
	 * until no two clusters touch. The cut is made in the widest empty arc, so
	 * a cluster never wraps across 0° Aries.
	 *
	 * @param array $items Each with `lon`.
	 * @return array The same items, each with `pos`, the degree it is drawn at.
	 */
	public static function spread( array $items, float $min_sep ): array {
		if ( empty( $items ) ) {
			return [];
		}
		usort( $items, static fn( $a, $b ) => $a['lon'] <=> $b['lon'] );
		$count  = count( $items );
		$cut    = 0;
		$widest = -1.0;
		for ( $i = 0; $i < $count; $i++ ) {
			$gap = fmod( $items[ ( $i + 1 ) % $count ]['lon'] - $items[ $i ]['lon'] + 360, 360 );
			$gap = 0.0 === $gap ? 360.0 : $gap;
			if ( $gap > $widest ) {
				$widest = $gap;
				$cut    = ( $i + 1 ) % $count;
			}
		}
		$seq      = array_merge( array_slice( $items, $cut ), array_slice( $items, 0, $cut ) );
		$base     = $seq[0]['lon'];
		$clusters = [];
		foreach ( $seq as $i => $item ) {
			$x = $item['lon'];
			while ( $x < $base ) {
				$x += 360;
			}
			$clusters[] = [
				'idx' => [ $i ],
				't'   => [ $x ],
			];
		}
		$place = static function ( array $cluster ) use ( $min_sep ): array {
			$n    = count( $cluster['t'] );
			$mean = array_sum( $cluster['t'] ) / $n;
			$out  = [];
			for ( $k = 0; $k < $n; $k++ ) {
				$out[] = $mean + ( $k - ( $n - 1 ) / 2 ) * $min_sep;
			}
			return $out;
		};
		do {
			$merged = false;
			$total  = count( $clusters );
			for ( $i = 0; $i < $total - 1; $i++ ) {
				$a = $place( $clusters[ $i ] );
				$b = $place( $clusters[ $i + 1 ] );
				if ( $b[0] - end( $a ) < $min_sep ) {
					array_splice(
						$clusters,
						$i,
						2,
						[
							[
								'idx' => array_merge( $clusters[ $i ]['idx'], $clusters[ $i + 1 ]['idx'] ),
								't'   => array_merge( $clusters[ $i ]['t'], $clusters[ $i + 1 ]['t'] ),
							],
						]
					);
					$merged = true;
					break;
				}
			}
		} while ( $merged );
		foreach ( $clusters as $cluster ) {
			foreach ( $place( $cluster ) as $k => $pos ) {
				$seq[ $cluster['idx'][ $k ] ]['pos'] = self::norm( $pos );
			}
		}
		return $seq;
	}

	/**
	 * A glyph centred on a point; $em is its size in wheel units. The point is
	 * a translate on a group, so the glyph's own origin is its centre and the
	 * CSS that enlarges it on a narrow column scales it in place.
	 */
	private static function glyph( string $id, float $x, float $y, float $em, string $css_class ): string {
		$ref = Glyphs::ref( $id );
		if ( '' === $ref ) {
			return '';
		}
		$box = explode( ' ', Glyphs::viewbox( $id ) );
		$h   = $em * 1.24;
		$w   = $h * (float) ( $box[2] ?? 1 ) / max( 1.0, (float) ( $box[3] ?? 1 ) );
		return sprintf(
			'<g transform="translate(%1$s %2$s)"><use class="%3$s" href="%4$s" x="%5$s" y="%6$s" width="%7$s" height="%8$s"/></g>',
			self::n( $x ),
			self::n( $y ),
			$css_class,
			esc_attr( $ref ),
			self::n( -$w / 2 ),
			self::n( -$h / 2 ),
			self::n( $w ),
			self::n( $h )
		);
	}

	private static function line( string $css_class, float $x1, float $y1, float $x2, float $y2 ): string {
		return sprintf( '<line class="%1$s" x1="%2$s" y1="%3$s" x2="%4$s" y2="%5$s"/>', $css_class, self::n( $x1 ), self::n( $y1 ), self::n( $x2 ), self::n( $y2 ) );
	}

	/** Point at an ecliptic longitude and radius, with the Ascendant on the left. */
	private static function at( float $lon, float $r ): array {
		$a = deg2rad( 180 + ( $lon - self::$asc ) );
		return [ self::C + $r * cos( $a ), self::C - $r * sin( $a ) ];
	}

	private static function norm( float $lon ): float {
		return fmod( fmod( $lon, 360 ) + 360, 360 );
	}

	private static function n( float $value ): string {
		$text = rtrim( rtrim( sprintf( '%.2F', $value ), '0' ), '.' );
		return '-0' === $text ? '0' : $text;
	}
}
