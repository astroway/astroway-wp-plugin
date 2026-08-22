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
	'label' => 'Elder Futhark Runes',
	'endpoints' => [
		'astroway_runes' => [
			'title' => 'Elder Futhark List',
			'description' => 'All 24 runes.',
			'documented' => false,
		],
		'astroway_runes_by_zodiac' => [
			'title' => 'Rune by Zodiac',
			'description' => 'Tropical-sign → Elder Futhark affinity rune.',
			'documented' => true,
		],
		'astroway_runes_nine' => [
			'title' => '9-Rune Cast',
			'description' => 'Pennick layout 9-rune cast.',
			'documented' => true,
		],
		'astroway_runes_single' => [
			'title' => 'Single Rune Draw',
			'description' => 'Deterministic 1-rune draw.',
			'documented' => true,
		],
		'astroway_runes_three' => [
			'title' => 'Norn 3-Rune Cast',
			'description' => 'Past/Present/Future (Urdr/Verdandi/Skuld).',
			'documented' => true,
		],
	],
];
