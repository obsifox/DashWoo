<?php
/**
 * The DashWoo brand kit, shipped inside the plugin.
 *
 * Files live in `assets/brand/` (SVG for the marks, WebP for the product cards), so
 * nothing is ever requested from a CDN. The account hero can show the mark, the
 * widget panel gets a category icon, and the marketing cards are ready for the
 * shop page / repository.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Brand assets.
 */
class Brand {

	/**
	 * Folder inside the plugin.
	 */
	const DIR = 'assets/brand/';

	/**
	 * Palette (also mirrored in assets/brand/BRAND-fa.md).
	 *
	 * @return array<string,string>
	 */
	public static function palette() {
		/**
		 * Filter the brand palette.
		 *
		 * @param array<string,string> $colors Token => hex.
		 */
		return (array) apply_filters(
			'dashwoo_brand_palette',
			array(
				'primary'    => '#2563eb',
				'primary_deep' => '#1d4ed8',
				'accent'     => '#22d3ee',
				'ink'        => '#0f172a',
				'surface'    => '#ffffff',
				'stage'      => '#f7f8fb',
				'muted'      => '#6b7280',
				'border'     => '#e5e7eb',
			)
		);
	}

	/**
	 * Absolute path of a brand file.
	 *
	 * @param string $file File name.
	 * @return string
	 */
	public static function path( $file ) {
		$file = basename( (string) $file );
		$base = defined( 'DASHWOO_PATH' ) ? (string) DASHWOO_PATH : dirname( __DIR__, 2 ) . '/';

		return $base . self::DIR . $file;
	}

	/**
	 * Public URL of a brand file.
	 *
	 * @param string $file File name.
	 * @return string
	 */
	public static function url( $file ) {
		$file = basename( (string) $file );
		$base = defined( 'DASHWOO_URL' ) ? (string) DASHWOO_URL : '';

		return $base . self::DIR . $file;
	}

	/**
	 * Does the file exist on disk?
	 *
	 * @param string $file File name.
	 * @return bool
	 */
	public static function exists( $file ) {
		return file_exists( self::path( $file ) );
	}

	/**
	 * Brand files DashWoo ships.
	 *
	 * @return array<string,string> Key => file name.
	 */
	public static function files() {
		return array(
			'mark'          => 'logo-mark.svg',
			'logo'          => 'logo.svg',
			'logo_dark'     => 'logo-dark.svg',
			'product_card'  => 'product-card.webp',
			'product_wide'  => 'product-wide.webp',
			'product_square' => 'product-square.webp',
			'icon_256'      => 'icon-256.png',
			'icon_512'      => 'icon-512.png',
			'banner'        => 'banner.png',
		);
	}

	/**
	 * The inline brand mark, ready to echo (no extra HTTP request).
	 *
	 * @param array<string,mixed> $args Args: size, class, label.
	 * @return string
	 */
	public static function mark( array $args = array() ) {
		$size  = max( 16, min( 200, (int) ( $args['size'] ?? 28 ) ) );
		$class = isset( $args['class'] ) ? ' ' . sanitize_html_class( (string) $args['class'] ) : '';
		$label = isset( $args['label'] ) ? (string) $args['label'] : 'DashWoo';
		$file  = self::path( self::files()['mark'] );

		$svg = '';

		if ( file_exists( $file ) ) {
			$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		if ( '' === trim( $svg ) ) {
			$svg = self::fallback_mark();
		}

		// Make the file reusable at any size: strip the XML header, force the size.
		$svg = preg_replace( '/<\?xml.*?\?>/s', '', $svg );
		$svg = preg_replace( '/\swidth="[^"]*"/', '', (string) $svg );
		$svg = preg_replace( '/\sheight="[^"]*"/', '', (string) $svg );
		$svg = preg_replace( '/<svg/', '<svg role="img" aria-label="' . esc_attr( $label ) . '" class="dw-brand-mark' . esc_attr( $class ) . '" width="' . $size . '" height="' . $size . '"', (string) $svg, 1 );

		/**
		 * Filter the inline brand mark markup.
		 *
		 * @param string              $svg  Markup.
		 * @param array<string,mixed> $args Args.
		 */
		return (string) apply_filters( 'dashwoo_brand_mark', $svg, $args );
	}

	/**
	 * A mark that always renders, even if the asset files were stripped.
	 *
	 * @return string
	 */
	protected static function fallback_mark() {
		$colors = self::palette();

		return '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">'
			. '<rect x="3" y="6" width="30" height="36" rx="9" fill="' . esc_attr( $colors['primary'] ) . '"/>'
			. '<rect x="15" y="13" width="30" height="22" rx="8" fill="' . esc_attr( $colors['accent'] ) . '" fill-opacity="0.85"/>'
			. '<path d="M25 35c8 0 14-4 18-10" stroke="' . esc_attr( $colors['ink'] ) . '" stroke-width="3.5" stroke-linecap="round"/>'
			. '</svg>';
	}

	/**
	 * The lockup (mark + wordmark) as inline SVG.
	 *
	 * @param array<string,mixed> $args Args: height, class, tone (light|dark).
	 * @return string
	 */
	public static function logo( array $args = array() ) {
		$height = max( 20, min( 160, (int) ( $args['height'] ?? 36 ) ) );
		$tone   = isset( $args['tone'] ) && 'dark' === $args['tone'] ? 'dark' : 'light';
		$file   = self::path( self::files()['logo'] );

		if ( file_exists( $file ) ) {
			$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( '' !== trim( $svg ) ) {
				$svg = preg_replace( '/<\?xml.*?\?>/s', '', $svg );
				$svg = preg_replace( '/\sheight="[^"]*"/', ' height="' . $height . '"', (string) $svg, 1 );
				$svg = preg_replace( '/<svg/', '<svg class="dw-brand-logo dw-brand-logo--' . esc_attr( $tone ) . '" role="img" aria-label="DashWoo"', (string) $svg, 1 );

				return (string) $svg;
			}
		}

		$color = 'dark' === $tone ? self::palette()['ink'] : self::palette()['surface'];

		return '<span class="dw-brand-lockup"><span class="dw-brand-lockup__mark">' . self::mark( array( 'size' => $height ) ) . '</span>'
			. '<span class="dw-brand-lockup__word" style="color:' . esc_attr( $color ) . ';font-size:' . (int) round( $height * 0.62 ) . 'px">DashWoo</span></span>';
	}

	/**
	 * Register the brand SVGs as a DashWoo icon set (SVG provider), so the logo and
	 * the marks can be dropped into any Elementor widget that draws DashWoo icons.
	 *
	 * @return array<string,array<string,string>> Set slug => icon name => file.
	 */
	public static function icon_set() {
		$set = array(
			'dashwoo-brand' => array(
				'mark'      => 'logo-mark.svg',
				'logo'      => 'logo.svg',
				'logo_dark' => 'logo-dark.svg',
				'card'      => 'product-card.webp',
			),
		);

		/**
		 * Filter the DashWoo brand icon set.
		 *
		 * @param array<string,array<string,string>> $set Icon set.
		 */
		return (array) apply_filters( 'dashwoo_brand_icon_set', $set );
	}
}
