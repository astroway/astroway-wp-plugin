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
	'astroway_interpret_element' => [ '/interpret/element', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_interpret_natal' => [ '/interpret/natal', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_interpret_placement' => [ '/interpret/placement', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'planet', '', 's', 1 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_interpret_synastry' => [ '/interpret/synastry', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
	'astroway_interpret_transits' => [ '/interpret/transits', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'transit_date', 'transitDate', 's', 1 ], [ 'transit_time', 'transitTime', 's', 0 ], [ 'transit_tz_offset', 'transitTzOffset', 'n', 0 ], [ 'language', '', 's', 0 ], [ 'disclaimer_inline', '', 'b', 0 ], [ 'lang', '', 's', 4 ] ] ],
];
