<?php
/**
 * My Account endpoints: labels, icons, order and visibility.
 *
 * WooCommerce decides *which* endpoints exist (and it is careful: `downloads` is
 * only offered to a customer who has downloads, `edit-address` only when the shop
 * collects addresses). DashWoo never re-implements that logic - it takes whatever
 * WooCommerce produced and applies the shop owner's presentation choices:
 * rename, reorder, re-icon, hide, and only for the front-end.
 *
 * Everything here is pure data + filters, so it can be tested without WooCommerce.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Endpoint registry + presentation rules.
 */
class Endpoints {

	/**
	 * Settings section that carries the per-endpoint choices.
	 */
	const SECTION = 'account_endpoints';

	/**
	 * Singleton.
	 *
	 * @var Endpoints|null
	 */
	private static $instance = null;

	/**
	 * Request cache of WooCommerce's live menu (one call, many readers).
	 *
	 * @var array<string,string>|null
	 */
	private $live = null;

	/**
	 * True while WooCommerce's own menu is being read.
	 *
	 * `wc_get_account_menu_items()` applies the `woocommerce_account_menu_items`
	 * filter, which is exactly the filter DashWoo hooks - so without this guard the
	 * first read would ask WooCommerce again, forever.
	 *
	 * @var bool
	 */
	private $probing = false;

	/**
	 * Singleton accessor.
	 *
	 * @return Endpoints
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Icon used when the shop owner removed an endpoint's icon.
	 */
	const DEFAULT_ICON = 'chevron_left';

	/**
	 * The endpoints WooCommerce core ships, with DashWoo's Persian defaults.
	 *
	 * `permanent` endpoints cannot be hidden: without them the account page has no
	 * way out (the logout link) and no landing page (the dashboard).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function core() {
		return array(
			'dashboard'       => array(
				'label'     => 'پیشخوان حساب',
				'icon'      => 'space_dashboard',
				'order'     => 10,
				'permanent' => true,
			),
			'orders'          => array(
				'label'     => 'سفارش‌ها',
				'icon'      => 'receipt_long',
				'order'     => 20,
				'permanent' => false,
			),
			'downloads'       => array(
				'label'     => 'دانلودها',
				'icon'      => 'download',
				'order'     => 30,
				'permanent' => false,
			),
			'edit-address'    => array(
				'label'     => 'آدرس‌ها',
				'icon'      => 'home_pin',
				'order'     => 40,
				'permanent' => false,
			),
			'payment-methods' => array(
				'label'     => 'روش‌های پرداخت',
				'icon'      => 'credit_card',
				'order'     => 50,
				'permanent' => false,
			),
			'edit-account'    => array(
				'label'     => 'جزئیات حساب',
				'icon'      => 'manage_accounts',
				'order'     => 60,
				'permanent' => false,
			),
			'customer-logout' => array(
				'label'     => 'خروج از حساب',
				'icon'      => 'logout',
				'order'     => 90,
				'permanent' => true,
			),
		);
	}

	/**
	 * Presentation settings of every endpoint: saved value, else the default.
	 *
	 * Settings are stored flat (`show_orders`, `label_edit_account`, ...) and the
	 * section that renders them is built from `fields()`, so this method and that
	 * one can never drift apart.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function settings() {
		$out = array();

		foreach ( $this->ids() as $id => $definition ) {
			$slug = $this->slug( $id );

			$out[ $id ] = array(
				// Default *visible*: an endpoint the shop never switched off (a fresh
				// install, or a tab added by another plugin) must never disappear just
				// because there is no saved setting for it yet.
				'show'  => (bool) dashwoo_get_setting( self::SECTION . '.show_' . $slug, true ),
				'label' => (string) dashwoo_get_setting( self::SECTION . '.label_' . $slug, $this->default_label( $id ) ),
				'icon'  => (string) dashwoo_get_setting( self::SECTION . '.icon_' . $slug, $this->default_icon( $id ) ),
				'order' => (int) dashwoo_get_setting( self::SECTION . '.order_' . $slug, $this->default_order( $id ) ),
			);
		}

		return $out;
	}

	/**
	 * Endpoint ids the platform can see (WooCommerce's live list when available).
	 *
	 * @return array<int,string>
	 */
	public function keys() {
		return array_keys( $this->ids() );
	}

	/**
	 * Every endpoint id DashWoo knows: WooCommerce's live list plus the core ones.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function ids() {
		$core = self::core();
		$live = $this->live();

		foreach ( array_keys( $live ) as $id ) {
			if ( ! isset( $core[ $id ] ) ) {
				$core[ $id ] = array(
					'label'     => $live[ $id ],
					'icon'      => self::DEFAULT_ICON,
					'order'     => 70,
					'permanent' => false,
				);
			}
		}

		/**
		 * Filter the endpoint registry (labels, icons, permanence).
		 *
		 * @param array<string,array<string,mixed>> $core Endpoint id => definition.
		 */
		return (array) apply_filters( 'dashwoo_account_endpoints', $core );
	}

	/**
	 * What WooCommerce currently offers (empty when WooCommerce is not around).
	 *
	 * @return array<string,string> Endpoint id => label.
	 */
	public function live() {
		if ( null !== $this->live ) {
			return $this->live;
		}

		if ( ! function_exists( 'wc_get_account_menu_items' ) ) {
			$this->live = array();

			return $this->live;
		}

		$items = array();

		$this->probing = true;
		$live          = (array) wc_get_account_menu_items();
		$this->probing = false;

		// WooCommerce applies its own filters here (and the shop may have hidden
		// endpoints already) - DashWoo starts from that result, never from the raw list.
		foreach ( $live as $id => $label ) {
			$id = (string) $id;

			if ( '' !== $id ) {
				$items[ $id ] = (string) $label;
			}
		}

		$this->live = $items;

		return $this->live;
	}

	/**
	 * Are we inside WooCommerce's menu read right now?
	 *
	 * @return bool
	 */
	public function probing() {
		return (bool) $this->probing;
	}

	/**
	 * Forget the request cache (after a customer action, or in tests).
	 *
	 * @return void
	 */
	public function flush() {
		$this->live    = null;
		$this->probing = false;
	}

	/**
	 * Drop the singleton (tests).
	 *
	 * @return void
	 */
	public static function destroy() {
		self::$instance = null;
	}

	/**
	 * The final, ordered, filtered list the front-end uses.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function wire() {
		$settings = $this->settings();
		$live     = $this->live();
		$items    = array();

		foreach ( $this->ids() as $id => $definition ) {
			$state    = isset( $settings[ $id ] ) ? $settings[ $id ] : array(
				'show'  => true,
				'label' => $definition['label'],
				'icon'  => $definition['icon'],
				'order' => $definition['order'],
			);
			$visible  = ! empty( $definition['permanent'] ) || ! empty( $state['show'] );
			$label    = '' !== (string) $state['label'] ? (string) $state['label'] : $definition['label'];

			$items[ $id ] = array(
				'id'        => $id,
				'label'     => (string) $label,
				'icon'      => (string) ( '' !== $state['icon'] ? $state['icon'] : $definition['icon'] ),
				'order'     => (int) ( isset( $state['order'] ) ? $state['order'] : $definition['order'] ),
				'permanent' => ! empty( $definition['permanent'] ),
				'visible'   => (bool) $visible,
				'live'      => isset( $live[ $id ] ),
				'url'       => $this->url( $id ),
				'active'    => $this->is_active( $id ),
			);
		}

		/**
		 * Filter the wire-ready account items.
		 *
		 * @param array<string,array<string,mixed>> $items Item id => definition.
		 */
		$items = (array) apply_filters( 'dashwoo_account_items', $items );

		uasort(
			$items,
			static function ( $a, $b ) {
				if ( (int) $a['order'] === (int) $b['order'] ) {
					return strcmp( (string) $a['id'], (string) $b['id'] );
				}

				return (int) $a['order'] <=> (int) $b['order'];
			}
		);

		return $items;
	}

	/**
	 * Visible items only.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function visible() {
		return array_filter(
			$this->wire(),
			static function ( $item ) {
				return ! empty( $item['visible'] );
			}
		);
	}

	/**
	 * The endpoint the visitor is looking at right now ('dashboard' when none).
	 *
	 * @return string
	 */
	public function current() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			$endpoint = '';

			if ( function_exists( 'WC' ) && WC() && isset( WC()->query ) && method_exists( WC()->query, 'get_current_endpoint' ) ) {
				$endpoint = (string) WC()->query->get_current_endpoint();
			}

			if ( '' !== $endpoint ) {
				return $endpoint;
			}
		}

		// `edit-address` has sub-endpoints (billing / shipping); report the parent.
		if ( function_exists( 'is_wc_endpoint_url' ) ) {
			foreach ( array( 'orders', 'downloads', 'edit-account', 'payment-methods', 'edit-address' ) as $candidate ) {
				if ( is_wc_endpoint_url( $candidate ) ) {
					return $candidate;
				}
			}
		}

		return 'dashboard';
	}

	/**
	 * Is this the active endpoint?
	 *
	 * @param string $id Endpoint id.
	 * @return bool
	 */
	public function is_active( $id ) {
		$current = $this->current();

		if ( 'edit-address' === $id ) {
			return in_array( $current, array( 'edit-address', 'edit-address-billing', 'edit-address-shipping' ), true );
		}

		return $id === $current;
	}

	/**
	 * Endpoint URL (WooCommerce's own builder when available).
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function url( $id ) {
		if ( function_exists( 'wc_get_endpoint_url' ) ) {
			return (string) wc_get_endpoint_url( $id, '', $this->account_url() );
		}

		return $this->account_url();
	}

	/**
	 * The account page URL.
	 *
	 * @return string
	 */
	public function account_url() {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			return (string) wc_get_page_permalink( 'myaccount' );
		}

		if ( function_exists( 'get_permalink' ) ) {
			return (string) get_permalink();
		}

		return home_url( '/' );
	}

	/**
	 * Login URL used when a guest faces a protected part of the account area.
	 *
	 * @return string
	 */
	public function login_url() {
		$account = $this->account_url();

		// WooCommerce's own login form lives on the account page; send guests there
		// instead of to wp-login.php so they stay inside the store design.
		return (string) apply_filters( 'dashwoo_account_login_url', $account );
	}

	/**
	 * Icon name for an endpoint (settings aware).
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function icon( $id ) {
		$items = $this->wire();

		return isset( $items[ $id ]['icon'] ) ? (string) $items[ $id ]['icon'] : self::DEFAULT_ICON;
	}

	/**
	 * Label for an endpoint (settings aware, falls back to WooCommerce's).
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function label( $id ) {
		$items = $this->wire();

		if ( isset( $items[ $id ]['label'] ) ) {
			return (string) $items[ $id ]['label'];
		}

		$live = $this->live();

		return isset( $live[ $id ] ) ? (string) $live[ $id ] : $id;
	}

	/**
	 * Default label of a core endpoint.
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function default_label( $id ) {
		$core = self::core();

		if ( isset( $core[ $id ]['label'] ) ) {
			return (string) $core[ $id ]['label'];
		}

		// An endpoint DashWoo does not ship (added by another shop plugin): keep the
		// label its own code chose, so the shop never sees a raw slug.
		$live = $this->live();

		if ( isset( $live[ $id ] ) && '' !== (string) $live[ $id ] ) {
			return (string) $live[ $id ];
		}

		$words = str_replace( array( '-', '_' ), ' ', (string) $id );

		return ucwords( $words );
	}

	/**
	 * Default icon of a core endpoint.
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function default_icon( $id ) {
		$core = self::core();

		return isset( $core[ $id ]['icon'] ) ? (string) $core[ $id ]['icon'] : self::DEFAULT_ICON;
	}

	/**
	 * Default order of a core endpoint.
	 *
	 * @param string $id Endpoint id.
	 * @return int
	 */
	public function default_order( $id ) {
		$core = self::core();

		return isset( $core[ $id ]['order'] ) ? (int) $core[ $id ]['order'] : 70;
	}

	/**
	 * A settings-safe slug for an endpoint id (`edit-address` -> `edit_address`).
	 *
	 * Settings keys go through `sanitize_key()`, which drops hyphens, so the field
	 * names use underscores and this maps back.
	 *
	 * @param string $id Endpoint id.
	 * @return string
	 */
	public function slug( $id ) {
		return str_replace( '-', '_', (string) $id );
	}

	/**
	 * Settings fields: per endpoint show / label / icon / order.
	 *
	 * Used by the Settings Center section, so the shop owner controls the whole menu
	 * from one place instead of per-block Elementor settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function fields() {
		$fields = array();

		foreach ( $this->ids() as $id => $definition ) {
			$slug  = $this->slug( $id );
			$label = isset( $definition['label'] ) ? (string) $definition['label'] : $id;

			if ( empty( $definition['permanent'] ) ) {
				$fields[ 'show_' . $slug ] = array(
					'key'     => 'show_' . $slug,
					'label'   => sprintf( 'نمایش «%s»', $label ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'اگر خاموش باشد، این مورد از منوی حساب کاربری حذف می‌شود (فقط در نمایش؛ خودِ صفحهٔ ووکامرس دست‌نخورده می‌ماند).',
				);
			}

			$fields[ 'label_' . $slug ] = array(
				'key'     => 'label_' . $slug,
				'label'   => sprintf( 'عنوان «%s»', $label ),
				'type'    => 'text',
				'default' => $label,
				'hint'    => 'این عنوان در منو، تب‌ها و کارت‌های حساب کاربری نشان داده می‌شود.',
			);

			$fields[ 'icon_' . $slug ] = array(
				'key'     => 'icon_' . $slug,
				'label'   => sprintf( 'آیکون «%s»', $label ),
				'type'    => 'text',
				'default' => isset( $definition['icon'] ) ? (string) $definition['icon'] : self::DEFAULT_ICON,
				'hint'    => 'نام آیکون Material Symbols (مثلاً receipt_long، download، credit_card). خالی باشد، آیکون پیش‌فرض DashWoo استفاده می‌شود.',
			);

			$fields[ 'order_' . $slug ] = array(
				'key'     => 'order_' . $slug,
				'label'   => sprintf( 'ترتیب «%s»', $label ),
				'type'    => 'number',
				'default' => isset( $definition['order'] ) ? (int) $definition['order'] : 70,
				'min'     => 0,
				'max'     => 99,
				'step'    => 1,
				'hint'    => 'عدد کوچک‌تر بالاتر می‌آید. خروج همیشه آخر است.',
			);
		}

		return $fields;
	}
}
