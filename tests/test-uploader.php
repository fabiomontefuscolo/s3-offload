<?php
/**
 * Tests for the Uploader class.
 *
 * @package S3_Offloader
 */

/**
 * Test case for Uploader class.
 */
class Test_Uploader extends WP_UnitTestCase {

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
	}

	public function tear_down(): void {
		parent::tear_down();
		self::set_private_class_property( S3Offloader\Uploader::class, 's3_client', null );
	}

	/**
	 * Test get_s3_config returns expected configuration array.
	 *
	 * @return void
	 */
	public function test_get_s3_config() {
		$config = S3Offloader\Uploader::get_s3_config();

		$expected_config = array(
			'version'                 => 'latest',
			'region'                  => S3Offloader\PluginConfig::get_region(),
			'endpoint'                => S3Offloader\PluginConfig::get_endpoint(),
			'use_path_style_endpoint' => S3Offloader\PluginConfig::get_use_path_style(),
			'credentials'             => array(
				'key'    => S3Offloader\PluginConfig::get_access_key(),
				'secret' => S3Offloader\PluginConfig::get_secret_key(),
			),
		);

		$this->assertIsArray( $config );
		$this->assertEquals( $expected_config, $config );
	}

	/**
	 * Test get_s3_config with access key and secret key set to empty strings.
	 *
	 * @return void
	 */
	public function test_get_s3_config_with_empty_credentials() {
		S3Offloader\PluginConfig::set_access_key( '' );
		S3Offloader\PluginConfig::set_secret_key( '' );

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );

		$config = S3Offloader\Uploader::get_s3_config();

		$this->assertEquals( null, $config );
	}


	/**
	 * Test get_s3_config with empty endpoint to ensure it defaults to AWS S3.
	 *
	 * @return void
	 */
	public function test_get_s3_config_with_empty_endpoint() {
		S3Offloader\PluginConfig::set_endpoint( '' );

		$config = S3Offloader\Uploader::get_s3_config();

		$expected_config = array(
			'version'                 => 'latest',
			'region'                  => S3Offloader\PluginConfig::get_region(),
			'use_path_style_endpoint' => S3Offloader\PluginConfig::get_use_path_style(),
			'credentials'             => array(
				'key'    => S3Offloader\PluginConfig::get_access_key(),
				'secret' => S3Offloader\PluginConfig::get_secret_key(),
			),
		);

		$this->assertIsArray( $config );
		$this->assertEquals( $expected_config, $config );
	}


	/**
	 * Test get_s3_config with path-style endpoint disabled
	 *
	 * @return void
	 */
	public function test_get_s3_config_with_virtual_hosted_style() {
		S3Offloader\PluginConfig::set_use_path_style( false );

		$config = S3Offloader\Uploader::get_s3_config();

		$expected_config = array(
			'version'     => 'latest',
			'region'      => S3Offloader\PluginConfig::get_region(),
			'endpoint'    => S3Offloader\PluginConfig::get_endpoint(),
			'credentials' => array(
				'key'    => S3Offloader\PluginConfig::get_access_key(),
				'secret' => S3Offloader\PluginConfig::get_secret_key(),
			),
		);

		$this->assertIsArray( $config );
		$this->assertEquals( $expected_config, $config );
	}

	/**
	 * Test get_s3_client returns an instance of S3Client and is cached.
	 *
	 * @return void
	 */
	public function test_get_s3_client() {
		$client = S3Offloader\Uploader::get_s3_client();

		$this->assertInstanceOf( \Aws\S3\S3Client::class, $client );
		$this->assertTrue( $client === S3Offloader\Uploader::get_s3_client() );
	}


	public function test_get_s3_client_with_empty_credentials() {
		S3Offloader\PluginConfig::set_access_key( '' );
		S3Offloader\PluginConfig::set_secret_key( '' );

		$this->setExpectedException( 'PHPUnit_Framework_Error_Warning' );

		$client = S3Offloader\Uploader::get_s3_client();
	}

	/**
	 * Test get_s3_base_url using virtual-hosted-style endpoint.
	 *
	 * @return void
	 */
	public function test_get_s3_base_url_using_virtual_hosted_style() {
		S3Offloader\PluginConfig::set_use_path_style( false );

		$base_url = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);

		$this->assertEquals( 'http://test-bucket.localstack:4566', $base_url );
	}

	/**
	 * Test get_s3_base_url using path-style endpoint.
	 *
	 * @return void
	 */
	public function test_get_s3_base_url_using_path_style() {
		S3Offloader\PluginConfig::set_use_path_style( true );

		$base_url = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);

		$this->assertEquals( 'http://localstack:4566/test-bucket', $base_url );
	}


	/**
	 * Test get_s3_base_url using empty endpoint to default to AWS S3.
	 *
	 * @return void
	 */
	public function test_get_s3_base_url_with_empty_endpoint() {
		S3Offloader\PluginConfig::set_endpoint( '' );

		$base_url = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);

		$expected_base_url = sprintf(
			'https://%s.s3.%s.amazonaws.com',
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_region(),
		);

		$this->assertEquals( $expected_base_url, $base_url );
	}


	/**
	 * Test get_s3_key without base prefix.
	 *
	 * @return void
	 */
	public function test_get_s3_key_without_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( '' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringContainsString( '.jpg', $key );

		$this->assertStringStartsNotWith( '/', $key );
	}

	/**
	 * Test get_s3_key with simple base prefix.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_simple_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( 'production' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringStartsWith( 'production/', $key );
		$this->assertStringContainsString( '.jpg', $key );
	}

	/**
	 * Test get_s3_key with prefix containing trailing slash.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_trailing_slash_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( 'staging/' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringStartsWith( 'staging/', $key );

		$this->assertStringNotContainsString( '//', $key );
	}

	/**
	 * Test get_s3_key with prefix containing leading slash.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_leading_slash_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( '/development' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringStartsWith( 'development/', $key );
		$this->assertStringNotContainsString( '//', $key );
	}

	/**
	 * Test get_s3_key with multi-level prefix.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_multi_level_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( 'site-1/uploads' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringStartsWith( 'site-1/uploads/', $key );
		$this->assertStringContainsString( '.jpg', $key );
	}

	/**
	 * Test get_s3_key with prefix containing both leading and trailing slashes.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_both_slashes_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( '/test-env/' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringStartsWith( 'test-env/', $key );
		$this->assertStringNotContainsString( '//', $key );
	}

	/**
	 * Test get_s3_key with empty prefix.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_empty_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( '' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringContainsString( '.jpg', $key );
		$this->assertStringStartsNotWith( '/', $key );
	}

	/**
	 * Test get_s3_key with whitespace-only prefix.
	 *
	 * @return void
	 */
	public function test_get_s3_key_with_whitespace_prefix() {
		S3Offloader\PluginConfig::set_base_prefix( '   ' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		$key = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$this->assertNotEmpty( $key );
		$this->assertStringContainsString( '.jpg', $key );
	}

	/**
	 * Helper method to set private class property value using reflection.
	 *
	 * @param object $object The object instance to modify.
	 * @param string $property_name The name of the private property to set.
	 * @param mixed  $value The value to assign to the property.
	 */
	private static function set_private_class_property( $class, $property_name, $value ) {
		$reflection = new ReflectionClass( $class );
		$property   = $reflection->getProperty( $property_name );
		$property->setAccessible( true );
		$property->setValue( $class, $value );
	}
}
