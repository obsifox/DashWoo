<?php
/**
 * Makes the shop owner's choices true for WooCommerce's *own* account menu too.
 *
 * Replacing the template is one thing; the default menu keeps working in themes,
 * page builders and the `[woocommerce_my_account]` shortcode. So DashWoo applies its
 * labels, order, icons and visibility to WooCommerce's filters as well - filtered,
 * never hard-coded, and only while the shop owner asked for it.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Bridges DashWoo endpoint settings into WooCommerce's menu filters.
 */
class Native_Bridge {

	/**
	 * Singleton.
	 *
	 * @var Native_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Native_Bridge
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook the WooCommerce menu filters.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! dashwoo_is_on( 'account_source.enabled' ) ) {
			return;
		}

		$priority = (int) dashwoo_get_setting( 'account_source.menu_priority', 20 );
		$priority = max( 1, min( 99, $priority ) );

		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_items' ), $priority, 1 );
		add_filter( 'woocommerce_get_endpoint_url', array( $this, 'endpoint_url' ), 10, 4 );
	}

	/**
	 * Rename / reorder / hide the native menu items.
	 *
	 * The logout entry is always last, exactly like WooCommerce has it.
	 *
	 * @param array<string,string> $items Menu items (endpoint => label).
	 * @return array<string,string>
	 */
	public function menu_items( $items ) {
		$items = is_array( $items ) ? $items : array();

		// Re-entrancy guard: this filter runs *inside* WooCommerce's own
		// wc_get_account_menu_items(), which Endpoints uses to learn the live list.
		if ( Endpoints::instance()->probing() ) {
			return $items;
		}

		$wire = Endpoints::instance()->wire();

		foreach ( $wire as $id => $item ) {
			if ( empty( $item['live'] ) || empty( $items[ $id ] ) ) {
				continue;
			}

			// A hidden endpoint is removed for the front-end only; WooCommerce's own
			// handlers stay reachable, which keeps direct links working.
			if ( empty( $item['visible'] ) ) {
				unset( $items[ $id ] );

				continue;
			}

			$items[ $id ] = (string) $item['label'];
		}

		// Order: DashWoo's numbers first, then whatever the shop (or a plugin) added.
		$ordered = array();
		$weights = array();

		foreach ( $wire as $id => $item ) {
			if ( ! isset( $items[ $id ] ) ) {
				continue;
			}

			if ( 'customer-logout' === $id ) {
				continue;
			}

			$weights[ $id ] = (int) $item['order'];
		}

		asort( $weights, SORT_NUMERIC );

		foreach ( array_keys( $weights ) as $id ) {
			$ordered[ $id ] = $items[ $id ];
		}

		foreach ( $items as $id => $label ) {
			if ( 'customer-logout' === $id || isset( $ordered[ $id ] ) ) {
				continue;
			}

			$ordered[ $id ] = $label;
		}

		if ( isset( $items['customer-logout'] ) ) {
			$ordered['customer-logout'] = $items['customer-logout'];
		}

		/**
		 * Filter the native menu DashWoo hands back to WooCommerce.
		 *
		 * @param array<string,string> $ordered Menu items.
		 * @param array<string,string> $items   The items WooCommerce passed in.
		 */
		return (array) apply_filters( 'dashwoo_account_native_menu', $ordered, $items );
	}

	/**
	 * Keep the account URLs pointing where WooCommerce expects them.
	 *
	 * DashWoo does not rewrite endpoints; the filter exists so a shop can point an
	 * endpoint at a custom page (e.g. an Elementor page) without touching core.
	 *
	 * @param string $url      URL.
	 * @param string $endpoint Endpoint.
	 * @param string $value    Endpoint value.
	 * @param string $permalink Permalink.
	 * @return string
	 */
	public function endpoint_url( $url, $endpoint, $value = '', $permalink = '' ) {
		unset( $value );

		$overrides = (array) apply_filters( 'dashwoo_account_endpoint_targets', array(), $endpoint, $permalink );

		if ( isset( $overrides[ $endpoint ] ) && '' !== (string) $overrides[ $endpoint ] ) {
			return (string) $overrides[ $endpoint ];
		}

		return (string) $url;
	}
}
