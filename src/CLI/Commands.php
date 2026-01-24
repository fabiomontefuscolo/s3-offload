<?php
namespace S3Offloader\CLI;

use S3Offloader\Uploader;
use WP_CLI;


class Commands {
	/**
	 * Sync existing media files to S3
	 *
	 * ## OPTIONS
	 *
	 * [--batch=<batch>]
	 * : Number of files to process per batch
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp s3-offloader sync
	 *     wp s3-offloader sync --batch=50
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function sync( $args, $assoc_args ) {
		$batch = isset( $assoc_args['batch'] ) ? (int) $assoc_args['batch'] : 100;

		WP_CLI::log( 'Starting S3 sync...' );

		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_s3_offloader_url',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$total = count( $attachments );
		WP_CLI::log( sprintf( 'Found %d files to sync.', $total ) );

		if ( empty( $attachments ) ) {
			WP_CLI::success( 'No files to sync.' );
			return;
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Syncing files', $total );
		$success  = 0;
		$failed   = 0;

		foreach ( $attachments as $attachment_id ) {
			if ( Uploader::upload_to_s3( $attachment_id ) ) {
				++$success;
			} else {
				++$failed;
			}
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success(
			sprintf(
				'Sync complete. Success: %d, Failed: %d',
				$success,
				$failed
			)
		);
	}

	/**
	 * Test S3 connection
	 *
	 * ## EXAMPLES
	 *
	 *     wp s3-offloader test-connection
	 *
	 * @return void
	 */
	public function test_connection() {
		WP_CLI::log( 'Testing S3 connection...' );

		$test_file = wp_upload_dir()['path'] . '/s3-test-' . time() . '.txt';
		file_put_contents( $test_file, 'S3 Offloader test file' );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'text/plain',
				'post_title'     => 'S3 Test File',
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$test_file
		);

		if ( is_wp_error( $attachment_id ) ) {
			WP_CLI::error( 'Failed to create test attachment.' );
			return;
		}

		if ( Uploader::upload_to_s3( $attachment_id ) ) {
			WP_CLI::success( 'S3 connection successful!' );

			// Clean up.
			wp_delete_attachment( $attachment_id, true );
			if ( file_exists( $test_file ) ) {
				@unlink( $test_file );
			}
		} else {
			WP_CLI::error( 'S3 connection failed. Check your credentials and bucket settings.' );

			wp_delete_attachment( $attachment_id, true );
			if ( file_exists( $test_file ) ) {
				@unlink( $test_file );
			}
		}
	}
}
