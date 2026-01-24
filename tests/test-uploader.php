<?php
namespace S3Offloader\Tests;

use S3Offloader\Uploader;
use WP_UnitTestCase;


class Test_Uploader extends WP_UnitTestCase {
	
	public function test_get_s3_key() {
		$attachment_id = $this->factory->attachment->create_upload_object(
			dirname( __FILE__ ) . '/fixtures/test-image.jpg'
		);

		$this->assertGreaterThan( 0, $attachment_id );

		// Test key generation.
		$key = $this->invoke_private_method( Uploader::class, 'get_s3_key', [ $attachment_id ] );
		$this->assertNotEmpty( $key );
		$this->assertStringContainsString( '.jpg', $key );
	}

	
	private function invoke_private_method( $class_name, $method_name, $parameters = [] ) {
		$reflection = new \ReflectionClass( $class_name );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( null, $parameters );
	}
}
