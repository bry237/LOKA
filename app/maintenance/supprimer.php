<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_POST['id'])
    ? (int) $_POST['id']
    : (int) ($_GET['id'] ?? 0);

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}


/*
 * Vérifie que la demande appartient
 * bien au locataire connecté.
 */
$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}


/*
 * Seule une demande OPEN peut être supprimée.
 */
if ($intervention['statut'] !== 'OPEN') {
    header(
        'Location: detail.php?id=' .
        $idIntervention .
        '&error=non_supprimable'
    );
    exit;
}


/*
 * Suppression
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $success = $controller->deleteRequest(
        $idIntervention
    );

    if ($success) {
        header(
            'Location: index.php?success=supprimee'
        );
        exit;
    }
}


/*
 * Si on arrive ici en GET,
 * on affiche une confirmation.
 */

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$initiales = strtoupper(
    substr($prenom, 0, 1) .
    substr($nom, 0, 1)
);

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
        Supprimer la demande | LOKA
    </title>

    <link
        rel="stylesheet"
        href="../locataires/locataire.css"
    >

    <link
        rel="stylesheet"
        href="maintenance.css"
    >

</head>

<body>

<div class="page-container">

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
                Recherche
            </a>

            <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>

            <a href="/LOKA/app/locataires/paiements/paiement.ph">
                <span class="nav-icon">💳</span>
                <span>Mes paiements</span>
            </a>

            <a href="index.php" class="active">
                <span class="nav-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a href="../documents/index.php">
                <span class="nav-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="../locataires/notifications/notifications.php">
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


    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Maintenance
                </h2>

                <p>
                    Suppression d'une demande.
                </p>

            </div>

            <div class="user-info">

                <div class="avatar">
                    <?= e($initiales) ?>
                </div>

                <div class="user-details">

                    <strong>
                        <?= e($prenom . ' ' . $nom) ?>
                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <section class="housing-page">

            <div class="maintenance-confirm-card">

                <div class="housing-card-header">

                    <span class="section-label">
                        SUPPRESSION
                    </span>

                    <h2>
                        Supprimer cette demande ?
                    </h2>

                    <p>
                        Cette action est définitive.
                    </p>

                </div>


                <div class="housing-card-divider"></div>


                <div class="maintenance-confirm-content">

                    <h3>
                        <?= e($intervention['titre']) ?>
                    </h3>

                    <p>
                        <?= nl2br(
                            e($intervention['description'])
                        ) ?>
                    </p>

                    <div class="maintenance-alert maintenance-alert-error">

                        La suppression supprimera également
                        les commentaires et documents éventuellement
                        associés à cette intervention.

                    </div>

                </div>


                <form
                    method="POST"
                    class="maintenance-form-actions"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= $idIntervention ?>"
                    >

                    <a
                        href="detail.php?id=<?= $idIntervention ?>"
                        class="maintenance-detail-btn"
                    >
                        Annuler
                    </a>

                    <button
                        type="submit"
                        class="maintenance-detail-btn"
                    >
                        Supprimer
                    </button>

                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>