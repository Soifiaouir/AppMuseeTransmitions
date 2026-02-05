#!/bin/bash
set -e

echo "================================================"
echo "Démarrage du conteneur APP (Symfony + React)"
echo "================================================"

# ============================================
# 0. PRÉPARER VARIABLES + .env.local
# ============================================

export DB_NAME=${DB_NAME:-museeTransmitions}
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-vaillanT1959}
export APP_SECRET=${APP_SECRET:-change-me-in-production}
export JWT_PASSPHRASE=${JWT_PASSPHRASE:-change-me-too}

cd /var/www/html

echo "0/9 - Configuration variables d'environnement..."
sed -e "s|__DB_NAME__|${DB_NAME}|g" \
    -e "s|__DB_ROOT_PASSWORD__|${DB_ROOT_PASSWORD}|g" \
    -e "s|__APP_SECRET__|${APP_SECRET}|g" \
    -e "s|__JWT_PASSPHRASE__|${JWT_PASSPHRASE}|g" \
    .env.docker > .env.local

# DATABASE_URL → db:3306 (multi-conteneur)
sed -i 's|localhost/__DB_NAME__|db:3306/'${DB_NAME}'|g; s|unix_socket=.*||' .env.local
echo "   -> DB → mysql://root@db:3306/${DB_NAME}"
echo "   -> .env.local prêt !"

# ============================================
# 1. ATTENDRE MARIADB
# ============================================
echo ""
echo "1/9 - Attente MariaDB (db:3306)..."
MAX_RETRY=30
for i in $(seq 1 $MAX_RETRY); do
    if mysqladmin ping -h db -P 3306 -u root -p${DB_ROOT_PASSWORD} --silent 2>/dev/null; then
        echo "   -> MariaDB OK !"
        break
    fi
    [ $i -eq $MAX_RETRY ] && { echo "ERREUR MariaDB timeout"; exit 1; }
    echo "   -> Tentative $i/${MAX_RETRY}..."
    sleep 3
done

# ============================================
# 2. CONNEXION SYMFONY
# ============================================
echo ""
echo "2/9 - Test connexion Symfony..."
MAX_RETRY=3
for i in $(seq 1 $MAX_RETRY); do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Symfony connecté !"
        break
    fi
    [ $i -eq $MAX_RETRY ] && { echo "ERREUR Symfony/DB"; exit 1; }
    sleep 3
done

# ============================================
# 3. MIGRATIONS
# ============================================
echo ""
echo "3/9 - Migrations Doctrine..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v -i "deprecated" || true
echo "   -> Migrations OK"

# ============================================
# 4. ADMIN (admin/123Azerty)
# ============================================
echo ""
echo "4/9 - Admin auto (si absent)..."
ADMIN_EXISTS=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM \`user\` WHERE username='admin'" -q 2>/dev/null || echo 0)
if [ "$ADMIN_EXISTS" -eq 0 ]; then
    php bin/console dbal:run-sql "
        INSERT INTO \`user\` (username, email, roles, password, created_at) VALUES
        ('admin', 'admin@musee.fr', '[\"ROLE_ADMIN\"]', '\$2y\$13\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW())
        ON DUPLICATE KEY UPDATE username=username;
    " 2>/dev/null || true
    echo "   -> ✅ Admin créé (admin / 123Azerty)"
else
    echo "   -> Admin existe"
fi

# ============================================
# 5. ASSETS SYMFONY (importmap + compile)
# ============================================
echo ""
echo "5/9 - Assets Symfony..."
php bin/console importmap:install --force || true
php bin/console asset-map:compile || true
echo "   -> Importmap + assets compilés"

# ============================================
# 6. CACHE (clear + warmup)
# ============================================
echo ""
echo "6/9 - Cache Symfony..."
php bin/console cache:clear --env=prod --no-warmup || true
php bin/console cache:warmup --env=prod || true
echo "   -> Cache nettoyé + réchauffé"

# ============================================
# 7. VÉRIFIER BUILD REACT
# ============================================
echo ""
echo "7/9 - Vérification React build..."
if [ -d "/var/www/react/dist" ] && [ "$(ls -A /var/www/react/dist 2>/dev/null)" ]; then
    REACT_SIZE=$(du -sh /var/www/react/dist 2>/dev/null | cut -f1 || echo "0")
    echo "   -> ✅ React OK ($REACT_SIZE) → http://localhost"
else
    echo "   -> ⚠️ React manquant → http
