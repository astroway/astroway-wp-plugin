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
	'astroway_mayan_calendar_round' => [ '/mayan/calendar-round', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_compatibility' => [ '/mayan/compatibility', 'POST', [ [ 'person1', '', 'j', 3 ], [ 'person2', '', 'j', 3 ] ] ],
	'astroway_mayan_dreamspell' => [ '/mayan/dreamspell', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_full' => [ '/mayan/full', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_haab' => [ '/mayan/haab', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_long_count' => [ '/mayan/long-count', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_lord_of_night' => [ '/mayan/lord-of-night', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_mayan_tzolkin' => [ '/mayan/tzolkin', 'POST', [ [ 'date', '', 's', 1 ] ] ],
];
