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
                <span class="menu-icon">⌂</span>
                <span>Accueil</span>
            </a>

            <a href="/LOKA/app/locataires/detail.php">
                <span class="menu-icon">👤</span>
                <span>Mon profil</span>
            </a>

            <a href="/LOKA/app/locataires/logement.php">
                <span class="menu-icon">🏠</span>
                <span>Mon logement</span>
            </a>

            <a href="/LOKA/app/locataires/recherche.php">
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

            <a href="/LOKA/app/locataires/paiements/paiement.php">
                <span class="menu-icon">💳</span>
                <span>Paiements</span>
            </a>

             <a href="/LOKA/app/maintenance/index.php">
                <span class="menu-icon">🔧</span>
                <span>Maintenance</span>
            </a>

            <a href="/LOKA/app/documents/index.php">
                <span class="menu-icon">📁</span>
                <span>Documents</span>
            </a>

            <a href="/LOKA/app/locataires/quittances/index.php"
               class="active">
                <span class="menu-icon">🧾</span>
                <span>Quittances</span>
            </a>

            <a href="/LOKA/app/locataires/notifications/notification.php">
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
                        🧾
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
                                        🧾
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