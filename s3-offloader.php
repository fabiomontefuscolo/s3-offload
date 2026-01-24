<?php
/**
 * Plugin Name: S3 Offloader
 * Description: WordPress plugin to offload media files S3-like storage services.
 * Version: 1.0.0
 * Author: Fabio Montefuscolo<fabio.montefuscolo@gmail.com>
 * Requires PHP: 7.4
 */

namespace S3Offloader;

if ( ! defined( 'ABSPATH' ) )
{
	exit;
}

define( 'S3_OFFLOADER_VERSION', '1.0.0' );
define( 'S3_OFFLOADER_PATH', \plugin_dir_path( __FILE__ ) );
define( 'S3_OFFLOADER_URL', \plugin_dir_url( __FILE__ ) );

require_once S3_OFFLOADER_PATH . 'vendor/autoload.php';

add_action( 'plugins_loaded', function() {
	Plugin::get_instance();
} );
