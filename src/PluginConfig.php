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
	 * WordPress option key constants.
	 */
	public const OPTION_ACCESS_KEY     = 's3_offloader_access_key';
	public const OPTION_SECRET_KEY     = 's3_offloader_secret_key';
	public const OPTION_BUCKET         = 's3_offloader_bucket';
	public const OPTION_REGION         = 's3_offloader_region';
	public const OPTION_ENDPOINT       = 's3_offloader_endpoint';
	public const OPTION_USE_PATH_STYLE = 's3_offloader_use_path_style';
	public const OPTION_DELETE_LOCAL   = 's3_offloader_delete_local';
	public const OPTION_BASE_PREFIX    = 's3_offloader_base_prefix';
	public const OPTION_CDN_URL        = 's3_offloader_cdn_url';

	/**
	 * Get AWS access key.
	 *
	 * @return string|null
	 */
	public static function get_access_key(): string|null {
		$value = get_option( self::OPTION_ACCESS_KEY );
		return ( false === $value || '' === $value ) ? null : $value;
	}

	/**
	 * Set AWS access key.
	 *
	 * @param string $access_key The AWS access key to set.
	 */
	public static function set_access_key( string $access_key ): void {
		if ( empty( $access_key ) ) {
			delete_option( self::OPTION_ACCESS_KEY );
		} else {
			update_option( self::OPTION_ACCESS_KEY, $access_key );
		}
	}

	/**
	 * Get AWS secret key.
	 *
	 * @return string|null
	 */
	public static function get_secret_key(): string|null {
		$value = get_option( self::OPTION_SECRET_KEY );
		return ( false === $value || '' === $value ) ? null : $value;
	}

	/**
	 * Set AWS secret key.
	 *
	 * @param string $secret_key The AWS secret key to set.
	 */
	public static function set_secret_key( string $secret_key ): void {
		if ( empty( $secret_key ) ) {
			delete_option( self::OPTION_SECRET_KEY );
		} else {
			update_option( self::OPTION_SECRET_KEY, $secret_key );
		}
	}

	/**
	 * Get AWS region.
	 *
	 * @return string
	 */
	public static function get_region(): string {
		return get_option( self::OPTION_REGION, 'us-east-1' );
	}

	/**
	 * Set AWS region.
	 *
	 * @param string $region The AWS region to set.
	 */
	public static function set_region( string $region ): void {
		if ( empty( $region ) ) {
			delete_option( self::OPTION_REGION );
		} else {
			update_option( self::OPTION_REGION, $region );
		}
	}

	/**
	 * Get S3 endpoint.
	 *
	 * @return string
	 */
	public static function get_endpoint(): string {
		return get_option( self::OPTION_ENDPOINT, '' );
	}

	/**
	 * Set S3 endpoint.
	 *
	 * @param string $endpoint The S3 endpoint to set.
	 */
	public static function set_endpoint( string $endpoint ): void {
		if ( empty( $endpoint ) ) {
			delete_option( self::OPTION_ENDPOINT );
		} else {
			update_option( self::OPTION_ENDPOINT, $endpoint );
		}
	}

	/**
	 * Check if path-style endpoint should be used.
	 *
	 * @return bool
	 */
	public static function get_use_path_style(): bool {
		return in_array(
			get_option( self::OPTION_USE_PATH_STYLE, false ),
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
		update_option( self::OPTION_USE_PATH_STYLE, true === $use_path_style );
	}

	/**
	 * Get S3 bucket name.
	 *
	 * @return string|null
	 */
	public static function get_bucket(): string|null {
		$value = get_option( self::OPTION_BUCKET );
		return ( false === $value || '' === $value ) ? null : $value;
	}

	/**
	 * Set S3 bucket name.
	 *
	 * @param string $bucket The S3 bucket name to set.
	 */
	public static function set_bucket( string $bucket ): void {
		if ( empty( $bucket ) ) {
			delete_option( self::OPTION_BUCKET );
		} else {
			update_option( self::OPTION_BUCKET, $bucket );
		}
	}

	/**
	 * Check if local files should be deleted after upload.
	 *
	 * @return bool
	 */
	public static function get_delete_local(): bool {
		return get_option( self::OPTION_DELETE_LOCAL, false ) === true;
	}

	/**
	 * Set whether to delete local files after upload.
	 *
	 * @param bool $delete_local Whether to delete local files after upload.
	 */
	public static function set_delete_local( bool $delete_local ): void {
		update_option( self::OPTION_DELETE_LOCAL, true === $delete_local );
	}

	/**
	 * Get base directory prefix for S3 keys.
	 *
	 * @return string
	 */
	public static function get_base_prefix(): string {
		return get_option( self::OPTION_BASE_PREFIX, '' );
	}

	/**
	 * Set base directory prefix for S3 keys.
	 *
	 * @param string $base_prefix The base directory prefix to set.
	 */
	public static function set_base_prefix( string $base_prefix ): void {
		if ( empty( $base_prefix ) ) {
			delete_option( self::OPTION_BASE_PREFIX );
		} else {
			update_option( self::OPTION_BASE_PREFIX, $base_prefix );
		}
	}

	/**
	 * Get CDN URL.
	 *
	 * @return string
	 */
	public static function get_cdn_url(): string {
		return get_option( self::OPTION_CDN_URL, '' );
	}

	/**
	 * Set CDN URL.
	 *
	 * @param string $cdn_url The CDN URL to set.
	 */
	public static function set_cdn_url( string $cdn_url ): void {
		if ( empty( $cdn_url ) ) {
			delete_option( self::OPTION_CDN_URL );
		} else {
			// Remove trailing slash.
			$cdn_url = rtrim( $cdn_url, '/' );
			update_option( self::OPTION_CDN_URL, $cdn_url );
		}
	}
}
