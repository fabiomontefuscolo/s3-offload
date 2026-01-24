return {
    intelephense = {
        config = {
            cmd = { "intelephense", "--stdio" },
            filetypes = { "php" },
            settings = {
                intelephense = {
                    files = {
                        maxSize = 5000000, -- 5 MB
                    },
                    -- stylua: ignore
                    stubs = {
                        "wordpress", "wordpress-globals", "wp-cli",
                        "woocommerce", "apache", "bcmath", "bz2",
                        "calendar", "Core", "curl", "date", "dom",
                        "filter", "fileinfo", "gd", "gettext", "hash",
                        "iconv", "imap", "intl", "json", "libxml",
                        "mbstring", "mcrypt", "mysqli", "openssl",
                        "pcre", "pdo", "pdo_mysql", "phar", "reflection",
                        "session", "simplexml", "soap", "sockets",
                        "sodium", "spl", "standard", "tokenizer", "xml",
                        "xmlreader", "xmlwriter", "xsl", "zip", "zlib",
                    },
                    -- stylua: ignore
                    environment = {
                        includePaths = {
                            "/home/fabio/.composer/vendor/php-stubs/wordpress-stubs",
                            "/home/fabio/.composer/vendor/php-stubs/wordpress-globals",
                            "/home/fabio/.composer/vendor/php-stubs/wp-cli-stubs",
                            "/home/fabio/.composer/vendor/php-stubs/woocommerce-stubs",
                            "/home/fabio/.composer/vendor/php-stubs/acf-pro-stubs"
                        }
                    },
                },
            },
        },
    },
}
