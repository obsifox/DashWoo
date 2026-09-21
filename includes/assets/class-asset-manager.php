<?php
/**
 * Asset Manager facade: one API for fonts, icons, images, SVG and custom code.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets;

use DashWoo\Cache\Cache;
use DashWoo\DesignSystem\Compiler;
use DashWoo\Support\Filesystem;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Asset manager.
 */
final class Asset_Manager {

	const STYLE_HANDLE = 'dashwoo-platform';

	/**
	 * Allowed uploads per type: extension => mime.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function rules() {
		return array(
			'font'   => array(
				'ext'   => array( 'woff2', 'woff', 'ttf', 'otf' ),
				'max'   => (int) dashwoo_get_setting( 'fonts_custom.max_file_mb', 10 ) * 1048576,
				'magic' => array( 'wOF2', 'wOFF', 'OTTO', "\x00\x01\x00\x00" ),
				'dir'   => 'fonts',
			),
			'image'  => array(
				'ext'   => array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' ),
				'max'   => (int) dashwoo_get_setting( 'assets_images.max_file_mb', 8 ) * 1048576,
				'magic' => array(),
				'dir'   => 'images',
			),
			'svg'    => array(
				'ext'   => array( 'svg' ),
				'max'   => 2097152,
				'magic' => array( '<svg', '<?xml' ),
				'dir'   => 'svg',
			),
			'custom' => array(
				'ext'   => array( 'css', 'js', 'json' ),
				'max'   => 1048576,
				'magic' => array(),
				'dir'   => 'custom',
			),
		);
	}

	/**
	 * Singleton.
	 *
	 * @var Asset_Manager|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Asset_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ), 30 );
		add_action( 'wp_head', array( $this, 'render_custom_css' ), 99 );
		add_action( 'wp_footer', array( $this, 'render_custom_js' ), 99 );
		add_action( 'wp_head', array( $this, 'render_custom_js' ), 99 );
		add_filter( 'upload_mimes', array( $this, 'allow_svg_mime' ) );
	}

	/**
	 * Platform stylesheet + compiled tokens.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! dashwoo_is_on( 'general.enabled' ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			DASHWOO_URL . 'assets/css/dashwoo.css',
			array(),
			DASHWOO_VERSION
		);

		$compiled = Cache::instance()->get( 'tokens_compiled' );

		if ( is_array( $compiled ) && ! empty( $compiled['url'] ) ) {
			wp_enqueue_style( 'dashwoo-tokens', $compiled['url'], array( self::STYLE_HANDLE ), $compiled['hash'] );
		} else {
			// Fallback: inject the variables inline, never a remote request.
			wp_add_inline_style( self::STYLE_HANDLE, Compiler::instance()->inline_css() );
		}
	}

	/**
	 * Admin assets.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		if ( false === strpos( (string) $hook, 'dashwoo' ) ) {
			return;
		}

		wp_enqueue_style(
			'dashwoo-admin',
			DASHWOO_URL . 'assets/css/admin.css',
			array(),
			DASHWOO_VERSION
		);
		wp_enqueue_script(
			'dashwoo-admin',
			DASHWOO_URL . 'assets/js/admin.js',
			array( 'wp-api-fetch', 'wp-i18n' ),
			DASHWOO_VERSION,
			true
		);

		// The admin script speaks through wp.i18n as well, so the JSON catalogue
		// next to the .mo file is what makes the strings Persian (or English).
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'dashwoo-admin', DASHWOO_TEXTDOMAIN, DASHWOO_DIR . 'languages' );
		}
		wp_localize_script(
			'dashwoo-admin',
			'DashWooData',
			array(
				'restUrl' => esc_url_raw( rest_url( 'dashwoo/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'version' => DASHWOO_VERSION,
			)
		);
	}

	/**
	 * Custom CSS from the settings (already sanitised on save).
	 *
	 * @return void
	 */
	public function render_custom_css() {
		if ( ! dashwoo_is_on( 'assets_custom.enabled' ) ) {
			return;
		}

		$css = (string) dashwoo_get_setting( 'assets_custom.css', '' );

		if ( '' === trim( $css ) ) {
			return;
		}

		printf( "<style id=\"dashwoo-custom-css\">\n%s\n</style>\n", $this->strip_breaks( $css, 'style' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Custom JS from the settings.
	 *
	 * @return void
	 */
	public function render_custom_js() {
		if ( ! dashwoo_is_on( 'assets_custom.enabled' ) ) {
			return;
		}

		$js = (string) dashwoo_get_setting( 'assets_custom.js', '' );

		if ( '' === trim( $js ) ) {
			return;
		}

		$location = dashwoo_get_setting( 'assets_custom.location', 'footer' );
		$wanted   = ( 'head' === $location ) ? 'wp_head' : 'wp_footer';

		if ( current_filter() !== $wanted ) {
			return;
		}

		printf( "<script id=\"dashwoo-custom-js\">\n%s\n</script>\n", $this->strip_breaks( $js, 'script' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Remove the closing tag of the hosting element (defence in depth).
	 *
	 * @param string $code Raw code.
	 * @param string $tag  style|script.
	 * @return string
	 */
	public function strip_breaks( $code, $tag ) {
		$code = (string) $code;

		// Remove both the opening and the closing form of every tag that could
		// terminate or re-open the element the snippet is printed inside.
		foreach ( array( 'style', 'script' ) as $forbidden ) {
			$code = preg_replace( '#<\s*/?\s*' . $forbidden . '\b[^>]*>#i', '', (string) $code );
		}

		return trim( (string) $code );
	}

	/**
	 * Register the SVG mime so the media library accepts it (admin only).
	 *
	 * @param array<string,string> $mimes Mimes.
	 * @return array<string,string>
	 */
	public function allow_svg_mime( $mimes ) {
		if ( dashwoo_is_on( 'assets_svg.allow_upload' ) && current_user_can( 'manage_options' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}

		return $mimes;
	}

	/**
	 * Import an uploaded file into the DashWoo storage.
	 *
	 * @param string              $tmp_path Temporary file path.
	 * @param array<string,mixed> $args     type, label, slug, provider, role, meta.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function import_file( $tmp_path, array $args = array() ) {
		$type  = isset( $args['type'] ) && in_array( $args['type'], Registry::instance()->types(), true ) ? $args['type'] : 'image';
		$rules = $this->rules();

		if ( ! isset( $rules[ $type ] ) ) {
			return new \WP_Error( 'dashwoo_asset_type', sprintf( __('Type "%s" does not accept uploads.', 'dashwoo'), $type ) );
		}

		if ( ! is_readable( $tmp_path ) ) {
			return new \WP_Error( 'dashwoo_asset_unreadable', __('The uploaded file is not readable.', 'dashwoo') );
		}

		$size = (int) filesize( $tmp_path );
		$max  = (int) $rules[ $type ]['max'];

		if ( $size <= 0 ) {
			return new \WP_Error( 'dashwoo_asset_empty', __('The uploaded file is empty.', 'dashwoo') );
		}
		if ( $size > $max ) {
			return new \WP_Error(
				'dashwoo_asset_too_large',
				sprintf( __('File is bigger than the %s limit.', 'dashwoo'), Filesystem::format_size( $max ) )
			);
		}

		$extension = strtolower( (string) pathinfo( $tmp_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, (array) $rules[ $type ]['ext'], true ) ) {
			return new \WP_Error(
				'dashwoo_asset_extension',
				sprintf( __('Extension ".%s" is not allowed for %s assets.', 'dashwoo'), $extension, $type )
			);
		}

		$contents = Filesystem::get( $tmp_path );
		if ( false === $contents ) {
			return new \WP_Error( 'dashwoo_asset_unreadable', __('Cannot read the uploaded file.', 'dashwoo') );
		}

		$magic = (array) $rules[ $type ]['magic'];
		if ( $magic ) {
			$ok = false;
			foreach ( $magic as $signature ) {
				if ( 0 === strncmp( (string) $contents, $signature, strlen( $signature ) ) ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) {
				return new \WP_Error( 'dashwoo_asset_signature', __('The file signature does not match its type.', 'dashwoo') );
			}
		}

		if ( 'svg' === $type ) {
			if ( ! dashwoo_is_on( 'assets_svg.allow_upload' ) ) {
				return new \WP_Error( 'dashwoo_svg_disabled', __('SVG uploads are disabled in the settings.', 'dashwoo') );
			}

			$audit = Svg_Sanitizer::audit( (string) $contents );

			// The structural (DOM) pass is an *optional* extra: without php-xml the
			// kses-based sanitizer above is the whole security story, and the shop
			// owner sees that as a blocked capability instead of a broken upload.
			$audit['structural'] = \DashWoo\Capabilities\Capabilities::instance()->enabled( 'svg_sanitizer' );
			$contents = Svg_Sanitizer::sanitize( (string) $contents, dashwoo_is_on( 'assets_svg.strip_ids' ) );

			if ( '' === $contents ) {
				return new \WP_Error( 'dashwoo_svg_invalid', __('The SVG could not be sanitised.', 'dashwoo') );
			}
		}

		$registry  = Registry::instance();
		$storage   = Storage::instance();
		$slug      = $registry->slug( $args['slug'] ?? pathinfo( $tmp_path, PATHINFO_FILENAME ) );
		$slug      = $slug ? $slug : 'asset-' . substr( md5( (string) $size . microtime() ), 0, 8 );
		$directory = $storage->path( $rules[ $type ]['dir'] ) . $slug . '/';
		$file_name = sanitize_file_name( $slug . '.' . $extension );
		$abs       = $directory . $file_name;

		if ( ! Filesystem::put( $abs, $contents ) ) {
			return new \WP_Error( 'dashwoo_asset_write', __('The asset could not be written to the uploads directory.', 'dashwoo') );
		}

		$meta = array_merge(
			isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array(),
			array(
				'file'       => $file_name,
				'path'       => $storage->relative( $abs ),
				'extension'  => $extension,
				'mime'       => isset( $rules[ $type ]['ext'] ) ? $this->mime_for( $extension ) : '',
				'bytes'      => $size,
				'sha256'     => hash( 'sha256', (string) $contents ),
				'audit'      => isset( $audit ) ? $audit : array(),
				'local'      => true,
				'updated_at' => gmdate( 'c' ),
			)
		);

		if ( 'image' === $type ) {
			$meta['optimized'] = $this->optimize_image( $abs, $extension, $slug, $rules[ $type ] );
		}

		$id = $registry->upsert(
			array(
				'type'       => $type,
				'group_key'  => isset( $args['group_key'] ) ? $args['group_key'] : $rules[ $type ]['dir'],
				'slug'       => $slug,
				'label'      => isset( $args['label'] ) ? $args['label'] : $slug,
				'provider'   => isset( $args['provider'] ) ? $args['provider'] : 'local',
				'version'    => substr( $meta['sha256'], 0, 8 ),
				'status'     => 'active',
				'is_default' => ! empty( $args['is_default'] ) ? 1 : 0,
				'role'       => isset( $args['role'] ) ? $args['role'] : '',
				'path'       => $storage->relative( $abs ),
				'url'        => $storage->url( '' ) . $storage->relative( $abs ),
				'size'       => $size,
				'meta'       => $meta,
			)
		);

		Logger::instance()->info( 'Asset imported', array( 'type' => $type, 'slug' => $slug ) );

		do_action( 'dashwoo_asset_imported', $id, $type, $slug );

		return array(
			'id'   => $id,
			'slug' => $slug,
			'type' => $type,
			'url'  => $storage->url( '' ) . $storage->relative( $abs ),
			'meta' => $meta,
		);
	}

	/**
	 * Replace the file of an existing asset (Update button).
	 *
	 * @param int    $id       Row id.
	 * @param string $tmp_path New file.
	 * @return array<string,mixed>|\WP_Error
	 */
	/**
	 * Generate an optimized sibling for an uploaded image - when the host can.
	 *
	 * This is the concrete effect of the `image_processing` capability gate: with no
	 * GD/Imagick nothing happens (and the row says why), the original file is stored
	 * untouched, and the feature switches itself back on the day the extension
	 * appears - no setting has to be touched.
	 *
	 * @param string              $abs       Absolute path of the stored file.
	 * @param string              $extension File extension.
	 * @param string              $slug      Asset slug.
	 * @param array<string,mixed> $rules     Type rules.
	 * @return array<string,mixed> Result metadata.
	 */
	protected function optimize_image( $abs, $extension, $slug, array $rules ) {
		unset( $rules );

		$capabilities = \DashWoo\Capabilities\Capabilities::instance();
		$states       = $capabilities->states();

		$result = array(
			'feature'    => 'image_processing',
			'capability' => 'image_processing',
			'enabled'    => $capabilities->enabled( 'image_processing' ),
			'source'     => $extension,
			'variants'   => array(),
			'reason'     => '',
		);

		if ( ! $result['enabled'] ) {
			$missing = isset( $states['image_processing']['missing'] ) ? (array) $states['image_processing']['missing'] : array();

			$result['reason'] = 'blocked by host capabilities: ' . implode( ',', $missing );

			return $result;
		}

		if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			$result['reason'] = 'source format needs no conversion';

			return $result;
		}

		if ( ! $capabilities->can( 'webp' ) ) {
			$result['reason'] = 'no WebP encoder on this host';

			return $result;
		}

		$image = @imagecreatefromstring( (string) Filesystem::get( $abs ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! $image ) {
			$result['reason'] = 'image could not be decoded';

			return $result;
		}

		$target = dirname( $abs ) . '/' . $slug . '.webp';
		$ok     = @imagewebp( $image, $target, 82 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		imagedestroy( $image );

		if ( ! $ok ) {
			$result['reason'] = 'WebP encoding failed';

			return $result;
		}

		$result['variants']['webp'] = array(
			'file'  => basename( $target ),
			'path'  => Storage::instance()->relative( $target ),
			'bytes' => (int) filesize( $target ),
		);

		return $result;
	}

	public function replace_file( $id, $tmp_path ) {
		$row = Registry::instance()->find( $id );

		if ( ! $row ) {
			return new \WP_Error( 'dashwoo_asset_missing', __('Asset not found.', 'dashwoo') );
		}

		$result = $this->import_file(
			$tmp_path,
			array(
				'type'       => $row['type'],
				'label'      => $row['label'] ? $row['label'] : $row['slug'],
				'slug'       => $row['slug'],
				'provider'   => $row['provider'],
				'role'       => $row['role'],
				'group_key'  => $row['group_key'],
				'is_default' => $row['is_default'],
				'meta'       => $row['meta'],
			)
		);

		return $result;
	}

	/**
	 * Rename (label) an asset.
	 *
	 * @param int    $id    Row id.
	 * @param string $label New label.
	 * @return bool
	 */
	public function rename( $id, $label ) {
		return Registry::instance()->update( $id, array( 'label' => sanitize_text_field( $label ) ) );
	}

	/**
	 * Enable / disable an asset.
	 *
	 * @param int  $id     Row id.
	 * @param bool $status Active?
	 * @return bool
	 */
	public function set_status( $id, $status ) {
		return Registry::instance()->update( $id, array( 'status' => $status ? 'active' : 'disabled' ) );
	}

	/**
	 * Make an asset the default of its group.
	 *
	 * @param int $id Row id.
	 * @return bool
	 */
	public function set_default( $id ) {
		return Registry::instance()->set_default( $id );
	}

	/**
	 * Delete an asset + files.
	 *
	 * @param int $id Row id.
	 * @return bool
	 */
	public function delete( $id ) {
		return Registry::instance()->delete( $id );
	}

	/**
	 * Preview payload for the admin list.
	 *
	 * @param int $id Row id.
	 * @return array<string,mixed>|null
	 */
	/**
	 * Delete an asset by type + slug (used by the UI and the REST API).
	 *
	 * @param string $slug Asset slug.
	 * @param string $type Asset type.
	 * @return bool
	 */
	public function delete_by_slug( $slug, $type = 'image' ) {
		$row = Registry::instance()->find_by_slug( (string) $type, (string) $slug );

		return $row ? $this->delete( (int) $row['id'] ) : false;
	}

	public function preview( $id ) {
		$row = Registry::instance()->find( $id );

		if ( ! $row ) {
			return null;
		}

		$html = '';

		switch ( $row['type'] ) {
			case 'font':
				$html = sprintf(
					'<span class="dw-font-preview" style="font-family:%s;font-size:22px">%s %s</span>',
					esc_attr( Fonts\Font_Face_Compiler::quote( $row['meta']['family'] ?? $row['label'] ) ),
					esc_html( __( 'Sample', 'dashwoo' ) ),
					esc_html__( 'ABC 123 — sample text', 'dashwoo' )
				);
				break;

			case 'icon':
				$html = sprintf(
					'<span class="dw-icon dw-icon--material-symbols dw-icon--%s" style="font-size:32px;font-variation-settings:\'FILL\' 0,\'GRAD\' 0,\'opsz\' 24,\'wght\' 400">%s</span>',
					esc_attr( (string) ( $row['meta']['style'] ?? 'outlined' ) ),
					esc_html( 'storefront' )
				);
				break;

			case 'svg':
				$abs  = Storage::instance()->absolute( $row['path'] );
				$html = $abs ? Svg_Sanitizer::inline( (string) Filesystem::get( $abs ), 'currentColor', 32 ) : '';
				break;

			case 'image':
				$html = sprintf( '<img src="%s" alt="%s" style="max-width:120px;height:auto" />', esc_url( $row['url'] ), esc_attr( $row['label'] ) );
				break;

			default:
				$html = sprintf( '<code>%s</code>', esc_html( $row['path'] ) );
		}

		return array(
			'id'    => (int) $row['id'],
			'type'  => $row['type'],
			'label' => $row['label'],
			'html'  => $html,
		);
	}

	/**
	 * All assets of a type.
	 *
	 * @param array<string,mixed> $args Query args.
	 * @return array<int,array<string,mixed>>
	 */
	public function all( array $args = array() ) {
		return Registry::instance()->query( $args );
	}

	/**
	 * Storage stats (used by the dashboard + System Status).
	 *
	 * @return array<string,mixed>
	 */
	public function stats() {
		$storage = Storage::instance()->stats();
		$counts  = array();

		foreach ( Registry::instance()->types() as $type ) {
			$counts[ $type ] = Registry::instance()->count( array( 'type' => $type ) );
		}

		$storage['counts'] = $counts;

		return $storage;
	}

	/**
	 * Extension => mime map for the registry metadata.
	 *
	 * @param string $extension Extension.
	 * @return string
	 */
	public function mime_for( $extension ) {
		$map = array(
			'woff2' => 'font/woff2',
			'woff'  => 'font/woff',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'svg'   => 'image/svg+xml',
			'png'   => 'image/png',
			'jpg'   => 'image/jpeg',
			'jpeg'  => 'image/jpeg',
			'webp'  => 'image/webp',
			'avif'  => 'image/avif',
			'gif'   => 'image/gif',
			'css'   => 'text/css',
			'js'    => 'application/javascript',
			'json'  => 'application/json',
		);

		return isset( $map[ $extension ] ) ? $map[ $extension ] : 'application/octet-stream';
	}
}
