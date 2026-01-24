#!/bin/bash

if [ ! -f .env ]; then
    cp .env.example .env
fi

docker-compose up --detach --wait

echo ""
echo "Waiting for services to be ready..."
sleep 10

echo ""
echo "Installing Composer dependencies..."
docker-compose exec wordpress composer install -d /var/www/html/wp-content/plugins/s3-offloader

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
_aws --endpoint-url=http://localhost:4566 s3 mb s3://${S3_OFFLOADER_BUCKET}

echo ""
echo "Configuring S3 Offloader for LocalStack..."
_wp option update s3_offloader_access_key "${AWS_ACCESS_KEY_ID}"
_wp option update s3_offloader_secret_key "${AWS_SECRET_ACCESS_KEY}"
_wp option update s3_offloader_bucket "${S3_OFFLOADER_BUCKET}"
_wp option update s3_offloader_region "${AWS_DEFAULT_REGION}"
_wp option update s3_offloader_endpoint "http://localstack:4566"
_wp option update s3_offloader_use_path_style "1"
