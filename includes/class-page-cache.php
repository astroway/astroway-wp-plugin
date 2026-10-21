<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps a page cache from serving a card past the moment its answer changes.
 *
 * The cards are rendered into the page, so a page cache that keeps the page
 * for a week keeps yesterday's horoscope, moon and tarot card for a week too.
 * Every read that is true only until a boundary reports how long it has left,
 * and the page is told to expire no later than the earliest of them.
 *
 * Only ever shortens. A page cache that would not have kept the page is not
 * asked to, and a configured lifetime shorter than ours is left alone.
 */
class PageCache {

	/** Anything this long is a chart, not a day, and not worth expiring a page over. */
	private const STATIC_FLOOR = 30 * DAY_IN_SECONDS;

	/** A Cache-Control header is sent only for this short a life, never a week of browser cache. */
	private const HEADER_MAX = DAY_IN_SECONDS;

	/** Shortcodes and blocks whose answer is today's when no date is given. */
	private const DAILY_SHORTCODES = [
		'astroway_daily_horoscope',
		'astroway_planet_of_day',
		'astroway_moon_phase',
		'astroway_tarot_card',
		'astroway_tarot_spread',
		'astroway_today_in_sky',
		'astroway_transit',
		'astroway_moon_voc',
		'astroway_planetary_hours',
		'astroway_panchang',
	];

	private const DAILY_BLOCKS = [
		'daily-horoscope',
		'planet-of-day',
		'moon-phase',
		'daily-tarot',
		'tarot-spread',
		'transit',
		'moon-voc',
		'planetary-hours',
		'panchang',
	];

	/** @var int|null Seconds the page may live, the least reported so far. */
	private static $ttl = null;

	public static function register(): void {
		// Shortcodes render inside the content, after the headers have gone; a
		// header has to be decided before that, from the post's own text.
		add_action( 'template_redirect', [ __CLASS__, 'early' ] );
	}

	/**
	 * The page holds an answer that stays true for this many more seconds.
	 *
	 * @param int $seconds Time left until the answer changes.
	 */
	public static function cap( int $seconds ): void {
		if ( $seconds >= self::STATIC_FLOOR || ! self::applies() ) {
			return;
		}
		$seconds = max( MINUTE_IN_SECONDS, $seconds );
		if ( null !== self::$ttl && $seconds >= self::$ttl ) {
			return;
		}
		self::$ttl = $seconds;

		// The action replaces LiteSpeed's lifetime outright, so it must never lengthen a shorter configured one.
		$configured = apply_filters( 'litespeed_conf', 'cache-ttl_pub' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed's own filter.
		if ( ! is_numeric( $configured ) || $seconds < (int) $configured ) {
			do_action( 'litespeed_control_set_ttl', $seconds ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed's own action.
		}

		if ( $seconds <= self::HEADER_MAX && ! headers_sent() && ! is_user_logged_in() && ! self::header_forbids_cache() ) {
			header( sprintf( 'Cache-Control: public, max-age=%1$d, s-maxage=%1$d', $seconds ) );
		}
	}

	/** Before any output: a page whose content carries a card of the day expires at the site's midnight. */
	public static function early(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( $post && self::has_daily( (string) $post->post_content ) ) {
			self::cap( PublicData::ttl_for( 'day' ) );
		}
	}

	/** Whether the text holds a card of the day without a date of its own. */
	public static function has_daily( string $content ): bool {
		if ( preg_match_all( '/\[(astroway_[a-z_]+)(\s[^\]]*)?\]/', $content, $codes, PREG_SET_ORDER ) ) {
			foreach ( $codes as $code ) {
				if ( in_array( $code[1], self::DAILY_SHORTCODES, true ) && ! preg_match( '/\bdate\s*=\s*["\']?\d{4}-\d{2}-\d{2}/', $code[2] ?? '' ) ) {
					return true;
				}
			}
		}
		if ( preg_match_all( '/<!--\s*wp:astroway\/([a-z0-9-]+)(\s+(\{.*?\}))?\s*\/?-->/s', $content, $blocks, PREG_SET_ORDER ) ) {
			foreach ( $blocks as $block ) {
				$attrs = isset( $block[3] ) ? json_decode( $block[3], true ) : null;
				$date  = is_array( $attrs ) ? (string) ( $attrs['date'] ?? '' ) : '';
				if ( in_array( $block[1], self::DAILY_BLOCKS, true ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/** The lifetime asked for on this request, or null. */
	public static function ttl(): ?int {
		return self::$ttl;
	}

	/** For tests: a new request. */
	public static function reset(): void {
		self::$ttl = null;
	}

	/** Page caches only: not the editor, not REST, not a request nobody caches. */
	private static function applies(): bool {
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		return true;
	}

	/** A no-cache already sent (the forms send one) is stricter than ours and stands. */
	private static function header_forbids_cache(): bool {
		foreach ( headers_list() as $line ) {
			if ( 0 === stripos( $line, 'cache-control:' ) && preg_match( '/no-cache|no-store|private/i', $line ) ) {
				return true;
			}
		}
		return false;
	}
}
