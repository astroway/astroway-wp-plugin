<?php
/** @var string $api_key */
/** @var array  $stats */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_site_id_hash = substr( md5( home_url() ), 0, 12 );
$astroway_option_key   = \AstroWay\WPPlugin\Admin::OPTION_KEY;
$astroway_page_slug    = \AstroWay\WPPlugin\Admin::PAGE_SLUG;
?>
<div class="wrap astroway-admin">
	<h1><?php esc_html_e( 'AstroWay Settings', 'astroway' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( $astroway_page_slug ); ?>

		<h2><?php esc_html_e( 'API Key', 'astroway' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="aw-api-key"><?php esc_html_e( 'Your API key', 'astroway' ); ?></label>
				</th>
				<td>
					<input type="text"
						id="aw-api-key"
						name="<?php echo esc_attr( $astroway_option_key ); ?>[api_key]"
						value="<?php echo esc_attr( $api_key ); ?>"
						class="regular-text"
						placeholder="aw_..."
						autocomplete="off"
						spellcheck="false" />
					<button type="button" class="button" id="aw-verify-key">
						<?php esc_html_e( 'Verify Key', 'astroway' ); ?>
					</button>
					<p class="description">
						<?php
						printf(
							wp_kses(
								/* translators: %1$s: opening <a>, %2$s: closing </a> */
								__( 'Leave empty for anonymous mode (30 requests/hour per visitor IP). %1$sGet a free API key%2$s for 10 000 credits/month + higher limits.', 'astroway' ),
								[
									'a' => [
										'href'   => true,
										'target' => true,
										'rel'    => true,
									],
								]
							),
							'<a href="https://api.astroway.info/dashboard/sign-up?source=wp_plugin" target="_blank" rel="noopener">',
							'</a>'
						);
						?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Sandbox keys (aw_test_*) and live keys (aw_live_*) both accepted — api routes them internally.', 'astroway' ); ?>
					</p>
					<div id="aw-key-status" class="notice inline" style="display:none;"></div>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>

	<h2><?php esc_html_e( 'Connection', 'astroway' ); ?></h2>
	<p>
		<button type="button" class="button" id="aw-test-connection">
			<?php esc_html_e( 'Test Connection', 'astroway' ); ?>
		</button>
		<span id="aw-test-result"></span>
	</p>

	<h2><?php esc_html_e( 'Cache', 'astroway' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %1$d: count, %2$s: formatted bytes */
			esc_html__( 'Cached items: %1$d (~%2$s)', 'astroway' ),
			(int) $stats['count'],
			esc_html( size_format( (int) $stats['bytes'], 1 ) )
		);
		?>
		<button type="button" class="button" id="aw-purge-cache" style="margin-left:1em;">
			<?php esc_html_e( 'Purge Cache', 'astroway' ); ?>
		</button>
	</p>

	<h2><?php esc_html_e( 'Shortcodes', 'astroway' ); ?></h2>
	<p><?php esc_html_e( 'Copy any of these into a page or post:', 'astroway' ); ?></p>
	<table class="widefat striped" style="max-width:800px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Widget', 'astroway' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'astroway' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Natal Chart', 'astroway' ); ?></td>
				<td><code>[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Daily Horoscope', 'astroway' ); ?></td>
				<td><code>[astroway_daily_horoscope sign="aries"]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Moon Phase', 'astroway' ); ?></td>
				<td><code>[astroway_moon_phase]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Human Design Bodygraph', 'astroway' ); ?></td>
				<td><code>[astroway_bodygraph date="1990-05-15" time="14:30" lat="50.45" lon="30.52"]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Daily Tarot', 'astroway' ); ?></td>
				<td><code>[astroway_tarot_card deck="rider-waite"]</code></td>
			</tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Diagnostics', 'astroway' ); ?></h2>
	<details>
		<summary style="cursor:pointer;"><?php esc_html_e( 'Show technical details', 'astroway' ); ?></summary>
		<ul style="margin-top:1em;">
			<li>
				<?php
				printf(
					/* translators: %s: site identifier hash */
					esc_html__( 'Site ID (hash): %s', 'astroway' ),
					'<code>' . esc_html( $astroway_site_id_hash ) . '</code>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %s: api base URL */
					esc_html__( 'API base: %s', 'astroway' ),
					'<code>' . esc_html( ASTROWAY_API_BASE ) . '</code>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'Plugin version: %s', 'astroway' ),
					'<code>' . esc_html( ASTROWAY_WP_PLUGIN_VERSION ) . '</code>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %s: WordPress version */
					esc_html__( 'WordPress: %s', 'astroway' ),
					'<code>' . esc_html( get_bloginfo( 'version' ) ) . '</code>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %s: PHP version */
					esc_html__( 'PHP: %s', 'astroway' ),
					'<code>' . esc_html( PHP_VERSION ) . '</code>'
				);
				?>
			</li>
		</ul>
	</details>
</div>
