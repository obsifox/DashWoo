<?php
/**
 * The 30 Settings Center sections + their field schemas.
 *
 * The schema is the single source of truth for: the admin UI, sanitisation,
 * defaults, the REST schema and the regression snapshots.
 *
 * @package DashWoo
 */

namespace DashWoo\Settings;

use DashWoo\Account\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Section + field definitions.
 */
final class Sections {

	/**
	 * Navigation groups (order matters).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function groups() {
		return array(
			'home'      => array(
				'label' => 'داشبورد',
				'icon'  => 'dashboard',
				'slug'  => 'dashwoo',
			),
			'design'    => array(
				'label' => 'طراحی',
				'icon'  => 'palette',
				'slug'  => 'colors',
			),
			'account'   => array(
				'label' => 'حساب کاربری',
				'icon'  => 'account_circle',
				'slug'  => 'account_layout',
			),
			'fonts'     => array(
				'label' => 'فونت و آیکون',
				'icon'  => 'text_fields',
				'slug'  => 'fonts_google',
			),
			'assets'    => array(
				'label' => 'دارایی‌ها',
				'icon'  => 'folder_open',
				'slug'  => 'assets_images',
				'page'  => 'dashwoo-assets',
			),
			'elementor' => array(
				'label' => 'المنتور',
				'icon'  => 'layout',
				'slug'  => 'elementor',
			),
			'system'    => array(
				'label' => 'سیستم',
				'icon'  => 'settings',
				'slug'  => 'general',
			),
		);
	}

	/**
	 * Canonical order of the sections (menus, tabs, forms and exports follow it).
	 *
	 * @return array<int,string>
	 */
	public static function order() {
		return array(
			'dashboard',
			'colors',
			'typography',
			'spacing',
			'radius',
			'shadows',
			'borders',
			'buttons',
			'forms',
			'cards',
			'tables',
			'badges',
			'alerts',
			'overlays',
			'pagination',
			'account_layout',
			'account_endpoints',
			'account_templates',
			'account_panel',
			'account_design',
			'account_forms',
			'account_source',
			'fonts_google',
			'fonts_custom',
			'assets_fonts',
			'icons',
			'icon_widget',
			'assets_images',
			'assets_svg',
			'assets_custom',
			'elementor',
			'general',
			'compatibility',
			'updates',
			'performance',
			'features',
			'rest_api',
			'logs',
		);
	}

	/**
	 * Navigation clusters (level 2): group => cluster => label.
	 *
	 * The admin never shows a flat list of all sections: a group is a submenu and
	 * a cluster is the tab row inside that submenu's screen.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function clusters() {
		return array(
			'home'      => array( 'home' => 'شروع' ),
			'design'    => array(
				'tokens'     => 'پایهٔ طراحی',
				'components' => 'کامپوننت‌ها',
			),
			'account'   => array(
				'layout'    => 'چیدمان حساب',
				'forms'     => 'فرم‌ها و صفحه‌ها',
			),
			'fonts'     => array(
				'families' => 'خانواده‌های فونت',
				'icons'    => 'آیکون‌ها',
			),
			'assets'    => array( 'library' => 'کتابخانه' ),
			'elementor' => array( 'integration' => 'یکپارچه‌سازی' ),
			'system'    => array(
				'basic'      => 'عمومی',
				'delivery'   => 'کارایی و تحویل',
				'capability' => 'قابلیت‌ها',
				'tools'      => 'API و گزارش',
			),
		);
	}

	/**
	 * All sections: key => definition.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		$sections = array();

		// ---------------------------------------------------------- Core (5).
		$sections['dashboard'] = array(
			'label'       => 'داشبورد',
			'group'       => 'home',
			'cluster'     => 'home',
			'capability'  => 'manage_options',
			'description' => 'نمای کلی وضعیت سیستم، دارایی‌ها و اعلان‌ها.',
			'fields'      => array(
				array(
					'key'     => 'widgets',
					'label'   => 'نمایش ابزارک‌های فعال',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'notice',
					'label'   => 'متن اعلان داشبورد',
					'type'    => 'text',
					'default' => '',
				),
			),
		);

		// ------------------------------------- Capabilities / features (2).
		$sections['features'] = array(
			'label'       => 'ویژگی‌ها',
			'group'       => 'system',
			'cluster'     => 'capability',
			'description' => 'هر ویژگی اختیاری DashWoo. اگر هاست قابلیت لازم را نداشته باشد، ویژگی خودش خاموش می‌ماند و به‌محض فراهم شدن، خودکار روشن می‌شود.',
			'fields'      => \DashWoo\Capabilities\Capabilities::instance()->fields(),
		);

		// ------------------------------------------------------- Core (4).
		$sections['general'] = array(
			'label'       => 'عمومی',
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => 'فعال‌سازی کلی، حالت رندر، زبان و کش.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال‌سازی DashWoo',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => 'حالت رندر',
					'type'    => 'select',
					'default' => 'full',
					'options' => array(
						'full'          => 'Full Override — کنترل کامل قالب‌ها',
						'compatibility' => 'Compatible — همزیستی با قالب/افزونه‌ها',
					),
				),
				array(
					'key'     => 'elementor_enabled',
					'label'   => 'یکپارچه‌سازی با المنتور',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'language',
					'label'   => 'زبان پلتفرم',
					'type'    => 'select',
					'default' => 'fa_IR',
					'options' => array(
						'fa_IR' => 'فارسی',
						'en_US' => 'English',
					),
				),
				array(
					'key'     => 'rtl',
					'label'   => 'پشتیبانی RTL',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'cache_ttl',
					'label'   => 'مدت کش (ثانیه)',
					'type'    => 'number',
					'default' => 3600,
					'min'     => 60,
					'max'     => 86400,
					'step'    => 60,
				),
				array(
					'key'     => 'log_level',
					'label'   => 'سطح لاگ',
					'type'    => 'select',
					'default' => 'warning',
					'options' => array(
						'debug'   => 'Debug',
						'info'    => 'Info',
						'warning' => 'Warning',
						'error'   => 'Error',
					),
				),
				array(
					'key'     => 'remove_data_on_uninstall',
					'label'   => 'حذف داده‌ها هنگام حذف افزونه',
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['compatibility'] = array(
			'label'       => 'سازگاری نسخه‌ها',
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => 'نتیجه بررسی محیط و آستانه‌های نسخه.',
			'fields'      => array(
				array(
					'key'     => 'min_php',
					'label'   => 'حداقل PHP',
					'type'    => 'text',
					'default' => '8.0',
				),
				array(
					'key'     => 'min_wp',
					'label'   => 'حداقل وردپرس',
					'type'    => 'text',
					'default' => '6.4',
				),
				array(
					'key'     => 'min_wc',
					'label'   => 'حداقل ووکامرس',
					'type'    => 'text',
					'default' => '8.5',
				),
				array(
					'key'     => 'min_elementor',
					'label'   => 'حداقل المنتور',
					'type'    => 'text',
					'default' => '3.24',
				),
				array(
					'key'     => 'auto_fallback',
					'label'   => 'سوئیچ خودکار به Compatibility Mode',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'recheck_interval',
					'label'   => 'فاصله بررسی مجدد (ساعت)',
					'type'    => 'number',
					'default' => 12,
					'min'     => 1,
					'max'     => 168,
					'step'    => 1,
				),
			),
		);

		$sections['updates'] = array(
			'label'       => 'به‌روزرسانی‌ها',
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => 'کانال به‌روزرسانی و مدیریت متادیتای دارایی‌ها.',
			'fields'      => array(
				array(
					'key'     => 'channel',
					'label'   => 'کانال',
					'type'    => 'select',
					'default' => 'stable',
					'options' => array(
						'stable' => 'Stable',
						'beta'   => 'Beta',
					),
				),
				array(
					'key'     => 'auto_update_assets',
					'label'   => 'به‌روزرسانی خودکار دارایی‌ها',
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'check_interval',
					'label'   => 'فاصله بررسی (ساعت)',
					'type'    => 'number',
					'default' => 24,
					'min'     => 1,
					'max'     => 720,
					'step'    => 1,
				),
			),
		);

		$sections['logs'] = array(
			'label'       => 'گزارش‌ها',
			'group'       => 'system',
			'cluster'     => 'tools',
			'capability'  => 'manage_options',
			'description' => 'آخرین رویدادهای سیستم (حداکثر ۲۰۰ ردیف).',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'ثبت رویداد',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_rows',
					'label'   => 'حداکثر ردیف',
					'type'    => 'number',
					'default' => 200,
					'min'     => 20,
					'max'     => 2000,
					'step'    => 20,
				),
			),
		);

		// --------------------------------------------- Fonts & Icons (6).
		$sections['typography'] = array(
			'label'       => 'تایپوگرافی',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'فونت‌های پایه و مقیاس ریسپانسیو با پشتیبانی کامل فارسی.',
			'fields'      => array(
				array(
					'key'     => 'primary_font',
					'label'   => 'فونت اصلی',
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'heading_font',
					'label'   => 'فونت تیترها',
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'body_font',
					'label'   => 'فونت متن',
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'button_font',
					'label'   => 'فونت دکمه‌ها',
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'weights',
					'label'   => 'وزن‌های فعال',
					'type'    => 'multi_select',
					'default' => array( '400', '500', '700' ),
					'options' => array(
						'100' => '100',
						'200' => '200',
						'300' => '300',
						'400' => '400',
						'500' => '500',
						'600' => '600',
						'700' => '700',
						'800' => '800',
						'900' => '900',
					),
				),
				array(
					'key'     => 'base_size_desktop',
					'label'   => 'اندازه پایه — دسکتاپ (px)',
					'type'    => 'number',
					'default' => 16,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'base_size_tablet',
					'label'   => 'اندازه پایه — تبلت (px)',
					'type'    => 'number',
					'default' => 15,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'base_size_mobile',
					'label'   => 'اندازه پایه — موبایل (px)',
					'type'    => 'number',
					'default' => 14,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'line_height',
					'label'   => 'ارتفاع خط',
					'type'    => 'number',
					'default' => 1.75,
					'min'     => 1,
					'max'     => 3,
					'step'    => 0.05,
				),
				array(
					'key'     => 'letter_spacing',
					'label'   => 'فاصله حروف (em)',
					'type'    => 'number',
					'default' => 0,
					'min'     => -0.1,
					'max'     => 0.5,
					'step'    => 0.01,
				),
				array(
					'key'     => 'font_display',
					'label'   => 'font-display',
					'type'    => 'select',
					'default' => 'swap',
					'options' => array(
						'auto'     => 'auto',
						'block'    => 'block',
						'swap'     => 'swap',
						'fallback' => 'fallback',
						'optional' => 'optional',
					),
				),
			),
		);

		// ------------------------------------------------------- Account (4).
		$sections['account_layout'] = array(
			'label'       => 'چیدمان و بخش‌ها',
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => 'ساختار صفحهٔ حساب کاربری: چه بخش‌هایی دیده شوند، با چه ترتیبی و در چه چیدمانی.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال بودن پک حساب کاربری',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'با خاموش کردن این گزینه، سبک و ویجت‌های حساب کاربری DashWoo کنار می‌روند و ظاهر پیش‌فرض باقی می‌ماند.',
				),
				array(
					'key'     => 'layout',
					'label'   => 'چیدمان پیش‌فرض',
					'type'    => 'select',
					'default' => 'sidebar',
					'options' => array(
						'sidebar'          => 'منوی کنار (دو ستون)',
						'top'              => 'منوی بالا (تمام‌عرض)',
						'cards'            => 'کارت‌های میان‌بر',
						'cards_with_menu'  => 'کارت‌ها + منوی کنار',
					),
					'hint'    => 'این مقدار در «قالب ساختهٔ DashWoo» و در میان‌بر [dashwoo_account] استفاده می‌شود؛ داخل المنتور هر ویجت مستقل است.',
				),
				array(
					'key'     => 'cards_columns',
					'label'   => 'تعداد ستون کارت‌های میان‌بر',
					'type'    => 'number',
					'default' => 3,
					'min'     => 1,
					'max'     => 4,
					'step'    => 1,
				),
				array(
					'key'     => 'show_icons',
					'label'   => 'آیکون بخش‌ها',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_counts',
					'label'   => 'شمارنده سفارش/دانلود',
					'type'    => 'toggle',
					'default' => false,
					'hint'    => 'فقط برای سفارش‌ها و دانلودها که با API عمومی ووکامرس شمرده می‌شوند.',
				),
				array(
					'key'     => 'sticky_nav',
					'label'   => 'منوی چسبان در اسکرول',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_breadcrumb',
					'label'   => 'مسیر راهنما (breadcrumb)',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'نمایش «حساب کاربری ← بخش» بالای محتوای بخش‌های داخلی.',
				),
			),
		);

		$sections['account_endpoints'] = array(
			'label'       => 'بخش‌های حساب کاربری',
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => 'برای هر بخش ووکامرس: نمایش/عدم نمایش، عنوان، آیکون و ترتیب. این فهرست از خود ووکامرس خوانده می‌شود، پس اگر افزونه‌ای بخشی اضافه کند، همین‌جا ظاهر می‌شود.',
			'fields'      => array(),
		);

		$sections['account_templates'] = array(
			'label'       => 'قالب‌ها (Template)',
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => 'برای هر بخش حساب کاربری یک قالب انتخاب کنید (فهرست، جدول، خط زمانی، کارتی…). هر قالبی را می‌توان با یک فایل در پوسته بازنویسی کرد: wp-content/themes/<پوسته>/dashwoo/account/orders--table.php',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال‌سازی انتخاب قالب',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'theme_folder',
					'label'   => 'پوشهٔ قالب در پوسته',
					'type'    => 'text',
					'default' => 'dashwoo/account',
					'hint'    => 'قالب‌های سفارشی شما اینجا خوانده می‌شوند (نسبت به پوشهٔ پوسته).',
				),
				array(
					'key'     => 'nav',
					'label'   => 'قالب منوی حساب',
					'type'    => 'select',
					'default' => 'menu',
					'options' => Templates::section_options( 'nav', true ),
				),
				array(
					'key'     => 'dashboard',
					'label'   => 'قالب پیشخوان',
					'type'    => 'select',
					'default' => 'hero-cards',
					'options' => Templates::section_options( 'dashboard', true ),
				),
				array(
					'key'     => 'orders',
					'label'   => 'قالب فهرست سفارش‌ها',
					'type'    => 'select',
					'default' => 'cards',
					'options' => Templates::section_options( 'orders', true ),
				),
				array(
					'key'     => 'order',
					'label'   => 'قالب جزئیات یک سفارش',
					'type'    => 'select',
					'default' => 'summary',
					'options' => Templates::section_options( 'order', true ),
				),
				array(
					'key'     => 'downloads',
					'label'   => 'قالب دانلودها',
					'type'    => 'select',
					'default' => 'list',
					'options' => Templates::section_options( 'downloads', true ),
				),
				array(
					'key'     => 'addresses',
					'label'   => 'قالب آدرس‌ها',
					'type'    => 'select',
					'default' => 'cards',
					'options' => Templates::section_options( 'addresses', true ),
				),
				array(
					'key'     => 'payment',
					'label'   => 'قالب روش‌های پرداخت',
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'payment', true ),
				),
				array(
					'key'     => 'details',
					'label'   => 'قالب جزئیات حساب',
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'details', true ),
				),
				array(
					'key'     => 'profile',
					'label'   => 'قالب کارت پروفایل',
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'profile', true ),
				),
				array(
					'key'     => 'forms',
					'label'   => 'قالب فرم‌ها',
					'type'    => 'select',
					'default' => 'grid',
					'options' => Templates::section_options( 'forms', true ),
				),
				array(
					'key'     => 'logout',
					'label'   => 'قالب خروج',
					'type'    => 'select',
					'default' => 'button',
					'options' => Templates::section_options( 'logout', true ),
				),
			),
		);

		$sections['account_panel'] = array(
			'label'       => 'پنل دوستونه (منو + محتوا)',
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => 'یک کارت منو کنار یک کارت محتوای بزرگ‌تر؛ با کلیک روی هر بخش (سفارش‌ها، دانلودها…) محتوای کارت دوم عوض می‌شود - بدون بارگذاری دوبارهٔ صفحه. جزئیات یک سفارش هم داخل همان کارت باز می‌شود.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'نمایش پنل دوستونه در ناحیهٔ حساب',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => 'حالت نمایش',
					'type'    => 'select',
					'default' => 'swap',
					'options' => array(
						'swap'    => 'جایگزینی محتوای کارت (پیشنهادی)',
						'modal'   => 'پنجرهٔ شناور برای جزئیات',
						'stacked' => 'هر دو کارت، بدون جابه‌جایی خودکار',
					),
					'hint'    => 'در حالت شناور، جزئیات سفارش در یک پنجرهٔ روی صفحه باز می‌شود.',
				),
				array(
					'key'     => 'ajax',
					'label'   => 'جابه‌جایی بدون بارگذاری صفحه (AJAX)',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'اگر خاموش باشد، لینک‌ها مثل قبل صفحه را باز می‌کنند (هنوز هم کار می‌کند).',
				),
				array(
					'key'     => 'source',
					'label'   => 'منبع محتوای بخش‌ها',
					'type'    => 'select',
					'default' => 'auto',
					'options' => array(
						'auto'    => 'خودکار (ووکامرس، و در نبودِ آن قالب DashWoo)',
						'native'  => 'فقط ووکامرس',
						'dashwoo' => 'فقط قالب‌های DashWoo',
					),
				),
				array(
					'key'     => 'default_view',
					'label'   => 'بخش پیش‌فرض',
					'type'    => 'select',
					'default' => 'dashboard',
					'options' => array(
						'dashboard'       => 'پیشخوان',
						'orders'          => 'سفارش‌ها',
						'downloads'       => 'دانلودها',
						'edit-address'    => 'آدرس‌ها',
						'payment-methods' => 'روش‌های پرداخت',
						'edit-account'    => 'جزئیات حساب',
					),
				),
				array(
					'key'     => 'aside',
					'label'   => 'جای کارت منو',
					'type'    => 'select',
					'default' => 'right',
					'options' => array(
						'right' => 'راست (مناسب RTL)',
						'left'  => 'چپ',
					),
				),
				array(
					'key'     => 'aside_width',
					'label'   => 'عرض کارت منو (px)',
					'type'    => 'number',
					'default' => 300,
					'min'     => 140,
					'max'     => 520,
					'step'    => 4,
				),
				array(
					'key'     => 'gap',
					'label'   => 'فاصلهٔ دو کارت (px)',
					'type'    => 'number',
					'default' => 24,
					'min'     => 0,
					'max'     => 80,
					'step'    => 2,
				),
				array(
					'key'     => 'sticky',
					'label'   => 'چسبیدن کارت منو در اسکرول',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'titles',
					'label'   => 'نمایش عنوان بخش بالای کارت محتوا',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'back_label',
					'label'   => 'متن دکمهٔ بازگشت (از جزئیات سفارش)',
					'type'    => 'text',
					'default' => 'بازگشت به سفارش‌ها',
				),
				array(
					'key'     => 'loading_text',
					'label'   => 'متن حالت بارگذاری',
					'type'    => 'text',
					'default' => 'در حال بارگذاری…',
				),
				array(
					'key'     => 'sync_url',
					'label'   => 'تغییر نشانی مرورگر همراه با هر بخش',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'با این گزینه، دکمهٔ «بازگشت» مرورگر هم بین بخش‌ها کار می‌کند.',
				),
				array(
					'key'     => 'mobile',
					'label'   => 'رفتار در موبایل',
					'type'    => 'select',
					'default' => 'stack',
					'options' => array(
						'stack' => 'دو کارت روی هم',
						'tabs'  => 'تب‌های افقی برای منو',
					),
				),
				array(
					'key'     => 'icons',
					'label'   => 'آیکون در منو',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'counts',
					'label'   => 'شمارنده در منو',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'per_page',
					'label'   => 'تعداد آیتم در هر بخش',
					'type'    => 'number',
					'default' => 10,
					'min'     => 1,
					'max'     => 50,
					'step'    => 1,
				),
			),
		);

		$sections['account_design'] = array(
			'label'       => 'ظاهر و رنگ‌ها',
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => 'رنگ تأکید، گردی گوشه‌ها و ابعاد کارت‌های حساب کاربری. رنگ‌ها به‌صورت متغیر CSS تزریق می‌شوند، پس با توکن‌های DashWoo هم‌خوان‌اند.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'اعمال سبک DashWoo روی صفحهٔ حساب',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'accent',
					'label'   => 'رنگ تأکید',
					'type'    => 'color',
					'default' => '#2563eb',
					'hint'    => 'لینک‌ها، کارت فعال و دکمه‌ها از این رنگ استفاده می‌کنند.',
				),
				array(
					'key'     => 'accent_hover',
					'label'   => 'رنگ تأکید (هاور)',
					'type'    => 'color',
					'default' => '#1d4ed8',
				),
				array(
					'key'     => 'radius',
					'label'   => 'گردی گوشه‌ها (px)',
					'type'    => 'number',
					'default' => 18,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'avatar_size',
					'label'   => 'اندازه آواتار (px)',
					'type'    => 'number',
					'default' => 96,
					'min'     => 32,
					'max'     => 200,
					'step'    => 4,
				),
				array(
					'key'     => 'nav_width',
					'label'   => 'عرض منوی کنار (px)',
					'type'    => 'number',
					'default' => 264,
					'min'     => 160,
					'max'     => 480,
					'step'    => 8,
				),
				array(
					'key'     => 'stage_background',
					'label'   => 'پس‌زمینهٔ ناحیهٔ حساب',
					'type'    => 'color',
					'default' => '#f7f8fb',
				),
				array(
					'key'     => 'auto_css',
					'label'   => 'CSS خودکار DashWoo',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'خاموش کنید تا DashWoo هیچ استایلی روی عناصر حساب کاربری نگذارد و طراحی کامل با خودتان (المنتور/پوسته) باشد.',
				),
				array(
					'key'     => 'css_reset',
					'label'   => 'ریست خنثی‌سازی (وقتی CSS خودکار خاموش است)',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'فقط پیش‌فرض‌های مرورگر را کنار می‌زند (لیست، حاشیه، اندازهٔ جعبه) تا از صفر تمیز شروع کنید؛ هیچ ظاهری از DashWoo اضافه نمی‌کند.',
				),
				array(
					'key'     => 'inline_vars',
					'label'   => 'تزریق متغیرهای رنگ و اندازه',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'متغیرهای CSS (رنگ تأکید، گردی، عرض منو) برای استفاده در استایل خودتان. اگر هیچ‌کدام را نمی‌خواهید این را هم خاموش کنید.',
				),
				array(
					'key'     => 'gap',
					'label'   => 'فاصلهٔ پیش‌فرض بلوک‌ها (px)',
					'type'    => 'number',
					'default' => 0,
					'min'     => 0,
					'max'     => 80,
					'step'    => 2,
					'hint'    => 'صفر = همان فاصلهٔ پیش‌فرض DashWoo.',
				),
				array(
					'key'     => 'logo_in_hero',
					'label'   => 'نمایش نشان DashWoo در کارت خوش‌آمد',
					'type'    => 'toggle',
					'default' => false,
					'hint'    => 'متناسب با هویت برند DashWoo (فایل‌های SVG داخل افزونه).',
				),
				array(
					'key'     => 'logo_size',
					'label'   => 'اندازهٔ نشان (px)',
					'type'    => 'number',
					'default' => 28,
					'min'     => 16,
					'max'     => 96,
					'step'    => 2,
				),
			),
		);

		$sections['account_forms'] = array(
			'label'       => 'فرم‌ها و رفتار',
			'group'       => 'account',
			'cluster'     => 'forms',
			'description' => 'رفتار فرم‌ها و اینکه چه چیزی جای قالب‌های پیش‌فرض ووکامرس را بگیرد.',
			'fields'      => array(
				array(
					'key'     => 'profile_fields',
					'label'   => 'فیلدهای فرم پروفایل',
					'type'    => 'multi_select',
					'default' => array( 'display_name' ),
					'options' => array(
						'display_name'  => 'نام نمایشی (قابل ذخیره)',
						'first_name'    => 'نام',
						'last_name'     => 'نام خانوادگی',
						'billing_email' => 'ایمیل صورتحساب',
						'billing_phone' => 'تلفن',
					),
					'hint'    => 'فقط «نام نمایشی» توسط DashWoo ذخیره می‌شود؛ بقیه فقط نمایشی‌اند و ویرایششان به فرم ووکامرس می‌رود.',
				),
				array(
					'key'     => 'inline_validation',
					'label'   => 'اعتبارسنجی درجا (HTML5)',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'فیلدهای لازم با required و نوع مناسب رندر می‌شوند تا مرورگر خودش بررسی کند.',
				),
				array(
					'key'     => 'redirect_after_login',
					'label'   => 'بازگشت به صفحهٔ حساب بعد از ورود',
					'type'    => 'select',
					'default' => 'account',
					'options' => array(
						'account' => 'صفحهٔ حساب کاربری',
						'current' => 'همان صفحه‌ای که کاربر بود',
						'default' => 'رفتار پیش‌فرض وردپرس/ووکامرس',
					),
				),
				array(
					'key'     => 'guest_message',
					'label'   => 'پیام مهمان‌ها',
					'type'    => 'textarea',
					'default' => 'برای دیدن سفارش‌ها، دانلودها و جزئیات حساب وارد شوید.',
					'hint'    => 'برای کاربران وارد‌نشده، به‌جای صفحهٔ خالی همین پیام و دکمهٔ ورود نمایش داده می‌شود.',
				),
				array(
					'key'     => 'endpoint_titles',
					'label'   => 'نمایش عنوان بالای هر بخش',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['account_source'] = array(
			'label'       => 'منبع قالب‌ها',
			'group'       => 'account',
			'cluster'     => 'forms',
			'description' => 'چه کسی صفحهٔ حساب را می‌سازد: قالب‌های ووکامرس، ویجت‌های DashWoo یا ترکیبی از هر دو. همهٔ حالت‌ها برگشت‌پذیرند و هیچ فایل قالبی بازنویسی نمی‌شود.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال بودن مدیر منبع',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => 'حالت',
					'type'    => 'select',
					'default' => 'default',
					'options' => array(
						'default'   => 'ووکامرس (پیش‌فرض): همه‌چیز مثل خود ووکامرس',
						'hybrid'    => 'ترکیبی: ناوبری DashWoo + محتوای ووکامرس',
						'elementor' => 'کاملاً المنتور: ناوبری و محتوا از ویجت‌های DashWoo',
					),
					'hint'    => 'در حالت «کاملاً المنتور» فقط صفحه‌هایی که با المنتور ساخته شده‌اند قالب پیش‌فرض را کنار می‌گذارند؛ اگر صفحه هنوز المنتوری نباشد، DashWoo تخطی نمی‌کند و همان قالب ووکامرس می‌ماند.',
				),
				array(
					'key'     => 'generated_layout',
					'label'   => 'ساخت خودکار چیدمان DashWoo',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'اگر صفحه هنوز ویجت DashWoo نداشته باشد، چیدمان آماده (پیشخوان + منو + کارت‌ها) به محتوای صفحه اضافه می‌شود تا صفحه خالی نماند.',
				),
				array(
					'key'     => 'shortcode',
					'label'   => 'میان‌بر [dashwoo_account]',
					'type'    => 'toggle',
					'default' => true,
					'hint'    => 'برای استفاده در هر برگه یا هر سازنده‌ای؛ همان چیدمان را داخل صفحه چاپ می‌کند.',
				),
				array(
					'key'     => 'menu_priority',
					'label'   => 'اولویت فیلتر منو',
					'type'    => 'number',
					'default' => 20,
					'min'     => 1,
					'max'     => 99,
					'step'    => 1,
					'hint'    => 'اولویت قلاب‌های DashWoo روی فهرست منوی حساب (کوچک‌تر = زودتر از ووکامرس، بزرگ‌تر = بعد از آن).',
				),
			),
		);

		$sections['fonts_google'] = array(
			'label'       => 'گوگل فونت',
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => 'جست‌وجو، دانلود و میزبانی محلی فونت‌های Google.',
			'fields'      => array(
				array(
					'key'     => 'allow_download',
					'label'   => 'اجازه دانلود از Google Fonts',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => 'حداکثر حجم هر فایل (MB)',
					'type'    => 'number',
					'default' => 5,
					'min'     => 1,
					'max'     => 30,
					'step'    => 1,
				),
				array(
					'key'     => 'subsets',
					'label'   => 'زیرمجموعه‌ها',
					'type'    => 'multi_select',
					'default' => array( 'arabic', 'latin', 'latin-ext' ),
					'options' => array(
						'arabic'    => 'Arabic',
						'latin'     => 'Latin',
						'latin-ext' => 'Latin Extended',
					),
				),
				array(
					'key'     => 'unicode_range',
					'label'   => 'حفظ unicode-range',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['fonts_custom'] = array(
			'label'       => 'فونت سفارشی',
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => 'آپلود فونت اختصاصی (woff2/woff/ttf).',
			'fields'      => array(
				array(
					'key'     => 'allow_upload',
					'label'   => 'اجازه آپلود فونت',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'allowed_ext',
					'label'   => 'پسوندهای مجاز',
					'type'    => 'text',
					'default' => 'woff2,woff,ttf,otf',
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => 'حداکثر حجم (MB)',
					'type'    => 'number',
					'default' => 10,
					'min'     => 1,
					'max'     => 50,
					'step'    => 1,
				),
			),
		);

		$sections['assets_fonts'] = array(
			'label'       => 'مدیریت فونت‌ها',
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => 'فهرست فونت‌های نصب‌شده، پیش‌فرض‌ها و همگام‌سازی با المنتور.',
			'fields'      => array(
				array(
					'key'     => 'sync_elementor',
					'label'   => 'افزودن فونت‌ها به لیست المنتور',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'preload_default',
					'label'   => 'preload فونت پیش‌فرض',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'per_page',
					'label'   => 'تعداد در هر صفحه',
					'type'    => 'number',
					'default' => 20,
					'min'     => 5,
					'max'     => 200,
					'step'    => 5,
				),
			),
		);

		$sections['icons'] = array(
			'label'       => 'آیکون‌ها',
			'group'       => 'fonts',
			'cluster'     => 'icons',
			'description' => 'Material Symbols (فونت متغیر) و Material Icons کلاسیک.',
			'fields'      => array(
				array(
					'key'     => 'provider',
					'label'   => 'ارائه‌دهنده پیش‌فرض',
					'type'    => 'select',
					'default' => 'material-symbols',
					'options' => array(
						'material-symbols' => 'Material Symbols',
						'material-icons'   => 'Material Icons',
						'custom-svg'       => 'SVG اختصاصی',
					),
				),
				array(
					'key'     => 'style',
					'label'   => 'سبک فونت متغیر',
					'type'    => 'select',
					'default' => 'outlined',
					'options' => array(
						'outlined' => 'Outlined',
						'rounded'  => 'Rounded',
						'sharp'    => 'Sharp',
					),
				),
				array(
					'key'     => 'weight',
					'label'   => 'Weight (100-700)',
					'type'    => 'number',
					'default' => 400,
					'min'     => 100,
					'max'     => 700,
					'step'    => 100,
				),
				array(
					'key'     => 'fill',
					'label'   => 'Fill (0-1)',
					'type'    => 'number',
					'default' => 0,
					'min'     => 0,
					'max'     => 1,
					'step'    => 1,
				),
				array(
					'key'     => 'grade',
					'label'   => 'Grade (-25 تا 200)',
					'type'    => 'number',
					'default' => 0,
					'min'     => -25,
					'max'     => 200,
					'step'    => 25,
				),
				array(
					'key'     => 'optical_size',
					'label'   => 'Optical Size (20 تا 48)',
					'type'    => 'number',
					'default' => 24,
					'min'     => 20,
					'max'     => 48,
					'step'    => 4,
				),
				array(
					'key'     => 'color',
					'label'   => 'رنگ پیش‌فرض',
					'type'    => 'color',
					'default' => 'currentColor',
				),
				array(
					'key'     => 'size',
					'label'   => 'اندازه پیش‌فرض (px)',
					'type'    => 'number',
					'default' => 24,
					'min'     => 12,
					'max'     => 96,
					'step'    => 2,
				),
			),
		);

		$sections['icon_widget'] = array(
			'label'       => 'ابزارک آیکون',
			'group'       => 'fonts',
			'cluster'     => 'icons',
			'description' => 'کنترل‌های پیش‌فرض ابزارک آیکون در المنتور.',
			'fields'      => array(
				array(
					'key'     => 'show_position_control',
					'label'   => 'کنترل موقعیت (قبل/بعد متن)',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_variation_controls',
					'label'   => 'نمایش کنترل‌های Weight/Fill/Grade',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'default_position',
					'label'   => 'موقعیت پیش‌فرض',
					'type'    => 'select',
					'default' => 'before',
					'options' => array(
						'before' => 'قبل از متن',
						'after'  => 'بعد از متن',
						'only'   => 'فقط آیکون',
					),
				),
			),
		);

		// -------------------------------------------- Design System (7).
		$sections['colors'] = array(
			'label'       => 'رنگ‌ها',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'پالت پایه و رنگ‌های معنایی.',
			'fields'      => array(
				array(
					'key'     => 'primary',
					'label'   => 'Primary',
					'type'    => 'color',
					'default' => '#2563eb',
				),
				array(
					'key'     => 'primary_hover',
					'label'   => 'Primary Hover',
					'type'    => 'color',
					'default' => '#1d4ed8',
				),
				array(
					'key'     => 'secondary',
					'label'   => 'Secondary',
					'type'    => 'color',
					'default' => '#0f766e',
				),
				array(
					'key'     => 'accent',
					'label'   => 'Accent',
					'type'    => 'color',
					'default' => '#f59e0b',
				),
				array(
					'key'     => 'text',
					'label'   => 'متن',
					'type'    => 'color',
					'default' => '#111827',
				),
				array(
					'key'     => 'muted',
					'label'   => 'متن کم‌رنگ',
					'type'    => 'color',
					'default' => '#6b7280',
				),
				array(
					'key'     => 'background',
					'label'   => 'پس‌زمینه',
					'type'    => 'color',
					'default' => '#ffffff',
				),
				array(
					'key'     => 'surface',
					'label'   => 'سطح',
					'type'    => 'color',
					'default' => '#f9fafb',
				),
				array(
					'key'     => 'border',
					'label'   => 'حاشیه',
					'type'    => 'color',
					'default' => '#e5e7eb',
				),
				array(
					'key'     => 'success',
					'label'   => 'موفق',
					'type'    => 'color',
					'default' => '#16a34a',
				),
				array(
					'key'     => 'warning',
					'label'   => 'هشدار',
					'type'    => 'color',
					'default' => '#d97706',
				),
				array(
					'key'     => 'danger',
					'label'   => 'خطا',
					'type'    => 'color',
					'default' => '#dc2626',
				),
			),
		);

		$sections['spacing'] = array(
			'label'       => 'فاصله‌ها',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'مقیاس فاصله‌گذاری (px).',
			'fields'      => array(
				array(
					'key'     => 'unit',
					'label'   => 'واحد',
					'type'    => 'select',
					'default' => 'px',
					'options' => array(
						'px' => 'px',
						'rem' => 'rem',
					),
				),
				array(
					'key'     => 'scale',
					'label'   => 'مقیاس',
					'type'    => 'text',
					'default' => '4,8,12,16,24,32,48,64',
					'description' => 'مقادیر با کاما جدا می‌شوند.',
				),
			),
		);

		$sections['radius'] = array(
			'label'       => 'شعاع گوشه‌ها',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'گردی گوشه‌ها.',
			'fields'      => array(
				array(
					'key'     => 'sm',
					'label'   => 'Small',
					'type'    => 'number',
					'default' => 4,
					'min'     => 0,
					'max'     => 40,
					'step'    => 1,
				),
				array(
					'key'     => 'md',
					'label'   => 'Medium',
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'lg',
					'label'   => 'Large',
					'type'    => 'number',
					'default' => 16,
					'min'     => 0,
					'max'     => 80,
					'step'    => 1,
				),
				array(
					'key'     => 'pill',
					'label'   => 'Pill',
					'type'    => 'number',
					'default' => 999,
					'min'     => 0,
					'max'     => 999,
					'step'    => 1,
				),
			),
		);

		$sections['shadows'] = array(
			'label'       => 'سایه‌ها',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'سایه‌های استاندارد.',
			'fields'      => array(
				array(
					'key'     => 'sm',
					'label'   => 'Small',
					'type'    => 'text',
					'default' => '0 1px 2px rgba(0,0,0,.06)',
				),
				array(
					'key'     => 'md',
					'label'   => 'Medium',
					'type'    => 'text',
					'default' => '0 4px 12px rgba(0,0,0,.08)',
				),
				array(
					'key'     => 'lg',
					'label'   => 'Large',
					'type'    => 'text',
					'default' => '0 12px 32px rgba(0,0,0,.12)',
				),
			),
		);

		$sections['borders'] = array(
			'label'       => 'حاشیه‌ها',
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => 'ضخامت و سبک حاشیه.',
			'fields'      => array(
				array(
					'key'     => 'width',
					'label'   => 'ضخامت (px)',
					'type'    => 'number',
					'default' => 1,
					'min'     => 0,
					'max'     => 8,
					'step'    => 1,
				),
				array(
					'key'     => 'style',
					'label'   => 'سبک',
					'type'    => 'select',
					'default' => 'solid',
					'options' => array(
						'solid'  => 'solid',
						'dashed' => 'dashed',
						'dotted' => 'dotted',
						'none'   => 'none',
					),
				),
			),
		);

		$sections['buttons'] = array(
			'label'       => 'دکمه‌ها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'تنظیمات پیش‌فرض دکمه.',
			'fields'      => array(
				array(
					'key'     => 'padding_x',
					'label'   => 'پدینگ افقی (px)',
					'type'    => 'number',
					'default' => 20,
					'min'     => 4,
					'max'     => 80,
					'step'    => 2,
				),
				array(
					'key'     => 'padding_y',
					'label'   => 'پدینگ عمودی (px)',
					'type'    => 'number',
					'default' => 12,
					'min'     => 4,
					'max'     => 60,
					'step'    => 2,
				),
				array(
					'key'     => 'radius',
					'label'   => 'گردی (px)',
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'weight',
					'label'   => 'وزن فونت',
					'type'    => 'select',
					'default' => '500',
					'options' => array(
						'400' => '400',
						'500' => '500',
						'600' => '600',
						'700' => '700',
					),
				),
				array(
					'key'     => 'transition_ms',
					'label'   => 'زمان انتقال (ms)',
					'type'    => 'number',
					'default' => 180,
					'min'     => 0,
					'max'     => 1000,
					'step'    => 10,
				),
			),
		);

		$sections['forms'] = array(
			'label'       => 'فرم‌ها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'فیلدهای ورودی و حالت‌های اعتبارسنجی.',
			'fields'      => array(
				array(
					'key'     => 'input_height',
					'label'   => 'ارتفاع فیلد (px)',
					'type'    => 'number',
					'default' => 44,
					'min'     => 28,
					'max'     => 72,
					'step'    => 2,
				),
				array(
					'key'     => 'input_radius',
					'label'   => 'گردی فیلد (px)',
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 40,
					'step'    => 1,
				),
				array(
					'key'     => 'focus_ring',
					'label'   => 'حلقه فوکوس',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'label_position',
					'label'   => 'موقعیت برچسب',
					'type'    => 'select',
					'default' => 'top',
					'options' => array(
						'top'     => 'بالای فیلد',
						'inside'  => 'داخل فیلد',
						'hidden'  => 'بدون برچسب',
					),
				),
			),
		);

		// ---------------------------------------------- Components (6).
		$sections['cards'] = array(
			'label'       => 'کارت‌ها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'کارت محصول و کارت محتوا.',
			'fields'      => array(
				array(
					'key'     => 'padding',
					'label'   => 'پدینگ (px)',
					'type'    => 'number',
					'default' => 16,
					'min'     => 0,
					'max'     => 64,
					'step'    => 2,
				),
				array(
					'key'     => 'radius',
					'label'   => 'گردی (px)',
					'type'    => 'number',
					'default' => 12,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'image_ratio',
					'label'   => 'نسبت تصویر',
					'type'    => 'select',
					'default' => '1:1',
					'options' => array(
						'1:1'  => '1:1',
						'4:3'  => '4:3',
						'3:4'  => '3:4',
						'16:9' => '16:9',
					),
				),
				array(
					'key'     => 'hover_lift',
					'label'   => 'بالا آمدن در هاور',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['tables'] = array(
			'label'       => 'جدول‌ها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'جدول‌های سبد خرید و لیست‌ها.',
			'fields'      => array(
				array(
					'key'     => 'zebra',
					'label'   => 'ردیف‌های یک‌درمیان',
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'cell_padding',
					'label'   => 'پدینگ سلول (px)',
					'type'    => 'number',
					'default' => 12,
					'min'     => 4,
					'max'     => 40,
					'step'    => 2,
				),
				array(
					'key'     => 'sticky_header',
					'label'   => 'هدر چسبان',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['badges'] = array(
			'label'       => 'نشان‌ها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'نشان تخفیف، موجودی و برچسب‌ها.',
			'fields'      => array(
				array(
					'key'     => 'discount_color',
					'label'   => 'رنگ تخفیف',
					'type'    => 'color',
					'default' => '#dc2626',
				),
				array(
					'key'     => 'new_color',
					'label'   => 'رنگ جدید',
					'type'    => 'color',
					'default' => '#16a34a',
				),
				array(
					'key'     => 'radius',
					'label'   => 'گردی (px)',
					'type'    => 'number',
					'default' => 999,
					'min'     => 0,
					'max'     => 999,
					'step'    => 1,
				),
				array(
					'key'     => 'uppercase',
					'label'   => 'حروف بزرگ',
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['alerts'] = array(
			'label'       => 'هشدارها',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'پیام‌های سیستمی و notice ووکامرس.',
			'fields'      => array(
				array(
					'key'     => 'position',
					'label'   => 'موقعیت',
					'type'    => 'select',
					'default' => 'top',
					'options' => array(
						'top'    => 'بالای صفحه',
						'bottom' => 'پایین صفحه',
						'inline' => 'در محل',
					),
				),
				array(
					'key'     => 'dismissible',
					'label'   => 'قابل بستن',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'timeout',
					'label'   => 'زمان بستن خودکار (ثانیه)',
					'type'    => 'number',
					'default' => 6,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
			),
		);

		$sections['overlays'] = array(
			'label'       => 'Modal و Drawer',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'پنجره‌های شناور، سبد خرید، ابزارک Tooltip.',
			'fields'      => array(
				array(
					'key'     => 'drawer_side',
					'label'   => 'سمت Drawer',
					'type'    => 'select',
					'default' => 'right',
					'options' => array(
						'right' => 'راست (RTL)',
						'left'  => 'چپ',
					),
				),
				array(
					'key'     => 'backdrop_opacity',
					'label'   => 'شفافیت پس‌زمینه (0-1)',
					'type'    => 'number',
					'default' => 0.5,
					'min'     => 0,
					'max'     => 1,
					'step'    => 0.05,
				),
				array(
					'key'     => 'tooltip_theme',
					'label'   => 'تم Tooltip',
					'type'    => 'select',
					'default' => 'dark',
					'options' => array(
						'dark'  => 'Dark',
						'light' => 'Light',
					),
				),
			),
		);

		$sections['pagination'] = array(
			'label'       => 'صفحه‌بندی',
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => 'صفحه‌بندی آرشیو و لیست محصولات.',
			'fields'      => array(
				array(
					'key'     => 'style',
					'label'   => 'سبک',
					'type'    => 'select',
					'default' => 'numbers',
					'options' => array(
						'numbers'    => 'شماره‌ها',
						'prev_next'  => 'قبلی/بعدی',
						'load_more'  => 'بارگذاری بیشتر (AJAX)',
					),
				),
				array(
					'key'     => 'per_page',
					'label'   => 'تعداد در صفحه',
					'type'    => 'number',
					'default' => 12,
					'min'     => 1,
					'max'     => 100,
					'step'    => 1,
				),
				array(
					'key'     => 'ajax',
					'label'   => 'بارگذاری AJAX',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		// ------------------------------------------------ Delivery (6).
		$sections['assets_images'] = array(
			'label'       => 'تصاویر',
			'group'       => 'assets',
			'cluster'     => 'library',
			'description' => 'مدیریت تصاویر پلتفرم.',
			'fields'      => array(
				array(
					'key'     => 'webp',
					'label'   => 'تبدیل به WebP',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => 'حداکثر حجم (MB)',
					'type'    => 'number',
					'default' => 8,
					'min'     => 1,
					'max'     => 40,
					'step'    => 1,
				),
				array(
					'key'     => 'lazy',
					'label'   => 'lazy-load',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['assets_svg'] = array(
			'label'       => 'SVG',
			'group'       => 'assets',
			'cluster'     => 'library',
			'description' => 'آپلود و پاک‌سازی SVG.',
			'fields'      => array(
				array(
					'key'     => 'allow_upload',
					'label'   => 'اجازه آپلود SVG',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'sanitize',
					'label'   => 'پاک‌سازی اجباری',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'strip_ids',
					'label'   => 'حذف id/class داخلی',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'inline',
					'label'   => 'رندر inline برای رنگ‌پذیری',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['assets_custom'] = array(
			'label'       => 'CSS/JS سفارشی',
			'group'       => 'assets',
			'cluster'     => 'library',
			'description' => 'کدهای اختصاصی پلتفرم.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'css',
					'label'   => 'CSS',
					'type'    => 'code',
					'default' => '',
				),
				array(
					'key'     => 'js',
					'label'   => 'JS',
					'type'    => 'code',
					'default' => '',
				),
				array(
					'key'     => 'location',
					'label'   => 'محل بارگذاری JS',
					'type'    => 'select',
					'default' => 'footer',
					'options' => array(
						'footer' => 'فوتر',
						'head'   => 'هدر',
					),
				),
			),
		);

		$sections['elementor'] = array(
			'label'       => 'المنتور',
			'group'       => 'elementor',
			'cluster'     => 'integration',
			'description' => 'سطوح یکپارچه‌سازی توکن‌ها با المنتور.',
			'fields'      => array(
				array(
					'key'     => 'integration_level',
					'label'   => 'سطح یکپارچه‌سازی',
					'type'    => 'select',
					'default' => 'variables',
					'options' => array(
						'variables' => 'سطح ۱ — CSS Variables (پیشنهادی)',
						'picker'    => 'سطح ۲ — Token Picker در ابزارک‌ها',
						'kit'       => 'سطح ۳ — همگام‌سازی با Global Kit',
					),
				),
				array(
					'key'     => 'sync_kit',
					'label'   => 'همگام‌سازی با Global Kit',
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'kit_backup',
					'label'   => 'تهیه نسخه پشتیبان قبل از همگام‌سازی',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'hide_woo_widgets',
					'label'   => 'پنهان‌سازی ابزارک‌های پیش‌فرض ووکامرس در المنتور',
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['performance'] = array(
			'label'       => 'عملکرد',
			'group'       => 'system',
			'cluster'     => 'delivery',
			'description' => 'بهینه‌سازی بارگذاری دارایی‌ها.',
			'fields'      => array(
				array(
					'key'     => 'preload_fonts',
					'label'   => 'preload فونت‌های حیاتی',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'font_display_swap',
					'label'   => 'اجبار font-display: swap',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'inline_tokens',
					'label'   => 'تزریق inline توکن‌ها (حذف درخواست اضافه)',
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'minify',
					'label'   => 'فشرده‌سازی CSS تولیدشده',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'cache_bust',
					'label'   => 'نسخه‌گذاری با hash محتوا',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'defer_js',
					'label'   => 'defer اسکریپت‌های پلتفرم',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['rest_api'] = array(
			'label'       => 'REST API',
			'group'       => 'system',
			'cluster'     => 'tools',
			'description' => 'دسترسی برنامه‌نویسی‌شده به تنظیمات و دارایی‌ها.',
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => 'فعال‌سازی REST',
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'namespace',
					'label'   => 'namespace',
					'type'    => 'text',
					'default' => 'dashwoo/v1',
				),
				array(
					'key'     => 'require_auth_write',
					'label'   => 'اجبار احراز هویت برای نوشتن',
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		// The account endpoint fields are built from WooCommerce's live menu, so the
		// section exists here and its fields are filled in from that source.
		if ( isset( $sections['account_endpoints'] ) ) {
			$sections['account_endpoints']['fields'] = \DashWoo\Account\Endpoints::instance()->fields();
		}

		// The `features` section is built from the capability gate (live state).
		if ( isset( $sections['features'] ) ) {
			$sections['features']['fields'] = \DashWoo\Capabilities\Capabilities::instance()->fields();
		}

		$sorted = array();

		foreach ( self::order() as $key ) {
			if ( isset( $sections[ $key ] ) ) {
				$sorted[ $key ] = $sections[ $key ];
			}
		}

		foreach ( $sections as $key => $definition ) {
			if ( ! isset( $sorted[ $key ] ) ) {
				$sorted[ $key ] = $definition;
			}
		}

		return $sorted;
	}

	/**
	 * Default values, shaped exactly like the option payload.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function defaults() {
		$defaults = array();

		foreach ( self::all() as $key => $section ) {
			$defaults[ $key ] = array();
			foreach ( (array) ( $section['fields'] ?? array() ) as $field ) {
				$defaults[ $key ][ $field['key'] ] = $field['default'];
			}
		}

		return $defaults;
	}

	/**
	 * Field ids per section.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function field_keys() {
		$map = array();

		foreach ( self::all() as $key => $section ) {
			$map[ $key ] = array();
			foreach ( (array) ( $section['fields'] ?? array() ) as $field ) {
				$map[ $key ][] = $field['key'];
			}
		}

		return $map;
	}

	/**
	 * Total number of fields.
	 *
	 * @return int
	 */
	public static function count_fields() {
		$total = 0;

		foreach ( self::all() as $section ) {
			$total += count( (array) ( $section['fields'] ?? array() ) );
		}

		return $total;
	}

	/**
	 * Section keys in navigation order (grouped).
	 *
	 * @return array<string,array<int,string>>
	 */
	/**
	 * Sections of a group, nested per cluster: group => cluster => [section keys].
	 *
	 * @return array<string,array<string,array<int,string>>>
	 */
	public static function by_cluster() {
		$out = array();

		foreach ( self::all() as $key => $section ) {
			$group   = isset( $section['group'] ) ? $section['group'] : 'system';
			$cluster = isset( $section['cluster'] ) ? $section['cluster'] : 'basic';

			$out[ $group ][ $cluster ][] = $key;
		}

		return $out;
	}

	/**
	 * Navigation tree used by the admin: group => label/clusters/sections.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function tree() {
		$groups   = self::groups();
		$clusters = self::clusters();
		$sections = self::all();
		$nested   = self::by_cluster();
		$out      = array();

		foreach ( $groups as $group => $meta ) {
			$out[ $group ] = array(
				'label'    => $meta['label'],
				'icon'     => isset( $meta['icon'] ) ? $meta['icon'] : 'circle',
				'slug'     => isset( $meta['slug'] ) ? $meta['slug'] : '',
				'page'     => isset( $meta['page'] ) ? $meta['page'] : '',
				'clusters' => array(),
			);

			foreach ( isset( $clusters[ $group ] ) ? $clusters[ $group ] : array( 'basic' => 'عمومی' ) as $cluster => $label ) {
				$keys = isset( $nested[ $group ][ $cluster ] ) ? $nested[ $group ][ $cluster ] : array();

				$out[ $group ]['clusters'][] = array(
					'key'      => $cluster,
					'label'    => $label,
					'sections' => array_map(
						static function ( $key ) use ( $sections ) {
							return array(
								'key'   => $key,
								'label' => isset( $sections[ $key ]['label'] ) ? $sections[ $key ]['label'] : $key,
								'icon'  => isset( $sections[ $key ]['icon'] ) ? $sections[ $key ]['icon'] : '',
							);
						},
						$keys
					),
				);
			}
		}

		return $out;
	}

	/**
	 * The group a section belongs to.
	 *
	 * @param string $section Section key.
	 * @return string
	 */
	public static function group_of( $section ) {
		$all = self::all();

		return isset( $all[ $section ]['group'] ) ? $all[ $section ]['group'] : 'system';
	}

	/**
	 * The cluster a section belongs to.
	 *
	 * @param string $section Section key.
	 * @return string
	 */
	public static function cluster_of( $section ) {
		$all = self::all();

		return isset( $all[ $section ]['cluster'] ) ? $all[ $section ]['cluster'] : 'basic';
	}

	public static function by_group() {
		$out = array();

		foreach ( array_keys( self::groups() ) as $group ) {
			$out[ $group ] = array();
		}

		foreach ( self::all() as $key => $section ) {
			$group = isset( $section['group'] ) ? $section['group'] : 'core';
			if ( ! isset( $out[ $group ] ) ) {
				$out[ $group ] = array();
			}
			$out[ $group ][] = $key;
		}

		return $out;
	}
}
