<?php
/**
 * Elementor widget: {title}.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * جزئیات حساب
 */
class Account_Details_Widget extends Account_Endpoint_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_details';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — جزئیات حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Endpoint.
	 *
	 * @return string
	 */
	protected function endpoint() {
		return 'edit-account';
	}

	/**
	 * DashWoo fallback block.
	 *
	 * @return string
	 */
	protected function fallback_view() {
		return 'edit_account';
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
