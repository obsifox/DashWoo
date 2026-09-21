<?php
/**
 * The shared Elementor style kit.
 *
 * Every DashWoo widget can be designed to the last pixel without a single line of
 * custom CSS: the controls below map to CSS custom properties (`--dw-acc-*`) that the
 * renderers print on the widget's own wrapper. That keeps one source of truth for the
 * markup, keeps Elementor's own stylesheet out of the picture, and means a control the
 * shop owner changes can never disagree with what the theme renders.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Renderer;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Style controls.
 */
class Style_Controls {

	/**
	 * The design kit every widget shares.
	 *
	 * `var` is the CSS custom property (without the `--dw-acc-` prefix), `unit` is
	 * appended to numbers, `class` marks a control that adds a modifier class instead
	 * of a variable.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function kit() {
		$kit = array(
			'dw_accent'    => array(
				'label' => __( 'Accent colour', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'accent',
				'hint'  => __( 'Links, the active item and the buttons.', 'dashwoo' ),
			),
			'dw_accent_hover' => array(
				'label' => __( 'Accent colour (hover)', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'accent-hover',
			),
			'dw_text'      => array(
				'label' => __( 'Text color', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'text',
			),
			'dw_muted'     => array(
				'label' => __( 'Muted text color', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'muted',
			),
			'dw_surface'   => array(
				'label' => __( 'Card surface color', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'surface',
			),
			'dw_stage'     => array(
				'label' => __( 'Area background color', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'stage',
			),
			'dw_border'    => array(
				'label' => __( 'Border color', 'dashwoo' ),
				'type'  => 'color',
				'var'   => 'border',
			),
			'dw_radius'    => array(
				'label'   => __( 'Corner radius', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'radius',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 60,
				'default' => (int) dashwoo_get_setting( 'account_design.radius', 18 ),
			),
			'dw_pad'       => array(
				'label'   => __( 'Card padding', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'pad',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 80,
				'default' => 0,
			),
			'dw_gap'       => array(
				'label'   => __( 'Gap between the parts', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'space',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 80,
				'default' => 0,
			),
			'dw_font_size' => array(
				'label'   => __( 'Font size', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'font-size',
				'unit'    => 'px',
				'min'     => 8,
				'max'     => 40,
				'default' => 0,
			),
			'dw_icon_size' => array(
				'label'   => __( 'Icon size', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'icon',
				'unit'    => 'px',
				'min'     => 12,
				'max'     => 64,
				'default' => 0,
			),
			'dw_weight'    => array(
				'label'   => __( 'Font weight', 'dashwoo' ),
				'type'    => 'select',
				'var'     => 'weight',
				'options' => array(
					''    => __( 'Default', 'dashwoo' ),
					'400' => __( 'Regular', 'dashwoo' ),
					'500' => __( 'Medium', 'dashwoo' ),
					'600' => __( 'Semi Bold', 'dashwoo' ),
					'700' => __( 'Bold', 'dashwoo' ),
					'800' => __( 'Extra Bold', 'dashwoo' ),
				),
			),
			'dw_align'     => array(
				'label'   => __( 'Alignment', 'dashwoo' ),
				'type'    => 'select',
				'var'     => 'align',
				'options' => array(
					''       => __( 'Default', 'dashwoo' ),
					'start'  => __( 'Getting started', 'dashwoo' ),
					'center' => __( 'Center', 'dashwoo' ),
					'end'    => __( 'End', 'dashwoo' ),
				),
			),
			'dw_tone'      => array(
				'label'   => __( 'Color mode', 'dashwoo' ),
				'type'    => 'select',
				'class'   => 'dw-acc--tone-',
				'options' => array(
					'auto'  => __( 'Automatic with the DashWoo tokens', 'dashwoo' ),
					'light' => __( 'On', 'dashwoo' ),
					'dark'  => __( 'Dark', 'dashwoo' ),
				),
				'default' => 'auto',
			),
		);

		/**
		 * Filter the shared style kit.
		 *
		 * @param array<string,array<string,mixed>> $kit Controls.
		 */
		return (array) apply_filters( 'dashwoo_widget_style_kit', $kit );
	}

	/**
	 * Controls only some widgets need (extras are merged over the kit).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function extras() {
		return array(
			'dw_nav_width'   => array(
				'label'   => __( 'Menu column width (two-column layout)', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'nav-width',
				'unit'    => 'px',
				'min'     => 140,
				'max'     => 520,
				'default' => 0,
				'widgets' => array( 'nav', 'panel' ),
			),
			'dw_cols'        => array(
				'label'   => __( 'Number of columns', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'cols',
				'min'     => 1,
				'max'     => 4,
				'default' => 0,
				'widgets' => array( 'dashboard', 'nav' ),
			),
			'dw_item_pad'    => array(
				'label'   => __( 'Padding of every menu item', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'item-pad',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 40,
				'default' => 0,
				'widgets' => array( 'nav', 'panel' ),
			),
			'dw_item_gap'    => array(
				'label'   => __( 'Gap between the menu items', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'item-gap',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 40,
				'default' => 0,
				'widgets' => array( 'nav', 'panel' ),
			),
			'dw_active_bg'   => array(
				'label'   => __( 'Active item background', 'dashwoo' ),
				'type'    => 'color',
				'var'     => 'active-bg',
				'widgets' => array( 'nav', 'panel' ),
			),
			'dw_hover_bg'    => array(
				'label'   => __( 'Hover background', 'dashwoo' ),
				'type'    => 'color',
				'var'     => 'hover-bg',
				'widgets' => array( 'nav', 'panel' ),
			),
			'dw_card_min_h'  => array(
				'label'   => __( 'Minimum height of the content card', 'dashwoo' ),
				'type'    => 'number',
				'var'     => 'panel-min-h',
				'unit'    => 'px',
				'min'     => 0,
				'max'     => 900,
				'default' => 0,
				'widgets' => array( 'panel' ),
			),
		);
	}

	/**
	 * Register the style section on a widget.
	 *
	 * @param object   $widget Widget instance (needs add_control/start_controls_section).
	 * @param string[] $include Extra control ids to include (nav, panel, dashboard...).
	 * @return void
	 */
	public static function register( $widget, array $include = array() ) {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'add_control' ) ) {
			return;
		}

		self::section( $widget, 'dw_style_colors', __( 'Colors', 'dashwoo' ), array( 'dw_accent', 'dw_accent_hover', 'dw_text', 'dw_muted', 'dw_surface', 'dw_stage', 'dw_border' ), $include );
		self::section( $widget, 'dw_style_size', __( 'Sizes', 'dashwoo' ), array( 'dw_radius', 'dw_pad', 'dw_gap', 'dw_font_size', 'dw_icon_size', 'dw_card_min_h' ), $include );
		self::section( $widget, 'dw_style_type', __( 'Typography and alignment', 'dashwoo' ), array( 'dw_weight', 'dw_align', 'dw_tone' ), $include );
		self::section( $widget, 'dw_style_menu', __( 'Menu', 'dashwoo' ), array( 'dw_nav_width', 'dw_item_pad', 'dw_item_gap', 'dw_active_bg', 'dw_hover_bg', 'dw_cols' ), $include );
	}

	/**
	 * One style section.
	 *
	 * @param object   $widget  Widget.
	 * @param string   $id      Section id.
	 * @param string   $label   Label.
	 * @param string[] $keys    Control keys.
	 * @param string[] $include Widget kind (nav/panel/...).
	 * @return void
	 */
	protected static function section( $widget, $id, $label, array $keys, array $include ) {
		$kit = array_merge( self::kit(), self::extras() );

		$widget->start_controls_section(
			$id,
			array(
				'label' => $label,
				'tab'   => 'style',
			)
		);

		foreach ( $keys as $key ) {
			if ( ! isset( $kit[ $key ] ) ) {
				continue;
			}

			if ( isset( $kit[ $key ]['widgets'] ) && ! array_intersect( (array) $kit[ $key ]['widgets'], $include ) && ! in_array( 'all', $include, true ) ) {
				continue;
			}

			$widget->add_control( $key, self::definition( $key, $kit[ $key ] ) );
		}

		$widget->end_controls_section();
	}

	/**
	 * Turn a kit entry into an Elementor control definition.
	 *
	 * @param string              $key  Control key.
	 * @param array<string,mixed> $spec Spec.
	 * @return array<string,mixed>
	 */
	protected static function definition( $key, array $spec ) {
		$type = (string) ( $spec['type'] ?? 'text' );

		$definition = array(
			'label'       => (string) ( $spec['label'] ?? $key ),
			'type'        => $type,
			'default'     => $spec['default'] ?? ( 'number' === $type ? 0 : '' ),
			'description' => (string) ( $spec['hint'] ?? '' ),
		);

		if ( isset( $spec['options'] ) ) {
			$definition['options'] = (array) $spec['options'];
		}

		if ( 'number' === $type ) {
			$definition['min']  = (int) ( $spec['min'] ?? 0 );
			$definition['max']  = (int) ( $spec['max'] ?? 999 );
			$definition['step'] = (int) ( $spec['step'] ?? 1 );
		}

		return $definition;
	}

	/**
	 * Settings => style args for the renderer (`vars` + modifier classes).
	 *
	 * Empty values are dropped, so "Default" really means "DashWoo's own default" and
	 * never a hard-coded zero.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string[]            $include  Extra ids in play.
	 * @return array<string,mixed>
	 */
	public static function args( array $settings, array $include = array() ) {
		$kit = array_merge( self::kit(), self::extras() );

		$vars    = array();
		$classes = array();

		foreach ( $kit as $key => $spec ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				continue;
			}

			$value = $settings[ $key ];

			if ( ! empty( $spec['widgets'] ) && ! array_intersect( (array) $spec['widgets'], $include ) && ! in_array( 'all', $include, true ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = $value['size'] ?? '';
			}

			$value = trim( (string) $value );

			if ( '' === $value || '0' === $value ) {
				continue;
			}

			if ( isset( $spec['class'] ) ) {
				$classes[] = (string) $spec['class'] . sanitize_key( $value );

				continue;
			}

			if ( isset( $spec['var'] ) ) {
				$vars[ (string) $spec['var'] ] = $value . ( isset( $spec['unit'] ) && is_numeric( $value ) ? (string) $spec['unit'] : '' );
			}
		}

		if ( ! $vars && ! $classes ) {
			return array();
		}

		return array(
			'vars'  => $vars,
			'class' => implode( ' ', $classes ),
		);
	}

	/**
	 * Style args + the renderer's own CSS for one widget.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string[]            $include  Widget kinds.
	 * @return array<string,mixed>
	 */
	public static function style_args( array $settings, array $include = array() ) {
		$args = self::args( $settings, $include );

		if ( ! $args ) {
			return array();
		}

		// The variables must reach the markup; the renderer turns them into a
		// `style="--dw-acc-x: y"` attribute on the wrapper.
		$args['css'] = Renderer::styles( array( 'vars' => $args['vars'] ) );

		return $args;
	}
}
