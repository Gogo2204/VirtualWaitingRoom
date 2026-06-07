FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    msmtp \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-blocking

COPY . .
RUN composer dump-autoload --optimize

COPY docker/php/msmtp.conf /etc/msmtprc
RUN chmod 644 /etc/msmtprc \
    && ln -sf /usr/bin/msmtp /usr/sbin/sendmail \
    && echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/mail.ini

EXPOSE 9000
CMD ["php-fpm"]