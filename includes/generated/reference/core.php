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
	'label' => 'Core',
	'endpoints' => [
		'astroway_chart' => [
			'title' => 'Natal Chart',
			'description' => 'Calculate a full natal chart: planets, house cusps, aspects, sect, and angular data for a given birth moment. When the birth time is unknown, send timeUnknown: true instead of time and the response carries planets and aspects with houses, angles and sect set to null, plus a timeUnknown block giving the range the Moon covers that day.',
			'documented' => true,
		],
		'astroway_ephemeris' => [
			'title' => 'Ephemeris Range',
			'description' => 'Return planet positions for a date range with configurable step (min 0.01 days). Max range: 1 year for sub-day steps, 5 years otherwise. Optionally filter by planetIds.',
			'documented' => true,
		],
		'astroway_planets' => [
			'title' => 'Planet Positions',
			'description' => 'Calculate raw planet longitudes for a given date/time without full chart context (no houses, no aspects).',
			'documented' => true,
		],
		'astroway_sun_times' => [
			'title' => 'Sun Times',
			'description' => 'Calculate sunrise, sunset, twilight times, and day length for a given date and geographic location.',
			'documented' => true,
		],
	],
];
