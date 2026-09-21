<?php
/**
 * Elementor control: token picker (level 2 integration).
 *
 * The control stores a `var(--dw-*, fallback)` string, so Elementor's own CSS
 * stays hooked to the token system: change the token, everything updates.
 *
 * @package DashWoo
 */

namespace DashWoo\DesignSystem\Elementor;

use DashWoo\DesignSystem\Compiler;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Base_Data_Control' ) ) {
	return;
}

/**
 * Token picker control.
 */
class Token_Picker_Control extends \Elementor\Base_Data_Control {

	/**
	 * Control type id.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'dashwoo_tokens';
	}

	/**
	 * Default value.
	 *
	 * @return string
	 */
	public function get_default_value() {
		return '';
	}

	/**
	 * Control scripts/styles.
	 *
	 * @return array<int,string>
	 */
	public function enqueue() {
		wp_enqueue_script(
			'dashwoo-token-picker',
			DASHWOO_URL . 'assets/js/token-picker.js',
			array( 'elementor-editor' ),
			DASHWOO_VERSION,
			true
		);
	}

	/**
	 * Editor markup.
	 *
	 * @return void
	 */
	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<label class="elementor-control-title">{{{ data.label }}}</label>
			<div class="elementor-control-input-wrapper">
				<select id="<?php echo esc_attr( $control_uid ); ?>" class="dw-token-select" data-setting="{{ data.name }}">
					<option value=""><?php echo esc_html__( '— token —', 'dashwoo' ); ?></option>
					<# _.each( data.dashwooTokens || {}, function( group, key ) { #>
						<optgroup label="{{ group.label }}">
							<# _.each( group.tokens, function( token ) { #>
								<option value="{{ token.css }}">{{ token.short }} — {{ token.value }}</option>
							<# } ); #>
						</optgroup>
					<# } ); #>
				</select>
			</div>
		</div>
		<?php
	}

	/**
	 * Tokens for the editor.
	 *
	 * @return array<string,mixed>
	 */
	public function get_tokens() {
		return Tokens_Integration::instance()->token_payload();
	}
}
