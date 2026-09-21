<?php
/**
 * Shared base for every DashWoo account widget.
 *
 * Elementor only loads these classes when it is active (the file bails out
 * otherwise), but all of the *logic* lives in DashWoo\Account\*, so the widgets
 * themselves stay thin: controls + a call to the renderer.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Renderer;
use DashWoo\Account\Source_Adapter;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Base widget.
 */
abstract class Account_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Which renderer method this widget calls.
	 *
	 * @return string
	 */
	abstract protected function view();

	/**
	 * Elementor category.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( 'dashwoo', 'dashwoo-account' );
	}

	/**
	 * Keywords for the Elementor search box (Persian + English).
	 *
	 * @return array<int,string>
	 */
	public function get_keywords() {
		return array( 'dashwoo', 'woocommerce', 'account', 'حساب', 'کاربری', 'ووکامرس' );
	}

	/**
	 * No Elementor Pro features, no external scripts.
	 *
	 * @return array<int,string>
	 */
	public function get_script_depends() {
		return array();
	}

	/**
	 * Shared style controls: every account widget can be themed per instance.
	 *
	 * @return void
	 */
	protected function register_style_controls() {
		if ( ! method_exists( $this, 'start_controls_section' ) ) {
			return;
		}

		$this->start_controls_section(
			'dw_account_style',
			array(
				'label' => 'سبک DashWoo',
				'tab'   => 'style',
			)
		);

		$this->add_control(
			'dw_accent',
			array(
				'label'       => 'رنگ تأکید',
				'type'        => 'color',
				'default'     => '',
				'description' => 'خالی بگذارید تا رنگ تأکید سراسری DashWoo (توکن primary) استفاده شود.',
			)
		);

		$this->add_control(
			'dw_radius',
			array(
				'label'   => 'گردی گوشه‌ها (px)',
				'type'    => 'number',
				'default' => (int) dashwoo_get_setting( 'account_design.radius', 18 ),
				'min'     => 0,
				'max'     => 60,
			)
		);

		$this->add_control(
			'dw_tone',
			array(
				'label'   => 'حالت رنگی',
				'type'    => 'select',
				'default' => 'auto',
				'options' => array(
					'auto'  => 'خودکار با توکن‌های DashWoo',
					'light' => 'روشن',
					'dark'  => 'تیره',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The style overrides Elementor collected, in the shape the renderer wants.
	 *
	 * @return array<string,mixed>
	 */
	protected function style_args() {
		$settings = method_exists( $this, 'get_settings_for_display' ) ? (array) $this->get_settings_for_display() : array();
		$args     = array();

		if ( ! empty( $settings['dw_accent'] ) ) {
			$args['accent'] = (string) $settings['dw_accent'];
		}

		if ( ! empty( $settings['dw_radius'] ) ) {
			$args['radius'] = (int) $settings['dw_radius'];
		}

		$args['class'] = 'dw-acc--tone-' . ( isset( $settings['dw_tone'] ) ? sanitize_key( (string) $settings['dw_tone'] ) : 'auto' );

		return $args;
	}

	/**
	 * Everything a control adds is forwarded to the renderer.
	 *
	 * @param array<int,string> $keys Control ids.
	 * @return array<string,mixed>
	 */
	protected function args_from( array $keys ) {
		$settings = method_exists( $this, 'get_settings_for_display' ) ? (array) $this->get_settings_for_display() : array();
		$args     = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$args[ $key ] = $settings[ $key ];
			}
		}

		return array_merge( $args, $this->style_args() );
	}

	/**
	 * Render through the shared renderer, with the availability guard in front.
	 *
	 * @return void
	 */
	protected function render() {
		$state = Renderer::availability();

		if ( ! $state['ready'] ) {
			if ( 'guest' === $state['state'] && ! $this->is_editor_request() ) {
				echo Renderer::open( $this->style_args() ) . Renderer::login_prompt() . Renderer::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				return;
			}

			$editor = $this->is_editor_request();

			echo Renderer::open( $this->style_args() ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				. Renderer::notice( $state, array( 'editor' => $editor ) )
				. Renderer::close();

			return;
		}

		$method = $this->view();
		$args   = $this->view_args();

		if ( ! method_exists( Renderer::class, $method ) ) {
			return;
		}

		$html = (string) call_user_func( array( Renderer::class, $method ), $args );

		if ( '' === $html ) {
			return;
		}

		echo Renderer::open( $this->style_args() ) . $html . Renderer::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Args for the view method (widgets override this).
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		return array();
	}

	/**
	 * Are we inside the Elementor editor / preview?
	 *
	 * @return bool
	 */
	protected function is_editor_request() {
		// The bridge owns this decision (edit mode, preview mode, editor iframes), so a
		// widget can never disagree with the rest of the pack about "are we in the builder".
		if ( class_exists( '\\DashWoo\\Account\\Elementor_Bridge' ) ) {
			return \DashWoo\Account\Elementor_Bridge::is_editor();
		}

		return false;
	}
}
