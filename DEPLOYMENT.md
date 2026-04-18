# Guide de Déploiement en Production - CubeTimer

## Résumé des Améliorations Appliquées

Cet audit a permis d'identifier et de corriger plusieurs points critiques dans l'application CubeTimer. Voici les améliorations apportées :

### 1. Sécurité Renforcée
- **Validation des entrées** : Amélioration de la validation dans `Timer::saveTime()` et `ScrambleController`
- **Protection contre les race conditions** : Utilisation de transactions avec verrouillage dans `DuelController::joinRoom()`
- **Politiques d'autorisation** : Création d'une policy `DuelRoomPolicy` pour contrôler l'accès aux salles de duel
- **Sécurisation du processus externe** : Ajout de timeout et validation stricte dans `ScrambleController`

### 2. Optimisations de Performance
- **Images Docker légères** : Utilisation d'Alpine Linux pour réduire la taille des images
- **Dépendances optimisées** : Séparation des dépendances de développement et de production
- **Build des assets en build** : Les assets frontend sont compilés lors de la construction de l'image

### 3. Corrections Logiques
- **Flux corrigé** : Dans `DuelController::submitTime()`, vérification de l'appartenance avant les autres contrôles
- **Élimination du code dupliqué** : Suppression des blocs de code dupliqués causés par une erreur d'édition

### 4. Configuration de Production Simplifiée

## Instructions de Déploiement

### Prérequis
- Docker et Docker Compose installés sur votre serveur
- Un nom de domaine configuré qui pointe vers votre serveur
- Accès SSH à votre serveur

### Étapes de Déploiement

1. **Cloner le dépôt sur votre serveur**
```bash
git clone <votre-repo-url>
cd CubeTimer
```

2. **Configurer les secrets de production**
```bash
# Créer le dossier des secrets
mkdir -p secrets

# Créer les fichiers de mots de passe (à remplacer par vos propres valeurs)
echo "votre_mot_de_passe_db" > secrets/db-password.txt
echo "votre_mot_de_passe_root" > secrets/root-password.txt

# Créer le fichier .env de production
cp .env.production_example .env
# Éditer .env pour configurer :
# - APP_URL=https://votre-domaine.com
# - DB_PASSWORD=votre_mot_de_passe_db
```

3. **Construire et déployer**
```bash
# Construire les images Docker
docker-compose -f docker-compose.prod.yml build --no-cache

# Démarrer les services
docker-compose -f docker-compose.prod.yml up -d
```

4. **Configurer Laravel (première fois seulement)**
```bash
# Générer la clé d'application
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate

# Exécuter les migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

5. **Configuration SSL (recommandé)**
Pour activer HTTPS, vous pouvez ajouter un reverse proxy comme Nginx Proxy Manager ou Traefik devant vos containers, ou utiliser le service `nginx` dans le docker-compose original.

### Maintenance

#### Commandes utiles
```bash
# Voir les logs
docker-compose -f docker-compose.prod.yml logs -f

# Redémarrer les services
docker-compose -f docker-compose.prod.yml restart

# Mettre à jour le code et redéployer
git pull
docker-compose -f docker-compose.prod.yml build --no-cache app
docker-compose -f docker-compose.prod.yml up -d app

# Nettoyer le système périodiquement
docker system prune -f
```

#### Sauvegarde de la base de données
```bash
# Sauvegarder
docker-compose -f docker-compose.prod.yml exec mysqldump -u cubetimer -p cubetimer > backup.sql

# Restaurer
cat backup.sql | docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u cubetimer -p cubetimer
```

## Structure des Fichiers Modifiés

### Fichiers Modifiés
- `app/Livewire/Timer.php` - Validation améliorée
- `app/Http/Controllers/Api/ScrambleController.php` - Sécurisation du générateur de mélanges
- `app/Http/Controllers/Api/DuelController.php` - Corrections de logique et prévention des race conditions
- `app/Policies/DuelRoomPolicy.php` - Nouvelle policy d'autorisation
- `Dockerfile.prod` - Dockerfile optimisé pour la production
- `docker-compose.prod.yml` - Configuration docker-compose simplifiée

### Nouveaux Fichiers
- `DEPLOYMENT.md` - Ce guide de déploiement

## Vérification Post-Déploiement

Après le déploiement, vérifiez que :

1. L'application répond sur votre domaine
2. Vous pouvez créer un compte et vous connecter
3. Vous pouvez démarrer un chronomètre et sauvegarder des temps
4. Vous pouvez créer et rejoindre une salle de duel
5. Les mélanges sont générés correctement pour tous les types de puzzles
6. Aucune erreur n'apparaît dans les logs (`docker-compose -f docker-compose.prod.yml logs`)

## Support

En cas de problème, consultez les logs avec :
```bash
docker-compose -f docker-compose.prod.yml logs
```

Pour les erreurs spécifiques à un service :
```bash
docker-compose -f docker-compose.prod.yml logs app
docker-compose -f docker-compose.prod.yml logs mysql
```