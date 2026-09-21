<?php
/**
 * Elementor (free + Pro) adapter.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters;

use DashWoo\Compatibility\Abstract_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor adapter.
 */
class Elementor_Adapter extends Abstract_Adapter {

	const MIN_ELEMENTOR = '3.24';
	const MIN_PRO       = '3.19';

	/**
	 * Adapter id.
	 *
	 * @return string
	 */
	/**
	 * Minimum supported version: the setting wins, the constant is the floor.
	 *
	 * @return string
	 */
	protected static function minimum_version() {
		$fallback = self::MIN_ELEMENTOR;

		if ( ! function_exists( 'dashwoo_get_setting' ) ) {
			return $fallback;
		}

		$value = dashwoo_get_setting( 'compatibility.min_elementor', $fallback );

		return '' === trim( (string) $value ) ? $fallback : (string) $value;
	}

	public function id() {
		return 'elementor';
	}

	/**
	 * DashWoo keeps running without this component - it only loses part of its
	 * delivery, so a missing WooCommerce must not force Compatibility Mode.
	 *
	 * @return bool
	 */
	public function is_required() {
		return false;
	}

	/**
	 * Adapter label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Elementor';
	}

	/**
	 * Is Elementor loaded?
	 *
	 * @return bool
	 */
	public function is_active() {
		return did_action( 'elementor/loaded' ) > 0 || defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Elementor version.
	 *
	 * @return string
	 */
	public function version() {
		return defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : '';
	}

	/**
	 * Tested up to.
	 *
	 * @return string
	 */
	public function tested_up_to() {
		return '3.26';
	}

	/**
	 * Elementor Pro version (empty when free only).
	 *
	 * @return string
	 */
	public function pro_version() {
		return defined( 'ELEMENTOR_PRO_VERSION' ) ? (string) ELEMENTOR_PRO_VERSION : '';
	}

	/**
	 * Collect checks.
	 *
	 * @return void
	 */
	protected function inspect() {
		$active = $this->is_active();
		$this->check(
			'elementor_active',
			$active ? self::STATUS_OK : self::STATUS_WARNING,
			$active ? __('Elementor is active', 'dashwoo') : __('Elementor is not active - visual controls disabled', 'dashwoo')
		);
		$this->capability( 'elementor_active', $active );

		$version = $this->version();
		$meets   = $active && $this->meets( $version, self::minimum_version() );

		$this->check(
			'elementor_version',
			! $active ? self::STATUS_NA : ( $meets ? self::STATUS_OK : self::STATUS_WARNING ),
			$active
				? sprintf( __('Elementor %s (minimum %s)', 'dashwoo'), $version, self::minimum_version() )
				: __('Elementor version not detected', 'dashwoo'),
			array(
				'current'  => $version,
				'required' => self::minimum_version(),
			)
		);
		$this->capability( 'elementor_version', $meets );

		$pro = $this->pro_version();
		$this->check(
			'elementor_pro',
			$pro ? self::STATUS_OK : self::STATUS_NA,
			$pro ? sprintf( __('Elementor Pro %s detected', 'dashwoo'), $pro ) : __('Elementor Pro not installed (optional)', 'dashwoo')
		);
		$this->capability( 'elementor_pro', '' !== $pro );

		$kit = $active && class_exists( '\Elementor\Plugin' );
		$this->check(
			'global_kit',
			$kit ? self::STATUS_OK : self::STATUS_NA,
			$kit ? __('Global Kit API reachable (optional token sync)', 'dashwoo') : __('Kit API unavailable', 'dashwoo')
		);
		$this->capability( 'elementor_kit', $kit );

		$css_vars = $active && $this->meets( $version, '3.0' );
		$this->check(
			'css_variables',
			$css_vars ? self::STATUS_OK : self::STATUS_NA,
			$css_vars ? __('CSS custom properties supported by the frontend', 'dashwoo') : __('CSS custom properties unavailable', 'dashwoo')
		);
		$this->capability( 'css_variables', $css_vars );

		$experiments = $active && $this->meets( $version, '3.5' );
		$this->check(
			'experiments',
			$experiments ? self::STATUS_OK : self::STATUS_NA,
			$experiments ? __('Experiments API available', 'dashwoo') : __('Experiments API unavailable', 'dashwoo')
		);
		$this->capability( 'experiments', $experiments );
	}
}
