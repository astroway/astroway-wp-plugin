<?php
/**
 * Admin → API Key page (default landing under AstroWay top-level menu).
 *
 * @var string     $api_key      Current saved API key (may be empty).
 * @var array|null $status_data  Pre-fetched /v1/auth/keys/me response (null if no key).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_option_key    = \AstroWay\WPPlugin\Admin::OPTION_KEY;
$astroway_page_slug     = \AstroWay\WPPlugin\Admin::PAGE_API_KEY;
$astroway_current_slug  = $astroway_page_slug;
$astroway_canonical_url = 'https://api.astroway.info/pricing';

$astroway_hero_title   = __( 'Getting started', 'astroway' );
$astroway_hero_tagline = __( 'Paste a shortcode into a page. No account, no key, nothing to configure.', 'astroway' );

// Shown in step 01 and copied by the button next to it.
$astroway_first_shortcode = '[astroway_daily_horoscope sign="leo"]';
$astroway_more_shortcodes = [
	[ '[astroway_moon_phase]', __( 'Tonight\'s Moon phase. Takes no parameters at all.', 'astroway' ) ],
	[ '[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52" tz="Europe/Kyiv"]', __( 'A birth chart: date, time and place.', 'astroway' ) ],
];
$astroway_shortcodes_url  = admin_url( 'admin.php?page=' . \AstroWay\WPPlugin\Admin::PAGE_SHORTCODES );

// Status panel state machine — derived from $status_data (passed from render_api_key_page).
$astroway_status_state   = 'none';     // none | valid | suspended | revoked | invalid_key | api_down
$astroway_status_payload = [];
$astroway_status_partial = false;
if ( '' !== $api_key && is_array( $status_data ) ) {
	$astroway_status_partial = ! empty( $status_data['fallback'] );
	$astroway_http_code      = (int) ( $status_data['status'] ?? 0 );
	if ( 200 === $astroway_http_code ) {
		$astroway_status_payload = (array) ( $status_data['data']['data'] ?? [] );
		$astroway_key_status     = (string) ( $astroway_status_payload['status'] ?? 'active' );
		if ( 'suspended' === $astroway_key_status ) {
			$astroway_status_state = 'suspended';
		} elseif ( 'revoked' === $astroway_key_status ) {
			$astroway_status_state = 'revoked';
		} else {
			$astroway_status_state = 'valid';
		}
	} elseif ( 401 === $astroway_http_code ) {
		$astroway_status_state = 'invalid_key';
	} else {
		$astroway_status_state = 'api_down';
	}
}
?>
<div class="wrap aw-app">

	<?php // WP moves admin notices here; without the marker they land inside our hero. ?>
	<hr class="wp-header-end">

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-hero.php'; ?>

	<div class="aw-grid">

	<main class="aw-main">

		<article class="aw-panel" data-num="01">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">01</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Your first widget', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'copy · paste · open the page', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<ol class="aw-steps">
					<li class="aw-step">
						<p class="aw-step-title"><?php esc_html_e( 'Copy a shortcode.', 'astroway' ); ?></p>
						<button type="button"
							class="aw-sc-code aw-sc-code--block"
							data-copy="<?php echo esc_attr( $astroway_first_shortcode ); ?>"
							title="<?php esc_attr_e( 'Click to copy', 'astroway' ); ?>">
							<code><?php echo esc_html( $astroway_first_shortcode ); ?></code>
							<span class="aw-sc-action" aria-hidden="true">
								<svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="9" height="9" rx="1.5"/><path d="M3 11V3a1 1 0 0 1 1-1h7"/></svg>
								<span class="aw-sc-action-text"><?php esc_html_e( 'copy', 'astroway' ); ?></span>
							</span>
						</button>
					</li>
					<li class="aw-step">
						<p class="aw-step-title"><?php esc_html_e( 'Paste it into any post or page.', 'astroway' ); ?></p>
						<p class="aw-hint"><?php esc_html_e( 'In the block editor you can type /astroway instead and pick the block: the preview shows the finished card, not a placeholder.', 'astroway' ); ?></p>
					</li>
					<li class="aw-step">
						<p class="aw-step-title"><?php esc_html_e( 'Open the page.', 'astroway' ); ?></p>
						<p class="aw-hint"><?php esc_html_e( 'The reading is already in the HTML, in your theme\'s fonts and colours. Nothing below this panel is required to make it work.', 'astroway' ); ?></p>
					</li>
				</ol>

				<div class="aw-steps-more">
				<p class="aw-step-more"><?php esc_html_e( 'Two more to try:', 'astroway' ); ?></p>
				<?php foreach ( $astroway_more_shortcodes as $astroway_more ) : ?>
					<button type="button"
						class="aw-sc-code aw-sc-code--block"
						data-copy="<?php echo esc_attr( $astroway_more[0] ); ?>"
						title="<?php esc_attr_e( 'Click to copy', 'astroway' ); ?>">
						<code><?php echo esc_html( $astroway_more[0] ); ?></code>
						<span class="aw-sc-action" aria-hidden="true">
							<svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="9" height="9" rx="1.5"/><path d="M3 11V3a1 1 0 0 1 1-1h7"/></svg>
							<span class="aw-sc-action-text"><?php esc_html_e( 'copy', 'astroway' ); ?></span>
						</span>
					</button>
					<p class="aw-hint aw-step-caption"><?php echo esc_html( $astroway_more[1] ); ?></p>
				<?php endforeach; ?>

				<p class="aw-hint">
					<?php
					printf(
						/* translators: %s is a link to the plugin's Shortcodes admin page */
						esc_html__( 'All 16 shortcodes with their parameters, plus a city search that fills in coordinates and timezone: %s.', 'astroway' ),
						'<a href="' . esc_url( $astroway_shortcodes_url ) . '">' . esc_html__( 'Shortcodes', 'astroway' ) . '</a>'
					);
					?>
				</p>
				</div>
			</div>
		</article>

		<article class="aw-panel" data-num="02">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">02</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Your API key', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'optional · paste · verify · save', 'astroway' ); ?></span>
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
					<?php esc_html_e( 'Paste a key from api.astroway.info/dashboard, or leave the field empty. Every widget renders without one. If you do save a key, your server offers it on the calls behind the page-rendered widgets, so a paid plan counts them against its own allowance rather than the one this site shares with every other anonymous caller. Saving a key also adds the status panel below, and the key itself is what you use when you call the API from your own code.', 'astroway' ); ?>
				</p>
				<div id="aw-key-status" class="aw-result" style="display:none;"></div>
				<div class="aw-panel-actions">
					<?php submit_button( __( 'Save changes', 'astroway' ), 'aw-btn aw-btn-primary', 'submit', false ); ?>
				</div>
			</form>
		</article>

		<?php if ( 'none' !== $astroway_status_state ) : ?>
		<article class="aw-panel aw-panel--status" data-num="status" data-state="<?php echo esc_attr( $astroway_status_state ); ?>">
			<header class="aw-panel-head">
				<span class="aw-panel-num aw-panel-num--status" aria-hidden="true">
					<?php
					// Status icon — green dot for valid, red for suspended/revoked/api_down, yellow for invalid_key.
					$astroway_dot_class = ( 'valid' === $astroway_status_state ) ? 'is-ok' : ( ( 'invalid_key' === $astroway_status_state ) ? 'is-warn' : 'is-err' );
					?>
					<span class="aw-dot <?php echo esc_attr( $astroway_dot_class ); ?>" aria-hidden="true"></span>
				</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Status', 'astroway' ); ?></h2>
				<span class="aw-panel-hint">
					<?php
					switch ( $astroway_status_state ) {
						case 'valid':
							esc_html_e( 'live · key authenticated', 'astroway' );
							break;
						case 'suspended':
							esc_html_e( 'suspended, contact support', 'astroway' );
							break;
						case 'revoked':
							esc_html_e( 'revoked, re-issue key', 'astroway' );
							break;
						case 'invalid_key':
							esc_html_e( 'invalid key', 'astroway' );
							break;
						case 'api_down':
							esc_html_e( 'unreachable, check connection', 'astroway' );
							break;
					}
					?>
				</span>
			</header>
			<div class="aw-panel-body">
			<?php if ( 'valid' === $astroway_status_state || 'suspended' === $astroway_status_state || 'revoked' === $astroway_status_state ) : ?>
				<dl class="aw-status-grid">
					<?php if ( ! empty( $astroway_status_payload['key_prefix'] ) ) : ?>
					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Key', 'astroway' ); ?></dt>
						<dd><code><?php echo esc_html( $astroway_status_payload['key_prefix'] ); ?></code></dd>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $astroway_status_payload['plan'] ) ) : ?>
					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Plan', 'astroway' ); ?></dt>
						<dd><span class="aw-pill aw-pill--<?php echo esc_attr( $astroway_status_payload['plan'] ); ?>"><?php echo esc_html( $astroway_status_payload['plan'] ); ?></span></dd>
					</div>
					<?php endif; ?>

					<?php if ( isset( $astroway_status_payload['credits_remaining'] ) && isset( $astroway_status_payload['credits_total_this_period'] ) ) : ?>
					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Credits', 'astroway' ); ?></dt>
						<dd>
							<?php
							$astroway_credits_remaining = (int) $astroway_status_payload['credits_remaining'];
							$astroway_credits_total     = (int) $astroway_status_payload['credits_total_this_period'];
							$astroway_credits_pct       = $astroway_credits_total > 0 ? round( ( $astroway_credits_remaining / $astroway_credits_total ) * 100 ) : 0;
							printf(
								'%s / %s <span class="aw-status-sub">(%d%%)</span>',
								esc_html( number_format_i18n( $astroway_credits_remaining ) ),
								esc_html( number_format_i18n( $astroway_credits_total ) ),
								(int) $astroway_credits_pct
							);
							?>
						</dd>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $astroway_status_payload['period_end'] ) ) : ?>
					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Period ends', 'astroway' ); ?></dt>
						<dd>
							<?php
							$astroway_period_ts = strtotime( (string) $astroway_status_payload['period_end'] );
							if ( $astroway_period_ts ) {
								$astroway_diff = $astroway_period_ts - time();
								$astroway_rel  = $astroway_diff > 0
									? sprintf( /* translators: %s = relative time. */ esc_html__( 'in %s', 'astroway' ), human_time_diff( time(), $astroway_period_ts ) )
									: esc_html__( 'expired', 'astroway' );
								printf(
									'%s <span class="aw-status-sub">%s</span>',
									esc_html( gmdate( 'Y-m-d', $astroway_period_ts ) ),
									wp_kses_post( $astroway_rel )
								);
							}
							?>
						</dd>
					</div>
					<?php endif; ?>

					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Domain', 'astroway' ); ?></dt>
						<dd>
							<?php if ( ! empty( $astroway_status_payload['domain'] ) ) : ?>
								<code><?php echo esc_html( $astroway_status_payload['domain'] ); ?></code>
								<?php if ( ! empty( $astroway_status_payload['domain_bound_at'] ) ) : ?>
									<span class="aw-status-sub">
									<?php
										printf(
											/* translators: %s = relative time the key was bound to this domain. */
											esc_html__( 'bound %s ago', 'astroway' ),
											esc_html( human_time_diff( strtotime( (string) $astroway_status_payload['domain_bound_at'] ) ) )
										);
									?>
									</span>
								<?php endif; ?>
							<?php else : ?>
								<em class="aw-status-sub"><?php esc_html_e( 'not bound: the first request binds this site', 'astroway' ); ?></em>
							<?php endif; ?>
						</dd>
					</div>

					<?php if ( ! empty( $astroway_status_payload['referrer_source'] ) ) : ?>
					<div class="aw-status-row">
						<dt><?php esc_html_e( 'Source', 'astroway' ); ?></dt>
						<dd><code><?php echo esc_html( $astroway_status_payload['referrer_source'] ); ?></code></dd>
					</div>
					<?php endif; ?>
				</dl>

				<?php if ( $astroway_status_partial ) : ?>
					<p class="aw-hint"><em><?php esc_html_e( 'Limited data: the connected api does not yet expose /v1/auth/keys/me. Plan and credits shown via legacy fallback.', 'astroway' ); ?></em></p>
				<?php endif; ?>
			<?php elseif ( 'invalid_key' === $astroway_status_state ) : ?>
				<p><?php esc_html_e( 'The saved key was rejected by api.astroway.info (HTTP 401). Re-paste a valid key above, or clear the field to revert to anonymous mode.', 'astroway' ); ?></p>
			<?php elseif ( 'api_down' === $astroway_status_state ) : ?>
				<p><?php esc_html_e( 'Could not reach api.astroway.info. Network or upstream issue, try again in a moment.', 'astroway' ); ?></p>
			<?php endif; ?>
			</div>
		</article>
		<?php endif; ?>

		<article class="aw-panel" data-num="03">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">03</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'What a key adds', 'astroway' ); ?></h2>
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
							<th scope="row"><?php esc_html_e( 'Widgets in this plugin', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'all of them', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'all of them', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'all of them', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'API requests / month', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td><?php esc_html_e( '10,000', 'astroway' ); ?></td>
							<td><?php esc_html_e( '50K to 3.5M', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Rate limit, keyed endpoints', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td><?php esc_html_e( '~60 requests/min', 'astroway' ); ?></td>
							<td><?php esc_html_e( '30 to 1000 requests/min', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Dashboard, usage history', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'AI interpretations', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Support', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'community', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'priority', 'astroway' ); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Spend cap', 'astroway' ); ?></th>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td><?php esc_html_e( 'none', 'astroway' ); ?></td>
							<td class="aw-compare-yes" aria-label="<?php esc_attr_e( 'included', 'astroway' ); ?>">✓</td>
						</tr>
					</tbody>
				</table>
				<p class="aw-compare-footnote">
					<?php
					printf(
						/* translators: %s is a link to the canonical pricing page on api.astroway.info */
						esc_html__( 'Every row below the first one is about calling api.astroway.info from your own code. The widgets on your pages are not affected by any of it. Canonical pricing → %s', 'astroway' ),
						'<a href="' . esc_url( $astroway_canonical_url ) . '" target="_blank" rel="noopener">api.astroway.info/pricing</a>'
					);
					?>
				</p>
			</div>
		</article>

		<article class="aw-panel" data-num="04">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">04</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'How it works', 'astroway' ); ?></h2>
			</header>
			<div class="aw-panel-body aw-how">
				<p>
					<strong><?php esc_html_e( 'On your pages.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'Your server renders seven widgets into the page, counted against this site\'s allowance of 300 requests an hour, or against your own plan when a paid key is saved above. The rest load in a frame against each visitor\'s own 30, and those carry the "Powered by AstroWay" watermark: a frame is fetched by the visitor\'s browser, which is no place for your key.', 'astroway' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Free key.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'A personal bucket of 10,000 calls a month for code you write yourself, plus a dashboard with activity history. It does not change what the shortcodes render, and the watermark on framed widgets stays: the key is not part of the frame\'s address.', 'astroway' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Paid key.', 'astroway' ); ?></strong>
					<?php esc_html_e( 'Higher limits and more calls on api.astroway.info, AI-written interpretations, priority support, and a spend cap against bill spikes. The page-rendered widgets on this site draw on that plan too, instead of the shared anonymous allowance. Widgets that need a paid endpoint will say so on the shortcode; none of the current ones do.', 'astroway' ); ?>
				</p>
			</div>
		</article>

		<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-channel-b-status.php'; ?>

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
