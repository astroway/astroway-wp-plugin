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
	'label' => 'BaZi (Four Pillars)',
	'endpoints' => [
		'astroway_bazi_day_master' => [
			'title' => 'Day Master',
			'description' => 'Day stem element + yin/yang polarity + canonical archetype description. The "self" character in BaZi from which all other pillars are interpreted.',
			'documented' => true,
		],
		'astroway_bazi_element_balance' => [
			'title' => '5-Element Balance',
			'description' => 'Element counts across year + month (4 of 8 chars). Identifies dominant + missing elements.',
			'documented' => true,
		],
		'astroway_bazi_four_pillars' => [
			'title' => 'Four Pillars (full)',
			'description' => 'All four pillars: year, month, day, hour. Day pillar uses the canonical 60-jiazi cycle (anchor 1990-01-01 = Bing-Yin). Pass `time` to compute the hour pillar; day and hour roll at 23:00 local, year and month at the exact Lichun and 節 instants.',
			'documented' => true,
		],
		'astroway_bazi_hour_pillar' => [
			'title' => 'Hour Pillar',
			'description' => 'Hour pillar via 五鼠遁 (Five-Rats-Escape) day-stem → hour-stem table. The Zi hour opens the day at 23:00 local time.',
			'documented' => true,
		],
		'astroway_bazi_luck_pillars' => [
			'title' => 'Luck Pillars (Da Yun)',
			'description' => '10-year luck pillars sequence. Direction by gender + year-stem polarity per Ziping canon.',
			'documented' => true,
		],
		'astroway_bazi_month_pillar' => [
			'title' => 'Month Pillar',
			'description' => 'Month stem + branch from solar-term boundaries.',
			'documented' => true,
		],
		'astroway_bazi_monthly' => [
			'title' => 'Monthly Forecast',
			'description' => 'Same Sheng-Ke + branch-clash analysis as yearly, but applied to a specific calendar month. Month branch resolved via mid-month jieqi.',
			'documented' => true,
		],
		'astroway_bazi_ten_gods' => [
			'title' => 'Ten Gods (Shi Shen)',
			'description' => 'Ten Gods classification per day master. Bi Jian/Jie Cai (peer), Shi Shen/Shang Guan (output), Pian Cai/Zheng Cai (wealth), Qi Sha/Zheng Guan (officer), Pian Yin/Zheng Yin (resource).',
			'documented' => true,
		],
		'astroway_bazi_year_pillar' => [
			'title' => 'Year Pillar',
			'description' => 'Year stem + branch + animal + element with yin/yang.',
			'documented' => true,
		],
		'astroway_bazi_year_pillar_decade' => [
			'title' => 'Year Pillars × 10',
			'description' => '10 consecutive year pillars from a given starting year.',
			'documented' => true,
		],
		'astroway_bazi_yearly' => [
			'title' => 'Yearly Forecast',
			'description' => 'Compares target year pillar against natal day master + natal year branch. Returns element-flow relation (companion / mother / output / control / wealth) per Sheng-Ke cycle, branch clashes (六冲), and trine support (三合).',
			'documented' => true,
		],
	],
];
