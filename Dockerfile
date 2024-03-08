# Self-contained test image: PHP 8.0 + Apache + MariaDB.
#   docker build -t ccscores . && docker run --rm ccscores
FROM php:8.0-apache

# Debian bullseye is end-of-life, so its packages live on archive.debian.org.
RUN sed -i 's#deb.debian.org#archive.debian.org#' /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY api /var/www/html/api
COPY install.sh docker/run.sh /opt/ccscores/
RUN ln -s /var/www/html/api /opt/ccscores/api && chmod +x /opt/ccscores/*.sh

ENTRYPOINT ["/opt/ccscores/run.sh"]
CMD ["test"]
