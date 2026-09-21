<?php
/**
 * Elementor widget: the account forms (profile, credentials, sign-in).
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
		return __( 'DashWoo — Account forms', 'dashwoo' );
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
			array( 'label' => __( 'Form', 'dashwoo' ) )
		);

		$this->add_control(
			'dw_form',
			array(
				'label'   => __( 'Form type', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'profile',
				'options' => array(
					'profile'  => __( 'Account details (display name)', 'dashwoo' ),
					'address'  => __( 'Addresses', 'dashwoo' ),
					'password' => __( 'Change password', 'dashwoo' ),
					'login'    => __( 'Sign in', 'dashwoo' ),
				),
			)
		);

		$this->add_control(
			'dw_title',
			array(
				'label'   => __( 'Form title', 'dashwoo' ),
				'type'    => 'text',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_fields',
			array(
				'label'       => __( 'Profile form fields', 'dashwoo' ),
				'type'        => 'select2',
				'multiple'    => true,
				'default'     => array( 'display_name' ),
				'options'     => array(
					'display_name'  => __( 'Display name (savable)', 'dashwoo' ),
					'first_name'    => __( 'First name', 'dashwoo' ),
					'last_name'     => __( 'Last name', 'dashwoo' ),
					'billing_email' => __( 'Billing email', 'dashwoo' ),
					'billing_phone' => __( 'Phone', 'dashwoo' ),
				),
				'condition'   => array( 'dw_form' => 'profile' ),
			)
		);

		$this->add_control(
			'dw_note',
			array(
				'type' => 'raw_html',
				'raw'  => __( 'DashWoo only saves the display name; email, password and addresses still go through WooCommerce\'s own form and validation. Every section links to it from this widget.', 'dashwoo' ),
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
			'title'  => true,
			'action' => true,
		);
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
