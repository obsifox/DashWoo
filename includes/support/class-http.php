<?php
/**
 * HTTP client with an explicit host allow-list.
 *
 * Architectural rule: DashWoo never talks to a third-party host at runtime.
 * Remote requests only happen during an explicit admin import action (fonts / icons),
 * and only to the hosts listed below.
 *
 * @package DashWoo
 */

namespace DashWoo\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Allow-listed HTTP wrapper.
 */
final class Http {

	/**
	 * Hosts DashWoo is allowed to contact.
	 */
	const ALLOWED_HOSTS = array(
		'fonts.googleapis.com',
		'fonts.gstatic.com',
		'raw.githubusercontent.com',
		'github.com',
	);

	/**
	 * Default timeout in seconds.
	 */
	const TIMEOUT = 20;

	/**
	 * A modern user agent: makes Google serve woff2 instead of ttf.
	 *
	 * @return string
	 */
	public static function user_agent() {
		return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
	}

	/**
	 * Is this URL allowed to be requested?
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_allowed_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}
		if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] );

		/**
		 * Filter the host allow-list.
		 *
		 * @param array<int,string> $hosts Allowed hosts.
		 * @param string            $url   Requested URL.
		 */
		$hosts = apply_filters( 'dashwoo_http_allowed_hosts', self::ALLOWED_HOSTS, $url );

		return in_array( $host, $hosts, true );
	}

	/**
	 * GET a remote URL.
	 *
	 * @param string               $url  URL.
	 * @param array<string,mixed>  $args Extra wp_remote_* args.
	 * @return array{code:int,body:string,headers:array<string,mixed>}|\WP_Error
	 */
	public static function get( $url, $args = array() ) {
		return self::request( 'GET', $url, $args );
	}

	/**
	 * POST a remote URL.
	 *
	 * @param string              $url  URL.
	 * @param array<string,mixed> $args Extra wp_remote_* args.
	 * @return array{code:int,body:string,headers:array<string,mixed>}|\WP_Error
	 */
	public static function post( $url, $args = array() ) {
		return self::request( 'POST', $url, $args );
	}

	/**
	 * Execute the request and normalise the response.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $url    URL.
	 * @param array<string,mixed> $args   Extra args.
	 * @return array{code:int,body:string,headers:array<string,mixed>}|\WP_Error
	 */
	public static function request( $method, $url, $args = array() ) {
		if ( ! self::is_allowed_url( $url ) ) {
			return new \WP_Error(
				'dashwoo_http_host_not_allowed',
				sprintf( __('DashWoo blocked a request to a non allow-listed host: %s', 'dashwoo'), $url )
			);
		}

		$defaults = array(
			'timeout'     => self::TIMEOUT,
			'redirection' => 3,
			'sslverify'   => true,
			'user-agent'  => self::user_agent(),
			'headers'     => array(),
		);

		$args = array_merge( $defaults, $args );

		$response = ( 'POST' === $method )
			? wp_remote_post( $url, $args )
			: wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'code'    => (int) wp_remote_retrieve_response_code( $response ),
			'body'    => (string) wp_remote_retrieve_body( $response ),
			'headers' => (array) wp_remote_retrieve_headers( $response ),
		);
	}

	/**
	 * Download a URL into a file after validating size and magic bytes.
	 *
	 * @param string $url       Source URL.
	 * @param string $dest      Destination absolute path.
	 * @param int    $max_bytes Hard size cap.
	 * @param array<int,string> $magic Allowed magic byte sequences (binary safe).
	 * @return array{path:string,bytes:int,sha256:string,magic:string}|\WP_Error
	 */
	public static function download( $url, $dest, $max_bytes = 5242880, $magic = array( 'wOF2', 'wOFF' ) ) {
		$response = self::get( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( 200 !== (int) $response['code'] ) {
			return new \WP_Error(
				'dashwoo_http_status',
				sprintf( __('Unexpected HTTP status %d for %s', 'dashwoo'), (int) $response['code'], $url )
			);
		}

		$body = (string) $response['body'];
		$size = strlen( $body );

		if ( 0 === $size ) {
			return new \WP_Error( 'dashwoo_http_empty', sprintf( __('Empty response body for %s', 'dashwoo'), $url ) );
		}
		if ( $size > (int) $max_bytes ) {
			return new \WP_Error(
				'dashwoo_http_too_large',
				sprintf( __('File is larger than the %d byte limit.', 'dashwoo'), (int) $max_bytes )
			);
		}

		$found = '';
		foreach ( $magic as $candidate ) {
			if ( 0 === strncmp( $body, $candidate, strlen( $candidate ) ) ) {
				$found = $candidate;
				break;
			}
		}
		if ( '' === $found ) {
			return new \WP_Error(
				'dashwoo_http_magic_bytes',
				sprintf( __('Signature check failed for %s (expected %s).', 'dashwoo'), $url, implode( '|', $magic ) )
			);
		}

		if ( ! Filesystem::put( $dest, $body ) ) {
			return new \WP_Error( 'dashwoo_http_write', sprintf( __('Cannot write %s', 'dashwoo'), $dest ) );
		}

		return array(
			'path'   => $dest,
			'bytes'  => $size,
			'sha256' => hash( 'sha256', $body ),
			'magic'  => $found,
		);
	}
}
