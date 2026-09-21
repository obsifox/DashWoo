<?php
/**
 * Elementor widget: حساب کاربری DashWoo (dashboard hero + cards).
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Account dashboard widget.
 */
class Account_Dashboard_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_dashboard';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — پیشخوان حساب';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'dashboard';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_dashboard',
			array( 'label' => 'محتوای کارت خوش‌آمد' )
		);

		$this->add_control(
			'dw_greeting',
			array(
				'label'        => 'متن خوش‌آمد',
				'type'         => 'text',
				'default'      => 'خوش آمدید',
				'description'  => 'نام کاربر به‌صورت خودکار بعد از این متن می‌آید.',
			)
		);

		$this->add_control(
			'dw_avatar',
			array(
				'label'   => 'نمایش آواتار',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_summary',
			array(
				'label'   => 'نمایش خلاصه (ایمیل و تعداد سفارش)',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_cards',
			array(
				'label'   => 'نمایش کارت‌های میان‌بر',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_columns',
			array(
				'label'     => 'تعداد ستون کارت‌ها',
				'type'      => 'select',
				'default'   => (string) (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ),
				'options'   => array(
					'1' => 'یک ستون',
					'2' => 'دو ستون',
					'3' => 'سه ستون',
					'4' => 'چهار ستون',
				),
				'condition' => array( 'dw_cards' => 'yes' ),
			)
		);

		$this->add_control(
			'dw_hero_align',
			array(
				'label'   => 'چیدمان کارت',
				'type'    => 'select',
				'default' => 'row',
				'options' => array(
					'row'    => 'افقی (آواتار کنار متن)',
					'column' => 'عمودی',
				),
			)
		);

		$this->end_controls_section();

		$this->register_visibility_controls();
		$this->register_style_controls();
	}

	/**
	 * Style kit extras for the dashboard (columns of the shortcut cards).
	 *
	 * @return array<int,string>
	 */
	protected function style_spec() {
		return array( 'dashboard' );
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
			'icon'   => true,
		);
	}

	/**
	 * View args.
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		return array(
			'greeting_text' => isset( $this->get_settings_for_display()['dw_greeting'] ) ? (string) $this->get_settings_for_display()['dw_greeting'] : 'خوش آمدید',
			'avatar'        => 'yes' === $this->setting( 'dw_avatar', 'yes' ),
			'greeting'      => 'yes' === $this->setting( 'dw_summary', 'yes' ),
			'cards'         => 'yes' === $this->setting( 'dw_cards', 'yes' ),
			'columns'       => (int) $this->setting( 'dw_columns', '3' ),
			'class'         => 'dw-acc--hero-' . sanitize_key( (string) $this->setting( 'dw_hero_align', 'row' ) ),
		);
	}

}
