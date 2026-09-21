<?php
/**
 * The two-pane account panel: a menu card next to a content card.
 *
 * WooCommerce ships one column: the menu and the section content are stacked. A shop
 * owner usually wants the menu in a smaller card *next to* a bigger content card, and
 * clicking a menu item should replace the content of that second card - including the
 * order detail ("Orders → a single order") - without a full page reload.
 *
 * This class is that shell:
 *   - `render()` draws the shell (progressive enhancement: every link is a real
 *     WooCommerce account URL, the AJAX swap only makes it smoother),
 *   - `view()` returns one view as data (title + markup + URL), used both by the
 *     server render and by the REST payload,
 *   - `prepare()`/`release()` put the request into the state WooCommerce templates
 *     expect while a view is rendered outside the account page (REST/AJAX).
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

use DashWoo\Assets\Icons\Icon_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Account panel.
 */
class Panel {

	/**
	 * Settings section.
	 */
	const SECTION = 'account_panel';

	/**
	 * REST path (inside the DashWoo namespace).
	 */
	const ROUTE = 'account/panel';

	/**
	 * Layout modes.
	 */
	const MODES = array( 'swap', 'modal', 'stacked' );

	/**
	 * The dynamic view that shows one order inside the panel.
	 */
	const ORDER_VIEW = 'view-order';

	/**
	 * Singleton.
	 *
	 * @var Panel|null
	 */
	private static $instance = null;

	/**
	 * Saved request state while a view is being rendered off-page.
	 *
	 * @var array<string,mixed>|null
	 */
	private $saved = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Panel
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the panel.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'dashwoo_account_panel_settings', array( $this, 'filter_settings' ) );
	}

	/**
	 * Merged panel settings.
	 *
	 * @param array<string,mixed> $args Optional overrides (widget args win).
	 * @return array<string,mixed>
	 */
	public static function settings( array $args = array() ) {
		$mode = (string) dashwoo_get_setting( self::SECTION . '.mode', 'swap' );
		$mode = in_array( $mode, self::MODES, true ) ? $mode : 'swap';

		$aside = (string) dashwoo_get_setting( self::SECTION . '.aside', 'right' );
		$aside = in_array( $aside, array( 'right', 'left' ), true ) ? $aside : 'right';

		$mobile = (string) dashwoo_get_setting( self::SECTION . '.mobile', 'stack' );
		$mobile = in_array( $mobile, array( 'stack', 'tabs' ), true ) ? $mobile : 'stack';

		$settings = array(
			'enabled'      => dashwoo_is_on( self::SECTION . '.enabled' ),
			'mode'         => $mode,
			'ajax'         => dashwoo_is_on( self::SECTION . '.ajax' ),
			'source'       => (string) dashwoo_get_setting( self::SECTION . '.source', 'auto' ),
			'default_view' => sanitize_key( (string) dashwoo_get_setting( self::SECTION . '.default_view', 'dashboard' ) ),
			'aside'        => $aside,
			'aside_width'  => max( 140, min( 520, (int) dashwoo_get_setting( self::SECTION . '.aside_width', 300 ) ) ),
			'gap'          => max( 0, min( 80, (int) dashwoo_get_setting( self::SECTION . '.gap', 24 ) ) ),
			'sticky'       => dashwoo_is_on( self::SECTION . '.sticky' ),
			'titles'       => dashwoo_is_on( self::SECTION . '.titles' ),
			'back_label'   => (string) dashwoo_get_setting( self::SECTION . '.back_label', __( 'Back to orders', 'dashwoo' ) ),
			'loading_text' => (string) dashwoo_get_setting( self::SECTION . '.loading_text', __( 'Loading…', 'dashwoo' ) ),
			'sync_url'     => dashwoo_is_on( self::SECTION . '.sync_url' ),
			'mobile'       => $mobile,
			'icons'        => dashwoo_is_on( self::SECTION . '.icons' ),
			'counts'       => dashwoo_is_on( self::SECTION . '.counts' ),
			'per_page'     => max( 0, min( 50, (int) dashwoo_get_setting( self::SECTION . '.per_page', 10 ) ) ),
		);

		/**
		 * Filter the panel settings (widget args are merged on top here).
		 *
		 * @param array<string,mixed> $settings Panel settings.
		 * @param array<string,mixed> $args     Overrides the caller passed.
		 */
		$settings = (array) apply_filters( 'dashwoo_account_panel_settings', $settings, $args );

		foreach ( $args as $key => $value ) {
			if ( array_key_exists( $key, $settings ) && null !== $value && '' !== $value ) {
				$settings[ $key ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Keep the shape stable when a filter messes with it (used by the panel widget's
	 * own settings).
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>
	 */
	public function filter_settings( $settings ) {
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Every view the panel can show: the WooCommerce endpoints plus the order view.
	 *
	 * @param array<string,mixed> $args Args (icons, counts, include_hidden).
	 * @return array<string,array<string,mixed>>
	 */
	public static function views( array $args = array() ) {
		$endpoints = Endpoints::instance();
		$items     = $endpoints->wire();
		$views     = array();

		foreach ( $items as $id => $item ) {
			$id      = (string) $id;
			$visible = ! empty( $item['visible'] );

			if ( ! $visible && empty( $args['include_hidden'] ) ) {
				continue;
			}

			$views[ $id ] = array(
				'id'      => $id,
				'label'   => (string) $item['label'],
				'icon'    => (string) $item['icon'],
				'url'     => (string) $item['url'],
				'order'   => (int) $item['order'],
				'active'  => ! empty( $item['active'] ),
				'section' => self::section_of( $id ),
				'dynamic' => false,
				'logout'  => ( 'customer-logout' === $id ),
			);
		}

		/**
		 * Filter the panel views.
		 *
		 * @param array<string,array<string,mixed>> $views Views.
		 * @param array<string,mixed>               $args  Args.
		 */
		return (array) apply_filters( 'dashwoo_account_panel_views', $views, $args );
	}

	/**
	 * Which template section a view uses.
	 *
	 * @param string $view View id (endpoint id or `view-order`).
	 * @return string
	 */
	public static function section_of( $view ) {
		$map = array(
			'edit-address'    => 'addresses',
			'payment-methods' => 'payment',
			'edit-account'    => 'details',
			'customer-logout' => 'logout',
			'view-order'      => 'order',
		);

		$view = (string) $view;

		if ( isset( $map[ $view ] ) ) {
			return $map[ $view ];
		}

		if ( Templates::supports( $view ) ) {
			return $view;
		}

		return 'dashboard' === $view ? 'dashboard' : 'orders';
	}

	/**
	 * The view the current request asks for.
	 *
	 * @param array<string,mixed> $args Optional `view`/`order_id` overrides.
	 * @return string
	 */
	public static function requested_view( array $args = array() ) {
		if ( isset( $args['view'] ) && '' !== (string) $args['view'] ) {
			return sanitize_key( (string) $args['view'] );
		}

		if ( isset( $_GET['dw_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_key( (string) wp_unslash( (string) $_GET['dw_view'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return '';
	}

	/**
	 * Order id the request asks for (0 = none).
	 *
	 * @param array<string,mixed> $args Optional overrides.
	 * @return int
	 */
	public static function requested_order( array $args = array() ) {
		if ( isset( $args['order_id'] ) ) {
			return max( 0, (int) $args['order_id'] );
		}

		if ( isset( $_GET['dw_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return max( 0, (int) $_GET['dw_order'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( function_exists( 'get_query_var' ) ) {
			$order_id = (int) get_query_var( self::ORDER_VIEW );

			if ( $order_id > 0 ) {
				return $order_id;
			}
		}

		return 0;
	}

	/**
	 * The view shown when nothing else was asked for.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function default_view( array $args = array() ) {
		$settings = self::settings( $args );
		$wanted   = (string) ( $args['default_view'] ?? $settings['default_view'] );
		$views    = self::views( $args );

		// On the account page the URL decides; inside the builder we keep the choice.
		$current = Endpoints::instance()->current();

		if ( isset( $views[ $current ] ) && ! self::is_builder() ) {
			return $current;
		}

		if ( '' !== $wanted && isset( $views[ $wanted ] ) ) {
			return $wanted;
		}

		return isset( $views['dashboard'] ) ? 'dashboard' : (string) array_key_first( $views );
	}

	/**
	 * Are we rendering inside the Elementor builder/preview?
	 *
	 * @return bool
	 */
	public static function is_builder() {
		return class_exists( __NAMESPACE__ . '\\Elementor_Bridge' ) && Elementor_Bridge::is_editor();
	}

	/**
	 * Build one view: title, markup, URL and the "back" target.
	 *
	 * @param string              $view View id.
	 * @param array<string,mixed> $args Args (order_id, template, icons, per_page, title...).
	 * @return array<string,mixed>
	 */
	public static function view( $view, array $args = array() ) {
		$view     = '' !== (string) $view ? sanitize_key( (string) $view ) : self::default_view( $args );
		$settings = self::settings( $args );
		$views    = self::views( array_merge( $args, array( 'include_hidden' => true ) ) );
		$label    = isset( $views[ $view ]['label'] ) ? (string) $views[ $view ]['label'] : '';

		if ( '' === $label && self::ORDER_VIEW === $view ) {
			$label = __( 'Order details', 'dashwoo' );
		}

		$order_id = self::ORDER_VIEW === $view ? max( 0, (int) ( $args['order_id'] ?? self::requested_order( $args ) ) ) : 0;
		$section  = self::section_of( $view );
		$title    = (string) ( $args['title'] ?? $label );

		$render_args = array(
			'title'    => $title,
			'icons'    => $settings['icons'],
			'per_page' => (int) ( $args['per_page'] ?? $settings['per_page'] ),
			'source'   => (string) ( $args['source'] ?? $settings['source'] ),
			'endpoint' => $view,
			'panel'    => true,
		);

		if ( isset( $args['template'] ) && '' !== (string) $args['template'] ) {
			$render_args['template'] = (string) $args['template'];
		}

		if ( $order_id > 0 ) {
			$render_args['order_id'] = $order_id;
		}

		$html = self::render_view( $view, $section, $order_id, $render_args );

		$url = ( self::ORDER_VIEW === $view && $order_id > 0 )
			? Endpoints::instance()->url( self::ORDER_VIEW, $order_id )
			: Endpoints::instance()->url( $view );

		/**
		 * Filter one rendered panel view.
		 *
		 * @param array<string,mixed> $view_data View data.
		 * @param array<string,mixed> $args      Args.
		 */
		return (array) apply_filters(
			'dashwoo_account_panel_view',
			array(
				'view'     => $view,
				'section'  => $section,
				'title'    => $title,
				'label'    => $label,
				'html'     => $html,
				'url'      => $url,
				'order_id' => $order_id,
				'back'     => ( self::ORDER_VIEW === $view && $order_id > 0 ) ? Endpoints::instance()->url( 'orders' ) : '',
				'back_label' => ( self::ORDER_VIEW === $view && $order_id > 0 ) ? (string) $settings['back_label'] : '',
				'empty'    => '' === trim( (string) $html ),
			),
			$args
		);
	}

	/**
	 * Is this request from a visitor who is not signed in?
	 *
	 * A shop owner editing the page still gets sample data, so the layout stays
	 * visible inside Elementor.
	 *
	 * @return bool
	 */
	public static function is_guest() {
		if ( Elementor_Bridge::is_editor() ) {
			return false;
		}

		return ! ( new Profile() )->logged_in();
	}

	/**
	 * What a guest sees in the content card: the menu stays as a table of contents,
	 * the card asks them to sign in instead of leaking account data.
	 *
	 * @param string              $view View id.
	 * @param array<string,mixed> $args Args.
	 * @return array<string,mixed> Same shape as view().
	 */
	public static function guest_view( $view, array $args = array() ) {
		$views = self::views( array_merge( $args, array( 'include_hidden' => true ) ) );
		$label = isset( $views[ $view ]['label'] ) ? (string) $views[ $view ]['label'] : '';

		$args['title'] = '' !== (string) ( $args['title'] ?? '' ) ? (string) $args['title'] : $label;

		/**
		 * Filter the panel view a guest sees.
		 *
		 * @param array<string,mixed> $view_data View data.
		 * @param array<string,mixed> $args      Args.
		 */
		return (array) apply_filters(
			'dashwoo_account_panel_guest_view',
			array(
				'view'       => (string) $view,
				'section'    => self::section_of( $view ),
				'title'      => (string) $args['title'],
				'label'      => $label,
				'html'       => Renderer::login_prompt( $args ),
				'url'        => Endpoints::instance()->url( $view ),
				'order_id'   => 0,
				'back'       => '',
				'back_label' => '',
				'empty'      => false,
			),
			$args
		);
	}

	/**
	 * Render the markup of a single view.
	 *
	 * Endpoint views keep WooCommerce as the source of truth: its own callback runs
	 * unless the shop owner asked for a DashWoo template.
	 *
	 * @param string              $view     View id.
	 * @param string              $section  Template section.
	 * @param int                 $order_id Order id (order view only).
	 * @param array<string,mixed> $args     Render args.
	 * @return string
	 */
	protected static function render_view( $view, $section, $order_id, array $args ) {
		$source = (string) ( $args['source'] ?? 'auto' );
		$hook   = 'woocommerce_account_' . $view . '_endpoint';
		$native = '';

		if ( 'dashwoo' !== $source && function_exists( 'has_action' ) && has_action( $hook ) && self::ORDER_VIEW !== $view ) {
			ob_start();
			do_action( $hook );
			$native = (string) ob_get_clean();
		}

		if ( '' !== trim( $native ) ) {
			$data = array(
				'title'    => (string) ( $args['title'] ?? '' ),
				'icons'    => ! empty( $args['icons'] ),
				'section'  => $section,
				'source'   => 'native',
				'template' => (string) ( $args['template'] ?? '' ),
			);

			/**
			 * Filter a panel view rendered from WooCommerce's own template.
			 *
			 * @param string $native Markup.
			 * @param string $view   View id.
			 */
			return (string) apply_filters( 'dashwoo_account_panel_native', $native, $view );
		}

		if ( 'native' === $source ) {
			return Sections::unavailable( __( 'This section has no content on this store.', 'dashwoo' ) );
		}

		if ( self::ORDER_VIEW === $view ) {
			$args['order_id'] = $order_id;

			return Templates::render( 'order', $args );
		}

		return Templates::render( $section, $args + array( 'endpoint' => $view, 'section' => $section ) );
	}

	/**
	 * The aside navigation (same renderer as the standalone nav widget).
	 *
	 * @param array<string,mixed> $args Args.
	 * @param string              $current Active view.
	 * @return string
	 */
	public static function nav( array $args, $current ) {
		$settings = self::settings( $args );
		$items    = isset( $args['items'] ) && is_array( $args['items'] ) && $args['items'] ? $args['items'] : self::views( $args );

		$nav_args = array(
			'layout'     => (string) ( $args['layout'] ?? 'menu' ),
			'icons'      => $settings['icons'],
			'counts'     => $settings['counts'],
			'sticky'     => $settings['sticky'],
			'items'      => $items,
			'active'     => $current,
			'panel'      => true,
			'counts_map' => isset( $args['counts_map'] ) ? $args['counts_map'] : array(),
			'labels'     => isset( $args['labels'] ) ? $args['labels'] : array(),
			'hidden'     => isset( $args['hidden'] ) ? $args['hidden'] : array(),
			'order_map'  => isset( $args['order_map'] ) ? $args['order_map'] : array(),
		);

		$html = Renderer::nav( $nav_args );

		if ( ! $settings['ajax'] ) {
			return $html;
		}

		// Progressive enhancement: mark the links so the JS can swap the content and
		// keep every href intact for a no-JS (or crawler) visit.
		return preg_replace_callback(
			'/<a class="([^"]*dw-acc__nav-link[^"]*)" href="([^"]*)"/',
			static function ( $matches ) {
				return '<a class="' . $matches[1] . '" href="' . $matches[2] . '" data-dw-nav="1"';
			},
			$html
		);
	}

	/**
	 * Draw the panel shell.
	 *
	 * @param array<string,mixed> $args Args (view, order_id, layout, items, aside_width...).
	 * @return string
	 */
	public static function render( array $args = array() ) {
		$settings = self::settings( $args );
		$view     = self::requested_view( $args );

		if ( '' === $view ) {
			$view = self::default_view( $args );
		}

		$current = ( self::is_guest() ) ? self::guest_view( $view, $args ) : self::view( $view, $args );
		$mode    = (string) $settings['mode'];

		$aside_width = (int) ( $args['aside_width'] ?? $settings['aside_width'] );
		$gap         = (int) ( $args['gap'] ?? $settings['gap'] );

		$classes = array(
			'dw-acc__panel',
			'dw-acc__panel--' . $mode,
			'dw-acc__panel--aside-' . $settings['aside'],
			'dw-acc__panel--mobile-' . $settings['mobile'],
		);

		if ( $settings['sticky'] ) {
			$classes[] = 'dw-acc__panel--sticky';
		}

		if ( ! empty( $args['class'] ) ) {
			$classes[] = sanitize_html_class( (string) $args['class'] );
		}

		$style = '--dw-acc-panel-aside:' . $aside_width . 'px;--dw-acc-panel-gap:' . $gap . 'px;';

		if ( isset( $args['style'] ) && '' !== (string) $args['style'] ) {
			$style .= (string) $args['style'];
		}

		$html  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '"';
		$html .= ' data-dw-panel="1" data-dw-mode="' . esc_attr( $mode ) . '"';
		$html .= ' data-dw-view="' . esc_attr( (string) $current['view'] ) . '"';
		$html .= ' data-dw-ajax="' . ( $settings['ajax'] ? '1' : '0' ) . '"';
		$html .= ' data-dw-sync="' . ( $settings['sync_url'] ? '1' : '0' ) . '"';
		$html .= ' data-dw-rest="' . esc_url( self::rest_url() ) . '"';
		$html .= ' data-dw-nonce="' . esc_attr( self::nonce() ) . '"';
		$html .= ' data-dw-account="' . esc_url( Endpoints::instance()->account_url() ) . '"';
		// The text lives in its own attribute: `data-dw-loading` is the busy flag the
		// script toggles, and one attribute cannot be both without losing the text.
		$html .= ' data-dw-loading-text="' . esc_attr( (string) $settings['loading_text'] ) . '"';
		$html .= ' data-dw-views="' . esc_attr( wp_json_encode( self::view_map( $args ) ) ) . '">';

		$html .= '<aside class="dw-acc__panel-aside" data-dw-panel-aside>';
		$html .= self::nav( $args, (string) $current['view'] );
		$html .= '</aside>';

		$html .= '<div class="dw-acc__panel-main" data-dw-panel-main>';
		$html .= '<header class="dw-acc__panel-head">';

		if ( '' !== (string) $current['back'] ) {
			$html .= '<button type="button" class="dw-acc__panel-back" data-dw-panel-back data-dw-target="orders" aria-label="' . esc_attr( (string) $current['back_label'] ) . '">'
				. Icon_Renderer::render( array( 'icon' => 'arrow_forward', 'size' => 20 ) )
				. '<span>' . esc_html( (string) $current['back_label'] ) . '</span></button>';
		}

		if ( $settings['titles'] ) {
			$html .= '<h3 class="dw-acc__panel-title" data-dw-panel-title>' . esc_html( (string) $current['title'] ) . '</h3>';
		} else {
			$html .= '<h3 class="dw-acc__panel-title is-visually-hidden" data-dw-panel-title>' . esc_html( (string) $current['title'] ) . '</h3>';
		}

		$html .= '<span class="dw-acc__panel-status" data-dw-panel-status role="status" aria-live="polite"></span>';
		$html .= '<span class="dw-acc__panel-spinner" data-dw-panel-spinner hidden aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'progress_activity', 'size' => 20 ) ) . '</span>';
		$html .= '</header>';

		$html .= '<div class="dw-acc__panel-body" data-dw-panel-body>' . (string) $current['html'] . '</div>';
		$html .= '</div>';

		if ( 'modal' === $mode ) {
			$html .= '<div class="dw-acc__panel-modal" data-dw-panel-modal hidden>'
				. '<div class="dw-acc__panel-modal-box" role="dialog" aria-modal="true">'
				. '<button type="button" class="dw-acc__panel-modal-close" data-dw-panel-close aria-label="' . esc_attr__( 'Close', 'dashwoo' ) . '">' . Icon_Renderer::render( array( 'icon' => 'close', 'size' => 22 ) ) . '</button>'
				. '<div class="dw-acc__panel-modal-body" data-dw-panel-modal-body></div>'
				. '</div></div>';
		}

		$html .= '</div>';

		/**
		 * Filter the whole panel markup.
		 *
		 * @param string              $html Markup.
		 * @param array<string,mixed> $args Args.
		 */
		return (string) apply_filters( 'dashwoo_account_panel_html', $html, $args );
	}

	/**
	 * Small map the front-end uses to recognise panel links.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return array<string,mixed>
	 */
	public static function view_map( array $args = array() ) {
		$views = self::views( array_merge( $args, array( 'include_hidden' => true ) ) );
		$map   = array();

		foreach ( $views as $id => $view ) {
			$map[ (string) $id ] = array(
				'id'     => (string) $id,
				'url'    => (string) $view['url'],
				'slug'   => (string) $id,
				'label'  => (string) $view['label'],
				'dynamic' => (bool) $view['dynamic'],
			);
		}

		$map[ self::ORDER_VIEW ] = array(
			'id'      => self::ORDER_VIEW,
			'url'     => '',
			'slug'    => self::ORDER_VIEW,
			'label'   => __( 'Order details', 'dashwoo' ),
			'dynamic' => true,
		);

		return $map;
	}

	/**
	 * REST URL of the panel endpoint.
	 *
	 * @return string
	 */
	public static function rest_url() {
		if ( function_exists( 'rest_url' ) ) {
			return (string) rest_url( 'dashwoo/v1/' . self::ROUTE );
		}

		if ( function_exists( 'get_rest_url' ) ) {
			return (string) get_rest_url( null, 'dashwoo/v1/' . self::ROUTE );
		}

		return '';
	}

	/**
	 * REST nonce for the panel request.
	 *
	 * @return string
	 */
	public static function nonce() {
		$nonce = function_exists( 'wp_create_nonce' ) ? (string) wp_create_nonce( 'wp_rest' ) : '';

		/**
		 * Filter the nonce the panel sends with its AJAX request.
		 *
		 * @param string $nonce Nonce.
		 */
		return (string) apply_filters( 'dashwoo_account_panel_nonce', $nonce );
	}

	/**
	 * The payload the REST route answers with.
	 *
	 * @param string              $view     View id.
	 * @param array<string,mixed> $args     Args (order_id, per_page, source, template).
	 * @return array<string,mixed>
	 */
	public static function payload( $view, array $args = array() ) {
		$state = self::prepare( $view, (int) ( $args['order_id'] ?? 0 ) );

		$data = self::view( $view, $args );

		self::release( $state );

		$data['html'] = self::absolutise( (string) $data['html'] );

		return $data;
	}

	/**
	 * Put the request into the state WooCommerce templates expect.
	 *
	 * Inside REST/AJAX there is no WooCommerce query var for the endpoint, so
	 * `wc_get_endpoint_url()`, `is_wc_endpoint_url()` and the pagination links inside
	 * WooCommerce's own templates would all point at the wrong place. This emulates
	 * exactly the query vars WooCommerce would have had - and only for the duration
	 * of one render (`release()` puts everything back).
	 *
	 * @param string $view     View id.
	 * @param int    $order_id Order id (order view only).
	 * @return array<string,mixed> State to hand back to release().
	 */
	public static function prepare( $view, $order_id = 0 ) {
		$state = array(
			'query_vars' => null,
			'paged'      => null,
			'active'     => true,
		);

		if ( isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars ) && is_array( $GLOBALS['wp']->query_vars ) ) {
			$state['query_vars'] = $GLOBALS['wp']->query_vars;
		} else {
			$state['active'] = false;
		}

		if ( ! $state['active'] ) {
			return $state;
		}

		// WooCommerce's query vars are the endpoint ids themselves ("edit-address",
		// "orders"), which is exactly what its templates look for.
		$slug = (string) $view;
		$slug = '' !== $slug ? $slug : $view;

		if ( 'dashboard' === $view || 'order' === $view || '' === $slug ) {
			$slug = '';
		}

		if ( '' !== $slug ) {
			$GLOBALS['wp']->query_vars[ $slug ] = self::ORDER_VIEW === $view ? (string) max( 0, (int) $order_id ) : '';
		}

		$paged = self::requested_page();
		$state['paged'] = $paged;

		if ( $paged > 1 ) {
			$GLOBALS['wp']->query_vars['paged'] = $paged;
		}

		// Pagination built by WooCommerce inside a REST response would point at the
		// REST URL; send it back to the real account page instead.
		add_filter( 'paginate_links', array( __CLASS__, 'rewrite_pagination' ) );

		/**
		 * Fires right before a panel view is rendered off the account page.
		 *
		 * @param string $view     View id.
		 * @param int    $order_id Order id.
		 */
		do_action( 'dashwoo_account_panel_prepare', $view, $order_id );

		return $state;
	}

	/**
	 * Undo prepare().
	 *
	 * @param array<string,mixed> $state State returned by prepare().
	 * @return void
	 */
	public static function release( $state ) {
		remove_filter( 'paginate_links', array( __CLASS__, 'rewrite_pagination' ) );

		if ( ! empty( $state['active'] ) && isset( $GLOBALS['wp'] ) && is_object( $GLOBALS['wp'] ) ) {
			$GLOBALS['wp']->query_vars = is_array( $state['query_vars'] ) ? $state['query_vars'] : $GLOBALS['wp']->query_vars;
		}

		/**
		 * Fires after a panel view rendered off the account page.
		 */
		do_action( 'dashwoo_account_panel_release' );
	}

	/**
	 * Page number requested (1 when none).
	 *
	 * @return int
	 */
	public static function requested_page() {
		if ( isset( $_GET['dw_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return max( 1, (int) $_GET['dw_page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( function_exists( 'get_query_var' ) ) {
			$paged = (int) get_query_var( 'paged' );

			if ( $paged > 0 ) {
				return $paged;
			}
		}

		return 1;
	}

	/**
	 * Rewrite a pagination link that WooCommerce built inside an off-page render.
	 *
	 * @param string $link Link.
	 * @return string
	 */
	public static function rewrite_pagination( $link ) {
		$link = (string) $link;

		if ( '' === $link || ! function_exists( 'add_query_arg' ) ) {
			return $link;
		}

		$rest = self::rest_url();

		if ( '' !== $rest && false === strpos( $link, self::ROUTE ) && false === strpos( $link, 'rest_route=' ) ) {
			return $link;
		}

		$page = 1;

		if ( preg_match( '#/page/(\d+)#', $link, $matches ) ) {
			$page = max( 1, (int) $matches[1] );
		} elseif ( preg_match( '#[?&](?:paged|dw_page)=(\d+)#', $link, $matches ) ) {
			$page = max( 1, (int) $matches[1] );
		}

		$view = self::requested_view();
		$view = '' !== $view ? $view : 'dashboard';

		return Endpoints::instance()->url( $view, $page > 1 ? 'page/' . $page : '' );
	}

	/**
	 * Make the URLs inside a swapped view absolute (the AJAX answer is read outside
	 * the account page, so relative links would break).
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function absolutise( $html ) {
		if ( '' === (string) $html || ! function_exists( 'home_url' ) ) {
			return (string) $html;
		}

		$home = rtrim( (string) home_url(), '/' );

		return (string) preg_replace_callback(
			'/(href|action)="(\/(?![\/])[^"]*)"/',
			static function ( $matches ) use ( $home ) {
				return $matches[1] . '="' . $home . $matches[2] . '"';
			},
			(string) $html
		);
	}

	/**
	 * Is this request a panel AJAX call?
	 *
	 * The panel script sends `dw_panel=1` with every swap, so templates and hooks
	 * can tell an in-card load apart from a normal page view.
	 *
	 * @return bool
	 */
	public static function is_ajax() {
		if ( ! isset( $_GET['dw_view'] ) && ! isset( $_GET['dw_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		return isset( $_GET['dw_panel'] ) && '1' === (string) $_GET['dw_panel']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
