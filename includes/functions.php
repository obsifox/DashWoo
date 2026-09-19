<?php
/**
 * Public template functions. Loaded eagerly by the plugin bootstrap.
 *
 * @package DashWoo
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'dashwoo_get_setting' ) ) {
	/**
	 * Read a setting by dot path.
	 *
	 * @param string $path    Dot path, e.g. "colors.primary".
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	function dashwoo_get_setting( $path, $default = null ) {
		return \DashWoo\Settings\Settings::instance()->get( $path, $default );
	}
}

if ( ! function_exists( 'dashwoo_is_on' ) ) {
	/**
	 * Boolean setting shortcut.
	 *
	 * @param string $path Dot path.
	 * @return bool
	 */
	function dashwoo_is_on( $path ) {
		return \DashWoo\Settings\Settings::instance()->is_on( $path );
	}
}

if ( ! function_exists( 'dashwoo_has_woocommerce' ) ) {
	/**
	 * Is WooCommerce present in this request?
	 *
	 * Every adapter asks this function instead of poking at `class_exists()` /
	 * `WC_VERSION` directly, so the answer is testable, filterable and identical
	 * everywhere in the platform (and a site that ships WooCommerce behind a
	 * custom loader can still tell DashWoo the truth).
	 *
	 * @return bool
	 */
	function dashwoo_has_woocommerce() {
		$active = class_exists( '\\WooCommerce' ) || defined( 'WC_VERSION' );

		/**
		 * Filter whether WooCommerce counts as active.
		 *
		 * @param bool $active Detected state.
		 */
		return (bool) apply_filters( 'dashwoo_has_woocommerce', $active );
	}
}

if ( ! function_exists( 'dashwoo_woocommerce_version' ) ) {
	/**
	 * WooCommerce version ('' when WooCommerce is not loaded).
	 *
	 * @return string
	 */
	function dashwoo_woocommerce_version() {
		$version = defined( 'WC_VERSION' ) ? (string) WC_VERSION : '';

		/**
		 * Filter the reported WooCommerce version.
		 *
		 * @param string $version Detected version.
		 */
		return (string) apply_filters( 'dashwoo_woocommerce_version', $version );
	}
}

if ( ! function_exists( 'dashwoo_mode' ) ) {
	/**
	 * Current render mode: "full" | "compatibility".
	 *
	 * @return string
	 */
	function dashwoo_mode() {
		return \DashWoo\Compatibility\Compatibility::instance()->mode();
	}
}

if ( ! function_exists( 'dashwoo_supports' ) ) {
	/**
	 * Capability check through the compatibility layer.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	function dashwoo_supports( $feature ) {
		return \DashWoo\Compatibility\Compatibility::instance()->supports( $feature );
	}
}

if ( ! function_exists( 'dashwoo_plugin' ) ) {
	/**
	 * Plugin container.
	 *
	 * @return \DashWoo\Plugin
	 */
	function dashwoo_plugin() {
		return \DashWoo\Plugin::instance();
	}
}

if ( ! function_exists( 'dashwoo_storage' ) ) {
	/**
	 * Asset storage helper.
	 *
	 * @return \DashWoo\Assets\Storage
	 */
	function dashwoo_storage() {
		return \DashWoo\Assets\Storage::instance();
	}
}

if ( ! function_exists( 'dashwoo_log' ) ) {
	/**
	 * Write a log row.
	 *
	 * @param string              $level   Level.
	 * @param string              $message Message.
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	function dashwoo_log( $level, $message, $context = array() ) {
		\DashWoo\Support\Logger::instance()->log( $level, $message, $context );
	}
}
