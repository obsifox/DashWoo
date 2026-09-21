<?php
/**
 * Elementor bridge for the account pack.
 *
 * Three jobs, all of them about "the shop owner must be able to build the account area
 * with Elementor":
 *
 *  1. register the DashWoo account widgets on *both* Elementor generations
 *     (`elementor/widgets/register` since 3.5 and the legacy
 *     `elementor/widgets/widgets_registered`, which older installations still use -
 *     without the legacy hook no DashWoo widget shows up in the panel at all),
 *  2. make the builder usable: recognise the editor (edit mode and preview mode),
 *     keep the account stylesheet loaded there and feed the widgets sample data when
 *     the logged-in administrator has no orders of their own,
 *  3. let the shop owner create the layout in one click: an Elementor document with
 *     the DashWoo widgets, or - when the Elementor document API is not available -
 *     the `[dashwoo_account]` shortcode, plus a registered Theme Builder location so
 *     a template can own the account area too.
 *
 * @package DashWoo\Account
 */

namespace DashWoo\Account;

use DashWoo\DesignSystem\Elementor\Tokens_Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration for the My Account area.
 */
class Elementor_Bridge {

	/**
	 * Singleton.
	 *
	 * @var Elementor_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Theme Builder location slug.
	 */
	const LOCATION = 'dashwoo_account';

	/**
	 * Query flag used by the one-click builder redirect.
	 */
	const BUILT_FLAG = 'dashwoo_account_built';

	/**
	 * Singleton accessor.
	 *
	 * @return Elementor_Bridge
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook everything up (called from Account::boot()).
	 *
	 * @return void
	 */
	public function boot() {
		// The builders own the widget list; this class only makes sure it is filled.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );

		// Theme Builder: let a template own the account area (Elementor Pro).
		add_action( 'elementor/theme/register_locations', array( $this, 'register_location' ) );

		// The editor and its preview need the account stylesheet as well, otherwise the
		// widgets look unstyled exactly where they are being designed.
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_styles' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_editor_styles' ) );

		// One-click layout from the admin (and from the notice inside the editor).
		add_action( 'admin_post_dashwoo_build_account_layout', array( $this, 'handle_build_request' ) );

		// The "build it for me" card lives inside the account source settings section.
		add_action( 'dashwoo_settings_section_after_fields', array( $this, 'settings_card' ), 10, 2 );

		// In the editor, tell the shop owner when this page has no DashWoo widgets yet.
		add_action( 'wp_footer', array( $this, 'editor_notice' ), 99 );
	}

	/* --------------------------------------------------------------------- */
	/* 1. widget registration (both Elementor generations)                    */
	/* --------------------------------------------------------------------- */

	/**
	 * Modern registration (Elementor 3.5+).
	 *
	 * @param mixed $manager Widgets manager.
	 * @return void
	 */
	public function register_widgets( $manager ) {
		if ( ! is_object( $manager ) ) {
			return;
		}

		if ( ! method_exists( $manager, 'register' ) && ! method_exists( $manager, 'register_widget_type' ) ) {
			return;
		}

		if ( method_exists( $manager, 'register' ) ) {
			Tokens_Integration::instance()->register_widgets( $manager );

			return;
		}

		$this->register_widgets_legacy( $manager );
	}

	/**
	 * Legacy registration (Elementor < 3.5): `register_widget_type()` instead of
	 * `register()`. Same widget classes, same categories.
	 *
	 * @param mixed $manager Widgets manager.
	 * @return void
	 */
	public function register_widgets_legacy( $manager ) {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'register_widget_type' ) ) {
			return;
		}

		foreach ( self::widget_classes() as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}

			$manager->register_widget_type( new $class() );
		}
	}

	/**
	 * Widget classes DashWoo ships (filterable, same list the modern path uses).
	 *
	 * @return array<int,string>
	 */
	public static function widget_classes() {
		/**
		 * Filter the widget classes DashWoo registers.
		 *
		 * @param array<int,string> $classes Widget classes.
		 */
		return (array) apply_filters( 'dashwoo_elementor_widgets', array(
			'\\DashWoo\\Widgets\\Icon_Widget',
			'\\DashWoo\\Widgets\\Account_Dashboard_Widget',
			'\\DashWoo\\Widgets\\Account_Nav_Widget',
			'\\DashWoo\\Widgets\\Account_Profile_Widget',
			'\\DashWoo\\Widgets\\Account_Orders_Widget',
			'\\DashWoo\\Widgets\\Account_Downloads_Widget',
			'\\DashWoo\\Widgets\\Account_Addresses_Widget',
			'\\DashWoo\\Widgets\\Account_Payment_Widget',
			'\\DashWoo\\Widgets\\Account_Details_Widget',
			'\\DashWoo\\Widgets\\Account_Logout_Widget',
			'\\DashWoo\\Widgets\\Account_Forms_Widget',
		) );
	}

	/**
	 * Slugs of the account widgets (used to detect them inside a document).
	 *
	 * @return array<int,string>
	 */
	public static function account_widget_names() {
		return array(
			'dashwoo_account_dashboard',
			'dashwoo_account_nav',
			'dashwoo_account_profile',
			'dashwoo_account_orders',
			'dashwoo_account_downloads',
			'dashwoo_account_addresses',
			'dashwoo_account_payment',
			'dashwoo_account_details',
			'dashwoo_account_logout',
			'dashwoo_account_forms',
		);
	}

	/* --------------------------------------------------------------------- */
	/* 2. builder awareness                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * Is Elementor available in this request?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( '\\Elementor\\Plugin' ) && is_object( \Elementor\Plugin::$instance );
	}

	/**
	 * Elementor version ('' when unknown).
	 *
	 * @return string
	 */
	public static function version() {
		if ( ! self::is_active() || ! isset( \Elementor\Plugin::$instance->version ) ) {
			return '';
		}

		return (string) \Elementor\Plugin::$instance->version;
	}

	/**
	 * Are we inside the Elementor editor (or its preview iframe)?
	 *
	 * `is_edit_mode()` is the reliable editor check; older and newer Elementor versions
	 * both expose it, and preview mode covers the editor iframe on some setups.
	 *
	 * @return bool
	 */
	public static function is_editor() {
		if ( self::is_active() ) {
			$plugin = \Elementor\Plugin::$instance;

			foreach ( array( 'editor' => 'is_edit_mode', 'preview' => 'is_preview_mode' ) as $key => $method ) {
				if ( ! isset( $plugin->{$key} ) || ! is_object( $plugin->{$key} ) ) {
					continue;
				}

				if ( method_exists( $plugin->{$key}, $method ) && $plugin->{$key}->{$method}() ) {
					return true;
				}
			}
		}

		// Fallbacks that do not depend on the Elementor objects: the editor iframe
		// carries `elementor-preview`, and the panel talks over elementor-ajax.
		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( '' !== $action && 0 === strpos( $action, 'elementor' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is the account area being rendered for the builder (editor or preview)?
	 *
	 * Used to keep the account stylesheet loaded while designing, even before the page
	 * is saved as an Elementor document.
	 *
	 * @return bool
	 */
	public static function is_builder_request() {
		return self::is_editor();
	}

	/**
	 * Does this page already contain a DashWoo account widget?
	 *
	 * @param int $post_id Post id (0 = queried object).
	 * @return bool
	 */
	public static function has_account_widgets( $post_id = 0 ) {
		$post_id = $post_id > 0 ? (int) $post_id : ( function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0 );

		if ( $post_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return false;
		}

		$raw = (string) get_post_meta( $post_id, '_elementor_data', true );

		if ( '' === $raw ) {
			return false;
		}

		foreach ( self::account_widget_names() as $name ) {
			if ( false !== strpos( $raw, '"' . $name . '"' ) ) {
				return true;
			}
		}

		return false;
	}

	/* --------------------------------------------------------------------- */
	/* 3. one-click layout                                                    */
	/* --------------------------------------------------------------------- */

	/**
	 * The Elementor structure the builder button creates.
	 *
	 * Deliberately simple and editable: a two-column hero (menu + dashboard) and a
	 * second row with the profile card and the orders list. Every widget is a normal
	 * DashWoo widget, so everything can be dragged, deleted or restyled afterwards.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function elements() {
		$widget = static function ( $name, array $settings = array() ) {
			return array(
				'id'         => substr( md5( $name . wp_json_encode( $settings ) ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => $name,
				'settings'   => $settings,
				'elements'   => array(),
			);
		};

		$column = static function ( $size, array $widgets ) {
			return array(
				'id'       => substr( md5( $size . wp_json_encode( $widgets ) ), 0, 7 ),
				'elType'   => 'column',
				'settings' => array( '_column_size' => (int) $size, '_inline_size' => null ),
				'elements' => $widgets,
			);
		};

		$section = static function ( array $columns ) {
			return array(
				'id'       => substr( md5( wp_json_encode( $columns ) ), 0, 7 ),
				'elType'   => 'section',
				'settings' => array( 'structure' => count( $columns ) > 1 ? '20' : '10' ),
				'elements' => $columns,
			);
		};

		/**
		 * Filter the layout the one-click builder writes into the account page.
		 *
		 * @param array<int,array<string,mixed>> $elements Elementor elements.
		 */
		return (array) apply_filters( 'dashwoo_account_elementor_layout', array(
			$section( array(
				$column( 30, array( $widget( 'dashwoo_account_nav', array( 'dw_layout' => 'menu', 'dw_icons' => 'yes', 'dw_sticky' => 'yes' ) ) ) ),
				$column( 70, array( $widget( 'dashwoo_account_dashboard', array( 'dw_greeting' => 'خوش آمدید', 'dw_cards' => 'yes' ) ) ) ),
			) ),
			$section( array(
				$column( 50, array( $widget( 'dashwoo_account_profile', array() ) ) ),
				$column( 50, array( $widget( 'dashwoo_account_orders', array( 'dw_title' => 'سفارش‌های من' ) ) ) ),
			) ),
		) );
	}

	/**
	 * Build (or rebuild) the account page layout.
	 *
	 * Path 1 - Elementor document API available: write a real Elementor document, so
	 *          the page opens in the editor with the widgets already placed.
	 * Path 2 - no document API (Elementor inactive, or a blocked REST/HTTP context):
	 *          write the `[dashwoo_account]` shortcode, which renders the same area and
	 *          works on any theme.
	 *
	 * @param int $post_id Target page (0 = WooCommerce's My Account page).
	 * @return array<string,mixed> Result with ok/mode/message/editor_url.
	 */
	public static function build_layout( $post_id = 0 ) {
		$post_id = $post_id > 0 ? (int) $post_id : self::account_page_id();

		if ( $post_id <= 0 ) {
			return array(
				'ok'      => false,
				'mode'    => '',
				'message' => 'صفحهٔ «حساب کاربری» پیدا نشد. اول در ووکامرس → تنظیمات → پیشرفته یک برگه به حساب کاربری اختصاص دهید.',
				'url'     => '',
			);
		}

		$elements = self::elements();

		if ( self::is_active() && isset( \Elementor\Plugin::$instance->documents ) && is_object( \Elementor\Plugin::$instance->documents ) ) {
			$documents = \Elementor\Plugin::$instance->documents;
			$document  = method_exists( $documents, 'get' ) ? $documents->get( $post_id ) : null;

			if ( $document && method_exists( $document, 'save' ) ) {
				$document->save(
					array(
						'elements' => $elements,
						'settings' => array(),
					)
				);

				if ( function_exists( 'update_post_meta' ) ) {
					update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
					update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
					update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
				}

				return array(
					'ok'      => true,
					'mode'    => 'elementor',
					'message' => 'چیدمان حساب کاربری با ابزارک‌های DashWoo ساخته شد. الان در ویرایشگر المنتور باز می‌شود و هر بخشش قابل جابه‌جایی و تغییر است.',
					'url'     => self::editor_url( $post_id ),
					'post_id' => $post_id,
				);
			}
		}

		// Fallback: the shortcode renders the same area without Elementor.
		if ( function_exists( 'wp_update_post' ) ) {
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => '[dashwoo_account]',
				),
				true
			);

			if ( ! is_wp_error( $result ) ) {
				return array(
					'ok'      => true,
					'mode'    => 'shortcode',
					'message' => 'محتوای صفحه با شورت‌کد [dashwoo_account] جایگزین شد. با المنتور هم می‌توانید همین شورت‌کد را در یک ویجت شورت‌کد بگذارید یا ابزارک‌های DashWoo را کنارش اضافه کنید.',
					'url'     => self::editor_url( $post_id ),
					'post_id' => $post_id,
				);
			}
		}

		return array(
			'ok'      => false,
			'mode'    => '',
			'message' => 'ساخت چیدمان ممکن نشد؛ دسترسی نوشتن به این برگه را بررسی کنید.',
			'url'     => '',
		);
	}

	/**
	 * Direct link into the Elementor editor for a page.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function editor_url( $post_id ) {
		$post_id = (int) $post_id;

		if ( function_exists( 'admin_url' ) ) {
			return admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
		}

		return '';
	}

	/**
	 * WooCommerce's My Account page id (0 when unassigned).
	 *
	 * @return int
	 */
	public static function account_page_id() {
		$id = 0;

		if ( function_exists( 'wc_get_page_id' ) ) {
			$id = (int) wc_get_page_id( 'myaccount' );
		}

		// WooCommerce keeps the same value in this option; reading it too makes the
		// bridge work on installations where wc_get_page_id() is not loaded yet.
		if ( $id <= 0 && function_exists( 'get_option' ) ) {
			$id = (int) get_option( 'woocommerce_myaccount_page_id' );
		}

		return max( 0, $id );
	}

	/**
	 * Handle the `admin-post.php?action=dashwoo_build_account_layout` request.
	 *
	 * @return void
	 */
	public function handle_build_request() {
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( 'برای این کار دسترسی مدیریت لازم است.' ) );
		}

		check_admin_referer( 'dashwoo_build_account_layout' );

		$result  = self::build_layout( $post_id );
		$target  = $result['ok'] ? (string) $result['url'] : self::editor_url( $post_id );
		$status  = $result['ok'] ? '1' : '0';

		dashwoo_log( $result['ok'] ? 'info' : 'warning', 'account elementor layout built', array(
			'mode'    => $result['mode'],
			'post_id' => $post_id,
			'ok'      => $result['ok'],
		) );

		wp_safe_redirect( add_query_arg( self::BUILT_FLAG, $status, $target ) );
		exit;
	}

	/**
	 * Build URL used by the buttons (with the nonce).
	 *
	 * @param int $post_id Target page.
	 * @return string
	 */
	public static function build_url( $post_id = 0 ) {
		if ( ! function_exists( 'admin_url' ) || ! function_exists( 'wp_nonce_url' ) ) {
			return '';
		}

		$args = array( 'action' => 'dashwoo_build_account_layout' );

		if ( $post_id > 0 ) {
			$args['post'] = (int) $post_id;
		}

		return wp_nonce_url( admin_url( 'admin-post.php?' . http_build_query( $args ) ), 'dashwoo_build_account_layout' );
	}

	/* --------------------------------------------------------------------- */
	/* Theme Builder + editor assets                                          */
	/* --------------------------------------------------------------------- */

	/**
	 * Register the DashWoo account location (Elementor Pro Theme Builder).
	 *
	 * @param mixed $manager Locations manager.
	 * @return void
	 */
	public function register_location( $manager ) {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'register_location' ) ) {
			return;
		}

		$manager->register_location(
			self::LOCATION,
			array(
				'label'    => 'حساب کاربری — DashWoo',
				'multiple' => false,
				'public'   => true,
			)
		);
	}

	/**
	 * Render a Theme Builder template for the account area, when one exists.
	 *
	 * @return string Markup ('' when there is no template or no Elementor Pro).
	 */
	public static function location_html() {
		if ( ! function_exists( 'elementor_theme_do_location' ) ) {
			return '';
		}

		ob_start();
		$rendered = (bool) elementor_theme_do_location( self::LOCATION );
		$html     = (string) ob_get_clean();

		return $rendered ? $html : '';
	}

	/**
	 * Keep the account stylesheet alive inside the builder.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles() {
		Account::instance()->enqueue();
	}

	/**
	 * Inside the editor: a short note with the one-click builder when the page has no
	 * DashWoo widget yet.
	 *
	 * @return void
	 */
	public function editor_notice() {
		if ( ! self::is_editor() || ! dashwoo_is_on( 'account_source.enabled' ) ) {
			return;
		}

		$page_id = self::account_page_id();

		// The account page already built with DashWoo widgets needs no offer.
		if ( $page_id > 0 && self::has_account_widgets( $page_id ) ) {
			return;
		}

		if ( $page_id <= 0 ) {
			// Nothing to build on yet: explain instead of offering a button that cannot
			// do anything.
			echo '<div class="dw-editor-hint" style="position:fixed;inset-inline:0;bottom:0;z-index:9999;'
				. 'background:#0d1117;color:#e6edf3;font:14px/1.8 Tahoma,sans-serif;padding:10px 16px;'
				. 'text-align:center;border-top:1px solid #30363d">'
				. esc_html( 'این برگه به‌عنوان صفحهٔ «حساب کاربری» ووکامرس ثبت نشده است؛ اول در ووکامرس → تنظیمات → پیشرفته آن را تعیین کنید.' )
				. '</div>';

			return;
		}

		$url = self::build_url( $page_id );

		if ( '' === $url ) {
			return;
		}

		$label = 'این برگه هنوز هیچ ابزارک DashWoo ندارد. با یک کلیک چیدمان حساب کاربری را بساز.';

		echo '<div class="dw-editor-hint" style="position:fixed;inset-inline:0;bottom:0;z-index:9999;'
			. 'background:#0d1117;color:#e6edf3;font:14px/1.8 Tahoma,sans-serif;padding:10px 16px;'
			. 'display:flex;gap:12px;align-items:center;justify-content:center;border-top:1px solid #30363d">'
			. '<span>' . esc_html( $label ) . '</span>'
			. '<a href="' . esc_url( $url ) . '" style="background:#2f81f7;color:#fff;padding:8px 14px;'
			. 'border-radius:8px;text-decoration:none">ساخت چیدمان با DashWoo</a></div>';
	}

	/**
	 * The "build it for me" card inside the «منبع قالب‌ها» settings section.
	 *
	 * @param string              $section_key Section key.
	 * @param array<string,mixed> $context     View context.
	 * @return void
	 */
	public function settings_card( $section_key, $context = array() ) {
		unset( $context );

		if ( 'account_source' !== $section_key ) {
			return;
		}

		$page_id = self::account_page_id();
		$url     = self::build_url( $page_id );
		$status  = self::status();

		echo '<div class="dw-card" style="margin-top:16px">';
		echo '<h3 style="margin-top:0">ساخت چیدمان با المنتور</h3>';

		if ( $page_id > 0 ) {
			printf(
				'<p>صفحهٔ حساب کاربری: <a href="%s" target="_blank" rel="noopener">%s</a></p>',
				esc_url( (string) get_permalink( $page_id ) ),
				esc_html( (string) get_the_title( $page_id ) )
			);
		}

		echo '<p>با یک کلیک، چیدمان کامل حساب کاربری (منو + پیشخوان + پروفایل + سفارش‌ها) به شکل ابزارک‌های DashWoo داخل صفحه ساخته می‌شود و ویرایشگر المنتور باز می‌شود؛ بعد از آن هر بخش را می‌توانید جابه‌جا، حذف یا بازطراحی کنید.</p>';

		if ( '' !== $url ) {
			printf(
				'<p><a class="button button-primary" href="%s">ساخت / بازسازی چیدمان حساب کاربری در المنتور</a></p>',
				esc_url( $url )
			);
		}

		echo '<h4>وضعیت آماده‌بودن المنتور</h4><ul style="margin:0;padding-inline-start:18px">';

		foreach ( $status['checks'] as $check ) {
			printf(
				'<li>%s <strong>%s</strong> — %s</li>',
				$check['ok'] ? '✅' : '⚠️',
				esc_html( $check['label'] ),
				esc_html( $check['detail'] )
			);
		}

		echo '</ul></div>';
	}

	/**
	 * Readiness report shown in the Settings Center (and helpful in support tickets).
	 *
	 * @return array<string,mixed>
	 */
	public static function status() {
		$page_id = self::account_page_id();
		$mode    = Source_Adapter::instance()->mode();

		$checks = array(
			array(
				'key'    => 'elementor',
				'label'  => 'افزونهٔ المنتور',
				'ok'     => self::is_active(),
				'detail' => self::is_active()
					? 'فعال است' . ( '' !== self::version() ? ' (نسخهٔ ' . self::version() . ')' : '' )
					: 'المنتور فعال نیست؛ ابزارک‌ها فقط وقتی المنتور روشن باشد در پنل دیده می‌شوند.',
			),
			array(
				'key'    => 'widgets',
				'label'  => 'ابزارک‌های DashWoo',
				'ok'     => self::is_active(),
				'detail' => 'دستهٔ «DashWoo — حساب کاربری» با ' . count( self::account_widget_names() ) . ' ابزارک ثبت می‌شود'
					. ( self::is_active() ? '' : ' (نیازمند المنتور)' ),
			),
			array(
				'key'    => 'account_page',
				'label'  => 'صفحهٔ حساب کاربری ووکامرس',
				'ok'     => $page_id > 0,
				'detail' => $page_id > 0
					? 'شناسهٔ ' . $page_id
					: 'در ووکامرس → تنظیمات → پیشرفته، برگهٔ «حساب کاربری» را تعیین کنید.',
			),
			array(
				'key'    => 'document',
				'label'  => 'ساخته‌شده با المنتور',
				'ok'     => self::has_account_widgets( $page_id ),
				'detail' => self::has_account_widgets( $page_id )
					? 'صفحه ابزارک حساب کاربری دارد؛ در ویرایشگر المنتور قابل تغییر است.'
					: 'با دکمهٔ بالا چیدمان آمادهٔ DashWoo را داخل صفحه بسازید.',
			),
			array(
				'key'    => 'mode',
				'label'  => 'حالت منبع قالب',
				'ok'     => Source_Adapter::MODE_DEFAULT !== $mode,
				'detail' => Source_Adapter::MODE_DEFAULT === $mode
					? 'روی «بومی» است؛ برای واگذاری صفحه به المنتور، حالت را روی «المنتور» بگذارید.'
					: 'حالت فعال: ' . $mode,
			),
		);

		/**
		 * Filter the Elementor readiness report.
		 *
		 * @param array<string,mixed> $status Report.
		 */
		return (array) apply_filters( 'dashwoo_account_elementor_status', array(
			'checks'  => $checks,
			'page_id' => $page_id,
			'mode'    => $mode,
			'widgets' => count( self::account_widget_names() ),
		) );
	}
}
