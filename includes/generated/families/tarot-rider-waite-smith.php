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
	'astroway_tarot_rider_waite_advice' => [ '/tarot/rider-waite/advice', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_birth_card' => [ '/tarot/rider-waite/birth-card', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_tarot_rider_waite_cards' => [ '/tarot/rider-waite/cards', 'GET', [  ] ],
	'astroway_tarot_rider_waite_cards_get' => [ '/tarot/rider-waite/cards/{slug}', 'GET', [ [ 'slug', '', 's', 9 ] ] ],
	'astroway_tarot_rider_waite_clarify' => [ '/tarot/rider-waite/clarify', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_courts' => [ '/tarot/rider-waite/courts', 'GET', [  ] ],
	'astroway_tarot_rider_waite_cross_sum' => [ '/tarot/rider-waite/cross-sum', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_tarot_rider_waite_daily' => [ '/tarot/rider-waite/daily', 'POST', [ [ 'date', '', 's', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_career' => [ '/tarot/rider-waite/draw/career', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_celtic_cross' => [ '/tarot/rider-waite/draw/celtic-cross', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_chakra' => [ '/tarot/rider-waite/draw/chakra', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_decision' => [ '/tarot/rider-waite/draw/decision', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_horseshoe' => [ '/tarot/rider-waite/draw/horseshoe', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_love_triangle' => [ '/tarot/rider-waite/draw/love-triangle', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_relationship' => [ '/tarot/rider-waite/draw/relationship', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_shadow_work' => [ '/tarot/rider-waite/draw/shadow-work', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_single' => [ '/tarot/rider-waite/draw/single', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_spiritual_path' => [ '/tarot/rider-waite/draw/spiritual-path', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_three_card' => [ '/tarot/rider-waite/draw/three-card', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_draw_year_ahead' => [ '/tarot/rider-waite/draw/year-ahead', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_elements' => [ '/tarot/rider-waite/elements/{element}', 'GET', [ [ 'element', '', 's', 9 ] ] ],
	'astroway_tarot_rider_waite_interpret' => [ '/tarot/rider-waite/interpret', 'POST', [ [ 'cards', '', 'j', 3 ], [ 'question', '', 's', 0 ] ] ],
	'astroway_tarot_rider_waite_keywords' => [ '/tarot/rider-waite/keywords/{keyword}', 'GET', [ [ 'keyword', '', 's', 9 ] ] ],
	'astroway_tarot_rider_waite_majors' => [ '/tarot/rider-waite/majors', 'GET', [  ] ],
	'astroway_tarot_rider_waite_minors' => [ '/tarot/rider-waite/minors', 'GET', [  ] ],
	'astroway_tarot_rider_waite_missing_info' => [ '/tarot/rider-waite/missing-info', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_numbers' => [ '/tarot/rider-waite/numbers/{n}', 'GET', [ [ 'n', '', 'n', 9 ] ] ],
	'astroway_tarot_rider_waite_outcome' => [ '/tarot/rider-waite/outcome', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_shadow_card' => [ '/tarot/rider-waite/shadow-card', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_tarot_rider_waite_soul_personality_card' => [ '/tarot/rider-waite/soul-personality-card', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_tarot_rider_waite_spreads' => [ '/tarot/rider-waite/spreads', 'GET', [  ] ],
	'astroway_tarot_rider_waite_spreads_get' => [ '/tarot/rider-waite/spreads/{slug}', 'GET', [ [ 'slug', '', 's', 9 ] ] ],
	'astroway_tarot_rider_waite_suits' => [ '/tarot/rider-waite/suits/{suit}', 'GET', [ [ 'suit', '', 's', 9 ] ] ],
	'astroway_tarot_rider_waite_timing' => [ '/tarot/rider-waite/timing', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_rider_waite_year_card' => [ '/tarot/rider-waite/year-card', 'POST', [ [ 'date', '', 's', 1 ], [ 'year', '', 'n', 1 ] ] ],
];
