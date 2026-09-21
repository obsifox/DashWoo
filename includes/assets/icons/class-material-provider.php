<?php
/**
 * Material Symbols / Material Icons provider.
 *
 * KEY FACT: Material Symbols is a VARIABLE font. One woff2 per style carries the
 * whole design space; wght / FILL / GRAD / opsz are axes selected at render time
 * through `font-variation-settings`. So "download icons" = 1 file per style,
 * never one file per weight.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Icons;

use DashWoo\Assets\Registry;
use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;
use DashWoo\Support\Http;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Material icon provider.
 */
final class Material_Provider {

	const BASE_CSS = 'https://fonts.googleapis.com/css2';
	const MAX_BYTES = 4194304;
	const MAGIC     = array( 'wOF2', 'wOFF' );

	/**
	 * Singleton.
	 *
	 * @var Material_Provider|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Material_Provider
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Available styles.
	 *
	 * @return array<string,string>
	 */
	public function styles() {
		return array(
			'outlined' => 'Material Symbols Outlined',
			'rounded'  => 'Material Symbols Rounded',
			'sharp'    => 'Material Symbols Sharp',
			'classic'  => 'Material Icons',
		);
	}

	/**
	 * Is the style a variable font?
	 *
	 * @param string $style Style key.
	 * @return bool
	 */
	public function is_variable( $style ) {
		return 'classic' !== $style;
	}

	/**
	 * Axis ranges for a style (used by the UI sliders + validation).
	 *
	 * @param string $style Style key.
	 * @return array<string,array<string,float>>
	 */
	public function axes( $style ) {
		if ( ! $this->is_variable( $style ) ) {
			return array();
		}

		return array(
			'wght' => array(
				'min'     => 100,
				'max'     => 700,
				'default' => 400,
				'step'    => 100,
			),
			'FILL' => array(
				'min'     => 0,
				'max'     => 1,
				'default' => 0,
				'step'    => 1,
			),
			'GRAD' => array(
				'min'     => -25,
				'max'     => 200,
				'default' => 0,
				'step'    => 25,
			),
			'opsz' => array(
				'min'     => 20,
				'max'     => 48,
				'default' => 24,
				'step'    => 1,
			),
		);
	}

	/**
	 * CSS2 request URL for a style.
	 *
	 * @param string $style Style key.
	 * @return string
	 */
	public function css_url( $style ) {
		if ( 'classic' === $style ) {
			return self::BASE_CSS . '?family=Material+Icons&display=block';
		}

		$family = str_replace( ' ', '+', $this->styles()[ $style ] );
		$axes   = $family . ':opsz,wght,FILL,GRAD@20..48,100..700,0..1,-25..200';

		// Hand-built query: the axis syntax must stay unencoded.
		return self::BASE_CSS . '?family=' . $axes . '&display=block&subset=latin';
	}

	/**
	 * Download one style and register it.
	 *
	 * @param string $style  Style key.
	 * @param bool   $force  Re-download even if present.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function install( $style, $force = false ) {
		if ( ! dashwoo_is_on( 'general.enabled' ) ) {
			return new \WP_Error( 'dashwoo_disabled', __('DashWoo is disabled.', 'dashwoo') );
		}

		$styles = $this->styles();
		if ( ! isset( $styles[ $style ] ) ) {
			return new \WP_Error( 'dashwoo_icon_style', sprintf( __('Unknown icon style "%s".', 'dashwoo'), (string) $style ) );
		}

		$registry = Registry::instance();
		$slug     = 'material-symbols-' . $style;
		$existing = $registry->find_by_slug( 'icon', $slug );

		if ( $existing && ! $force && 'active' === $existing['status'] ) {
			return array(
				'slug'      => $slug,
				'installed' => false,
				'reason'    => 'already_installed',
				'row'       => $existing,
			);
		}

		$css = Http::get( $this->css_url( $style ) );

		if ( is_wp_error( $css ) ) {
			return $css;
		}
		if ( 200 !== (int) $css['code'] ) {
			return new \WP_Error( 'dashwoo_icon_css_status', sprintf( __('CSS2 responded with %d.', 'dashwoo'), (int) $css['code'] ) );
		}

		$url = $this->extract_woff2( $css['body'] );

		if ( '' === $url ) {
			return new \WP_Error( 'dashwoo_icon_css_parse', __('No woff2 source found in the icon CSS.', 'dashwoo') );
		}
		if ( ! Http::is_allowed_url( $url ) ) {
			return new \WP_Error( 'dashwoo_icon_host', sprintf( __('Blocked non allow-listed icon host: %s', 'dashwoo'), $url ) );
		}

		$storage = Storage::instance();
		$dir     = $storage->path( 'icons' ) . $slug;
		$dest    = trailingslashit( $dir ) . 'material-symbols.woff2';

		Filesystem::mkdir( $dir );

		$download = Http::download( $url, $dest, self::MAX_BYTES, self::MAGIC );

		if ( is_wp_error( $download ) ) {
			return $download;
		}

		$meta = array(
			'style'      => $style,
			'variable'   => $this->is_variable( $style ),
			'axes'       => $this->axes( $style ),
			'format'     => 'woff2',
			'file'       => 'material-symbols.woff2',
			'path'       => $storage->relative( $download['path'] ),
			'url'        => $storage->url( 'icons' ) . $slug . '/material-symbols.woff2',
			'bytes'      => (int) $download['bytes'],
			'sha256'     => $download['sha256'],
			'provider'   => 'google',
			'source'     => $url,
			'local'      => true,
			'updated_at' => gmdate( 'c' ),
			'codepoints' => 'ligature',
		);

		$id = $registry->upsert(
			array(
				'type'       => 'icon',
				'group_key'  => 'icons',
				'slug'       => $slug,
				'label'      => $styles[ $style ],
				'provider'   => 'google',
				'version'    => substr( $download['sha256'], 0, 8 ),
				'status'     => 'active',
				'is_default' => 'outlined' === $style ? 1 : 0,
				'role'       => 'icon-font',
				'path'       => 'icons/' . $slug,
				'url'        => $storage->url( 'icons' ) . $slug . '/',
				'size'       => (int) $download['bytes'],
				'meta'       => $meta,
			)
		);

		Logger::instance()->info( 'Icon font installed', array( 'style' => $style ) );

		do_action( 'dashwoo_icon_installed', $slub = $slug, $meta );

		return array(
			'id'        => $id,
			'slug'      => $slug,
			'installed' => true,
			'meta'      => $meta,
		);
	}

	/**
	 * Extract the woff2 URL from a CSS2 response.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	public function extract_woff2( $css ) {
		if ( preg_match_all( '/url\(\s*[\'"]?([^\'")]+\.woff2)[\'"]?\s*\)/i', (string) $css, $matches ) ) {
			return (string) $matches[1][0];
		}
		if ( preg_match_all( '/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', (string) $css, $matches ) ) {
			return (string) $matches[1][0];
		}

		return '';
	}
}
