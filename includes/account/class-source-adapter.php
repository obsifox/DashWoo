<?php
/**
 * Source of truth for the account area: WooCommerce's defaults, DashWoo's widgets,
 * or a deliberate mixture.
 *
 * Three modes, all reversible from one setting:
 *
 *   default   WooCommerce renders the account page (DashWoo only restyles it).
 *   hybrid    DashWoo draws the account navigation, WooCommerce keeps the content
 *             (orders, addresses, downloads) - the safest way to leave the default
 *             menu behind without re-implementing shop logic.
 *   elementor Elementor owns both: the native navigation and the native content are
 *             unhooked, and the page is built from DashWoo widgets. If such a page
 *             is *not* built with Elementor yet, DashWoo renders its own generated
 *             layout instead, so a shop never ends up with an empty account page.
 *
 * Nothing is deleted and no template file is overridden: everything is hook
 * removal, which the Compatibility Mode can fall back from instantly.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Mode handling + the generated layout.
 */
class Source_Adapter {

	/**
	 * Settings section.
	 */
	const SECTION = 'account_source';

	/**
	 * Mode: WooCommerce's own templates.
	 */
	const MODE_DEFAULT = 'default';

	/**
	 * Mode: DashWoo navigation, WooCommerce content.
	 */
	const MODE_HYBRID = 'hybrid';

	/**
	 * Mode: everything from Elementor + DashWoo widgets.
	 */
	const MODE_ELEMENTOR = 'elementor';

	/**
	 * Singleton.
	 *
	 * @var Source_Adapter|null
	 */
	private static $instance = null;

	/**
	 * Whether the removals were already applied this request.
	 *
	 * @var bool
	 */
	private $applied = false;

	/**
	 * Singleton accessor.
	 *
	 * @return Source_Adapter
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks. Nothing is removed on `init`: the decision needs to know whether the
	 * account page is built with Elementor, which is only known once the query is
	 * resolved.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'apply' ), 5 );
		add_filter( 'the_content', array( $this, 'maybe_inject_layout' ), 20 );
	}

	/**
	 * The active mode (filterable, and downgraded in Compatibility Mode).
	 *
	 * @return string
	 */
	public function mode() {
		$mode = (string) dashwoo_get_setting( self::SECTION . '.mode', self::MODE_DEFAULT );

		/**
		 * Filter the account source mode at runtime.
		 *
		 * @param string $mode One of default | hybrid | elementor.
		 */
		$mode = (string) apply_filters( 'dashwoo_account_mode', $mode );

		// Validate *after* the filter: a filter may never be able to push the adapter
		// into a mode it does not understand.
		if ( ! in_array( $mode, array( self::MODE_DEFAULT, self::MODE_HYBRID, self::MODE_ELEMENTOR ), true ) ) {
			$mode = self::MODE_DEFAULT;
		}

		// Compatibility Mode never leaves a shop without a working account page: the
		// Elementor-owned layouts are only allowed while the platform runs at full
		// power (a host without Elementor, or a shop that forced the safe mode).
		if ( self::MODE_DEFAULT !== $mode && class_exists( '\\DashWoo\\Compatibility\\Compatibility' ) ) {
			if ( 'compatibility' === \DashWoo\Compatibility\Compatibility::instance()->mode() ) {
				$mode = self::MODE_DEFAULT;
			}
		}

		return $mode;
	}

	/**
	 * Register `[dashwoo_account]`.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		if ( ! function_exists( 'add_shortcode' ) ) {
			return;
		}

		if ( ! dashwoo_is_on( self::SECTION . '.shortcode' ) ) {
			return;
		}

		add_shortcode( 'dashwoo_account', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Remove WooCommerce's own navigation / content when DashWoo owns them.
	 *
	 * @return void
	 */
	public function apply() {
		if ( $this->applied ) {
			return;
		}

		$this->applied = true;

		$mode = $this->mode();

		if ( self::MODE_DEFAULT === $mode ) {
			return;
		}

		if ( ! $this->is_account_page() ) {
			return;
		}

		// Nothing is removed for a page that is not yet built with Elementor: that
		// page would show nothing at all.
		if ( self::MODE_ELEMENTOR === $mode && ! $this->is_elementor_built() ) {
			return;
		}

		if ( function_exists( 'remove_action' ) ) {
			remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation', 10 );
			remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard', 10 );
		}

		if ( self::MODE_ELEMENTOR === $mode && function_exists( 'remove_action' ) ) {
			remove_action( 'woocommerce_account_content', 'woocommerce_account_content', 10 );
		}

		if ( function_exists( 'remove_shortcode' ) ) {
			// The native shortcode would print the whole page again inside the builder.
			remove_shortcode( 'woocommerce_my_account' );
		}

		/**
		 * Fires after DashWoo took over parts of the account page.
		 *
		 * @param string $mode Active mode.
		 */
		do_action( 'dashwoo_account_source_applied', $mode );
	}

	/**
	 * Render the generated layout instead of the (empty) native page.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_inject_layout( $content ) {
		if ( ! dashwoo_is_on( self::SECTION . '.generated_layout' ) ) {
			return $content;
		}

		if ( is_admin() || ! $this->is_account_page() ) {
			return $content;
		}

		if ( self::MODE_ELEMENTOR !== $this->mode() || $this->is_elementor_built() ) {
			return $content;
		}

		if ( ! Renderer::ready() ) {
			return $content;
		}

		// A Theme Builder template for the DashWoo account location owns the area when
		// the shop built one; otherwise the generated layout is used.
		$theme_builder = Elementor_Bridge::location_html();

		if ( '' !== $theme_builder ) {
			return $content . $theme_builder;
		}

		/** This filter is documented in the class docblock. */
		return $content . $this->generated_layout();
	}

	/**
	 * The generated account layout: hero + navigation + native content (when the
	 * native content is still wired).
	 *
	 * @param array<string,mixed> $args Options.
	 * @return string
	 */
	public function generated_layout( array $args = array() ) {
		$endpoints = Endpoints::instance();

		$layout = (string) apply_filters( 'dashwoo_account_generated_layout', 'sidebar', $args );

		$html = Renderer::open( array_merge( array( 'layout' => 'generated' ), $args ) );

		// The two-pane panel is the modern shell: menu card + content card, with the
		// content swapped in place. Everything else stays as a fallback.
		if ( dashwoo_is_on( Panel::SECTION . '.enabled' ) ) {
			$html .= Panel::render( array( 'layout' => 'menu' ) );
			$html .= Renderer::close();

			return $html;
		}

		$html .= '<div class="dw-acc__shell dw-acc__shell--' . esc_attr( $layout ) . '">';
		$html .= '<aside class="dw-acc__side">' . Renderer::dashboard( array( 'cards' => false ) ) . Renderer::nav( array( 'layout' => 'menu', 'icons' => true ) ) . '</aside>';
		$html .= '<section class="dw-acc__main">';

		$current = $endpoints->current();

		if ( 'dashboard' === $current ) {
			$html .= Renderer::cards( array( 'columns' => (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ) ) );
		}

		if ( function_exists( 'do_action' ) && has_action( 'woocommerce_account_content' ) ) {
			$html .= '<div class="dw-acc__endpoint">';
			ob_start();
			do_action( 'woocommerce_account_content' );
			$html .= (string) ob_get_clean();
			$html .= '</div>';
		}

		$html .= '</section></div>';
		$html .= Renderer::close();

		return $html;
	}

	/**
	 * `[dashwoo_account]` — the same layout, usable in any page or builder.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		$atts = is_array( $atts ) ? $atts : array();

		$state = Renderer::availability();

		if ( ! $state['ready'] ) {
			if ( 'guest' === $state['state'] ) {
				return Renderer::open( array( 'layout' => 'boxed' ) ) . Renderer::login_prompt() . Renderer::close();
			}

			return Renderer::notice( $state, array( 'editor' => true ) );
		}

		// `[dashwoo_account panel="0"]` keeps the classic two-block shell; by default the
		// panel (with its in-place swapping) is what the shop owner sees.
		$panel = dashwoo_is_on( Panel::SECTION . '.enabled' );

		if ( isset( $atts['panel'] ) && in_array( strtolower( (string) $atts['panel'] ), array( '0', 'no', 'off', 'false' ), true ) ) {
			$panel = false;
		}

		if ( $panel ) {
			$panel_args = array( 'layout' => isset( $atts['nav_layout'] ) ? sanitize_key( (string) $atts['nav_layout'] ) : 'menu' );

			foreach ( array( 'view', 'order_id', 'template', 'mode', 'aside', 'aside_width', 'gap' ) as $key ) {
				if ( isset( $atts[ $key ] ) && '' !== (string) $atts[ $key ] ) {
					$panel_args[ $key ] = $atts[ $key ];
				}
			}

			return Renderer::open( array( 'layout' => 'boxed' ) ) . Panel::render( $panel_args ) . Renderer::close();
		}

		$public = '<div class="dw-acc__shell dw-acc__shell--' . esc_attr( isset( $atts['layout'] ) ? (string) $atts['layout'] : 'sidebar' ) . '">';
		$public .= '<aside class="dw-acc__side">' . Renderer::dashboard( array( 'cards' => false ) ) . Renderer::nav( array( 'layout' => 'menu', 'icons' => true ) ) . '</aside>';
		$public .= '<section class="dw-acc__main">';

		if ( 'dashboard' === Endpoints::instance()->current() ) {
			$public .= Renderer::cards( array( 'columns' => (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ) ) );
		}

		if ( function_exists( 'do_action' ) && has_action( 'woocommerce_account_content' ) ) {
			$public .= '<div class="dw-acc__endpoint">';
			ob_start();
			do_action( 'woocommerce_account_content' );
			$public .= (string) ob_get_clean();
			$public .= '</div>';
		}

		$public .= '</section></div>';

		return Renderer::open( array( 'layout' => 'boxed' ) ) . $public . Renderer::close();
	}

	/**
	 * Is the current request the WooCommerce account page?
	 *
	 * @return bool
	 */
	public function is_account_page() {
		if ( function_exists( 'is_account_page' ) ) {
			return (bool) is_account_page();
		}

		return false;
	}

	/**
	 * Is the account page built with Elementor?
	 *
	 * @return bool
	 */
	public function is_elementor_built() {
		if ( ! function_exists( 'get_queried_object_id' ) || ! function_exists( 'get_post_meta' ) ) {
			return false;
		}

		$post_id = (int) get_queried_object_id();

		if ( $post_id <= 0 ) {
			return false;
		}

		if ( 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return true;
		}

		return false;
	}
}
