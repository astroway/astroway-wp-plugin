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
	'label' => 'Tarot: Lenormand',
	'endpoints' => [
		'astroway_tarot_lenormand_cards' => [
			'title' => 'Lenormand: All Cards',
			'description' => '36-card Lenormand oracle deck.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_cards_get' => [
			'title' => 'Lenormand: Single Card',
			'description' => 'Single Lenormand card lookup by slug.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_daily' => [
			'title' => 'Lenormand: Daily Cards',
			'description' => 'Daily three-card draw based on date seed.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_9_card_square' => [
			'title' => 'Lenormand: 9-Card Square',
			'description' => 'Three-by-three grid: rows = past/present/future, cols = mind/heart/body.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_celtic_cross_lenormand' => [
			'title' => 'Lenormand: Celtic Cross',
			'description' => 'Adapted Celtic Cross with Lenormand cards.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_grand_tableau' => [
			'title' => 'Lenormand: Grand Tableau',
			'description' => 'Full 36-card layout: every card and house used.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_line_of_five' => [
			'title' => 'Lenormand: Line of Five',
			'description' => 'Five-card linear story spread.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_relationship' => [
			'title' => 'Lenormand: Relationship',
			'description' => '7-card relationship dynamics.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_draw_three_card' => [
			'title' => 'Lenormand: Three-Card',
			'description' => 'Subject / Situation / Outcome three-card line.',
			'documented' => true,
		],
		'astroway_tarot_lenormand_houses' => [
			'title' => 'Lenormand: 36 Houses',
			'description' => 'The 36 fixed houses for Grand Tableau interpretation.',
			'documented' => true,
		],
	],
];
