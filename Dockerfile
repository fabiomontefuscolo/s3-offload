FROM wordpress:latest

RUN apt-get update && apt-get install -y \
    curl \
    git \
    nodejs \
    npm \
    socat \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp \
    && curl -sSLo /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    && chmod +x /usr/local/bin/composer \
    && NPM_CONFIG_PREFIX="/usr/local" npm install -g intelephense \
    && COMPOSER_BIN_DIR=/usr/local/bin composer g require psy/psysh:@stable

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN groupmod -g ${USER_ID} www-data \
    && usermod -g ${GROUP_ID} -u ${USER_ID} www-data
