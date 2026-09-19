<?php
/**
 * Plugin container + boot sequence.
 *
 * @package DashWoo
 */

namespace DashWoo;

use DashWoo\Admin\Admin;
use DashWoo\Assets\Asset_Manager;
use DashWoo\Assets\Fonts\Font_Manager;
use DashWoo\Assets\Icons\Icon_Manager;
use DashWoo\Cache\Cache;
use DashWoo\Compatibility\Compatibility;
use DashWoo\DesignSystem\Compiler;
use DashWoo\DesignSystem\Elementor\Tokens_Integration;
use DashWoo\DesignSystem\Tokens;
use DashWoo\Rest\Router;
use DashWoo\Settings\Settings;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Small service container. Everything is lazy: nothing is instantiated until asked for.
 */
final class Plugin {

	/**
	 * Singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Resolved services.
	 *
	 * @var array<string,object>
	 */
	private $resolved = array();

	/**
	 * Boot state.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Entry point hooked on plugins_loaded.
	 *
	 * @return void
	 */
	public static function boot() {
		self::instance()->init();
	}

	/**
	 * Service definitions: id => callable factory.
	 *
	 * @return array<string,callable>
	 */
	private function definitions() {
		return array(
			'compatibility' => static function () {
				return Compatibility::instance();
			},
			'wc_features'   => static function () {
				return \DashWoo\Compatibility\WooCommerce_Features::instance();
			},
			'capabilities'  => static function () {
				return \DashWoo\Capabilities\Capabilities::instance();
			},
			'settings'      => static function () {
				return Settings::instance();
			},
			'logger'        => static function () {
				return Logger::instance();
			},
			'cache'         => static function () {
				return Cache::instance();
			},
			'assets'        => static function () {
				return Asset_Manager::instance();
			},
			'fonts'         => static function () {
				return Font_Manager::instance();
			},
			'icons'         => static function () {
				return Icon_Manager::instance();
			},
			'tokens'        => static function () {
				return Tokens::instance();
			},
			'compiler'      => static function () {
				return Compiler::instance();
			},
			'account'       => static function () {
				return \DashWoo\Account\Account::instance();
			},
			'elementor'     => static function () {
				return Tokens_Integration::instance();
			},
			'admin'         => static function () {
				return Admin::instance();
			},
			'rest'          => static function () {
				return Router::instance();
			},
		);
	}

	/**
	 * Resolve a service.
	 *
	 * @param string $id Service id.
	 * @return object
	 * @throws \InvalidArgumentException When the service is unknown.
	 */
	public function get( $id ) {
		if ( isset( $this->resolved[ $id ] ) ) {
			return $this->resolved[ $id ];
		}

		$definitions = $this->definitions();
		if ( ! isset( $definitions[ $id ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unknown DashWoo service: %s', $id ) );
		}

		$this->resolved[ $id ] = call_user_func( $definitions[ $id ] );

		return $this->resolved[ $id ];
	}

	/**
	 * Whether a service has already been resolved.
	 *
	 * @param string $id Service id.
	 * @return bool
	 */
	public function has( $id ) {
		$definitions = $this->definitions();

		return isset( $definitions[ $id ] ) || isset( $this->resolved[ $id ] );
	}

	/**
	 * Boot every subsystem.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( DASHWOO_TEXTDOMAIN, false, dirname( DASHWOO_BASENAME ) . '/languages' );

		// 1. Environment first: everything else may depend on the compatibility mode.
		$this->get( 'compatibility' )->boot();
		$this->get( 'wc_features' )->boot();
		$this->get( 'capabilities' )->boot();

		// 2. Settings + cache + logging.
		$this->get( 'settings' )->boot();
		$this->get( 'cache' )->boot();
		$this->get( 'logger' )->boot();

		// 3. Design system.
		$this->get( 'tokens' )->boot();
		$this->get( 'compiler' )->boot();

		// 4. Assets.
		$this->get( 'assets' )->boot();
		$this->get( 'fonts' )->boot();
		$this->get( 'icons' )->boot();

		// 5. Integrations.
		$this->get( 'account' )->boot();
		$this->get( 'elementor' )->boot();

		// 6. Interfaces.
		if ( is_admin() ) {
			$this->get( 'admin' )->boot();
		}
		$this->get( 'rest' )->boot();

		do_action( 'dashwoo_booted', $this );
	}

	/**
	 * Whether boot() already ran.
	 *
	 * @return bool
	 */
	public function is_booted() {
		return $this->booted;
	}

	/**
	 * Reset the container (tests only).
	 *
	 * @return void
	 */
	public function reset() {
		foreach ( $this->resolved as $service ) {
			if ( method_exists( $service, 'reset' ) ) {
				$service->reset();
			}
		}
		$this->resolved = array();
		$this->booted   = false;
	}

	/**
	 * Drop the singleton (tests only).
	 *
	 * @return void
	 */
	public static function destroy() {
		if ( self::$instance ) {
			self::$instance->reset();
		}
		self::$instance = null;
	}

	/**
	 * Shortcut: compatibility layer.
	 *
	 * @return Compatibility
	 */
	public function compatibility() {
		return $this->get( 'compatibility' );
	}

	/**
	 * Shortcut: settings.
	 *
	 * @return Settings
	 */
	public function settings() {
		return $this->get( 'settings' );
	}
}
