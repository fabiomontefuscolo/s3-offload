#!/bin/bash
# Convenience script to run PHPUnit tests

docker-compose exec -w /var/www/html/wp-content/plugins/s3-offloader wordpress vendor/bin/phpunit "$@"
