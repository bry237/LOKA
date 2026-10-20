# Jeu de données LOKA — réinitialisé le 2026-10-20

## Cohérence base de données / code

Le schéma SQL (`database/schema/loka_database_mysql.sql`) a été comparé table par table et
colonne par colonne à la base `loka` réellement servie par XAMPP : les deux étaient déjà
identiques (40 tables, aucun écart). La seule mise à jour de structure apportée dans cette
passe est l'ajout de la table **`password_reset`** (nouvelle fonctionnalité "mot de passe
oublié", voir plus bas) — elle est présente à la fois dans le fichier de schéma et dans la
base, donc le fichier reste la source de vérité fidèle à ce qui tourne réellement.

Une sauvegarde complète de l'ancienne base (comptes de test de l'équipe, agences factices,
emails personnels...) a été faite avant la purge : `database/backups/loka_backup_before_reseed_*.sql`
(non versionné, voir `.gitignore`).

## Ce qui a été vidé / ce qui a été conservé

**Vidé et recréé** : `agence`, `utilisateur`, `proprietaire`, `locataire`, `bien`, `immeuble`,
`adresse`, `bien_proprietaire`, `contrat`, `contrat_locataire`, `echeance`, `paiement`,
`quittance`, `intervention`, `commentaire_intervention`, `notification`, `agence_abonnement`,
`session_utilisateur`, `otp_code`, `journal_audit`, `password_reset`, `document*`.

**Conservé tel quel** : `role`, `permission`, `role_permission`, `type_bien`, `type_document`,
`equipement`, `abonnement`, `parametre_plateforme` — ce sont des référentiels système (pas des
données utilisateur), et plusieurs autres parties de l'app (catalogue d'abonnements Stripe,
listes déroulantes) s'appuient sur leurs ids existants.

Le script qui a produit ce jeu de données est **`database/seed/seed.php`** (ré-exécutable à
tout moment : `php database/seed/seed.php` depuis la racine du projet — il vide puis recrée
les mêmes données, de façon déterministe).

## Connexion — mot de passe unique

Tous les comptes ci-dessous utilisent le **même mot de passe** :

```
Loka2026!
```

(8 caractères minimum requis par le formulaire d'inscription — ce mot de passe le respecte.
Chaque utilisateur peut le changer depuis son propre espace, ou via "Mot de passe oublié"
sur la page de connexion.)

## Les 21 comptes (3 par rôle)

### Administrateur plateforme — aucune agence
| Email | Nom |
|---|---|
| admin.claire@loka.fr | Claire Fontaine |
| admin.julien@loka.fr | Julien Moreau |
| admin.sophie@loka.fr | Sophie Lambert |

### 3 agences, chacune avec son équipe complète (Administrateur agence, Gestionnaire immobilier, Comptable, Technicien)

**Agence Horizon Immobilier** (Paris)
| Rôle | Email | Nom |
|---|---|---|
| Administrateur agence | agence.nicolas@loka.fr | Nicolas Girard |
| Gestionnaire immobilier | gestion.lea@loka.fr | Léa Bonnet |
| Comptable | compta.antoine@loka.fr | Antoine Perrin |
| Technicien | technicien.karim@loka.fr | Karim Haddad |

**Agence Atlantique Gestion** (Nantes)
| Rôle | Email | Nom |
|---|---|---|
| Administrateur agence | agence.camille@loka.fr | Camille Dubois |
| Gestionnaire immobilier | gestion.hugo@loka.fr | Hugo Faure |
| Comptable | compta.chloe@loka.fr | Chloé Masson |
| Technicien | technicien.yanis@loka.fr | Yanis Picard |

**Agence Lumière du Sud** (Marseille)
| Rôle | Email | Nom |
|---|---|---|
| Administrateur agence | agence.thomas@loka.fr | Thomas Lefevre |
| Gestionnaire immobilier | gestion.manon@loka.fr | Manon Roche |
| Comptable | compta.mathieu@loka.fr | Mathieu Robin |
| Technicien | technicien.romain@loka.fr | Romain Caron |

### Propriétaires (3, un par agence — gérés par celle-ci)
| Email | Nom | Agence |
|---|---|---|
| proprio.isabelle@loka.fr | Isabelle Noel | Horizon Immobilier |
| proprio.marc@loka.fr | Marc Vidal | Atlantique Gestion |
| proprio.nathalie@loka.fr | Nathalie Blanchard | Lumière du Sud |

### Locataires (3, un par agence — tous avec un bail actif)
| Email | Nom | Agence |
|---|---|---|
| locataire.emma@loka.fr | Emma Petit | Horizon Immobilier |
| locataire.lucas@loka.fr | Lucas Bernard | Atlantique Gestion |
| locataire.sarah@loka.fr | Sarah Michel | Lumière du Sud |

## Données associées (par agence)

Chaque agence dispose de données cohérentes et interconnectées pour tester tous les
modules sans configuration supplémentaire :

- **2 biens** : un occupé (bail actif, loyer encaissé) + un disponible à la location.
- **1 contrat actif** sur le bien occupé, avec le locataire de l'agence comme titulaire.
- **2 échéances** : le mois dernier (payée, quittance générée) + le mois en cours (en attente).
- **2 interventions** de maintenance assignées au technicien de l'agence (statuts variés :
  ouverte, en cours, résolue, clôturée — pour voir tous les états dans les tableaux de bord).
- **Notifications** réalistes pour chaque membre de l'équipe, le propriétaire et le locataire
  (certaines déjà lues, d'autres non — pour tester la cloche de notifications dès la connexion).
- **1 abonnement actif** (Horizon et Lumière du Sud en Pro, Atlantique en Gratuit).

Aucun document (PDF, pièce d'identité...) n'a été recréé : les tables `document`,
`document_utilisateur` et `document_agence` sont volontairement vides — tous les comptes
seedés sont déjà `ACTIVE`/vérifiés, aucun dossier n'est en attente de validation.

## Mot de passe oublié & Se souvenir de moi

Deux fonctionnalités codées dans cette passe (elles étaient des pages vides avant) :

- **Mot de passe oublié** (`app/authentification/mot-de-passe-oublie.php` →
  `nouveau-mot-de-passe.php`) : génère un lien à usage unique valable 30 minutes
  (`core/PasswordResetService.php`, table `password_reset`). Aucun serveur SMTP n'étant
  configuré dans ce projet (seul `stripe/stripe-php` figure au composer.json), l'email part
  en mode "log" comme les SMS (`core/EmailSender.php`, même pattern que `core/SmsSender.php`) :
  le lien est écrit dans le journal serveur **et affiché directement sur la page de
  confirmation**, clairement encadré "Mode développement". Pour brancher un vrai envoi,
  ajouter une dépendance SMTP (ex. PHPMailer) et compléter `EmailSender::sendViaSmtp()` +
  les variables d'environnement `EMAIL_PROVIDER=smtp`, `SMTP_HOST`, `SMTP_USER`, etc.
  (voir `config/services.php`). Réinitialiser le mot de passe déconnecte automatiquement
  toutes les sessions actives de l'utilisateur, sur tous les appareils.

- **Se souvenir de moi** (case à cocher déjà présente sur le formulaire de connexion, non
  câblée avant) : quand elle est cochée, la session passe de 8 heures à 30 jours — à la fois
  côté base (`session_utilisateur.date_expiration`) et côté cookie navigateur
  (`core/Session::extend()`, appelé depuis `core/Auth::createSession()`). Non cochée,
  comportement inchangé : le cookie expire à la fermeture du navigateur.
