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
	'astroway_coalescent' => [ '/coalescent', 'POST', [ [ 'input1', '', 'j', 3 ], [ 'input2', '', 'j', 3 ] ] ],
	'astroway_composite' => [ '/composite', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_davison' => [ '/davison', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_group_synastry' => [ '/group-synastry', 'POST', [ [ 'inputs', '', 'j', 3 ] ] ],
	'astroway_match_score' => [ '/match/score', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_synastry_aspect_grid' => [ '/synastry/aspect-grid', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_synastry_attraction_score' => [ '/synastry/attraction-score', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_synastry_element_balance' => [ '/synastry/element-balance', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
	'astroway_synastry_house_overlay' => [ '/synastry/house-overlay', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ] ] ],
];
