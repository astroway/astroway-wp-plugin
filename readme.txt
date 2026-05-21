=== AstroWay ===
Contributors: astrowayteam
Tags: astrology, natal chart, horoscope, tarot, human design
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.5.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Natal charts, synastry, transits, Tarot, Numerology, Human Design, AI horoscopes — shortcodes + Gutenberg blocks.

== Description ==

Add astrological calculations to any WordPress page or post via shortcodes or Gutenberg blocks. Powered by api.astroway.info — 700+ endpoints covering Western, Vedic, Hellenistic, Chinese, Mayan astrology + Tarot (Rider-Waite, Marseille, Lenormand) + Numerology (Pythagorean, Chaldean, Kabbalistic, Tamil) + Human Design + AI horoscopes.

**Works without an API key** in anonymous mode (30 requests/hour per visitor IP). Get a free API key at api.astroway.info/dashboard/sign-up for 10,000 credits/month and higher rate limits.

== Installation ==

1. Upload the plugin via Plugins → Add New, or unzip into `/wp-content/plugins/`.
2. Activate through the Plugins menu.
3. (Optional) Settings → AstroWay → paste your API key for higher rate limits and Pro features.
4. Add a shortcode to any page: `[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]`.

== Frequently Asked Questions ==

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

1. AstroWay menu → API Key landing. Top-level admin menu item with a custom brand icon. The default landing page is the API Key submenu — paste your key, compare anonymous / free / paid plans side-by-side, and connect in one click.
2. Settings — connection, cache, and system diagnostics. Test the connection to api.astroway.info, inspect the local transient cache (size, entry count, purge), and copy a diagnostic block (PHP / WP / plugin version, key status) for support.
3. Shortcodes reference with built-in city → lat/lon helper. Five copy-to-clipboard reference cards (description, snippet, params table, Gutenberg block hint). The city search resolves coordinates and IANA timezone and pastes a ready-to-use shortcode.
4. Gutenberg block in the editor. Five blocks — Natal Chart, Human Design Bodygraph, Daily Horoscope, Moon Phase, Daily Tarot — render via ServerSideRender with live preview. Block selection and the Inspector panel work the standard WordPress way.
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
* WP transient cache of API responses (prefix `astroway_v1_`) to reduce repeat external calls. Cache contents are chart/horoscope/tarot output — no visitor PII beyond what was submitted via shortcode arguments. Purged via Settings → AstroWay → Purge Cache.

**This plugin does not set any cookies on visitor browsers, does not use third-party tracking, and does not transmit visitor data to anyone other than api.astroway.info (see External services above).**

== Changelog ==

= 0.5.2 =
* Bundled YahnisElsts/plugin-update-checker v5.6 library at includes/lib/plugin-update-checker/ — autoloaded via load-v5p6.php. Foundation for v0.5.3 Channel B update hook.
* No new user-visible behavior. PUC is loaded but no update checker instance is created yet.

= 0.5.1 =
* Channel B download endpoint live at astroway.info/wp-plugin/download/{version}/ — 302-redirects paid-tier keys to versioned ZIP on GitHub Releases.
* update.json now returns full download_url field for paid tiers.
* No user-visible plugin code change. Plugin-side update hooks land in v0.5.3.

= 0.5.0 =
* Channel B server endpoint deployed at astroway.info/wp-plugin/update.json — paid-tier auto-update infrastructure (first of 6 atomic pieces, v0.5.0-v0.5.5).
* No user-visible plugin code change. Plugin-side updater hooks land in v0.5.3.

= 0.4.0 =
* CI/Tests infrastructure: PHPUnit unit tests + WPCS lint via GitHub Actions matrix (PHP 8.1-8.4 full test, 7.4/8.0 syntax compat)
* Refactor: input sanitization extracted as public statics on Shortcodes class (no behavior change, enables addon reuse)
* No user-visible changes — pure foundation for upcoming addon ecosystem (v0.6.0)

= 0.3.0 =
* New: live Status panel on the API Key admin landing — plan / credits / period / domain pulled from /v1/auth/keys/me (30 min transient cache)
* New: `lang` attribute on all 5 shortcodes (e.g. `[astroway_natal date="..." lang="ru"]`) — 21 api-supported locales
* New: Language Inspector dropdown on all 5 Gutenberg blocks — uk, en, de, ru, pl, es, pt, fr, it, nl, cs, ro, hu, el, tr, ar, hi, ja, ko, vi, id
* Default lang resolves to the WP site locale; invalid codes silently fall back, no user-facing error
* Status panel handles all 5 states: valid / suspended / revoked / invalid_key / api_down with per-state color accent
* Requires api.astroway.info v2.33.0+ for /v1/auth/keys/me; falls back to legacy /v1/keys/usage with "Limited data" notice on older self-hosted api
* No regressions on v0.2.3 — existing shortcodes/blocks without `lang=` render in site default exactly as before

= 0.2.3 =
* All 3 subpages share one hero (brand + status badge + CTAs); only title and tagline vary per page
* Right sidebar restored (Resources + System + quote) in a 1fr + 280px grid
* Buttons read as buttons in both light panels and the dark hero (ghost variant + on-dark override)
* Shortcode reference cards gain proper panel-body inset
* New: city → lat/lon/IANA-tz helper on the Shortcodes page (server-side proxy to app.astroway.info atlas + click-to-copy snippet)
* Type tokens drop bundled webfont declarations — pure ui-sans-serif/system-ui stack, multi-script coverage via OS fonts
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
* Settings page fully redesigned with an observatory aesthetic — hero with owl-moon logo, four numbered panels (API key / Connection / Cache / Shortcodes), right-side Resources / System / quote aside, paper-warm cards with gold hairlines
* Shortcode rows copy-to-clipboard on click
* ASTROWAY_WP_PLUGIN_VERSION constant auto-derived from the plugin header via get_file_data — single source of truth, simpler release pipeline

= 0.2.0 =
* Settings page under Settings → AstroWay (API key input, Verify Key, Test Connection, Cache controls, shortcode reference, Diagnostics)
* API key field accepts both sandbox (aw_test_*) and live (aw_live_*) keys
* All keyed requests now carry X-Api-Key + X-AstroWay-Site-URL headers (lazy domain bind support)
* Plugin row links: Settings + Get API Key (with ?source=wp_plugin)
* Activation notice CTA URL includes ?source=wp_plugin for referrer-source tracking
* New transient cache layer (prefix astroway_v1_) with purge button + stats
* Verify Key gracefully falls back to /v1/keys/usage until api ships /v1/auth/keys/me (Block A)

= 0.1.0 =
* MVP — 5 iframe shortcodes via /v1/embed/* (works without API key, 30/hr per IP)
* 5 Gutenberg blocks with ServerSideRender
* Vanilla CSS theme-overridable via CSS vars (--astroway-*)
* i18n .pot + EN/UK/DE/PL base translations
* Graceful 429 handler with 'Get free API key' admin notice

= 0.1.0-alpha.1 =
* Initial scaffold (pre-release). Plugin header + PSR-4 autoload + activation hooks. No shortcodes yet — first functional release planned as 0.1.0.

== Upgrade Notice ==

= 0.3.0 =
Adds a live Status panel on the API Key admin page (plan / credits / period / domain) and a `lang` attribute on all shortcodes and Gutenberg blocks for 21 api-supported locales. Drop-in upgrade.

= 0.2.3 =
Admin UI polish — unified hero, restored sidebar, city search on the Shortcodes page, 20 bundled translations. Drop-in upgrade.

= 0.2.2 =
Admin split into 3 submenu pages (API Key / Settings / Shortcodes). Drop-in upgrade.

= 0.2.1 =
Settings page moves to a top-level admin menu with a brand icon. Drop-in upgrade.

= 0.2.0 =
Adds Settings page for API key configuration. Drop-in upgrade from 0.1.0 — anonymous mode shortcodes continue to work.

= 0.1.0 =
First functional release. 5 shortcodes + 5 Gutenberg blocks work without an API key in anonymous mode (30 requests/hour per IP).

= 0.1.0-alpha.1 =
Pre-release scaffold. Not for production use.
