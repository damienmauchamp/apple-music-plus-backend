# AM+

Suit les nouvelles sorties Apple Music (albums, EP, singles, songs) pour une liste d'artistes par utilisateur.

**Stack :** Laravel 12 · PHP 8.4 · Laravel Sail (Docker) · SQLite (tests) / MySQL (prod)

## Prérequis

- Docker Desktop (ou Docker Engine + Compose)
- PHP 8.4 + Composer (pour les commandes hors Sail)
- Compte Apple Developer avec une clé MusicKit (ES256)

## Installation locale

```bash
git clone <repo> && cd <repo>
cp .env.example .env
composer install --no-interaction --prefer-dist
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Aliases recommandés (à ajouter dans `.zshrc` / `.bashrc`) :

```bash
alias sail='./vendor/bin/sail'
alias art='sail artisan'
alias scomp='sail composer'
```

## Variables d'environnement

Toutes les variables utiles sont dans `.env.example`. Les principales :

| Variable | Description | Exemple |
|---|---|---|
| `APP_KEY` | Clé AES-256 de l'app (`php artisan key:generate`) | `base64:...` |
| `APP_URL` | URL de base de l'API | `http://localhost` |
| `DB_*` | Connexion base de données | voir `.env.example` |
| `LOG_CHANNEL` | Canal de logs (`stack`) | `stack` |
| `LOG_LEVEL` | Niveau de log | `debug` |
| `APPLE_AUTH_KEY` | Clé privée EC P-256 PEM (MusicKit) | `-----BEGIN PRIVATE KEY-----\n...` |
| `APPLE_AUTH_KEY_FILE` | Chemin vers le fichier `.p8` (alternative à `APPLE_AUTH_KEY`) | `AuthKey_XXXXXXXX.p8` |
| `APPLE_TEAM_ID` | Team ID Apple Developer | `XXXXXXXXXX` |
| `APPLE_KEY_ID` | Key ID MusicKit | `XXXXXXXXXX` |
| `APPLE_STOREFRONT` | Storefront par défaut | `fr` |
| `AM_STOREFRONT_TIMEZONE` | Fuseau du storefront (calcul released/upcoming) | `Europe/Paris` |
| `RELEASE_DATA_RETENTION_DAYS` | Rétention des sorties (jours) | `90` |
| `JOB_DELAY` | Délai entre les jobs d'ingestion (ms) | `3000` |
| `RELEASE_WEEKDAY` | Jour de début de semaine de sorties (0=dim, 5=ven) | `5` |
| `NIGHTWATCH_*` | Configuration Laravel Nightwatch (monitoring) | voir `.env.example` |

> **Important :** la var `TIMEZONE` (ancienne) est abandonnée. Utiliser `APP_TIMEZONE` (défaut `UTC`) pour le stockage ; le fuseau du storefront est dans `AM_STOREFRONT_TIMEZONE`.

### Clé MusicKit (APPLE_AUTH_KEY)

Générer une clé de test (non reliée à Apple, pour le dev local / CI) :

```bash
openssl ecparam -name prime256v1 -genkey -noout | openssl pkcs8 -topk8 -nocrypt
```

Pour la prod, utiliser la vraie clé `.p8` téléchargée depuis [Apple Developer](https://developer.apple.com/account/resources/authkeys/list).

## CI/CD (GitHub Actions)

Le workflow `.github/workflows/ci.yml` s'exécute sur chaque push (hors `master`) et sur les PR vers `master`.

Étapes : **Pint** (style) → **Larastan** (analyse statique) → **Pest** (tests).

### Secrets GitHub requis

À configurer dans *Settings → Secrets and variables → Actions* du dépôt :

| Secret | Description |
|---|---|
| `APPLE_AUTH_KEY` | Clé privée EC P-256 au format PEM (peut être une clé de test générée avec la commande ci-dessus — pas besoin que ce soit la vraie clé Apple pour que les tests passent) |

Les variables `APPLE_TEAM_ID` et `APPLE_KEY_ID` utilisées en CI sont des valeurs fictives (`TESTTEAMID` / `TESTKEY01`) — les tests vérifient la mécanique de génération JWT, pas l'authentification Apple.

## Commandes utiles

```bash
# Mise à jour des sorties
art app:fetch-all-artists        # tous les artistes
art app:fetch-artist {storeId}   # un artiste

# Qualité
vendor/bin/pint                  # formatter le code
vendor/bin/phpstan analyse       # analyse statique
vendor/bin/pest                  # tests

# Queue
art queue:work --queue=update-artist
art queue:work --stop-when-empty
art queue:clear

# Scheduler (cron)
art schedule:run
art schedule:list
```

### Supervisor (prod)

Worker principal (`/etc/supervisor/conf.d/amplus-worker.conf`) :

```ini
[program:amplus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=low,default,high,update-artist --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Agent Nightwatch (`/etc/supervisor/conf.d/amplus-nightwatch.conf`) :

```ini
[program:amplus-nightwatch-agent]
process_name=%(program_name)s
command=php /path/to/artisan nightwatch:agent
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/nightwatch-agent.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start "amplus-worker:*"
sudo supervisorctl start amplus-nightwatch-agent
```

### Cron (scheduler)

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## iOS Shortcuts

### S'abonner à un artiste

https://www.icloud.com/shortcuts/1c4f2d2e1b1245deb0433dfbce78853e

Renseigner le token API et l'URL de base (`https://...`), puis depuis une page artiste dans l'app Music → Partager → "Follow artist".

## Voir aussi

- [Frontend (Next.js)](https://github.com/damienmauchamp/apple-music-plus-frontend)
