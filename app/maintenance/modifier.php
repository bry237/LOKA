<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_POST['id_intervention'] ?? 0);

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}


/*
 * Vérification de l'intervention
 */
$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}


/*
 * Une intervention ne peut être modifiée
 * que lorsqu'elle est encore ouverte.
 */
if ($intervention['statut'] !== 'OPEN') {
    header(
        'Location: detail.php?id=' . $idIntervention .
        '&error=non_modifiable'
    );
    exit;
}


/*
 * Locataire connecté
 */
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}


/*
 * Traitement du formulaire
 */
$errors = [];

$titre = $intervention['titre'];
$description = $intervention['description'];
$priorite = $intervention['priorite'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorite = $_POST['priorite'] ?? 'MEDIUM';


    /*
     * Validation
     */
    if ($titre === '') {
        $errors[] = 'Le titre est obligatoire.';
    }

    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    }

    $prioritesAutorisees = [
        'LOW',
        'MEDIUM',
        'HIGH',
        'URGENT'
    ];

    if (!in_array($priorite, $prioritesAutorisees, true)) {
        $errors[] = 'La priorité sélectionnée est invalide.';
    }


    /*
     * Mise à jour
     */
    if (empty($errors)) {

        $success = $controller->updateRequest(
            $idIntervention,
            $titre,
            $description,
            $priorite
        );

        if ($success) {

            header(
                'Location: detail.php?id=' .
                $idIntervention .
                '&success=modifiee'
            );

            exit;
        }

        $errors[] =
            'La demande n\'a pas pu être modifiée. ' .
            'Elle a peut-être déjà été prise en charge.';
    }
}


/*
 * Helpers
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
 * Locataire
 */
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
        Modifier la demande - LOKA
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


    <!-- SIDEBAR -->

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


    <!-- CONTENU -->

    <main class="main-content">


        <!-- HEADER -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Maintenance
                </h2>

                <p>
                    Modifier votre demande de maintenance.
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


        <!-- PAGE -->

        <section class="housing-page">


            <a
                href="detail.php?id=<?= $idIntervention ?>"
                class="maintenance-back-link"
            >
                ← Retour au détail
            </a>


            <div class="maintenance-form-card">


                <div class="housing-card-header">

                    <span class="section-label">
                        MAINTENANCE
                    </span>

                    <h2>
                        Modifier la demande
                    </h2>

                    <p>
                        Modifiez les informations de votre demande
                        de maintenance.
                    </p>

                </div>


                <div class="housing-card-divider"></div>


                <?php if (!empty($errors)): ?>

                    <div class="maintenance-alert maintenance-alert-error">

                        <?php foreach ($errors as $error): ?>

                            <div>
                                <?= e($error) ?>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <form method="POST">

                    <input
                        type="hidden"
                        name="id_intervention"
                        value="<?= $idIntervention ?>"
                    >


                    <!-- TITRE -->

                    <div class="form-group">

                        <label for="titre">
                            Titre du problème
                        </label>

                        <input
                            type="text"
                            id="titre"
                            name="titre"
                            maxlength="200"
                            value="<?= e($titre) ?>"
                            required
                        >

                    </div>


                    <!-- PRIORITÉ -->

                    <div class="form-group">

                        <label for="priorite">
                            Priorité
                        </label>

                        <select
                            id="priorite"
                            name="priorite"
                            required
                        >

                            <option
                                value="LOW"
                                <?= $priorite === 'LOW'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Faible
                            </option>

                            <option
                                value="MEDIUM"
                                <?= $priorite === 'MEDIUM'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Moyenne
                            </option>

                            <option
                                value="HIGH"
                                <?= $priorite === 'HIGH'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Élevée
                            </option>

                            <option
                                value="URGENT"
                                <?= $priorite === 'URGENT'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Urgente
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="description">
                            Description du problème
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="7"
                            required
                        ><?= e($description) ?></textarea>

                    </div>


                    <!-- BOUTONS -->

                    <div class="maintenance-form-actions">

                        <a
                            href="detail.php?id=<?= $idIntervention ?>"
                            class="maintenance-detail-btn"
                        >
                            Annuler
                        </a>

                        <button
                            type="submit"
                            class="maintenance-btn"
                        >
                            Enregistrer les modifications
                        </button>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>