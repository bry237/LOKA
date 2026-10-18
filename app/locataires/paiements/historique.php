<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

$paiements = $controller->getMyPaiements();

$totalPaye = $controller->getTotalPaye();
$nombrePaiements = $controller->getNombrePaiements();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Historique des paiements - LOKA</title>

    <link rel="stylesheet"
          href="paiement.css">

    <style>

        .history-page {
            padding: 35px 34px;
        }

        .history-page-header {
            margin-bottom: 25px;
        }

        .history-page-header h1 {
            margin-bottom: 7px;
            color: #043f42;
            font-size: 27px;
        }

        .history-page-header p {
            color: #687878;
            font-size: 13px;
        }

        .history-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .history-summary-card {
            padding: 20px;
            background: white;
            border: 1px solid #dde8e8;
            border-radius: 14px;
        }

        .history-summary-card span {
            display: block;
            margin-bottom: 6px;
            color: #687878;
            font-size: 12px;
        }

        .history-summary-card strong {
            color: #043f42;
            font-size: 20px;
        }

        .history-table-container {
            background: white;
            border: 1px solid #dde8e8;
            border-radius: 14px;
            overflow-x: auto;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            padding: 15px;
            background: #effbfa;
            color: #043f42;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
        }

        .history-table td {
            padding: 15px;
            border-top: 1px solid #edf2f2;
            color: #687878;
            font-size: 12px;
        }

        .history-table td strong {
            color: #043f42;
        }

        .empty-history {
            padding: 45px 20px;
            text-align: center;
        }

        .empty-history div {
            margin-bottom: 10px;
            font-size: 32px;
        }

        .empty-history h3 {
            margin-bottom: 6px;
            color: #043f42;
            font-size: 17px;
        }

        .empty-history p {
            color: #687878;
            font-size: 13px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0f8b8d;
            font-size: 13px;
            font-weight: 600;
        }

        .back-link:hover {
            color: #075e63;
        }

        @media (max-width: 650px) {

            .history-page {
                padding: 25px 20px;
            }

            .history-summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

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

<div class="main-content">

    <header class="top-header">

        <div class="welcome">

            <h1>
                Bonjour Pascale
                <span class="wave">👋</span>
            </h1>

            <p>
                Bienvenue dans votre espace locataire
            </p>

        </div>

        <div class="user-information">

            <div class="avatar">
                PN
            </div>

            <div class="user-name">

                <strong>Pascale NGASSA</strong>

                <span>
                    Locataire
                </span>

            </div>

        </div>

    </header>


    <main class="page-container">

        <section class="history-page">

           <a href="paiement.php"
               class="back-link">
                ← Retour à mes paiements
            </a>


            <div class="history-page-header">

                <span class="section-label">
                    MON ESPACE
                </span>

                <h1>
                    Historique des paiements
                </h1>

                <p>
                    Consultez l'ensemble de vos paiements et règlements.
                </p>

            </div>


            <div class="history-summary">

                <div class="history-summary-card">

                    <span>
                        Total payé
                    </span>

                    <strong>
                        <?= number_format(
                            $totalPaye,
                            2,
                            ',',
                            ' '
                        ) ?> €
                    </strong>

                </div>


                <div class="history-summary-card">

                    <span>
                        Nombre de paiements
                    </span>

                    <strong>
                        <?= $nombrePaiements ?>
                    </strong>

                </div>

            </div>


            <?php if (empty($paiements)): ?>

                <div class="history-table-container">

                    <div class="empty-history">

                        <div>
                            💳
                        </div>

                        <h3>
                            Aucun paiement
                        </h3>

                        <p>
                            Vous n'avez encore aucun paiement enregistré.
                        </p>

                    </div>

                </div>

            <?php else: ?>

                <div class="history-table-container">

                    <table class="history-table">

                        <thead>

                            <tr>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Référence
                                </th>

                                <th>
                                    Mode
                                </th>

                                <th>
                                    Montant
                                </th>

                                <th>
                                    Statut
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($paiements as $paiement): ?>

                            <?php

                            $statut =
                                (string) ($paiement['statut'] ?? 'PENDING');

                            switch ($statut) {

                                case 'PAID':
                                    $statutLabel = 'Payé';
                                    $statutClass = 'status-paid';
                                    break;

                                case 'LATE':
                                    $statutLabel = 'En retard';
                                    $statutClass = 'status-late';
                                    break;

                                case 'CANCELLED':
                                    $statutLabel = 'Annulé';
                                    $statutClass = 'status-cancelled';
                                    break;

                                case 'REFUNDED':
                                    $statutLabel = 'Remboursé';
                                    $statutClass = 'status-cancelled';
                                    break;

                                default:
                                    $statutLabel = 'En attente';
                                    $statutClass = 'status-pending';
                                    break;
                            }

                            $datePaiement = !empty(
                                $paiement['date_paiement']
                            )
                                ? date(
                                    'd/m/Y',
                                    strtotime(
                                        $paiement['date_paiement']
                                    )
                                )
                                : '-';

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($datePaiement) ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $paiement[
                                                    'reference_transaction'
                                                ] ?? '-'
                                            )
                                        ) ?>
                                    </strong>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $paiement[
                                                'mode_paiement'
                                            ] ?? '-'
                                        )
                                    ) ?>
                                </td>

                                <td>

                                    <strong>
                                        <?= number_format(
                                            (float) (
                                                $paiement['montant'] ?? 0
                                            ),
                                            2,
                                            ',',
                                            ' '
                                        ) ?> €
                                    </strong>

                                </td>

                                <td>

                                    <span class="status-badge <?= $statutClass ?>">

                                        <?= htmlspecialchars(
                                            $statutLabel
                                        ) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>