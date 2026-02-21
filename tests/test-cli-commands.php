<?php
/**
 * Tests for the CLI Commands class.
 *
 * @package S3_Offloader
 */

use S3Offloader\CLI\Commands;

/**
 * Test case for CLI Commands class.
 */
class Test_CLI_Commands extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		S3Offloader\PluginConfig::set_access_key( 'test' );
		S3Offloader\PluginConfig::set_secret_key( 'test' );
		S3Offloader\PluginConfig::set_bucket( 'test-bucket' );
		S3Offloader\PluginConfig::set_region( 'us-east-1' );
		S3Offloader\PluginConfig::set_endpoint( 'http://localstack:4566' );
		S3Offloader\PluginConfig::set_use_path_style( true );
		S3Offloader\PluginConfig::set_delete_local( false );

		// Remove auto-upload hook.
		remove_filter( 'wp_generate_attachment_metadata', array( S3Offloader\Uploader::class, 'upload_after_metadata_generation' ), 10 );

		// Reset WP_CLI mock for clean state.
		if ( class_exists( 'WP_CLI' ) && method_exists( 'WP_CLI', 'reset' ) ) {
			WP_CLI::reset();
		}
	}

	public function tear_down(): void {
		parent::tear_down();
		self::set_private_class_property( S3Offloader\Uploader::class, 's3_client', null );

		// Reset WP_CLI mock after each test.
		if ( class_exists( 'WP_CLI' ) && method_exists( 'WP_CLI', 'reset' ) ) {
			WP_CLI::reset();
		}
	}

	/**
	 * Test sync command with no attachments to sync.
	 *
	 * @return void
	 */
	public function test_sync_with_no_attachments() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};

		// Run sync command.
		$commands->sync( array(), array() );

		// Verify log messages.
		$this->assertContains( 'Starting S3 sync...', $log_messages );
		$this->assertContains( 'Found 0 files to sync.', $log_messages );
		$this->assertContains( 'No files to sync.', $success_messages );
	}

	/**
	 * Test sync command with attachments already synced.
	 *
	 * @return void
	 */
	public function test_sync_with_already_synced_attachments() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		// Create attachment and mark as already synced.
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);
		update_post_meta( $attachment_id, '_s3_offloader_url', 'http://s3.example.com/test.jpg' );

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};

		// Run sync command.
		$commands->sync( array(), array() );

		// Should find no files since it's already synced.
		$this->assertContains( 'No files to sync.', $success_messages );
	}

	/**
	 * Test sync command with unsynced attachments.
	 *
	 * @return void
	 */
	public function test_sync_with_unsynced_attachments() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		// Create attachments without S3 URLs.
		$attachment_id_1 = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);
		$attachment_id_2 = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};

		// Run sync command.
		$commands->sync( array(), array() );

		// Verify messages.
		$this->assertContains( 'Starting S3 sync...', $log_messages );
		$this->assertStringContainsString( 'Found 2 files to sync', implode( ' ', $log_messages ) );
		$this->assertStringContainsString( 'Success: 2', implode( ' ', $success_messages ) );
		$this->assertStringContainsString( 'Failed: 0', implode( ' ', $success_messages ) );
	}

	/**
	 * Test sync command with custom batch size.
	 *
	 * @return void
	 */
	public function test_sync_with_custom_batch_size() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		// Create attachment without S3 URL.
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};

		// Run sync command with custom batch size.
		$commands->sync( array(), array( 'batch' => 50 ) );

		// Verify it processed the file.
		$this->assertStringContainsString( 'Success: 1', implode( ' ', $success_messages ) );
	}

	/**
	 * Test sync command handles upload failures.
	 *
	 * @return void
	 */
	public function test_sync_handles_upload_failures() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		// Create attachment without S3 URL.
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Set invalid credentials to cause upload failure.
		S3Offloader\PluginConfig::set_access_key( '' );
		S3Offloader\PluginConfig::set_secret_key( '' );

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};

		// Run sync command.
		$commands->sync( array(), array() );

		// Verify failure was tracked.
		$this->assertStringContainsString( 'Failed: 1', implode( ' ', $success_messages ) );
		$this->assertStringContainsString( 'Success: 0', implode( ' ', $success_messages ) );
	}

	/**
	 * Test test_connection command with successful connection.
	 *
	 * @return void
	 */
	public function test_connection_success() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();
		$error_messages   = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};
		WP_CLI::$__error   = function ( $message ) use ( &$error_messages ) {
			$error_messages[] = $message;
		};

		// Run test connection command.
		$commands->test_connection();

		// Verify success message.
		$this->assertContains( 'Testing S3 connection...', $log_messages );
		$this->assertContains( 'S3 connection successful!', $success_messages );
		$this->assertEmpty( $error_messages );
	}

	/**
	 * Test test_connection command with failed connection.
	 *
	 * @return void
	 */
	public function test_connection_failure() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		// Set invalid credentials to cause connection failure.
		S3Offloader\PluginConfig::set_access_key( '' );
		S3Offloader\PluginConfig::set_secret_key( '' );

		$commands = new Commands();

		// Mock WP_CLI methods.
		$log_messages     = array();
		$success_messages = array();
		$error_messages   = array();

		WP_CLI::$__log     = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};
		WP_CLI::$__success = function ( $message ) use ( &$success_messages ) {
			$success_messages[] = $message;
		};
		WP_CLI::$__error   = function ( $message ) use ( &$error_messages ) {
			$error_messages[] = $message;
		};

		// Run test connection command.
		$commands->test_connection();

		// Verify error message.
		$this->assertContains( 'Testing S3 connection...', $log_messages );
		$this->assertStringContainsString( 'S3 connection failed', implode( ' ', $error_messages ) );
		$this->assertEmpty( $success_messages );
	}

	/**
	 * Test test_connection cleans up test file on success.
	 *
	 * @return void
	 */
	public function test_connection_cleans_up_test_file() {
		if ( ! class_exists( 'WP_CLI' ) ) {
			$this->markTestSkipped( 'WP_CLI not available' );
		}

		$commands = new Commands();

		// Mock WP_CLI methods.
		WP_CLI::$__log     = function ( $message ) {};
		WP_CLI::$__success = function ( $message ) {};
		WP_CLI::$__error   = function ( $message ) {};

		// Get initial attachment count.
		$initial_count = count(
			get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
				)
			)
		);

		// Run test connection command.
		$commands->test_connection();

		// Verify test attachment was deleted.
		$final_count = count(
			get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => -1,
				)
			)
		);

		$this->assertEquals( $initial_count, $final_count, 'Test attachment should be cleaned up' );
	}

	/**
	 * Helper method to set private class property value using reflection.
	 *
	 * @param string $class         Class name.
	 * @param string $property_name The name of the private property to set.
	 * @param mixed  $value         The value to assign to the property.
	 */
	private static function set_private_class_property( $class, $property_name, $value ) {
		$reflection = new ReflectionClass( $class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( null, $value );
	}
}
