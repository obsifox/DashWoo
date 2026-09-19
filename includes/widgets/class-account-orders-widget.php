<?php
/**
 * Elementor widget: {title}.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * سفارش‌های حساب
 */
class Account_Orders_Widget extends Account_Endpoint_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_orders';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — سفارش‌های حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-woo-orders';
	}

	/**
	 * Endpoint.
	 *
	 * @return string
	 */
	protected function endpoint() {
		return 'orders';
	}

	/**
	 * DashWoo fallback block.
	 *
	 * @return string
	 */
	protected function fallback_view() {
		return 'orders';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_endpoint_controls( 'yes' );
	}
}
