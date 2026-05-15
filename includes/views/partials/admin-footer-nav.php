<?php
/**
 * Shared footer nav strip for all 3 admin subpages.
 *
 * @var string $astroway_current_slug  One of Admin::PAGE_API_KEY / PAGE_SETTINGS / PAGE_SHORTCODES.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_nav_items = [
	[ \AstroWay\WPPlugin\Admin::PAGE_API_KEY, __( 'API Key', 'astroway' ) ],
	[ \AstroWay\WPPlugin\Admin::PAGE_SETTINGS, __( 'Settings', 'astroway' ) ],
	[ \AstroWay\WPPlugin\Admin::PAGE_SHORTCODES, __( 'Shortcodes', 'astroway' ) ],
];

$astroway_docs_url = 'https://wordpress.org/plugins/astroway/';
?>
<nav class="aw-footer-nav" aria-label="<?php esc_attr_e( 'Plugin pages', 'astroway' ); ?>">
	<?php
	foreach ( $astroway_nav_items as $astroway_item ) :
		list( $astroway_slug, $astroway_label ) = $astroway_item;
		$astroway_url                           = admin_url( 'admin.php?page=' . $astroway_slug );
		if ( $astroway_slug === $astroway_current_slug ) :
			?>
			<span class="aw-footer-nav-current" aria-current="page"><?php echo esc_html( $astroway_label ); ?></span>
		<?php else : ?>
			<a href="<?php echo esc_url( $astroway_url ); ?>"><?php echo esc_html( $astroway_label ); ?></a>
			<?php
		endif;
	endforeach;
	?>
	<a class="aw-footer-nav-external" href="<?php echo esc_url( $astroway_docs_url ); ?>" target="_blank" rel="noopener">
		<?php esc_html_e( 'Docs', 'astroway' ); ?>
		<span aria-hidden="true">↗</span>
	</a>
</nav>
