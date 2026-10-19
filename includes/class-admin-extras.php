<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The plugin outside its own pages: a box on the Dashboard, a section and a
 * test in Site Health, and Try it in the shortcode reference. Everything here
 * reads what the plugin's own admin already shows; nothing new is fetched.
 *
 * @since 2.5.0
 */
final class AdminExtras {

	public static function register(): void {
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'dashboard_setup' ] );
		add_filter( 'debug_information', [ __CLASS__, 'site_health_info' ] );
		add_filter( 'site_status_tests', [ __CLASS__, 'site_health_tests' ] );
		add_action( 'wp_ajax_astroway_try_shortcode', [ __CLASS__, 'ajax_try' ] );
	}

	/** Where the key comes from, the account's plan and credits, the last limit hit, the cache. */
	public static function summary(): array {
		$client = new ApiClient();
		$key    = $client->key();
		if ( Key::from_constant() ) {
			$source = 'constant';
		} elseif ( '' !== $key ) {
			$source = 'settings';
		} else {
			$source = '' !== SiteKey::key() ? 'site' : 'none';
		}

		$status = [];
		if ( '' !== $key ) {
			$me = $client->get_keys_me();
			if ( 200 === (int) ( $me['status'] ?? 0 ) ) {
				$status = (array) ( $me['data']['data'] ?? [] );
			}
		}

		return [
			'version'           => ASTROWAY_WP_PLUGIN_VERSION,
			'source'            => $source,
			'plan'              => (string) ( $status['plan'] ?? '' ),
			'credits_remaining' => isset( $status['credits_remaining'] ) ? (int) $status['credits_remaining'] : null,
			'credits_total'     => isset( $status['credits_total_this_period'] ) ? (int) $status['credits_total_this_period'] : null,
			'unlimited'         => ! empty( $status['unlimited'] ),
			'period_end'        => (string) ( $status['period_end'] ?? '' ),
			'domain'            => (string) ( $status['domain'] ?? '' ),
			'quota'             => PublicData::quota_seen( DAY_IN_SECONDS ),
			'render_mode'       => (string) Admin::get( 'render_mode', 'auto' ),
			'cache'             => Cache::stats(),
		];
	}

	public static function source_label( string $source ): string {
		$labels = [
			'constant' => __( 'API key in wp-config.php', 'astroway' ),
			'settings' => __( 'API key in AstroWay settings', 'astroway' ),
			'site'     => __( 'the site key this site was issued', 'astroway' ),
			'none'     => __( 'no key yet', 'astroway' ),
		];
		return $labels[ $source ] ?? $labels['none'];
	}

	public static function dashboard_setup(): void {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget( 'astroway_status', __( 'AstroWay', 'astroway' ), [ __CLASS__, 'render_dashboard' ] );
		}
	}

	public static function render_dashboard(): void {
		echo self::dashboard_html( self::summary() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}

	public static function dashboard_html( array $s ): string {
		$rows   = [];
		$rows[] = [ __( 'Key', 'astroway' ), self::source_label( (string) ( $s['source'] ?? 'none' ) ) ];
		if ( '' !== (string) ( $s['plan'] ?? '' ) ) {
			$rows[] = [ __( 'Plan', 'astroway' ), (string) $s['plan'] ];
		}
		if ( ! empty( $s['unlimited'] ) ) {
			$rows[] = [ __( 'Credits', 'astroway' ), __( 'Unlimited', 'astroway' ) ];
		} elseif ( null !== ( $s['credits_remaining'] ?? null ) && null !== ( $s['credits_total'] ?? null ) ) {
			$rows[] = [
				__( 'Credits', 'astroway' ),
				/* translators: 1: credits left, 2: credits in the period */
				sprintf( __( '%1$s of %2$s left', 'astroway' ), self::number( (int) $s['credits_remaining'] ), self::number( (int) $s['credits_total'] ) ),
			];
		}
		$end = strtotime( (string) ( $s['period_end'] ?? '' ) );
		if ( false !== $end && '' !== (string) ( $s['period_end'] ?? '' ) ) {
			$rows[] = [ __( 'Period ends', 'astroway' ), gmdate( 'Y-m-d', $end ) ];
		}
		if ( '' !== (string) ( $s['domain'] ?? '' ) ) {
			$rows[] = [ __( 'Bound to', 'astroway' ), (string) $s['domain'] ];
		}

		$html = '<table class="widefat striped"><tbody>';
		foreach ( $rows as [ $label, $value ] ) {
			$html .= '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		$html .= '</tbody></table>';

		$quota = is_array( $s['quota'] ?? null ) ? $s['quota'] : null;
		if ( null !== $quota && ( 429 === (int) ( $quota['status'] ?? 0 ) || 0 === (int) ( $quota['remaining'] ?? 1 ) ) ) {
			$html .= '<p><strong>' . esc_html(
				sprintf(
					/* translators: %s = time of day, such as 14:05 */
					__( 'The hourly limit was reached at %s (UTC); widgets fell back to frames until it reset.', 'astroway' ),
					gmdate( 'H:i', (int) ( $quota['at'] ?? time() ) )
				)
			) . '</strong></p>';
		}
		if ( 'none' === ( $s['source'] ?? 'none' ) ) {
			$html .= '<p>' . esc_html__( 'Widgets that need no key work already. Connect the site to unlock the rest.', 'astroway' ) . '</p>';
		}
		$html .= '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . Admin::PAGE_API_KEY ) ) . '">' . esc_html__( 'Open AstroWay', 'astroway' ) . '</a></p>';
		return $html;
	}

	/** @param array $info Site Health sections. */
	public static function site_health_info( $info ) {
		$info             = is_array( $info ) ? $info : [];
		$info['astroway'] = [
			'label'  => __( 'AstroWay', 'astroway' ),
			'fields' => self::info_fields( self::summary() ),
		];
		return $info;
	}

	/** Facts only, never the key itself: this block is pasted into support threads. */
	public static function info_fields( array $s ): array {
		$cache = (array) ( $s['cache'] ?? [] );
		return [
			'version'     => [
				'label' => __( 'Plugin version', 'astroway' ),
				'value' => (string) ( $s['version'] ?? '' ),
			],
			'key_source'  => [
				'label' => __( 'Key', 'astroway' ),
				'value' => self::source_label( (string) ( $s['source'] ?? 'none' ) ),
			],
			'plan'        => [
				'label' => __( 'Plan', 'astroway' ),
				'value' => '' === (string) ( $s['plan'] ?? '' ) ? '-' : (string) $s['plan'],
			],
			'domain'      => [
				'label' => __( 'Bound to', 'astroway' ),
				'value' => '' === (string) ( $s['domain'] ?? '' ) ? '-' : (string) $s['domain'],
			],
			'cache'       => [
				'label' => __( 'Cached answers', 'astroway' ),
				/* translators: 1: number of cached answers, 2: their size in KB */
				'value' => sprintf( __( '%1$d, %2$d KB', 'astroway' ), (int) ( $cache['count'] ?? 0 ), (int) round( ( (int) ( $cache['bytes'] ?? 0 ) ) / 1024 ) ),
			],
			'render_mode' => [
				'label' => __( 'Render mode', 'astroway' ),
				'value' => (string) ( $s['render_mode'] ?? 'auto' ),
			],
		];
	}

	/** @param array $tests Site Health tests. */
	public static function site_health_tests( $tests ) {
		$tests                           = is_array( $tests ) ? $tests : [];
		$tests['direct']['astroway_key'] = [
			'label' => __( 'AstroWay key', 'astroway' ),
			'test'  => [ __CLASS__, 'test_key' ],
		];
		return $tests;
	}

	public static function test_key(): array {
		return self::key_result( (string) self::summary()['source'] );
	}

	public static function key_result( string $source ): array {
		$good = 'none' !== $source;
		return [
			'label'       => $good
				? __( 'AstroWay has a key', 'astroway' )
				: __( 'AstroWay has no key yet', 'astroway' ),
			'status'      => $good ? 'good' : 'recommended',
			'badge'       => [
				'label' => __( 'AstroWay', 'astroway' ),
				'color' => 'blue',
			],
			'description' => '<p>' . esc_html(
				$good
					/* translators: %s = where the key comes from */
					? sprintf( __( 'Widgets use %s.', 'astroway' ), self::source_label( $source ) )
					: __( 'Widgets that need no key work. The site asks for its own key on its own; if it cannot, connect it on the AstroWay page.', 'astroway' )
			) . '</p>',
			'actions'     => $good ? '' : '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . Admin::PAGE_API_KEY ) ) . '">' . esc_html__( 'Open AstroWay', 'astroway' ) . '</a></p>',
			'test'        => 'astroway_key',
		];
	}

	/** Tag to example: the hand-built cards of the reference page, then the generated shortcodes. */
	public static function examples(): array {
		$out   = [];
		$cards = require ASTROWAY_WP_PLUGIN_DIR . 'includes/views/partials/shortcode-cards.php';
		foreach ( (array) $cards as $card ) {
			if ( is_array( $card ) && ! empty( $card['tag'] ) && ! empty( $card['example'] ) ) {
				$out[ (string) $card['tag'] ] = (string) $card['example'];
			}
		}
		if ( class_exists( __NAMESPACE__ . '\\GeneratedRegistry' ) ) {
			foreach ( GeneratedRegistry::reference_by_family() as $family ) {
				foreach ( (array) ( $family['endpoints'] ?? [] ) as $tag => $endpoint ) {
					if ( ! isset( $out[ $tag ] ) && ! empty( $endpoint['example'] ) ) {
						$out[ (string) $tag ] = (string) $endpoint['example'];
					}
				}
			}
		}
		return $out;
	}

	public static function example_for( string $tag ): string {
		return self::examples()[ $tag ] ?? '';
	}

	/** @return array{ok: bool, code: int, html: string} */
	public static function try_render( string $tag ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return [
				'ok'   => false,
				'code' => 403,
				'html' => '',
			];
		}
		$example = self::example_for( $tag );
		if ( '' === $example ) {
			return [
				'ok'   => false,
				'code' => 400,
				'html' => '',
			];
		}
		Plugin::use_styles();
		return [
			'ok'   => true,
			'code' => 200,
			'html' => (string) do_shortcode( $example ),
		];
	}

	public static function ajax_try(): void {
		check_ajax_referer( 'astroway_try', 'nonce' );
		$tag = isset( $_POST['tag'] ) ? sanitize_key( wp_unslash( $_POST['tag'] ) ) : '';
		$out = self::try_render( $tag );
		if ( $out['ok'] ) {
			wp_send_json_success( [ 'html' => $out['html'] ] );
		}
		wp_send_json_error( [ 'message' => __( 'This shortcode cannot be tried here.', 'astroway' ) ], $out['code'] );
	}

	private static function number( int $n ): string {
		return function_exists( 'number_format_i18n' ) ? number_format_i18n( $n ) : number_format( $n );
	}
}
