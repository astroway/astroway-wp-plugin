<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AstroWay → Horoscope pages: one click makes a hub page and a page for each of
 * the twelve signs, built from the plugin's own blocks inside an Astrology
 * Section. The pages are ordinary pages afterwards; the owner edits or deletes
 * them like any other. Each page remembers what it was made for, so a second
 * run only fills in the ones that are missing and never touches edited ones.
 */
class PageBuilder {

	public const PAGE   = 'astroway-pages';
	public const ACTION = 'astroway_build_pages';
	public const META   = '_astroway_page';

	private const SIGNS = [ 'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces' ];

	/** Blocks a sign page can carry, in the order they appear. */
	private const PERIODS = [ 'daily-horoscope', 'weekly-horoscope', 'monthly-horoscope', 'yearly-horoscope' ];

	public static function register(): void {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 20 );
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle' ] );
	}

	public static function menu(): void {
		add_submenu_page(
			Admin::PAGE_API_KEY,
			__( 'Horoscope pages', 'astroway' ),
			__( 'Horoscope pages', 'astroway' ),
			'manage_options',
			self::PAGE,
			[ __CLASS__, 'render' ]
		);
	}

	private static function period_labels(): array {
		return [
			'daily-horoscope'   => __( 'Daily horoscope', 'astroway' ),
			'weekly-horoscope'  => __( 'Weekly horoscope', 'astroway' ),
			'monthly-horoscope' => __( 'Monthly horoscope', 'astroway' ),
			'yearly-horoscope'  => __( 'Yearly horoscope', 'astroway' ),
		];
	}

	/** Pages made earlier, by what they were made for ('hub' or a sign), still present. */
	public static function existing(): array {
		$found = get_posts(
			[
				'post_type'      => 'page',
				// A trashed page still counts: the owner removed it, the wizard does not bring it back.
				'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
				'posts_per_page' => 20,
				'meta_key'       => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- at most 13 rows, admin screen only.
				'fields'         => 'ids',
			]
		);
		$out   = [];
		foreach ( $found as $id ) {
			$out[ (string) get_post_meta( $id, self::META, true ) ] = (int) $id;
		}
		return $out;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$have   = self::existing();
		$labels = self::period_labels();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display of the redirect's own counters.
		$made = isset( $_GET['made'] ) ? absint( $_GET['made'] ) : null;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Horoscope pages', 'astroway' ); ?></h1>
			<p><?php esc_html_e( 'Creates a horoscope hub and a page for each of the twelve signs from AstroWay blocks. The text is rendered by your server into the page, so search engines index it. The pages are ordinary pages afterwards: edit, move or delete them as you like. Running this again only adds pages that are missing.', 'astroway' ); ?></p>
			<?php if ( null !== $made ) : ?>
				<div class="notice notice-success"><p>
					<?php
					/* translators: %d: number of pages created */
					echo esc_html( sprintf( _n( '%d page created.', '%d pages created.', $made, 'astroway' ), $made ) );
					?>
				</p></div>
			<?php endif; ?>
			<?php if ( $have ) : ?>
				<h2><?php esc_html_e( 'Already created', 'astroway' ); ?></h2>
				<ul>
					<?php foreach ( $have as $what => $id ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a>
							<?php
							$st = get_post_status_object( (string) get_post_status( $id ) );
							echo esc_html( $st && 'publish' !== $st->name ? ' (' . $st->label . ')' : '' );
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="astroway-hub-title"><?php esc_html_e( 'Hub page title', 'astroway' ); ?></label></th>
						<td><input type="text" class="regular-text" id="astroway-hub-title" name="hub_title" value="<?php echo esc_attr( __( 'Horoscopes', 'astroway' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'On each sign page', 'astroway' ); ?></th>
						<td><fieldset>
							<?php foreach ( $labels as $slug => $label ) : ?>
								<label><input type="checkbox" name="periods[]" value="<?php echo esc_attr( $slug ); ?>" checked> <?php echo esc_html( $label ); ?></label><br>
							<?php endforeach; ?>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Layout', 'astroway' ); ?></th>
						<td><fieldset>
							<label><input type="radio" name="layout" value="tabs" checked> <?php esc_html_e( 'Tabs (all text stays in the page)', 'astroway' ); ?></label><br>
							<label><input type="radio" name="layout" value="stack"> <?php esc_html_e( 'One after another', 'astroway' ); ?></label>
						</fieldset></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Status', 'astroway' ); ?></th>
						<td><fieldset>
							<label><input type="radio" name="status" value="draft" checked> <?php esc_html_e( 'Drafts, I will review them first', 'astroway' ); ?></label><br>
							<label><input type="radio" name="status" value="publish"> <?php esc_html_e( 'Publish now', 'astroway' ); ?></label>
						</fieldset></td>
					</tr>
				</table>
				<?php submit_button( __( 'Create pages', 'astroway' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'astroway' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$periods = array_values( array_intersect( self::PERIODS, array_map( 'sanitize_key', (array) wp_unslash( $_POST['periods'] ?? [] ) ) ) );
		$layout  = 'stack' === sanitize_key( wp_unslash( $_POST['layout'] ?? '' ) ) ? 'stack' : 'tabs';
		$status  = 'publish' === sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ) ? 'publish' : 'draft';
		$title   = sanitize_text_field( wp_unslash( $_POST['hub_title'] ?? '' ) );
		$title   = '' !== $title ? $title : __( 'Horoscopes', 'astroway' );
		if ( ! $periods ) {
			$periods = self::PERIODS;
		}

		$made = self::build( $periods, $layout, $status, $title );
		wp_safe_redirect(
			add_query_arg(
				[
					'page' => self::PAGE,
					'made' => $made,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Creates what is missing and returns how many pages were made. */
	public static function build( array $periods, string $layout, string $status, string $hub_title ): int {
		$have = self::existing();
		$made = 0;

		$hub = $have['hub'] ?? 0;
		if ( ! $hub ) {
			$hub = (int) wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => $status,
					'post_title'   => wp_slash( $hub_title ),
					'post_content' => '',
				]
			);
			if ( $hub ) {
				update_post_meta( $hub, self::META, 'hub' );
				++$made;
			}
		}

		foreach ( self::SIGNS as $sign ) {
			$id = $have[ $sign ] ?? 0;
			if ( ! $id ) {
				$id = (int) wp_insert_post(
					[
						'post_type'    => 'page',
						'post_status'  => $status,
						'post_parent'  => $hub,
						/* translators: %s: zodiac sign name */
						'post_title'   => sprintf( __( '%s horoscope', 'astroway' ), Render::sign_label( $sign ) ),
						'post_content' => self::sign_content( $sign, $periods, $layout ),
					]
				);
				if ( ! $id ) {
					continue;
				}
				update_post_meta( $id, self::META, $sign );
				++$made;
			}
		}

		// Written once; an edited hub is left alone. The list is the core Page List block over the
		// hub's children, so links stay right when drafts are published or pages move.
		if ( $hub && ! isset( $have['hub'] ) ) {
			wp_update_post(
				[
					'ID'           => $hub,
					'post_content' => self::hub_content( $hub ),
				]
			);
		}
		return $made;
	}

	private static function sign_content( string $sign, array $periods, string $layout ): string {
		$inner = '';
		foreach ( $periods as $block ) {
			$inner .= '<!-- wp:astroway/' . $block . ' /-->' . "\n\n";
		}
		$attrs = wp_json_encode(
			[
				'sign'   => $sign,
				'layout' => $layout,
			]
		);
		return '<!-- wp:astroway/astrology-section ' . $attrs . ' -->' . "\n" . $inner . '<!-- /wp:astroway/astrology-section -->';
	}

	private static function hub_content( int $hub ): string {
		return '<!-- wp:paragraph --><p>' . esc_html__( 'Pick your sign', 'astroway' ) . '</p><!-- /wp:paragraph -->' . "\n\n"
			. '<!-- wp:page-list ' . wp_json_encode( [ 'parentPageID' => $hub ] ) . ' /-->' . "\n\n"
			. '<!-- wp:astroway/moon-phase /-->';
	}
}
