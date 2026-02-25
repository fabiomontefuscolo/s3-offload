# S3 Offloader for WordPress

A WordPress plugin that offloads media files to S3-compatible storage services, reducing server load and improving performance with CDN support.

> **⚠️ Work in Progress**: This plugin is currently under active development. While functional, some features may change and bugs may exist. Use with caution in production environments.

## What is S3 Offloader?

S3 Offloader automatically uploads your WordPress media files to Amazon S3 or any S3-compatible storage service (like Cloudflare R2, DigitalOcean Spaces, etc.) and serves them directly from there or through a CDN. This reduces bandwidth usage on your WordPress server and improves content delivery speed globally.

## Features

- 🚀 **Automatic Upload** - Media files are automatically uploaded to S3 upon upload
- 🌐 **CDN Support** - Serve files through CloudFront, Cloudflare R2, or any CDN
- 🗑️ **Local Cleanup** - Optional deletion of local files after upload
- ⚙️ **S3-Compatible** - Works with AWS S3, Cloudflare R2, DigitalOcean Spaces, MinIO, LocalStack
- 🔄 **Bulk Sync** - WP-CLI integration for syncing existing media files

## Installation

1. Clone or download this repository:
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/fabiomontefuscolo/s3-offloader.git
```

2. Install dependencies:
```bash
cd s3-offloader
composer install --no-dev
```

3. Activate the plugin in WordPress Admin:
- Go to **Plugins → Installed Plugins**
- Find "S3 Offloader" and click **Activate**

4. Configure the plugin:
- Go to **Settings → S3 Offloader**
- Enter your S3 credentials and bucket information
- Optionally add a CDN URL
- Save settings

## Configuration

Navigate to **Settings → S3 Offloader** in your WordPress admin panel:

- **AWS Access Key** - Your S3 access key
- **AWS Secret Key** - Your S3 secret key
- **S3 Bucket** - Your bucket name
- **AWS Region** - e.g., `us-east-1`
- **Custom Endpoint** - For S3-compatible services (e.g., `https://your-account.r2.cloudflarestorage.com`)
- **CDN URL** - Optional CDN domain (e.g., `https://cdn.example.com` or `http://localhost:8080` for development)
- **Base Directory Prefix** - Optional prefix for organizing files
- **Delete Local Files** - Remove files from server after upload

## Sponsored By

This plugin is sponsored by [**Escola Educação**](https://escolaeducacao.com.br/)

## Thanks To

Special thanks to:
- [@valterscherer](https://github.com/valterscherer)
- [@amixel](https://github.com/amixel)

## License

This project is open source. See LICENSE file for details.
