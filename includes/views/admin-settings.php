<?php
/**
 * Admin → Settings page (Connection + Cache + System).
 *
 * @var array  $stats   ['count' => int, 'bytes' => int] from Cache::stats()
 * @var string $api_key Current saved API key (may be empty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_current_slug = \AstroWay\WPPlugin\Admin::PAGE_SETTINGS;
$astroway_api_host     = wp_parse_url( ASTROWAY_API_BASE, PHP_URL_HOST );

$astroway_hero_title   = __( 'Settings', 'astroway' );
$astroway_hero_tagline = __( 'Connection, cache, diagnostics.', 'astroway' );

$astroway_opts         = (array) get_option( \AstroWay\WPPlugin\Admin::OPTION_KEY, [] );
$astroway_matrix       = \AstroWay\WPPlugin\Tier::matrix();
$astroway_current_tier = \AstroWay\WPPlugin\Tier::current();
$astroway_tier_columns = [ 'anonymous', 'free', 'indie', 'starter', 'pro', 'business' ];

// Current domain binding (from cached /me response if api key is set).
$astroway_current_domain = '';
if ( '' !== (string) ( $astroway_opts['api_key'] ?? '' ) ) {
	$astroway_client         = new \AstroWay\WPPlugin\ApiClient();
	$astroway_me             = $astroway_client->get_keys_me();
	$astroway_current_domain = (string) ( $astroway_me['data']['data']['domain'] ?? '' );
}

$astroway_diag = [
	[ __( 'Plugin', 'astroway' ), ASTROWAY_WP_PLUGIN_VERSION ],
	[ __( 'WordPress', 'astroway' ), get_bloginfo( 'version' ) . ( is_multisite() ? ' (multisite)' : '' ) ],
	[ __( 'PHP', 'astroway' ), PHP_VERSION ],
	[ __( 'WP_DEBUG', 'astroway' ), ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? __( 'on', 'astroway' ) : __( 'off', 'astroway' ) ],
	[ __( 'Active theme', 'astroway' ), wp_get_theme()->get( 'Name' ) ],
	[ __( 'wp_remote_post', 'astroway' ), function_exists( 'wp_remote_post' ) ? __( 'available', 'astroway' ) : __( 'missing', 'astroway' ) ],
	[ __( 'Locale', 'astroway' ), get_locale() ],
	[ __( 'API host', 'astroway' ), $astroway_api_host ],
];
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
				<h2 class="aw-panel-title"><?php esc_html_e( 'Connection', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'live ping · api.astroway.info', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<div class="aw-inline-action">
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-test-connection">
						<?php esc_html_e( 'Test connection', 'astroway' ); ?>
					</button>
					<span id="aw-test-result" class="aw-test-result"></span>
				</div>
				<p class="aw-hint">
					<?php
					printf(
						/* translators: %s = API health endpoint */
						esc_html__( 'Pings %s to confirm reachability from this server.', 'astroway' ),
						'<code>' . esc_html( $astroway_api_host ) . '/v1/health</code>'
					);
					?>
				</p>
			</div>
		</article>

		<article class="aw-panel" data-num="02">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">02</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Cache', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'WP transients · prefix astroway_v1_', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<div class="aw-stats">
					<div class="aw-stat">
						<span class="aw-stat-num"><?php echo (int) $stats['count']; ?></span>
						<span class="aw-stat-label"><?php esc_html_e( 'items', 'astroway' ); ?></span>
					</div>
					<div class="aw-stat">
						<span class="aw-stat-num"><?php echo esc_html( size_format( (int) $stats['bytes'], 1 ) ); ?></span>
						<span class="aw-stat-label"><?php esc_html_e( 'cached size', 'astroway' ); ?></span>
					</div>
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-purge-cache">
						<?php esc_html_e( 'Purge all', 'astroway' ); ?>
					</button>
				</div>
				<p class="aw-hint">
					<?php esc_html_e( 'TTLs: charts 1h · moon 24h · reference data 7d · key /me 30 min.', 'astroway' ); ?>
				</p>
			</div>
		</article>

		<article class="aw-panel" data-num="03">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">03</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'System', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'diagnostic info for support', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<table class="aw-diag">
					<tbody>
						<?php
						foreach ( $astroway_diag as $astroway_row ) :
							list( $astroway_label, $astroway_value ) = $astroway_row;
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $astroway_label ); ?></th>
								<td><code><?php echo esc_html( $astroway_value ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="aw-panel-actions">
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-copy-diag" data-target="aw-diag-payload">
						<?php esc_html_e( 'Copy diagnostic info', 'astroway' ); ?>
					</button>
				</div>
				<textarea id="aw-diag-payload" readonly aria-hidden="true" class="aw-diag-payload">
				<?php
				foreach ( $astroway_diag as $astroway_row ) {
					list( $astroway_label, $astroway_value ) = $astroway_row;
					echo esc_textarea( $astroway_label . ': ' . $astroway_value ) . "\n";
				}
				?>
				</textarea>
			</div>
		</article>

		<article class="aw-panel" data-num="04">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">04</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Feature matrix', 'astroway' ); ?></h2>
				<span class="aw-panel-hint">
					<?php
					printf(
						/* translators: %s = current tier */
						esc_html__( 'current tier: %s', 'astroway' ),
						'<strong>' . esc_html( $astroway_current_tier ) . '</strong>'
					);
					?>
				</span>
			</header>
			<div class="aw-panel-body">
				<table class="aw-matrix" style="border-collapse:collapse;font-size:13px">
					<thead>
						<tr>
							<th style="text-align:left;padding:6px 10px;border-bottom:1px solid #d9d3c2"><?php esc_html_e( 'Feature', 'astroway' ); ?></th>
							<?php foreach ( $astroway_tier_columns as $astroway_tier_col ) : ?>
								<th style="padding:6px 10px;border-bottom:1px solid #d9d3c2;text-transform:capitalize;<?php echo $astroway_tier_col === $astroway_current_tier ? 'background:#fff7e0' : ''; ?>">
									<?php echo esc_html( $astroway_tier_col ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $astroway_matrix as $astroway_feature => $astroway_allowed_tiers ) : ?>
							<tr>
								<td style="padding:6px 10px;border-bottom:1px solid #f0ece2"><code><?php echo esc_html( $astroway_feature ); ?></code></td>
								<?php foreach ( $astroway_tier_columns as $astroway_tier_col ) : ?>
									<td style="text-align:center;padding:6px 10px;border-bottom:1px solid #f0ece2;<?php echo $astroway_tier_col === $astroway_current_tier ? 'background:#fff7e0' : ''; ?>">
										<?php echo in_array( $astroway_tier_col, $astroway_allowed_tiers, true ) ? '<span style="color:#5a8f3f">✓</span>' : '<span style="color:#c0c0c0">·</span>'; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="aw-hint" style="margin-top:8px">
					<?php esc_html_e( 'Matrix is filterable via the `astroway_tier_matrix` filter — addons can append their own features.', 'astroway' ); ?>
				</p>
			</div>
		</article>

		<article class="aw-panel" data-num="05">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">05</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Domain binding', 'astroway' ); ?></h2>
				<span class="aw-panel-hint">
					<?php
					if ( '' !== $astroway_current_domain ) {
						printf(
							/* translators: %s = current domain */
							esc_html__( 'bound to: %s', 'astroway' ),
							'<code>' . esc_html( $astroway_current_domain ) . '</code>'
						);
					} else {
						esc_html_e( 'no domain binding yet', 'astroway' );
					}
					?>
				</span>
			</header>
			<div class="aw-panel-body">
				<div class="aw-inline-action" style="gap:8px">
					<input type="text" id="aw-new-domain" placeholder="example.com" class="aw-input"
						style="padding:6px 10px;border:1px solid #d9d3c2;border-radius:4px;font-size:14px;width:240px">
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-domain-change-btn">
						<?php esc_html_e( 'Request rebind', 'astroway' ); ?>
					</button>
					<span id="aw-domain-change-result" class="aw-test-result"></span>
				</div>
				<p class="aw-hint" style="margin-top:8px">
					<?php
					printf(
						/* translators: %s = api dashboard URL */
						esc_html__( 'Manual fallback: %s.', 'astroway' ),
						'<a href="https://api.astroway.info/dashboard/account" target="_blank" rel="noopener">api.astroway.info/dashboard/account</a>'
					);
					?>
				</p>
				<script>
				(function(){
					var btn = document.getElementById('aw-domain-change-btn');
					if (!btn) return;
					btn.addEventListener('click', function(){
						var input = document.getElementById('aw-new-domain');
						var out = document.getElementById('aw-domain-change-result');
						var d = (input.value || '').trim();
						if (!d) { out.textContent = '<?php echo esc_js( __( 'Enter a domain first.', 'astroway' ) ); ?>'; return; }
						btn.disabled = true; out.textContent = '<?php echo esc_js( __( 'Requesting…', 'astroway' ) ); ?>';
						var fd = new FormData();
						fd.append('action', 'astroway_domain_change');
						fd.append('nonce', astrowayAdmin.nonce);
						fd.append('new_domain', d);
						fetch(ajaxurl, { method:'POST', body:fd })
							.then(function(r){ return r.json(); })
							.then(function(j){
								btn.disabled = false;
								out.textContent = j.success
									? '<?php echo esc_js( __( '✓ requested', 'astroway' ) ); ?>'
									: '✗ ' + (j.data && j.data.message ? j.data.message : '<?php echo esc_js( __( 'failed', 'astroway' ) ); ?>');
							})
							.catch(function(){
								btn.disabled = false;
								out.textContent = '<?php echo esc_js( __( 'Network error', 'astroway' ) ); ?>';
							});
					});
				})();
				</script>
			</div>
		</article>

		<article class="aw-panel" data-num="06">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">06</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Widget disclaimer', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'optional legal line in the widget footer', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<form method="post" action="options.php">
					<?php settings_fields( \AstroWay\WPPlugin\Admin::PAGE_API_KEY ); ?>
					<input type="hidden" name="<?php echo esc_attr( \AstroWay\WPPlugin\Admin::OPTION_KEY ); ?>[widget_disclaimer_submitted]" value="1">
					<label style="display:flex;gap:8px;align-items:flex-start">
						<input type="checkbox" name="<?php echo esc_attr( \AstroWay\WPPlugin\Admin::OPTION_KEY ); ?>[widget_disclaimer]" value="1" <?php checked( ! empty( $astroway_opts['widget_disclaimer'] ) ); ?>>
						<span><strong><?php esc_html_e( 'Show a disclaimer in embedded widgets', 'astroway' ); ?></strong><br>
							<span class="aw-hint"><?php esc_html_e( 'Appends a short "informational, not professional advice" line to the widget footer. Off by default — enable it if your jurisdiction or compliance policy requires it. The wording is rendered and localized by api.astroway.info.', 'astroway' ); ?></span>
						</span>
					</label>
					<?php submit_button( __( 'Save disclaimer setting', 'astroway' ), 'aw-btn', 'submit', false ); ?>
				</form>
			</div>
		</article>

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
