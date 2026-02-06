#!/bin/bash
set -e

echo "================================================"
echo "Demarrage du conteneur APP (Symfony + React)"
echo "================================================"

# Preparer les variables d'environnement
export DB_NAME=${DB_NAME:-museeTransmitions}
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
export APP_SECRET=${APP_SECRET}
export JWT_PASSPHRASE=${JWT_PASSPHRASE}
export VITE_API_USERNAME=${VITE_API_USERNAME}
export VITE_API_PASSWORD=${VITE_API_PASSWORD}
export CORS_ALLOW_ORIGIN=${CORS_ALLOW_ORIGIN:-^https?://(localhost|127\\.0\\.0\\.1)(:[0-9]+)?$}

cd /var/www/html

echo "0/8 - Configuration des variables d'environnement..."

# Remplacer les placeholders dans .env.docker pour creer .env.local
sed -e "s|__DB_NAME__|${DB_NAME}|g" \
    -e "s|__DB_ROOT_PASSWORD__|${DB_ROOT_PASSWORD}|g" \
    -e "s|__APP_SECRET__|${APP_SECRET}|g" \
    -e "s|__JWT_PASSPHRASE__|${JWT_PASSPHRASE}|g" \
    .env.docker > .env.local

echo "   -> DB -> mysql://root@db:3306/${DB_NAME}"
echo "   -> APP_SECRET : ${APP_SECRET:0:8}..."
echo "   -> JWT_PASSPHRASE : ${JWT_PASSPHRASE:0:8}..."
echo "   -> VITE_API_USERNAME : ${VITE_API_USERNAME}"
echo "   -> CORS_ALLOW_ORIGIN : ${CORS_ALLOW_ORIGIN}"
echo "   -> .env.local pret !"

# Attendre que MariaDB soit pret
echo ""
echo "1/8 - Attente MariaDB (db:3306)..."

MAX_RETRY=30
for i in $(seq 1 $MAX_RETRY); do
    if nc -z db 3306 2>/dev/null; then
        echo "   -> MariaDB accessible !"
        break
    fi

    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : MariaDB n'est pas accessible apres 30 tentatives"
        exit 1
    fi

    echo "   -> Tentative $i/$MAX_RETRY..."
    sleep 3
done

sleep 2

# Verifier la connexion Symfony a la DB
echo ""
echo "2/8 - Test de connexion Symfony -> MariaDB..."

MAX_RETRY=5
for i in $(seq 1 $MAX_RETRY); do
    if php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; then
        echo "   -> Connexion Symfony OK !"
        break
    fi

    if [ $i -eq $MAX_RETRY ]; then
        echo "ERREUR : Symfony ne peut pas se connecter a MariaDB"
        echo "Verifiez DATABASE_URL dans .env.local"
        cat .env.local | grep DATABASE_URL
        exit 1
    fi

    echo "   -> Tentative $i/$MAX_RETRY echouee, reessai..."
    sleep 3
done

# Lancer les migrations Doctrine
echo ""
echo "3/8 - Execution des migrations Doctrine..."

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | grep -v -i "deprecated\|user deprecated" || true

echo "   -> Migrations terminees !"

# Valider le schema
echo ""
echo "4/8 - Validation du schema..."

if php bin/console doctrine:schema:validate 2>&1 | grep -q "OK"; then
    echo "   -> Schema valide !"
else
    echo "   -> ATTENTION : Schema non synchronise, mais on continue..."
fi

# Compiler les assets (AssetMapper)
echo ""
echo "5/8 - Compilation des assets..."

if php bin/console list | grep -q "importmap"; then
    echo "   -> AssetMapper detecte, installation..."
    php bin/console importmap:install 2>&1 | grep -v -i "deprecated" || true

    echo "   -> Compilation des assets..."
    php bin/console asset-map:compile  2>&1 | grep -v -i "deprecated" || true

    echo "   -> Assets compiles !"
else
    echo "   -> AssetMapper non configure, skip"
fi

# Nettoyer et rechauffer le cache
echo ""
echo "6/8 - Gestion du cache Symfony..."

echo "   -> Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-warmup 2>&1 | grep -v -i "deprecated" || true

echo "   -> Rechauffage du cache..."
php bin/console cache:warmup --env=prod 2>&1 | grep -v -i "deprecated" || true

echo "   -> Cache pret !"

# Corriger les permissions
echo ""
echo "7/8 - Correction des permissions..."

if [ -d "var" ]; then
    chown -R www-data:www-data var/
    chmod -R 775 var/
fi

if [ -d "public" ]; then
    chown -R www-data:www-data public/
    chmod -R 775 public/
fi

echo "   -> Permissions corrigees !"

# Verifier si React est present
echo ""
echo "8/8 - Verification React..."

if [ -d "/var/www/react/dist" ] && [ "$(ls -A /var/www/react/dist 2>/dev/null)" ]; then
    REACT_SIZE=$(du -sh /var/www/react/dist 2>/dev/null | cut -f1 || echo "inconnu")
    echo "   -> React build trouve ($REACT_SIZE)"
    ls -la /var/www/react/dist/ | head -10
else
    echo "   -> React build manquant (verifiez votre Dockerfile)"
fi

# Test de l'API Symfony
echo ""
echo "Test API Symfony..."

if php bin/console about 2>&1 | grep -q "Symfony"; then
    echo "   -> Symfony OK !"
else
    echo "   -> ATTENTION : Probleme avec Symfony"
fi

# Affichage final + demarrage Apache
echo ""
echo "================================================"
echo " APPLICATION PRETE !"
echo "================================================"
echo ""
echo " Frontend React  : http://localhost"
echo " API Symfony     : http://localhost:8081"
echo " Base de donnees : db:3306 (depuis conteneur)"
echo "                   localhost:3307 (depuis PC)"
echo ""
echo " Secrets charges :"
echo "    - APP_SECRET     : ${APP_SECRET:0:12}..."
echo "    - JWT_PASSPHRASE : ${JWT_PASSPHRASE:0:12}..."
echo "    - DB_PASSWORD    : ${DB_ROOT_PASSWORD:0:8}..."
echo "    - API_USERNAME   : ${VITE_API_USERNAME}"
echo "    - CORS_ORIGIN    : ${CORS_ALLOW_ORIGIN:0:40}..."
echo ""
echo "================================================"
echo ""
echo "Demarrage Apache..."

# Tester la config Apache avant de démarrer
if apachectl configtest 2>&1 | grep -q "Syntax OK"; then
    echo "   -> Configuration Apache OK"
else
    echo "   -> ERREUR : Configuration Apache invalide"
    apachectl configtest
    exit 1
fi

# Demarrer Apache en foreground
exec apache2-foreground