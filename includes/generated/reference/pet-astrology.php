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
	'label' => 'Pet Astrology',
	'endpoints' => [
		'astroway_pet_best_names' => [
			'title' => 'Best Names by Sign',
			'description' => 'Sign-aligned name suggestions; optional gender filter.',
			'documented' => true,
		],
		'astroway_pet_birth_chart' => [
			'title' => 'Pet Birth Chart',
			'description' => 'Full natal chart for the pet (sun, moon, ASC + planets) plus disclaimer.',
			'documented' => true,
		],
		'astroway_pet_communication_style' => [
			'title' => 'Communication Style',
			'description' => 'How best to communicate with this pet.',
			'documented' => true,
		],
		'astroway_pet_diet_by_sign' => [
			'title' => 'Diet by Sign',
			'description' => 'Element-based dietary focus.',
			'documented' => true,
		],
		'astroway_pet_exercise_needs' => [
			'title' => 'Exercise Needs',
			'description' => 'Element-based daily exercise minutes + recommended activities.',
			'documented' => true,
		],
		'astroway_pet_grooming_by_element' => [
			'title' => 'Grooming by Element',
			'description' => 'Grooming focus + frequency by element.',
			'documented' => true,
		],
		'astroway_pet_health_tips' => [
			'title' => 'Health Watch-Outs',
			'description' => 'Sign-specific health vulnerabilities. NOT veterinary advice.',
			'documented' => true,
		],
		'astroway_pet_lucky_day' => [
			'title' => 'Lucky Days',
			'description' => 'Days of week traditionally aligned with the sign.',
			'documented' => true,
		],
		'astroway_pet_owner_pet_compatibility' => [
			'title' => 'Owner-Pet Compatibility',
			'description' => 'Compatibility score (0-100) based on element + sign distance.',
			'documented' => true,
		],
		'astroway_pet_personality' => [
			'title' => 'Pet Personality Profile',
			'description' => 'Full personality breakdown.',
			'documented' => true,
		],
		'astroway_pet_play_style' => [
			'title' => 'Play Style + Toys',
			'description' => 'Preferred play and toy types.',
			'documented' => true,
		],
		'astroway_pet_sun_sign_meaning' => [
			'title' => 'Pet Sun-Sign Meaning',
			'description' => 'Personality + temperament + best-suited household.',
			'documented' => true,
		],
		'astroway_pet_temperament' => [
			'title' => 'Pet Temperament',
			'description' => 'Short temperament-only summary.',
			'documented' => true,
		],
		'astroway_pet_training_style' => [
			'title' => 'Training Style',
			'description' => 'Preferred training style + 3 tips.',
			'documented' => true,
		],
	],
];
