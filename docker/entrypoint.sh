#!/bin/bash
set -e

echo "================================================"
echo "Demarrage du conteneur Docker"
echo "================================================"

# ============================================
# 0. PREPARER LES VARIABLES D'ENVIRONNEMENT
# ============================================

# Valeurs par défaut si non fournies
export DB_NAME=${DB_NAME:-museeTransmitions}
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-root}
export APP_SECRET=${APP_SECRET:-change-me-in-production}
export JWT_PASSPHRASE=${JWT_PASSPHRASE:-change-me-too}

# Remplacer les placeholders dans .env.docker
echo "0/7 - Configuration des variables d'environnement..."
sed -e "s|__DB_NAME__|${DB_NAME}|g" \
    -e "s|__DB_ROOT_PASSWORD__|${DB_ROOT_PASSWORD}|g" \
    -e "s|__APP_SECRET__|${APP_SECRET}|g" \
    -e "s|__JWT_PASSPHRASE__|${JWT_PASSPHRASE}|g" \
    /var/www/html/.env.docker > /var/www/html/.env.local

echo "   -> Variables configurees !"

# ============================================
# 1. DEMARRER MARIADB
# ============================================
echo ""
echo "1/7 - Demarrage de MariaDB..."

# Créer les dossiers
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

# Démarrer MariaDB temporairement pour la config
echo "   -> Demarrage temporaire pour configuration..."
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
        echo "   -> MariaDB pret !"
        break
    fi
    sleep 1
done

sleep 2

# ============================================
# 2. CONFIGURER LA BASE DE DONNEES
# ============================================
echo ""
echo "2/7 - Configuration de la base de donnees..."

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

echo "   -> Configuration terminee !"
sleep 1

# Arrêter MariaDB temporaire
echo "   -> Arret du serveur temporaire..."
mysqladmin --socket=/var/run/mysqld/mysqld.sock -u root -p${DB_ROOT_PASSWORD} shutdown
wait $TEMP_MARIADB_PID 2>/dev/null || true

echo "   -> Serveur arrete, attente de 3 secondes..."
sleep 3

# ============================================
# 3. DEMARRER MARIADB EN MODE NORMAL
# ============================================
echo ""
echo "3/7 - Demarrage de MariaDB en mode production..."

# Démarrer MariaDB en mode normal
mysqld_safe --user=mysql &
MARIADB_PID=$!

# Attendre que MariaDB soit prêt
echo "   -> Attente du demarrage..."
for i in {1..60}; do
    if mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1" >/dev/null 2>&1; then
        echo "   -> MariaDB demarre en mode production !"
        break
    fi
    if [ $i -eq 60 ]; then
        echo "ERREUR : MariaDB n'a pas demarre apres 60 secondes"
        exit 1
    fi
    sleep 1
done

sleep 3

# ============================================
# 4. VERIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "4/7 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion
MAX_RETRY=3
for i in $(seq 1 $MAX_RETRY); do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Connexion Symfony OK !"
        break
    fi
    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : Symfony ne peut pas se connecter a MariaDB"
        exit 1
    fi
    echo "   -> Tentative $i/$MAX_RETRY echouee..."
    sleep 3
done

# ============================================
# 5. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "5/7 - Execution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v -i "deprecated\|user deprecated" || true

echo "   -> Migrations terminees !"

# ============================================
# 6. CREER L'UTILISATEUR ADMIN
# ============================================
echo ""
echo "6/7 - Creation de l'utilisateur admin..."

# Vérifier si l'admin existe déjà
ADMIN_EXISTS=$(mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SELECT COUNT(*) FROM user WHERE username='admin';" 2>/dev/null || echo "0")

if [ "$ADMIN_EXISTS" -eq "0" ]; then
    echo "   -> Creation de l'utilisateur admin..."

    # Générer le hash du mot de passe "admin" avec Symfony
    ADMIN_PASSWORD_HASH=$(php bin/console security:hash-password admin --quiet | tail -1 | awk '{print $NF}')

    # Insérer l'admin en base
    mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} << EOF
INSERT INTO user (username, password, roles, password_change, password_change_date)
VALUES ('admin', '${ADMIN_PASSWORD_HASH}', '["ROLE_ADMIN"]', 0, NOW());
EOF

    echo "   -> Utilisateur admin cree (username: admin, password: admin)"
else
    echo "   -> Utilisateur admin deja present"
fi

# ============================================
# 7. CORRIGER LES PERMISSIONS
# ============================================
echo ""
echo "7/7 - Correction des permissions..."

# S'assurer que www-data possède tout
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "   -> Permissions corrigees !"

# ============================================
# 8. DEMARRER APACHE
# ============================================
echo ""
echo "================================================"
echo " Application prete !"
echo "================================================"
echo ""
echo " Front React  : http://localhost"
echo " API Symfony  : http://localhost:8080"
echo ""
echo " Utilisateur admin : admin / admin"
echo ""
echo "================================================"
echo ""

exec apache2-foreground