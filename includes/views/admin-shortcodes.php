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

$astroway_cards = [
	[
		'tag'         => 'astroway_natal',
		'title'       => __( 'Natal Chart', 'astroway' ),
		'description' => __( 'Birth chart wheel — planet positions, houses, aspects.', 'astroway' ),
		'example'     => '[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52" name="Anna" tz="Europe/Kyiv"]',
		'block_name'  => __( 'AstroWay — Natal Chart', 'astroway' ),
		'params'      => [
			[ 'date', 'string', true,  __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true,  __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat',  'float',  true,  __( 'WGS-84 latitude (e.g. 50.45).', 'astroway' ) ],
			[ 'lon',  'float',  true,  __( 'WGS-84 longitude (e.g. 30.52).', 'astroway' ) ],
			[ 'tz',   'string', true,  __( 'IANA timezone (e.g. Europe/Kyiv).', 'astroway' ) ],
			[ 'name', 'string', false, __( 'Display name on the chart.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_daily_horoscope',
		'title'       => __( 'Daily Horoscope', 'astroway' ),
		'description' => __( "Today's horoscope for a zodiac sign. Auto-refreshes daily at api side.", 'astroway' ),
		'example'     => '[astroway_daily_horoscope sign="aries"]',
		'block_name'  => __( 'AstroWay — Daily Horoscope', 'astroway' ),
		'params'      => [
			[ 'sign', 'string', true, __( 'One of: aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_moon_phase',
		'title'       => __( 'Moon Phase', 'astroway' ),
		'description' => __( 'Current Moon phase visualization with illumination percent.', 'astroway' ),
		'example'     => '[astroway_moon_phase]',
		'block_name'  => __( 'AstroWay — Moon Phase', 'astroway' ),
		'params'      => [
			[ 'date', 'string', false, __( 'Override date in YYYY-MM-DD. Defaults to today.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_bodygraph',
		'title'       => __( 'Human Design Bodygraph', 'astroway' ),
		'description' => __( 'Human Design body graph with centers, channels, gates.', 'astroway' ),
		'example'     => '[astroway_bodygraph date="1990-05-15" time="14:30" lat="50.45" lon="30.52" name="Anna" tz="Europe/Kyiv"]',
		'block_name'  => __( 'AstroWay — Bodygraph', 'astroway' ),
		'params'      => [
			[ 'date', 'string', true,  __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true,  __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat',  'float',  true,  __( 'WGS-84 latitude.', 'astroway' ) ],
			[ 'lon',  'float',  true,  __( 'WGS-84 longitude.', 'astroway' ) ],
			[ 'tz',   'string', true,  __( 'IANA timezone.', 'astroway' ) ],
			[ 'name', 'string', false, __( 'Display name.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_tarot_card',
		'title'       => __( 'Daily Tarot Card', 'astroway' ),
		'description' => __( 'Single card pull of the day from a chosen deck.', 'astroway' ),
		'example'     => '[astroway_tarot_card deck="rider-waite"]',
		'block_name'  => __( 'AstroWay — Daily Tarot Card', 'astroway' ),
		'params'      => [
			[ 'deck', 'string', false, __( 'Deck slug: rider-waite (default), marseille, or lenormand.', 'astroway' ) ],
		],
	],
];
?>
<div class="wrap aw-app">

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
						placeholder="<?php esc_attr_e( 'Type a city name — e.g. Kyiv, Berlin, São Paulo', 'astroway' ); ?>"
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

		<?php foreach ( $astroway_cards as $astroway_card ) : ?>
			<article class="aw-panel aw-card">
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
					</div>

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
						<?php foreach ( $astroway_card['params'] as $astroway_param ) :
							list( $astroway_pname, $astroway_ptype, $astroway_preq, $astroway_pdesc ) = $astroway_param; ?>
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

					<p class="aw-card-block-hint">
						<span aria-hidden="true">💡</span>
						<?php
						printf(
							/* translators: %s is a Gutenberg block name */
							esc_html__( 'Also available as Gutenberg block "%s".', 'astroway' ),
							esc_html( $astroway_card['block_name'] )
						);
						?>
					</p>
				</div>
			</article>
		<?php endforeach; ?>

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
