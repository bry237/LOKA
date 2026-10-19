<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';

$controller = new DocumentController();


/* =========================================================
   ID DU DOCUMENT
   ========================================================= */

$idDocument = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idDocument) {
    http_response_code(400);
    exit('Document invalide.');
}


/* =========================================================
   DOCUMENT
   ========================================================= */

$document = $controller->getDocument(
    (int) $idDocument
);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès refusé.');
}


/* =========================================================
   LOCATAIRE
   ========================================================= */

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}


/* =========================================================
   FONCTIONS
   ========================================================= */

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d/m/Y à H:i', $timestamp);
}


function formatFileSize($bytes): string
{
    if ($bytes === null || $bytes === '') {
        return '—';
    }

    $bytes = (int) $bytes;

    if ($bytes < 1024) {
        return $bytes . ' o';
    }

    if ($bytes < 1024 * 1024) {
        return number_format(
            $bytes / 1024,
            1,
            ',',
            ' '
        ) . ' Ko';
    }

    if ($bytes < 1024 * 1024 * 1024) {
        return number_format(
            $bytes / (1024 * 1024),
            1,
            ',',
            ' '
        ) . ' Mo';
    }

    return number_format(
        $bytes / (1024 * 1024 * 1024),
        1,
        ',',
        ' '
    ) . ' Go';
}


/* =========================================================
   UTILISATEUR
   ========================================================= */

$prenom = e($locataire['prenom'] ?? '');
$nom = e($locataire['nom'] ?? '');

$initiales = '';

if ($prenom !== '') {
    $initiales .= strtoupper(
        substr($prenom, 0, 1)
    );
}

if ($nom !== '') {
    $initiales .= strtoupper(
        substr($nom, 0, 1)
    );
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
        <?= e(
            $document['nom']
            ?? $document['nom_original']
            ?? 'Document'
        ) ?>
        | LOKA
    </title>

    <link
        rel="stylesheet"
        href="documents.css"
    >

</head>

<body>

<div class="page-container">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav>

            <a href="../locataires/index.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                <span>Accueil</span>
            </a>

            <a href="../locataires/detail.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                <span>Mon profil</span>
            </a>

            <a href="../locataires/logement.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
                <span>Mon logement</span>
            </a>

            <a href="../locataires/recherche.php">
                <span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                <span>Recherche</span>
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>

                <span>
                    Mon contrat
                </span>
            </a>

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span>Mes paiements</span>
            </a>

            <a href="../maintenance/index.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
                <span>Maintenance</span>
            </a>

            <a
                href="index.php"
                class="active"
            >
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>

            <a href="../locataires/notifications/notification.php">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                <span>Notifications</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span>Déconnexion</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         CONTENU
         ===================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Détail du document
                </h2>

                <p>
                    Consultez les informations de votre document.
                </p>

            </div>


            <div class="user-info">

                <div class="avatar">
                    <?= e($initiales) ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= $prenom . ' ' . $nom ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             PAGE
             ================================================= -->

        <section class="documents-page">


            <div class="document-detail-card">


                <!-- HEADER -->

                <div class="document-detail-header">

                    <div class="document-detail-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                    </div>

                    <div class="document-detail-title">

                        <h2>
                            <?= e(
                                $document['nom']
                                ?? $document['nom_original']
                                ?? 'Document'
                            ) ?>
                        </h2>

                        <p>
                            Informations du document
                        </p>

                    </div>

                </div>


                <!-- INFORMATIONS -->

                <div class="document-detail-content">


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Nom
                        </span>

                        <span class="document-info-value">
                            <?= e(
                                $document['nom']
                                ?? '—'
                            ) ?>
                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Nom original
                        </span>

                        <span class="document-info-value">
                            <?= e(
                                $document['nom_original']
                                ?? '—'
                            ) ?>
                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Type de fichier
                        </span>

                        <span class="document-info-value">
                            <?= e(
                                $document['mime_type']
                                ?? '—'
                            ) ?>
                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Taille
                        </span>

                        <span class="document-info-value">
                            <?= formatFileSize(
                                $document['taille_octets']
                                ?? null
                            ) ?>
                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Version
                        </span>

                        <span class="document-info-value">

                            v<?= (int) (
                                $document['version'] ?? 1
                            ) ?>

                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Date d'ajout
                        </span>

                        <span class="document-info-value">
                            <?= dateFr(
                                $document['created_at']
                                ?? null
                            ) ?>
                        </span>

                    </div>


                    <div class="document-info-row">

                        <span class="document-info-label">
                            Dernière modification
                        </span>

                        <span class="document-info-value">
                            <?= dateFr(
                                $document['updated_at']
                                ?? null
                            ) ?>
                        </span>

                    </div>


                </div>


                <!-- ACTIONS -->

                <div class="document-detail-footer">

                    <a
                        href="index.php"
                        class="btn-back"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Retour aux documents
                    </a>

                    <a
                        href="telecharger.php?id=<?= (int) $document['id_document'] ?>"
                        class="document-btn document-btn-primary"
                    >
                        Télécharger le document
                    </a>

                    <a
                    href="supprimer.php?id=<?= (int) $document['id_document'] ?>"
                    class="document-btn document-btn-danger"
                    >
                        Supprimer
                    </a>

                </div>


            </div>

        </section>

    </main>

</div>

</body>

</html>