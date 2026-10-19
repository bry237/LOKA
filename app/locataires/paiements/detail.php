<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

$idEcheance = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

$echeance = $controller->getMyEcheance($idEcheance);

if ($echeance === null) {
    http_response_code(404);
    exit('Échéance introuvable.');
}

$montantRestant = $controller->getMontantRestant($idEcheance);

$statut = (string) ($echeance['statut'] ?? 'PENDING');

switch ($statut) {
    case 'PAID':
        $statutLabel = 'Payée';
        $statutClass = 'status-paid';
        break;

    case 'LATE':
        $statutLabel = 'En retard';
        $statutClass = 'status-late';
        break;

    case 'CANCELLED':
        $statutLabel = 'Annulée';
        $statutClass = 'status-cancelled';
        break;

    default:
        $statutLabel = 'À payer';
        $statutClass = 'status-pending';
        break;
}

$montantTotal = (float) ($echeance['montant_total'] ?? 0);

$dateDebut = !empty($echeance['periode_debut'])
    ? date('d/m/Y', strtotime($echeance['periode_debut']))
    : '-';

$dateFin = !empty($echeance['periode_fin'])
    ? date('d/m/Y', strtotime($echeance['periode_fin']))
    : '-';

$dateEcheance = !empty($echeance['date_echeance'])
    ? date('d/m/Y', strtotime($echeance['date_echeance']))
    : '-';

$retourPaiement = (string) ($_GET['paiement'] ?? '');

$messagesRetour = [
    'success' => ['flash-success', 'Paiement reçu, merci ! Votre quittance est disponible dans « Quittances ».'],
    'pending' => ['flash-info', 'Paiement en cours de confirmation par la banque. Cette page se mettra à jour dès sa validation.'],
    'cancel'  => ['flash-warning', 'Paiement annulé : aucun montant n’a été débité.'],
    'error'   => ['flash-error', 'Le paiement n’a pas pu être vérifié. Si vous avez été débité, contactez votre agence.'],
];

$loyer = (float) ($echeance['montant_loyer'] ?? 0);
$charges = (float) ($echeance['montant_charges'] ?? 0);
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Détail du paiement - LOKA</title>

    <link rel="stylesheet"
          href="paiement.css">

    <style>

        .payment-detail-container {
            padding: 35px 34px;
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

        .detail-card {
            background: white;
            border: 1px solid #dde8e8;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 3px 12px rgba(4, 63, 66, 0.04);
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dde8e8;
        }

        .detail-header h1 {
            color: #043f42;
            font-size: 24px;
            margin-bottom: 7px;
        }

        .detail-header p {
            color: #687878;
            font-size: 13px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .detail-item {
            padding: 16px;
            background: #f6f9f9;
            border-radius: 10px;
        }

        .detail-item span {
            display: block;
            margin-bottom: 6px;
            color: #687878;
            font-size: 12px;
        }

        .detail-item strong {
            color: #043f42;
            font-size: 15px;
        }

        .amount-box {
            margin-top: 25px;
            padding: 20px;
            background: #effbfa;
            border-radius: 12px;
        }

        .amount-line {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 7px 0;
            color: #687878;
            font-size: 13px;
        }

        .amount-line.total {
            margin-top: 8px;
            padding-top: 14px;
            border-top: 1px solid #d8eeee;
            color: #043f42;
            font-size: 16px;
            font-weight: 700;
        }

        .detail-actions {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        @media (max-width: 650px) {

            .payment-detail-container {
                padding: 25px 20px;
            }

            .detail-header {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

        }

            .payment-flash {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 13px;
            border: 1px solid transparent;
        }

        .flash-success { background: #e8f8ef; border-color: #b7e4c7; color: #1b6b3a; }
        .flash-info    { background: #effbfa; border-color: #bfe6e4; color: #075e63; }
        .flash-warning { background: #fff7e6; border-color: #f5deb3; color: #8a5a00; }
        .flash-error   { background: #fdf0f0; border-color: #f3c2c2; color: #a12a2a; }

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
                <span>Locataire</span>
            </div>

        </div>

    </header>


    <main class="page-container">

        <section class="payment-detail-container">

            <a href="paiement.php"
               class="back-link">
                ← Retour à mes paiements
            </a>


                        <?php if (isset($messagesRetour[$retourPaiement])): ?>
                <div class="payment-flash <?= $messagesRetour[$retourPaiement][0] ?>" role="status">
                    <?= htmlspecialchars($messagesRetour[$retourPaiement][1]) ?>
                </div>
            <?php endif; ?>

            <div class="detail-card">

                <div class="detail-header">

                    <div>

                        <span class="section-label">
                            DÉTAIL DE L'ÉCHÉANCE
                        </span>

                        <h1>
                            Échéance de loyer
                        </h1>

                        <p>
                            Période du
                            <?= htmlspecialchars($dateDebut) ?>
                            au
                            <?= htmlspecialchars($dateFin) ?>
                        </p>

                    </div>


                    <span class="status-badge <?= $statutClass ?>">
                        <?= htmlspecialchars($statutLabel) ?>
                    </span>

                </div>


                <div class="detail-grid">

                    <div class="detail-item">

                        <span>
                            Date d'échéance
                        </span>

                        <strong>
                            <?= htmlspecialchars($dateEcheance) ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Contrat
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                (string) ($echeance['numero_contrat'] ?? '-')
                            ) ?>
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Loyer
                        </span>

                        <strong>
                            <?= number_format(
                                $loyer,
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>

                    </div>


                    <div class="detail-item">

                        <span>
                            Charges
                        </span>

                        <strong>
                            <?= number_format(
                                $charges,
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>

                    </div>

                </div>


                <div class="amount-box">

                    <div class="amount-line">

                        <span>
                            Montant total
                        </span>

                        <strong>
                            <?= number_format(
                                $montantTotal,
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>

                    </div>


                    <div class="amount-line">

                        <span>
                            Montant restant
                        </span>

                        <strong>
                            <?= number_format(
                                $montantRestant,
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>

                    </div>


                    <div class="amount-line total">

                        <span>
                            À payer
                        </span>

                        <strong>
                            <?= number_format(
                                $montantRestant,
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </strong>

                    </div>

                </div>


                <div class="detail-actions">

                    <?php if ($montantRestant > 0 && $statut !== 'CANCELLED'): ?>

                        <a href="payer.php?id=<?= $idEcheance ?>"
                           class="button button-primary">

                            Payer cette échéance

                        </a>

                    <?php endif; ?>


                    <a href="historique.php"
                       class="button button-secondary">

                        Voir mon historique

                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>