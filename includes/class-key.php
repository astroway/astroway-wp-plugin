<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where the API key comes from, and how it is kept.
 *
 * Two sources. `ASTROWAY_API_KEY` in wp-config.php wins when it is set: a key
 * there never reaches the database, its backups or an export, and a site that
 * deploys its config from a repository can rotate it there. Otherwise the key
 * lives in the settings option, sealed with a secret derived from the site's
 * salts, so a leaked database dump alone does not hand it over.
 *
 * The seal is authenticated. When the salts change (a security plugin
 * regenerating them, a clone with a fresh wp-config.php) the old value cannot
 * be opened and the key reads as missing, with an admin notice saying why. A
 * value that fails the check is never passed on, so the api is not sent bytes
 * that only look like a key.
 *
 * @since 1.5.6
 */
class Key {

	public const CONSTANT = 'ASTROWAY_API_KEY';

	/** Marks a sealed value; the digit is the format, so a future one can coexist. */
	private const SEALED = 'awenc1:';

	/** The key to use: the constant when set, the stored one otherwise, '' when neither can be had. */
	public static function current(): string {
		if ( self::from_constant() ) {
			return trim( (string) constant( self::CONSTANT ) );
		}
		$open = self::open( self::stored() );
		return null === $open ? '' : $open;
	}

	/** Whether wp-config.php supplies the key, which makes the settings field read-only. */
	public static function from_constant(): bool {
		return defined( self::CONSTANT ) && '' !== trim( (string) constant( self::CONSTANT ) );
	}

	/** A sealed key that no longer opens, because the salts it was sealed with are gone. */
	public static function is_lost(): bool {
		return ! self::from_constant() && null === self::open( self::stored() );
	}

	/** The raw option value: sealed, a plaintext key from before 1.5.6, or ''. */
	public static function stored(): string {
		$opts = (array) get_option( Admin::OPTION_KEY, [] );
		return trim( (string) ( $opts['api_key'] ?? '' ) );
	}

	public static function is_sealed( string $value ): bool {
		return 0 === strpos( $value, self::SEALED );
	}

	/**
	 * Enough of a key to recognise it and not enough to use it:
	 * aw_live_4abe…636e. Short keys show less, so the mask never adds up to
	 * the whole key.
	 */
	public static function mask( string $key ): string {
		$key = trim( $key );
		if ( '' === $key ) {
			return '';
		}
		$prefix = '';
		$body   = $key;
		if ( preg_match( '/^(aw_[a-z]+_)(.+)$/', $key, $m ) ) {
			$prefix = $m[1];
			$body   = $m[2];
		}
		$show = min( 4, intdiv( strlen( $body ), 4 ) );
		if ( $show < 1 ) {
			return $prefix . '…';
		}
		return $prefix . substr( $body, 0, $show ) . '…' . substr( $body, -$show );
	}

	/**
	 * Seal a key for the option. Returns it unchanged when the site has no
	 * sodium at all, which WordPress has bundled a fallback for since 5.2, so
	 * in practice only a broken install would store it in the clear.
	 */
	public static function seal( string $key ): string {
		$key = trim( $key );
		if ( '' === $key || self::is_sealed( $key ) || ! function_exists( 'sodium_crypto_secretbox' ) ) {
			return $key;
		}
		try {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$box   = sodium_crypto_secretbox( $key, $nonce, self::secret() );
		} catch ( \Exception $e ) {
			return $key;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary ciphertext stored in a text option.
		return self::SEALED . base64_encode( $nonce . $box );
	}

	/**
	 * The key inside a stored value, or null when it is sealed and does not
	 * open. A plaintext value from before 1.5.6 is returned as it is, which is
	 * what lets an upgrade keep working before the migration reseals it.
	 */
	public static function open( string $stored ): ?string {
		$stored = trim( $stored );
		if ( ! self::is_sealed( $stored ) ) {
			return $stored;
		}
		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return null;
		}
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- reverses seal().
		$raw = base64_decode( substr( $stored, strlen( self::SEALED ) ), true );
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
			return null;
		}
		try {
			$key = sodium_crypto_secretbox_open(
				substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
				self::secret()
			);
		} catch ( \Exception $e ) {
			return null;
		}
		return ( false === $key || 0 !== strpos( $key, 'aw_' ) ) ? null : $key;
	}

	/**
	 * Reseal a plaintext key left by an older version. Runs on admin_init, so
	 * the first visit to the dashboard after the upgrade takes it out of the
	 * clear, and a front-end request never writes to the options table.
	 */
	public static function migrate(): void {
		$stored = self::stored();
		if ( '' === $stored || self::is_sealed( $stored ) || 0 !== strpos( $stored, 'aw_' ) ) {
			return;
		}
		$sealed = self::seal( $stored );
		if ( $sealed === $stored ) {
			return;
		}
		$opts            = (array) get_option( Admin::OPTION_KEY, [] );
		$opts['api_key'] = $sealed;
		update_option( Admin::OPTION_KEY, $opts );
	}

	/** 32 bytes from the site's auth salt, bound to this purpose so it is not the salt itself. */
	private static function secret(): string {
		return hash_hmac( 'sha256', 'astroway-api-key', wp_salt( 'auth' ), true );
	}
}
