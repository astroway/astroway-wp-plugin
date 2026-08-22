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
	'astroway_wellness_biorhythm' => [ '/wellness/biorhythm', 'POST', [ [ 'birth_date', 'birthDate', 's', 1 ], [ 'range_start', 'rangeStart', 's', 0 ], [ 'range_end', 'rangeEnd', 's', 0 ] ] ],
	'astroway_wellness_crystals' => [ '/wellness/crystals', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_cycle' => [ '/wellness/cycle', 'POST', [ [ 'birth_date', 'birthDate', 's', 1 ], [ 'target_date', 'targetDate', 's', 0 ] ] ],
	'astroway_wellness_diet' => [ '/wellness/diet', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_exercise' => [ '/wellness/exercise', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_herbs' => [ '/wellness/herbs', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_medical_astrology' => [ '/wellness/medical-astrology', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_mental_health' => [ '/wellness/mental-health', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
	'astroway_wellness_sleep_cycles' => [ '/wellness/sleep-cycles', 'POST', [ [ 'date', '', 's', 1 ] ] ],
	'astroway_wellness_yoga' => [ '/wellness/yoga', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ] ] ],
];
