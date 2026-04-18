#!/bin/bash

# ===============================================
# DEPLOYMENT SCRIPT - CubeTimer Production
# ===============================================
# Usage: ./deploy.sh
# ===============================================

set -e

echo "=========================================="
echo "🔧 CubeTimer Deployment"
echo "=========================================="

# Check .env exists
if [ ! -f ".env" ]; then
    echo "❌ Fichier .env manquant!"
    echo "   Copiez .env.production.example vers .env et complétez les valeurs"
    exit 1
fi

echo "📦 Construction des images Docker..."
docker compose -f docker-compose.prod.yml build --no-cache

echo "🗄️  Initialisation de la base de données..."
docker compose -f docker-compose.prod.yml run --rm app php artisan migrate --force

echo "🚀 Démarrage des services..."
docker compose -f docker-compose.prod.yml up -d

echo ""
echo "=========================================="
echo "✅ Deployment terminé!"
echo "=========================================="
echo ""
echo "🌐 Application: http://votre-serveur:80"
echo "📡 Websocket: ws://votre-serveur:80/ws"
echo ""
echo "Commandes utiles:"
echo "  docker compose -f docker-compose.prod.yml logs -f    # Voir les logs"
echo "  docker compose -f docker-compose.prod.yml down        # Arrêter"
echo "  docker compose -f docker-compose.prod.yml up -d       # Redémarrer"
echo "  docker compose -f docker-compose.prod.yml exec app sh # Shell dans le container"
echo ""