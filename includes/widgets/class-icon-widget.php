<?php
/**
 * Elementor Icon widget — fully local, variable-font aware.
 *
 * The widget is only instantiated by Elementor, but the class is written so
 * render_icon() can be unit tested without Elementor being present.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Assets\Icons\Icon_Manager;
use DashWoo\Assets\Icons\Icon_Renderer;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Icon widget.
 */
class Icon_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'dashwoo_icon';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'DashWoo icon', 'dashwoo' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-star';
	}

	/**
	 * Widget category.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		return array( 'dashwoo' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords() {
		return array( 'icon', 'material', 'symbols', 'dashwoo', __( 'Icon', 'dashwoo' ) );
	}

	/**
	 * Panel controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$schema = Icon_Renderer::controls_schema();

		$this->start_controls_section(
			'content',
			array(
				'label' => __( 'Icon', 'dashwoo' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'provider',
			array(
				'label'   => $schema['provider']['label'],
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $schema['provider']['default'],
				'options' => $schema['provider']['options'],
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'       => $schema['icon']['label'],
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $schema['icon']['default'],
				'placeholder' => 'shopping_cart',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'position',
			array(
				'label'     => $schema['position']['label'],
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => $schema['position']['default'],
				'options'   => $schema['position']['options'],
				'condition' => array( 'show_position' => 'yes' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label' => __( 'Link', 'dashwoo' ),
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->end_controls_section();

		// --- Variable font axes -------------------------------------------------
		if ( dashwoo_is_on( 'icon_widget.show_variation_controls' ) ) {
			$this->start_controls_section(
				'variations',
				array(
					'label' => __( 'Variable font axes', 'dashwoo' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'weight',
				array(
					'label'     => __('Weight', 'dashwoo'),
					'type'      => \Elementor\Controls_Manager::SLIDER,
					'default'   => array( 'size' => $schema['weight']['default'] ),
					'range'     => array(
						'px' => array(
							'min'  => 100,
							'max'  => 700,
							'step' => 100,
						),
					),
					'condition' => array( 'provider' => 'material-symbols' ),
				)
			);

			$this->add_control(
				'fill',
				array(
					'label'     => __('Fill', 'dashwoo'),
					'type'      => \Elementor\Controls_Manager::SWITCHER,
					'default'   => '',
					'condition' => array( 'provider' => 'material-symbols' ),
				)
			);

			$this->add_control(
				'grade',
				array(
					'label'     => __('Grade', 'dashwoo'),
					'type'      => \Elementor\Controls_Manager::SLIDER,
					'default'   => array( 'size' => 0 ),
					'range'     => array(
						'px' => array(
							'min'  => -25,
							'max'  => 200,
							'step' => 25,
						),
					),
					'condition' => array( 'provider' => 'material-symbols' ),
				)
			);

			$this->add_control(
				'optical_size',
				array(
					'label'     => __('Optical Size', 'dashwoo'),
					'type'      => \Elementor\Controls_Manager::SLIDER,
					'default'   => array( 'size' => $schema['opsz']['default'] ),
					'range'     => array(
						'px' => array(
							'min'  => 20,
							'max'  => 48,
							'step' => 1,
						),
					),
					'condition' => array( 'provider' => 'material-symbols' ),
				)
			);

			$this->end_controls_section();
		}

		// --- Style -------------------------------------------------------------
		$this->start_controls_section(
			'style',
			array(
				'label' => __( 'Style', 'dashwoo' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'size',
			array(
				'label'     => __( 'Size', 'dashwoo' ),
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'default'   => array( 'size' => $schema['size']['default'] ),
				'range'     => array(
					'px' => array(
						'min' => 8,
						'max' => 512,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .dw-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'color',
			array(
				'label'     => __( 'Color', 'dashwoo' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .dw-icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'dashwoo' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Getting started', 'dashwoo' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => __( 'Center', 'dashwoo' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'End', 'dashwoo' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .dw-icon-wrapper' => 'display:flex; justify-content:{{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build renderer args from saved settings (no Elementor dependency).
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	public function to_renderer_args( array $settings ) {
		$scalar = static function ( $value, $default ) {
			if ( is_array( $value ) && isset( $value['size'] ) ) {
				return $value['size'];
			}
			return ( null === $value || '' === $value ) ? $default : $value;
		};

		return array(
			'provider' => $settings['provider'] ?? 'material-symbols',
			'icon'     => $settings['icon'] ?? 'star',
			'size'     => $scalar( $settings['size'] ?? null, dashwoo_get_setting( 'icons.size', 24 ) ),
			'weight'   => $scalar( $settings['weight'] ?? null, dashwoo_get_setting( 'icons.weight', 400 ) ),
			'fill'     => 'yes' === ( $settings['fill'] ?? '' ) ? 1 : $scalar( $settings['fill'] ?? null, 0 ),
			'grade'    => $scalar( $settings['grade'] ?? null, 0 ),
			'opsz'     => $scalar( $settings['optical_size'] ?? null, dashwoo_get_setting( 'icons.optical_size', 24 ) ),
			'color'    => $settings['color'] ?? dashwoo_get_setting( 'icons.color', 'currentColor' ),
			'position' => $settings['position'] ?? dashwoo_get_setting( 'icon_widget.default_position', 'before' ),
		);
	}

	/**
	 * Front-end render.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$args     = $this->to_renderer_args( is_array( $settings ) ? $settings : array() );
		$markup   = Icon_Renderer::render( $args );

		if ( '' === $markup ) {
			return;
		}

		$url      = isset( $settings['link']['url'] ) ? $settings['link']['url'] : '';
		$is_blank = ! empty( $settings['link']['is_external'] );
		$nofollow = ! empty( $settings['link']['nofollow'] );

		echo '<div class="dw-icon-wrapper">';

		if ( $url ) {
			printf(
				'<a href="%1$s"%2$s%3$s>%4$s</a>',
				esc_url( $url ),
				$is_blank ? ' target="_blank" rel="noopener"' : '',
				$nofollow ? ' rel="nofollow"' : '',
				$markup // phpcs:ignore WordPress.Security.EscapeOutput
			);
		} else {
			echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '</div>';
	}

	/**
	 * Installed styles (used by the editor notice when a font is missing).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function installed_styles() {
		return Icon_Manager::instance()->all( array( 'status' => 'active' ) );
	}
}
