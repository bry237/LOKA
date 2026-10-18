<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

try {
    $locataire = $controller->getCurrentLocataire();

    $echeances = $controller->getMyEcheances();
    $paiements = $controller->getMyPaiements();

    $totalPaye = $controller->getTotalPaye();

} catch (Throwable $e) {
    http_response_code(500);
    die(
        'Une erreur est survenue lors du chargement de vos paiements : '
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}


/*
|--------------------------------------------------------------------------
| Informations du locataire
|--------------------------------------------------------------------------
*/

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));

$nomComplet = trim($prenom . ' ' . $nom);

if ($nomComplet === '') {
    $nomComplet = 'Locataire';
}

$initiales = '';

if ($prenom !== '') {
    $initiales .= strtoupper(substr($prenom, 0, 1));
}

if ($nom !== '') {
    $initiales .= strtoupper(substr($nom, 0, 1));
}

if ($initiales === '') {
    $initiales = 'PN';
}


/*
|--------------------------------------------------------------------------
| Calcul des montants
|--------------------------------------------------------------------------
*/

$totalARegler = 0.0;
$prochaineEcheance = null;

foreach ($echeances as $echeance) {

    $statut = strtoupper((string) ($echeance['statut'] ?? ''));

    /*
     * Seules les échéances qui ne sont pas payées
     * sont considérées comme restant à régler.
     */
    if ($statut !== 'PAID') {

        $montantTotal = (float) ($echeance['montant_total'] ?? 0);

        $montantPaye = 0.0;

        try {
            $montantPaye = $controller->getMontantRestant(
                (int) $echeance['id_echeance']
            );

            /*
             * getMontantRestant() retourne directement
             * le montant restant.
             */
            $montantRestant = $montantPaye;

        } catch (Throwable $e) {
            $montantRestant = $montantTotal;
        }

        $totalARegler += max(0, $montantRestant);
    }

    /*
     * Recherche de la prochaine échéance.
     */
    if ($statut !== 'PAID') {

        $dateEcheance = $echeance['date_echeance'] ?? null;

        if ($dateEcheance !== null) {

            if (
                $prochaineEcheance === null ||
                strtotime((string) $dateEcheance)
                < strtotime((string) $prochaineEcheance['date_echeance'])
            ) {
                $prochaineEcheance = $echeance;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fonctions d'affichage
|--------------------------------------------------------------------------
*/

function formatMoney(float $montant): string
{
    return number_format(
        $montant,
        2,
        ',',
        ' '
    ) . ' €';
}


function formatDateFr(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}


function getStatutLabel(string $statut): string
{
    return match (strtoupper($statut)) {

        'PAID' => 'Payée',

        'PENDING' => 'À payer',

        'LATE' => 'En retard',

        'CANCELLED' => 'Annulée',

        'REFUNDED' => 'Remboursée',

        default => ucfirst(strtolower($statut))
    };
}


function getStatutClass(string $statut): string
{
    return match (strtoupper($statut)) {

        'PAID' => 'status-paid',

        'LATE' => 'status-late',

        'CANCELLED' => 'status-cancelled',

        default => 'status-pending'
    };
}


/*
|--------------------------------------------------------------------------
| Prochaine échéance
|--------------------------------------------------------------------------
*/

$prochaineDate = '-';
$prochaineMontant = 0.0;

if ($prochaineEcheance !== null) {

    $prochaineDate = formatDateFr(
        (string) ($prochaineEcheance['date_echeance'] ?? '')
    );

    $prochaineMontant = (float) (
        $prochaineEcheance['montant_total'] ?? 0
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

    <title>Mes paiements - LOKA</title>

    <link
        rel="stylesheet"
        href="paiement.css"
    >

</head>


<body>


<!-- ============================================================
     SIDEBAR
============================================================ -->

<aside class="sidebar">

    <div class="logo">
        LOKA
    </div>


    <nav class="sidebar-menu">

        <a
            href="../index.php"
            class="menu-item"
        >
            <span class="menu-icon">⌂</span>
            <span>Accueil</span>
        </a>


        <a
            href="../detail.php"
            class="menu-item"
        >
            <span class="menu-icon">👤</span>
            <span>Mon profil</span>
        </a>


        <a
            href="../logement.php"
            class="menu-item"
        >
            <span class="menu-icon">🏠</span>
            <span>Mon logement</span>
        </a>


        <a
            href="../recherche.php"
            class="menu-item"
        >
            <span class="menu-icon">🔎</span>
            <span>Recherche</span>
        </a>


       <a href="/LOKA/app/locataires/contrats/index.php">

                <span class="nav-icon">
                    📄
                </span>

                <span>
                    Mon contrat
                </span>


        <!-- PAGE ACTIVE -->

        <a
            href="paiement.php"
            class="menu-item active"
        >
            <span class="menu-icon">💳</span>
            <span>Mes paiements</span>
        </a>


        <a
            href="/LOKA/app/maintenance/index.php"
            class="menu-item"
        >
            <span class="menu-icon">🔧</span>
            <span>Maintenance</span>
        </a>


        <a
            href="/LOKA/app/documents/index.php"
            class="menu-item"
        >
            <span class="menu-icon">📁</span>
            <span>Documents</span>
        </a>

        <a href="/LOKA/app/locataires/quittances/index.php"
               >
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>


        <a
            href="../notifications/notification.php"
            class="menu-item"
        >
            <span class="menu-icon">🔔</span>
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



<!-- ============================================================
     CONTENU PRINCIPAL
============================================================ -->

<main class="main-content">


    <!-- ========================================================
         HEADER
    ========================================================= -->

    <header class="top-header">

        <div class="welcome">

            <h1>
                Bonjour <?= htmlspecialchars($prenom ?: 'Pascale', ENT_QUOTES, 'UTF-8') ?>
                <span class="wave">👋</span>
            </h1>

            <p>
                Bienvenue dans votre espace locataire
            </p>

        </div>


        <div class="user-information">

            <div class="avatar">
                <?= htmlspecialchars($initiales, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="user-name">

                <strong>
                    <?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>
                </strong>

                <span>
                    Locataire
                </span>

            </div>

        </div>

    </header>



    <!-- ========================================================
         PAGE
    ========================================================= -->

    <section class="page-container">


        <!-- ====================================================
             INTRODUCTION
        ==================================================== -->

        <section class="page-introduction">

            <div>

                <span class="section-label">
                    MES PAIEMENTS
                </span>

                <h2>
                    Mes paiements
                </h2>

                <p>
                    Consultez vos échéances, vos paiements et
                    l'historique de vos règlements.
                </p>

            </div>

        </section>



        <!-- ====================================================
             STATISTIQUES
        ==================================================== -->

        <section class="stats-grid">


            <!-- À PAYER -->

            <div class="stat-card">

                <div class="stat-icon">
                    💳
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        À payer
                    </span>

                    <strong class="stat-value">
                        <?= formatMoney($totalARegler) ?>
                    </strong>

                </div>

            </div>



            <!-- PROCHAINE ÉCHÉANCE -->

            <div class="stat-card">

                <div class="stat-icon">
                    📅
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Prochaine échéance
                    </span>

                    <strong class="stat-value small">
                        <?= htmlspecialchars($prochaineDate, ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <?php if ($prochaineEcheance !== null): ?>

                        <span class="stat-description">
                            <?= formatMoney($prochaineMontant) ?>
                        </span>

                    <?php else: ?>

                        <span class="stat-description">
                            Aucune échéance
                        </span>

                    <?php endif; ?>

                </div>

            </div>



            <!-- TOTAL PAYÉ -->

            <div class="stat-card">

                <div class="stat-icon">
                    💰
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Total payé
                    </span>

                    <strong class="stat-value">
                        <?= formatMoney($totalPaye) ?>
                    </strong>

                </div>

            </div>

        </section>



        <!-- ====================================================
             MES ÉCHÉANCES
        ==================================================== -->

        <section class="content-section">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        MES ÉCHÉANCES
                    </span>

                    <h2>
                        Échéances de paiement
                    </h2>

                </div>

            </div>



            <?php if (empty($echeances)): ?>

                <div class="empty-card">

                    <div class="empty-icon">
                        💳
                    </div>

                    <h3>
                        Aucune échéance
                    </h3>

                    <p>
                        Vous n'avez actuellement aucune échéance
                        de paiement.
                    </p>

                </div>

            <?php else: ?>


                <div class="payment-list">


                    <?php foreach ($echeances as $echeance): ?>

                        <?php

                        $idEcheance = (int) $echeance['id_echeance'];

                        $statut = strtoupper(
                            (string) ($echeance['statut'] ?? 'PENDING')
                        );

                        $montant = (float) (
                            $echeance['montant_total'] ?? 0
                        );

                        try {

                            $montantRestant =
                                $controller->getMontantRestant(
                                    $idEcheance
                                );

                        } catch (Throwable $e) {

                            $montantRestant = $montant;
                        }

                        ?>

                        <article class="payment-card">


                            <div class="payment-card-left">

                                <div class="payment-icon">
                                    💳
                                </div>


                                <div class="payment-information">

                                    <h3>
                                        Échéance de loyer
                                    </h3>

                                    <p class="payment-period">

                                        Du
                                        <?= formatDateFr(
                                            $echeance['periode_debut'] ?? null
                                        ) ?>

                                        au

                                        <?= formatDateFr(
                                            $echeance['periode_fin'] ?? null
                                        ) ?>

                                    </p>


                                    <div class="payment-details">

                                        <span>
                                            Échéance :
                                            <strong>
                                                <?= formatDateFr(
                                                    $echeance['date_echeance'] ?? null
                                                ) ?>
                                            </strong>
                                        </span>

                                        <span>
                                            Montant :
                                            <strong>
                                                <?= formatMoney($montant) ?>
                                            </strong>
                                        </span>

                                    </div>

                                </div>

                            </div>



                            <div class="payment-card-right">


                                <span
                                    class="status-badge <?= getStatutClass($statut) ?>"
                                >
                                    <?= htmlspecialchars(
                                        getStatutLabel($statut),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>


                                <?php if ($statut !== 'PAID'): ?>

                                    <strong class="remaining-amount">

                                        <?= formatMoney(
                                            max(0, $montantRestant)
                                        ) ?>

                                    </strong>

                                <?php endif; ?>


                                <div class="payment-actions">

                                    <a
                                        href="detail.php?id=<?= $idEcheance ?>"
                                        class="button button-secondary"
                                    >
                                        Voir le détail
                                    </a>


                                    <?php if ($statut !== 'PAID'): ?>

                                        <a
                                            href="payer.php?id=<?= $idEcheance ?>"
                                            class="button button-primary"
                                        >
                                            Payer
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>


                </div>

            <?php endif; ?>

        </section>



        <!-- ====================================================
             HISTORIQUE
        ==================================================== -->

        <section class="content-section history-section">

            <div class="history-card">

                <div class="history-icon">
                    📋
                </div>


                <div class="history-information">

                    <span class="section-label">
                        HISTORIQUE
                    </span>

                    <h2>
                        Mes paiements effectués
                    </h2>

                    <p>
                        Consultez l'ensemble de vos règlements
                        et leur statut.
                    </p>

                </div>


                <a
                    href="historique.php"
                    class="button button-primary"
                >
                    Voir mon historique
                </a>

            </div>

        </section>


    </section>

</main>


</body>

</html>