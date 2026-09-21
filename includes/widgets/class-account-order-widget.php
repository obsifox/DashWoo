<?php
/**
 * Elementor widget: جزئیات یک سفارش (تک سفارش).
 *
 * This is the widget that makes "وارد سفارش شو و جزئیاتش را همان‌جا ببین" possible
 * outside the panel too: drop it in a column, pick a template (خلاصه / جدول / فقط
 * اقلام / خام) and it draws the order exactly like the panel's content card does.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Single order widget.
 */
class Account_Order_Widget extends Account_Endpoint_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_order';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — جزئیات یک سفارش';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-info';
	}

	/**
	 * Keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords() {
		return array_merge( parent::get_keywords(), array( 'order', 'سفارش', 'order view', 'invoice' ) );
	}

	/**
	 * Endpoint.
	 *
	 * @return string
	 */
	protected function endpoint() {
		return 'view-order';
	}

	/**
	 * The DashWoo block drawn when WooCommerce has no content for this order.
	 *
	 * @return string
	 */
	protected function fallback_view() {
		return 'order';
	}

	/**
	 * A template section of its own, so it can be picked in the settings too.
	 *
	 * @return string
	 */
	protected function template_section() {
		return 'order';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_order_source',
			array( 'label' => 'کدام سفارش؟' )
		);

		$this->add_control(
			'dw_order_id',
			array(
				'label'       => 'شناسهٔ سفارش',
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => 'صفر = آخرین سفارش مشتری. وقتی مشتری روی یک سفارش کلیک می‌کند، همان سفارش داخل پنل باز می‌شود.',
			)
		);

		$this->end_controls_section();

		$this->register_endpoint_controls( 'no' );
	}
}
