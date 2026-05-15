# Changelog

All notable changes to `astroway/wp-plugin` are documented here.

## [0.3.0] — 2026-05-15

### Added
- **Live Status panel** on the API Key admin landing — pulls `GET /v1/auth/keys/me` on page load (TTL_KEYS_ME 30min transient), surfaces plan badge with tier-coloured pill, credits remaining / total with percentage, period_end with relative countdown, bound domain with "bound N days ago" caption, referrer source. State machine: `valid` / `suspended` / `revoked` / `invalid_key` / `api_down` with per-state border accent.
- **`lang` attribute on all 5 shortcodes** — `[astroway_natal date="…" lang="ru"]`, `[astroway_daily_horoscope sign="leo" lang="hi"]`, etc. Default = site locale via `Plugin::normalize_locale(get_locale())`; invalid codes silently fall back.
- **Inspector dropdown on all 5 Gutenberg blocks** — 21 locale options (uk, en, de, ru, pl, es, pt, fr, it, nl, cs, ro, hu, el, tr, ar, hi, ja, ko, vi, id), native script labels per WP Polyglots convention.
- **`Plugin::SUPPORTED_LANGS` const + `Plugin::normalize_locale()` helper** — single source of truth for the 21-locale whitelist; `WP locale` (`uk_UA`, `pt_BR`) → short api code (`uk`, `pt`).
- **`'lang'` added to every widget's `params` whitelist** in `RendererDecisions` — propagates to api as `?lang={code}` query on `/v1/embed/*` URLs.

### Requires
- api.astroway.info v2.33.0+ for the Status panel `/v1/auth/keys/me` endpoint. Falls back to legacy `/v1/keys/usage` with "Limited data" notice if a self-hosted api is older.

### Backwards compatibility
- No breaking changes. Shortcodes without `lang=` continue to render in site default locale exactly as before.
- Status panel renders only when a key is configured; anonymous-mode sites unchanged.
- 30-min cache + invalidation on key change means no extra api calls on repeated admin visits.

## [0.2.3] — 2026-05-14 (shipped to wp.org)

### Changed
- **Hero parity** across all 3 subpages: extracted shared hero partial (`includes/views/partials/admin-hero.php`) — Settings and Shortcodes now render the same brand mark + version eyebrow + status badge + CTAs as the API Key landing. Title and tagline are the only per-page variables.
- **Neutral system font stack** replaces declared `Fraunces` (serif, no CJK/Arabic coverage) and `Plus Jakarta Sans`. `--aw-display` / `--aw-body` / `--aw-mono` now resolve to `ui-sans-serif` / `system-ui` / OS-native fonts. No bundled web fonts (GDPR-friendly, full multi-script via OS fallback).
- **Right sidebar restored** as in v0.2.1, minus the decorative quote: `Resources` links + small `System` box. Lives in `includes/views/partials/admin-sidebar.php`, included by all 3 page views inside the original `.aw-grid` (main + 280px aside).
- **Buttons read as buttons:** `.aw-btn-ghost` gets stronger border (`--aw-ink-mute`), pure white background, subtle shadow + lift on hover. Dark-hero variant `.aw-hero .aw-btn-ghost` switches to translucent white border / cosmos-text color so the `View paid plans` CTA stays readable on the dark hero.
- **Shortcode reference cards** now wrap content in `.aw-panel-body` so they get the same `20px 28px 24px` inset as panels on the other pages.

### Added
- **City → lat / lon / IANA tz helper** on the Shortcodes page (`#aw-tz-helper` panel above the reference cards). Server-side AJAX proxy `astroway_atlas_search` (capability-gated, nonce-protected, 24h transient cache) calls `https://app.astroway.info/api/atlas/search`. Click a result → ready-to-paste `lat="..." lon="..." tz="..."` snippet is copied to the clipboard. Reference link to the Wikipedia IANA tz list for users who prefer manual lookup.
- **Bundled translations for 20 locales**: uk, de_DE, ru_RU, pl_PL, es_ES, pt_BR, hi_IN, fr_FR, ko_KR, it_IT, ja, id_ID, tr_TR, nl_NL, ro_RO, cs_CZ, vi, ar (RTL), el, hu_HU. Generated via the AstroWay AI gateway's 14-provider fallback chain; DeepL kept as reserve. Source generator in `_dev/generate-translations.py` (gitignored, dev-only).
- Explicit `load_plugin_textdomain('astroway', false, …/languages)` on `init` so bundled `.mo` files load before WordPress 6.x just-in-time loader runs.

### Fixed
- **Translation filenames** corrected from `astroway-wp-plugin-{locale}.mo` to `astroway-{locale}.mo`. WordPress loads bundled `.mo` as `{textdomain}-{locale}.mo` — the old naming silently fell through to English. `.pot` renamed to `astroway.pot` for consistency.
- **DOMContentLoaded race** in shortcodes JS — switched to a `ready()` helper that handles both `loading` and post-`complete` document states. Without this, the helper widget bindings missed the event when WP enqueues the script after document load.
- **Gold CTA invisible:** `.aw-btn-gold`, `.aw-btn-ghost`, `.aw-btn-primary` rules had specificity (0,1,0) but the base `.aw-app .aw-btn` rule that resets background to `transparent` had (0,2,0) and silently won — leaving the gold `Get free API key` button rendered with no background on the dark hero. Bumped all button variants to `.aw-app .aw-btn-*` and the dark-hero override to `.aw-app .aw-hero .aw-btn-ghost`.

### Removed
- "As above, so below — Hermes Trismegistus" decorative quote from the sidebar. Filler from the v0.2.1 observatory aesthetic; out of place in a WP plugin admin sidebar.
- "Site ID" row (first 12 chars of `md5(home_url())`) from sidebar System box and Settings → System diagnostic table. Added in v0.2.1 as a "support identifier" but never used — not sent in any API request, not consumed by api side, not referenced anywhere outside the two display points. URL is already obvious from the address bar.

### Internal
- `_dev/generate-translations.py` now merges with existing `.po` so re-runs translate only the new (empty) msgstr entries, preserving prior translations.
- `.pot` regenerated from current source (~178 msgids after the quote removal).
- Plugin header `* Version:` bumped from `0.2.0` → `0.2.3` to match the highest queued release. `ASTROWAY_WP_PLUGIN_VERSION` is auto-derived from this header at runtime via `get_file_data()`, so sandbox screenshots and runtime version checks now reflect the actual queued work instead of always reading `0.2.0`. Going forward: bump header on the first commit after `_unreleased/vNEXT/` is queued. Manifest `plugin_header_patch.Version` becomes a no-op safety net at ship time.

## [0.2.2] — 2026-05-13 (internal dev snapshot, rolled into 0.2.3 ship)

### Changed
- Admin UI split into 3 submenu pages: **API Key** (default landing), **Settings**, **Shortcodes**.
- API Key landing rebuilt with hero state badge, key form, 3-tier comparison table (Anonymous / Free key / Paid), how-it-works paragraphs, prominent CTAs (`Get free API key`, `View paid plans`).
- Settings page now focuses on diagnostics — Connection ping, Cache stats + Purge all, read-only System table with **Copy diagnostic info** button.
- Shortcodes page rebuilt as expanded reference cards — per shortcode: tag chip, title, description, copyable example, parameters table, Gutenberg block hint.

### Added
- Shared footer nav strip on every subpage (API Key · Settings · Shortcodes · Docs ↗).
- Hidden textarea-backed `Copy diagnostic info` (clipboard fallback for non-HTTPS local dev).

### Removed
- Monolithic right aside (Resources / System / Quote) — Resources merged into footer nav, System split into Settings → System panel, Quote dropped.

### Internal
- `assets/js/astroway-admin.js` split into 3 page-specific files (`-api-key.js`, `-settings.js`, `-shortcodes.js`). Each page loads only what it needs.
- `class-admin.php`: added `PAGE_SETTINGS` and `PAGE_SHORTCODES` slug constants; `PAGE_SLUG` kept as backward-compat alias of `PAGE_API_KEY`. Three render callbacks plus hook-aware asset enqueue.

## [0.2.0] — 2026-05-12

Settings page + free key flow foundation.

- Settings page under `Settings → AstroWay` with API key input, Verify Key button, Test Connection, Cache controls, shortcode reference, and Diagnostics panel
- API key field accepts both sandbox (`aw_test_*`) and live (`aw_live_*`) keys — api routes them internally
- All keyed requests now carry `X-Api-Key` + `X-AstroWay-Site-URL` headers (PRICING §4.6 lazy domain bind signal)
- Plugin row links: Settings + Get API Key (with `?source=wp_plugin`)
- Activation notice CTA URL includes `?source=wp_plugin` for `referrer_source` persistence
- New transient cache layer (prefix `astroway_v1_`) with purge button + stats
- Verify Key gracefully falls back to existing `/v1/keys/usage` endpoint until api ships `/v1/auth/keys/me` (Block A)

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
