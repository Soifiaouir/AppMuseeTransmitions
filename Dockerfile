FROM php:8.4-apache

ARG REACT_REPO_URL
ARG REACT_BRANCH=master
ARG VITE_API_USERNAME
ARG VITE_API_PASSWORD

# INSTALL TOOLS (avec netcat-openbsd ajouté)
RUN apt-get update && apt-get install -y \
    git unzip curl wget netcat-openbsd \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP EXTENSIONS
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl zip gd opcache

# PHP CONFIG
RUN echo "memory_limit=512M\nupload_max_filesize=500M\npost_max_size=500M\nmax_execution_time=300\nmax_input_time=300\ndate.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/custom.ini

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# APACHE
RUN a2enmod rewrite headers proxy proxy_http
COPY docker/apache/back.conf /etc/apache2/sites-available/back.conf
COPY docker/apache/front.conf /etc/apache2/sites-available/front.conf
RUN a2dissite 000-default.conf && a2ensite back.conf front.conf
RUN echo "Listen 80\nListen 8081" > /etc/apache2/ports.conf

# SYMFONY
WORKDIR /var/www/html
COPY composer.json composer.lock symfony.lock ./
RUN composer install --dev --optimize-autoloader --no-scripts --no-autoloader
COPY . /var/www/html
COPY .env.docker /var/www/html/.env.docker
RUN composer dump-autoload --optimize --classmap-authoritative

# PERMISSIONS
RUN mkdir -p var/cache var/log var/sessions public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 777 var \
    && chmod -R 775 public/uploads

# Assets Symfony (au build)
RUN php bin/console importmap:install || true \
    && php bin/console asset-map:compile || true \
    && chown -R www-data:www-data public/assets || true \
    && php bin/console cache:clear --env=prod --no-warmup || true \
    && php bin/console cache:warmup --env=prod || true

# REACT BUILD - AVEC SECRETS depuis ARG
RUN if [ -n "$REACT_REPO_URL" ]; then \
        echo "🚀 Clonage du repo React : $REACT_REPO_URL"; \
        git clone --branch ${REACT_BRANCH} --depth 1 ${REACT_REPO_URL} /tmp/react-app && \
        cd /tmp/react-app && \
        echo "🔐 Création du .env.prod avec secrets..."; \
        echo "VITE_API_URL=http://localhost:8081/api" > .env.prod && \
        echo "VITE_API_UPLOAD=http://localhost:8081/uploads/media" >> .env.prod && \
        echo "VITE_API_USERNAME=${VITE_API_USERNAME}" >> .env.prod && \
        echo "VITE_API_PASSWORD=${VITE_API_PASSWORD}" >> .env.prod && \
        echo "Contenu du .env.prod :"; \
        cat .env.prod; \
        echo "Installation npm..."; \
        npm install && \
        echo "Build React..."; \
        npm run build && \
        echo "Copie vers /var/www/react/dist/..."; \
        mkdir -p /var/www/react && \
        cp -r dist /var/www/react/ && \
        echo "React build terminé !"; \
        ls -la /var/www/react/dist/ && \
        cd / && rm -rf /tmp/react-app; \
    else \
        echo "REACT_REPO_URL non défini - Skip du build React"; \
        mkdir -p /var/www/react && echo "<h1>React non configuré</h1>" > /var/www/react/index.html; \
    fi

RUN chown -R www-data:www-data /var/www/react

# ENTRYPOINT
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 8081
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]