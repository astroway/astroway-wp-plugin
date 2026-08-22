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
	'label' => 'Astro-Geography',
	'endpoints' => [
		'astroway_acg' => [
			'title' => 'Astrocartography (A*C*G)',
			'description' => 'Calculate A*C*G lines for all planets: the geodetic map lines where each planet was on an angle at birth.',
			'documented' => true,
		],
		'astroway_acg_zones' => [
			'title' => 'A*C*G Lines Near a Point',
			'description' => 'A*C*G lines passing within radiusDeg of one location, with the angular distance to each. The "what runs over this city" lookup.',
			'documented' => true,
		],
		'astroway_acg_best_places' => [
			'title' => 'Best places for a life category',
			'description' => 'Rank cities against the astrocartography lines of a chart for one of the 19 life categories. Distance to a line is computed from the body hour angle and altitude rather than against a discretised polyline, so it is exact: the distance to a horizon line IS the altitude in degrees of arc, and a meridian line offset is the hour angle times cos(latitude). Supportive and challenging totals are returned',
			'documented' => true,
		],
		'astroway_acg_by_category' => [
			'title' => 'A*C*G by Life Category',
			'description' => 'A*C*G lines that govern one area of life, ranked by astrological weight and, when a point is supplied, by proximity to it. Returns curated interpretation text per line in 11 languages.',
			'documented' => true,
		],
		'astroway_acg_categories' => [
			'title' => 'A*C*G Life Categories',
			'description' => 'Reference taxonomy: 19 areas of life, each naming the planet-and-angle lines that govern it with a weight and a polarity. Free, cacheable, drives the two endpoints below.',
			'documented' => true,
		],
		'astroway_acg_countries' => [
			'title' => 'Countries available for ranking',
			'description' => 'Which countries the bundled city list can rank, and how many qualifying cities each has. Asking for a country with two cities and getting two results should not look like a bug.',
			'documented' => true,
		],
		'astroway_acg_line_report' => [
			'title' => 'A*C*G Line Report',
			'description' => 'One line in full: its geometry, every life area it touches with weight and polarity, and the interpretation text for that planet-and-angle pair. Rising and setting curves arrive as several disjoint segments; all of them are returned.',
			'documented' => true,
		],
		'astroway_ccg_analysis' => [
			'title' => 'CCG Analysis',
			'description' => 'Analyse Cyclo-Carto-Graphy (CCG) lines: progressed A*C*G lines at a target date with mundane/local aspects and latitude crossings.',
			'documented' => true,
		],
		'astroway_eclipse_analysis' => [
			'title' => 'Eclipse Analysis',
			'description' => 'Analyse the impact of upcoming eclipses on a natal chart: aspects to natal planets, house activations, and Saros series context.',
			'documented' => true,
		],
		'astroway_geodetic' => [
			'title' => 'Geodetic Equivalents',
			'description' => 'Convert geographic coordinates to geodetic chart degrees (Sepharial method): each longitude maps to a zodiac degree on the Earth\'s surface.',
			'documented' => true,
		],
		'astroway_horizon' => [
			'title' => 'Horizon Chart',
			'description' => 'Calculate the horizon chart showing each planet\'s altitude and azimuth relative to the observer\'s local horizon at birth.',
			'documented' => true,
		],
		'astroway_local_space' => [
			'title' => 'Local Space Chart',
			'description' => 'Calculate local space chart: azimuth and altitude of each planet from a given geographic location at the birth moment.',
			'documented' => true,
		],
		'astroway_local_space_influence_zone' => [
			'title' => 'Local Space Influence Zone',
			'description' => 'Calculate the local space influence zone lines for a chart relocated to a specific city: lines on the map showing planet directions from that location.',
			'documented' => true,
		],
		'astroway_parans' => [
			'title' => 'Parans',
			'description' => 'Crossing points of two A*C*G lines: the places where two planets were simultaneously angular. Returns every planet-to-planet crossing with its coordinates.',
			'documented' => true,
		],
		'astroway_parans_star' => [
			'title' => 'Star-planet parans (Brady)',
			'description' => 'Latitudes where a fixed star and a planet are angular at the same moment, which is how Brady tabulates a paran: not a place, a latitude. Twelve event pairs per star and planet (rise, set, culminate, anticulminate, minus meridian against meridian, which is a shared right ascension rather than a paran). Each row carries the houses of the technique in plain form: which body does what, the latitude, a',
			'documented' => true,
		],
		'astroway_phase_return' => [
			'title' => 'Phase Return',
			'description' => 'Find dates when a planet returns to its same synodic phase relative to the Sun (e.g. next Venus heliacal rising, next Mars retrograde station).',
			'documented' => true,
		],
		'astroway_relocation' => [
			'title' => 'Relocation Chart',
			'description' => 'Recalculate a natal chart for a new geographic location (same birth moment, different lat/lng). Returns new houses and planet house positions.',
			'documented' => true,
		],
		'astroway_solar_acg' => [
			'title' => 'Solar A*C*G',
			'description' => 'Astrocartography for the solar return of a given year: the A*C*G map of the moment the Sun comes back to its natal longitude. Planet names carry an SR suffix.',
			'documented' => true,
		],
		'astroway_zenith' => [
			'title' => 'Zenith',
			'description' => 'Find all geographic locations where a given planet is exactly at the zenith (MC) at the birth moment.',
			'documented' => true,
		],
	],
];
