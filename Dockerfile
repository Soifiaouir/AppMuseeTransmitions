FROM php:8.4-apache

ARG REACT_REPO_URL
ARG REACT_BRANCH=master

# INSTALL TOOLS (sans mariadb-server/client)
RUN apt-get update && apt-get install -y \
    git unzip curl wget libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP EXTENSIONS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl zip gd opcache

# PHP CONFIG (inchangé)
RUN echo "memory_limit=512M\nupload_max_filesize=500M\npost_max_size=500M\nmax_execution_time=300\nmax_input_time=300\ndate.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/custom.ini

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# APACHE (inchangé)
RUN a2enmod rewrite headers proxy proxy_http
COPY docker/apache/back.conf /etc/apache2/sites-available/back.conf
COPY docker/apache/front.conf /etc/apache2/sites-available/front.conf
RUN a2dissite 000-default.conf && a2ensite back.conf front.conf
RUN echo "Listen 80\nListen 8080" > /etc/apache2/ports.conf

# SYMFONY
WORKDIR /var/www/html
COPY composer.json composer.lock symfony.lock ./
RUN composer install --dev --optimize-autoloader --no-scripts --no-autoloader
COPY . /var/www/html
COPY .env.docker /var/www/html/.env.docker
RUN composer dump-autoload --optimize --classmap-authoritative

# Permissions
RUN mkdir -p var/cache var/log var/sessions public/uploads \
    && chown -R www-data:www-data /var/www/html/{var,public/uploads} \
    && chmod -R 777 /var/www/html/var && chmod -R 775 /var/www/html/public/uploads

# Assets Symfony
RUN php bin/console importmap:install || true \
    && php bin/console asset-map:compile || true \
    && chown -R www-data:www-data /var/www/html/public/assets || true \
    && php bin/console cache:clear --env=prod --no-warmup || true \
    && php bin/console cache:warmup --env=prod || true

# REACT BUILD (inchangé)
RUN if [ -n "$REACT_REPO_URL" ]; then \
        git clone --branch ${REACT_BRANCH} --depth 1 ${REACT_REPO_URL} /tmp/react-app && \
        cd /tmp/react-app && npm install && npm run build && \
        mkdir -p /var/www/react && cp -r dist /var/www/react/ && \
        cd / && rm -rf /tmp/react-app; \
    else \
        mkdir -p /var/www/react/dist && echo "<h1>React non configuré</h1>" > /var/www/react/dist/index.html; \
    fi
RUN chown -R www-data:www-data /var/www/react

# ENTRYPOINT SIMPLIFIÉ
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 8080
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
