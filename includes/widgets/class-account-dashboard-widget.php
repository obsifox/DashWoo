<?php
/**
 * Elementor widget: the DashWoo account dashboard (welcome card + shortcuts).
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
		return __( 'DashWoo — Account dashboard', 'dashwoo' );
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
			array( 'label' => __( 'Welcome card content', 'dashwoo' ) )
		);

		$this->add_control(
			'dw_greeting',
			array(
				'label'        => __( 'Welcome text', 'dashwoo' ),
				'type'         => 'text',
				'default'      => __( 'Welcome', 'dashwoo' ),
				'description'  => __( 'The customer\'s name is appended automatically after this text.', 'dashwoo' ),
			)
		);

		$this->add_control(
			'dw_avatar',
			array(
				'label'   => __( 'Show the avatar', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_summary',
			array(
				'label'   => __( 'Show the summary (email and order count)', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_cards',
			array(
				'label'   => __( 'Show the shortcut cards', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_columns',
			array(
				'label'     => __( 'Number of card columns', 'dashwoo' ),
				'type'      => 'select',
				'default'   => (string) (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ),
				'options'   => array(
					'1' => __( 'One column', 'dashwoo' ),
					'2' => __( 'Two columns', 'dashwoo' ),
					'3' => __( 'Three columns', 'dashwoo' ),
					'4' => __( 'Four columns', 'dashwoo' ),
				),
				'condition' => array( 'dw_cards' => 'yes' ),
			)
		);

		$this->add_control(
			'dw_hero_align',
			array(
				'label'   => __( 'Card layout', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'row',
				'options' => array(
					'row'    => __( 'Horizontal (avatar next to the text)', 'dashwoo' ),
					'column' => __( 'Stacked', 'dashwoo' ),
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
			'greeting_text' => isset( $this->get_settings_for_display()['dw_greeting'] ) ? (string) $this->get_settings_for_display()['dw_greeting'] : __( 'Welcome', 'dashwoo' ),
			'avatar'        => 'yes' === $this->setting( 'dw_avatar', 'yes' ),
			'greeting'      => 'yes' === $this->setting( 'dw_summary', 'yes' ),
			'cards'         => 'yes' === $this->setting( 'dw_cards', 'yes' ),
			'columns'       => (int) $this->setting( 'dw_columns', '3' ),
			'class'         => 'dw-acc--hero-' . sanitize_key( (string) $this->setting( 'dw_hero_align', 'row' ) ),
		);
	}

}
