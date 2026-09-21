<?php
/**
 * Asset Manager screen.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_admin   = \DashWoo\Admin\Admin::instance();
$dw_type    = $context['type'];
$dw_rows    = $context['rows'];
$dw_storage = $context['storage'];
$dw_tabs    = array(
	'font'   => __( 'Fonts', 'dashwoo' ),
	'icon'   => __( 'Icons', 'dashwoo' ),
	'image'  => __( 'Images', 'dashwoo' ),
	'svg'    => 'SVG',
	'custom' => 'CSS/JS',
);
?>
	<nav class="dw-subnav">
		<?php foreach ( $dw_tabs as $dw_slug => $dw_label ) : ?>
			<a class="dw-nav__item <?php echo $dw_type === $dw_slug ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( array( 'page' => 'dashwoo-assets', 'type' => $dw_slug ), admin_url( 'admin.php' ) ) ); ?>">
				<?php echo esc_html( $dw_label ); ?>
				<span class="dw-count"><?php echo (int) ( $dw_storage['counts'][ $dw_slug ] ?? 0 ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'font' === $dw_type ) : ?>
		<div class="dw-grid dw-grid--2">
			<div class="dw-card">
				<h2><?php esc_html_e( 'Add a font from Google Fonts', 'dashwoo' ); ?></h2>
				<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
					<?php wp_nonce_field( 'dashwoo_action' ); ?>
					<input type="hidden" name="action" value="dashwoo_action" />
					<input type="hidden" name="dw_action" value="install_font" />
					<p>
						<label for="dw-family"><?php esc_html_e( 'Font family', 'dashwoo' ); ?></label>
						<select id="dw-family" name="family" class="dw-select-search" data-search="1">
							<?php foreach ( (array) $context['catalog'] as $dw_font ) : ?>
								<option value="<?php echo esc_attr( $dw_font['family'] ); ?>">
									<?php echo esc_html( $dw_font['family'] . ( $dw_font['persian'] ? __( ' — Persian', 'dashwoo' ) : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label><?php esc_html_e( 'Weights', 'dashwoo' ); ?></label><br />
						<?php foreach ( array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) as $dw_weight ) : ?>
							<label class="dw-inline"><input type="checkbox" name="weights[]" value="<?php echo esc_attr( $dw_weight ); ?>"
								<?php checked( in_array( $dw_weight, array( '400', '500', '700' ), true ) ); ?> /> <?php echo esc_html( $dw_weight ); ?></label>
						<?php endforeach; ?>
					</p>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Download and store it locally', 'dashwoo' ); ?></button>
					<p class="description"><?php esc_html_e( 'After the download the files live in', 'dashwoo' ); ?> <code>uploads/dashwoo/fonts/</code> <?php esc_html_e( 'and no request is made to Google.', 'dashwoo' ); ?></p>
				</form>
			</div>

			<div class="dw-card">
				<h2><?php esc_html_e( 'Upload your own font', 'dashwoo' ); ?></h2>
				<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dashwoo_action' ); ?>
					<input type="hidden" name="action" value="dashwoo_action" />
					<input type="hidden" name="dw_action" value="upload_font" />
					<p><input type="text" name="label" placeholder=__( 'Font family name', 'dashwoo' ) class="regular-text" required /></p>
					<p><input type="file" name="font_file" accept=".woff2,.woff,.ttf,.otf" required /></p>
					<button class="button" type="submit"><?php esc_html_e( 'Upload', 'dashwoo' ); ?></button>
				</form>
			</div>
		</div>
	<?php elseif ( 'icon' === $dw_type ) : ?>
		<div class="dw-card">
			<h2><?php esc_html_e( 'Material Symbols and Material Icons', 'dashwoo' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Material Symbols is a <strong>variable font</strong>: one WOFF2 file is downloaded for every style and', 'dashwoo' ); ?>
				<?php esc_html_e( 'Weight / Fill / Grade / Optical Size are set at render time through <code>font-variation-settings</code>.', 'dashwoo' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="install_icons" />
				<select name="style">
					<?php foreach ( (array) $context['icon_styles'] as $dw_style => $dw_label ) : ?>
						<option value="<?php echo esc_attr( $dw_style ); ?>"><?php echo esc_html( $dw_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="dw-inline"><input type="checkbox" name="force" value="1" /> <?php esc_html_e( 'Download again', 'dashwoo' ); ?></label>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Download', 'dashwoo' ); ?></button>
			</form>
		</div>
	<?php endif; ?>

	<div class="dw-card">
		<h2><?php esc_html_e( 'Registered assets', 'dashwoo' ); ?> (<?php echo esc_html( $dw_tabs[ $dw_type ] ?? $dw_type ); ?>)</h2>
		<table class="widefat striped dw-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Label', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Slug', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Provider', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Version', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Status', 'dashwoo' ); ?></th>
					<th><?php esc_html_e( 'Default', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Size', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Last change', 'dashwoo' ); ?></th><th><?php esc_html_e( 'Actions', 'dashwoo' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! $dw_rows ) : ?>
				<tr><td colspan="9"><?php esc_html_e( 'No asset is registered yet.', 'dashwoo' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $dw_rows as $dw_row ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $dw_row['label'] ); ?></strong></td>
					<td><code><?php echo esc_html( $dw_row['slug'] ); ?></code></td>
					<td><?php echo esc_html( $dw_row['provider'] ); ?></td>
					<td><code><?php echo esc_html( $dw_row['version'] ); ?></code></td>
					<td><?php echo 'active' === $dw_row['status'] ? '<span class="dw-pill dw-pill--ok">' . esc_html__( 'active', 'dashwoo' ) . '</span>' : '<span class="dw-pill">' . esc_html__( 'inactive', 'dashwoo' ) . '</span>'; ?></td>
					<td><?php echo $dw_row['is_default'] ? '✓' : '—'; ?></td>
					<td><?php echo esc_html( size_format( (int) $dw_row['size'] ) ); ?></td>
					<td><?php echo esc_html( $dw_row['updated_at'] ); ?></td>
					<td class="dw-row-actions">
						<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>" class="dw-inline">
							<?php wp_nonce_field( 'dashwoo_action' ); ?>
							<input type="hidden" name="action" value="dashwoo_action" />
							<input type="hidden" name="slug" value="<?php echo esc_attr( $dw_row['slug'] ); ?>" />
							<button class="button button-small" type="submit" name="dw_action" value="update_font"><?php esc_html_e( 'Update', 'dashwoo' ); ?></button>
							<button class="button button-small" type="submit" name="dw_action" value="delete_font"><?php esc_html_e( 'Delete', 'dashwoo' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
