=== DashWoo ===
Contributors: dashwoo
Tags: woocommerce, elementor, design system, fonts, icons
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.4.0
License: GPL-2.0-or-later

Full UI platform for WooCommerce x Elementor with a local Design & Assets System.

== Description ==

DashWoo turns WordPress into a single control center for the whole store UI:

* Design tokens (colors, typography, spacing, radius, shadows, components) compiled to CSS variables.
* Local Google Fonts pipeline - one click, stored in `wp-content/uploads/dashwoo/fonts/`, zero runtime calls to Google.
* Material Symbols / Material Icons variable font manager + a fully controllable Elementor Icon widget.
* Asset Manager for fonts, icons, images, SVG and custom CSS/JS.
* My Account kit: a two-pane panel (menu card + content card), twelve Elementor widgets and full control over every WooCommerce account endpoint (label, icon, order, visibility).
* No built-in styling: DashWoo paints nothing until you switch its design pack on - every element is bare markup with your own classes and tokens.
* Template system: every account section has several shapes (cards, table, timeline, grid, plain) and can be overridden from your theme (`dashwoo/account/orders--table.php`).
* DashWoo brand kit: logo, mark, palette and ready product cards (webp) for the shop's public pages, plus a DashWoo tab inside the Elementor icon picker.
* Version Compatibility Layer with per-component adapters and an automatic Compatibility Mode.
* Full RTL / Persian support.

== Installation ==

1. Upload the `dashwoo` folder to `/wp-content/plugins/`.
2. Activate the plugin and review the activation compatibility report.
3. Open **DashWoo -> Dashboard** and start with the Design System.

== Changelog ==

= 1.4.0 =
* New two-pane account panel: a menu card next to a bigger content card. Clicking a menu item (or an order row) loads that section in the content card - over the site's own REST route, with the URL kept in sync, and a plain link fallback when JavaScript is off.
* New template (قالب) system for the account area: eleven sections, at least two shapes each (cards, compact, table, timeline, grid, list, plain) and one "default" choice; every template can be overridden by a file in the theme (`wp-content/themes/<theme>/dashwoo/account/orders--table.php`).
* New: nothing is styled automatically. «حساب کاربری → دیزاین» now has a master switch for the DashWoo design pack, an optional neutral reset, the `--dw-acc-*` variable injection, and a per-widget “bare markup” mode - turn it all off and the elements come out unstyled for your own CSS.
* New: every element can be told what to show. Each account widget gained a «چه چیزی نمایش داده شود» section: the element itself, icon, title, meta, badge, description, avatar, arrows, action button, the audience (everyone / logged in / administrators) and per-device hiding.
* Fixed: the Elementor panel showed two DashWoo groups ("dashwoo" and "dashwoo | dashwoo حساب کاربری"). There is now one DashWoo category, one widget list and one registration path (modern + legacy Elementor).
* New: the account menu is unstyled by default and fully editable - repeater rows (id, label, icon, visibility, badge, custom URL with `target="_blank"`), icon before/after, plain / menu / tabs / rail shapes.
* New: a login prompt inside the content card for guests (the menu stays as a table of contents) and an ownership check, so a customer can never open somebody else's order.
* New: DashWoo brand kit - `logo.svg`, `logo-dark.svg`, `logo-mark.svg`, icon set, palette tied to the design tokens and three Persian product-introduction cards in **webp** (`product-card.webp`, `product-wide.webp`, `product-square.webp`) for the public side of the shop.
* New: a DashWoo tab in the Elementor icon picker with the brand mark and logos (`assets/css/brand-icons.css` + inline SVGs, still zero external requests).
* New: `tests/js/panel.js` runs the shipped panel script inside a real DOM (node + jsdom) against markup and REST payloads rendered by the plugin; the seam itself (script hooks ↔ markup, `dw_panel` / `dw_page`, nonce, logout, no CDN) is covered by `tests/functional/AccountPanelScriptTest.php` in the normal suite.

= 1.3.1 =
* Fixed: DashWoo account widgets were missing from the Elementor panel on Elementor older than 3.5 (the legacy `elementor/widgets/widgets_registered` hook is now covered as well).
* Fixed: the Elementor **editor** was not recognised as the builder (only the preview was), so the account stylesheet could be missing exactly where the page is designed.
* New: the editor now previews the account area with sample data (menu, hero, three sample orders, downloads) so there is always something to style - a customer's real data always wins over the samples.
* New one-click builder: «ساخت / بازسازی چیدمان حساب کاربری در المنتور» in حساب کاربری → منبع قالب‌ها writes a real Elementor document (menu + dashboard + profile + orders) and opens the editor; without the Elementor document API the page falls back to `[dashwoo_account]`.
* New: an editor-wide bar offers the same one-click build when the account page has no DashWoo widget yet.
* New: Elementor Pro Theme Builder location «حساب کاربری — DashWoo», with a readiness report (Elementor version, widgets, account page, document, source mode) in the settings screen.

= 1.3.0 =
* New account pack (حساب کاربری): the My Account area is DashWoo's own - navigation, dashboard hero, orders, downloads, addresses, payment methods, account details, forms and logout, all with Persian/RTL markup and DashWoo design tokens.
* Ten new Elementor widgets in a dedicated "DashWoo — حساب کاربری" category, plus the `[dashwoo_account]` shortcode for pages and blocks.
* New settings group "حساب کاربری" with five sections (layout, endpoints, design, forms, source): every endpoint's label, icon, order and visibility is editable, including endpoints other plugins add.
* Native bridge: DashWoo renames/reorders/hides the WooCommerce account menu in place, so themes and third-party endpoints keep working.
* Source modes (default / hybrid / elementor) with a compatibility guard: the account page is never left blank, on any theme or builder.
* Account styles load only where they are needed (account page, builder preview or a page with the shortcode).

= 1.2.0 =
* WooCommerce never lists DashWoo as an incompatible plugin any more: per-feature compatibility declarations on `before_woocommerce_init` (28 audited features + anything the live WooCommerce feature list adds).
* New Host Capabilities layer: missing host extensions turn a feature off automatically and back on when they appear (gd/imagick → image optimization, zip → compressed backups, multisite, dom → strict SVG audit).
* New "قابلیت‌ها" tab in the System group + `GET/POST /system/capabilities` REST routes.
* Compatibility report gained the `woocommerce.feature_declarations` row so the declaration can be verified from the admin.
* Fixed: dynamic static call in the declaration code, duplicated feature id, nested `extra` payload in the WooCommerce adapter row.

= 1.1.0 =
* Hierarchical admin menu: menu → submenu → tab row → tabs (no flat lists).
* Settings Center grew to 31 sections / 133 fields in 6 groups.

= 1.0.1 =
* Yellow `gd`/`zip` capability checks became neutral informational rows.

= 1.0.0 =
* First public release.
