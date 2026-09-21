<?php
/**
 * Elementor widget: منوی حساب کاربری (navigation).
 *
 * Fully controllable: layout, icons and their position, counters, dividers, size,
 * alignment, sticky behaviour - and the menu itself (which items, in which order, with
 * which label, icon, badge or custom link).
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Endpoints;

defined( 'ABSPATH' ) || exit;

/**
 * Account navigation widget.
 */
class Account_Nav_Widget extends Account_Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_account_nav';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'DashWoo — منوی حساب کاربری';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Aliases: the old slug keeps loading a layout that already used it.
	 *
	 * @return array<int,string>
	 */
	public function get_style_depends() {
		return array();
	}

	/**
	 * View.
	 *
	 * @return string
	 */
	protected function view() {
		return 'nav';
	}

	/**
	 * Parts the shop owner may switch off.
	 *
	 * @return array<string,mixed>
	 */
	protected function visibility_spec() {
		return array(
			'icon'  => true,
			'badge' => false,
		);
	}

	/**
	 * Style extras that make sense for a menu.
	 *
	 * @return array<int,string>
	 */
	protected function style_spec() {
		return array( 'nav' );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'dw_account_nav',
			array( 'label' => 'شکل و رفتار منو' )
		);

		$this->add_control(
			'dw_layout',
			array(
				'label'   => 'چیدمان',
				'type'    => 'select',
				'default' => 'menu',
				'options' => array(
					'menu'  => 'فهرست عمودی',
					'tabs'  => 'نوار تب افقی',
					'cards' => 'کارت‌های میان‌بر',
					'rail'  => 'نوار آیکونی کنار',
					'plain' => 'بدون استایل (خام)',
				),
			)
		);

		$this->add_control(
			'dw_icons',
			array(
				'label'   => 'آیکون‌ها',
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_icon_position',
			array(
				'label'     => 'جای آیکون',
				'type'      => 'select',
				'default'   => 'before',
				'options'   => array(
					'before' => 'قبل از عنوان',
					'after'  => 'بعد از عنوان',
				),
				'condition' => array( 'dw_icons' => 'yes' ),
			)
		);

		$this->add_control(
			'dw_counts',
			array(
				'label'       => 'شمارنده (سفارش‌ها / دانلودها)',
				'type'        => 'switcher',
				'default'     => '',
				'description' => 'فقط برای مواردی که DashWoo می‌تواند با API عمومی بشمارد.',
			)
		);

		$this->add_control(
			'dw_size',
			array(
				'label'   => 'اندازهٔ آیتم‌ها',
				'type'    => 'select',
				'default' => 'md',
				'options' => array(
					'sm' => 'کوچک',
					'md' => 'متوسط',
					'lg' => 'بزرگ',
				),
			)
		);

		$this->add_control(
			'dw_align',
			array(
				'label'   => 'ترازبندی آیتم‌ها',
				'type'    => 'select',
				'default' => 'start',
				'options' => array(
					'start'  => 'شروع',
					'center' => 'وسط',
					'end'    => 'پایان',
				),
			)
		);

		$this->add_control(
			'dw_divider',
			array(
				'label'   => 'خط جداکننده بین آیتم‌ها',
				'type'    => 'switcher',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_sticky',
			array(
				'label'   => 'چسبیدن منو در اسکرول',
				'type'    => 'switcher',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_columns',
			array(
				'label'     => 'تعداد ستون (چیدمان کارتی)',
				'type'      => 'select',
				'default'   => (string) (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ),
				'options'   => array(
					'1' => 'یک ستون',
					'2' => 'دو ستون',
					'3' => 'سه ستون',
					'4' => 'چهار ستون',
				),
				'condition' => array( 'dw_layout' => 'cards' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'dw_account_nav_items',
			array( 'label' => 'آیتم‌های منو' )
		);

		$this->register_items_control(
			'dw_items',
			'آیتم‌ها',
			'خالی بگذارید تا همهٔ بخش‌هایی که ووکامرس دارد (و در تنظیمات DashWoo روشن است) با ترتیب پیش‌فرض نمایش داده شود. با افزودن آیتم، همین فهرست جای فهرست خودکار را می‌گیرد: ترتیب، عنوان، آیکون، شمارنده، پنهان‌کردن و حتی لینک‌های دلخواه (مثل «پشتیبانی») در همین جدول قابل تنظیم است.'
		);

		$this->end_controls_section();

		$this->register_visibility_controls();
		$this->register_style_controls();
	}

	/**
	 * View args.
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		$layout = sanitize_key( (string) $this->setting( 'dw_layout', 'menu' ) );

		if ( ! in_array( $layout, array( 'menu', 'tabs', 'cards', 'rail', 'plain' ), true ) ) {
			$layout = 'menu';
		}

		$items = $this->setting( 'dw_items', array() );
		$items = $this->rows( is_array( $items ) ? $items : array() );

		return array(
			'layout'        => $layout,
			'icons'         => 'yes' === (string) $this->setting( 'dw_icons', 'yes' ) && $this->shows( 'icon' ),
			'icon_position' => sanitize_key( (string) $this->setting( 'dw_icon_position', 'before' ) ),
			'counts'        => 'yes' === (string) $this->setting( 'dw_counts', '' ) || $this->shows( 'badge', false ),
			'sticky'        => 'yes' === (string) $this->setting( 'dw_sticky', '' ),
			'divider'       => 'yes' === (string) $this->setting( 'dw_divider', '' ),
			'size'          => sanitize_key( (string) $this->setting( 'dw_size', 'md' ) ),
			'align'         => sanitize_key( (string) $this->setting( 'dw_align', 'start' ) ),
			'columns'       => (int) $this->setting( 'dw_columns', '3' ),
			'items'         => $items,
			'class'         => 'dw-acc--nav-' . $layout,
		);
	}

	/**
	 * Clean the repeater rows.
	 *
	 * @param array<int,mixed> $rows Rows.
	 * @return array<int,array<string,mixed>>
	 */
	protected function rows( array $rows ) {
		$clean = array();

		foreach ( $rows as $row ) {
			$row = (array) $row;

			$clean[] = array(
				'id'      => isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '',
				'label'   => isset( $row['label'] ) ? (string) $row['label'] : '',
				'icon'    => isset( $row['icon'] ) ? sanitize_key( (string) $row['icon'] ) : '',
				'visible' => isset( $row['visible'] ) ? (string) $row['visible'] : 'yes',
				'badge'   => isset( $row['badge'] ) && '' !== (string) $row['badge'] ? (int) $row['badge'] : '',
				'url'     => isset( $row['url'] ) ? ( is_array( $row['url'] ) ? (string) ( $row['url']['url'] ?? '' ) : (string) $row['url'] ) : '',
			);
		}

		unset( $rows );

		return $clean;
	}
}
