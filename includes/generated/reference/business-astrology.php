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
	'label' => 'Business Astrology',
	'endpoints' => [
		'astroway_business_customer_archetype' => [
			'title' => 'Customer Archetype',
			'description' => 'Native customer persona that resonates with the founder\'s sun-sign brand voice.',
			'documented' => true,
		],
		'astroway_business_electional_day' => [
			'title' => 'Electional-Day Suitability',
			'description' => 'Suitability of a proposed launch date for the venture type.',
			'documented' => true,
		],
		'astroway_business_expansion_timing' => [
			'title' => 'Expansion Timing',
			'description' => 'High-level expansion guidance; pair with /transits for actual windows.',
			'documented' => true,
		],
		'astroway_business_founder_personality' => [
			'title' => 'Founder Personality',
			'description' => 'Founder type + strengths/weaknesses + ideal industry.',
			'documented' => true,
		],
		'astroway_business_founding_chart' => [
			'title' => 'Founding-Day Chart',
			'description' => 'Treat the proposed founding date as a chart; returns sun/moon/ASC + theme.',
			'documented' => true,
		],
		'astroway_business_ideal_industry' => [
			'title' => 'Ideal Industries',
			'description' => 'Industries best suited to this founder profile.',
			'documented' => true,
		],
		'astroway_business_ideal_partner_sign' => [
			'title' => 'Ideal Partner Signs',
			'description' => 'Trine elemental partners with natural synergy.',
			'documented' => true,
		],
		'astroway_business_leadership_style' => [
			'title' => 'Leadership Style',
			'description' => 'Concise leadership archetype + key strengths.',
			'documented' => true,
		],
		'astroway_business_marketing_style' => [
			'title' => 'Marketing Style',
			'description' => 'Element-based marketing voice and campaign style.',
			'documented' => true,
		],
		'astroway_business_name_suggestions' => [
			'title' => 'Name Suggestions',
			'description' => 'Naming hints (syllable structure, palette, semantic field) by sign.',
			'documented' => true,
		],
		'astroway_business_risk_profile' => [
			'title' => 'Risk Profile',
			'description' => 'Element-based risk tolerance + recommended safeguards.',
			'documented' => true,
		],
		'astroway_business_team_compatibility' => [
			'title' => 'Team Compatibility',
			'description' => 'Founder × partner compatibility score.',
			'documented' => true,
		],
	],
];
