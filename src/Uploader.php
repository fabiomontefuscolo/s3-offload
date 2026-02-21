<?php
/**
 * S3 uploader class.
 *
 * @package S3Offloader
 */

namespace S3Offloader;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Handles uploading and filtering of media files to S3.
 */
class Uploader {

	/**
	 * Post meta key for S3 URL.
	 */
	public const META_S3_URL = '_s3_offloader_url';

	/**
	 * S3 client instance.
	 *
	 * @var S3Client|null
	 */
	private static $s3_client = null;


	/**
	 * Get S3 client configuration array.
	 *
	 * @return array|null S3 client configuration or null if credentials are missing.
	 */
	public static function get_s3_config(): array|null {
		$access_key     = PluginConfig::get_access_key();
		$secret_key     = PluginConfig::get_secret_key();
		$region         = PluginConfig::get_region();
		$endpoint       = PluginConfig::get_endpoint();
		$use_path_style = PluginConfig::get_use_path_style();

		if ( empty( $access_key ) || empty( $secret_key ) ) {
			return null;
		}

		$config = array(
			'version'     => 'latest',
			'region'      => $region,
			'credentials' => array(
				'key'    => $access_key,
				'secret' => $secret_key,
			),
		);

		if ( ! empty( $endpoint ) ) {
			$config['endpoint'] = $endpoint;
		}

		if ( $use_path_style ) {
			$config['use_path_style_endpoint'] = true;
		}

		return $config;
	}

	/**
	 * Get or initialize S3 client.
	 *
	 * @return S3Client|false S3 client instance or false on failure.
	 */
	public static function get_s3_client() {
		if ( null !== self::$s3_client ) {
			return self::$s3_client;
		}

		$config = self::get_s3_config();
		if ( ! $config ) {
			return false;
		}

		self::$s3_client = new S3Client( $config );
		return self::$s3_client;
	}

	/**
	 * Upload file to S3 after WordPress generates attachment metadata.
	 *
	 * @param array $metadata      Attachment metadata.
	 * @param int   $attachment_id Attachment ID.
	 * @return array Unmodified metadata.
	 */
	public static function upload_after_metadata_generation( $metadata, $attachment_id ) {
		self::upload_to_s3( $attachment_id );
		return $metadata;
	}

	/**
	 * Upload an attachment and its thumbnails to S3.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool True on success, false on failure.
	 */
	public static function upload_to_s3( $attachment_id ) {
		$s3_client = self::get_s3_client();
		if ( ! $s3_client ) {
			return false;
		}

		$bucket = PluginConfig::get_bucket();
		if ( empty( $bucket ) ) {
			return false;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return false;
		}

		$key          = self::get_s3_key( $attachment_id );
		$mime_type    = get_post_mime_type( $attachment_id );
		$delete_local = PluginConfig::get_delete_local();

		// Upload main file.
		$s3_url = self::upload_file_to_s3( $s3_client, $bucket, $file, $key, $mime_type );
		if ( ! $s3_url ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Intentional for debugging.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Error message context.
			trigger_error(
				'S3 Offloader: Failed to upload main file for attachment ID ' . esc_html( $attachment_id ),
				E_USER_WARNING
			);
			return false;
		}

		// Store S3 URL in post meta.
		update_post_meta( $attachment_id, self::META_S3_URL, $s3_url );

		// Upload related files.
		self::upload_thumbnails( $s3_client, $bucket, $attachment_id, $file, $key );

		// Delete local main file if option is enabled.
		if ( $delete_local ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $file );
		}

		return true;
	}

	/**
	 * Upload a single file to S3.
	 *
	 * @param S3Client $s3_client S3 client instance.
	 * @param string   $bucket    S3 bucket name.
	 * @param string   $file_path Local file path.
	 * @param string   $key       S3 object key.
	 * @param string   $mime_type File MIME type.
	 * @return string|false S3 URL on success, false on failure.
	 */
	private static function upload_file_to_s3( $s3_client, $bucket, $file_path, $key, $mime_type ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$result = $s3_client->putObject(
			array(
				'Bucket'      => $bucket,
				'Key'         => $key,
				'SourceFile'  => $file_path,
				'ACL'         => 'public-read',
				'ContentType' => $mime_type,
			)
		);

		return $result['ObjectURL'];
	}

	/**
	 * Upload all thumbnails for an attachment to S3.
	 *
	 * @param S3Client $s3_client    S3 client instance.
	 * @param string   $bucket       S3 bucket name.
	 * @param int      $attachment_id Attachment ID.
	 * @param string   $main_file    Path to main file.
	 * @param string   $main_key     S3 key for main file.
	 */
	private static function upload_thumbnails( $s3_client, $bucket, $attachment_id, $main_file, $main_key ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $metadata['sizes'] ) ) {
			return;
		}

		$upload_dir   = dirname( $main_file );
		$base_key     = dirname( $main_key );
		$delete_local = PluginConfig::get_delete_local();
		$default_mime = get_post_mime_type( $attachment_id );

		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			$thumbnail_file = $upload_dir . '/' . $size_data['file'];
			$thumbnail_key  = $base_key . '/' . $size_data['file'];
			$thumbnail_mime = $size_data['mime-type'] ?? $default_mime;

			$s3_url = self::upload_file_to_s3( $s3_client, $bucket, $thumbnail_file, $thumbnail_key, $thumbnail_mime );

			if ( $s3_url && $delete_local ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $thumbnail_file );
			}
		}
	}

	/**
	 * Filter attachment URL to return S3 URL.
	 *
	 * @param string $url           Original attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string Modified or original URL.
	 */
	public static function filter_attachment_url( $url, $attachment_id ) {
		$s3_url = get_post_meta( $attachment_id, self::META_S3_URL, true );
		if ( ! empty( $s3_url ) ) {
			return $s3_url;
		}
		return $url;
	}

	/**
	 * Filter attachment image src to use S3 URLs.
	 *
	 * @param array|false  $image         Image data array or false.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size          Image size (unused but required by WordPress hook).
	 * @param bool         $icon          Whether to use icon (unused but required by WordPress hook).
	 * @return array|false Modified image data or original.
	 */
	public static function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		// Unused parameters required by WordPress filter hook.
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		unset( $size, $icon );

		if ( ! $image ) {
			return $image;
		}

		// Convert the URL to S3.
		$image[0] = self::convert_url_to_s3( $image[0], $attachment_id );

		return $image;
	}

	/**
	 * Filter image srcset to use S3 URLs.
	 *
	 * @param array  $sources      Srcset sources.
	 * @param array  $size_array   Image size array.
	 * @param string $image_src    Image source URL.
	 * @param array  $image_meta   Image metadata.
	 * @param int    $attachment_id Attachment ID.
	 * @return array Modified sources.
	 */
	public static function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! $sources ) {
			return $sources;
		}

		foreach ( $sources as $width => $source ) {
			$sources[ $width ]['url'] = self::convert_url_to_s3( $source['url'], $attachment_id );
		}

		return $sources;
	}

	/**
	 * Convert a local URL to S3 URL.
	 *
	 * @param string $url           Local URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string S3 URL or original URL.
	 */
	private static function convert_url_to_s3( $url, $attachment_id ) {
		// Check if this attachment is offloaded to S3.
		$s3_url = get_post_meta( $attachment_id, self::META_S3_URL, true );
		if ( empty( $s3_url ) ) {
			return $url;
		}

		// Get the S3 base URL.
		$s3_base_url = self::get_s3_base_url(
			PluginConfig::get_bucket(),
			PluginConfig::get_endpoint(),
			PluginConfig::get_region(),
			PluginConfig::get_use_path_style()
		);

		if ( empty( $s3_base_url ) ) {
			return $url;
		}

		// Check if URL is already an S3 URL (to avoid double conversion).
		if ( strpos( $url, $s3_base_url ) === 0 ) {
			return $url;
		}

		// Get the upload directory info.
		$uploads  = wp_upload_dir();
		$base_url = $uploads['baseurl'];

		// Replace the local URL with S3 URL.
		return str_replace( $base_url, $s3_base_url, $url );
	}

	/**
	 * Get S3 base URL based on configuration.
	 *
	 * @param string $bucket         S3 bucket name.
	 * @param string $endpoint       Custom endpoint.
	 * @param string $region         AWS region.
	 * @param bool   $use_path_style Whether to use path-style URLs.
	 * @return string S3 base URL.
	 */
	public static function get_s3_base_url(
		$bucket,
		$endpoint,
		$region,
		$use_path_style
	) {

		if ( empty( $bucket ) ) {
			return '';
		}

		$base_url = '';

		if ( ! empty( $endpoint ) ) {
			$endpoint = preg_replace( '#^https?://#', '', $endpoint );
			$endpoint = rtrim( $endpoint, '/' );

			if ( $use_path_style ) {
				$base_url = 'http://' . $endpoint . '/' . $bucket;
			} else {
				$base_url = 'http://' . $bucket . '.' . $endpoint;
			}
		} else {
			$base_url = 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com';
		}

		// Append custom base prefix if configured.
		$custom_prefix = PluginConfig::get_base_prefix();
		if ( ! empty( $custom_prefix ) ) {
			$custom_prefix = trim( $custom_prefix, '/' );
			$base_url     .= '/' . $custom_prefix;
		}

		return $base_url;
	}

	/**
	 * Get S3 key for an attachment.
	 *
	 * Strips /wp-content/uploads/ from the file path to get the S3 key.
	 * Optionally prefixes with a custom base directory.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string S3 key.
	 */
	public static function get_s3_key( $attachment_id ) {
		$file     = get_attached_file( $attachment_id );
		$uploads  = wp_upload_dir();
		$base_dir = $uploads['basedir'];

		$key = str_replace( $base_dir . '/', '', $file );

		$custom_prefix = PluginConfig::get_base_prefix();
		if ( ! empty( $custom_prefix ) ) {
			$custom_prefix = trim( $custom_prefix, '/' );
			$key           = $custom_prefix . '/' . $key;
		}

		return $key;
	}

	/**
	 * Filter image downsize to use S3 URLs.
	 *
	 * @param bool|array   $downsize      Current downsize value.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size          Image size.
	 * @return bool|array Modified downsize value.
	 */
	public static function filter_image_downsize( $downsize, $attachment_id, $size ) {
		// Check if this attachment is offloaded to S3.
		$s3_url = get_post_meta( $attachment_id, self::META_S3_URL, true );
		if ( empty( $s3_url ) ) {
			return $downsize;
		}

		// Get image metadata.
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! $meta ) {
			return $downsize;
		}

		// Handle array size (width, height) by returning full size.
		// WordPress core can't resize if the file is only on S3.
		if ( is_array( $size ) ) {
			$width  = $meta['width'] ?? 0;
			$height = $meta['height'] ?? 0;
			$url    = self::convert_url_to_s3( wp_get_attachment_url( $attachment_id ), $attachment_id );
			return array( $url, $width, $height, false );
		}

		// Get the appropriate size data.
		if ( 'full' === $size || ! isset( $meta['sizes'][ $size ] ) ) {
			$width  = $meta['width'] ?? 0;
			$height = $meta['height'] ?? 0;
			$url    = self::convert_url_to_s3( wp_get_attachment_url( $attachment_id ), $attachment_id );
			return array( $url, $width, $height, false );
		}

		$size_data = $meta['sizes'][ $size ];
		$url       = wp_get_attachment_url( $attachment_id );
		$url       = str_replace( basename( $url ), $size_data['file'], $url );
		$url       = self::convert_url_to_s3( $url, $attachment_id );

		return array( $url, $size_data['width'], $size_data['height'], true );
	}

	/**
	 * Filter attachment data for JS (media library).
	 *
	 * @param array    $response   Response data.
	 * @param \WP_Post $attachment Attachment object.
	 * @param array    $meta       Attachment metadata (unused but required by WordPress hook).
	 * @return array Modified response.
	 */
	public static function filter_attachment_for_js( $response, $attachment, $meta ) {
		// Unused parameter required by WordPress filter hook.
		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		unset( $meta );

		$attachment_id = $attachment->ID;

		// Check if this attachment is offloaded to S3.
		$s3_url = get_post_meta( $attachment_id, self::META_S3_URL, true );
		if ( empty( $s3_url ) ) {
			return $response;
		}

		// Update the main URL.
		if ( isset( $response['url'] ) ) {
			$response['url'] = self::convert_url_to_s3( $response['url'], $attachment_id );
		}

		// Update all size URLs.
		if ( isset( $response['sizes'] ) && is_array( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size_name => $size_data ) {
				if ( isset( $size_data['url'] ) ) {
					$response['sizes'][ $size_name ]['url'] = self::convert_url_to_s3( $size_data['url'], $attachment_id );
				}
			}
		}

		return $response;
	}

	/**
	 * Filter content URLs to use S3 URLs.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public static function filter_content_urls( $content ) {
		// Get upload directory info.
		$uploads  = wp_upload_dir();
		$base_url = $uploads['baseurl'];

		// Build a regex pattern to match upload URLs.
		$pattern = '#' . preg_quote( $base_url, '#' ) . '/[^\s\'"]+#';

		// Find all upload URLs in content.
		preg_match_all( $pattern, $content, $matches );

		if ( empty( $matches[0] ) ) {
			return $content;
		}

		// Try to convert each URL to S3.
		foreach ( $matches[0] as $url ) {
			// Try to get attachment ID from URL.
			$attachment_id = attachment_url_to_postid( $url );

			if ( $attachment_id ) {
				$s3_url = get_post_meta( $attachment_id, self::META_S3_URL, true );
				if ( ! empty( $s3_url ) ) {
					$s3_converted_url = self::convert_url_to_s3( $url, $attachment_id );
					$content          = str_replace( $url, $s3_converted_url, $content );
				}
			}
		}

		return $content;
	}
}
