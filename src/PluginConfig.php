<?php

namespace S3Offloader;

class PluginConfig {

	public static function getAccessKey(): string|null {
		return get_option( 's3_offloader_access_key' );
	}

	public static function getSecretKey(): string|null {
		return get_option( 's3_offloader_secret_key' );
	}

	public static function getRegion(): string {
		return get_option( 's3_offloader_region', 'us-east-1' );
	}

	public static function getEndpoint(): string {
		return get_option( 's3_offloader_endpoint', '' );
	}

	public static function getUsePathStyle(): bool {
		return get_option( 's3_offloader_use_path_style', false ) == true;
	}

	public static function getBucket(): string|null {
		return get_option( 's3_offloader_bucket' );
	}

	public static function getDeleteLocal(): bool {
		return get_option( 's3_offloader_delete_local', false ) == true;
	}
}
