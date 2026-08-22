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
	'label' => 'Human Design',
	'endpoints' => [
		'astroway_hd_circuitry' => [
			'title' => 'HD Circuitry Analysis',
			'description' => 'Analyze which of the 3 Circuits (Tribal, Individual, Collective) and 6 Sub-circuits are activated in a Human Design chart based on defined channels.',
			'documented' => true,
		],
		'astroway_hd_design_date' => [
			'title' => 'HD Design Date',
			'description' => 'Find the Design date (the moment 88° of solar arc before birth) for a given birth moment, the unconscious imprinting point.',
			'documented' => true,
		],
		'astroway_hd_dream_rave' => [
			'title' => 'Dream Rave',
			'description' => 'Calculate the Dream Rave chart: sleep type, active gates in lower centers (Sacral, Solar Plexus, Root), and dream themes.',
			'documented' => true,
		],
		'astroway_hd_group_overlay' => [
			'title' => 'HD Group Overlay',
			'description' => 'Calculate a combined HD group overlay chart for 2 or more people, showing collectively defined centers and channels.',
			'documented' => true,
		],
		'astroway_hd_hologenetic' => [
			'title' => 'Hologenetic Profile',
			'description' => 'Calculate the Gene Keys hologenetic profile (Activation Sequence, Venus Sequence, Pearl Sequence) from HD gate activations.',
			'documented' => true,
		],
		'astroway_hd_incarnation_cross' => [
			'title' => 'Incarnation Cross',
			'description' => 'Return the Incarnation Cross from birth data (or directly from gate numbers). Includes cross name, type, and theme description.',
			'documented' => true,
		],
		'astroway_hd_penta' => [
			'title' => 'Penta Chart',
			'description' => 'Calculate the Penta (group) chart for 3–5 people: combined BodyGraph showing group dynamics and collective conditioning.',
			'documented' => true,
		],
		'astroway_hd_rave_new_years' => [
			'title' => 'Rave New Years',
			'description' => 'Calculate Rave New Year dates (exact moment Sun enters Gate 41) for a range of years. Max range: 50 years.',
			'documented' => true,
		],
		'astroway_hd_sensitivity' => [
			'title' => 'HD Time Sensitivity',
			'description' => 'Analyze how sensitive the HD chart type/authority is to birth time changes. Returns windows where the chart is stable vs. in transition.',
			'documented' => true,
		],
		'astroway_human_design' => [
			'title' => 'Human Design Chart',
			'description' => 'Calculate a full Human Design BodyGraph chart: type, strategy, authority, profile, definition, incarnation cross, centers, channels, and gate activations. Centre identifiers are PascalCase with no separator and are stable: Head, Ajna, Throat, G, Heart, SolarPlexus, Spleen, Sacral, Root. `channels[].centerA` and `centerB` use the same nine. These identifiers are English and have no localised twin y',
			'documented' => true,
		],
		'astroway_human_design_compatibility' => [
			'title' => 'HD Compatibility',
			'description' => 'Calculate Human Design connection chart (compatibility) between two people: electromagnetic connections, compromise, dominance, and companionship channels.',
			'documented' => true,
		],
		'astroway_human_design_transits' => [
			'title' => 'HD Transits',
			'description' => 'Calculate current Human Design transit activations. Optionally overlay on natal chart to show combined defined centers and channels.',
			'documented' => true,
		],
	],
];
