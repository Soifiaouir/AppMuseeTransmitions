#!/bin/bash
set -e

echo "================================================"
echo "Demarrage du conteneur Docker"
echo "================================================"

# ============================================
# 1. DEMARRER MARIADB
# ============================================
echo ""
echo "1/6 - Demarrage de MariaDB..."

# Creer le dossier de donnees si necessaire
mkdir -p /var/lib/mysql
chown -R mysql:mysql /var/lib/mysql

# Initialiser MariaDB si premiere fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB (premiere fois)..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Demarrer MariaDB en arriere-plan
mysqld_safe --user=mysql &

# ============================================
# 2. ATTENDRE QUE MARIADB SOIT PRETE
# ============================================
echo ""
echo "2/6 - Attente de MariaDB..."

MAX_TRIES=30
COUNT=0

while ! mysqladmin ping --silent; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "Erreur : MariaDB n'a pas demarre apres 30 secondes"
        exit 1
    fi
    echo "   -> Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "MariaDB est prete !"

# ============================================
# 3. CREER LA BASE DE DONNEES
# ============================================
echo ""
echo "3/6 - Creation de la base de donnees..."

mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS museeTransmitions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'localhost' IDENTIFIED BY 'vaillanT1959';
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'%' IDENTIFIED BY 'vaillanT1959';
FLUSH PRIVILEGES;
EOF

echo "Base de donnees creee !"

# ============================================
# 4. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "4/6 - Execution des migrations Doctrine..."

cd /var/www/html

# Attendre un peu pour etre sur que la BDD est accessible
sleep 2

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Migrations executees !"

# ============================================
# 5. CHARGER LES FIXTURES (SI NECESSAIRE)
# ============================================
echo ""
echo "5/6 - Verification des fixtures..."

# Verifier si la table user contient des donnees
USER_COUNT=$(mysql -u root -pvaillanT1959 museeTransmitions -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucune donnee trouvee, chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction
    echo "Fixtures chargees avec succes !"
else
    echo "   -> Donnees deja presentes ($USER_COUNT utilisateurs), fixtures ignorees"
fi

# ============================================
# 6. DEMARRER APACHE
# ============================================
echo ""
echo "6/6 - Demarrage d'Apache..."
echo ""
echo "================================================"
echo "Application prête !"
echo "================================================"
echo "React : http://localhost"
echo "API Symfony : http://localhost:8080/api"
echo "================================================"
echo ""

# Lancer Apache (cette commande ne rend pas la main)
exec apache2-foreground