<?php
/**
 * WooCommerce adapter + version-dispatched implementation.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility\Adapters;

use DashWoo\Compatibility\Abstract_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce adapter.
 */
class WooCommerce_Adapter extends Abstract_Adapter {  // Not final: third parties may extend a check set.

	const MIN_WC = '8.5';

	/**
	 * Resolved versioned implementation.
	 *
	 * @var object|null
	 */
	private $impl = null;

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
		$fallback = self::MIN_WC;

		if ( ! function_exists( 'dashwoo_get_setting' ) ) {
			return $fallback;
		}

		$value = dashwoo_get_setting( 'compatibility.min_wc', $fallback );

		return '' === trim( (string) $value ) ? $fallback : (string) $value;
	}

	public function id() {
		return 'woocommerce';
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
		return 'WooCommerce';
	}

	/**
	 * Is WooCommerce loaded?
	 *
	 * @return bool
	 */
	public function is_active() {
		return function_exists( 'dashwoo_has_woocommerce' ) ? dashwoo_has_woocommerce() : class_exists( '\WooCommerce' );
	}

	/**
	 * WooCommerce version.
	 *
	 * @return string
	 */
	public function version() {
		return function_exists( 'dashwoo_woocommerce_version' ) ? dashwoo_woocommerce_version() : ( defined( 'WC_VERSION' ) ? (string) WC_VERSION : '' );
	}

	/**
	 * Tested up to.
	 *
	 * @return string
	 */
	public function tested_up_to() {
		return '9.9';
	}

	/**
	 * Version bucket: 8 / 9 / unknown.
	 *
	 * @return string
	 */
	public function branch() {
		$major = (int) explode( '.', $this->normalize_version( $this->version() ) )[0];

		if ( $major >= 9 ) {
			return '9';
		}
		if ( $major >= 8 ) {
			return '8';
		}

		return 'unknown';
	}

	/**
	 * Version-dispatched implementation object.
	 *
	 * @return object|null
	 */
	public function impl() {
		if ( null !== $this->impl ) {
			return $this->impl;
		}

		$map = array(
			'9' => 'DashWoo\\Compatibility\\Adapters\\WooCommerce\\V9_Adapter',
			'8' => 'DashWoo\\Compatibility\\Adapters\\WooCommerce\\V8_Adapter',
		);

		$branch = $this->branch();
		if ( isset( $map[ $branch ] ) && class_exists( $map[ $branch ] ) ) {
			$this->impl = new $map[ $branch ]();
		}

		return $this->impl;
	}

	/**
	 * Convenience passthroughs used by widgets.
	 *
	 * @param string $method Method name.
	 * @param array<int,mixed> $args Arguments.
	 * @return mixed
	 */
	public function call( $method, array $args = array() ) {
		$impl = $this->impl();

		if ( ! $impl || ! method_exists( $impl, $method ) ) {
			return null;
		}

		return call_user_func_array( array( $impl, $method ), $args );
	}

	/**
	 * Collect checks.
	 *
	 * @return void
	 */
	protected function inspect() {
		$active = $this->is_active();
		$this->check(
			'wc_active',
			$active ? self::STATUS_OK : self::STATUS_WARNING,
			$active ? 'WooCommerce is active' : 'WooCommerce is not active - commerce widgets will be hidden'
		);
		$this->capability( 'wc_active', $active );

		$version = $this->version();
		$meets   = $active && $this->meets( $version, self::minimum_version() );

		$this->check(
			'wc_version',
			! $active ? self::STATUS_NA : ( $meets ? self::STATUS_OK : self::STATUS_WARNING ),
			$active
				? sprintf( 'WooCommerce %s (minimum %s)', $version, self::minimum_version() )
				: 'WooCommerce version not detected',
			array(
				'current'  => $version,
				'required' => self::minimum_version(),
				'branch'   => $this->branch(),
			)
		);
		$this->capability( 'wc_version', $meets );

		$has_blocks = $active && class_exists( '\Automattic\WooCommerce\Blocks\Package' );
		$this->check(
			'wc_blocks',
			$has_blocks ? self::STATUS_OK : self::STATUS_NA,
			$has_blocks ? 'WooCommerce Blocks detected (Cart/Checkout block aware)' : 'Legacy [woocommerce] shortcodes in use'
		);
		$this->capability( 'wc_blocks', $has_blocks );

		$has_hpos = $active && $this->meets( $version, '7.1' );
		$this->check(
			'hpos',
			$has_hpos ? self::STATUS_OK : self::STATUS_NA,
			$has_hpos ? 'HPOS-capable WooCommerce detected' : 'HPOS not applicable'
		);
		$this->capability( 'hpos', $has_hpos );

		$this->check(
			'wc_template_hooks',
			$active ? self::STATUS_OK : self::STATUS_NA,
			$active ? 'Woo template hooks reachable through the adapter' : 'Skipped (WooCommerce inactive)'
		);
		$this->capability( 'wc_template_hooks', $active );

		$this->inspect_feature_declarations( $active );
	}

	/**
	 * Check the WooCommerce feature declarations.
	 *
	 * WooCommerce only looks at plugins that advertise the `WC tested up to` header,
	 * and treats an undeclared plugin as incompatible with any feature whose default
	 * plugin compatibility is `incompatible` (High-Performance Order Storage is one):
	 * that is the "some of your active plugins are incompatible with currently enabled
	 * WooCommerce features" notice. DashWoo declares every feature on
	 * `before_woocommerce_init`; this row proves it landed.
	 *
	 * @param bool $active Is WooCommerce active?
	 * @return void
	 */
	protected function inspect_feature_declarations( $active ) {
		$status = \DashWoo\Compatibility\WooCommerce_Features::instance()->status();

		if ( ! $active || ! $status['aware'] ) {
			$this->check(
				'feature_declarations',
				self::STATUS_NA,
				'WooCommerce feature declarations skipped (WooCommerce inactive)',
				$status
			);

			return;
		}

		$incompatible = (array) $status['incompatible'];
		$uncertain    = (array) $status['uncertain'];
		$declared     = count( (array) $status['declared'] );

		if ( $incompatible || $uncertain ) {
			$this->check(
				'feature_declarations',
				self::STATUS_WARNING,
				sprintf(
					'WooCommerce still lists DashWoo as undeclared for: %s',
					implode( ', ', array_merge( $incompatible, $uncertain ) )
				),
				array_merge(
					$status,
					array( 'hint' => 'اعلان سازگاری روی قلاب before_woocommerce_init اجرا می‌شود؛ اگر این پیام ماند، یعنی ووکامرس آن ویژگی را بعد از این قلاب ثبت کرده است.' )
				)
			);

			return;
		}

		$unknown = count( (array) $status['unknown'] );

		$this->check(
			'feature_declarations',
			self::STATUS_OK,
			sprintf(
				'%d WooCommerce features declared compatible%s',
				$declared ? $declared : (int) $status['total'],
				$unknown ? sprintf( ' (%d not in the audit list)', $unknown ) : ''
			),
			array_merge(
				$status,
				array( 'hint' => 'ووکامرس دیگر DashWoo را ناسازگار نمی‌بیند: نه در فهرست افزونه‌های ناسازگار، نه در هشدار «افزونه‌های ناسازگار».' )
			)
		);
	}
}
