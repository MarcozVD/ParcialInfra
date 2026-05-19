FROM php:8.2-apache

LABEL maintainer="EVA Infraestructura Tecnologica"

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxslt1-dev \
    postgresql-client \
    unzip \
    curl \
    cron \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        xml \
        zip \
        intl \
        gd \
        mbstring \
        soap \
        opcache \
        exif \
        xsl

RUN a2enmod rewrite

RUN { \
    echo "max_execution_time = 360"; \
    echo "max_input_vars = 5000"; \
    echo "memory_limit = 256M"; \
    echo "upload_max_filesize = 50M"; \
    echo "post_max_size = 50M"; \
    echo "date.timezone = America/Bogota"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
} > /usr/local/etc/php/conf.d/moodle.ini

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/moodle.conf \
    && a2enconf moodle

RUN curl -L https://github.com/moodle/moodle/archive/refs/tags/v5.1.4.tar.gz \
    | tar xz -C /var/www/html --strip-components=1 \
    && chown -R www-data:www-data /var/www/html

RUN mkdir -p /var/moodledata && chown www-data:www-data /var/moodledata

VOLUME ["/var/moodledata"]
EXPOSE 80

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
