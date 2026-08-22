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
	'label' => 'Tarot: Rider-Waite-Smith',
	'endpoints' => [
		'astroway_tarot_rider_waite_advice' => [
			'title' => 'RWS: Advice Card',
			'description' => 'Single advice card.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_birth_card' => [
			'title' => 'RWS: Birth Card',
			'description' => 'Birth card from date per Mary Greer\'s method (m+d+y reduced).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_cards' => [
			'title' => 'RWS: All Cards',
			'description' => 'Full 78-card RWS deck listing with upright/reversed meanings, keywords, astrology, and yes/no affinity.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_cards_get' => [
			'title' => 'RWS: Single Card',
			'description' => 'Single RWS card lookup by slug (e.g. "the-fool", "ace-of-cups"). Returns full meaning structure.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_clarify' => [
			'title' => 'RWS: Clarifier Card',
			'description' => 'Single clarifying card after a primary draw.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_courts' => [
			'title' => 'RWS: 16 Court Cards',
			'description' => 'The 16 Court cards (Page, Knight, Queen, King × 4 suits).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_cross_sum' => [
			'title' => 'RWS: Court Card Cross-Sum',
			'description' => 'Birth-card meditation pair for court-card practice.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_daily' => [
			'title' => 'RWS: Daily Card',
			'description' => 'Daily card based on date seed (deterministic per day).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_career' => [
			'title' => 'RWS: Career',
			'description' => '5-card career trajectory spread.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_celtic_cross' => [
			'title' => 'RWS: Celtic Cross',
			'description' => 'Classical 10-card Celtic Cross spread.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_chakra' => [
			'title' => 'RWS: Chakra',
			'description' => '7-card chakra spread (Root → Crown).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_decision' => [
			'title' => 'RWS: Yes/No',
			'description' => 'Single-card draw with yes/no/maybe verdict from card affinity.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_horseshoe' => [
			'title' => 'RWS: Horseshoe',
			'description' => '7-card horseshoe progression.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_love_triangle' => [
			'title' => 'RWS: Love Triangle',
			'description' => '6-card three-person love dynamics.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_relationship' => [
			'title' => 'RWS: Relationship',
			'description' => '7-card relationship dynamics.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_shadow_work' => [
			'title' => 'RWS: Shadow Work',
			'description' => '6-card shadow integration spread.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_single' => [
			'title' => 'RWS: Single Card Draw',
			'description' => 'Draw 1 card from the RWS deck. Optional seed for deterministic shuffle.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_spiritual_path' => [
			'title' => 'RWS: Spiritual Path',
			'description' => '5-card spiritual development spread.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_three_card' => [
			'title' => 'RWS: Three-Card Draw',
			'description' => 'Past / Present / Future three-card spread.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_draw_year_ahead' => [
			'title' => 'RWS: Year Ahead',
			'description' => '13-card spread (12 months + theme).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_elements' => [
			'title' => 'RWS: Cards by Element',
			'description' => 'All Minor cards mapped to fire / water / air / earth element.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_interpret' => [
			'title' => 'RWS: Interpret a Hand',
			'description' => 'Resolve a list of card slugs into structured meanings (use AI /interpret/* for full narrative).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_keywords' => [
			'title' => 'RWS: Cards by Keyword',
			'description' => 'Substring search across upright and reversed keywords across all 78 cards.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_majors' => [
			'title' => 'RWS: 22 Majors',
			'description' => 'The 22 Major Arcana cards only.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_minors' => [
			'title' => 'RWS: 40 Minors',
			'description' => 'The 40 numbered Minor Arcana cards (Ace through 10 of each suit).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_missing_info' => [
			'title' => 'RWS: Missing Info Card',
			'description' => 'Single card to surface hidden information.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_numbers' => [
			'title' => 'RWS: Cards of Number',
			'description' => 'All Minor Arcana cards of a given number 1-14 (Ace=1..King=14).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_outcome' => [
			'title' => 'RWS: Outcome Card',
			'description' => 'Single outcome card.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_shadow_card' => [
			'title' => 'RWS: Shadow Card',
			'description' => 'Shadow card pair (mirror in major arcana of personality card).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_soul_personality_card' => [
			'title' => 'RWS: Soul + Personality',
			'description' => 'Returns soul card and personality card pair from birth date.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_spreads' => [
			'title' => 'RWS: All Spreads',
			'description' => 'List of all 12 RWS spread definitions.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_spreads_get' => [
			'title' => 'RWS: Single Spread',
			'description' => 'Definition of a single spread by slug.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_suits' => [
			'title' => 'RWS: Suit',
			'description' => 'All 14 cards of a suit (wands, cups, swords, or pentacles).',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_timing' => [
			'title' => 'RWS: Timing Card',
			'description' => 'Single timing card to indicate when.',
			'documented' => true,
		],
		'astroway_tarot_rider_waite_year_card' => [
			'title' => 'RWS: Year Card',
			'description' => 'Year card per Greer (m+d+year reduced).',
			'documented' => true,
		],
	],
];
