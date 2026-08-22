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
	'label' => 'Chinese: Zodiac & Feng Shui',
	'endpoints' => [
		'astroway_chinese_feng_shui_annual_stars' => [
			'title' => 'Annual flying stars and afflictions',
			'description' => 'The nine annual stars for a solar year, plus Tai Sui, Sui Po, San Sha, the five yellow and the two black with the sectors they occupy. Optionally the monthly layer. The year turns at Li Chun, computed from the exact solar term.',
			'documented' => true,
		],
		'astroway_chinese_feng_shui_bagua' => [
			'title' => 'Bagua Life Areas',
			'description' => 'Eastern-school Bagua mapping of 9 life areas (career, knowledge, family, wealth, fame, relationships, children, helpful-people, health) onto compass sectors.',
			'documented' => true,
		],
		'astroway_chinese_feng_shui_flying_star' => [
			'title' => 'Flying Star natal chart (Xuan Kong Fei Xing)',
			'description' => 'The nine-palace natal chart of a building: mountain star, period star and facing star per sector, the named arrangement (旺山旺水 and the other three), and the special patterns. Send the facing in degrees or as one of the 24 mountains, and the period directly or as the date the building was occupied. Neither is defaulted.',
			'documented' => true,
		],
		'astroway_chinese_feng_shui_kua' => [
			'title' => 'Kua Number',
			'description' => 'Personal Kua number from solar-year digit sum + gender. Maps to East/West group for direction work.',
			'documented' => true,
		],
		'astroway_chinese_feng_shui_lucky_directions' => [
			'title' => 'Lucky / Unlucky Directions',
			'description' => 'Personal 4 lucky + 4 unlucky compass directions from Kua. Standard Pa Kua mapping.',
			'documented' => true,
		],
		'astroway_chinese_lunar_date' => [
			'title' => 'Gregorian to Lunar Date',
			'description' => 'Chinese lunar month and day for a Gregorian date, including leap-month detection, plus the Chinese New Year of that year.',
			'documented' => true,
		],
		'astroway_chinese_solar_terms' => [
			'title' => '24 Solar Terms (節氣)',
			'description' => 'The 24 solar terms of a Chinese solar year as exact instants, in UTC and Beijing time. Twelve of them open a BaZi pillar month; the other twelve decide where a leap month falls.',
			'documented' => true,
		],
		'astroway_chinese_tong_shu' => [
			'title' => 'Tong Shu day: officer and mansion',
			'description' => 'The almanac reading of one day: day pillar, the day officer (建除十二神) with what the register endorses and forbids, the 28 mansion with its quadrant and planet, and the animal the day clashes.',
			'documented' => true,
		],
		'astroway_chinese_tong_shu_select' => [
			'title' => 'Tong Shu date selection',
			'description' => 'Walk a date range and return the days the almanac endorses for one activity, each with the officer that decided it. Optionally drop the days that clash a person\'s animal. Range capped at 366 days and a longer one is refused, not truncated.',
			'documented' => true,
		],
		'astroway_chinese_zodiac_compatibility' => [
			'title' => 'Animal Compatibility',
			'description' => 'Pair compatibility score (0-100) using San He trine + Liu Chong conflict-pair canon.',
			'documented' => true,
		],
		'astroway_chinese_zodiac_element' => [
			'title' => 'Chinese Element',
			'description' => 'Fixed-branch element + cycling-stem element + yin/yang for given solar year.',
			'documented' => true,
		],
		'astroway_chinese_zodiac_inner_animal' => [
			'title' => 'Inner Animal (month branch)',
			'description' => 'Month-branch animal: represents inner motivations and private self.',
			'documented' => true,
		],
		'astroway_chinese_zodiac_secret_animal' => [
			'title' => 'Secret Animal (hour branch)',
			'description' => 'Hour-of-birth branch animal: represents the deepest self. Requires birth time.',
			'documented' => true,
		],
	],
];
