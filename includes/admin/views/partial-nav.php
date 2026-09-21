<?php
/**
 * Shared navigation header for the DashWoo screens.
 *
 * Three levels, never a flat list:
 *   1. groups   - mirrors the WordPress submenu (dashboard, design, fonts and icons, …)
 *   2. clusters - the tab row of the active group (design foundation | components)
 *   3. sections - the chips of the active cluster
 * plus a breadcrumb that shows where the shop owner currently is.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

$dw_nav   = isset( $context['nav'] ) ? $context['nav'] : array();
$dw_done  = isset( $_GET['dw_done'] ) ? sanitize_key( wp_unslash( $_GET['dw_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$dw_msg   = isset( $_GET['dw_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['dw_msg'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

$dw_groups   = isset( $dw_nav['groups'] ) ? $dw_nav['groups'] : array();
$dw_clusters = isset( $dw_nav['clusters'] ) ? $dw_nav['clusters'] : array();
$dw_active_c = isset( $dw_nav['active_cluster'] ) ? $dw_nav['active_cluster'] : '';
$dw_crumb    = isset( $dw_nav['breadcrumb'] ) ? $dw_nav['breadcrumb'] : array();
$dw_screens  = array( 'assets-library', 'system-status', 'system' );
?>
<div class="wrap dashwoo-wrap">
	<h1 class="dw-title">
		<span class="dw-logo" aria-hidden="true"></span>
		DashWoo
		<span class="dw-badge">v<?php echo esc_html( DASHWOO_VERSION ); ?></span>
		<span class="dw-badge dw-badge--<?php echo esc_attr( $context['mode'] ); ?>">
			<?php echo 'compatibility' === $context['mode'] ? 'Compatibility Mode' : 'Full Override'; ?>
		</span>
	</h1>

	<?php if ( '' !== $dw_done ) : ?>
		<div class="notice <?php echo '1' === $dw_done ? 'notice-success' : 'notice-error'; ?> is-dismissible">
			<p><?php echo esc_html( $dw_msg ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $dw_groups ) : ?>
		<nav class="dw-nav dw-nav--groups" aria-label=__( 'DashWoo groups', 'dashwoo' )>
			<?php foreach ( $dw_groups as $dw_group ) : ?>
				<a class="dw-nav__item <?php echo ! empty( $dw_group['active'] ) ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( $dw_group['url'] ); ?>">
					<?php echo esc_html( $dw_group['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $dw_crumb ) : ?>
		<p class="dw-breadcrumb">
			<span class="dw-breadcrumb__root">DashWoo</span>
			<?php foreach ( $dw_crumb as $dw_i => $dw_part ) : ?>
				<span class="dw-breadcrumb__sep" aria-hidden="true">‹</span>
				<span class="<?php echo $dw_i === count( $dw_crumb ) - 1 ? 'dw-breadcrumb__current' : 'dw-breadcrumb__part'; ?>">
					<?php echo esc_html( $dw_part ); ?>
				</span>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>

	<?php if ( count( $dw_clusters ) > 1 ) : ?>
		<nav class="dw-tabs" aria-label=__( 'Subgroups', 'dashwoo' )>
			<?php foreach ( $dw_clusters as $dw_cluster ) : ?>
				<a class="dw-tabs__tab <?php echo $dw_cluster['key'] === $dw_active_c ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( $dw_cluster['url'] ); ?>">
					<?php echo esc_html( $dw_cluster['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php foreach ( $dw_clusters as $dw_cluster ) : ?>
		<?php
		if ( $dw_cluster['key'] !== $dw_active_c ) {
			continue;
		}

		$dw_special = array();
		$dw_schema  = array();

		foreach ( $dw_cluster['sections'] as $dw_item ) {
			if ( in_array( $dw_item['key'], $dw_screens, true ) ) {
				$dw_special[] = $dw_item;
			} else {
				$dw_schema[] = $dw_item;
			}
		}
		?>
		<nav class="dw-subnav" aria-label=__( 'Sections in this group', 'dashwoo' )>
			<?php foreach ( $dw_special as $dw_item ) : ?>
				<a class="dw-nav__item dw-nav__item--screen <?php echo ! empty( $dw_item['active'] ) ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( $dw_item['url'] ); ?>">
					<span class="dw-nav__icon" aria-hidden="true">▤</span>
					<?php echo esc_html( $dw_item['label'] ); ?>
				</a>
			<?php endforeach; ?>

			<?php if ( $dw_special && $dw_schema ) : ?>
				<span class="dw-subnav__sep" aria-hidden="true"></span>
			<?php endif; ?>

			<?php foreach ( $dw_schema as $dw_item ) : ?>
				<a class="dw-nav__item <?php echo ! empty( $dw_item['active'] ) ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( $dw_item['url'] ); ?>">
					<?php echo esc_html( $dw_item['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endforeach; ?>
