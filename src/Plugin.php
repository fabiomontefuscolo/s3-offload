<?php
/**
 * Main plugin class for S3 Offloader.
 *
 * @package S3Offloader
 */

namespace S3Offloader;

use S3Offloader\Admin\SettingsPage;

/**
 * Main Plugin class.
 */
class Plugin {

	/**
	 * Plugin singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize plugin hooks and filters.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_filter( 'wp_generate_attachment_metadata', array( Uploader::class, 'upload_after_metadata_generation' ), 10, 2 );
		add_filter( 'wp_get_attachment_url', array( Uploader::class, 'filter_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_src', array( Uploader::class, 'filter_attachment_image_src' ), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array( Uploader::class, 'filter_image_srcset' ), 10, 5 );
		add_filter( 'image_downsize', array( Uploader::class, 'filter_image_downsize' ), 10, 3 );
		add_filter( 'wp_prepare_attachment_for_js', array( Uploader::class, 'filter_attachment_for_js' ), 10, 3 );
		add_filter( 'the_content', array( Uploader::class, 'filter_content_urls' ), 10, 1 );

		if ( defined( 'WP_CLI' ) ) {
			\WP_CLI::add_command( 's3-offloader', CLI\Commands::class );
		}
	}

	/**
	 * Add plugin settings page to WordPress admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'S3 Offloader Settings', 's3-offloader' ),
			__( 'S3 Offloader', 's3-offloader' ),
			'manage_options',
			's3-offloader',
			array( SettingsPage::class, 'render_page' )
		);
	}
}
