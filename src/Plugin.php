<?php

namespace S3Offloader;

use S3Offloader\Admin\SettingsPage;

class Plugin
{
    private static $instance = null;

    private function __construct()
    {
        $this->init_hooks();
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_attachment', array(Uploader::class, 'upload_to_s3'));
        add_filter('wp_get_attachment_url', array(Uploader::class, 'filter_attachment_url'), 10, 2);

        if (defined('WP_CLI')) {
            \WP_CLI::add_command('s3-offloader', CLI\Commands::class);
        }
    }

    public function add_admin_menu()
    {
        add_options_page(
            __('S3 Offloader Settings', 's3-offloader'),
            __('S3 Offloader', 's3-offloader'),
            'manage_options',
            's3-offloader',
            array(SettingsPage::class, 'render_page')
        );
    }
}
