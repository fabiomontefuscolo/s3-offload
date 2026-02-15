#!/bin/bash

if [ ! -f .env ]; then
    cp .env.example .env
fi

docker-compose up --detach --wait

echo ""
echo "Installing Composer dependencies..."
docker-compose exec wordpress git config --global --add safe.directory /var/www/html/wp-content/plugins/s3-offloader
docker-compose exec wordpress composer install -d /var/www/html/wp-content/plugins/s3-offloader

echo ""
echo "Installing test dependencies..."
docker-compose exec wordpress composer require --dev yoast/phpunit-polyfills:"^2.0" -d /var/www/html/wp-content/plugins/s3-offloader 2>&1 | grep -E "(Installing|Locking|Nothing to|already)" || true

_wp () {
    docker-compose exec -u www-data -T wordpress wp "$@"
}

echo ""
echo "Checking if WordPress is installed..."
if ! _wp core is-installed 2>/dev/null; then
    echo "Installing WordPress..."
    _wp core install \
        --url=localhost:8080 \
        --title="S3 Offloader Dev Site" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com
    echo "✓ WordPress installed"
else
    echo "✓ WordPress already installed"
fi

echo ""
echo "Activating S3 Offloader plugin..."
_wp plugin activate s3-offloader


AWS_ACCESS_KEY_ID="test"
AWS_SECRET_ACCESS_KEY="test"
AWS_DEFAULT_REGION="us-east-1"

S3_OFFLOADER_BUCKET="wordpress-media"

_aws () {
    docker-compose exec \
        -e AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID} \
        -e AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY} \
        -e AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION} \
        localstack aws --endpoint-url=http://localhost:4566 "$@"
}
_aws --endpoint-url=http://localhost:4566 s3 mb s3://${S3_OFFLOADER_BUCKET} --region ${AWS_DEFAULT_REGION}
_aws s3api put-bucket-acl --bucket wordpress-media --acl public-read

echo ""
echo "Configuring S3 Offloader for LocalStack..."
_wp option update s3_offloader_access_key "${AWS_ACCESS_KEY_ID}"
_wp option update s3_offloader_secret_key "${AWS_SECRET_ACCESS_KEY}"
_wp option update s3_offloader_bucket "${S3_OFFLOADER_BUCKET}"
_wp option update s3_offloader_region "${AWS_DEFAULT_REGION}"
_wp option update s3_offloader_endpoint "http://localstack:4566"
_wp option update s3_offloader_use_path_style "1"

echo ""
echo "Setting up test database..."
docker-compose exec db mysql -u root -prootpassword -e "CREATE DATABASE IF NOT EXISTS wordpress_test;" 2>/dev/null || echo "Note: Test database may already exist"
docker-compose exec db mysql -u root -prootpassword -e "GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wordpress'@'%'; FLUSH PRIVILEGES;" 2>/dev/null

echo ""
echo "Installing WordPress test suite..."
docker-compose exec wordpress bash /var/www/html/wp-content/plugins/s3-offloader/bin/install-wp-tests-docker.sh wordpress_test wordpress wordpress db latest

echo ""
echo "=========================================="
echo "✓ Development environment ready!"
echo "=========================================="
echo ""
echo "WordPress Admin: http://localhost:8080/wp-admin"
echo "  Username: admin"
echo "  Password: admin"
echo ""
echo "LocalStack S3: http://localhost:4566"
echo "  Bucket: ${S3_OFFLOADER_BUCKET}"
echo ""
echo "To run tests:"
echo "  docker-compose exec -w /var/www/html/wp-content/plugins/s3-offloader wordpress vendor/bin/phpunit"
echo ""
