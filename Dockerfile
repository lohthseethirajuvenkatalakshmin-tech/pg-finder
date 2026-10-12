FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

COPY docker-entrypoint.sh /usr/local/bin/pg-finder-entrypoint
RUN chmod +x /usr/local/bin/pg-finder-entrypoint

COPY PayGuest/ /var/www/html/
COPY assets/ /var/www/html/assets/

EXPOSE 8080

ENTRYPOINT ["pg-finder-entrypoint"]
