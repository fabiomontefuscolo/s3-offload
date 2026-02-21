<?php
/**
 * Tests for the PluginConfig class.
 *
 * @package S3_Offloader
 */

use S3Offloader\PluginConfig;

/**
 * Test case for PluginConfig class.
 */
class PluginConfigTest extends WP_UnitTestCase {

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up all plugin options
		delete_option( PluginConfig::OPTION_ACCESS_KEY );
		delete_option( PluginConfig::OPTION_SECRET_KEY );
		delete_option( PluginConfig::OPTION_BUCKET );
		delete_option( PluginConfig::OPTION_REGION );
		delete_option( PluginConfig::OPTION_ENDPOINT );
		delete_option( PluginConfig::OPTION_USE_PATH_STYLE );
		delete_option( PluginConfig::OPTION_DELETE_LOCAL );
		delete_option( PluginConfig::OPTION_BASE_PREFIX );
	}

	/**
	 * Test access key getter and setter.
	 */
	public function test_access_key() {
		$this->assertNull( PluginConfig::get_access_key() );

		// Test setting the value
		$access_key = bin2hex( random_bytes( 16 ) );
		PluginConfig::set_access_key( $access_key );
		$this->assertEquals( $access_key, PluginConfig::get_access_key() );

		// Test updating the value
		$access_key = bin2hex( random_bytes( 8 ) );
		PluginConfig::set_access_key( $access_key );
		$this->assertEquals( $access_key, PluginConfig::get_access_key() );

		// Test setting empty string deletes the option
		PluginConfig::set_access_key( '' );
		$this->assertNull( PluginConfig::get_access_key() );
	}

	/**
	 * Test secret key getter and setter.
	 */
	public function test_secret_key() {
		$this->assertNull( PluginConfig::get_secret_key() );

		$secret_key = bin2hex( random_bytes( 16 ) );
		PluginConfig::set_secret_key( $secret_key );
		$this->assertEquals( $secret_key, PluginConfig::get_secret_key() );

		PluginConfig::set_secret_key( '' );
		$this->assertNull( PluginConfig::get_secret_key() );
	}

	/**
	 * Test bucket getter and setter.
	 */
	public function test_bucket() {
		$this->assertNull( PluginConfig::get_bucket() );

		PluginConfig::set_bucket( 'my-test-bucket' );
		$this->assertEquals( 'my-test-bucket', PluginConfig::get_bucket() );

		PluginConfig::set_bucket( '' );
		$this->assertNull( PluginConfig::get_bucket() );
	}

	/**
	 * Test region getter and setter.
	 */
	public function test_region() {
		$this->assertEquals( 'us-east-1', PluginConfig::get_region() );

		PluginConfig::set_region( 'eu-west-1' );
		$this->assertEquals( 'eu-west-1', PluginConfig::get_region() );

		PluginConfig::set_region( '' );
		$this->assertEquals( 'us-east-1', PluginConfig::get_region() );
	}

	/**
	 * Test endpoint getter and setter.
	 */
	public function test_endpoint() {
		$this->assertEquals( '', PluginConfig::get_endpoint() );

		PluginConfig::set_endpoint( 'http://localstack:4566' );
		$this->assertEquals( 'http://localstack:4566', PluginConfig::get_endpoint() );

		PluginConfig::set_endpoint( '' );
		$this->assertEquals( '', PluginConfig::get_endpoint() );
	}

	/**
	 * Test use_path_style getter and setter.
	 */
	public function test_use_path_style() {
		$this->assertFalse( PluginConfig::get_use_path_style() );

		PluginConfig::set_use_path_style( true );
		$this->assertTrue( PluginConfig::get_use_path_style() );

		PluginConfig::set_use_path_style( false );
		$this->assertFalse( PluginConfig::get_use_path_style() );

		update_option( PluginConfig::OPTION_USE_PATH_STYLE, 'true' );
		$this->assertTrue( PluginConfig::get_use_path_style() );

		update_option( PluginConfig::OPTION_USE_PATH_STYLE, 1 );
		$this->assertTrue( PluginConfig::get_use_path_style() );

		update_option( PluginConfig::OPTION_USE_PATH_STYLE, '1' );
		$this->assertTrue( PluginConfig::get_use_path_style() );
	}

	/**
	 * Test delete_local getter and setter.
	 */
	public function test_delete_local() {
		// Test default value (false)
		$this->assertFalse( PluginConfig::get_delete_local() );

		PluginConfig::set_delete_local( true );
		$this->assertTrue( PluginConfig::get_delete_local() );

		PluginConfig::set_delete_local( false );
		$this->assertFalse( PluginConfig::get_delete_local() );
	}

	/**
	 * Test base_prefix getter and setter.
	 */
	public function test_base_prefix() {
		$this->assertEquals( '', PluginConfig::get_base_prefix() );

		PluginConfig::set_base_prefix( 'production/uploads' );
		$this->assertEquals( 'production/uploads', PluginConfig::get_base_prefix() );

		PluginConfig::set_base_prefix( 'site-1' );
		$this->assertEquals( 'site-1', PluginConfig::get_base_prefix() );

		PluginConfig::set_base_prefix( '' );
		$this->assertEquals( '', PluginConfig::get_base_prefix() );
	}

	/**
	 * Test that all config values can be set and retrieved together.
	 */
	public function test_all_config_values_together() {
		PluginConfig::set_access_key( 'test-access-key' );
		PluginConfig::set_secret_key( 'test-secret-key' );
		PluginConfig::set_bucket( 'test-bucket' );
		PluginConfig::set_region( 'us-west-2' );
		PluginConfig::set_endpoint( 'http://localhost:4566' );
		PluginConfig::set_use_path_style( true );
		PluginConfig::set_delete_local( true );
		PluginConfig::set_base_prefix( 'test-prefix' );

		$this->assertEquals( 'test-access-key', PluginConfig::get_access_key() );
		$this->assertEquals( 'test-secret-key', PluginConfig::get_secret_key() );
		$this->assertEquals( 'test-bucket', PluginConfig::get_bucket() );
		$this->assertEquals( 'us-west-2', PluginConfig::get_region() );
		$this->assertEquals( 'http://localhost:4566', PluginConfig::get_endpoint() );
		$this->assertTrue( PluginConfig::get_use_path_style() );
		$this->assertTrue( PluginConfig::get_delete_local() );
		$this->assertEquals( 'test-prefix', PluginConfig::get_base_prefix() );
	}
}
