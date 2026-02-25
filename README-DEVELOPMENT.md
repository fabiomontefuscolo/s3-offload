# Development Setup

This guide will help you set up a local development environment for the S3 Offloader plugin.

## Prerequisites

- Docker and Docker Compose
- Basic understanding of WordPress plugin development

## Quick Start

1. **Run the setup script:**
```bash
./setup-dev.sh
```

This will start all services, install WordPress, activate the plugin, and configure it for LocalStack.

2. **Access WordPress:**
- URL: http://localhost:8080/wp-admin
- Username: `admin`
- Password: `admin`

## Development Environment

### Services

- **WordPress**: http://localhost:8080
- **MySQL**: localhost:3306 (credentials: `wordpress`/`wordpress`)
- **LocalStack S3**: http://localhost:4566

### LocalStack Configuration

The plugin is pre-configured to use LocalStack with these settings:
- Access Key: `test`
- Secret Key: `test`
- Bucket: `wordpress-media`
- Region: `us-east-1`
- Endpoint: `http://localstack:4566`
- Use Path Style: Enabled

## Running Tests

```bash
# All tests
./run-tests.sh

# With coverage report
./run-tests-coverage.sh

# Specific test file
./run-tests.sh tests/test-uploader.php

# Code style check
docker-compose exec -w /var/www/html/wp-content/plugins/s3-offloader wordpress ./vendor/bin/phpcs

# Code style fix
docker-compose exec -w /var/www/html/wp-content/plugins/s3-offloader wordpress ./vendor/bin/phpcbf
```

## Testing

### Upload Media
1. Go to Media → Add New in WordPress admin
2. Upload an image
3. Verify URLs point to LocalStack

### WP-CLI Commands
```bash
# Sync existing media
docker-compose exec -u www-data wordpress wp s3-offloader sync

# List plugins
docker-compose exec -u www-data wordpress wp plugin list
```

### Verify in LocalStack
```bash
# List buckets
docker-compose exec localstack awslocal s3 ls

# List files in bucket
docker-compose exec localstack awslocal s3 ls s3://wordpress-media/ --recursive
```

### Test CDN Configuration
1. Go to Settings → S3 Offloader
2. Set **CDN URL** to `http://localhost:4566/wordpress-media`
3. Upload a new image
4. Verify media URLs use the CDN URL

## Docker Commands

```bash
# View logs
docker-compose logs -f wordpress

# Restart services
docker-compose restart

# Stop services
docker-compose down

# Fresh start (removes all data)
docker-compose down -v

# Access container
docker-compose exec wordpress bash
```

## Debugging

```bash
# WordPress debug log
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log

# LocalStack health check
curl http://localhost:4566/_localstack/health

# Check plugin settings
docker-compose exec -u www-data wordpress wp option get s3_offloader_bucket
docker-compose exec -u www-data wordpress wp option get s3_offloader_cdn_url
```

## Common Issues

**LocalStack bucket not created:**
```bash
docker-compose exec localstack awslocal s3 mb s3://wordpress-media
```

**Composer dependencies missing:**
```bash
docker-compose exec wordpress composer install -d /var/www/html/wp-content/plugins/s3-offloader
```

**Test database issues:**
```bash
docker-compose exec db mysql -u root -prootpassword -e "DROP DATABASE IF EXISTS wordpress_test; CREATE DATABASE wordpress_test;"
docker-compose exec wordpress bash /var/www/html/wp-content/plugins/s3-offloader/bin/install-wp-tests.sh wordpress_test wordpress wordpress db latest
```

## Contributing

1. Write tests for new features
2. Ensure all tests pass: `./run-tests.sh`
3. Check code style before committing
4. Update documentation as needed

**Code Standards:**
- PSR-4 autoloading
- WordPress Coding Standards
- 95%+ test coverage