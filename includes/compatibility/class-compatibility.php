<?php
/**
 * Version Compatibility Layer.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility;

use DashWoo\Compatibility\Adapters\Asset_Adapter;
use DashWoo\Compatibility\Adapters\Elementor_Adapter;
use DashWoo\Compatibility\Adapters\WooCommerce_Adapter;
use DashWoo\Compatibility\Adapters\WordPress_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the adapters + the current runtime mode.
 */
final class Compatibility {

	const OPTION = 'dashwoo_compatibility';
	const CACHE_TTL = 300;

	/**
	 * Singleton.
	 *
	 * @var Compatibility|null
	 */
	private static $instance = null;

	/**
	 * Adapters.
	 *
	 * @var array<string,Interface_Adapter>
	 */
	private $adapters = array();

	/**
	 * Last computed report.
	 *
	 * @var Version_Report|null
	 */
	private $report = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Compatibility
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
		add_action( 'admin_notices', array( $this, 'maybe_render_notice' ) );
		add_action( 'dashwoo_check_compatibility', array( $this, 'refresh' ) );
	}

	/**
	 * Register the four adapters (extensible through a filter).
	 *
	 * @return array<string,Interface_Adapter>
	 */
	public function adapters() {
		if ( ! $this->adapters ) {
			$defaults = array(
				new WordPress_Adapter(),
				new WooCommerce_Adapter(),
				new Elementor_Adapter(),
				new Asset_Adapter(),
			);

			/**
			 * Filter the adapter list.
			 *
			 * @param array<int,Interface_Adapter> $defaults Adapters.
			 */
			$list = apply_filters( 'dashwoo_adapters', $defaults );

			foreach ( (array) $list as $adapter ) {
				if ( $adapter instanceof Interface_Adapter ) {
					$this->adapters[ $adapter->id() ] = $adapter;
				}
			}
		}

		return $this->adapters;
	}

	/**
	 * Get one adapter.
	 *
	 * @param string $id Adapter id.
	 * @return Interface_Adapter|null
	 */
	public function adapter( $id ) {
		$adapters = $this->adapters();

		return isset( $adapters[ $id ] ) ? $adapters[ $id ] : null;
	}

	/**
	 * Build a fresh report by asking every adapter.
	 *
	 * @return Version_Report
	 */
	public function build_report() {
		$reports = array();

		foreach ( $this->adapters() as $id => $adapter ) {
			$reports[ $id ]             = $adapter->report();
			$reports[ $id ]['required'] = (bool) $adapter->is_required();
		}

		return new Version_Report( $reports );
	}

	/**
	 * Report (memoised per request).
	 *
	 * @return Version_Report
	 */
	public function report() {
		if ( null === $this->report ) {
			$this->report = $this->build_report();
		}

		return $this->report;
	}

	/**
	 * Persist a fresh report and return it.
	 *
	 * @return Version_Report
	 */
	public function refresh() {
		$this->report = $this->build_report();

		update_option( self::OPTION, $this->report->to_array(), true );

		return $this->report;
	}

	/**
	 * Stored report (or a fresh one when missing).
	 *
	 * @return array<string,mixed>
	 */
	public function stored() {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) || empty( $stored['checks'] ) ) {
			$stored = $this->refresh()->to_array();
		}

		return $stored;
	}

	/**
	 * "full" | "compatibility"
	 *
	 * @return string
	 */
	public function mode() {
		$requested = dashwoo_get_setting( 'general.mode', 'full' );

		if ( 'full' !== $requested ) {
			return 'compatibility';
		}

		$stored = $this->stored();
		$forced = isset( $stored['compatibility'] ) && 'compatibility' === $stored['compatibility'];

		if ( $forced ) {
			return 'compatibility';
		}

		return 'full';
	}

	/**
	 * Is a feature available in the current mode + environment?
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function supports( $feature ) {
		if ( 'compatibility' === $this->mode() ) {
			// Compatibility mode never removes assets, only template overrides.
			$always = array( 'asset_storage', 'cdn_free', 'css_variables', 'variable_fonts', 'svg_sanitizer' );
			if ( in_array( $feature, $always, true ) ) {
				return true;
			}
		}

		foreach ( $this->adapters() as $adapter ) {
			$caps = $adapter->capabilities();
			if ( array_key_exists( $feature, $caps ) ) {
				return (bool) $caps[ $feature ];
			}
		}

		return false;
	}

	/**
	 * Raw environment facts, for support tickets and host migrations.
	 *
	 * Deliberately *facts only* (no judgement): extension_loaded() vs
	 * function_exists(), disable_functions, ini limits, uploads writability.
	 * That is what a host needs to answer "please enable X".
	 *
	 * @return array<string,mixed>
	 */
	public function diagnostics() {
		$storage = \DashWoo\Assets\Storage::instance();
		$report  = $this->report();

		$extensions = array();
		$probe      = array( 'gd', 'zip', 'imagick', 'mbstring', 'curl', 'json', 'openssl', 'intl', 'iconv', 'zlib', 'exif', 'fileinfo' );

		foreach ( $probe as $extension ) {
			$extensions[ $extension ] = extension_loaded( $extension );
		}

		$gd_formats = array();

		foreach ( array( 'jpeg', 'png', 'gif', 'webp', 'avif' ) as $format ) {
			$function = 'imagecreatefrom' . $format;

			if ( function_exists( $function ) ) {
				$gd_formats[] = $format;
			}
		}

		$disabled = (string) ini_get( 'disable_functions' );

		return array(
			'generated_at' => gmdate( 'c' ),
			'dashwoo'      => array(
				'version'       => DASHWOO_VERSION,
				'mode'          => $this->mode(),
				'status'        => $report->status(),
				'summary'       => $report->summary(),
				'revision'      => \DashWoo\Settings\Settings::instance()->revision(),
				'storage'       => array(
					'basedir'  => $storage->basedir(),
					'writable' => is_writable( $storage->basedir() ),
					'guard'    => file_exists( $storage->basedir() . '/.htaccess' ),
					'types'    => $storage->stats(),
				),
			),
			'php'          => array(
				'version'            => PHP_VERSION,
				'interface'          => PHP_SAPI,
				'os'                 => PHP_OS . ' ' . php_uname( 'm' ),
				'memory_limit'       => (string) ini_get( 'memory_limit' ),
				'max_execution_time' => (string) ini_get( 'max_execution_time' ),
				'upload_max_filesize' => (string) ini_get( 'upload_max_filesize' ),
				'post_max_size'      => (string) ini_get( 'post_max_size' ),
				'max_input_vars'     => (string) ini_get( 'max_input_vars' ),
				'timezone'           => date_default_timezone_get(),
			),
			'capabilities' => array(
				'extensions'        => $extensions,
				'disable_functions' => '' !== trim( $disabled ) ? array_map( 'trim', explode( ',', $disabled ) ) : array(),
				'gd_formats'        => $gd_formats,
				'gd_loaded'         => extension_loaded( 'gd' ),
				'gd_usable'         => function_exists( 'imagecreatetruecolor' ),
				'zip_available'     => class_exists( '\\ZipArchive' ),
				'imagick'           => class_exists( '\\Imagick' ),
			),
			'environment'  => array(
				'wordpress'  => function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : '',
				'multisite'  => function_exists( 'is_multisite' ) && is_multisite(),
				'woocommerce' => array(
					'active'  => class_exists( '\\WooCommerce' ),
					'version' => function_exists( 'dashwoo_woocommerce_version' ) ? dashwoo_woocommerce_version() : ( defined( 'WC_VERSION' ) ? (string) WC_VERSION : '' ),
				),
				'elementor'  => array(
					'active'  => did_action( 'elementor/loaded' ) > 0 || defined( 'ELEMENTOR_VERSION' ),
					'version' => defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : '',
					'pro'     => defined( 'ELEMENTOR_PRO_VERSION' ) ? (string) ELEMENTOR_PRO_VERSION : '',
				),
			),
		);
	}

	/**
	 * Admin notice when the environment is degraded (not on the DashWoo screens,
	 * those render the full report themselves).
	 *
	 * @return void
	 */
	public function maybe_render_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, 'dashwoo' ) ) {
			return;
		}

		$stored = $this->stored();
		if ( empty( $stored['checks'] ) ) {
			return;
		}

		$summary = isset( $stored['summary'] ) ? $stored['summary'] : array();

		if ( empty( $summary['warning'] ) && empty( $summary['error'] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html(
				sprintf(
					/* translators: 1: warnings, 2: errors */
					__( 'dashwoo: %1$d warnings and %2$d errors in the environment compatibility check.', 'dashwoo' ),
					(int) $summary['warning'],
					(int) $summary['error']
				)
			),
			esc_url( admin_url( 'admin.php?page=dashwoo&section=system' ) ),
			esc_html__( __( 'View the system status', 'dashwoo' ), 'dashwoo' )
		);
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->adapters = array();
		$this->report   = null;
	}
}
