<?php
/**
 * The account *sections* DashWoo can draw itself.
 *
 * Used in two situations:
 *   - as the fallback when WooCommerce's own endpoint callback is not available
 *     (a shop that un-hooked it, a page built outside the account page), and
 *   - when the shop owner explicitly switches a widget to "DashWoo layout".
 *
 * Everything here reads through WooCommerce's public CRUD API (orders, downloads)
 * - never through the post tables - which is what the compatibility audit promises.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

use DashWoo\Assets\Icons\Icon_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Section blocks.
 */
class Sections {

	/**
	 * Draw one endpoint section in DashWoo's own layout.
	 *
	 * @param string              $endpoint Endpoint id.
	 * @param array<string,mixed> $args     Args (title, icons, per_page, url).
	 * @return string
	 */
	public static function block( $endpoint, array $args = array() ) {
		$method = 'block_' . str_replace( '-', '_', (string) $endpoint );

		/**
		 * Filter an account section's markup (return a non-empty string to replace it).
		 *
		 * @param string              $html     Markup (empty by default).
		 * @param string              $endpoint Endpoint id.
		 * @param array<string,mixed> $args     Args.
		 */
		$custom = (string) apply_filters( 'dashwoo_account_block', '', $endpoint, $args );

		if ( '' !== $custom ) {
			return $custom;
		}

		if ( method_exists( __CLASS__, $method ) ) {
			return (string) call_user_func( array( __CLASS__, $method ), $args );
		}

		return self::block_unknown( $endpoint, $args );
	}

	/**
	 * Section heading.
	 *
	 * @param string $title Title ('' to skip).
	 * @param array  $args  Args.
	 * @param string $extra Extra markup after the title.
	 * @return string
	 */
	public static function heading( $title, array $args = array(), $extra = '' ) {
		if ( '' === (string) $title ) {
			return '';
		}

		$icon = ! empty( $args['icons'] ) ? Icon_Renderer::render( array( 'icon' => (string) ( $args['icon'] ?? 'chevron_left' ), 'size' => 24 ) ) : '';

		return '<header class="dw-acc__section-head">' . $icon . '<h3 class="dw-acc__section-title">' . esc_html( (string) $title ) . '</h3>' . $extra . '</header>';
	}

	/**
	 * Orders list (CRUD read only).
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	/**
	 * Orders list (CRUD read only).
	 *
	 * The shape is a template: `cards` (the default), `compact`, `table`, `timeline`
	 * or `plain` for a shop that styles the rows itself.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_orders( array $args = array() ) {
		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return '';
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return self::unavailable( __( 'WooCommerce must be active to show your orders.', 'dashwoo' ) );
		}

		$orders = self::orders_data( $args );

		if ( ! $orders ) {
			return self::empty_state( __( 'You have no orders yet.', 'dashwoo' ), __( 'Browse the shop', 'dashwoo' ), function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '' );
		}

		$title   = (string) ( $args['title'] ?? __( 'My orders', 'dashwoo' ) );
		$variant = self::variant( $args, 'cards' );

		switch ( $variant ) {
			case 'compact':
				return self::orders_compact( $orders, $title, $args );

			case 'table':
				return self::orders_table( $orders, $title, $args );

			case 'timeline':
				return self::orders_timeline( $orders, $title, $args );

			case 'plain':
				return self::orders_plain( $orders, $title, $args );
		}

		return self::orders_cards( $orders, $title, $args );
	}

	/**
	 * The order objects for this request (sample data in the builder).
	 *
	 * @param array<string,mixed> $args Args.
	 * @return array<int,object>
	 */
	protected static function orders_data( array $args = array() ) {
		$profile = new Profile();
		$limit   = (int) ( $args['per_page'] ?? 0 );
		$limit   = $limit > 0 ? $limit : 10;

		if ( ! $profile->logged_in() || ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer' => $profile->id(),
				'limit'    => $limit,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);

		$orders = is_array( $orders ) ? $orders : array();

		// Building the page in Elementor: an empty list would render an empty state that
		// cannot be styled, so the builder gets sample orders.
		if ( ! $orders && Preview::active() ) {
			$orders = Preview::orders();
		}

		return $orders;
	}

	/**
	 * Normalise one order for the templates.
	 *
	 * @param object $order Order (WooCommerce or the preview double).
	 * @return array<string,mixed>
	 */
	protected static function order_row( $order ) {
		$date = method_exists( $order, 'get_date_created' ) ? $order->get_date_created() : null;

		return array(
			'order'  => $order,
			'number' => method_exists( $order, 'get_order_number' ) ? (string) $order->get_order_number() : '',
			'date'   => ( $date && is_object( $date ) && method_exists( $date, 'date_i18n' ) ) ? (string) $date->date_i18n( 'Y/m/d' ) : '',
			'status' => method_exists( $order, 'get_status' ) ? (string) $order->get_status() : '',
			'total'  => method_exists( $order, 'get_formatted_order_total' ) ? (string) $order->get_formatted_order_total() : '',
			'url'    => method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '',
		);
	}

	/**
	 * Default orders template: one card per order.
	 *
	 * @param array<int,object>   $orders Orders.
	 * @param string              $title  Heading ('' = none).
	 * @param array<string,mixed> $args   Args.
	 * @return string
	 */
	protected static function orders_cards( array $orders, $title, array $args ) {
		$html = self::heading( $title, $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( __( 'Orders: %d', 'dashwoo' ), count( $orders ) ) ) . '</span>' );
		$html .= '<div class="dw-acc__orders">';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$row = self::order_row( $order );

			$html .= '<a class="dw-acc__order" href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">';
			$html .= self::order_cells( $row, true );
			$html .= '<span class="dw-acc__order-go" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'arrow_back', 'size' => 20 ) ) . '</span>';
			$html .= '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Compact list: number, status and total on one line.
	 *
	 * @param array<int,object>   $orders Orders.
	 * @param string              $title  Heading.
	 * @param array<string,mixed> $args   Args.
	 * @return string
	 */
	protected static function orders_compact( array $orders, $title, array $args ) {
		$html = self::heading( $title, $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( __( 'Orders: %d', 'dashwoo' ), count( $orders ) ) ) . '</span>' );
		$html .= '<ul class="dw-acc__orders dw-acc__orders--compact">';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$row = self::order_row( $order );

			$html .= '<li class="dw-acc__order-row">';
			$html .= '<a class="dw-acc__order dw-acc__order--compact" href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">';
			$html .= '<span class="dw-acc__order-number">#' . esc_html( (string) $row['number'] ) . '</span>';
			$html .= self::status_badge( (string) $row['status'] );
			$html .= '<span class="dw-acc__order-date">' . esc_html( (string) $row['date'] ) . '</span>';
			$html .= '<span class="dw-acc__order-total">' . wp_kses_post( (string) $row['total'] ) . '</span>';
			$html .= '</a></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Table template: the classic shop table, scrollable on small screens.
	 *
	 * @param array<int,object>   $orders Orders.
	 * @param string              $title  Heading.
	 * @param array<string,mixed> $args   Args.
	 * @return string
	 */
	protected static function orders_table( array $orders, $title, array $args ) {
		$html = self::heading( $title, $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( __( 'Orders: %d', 'dashwoo' ), count( $orders ) ) ) . '</span>' );
		$html .= '<div class="dw-acc__table-wrap"><table class="dw-acc__table dw-acc__orders-table">';
		$html .= '<thead><tr>'
			. '<th>' . esc_html( __( 'Number', 'dashwoo' ) ) . '</th>'
			. '<th>' . esc_html( __( 'Date', 'dashwoo' ) ) . '</th>'
			. '<th>' . esc_html( __( 'Status', 'dashwoo' ) ) . '</th>'
			. '<th>' . esc_html( __( 'Total', 'dashwoo' ) ) . '</th>'
			. '<th><span class="screen-reader-text">' . esc_html( __( 'Details', 'dashwoo' ) ) . '</span></th>'
			. '</tr></thead><tbody>';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$row = self::order_row( $order );

			$html .= '<tr>';
			$html .= '<td data-title="' . esc_attr( __( 'Number', 'dashwoo' ) ) . '"><a class="dw-acc__order-number" href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">#' . esc_html( (string) $row['number'] ) . '</a></td>';
			$html .= '<td data-title="' . esc_attr( __( 'Date', 'dashwoo' ) ) . '">' . esc_html( (string) $row['date'] ) . '</td>';
			$html .= '<td data-title="' . esc_attr( __( 'Status', 'dashwoo' ) ) . '">' . self::status_badge( (string) $row['status'] ) . '</td>';
			$html .= '<td data-title="' . esc_attr( __( 'Total', 'dashwoo' ) ) . '">' . wp_kses_post( (string) $row['total'] ) . '</td>';
			$html .= '<td class="dw-acc__table-action"><a class="dw-acc__button dw-acc__button--ghost" href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">' . esc_html( __( 'View', 'dashwoo' ) ) . '</a></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return $html;
	}

	/**
	 * Timeline template: a vertical history of the orders.
	 *
	 * @param array<int,object>   $orders Orders.
	 * @param string              $title  Heading.
	 * @param array<string,mixed> $args   Args.
	 * @return string
	 */
	protected static function orders_timeline( array $orders, $title, array $args ) {
		$html = self::heading( $title, $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( __( 'Orders: %d', 'dashwoo' ), count( $orders ) ) ) . '</span>' );
		$html .= '<ol class="dw-acc__orders dw-acc__orders--timeline">';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$row = self::order_row( $order );

			$html .= '<li class="dw-acc__order-entry">';
			$html .= '<span class="dw-acc__order-dot" aria-hidden="true"></span>';
			$html .= '<div class="dw-acc__order-entry-body">';
			$html .= '<a class="dw-acc__order" href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">';
			$html .= self::order_cells( $row, false );
			$html .= '</a>';
			$html .= '</div></li>';
		}

		$html .= '</ol>';

		return $html;
	}

	/**
	 * Plain template: only the semantics, for a shop that styles the rows itself.
	 *
	 * @param array<int,object>   $orders Orders.
	 * @param string              $title  Heading.
	 * @param array<string,mixed> $args   Args.
	 * @return string
	 */
	protected static function orders_plain( array $orders, $title, array $args ) {
		$html = self::heading( $title, $args );
		$html .= '<ul class="dw-acc__orders--plain">';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$row = self::order_row( $order );

			$html .= '<li><a href="' . esc_url( (string) $row['url'] ) . '" data-dw-order="' . esc_attr( (string) $row['number'] ) . '">'
				. esc_html( sprintf( __( 'Order #%1$s — %2$s — %3$s', 'dashwoo' ), (string) $row['number'], (string) $row['date'], self::status_label( (string) $row['status'] ) ) )
				. '</a></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * The shared cells of an order row (cards + timeline).
	 *
	 * @param array<string,mixed> $row     Normalised order.
	 * @param bool                $with_go Print the arrow cell.
	 * @return string
	 */
	protected static function order_cells( array $row, $with_go = true ) {
		$html  = '<span class="dw-acc__order-number">#' . esc_html( (string) $row['number'] ) . '</span>';
		$html .= '<span class="dw-acc__order-date">' . esc_html( (string) $row['date'] ) . '</span>';
		$html .= self::status_badge( (string) $row['status'] );
		$html .= '<span class="dw-acc__order-total">' . wp_kses_post( (string) $row['total'] ) . '</span>';

		if ( $with_go ) {
			unset( $with_go );
		}

		return $html;
	}

	/**
	 * A status badge.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	protected static function status_badge( $status ) {
		$status = sanitize_html_class( (string) $status );

		return '<span class="dw-acc__order-status dw-acc__order-status--' . esc_attr( $status ) . '">' . esc_html( self::status_label( (string) $status ) ) . '</span>';
	}

	/**
	 * One order in full: number, date, items, totals and the payment method.
	 *
	 * @param array<string,mixed> $args Args (order_id, title, variant).
	 * @return string
	 */
	public static function block_order( array $args = array() ) {
		$order_id = (int) ( $args['order_id'] ?? 0 );
		$order    = self::resolve_order( $order_id );

		if ( is_string( $order ) ) {
			return $order;
		}

		$row     = self::order_row( $order );
		$variant = self::variant( $args, 'summary' );
		$title   = (string) ( $args['title'] ?? sprintf( __( 'Order #%s', 'dashwoo' ), (string) $row['number'] ) );

		switch ( $variant ) {
			case 'items':
				return self::order_items_html( $order, $args );

			case 'table':
				return self::order_items_table( $order, $title, $args );

			case 'plain':
				return self::order_plain( $order, $title, $args );
		}

		return self::order_summary( $order, $title, $args );
	}

	/**
	 * Resolve an order and make sure the visitor may see it.
	 *
	 * @param int $order_id Order id.
	 * @return object|string Order object, or the markup of the error state.
	 */
	protected static function resolve_order( $order_id ) {
		$profile  = new Profile();
		$order_id = max( 0, (int) $order_id );

		if ( ! $profile->logged_in() ) {
			return self::unavailable( __( 'Sign in to see the details of this order.', 'dashwoo' ) );
		}

		if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				return self::unavailable( __( 'This order was not found.', 'dashwoo' ) );
			}

			$owner = method_exists( $order, 'get_customer_id' ) ? (int) $order->get_customer_id() : 0;

			// A customer only ever sees their own order; an administrator editing the
			// account page keeps the sample data so the template stays visible.
			if ( $owner > 0 && $owner !== (int) $profile->id() && ! Renderer::may_edit() ) {
				return self::unavailable( __( 'This order does not belong to your account.', 'dashwoo' ) );
			}

			return $order;
		}

		// No id (or the widget's "0 = latest"): the customer's newest order.
		if ( function_exists( 'wc_get_orders' ) ) {
			$latest = wc_get_orders(
				array(
					'customer' => $profile->id(),
					'limit'    => 1,
					'orderby'  => 'date',
					'order'    => 'DESC',
				)
			);

			if ( is_array( $latest ) && $latest ) {
				return $latest[0];
			}
		}

		if ( Preview::active() ) {
			return Preview::order( $order_id );
		}

		return self::unavailable( __( 'This order was not found.', 'dashwoo' ) );
	}

	/**
	 * The line items of an order, normalised for the templates.
	 *
	 * @param object $order Order.
	 * @return array<int,array<string,mixed>>
	 */
	protected static function order_items( $order ) {
		$rows = array();

		if ( ! method_exists( $order, 'get_items' ) ) {
			return $rows;
		}

		foreach ( (array) $order->get_items() as $item ) {
			if ( is_object( $item ) ) {
				$rows[] = array(
					'name'     => method_exists( $item, 'get_name' ) ? (string) $item->get_name() : '',
					'quantity' => method_exists( $item, 'get_quantity' ) ? (int) $item->get_quantity() : 1,
					'total'    => method_exists( $item, 'get_total' ) ? (string) $item->get_total() : '',
				);

				continue;
			}

			$item   = (array) $item;
			$rows[] = array(
				'name'     => (string) ( $item['name'] ?? '' ),
				'quantity' => (int) ( $item['quantity'] ?? 1 ),
				'total'    => (string) ( $item['total'] ?? '' ),
			);
		}

		return $rows;
	}

	/**
	 * The order-summary meta block (status, date, total, payment method).
	 *
	 * @param object              $order Order.
	 * @param array<string,mixed> $args  Args.
	 * @return string
	 */
	protected static function order_meta( $order, array $args = array() ) {
		$row    = self::order_row( $order );
		$method = method_exists( $order, 'get_payment_method_title' ) ? (string) $order->get_payment_method_title() : '';

		$html  = '<div class="dw-acc__order-detail-meta">';
		$html .= '<span class="dw-acc__order-number">#' . esc_html( (string) $row['number'] ) . '</span>';
		$html .= self::status_badge( (string) $row['status'] );
		$html .= '<span class="dw-acc__order-date">' . esc_html( (string) $row['date'] ) . '</span>';

		if ( '' !== $method ) {
			$html .= '<span class="dw-acc__order-method">' . esc_html( $method ) . '</span>';
		}

		$html .= '<span class="dw-acc__order-total">' . wp_kses_post( (string) $row['total'] ) . '</span>';
		$html .= '</div>';

		unset( $args );

		return $html;
	}

	/**
	 * Order totals rows.
	 *
	 * @param object $order Order.
	 * @return array<string,string>
	 */
	protected static function order_totals( $order ) {
		if ( method_exists( $order, 'get_totals' ) ) {
			$totals = (array) $order->get_totals();

			return array_filter( array_map( 'strval', $totals ) );
		}

		return array();
	}

	/**
	 * Default order template: summary + items + totals.
	 *
	 * @param object              $order Order.
	 * @param string              $title Heading.
	 * @param array<string,mixed> $args  Args.
	 * @return string
	 */
	protected static function order_summary( $order, $title, array $args ) {
		$items = self::order_items( $order );

		$html  = self::heading( $title, $args );
		$html .= self::order_meta( $order, $args );
		$html .= self::order_items_table( $order, '', $args );
		$html .= self::order_totals_html( $order );
		$html .= '<div class="dw-acc__order-actions">';
		$html .= '<a class="dw-acc__button dw-acc__button--ghost" href="' . esc_url( Endpoints::instance()->url( 'orders' ) ) . '" data-dw-panel-back data-dw-target="orders">' . esc_html( __( 'Back to orders', 'dashwoo' ) ) . '</a>';

		if ( $items && method_exists( $order, 'get_checkout_payment_url' ) ) {
			$pay = (string) $order->get_checkout_payment_url();

			if ( '' !== $pay && method_exists( $order, 'needs_payment' ) && $order->needs_payment() ) {
				$html .= '<a class="dw-acc__button" href="' . esc_url( $pay ) . '">' . esc_html( __( 'Pay for order', 'dashwoo' ) ) . '</a>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Items as a table (shared by the summary and the table template).
	 *
	 * @param object              $order Order.
	 * @param string              $title Heading.
	 * @param array<string,mixed> $args  Args.
	 * @return string
	 */
	protected static function order_items_table( $order, $title, array $args ) {
		$items = self::order_items( $order );

		if ( ! $items ) {
			return '';
		}

		$html = '' !== (string) $title ? self::heading( $title, $args ) : '';
		$html .= '<div class="dw-acc__table-wrap"><table class="dw-acc__table dw-acc__order-items">';
		$html .= '<thead><tr>'
			. '<th>' . esc_html( __( 'Item', 'dashwoo' ) ) . '</th>'
			. '<th>' . esc_html( __( 'Qty', 'dashwoo' ) ) . '</th>'
			. '<th>' . esc_html( __( 'Total', 'dashwoo' ) ) . '</th>'
			. '</tr></thead><tbody>';

		foreach ( $items as $item ) {
			$html .= '<tr>';
			$html .= '<td data-title="' . esc_attr( __( 'Item', 'dashwoo' ) ) . '">' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</td>';
			$html .= '<td data-title="' . esc_attr( __( 'Qty', 'dashwoo' ) ) . '">' . esc_html( (string) ( $item['quantity'] ?? 1 ) ) . '</td>';
			$html .= '<td data-title="' . esc_attr( __( 'Total', 'dashwoo' ) ) . '">' . esc_html( (string) ( $item['total'] ?? '' ) ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return $html;
	}

	/**
	 * Items as a list (the `items` template).
	 *
	 * @param object              $order Order.
	 * @param array<string,mixed> $args  Args.
	 * @return string
	 */
	protected static function order_items_html( $order, array $args ) {
		$items = self::order_items( $order );

		if ( ! $items ) {
			return self::empty_state( __( 'This order has no items.', 'dashwoo' ) );
		}

		$html = '';
		$html .= '<ul class="dw-acc__order-items-list">';

		foreach ( $items as $item ) {
			$html .= '<li class="dw-acc__order-item">';
			$html .= '<span class="dw-acc__order-item-name">' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</span>';
			$html .= '<span class="dw-acc__order-item-qty">×' . esc_html( (string) ( $item['quantity'] ?? 1 ) ) . '</span>';
			$html .= '<span class="dw-acc__order-item-total">' . esc_html( (string) ( $item['total'] ?? '' ) ) . '</span>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		unset( $args );

		return $html;
	}

	/**
	 * Totals block.
	 *
	 * @param object $order Order.
	 * @return string
	 */
	protected static function order_totals_html( $order ) {
		$totals = self::order_totals( $order );

		if ( ! $totals ) {
			return '';
		}

		$html = '<dl class="dw-acc__order-totals">';

		foreach ( $totals as $label => $value ) {
			$html .= '<div class="dw-acc__order-total-row"><dt>' . esc_html( (string) $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd></div>';
		}

		$html .= '</dl>';

		return $html;
	}

	/**
	 * Plain order template: semantics only.
	 *
	 * @param object              $order Order.
	 * @param string              $title Heading.
	 * @param array<string,mixed> $args  Args.
	 * @return string
	 */
	protected static function order_plain( $order, $title, array $args ) {
		$row   = self::order_row( $order );
		$items = self::order_items( $order );

		$html  = self::heading( $title, $args );
		$html .= '<p>' . esc_html( sprintf( __( 'Status: %1$s — Date: %2$s — Total: %3$s', 'dashwoo' ), self::status_label( (string) $row['status'] ), (string) $row['date'], (string) $row['total'] ) ) . '</p>';
		$html .= '<ul>';

		foreach ( $items as $item ) {
			$html .= '<li>' . esc_html( sprintf( '%1$s × %2$s — %3$s', (string) ( $item['name'] ?? '' ), (string) ( $item['quantity'] ?? 1 ), (string) ( $item['total'] ?? '' ) ) ) . '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Which template variation the caller asked for.
	 *
	 * @param array<string,mixed> $args    Args.
	 * @param string              $default Fallback.
	 * @return string
	 */
	protected static function variant( array $args, $default ) {
		foreach ( array( 'variant', 'template' ) as $key ) {
			if ( isset( $args[ $key ] ) && '' !== (string) $args[ $key ] ) {
				return sanitize_key( (string) $args[ $key ] );
			}
		}

		return (string) $default;
	}

	/**
	 * Downloads list.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	/**
	 * Downloads: `list` (default), `grid`, `table` or `plain`.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_downloads( array $args = array() ) {
		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return '';
		}

		if ( ! function_exists( 'wc_get_customer_available_downloads' ) ) {
			return self::unavailable( __( 'WooCommerce must be active for this section.', 'dashwoo' ) );
		}

		$downloads = (array) wc_get_customer_available_downloads( $profile->id() );

		if ( ! $downloads && Preview::active() ) {
			$downloads = Preview::downloads();
		}

		$downloads = array_values( array_filter( $downloads, 'is_array' ) );

		if ( ! $downloads ) {
			return self::empty_state( __( 'There is nothing to download.', 'dashwoo' ), '', '' );
		}

		$title   = (string) ( $args['title'] ?? __( 'My downloads', 'dashwoo' ) );
		$variant = self::variant( $args, 'list' );

		switch ( $variant ) {
			case 'grid':
				return self::downloads_grid( $downloads, $title, $args );

			case 'table':
				return self::downloads_table( $downloads, $title, $args );

			case 'plain':
				return self::downloads_plain( $downloads, $title, $args );
		}

		return self::downloads_list( $downloads, $title, $args );
	}

	/**
	 * Default downloads template.
	 *
	 * @param array<int,array<string,mixed>> $downloads Downloads.
	 * @param string                         $title     Heading.
	 * @param array<string,mixed>            $args      Args.
	 * @return string
	 */
	protected static function downloads_list( array $downloads, $title, array $args ) {
		$html = self::heading( $title, $args );

		if ( ! empty( $args['icons'] ) ) {
			$html = self::heading( $title, $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( __( 'Files: %d', 'dashwoo' ), count( $downloads ) ) ) . '</span>' );
		}

		$html .= '<ul class="dw-acc__downloads">';

		foreach ( $downloads as $download ) {
			$html .= '<li class="dw-acc__download">';
			$html .= '<span class="dw-acc__download-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'download', 'size' => 22 ) ) . '</span>';
			$html .= '<a class="dw-acc__download-link" href="' . esc_url( (string) ( $download['download_url'] ?? '' ) ) . '">' . esc_html( (string) ( $download['product_name'] ?? '' ) ) . '</a>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Card grid template.
	 *
	 * @param array<int,array<string,mixed>> $downloads Downloads.
	 * @param string                         $title     Heading.
	 * @param array<string,mixed>            $args      Args.
	 * @return string
	 */
	protected static function downloads_grid( array $downloads, $title, array $args ) {
		$html = self::heading( $title, $args );
		$html .= '<div class="dw-acc__downloads-grid">';

		foreach ( $downloads as $download ) {
			$name = (string) ( $download['product_name'] ?? '' );
			$url  = (string) ( $download['download_url'] ?? '' );

			$html .= '<a class="dw-acc__download-card" href="' . esc_url( $url ) . '">';
			$html .= '<span class="dw-acc__download-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'download', 'size' => 26 ) ) . '</span>';
			$html .= '<span class="dw-acc__download-name">' . esc_html( $name ) . '</span>';

			if ( ! empty( $download['download_name'] ) ) {
				$html .= '<span class="dw-acc__download-file">' . esc_html( (string) $download['download_name'] ) . '</span>';
			}

			$html .= '<span class="dw-acc__button dw-acc__button--ghost">' . esc_html( __( 'Download', 'dashwoo' ) ) . '</span>';
			$html .= '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Table template.
	 *
	 * @param array<int,array<string,mixed>> $downloads Downloads.
	 * @param string                         $title     Heading.
	 * @param array<string,mixed>            $args      Args.
	 * @return string
	 */
	protected static function downloads_table( array $downloads, $title, array $args ) {
		$html = self::heading( $title, $args );
		$html .= '<div class="dw-acc__table-wrap"><table class="dw-acc__table dw-acc__downloads-table">';
		$html .= '<thead><tr><th>' . esc_html( __( 'File', 'dashwoo' ) ) . '</th><th>' . esc_html( __( 'Product', 'dashwoo' ) ) . '</th><th></th></tr></thead><tbody>';

		foreach ( $downloads as $download ) {
			$html .= '<tr>';
			$html .= '<td data-title="' . esc_attr( __( 'File', 'dashwoo' ) ) . '">' . esc_html( (string) ( $download['download_name'] ?? '' ) ) . '</td>';
			$html .= '<td data-title="' . esc_attr( __( 'Product', 'dashwoo' ) ) . '">' . esc_html( (string) ( $download['product_name'] ?? '' ) ) . '</td>';
			$html .= '<td class="dw-acc__table-action"><a class="dw-acc__button dw-acc__button--ghost" href="' . esc_url( (string) ( $download['download_url'] ?? '' ) ) . '">' . esc_html( __( 'Download', 'dashwoo' ) ) . '</a></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return $html;
	}

	/**
	 * Plain template.
	 *
	 * @param array<int,array<string,mixed>> $downloads Downloads.
	 * @param string                         $title     Heading.
	 * @param array<string,mixed>            $args      Args.
	 * @return string
	 */
	protected static function downloads_plain( array $downloads, $title, array $args ) {
		$html = self::heading( $title, $args );
		$html .= '<ul class="dw-acc__downloads--plain">';

		foreach ( $downloads as $download ) {
			$html .= '<li><a href="' . esc_url( (string) ( $download['download_url'] ?? '' ) ) . '">' . esc_html( (string) ( $download['product_name'] ?? '' ) ) . '</a></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Addresses: a link to WooCommerce's own editors (the only writable address
	 * store is WooCommerce's, so DashWoo never fakes an address field).
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_edit_address( array $args = array() ) {
		$form     = new Form_Adapter();
		$profile  = new Profile();
		$html     = self::heading( (string) ( $args['title'] ?? __( 'My addresses', 'dashwoo' ) ), $args );
		$sections = array(
			'billing'  => __( 'Billing address', 'dashwoo' ),
			'shipping' => __( 'Shipping address', 'dashwoo' ),
		);

		$variant = self::variant( $args, 'cards' );
		$classes = 'plain' === $variant ? 'dw-acc__addresses--plain' : 'dw-acc__addresses';
		$html   .= '<div class="' . esc_attr( $classes ) . '">';

		foreach ( $sections as $type => $label ) {
			$values  = array();
			$fields  = array( 'first_name', 'last_name', 'address_1', 'city', 'state', 'postcode', 'country' );

			foreach ( $fields as $field ) {
				$value = (string) $form->read( $type . '_' . $field );

				if ( '' !== $value ) {
					$values[] = $value;
				}
			}

			$target = $profile->logged_in() && function_exists( 'wc_get_endpoint_url' )
				? (string) wc_get_endpoint_url( 'edit-address', $type, Endpoints::instance()->account_url() )
				: (string) ( $args['url'] ?? '' );

			$html .= '<div class="dw-acc__address' . ( 'list' === $variant ? ' dw-acc__address--row' : '' ) . '">';
			$html .= '<h4 class="dw-acc__address-title">' . esc_html( $label ) . '</h4>';
			$html .= '<p class="dw-acc__address-lines">' . ( $values ? esc_html( implode( __( ', ', 'dashwoo' ), $values ) ) : '<span class="dw-acc__muted">' . esc_html__( 'Not added yet.', 'dashwoo' ) . '</span>' ) . '</p>';
			$html .= 'plain' === $variant
				? '<a href="' . esc_url( $target ) . '">' . esc_html( __( 'Edit ', 'dashwoo' ) . $label ) . '</a>'
				: '<a class="dw-acc__button dw-acc__button--ghost" href="' . esc_url( $target ) . __( '">Edit ', 'dashwoo' ) . esc_html( $label ) . '</a>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Payment methods: WooCommerce keeps them (tokens); DashWoo only frames them.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_payment_methods( array $args = array() ) {
		$html = self::heading( (string) ( $args['title'] ?? __( 'Payment methods', 'dashwoo' ) ), $args );
		$html .= self::empty_state(
			__( 'Stored payment methods are managed by WooCommerce. If your gateway supports saved secure payment, that same list is shown here.', 'dashwoo' ),
			__( 'Open the WooCommerce payment section', 'dashwoo' ),
			(string) ( $args['url'] ?? '' )
		);

		return $html;
	}

	/**
	 * Account details: the fields DashWoo may write, plus a link to WooCommerce's
	 * own form for email/password (which need WooCommerce's validation).
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_edit_account( array $args = array() ) {
		$form    = new Form_Adapter();
		$profile = new Profile();

		$html = self::heading( (string) ( $args['title'] ?? __( 'Account details', 'dashwoo' ) ), $args );

		if ( ! $profile->logged_in() ) {
			return $html;
		}

		$html .= '<div class="dw-acc__details">';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">' . esc_html__( 'Display name', 'dashwoo' ) . '</span><span class="dw-acc__field-value">' . esc_html( $form->read( 'display_name' ) ) . '</span></div>';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">' . esc_html__( 'Email', 'dashwoo' ) . '</span><span class="dw-acc__field-value">' . esc_html( $profile->email() ) . '</span></div>';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">' . esc_html__( 'Name', 'dashwoo' ) . '</span><span class="dw-acc__field-value">' . esc_html( trim( $form->read( 'first_name' ) . ' ' . $form->read( 'last_name' ) ) ) . '</span></div>';
		$html .= '</div>';

		if ( ! empty( $args['url'] ) ) {
			$html .= '<a class="dw-acc__button" href="' . esc_url( (string) $args['url'] ) . '">' . esc_html__( 'Edit the details in the WooCommerce form', 'dashwoo' ) . '</a>';
		}

		return $html;
	}

	/**
	 * Logout.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function block_customer_logout( array $args = array() ) {
		$url = function_exists( 'wc_logout_url' ) ? (string) wc_logout_url() : ( function_exists( 'wp_logout_url' ) ? (string) wp_logout_url() : '' );

		if ( '' === $url ) {
			return '';
		}

		$label = (string) ( $args['title'] ?? '' );

		if ( '' === trim( $label ) ) {
			$label = __( 'Log out', 'dashwoo' );
		}

		if ( 'plain' === self::variant( $args, 'button' ) ) {
			return '<div class="dw-acc__logout--plain"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></div>';
		}

		$html  = '<div class="dw-acc__logout">';
		$html .= '<a class="dw-acc__button dw-acc__button--ghost dw-acc__logout-button" href="' . esc_url( $url ) . '">';
		$html .= Icon_Renderer::render( array( 'icon' => 'logout', 'size' => 20 ) );
		$html .= '<span>' . esc_html( $label ) . '</span></a></div>';

		return $html;
	}

	/**
	 * Profile card: avatar, name, contact lines and the counts DashWoo can read.
	 *
	 * @param array<string,mixed> $args Args: variant (card|compact), avatar, fields.
	 * @return string
	 */
	public static function profile( array $args = array() ) {
		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return '';
		}

		$variant = (string) ( $args['variant'] ?? 'card' );
		$avatar  = ! isset( $args['avatar'] ) || $args['avatar'];
		$fields  = isset( $args['fields'] ) && is_array( $args['fields'] ) ? $args['fields'] : array( 'email' );

		if ( 'plain' === $variant ) {
			$html  = '<div class="dw-acc__profile--plain">';
			$html .= '<p>' . esc_html( $profile->name() ) . '</p>';
			$html .= '<p>' . esc_html( $profile->email() ) . '</p></div>';

			return $html;
		}

		$html  = '<div class="dw-acc__profile dw-acc__profile--' . esc_attr( sanitize_html_class( $variant ) ) . '">';

		if ( $avatar ) {
			$picture = $profile->avatar( 128 );

			$html .= '<div class="dw-acc__profile-avatar">';

			if ( '' !== $picture ) {
				$html .= $picture;
			} else {
				$html .= '<span class="dw-acc__initials">' . esc_html( $profile->initials() ) . '</span>';
			}

			$html .= '</div>';
		}

		$html .= '<div class="dw-acc__profile-body">';
		$html .= '<p class="dw-acc__profile-name">' . esc_html( $profile->name() ) . '</p>';

		foreach ( $fields as $field ) {
			$field = (string) $field;

			switch ( $field ) {
				case 'email':
					$value = $profile->email();
					break;
				case 'orders':
					$value = sprintf( __( 'Orders: %d', 'dashwoo' ), $profile->order_count() );
					break;
				default:
					$value = ( new Form_Adapter() )->read( $field );
			}

			if ( '' !== (string) $value ) {
				$html .= '<p class="dw-acc__profile-line"><span class="dw-acc__muted">' . esc_html( $field ) . ':</span> ' . esc_html( (string) $value ) . '</p>';
			}
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Form blocks for the "forms" widget: profile (display name), address links,
	 * password link and a login form for guests.
	 *
	 * @param array<string,mixed> $args Args: form (profile|address|password|login), fields, target, values.
	 * @return string
	 */
	public static function forms( array $args = array() ) {
		$form_name = isset( $args['form'] ) ? sanitize_key( (string) $args['form'] ) : 'profile';

		if ( 'login' === $form_name ) {
			return self::form_login( $args );
		}

		if ( 'address' === $form_name ) {
			return self::block_edit_address( $args );
		}

		if ( 'password' === $form_name ) {
			$target = ( new Form_Adapter() )->action_for( 'password' );

			$html  = self::heading( (string) ( $args['title'] ?? __( 'Change password', 'dashwoo' ) ), $args );
			$html .= self::empty_state(
				__( 'The password change is handled by WooCommerce (with the current password confirmation and a notification email) so the account stays secure.', 'dashwoo' ),
				__( 'Change the password in the WooCommerce form', 'dashwoo' ),
				(string) $target['url']
			);

			return $html;
		}

		return self::form_profile( $args );
	}

	/**
	 * The one DashWoo form that writes: display name.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	protected static function form_profile( array $args = array() ) {
		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return '';
		}

		$form   = new Form_Adapter();
		$values = $form->values();
		$fields = isset( $args['fields'] ) && is_array( $args['fields'] ) ? $args['fields'] : array( 'display_name' );
		$action = $form->action_for( 'profile' );

		$html = self::heading( (string) ( $args['title'] ?? __( 'My account details', 'dashwoo' ) ), $args );
		$html .= '<form class="dw-acc__form" method="post" action="' . esc_url( (string) $action['url'] ) . '">';
		$html .= '<div class="dw-acc__form-grid">';

		foreach ( $fields as $field ) {
			$field = (string) $field;
			$key   = 'display_name' === $field ? 'display_name' : $field;
			$type  = in_array( $key, array( 'billing_email', 'billing_phone' ), true ) ? 'text' : 'text';

			$html .= '<label class="dw-acc__input"><span class="dw-acc__input-label">' . esc_html( self::field_label( $key ) ) . '</span>';
			$html .= '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) ( $values[ $key ] ?? '' ) ) . '"'
				. ( 'display_name' === $key ? '' : ' readonly' ) . ' /></label>';
		}

		$html .= '</div>';
		$html .= '<input type="hidden" name="dw_account_form" value="profile" />';
		$html .= '<p class="dw-acc__form-actions"><button type="submit" class="dw-acc__button dw-acc__button--primary">' . esc_html__( 'Save changes', 'dashwoo' ) . '</button></p>';
		$html .= '</form>';

		$html .= self::empty_state(
			__( 'Email, password and addresses are saved with WooCommerce\'s own validation.', 'dashwoo' ),
			__( 'Edit everything in the WooCommerce form', 'dashwoo' ),
			(string) ( $action['url'] ?? '' )
		);

		return $html;
	}

	/**
	 * Login block for guests.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	protected static function form_login( array $args = array() ) {
		$action = ( new Form_Adapter() )->action_for( 'login' );

		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			return self::empty_state( __( 'You are signed in.', 'dashwoo' ), __( 'Go to my account dashboard', 'dashwoo' ), (string) $action['url'] );
		}

		$html  = '<div class="dw-acc__login-form">';
		$html .= '<form method="post" action="' . esc_url( (string) $action['url'] ) . '" class="dw-acc__form">';
		$html .= '<label class="dw-acc__input"><span class="dw-acc__input-label">' . esc_html__( 'Username or email', 'dashwoo' ) . '</span><input type="text" name="username" autocomplete="username" required /></label>';
		$html .= '<label class="dw-acc__input"><span class="dw-acc__input-label">' . esc_html__( 'Password', 'dashwoo' ) . '</span><input type="password" name="password" autocomplete="current-password" required /></label>';
		$html .= '<label class="dw-acc__check"><input type="checkbox" name="rememberme" value="forever" /> ' . esc_html__( 'Remember me', 'dashwoo' ) . '</label>';
		$html .= '<p class="dw-acc__form-actions"><button type="submit" class="dw-acc__button dw-acc__button--primary">' . esc_html__( 'Log in', 'dashwoo' ) . '</button></p>';
		$html .= '</form></div>';

		return $html;
	}

	/**
	 * Human label for a field key.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public static function field_label( $key ) {
		$labels = array(
			'display_name'   => __( 'Display name', 'dashwoo' ),
			'first_name'     => __( 'First name', 'dashwoo' ),
			'last_name'      => __( 'Last name', 'dashwoo' ),
			'email'          => __( 'Email', 'dashwoo' ),
			'billing_email'  => __( 'Billing email', 'dashwoo' ),
			'billing_phone'  => __( 'Phone', 'dashwoo' ),
		);

		/**
		 * Filter the label of an account form field.
		 *
		 * @param string $label Label.
		 * @param string $key   Field key.
		 */
		return (string) apply_filters( 'dashwoo_account_field_label', $labels[ $key ] ?? $key, $key );
	}

	/**
	 * Persian label for a WooCommerce order status.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function status_label( $status ) {
		$map = array(
			'pending'    => __( 'Pending payment', 'dashwoo' ),
			'processing' => __( 'Processing', 'dashwoo' ),
			'on-hold'    => __( 'On hold', 'dashwoo' ),
			'completed'  => __( 'Completed', 'dashwoo' ),
			'cancelled'  => __( 'Cancelled', 'dashwoo' ),
			'refunded'   => __( 'Refunded', 'dashwoo' ),
			'failed'     => __( 'Failed', 'dashwoo' ),
			'checkout-draft' => __( 'Draft', 'dashwoo' ),
		);

		/**
		 * Filter the order status label.
		 *
		 * @param string $label  Label.
		 * @param string $status Status slug.
		 */
		return (string) apply_filters( 'dashwoo_account_order_status_label', $map[ $status ] ?? $status, $status );
	}

	/**
	 * Empty-state card.
	 *
	 * @param string $text Text.
	 * @param string $cta  Button label ('' = none).
	 * @param string $url  Button URL.
	 * @return string
	 */
	public static function empty_state( $text, $cta = '', $url = '' ) {
		$html  = '<div class="dw-acc__empty"><p class="dw-acc__empty-text">' . esc_html( (string) $text ) . '</p>';

		if ( '' !== (string) $cta && '' !== (string) $url ) {
			$html .= '<a class="dw-acc__button" href="' . esc_url( (string) $url ) . '">' . esc_html( (string) $cta ) . '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * "Not available" explanation.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function unavailable( $text ) {
		return Renderer::ready() || Renderer::may_edit() ? self::empty_state( $text ) : '';
	}

	/**
	 * Unknown endpoint: say so instead of rendering nothing silently.
	 *
	 * @param string $endpoint Endpoint id.
	 * @param array  $args     Args.
	 * @return string
	 */
	public static function block_unknown( $endpoint, array $args = array() ) {
		if ( ! Renderer::may_edit() ) {
			return '';
		}

		return self::empty_state(
			sprintf( __( 'No content was found for “%s” (neither from WooCommerce nor from a DashWoo shortcut).', 'dashwoo' ), (string) $endpoint ),
			'',
			''
		);
	}
}
