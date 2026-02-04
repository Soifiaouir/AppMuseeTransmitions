#!/bin/bash
set -e

echo "================================================"
echo "🚀 Démarrage du conteneur Docker"
echo "================================================"

# ============================================
# 1. DÉMARRER MARIADB
# ============================================
echo ""
echo "📦 1/5 - Démarrage de MariaDB..."

# Créer le dossier de données si nécessaire
mkdir -p /var/lib/mysql
chown -R mysql:mysql /var/lib/mysql

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   → Initialisation de MariaDB (première fois)..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Démarrer MariaDB en arrière-plan
mysqld_safe --user=mysql &

# ============================================
# 2. ATTENDRE QUE MARIADB SOIT PRÊTE
# ============================================
echo ""
echo "⏳ 2/5 - Attente de MariaDB..."

MAX_TRIES=30
COUNT=0

while ! mysqladmin ping --silent; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "❌ Erreur : MariaDB n'a pas démarré après 30 secondes"
        exit 1
    fi
    echo "   → Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "✅ MariaDB est prête !"

# ============================================
# 3. CRÉER LA BASE DE DONNÉES
# ============================================
echo ""
echo "🗄️  3/5 - Création de la base de données..."

mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS museeTransmitions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'localhost' IDENTIFIED BY 'vaillanT1959';
GRANT ALL PRIVILEGES ON museeTransmitions.* TO 'root'@'%' IDENTIFIED BY 'vaillanT1959';
FLUSH PRIVILEGES;
EOF

echo "✅ Base de données créée !"

# ============================================
# 4. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "🔄 4/5 - Exécution des migrations Doctrine..."

cd /var/www/html

# Attendre un peu pour être sûr que la BDD est accessible
sleep 2

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "✅ Migrations exécutées !"

# ============================================
# 5. DÉMARRER APACHE
# ============================================
echo ""
echo "🌐 5/5 - Démarrage d'Apache..."
echo ""
echo "================================================"
echo "✅ Application prête !"
echo "================================================"
echo "📱 React : http://localhost"
echo "🔌 API Symfony : http://localhost:8080/api"
echo "================================================"
echo ""

# Lancer Apache (cette commande ne rend pas la main)
exec apache2-foreground