#!/bin/bash
set -e

echo "================================================"
echo "Démarrage du conteneur Docker"
echo "================================================"

# ============================================
# 0. PRÉPARER LES VARIABLES D'ENVIRONNEMENT
# ============================================

# Valeurs par défaut si non fournies
export DB_NAME=${DB_NAME:-museeTransmitions}
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-root}
export APP_SECRET=${APP_SECRET:-change-me-in-production}
export JWT_PASSPHRASE=${JWT_PASSPHRASE:-change-me-too}

# Remplacer les placeholders dans .env.docker
echo "0/8 - Configuration des variables d'environnement..."
sed -e "s|__DB_NAME__|${DB_NAME}|g" \
    -e "s|__DB_ROOT_PASSWORD__|${DB_ROOT_PASSWORD}|g" \
    -e "s|__APP_SECRET__|${APP_SECRET}|g" \
    -e "s|__JWT_PASSPHRASE__|${JWT_PASSPHRASE}|g" \
    /var/www/html/.env.docker > /var/www/html/.env.local

echo "   -> Variables configurées !"

# ============================================
# 1. DÉMARRER MARIADB
# ============================================
echo ""
echo "1/8 - Démarrage de MariaDB..."

# Créer les dossiers
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

# Démarrer MariaDB temporairement pour la config
echo "   -> Démarrage temporaire pour configuration..."
mysqld --user=mysql --datadir=/var/lib/mysql --skip-networking --socket=/var/run/mysqld/mysqld.sock &
TEMP_MARIADB_PID=$!

# Attendre que le socket soit créé
echo "   -> Attente du socket..."
for i in {1..30}; do
    if [ -S /var/run/mysqld/mysqld.sock ]; then
        break
    fi
    sleep 1
done

# Attendre que MariaDB réponde
echo "   -> Attente de MariaDB..."
for i in {1..30}; do
    if mysqladmin ping --socket=/var/run/mysqld/mysqld.sock --silent 2>/dev/null; then
        echo "   -> MariaDB prêt !"
        break
    fi
    sleep 1
done

sleep 2

# ============================================
# 2. CONFIGURER LA BASE DE DONNÉES
# ============================================
echo ""
echo "2/8 - Configuration de la base de données..."

mysql --socket=/var/run/mysqld/mysqld.sock << EOF
-- Configurer le mot de passe root
ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'%';

FLUSH PRIVILEGES;
EOF

echo "   -> Configuration terminée !"
sleep 1

# Arrêter MariaDB temporaire
echo "   -> Arrêt du serveur temporaire..."
mysqladmin --socket=/var/run/mysqld/mysqld.sock -u root -p${DB_ROOT_PASSWORD} shutdown
wait $TEMP_MARIADB_PID 2>/dev/null || true

echo "   -> Serveur arrêté, attente de 3 secondes..."
sleep 3

# ============================================
# 3. DÉMARRER MARIADB EN MODE NORMAL
# ============================================
echo ""
echo "3/8 - Démarrage de MariaDB en mode production..."

# Démarrer MariaDB en mode normal
mysqld_safe --user=mysql &
MARIADB_PID=$!

# Attendre que MariaDB soit prêt
echo "   -> Attente du démarrage..."
for i in {1..60}; do
    if mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1" >/dev/null 2>&1; then
        echo "   -> MariaDB démarré en mode production !"
        break
    fi
    if [ $i -eq 60 ]; then
        echo "ERREUR : MariaDB n'a pas démarré après 60 secondes"
        exit 1
    fi
    sleep 1
done

sleep 3

# ============================================
# 4. VÉRIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "4/8 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion
MAX_RETRY=3
for i in $(seq 1 $MAX_RETRY); do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Connexion Symfony OK !"
        break
    fi
    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : Symfony ne peut pas se connecter à MariaDB"
        exit 1
    fi
    echo "   -> Tentative $i/$MAX_RETRY échouée..."
    sleep 3
done

# ============================================
# 5. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "5/8 - Exécution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v -i "deprecated\|user deprecated" || true

echo "   -> Migrations terminées !"

# ============================================
# 6. CHARGER LES FIXTURES
# ============================================
echo ""
echo "6/8 - Chargement des fixtures..."

# Vérifier si des données existent déjà
TABLES_COUNT=$(mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SHOW TABLES;" 2>/dev/null | wc -l || echo "0")
USER_COUNT=0

if [ "$TABLES_COUNT" -gt "0" ]; then
    USER_COUNT=$(mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")
fi

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucune donnée trouvée, chargement des fixtures..."

    # Charger les fixtures (avec --no-interaction pour éviter la confirmation)
    php bin/console doctrine:fixtures:load --no-interaction 2>&1 | grep -v -i "deprecated\|user deprecated" || true

    echo "   -> Fixtures chargées avec succès !"
else
    echo "   -> Données déjà présentes ($USER_COUNT utilisateurs), skip des fixtures"
fi

# ============================================
# 7. COMPILER LES ASSETS (CSS/JS)
# ============================================
echo ""
echo "7/8 - Compilation des assets..."

# Vérifier si AssetMapper est configuré
if php bin/console list | grep -q "importmap"; then
    echo "   -> AssetMapper détecté, compilation..."
    php bin/console importmap:install || true
    php bin/console asset-map:compile || true
    echo "   -> Assets compilés !"
else
    echo "   -> Pas d'AssetMapper configuré, skip"
fi

# ============================================
# 8. CORRIGER LES PERMISSIONS
# ============================================
echo ""
echo "8/8 - Correction des permissions..."

# S'assurer que www-data possède tout
chown -R www-data:www-data /var/www/html/var
chmod -R 777 /var/www/html/var

# Permissions pour les assets
chown -R www-data:www-data /var/www/html/public
chmod -R 775 /var/www/html/public

echo "   -> Permissions corrigées !"

# ============================================
# 9. DÉMARRER APACHE
# ============================================
echo ""
echo "================================================"
echo " ✅ Application prête !"
echo "================================================"
echo ""
echo " 🌐 Front React  : http://localhost"
echo " 🔧 API Symfony  : http://localhost:8080"
echo ""
echo " 👤 Admin créé via fixtures"
echo ""
echo "================================================"
echo ""

exec apache2-foreground