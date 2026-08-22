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
	'astroway_tarot_marseille_birth_card' => [ '/tarot/marseille/birth-card', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_tarot_marseille_cards' => [ '/tarot/marseille/cards', 'GET', [  ] ],
	'astroway_tarot_marseille_cards_get' => [ '/tarot/marseille/cards/{slug}', 'GET', [ [ 'slug', '', 's', 9 ] ] ],
	'astroway_tarot_marseille_clarify' => [ '/tarot/marseille/clarify', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_daily' => [ '/tarot/marseille/daily', 'POST', [ [ 'date', '', 's', 0 ] ] ],
	'astroway_tarot_marseille_draw_career' => [ '/tarot/marseille/draw/career', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_celtic_cross' => [ '/tarot/marseille/draw/celtic-cross', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_cross' => [ '/tarot/marseille/draw/cross', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_decision' => [ '/tarot/marseille/draw/decision', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_hero' => [ '/tarot/marseille/draw/hero', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_love' => [ '/tarot/marseille/draw/love', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_seven_card' => [ '/tarot/marseille/draw/seven-card', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_single' => [ '/tarot/marseille/draw/single', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_spiritual' => [ '/tarot/marseille/draw/spiritual', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_draw_three_card' => [ '/tarot/marseille/draw/three-card', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_interpret' => [ '/tarot/marseille/interpret', 'POST', [ [ 'cards', '', 'j', 3 ], [ 'question', '', 's', 0 ] ] ],
	'astroway_tarot_marseille_majors' => [ '/tarot/marseille/majors', 'GET', [  ] ],
	'astroway_tarot_marseille_spreads' => [ '/tarot/marseille/spreads', 'GET', [  ] ],
	'astroway_tarot_marseille_spreads_get' => [ '/tarot/marseille/spreads/{slug}', 'GET', [ [ 'slug', '', 's', 9 ] ] ],
	'astroway_tarot_marseille_timing' => [ '/tarot/marseille/timing', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_marseille_year_card' => [ '/tarot/marseille/year-card', 'POST', [ [ 'date', '', 's', 1 ], [ 'year', '', 'n', 1 ] ] ],
];
