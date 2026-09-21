<?php
/**
 * Shared base for every DashWoo account widget.
 *
 * Elementor only loads these classes when it is active (the file bails out
 * otherwise), but all of the *logic* lives in DashWoo\Account\*, so the widgets
 * themselves stay thin: controls + a call to the renderer.
 *
 * Two things every DashWoo widget has, by design:
 *   - "What is shown": a switch for the element itself, a switch per part of
 *     it (icon, title, meta, badge, ...), role gating and device hiding;
 *   - "Unstyled": one switch that removes DashWoo's design from that single
 *     element, for a shop owner who styles it in Elementor.
 *
 * @package DashWoo
 */

namespace DashWoo\Widgets;

use DashWoo\Account\Endpoints;
use DashWoo\Account\Renderer;
use DashWoo\Account\Source_Adapter;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Base widget.
 */
abstract class Account_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Elementor category every DashWoo widget lives in (one group, one name).
	 */
	const CATEGORY = 'dashwoo';

	/**
	 * Which renderer method this widget calls.
	 *
	 * @return string
	 */
	abstract protected function view();

	/**
	 * Elementor category.
	 *
	 * One category - «DashWoo» - so the panel never shows two DashWoo groups. A shop
	 * that wants its own grouping can filter this.
	 *
	 * @return array<int,string>
	 */
	public function get_categories() {
		/**
		 * Filter the Elementor category of a DashWoo widget.
		 *
		 * @param array<int,string> $categories Categories.
		 */
		return (array) apply_filters( 'dashwoo_widget_categories', array( self::CATEGORY ) );
	}

	/**
	 * Keywords for the Elementor search box (Persian + English).
	 *
	 * @return array<int,string>
	 */
	public function get_keywords() {
		return array( 'dashwoo', 'woocommerce', 'account', __( 'Account', 'dashwoo' ), __( 'user', 'dashwoo' ), __( 'WooCommerce', 'dashwoo' ) );
	}

	/**
	 * No Elementor Pro features, no external scripts.
	 *
	 * @return array<int,string>
	 */
	public function get_script_depends() {
		return array();
	}

	/**
	 * Which parts of this widget the shop owner may switch off.
	 *
	 * @return array<string,mixed> Part => default on/off.
	 */
	protected function visibility_spec() {
		return array();
	}

	/**
	 * Which style-kit extras this widget uses (nav, panel, dashboard...).
	 *
	 * @return array<int,string>
	 */
	protected function style_spec() {
		return array();
	}

	/**
	 * Register the shared style section.
	 *
	 * @return void
	 */
	protected function register_style_controls() {
		if ( ! method_exists( $this, 'start_controls_section' ) ) {
			return;
		}

		Style_Controls::register( $this, $this->style_spec() );
	}

	/**
	 * Register the "what shows" section.
	 *
	 * @param string $label Section label.
	 * @return void
	 */
	protected function register_visibility_controls( $label = '' ) {
		if ( ! method_exists( $this, 'start_controls_section' ) ) {
			return;
		}

		$label = '' !== $label ? $label : __( 'What to display', 'dashwoo' );

		$this->start_controls_section(
			'dw_visibility',
			array( 'label' => $label )
		);

		$this->add_control(
			'dw_visible',
			array(
				'label'   => __( 'Show this element', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$parts = array(
			'icon'   => __( 'Icons', 'dashwoo' ),
			'title'  => __( 'Title', 'dashwoo' ),
			'meta'   => __( 'Meta next to the title (count, date…)', 'dashwoo' ),
			'badge'  => __( 'Counter', 'dashwoo' ),
			'desc'   => __( 'Short description', 'dashwoo' ),
			'avatar' => __( 'Avatar', 'dashwoo' ),
			'arrow'  => __( 'Arrows', 'dashwoo' ),
			'action' => __( 'Action button', 'dashwoo' ),
		);

		foreach ( $this->visibility_spec() as $part => $default ) {
			if ( ! isset( $parts[ $part ] ) ) {
				continue;
			}

			$this->add_control(
				'dw_show_' . $part,
				array(
					'label'   => $parts[ $part ],
					'type'    => 'switcher',
					'default' => ! empty( $default ) ? 'yes' : '',
				)
			);
		}

		$this->add_control(
			'dw_roles',
			array(
				'label'   => __( 'For whom', 'dashwoo' ),
				'type'    => 'select',
				'default' => 'all',
				'options' => array(
					'all'           => __( 'Everybody', 'dashwoo' ),
					'logged_in'     => __( 'Signed-in customers only', 'dashwoo' ),
					'administrator' => __( 'Site administrators only', 'dashwoo' ),
				),
			)
		);

		$this->add_control(
			'dw_hide_on',
			array(
				'label'       => __( 'Hide on', 'dashwoo' ),
				'type'        => 'select',
				'default'     => '',
				'options'     => array(
					''        => __( 'Show everywhere', 'dashwoo' ),
					'desktop' => __( 'Desktop only (hidden on tablet and mobile)', 'dashwoo' ),
					'tablet'  => __( 'Hidden on tablet', 'dashwoo' ),
					'mobile'  => __( 'Hidden on mobile', 'dashwoo' ),
				),
				'description' => __( 'This option needs a little CSS; with “Automatic DashWoo CSS” switched off you write it yourself with the dw-hide-* classes.', 'dashwoo' ),
			)
		);

		$this->add_control(
			'dw_bare',
			array(
				'label'       => __( 'No DashWoo styling (this element only)', 'dashwoo' ),
				'type'        => 'switcher',
				'default'     => '',
				'description' => __( 'Turn it on and this element alone takes no DashWoo styling: the whole design is yours.', 'dashwoo' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * A repeater for a list widget (menu items, tabs, shortcuts...).
	 *
	 * @param string              $id          Control id.
	 * @param string              $label       Label.
	 * @param string              $description Description.
	 * @return void
	 */
	protected function register_items_control( $id = 'dw_items', $label = '', $description = '' ) {
		if ( ! method_exists( $this, 'add_control' ) || ! class_exists( '\Elementor\Repeater' ) ) {
			return;
		}

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'id',
			array(
				'label'   => __( 'Section', 'dashwoo' ),
				'type'    => 'select',
				'options' => $this->item_options(),
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label'       => __( 'Custom label', 'dashwoo' ),
				'type'        => 'text',
				'description' => __( 'Leave it empty to use the label from WooCommerce or from the settings.', 'dashwoo' ),
			)
		);

		$repeater->add_control(
			'icon',
			array(
				'label'       => __( 'Icon (icon name)', 'dashwoo' ),
				'type'        => 'text',
				'description' => __( 'For example: receipt_long — empty = the section\'s default icon.', 'dashwoo' ),
			)
		);

		$repeater->add_control(
			'visible',
			array(
				'label'   => __( 'Show this item', 'dashwoo' ),
				'type'    => 'switcher',
				'default' => 'yes',
			)
		);

		$repeater->add_control(
			'badge',
			array(
				'label'       => __( 'Manual counter', 'dashwoo' ),
				'type'        => 'number',
				'default'     => '',
				'description' => __( 'Empty = the real counter (orders/downloads).', 'dashwoo' ),
			)
		);

		$repeater->add_control(
			'url',
			array(
				'label'       => __( 'Custom link', 'dashwoo' ),
				'type'        => 'url',
				'description' => __( 'For items outside the account area (such as “Support”).', 'dashwoo' ),
			)
		);

		$this->add_control(
			$id,
			array(
				'label'       => $label,
				'type'        => 'repeater',
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
				'description' => $description,
			)
		);
	}

	/**
	 * Options of the item repeater: every account section WooCommerce knows.
	 *
	 * @return array<string,string>
	 */
	protected function item_options() {
		$options = array( '' => __( '— Select —', 'dashwoo' ) );

		foreach ( Endpoints::instance()->wire() as $id => $item ) {
			$options[ (string) $id ] = (string) $item['label'];
		}

		return $options;
	}

	/**
	 * Read one setting with a default.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	protected function setting( $key, $default ) {
		$settings = method_exists( $this, 'get_settings_for_display' ) ? (array) $this->get_settings_for_display() : array();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Is a part switched on? (used by the widgets' view args)
	 *
	 * @param string $part    Part key (icon, title, meta, badge...).
	 * @param bool   $default Default when the control is absent.
	 * @return bool
	 */
	protected function shows( $part, $default = true ) {
		$spec = $this->visibility_spec();

		if ( ! isset( $spec[ $part ] ) ) {
			return (bool) $default;
		}

		return 'yes' === (string) $this->setting( 'dw_show_' . $part, ! empty( $spec[ $part ] ) ? 'yes' : '' );
	}

	/**
	 * The style overrides Elementor collected, in the shape the renderer wants.
	 *
	 * @return array<string,mixed>
	 */
	protected function style_args() {
		$settings = method_exists( $this, 'get_settings_for_display' ) ? (array) $this->get_settings_for_display() : array();

		$args = Style_Controls::args( $settings, array_merge( array( 'all' ), $this->style_spec() ) );

		if ( ! empty( $args['vars'] ) ) {
			// Only the overrides travel to the markup; the base values keep coming from
			// the settings screen / design tokens.
			$args['css'] = Renderer::styles( array( 'vars' => $args['vars'] ) );
		}

		$classes = array();

		if ( ! empty( $args['class'] ) ) {
			$classes[] = (string) $args['class'];
		}

		$hide = sanitize_key( (string) $this->setting( 'dw_hide_on', '' ) );

		if ( '' !== $hide && in_array( $hide, array( 'desktop', 'tablet', 'mobile' ), true ) ) {
			$classes[] = 'dw-hide-' . $hide;
		}

		if ( 'yes' === (string) $this->setting( 'dw_bare', '' ) ) {
			$classes[] = 'dw-acc--bare';
		}

		if ( $classes ) {
			$args['class'] = implode( ' ', $classes );
		}

		return $args;
	}

	/**
	 * Everything a control adds is forwarded to the renderer.
	 *
	 * @param array<int,string> $keys Control ids.
	 * @return array<string,mixed>
	 */
	protected function args_from( array $keys ) {
		$settings = method_exists( $this, 'get_settings_for_display' ) ? (array) $this->get_settings_for_display() : array();
		$args     = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$args[ $key ] = $settings[ $key ];
			}
		}

		return array_merge( $args, $this->style_args() );
	}

	/**
	 * Role / visibility gate.
	 *
	 * @return bool
	 */
	protected function is_visible() {
		if ( 'yes' !== (string) $this->setting( 'dw_visible', 'yes' ) ) {
			return false;
		}

		$role = sanitize_key( (string) $this->setting( 'dw_roles', 'all' ) );

		if ( 'logged_in' === $role ) {
			return function_exists( 'is_user_logged_in' ) && is_user_logged_in();
		}

		if ( 'administrator' === $role ) {
			return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
		}

		return true;
	}

	/**
	 * Render through the shared renderer, with the availability guard in front.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! $this->is_visible() ) {
			return;
		}

		$state = Renderer::availability();

		if ( ! $state['ready'] ) {
			if ( 'guest' === $state['state'] && ! $this->is_editor_request() ) {
				echo Renderer::open( $this->style_args() ) . Renderer::login_prompt() . Renderer::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				return;
			}

			$editor = $this->is_editor_request();

			echo Renderer::open( $this->style_args() ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				. Renderer::notice( $state, array( 'editor' => $editor ) )
				. Renderer::close();

			return;
		}

		$method = $this->view();
		$args   = $this->view_args();

		if ( ! method_exists( Renderer::class, $method ) ) {
			return;
		}

		$html = (string) call_user_func( array( Renderer::class, $method ), $args );

		if ( '' === $html ) {
			return;
		}

		echo Renderer::open( $this->style_args() ) . $html . Renderer::close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Args for the view method (widgets override this).
	 *
	 * @return array<string,mixed>
	 */
	protected function view_args() {
		return array();
	}

	/**
	 * Are we inside the Elementor editor / preview?
	 *
	 * @return bool
	 */
	protected function is_editor_request() {
		// The bridge owns this decision (edit mode, preview mode, editor iframes), so a
		// widget can never disagree with the rest of the pack about "are we in the builder".
		if ( class_exists( '\\DashWoo\\Account\\Elementor_Bridge' ) ) {
			return \DashWoo\Account\Elementor_Bridge::is_editor();
		}

		return false;
	}

	/**
	 * The account source adapter (kept for subclasses that need it).
	 *
	 * @return Source_Adapter
	 */
	protected function source() {
		return Source_Adapter::instance();
	}
}
