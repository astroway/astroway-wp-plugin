<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The daily transit alert: one email on the days the sky does something.
 *
 * An alert, not a newsletter. A message every morning saying the Moon is
 * waxing gibbous trains its readers to delete it unopened, so the mail goes out
 * only when a planet stations or the Moon goes void of course, and the phase
 * and the retrograde list ride along as context inside it.
 *
 * Scheduled hourly and checked against the site's own clock rather than
 * scheduled for a chosen hour. That way changing the hour needs no
 * rescheduling, and a site that moves onto summer time keeps sending at the
 * hour its owner picked instead of an hour either side of it.
 *
 * @since 1.3.0
 */
class Digest {

	public const EVENT        = 'astroway_sky_digest_tick';
	public const LAST_SENT    = 'astroway_digest_last_sent';
	public const DEFAULT_HOUR = 8;

	public static function register(): void {
		add_action( self::EVENT, [ __CLASS__, 'tick' ] );
		add_action( 'init', [ __CLASS__, 'ensure_scheduled' ] );
		add_action( 'admin_post_astroway_digest_test', [ __CLASS__, 'handle_test' ] );
	}

	/** Enabled, allowed by the plan and able to reach the api. */
	public static function available(): bool {
		return Tier::can( 'transit_alerts' ) && ( new ApiClient() )->has_key();
	}

	public static function enabled(): bool {
		return ! empty( Admin::get( 'digest_enabled' ) ) && self::available();
	}

	public static function hour(): int {
		$hour = Admin::get( 'digest_hour', self::DEFAULT_HOUR );
		return max( 0, min( 23, (int) $hour ) );
	}

	/**
	 * Addresses the digest goes to. The site administrator when none were given,
	 * because an alert nobody receives is the same as no alert.
	 *
	 * @return list<string>
	 */
	public static function recipients(): array {
		$saved = self::sanitize_recipients( (string) Admin::get( 'digest_recipients', '' ) );
		if ( '' !== $saved ) {
			return explode( ', ', $saved );
		}
		$admin = (string) get_option( 'admin_email', '' );
		return is_email( $admin ) ? [ $admin ] : [];
	}

	/** A comma separated list with everything that is not an address removed. */
	public static function sanitize_recipients( string $raw ): string {
		$out   = [];
		$parts = preg_split( '/[,;\s]+/', $raw );
		foreach ( is_array( $parts ) ? $parts : [] as $candidate ) {
			$candidate = sanitize_email( trim( (string) $candidate ) );
			if ( '' !== $candidate && is_email( $candidate ) && ! in_array( $candidate, $out, true ) ) {
				$out[] = $candidate;
			}
		}
		return implode( ', ', $out );
	}

	/** Keep the hourly tick in step with the setting. */
	public static function ensure_scheduled(): void {
		$scheduled = wp_next_scheduled( self::EVENT );
		if ( self::enabled() ) {
			if ( false === $scheduled ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', self::EVENT );
			}
			return;
		}
		if ( false !== $scheduled ) {
			wp_clear_scheduled_hook( self::EVENT );
		}
	}

	/**
	 * Once an hour: send if this is the hour, and if today has not been sent.
	 *
	 * The day is marked spent as soon as the hour matches, before the digest is
	 * built. The next tick is an hour away and its hour will not match, so there
	 * is no retry to preserve; marking afterwards would only leave room for two
	 * emails if a page load ran the event twice.
	 */
	public static function tick( ?int $now = null ): void {
		if ( ! self::enabled() ) {
			return;
		}

		$now    = null === $now ? time() : $now;
		$offset = Sky::offset( '', $now );
		$local  = $now + (int) round( $offset * HOUR_IN_SECONDS );
		if ( (int) gmdate( 'G', $local ) !== self::hour() ) {
			return;
		}

		$today = Sky::local_date( $offset, $now );
		if ( (string) get_option( self::LAST_SENT, '' ) === $today ) {
			return;
		}
		update_option( self::LAST_SENT, $today, false );

		$digest = self::compose( $now );
		if ( null === $digest ) {
			return;
		}
		self::send( $digest, self::recipients() );
	}

	/**
	 * Today's digest, or null on a day with nothing to report.
	 *
	 * @return array{subject: string, body: string, events: list<string>}|null
	 */
	public static function compose( ?int $now = null ): ?array {
		$now     = null === $now ? time() : $now;
		$offset  = Sky::offset( '', $now );
		$today   = Sky::local_date( $offset, $now );
		$events  = array_merge( self::stations( $now, $offset, $today ), self::void_windows( $now, $offset, $today ) );
		$context = self::context( $now, $today );

		if ( empty( $events ) ) {
			return null;
		}

		$headline = 1 === count( $events )
			? rtrim( $events[0], '.' )
			: sprintf(
				/* translators: %s = a date */
				__( 'sky notes for %s', 'astroway' ),
				(string) wp_date( (string) get_option( 'date_format', 'Y-m-d' ), (int) strtotime( $today . 'T12:00:00Z' ) )
			);

		$lines = $events;
		if ( ! empty( $context ) ) {
			$lines[] = '';
			$lines   = array_merge( $lines, $context );
		}
		$lines[] = '';
		$lines[] = __( 'Sent by the AstroWay plugin. Turn it off under AstroWay, Settings.', 'astroway' );
		$lines[] = admin_url( 'admin.php?page=' . Admin::PAGE_SETTINGS );

		return [
			// Not run through gettext: the site name is the site's own and the
			// headline is already translated, so the msgid would be a colon.
			// It also collided with the one the placement card uses for
			// "Moon sign: Anna", which left translators one string and two
			// contradictory notes about what it means.
			'subject' => (string) get_bloginfo( 'name' ) . ': ' . $headline,
			'body'    => implode( "\n", $lines ),
			'events'  => $events,
		];
	}

	/** Planets stationing today, in either direction. */
	private static function stations( int $now, float $offset, string $today ): array {
		$data = Sky::retrogrades( $now );
		if ( ! is_array( $data ) ) {
			return [];
		}

		$lines = [];
		foreach ( (array) ( $data['periods'] ?? [] ) as $period ) {
			if ( ! is_array( $period ) ) {
				continue;
			}
			$planet = Render::planet_label( (string) ( $period['planetName'] ?? '' ) );
			$fields = [
				'retroStart' => true,
				'retroEnd'   => false,
			];
			foreach ( $fields as $field => $turning_back ) {
				$stamp = strtotime( (string) ( $period[ $field ] ?? '' ) );
				if ( false === $stamp || Sky::local_date( $offset, $stamp ) !== $today ) {
					continue;
				}
				$at      = (string) wp_date( (string) get_option( 'time_format', 'H:i' ), $stamp, self::zone( $offset ) );
				$lines[] = $turning_back
					? sprintf(
						/* translators: 1: planet name, 2: a time of day */
						__( '%1$s turns retrograde today at %2$s.', 'astroway' ),
						$planet,
						$at
					)
					: sprintf(
						/* translators: 1: planet name, 2: a time of day */
						__( '%1$s turns direct today at %2$s.', 'astroway' ),
						$planet,
						$at
					);
			}
		}
		return $lines;
	}

	/** Void of course windows opening today. */
	private static function void_windows( int $now, float $offset, string $today ): array {
		$data = Sky::moon_voc( [ 'range_days' => 2 ], $now );
		if ( ! is_array( $data ) ) {
			return [];
		}

		$lines = [];
		foreach ( (array) ( $data['periods'] ?? [] ) as $period ) {
			if ( ! is_array( $period ) ) {
				continue;
			}
			$start = strtotime( (string) ( $period['startDate'] ?? '' ) );
			$end   = strtotime( (string) ( $period['endDate'] ?? '' ) );
			if ( false === $start || false === $end || Sky::local_date( $offset, $start ) !== $today ) {
				continue;
			}
			$format  = (string) get_option( 'time_format', 'H:i' );
			$lines[] = sprintf(
				/* translators: 1: a time of day, 2: a time of day */
				__( 'The Moon is void of course from %1$s to %2$s.', 'astroway' ),
				(string) wp_date( $format, $start, self::zone( $offset ) ),
				(string) wp_date( $format, $end, self::zone( $offset ) )
			);
		}
		return $lines;
	}

	/** The standing state of the sky, for the mails that go out anyway. */
	private static function context( int $now, string $today ): array {
		$lines = [];

		$data = Sky::retrogrades( $now );
		if ( is_array( $data ) ) {
			$backwards = Sky::retrograde_now( $data, $now );
			if ( ! empty( $backwards ) ) {
				$lines[] = sprintf(
					/* translators: %s = comma-separated list of planet names */
					__( 'Retrograde right now: %s.', 'astroway' ),
					implode( ', ', $backwards )
				);
			}
		}

		// The phase comes from the anonymous endpoint the moon widget reads, so
		// it costs nothing a page on this site is not already paying for.
		// Asked for by date rather than left to default to today: composing a
		// digest for another day is exactly what the "send one now" button does
		// out of hours, and an undated call would report tonight's Moon on it.
		$moon = PublicData::get( 'moon_phase', [ 'date' => $today ] );
		if ( is_array( $moon ) && ! empty( $moon['phaseName'] ) ) {
			$named   = (string) ( $moon['localized']['phaseName'] ?? '' );
			$lines[] = sprintf(
				/* translators: 1: moon phase name, 2: percentage of the disc lit */
				__( 'Moon: %1$s, %2$s%% lit.', 'astroway' ),
				'' !== $named ? $named : Render::phase_label( (string) $moon['phaseName'] ),
				number_format_i18n( (float) ( $moon['illuminationPercent'] ?? 0 ), 0 )
			);
		}

		return $lines;
	}

	/** The site's offset as a zone, so wp_date prints the site's own clock. */
	private static function zone( float $offset ): \DateTimeZone {
		$minutes = (int) round( $offset * 60 );
		$sign    = $minutes < 0 ? '-' : '+';
		$minutes = abs( $minutes );
		return new \DateTimeZone( sprintf( '%s%02d:%02d', $sign, intdiv( $minutes, 60 ), $minutes % 60 ) );
	}

	/**
	 * @param array $digest     From compose().
	 * @param array $recipients Addresses.
	 */
	public static function send( array $digest, array $recipients ): bool {
		if ( empty( $recipients ) ) {
			return false;
		}
		return (bool) wp_mail(
			$recipients,
			(string) $digest['subject'],
			(string) $digest['body']
		);
	}

	/**
	 * The "send one now" button: composes today's digest and mails it to
	 * whoever pressed it, so the recipients are not sent a test.
	 */
	public static function handle_test(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'astroway' ) );
		}
		check_admin_referer( 'astroway_digest_test' );

		$result = 'unavailable';
		if ( self::available() ) {
			$digest = self::compose();
			if ( null === $digest ) {
				$result = 'empty';
			} else {
				$to     = (string) wp_get_current_user()->user_email;
				$result = self::send( $digest, is_email( $to ) ? [ $to ] : [] ) ? 'sent' : 'failed';
			}
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'            => Admin::PAGE_SETTINGS,
					'astroway_digest' => $result,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
