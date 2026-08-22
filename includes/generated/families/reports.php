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
	'astroway_reports_business' => [ '/reports/business', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_career' => [ '/reports/career', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_child' => [ '/reports/child', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_gemstone' => [ '/reports/gemstone', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'school', '', 's', 0 ], [ 'wearing_from', 'wearingFrom', 's', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_generate' => [ '/reports/generate', 'POST', [ [ 'report_type', '', 's', 1 ], [ 'chart', '', 'j', 2 ], [ 'chart1', '', 'j', 2 ], [ 'chart2', '', 'j', 2 ], [ 'year', '', 'n', 0 ], [ 'spread', '', 's', 0 ], [ 'seed', '', 'n', 0 ], [ 'name', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_history' => [ '/reports/history', 'GET', [  ] ],
	'astroway_reports_human_design' => [ '/reports/human-design', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_lal_kitab' => [ '/reports/lal-kitab', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_love' => [ '/reports/love', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_money' => [ '/reports/money', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_muhurta' => [ '/reports/muhurta', 'POST', [ [ 'activity', '', 's', 1 ], [ 'search_window_start', '', 's', 1 ], [ 'search_window_end', '', 's', 0 ], [ 'latitude', '', 'n', 1 ], [ 'longitude', '', 'n', 1 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ], [ 'top_n', 'topN', 'n', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_natal' => [ '/reports/natal', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ], [ 'enrich', '', 'b', 0 ], [ 'tone', '', 's', 0 ], [ 'length', '', 's', 0 ] ] ],
	'astroway_reports_relocation' => [ '/reports/relocation', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'locations', '', 'j', 3 ], [ 'categories', '', 'j', 2 ], [ 'orb_km', 'orbKm', 'n', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_stellaforge' => [ '/reports/stellaforge', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'style', '', 's', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_synastry' => [ '/reports/synastry', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_tarot' => [ '/reports/tarot', 'POST', [ [ 'spread', '', 's', 0 ], [ 'seed', '', 'n', 0 ], [ 'name', '', 's', 0 ], [ 'allow_reversed', 'allowReversed', 'b', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_transit_yearly' => [ '/reports/transit-yearly', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'year', '', 'n', 0 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
	'astroway_reports_vedic_kundli' => [ '/reports/vedic-kundli', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'whitelabel', '', 's', 0 ] ] ],
];
