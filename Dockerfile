FROM php:8.4-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        intl \
        zip \
        gd \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Configuration PHP pour Symfony
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/upload.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/upload.ini

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer d'abord (pour le cache Docker)
COPY composer.json composer.lock ./

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copier le reste des fichiers du projet
COPY . /var/www/html

# Finaliser l'installation de Composer
RUN composer dump-autoload --optimize --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html/var

# Port exposé
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]
