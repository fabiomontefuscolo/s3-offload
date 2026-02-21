FROM wordpress:latest

RUN apt-get update && apt-get install -y \
    curl \
    git \
    nodejs \
    npm \
    socat \
    subversion \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PCOV for code coverage
RUN pecl install pcov \
    && docker-php-ext-enable pcov \
    && echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/pcov.ini \
    && echo "pcov.directory=/var/www/html/wp-content/plugins/s3-offloader/src" >> /usr/local/etc/php/conf.d/pcov.ini

# Install Xdebug for PHP 8.3
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=xdbg" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && touch /usr/local/bin/xdebug \
    && chmod +x /usr/local/bin/xdebug \
    && cat > /usr/local/bin/xdebug << 'EOF'
#!/bin/bash
PHP="/usr/local/bin/php"

if [ -z "$XDEBUG_CLIENT_HOST" ];
then
    client_host=$(awk '{
            if($2 == "00000000" && $8 == "00000000") {
                printf "%d.", "0x" substr($3, 7, 2);
                printf "%d.", "0x" substr($3, 5, 2);
                printf "%d.", "0x" substr($3, 3, 2);
                printf "%d",  "0x" substr($3, 1, 2);
            }
        }' /proc/net/route)
else
    client_host="${XDEBUG_CLIENT_HOST}"
fi

inifile="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
extfile="$(find /usr/local/lib/php/extensions/ -name xdebug.so)";
client_port=$($PHP -r 'echo ini_get("xdebug.client_port");');
idekey=$($PHP -r 'echo ini_get("xdebug.idekey");');

$PHP \
    -d "zend_extension=${extfile}" \
    -d "xdebug.idekey=${idekey:-xdbg}" \
    -d "xdebug.mode=debug" \
    -d "xdebug.start_with_request=yes" \
    -d "xdebug.discover_client_host=true" \
    -d "xdebug.client_port=${client_port:-9003}" \
    -d "xdebug.client_host=${client_host:-172.17.0.1}" \
    -d "xdebug.remote_handler=dbgp" \
    "$@"
EOF

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp \
    && curl -sSLo /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    && chmod +x /usr/local/bin/composer \
    && NPM_CONFIG_PREFIX="/usr/local" npm install -g intelephense \
    && COMPOSER_BIN_DIR=/usr/local/bin composer g require psy/psysh:@stable \
    && groupmod -g ${USER_ID} www-data \
    && usermod -g ${GROUP_ID} -u ${USER_ID} www-data

