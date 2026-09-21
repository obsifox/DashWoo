<?php
/**
 * Elementor integration — three levels, from safest to most invasive.
 *
 *  1. CSS variables        : always on, Elementor only reads `var(--dw-*)`.
 *  2. Token picker control : a custom control type that outputs var() references.
 *  3. Global Kit sync      : optional, OFF by default, snapshot + revert.
 *
 * @package DashWoo
 */

namespace DashWoo\DesignSystem\Elementor;

use DashWoo\Cache\Cache;
use DashWoo\DesignSystem\Compiler;
use DashWoo\DesignSystem\Tokens;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor bridge.
 */
final class Tokens_Integration {

	const KIT_BACKUP_OPTION = 'dashwoo_kit_backup';
	const CATEGORY           = 'dashwoo';

	/**
	 * Singleton.
	 *
	 * @var Tokens_Integration|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Tokens_Integration
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks (only wired when Elementor is present).
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! dashwoo_is_on( 'general.elementor_enabled' ) ) {
			return;
		}

		// The DashWoo brand tab (marks) inside Elementor's icon library.
		if ( class_exists( __NAMESPACE__ . '\\Brand_Icons' ) ) {
			Brand_Icons::instance()->boot();
		}

		add_action( 'elementor/init', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_tokens' ), 5 );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_tokens' ), 5 );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'elementor/editor/localize_settings', array( $this, 'localize_tokens' ) );
	}

	/**
	 * Whether Elementor is usable right now.
	 *
	 * @return bool
	 */
	public function is_available() {
		return dashwoo_supports( 'elementor_active' );
	}

	/**
	 * Register the DashWoo widget category.
	 *
	 * @param mixed $manager Elementor category manager (or nothing on elementor/init).
	 * @return void
	 */
	public function register_category( $manager = null ) {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'add_category' ) ) {
			return;
		}

		$manager->add_category(
			self::CATEGORY,
			array(
				'title' => 'DashWoo',
				'icon'  => 'eicon-nerd',
			)
		);

		// Only ONE category is registered: the panel used to show "DashWoo" and a
		// second "DashWoo — حساب کاربری" group (the slug was added twice), which made
		// the widget list look like two products. A shop that wants the account widgets
		// in their own group can still ask for it - it is opt-in now.
		$extra = (array) apply_filters( 'dashwoo_elementor_extra_categories', array() );

		foreach ( $extra as $slug => $args ) {
			if ( ! is_array( $args ) ) {
				continue;
			}

			$manager->add_category( (string) $slug, $args );
		}
	}

	/**
	 * Register DashWoo widgets.
	 *
	 * @param mixed $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		if ( ! is_object( $widgets_manager ) || ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}

		// ONE list, in ONE place. This method used to carry a copy of the class list,
		// which is how the panel ended up able to disagree with itself (and why the
		// module registered a different set of widgets than the bridge did).
		$classes = class_exists( '\\DashWoo\\Account\\Elementor_Bridge' )
			? \DashWoo\Account\Elementor_Bridge::widget_classes()
			: array( '\\DashWoo\\Widgets\\Icon_Widget' );

		// The list is already filtered inside widget_classes() - filtering here again
		// would run every shop callback twice.
		foreach ( (array) $classes as $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/**
	 * Register the token picker control (level 2).
	 *
	 * @param mixed $controls_manager Elementor controls manager.
	 * @return void
	 */
	public function register_controls( $controls_manager ) {
		if ( ! is_object( $controls_manager ) || ! method_exists( $controls_manager, 'register' ) ) {
			return;
		}

		require_once DASHWOO_INCLUDES . 'design-system/elementor/class-token-picker-control.php';

		if ( class_exists( '\DashWoo\DesignSystem\Elementor\Token_Picker_Control' ) ) {
			$controls_manager->register( new Token_Picker_Control() );
		}
	}

	/**
	 * Enqueue the compiled tokens stylesheet inside Elementor (editor + frontend).
	 *
	 * @return void
	 */
	public function enqueue_tokens() {
		$compiled = Compiler::instance()->compiled();

		if ( empty( $compiled['url'] ) ) {
			return;
		}

		wp_enqueue_style( 'dashwoo-tokens', $compiled['url'], array(), $compiled['hash'] );
	}

	/**
	 * Editor-only script (token preview in the panel).
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'dashwoo-elementor',
			DASHWOO_URL . 'assets/js/elementor.js',
			array( 'elementor-editor' ),
			DASHWOO_VERSION,
			true
		);
	}

	/**
	 * Hand the token map to the editor so the picker can render swatches.
	 *
	 * @param array<string,mixed> $settings Localised settings.
	 * @return array<string,mixed>
	 */
	public function localize_tokens( $settings ) {
		$settings['dashwoo'] = array(
			'tokens'    => $this->token_payload(),
			'version'   => DASHWOO_VERSION,
			'level'     => dashwoo_get_setting( 'elementor.integration_level', 'variables' ),
			'kitSync'   => dashwoo_is_on( 'elementor.sync_kit' ),
			'widgetUrl' => esc_url_raw( admin_url( 'admin.php?page=dashwoo&section=icon_widget' ) ),
		);

		return $settings;
	}

	/**
	 * Token payload for JS (grouped, with resolved values).
	 *
	 * @return array<string,mixed>
	 */
	public function token_payload() {
		$tokens = Compiler::instance()->variables();
		$groups = array();

		foreach ( Tokens::instance()->categories() as $category => $definition ) {
			$groups[ $category ] = array(
				'label'  => $definition['label'],
				'tokens' => array(),
			);
		}

		foreach ( $tokens as $name => $value ) {
			$short  = substr( $name, strlen( Tokens::PREFIX ) );
			$bucket = 'other';

			foreach ( array_keys( $groups ) as $category ) {
				$needle = 'font-size' === $category ? 'font-size' : $category;
				if ( 0 === strpos( $short, $needle ) ) {
					$bucket = $category;
					break;
				}
			}

			if ( ! isset( $groups[ $bucket ] ) ) {
				$groups[ $bucket ] = array(
					'label'  => $bucket,
					'tokens' => array(),
				);
			}

			$groups[ $bucket ]['tokens'][] = array(
				'name'  => $name,
				'short' => $short,
				'value' => $value,
				'css'   => 'var(' . $name . ')',
			);
		}

		return $groups;
	}

	/**
	 * Level 3: push tokens into the Elementor Global Kit (opt-in).
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function sync_kit() {
		if ( ! $this->is_available() ) {
			return new \WP_Error( 'dashwoo_elementor_missing', 'Elementor is not available.' );
		}
		if ( ! dashwoo_is_on( 'elementor.sync_kit' ) ) {
			return new \WP_Error( 'dashwoo_kit_sync_off', 'Kit sync is disabled in the settings.' );
		}
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new \WP_Error( 'dashwoo_elementor_missing', 'Elementor Kit API is unavailable.' );
		}

		$kit_id = $this->active_kit_id();

		if ( ! $kit_id ) {
			return new \WP_Error( 'dashwoo_kit_missing', 'No active Elementor Kit found.' );
		}

		if ( dashwoo_is_on( 'elementor.kit_backup' ) ) {
			$this->backup_kit( $kit_id );
		}

		$colors = array();

		foreach ( Compiler::instance()->variables() as $name => $value ) {
			$short = substr( $name, strlen( Tokens::PREFIX ) );

			if ( 0 === strpos( $short, 'color-' ) ) {
				$colors[] = array(
					'_id'   => substr( md5( $name ), 0, 7 ),
					'title' => ucwords( str_replace( '-', ' ', substr( $short, 6 ) ) ),
					'color' => $value,
				);
			}
		}

		$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		$settings = is_array( $settings ) ? $settings : array();

		$settings['custom_colors']   = $colors;
		$settings['dashwoo_synced']  = gmdate( 'c' );
		$settings['dashwoo_version'] = DASHWOO_VERSION;

		update_post_meta( $kit_id, '_elementor_page_settings', $settings );

		Logger::instance()->info( 'Elementor Kit synced', array( 'kit' => $kit_id, 'colors' => count( $colors ) ) );

		return array(
			'kit'     => $kit_id,
			'colors'  => count( $colors ),
			'synced'  => gmdate( 'c' ),
			'backup'  => dashwoo_is_on( 'elementor.kit_backup' ),
		);
	}

	/**
	 * Restore the Kit snapshot taken before the last sync.
	 *
	 * @return bool|\WP_Error
	 */
	public function revert_kit() {
		$backup = get_option( self::KIT_BACKUP_OPTION, array() );

		if ( empty( $backup['kit'] ) || empty( $backup['settings'] ) ) {
			return new \WP_Error( 'dashwoo_kit_no_backup', 'No Kit backup available.' );
		}

		update_post_meta( (int) $backup['kit'], '_elementor_page_settings', $backup['settings'] );

		Logger::instance()->warning( 'Elementor Kit reverted', array( 'kit' => $backup['kit'] ) );

		return true;
	}

	/**
	 * Snapshot the current Kit settings.
	 *
	 * @param int $kit_id Kit post id.
	 * @return array<string,mixed>
	 */
	public function backup_kit( $kit_id ) {
		$settings = get_post_meta( (int) $kit_id, '_elementor_page_settings', true );

		$snapshot = array(
			'kit'      => (int) $kit_id,
			'settings' => is_array( $settings ) ? $settings : array(),
			'taken_at' => gmdate( 'c' ),
		);

		update_option( self::KIT_BACKUP_OPTION, $snapshot, false );

		return $snapshot;
	}

	/**
	 * Active Kit id.
	 *
	 * @return int
	 */
	public function active_kit_id() {
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->kits_manager ) ) {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

			if ( $kit && method_exists( $kit, 'get_id' ) ) {
				return (int) $kit->get_id();
			}
		}

		return (int) get_option( 'elementor_active_kit', 0 );
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		// No state kept between requests.
	}
}
