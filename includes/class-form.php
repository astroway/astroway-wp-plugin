<?php
/**
 * Birth-data form for visitors: a natal chart, moon sign or rising sign
 * shortcode with no date asks the visitor for one, a synastry shortcode with
 * no dates for two.
 *
 * Posts back to its own page and the server draws the result, so it works
 * without JavaScript. The plugin keeps nothing of what a visitor types: the
 * answer is fetched uncached and the page is sent no-store.
 *
 * @package AstroWay\WPPlugin
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form {

	public const MARKER    = 'astroway_form';
	private const TRAP     = 'astroway_website';
	private const HITS     = 'astroway_form_hits';
	private const FAILS    = 'astroway_form_fails';
	private const WINDOW   = 10 * MINUTE_IN_SECONDS;
	private const PER_IP   = 8;
	private const PER_HOUR = 120;
	private const NAME_MAX = 60;

	/** The Moon's greatest drift from its noon longitude within a day, per the api. */
	private const MOON_DRIFT = 7.7;

	/** Cloudflare's edge ranges: only from these is CF-Connecting-IP believed. */
	private const CLOUDFLARE = [
		'173.245.48.0/20',
		'103.21.244.0/22',
		'103.22.200.0/22',
		'103.31.4.0/22',
		'141.101.64.0/18',
		'108.162.192.0/18',
		'190.93.240.0/20',
		'188.114.96.0/20',
		'197.234.240.0/22',
		'198.41.128.0/17',
		'162.158.0.0/15',
		'104.16.0.0/13',
		'104.24.0.0/14',
		'172.64.0.0/13',
		'131.0.72.0/22',
		'2400:cb00::/32',
		'2606:4700::/32',
		'2803:f800::/32',
		'2405:b500::/32',
		'2405:8100::/32',
		'2a06:98c0::/29',
		'2c0f:f248::/32',
	];

	/** @var array<string,int> Forms of each kind printed so far on this page. */
	private static array $seq = [];

	public static function register(): void {
		add_action( 'template_redirect', [ __CLASS__, 'headers' ], 1 );
		add_action( 'admin_init', [ __CLASS__, 'privacy' ] );
	}

	/**
	 * A page answering one of these forms carries a visitor's birth data: no
	 * cache may keep it, the back button on a shared computer must not show
	 * it, and no search engine should index it.
	 */
	public static function headers(): void {
		if ( '' === self::posted( self::MARKER ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- the name page caches read.
		}
		nocache_headers();
		header( 'Cache-Control: no-store, private' );
		add_filter(
			'wp_robots',
			static function ( array $robots ): array {
				$robots['noindex']  = true;
				$robots['nofollow'] = true;
				return $robots;
			}
		);
	}

	public static function privacy(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content(
			'AstroWay',
			'<p>' . esc_html__( 'When a visitor fills in a birth chart, compatibility, moon sign or rising sign form, the date, time and place they enter are sent to api.astroway.info to calculate the answer, and the city name to app.astroway.info to find its coordinates. Names typed into the compatibility form are not sent anywhere. The AstroWay plugin does not store any of it on this site: the answer is not cached and the page is sent with no-store. Security or activity-log plugins on this site may record form submissions on their own.', 'astroway' ) . '</p>'
		);
	}

	/** The form, or its answer when this very form was just sent. */
	public static function render( string $widget, array $params ): string {
		self::$seq[ $widget ] = ( self::$seq[ $widget ] ?? 0 ) + 1;
		$id                   = $widget . '-' . self::$seq[ $widget ];
		$lang                 = (string) ( $params['lang'] ?? '' );

		if ( self::posted( self::MARKER ) !== $id || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return self::card( $widget, $id, $lang, [] );
		}
		return self::answer( $widget, $id, $lang, $params );
	}

	/** What was posted for one person; $sfx is `_a` or `_b` on the form for two. */
	private static function inputs( string $sfx ): array {
		return [
			'name'    => mb_substr( self::posted( 'aw_name' . $sfx ), 0, self::NAME_MAX ),
			'date'    => self::posted( 'aw_date' . $sfx ),
			'time'    => self::posted( 'aw_time' . $sfx ),
			'unknown' => '1' === self::posted( 'aw_time_unknown' . $sfx ),
			'city'    => self::posted( 'aw_city' . $sfx ),
			'place'   => self::posted( 'aw_place' . $sfx ),
			'for'     => self::posted( 'aw_place_for' . $sfx ),
			'lat'     => self::posted( 'aw_lat' . $sfx ),
			'lng'     => self::posted( 'aw_lng' . $sfx ),
			'tz'      => self::posted( 'aw_tz' . $sfx ),
		];
	}

	/** The people a form asks about, by field suffix. */
	private static function sides( string $widget ): array {
		return 'synastry' === $widget ? [ '_a', '_b' ] : [ '' ];
	}

	/** @param array $given The shortcode's own attributes, for what the visitor is not asked. */
	private static function answer( string $widget, string $id, string $lang, array $given ): string {
		$in = [];
		foreach ( self::sides( $widget ) as $sfx ) {
			$in[ $sfx ] = self::inputs( $sfx );
		}

		if ( ! self::same_origin() ) {
			return self::card( $widget, $id, $lang, $in, [ '' => __( 'This form works only on the site it is on.', 'astroway' ) ] );
		}
		// Filled in only by a robot that fills in every field.
		if ( '' !== self::posted( self::TRAP ) ) {
			return self::card( $widget, $id, $lang, $in );
		}

		$errors = [];
		foreach ( $in as $sfx => $one ) {
			$errors += self::validate( $widget, $one, $sfx );
		}
		if ( $errors ) {
			return self::card( $widget, $id, $lang, $in, $errors );
		}
		if ( ! self::allow( time() ) ) {
			return self::card( $widget, $id, $lang, $in, [ '' => __( 'Too many calculations from here in a short time. Try again in a few minutes.', 'astroway' ) ] );
		}
		if ( self::busy() ) {
			return self::card( $widget, $id, $lang, $in, [ '' => __( 'The calculator is busy right now. Try again later.', 'astroway' ) ] );
		}

		$places  = [];
		$choices = [];
		foreach ( $in as $sfx => $one ) {
			$place = self::place( $one, $lang );
			if ( isset( $place['error'] ) ) {
				$errors[ 'aw_city' . $sfx ] = $place['error'];
				$errors[ 'manual' . $sfx ]  = '1';
			} elseif ( isset( $place['choices'] ) ) {
				$choices[ $sfx ] = $place['choices'];
			} else {
				$places[ $sfx ] = $place;
			}
		}
		if ( $errors || $choices ) {
			return self::card( $widget, $id, $lang, $in, $errors, $choices );
		}

		if ( 'synastry' === $widget ) {
			$params = [
				'lang'    => $lang,
				'nocache' => true,
				'aspects' => (int) ( $given['aspects'] ?? 0 ),
			];
			$sides  = [
				'_a' => 'a',
				'_b' => 'b',
			];
			foreach ( $sides as $sfx => $side ) {
				$params[ 'date_' . $side ] = $in[ $sfx ]['date'];
				// Empty is how the pair says "unknown": the route declares it, not noon.
				$params[ 'time_' . $side ] = $in[ $sfx ]['unknown'] ? '' : $in[ $sfx ]['time'];
				$params[ 'lat_' . $side ]  = (string) $places[ $sfx ]['lat'];
				$params[ 'lng_' . $side ]  = (string) $places[ $sfx ]['lng'];
				$params[ 'tz_' . $side ]   = '';
				$params[ 'name_' . $side ] = $in[ $sfx ]['name'];
			}
		} else {
			$params = [
				'date'         => $in['']['date'],
				'time'         => $in['']['unknown'] ? '12:00' : $in['']['time'],
				'lat'          => $places['']['lat'],
				'lng'          => $places['']['lng'],
				'tz'           => '',
				'lang'         => $lang,
				'nocache'      => true,
				'time_unknown' => $in['']['unknown'],
				'transits'     => (string) ( $given['transits'] ?? '' ),
			];
		}
		// Asked directly, not through Render::widget: a frame cannot answer a
		// POST, so the "in an iframe" setting does not apply to a visitor's chart.
		$data = PublicData::get( $widget, $params, false );
		$html = null === $data ? '' : Render::with_data( $widget, $data, $params );
		if ( false === strpos( $html, 'astroway-card' ) || false !== strpos( $html, 'astroway-embed' ) ) {
			self::failed();
			return self::card( $widget, $id, $lang, $in, [ '' => __( 'The calculation did not come back. Try again in a moment.', 'astroway' ) ] );
		}

		$notes = '';
		foreach ( $in as $sfx => $one ) {
			$notes .= self::dst_note( $one['date'], $one['time'], (string) $places[ $sfx ]['tz'], $one['unknown'] );
		}
		if ( 'moon_sign' === $widget && $in['']['unknown'] ) {
			$notes .= self::moon_note( $data );
		}
		return $notes . $html . '<p class="astroway-form__again"><a href="">' . esc_html__( 'Calculate another', 'astroway' ) . '</a></p>';
	}

	/** Field errors keyed by input name; '' holds one for the whole form. */
	private static function validate( string $widget, array $in, string $sfx = '' ): array {
		$errors = [];
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $in['date'], $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			$errors[ 'aw_date' . $sfx ] = __( 'Enter the date of birth.', 'astroway' );
		} elseif ( (int) $m[1] < 1800 || $in['date'] > gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ) {
			$errors[ 'aw_date' . $sfx ] = __( 'Enter a date between 1800 and today.', 'astroway' );
		}
		if ( $in['unknown'] ) {
			if ( 'rising_sign' === $widget ) {
				$errors[ 'aw_time' . $sfx ] = __( 'The rising sign depends on the minute of birth; without the time it cannot be told.', 'astroway' );
			}
		} elseif ( ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $in['time'] ) ) {
			$errors[ 'aw_time' . $sfx ] = __( 'Enter the time of birth, or tick that it is unknown.', 'astroway' );
		}
		$manual = is_numeric( $in['lat'] ) && is_numeric( $in['lng'] );
		if ( $manual && abs( (float) $in['lat'] ) > 90 ) {
			$errors[ 'aw_lat' . $sfx ] = __( 'Latitude runs from -90 to 90.', 'astroway' );
		}
		if ( $manual && abs( (float) $in['lng'] ) > 180 ) {
			$errors[ 'aw_lng' . $sfx ] = __( 'Longitude runs from -180 to 180.', 'astroway' );
		}
		if ( ! $manual && '' === $in['place'] && mb_strlen( $in['city'] ) < 2 ) {
			$errors[ 'aw_city' . $sfx ] = __( 'Enter the city of birth.', 'astroway' );
		}
		return $errors;
	}

	/**
	 * ['lat', 'lng', 'tz'], or ['choices' => ...] when the name fits several
	 * places, or ['error' => ...]. Coordinates typed in by hand win; a place
	 * picked on the second step comes back as "lat|lng|tz".
	 */
	private static function place( array $in, string $lang ): array {
		if ( is_numeric( $in['lat'] ) && is_numeric( $in['lng'] ) ) {
			return [
				'lat' => (float) $in['lat'],
				'lng' => (float) $in['lng'],
				'tz'  => $in['tz'],
			];
		}
		// A place kept from an earlier step counts only for the city it was picked for.
		$kept = '' === (string) ( $in['for'] ?? '' ) || (string) $in['for'] === $in['city'];
		if ( $kept && preg_match( '/^(-?\d{1,2}(?:\.\d+)?)\|(-?\d{1,3}(?:\.\d+)?)\|([A-Za-z0-9_\/+-]{1,64})$/', $in['place'], $m ) ) {
			return [
				'lat' => (float) $m[1],
				'lng' => (float) $m[2],
				'tz'  => $m[3],
			];
		}

		// Not kept: the plugin promises to store nothing a visitor types.
		$found = Atlas::search( $in['city'], 6, false );
		if ( isset( $found['error'] ) ) {
			return [ 'error' => __( 'The city search is not answering. Enter the coordinates below instead.', 'astroway' ) ];
		}
		$results = (array) ( $found['results'] ?? [] );
		if ( ! $results ) {
			return [ 'error' => __( 'No place by that name. Check the spelling, or enter the coordinates below.', 'astroway' ) ];
		}
		$one = Atlas::pick( $in['city'], $results );
		if ( null === $one ) {
			$choices = [];
			foreach ( $results as $r ) {
				$choices[] = [
					'value' => round( (float) $r['latitude'], 4 ) . '|' . round( (float) $r['longitude'], 4 ) . '|' . $r['timezone'],
					'label' => Atlas::label( $r, $lang ),
				];
			}
			return [ 'choices' => $choices ];
		}
		return [
			'lat' => (float) $one['latitude'],
			'lng' => (float) $one['longitude'],
			'tz'  => (string) $one['timezone'],
		];
	}

	/**
	 * The form in a card: its fields with what was typed and what was wrong,
	 * or the list of places when the city name fitted several.
	 *
	 * @param array $in      What was typed, by person suffix.
	 * @param array $choices Places to pick from, by person suffix.
	 */
	private static function card( string $widget, string $id, string $lang, array $in, array $errors = [], array $choices = [] ): string {
		$titles = [
			'natal'       => __( 'Your birth chart', 'astroway' ),
			'moon_sign'   => __( 'Your moon sign', 'astroway' ),
			'rising_sign' => __( 'Your rising sign', 'astroway' ),
			'synastry'    => __( 'Compatibility of two charts', 'astroway' ),
		];
		$inner  = UI::header( [ 'title' => $titles[ $widget ] ?? $titles['natal'] ] );
		if ( isset( $errors[''] ) ) {
			$inner .= UI::callout( esc_html( $errors[''] ), 'tens' );
		}

		$fields = '';
		if ( 'synastry' === $widget ) {
			$legends = [
				'_a' => __( 'First person', 'astroway' ),
				'_b' => __( 'Second person', 'astroway' ),
			];
			foreach ( $legends as $sfx => $legend ) {
				$fields .= '<fieldset class="astroway-form__person"><legend class="astroway-card__subtitle">' . esc_html( $legend ) . '</legend>'
					. UI::field(
						[
							'name'  => 'aw_name' . $sfx,
							'label' => __( 'Name (optional)', 'astroway' ),
							'value' => $in[ $sfx ]['name'] ?? '',
							'hint'  => __( 'Shown on the result only.', 'astroway' ),
							'attrs' => [
								'autocomplete' => 'off',
								'maxlength'    => (string) self::NAME_MAX,
							],
						]
					)
					. self::person( $widget, $in[ $sfx ] ?? [], $errors, $choices[ $sfx ] ?? [], $sfx )
					. '</fieldset>';
			}
		} else {
			$fields = self::person( $widget, $in[''] ?? [], $errors, $choices[''] ?? [], '' );
		}

		// Off-screen, not display:none: a robot skips a field it sees is hidden.
		$fields .= '<div class="astroway-form__trap" aria-hidden="true"><label>' . esc_html__( 'Leave empty', 'astroway' ) . ' <input type="text" name="' . self::TRAP . '" value="" tabindex="-1" autocomplete="off"></label></div>';

		$inner .= UI::form( $fields, [ self::MARKER => $id ], __( 'Calculate', 'astroway' ) );
		return UI::card( 'astroway-card', 'form', $inner, [ 'lang' => $lang ] );
	}

	/** Date, time and place of one birth, with what was typed and what was wrong. */
	private static function person( string $widget, array $in, array $errors, array $choices, string $sfx ): string {
		$fields  = UI::field(
			[
				'name'  => 'aw_date' . $sfx,
				'label' => __( 'Date of birth', 'astroway' ),
				'type'  => 'date',
				'value' => $in['date'] ?? '',
				'error' => $errors[ 'aw_date' . $sfx ] ?? '',
				'attrs' => [
					'min'          => '1800-01-01',
					'max'          => gmdate( 'Y-m-d' ),
					// The browser's own birthday belongs to one person, not to both.
					'autocomplete' => '' === $sfx ? 'bday' : 'off',
				],
			]
		);
		$fields .= UI::field(
			[
				'name'  => 'aw_time' . $sfx,
				'label' => __( 'Time of birth', 'astroway' ),
				'type'  => 'time',
				'value' => $in['time'] ?? '',
				'error' => $errors[ 'aw_time' . $sfx ] ?? '',
				'hint'  => 'rising_sign' === $widget ? '' : __( 'Local time at the place of birth.', 'astroway' ),
			]
		);
		if ( 'rising_sign' !== $widget ) {
			$fields .= UI::check( 'aw_time_unknown' . $sfx, __( 'I do not know the time', 'astroway' ), ! empty( $in['unknown'] ) );
		}

		if ( $choices ) {
			$fields .= UI::choices( 'aw_place' . $sfx, __( 'Which one?', 'astroway' ), $choices, (string) ( $in['city'] ?? '' ) );
			return $fields . '<input type="hidden" name="aw_city' . $sfx . '" value="' . esc_attr( (string) ( $in['city'] ?? '' ) ) . '">';
		}
		// A place already picked survives the other person's list, tied to its
		// city: change the city and the search runs again.
		if ( '' !== (string) ( $in['place'] ?? '' ) && '' === (string) ( $in['lat'] ?? '' ) ) {
			$fields .= '<input type="hidden" name="aw_place' . $sfx . '" value="' . esc_attr( (string) $in['place'] ) . '">'
				. '<input type="hidden" name="aw_place_for' . $sfx . '" value="' . esc_attr( (string) ( $in['city'] ?? '' ) ) . '">';
		}

		$fields .= UI::field(
			[
				'name'  => 'aw_city' . $sfx,
				'label' => __( 'City of birth', 'astroway' ),
				'value' => $in['city'] ?? '',
				'error' => $errors[ 'aw_city' . $sfx ] ?? '',
				'attrs' => [ 'autocomplete' => 'off' ],
			]
		);
		// Coordinates by hand, for a place the search does not know or a
		// moment it is down. Open when they were used or asked for.
		$open = '' !== ( $in['lat'] ?? '' ) || isset( $errors[ 'aw_lat' . $sfx ] ) || isset( $errors[ 'aw_lng' . $sfx ] ) || isset( $errors[ 'manual' . $sfx ] );
		return $fields . '<details class="astroway-form__manual"' . ( $open ? ' open' : '' ) . '><summary>' . esc_html__( 'Enter coordinates instead', 'astroway' ) . '</summary>'
			. UI::field(
				[
					'name'  => 'aw_lat' . $sfx,
					'label' => __( 'Latitude', 'astroway' ),
					'value' => $in['lat'] ?? '',
					'error' => $errors[ 'aw_lat' . $sfx ] ?? '',
					'hint'  => __( 'North positive, e.g. 50.45', 'astroway' ),
					'attrs' => [ 'inputmode' => 'decimal' ],
				]
			)
			. UI::field(
				[
					'name'  => 'aw_lng' . $sfx,
					'label' => __( 'Longitude', 'astroway' ),
					'value' => $in['lng'] ?? '',
					'error' => $errors[ 'aw_lng' . $sfx ] ?? '',
					'hint'  => __( 'East positive, e.g. 30.52', 'astroway' ),
					'attrs' => [ 'inputmode' => 'decimal' ],
				]
			)
			. '</details>';
	}

	/**
	 * A cross-site form costs a visitor nothing and the site a call: accept a
	 * post only from a page of this site. Browsers send one of the two headers
	 * on every form post; a request with neither is not a browser.
	 */
	private static function same_origin(): bool {
		$site = (string) ( $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- compared to fixed strings.
		if ( '' !== $site ) {
			return in_array( $site, [ 'same-origin', 'none' ], true );
		}
		$origin = (string) ( $_SERVER['HTTP_ORIGIN'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- only its host is read.
		return '' !== $origin && strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Eight answers per visitor in ten minutes and a hundred and twenty an hour
	 * for the site, counted in one option with a ceiling, not a row per visitor.
	 */
	private static function allow( int $now ): bool {
		$hits = get_option( self::HITS, [] );
		$hits = is_array( $hits ) ? $hits : [];

		$site = (array) ( $hits['*'] ?? [ 0, $now ] );
		if ( (int) $site[1] <= $now - HOUR_IN_SECONDS ) {
			$site = [ 0, $now ];
		}
		// Keyed with the site's salt: a bare md5 of an IPv4 address is undone by trying them all.
		$who = wp_hash( self::client() );
		$one = (array) ( $hits[ $who ] ?? [ 0, $now ] );
		if ( (int) $one[1] <= $now - self::WINDOW ) {
			$one = [ 0, $now ];
		}
		if ( (int) $site[0] >= self::PER_HOUR || (int) $one[0] >= self::PER_IP ) {
			return false;
		}

		unset( $hits['*'], $hits[ $who ] );
		$hits = array_filter( $hits, static fn( $h ) => is_array( $h ) && (int) ( $h[1] ?? 0 ) > $now - self::WINDOW );
		if ( count( $hits ) >= 500 ) {
			uasort( $hits, static fn( $a, $b ) => $b[1] <=> $a[1] );
			$hits = array_slice( $hits, 0, 499, true );
		}
		$hits[ $who ] = [ (int) $one[0] + 1, (int) $one[1] ];
		$hits['*']    = [ (int) $site[0] + 1, (int) $site[1] ];
		update_option( self::HITS, $hits, false );
		return true;
	}

	/** The visitor's address: Cloudflare's header only when Cloudflare sent it; IPv6 by its /64. */
	private static function client(): string {
		$remote = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as an IP below.
		$real   = (string) ( $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated as an IP below.
		$ip     = ( '' !== $real && filter_var( $real, FILTER_VALIDATE_IP ) && self::in_ranges( $remote, self::CLOUDFLARE ) ) ? $real : $remote;
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$bin = (string) inet_pton( $ip );
			return bin2hex( substr( $bin, 0, 8 ) ) . '::/64';
		}
		return (string) filter_var( $ip, FILTER_VALIDATE_IP );
	}

	private static function in_ranges( string $ip, array $ranges ): bool {
		$bin = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- a bad address is simply not in range.
		if ( false === $bin ) {
			return false;
		}
		foreach ( $ranges as $range ) {
			[ $net, $bits ] = explode( '/', $range );
			$net            = inet_pton( $net );
			if ( strlen( $net ) !== strlen( $bin ) ) {
				continue;
			}
			$bytes = intdiv( (int) $bits, 8 );
			$rest  = (int) $bits % 8;
			if ( substr( $bin, 0, $bytes ) !== substr( $net, 0, $bytes ) ) {
				continue;
			}
			if ( 0 === $rest || ( ( ord( $bin[ $bytes ] ) ^ ord( $net[ $bytes ] ) ) & ( 0xFF << ( 8 - $rest ) ) & 0xFF ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Hold the form back when the site's hourly allowance runs low, so the
	 * horoscopes on the rest of the site keep theirs, and for a few minutes
	 * after the api failed three times in a row.
	 */
	private static function busy(): bool {
		$fails = get_transient( self::FAILS );
		if ( is_array( $fails ) && (int) ( $fails['count'] ?? 0 ) >= 3 ) {
			return true;
		}
		$quota     = get_option( PublicData::QUOTA_OPTION, [] );
		$limit     = is_array( $quota ) ? ( $quota['limit'] ?? null ) : null;
		$remaining = is_array( $quota ) ? ( $quota['remaining'] ?? null ) : null;
		$fresh     = is_array( $quota ) && (int) ( $quota['at'] ?? 0 ) > time() - HOUR_IN_SECONDS;
		return $fresh && null !== $limit && null !== $remaining && (int) $remaining < max( 20, (int) $limit / 5 );
	}

	private static function failed(): void {
		$fails = get_transient( self::FAILS );
		$count = is_array( $fails ) ? (int) ( $fails['count'] ?? 0 ) : 0;
		set_transient( self::FAILS, [ 'count' => $count + 1 ], 5 * MINUTE_IN_SECONDS );
	}

	/** A line when the clocks changed at that place on that day: the hour may be off by one. */
	private static function dst_note( string $date, string $time, string $tz, bool $unknown ): string {
		if ( '' === $tz || $unknown ) {
			return '';
		}
		try {
			$zone  = new \DateTimeZone( $tz );
			$start = new \DateTimeImmutable( $date . ' 00:00', $zone );
		} catch ( \Exception $e ) {
			return '';
		}
		$changes = $zone->getTransitions( $start->getTimestamp() + 1, $start->getTimestamp() + DAY_IN_SECONDS );
		if ( count( $changes ) < 2 ) {
			return '';
		}
		return UI::callout( esc_html__( 'The clocks changed at this place on that day, so one hour of it happened twice or not at all. Check the birth time was read from a local clock.', 'astroway' ) );
	}

	/** Without the time the Moon's degree is a guess; say so, and when its sign is one too. */
	private static function moon_note( array $chart ): string {
		$moon = null;
		foreach ( (array) ( $chart['planets'] ?? [] ) as $planet ) {
			if ( is_array( $planet ) && 'Moon' === ( $planet['name'] ?? '' ) && isset( $planet['longitude'] ) ) {
				$moon = (float) $planet['longitude'];
			}
		}
		if ( null === $moon ) {
			return '';
		}
		$from = Render::sign_of( $moon - self::MOON_DRIFT );
		$to   = Render::sign_of( $moon + self::MOON_DRIFT );
		if ( $from === $to ) {
			return UI::callout( esc_html__( 'Without the time of birth the Moon\'s degree is approximate; its sign is certain for that day.', 'astroway' ) );
		}
		return UI::callout(
			esc_html(
				sprintf(
					/* translators: 1, 2: zodiac signs */
					__( 'The Moon changed sign that day, from %1$s to %2$s. The time of birth decides which one is yours.', 'astroway' ),
					Render::sign_label( $from ),
					Render::sign_label( $to )
				)
			),
			'tens'
		);
	}

	/** A field of this form's post, unslashed and trimmed; '' when absent. */
	private static function posted( string $name ): string {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST[ $name ] ) || ! is_string( $_POST[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- a read-only calculation, checked by origin instead of a nonce a page cache would freeze.
			return '';
		}
		return trim( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
	}
}
