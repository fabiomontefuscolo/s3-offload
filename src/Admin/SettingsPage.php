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
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission.
		if ( isset( $_POST['s3_offloader_submit'] ) ) {
			check_admin_referer( 's3_offloader_settings' );
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
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 's3_offloader_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="s3_offloader_access_key"><?php esc_html_e( 'AWS Access Key', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="s3_offloader_access_key" name="s3_offloader_access_key" value="<?php echo esc_attr( $access_key ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="s3_offloader_secret_key"><?php esc_html_e( 'AWS Secret Key', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="password" id="s3_offloader_secret_key" name="s3_offloader_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="s3_offloader_bucket"><?php esc_html_e( 'S3 Bucket', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="s3_offloader_bucket" name="s3_offloader_bucket" value="<?php echo esc_attr( $bucket ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="s3_offloader_region"><?php esc_html_e( 'AWS Region', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="s3_offloader_region" name="s3_offloader_region" value="<?php echo esc_attr( $region ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'e.g., us-east-1, eu-west-1', 's3-offloader' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="s3_offloader_endpoint"><?php esc_html_e( 'Custom Endpoint', 's3-offloader' ); ?></label>
						</th>
						<td>
							<input type="text" id="s3_offloader_endpoint" name="s3_offloader_endpoint" value="<?php echo esc_attr( $endpoint ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'For LocalStack use: http://localstack:4566 (leave empty for AWS S3)', 's3-offloader' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Use Path Style Endpoint', 's3-offloader' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="s3_offloader_use_path_style" value="1" <?php checked( $use_path_style, true ); ?> />
								<?php esc_html_e( 'Enable for LocalStack and some S3-compatible services', 's3-offloader' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Delete Local Files', 's3-offloader' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="s3_offloader_delete_local" value="1" <?php checked( $delete_local, true ); ?> />
								<?php esc_html_e( 'Delete local files after uploading to S3', 's3-offloader' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 's3-offloader' ), 'primary', 's3_offloader_submit' ); ?>
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
		update_option( 's3_offloader_access_key', sanitize_text_field( wp_unslash( $_POST['s3_offloader_access_key'] ?? '' ) ) );
		update_option( 's3_offloader_secret_key', sanitize_text_field( wp_unslash( $_POST['s3_offloader_secret_key'] ?? '' ) ) );
		update_option( 's3_offloader_bucket', sanitize_text_field( wp_unslash( $_POST['s3_offloader_bucket'] ?? '' ) ) );
		update_option( 's3_offloader_region', sanitize_text_field( wp_unslash( $_POST['s3_offloader_region'] ?? 'us-east-1' ) ) );
		update_option( 's3_offloader_endpoint', sanitize_text_field( wp_unslash( $_POST['s3_offloader_endpoint'] ?? '' ) ) );
		update_option( 's3_offloader_use_path_style', isset( $_POST['s3_offloader_use_path_style'] ) );
		update_option( 's3_offloader_delete_local', isset( $_POST['s3_offloader_delete_local'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
