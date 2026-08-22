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
	'label' => 'AI Interpretations',
	'endpoints' => [
		'astroway_interpret_element' => [
			'title' => 'Chart Element Interpretation',
			'description' => 'AI interpretation of a single chart element (planet/house/aspect) in context. Useful for chart-detail pages.',
			'documented' => true,
		],
		'astroway_interpret_natal' => [
			'title' => 'Natal Chart Interpretation',
			'description' => 'Generate AI interpretation of a natal chart: personality traits, life themes, strongest archetypes. Multi-language. Token-cached for repeats.',
			'documented' => true,
		],
		'astroway_interpret_placement' => [
			'title' => 'Specific Placement Interpretation',
			'description' => 'AI interpretation of a specific planet+sign+house combination: concise, focused on the placement only.',
			'documented' => true,
		],
		'astroway_interpret_synastry' => [
			'title' => 'Synastry Interpretation',
			'description' => 'AI interpretation of synastry between two charts: relationship dynamics, attractions, friction points, long-term outlook.',
			'documented' => true,
		],
		'astroway_interpret_transits' => [
			'title' => 'Transits Interpretation',
			'description' => 'AI interpretation of current/upcoming transits to a natal chart: what each major transit means in life context.',
			'documented' => true,
		],
	],
];
