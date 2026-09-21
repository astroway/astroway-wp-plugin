<?php
/**
 * Runs only when the plugin is deleted from Plugins, never on deactivation.
 *
 * WordPress loads this file on its own, without the plugin, so nothing here may
 * lean on the plugin's classes. Only names this plugin writes are removed:
 * other AstroWay plugins (the Elementor addon, Books) keep their own data.
 *
 * @package AstroWay\WPPlugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Everything the plugin stored for one site.
 */
function astroway_uninstall_site() {
	global $wpdb;

	foreach ( [ 'astroway_settings', 'astroway_activated_at', 'astroway_rate_limit_hit_at', 'astroway_quota_seen', 'astroway_digest_last_sent' ] as $astroway_option ) {
		delete_option( $astroway_option );
	}
	delete_transient( 'astroway_rate_limit_probe' );

	// Cached api answers: every transient under the plugin's prefix. There is no
	// API for deleting transients by prefix, so this is one query.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- bulk delete by prefix on uninstall.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_astroway_v1_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_astroway_v1_' ) . '%'
		)
	);

	wp_clear_scheduled_hook( 'astroway_sky_digest_tick' );
}

if ( is_multisite() ) {
	foreach ( get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) as $astroway_site_id ) {
		switch_to_blog( (int) $astroway_site_id );
		astroway_uninstall_site();
		restore_current_blog();
	}
} else {
	astroway_uninstall_site();
}

// Network-wide: the update checker's state in paid builds, and the per-user
// "dismissed" flags of the plugin's notices.
delete_site_option( 'external_updates-astroway' );
foreach ( [ 'astroway_notice_dismissed', 'astroway_review_prompt_dismissed', 'astroway_rl_notice_dismissed' ] as $astroway_meta_key ) {
	delete_metadata( 'user', 0, $astroway_meta_key, '', true );
}
