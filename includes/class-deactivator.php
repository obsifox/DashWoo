<?php
/**
 * DashWoo protected module. Do not edit: one changed byte and this module
 * refuses to run, because its SHA-256 no longer matches the code it produces.
 *
 * module: includes/class-deactivator.php
 * sha256: dc4151614a066f8ce6e8f080531bd556f807a1dc0ea442d2260b689173ac4517
 *
 * @package DashWoo
 */

defined( 'ABSPATH' ) || exit;

// Without the kernel there is nothing to ask for the code: a decoded copy of this
// file is inert, and the site never sees a fatal error.
if ( ! class_exists( 'DashWoo\Kernel', false ) ) {
	return null;
}

return eval( DashWoo\Kernel::code(
	'includes/class-deactivator.php',
	'XJuUCGPx9+dbAHUNNCrxXezpvrJX+fMdF44Y4kyret/z2ZWSrY0H8pY+BR8FTr572QMMDMtoUB4cg//MTIfyOdY1qm+gac60mjIoDsVV40Qn/G' .
	'H/VCNceTfL61ZiIgfmrYbEHiHACsvItmawN6ygCNXcI4051yACRakKhWvwnIBxQZfiAhcluD9iVDU87IFXSvYAzP1c7aLNwu5DyI0Hxeaa9FNh' .
	'XY/vRzPAAIwqs2aPunYDQJP1b8Gqagg01mLX3sYlrApBXjqWiFF1hjpbUwGHsR6hJG4QKl/d5wRQvUakUfK9I/DZbzEs2YcVxcKsQYtnpHwJ+h' .
	'R8UrMYnmV20vrdF7EUs1czfT+9oYbH+CXo2yJk+/0lUL+q/AkCg9G85XKiMQkoHux5acGwyBytzbuIphQv94Pf5V1A00Lsk7BJi/kIRic2GiLe' .
	'JN/EgbP8Nn8Jke0vEml1cMGY0drRxL6upaw=',
	'dc4151614a066f8ce6e8f080531bd556f807a1dc0ea442d2260b689173ac4517'
) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
