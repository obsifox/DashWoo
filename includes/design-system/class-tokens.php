<?php
/**
 * Design tokens: the 16 categories, mapped onto CSS custom properties.
 *
 * @package DashWoo
 */

namespace DashWoo\DesignSystem;

use DashWoo\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Token definitions.
 */
final class Tokens {

	const PREFIX = '--dw-';

	/**
	 * Singleton.
	 *
	 * @var Tokens|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Tokens
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function boot() {
		// Nothing to hook: resolve() reads the custom-token filter directly so the
		// token map is identical whether or not the plugin was booted.
	}

	/**
	 * The token categories (this is the public map: 16 categories).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function categories() {
		return array(
			'color'      => array( 'label' => __( 'Colors', 'dashwoo' ), 'settings' => 'colors' ),
			'font'       => array( 'label' => __( 'Fonts', 'dashwoo' ), 'settings' => 'typography' ),
			'font-size'  => array( 'label' => __( 'Font size', 'dashwoo' ), 'settings' => 'typography' ),
			'line-height' => array( 'label' => __( 'Line height', 'dashwoo' ), 'settings' => 'typography' ),
			'spacing'    => array( 'label' => __( 'Spacing', 'dashwoo' ), 'settings' => 'spacing' ),
			'radius'     => array( 'label' => __( 'Radius', 'dashwoo' ), 'settings' => 'radius' ),
			'shadow'     => array( 'label' => __( 'Shadow', 'dashwoo' ), 'settings' => 'shadows' ),
			'border'     => array( 'label' => __( 'Border', 'dashwoo' ), 'settings' => 'borders' ),
			'button'     => array( 'label' => __( 'Button', 'dashwoo' ), 'settings' => 'buttons' ),
			'input'      => array( 'label' => __( 'Input', 'dashwoo' ), 'settings' => 'forms' ),
			'card'       => array( 'label' => __( 'Card', 'dashwoo' ), 'settings' => 'cards' ),
			'table'      => array( 'label' => __( 'Table', 'dashwoo' ), 'settings' => 'tables' ),
			'badge'      => array( 'label' => __( 'Badge', 'dashwoo' ), 'settings' => 'badges' ),
			'alert'      => array( 'label' => __( 'Notice', 'dashwoo' ), 'settings' => 'alerts' ),
			'overlay'    => array( 'label' => __( 'Overlay', 'dashwoo' ), 'settings' => 'overlays' ),
			'pagination' => array( 'label' => __( 'Pagination', 'dashwoo' ), 'settings' => 'pagination' ),
		);
	}

	/**
	 * All token values as token-name => value (no CSS prefix yet).
	 *
	 * @return array<string,string>
	 */
	public function resolve() {
		$tokens = array();

		/* ------------------------------------------------------------ colors */
		$colors = (array) dashwoo_get_setting( 'colors', array() );
		foreach ( $colors as $key => $value ) {
			$tokens[ 'color-' . str_replace( '_', '-', $key ) ] = (string) $value;
		}

		/* -------------------------------------------------------- typography */
		$tokens['font-primary']      = (string) dashwoo_get_setting( 'typography.primary_font', 'Vazirmatn' );
		$tokens['font-heading']      = (string) dashwoo_get_setting( 'typography.heading_font', 'Vazirmatn' );
		$tokens['font-body']         = (string) dashwoo_get_setting( 'typography.body_font', 'Vazirmatn' );
		$tokens['font-button']       = (string) dashwoo_get_setting( 'typography.button_font', 'Vazirmatn' );
		$tokens['font-weight-normal'] = '400';
		$tokens['font-weight-medium'] = '500';
		$tokens['font-weight-bold']   = '700';

		$weights = (array) dashwoo_get_setting( 'typography.weights', array( '400', '500', '700' ) );
		foreach ( $weights as $weight ) {
			$tokens[ 'font-weight-' . $weight ] = (string) $weight;
		}

		$base = (int) dashwoo_get_setting( 'typography.base_size_desktop', 16 );
		$tokens['font-size-base']      = $base . 'px';
		$tokens['font-size-sm']        = max( 10, $base - 2 ) . 'px';
		$tokens['font-size-lg']        = ( $base + 2 ) . 'px';
		$tokens['font-size-xl']        = ( $base + 6 ) . 'px';
		$tokens['font-size-2xl']       = ( $base + 12 ) . 'px';
		$tokens['font-size-3xl']       = ( $base + 20 ) . 'px';
		$tokens['font-line-height']    = (string) dashwoo_get_setting( 'typography.line_height', 1.75 );
		$tokens['font-letter-spacing'] = dashwoo_get_setting( 'typography.letter_spacing', 0 ) . 'em';

		/* ----------------------------------------------------------- spacing */
		$unit  = (string) dashwoo_get_setting( 'spacing.unit', 'px' );
		$scale = preg_split( '/\s*,\s*/', (string) dashwoo_get_setting( 'spacing.scale', '4,8,12,16,24,32,48,64' ) );
		$index = 0;
		foreach ( (array) $scale as $step ) {
			if ( '' === trim( (string) $step ) ) {
				continue;
			}
			$index++;
			$tokens[ 'space-' . $index ] = ( is_numeric( $step ) ? $step : $step ) . $unit;
		}

		/* ------------------------------------------------------------ radius */
		foreach ( (array) dashwoo_get_setting( 'radius', array() ) as $key => $value ) {
			$tokens[ 'radius-' . $key ] = (int) $value . 'px';
		}

		/* ------------------------------------------------------------ shadows */
		foreach ( (array) dashwoo_get_setting( 'shadows', array() ) as $key => $value ) {
			$tokens[ 'shadow-' . $key ] = (string) $value;
		}

		/* ------------------------------------------------------------ borders */
		$tokens['border-width'] = (int) dashwoo_get_setting( 'borders.width', 1 ) . 'px';
		$tokens['border-style'] = (string) dashwoo_get_setting( 'borders.style', 'solid' );
		$tokens['border']       = $tokens['border-width'] . ' ' . $tokens['border-style'] . ' ' . $tokens['color-border'];

		/* ------------------------------------------------------------ buttons */
		$tokens['button-padding-y']   = (int) dashwoo_get_setting( 'buttons.padding_y', 12 ) . 'px';
		$tokens['button-padding-x']   = (int) dashwoo_get_setting( 'buttons.padding_x', 20 ) . 'px';
		$tokens['button-radius']      = (int) dashwoo_get_setting( 'buttons.radius', 8 ) . 'px';
		$tokens['button-font-weight'] = (string) dashwoo_get_setting( 'buttons.weight', '500' );
		$tokens['button-transition']  = (int) dashwoo_get_setting( 'buttons.transition_ms', 180 ) . 'ms';

		/* -------------------------------------------------------------- input */
		$tokens['input-height']      = (int) dashwoo_get_setting( 'forms.input_height', 44 ) . 'px';
		$tokens['input-radius']      = (int) dashwoo_get_setting( 'forms.input_radius', 8 ) . 'px';
		$tokens['input-label-position'] = (string) dashwoo_get_setting( 'forms.label_position', 'top' );

		/* --------------------------------------------------------------- card */
		$tokens['card-padding'] = (int) dashwoo_get_setting( 'cards.padding', 16 ) . 'px';
		$tokens['card-radius']  = (int) dashwoo_get_setting( 'cards.radius', 12 ) . 'px';

		/* -------------------------------------------------------------- table */
		$tokens['table-cell-padding'] = (int) dashwoo_get_setting( 'tables.cell_padding', 12 ) . 'px';
		$tokens['table-sticky-header'] = dashwoo_is_on( 'tables.sticky_header' ) ? '1' : '0';

		/* -------------------------------------------------------------- badge */
		$tokens['badge-discount-color'] = (string) dashwoo_get_setting( 'badges.discount_color', '#dc2626' );
		$tokens['badge-new-color']      = (string) dashwoo_get_setting( 'badges.new_color', '#16a34a' );
		$tokens['badge-radius']         = (int) dashwoo_get_setting( 'badges.radius', 999 ) . 'px';

		/* -------------------------------------------------------------- alert */
		$tokens['alert-position']  = (string) dashwoo_get_setting( 'alerts.position', 'top' );
		$tokens['alert-timeout']   = (int) dashwoo_get_setting( 'alerts.timeout', 6 ) . 's';

		/* ------------------------------------------------------------ overlay */
		$tokens['overlay-backdrop'] = (string) dashwoo_get_setting( 'overlays.backdrop_opacity', 0.5 );

		/* --------------------------------------------------------- pagination */
		$tokens['pagination-per-page'] = (int) dashwoo_get_setting( 'pagination.per_page', 12 );
		$tokens['pagination-style']    = (string) dashwoo_get_setting( 'pagination.style', 'numbers' );

		/**
		 * Filter the resolved tokens before they become CSS variables.
		 *
		 * @param array<string,string> $tokens token => value.
		 */
		$tokens = apply_filters( 'dashwoo_tokens', $tokens );

		return $this->with_custom_tokens( $tokens );
	}

	/**
	 * Developer hook: merge third-party tokens (dashwoo_custom_tokens).
	 *
	 * Names are normalized to CSS-safe characters; anything that cannot be
	 * normalized is dropped instead of producing a broken custom property.
	 *
	 * @param array<string,string> $tokens Tokens.
	 * @return array<string,string>
	 */
	public function with_custom_tokens( $tokens ) {
		$custom = (array) apply_filters( 'dashwoo_custom_tokens', array() );

		foreach ( $custom as $name => $value ) {
			$name = strtolower( trim( (string) $name ) );

			// Allow-list: reject the whole name when it carries characters that are
			// not valid inside a custom property (quotes, brackets, dollars, ...),
			// instead of silently "fixing" it into a different token.
			if ( '' === $name || ! preg_match( '/^[a-z0-9\-_ ]+$/', $name ) ) {
				continue;
			}

			$name = trim( str_replace( array( ' ', '_' ), '-', $name ), '-' );

			if ( '' !== $name && is_scalar( $value ) ) {
				$tokens[ $name ] = (string) $value;
			}
		}

		return $tokens;
	}

	/**
	 * Raw CSS custom property name.
	 *
	 * @param string $token Token name.
	 * @return string
	 */
	public static function var_name( $token ) {
		return self::PREFIX . str_replace( '_', '-', (string) $token );
	}

	/**
	 * Elementor-friendly reference with a fallback: var(--dw-color-primary, #000).
	 *
	 * @param string $token    Token name.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	public static function reference( $token, $fallback = '' ) {
		$name = self::var_name( $token );

		return '' === $fallback ? 'var(' . $name . ')' : 'var(' . $name . ', ' . $fallback . ')';
	}

	/**
	 * Defaults, used to seed the Elementor Kit sync + regression snapshots.
	 *
	 * @return array<string,string>
	 */
	public static function defaults() {
		$settings = Settings::defaults();
		$tokens   = array();

		foreach ( $settings as $section => $values ) {
			foreach ( $values as $key => $value ) {
				if ( is_scalar( $value ) ) {
					$tokens[ $section . '-' . $key ] = (string) $value;
				}
			}
		}

		return $tokens;
	}
}
