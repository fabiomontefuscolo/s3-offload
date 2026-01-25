<?php

namespace S3Offloader;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


class Uploader {



	private static $s3_client = null;


	private static function get_s3_client() {
		if ( null !== self::$s3_client ) {
			return self::$s3_client;
		}

		$access_key     = PluginConfig::getAccessKey();
		$secret_key     = PluginConfig::getSecretKey();
		$region         = PluginConfig::getRegion();
		$endpoint       = PluginConfig::getEndpoint();
		$use_path_style = PluginConfig::getUsePathStyle();

		if ( empty( $access_key ) || empty( $secret_key ) ) {
			trigger_error(
				'S3 Offloader: AWS credentials are not set.',
				E_USER_WARNING
			);
			return false;
		}

		try {
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

			self::$s3_client = new S3Client( $config );

			return self::$s3_client;
		} catch ( AwsException $e ) {
			error_log( 'S3 Offloader: Failed to initialize S3 client: ' . $e->getMessage() );
			return false;
		}
	}


	public static function upload_after_metadata_generation( $metadata, $attachment_id ) {
		self::upload_to_s3( $attachment_id );
		return $metadata;
	}


	public static function upload_to_s3( $attachment_id ) {
		$s3_client = self::get_s3_client();
		if ( ! $s3_client ) {
			return false;
		}

		$bucket = PluginConfig::getBucket();
		if ( empty( $bucket ) ) {
			return false;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! file_exists( $file ) ) {
			trigger_error(
				'S3 Offloader: File does not exist for attachment ID ' . $attachment_id,
				E_USER_WARNING
			);
			return false;
		}

		$key          = self::get_s3_key( $attachment_id );
		$mime_type    = get_post_mime_type( $attachment_id );
		$delete_local = PluginConfig::getDeleteLocal();

		// Upload main file.
		$s3_url = self::upload_file_to_s3( $s3_client, $bucket, $file, $key, $mime_type );
		if ( ! $s3_url ) {
			trigger_error(
				'S3 Offloader: Failed to upload main file for attachment ID ' . $attachment_id,
				E_USER_WARNING
			);
			return false;
		}

		// Store S3 URL in post meta.
		update_post_meta( $attachment_id, '_s3_offloader_url', $s3_url );

		// Upload related files.
		self::upload_thumbnails( $s3_client, $bucket, $attachment_id, $file, $key );

		// Delete local main file if option is enabled.
		if ( $delete_local ) {
			@unlink( $file );
		}

		return true;
	}


	private static function upload_file_to_s3( $s3_client, $bucket, $file_path, $key, $mime_type ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		try {
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
		} catch ( AwsException $e ) {
			error_log( 'S3 Offloader: Failed to upload file ' . $file_path . ': ' . $e->getMessage() );
			return false;
		}
	}


	private static function upload_thumbnails( $s3_client, $bucket, $attachment_id, $main_file, $main_key ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $metadata['sizes'] ) ) {
			return;
		}

		$upload_dir   = dirname( $main_file );
		$base_key     = dirname( $main_key );
		$delete_local = PluginConfig::getDeleteLocal();
		$default_mime = get_post_mime_type( $attachment_id );

		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			$thumbnail_file = $upload_dir . '/' . $size_data['file'];
			$thumbnail_key  = $base_key . '/' . $size_data['file'];
			$thumbnail_mime = $size_data['mime-type'] ?? $default_mime;

			$s3_url = self::upload_file_to_s3( $s3_client, $bucket, $thumbnail_file, $thumbnail_key, $thumbnail_mime );

			if ( $s3_url && $delete_local ) {
				@unlink( $thumbnail_file );
			}
		}
	}


	public static function filter_attachment_url( $url, $attachment_id ) {
		$s3_url = get_post_meta( $attachment_id, '_s3_offloader_url', true );
		if ( ! empty( $s3_url ) ) {
			return $s3_url;
		}
		return $url;
	}


	public static function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		if ( ! $image ) {
			return $image;
		}

		// Convert the URL to S3.
		$image[0] = self::convert_url_to_s3( $image[0], $attachment_id );

		return $image;
	}


	public static function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! $sources ) {
			return $sources;
		}

		foreach ( $sources as $width => $source ) {
			$sources[ $width ]['url'] = self::convert_url_to_s3( $source['url'], $attachment_id );
		}

		return $sources;
	}


	private static function convert_url_to_s3( $url, $attachment_id ) {
		// Check if this attachment is offloaded to S3.
		$s3_url = get_post_meta( $attachment_id, '_s3_offloader_url', true );
		if ( empty( $s3_url ) ) {
			return $url;
		}

		// Get the upload directory info.
		$uploads     = wp_upload_dir();
		$base_url    = $uploads['baseurl'];
		$s3_base_url = self::get_s3_base_url(
			PluginConfig::getBucket(),
			PluginConfig::getEndpoint(),
			PluginConfig::getRegion(),
			PluginConfig::getUsePathStyle()
		);

		if ( empty( $s3_base_url ) ) {
			return $url;
		}

		// Replace the local URL with S3 URL.
		return str_replace( $base_url, $s3_base_url, $url );
	}


	private static function get_s3_base_url(
		$bucket,
		$endpoint,
		$region,
		$use_path_style
	) {

		if ( empty( $bucket ) ) {
			return '';
		}

		// If custom endpoint is set, use it.
		if ( ! empty( $endpoint ) ) {
			// Remove protocol and trailing slash.
			$endpoint = preg_replace( '#^https?://#', '', $endpoint );
			$endpoint = rtrim( $endpoint, '/' );

			// For path-style endpoints (like LocalStack).
			if ( $use_path_style ) {
				return 'http://' . $endpoint . '/' . $bucket;
			}

			// For virtual-hosted-style.
			return 'http://' . $bucket . '.' . $endpoint;
		}

		// Default AWS S3 URL structure.
		return 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com';
	}


	// In short, strips /wp-content/uploads/ from the file path to get the S3 key.
	private static function get_s3_key( $attachment_id ) {
		$file     = get_attached_file( $attachment_id );
		$uploads  = wp_upload_dir();
		$base_dir = $uploads['basedir'];

		return str_replace( $base_dir . '/', '', $file );
	}
}
