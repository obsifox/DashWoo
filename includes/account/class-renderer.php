<?php
/**
 * The account renderer: the piece every account widget shares.
 *
 * Responsibilities:
 *   - decide whether the account UI can be drawn at all (and say why not, in Persian,
 *     instead of showing a broken block),
 *   - wrap content in the platform's own markup + tokens classes,
 *   - draw the account navigation (vertical, horizontal or as cards),
 *   - print the inline CSS that the account settings ask for (accent colour, radius,
 *     grid columns, ...) without shipping a second stylesheet.
 *
 * Every method is static and Elementor-free, so the whole layer is unit testable.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

use DashWoo\Assets\Icons\Icon_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Account markup builder.
 */
class Renderer {

	/**
	 * Prefix every CSS class uses.
	 */
	const CSS = 'dw-acc';

	/**
	 * Can the account UI be rendered for this request?
	 *
	 * States:
	 *   ready            - WooCommerce is there and the visitor may use the account
	 *   no_woocommerce   - WooCommerce is not active
	 *   account_disabled - the shop disabled customer accounts on this site
	 *   no_account_page  - accounts exist but no My Account page is published
	 *   guest            - the account requires login and the visitor is a guest
	 *
	 * Only `ready` renders storefront markup; every other state renders an
	 * explanation (visible to editors/admins, silent for ordinary visitors).
	 *
	 * @return array<string,mixed>
	 */
	public static function availability() {
		if ( ! function_exists( 'wc_get_page_permalink' ) ) {
			return array(
				'state'    => 'no_woocommerce',
				'ready'    => false,
				'reason'   => 'ووکامرس فعال نیست، پس حساب کاربری ووکامرس هم وجود ندارد. تا زمانی که ووکامرس فعال شود، این بخش نمایش داده نمی‌شود.',
				'url'      => '',
			);
		}

		$account_url = (string) wc_get_page_permalink( 'myaccount' );

		if ( '' === $account_url ) {
			return array(
				'state'  => 'no_account_page',
				'ready'  => false,
				'reason' => 'صفحهٔ «حساب کاربری» ووکامرس ساخته یا منتشر نشده است؛ در تنظیمات ووکامرس → پیشرفته یک برگه به حساب کاربری اختصاص دهید.',
				'url'    => '',
			);
		}

		$registration = function_exists( 'get_option' ) ? (string) get_option( 'woocommerce_enable_myaccount_registration', 'no' ) : 'no';

		if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() && 'yes' !== $registration ) {
			// Registration is closed, so a guest can do nothing but log in: the widgets
			// draw the login form location instead of pretending the account exists.
			$profile = new Profile();

			unset( $profile );

			return array(
				'state'  => 'guest',
				'ready'  => false,
				'reason' => 'حساب کاربری نیاز به ورود دارد. این بخش فقط برای کاربران وارد‌شده نمایش داده می‌شود و بقیه به شکل ورود هدایت می‌شوند.',
				'url'    => $account_url,
			);
		}

		return array(
			'state'  => 'ready',
			'ready'  => true,
			'reason' => '',
			'url'    => $account_url,
		);
	}

	/**
	 * Is the visitor allowed to see account content right now?
	 *
	 * @return bool
	 */
	public static function ready() {
		$state = self::availability();

		return ! empty( $state['ready'] );
	}

	/**
	 * Explanation block (for visitors, editors and the Elementor canvas).
	 *
	 * @param array<string,mixed> $availability Result of availability().
	 * @param array<string,mixed> $args         Options: `editor` (bool), `compact` (bool).
	 * @return string
	 */
	public static function notice( array $availability, array $args = array() ) {
		$editor = ! empty( $args['editor'] );

		// On the live site an ordinary visitor should never see a "missing plugin"
		// message: it is not their problem and it looks broken.
		if ( ! $editor && ! self::may_edit() ) {
			return '';
		}

		$classes = array( self::CSS . '__notice', self::CSS . '__notice--' . $availability['state'] );

		if ( ! empty( $args['compact'] ) ) {
			$classes[] = self::CSS . '__notice--compact';
		}

		$html  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" role="status">';
		$html .= '<span class="' . self::CSS . '__notice-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'info', 'size' => 22 ) ) . '</span>';
		$html .= '<p class="' . self::CSS . '__notice-text">' . esc_html( (string) $availability['reason'] ) . '</p>';

		if ( 'guest' === $availability['state'] && ! empty( $availability['url'] ) ) {
			$html .= '<a class="' . self::CSS . '__notice-link" href="' . esc_url( (string) $availability['url'] ) . '">ورود به حساب</a>';
		}

		$html .= '<p class="' . self::CSS . '__notice-hint">این پیام فقط برای مدیران نمایش داده می‌شود.</p>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Can the current user see diagnostics?
	 *
	 * @return bool
	 */
	public static function may_edit() {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return function_exists( 'is_user_logged_in' ) && is_user_logged_in() && function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' );
	}

	/**
	 * Open the platform wrapper.
	 *
	 * @param array<string,mixed> $args Options: `layout`, `columns`, `class`, `title`.
	 * @return string
	 */
	public static function open( array $args = array() ) {
		$classes = array( self::CSS, self::CSS . '--' . self::option( $args, 'layout', 'boxed' ) );

		if ( ! empty( $args['class'] ) ) {
			$classes[] = (string) $args['class'];
		}

		$style = self::style_attribute( $args );

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $style . '>';

		if ( ! empty( $args['title'] ) ) {
			$html .= '<div class="' . self::CSS . '__head"><h2 class="' . self::CSS . '__title">' . esc_html( (string) $args['title'] ) . '</h2></div>';
		}

		return $html;
	}

	/**
	 * Close the wrapper.
	 *
	 * @return string
	 */
	public static function close() {
		return '</div>';
	}

	/**
	 * The account navigation.
	 *
	 * @param array<string,mixed> $args Options: `layout` (menu|tabs|cards), `icons`, `counts`.
	 * @return string
	 */
	public static function nav( array $args = array() ) {
		$layout = self::option( $args, 'layout', 'menu' );

		// The card grid is a different markup shape, but the shop owner picks it in
		// the same control: route it instead of duplicating the control set.
		if ( 'cards' === $layout ) {
			return self::cards( $args );
		}

		$icons   = (bool) self::option( $args, 'icons', true );
		$counts  = (bool) self::option( $args, 'counts', false );
		$items   = Endpoints::instance()->visible();
		$profile = new Profile();

		$classes = array( self::CSS . '__nav', self::CSS . '__nav--' . $layout );

		if ( ! empty( $args['sticky'] ) ) {
			$classes[] = self::CSS . '__nav--sticky';
		}

		$html    = '<nav class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-label="منوی حساب کاربری">';
		$html   .= '<ul class="' . self::CSS . '__nav-list">';

		foreach ( $items as $id => $item ) {
			$link_classes = array( self::CSS . '__nav-link' );

			if ( ! empty( $item['active'] ) ) {
				$link_classes[] = 'is-active';
			}

			$badge = '';

			if ( $counts ) {
				$count = self::count_for( (string) $id, $profile );

				if ( $count > 0 ) {
					$badge = '<span class="' . self::CSS . '__badge">' . esc_html( (string) $count ) . '</span>';
				}
			}

			$html .= '<li class="' . self::CSS . '__nav-item' . ( ! empty( $item['active'] ) ? ' is-active' : '' ) . '">';
			$html .= '<a class="' . esc_attr( implode( ' ', $link_classes ) ) . '" href="' . esc_url( (string) $item['url'] ) . '"'
				. ( ! empty( $item['active'] ) ? ' aria-current="page"' : '' ) . '>';

			if ( $icons && '' !== (string) $item['icon'] ) {
				$html .= '<span class="' . self::CSS . '__nav-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => (string) $item['icon'], 'size' => 22 ) ) . '</span>';
			}

			$html .= '<span class="' . self::CSS . '__nav-label">' . esc_html( (string) $item['label'] ) . '</span>' . $badge;
			$html .= '</a></li>';
		}

		$html .= '</ul></nav>';

		/**
		 * Filter the rendered account navigation.
		 *
		 * @param string              $html Navigation markup.
		 * @param array<string,mixed> $args Args the block used.
		 */
		return (string) apply_filters( 'dashwoo_account_nav_html', $html, $args );
	}

	/**
	 * Card grid of the account areas - a visual replacement for the default list.
	 *
	 * @param array<string,mixed> $args Options: `columns`, `icons`, `exclude`.
	 * @return string
	 */
	public static function cards( array $args = array() ) {
		$columns = max( 1, min( 4, (int) self::option( $args, 'columns', 3 ) ) );
		$icons   = (bool) self::option( $args, 'icons', true );
		$exclude = array_map( 'strval', (array) self::option( $args, 'exclude', array( 'customer-logout' ) ) );
		$items   = Endpoints::instance()->visible();
		$profile = new Profile();

		$html  = '<div class="' . self::CSS . '__cards" style="--dw-acc-cols:' . $columns . '">';

		foreach ( $items as $id => $item ) {
			$id = (string) $id;

			if ( in_array( $id, $exclude, true ) ) {
				continue;
			}

			$count = self::count_for( $id, $profile );

			$html .= '<a class="' . self::CSS . '__card' . ( ! empty( $item['active'] ) ? ' is-active' : '' ) . '" href="' . esc_url( (string) $item['url'] ) . '">';

			if ( $icons ) {
				$html .= '<span class="' . self::CSS . '__card-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => (string) $item['icon'], 'size' => 30 ) ) . '</span>';
			}

			$html .= '<span class="' . self::CSS . '__card-title">' . esc_html( (string) $item['label'] ) . '</span>';

			if ( $count > 0 ) {
				$html .= '<span class="' . self::CSS . '__card-meta">' . esc_html( sprintf( '%d مورد', $count ) ) . '</span>';
			}

			$html .= '<span class="' . self::CSS . '__card-arrow" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'arrow_back', 'size' => 20 ) ) . '</span>';
			$html .= '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * The dashboard block: greeting + summary + cards.
	 *
	 * @param array<string,mixed> $args Options: `greeting`, `avatar`, `cards`, `columns`.
	 * @return string
	 */
	public static function dashboard( array $args = array() ) {
		$profile  = new Profile();
		$greeting = (bool) self::option( $args, 'greeting', true );
		$avatar   = (bool) self::option( $args, 'avatar', true );
		$cards    = (bool) self::option( $args, 'cards', true );
		$text     = (string) self::option( $args, 'greeting_text', 'خوش آمدید' );

		$html = '';

		if ( $greeting || $avatar ) {
			$html .= '<div class="' . self::CSS . '__hero">';

			if ( $avatar ) {
				$html .= '<div class="' . self::CSS . '__avatar">';

				$picture = $profile->avatar( 96 );

				if ( '' !== $picture ) {
					$html .= $picture; // WordPress markup, already escaped.
				} else {
					$html .= '<span class="' . self::CSS . '__initials">' . esc_html( $profile->initials() ) . '</span>';
				}

				$html .= '</div>';
			}

			if ( $greeting ) {
				$name = $profile->first_name();

				$html .= '<div class="' . self::CSS . '__hero-text">';
				$html .= '<p class="' . self::CSS . '__hero-greeting">' . esc_html( $text ) . ( '' !== $name ? '، ' . esc_html( $name ) : '' ) . '</p>';
				$html .= '<p class="' . self::CSS . '__hero-summary">' . esc_html( $profile->summary() ) . '</p>';
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		if ( $cards ) {
			$html .= self::cards( $args );
		}

		return $html;
	}

	/**
	 * Login prompt for guests (and the URL Elementor should link to).
	 *
	 * @param array<string,mixed> $args Options: `text`, `button`.
	 * @return string
	 */
	public static function login_prompt( array $args = array() ) {
		$url  = Endpoints::instance()->login_url();
		$text = (string) self::option( $args, 'text', 'برای دیدن سفارش‌ها، دانلودها و جزئیات حساب وارد شوید.' );
		$cta  = (string) self::option( $args, 'button', 'ورود به حساب' );

		$html  = '<div class="' . self::CSS . '__login">';
		$html .= '<span class="' . self::CSS . '__login-icon" aria-hidden="true">' . Icon_Renderer::render( array( 'icon' => 'lock', 'size' => 28 ) ) . '</span>';
		$html .= '<p class="' . self::CSS . '__login-text">' . esc_html( $text ) . '</p>';
		$html .= '<a class="' . self::CSS . '__login-button" href="' . esc_url( $url ) . '">' . esc_html( $cta ) . '</a>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * One account endpoint: WooCommerce's own content when it is available, else the
	 * DashWoo block.
	 *
	 * The shop's callbacks always win (`$args['shortcut']`), because they know about
	 * the plugins the shop installed - DashWoo only replaces the *frame*.
	 *
	 * @param array<string,mixed> $args Args: endpoint, hook, shortcut, title, icons, per_page.
	 * @return string
	 */
	public static function endpoint( array $args = array() ) {
		$endpoint = isset( $args['endpoint'] ) ? (string) $args['endpoint'] : '';
		$hook     = isset( $args['hook'] ) && '' !== (string) $args['hook'] ? (string) $args['hook'] : 'woocommerce_account_' . $endpoint . '_endpoint';
		$native   = '';

		// A widget without a title still needs a heading: fall back to the endpoint's
		// own label, which the shop owner set (or DashWoo's Persian default).
		if ( '' === trim( (string) ( $args['title'] ?? '' ) ) ) {
			$args['title'] = Endpoints::instance()->label( $endpoint );
		}

		if ( ! empty( $args['shortcut'] ) && function_exists( 'has_action' ) && has_action( $hook ) ) {
			ob_start();
			do_action( $hook );
			$native = (string) ob_get_clean();
		}

		if ( '' !== trim( $native ) ) {
			$title = isset( $args['title'] ) ? (string) $args['title'] : '';

			return Sections::heading( $title, $args ) . '<div class="' . self::CSS . '__native">' . $native . '</div>';
		}

		$block = Sections::block( $endpoint, $args );

		if ( '' === $block && ! empty( $args['title'] ) ) {
			return Sections::heading( (string) $args['title'], $args );
		}

		return $block;
	}

	/**
	 * Profile card.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function profile( array $args = array() ) {
		return Sections::profile( $args );
	}

	/**
	 * Form blocks (profile / address / password / login).
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function forms( array $args = array() ) {
		return Sections::forms( $args );
	}

	/**
	 * Logout block.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	public static function logout( array $args = array() ) {
		return Sections::block_customer_logout( $args );
	}

	/**
	 * Order notices WooCommerce queued (empty when there are none).
	 *
	 * @return string
	 */
	public static function notices() {
		if ( ! function_exists( 'wc_print_notices' ) ) {
			return '';
		}

		ob_start();
		wc_print_notices();

		return (string) ob_get_clean();
	}

	/**
	 * Inline CSS built from the account settings (no second stylesheet).
	 *
	 * @param array<string,mixed> $args Optional overrides.
	 * @return string
	 */
	public static function styles( array $args = array() ) {
		$accent = (string) self::option( $args, 'accent', (string) dashwoo_get_setting( 'account_design.accent', '#2563eb' ) );
		$radius = (int) self::option( $args, 'radius', (int) dashwoo_get_setting( 'account_design.radius', 18 ) );
		$width  = (int) self::option( $args, 'nav_width', (int) dashwoo_get_setting( 'account_design.nav_width', 264 ) );
		$cols   = (int) self::option( $args, 'columns', (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ) );
		$avatar = (int) self::option( $args, 'avatar_size', (int) dashwoo_get_setting( 'account_design.avatar_size', 96 ) );

		$scope = '.' . self::CSS;

		$css  = $scope . '{--dw-acc-accent:' . self::color( $accent ) . ';';
		$css .= '--dw-acc-radius:' . max( 0, min( 60, $radius ) ) . 'px;';
		$css .= '--dw-acc-nav-width:' . max( 160, min( 480, $width ) ) . 'px;';
		$css .= '--dw-acc-cols:' . max( 1, min( 4, $cols ) ) . ';';
		$css .= '--dw-acc-avatar:' . max( 32, min( 200, $avatar ) ) . 'px;}';

		/**
		 * Filter the account inline CSS.
		 *
		 * @param string              $css  Generated CSS.
		 * @param array<string,mixed> $args Overrides in play.
		 */
		return (string) apply_filters( 'dashwoo_account_css', $css, $args );
	}

	/**
	 * Sanitise a colour for inline CSS (never trust a setting blindly).
	 *
	 * @param string $color Raw colour.
	 * @return string
	 */
	protected static function color( $color ) {
		$color = trim( (string) $color );

		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color ) ) {
			return $color;
		}

		if ( preg_match( '/^(rgb|hsl)a?\([0-9.,%\s\/]+\)$/i', $color ) ) {
			return $color;
		}

		if ( preg_match( '/^var\(--[a-z0-9\-_]+\)$/i', $color ) ) {
			return $color;
		}

		return '#2563eb';
	}

	/**
	 * A `style="…"` attribute for one block.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	protected static function style_attribute( array $args ) {
		$css = self::styles( $args );

		if ( '' === $css ) {
			return '';
		}

		return ' style="' . esc_attr( $css ) . '"';
	}

	/**
	 * Read an option with a default.
	 *
	 * @param array<string,mixed> $args    Args.
	 * @param string              $key     Key.
	 * @param mixed               $default Default.
	 * @return mixed
	 */
	protected static function option( array $args, $key, $default ) {
		return array_key_exists( $key, $args ) ? $args[ $key ] : $default;
	}

	/**
	 * Number of "things" behind an endpoint, for the badge/counter.
	 *
	 * Only the counts DashWoo can read through public APIs are offered; anything
	 * else is 0 (the widget then shows no badge instead of a wrong number).
	 *
	 * @param string  $id      Endpoint id.
	 * @param Profile $profile Profile helper.
	 * @return int
	 */
	public static function count_for( $id, Profile $profile ) {
		if ( ! $profile->logged_in() ) {
			return 0;
		}

		switch ( $id ) {
			case 'orders':
				return $profile->order_count();

			case 'downloads':
				return function_exists( 'wc_get_customer_available_downloads' ) ? count( (array) wc_get_customer_available_downloads( $profile->id() ) ) : 0;
		}

		/**
		 * Filter the counter shown next to an endpoint.
		 *
		 * @param int    $count   Count.
		 * @param string $id      Endpoint id.
		 * @param Profile $profile Profile helper.
		 */
		return (int) apply_filters( 'dashwoo_account_count', 0, $id, $profile );
	}
}
