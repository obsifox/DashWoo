<?php
/**
 * Token compiler: settings -> CSS custom properties -> a versioned, local file.
 *
 * @package DashWoo
 */

namespace DashWoo\DesignSystem;

use DashWoo\Cache\Cache;
use DashWoo\Support\Filesystem;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Compiler.
 */
final class Compiler {

	const FILE_PREFIX = 'dw-tokens.';

	/**
	 * Singleton.
	 *
	 * @var Compiler|null
	 */
	private static $instance = null;

	/**
	 * Memoised compile result.
	 *
	 * @var array<string,mixed>|null
	 */
	private $compiled = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Compiler
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks: recompile whenever something relevant changes.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'dashwoo_settings_saved', array( $this, 'recompile' ) );
		add_action( 'dashwoo_settings_imported', array( $this, 'recompile' ) );
		add_action( 'dashwoo_settings_reset', array( $this, 'recompile' ) );
		add_action( 'dashwoo_font_changed', array( $this, 'recompile' ) );
	}

	/**
	 * Flatten a nested array into dashed keys (used for the Elementor Kit shape).
	 *
	 * @param array<string,mixed> $input  Nested array.
	 * @param string              $prefix Prefix.
	 * @return array<string,string>
	 */
	public static function flatten( array $input, $prefix = '' ) {
		$out = array();

		foreach ( $input as $key => $value ) {
			$key   = str_replace( '_', '-', (string) $key );
			$dash  = '' === $prefix ? $key : $prefix . '-' . $key;

			if ( is_array( $value ) ) {
				$out = array_merge( $out, self::flatten( $value, $dash ) );
				continue;
			}
			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}
			if ( is_scalar( $value ) ) {
				$out[ $dash ] = (string) $value;
			}
		}

		return $out;
	}

	/**
	 * Build `--dw-*` variables from the token map.
	 *
	 * @param array<string,string> $tokens Tokens (or null to resolve from settings).
	 * @return array<string,string>
	 */
	public function variables( $tokens = null ) {
		$tokens = null === $tokens ? Tokens::instance()->resolve() : (array) $tokens;
		$out    = array();

		foreach ( $tokens as $name => $value ) {
			$name = strtolower( preg_replace( '/[^A-Za-z0-9\-_]/', '-', (string) $name ) );
			$name = trim( str_replace( '_', '-', $name ), '-' );

			if ( '' === $name ) {
				continue;
			}

			$out[ Tokens::var_name( $name ) ] = $this->maybe_font_stack( $name, (string) $value );
		}

		ksort( $out );

		return $out;
	}

	/**
	 * Font tokens get a proper fallback stack so a missing family degrades gracefully.
	 *
	 * @param string $name  Token name.
	 * @param string $value Value.
	 * @return string
	 */
	private function maybe_font_stack( $name, $value ) {
		if ( ! in_array( $name, array( 'font-primary', 'font-heading', 'font-body', 'font-button' ), true ) ) {
			return $value;
		}

		$family = trim( $value, "\"'" );

		if ( '' === $family ) {
			return 'system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif';
		}
		if ( false !== strpos( $family, ',' ) ) {
			return $family;
		}

		return '"' . $family . '", system-ui, -apple-system, "Segoe UI", Tahoma, "Noto Sans Arabic", sans-serif';
	}

	/**
	 * CSS text for a variable map.
	 *
	 * @param array<string,string> $variables Variables.
	 * @param array<string,mixed>  $args      selector, rtl, responsive, minify.
	 * @return string
	 */
	public function css( array $variables, array $args = array() ) {
		$args = array_merge(
			array(
				'selector'   => ':root',
				'rtl'        => dashwoo_is_on( 'general.rtl' ) || \DashWoo\I18n\Language::is_rtl(),
				'responsive' => true,
				'minify'     => dashwoo_is_on( 'performance.minify' ),
			),
			$args
		);

		$declarations = array();
		foreach ( $variables as $name => $value ) {
			$declarations[] = $name . ':' . trim( (string) $value );
		}

		$separator = $args['minify'] ? ';' : ";\n  ";
		$body      = implode( $separator, $declarations );

		$css = sprintf(
			"%s {\n  %s;\n}\n",
			$args['selector'],
			$args['minify'] ? $body : str_replace( "\n  \n", "\n", $body )
		);

		if ( $args['rtl'] ) {
			// RTL flips direction only: every spacing rule uses logical properties.
			$css .= "[dir=\"rtl\"], body.rtl {\n  --dw-direction: rtl;\n}\n";
		}

		if ( $args['responsive'] ) {
			$tablet = (int) dashwoo_get_setting( 'typography.base_size_tablet', 15 );
			$mobile = (int) dashwoo_get_setting( 'typography.base_size_mobile', 14 );
			$lg     = (int) dashwoo_get_setting( 'elementor.tablet_breakpoint', 1024 );
			$md     = (int) dashwoo_get_setting( 'elementor.mobile_breakpoint', 767 );

			$css .= sprintf(
				"@media (max-width: %dpx) {\n  :root { --dw-font-size-base: %dpx; --dw-breakpoint-current: tablet; }\n}\n",
				$lg ? $lg : 1024,
				$tablet
			);
			$css .= sprintf(
				"@media (max-width: %dpx) {\n  :root { --dw-font-size-base: %dpx; --dw-breakpoint-current: mobile; }\n}\n",
				$md ? $md : 767,
				$mobile
			);
		}

		/**
		 * Filter the generated tokens CSS.
		 *
		 * @param string               $css  CSS.
		 * @param array<string,string> $variables Variables.
		 */
		return apply_filters( 'dashwoo_tokens_css', $css, $variables );
	}

	/**
	 * CSS built straight from the current settings.
	 *
	 * @return string
	 */
	public function inline_css() {
		return $this->css( $this->variables() );
	}

	/**
	 * Compile to a versioned file in uploads/dashwoo/cache/.
	 *
	 * @return array<string,mixed>
	 */
	public function compile() {
		$variables = $this->variables();
		$css       = $this->css( $variables );
		$hash      = substr( md5( $css ), 0, 12 );
		$name      = self::FILE_PREFIX . $hash . '.css';
		$cache     = Cache::instance();

		// Drop previous revisions: the cache directory only ever holds one tokens file.
		$cache->purge_files( self::FILE_PREFIX );

		$result = $cache->put_file( $name, $css );

		$this->compiled = array(
			'file'      => $name,
			'path'      => $result ? $result['path'] : '',
			'url'       => $result ? $result['url'] : '',
			'hash'      => $hash,
			'bytes'     => strlen( $css ),
			'variables' => count( $variables ),
			'css'       => $css,
		);

		$cache->set( 'tokens_compiled', $this->compiled, (int) dashwoo_get_setting( 'general.cache_ttl', 3600 ) );
		update_option( 'dashwoo_tokens_hash', $hash, false );

		return $this->compiled;
	}

	/**
	 * Last compiled artifact (compiles on demand).
	 *
	 * @return array<string,mixed>
	 */
	public function compiled() {
		if ( null === $this->compiled ) {
			$cached = Cache::instance()->get( 'tokens_compiled' );

			if ( is_array( $cached ) && ! empty( $cached['url'] ) && is_readable( (string) $cached['path'] ) ) {
				$this->compiled = $cached;

				return $cached;
			}

			return $this->compile();
		}

		return $this->compiled;
	}

	/**
	 * Recompile and log.
	 *
	 * @return array<string,mixed>
	 */
	public function recompile() {
		$result = $this->compile();

		Logger::instance()->debug( 'Tokens recompiled', array( 'hash' => $result['hash'], 'bytes' => $result['bytes'] ) );

		return $result;
	}

	/**
	 * Public URL of the compiled stylesheet.
	 *
	 * @return string
	 */
	public function url() {
		$compiled = $this->compiled();

		return isset( $compiled['url'] ) ? (string) $compiled['url'] : '';
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->compiled = null;
	}
}
