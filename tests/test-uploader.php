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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
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

		$key = $this->invoke_private_method( 'S3Offloader\Uploader', 'get_s3_key', array( $attachment_id ) );
		$this->assertNotEmpty( $key );
		$this->assertStringContainsString( '.jpg', $key );
	}

	/**
	 * Invoke a private or protected method using reflection.
	 *
	 * @param string $class_name  Fully qualified class name.
	 * @param string $method_name Method name to invoke.
	 * @param array  $parameters  Method parameters.
	 * @return mixed Method return value.
	 */
	private function invoke_private_method( $class_name, $method_name, $parameters = array() ) {
		$reflection = new \ReflectionClass( $class_name );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $parameters );
	}
}
