<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The widgets and their fields, for every editor that is not Gutenberg.
 *
 * Panels, fields and labels repeat the block editor's word for word, in its
 * order, so a page builder offers what the block offers and shares its
 * translations. The section block is left out: it only groups other blocks.
 *
 * @since 2.5.0
 */
final class WidgetManifest {

	/** @var array<string, array>|null */
	private static $cache = null;

	private static function text( string $name, string $label ): array {
		return [
			'name'  => $name,
			'type'  => 'text',
			'label' => $label,
		];
	}

	/** @param array<string, string> $options value => label, the first one preselected. */
	private static function select( string $name, string $label, array $options ): array {
		return [
			'name'    => $name,
			'type'    => 'select',
			'label'   => $label,
			'options' => $options,
		];
	}

	private static function signs( string $blank ): array {
		return [
			''            => $blank,
			'aries'       => __( 'Aries', 'astroway' ),
			'taurus'      => __( 'Taurus', 'astroway' ),
			'gemini'      => __( 'Gemini', 'astroway' ),
			'cancer'      => __( 'Cancer', 'astroway' ),
			'leo'         => __( 'Leo', 'astroway' ),
			'virgo'       => __( 'Virgo', 'astroway' ),
			'libra'       => __( 'Libra', 'astroway' ),
			'scorpio'     => __( 'Scorpio', 'astroway' ),
			'sagittarius' => __( 'Sagittarius', 'astroway' ),
			'capricorn'   => __( 'Capricorn', 'astroway' ),
			'aquarius'    => __( 'Aquarius', 'astroway' ),
			'pisces'      => __( 'Pisces', 'astroway' ),
		];
	}

	private static function sign( string $label = '' ): array {
		return self::select( 'sign', '' !== $label ? $label : __( 'Zodiac sign', 'astroway' ), self::signs( __( 'Default (today\'s featured sign)', 'astroway' ) ) );
	}

	/** The day, week and month cards ask the visitor when no sign is set. */
	private static function asked_sign(): array {
		return self::select( 'sign', __( 'Zodiac sign', 'astroway' ), self::signs( __( 'Visitor picks the sign', 'astroway' ) ) );
	}

	private static function lang(): array {
		return self::select(
			'lang',
			__( 'Language', 'astroway' ),
			[
				''   => __( 'Site default', 'astroway' ),
				'en' => 'English (en)',
				'uk' => 'Українська (uk)',
				'de' => 'Deutsch (de)',
				'ru' => 'Русский (ru)',
				'pl' => 'Polski (pl)',
				'es' => 'Español (es)',
				'pt' => 'Português (pt)',
				'fr' => 'Français (fr)',
				'it' => 'Italiano (it)',
				'nl' => 'Nederlands (nl)',
				'cs' => 'Čeština (cs)',
				'ro' => 'Română (ro)',
				'hu' => 'Magyar (hu)',
				'el' => 'Ελληνικά (el)',
				'tr' => 'Türkçe (tr)',
				'ar' => 'العربية (ar)',
				'hi' => 'हिन्दी (hi)',
				'ja' => '日本語 (ja)',
				'ko' => '한국어 (ko)',
				'vi' => 'Tiếng Việt (vi)',
				'id' => 'Bahasa Indonesia (id)',
			]
		);
	}

	/** The picture sets, then the drawn face the plugin had before them. */
	private static function art(): array {
		return self::select( 'art', __( 'Card pictures', 'astroway' ), Tarot::arts() + [ 'none' => __( 'Drawn face, no pictures', 'astroway' ) ] );
	}

	private static function look(): array {
		return self::select(
			'look',
			__( 'Style', 'astroway' ),
			[
				''           => __( 'Site default', 'astroway' ),
				'instrument' => __( 'Instrument', 'astroway' ),
				'night'      => __( 'Night sky', 'astroway' ),
				'native'     => __( 'Theme colours', 'astroway' ),
			]
		);
	}

	private static function theme(): array {
		return self::select(
			'theme',
			__( 'Theme', 'astroway' ),
			[
				''        => __( 'Default (light)', 'astroway' ),
				'dark'    => __( 'Dark', 'astroway' ),
				'light'   => __( 'Light', 'astroway' ),
				'console' => __( 'Console (green on black)', 'astroway' ),
			]
		);
	}

	private static function lat( string $name = 'lat' ): array {
		return self::text( $name, __( 'Latitude (-90 to 90)', 'astroway' ) );
	}

	private static function lon( string $name = 'lon' ): array {
		return self::text( $name, __( 'Longitude (-180 to 180)', 'astroway' ) );
	}

	private static function today(): array {
		return self::text( 'date', __( 'Date (leave blank for today)', 'astroway' ) );
	}

	private static function offset(): array {
		return self::text( 'timezone_offset', __( 'UTC offset in hours (blank for the site default)', 'astroway' ) );
	}

	private static function tz_wide(): array {
		return self::text( 'tz', __( 'Timezone (Europe/Kyiv, +05:30, or 5.5)', 'astroway' ) );
	}

	private static function chart(): array {
		return [
			self::text( 'date', __( 'Date (YYYY-MM-DD)', 'astroway' ) ),
			self::text( 'time', __( 'Time (HH:MM)', 'astroway' ) ),
			self::lat(),
			self::lon(),
			self::text( 'name', __( 'Person name (optional)', 'astroway' ) ),
			self::text( 'tz', __( 'Timezone (Europe/Kyiv or +03:00)', 'astroway' ) ),
			self::lang(),
		];
	}

	private static function kundli(): array {
		return [
			self::text( 'date', __( 'Date (YYYY-MM-DD)', 'astroway' ) ),
			self::text( 'time', __( 'Time (HH:MM)', 'astroway' ) ),
			self::lat(),
			self::lon(),
			self::tz_wide(),
			self::theme(),
			self::lang(),
		];
	}

	private static function returns(): array {
		return [
			self::text( 'return_lat', __( 'Return place: latitude (blank for the place of birth)', 'astroway' ) ),
			self::text( 'return_lon', __( 'Return place: longitude', 'astroway' ) ),
			self::text( 'return_tz', __( 'Return place: timezone for the time shown (blank for UTC)', 'astroway' ) ),
		];
	}

	/** Person A and B, as synastry and Davison ask for them. */
	private static function two_births( bool $synastry ): array {
		$fields = [];
		foreach ( [ 'a', 'b' ] as $who ) {
			if ( 'a' === $who ) {
				$name = $synastry ? __( 'Person A: name (for the table heading)', 'astroway' ) : __( 'Person A: name', 'astroway' );
				$date = __( 'Person A: date (YYYY-MM-DD)', 'astroway' );
				$time = $synastry ? __( 'Person A: time (HH:MM, blank if unknown)', 'astroway' ) : __( 'Person A: time (HH:MM)', 'astroway' );
				$lat  = __( 'Person A: latitude', 'astroway' );
				$lon  = __( 'Person A: longitude', 'astroway' );
				$tz   = __( 'Person A: timezone', 'astroway' );
			} else {
				$name = $synastry ? __( 'Person B: name (for the table heading)', 'astroway' ) : __( 'Person B: name', 'astroway' );
				$date = __( 'Person B: date (YYYY-MM-DD)', 'astroway' );
				$time = $synastry ? __( 'Person B: time (HH:MM, blank if unknown)', 'astroway' ) : __( 'Person B: time (HH:MM)', 'astroway' );
				$lat  = __( 'Person B: latitude', 'astroway' );
				$lon  = __( 'Person B: longitude', 'astroway' );
				$tz   = __( 'Person B: timezone', 'astroway' );
			}
			array_push(
				$fields,
				self::text( 'name_' . $who, $name ),
				self::text( 'date_' . $who, $date ),
				self::text( 'time_' . $who, $time ),
				self::text( 'lat_' . $who, $lat ),
				self::text( 'lon_' . $who, $lon ),
				self::text( 'tz_' . $who, $tz )
			);
		}
		return $fields;
	}

	/** Panel and fields per block, in the block editor's order. */
	private static function definitions(): array {
		$birth = __( 'Birth data', 'astroway' );
		$month = __( 'Any date inside the month (blank for this month)', 'astroway' );
		return [
			'solar-return'         => [
				'panel'  => $birth,
				'fields' => array_merge( self::chart(), [ self::text( 'year', __( 'Year (blank for the one in force now)', 'astroway' ) ) ], self::returns() ),
			],
			'lunar-return'         => [
				'panel'  => $birth,
				'fields' => array_merge( self::chart(), [ self::text( 'after', __( 'First return after (YYYY-MM-DD, blank for the 1st of this month)', 'astroway' ) ) ], self::returns() ),
			],
			'davison'              => [
				'panel'  => __( 'Two births', 'astroway' ),
				'fields' => array_merge( self::two_births( false ), [ self::lang() ] ),
			],
			'natal-chart'          => [
				'panel'  => $birth,
				'fields' => array_merge(
					self::chart(),
					[
						self::select(
							'transits',
							__( 'Transits', 'astroway' ),
							[
								''      => __( 'None', 'astroway' ),
								'today' => __( 'Today\'s sky over the chart', 'astroway' ),
							]
						),
					]
				),
			],
			'bodygraph'            => [
				'panel'  => $birth,
				'fields' => self::chart(),
			],
			'moon-sign'            => [
				'panel'  => $birth,
				'fields' => self::chart(),
			],
			'rising-sign'          => [
				'panel'  => $birth,
				'fields' => self::chart(),
			],
			'kundli'               => [
				'panel'  => $birth,
				'fields' => self::kundli(),
			],
			'transit'              => [
				'panel'  => __( 'Transit date', 'astroway' ),
				'fields' => [ self::today(), self::theme(), self::lang() ],
			],
			'panchang'             => [
				'panel'  => __( 'Panchang', 'astroway' ),
				'fields' => [ self::today(), self::lat(), self::lon(), self::tz_wide(), self::theme(), self::lang() ],
			],
			'numerology'           => [
				'panel'  => __( 'Numerology', 'astroway' ),
				'fields' => [
					self::text( 'name', __( 'Full name', 'astroway' ) ),
					self::text( 'date', __( 'Date of birth (YYYY-MM-DD)', 'astroway' ) ),
					self::select(
						'system',
						__( 'System', 'astroway' ),
						[
							'pythagorean' => __( 'Pythagorean', 'astroway' ),
							'chaldean'    => __( 'Chaldean', 'astroway' ),
						]
					),
					self::theme(),
					self::lang(),
				],
			],
			'synastry'             => [
				'panel'  => __( 'Two subjects', 'astroway' ),
				'fields' => array_merge(
					self::two_births( true ),
					[ self::text( 'aspects', __( 'How many aspects to show (6 by default)', 'astroway' ) ), self::theme(), self::lang() ]
				),
			],
			'daily-horoscope'      => [
				'panel'  => __( 'Daily horoscope', 'astroway' ),
				'fields' => [ self::asked_sign(), self::lang() ],
			],
			'moon-phase'           => [
				'panel'  => __( 'Moon phase', 'astroway' ),
				'fields' => [ self::today(), self::lang() ],
			],
			'daily-tarot'          => [
				'panel'  => __( 'Daily Tarot', 'astroway' ),
				'fields' => [
					self::select(
						'deck',
						__( 'Deck', 'astroway' ),
						[
							'rider-waite' => __( 'Rider-Waite-Smith', 'astroway' ),
							'marseille'   => __( 'Marseille', 'astroway' ),
							'lenormand'   => __( 'Lenormand', 'astroway' ),
						]
					),
					self::art(),
					self::lang(),
				],
			],
			'tarot-spread'         => [
				'panel'  => __( 'Tarot spread', 'astroway' ),
				'fields' => [
					self::select(
						'spread',
						__( 'Spread', 'astroway' ),
						[
							'three-card'   => __( 'Past, present, future', 'astroway' ),
							'celtic-cross' => __( 'Celtic Cross', 'astroway' ),
						]
					),
					self::select(
						'redraw',
						__( 'Draw again button', 'astroway' ),
						[
							'yes' => __( 'Show', 'astroway' ),
							'no'  => __( 'Hide', 'astroway' ),
						]
					),
					self::art(),
					self::select(
						'reveal',
						__( 'Cards', 'astroway' ),
						[
							'no'    => __( 'Face up', 'astroway' ),
							'click' => __( 'Face down, turned by the visitor', 'astroway' ),
						]
					),
					self::select(
						'reading',
						__( 'Reading of the spread', 'astroway' ),
						[
							'0' => __( 'Off', 'astroway' ),
							'1' => __( 'After the last card is turned', 'astroway' ),
						]
					),
					self::lang(),
				],
			],
			'weekly-horoscope'     => [
				'panel'  => __( 'Weekly horoscope', 'astroway' ),
				'fields' => [ self::asked_sign(), self::text( 'date', __( 'Any date inside the week (blank for this week)', 'astroway' ) ), self::lang() ],
			],
			'monthly-horoscope'    => [
				'panel'  => __( 'Monthly horoscope', 'astroway' ),
				'fields' => [ self::asked_sign(), self::text( 'date', $month ), self::lang() ],
			],
			'planet-of-day'        => [
				'panel'  => __( 'Planet of the day', 'astroway' ),
				'fields' => [ self::today(), self::lang() ],
			],
			'mini-chart'           => [
				'panel'  => $birth,
				'fields' => self::kundli(),
			],
			'monthly-forecast'     => [
				'panel'  => __( 'Monthly forecast', 'astroway' ),
				'fields' => [ self::sign(), self::text( 'date', $month ), self::theme(), self::lang() ],
			],
			'transit-timeline'     => [
				'panel'  => __( 'Moon transit timeline', 'astroway' ),
				'fields' => [ self::text( 'date', __( 'Day the seven start from (blank for today)', 'astroway' ) ), self::theme(), self::lang() ],
			],
			'retrograde'           => [
				'panel'  => __( 'Retrograde status', 'astroway' ),
				'fields' => [
					self::select(
						'planet',
						__( 'Planet', 'astroway' ),
						[
							'mercury' => __( 'Mercury', 'astroway' ),
							'venus'   => __( 'Venus', 'astroway' ),
							'mars'    => __( 'Mars', 'astroway' ),
							'jupiter' => __( 'Jupiter', 'astroway' ),
							'saturn'  => __( 'Saturn', 'astroway' ),
							'uranus'  => __( 'Uranus', 'astroway' ),
							'neptune' => __( 'Neptune', 'astroway' ),
							'pluto'   => __( 'Pluto', 'astroway' ),
						]
					),
					self::lang(),
				],
			],
			'retrogrades'          => [
				'panel'  => __( 'Retrograde planets', 'astroway' ),
				'fields' => [ self::text( 'date', __( 'Date (leave blank for today)', 'astroway' ) ), self::lang() ],
			],
			'moon-voc'             => [
				'panel'  => __( 'Void of course Moon', 'astroway' ),
				'fields' => [
					self::text( 'date', __( 'Day to start from (blank for today)', 'astroway' ) ),
					self::text( 'time', __( 'Time to start from (HH:MM, blank for midnight)', 'astroway' ) ),
					self::offset(),
					self::text( 'range_days', __( 'Days to look ahead (1 to 30, default 7)', 'astroway' ) ),
					self::lang(),
				],
			],
			'yearly-horoscope'     => [
				'panel'  => __( 'Yearly horoscope', 'astroway' ),
				'fields' => [ self::sign(), self::text( 'date', __( 'Any date inside the year (blank for this year)', 'astroway' ) ), self::lang() ],
			],
			'zodiac-compatibility' => [
				'panel'  => __( 'Two signs', 'astroway' ),
				'fields' => [
					self::select( 'sign1', __( 'First sign', 'astroway' ), self::signs( __( 'Default (today\'s featured sign)', 'astroway' ) ) ),
					self::select( 'sign2', __( 'Second sign', 'astroway' ), self::signs( __( 'Default (today\'s featured sign)', 'astroway' ) ) ),
					self::lang(),
				],
			],
			'compatibility-matrix' => [
				'panel'  => __( 'Sign compatibility', 'astroway' ),
				'fields' => [
					self::select( 'sign', __( 'One sign against the others (blank for all pairs)', 'astroway' ), self::signs( __( 'All pairs', 'astroway' ) ) ),
					self::lang(),
				],
			],
			'chinese-zodiac'       => [
				'panel'  => __( 'Chinese zodiac', 'astroway' ),
				'fields' => [
					self::text( 'date', __( 'Birth date (YYYY-MM-DD)', 'astroway' ) ),
					self::text( 'time', __( 'Birth time (HH:MM, optional)', 'astroway' ) ),
					self::offset(),
					self::lang(),
				],
			],
			'planetary-hours'      => [
				'panel'  => __( 'Planetary hours', 'astroway' ),
				'fields' => [ self::today(), self::lat( 'latitude' ), self::lon( 'longitude' ), self::offset(), self::lang() ],
			],
		];
	}

	/** A block title in the admin's language, without the brand the category already shows. */
	private static function title( string $title ): string {
		if ( function_exists( 'translate_with_gettext_context' ) ) {
			// The call register_block_type() makes for block.json titles, so both share one translation.
			$title = translate_with_gettext_context( $title, 'block title', 'astroway' ); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction, WordPress.WP.I18n.NonSingularStringLiteralText
		}
		return trim( (string) preg_replace( '/\s*\(AstroWay\)$/', '', $title ) );
	}

	/**
	 * Every widget: title, icon and keywords from its block.json, the panel and
	 * fields the block editor shows, the look last, and the block attributes no
	 * field shows (spellings kept for old pages, such as `latitude`).
	 *
	 * @return array<string, array{title:string, icon:string, keywords:string[], panel:string, fields:array, hidden:string[]}>
	 */
	/**
	 * Cards whose parts an author can leave out with `hide_sections`, and the
	 * keys each one knows. Listed in the shortcode reference too.
	 */
	public const SECTIONS = [
		'natal-chart'       => [ 'big-three', 'summary', 'wheel', 'grid', 'placements', 'aspects', 'transits', 'elements', 'patterns', 'meaning' ],
		'bodygraph'         => [ 'tags', 'facts', 'bodygraph', 'centres', 'channels' ],
		'synastry'          => [ 'summary', 'spheres', 'strengths', 'wheel', 'aspects', 'houses', 'all-aspects' ],
		'daily-tarot'       => [ 'tags', 'keywords', 'facts', 'other-side' ],
		'moon-phase'        => [ 'facts', 'meaning', 'phases' ],
		'daily-horoscope'   => [ 'signs', 'sky' ],
		'weekly-horoscope'  => [ 'signs', 'sky' ],
		'monthly-horoscope' => [ 'signs', 'sky' ],
	];

	/** Cards with a list of readings an author can open on its first row or keep closed. */
	private const OPENS = [ 'natal-chart', 'synastry', 'bodygraph' ];

	/** The fields every sectioned card gets after its own. */
	private static function view_fields( string $slug ): array {
		$out = [];
		if ( isset( self::SECTIONS[ $slug ] ) ) {
			$out[] = self::text( 'hide_sections', __( 'Hide sections (comma-separated keys)', 'astroway' ) );
		}
		if ( in_array( $slug, self::OPENS, true ) ) {
			$out[] = self::select(
				'open',
				__( 'Readings list', 'astroway' ),
				[
					''      => __( 'As the card has it', 'astroway' ),
					'first' => __( 'First row open', 'astroway' ),
					'none'  => __( 'All rows closed', 'astroway' ),
				]
			);
		}
		return $out;
	}

	public static function widgets(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$out = [];
		foreach ( self::definitions() as $slug => $def ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file, not a remote URL.
			$json   = json_decode( (string) file_get_contents( ASTROWAY_WP_PLUGIN_DIR . 'blocks/' . $slug . '/block.json' ), true );
			$json   = is_array( $json ) ? $json : [];
			$fields = array_merge( $def['fields'], self::view_fields( $slug ), [ self::look() ] );
			$names  = array_column( $fields, 'name' );

			$out[ $slug ] = [
				'title'    => self::title( (string) ( $json['title'] ?? $slug ) ),
				'icon'     => (string) ( $json['icon'] ?? 'star-filled' ),
				'keywords' => array_values( (array) ( $json['keywords'] ?? [] ) ),
				'panel'    => $def['panel'],
				'fields'   => $fields,
				'hidden'   => array_values( array_diff( array_keys( (array) ( $json['attributes'] ?? [] ) ), $names ) ),
			];
		}
		self::$cache = $out;
		return $out;
	}

	public static function widget( string $slug ): ?array {
		return self::widgets()[ $slug ] ?? null;
	}
}
