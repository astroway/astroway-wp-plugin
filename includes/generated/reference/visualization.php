<?php
/**
 * Generated from the api.astroway.info OpenAPI spec. Do not edit.
 * Run scripts/generate-from-openapi.py instead.
 *
 * @package AstroWay\WPPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'label' => 'Visualization',
	'endpoints' => [
		'astroway_render_aspect_grid' => [
			'title' => 'Aspect Grid (SVG)',
			'description' => 'Triangular aspect matrix: rows × columns = planets, each cell shows aspect glyph + orb. Standard textbook layout.',
			'documented' => true,
		],
		'astroway_render_bi_wheel' => [
			'title' => 'Bi-Wheel (SVG)',
			'description' => 'Two concentric wheels: inner natal + outer ring with transit (or progression) planets. Standard bi-wheel layout.',
			'documented' => true,
		],
		'astroway_render_biorhythm' => [
			'title' => 'Biorhythm (SVG)',
			'description' => 'Three-curve sine plot of physical (23d), emotional (28d), and intellectual (33d) cycles since birth.',
			'documented' => true,
		],
		'astroway_render_composite' => [
			'title' => 'Composite Chart (SVG)',
			'description' => 'Composite-chart wheel from two natal inputs. Computes midpoint composite then renders as Western wheel.',
			'documented' => true,
		],
		'astroway_render_cosmogram' => [
			'title' => 'Cosmogram: Hamburg School 90° dial (SVG)',
			'description' => 'Cosmobiology 90° dial. Plots planets at (longitude mod 90)° across 4 quadrants, Cardinal/Fixed/Mutable repeated.',
			'documented' => true,
		],
		'astroway_render_eclipse_path' => [
			'title' => 'Eclipse Path Map (SVG)',
			'description' => 'Equirectangular world graticule with caller-supplied eclipse track. Renders centerline + shaded band of given degree-width.',
			'documented' => true,
		],
		'astroway_render_moon_phase' => [
			'title' => 'Moon Phase (SVG)',
			'description' => 'Render the moon disk with its illuminated fraction at the given moment. Returns SVG plus illumination metrics.',
			'documented' => true,
		],
		'astroway_render_star_map' => [
			'title' => 'Star Map (SVG)',
			'description' => 'Stereographic projection of caller-supplied points (RA/Dec). Handles brightness magnitude scaling and labels.',
			'documented' => true,
		],
		'astroway_render_timeline' => [
			'title' => 'Timeline (SVG)',
			'description' => 'Gantt-style horizontal timeline of transit/aspect events over a date window. Caller supplies events array.',
			'documented' => true,
		],
		'astroway_render_tri_wheel' => [
			'title' => 'Tri-Wheel (SVG)',
			'description' => 'Three concentric wheels: natal + progressed + transit. Used for advanced forecasting visuals.',
			'documented' => true,
		],
		'astroway_render_wheel_vedic_east' => [
			'title' => 'Vedic Wheel: East Indian (SVG)',
			'description' => 'East Indian (Bengali) layout: square with diagonals + inner rotated square forming 12 sectors.',
			'documented' => true,
		],
		'astroway_render_wheel_vedic_north' => [
			'title' => 'Vedic Wheel: North Indian (SVG)',
			'description' => 'North Indian diamond chart layout: 12 fixed positions, signs rotate per ascendant. Standard BPHS rendering.',
			'documented' => true,
		],
		'astroway_render_wheel_vedic_south' => [
			'title' => 'Vedic Wheel: South Indian (SVG)',
			'description' => 'South Indian 4×4 grid layout (Pisces top-left, signs fixed). House numbers placed where lagna sign falls.',
			'documented' => true,
		],
		'astroway_render_wheel_western' => [
			'title' => 'Western Wheel (SVG)',
			'description' => 'Render a Western natal wheel as SVG: signs ring, houses ring, planets, aspect lines. Pure server-side, no browser needed.',
			'documented' => true,
		],
	],
];
