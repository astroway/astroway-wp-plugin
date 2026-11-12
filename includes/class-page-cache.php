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
 * LiteSpeed is only ever shortened. nginx gets X-Accel-Expires, which outranks
 * its own *_cache_valid, so a server cache configured for less than the time to
 * midnight keeps such a page longer than it would have: the filter
 * `astroway_page_cache_server_expires` turns the header off. Browsers and CDNs
 * get a Cache-Control only to shorten one the site already sends, and a
 * browser max-age shorter than ours stays.
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
		'astroway_dasha',
		'astroway_progressions',
		'astroway_sade_sati',
		'astroway_choghadiya',
	];

	/**
	 * The attribute that pins a card to a day of its own, where it is not `date`:
	 * on the birth cards `date` is the birth. Empty: the card is always today's.
	 */
	private const PINNED_BY = [
		'astroway_dasha'        => '',
		'astroway_progressions' => 'target_date',
		'dasha'                 => '',
		'astroway_sade_sati'    => '',
		'sade-sati'             => '',
		'progressions'          => 'target_date',
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
		'dasha',
		'progressions',
		'sade-sati',
		'choghadiya',
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

		if ( $seconds > self::HEADER_MAX || headers_sent() || is_user_logged_in() ) {
			return;
		}
		$lines = headers_list();
		$sent  = self::sent_lifetime( $lines );
		if ( null !== $sent && $sent <= $seconds ) {
			return;
		}
		if ( apply_filters( 'astroway_page_cache_server_expires', true ) ) {
			// nginx reads this and strips it, so a server cache expires at midnight while browsers and CDNs see nothing new.
			header( 'X-Accel-Expires: ' . $seconds );
		}
		$control = self::cache_control( $lines, $seconds );
		if ( null !== $control ) {
			header( 'Cache-Control: ' . $control );
		}
	}

	/**
	 * Before any output: a page whose content carries a card of the day expires at the site's midnight.
	 * The page is buffered so a card that fails later in the content can still shorten that to a minute.
	 */
	public static function early(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( $post && self::has_daily( (string) $post->post_content ) ) {
			self::cap( PublicData::ttl_for( 'day' ) );
			if ( null !== self::$ttl ) {
				self::buffer();
			}
		}
	}

	/**
	 * The Cache-Control to send for a page that may live $seconds, given the headers
	 * already set, or null to leave it: a page sent without one keeps none, and a
	 * shorter browser max-age than ours stays.
	 *
	 * @param string[] $lines Headers set so far.
	 */
	public static function cache_control( array $lines, int $seconds ): ?string {
		$sent = self::sent_lifetime( $lines );
		if ( null === $sent || $sent <= $seconds ) {
			return null;
		}
		$browser = self::browser_age( $lines );
		return sprintf( 'public, max-age=%d, s-maxage=%d', null === $browser ? $seconds : min( $browser, $seconds ), $seconds );
	}

	/**
	 * Holds the page until the end, so a card that fails later can still cap it, and
	 * nocache_headers() at a later hook also takes back our nginx lifetime. Opened only
	 * before the template: one opened mid-render would be swallowed by a builder's own
	 * capture. A buffer's handler, not header_register_callback(), which keeps only one
	 * callback per request and would replace another plugin's.
	 */
	private static function buffer(): void {
		static $started = false;
		// The command line sends no headers (wp-cli, tests).
		if ( $started || headers_sent() || 'cli' === PHP_SAPI ) {
			return;
		}
		$started = true;
		ob_start( [ __CLASS__, 'on_flush' ] );
	}

	/** @internal Output handler: the headers are still open when the buffer first flushes. */
	public static function on_flush( string $buffer ): string {
		if ( ! headers_sent() && self::forbids( headers_list() ) ) {
			header_remove( 'X-Accel-Expires' );
		}
		return $buffer;
	}

	/** @param string[] $lines Headers set so far. */
	public static function forbids( array $lines ): bool {
		return 0 === self::sent_lifetime( $lines );
	}

	/** Whether the text holds a card of the day without a date of its own. */
	public static function has_daily( string $content ): bool {
		if ( preg_match_all( '/\[(astroway_[a-z_]+)(\s[^\]]*)?\]/', $content, $codes, PREG_SET_ORDER ) ) {
			foreach ( $codes as $code ) {
				$pin = self::PINNED_BY[ $code[1] ] ?? 'date';
				if ( in_array( $code[1], self::DAILY_SHORTCODES, true ) && ( '' === $pin || ! preg_match( '/(?<![a-z_])' . $pin . '\s*=\s*["\']?\d{4}-\d{2}-\d{2}/', $code[2] ?? '' ) ) ) {
					return true;
				}
			}
		}
		if ( preg_match_all( '/<!--\s*wp:astroway\/([a-z0-9-]+)(\s+(\{.*?\}))?\s*\/?-->/s', $content, $blocks, PREG_SET_ORDER ) ) {
			foreach ( $blocks as $block ) {
				$attrs = isset( $block[3] ) ? json_decode( $block[3], true ) : null;
				$pin   = self::PINNED_BY[ $block[1] ] ?? 'date';
				$date  = is_array( $attrs ) && '' !== $pin ? (string) ( $attrs[ $pin ] ?? '' ) : '';
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

	/**
	 * Seconds a Cache-Control already sent lets a shared cache keep the page: 0 when it
	 * forbids caching (the forms send no-store), null when none was sent.
	 */
	private static function sent_lifetime( array $lines ): ?int {
		$life = null;
		foreach ( $lines as $line ) {
			if ( 0 !== stripos( $line, 'cache-control:' ) ) {
				continue;
			}
			if ( preg_match( '/no-cache|no-store|private/i', $line ) ) {
				return 0;
			}
			if ( preg_match( '/s-maxage\s*=\s*(\d+)/i', $line, $m ) || preg_match( '/max-age\s*=\s*(\d+)/i', $line, $m ) ) {
				$life = (int) $m[1];
			}
		}
		return $life;
	}

	/** The browser's max-age in the Cache-Control already set, or null. */
	private static function browser_age( array $lines ): ?int {
		foreach ( $lines as $line ) {
			if ( 0 === stripos( $line, 'cache-control:' ) && preg_match( '/(?<![-a-z])max-age\s*=\s*(\d+)/i', $line, $m ) ) {
				return (int) $m[1];
			}
		}
		return null;
	}
}
