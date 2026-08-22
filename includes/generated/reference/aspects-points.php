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
	'label' => 'Aspects & Points',
	'endpoints' => [
		'astroway_antiscia' => [
			'title' => 'Antiscia',
			'description' => 'Calculate antiscia (mirror points along the Cancer/Capricorn axis) and contra-antiscia, plus their aspects to natal planets.',
			'documented' => true,
		],
		'astroway_arabic_parts' => [
			'title' => 'Arabic Parts (Lots)',
			'description' => 'Calculate Arabic parts (Lots) from a natal chart. Returns positions for standard lots (Fortune, Spirit, etc.) and any custom formula.',
			'documented' => true,
		],
		'astroway_aspect_bar' => [
			'title' => 'Aspect Bars (Gantt)',
			'description' => 'Transform aspect timelines into Gantt-style bars grouped by transit planet. Each bar shows the duration an aspect is in orb, with exact dates marked. Useful for visual transit calendars.',
			'documented' => true,
		],
		'astroway_aspect_timeline' => [
			'title' => 'Aspect Timeline',
			'description' => 'Calculate a timeline of when two specific planets form an exact aspect within a date range, including enter/exact/leave dates.',
			'documented' => true,
		],
		'astroway_fixed_stars' => [
			'title' => 'Fixed Stars',
			'description' => 'Calculate conjunctions between natal planets and fixed stars within a specified orb. Returns star details and aspect type.',
			'documented' => true,
		],
		'astroway_fixed_stars_catalog' => [
			'title' => 'Fixed star catalogue',
			'description' => 'The star names this API accepts, from Swiss Ephemeris `fixstars.cat`: traditional name, Bayer nomenclature, constellation and magnitude, with the 36-star astrological default set flagged. Optional `?maxMagnitude=` trims to the bright end. Static lookup, no chart needed.',
			'documented' => true,
		],
		'astroway_gauquelin_sectors' => [
			'title' => 'Gauquelin Sectors',
			'description' => 'Calculate Gauquelin sector positions (1–36) for all planets, indicating whether each planet is in a power zone.',
			'documented' => true,
		],
		'astroway_midpoint_trees' => [
			'title' => 'Midpoint Trees (Uranian)',
			'description' => 'Calculate Uranian midpoint trees and planetary pictures (symmetries) for each focal planet within a given orb.',
			'documented' => true,
		],
		'astroway_midpoints' => [
			'title' => 'Midpoints',
			'description' => 'Calculate all planetary midpoints and their zodiac positions. Optionally include midpoint aspects to natal points.',
			'documented' => true,
		],
		'astroway_parallel_aspects' => [
			'title' => 'Parallel Aspects',
			'description' => 'Calculate parallel (same declination) and contra-parallel (opposite declination) aspects between all planets within orb.',
			'documented' => true,
		],
		'astroway_sabian_symbols' => [
			'title' => 'Sabian Symbols',
			'description' => 'Return the Sabian symbol (Dane Rudhyar) for each given ecliptic longitude.',
			'documented' => true,
		],
	],
];
