<?php
/**
 * Shared base for the account *section* widgets (orders, downloads, ...).
 *
 * WooCommerce stays the source of truth: the widget asks the shop's own callback
 * to run (`do_action( 'woocommerce_account_<endpoint>_endpoint' )`) or, when that
 * is not available, prints DashWoo's shortcut block. Nothing is re-implemented,
 * so an order page can never disagree with the shop.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Endpoints;
use DashWoo\Account\Panel;
use DashWoo\Account\Renderer;
use DashWoo\Account\Templates;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Endpoint widget base.
 */
abstract class Account_Endpoint_Widget_Base extends Account_Widget_Base {

	/**
	 * WooCommerce endpoint this widget shows.
	 *
	 * @return string
	 */
	abstract protected function endpoint();

	/**
	 * The shortcode-style block DashWoo draws when WooCommerce's callback is gone.
	 *
	 * @return string
	 */
	abstract protected function fallback_view();

	/**
	 * View: the endpoint content itself.
	 *
	 * @return string
	 */
	protected function view() {
		return 'endpoint';
	}

	/**
	 * Template section of this widget (orders, downloads, addresses...).
	 *
	 * @return string
	 */
	protected function template_section() {
		return Panel::section_of( $this->endpoint() );
	}

	/**
	 * Parts the shop owner may switch off.
	 *
	 * @return array<string,mixed>
	 */
	protected function visibility_spec() {
		return array(
			'icon'   => true,
			'title'  => true,
			'meta'   => true,
			'action' => true,
		);
	}

	/**
	 * The endpoint hook WooCommerce uses for this section.
	 *
	 * @return string
	 */
	protected function hook() {
		return 'woocommerce_account_' . $this->endpoint() . '_endpoint';
	}

	/**
	 * Args for Renderer::endpoint().
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		$source = sanitize_key( (string) $this->setting( 'dw_source', '' ) );

		if ( ! in_array( $source, array( 'auto', 'native', 'dashwoo' ), true ) ) {
			// Backwards compatible with the old "content" switch.
			$source = 'yes' === (string) $this->setting( 'dw_shortcut', 'yes' ) ? 'auto' : 'dashwoo';
		}

		$template = sanitize_key( (string) $this->setting( 'dw_template', '' ) );

		$args = array(
			'endpoint'   => $this->endpoint(),
			'hook'       => $this->hook(),
			'source'     => $source,
			'fallback'   => $this->fallback_view(),
			'title'      => $this->shows( 'title' ) ? $this->title_text() : '',
			'show_title' => $this->shows( 'title' ),
			'url'        => Endpoints::instance()->url( $this->endpoint() ),
			'per_page'   => (int) $this->setting( 'dw_per_page', 0 ),
			'icons'      => 'yes' === (string) $this->setting( 'dw_icons', 'yes' ) && $this->shows( 'icon' ),
			'order_id'   => $this->order_id(),
		);

		if ( '' !== $template ) {
			$args['template'] = $template;
		}

		return $args;
	}

	/**
	 * Order id for the order-detail widget (0 = the customer's latest order).
	 *
	 * @return int
	 */
	protected function order_id() {
		if ( 'view-order' !== $this->endpoint() ) {
			return 0;
		}

		return max( 0, (int) $this->setting( 'dw_order_id', 0 ) );
	}

	/**
	 * Title control value ('' = no heading).
	 *
	 * @return string
	 */
	protected function title_text() {
		$value = $this->setting( 'dw_title', '' );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Read a widget setting with a default.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	protected function setting( $key, $default ) {
		$settings = (array) $this->get_settings_for_display();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Shared controls: heading, native content switch, fallback.
	 *
	 * @param string $shortcut_default Default of the "use WooCommerce content" switch.
	 * @return void
	 */
	protected function register_endpoint_controls( $shortcut_default = 'yes' ) {
		$this->start_controls_section(
			'dw_account_endpoint',
			array( 'label' => __( 'This section\'s content', 'dashwoo' ) )
		);

		$this->add_control(
			'dw_title',
			array(
				'label'       => __( 'Section title', 'dashwoo' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Leave it empty to show no heading.', 'dashwoo' ),
			)
		);

		$this->add_control(
			'dw_source',
			array(
				'label'       => __( 'Content source', 'dashwoo' ),
				'type'        => 'select',
				'default'     => 'yes' === $shortcut_default ? 'auto' : 'dashwoo',
				'options'     => array(
					'auto'    => __( 'Automatic: WooCommerce content, and the DashWoo template when it has none', 'dashwoo' ),
					'native'  => __( 'WooCommerce only (no DashWoo template)', 'dashwoo' ),
					'dashwoo' => __( 'DashWoo template only', 'dashwoo' ),
				),
				'description' => __( 'When another plugin renders content for this section, “automatic” shows that and nothing is re-implemented.', 'dashwoo' ),
			)
		);

		$this->add_control(
			'dw_template',
			array(
				'label'       => __( 'Template', 'dashwoo' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array( '' => __( 'DashWoo settings default', 'dashwoo' ) ) + Templates::section_options( $this->template_section() ),
				'description' => '',
			)
		);

		$this->add_control(
			'dw_icons',
			array(
				'label'   => __( 'Icons', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->register_visibility_controls();
		$this->register_style_controls();
	}
}
