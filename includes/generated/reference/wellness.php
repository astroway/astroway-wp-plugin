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
	'label' => 'Wellness',
	'endpoints' => [
		'astroway_wellness_biorhythm' => [
			'title' => 'Biorhythm (JSON)',
			'description' => 'Physical (23d), emotional (28d) and intellectual (33d) cycle values per day, plus the critical days where a cycle crosses zero. Data twin of POST /render/biorhythm.',
			'documented' => true,
		],
		'astroway_wellness_crystals' => [
			'title' => 'Healing Crystals by Sign',
			'description' => 'Primary + supportive crystals + intentions per zodiac sign.',
			'documented' => true,
		],
		'astroway_wellness_cycle' => [
			'title' => 'Wellness Cycle Milestones',
			'description' => 'Age-based wellness milestones (Saturn return, Uranus opposition, hormonal shifts), nearby + full list.',
			'documented' => true,
		],
		'astroway_wellness_diet' => [
			'title' => 'Dietary Suggestions',
			'description' => 'Element-based dietary focus: emphasize/avoid foods + cooking style.',
			'documented' => true,
		],
		'astroway_wellness_exercise' => [
			'title' => 'Exercise Recommendations',
			'description' => 'Element-based intensity + recommended/avoid activities.',
			'documented' => true,
		],
		'astroway_wellness_herbs' => [
			'title' => 'Herbs by Planetary Ruler',
			'description' => 'Herbs ruled by sign\'s traditional planetary lord (Culpeper). Includes all 7 classical planets.',
			'documented' => true,
		],
		'astroway_wellness_medical_astrology' => [
			'title' => 'Medical Astrology (Body Rulership)',
			'description' => 'Body parts ruled by sun-sign per traditional Melothesia (head→toe). Returns primary + secondary + vulnerabilities.',
			'documented' => true,
		],
		'astroway_wellness_mental_health' => [
			'title' => 'Mental-Health Profile',
			'description' => 'Element-profile across luminaries + Mercury/Venus/Mars; identifies dominant element + strengths/vulnerabilities/coping.',
			'documented' => true,
		],
		'astroway_wellness_sleep_cycles' => [
			'title' => 'Moon-Phase Sleep Tips',
			'description' => 'Sleep recommendations per moon phase. Pair with /sun-times to find current phase.',
			'documented' => true,
		],
		'astroway_wellness_yoga' => [
			'title' => 'Yoga Practice by Sign',
			'description' => 'Sign-specific yoga focus + asanas + pranayama.',
			'documented' => true,
		],
	],
];
