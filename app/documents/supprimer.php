<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';

$controller = new DocumentController();

$idDocument = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idDocument) {
    http_response_code(400);
    exit('Document invalide.');
}

$document = $controller->getDocument($idDocument);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès refusé.');
}

/*
 * Confirmation de suppression
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $deletedDocument = $controller->deleteDocument(
        (int) $idDocument
    );

    if (!$deletedDocument) {
        http_response_code(404);
        exit('Document introuvable ou accès refusé.');
    }

    /*
     * Suppression du fichier physique
     */
    $chemin = $deletedDocument['chemin_stockage'];

    if ($chemin) {

        $projectRoot = dirname(__DIR__, 2);

        /*
         * Si le chemin enregistré est relatif
         */
        if (!str_starts_with($chemin, DIRECTORY_SEPARATOR)) {
            $chemin = $projectRoot . DIRECTORY_SEPARATOR . $chemin;
        }

        $realPath = realpath($chemin);

        if ($realPath && is_file($realPath)) {
            unlink($realPath);
        }
    }

    header('Location: index.php?deleted=1');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
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

    <title>Supprimer un document | LOKA</title>

    <link
        rel="stylesheet"
        href="documents.css"
    >

</head>

<body>

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

    </aside>


    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Mes documents</h1>
            </div>

        </header>


        <section class="documents-container">

            <div class="document-detail-card">

                <div class="document-detail-header">

                    <span class="section-label">
                        SUPPRESSION
                    </span>

                    <h2>
                        Supprimer le document
                    </h2>

                </div>


                <div class="document-form">

                    <p>
                        Voulez-vous vraiment supprimer
                        le document :
                    </p>

                    <p>
                        <strong>
                            <?= e(
                                $document['nom_original']
                                ?: $document['nom']
                            ) ?>
                        </strong>
                    </p>

                    <p class="form-warning">
                        Cette action est irréversible.
                    </p>


                    <form
                        method="POST"
                        action="supprimer.php?id=<?= (int) $idDocument ?>"
                    >

                        <div class="document-detail-footer">

                            <a
                                href="detail.php?id=<?= (int) $idDocument ?>"
                                class="document-btn"
                            >
                                Annuler
                            </a>

                            <button
                                type="submit"
                                class="document-btn document-btn-danger"
                            >
                                Supprimer définitivement
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </section>

    </main>

</body>

</html>