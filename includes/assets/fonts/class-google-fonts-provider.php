<?php
/**
 * Google Fonts provider: search -> CSS2 -> download -> verify -> local.
 *
 * After a family is installed, DashWoo never talks to Google again for it.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Fonts;

use DashWoo\Assets\Registry;
use DashWoo\Assets\Storage;
use DashWoo\Support\Filesystem;
use DashWoo\Support\Http;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Remote provider (admin import only).
 */
final class Google_Fonts_Provider {

	const CATALOG_FILE = 'assets/data/google-fonts.json';
	const CSS_ENDPOINT = 'https://fonts.googleapis.com/css2';
	const MAX_BYTES    = 5242880;
	const MAGIC        = array( 'wOF2', 'wOFF', 'OTTO' );

	/**
	 * Singleton.
	 *
	 * @var Google_Fonts_Provider|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Google_Fonts_Provider
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Bundled catalogue (offline, ships with the plugin).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function catalog() {
		$file = DASHWOO_DIR . self::CATALOG_FILE;

		if ( ! is_readable( $file ) ) {
			return array();
		}

		$raw = Filesystem::get( $file );
		if ( false === $raw ) {
			return array();
		}

		$data = json_decode( $raw, true );
		$list = isset( $data['fonts'] ) && is_array( $data['fonts'] ) ? $data['fonts'] : array();

		/**
		 * Filter the bundled font catalogue.
		 *
		 * @param array<int,array<string,mixed>> $list Catalogue.
		 */
		return apply_filters( 'dashwoo_font_catalog', $list );
	}

	/**
	 * Search the catalogue.
	 *
	 * @param string              $query   Free text.
	 * @param array<string,mixed> $filters persian (bool), category (string).
	 * @return array<int,array<string,mixed>>
	 */
	public function search( $query = '', $filters = array() ) {
		$query = strtolower( trim( (string) $query ) );
		$out   = array();

		foreach ( $this->catalog() as $font ) {
			if ( '' !== $query ) {
				$haystack = strtolower( $font['family'] . ' ' . $font['slug'] );
				if ( false === strpos( $haystack, $query ) ) {
					continue;
				}
			}

			if ( ! empty( $filters['persian'] ) && empty( $font['persian'] ) ) {
				continue;
			}
			if ( ! empty( $filters['category'] ) && $filters['category'] !== $font['category'] ) {
				continue;
			}

			$out[] = $font;
		}

		return $out;
	}

	/**
	 * Find one catalogue entry.
	 *
	 * @param string $family Family name or slug.
	 * @return array<string,mixed>|null
	 */
	public function find( $family ) {
		$needle = strtolower( trim( (string) $family ) );

		foreach ( $this->catalog() as $font ) {
			if ( strtolower( $font['family'] ) === $needle || strtolower( $font['slug'] ) === $needle ) {
				return $font;
			}
		}

		return null;
	}

	/**
	 * Build the CSS2 request URL.
	 *
	 * @param string             $family  Family name.
	 * @param array<int,string>  $weights Weights.
	 * @param array<int,string>  $subsets Subsets.
	 * @return string
	 */
	public function css_url( $family, $weights = array( '400' ), $subsets = array() ) {
		$weights = array_values( array_unique( array_filter( array_map( 'strval', (array) $weights ) ) ) );
		sort( $weights );

		$family_param = str_replace( ' ', '+', trim( (string) $family ) );
		if ( $weights ) {
			$family_param .= ':wght@' . implode( ';', $weights );
		}

		// Built by hand on purpose: Google expects "+" for spaces and raw ";" ":"
		// separators, which http_build_query() would percent-encode.
		$family_param = preg_replace( '/[^A-Za-z0-9+:;@,\-]/', '', $family_param );

		$query = 'family=' . $family_param . '&display=swap';

		if ( $subsets ) {
			$query .= '&subset=' . implode( ',', array_map( 'sanitize_key', (array) $subsets ) );
		}

		return self::CSS_ENDPOINT . '?' . $query;
	}

	/**
	 * Parse a CSS2 response into faces.
	 *
	 * @param string $css CSS text.
	 * @return array<int,array<string,mixed>>
	 */
	public function parse_css( $css ) {
		$css   = (string) $css;
		$faces = array();

		if ( false === stripos( $css, '@font-face' ) ) {
			return $faces;
		}

		if ( ! preg_match_all( '/@font-face\s*\{(.*?)\}/s', $css, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $faces;
		}

		foreach ( $matches[1] as $index => $entry ) {
			$block  = $entry[0];
			$offset = (int) $matches[0][ $index ][1];

			// Google writes the subset as a comment right before the block:
			//   /* arabic */ @font-face { ... }
			$subset = 'default';
			$before = substr( $css, max( 0, $offset - 120 ), min( 120, $offset ) );
			if ( preg_match( '#/\*\s*([a-z0-9\-]+)\s*\*/\s*$#i', $before, $sm ) ) {
				$subset = strtolower( $sm[1] );
			}

			$url = '';
			if ( preg_match( '/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $block, $m ) ) {
				$url = trim( $m[1] );
			}
			if ( '' === $url ) {
				continue;
			}

			$weight = '400';
			if ( preg_match( '/font-weight\s*:\s*([0-9]{3})/i', $block, $m ) ) {
				$weight = $m[1];
			}

			$style = 'normal';
			if ( preg_match( '/font-style\s*:\s*(italic|oblique|normal)/i', $block, $m ) ) {
				$style = strtolower( $m[1] );
			}

			$range = '';
			if ( preg_match( '/unicode-range\s*:\s*([^;}]+)/i', $block, $m ) ) {
				$range = trim( $m[1] );
			}

			$format = '';
			if ( preg_match( '/format\(\s*[\'"]?([a-z0-9\-]+)[\'"]?\s*\)/i', $block, $m ) ) {
				$format = strtolower( $m[1] );
			}

			$faces[] = array(
				'url'           => $url,
				'weight'        => $weight,
				'style'         => $style,
				'subset'        => $subset,
				'unicode_range' => $range,
				'format'        => $format ? $format : 'woff2',
			);
		}

		return $faces;
	}

	/**
	 * Fetch the CSS then the files, store them locally and register metadata.
	 *
	 * @param string            $family  Family name or slug.
	 * @param array<int,string> $weights Weights to install.
	 * @param array<int,string> $subsets Subsets.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function install( $family, $weights = array( '400' ), $subsets = array() ) {
		if ( ! dashwoo_is_on( 'fonts_google.allow_download' ) ) {
			return new \WP_Error( 'dashwoo_download_disabled', __('Downloading Google Fonts is disabled in the settings.', 'dashwoo') );
		}

		$entry = $this->find( $family );
		if ( ! $entry ) {
			return new \WP_Error( 'dashwoo_font_unknown', sprintf( __('Font "%s" is not in the DashWoo catalogue.', 'dashwoo'), (string) $family ) );
		}

		$requested = array_values( array_intersect( (array) $weights, (array) $entry['weights'] ) );
		if ( ! $requested ) {
			$requested = array( '400' );
		}

		if ( ! $subsets ) {
			$subsets = (array) dashwoo_get_setting( 'fonts_google.subsets', array( 'arabic', 'latin' ) );
		}

		$css_url = $this->css_url( $entry['family'], $requested, $subsets );
		$css     = Http::get( $css_url );

		if ( is_wp_error( $css ) ) {
			return $css;
		}
		if ( 200 !== (int) $css['code'] ) {
			return new \WP_Error( 'dashwoo_css_status', sprintf( __('CSS2 responded with %d.', 'dashwoo'), (int) $css['code'] ) );
		}

		$faces = $this->parse_css( $css['body'] );
		if ( ! $faces ) {
			return new \WP_Error( 'dashwoo_css_parse', 'No @font-face rules found in the CSS2 response.' );
		}

		$slug     = $entry['slug'];
		$registry = Registry::instance();
		$existing = $registry->find_by_slug( 'font', $slug );
		$max_mb   = (int) dashwoo_get_setting( 'fonts_google.max_file_mb', 5 );
		$storage  = Storage::instance();
		$dir      = $storage->path( 'fonts' ) . $slug;
		$files    = array();
		$errors   = array();

		// Faces already registered (a subset of them may be re-downloaded now).
		$known = array();
		foreach ( (array) ( $existing['meta']['faces'] ?? array() ) as $stored_face ) {
			$known[ self::face_key( $stored_face ) ] = $stored_face;
		}

		Filesystem::mkdir( $dir );

		foreach ( $faces as $face ) {
			$weight = (string) $face['weight'];
			if ( ! in_array( $weight, $requested, true ) ) {
				continue;
			}

			// Subsets are separate files upstream: the local name has to keep them
			// apart, otherwise the arabic face would overwrite the latin one.
			$name = sprintf(
				'%s-%s-%s.woff2',
				$weight,
				$face['style'],
				isset( $face['subset'] ) ? $face['subset'] : 'default'
			);
			$dest = trailingslashit( $dir ) . $name;

			$result = Http::download( $face['url'], $dest, $max_mb * 1048576, self::MAGIC );

			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'weight'  => $weight,
					'message' => $result->get_error_message(),
				);
				continue;
			}

			$downloaded = array(
				'file'          => $name,
				'subset'        => isset( $face['subset'] ) ? $face['subset'] : 'default',
				'path'          => $storage->relative( $result['path'] ),
				// url = the LOCAL file that goes into @font-face,
				// source_url = where it came from, used to detect upstream changes.
				'url'           => $storage->url( 'fonts' ) . $slug . '/' . $name,
				'source_url'    => $face['url'],
				'weight'        => $weight,
				'style'         => $face['style'],
				'unicode_range' => $face['unicode_range'],
				'bytes'         => (int) $result['bytes'],
				'sha256'        => $result['sha256'],
			);

			$files[] = $downloaded;
			$known[ self::face_key( $downloaded ) ] = $downloaded;
		}

		if ( ! $files ) {
			return new \WP_Error( 'dashwoo_download_failed', __('No font file could be downloaded.', 'dashwoo') );
		}

		$all_faces = array_values( $known );
		$weights   = array_values( array_unique( array_merge( $requested, array_map( 'strval', array_column( $all_faces, 'weight' ) ) ) ) );
		sort( $weights, SORT_NUMERIC );

		$all_subsets = array_values(
			array_unique(
				array_merge(
					array_map( 'strval', (array) $subsets ),
					array_map( 'strval', array_column( $all_faces, 'subset' ) )
				)
			)
		);

		$meta = array(
			'family'    => $entry['family'],
			'slug'      => $slug,
			'category'  => $entry['category'],
			'persian'   => ! empty( $entry['persian'] ),
			'weights'   => $weights,
			'subsets'   => $all_subsets,
			'faces'     => $all_faces,
			'files'     => array_column( $all_faces, 'file' ),
			'css_hash'  => md5( (string) $css['body'] ),
			'provider'  => 'google',
			'source'    => $css_url,
			'license'   => 'OFL-1.1',
			'local'     => true,
			'updated_at' => gmdate( 'c' ),
			'bytes'     => array_sum( array_map( 'intval', array_column( $all_faces, 'bytes' ) ) ),
			'errors'    => $errors,
		);

		$id = $registry->upsert(
			array(
				'type'       => 'font',
				'group_key'  => 'typography',
				'slug'       => $slug,
				'label'      => $entry['family'],
				'provider'   => 'google',
				'version'    => substr( md5( (string) $css['body'] ), 0, 8 ),
				'status'     => 'active',
				'is_default' => $existing
					? (bool) $existing['is_default']
					: ( 0 === count( $registry->query( array( 'type' => 'font' ) ) ) ),
				'role'       => 'body',
				'path'       => 'fonts/' . $slug,
				'url'        => $storage->url( 'fonts' ) . $slug . '/',
				'size'       => (int) $meta['bytes'],
				'meta'       => $meta,
			)
		);

		do_action( 'dashwoo_font_installed', $slug, $meta );

		Logger::instance()->info( 'Font installed', array( 'family' => $entry['family'], 'weights' => $requested ) );

		return array(
			'id'        => $id,
			'slug'      => $slug,
			'label'     => $entry['family'],
			'downloaded' => array_column( $files, 'file' ),
			'meta'      => $meta,
			'errors'    => $errors,
		);
	}

	/**
	 * Is a newer version of the family available upstream?
	 *
	 * Compares the CSS2 response hash with the stored one; only changed weights
	 * are re-downloaded, which is what the "update only what changed" rule asks for.
	 *
	 * @param string $slug Installed slug.
	 * @return array{update:bool,reason:string,changed:array<int,string>}|\WP_Error
	 */
	public function check_update( $slug ) {
		$row = Registry::instance()->find_by_slug( 'font', $slug );

		if ( ! $row ) {
			return new \WP_Error( 'dashwoo_font_missing', sprintf( __('Font "%s" is not installed.', 'dashwoo'), (string) $slug ) );
		}

		// Local integrity first: missing/renamed files force a re-install.
		$missing = $this->missing_files( $row );
		if ( $missing ) {
			return array(
				'update'  => true,
				'reason'  => 'missing_files',
				'changed' => $missing,
			);
		}

		$meta = $row['meta'];
		if ( empty( $meta['source'] ) ) {
			return array(
				'update'  => false,
				'reason'  => 'local_asset',
				'changed' => array(),
			);
		}

		$response = Http::get( $meta['source'] );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$hash = md5( (string) $response['body'] );

		if ( isset( $meta['css_hash'] ) && $hash === $meta['css_hash'] ) {
			return array(
				'update'  => false,
				'reason'  => 'up_to_date',
				'changed' => array(),
			);
		}

		$faces   = $this->parse_css( $response['body'] );
		$changed = array();

		foreach ( (array) ( $meta['weights'] ?? array() ) as $weight ) {
			$remote = '';
			foreach ( $faces as $face ) {
				if ( (string) $face['weight'] === (string) $weight ) {
					$remote = $face['url'];
					break;
				}
			}

			// A weight that is not part of this response (because only the requested
			// weights were fetched) is not a change.
			if ( '' === $remote ) {
				continue;
			}
			$local = '';
			foreach ( (array) ( $meta['faces'] ?? array() ) as $stored ) {
				if ( (string) $stored['weight'] === (string) $weight ) {
					// Compare against the upstream origin, never the local uploads URL.
					$local = isset( $stored['source_url'] ) ? $stored['source_url'] : '';
					break;
				}
			}
			if ( $remote !== $local ) {
				$changed[] = (string) $weight;
			}
		}

		if ( ! $changed ) {
			// The stylesheet moved but every file URL is identical: just re-record
			// the new hash so the next check is a no-op.
			$meta['css_hash'] = $hash;
			Registry::instance()->update( $row['id'], array( 'meta' => $meta ) );

			return array(
				'update'  => false,
				'reason'  => 'css_changed_only',
				'changed' => array(),
			);
		}

		return array(
			'update'  => true,
			'reason'  => 'remote_changed',
			'changed' => $changed,
		);
	}

	/**
	 * Stable identity of a face inside the registry metadata.
	 *
	 * @param array<string,mixed> $face Face row.
	 * @return string
	 */
	public static function face_key( array $face ) {
		return sprintf(
			'%s-%s-%s',
			isset( $face['weight'] ) ? $face['weight'] : '400',
			isset( $face['style'] ) ? $face['style'] : 'normal',
			isset( $face['subset'] ) ? $face['subset'] : 'default'
		);
	}

	/**
	 * Which stored files are gone from disk?
	 *
	 * @param array<string,mixed> $row Registry row.
	 * @return array<int,string> Weights with a missing file.
	 */
	public function missing_files( array $row ) {
		$storage = Storage::instance();
		$missing = array();

		foreach ( (array) ( $row['meta']['faces'] ?? array() ) as $face ) {
			$abs = $storage->absolute( isset( $face['path'] ) ? $face['path'] : '' );

			if ( '' === $abs || ! is_readable( $abs ) ) {
				$missing[] = (string) $face['weight'];
			}
		}

		return array_values( array_unique( $missing ) );
	}
}
