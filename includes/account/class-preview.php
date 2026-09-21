<?php
/**
 * Preview data for the Elementor editor.
 *
 * A shop owner builds the account area while logged in as an administrator - an
 * administrator usually has no orders, no downloads and no billing address, so every
 * widget would render an empty state and there would be nothing to style. In the
 * builder (and only there) DashWoo fills the gaps with sample data, so the layout is
 * always visible while it is being designed.
 *
 * A real customer's own data is never replaced: the sample rows only fill in what is
 * actually empty.
 *
 * @package DashWoo\Account
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Sample account data for the builder.
 */
class Preview {

	/**
	 * Explicit on/off switch (null = decide from the request).
	 *
	 * @var bool|null
	 */
	private static $forced = null;

	/**
	 * Force preview mode on or off (tests, or a shop that wants the demo in the
	 * editor even for a customer that has data).
	 *
	 * @param bool|null $on Toggle.
	 * @return void
	 */
	public static function force( $on = null ) {
		self::$forced = null === $on ? null : (bool) $on;
	}

	/**
	 * Are we showing sample data right now?
	 *
	 * @return bool
	 */
	public static function active() {
		if ( null !== self::$forced ) {
			return (bool) apply_filters( 'dashwoo_account_preview', self::$forced );
		}

		$active = Elementor_Bridge::is_editor();

		/**
		 * Filter whether the builder gets sample account data.
		 *
		 * @param bool $active Sample data on?
		 */
		return (bool) apply_filters( 'dashwoo_account_preview', $active );
	}

	/**
	 * Is the person looking at the preview a shop administrator (and not a customer
	 * browsing their own account)?
	 *
	 * @return bool
	 */
	public static function is_administrator() {
		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}

	/**
	 * Sample orders (objects with the same surface the real ones have).
	 *
	 * @return array<int,Preview_Order>
	 */
	public static function orders() {
		return array(
			new Preview_Order( '1048', 'completed', '۱٬۲۵۰٬۰۰۰ تومان', '۱۴۰۵/۰۶/۱۲' ),
			new Preview_Order( '1041', 'processing', '۸۵۰٬۰۰۰ تومان', '۱۴۰۵/۰۵/۲۸' ),
			new Preview_Order( '1027', 'on-hold', '۲٬۴۹۰٬۰۰۰ تومان', '۱۴۰۵/۰۴/۰۹' ),
		);
	}

	/**
	 * Sample downloads.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function downloads() {
		return array(
			array(
				'product_name'  => 'قالب فروشگاهی نمونه',
				'download_url'  => '#',
				'download_name' => 'sample-template.zip',
			),
			array(
				'product_name'  => 'بستهٔ آیکون‌های فروشگاه',
				'download_url'  => '#',
				'download_name' => 'shop-icons.zip',
			),
		);
	}

	/**
	 * Sample addresses (used by the addresses block when the customer has none).
	 *
	 * @return array<string,string>
	 */
	public static function addresses() {
		return array(
			'billing'  => 'تهران، خیابان نمونه، پلاک ۱۲، واحد ۳',
			'shipping' => 'تهران، خیابان نمونه، پلاک ۱۲، واحد ۳',
		);
	}

	/**
	 * A sample customer name for the hero when nobody is logged in (builder only).
	 *
	 * @return array<string,string>
	 */
	public static function customer() {
		return array(
			'name'       => 'مشتری نمونه',
			'first_name' => 'مشتری',
			'last_name'  => 'نمونه',
			'email'      => 'customer@example.com',
		);
	}
}

/**
 * A sample order with the surface the real WooCommerce order exposes to the renderer.
 */
class Preview_Order {

	/**
	 * Order number.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Status slug.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Formatted total.
	 *
	 * @var string
	 */
	private $total;

	/**
	 * Formatted date.
	 *
	 * @var string
	 */
	private $date;

	/**
	 * Constructor.
	 *
	 * @param string $number Order number.
	 * @param string $status Status slug.
	 * @param string $total  Formatted total.
	 * @param string $date   Formatted date.
	 */
	public function __construct( $number, $status, $total, $date ) {
		$this->number = (string) $number;
		$this->status = (string) $status;
		$this->total  = (string) $total;
		$this->date   = (string) $date;
	}

	/**
	 * Order number.
	 *
	 * @return string
	 */
	public function get_order_number() {
		return $this->number;
	}

	/**
	 * Status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Formatted total.
	 *
	 * @return string
	 */
	public function get_formatted_order_total() {
		return $this->total;
	}

	/**
	 * Creation date.
	 *
	 * @return object
	 */
	public function get_date_created() {
		return new Preview_Date( $this->date );
	}

	/**
	 * View-order URL (the builder preview has none, so it points at the account page).
	 *
	 * @return string
	 */
	public function get_view_order_url() {
		return function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'myaccount' ) : '#';
	}
}

/**
 * Sample date.
 */
class Preview_Date {

	/**
	 * Stored date.
	 *
	 * @var string
	 */
	private $date;

	/**
	 * Constructor.
	 *
	 * @param string $date Date.
	 */
	public function __construct( $date ) {
		$this->date = (string) $date;
	}

	/**
	 * Formatted date.
	 *
	 * @param string $format Unused.
	 * @return string
	 */
	public function date_i18n( $format = 'Y/m/d' ) {
		unset( $format );

		return $this->date;
	}
}
