#!/bin/bash
# Run PHPUnit tests with code coverage

echo "Running tests with code coverage..."
echo ""

docker-compose exec -w /var/www/html/wp-content/plugins/s3-offloader wordpress vendor/bin/phpunit --coverage-text --coverage-html coverage-report "$@"

echo ""
echo "Coverage report generated!"
echo "HTML report: coverage-report/index.html"
echo "XML report: coverage.xml"
