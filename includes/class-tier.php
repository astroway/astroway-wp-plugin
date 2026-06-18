<?php
/**
 * Tier resolution — single source of truth for the user's plan level.
 *
 * Reads /v1/auth/keys/me via ApiClient (30 min transient cache). Returns
 * a normalized tier string. AddonAPI::current_tier() delegates here.
 *
 * @since 0.7.0
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tier {

	/**
	 * Tiers a paying customer can be on. Used by Channel B updater + feature gates.
	 */
	public const PAID = [ 'indie', 'starter', 'pro', 'business' ];

	/**
	 * Recognised tier values returned by api `/v1/auth/keys/me`.
	 */
	public const ALL = [ 'anonymous', 'free', 'indie', 'starter', 'pro', 'business', 'internal' ];

	/**
	 * Resolve the current site's tier.
	 *
	 * Returns 'anonymous' if no key configured, 'free' if key invalid/expired,
	 * otherwise whatever plan the api returns.
	 */
	public static function current(): string {
		$client = new ApiClient();
		if ( ! $client->has_key() ) {
			return 'anonymous';
		}
		$resp = $client->get_keys_me();
		if ( 200 !== ( $resp['status'] ?? 0 ) ) {
			return 'free';
		}
		$plan = $resp['data']['data']['plan'] ?? null;
		return is_string( $plan ) && '' !== $plan ? $plan : 'free';
	}

	/**
	 * Feature → minimum tier matrix. Higher tiers inherit lower-tier features.
	 *
	 * Default v1 (5 shortcodes/blocks) are anonymous-accessible — iframe
	 * embeds rate-limited by IP on api side. Pro features (synastry,
	 * native render, AI chat) require paid tiers.
	 *
	 * @return array<string, list<string>>
	 */
	public static function matrix(): array {
		/**
		 * Filters the feature → allowed tiers matrix. Addons can append
		 * their own feature names here.
		 *
		 * @since 0.7.1
		 *
		 * @param array $matrix Default matrix.
		 */
		return apply_filters( 'astroway_tier_matrix', self::matrix_default() );
	}

	private static function matrix_default(): array {
		$all_tiers = self::ALL;
		$free_plus = [ 'free', 'indie', 'starter', 'pro', 'business', 'internal' ];
		$paid_plus = [ 'indie', 'starter', 'pro', 'business', 'internal' ];
		$pro_plus  = [ 'pro', 'business', 'internal' ];

		return [
			// v1 widgets (5 shortcodes/blocks) — anonymous OK.
			'natal'                  => $all_tiers,
			'daily_horoscope'        => $all_tiers,
			'moon_phase'             => $all_tiers,
			'bodygraph'              => $all_tiers,
			'daily_tarot'            => $all_tiers,
			// Paid-only (v0.8+ features).
			'synastry'               => $paid_plus,
			'solar_return'           => $paid_plus,
			'lunar_return'           => $paid_plus,
			'progressions'           => $paid_plus,
			'native_render'          => $paid_plus,
			// Pro+ only.
			'ai_chat'                => $pro_plus,
			'transit_alerts'         => $pro_plus,
			// Free-tier and above (key required).
			'custom_interpretations' => $free_plus,
		];
	}

	/**
	 * Whether the current tier can access a given feature.
	 *
	 * Unknown features default to allowed (forward-compatible).
	 */
	public static function can( string $feature ): bool {
		$matrix = self::matrix();
		if ( ! isset( $matrix[ $feature ] ) ) {
			return true;
		}
		return in_array( self::current(), $matrix[ $feature ], true );
	}

	/**
	 * Render a unified upgrade CTA for a locked feature.
	 *
	 * Filterable via `astroway_upgrade_cta_html` so themes / addons can
	 * override the markup or copy.
	 *
	 * @since 0.7.4
	 */
	public static function render_upgrade_cta( string $feature ): string {
		$upgrade_url = add_query_arg(
			[
				'source'  => 'wp_plugin',
				'feature' => $feature,
			],
			'https://api.astroway.info/dashboard/upgrade'
		);

		$label = ucwords( str_replace( '_', ' ', $feature ) );

		ob_start();
		?>
		<div class="astroway-locked" role="region" aria-label="<?php esc_attr_e( 'Locked feature', 'astroway' ); ?>">
			<style>
				.astroway-locked{border:1px solid #d9d3c2;border-radius:6px;padding:14px 18px;background:#fff;display:flex;align-items:center;gap:14px;color:#1a1815;font-size:14px}
				.astroway-locked .awl-text{flex:1 1 auto;min-width:0}
				.astroway-locked .awl-title{margin:0 0 2px;font-weight:600;color:#1a1815}
				.astroway-locked .awl-desc{margin:0;color:#4a4640;font-size:13px}
				.astroway-locked .awl-btn{display:inline-flex;align-items:center;height:32px;padding:0 14px;border-radius:4px;background:#f0b429;color:#1a1815;font-weight:500;text-decoration:none;border:1px solid #b88419}
				.astroway-locked .awl-btn:hover{background:#ffd773}
			</style>
			<div class="awl-text">
				<p class="awl-title"><?php echo esc_html( $label ); ?> — <?php esc_html_e( 'Pro feature', 'astroway' ); ?></p>
				<p class="awl-desc"><?php esc_html_e( 'This widget is available on a paid AstroWay plan.', 'astroway' ); ?></p>
			</div>
			<a class="awl-btn" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Upgrade →', 'astroway' ); ?>
			</a>
		</div>
		<?php
		$html = (string) ob_get_clean();

		/**
		 * Filters the locked-feature upgrade CTA HTML.
		 *
		 * @since 0.7.4
		 *
		 * @param string $html    Default CTA markup.
		 * @param string $feature Feature name being locked.
		 */
		return apply_filters( 'astroway_upgrade_cta_html', $html, $feature );
	}
}
