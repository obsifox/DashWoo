<?php
/**
 * Elementor widget: {title}.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * آدرس‌های حساب
 */
class Account_Addresses_Widget extends Account_Endpoint_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_addresses';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — آدرس‌های حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-map-pin';
	}

	/**
	 * Endpoint.
	 *
	 * @return string
	 */
	protected function endpoint() {
		return 'edit-address';
	}

	/**
	 * DashWoo fallback block.
	 *
	 * @return string
	 */
	protected function fallback_view() {
		return 'edit_address';
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
