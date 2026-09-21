<?php
/**
 * Elementor widget: کارت پروفایل کاربر (account profile).
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Profile card widget.
 */
class Account_Profile_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_profile';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — پروفایل کاربر';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-box';
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'profile';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_profile',
			array( 'label' => 'کارت پروفایل' )
		);

		$this->add_control(
			'dw_variant',
			array(
				'label'   => 'حالت نمایش',
				'type'    => 'select',
				'default' => 'card',
				'options' => array(
					'card'    => 'کارت بزرگ',
					'compact' => 'فشرده (یک خط)',
					'inline'  => 'افقی کنار محتوا',
				),
			)
		);

		$this->add_control(
			'dw_avatar',
			array(
				'label'       => 'آواتار',
				'type'        => 'switcher',
				'default'     => 'yes',
				'description' => 'اگر سایت گراواتار نداشته باشد، حرف اول نام نمایش داده می‌شود.',
			)
		);

		$this->add_control(
			'dw_avatar_size',
			array(
				'label'     => 'اندازه آواتار (px)',
				'type'      => 'number',
				'default'   => (int) dashwoo_get_setting( 'account_design.avatar_size', 96 ),
				'min'       => 32,
				'max'       => 200,
				'condition' => array( 'dw_avatar' => 'yes' ),
			)
		);

		$this->add_control(
			'dw_fields',
			array(
				'label'       => 'خطوط اطلاعات',
				'type'        => 'select2',
				'multiple'    => true,
				'default'     => array( 'email' ),
				'options'     => array(
					'email'         => 'ایمیل',
					'billing_phone' => 'تلفن',
					'billing_city'  => 'شهر',
					'orders'        => 'تعداد سفارش',
					'first_name'    => 'نام',
					'last_name'     => 'نام خانوادگی',
				),
			)
		);

		$this->add_control(
			'dw_edit_button',
			array(
				'label'       => 'دکمهٔ ویرایش پروفایل',
				'type'        => 'switcher',
				'default'     => 'yes',
				'description' => 'لینک به فرم ویرایش حساب ووکامرس.',
			)
		);

		$this->end_controls_section();

		$this->register_visibility_controls();
		$this->register_style_controls();
	}

	/**
	 * Style kit extras.
	 *
	 * @return array<int,string>
	 */
	protected function style_spec() {
		return array();
	}

	/**
	 * Parts the shop owner may switch off.
	 *
	 * @return array<string,mixed>
	 */
	protected function visibility_spec() {
		return array(
			'avatar' => true,
			'title'  => true,
			'meta'   => true,
		);
	}

	/**
	 * View args.
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		$settings = (array) $this->get_settings_for_display();

		$variant = isset( $settings['dw_variant'] ) ? sanitize_key( (string) $settings['dw_variant'] ) : 'card';

		if ( ! in_array( $variant, array( 'card', 'compact', 'inline' ), true ) ) {
			$variant = 'card';
		}

		$fields = isset( $settings['dw_fields'] ) ? (array) $settings['dw_fields'] : array( 'email' );

		return array(
			'variant'     => $variant,
			'avatar'      => 'yes' === ( $settings['dw_avatar'] ?? 'yes' ),
			'avatar_size' => (int) ( $settings['dw_avatar_size'] ?? 96 ),
			'fields'      => array_values( array_filter( array_map( 'sanitize_key', $fields ) ) ),
			'edit_button' => 'yes' === ( $settings['dw_edit_button'] ?? 'yes' ),
			'class'       => 'dw-acc--profile-' . $variant,
		);
	}
}
