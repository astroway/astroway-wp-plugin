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
	'label' => 'Prognostics',
	'endpoints' => [
		'astroway_firdaria' => [
			'title' => 'Firdaria',
			'description' => 'Calculate Firdaria planetary periods: the traditional Persian time-lord system based on sect and planet order.',
			'documented' => true,
		],
		'astroway_forecast_calendar' => [
			'title' => 'Forecast Calendar',
			'description' => 'Generate a combined forecast calendar including transits, lunar phases, ingresses, and retrograde stations for a given period.',
			'documented' => true,
		],
		'astroway_lunar_return' => [
			'title' => 'Lunar Return',
			'description' => 'Find the next date when the Moon returns to its natal longitude. Returns full lunar return chart.',
			'documented' => true,
		],
		'astroway_minor_progressions' => [
			'title' => 'Minor Progressions',
			'description' => 'Calculate minor progressions (lunar month-for-a-year) to a target date. Supports converse direction.',
			'documented' => true,
		],
		'astroway_planetary_return' => [
			'title' => 'Planetary Return',
			'description' => 'Find all returns of any planet to its natal longitude within a given year. Returns full charts for each return.',
			'documented' => true,
		],
		'astroway_primary_directions' => [
			'title' => 'Primary Directions',
			'description' => 'Calculate primary directions (Ptolemaic or Regiomontanus) up to a maximum age. Returns a timeline of directed aspect hits.',
			'documented' => true,
		],
		'astroway_profections' => [
			'title' => 'Profections',
			'description' => 'Calculate annual profections: profected Ascendant, lord of the year, activated house, and monthly profection sub-rulers.',
			'documented' => true,
		],
		'astroway_progressions' => [
			'title' => 'Secondary Progressions',
			'description' => 'Calculate secondary progressed chart (day-for-a-year) for a target date. Returns progressed planets, angles, and aspects to natal.',
			'documented' => true,
		],
		'astroway_rectification' => [
			'title' => 'Rectification',
			'description' => 'Rectify an approximate birth time using a list of life events and transit/direction hits to narrow the birth time window.',
			'documented' => true,
		],
		'astroway_rectification_trutine' => [
			'title' => 'Trutine of Hermes',
			'description' => 'Apply the Trutine of Hermes (Animodar) technique to derive the birth time from the Moon\'s prenatal syzygy position.',
			'documented' => true,
		],
		'astroway_solar_return' => [
			'title' => 'Solar Return',
			'description' => 'Find the exact moment when the Sun returns to its natal longitude in a given year. Returns full solar return chart.',
			'documented' => true,
		],
		'astroway_symbolic_directions' => [
			'title' => 'Symbolic Directions',
			'description' => 'Calculate symbolic arc directions to natal points for a target date. Key: "one_degree" (default), "naibod", or "solar_arc" (the arc of the progressed Sun).',
			'documented' => true,
		],
		'astroway_tertiary_progressions' => [
			'title' => 'Tertiary Progressions',
			'description' => 'Calculate tertiary progressions (day-for-a-lunar-month) to a target date. Supports converse direction.',
			'documented' => true,
		],
		'astroway_transit_calendar' => [
			'title' => 'Transit Calendar',
			'description' => 'Build a month-by-month transit calendar for a natal chart showing exact dates of aspect ingresses and partile hits.',
			'documented' => true,
		],
		'astroway_transits' => [
			'title' => 'Transits',
			'description' => 'Calculate transit planet aspects to natal planets for a given date. Returns applying and separating aspects.',
			'documented' => true,
		],
	],
];
