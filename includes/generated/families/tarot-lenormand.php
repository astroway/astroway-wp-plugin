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
	'astroway_tarot_lenormand_cards' => [ '/tarot/lenormand/cards', 'GET', [  ] ],
	'astroway_tarot_lenormand_cards_get' => [ '/tarot/lenormand/cards/{slug}', 'GET', [ [ 'slug', '', 's', 9 ] ] ],
	'astroway_tarot_lenormand_daily' => [ '/tarot/lenormand/daily', 'POST', [ [ 'date', '', 's', 0 ] ] ],
	'astroway_tarot_lenormand_draw_9_card_square' => [ '/tarot/lenormand/draw/9-card-square', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_draw_celtic_cross_lenormand' => [ '/tarot/lenormand/draw/celtic-cross-lenormand', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_draw_grand_tableau' => [ '/tarot/lenormand/draw/grand-tableau', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_draw_line_of_five' => [ '/tarot/lenormand/draw/line-of-five', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_draw_relationship' => [ '/tarot/lenormand/draw/relationship', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_draw_three_card' => [ '/tarot/lenormand/draw/three-card', 'POST', [ [ 'seed', '', 'n', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_tarot_lenormand_houses' => [ '/tarot/lenormand/houses', 'GET', [  ] ],
];
