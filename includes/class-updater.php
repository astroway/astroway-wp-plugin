<?php
/**
 * Channel B custom updater — wires plugin-update-checker against
 * https://astroway.info/wp-plugin/update.json. Falls back gracefully to
 * wp.org Channel A if PUC is absent or the server endpoint is unreachable.
 *
 * v0.5.3+.
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class Updater {

	private const METADATA_URL = 'https://astroway.info/wp-plugin/update.json';

	public static function boot(): void {
		if ( ! class_exists( PucFactory::class ) ) {
			return; // PUC not loaded — wp.org Channel A continues to work.
		}

		$puc = PucFactory::buildUpdateChecker(
			self::METADATA_URL,
			ASTROWAY_WP_PLUGIN_FILE,
			'astroway'
		);

		// Inject saved API key as ?key= on every update check request.
		$puc->addQueryArgFilter(
			static function ( $args ) {
				$opts = (array) get_option( Admin::OPTION_KEY, [] );
				$key  = isset( $opts['api_key'] ) ? trim( (string) $opts['api_key'] ) : '';
				if ( '' !== $key ) {
					$args['key'] = $key;
				}
				return $args;
			}
		);

		// Force fresh response from astroway.info (defeats any transit cache).
		$puc->addHttpRequestArgFilter(
			static function ( $args ) {
				$args['headers']                  = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : [];
				$args['headers']['Cache-Control'] = 'no-cache';
				$args['headers']['Pragma']        = 'no-cache';
				return $args;
			}
		);
	}

	/**
	 * Snapshot of Channel B state for admin display.
	 *
	 * @return array{active:bool, channel:string, endpoint:string, has_key:bool, last_check:?int}
	 */
	public static function get_status(): array {
		$active     = class_exists( PucFactory::class );
		$opts       = (array) get_option( Admin::OPTION_KEY, [] );
		$key        = isset( $opts['api_key'] ) ? trim( (string) $opts['api_key'] ) : '';
		$has_key    = '' !== $key;
		$channel    = $has_key && $active ? 'B' : 'A';
		$last_check = null;
		// PUC v5 stores last check time in `external_updates-<slug>` site option.
		$puc_option = get_site_option( 'external_updates-astroway' );
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PUC third-party property name.
		if ( is_object( $puc_option ) && isset( $puc_option->lastCheck ) ) {
			$last_check = (int) $puc_option->lastCheck;
		}
		// phpcs:enable
		return [
			'active'     => $active,
			'channel'    => $channel,
			'endpoint'   => 'https://astroway.info/wp-plugin/update.json',
			'has_key'    => $has_key,
			'last_check' => $last_check,
		];
	}
}
