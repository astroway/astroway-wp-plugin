<?php
/**
 * Shared hero block for all 3 admin subpages.
 *
 * @var string $astroway_hero_title    Page H1.
 * @var string $astroway_hero_tagline  Subtitle below H1.
 * @var string $api_key                Current saved API key (may be empty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_logo_url      = ASTROWAY_WP_PLUGIN_URL . 'assets/img/logo-owl-moon.png';
$astroway_has_key       = '' !== $api_key;
$astroway_signup_url    = 'https://api.astroway.info/dashboard/sign-up?source=wp_plugin';
$astroway_pricing_url   = 'https://api.astroway.info/pricing?source=wp_plugin';
?>
<header class="aw-hero" role="banner">
	<div class="aw-hero-stars" aria-hidden="true"></div>
	<div class="aw-hero-grain" aria-hidden="true"></div>

	<div class="aw-hero-inner">
		<div class="aw-hero-brand">
			<div class="aw-moon-orb">
				<span class="aw-moon-glow" aria-hidden="true"></span>
				<img src="<?php echo esc_url( $astroway_logo_url ); ?>" alt="" class="aw-owl" width="92" height="92" />
			</div>
			<div class="aw-wordmark">
				<p class="aw-eyebrow"><?php esc_html_e( 'astroway · plugin · v', 'astroway' ); ?><?php echo esc_html( ASTROWAY_WP_PLUGIN_VERSION ); ?></p>
				<h1 class="aw-title"><?php echo esc_html( $astroway_hero_title ); ?></h1>
				<p class="aw-tagline"><?php echo esc_html( $astroway_hero_tagline ); ?></p>
			</div>
		</div>

		<div class="aw-hero-status">
			<?php if ( $astroway_has_key ) : ?>
				<div class="aw-status is-on">
					<span class="aw-status-dot" aria-hidden="true"></span>
					<div class="aw-status-text">
						<span class="aw-status-label"><?php esc_html_e( 'Authenticated', 'astroway' ); ?></span>
						<span class="aw-status-detail"><?php esc_html_e( 'API key configured', 'astroway' ); ?></span>
					</div>
				</div>
			<?php else : ?>
				<div class="aw-status is-off">
					<span class="aw-status-dot" aria-hidden="true"></span>
					<div class="aw-status-text">
						<span class="aw-status-label"><?php esc_html_e( 'Anonymous mode', 'astroway' ); ?></span>
						<span class="aw-status-detail"><?php esc_html_e( '30 requests / hour / visitor IP', 'astroway' ); ?></span>
					</div>
				</div>
				<div class="aw-hero-ctas">
					<a class="aw-btn aw-btn-gold" href="<?php echo esc_url( $astroway_signup_url ); ?>" target="_blank" rel="noopener">
						<span><?php esc_html_e( 'Get free API key', 'astroway' ); ?></span>
						<svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
					</a>
					<a class="aw-btn aw-btn-ghost" href="<?php echo esc_url( $astroway_pricing_url ); ?>" target="_blank" rel="noopener">
						<span><?php esc_html_e( 'View paid plans', 'astroway' ); ?></span>
						<svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>
