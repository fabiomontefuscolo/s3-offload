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
		return get_option( 's3_offloader_access_key', null );
	}

	/**
	 * Set AWS access key.
	 *
	 * @param string $access_key The AWS access key to set.
	 */
	public static function set_access_key( string $access_key ): void {
		if ( empty( $access_key ) ) {
			delete_option( 's3_offloader_access_key' );
		} else {
			update_option( 's3_offloader_access_key', $access_key );
		}
	}

	/**
	 * Get AWS secret key.
	 *
	 * @return string|null
	 */
	public static function get_secret_key(): string|null {
		return get_option( 's3_offloader_secret_key' , null);
	}

	/**
	 * Set AWS secret key.
	 *
	 * @param string $secret_key The AWS secret key to set.
	 */
	public static function set_secret_key( string $secret_key ): void {
		if ( empty( $secret_key ) ) {
			delete_option( 's3_offloader_secret_key' );
		} else {
			update_option( 's3_offloader_secret_key', $secret_key );
		}
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
	 * Set AWS region.
	 *
	 * @param string $region The AWS region to set.
	 */
	public static function set_region( string $region ): void {
		if ( empty( $region ) ) {
			delete_option( 's3_offloader_region' );
		} else {
			update_option( 's3_offloader_region', $region );
		}
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
	 * Set S3 endpoint.
	 *
	 * @param string $endpoint The S3 endpoint to set.
	 */
	public static function set_endpoint( string $endpoint ): void {
		if ( empty( $endpoint ) ) {
			delete_option( 's3_offloader_endpoint' );
		} else {
			update_option( 's3_offloader_endpoint', $endpoint );
		}
	}

	/**
	 * Check if path-style endpoint should be used.
	 *
	 * @return bool
	 */
	public static function get_use_path_style(): bool {
		return in_array(
			get_option( 's3_offloader_use_path_style', false ),
			array( true, 'true', 1, '1' ),
			true
		);
	}

	/**
	 * Set whether to use path-style endpoint.
	 *
	 * @param bool $use_path_style Whether to use path-style endpoint.
	 */
	public static function set_use_path_style( bool $use_path_style ): void {
		update_option( 's3_offloader_use_path_style', $use_path_style === true );
	}

	/**
	 * Get S3 bucket name.
	 *
	 * @return string|null
	 */
	public static function get_bucket(): string|null {
		return get_option( 's3_offloader_bucket', null );
	}

	/**
	 * Set S3 bucket name.
	 *
	 * @param string $bucket The S3 bucket name to set.
	 */
	public static function set_bucket( string $bucket ): void {
		if ( empty( $bucket ) ) {
			delete_option( 's3_offloader_bucket' );
		} else {
			update_option( 's3_offloader_bucket', $bucket );
		}
	}

	/**
	 * Check if local files should be deleted after upload.
	 *
	 * @return bool
	 */
	public static function get_delete_local(): bool {
		return get_option( 's3_offloader_delete_local', false ) === true;
	}

	/**
	 * Set whether to delete local files after upload.
	 *
	 * @param bool $delete_local Whether to delete local files after upload.
	 */
	public static function set_delete_local( bool $delete_local ): void {
		update_option( 's3_offloader_delete_local', $delete_local === true );
	}

	/**
	 * Get base directory prefix for S3 keys.
	 *
	 * @return string
	 */
	public static function get_base_prefix(): string {
		return get_option( 's3_offloader_base_prefix', '' );
	}

	/**
	 * Set base directory prefix for S3 keys.
	 *
	 * @param string $base_prefix The base directory prefix to set.
	 */
	public static function set_base_prefix( string $base_prefix ): void {
		if ( empty( $base_prefix ) ) {
			delete_option( 's3_offloader_base_prefix' );
		} else {
			update_option( 's3_offloader_base_prefix', $base_prefix );
		}
	}
}
