<?php
/**
 * Tier resolution — single source of truth for the user's plan level.
 *
 * Reads /v1/auth/keys/me via ApiClient (30 min transient cache). Returns
 * a normalized tier string. AddonAPI::current_tier() delegates here.
 *
 * @since 0.7.0
 */

namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tier {

	/**
	 * Tiers a paying customer can be on. Used by Channel B updater + feature gates.
	 */
	public const PAID = [ 'indie', 'starter', 'pro', 'business' ];

	/**
	 * Recognised tier values returned by api `/v1/auth/keys/me`.
	 */
	public const ALL = [ 'anonymous', 'free', 'indie', 'starter', 'pro', 'business', 'internal' ];

	/**
	 * Resolve the current site's tier.
	 *
	 * Returns 'anonymous' if no key configured, 'free' if key invalid/expired,
	 * otherwise whatever plan the api returns.
	 */
	public static function current(): string {
		$client = new ApiClient();
		if ( ! $client->has_key() ) {
			return 'anonymous';
		}
		$resp = $client->get_keys_me();
		if ( 200 !== ( $resp['status'] ?? 0 ) ) {
			return 'free';
		}
		$plan = $resp['data']['data']['plan'] ?? null;
		return is_string( $plan ) && '' !== $plan ? $plan : 'free';
	}
}
