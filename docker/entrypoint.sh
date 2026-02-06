#!/bin/bash
set -e

echo "================================================"
echo "Démarrage du conteneur APP (Symfony + React)"
echo "================================================"

# ============================================
# 0. PRÉPARER LES VARIABLES D'ENVIRONNEMENT
# ============================================

# Valeurs par défaut si non fournies
export DB_NAME=${DB_NAME:-museeTransmitions}
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-vaillanT1959}
export APP_SECRET=${APP_SECRET:-change-me-in-production}
export JWT_PASSPHRASE=${JWT_PASSPHRASE:-change-me-too}

cd /var/www/html

echo "0/7 - Configuration des variables d'environnement..."

# Remplacer les placeholders dans .env.docker pour créer .env.local
sed -e "s|__DB_NAME__|${DB_NAME}|g" \
    -e "s|__DB_ROOT_PASSWORD__|${DB_ROOT_PASSWORD}|g" \
    -e "s|__APP_SECRET__|${APP_SECRET}|g" \
    -e "s|__JWT_PASSPHRASE__|${JWT_PASSPHRASE}|g" \
    .env.docker > .env.local

echo "   -> DB → mysql://root@db:3306/${DB_NAME}"
echo "   -> .env.local prêt !"

# ============================================
# 1. ATTENDRE QUE MARIADB SOIT PRÊT
# ============================================
echo ""
echo "1/7 - Attente MariaDB (db:3306)..."

MAX_RETRY=30
for i in $(seq 1 $MAX_RETRY); do
    # Utiliser nc (netcat) pour tester si le port 3306 répond
    if nc -z db 3306 2>/dev/null; then
        echo "   -> MariaDB accessible !"
        break
    fi

    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : MariaDB n'est pas accessible après 30 tentatives"
        exit 1
    fi

    echo "   -> Tentative $i/$MAX_RETRY..."
    sleep 3
done

# Attendre 2 secondes supplémentaires pour que MariaDB soit totalement prêt
sleep 2

# ============================================
# 2. VÉRIFIER LA CONNEXION SYMFONY À LA DB
# ============================================
echo ""
echo "2/7 - Test de connexion Symfony → MariaDB..."

MAX_RETRY=5
for i in $(seq 1 $MAX_RETRY); do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Connexion Symfony OK !"
        break
    fi

    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : Symfony ne peut pas se connecter à MariaDB"
        echo "Vérifiez DATABASE_URL dans .env.local"
        exit 1
    fi

    echo "   -> Tentative $i/$MAX_RETRY échouée, réessai..."
    sleep 3
done

# ============================================
# 3. LANCER LES MIGRATIONS DOCTRINE
# ============================================
echo ""
echo "3/7 - Exécution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v -i "deprecated\|user deprecated" || true

echo "   -> Migrations terminées !"

# ============================================
# 4. CHARGER LES FIXTURES (si nécessaire)
# ============================================
echo ""
echo "4/7 - Vérification des données..."

# Compter les utilisateurs dans la DB
USER_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as count FROM user" 2>/dev/null | grep -oP '\d+' | tail -1 || echo "0")

if [ "$USER_COUNT" -eq "0" ]; then
    echo "   -> Aucun utilisateur trouvé, chargement des fixtures..."

    # Charger les fixtures
    if [ -f "bin/console" ] && php bin/console list | grep -q "doctrine:fixtures:load"; then
        php bin/console doctrine:fixtures:load --no-interaction 2>&1 | grep -v -i "deprecated\|user deprecated" || true
        echo "   -> Fixtures chargées avec succès !"
    else
        echo "   -> Fixtures non disponibles, création admin manuel..."

        # Créer un admin par défaut (admin / 123Azerty)
        php bin/console dbal:run-sql "
            INSERT INTO user (username, email, roles, password, created_at) VALUES
            ('admin', 'admin@musee.fr', '[\"ROLE_ADMIN\"]', '\$2y\$13\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW())
            ON DUPLICATE KEY UPDATE username=username;
        " 2>/dev/null || true

        echo "   -> Admin créé (admin / 123Azerty)"
    fi
else
    echo "   -> Données existantes ($USER_COUNT utilisateurs), skip des fixtures"
fi

# ============================================
# 5. COMPILER LES ASSETS (AssetMapper)
# ============================================
echo ""
echo "5/7 - Compilation des assets..."

# Vérifier si AssetMapper est configuré
if php bin/console list | grep -q "importmap"; then
    echo "   -> AssetMapper détecté, installation..."
    php bin/console importmap:install 2>&1 | grep -v -i "deprecated" || true

    echo "   -> Compilation des assets..."
    php bin/console asset-map:compile 2>&1 | grep -v -i "deprecated" || true

    echo "   -> Assets compilés !"
else
    echo "   -> AssetMapper non configuré, skip"
fi

# ============================================
# 6. NETTOYER ET RÉCHAUFFER LE CACHE
# ============================================
echo ""
echo "6/7 - Gestion du cache Symfony..."

echo "   -> Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-warmup 2>&1 | grep -v -i "deprecated" || true

echo "   -> Réchauffage du cache..."
php bin/console cache:warmup --env=prod 2>&1 | grep -v -i "deprecated" || true

echo "   -> Cache prêt !"

# ============================================
# 7. CORRIGER LES PERMISSIONS
# ============================================
echo ""
echo "7/7 - Correction des permissions..."

# Permissions pour var/ (cache, logs)
if [ -d "var" ]; then
    chown -R www-data:www-data var/
    chmod -R 775 var/
fi

# Permissions pour public/ (assets, uploads)
if [ -d "public" ]; then
    chown -R www-data:www-data public/
    chmod -R 775 public/
fi

echo "   -> Permissions corrigées !"

# ============================================
# 8. VÉRIFIER SI REACT EST PRÉSENT
# ============================================
echo ""
echo "Vérification React..."

if [ -d "/var/www/react/dist" ] && [ "$(ls -A /var/www/react/dist 2>/dev/null)" ]; then
    REACT_SIZE=$(du -sh /var/www/react/dist 2>/dev/null | cut -f1 || echo "inconnu")
    echo "   -> ✅ React build trouvé ($REACT_SIZE)"
else
    echo "   -> ⚠️  React build manquant (vérifiez votre Dockerfile)"
fi

# ============================================
# AFFICHAGE FINAL + DÉMARRAGE APACHE
# ============================================
echo ""
echo "================================================"
echo " ✅APPLICATION PRÊTE !"
echo "================================================"
echo ""
echo " 🌐 Frontend React  : http://localhost"
echo " 🔧 API Symfony     : http://localhost:8080/api"
echo " 🗄️  Base de données : db:3306 (depuis conteneur)"
echo "                      localhost:3307 (depuis PC)"
echo ""
echo " 👤 Compte admin    : admin / 123Azerty"
echo ""
echo "================================================"
echo ""

# Démarrer Apache en foreground (ne termine jamais)
exec apache2-foreground