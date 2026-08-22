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
	'label' => 'I Ching (Standalone)',
	'endpoints' => [
		'astroway_iching_by_question' => [
			'title' => 'By Question',
			'description' => 'Deterministic hexagram from question text.',
			'documented' => true,
		],
		'astroway_iching_daily' => [
			'title' => 'Daily I Ching',
			'description' => 'Deterministic per-date hexagram.',
			'documented' => true,
		],
		'astroway_iching_lookup' => [
			'title' => 'Hexagram Lookup',
			'description' => 'Fetch hexagram 1-64 by King Wen number.',
			'documented' => true,
		],
		'astroway_iching_throw_coins' => [
			'title' => 'Throw Coins',
			'description' => 'Seeded 3-coin method.',
			'documented' => true,
		],
		'astroway_iching_with_changing_lines' => [
			'title' => 'With Changing Lines',
			'description' => 'Primary + transformed hexagrams.',
			'documented' => true,
		],
	],
];
