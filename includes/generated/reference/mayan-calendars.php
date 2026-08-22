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
	'label' => 'Mayan Calendars',
	'endpoints' => [
		'astroway_mayan_calendar_round' => [
			'title' => 'Calendar Round',
			'description' => 'Combined Tzolkin + Haab: unique date label within the 52-year cycle (18,980 days).',
			'documented' => true,
		],
		'astroway_mayan_compatibility' => [
			'title' => 'Mayan Compatibility',
			'description' => 'Pair compatibility from Tzolkin name + tone alignment plus elemental/directional affinity. Returns 0-100 score.',
			'documented' => true,
		],
		'astroway_mayan_dreamspell' => [
			'title' => 'Dreamspell (Argüelles 1990)',
			'description' => 'Modern synchronometer reinterpretation by José Argüelles. Returns kin (1-260) + tone + seal. Distinct from traditional Tzolkin.',
			'documented' => true,
		],
		'astroway_mayan_full' => [
			'title' => 'Full Mayan Date',
			'description' => 'All four classical components in one call: Long Count + Tzolkin + Haab + Calendar Round + Lord of the Night.',
			'documented' => true,
		],
		'astroway_mayan_haab' => [
			'title' => 'Haab Civil Day',
			'description' => '365-day civil calendar = 18 months × 20 days + 5-day Wayeb. Identifies if date falls in unlucky Wayeb period.',
			'documented' => true,
		],
		'astroway_mayan_long_count' => [
			'title' => 'Long Count',
			'description' => 'Five-place positional notation: baktun.katun.tun.uinal.kin. Days since 4 Ahau 8 Cumku (3114 BC).',
			'documented' => true,
		],
		'astroway_mayan_lord_of_night' => [
			'title' => 'Lord of the Night',
			'description' => '9-day cycle of underworld deities (G1-G9) governing the spiritual influence of each night.',
			'documented' => true,
		],
		'astroway_mayan_tzolkin' => [
			'title' => 'Tzolkin Day Sign',
			'description' => '260-day sacred calendar. Returns 1-13 number + 20 day-name + element + direction + keyword.',
			'documented' => true,
		],
	],
];
