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
	'label' => 'Vedic',
	'endpoints' => [
		'astroway_ashtakavarga' => [
			'title' => 'Ashtakavarga',
			'description' => 'Calculate the Ashtakavarga benefic point table for a Vedic chart: individual bindus for each planet in each sign and the total Sarvashtakavarga.',
			'documented' => true,
		],
		'astroway_muhurta_types' => [
			'title' => 'Muhurat: activity catalogue',
			'description' => 'List the 12 supported muhurat activities (key + Sanskrit name + one-line purpose) so a client can discover them without hard-coding. Free metadata read, no calculation.',
			'documented' => true,
		],
		'astroway_nakshatras' => [
			'title' => 'Nakshatras',
			'description' => 'Calculate the Nakshatra (lunar mansion) for each planet using the sidereal zodiac. Returns nakshatra name, pada, deity, and quality.',
			'documented' => true,
		],
		'astroway_vedic_bhavabala' => [
			'title' => 'Bhava Bala: house strength',
			'description' => 'Strength of the twelve houses per BPHS Adhyaya 27: Bhavadhipati Bala (the Shadbala of the house lord), Bhava Digbala (the four rasi classes, each strong in one direction), and Bhava Drishti Bala (net benefic minus malefic aspect on the bhava). Components returned separately, plus rank, strongest and weakest house.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_ashtakoot' => [
			'title' => 'Compatibility: Ashtakoot Guna Milan (8-fold 36-point)',
			'description' => 'Canonical 8-Kuta matchmaking out of 36 points: Varna(1) + Vashya(2) + Tara(3) + Yoni(4) + Graha-Maitri(5) + Gana(6) + Bhakoot(7) + Nadi(8). Threshold 18+ traditionally acceptable; 24+ good; 32+ excellent. Returns per-Kuta scores + Bhakoot/Nadi dosha flags + threshold verdict.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_bhrigu_match' => [
			'title' => 'Compatibility: Bhrigu-match (7H placement)',
			'description' => 'Bhrigu Sanhita-style structured 7th-house planetary placement summary for both partners. Counts benefic/malefic planets in each partner\'s 7H from Lagna and labels status {beneficial|challenging|neutral}. NOT a numerical match score.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_dashakoota' => [
			'title' => 'Compatibility: Dashakoota (10-fold 39-point)',
			'description' => 'Extended matchmaking adding Mahendra(2) + Vedha(1) on top of Ashtakoot 36 = 39 max. Mahendra: birth-star count 4/7/10/13/16/19/22/25 → 2pt; else 0. Vedha: 13 canonical mutually-obstructing nakshatra pairs → 0pt blocked, 1pt clear.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_full' => [
			'title' => 'Compatibility: Parashara full report',
			'description' => 'Combined matchmaking response: Ashtakoot total + threshold + Manglik check for both partners + per-Kuta sub-scores + recommendation in one call.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_mangal_match' => [
			'title' => 'Compatibility: Mangal-match (Manglik between partners)',
			'description' => 'Compares Manglik status of both partners and applies BPHS A.39 cancellation rule: if both partners are Manglik, the dosha is mutually cancelled. Returns verdict {compatible|cancelled|incompatible}.',
			'documented' => true,
		],
		'astroway_vedic_compatibility_manglik_check' => [
			'title' => 'Compatibility: Manglik check (single chart)',
			'description' => 'Detects Manglik status for a single chart with school selection (strict/north/south, default south). Same canon as `/vedic/doshas/parashara/mangal`; surfaced here in compatibility context for matchmaking flows.',
			'documented' => true,
		],
		'astroway_vedic_dashas_ashtottari_antar' => [
			'title' => 'Dashas: Ashtottari Antardasha',
			'description' => 'Ashtottari Antardasha: 8 sub-periods of the running MD at `targetDate` (default today UTC). Sub-period of planet Q within MD of P: `years = (P.years × Q.years) / 108`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_ashtottari_maha' => [
			'title' => 'Dashas: Ashtottari Mahadasha',
			'description' => '108-year Ashtottari Dasha (Ardradi tradition). 8 planets (Sun/Moon/Mars/Mercury/Saturn/Jupiter/Rahu/Venus) with periods 6/15/8/17/10/19/12/21 (total 108y), Ketu excluded. Mapping is block-based (4-3-4-3-3-3-4-3 nakshatras anchored at Ardra). Returns applicability flag per BPHS 46.23 (day Krishna OR night Shukla).',
			'documented' => true,
		],
		'astroway_vedic_dashas_ashtottari_prana' => [
			'title' => 'Dashas: Ashtottari Pranadasha',
			'description' => 'Ashtottari Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_ashtottari_pratyantar' => [
			'title' => 'Dashas: Ashtottari Pratyantardasha',
			'description' => 'Ashtottari Pratyantardasha: 3-level cascade (MD → AD → 8 PDs).',
			'documented' => true,
		],
		'astroway_vedic_dashas_ashtottari_sookshma' => [
			'title' => 'Dashas: Ashtottari Sookshmadasha',
			'description' => 'Ashtottari Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_chara_antar' => [
			'title' => 'Dashas: Chara Antardasha',
			'description' => 'Chara Antardasha: 12 sub-periods of the running Mahadasha at `targetDate` (default today UTC). Equal-share subdivision: each antar = parent_years / 12. Order: parent\'s NEXT sign first (in parent direction), parent sign LAST (per K.N. Rao).',
			'documented' => true,
		],
		'astroway_vedic_dashas_chara_maha' => [
			'title' => 'Dashas: Chara Mahadasha',
			'description' => 'Chara Dasha (Jaimini rasi-dasha: Mahadasha lord = sign, not planet). 12 Mahadashas of variable duration starting at lagna sign. Direction = forward for movable+dual signs (Aries/Cancer/Libra/Capricorn/Gemini/Virgo/Sagittarius/Pisces); reverse for fixed (Taurus/Leo/Scorpio/Aquarius). Per-sign duration = inclusive count from sign to its lord (in direction) minus 1; lord-in-own-sign → 12 years. Co-lo',
			'documented' => true,
		],
		'astroway_vedic_dashas_chara_prana' => [
			'title' => 'Dashas: Chara Pranadasha',
			'description' => 'Chara Pranadasha: 5-level cascade (finest grain). Minute-scale duration at full depth.',
			'documented' => true,
		],
		'astroway_vedic_dashas_chara_pratyantar' => [
			'title' => 'Dashas: Chara Pratyantardasha',
			'description' => 'Chara Pratyantardasha: 3-level cascade (MD → AD → 12 PDs). Recursive equal-share subdivision; same direction at every depth.',
			'documented' => true,
		],
		'astroway_vedic_dashas_chara_sookshma' => [
			'title' => 'Dashas: Chara Sookshmadasha',
			'description' => 'Chara Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_kalachakra_antar' => [
			'title' => 'Dashas: Kalachakra Antardasha',
			'description' => 'Kalachakra Antardasha: 9 sub-periods of the running MD at `targetDate` (default today UTC). Same chakra-row at every depth; sub-period of sign Q within MD of P: `years = (P.years × Q.years) / paramayu`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_kalachakra_maha' => [
			'title' => 'Dashas: Kalachakra Mahadasha',
			'description' => 'Kalachakra Dasha (rasi-dasha: Mahadasha lords are signs, not planets). 8 chakra-rows (Savya×4 + Apasavya×4); direction determined by nakshatra group (Aswini/Bharani/Krittika = Savya; Rohini/Mrigasira/Ardra = Apasavya). Total cycle (paramayu) varies per natal pada: 100/85/83/86 years. Returns the 9 Mahadashas of the running cycle from birth; first MD truncated by elapsed pada-fraction.',
			'documented' => true,
		],
		'astroway_vedic_dashas_kalachakra_prana' => [
			'title' => 'Dashas: Kalachakra Pranadasha',
			'description' => 'Kalachakra Pranadasha: 5-level cascade (finest grain, minutes-scale at full depth).',
			'documented' => true,
		],
		'astroway_vedic_dashas_kalachakra_pratyantar' => [
			'title' => 'Dashas: Kalachakra Pratyantardasha',
			'description' => 'Kalachakra Pratyantardasha: 3-level cascade (MD → AD → 9 PDs). Recursion preserves the natal chakra-row and paramayu.',
			'documented' => true,
		],
		'astroway_vedic_dashas_kalachakra_sookshma' => [
			'title' => 'Dashas: Kalachakra Sookshmadasha',
			'description' => 'Kalachakra Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shatabdika_antar' => [
			'title' => 'Dashas: Shatabdika Antardasha',
			'description' => 'Shatabdika Antardasha: 7 sub-periods of the running MD at `targetDate`. Recursive proportional split (parent_years × sub_planet_period / 100).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shatabdika_maha' => [
			'title' => 'Dashas: Shatabdika Mahadasha',
			'description' => 'Shatabdika Dasha: 100-year nakshatra dasha cycle (BPHS Adhyaya 46 group). 7 planets (no shadow planets), seed nakshatra = Revati (27). Sequence: Sun(5)→Moon(5)→Venus(10)→Mercury(10)→Jupiter(20)→Mars(20)→Saturn(30). Distribution: 6 planets get 4 nakshatras each, Saturn gets 3. Standard nakshatra-fraction × period balance rule. Algorithm port of PyJHora sataatbika.py.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shatabdika_prana' => [
			'title' => 'Dashas: Shatabdika Pranadasha',
			'description' => 'Shatabdika Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shatabdika_pratyantar' => [
			'title' => 'Dashas: Shatabdika Pratyantardasha',
			'description' => 'Shatabdika Pratyantardasha: 3-level cascade (MD → AD → 7 PDs).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shatabdika_sookshma' => [
			'title' => 'Dashas: Shatabdika Sookshmadasha',
			'description' => 'Shatabdika Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shodashottari_antar' => [
			'title' => 'Dashas: Shodashottari Antardasha',
			'description' => 'Shodashottari Antardasha: 8 sub-periods of the running MD at `targetDate`. Recursive proportional split (parent_years × sub_planet_period / 116).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shodashottari_maha' => [
			'title' => 'Dashas: Shodashottari Mahadasha',
			'description' => 'Shodashottari Dasha: 116-year nakshatra dasha cycle (BPHS Adhyaya 46 group). 8 planets (Rahu excluded, Ketu included), seed nakshatra = Pushya (8). Sequence: Sun(11)→Mars(12)→Jupiter(13)→Saturn(14)→Ketu(15)→Moon(16)→Mercury(17)→Venus(18). Distribution: 3 planets get 4 nakshatras, 5 get 3. Per AmatyaKaraka tradition: applicable when lagna in Chandra hora during Krishna paksha OR Surya hora during S',
			'documented' => true,
		],
		'astroway_vedic_dashas_shodashottari_prana' => [
			'title' => 'Dashas: Shodashottari Pranadasha',
			'description' => 'Shodashottari Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shodashottari_pratyantar' => [
			'title' => 'Dashas: Shodashottari Pratyantardasha',
			'description' => 'Shodashottari Pratyantardasha: 3-level cascade (MD → AD → 8 PDs).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shodashottari_sookshma' => [
			'title' => 'Dashas: Shodashottari Sookshmadasha',
			'description' => 'Shodashottari Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shoola_antar' => [
			'title' => 'Dashas: Shoola Antardasha',
			'description' => 'Shoola Antardasha: sub-periods of running MD at `targetDate` per chosen `antardasaSeedOption`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shoola_maha' => [
			'title' => 'Dashas: Shoola Mahadasha',
			'description' => 'Shoola Dasha: Jaimini "Trident" rasi-dasha. Seed = stronger_rasi(asc, asc+6) by default (`houseIndex=1`; can be 1..12 to shift the lagna anchor). MD = 12 signs forward, 9 years each. Sub-period antara-seed `option=2` by default (stronger_rasi of parent vs parent+6); option 1 = sign of lord(parent), option 3 = sign of lord(stronger). Children: equal 12-fold split, forward from antara seed.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shoola_prana' => [
			'title' => 'Dashas: Shoola Pranadasha',
			'description' => 'Shoola Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_shoola_pratyantar' => [
			'title' => 'Dashas: Shoola Pratyantardasha',
			'description' => 'Shoola Pratyantardasha: 3-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_shoola_sookshma' => [
			'title' => 'Dashas: Shoola Sookshmadasha',
			'description' => 'Shoola Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_sthira_antar' => [
			'title' => 'Dashas: Sthira Antardasha',
			'description' => 'Sthira Antardasha: equal-split sub-periods of running MD at `targetDate`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_sthira_maha' => [
			'title' => 'Dashas: Sthira Mahadasha',
			'description' => 'Sthira Dasha: Jaimini fixed rasi-dasha. Seed = sign of Brahma planet (PyJHora `house.brahma`: stronger of asc vs 7th → top-2 lords of 6/8/12 from stronger rasi → strongest by 6 Jaimini rasi-rules). MD walks 12 signs forward; per-sign duration 7y movable / 8y fixed / 9y dual. Sub-periods: equal 12-fold split, forward from parent. Year basis 365.256364d (sidereal year, PyJHora canon).',
			'documented' => true,
		],
		'astroway_vedic_dashas_sthira_prana' => [
			'title' => 'Dashas: Sthira Pranadasha',
			'description' => 'Sthira Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_sthira_pratyantar' => [
			'title' => 'Dashas: Sthira Pratyantardasha',
			'description' => 'Sthira Pratyantardasha: 3-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_sthira_sookshma' => [
			'title' => 'Dashas: Sthira Sookshmadasha',
			'description' => 'Sthira Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_tribhagi_antar' => [
			'title' => 'Dashas: Tribhagi Antardasha',
			'description' => 'Tribhagi Antardasha: 9 sub-periods of the running MD at `targetDate`. Recursive proportional split (parent_years × sub_planet_period / 40).',
			'documented' => true,
		],
		'astroway_vedic_dashas_tribhagi_maha' => [
			'title' => 'Dashas: Tribhagi Mahadasha',
			'description' => 'Tribhagi Dasha: 1/3-scale variant of Vimshottari (40-year cycle). Same 9-planet sequence (Ketu→Venus→Sun→Moon→Mars→Rahu→Jupiter→Saturn→Mercury) and same nakshatra-mapping rule, all periods × (1/3): Ketu 7/3, Venus 20/3, Sun 2, Moon 10/3, etc. Useful when finer-grain timing is needed within a Vimshottari-equivalent span. Returns the running cycle of 9 mahadashas from the chart\'s initial-balance-adj',
			'documented' => true,
		],
		'astroway_vedic_dashas_tribhagi_prana' => [
			'title' => 'Dashas: Tribhagi Pranadasha',
			'description' => 'Tribhagi Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_tribhagi_pratyantar' => [
			'title' => 'Dashas: Tribhagi Pratyantardasha',
			'description' => 'Tribhagi Pratyantardasha: 3-level cascade (MD → AD → 9 PDs).',
			'documented' => true,
		],
		'astroway_vedic_dashas_tribhagi_sookshma' => [
			'title' => 'Dashas: Tribhagi Sookshmadasha',
			'description' => 'Tribhagi Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_vimshottari_antar' => [
			'title' => 'Dashas: Vimshottari Antardasha',
			'description' => 'Antardasha (sub-period) within the running Mahadasha. Identifies which MD is active at `targetDate` (default today UTC), then returns the 9 ADs covering that MD. Sub-period of planet Q within MD of P: `years = (P.years × Q.years) / 120`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_vimshottari_maha' => [
			'title' => 'Dashas: Vimshottari Mahadasha',
			'description' => 'Canonical Vimshottari Mahadasha sequence: 9 planets (Ketu/Venus/Sun/Moon/Mars/Rahu/Jupiter/Saturn/Mercury), 120-year total cycle, starts from the lord of Moon\'s nakshatra at birth. First period is truncated by elapsed fraction within Moon\'s nakshatra.',
			'documented' => true,
		],
		'astroway_vedic_dashas_vimshottari_prana' => [
			'title' => 'Dashas: Vimshottari Pranadasha',
			'description' => 'Pranadasha (5th-level Dasha, the finest grain). 5-level cascade. Each Prana sub-period is typically a few hours to a few days.',
			'documented' => true,
		],
		'astroway_vedic_dashas_vimshottari_pratyantar' => [
			'title' => 'Dashas: Vimshottari Pratyantardasha',
			'description' => 'Pratyantardasha (sub-sub-period) within the running Antardasha. 3-level cascade: find current MD → AD → return 9 PDs of that AD.',
			'documented' => true,
		],
		'astroway_vedic_dashas_vimshottari_sookshma' => [
			'title' => 'Dashas: Vimshottari Sookshmadasha',
			'description' => 'Sookshma (4th-level Dasha) within the running Pratyantardasha. 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_dashas_yogini_antar' => [
			'title' => 'Dashas: Yogini Antardasha',
			'description' => 'Yogini Antardasha: 8 sub-periods of the running MD at `targetDate` (default today UTC). Sub-period of yogini Q within MD of P: `years = (P.years × Q.years) / 36`.',
			'documented' => true,
		],
		'astroway_vedic_dashas_yogini_maha' => [
			'title' => 'Dashas: Yogini Mahadasha',
			'description' => '36-year Yogini Dasha. 8 yoginis (Mangala/Pingala/Dhanya/Bhramari/Bhadrika/Ulka/Siddha/Sankata) ruled by Moon/Sun/Jupiter/Mars/Mercury/Saturn/Venus/Rahu with periods 1/2/3/4/5/6/7/8 (total 36). Starts from yogini-of-Moon-nakshatra at birth, first MD truncated by elapsed nakshatra fraction.',
			'documented' => true,
		],
		'astroway_vedic_dashas_yogini_prana' => [
			'title' => 'Dashas: Yogini Pranadasha',
			'description' => 'Yogini Pranadasha: 5-level cascade (finest grain).',
			'documented' => true,
		],
		'astroway_vedic_dashas_yogini_pratyantar' => [
			'title' => 'Dashas: Yogini Pratyantardasha',
			'description' => 'Yogini Pratyantardasha: 3-level cascade (MD → AD → 8 PDs).',
			'documented' => true,
		],
		'astroway_vedic_dashas_yogini_sookshma' => [
			'title' => 'Dashas: Yogini Sookshmadasha',
			'description' => 'Yogini Sookshmadasha: 4-level cascade.',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_full' => [
			'title' => 'Doshas: KP full summary',
			'description' => 'Composite KP dosha summary: manglik + kalasarpa + pitra + kemadruma + Sade-Sati pointer. (Sade Sati requires an explicit targetDate; call /sade-sati separately.)',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_kalasarpa' => [
			'title' => 'Doshas: KP Kalasarpa',
			'description' => 'Kalasarpa dosha with KP Rahu sub-lord chain attached.',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_kemadruma' => [
			'title' => 'Doshas: KP Kemadruma',
			'description' => 'Kemadruma yoga: no planet (excl. Sun, Rahu, Ketu) in 2nd or 12th from Moon. Moon-isolation flag, emotional/financial volatility indicator. KP Moon sub-lord chain attached.',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_manglik' => [
			'title' => 'Doshas: KP Manglik',
			'description' => 'Manglik dosha with KP sub-lord precision attached. Sub-lord chain of Mars added for transit-trigger analysis.',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_pitra' => [
			'title' => 'Doshas: KP Pitra',
			'description' => 'Pitra dosha (Sun affliction) with KP Sun sub-lord chain.',
			'documented' => true,
		],
		'astroway_vedic_doshas_kp_sade_sati' => [
			'title' => 'Doshas: KP Sade Sati',
			'description' => 'Sade Sati state at `targetDate`: Saturn transit through 12th/1st/2nd from natal Moon (7.5y total). Returns the active phase with KP Saturn sub-lord chain.',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_full' => [
			'title' => 'Doshas: Lal Kitab full summary',
			'description' => 'Composite Lal Kitab dosha summary: manglik + kalsarpa + pitra + shrapit + 6 Rin + kismat score.',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_kalsarpa' => [
			'title' => 'Doshas: Lal Kitab Kalsarpa',
			'description' => 'Kalsarpa dosha (Rahu-Ketu encirclement) with Lal Kitab remedies.',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_manglik' => [
			'title' => 'Doshas: Lal Kitab Manglik',
			'description' => 'Manglik dosha per Lal Kitab: Mars in 1/4/7/8/12 with LK-specific cancellation (Mars in Aries/Scorpio/Gemini cancels).',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_pitra' => [
			'title' => 'Doshas: Lal Kitab Pitra',
			'description' => 'Pitri Rin (paternal-debt dosha) per Lal Kitab patterns. Triggers + remedy.',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_rin' => [
			'title' => 'Doshas: Lal Kitab Rin (6 ancestral debts)',
			'description' => 'Aggregate of all 6 Rin (Pitri/Stree/Kanya/Atma/Rishi/Daiva) with active count. Same engine as /lal-kitab/debts but framed as dosha.',
			'documented' => true,
		],
		'astroway_vedic_doshas_lal_kitab_shrapit' => [
			'title' => 'Doshas: Lal Kitab Shrapit',
			'description' => 'Shrapit dosha (ancestral curse) per LK: Saturn conjunct Rahu/Ketu. Specific remedies provided.',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_full' => [
			'title' => 'Doshas: Parashara full report',
			'description' => 'Combined Parashara dosha report: runs all 6 detectors (Mangal/Kaal Sarp/Pitru/Shrapit/Grahan/Guru-Chandal).',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_grahan' => [
			'title' => 'Doshas: Grahan (eclipse-like)',
			'description' => 'Grahan Dosha: Sun + Rahu/Ketu or Moon + Rahu/Ketu conjunct (eclipse-mimicking position). Up to 4 sub-patterns possible (Surya-Rahu / Surya-Ketu / Chandra-Rahu / Chandra-Ketu).',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_guru_chandal' => [
			'title' => 'Doshas: Guru-Chandal',
			'description' => 'Guru-Chandal Dosha: Jupiter + Rahu or Jupiter + Ketu conjunct. Wisdom-confusion affliction.',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_kaal_sarp' => [
			'title' => 'Doshas: Kaal Sarp',
			'description' => 'Kaal Sarp Dosha: all 7 classical grahas on one side of the Rahu-Ketu axis. Returns 12 sub-types by Rahu house position (Anant=1H, Kulik=2H, Vasuki=3H, Shankhpal=4H, Padma=5H, Mahapadma=6H, Takshak=7H, Karkotak=8H, Shankhachud=9H, Ghatak=10H, Vishdhar=11H, Sheshnag=12H). Also `partial: true` flag when 6 of 7 grahas on one side.',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_mangal' => [
			'title' => 'Doshas: Mangal (Mars affliction)',
			'description' => 'Mangal Dosha detection with school selection (`school` body param: `strict` BPHS verse 1/4/7/8/12 from Lagna only; `north` 1/2/4/7/8/12 from Lagna+Moon; `south` 1/2/4/7/8/12 from Lagna+Moon+Venus, default). Canonical sign-based cancellations applied: Mars in own (Aries/Scorpio) or exalted (Capricorn) sign cancels; per-house cancellations (house 2: Gemini/Virgo; house 4: Taurus/Libra; house 12: Tau',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_pitru' => [
			'title' => 'Doshas: Pitru (ancestral)',
			'description' => 'Pitru Dosha: Sun + Rahu or Sun + Saturn conjunct, plus Sun in 9th house as auxiliary marker. Note: BPHS lists 14 patterns of Pitru Dosha; this endpoint detects 3 most-cited conjunction patterns. Full canonical detection (9th-lord placement, malefic in 9H) deferred to Phase Q.',
			'documented' => true,
		],
		'astroway_vedic_doshas_parashara_shrapit' => [
			'title' => 'Doshas: Shrapit (curse)',
			'description' => 'Shrapit Dosha: Saturn + Rahu conjunct in any sign. Signifies inherited curse/blockage in life.',
			'documented' => true,
		],
		'astroway_vedic_gemstones' => [
			'title' => 'Gemstone (ratna) recommendation',
			'description' => 'Which of the nine gems to wear, from the sidereal lagna. Two schools are implemented: `lagna-lord` (default) prescribes the gems of the 1st, 9th and 5th lords as the life, lucky and benefic stones; `functional-benefic` classifies every graha by the houses it owns from the lagna, prescribes only for functional benefics led by the yogakaraka, and names the functional malefics as gems to avoid. Both ',
			'documented' => true,
		],
		'astroway_vedic_gemstones_navaratna' => [
			'title' => 'Navaratna reference table',
			'description' => 'The nine gems and their graha, with substitutes (upa-ratna), weekday, finger and its variants, metals, minimum weight range in ratti with gram and carat conversions, beeja mantra and japa count. Static lookup, no chart needed.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_argala_analysis' => [
			'title' => 'Jaimini: Argala / Virodhargala scan',
			'description' => 'Full Argala (intervention) + Virodhargala (counter-intervention) scan across all 12 houses. Argala from 2/4/11 (primary), 5 (secondary), 8 (special); Virodhargala from 12/10/3 (primary), 9 (secondary), 6 (special). Net influence and dominant-over metric per house.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_aspects' => [
			'title' => 'Jaimini: Aspects (Rasi + Graha drishti)',
			'description' => 'Combined Jaimini aspects: rasi drishti (12-rasi sign-aspect table per modality rules) + graha drishti (per-planet Parashari aspects). Convenience aggregate of `/drishti-rasi` and `/drishti-graha`.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_atmakaraka_navamsa' => [
			'title' => 'Jaimini: Karakamsa (AK in Navamsa)',
			'description' => 'Karakamsa: sign occupied by the Atmakaraka in the Navamsa (D9). Per Jaimini Sutras 1.2: indicates soul-level destiny, deity worship orientation, primary spiritual path.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_atmakaraka_rotation' => [
			'title' => 'Jaimini: Atmakaraka rotation (timeline)',
			'description' => 'Naisargika 1°/year symbolic progression of sidereal longitudes; scans for moments when the rank-1 chara karaka (Atmakaraka) changes. Returns timeline of soul-significator transitions with age-of-event + before/after planets + life-event hints. Default span 84 years; capped at 120.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_chara_karakas' => [
			'title' => 'Jaimini: Chara Karakas (detailed)',
			'description' => 'Detailed chara karaka ranking with Atmakaraka/Darakaraka highlighted. Same algorithm as `/karakas` but focused output for AK/DK-driven analyses.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_dasha_summary' => [
			'title' => 'Jaimini: Running Dasha Summary',
			'description' => 'Convenience aggregate: returns the running Mahadasha for Chara, Sthira, and Shoola at `targetDate` (default = now). Same builders as the dedicated `/dashas/{chara,sthira,shoola}/maha` endpoints; this one returns three running periods in a single call.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_drishti_graha' => [
			'title' => 'Jaimini: Graha Drishti (planet aspects)',
			'description' => 'Per-planet graha drishti per Parashari rules (BPHS): Mars 4/7/8, Jupiter 5/7/9, Saturn 3/7/10, others 7th. Used in Jaimini-context dashboards alongside rasi drishti.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_drishti_rasi' => [
			'title' => 'Jaimini: Rasi Drishti (sign aspects)',
			'description' => 'Jaimini rasi drishti table: movable signs aspect all fixed except adjacent; fixed aspect all movable except adjacent; dual aspect all other dual. Per Jaimini Sutras 1.1.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_karakas' => [
			'title' => 'Jaimini: Karakas (Chara + Naisargika)',
			'description' => 'Jaimini karakas: chara karakas (8-planet ranking by advancement-in-rasi, Atmakaraka..Darakaraka) + naisargika karakas (fixed planet→house mapping). Source: Jaimini Sutras 2.x + BPHS Adhyaya 47 + PyJHora chara_karakas/naisargika_karakas.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_padas' => [
			'title' => 'Jaimini: Padas (Bhava/Surya/Chandra/Graha Arudhas)',
			'description' => 'All canonical Arudhas: A1..A12 (Bhava Arudhas / lagna padas), S1..S12 (Surya/Sun arudhas), M1..M12 (Chandra/Moon arudhas), and Graha Arudhas (lagna + 9 planets). Implements 1/7-trim rule per BPHS Adhyaya 26 verse 4.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_upapada' => [
			'title' => 'Jaimini: Upapada Lagna (UL)',
			'description' => 'Upapada Lagna (UL = A12): pada of the 12th house from lagna. Canonical Jaimini significator for spouse, marriage, partnerships.',
			'documented' => true,
		],
		'astroway_vedic_jaimini_yogas' => [
			'title' => 'Jaimini: Yogas (basic AK/DK/PK set)',
			'description' => 'Basic Jaimini-yoga checks based on chara karakas: Raja yoga (AK in Lagna/Kendra), marriage yoga (DK in trine), AK+PK conjunction (success yoga), AK in dusthana (challenge flag). Phase 2 block 19 will add 5 dedicated `/yogas/jaimini/*` endpoints with Raja/Dhana/Daridra/Viparita Raja yogas per Jaimini canon.',
			'documented' => true,
		],
		'astroway_vedic_kp_asc_sub' => [
			'title' => 'KP: Ascendant sub-lord',
			'description' => 'Sub-lord of the Ascendant: the canonical "ruling indicator" for the chart\'s primary motivation, life direction, and dominant karmic theme per K.S. Krishnamurti *Reader I-II*.',
			'documented' => true,
		],
		'astroway_vedic_kp_cusps' => [
			'title' => 'KP: Placidus cusps with sub-lord chain',
			'description' => 'KP-canonical Placidus cusps (12) with full sub-lord chain (sign / star / sub / sub-sub) for each cusp. Sub-lord chain follows K.S. Krishnamurti 1971 Vimshottari proportional sub-divisions.',
			'documented' => true,
		],
		'astroway_vedic_kp_fortuna' => [
			'title' => 'KP: Part of Fortune',
			'description' => 'Part of Fortune (Lot of Fortune): ASC + Moon − Sun (day birth) or ASC + Sun − Moon (night birth). Sub-lord chain attached for KP-style usage.',
			'documented' => true,
		],
		'astroway_vedic_kp_horary' => [
			'title' => 'KP: Horary chart (1..249)',
			'description' => 'KP horary number lookup: given a number 1..249, returns the canonical KP-table ASC longitude + sub-lord chain. The horary moment is the call moment passed in the body.',
			'documented' => true,
		],
		'astroway_vedic_kp_planet_cuspal_position' => [
			'title' => 'KP: Planet cuspal positions',
			'description' => 'For each planet: sidereal longitude + KP chain (sign/star/sub/sub-sub) + Placidus house occupied. Convenience layout for KP analyses.',
			'documented' => true,
		],
		'astroway_vedic_kp_ruling_planets' => [
			'title' => 'KP: Ruling Planets',
			'description' => 'Canonical KP ruling planets: Day-lord + Hora-lord + Asc-sign + Asc-star + Asc-sub + Moon-sign + Moon-star + Moon-sub, deduplicated. Used in horary timing analysis.',
			'documented' => true,
		],
		'astroway_vedic_kp_significators' => [
			'title' => 'KP: Significators (primary/secondary/tertiary)',
			'description' => 'KP significator hierarchy per planet: primary = houses occupied by the star-lord; secondary = houses occupied by the planet itself; tertiary = houses occupied by the sign-lord. K.S. Krishnamurti *Reader IV*.',
			'documented' => true,
		],
		'astroway_vedic_kp_sub_lords' => [
			'title' => 'KP: Sub-lords (cusps + planets)',
			'description' => 'Full KP "horoscope at a glance" table: sub-lord chain for every cusp + every planet (lagna, 9 grahas including Ketu).',
			'documented' => true,
		],
		'astroway_vedic_kp_sub_sub_lord' => [
			'title' => 'KP: Sub-sub-lord lookup',
			'description' => 'Returns the full sub-lord chain (sign/star/sub/sub-sub) at any sidereal longitude 0..360. Useful for transit-trigger and dasha-bhukti exact-moment analysis.',
			'documented' => true,
		],
		'astroway_vedic_kp_transit_kp' => [
			'title' => 'KP: Transit positions',
			'description' => 'Sidereal positions of all 9 grahas (incl. Ketu) at `targetDate` (default = now) with KP sub-lord chain attached. Use for KP transit-timing.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_blind_house' => [
			'title' => 'Lal Kitab: Blind houses (Andha bhava)',
			'description' => 'Houses with no planet AND no Parashari aspect. Per Lal Kitab, blind houses indicate areas where karma is "unilluminated" and remedies are essential.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_dasha' => [
			'title' => 'Lal Kitab: Dasha (35-year cycle)',
			'description' => 'Lal Kitab dasha: 35-year cycle, 1 house per ~2.917 years from age 0 forward. Per K. Ashant tradition (alternate 38y impl in some authors flagged in `method`).',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_debts' => [
			'title' => 'Lal Kitab: Rin (6 ancestral debts)',
			'description' => 'Detects six ancestral debts (Pitri / Stree / Kanya / Atma / Rishi / Daiva Rin) per Lal Kitab planet-affliction patterns. Each rin returns trigger conditions + recommended remedy. Per K. Ashant Vol. IV + R.D. Mathur consensus.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_kismat' => [
			'title' => 'Lal Kitab: Kismat (fortune indicator)',
			'description' => 'LK fortune score: +2 pakka ghar, +1 own sign, +2 exalted, −2 debilitated. Higher = more fortunate per K. Ashant Vol. III.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_lal_kundali' => [
			'title' => 'Lal Kitab: Kundali (12-house grid)',
			'description' => 'Lal Kitab kundali grid layout: 12 houses, each listing planets currently in it with state. Companion to /teva for chart visualization.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_life_graph' => [
			'title' => 'Lal Kitab: Life graph (age-by-age)',
			'description' => 'Year-by-year (age 0..35) Lal Kitab dasha snapshot showing the running house, its ruler, and the ruler\'s current state in the chart. Use as a rough timing index.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_planet_house_effect' => [
			'title' => 'Lal Kitab: Planet-in-house effect',
			'description' => 'Short summary of a (planet, house) placement per LK. Caller passes `planet` (0..6, 11=Rahu, 100=Ketu) and `house` (1..12). Currently Sun-only full data; remaining 8 planets are placeholder text; the full 144-cell reading database is a Phase 3 content task.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_prosperity' => [
			'title' => 'Lal Kitab: Sukh (prosperity yoga)',
			'description' => 'LK dhana yoga sum: count benefics (Moon/Mercury/Venus/Jupiter) in 2/5/9/11 houses (LK fixed). +2 each. Higher = more prosperity yoga.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_remedies' => [
			'title' => 'Lal Kitab: Remedies (Upayas)',
			'description' => 'Per-planet Lal Kitab remedies (upayas): the canonical Mathur-tradition remedy + day + donation + mantra. With optional `planet` param, returns single-planet upaya; without, returns all 9.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_sleeping_house' => [
			'title' => 'Lal Kitab: Sleeping houses',
			'description' => 'Houses where a planet is in its pakka ghar with no companions/aspects. LK considers such planets dormant; remedies activate them.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_teva' => [
			'title' => 'Lal Kitab: Teva (fixed-house chart)',
			'description' => 'Lal Kitab teva: fixed-house chart where house = sign (Aries=1..Pisces=12), no ASC rotation. Each planet placed by sign with state (own/exalted/debilitated/neutral) + pakka-ghar match flag. YELLOW: Lal Kitab is single-school; we ship K. Ashant + R.D. Mathur consensus.',
			'documented' => true,
		],
		'astroway_vedic_lal_kitab_varshphal' => [
			'title' => 'Lal Kitab: Varshphal (annual)',
			'description' => 'Lal Kitab annual progression at given `age`. Returns the running 35-year-cycle dasha house + approximate solar-return JD for full annual chart casting.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_business_start' => [
			'title' => 'Muhurat: Business start (Vyapara)',
			'description' => 'Auspicious-window scanner for starting a business / new venture. Preferred nakshatras: Pushya/Hasta/Chitra/Anuradha/U.Phalguni/U.Ashadha/U.Bhadrapada/Sravana/Punarvasu. Avoid Sun/Tue/Sat. Same scoring shape.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_education_start' => [
			'title' => 'Muhurat: Education start (Vidyarambha)',
			'description' => 'Auspicious-window scanner for starting formal education / Vidyarambha ceremony. Preferred nakshatras: Hasta/Chitra/Swati/Pushya/Sravana/Revati/Anuradha/Punarvasu/U.Phalguni/U.Ashadha/U.Bhadrapada. Avoid Sun/Tue/Sat.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_general_auspicious' => [
			'title' => 'Muhurat: General auspicious window',
			'description' => 'Generic favourable-window finder when no specific activity applies (Sankalpa, prayer, fallback). Universal Pushya/Hasta nakshatras + standard shubha tithis. Returns top-N days against universal Panchang criteria.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_investment' => [
			'title' => 'Muhurat: Investment / Dhana Sthapana',
			'description' => 'Auspicious-window scanner for major investments and financial commitments (deposits, share/bond purchase, lending). Preferred nakshatras: Pushya/Anuradha/U.Phalguni/U.Ashadha/U.Bhadrapada/Hasta/Sravana. Avoid Sun/Tue/Sat.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_journey_long' => [
			'title' => 'Muhurat: Long journey (multi-day Yatra)',
			'description' => 'Auspicious-window scanner for multi-day journeys (pilgrimages, relocation travel). Stricter than short travel: Friday is excluded per Yatra prakarana. Preferred nakshatras: Punarvasu/Pushya/Anuradha/Sravana/Hasta/Mrigashira/Revati/Ashwini.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_marriage' => [
			'title' => 'Muhurat: Marriage (Vivah)',
			'description' => 'Auspicious-window scanner for marriage (Vivah Muhurat) over a date range. Scores each day by Tithi + Vara + Nakshatra + Yoga + Karana per Muhurta Chintamani Adhyaya 5 + B.V.Raman *Muhurta* Ch.6. Preferred nakshatras: Rohini/Mrigashira/Magha/Hasta/Swati/Anuradha/Mula/U.Phalguni/U.Ashadha/U.Bhadrapada/Revati. Avoid Sun/Tue/Sat. Returns top-N days sorted by score with per-day Abhijit Muhurat sub-wind',
			'documented' => true,
		],
		'astroway_vedic_muhurat_name_change' => [
			'title' => 'Muhurat: Name change',
			'description' => 'Auspicious-window scanner for legal or sacramental name change (uses Namkaran-derived rules with widened tithi set). Preferred nakshatras: Hasta/Chitra/Swati/Pushya/Anuradha/Revati/U.Phalguni/U.Ashadha/U.Bhadrapada.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_naming_ceremony' => [
			'title' => 'Muhurat: Naming ceremony (Namkaran)',
			'description' => 'Auspicious-window scanner for Namkaran (naming ceremony). Wide nakshatra acceptance per classical text. Note: orthodox practice schedules Namkaran on the 11th or 12th day after birth; this scan returns top auspicious days within any caller-supplied window.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_property_purchase' => [
			'title' => 'Muhurat: Property purchase / Griha Pravesh',
			'description' => 'Auspicious-window scanner for purchasing or moving into property (Griha Pravesh). Preferred nakshatras: Anuradha/U.Phalguni/U.Ashadha/U.Bhadrapada/Mrigashira/Rohini/Pushya/Hasta/Sravana/Dhanishta/Shatabhisha/Revati. Avoid Sun/Tue/Sat.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_surgery' => [
			'title' => 'Muhurat: Surgery (Shastrakarma)',
			'description' => 'Auspicious-window scanner for elective surgery. Inverted polarity vs benefic activities: Tue/Sat (Mars/Saturn) preferred for cutting work; Sun/Mon/Thu/Fri avoided. Output is advisory only, and modern medical scheduling takes precedence.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_travel' => [
			'title' => 'Muhurat: Travel (short Yatra)',
			'description' => 'Auspicious-window scanner for short / daily travel. Preferred nakshatras: Ashwini/Pushya/Anuradha/Hasta/Sravana/Mrigashira/Punarvasu/Revati. Avoid Sun/Tue/Sat.',
			'documented' => true,
		],
		'astroway_vedic_muhurat_vehicle_purchase' => [
			'title' => 'Muhurat: Vehicle purchase',
			'description' => 'Auspicious-window scanner for buying a new vehicle. Preferred nakshatras: Ashwini/Pushya/Hasta/Chitra/Anuradha/Revati/U.Phalguni/U.Ashadha/U.Bhadrapada/Sravana. Avoid Sun/Sat. Same scoring shape as /vedic/muhurat/marriage.',
			'documented' => true,
		],
		'astroway_vedic_panchang_choghadia' => [
			'title' => 'Panchang: Choghadia',
			'description' => '8 day + 8 night Choghadia divisions. Each ~1.5h, marked good/bad/neutral. Cycle: Udveg, Char, Labh, Amrit, Kaal, Shubh, Rog. Day-start by weekday.',
			'documented' => true,
		],
		'astroway_vedic_panchang_full' => [
			'title' => 'Panchang: full',
			'description' => 'Complete daily Panchang: Tithi, Vara, Karana, Yoga, Nakshatra + Choghadia + Rahu Kaal + Yamaganda + Gulika + Abhijit Muhurat. Single call.',
			'documented' => true,
		],
		'astroway_vedic_panchang_hora' => [
			'title' => 'Panchang: Hora',
			'description' => '24 planetary hours per Chaldean order, with sunrise/sunset and day ruler.',
			'documented' => true,
		],
		'astroway_vedic_panchang_karana' => [
			'title' => 'Panchang: Karana',
			'description' => 'Half-tithi (1-60). 7 movable (Bava-Vishti) + 4 fixed (Kimstughna, Shakuni, Naga, Chatushpada).',
			'documented' => true,
		],
		'astroway_vedic_panchang_nakshatra_of_day' => [
			'title' => 'Panchang: Nakshatra of Day',
			'description' => 'Moon\'s sidereal Nakshatra (lunar mansion) at the given moment, with Pada (1-4) and percent-complete.',
			'documented' => true,
		],
		'astroway_vedic_panchang_rahu_kaal' => [
			'title' => 'Panchang: Rahu Kaal block',
			'description' => 'Three inauspicious 1.5h-windows (Rahu Kaal + Yamaganda + Gulika) + Abhijit Muhurat. Position depends on weekday and sunrise/sunset.',
			'documented' => true,
		],
		'astroway_vedic_panchang_tithi' => [
			'title' => 'Panchang: Tithi',
			'description' => 'Lunar day (1-30): Moon-Sun elongation / 12°. Returns paksha (shukla/krishna), tithi name, % complete.',
			'documented' => true,
		],
		'astroway_vedic_panchang_yoga' => [
			'title' => 'Panchang: Yoga',
			'description' => 'Surya-Chandra Yoga (1-27). Sum of Sun + Moon longitudes / (360/27).',
			'documented' => true,
		],
		'astroway_vedic_shadbala_cheshta' => [
			'title' => 'Shadbala: Cheshta (motional)',
			'description' => 'Cheshta Bala, motional strength via simplified retrograde+speed model: retrograde=60v, direct slow=ratio*60, direct fast=(2-ratio)*60. Sun=0 (handled via Ayana with ×2), Moon=0 (handled via Paksha with ×2). Full BPHS Cheshta-Kendra method (mean-longitude based) queued for Phase Q, current divergence vs jhora ≤30v on non-stationary dates.',
			'documented' => true,
		],
		'astroway_vedic_shadbala_dig' => [
			'title' => 'Shadbala: Dig (directional)',
			'description' => 'Dig Bala: directional strength. 60v at preferred kendra cusp, 0v at opposite point, linear gradient. Sun/Mars→10th, Moon/Venus→4th, Jupiter/Mercury→1st, Saturn→7th.',
			'documented' => true,
		],
		'astroway_vedic_shadbala_drik' => [
			'title' => 'Shadbala: Drik (aspectual)',
			'description' => 'Drik Bala, net aspectual strength per BPHS A.27.49: (benefic_drishti − malefic_drishti) / 4 + full Mercury_drishti + full Jupiter_drishti. Mercury and Jupiter aspects super-add (full weight). Vedic full drishti: 7th for all + Mars 4/8, Jupiter 5/9, Saturn 3/10.',
			'documented' => true,
		],
		'astroway_vedic_shadbala_full' => [
			'title' => 'Shadbala: full summary',
			'description' => 'Combined Shadbala: всі 6 типів strength + total Virupa + total Rupa per planet. Single call. Useful for Vedic chart strength dashboards.',
			'documented' => true,
		],
		'astroway_vedic_shadbala_kala' => [
			'title' => 'Shadbala: Kala (temporal)',
			'description' => 'Kala Bala, sum of Nathonnatha (continuous time-from-midnight/noon), Paksha (continuous Moon-Sun elongation, Moon-doubled per BPHS), Tribhaga (3-fold split of day/night), Abda+Masa+Vara+Hora rulers, Ayana (declination-based, Sun-doubled, Mercury bidirectional). Yuddha (planetary war) deferred. Abda/Masa rulers require Vedic calendar lookup, currently zeroed (no contribution) to avoid double-countin',
			'documented' => true,
		],
		'astroway_vedic_shadbala_naisargika' => [
			'title' => 'Shadbala: Naisargika (natural)',
			'description' => 'Naisargika Bala: fixed natural strength per planet. Sun=60v, Moon=51.43, Venus=42.85, Jupiter=34.28, Mercury=25.71, Mars=17.14, Saturn=8.57.',
			'documented' => true,
		],
		'astroway_vedic_shadbala_sthana' => [
			'title' => 'Shadbala: Sthana (positional)',
			'description' => 'Sthana Bala, the sum of 5 sub-strengths: Ucchabala (exaltation), Saptavargaja (sum across 7 vargas D1/D2/D3/D7/D9/D12/D30 with Mulatrikona+Own/Friend/Neutral/Enemy weights per BPHS A.27.10-19), Ojhayugmarasyamsa (odd/even sign suitability D1+D9), Kendradi (60/30/15 angular/succedent/cadent), Drekkana (1st/2nd/3rd third gender match).',
			'documented' => true,
		],
		'astroway_vedic_varga_d1' => [
			'title' => 'Varga D1: Rashi',
			'description' => 'Sidereal natal sign chart (Rashi). Foundation of Vedic analysis.',
			'documented' => true,
		],
		'astroway_vedic_varga_d10' => [
			'title' => 'Varga D10: Dasamsa',
			'description' => 'Dasamsa chart (career, profession, public reputation).',
			'documented' => true,
		],
		'astroway_vedic_varga_d12' => [
			'title' => 'Varga D12: Dwadasamsa',
			'description' => 'Dwadasamsa chart (parents, ancestral karma).',
			'documented' => true,
		],
		'astroway_vedic_varga_d16' => [
			'title' => 'Varga D16: Shodasamsa',
			'description' => 'Shodasamsa chart (vehicles, comforts, conveyances).',
			'documented' => true,
		],
		'astroway_vedic_varga_d2' => [
			'title' => 'Varga D2: Hora',
			'description' => 'Hora chart (wealth analysis, half-sign Sun/Moon division).',
			'documented' => true,
		],
		'astroway_vedic_varga_d20' => [
			'title' => 'Varga D20: Vimsamsa',
			'description' => 'Vimsamsa chart (spiritual progress, sadhana, religious inclination).',
			'documented' => true,
		],
		'astroway_vedic_varga_d24' => [
			'title' => 'Varga D24: Chaturvimsamsa',
			'description' => 'Chaturvimsamsa / Siddhamsa chart (education, learning, academic achievement).',
			'documented' => true,
		],
		'astroway_vedic_varga_d27' => [
			'title' => 'Varga D27: Saptavimsamsa',
			'description' => 'Saptavimsamsa / Bhamsa chart (strengths, weaknesses, stamina).',
			'documented' => true,
		],
		'astroway_vedic_varga_d3' => [
			'title' => 'Varga D3: Drekkana',
			'description' => 'Drekkana chart (siblings, courage; trinal third-of-sign division).',
			'documented' => true,
		],
		'astroway_vedic_varga_d30' => [
			'title' => 'Varga D30: Trimsamsa',
			'description' => 'Trimsamsa chart (misfortunes, evils; uses Parashara unequal-segments formula).',
			'documented' => true,
		],
		'astroway_vedic_varga_d4' => [
			'title' => 'Varga D4: Chaturthamsa',
			'description' => 'Chaturthamsa chart (fortune, fixed assets, real estate).',
			'documented' => true,
		],
		'astroway_vedic_varga_d40' => [
			'title' => 'Varga D40: Khavedamsa',
			'description' => 'Khavedamsa chart (auspicious & inauspicious effects, maternal lineage).',
			'documented' => true,
		],
		'astroway_vedic_varga_d45' => [
			'title' => 'Varga D45: Akshavedamsa',
			'description' => 'Akshavedamsa chart (general life patterns, paternal lineage).',
			'documented' => true,
		],
		'astroway_vedic_varga_d60' => [
			'title' => 'Varga D60: Shashtiamsa',
			'description' => 'Shashtiamsa chart (past karma, the most subtle divisional; high precision needed in birth time).',
			'documented' => true,
		],
		'astroway_vedic_varga_d7' => [
			'title' => 'Varga D7: Saptamsa',
			'description' => 'Saptamsa chart (children, progeny).',
			'documented' => true,
		],
		'astroway_vedic_varga_d9' => [
			'title' => 'Varga D9: Navamsa',
			'description' => 'Navamsa chart (spouse, dharma; the most important divisional chart in BPHS Vedic analysis).',
			'documented' => true,
		],
		'astroway_vedic_varshaphal' => [
			'title' => 'Varshaphal: Tajika annual chart',
			'description' => 'The Tajika annual chart for one year of life. Cast on the moment the SIDEREAL Sun returns to its natal longitude, which for an adult sits many hours away from the tropical solar return and therefore on a different ascendant: the gap runs 0.4 h at age 1, 3.3 h at 10, 7.0 h at 20, 12.5 h at 36 and 17.2 h at 50. Returns the year entry to the second, the sidereal annual chart, the muntha (the natal la',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_daridra' => [
			'title' => 'Yogas: Jaimini Daridra yoga',
			'description' => 'Jaimini Daridra yoga: AK + AmK both in dusthanas (6/8/12), poverty/struggle flag. Remedies recommended.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_dhana' => [
			'title' => 'Yogas: Jaimini Dhana yoga',
			'description' => 'Jaimini Dhana yoga: AK or AmK in 2nd / 11th from lagna. Wealth indicator.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_full' => [
			'title' => 'Yogas: Jaimini full summary',
			'description' => 'Composite Jaimini yoga summary: Raja / Dhana / Daridra / Viparita with karaka details.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_karaka_yoga' => [
			'title' => 'Yogas: Jaimini Karaka yoga (all 8 karakas)',
			'description' => 'Scans every chara karaka (Atmakaraka..Darakaraka) for house-based yogas. Each karaka in kendra/trine/dusthana receives a strength score and manifestation hint (e.g. "Atmakaraka-in-5: creative/spiritual purpose"). Returns 8 yogas with karaka role + house + strength 0-100.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_karakamsa' => [
			'title' => 'Yogas: Karakamsa chart (12-house projection)',
			'description' => 'Karakamsa = Atmakaraka\'s sign in D9 Navamsha, treated as lagna for a 12-house projection. Each house carries canonical iṣṭa-devata / moksha / spiritual significations per Sanjay Rath. Yogas surfaced: 5th-house planet = Ishta Devata; 12th-house planet = Moksha Indicator.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_raja' => [
			'title' => 'Yogas: Jaimini Raja yoga',
			'description' => 'Jaimini Raja yoga: Atmakaraka in Lagna/Kendra OR conjunct Amatyakaraka. Sources: Jaimini Sutras 2.x + Sanjay Rath modern commentary.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_shubha_graha' => [
			'title' => 'Yogas: Shubha-graha (functional natures)',
			'description' => 'Functional benefic / malefic identification per Lagna-lord ownership. Returns each of the 7 visible grahas with natural nature + functional nature (yogakaraka / functional-benefic / neutral / functional-malefic / maraka) + houses owned + reasoning per BPHS Adhyaya 34 kendradhipati & maraka rules.',
			'documented' => true,
		],
		'astroway_vedic_yogas_jaimini_viparita' => [
			'title' => 'Yogas: Jaimini Viparita Raja yoga',
			'description' => 'Reverse Raja yoga: AK in 6/8/12, success after struggle. Per Sanjay Rath modern commentary.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_adhi' => [
			'title' => 'Yogas: Adhi',
			'description' => 'Adhi Yoga: benefics (Mercury, Venus, Jupiter) in 6th, 7th, 8th houses from Moon. Maha Adhi Yoga when all three benefics are positioned там. Powerful for status and prosperity.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_dhana' => [
			'title' => 'Yogas: Dhana (wealth)',
			'description' => 'Dhana Yoga: lords of wealth houses (1, 2, 5, 9, 11) connected via conjunction, mutual graha drishti, or strict parivartana.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_dharma_karmadhipati' => [
			'title' => 'Yogas: Dharma-Karmadhipati',
			'description' => 'Dharma-Karmadhipati Yoga: lord of 9th (Dharma) and lord of 10th (Karma) conjunct, in mutual graha drishti (full Vedic aspects), or single planet rules both. Exceptionally fortunate combination.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_full' => [
			'title' => 'Yogas: Parashara full report',
			'description' => 'Combined Parashara yoga report: runs all 6 detectors (Raja/Dhana/Dharma-Karmadhipati/Pancha-Mahapurusha/Gajakesari/Adhi) and returns one structured response.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_gajakesari' => [
			'title' => 'Yogas: Gajakesari',
			'description' => 'Gajakesari Yoga: Jupiter in a kendra (1, 4, 7, 10) from Moon. One of the most-cited classical combinations.',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_pancha_mahapurusha' => [
			'title' => 'Yogas: Pancha Mahapurusha (5 great)',
			'description' => '5 Mahapurusha yogas: Ruchaka (Mars), Bhadra (Mercury), Hamsa (Jupiter), Malavya (Venus), Sasha (Saturn). Each formed when respective planet is in its own/exalted sign AND in a kendra (1/4/7/10).',
			'documented' => true,
		],
		'astroway_vedic_yogas_parashara_raja' => [
			'title' => 'Yogas: Raja (royal)',
			'description' => 'Raja Yoga detection per BPHS A.36-37: sambandha between kendra-lord (1/4/7/10) and trikona-lord (1/5/9) via 1) conjunction, 2) mutual graha drishti (full Vedic aspects: 7th universal + Mars 4/8, Jupiter 5/9, Saturn 3/10), or 3) strict parivartana (specific pair-swap of houses).',
			'documented' => true,
		],
	],
];
