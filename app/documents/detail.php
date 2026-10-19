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
        - LOKA
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
                <span class="nav-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="../locataires/detail.php">
                <span class="nav-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="../locataires/logement.php">
                <span class="nav-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="../locataires/recherche.php">
                <span>🔎</span>
                <span>Recherche</span>
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="../maintenance/index.php">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a
                href="index.php"
                class="active"
            >
                <span class="nav-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="../locataires/notifications/notification.php">
                <span class="nav-icon">🔔</span>
                <span>Notifications</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="/LOKA/app/authentification/deconnexion.php">
                <span class="menu-icon">↪</span>
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
                        📄
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
                        ← Retour aux documents
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