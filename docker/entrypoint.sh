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
mkdir -p /var/lib/mysql
chown -R mysql:mysql /var/lib/mysql

# Initialiser MariaDB si première fois
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "   -> Initialisation de MariaDB (première fois)..."
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

# Démarrer MariaDB en arrière-plan
mysqld_safe --user=mysql &
MARIADB_PID=$!

# ============================================
# 2. ATTENDRE QUE MARIADB RÉPONDE AU PING
# ============================================
echo ""
echo "2/7 - Attente de MariaDB (ping)..."

MAX_TRIES=30
COUNT=0

while ! mysqladmin ping --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -gt $MAX_TRIES ]; then
        echo "ERREUR : MariaDB n'a pas démarré après 30 secondes"
        exit 1
    fi
    echo "   -> Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "   -> MariaDB répond au ping !"

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
        echo "ERREUR : MariaDB n'accepte pas les connexions après 30 secondes"
        exit 1
    fi
    echo "   -> Tentative $COUNT/$MAX_TRIES..."
    sleep 1
done

echo "   -> MariaDB accepte les connexions !"

# Attendre encore 5 secondes pour être vraiment sûr
echo "   -> Attente de sécurité (5 secondes)..."
sleep 5

# ============================================
# 4. CRÉER LA BASE DE DONNÉES AVEC LES VARIABLES D'ENVIRONNEMENT
# ============================================
echo ""
echo "4/7 - Création de la base de données..."

# Utiliser les variables d'environnement au lieu de valeurs en dur
mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'root'@'%' IDENTIFIED BY '${DB_ROOT_PASSWORD}';
FLUSH PRIVILEGES;
EOF

echo "   -> Base de données '${DB_NAME}' créée !"

# Attendre encore 3 secondes après la création de la BDD
sleep 3

# ============================================
# 5. VÉRIFIER LA CONNEXION SYMFONY
# ============================================
echo ""
echo "5/7 - Test de connexion Symfony..."

cd /var/www/html

# Tester la connexion avec une commande simple
if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
    echo "   -> Connexion Symfony OK !"
else
    echo "   -> Première tentative échouée, nouvelle tentative..."
    sleep 5
    if ! php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "ERREUR : Impossible de connecter Symfony à MariaDB"
        echo "DATABASE_URL actuel : ${DATABASE_URL}"
        exit 1
    fi
fi

# ============================================
# 6. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "6/7 - Exécution des migrations Doctrine..."

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v "User Deprecated" || true

echo "   -> Migrations exécutées !"

# ============================================
# 7. CHARGER LES FIXTURES (SI NÉCESSAIRE)
# ============================================
echo ""
echo "7/7 - Vérification des fixtures..."

# Vérifier si la table user existe et contient des données
USER_COUNT=$(mysql -u root -p${DB_ROOT_PASSWORD} ${DB_NAME} -sNe "SELECT COUNT(*) FROM user;" 2>/dev/null || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucune donnée trouvée, chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction 2>&1 | grep -v "User Deprecated" || true
    echo "   -> Fixtures chargées avec succès !"
else
    echo "   -> Données déjà présentes ($USER_COUNT utilisateurs)"
fi

# ============================================
# 8. DÉMARRER APACHE
# ============================================
echo ""
echo "================================================"
echo "Démarrage d'Apache..."
echo "================================================"
echo ""
echo " ✅ Application prête !"
echo ""
echo " Front React  : http://localhost"
echo " API Symfony  : http://localhost:8080"
echo ""
echo "================================================"
echo ""

# Lancer Apache (cette commande ne rend pas la main)
exec apache2-foreground
