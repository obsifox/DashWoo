<?php
/**
 * Elementor widget: پنل دوستونهٔ حساب کاربری.
 *
 * The WooCommerce account page is a single column: the menu and the section content
 * are stacked. This widget builds what a shop actually wants - a smaller menu card
 * next to a bigger content card - and makes the second card swap its content when the
 * customer clicks a menu item (including "سفارش‌ها → یک سفارش"), with a real link
 * behind every item so the page still works without JavaScript.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Panel;

defined( 'ABSPATH' ) || exit;

/**
 * Two-pane account panel widget.
 */
class Account_Panel_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_panel';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — پنل دوستونهٔ حساب کاربری';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-columns';
	}

	/**
	 * Keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords() {
		return array_merge( parent::get_keywords(), array( 'panel', 'پنل', 'دوستونه', 'داشبورد', 'dashboard' ) );
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'panel';
	}

	/**
	 * Parts the shop owner may switch off.
	 *
	 * @return array<string,mixed>
	 */
	protected function visibility_spec() {
		return array(
			'icon'   => true,
			'badge'  => true,
			'action' => true,
		);
	}

	/**
	 * Style extras.
	 *
	 * @return array<int,string>
	 */
	protected function style_spec() {
		return array( 'nav', 'panel' );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_panel_behaviour',
			array( 'label' => 'رفتار پنل' )
		);

		$this->add_control(
			'dw_mode',
			array(
				'label'   => 'حالت باز شدن بخش‌ها',
				'type'    => 'select',
				'default' => 'swap',
				'options' => array(
					'swap'    => 'جایگزینی محتوای کارت بزرگ',
					'modal'   => 'پنجرهٔ شناور (برای جزئیات سفارش)',
					'stacked' => 'هر دو کارت بدون جابه‌جایی خودکار',
				),
			)
		);

		$this->add_control(
			'dw_ajax',
			array(
				'label'   => 'جابه‌جایی بدون بارگذاری صفحه',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_source',
			array(
				'label'   => 'منبع محتوا',
				'type'    => 'select',
				'default' => 'auto',
				'options' => array(
					'auto'    => 'خودکار (ووکامرس، در نبودِ آن قالب DashWoo)',
					'native'  => 'فقط ووکامرس',
					'dashwoo' => 'فقط قالب‌های DashWoo',
				),
			)
		);

		$this->add_control(
			'dw_default_view',
			array(
				'label'   => 'بخش پیش‌فرض',
				'type'    => 'select',
				'default' => 'dashboard',
				'options' => $this->view_options(),
			)
		);

		$this->add_control(
			'dw_per_page',
			array(
				'label'   => 'تعداد آیتم در هر بخش',
				'type'    => 'number',
				'default' => (int) dashwoo_get_setting( 'account_panel.per_page', 10 ),
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'dw_panel_layout',
			array( 'label' => 'چیدمان دو کارت' )
		);

		$this->add_control(
			'dw_aside',
			array(
				'label'   => 'جای کارت منو',
				'type'    => 'select',
				'default' => 'right',
				'options' => array(
					'right' => 'راست (مناسب RTL)',
					'left'  => 'چپ',
				),
			)
		);

		$this->add_control(
			'dw_aside_width',
			array(
				'label'   => 'عرض کارت منو (px)',
				'type'    => 'number',
				'default' => 300,
				'min'     => 140,
				'max'     => 520,
			)
		);

		$this->add_control(
			'dw_gap',
			array(
				'label'   => 'فاصلهٔ دو کارت (px)',
				'type'    => 'number',
				'default' => 24,
				'min'     => 0,
				'max'     => 80,
			)
		);

		$this->add_control(
			'dw_mobile',
			array(
				'label'   => 'رفتار در موبایل',
				'type'    => 'select',
				'default' => 'stack',
				'options' => array(
					'stack' => 'دو کارت روی هم',
					'tabs'  => 'تب‌های افقی منو',
				),
			)
		);

		$this->add_control(
			'dw_sticky',
			array(
				'label'   => 'چسبیدن کارت منو در اسکرول',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_titles',
			array(
				'label'   => 'نمایش عنوان بخش بالای کارت محتوا',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_back_label',
			array(
				'label'   => 'متن دکمهٔ بازگشت',
				'type'    => 'text',
				'default' => (string) dashwoo_get_setting( 'account_panel.back_label', 'بازگشت به سفارش‌ها' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'dw_panel_items',
			array( 'label' => 'آیتم‌های کارت منو' )
		);

		$this->register_items_control(
			'dw_items',
			'آیتم‌ها',
			'خالی بگذارید تا همهٔ بخش‌های فعال حساب کاربری نمایش داده شود. با افزودن آیتم، ترتیب/عنوان/آیکون/نمایش هر آیتم را خودتان تعیین می‌کنید.'
		);

		$this->end_controls_section();

		$this->register_visibility_controls( 'چه چیزی در پنل نمایش داده شود' );
		$this->register_style_controls();
	}

	/**
	 * Options of the "default view" select: every account section.
	 *
	 * @return array<string,string>
	 */
	protected function view_options() {
		$options = array();

		foreach ( Panel::views( array( 'include_hidden' => true ) ) as $id => $view ) {
			$options[ (string) $id ] = (string) $view['label'];
		}

		if ( ! isset( $options['dashboard'] ) ) {
			$options = array_merge( array( 'dashboard' => 'پیشخوان' ), $options );
		}

		return $options;
	}

	/**
	 * View args.
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		$items = $this->setting( 'dw_items', array() );
		$rows  = array();

		foreach ( (array) $items as $row ) {
			$row = (array) $row;

			$rows[] = array(
				'id'      => isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '',
				'label'   => isset( $row['label'] ) ? (string) $row['label'] : '',
				'icon'    => isset( $row['icon'] ) ? sanitize_key( (string) $row['icon'] ) : '',
				'visible' => isset( $row['visible'] ) ? (string) $row['visible'] : 'yes',
				'badge'   => isset( $row['badge'] ) && '' !== (string) $row['badge'] ? (int) $row['badge'] : '',
				'url'     => isset( $row['url'] ) ? ( is_array( $row['url'] ) ? (string) ( $row['url']['url'] ?? '' ) : (string) $row['url'] ) : '',
			);
		}

		return array(
			'mode'         => sanitize_key( (string) $this->setting( 'dw_mode', 'swap' ) ),
			'ajax'         => 'yes' === (string) $this->setting( 'dw_ajax', 'yes' ),
			'source'       => sanitize_key( (string) $this->setting( 'dw_source', 'auto' ) ),
			'default_view' => sanitize_key( (string) $this->setting( 'dw_default_view', 'dashboard' ) ),
			'per_page'     => (int) $this->setting( 'dw_per_page', 10 ),
			'aside'        => sanitize_key( (string) $this->setting( 'dw_aside', 'right' ) ),
			'aside_width'  => (int) $this->setting( 'dw_aside_width', 300 ),
			'gap'          => (int) $this->setting( 'dw_gap', 24 ),
			'mobile'       => sanitize_key( (string) $this->setting( 'dw_mobile', 'stack' ) ),
			'sticky'       => 'yes' === (string) $this->setting( 'dw_sticky', 'yes' ),
			'titles'       => 'yes' === (string) $this->setting( 'dw_titles', 'yes' ),
			'back_label'   => (string) $this->setting( 'dw_back_label', '' ),
			'icons'        => $this->shows( 'icon' ),
			'counts'       => $this->shows( 'badge', true ),
			'items'        => $rows,
			'layout'       => 'menu',
		);
	}
}
