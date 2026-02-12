<?php
/**
 * Plugin configuration class.
 *
 * @package S3Offloader
 */

namespace S3Offloader;

/**
 * Configuration handler for S3 Offloader plugin.
 */
class PluginConfig {

	/**
	 * Get AWS access key.
	 *
	 * @return string|null
	 */
	public static function get_access_key(): string|null {
		return get_option( 's3_offloader_access_key' );
	}

	/**
	 * Get AWS secret key.
	 *
	 * @return string|null
	 */
	public static function get_secret_key(): string|null {
		return get_option( 's3_offloader_secret_key' );
	}

	/**
	 * Get AWS region.
	 *
	 * @return string
	 */
	public static function get_region(): string {
		return get_option( 's3_offloader_region', 'us-east-1' );
	}

	/**
	 * Get S3 endpoint.
	 *
	 * @return string
	 */
	public static function get_endpoint(): string {
		return get_option( 's3_offloader_endpoint', '' );
	}

	/**
	 * Check if path-style endpoint should be used.
	 *
	 * @return bool
	 */
	public static function get_use_path_style(): bool {
		return get_option( 's3_offloader_use_path_style', false ) === true;
	}

	/**
	 * Get S3 bucket name.
	 *
	 * @return string|null
	 */
	public static function get_bucket(): string|null {
		return get_option( 's3_offloader_bucket' );
	}

	/**
	 * Check if local files should be deleted after upload.
	 *
	 * @return bool
	 */
	public static function get_delete_local(): bool {
		return get_option( 's3_offloader_delete_local', false ) === true;
	}
}
