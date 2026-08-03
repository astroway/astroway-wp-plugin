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
		'description' => __( 'Birth chart: the wheel plus planet positions, houses and aspects as text.', 'astroway' ),
		'example'     => '[astroway_natal date="1990-05-15" time="14:30" lat="50.45" lon="30.52" name="Anna" tz="Europe/Kyiv"]',
		'block'       => 'astroway/natal-chart',
		'params'      => [
			[ 'date', 'string', true, __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true, __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat', 'float', true, __( 'WGS-84 latitude (e.g. 50.45).', 'astroway' ) ],
			[ 'lon', 'float', true, __( 'WGS-84 longitude (e.g. 30.52).', 'astroway' ) ],
			[ 'tz', 'string', true, __( 'IANA timezone (e.g. Europe/Kyiv).', 'astroway' ) ],
			[ 'name', 'string', false, __( 'Display name on the chart.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'Palette of the wheel drawing: dark (default), light or console. The text beside it follows your theme on its own; the wheel is an image and cannot, so set this to match.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_daily_horoscope',
		'title'       => __( 'Daily Horoscope', 'astroway' ),
		'description' => __( "Today's horoscope for a zodiac sign. Auto-refreshes daily at api side.", 'astroway' ),
		'example'     => '[astroway_daily_horoscope sign="aries"]',
		'block'       => 'astroway/daily-horoscope',
		'params'      => [
			[ 'sign', 'string', true, __( 'One of: aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_weekly_horoscope',
		'title'       => __( 'Weekly Horoscope', 'astroway' ),
		'description' => __( 'Horoscope for the current week, anchored to Monday. Rendered into the page, not an iframe.', 'astroway' ),
		'example'     => '[astroway_weekly_horoscope sign="aries"]',
		'block'       => 'astroway/weekly-horoscope',
		'params'      => [
			[ 'sign', 'string', true, __( 'One of: aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces.', 'astroway' ) ],
			[ 'date', 'string', false, __( 'Any date inside the week you want. Defaults to today.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_monthly_horoscope',
		'title'       => __( 'Monthly Horoscope', 'astroway' ),
		'description' => __( 'Horoscope for the current month, anchored to the 1st. Rendered into the page, not an iframe.', 'astroway' ),
		'example'     => '[astroway_monthly_horoscope sign="aries"]',
		'block'       => 'astroway/monthly-horoscope',
		'params'      => [
			[ 'sign', 'string', true, __( 'One of: aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces.', 'astroway' ) ],
			[ 'date', 'string', false, __( 'Any date inside the month you want. Defaults to today.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_planet_of_day',
		'title'       => __( 'Planet of the Day', 'astroway' ),
		'description' => __( 'Traditional planetary ruler of the weekday, with its glyph and themes. Rendered into the page, not an iframe.', 'astroway' ),
		'example'     => '[astroway_planet_of_day]',
		'block'       => 'astroway/planet-of-day',
		'params'      => [
			[ 'date', 'string', false, __( 'Override date in YYYY-MM-DD. Defaults to today.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_moon_phase',
		'title'       => __( 'Moon Phase', 'astroway' ),
		'description' => __( 'Current Moon phase visualization with illumination percent.', 'astroway' ),
		'example'     => '[astroway_moon_phase]',
		'block'       => 'astroway/moon-phase',
		'params'      => [
			[ 'date', 'string', false, __( 'Override date in YYYY-MM-DD. Defaults to today.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_bodygraph',
		'title'       => __( 'Human Design Bodygraph', 'astroway' ),
		'description' => __( 'Human Design body graph with centers, channels, gates.', 'astroway' ),
		'example'     => '[astroway_bodygraph date="1990-05-15" time="14:30" lat="50.45" lon="30.52" name="Anna" tz="Europe/Kyiv"]',
		'block'       => 'astroway/bodygraph',
		'params'      => [
			[ 'date', 'string', true, __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true, __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat', 'float', true, __( 'WGS-84 latitude.', 'astroway' ) ],
			[ 'lon', 'float', true, __( 'WGS-84 longitude.', 'astroway' ) ],
			[ 'tz', 'string', true, __( 'IANA timezone.', 'astroway' ) ],
			[ 'name', 'string', false, __( 'Display name.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_tarot_card',
		'title'       => __( 'Daily Tarot Card', 'astroway' ),
		'description' => __( 'Single card pull of the day from a chosen deck.', 'astroway' ),
		'example'     => '[astroway_tarot_card deck="rider-waite"]',
		'block'       => 'astroway/daily-tarot',
		'params'      => [
			[ 'deck', 'string', false, __( 'Deck slug: rider-waite (default), marseille, or lenormand. Only rider-waite renders into the page; the other two load in a frame.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_mini_chart',
		'title'       => __( 'Mini Birth Chart', 'astroway' ),
		'description' => __( 'The natal wheel drawn small, for a sidebar. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_mini_chart date="1990-05-15" time="14:30" lat="50.45" lon="30.52" tz="Europe/Kyiv"]',
		'block'       => 'astroway/mini-chart',
		'params'      => [
			[ 'date', 'string', true, __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true, __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat', 'float', true, __( 'WGS-84 latitude.', 'astroway' ) ],
			[ 'lon', 'float', true, __( 'WGS-84 longitude.', 'astroway' ) ],
			[ 'tz', 'string', true, __( 'IANA timezone.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_monthly_forecast',
		'title'       => __( 'Monthly Forecast', 'astroway' ),
		'description' => __( 'Four weeks ahead for one sign, each with its own theme. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_monthly_forecast sign="leo"]',
		'block'       => 'astroway/monthly-forecast',
		'params'      => [
			[ 'sign', 'string', true, __( 'One of: aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces.', 'astroway' ) ],
			[ 'date', 'string', false, __( 'Any date inside the month you want. Defaults to today.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_transit_timeline',
		'title'       => __( 'Moon Transit Timeline', 'astroway' ),
		'description' => __( 'The next seven days of Moon ingresses. Not sign-specific. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_transit_timeline]',
		'block'       => 'astroway/transit-timeline',
		'params'      => [
			[ 'date', 'string', false, __( 'Day the seven start from. Defaults to today.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_kundli',
		'title'       => __( 'Vedic Kundli', 'astroway' ),
		'description' => __( 'North-Indian D1 chart. Loads in a frame: there is no page-side template for it yet.', 'astroway' ),
		'example'     => '[astroway_kundli date="1990-05-15" time="14:30" lat="50.45" lon="30.52" tz="Europe/Kyiv"]',
		'block'       => 'astroway/kundli',
		'params'      => [
			[ 'date', 'string', true, __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time', 'string', true, __( 'Birth time in HH:MM (24-hour).', 'astroway' ) ],
			[ 'lat', 'float', true, __( 'WGS-84 latitude.', 'astroway' ) ],
			[ 'lon', 'float', true, __( 'WGS-84 longitude.', 'astroway' ) ],
			[ 'tz', 'string', true, __( 'IANA timezone.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_transit',
		'title'       => __( 'Transit Sky', 'astroway' ),
		'description' => __( 'Where the planets stand on a given date. Loads in a frame: there is no page-side template for it yet.', 'astroway' ),
		'example'     => '[astroway_transit]',
		'block'       => 'astroway/transit',
		'params'      => [
			[ 'date', 'string', false, __( 'Override date in YYYY-MM-DD. Defaults to today.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_panchang',
		'title'       => __( 'Daily Panchang', 'astroway' ),
		'description' => __( 'Tithi, nakshatra, yoga, karana, vara and rahu-kaal for a date and place. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_panchang lat="50.45" lon="30.52" tz="Europe/Kyiv"]',
		'block'       => 'astroway/panchang',
		'params'      => [
			[ 'date', 'string', false, __( 'Override date in YYYY-MM-DD. Defaults to today.', 'astroway' ) ],
			[ 'lat', 'float', true, __( 'WGS-84 latitude.', 'astroway' ) ],
			[ 'lon', 'float', true, __( 'WGS-84 longitude.', 'astroway' ) ],
			[ 'tz', 'string', true, __( 'IANA timezone.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_numerology',
		'title'       => __( 'Numerology', 'astroway' ),
		'description' => __( 'Life Path, Expression, Soul Urge and Personality numbers. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_numerology name="Anna Kovalenko" date="1990-05-15"]',
		'block'       => 'astroway/numerology',
		'params'      => [
			[ 'name', 'string', true, __( 'Full birth name. The letters are what the numbers are built from.', 'astroway' ) ],
			[ 'date', 'string', true, __( 'Birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'system', 'string', false, __( 'pythagorean (default), chaldean, kabbalistic or tamil.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
	[
		'tag'         => 'astroway_synastry',
		'title'       => __( 'Synastry Compatibility', 'astroway' ),
		'description' => __( 'Compatibility score and top inter-aspects for two birth charts. Loads in a frame.', 'astroway' ),
		'example'     => '[astroway_synastry date_a="1990-05-15" time_a="14:30" lat_a="50.45" lon_a="30.52" tz_a="Europe/Kyiv" date_b="1988-11-02" time_b="09:15" lat_b="52.52" lon_b="13.40" tz_b="Europe/Berlin"]',
		'block'       => 'astroway/synastry',
		'params'      => [
			[ 'date_a', 'string', true, __( 'First person: birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time_a', 'string', true, __( 'First person: birth time in HH:MM.', 'astroway' ) ],
			[ 'lat_a', 'float', true, __( 'First person: latitude.', 'astroway' ) ],
			[ 'lon_a', 'float', true, __( 'First person: longitude.', 'astroway' ) ],
			[ 'tz_a', 'string', true, __( 'First person: IANA timezone.', 'astroway' ) ],
			[ 'date_b', 'string', true, __( 'Second person: birth date in YYYY-MM-DD.', 'astroway' ) ],
			[ 'time_b', 'string', true, __( 'Second person: birth time in HH:MM.', 'astroway' ) ],
			[ 'lat_b', 'float', true, __( 'Second person: latitude.', 'astroway' ) ],
			[ 'lon_b', 'float', true, __( 'Second person: longitude.', 'astroway' ) ],
			[ 'tz_b', 'string', true, __( 'Second person: IANA timezone.', 'astroway' ) ],
			[ 'theme', 'string', false, __( 'dark (default), light or console.', 'astroway' ) ],
		],
	],
];
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

	</main>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-sidebar.php'; ?>

	</div>

	<?php require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/admin-footer-nav.php'; ?>

</div>
