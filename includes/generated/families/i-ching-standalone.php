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
	'astroway_iching_by_question' => [ '/iching/by-question', 'POST', [ [ 'question', '', 's', 1 ] ] ],
	'astroway_iching_daily' => [ '/iching/daily', 'POST', [ [ 'date', '', 's', 0 ] ] ],
	'astroway_iching_lookup' => [ '/iching/lookup/{number}', 'GET', [ [ 'number', '', 'n', 9 ] ] ],
	'astroway_iching_throw_coins' => [ '/iching/throw-coins', 'POST', [ [ 'seed', '', 's', 0 ] ] ],
	'astroway_iching_with_changing_lines' => [ '/iching/with-changing-lines', 'POST', [ [ 'seed', '', 's', 0 ] ] ],
];
