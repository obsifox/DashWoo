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
	public static function block_orders( array $args = array() ) {
		$profile = new Profile();

		if ( ! $profile->logged_in() ) {
			return '';
		}

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return self::unavailable( 'برای دیدن سفارش‌ها ووکامرس باید فعال باشد.' );
		}

		$limit  = (int) ( $args['per_page'] ?? 0 );
		$limit  = $limit > 0 ? $limit : 10;
		$orders = wc_get_orders(
			array(
				'customer' => $profile->id(),
				'limit'    => $limit,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);

		$orders = is_array( $orders ) ? $orders : array();

		if ( ! $orders ) {
			return self::empty_state( 'هنوز سفارشی ثبت نشده است.', 'خرید از فروشگاه', function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : '' );
		}

		$html = self::heading( (string) ( $args['title'] ?? 'سفارش‌های من' ), $args, '<span class="dw-acc__section-meta">' . esc_html( sprintf( '%d سفارش', count( $orders ) ) ) . '</span>' );
		$html .= '<div class="dw-acc__orders">';

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) ) {
				continue;
			}

			$number = method_exists( $order, 'get_order_number' ) ? (string) $order->get_order_number() : '';
			$date   = method_exists( $order, 'get_date_created' ) ? $order->get_date_created() : null;
			$status = method_exists( $order, 'get_status' ) ? (string) $order->get_status() : '';
			$total  = method_exists( $order, 'get_formatted_order_total' ) ? (string) $order->get_formatted_order_total() : '';
			$url    = method_exists( $order, 'get_view_order_url' ) ? (string) $order->get_view_order_url() : '';

			$html .= '<a class="dw-acc__order" href="' . esc_url( $url ) . '">';
			$html .= '<span class="dw-acc__order-number">#' . esc_html( $number ) . '</span>';
			$html .= '<span class="dw-acc__order-date">' . esc_html( $date && is_object( $date ) && method_exists( $date, 'date_i18n' ) ? (string) $date->date_i18n( 'Y/m/d' ) : '' ) . '</span>';
			$html .= '<span class="dw-acc__order-status dw-acc__order-status--' . esc_attr( sanitize_html_class( $status ) ) . '">' . esc_html( self::status_label( $status ) ) . '</span>';
			$html .= '<span class="dw-acc__order-total">' . wp_kses_post( $total ) . '</span>';
			$html .= '<span class="dw-acc__order-go" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'arrow_back', 'size' => 20 ) ) . '</span>';
			$html .= '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Downloads list.
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
			return self::unavailable( 'برای این بخش، ووکامرس باید فعال باشد.' );
		}

		$downloads = (array) wc_get_customer_available_downloads( $profile->id() );

		if ( ! $downloads ) {
			return self::empty_state( 'فایلی برای دانلود وجود ندارد.', '', '' );
		}

		$html = self::heading( (string) ( $args['title'] ?? 'دانلودهای من' ), $args );
		$html .= '<ul class="dw-acc__downloads">';

		foreach ( $downloads as $download ) {
			if ( ! is_array( $download ) ) {
				continue;
			}

			$link = isset( $download['download_url'] ) ? (string) $download['download_url'] : '';

			$html .= '<li class="dw-acc__download">';
			$html .= '<span class="dw-acc__download-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'download', 'size' => 22 ) ) . '</span>';
			$html .= '<a class="dw-acc__download-link" href="' . esc_url( $link ) . '">' . esc_html( (string) ( $download['product_name'] ?? '' ) ) . '</a>';
			$html .= '</li>';
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
		$html     = self::heading( (string) ( $args['title'] ?? 'آدرس‌های من' ), $args );
		$sections = array(
			'billing'  => 'آدرس صورتحساب',
			'shipping' => 'آدرس ارسال',
		);

		$html .= '<div class="dw-acc__addresses">';

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

			$html .= '<div class="dw-acc__address">';
			$html .= '<h4 class="dw-acc__address-title">' . esc_html( $label ) . '</h4>';
			$html .= '<p class="dw-acc__address-lines">' . ( $values ? esc_html( implode( '، ', $values ) ) : '<span class="dw-acc__muted">هنوز ثبت نشده است.</span>' ) . '</p>';
			$html .= '<a class="dw-acc__button dw-acc__button--ghost" href="' . esc_url( $target ) . '">ویرایش ' . esc_html( $label ) . '</a>';
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
		$html = self::heading( (string) ( $args['title'] ?? 'روش‌های پرداخت' ), $args );
		$html .= self::empty_state(
			'روش‌های پرداخت ذخیره‌شده توسط ووکامرس مدیریت می‌شود. اگر درگاه شما پرداخت امن ذخیره‌شده را پشتیبانی کند، همان فهرست در این بخش نمایش داده می‌شود.',
			'باز کردن بخش پرداخت ووکامرس',
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

		$html = self::heading( (string) ( $args['title'] ?? 'جزئیات حساب' ), $args );

		if ( ! $profile->logged_in() ) {
			return $html;
		}

		$html .= '<div class="dw-acc__details">';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">نام نمایشی</span><span class="dw-acc__field-value">' . esc_html( $form->read( 'display_name' ) ) . '</span></div>';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">ایمیل</span><span class="dw-acc__field-value">' . esc_html( $profile->email() ) . '</span></div>';
		$html .= '<div class="dw-acc__field"><span class="dw-acc__field-label">نام</span><span class="dw-acc__field-value">' . esc_html( trim( $form->read( 'first_name' ) . ' ' . $form->read( 'last_name' ) ) ) . '</span></div>';
		$html .= '</div>';

		if ( ! empty( $args['url'] ) ) {
			$html .= '<a class="dw-acc__button" href="' . esc_url( (string) $args['url'] ) . '">ویرایش جزئیات در فرم ووکامرس</a>';
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
			$label = 'خروج از حساب';
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
					$value = sprintf( '%d سفارش', $profile->order_count() );
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

			$html  = self::heading( (string) ( $args['title'] ?? 'تغییر گذرواژه' ), $args );
			$html .= self::empty_state(
				'تغییر گذرواژه توسط ووکامرس (با تأیید گذرواژهٔ فعلی و ایمیل اطلاع‌رسانی) انجام می‌شود تا امنیت حساب حفظ شود.',
				'تغییر گذرواژه در فرم ووکامرس',
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

		$html = self::heading( (string) ( $args['title'] ?? 'جزئیات حساب من' ), $args );
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
		$html .= '<p class="dw-acc__form-actions"><button type="submit" class="dw-acc__button dw-acc__button--primary">ذخیره تغییرات</button></p>';
		$html .= '</form>';

		$html .= self::empty_state(
			'ایمیل، گذرواژه و آدرس‌ها با اعتبارسنجی خود ووکامرس ذخیره می‌شوند.',
			'ویرایش کامل در فرم ووکامرس',
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
			return self::empty_state( 'شما وارد حساب خود هستید.', 'رفتن به پیشخوان حساب', (string) $action['url'] );
		}

		$html  = '<div class="dw-acc__login-form">';
		$html .= '<form method="post" action="' . esc_url( (string) $action['url'] ) . '" class="dw-acc__form">';
		$html .= '<label class="dw-acc__input"><span class="dw-acc__input-label">نام کاربری یا ایمیل</span><input type="text" name="username" autocomplete="username" required /></label>';
		$html .= '<label class="dw-acc__input"><span class="dw-acc__input-label">گذرواژه</span><input type="password" name="password" autocomplete="current-password" required /></label>';
		$html .= '<label class="dw-acc__check"><input type="checkbox" name="rememberme" value="forever" /> مرا به خاطر بسپار</label>';
		$html .= '<p class="dw-acc__form-actions"><button type="submit" class="dw-acc__button dw-acc__button--primary">ورود</button></p>';
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
			'display_name'   => 'نام نمایشی',
			'first_name'     => 'نام',
			'last_name'      => 'نام خانوادگی',
			'email'          => 'ایمیل',
			'billing_email'  => 'ایمیل صورتحساب',
			'billing_phone'  => 'تلفن',
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
			'pending'    => 'در انتظار پرداخت',
			'processing' => 'در حال پردازش',
			'on-hold'    => 'در انتظار بررسی',
			'completed'  => 'تکمیل‌شده',
			'cancelled'  => 'لغوشده',
			'refunded'   => 'مسترد‌شده',
			'failed'     => 'ناموفق',
			'checkout-draft' => 'پیش‌نویس',
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
			sprintf( 'برای بخش «%s» محتوایی پیدا نشد (نه از ووکامرس و نه میان‌بر DashWoo).', (string) $endpoint ),
			'',
			''
		);
	}
}
