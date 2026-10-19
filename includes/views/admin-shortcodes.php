<?php
/**
 * Admin → Shortcodes reference page.
 *
 * @var string $api_key Current saved API key (may be empty).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$astroway_current_slug = \AstroWay\WPPlugin\Admin::PAGE_SHORTCODES;

$astroway_hero_title   = __( 'Shortcodes', 'astroway' );
$astroway_hero_tagline = __( 'Drop any shortcode into a post or page. Also available as Gutenberg blocks (/astroway).', 'astroway' );

$astroway_cards = require __DIR__ . '/partials/shortcode-cards.php';

foreach ( $astroway_cards as $astroway_i => $astroway_card ) {
	$astroway_slug = substr( (string) ( $astroway_card['block'] ?? '' ), strlen( 'astroway/' ) );
	if ( isset( \AstroWay\WPPlugin\WidgetManifest::SECTIONS[ $astroway_slug ] ) ) {
		$astroway_cards[ $astroway_i ]['params'][] = [
			'hide_sections',
			'string',
			false,
			/* translators: %s = comma-separated section keys */
			sprintf( __( 'Comma-separated parts to leave out: %s.', 'astroway' ), implode( ', ', \AstroWay\WPPlugin\WidgetManifest::SECTIONS[ $astroway_slug ] ) ),
		];
	}
	if ( 'natal-chart' === $astroway_slug ) {
		$astroway_cards[ $astroway_i ]['params'][] = [ 'open', 'string', false, __( 'first opens the first reading, none keeps them all closed.', 'astroway' ) ];
	}
}
?>
<div class="wrap aw-app">

	<?php // WP moves admin notices here; without the marker they land inside our hero. ?>
	<hr class="wp-header-end">

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-hero.php'; ?>

	<div class="aw-grid">

	<main class="aw-main">

		<article class="aw-panel aw-tz-helper">
			<header class="aw-panel-head">
				<span class="aw-panel-num" aria-hidden="true">☉</span>
				<h2 class="aw-panel-title"><?php esc_html_e( 'Find your coordinates and timezone', 'astroway' ); ?></h2>
				<span class="aw-panel-hint"><?php esc_html_e( 'city → lat · lon · IANA tz', 'astroway' ); ?></span>
			</header>
			<div class="aw-panel-body">
				<div class="aw-field-row">
					<input type="search"
						id="aw-city-search"
						class="aw-input"
						placeholder="<?php esc_attr_e( 'Type a city name, for example Kyiv, Berlin or São Paulo', 'astroway' ); ?>"
						autocomplete="off"
						spellcheck="false" />
					<button type="button" class="aw-btn aw-btn-ghost" id="aw-city-search-btn">
						<?php esc_html_e( 'Search', 'astroway' ); ?>
					</button>
				</div>
				<p class="aw-hint">
					<?php
					printf(
						/* translators: %s is a link to the Wikipedia IANA tz list */
						esc_html__( 'Pick the matching city to copy values into the shortcode. Reference: %s.', 'astroway' ),
						'<a href="https://en.wikipedia.org/wiki/List_of_tz_database_time_zones" target="_blank" rel="noopener">' . esc_html__( 'IANA timezone list', 'astroway' ) . '</a>'
					);
					?>
				</p>
				<div id="aw-city-results" class="aw-city-results" role="region" aria-live="polite"></div>
			</div>
		</article>

		<div class="aw-filter">
			<label class="screen-reader-text" for="aw-sc-filter"><?php esc_html_e( 'Filter shortcodes', 'astroway' ); ?></label>
			<input type="search"
				id="aw-sc-filter"
				class="aw-input"
				placeholder="<?php echo esc_attr( sprintf( /* translators: %d is the number of shortcodes */ __( 'Filter %d shortcodes: name, tag or what it does', 'astroway' ), count( $astroway_cards ) ) ); ?>"
				autocomplete="off"
				spellcheck="false" />
			<p class="aw-filter-count" id="aw-sc-filter-count" role="status" aria-live="polite"></p>
		</div>

		<?php foreach ( $astroway_cards as $astroway_card ) : ?>
			<article class="aw-panel aw-card"
				data-search="<?php echo esc_attr( strtolower( $astroway_card['tag'] . ' ' . $astroway_card['title'] . ' ' . $astroway_card['description'] ) ); ?>">
				<div class="aw-panel-body">
					<header class="aw-card-head">
						<code class="aw-card-tag">[<?php echo esc_html( $astroway_card['tag'] ); ?>]</code>
						<h2 class="aw-card-title"><?php echo esc_html( $astroway_card['title'] ); ?></h2>
						<p class="aw-card-desc"><?php echo esc_html( $astroway_card['description'] ); ?></p>
					</header>

					<div class="aw-card-example">
						<button type="button"
							class="aw-sc-code"
							data-copy="<?php echo esc_attr( $astroway_card['example'] ); ?>"
							title="<?php esc_attr_e( 'Click to copy', 'astroway' ); ?>">
							<code><?php echo esc_html( $astroway_card['example'] ); ?></code>
							<span class="aw-sc-action" aria-hidden="true">
								<svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="9" height="9" rx="1.5"/><path d="M3 11V3a1 1 0 0 1 1-1h7"/></svg>
								<span class="aw-sc-action-text"><?php esc_html_e( 'copy', 'astroway' ); ?></span>
							</span>
						</button>
						<button type="button" class="aw-btn aw-btn-ghost aw-try" data-tag="<?php echo esc_attr( $astroway_card['tag'] ); ?>"><?php esc_html_e( 'Try it', 'astroway' ); ?></button>
					</div>
					<div class="aw-try-out" hidden aria-live="polite"></div>

					<table class="aw-params">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Parameter', 'astroway' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Type', 'astroway' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Required', 'astroway' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Description', 'astroway' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $astroway_card['params'] as $astroway_param ) :
							list( $astroway_pname, $astroway_ptype, $astroway_preq, $astroway_pdesc ) = $astroway_param;
							?>
							<tr>
								<td><code><?php echo esc_html( $astroway_pname ); ?></code></td>
								<td><?php echo esc_html( $astroway_ptype ); ?></td>
								<td>
									<?php if ( $astroway_preq ) : ?>
										<span class="aw-param-req"><?php esc_html_e( 'required', 'astroway' ); ?></span>
									<?php else : ?>
										<span class="aw-param-opt"><?php esc_html_e( 'optional', 'astroway' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $astroway_pdesc ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

					<?php
					// Title comes from the registry rather than a copy here: WP
					// translates block.json titles, and a second copy would both
					// drift and collide as a duplicate msgid in the .pot.
					$astroway_block = $astroway_card['block']
						? \WP_Block_Type_Registry::get_instance()->get_registered( $astroway_card['block'] )
						: null;
					?>
					<?php if ( $astroway_block && ! empty( $astroway_block->title ) ) : ?>
					<p class="aw-card-block-hint">
						<span aria-hidden="true">💡</span>
						<?php
						printf(
							/* translators: %s is a Gutenberg block name */
							esc_html__( 'Also available as Gutenberg block "%s".', 'astroway' ),
							esc_html( $astroway_block->title )
						);
						?>
					</p>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>

		<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-generated-coverage.php'; ?>

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
