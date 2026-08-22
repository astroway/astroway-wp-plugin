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
	'label' => 'Reports',
	'endpoints' => [
		'astroway_reports_business' => [
			'title' => 'Generate Business Astrology Report (PDF or HTML)',
			'description' => 'Founding-chart analysis (mundane astrology). Highlights Sun (purpose), MC (reputation), Jupiter (growth), Saturn (structure). Disclaimer: "not financial advice".',
			'documented' => true,
		],
		'astroway_reports_career' => [
			'title' => 'Generate Career Compass Report (PDF or HTML)',
			'description' => 'Career-themed natal: MC, 10th-house cusp, Saturn placement, Mars motivation, aspects to Sun. Self-knowledge tool, not directive.',
			'documented' => true,
		],
		'astroway_reports_child' => [
			'title' => 'Generate Child Astrology Report (PDF or HTML)',
			'description' => 'Parenting-oriented natal report. Highlights Moon (emotional core), Mercury (learning style), Venus (connection style), Mars (energy/temperament). Includes full natal data and a disclaimer noting interpretive nature.',
			'documented' => true,
		],
		'astroway_reports_gemstone' => [
			'title' => 'Generate Gemstone Report (PDF or HTML)',
			'description' => 'The ratna prescription as a multi-page A4 PDF (default) or live HTML (add `?format=html`). Same astrology as `POST /vedic/gemstones`, and pinned by a test to never disagree with it: same sidereal lagna, same two schools, same three gems. What the document adds is what a table cannot carry. Every recommended gem gets a page: what the graha it belongs to signifies classically, the basis in house own',
			'documented' => true,
		],
		'astroway_reports_generate' => [
			'title' => 'Generate Report: Unified Dispatcher (V2)',
			'description' => 'Single endpoint over the 12 type-specific renderers: pass report_type ("natal" | "transit-yearly" | "synastry" | "business" | "career" | "love" | "money" | "child" | "lal-kitab" | "human-design" | "tarot" | "vedic-kundli") plus the renderer-specific inputs. SDK ergonomics: one method instead of 12. Required fields vary by type: chart for most, chart1+chart2 for synastry, seed for tarot (chart opti',
			'documented' => true,
		],
		'astroway_reports_history' => [
			'title' => 'List Recent Report Exports',
			'description' => 'List the calling key\'s most recently generated PDF reports: type, byte size, page count, language, created/expiry timestamps and a `url`. Re-fetch a report link within its 24h validity window, or surface recent exports in a dashboard. PDFs are purged after 24h, so older items return `expired: true` with a now-dead URL. Free to call. Query `?limit=` (1–50, default 10).',
			'documented' => true,
		],
		'astroway_reports_human_design' => [
			'title' => 'Generate Human Design Report (PDF or HTML)',
			'description' => 'Bodygraph PDF: Type, Strategy, Authority, Profile, Definition, Not-Self theme, Incarnation Cross + 9 centers (defined/open) + activated channels with gate pairs.',
			'documented' => true,
		],
		'astroway_reports_lal_kitab' => [
			'title' => 'Generate Lal Kitab Report (PDF or HTML)',
			'description' => 'Lal Kitab analysis: Teva (graha placements with Pakka-ghar match), Kismat & Prosperity scores, detected Rins (ancestral debts) with triggers, suggested Upayas (remedies). Sidereal compute (Lahiri ayanamsa) with sign-from-Lagna house numbering per Lal Kitab convention.',
			'documented' => true,
		],
		'astroway_reports_love' => [
			'title' => 'Generate Love Report (PDF or HTML)',
			'description' => 'Romantic-relationship natal report. Highlights Venus (attraction style), Mars (desire), Moon (emotional needs), Descendant (partner profile). Disclaimer: not a prophecy.',
			'documented' => true,
		],
		'astroway_reports_money' => [
			'title' => 'Generate Money Report (PDF or HTML)',
			'description' => 'Financial natal: 2nd house (earned income), 8th house (shared resources), Jupiter (expansion), Saturn (discipline). Disclaimer: not investment advice.',
			'documented' => true,
		],
		'astroway_reports_muhurta' => [
			'title' => 'Generate Muhurta Report (PDF or HTML)',
			'description' => 'Render a standard A4 report of the most auspicious dates for a chosen activity over a search window. Same window-scan engine as /vedic/muhurat/*: it scores each day by sunrise Panchang (Tithi/Vara/Nakshatra/Yoga/Karana) per Muhurta Chintamani + B.V.Raman, and lists ranked days with per-day Abhijit Muhurat and the scoring factors as the rationale. `activity` is one of the 12 from /muhurta/types. PD',
			'documented' => true,
		],
		'astroway_reports_natal' => [
			'title' => 'Generate Natal Report (PDF or HTML)',
			'description' => 'Render a Western tropical natal chart as a single-page A4 PDF (default) or live HTML (add `?format=html`). Includes Big Three (Sun/Moon/ASC), full planets table with houses + retrograde, all 12 house cusps, and major aspects. Set `whitelabel: true` to apply the caller\'s branding overrides. Languages: `uk` (default) or `en`. PDF URLs valid 24h via auto-cleanup cron; HTML mode streams directly witho',
			'documented' => true,
		],
		'astroway_reports_relocation' => [
			'title' => 'Generate Relocation Report (PDF or HTML)',
			'description' => 'Compare up to five places for one birth chart as a multi-page A4 PDF (default) or live HTML (add `?format=html`). Per place: the relocated ascendant and midheaven with the signed shift from birth, **which planets changed house** (the substantive difference, diffed rather than left as two tables), every astrocartography line running within 300 km with its interpretation text, and supportive/challen',
			'documented' => true,
		],
		'astroway_reports_stellaforge' => [
			'title' => 'Generate Stellaforge Birth-Chart Poster (PDF or HTML)',
			'description' => 'Render a data-rich, print-ready natal chart poster. A high-detail western wheel (colored element sectors, colored glyphs, degree labels, ASC arrow, MC marker, house cusps) plus the Sun/Moon/Rising trio, a placements table, element/modality balance bars, and the top aspects, fully deterministic, 0 AI. Three styles via `style`: `editorial` (light), `celestial` (dark + gold), `classic` (minimal). Whi',
			'documented' => true,
		],
		'astroway_reports_synastry' => [
			'title' => 'Generate Synastry Report (PDF or HTML)',
			'description' => 'Render a relationship synastry PDF: side-by-side Big Three, full cross-chart aspect table (top 40 major aspects), and per-chart + combined element/modality balance.',
			'documented' => true,
		],
		'astroway_reports_tarot' => [
			'title' => 'Generate Tarot Reading (PDF or HTML)',
			'description' => 'Render a tarot reading PDF. Default spread `three-card` (Past/Present/Future from Rider-Waite-Smith deck). Available spreads: single-card, three-card, celtic-cross, horseshoe, relationship, year-ahead, decision, chakra, career, love-triangle. Pass `seed` for reproducibility (deterministic mulberry32 RNG); omit to use a daily seed. Set `allowReversed: false` to draw upright cards only.',
			'documented' => true,
		],
		'astroway_reports_transit_yearly' => [
			'title' => 'Generate Year-Ahead Transit (PDF or HTML)',
			'description' => 'Render a year-ahead transit calendar PDF grouped by month. Includes major aspects (conjunction, sextile, square, trine, opposition) of outer planets (Mars through Pluto) to natal positions, with 0.5° max orb. Defaults to next calendar year if `year` omitted.',
			'documented' => true,
		],
		'astroway_reports_vedic_kundli' => [
			'title' => 'Generate Vedic Kundli (PDF or HTML)',
			'description' => 'Sidereal Vedic chart (Lahiri ayanamsa): Lagna + Moon nakshatra/pada, all sidereal planet positions with nakshatra+pada+house, full 9-period Vimshottari Mahadasha tree with current period highlighted.',
			'documented' => true,
		],
	],
];
