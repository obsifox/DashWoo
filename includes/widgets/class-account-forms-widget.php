<?php
/**
 * Elementor widget: فرم‌های حساب (forms).
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Forms widget (profile / address / password / login).
 */
class Account_Forms_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_forms';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — فرم‌های حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'forms';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_form',
			array( 'label' => 'فرم' )
		);

		$this->add_control(
			'dw_form',
			array(
				'label'   => 'نوع فرم',
				'type'    => 'select',
				'default' => 'profile',
				'options' => array(
					'profile'  => 'جزئیات حساب (نام نمایشی)',
					'address'  => 'آدرس‌ها',
					'password' => 'تغییر گذرواژه',
					'login'    => 'ورود به حساب',
				),
			)
		);

		$this->add_control(
			'dw_title',
			array(
				'label'   => 'عنوان فرم',
				'type'    => 'text',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_fields',
			array(
				'label'       => 'فیلدهای فرم پروفایل',
				'type'        => 'select2',
				'multiple'    => true,
				'default'     => array( 'display_name' ),
				'options'     => array(
					'display_name'  => 'نام نمایشی (قابل ذخیره)',
					'first_name'    => 'نام',
					'last_name'     => 'نام خانوادگی',
					'billing_email' => 'ایمیل صورتحساب',
					'billing_phone' => 'تلفن',
				),
				'condition'   => array( 'dw_form' => 'profile' ),
			)
		);

		$this->add_control(
			'dw_note',
			array(
				'type' => 'raw_html',
				'raw'  => 'DashWoo فقط «نام نمایشی» را ذخیره می‌کند؛ ایمیل، گذرواژه و آدرس‌ها همچنان با فرم و اعتبارسنجی خود ووکامرس ذخیره می‌شوند. لینک‌های هر بخش در همان ویجت هست.',
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

		$form = isset( $settings['dw_form'] ) ? sanitize_key( (string) $settings['dw_form'] ) : 'profile';

		if ( ! in_array( $form, array( 'profile', 'address', 'password', 'login' ), true ) ) {
			$form = 'profile';
		}

		$fields = isset( $settings['dw_fields'] ) ? (array) $settings['dw_fields'] : array( 'display_name' );

		return array(
			'form'   => $form,
			'title'  => isset( $settings['dw_title'] ) ? (string) $settings['dw_title'] : '',
			'fields' => array_values( array_filter( array_map( 'sanitize_key', $fields ) ) ),
		);
	}
}
