<?php
/**
 * The account subsystem: one boot point for everything under My Account.
 *
 * Wiring only - the behaviour lives in Endpoints, Renderer, Form_Adapter and
 * Source_Adapter, so each piece can be tested on its own.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

use DashWoo\Assets\Asset_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Account service.
 */
class Account {

	/**
	 * Handle of the account stylesheet (a dependency-only style; all of its CSS is
	 * inline, so no extra request is made).
	 */
	const STYLE_HANDLE = 'dashwoo-account';

	/**
	 * Singleton.
	 *
	 * @var Account|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Account
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the account layer.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 40 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'dashwoo_capability_features', array( $this, 'capabilities' ) );

		if ( ! dashwoo_is_on( 'account_source.enabled' ) ) {
			return;
		}

		Source_Adapter::instance()->boot();
		Native_Bridge::instance()->boot();

		// Elementor is the second front end of this pack: widgets, builder preview and
		// the one-click layout all live in the bridge.
		Elementor_Bridge::instance()->boot();

		// Anchor for in-page links (#edit-account) and for the generated layout's
		// section headings, on the account page only.
		add_filter( 'woocommerce_account_content', array( $this, 'breadcrumb' ), 1 );
	}

	/**
	 * Breadcrumb above an account sub-page ("حساب کاربری ← سفارش‌ها").
	 *
	 * @return void
	 */
	public function breadcrumb() {
		if ( ! dashwoo_is_on( 'account_layout.show_breadcrumb' ) ) {
			return;
		}

		$endpoints = Endpoints::instance();
		$current   = $endpoints->current();

		if ( 'dashboard' === $current ) {
			return;
		}

		$home = $endpoints->url( 'dashboard' );
		$item = $endpoints->wire();

		$label = isset( $item[ $current ]['label'] ) ? (string) $item[ $current ]['label'] : $current;

		$html  = '<nav class="dw-acc__crumbs" aria-label="مسیر">';
		$html .= '<a class="dw-acc__crumb" href="' . esc_url( $home ) . '">' . esc_html( $endpoints->label( 'dashboard' ) ) . '</a>';
		$html .= '<span class="dw-acc__crumb-sep" aria-hidden="true">/</span>';
		$html .= '<span class="dw-acc__crumb is-current">' . esc_html( $label ) . '</span>';
		$html .= '</nav>';

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Styles used by the account blocks, on top of the platform stylesheet.
	 *
	 * Only loaded where they can matter (account page, Elementor editor, a page that
	 * uses the shortcode), so the rest of the site keeps the exact same CSS payload
	 * it had before this pack existed.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! dashwoo_is_on( 'general.enabled' ) || ! dashwoo_is_on( 'account_design.enabled' ) ) {
			return;
		}

		if ( ! $this->is_account_context() ) {
			return;
		}

		$css = Renderer::styles();

		if ( '' === $css ) {
			return;
		}

		// A dependency-only stylesheet (src = false) is the WordPress way to hang
		// inline CSS off a handle without shipping another file.
		if ( function_exists( 'wp_register_style' ) ) {
			wp_register_style( self::STYLE_HANDLE, false, array( Asset_Manager::STYLE_HANDLE ), DASHWOO_VERSION );
			wp_enqueue_style( self::STYLE_HANDLE );
		}

		if ( function_exists( 'wp_add_inline_style' ) ) {
			wp_add_inline_style( self::STYLE_HANDLE, $css );
		}
	}

	/**
	 * Where the account styles are meaningful.
	 *
	 * @return bool
	 */
	public function is_account_context() {
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		if ( $this->is_builder_request() ) {
			return true;
		}

		if ( function_exists( 'is_singular' ) && is_singular() && function_exists( 'has_shortcode' ) && function_exists( 'get_post' ) ) {
			$post = get_post();

			if ( $post && isset( $post->post_content ) && has_shortcode( (string) $post->post_content, 'dashwoo_account' ) ) {
				return true;
			}
		}

		/**
		 * Filter whether the account styles should load for this request.
		 *
		 * @param bool $needed Whether the context needs them.
		 */
		return (bool) apply_filters( 'dashwoo_account_needs_styles', false );
	}

	/**
	 * Elementor editor / preview, where a shop owner is designing the account page.
	 *
	 * @return bool
	 */
	public function is_builder_request() {
		// One source of truth lives in the bridge (edit mode, preview mode and the
		// elementor-preview iframe all count as "the builder").
		return Elementor_Bridge::is_builder_request();
	}

	/**
	 * Mark the account page in the body class, for theme-side styling.
	 *
	 * @param array<int,string> $classes Classes.
	 * @return array<int,string>
	 */
	public function body_class( $classes ) {
		$classes   = is_array( $classes ) ? $classes : array();
		$classes[] = 'dw-account';

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			$classes[] = 'dw-account-page';
			$classes[] = 'dw-account--' . Source_Adapter::instance()->mode();
		}

		return $classes;
	}

	/**
	 * Account features join the gate table, so a shop owner sees them (and they can
	 * be switched off) exactly like every other DashWoo feature.
	 *
	 * @param array<string,array<string,mixed>> $features Feature table.
	 * @return array<string,array<string,mixed>>
	 */
	public function capabilities( $features ) {
		$features['account_area'] = array(
			'label'    => 'پک حساب کاربری',
			'requires' => array(),
			'effect'   => 'میان‌بر و ویجت‌های حساب کاربری DashWoo: منوی حساب، کارت‌ها، فرم‌ها و جایگزینی ناوبری پیش‌فرض ووکامرس',
			'when_off' => 'همهٔ ویجت‌ها پیام «خاموش است» را در ویرایشگر نشان می‌دهند و سبک DashWoo اعمال نمی‌شود',
			'hint'     => 'بدون نیاز به قابلیت خاصی کار می‌کند.',
		);

		return $features;
	}
}
