<?php

declare(strict_types=1);



require_once dirname(__DIR__, 3) . '/core/Authorization.php';

/*
|--------------------------------------------------------------------------
| Vérification du rôle
|--------------------------------------------------------------------------
*/

$auth = Authorization::requireRole('Proprietaire');

/*
|--------------------------------------------------------------------------
| Connexion à la base de données
|--------------------------------------------------------------------------
*/

$config = require dirname(__DIR__, 3) . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['name'],
    $config['charset']
);

try {

    $pdo = new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {

    http_response_code(500);

    exit('Erreur de connexion à la base de données.');

}


/*
|--------------------------------------------------------------------------
| Fonctions
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatMoney($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format(
        (float) $value,
        2,
        ',',
        ' '
    ) . ' €';
}


function formatDateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}


function contratStatutLabel(?string $statut): string
{
    return match (strtoupper((string) $statut)) {

        'DRAFT' => 'Brouillon',

        'ACTIVE' => 'Actif',

        'EXPIRED' => 'Expiré',

        'RENEWED' => 'Renouvelé',

        'TERMINATED' => 'Terminé',

        'TERMINATED_EARLY' => 'Résilié',

        default => $statut ?: '—',
    };
}


function contratStatutClass(?string $statut): string
{
    return match (strtoupper((string) $statut)) {

        'ACTIVE' => 'status-active',

        'DRAFT' => 'status-draft',

        'EXPIRED' => 'status-expired',

        'RENEWED' => 'status-renewed',

        'TERMINATED',
        'TERMINATED_EARLY' => 'status-terminated',

        default => 'status-default',
    };
}


function valeurOuTiret($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return e((string) $value);
}


/*
|--------------------------------------------------------------------------
| Récupération de l'identifiant du locataire
|--------------------------------------------------------------------------
*/

$idLocataire = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$idLocataire || $idLocataire <= 0) {

    http_response_code(400);

    exit('Locataire invalide.');

}


/*
|--------------------------------------------------------------------------
| Identification du propriétaire connecté
|--------------------------------------------------------------------------
*/

$idUtilisateur = (int) ($auth['id_utilisateur'] ?? 0);

if ($idUtilisateur <= 0) {

    http_response_code(403);

    exit('Utilisateur non authentifié.');

}


/*
|--------------------------------------------------------------------------
| Recherche du propriétaire
|--------------------------------------------------------------------------
|
| On utilise le même principe que dans la page Mes locataires :
| utilisateur.email = proprietaire.email
|
*/

$stmt = $pdo->prepare(
    "
    SELECT
        p.id_proprietaire,
        p.nom,
        p.prenom,
        p.email
    FROM proprietaire p
    INNER JOIN utilisateur u
        ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
    "
);

$stmt->execute([
    $idUtilisateur
]);

$proprietaire = $stmt->fetch();


if (!$proprietaire) {

    http_response_code(403);

    exit('Profil propriétaire introuvable.');

}


$idProprietaire = (int) $proprietaire['id_proprietaire'];


/*
|--------------------------------------------------------------------------
| Récupération du locataire
|--------------------------------------------------------------------------
|
| IMPORTANT :
| Le locataire doit être relié à un contrat d'un bien
| appartenant au propriétaire connecté.
|
| Cela empêche un propriétaire d'afficher le locataire
| d'un autre propriétaire en modifiant simplement ?id=
|
*/

$stmt = $pdo->prepare(
    "
    SELECT
        l.id_locataire,
        l.nom,
        l.prenom,
        l.email,
        l.telephone,
        l.adresse,
        l.code_postal,
        l.ville,
        l.pays,
        l.date_naissance,
        l.profession,
        l.revenu_mensuel,
        l.revenu_mensuel_fourchette,
        l.statut AS statut_locataire,
        l.created_at,
        l.updated_at

    FROM locataire l

    WHERE l.id_locataire = ?

      AND l.deleted_at IS NULL

      AND EXISTS (

          SELECT 1

          FROM contrat_locataire cl

          INNER JOIN contrat c
              ON c.id_contrat = cl.id_contrat

          INNER JOIN bien b
              ON b.id_bien = c.id_bien

          WHERE cl.id_locataire = l.id_locataire

            AND c.deleted_at IS NULL

            AND b.deleted_at IS NULL

            AND EXISTS (

                SELECT 1

                FROM bien_proprietaire bp

                WHERE bp.id_bien = b.id_bien

                  AND bp.id_proprietaire = ?

            )

      )

    LIMIT 1
    "
);

$stmt->execute([
    $idLocataire,
    $idProprietaire
]);

$locataire = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Locataire introuvable ou non accessible
|--------------------------------------------------------------------------
*/

if (!$locataire) {

    http_response_code(404);

    exit('Locataire introuvable.');

}


/*
|--------------------------------------------------------------------------
| Récupération des contrats du locataire
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "
    SELECT

        c.id_contrat,
        c.numero AS numero_contrat,
        c.date_debut,
        c.date_fin,
        c.loyer,
        c.charges,
        c.depot_garantie,
        c.frequence_paiement,
        c.jour_echeance,
        c.statut AS statut_contrat,

        cl.titulaire_principal,
        cl.date_entree,
        cl.date_sortie,

        b.id_bien,
        b.reference AS reference_bien,
        b.titre AS titre_bien

    FROM contrat_locataire cl

    INNER JOIN contrat c
        ON c.id_contrat = cl.id_contrat

    INNER JOIN bien b
        ON b.id_bien = c.id_bien

    WHERE cl.id_locataire = ?

      AND c.deleted_at IS NULL

      AND b.deleted_at IS NULL

      AND EXISTS (

          SELECT 1

          FROM bien_proprietaire bp

          WHERE bp.id_bien = b.id_bien

            AND bp.id_proprietaire = ?

      )

    ORDER BY
        CASE

            WHEN c.statut = 'ACTIVE'
                THEN 0

            WHEN c.statut = 'DRAFT'
                THEN 1

            WHEN c.statut = 'RENEWED'
                THEN 2

            WHEN c.statut = 'EXPIRED'
                THEN 3

            ELSE 4

        END,

        c.date_debut DESC
    "
);

$stmt->execute([
    $idLocataire,
    $idProprietaire
]);

$contrats = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Calculs pour l'affichage
|--------------------------------------------------------------------------
*/

$nombreContrats = count($contrats);

$contratActif = null;

foreach ($contrats as $contrat) {

    if (strtoupper((string) $contrat['statut_contrat']) === 'ACTIVE') {

        $contratActif = $contrat;

        break;
    }
}


/*
|--------------------------------------------------------------------------
| Nom complet
|--------------------------------------------------------------------------
*/

$nomComplet = trim(
    ($locataire['prenom'] ?? '') .
    ' ' .
    ($locataire['nom'] ?? '')
);

if ($nomComplet === '') {
    $nomComplet = 'Locataire';
}


/*
|--------------------------------------------------------------------------
| Initiales
|--------------------------------------------------------------------------
*/

$initiales = '';

if (!empty($locataire['prenom'])) {
    $initiales .= strtoupper(
        substr(
            $locataire['prenom'],
            0,
            1
        )
    );
}

if (!empty($locataire['nom'])) {
    $initiales .= strtoupper(
        substr(
            $locataire['nom'],
            0,
            1
        )
    );
}

if ($initiales === '') {
    $initiales = 'L';
}

?>
<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($nomComplet) ?> - LOKA
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS propriétaire existant -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/proprietaire.css"
    >


    <!-- CSS spécifique aux locataires -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Locataires/locataire.css"
    >


    <!-- CSS spécifique à cette page -->

    <link
        rel="stylesheet"
        href="/LOKA/app/proprietaires/Locataires/locataireDetail.css"
    >

</head>


<body>


<div class="dashboard">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">


        <div class="sidebar-header">

            <div class="logo">
                LOKA
            </div>

        </div>


        <nav class="sidebar-nav">


            <a
                href="/LOKA/app/proprietaires/index.php"
            >
                <i class="fa-solid fa-house"></i>
                <span>Tableau de bord</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/biens.php"
            >
                <i class="fa-solid fa-building"></i>
                <span>Mes biens</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/Locataires/index.php"
                class="active"
            >
                <i class="fa-solid fa-users"></i>
                <span>Mes locataires</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/mes_contrats.php"
            >
                <i class="fa-solid fa-file-contract"></i>
                <span>Mes contrats</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/mes_revenus.php"
            >
                <i class="fa-solid fa-chart-line"></i>
                <span>Mes revenus</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/paiements.php"
            >
                <i class="fa-solid fa-money-bill-wave"></i>
                <span>Paiements</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/quittances.php"
            >
                <i class="fa-solid fa-receipt"></i>
                <span>Quittances</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/documents.php"
            >
                <i class="fa-solid fa-folder-open"></i>
                <span>Documents</span>
            </a>


            <a
                href="/LOKA/app/proprietaires/parametres.php"
            >
                <i class="fa-solid fa-gear"></i>
                <span>Paramètres</span>
            </a>


        </nav>


    </aside>



    <!-- =========================================================
         CONTENU
    ========================================================== -->

    <main class="content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h1>
                    Détail du locataire
                </h1>

                <p>
                    Consultez les informations et les contrats du locataire.
                </p>

            </div>


            <div class="user">

                <div class="avatar">

                    <?= e($initiales) ?>

                </div>

            </div>

        </header>



        <!-- =====================================================
             RETOUR
        ====================================================== -->

        <div class="locataire-detail-back">

            <a
                href="/LOKA/app/proprietaires/Locataires/index.php"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Retour à mes locataires

            </a>

        </div>



        <!-- =====================================================
             PROFIL
        ====================================================== -->

        <section class="locataire-detail-profile">


            <div class="locataire-detail-avatar">

                <?= e($initiales) ?>

            </div>


            <div class="locataire-detail-profile-info">


                <h2>
                    <?= e($nomComplet) ?>
                </h2>


                <div class="locataire-detail-status">

                    <span
                        class="locataire-status-badge"
                    >
                        <?= e($locataire['statut_locataire'] ?: '—') ?>
                    </span>

                </div>


                <?php if (!empty($locataire['email'])): ?>

                    <a
                        href="mailto:<?= e($locataire['email']) ?>"
                    >

                        <i class="fa-solid fa-envelope"></i>

                        <?= e($locataire['email']) ?>

                    </a>

                <?php endif; ?>


                <?php if (!empty($locataire['telephone'])): ?>

                    <a
                        href="tel:<?= e($locataire['telephone']) ?>"
                    >

                        <i class="fa-solid fa-phone"></i>

                        <?= e($locataire['telephone']) ?>

                    </a>

                <?php endif; ?>


            </div>


        </section>



        <!-- =====================================================
             STATISTIQUES
        ====================================================== -->

        <section class="locataire-detail-stats">


            <div class="locataire-detail-stat-card">

                <div class="locataire-detail-stat-icon">

                    <i class="fa-solid fa-file-contract"></i>

                </div>

                <div>

                    <span>
                        Contrats
                    </span>

                    <strong>
                        <?= $nombreContrats ?>
                    </strong>

                </div>

            </div>



            <div class="locataire-detail-stat-card">

                <div class="locataire-detail-stat-icon">

                    <i class="fa-solid fa-house"></i>

                </div>

                <div>

                    <span>
                        Bien actuel
                    </span>

                    <strong>

                        <?php if ($contratActif): ?>

                            <?= e(
                                $contratActif['reference_bien']
                                ?: $contratActif['titre_bien']
                                ?: '—'
                            ) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>

            </div>



            <div class="locataire-detail-stat-card">

                <div class="locataire-detail-stat-icon">

                    <i class="fa-solid fa-euro-sign"></i>

                </div>

                <div>

                    <span>
                        Loyer actuel
                    </span>

                    <strong>

                        <?php if ($contratActif): ?>

                            <?= formatMoney($contratActif['loyer']) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>

            </div>



            <div class="locataire-detail-stat-card">

                <div class="locataire-detail-stat-icon">

                    <i class="fa-solid fa-calendar-check"></i>

                </div>

                <div>

                    <span>
                        Entrée actuelle
                    </span>

                    <strong>

                        <?php if ($contratActif): ?>

                            <?= formatDateFr(
                                $contratActif['date_entree']
                                ?: $contratActif['date_debut']
                            ) ?>

                        <?php else: ?>

                            —

                        <?php endif; ?>

                    </strong>

                </div>

            </div>


        </section>



        <!-- =====================================================
             INFORMATIONS PERSONNELLES
        ====================================================== -->

        <section class="locataire-detail-section">


            <div class="locataire-detail-section-header">

                <div>

                    <h2>
                        Informations personnelles
                    </h2>

                    <p>
                        Informations enregistrées pour ce locataire.
                    </p>

                </div>


                <div class="locataire-detail-section-icon">

                    <i class="fa-solid fa-user"></i>

                </div>

            </div>



            <div class="locataire-detail-grid">


                <div class="locataire-detail-field">

                    <span>
                        Prénom
                    </span>

                    <strong>
                        <?= valeurOuTiret($locataire['prenom']) ?>
                    </strong>   

                </div>


                <div class="locataire-detail-field">

                    <span>
                        Nom
                    </span>

                    <strong>
                        <?= valeurOuTiret($locataire['nom']) ?>
                    </strong>

                </div>


                <div class="locataire-detail-field">

                    <span>
                        Date de naissance
                    </span>

                    <strong>
                        <?= formatDateFr($locataire['date_naissance']) ?>
                    </strong>

                </div>


                <div class="locataire-detail-field">

                    <span>
                        Profession
                    </span>

                    <strong>
                        <?= valeurOuTiret($locataire['profession']) ?>
                    </strong>

                </div>


                <div class="locataire-detail-field locataire-detail-field-wide">

                    <span>
                        Adresse
                    </span>

                    <strong>

                        <?php

                        $adresseComplete = trim(
                            implode(
                                ' ',
                                array_filter([
                                    $locataire['adresse'] ?? '',
                                    $locataire['code_postal'] ?? '',
                                    $locataire['ville'] ?? '',
                                    $locataire['pays'] ?? '',
                                ])
                            )
                        );

                        ?>

                        <?= e($adresseComplete ?: '—') ?>

                    </strong>

                </div>


            </div>


        </section>



        <!-- =====================================================
             CONTACT
        ====================================================== -->

        <section class="locataire-detail-section">


            <div class="locataire-detail-section-header">

                <div>

                    <h2>
                        Coordonnées
                    </h2>

                    <p>
                        Moyens de contact du locataire.
                    </p>

                </div>


                <div class="locataire-detail-section-icon">

                    <i class="fa-solid fa-address-book"></i>

                </div>

            </div>



            <div class="locataire-detail-contact-grid">


                <a
                    class="locataire-detail-contact-card"
                    href="mailto:<?= e($locataire['email']) ?>"
                >

                    <div class="contact-card-icon">

                        <i class="fa-solid fa-envelope"></i>

                    </div>

                    <div>

                        <span>
                            Adresse e-mail
                        </span>

                        <strong>
                            <?= valeurOuTiret($locataire['email']) ?>
                        </strong>

                    </div>

                </a>



                <a
                    class="locataire-detail-contact-card"
                    href="tel:<?= e($locataire['telephone']) ?>"
                >

                    <div class="contact-card-icon">

                        <i class="fa-solid fa-phone"></i>

                    </div>

                    <div>

                        <span>
                            Téléphone
                        </span>

                        <strong>
                            <?= valeurOuTiret($locataire['telephone']) ?>
                        </strong>

                    </div>

                </a>


            </div>


        </section>



        <!-- =====================================================
             INFORMATIONS FINANCIÈRES
        ====================================================== -->

        <section class="locataire-detail-section">


            <div class="locataire-detail-section-header">

                <div>

                    <h2>
                        Situation financière
                    </h2>

                    <p>
                        Informations financières enregistrées.
                    </p>

                </div>


                <div class="locataire-detail-section-icon">

                    <i class="fa-solid fa-wallet"></i>

                </div>

            </div>



            <div class="locataire-detail-grid">


                <div class="locataire-detail-field">

                    <span>
                        Revenu mensuel
                    </span>

                    <strong>
                        <?= formatMoney($locataire['revenu_mensuel']) ?>
                    </strong>

                </div>


                <div class="locataire-detail-field">

                    <span>
                        Fourchette de revenus
                    </span>

                    <strong>
                        <?= valeurOuTiret(
                            $locataire['revenu_mensuel_fourchette']
                        ) ?>
                    </strong>

                </div>


            </div>


        </section>



        <!-- =====================================================
             CONTRATS
        ====================================================== -->

        <section class="locataire-detail-section">


            <div class="locataire-detail-section-header">

                <div>

                    <h2>
                        Contrats
                    </h2>

                    <p>
                        Contrats associés à ce locataire sur vos biens.
                    </p>

                </div>


                <div class="locataire-detail-section-icon">

                    <i class="fa-solid fa-file-signature"></i>

                </div>

            </div>



            <?php if (empty($contrats)): ?>


                <div class="locataire-detail-empty">

                    <i class="fa-solid fa-file-circle-xmark"></i>

                    <h3>
                        Aucun contrat
                    </h3>

                    <p>
                        Aucun contrat n'est actuellement associé à ce locataire.
                    </p>

                </div>


            <?php else: ?>


                <div class="locataire-contract-list">


                    <?php foreach ($contrats as $contrat): ?>


                        <article class="locataire-contract-detail-card">


                            <div class="locataire-contract-detail-header">


                                <div>


                                    <span class="locataire-contract-number">

                                        <i class="fa-solid fa-file-contract"></i>

                                        Contrat
                                        <?= e(
                                            $contrat['numero_contrat']
                                            ?: '#' . $contrat['id_contrat']
                                        ) ?>

                                    </span>


                                    <h3>

                                        <?= e(
                                            $contrat['titre_bien']
                                            ?: 'Bien immobilier'
                                        ) ?>

                                    </h3>


                                    <?php if (!empty($contrat['reference_bien'])): ?>

                                        <p>

                                            Référence :
                                            <strong>
                                                <?= e($contrat['reference_bien']) ?>
                                            </strong>

                                        </p>

                                    <?php endif; ?>


                                </div>



                                <span
                                    class="contract-status <?= e(
                                        contratStatutClass(
                                            $contrat['statut_contrat']
                                        )
                                    ) ?>"
                                >

                                    <?= e(
                                        contratStatutLabel(
                                            $contrat['statut_contrat']
                                        )
                                    ) ?>

                                </span>


                            </div>



                            <div class="locataire-contract-detail-grid">


                                <div>

                                    <span>
                                        Date de début
                                    </span>

                                    <strong>
                                        <?= formatDateFr(
                                            $contrat['date_debut']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Date de fin
                                    </span>

                                    <strong>
                                        <?= formatDateFr(
                                            $contrat['date_fin']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Date d'entrée
                                    </span>

                                    <strong>
                                        <?= formatDateFr(
                                            $contrat['date_entree']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Date de sortie
                                    </span>

                                    <strong>
                                        <?= formatDateFr(
                                            $contrat['date_sortie']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Loyer
                                    </span>

                                    <strong class="contract-price">
                                        <?= formatMoney(
                                            $contrat['loyer']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Charges
                                    </span>

                                    <strong>
                                        <?= formatMoney(
                                            $contrat['charges']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Dépôt de garantie
                                    </span>

                                    <strong>
                                        <?= formatMoney(
                                            $contrat['depot_garantie']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Échéance
                                    </span>

                                    <strong>

                                        <?php if (
                                            $contrat['jour_echeance'] !== null
                                            &&
                                            $contrat['jour_echeance'] !== ''
                                        ): ?>

                                            Le
                                            <?= e(
                                                (string) $contrat['jour_echeance']
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Fréquence de paiement
                                    </span>

                                    <strong>
                                        <?= valeurOuTiret(
                                            $contrat['frequence_paiement']
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Titulaire principal
                                    </span>

                                    <strong>

                                        <?php if (
                                            (int) $contrat['titulaire_principal'] === 1
                                        ): ?>

                                            Oui

                                        <?php else: ?>

                                            Non

                                        <?php endif; ?>

                                    </strong>

                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </section>



        <!-- =====================================================
             PIED
        ====================================================== -->

        <div class="locataire-detail-bottom">


            <a
                href="/LOKA/app/proprietaires/Locataires/index.php"
                class="locataire-detail-back-button"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Retour à la liste des locataires

            </a>


        </div>


    </main>


</div>


</body>

</html>