<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package S3_Offloader
 */

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load PHPUnit Polyfills.
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/s3-offloader.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Configure S3 Offloader to use LocalStack for tests.
 *
 * This allows tests to actually upload to S3 (LocalStack) instead of mocking,
 * making them more realistic integration tests.
 */
function _setup_localstack_for_tests() {
	update_option( 's3_offloader_access_key', 'test' );
	update_option( 's3_offloader_secret_key', 'test' );
	update_option( 's3_offloader_bucket', 'wordpress-media' );
	update_option( 's3_offloader_region', 'us-east-1' );
	update_option( 's3_offloader_endpoint', 'http://localstack:4566' );
	update_option( 's3_offloader_use_path_style', true );
	update_option( 's3_offloader_delete_local', false );
}

tests_add_filter( 'wp_loaded', '_setup_localstack_for_tests' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
