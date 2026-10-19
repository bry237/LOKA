<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

$idEcheance = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

$mode = (string) (
    $_GET['mode'] ?? ''
);

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

if ($mode !== 'BANK_TRANSFER') {
    http_response_code(400);
    exit('Mode de paiement invalide.');
}

$echeance = $controller->getMyEcheance($idEcheance);

if ($echeance === null) {
    http_response_code(404);
    exit('Échéance introuvable.');
}

$montantRestant = $controller->getMontantRestant(
    $idEcheance
);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Virement bancaire | LOKA</title>

    <link rel="stylesheet"
          href="paiement.css">

    <style>

        .confirmation-page {
            padding: 45px 34px;
        }

        .confirmation-card {
            max-width: 650px;
            margin: 0 auto;

            background: white;

            border: 1px solid #dde8e8;

            border-radius: 16px;

            padding: 35px;

            text-align: center;

            box-shadow:
                0 3px 12px rgba(4, 63, 66, 0.04);
        }

        .confirmation-icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #effbfa;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;
        }

        .confirmation-card h1 {
            margin-bottom: 10px;

            color: #043f42;

            font-size: 24px;
        }

        .confirmation-card p {
            margin-bottom: 20px;

            color: #687878;

            font-size: 13px;

            line-height: 1.6;
        }

        .transfer-info {
            margin: 20px 0;

            padding: 20px;

            background: #f6f9f9;

            border-radius: 12px;

            text-align: left;
        }

        .transfer-line {
            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 8px 0;

            color: #687878;

            font-size: 13px;
        }

        .transfer-line strong {
            color: #043f42;
        }

        .confirmation-actions {
            margin-top: 25px;

            display: flex;

            justify-content: center;

            gap: 10px;
        }

    </style>

</head>

<body>

<div class="main-content">

    <header class="top-header">

        <div class="welcome">

            <h1>
                Bonjour Pascale
                <span class="wave"></span>
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

                <strong>
                    Pascale NGASSA
                </strong>

                <span>
                    Locataire
                </span>

            </div>

        </div>

    </header>


    <main class="page-container">

        <section class="confirmation-page">

            <div class="confirmation-card">

                <div class="confirmation-icon">
                    🏦
                </div>


                <span class="section-label">
                    VIREMENT BANCAIRE
                </span>


                <h1>
                    Effectuer votre virement
               </h1>


                <p>

                    Votre paiement sera enregistré comme
                    <strong>en attente</strong> jusqu'à
                    validation du virement par l'agence.

                </p>


                <div class="transfer-info">

                    <div class="transfer-line">

                        <span>
                            Montant
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


                    <div class="transfer-line">

                        <span>
                            Mode
                        </span>

                        <strong>
                            Virement bancaire
                        </strong>

                    </div>


                    <div class="transfer-line">

                        <span>
                            Statut
                        </span>

                        <strong>
                            En attente de validation
                        </strong>

                    </div>

                </div>


                <p>

                    Les coordonnées bancaires de l'agence
                    devront être affichées ici lorsque
                    leur configuration sera disponible.

                </p>


                <div class="confirmation-actions">

                    <a
                        href="detail.php?id=<?= $idEcheance ?>"
                        class="button button-secondary"
                    >
                        Retour
                    </a>


                    <a
                        href="historique.php"
                        class="button button-primary"
                    >
                        Voir mon historique
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>