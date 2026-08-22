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
	'astroway_runes' => [ '/runes', 'POST', [  ] ],
	'astroway_runes_by_zodiac' => [ '/runes/by-zodiac', 'POST', [ [ 'sign', '', 's', 1 ] ] ],
	'astroway_runes_nine' => [ '/runes/nine', 'POST', [ [ 'seed', '', 's', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_runes_single' => [ '/runes/single', 'POST', [ [ 'seed', '', 's', 0 ], [ 'date', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
	'astroway_runes_three' => [ '/runes/three', 'POST', [ [ 'seed', '', 's', 0 ], [ 'question', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ] ] ],
];
