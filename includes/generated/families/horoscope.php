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
	'astroway_horoscope_daily' => [ '/horoscope/daily', 'POST', [ [ 'sign', '', 's', 1 ], [ 'date', '', 's', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_horoscope_monthly' => [ '/horoscope/monthly', 'POST', [ [ 'sign', '', 's', 1 ], [ 'date', '', 's', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_horoscope_weekly' => [ '/horoscope/weekly', 'POST', [ [ 'sign', '', 's', 1 ], [ 'date', '', 's', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
];
