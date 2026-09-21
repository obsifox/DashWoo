<?php
/**
 * The DashWoo brand tab in Elementor's icon library.
 *
 * The shop owner asked for a product with an identity: the marks and the product
 * cards live in `assets/brand/`, and this class makes the marks pickable *everywhere*
 * in Elementor (any widget with an icon control gets a "DashWoo" tab), instead of
 * only inside DashWoo's own widgets.
 *
 * No icon font, no CDN: the tab is a tiny local stylesheet whose `content: url(...)`
 * points at the SVGs inside the plugin.
 *
 * @package DashWoo
 */

namespace DashWoo\DesignSystem\Elementor;

use DashWoo\Account\Brand;

defined( 'ABSPATH' ) || exit;

/**
 * Brand icon tab.
 */
class Brand_Icons {

	/**
	 * Tab slug.
	 */
	const TAB = 'dashwoo';

	/**
	 * Style handle.
	 */
	const HANDLE = 'dashwoo-brand-icons';

	/**
	 * Singleton.
	 *
	 * @var Brand_Icons|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Brand_Icons
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue' ) );
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'register_tab' ) );
	}

	/**
	 * Stylesheet URL.
	 *
	 * @return string
	 */
	public static function url() {
		$base = defined( 'DASHWOO_URL' ) ? (string) DASHWOO_URL : '';

		return $base . 'assets/css/brand-icons.css';
	}

	/**
	 * Path of the stylesheet.
	 *
	 * @return string
	 */
	public static function path() {
		$base = defined( 'DASHWOO_PATH' ) ? (string) DASHWOO_PATH : dirname( __DIR__, 3 ) . '/';

		return $base . 'assets/css/brand-icons.css';
	}

	/**
	 * Enqueue the tab's stylesheet (editor, preview and front end - the same file).
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, self::url(), array(), defined( 'DASHWOO_VERSION' ) ? DASHWOO_VERSION : null );
	}

	/**
	 * Icon names of the tab (also the CSS class suffix: `dw-brand-<name>`).
	 *
	 * @return array<int,string>
	 */
	public static function icons() {
		$files = (array) Brand::files();
		$icons = array();

		foreach ( array( 'mark', 'logo', 'logo_dark' ) as $key ) {
			if ( isset( $files[ $key ] ) ) {
				$icons[] = 'dw-brand-' . str_replace( '_', '-', $key );
			}
		}

		/**
		 * Filter the icons exposed in the DashWoo brand tab.
		 *
		 * @param array<int,string> $icons Icon names.
		 */
		return (array) apply_filters( 'dashwoo_brand_icons', $icons );
	}

	/**
	 * Register the tab in Elementor's icon manager.
	 *
	 * @param array<string,mixed> $tabs Existing tabs.
	 * @return array<string,mixed>
	 */
	public function register_tab( $tabs ) {
		$tabs = is_array( $tabs ) ? $tabs : array();

		if ( isset( $tabs[ self::TAB ] ) ) {
			return $tabs;
		}

		$tabs[ self::TAB ] = array(
			'name'          => self::TAB,
			'label'         => 'DashWoo',
			'url'           => self::url(),
			'enqueue'       => array( self::url() ),
			'prefix'        => 'dw-brand-',
			'displayPrefix' => 'dw-brand',
			'labelIcon'     => 'eicon-nerd',
			'ver'           => defined( 'DASHWOO_VERSION' ) ? DASHWOO_VERSION : '1.0.0',
			'icons'         => self::icons(),
			'styles'        => array( self::url() ),
		);

		return $tabs;
	}

	/**
	 * Everything a shop owner can drop in from the tab.
	 *
	 * @return array<string,string> Class => file.
	 */
	public static function map() {
		return array(
			'dw-brand-mark'     => 'logo-mark.svg',
			'dw-brand-logo'     => 'logo.svg',
			'dw-brand-logo-dark' => 'logo-dark.svg',
		);
	}
}
