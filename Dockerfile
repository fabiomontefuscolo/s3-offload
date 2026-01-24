FROM wordpress:latest

RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp \
    && groupmod -g1000 www-data \
    && usermod -g1000 -u1000 www-data

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
