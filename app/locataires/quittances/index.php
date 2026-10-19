<?php

declare(strict_types=1);

require_once __DIR__ . '/QuittanceController.php';

$controller = new QuittanceController();

try {
    $quittances = $controller->getMyQuittances();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Une erreur est survenue lors du chargement de vos quittances.');
}

/**
 * Formatage d'une date.
 */
function formatDate(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Formatage d'un montant.
 */
function formatMontant($montant): string
{
    return number_format(
        (float) $montant,
        2,
        ',',
        ' '
    ) . ' €';
}

/**
 * Traduction du mode de paiement.
 */
function modePaiementLabel(?string $mode): string
{
    return match ($mode) {
        'CASH'          => 'Espèces',
        'BANK_TRANSFER' => 'Virement bancaire',
        'CARD'          => 'Carte bancaire',
        'CHEQUE'        => 'Chèque',
        'DIRECT_DEBIT'  => 'Prélèvement',
        'OTHER'         => 'Autre',
        default         => '-',
    };
}

$totalQuittances = count($quittances);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mes quittances | LOKA</title>

    <link rel="stylesheet" href="quittance.css">
</head>

<body>

<div class="layout">

    <!-- =========================
         SIDEBAR
    ========================== -->
    <aside class="sidebar">

        <div class="logo">
            LOKA
        </div>

        <nav class="sidebar-menu">

            <a href="/LOKA/app/locataires/index.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                <span>Accueil</span>
            </a>

            <a href="/LOKA/app/locataires/detail.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></span>
                <span>Mon profil</span>
            </a>

            <a href="/LOKA/app/locataires/logement.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg></span>
                <span>Mon logement</span>
            </a>

            <a href="/LOKA/app/locataires/recherche.php">
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
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span>Paiements</span>
            </a>

             <a href="/LOKA/app/maintenance/index.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></span>
                <span>Maintenance</span>
            </a>

            <a href="/LOKA/app/documents/index.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               class="active">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></span>
                <span>Quittances</span>
            </a>

            <a href="/LOKA/app/locataires/notifications/notification.php">
                <span class="menu-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
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


    <!-- =========================
         CONTENU PRINCIPAL
    ========================== -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="top-header">

            <div>
                <h1>Mes quittances</h1>

                <p>
                    Consultez et téléchargez vos quittances de loyer.
                </p>
            </div>

        </header>


        <!-- CONTENU -->
        <div class="page-container">

            <!-- STATISTIQUE -->
            <section class="summary-card">

                <div>
                    <span class="summary-label">
                        Nombre de quittances
                    </span>

                    <strong>
                        <?= $totalQuittances ?>
                    </strong>
                </div>

            </section>


            <?php if (empty($quittances)): ?>

                <!-- AUCUNE QUITTANCE -->
                <section class="empty-state">

                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg>
                    </div>

                    <h2>Aucune quittance disponible</h2>

                    <p>
                        Vous n'avez actuellement aucune quittance
                        disponible dans votre espace locataire.
                    </p>

                </section>

            <?php else: ?>

                <!-- LISTE DES QUITTANCES -->
                <section class="quittances-section">

                    <div class="section-header">

                        <div>
                            <h2>Mes quittances</h2>

                            <p>
                                Retrouvez ici vos quittances disponibles.
                            </p>
                        </div>

                    </div>


                    <div class="quittances-list">

                        <?php foreach ($quittances as $quittance): ?>

                            <article class="quittance-card">

                                <div class="quittance-main">

                                    <div class="quittance-icon">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg>
                                    </div>

                                    <div class="quittance-info">

                                        <h3>
                                            <?= htmlspecialchars(
                                                (string) (
                                                    $quittance['numero']
                                                    ?? 'Quittance'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </h3>

                                        <p class="quittance-period">

                                            Période :
                                            <strong>
                                                <?= formatDate(
                                                    $quittance['periode_debut']
                                                    ?? null
                                                ) ?>
                                            </strong>

                                            au

                                            <strong>
                                                <?= formatDate(
                                                    $quittance['periode_fin']
                                                    ?? null
                                                ) ?>
                                            </strong>

                                        </p>

                                        <p class="quittance-details">

                                            Montant :
                                            <strong>
                                                <?= formatMontant(
                                                    $quittance['montant']
                                                    ?? 0
                                                ) ?>
                                            </strong>

                                            <span class="separator">
                                                •
                                            </span>

                                            Émise le :
                                            <strong>
                                                <?= formatDate(
                                                    $quittance['date_emission']
                                                    ?? null
                                                ) ?>
                                            </strong>

                                        </p>

                                    </div>

                                </div>


                                <div class="quittance-actions">

                                    <a
                                        href="quittance-telecharger.php?id=<?= (int) ($quittance['id_paiement'] ?? 0) ?>"
                                        class="btn btn-primary"
                                    >
                                        ⬇ Télécharger
                                    </a>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>