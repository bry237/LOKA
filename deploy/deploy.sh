#!/usr/bin/env bash
#
# deploy.sh — exécuté sur le serveur par GitHub Actions à chaque push sur
# `main` (voir .github/workflows/deploy.yml). Ne touche jamais aux
# fichiers uploadés par les utilisateurs (storage/, uploads/,
# public/uploads/...) : `git reset --hard` ne supprime que les fichiers
# SUIVIS par git, jamais les fichiers non suivis — ce script n'exécute
# volontairement aucun `git clean`.

set -euo pipefail

APP_DIR="/var/www/LOKA"
BRANCH="main"

cd "$APP_DIR"

echo "→ git fetch/reset sur origin/$BRANCH"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "→ composer install"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ vérification de syntaxe PHP avant mise en ligne"
# Le job "lint" du pipeline GitHub Actions a déjà fait ce contrôle avant
# même de lancer ce script, mais on revérifie ici sur le code réellement
# présent sur le serveur (ceinture + bretelles).
find . -name "*.php" -not -path "./vendor/*" -not -path "./.git/*" -print0 \
  | xargs -0 -n1 -P4 php -l > /tmp/loka-deploy-lint.log 2>&1 \
  || { echo "Échec du lint PHP, déploiement interrompu :"; cat /tmp/loka-deploy-lint.log; exit 1; }

echo "✓ Déployé : $(git rev-parse --short HEAD) — $(git log -1 --format=%s)"
