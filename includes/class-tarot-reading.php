<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The reading of a tarot spread, asked for by the page once the cards are open.
 *
 * A reading costs the site 100 credits, so the cards are never taken from the
 * browser: the route draws the same spread again from its seed (the draw is
 * cached, so this costs nothing), keeps each reading for a day, and stops at a
 * daily number of new readings so that forged seeds cannot run up a bill.
 *
 * @since 2.5.0
 */
final class TarotReading {

	public const ROUTE_NS = 'astroway/v1';
	public const ROUTE    = '/tarot-reading';

	private const SPREADS = [
		'tarot_three_card'   => 'three-card',
		'tarot_celtic_cross' => 'celtic-cross',
	];

	/** Where each spread is drawn, for the seed of the spread of the day. */
	private const PATHS = [
		'tarot_three_card'   => '/public/tarot/three-card',
		'tarot_celtic_cross' => '/public/tarot/celtic-cross',
	];

	/** New readings counted per UTC day: `[ date, count ]`. */
	private const COUNTER = 'astroway_tarot_readings';

	private const TIMEOUT = 25;

	private const LOCK = 'astroway_tarot_reading_lock';

	/** The value of the lock this request holds, '' when it holds none. */
	private static string $token = '';

	public static function register(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
	}

	public static function routes(): void {
		register_rest_route(
			self::ROUTE_NS,
			self::ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'respond' ],
				// A visitor's page asks for it; the checks are in handle().
				'permission_callback' => '__return_true',
			]
		);
	}

	/** @param \WP_REST_Request $request */
	public static function respond( $request ) {
		$out = self::handle( (array) $request->get_json_params() );
		return new \WP_REST_Response( $out, $out['ok'] ? 200 : 422 );
	}

	/**
	 * @param array $ask `widget`, `seed`, `lang`, `art`.
	 * @return array{ok: bool, html: string, code: string, note: string}
	 */
	public static function handle( array $ask ): array {
		$widget = (string) ( $ask['widget'] ?? '' );
		if ( ! isset( self::SPREADS[ $widget ] ) ) {
			return self::refuse( 'widget' );
		}
		if ( ! Form::same_origin() ) {
			return self::refuse( 'origin' );
		}
		$lang           = Plugin::resolve_lang( (string) ( $ask['lang'] ?? '' ) );
		$params         = [
			'lang' => $lang,
			'art'  => Tarot::sanitize_art( (string) ( $ask['art'] ?? '' ) ),
		];
		$params['seed'] = max( 0, min( 0x7fffffff, (int) ( $ask['seed'] ?? 0 ) ) );
		$of_the_day     = PublicData::seed( self::PATHS[ $widget ], time() ) === $params['seed'];
		// The spread of the day is one draw and one reading for every visitor, both
		// cached, so a shared address must not lock people out of it. A fresh
		// draw is a real call each time, and that is what the limit is for.
		if ( ! $of_the_day && ! Form::allow( time() ) ) {
			return self::refuse( 'busy' );
		}
		$data  = PublicData::get( $widget, $params );
		$drawn = array_values( array_filter( (array) ( $data['drawn'] ?? [] ), 'is_array' ) );
		if ( empty( $drawn ) ) {
			return self::refuse( 'spread' );
		}

		$cache = 'tarot_reading_' . md5( self::SPREADS[ $widget ] . '|' . $lang . '|' . self::signature( $drawn ) );
		$html  = Cache::get( $cache );
		if ( is_string( $html ) && '' !== $html ) {
			return self::answer( $html );
		}
		if ( Cache::get( $cache . '_x' ) ) {
			return self::refuse( 'mismatch' );
		}

		// The owner's key only: /interpret/tarot is not in the site key's set.
		$key = ( new ApiClient() )->key();
		if ( '' === $key ) {
			return self::refuse( 'nokey' );
		}
		if ( ! self::lock() ) {
			// The page asks again in a few seconds and most likely finds it cached.
			return self::refuse( 'wait' );
		}
		try {
			// Whoever held the lock may have just read these very cards.
			$html = Cache::get( $cache );
			if ( is_string( $html ) && '' !== $html ) {
				return self::answer( $html );
			}
			if ( ! self::count_one( 1 ) ) {
				return self::refuse( 'cap' );
			}
			$reading = self::interpret( $key, self::SPREADS[ $widget ], $drawn, $lang );
			if ( null === $reading ) {
				// Nothing came back, so nothing was charged.
				self::count_one( -1 );
				return self::refuse( 'failed' );
			}
			// A reading of other cards was still charged: the unit stays spent, and
			// the refusal is kept an hour so each page view is not another paid call.
			$html = self::signature( (array) ( $reading['positions'] ?? [] ) ) === self::signature( $drawn ) ? self::render( $reading ) : '';
			if ( '' === $html ) {
				Cache::set( $cache . '_x', 1, HOUR_IN_SECONDS );
				return self::refuse( 'mismatch' );
			}
			Cache::set( $cache, $html, DAY_IN_SECONDS );
			return self::answer( $html );
		} finally {
			self::unlock();
		}
	}

	/**
	 * One new reading at a time across the site.
	 *
	 * INSERT IGNORE straight into the options table, as core's own upgrader lock
	 * does: add_option() reads first and then upserts, so two requests in the same
	 * second could both take it. The value carries a token, so a request only ever
	 * removes its own lock, and a lock older than the call can take belongs to a
	 * request that died: only the one request that deletes that very value may
	 * take its place.
	 */
	private static function lock(): bool {
		global $wpdb;
		$token = time() . ':' . bin2hex( random_bytes( 6 ) );
		if ( self::insert_lock( $token ) ) {
			return true;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the lock must bypass the options cache.
		$held = (string) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::LOCK ) );
		if ( '' === $held || (int) $held > time() - self::TIMEOUT - 30 ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( 1 !== (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK, $held ) ) ) {
			return false;
		}
		return self::insert_lock( $token );
	}

	private static function insert_lock( string $token ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( 1 !== (int) $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", self::LOCK, $token ) ) ) {
			return false;
		}
		self::$token = $token;
		return true;
	}

	private static function unlock(): void {
		global $wpdb;
		if ( '' !== self::$token ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK, self::$token ) );
			self::$token = '';
		}
	}

	/**
	 * The api's spread of the day with its reading, for a site without the
	 * owner's key: the cards shaped like a public spread, the reading as HTML.
	 * null when the answer is missing or not three cards.
	 *
	 * @return array{data: array, html: string}|null
	 */
	public static function daily( string $lang, string $art ): ?array {
		$r         = PublicData::get(
			'tarot_daily_reading',
			[
				'lang' => $lang,
				'art'  => $art,
			]
		);
		$positions = array_values( array_filter( (array) ( $r['positions'] ?? [] ), 'is_array' ) );
		if ( 3 !== count( $positions ) ) {
			return null;
		}
		// A reading the api could only give in another language is not this page's.
		$answered = (string) ( $r['language'] ?? '' );
		if ( '' !== $answered && Plugin::resolve_lang( $answered ) !== Plugin::resolve_lang( $lang ) ) {
			return null;
		}
		$english = 'en' === Plugin::resolve_lang( $lang );
		$drawn   = [];
		$cards   = [];
		$places  = [];
		foreach ( $positions as $i => $p ) {
			$card = (array) ( $p['card'] ?? [] );
			$slug = (string) ( $card['slug'] ?? '' );
			if ( '' === $slug ) {
				return null;
			}
			// A major arcanum has no suit; a minor one is read off the slug in case it came without it.
			if ( empty( $card['suit'] ) && preg_match( '/-of-(wands|cups|swords|pentacles)$/', $slug, $m ) ) {
				$card['suit'] = $m[1];
			}
			$drawn[] = [
				'position' => [
					'index'   => $i,
					'name'    => (string) ( $p['position'] ?? '' ),
					'meaning' => (string) ( $p['positionMeaning'] ?? '' ),
				],
				'card'     => $card,
				'reversed' => ! empty( $p['reversed'] ),
			];
			if ( ! $english ) {
				$name = trim( (string) ( $card['nameLocalized'] ?? '' ) );
				$loc  = '' !== $name ? [
					'name'          => $name,
					'nameLocalized' => true,
				] : [];
				// meaningLocalized is the side the card came out on (api 2.228.4).
				$side = empty( $p['reversed'] ) ? 'upright' : 'reversed';
				if ( ! empty( $card['textLocalized'] ) && is_array( $card['meaningLocalized'] ?? null ) ) {
					$loc[ $side ]         = $card['meaningLocalized'];
					$loc['textLocalized'] = true;
				}
				if ( $loc ) {
					$cards[ $slug ] = $loc;
				}
				// An English gloss of the place is left out; a translated one is shown.
				$places[] = [
					'index'   => $i,
					'name'    => trim( (string) ( $p['positionLocalized'] ?? '' ) ),
					'meaning' => trim( (string) ( $p['positionMeaningLocalized'] ?? '' ) ),
				];
			}
		}
		$html = self::render( $r );
		if ( '' === $html ) {
			return null;
		}
		$data = [
			'date'   => (string) ( $r['date'] ?? '' ),
			'spread' => is_array( $r['spread'] ?? null ) ? $r['spread'] : [],
			'drawn'  => $drawn,
		];
		if ( is_array( $r['back'] ?? null ) ) {
			$data['back'] = $r['back'];
		}
		if ( ! $english ) {
			$data['localized'] = [
				'cards'         => $cards,
				'positions'     => $places,
				'textLocalized' => true,
			];
		}
		return [
			'data' => $data,
			'html' => $html,
		];
	}

	/**
	 * Which cards came out and which way up: the same draw reads the same. Also
	 * read off the answer's positions, since the api has answered one spread with
	 * another's reading.
	 */
	private static function signature( array $cards ): string {
		$parts = [];
		foreach ( $cards as $one ) {
			$parts[] = ( is_array( $one ) ? (string) ( $one['card']['slug'] ?? '' ) . ( empty( $one['reversed'] ) ? '+' : '-' ) : '?' );
		}
		return implode( ',', $parts );
	}

	/**
	 * Counts a new reading against today's cap (false once it is reached), or
	 * gives one back with -1 when the call did not bring a reading.
	 *
	 * Three a day by default: a reading costs 100 credits and a free key has
	 * 10,000 a month, while a fresh draw is a visitor's click.
	 */
	private static function count_one( int $step ): bool {
		$today = gmdate( 'Y-m-d' );
		$limit = (int) apply_filters( 'astroway_tarot_readings_per_day', 3 );
		$seen  = get_option( self::COUNTER, [] );
		$count = is_array( $seen ) && ( $seen[0] ?? '' ) === $today ? (int) ( $seen[1] ?? 0 ) : 0;
		if ( $step > 0 && $count >= $limit ) {
			return false;
		}
		update_option( self::COUNTER, [ $today, max( 0, $count + $step ) ], false );
		return true;
	}

	private static function interpret( string $key, string $spread, array $drawn, string $lang ): ?array {
		$response = wp_remote_post(
			rtrim( ASTROWAY_API_BASE, '/' ) . '/interpret/tarot',
			[
				'timeout' => self::TIMEOUT,
				'headers' => [
					'Accept'              => 'application/json',
					'Content-Type'        => 'application/json',
					'X-Api-Key'           => $key,
					'X-AstroWay-Site-URL' => home_url(),
				],
				'body'    => (string) wp_json_encode(
					[
						'deck'     => 'rider-waite',
						'spread'   => $spread,
						'cards'    => $drawn,
						'question' => '',
						'language' => $lang,
					]
				),
			]
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $body ) && ! empty( $body['ok'] ) && is_array( $body['data'] ?? null ) ? $body['data'] : null;
	}

	/** The summary, the advice, each position under its own fold, the disclaimer. */
	private static function render( array $r ): string {
		$html = '';
		$sum  = trim( (string) ( $r['synthesis'] ?? '' ) );
		if ( '' !== $sum ) {
			$html .= UI::section( __( 'Summary', 'astroway' ), UI::prose( '<p>' . esc_html( $sum ) . '</p>' ) );
		}
		$advice = trim( (string) ( $r['advice'] ?? '' ) );
		if ( '' !== $advice ) {
			$html .= UI::section( __( 'Advice', 'astroway' ), UI::callout( esc_html( $advice ) ) );
		}
		$items = [];
		foreach ( (array) ( $r['positions'] ?? [] ) as $p ) {
			if ( ! is_array( $p ) || '' === trim( (string) ( $p['reading'] ?? '' ) ) ) {
				continue;
			}
			$card = (array) ( $p['card'] ?? [] );
			$name = (string) ( $card['nameLocalized'] ?? $card['name'] ?? '' );
			if ( '' !== $name && ! empty( $p['reversed'] ) ) {
				/* translators: %s = tarot card name */
				$name = sprintf( __( '%s (reversed)', 'astroway' ), $name );
			}
			$items[] = [
				'title'     => (string) ( $p['positionLocalized'] ?? $p['position'] ?? '' ),
				'meta_html' => esc_html( $name ),
				'body_html' => UI::prose( '<p>' . esc_html( trim( (string) $p['reading'] ) ) . '</p>' ),
				'open'      => empty( $items ),
			];
		}
		if ( ! empty( $items ) ) {
			$html .= UI::section( __( 'Card by card', 'astroway' ), UI::accordion( $items ) );
		}
		if ( '' === $html ) {
			return '';
		}
		return $html . UI::note( (string) ( $r['disclaimer'] ?? '' ) );
	}

	private static function answer( string $html ): array {
		return [
			'ok'   => true,
			'html' => $html,
			'code' => '',
			'note' => '',
		];
	}

	/** Visitors get no reason; an administrator is told what to do about it. */
	private static function refuse( string $code ): array {
		$notes = [
			'nokey'    => __( 'The reading needs an API key: the site key does not cover readings. Paste yours in AstroWay, API Key. Only administrators see this note.', 'astroway' ),
			'cap'      => __( 'Today\'s number of new tarot readings is reached; readings already given are still shown. Only administrators see this note.', 'astroway' ),
			'failed'   => __( 'The api did not return the reading just now. Only administrators see this note.', 'astroway' ),
			'mismatch' => __( 'The api answered with a reading of other cards, so it was not shown; it is asked again in an hour. Only administrators see this note.', 'astroway' ),
		];
		$admin = function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
		return [
			'ok'   => false,
			'html' => '',
			'code' => $code,
			'note' => $admin ? (string) ( $notes[ $code ] ?? '' ) : '',
		];
	}
}
