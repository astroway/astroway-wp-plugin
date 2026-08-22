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

	/**
	 * The configured key when it looks like one, '' otherwise.
	 *
	 * PublicData needs the value itself, not a yes/no, and the rule for what
	 * counts as a key belongs in one place: the settings field takes free text
	 * and collects pasted URLs and dashboard labels as readily as keys.
	 */
	public function key(): string {
		return $this->has_key() ? $this->api_key : '';
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
		$usage            = $resp['data']['data'] ?? [];
		$resp['fallback'] = true;
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

	/**
	 * One call to any endpoint, for the generated shortcode registry.
	 *
	 * The registry knows 682 endpoints and needs no method of its own for each;
	 * what it needs is the transport, the headers and the error shape that the
	 * hand-written calls above already agree on.
	 *
	 * @since 1.2.0
	 *
	 * @param string $method GET or POST.
	 * @param string $path   Path under /v1, with a leading slash.
	 * @param array  $params Query for GET, JSON body for POST.
	 */
	public function call( string $method, string $path, array $params = [] ): array {
		if ( 'POST' !== strtoupper( $method ) ) {
			return $this->get( $path, $params );
		}
		$response = wp_remote_post(
			$this->base . $path,
			[
				'timeout' => 10,
				'headers' => $this->headers() + [ 'Content-Type' => 'application/json' ],
				'body'    => (string) wp_json_encode( $params ),
			]
		);
		return $this->normalize( $response );
	}

	/**
	 * A call whose answer is remembered, and whose failure is remembered too.
	 *
	 * The second half is the half worth having. Without it an exhausted quota
	 * turns every page render into another request, which is exactly what keeps
	 * the quota exhausted instead of letting it recover.
	 *
	 * @since 1.3.0
	 *
	 * @param string $method GET or POST.
	 * @param string $path   Path under /v1, with a leading slash.
	 * @param array  $params Query for GET, JSON body for POST.
	 * @param int    $ttl    Seconds the answer stays true.
	 * @param string $prefix Cache key prefix, so two callers asking the same
	 *                       question with different lifetimes do not share one entry.
	 * @return array|null The payload under `data`, or null when the call failed.
	 */
	public function cached_call( string $method, string $path, array $params, int $ttl, string $prefix = 'api_' ): ?array {
		$key    = $prefix . md5( $path . '|' . strtoupper( $method ) . '|' . (string) wp_json_encode( $params ) );
		$cached = Cache::get( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		if ( false !== Cache::get( $key . '_neg' ) ) {
			return null;
		}

		$response = $this->call( $method, $path, $params );
		$payload  = $response['data']['data'] ?? null;
		if ( 200 !== (int) ( $response['status'] ?? 0 ) || ! is_array( $payload ) ) {
			Cache::set( $key . '_neg', 'fail', PublicData::NEGATIVE_TTL );
			return null;
		}

		Cache::set( $key, $payload, $ttl );
		return $payload;
	}

	private function get( string $path, array $params = [] ): array {
		$url = $this->base . $path;
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
