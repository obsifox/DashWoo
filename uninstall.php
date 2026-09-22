<?php
/**
 * DashWoo protected module. Do not edit: one changed byte and this module
 * refuses to run, because its SHA-256 no longer matches the code it produces.
 *
 * module: uninstall.php
 * sha256: 87faa36027b5308d9383bf0e2e3b7a171f1e440c7534fcd0b0a497eb7054ad62
 *
 * @package DashWoo
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/kernel.php';
// Without the kernel there is nothing to ask for the code: a decoded copy of this
// file is inert, and the site never sees a fatal error.
if ( ! class_exists( 'DashWoo\Kernel', false ) ) {
	return null;
}

return eval( DashWoo\Kernel::code(
	'uninstall.php',
	'4PPWCLmauRH8jIsOf0LgPZjVtkAgFMYj771bIo+ZxsZ0X4UMIq0wnQfusixXQ/vs0UW1MHJdzQ0uCtwKKxifNRz4bie50Ds92jyhMmuCfb4Mxn' .
	'IEC9o7wZMJp34toz6ckFcktSGFE8PpkZnLrpOjQoykvUeKPIOSEbDJG7DlV/hfsOqfGdHO9KupfPTzcNFInVdY/ujGyv+cLwFWL5StC1L3mnjN' .
	'v36efQE/Td1l2NjRZe8q6JhHkixG7H4rlN76Y3GgXbpMpRhfTPcdE51pbFuUOLJOXDd4/aCP3rlpZ1jmA7XCu2Sb7NzwyCEWAxuYJOfqJ+2aez' .
	'cDOQGlrW/X4Z+kDq3FDXSWXwiIj8zEtVwL8AfKTx+3twN5zauWqq9dHrLS4/Z2TDyOExGQOJOOluLkcHo1bS29DyKFkddgwHNPQnyGmL7/Y12o' .
	'6YXHabb6Drk62kEu131bzKlT8+nUKzilZc7Dd19DH8OA9LJ6rng/vaBUZbCG/5/oFDxV50htSDb2gIfeHTFD6T4tSENJkROfSzDmVo86O4Qhji' .
	'bjnJoQz6X+DT1AVyMWYICOqR1dSnlOWeLE4Bw1JnfcXmGXNloWRSGu4WWw+CL1W/b/C5UBSI/8ooHSlwBrYS4FzCo7UbRCYzc7Kp9TMDfMA8DS' .
	'dZ57kFT6PBke6YN2uvAqMAUVkY4Z7Thj73omD7sZ+n4tQZsAh2CsDctP5yBoysXVQPIRr4sHiMkWsDMlN6k70KqFBQILPF29i5KH4uMeA7grJL' .
	'G8lVTacsZ+iMY3WZeIa7HVpZiuqtzUeboNvld2fARr5vx+NXhJmJU2li7xeDc3XxMv41WocIqt0fq9QkUfBFV9gt3/XmoMPn/OO2G7GN947PaZ' .
	'53H8LrUQ4qXrsiFfvLF9h/FGq0//oyt9SSuf4koz+6+QUW7r5Ls+FVUjt/thU0EXXKYNzfeHgm2wbQyZkAEYyWAnuUXQVqa313tquXS0zVj+GD' .
	'gCpILSx5NCkFGGe1HsCZ0gbVXr/sx3vce2iuV+gOjUOQWEVeRmkF6SBLUWfj1PA3IL0p9NxUce/br5ObXJhOnbDIcf8+klt1fqN7sBwqvqdbnl' .
	'1AZx3LPknrfK5dsaAER0OSh1INMKQkmJ8HrBcTiiPvfLCTKkNTXczAmQql/mST+dXg3HMnY5QV0XpijPs5esgSB9NrUCn+oyqOqwdI5sWSA/Rl' .
	'CUAoMLJUHXQfb1DFL4Kyt/DNdMngSJCbwrlwNtDkk7Z96i7me4FK77iUbiq4bbF7XFGCHxRIMuQHTNwK4QN2zdMNpEJk4NrMEKSzAJo61nMto2' .
	'gyVs1ITB3G16i07CGipMx+3sGTRxfVX8GysGWcLSRGBuSjqZ7FxKwlWBCRYv+HNJULAa5PzffV+9qgHhZR4g1jWxBB/kZhPGVJ91LkjqesiORO' .
	'YjAh2C8Q+a0+8hh1WtWEyAk8Pf0FqoT36UCObbFXPTBAGuH7EzI+A+hMK5d2mrLuqy9MniEuruMyy+MxUK1lUejiUmG4S+ofyIAPZs+vpQEpvY' .
	'KZvZk+V6mIF0zcZ+2/p68kf6zp7bbXfZymISsJHRlMSMkp501NGmY1Y97vBCB4eKgQlsmgk8i8jEPp8tiSTlQNp2sf4CGoCX9l8tSLn5+5z7M2' .
	'KIZV5EFAMyvHJtTKIw0wZZbQ==',
	'87faa36027b5308d9383bf0e2e3b7a171f1e440c7534fcd0b0a497eb7054ad62'
) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
