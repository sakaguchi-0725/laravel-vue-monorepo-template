FROM php:8.4-fpm AS base
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    gd \
    redis \
    pcntl \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# 開発環境用
FROM base AS dev

RUN install-php-extensions xdebug

# php.ini-development は opcache のタイムスタンプ検証が有効なので、
# ファイルを直せば再起動なしで反映される。
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

# 本番用
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'opcache.enable=1\nopcache.validate_timestamps=0\nopcache.memory_consumption=256\n' \
    > "$PHP_INI_DIR/conf.d/opcache.ini"

COPY --chown=www-data:www-data . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
