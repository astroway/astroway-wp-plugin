<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	/**
	 * Locales accepted by api.astroway.info `/v1/horoscope/*` and `/v1/interpret/*`
	 * (Accept-Language Phase 6, shipped 2026-05-14 in api v2.30.0). Source of truth
	 * for plugin shortcode `lang=` attribute + Gutenberg block `lang` Inspector
	 * dropdown. Update this list when api expands locale coverage.
	 */
	public const SUPPORTED_LANGS = [
		'uk',
		'en',
		'de',
		'ru',
		'pl',
		'es',
		'pt',
		'fr',
		'it',
		'nl',
		'cs',
		'ro',
		'hu',
		'el',
		'tr',
		'ar',
		'hi',
		'ja',
		'ko',
		'vi',
		'id',
	];

	/**
	 * Map WP locale (`uk_UA`, `de_DE`, `pt_BR`, ...) to api short code (`uk`, `de`, `pt`).
	 * Falls back to `uk` if the site language is outside the api's supported set —
	 * `uk` is the source language for api content, so widgets render correctly there.
	 */
	public static function normalize_locale( string $wp_locale ): string {
		$short = strtolower( substr( $wp_locale, 0, 2 ) );
		return in_array( $short, self::SUPPORTED_LANGS, true ) ? $short : 'uk';
	}

	public static function boot(): void {
		// WP 4.6+ auto-loads textdomain from /languages when slug matches; no manual call needed for wp.org-hosted plugins.

		/**
		 * Fires after core plugin classes are loaded but before any registration.
		 * Addons should hook here to set up their own state.
		 *
		 * @since 0.6.0
		 */
		do_action( 'astroway_init' );

		Shortcodes::register();
		Blocks::register();
		Admin::register();
		Updater::boot();

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ] );
		add_action( 'admin_notices', [ __CLASS__, 'maybe_activation_notice' ] );
		add_action( 'wp_ajax_astroway_dismiss_activation_notice', [ __CLASS__, 'dismiss_activation_notice' ] );
	}

	public static function enqueue_frontend(): void {
		wp_enqueue_style(
			'astroway-widgets',
			ASTROWAY_WP_PLUGIN_URL . 'assets/css/astroway-widgets.css',
			[],
			ASTROWAY_WP_PLUGIN_VERSION
		);
	}

	public static function maybe_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'astroway_notice_dismissed', true ) ) {
			return;
		}

		$dismiss_url  = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=astroway_dismiss_activation_notice' ),
			'astroway_dismiss_activation_notice'
		);
		$settings_url = admin_url( 'admin.php?page=' . Admin::PAGE_SLUG );
		$signup_url   = 'https://api.astroway.info/dashboard/sign-up?source=wp_plugin';

		$star_svg = '<svg viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 1.5l2.36 5.34 5.83.55-4.4 3.85 1.31 5.71L10 13.93l-5.1 2.92 1.31-5.71-4.4-3.85 5.83-.55L10 1.5z"/></svg>';
		?>
		<div class="notice is-dismissible astroway-activation-notice" data-astroway-dismiss="<?php echo esc_url( $dismiss_url ); ?>">
			<style>
				.astroway-activation-notice{border-left:0;padding:14px 12px 14px 18px;background:#fff;display:flex;align-items:center;gap:16px;box-shadow:0 1px 1px rgba(0,0,0,.04)}
				.astroway-activation-notice .awn-mark{flex:0 0 auto;width:46px;height:46px;border-radius:50%;background:radial-gradient(circle at 30% 25%, #1d1c24 0%, #0a0a10 75%);display:flex;align-items:center;justify-content:center;box-shadow:inset 0 0 0 1px rgba(240,180,41,.35), 0 0 18px rgba(240,180,41,.18)}
				.astroway-activation-notice .awn-mark svg{width:24px;height:24px;color:#f0b429;filter:drop-shadow(0 0 4px rgba(240,180,41,.5))}
				.astroway-activation-notice .awn-text{flex:1 1 auto;min-width:0}
				.astroway-activation-notice .awn-title{margin:0 0 2px;font-size:14px;font-weight:600;color:#1a1815;line-height:1.3}
				.astroway-activation-notice .awn-desc{margin:0;font-size:13px;color:#4a4640;line-height:1.45}
				.astroway-activation-notice .awn-actions{flex:0 0 auto;display:flex;align-items:center;gap:8px;margin-right:24px}
				.astroway-activation-notice .awn-btn{display:inline-flex;align-items:center;height:30px;padding:0 14px;border-radius:4px;font-size:13px;line-height:30px;text-decoration:none;font-weight:500;border:1px solid transparent;white-space:nowrap}
				.astroway-activation-notice .awn-btn-primary{background:#f0b429;color:#1a1815;border-color:#b88419}
				.astroway-activation-notice .awn-btn-primary:hover{background:#ffd773;color:#1a1815}
				.astroway-activation-notice .awn-btn-secondary{background:transparent;color:#b88419;border-color:#d9d3c2}
				.astroway-activation-notice .awn-btn-secondary:hover{border-color:#f0b429;color:#b88419}
				.astroway-activation-notice .awn-btn:focus{box-shadow:0 0 0 2px rgba(240,180,41,.4);outline:none}
				@media (max-width:782px){.astroway-activation-notice{flex-wrap:wrap}.astroway-activation-notice .awn-actions{margin-right:30px;width:100%;margin-top:6px}}
			</style>
			<div class="awn-mark"><?php echo $star_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup ?></div>
			<div class="awn-text">
				<p class="awn-title"><?php esc_html_e( 'AstroWay is active', 'astroway' ); ?></p>
				<p class="awn-desc"><?php esc_html_e( 'Shortcodes work without an API key (30 requests/hour per visitor IP). For higher limits and Pro features, get a free API key.', 'astroway' ); ?></p>
			</div>
			<div class="awn-actions">
				<a href="<?php echo esc_url( $signup_url ); ?>" target="_blank" rel="noopener" class="awn-btn awn-btn-primary">
					<?php esc_html_e( 'Get free API key', 'astroway' ); ?>
				</a>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="awn-btn awn-btn-secondary">
					<?php esc_html_e( 'Open Settings', 'astroway' ); ?>
				</a>
			</div>
			<script>
				( function () {
					var n = document.currentScript && document.currentScript.parentNode;
					if ( ! n ) return;
					n.addEventListener( 'click', function ( e ) {
						if ( ! e.target.classList.contains( 'notice-dismiss' ) ) return;
						var url = n.getAttribute( 'data-astroway-dismiss' );
						if ( url ) { var img = new Image(); img.src = url; }
					}, true );
				} )();
			</script>
		</div>
		<?php
	}

	public static function dismiss_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		check_admin_referer( 'astroway_dismiss_activation_notice' );
		update_user_meta( get_current_user_id(), 'astroway_notice_dismissed', 1 );
		wp_send_json_success();
	}

	public static function activate(): void {
		if ( false === get_option( 'astroway_activated_at' ) ) {
			update_option( 'astroway_activated_at', time() );
		}
	}

	public static function deactivate(): void {
		// Per-user dismiss flag intentionally kept across deactivation cycles.
	}
}
