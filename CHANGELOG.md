# Changelog

All notable changes to `astroway/wp-plugin` are documented here.

## [0.1.0] — 2026-05-12

First functional release. Adds five astrology widgets to any WordPress page or post — no API key required, no signup, no server-side calculation on your site.

### Shortcodes (5)

```
[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]
[astroway_daily_horoscope sign="aries"]
[astroway_moon_phase]
[astroway_bodygraph date="..." time="..." lat="..." lon="..."]
[astroway_tarot_card]
```

Each renders an embed iframe served by `api.astroway.info/v1/embed/*`. Rate limit: 30 requests/hour per visitor IP, enforced api-side.

### Gutenberg blocks (5)

- AstroWay: Natal Chart
- AstroWay: Daily Horoscope
- AstroWay: Moon Phase
- AstroWay: Human Design Bodygraph
- AstroWay: Daily Tarot

Each uses `ServerSideRender` so the editor shows a live preview.

### Other

- Vanilla CSS theme-overridable via CSS custom properties on `.astroway-embed`
- i18n `.pot` template (EN base; UK/DE/PL `.po`/`.mo` via astroway.info translation pipeline)
- Dismissible activation notice with link to `api.astroway.info/dashboard/sign-up`
- PSR-4 autoload (`AstroWay\WPPlugin\` namespace)

### Compatibility

- WordPress 5.0+ (Gutenberg required for blocks; classic editor users get shortcodes)
- PHP 7.4+ (tested through 8.4)

## [0.1.0-alpha.1] — 2026-05-11

Pre-release scaffold. Not for production use.

- Plugin header file, activation/deactivation hooks
- PSR-4 autoload (`AstroWay\WPPlugin\` namespace)
- Composer dev deps: WPCS, PHPUnit
- WP plugin metadata (readme.txt, EditorConfig)
- Release infra preview: `_unreleased/`-driven release queue in staging repo (MCP-style)

First functional release planned as `0.1.0` ships W1 MVP (5 iframe-based shortcodes + 5 Gutenberg blocks via `/v1/embed/*`).
