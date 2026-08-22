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
	'label' => 'Specialized Charts',
	'endpoints' => [
		'astroway_draconic' => [
			'title' => 'Draconic Chart',
			'description' => 'Calculate the draconic chart by shifting all planet longitudes relative to the True Node (0° = True Node).',
			'documented' => true,
		],
		'astroway_harmonics' => [
			'title' => 'Harmonic Chart',
			'description' => 'Calculate a harmonic chart by multiplying all planet longitudes by the given harmonic number (H2–H36).',
			'documented' => true,
		],
		'astroway_heliocentric' => [
			'title' => 'Heliocentric Chart',
			'description' => 'Calculate heliocentric planetary positions (Sun-centered) for a given birth moment. Earth replaces Sun; Moon is excluded.',
			'documented' => true,
		],
		'astroway_vedic_divisional' => [
			'title' => 'Vedic Divisional Chart (DEPRECATED: use /vedic/varga/{D}<*>)',
			'description' => 'DEPRECATED: moved to dedicated per-varga endpoints `/vedic/varga/{D1..D60}` for OpenAPI/SDK ergonomics. This generic endpoint still works and stays live until its 2027-06-15 sunset (12-month, per the /v1 stability policy), then will be removed; migrate to `/vedic/varga/{D}`. Calculate a Vedic divisional (varga) chart using sidereal zodiac. Supported vargas: D1–D60 (e.g. D9 Navamsha).',
			'documented' => true,
		],
	],
];
