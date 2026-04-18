#!/bin/bash

# ===============================================
# SETUP PRODUCTION - CubeTimer
# ===============================================

set -e

echo "=========================================="
echo "⚙️  Setup Production CubeTimer"
echo "=========================================="

if [ ! -f ".env" ]; then
    echo "📄 Création du fichier .env..."
    cp .env.production_example .env
    echo "❌ Veuillez modifier .env avec:"
    echo "   - APP_URL=votre-domaine.com"
    echo "   - DB_PASSWORD=motdepasse_fort"
    echo "   - DB_ROOT_PASSWORD=motdepasse_root"
    echo "   Puis relancez ce script"
    exit 1
fi

echo "🔨 Build des images..."
docker compose -f docker-compose.prod.yml build --no-cache

echo "🔑 Génération clés Laravel..."
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --force

echo "📡 Configuration Reverb..."
docker compose -f docker-compose.prod.yml run --rm app php artisan reverb:install --append

echo "🚀 Démarrage des services..."
docker compose -f docker-compose.prod.yml up -d

echo "⏳ Attente de la base de données (10s)..."
sleep 10

echo "🗄️  Migration..."
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

echo ""
echo "=========================================="
echo "✅ Setup terminé!"
echo "=========================================="
echo ""
echo "🌐 Votre app est sur http://localhost:80"
echo ""
echo "⚠️  Configurez Cloudflare:"
echo "   - Enregistrement A vers votre IP"
echo "   - Proxy activé (icône orange)"
echo ""