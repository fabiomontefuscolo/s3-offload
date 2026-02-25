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
		// Clean up $_POST superglobal (form field names)
		unset( $_POST[ SettingsPage::FIELD_ACCESS_KEY ] );
		unset( $_POST[ SettingsPage::FIELD_SECRET_KEY ] );
		unset( $_POST[ SettingsPage::FIELD_BUCKET ] );
		unset( $_POST[ SettingsPage::FIELD_REGION ] );
		unset( $_POST[ SettingsPage::FIELD_ENDPOINT ] );
		unset( $_POST[ SettingsPage::FIELD_USE_PATH_STYLE ] );
		unset( $_POST[ SettingsPage::FIELD_BASE_PREFIX ] );
		unset( $_POST[ SettingsPage::FIELD_CDN_URL ] );
		unset( $_POST[ SettingsPage::FIELD_DELETE_LOCAL ] );
		unset( $_POST[ SettingsPage::FIELD_SUBMIT ] );
		unset( $_POST['_wpnonce'] );
		unset( $_REQUEST['_wpnonce'] );

		// Clean up WordPress options
		delete_option( PluginConfig::OPTION_ACCESS_KEY );
		delete_option( PluginConfig::OPTION_SECRET_KEY );
		delete_option( PluginConfig::OPTION_BUCKET );
		delete_option( PluginConfig::OPTION_REGION );
		delete_option( PluginConfig::OPTION_ENDPOINT );
		delete_option( PluginConfig::OPTION_USE_PATH_STYLE );
		delete_option( PluginConfig::OPTION_BASE_PREFIX );
		delete_option( PluginConfig::OPTION_CDN_URL );
		delete_option( PluginConfig::OPTION_DELETE_LOCAL );

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

		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_ACCESS_KEY ) );
		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_SECRET_KEY ) );
		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_BUCKET ) );
		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_REGION ) );
		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_ENDPOINT ) );
		$this->assertNotEmpty( $dom->getElementById( SettingsPage::FIELD_CDN_URL ) );
	}

	/**
	 * Test saving settings page works as expected.
	 */
	public function test_saving_settings_works_as_expected() {
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$nonce = wp_create_nonce( SettingsPage::NONCE_ACTION );

		$access_key = bin2hex( random_bytes( 16 ) );
		$secret_key = bin2hex( random_bytes( 16 ) );

		$_POST[ SettingsPage::FIELD_ACCESS_KEY ]     = $access_key;
		$_POST[ SettingsPage::FIELD_SECRET_KEY ]     = $secret_key;
		$_POST[ SettingsPage::FIELD_BUCKET ]         = 'test-bucket';
		$_POST[ SettingsPage::FIELD_REGION ]         = 'us-east-1';
		$_POST[ SettingsPage::FIELD_ENDPOINT ]       = 'test-endpoint';
		$_POST[ SettingsPage::FIELD_USE_PATH_STYLE ] = '1';
		$_POST[ SettingsPage::FIELD_BASE_PREFIX ]    = 'test-prefix';
		$_POST[ SettingsPage::FIELD_CDN_URL ]        = 'https://cdn.example.com';
		$_POST[ SettingsPage::FIELD_DELETE_LOCAL ]   = '1';
		$_POST[ SettingsPage::FIELD_SUBMIT ]         = '1';
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
		$this->assertEquals( 'https://cdn.example.com', PluginConfig::get_cdn_url() );
		$this->assertEquals( true, PluginConfig::get_delete_local() );
	}

	/**
	 * Test settings are saved with safe values
	 */
	public function test_saving_settings_with_safe_values() {
		$_POST[ SettingsPage::FIELD_ACCESS_KEY ]     = '<i>test-access-key';
		$_POST[ SettingsPage::FIELD_SECRET_KEY ]     = '<b>test-secret-key</b>';
		$_POST[ SettingsPage::FIELD_BUCKET ]         = 'test<b>-<b>bucket';
		$_POST[ SettingsPage::FIELD_REGION ]         = 'us-east-\1';
		$_POST[ SettingsPage::FIELD_ENDPOINT ]       = 'test-endpoint<script>';
		$_POST[ SettingsPage::FIELD_BASE_PREFIX ]    = '\\test-prefix';
		$_POST[ SettingsPage::FIELD_CDN_URL ]        = 'https://cdn.example.com/<script>';
		$_POST[ SettingsPage::FIELD_USE_PATH_STYLE ] = '11';
		$_POST[ SettingsPage::FIELD_DELETE_LOCAL ]   = '<b>';

		self::invoke_private_class_method( SettingsPage::class, 'save_settings', array() );

		$this->assertEquals( 'test-access-key', PluginConfig::get_access_key() );
		$this->assertEquals( 'test-secret-key', PluginConfig::get_secret_key() );
		$this->assertEquals( 'test-bucket', PluginConfig::get_bucket() );
		$this->assertEquals( 'us-east-1', PluginConfig::get_region() );
		$this->assertEquals( 'test-endpoint', PluginConfig::get_endpoint() );
		$this->assertEquals( 'test-prefix', PluginConfig::get_base_prefix() );
		$this->assertEquals( 'https://cdn.example.com', PluginConfig::get_cdn_url() );
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
