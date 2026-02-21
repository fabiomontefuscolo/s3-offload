<?php
/**
 * Tests for the Uploader class.
 *
 * @package S3_Offloader
 */
use Aws\Command;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

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
		// Remove auto-upload hook to allow tests to control when uploads happen.
		remove_filter( 'wp_generate_attachment_metadata', array( S3Offloader\Uploader::class, 'upload_after_metadata_generation' ), 10 );
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

		$client = S3Offloader\Uploader::get_s3_client();
		$this->assertFalse( $client );
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
	 * Test filter_attachment_url returns S3 URL when attachment is uploaded to S3.
	 *
	 * @return void
	 */
	public function test_filter_attachment_url_with_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$original_url = wp_get_attachment_url( $attachment_id );
		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;

		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );
		$filtered_url = S3Offloader\Uploader::filter_attachment_url( $original_url, $attachment_id );

		$this->assertNotEquals( $original_url, $filtered_url );
		$this->assertEquals( $s3_url, $filtered_url );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_bucket(), $filtered_url );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_endpoint(), $filtered_url );
	}

	/**
	 * Test filter_attachment_url returns original URL when not uploaded to S3.
	 *
	 * @return void
	 */
	public function test_filter_attachment_url_without_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$original_url = wp_get_attachment_url( $attachment_id );
		$filtered_url = S3Offloader\Uploader::filter_attachment_url( $original_url, $attachment_id );

		$this->assertEquals( $original_url, $filtered_url );
	}

	/**
	 * Test filter_attachment_image_src with S3 URL.
	 *
	 * @return void
	 */
	public function test_filter_attachment_image_src_with_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		// Use actual attachment URL instead of hardcoded URL.
		$actual_url = wp_get_attachment_url( $attachment_id );
		$image      = array(
			$actual_url,
			800,
			600,
			false,
		);

		$filtered = S3Offloader\Uploader::filter_attachment_image_src( $image, $attachment_id, 'full', false );

		$this->assertIsArray( $filtered );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_endpoint(), $filtered[0] );
		$this->assertEquals( 800, $filtered[1] );
		$this->assertEquals( 600, $filtered[2] );
	}

	/**
	 * Test filter_attachment_image_src returns false when input is false.
	 *
	 * @return void
	 */
	public function test_filter_attachment_image_src_with_false_input() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$filtered = S3Offloader\Uploader::filter_attachment_image_src( false, $attachment_id, 'full', false );

		$this->assertFalse( $filtered );
	}

	/**
	 * Test filter_attachment_image_src without S3 URL returns original.
	 *
	 * @return void
	 */
	public function test_filter_attachment_image_src_without_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$image = array(
			'http://example.com/wp-content/uploads/2026/02/test-image.jpg',
			800,
			600,
			false,
		);

		$filtered = S3Offloader\Uploader::filter_attachment_image_src( $image, $attachment_id, 'full', false );

		$this->assertEquals( $image, $filtered );
	}

	/**
	 * Test filter_image_srcset with S3 URL.
	 *
	 * @return void
	 */
	public function test_filter_image_srcset_with_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		// Use actual attachment URL as base for constructing thumbnail URLs.
		$base_url = wp_get_attachment_url( $attachment_id );
		$dir_url  = dirname( $base_url );
		$sources  = array(
			800 => array(
				'url'        => $dir_url . '/test-image-800x600.jpg',
				'descriptor' => 'w',
				'value'      => 800,
			),
			400 => array(
				'url'        => $dir_url . '/test-image-400x300.jpg',
				'descriptor' => 'w',
				'value'      => 400,
			),
		);

		$filtered = S3Offloader\Uploader::filter_image_srcset( $sources, array( 800, 600 ), '', array(), $attachment_id );

		$this->assertIsArray( $filtered );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_endpoint(), $filtered[800]['url'] );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_endpoint(), $filtered[400]['url'] );
	}

	/**
	 * Test filter_image_srcset returns false when input is false.
	 *
	 * @return void
	 */
	public function test_filter_image_srcset_with_false_input() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$filtered = S3Offloader\Uploader::filter_image_srcset( false, array(), '', array(), $attachment_id );

		$this->assertFalse( $filtered );
	}

	/**
	 * Test filter_image_downsize with S3 URL for full size.
	 *
	 * @return void
	 */
	public function test_filter_image_downsize_full_size() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		$result = S3Offloader\Uploader::filter_image_downsize( false, $attachment_id, 'full' );

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result );
		$this->assertStringContainsString( S3Offloader\PluginConfig::get_endpoint(), $result[0] );
		$this->assertFalse( $result[3] );
	}

	/**
	 * Test filter_image_downsize without S3 URL returns original.
	 *
	 * @return void
	 */
	public function test_filter_image_downsize_without_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$result = S3Offloader\Uploader::filter_image_downsize( false, $attachment_id, 'full' );

		$this->assertFalse( $result );
	}

	/**
	 * Test filter_image_downsize with array size returns full-size S3 image.
	 * WordPress core can't resize files that are only on S3.
	 *
	 * @return void
	 */
	public function test_filter_image_downsize_with_array_size() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		$result = S3Offloader\Uploader::filter_image_downsize( false, $attachment_id, array( 800, 600 ) );

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result );
		$this->assertStringContainsString( 'localstack', $result[0] );
		$this->assertFalse( $result[3] );
	}

	/**
	 * Test filter_image_downsize with specific size.
	 *
	 * @return void
	 */
	public function test_filter_image_downsize_with_specific_size() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$s3_url = 'https://test-bucket.s3.us-east-1.amazonaws.com/2026/02/test-image.jpg';
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		// Get metadata to check if thumbnails exist.
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $meta['sizes'] ) ) {
			$size_name = array_key_first( $meta['sizes'] );
			$result    = S3Offloader\Uploader::filter_image_downsize( false, $attachment_id, $size_name );

			$this->assertIsArray( $result );
			$this->assertCount( 4, $result );
			$this->assertStringContainsString( 'localstack', $result[0] );
			$this->assertTrue( $result[3] );
		} else {
			$this->markTestSkipped( 'No thumbnail sizes available for testing' );
		}
	}

	/**
	 * Test filter_attachment_for_js with S3 URL.
	 *
	 * @return void
	 */
	public function test_filter_attachment_for_js_with_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		$attachment = get_post( $attachment_id );
		// Use actual attachment URL instead of hardcoded URLs.
		$base_url = wp_get_attachment_url( $attachment_id );
		$dir_url  = dirname( $base_url );
		$response = array(
			'url'   => $base_url,
			'sizes' => array(
				'thumbnail' => array(
					'url' => $dir_url . '/test-image-150x150.jpg',
				),
			),
		);

		$filtered = S3Offloader\Uploader::filter_attachment_for_js( $response, $attachment, array() );

		$this->assertIsArray( $filtered );
		$this->assertStringContainsString( 'localstack', $filtered['url'] );
		$this->assertStringContainsString( 'localstack', $filtered['sizes']['thumbnail']['url'] );
	}

	/**
	 * Test filter_attachment_for_js without S3 URL returns original.
	 *
	 * @return void
	 */
	public function test_filter_attachment_for_js_without_s3_url() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$attachment = get_post( $attachment_id );
		$response   = array(
			'url'   => 'http://example.com/wp-content/uploads/2026/02/test-image.jpg',
			'sizes' => array(
				'thumbnail' => array(
					'url' => 'http://example.com/wp-content/uploads/2026/02/test-image-150x150.jpg',
				),
			),
		);

		$filtered = S3Offloader\Uploader::filter_attachment_for_js( $response, $attachment, array() );

		$this->assertEquals( $response, $filtered );
	}

	/**
	 * Test filter_content_urls replaces URLs in content.
	 *
	 * @return void
	 */
	public function test_filter_content_urls_with_s3_attachment() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$original_url = wp_get_attachment_url( $attachment_id );
		// Compute S3 URL based on test configuration.
		$s3_key  = S3Offloader\Uploader::get_s3_key( $attachment_id );
		$s3_base = S3Offloader\Uploader::get_s3_base_url(
			S3Offloader\PluginConfig::get_bucket(),
			S3Offloader\PluginConfig::get_endpoint(),
			S3Offloader\PluginConfig::get_region(),
			S3Offloader\PluginConfig::get_use_path_style()
		);
		$s3_url  = $s3_base . '/' . $s3_key;
		update_post_meta( $attachment_id, S3Offloader\Uploader::META_S3_URL, $s3_url );

		$content = sprintf( '<img src="%s" alt="Test">', $original_url );

		$filtered = S3Offloader\Uploader::filter_content_urls( $content );

		$this->assertStringContainsString( 'localstack', $filtered );
		$this->assertStringNotContainsString( $original_url, $filtered );
	}

	/**
	 * Test filter_content_urls without S3 URLs returns original.
	 *
	 * @return void
	 */
	public function test_filter_content_urls_without_s3_attachment() {
		$content = '<p>This is some content without upload URLs.</p>';

		$filtered = S3Offloader\Uploader::filter_content_urls( $content );

		$this->assertEquals( $content, $filtered );
	}

	/**
	 * Test filter_content_urls with non-offloaded attachment.
	 *
	 * @return void
	 */
	public function test_filter_content_urls_with_non_offloaded_attachment() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$original_url = wp_get_attachment_url( $attachment_id );
		$content      = sprintf( '<img src="%s" alt="Test">', $original_url );

		$filtered = S3Offloader\Uploader::filter_content_urls( $content );

		$this->assertEquals( $content, $filtered );
	}

	/**
	 * Test upload_after_metadata_generation returns metadata unmodified.
	 *
	 * @return void
	 */
	public function test_upload_after_metadata_generation_returns_metadata() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$metadata = array(
			'width'  => 800,
			'height' => 600,
			'file'   => '2026/02/test-image.jpg',
		);

		$result = S3Offloader\Uploader::upload_after_metadata_generation( $metadata, $attachment_id );

		$this->assertEquals( $metadata, $result );
	}

	/**
	 * Test upload_to_s3 returns false with invalid attachment ID.
	 *
	 * @return void
	 */
	public function test_upload_to_s3_with_invalid_attachment() {
		$result = S3Offloader\Uploader::upload_to_s3( 99999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test upload_to_s3 returns false with empty bucket.
	 *
	 * @return void
	 */
	public function test_upload_to_s3_with_empty_bucket() {
		S3Offloader\PluginConfig::set_bucket( '' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$result = S3Offloader\Uploader::upload_to_s3( $attachment_id );

		$this->assertFalse( $result );
	}

	/**
	 * Test upload_to_s3 returns false with invalid credentials.
	 *
	 * @return void
	 */
	public function test_upload_to_s3_with_invalid_credentials() {
		S3Offloader\PluginConfig::set_access_key( '' );
		S3Offloader\PluginConfig::set_secret_key( '' );

		$attachment_id = $this->factory->attachment->create_upload_object(
			__DIR__ . '/fixtures/test-image.jpg'
		);

		$result = S3Offloader\Uploader::upload_to_s3( $attachment_id );

		$this->assertFalse( $result );
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
		$property->setValue( null, $value );
	}
}
