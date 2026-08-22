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
	'astroway_chart' => [ '/chart', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'time_unknown', 'timeUnknown', 'b', 0 ] ] ],
	'astroway_ephemeris' => [ '/ephemeris', 'POST', [ [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ], [ 'step_days', 'stepDays', 'n', 0 ], [ 'planet_ids', 'planetIds', 'j', 2 ] ] ],
	'astroway_planets' => [ '/planets', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_sun_times' => [ '/sun-times', 'POST', [ [ 'date', '', 's', 1 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
];
