# ============================================
# PARTIE 1 : IMAGE DE BASE
# ============================================
FROM php:8.4-apache

# Définir les arguments pour le repo React
ARG REACT_REPO_URL
ARG REACT_BRANCH=main

# ============================================
# PARTIE 2 : INSTALLATION DES OUTILS SYSTÈME
# ============================================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    wget \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    mariadb-server \
    mariadb-client \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ============================================
# PARTIE 3 : CONFIGURATION PHP
# ============================================

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        intl \
        zip \
        gd \
        opcache

# Copier la configuration PHP personnalisée
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================
# PARTIE 4 : CONFIGURATION APACHE
# ============================================

# Activer les modules Apache nécessaires
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod proxy \
    && a2enmod proxy_http

# Copier les VirtualHosts
COPY docker/apache/back.conf /etc/apache2/sites-available/back.conf
COPY docker/apache/front.conf /etc/apache2/sites-available/front.conf

# Désactiver le site par défaut et activer nos VirtualHosts
RUN a2dissite 000-default.conf \
    && a2ensite back.conf \
    && a2ensite front.conf

# Configurer Apache pour écouter sur les ports 80 et 8080
RUN echo "Listen 80" > /etc/apache2/ports.conf \
    && echo "Listen 8080" >> /etc/apache2/ports.conf

# ============================================
# PARTIE 5 : CONFIGURATION MARIADB
# ============================================

# Copier la configuration MariaDB
COPY docker/mariadb/my.cnf /etc/mysql/mariadb.conf.d/99-custom.cnf

# Créer les dossiers nécessaires
RUN mkdir -p /var/run/mysqld \
    && chown -R mysql:mysql /var/run/mysqld \
    && chmod 777 /var/run/mysqld

# ============================================
# PARTIE 6 : INSTALLATION DE SYMFONY
# ============================================

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de dépendances d'abord (cache Docker)
COPY composer.json composer.lock symfony.lock ./

# Installer les dépendances Composer (sans autoload pour le moment)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-autoloader

# Copier tout le code Symfony
COPY . /var/www/html

# Copier le fichier .env.docker et le renommer en .env.local
COPY .env.docker /var/www/html/.env.local

# Finaliser l'installation Composer
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Créer les dossiers Symfony et configurer les permissions
RUN mkdir -p var/cache var/log var/sessions public/uploads \
    && chown -R www-data:www-data /var/www/html/var \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/var \
    && chmod -R 775 /var/www/html/public/uploads

# ============================================
# PARTIE 7 : BUILD DU REACT
# ============================================

# Cloner le repo React
RUN if [ -n "$REACT_REPO_URL" ]; then \
        echo "📦 Clonage du repo React : $REACT_REPO_URL"; \
        git clone --branch ${REACT_BRANCH} --depth 1 ${REACT_REPO_URL} /tmp/react-app; \
        cd /tmp/react-app; \
        echo "📦 Installation des dépendances npm..."; \
        npm install; \
        echo "🔨 Build de React..."; \
        npm run build; \
        echo "📁 Copie du build React..."; \
        mkdir -p /var/www/react; \
        cp -r dist /var/www/react/; \
        echo "🧹 Nettoyage..."; \
        cd /; \
        rm -rf /tmp/react-app; \
        echo "✅ React buildé avec succès !"; \
    else \
        echo "⚠️  REACT_REPO_URL non défini - Skip du build React"; \
        mkdir -p /var/www/react/dist; \
        echo "<h1>React non configuré</h1>" > /var/www/react/dist/index.html; \
    fi

# Donner les permissions au dossier React
RUN chown -R www-data:www-data /var/www/react

# ============================================
# PARTIE 8 : SCRIPT DE DÉMARRAGE
# ============================================

# Copier et rendre exécutable le script d'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ============================================
# PARTIE 9 : CONFIGURATION FINALE
# ============================================

# Exposer les ports
EXPOSE 80 8080

# Point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]