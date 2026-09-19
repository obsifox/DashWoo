<?php
/**
 * Elementor widget: {title}.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * خروج از حساب
 */
class Account_Logout_Widget extends Account_Endpoint_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_logout';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — خروج از حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-exit';
	}

	/**
	 * Endpoint.
	 *
	 * @return string
	 */
	protected function endpoint() {
		return 'customer-logout';
	}

	/**
	 * DashWoo fallback block.
	 *
	 * @return string
	 */
	protected function fallback_view() {
		return 'customer_logout';
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
