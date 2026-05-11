=== AstroWay ===
Contributors: astrowayteam
Tags: astrology, natal chart, horoscope, tarot, human design
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Natal charts, synastry, transits, Tarot, Numerology, Human Design, AI horoscopes — shortcodes + Gutenberg blocks.

== Description ==

Add astrological calculations to any WordPress page or post via shortcodes or Gutenberg blocks. Powered by api.astroway.info — 700+ endpoints covering Western, Vedic, Hellenistic, Chinese, Mayan astrology + Tarot (Rider-Waite, Marseille, Lenormand) + Numerology (Pythagorean, Chaldean, Kabbalistic, Tamil) + Human Design + AI horoscopes.

**Works without an API key** in anonymous mode (30 requests/hour per visitor IP). Get a free API key at api.astroway.info/dashboard/sign-up for 10,000 credits/month and higher rate limits.

== Installation ==

1. Upload the plugin via Plugins → Add New, or unzip into `/wp-content/plugins/`.
2. Activate through the Plugins menu.
3. (Optional) Settings → AstroWay → enter your API key.
4. Add a shortcode to any page: `[astroway_natal date="1990-05-15" time="14:30" city="Kyiv"]`.

== Frequently Asked Questions ==

= Do I need an API key? =

No. The plugin works without a key in anonymous mode (30 requests/hour per visitor IP). For higher limits and Pro features, get a free key at api.astroway.info.

= What data is sent to api.astroway.info? =

Birth data (date, time, city, coordinates) for chart calculation. No data is stored on your site beyond standard WP caching.

= Can I use this on multiple sites? =

Yes for the free anonymous tier. Paid API keys are bound to a single domain by default; contact support to migrate.

== Changelog ==

= 0.1.0 =
* Initial scaffold.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
