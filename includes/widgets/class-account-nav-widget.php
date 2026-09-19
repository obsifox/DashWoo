<?php
/**
 * Elementor widget: منوی حساب کاربری (navigation).
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Account navigation widget.
 */
class Account_Nav_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_nav';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — منوی حساب کاربری';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'nav';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_nav',
			array( 'label' => 'منوی حساب' )
		);

		$this->add_control(
			'dw_layout',
			array(
				'label'   => 'چیدمان',
				'type'    => 'select',
				'default' => 'menu',
				'options' => array(
					'menu'  => 'فهرست عمودی',
					'tabs'  => 'نوار تب افقی',
					'cards' => 'کارت‌های میان‌بر',
				),
			)
		);

		$this->add_control(
			'dw_icons',
			array(
				'label'   => 'آیکون‌ها',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_counts',
			array(
				'label'       => 'شمارنده (سفارش‌ها / دانلودها)',
				'type'        => 'switcher',
				'default'     => '',
				'description' => 'فقط برای مواردی که DashWoo می‌تواند با API عمومی بشمارد.',
			)
		);

		$this->add_control(
			'dw_sticky',
			array(
				'label'   => 'چسبیدن منو در اسکرول',
				'type'    => 'switcher',
				'default' => '',
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * View args.
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		$settings = (array) $this->get_settings_for_display();

		$layout = isset( $settings['dw_layout'] ) ? sanitize_key( (string) $settings['dw_layout'] ) : 'menu';

		if ( ! in_array( $layout, array( 'menu', 'tabs', 'cards' ), true ) ) {
			$layout = 'menu';
		}

		return array(
			'layout' => $layout,
			'icons'  => 'yes' === ( $settings['dw_icons'] ?? 'yes' ),
			'counts' => 'yes' === ( $settings['dw_counts'] ?? '' ),
			'sticky' => 'yes' === ( $settings['dw_sticky'] ?? '' ),
		);
	}
}
