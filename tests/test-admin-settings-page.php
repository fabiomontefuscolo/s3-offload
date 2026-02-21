<?php

/**
 * Class SampleTest
 *
 * @package S3_Offloader
 */

use S3Offloader\Admin\SettingsPage;
use S3Offloader\PluginConfig;

/**
 * Sample test case.
 */
class AdminSettingsPageTest extends WP_UnitTestCase {


	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unset( $_POST['s3_offloader_access_key'] );
		unset( $_POST['s3_offloader_secret_key'] );
		unset( $_POST['s3_offloader_bucket'] );
		unset( $_POST['s3_offloader_region'] );
		unset( $_POST['s3_offloader_endpoint'] );
		unset( $_POST['s3_offloader_use_path_style'] );
		unset( $_POST['s3_offloader_base_prefix'] );
		unset( $_POST['s3_offloader_delete_local'] );
		unset( $_POST['s3_offloader_submit'] );
		unset( $_POST['_wpnonce'] );

		parent::tearDown();
	}

	/**
	 * Test that the settings page shows the needed fields.
	 */
	public function test_showing_user_the_needed_fields() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		ob_start();
		SettingsPage::render_page();
		$html = ob_get_clean();

		$this->assertNotEmpty( $html );

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$this->assertNotEmpty( $dom->getElementById( 's3_offloader_access_key' ) );
		$this->assertNotEmpty( $dom->getElementById( 's3_offloader_secret_key' ) );
		$this->assertNotEmpty( $dom->getElementById( 's3_offloader_bucket' ) );
		$this->assertNotEmpty( $dom->getElementById( 's3_offloader_region' ) );
		$this->assertNotEmpty( $dom->getElementById( 's3_offloader_endpoint' ) );
	}

	/**
	 * Test saving settings page works as expected.
	 */
	public function test_saving_settings_works_as_expected() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$nonce = wp_create_nonce( 's3_offloader_settings' );

		$access_key = bin2hex( random_bytes( 16 ) );
		$secret_key = bin2hex( random_bytes( 16 ) );

		$_POST['s3_offloader_access_key']     = $access_key;
		$_POST['s3_offloader_secret_key']     = $secret_key;
		$_POST['s3_offloader_bucket']         = 'test-bucket';
		$_POST['s3_offloader_region']         = 'us-east-1';
		$_POST['s3_offloader_endpoint']       = 'test-endpoint';
		$_POST['s3_offloader_use_path_style'] = '1';
		$_POST['s3_offloader_base_prefix']    = 'test-prefix';
		$_POST['s3_offloader_delete_local']   = '1';
		$_POST['s3_offloader_submit']         = '1';
		$_POST['_wpnonce']                    = $nonce;

		// WordPress's check_admin_referer() checks $_REQUEST, not just $_POST
		$_REQUEST['_wpnonce'] = $nonce;

		ob_start();
		SettingsPage::render_page();
		ob_end_clean();

		$this->assertEquals( $access_key, PluginConfig::get_access_key() );
		$this->assertEquals( $secret_key, PluginConfig::get_secret_key() );
		$this->assertEquals( 'test-bucket', PluginConfig::get_bucket() );
		$this->assertEquals( 'us-east-1', PluginConfig::get_region() );
		$this->assertEquals( 'test-endpoint', PluginConfig::get_endpoint() );
		$this->assertEquals( true, PluginConfig::get_use_path_style() );
		$this->assertEquals( 'test-prefix', PluginConfig::get_base_prefix() );
		$this->assertEquals( true, PluginConfig::get_delete_local() );
	}

	/**
	 * Test settings are saved with safe values
	 */
	public function test_saving_settings_with_safe_values() {
		$_POST['s3_offloader_access_key']     = '<i>test-access-key';
		$_POST['s3_offloader_secret_key']     = '<b>test-secret-key</b>';
		$_POST['s3_offloader_bucket']         = 'test<b>-<b>bucket';
		$_POST['s3_offloader_region']         = 'us-east-\1';
		$_POST['s3_offloader_endpoint']       = 'test-endpoint<script>';
		$_POST['s3_offloader_base_prefix']    = '\\test-prefix';
		$_POST['s3_offloader_use_path_style'] = '11';
		$_POST['s3_offloader_delete_local']   = '<b>';

		self::invoke_private_class_method( SettingsPage::class, 'save_settings', array() );

		$this->assertEquals( 'test-access-key', PluginConfig::get_access_key() );
		$this->assertEquals( 'test-secret-key', PluginConfig::get_secret_key() );
		$this->assertEquals( 'test-bucket', PluginConfig::get_bucket() );
		$this->assertEquals( 'us-east-1', PluginConfig::get_region() );
		$this->assertEquals( 'test-endpoint', PluginConfig::get_endpoint() );
		$this->assertEquals( 'test-prefix', PluginConfig::get_base_prefix() );
		$this->assertEquals( false, PluginConfig::get_use_path_style() );
		$this->assertEquals( false, PluginConfig::get_delete_local() );
	}

	public function test_only_admin_can_access_settings_page() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		ob_start();
		SettingsPage::render_page();
		$html = ob_get_clean();

		$this->assertEmpty( $html );
	}

	private static function invoke_private_class_method( string $class_name, string $method_name, array $args = array() ) {
		$reflection = new ReflectionClass( $class_name );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( null, $args );
	}
}
