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
	'label' => 'AI Reports',
	'endpoints' => [
		'astroway_reports_ai_monthly_narrative' => [
			'title' => 'AI Monthly Narrative',
			'description' => 'Single-month forecast. Tighter scope than year-ahead: uses fast and slow planet transits within the month. Inputs: chart, year, month (1-12), language, tone, length.',
			'documented' => true,
		],
		'astroway_reports_ai_natal_narrative' => [
			'title' => 'AI Natal Narrative',
			'description' => 'Long-form natal-chart narrative (markdown). Inputs: chart, language (21 codes), tone (warm/professional/concise), length (short/medium/long; ≤3200 tokens). Returns the narrative text plus model and token usage. AI grounded on the computed natal chart: Sun/Moon/Asc, 13 bodies, 12 houses, ≤25 major aspects.',
			'documented' => true,
		],
		'astroway_reports_ai_synastry_narrative' => [
			'title' => 'AI Synastry Narrative',
			'description' => 'Long-form relationship narrative grounded in cross-chart aspects. Inputs: chart1, chart2, language, tone, length.',
			'documented' => true,
		],
		'astroway_reports_ai_transit_narrative' => [
			'title' => 'AI Transit Narrative (single date)',
			'description' => 'Snapshot transit interpretation for a specific date. Inputs: chart + transitDate (+optional transitTime/tzOffset), language, tone, length. Returns narrative grounded in transit-to-natal aspects (orb ≤1°).',
			'documented' => true,
		],
		'astroway_reports_ai_year_ahead_narrative' => [
			'title' => 'AI Year-Ahead Narrative',
			'description' => 'Long-form annual report. Combines natal context with the year\'s major outer-planet transits clustered by month. Inputs: chart, year (default = next year), language, tone, length (use long for full annual report).',
			'documented' => true,
		],
	],
];
