=== DashWoo ===
Contributors: dashwoo
Tags: woocommerce, elementor, design system, fonts, icons
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.6.0
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
* English source with a complete Persian (fa_IR) translation and full RTL support.

== Installation ==

1. Upload the `dashwoo` folder to `/wp-content/plugins/`.
2. Activate the plugin and review the activation compatibility report.
3. Open **DashWoo -> Dashboard** and start with the Design System.

== Frequently Asked Questions ==

= Does the plugin speak my language? =

Yes. Every string is written in English in the code and translated through the standard
WordPress gettext layer, so DashWoo follows the site language. A complete Persian
(`fa_IR`) translation ships with the plugin, including the JavaScript catalogue, and the
platform is right-to-left aware. **DashWoo -> System -> General -> Platform language** can
also force English or Persian for DashWoo alone, whatever the site is set to.

= Does it call Google or any CDN? =

No. Fonts, icons and images are downloaded once into `wp-content/uploads/dashwoo/` and
served locally; the compiled stylesheets and scripts are local files as well.

= What happens on a host without GD, Imagick or zip? =

Nothing breaks. Those capabilities are optional, so the matching feature stays off and the
status screen reports it as an informational row - never as a warning - with the exact text
to send to the host. When the capability appears, the feature switches itself back on.

== Changelog ==

= 1.6.0 =
* Protected build: the released plugin is no longer readable source. Every logic file ships as a stub that asks the module kernel (`includes/kernel.php`) for its own body at runtime, so the file list still shows the plugin, the site still boots, and there is nothing to copy. A stub that is edited by hand - or a payload that is decoded and pasted back - stops working in that module only, is reported to the administrator with the file name and the exact fix, and never takes the shop down. `bin/encode.php` produces the build, the build refuses to finish unless every payload decodes back to the tested source byte for byte, and `bin/verify-build.php` re-checks it from the archive with the key the archive carries. The key can be kept outside the plugin with the `dashwoo_kernel_key` filter; keep a copy of your own build if you want to reinstall it later.
* New visual identity: **Dwoo**. A new mark (a rounded tile with a "d"), a geometric wordmark drawn from paths - no font needed - in a light and a dark lockup, a banner, a square card and the four images the WordPress plugin directory expects (`icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`). The DashWoo admin header shows the mark, the account hero can show it per widget, and the Elementor icon picker has the new marks. The name, the settings and the technical identity stay DashWoo; `tools/brand/make-dwoo.py` regenerates the whole kit.
* New: a second menu card. Custom endpoint pages the shop owner defines (`DashWoo -> Account -> Custom pages`) are pages on the site with their own slug, label, icon, order, capability and visibility, so the account menu can be built up without touching a theme file. Each page can be shown to everyone, to logged-in customers or to administrators only, can be hidden from the menu while staying reachable by URL, and can point at any template.
* New: a content box for every page. The dashboard and every custom page can render a content box whose source is HTML, a shortcode, an Elementor template or a theme file, and whose shape is plain, a card or a section - or no template at all, which is the default when nothing is chosen.
* New: the settings framework understands repeatable rows (`repeater` field type), with add / duplicate / move / remove in the settings screen and a script that renumbers every row field name on save, so a row that is added, moved or deleted is stored correctly.
* New: the project page (`PROJECT.md`) is English with a Persian section at the end — a short feature list, the system requirements taken from the same floors the compatibility layer enforces, and the preview. A test asserts all three.
* New: real screenshots ship with the plugin (`screenshots/`), drawn from the shipped views and stylesheets by `tools/preview/` — the settings screen, the account design screen, the account panel and a custom endpoint page, in English and in Persian. The public project page (`PROJECT.md`) shows them next to a short feature list and the system requirements, in English with a Persian section.
* Changed: the default design is zero. A fresh install is painted once with the neutral account pack so the area looks like a page rather than a list, and from the first save onwards DashWoo loads no stylesheet of its own at all - no DashWoo CSS file, no reset and no inline variables unless the corresponding switch is on. Style it with your theme, your CSS or Elementor.

= 1.5.0 =
* The whole project is English: every user-facing string, the readme, the settings screens, the Elementor widget names, the release notes and the docs. Nothing in the source tree carries a hard-coded Persian sentence any more.
* New language layer: DashWoo loads its catalogue through the standard gettext layer (`load_plugin_textdomain` / just-in-time loading), so a Persian WordPress shows Persian automatically. The `Platform language` setting can force English or Persian for DashWoo alone without touching WooCommerce or the theme.
* New: a complete Persian catalogue ships in `languages/` (`dashwoo-fa_IR.po`, compiled `dashwoo-fa_IR.mo`, 916 strings) plus the JavaScript catalogue WordPress hands to the admin script (`dashwoo-fa_IR-<hash>.json`, loaded through `wp_set_script_translations`).
* New tooling: `tools/i18n/extract.py` (find strings), `tools/i18n/apply.py` (the migration), `tools/i18n/build-catalogue.py --strict` (refresh the POT, sync the `.po`, compile the `.mo`, write the JSON catalogue, fail when a string has no translation). The build and the release gate run it.
* New tests: `tests/unit/I18nCatalogueTest.php` (no string may be missing a translation, no translation may lose a `%s`, the `.mo` must match the `.po`, the source tree must stay English) and `tests/integration/LanguageIntegrationTest.php` (site language, forced languages, RTL, the script catalogue path, and the same account section rendering in both languages).
* Compatibility: with `Platform language` on “Follow the site language” the behaviour follows WordPress; an install that had already picked Persian keeps Persian.
* Counters read correctly for any number in English (“Orders: 5”, not “5 orders” for a single order), and the sign-in prompt, table headers and form labels are translated as their own strings instead of whole HTML blocks, so translators never have to edit markup.

= 1.4.0 =
* New two-pane account panel: a menu card next to a bigger content card. Clicking a menu item (or an order row) loads that section in the content card - over the site's own REST route, with the URL kept in sync, and a plain link fallback when JavaScript is off.
* New template system for the account area: eleven sections, at least two shapes each (cards, compact, table, timeline, grid, list, plain) and one "default" choice; every template can be overridden by a file in the theme (`wp-content/themes/<theme>/dashwoo/account/orders--table.php`).
* New: nothing is styled automatically. The account design screen now has a master switch for the DashWoo design pack, an optional neutral reset, the `--dw-acc-*` variable injection, and a per-widget "bare markup" mode - turn it all off and the elements come out unstyled for your own CSS.
* New: every element can be told what to show. Each account widget gained a "what is shown" section: the element itself, icon, title, meta, badge, description, avatar, arrows, action button, the audience (everyone / logged in / administrators) and per-device hiding.
* Fixed: the Elementor panel showed two DashWoo groups. There is now one DashWoo category, one widget list and one registration path (modern + legacy Elementor).
* New: the account menu is unstyled by default and fully editable - repeater rows (id, label, icon, visibility, badge, custom URL with `target="_blank"`), icon before/after, plain / menu / tabs / rail shapes.
* New: a login prompt inside the content card for guests (the menu stays as a table of contents) and an ownership check, so a customer can never open somebody else's order.
* New: DashWoo brand kit - `logo.svg`, `logo-dark.svg`, `logo-mark.svg`, icon set, palette tied to the design tokens and three product-introduction cards in **webp** (`product-card.webp`, `product-wide.webp`, `product-square.webp`) for the public side of the shop.
* New: a DashWoo tab in the Elementor icon picker with the brand mark and logos (`assets/css/brand-icons.css` + inline SVGs, still zero external requests).
* New: `tests/js/panel.js` runs the shipped panel script inside a real DOM (node + jsdom) against markup and REST payloads rendered by the plugin; the seam itself (script hooks to markup, `dw_panel` / `dw_page`, nonce, logout, no CDN) is covered by `tests/functional/AccountPanelScriptTest.php` in the normal suite.

= 1.3.1 =
* Fixed: DashWoo account widgets were missing from the Elementor panel on Elementor older than 3.5 (the legacy `elementor/widgets/widgets_registered` hook is now covered as well).
* Fixed: the Elementor **editor** was not recognised as the builder (only the preview was), so the account stylesheet could be missing exactly where the page is designed.
* New: the editor now previews the account area with sample data (menu, hero, three sample orders, downloads) so there is always something to style - a customer's real data always wins over the samples.
* New one-click builder: "Build / rebuild the account layout in Elementor" in My Account -> Template source writes a real Elementor document (menu + dashboard + profile + orders) and opens the editor; without the Elementor document API the page falls back to `[dashwoo_account]`.
* New: an editor-wide bar offers the same one-click build when the account page has no DashWoo widget yet.
* New: Elementor Pro Theme Builder location "My Account - DashWoo", with a readiness report (Elementor version, widgets, account page, document, source mode) in the settings screen.

= 1.3.0 =
* New account pack: the My Account area is DashWoo's own - navigation, dashboard hero, orders, downloads, addresses, payment methods, account details, forms and logout, all with RTL-ready markup and DashWoo design tokens.
* Ten new Elementor widgets in a dedicated "DashWoo - My Account" category, plus the `[dashwoo_account]` shortcode for pages and blocks.
* New settings group "My Account" with five sections (layout, endpoints, design, forms, source): every endpoint's label, icon, order and visibility is editable, including endpoints other plugins add.
* Native bridge: DashWoo renames/reorders/hides the WooCommerce account menu in place, so themes and third-party endpoints keep working.
* Source modes (default / hybrid / elementor) with a compatibility guard: the account page is never left blank, on any theme or builder.
* Account styles load only where they are needed (account page, builder preview or a page with the shortcode).

= 1.2.0 =
* WooCommerce never lists DashWoo as an incompatible plugin any more: per-feature compatibility declarations on `before_woocommerce_init` (28 audited features + anything the live WooCommerce feature list adds).
* New Host Capabilities layer: missing host extensions turn a feature off automatically and back on when they appear (gd/imagick -> image optimization, zip -> compressed backups, multisite, dom -> strict SVG audit).
* New "Capabilities" tab in the System group + `GET/POST /system/capabilities` REST routes.
* Compatibility report gained the `woocommerce.feature_declarations` row so the declaration can be verified from the admin.
* Fixed: dynamic static call in the declaration code, duplicated feature id, nested `extra` payload in the WooCommerce adapter row.

= 1.1.0 =
* Icon renderer: Material Symbols variable axes, per-icon overrides, RTL ligature handling.
* Elementor: token picker control, widget-level style controls, Global Kit sync with backup and revert.
* Asset Manager: upload, replace and delete fonts, icons, images and SVG with metadata so updates only touch changed files.
* REST: `/fonts`, `/icons`, `/assets`, `/tokens`, `/system/diagnostics`.

= 1.0.1 =
* Fixed the admin navigation on hosts where `admin_url()` carries a port, and the asset-folder check on open_basedir setups.

= 1.0.0 =
* First release: design tokens, local fonts, icons, assets, compatibility layer, REST API, settings center.

== Upgrade Notice ==

= 1.6.0 =
The released plugin is a protected build this time, so the plugin files are not readable source any more. Nothing about your site changes: same settings, same data, same screens. Keep the zip you install - reinstalling DashWoo later needs the same build. If a build file is ever modified, DashWoo deactivates itself, tells you which file was touched and leaves the site and the data exactly as they were.

= 1.5.0 =
DashWoo is now written in English and ships Persian as a translation. Nothing is lost on upgrade: with the platform language on "Follow the site language" a Persian WordPress keeps showing Persian, and an install that had already picked Persian keeps it as well.
