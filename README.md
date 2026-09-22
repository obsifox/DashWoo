# Dwoo — the DashWoo UI Platform for WooCommerce × Elementor

DashWoo turns every visual decision of a WooCommerce shop into locally served
assets and design tokens. Fonts, icons, images, SVG and custom code are managed
inside WordPress — **with zero dependency on any external CDN**.

> **English first, Persian supported.** The project page and this readme are English
> with a Persian section; the plugin itself is English in the code and ships a complete
> `fa_IR` catalogue.
>
> **Dwoo** is the visual identity of DashWoo: the mark in the header, the logo on the
> product card and the icon set in Elementor. The plugin, its settings and its
> technical name stay **DashWoo**.
>
> **English source, Persian ready.** Every string lives in the code in English and
> goes through the WordPress gettext layer, so DashWoo follows the site language.
> A complete Persian (`fa_IR`) catalogue — PHP and JavaScript — ships with the
> plugin, and the platform is right-to-left aware. A Persian shop that only wants
> DashWoo in Persian can force the language in the settings.

**Preview** — real screenshots of the plugin, in English and in Persian. They ship in
[`screenshots/`](screenshots/) and are produced by `tools/preview/` from the shipped views
and the shipped stylesheets, so they cannot drift away from the product.

| Settings | My Account → Design |
| --- | --- |
| ![DashWoo settings screen](screenshots/admin-settings.png) | ![DashWoo account design screen](screenshots/admin-design.png) |

| Account panel (menu card + content card) | A custom page with its own content box |
| --- | --- |
| ![DashWoo account panel](screenshots/account-panel.png) | ![DashWoo custom endpoint page](screenshots/custom-page.png) |

| پنل حساب — فارسی | صفحهٔ سفارشی — فارسی |
| --- | --- |
| ![پنل حساب DashWoo](screenshots/account-panel-fa.png) | ![صفحهٔ سفارشی DashWoo](screenshots/custom-page-fa.png) |

The short project page — what DashWoo is, the system requirements and the same preview —
is [`PROJECT.md`](PROJECT.md), in English with a Persian section (**[فارسی](PROJECT.md#فارسی)**).

## Features

- **Local Google Fonts** — search 31 families, download only the weights/subsets
  you need, keep the upstream metadata so updates touch only changed files.
- **Material Symbols & Material Icons** — variable font axes (weight, FILL, GRAD,
  optical size) with per-icon overrides, RTL-safe ligature rendering.
- **Asset library** — images, sanitized SVG, uploaded fonts and custom CSS/JS,
  stored in `wp-content/uploads/dashwoo/` (your files survive plugin updates).
- **Design tokens → CSS variables** — 16 token categories compiled to
  `--dw-*` variables plus a versioned stylesheet in the uploads cache.
- **Elementor integration on three levels** — CSS variables, a real token picker
  control, and optional Global Kit sync with backup + revert.
- **Host capabilities → automatic feature gating** — DashWoo probes what the host
  actually has (`gd`, `imagick`, `zip`, `multisite`, `dom`, `webp`, `avif`, …). A
  feature whose requirement is missing switches itself off with a plain-language
  explanation, and comes back on its own the moment the host gains it. Nothing has to
  be configured twice, and no missing extension ever shows up as a broken store.
  See the **Capabilities** tab in the System group.
- **No WooCommerce "incompatible plugin" notice** — DashWoo declares per-feature
  compatibility on `before_woocommerce_init`, so WooCommerce never lists it among the
  plugins that are incompatible with High-Performance Order Storage (or any other
  feature). The compatibility screen shows the declaration result.
- **Account kit** — the My Account area is rebuilt with DashWoo markup, tokens and
  RTL: sidebar/top/cards layouts, a dashboard hero, orders, downloads, addresses,
  payment methods, account details, forms and logout. Twelve Elementor widgets in a
  dedicated *DashWoo — My Account* category plus the `[dashwoo_account]` shortcode,
  and a native bridge that renames, reorders and hides WooCommerce's own endpoints
  (third-party endpoints included) in place.
- **Settings Center** — 38 sections / 220 fields in 7 groups, schema-driven,
  exportable and importable as JSON.
- **Version compatibility layer** — adapters per component, activation check,
  warning screen and automatic Compatibility Mode instead of a hard dependency.
  Optional host capabilities (no `php-gd`, no `php-zip`) are reported as neutral
  *informational* rows with an explanation, and never turn the report yellow.
- **REST API** `dashwoo/v1` — routes for settings, fonts, icons, assets, tokens and
  the account panel, plus `/system/diagnostics` with the raw server facts
  (extensions, `disable_functions`, ini limits) for support tickets.
- **i18n** — English source strings, a complete Persian catalogue (916 strings in
  `languages/dashwoo-fa_IR.po` / `.mo`, plus the script catalogue
  `languages/dashwoo-fa_IR-<hash>.json`), `Language` layer with a site / English /
  Persian choice, and RTL output.

## The My Account kit

The account area is the first stop of the "no more default WooCommerce look"
roadmap, and it ships complete:

| Widget (Elementor) | What it renders |
| --- | --- |
| `dashwoo_account_panel` | The two-pane panel: menu card + content card, swapped over REST |
| `dashwoo_account_dashboard` | Hero: avatar/initials, name, counters and the shortcut cards |
| `dashwoo_account_nav` | The account menu — sidebar, top tabs or shortcut cards |
| `dashwoo_account_profile` | Profile card with editable fields |
| `dashwoo_account_orders` / `downloads` / `addresses` / `payment` / `details` | One endpoint each, with WooCommerce's own handlers kept reachable |
| `dashwoo_account_order` | A single order, exactly as the panel's content card draws it |
| `dashwoo_account_forms` | Profile, password, address and login forms |
| `dashwoo_account_logout` | Logout button |
| `dashwoo_icon` | The DashWoo icon widget (variable font axes, brand tab) |

`[dashwoo_account]` prints the whole area in one shortcode, for classic pages and
block themes.

**Editing the account area with Elementor**

* The account source section carries a one-click builder: it writes the DashWoo layout
  (menu + dashboard + profile + orders) as a real Elementor document and opens the
  editor, and it shows a readiness report (Elementor version, widgets, account page,
  document, source mode).
* While the editor is open, the widgets render with sample data (three orders,
  downloads, a sample customer) so the design is always visible; a real customer's own
  data is never replaced.
* Elementor Pro users can also build the area as a Theme Builder template for the
  "My Account — DashWoo" location.
* Widgets are registered for modern Elementor (3.5+) and legacy installations alike.

**Settings — the "My Account" group**

- **Layout and sections** — layout (`sidebar` / `top` / `cards` / `cards_with_menu`),
  columns, icons, counters, sticky navigation, breadcrumb.
- **Account sections** — label, icon, order and visibility for every endpoint
  WooCommerce reports, including tabs added by other plugins.
- **Templates** — one shape per section (list, table, timeline, cards, plain), with
  theme overrides (`dashwoo/account/{section}-{variation}.php`).
- **Two-column panel** — menu card next to the content card, swap / modal / stacked,
  AJAX, URL sync, mobile behaviour.
- **Look and colors** — accent colours, radius, avatar size, navigation width, stage
  background, the automatic-CSS master switch and the bare-markup mode.
- **Forms and behaviour** — profile fields, inline validation, post-login redirect,
  guest message, endpoint titles.
- **Template source** — `default` (WooCommerce keeps the page), `hybrid`, or
  `elementor` (DashWoo removes the native navigation/content and hands the page to
  the builder). If the page is not built with Elementor yet — or the platform runs
  in Compatibility Mode — the native page stays intact, so the account area is never
  blank.

Styles ship as one local CSS pack that is only enqueued on the account page, inside
the builder preview, or on a page that uses `[dashwoo_account]`.

## Languages

| Path | What it is |
| --- | --- |
| `languages/dashwoo.pot` | The template with every English source string |
| `languages/dashwoo-fa_IR.po` | The Persian translation (editable in Poedit) |
| `languages/dashwoo-fa_IR.mo` | The compiled catalogue WordPress reads |
| `languages/dashwoo-fa_IR-<hash>.json` | The catalogue `wp.i18n` hands to the admin script |

Refresh them after changing a string:

```bash
python3 tools/i18n/build-catalogue.py            # sync POT/PO, compile MO + JSON
python3 tools/i18n/build-catalogue.py --strict   # …and fail if a string is untranslated
```

The `Platform language` setting (`DashWoo → System → General`) decides what DashWoo
speaks: **Follow the site language** (default), **English** or **Persian**. The
filters are scoped to the `dashwoo` text domain, so WooCommerce and the active theme
keep following the site language.

## Installation

1. Download `dashwoo-1.6.0.zip` from the [Releases](../../releases) page.
2. WordPress → **Plugins → Add New → Upload Plugin** → choose the zip → **Install**.
3. Activate, then open **DashWoo** in the admin menu.

Requires **PHP 8.0+**, **WordPress 6.4+**. WooCommerce 8.5+ and Elementor 3.24+
are optional: without them DashWoo keeps working and simply reports a warning.

## Where files live

```
wp-content/uploads/dashwoo/
├── fonts/    icons/    images/    svg/    custom/
└── cache/    compiled design-token + icon stylesheets
```

Nothing is ever written inside the plugin folder, so updating the plugin never
deletes your fonts, icons or images. Uninstalling keeps them too, unless you tick
“remove data on uninstall” in the settings.

## Integrity

Every build ships `BUILD-MANIFEST.txt` with the sha256 of each file, and the
release is verified by `bin/verify-build.php` (archive layout, hashes, plugin
header, PHP syntax of every shipped file, full autoloader class-map resolution
and a CDN scan) before publishing. `php bin/lint.php` and `php tests/run.php`
run the full local gate.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE). The bundled Google Fonts catalogue
data is provided for convenience; the fonts themselves are served under their own
licenses (mostly SIL OFL 1.1) by Google Fonts.
