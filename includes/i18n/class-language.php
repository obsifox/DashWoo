<?php
/**
 * Language: English source strings, Persian (and any other locale) shipped as a
 * translation.
 *
 * Every user-facing string in DashWoo is written in English and wrapped in a
 * gettext call with the `dashwoo` text domain, so the plugin runs in English on
 * an English site and in Persian on a Persian one. The Persian catalogue lives in
 * `languages/dashwoo-fa_IR.po` / `.mo` (compiled) and the JavaScript catalogue in
 * `languages/dashwoo-fa_IR-<hash>.json`.
 *
 * The `general.language` setting decides what DashWoo itself speaks:
 *
 *   - `site`  (default) - whatever the WordPress install is set to, which means a
 *                         Persian WordPress shows DashWoo in Persian;
 *   - `en_US`           - force English, even on a Persian install;
 *   - `fa_IR`           - force Persian, even on an English install.
 *
 * Nothing here touches another plugin: the filters are scoped to the `dashwoo`
 * text domain (and to DashWoo's own script handles), so WooCommerce and the theme
 * keep following the site language.
 *
 * @package DashWoo\I18n
 */

namespace DashWoo\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * The language of the platform.
 */
class Language {

	/**
	 * Locale that means "whatever the site uses".
	 */
	public const SITE = 'site';

	/**
	 * Cached resolved locale.
	 *
	 * @var string|null
	 */
	private static $locale = null;

	/**
	 * Hook the locale filters and load the catalogue.
	 *
	 * @return void
	 */
	public static function boot() {
		// Checking the hooks (instead of a static flag) keeps boot() safe to call
		// twice - the test suite resets the hook collection between tests.
		if ( ! has_filter( 'plugin_locale', array( __CLASS__, 'filter_plugin_locale' ) ) ) {
			add_filter( 'plugin_locale', array( __CLASS__, 'filter_plugin_locale' ), 10, 2 );
		}

		if ( ! has_filter( 'load_script_translation_file', array( __CLASS__, 'filter_script_file' ) ) ) {
			add_filter( 'load_script_translation_file', array( __CLASS__, 'filter_script_file' ), 10, 3 );
		}

		if ( ! has_action( 'init', array( __CLASS__, 'load' ) ) ) {
			add_action( 'init', array( __CLASS__, 'load' ), 1 );
		}

		// A language change in the settings must be visible without a reload.
		if ( ! has_action( 'dashwoo_settings_changed', array( __CLASS__, 'flush' ) ) ) {
			add_action( 'dashwoo_settings_changed', array( __CLASS__, 'flush' ) );
		}
	}

	/**
	 * Forget everything cached in this process (tests and settings changes).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$locale = null;
	}

	/**
	 * Locales a shop can pick in the settings.
	 *
	 * @return array<string,string>
	 */
	public static function choices() {
		return array(
			self::SITE => __( 'Follow the site language', 'dashwoo' ),
			'en_US'    => __( 'English', 'dashwoo' ),
			'fa_IR'    => __( 'Persian', 'dashwoo' ),
		);
	}

	/**
	 * The saved choice.
	 *
	 * @return string
	 */
	public static function setting() {
		$value = (string) dashwoo_get_setting( 'general.language', self::SITE );

		return '' === $value ? self::SITE : $value;
	}

	/**
	 * The locale DashWoo renders in. Empty when it cannot be told.
	 *
	 * @return string
	 */
	public static function locale() {
		if ( null !== self::$locale ) {
			return self::$locale;
		}

		$setting = self::setting();

		if ( self::SITE === $setting ) {
			$locale = function_exists( 'determine_locale' ) ? determine_locale() : '';
			if ( '' === $locale && function_exists( 'get_locale' ) ) {
				$locale = get_locale();
			}
		} else {
			$locale = (string) apply_filters( 'dashwoo_locale', $setting );
		}

		self::$locale = (string) $locale;

		return self::$locale;
	}

	/**
	 * Forget the resolved locale (settings changed, tests).
	 *
	 * @return void
	 */
	public static function flush() {
		self::$locale = null;
	}

	/**
	 * Whether the platform is rendering in a right-to-left language.
	 *
	 * @return bool
	 */
	public static function is_rtl() {
		$locale = self::locale();

		if ( function_exists( 'is_rtl' ) && is_rtl() ) {
			return true;
		}

		return in_array( $locale, self::rtl_locales(), true );
	}

	/**
	 * Locales whose script runs right-to-left.
	 *
	 * @return array<int,string>
	 */
	public static function rtl_locales() {
		/**
		 * Filter the RTL locales the platform reacts to.
		 *
		 * @param array<int,string> $locales Locale codes.
		 */
		return (array) apply_filters(
			'dashwoo_rtl_locales',
			array( 'fa_IR', 'fa_AF', 'ar', 'ar_AR', 'he_IL', 'ur', 'ckb', 'ps', 'sd', 'ug_CN', 'yi' )
		);
	}

	/**
	 * The shipped catalogue file for a locale, when it exists.
	 *
	 * @param string $locale Locale code.
	 * @param string $suffix File suffix, `mo` or `po`.
	 * @return string Absolute path, empty when the file is not there.
	 */
	public static function catalogue( $locale, $suffix = 'mo' ) {
		if ( '' === $locale ) {
			return '';
		}

		$file = DASHWOO_DIR . 'languages/dashwoo-' . $locale . '.' . $suffix;

		return is_readable( $file ) ? $file : '';
	}

	/**
	 * Load the catalogue on `init`.
	 *
	 * A forced locale is loaded from the plugin folder itself; with `site` we let
	 * WordPress do the work (the plugin declares `Domain Path: /languages`, so the
	 * catalogue is picked up just-in-time as usual).
	 *
	 * @return void
	 */
	public static function load() {
		if ( self::SITE !== self::setting() ) {
			$locale = self::locale();
			$file   = self::catalogue( $locale );

			if ( '' !== $file && function_exists( 'load_textdomain' ) ) {
				if ( function_exists( 'unload_textdomain' ) ) {
					unload_textdomain( DASHWOO_TEXTDOMAIN );
				}
				load_textdomain( DASHWOO_TEXTDOMAIN, $file, $locale );
				return;
			}
		}

		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( DASHWOO_TEXTDOMAIN, false, dirname( DASHWOO_BASENAME ) . '/languages' );
		}
	}

	/**
	 * Keep the plugin locale in step with the choice above.
	 *
	 * @param string $locale Locale WordPress resolved.
	 * @param string $domain Text domain the locale is for.
	 * @return string
	 */
	public static function filter_plugin_locale( $locale, $domain = '' ) {
		if ( DASHWOO_TEXTDOMAIN !== $domain || self::SITE === self::setting() ) {
			return $locale;
		}

		return self::locale();
	}

	/**
	 * Point the JavaScript catalogue at the forced locale.
	 *
	 * WordPress builds the file name as `<domain>-<locale>-<hash>.json`; we only
	 * swap the locale part and only when the shop asked for one explicitly.
	 *
	 * @param string $file   Resolved JSON file.
	 * @param string $handle Script handle.
	 * @param string $domain Text domain.
	 * @return string
	 */
	public static function filter_script_file( $file, $handle = '', $domain = '' ) {
		unset( $handle );

		if ( DASHWOO_TEXTDOMAIN !== $domain || self::SITE === self::setting() ) {
			return $file;
		}

		$name = basename( (string) $file );
		$dash = strrpos( $name, '-' );

		if ( false === $dash ) {
			return $file;
		}

		$candidate = DASHWOO_DIR . 'languages/dashwoo-' . self::locale() . '-' . substr( $name, $dash + 1 );

		return is_readable( $candidate ) ? $candidate : $file;
	}

	/**
	 * The `.json` file WordPress looks for a script handle.
	 *
	 * WordPress hashes the script path relative to the plugin folder, which is
	 * what the generated files use as well.
	 *
	 * @param string $relative Script path relative to the plugin folder.
	 * @param string $locale   Locale code.
	 * @return string File name, not a path.
	 */
	public static function script_catalogue_name( $relative, $locale ) {
		return 'dashwoo-' . $locale . '-' . md5( $relative ) . '.json';
	}

	/**
	 * Status for the system screen.
	 *
	 * @return array<string,mixed>
	 */
	public static function status() {
		$locale = self::locale();
		$catalogues = array();

		foreach ( array( 'fa_IR' ) as $shipped ) {
			if ( '' !== self::catalogue( $shipped ) ) {
				$catalogues[] = $shipped;
			}
		}

		return array(
			'setting'    => self::setting(),
			'locale'     => $locale,
			'rtl'        => self::is_rtl(),
			'catalogues' => $catalogues,
			'json'       => count( (array) glob( DASHWOO_DIR . 'languages/dashwoo-' . $locale . '-*.json' ) ) > 0,
		);
	}
}
