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
	'g9yZpwlZJpjW38E8UXumkAIWkjsBJRTyYdpk5tGfDXF/GPhwRJ4Hf8fJM4tV2IkVfnTIa4jnaY8VMa52Cyi9C8cqw9A00qewGbeyWR8KQcRSDk' .
	'seihqm63KeOOjKrSFsTpUlkwEe0Eb7DMLp5lhqFEm17aeGV749/3ELsfoSrmWz+nGaKaRJdsvDkIalhRS8l+6pCzjb4RvasbMOokuPYe0cYTzv' .
	'Xw1zzRhM8AvBbWnUuCdNl+RdzTf6Vv83auOE1saVrkHS21SXueStU2Lqx1pRp9tfWATAYvtJklqEvs80PZpaMvg089ky/6LRBE5317+lGfuef7' .
	'YndzhKUZczVCe3RQAXQadLBlqJuPUu/25d/rn52p5VThc/buvxqCESr6dp+8nVdeJdmxh/2YAHaQKGQ9/E274t3ja5hPQDJZ0QOLxa7ehQ8NwM' .
	'aNVclpWtJQWhzJ+CCGBweV5OhEp50irbhCY=',
	'dc4151614a066f8ce6e8f080531bd556f807a1dc0ea442d2260b689173ac4517'
) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
