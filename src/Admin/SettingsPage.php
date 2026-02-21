<?php

/**
 * Settings page for S3 Offloader.
 *
 * @package S3Offloader
 */

namespace S3Offloader\Admin;

use S3Offloader\PluginConfig;

/**
 * Admin settings page handler.
 */
class SettingsPage {

	/**
	 * Form field name constants.
	 */
	public const FIELD_ACCESS_KEY     = 's3_offloader_access_key';
	public const FIELD_SECRET_KEY     = 's3_offloader_secret_key';
	public const FIELD_BUCKET         = 's3_offloader_bucket';
	public const FIELD_REGION         = 's3_offloader_region';
	public const FIELD_ENDPOINT       = 's3_offloader_endpoint';
	public const FIELD_USE_PATH_STYLE = 's3_offloader_use_path_style';
	public const FIELD_BASE_PREFIX    = 's3_offloader_base_prefix';
	public const FIELD_DELETE_LOCAL   = 's3_offloader_delete_local';
	public const FIELD_SUBMIT         = 's3_offloader_submit';

	/**
	 * Nonce action name.
	 */
	public const NONCE_ACTION = 's3_offloader_settings';

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission.
		if ( isset( $_POST[ self::FIELD_SUBMIT ] ) ) {
			check_admin_referer( self::NONCE_ACTION );
			self::save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 's3-offloader' ) . '</p></div>';
		}

		$access_key     = PluginConfig::get_access_key() ?? '';
		$secret_key     = PluginConfig::get_secret_key() ?? '';
		$bucket         = PluginConfig::get_bucket() ?? '';
		$region         = PluginConfig::get_region();
		$endpoint       = PluginConfig::get_endpoint();
		$use_path_style = PluginConfig::get_use_path_style();
		$delete_local   = PluginConfig::get_delete_local();
		$base_prefix    = PluginConfig::get_base_prefix();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_ACCESS_KEY ); ?>"><?php esc_html_e( 'AWS Access Key', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="<?php echo esc_attr( self::FIELD_ACCESS_KEY ); ?>" name="<?php echo esc_attr( self::FIELD_ACCESS_KEY ); ?>" value="<?php echo esc_attr( $access_key ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_SECRET_KEY ); ?>"><?php esc_html_e( 'AWS Secret Key', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="password" id="<?php echo esc_attr( self::FIELD_SECRET_KEY ); ?>" name="<?php echo esc_attr( self::FIELD_SECRET_KEY ); ?>" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_BUCKET ); ?>"><?php esc_html_e( 'S3 Bucket', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="<?php echo esc_attr( self::FIELD_BUCKET ); ?>" name="<?php echo esc_attr( self::FIELD_BUCKET ); ?>" value="<?php echo esc_attr( $bucket ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_REGION ); ?>"><?php esc_html_e( 'AWS Region', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="<?php echo esc_attr( self::FIELD_REGION ); ?>" name="<?php echo esc_attr( self::FIELD_REGION ); ?>" value="<?php echo esc_attr( $region ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'e.g., us-east-1, eu-west-1', 's3-offloader' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_ENDPOINT ); ?>"><?php esc_html_e( 'Custom Endpoint', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="<?php echo esc_attr( self::FIELD_ENDPOINT ); ?>" name="<?php echo esc_attr( self::FIELD_ENDPOINT ); ?>" value="<?php echo esc_attr( $endpoint ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'For LocalStack use: http://localstack:4566 (leave empty for AWS S3)', 's3-offloader' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Use Path Style Endpoint', 's3-offloader' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::FIELD_USE_PATH_STYLE ); ?>" value="1" <?php checked( $use_path_style, true ); ?> />
								<?php esc_html_e( 'Enable for LocalStack and some S3-compatible services', 's3-offloader' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::FIELD_BASE_PREFIX ); ?>"><?php esc_html_e( 'Base Directory Prefix', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="<?php echo esc_attr( self::FIELD_BASE_PREFIX ); ?>" name="<?php echo esc_attr( self::FIELD_BASE_PREFIX ); ?>" value="<?php echo esc_attr( $base_prefix ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Optional prefix for S3 keys (e.g., "production", "site-1/uploads"). Leave empty for no prefix.', 's3-offloader' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Delete Local Files', 's3-offloader' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::FIELD_DELETE_LOCAL ); ?>" value="1" <?php checked( $delete_local, true ); ?> />
								<?php esc_html_e( 'Delete local files after uploading to S3', 's3-offloader' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 's3-offloader' ), 'primary', self::FIELD_SUBMIT ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save settings from form submission.
	 */
	private static function save_settings() {
		// Nonce is already verified in render_page() before calling this method.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$s3_offloader_use_path_style = isset( $_POST[ self::FIELD_USE_PATH_STYLE ] ) && $_POST[ self::FIELD_USE_PATH_STYLE ] === '1';
		$s3_offloader_delete_local   = isset( $_POST[ self::FIELD_DELETE_LOCAL ] ) && $_POST[ self::FIELD_DELETE_LOCAL ] === '1';

		PluginConfig::set_access_key(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_ACCESS_KEY ] ?? '' ) )
		);
		PluginConfig::set_secret_key(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_SECRET_KEY ] ?? '' ) )
		);
		PluginConfig::set_region(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_REGION ] ?? 'us-east-1' ) )
		);
		PluginConfig::set_endpoint(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_ENDPOINT ] ?? '' ) )
		);
		PluginConfig::set_bucket(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_BUCKET ] ?? '' ) )
		);
		PluginConfig::set_base_prefix(
			sanitize_text_field( wp_unslash( $_POST[ self::FIELD_BASE_PREFIX ] ?? '' ) )
		);
		PluginConfig::set_use_path_style( $s3_offloader_use_path_style );
		PluginConfig::set_delete_local( $s3_offloader_delete_local );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
