<?php
/**
 * Elementor widget: the account menu (navigation).
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
		return __( 'DashWoo — Account menu', 'dashwoo' );
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
			array( 'label' => __( 'Menu shape and behaviour', 'dashwoo' ) )
		);

		$this->add_control(
			'dw_layout',
			array(
				'label'   => __( 'Layout', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'menu',
				'options' => array(
					'menu'  => __( 'Vertical list', 'dashwoo' ),
					'tabs'  => __( 'Horizontal tabs', 'dashwoo' ),
					'cards' => __( 'Shortcut cards', 'dashwoo' ),
					'rail'  => __( 'Side icon rail', 'dashwoo' ),
					'plain' => __( 'Unstyled (plain)', 'dashwoo' ),
				),
			)
		);

		$this->add_control(
			'dw_icons',
			array(
				'label'   => __( 'Icons', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$this->add_control(
			'dw_icon_position',
			array(
				'label'     => __( 'Icon position', 'dashwoo' ),
				'type'      => 'select',
				'default'   => 'before',
				'options'   => array(
					'before' => __( 'Before the label', 'dashwoo' ),
					'after'  => __( 'After the label', 'dashwoo' ),
				),
				'condition' => array( 'dw_icons' => 'yes' ),
			)
		);

		$this->add_control(
			'dw_counts',
			array(
				'label'       => __( 'Counter (orders / downloads)', 'dashwoo' ),
				'type'        => 'switcher',
				'default'     => '',
				'description' => __( 'Only for the items DashWoo can count through the public API.', 'dashwoo' ),
			)
		);

		$this->add_control(
			'dw_size',
			array(
				'label'   => __( 'Item size', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'md',
				'options' => array(
					'sm' => __( 'Small', 'dashwoo' ),
					'md' => __( 'Medium', 'dashwoo' ),
					'lg' => __( 'Large', 'dashwoo' ),
				),
			)
		);

		$this->add_control(
			'dw_align',
			array(
				'label'   => __( 'Item alignment', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'start',
				'options' => array(
					'start'  => __( 'Getting started', 'dashwoo' ),
					'center' => __( 'Center', 'dashwoo' ),
					'end'    => __( 'End', 'dashwoo' ),
				),
			)
		);

		$this->add_control(
			'dw_divider',
			array(
				'label'   => __( 'Divider between the items', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_sticky',
			array(
				'label'   => __( 'Sticky menu while scrolling', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => '',
			)
		);

		$this->add_control(
			'dw_columns',
			array(
				'label'     => __( 'Number of columns (card layout)', 'dashwoo' ),
				'type'      => 'select',
				'default'   => (string) (int) dashwoo_get_setting( 'account_layout.cards_columns', 3 ),
				'options'   => array(
					'1' => __( 'One column', 'dashwoo' ),
					'2' => __( 'Two columns', 'dashwoo' ),
					'3' => __( 'Three columns', 'dashwoo' ),
					'4' => __( 'Four columns', 'dashwoo' ),
				),
				'condition' => array( 'dw_layout' => 'cards' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'dw_account_nav_items',
			array( 'label' => __( 'Menu items', 'dashwoo' ) )
		);

		$this->register_items_control(
			'dw_items',
			__( 'Items', 'dashwoo' ),
			__( 'Leave it empty to show every section WooCommerce has (that is switched on in the DashWoo settings) in the default order. As soon as you add an item, this list replaces the automatic one: order, label, icon, counter, visibility and even custom links (such as “Support”) are all set in this table.', 'dashwoo' )
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
