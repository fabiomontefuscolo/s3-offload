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

		$access_key     = get_option( 's3_offloader_access_key' );
		$secret_key     = get_option( 's3_offloader_secret_key' );
		$region         = get_option( 's3_offloader_region', 'us-east-1' );
		$endpoint       = get_option( 's3_offloader_endpoint', '' );
		$use_path_style = get_option( 's3_offloader_use_path_style', false );

		if ( empty( $access_key ) || empty( $secret_key ) ) {
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


	public static function upload_to_s3( $attachment_id ) {
		$s3_client = self::get_s3_client();
		if ( ! $s3_client ) {
			return false;
		}

		$bucket = get_option( 's3_offloader_bucket' );
		if ( empty( $bucket ) ) {
			return false;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! file_exists( $file ) ) {
			return false;
		}

		$key = self::get_s3_key( $attachment_id );

		try {
			$result = $s3_client->putObject(
				array(
					'Bucket'      => $bucket,
					'Key'         => $key,
					'SourceFile'  => $file,
					'ACL'         => 'public-read',
					'ContentType' => get_post_mime_type( $attachment_id ),
				)
			);

			// Store S3 URL in post meta.
			$s3_url = $result['ObjectURL'];
			update_post_meta( $attachment_id, '_s3_offloader_url', $s3_url );

			// Delete local file if option is enabled.
			if ( get_option( 's3_offloader_delete_local', false ) ) {
				@unlink( $file );
			}

			return true;
		} catch ( AwsException $e ) {
			error_log( 'S3 Offloader: Failed to upload file: ' . $e->getMessage() );
			return false;
		}
	}


	public static function filter_attachment_url( $url, $attachment_id ) {
		$s3_url = get_post_meta( $attachment_id, '_s3_offloader_url', true );
		if ( ! empty( $s3_url ) ) {
			return $s3_url;
		}
		return $url;
	}


	private static function get_s3_key( $attachment_id ) {
		$file     = get_attached_file( $attachment_id );
		$uploads  = wp_upload_dir();
		$base_dir = $uploads['basedir'];

		return str_replace( $base_dir . '/', '', $file );
	}
}
