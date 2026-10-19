<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a natal chart says that the api leaves for the reader to work out:
 * the Part of Fortune, the vertex and the South Node, the element and mode it
 * leans on, and the figures its planets make. Pure arithmetic on the chart,
 * so it is tested without a card around it.
 *
 * @since 2.5.0
 */
final class ChartFacts {

	/** Bodies that make figures: the ten planets, not the nodes, Lilith or Chiron. */
	private const FIGURE_BODIES = [ 'sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter', 'saturn', 'uranus', 'neptune', 'pluto' ];

	/**
	 * Orbs for the figures, tighter than the api's aspect list on purpose: a
	 * figure is named only when every side of it is close, so a T-square at the
	 * edge of three wide orbs is not presented as one.
	 */
	private const ORBS = [
		'conjunction' => 8.0,
		'opposition'  => 8.0,
		'trine'       => 8.0,
		'square'      => 8.0,
		'sextile'     => 6.0,
		'quincunx'    => 3.0,
	];

	private const ANGLES = [
		'conjunction' => 0.0,
		'opposition'  => 180.0,
		'trine'       => 120.0,
		'square'      => 90.0,
		'sextile'     => 60.0,
		'quincunx'    => 150.0,
	];

	private const ELEMENTS = [
		'fire'  => [ 'aries', 'leo', 'sagittarius' ],
		'earth' => [ 'taurus', 'virgo', 'capricorn' ],
		'air'   => [ 'gemini', 'libra', 'aquarius' ],
		'water' => [ 'cancer', 'scorpio', 'pisces' ],
	];

	private const MODES = [
		'cardinal' => [ 'aries', 'cancer', 'libra', 'capricorn' ],
		'fixed'    => [ 'taurus', 'leo', 'scorpio', 'aquarius' ],
		'mutable'  => [ 'gemini', 'virgo', 'sagittarius', 'pisces' ],
	];

	private const SIGNS = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];

	public static function norm( float $lon ): float {
		$lon = fmod( $lon, 360.0 );
		return $lon < 0 ? $lon + 360.0 : $lon;
	}

	public static function sign( float $lon ): string {
		return self::SIGNS[ (int) floor( self::norm( $lon ) / 30 ) % 12 ];
	}

	/** Body id as the glyphs name it: "true Node" is the north node, "mean Apogee" Lilith. */
	public static function id( string $name ): string {
		$id      = strtolower( trim( $name ) );
		$aliases = [
			'true node'   => 'north-node',
			'mean node'   => 'north-node',
			'mean apogee' => 'lilith',
			'osc. apogee' => 'lilith',
		];
		return $aliases[ $id ] ?? str_replace( ' ', '-', $id );
	}

	/** @return array<string, float> Body id to longitude. */
	private static function longitudes( array $planets ): array {
		$out = [];
		foreach ( $planets as $p ) {
			if ( is_array( $p ) && isset( $p['name'], $p['longitude'] ) && is_numeric( $p['longitude'] ) ) {
				$out[ self::id( (string) $p['name'] ) ] = self::norm( (float) $p['longitude'] );
			}
		}
		return $out;
	}

	/**
	 * The Part of Fortune, the vertex and the South Node, by longitude. The lot
	 * runs from the Sun to the Moon by day and the other way by night, added to
	 * the Ascendant; with no Ascendant (birth time unknown) there is no lot and
	 * no vertex.
	 *
	 * @param string $sect `diurnal` or `nocturnal`, as the api's chartSect.
	 * @return array<string, float>
	 */
	public static function points( array $planets, array $houses, string $sect ): array {
		$lon = self::longitudes( $planets );
		$out = [];
		if ( isset( $houses['ascendant'], $lon['sun'], $lon['moon'] ) && in_array( $sect, [ 'diurnal', 'nocturnal' ], true ) ) {
			$asc            = (float) $houses['ascendant'];
			$out['fortune'] = 'diurnal' === $sect
				? self::norm( $asc + $lon['moon'] - $lon['sun'] )
				: self::norm( $asc + $lon['sun'] - $lon['moon'] );
		}
		if ( isset( $houses['ascendant'], $houses['vertex'] ) && is_numeric( $houses['vertex'] ) ) {
			$out['vertex'] = self::norm( (float) $houses['vertex'] );
		}
		if ( isset( $lon['north-node'] ) ) {
			$out['south-node'] = self::norm( $lon['north-node'] + 180.0 );
		}
		return $out;
	}

	/**
	 * The element and the mode with the most of the ten planets; a tie names
	 * every leader, in the order they were first counted.
	 *
	 * @return array{element: string[], mode: string[]}
	 */
	public static function dominant( array $planets ): array {
		$elements = [];
		$modes    = [];
		foreach ( self::longitudes( $planets ) as $id => $lon ) {
			if ( ! in_array( $id, self::FIGURE_BODIES, true ) ) {
				continue;
			}
			$sign = self::sign( $lon );
			foreach ( self::ELEMENTS as $e => $signs ) {
				if ( in_array( $sign, $signs, true ) ) {
					$elements[ $e ] = ( $elements[ $e ] ?? 0 ) + 1;
				}
			}
			foreach ( self::MODES as $m => $signs ) {
				if ( in_array( $sign, $signs, true ) ) {
					$modes[ $m ] = ( $modes[ $m ] ?? 0 ) + 1;
				}
			}
		}
		$top = static function ( array $counts ): array {
			if ( empty( $counts ) ) {
				return [];
			}
			$max = max( $counts );
			return array_keys( array_filter( $counts, static fn( $n ) => $n === $max ) );
		};
		return [
			'element' => $top( $elements ),
			'mode'    => $top( $modes ),
		];
	}

	/**
	 * Harmonious (trine, sextile), tense (square, opposition) and the rest.
	 *
	 * @return array{h: int, t: int, n: int}
	 */
	public static function balance( array $aspects ): array {
		$out = [
			'h' => 0,
			't' => 0,
			'n' => 0,
		];
		foreach ( $aspects as $a ) {
			$type = strtolower( trim( (string) ( is_array( $a ) ? ( $a['type']['name'] ?? $a['aspect'] ?? '' ) : '' ) ) );
			if ( in_array( $type, [ 'trine', 'sextile' ], true ) ) {
				++$out['h'];
			} elseif ( in_array( $type, [ 'square', 'opposition' ], true ) ) {
				++$out['t'];
			} elseif ( '' !== $type ) {
				++$out['n'];
			}
		}
		return $out;
	}

	private static function is( float $a, float $b, string $aspect ): bool {
		$d = abs( self::norm( $a - $b ) );
		$d = $d > 180 ? 360 - $d : $d;
		return abs( $d - self::ANGLES[ $aspect ] ) <= self::ORBS[ $aspect ];
	}

	/**
	 * Grand cross, T-square, grand trine, yod and stellium, from the ten
	 * planets' longitudes. A grand cross is not reported again as the four
	 * T-squares inside it, and a stellium in one sign and one house is one
	 * figure. A stellium is three or more planets in one sign, or in one house
	 * when the cusps are known.
	 *
	 * @param float[] $cusps Twelve house cusps, or none.
	 * @return list<array{kind: string, bodies: string[], apex?: string, sign?: string, house?: int}>
	 */
	public static function patterns( array $planets, array $cusps ): array {
		$lon = array_intersect_key( self::longitudes( $planets ), array_flip( self::FIGURE_BODIES ) );
		// The planets' own order, so a figure lists them the way the chart does.
		$ids = array_values( array_filter( self::FIGURE_BODIES, static fn( $id ) => isset( $lon[ $id ] ) ) );
		$out = [];
		$n   = count( $ids );

		$crossed = [];
		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = $i + 1; $j < $n; $j++ ) {
				for ( $k = $j + 1; $k < $n; $k++ ) {
					for ( $l = $k + 1; $l < $n; $l++ ) {
						$four = [ $ids[ $i ], $ids[ $j ], $ids[ $k ], $ids[ $l ] ];
						$opp  = 0;
						$sq   = 0;
						foreach ( [ [ 0, 1 ], [ 0, 2 ], [ 0, 3 ], [ 1, 2 ], [ 1, 3 ], [ 2, 3 ] ] as $pair ) {
							$a    = $lon[ $four[ $pair[0] ] ];
							$b    = $lon[ $four[ $pair[1] ] ];
							$opp += self::is( $a, $b, 'opposition' ) ? 1 : 0;
							$sq  += self::is( $a, $b, 'square' ) ? 1 : 0;
						}
						if ( 2 === $opp && 4 === $sq ) {
							$out[]   = [
								'kind'   => 'grand_cross',
								'bodies' => $four,
							];
							$crossed = array_merge( $crossed, $four );
						}
					}
				}
			}
		}

		for ( $i = 0; $i < $n; $i++ ) {
			for ( $j = $i + 1; $j < $n; $j++ ) {
				for ( $k = $j + 1; $k < $n; $k++ ) {
					$three             = [ $ids[ $i ], $ids[ $j ], $ids[ $k ] ];
					list( $a, $b, $c ) = [ $lon[ $three[0] ], $lon[ $three[1] ], $lon[ $three[2] ] ];
					if ( self::is( $a, $b, 'trine' ) && self::is( $b, $c, 'trine' ) && self::is( $a, $c, 'trine' ) ) {
						$out[] = [
							'kind'   => 'grand_trine',
							'bodies' => $three,
						];
						continue;
					}
					// Each of the three may be the apex: the one square to both ends of an opposition.
					foreach ( [ [ 0, 1, 2 ], [ 0, 2, 1 ], [ 1, 2, 0 ] ] as $o ) {
						$x            = $lon[ $three[ $o[0] ] ];
						$y            = $lon[ $three[ $o[1] ] ];
						$z            = $lon[ $three[ $o[2] ] ];
						$inside_cross = 3 === count( array_intersect( $three, $crossed ) );
						if ( ! $inside_cross && self::is( $x, $y, 'opposition' ) && self::is( $z, $x, 'square' ) && self::is( $z, $y, 'square' ) ) {
							$out[] = [
								'kind'   => 't_square',
								'bodies' => $three,
								'apex'   => $three[ $o[2] ],
							];
						}
						if ( self::is( $x, $y, 'sextile' ) && self::is( $z, $x, 'quincunx' ) && self::is( $z, $y, 'quincunx' ) ) {
							$out[] = [
								'kind'   => 'yod',
								'bodies' => $three,
								'apex'   => $three[ $o[2] ],
							];
						}
					}
				}
			}
		}

		$out = self::merge_on_apex( $out );

		$groups = [];
		foreach ( $ids as $id ) {
			$groups[ 'sign:' . self::sign( $lon[ $id ] ) ][] = $id;
			if ( 12 === count( $cusps ) ) {
				$house = self::house( $lon[ $id ], $cusps );
				if ( null !== $house ) {
					$groups[ 'house:' . $house ][] = $id;
				}
			}
		}
		$groups = array_filter( $groups, static fn( $b ) => count( $b ) >= 3 );
		// Larger groups first, so one that lies inside another is seen as repeating it.
		uasort( $groups, static fn( $a, $b ) => count( $b ) <=> count( $a ) );
		$taken = [];
		foreach ( $groups as $key => $bodies ) {
			foreach ( $taken as $bigger ) {
				if ( ! array_diff( $bodies, $bigger ) ) {
					continue 2;
				}
			}
			$taken[]            = $bodies;
			list( $by, $where ) = explode( ':', $key );
			$figure             = [
				'kind'   => 'stellium',
				'bodies' => $bodies,
			];
			if ( 'sign' === $by ) {
				$figure['sign'] = $where;
			} else {
				$figure['house'] = (int) $where;
			}
			$out[] = $figure;
		}
		return $out;
	}

	/**
	 * Two T-squares (or yods) on one apex that share a base planet are one
	 * figure whose other base is a conjunction: Uranus beside Neptune made the
	 * same T-square twice.
	 */
	private static function merge_on_apex( array $figures ): array {
		$order = array_flip( self::FIGURE_BODIES );
		$out   = [];
		foreach ( $figures as $figure ) {
			if ( ! isset( $figure['apex'] ) ) {
				$out[] = $figure;
				continue;
			}
			foreach ( $out as $i => $kept ) {
				$shared = array_diff( array_intersect( $kept['bodies'], $figure['bodies'] ), [ $figure['apex'] ] );
				if ( ( $kept['kind'] === $figure['kind'] ) && ( $kept['apex'] ?? '' ) === $figure['apex'] && ! empty( $shared ) ) {
					$bodies = array_values( array_unique( array_merge( $kept['bodies'], $figure['bodies'] ) ) );
					usort( $bodies, static fn( $a, $b ) => $order[ $a ] <=> $order[ $b ] );
					$out[ $i ]['bodies'] = $bodies;
					continue 2;
				}
			}
			$out[] = $figure;
		}
		return $out;
	}

	/** House 1 to 12 the longitude falls in, by the cusps. */
	public static function house( float $lon, array $cusps ): ?int {
		$cusps = array_values( array_map( 'floatval', $cusps ) );
		if ( 12 !== count( $cusps ) ) {
			return null;
		}
		$lon = self::norm( $lon );
		for ( $i = 0; $i < 12; $i++ ) {
			$start = self::norm( $cusps[ $i ] );
			$end   = self::norm( $cusps[ ( $i + 1 ) % 12 ] );
			$in    = $start <= $end ? ( $lon >= $start && $lon < $end ) : ( $lon >= $start || $lon < $end );
			if ( $in ) {
				return $i + 1;
			}
		}
		return null;
	}
}
