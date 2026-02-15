#!/usr/bin/env bash
# Install WordPress test suite for Docker environments (without svn dependency)

set -e

DB_NAME=${1-wordpress_test}
DB_USER=${2-wordpress}
DB_PASS=${3-wordpress}
DB_HOST=${4-db}
WP_VERSION=${5-latest}

TMPDIR=${TMPDIR-/tmp}
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
    if command -v curl > /dev/null; then
        curl -s "$1" > "$2"
    else
        echo "Error: curl is not installed."
        exit 1
    fi
}

echo "Installing WordPress test suite..."

# Determine WordPress version tag
if [[ $WP_VERSION == 'latest' ]]; then
    download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
    LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//' | head -1)
    if [[ -z "$LATEST_VERSION" ]]; then
        echo "Latest WordPress version could not be found"
        exit 1
    fi
    WP_TESTS_TAG="tags/$LATEST_VERSION"
    WP_VERSION=$LATEST_VERSION
elif [[ $WP_VERSION == 'trunk' || $WP_VERSION == 'nightly' ]]; then
    WP_TESTS_TAG="trunk"
else
    WP_TESTS_TAG="tags/$WP_VERSION"
fi

echo "Using WordPress version: $WP_VERSION (test tag: $WP_TESTS_TAG)"

# Install WordPress core
if [ ! -d "$WP_CORE_DIR" ]; then
    echo "Downloading WordPress..."
    mkdir -p "$WP_CORE_DIR"
    if [[ $WP_VERSION == 'latest' ]]; then
        ARCHIVE_NAME='latest'
    else
        ARCHIVE_NAME="wordpress-$WP_VERSION"
    fi
    download "https://wordpress.org/${ARCHIVE_NAME}.tar.gz" "$TMPDIR/wordpress.tar.gz"
    tar --strip-components=1 -zxf "$TMPDIR/wordpress.tar.gz" -C "$WP_CORE_DIR"
    download https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php "$WP_CORE_DIR/wp-content/db.php"
    echo "✓ WordPress core installed"
else
    echo "✓ WordPress core already exists"
fi

# Install test suite
if [ ! -d "$WP_TESTS_DIR/includes" ] || [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo "Downloading WordPress test suite..."
    mkdir -p "$WP_TESTS_DIR/includes"
    mkdir -p "$WP_TESTS_DIR/data"

    # Download test suite files using curl (no svn required)
    BASE_URL="https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit"

    # Core test files
    download "${BASE_URL}/includes/functions.php" "$WP_TESTS_DIR/includes/functions.php"
    download "${BASE_URL}/includes/install.php" "$WP_TESTS_DIR/includes/install.php"
    download "${BASE_URL}/includes/bootstrap.php" "$WP_TESTS_DIR/includes/bootstrap.php"
    download "${BASE_URL}/includes/listener.php" "$WP_TESTS_DIR/includes/listener.php"
    download "${BASE_URL}/includes/factory.php" "$WP_TESTS_DIR/includes/factory.php"
    download "${BASE_URL}/includes/testcase.php" "$WP_TESTS_DIR/includes/testcase.php"
    download "${BASE_URL}/includes/exceptions.php" "$WP_TESTS_DIR/includes/exceptions.php"
    download "${BASE_URL}/includes/utils.php" "$WP_TESTS_DIR/includes/utils.php"

    # Additional test case types
    download "${BASE_URL}/includes/testcase-rest-api.php" "$WP_TESTS_DIR/includes/testcase-rest-api.php"
    download "${BASE_URL}/includes/testcase-rest-controller.php" "$WP_TESTS_DIR/includes/testcase-rest-controller.php"
    download "${BASE_URL}/includes/testcase-rest-post-type-controller.php" "$WP_TESTS_DIR/includes/testcase-rest-post-type-controller.php"
    download "${BASE_URL}/includes/testcase-xmlrpc.php" "$WP_TESTS_DIR/includes/testcase-xmlrpc.php"
    download "${BASE_URL}/includes/testcase-ajax.php" "$WP_TESTS_DIR/includes/testcase-ajax.php"
    download "${BASE_URL}/includes/testcase-canonical.php" "$WP_TESTS_DIR/includes/testcase-canonical.php"

    # Mock and helper classes
    download "${BASE_URL}/includes/spy-rest-server.php" "$WP_TESTS_DIR/includes/spy-rest-server.php"
    download "${BASE_URL}/includes/mock-image-editor.php" "$WP_TESTS_DIR/includes/mock-image-editor.php"
    download "${BASE_URL}/includes/mock-mailer.php" "$WP_TESTS_DIR/includes/mock-mailer.php"
    download "${BASE_URL}/includes/mock-fs.php" "$WP_TESTS_DIR/includes/mock-fs.php"

    # Factory classes
    mkdir -p "$WP_TESTS_DIR/includes/factory"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-thing.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-thing.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-post.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-post.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-attachment.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-attachment.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-user.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-user.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-comment.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-comment.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-term.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-term.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-bookmark.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-bookmark.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-blog.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-blog.php"
    download "${BASE_URL}/includes/factory/class-wp-unittest-factory-for-network.php" "$WP_TESTS_DIR/includes/factory/class-wp-unittest-factory-for-network.php"

    # Additional helper classes
    download "${BASE_URL}/includes/class-basic-object.php" "$WP_TESTS_DIR/includes/class-basic-object.php"
    download "${BASE_URL}/includes/class-basic-subclass.php" "$WP_TESTS_DIR/includes/class-basic-subclass.php"
    download "${BASE_URL}/includes/class-wp-fake-block-type.php" "$WP_TESTS_DIR/includes/class-wp-fake-block-type.php"

    echo "✓ Test suite files downloaded"
else
    echo "✓ Test suite already exists"
fi

# Configure test suite (always regenerate to ensure correct settings)
echo "Configuring wp-tests-config.php..."
download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"

# Determine sed option for in-place editing
if [[ $(uname -s) == 'Darwin' ]]; then
    ioption='-i .bak'
else
    ioption='-i'
fi

# Configure database settings
sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
sed $ioption "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"

echo "✓ Test configuration created"

echo ""
echo "Note: Test database should be created separately (handled by setup-dev.sh)"
echo ""
echo "WordPress test suite installation complete!"
echo "Test library: $WP_TESTS_DIR"
echo "WordPress core: $WP_CORE_DIR"
echo "Database: $DB_NAME"
echo ""
