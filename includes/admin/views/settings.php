<?php
/**
 * Settings Center: schema-driven form.
 *
 * @package DashWoo
 *
 * @var array<string,mixed> $context View context.
 */

defined( 'ABSPATH' ) || exit;

require DASHWOO_INCLUDES . 'admin/views/partial-nav.php';

$dw_key      = $context['section'];
$dw_section  = $context['sections'][ $dw_key ];
$dw_values   = isset( $context['settings'][ $dw_key ] ) ? $context['settings'][ $dw_key ] : array();
$dw_admin    = \DashWoo\Admin\Admin::instance();
?>
	<div class="dw-grid dw-grid--main">
		<div class="dw-card dw-card--form">
			<h2><?php echo esc_html( $dw_section['label'] ); ?></h2>
			<?php if ( ! empty( $dw_section['description'] ) ) : ?>
				<p class="description"><?php echo esc_html( $dw_section['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( empty( $dw_section['fields'] ) ) : ?>
				<p>این بخش فیلد تنظیماتی ندارد؛ از طریق ابزارهای زیر مدیریت می‌شود.</p>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( $context['action_url'] ); ?>">
				<?php wp_nonce_field( 'dashwoo_action' ); ?>
				<input type="hidden" name="action" value="dashwoo_action" />
				<input type="hidden" name="dw_action" value="save_settings" />
				<input type="hidden" name="section" value="<?php echo esc_attr( $dw_key ); ?>" />

				<table class="form-table dw-form">
					<tbody>
					<?php foreach ( (array) $dw_section['fields'] as $dw_field ) : ?>
						<?php
						$dw_name  = 'dw[' . $dw_field['key'] . ']';
						$dw_value = $dw_values[ $dw_field['key'] ] ?? $dw_field['default'];

						// A field may declare `requires`: the host capability it needs. When
						// that capability is missing the control is shown read-only with the
						// reason, because the runtime gate keeps the feature off anyway.
						$dw_lock        = '';
						$dw_lock_reason = '';

						if ( ! empty( $dw_field['requires'] ) ) {
							$dw_state = $context['capabilities']['features'][ $dw_field['key'] ] ?? array();

							if ( isset( $dw_state['state'] ) && 'blocked' === $dw_state['state'] ) {
								$dw_lock        = 'disabled';
								$dw_lock_reason = $dw_field['hint'] ?? 'قابلیت لازم روی این هاست موجود نیست.';
							}
						}
						?>
						<tr>
							<th scope="row"><label for="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"><?php echo esc_html( $dw_field['label'] ); ?></label></th>
							<td>
								<?php if ( 'toggle' === $dw_field['type'] ) : ?>
									<label class="dw-switch">
										<input type="checkbox" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
											name="<?php echo esc_attr( $dw_name ); ?>" value="1"
											<?php checked( (bool) $dw_value ); ?> <?php echo esc_attr( $dw_lock ); ?> />
										<span>فعال</span>
									</label>
									<?php if ( $dw_lock_reason ) : ?>
										<p class="dw-hint"><?php echo esc_html( $dw_lock_reason ); ?></p>
									<?php endif; ?>

								<?php elseif ( 'select' === $dw_field['type'] ) : ?>
									<select id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>">
										<?php foreach ( (array) $dw_field['options'] as $dw_option => $dw_label ) : ?>
											<option value="<?php echo esc_attr( $dw_option ); ?>" <?php selected( (string) $dw_value, (string) $dw_option ); ?>>
												<?php echo esc_html( $dw_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

								<?php elseif ( 'multi_select' === $dw_field['type'] ) : ?>
									<select id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>[]" multiple size="6">
										<?php foreach ( (array) $dw_field['options'] as $dw_option => $dw_label ) : ?>
											<option value="<?php echo esc_attr( $dw_option ); ?>"
												<?php echo in_array( (string) $dw_option, array_map( 'strval', (array) $dw_value ), true ) ? 'selected' : ''; ?>>
												<?php echo esc_html( $dw_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>

								<?php elseif ( 'color' === $dw_field['type'] ) : ?>
									<input type="text" class="dw-color" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>"
										placeholder="#000000" data-color-picker="1" />

								<?php elseif ( 'number' === $dw_field['type'] ) : ?>
									<input type="number" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>"
										min="<?php echo esc_attr( (string) ( $dw_field['min'] ?? '' ) ); ?>"
										max="<?php echo esc_attr( (string) ( $dw_field['max'] ?? '' ) ); ?>"
										step="<?php echo esc_attr( (string) ( $dw_field['step'] ?? 1 ) ); ?>" />

								<?php elseif ( 'textarea' === $dw_field['type'] ) : ?>
									<textarea id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>" rows="5" class="large-text"><?php echo esc_textarea( (string) $dw_value ); ?></textarea>

								<?php elseif ( 'code' === $dw_field['type'] ) : ?>
									<textarea id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>" name="<?php echo esc_attr( $dw_name ); ?>" rows="8" class="large-text code" dir="ltr"><?php echo esc_textarea( (string) $dw_value ); ?></textarea>

								<?php else : ?>
									<input type="text" class="regular-text" id="dw-<?php echo esc_attr( $dw_field['key'] ); ?>"
										name="<?php echo esc_attr( $dw_name ); ?>" value="<?php echo esc_attr( (string) $dw_value ); ?>" />
								<?php endif; ?>

								<?php if ( ! empty( $dw_field['description'] ) ) : ?>
									<p class="description"><?php echo esc_html( $dw_field['description'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p class="dw-actions">
					<button class="button button-primary" type="submit">ذخیره تنظیمات</button>
					<button class="button" type="submit" name="dw_action" value="reset_section">بازنشانی به پیش‌فرض</button>
				</p>
			</form>
			<?php
			/**
			 * Fires after the schema-driven fields of one settings section.
			 *
			 * The account pack uses it for its "build the layout in Elementor" card;
			 * any other subsystem can add its own panel without touching this view.
			 *
			 * @param string              $dw_key Section key.
			 * @param array<string,mixed> $context View context.
			 */
			do_action( 'dashwoo_settings_section_after_fields', $dw_key, $context );
			?>
			<?php endif; ?>
		</div>

		<div class="dw-card dw-card--aside">
			<h3>در این گروه</h3>
			<?php foreach ( $context['nav']['clusters'] as $dw_cluster ) : ?>
				<div class="dw-aside-cluster">
					<h4><?php echo esc_html( $dw_cluster['label'] ); ?></h4>
					<ul class="dw-list">
						<?php foreach ( $dw_cluster['sections'] as $dw_item ) : ?>
							<li class="<?php echo ! empty( $dw_item['active'] ) ? 'is-active' : ''; ?>">
								<a href="<?php echo esc_url( $dw_item['url'] ); ?>"><?php echo esc_html( $dw_item['label'] ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
			<p class="description"><a href="<?php echo esc_url( $dw_admin->group_url( $context['nav']['group'] ) ); ?>">
				همهٔ زیرگروه‌های <?php echo esc_html( $context['group_labels'][ $context['nav']['group'] ]['label'] ?? $context['nav']['group'] ); ?>
			</a></p>
		</div>
	</div>
</div>
