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
	'label' => 'Dignities & Receptions',
	'endpoints' => [
		'astroway_almuten' => [
			'title' => 'Almuten Figuris',
			'description' => 'Calculate the Almuten Figuris: the planet with the highest essential dignity score across the key chart positions.',
			'documented' => true,
		],
		'astroway_disposition_chains' => [
			'title' => 'Disposition Chains',
			'description' => 'Calculate planetary disposition chains: the recursive sequence of sign rulers leading to the final dispositor.',
			'documented' => true,
		],
		'astroway_disposition_chains_layout' => [
			'title' => 'Disposition Chains Layout',
			'description' => 'Return disposition chains with computed x/y layout coordinates for graph visualization.',
			'documented' => true,
		],
		'astroway_essential_dignities' => [
			'title' => 'Essential Dignities',
			'description' => 'Calculate the five Ptolemaic essential dignities (rulership, exaltation, triplicity, term, face) and debilities for each planet.',
			'documented' => true,
		],
		'astroway_hyleg' => [
			'title' => 'Hyleg & Alcocoden',
			'description' => 'Calculate the Hyleg (apheta, giver of life) and Alcocoden (indicator of lifespan) using traditional Hellenistic method.',
			'documented' => true,
		],
		'astroway_receptions' => [
			'title' => 'Mutual Receptions',
			'description' => 'Find mutual receptions between planets: pairs where each planet is in a sign ruled, exalted, or in the dignity of the other.',
			'documented' => true,
		],
	],
];
