=== AstroWay – Astrology, Birth Chart & Horoscope Widgets ===
Contributors: astrowayteam
Tags: astrology, birth chart, natal chart, horoscope, tarot
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.10.7
License: MIT
License URI: https://opensource.org/licenses/MIT

Astrology & horoscope widgets for WordPress: birth & natal charts, synastry, Tarot, Numerology, Human Design via shortcodes & blocks.

== Description ==

Add astrology and horoscope widgets to any WordPress page or post via shortcodes or Gutenberg blocks: natal and birth charts, synastry, Tarot, Numerology, Human Design, moon phase and daily horoscopes.

**Free and open source. No account, no API key, and no credit card required.** The core widgets render out of the box in anonymous mode (30 requests/hour per visitor IP), which is enough for most blogs and small sites. Need more volume? A free API key (still no card) raises the limit to 10,000 calls/month, and paid plans are optional and only unlock advanced Pro widgets.

Under the hood the widgets are powered by api.astroway.info, with 700+ endpoints covering Western, Vedic, Hellenistic, Chinese and Mayan astrology, Tarot (Rider-Waite, Marseille, Lenormand), Numerology (Pythagorean, Chaldean, Kabbalistic, Tamil), Human Design and AI horoscopes.

== Installation ==

1. Upload the plugin via Plugins → Add New, or unzip into `/wp-content/plugins/`.
2. Activate through the Plugins menu.
3. (Optional) Settings → AstroWay → paste your API key for higher rate limits and Pro features.
4. Add a shortcode to any page: `[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]`.

== Frequently Asked Questions ==

= Is AstroWay free? Are there any hidden costs? =

Yes. The plugin is free and open source, and the core widgets work with no account, no API key, and no credit card. They render in anonymous mode (30 requests/hour per visitor IP), which is plenty for most blogs and small sites. If you need higher limits, a free API key (still no card) gives you 10,000 calls/month. Paid plans are optional and only unlock advanced Pro widgets; anything you have already added keeps working.

= How do I add a horoscope or birth chart to WordPress? =

Drop a shortcode into any page or post, for example `[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]` for a natal (birth) chart, or `[astroway_daily_horoscope sign="leo"]` for a daily horoscope. Every widget is also a Gutenberg block: Natal Chart, Daily Horoscope, Moon Phase, Human Design Bodygraph, Daily Tarot, Vedic Kundli, Transit Sky, Daily Panchang, Numerology and Synastry.

= Do I need an API key? =

No. The plugin works without a key in anonymous mode (30 requests/hour per visitor IP). For higher limits and Pro features, get a free key at api.astroway.info.

= What data is sent to api.astroway.info? =

Birth data (date, time, city, coordinates) for chart calculation. No data is stored on your site beyond standard WP caching.

= Can I use this on multiple sites? =

Yes for the free anonymous tier. Paid API keys are bound to a single domain by default; you can change the bound domain self-service from the API dashboard at api.astroway.info/dashboard.

= Is my visitors' data stored anywhere? =

No personal data is stored on api.astroway.info. Birth data (date, time, city, lat/lon) is processed in-memory for chart calculation and the result is returned. The plugin caches results locally on your WordPress site (WP transients) to reduce repeat API calls; the cache contains chart output only, no PII beyond what was submitted via shortcode arguments.

= How do I report a bug or request a feature? =

For plugin issues: file an issue at https://github.com/astroway/astroway-wp-plugin/issues. For API or billing questions: contact support via your dashboard at api.astroway.info/dashboard.

== Screenshots ==

1. AstroWay menu: API Key landing. Top-level admin menu item with a custom brand icon. The default landing page is the API Key submenu, paste your key, compare anonymous / free / paid plans side-by-side, and connect in one click.
2. Settings: connection, cache, and system diagnostics. Test the connection to api.astroway.info, inspect the local transient cache (size, entry count, purge), and copy a diagnostic block (PHP / WP / plugin version, key status) for support.
3. Shortcodes reference with built-in city: lat/lon helper. Every shortcode gets a copy-to-clipboard reference card (description, snippet, params table, Gutenberg block hint). The city search resolves coordinates and IANA timezone and pastes a ready-to-use shortcode.
4. Gutenberg block in the editor. Blocks render via ServerSideRender with live preview. Block selection and the Inspector panel work the standard WordPress way.
5. Natal chart widget on the frontend, rendered via `[astroway_natal date="…" time="…" lat="…" lon="…"]`. Includes zodiac wheel, planets with retrograde markers, and major aspects.

== External services ==

This plugin connects to **api.astroway.info**, the AstroWay Calculation API operated by the AstroWay Team, to render astrology widgets and tarot readings. No external request is made until a widget is actually rendered on a page where one of the plugin's shortcodes or Gutenberg blocks is used.

**What is sent:**

* When a natal chart, bodygraph, or transit widget renders: the parameters provided in the shortcode (date, time, latitude, longitude) plus the visitor's IP address (for anonymous rate-limiting).
* When a daily horoscope, moon phase, or daily tarot widget renders: the zodiac sign or deck identifier plus the visitor's IP address.
* When the site administrator clicks "Verify Key" or "Test Connection" in the Settings screen: the configured API key and a small diagnostics payload.

**When this happens:**

* On frontend page render (only on pages where an AstroWay shortcode or block is present).
* On explicit admin action (Verify, Test Connection, Purge Cache).

**No data is sent on plugin activation, deactivation, or admin pages without explicit user action.**

* Service URL: `https://api.astroway.info/v1/`
* Terms of Service: https://api.astroway.info/terms
* Privacy Policy: https://api.astroway.info/privacy

== Privacy ==

This plugin stores the following on the WordPress site:

* The site administrator's API key (if entered), stored in the `wp_options` table under `astroway_settings`. Visible only to users with `manage_options` capability.
* WP transient cache of API responses (prefix `astroway_v1_`) to reduce repeat external calls. Cache contents are chart/horoscope/tarot output, no visitor PII beyond what was submitted via shortcode arguments. Purged via Settings → AstroWay → Purge Cache.

**This plugin does not set any cookies on visitor browsers, does not use third-party tracking, and does not transmit visitor data to anyone other than api.astroway.info (see External services above).**

== Changelog ==

= 0.10.7 =
* Fix: widgets no longer disappear from your pages. The plugin probed its own server's rate limit and, when that quota ran out, rendered nothing at all, even though each visitor has a separate quota. Rendering no longer depends on that probe, and the probe now runs only where its reading is meaningful: the admin notice.
* Fix: [astroway_today_in_sky] and [astroway_fortune_cookie] are deprecated. The api routes they call were never implemented, so both rendered an empty frame. Pages containing them stay intact and administrators see a short note; the shortcodes will return when the routes exist.
* Fix: the activation, review and rate-limit notices appeared on every admin page, including other plugins' settings screens, while being hidden on our own. Both halves fixed: they show up on AstroWay screens only, and they are visible there.
* Fix: the "Render mode" and "Spend cap" settings were saved but never read by anything. Both panels are removed until they actually do something.
* Change: wp.org builds no longer bundle an update checker, per directory rules. Updates come from wordpress.org as before.
* New: five Gutenberg blocks with matching shortcodes, each rendering an anonymous embed: Vedic Kundli, Transit Sky, Daily Panchang, Numerology and Synastry. The plugin now ships ten blocks.
* Fix: Natal Chart and Bodygraph sent longitude as lon (the api reads lng) and the timezone as a name rather than a numeric offset, so charts were built for longitude 0 and UTC. House cusps and the Ascendant are now correct. No content edits needed.
* Fix: verifying a rejected API key showed an empty success panel instead of the rejection reason.
* New: optional "Widget disclaimer" setting, off by default, appends a short informational line to embedded widgets.
* New: the admin menu carries the AstroWay owl mark.
* Note: the review prompt and the rate-limit notice announced in 0.5.6 were missing from the published builds until now. They ship with this release.

= 0.10.6 =
* New: "Widget disclaimer" setting (Settings → AstroWay). Off by default. When enabled, embedded widgets show a short "informational, not professional advice" line in their footer, useful when your jurisdiction or compliance policy requires it. The wording is rendered and localized by api.astroway.info.
* Fix: the "Spend cap" field on the Settings page no longer renders as an oversized box (it had inherited a flex value meant for horizontal layouts).

= 0.10.5 =
* Branding: the admin menu icon and the review-prompt now use the AstroWay owl mark instead of a generic star.
* Fix: verifying a rejected API key now shows a clear error message instead of an empty green panel (the panel only appeared blank because the request still reported success on a 401).

= 0.10.4 =
* New: 5 Gutenberg blocks + matching shortcodes, Vedic Kundli, Transit Sky, Daily Panchang, Numerology, and Synastry compatibility. Each wraps a /v1/embed/* endpoint as an anonymous-friendly responsive iframe. Plugin now ships 10 blocks.
* New: dark / light / console theme selector on the new widgets.
* Fix: Natal Chart and Bodygraph now send longitude as `lng` and the timezone as a numeric UTC offset. The api had been receiving longitude 0 and UTC, so house cusps and the Ascendant were computed for the wrong location/time, charts are now correct. Existing blocks need no changes; just upgrade.

= 0.10.3 =
* Two final Elementor widgets: AstroWay Today in Sky (with SWITCHER controls for Sun/Moon, Mercury retrograde, planetary hour, location fields conditional) + AstroWay Fortune Cookie (optional sign dropdown).
* Closes Elementor wrapper stack (v0.10.0-v0.10.3). Plugin now ships 7 Elementor widgets covering all 7 core shortcodes.

= 0.10.2 =
* Two more Elementor widgets: AstroWay Human Design Bodygraph + AstroWay Daily Tarot (with deck dropdown, Rider-Waite/Marseille/Lenormand).

= 0.10.1 =
* Two more Elementor widgets: AstroWay Daily Horoscope (sign dropdown) + AstroWay Moon Phase (date + lang). Same render-via-shortcode pattern as v0.10.0.

= 0.10.0 =
* New AstroWay category in Elementor widget panel. First widget: AstroWay Natal Chart, which wraps the [astroway_natal] shortcode with Inspector controls for date/time/lat/lon/tz/lang. Loads only when Elementor plugin is active.
* First piece of Elementor integration stack (v0.10.0-v0.10.3). Standalone `astroway-elementor` addon plugin (planned ~Aug 2026) will provide advanced controls + animations.

= 0.9.4 =
* New shortcode `[astroway_fortune_cookie]` shows a daily AI-generated cosmic quote via /v1/embed/fortune-cookie. Anonymous-accessible, sign-aware (optional `sign` param).
* Closes Today in Sky stack (v0.9.0-v0.9.4).

= 0.9.3 =
* `[astroway_today_in_sky]` accepts `show_planetary_hour` (default `0`) + `lat`, `lon`, `tz` parameters. When enabled, the widget pulls today's planetary hour mapping for the supplied location.

= 0.9.2 =
* `[astroway_today_in_sky]` accepts `show_retrograde` parameter (default `1`), toggles the Mercury retrograde indicator section. SEO-friendly viral content for retrograde periods.

= 0.9.1 =
* `[astroway_today_in_sky]` accepts `show_signs` parameter (default `1`): toggles the Sun/Moon zodiac positions block. New `sanitize_bool_flag()` helper added as public static for addon reuse.

= 0.9.0 =
* New shortcode `[astroway_today_in_sky]` renders the day's celestial overview via /v1/embed/today-in-sky. Anonymous-accessible (IP-rate-limited iframe).
* New widget config in RendererDecisions registry; new feature key in Tier::matrix. Sub-features (sun/moon positions, retrogrades, planetary hour, Fortune Cookie) land in v0.9.1-v0.9.4.

= 0.8.3 =
* New Settings panel "Domain binding": shows current domain attached to the key + input to request rebinding via POST /v1/auth/keys/domain-change. Manual fallback link to api dashboard if the endpoint returns an error.
* Closes admin enhancements stack (v0.8.0-v0.8.3).

= 0.8.2 =
* New Settings panel "Feature matrix": visual grid of features × tiers with checkmarks. Highlights the current tier's column. Reads from `Tier::matrix()` so addon-registered features appear automatically.

= 0.8.1 =
* New Settings panel "Spend cap": local mirror of the monthly USD ceiling configurable at api.astroway.info/dashboard/billing. Stored as integer 0..100000 in OPTION_KEY['spend_cap_usd'].

= 0.8.0 =
* New Settings panel "Render mode" with radio toggle: Auto (default) / Force iframe / Force client. Stored in OPTION_KEY['render_mode']. RendererDecisions wiring lands when native client widgets ship in v1.1+.
* First piece of admin enhancements stack (v0.8.0-v0.8.3).

= 0.7.4 =
* New `Tier::render_upgrade_cta( $feature )` returns a styled CTA panel with feature label + upgrade button linking to api.astroway.info/dashboard/upgrade. Filterable via `astroway_upgrade_cta_html` for theme/addon customization.
* Block + shortcode guards (v0.7.2/v0.7.3) refactored to call the helper. Closes the v0.7.x tier-gating stack and pre-v1.0 blocker B3.

= 0.7.3 =
* Per-shortcode tier guards: each core `astroway_*` shortcode is now wrapped with `Tier::can( $feature )`. Locked shortcodes render an inline "Pro feature" CTA. Behavior matches v0.7.2 block-side gating, v1 shortcodes remain anonymous-accessible.

= 0.7.2 =
* Per-block tier guards: each registered Gutenberg block is now wrapped with `Tier::can( $feature )`. Locked blocks render an inline "Pro feature" CTA instead of the widget. All v1 blocks remain accessible to anonymous users (matrix default); guards activate for v1.x+ Pro blocks.

= 0.7.1 =
* New `Tier::can( string $feature ): bool` checks whether current tier can access a feature.
* New `Tier::matrix(): array` returns feature → allowed tiers map, filterable via `astroway_tier_matrix` so addons can declare their own features.
* Default matrix: v1 widgets (natal, daily_horoscope, moon_phase, bodygraph, daily_tarot) anonymous-OK; synastry/solar_return/lunar_return/progressions/native_render paid-only; ai_chat/transit_alerts pro+.

= 0.7.0 =
* New utility class `AstroWay\WPPlugin\Tier` with `Tier::current()` resolving the user's plan from cached /v1/auth/keys/me response (30 min TTL). Returns anonymous/free/indie/starter/pro/business/internal.
* AddonAPI::current_tier() refactored as facade delegating to Tier::current(). First piece of tier-gating subsystem (v0.7.x).

= 0.6.5 =
* New developer guide `docs/addon-development.md` documents the v0.6.x Addon Hooks API + AddonAPI reference + minimal working example.
* New example addon scaffold `docs/example-addon/` shows shortcode registration + tier check + widget declaration.
* Closes the Addon Hooks API atomic stack v0.6.0-v0.6.5. Track 3 addon plugins can now develop against a stable surface.

= 0.6.4 =
* New public class `AstroWay\WPPlugin\AddonAPI`: BC-locked stable surface for addon developers. Methods: `register_widget()`, `current_tier()`, `api_base()`, `has_key()`, `cache_key()`. Internal classes (RendererDecisions, ApiClient, Cache, Admin) remain unstable.

= 0.6.3 =
* New filter: `astroway_widgets` lets addons append entries to the widget registry without touching core. Useful for new shortcodes/blocks that map to api `/v1/embed/*` endpoints.

= 0.6.2 =
* New action: `astroway_register_blocks` fires at end of Blocks::register_assets_and_blocks(). Addons hook here to call register_block_type() for their own astroway/* Gutenberg blocks.

= 0.6.1 =
* New action: `astroway_register_shortcodes` fires at end of Shortcodes::register(). Addons hook here to call add_shortcode() for their own astroway_* shortcodes.

= 0.6.0 =
* New action: `astroway_init` fires early in plugin boot, signalling addons that core classes are loaded and they can hook into upcoming registration actions.
* First atomic piece of the Addon Hooks API (v0.6.0-v0.6.5). Unlocks third-party addon ecosystem.

= 0.5.5 =
* Admin: new "Update channel" panel on the API Key page shows current channel (A/B), whether PUC library is loaded, whether an API key is set, and last update check timestamp.
* Closes the Channel B atomic stack v0.5.0-v0.5.5. Plugin meets the v1.0 "Channel B custom updater" stability gate condition.

= 0.5.4 =
* Channel B update checks now send `Cache-Control: no-cache` + `Pragma: no-cache` headers, ensuring fresh response from `astroway.info` even if a transit cache sits between WP and the server.
* "View details" modal verified to render correctly with PUC defaults: pulls changelog/sections from `update.json`.

= 0.5.3 =
* Channel B custom updater wired via plugin-update-checker: registers against https://astroway.info/wp-plugin/update.json with the saved API key carried as ?key= query arg.
* When PUC is absent or the server endpoint is unreachable, wp.org Channel A continues to deliver updates as before.

= 0.5.2 =
* Bundled `YahnisElsts/plugin-update-checker` v5.6 library at `includes/lib/plugin-update-checker/`, autoloaded via `load-v5p6.php`. Foundation for v0.5.3 Channel B update hook.
* No new user-visible behavior. PUC is loaded but no update checker instance is created yet.

= 0.5.1 =
* Channel B download endpoint live at astroway.info/wp-plugin/download/{version}/, 302-redirects paid-tier keys to versioned ZIP on GitHub Releases.
* update.json now returns full `download_url` field for paid tiers.
* No user-visible plugin code change. Plugin-side update hooks land in v0.5.3.

= 0.5.0 =
* Channel B server endpoint deployed at astroway.info/wp-plugin/update.json, paid-tier auto-update infrastructure (first of 6 atomic pieces, v0.5.0-v0.5.5).
* No user-visible plugin code change. Plugin-side updater hooks land in v0.5.3.

= 0.4.0 =
* CI/Tests infrastructure: PHPUnit unit tests + WPCS lint via GitHub Actions matrix (PHP 8.1-8.4 full test, 7.4/8.0 syntax compat)
* Refactor: input sanitization extracted as public statics on Shortcodes class (no behavior change, enables addon reuse)
* No user-visible changes: pure foundation for upcoming addon ecosystem (v0.6.0)

= 0.3.0 =
* New: live Status panel on the API Key admin landing, plan / credits / period / domain pulled from /v1/auth/keys/me (30 min transient cache)
* New: `lang` attribute on all 5 shortcodes (e.g. `[astroway_natal date="..." lang="ru"]`), 21 api-supported locales
* New: Language Inspector dropdown on all 5 Gutenberg blocks, uk, en, de, ru, pl, es, pt, fr, it, nl, cs, ro, hu, el, tr, ar, hi, ja, ko, vi, id
* Default lang resolves to the WP site locale; invalid codes silently fall back, no user-facing error
* Status panel handles all 5 states: valid / suspended / revoked / invalid_key / api_down with per-state color accent
* Requires api.astroway.info v2.33.0+ for /v1/auth/keys/me; falls back to legacy /v1/keys/usage with "Limited data" notice on older self-hosted api
* No regressions on v0.2.3: existing shortcodes/blocks without `lang=` render in site default exactly as before

= 0.2.3 =
* All 3 subpages share one hero (brand + status badge + CTAs); only title and tagline vary per page
* Right sidebar restored (Resources + System + quote) in a 1fr + 280px grid
* Buttons read as buttons in both light panels and the dark hero (ghost variant + on-dark override)
* Shortcode reference cards gain proper panel-body inset
* New: city → lat/lon/IANA-tz helper on the Shortcodes page (server-side proxy to app.astroway.info atlas + click-to-copy snippet)
* Type tokens drop bundled webfont declarations: pure ui-sans-serif/system-ui stack, multi-script coverage via OS fonts
* 20 bundled translations: uk, de_DE, ru_RU, pl_PL, es_ES, pt_BR, hi_IN, fr_FR, ko_KR, it_IT, ja, id_ID, tr_TR, nl_NL, ro_RO, cs_CZ, vi, ar (RTL), el, hu_HU
* Translation filenames corrected to {textdomain}-{locale}.mo + explicit load_plugin_textdomain call

= 0.2.2 =
* Admin UI split into 3 submenu pages: API Key (default landing), Settings, Shortcodes
* API Key landing rebuilt with 3-tier comparison (Anonymous / Free key / Paid), state badge, key form, prominent CTAs
* Settings page focused on diagnostics: Connection ping, Cache stats + purge, System info with Copy diagnostic button
* Shortcodes page rebuilt as expanded reference cards (description + copyable code + params table + Gutenberg block hint per shortcode)
* JS split per-page (api-key / settings / shortcodes), reducing payload on each admin screen

= 0.2.1 =
* Settings page promoted to a top-level admin menu item with a custom brand icon (star inside orbit ring, single-path SVG)
* Activation notice rebranded with a cosmic-gradient mark, two CTAs (Get free API key / Open Settings), and persistent dismiss (fix for a latent bug where the dismiss action did not survive page reloads)
* Settings page fully redesigned with an observatory aesthetic: hero with owl-moon logo, four numbered panels (API key / Connection / Cache / Shortcodes), right-side Resources / System / quote aside, paper-warm cards with gold hairlines
* Shortcode rows copy-to-clipboard on click
* ASTROWAY_WP_PLUGIN_VERSION constant auto-derived from the plugin header via get_file_data, single source of truth, simpler release pipeline

= 0.2.0 =
* Settings page under Settings → AstroWay (API key input, Verify Key, Test Connection, Cache controls, shortcode reference, Diagnostics)
* API key field accepts both sandbox (aw_test_*) and live (aw_live_*) keys
* All keyed requests now carry X-Api-Key + X-AstroWay-Site-URL headers (lazy domain bind support)
* Plugin row links: Settings + Get API Key (with ?source=wp_plugin)
* Activation notice CTA URL includes ?source=wp_plugin for referrer-source tracking
* New transient cache layer (prefix astroway_v1_) with purge button + stats
* Verify Key gracefully falls back to /v1/keys/usage until api ships /v1/auth/keys/me (Block A)

= 0.1.0 =
* MVP: 5 iframe shortcodes via /v1/embed/* (works without API key, 30/hr per IP)
* 5 Gutenberg blocks with ServerSideRender
* Vanilla CSS theme-overridable via CSS vars (--astroway-*)
* i18n .pot + EN/UK/DE/PL base translations
* Graceful 429 handler with 'Get free API key' admin notice

= 0.1.0-alpha.1 =
* Initial scaffold (pre-release). Plugin header + PSR-4 autoload + activation hooks. No shortcodes yet, first functional release planned as 0.1.0.

== Upgrade Notice ==

= 0.3.0 =
Adds a live Status panel on the API Key admin page (plan / credits / period / domain) and a `lang` attribute on all shortcodes and Gutenberg blocks for 21 api-supported locales. Drop-in upgrade.

= 0.2.3 =
Admin UI polish, unified hero, restored sidebar, city search on the Shortcodes page, 20 bundled translations. Drop-in upgrade.

= 0.2.2 =
Admin split into 3 submenu pages (API Key / Settings / Shortcodes). Drop-in upgrade.

= 0.2.1 =
Settings page moves to a top-level admin menu with a brand icon. Drop-in upgrade.

= 0.2.0 =
Adds Settings page for API key configuration. Drop-in upgrade from 0.1.0, anonymous mode shortcodes continue to work.

= 0.1.0 =
First functional release. 5 shortcodes + 5 Gutenberg blocks work without an API key in anonymous mode (30 requests/hour per IP).

= 0.1.0-alpha.1 =
Pre-release scaffold. Not for production use.
