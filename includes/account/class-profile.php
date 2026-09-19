<?php
/**
 * What the account area knows about the current visitor, without dying.
 *
 * A widget (or a page built before WooCommerce was installed, or an account area
 * closed to guests) must render *something* useful instead of a fatal error, so
 * every accessor here answers with a safe fallback and every read of WooCommerce
 * data goes through WooCommerce's own CRUD API - the same rule the compatibility
 * layer audits.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Visitor-facing account facts.
 */
class Profile {

	/**
	 * Cached user object for the request.
	 *
	 * @var mixed
	 */
	private $user = null;

	/**
	 * Is somebody logged in?
	 *
	 * @return bool
	 */
	public function logged_in() {
		if ( ! function_exists( 'is_user_logged_in' ) ) {
			return false;
		}

		return (bool) is_user_logged_in() && ! empty( $this->user() );
	}

	/**
	 * Current user object (false when there is none).
	 *
	 * @return mixed
	 */
	public function user() {
		if ( null !== $this->user ) {
			return $this->user;
		}

		$this->user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : false;

		if ( ! is_object( $this->user ) || empty( $this->user->ID ) ) {
			$this->user = false;
		}

		return $this->user;
	}

	/**
	 * User id (0 for guests).
	 *
	 * @return int
	 */
	public function id() {
		$user = $this->user();

		return $user ? (int) $user->ID : 0;
	}

	/**
	 * Display name, falling back to the login name.
	 *
	 * @return string
	 */
	public function name() {
		$user = $this->user();

		if ( ! $user ) {
			return '';
		}

		foreach ( array( 'display_name', 'user_login' ) as $key ) {
			if ( ! empty( $user->{$key} ) ) {
				return (string) $user->{$key};
			}
		}

		return '';
	}

	/**
	 * First name when the account has one (used for the greeting).
	 *
	 * @return string
	 */
	public function first_name() {
		$user = $this->user();

		if ( ! $user ) {
			return '';
		}

		return ! empty( $user->first_name ) ? (string) $user->first_name : $this->name();
	}

	/**
	 * Email address.
	 *
	 * @return string
	 */
	public function email() {
		$user = $this->user();

		return $user && ! empty( $user->user_email ) ? (string) $user->user_email : '';
	}

	/**
	 * Avatar markup (empty string when WordPress cannot render one).
	 *
	 * @param int $size Pixel size.
	 * @return string
	 */
	public function avatar( $size = 96 ) {
		if ( ! $this->logged_in() || ! function_exists( 'get_avatar' ) ) {
			return '';
		}

		$size = max( 16, min( 512, (int) $size ) );

		return (string) get_avatar( $this->id(), $size, '', $this->name() );
	}

	/**
	 * Initials for the avatar fallback (works without Gravatar).
	 *
	 * @return string
	 */
	public function initials() {
		$name  = trim( $this->name() );
		$parts = preg_split( '/\s+/u', $name ) ?: array();
		$first = '';

		foreach ( $parts as $part ) {
			if ( '' !== $part ) {
				$first = $this->first_letter( $part );
				break;
			}
		}

		$last = '';

		if ( count( $parts ) > 1 ) {
			$last = $this->first_letter( (string) end( $parts ) );
		}

		$initials = $first . $last;

		return '' !== $initials ? $initials : '?';
	}

	/**
	 * The customer's order count (CRUD only, `0` when unavailable).
	 *
	 * @return int
	 */
	public function order_count() {
		$user = $this->user();

		if ( ! $user || ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer' => $this->id(),
				'limit'    => -1,
				'return'   => 'ids',
			)
		);

		return is_array( $orders ) ? count( $orders ) : 0;
	}

	/**
	 * A short "who is this" line for the dashboard card.
	 *
	 * @return string
	 */
	public function summary() {
		if ( ! $this->logged_in() ) {
			return 'مهمان — برای دیدن سفارش‌ها و دانلودها وارد شوید.';
		}

		$orders = $this->order_count();

		return sprintf(
			'%s · %d سفارش',
			$this->email() ? $this->email() : $this->name(),
			$orders
		);
	}

	/**
	 * First letter of a string, multibyte safe.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function first_letter( $value ) {
		if ( function_exists( 'mb_substr' ) ) {
			return (string) mb_substr( $value, 0, 1 );
		}

		return (string) substr( $value, 0, 1 );
	}
}
