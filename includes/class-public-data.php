<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side reader for the anonymous JSON endpoints under /v1/public/*.
 *
 * Companion to PublicClient, which builds iframe URLs for the browser to load.
 * This one fetches the data here, on the server, so the widget's text ends up in
 * the page DOM: indexable, styleable by the theme, readable without JavaScript.
 *
 * Two things make that affordable. Every request declares the site through
 * X-AstroWay-Site-URL, which puts it in the api's per-site quota (300 an hour)
 * instead of the per-IP one (30), because a server fetching for every visitor is
 * not a visitor. And answers are cached until they actually change rather than
 * for a fixed hour: a daily horoscope is true until midnight where the site
 * lives, a weekly one until Monday, so a site showing all twelve signs spends
 * twelve calls a day. "Today" is the site's today: the date goes to the api
 * explicitly, because the api's own default is the UTC day, a day ahead of a
 * site in Chicago every evening after seven.
 *
 * Failures are cached too, for a minute. Without that, an exhausted quota or a
 * timing-out api turns every page render into another request, which keeps the
 * quota exhausted instead of letting it recover.
 */
class PublicData {

	/** How long a failure is remembered. Short enough to recover, long enough to stop a stampede. */
	public const NEGATIVE_TTL = MINUTE_IN_SECONDS;

	private const TIMEOUT = 5;

	/** Never cache for less than this, so a rollover at 23:59:50 cannot cause a burst. */
	private const MIN_TTL = MINUTE_IN_SECONDS;

	private const SIGNS = [
		'aries',
		'taurus',
		'gemini',
		'cancer',
		'leo',
		'virgo',
		'libra',
		'scorpio',
		'sagittarius',
		'capricorn',
		'aquarius',
		'pisces',
	];

	/**
	 * Endpoints we read server-side, with how long each answer stays true.
	 * `required` names params without a sensible default: absent one, we render
	 * nothing rather than guess (there is no reasonable stand-in for a sign).
	 */
	private const ENDPOINTS = [
		'daily_horoscope'    => [
			'path'      => '/public/horoscope/daily',
			'freshness' => 'day',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		'weekly_horoscope'   => [
			'path'      => '/public/horoscope/weekly',
			'freshness' => 'week',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		'monthly_horoscope'  => [
			'path'      => '/public/horoscope/monthly',
			'freshness' => 'month',
			'params'    => [ 'sign', 'date' ],
			'required'  => [ 'sign' ],
		],
		// With `lang` since api v2.205.0: the card's name, keywords and reading
		// come back translated in `localized`. Before that the route ignored it.
		'tarot_daily'        => [
			'path'      => '/public/tarot/daily',
			'freshness' => 'day',
			'params'    => [ 'date', 'art' ],
			'required'  => [],
		],
		// A spread of the day: the seed is the site and the date, so each site
		// draws its own spread and keeps it until the site's midnight.
		'tarot_three_card'   => [
			'path'      => '/public/tarot/three-card',
			'freshness' => 'day',
			'params'    => [ 'seed', 'art' ],
			'required'  => [],
		],
		'tarot_celtic_cross' => [
			'path'      => '/public/tarot/celtic-cross',
			'freshness' => 'day',
			'params'    => [ 'seed', 'art' ],
			'required'  => [],
		],
		'moon_phase'         => [
			'path'      => '/public/moon-phase',
			'freshness' => 'day',
			'params'    => [ 'date' ],
			'required'  => [],
		],
		'planet_of_day'      => [
			'path'      => '/public/planet-of-day',
			'freshness' => 'day',
			'params'    => [ 'date' ],
			'required'  => [],
		],
		// The only POST among them, and the only one whose answer never changes:
		// a chart for a given birth moment is the same forever, so it is cached
		// for a month rather than to a calendar boundary. No `lang` either, the
		// payload is numbers.
		'natal'              => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
		// The mini chart, the moon and rising signs read the same chart as the
		// natal card and are cached under the same key: one call between them.
		'mini_chart'         => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
		'moon_sign'          => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
		'rising_sign'        => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			// A rising sign is the horizon at a place and a minute; without
			// coordinates it would be the horizon of the Atlantic at Greenwich.
			'required'  => [ 'date', 'time', 'latitude', 'longitude' ],
			'lang'      => false,
		],
		// Two charts at once, and the only route that costs three units of the
		// hourly bucket rather than one, because it computes both charts and the
		// matrix between them. Cached as `static` like the single chart: two
		// birth moments do not stop aspecting each other.
		'synastry'           => [
			'path'      => '/public/synastry',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'charts' ],
			// A date each. Time may be unknown on either side and coordinates
			// are not read at all, see chart_of_pair().
			'required'  => [ 'date_a', 'date_b' ],
			'lang'      => false,
		],
		// How each pair of signs stands in the zodiac: a relation and a tone, no
		// score (api contract docs/WP-COMPAT-CONTRACT.md). Static, one unit.
		'zodiac_matrix'      => [
			'path'      => '/public/compatibility/matrix',
			'freshness' => 'static',
			'params'    => [],
			'required'  => [],
			'lang'      => false,
		],
		// Human Design's `localized` covers type, strategy, authority, geometry
		// and the centres but not the definition or the not-self theme, so all
		// of its closed sets are translated here, in one voice.
		'bodygraph'          => [
			'path'      => '/public/human-design',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
		// Returns and the Davison chart are keyed routes, not /public: a site key
		// answers them inside the hourly bucket, an owner key pays 50 credits a
		// call. `paid` keeps a visitor's form on the site key only. Houses are
		// the point of all three, so the place is required, never 0,0.
		'solar_return'       => [
			'path'      => '/solar-return',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'chart', 'return' ],
			'required'  => [ 'date', 'time', 'latitude', 'longitude' ],
			'lang'      => false,
			'paid'      => true,
		],
		// Searched from the 1st of the month by default, so a page spends one
		// call a month, not one a day.
		'lunar_return'       => [
			'path'      => '/lunar-return',
			'method'    => 'POST',
			'freshness' => 'month',
			'params'    => [ 'chart', 'return' ],
			'required'  => [ 'date', 'time', 'latitude', 'longitude' ],
			'lang'      => false,
			'paid'      => true,
		],
		'davison'            => [
			'path'      => '/davison',
			'method'    => 'POST',
			'freshness' => 'static',
			'params'    => [ 'charts' ],
			'required'  => [ 'date_a', 'date_b' ],
			'lang'      => false,
			'paid'      => true,
			// The midpoint in time and space needs both minutes and both places.
			'strict'    => true,
		],
	];

	/**
	 * Reads that are not widgets of their own. The sky of one day over a chart
	 * goes to the birth chart's route but is kept to the next midnight, not a
	 * month, so a card on "today" does not pile up a month of skies.
	 */
	private const READS = [
		// Texts on the twelve animals and five elements for the Chinese sign
		// card, which reads the sign itself on the keyed route.
		'chinese_texts'       => [
			'path'      => '/public/chinese/zodiac/texts',
			'freshness' => 'static',
			'params'    => [],
			'required'  => [],
		],
		// What the picture of a card shows and the card in five spheres of life,
		// per language (api 2.228.1). Asked for the cards on the page only; Tarot
		// keeps each card on its own.
		'tarot_texts'         => [
			'path'      => '/public/tarot/texts',
			'freshness' => 'week',
			'params'    => [ 'cards' ],
			'required'  => [ 'cards' ],
		],
		// Texts on types, authorities, profiles, centres, channels and gates.
		'hd_texts'            => [
			'path'      => '/public/human-design/texts',
			'freshness' => 'week',
			'params'    => [],
			'required'  => [],
		],
		// The api's own spread of the day, read once a day per language, no key
		// (api 2.228.1): the reading for a site that has no owner's key.
		'tarot_daily_reading' => [
			'path'      => '/public/tarot/daily-reading',
			'freshness' => 'day',
			'params'    => [ 'date', 'art' ],
			'required'  => [],
		],
		// The picture sets, for the editors' "Card pictures" field (api 2.228.1).
		'tarot_decks'         => [
			'path'      => '/public/tarot/decks',
			'freshness' => 'day',
			'params'    => [],
			'required'  => [],
			'lang'      => false,
		],
		'sky'                 => [
			'path'      => '/public/chart',
			'method'    => 'POST',
			'freshness' => 'day',
			'params'    => [ 'chart' ],
			'required'  => [ 'date', 'time' ],
			'lang'      => false,
		],
	];

	/** Widget keys this client can read. */
	public static function widgets(): array {
		return array_keys( self::ENDPOINTS );
	}

	public static function supports( string $widget ): bool {
		return isset( self::ENDPOINTS[ $widget ] );
	}

	/** A keyed route that costs an owner key credits: returns and the Davison chart. */
	public static function is_paid( string $widget ): bool {
		return ! empty( self::ENDPOINTS[ $widget ]['paid'] );
	}

	/**
	 * Payload for a widget, or null when it cannot be had: unknown widget, a
	 * required param missing, a remembered failure, or the request failing now.
	 * Callers must handle null by falling back, never by rendering an empty box.
	 */
	/**
	 * @param bool $remember False for a visitor's own data: asked fresh, and
	 *                       neither the answer nor a failure is kept.
	 */
	public static function get( string $widget, array $params = [], bool $remember = true ): ?array {
		$config = self::ENDPOINTS[ $widget ] ?? self::READS[ $widget ] ?? null;
		if ( null === $config ) {
			return null;
		}

		$query = self::query_for( $widget, $params );
		if ( null === $query ) {
			return null;
		}

		if ( ! $remember ) {
			// A visitor's own calculation on a paid route goes on the site key only.
			return self::request( $config['path'], $query, $config['method'] ?? 'GET', ! empty( $config['paid'] ) );
		}

		$key    = self::cache_key( $config['path'], $query );
		$cached = Cache::get( $key );
		if ( is_array( $cached ) ) {
			self::cap_page( $config, $params );
			return $cached;
		}

		// A remembered failure short-circuits before the network, which is the
		// whole point: the api said no a moment ago and asking again on every
		// render is what keeps it saying no.
		if ( false !== Cache::get( $key . '_neg' ) ) {
			return null;
		}

		$payload = self::request( $config['path'], $query, $config['method'] ?? 'GET' );
		if ( null === $payload ) {
			Cache::set( $key . '_neg', 'fail', self::NEGATIVE_TTL );
			return null;
		}

		Cache::set( $key, $payload, self::ttl_for( $config['freshness'] ) );
		self::cap_page( $config, $params );
		return $payload;
	}

	/** What is kept for a read, without asking the api: for code a page runs on every render. */
	public static function cached( string $widget, array $params = [] ): ?array {
		$config = self::ENDPOINTS[ $widget ] ?? self::READS[ $widget ] ?? null;
		$query  = null === $config ? null : self::query_for( $widget, $params );
		if ( null === $query ) {
			return null;
		}
		$cached = Cache::get( self::cache_key( $config['path'], $query ) );
		return is_array( $cached ) ? $cached : null;
	}

	/** The seed of a spread of the day: this site, the site's day, this spread. */
	public static function seed( string $path, int $now ): int {
		return (int) ( crc32( home_url() . '|' . self::site_day( 'day', $now ) . '|' . $path ) & 0x7fffffff );
	}

	/**
	 * The first day of the current period where the site lives, YYYY-MM-DD:
	 * today, this week's Monday, or the 1st of the month.
	 */
	public static function site_day( string $freshness = 'day', ?int $now = null ): string {
		return self::period_start( $freshness, null === $now ? time() : $now )->format( 'Y-m-d' );
	}

	/**
	 * Seconds until the answer changes, at the site's midnight. Weekly answers
	 * are anchored to Monday and monthly ones to the 1st, so the boundary is
	 * not simply midnight. Stepped on the calendar rather than in fixed
	 * seconds, so a daylight saving night lasts 23 or 25 hours, as on the site.
	 */
	public static function ttl_for( string $freshness, ?int $now = null ): int {
		$now = null === $now ? time() : $now;

		switch ( $freshness ) {
			case 'week':
				$boundary = self::period_start( 'week', $now )->modify( '+7 days' )->getTimestamp();
				break;
			case 'month':
				$boundary = self::period_start( 'month', $now )->modify( '+1 month' )->getTimestamp();
				break;
			case 'static':
				// Nothing about a past moment changes. A month is arbitrary but
				// keeps the transient table from holding entries forever.
				$boundary = $now + 30 * DAY_IN_SECONDS;
				break;
			default:
				$boundary = self::period_start( 'day', $now )->modify( '+1 day' )->getTimestamp();
				break;
		}

		return max( self::MIN_TTL, $boundary - $now );
	}

	/** Midnight that opens the period, on the site's clock. */
	private static function period_start( string $freshness, int $now ): \DateTimeImmutable {
		$zone  = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		$start = ( new \DateTimeImmutable( '@' . $now ) )->setTimezone( $zone )->setTime( 0, 0 );
		if ( 'week' === $freshness ) {
			return $start->modify( '-' . ( (int) $start->format( 'N' ) - 1 ) . ' days' );
		}
		if ( 'month' === $freshness ) {
			return $start->modify( 'first day of this month' );
		}
		return $start;
	}

	/**
	 * A card of today tells the page cache when it stops being true. One with a
	 * date of its own is left alone: it is true for as long as the page is.
	 */
	private static function cap_page( array $config, array $params ): void {
		if ( 'static' === $config['freshness'] || '' !== self::sanitize_date( (string) ( $params['date'] ?? '' ) ) ) {
			return;
		}
		PageCache::cap( self::ttl_for( $config['freshness'] ) );
	}

	/**
	 * Cache key over the endpoint and the exact query, so two signs or two
	 * languages never share an entry.
	 *
	 * Keyed on the path rather than the widget on purpose: the natal, moon sign
	 * and rising sign cards all read POST /public/chart, and a page carrying all
	 * three should spend one call, not three.
	 */
	public static function cache_key( string $path, array $query ): string {
		ksort( $query );
		$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '_', strtolower( $path ) ), '_' );
		return 'pub_' . $slug . '_' . md5( (string) wp_json_encode( $query ) );
	}

	/**
	 * Query for a widget, or null when a required param is unusable.
	 * A malformed date is dropped rather than refused: the api then answers for
	 * today, which beats blanking the widget over a typo in one attribute.
	 */
	private static function query_for( string $widget, array $params ): ?array {
		$config = self::ENDPOINTS[ $widget ] ?? self::READS[ $widget ];
		$query  = [];

		if ( in_array( 'chart', $config['params'], true ) ) {
			$payload = self::chart_payload( $params, $config['required'] );
			if ( null !== $payload && in_array( 'return', $config['params'], true ) ) {
				$payload = self::with_return( $widget, $payload, $params );
			}
			return $payload;
		}

		if ( in_array( 'charts', $config['params'], true ) ) {
			return self::chart_pair_payload( $params, $config['required'], ! empty( $config['strict'] ) );
		}

		foreach ( $config['params'] as $name ) {
			$raw = isset( $params[ $name ] ) ? (string) $params[ $name ] : '';
			switch ( $name ) {
				case 'sign':
					$sign = strtolower( trim( $raw ) );
					if ( in_array( $sign, self::SIGNS, true ) ) {
						$query['sign'] = $sign;
					}
					break;
				case 'date':
					$date = self::sanitize_date( $raw );
					if ( '' === $date && 'static' !== $config['freshness'] ) {
						// The period as the site counts it, not the api's UTC default.
						$date = self::site_day( $config['freshness'] );
					}
					if ( '' !== $date ) {
						$query['date'] = $date;
					}
					break;
				case 'art':
					// The picture set; none asked for means the drawn face and no set sent.
					$art = Tarot::sanitize_art( $raw );
					if ( '' !== $art ) {
						$query['art'] = $art;
					}
					break;
				case 'cards':
					// One unknown slug fails the whole request, so only slug-shaped ones go.
					$cards = array_filter( explode( ',', $raw ), static fn( $c ) => (bool) preg_match( '/^[a-z0-9]+(-[a-z0-9]+){0,4}$/', $c ) );
					if ( $cards ) {
						$query['cards'] = implode( ',', array_unique( $cards ) );
					}
					break;
				case 'seed':
					// A visitor's fresh draw brings its own seed; otherwise the spread of the day.
					$query['seed'] = isset( $params['seed'] ) && is_int( $params['seed'] )
						? max( 0, min( 0x7fffffff, $params['seed'] ) )
						: self::seed( $config['path'], time() );
					break;
			}
		}

		foreach ( $config['required'] as $name ) {
			if ( ! isset( $query[ $name ] ) ) {
				return null;
			}
		}

		// Always explicit: without `lang` the api answers in Ukrainian.
		if ( false !== ( $config['lang'] ?? true ) ) {
			$query['lang'] = Plugin::resolve_lang( $params['lang'] ?? '' );
		}

		return $query;
	}

	/**
	 * Body for POST /public/chart.
	 *
	 * The field names are not the ones the embed routes use, and getting them
	 * wrong is silent: the schema passes unknown keys through and defaults
	 * latitude and longitude to 0, so sending `lat`/`lng`/`tz` returns a chart
	 * for the Gulf of Guinea under a 200. Read off the schema, not by analogy.
	 */
	private static function chart_payload( array $params, array $required ): ?array {
		$date = self::sanitize_date( (string) ( $params['date'] ?? '' ) );
		$time = trim( (string) ( $params['time'] ?? '' ) );
		if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			$time .= ':00'; // the schema wants HH:mm:ss and rejects HH:mm
		}
		if ( ! preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
			$time = '';
		}

		// The raw attributes, not the cast ones: a missing coordinate arrives as
		// an empty string and a real one may legitimately be "0", and (float)
		// collapses both to the same number.
		$have = [
			'date'      => $date,
			'time'      => $time,
			'latitude'  => trim( (string) ( $params['lat'] ?? '' ) ),
			'longitude' => trim( (string) ( $params['lng'] ?? '' ) ),
		];
		foreach ( $required as $name ) {
			if ( '' === ( $have[ $name ] ?? '' ) ) {
				return null;
			}
		}

		$payload = [
			'date'      => $date,
			'time'      => $time,
			'latitude'  => (float) ( $params['lat'] ?? 0 ),
			'longitude' => (float) ( $params['lng'] ?? 0 ),
		];

		// No zone given but a place is: the api looks the zone up from the
		// coordinates and applies the offset its clocks kept on that date,
		// summer time included. Before, the time went out as UTC under a 200.
		// An explicit tz, including "0", is sent as it was.
		if ( '' === trim( (string) ( $params['tz'] ?? '' ) ) && '' !== $have['latitude'] && '' !== $have['longitude'] ) {
			$payload['timezone'] = 'auto';
		} else {
			$payload['timezoneOffset'] = (float) ( $params['tz'] ?? 0 );
		}

		return $payload;
	}

	/**
	 * The return's own fields on top of the birth chart: the year of a Solar
	 * Return, the date a Lunar Return is searched from, and the place it is
	 * cast for when that is not the place of birth.
	 */
	private static function with_return( string $widget, array $payload, array $params ): ?array {
		if ( 'solar_return' === $widget ) {
			$year = (int) ( $params['year'] ?? 0 );
			if ( $year < 1900 || $year > 2100 ) {
				return null;
			}
			$payload['year'] = $year;
		} else {
			$after = self::sanitize_date( (string) ( $params['after'] ?? '' ) );
			if ( '' === $after ) {
				return null;
			}
			$payload['afterDate'] = $after;
		}
		$lat = trim( (string) ( $params['return_lat'] ?? '' ) );
		$lng = trim( (string) ( $params['return_lng'] ?? '' ) );
		if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
			$payload['locationLat'] = (float) $lat;
			$payload['locationLng'] = (float) $lng;
		}
		return $payload;
	}

	/**
	 * Body for POST /public/synastry: one chart per partner, flat `_a`/`_b`
	 * attributes on the shortcode folded into the two objects the route wants.
	 *
	 * Null when either date is missing, because a synastry of one person is not
	 * a reduced answer, it is a different question.
	 */
	private static function chart_pair_payload( array $params, array $required, bool $strict = false ): ?array {
		foreach ( $required as $name ) {
			if ( '' === self::sanitize_date( (string) ( $params[ $name ] ?? '' ) ) ) {
				return null;
			}
		}
		// Davison: no unknown time (the route's schema has no such flag and
		// answers 400) and no default place (the midpoint would drift to 0,0).
		if ( $strict ) {
			foreach ( [ 'a', 'b' ] as $side ) {
				$time = trim( (string) ( $params[ 'time_' . $side ] ?? '' ) );
				$lat  = trim( (string) ( $params[ 'lat_' . $side ] ?? '' ) );
				$lng  = trim( (string) ( $params[ 'lng_' . $side ] ?? '' ) );
				if ( ! preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $time ) || ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
					return null;
				}
			}
		}

		return [
			'chart1' => self::chart_of_pair( $params, 'a' ),
			'chart2' => self::chart_of_pair( $params, 'b' ),
		];
	}

	/**
	 * One partner's half of the pair.
	 *
	 * An unknown birth time is declared rather than guessed. The route supports
	 * it, and cross-aspects survive it because they run planet to planet: what
	 * an unknown time costs is the houses and the angles, and this answer
	 * carries neither. Defaulting to noon instead would move the Moon by up to
	 * six degrees and quietly invent an aspect.
	 *
	 * Coordinates default to zero and that is safe here, unlike the natal card
	 * where the same default answers for the Gulf of Guinea. Checked against the
	 * live route on 2026-08-23: the same two birth moments at Kyiv and Paris and
	 * at 0,0 return byte-identical aspects, the same score and the same count,
	 * because the answer holds no houses and planetary longitudes are geocentric.
	 * Whatever the author did give is still sent, so the day the route grows a
	 * house overlay it will get the places it needs.
	 */
	private static function chart_of_pair( array $params, string $side ): array {
		$date = self::sanitize_date( (string) ( $params[ 'date_' . $side ] ?? '' ) );
		$time = trim( (string) ( $params[ 'time_' . $side ] ?? '' ) );
		if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			$time .= ':00';
		}

		$chart = [
			'date'      => $date,
			'latitude'  => (float) ( $params[ 'lat_' . $side ] ?? 0 ),
			'longitude' => (float) ( $params[ 'lng_' . $side ] ?? 0 ),
		];
		// The same rule as a single chart: a place with no zone is local time
		// there, not UTC. The Moon moves half a degree an hour, enough to move
		// a tight aspect in or out of orb.
		$place = '' !== trim( (string) ( $params[ 'lat_' . $side ] ?? '' ) ) && '' !== trim( (string) ( $params[ 'lng_' . $side ] ?? '' ) );
		if ( '' === trim( (string) ( $params[ 'tz_' . $side ] ?? '' ) ) && $place ) {
			$chart['timezone'] = 'auto';
		} else {
			$chart['timezoneOffset'] = (float) ( $params[ 'tz_' . $side ] ?? 0 );
		}

		if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
			$chart['time'] = $time;
		} else {
			$chart['timeUnknown'] = true;
		}

		return $chart;
	}

	/** YYYY-MM-DD that is also a real calendar date, or '' when it is neither. */
	private static function sanitize_date( string $raw ): string {
		$raw = trim( $raw );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $raw : '';
	}

	/** Days either side of today a dated horoscope may ask for on the site key. */
	private const SITE_KEY_DAYS = [
		'/public/horoscope/daily'   => 2,
		'/public/horoscope/weekly'  => 8,
		'/public/horoscope/monthly' => 35,
	];

	/**
	 * Whether the site key may carry this call. The api refuses it a horoscope
	 * dated far from today (400 DATE_OUT_OF_RANGE); the date arrows reach any
	 * day, and without the key those still go on the anonymous allowance.
	 */
	private static function near_today( string $path, array $payload, ?int $now = null ): bool {
		if ( ! isset( self::SITE_KEY_DAYS[ $path ], $payload['date'] ) ) {
			return true;
		}
		$day = strtotime( (string) $payload['date'] . ' 00:00:00 UTC' );
		if ( false === $day ) {
			return true;
		}
		$today = strtotime( gmdate( 'Y-m-d', null === $now ? time() : $now ) . ' 00:00:00 UTC' );
		return abs( $day - $today ) <= self::SITE_KEY_DAYS[ $path ] * DAY_IN_SECONDS;
	}

	private static function request( string $path, array $payload, string $method = 'GET', bool $site_only = false ): ?array {
		$base = rtrim( ASTROWAY_API_BASE, '/' ) . $path;
		$args = [
			'timeout' => self::TIMEOUT,
			'headers' => [
				'Accept'              => 'application/json',
				// Declares which site this call renders for. The api reads it
				// and counts the anonymous quota per site instead of per IP,
				// which is the only reason server-side rendering fits inside
				// the free tier at all.
				'X-AstroWay-Site-URL' => home_url(),
			],
		];

		// Since api 2.124.0 these routes read a key when one is offered, and the
		// call is then counted against the plan instead of the 300-an-hour bucket
		// the whole site shares. Offered on every call rather than gated on the
		// tier: asking /v1/auth/keys/me first would cost a request to save one,
		// and an unknown or free-tier key falls back to the anonymous bucket
		// rather than being refused, so there is nothing to lose by trying.
		//
		// Server-side only. PublicClient::embed_url() must never carry it: that
		// URL is an iframe src, so it lands in the page's markup and in the
		// visitor's browser history.
		$key      = $site_only ? '' : ( new ApiClient() )->key();
		$site_key = '' === $key && self::near_today( $path, $payload ) ? SiteKey::key() : '';
		if ( '' !== $key || '' !== $site_key ) {
			$args['headers']['X-Api-Key'] = '' !== $key ? $key : $site_key;
		}

		if ( 'POST' === $method ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $payload );
			$response                        = wp_remote_post( $base, $args );
		} else {
			$response = wp_remote_get( add_query_arg( $payload, $base ), $args );
		}

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		self::record_quota( $response, $status );

		if ( 200 !== $status ) {
			if ( '' !== $site_key ) {
				$error = json_decode( (string) wp_remote_retrieve_body( $response ), true );
				SiteKey::note( $status, (string) ( $error['error']['code'] ?? '' ), $site_key );
			}
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['ok'] ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return null;
		}

		return $body['data'];
	}

	/**
	 * Edited natal texts for these keys in this language, `key => [title, body]`.
	 *
	 * The texts are static, so each one is kept for a week once had. A key the
	 * api has no text for yet is asked again after a day, not on every render:
	 * houses and aspects arrive as they are edited. At most 64 keys a call.
	 */
	public static function natal_texts( array $keys, string $lang, ?int $now = null ): array {
		$now   = null === $now ? time() : $now;
		$keys  = array_values( array_unique( array_filter( array_map( 'strval', $keys ), static fn( $k ) => (bool) preg_match( '/^[a-z]+(_[a-z]+)?\.[a-z0-9]+(_[a-z]+)?$/', $k ) ) ) );
		$store = Cache::get( 'natal_texts_' . $lang );
		$store = is_array( $store ) ? $store : [];
		$texts = isset( $store['texts'] ) && is_array( $store['texts'] ) ? $store['texts'] : [];
		$gone  = isset( $store['missing'] ) && is_array( $store['missing'] ) ? $store['missing'] : [];

		$need = array_values(
			array_filter(
				$keys,
				static fn( $k ) => ! isset( $texts[ $k ] ) && (int) ( $gone[ $k ] ?? 0 ) <= $now - DAY_IN_SECONDS
			)
		);
		if ( $need && false === Cache::get( 'natal_texts_' . $lang . '_neg' ) ) {
			foreach ( array_chunk( $need, 64 ) as $chunk ) {
				$data = self::request(
					'/natal-texts',
					[
						'keys' => implode( ',', $chunk ),
						'lang' => $lang,
					]
				);
				if ( null === $data ) {
					Cache::set( 'natal_texts_' . $lang . '_neg', 'fail', self::NEGATIVE_TTL );
					break;
				}
				foreach ( (array) ( $data['texts'] ?? [] ) as $k => $t ) {
					if ( is_array( $t ) && '' !== (string) ( $t['body'] ?? '' ) ) {
						$texts[ (string) $k ] = [
							'title' => (string) ( $t['title'] ?? '' ),
							'body'  => (string) $t['body'],
						];
						unset( $gone[ (string) $k ] );
					}
				}
				foreach ( (array) ( $data['missing'] ?? [] ) as $k ) {
					$gone[ (string) $k ] = $now;
				}
			}
			Cache::set(
				'natal_texts_' . $lang,
				[
					'texts'   => $texts,
					'missing' => $gone,
				],
				WEEK_IN_SECONDS
			);
		}

		return array_intersect_key( $texts, array_flip( $keys ) );
	}

	/** Option holding the last quota reading taken from a real render. */
	public const QUOTA_OPTION = 'astroway_quota_seen';

	/**
	 * Remember what the api said about the quota on this call.
	 *
	 * Every render already carries the answer in its headers, so the admin
	 * notice does not need a request of its own. The old probe fired up to
	 * twelve an hour and, worse, measured a bucket nothing renders against:
	 * it sent no site header, so it read the 30-an-hour per-IP tier while the
	 * widgets were being served out of the 300-an-hour per-site one.
	 */
	private static function record_quota( $response, int $status ): void {
		$limit     = wp_remote_retrieve_header( $response, 'x-ratelimit-limit' );
		$remaining = wp_remote_retrieve_header( $response, 'x-ratelimit-remaining' );
		if ( '' === $limit && '' === $remaining && 429 !== $status ) {
			return;
		}

		update_option(
			self::QUOTA_OPTION,
			[
				'limit'     => '' === $limit ? null : (int) $limit,
				'remaining' => '' === $remaining ? null : (int) $remaining,
				'scope'     => (string) wp_remote_retrieve_header( $response, 'x-ratelimit-scope' ),
				'status'    => $status,
				'at'        => time(),
			],
			false
		);
	}

	/** Last quota reading, or null when nothing has been rendered recently. */
	public static function quota_seen( int $max_age = 900 ): ?array {
		$seen = get_option( self::QUOTA_OPTION, null );
		if ( ! is_array( $seen ) || empty( $seen['at'] ) ) {
			return null;
		}
		return ( time() - (int) $seen['at'] ) > $max_age ? null : $seen;
	}
}
