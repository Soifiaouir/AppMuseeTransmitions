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
echo "0/6 - Configuration des variables d'environnement..."
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
echo "1/6 - Demarrage de MariaDB..."

# Créer les dossiers
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

# Démarrer MariaDB en mode sûr (sans auth pour config initiale)
echo "   -> Demarrage de MariaDB en mode bootstrap..."
mysqld_safe --user=mysql --skip-grant-tables &
MARIADB_PID=$!

# Attendre le socket
echo "   -> Attente du socket MySQL..."
for i in {1..30}; do
    if [ -S /var/run/mysqld/mysqld.sock ]; then
        echo "   -> Socket cree !"
        break
    fi
    sleep 1
done

# Attendre que MariaDB réponde
echo "   -> Attente que MariaDB reponde..."
for i in {1..30}; do
    if mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo "   -> MariaDB pret !"
        break
    fi
    sleep 1
done

sleep 3

# ============================================
# 2. CONFIGURER LE MOT DE PASSE ROOT
# ============================================
echo ""
echo "2/6 - Configuration du mot de passe root..."

mysql -h localhost << EOF
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
CREATE USER IF NOT EXISTS 'root'@'%' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

echo "   -> Mot de passe configure !"

# Redémarrer MariaDB en mode normal
echo "   -> Redemarrage de MariaDB..."
kill $MARIADB_PID
wait $MARIADB_PID 2>/dev/null || true
sleep 2

mysqld_safe --user=mysql &
MARIADB_PID=$!

# Attendre redémarrage
for i in {1..30}; do
    if mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1" >/dev/null 2>&1; then
        echo "   -> MariaDB redemarre en mode normal !"
        break
    fi
    sleep 1
done

sleep 5

# ============================================
# 3. CREER LA BASE DE DONNEES
# ============================================
echo ""
echo "3/6 - Creation de la base de donnees..."

mysql -h localhost -u root -p${DB_ROOT_PASSWORD} << EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'%';
FLUSH PRIVILEGES;
EOF

echo "   -> Base '${DB_NAME}' creee !"
sleep 3

# ============================================
# 4. VERIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "4/6 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion
if ! php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
    echo "ERREUR : Symfony ne peut pas se connecter a MariaDB"
    echo "Verifiez les variables d'environnement"
    cat /var/www/html/.env.local | grep DATABASE_URL
    exit 1
fi

echo "   -> Connexion Symfony OK !"

# ============================================
# 5. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "5/6 - Execution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v "deprecated" || true

echo "   -> Migrations terminees !"

# ============================================
# 6. CHARGER LES FIXTURES (SI NECESSAIRE)
# ============================================
echo ""
echo "6/6 - Verification des fixtures..."

USER_COUNT=$(mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction 2>&1 | grep -v "deprecated" || true
    echo "   -> Fixtures chargees !"
else
    echo "   -> ${USER_COUNT} utilisateurs deja presents"
fi

# ============================================
# 7. DEMARRER APACHE
# ============================================
echo ""
echo "================================================"
echo " Application prete !"
echo "================================================"
echo " Front React  : http://localhost"
echo " API Symfony  : http://localhost:8080"
echo "================================================"
echo ""

exec apache2-foreground