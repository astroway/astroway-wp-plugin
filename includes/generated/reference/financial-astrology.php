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
	'label' => 'Financial Astrology',
	'endpoints' => [
		'astroway_financial_career_money_style' => [
			'title' => 'Career Money Style',
			'description' => 'Income / earning archetype by sign.',
			'documented' => true,
		],
		'astroway_financial_investor_archetype' => [
			'title' => 'Investor Archetype',
			'description' => 'Investor type + bias + strength + pitfall by sign.',
			'documented' => true,
		],
		'astroway_financial_lucky_day' => [
			'title' => 'Lucky Day of Week',
			'description' => 'Days traditionally aligned with the sign.',
			'documented' => true,
		],
		'astroway_financial_lucky_numbers' => [
			'title' => 'Lucky Numbers',
			'description' => 'Gematria-style number set per sign.',
			'documented' => true,
		],
		'astroway_financial_market_timing' => [
			'title' => 'Market-Timing Caution Windows',
			'description' => 'Generic caution windows (Mercury Rx, eclipses, Mars Rx, major macro aspects).',
			'documented' => true,
		],
		'astroway_financial_risk_tolerance' => [
			'title' => 'Risk Tolerance',
			'description' => 'Element-based risk tolerance + suggested allocation buckets.',
			'documented' => true,
		],
		'astroway_financial_savings_tips' => [
			'title' => 'Savings Tips',
			'description' => 'Element-based saving recommendations.',
			'documented' => true,
		],
		'astroway_financial_spending_style' => [
			'title' => 'Spending Style',
			'description' => 'Sign-specific spending pattern.',
			'documented' => true,
		],
		'astroway_financial_wealth_cycle' => [
			'title' => 'Wealth Cycle (long)',
			'description' => 'Long-cycle archetype tied to Jupiter (~12y) and Saturn (~29y) returns.',
			'documented' => true,
		],
		'astroway_financial_wealth_house' => [
			'title' => '2nd & 8th House',
			'description' => 'Personal money (2nd) and shared resources (8th) house cusps + signs.',
			'documented' => true,
		],
	],
];
