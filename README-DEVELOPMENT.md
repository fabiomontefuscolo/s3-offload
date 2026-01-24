# Development Setup

This guide will help you set up a local development environment for the S3 Offloader plugin using Docker and LocalStack.

## Prerequisites

- Docker
- Docker Compose
- Basic understanding of WordPress plugin development

## Quick Start

1. **Run the setup script:**
   ```bash
   chmod +x setup-dev.sh
   ./setup-dev.sh
   ```

   This will:
   - Create a `.env` file with LocalStack configuration
   - Start all Docker containers (WordPress, MySQL, LocalStack S3)
   - Install WordPress
   - Activate the plugin
   - Configure the plugin to use LocalStack

2. **Access WordPress:**
   - URL: http://localhost:8080/wp-admin
   - Username: `admin`
   - Password: `admin`

3. **Test S3 connection:**
   ```bash
   docker-compose exec wpcli wp s3-offloader test-connection
   ```

## Manual Setup

If you prefer to set up manually:

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Start Docker containers:**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies:**
   ```bash
   docker-compose exec wordpress composer install -d /var/www/html/wp-content/plugins/s3-offloader
   ```

4. **Install WordPress:**
   ```bash
   docker-compose exec wpcli wp core install \
     --url=localhost:8080 \
     --title="Dev Site" \
     --admin_user=admin \
     --admin_password=admin \
     --admin_email=admin@example.com
   ```

5. **Activate plugin:**
   ```bash
   docker-compose exec wpcli wp plugin activate s3-offloader
   ```

6. **Configure plugin settings in WordPress Admin:**
   - Go to Settings > S3 Offloader
   - AWS Access Key: `test`
   - AWS Secret Key: `test`
   - S3 Bucket: `wordpress-media`
   - AWS Region: `us-east-1`
   - Custom Endpoint: `http://localstack:4566`
   - Enable "Use Path Style Endpoint"

## LocalStack S3

LocalStack provides a local S3-compatible service for testing:

- **Endpoint:** http://localhost:4566
- **Bucket:** `wordpress-media` (automatically created)
- **Credentials:** `test` / `test` (no real AWS credentials needed)

### Accessing LocalStack S3

You can interact with LocalStack S3 using AWS CLI:

```bash
# Install awscli-local
pip install awscli-local

# List buckets
awslocal s3 ls

# List files in bucket
awslocal s3 ls s3://wordpress-media/

# Download a file
awslocal s3 cp s3://wordpress-media/path/to/file.jpg ./
```

Or use regular AWS CLI with endpoint override:

```bash
aws --endpoint-url=http://localhost:4566 s3 ls s3://wordpress-media/
```

## Testing Uploads

1. **Via WordPress Admin:**
   - Go to Media > Add New
   - Upload an image
   - Check LocalStack to verify upload

2. **Via WP-CLI:**
   ```bash
   # Test connection
   docker-compose exec wpcli wp s3-offloader test-connection
   
   # Sync existing media
   docker-compose exec wpcli wp s3-offloader sync
   ```

## Running Tests

```bash
# Install test dependencies
docker-compose exec wordpress composer install --dev -d /var/www/html/wp-content/plugins/s3-offloader

# Run PHPUnit tests
docker-compose exec wordpress vendor/bin/phpunit -c /var/www/html/wp-content/plugins/s3-offloader/phpunit.xml

# Run code sniffer
docker-compose exec wordpress vendor/bin/phpcs --standard=/var/www/html/wp-content/plugins/s3-offloader/phpcs.xml
```

## Useful Commands

```bash
# View logs
docker-compose logs -f wordpress
docker-compose logs -f localstack

# Restart services
docker-compose restart

# Stop all services
docker-compose down

# Stop and remove volumes (fresh start)
docker-compose down -v

# Access WordPress container
docker-compose exec wordpress bash

# Access WP-CLI
docker-compose exec wpcli wp --info
```

## Debugging

1. **Check LocalStack status:**
   ```bash
   curl http://localhost:4566/health
   ```

2. **Verify S3 bucket exists:**
   ```bash
   docker-compose exec wpcli wp shell
   ```
   Then in the shell:
   ```php
   $client = new Aws\S3\S3Client([
       'version' => 'latest',
       'region' => 'us-east-1',
       'endpoint' => 'http://localstack:4566',
       'use_path_style_endpoint' => true,
       'credentials' => ['key' => 'test', 'secret' => 'test']
   ]);
   $result = $client->listBuckets();
   print_r($result);
   ```

3. **Check WordPress error logs:**
   ```bash
   docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log
   ```

## Production Configuration

When deploying to production with real AWS S3:

1. Update settings in WordPress Admin:
   - Use real AWS credentials
   - Remove custom endpoint
   - Disable "Use Path Style Endpoint"
   - Set correct region for your bucket

2. Or use environment variables/constants in `wp-config.php`

## Troubleshooting

**Problem:** LocalStack bucket not created
```bash
# Manually create bucket
docker-compose exec localstack awslocal s3 mb s3://wordpress-media
```

**Problem:** Permission denied on setup script
```bash
chmod +x setup-dev.sh
```

**Problem:** Composer dependencies not found
```bash
docker-compose exec wordpress composer install -d /var/www/html/wp-content/plugins/s3-offloader
```
