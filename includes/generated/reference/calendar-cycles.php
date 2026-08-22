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
	'label' => 'Calendar & Cycles',
	'endpoints' => [
		'astroway_algol_minimum' => [
			'title' => 'Algol Minimum',
			'description' => 'Find the nearest minimum brightness moment of Algol (Beta Persei), the eclipsing variable star, within a date range.',
			'documented' => true,
		],
		'astroway_algol_minimum_nearest' => [
			'title' => 'Algol Nearest Minimum',
			'description' => 'Find the single nearest Algol brightness minimum before or after a given date.',
			'documented' => true,
		],
		'astroway_aspects' => [
			'title' => 'Aspect Matrix',
			'description' => 'Standalone aspect calculation: full inter-planetary aspect list with type / exactAngle / orb / isApplying. Uses per-pair astro.com orb matrix. Same calcAspects() as /chart but without planet positions / houses / midpoints overhead.',
			'documented' => true,
		],
		'astroway_cyclic_index' => [
			'title' => 'Cyclic Index',
			'description' => 'Calculate the André Barbault Cyclic Index, sum of all outer planet separations, for a date range to indicate global crisis periods.',
			'documented' => true,
		],
		'astroway_eclipses' => [
			'title' => 'Eclipses',
			'description' => 'Find solar and lunar eclipses within a given year or multi-year range. Returns type, date, and geographic visibility data.',
			'documented' => true,
		],
		'astroway_houses' => [
			'title' => 'House Cusps',
			'description' => 'Standalone house calculation: 12 cusps + ascendant + MC + ARMC + vertex + co-asc + polar-asc. Supports Placidus, Koch, Regiomontanus, Campanus, Topocentric, Whole Sign, Equal, Porphyry, Morinus etc. Auto-fallback warning on |lat|>66.5° quadrant systems.',
			'documented' => true,
		],
		'astroway_ingresses' => [
			'title' => 'Planet Ingresses',
			'description' => 'Find all sign ingresses for a planet within a date range (including retrograde re-entries).',
			'documented' => true,
		],
		'astroway_lunar_calendar' => [
			'title' => 'Lunar Calendar',
			'description' => 'Calculate a lunar calendar for a given month: Moon sign per day, lunar phases, void-of-course windows, and perigee/apogee.',
			'documented' => true,
		],
		'astroway_moon_aspects' => [
			'title' => 'Moon Aspects',
			'description' => 'Calculate all aspects the Moon makes within a date range (applying and separating), useful for electional and horary work.',
			'documented' => true,
		],
		'astroway_planetary_cycles' => [
			'title' => 'Planetary Cycles',
			'description' => 'Find all conjunctions between two planets within a date range, defining the start of a new synodic cycle.',
			'documented' => true,
		],
		'astroway_planetary_phases' => [
			'title' => 'Planetary Phases',
			'description' => 'Calculate the synodic phase of each planet relative to the Sun (new, crescent, first quarter, gibbous, full, disseminating, last quarter, balsamic).',
			'documented' => true,
		],
		'astroway_retrograde_periods' => [
			'title' => 'Retrograde Periods',
			'description' => 'Find all retrograde stations (direct, retrograde, stationary) for a planet within a date range.',
			'documented' => true,
		],
	],
];
