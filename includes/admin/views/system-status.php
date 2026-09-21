<?php
/**
 * System Status + compatibility report.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_report  = $context['compatibility'];
$dw_checks  = $dw_report['checks'] ?? array();
$dw_summary = $dw_report['summary'] ?? array();
$dw_degraded = $dw_report['degraded'] ?? array();
?>
	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2><?php esc_html_e( 'Check summary', 'dashwoo' ); ?></h2>
			<p class="dw-kpi">
				<span class="dw-pill dw-pill--ok">✓ <?php echo (int) ( $dw_summary['ok'] ?? 0 ); ?> <?php esc_html_e( 'passed', 'dashwoo' ); ?></span>
				<span class="dw-pill dw-pill--info">ℹ <?php echo (int) ( $dw_summary['notice'] ?? 0 ); ?> <?php esc_html_e( 'informational', 'dashwoo' ); ?></span>
				<span class="dw-pill dw-pill--warn">⚠ <?php echo (int) ( $dw_summary['warning'] ?? 0 ); ?> <?php esc_html_e( 'warnings', 'dashwoo' ); ?></span>
				<span class="dw-pill dw-pill--err">✕ <?php echo (int) ( $dw_summary['error'] ?? 0 ); ?> <?php esc_html_e( 'errors', 'dashwoo' ); ?></span>
				<span class="dw-pill">– <?php echo (int) ( $dw_summary['na'] ?? 0 ); ?> <?php esc_html_e( 'not relevant', 'dashwoo' ); ?></span>
			</p>
			<p class="description">
				<?php esc_html_e( 'The <strong>ℹ</strong> mark means “this capability is missing on the host, but DashWoo does not need it” —', 'dashwoo' ); ?>
				<?php esc_html_e( 'like <code>zip</code> or <code>gd</code>. Those are neither errors nor do they touch the compatibility mode.', 'dashwoo' ); ?>
			</p>
			<p class="description"><?php esc_html_e( 'Current mode:', 'dashwoo' ); ?> <strong><?php echo 'compatibility' === $context['mode'] ? 'Compatibility Mode' : 'Full Override'; ?></strong>
				— <?php esc_html_e( 'last check:', 'dashwoo' ); ?> <?php echo esc_html( isset( $dw_report['generated_at'] ) ? gmdate( 'Y-m-d H:i', (int) $dw_report['generated_at'] ) : '—' ); ?></p>

			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="recheck" />
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Run the environment check again', 'dashwoo' ); ?></button>
			</form>
		</div>

		<div class="dw-card">
			<h2><?php esc_html_e( 'Switched-off capabilities', 'dashwoo' ); ?></h2>
			<?php if ( ! $dw_degraded ) : ?>
				<p><span class="dw-pill dw-pill--ok"><?php esc_html_e( 'every capability is available', 'dashwoo' ); ?></span></p>
			<?php else : ?>
				<ul class="dw-list">
					<?php foreach ( (array) $dw_degraded as $dw_feature ) : ?>
						<li><code><?php echo esc_html( $dw_feature ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="dw-card">
		<h2><?php esc_html_e( 'Check details', 'dashwoo' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr><th style="width:40px"><?php esc_html_e( 'Status', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Adapter', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Check', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Details', 'dashwoo' ); ?></th></tr>
			</thead>
			<tbody>
			<?php foreach ( (array) $dw_checks as $dw_check ) : ?>
				<?php
				$dw_class = 'dw-pill';
				if ( 'ok' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--ok';
				} elseif ( 'notice' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--info';
				} elseif ( 'warning' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--warn';
				} elseif ( 'error' === $dw_check['status'] ) {
					$dw_class .= ' dw-pill--err';
				}
				?>
				<tr>
					<td><span class="<?php echo esc_attr( $dw_class ); ?>"><?php echo esc_html( $dw_check['symbol'] ); ?></span></td>
					<td><code><?php echo esc_html( $dw_check['adapter'] ); ?></code></td>
					<td><code><?php echo esc_html( $dw_check['id'] ); ?></code></td>
					<td>
						<?php echo esc_html( $dw_check['message'] ); ?>
						<?php if ( ! empty( $dw_check['hint'] ) ) : ?>
							<p class="dw-hint"><?php echo esc_html( $dw_check['hint'] ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php
	$dw_diagnostics = $context['diagnostics'] ?? array();
	$dw_plain       = '';

	if ( $dw_diagnostics ) {
		// Plain-text block: easy to copy into a support ticket.
		foreach ( (array) $dw_diagnostics as $dw_group => $dw_facts ) {
			$dw_plain .= '[' . $dw_group . ']' . "\n";

			foreach ( (array) $dw_facts as $dw_key => $dw_value ) {
				$dw_plain .= '  ' . str_pad( (string) $dw_key, 20 ) . ' : ' . ( is_scalar( $dw_value ) ? (string) $dw_value : wp_json_encode( $dw_value ) ) . "\n";
			}

			$dw_plain .= "\n";
		}
	}
	?>
	<div class="dw-card">
		<h2><?php esc_html_e( 'Environment diagnostics (for the host\'s support)', 'dashwoo' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'This text is the raw truth of the server: the PHP version, the installed extensions, the functions blocked in', 'dashwoo' ); ?>
			<code>disable_functions</code><?php esc_html_e( ', the download size limits and whether the asset folder is writable.', 'dashwoo' ); ?>
			<?php esc_html_e( 'Copy it whenever you need it and hand it to your host.', 'dashwoo' ); ?>
		</p>
		<textarea class="dw-diagnostics" readonly rows="12" onclick="this.select()"><?php echo esc_textarea( $dw_plain ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Machine-readable:', 'dashwoo' ); ?> <code>GET /wp-json/dashwoo/v1/system/diagnostics</code></p>
	</div>

	<div class="dw-grid dw-grid--2">
		<div class="dw-card">
			<h2><?php esc_html_e( 'Elementor kit sync', 'dashwoo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Integration level 3. It is off by default and a backup is taken before every change.', 'dashwoo' ); ?></p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<button class="button" type="submit" name="dw_action" value="kit_sync"><?php esc_html_e( 'Sync', 'dashwoo' ); ?></button>
				<button class="button" type="submit" name="dw_action" value="kit_revert"><?php esc_html_e( 'Restore from the backup', 'dashwoo' ); ?></button>
			</form>
		</div>
		<div class="dw-card">
			<h2><?php esc_html_e( 'Cache', 'dashwoo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Generated files in', 'dashwoo' ); ?> <code>uploads/dashwoo/cache/</code>.</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<button class="button" type="submit" name="dw_action" value="flush_cache"><?php esc_html_e( 'Flush the cache', 'dashwoo' ); ?></button>
				<button class="button" type="submit" name="dw_action" value="compile"><?php esc_html_e( 'Rebuild the tokens', 'dashwoo' ); ?></button>
			</form>
		</div>
	</div>
</div>
