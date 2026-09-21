<?php
/**
 * Font manager: install / list / rename / toggle / delete / update + front-end delivery.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Fonts;

use DashWoo\Assets\Registry;
use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Font manager.
 */
final class Font_Manager {

	const HANDLE = 'dashwoo-fonts';

	/**
	 * Singleton.
	 *
	 * @var Font_Manager|null
	 */
	private static $instance = null;

	/**
	 * Cached compiled stylesheet info.
	 *
	 * @var array<string,mixed>|null
	 */
	private $compiled = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Font_Manager
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'wp_head', array( $this, 'preload_default' ), 1 );
		add_filter( 'elementor/fonts/additional_fonts', array( $this, 'elementor_fonts' ) );
		add_action( 'dashwoo_font_changed', array( $this, 'recompile' ), 10, 0 );
	}

	/**
	 * Installed fonts.
	 *
	 * @param array<string,mixed> $args Query args.
	 * @return array<int,array<string,mixed>>
	 */
	public function all( array $args = array() ) {
		return Registry::instance()->query( array_merge( array( 'type' => 'font', 'per_page' => 200 ), $args ) );
	}

	/**
	 * Active fonts.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function active() {
		return $this->all( array( 'status' => 'active' ) );
	}

	/**
	 * The default font row (falls back to the first active one).
	 *
	 * @return array<string,mixed>|null
	 */
	public function default_font() {
		$defaults = $this->all( array( 'is_default' => 1 ) );

		if ( $defaults ) {
			return $defaults[0];
		}

		$active = $this->active();

		return $active ? $active[0] : null;
	}

	/**
	 * Install a Google family (thin wrapper so tests can stub the provider).
	 *
	 * @param string            $family  Family name or slug.
	 * @param array<int,string> $weights Weights.
	 * @param array<int,string> $subsets Subsets.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function install_google( $family, $weights = array( '400' ), $subsets = array() ) {
		$result = Google_Fonts_Provider::instance()->install( $family, $weights, $subsets );

		if ( ! is_wp_error( $result ) ) {
			$this->recompile();
		}

		return $result;
	}

	/**
	 * Install a font from already-uploaded files (asset manager / uploader).
	 *
	 * @param array<string,mixed>              $args family, slug, label, weights map.
	 * @param array<int,array<string,mixed>>   $files Relative file descriptors.
	 * @return int|\WP_Error Row id.
	 */
	public function install_local( array $args, array $files ) {
		$family = isset( $args['family'] ) ? sanitize_text_field( $args['family'] ) : '';
		$slug   = isset( $args['slug'] ) ? $args['slug'] : $family;

		if ( '' === $family ) {
			return new \WP_Error( 'dashwoo_font_family_required', __('A font family name is required.', 'dashwoo') );
		}

		$registry = Registry::instance();
		$storage  = Storage::instance();
		$slug     = $registry->slug( $slug );

		if ( '' === $slug || '' === $storage->clean_type( 'fonts' ) ) {
			return new \WP_Error( 'dashwoo_font_slug', __('Invalid font slug.', 'dashwoo') );
		}
		if ( ! $files ) {
			return new \WP_Error( 'dashwoo_font_files', __('No font files supplied.', 'dashwoo') );
		}

		$faces = array();
		$bytes = 0;

		foreach ( $files as $file ) {
			$relative = isset( $file['path'] ) ? ltrim( (string) $file['path'], '/' ) : '';
			$abs      = $storage->absolute( $relative );

			if ( '' === $abs || ! is_readable( $abs ) ) {
				continue;
			}

			$bytes  += (int) filesize( $abs );
			$faces[] = array(
				'file'          => basename( $abs ),
				'path'          => $relative,
				'url'           => $storage->url( '' ) . $relative,
				'weight'        => isset( $file['weight'] ) ? (string) $file['weight'] : '400',
				'style'         => isset( $file['style'] ) ? (string) $file['style'] : 'normal',
				'unicode_range' => isset( $file['unicode_range'] ) ? (string) $file['unicode_range'] : '',
				'bytes'         => (int) filesize( $abs ),
				'sha256'        => (string) hash_file( 'sha256', $abs ),
			);
		}

		if ( ! $faces ) {
			return new \WP_Error( 'dashwoo_font_files_missing', __('The uploaded font files are not readable.', 'dashwoo') );
		}

		$meta = array(
			'family'     => $family,
			'slug'       => $slug,
			'weights'    => array_values( array_unique( array_column( $faces, 'weight' ) ) ),
			'faces'      => $faces,
			'files'      => array_column( $faces, 'file' ),
			'provider'   => isset( $args['provider'] ) ? $args['provider'] : 'local',
			'license'    => isset( $args['license'] ) ? $args['license'] : '',
			'local'      => true,
			'updated_at' => gmdate( 'c' ),
			'bytes'      => $bytes,
		);

		$id = $registry->upsert(
			array(
				'type'       => 'font',
				'group_key'  => 'typography',
				'slug'       => $slug,
				'label'      => $family,
				'provider'   => isset( $args['provider'] ) ? $args['provider'] : 'local',
				'version'    => substr( md5( $family . $bytes ), 0, 8 ),
				'status'     => 'active',
				'is_default' => isset( $args['is_default'] ) ? (bool) $args['is_default'] : null,
				'role'       => isset( $args['role'] ) ? $args['role'] : 'body',
				'path'       => 'fonts/' . $slug,
				'url'        => $storage->url( 'fonts' ) . $slug . '/',
				'size'       => $bytes,
				'meta'       => $meta,
			)
		);

		$this->recompile();

		Logger::instance()->info( 'Local font installed', array( 'family' => $family, 'files' => count( $faces ) ) );

		return $id;
	}

	/**
	 * Rename (label only, the slug/file names stay stable).
	 *
	 * @param string $slug  Slug.
	 * @param string $label New label.
	 * @return bool
	 */
	public function rename( $slug, $label ) {
		$row = Registry::instance()->find_by_slug( 'font', $slug );
		if ( ! $row ) {
			return false;
		}

		$meta           = $row['meta'];
		$meta['family'] = sanitize_text_field( $label );

		$ok = Registry::instance()->update(
			$row['id'],
			array(
				'label' => sanitize_text_field( $label ),
				'meta'  => $meta,
			)
		);

		if ( $ok ) {
			// The family name lives inside the generated CSS, so it must be rebuilt.
			$this->recompile();
			do_action( 'dashwoo_font_changed' );
		}

		return $ok;
	}

	/**
	 * Enable / disable a font.
	 *
	 * @param string $slug   Slug.
	 * @param bool   $active Status.
	 * @return bool
	 */
	public function set_status( $slug, $active ) {
		$row = Registry::instance()->find_by_slug( 'font', $slug );
		if ( ! $row ) {
			return false;
		}

		$ok = Registry::instance()->update( $row['id'], array( 'status' => $active ? 'active' : 'disabled' ) );

		if ( $ok ) {
			$this->recompile();
		}

		return $ok;
	}

	/**
	 * Delete a font and its files.
	 *
	 * @param string $slug Slug.
	 * @return bool
	 */
	public function delete( $slug ) {
		$row = Registry::instance()->find_by_slug( 'font', $slug );
		if ( ! $row ) {
			return false;
		}

		$ok = Registry::instance()->delete( $row['id'] );

		if ( $ok ) {
			$this->recompile();
			do_action( 'dashwoo_font_deleted', $slug );
		}

		return $ok;
	}

	/**
	 * Update one family: re-download only the weights that changed.
	 *
	 * @param string $slug Slug.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function update( $slug ) {
		$provider = Google_Fonts_Provider::instance();
		$row      = Registry::instance()->find_by_slug( 'font', $slug );

		if ( ! $row ) {
			return new \WP_Error( 'dashwoo_font_missing', __('Font not installed.', 'dashwoo') );
		}

		$check = $provider->check_update( $slug );

		if ( is_wp_error( $check ) ) {
			return $check;
		}
		if ( empty( $check['update'] ) ) {
			return array(
				'slug'    => $slug,
				'updated' => false,
				'reason'  => $check['reason'],
				'changed' => array(),
			);
		}

		$result = $provider->install(
			$slug,
			$check['changed'] ? $check['changed'] : (array) ( $row['meta']['weights'] ?? array( '400' ) ),
			(array) ( $row['meta']['subsets'] ?? array() )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result['updated'] = true;
		$result['reason']  = $check['reason'];
		$result['changed'] = (array) $check['changed'];

		$this->recompile();

		return $result;
	}

	/**
	 * Compile + remember the stylesheet.
	 *
	 * @return array<string,mixed>
	 */
	public function recompile() {
		$this->compiled = Font_Face_Compiler::compile( $this->active() );

		update_option( 'dashwoo_fonts_hash', $this->compiled['hash'], false );

		return $this->compiled;
	}

	/**
	 * Compiled stylesheet info (compiling on demand).
	 *
	 * @return array<string,mixed>
	 */
	public function compiled() {
		if ( null === $this->compiled ) {
			$path = Storage::instance()->path( 'fonts' ) . 'dw-fonts.css';

			if ( ! is_readable( $path ) ) {
				return $this->recompile();
			}

			$css            = (string) Filesystem::get( $path );
			$this->compiled = array(
				'path'  => $path,
				'url'   => Storage::instance()->url( 'fonts' ) . 'dw-fonts.css',
				'hash'  => substr( md5( $css ), 0, 12 ),
				'bytes' => strlen( $css ),
			);
		}

		return $this->compiled;
	}

	/**
	 * Enqueue the locally hosted stylesheet.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! dashwoo_is_on( 'general.enabled' ) ) {
			return;
		}

		$compiled = $this->compiled();

		if ( empty( $compiled['url'] ) || 0 === (int) $compiled['bytes'] ) {
			return;
		}

		wp_enqueue_style( self::HANDLE, $compiled['url'], array(), $compiled['hash'] );
	}

	/**
	 * Preload the default family (performance setting).
	 *
	 * @return void
	 */
	public function preload_default() {
		if ( ! dashwoo_is_on( 'assets_fonts.preload_default' ) || ! dashwoo_is_on( 'performance.preload_fonts' ) ) {
			return;
		}

		$font = $this->default_font();
		if ( ! $font ) {
			return;
		}

		foreach ( (array) ( $font['meta']['faces'] ?? array() ) as $face ) {
			if ( '400' !== (string) $face['weight'] ) {
				continue;
			}
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous" />' . "\n",
				esc_url( $face['url'] )
			);
			break;
		}
	}

	/**
	 * Make the local families visible in Elementor's font pickers.
	 *
	 * @param array<string,string> $fonts family => group.
	 * @return array<string,string>
	 */
	public function elementor_fonts( $fonts ) {
		if ( ! dashwoo_is_on( 'assets_fonts.sync_elementor' ) ) {
			return $fonts;
		}

		foreach ( $this->active() as $row ) {
			$family = ! empty( $row['meta']['family'] ) ? $row['meta']['family'] : $row['label'];

			if ( '' !== $family ) {
				$fonts[ $family ] = 'dashwoo';
			}
		}

		/**
		 * Filter the Elementor font groups after DashWoo added its families.
		 *
		 * @param array<string,string> $fonts Fonts.
		 */
		return apply_filters( 'dashwoo_elementor_font_families', $fonts );
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
