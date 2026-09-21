<?php
/**
 * Capabilities screen: what the host provides and which DashWoo features that gates.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_status = isset( $context['capabilities'] ) ? $context['capabilities'] : array();
$dw_checks = isset( $dw_status['checks'] ) ? $dw_status['checks'] : array();
$dw_feat   = isset( $dw_status['features'] ) ? $dw_status['features'] : array();
$dw_admin  = \DashWoo\Admin\Admin::instance();

$dw_labels = array(
	'on'      => array( __( 'On', 'dashwoo' ), 'dw-pill--ok' ),
	'off'     => array( __( 'Off by your choice', 'dashwoo' ), 'dw-pill--warning' ),
	'blocked' => array( __( 'Off automatically (host capability missing)', 'dashwoo' ), 'dw-pill--info' ),
);

$dw_on      = 0;
$dw_blocked = 0;

foreach ( $dw_feat as $dw_feature ) {
	if ( 'on' === $dw_feature['state'] ) {
		$dw_on++;
	}
	if ( 'blocked' === $dw_feature['state'] ) {
		$dw_blocked++;
	}
}
?>
	<div class="dw-grid dw-grid--4">
		<div class="dw-card">
			<p class="description"><?php esc_html_e( 'Host capabilities found', 'dashwoo' ); ?></p>
			<p class="dw-kpi"><?php echo (int) count( array_filter( $dw_checks, static function ( $c ) { return ! empty( $c['available'] ); } ) ); ?> / <?php echo (int) count( $dw_checks ); ?></p>
		</div>
		<div class="dw-card">
			<p class="description"><?php esc_html_e( 'Features that are on', 'dashwoo' ); ?></p>
			<p class="dw-kpi"><?php echo (int) $dw_on; ?> / <?php echo (int) count( $dw_feat ); ?></p>
		</div>
		<div class="dw-card">
			<p class="description"><?php esc_html_e( 'Switched off automatically', 'dashwoo' ); ?></p>
			<p class="dw-kpi"><?php echo (int) $dw_blocked; ?></p>
		</div>
		<div class="dw-card">
			<p class="description"><?php esc_html_e( 'Last automatic check', 'dashwoo' ); ?></p>
			<p class="dw-kpi"><?php echo esc_html( isset( $dw_status['checked_human'] ) ? $dw_status['checked_human'] : '—' ); ?></p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="capabilities_recheck" />
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Run the capability check again', 'dashwoo' ); ?></button>
			</form>
		</div>
	</div>

	<div class="dw-card">
		<h2><?php esc_html_e( 'DashWoo features and their automatic state', 'dashwoo' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'DashWoo never depends on a host capability that is optional: when the host does not have something,', 'dashwoo' ); ?>
			<?php esc_html_e( 'the matching feature stays off by itself and switches <strong>on automatically</strong> as soon as it is there.', 'dashwoo' ); ?>
		</p>

		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'State', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'Needs capability', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'What happens when it is off', 'dashwoo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $dw_feat as $dw_id => $dw_feature ) : ?>
					<?php $dw_state = isset( $dw_labels[ $dw_feature['state'] ] ) ? $dw_labels[ $dw_feature['state'] ] : array( $dw_feature['state'], 'dw-pill' ); ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $dw_feature['label'] ); ?></strong>
							<div class="dw-hint"><?php echo esc_html( $dw_feature['effect'] ); ?></div>
						</td>
						<td><span class="dw-pill <?php echo esc_attr( $dw_state[1] ); ?>"><?php echo esc_html( $dw_state[0] ); ?></span></td>
						<td>
							<?php if ( empty( $dw_feature['requires'] ) ) : ?>
								<span class="dw-pill"><?php esc_html_e( 'always on', 'dashwoo' ); ?></span>
							<?php else : ?>
								<?php foreach ( $dw_feature['requires'] as $dw_req ) : ?>
									<span class="dw-pill <?php echo ! empty( $dw_checks[ $dw_req ]['available'] ) ? 'dw-pill--ok' : 'dw-pill--info'; ?>">
										<?php echo esc_html( $dw_checks[ $dw_req ]['label'] ?? $dw_req ); ?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( $dw_feature['when_off'] ); ?>
							<?php if ( 'blocked' === $dw_feature['state'] ) : ?>
								<div class="dw-hint"><?php echo esc_html( $dw_feature['hint'] ); ?></div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="dw-card">
		<h2><?php esc_html_e( 'Host capabilities', 'dashwoo' ); ?></h2>
		<p class="description"><?php esc_html_e( 'This table is checked automatically every 6 hours and by hand with the button above.', 'dashwoo' ); ?></p>

		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Capability', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'State', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'Details', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'Role in DashWoo', 'dashwoo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $dw_checks as $dw_id => $dw_check ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $dw_check['label'] ); ?></strong>
							<?php if ( ! empty( $dw_check['required'] ) ) : ?>
								<span class="dw-pill dw-pill--err"><?php esc_html_e( 'required', 'dashwoo' ); ?></span>
							<?php else : ?>
								<span class="dw-pill dw-pill--info"><?php esc_html_e( 'optional', 'dashwoo' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<span class="dw-pill <?php echo ! empty( $dw_check['available'] ) ? 'dw-pill--ok' : 'dw-pill--info'; ?>">
								<?php echo ! empty( $dw_check['available'] ) ? '✓' : 'ℹ'; ?>
							</span>
						</td>
						<td><?php echo esc_html( $dw_check['detail'] ); ?></td>
						<td><div class="dw-hint"><?php echo esc_html( $dw_check['hint'] ); ?></div></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Ready-made text for your host', 'dashwoo' ); ?></h3>
		<p class="description"><?php esc_html_e( 'When a feature stays off automatically, copy this text and send it to your host\'s support.', 'dashwoo' ); ?></p>
		<textarea class="dw-diagnostics" rows="10" readonly><?php
		foreach ( $dw_checks as $dw_id => $dw_check ) {
			printf(
				"%-18s %-4s %s\n",
				$dw_id,
				! empty( $dw_check['available'] ) ? 'on' : 'off',
				$dw_check['detail']
			);
		}
		?></textarea>
	</div>

	<div class="dw-card">
		<h2><?php esc_html_e( 'Compatibility declaration for the WooCommerce features', 'dashwoo' ); ?></h2>
		<?php $dw_wc = \DashWoo\Compatibility\WooCommerce_Features::instance()->status(); ?>

		<?php if ( empty( $dw_wc['aware'] ) ) : ?>
			<p class="description"><?php esc_html_e( 'WooCommerce is not active, so no declaration is needed. As soon as it is active, DashWoo declares its compatibility on the', 'dashwoo' ); ?>
				<code>before_woocommerce_init</code> <?php esc_html_e( 'hook.', 'dashwoo' ); ?></p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'WooCommerce only checks plugins that carry the <code>WC tested up to</code> header and counts a plugin that never declared', 'dashwoo' ); ?>
				<?php esc_html_e( 'itself as “incompatible” for features such as HPOS. DashWoo declares compatibility for every feature.', 'dashwoo' ); ?>
			</p>
			<ul class="dw-list">
				<li><?php esc_html_e( 'Declared in this request:', 'dashwoo' ); ?> <strong><?php echo (int) count( (array) $dw_wc['declared'] ); ?></strong></li>
				<li><?php esc_html_e( 'WooCommerce sees DashWoo as compatible:', 'dashwoo' ); ?> <strong><?php echo (int) count( (array) $dw_wc['compatible'] ); ?></strong></li>
				<li><?php esc_html_e( 'Incompatible:', 'dashwoo' ); ?> <strong><?php echo (int) count( (array) $dw_wc['incompatible'] ); ?></strong>
					<?php if ( ! empty( $dw_wc['incompatible'] ) ) : ?>
						— <?php echo esc_html( implode( ', ', $dw_wc['incompatible'] ) ); ?>
					<?php endif; ?></li>
				<li><?php esc_html_e( 'Unknown to WooCommerce:', 'dashwoo' ); ?> <strong><?php echo (int) count( (array) $dw_wc['uncertain'] ); ?></strong></li>
				<li><?php esc_html_e( 'Outside the DashWoo audit list:', 'dashwoo' ); ?> <strong><?php echo (int) count( (array) $dw_wc['unknown'] ); ?></strong>
					<?php if ( ! empty( $dw_wc['unknown'] ) ) : ?>
						— <?php echo esc_html( implode( ', ', $dw_wc['unknown'] ) ); ?>
					<?php endif; ?></li>
			</ul>
		<?php endif; ?>

		<p>
			<a class="button" href="<?php echo esc_url( $dw_admin->url( 'features' ) ); ?>"><?php esc_html_e( 'Set the features by hand', 'dashwoo' ); ?></a>
			<a class="button" href="<?php echo esc_url( $dw_admin->url( 'system' ) ); ?>"><?php esc_html_e( 'View the full compatibility report', 'dashwoo' ); ?></a>
		</p>
	</div>
</div>
