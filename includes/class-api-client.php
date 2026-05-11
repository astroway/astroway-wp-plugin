<?php
namespace AstroWay\WPPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApiClient {

	private string $api_key;
	private string $base;

	public function __construct( ?string $api_key = null ) {
		if ( null === $api_key ) {
			$opts    = (array) get_option( Admin::OPTION_KEY, [] );
			$api_key = (string) ( $opts['api_key'] ?? '' );
		}
		$this->api_key = trim( $api_key );
		$this->base    = rtrim( ASTROWAY_API_BASE, '/' );
	}

	public function has_key(): bool {
		return '' !== $this->api_key && 0 === strpos( $this->api_key, 'aw_' );
	}

	public function ping_health(): array {
		return $this->get( '/health' );
	}

	public function get_keys_me( bool $force_fresh = false ): array {
		$cache_key = 'keys_me_' . md5( $this->api_key );
		if ( ! $force_fresh ) {
			$cached = Cache::get( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = $this->get( '/auth/keys/me' );

		// Block A not yet shipped — fall back to existing /v1/keys/usage
		if ( 404 === ( $response['status'] ?? 0 ) ) {
			$response = $this->fallback_keys_usage();
		}

		if ( 200 === ( $response['status'] ?? 0 ) ) {
			Cache::set( $cache_key, $response, Cache::TTL_KEYS_ME );
		}
		return $response;
	}

	private function fallback_keys_usage(): array {
		$resp = $this->get( '/keys/usage' );
		if ( 200 !== ( $resp['status'] ?? 0 ) ) {
			return $resp;
		}
		$usage             = $resp['data']['data'] ?? [];
		$resp['fallback']  = true;
		$resp['data']     = [
			'ok'   => true,
			'data' => [
				'plan'                      => $usage['plan'] ?? null,
				'rate_limit_per_min'        => $usage['rateLimit'] ?? null,
				'credits_used_this_period'  => $usage['usage']['month'] ?? null,
				'credits_total_this_period' => null,
				'credits_remaining'         => null,
				'period_end'                => null,
				'domain'                    => null,
				'status'                    => null,
			],
		];
		return $resp;
	}

	private function get( string $path, array $params = [] ): array {
		$url  = $this->base . $path;
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 5,
				'headers' => $this->headers(),
			]
		);
		return $this->normalize( $response );
	}

	private function headers(): array {
		$headers = [
			'Accept'              => 'application/json',
			'X-AstroWay-Site-URL' => home_url(),
		];
		if ( $this->has_key() ) {
			$headers['X-Api-Key'] = $this->api_key;
		}
		return $headers;
	}

	private function normalize( $response ): array {
		if ( is_wp_error( $response ) ) {
			return [
				'status' => 0,
				'error'  => $response->get_error_message(),
			];
		}
		$status  = (int) wp_remote_retrieve_response_code( $response );
		$body    = (string) wp_remote_retrieve_body( $response );
		$headers = wp_remote_retrieve_headers( $response );

		return [
			'status'      => $status,
			'data'        => json_decode( $body, true ),
			'retry_after' => 429 === $status ? (int) ( $headers['retry-after'] ?? 0 ) : null,
			'rate_limit'  => [
				'limit'     => (int) ( $headers['x-ratelimit-limit'] ?? 0 ),
				'remaining' => (int) ( $headers['x-ratelimit-remaining'] ?? 0 ),
			],
			'credits'     => [
				'used'      => (int) ( $headers['x-credits-used'] ?? 0 ),
				'remaining' => (int) ( $headers['x-credits-remaining'] ?? 0 ),
			],
		];
	}
}
