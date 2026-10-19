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