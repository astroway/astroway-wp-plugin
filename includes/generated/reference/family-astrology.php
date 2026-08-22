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
	'label' => 'Family Astrology',
	'endpoints' => [
		'astroway_family_genogram' => [
			'title' => 'Genogram',
			'description' => 'Multi-generational family pattern map.',
			'documented' => true,
		],
		'astroway_family_parent_child_deep' => [
			'title' => 'Parent-Child Deep',
			'description' => 'Deep parent-child synastry analysis.',
			'documented' => true,
		],
		'astroway_family_saturn_return_cycles' => [
			'title' => 'Saturn Return Cycles',
			'description' => 'Saturn return timing across a family cohort.',
			'documented' => true,
		],
		'astroway_family_sibling_dynamics' => [
			'title' => 'Sibling Dynamics',
			'description' => 'Inter-sibling synastry and dynamics.',
			'documented' => true,
		],
		'astroway_family_system_pattern' => [
			'title' => 'Family System Pattern',
			'description' => 'System-level family astrological signatures.',
			'documented' => true,
		],
	],
];
