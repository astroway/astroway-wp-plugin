<?php
/**
 * The generated half of the shortcode reference.
 *
 * Rendered as one collapsed group per family rather than as cards. The
 * hand-written widgets get a card each because there are eighteen of them and
 * each is worth reading; there are close to seven hundred of these, and eighteen
 * cards' worth of markup per endpoint would be a several-megabyte admin page.
 *
 * Every row still carries `data-search`, so the filter at the top of the page
 * finds them alongside the cards.
 *
 * @package AstroWay\WPPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_families = \AstroWay\WPPlugin\GeneratedRegistry::reference_by_family();
if ( empty( $astroway_families ) ) {
	return;
}

$astroway_total = 0;
foreach ( $astroway_families as $astroway_family ) {
	$astroway_total += count( $astroway_family['endpoints'] );
}
?>

<article class="aw-panel aw-generated">
	<div class="aw-panel-body">
		<header class="aw-card-head">
			<h2 class="aw-card-title"><?php esc_html_e( 'The rest of the API', 'astroway' ); ?></h2>
			<p class="aw-card-desc">
				<?php
				printf(
					/* translators: 1: number of shortcodes, 2: number of families */
					esc_html__( '%1$d more shortcodes across %2$d families, generated from the api specification. Each one needs an API key. The widgets above are hand-built and read better; these cover everything else the api can calculate.', 'astroway' ),
					(int) $astroway_total,
					count( $astroway_families )
				);
				?>
			</p>
		</header>

		<?php foreach ( $astroway_families as $astroway_slug => $astroway_family ) : ?>
			<details class="aw-generated-family">
				<summary>
					<?php echo esc_html( $astroway_family['label'] ); ?>
					<span class="aw-generated-count"><?php echo esc_html( (string) count( $astroway_family['endpoints'] ) ); ?></span>
				</summary>
				<ul class="aw-generated-list">
					<?php foreach ( $astroway_family['endpoints'] as $astroway_tag => $astroway_endpoint ) : ?>
						<li class="aw-generated-row"
							data-search="<?php echo esc_attr( strtolower( $astroway_tag . ' ' . $astroway_endpoint['title'] . ' ' . $astroway_endpoint['description'] ) ); ?>">
							<button type="button"
								class="aw-sc-code aw-generated-copy"
								data-copy="<?php echo esc_attr( $astroway_endpoint['example'] ); ?>"
								title="<?php esc_attr_e( 'Click to copy', 'astroway' ); ?>">
								<code><?php echo esc_html( $astroway_endpoint['example'] ); ?></code>
							</button>
							<span class="aw-generated-title"><?php echo esc_html( $astroway_endpoint['title'] ); ?></span>
							<?php if ( '' !== $astroway_endpoint['description'] ) : ?>
								<span class="aw-generated-desc"><?php echo esc_html( $astroway_endpoint['description'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endforeach; ?>
	</div>
</article>
