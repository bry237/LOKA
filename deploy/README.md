# Déploiement LOKA — pipeline CI/CD gratuit, sans carte bancaire (InfinityFree)

Déploiement automatique : chaque `push` sur `main` déclenche GitHub Actions, qui
vérifie le code PHP, construit les dépendances Composer, puis envoie tout par
FTP vers un hébergement mutualisé 100% gratuit. Coût réel : **0 €**, aucune
carte bancaire requise.

> Historique : on avait d'abord préparé un déploiement SSH vers un VPS Oracle
> Cloud (gratuit à vie mais carte requise à l'inscription) — abandonné suite à
> un refus de carte. Cette version FTP est 100% sans carte, mais plus limitée
> (pas de SSH, pas de ligne de commande sur le serveur).

## Vue d'ensemble du pipeline

```
toi (git push main) → GitHub Actions
                         ├─ job "lint"   : php -l + composer install (vérification)
                         └─ job "deploy" : composer install --no-dev (construit vendor/)
                                            → envoi FTP de tout le projet vers l'hébergeur
```

Aucune étape manuelle après la mise en place initiale ci-dessous — à une
exception près : l'hébergeur n'a pas d'accès en ligne de commande, donc un
changement de **structure** de base de données reste à appliquer à la main
via phpMyAdmin (voir "Limites" en bas de page).

---

## Étape 1 — Créer le compte et l'hébergement

1. Va sur **https://infinityfree.net/** (ou un équivalent comme 000webhost —
   les étapes sont similaires) et inscris-toi avec ton email. Aucune carte
   bancaire demandée.
2. Clique **Create Account** (un "compte" = un site). Choisis un sous-domaine
   gratuit, par exemple `loka-app.infinityfreeapp.com` (ou connecte ton propre
   nom de domaine si tu en as un, gratuitement aussi).
3. Attends l'activation (quelques minutes en général).

## Étape 2 — Récupérer les accès FTP

Dans le **Control Panel (vPanel)** de ton hébergement, section **FTP
Accounts** / **FTP Details** :

- **FTP Server** (ex: `ftpupload.net`)
- **FTP Username** (ex: `if0_12345678`)
- **FTP Password** (celui que tu as choisi ou qui t'a été donné)

Note ces 3 valeurs — elles vont dans les secrets GitHub (étape 5).

## Étape 3 — Créer la base de données

Dans le vPanel, section **MySQL Databases** :

1. Crée une nouvelle base (note le nom généré, ex: `if0_12345678_loka`).
2. Note aussi : **nom d'utilisateur MySQL**, **mot de passe**, et surtout
   l'**hôte MySQL** (souvent différent du serveur FTP, ex: `sql200.infinityfree.com`
   — c'est indiqué dans le vPanel).
3. Ouvre **phpMyAdmin** (lien disponible dans le vPanel), sélectionne ta base,
   onglet **Importer**, et importe le fichier
   **`database/schema/loka_database_mysql.shared-hosting.sql`** de ce dépôt
   (pas le fichier `loka_database_mysql.sql` original). Deux adaptations par
   rapport à l'original, toutes deux liées aux limites d'un hébergement
   mutualisé gratuit :
   - les 2 triggers de la table `document` sont retirés (droit `TRIGGER`
     refusé aux comptes MySQL gratuits, erreur `#1142` sinon) — la règle
     qu'ils imposaient (un document rattaché à une seule entité) reste
     appliquée côté PHP (`DocumentValidator`), seul le filet de sécurité
     redondant au niveau base de données saute ;
   - toutes les tables sont créées **sans** leurs clés étrangères, qui sont
     ajoutées ensuite via des `ALTER TABLE` une fois que les 41 tables
     existent toutes — évite l'erreur `#1005 (Errcode 150)` que
     phpMyAdmin déclenche quand il importe un gros fichier en plusieurs
     morceaux et perd au passage le `FOREIGN_KEY_CHECKS=0` qui aurait
     permis de créer les tables dans le désordre.

   (Même schéma au final : 41 tables, mêmes 62 clés étrangères, mêmes
   données de référence — rôles, types de biens, etc., pas de faux comptes.
   Testé par un import réel en local avant d'être ajouté au dépôt.)

## Étape 4 — Choisir la version PHP

Dans le vPanel, section **PHP Version** (parfois sous "Software" ou directement
en haut du panneau) : choisis la version la plus récente disponible, idéalement
**8.1 ou plus** (le projet est développé et testé en PHP 8.2).

## Étape 5 — Configurer les secrets GitHub Actions

Sur https://github.com/bry237/LOKA/settings/secrets/actions, ajoute 3
secrets (**New repository secret**) avec les valeurs de l'étape 2 :

| Nom              | Valeur                          |
|------------------|----------------------------------|
| `FTP_SERVER`     | le serveur FTP (étape 2)        |
| `FTP_USERNAME`   | l'utilisateur FTP (étape 2)     |
| `FTP_PASSWORD`   | le mot de passe FTP (étape 2)   |

## Étape 6 — Configurer la connexion à la base (une seule fois, à la main)

L'hébergeur ne permet pas de définir des variables d'environnement serveur de
façon fiable (pas d'accès SSH, et `SetEnv` dans `.htaccess` ne remonte pas
toujours jusqu'à PHP selon le mode d'exécution utilisé par l'hébergeur). À la
place, `config/database.php` et `config/services.php` chargent un fichier
`config/env.local.php` s'il existe — ce fichier n'est **jamais** versionné ni
envoyé par le pipeline automatique, tu l'uploades toi-même **une seule fois** :

1. Sur ta machine, copie `config/env.local.php.example` vers
   `config/env.local.php` et remplis les vraies valeurs (celles de l'étape 3
   pour la base de données).
2. Connecte-toi en FTP (avec FileZilla par exemple, identifiants de l'étape 2)
   et dépose ce fichier unique dans `htdocs/LOKA/config/env.local.php`.

Il restera en place à chaque déploiement automatique (le pipeline l'exclut
explicitement de l'envoi, voir `.github/workflows/deploy.yml`).

## Étape 7 — Premier déploiement

Fusionne `odyssee` dans `main` (ou pousse directement sur `main`) :

```bash
git checkout main
git merge odyssee
git push origin main
```

Va dans l'onglet **Actions** du dépôt GitHub : le workflow "Déploiement LOKA"
se lance automatiquement, lint puis déploie par FTP. À partir de maintenant,
**chaque push sur `main` redéploie automatiquement**.

Teste ensuite : `https://loka-app.infinityfreeapp.com/LOKA/` (remplace par ton
sous-domaine — le HTTPS gratuit est généralement actif automatiquement, parfois
avec quelques heures de délai sur un compte tout juste créé).

---

## Limites à connaître

- **Pas de migrations automatiques de base de données**, comme pour n'importe
  quelle approche sans accès shell sur ce projet (voir mémoire projet : un seul
  fichier de schéma SQL, pas d'outil de migration). Si tu modifies le schéma en
  local, réimporte le changement à la main via phpMyAdmin.
- **Pas d'accès ligne de commande** sur le serveur — tout ce qui nécessiterait
  normalement du SSH (purge de cache, scripts ponctuels) doit passer par une
  page PHP dédiée ou phpMyAdmin.
- **Compte suspendu après ~30 jours d'inactivité** côté hébergeur : connecte-toi
  de temps en temps au vPanel pour garder le compte actif.
- **Les fichiers uploadés par les utilisateurs** (documents, photos, quittances
  PDF, contrats — `storage/`, `uploads/`, `public/uploads/`, dans `.gitignore`)
  vivent uniquement sur le serveur FTP, ne sont jamais écrasés par le pipeline
  (`dangerous-clean-slate: false`), mais ne sont pas sauvegardés automatiquement
  — pense à les télécharger de temps en temps en FTP si tu veux une sauvegarde.
- **Ressources partagées** : hébergement mutualisé, performances correctes pour
  une démo/un petit usage mais pas pour un trafic important.

## Si tu retrouves un moyen de payer (ou qu'une carte finit par passer)

Le dossier `deploy/` gardait avant une version SSH (Oracle Cloud Free Tier,
gratuit à vie mais carte requise) — plus robuste (vrai serveur, vraie ligne de
commande, migrations possibles). Dis-le-moi si tu veux qu'on la reprenne : elle
n'a pas été supprimée de l'historique git, seulement retirée du dossier actif.
