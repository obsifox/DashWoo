<?php
/**
 * WooCommerce feature compatibility declarations.
 *
 * WooCommerce flags every plugin that advertises the `WC tested up to` header but
 * never tells WooCommerce whether it works with a given feature. For a feature whose
 * `default_plugin_compatibility` is `incompatible` - High-Performance Order Storage is
 * one - an undeclared plugin is treated as *incompatible*, and the merchant sees
 * "WooCommerce has detected that some of your active plugins are incompatible with
 * currently enabled WooCommerce features".
 *
 * DashWoo advertises that header, so this class declares its verdict for every
 * WooCommerce feature through `FeaturesUtil::declare_compatibility()`, hooked exactly
 * where WooCommerce requires it: `before_woocommerce_init`, from the main plugin file.
 *
 * @package DashWoo
 */

namespace DashWoo\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Declares (and verifies) DashWoo's WooCommerce feature compatibility.
 */
class WooCommerce_Features {

	/**
	 * WooCommerce's declaration API.
	 */
	const FEATURES_UTIL = 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil';

	/**
	 * WooCommerce's controller, used only to ask which features exist.
	 */
	const CONTROLLER = 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController';

	/**
	 * Singleton.
	 *
	 * @var WooCommerce_Features|null
	 */
	private static $instance = null;

	/**
	 * Declarations sent during this request: feature id => bool.
	 *
	 * @var array<string,bool>
	 */
	private $declared = array();

	/**
	 * Singleton accessor.
	 *
	 * @return WooCommerce_Features
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook the declaration where WooCommerce expects it.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_all' ), 10, 0 );
	}

	/**
	 * The audit table: WooCommerce feature id => verdict.
	 *
	 * Every entry was reviewed against DashWoo's own code. DashWoo is a UI layer: it
	 * renders design tokens, local fonts, icons and assets and uses the documented
	 * template hooks. Its **only** WooCommerce data reads are two CRUD calls -
	 * `wc_get_product()->get_price_html()` in the v8 adapter and
	 * `wc_get_order()->get_status()` in the v9 adapter - and the CRUD API is the
	 * HPOS-safe path by definition: no post tables, no post meta, no order SQL.
	 *
	 * `test_the_shipped_plugin_only_reads_woocommerce_data_through_the_crud_api` guards
	 * that statement. The day the code grows a direct storage read, the test fails and
	 * the audit has to be redone before the verdicts are trusted again.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function audits() {
		$reason = 'DashWoo is a presentation layer: template hooks, the cart badge and two CRUD reads - no direct storage access.';

		$features = array(
			'custom_order_tables'         => array(
				'label' => __( 'High-Performance Order Storage (HPOS)', 'dashwoo' ),
				'why'   => 'The one order read goes through wc_get_order()->get_status(), i.e. the CRUD API WooCommerce recommends under HPOS; no post tables or post meta are touched.',
			),
			'cart_checkout_blocks'        => array(
				'label' => __( 'Cart and Checkout blocks', 'dashwoo' ),
				'why'   => 'The v9 adapter hooks the documented block checkout actions and adds no cart logic of its own.',
			),
			'product_block_editor'        => array(
				'label' => __( 'Product block editor', 'dashwoo' ),
				'why'   => 'No product editor extension is registered; product markup is styled with tokens only.',
			),
			'analytics'                   => array(
				'label' => __( 'Analytics', 'dashwoo' ),
				'why'   => 'WooCommerce Analytics screens are untouched.',
			),
			'rate_limit_checkout'         => array(
				'label' => __( 'Checkout rate limiting', 'dashwoo' ),
				'why'   => 'No checkout request handling, no rate logic.',
			),
			'marketplace'                 => array(
				'label' => __( 'Marketplace', 'dashwoo' ),
				'why'   => 'No product listing or marketplace integration.',
			),
			'order_withdrawal'            => array(
				'label' => __( 'Order withdrawal', 'dashwoo' ),
				'why'   => 'No order lifecycle participation.',
			),
			'order_attribution'           => array(
				'label' => __( 'Order attribution', 'dashwoo' ),
				'why'   => 'No order meta is written.',
			),
			'site_visibility_badge'       => array(
				'label' => __( 'Site visibility badge', 'dashwoo' ),
				'why'   => 'No storefront badge or visibility logic.',
			),
			'hpos_fts_indexes'            => array(
				'label' => __( 'HPOS full-text search indexes', 'dashwoo' ),
				'why'   => 'No direct SQL against order tables.',
			),
			'hpos_datastore_caching'      => array(
				'label' => __( 'HPOS datastore caching', 'dashwoo' ),
				'why'   => 'No order datastore usage.',
			),
			'remote_logging'              => array(
				'label' => __( 'Remote logging', 'dashwoo' ),
				'why'   => 'DashWoo logs into its own option and never to a remote endpoint.',
			),
			'deferred_transactional_emails' => array(
				'label' => __( 'Deferred transactional emails', 'dashwoo' ),
				'why'   => 'No email sending or email template overrides.',
			),
			'customer_review_request'     => array(
				'label' => __( 'Customer review request', 'dashwoo' ),
				'why'   => 'No scheduled customer communication.',
			),
			'email_improvements'          => array(
				'label' => __( 'Email improvements', 'dashwoo' ),
				'why'   => 'WooCommerce email templates are not filtered.',
			),
			'block_email_editor'          => array(
				'label' => __( 'Block email editor', 'dashwoo' ),
				'why'   => 'No email template edits.',
			),
			'blueprint'                   => array(
				'label' => __( 'Store blueprint', 'dashwoo' ),
				'why'   => 'No store setup wizard participation.',
			),
			'wc-visual-attribute'         => array(
				'label' => __( 'Product attributes', 'dashwoo' ),
				'why'   => 'Attribute markup is only styled through the design tokens.',
			),
			'point_of_sale'               => array(
				'label' => __( 'Point of Sale (POS)', 'dashwoo' ),
				'why'   => 'No POS integration.',
			),
			'point_of_sale_staff'         => array(
				'label' => __( 'POS staff', 'dashwoo' ),
				'why'   => 'No POS integration.',
			),
			'fulfillments'                => array(
				'label' => __( 'Fulfillments', 'dashwoo' ),
				'why'   => 'No fulfillment workflow.',
			),
			'mcp_integration'             => array(
				'label' => __( 'MCP integration', 'dashwoo' ),
				'why'   => 'No MCP tool registration.',
			),
			'destroy-empty-sessions'      => array(
				'label' => __( 'Clear empty sessions', 'dashwoo' ),
				'why'   => 'No session handling.',
			),
			'rest_api_caching'            => array(
				'label' => __( 'REST API caching', 'dashwoo' ),
				'why'   => 'DashWoo exposes its own namespace (dashwoo/v1) and does not intercept WooCommerce routes.',
			),
			'cart_save_for_later'         => array(
				'label' => __( 'Save cart for later', 'dashwoo' ),
				'why'   => 'No cart data manipulation.',
			),
			'product_wishlist'            => array(
				'label' => __( 'Wishlist', 'dashwoo' ),
				'why'   => 'No wishlist storage or markup.',
			),
			'address_autocomplete'        => array(
				'label' => __( 'Address autocomplete', 'dashwoo' ),
				'why'   => 'No address fields are registered or filtered.',
			),
			'new_product_editor'          => array(
				'label' => __( 'New product editor', 'dashwoo' ),
				'why'   => 'No product editor extension.',
			),
		);

		foreach ( $features as $id => $feature ) {
			$features[ $id ]['compatible'] = true;
			$features[ $id ]['why']        = isset( $feature['why'] ) ? $feature['why'] : $reason;
		}

		/**
		 * Filter DashWoo's WooCommerce feature verdicts.
		 *
		 * A site that knows better can flip a single feature to `false` (which makes
		 * WooCommerce warn about DashWoo *and* hide the corresponding feature switch),
		 * or add feature ids of its own.
		 *
		 * @param array<string,array<string,mixed>> $features Feature id => verdict.
		 */
		return (array) apply_filters( 'dashwoo_wc_feature_compatibility', $features );
	}

	/**
	 * Feature ids WooCommerce knows about right now (its own list + ours).
	 *
	 * @return array<int,string>
	 */
	public function feature_ids() {
		$ids = array_keys( $this->audits() );

		foreach ( $this->wc_feature_ids() as $id ) {
			if ( ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Ask WooCommerce which features exist (best effort, never fatal).
	 *
	 * @return array<int,string>
	 */
	public function wc_feature_ids() {
		if ( ! function_exists( 'wc_get_container' ) || ! class_exists( static::CONTROLLER ) ) {
			return array();
		}

		try {
			$controller = wc_get_container()->get( static::CONTROLLER );

			if ( ! is_object( $controller ) || ! method_exists( $controller, 'get_feature_definitions' ) ) {
				return array();
			}

			$definitions = (array) $controller->get_feature_definitions( false, true, true );

			return array_map( 'strval', array_keys( $definitions ) );
		} catch ( \Throwable $e ) {
			// A WooCommerce version we do not know must never break the store.
			return array();
		}
	}

	/**
	 * Whether a feature is compatible according to the audit (unknown ids default to yes).
	 *
	 * @param string $feature_id Feature id.
	 * @return bool
	 */
	public function verdict( $feature_id ) {
		$audits = $this->audits();

		if ( isset( $audits[ $feature_id ]['compatible'] ) ) {
			return (bool) $audits[ $feature_id ]['compatible'];
		}

		return true;
	}

	/**
	 * Send every declaration to WooCommerce.
	 *
	 * Safe to call when WooCommerce is absent: then there is nothing to declare and
	 * the method returns an empty array.
	 *
	 * @return array<string,bool> Declarations sent: feature id => compatible.
	 */
	public function declare_all() {
		if ( ! class_exists( static::FEATURES_UTIL ) ) {
			return array();
		}

		$sent = array();

		foreach ( $this->feature_ids() as $feature_id ) {
			$compatible = $this->verdict( (string) $feature_id );

			try {
				call_user_func( array( static::FEATURES_UTIL, 'declare_compatibility' ), (string) $feature_id, DASHWOO_FILE, $compatible );
			} catch ( \Throwable $e ) {
				// A WooCommerce API change must never white-screen the admin.
				continue;
			}

			$sent[ $feature_id ] = $compatible;
		}

		$this->declared = $sent;

		do_action( 'dashwoo_wc_features_declared', $sent );

		return $sent;
	}

	/**
	 * Declarations sent during this request.
	 *
	 * @return array<string,bool>
	 */
	public function declared() {
		return $this->declared;
	}

	/**
	 * WooCommerce's own verdict about DashWoo, read back for the report.
	 *
	 * @return array<string,mixed>
	 */
	public function status() {
		$status = array(
			'plugin'       => DASHWOO_BASENAME,
			'aware'        => false,
			'declared'     => $this->declared,
			'total'        => count( $this->feature_ids() ),
			'unknown'      => array(),
			'compatible'   => array(),
			'incompatible' => array(),
			'uncertain'    => array(),
			'error'        => '',
		);

		$audits = $this->audits();

		foreach ( $this->feature_ids() as $id ) {
			if ( ! isset( $audits[ $id ] ) ) {
				$status['unknown'][] = $id;
			}
		}

		if ( ! class_exists( static::FEATURES_UTIL ) ) {
			return $status;
		}

		$status['aware'] = true;

		try {
			$info = call_user_func( array( static::FEATURES_UTIL, 'get_compatible_features_for_plugin' ), DASHWOO_BASENAME );
		} catch ( \Throwable $e ) {
			$status['error'] = $e->getMessage();

			return $status;
		}

		foreach ( array( 'compatible', 'incompatible', 'uncertain' ) as $bucket ) {
			$status[ $bucket ] = array_values( array_map( 'strval', (array) ( $info[ $bucket ] ?? array() ) ) );
		}

		return $status;
	}

}
