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
	'label' => 'Evolutionary Astrology',
	'endpoints' => [
		'astroway_evolutionary_nodal_axis_detail' => [
			'title' => 'Nodal Axis Detail',
			'description' => 'Full NN/SN with rulers + conjuncts.',
			'documented' => true,
		],
		'astroway_evolutionary_pluto_natal_condition' => [
			'title' => 'Pluto Natal Condition',
			'description' => 'JWG: Pluto sign+house+ruler+hard aspects.',
			'documented' => true,
		],
		'astroway_evolutionary_skipped_steps' => [
			'title' => 'Skipped Steps',
			'description' => 'Planets squaring nodal axis = unfinished karma.',
			'documented' => true,
		],
		'astroway_evolutionary_soul_types' => [
			'title' => 'Soul Types',
			'description' => 'JWG\'s 4-type by Pluto house quadrant.',
			'documented' => true,
		],
		'astroway_evolutionary_yesterday_sky' => [
			'title' => 'Yesterday\'s Sky',
			'description' => 'Forrest 2008 past-life method.',
			'documented' => true,
		],
	],
];
