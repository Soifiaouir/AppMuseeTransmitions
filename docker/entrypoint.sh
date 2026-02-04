#!/bin/bash
set -e

echo "================================================"
echo "Demarrage du conteneur Docker"
echo "================================================"

# ============================================
# 1. DEMARRER MARIADB
# ============================================
echo ""
echo "1/7 - Demarrage de MariaDB..."

# Créer le dossier de données si nécessaire
mkdir -p /var/lib/mysql /var/run/mysqld
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld
chmod 777 /var/run/mysqld

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB (premiere fois)..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql --skip-test-db
fi

# Démarrer MariaDB en arrière-plan
echo "   -> Demarrage du daemon MariaDB..."
mysqld --user=mysql --datadir=/var/lib/mysql --skip-networking=0 &

# ============================================
# 2. ATTENDRE QUE MARIADB SOIT PRETE
# ============================================
echo ""
echo "2/7 - Attente de MariaDB..."

MAX_TRIES=60
COUNT=0

# Attendre que le socket soit créé
while [ ! -S /var/run/mysqld/mysqld.sock ] && [ $COUNT -lt $MAX_TRIES ]; do
    COUNT=$((COUNT + 1))
    echo "   -> Attente du socket MySQL ($COUNT/$MAX_TRIES)..."
    sleep 1
done

# Attendre que MariaDB accepte les connexions (ping)
COUNT=0
while ! mysqladmin ping -h localhost --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "ERREUR : MariaDB n'a pas demarre apres $MAX_TRIES secondes"
        exit 1
    fi
    echo "   -> Tentative ping $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "   -> MariaDB repond au ping !"

# ============================================
# 3. CREER LA BASE DE DONNEES
# ============================================
echo ""
echo "3/7 - Creation de la base de donnees..."

# D'abord, créer l'utilisateur root avec mot de passe
mysql -h localhost -u root << EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
ALTER USER 'root'@'%' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
FLUSH PRIVILEGES;
EOF

echo "   -> Mot de passe root configure !"

# Ensuite, créer la base de données AVEC le mot de passe
mysql -h localhost -u root -p${DB_ROOT_PASSWORD} << EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'%';
FLUSH PRIVILEGES;
EOF

echo "   -> Base de donnees '${DB_NAME}' creee !"
sleep 3

# ============================================
# 4. VERIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "4/7 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion avec une commande simple
MAX_TRIES=3
COUNT=0
while [ $COUNT -lt $MAX_TRIES ]; do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Connexion Symfony OK !"
        break
    fi
    COUNT=$((COUNT + 1))
    if [ $COUNT -lt $MAX_TRIES ]; then
        echo "   -> Tentative $COUNT/$MAX_TRIES echouee, nouvelle tentative..."
        sleep 5
    else
        echo "ERREUR : Impossible de connecter Symfony a MariaDB"
        echo "DATABASE_URL : ${DATABASE_URL}"
        exit 1
    fi
done

# ============================================
# 5. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "5/7 - Execution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "   -> Migrations executees !"

# ============================================
# 6. CHARGER LES FIXTURES (SI NECESSAIRE)
# ============================================
echo ""
echo "6/7 - Verification des fixtures..."

USER_COUNT=$(mysql -h localhost -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucune donnee trouvee, chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction
    echo "   -> Fixtures chargees avec succes !"
else
    echo "   -> Donnees deja presentes ($USER_COUNT utilisateurs)"
fi

# ============================================
# 7. DEMARRER APACHE
# ============================================
echo ""
echo "7/7 - Demarrage d'Apache..."
echo ""
echo "================================================"
echo " Application prete !"
echo "================================================"
echo ""
echo " Front React  : http://localhost"
echo " API Symfony  : http://localhost:8080"
echo ""
echo "================================================"
echo ""

# Lancer Apache
exec apache2-foreground