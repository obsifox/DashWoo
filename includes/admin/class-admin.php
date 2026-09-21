<?php
/**
 * Admin UI: Settings Center, Asset Manager, System Status, Logs, Export.
 *
 * @package DashWoo
 */

namespace DashWoo\Admin;

use DashWoo\Assets\Asset_Manager;
use DashWoo\Assets\Fonts\Font_Manager;
use DashWoo\Assets\Fonts\Google_Fonts_Provider;
use DashWoo\Assets\Icons\Icon_Manager;
use DashWoo\Assets\Icons\Material_Provider;
use DashWoo\Cache\Cache;
use DashWoo\Compatibility\Compatibility;
use DashWoo\DesignSystem\Compiler;
use DashWoo\DesignSystem\Elementor\Tokens_Integration;
use DashWoo\Settings\Sections;
use DashWoo\Settings\Settings;
use DashWoo\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller.
 */
final class Admin {

	const SLUG = 'dashwoo';

	/**
	 * Singleton.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Notice to render on the next page load.
	 *
	 * @var array<string,string>|null
	 */
	private $notice = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Admin
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
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_dashwoo_action', array( $this, 'handle_action' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );
	}

	/**
	 * Admin menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			'DashWoo',
			'DashWoo',
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-art',
			58
		);

		// One submenu per navigation group; the sections inside a group are tabs
		// on that screen (see partial-nav.php), never one long flat list.
		add_submenu_page( self::SLUG, __( 'Dashboard', 'dashwoo' ), __( 'Dashboard', 'dashwoo' ), 'manage_options', self::SLUG, array( $this, 'render' ) );

		foreach ( Sections::groups() as $group => $meta ) {
			if ( 'home' === $group ) {
				continue;
			}

			$slug = ! empty( $meta['page'] ) ? $meta['page'] : self::SLUG . '&section=' . $meta['slug'];

			add_submenu_page(
				self::SLUG,
				$meta['label'],
				$meta['label'],
				'manage_options',
				$slug,
				array( $this, 'assets' === $group ? 'render_assets' : 'render' )
			);
		}
	}

	/**
	 * Navigation model for the current screen.
	 *
	 * Groups are the submenus, clusters are the tab row inside a group and
	 * sections are the chips of the active cluster.
	 *
	 * @param string $section Active section (or screen key).
	 * @param string $screen  Screen kind: settings | assets | system.
	 * @return array<string,mixed>
	 */
	public function nav( $section = null, $screen = 'settings' ) {
		$section = null === $section ? $this->current_section() : $section;
		$tree    = Sections::tree();

		if ( 'assets' === $screen ) {
			$group  = 'assets';
			$active = 'assets-library';
		} elseif ( 'capabilities' === $screen ) {
			$group  = 'system';
			$active = 'capabilities';
		} elseif ( 'system-status' === $section ) {
			$group  = 'system';
			$active = 'system-status';
		} else {
			$group  = Sections::group_of( $section );
			$active = $section;
		}

		if ( ! isset( $tree[ $group ] ) ) {
			$group  = 'home';
			$active = 'dashboard';
		}

		$groups = array();

		foreach ( $tree as $key => $meta ) {
			$groups[ $key ] = array(
				'label'  => $meta['label'],
				'icon'   => $meta['icon'],
				'url'    => $this->group_url( $key ),
				'active' => $key === $group,
			);
		}

		$clusters       = array();
		$active_cluster = $tree[ $group ]['clusters'][0]['key'];
		$crumb          = array( $tree[ $group ]['label'] );

		foreach ( $tree[ $group ]['clusters'] as $cluster ) {
			$items = array();

			foreach ( $cluster['sections'] as $item ) {
				$items[] = array(
					'key'    => $item['key'],
					'label'  => $item['label'],
					'url'    => $this->url( $item['key'] ),
					'active' => $item['key'] === $active,
				);
			}

			$items = array_merge( $this->extra_tabs( $group, $cluster['key'], $active ), $items );

			if ( $this->cluster_has_active( $items ) ) {
				$active_cluster = $cluster['key'];
			}

			$clusters[] = array(
				'key'      => $cluster['key'],
				'label'    => $cluster['label'],
				'url'      => $items ? $items[0]['url'] : $this->group_url( $group ),
				'sections' => $items,
			);
		}

		foreach ( $clusters as $cluster ) {
			if ( $cluster['key'] !== $active_cluster ) {
				continue;
			}

			foreach ( $cluster['sections'] as $item ) {
				if ( ! empty( $item['active'] ) ) {
					$crumb[] = $item['label'];
				}
			}
		}

		return array(
			'group'          => $group,
			'groups'         => $groups,
			'clusters'       => $clusters,
			'active_cluster' => $active_cluster,
			'breadcrumb'     => $crumb,
		);
	}

	/**
	 * Screen-only tabs that do not come from the settings schema.
	 *
	 * @param string $group   Group key.
	 * @param string $cluster Cluster key.
	 * @param string $active  Active tab key.
	 * @return array<int,array<string,mixed>>
	 */
	private function extra_tabs( $group, $cluster, $active = '' ) {
		$tabs = array();

		if ( 'assets' === $group && 'library' === $cluster ) {
			array_unshift(
				$tabs,
				array(
					'key'    => 'assets-library',
					'label'  => __( 'Media Library', 'dashwoo' ),
					'url'    => admin_url( 'admin.php?page=' . self::SLUG . '-assets' ),
					'active' => 'assets-library' === $active,
				)
			);
		}

		if ( 'system' === $group && 'basic' === $cluster ) {
			$tabs[] = array(
				'key'    => 'system-status',
				'label'  => __( 'System status', 'dashwoo' ),
				'url'    => $this->url( 'system' ),
				'active' => 'system-status' === $active,
			);
		}

		if ( 'system' === $group && 'capability' === $cluster ) {
			array_unshift(
				$tabs,
				array(
					'key'    => 'capabilities',
					'label'  => __( 'Host capabilities', 'dashwoo' ),
					'url'    => $this->url( 'capabilities' ),
					'active' => 'capabilities' === $active,
				)
			);
		}

		return $tabs;
	}

	/**
	 * Whether one of the tabs of a cluster is the active one.
	 *
	 * @param array<int,array<string,mixed>> $items Tabs.
	 * @return bool
	 */
	private function cluster_has_active( array $items ) {
		foreach ( $items as $item ) {
			if ( ! empty( $item['active'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * URL of a navigation group (its first section, or its own screen).
	 *
	 * @param string $group Group key.
	 * @return string
	 */
	public function group_url( $group ) {
		$groups = Sections::groups();

		if ( ! isset( $groups[ $group ] ) ) {
			return $this->url( 'dashboard' );
		}

		if ( ! empty( $groups[ $group ]['page'] ) ) {
			return admin_url( 'admin.php?page=' . $groups[ $group ]['page'] );
		}

		return $this->url( 'home' === $group ? 'dashboard' : $groups[ $group ]['slug'] );
	}

	/**
	 * Requested section (default dashboard).
	 *
	 * @return string
	 */
	public function current_section() {
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification
		$all     = Sections::all();

		if ( in_array( $section, array( 'system', 'assets', 'capabilities' ), true ) ) {
			return $section;
		}

		return isset( $all[ $section ] ) ? $section : 'dashboard';
	}

	/**
	 * Main page router.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('Access denied.', 'dashwoo') );
		}

		$section = $this->current_section();
		$screen  = 'settings';

		switch ( $section ) {
			case 'system':
				$screen = 'system-status';
				break;

			case 'capabilities':
				$screen = 'capabilities';
				break;

			case 'dashboard':
				$screen = 'dashboard';
				break;

			case 'logs':
				$screen = 'logs';
				break;
		}

		$context = $this->context( $section, $screen );

		switch ( $section ) {
			case 'system':
				$this->view( 'system-status', $context );
				break;

			case 'capabilities':
				$this->view( 'capabilities', $context );
				break;

			case 'dashboard':
				$this->view( 'dashboard', $context );
				break;

			case 'logs':
				$this->view( 'logs', $context );
				break;

			default:
				$this->view( 'settings', $context );
		}
	}

	/**
	 * Asset manager screen.
	 *
	 * @return void
	 */
	public function render_assets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('Access denied.', 'dashwoo') );
		}

		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'font'; // phpcs:ignore WordPress.Security.NonceVerification
		$context = $this->context( 'assets', 'assets' );
		$context['type']  = in_array( $type, array( 'font', 'icon', 'image', 'svg', 'custom' ), true ) ? $type : 'font';
		$context['rows']  = Asset_Manager::instance()->all( array( 'type' => $context['type'], 'per_page' => 100 ) );
		$context['stats'] = Asset_Manager::instance()->stats();

		$this->view( 'assets', $context );
	}

	/**
	 * Shared view context.
	 *
	 * @param string $section Section.
	 * @return array<string,mixed>
	 */
	public function context( $section, $screen = 'settings' ) {
		return array(
			'section'       => $section,
			'screen'        => $screen,
			'nav'           => $this->nav( 'system-status' === $screen ? 'system-status' : $section, $screen ),
			'settings'      => Settings::instance()->all(),
			'sections'      => Sections::all(),
			'groups'        => Sections::by_group(),
			'group_labels'  => Sections::groups(),
			'compatibility' => Compatibility::instance()->stored(),
			'diagnostics'   => Compatibility::instance()->diagnostics(),
			'capabilities'  => \DashWoo\Capabilities\Capabilities::instance()->status(),
			'wc_features'   => \DashWoo\Compatibility\WooCommerce_Features::instance()->status(),
			'mode'          => Compatibility::instance()->mode(),
			'storage'       => Asset_Manager::instance()->stats(),
			'fonts'         => Font_Manager::instance()->all(),
			'icons'         => Icon_Manager::instance()->all(),
			'logs'          => Logger::instance()->all( 60 ),
			'compiled'      => Compiler::instance()->compiled(),
			'notice'        => $this->notice,
			'catalog'       => Google_Fonts_Provider::instance()->catalog(),
			'icon_styles'   => Material_Provider::instance()->styles(),
			'action_url'    => admin_url( 'admin-post.php' ),
		);
	}

	/**
	 * Load a view file.
	 *
	 * @param string              $name    View name (without .php).
	 * @param array<string,mixed> $context Context.
	 * @return void
	 */
	public function view( $name, array $context = array() ) {
		$file = DASHWOO_INCLUDES . 'admin/views/' . sanitize_file_name( $name ) . '.php';

		if ( ! is_readable( $file ) ) {
			printf( '<div class="wrap"><p>%s</p></div>', esc_html( 'View not found: ' . $name ) );
			return;
		}

		require $file;
	}

	/**
	 * Handle every admin-post action.
	 *
	 * @return void
	 */
	/**
	 * Every action key `dispatch()` understands.
	 *
	 * @return string[]
	 */
	public function actions() {
		return array(
			'save_settings',
			'reset_section',
			'install_font',
			'update_font',
			'delete_font',
			'default_font',
			'install_icons',
			'delete_icon',
			'capabilities_recheck',
			'recheck',
			'compile',
			'flush_cache',
			'kit_sync',
			'kit_revert',
			'clear_logs',
			'upload_font',
			'upload_asset',
		);
	}

	public function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('Access denied.', 'dashwoo') );
		}

		check_admin_referer( 'dashwoo_action' );

		$action  = isset( $_POST['dw_action'] ) ? sanitize_key( wp_unslash( $_POST['dw_action'] ) ) : '';
		$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : 'dashboard';
		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=' . self::SLUG );

		$result = $this->dispatch( $action );

		$redirect = add_query_arg(
			array(
				'dw_done' => $result['ok'] ? '1' : '0',
				'dw_msg'  => rawurlencode( $result['message'] ),
			),
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Execute one admin action.
	 *
	 * @param string $action Action key.
	 * @return array{ok:bool,message:string,data?:mixed}
	 */
	public function dispatch( $action ) {
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

		switch ( $action ) {
			case 'save_settings':
				$section = isset( $post['section'] ) ? sanitize_key( $post['section'] ) : '';
				$fields  = isset( $post['dw'] ) && is_array( $post['dw'] ) ? $post['dw'] : array();
				$saved   = Settings::instance()->save_section( $section, $fields );

				return array(
					'ok'      => (bool) $saved,
					'message' => $saved ? __( 'Settings saved.', 'dashwoo' ) : __( 'Unknown section.', 'dashwoo' ),
				);

			case 'reset_section':
				Settings::instance()->reset_section( isset( $post['section'] ) ? sanitize_key( $post['section'] ) : '' );

				return array(
					'ok'      => true,
					'message' => __( 'Section reset.', 'dashwoo' ),
				);

			case 'install_font':
				$family  = isset( $post['family'] ) ? sanitize_text_field( $post['family'] ) : '';
				$weights = isset( $post['weights'] ) ? array_map( 'sanitize_text_field', (array) $post['weights'] ) : array( '400' );
				$result  = Font_Manager::instance()->install_google( $family, $weights );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => sprintf( __( 'The font %s was downloaded and stored locally.', 'dashwoo' ), $result['label'] ),
					);

			case 'update_font':
				$result = Font_Manager::instance()->update( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => ! empty( $result['updated'] ) ? __( 'Font updated.', 'dashwoo' ) : __( 'The font is already up to date.', 'dashwoo' ),
					);

			case 'delete_font':
				$ok = Font_Manager::instance()->delete( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return array(
					'ok'      => $ok,
					'message' => $ok ? __( 'Font deleted.', 'dashwoo' ) : __( 'Font not found.', 'dashwoo' ),
				);

			case 'upload_font':
				$result = $this->handle_upload( 'font_file', 'font', isset( $post['label'] ) ? $post['label'] : 'Font' );

				return $result;

			case 'upload_asset':
				$result = $this->handle_upload(
					'asset_file',
					isset( $post['type'] ) ? sanitize_key( $post['type'] ) : 'image',
					isset( $post['label'] ) ? $post['label'] : ''
				);

				return $result;

			case 'default_font':
				$result = Font_Manager::instance();

				return array(
					'ok'      => (bool) $result->set_status( (string) ( $post['slug'] ?? '' ), true ),
					'message' => __( 'Default font set.', 'dashwoo' ),
				);

			case 'install_icons':
				$result = Icon_Manager::instance()->install( isset( $post['style'] ) ? sanitize_key( $post['style'] ) : 'outlined', ! empty( $post['force'] ) );

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => ! empty( $result['installed'] ) ? __( 'The icon font was downloaded (a single variable file).', 'dashwoo' ) : __( 'This style is already installed.', 'dashwoo' ),
					);

			case 'delete_icon':
				$ok = Icon_Manager::instance()->delete( isset( $post['slug'] ) ? sanitize_text_field( $post['slug'] ) : '' );

				return array(
					'ok'      => $ok,
					'message' => $ok ? __( 'Icon style removed.', 'dashwoo' ) : __( 'Style not found.', 'dashwoo' ),
				);

			case 'capabilities_recheck':
				$capabilities = \DashWoo\Capabilities\Capabilities::instance();
				$state        = $capabilities->refresh( true );
				$blocked      = 0;

				foreach ( (array) $state['features'] as $feature ) {
					if ( 'blocked' === $feature['state'] ) {
						$blocked++;
					}
				}

				return array(
					'ok'      => true,
					'message' => $blocked
						? sprintf( __( 'Check finished: %d features are switched off automatically because the host capability is missing.', 'dashwoo' ), $blocked )
						: __( 'Check finished: every DashWoo feature is active.', 'dashwoo' ),
				);

			case 'recheck':
				$report = Compatibility::instance()->refresh();

				return array(
					'ok'      => true,
					'message' => sprintf(
						__( 'Check finished: %d passed, %d warnings, %d errors.', 'dashwoo' ),
						(int) $report->summary()['ok'],
						(int) $report->summary()['warning'],
						(int) $report->summary()['error']
					),
				);

			case 'compile':
				$result = Compiler::instance()->recompile();

				return array(
					'ok'      => true,
					'message' => sprintf( __( 'The token CSS was compiled (%d variables, %s).', 'dashwoo' ), (int) $result['variables'], $result['file'] ),
				);

			case 'flush_cache':
				$gone = Cache::instance()->flush_all();

				return array(
					'ok'      => true,
					'message' => sprintf( __( '%d cache files were removed.', 'dashwoo' ), (int) $gone ),
				);

			case 'kit_sync':
				$result = Tokens_Integration::instance()->sync_kit();

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => sprintf( __( 'The Elementor kit was synced (%d colours).', 'dashwoo' ), (int) $result['colors'] ),
					);

			case 'kit_revert':
				$result = Tokens_Integration::instance()->revert_kit();

				return is_wp_error( $result )
					? array(
						'ok'      => false,
						'message' => $result->get_error_message(),
					)
					: array(
						'ok'      => true,
						'message' => __( 'The Elementor kit was restored from the backup.', 'dashwoo' ),
					);

			case 'clear_logs':
				Logger::instance()->clear();

				return array(
					'ok'      => true,
					'message' => __( 'The logs were cleared.', 'dashwoo' ),
				);

			default:
				return array(
					'ok'      => false,
					'message' => __( 'Unknown action.', 'dashwoo' ),
				);
		}
	}

	/**
	 * Activation compatibility screen (once).
	 *
	 * @return void
	 */
	public function activation_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = get_transient( 'dashwoo_activation_report' );

		if ( ! $report ) {
			return;
		}

		delete_transient( 'dashwoo_activation_report' );

		$summary = isset( $report['summary'] ) ? $report['summary'] : array();
		$class   = empty( $summary['error'] ) ? 'notice-success' : 'notice-error';

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>DashWoo</strong> — ';
		printf(
			/* translators: 1: ok, 2: warnings, 3: errors */
			esc_html__( __( 'Compatibility check: %1$d passed, %2$d warnings, %3$d errors.', 'dashwoo' ), 'dashwoo' ),
			(int) ( $summary['ok'] ?? 0 ),
			(int) ( $summary['warning'] ?? 0 ),
			(int) ( $summary['error'] ?? 0 )
		);
		printf(
			' <a href="%s">%s</a></p></div>',
			esc_url( admin_url( 'admin.php?page=dashwoo&section=system' ) ),
			esc_html__( __( 'View the full report', 'dashwoo' ), 'dashwoo' )
		);
	}

	/**
	 * Shared upload handler for fonts and assets.
	 *
	 * @param string $field File field name.
	 * @param string $type  Asset type.
	 * @param string $label Label.
	 * @return array{ok:bool,message:string}
	 */
	public function handle_upload( $field, $type, $label = '' ) {
		if ( empty( $_FILES[ $field ]['tmp_name'] ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'No file was selected.', 'dashwoo' ),
			);
		}

		$file  = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$label = sanitize_text_field( (string) $label );

		if ( function_exists( 'wp_check_filetype_and_ext' ) ) {
			$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ) );

			if ( empty( $check['ext'] ) ) {
				return array(
					'ok'      => false,
					'message' => __( 'This file extension is not allowed.', 'dashwoo' ),
				);
			}
		}

		$result = Asset_Manager::instance()->import_file(
			(string) $file['tmp_name'],
			array(
				'type'  => $type,
				'label' => '' !== $label ? $label : sanitize_file_name( (string) $file['name'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'ok'      => false,
				'message' => $result->get_error_message(),
			);
		}

		if ( 'font' === $type ) {
			Font_Manager::instance()->install_local(
				array(
					'family'   => '' !== $label ? $label : $result['slug'],
					'slug'     => $result['slug'],
					'provider' => 'local',
				),
				array(
					array(
						'path'   => (string) $result['meta']['path'],
						'weight' => '400',
						'style'  => 'normal',
					),
				)
			);
		}

		return array(
			'ok'      => true,
			'message' => sprintf( __( 'The file “%s” was saved.', 'dashwoo' ), $label ),
		);
	}

	/**
	 * URL helper for the tabs.
	 *
	 * @param string              $section Section key.
	 * @param array<string,mixed> $args    Extra query args.
	 * @return string
	 */
	public function url( $section, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG, 'section' => $section ), $args ), admin_url( 'admin.php' ) );
	}
}
