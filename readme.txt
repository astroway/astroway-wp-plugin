=== AstroWay ===
Contributors: astrowayteam
Tags: astrology, natal chart, horoscope, tarot, human design
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.0
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

1. Settings screen — paste your API key, verify in one click, see plan/credits/rate-limit at a glance. The page also lists the five shortcodes ready to copy into any post or page.
2. Natal chart widget on the frontend, rendered via `[astroway_natal date="…" time="…" lat="…" lon="…"]`. Includes zodiac wheel, planets with retrograde markers, and major aspects.
3. Moon phase widget on the frontend via `[astroway_moon_phase]` — anonymous-mode example, no API key required, 30 requests/hour/IP.
4. Daily tarot widget via `[astroway_tarot_card deck="rider-waite"]` — fresh card pulled each day with name and meaning.
5. Gutenberg block picker showing all five AstroWay blocks (Natal Chart, Human Design Bodygraph, Daily Horoscope, Moon Phase, Daily Tarot) with live ServerSideRender preview in the editor.

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

= 0.2.0 =
Adds Settings page for API key configuration. Drop-in upgrade from 0.1.0 — anonymous mode shortcodes continue to work.

= 0.1.0 =
First functional release. 5 shortcodes + 5 Gutenberg blocks work without an API key in anonymous mode (30 requests/hour per IP).

= 0.1.0-alpha.1 =
Pre-release scaffold. Not for production use.
