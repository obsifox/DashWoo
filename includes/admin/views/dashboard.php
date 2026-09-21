<?php
/**
 * Dashboard view.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_summary  = $context['compatibility']['summary'] ?? array();
$dw_storage  = $context['storage'];
$dw_fonts    = count( $context['fonts'] );
$dw_icons    = count( $context['icons'] );
$dw_compiled = $context['compiled'];
?>
	<div class="dw-grid dw-grid--4">
		<div class="dw-card">
			<h2><?php esc_html_e( 'Environment compatibility', 'dashwoo' ); ?></h2>
			<p class="dw-kpi"><?php echo (int) ( $dw_summary['ok'] ?? 0 ); ?><small> ✓</small>
				<?php echo (int) ( $dw_summary['warning'] ?? 0 ); ?><small> ⚠</small>
				<?php echo (int) ( $dw_summary['error'] ?? 0 ); ?><small> ✕</small></p>
			<a class="button" href="<?php echo esc_url( \DashWoo\Admin\Admin::instance()->url( 'system' ) ); ?>"><?php esc_html_e( 'Full report', 'dashwoo' ); ?></a>
		</div>
		<div class="dw-card">
			<h2><?php esc_html_e( 'Local fonts', 'dashwoo' ); ?></h2>
			<p class="dw-kpi"><?php echo (int) $dw_fonts; ?></p>
			<p class="description"><?php esc_html_e( 'Icon styles:', 'dashwoo' ); ?> <?php echo (int) $dw_icons; ?></p>
		</div>
		<div class="dw-card">
			<h2><?php esc_html_e( 'Token CSS', 'dashwoo' ); ?></h2>
			<p class="description"><?php echo esc_html( $dw_compiled['file'] ?? '—' ); ?></p>
			<p class="description"><?php echo (int) ( $dw_compiled['variables'] ?? 0 ); ?> <?php esc_html_e( 'variables', 'dashwoo' ); ?> • <?php echo esc_html( size_format( (int) ( $dw_compiled['bytes'] ?? 0 ) ) ); ?></p>
		</div>
		<div class="dw-card">
			<h2><?php esc_html_e( 'Storage', 'dashwoo' ); ?></h2>
			<p class="dw-kpi"><?php echo esc_html( size_format( (int) $dw_storage['total'] ) ); ?></p>
			<p class="description"><code><?php echo esc_html( $dw_storage['basedir'] ); ?></code></p>
		</div>
	</div>

	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2><?php esc_html_e( 'Registered assets', 'dashwoo' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Type', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Count', 'dashwoo' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( (array) ( $dw_storage['counts'] ?? array() ) as $dw_type => $dw_count ) : ?>
					<tr><td><?php echo esc_html( $dw_type ); ?></td><td><?php echo (int) $dw_count; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="dw-card">
			<h2><?php esc_html_e( 'CDN status', 'dashwoo' ); ?></h2>
			<p><span class="dw-pill dw-pill--ok">✓ <?php esc_html_e( 'no CDN dependency', 'dashwoo' ); ?></span></p>
			<p class="description"><?php esc_html_e( 'Every font and icon is loaded from', 'dashwoo' ); ?> <code>uploads/dashwoo/</code> <?php esc_html_e( 'After the download no request is made to any external service.', 'dashwoo' ); ?></p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="compile" />
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Rebuild the token CSS', 'dashwoo' ); ?></button>
			</form>
		</div>
	</div>
</div>
