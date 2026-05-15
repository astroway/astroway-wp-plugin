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

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
