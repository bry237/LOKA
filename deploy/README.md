# Déploiement LOKA — pipeline CI/CD gratuit (Oracle Cloud Free Tier)

Déploiement automatique : chaque `push` sur `main` déclenche GitHub Actions, qui
vérifie le code PHP puis se connecte en SSH au serveur pour mettre à jour le
site. Coût réel : **0 €**, à vie, tant que tu restes dans les limites "Always
Free" d'Oracle Cloud (largement suffisant pour ce projet).

La carte bancaire demandée par Oracle à l'inscription sert uniquement à la
vérification anti-abus — rien n'est débité sur l'offre Always Free. Tu peux
configurer une alerte de budget à 0 € dans Oracle Cloud si tu veux être
rassuré(e).

## Vue d'ensemble du pipeline

```
toi (git push main) → GitHub Actions
                         ├─ job "lint"   : php -l sur tout le projet
                         └─ job "deploy" : SSH vers le serveur → deploy.sh
                                              ├─ git pull
                                              ├─ composer install
                                              └─ re-vérification php -l
```

Aucune étape manuelle après la mise en place initiale ci-dessous.

---

## Étape 1 — Créer le compte Oracle Cloud

1. Va sur https://www.oracle.com/cloud/free/ et inscris-toi (email, carte
   bancaire pour vérification, pas de prélèvement sur l'offre gratuite).
2. Une fois dans la console, va dans **Compute > Instances > Create Instance**.
3. **Image** : Ubuntu 22.04 (ou 24.04).
4. **Shape** : clique sur "Change shape" → choisis **Ampere (ARM)**,
   `VM.Standard.A1.Flex`, et règle 2 OCPU / 12 Go de RAM (reste dans le
   quota "Always Free" qui va jusqu'à 4 OCPU / 24 Go au total).
   - Si tu as une erreur "Out of capacity" sur ta région, retente plus tard
     ou bascule sur le shape AMD `VM.Standard.E2.1.Micro` (plus petit mais
     toujours gratuit à vie, et largement suffisant pour démarrer).
5. **Clé SSH** : laisse Oracle générer une paire de clés et télécharge-la
   (ou colle une clé publique que tu as déjà) — c'est la clé qui te permet
   de te connecter en `ssh ubuntu@<IP>` pour faire le provisionnement.
6. Crée l'instance, note son **IP publique**.
7. Dans **Networking > Virtual Cloud Networks > (ton VCN) > Security Lists**,
   ajoute des règles d'entrée (Ingress) pour les ports **80** et **443**
   (TCP, source `0.0.0.0/0`) — le port 22 (SSH) est généralement déjà ouvert
   par défaut.

## Étape 2 — Générer la clé SSH que GitHub Actions utilisera

Sur **ta machine** (pas sur le serveur) :

```bash
ssh-keygen -t ed25519 -C "github-actions-loka" -f gh-actions-key
```

Ça crée deux fichiers : `gh-actions-key` (clé **privée** — ira dans un
secret GitHub) et `gh-actions-key.pub` (clé **publique** — ira sur le
serveur). Garde les deux sous la main pour les étapes suivantes.

## Étape 3 — Provisionner le serveur

Connecte-toi au serveur avec la clé Oracle de l'étape 1 :

```bash
ssh ubuntu@<IP_PUBLIQUE>
```

Récupère le script de provisionnement (le repo n'est pas encore cloné sur
le serveur à ce stade, donc on le télécharge directement) :

```bash
curl -fsSL https://raw.githubusercontent.com/bry237/LOKA/main/deploy/provision.sh -o provision.sh
sudo bash provision.sh
```

Le script va te demander, dans l'ordre :
1. De coller le contenu de `gh-actions-key.pub` (étape 2).
2. D'ajouter une **Deploy Key** sur GitHub (il t'affiche la clé à coller
   dans https://github.com/bry237/LOKA/settings/keys — coche "Allow write
   access" **décoché**, lecture seule suffit).
3. Un mot de passe pour l'utilisateur MySQL applicatif (`loka_app`) —
   choisis-en un et note-le, tu en auras besoin à l'étape suivante.

À la fin, le script t'affiche un résumé avec les prochaines étapes.

## Étape 4 — Configurer les variables d'environnement du serveur

```bash
sudo nano /etc/apache2/sites-available/loka.conf
```

Remplace `LOKA_DB_PASSWORD CHANGE-MOI` par le mot de passe choisi à
l'étape 3. Laisse `SMS_PROVIDER log` tel quel pour l'instant (voir
mémoire projet : l'envoi SMS réel via Vonage est bloqué tant que le
compte n'est pas vérifié avec un numéro acheté — pas bloquant pour le
déploiement). Si tu as des clés Stripe à activer, décommente et
renseigne les lignes `STRIPE_*`.

```bash
sudo systemctl reload apache2
```

Teste : `http://<IP_PUBLIQUE>/LOKA/` doit afficher la page d'accueil LOKA.

## Étape 5 — Configurer les secrets GitHub Actions

Sur https://github.com/bry237/LOKA/settings/secrets/actions, ajoute 3
secrets (**New repository secret**) :

| Nom               | Valeur                                              |
|-------------------|------------------------------------------------------|
| `SSH_HOST`        | l'IP publique du serveur                            |
| `SSH_USER`        | `deploy`                                             |
| `SSH_PRIVATE_KEY` | le contenu **complet** de `gh-actions-key` (la clé privée, pas `.pub`) |

## Étape 6 — Premier déploiement

Fusionne `odyssee` dans `main` (ou pousse directement sur `main`) :

```bash
git checkout main
git merge odyssee
git push origin main
```

Va dans l'onglet **Actions** du dépôt GitHub : le workflow "Déploiement
LOKA" se lance automatiquement, lint puis déploie. À partir de maintenant,
**chaque push sur `main` redéploie automatiquement**.

---

## (Optionnel) Nom de domaine + HTTPS gratuit

Sans domaine, le site reste accessible en `http://<IP>/LOKA/` — ça
fonctionne, mais sans HTTPS (gênant pour Stripe en production). Deux
options gratuites :

- **Tu as déjà un nom de domaine** : crée un enregistrement DNS de type `A`
  pointant vers l'IP publique du serveur.
- **Tu n'en as pas** : https://www.duckdns.org/ donne un sous-domaine
  gratuit (`tonnom.duckdns.org`) sans carte bancaire, en 2 minutes.

Puis sur le serveur :

```bash
sudo apt install certbot python3-certbot-apache
sudo sed -i 's/CHANGE-MOI.example.com/tonnom.duckdns.org/' /etc/apache2/sites-available/loka.conf
sudo systemctl reload apache2
sudo certbot --apache -d tonnom.duckdns.org
```

Certbot configure le HTTPS et renouvelle automatiquement le certificat
(gratuit, Let's Encrypt).

---

## Limites à connaître

- **Pas de migrations automatiques de base de données.** Ce projet n'a pas
  d'outil de migration (voir mémoire projet : un seul fichier de schéma
  SQL, appliqué manuellement). Si tu modifies le schéma en local, il
  faudra réappliquer le changement à la main sur la base de production
  (`mysql -u loka_app -p loka < nouveau_fichier.sql` ou des `ALTER TABLE`
  ciblés) — le pipeline ne déploie que le code PHP, jamais la structure de
  la base.
- **Les fichiers uploadés par les utilisateurs** (documents, photos,
  quittances PDF, contrats) vivent directement sur le serveur, en dehors
  de git (`storage/`, `uploads/`, `public/uploads/` — maintenant dans
  `.gitignore`). Ils survivent aux déploiements (`deploy.sh` ne fait
  jamais `git clean`), mais ne sont pas sauvegardés automatiquement —
  pense à les copier (`rsync`/`scp`) de temps en temps si tu veux une
  sauvegarde.
- **Oracle Cloud Free Tier** a de vraies limites de capacité ("out of
  host capacity" sur les shapes Ampere selon la région/le moment) mais
  reste gratuit à vie une fois l'instance créée — ce n'est pas un essai
  limité dans le temps comme AWS/GCP.
