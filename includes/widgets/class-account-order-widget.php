<?php
/**
 * Elementor widget: a single order (the details view).
 *
 * This is the widget that makes "Open an order and see its details right there" possible
 * outside the panel too: drop it in a column, pick a template (summary / table / items
 * only / plain) and it draws the order exactly like the panel's content card does.
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
		return __( 'DashWoo — Single order', 'dashwoo' );
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
		return array_merge( parent::get_keywords(), array( 'order', __( 'Order', 'dashwoo' ), 'order view', 'invoice' ) );
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
			array( 'label' => __( 'Which order?', 'dashwoo' ) )
		);

		$this->add_control(
			'dw_order_id',
			array(
				'label'       => __( 'Order ID', 'dashwoo' ),
				'type'        => 'number',
				'default'     => 0,
				'min'         => 0,
				'description' => __( 'Zero = the customer\'s latest order. When a customer clicks an order, that order opens inside the panel.', 'dashwoo' ),
			)
		);

		$this->end_controls_section();

		$this->register_endpoint_controls( 'no' );
	}
}
