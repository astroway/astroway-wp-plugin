<?php
/**
 * Shared right aside for all 3 admin subpages.
 * Resources links + small System box + decorative quote.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_dashboard_url = 'https://api.astroway.info/dashboard?source=wp_plugin';
$astroway_docs_url      = 'https://api.astroway.info/docs';
$astroway_github_url    = 'https://github.com/astroway/astroway-wp-plugin';
$astroway_support_url   = 'https://astroway.info/support';
$astroway_api_host      = wp_parse_url( ASTROWAY_API_BASE, PHP_URL_HOST );
?>
<aside class="aw-aside">

	<section class="aw-mini">
		<h3 class="aw-mini-title"><?php esc_html_e( 'Resources', 'astroway' ); ?></h3>
		<ul class="aw-link-list">
			<li><a href="<?php echo esc_url( $astroway_dashboard_url ); ?>" target="_blank" rel="noopener">
				<span><?php esc_html_e( 'API dashboard', 'astroway' ); ?></span>
				<span class="aw-link-arrow">↗</span>
			</a></li>
			<li><a href="<?php echo esc_url( $astroway_docs_url ); ?>" target="_blank" rel="noopener">
				<span><?php esc_html_e( 'Documentation', 'astroway' ); ?></span>
				<span class="aw-link-arrow">↗</span>
			</a></li>
			<li><a href="<?php echo esc_url( $astroway_github_url ); ?>" target="_blank" rel="noopener">
				<span><?php esc_html_e( 'GitHub repository', 'astroway' ); ?></span>
				<span class="aw-link-arrow">↗</span>
			</a></li>
			<li><a href="<?php echo esc_url( $astroway_support_url ); ?>" target="_blank" rel="noopener">
				<span><?php esc_html_e( 'Support', 'astroway' ); ?></span>
				<span class="aw-link-arrow">↗</span>
			</a></li>
		</ul>
	</section>

	<section class="aw-mini">
		<h3 class="aw-mini-title"><?php esc_html_e( 'System', 'astroway' ); ?></h3>
		<dl class="aw-meta">
			<dt><?php esc_html_e( 'Plugin', 'astroway' ); ?></dt>
			<dd><?php echo esc_html( ASTROWAY_WP_PLUGIN_VERSION ); ?></dd>
			<dt><?php esc_html_e( 'WordPress', 'astroway' ); ?></dt>
			<dd><?php echo esc_html( get_bloginfo( 'version' ) ); ?></dd>
			<dt><?php esc_html_e( 'PHP', 'astroway' ); ?></dt>
			<dd><?php echo esc_html( PHP_VERSION ); ?></dd>
			<dt><?php esc_html_e( 'API host', 'astroway' ); ?></dt>
			<dd><code><?php echo esc_html( $astroway_api_host ); ?></code></dd>
		</dl>
	</section>

</aside>
