<?php
/**
 * Template variations for every account section.
 *
 * The shop owner picks a *shape* per section (a list, a table, a timeline, ...) in
 * DashWoo → حساب کاربری → قالب‌ها, in the Elementor widget, or per shortcode
 * attribute. A theme can override any variation with a file, so a developer never
 * has to fight the plugin:
 *
 *     wp-content/themes/<child-theme>/dashwoo/account/orders--table.php
 *
 * Resolution order (first hit wins):
 *   1. `$args['template']` / `$args['variant']` (widget or shortcode)
 *   2. theme override file (folder from the `theme_folder` setting)
 *   3. the per-section setting saved by the shop owner
 *   4. the section default
 *
 * Nothing here talks to the database on its own: the sections still read through
 * WooCommerce's public API, this class only decides *which markup shape* is drawn.
 *
 * @package DashWoo
 */

namespace DashWoo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * Template resolver + renderer.
 */
class Templates {

	/**
	 * Setting that stores the per-section picks.
	 */
	const SECTION = 'account_templates';

	/**
	 * Section => variations (id => Persian label).
	 *
	 * `plain` exists everywhere on purpose: it is the *unstyled* markup the shop
	 * owner can style from zero, which is the opposite end of the "one-click look".
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function sections() {
		$templates = array(
			'nav'        => array(
				'label'      => 'منوی حساب',
				'default'    => 'menu',
				'variations' => array(
					'menu'  => 'فهرست عمودی',
					'tabs'  => 'نوار تب افقی',
					'cards' => 'کارت‌های میان‌بر',
					'rail'  => 'نوار آیکونی کنار',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'dashboard'  => array(
				'label'      => 'پیشخوان',
				'default'    => 'hero-cards',
				'variations' => array(
					'hero-cards' => 'کارت خوش‌آمد + میان‌برها',
					'hero'       => 'فقط کارت خوش‌آمد',
					'cards'      => 'فقط میان‌برها',
					'plain'      => 'بدون استایل (خام)',
				),
			),
			'orders'     => array(
				'label'      => 'سفارش‌ها',
				'default'    => 'cards',
				'variations' => array(
					'cards'    => 'کارت سفارش',
					'compact'  => 'فهرست جمع‌وجور',
					'table'    => 'جدول',
					'timeline' => 'خط زمانی',
					'plain'    => 'بدون استایل (خام)',
				),
			),
			'order'      => array(
				'label'      => 'جزئیات یک سفارش',
				'default'    => 'summary',
				'variations' => array(
					'summary' => 'خلاصه + اقلام',
					'table'   => 'جدول اقلام',
					'items'   => 'فقط اقلام',
					'plain'   => 'بدون استایل (خام)',
				),
			),
			'downloads'  => array(
				'label'      => 'دانلودها',
				'default'    => 'list',
				'variations' => array(
					'list'  => 'فهرست',
					'grid'  => 'شبکهٔ کارتی',
					'table' => 'جدول',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'addresses'  => array(
				'label'      => 'آدرس‌ها',
				'default'    => 'cards',
				'variations' => array(
					'cards' => 'کارت آدرس',
					'list'  => 'فهرست ساده',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'payment'    => array(
				'label'      => 'روش‌های پرداخت',
				'default'    => 'card',
				'variations' => array(
					'card'  => 'کارت',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'details'    => array(
				'label'      => 'جزئیات حساب',
				'default'    => 'card',
				'variations' => array(
					'card'  => 'کارت',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'profile'    => array(
				'label'      => 'کارت پروفایل',
				'default'    => 'card',
				'variations' => array(
					'card'    => 'کارت',
					'compact' => 'جمع‌وجور',
					'plain'   => 'بدون استایل (خام)',
				),
			),
			'forms'      => array(
				'label'      => 'فرم‌ها',
				'default'    => 'grid',
				'variations' => array(
					'grid'  => 'شبکه‌ای',
					'stack' => 'عمودی',
					'plain' => 'بدون استایل (خام)',
				),
			),
			'logout'     => array(
				'label'      => 'خروج از حساب',
				'default'    => 'button',
				'variations' => array(
					'button' => 'دکمه',
					'plain'  => 'لینک ساده (خام)',
				),
			),
		);

		/**
		 * Filter the template catalogue.
		 *
		 * @param array<string,array<string,mixed>> $templates Section templates.
		 */
		return (array) apply_filters( 'dashwoo_account_templates', $templates );
	}

	/**
	 * Does this section have template variations?
	 *
	 * @param string $section Section id.
	 * @return bool
	 */
	public static function supports( $section ) {
		$all = self::sections();

		return isset( $all[ $section ] );
	}

	/**
	 * Variations of one section (id => label).
	 *
	 * @param string $section Section id.
	 * @return array<string,string>
	 */
	public static function variations( $section ) {
		$all = self::sections();

		return isset( $all[ $section ]['variations'] ) ? (array) $all[ $section ]['variations'] : array();
	}

	/**
	 * Options for a settings select (variation id => label).
	 *
	 * @param string $section Section id.
	 * @return array<string,string>
	 */
	public static function section_options( $section, $with_default = false ) {
		$options = array();

		if ( $with_default ) {
			// An empty id means "whatever the default of this section is", so the shop
			// owner can always go back after trying another shape.
			$default = self::default_variation( $section );
			$label   = isset( self::variations( $section )[ $default ] ) ? (string) self::variations( $section )[ $default ] : $default;

			$options[''] = sprintf( 'پیش‌فرض (%s)', $label );
		}

		foreach ( self::variations( $section ) as $id => $label ) {
			$options[ (string) $id ] = (string) $label;
		}

		return $options;
	}

	/**
	 * Allowed variation ids (used to sanitise).
	 *
	 * @param string $section Section id.
	 * @return array<int,string>
	 */
	public static function ids( $section ) {
		return array_map( 'strval', array_keys( self::variations( $section ) ) );
	}

	/**
	 * The default variation of a section.
	 *
	 * @param string $section Section id.
	 * @return string
	 */
	public static function default_variation( $section ) {
		$all = self::sections();

		return isset( $all[ $section ]['default'] ) ? (string) $all[ $section ]['default'] : 'cards';
	}

	/**
	 * Which variation should be drawn for this section?
	 *
	 * @param string              $section Section id.
	 * @param array<string,mixed> $args    Args (template/variant, settings override).
	 * @return string
	 */
	public static function resolve( $section, array $args = array() ) {
		$requested = '';

		foreach ( array( 'template', 'variant', 'layout' ) as $key ) {
			if ( isset( $args[ $key ] ) && '' !== (string) $args[ $key ] ) {
				$requested = sanitize_key( (string) $args[ $key ] );
				break;
			}
		}

		$allowed = self::ids( $section );

		if ( '' !== $requested && in_array( $requested, $allowed, true ) ) {
			$resolved = $requested;
		} else {
			$saved = (string) dashwoo_get_setting( self::SECTION . '.' . $section, '' );

			if ( '' !== $saved && in_array( $saved, $allowed, true ) ) {
				$resolved = $saved;
			} else {
				$resolved = self::default_variation( $section );
			}
		}

		/**
		 * Filter the resolved template of one section.
		 *
		 * @param string              $resolved Variation id.
		 * @param string              $section  Section id.
		 * @param array<string,mixed> $args     Args.
		 */
		$resolved = (string) apply_filters( 'dashwoo_account_template', $resolved, $section, $args );

		return '' !== $resolved ? sanitize_key( $resolved ) : self::default_variation( $section );
	}

	/**
	 * Folder in the theme that can override templates.
	 *
	 * @return string
	 */
	public static function theme_folder() {
		$folder = (string) dashwoo_get_setting( 'account_templates.theme_folder', 'dashwoo/account' );
		$folder = trim( str_replace( array( '..', '\\' ), '', $folder ), '/' );

		/**
		 * Filter the theme template folder.
		 *
		 * @param string $folder Relative folder (no leading slash).
		 */
		return (string) apply_filters( 'dashwoo_account_template_folder', '' !== $folder ? $folder : 'dashwoo/account' );
	}

	/**
	 * Candidate files for a section/variation, in priority order.
	 *
	 * @param string $section   Section id.
	 * @param string $variation Variation id.
	 * @return array<int,string> Absolute paths (may not exist).
	 */
	public static function candidates( $section, $variation ) {
		$section   = sanitize_key( (string) $section );
		$variation = sanitize_key( (string) $variation );
		$folder    = self::theme_folder();

		$files = array();

		if ( function_exists( 'get_stylesheet_directory' ) ) {
			$stylesheet = (string) get_stylesheet_directory();
			$files[]    = $stylesheet . '/' . $folder . '/' . $section . '--' . $variation . '.php';
			$files[]    = $stylesheet . '/' . $folder . '/' . $section . '.php';
		}

		if ( function_exists( 'get_template_directory' ) ) {
			$template = (string) get_template_directory();
			$files[]  = $template . '/' . $folder . '/' . $section . '--' . $variation . '.php';
			$files[]  = $template . '/' . $folder . '/' . $section . '.php';
		}

		/**
		 * Filter the template file candidates.
		 *
		 * @param array<int,string> $files     Candidates.
		 * @param string            $section   Section id.
		 * @param string            $variation Variation id.
		 */
		return (array) apply_filters( 'dashwoo_account_template_candidates', $files, $section, $variation );
	}

	/**
	 * The override file that exists, if any.
	 *
	 * @param string $section   Section id.
	 * @param string $variation Variation id.
	 * @return string
	 */
	public static function locate( $section, $variation ) {
		foreach ( self::candidates( $section, $variation ) as $file ) {
			if ( '' !== (string) $file && @is_readable( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return (string) $file;
			}
		}

		return '';
	}

	/**
	 * Run a template file with the same args the built-in renderer gets.
	 *
	 * @param string              $file Absolute path.
	 * @param array<string,mixed> $args Args.
	 * @return string
	 */
	protected static function run( $file, array $args ) {
		ob_start();

		$dw_args = $args; // The name a theme template sees.

		include $file;

		return (string) ob_get_clean();
	}

	/**
	 * Render one section with the resolved template.
	 *
	 * @param string              $section Section id.
	 * @param array<string,mixed> $args    Args.
	 * @return string
	 */
	public static function render( $section, array $args = array() ) {
		$section   = sanitize_key( (string) $section );
		$variation = self::resolve( $section, $args );

		$args['variant']   = $variation;
		$args['section']   = $section;
		$args['templated'] = true;

		$file = self::locate( $section, $variation );

		if ( '' !== $file ) {
			$html = self::run( $file, $args );

			if ( '' !== trim( $html ) ) {
				/**
				 * Filter the markup of a section rendered through a theme template.
				 *
				 * @param string              $html    Markup.
				 * @param string              $section Section id.
				 * @param string              $variation Variation id.
				 * @param array<string,mixed> $args    Args.
				 */
				return (string) apply_filters( 'dashwoo_account_template_html', $html, $section, $variation, $args );
			}
		}

		/**
		 * Filter the built-in markup of a section ('' = keep it).
		 *
		 * @param string              $html      Markup (empty by default).
		 * @param string              $section   Section id.
		 * @param string              $variation Variation id.
		 * @param array<string,mixed> $args      Args.
		 */
		$custom = (string) apply_filters( 'dashwoo_account_template_html', '', $section, $variation, $args );

		if ( '' !== $custom ) {
			return $custom;
		}

		return self::built_in( $section, $variation, $args );
	}

	/**
	 * The markup DashWoo ships for a variation.
	 *
	 * @param string              $section   Section id.
	 * @param string              $variation Variation id.
	 * @param array<string,mixed> $args      Args.
	 * @return string
	 */
	public static function built_in( $section, $variation, array $args ) {
		switch ( $section ) {
			case 'nav':
				return Renderer::nav( $args );

			case 'dashboard':
				$args['parts'] = $variation;

				return Renderer::dashboard( $args );

			case 'profile':
				return Sections::profile( $args );

			case 'forms':
				return Sections::forms( $args );

			case 'logout':
				return Sections::block_customer_logout( $args );

			case 'order':
				return Sections::block_order( $args );
		}

		// Endpoint sections (orders, downloads, addresses, payment, details) draw
		// through their own block, which understands `variant`.
		$args['template'] = $variation;

		return Sections::block( self::endpoint_for( $section ), $args );
	}

	/**
	 * Endpoint id a section id maps to (identical for all but a few aliases).
	 *
	 * @param string $section Section id.
	 * @return string
	 */
	public static function endpoint_for( $section ) {
		$map = array(
			'addresses' => 'edit-address',
			'payment'   => 'payment-methods',
			'details'   => 'edit-account',
			'profile'   => 'dashboard',
		);

		return isset( $map[ $section ] ) ? $map[ $section ] : $section;
	}
}
