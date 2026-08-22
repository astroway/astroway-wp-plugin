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
	'label' => 'Kabbalah',
	'endpoints' => [
		'astroway_kabbalah_gematria' => [
			'title' => 'Gematria ciphers',
			'description' => 'Seven standard ciphers over a Hebrew text: absolute, large, small, ordinal, inclusive, AtBash and AlBam. Vowel points and cantillation are stripped; a final letter counts as its ordinary form in the absolute value and as 500-900 in the large one, and both are returned rather than one being chosen.',
			'documented' => true,
		],
		'astroway_kabbalah_sephiroth' => [
			'title' => 'The ten sephirot',
			'description' => 'The Tree of Life as a reference table: Hebrew, meaning, pillar, triad and the Golden Dawn planetary attribution, grouped by pillar. Nothing here is computed, and the response says so.',
			'documented' => true,
		],
		'astroway_kabbalah_shem_names' => [
			'title' => 'The seventy-two names (Shem HaMephorash)',
			'description' => 'The 72 three-letter names, computed from Exodus 14:19-21 rather than read from a table, each with its five degrees of the zodiac. Filter with ?index=1..72 or ?longitude=0..360.',
			'documented' => true,
		],
	],
];
