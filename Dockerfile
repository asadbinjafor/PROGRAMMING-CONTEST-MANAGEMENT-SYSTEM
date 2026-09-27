FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker/apache-pcms.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /usr/local/bin/pcms-start
RUN chmod +x /usr/local/bin/pcms-start \
    && mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage

ENV PORT=10000
EXPOSE 10000
CMD ["pcms-start"]
