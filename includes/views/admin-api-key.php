<?php
/**
 * Admin → API Key page (default landing under AstroWay top-level menu).
 *
 * @var string $api_key  Current saved API key (may be empty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_option_key    = \AstroWay\WPPlugin\Admin::OPTION_KEY;
$astroway_page_slug     = \AstroWay\WPPlugin\Admin::PAGE_API_KEY;
$astroway_current_slug  = $astroway_page_slug;
$astroway_canonical_url = 'https://api.astroway.info/pricing';

$astroway_hero_title   = __( 'API Key', 'astroway' );
$astroway_hero_tagline = __( 'Higher limits, advanced widgets, no watermark.', 'astroway' );
?>
<div class="wrap aw-app">

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-hero.php'; ?>

	<div class="aw-grid">

	<main class="aw-main">

		<article class="aw-panel" data-num="01">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">01</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Your API key', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'paste · verify · save', 'astroway' ); ?></span>
			</header>
			<form method="post" action="options.php" class="aw-panel-body">
				<?php settings_fields( $astroway_page_slug ); ?>
				<label for="aw-api-key" class="aw-label"><?php esc_html_e( 'Paste your key', 'astroway' ); ?></label>
				<div class="aw-field-row">
					<input type="text"
						id="aw-api-key"
						name="<?php echo esc_attr( $astroway_option_key ); ?>[api_key]"
						value="<?php echo esc_attr( $api_key ); ?>"
						class="aw-input aw-input-mono"
						placeholder="aw_live_…  or  aw_test_…"
						autocomplete="off"
						spellcheck="false" />
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-verify-key">
						<?php esc_html_e( 'Verify', 'astroway' ); ?>
					</button>
				</div>
				<p class="aw-hint">
					<?php esc_html_e( 'Paste a key from api.astroway.info/dashboard. Leave empty to revert to Anonymous mode.', 'astroway' ); ?>
				</p>
				<div id="aw-key-status" class="aw-result" style="display:none;"></div>
				<div class="aw-panel-actions">
					<?php submit_button( __( 'Save changes', 'astroway' ), 'aw-btn aw-btn-primary', 'submit', false ); ?>
				</div>
			</form>
		</article>

		<article class="aw-panel" data-num="02">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">02</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'What you get', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( '3 tiers compared', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<table class="aw-compare">
					<thead>
						<tr>
							<th scope="col"></th>
							<th scope="col">
								<span class="aw-compare-tier"><?php esc_html_e( 'Anonymous', 'astroway' ); ?></span>
								<span class="aw-compare-sub"><?php esc_html_e( 'no setup', 'astroway' ); ?></span>
							</th>
							<th scope="col">
								<span class="aw-compare-tier"><?php esc_html_e( 'Free key', 'astroway' ); ?></span>
								<span class="aw-compare-sub"><?php esc_html_e( 'signup', 'astroway' ); ?></span>
							</th>
							<th scope="col" class="aw-compare-paid">
								<span class="aw-compare-tier"><?php esc_html_e( 'Paid', 'astroway' ); ?></span>
								<span class="aw-compare-sub"><?php esc_html_e( 'from $5/mo', 'astroway' ); ?></span>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Rate limit', 'astroway' ); ?></th>
							<td><?php esc_html_e( '30/hour/IP', 'astroway' ); ?></td>
							<td><?php esc_html_e( '~60/min/key', 'astroway' ); ?></td>
							<td><?php esc_html_e( '30–1000/min', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Credits / month', 'astroway' ); ?></th>
							<td>—</td>
							<td><?php esc_html_e( '10,000', 'astroway' ); ?></td>
							<td><?php esc_html_e( '50K – 3.5M', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Render mode', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'iframe', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'iframe', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'native', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Widgets', 'astroway' ); ?></th>
							<td><?php esc_html_e( '5 basic', 'astroway' ); ?></td>
							<td><?php esc_html_e( '5 basic', 'astroway' ); ?></td>
							<td><?php esc_html_e( '+ Tarot, HD, Synastry, Numerology', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'AI interpretations', 'astroway' ); ?></th>
							<td>—</td>
							<td>—</td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Watermark', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'shown', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'shown', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'removed', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Support', 'astroway' ); ?></th>
							<td>—</td>
							<td><?php esc_html_e( 'community', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'priority', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Spend cap', 'astroway' ); ?></th>
							<td>—</td>
							<td>—</td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
						</tr>
					</tbody>
				</table>
				<p class="aw-compare-footnote">
					<?php
					printf(
						/* translators: %s is a link to the canonical pricing page on api.astroway.info */
						esc_html__( 'Numbers shown for context. Canonical pricing → %s', 'astroway' ),
						'<a href="' . esc_url( $astroway_canonical_url ) . '" target="_blank" rel="noopener">api.astroway.info/pricing</a>'
					);
					?>
				</p>
			</div>
		</article>

		<article class="aw-panel" data-num="03">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">03</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'How it works', 'astroway' ); ?></h2>
			</header>
			<div class="aw-panel-body aw-how">
				<p>
					<strong><?php esc_html_e( 'No key.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'Plugin renders iframe widgets, rate-limited by visitor IP. Good for low-traffic sites and quick trials. The "Powered by AstroWay" watermark is shown.', 'astroway' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Free key.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'Personal credit bucket (10,000/month), isolated from other sites. Iframe rendering stays, but you get a dashboard with activity history. Watermark is still shown.', 'astroway' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Paid key.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'Native client-side rendering (no iframe), advanced widgets (Tarot decks, synastry, Human Design, Numerology), AI-written interpretations, and no watermark. Spend cap available to protect against bill spikes.', 'astroway' ); ?>
				</p>
			</div>
		</article>

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
