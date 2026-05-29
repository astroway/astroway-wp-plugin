<?php
/**
 * Channel B status card — shown on the API Key admin page (v0.5.5+).
 *
 * @package AstroWay\WPPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_cb_status     = \AstroWay\WPPlugin\Updater::get_status();
$astroway_cb_channel    = $astroway_cb_status['channel'];
$astroway_cb_active     = $astroway_cb_status['active'];
$astroway_cb_has_key    = $astroway_cb_status['has_key'];
$astroway_cb_last_check = $astroway_cb_status['last_check'];
?>
<article class="aw-panel" data-num="04">
	<header class="aw-panel-head">
		<span class="aw-panel-num" aria-hidden="true">04</span>
		<h2 class="aw-panel-title"><?php esc_html_e( 'Update channel', 'astroway' ); ?></h2>
	</header>
	<div class="aw-panel-body">
		<p>
			<strong><?php echo esc_html( 'B' === $astroway_cb_channel ? __( 'Channel B', 'astroway' ) : __( 'Channel A', 'astroway' ) ); ?>.</strong>
			<?php if ( 'B' === $astroway_cb_channel ) : ?>
				<?php esc_html_e( 'Updates are delivered directly from astroway.info using your API key. Paid-tier features and early releases reach you first.', 'astroway' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Updates are delivered through the WordPress.org plugin directory. To activate Channel B for paid-tier delivery, add a paid API key above.', 'astroway' ); ?>
			<?php endif; ?>
		</p>
		<ul style="margin:8px 0 0;line-height:1.6;font-size:13px">
			<li>
				<strong><?php esc_html_e( 'Update endpoint', 'astroway' ); ?>:</strong>
				<code><?php echo esc_html( $astroway_cb_status['endpoint'] ); ?></code>
			</li>
			<li>
				<strong><?php esc_html_e( 'Library loaded', 'astroway' ); ?>:</strong>
				<?php echo $astroway_cb_active ? esc_html__( 'yes', 'astroway' ) : esc_html__( 'no', 'astroway' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'API key set', 'astroway' ); ?>:</strong>
				<?php echo $astroway_cb_has_key ? esc_html__( 'yes', 'astroway' ) : esc_html__( 'no', 'astroway' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Last update check', 'astroway' ); ?>:</strong>
				<?php
				if ( $astroway_cb_last_check ) {
					echo esc_html( human_time_diff( $astroway_cb_last_check, time() ) ) . ' ' . esc_html__( 'ago', 'astroway' );
				} else {
					esc_html_e( 'not yet', 'astroway' );
				}
				?>
			</li>
		</ul>
	</div>
</article>
