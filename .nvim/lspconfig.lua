return {
    intelephense = {
        config = {
            -- cmd = { "ncat", "127.0.0.1", "4567" },
            -- cmd = { "intelephense", "--stdio" },
            cmd = { "docker-compose", "run", "--rm", "-T", "-i", "intelephense" },
            filetypes = { "php" },
            settings = {
                intelephense = {
                    trace = {
                        server = "verbose",
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
                },
            },
        },
    },
}
