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
	'astroway_antiscia' => [ '/antiscia', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_arabic_parts' => [ '/arabic-parts', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'parts', '', 'j', 2 ] ] ],
	'astroway_aspect_bar' => [ '/aspect-bar', 'POST', [ [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ], [ 'step_days', 'stepDays', 'n', 0 ], [ 'planet1_ids', 'planet1Ids', 'j', 2 ], [ 'planet2_ids', 'planet2Ids', 'j', 2 ], [ 'aspect_angles', 'aspectAngles', 'j', 2 ], [ 'max_orb', 'maxOrb', 'n', 0 ], [ 'visible_planet_ids', 'visiblePlanetIds', 'j', 2 ] ] ],
	'astroway_aspect_timeline' => [ '/aspect-timeline', 'POST', [ [ 'start_date', 'startDate', 's', 1 ], [ 'end_date', 'endDate', 's', 1 ], [ 'step_days', 'stepDays', 'n', 0 ], [ 'planet1_ids', 'planet1Ids', 'j', 2 ], [ 'planet2_ids', 'planet2Ids', 'j', 2 ], [ 'aspect_angles', 'aspectAngles', 'j', 2 ], [ 'max_orb', 'maxOrb', 'n', 0 ] ] ],
	'astroway_fixed_stars' => [ '/fixed-stars', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_fixed_stars_catalog' => [ '/fixed-stars/catalog', 'GET', [  ] ],
	'astroway_gauquelin_sectors' => [ '/gauquelin-sectors', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_midpoint_trees' => [ '/midpoint-trees', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'orb', '', 'n', 0 ] ] ],
	'astroway_midpoints' => [ '/midpoints', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_parallel_aspects' => [ '/parallel-aspects', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'orb', '', 'n', 0 ] ] ],
	'astroway_sabian_symbols' => [ '/sabian-symbols', 'POST', [ [ 'longitudes', '', 'j', 3 ] ] ],
];
