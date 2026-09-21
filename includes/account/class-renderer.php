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
		// Inside the Elementor editor the account area must always render: a shop owner
		// who is not a customer (or a shop without a published account page yet) still
		// has to see and style the widgets. Sample data fills the gaps - see Preview.
		if ( Elementor_Bridge::is_editor() ) {
			return array(
				'state'   => 'builder',
				'ready'   => true,
				'reason'  => '',
				'url'     => function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'myaccount' ) : '',
				'preview' => true,
			);
		}

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

		if ( ! dashwoo_is_on( 'account_design.auto_css' ) ) {
			// The shop owner owns the styling: DashWoo only marks the wrapper, the
			// reset (if enabled) does the neutralising and nothing else is applied.
			$classes[] = self::CSS . '--bare';
		}

		$style = self::style_attribute( $args );
		$attrs = '';

		foreach ( (array) self::option( $args, 'data', array() ) as $key => $value ) {
			if ( '' === (string) $key ) {
				continue;
			}

			$attrs .= ' data-' . esc_attr( sanitize_key( (string) $key ) ) . '="'
				. esc_attr( is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value ) ) . '"';
		}

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $style . $attrs . '>';

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
		$layout = sanitize_key( (string) self::option( $args, 'layout', 'menu' ) );

		// The card grid is a different markup shape, but the shop owner picks it in
		// the same control: route it instead of duplicating the control set.
		if ( 'cards' === $layout ) {
			return self::cards( $args );
		}

		$plain  = 'plain' === $layout;
		$layout = $plain ? 'plain' : ( in_array( $layout, array( 'menu', 'tabs', 'rail' ), true ) ? $layout : 'menu' );
		$icons  = $plain ? false : (bool) self::option( $args, 'icons', true );
		$counts = $plain ? false : (bool) self::option( $args, 'counts', false );
		$items  = self::nav_items( $args );

		if ( ! $items ) {
			return '';
		}

		$profile       = new Profile();
		$active        = (string) self::option( $args, 'active', '' );
		$icon_position = 'after' === (string) self::option( $args, 'icon_position', 'before' ) ? 'after' : 'before';
		$size          = sanitize_key( (string) self::option( $args, 'size', 'md' ) );
		$align         = sanitize_key( (string) self::option( $args, 'align', 'start' ) );

		$classes = array(
			self::CSS . '__nav',
			self::CSS . '__nav--' . $layout,
			self::CSS . '__nav--size-' . $size,
			self::CSS . '__nav--align-' . $align,
			self::CSS . '__nav--icon-' . $icon_position,
		);

		if ( ! empty( $args['sticky'] ) ) {
			$classes[] = self::CSS . '__nav--sticky';
		}

		if ( ! empty( $args['divider'] ) ) {
			$classes[] = self::CSS . '__nav--divider';
		}

		if ( ! empty( $args['panel'] ) ) {
			$classes[] = self::CSS . '__nav--panel';
		}

		$html  = '<nav class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-label="منوی حساب کاربری" data-dw-nav-list="1">';
		$html .= '<ul class="' . self::CSS . '__nav-list">';

		foreach ( $items as $id => $item ) {
			$is_active    = '' !== $active ? ( (string) $id === $active ) : ! empty( $item['active'] );
			$link_classes = array( self::CSS . '__nav-link' );

			if ( $is_active ) {
				$link_classes[] = 'is-active';
			}

			$badge = '';

			if ( $counts ) {
				$count = isset( $item['count'] ) ? (int) $item['count'] : self::count_for( (string) $id, $profile );

				if ( $count > 0 ) {
					$badge = '<span class="' . self::CSS . '__badge">' . esc_html( (string) $count ) . '</span>';
				}
			}

			$icon = '';

			if ( $icons && '' !== (string) $item['icon'] ) {
				$icon = '<span class="' . self::CSS . '__nav-icon" aria-hidden="true">'
					. Icon_Renderer::render( array( 'icon' => (string) $item['icon'], 'size' => 'rail' === $layout ? 24 : 22 ) ) . '</span>';
			}

			$label = '<span class="' . self::CSS . '__nav-label">' . esc_html( (string) $item['label'] ) . '</span>';
			$inner = 'after' === $icon_position ? $label . $icon : $icon . $label;

			$link_attrs = '';

			if ( isset( $item['view'] ) && '' !== (string) $item['view'] ) {
				$link_attrs .= ' data-dw-view="' . esc_attr( (string) $item['view'] ) . '"';
			}

			if ( ! empty( $item['external'] ) ) {
				$link_attrs .= ' target="_blank" rel="noopener"';
			}

			$html .= '<li class="' . self::CSS . '__nav-item' . ( $is_active ? ' is-active' : '' ) . '">';
			$html .= '<a class="' . esc_attr( implode( ' ', $link_classes ) ) . '" href="' . esc_url( (string) $item['url'] ) . '"'
				. ( $is_active ? ' aria-current="page"' : '' ) . $link_attrs . '>';
			$html .= $inner . $badge;
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
	 * The menu items after the shop owner's choices are applied.
	 *
	 * Two shapes are accepted:
	 *   - `items` as rows (an Elementor repeater): the list *is* the menu, with the
	 *     order, the label, the icon and the visibility of every single row, and even
	 *     custom links that are not WooCommerce endpoints;
	 *   - no rows: the WooCommerce endpoint list, filtered by `labels`, `icon_map`,
	 *     `hidden`, `counts_map` and `order_map`.
	 *
	 * @param array<string,mixed> $args Args.
	 * @return array<string,array<string,mixed>>
	 */
	public static function nav_items( array $args = array() ) {
		$base      = Endpoints::instance()->visible();
		$rows      = self::option( $args, 'items', array() );
		$labels    = (array) self::option( $args, 'labels', array() );
		$icon_map  = (array) self::option( $args, 'icon_map', array() );
		$count_map = (array) self::option( $args, 'counts_map', array() );
		$order_map = (array) self::option( $args, 'order_map', array() );
		$hidden    = array_map( 'strval', (array) self::option( $args, 'hidden', array() ) );
		$items     = array();

		if ( is_array( $rows ) && $rows ) {
			foreach ( $rows as $row ) {
				$row = (array) $row;
				$id  = isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '';
				$url = isset( $row['url'] ) ? (string) $row['url'] : '';

				if ( '' !== $id && ! isset( $base[ $id ] ) ) {
					continue;
				}

				if ( '' === $id && '' === trim( $url ) ) {
					continue;
				}

				$show = ! isset( $row['visible'] ) || 'no' !== (string) $row['visible'];

				if ( ! $show || ( '' !== $id && in_array( $id, $hidden, true ) ) ) {
					continue;
				}

				$item = isset( $base[ $id ] ) ? $base[ $id ] : array(
					'id'       => $id,
					'label'    => $id,
					'icon'     => 'link',
					'url'      => $url,
					'active'   => false,
					'count'    => 0,
					'external' => true,
					'view'     => '',
				);

				if ( isset( $row['label'] ) && '' !== trim( (string) $row['label'] ) ) {
					$item['label'] = (string) $row['label'];
				}

				if ( isset( $row['icon'] ) && '' !== trim( (string) $row['icon'] ) ) {
					$item['icon'] = (string) $row['icon'];
				}

				if ( '' !== trim( $url ) ) {
					$item['url']      = $url;
					$item['external'] = ! isset( $base[ $id ] );
				}

				$item['view'] = isset( $base[ $id ] ) ? (string) $id : '';

				if ( isset( $row['badge'] ) && is_numeric( $row['badge'] ) ) {
					$item['count'] = (int) $row['badge'];
				}

				$key = '' !== $id ? $id : 'custom-' . substr( md5( $url . '|' . (string) $item['label'] ), 0, 6 );

				$items[ $key ] = $item;
			}

			/**
			 * Filter the menu items (repeater shape).
			 *
			 * @param array<string,array<string,mixed>> $items Items.
			 * @param array<string,mixed>               $args  Args.
			 */
			return (array) apply_filters( 'dashwoo_account_nav_items', $items, $args );
		}

		foreach ( $base as $id => $item ) {
			$id = (string) $id;

			if ( in_array( $id, $hidden, true ) ) {
				continue;
			}

			if ( isset( $labels[ $id ] ) && '' !== trim( (string) $labels[ $id ] ) ) {
				$item['label'] = (string) $labels[ $id ];
			}

			if ( isset( $icon_map[ $id ] ) && '' !== trim( (string) $icon_map[ $id ] ) ) {
				$item['icon'] = (string) $icon_map[ $id ];
			}

			if ( isset( $count_map[ $id ] ) ) {
				$item['count'] = (int) $count_map[ $id ];
			}

			if ( isset( $order_map[ $id ] ) ) {
				$item['order'] = (int) $order_map[ $id ];
			}

			$item['view'] = $id;

			$items[ $id ] = $item;
		}

		if ( $order_map ) {
			uasort(
				$items,
				static function ( $a, $b ) {
					return (int) $a['order'] <=> (int) $b['order'];
				}
			);
		}

		/** This filter is documented above. */
		return (array) apply_filters( 'dashwoo_account_nav_items', $items, $args );
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
		$counts  = (bool) self::option( $args, 'counts', true );
		$items   = self::nav_items( $args );
		$profile = new Profile();
		$active  = (string) self::option( $args, 'active', '' );

		if ( ! $items ) {
			return '';
		}

		// A card grid is a menu with a different shape: `cards` as a nav layout uses
		// the same item list, but the exit link stays out of the grid.
		$html = '<div class="' . self::CSS . '__cards" style="--dw-acc-cols:' . $columns . '" data-dw-nav-list="1">';

		foreach ( $items as $id => $item ) {
			$id = (string) $id;

			if ( in_array( $id, $exclude, true ) ) {
				continue;
			}

			$is_active = '' !== $active ? ( $id === $active ) : ! empty( $item['active'] );
			$count     = $counts ? self::count_for( $id, $profile ) : 0;

			if ( $counts && isset( $item['count'] ) ) {
				$count = (int) $item['count'];
			}

			$html .= '<a class="' . self::CSS . '__card' . ( $is_active ? ' is-active' : '' ) . '" href="' . esc_url( (string) $item['url'] ) . '"'
				. ( '' !== $id ? ' data-dw-view="' . esc_attr( $id ) . '"' : '' ) . '>';

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

		/** This filter is documented in nav(). */
		return (string) apply_filters( 'dashwoo_account_cards_html', $html, $args );
	}

	/**
	 * The dashboard block: greeting + summary + cards.
	 *
	 * @param array<string,mixed> $args Options: `greeting`, `avatar`, `cards`, `columns`.
	 * @return string
	 */
	public static function dashboard( array $args = array() ) {
		$profile  = new Profile();
		$parts    = sanitize_key( (string) self::option( $args, 'parts', 'hero-cards' ) );
		$greeting = 'plain' === $parts ? (bool) self::option( $args, 'greeting', true ) : ( 'cards' === $parts ? false : (bool) self::option( $args, 'greeting', true ) );
		$avatar   = (bool) self::option( $args, 'avatar', true );
		$cards    = 'hero' === $parts ? false : (bool) self::option( $args, 'cards', true );
		$text     = (string) self::option( $args, 'greeting_text', 'خوش آمدید' );
		$logo     = ! empty( $args['logo'] ) && class_exists( __NAMESPACE__ . '\\Brand' ) ? Brand::mark( array( 'size' => max( 16, (int) ( $args['logo_size'] ?? 28 ) ) ) ) : '';

		if ( 'cards' === $parts ) {
			$greeting = false;
			$avatar   = false;
		}

		$html = '';

		if ( $greeting || $avatar ) {
			$html .= '<div class="' . self::CSS . '__hero">';

			if ( '' !== $logo ) {
				$html .= '<div class="' . self::CSS . '__hero-logo">' . $logo . '</div>';
			}

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

				// The summary (e-mail + order count) stays on by default: a widget that
				// only wants the greeting turns it off explicitly.
				if ( ! array_key_exists( 'summary', $args ) || ! empty( $args['summary'] ) ) {
					$html .= '<p class="' . self::CSS . '__hero-summary">' . esc_html( $profile->summary() ) . '</p>';
				}

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
		// In the builder the login form is not helpful: sample data is.
		if ( Elementor_Bridge::is_editor() ) {
			$customer = Preview::customer();

			return self::open( $args ) . self::dashboard( array(
				'greeting_text' => $customer['name'],
				'summary'       => 'پیش‌نمایش چیدمان در ویرایشگر المنتور',
			) ) . self::close();
		}

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

		// Where the content comes from: `auto` = WooCommerce first, `native` = only
		// WooCommerce, `dashwoo` = only the DashWoo template.
		$source = isset( $args['source'] ) && '' !== (string) $args['source']
			? sanitize_key( (string) $args['source'] )
			: ( empty( $args['shortcut'] ) ? 'dashwoo' : 'auto' );

		if ( ! in_array( $source, array( 'auto', 'native', 'dashwoo' ), true ) ) {
			$source = 'auto';
		}

		// A widget without a title still needs a heading: fall back to the endpoint's
		// own label, which the shop owner set (or DashWoo's Persian default). Turning
		// the heading off in the widget is a decision, so it beats the fallback.
		$show_title = ! array_key_exists( 'show_title', $args ) || ! empty( $args['show_title'] );

		if ( ! $show_title ) {
			$args['title'] = '';
		} elseif ( '' === trim( (string) ( $args['title'] ?? '' ) ) ) {
			$args['title'] = Endpoints::instance()->label( $endpoint );
		}

		if ( 'dashwoo' !== $source && function_exists( 'has_action' ) && has_action( $hook ) ) {
			ob_start();
			do_action( $hook );
			$native = (string) ob_get_clean();
		}

		if ( '' !== trim( $native ) ) {
			$title = isset( $args['title'] ) ? (string) $args['title'] : '';

			return Sections::heading( $title, $args ) . '<div class="' . self::CSS . '__native">' . $native . '</div>';
		}

		if ( 'native' === $source ) {
			return Sections::unavailable( 'این بخش روی این فروشگاه محتوایی ندارد.' );
		}

		// The template *section* is what the shop owner picks (orders, downloads,
		// addresses...); the endpoint stays the WooCommerce id (`edit-address`).
		$section = Panel::section_of( $endpoint );

		if ( Templates::supports( $section ) && dashwoo_is_on( 'account_templates.enabled' ) ) {
			$block = Templates::render( $section, $args );
		} else {
			$block = Sections::block( $endpoint, $args );
		}

		if ( '' === $block && ! empty( $args['title'] ) ) {
			return Sections::heading( (string) $args['title'], $args );
		}

		return $block;
	}

	/**
	 * The two-pane account panel (menu card + content card).
	 *
	 * @param array<string,mixed> $args Args; see Panel::render().
	 * @return string
	 */
	public static function panel( array $args = array() ) {
		return Panel::render( $args );
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
		$gap    = (int) self::option( $args, 'space', (int) dashwoo_get_setting( 'account_design.gap', 0 ) );

		$scope = '.' . self::CSS;

		$css  = $scope . '{--dw-acc-accent:' . self::color( $accent ) . ';';
		$css .= '--dw-acc-radius:' . max( 0, min( 60, $radius ) ) . 'px;';
		$css .= '--dw-acc-nav-width:' . max( 160, min( 480, $width ) ) . 'px;';
		$css .= '--dw-acc-cols:' . max( 1, min( 4, $cols ) ) . ';';
		$css .= '--dw-acc-avatar:' . max( 32, min( 200, $avatar ) ) . 'px;';

		if ( $gap > 0 ) {
			$css .= '--dw-acc-space:' . max( 0, min( 80, $gap ) ) . 'px;';
		}

		$css .= '}';

		// Free-form variables: this is how the Elementor style controls (colour,
		// slider, dimension, typography...) reach the markup - one CSS custom property
		// per control, no stylesheet of Elementor's own, nothing to purge.
		foreach ( (array) self::option( $args, 'vars', array() ) as $prop => $value ) {
			$prop  = preg_replace( '/[^a-z0-9\-]/i', '', (string) $prop );
			$value = trim( (string) $value );

			if ( '' === $prop || '' === $value ) {
				continue;
			}

			$css .= $scope . '{--dw-acc-' . $prop . ':' . self::css_value( $value ) . ';}';
		}

		/**
		 * Filter the account inline CSS.
		 *
		 * @param string              $css  Generated CSS.
		 * @param array<string,mixed> $args Overrides in play.
		 */
		return (string) apply_filters( 'dashwoo_account_css', $css, $args );
	}

	/**
	 * The neutralising reset, used when the shop owner takes the styling over
	 * (`auto_css` off + `css_reset` on). It only removes browser defaults - it never
	 * adds a DashWoo look.
	 *
	 * @return string
	 */
	public static function reset_css() {
		$scope = '.' . self::CSS . '--bare';
		$css   = $scope . ', ' . $scope . ' *{box-sizing:border-box;}';
		$css  .= $scope . ' ul,' . $scope . ' ol{list-style:none;margin:0;padding:0;}';
		$css  .= $scope . ' h1,' . $scope . ' h2,' . $scope . ' h3,' . $scope . ' h4,' . $scope . ' p{margin:0;}';
		$css  .= $scope . ' a{color:inherit;text-decoration:none;}';
		$css  .= $scope . ' img,' . $scope . ' svg{max-width:100%;height:auto;}';
		$css  .= $scope . ' button{font:inherit;color:inherit;background:none;border:0;padding:0;cursor:pointer;}';
		$css  .= $scope . ' table{border-collapse:collapse;width:100%;}';

		/**
		 * Filter the reset CSS.
		 *
		 * @param string $css Reset CSS.
		 */
		return (string) apply_filters( 'dashwoo_account_reset_css', $css );
	}

	/**
	 * A safe CSS value for a custom property (numbers, lengths, colours, keywords and
	 * var() references only - anything else is dropped).
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function css_value( $value ) {
		$value = trim( preg_replace( '/[\x00-\x1f]+/', '', (string) $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^-?[0-9.]+(px|em|rem|%|vh|vw|s|deg)?$/', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^#[0-9a-f]{3,8}$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^(rgb|hsl)a?\([0-9.,%\s\/]+\)$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^var\(--[a-z0-9\-_]+\)$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^[a-z0-9\s,()\-]+$/i', $value ) ) {
			return $value;
		}

		return '';
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
