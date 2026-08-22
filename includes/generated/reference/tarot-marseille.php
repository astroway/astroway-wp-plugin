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
	'label' => 'Tarot: Marseille',
	'endpoints' => [
		'astroway_tarot_marseille_birth_card' => [
			'title' => 'Marseille: Birth Card',
			'description' => 'Birth card per Greer method, Marseille deck.',
			'documented' => true,
		],
		'astroway_tarot_marseille_cards' => [
			'title' => 'Marseille: All Cards',
			'description' => '78-card Marseille deck. Justice = 8, Strength = 11 (pre-Waite swap). Pip minors interpreted by number+suit.',
			'documented' => true,
		],
		'astroway_tarot_marseille_cards_get' => [
			'title' => 'Marseille: Single Card',
			'description' => 'Single Marseille card lookup by slug.',
			'documented' => true,
		],
		'astroway_tarot_marseille_clarify' => [
			'title' => 'Marseille: Clarifier',
			'description' => 'Single clarifying card.',
			'documented' => true,
		],
		'astroway_tarot_marseille_daily' => [
			'title' => 'Marseille: Daily Card',
			'description' => 'Daily Marseille card based on date seed.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_career' => [
			'title' => 'Marseille: Career',
			'description' => '4-card career spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_celtic_cross' => [
			'title' => 'Marseille: Celtic Cross',
			'description' => 'Adapted 10-card Celtic Cross with Marseille pip-style reading.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_cross' => [
			'title' => 'Marseille: Tirage Réduit (Jodorowsky Reduced Cross)',
			'description' => 'Authentic 5-card cross from Jodorowsky/Costa "The Way of Tarot" + Camoin ArtduTarot: 4 Major Arcana (consultant / external / higher / result) + 5th synthesis card (numerological sum reduced ≤22).',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_decision' => [
			'title' => 'Marseille: Yes/No',
			'description' => 'Single-card yes/no with verdict.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_hero' => [
			'title' => 'Marseille: Tirage du Héros (Hero\'s Journey)',
			'description' => 'Authentic 6-card Hero\'s Journey spread from Jodorowsky/Costa "The Way of Tarot": Hero / Objective / two Obstacles / Key / Resolution.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_love' => [
			'title' => 'Marseille: Love',
			'description' => '5-card love and connection spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_seven_card' => [
			'title' => 'Marseille: Seven-Card',
			'description' => '7-card pyramid spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_single' => [
			'title' => 'Marseille: Single Card',
			'description' => 'Single-card draw from Marseille deck.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_spiritual' => [
			'title' => 'Marseille: Spiritual',
			'description' => '4-card spiritual development spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_draw_three_card' => [
			'title' => 'Marseille: Three-Card',
			'description' => 'Past / Present / Future three-card spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_interpret' => [
			'title' => 'Marseille: Interpret',
			'description' => 'Resolve list of card slugs into meanings.',
			'documented' => true,
		],
		'astroway_tarot_marseille_majors' => [
			'title' => 'Marseille: 22 Majors',
			'description' => '22 Major Arcana of Marseille deck.',
			'documented' => true,
		],
		'astroway_tarot_marseille_spreads' => [
			'title' => 'Marseille: All Spreads',
			'description' => 'List of all Marseille spreads (incl. Jodorowsky cross).',
			'documented' => true,
		],
		'astroway_tarot_marseille_spreads_get' => [
			'title' => 'Marseille: Single Spread',
			'description' => 'Definition of a single Marseille spread.',
			'documented' => true,
		],
		'astroway_tarot_marseille_timing' => [
			'title' => 'Marseille: Timing',
			'description' => 'Single timing card.',
			'documented' => true,
		],
		'astroway_tarot_marseille_year_card' => [
			'title' => 'Marseille: Year Card',
			'description' => 'Year card per Greer method.',
			'documented' => true,
		],
	],
];
