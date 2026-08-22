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
	'label' => 'Esoteric',
	'endpoints' => [
		'astroway_djamaspa' => [
			'title' => 'Djamaspa (DEPRECATED: RED quality, sunset 2027-06-15)',
			'description' => 'DEPRECATED: RED quality (oral Zoroastrian tradition, scattered manuscripts, no canonical reference). Still works until its 2027-06-15 sunset (12-month, per the /v1 stability policy), then will be removed. Migrate to broader /reference endpoints or remove the dependency. Calculate Djamaspa planetary positions for date.',
			'documented' => true,
		],
		'astroway_esoteric_angel_numbers' => [
			'title' => 'Angel Numbers: Catalogue',
			'description' => 'Full angel number catalogue (singles, masters, repeating, mirror, sequential patterns) with short and full meanings, themes.',
			'documented' => true,
		],
		'astroway_esoteric_angel_numbers_by_life_path' => [
			'title' => 'Angel Numbers for Life Path',
			'description' => 'Angel numbers aligned to a given life-path number (1-9, 11, 22, 33). Useful to identify recurring numerical signatures aligned with one\'s soul path.',
			'documented' => true,
		],
		'astroway_esoteric_angel_numbers_decode' => [
			'title' => 'Decode Angel Number',
			'description' => 'Decode a numeric sequence in optional life context. Returns pattern classification, exact match, and reduced single-digit meaning.',
			'documented' => true,
		],
		'astroway_esoteric_angel_numbers_today' => [
			'title' => 'Daily Angel Number',
			'description' => 'Compute today\'s personal angel number from date (digit-sum reduced to single digit). Optional `?date=YYYY-MM-DD` query.',
			'documented' => true,
		],
		'astroway_esoteric_angel_numbers_get' => [
			'title' => 'Angel Number Lookup',
			'description' => 'Look up the meaning of a specific angel number sequence. Falls back to numerological reduction if not in catalogue.',
			'documented' => true,
		],
		'astroway_esoteric_crystals' => [
			'title' => 'Crystals: Full Directory',
			'description' => 'Reference list of crystals with chakra, zodiac, planet, element, hardness, and purpose tags. Returns chakra and purpose taxonomies for filtering.',
			'documented' => true,
		],
		'astroway_esoteric_crystals_by_chakra' => [
			'title' => 'Crystals by Chakra',
			'description' => 'Crystals indexed for a given chakra (Root / Sacral / Solar Plexus / Heart / Throat / Third Eye / Crown).',
			'documented' => true,
		],
		'astroway_esoteric_crystals_by_purpose' => [
			'title' => 'Crystals by Purpose',
			'description' => 'Crystals indexed by intent keyword (love, prosperity, healing, protection, etc.). Substring-matches against purpose tags.',
			'documented' => true,
		],
		'astroway_esoteric_crystals_by_zodiac' => [
			'title' => 'Crystals by Zodiac Sign',
			'description' => 'Crystals indexed for a given zodiac sign (case-insensitive English name).',
			'documented' => true,
		],
		'astroway_esoteric_crystals_recommend' => [
			'title' => 'Crystal Recommendations',
			'description' => 'Recommend crystals scored against natal sign placements (Sun/Moon/Asc) and intent keywords. Returns top-N matches with score.',
			'documented' => true,
		],
		'astroway_esoteric_dreams' => [
			'title' => 'Dream Symbol Dictionary',
			'description' => 'Full dream symbol dictionary with category, element, archetype, common and shadow meanings, and recurring-dream readings.',
			'documented' => true,
		],
		'astroway_esoteric_dreams_by_element' => [
			'title' => 'Dream Symbols by Element',
			'description' => 'Dream symbols indexed by element (fire / earth / air / water / spirit).',
			'documented' => true,
		],
		'astroway_esoteric_dreams_decode' => [
			'title' => 'Decode Dream Text',
			'description' => 'Scan dream narrative text for known symbols. Returns matched symbols with their meanings (recurring or single-event).',
			'documented' => true,
		],
		'astroway_esoteric_dreams_recurring_themes' => [
			'title' => 'Recurring Dream Themes',
			'description' => 'Catalogue of common recurring dream themes (falling, naked in public, losing teeth, being chased) with universal psychological meanings.',
			'documented' => true,
		],
		'astroway_esoteric_dreams_symbol' => [
			'title' => 'Dream Symbol Lookup',
			'description' => 'Look up a dream symbol by keyword. Exact match if available, otherwise partial substring matches.',
			'documented' => true,
		],
		'astroway_iching' => [
			'title' => 'I Ching Hexagram (DEPRECATED: use /iching/throw-coins)',
			'description' => 'DEPRECATED: legacy un-namespaced random hexagram cast. Superseded by the /iching/* namespace: `/iching/throw-coins` (seeded + reproducible), `/iching/by-question`, `/iching/with-changing-lines`, `/iching/daily`, `/iching/lookup/{n}`, all on the Wilhelm-Baynes hexagram set. Still works until its 2027-06-16 sunset, then removed. Casts a single I Ching hexagram (input ignored).',
			'documented' => false,
		],
	],
];
