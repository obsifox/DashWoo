<?php
/**
 * Host capabilities and the DashWoo features that depend on them.
 *
 * DashWoo never *requires* php-gd, php-zip or a multisite network, so a missing
 * capability must never look like a broken store. Instead every optional DashWoo
 * feature declares what it needs:
 *
 *   - the host is probed on a schedule (and on demand),
 *   - a feature whose requirement is missing turns itself **offline**,
 *   - the moment the host gains the capability the feature comes back **on its own**,
 *   - the shop owner can always force a feature off, never on.
 *
 * @package DashWoo
 */

namespace DashWoo\Capabilities;

use DashWoo\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Capability probe + automatic feature gating.
 */
class Capabilities {

	/**
	 * Persisted probe + feature state.
	 */
	const OPTION = 'dashwoo_capabilities';

	/**
	 * How long a probe stays fresh.
	 */
	const TTL = 21600; // 6 hours.

	/**
	 * Singleton.
	 *
	 * @var Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Request cache of the persisted state.
	 *
	 * @var array<string,mixed>|null
	 */
	private $state = null;

	/**
	 * Singleton accessor.
	 *
	 * @return Capabilities
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Re-probe on a schedule and whenever an administrator is around.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', array( $this, 'maybe_refresh' ), 5, 0 );
		add_action( 'dashwoo_settings_saved', array( $this, 'flush' ), 10, 0 );
		add_action( 'switch_blog', array( $this, 'flush' ), 10, 0 );
	}

	/**
	 * The host facts DashWoo cares about.
	 *
	 * @return array<string,array<string,mixed>> Capability id => [label, available, detail, hint].
	 */
	public function checks() {
		$checks = array();

		$checks['gd'] = array(
			'label'     => __( 'GD image library (php-gd)', 'dashwoo' ),
			'available' => extension_loaded( 'gd' ) && function_exists( 'imagecreatetruecolor' ),
			'detail'    => extension_loaded( 'gd' )
				? ( function_exists( 'imagecreatetruecolor' )
					? __( 'loaded and usable', 'dashwoo' )
					: __( 'installed, but its image functions are blocked by disable_functions', 'dashwoo' ) )
				: __( 'the php-gd extension is not installed on this host', 'dashwoo' ),
			'hint'      => __( 'Optional. DashWoo uses it to convert images automatically (WebP/AVIF); everything else works without it.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['imagick'] = array(
			'label'     => __('Imagick (php-imagick)', 'dashwoo'),
			'available' => extension_loaded( 'imagick' ),
			'detail'    => extension_loaded( 'imagick' ) ? __( 'loaded', 'dashwoo' ) : __( 'not installed on this host', 'dashwoo' ),
			'hint'      => __( 'Optional. The GD alternative for image processing; either one of the two is enough.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['zip'] = array(
			'label'     => __('ZipArchive (php-zip)', 'dashwoo'),
			'available' => class_exists( 'ZipArchive' ) && function_exists( 'gzopen' ),
			'detail'    => class_exists( 'ZipArchive' ) ? __( 'Ready', 'dashwoo' ) : __( 'the php-zip extension is not installed on this host', 'dashwoo' ),
			'hint'      => __( 'Optional. Only needed for the compressed backup bundle; the JSON export always works.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['multisite'] = array(
			'label'     => __( 'WordPress multisite', 'dashwoo' ),
			'available' => is_multisite(),
			'detail'    => is_multisite() ? __( 'this site runs in a network', 'dashwoo' ) : __( 'this is a single-site install', 'dashwoo' ),
			'hint'      => __( 'Optional. DashWoo\'s network features are only available on a multisite install.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['dom'] = array(
			'label'     => __('DOM (php-xml)', 'dashwoo'),
			'available' => class_exists( 'DOMDocument' ),
			'detail'    => class_exists( 'DOMDocument' ) ? __( 'Ready', 'dashwoo' ) : __( 'the php-xml extension is not installed on this host', 'dashwoo' ),
			'hint'      => __( 'Used for strict SVG sanitising; without it DashWoo falls back to WordPress\' own sanitiser.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['mbstring'] = array(
			'label'     => 'mbstring',
			'available' => function_exists( 'mb_strlen' ),
			'detail'    => function_exists( 'mb_strlen' ) ? __( 'Ready', 'dashwoo' ) : __( 'not installed', 'dashwoo' ),
			'hint'      => __( 'Used to cut multibyte text correctly in the admin.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['webp'] = array(
			'label'     => __( 'WebP support', 'dashwoo' ),
			'available' => $this->gd_supports( 'imagewebp' ),
			'detail'    => $this->gd_supports( 'imagewebp' ) ? __( 'GD supports WebP', 'dashwoo' ) : __( 'not enabled in this host\'s GD', 'dashwoo' ),
			'hint'      => __( 'Optional. Improves the quality and the file size of compressed images.', 'dashwoo' ),
			'required'  => false,
		);

		$checks['avif'] = array(
			'label'     => __( 'AVIF support', 'dashwoo' ),
			'available' => $this->gd_supports( 'imageavif' ),
			'detail'    => $this->gd_supports( 'imageavif' ) ? __( 'GD supports AVIF', 'dashwoo' ) : __( 'not enabled in this host\'s GD', 'dashwoo' ),
			'hint'      => __( 'Optional and very new; nothing breaks without it.', 'dashwoo' ),
			'required'  => false,
		);

		$basedir = \DashWoo\Assets\Storage::instance()->basedir();

		$checks['uploads_writable'] = array(
			'label'     => __( 'Asset folder is writable', 'dashwoo' ),
			'available' => wp_is_writable( $basedir ),
			'detail'    => wp_is_writable( $basedir )
				? __( 'uploads/dashwoo is writable', 'dashwoo' )
				: __( 'the uploads/dashwoo folder is not writable', 'dashwoo' ),
			'hint'      => __( 'Required to store fonts, icons and images.', 'dashwoo' ),
			'required'  => true,
		);

		/**
		 * Filter the probe result.
		 *
		 * A host with non-standard checks (containers without the extension listed in
		 * phpinfo, a read-only staging copy, tests) can correct a single capability here.
		 *
		 * @param array<string,array<string,mixed>> $checks Capability id => probe result.
		 */
		return (array) apply_filters( 'dashwoo_capability_probe', $checks );
	}

	/**
	 * DashWoo features and their requirements.
	 *
	 * `requires` is a list of alternatives: any single available capability enables
	 * the feature. An empty list means the feature is always available.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function features() {
		$features = array(
			'image_processing' => array(
				'label'    => __( 'Image processing and optimisation', 'dashwoo' ),
				'requires' => array( 'gd', 'imagick' ),
				'effect'   => __( 'Convert uploaded images to WebP/AVIF automatically and build a lighter version', 'dashwoo' ),
				'when_off' => __( 'Images are stored untouched (every common format is supported)', 'dashwoo' ),
				'hint'     => __( 'Ask your host to install the php-gd or php-imagick extension to enable this.', 'dashwoo' ),
			),
			'archives'         => array(
				'label'    => __( 'Compressed bundle (zip)', 'dashwoo' ),
				'requires' => array( 'zip' ),
				'effect'   => __( 'Export a backup bundle with settings + assets in a single zip file', 'dashwoo' ),
				'when_off' => __( 'The export runs as a JSON file (it works, the assets are simply not included)', 'dashwoo' ),
				'hint'     => __( 'Ask your host to install the php-zip extension to enable this.', 'dashwoo' ),
			),
			'multisite'        => array(
				'label'    => __( 'Network defaults', 'dashwoo' ),
				'requires' => array( 'multisite' ),
				'effect'   => __( 'Apply the tokens and the default settings to every site in the network', 'dashwoo' ),
				'when_off' => __( 'Settings apply to this site only', 'dashwoo' ),
				'hint'     => __( 'Only meaningful on multisite installs.', 'dashwoo' ),
			),
			'svg_sanitizer'    => array(
				'label'    => __( 'Strict SVG sanitising', 'dashwoo' ),
				'requires' => array( 'dom' ),
				'effect'   => __( 'Structural SVG inspection with DOMDocument on upload', 'dashwoo' ),
				'when_off' => __( 'Sanitising falls back to WordPress\' allow-list (kses)', 'dashwoo' ),
				'hint'     => __( 'The php-xml extension is required to enable this.', 'dashwoo' ),
			),
		);

		foreach ( $features as $id => $feature ) {
			$features[ $id ]['id'] = $id;
		}

		/**
		 * Filter the feature gate table.
		 *
		 * @param array<string,array<string,mixed>> $features Feature id => definition.
		 */
		return (array) apply_filters( 'dashwoo_capability_features', $features );
	}

	/**
	 * Probe (with cache) + persist + report what changed.
	 *
	 * @param bool $force Skip the freshness check.
	 * @return array<string,mixed> The stored state.
	 */
	public function refresh( $force = true ) {
		// Read the option directly: stored() calls refresh() on a cold cache, and a
		// refresh that asked stored() for the previous state would recurse forever.
		$previous = get_option( self::OPTION, array() );
		$previous = is_array( $previous ) ? $previous : array();
		$now      = time();

		if ( ! $force && ! empty( $previous['checked_at'] ) && ( $now - (int) $previous['checked_at'] ) < self::TTL ) {
			return $previous;
		}

		$checks = array();

		foreach ( $this->checks() as $id => $check ) {
			$checks[ $id ] = array(
				'label'     => $check['label'],
				'available' => (bool) $check['available'],
				'detail'    => (string) $check['detail'],
				'hint'      => (string) $check['hint'],
				'required'  => (bool) $check['required'],
			);
		}

		$state = array(
			'checked_at' => $now,
			'checks'     => $checks,
			'features'   => $this->evaluate( $checks ),
		);

		$this->state = $state;
		update_option( self::OPTION, $state, false );

		$changed = array();

		foreach ( $state['features'] as $id => $feature ) {
			$was = isset( $previous['features'][ $id ]['state'] ) ? $previous['features'][ $id ]['state'] : null;

			if ( $was !== $feature['state'] ) {
				$changed[ $id ] = array(
					'from' => $was,
					'to'   => $feature['state'],
				);
			}
		}

		if ( $changed ) {
			dashwoo_log( 'info', __('Capabilities re-checked: features changed automatically.', 'dashwoo'), array( 'changed' => $changed ) );

			/**
			 * Fires when a feature was switched automatically.
			 *
			 * @param array<string,array<string,string|null>> $changed Feature id => from/to.
			 * @param array<string,mixed>                     $state   The new state.
			 */
			do_action( 'dashwoo_capabilities_changed', $changed, $state );
		}

		return $state;
	}

	/**
	 * Re-probe only when the stored probe went stale.
	 *
	 * @return array<string,mixed>
	 */
	public function maybe_refresh() {
		return $this->refresh( false );
	}

	/**
	 * Drop the request cache (after settings are saved, or on blog switch).
	 *
	 * @return void
	 */
	public function flush() {
		$this->state = null;
	}

	/**
	 * The persisted state, probed on first use.
	 *
	 * @return array<string,mixed>
	 */
	public function stored() {
		if ( null !== $this->state ) {
			return $this->state;
		}

		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) || empty( $stored['checks'] ) || empty( $stored['checked_at'] ) ) {
			return $this->refresh( true );
		}

		$this->state = $stored;

		return $this->state;
	}

	/**
	 * Feature state table.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function states() {
		return (array) $this->stored()['features'];
	}

	/**
	 * State of a single feature: on | off | blocked.
	 *
	 * - `blocked`  the host cannot do it (its requirement is missing),
	 * - `off`      the shop owner switched it off,
	 * - `on`       available and wanted.
	 *
	 * @param string $feature Feature id.
	 * @return string
	 */
	public function state( $feature ) {
		$states = $this->states();

		return isset( $states[ $feature ]['state'] ) ? (string) $states[ $feature ]['state'] : 'on';
	}

	/**
	 * Should this feature run right now?
	 *
	 * Capability wins: even with the toggle on, a feature whose requirement is missing
	 * reports `false` here - that is the "switch itself off" contract.
	 *
	 * @param string $feature Feature id.
	 * @return bool
	 */
	public function enabled( $feature ) {
		return 'on' === $this->state( $feature );
	}

	/**
	 * Is a host capability available (fresh probe respected).
	 *
	 * @param string $capability Capability id.
	 * @return bool
	 */
	public function can( $capability ) {
		$checks = (array) $this->stored()['checks'];

		return ! empty( $checks[ $capability ]['available'] );
	}

	/**
	 * Settings fields for the Features screen (one toggle per feature).
	 *
	 * Keyed by feature id, exactly like every other Settings Center section.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function fields() {
		$fields = array();

		foreach ( $this->features() as $id => $feature ) {
			$fields[ $id ] = array(
				'key'        => $id,
				'label'      => $feature['label'],
				'type'       => 'toggle',
				'default'    => true,
				'requires'   => $feature['requires'],
				'capability' => implode( ',', $feature['requires'] ),
				'effect'     => $feature['effect'],
				'when_off'   => $feature['when_off'],
				'hint'       => $feature['hint'],
			);
		}

		return $fields;
	}

	/**
	 * Everything the admin screen and the REST route need.
	 *
	 * @return array<string,mixed>
	 */
	public function status() {
		$state = $this->stored();

		return array(
			'checked_at'    => (int) $state['checked_at'],
			'checked_human' => $this->human_time( (int) $state['checked_at'] ),
			'ttl'           => self::TTL,
			'checks'        => $state['checks'],
			'features'      => $state['features'],
		);
	}

	/**
	 * Evaluate every feature against the current probe.
	 *
	 * @param array<string,array<string,mixed>> $checks Probe result.
	 * @return array<string,array<string,mixed>>
	 */
	private function evaluate( array $checks ) {
		$settings = Settings::instance()->all();
		$saved    = isset( $settings['features'] ) && is_array( $settings['features'] ) ? $settings['features'] : array();
		$states   = array();

		foreach ( $this->features() as $id => $feature ) {
			$requires    = (array) $feature['requires'];
			$available   = true;
			$missing     = array();

			foreach ( $requires as $capability ) {
				if ( empty( $checks[ $capability ]['available'] ) ) {
					$missing[] = $capability;
				}
			}

			if ( $requires ) {
				// Any single available requirement is enough.
				$available = count( $missing ) < count( $requires );
			}

			$wanted = ! array_key_exists( $id, $saved ) || ! empty( $saved[ $id ] );

			if ( ! $available ) {
				$state = 'blocked';
			} elseif ( ! $wanted ) {
				$state = 'off';
			} else {
				$state = 'on';
			}

			$states[ $id ] = array(
				'label'     => $feature['label'],
				'requires'  => $requires,
				'missing'   => $missing,
				'available' => $available,
				'wanted'    => $wanted,
				'state'     => $state,
				'effect'    => $feature['effect'],
				'when_off'  => $feature['when_off'],
				'hint'      => $feature['hint'],
			);
		}

		return $states;
	}

	/**
	 * Does GD advertise a specific encoder?
	 *
	 * @param string $function Function name (imagewebp, imageavif, ...).
	 * @return bool
	 */
	private function gd_supports( $function ) {
		if ( ! function_exists( 'gd_info' ) || ! function_exists( $function ) ) {
			return false;
		}

		$info = @gd_info(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! is_array( $info ) ) {
			return false;
		}

		$map = array(
			'imagewebp' => 'WebP Support',
			'imageavif' => 'AVIF Support',
		);

		return ! empty( $info[ $map[ $function ] ?? '' ] );
	}

	/**
	 * "3 minutes ago" style label without pulling in the whole WP l10n stack.
	 *
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	private function human_time( $timestamp ) {
		if ( $timestamp <= 0 ) {
			return __( 'not checked yet', 'dashwoo' );
		}

		$diff = max( 0, time() - $timestamp );

		if ( $diff < 60 ) {
			return __( 'just now', 'dashwoo' );
		}
		if ( $diff < 3600 ) {
			return sprintf( __( '%d minutes ago', 'dashwoo' ), (int) floor( $diff / 60 ) );
		}
		if ( $diff < 86400 ) {
			return sprintf( __( '%d hours ago', 'dashwoo' ), (int) floor( $diff / 3600 ) );
		}

		return sprintf( __( '%d days ago', 'dashwoo' ), (int) floor( $diff / 86400 ) );
	}
}
