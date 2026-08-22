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
	'label' => 'Comparisons',
	'endpoints' => [
		'astroway_coalescent' => [
			'title' => 'Coalescent Chart',
			'description' => 'Calculate a coalescent chart: the harmonic chart that resonates most strongly between two charts.',
			'documented' => true,
		],
		'astroway_composite' => [
			'title' => 'Composite Chart',
			'description' => 'Calculate a midpoint composite chart by averaging the planetary positions and house cusps of two natal charts.',
			'documented' => true,
		],
		'astroway_davison' => [
			'title' => 'Davison Chart',
			'description' => 'Calculate a Davison relationship chart using the midpoint in time and space between two birth moments.',
			'documented' => true,
		],
		'astroway_group_synastry' => [
			'title' => 'Group Synastry',
			'description' => 'Calculate synastry aspects among 2–8 charts simultaneously, returning all pairwise cross-chart aspect matrices.',
			'documented' => true,
		],
		'astroway_match_score' => [
			'title' => 'Match Score (dating compatibility)',
			'description' => 'One-call dating-compatibility aggregate over the synastry engine: overall score (0-100) + label + harmony/tension, the attraction score (Venus-Mars / Moon-Venus / 5th-house), the most influential cross-aspects, and green/red flags surfaced from those aspects. Built for dating apps that want a single call instead of synastry + attraction + their own flag logic. Same TwoChart input as /synastry.',
			'documented' => true,
		],
		'astroway_synastry_aspect_grid' => [
			'title' => 'Synastry Aspect Grid',
			'description' => 'NxM matrix of cross-chart aspects between the two charts (default 13×13: Sun..Pluto + nodes + Lilith + Chiron). Cells contain aspect or null.',
			'documented' => true,
		],
		'astroway_synastry_attraction_score' => [
			'title' => 'Synastry Attraction Score',
			'description' => 'Weighted 0–100 attraction score from Sun-Moon, Mars-Venus, ASC/DSC, Mars-Mars, Sun-Mars, Moon-Venus contacts and 5th-house overlay.',
			'documented' => true,
		],
		'astroway_synastry_element_balance' => [
			'title' => 'Synastry Element Balance',
			'description' => 'Element (fire/earth/air/water) and modality (cardinal/fixed/mutable) tallies for each chart and the combined pair, with dominant and missing elements.',
			'documented' => true,
		],
		'astroway_synastry_house_overlay' => [
			'title' => 'Synastry House Overlay',
			'description' => 'Locate each chart’s personal planets in the partner’s houses; reports top-3 emphasized houses on either side.',
			'documented' => true,
		],
	],
];
