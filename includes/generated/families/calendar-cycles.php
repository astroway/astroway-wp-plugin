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
	'astroway_algol_minimum' => [ '/algol-minimum', 'POST', [ [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ], [ 'longitude', '', 'n', 0 ] ] ],
	'astroway_algol_minimum_nearest' => [ '/algol-minimum/nearest', 'POST', [ [ 'date', '', 's', 1 ], [ 'longitude', '', 'n', 0 ] ] ],
	'astroway_aspects' => [ '/aspects', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_cyclic_index' => [ '/cyclic-index', 'POST', [ [ 'start_year', 'startYear', 'n', 1 ], [ 'end_year', 'endYear', 'n', 1 ], [ 'pairs', '', 's', 0 ] ] ],
	'astroway_eclipses' => [ '/eclipses', 'POST', [ [ 'year', '', 'n', 1 ], [ 'years_range', 'yearsRange', 'n', 0 ] ] ],
	'astroway_houses' => [ '/houses', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_ingresses' => [ '/ingresses', 'POST', [ [ 'planet_id', 'planetId', 'n', 1 ], [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ] ] ],
	'astroway_lunar_calendar' => [ '/lunar-calendar', 'POST', [ [ 'year', '', 'n', 1 ], [ 'month', '', 'n', 1 ] ] ],
	'astroway_moon_aspects' => [ '/moon-aspects', 'POST', [ [ 'year', '', 'n', 1 ], [ 'month', '', 'n', 1 ] ] ],
	'astroway_moon_voc' => [ '/moon-voc', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'range_days', 'rangeDays', 'n', 0 ] ] ],
	'astroway_planetary_cycles' => [ '/planetary-cycles', 'POST', [ [ 'planet1_id', 'planet1Id', 'n', 1 ], [ 'planet2_id', 'planet2Id', 'n', 1 ], [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ] ] ],
	'astroway_planetary_hours' => [ '/planetary-hours', 'POST', [ [ 'date', '', 's', 1 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_planetary_phases' => [ '/planetary-phases', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_retrograde_periods' => [ '/retrograde-periods', 'POST', [ [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ], [ 'planet_ids', 'planetIds', 'j', 2 ] ] ],
];
