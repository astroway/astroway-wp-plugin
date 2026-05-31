# AstroWay WordPress Plugin — astrology API for natal charts, Tarot & Human Design

> Official WordPress plugin for the [AstroWay API](https://api.astroway.info) — natal charts, synastry, transits, Vedic dashas, Tarot (Rider-Waite / Marseille / Lenormand), Numerology, Human Design, AI horoscopes. Shortcodes + Gutenberg blocks.

[![license: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

700+ endpoints exposed as WordPress shortcodes and Gutenberg blocks. Hybrid rendering: heavy widgets (natal wheel, HD bodygraph, Vedic kundli) via iframe from `/v1/embed/*`, light widgets (daily horoscope, moon phase, daily tarot, reference data) via client-side JSON fetch from `/v1/public/*` and `/v1/*` with API key.

Works **out of the box** without API key (anonymous tier, 30 requests/hour per visitor IP). Get a free API key at <https://api.astroway.info/dashboard/sign-up> for **10,000 credits/month** and higher per-key rate limits.

---

## Install

### From wp.org plugin directory (recommended)

`WordPress Admin → Plugins → Add New → search "AstroWay"` → Install → Activate.

### From ZIP (paid tier early access)

Download the latest release ZIP from [releases](https://github.com/astroway/astroway-wp-plugin/releases) → upload via `Plugins → Add New → Upload Plugin`.

---

## Quick start

After activation:

1. (Optional) Go to `Settings → AstroWay` to enter your API key. Without a key the plugin works in anonymous mode with 30 requests/hour per visitor IP.
2. Add a shortcode or Gutenberg block to any page or post.

### Shortcode examples

```
[astroway_natal date="1990-05-15" time="14:30" city="Kyiv, Ukraine"]
[astroway_daily_horoscope sign="scorpio"]
[astroway_moon_phase]
[astroway_bodygraph date="1990-05-15" time="14:30" city="Kyiv, Ukraine"]
[astroway_tarot_card type="daily"]
```

### Gutenberg blocks

Open any post in the block editor → click `+` → search "AstroWay" → choose:

- **AstroWay Natal Chart** — interactive wheel
- **AstroWay Daily Horoscope** — sign selector
- **AstroWay Moon Phase** — current phase widget
- **AstroWay HD Bodygraph** — Human Design bodygraph
- **AstroWay Tarot Daily Card** — deterministic per-date draw

More blocks ship with v1.1 (Tarot full decks, Numerology, Destiny Matrix) and v1.2 (Synastry, Transits, AI Chat — Pro tier).

---

## Tiers

| Feature | Anonymous (no key) | Free key (10k credits/mo) | Paid (Indie / Starter / Pro) |
|---|---|---|---|
| Basic widgets (natal, horoscope, tarot daily) | ✓ (via iframe) | ✓ (native render) | ✓ |
| Per-visitor rate limit | shared 30/hr per IP | per-key | per-key (higher) |
| Watermark "Powered by AstroWay" | shown | shown (plan=free) | removed |
| Heavy widgets (HD, kundli, advanced) | iframe only | iframe + native option | full native |
| Synastry / Transits | — | — | ✓ |
| AI Chat (streaming) | — | — | ✓ |
| PDF chart export | — | — | ✓ |

Get a paid key at [astroway.info/shop](https://astroway.info/shop/) starting from Indie ($5 / 50k credits/month).

---

## Privacy & data handling

When users interact with the plugin's widgets, birth data (date / time / city / coordinates) is sent to `api.astroway.info` for chart calculation. No data is stored on your WordPress site beyond standard WP caching (transients, 1h–24h TTL).

The plugin shows an activation consent dialog and exposes a privacy policy link in `readme.txt`. You are responsible for adding `api.astroway.info` to your site's privacy policy if applicable in your jurisdiction.

---

## Requirements

- WordPress 5.0+
- PHP 7.4+ (tested up to 8.4)
- Internet connection (calls to `api.astroway.info`)

---

## Links

- API docs: <https://api.astroway.info/docs>
- Pricing: <https://astroway.info/shop/>
- Sign up: <https://api.astroway.info/dashboard/sign-up>
- Bug reports: <https://github.com/astroway/astroway-wp-plugin/issues>
- Contact: <astroway.info@gmail.com>
- Source: <https://github.com/astroway/astroway-wp-plugin>

---

## License

MIT — see [LICENSE](LICENSE).
