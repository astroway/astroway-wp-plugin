=== AstroWay ===
Contributors: astrowayteam
Tags: astrology, natal chart, horoscope, tarot, human design
Requires at least: 5.0
Tested up to: 6.5
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

Yes for the free anonymous tier. Paid API keys are bound to a single domain by default; contact support to migrate.

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
