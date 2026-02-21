#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

WP_TESTS_DIR=${WP_TESTS_DIR-/var/www/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/var/www/html}
WP_VERSION=$(php -r "include '$WP_CORE_DIR/wp-includes/version.php'; echo \$wp_version;")
WP_TESTS_TAG="branches/${WP_VERSION%\-*}"

curl -sL https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php -o $WP_CORE_DIR/wp-content/db.php

if [ ! -d $WP_TESTS_DIR ]; then
    # set up testing suite
    mkdir -p $WP_TESTS_DIR
    rm -rf $WP_TESTS_DIR/{includes,data}
    svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
    svn export --quiet --ignore-externals https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
fi

cat > "${WP_TESTS_DIR}/wp-tests-config.php" <<PHP
<?php
define( 'ABSPATH', '${WP_CORE_DIR}/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'AUTH_KEY', '$(openssl rand -hex 16)' );
define( 'SECURE_AUTH_KEY', '$(openssl rand -hex 16)' );
define( 'LOGGED_IN_KEY', '$(openssl rand -hex 16)' );
define( 'NONCE_KEY', '$(openssl rand -hex 16)' );
define( 'AUTH_SALT', '$(openssl rand -hex 16)' );
define( 'SECURE_AUTH_SALT', '$(openssl rand -hex 16)' );
define( 'LOGGED_IN_SALT', '$(openssl rand -hex 16)' );
define( 'NONCE_SALT', '$(openssl rand -hex 16)' );
\$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
PHP

