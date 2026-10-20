#!/usr/bin/env bash
#
# provision.sh — one-time server setup for LOKA.
#
# Run ONCE, by hand, as root (or via sudo) on a fresh Ubuntu 22.04/24.04 VM
# (e.g. an Oracle Cloud "Always Free" instance):
#
#   sudo bash provision.sh
#
# It installs Apache + PHP 8.2 + MySQL + Composer, creates a dedicated
# low-privilege `deploy` user (the one GitHub Actions will SSH in as),
# clones the repo, and wires up the vhost. It does NOT touch GitHub
# secrets or DNS — those steps are in deploy/README.md.
#
# Safe to re-run: every step either checks before acting or is naturally
# idempotent (apt install, useradd -m with existing user skipped, etc).

set -euo pipefail

REPO_URL="git@github.com:bry237/LOKA.git"
APP_DIR="/var/www/LOKA"
DEPLOY_USER="deploy"
DB_NAME="loka"
DB_USER="loka_app"

echo "== 1/8  Mise à jour du système =="
apt-get update -y
apt-get upgrade -y

echo "== 2/8  Apache + PHP 8.2 + extensions =="
apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y apache2 \
  php8.2 php8.2-cli php8.2-mysql php8.2-curl php8.2-mbstring \
  php8.2-xml php8.2-fileinfo php8.2-opcache php8.2-intl libapache2-mod-php8.2

a2enmod php8.2
a2enmod env
a2enmod rewrite

echo "== 3/8  MySQL =="
apt-get install -y mysql-server
systemctl enable --now mysql

echo "== 4/8  Composer =="
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "== 5/8  Utilisateur de déploiement (SSH-only, sans mot de passe) =="
if ! id -u "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG www-data "$DEPLOY_USER"
mkdir -p "/home/$DEPLOY_USER/.ssh"
chmod 700 "/home/$DEPLOY_USER/.ssh"
touch "/home/$DEPLOY_USER/.ssh/authorized_keys"
chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
chown -R "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"

echo ">>> Colle ici la clé PUBLIQUE que GitHub Actions utilisera pour se connecter en SSH"
echo ">>> (générée sur TA machine avec: ssh-keygen -t ed25519 -C \"github-actions-loka\" -f gh-actions-key)"
echo ">>> Laisse vide + Entrée pour l'ajouter toi-même plus tard dans /home/$DEPLOY_USER/.ssh/authorized_keys"
read -r -p "Clé publique (gh-actions-key.pub) : " GH_ACTIONS_PUBKEY
if [ -n "$GH_ACTIONS_PUBKEY" ]; then
  echo "$GH_ACTIONS_PUBKEY" >> "/home/$DEPLOY_USER/.ssh/authorized_keys"
fi

echo "== 6/8  Clé de déploiement GitHub (le VM lit le repo en lecture seule) =="
if [ ! -f "/home/$DEPLOY_USER/.ssh/github_deploy_key" ]; then
  sudo -u "$DEPLOY_USER" ssh-keygen -t ed25519 -N "" -C "loka-server-deploy-key" \
    -f "/home/$DEPLOY_USER/.ssh/github_deploy_key"
fi
cat <<'EOF'

>>> Va sur https://github.com/bry237/LOKA/settings/keys > "Add deploy key"
>>> Colle la clé PUBLIQUE ci-dessous (lecture seule, ne coche PAS "Allow write access") :

EOF
cat "/home/$DEPLOY_USER/.ssh/github_deploy_key.pub"
cat <<'EOF'

Appuie sur Entrée une fois la clé ajoutée sur GitHub pour continuer.
EOF
read -r _

sudo -u "$DEPLOY_USER" bash -c "cat >> /home/$DEPLOY_USER/.ssh/config" <<EOF
Host github.com
  HostName github.com
  User git
  IdentityFile /home/$DEPLOY_USER/.ssh/github_deploy_key
  IdentitiesOnly yes
EOF
chmod 600 "/home/$DEPLOY_USER/.ssh/config"

echo "== 7/8  Clonage du dépôt =="
mkdir -p "$(dirname "$APP_DIR")"
if [ ! -d "$APP_DIR/.git" ]; then
  sudo -u "$DEPLOY_USER" git clone "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"
sudo -u "$DEPLOY_USER" git checkout main
sudo -u "$DEPLOY_USER" composer install --no-dev --optimize-autoloader --no-interaction

# Le serveur web (www-data) doit pouvoir écrire dans les dossiers d'upload
# créés à la volée par l'app (storage/, uploads/, public/uploads/...).
find "$APP_DIR" -maxdepth 0 -exec chgrp www-data {} \;
chmod -R g+w "$APP_DIR"
find "$APP_DIR" -type d -exec chmod g+s {} \;

echo "== 8/8  Base de données =="
echo ">>> Mot de passe pour le nouvel utilisateur MySQL applicatif ($DB_USER) :"
read -r -s DB_PASSWORD
echo
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
mysql -u root "$DB_NAME" < "$APP_DIR/database/schema/loka_database_mysql.sql"

echo "== Vhost Apache =="
sed -e "s#__APP_DIR__#$APP_DIR#g" "$APP_DIR/deploy/apache-loka.conf.template" \
  > /etc/apache2/sites-available/loka.conf
a2ensite loka.conf
a2dissite 000-default.conf || true
systemctl reload apache2

cat <<EOF

========================================================================
 Provisionnement terminé.

 Prochaines étapes (voir deploy/README.md pour le détail) :

 1. Ajoute les variables d'environnement réelles (base de données, et
    éventuellement Stripe/SMS) dans :
       /etc/apache2/sites-available/loka.conf
    puis : sudo systemctl reload apache2

    Valeurs DB à utiliser :
       LOKA_DB_HOST=127.0.0.1
       LOKA_DB_NAME=$DB_NAME
       LOKA_DB_USER=$DB_USER
       LOKA_DB_PASSWORD=(celui que tu viens de saisir)

 2. Dans les secrets GitHub du dépôt (Settings > Secrets and variables
    > Actions), ajoute :
       SSH_HOST  = l'IP publique de ce serveur
       SSH_USER  = $DEPLOY_USER
       SSH_KEY   = la clé PRIVÉE correspondant à la clé publique que tu
                   as collée à l'étape 5 (gh-actions-key, PAS .pub)

 3. Teste le site : http://<IP_PUBLIQUE>/LOKA/

 4. (Optionnel) Configure un nom de domaine + HTTPS avec certbot — voir
    deploy/README.md.
========================================================================
EOF
