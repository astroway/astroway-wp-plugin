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
	'astroway_djamaspa' => [ '/djamaspa', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_esoteric_angel_numbers' => [ '/esoteric/angel-numbers', 'GET', [  ] ],
	'astroway_esoteric_angel_numbers_by_life_path' => [ '/esoteric/angel-numbers/by-life-path/{n}', 'GET', [ [ 'n', '', 'n', 9 ] ] ],
	'astroway_esoteric_angel_numbers_decode' => [ '/esoteric/angel-numbers/decode', 'POST', [ [ 'sequence', '', 's', 1 ], [ 'context', '', 's', 0 ] ] ],
	'astroway_esoteric_angel_numbers_today' => [ '/esoteric/angel-numbers/today', 'GET', [  ] ],
	'astroway_esoteric_angel_numbers_get' => [ '/esoteric/angel-numbers/{number}', 'GET', [ [ 'number', '', 's', 9 ] ] ],
	'astroway_esoteric_crystals' => [ '/esoteric/crystals', 'GET', [  ] ],
	'astroway_esoteric_crystals_by_chakra' => [ '/esoteric/crystals/by-chakra/{chakra}', 'GET', [ [ 'chakra', '', 's', 9 ] ] ],
	'astroway_esoteric_crystals_by_purpose' => [ '/esoteric/crystals/by-purpose/{purpose}', 'GET', [ [ 'purpose', '', 's', 9 ] ] ],
	'astroway_esoteric_crystals_by_zodiac' => [ '/esoteric/crystals/by-zodiac/{sign}', 'GET', [ [ 'sign', '', 's', 9 ] ] ],
	'astroway_esoteric_crystals_recommend' => [ '/esoteric/crystals/recommend', 'POST', [ [ 'sun_sign', 'sunSign', 's', 0 ], [ 'moon_sign', 'moonSign', 's', 0 ], [ 'ascendant_sign', 'ascendantSign', 's', 0 ], [ 'intent', '', 's', 0 ], [ 'limit', '', 'n', 0 ] ] ],
	'astroway_esoteric_dreams' => [ '/esoteric/dreams', 'GET', [  ] ],
	'astroway_esoteric_dreams_by_element' => [ '/esoteric/dreams/by-element/{element}', 'GET', [ [ 'element', '', 's', 9 ] ] ],
	'astroway_esoteric_dreams_decode' => [ '/esoteric/dreams/decode', 'POST', [ [ 'text', '', 's', 1 ], [ 'recurring', '', 'b', 0 ] ] ],
	'astroway_esoteric_dreams_recurring_themes' => [ '/esoteric/dreams/recurring-themes', 'GET', [  ] ],
	'astroway_esoteric_dreams_symbol' => [ '/esoteric/dreams/symbol/{keyword}', 'GET', [ [ 'keyword', '', 's', 9 ] ] ],
	'astroway_iching' => [ '/iching', 'POST', [  ] ],
];
