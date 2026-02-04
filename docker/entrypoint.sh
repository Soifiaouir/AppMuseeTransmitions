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
MARIADB_PID=$!

# ============================================
# 2. ATTENDRE QUE MARIADB REPONDE AU PING
# ============================================
echo ""
echo "2/7 - Attente de MariaDB (ping)..."

MAX_TRIES=30
COUNT=0

while ! mysqladmin ping --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "ERREUR : MariaDB n'a pas demarre apres 30 secondes"
        exit 1
    fi
    echo "   -> Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "   -> MariaDB repond au ping !"

# ============================================
# 3. ATTENDRE QUE MARIADB ACCEPTE LES CONNEXIONS
# ============================================
echo ""
echo "3/7 - Attente que MariaDB accepte les connexions..."

COUNT=0
MAX_TRIES=30

while ! mysql -u root -e "SELECT 1" >/dev/null 2>&1; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "ERREUR : MariaDB n'accepte pas les connexions apres 30 secondes"
        exit 1
    fi
    echo "   -> Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "   -> MariaDB accepte les connexions !"

# Attendre encore 5 secondes pour etre vraiment sur
echo "   -> Attente de securite (5 secondes)..."
sleep 5

# ============================================
# 4. CREER LA BASE DE DONNEES
# ============================================
echo ""
echo "4/7 - Creation de la base de donnees..."

mysql -u root << 'EOF'
CREATE DATABASE IF NOT EXISTS museeTransmitions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'localhost' IDENTIFIED BY 'vaillanT1959';
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'%' IDENTIFIED BY 'vaillanT1959';
FLUSH PRIVILEGES;
EOF

echo "   -> Base de donnees creee !"

# Attendre encore 3 secondes apres la creation de la BDD
sleep 3

# ============================================
# 5. VERIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "5/7 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion avec une commande simple
if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
    echo "   -> Connexion Symfony OK !"
else
    echo "   -> Premiere tentative echouee, nouvelle tentative..."
    sleep 5
    if ! php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "ERREUR : Impossible de connecter Symfony a MariaDB"
        echo "Verifiez votre fichier .env.docker"
        exit 1
    fi
fi

# ============================================
# 6. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "6/7 - Execution des migrations Doctrine..."

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v "User Deprecated" || true

echo "   -> Migrations executees !"

# ============================================
# 7. CHARGER LES FIXTURES (SI NECESSAIRE)
# ============================================
echo ""
echo "7/7 - Verification des fixtures..."

# Verifier si la table user existe et contient des donnees
USER_COUNT=$(mysql -u root -pvaillanT1959 museeTransmitions -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucune donnee trouvee, chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction 2>&1 | grep -v "User Deprecated" || true
    echo "   -> Fixtures chargees avec succes !"
else
    echo "   -> Donnees deja presentes ($USER_COUNT utilisateurs)"
fi

# ============================================
# 8. DEMARRER APACHE
# ============================================
echo ""
echo "================================================"
echo "Demarrage d'Apache..."
echo "================================================"
echo ""
echo " Application prete !"
echo ""
echo " Front React  : http://localhost"
echo " API Symfony  : http://localhost:8080"
echo ""
echo "================================================"
echo ""

# Lancer Apache (cette commande ne rend pas la main)
exec apache2-foreground