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
	'astroway_reports_ai_monthly_narrative' => [ '/reports/ai/monthly-narrative', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'year', '', 'n', 1 ], [ 'month', '', 'n', 1 ], [ 'language', '', 's', 0 ], [ 'tone', '', 's', 0 ], [ 'length', '', 's', 0 ] ] ],
	'astroway_reports_ai_natal_narrative' => [ '/reports/ai/natal-narrative', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'tone', '', 's', 0 ], [ 'length', '', 's', 0 ] ] ],
	'astroway_reports_ai_synastry_narrative' => [ '/reports/ai/synastry-narrative', 'POST', [ [ 'chart1', '', 'j', 3 ], [ 'chart2', '', 'j', 3 ], [ 'language', '', 's', 0 ], [ 'tone', '', 's', 0 ], [ 'length', '', 's', 0 ] ] ],
	'astroway_reports_ai_transit_narrative' => [ '/reports/ai/transit-narrative', 'POST', [ [ 'date', '', 's', 0 ], [ 'time', '', 's', 0 ], [ 'timezone_offset', 'timezoneOffset', 'n', 0 ], [ 'ayanamsa_id', 'ayanamsaId', 'n', 0 ], [ 'ayanamsa', '', 's', 0 ] ] ],
	'astroway_reports_ai_year_ahead_narrative' => [ '/reports/ai/year-ahead-narrative', 'POST', [ [ 'chart', '', 'j', 3 ], [ 'year', '', 'n', 0 ], [ 'language', '', 's', 0 ], [ 'tone', '', 's', 0 ], [ 'length', '', 's', 0 ] ] ],
];
