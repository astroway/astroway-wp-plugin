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
	'label' => 'Horary',
	'endpoints' => [
		'astroway_horary' => [
			'title' => 'Horary Chart',
			'description' => 'Calculate a horary chart for a question moment and return the chart data with radicality assessment and significator analysis.',
			'documented' => true,
		],
		'astroway_horary_diagnostics' => [
			'title' => 'Horary Diagnostics',
			'description' => 'Run a full horary radicality diagnostic including early/late ASC, Via Combusta, Saturn in 7th, and considerations before judgement.',
			'documented' => true,
		],
		'astroway_horary_moon_aspects' => [
			'title' => 'Horary Moon Aspects',
			'description' => 'List all aspects the Moon will make in this horary chart before leaving its sign, the key timing tool in horary.',
			'documented' => true,
		],
		'astroway_horary_moon_voc' => [
			'title' => 'Horary Moon VOC',
			'description' => 'Check if the Moon is void-of-course in this horary chart and return the last aspect it made and when it enters the next sign.',
			'documented' => true,
		],
		'astroway_horary_planetary_hours' => [
			'title' => 'Horary Planetary Hours',
			'description' => 'Return the planetary hour ruler at the exact moment of a horary question and check if it matches the ASC ruler.',
			'documented' => true,
		],
		'astroway_horary_via_combusta' => [
			'title' => 'Via Combusta Check',
			'description' => 'Check if the Moon or ASC is in the Via Combusta (15° Libra – 15° Scorpio), a classical prohibition in horary.',
			'documented' => true,
		],
	],
];
