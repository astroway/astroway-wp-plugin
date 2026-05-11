# Changelog

All notable changes to `astroway/wp-plugin` are documented here.

## [0.1.0-alpha.1] — 2026-05-11

Pre-release scaffold. Not for production use.

- Plugin header file, activation/deactivation hooks
- PSR-4 autoload (`AstroWay\WPPlugin\` namespace)
- Composer dev deps: WPCS, PHPUnit
- WP plugin metadata (readme.txt, EditorConfig)
- Release infra preview: `_unreleased/`-driven release queue in staging repo (MCP-style)

First functional release planned as `0.1.0` ships W1 MVP (5 iframe-based shortcodes + 5 Gutenberg blocks via `/v1/embed/*`).
