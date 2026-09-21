<?php
/**
 * REST API: dashwoo/v1
 *
 * @package DashWoo
 */

namespace DashWoo\Rest;

use DashWoo\Account\Panel;
use DashWoo\Assets\Asset_Manager;
use DashWoo\Assets\Fonts\Font_Manager;
use DashWoo\Assets\Fonts\Google_Fonts_Provider;
use DashWoo\Assets\Icons\Icon_Manager;
use DashWoo\Assets\Icons\Icon_Renderer;
use DashWoo\Assets\Icons\Material_Provider;
use DashWoo\Cache\Cache;
use DashWoo\Compatibility\Compatibility;
use DashWoo\DesignSystem\Compiler;
use DashWoo\DesignSystem\Elementor\Tokens_Integration;
use DashWoo\DesignSystem\Tokens;
use DashWoo\Settings\Sections;
use DashWoo\Settings\Settings;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * REST router.
 */
final class Router {

	const CACHE_TTL = 300;

	/**
	 * Registered routes (introspectable, used by tests + the docs endpoint).
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $routes = array();

	/**
	 * Singleton.
	 *
	 * @var Router|null
	 */
	private static $instance = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Router
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Namespace.
	 *
	 * @return string
	 */
	public function namespace_() {
		$namespace = (string) dashwoo_get_setting( 'rest_api.namespace', 'dashwoo/v1' );

		return '' !== $namespace ? $namespace : 'dashwoo/v1';
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Read permission.
	 *
	 * @return bool
	 */
	public function can_read() {
		return (bool) current_user_can( 'manage_options' );
	}

	/**
	 * Read permission for the account panel: any logged-in customer, because the
	 * panel draws *their own* account content. The REST cookie nonce (`X-WP-Nonce`)
	 * is what proves the request came from their browser session.
	 *
	 * A visitor who is not logged in gets the login prompt instead of data, so a
	 * guest can never read another customer's orders.
	 *
	 * @return bool
	 */
	public function can_read_account() {
		if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return false;
		}

		return (bool) current_user_can( 'read' );
	}

	/**
	 * Write permission (settings can lock it down).
	 *
	 * @return bool
	 */
	public function can_write() {
		if ( ! dashwoo_is_on( 'rest_api.enabled' ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register every route.
	 *
	 * @return array<int,array<string,mixed>> Registered routes.
	 */
	public function register_routes() {
		if ( ! dashwoo_is_on( 'rest_api.enabled' ) ) {
			return array();
		}

		$ns = $this->namespace_();

		$this->route( $ns, '/system', 'GET', 'system', array( $this, 'get_system' ) );
		$this->route( $ns, '/system/recheck', 'POST', 'system-recheck', array( $this, 'post_recheck' ) );
		$this->route( $ns, '/system/diagnostics', 'GET', 'system-diagnostics', array( $this, 'get_diagnostics' ) );
		$this->route( $ns, '/system/capabilities', 'GET', 'system-capabilities', array( $this, 'get_capabilities' ) );
		$this->route( $ns, '/system/capabilities/recheck', 'POST', 'system-capabilities-recheck', array( $this, 'post_capabilities_recheck' ) );
		$this->route( $ns, '/tokens', 'GET', 'tokens', array( $this, 'get_tokens' ) );
		$this->route( $ns, '/tokens/compile', 'POST', 'tokens-compile', array( $this, 'post_compile' ) );
		$this->route( $ns, '/settings', 'GET', 'settings', array( $this, 'get_settings' ) );
		$this->route( $ns, '/settings/export', 'GET', 'settings-export', array( $this, 'get_export' ) );
		$this->route( $ns, '/settings/import', 'POST', 'settings-import', array( $this, 'post_import' ) );
		$this->route( $ns, '/settings/(?P<section>[a-z_]+)', 'GET', 'settings-section', array( $this, 'get_section' ) );
		$this->route( $ns, '/settings/(?P<section>[a-z_]+)', 'POST', 'settings-section-save', array( $this, 'post_section' ) );
		$this->route( $ns, '/fonts', 'GET', 'fonts', array( $this, 'get_fonts' ) );
		$this->route( $ns, '/fonts/search', 'GET', 'fonts-search', array( $this, 'get_fonts_search' ) );
		$this->route( $ns, '/fonts', 'POST', 'fonts-install', array( $this, 'post_fonts' ) );
		$this->route( $ns, '/fonts/(?P<slug>[a-zA-Z0-9\-\_]+)/update', 'POST', 'font-update', array( $this, 'post_font_update' ) );
		$this->route( $ns, '/fonts/(?P<slug>[a-zA-Z0-9\-\_]+)/status', 'POST', 'font-status', array( $this, 'post_font_status' ) );
		$this->route( $ns, '/fonts/(?P<slug>[a-zA-Z0-9\-\_]+)', 'DELETE', 'font-delete', array( $this, 'delete_font' ) );
		$this->route( $ns, '/icons', 'GET', 'icons', array( $this, 'get_icons' ) );
		$this->route( $ns, '/icons', 'POST', 'icons-install', array( $this, 'post_icons' ) );
		$this->route( $ns, '/icons/render', 'GET', 'icons-render', array( $this, 'get_icon_render' ) );
		$this->route( $ns, '/icons/(?P<slug>[a-zA-Z0-9\-\_]+)', 'DELETE', 'icon-delete', array( $this, 'delete_icon' ) );
		$this->route( $ns, '/assets', 'GET', 'assets', array( $this, 'get_assets' ) );
		$this->route( $ns, '/assets/(?P<id>\d+)/preview', 'GET', 'asset-preview', array( $this, 'get_asset_preview' ) );
		$this->route( $ns, '/assets/(?P<id>\d+)/rename', 'POST', 'asset-rename', array( $this, 'post_asset_rename' ) );
		$this->route( $ns, '/assets/(?P<id>\d+)/status', 'POST', 'asset-status', array( $this, 'post_asset_status' ) );
		$this->route( $ns, '/assets/(?P<id>\d+)/default', 'POST', 'asset-default', array( $this, 'post_asset_default' ) );
		$this->route( $ns, '/assets/(?P<id>\d+)', 'DELETE', 'asset-delete', array( $this, 'delete_asset' ) );
		$this->route( $ns, '/cache/flush', 'POST', 'cache-flush', array( $this, 'post_cache_flush' ) );
		$this->route( $ns, '/kit/sync', 'POST', 'kit-sync', array( $this, 'post_kit_sync' ) );
		$this->route( $ns, '/kit/revert', 'POST', 'kit-revert', array( $this, 'post_kit_revert' ) );
		$this->route( $ns, '/account/panel', 'GET', 'account-panel', array( $this, 'get_account_panel' ), array( $this, 'can_read_account' ) );
		$this->route( $ns, '/logs', 'GET', 'logs', array( $this, 'get_logs' ) );
		$this->route( $ns, '/logs', 'DELETE', 'logs-clear', array( $this, 'delete_logs' ) );

		/**
		 * Fires after the DashWoo routes were registered.
		 *
		 * @param string $namespace REST namespace.
		 * @param Router $router    Router.
		 */
		do_action( 'dashwoo_rest_registered', $ns, $this );

		return $this->routes;
	}

	/**
	 * Register a single route and remember it.
	 *
	 * @param string   $namespace Namespace.
	 * @param string   $path      Path.
	 * @param string   $method    HTTP method.
	 * @param string   $id        Route id.
	 * @param callable $callback  Handler.
	 * @return void
	 */
	public function route( $namespace, $path, $method, $id, $callback, $permission = null ) {
		if ( ! is_array( $permission ) && ! is_string( $permission ) ) {
			$permission = ( 'GET' === $method ) ? array( $this, 'can_read' ) : array( $this, 'can_write' );
		}

		$this->routes[] = array(
			'id'     => $id,
			'method' => $method,
			'path'   => '/' . trim( $namespace . $path, '/' ),
		);

		if ( function_exists( 'register_rest_route' ) ) {
			register_rest_route(
				$namespace,
				$path,
				array(
					'methods'             => $method,
					'callback'            => $callback,
					'permission_callback' => $permission,
					'args'                => array(),
				)
			);
		}
	}

	/**
	 * Route table (docs + tests).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function routes() {
		if ( ! $this->routes ) {
			$this->register_routes();
		}

		return $this->routes;
	}

	/* ---------------------------------------------------------------------
	 * System
	 * ------------------------------------------------------------------ */

	/**
	 * GET /system
	 *
	 * @return array<string,mixed>
	 */
	public function get_system() {
		$compat = Compatibility::instance();
		$report = $compat->stored();

		return array(
			'plugin'        => array(
				'version' => DASHWOO_VERSION,
				'db'      => DASHWOO_DB_VERSION,
				'mode'    => $compat->mode(),
			),
			'compatibility' => $report,
			'storage'       => Asset_Manager::instance()->stats(),
			'tokens'        => array(
				'count' => count( Compiler::instance()->variables() ),
				'file'  => Compiler::instance()->compiled(),
			),
			'fonts'         => count( Font_Manager::instance()->all() ),
			'icons'         => count( Icon_Manager::instance()->all() ),
		);
	}

	/**
	 * GET /system/diagnostics
	 *
	 * Raw environment facts (extensions, ini limits, disable_functions, writability)
	 * so a shop owner can hand them to the host when something is missing.
	 *
	 * @return array<string,mixed>
	 */
	public function get_diagnostics() {
		return array(
			'diagnostics' => Compatibility::instance()->diagnostics(),
		);
	}

	/**
	 * GET /system/capabilities
	 *
	 * Host capabilities + the DashWoo features they automatically gate.
	 *
	 * @return array<string,mixed>
	 */
	public function get_capabilities() {
		return array(
			'capabilities' => \DashWoo\Capabilities\Capabilities::instance()->status(),
		);
	}

	/**
	 * POST /system/capabilities/recheck
	 *
	 * Re-probes the host and re-evaluates every gated feature.
	 *
	 * @return array<string,mixed>
	 */
	public function post_capabilities_recheck() {
		$capabilities = \DashWoo\Capabilities\Capabilities::instance();
		$capabilities->refresh( true );

		return array(
			'capabilities' => $capabilities->status(),
		);
	}

	/**
	 * POST /system/recheck
	 *
	 * @return array<string,mixed>
	 */
	public function post_recheck() {
		return Compatibility::instance()->refresh()->to_array();
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	/**
	 * GET /tokens
	 *
	 * @return array<string,mixed>
	 */
	public function get_tokens() {
		return array(
			'categories' => Tokens::instance()->categories(),
			'variables'  => Compiler::instance()->variables(),
			'compiled'   => Compiler::instance()->compiled(),
		);
	}

	/**
	 * POST /tokens/compile
	 *
	 * @return array<string,mixed>
	 */
	public function post_compile() {
		$result = Compiler::instance()->recompile();
		unset( $result['css'] );

		return $result;
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	/**
	 * GET /settings
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings() {
		return array(
			'schema'   => array(
				'groups'   => Sections::groups(),
				'sections' => Sections::all(),
			),
			'settings' => Settings::instance()->all(),
		);
	}

	/**
	 * GET /settings/export
	 *
	 * @return array<string,mixed>
	 */
	public function get_export() {
		return Settings::instance()->export();
	}

	/**
	 * POST /settings/import
	 *
	 * @param mixed $request REST request (payload under "settings").
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_import( $request = null ) {
		$payload = $this->payload( $request );
		$json    = isset( $payload['settings'] ) && is_array( $payload['settings'] )
			? wp_json_encode( $payload['settings'] )
			: wp_json_encode( $payload );

		$result = Settings::instance()->import_json( (string) $json );

		if ( empty( $result['ok'] ) ) {
			return new \WP_Error( 'dashwoo_import_failed', __('Import failed.', 'dashwoo'), array( 'status' => 400 ) );
		}

		Compiler::instance()->recompile();

		return $result;
	}

	/**
	 * GET /settings/{section}
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_section( $request = null ) {
		$section = $this->param( $request, 'section' );
		$all     = Sections::all();

		if ( ! isset( $all[ $section ] ) ) {
			return new \WP_Error( 'dashwoo_unknown_section', __('Unknown section.', 'dashwoo'), array( 'status' => 404 ) );
		}

		return array(
			'section' => $section,
			'fields'  => $all[ $section ]['fields'],
			'values'  => Settings::instance()->get( $section, array() ),
		);
	}

	/**
	 * POST /settings/{section}
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_section( $request = null ) {
		$section = $this->param( $request, 'section' );
		$all     = Sections::all();

		if ( ! isset( $all[ $section ] ) ) {
			return new \WP_Error( 'dashwoo_unknown_section', __('Unknown section.', 'dashwoo'), array( 'status' => 404 ) );
		}

		$payload = $this->payload( $request );
		$values  = isset( $payload['values'] ) && is_array( $payload['values'] ) ? $payload['values'] : $payload;
		$partial = ! empty( $payload['partial'] );
		$saved   = Settings::instance()->save_section( $section, $values, $partial );

		Compiler::instance()->recompile();

		return array(
			'section' => $section,
			'partial' => $partial,
			'saved'   => $saved,
		);
	}

	/* ---------------------------------------------------------------------
	 * Fonts
	 * ------------------------------------------------------------------ */

	/**
	 * GET /fonts
	 *
	 * @return array<string,mixed>
	 */
	public function get_fonts() {
		$fonts = Font_Manager::instance()->all();
		$out   = array();

		foreach ( $fonts as $row ) {
			$out[] = array(
				'id'         => $row['id'],
				'slug'       => $row['slug'],
				'label'      => $row['label'],
				'provider'   => $row['provider'],
				'version'    => $row['version'],
				'status'     => $row['status'],
				'is_default' => $row['is_default'],
				'weights'    => $row['meta']['weights'] ?? array(),
				'subsets'    => $row['meta']['subsets'] ?? array(),
				'faces'      => $row['meta']['faces'] ?? array(),
				'bytes'      => $row['size'],
				'updated_at' => $row['updated_at'],
			);
		}

		return array(
			'fonts'    => $out,
			'compiled' => Font_Manager::instance()->compiled(),
		);
	}

	/**
	 * GET /fonts/search
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function get_fonts_search( $request = null ) {
		$query = $this->param( $request, 'q' );

		$fonts = Google_Fonts_Provider::instance()->search(
			$query,
			array( 'persian' => $this->param( $request, 'persian' ) )
		);

		return array(
			'query'   => $query,
			'count'   => count( $fonts ),
			'results' => $fonts,
		);
	}

	/**
	 * POST /fonts  (install a Google family locally)
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_fonts( $request = null ) {
		$payload = $this->payload( $request );
		$family  = isset( $payload['family'] ) ? sanitize_text_field( (string) $payload['family'] ) : '';
		$weights = isset( $payload['weights'] ) ? array_map( 'strval', (array) $payload['weights'] ) : array( '400' );
		$subsets = isset( $payload['subsets'] ) ? array_map( 'sanitize_key', (array) $payload['subsets'] ) : array();

		$result = Font_Manager::instance()->install_google( $family, $weights, $subsets );

		return is_wp_error( $result ) ? $result : $result;
	}

	/**
	 * POST /fonts/{slug}/update
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_font_update( $request = null ) {
		return Font_Manager::instance()->update( $this->param( $request, 'slug' ) );
	}

	/**
	 * POST /fonts/{slug}/status
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function post_font_status( $request = null ) {
		$payload = $this->payload( $request );
		$status  = ! empty( $payload['status'] );

		return array(
			'slug' => $this->param( $request, 'slug' ),
			'ok'   => Font_Manager::instance()->set_status( $this->param( $request, 'slug' ), $status ),
		);
	}

	/**
	 * DELETE /fonts/{slug}
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function delete_font( $request = null ) {
		return array(
			'slug' => $this->param( $request, 'slug' ),
			'ok'   => Font_Manager::instance()->delete( $this->param( $request, 'slug' ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Icons
	 * ------------------------------------------------------------------ */

	/**
	 * GET /icons
	 *
	 * @return array<string,mixed>
	 */
	public function get_icons() {
		return array(
			'installed' => Icon_Manager::instance()->all(),
			'styles'    => Material_Provider::instance()->styles(),
			'axes'      => Material_Provider::instance()->axes( 'outlined' ),
		);
	}

	/**
	 * POST /icons  (install one variable-font style)
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_icons( $request = null ) {
		$payload = $this->payload( $request );
		$style   = isset( $payload['style'] ) ? sanitize_key( (string) $payload['style'] ) : 'outlined';

		return Icon_Manager::instance()->install( $style, ! empty( $payload['force'] ) );
	}

	/**
	 * GET /icons/render  (public preview used by the editor)
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function get_icon_render( $request = null ) {
		$args = array(
			'provider' => $this->param( $request, 'provider', 'material-symbols' ),
			'icon'     => $this->param( $request, 'icon', 'star' ),
			'size'     => $this->param( $request, 'size', 24 ),
			'weight'   => $this->param( $request, 'weight', 400 ),
			'fill'     => $this->param( $request, 'fill', 0 ),
			'grade'    => $this->param( $request, 'grade', 0 ),
			'opsz'     => $this->param( $request, 'opsz', 24 ),
			'color'    => $this->param( $request, 'color', 'currentColor' ),
		);

		return array(
			'args'            => Icon_Renderer::normalize( $args ),
			'html'            => Icon_Renderer::render( $args ),
			'variation'       => Icon_Renderer::variation_settings( $args ),
			'inline_style'    => Icon_Renderer::inline_style( $args ),
		);
	}

	/**
	 * DELETE /icons/{slug}
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function delete_icon( $request = null ) {
		return array(
			'slug' => $this->param( $request, 'slug' ),
			'ok'   => Icon_Manager::instance()->delete( $this->param( $request, 'slug' ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------ */

	/**
	 * GET /assets
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function get_assets( $request = null ) {
		$args = array(
			'type'     => $this->param( $request, 'type' ),
			'status'   => $this->param( $request, 'status' ),
			'search'   => $this->param( $request, 'search' ),
			'per_page' => (int) $this->param( $request, 'per_page', 50 ),
		);

		return array(
			'assets' => Asset_Manager::instance()->all( $args ),
			'stats'  => Asset_Manager::instance()->stats(),
		);
	}

	/**
	 * GET /assets/{id}/preview
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_asset_preview( $request = null ) {
		$preview = Asset_Manager::instance()->preview( (int) $this->param( $request, 'id' ) );

		if ( ! $preview ) {
			return new \WP_Error( 'dashwoo_asset_not_found', __('Asset not found.', 'dashwoo'), array( 'status' => 404 ) );
		}

		return $preview;
	}

	/**
	 * POST /assets/{id}/rename
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function post_asset_rename( $request = null ) {
		$payload = $this->payload( $request );

		return array(
			'ok' => Asset_Manager::instance()->rename( (int) $this->param( $request, 'id' ), (string) ( $payload['label'] ?? '' ) ),
		);
	}

	/**
	 * POST /assets/{id}/status
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function post_asset_status( $request = null ) {
		$payload = $this->payload( $request );

		return array(
			'ok' => Asset_Manager::instance()->set_status( (int) $this->param( $request, 'id' ), ! empty( $payload['status'] ) ),
		);
	}

	/**
	 * POST /assets/{id}/default
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function post_asset_default( $request = null ) {
		return array(
			'ok' => Asset_Manager::instance()->set_default( (int) $this->param( $request, 'id' ) ),
		);
	}

	/**
	 * DELETE /assets/{id}
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function delete_asset( $request = null ) {
		return array(
			'ok' => Asset_Manager::instance()->delete( (int) $this->param( $request, 'id' ) ),
		);
	}

	/* ---------------------------------------------------------------------
	 * Ops
	 * ------------------------------------------------------------------ */

	/**
	 * POST /cache/flush
	 *
	 * @return array<string,mixed>
	 */
	public function post_cache_flush() {
		return array(
			'deleted' => Cache::instance()->flush_all(),
		);
	}

	/**
	 * POST /kit/sync
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_kit_sync() {
		return Tokens_Integration::instance()->sync_kit();
	}

	/**
	 * POST /kit/revert
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function post_kit_revert() {
		$result = Tokens_Integration::instance()->revert_kit();

		return is_wp_error( $result ) ? $result : array( 'ok' => $result );
	}

	/**
	 * GET /logs
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function get_logs( $request = null ) {
		return array(
			'logs' => Logger::instance()->all( (int) $this->param( $request, 'limit', 50 ) ),
		);
	}

	/**
	 * DELETE /logs
	 *
	 * @return array<string,mixed>
	 */
	public function delete_logs() {
		Logger::instance()->clear();

		return array( 'ok' => true );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * One account-panel view (menu card + content card, swapped over AJAX).
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_account_panel( $request = null ) {
		$view     = sanitize_key( (string) $this->param( $request, 'view', '' ) );
		$order_id = max( 0, (int) $this->param( $request, 'order_id', 0 ) );

		if ( '' === $view ) {
			return new \WP_Error( 'dashwoo_panel_view', __('Unknown view.', 'dashwoo'), array( 'status' => 404 ) );
		}

		$data = Panel::instance()->payload(
			$view,
			array(
				'order_id' => $order_id,
			)
		);

		return $data;
	}

	/**
	 * Read a route parameter.
	 *
	 * @param mixed  $request Request (or null).
	 * @param string $key     Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function param( $request, $key, $default = '' ) {
		if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
			$value = $request->get_param( $key );

			return null === $value ? $default : $value;
		}
		if ( is_array( $request ) && isset( $request[ $key ] ) ) {
			return $request[ $key ];
		}

		return isset( $_GET[ $key ] ) ? wp_unslash( $_GET[ $key ] ) : $default; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * JSON body (or merged params).
	 *
	 * @param mixed $request Request.
	 * @return array<string,mixed>
	 */
	public function payload( $request ) {
		if ( is_object( $request ) && method_exists( $request, 'get_json_params' ) ) {
			$json = $request->get_json_params();

			if ( is_array( $json ) ) {
				return $json;
			}
		}
		if ( is_array( $request ) ) {
			return $request;
		}

		return (array) $_POST; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Reset for tests.
	 *
	 * @return void
	 */
	public function reset() {
		$this->routes = array();
	}
}
