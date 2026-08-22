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
	'label' => 'Horoscope',
	'endpoints' => [
		'astroway_horoscope_compatibility' => [
			'title' => 'Compatibility Horoscope',
			'description' => 'Generate a relationship horoscope based on synastry between two charts: strengths, friction points, and themes for the next 30 days.',
			'documented' => true,
		],
		'astroway_horoscope_daily' => [
			'title' => 'Daily Horoscope',
			'description' => 'Generate a daily horoscope by zodiac sign, grounded in real ephemeris data (current Moon phase, transits). Multi-language. AI-written, not pre-generated content.',
			'documented' => true,
		],
		'astroway_horoscope_monthly' => [
			'title' => 'Monthly Horoscope',
			'description' => 'Generate a monthly horoscope with major transits, Moon phases, and personal-year themes (profections-aware).',
			'documented' => true,
		],
		'astroway_horoscope_weekly' => [
			'title' => 'Weekly Horoscope',
			'description' => 'Generate a weekly horoscope (7-day window) with key transit events and themes. AI-written, ephemeris-grounded.',
			'documented' => true,
		],
		'astroway_horoscope_yearly' => [
			'title' => 'Yearly Horoscope',
			'description' => 'Generate a year-ahead horoscope (12-month window) with major transit cycles, solar return chart context, and time-lord (profection) for the year.',
			'documented' => true,
		],
	],
];
