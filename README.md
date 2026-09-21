# DashWoo — UI Platform for WooCommerce × Elementor

DashWoo turns every visual decision of a WooCommerce shop into locally served
assets and design tokens. Fonts, icons, images, SVG and custom code are managed
inside WordPress — **with zero dependency on any external CDN**.

> **فارسی:** DashWoo کنترل کامل ظاهر فروشگاه ووکامرس را در پیشخوان وردپرس می‌دهد؛
> فونت‌ها، آیکون‌ها، تصاویر و توکن‌های طراحی همه **به‌صورت محلی** ذخیره و سرو می‌شوند
> و پس از نصب، هیچ درخواستی به گوگل یا هیچ CDN دیگری زده نمی‌شود.

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
  feature whose requirement is missing switches itself off with a Persian
  explanation, and comes back on its own the moment the host gains it. Nothing has to
  be configured twice, and no missing extension ever shows up as a broken store.
  See the **قابلیت‌ها** tab in the System group.
- **No WooCommerce "incompatible plugin" notice** — DashWoo declares per-feature
  compatibility on `before_woocommerce_init`, so WooCommerce never lists it among the
  plugins that are incompatible with High-Performance Order Storage (or any other
  feature). The compatibility screen shows the declaration result.
- **Account kit (حساب کاربری)** — the My Account area is rebuilt with DashWoo
  markup, tokens and RTL: sidebar/top/cards layouts, a dashboard hero, orders,
  downloads, addresses, payment methods, account details, forms and logout. Ten
  Elementor widgets in a dedicated *DashWoo — حساب کاربری* category plus the
  `[dashwoo_account]` shortcode, and a native bridge that renames, reorders and
  hides WooCommerce's own endpoints (third-party endpoints included) in place.
- **Settings Center** — 36 sections / 184 fields in 7 groups, schema-driven,
  exportable and importable as JSON.
- **Version compatibility layer** — adapters per component, activation check,
  warning screen and automatic Compatibility Mode instead of a hard dependency.
  Optional host capabilities (no `php-gd`, no `php-zip`) are reported as neutral
  *informational* rows with a Persian explanation, and never turn the report yellow.
- **REST API** `dashwoo/v1` — 33 routes for settings, fonts, icons, assets, tokens,
  plus `/system/diagnostics` with the raw server facts (extensions,
  `disable_functions`, ini limits) for support tickets.
- **Full Persian / RTL** — the admin is Persian-first and `dir="rtl"` aware.

## The My Account kit (حساب کاربری)

The account area is the first stop of the "no more default WooCommerce look"
roadmap, and it ships complete:

| Widget (Elementor) | What it renders |
| --- | --- |
| `dashwoo_account_dashboard` | Hero: avatar/initials, name, counters and the shortcut cards |
| `dashwoo_account_nav` | The account menu — sidebar, top tabs or shortcut cards |
| `dashwoo_account_profile` | Profile card with editable fields |
| `dashwoo_account_orders` / `downloads` / `addresses` / `payment` / `details` | One endpoint each, with WooCommerce's own handlers kept reachable |
| `dashwoo_account_forms` | Profile, password, address and login forms |
| `dashwoo_account_logout` | Logout button |

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
  «حساب کاربری — DashWoo» location.
* Widgets are registered for modern Elementor (3.5+) and legacy installations alike.

**Settings — «حساب کاربری» group (5 sections)**

- **چیدمان و بخش‌ها** — layout (`sidebar` / `top` / `cards` / `cards_with_menu`),
  columns, icons, counters, sticky navigation, breadcrumb.
- **بخش‌های حساب کاربری** — label, icon, order and visibility for every endpoint
  WooCommerce reports, including tabs added by other plugins.
- **ظاهر و رنگ‌ها** — accent colours, radius, avatar size, navigation width, stage
  background.
- **فرم‌ها و رفتار** — profile fields, inline validation, post-login redirect, guest
  message, endpoint titles.
- **منبع قالب‌ها** — `default` (WooCommerce keeps the page), `hybrid`, or
  `elementor` (DashWoo removes the native navigation/content and hands the page to
  the builder). If the page is not built with Elementor yet — or the platform runs
  in Compatibility Mode — the native page stays intact, so the account area is never
  blank.

Styles ship as one local CSS pack that is only enqueued on the account page, inside
the builder preview, or on a page that uses `[dashwoo_account]`.

## Installation

1. Download `dashwoo-1.4.0.zip` from the [Releases](../../releases) page.
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
and a CDN scan) before publishing.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE). The bundled Google Fonts catalogue
data is provided for convenience; the fonts themselves are served under their own
licenses (mostly SIL OFL 1.1) by Google Fonts.
