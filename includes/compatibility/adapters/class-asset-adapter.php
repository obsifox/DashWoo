<?php
/**
 * Asset-layer adapter: what the design system can rely on at runtime.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters;

use DashWoo\Compatibility\Abstract_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Asset adapter.
 */
class Asset_Adapter extends Abstract_Adapter {  // Not final: third parties may extend a check set.

	/**
	 * Adapter id.
	 *
	 * @return string
	 */
	public function id() {
		return 'assets';
	}

	/**
	 * Adapter label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Assets';
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
	 * No version.
	 *
	 * @return string
	 */
	public function version() {
		return DASHWOO_VERSION;
	}

	/**
	 * Tested up to.
	 *
	 * @return string
	 */
	public function tested_up_to() {
		return DASHWOO_VERSION;
	}

	/**
	 * Probe the image toolchain honestly.
	 *
	 * `extension_loaded()` and `function_exists()` disagree on purpose: a host can
	 * ship GD while blocking its functions through `disable_functions`, which is
	 * exactly the case WordPress and WooCommerce also complain about.
	 *
	 * @return array{works:bool,loaded:bool,formats:array<int,string>,imagick:bool,message:string,hint:string}
	 */
	protected function probe_gd() {
		$loaded  = extension_loaded( 'gd' );
		$works   = $loaded && function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagecreate' );
		$imagick = class_exists( '\\Imagick' );
		$formats = array();

		if ( $works ) {
			$map = array(
				'jpeg' => 'imagecreatefromjpeg',
				'png'  => 'imagecreatefrompng',
				'gif'  => 'imagecreatefromgif',
				'webp' => 'imagecreatefromwebp',
				'avif' => 'imagecreatefromavif',
			);

			foreach ( $map as $format => $function ) {
				if ( function_exists( $function ) ) {
					$formats[] = $format;
				}
			}
		}

		$result = array(
			'works'   => $works,
			'loaded'  => $loaded,
			'formats' => array_values( $formats ),
			'imagick' => $imagick,
			'message' => '',
			'hint'    => '',
		);

		if ( $works ) {
			$result['message'] = sprintf(
				__('GD available (%s)%s - optional helper for server-side thumbnails', 'dashwoo'),
				$formats ? implode( ', ', $formats ) : __('no format readers detected', 'dashwoo'),
				$imagick ? __('; Imagick also present', 'dashwoo') : ''
			);

			return $result;
		}

		if ( ! $loaded ) {
			$result['message'] = __('GD not installed (php-gd): optional, DashWoo generates no thumbnails and needs no image functions', 'dashwoo');
			$result['hint']    = __( 'DashWoo works fully without GD. If you also see a GD warning in WooCommerce or in Site Health, ask your host to install or enable the php-gd extension (needed to resize product images).', 'dashwoo' );

			return $result;
		}

		// Loaded but unusable: usually disable_functions.
		$result['message'] = __('GD is loaded but its image functions are unavailable (blocked by disable_functions?) - optional for DashWoo', 'dashwoo');
		$result['hint']    = __( 'The GD extension is installed on this server, but its image functions (such as imagecreatetruecolor) are blocked by disable_functions. Ask your host to open those functions. DashWoo does not need them.', 'dashwoo' );

		return $result;
	}

	/**
	 * Collect checks.
	 *
	 * @return void
	 */
	protected function inspect() {
		$upload = wp_upload_dir();
		$base   = trailingslashit( $upload['basedir'] ) . 'dashwoo';

		$this->check(
			'storage_dir',
			(self::STATUS_OK),
			__('Asset storage: ', 'dashwoo') . $base,
			array( 'path' => $base )
		);
		$this->capability( 'asset_storage', true );

		// ------------------------------------------------------------------
		// Optional host capabilities.
		//
		// DashWoo never needs ZipArchive or GD to download, store and serve fonts
		// and icons: it only reads/writes plain files. Both are therefore reported
		// as *informational* rows - "this host does not provide X" - and never as a
		// warning, so the report stays green on a perfectly healthy shop. They are
		// listed because support tickets and host migrations need the full picture.
		// ------------------------------------------------------------------
		$zip = class_exists( '\\ZipArchive' );

		$this->check(
			'zip',
			$zip ? self::STATUS_OK : self::STATUS_NOTICE,
			$zip
				? __('ZipArchive available (optional helper for packaged export/import)', 'dashwoo')
				: __('ZipArchive not available (php-zip): optional, DashWoo installs and serves assets without it', 'dashwoo'),
			array(
				'available' => $zip,
				'required'  => false,
				'hint'      => $zip
					? ''
					: __( 'This one is optional and breaks nothing. If you want to download the asset bundle as a zip, ask your host to enable the php-zip extension.', 'dashwoo' ),
			)
		);
		$this->capability( 'zip', $zip );

		$gd        = $this->probe_gd();
		$gd_status = $gd['works'] ? self::STATUS_OK : self::STATUS_NOTICE;

		$this->check(
			'gd',
			$gd_status,
			$gd['message'],
			array(
				'available' => $gd['works'],
				'required'  => false,
				'loaded'    => $gd['loaded'],
				'formats'   => $gd['formats'],
				'imagick'   => $gd['imagick'],
				'hint'      => $gd['works'] ? '' : $gd['hint'],
			)
		);
		$this->capability( 'image_processing', $gd['works'] );

		$svg_ok = function_exists( 'wp_kses' );
		$this->check(
			'svg_sanitizer',
			$svg_ok ? self::STATUS_OK : self::STATUS_WARNING,
			$svg_ok ? __('SVG sanitizer ready (wp_kses allow-list)', 'dashwoo') : __('SVG sanitizer unavailable - SVG uploads blocked', 'dashwoo')
		);
		$this->capability( 'svg_sanitizer', $svg_ok );

		$variation = function_exists( 'wp_add_inline_style' );
		$this->check(
			'css_variation',
			$variation ? self::STATUS_OK : self::STATUS_WARNING,
			$variation ? __('Variable fonts + font-variation-settings supported', 'dashwoo') : __('Inline CSS API unavailable', 'dashwoo')
		);
		$this->capability( 'variable_fonts', $variation );

		$this->check(
			'cdn_free',
			self::STATUS_OK,
			__('CDN-free mode: all fonts/icons are served from the local uploads directory', 'dashwoo')
		);
		$this->capability( 'cdn_free', true );
	}
}
