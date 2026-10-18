<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$locataire = $controller->getCurrentLocataire();
$housing = $controller->getCurrentHousing();

$error = '';

/*
 * Traitement du formulaire
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorite = $_POST['priorite'] ?? 'MEDIUM';

    if ($titre === '') {

        $error = 'Veuillez renseigner le titre du problème.';

    } elseif ($description === '') {

        $error = 'Veuillez décrire le problème.';

    } else {

        $success = $controller->createRequest(
            $titre,
            $description,
            $priorite
        );

        if ($success) {

            header('Location: index.php');
            exit;

        }

        $error = 'Impossible de créer la demande de maintenance.';
    }
}


/*
 * Informations du locataire
 */
$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$initiales = '';

if ($prenom !== '') {
    $initiales .= strtoupper(substr($prenom, 0, 1));
}

if ($nom !== '') {
    $initiales .= strtoupper(substr($nom, 0, 1));
}

if ($initiales === '') {
    $initiales = 'LO';
}


/*
 * Fonction d'échappement HTML
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
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

    <title>Signaler un problème - LOKA</title>

    <!-- CSS commun LOKA -->
    <link
        rel="stylesheet"
        href="../locataires/locataire.css"
    >

    <!-- CSS maintenance -->
    <link
        rel="stylesheet"
        href="maintenance.css"
    >

</head>

<body>

<div class="page-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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


            <a href="../contrats/index.php">

                <span class="nav-icon">📄</span>

                <span>Mon contrat</span>

            </a>


            <a href="/LOKA/app/locataires/paiements/paiement.ph">

                <span class="nav-icon">💳</span>

                <span>Mes paiements</span>

            </a>


            <a
                href="index.php"
                class="active"
            >

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


    <!-- =====================================================
         CONTENU PRINCIPAL
    ====================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">

            <div class="topbar-left">

                <h2>
                    Maintenance
                </h2>

                <p>
                    Signalez un problème dans votre logement.
                </p>

            </div>


            <div class="user-info">

                <div class="avatar">

                    <?= e($initiales) ?>

                </div>


                <div class="user-details">

                    <strong>

                        <?= e(
                            trim($prenom . ' ' . $nom)
                        ) ?>

                    </strong>

                    <span>
                        Locataire
                    </span>

                </div>

            </div>

        </header>


        <!-- =================================================
             CONTENU
        ================================================== -->

        <section class="housing-page">


            <!-- =================================================
                 CARTE FORMULAIRE
            ================================================== -->

            <div class="maintenance-form-card">


                <!-- EN-TÊTE -->

                <div class="maintenance-card-header">

                    <span class="section-label">

                        MAINTENANCE

                    </span>
                    <br>

                    <h2>

                        Signaler un problème

                    </h2>

                    <br>
                    <p>

                        Décrivez le problème rencontré
                        dans votre logement afin que
                        nous puissions intervenir.

                    </p>

                </div>


                <div class="maintenance-card-divider"></div>


                <!-- =================================================
                     MESSAGE D'ERREUR
                ================================================== -->

                <?php if ($error !== ''): ?>

                    <div
                        class="maintenance-alert maintenance-alert-error"
                    >

                        <?= e($error) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     FORMULAIRE
                ================================================== -->

                <form
                    method="POST"
                    action=""
                >

                    <br>
                    <!-- TITRE -->

                    <div class="form-group">

                        <label for="titre">

                            Titre du problème

                        </label>


                        <input
                            type="text"
                            id="titre"
                            name="titre"
                            maxlength="255"
                            value="<?= e(
                                $_POST['titre'] ?? ''
                            ) ?>"
                            placeholder="Ex : Fuite d'eau dans la salle de bain"
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
                        >

                            <option
                                value="LOW"
                                <?= (
                                    ($_POST['priorite'] ?? '')
                                    === 'LOW'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Faible
                            </option>


                            <option
                                value="MEDIUM"
                                <?= (
                                    ($_POST['priorite'] ?? 'MEDIUM')
                                    === 'MEDIUM'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Moyenne
                            </option>


                            <option
                                value="HIGH"
                                <?= (
                                    ($_POST['priorite'] ?? '')
                                    === 'HIGH'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Élevée
                            </option>


                            <option
                                value="URGENT"
                                <?= (
                                    ($_POST['priorite'] ?? '')
                                    === 'URGENT'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
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
                            placeholder="Décrivez précisément le problème rencontré..."
                            required
                        ><?= e(
                            $_POST['description'] ?? ''
                        ) ?></textarea>

                    </div>


                    <!-- =================================================
                         LOGEMENT CONCERNÉ
                    ================================================== -->

                    <?php if ($housing): ?>

                        <div class="maintenance-housing-info">

                            <h3>
                                Logement concerné
                            </h3>


                            <p>

                                <strong>

                                    <?= e(
                                        $housing['titre']
                                        ?? 'Mon logement'
                                    ) ?>

                                </strong>

                            </p>


                            <p>

                                <?= e(
                                    $housing['ligne1']
                                    ?? ''
                                ) ?>


                                <?php if (!empty($housing['ligne2'])): ?>

                                    <br>

                                    <?= e(
                                        $housing['ligne2']
                                    ) ?>

                                <?php endif; ?>


                                <br>

                                <?= e(
                                    $housing['code_postal']
                                    ?? ''
                                ) ?>

                                <?= e(
                                    $housing['ville']
                                    ?? ''
                                ) ?>

                            </p>

                        </div>

                    <?php else: ?>

                        <div
                            class="maintenance-alert maintenance-alert-error"
                        >

                            Aucun logement actif n'est associé
                            à votre compte.

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         BOUTONS
                    ================================================== -->

                    <div
                        style="
                            display: flex;
                            justify-content: flex-end;
                            align-items: center;
                            gap: 12px;
                            margin-top: 25px;
                        "
                    >

                        <a
                            href="index.php"
                            class="maintenance-detail-btn"
                        >
                            Annuler
                        </a>


                        <button
                            type="submit"
                            class="maintenance-btn"
                            <?= !$housing ? 'disabled' : '' ?>
                        >

                            Signaler le problème

                        </button>

                    </div>


                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>