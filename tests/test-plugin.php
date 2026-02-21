<?php
/**
 * Tests for the Plugin class.
 *
 * @package S3_Offloader
 */

/**
 * Test case for Plugin class.
 */
class PluginTest extends WP_UnitTestCase {
	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Reset the plugin instance to ensure a clean state for each test.
		// This is necessary because the plugin uses a singleton pattern.
		$reflection        = new ReflectionClass( S3Offloader\Plugin::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * Test that the plugin instance can be retrieved.
	 */
	public function test_get_instance() {
		$instance = S3Offloader\Plugin::get_instance();
		$this->assertInstanceOf( S3Offloader\Plugin::class, $instance );
	}

	/**
	 * Test that the plugin hooks are registered.
	 */
	public function test_hooks_registered() {
		$instance = S3Offloader\Plugin::get_instance();
		$this->assertTrue( has_action( 'admin_menu', array( $instance, 'add_admin_menu' ) ) > 0 );
		$this->assertTrue( has_filter( 'wp_generate_attachment_metadata', array( S3Offloader\Uploader::class, 'upload_after_metadata_generation' ) ) > 0 );
		$this->assertTrue( has_filter( 'wp_get_attachment_url', array( S3Offloader\Uploader::class, 'filter_attachment_url' ) ) > 0 );
		$this->assertTrue( has_filter( 'wp_get_attachment_image_src', array( S3Offloader\Uploader::class, 'filter_attachment_image_src' ) ) > 0 );
		$this->assertTrue( has_filter( 'wp_calculate_image_srcset', array( S3Offloader\Uploader::class, 'filter_image_srcset' ) ) > 0 );
		$this->assertTrue( has_filter( 'image_downsize', array( S3Offloader\Uploader::class, 'filter_image_downsize' ) ) > 0 );
		$this->assertTrue( has_filter( 'wp_prepare_attachment_for_js', array( S3Offloader\Uploader::class, 'filter_attachment_for_js' ) ) > 0 );
		$this->assertTrue( has_filter( 'the_content', array( S3Offloader\Uploader::class, 'filter_content_urls' ) ) > 0 );
	}

	/**
	 * Test that the CLI command is registered when WP_CLI is defined.
	 */
	public function test_cli_command_registered() {
		// Reset the mock to ensure clean state.
		WP_CLI::reset();

		// Force re-initialization to register commands.
		// Reset singleton first.
		$reflection        = new ReflectionClass( S3Offloader\Plugin::class );
		$instance_property = $reflection->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );

		// Get new instance which will register commands.
		S3Offloader\Plugin::get_instance();

		// Verify command is registered.
		$this->assertTrue( WP_CLI::has_command( 's3-offloader' ) );

		$command = WP_CLI::get_command( 's3-offloader' );
		$this->assertEquals( S3Offloader\CLI\Commands::class, $command );
	}

	/**
	 * Test admin menu is added.
	 */
	public function test_admin_menu_added() {
		global $submenu;

		// Create an admin user (required for manage_options capability)
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Set admin context
		set_current_screen( 'dashboard' );

		$instance = S3Offloader\Plugin::get_instance();
		$instance->add_admin_menu();

		// add_options_page() adds items under 'options-general.php'
		$this->assertArrayHasKey( 'options-general.php', $submenu );

		// Find our menu item in the submenu array
		$entry = array_find(
			$submenu['options-general.php'],
			function ( $item ) {
				return 's3-offloader' === $item[2];
			}
		);

		$this->assertNotNull( $entry );
		$this->assertEquals( 'manage_options', $entry[1] );
		$this->assertEquals( 'S3 Offloader', $entry[0] );
	}
}
