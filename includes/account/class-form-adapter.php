<?php
/**
 * Form adapter: how a DashWoo form writes into WooCommerce.
 *
 * WooCommerce owns the customer data. DashWoo draws the fields, deletes nothing,
 * and - for exactly one thing - writes through the public API: the display name.
 * A widget's `form` link is a normal WooCommerce form *action* pointing at the
 * shop's own account page (`#edit-account`, `#edit-address`), so WooCommerce's own
 * handler, nonce and validation stay in charge.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Read + minimal write of the fields the forms show.
 */
class Form_Adapter {

	/**
	 * Meta keys DashWoo is willing to read for pre-filling a form.
	 *
	 * @var array<int,string>
	 */
	const READABLE = array(
		'first_name',
		'last_name',
		'display_name',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_country',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_city',
		'shipping_state',
		'shipping_postcode',
	);

	/**
	 * Current value of a customer field ('' when unknown).
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public function read( $key ) {
		$key = (string) $key;

		if ( ! in_array( $key, self::READABLE, true ) ) {
			return '';
		}

		$profile = new Profile();
		$user    = $profile->user();

		if ( ! $user ) {
			return '';
		}

		if ( 'display_name' === $key ) {
			return $profile->name();
		}

		if ( in_array( $key, array( 'first_name', 'last_name' ), true ) ) {
			return ! empty( $user->{$key} ) ? (string) $user->{$key} : '';
		}

		if ( function_exists( 'get_user_meta' ) ) {
			$value = get_user_meta( $profile->id(), $key, true );

			return is_scalar( $value ) ? (string) $value : '';
		}

		return '';
	}

	/**
	 * Every readable field, pre-filled.
	 *
	 * @return array<string,string>
	 */
	public function values() {
		$out = array();

		foreach ( self::READABLE as $key ) {
			$out[ $key ] = $this->read( $key );
		}

		return $out;
	}

	/**
	 * Update the display name through WordPress (the documented path).
	 *
	 * @param string $new  New display name.
	 * @param string $old  Current display name.
	 * @return array<string,mixed> Result: ok / message / changed.
	 */
	public function update_display_name( $new, $old = '' ) {
		$new = trim( (string) $new );

		if ( '' === $new ) {
			return array(
				'ok'      => false,
				'message' => __( 'The display name cannot be empty.', 'dashwoo' ),
				'changed' => false,
			);
		}

		if ( '' === (string) $old ) {
			$old = $this->read( 'display_name' );
		}

		if ( $new === (string) $old ) {
			return array(
				'ok'      => true,
				'message' => __( 'The display name did not change.', 'dashwoo' ),
				'changed' => false,
			);
		}

		if ( ! function_exists( 'wp_update_user' ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'Saving is not possible in this environment.', 'dashwoo' ),
				'changed' => false,
			);
		}

		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return array(
				'ok'      => false,
				'message' => __( 'You must be signed in to change the name.', 'dashwoo' ),
				'changed' => false,
			);
		}

		$result = wp_update_user(
			array(
				'ID'           => $profile->id(),
				'display_name' => $new,
			)
		);

		$failed = function_exists( 'is_wp_error' ) && is_wp_error( $result );

		if ( $failed ) {
			return array(
				'ok'      => false,
				'message' => __( 'It was not saved; please try again.', 'dashwoo' ),
				'changed' => false,
			);
		}

		/**
		 * Fires after a DashWoo form wrote a customer field.
		 *
		 * @param string $field Field key.
		 * @param mixed  $value New value.
		 * @param mixed  $old   Previous value.
		 */
		do_action( 'dashwoo_account_field_saved', 'display_name', $new, $old );

		return array(
			'ok'      => true,
			'message' => __( 'Display name saved.', 'dashwoo' ),
			'changed' => true,
		);
	}

	/**
	 * The WordPress action a DashWoo form posts to, plus the fields WooCommerce
	 * expects for its own handlers (so a "hybrid" form still works).
	 *
	 * @param string $target Form target: profile | address | password | login.
	 * @return array<string,mixed>
	 */
	public function action_for( $target ) {
		$endpoints = Endpoints::instance();
		$base      = $endpoints->account_url();

		$map = array(
			'profile'  => array(
				'url'    => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-account', '', $base ) : $base,
				'method' => 'post',
				'fields' => array( 'account_first_name', 'account_last_name', 'account_display_name', 'account_email', 'save-account-details-nonce' ),
			),
			'address'  => array(
				'url'    => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-address', '', $base ) : $base,
				'method' => 'post',
				'fields' => array( 'billing_first_name', 'billing_last_name', 'billing_country', 'save-address-nonce' ),
			),
			'password' => array(
				'url'    => function_exists( 'wc_get_endpoint_url' ) ? wc_get_endpoint_url( 'edit-account', '', $base ) : $base,
				'method' => 'post',
				'fields' => array( 'password_current', 'password_1', 'password_2', 'save-account-details-nonce' ),
			),
			'login'    => array(
				'url'    => $base,
				'method' => 'post',
				'fields' => array( 'username', 'password', 'rememberme' ),
			),
		);

		/**
		 * Filter the form target for a DashWoo account form.
		 *
		 * @param array<string,mixed> $target Form target definition.
		 * @param string              $name   Requested target name.
		 */
		return (array) apply_filters( 'dashwoo_account_form_target', $map[ $target ] ?? $map['profile'], $target );
	}
}
