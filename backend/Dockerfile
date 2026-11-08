# Image chạy backend Laravel trong lớp học.
# Máy giảng viên không cần cài PHP hay Composer: mọi thứ nằm trong image này.
FROM php:8.4-cli

# pdo_mysql: để PHP nói chuyện với MySQL. zip, git, unzip: để Composer tải thư viện.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
