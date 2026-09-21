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
				'label' => __( 'Dashboard', 'dashwoo' ),
				'icon'  => 'dashboard',
				'slug'  => 'dashwoo',
			),
			'design'    => array(
				'label' => __( 'Design', 'dashwoo' ),
				'icon'  => 'palette',
				'slug'  => 'colors',
			),
			'account'   => array(
				'label' => __( 'My Account', 'dashwoo' ),
				'icon'  => 'account_circle',
				'slug'  => 'account_layout',
			),
			'fonts'     => array(
				'label' => __( 'Fonts and icons', 'dashwoo' ),
				'icon'  => 'text_fields',
				'slug'  => 'fonts_google',
			),
			'assets'    => array(
				'label' => __( 'Assets', 'dashwoo' ),
				'icon'  => 'folder_open',
				'slug'  => 'assets_images',
				'page'  => 'dashwoo-assets',
			),
			'elementor' => array(
				'label' => __( 'Elementor', 'dashwoo' ),
				'icon'  => 'layout',
				'slug'  => 'elementor',
			),
			'system'    => array(
				'label' => __( 'System', 'dashwoo' ),
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
			'home'      => array( 'home' => __( 'Getting started', 'dashwoo' ) ),
			'design'    => array(
				'tokens'     => __( 'Design foundation', 'dashwoo' ),
				'components' => __( 'Components', 'dashwoo' ),
			),
			'account'   => array(
				'layout'    => __( 'Account layout', 'dashwoo' ),
				'forms'     => __( 'Forms and pages', 'dashwoo' ),
			),
			'fonts'     => array(
				'families' => __( 'Font families', 'dashwoo' ),
				'icons'    => __( 'Icons', 'dashwoo' ),
			),
			'assets'    => array( 'library' => __( 'Library', 'dashwoo' ) ),
			'elementor' => array( 'integration' => __( 'Integrations', 'dashwoo' ) ),
			'system'    => array(
				'basic'      => __( 'General', 'dashwoo' ),
				'delivery'   => __( 'Performance and delivery', 'dashwoo' ),
				'capability' => __( 'Capabilities', 'dashwoo' ),
				'tools'      => __( 'API and logs', 'dashwoo' ),
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
			'label'       => __( 'Dashboard', 'dashwoo' ),
			'group'       => 'home',
			'cluster'     => 'home',
			'capability'  => 'manage_options',
			'description' => __( 'An overview of the system status, the assets and the notices.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'widgets',
					'label'   => __( 'Show the active dashboard widgets', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'notice',
					'label'   => __( 'Dashboard notice text', 'dashwoo' ),
					'type'    => 'text',
					'default' => '',
				),
			),
		);

		// ------------------------------------- Capabilities / features (2).
		$sections['features'] = array(
			'label'       => __( 'Features', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'capability',
			'description' => __( 'Every optional DashWoo feature. When the host is missing what a feature needs, the feature stays off and switches itself on as soon as the capability appears.', 'dashwoo' ),
			'fields'      => \DashWoo\Capabilities\Capabilities::instance()->fields(),
		);

		// ------------------------------------------------------- Core (4).
		$sections['general'] = array(
			'label'       => __( 'General', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => __( 'Global switch, render mode, language and cache.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Enable DashWoo', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => __( 'Render mode', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'full',
					'options' => array(
						'full'          => __( 'Full Override — full control over templates', 'dashwoo' ),
						'compatibility' => __( 'Compatible — co-exist with themes and plugins', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'elementor_enabled',
					'label'   => __( 'Elementor integration', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'         => 'language',
					'label'       => __( 'Platform language', 'dashwoo' ),
					'type'        => 'select',
					'default'     => \DashWoo\I18n\Language::SITE,
					'options'     => \DashWoo\I18n\Language::choices(),
					'description' => __( 'DashWoo is written in English and ships a complete Persian translation. “Follow the site language” uses whatever the WordPress install is set to, so a Persian site shows Persian; pick a language here to force one for DashWoo alone.', 'dashwoo' ),
				),
				array(
					'key'         => 'rtl',
					'label'       => __( 'RTL support', 'dashwoo' ),
					'type'        => 'toggle',
					'default'     => true,
					'description' => __( 'Keeps the layout right-to-left for Persian and Arabic. It follows the site direction as well, so a Persian WordPress gets RTL even when this is off.', 'dashwoo' ),
				),
				array(
					'key'     => 'cache_ttl',
					'label'   => __( 'Cache lifetime (seconds)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 3600,
					'min'     => 60,
					'max'     => 86400,
					'step'    => 60,
				),
				array(
					'key'     => 'log_level',
					'label'   => __( 'Log level', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'warning',
					'options' => array(
						'debug'   => __('Debug', 'dashwoo'),
						'info'    => __('Info', 'dashwoo'),
						'warning' => __('Warning', 'dashwoo'),
						'error'   => __('Error', 'dashwoo'),
					),
				),
				array(
					'key'     => 'remove_data_on_uninstall',
					'label'   => __( 'Delete data when the plugin is deleted', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['compatibility'] = array(
			'label'       => __( 'Version compatibility', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => __( 'The environment check result and the version floors.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'min_php',
					'label'   => __( 'Minimum PHP', 'dashwoo' ),
					'type'    => 'text',
					'default' => '8.0',
				),
				array(
					'key'     => 'min_wp',
					'label'   => __( 'Minimum WordPress', 'dashwoo' ),
					'type'    => 'text',
					'default' => '6.4',
				),
				array(
					'key'     => 'min_wc',
					'label'   => __( 'Minimum WooCommerce', 'dashwoo' ),
					'type'    => 'text',
					'default' => '8.5',
				),
				array(
					'key'     => 'min_elementor',
					'label'   => __( 'Minimum Elementor', 'dashwoo' ),
					'type'    => 'text',
					'default' => '3.24',
				),
				array(
					'key'     => 'auto_fallback',
					'label'   => __( 'Switch to Compatibility Mode automatically', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'recheck_interval',
					'label'   => __( 'Re-check interval (hours)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 1,
					'max'     => 168,
					'step'    => 1,
				),
			),
		);

		$sections['updates'] = array(
			'label'       => __( 'Updates', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'basic',
			'capability'  => 'manage_options',
			'description' => __( 'Update channel and asset metadata handling.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'channel',
					'label'   => __( 'Channel', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'stable',
					'options' => array(
						'stable' => __('Stable', 'dashwoo'),
						'beta'   => __('Beta', 'dashwoo'),
					),
				),
				array(
					'key'     => 'auto_update_assets',
					'label'   => __( 'Automatic asset updates', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'check_interval',
					'label'   => __( 'Check interval (hours)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 24,
					'min'     => 1,
					'max'     => 720,
					'step'    => 1,
				),
			),
		);

		$sections['logs'] = array(
			'label'       => __( 'Logs', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'tools',
			'capability'  => 'manage_options',
			'description' => __( 'The latest system events (up to 200 rows).', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Event logging', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_rows',
					'label'   => __( 'Maximum rows', 'dashwoo' ),
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
			'label'       => __( 'Typography', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'Base fonts and a responsive scale with full Persian support.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'primary_font',
					'label'   => __( 'Primary font', 'dashwoo' ),
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'heading_font',
					'label'   => __( 'Heading font', 'dashwoo' ),
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'body_font',
					'label'   => __( 'Body font', 'dashwoo' ),
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'button_font',
					'label'   => __( 'Button font', 'dashwoo' ),
					'type'    => 'font',
					'default' => 'Vazirmatn',
				),
				array(
					'key'     => 'weights',
					'label'   => __( 'Active weights', 'dashwoo' ),
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
					'label'   => __( 'Base size — desktop (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 16,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'base_size_tablet',
					'label'   => __( 'Base size — tablet (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 15,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'base_size_mobile',
					'label'   => __( 'Base size — mobile (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 14,
					'min'     => 10,
					'max'     => 32,
					'step'    => 1,
				),
				array(
					'key'     => 'line_height',
					'label'   => __( 'Line height', 'dashwoo' ),
					'type'    => 'number',
					'default' => 1.75,
					'min'     => 1,
					'max'     => 3,
					'step'    => 0.05,
				),
				array(
					'key'     => 'letter_spacing',
					'label'   => __( 'Letter spacing (em)', 'dashwoo' ),
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
			'label'       => __( 'Layout and sections', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => __( 'The structure of the My Account page: which sections show, in what order and in what layout.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'My Account pack enabled', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'Turning this off removes the DashWoo account styles and widgets and your theme\'s default look stays.', 'dashwoo' ),
				),
				array(
					'key'     => 'layout',
					'label'   => __( 'Default layout', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'sidebar',
					'options' => array(
						'sidebar'          => __( 'Side menu (two columns)', 'dashwoo' ),
						'top'              => __( 'Top menu (full width)', 'dashwoo' ),
						'cards'            => __( 'Shortcut cards', 'dashwoo' ),
						'cards_with_menu'  => __( 'Cards + side menu', 'dashwoo' ),
					),
					'hint'    => __( 'This value is used by the “DashWoo built layout” and by the [dashwoo_account] shortcut; inside Elementor every widget stands on its own.', 'dashwoo' ),
				),
				array(
					'key'     => 'cards_columns',
					'label'   => __( 'Number of shortcut card columns', 'dashwoo' ),
					'type'    => 'number',
					'default' => 3,
					'min'     => 1,
					'max'     => 4,
					'step'    => 1,
				),
				array(
					'key'     => 'show_icons',
					'label'   => __( 'Section icons', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_counts',
					'label'   => __( 'Order/download counter', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
					'hint'    => __( 'Only for orders and downloads, which are counted through WooCommerce\'s public API.', 'dashwoo' ),
				),
				array(
					'key'     => 'sticky_nav',
					'label'   => __( 'Sticky menu while scrolling', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_breadcrumb',
					'label'   => __( 'Breadcrumb', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'Show “My Account ← section” above the content of the inner sections.', 'dashwoo' ),
				),
			),
		);

		$sections['account_endpoints'] = array(
			'label'       => __( 'Account sections', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => __( 'For every WooCommerce endpoint: visibility, label, icon and order. The list is read from WooCommerce itself, so any section an other plugin adds shows up here too.', 'dashwoo' ),
			'fields'      => array(),
		);

		$sections['account_templates'] = array(
			'label'       => __( 'Templates', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => __( 'Pick a template for every account section (list, table, timeline, cards…). Any template can be overridden by a file in your theme: wp-content/themes/<theme>/dashwoo/account/orders--table.php', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Enable template selection', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'theme_folder',
					'label'   => __( 'Templates folder in the theme', 'dashwoo' ),
					'type'    => 'text',
					'default' => 'dashwoo/account',
					'hint'    => __( 'Your custom templates are read from here (relative to the theme folder).', 'dashwoo' ),
				),
				array(
					'key'     => 'nav',
					'label'   => __( 'Account menu template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'menu',
					'options' => Templates::section_options( 'nav', true ),
				),
				array(
					'key'     => 'dashboard',
					'label'   => __( 'Dashboard template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'hero-cards',
					'options' => Templates::section_options( 'dashboard', true ),
				),
				array(
					'key'     => 'orders',
					'label'   => __( 'Orders list template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'cards',
					'options' => Templates::section_options( 'orders', true ),
				),
				array(
					'key'     => 'order',
					'label'   => __( 'Single order template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'summary',
					'options' => Templates::section_options( 'order', true ),
				),
				array(
					'key'     => 'downloads',
					'label'   => __( 'Downloads template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'list',
					'options' => Templates::section_options( 'downloads', true ),
				),
				array(
					'key'     => 'addresses',
					'label'   => __( 'Addresses template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'cards',
					'options' => Templates::section_options( 'addresses', true ),
				),
				array(
					'key'     => 'payment',
					'label'   => __( 'Payment methods template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'payment', true ),
				),
				array(
					'key'     => 'details',
					'label'   => __( 'Account details template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'details', true ),
				),
				array(
					'key'     => 'profile',
					'label'   => __( 'Profile card template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'card',
					'options' => Templates::section_options( 'profile', true ),
				),
				array(
					'key'     => 'forms',
					'label'   => __( 'Forms template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'grid',
					'options' => Templates::section_options( 'forms', true ),
				),
				array(
					'key'     => 'logout',
					'label'   => __( 'Logout template', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'button',
					'options' => Templates::section_options( 'logout', true ),
				),
			),
		);

		$sections['account_panel'] = array(
			'label'       => __( 'Two-column panel (menu + content)', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => __( 'A menu card next to a bigger content card; clicking a section (orders, downloads…) swaps the content of the second card - without reloading the page. Order details open inside that same card too.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Show the two-column panel in the account area', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => __( 'Display mode', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'swap',
					'options' => array(
						'swap'    => __( 'Swap the card content (recommended)', 'dashwoo' ),
						'modal'   => __( 'Floating window for details', 'dashwoo' ),
						'stacked' => __( 'Both cards, no automatic swapping', 'dashwoo' ),
					),
					'hint'    => __( 'In floating mode the order details open in a window on top of the page.', 'dashwoo' ),
				),
				array(
					'key'     => 'ajax',
					'label'   => __( 'Swap without reloading the page (AJAX)', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'When off, the links open the page the classic way (which still works).', 'dashwoo' ),
				),
				array(
					'key'     => 'source',
					'label'   => __( 'Section content source', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'auto',
					'options' => array(
						'auto'    => __( 'Automatic (WooCommerce, and the DashWoo template when it has none)', 'dashwoo' ),
						'native'  => __( 'WooCommerce only', 'dashwoo' ),
						'dashwoo' => __( 'DashWoo templates only', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'default_view',
					'label'   => __( 'Default section', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'dashboard',
					'options' => array(
						'dashboard'       => __( 'Dashboard', 'dashwoo' ),
						'orders'          => __( 'Orders', 'dashwoo' ),
						'downloads'       => __( 'Downloads', 'dashwoo' ),
						'edit-address'    => __( 'Addresses', 'dashwoo' ),
						'payment-methods' => __( 'Payment methods', 'dashwoo' ),
						'edit-account'    => __( 'Account details', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'aside',
					'label'   => __( 'Menu card position', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'right',
					'options' => array(
						'right' => __( 'Right (natural for RTL)', 'dashwoo' ),
						'left'  => __( 'Left', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'aside_width',
					'label'   => __( 'Menu card width (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 300,
					'min'     => 140,
					'max'     => 520,
					'step'    => 4,
				),
				array(
					'key'     => 'gap',
					'label'   => __( 'Gap between the cards (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 24,
					'min'     => 0,
					'max'     => 80,
					'step'    => 2,
				),
				array(
					'key'     => 'sticky',
					'label'   => __( 'Sticky menu card while scrolling', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'titles',
					'label'   => __( 'Show the section title above the content card', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'back_label',
					'label'   => __( 'Back button label (from order details)', 'dashwoo' ),
					'type'    => 'text',
					'default' => __( 'Back to orders', 'dashwoo' ),
				),
				array(
					'key'     => 'loading_text',
					'label'   => __( 'Loading text', 'dashwoo' ),
					'type'    => 'text',
					'default' => __( 'Loading…', 'dashwoo' ),
				),
				array(
					'key'     => 'sync_url',
					'label'   => __( 'Update the browser URL with every section', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'With this on, the browser\'s Back button also works between sections.', 'dashwoo' ),
				),
				array(
					'key'     => 'mobile',
					'label'   => __( 'Mobile behaviour', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'stack',
					'options' => array(
						'stack' => __( 'Stack the two cards', 'dashwoo' ),
						'tabs'  => __( 'Horizontal tabs for the menu', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'icons',
					'label'   => __( 'Icons in the menu', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'counts',
					'label'   => __( 'Counters in the menu', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'per_page',
					'label'   => __( 'Items per section', 'dashwoo' ),
					'type'    => 'number',
					'default' => 10,
					'min'     => 1,
					'max'     => 50,
					'step'    => 1,
				),
			),
		);

		$sections['account_design'] = array(
			'label'       => __( 'Look and colors', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'layout',
			'description' => __( 'Accent colour, corner radius and the size of the account cards. The colours are injected as CSS variables, so they match the DashWoo tokens.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Apply the DashWoo style to the account page', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'accent',
					'label'   => __( 'Accent colour', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#2563eb',
					'hint'    => __( 'Links, the active card and the buttons use this colour.', 'dashwoo' ),
				),
				array(
					'key'     => 'accent_hover',
					'label'   => __( 'Accent colour (hover)', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#1d4ed8',
				),
				array(
					'key'     => 'radius',
					'label'   => __( 'Corner radius (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 18,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'avatar_size',
					'label'   => __( 'Avatar size (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 96,
					'min'     => 32,
					'max'     => 200,
					'step'    => 4,
				),
				array(
					'key'     => 'nav_width',
					'label'   => __( 'Side menu width (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 264,
					'min'     => 160,
					'max'     => 480,
					'step'    => 8,
				),
				array(
					'key'     => 'stage_background',
					'label'   => __( 'Account area background', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#f7f8fb',
				),
				array(
					'key'     => 'auto_css',
					'label'   => __( 'Automatic DashWoo CSS', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'Turn it off and DashWoo puts no styling on the account elements at all: the design is entirely yours (Elementor/theme).', 'dashwoo' ),
				),
				array(
					'key'     => 'css_reset',
					'label'   => __( 'Neutralising reset (while automatic CSS is off)', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'Only removes the browser defaults (list, margin, box-sizing) so you start from a clean slate; it adds nothing that looks like DashWoo.', 'dashwoo' ),
				),
				array(
					'key'     => 'inline_vars',
					'label'   => __( 'Inject the colour and size variables', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'CSS variables (accent colour, radius, menu width) for your own styling. If you do not want any of them, switch this off as well.', 'dashwoo' ),
				),
				array(
					'key'     => 'gap',
					'label'   => __( 'Default block spacing (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 0,
					'min'     => 0,
					'max'     => 80,
					'step'    => 2,
					'hint'    => __( 'Zero = the DashWoo default spacing.', 'dashwoo' ),
				),
				array(
					'key'     => 'logo_in_hero',
					'label'   => __( 'Show the DashWoo mark in the welcome card', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
					'hint'    => __( 'Matches the DashWoo brand identity (the SVG files inside the plugin).', 'dashwoo' ),
				),
				array(
					'key'     => 'logo_size',
					'label'   => __( 'Mark size (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 28,
					'min'     => 16,
					'max'     => 96,
					'step'    => 2,
				),
			),
		);

		$sections['account_forms'] = array(
			'label'       => __( 'Forms and behaviour', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'forms',
			'description' => __( 'How the forms behave and what replaces WooCommerce\'s default templates.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'profile_fields',
					'label'   => __( 'Profile form fields', 'dashwoo' ),
					'type'    => 'multi_select',
					'default' => array( 'display_name' ),
					'options' => array(
						'display_name'  => __( 'Display name (savable)', 'dashwoo' ),
						'first_name'    => __( 'First name', 'dashwoo' ),
						'last_name'     => __( 'Last name', 'dashwoo' ),
						'billing_email' => __( 'Billing email', 'dashwoo' ),
						'billing_phone' => __( 'Phone', 'dashwoo' ),
					),
					'hint'    => __( 'Only the display name is saved by DashWoo; the other fields are display-only and their editing goes to the WooCommerce form.', 'dashwoo' ),
				),
				array(
					'key'     => 'inline_validation',
					'label'   => __( 'Inline validation (HTML5)', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'Required fields render with `required` and the right input type so the browser checks them itself.', 'dashwoo' ),
				),
				array(
					'key'     => 'redirect_after_login',
					'label'   => __( 'Back to the account page after signing in', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'account',
					'options' => array(
						'account' => __( 'The My Account page', 'dashwoo' ),
						'current' => __( 'The page the visitor was on', 'dashwoo' ),
						'default' => __( 'WordPress/WooCommerce default behaviour', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'guest_message',
					'label'   => __( 'Guest message', 'dashwoo' ),
					'type'    => 'textarea',
					'default' => __( 'Sign in to see your orders, downloads and account details.', 'dashwoo' ),
					'hint'    => __( 'Customers who are not signed in see this message and the sign-in button instead of an empty page.', 'dashwoo' ),
				),
				array(
					'key'     => 'endpoint_titles',
					'label'   => __( 'Show a title above every section', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['account_source'] = array(
			'label'       => __( 'Template source', 'dashwoo' ),
			'group'       => 'account',
			'cluster'     => 'forms',
			'description' => __( 'Who builds the account page: WooCommerce templates, DashWoo widgets, or a mix of both. Every mode is reversible and no template file is ever overwritten.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Source manager enabled', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'mode',
					'label'   => __( 'Mode', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'default',
					'options' => array(
						'default'   => __( 'WooCommerce (default): everything exactly like WooCommerce', 'dashwoo' ),
						'hybrid'    => __( 'Hybrid: DashWoo navigation + WooCommerce content', 'dashwoo' ),
						'elementor' => __( 'Elementor only: navigation and content from DashWoo widgets', 'dashwoo' ),
					),
					'hint'    => __( 'In “Elementor only”, only pages that were actually built with Elementor step aside from the default template; if the page is not an Elementor page yet, DashWoo does not overstep and the WooCommerce template stays.', 'dashwoo' ),
				),
				array(
					'key'     => 'generated_layout',
					'label'   => __( 'Build the DashWoo layout automatically', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'When the page has no DashWoo widget yet, the ready-made layout (dashboard + menu + cards) is added to the page content so the page is never blank.', 'dashwoo' ),
				),
				array(
					'key'     => 'shortcode',
					'label'   => __( '[dashwoo_account] shortcut', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
					'hint'    => __( 'For use in any page or builder; it prints the same layout inside the page.', 'dashwoo' ),
				),
				array(
					'key'     => 'menu_priority',
					'label'   => __( 'Menu filter priority', 'dashwoo' ),
					'type'    => 'number',
					'default' => 20,
					'min'     => 1,
					'max'     => 99,
					'step'    => 1,
					'hint'    => __( 'The priority of DashWoo\'s hooks on the account menu (smaller = before WooCommerce, larger = after it).', 'dashwoo' ),
				),
			),
		);

		$sections['fonts_google'] = array(
			'label'       => __( 'Google Fonts', 'dashwoo' ),
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => __( 'Search, download and self-host Google fonts.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'allow_download',
					'label'   => __( 'Allow downloading from Google Fonts', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => __( 'Maximum size per file (MB)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 5,
					'min'     => 1,
					'max'     => 30,
					'step'    => 1,
				),
				array(
					'key'     => 'subsets',
					'label'   => __( 'Subsets', 'dashwoo' ),
					'type'    => 'multi_select',
					'default' => array( 'arabic', 'latin', 'latin-ext' ),
					'options' => array(
						'arabic'    => __('Arabic', 'dashwoo'),
						'latin'     => __('Latin', 'dashwoo'),
						'latin-ext' => __('Latin Extended', 'dashwoo'),
					),
				),
				array(
					'key'     => 'unicode_range',
					'label'   => __( 'Keep unicode-range', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['fonts_custom'] = array(
			'label'       => __( 'Custom font', 'dashwoo' ),
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => __( 'Upload your own font (woff2/woff/ttf).', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'allow_upload',
					'label'   => __( 'Allow font uploads', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'allowed_ext',
					'label'   => __( 'Allowed extensions', 'dashwoo' ),
					'type'    => 'text',
					'default' => 'woff2,woff,ttf,otf',
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => __( 'Maximum size (MB)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 10,
					'min'     => 1,
					'max'     => 50,
					'step'    => 1,
				),
			),
		);

		$sections['assets_fonts'] = array(
			'label'       => __( 'Font management', 'dashwoo' ),
			'group'       => 'fonts',
			'cluster'     => 'families',
			'description' => __( 'The installed fonts, the defaults and the Elementor sync.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'sync_elementor',
					'label'   => __( 'Add the fonts to the Elementor list', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'preload_default',
					'label'   => __( 'Preload the default font', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'per_page',
					'label'   => __( 'Items per page', 'dashwoo' ),
					'type'    => 'number',
					'default' => 20,
					'min'     => 5,
					'max'     => 200,
					'step'    => 5,
				),
			),
		);

		$sections['icons'] = array(
			'label'       => __( 'Icons', 'dashwoo' ),
			'group'       => 'fonts',
			'cluster'     => 'icons',
			'description' => __( 'Material Symbols (variable font) and classic Material Icons.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'provider',
					'label'   => __( 'Default provider', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'material-symbols',
					'options' => array(
						'material-symbols' => __('Material Symbols', 'dashwoo'),
						'material-icons'   => __('Material Icons', 'dashwoo'),
						'custom-svg'       => __( 'Custom SVG', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'style',
					'label'   => __( 'Variable font style', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'outlined',
					'options' => array(
						'outlined' => __('Outlined', 'dashwoo'),
						'rounded'  => __('Rounded', 'dashwoo'),
						'sharp'    => __('Sharp', 'dashwoo'),
					),
				),
				array(
					'key'     => 'weight',
					'label'   => __('Weight (100-700)', 'dashwoo'),
					'type'    => 'number',
					'default' => 400,
					'min'     => 100,
					'max'     => 700,
					'step'    => 100,
				),
				array(
					'key'     => 'fill',
					'label'   => __('Fill (0-1)', 'dashwoo'),
					'type'    => 'number',
					'default' => 0,
					'min'     => 0,
					'max'     => 1,
					'step'    => 1,
				),
				array(
					'key'     => 'grade',
					'label'   => __( 'Grade (-25 to 200)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 0,
					'min'     => -25,
					'max'     => 200,
					'step'    => 25,
				),
				array(
					'key'     => 'optical_size',
					'label'   => __( 'Optical Size (20 to 48)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 24,
					'min'     => 20,
					'max'     => 48,
					'step'    => 4,
				),
				array(
					'key'     => 'color',
					'label'   => __( 'Default colour', 'dashwoo' ),
					'type'    => 'color',
					'default' => 'currentColor',
				),
				array(
					'key'     => 'size',
					'label'   => __( 'Default size (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 24,
					'min'     => 12,
					'max'     => 96,
					'step'    => 2,
				),
			),
		);

		$sections['icon_widget'] = array(
			'label'       => __( 'Icon widget', 'dashwoo' ),
			'group'       => 'fonts',
			'cluster'     => 'icons',
			'description' => __( 'The default controls of the Elementor icon widget.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'show_position_control',
					'label'   => __( 'Position control (before/after the text)', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'show_variation_controls',
					'label'   => __( 'Show the Weight/Fill/Grade controls', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'default_position',
					'label'   => __( 'Default position', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'before',
					'options' => array(
						'before' => __( 'Before the text', 'dashwoo' ),
						'after'  => __( 'After the text', 'dashwoo' ),
						'only'   => __( 'Icon only', 'dashwoo' ),
					),
				),
			),
		);

		// -------------------------------------------- Design System (7).
		$sections['colors'] = array(
			'label'       => __( 'Colors', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'The base palette and the semantic colours.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'primary',
					'label'   => __('Primary', 'dashwoo'),
					'type'    => 'color',
					'default' => '#2563eb',
				),
				array(
					'key'     => 'primary_hover',
					'label'   => __('Primary Hover', 'dashwoo'),
					'type'    => 'color',
					'default' => '#1d4ed8',
				),
				array(
					'key'     => 'secondary',
					'label'   => __('Secondary', 'dashwoo'),
					'type'    => 'color',
					'default' => '#0f766e',
				),
				array(
					'key'     => 'accent',
					'label'   => __('Accent', 'dashwoo'),
					'type'    => 'color',
					'default' => '#f59e0b',
				),
				array(
					'key'     => 'text',
					'label'   => __( 'Text', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#111827',
				),
				array(
					'key'     => 'muted',
					'label'   => __( 'Muted text', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#6b7280',
				),
				array(
					'key'     => 'background',
					'label'   => __( 'Background', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#ffffff',
				),
				array(
					'key'     => 'surface',
					'label'   => __( 'Surface', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#f9fafb',
				),
				array(
					'key'     => 'border',
					'label'   => __( 'Border', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#e5e7eb',
				),
				array(
					'key'     => 'success',
					'label'   => __( 'Success', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#16a34a',
				),
				array(
					'key'     => 'warning',
					'label'   => __( 'Notice', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#d97706',
				),
				array(
					'key'     => 'danger',
					'label'   => __( 'Error', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#dc2626',
				),
			),
		);

		$sections['spacing'] = array(
			'label'       => __( 'Spacing', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'The spacing scale (px).', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'unit',
					'label'   => __( 'Unit', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'px',
					'options' => array(
						'px' => 'px',
						'rem' => 'rem',
					),
				),
				array(
					'key'     => 'scale',
					'label'   => __( 'Scale', 'dashwoo' ),
					'type'    => 'text',
					'default' => '4,8,12,16,24,32,48,64',
					'description' => __( 'Values are separated by commas.', 'dashwoo' ),
				),
			),
		);

		$sections['radius'] = array(
			'label'       => __( 'Corner radius', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'Corner rounding.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'sm',
					'label'   => __('Small', 'dashwoo'),
					'type'    => 'number',
					'default' => 4,
					'min'     => 0,
					'max'     => 40,
					'step'    => 1,
				),
				array(
					'key'     => 'md',
					'label'   => __('Medium', 'dashwoo'),
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'lg',
					'label'   => __('Large', 'dashwoo'),
					'type'    => 'number',
					'default' => 16,
					'min'     => 0,
					'max'     => 80,
					'step'    => 1,
				),
				array(
					'key'     => 'pill',
					'label'   => __('Pill', 'dashwoo'),
					'type'    => 'number',
					'default' => 999,
					'min'     => 0,
					'max'     => 999,
					'step'    => 1,
				),
			),
		);

		$sections['shadows'] = array(
			'label'       => __( 'Shadows', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'The standard shadows.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'sm',
					'label'   => __('Small', 'dashwoo'),
					'type'    => 'text',
					'default' => '0 1px 2px rgba(0,0,0,.06)',
				),
				array(
					'key'     => 'md',
					'label'   => __('Medium', 'dashwoo'),
					'type'    => 'text',
					'default' => '0 4px 12px rgba(0,0,0,.08)',
				),
				array(
					'key'     => 'lg',
					'label'   => __('Large', 'dashwoo'),
					'type'    => 'text',
					'default' => '0 12px 32px rgba(0,0,0,.12)',
				),
			),
		);

		$sections['borders'] = array(
			'label'       => __( 'Borders', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'tokens',
			'description' => __( 'Border width and style.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'width',
					'label'   => __( 'Width (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 1,
					'min'     => 0,
					'max'     => 8,
					'step'    => 1,
				),
				array(
					'key'     => 'style',
					'label'   => __( 'Style', 'dashwoo' ),
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
			'label'       => __( 'Buttons', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'The default button settings.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'padding_x',
					'label'   => __( 'Horizontal padding (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 20,
					'min'     => 4,
					'max'     => 80,
					'step'    => 2,
				),
				array(
					'key'     => 'padding_y',
					'label'   => __( 'Vertical padding (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 4,
					'max'     => 60,
					'step'    => 2,
				),
				array(
					'key'     => 'radius',
					'label'   => __( 'Radius (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'weight',
					'label'   => __( 'Font weight', 'dashwoo' ),
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
					'label'   => __( 'Transition time (ms)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 180,
					'min'     => 0,
					'max'     => 1000,
					'step'    => 10,
				),
			),
		);

		$sections['forms'] = array(
			'label'       => __( 'Forms', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'Input fields and their validation states.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'input_height',
					'label'   => __( 'Field height (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 44,
					'min'     => 28,
					'max'     => 72,
					'step'    => 2,
				),
				array(
					'key'     => 'input_radius',
					'label'   => __( 'Field radius (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 8,
					'min'     => 0,
					'max'     => 40,
					'step'    => 1,
				),
				array(
					'key'     => 'focus_ring',
					'label'   => __( 'Focus ring', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'label_position',
					'label'   => __( 'Label position', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'top',
					'options' => array(
						'top'     => __( 'Above the field', 'dashwoo' ),
						'inside'  => __( 'Inside the field', 'dashwoo' ),
						'hidden'  => __( 'No label', 'dashwoo' ),
					),
				),
			),
		);

		// ---------------------------------------------- Components (6).
		$sections['cards'] = array(
			'label'       => __( 'Cards', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'The product card and the content card.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'padding',
					'label'   => __( 'Padding (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 16,
					'min'     => 0,
					'max'     => 64,
					'step'    => 2,
				),
				array(
					'key'     => 'radius',
					'label'   => __( 'Radius (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
				array(
					'key'     => 'image_ratio',
					'label'   => __( 'Image ratio', 'dashwoo' ),
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
					'label'   => __( 'Lift on hover', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['tables'] = array(
			'label'       => __( 'Tables', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'Cart and list tables.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'zebra',
					'label'   => __( 'Striped rows', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'cell_padding',
					'label'   => __( 'Cell padding (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 4,
					'max'     => 40,
					'step'    => 2,
				),
				array(
					'key'     => 'sticky_header',
					'label'   => __( 'Sticky header', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['badges'] = array(
			'label'       => __( 'Badges', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'The sale badge, the stock badge and the labels.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'discount_color',
					'label'   => __( 'Sale colour', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#dc2626',
				),
				array(
					'key'     => 'new_color',
					'label'   => __( '“New” colour', 'dashwoo' ),
					'type'    => 'color',
					'default' => '#16a34a',
				),
				array(
					'key'     => 'radius',
					'label'   => __( 'Radius (px)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 999,
					'min'     => 0,
					'max'     => 999,
					'step'    => 1,
				),
				array(
					'key'     => 'uppercase',
					'label'   => __( 'Uppercase', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['alerts'] = array(
			'label'       => __( 'Notices', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'System messages and WooCommerce notices.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'position',
					'label'   => __( 'Position', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'top',
					'options' => array(
						'top'    => __( 'Top of the page', 'dashwoo' ),
						'bottom' => __( 'Bottom of the page', 'dashwoo' ),
						'inline' => __( 'Inline', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'dismissible',
					'label'   => __( 'Dismissible', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'timeout',
					'label'   => __( 'Auto-dismiss time (seconds)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 6,
					'min'     => 0,
					'max'     => 60,
					'step'    => 1,
				),
			),
		);

		$sections['overlays'] = array(
			'label'       => __( 'Modal and Drawer', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'Floating windows, the cart and the tooltip widget.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'drawer_side',
					'label'   => __( 'Drawer side', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'right',
					'options' => array(
						'right' => __( 'Right (RTL)', 'dashwoo' ),
						'left'  => __( 'Left', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'backdrop_opacity',
					'label'   => __( 'Background opacity (0-1)', 'dashwoo' ),
					'type'    => 'number',
					'default' => 0.5,
					'min'     => 0,
					'max'     => 1,
					'step'    => 0.05,
				),
				array(
					'key'     => 'tooltip_theme',
					'label'   => __( 'Tooltip theme', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'dark',
					'options' => array(
						'dark'  => __('Dark', 'dashwoo'),
						'light' => __('Light', 'dashwoo'),
					),
				),
			),
		);

		$sections['pagination'] = array(
			'label'       => __( 'Pagination', 'dashwoo' ),
			'group'       => 'design',
			'cluster'     => 'components',
			'description' => __( 'Archive and product list pagination.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'style',
					'label'   => __( 'Style', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'numbers',
					'options' => array(
						'numbers'    => __( 'Numbers', 'dashwoo' ),
						'prev_next'  => __( 'Previous/next', 'dashwoo' ),
						'load_more'  => __( 'Load more (AJAX)', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'per_page',
					'label'   => __( 'Items per page', 'dashwoo' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 1,
					'max'     => 100,
					'step'    => 1,
				),
				array(
					'key'     => 'ajax',
					'label'   => __( 'AJAX loading', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		// ------------------------------------------------ Delivery (6).
		$sections['assets_images'] = array(
			'label'       => __( 'Images', 'dashwoo' ),
			'group'       => 'assets',
			'cluster'     => 'library',
			'description' => __( 'Platform image management.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'webp',
					'label'   => __( 'Convert to WebP', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'max_file_mb',
					'label'   => __( 'Maximum size (MB)', 'dashwoo' ),
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
			'description' => __( 'SVG upload and sanitising.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'allow_upload',
					'label'   => __( 'Allow SVG uploads', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'sanitize',
					'label'   => __( 'Always sanitise', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'strip_ids',
					'label'   => __( 'Strip internal id/class', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'inline',
					'label'   => __( 'Render inline so it can take a colour', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['assets_custom'] = array(
			'label'       => __( 'Custom CSS/JS', 'dashwoo' ),
			'group'       => 'assets',
			'cluster'     => 'library',
			'description' => __( 'Your own platform code.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Active', 'dashwoo' ),
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
					'label'   => __( 'JS loading location', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'footer',
					'options' => array(
						'footer' => __( 'Footer', 'dashwoo' ),
						'head'   => __( 'Header', 'dashwoo' ),
					),
				),
			),
		);

		$sections['elementor'] = array(
			'label'       => __( 'Elementor', 'dashwoo' ),
			'group'       => 'elementor',
			'cluster'     => 'integration',
			'description' => __( 'The levels of token integration with Elementor.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'integration_level',
					'label'   => __( 'Integration level', 'dashwoo' ),
					'type'    => 'select',
					'default' => 'variables',
					'options' => array(
						'variables' => __( 'Level 1 — CSS Variables (recommended)', 'dashwoo' ),
						'picker'    => __( 'Level 2 — Token Picker in the widgets', 'dashwoo' ),
						'kit'       => __( 'Level 3 — Sync with the Global Kit', 'dashwoo' ),
					),
				),
				array(
					'key'     => 'sync_kit',
					'label'   => __( 'Sync with the Global Kit', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'kit_backup',
					'label'   => __( 'Back up before syncing', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'hide_woo_widgets',
					'label'   => __( 'Hide WooCommerce\'s default widgets in Elementor', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
			),
		);

		$sections['performance'] = array(
			'label'       => __( 'Performance', 'dashwoo' ),
			'group'       => 'system',
			'cluster'     => 'delivery',
			'description' => __( 'Optimise how assets load.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'preload_fonts',
					'label'   => __( 'Preload the critical fonts', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'font_display_swap',
					'label'   => __( 'Force font-display: swap', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'inline_tokens',
					'label'   => __( 'Inline the tokens (removes an extra request)', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => false,
				),
				array(
					'key'     => 'minify',
					'label'   => __( 'Minify the generated CSS', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'cache_bust',
					'label'   => __( 'Version with a content hash', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
				array(
					'key'     => 'defer_js',
					'label'   => __( 'Defer the platform scripts', 'dashwoo' ),
					'type'    => 'toggle',
					'default' => true,
				),
			),
		);

		$sections['rest_api'] = array(
			'label'       => 'REST API',
			'group'       => 'system',
			'cluster'     => 'tools',
			'description' => __( 'Programmatic access to the settings and the assets.', 'dashwoo' ),
			'fields'      => array(
				array(
					'key'     => 'enabled',
					'label'   => __( 'Enable the REST API', 'dashwoo' ),
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
					'label'   => __( 'Require authentication for writes', 'dashwoo' ),
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

			foreach ( isset( $clusters[ $group ] ) ? $clusters[ $group ] : array( 'basic' => __( 'General', 'dashwoo' ) ) as $cluster => $label ) {
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
