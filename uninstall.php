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
	'G8sri4csa28AzQkPNURbp1r2PbF+YZdLg/OpQbPImpDWBjcBP2HrhA3/1xsBcW40WkwGmYXi/REvO9ih5FC5mjEN+MGGrKpsMsG/tazkX+FxlP' .
	'+9xCNaQcSKtRx9v9f6FJoeTSPbFB8/18SSFxWmJrL9R1DHUp8af4vOi+AwkPL76NauOzWuwcAPA5CkHTeLmtiuClPrWJoOok0LAijwUbXWw1xe' .
	'vkbP2QGftQHwit+87UlJ4zk7TBCrP4XmRAGW11vm3SIl+T24Us56mgiePqJSEg5P+5W47M6BnQj1gR4ru0IYdIAYCOucmcGWsB82G7Cn+OeOc0' .
	'Wb3brxb5UYWF8Hv2oR4dBwycbKgIawIdMarDbZ88w5knKvZHcDI1K/jvIQlKLhOWA9cIaK8AQKomsfykY4xTeE8krSSYo2pxOJ7JcrMG7b0BCd' .
	'vaXol8lMub32J6Fr0Ip5VwSM7z1QU+7x3YejluLwJMo0a8bzPrzjB67bJ+vccbnEO5Lsr5IpH0ysEstTxQFHsU5br1OSCPS3gt1/eaWKNZs5bg' .
	'XBL/jrMNTc/MgQRkdKTnzjEEtX+XaOs4YvIlM1qLBWPlOPq4ax4jRRxrmUX6M0b4ATWoNWw+Kaqt1TVtxzINuJFV/4LvG4SciK6m4RPmm41mHf' .
	'XpJ0IRRN6noTwlI+sSbG7rMOPtvhPLIYbjMWdLajVPF22QlmlpLdynaCZJkp0018g3mdhl8blMVIAaLdVFuMYdLZd0XoEsv3s+aUogx89z9F2Z' .
	'ehvaF6ruet/xZ5ViA8UZlwYENXQxCcf798nO921hW41OPu/ea/ztQiVKsn1yDEf5lUqdQ4EB2WaF5htw6dnwdSDEgV9g+vVXr63S7Y2E9flte9' .
	'CFuYGCsZWL6DY7IigVuhNPYHWeyKMfWVK8mNVwLIYY3nUJn+c8DoFNF+frmSSBYdMC3XYtLG3Lpcqlkkh9R5tYdYNOa47W50GS9ZE+CxK5FU/P' .
	'ZNPpR9XLOZhV4EwFLXJLxLJOtO2VJe4Od+N5TEFoNjo7nPE3oxA/l45+UleGx4oXLbDN4h7fqoZ9SMi9v4Cyc5Dw1KKc4l/YUpYP2WuGBIHk5O' .
	'zuQHXFgksLeoUchprsuCwCOOLgbnXhzX9nWDrw0N9NVEmhbIgL/JIqjbqzqXWN/bF3GgES4/4EQkp/RERu8YI7ZtUHvMmGnikr6l2MrblPmlGu' .
	'd5T4Cn4NnZ/kNbzhqZkdNDts6wNthA2iJaakeZRi4C40Td8HCZcXTkKIx6Cs091Y7o+roSLbBtETyzeIcAjdcfOMmrOqs9T8skLWDh68oL/Q+I' .
	'benMGhP9Nyb0ZeBJNlZB5gMUvsxy5vpyBRTzq4PxPzJYMcBY7C3ywPX0GvYWkWLZggEriyU/Hs4nC929h2wVEciUjP4aD4w5GgrXomyLkvIMOl' .
	'/xQA4C0GxPBlXPnaUkDY7GdhLt4qGnr3p35Ha+Z/jH8W+54pOZaq6z5ipWLoWbE0Br5nhJMKxYMsBaSxSMiaPswksYB3rP8QgXBoA8R75QYTlg' .
	'REWrG44ZR3tZjGZliTG7Tq1OSQKLg2j/rIYn4YvTp3wZvPEqCZgQBn9MXZbsfHYQwvnnqvy71gdQdsapr2Nju+I5zQin03cow6ZaWkpetoituk' .
	'srPr7bnj1rFYOpCXpsnVRRIQ==',
	'87faa36027b5308d9383bf0e2e3b7a171f1e440c7534fcd0b0a497eb7054ad62'
) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
