<?php
/**
 * DashWoo kernel — the module loader of a protected build.
 *
 * A released DashWoo is shipped as **encoded modules**: every PHP file that carries
 * DashWoo logic is replaced by a small stub that hands the kernel a payload and the
 * SHA-256 of the source it must produce. This file is the only place that knows how to
 * turn a payload back into running code, and it is deliberately the only readable PHP a
 * shop owner's server ever sees together with the plugin header (`dashwoo.php`) and the
 * uninstall stub.
 *
 * What the lock does, and what it honestly cannot do
 * -------------------------------------------------
 *
 *  1. **Not readable.** A module is stored as base64 of `source XOR keystream`, the
 *     keystream being derived from a key that is injected at build time. Opening a file
 *     in an editor shows a payload, not code.
 *  2. **Not reusable as source.** A decoded copy pasted into another plugin does
 *     nothing: the stub evaluates code only while this kernel is present, and it is the
 *     kernel that hands out the source. Without it the stub returns `null` - no fatal
 *     error, no half-working plugin, silence.
 *  3. **Any edit stops it.** Every module carries the digest of its own source. One
 *     changed byte and the module refuses to run, the plugin deactivates itself with a
 *     notice, and the site keeps working exactly as it did before.
 *  4. **What it is not.** This protects against casual copying and against reading the
 *     code; it is not cryptography against a determined expert, who can read this file
 *     and eventually extract the key. DashWoo ships **no** licence check, no phone-home
 *     and no domain lock: the lock makes the code unreadable, it does not police a site.
 *
 * A development install (the private source tree) runs the same kernel with the modules
 * in plain PHP; `Kernel::code()` is simply never called there.
 *
 * @package DashWoo
 */

namespace DashWoo;

defined( 'ABSPATH' ) || exit;

/**
 * The module loader.
 */
final class Kernel {

	/**
	 * Digest used for the payload hash and the keystream.
	 */
	const ALGO = 'sha256';

	/**
	 * Cipher key. The build injects a fresh value here (a random one, or the one given
	 * with `--key=` for a byte-identical rebuild). The source tree ships a dev key so a
	 * plain install behaves identically - nothing is decoded outside a protected build.
	 */
	const KEY = '575401662c88c1c5c58a75b0c1ec10f9047dd7833d5ab46d22fe9eb7b6f56426';

	/**
	 * Keystream block size, in bytes.
	 */
	const BLOCK = 32;

	/**
	 * Modules that failed verification during this request.
	 *
	 * @var array<int,string>
	 */
	private static $broken = array();

	/**
	 * The source of one module.
	 *
	 * Called by the stub of every encoded module, from inside the file that module
	 * replaced: `return eval( Kernel::code( ... ) );`. The payload is decoded, verified
	 * against its own digest, and handed back as PHP source - so the module runs in the
	 * scope of the file that asked for it, with exactly the variables that file saw.
	 *
	 * @param string $id      Module id (its path inside the plugin).
	 * @param string $payload Encoded source.
	 * @param string $hash    SHA-256 of the source.
	 * @return string PHP source, or the harmless `return null;` when it cannot be trusted.
	 */
	public static function code( $id, $payload, $hash ) {
		$id    = (string) $id;
		$plain = self::decode( $id, (string) $payload );

		if ( '' === $plain || ! hash_equals( strtolower( trim( (string) $hash ) ), hash( self::ALGO, $plain ) ) ) {
			self::refuse( $id );

			return 'return null;';
		}

		return $plain;
	}

	/**
	 * The key in use.
	 *
	 * @return string
	 */
	public static function key() {
		$key = (string) self::KEY;

		/**
		 * Filter the module key, for hosts that prefer to keep it outside the plugin.
		 *
		 * @param string $key Key.
		 */
		if ( function_exists( 'apply_filters' ) ) {
			$key = (string) apply_filters( 'dashwoo_kernel_key', $key );
		}

		return $key;
	}

	/**
	 * Does this install ship encoded modules?
	 *
	 * @return bool
	 */
	public static function protected_build() {
		return 0 !== strpos( (string) self::KEY, 'dashwoo-development-key' );
	}

	/**
	 * Modules that failed their integrity check during this request.
	 *
	 * @return array<int,string>
	 */
	public static function broken() {
		return self::$broken;
	}

	/**
	 * Decode one payload: base64 → XOR against a keystream derived from key + module id.
	 *
	 * The keystream never repeats inside a module (each 32-byte block is a fresh digest
	 * of key, module id and block index), and the digest of the result is checked by the
	 * caller - so a truncated, reordered or hand-edited payload can never run.
	 *
	 * @param string $id      Module id.
	 * @param string $payload Encoded source.
	 * @return string Source ('' when the payload cannot be decoded at all).
	 */
	private static function decode( $id, $payload ) {
		$payload = (string) preg_replace( '/\s+/', '', $payload );
		$raw     = base64_decode( $payload, true );

		if ( false === $raw || '' === $raw ) {
			return '';
		}

		$key   = self::key();
		$plain = '';
		$size  = strlen( $raw );
		$index = 0;

		for ( $offset = 0; $offset < $size; $offset += self::BLOCK ) {
			$chunk = substr( $raw, $offset, self::BLOCK );
			$seed  = hash( self::ALGO, $key . '|' . $id . '|' . $index, true );
			$plain .= $chunk ^ substr( $seed, 0, strlen( $chunk ) );
			$index++;
		}

		return $plain;
	}

	/**
	 * Remember a module that could not be verified and get out of the way.
	 *
	 * The plugin never dies with a fatal error: the module does nothing, the site keeps
	 * working, and the shop owner is told what happened on the next admin request - where
	 * DashWoo also deactivates itself, because a half-loaded DashWoo is worse than none.
	 *
	 * @param string $id Module id.
	 * @return void
	 */
	private static function refuse( $id ) {
		if ( ! in_array( $id, self::$broken, true ) ) {
			self::$broken[] = $id;
		}

		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_init', array( __CLASS__, 'report' ) );
			add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		}
	}

	/**
	 * The message the shop owner sees.
	 *
	 * @return string
	 */
	public static function message() {
		return sprintf(
			/* translators: %s: comma separated list of plugin files. */
			'DashWoo stopped loading: its integrity check failed for %s. Those protected files were edited or decoded outside the plugin. DashWoo has deactivated itself; your site, your orders and your settings are untouched. Reinstall DashWoo from the official package to keep going.',
			implode( ', ', self::$broken )
		);
	}

	/**
	 * Deactivate DashWoo and leave the reason behind for the next screen.
	 *
	 * @return void
	 */
	public static function report() {
		if ( ! self::$broken ) {
			return;
		}

		if ( function_exists( 'set_transient' ) && defined( 'HOUR_IN_SECONDS' ) ) {
			set_transient( 'dashwoo_integrity_failure', self::message(), HOUR_IN_SECONDS );
		}

		if ( function_exists( 'deactivate_plugins' ) ) {
			if ( function_exists( 'current_user_can' ) && ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			if ( ! did_action( 'dashwoo_kernel_deactivated' ) ) {
				do_action( 'dashwoo_kernel_deactivated' );
				deactivate_plugins( defined( 'DASHWOO_BASENAME' ) ? DASHWOO_BASENAME : 'dashwoo/dashwoo.php' );
			}
		}

		/**
		 * Fires when a module failed its integrity check.
		 *
		 * @param array<int,string> $broken Broken module ids.
		 */
		do_action( 'dashwoo_kernel_integrity_failed', self::$broken );
	}

	/**
	 * Print the notice - now, or once more after the deactivation.
	 *
	 * @return void
	 */
	public static function notice() {
		$message = self::$broken
			? self::message()
			: ( function_exists( 'get_transient' ) ? (string) get_transient( 'dashwoo_integrity_failure' ) : '' );

		if ( '' === $message ) {
			return;
		}

		if ( function_exists( 'delete_transient' ) ) {
			delete_transient( 'dashwoo_integrity_failure' );
		}

		$safe = function_exists( 'esc_html' ) ? esc_html( $message ) : htmlspecialchars( $message, ENT_QUOTES, 'UTF-8' );

		echo '<div class="notice notice-error"><p>' . $safe . '</p></div>';
	}
}
