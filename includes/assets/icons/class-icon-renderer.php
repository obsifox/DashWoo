<?php
/**
 * Icon renderer: turns widget attributes into markup + variable-font CSS.
 *
 * @package DashWoo
 */

namespace DashWoo\Assets\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Renderer.
 */
final class Icon_Renderer {

	/**
	 * Numeric clamp helper.
	 *
	 * @param mixed $value Raw value.
	 * @param float $min   Minimum.
	 * @param float $max   Maximum.
	 * @param float $default Fallback.
	 * @return float
	 */
	public static function clamp( $value, $min, $max, $default ) {
		if ( ! is_numeric( $value ) ) {
			$value = $default;
		}

		return max( (float) $min, min( (float) $max, (float) $value ) );
	}

	/**
	 * Normalise widget/attribute input.
	 *
	 * @param array<string,mixed> $args provider, icon, size, weight, fill, grade, opsz, color, position.
	 * @return array<string,mixed>
	 */
	public static function normalize( array $args ) {
		$defaults = array(
			'provider' => dashwoo_get_setting( 'icons.provider', 'material-symbols' ),
			'style'    => dashwoo_get_setting( 'icons.style', 'outlined' ),
			'icon'     => 'star',
			'size'     => dashwoo_get_setting( 'icons.size', 24 ),
			'weight'   => dashwoo_get_setting( 'icons.weight', 400 ),
			'fill'     => dashwoo_get_setting( 'icons.fill', 0 ),
			'grade'    => dashwoo_get_setting( 'icons.grade', 0 ),
			'opsz'     => dashwoo_get_setting( 'icons.optical_size', 24 ),
			'color'    => dashwoo_get_setting( 'icons.color', 'currentColor' ),
			'position' => dashwoo_get_setting( 'icon_widget.default_position', 'before' ),
			'class'    => '',
		);

		$args = array_merge( $defaults, array_filter( $args, static function ( $value ) {
			return null !== $value && '' !== $value;
		} ) );

		$args['provider'] = in_array( $args['provider'], array( 'material-symbols', 'material-icons', 'custom-svg' ), true )
			? $args['provider']
			: 'material-symbols';

		$args['style']    = in_array( $args['style'], array( 'outlined', 'rounded', 'sharp', 'classic' ), true ) ? $args['style'] : 'outlined';
		$args['position'] = in_array( $args['position'], array( 'before', 'after', 'only' ), true ) ? $args['position'] : 'before';
		$args['icon']     = preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $args['icon'] );
		$args['size']     = (int) self::clamp( $args['size'], 8, 512, 24 );

		if ( 'material-icons' === $args['provider'] ) {
			// Classic Material Icons is a static font: no axes.
			$args['style']  = 'classic';
			$args['weight'] = 400;
			$args['fill']   = 0;
			$args['grade']  = 0;
			$args['opsz']   = 24;
		} else {
			$args['weight'] = (int) self::clamp( $args['weight'], 100, 700, 400 );
			$args['weight'] = (int) ( round( $args['weight'] / 100 ) * 100 );
			$args['fill']   = (int) self::clamp( $args['fill'], 0, 1, 0 );
			$args['grade']  = (int) self::clamp( $args['grade'], -25, 200, 0 );
			$args['opsz']   = (int) self::clamp( $args['opsz'], 20, 48, 24 );
		}

		if ( ! in_array( $args['color'], array( 'currentColor', 'inherit', 'transparent' ), true ) ) {
			$hex             = function_exists( 'sanitize_hex_color' ) ? sanitize_hex_color( (string) $args['color'] ) : '';
			$args['color']   = $hex ? $hex : 'currentColor';
		}

		return $args;
	}

	/**
	 * font-variation-settings value for the given axes.
	 *
	 * @param array<string,mixed> $args Normalised args.
	 * @return string
	 */
	public static function variation_settings( array $args ) {
		$args = self::normalize( $args );

		if ( 'classic' === $args['style'] ) {
			return '';
		}

		return sprintf(
			'"FILL" %d, "GRAD" %d, "opsz" %d, "wght" %d',
			(int) $args['fill'],
			(int) $args['grade'],
			(int) $args['opsz'],
			(int) $args['weight']
		);
	}

	/**
	 * Inline style attribute content.
	 *
	 * @param array<string,mixed> $args Normalised args.
	 * @return string
	 */
	public static function inline_style( array $args ) {
		$args    = self::normalize( $args );
		$styles  = array(
			'font-size: ' . (int) $args['size'] . 'px',
			'color: ' . $args['color'],
		);

		if ( 'classic' !== $args['style'] ) {
			$styles[] = 'font-variation-settings: ' . self::variation_settings( $args );
		}

		return implode( '; ', $styles ) . ';';
	}

	/**
	 * CSS classes for the icon element.
	 *
	 * @param array<string,mixed> $args Normalised args.
	 * @return string
	 */
	public static function classes( array $args ) {
		$args  = self::normalize( $args );
		$class = array( 'dw-icon' );

		$class[] = 'material-symbols' === $args['provider'] ? 'dw-icon--material-symbols' : 'dw-icon--' . $args['provider'];
		$class[] = 'dw-icon--' . $args['style'];
		$class[] = 'dw-icon--' . $args['position'];

		if ( ! empty( $args['class'] ) ) {
			foreach ( preg_split( '/\s+/', (string) $args['class'] ) as $extra ) {
				$extra = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $extra );
				if ( '' !== $extra ) {
					$class[] = $extra;
				}
			}
		}

		return implode( ' ', array_unique( $class ) );
	}

	/**
	 * Full markup.
	 *
	 * @param array<string,mixed> $args Widget attributes.
	 * @return string
	 */
	public static function render( array $args = array() ) {
		$args = self::normalize( $args );

		if ( '' === $args['icon'] ) {
			return '';
		}

		if ( 'custom-svg' === $args['provider'] ) {
			return self::render_svg( $args );
		}

		/**
		 * Filter the icon element markup.
		 *
		 * @param string              $html Markup.
		 * @param array<string,mixed> $args Normalised args.
		 */
		return apply_filters(
			'dashwoo_icon_html',
			sprintf(
				'<span class="%1$s" style="%2$s" aria-hidden="true" translate="no">%3$s</span>',
				esc_attr( self::classes( $args ) ),
				esc_attr( self::inline_style( $args ) ),
				esc_html( $args['icon'] )
			),
			$args
		);
	}

	/**
	 * Render a stored custom SVG as an icon.
	 *
	 * @param array<string,mixed> $args Normalised args.
	 * @return string
	 */
	public static function render_svg( array $args ) {
		$svg = apply_filters( 'dashwoo_icon_svg', '', $args['icon'], $args );

		if ( ! is_string( $svg ) || '' === $svg ) {
			return '';
		}

		return sprintf(
			'<span class="%1$s" style="%2$s" aria-hidden="true">%3$s</span>',
			esc_attr( self::classes( $args ) ),
			esc_attr( 'font-size: ' . (int) $args['size'] . 'px; color: ' . $args['color'] . ';' ),
			$svg
		);
	}

	/**
	 * Elementor control definition (used by the widget + the admin preview).
	 *
	 * @return array<string,mixed>
	 */
	public static function controls_schema() {
		return array(
			'provider' => array(
				'type'    => 'select',
				'label'   => __( 'Icon provider', 'dashwoo' ),
				'default' => dashwoo_get_setting( 'icons.provider', 'material-symbols' ),
				'options' => array(
					'material-symbols' => __( 'Material Symbols (variable)', 'dashwoo' ),
					'material-icons'   => __( 'Material Icons (classic)', 'dashwoo' ),
					'custom-svg'       => __( 'Custom SVG', 'dashwoo' ),
				),
			),
			'icon'     => array(
				'type'    => 'text',
				'label'   => __( 'Icon name (ligature)', 'dashwoo' ),
				'default' => 'star',
			),
			'size'     => array(
				'type'    => 'number',
				'label'   => __( 'Size (px)', 'dashwoo' ),
				'default' => dashwoo_get_setting( 'icons.size', 24 ),
				'min'     => 8,
				'max'     => 512,
			),
			'weight'   => array(
				'type'    => 'number',
				'label'   => __('Weight', 'dashwoo'),
				'default' => dashwoo_get_setting( 'icons.weight', 400 ),
				'min'     => 100,
				'max'     => 700,
				'step'    => 100,
			),
			'fill'     => array(
				'type'    => 'number',
				'label'   => __('Fill', 'dashwoo'),
				'default' => dashwoo_get_setting( 'icons.fill', 0 ),
				'min'     => 0,
				'max'     => 1,
			),
			'grade'    => array(
				'type'    => 'number',
				'label'   => __('Grade', 'dashwoo'),
				'default' => dashwoo_get_setting( 'icons.grade', 0 ),
				'min'     => -25,
				'max'     => 200,
				'step'    => 25,
			),
			'opsz'     => array(
				'type'    => 'number',
				'label'   => __('Optical Size', 'dashwoo'),
				'default' => dashwoo_get_setting( 'icons.optical_size', 24 ),
				'min'     => 20,
				'max'     => 48,
			),
			'color'    => array(
				'type'    => 'color',
				'label'   => __( 'Color', 'dashwoo' ),
				'default' => dashwoo_get_setting( 'icons.color', 'currentColor' ),
			),
			'position' => array(
				'type'    => 'select',
				'label'   => __( 'Position', 'dashwoo' ),
				'default' => dashwoo_get_setting( 'icon_widget.default_position', 'before' ),
				'options' => array(
					'before' => __( 'Before the text', 'dashwoo' ),
					'after'  => __( 'After the text', 'dashwoo' ),
					'only'   => __( 'Icon only', 'dashwoo' ),
				),
			),
		);
	}
}
