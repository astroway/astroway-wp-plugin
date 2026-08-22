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
	'astroway_bazi_day_master' => [ '/bazi/day-master', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_element_balance' => [ '/bazi/element-balance', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_four_pillars' => [ '/bazi/four-pillars', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_hour_pillar' => [ '/bazi/hour-pillar', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_luck_pillars' => [ '/bazi/luck-pillars', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'gender', '', 's', 1 ], [ 'count', '', 'n', 0 ] ] ],
	'astroway_bazi_month_pillar' => [ '/bazi/month-pillar', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_monthly' => [ '/bazi/monthly', 'POST', [ [ 'natal_date', 'natalDate', 's', 1 ], [ 'natal_time', 'natalTime', 's', 0 ], [ 'natal_tz_offset', 'natalTzOffset', 'n', 0 ], [ 'target_year', 'targetYear', 'n', 1 ], [ 'target_month', 'targetMonth', 'n', 1 ] ] ],
	'astroway_bazi_ten_gods' => [ '/bazi/ten-gods', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_year_pillar' => [ '/bazi/year-pillar', 'POST', [ [ 'date', '', 's', 1 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ] ] ],
	'astroway_bazi_year_pillar_decade' => [ '/bazi/year-pillar-decade', 'POST', [ [ 'start_year', 'startYear', 'n', 1 ] ] ],
	'astroway_bazi_yearly' => [ '/bazi/yearly', 'POST', [ [ 'natal_date', 'natalDate', 's', 1 ], [ 'natal_time', 'natalTime', 's', 0 ], [ 'natal_tz_offset', 'natalTzOffset', 'n', 0 ], [ 'target_year', 'targetYear', 'n', 1 ] ] ],
];
