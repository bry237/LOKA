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
        Modifier la demande | LOKA
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
                Recherche
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

            <a href="index.php" class="active">
                <span class="nav-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
                <span>Maintenance</span>
            </a>

            <a href="../documents/index.php">
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
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg> Retour au détail
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