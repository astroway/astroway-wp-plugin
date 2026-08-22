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
	'astroway_render_aspect_grid' => [ '/render/aspect-grid', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_bi_wheel' => [ '/render/bi-wheel', 'POST', [ [ 'natal', '', 'j', 3 ], [ 'outer', '', 'j', 3 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_biorhythm' => [ '/render/biorhythm', 'POST', [ [ 'birth_date', 'birthDate', 's', 1 ], [ 'range_start', 'rangeStart', 's', 1 ], [ 'range_end', 'rangeEnd', 's', 1 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_composite' => [ '/render/composite', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_cosmogram' => [ '/render/cosmogram', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_eclipse_path' => [ '/render/eclipse-path', 'POST', [ [ 'path', '', 'j', 3 ], [ 'centerline_points', 'centerlinePoints', 'j', 2 ], [ 'band_width_deg', 'bandWidthDeg', 'n', 0 ], [ 'title', '', 's', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_moon_phase' => [ '/render/moon-phase', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_star_map' => [ '/render/star-map', 'POST', [ [ 'points', '', 'j', 3 ], [ 'observer_lat_deg', 'observerLatDeg', 'n', 0 ], [ 'zenith_dec_deg', 'zenithDecDeg', 'n', 0 ], [ 'cap_altitude_deg', 'capAltitudeDeg', 'n', 0 ], [ 'title', '', 's', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_timeline' => [ '/render/timeline', 'POST', [ [ 'range_start', 'rangeStart', 's', 1 ], [ 'range_end', 'rangeEnd', 's', 1 ], [ 'events', '', 'j', 3 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_tri_wheel' => [ '/render/tri-wheel', 'POST', [ [ 'natal', '', 'j', 3 ], [ 'middle', '', 'j', 3 ], [ 'outer', '', 'j', 3 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_wheel_vedic_east' => [ '/render/wheel-vedic-east', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_wheel_vedic_north' => [ '/render/wheel-vedic-north', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_wheel_vedic_south' => [ '/render/wheel-vedic-south', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
	'astroway_render_wheel_western' => [ '/render/wheel-western', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'house_system', 'houseSystem', 's', 0 ], [ 'name', '', 's', 0 ], [ 'city', '', 's', 0 ], [ 'zodiac_type', 'zodiacType', 's', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'cosmogram', '', 'b', 0 ], [ 'options', '', 'j', 2 ] ] ],
];
