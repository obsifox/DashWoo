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
use DashWoo\Account\Renderer;

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
		return array(
			'endpoint'   => $this->endpoint(),
			'hook'       => $this->hook(),
			'shortcut'   => (string) $this->setting( 'dw_shortcut', 'yes' ),
			'fallback'   => $this->fallback_view(),
			'title'      => $this->title_text(),
			'url'        => Endpoints::instance()->url( $this->endpoint() ),
			'per_page'   => (int) $this->setting( 'dw_per_page', 0 ),
			'icons'      => 'yes' === $this->setting( 'dw_icons', 'yes' ),
		);
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
			array( 'label' => 'محتوای این بخش' )
		);

		$this->add_control(
			'dw_title',
			array(
				'label'       => 'عنوان بخش',
				'type'        => 'text',
				'default'     => '',
				'description' => 'خالی بگذارید تا عنوانی نمایش داده نشود.',
			)
		);

		$this->add_control(
			'dw_shortcut',
			array(
				'label'       => 'محتوای ووکامرس',
				'type'        => 'switcher',
				'default'     => $shortcut_default,
				'description' => 'روشن = همان محتوایی که ووکامرس (یا افزونه‌های فروشگاه شما) برای این بخش می‌سازد اجرا می‌شود؛ خاموش = فقط قالب میان‌بر DashWoo.',
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

		$this->end_controls_section();

		$this->register_style_controls();
	}
}
