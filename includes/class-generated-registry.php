<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes generated from the api's OpenAPI spec.
 *
 * The plugin hand-writes twenty widgets, because the ones people actually put
 * on a page deserve a purpose-built card. That leaves roughly seven hundred
 * endpoints reachable only by writing HTTP calls yourself, which is what a
 * WordPress plugin exists to spare you.
 *
 * There is one shortcode per endpoint, and one registry rather than one class
 * per endpoint. What varies between them is a path, a method and a list of
 * attributes; everything else (sanitise, call, cache, render, fail) is the same
 * code, so the generated part is data.
 *
 * Loading is in two steps on purpose. The index is a flat map of tag to family
 * and is read on every request, because shortcodes have to be registered before
 * anything renders. The family file carries the paths and attributes and is read
 * only when a shortcode of that family is actually on the page, so a site using
 * one Vedic shortcode does not parse the other six hundred.
 *
 * @since 1.2.0
 */
class GeneratedRegistry {

	/** Attribute flags, mirrored in scripts/generate-from-openapi.py. */
	public const FLAG_REQUIRED = 1;
	public const FLAG_NESTED   = 2;
	public const FLAG_QUERY    = 4;
	public const FLAG_PATH     = 8;

	/** Packed attribute offsets: [attr, field, type, flags]. */
	private const A_ATTR  = 0;
	private const A_FIELD = 1;
	private const A_TYPE  = 2;
	private const A_FLAGS = 3;

	private static ?array $index = null;

	/** @var array<string, array> family slug => endpoints */
	private static array $families = [];

	public static function register(): void {
		foreach ( array_keys( self::index() ) as $tag ) {
			// Never take a name the hand-written half already answers to. The
			// generator excludes them, and this is the guard for the case where
			// a future hand-written shortcode lands on a generated name.
			if ( ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, [ __CLASS__, 'render' ] );
			}
		}

		// Blocks are handled in two different ways on purpose.
		//
		// The editor needs all of them registered, because you cannot insert a
		// block that does not exist; there, seven hundred registrations cost
		// about two milliseconds and nobody notices on an admin screen.
		//
		// The front end needs no registration at all. `pre_render_block` fires
		// for every block in the content, registered or not, so the handful
		// actually on the page can be rendered on the spot and the rest cost
		// nothing. A visitor's request should not pay for endpoints the page
		// does not use.
		if ( is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
			add_action( 'init', [ __CLASS__, 'register_blocks' ], 20 );
		} else {
			add_filter( 'pre_render_block', [ __CLASS__, 'render_block_on_demand' ], 10, 2 );
		}
	}

	/** Block name for a shortcode tag: astroway_bazi_ten_gods -> astroway/bazi-ten-gods. */
	public static function block_name( string $tag ): string {
		return 'astroway/' . str_replace( '_', '-', (string) preg_replace( '/^astroway_/', '', $tag ) );
	}

	/** Shortcode tag for a block name, or '' when it is not one of ours. */
	public static function tag_for_block( string $block ): string {
		if ( 0 !== strpos( $block, 'astroway/' ) ) {
			return '';
		}
		$tag = 'astroway_' . str_replace( '-', '_', substr( $block, strlen( 'astroway/' ) ) );
		return isset( self::index()[ $tag ] ) ? $tag : '';
	}

	/**
	 * Register every generated block. Editor and REST only.
	 *
	 * Registered from the registry rather than from a block.json per endpoint:
	 * seven hundred JSON files would be seven hundred file reads, four times the
	 * cost of building the same thing in memory, and 700 KB in the zip.
	 */
	public static function register_blocks(): void {
		$for_editor = [];
		foreach ( self::index() as $tag => $family ) {
			$block = self::block_name( $tag );
			if ( \WP_Block_Type_Registry::get_instance()->is_registered( $block ) ) {
				continue;
			}
			$endpoint = self::endpoint( $tag );
			if ( null === $endpoint ) {
				continue;
			}
			$attributes = [
				'lang' => [
					'type'    => 'string',
					'default' => '',
				],
			];
			$fields     = [];
			foreach ( $endpoint[2] as $spec ) {
				$attributes[ $spec[ self::A_ATTR ] ] = [
					'type'    => 'string',
					'default' => '',
				];
				$fields[]                            = [
					$spec[ self::A_ATTR ],
					(bool) ( $spec[ self::A_FLAGS ] & self::FLAG_REQUIRED ),
				];
			}
			register_block_type(
				$block,
				[
					'api_version'     => 3,
					'title'           => self::title( $tag ),
					'category'        => 'astroway-generated',
					'attributes'      => $attributes,
					'render_callback' => static function ( $atts ) use ( $tag ) {
						return self::render( (array) $atts, '', $tag );
					},
				]
			);
			$for_editor[] = [ $block, self::title( $tag ), $fields ];
		}

		self::hand_to_editor( $for_editor );
	}

	/**
	 * Hand the block list to the editor script.
	 *
	 * A block registered in PHP alone shows up in the editor as "missing": the
	 * canvas needs a JavaScript registration too. Rather than seven hundred
	 * generated JS files, the list is inlined and one loop in
	 * astroway-blocks-editor.js registers them, sharing the same edit component
	 * as the hand-built blocks.
	 *
	 * @param array $blocks Triples of [block name, title, [[attr, required], ...]].
	 */
	private static function hand_to_editor( array $blocks ): void {
		if ( empty( $blocks ) || ! function_exists( 'wp_add_inline_script' ) ) {
			return;
		}
		wp_add_inline_script(
			'astroway-blocks-editor',
			'window.astrowayGeneratedBlocks = ' . wp_json_encode( $blocks ) . ';',
			'before'
		);
	}

	/**
	 * Render a generated block without it ever being registered.
	 *
	 * @param string|null $pre    Short-circuit value from an earlier filter.
	 * @param array       $parsed Parsed block.
	 * @return string|null
	 */
	public static function render_block_on_demand( $pre, $parsed ) {
		if ( null !== $pre ) {
			return $pre;
		}
		$name = is_array( $parsed ) ? (string) ( $parsed['blockName'] ?? '' ) : '';
		if ( '' === $name || \WP_Block_Type_Registry::get_instance()->is_registered( $name ) ) {
			return $pre;
		}
		$tag = self::tag_for_block( $name );
		if ( '' === $tag ) {
			return $pre;
		}
		return self::render( (array) ( $parsed['attrs'] ?? [] ), '', $tag );
	}

	/**
	 * Human title for a tag, from the reference file.
	 *
	 * That file holds the prose and is only read here and on the admin
	 * reference page, so a front-end request never loads it.
	 */
	public static function title( string $tag ): string {
		$family = self::index()[ $tag ] ?? '';
		if ( '' === $family ) {
			return $tag;
		}
		if ( ! isset( self::$reference[ $family ] ) ) {
			$file                       = ASTROWAY_WP_PLUGIN_DIR . 'includes/generated/reference/' . $family . '.php';
			$data                       = is_readable( $file ) ? include $file : [];
			self::$reference[ $family ] = is_array( $data ) ? $data : [];
		}
		$title = self::$reference[ $family ]['endpoints'][ $tag ]['title'] ?? '';
		return '' !== $title ? (string) $title : $tag;
	}

	/** Reference data per family, loaded only where prose is shown. */
	private static array $reference = [];

	/**
	 * Everything the admin reference page needs, grouped by family.
	 *
	 * Reads every reference file, so it is only ever called from that page. The
	 * example is built here rather than stored: it is derived from the required
	 * attributes, and storing it would be a second copy to drift.
	 *
	 * @return array<string, array{label: string, endpoints: array}>
	 */
	public static function reference_by_family(): array {
		$out = [];
		foreach ( self::index() as $tag => $family ) {
			$endpoint = self::endpoint( $tag );
			if ( null === $endpoint ) {
				continue;
			}
			self::title( $tag ); // loads the family's reference file
			$meta = self::$reference[ $family ]['endpoints'][ $tag ] ?? [];
			if ( ! isset( $out[ $family ] ) ) {
				$out[ $family ] = [
					'label'     => (string) ( self::$reference[ $family ]['label'] ?? $family ),
					'endpoints' => [],
				];
			}
			$out[ $family ]['endpoints'][ $tag ] = [
				'title'       => (string) ( $meta['title'] ?? $tag ),
				'description' => (string) ( $meta['description'] ?? '' ),
				'method'      => $endpoint[1],
				'path'        => $endpoint[0],
				'attrs'       => $endpoint[2],
				'example'     => self::example( $tag, $endpoint[2] ),
			];
		}
		ksort( $out );
		return $out;
	}

	/** A copy-pasteable shortcode with its required attributes filled in. */
	public static function example( string $tag, array $attrs ): string {
		$samples = [
			'date'            => '1990-05-15',
			'time'            => '14:30:00',
			'latitude'        => '50.45',
			'longitude'       => '30.52',
			'timezone_offset' => '3',
			'timezone'        => 'Europe/Kyiv',
			'name'            => 'Anna',
			'sign'            => 'leo',
			'gender'          => 'female',
			'number'          => '3',
			'year'            => '2026',
			'month'           => '8',
			'day'             => '15',
			'lang'            => 'en',
		];
		$parts   = [ $tag ];
		foreach ( $attrs as $spec ) {
			if ( ! ( $spec[ self::A_FLAGS ] & self::FLAG_REQUIRED ) ) {
				continue;
			}
			$name    = $spec[ self::A_ATTR ];
			$value   = $samples[ $name ] ?? ( 'n' === $spec[ self::A_TYPE ] ? '1' : 'value' );
			$parts[] = $name . '="' . $value . '"';
		}
		return '[' . implode( ' ', $parts ) . ']';
	}

	/** Tag => family slug. */
	public static function index(): array {
		if ( null === self::$index ) {
			$file        = ASTROWAY_WP_PLUGIN_DIR . 'includes/generated/index.php';
			$index       = is_readable( $file ) ? include $file : [];
			self::$index = is_array( $index ) ? $index : [];
		}
		return self::$index;
	}

	/** One endpoint: [path, method, attrs], or null when unknown. */
	public static function endpoint( string $tag ): ?array {
		$family = self::index()[ $tag ] ?? null;
		if ( null === $family ) {
			return null;
		}
		if ( ! isset( self::$families[ $family ] ) ) {
			$file                      = ASTROWAY_WP_PLUGIN_DIR . 'includes/generated/families/' . $family . '.php';
			$data                      = is_readable( $file ) ? include $file : [];
			self::$families[ $family ] = is_array( $data ) ? $data : [];
		}
		return self::$families[ $family ][ $tag ] ?? null;
	}

	public static function render( $atts, $content = '', $tag = '' ): string {
		$endpoint = self::endpoint( (string) $tag );
		if ( null === $endpoint ) {
			return '';
		}

		// Every generated endpoint sits behind the key. Saying so to an editor
		// beats rendering an empty box for a visitor, which is the failure mode
		// this plugin spent 1.0.0 removing.
		$client = new ApiClient();
		if ( ! $client->has_key() ) {
			return Render::admin_note(
				sprintf(
					/* translators: %s = shortcode tag */
					__( '%s needs an API key: paste one in AstroWay, API Key. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']'
				)
			);
		}

		list( $path, $method, $attr_spec ) = $endpoint;

		$defaults = [];
		foreach ( $attr_spec as $spec ) {
			$defaults[ $spec[ self::A_ATTR ] ] = '';
		}
		$defaults['lang'] = '';
		$given            = shortcode_atts( $defaults, is_array( $atts ) ? $atts : [], (string) $tag );

		$missing = self::missing_required( $attr_spec, $given );
		if ( ! empty( $missing ) ) {
			return Render::admin_note(
				sprintf(
					/* translators: 1: shortcode tag, 2: comma-separated attribute names */
					__( '%1$s is missing required attributes: %2$s. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']',
					implode( ', ', $missing )
				)
			);
		}

		$request = self::build_request( $path, $attr_spec, $given );
		$data    = self::fetch( $request['path'], $method, $request['params'] );
		if ( null === $data ) {
			return Render::admin_note(
				sprintf(
					/* translators: %s = shortcode tag */
					__( '%s could not be loaded just now. Only administrators see this note.', 'astroway' ),
					'[' . $tag . ']'
				)
			);
		}

		return Render::generic( (string) $tag, $data, Plugin::resolve_lang( (string) $given['lang'] ) );
	}

	/** Required attributes the author left empty, by attribute name. */
	private static function missing_required( array $attr_spec, array $given ): array {
		$missing = [];
		foreach ( $attr_spec as $spec ) {
			$name = $spec[ self::A_ATTR ];
			if ( ( $spec[ self::A_FLAGS ] & self::FLAG_REQUIRED ) && '' === trim( (string) ( $given[ $name ] ?? '' ) ) ) {
				$missing[] = $name;
			}
		}
		return $missing;
	}

	/**
	 * Path with its {placeholders} filled, and the params for query or body.
	 *
	 * Values are cast to the type the spec declares. That matters for more than
	 * tidiness: latitude sent as the string "50.45" is a different request from
	 * the number 50.45, and the api answers 400 for one of them.
	 */
	private static function build_request( string $path, array $attr_spec, array $given ): array {
		$params = [];
		foreach ( $attr_spec as $spec ) {
			$name  = $spec[ self::A_ATTR ];
			$value = trim( (string) ( $given[ $name ] ?? '' ) );
			if ( '' === $value ) {
				continue;
			}
			$field = '' !== $spec[ self::A_FIELD ] ? $spec[ self::A_FIELD ] : $name;

			if ( $spec[ self::A_FLAGS ] & self::FLAG_PATH ) {
				$path = str_replace( '{' . $field . '}', rawurlencode( $value ), $path );
				continue;
			}

			$params[ $field ] = self::cast( $value, $spec[ self::A_TYPE ] );
		}

		// Anything left unfilled would reach the api as a literal {slug}.
		$path = (string) preg_replace( '/\{[^}]+\}/', '', $path );

		return [
			'path'   => rtrim( $path, '/' ),
			'params' => $params,
		];
	}

	/**
	 * @param string $value Raw attribute value.
	 * @param string $type  s string, n number, b boolean, j JSON.
	 * @return mixed
	 */
	private static function cast( string $value, string $type ) {
		switch ( $type ) {
			case 'n':
				return is_numeric( $value ) ? ( 0 + $value ) : $value;
			case 'b':
				return in_array( strtolower( $value ), [ '1', 'true', 'yes', 'on' ], true );
			case 'j':
				$decoded = json_decode( $value, true );
				return null === $decoded ? $value : $decoded;
			default:
				return $value;
		}
	}

	/**
	 * Cached answer, or null.
	 *
	 * Cached the way PublicData caches: by what the answer depends on rather
	 * than for a flat hour, and a failure remembered for a minute so an
	 * exhausted quota is not turned into a stampede by the next page view.
	 */
	private static function fetch( string $path, string $method, array $params ): ?array {
		return ( new ApiClient() )->cached_call(
			$method,
			$path,
			$params,
			PublicData::ttl_for( self::freshness( $path, $params ) ),
			'gen_'
		);
	}

	/**
	 * How long the answer stays true, from what was asked rather than from a
	 * table: a chart for a birth moment never changes, a reference list changes
	 * about never, and anything anchored to today changes at midnight.
	 */
	private static function freshness( string $path, array $params ): string {
		if ( isset( $params['date'] ) || isset( $params['datetime'] ) ) {
			return 'static';
		}
		if ( false !== strpos( $path, '/reference/' ) ) {
			return 'static';
		}
		return 'day';
	}
}
