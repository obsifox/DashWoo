<?php
/**
 * WordPress environment adapter: PHP, WP core, uploads, REST, cron.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters;

use DashWoo\Compatibility\Abstract_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Environment adapter.
 */
class WordPress_Adapter extends Abstract_Adapter {  // Not final: third parties may extend a check set.

	const MIN_PHP = '8.0';
	const MIN_WP  = '6.4';

	/**
	 * Adapter id.
	 *
	 * @return string
	 */
	public function id() {
		return 'wordpress';
	}

	/**
	 * Adapter label.
	 *
	 * @return string
	 */
	public function label() {
		return 'WordPress';
	}

	/**
	 * Always active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return true;
	}

	/**
	 * WP core version.
	 *
	 * @return string
	 */
	public function version() {
		return defined( 'ABSPATH' ) ? (string) get_bloginfo( 'version' ) : '';
	}

	/**
	 * Tested up to.
	 *
	 * @return string
	 */
	public function tested_up_to() {
		return '6.8';
	}

	/**
	 * PHP version.
	 *
	 * @return string
	 */
	/**
	 * Minimum version for a check: the setting wins, the constant is the floor.
	 *
	 * @param string $key Settings key inside the compatibility section.
	 * @return string
	 */
	protected function minimum( $key ) {
		$fallback = ( 'min_php' === $key ) ? self::MIN_PHP : self::MIN_WP;

		if ( ! function_exists( 'dashwoo_get_setting' ) ) {
			return $fallback;
		}

		$value = dashwoo_get_setting( 'compatibility.' . $key, $fallback );

		return '' === trim( (string) $value ) ? $fallback : (string) $value;
	}

	public function php_version() {
		return PHP_VERSION;
	}

	/**
	 * Collect checks.
	 *
	 * @return void
	 */
	protected function inspect() {
		$php = PHP_VERSION;
		$this->check(
			'php',
			$this->meets( $php, $this->minimum( 'min_php' ) ) ? self::STATUS_OK : self::STATUS_ERROR,
			sprintf( __('PHP %s (minimum %s)', 'dashwoo'), $php, $this->minimum( 'min_php' ) ),
			array(
				'current'  => $php,
				'required' => self::MIN_PHP,
			)
		);
		$this->capability( 'php', $this->meets( $php, self::MIN_PHP ) );

		$wp = $this->version();
		$this->check(
			'wp_core',
			$this->meets( $wp, $this->minimum( 'min_wp' ) ) ? self::STATUS_OK : self::STATUS_ERROR,
			sprintf( __('WordPress %s (minimum %s)', 'dashwoo'), $wp ? $wp : __('not detected', 'dashwoo'), $this->minimum( 'min_wp' ) ),
			array(
				'current'  => $wp,
				'required' => self::MIN_WP,
			)
		);
		$this->capability( 'wp_core', $this->meets( $wp, self::MIN_WP ) );

		$upload = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array();
		$writable = ! empty( $upload['basedir'] ) && is_writable( $upload['basedir'] );
		$this->check(
			'uploads',
			$writable ? self::STATUS_OK : self::STATUS_ERROR,
			$writable ? __('uploads/ is writable', 'dashwoo') : __('uploads/ is NOT writable - assets cannot be stored', 'dashwoo'),
			array( 'basedir' => isset( $upload['basedir'] ) ? $upload['basedir'] : '' )
		);
		$this->capability( 'uploads', $writable );

		$rest = function_exists( 'rest_url' );
		$this->check(
			'rest',
			$rest ? self::STATUS_OK : self::STATUS_ERROR,
			$rest ? __('REST API available', 'dashwoo') : __('REST API unavailable', 'dashwoo')
		);
		$this->capability( 'rest', $rest );

		// Nginx ignores .htaccess, so the "no PHP in uploads" guard must be verified.
		$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) $_SERVER['SERVER_SOFTWARE'] : '';
		$nginx  = false !== stripos( $server, 'nginx' );
		$this->check(
			'htaccess',
			$nginx ? self::STATUS_WARNING : self::STATUS_OK,
			$nginx
				? __('Nginx detected: .htaccess rules are ignored, deny PHP execution manually', 'dashwoo')
				: __('Apache detected: .htaccess guard applies to uploads/dashwoo/', 'dashwoo'),
			array( 'server' => $server )
		);
		$this->capability( 'htaccess_guard', ! $nginx );

		$this->check( 'multisite', self::STATUS_NA, is_multisite() ? __('Multisite: per-site settings', 'dashwoo') : __('Single site', 'dashwoo') );
		$this->capability( 'multisite', (bool) is_multisite() );
	}
}
