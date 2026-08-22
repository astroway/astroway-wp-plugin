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
	'astroway_chinese_feng_shui_annual_stars' => [ '/chinese/feng-shui/annual-stars', 'POST', [ [ 'year', '', 'n', 0 ], [ 'date', '', 's', 0 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'include_monthly', 'includeMonthly', 'b', 0 ] ] ],
	'astroway_chinese_feng_shui_bagua' => [ '/chinese/feng-shui/bagua', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'gender', '', 's', 1 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_feng_shui_flying_star' => [ '/chinese/feng-shui/flying-star', 'POST', [ [ 'facing', '', 'n', 0 ], [ 'facing_mountain', 'facingMountain', 's', 0 ], [ 'period', '', 'n', 0 ], [ 'occupied_date', 'occupiedDate', 's', 0 ], [ 'occupied_time', 'occupiedTime', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'year', '', 'n', 0 ] ] ],
	'astroway_chinese_feng_shui_kua' => [ '/chinese/feng-shui/kua', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'gender', '', 's', 1 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_feng_shui_lucky_directions' => [ '/chinese/feng-shui/lucky-directions', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'gender', '', 's', 1 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_lunar_date' => [ '/chinese/lunar-date', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_chinese_solar_terms' => [ '/chinese/solar-terms', 'POST', [ [ 'year', '', 'n', 1 ] ] ],
	'astroway_chinese_tong_shu' => [ '/chinese/tong-shu', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_chinese_tong_shu_select' => [ '/chinese/tong-shu/select', 'POST', [ [ 'from', '', 's', 1 ], [ 'to', '', 's', 1 ], [ 'activity', '', 's', 1 ], [ 'avoid_clash_with', 'avoidClashWith', 's', 0 ], [ 'include_neutral', 'includeNeutral', 'b', 0 ] ] ],
	'astroway_chinese_zodiac_animal' => [ '/chinese/zodiac/animal', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_zodiac_compatibility' => [ '/chinese/zodiac/compatibility', 'POST', [ [ 'person1', '', 'j', 3 ], [ 'person2', '', 'j', 3 ] ] ],
	'astroway_chinese_zodiac_element' => [ '/chinese/zodiac/element', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_zodiac_inner_animal' => [ '/chinese/zodiac/inner-animal', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
	'astroway_chinese_zodiac_secret_animal' => [ '/chinese/zodiac/secret-animal', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'solar_year', 'solarYear', 'n', 0 ] ] ],
];
