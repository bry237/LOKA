<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';

function h($valeur): string
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

try {
    $controller = new ContratController();

    $idContrat = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idContrat || $idContrat < 1) {
        throw new RuntimeException('Contrat invalide.');
    }

    $contrat = $controller->getContrat($idContrat);

    if (!$contrat) {
        throw new RuntimeException(
            'Contrat introuvable ou accès non autorisé.'
        );
    }

    $statut = (string) ($contrat['statut_signature'] ?? '');

    if (!in_array(
        $statut,
        ['SIGNE_SIMULATION', 'DEPOSE', 'VALIDE'],
        true
    )) {
        throw new RuntimeException(
            'Aucune signature enregistrée pour ce contrat.'
        );
    }

    $dateSignature = $contrat['date_signature'] ?? null;

    if (empty($dateSignature)) {
        throw new RuntimeException(
            'La date de signature est indisponible.'
        );
    }

    $nomLocataire = trim(
        (string) ($controller->getLocataire()['prenom'] ?? '')
        . ' '
        . (string) ($controller->getLocataire()['nom'] ?? '')
    );

} catch (Throwable $e) {
    http_response_code(404);
    exit(
        'Impossible de générer la confirmation : '
        . h($e->getMessage())
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation de signature - LOKA</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #222;
            margin: 0;
            padding: 30px;
        }

        .document {
            max-width: 800px;
            margin: 0 auto;
            padding: 45px;
            background: white;
            border: 1px solid #ddd;
        }

        h1 {
            text-align: center;
            color: #173b65;
        }

        h2 {
            margin-top: 35px;
            color: #173b65;
        }

        .reference {
            text-align: center;
            color: #555;
        }

        .bloc {
            margin-top: 25px;
            padding: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .mention {
            margin-top: 30px;
            padding: 15px;
            background: #f0f6fc;
            border-left: 4px solid #173b65;
        }

        .actions {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            gap: 12px;
        }

        button, .retour {
            padding: 12px 18px;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }

        button {
            background: #173b65;
            color: white;
        }

        .retour {
            background: #ddd;
            color: #222;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .document {
                border: none;
                padding: 15px;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>

<main class="document">
    <h1>LOKA</h1>

    <p class="reference">
        Confirmation de signature du contrat de location
    </p>

    <hr>

    <h2>1. Référence du contrat</h2>

    <div class="bloc">
        <p>
            <strong>Numéro du contrat :</strong>
            <?= h($contrat['numero'] ?? $idContrat) ?>
        </p>

        <p>
            <strong>Date de signature enregistrée :</strong>
            <?= h(date(
                'd/m/Y à H:i',
                strtotime((string) $dateSignature)
            )) ?>
        </p>
    </div>

    <h2>2. Identification du locataire</h2>

    <div class="bloc">
        <p>
            <strong>Nom du locataire :</strong>
            <?= h($nomLocataire) ?>
        </p>
    </div>

    <h2>3. Confirmation</h2>

    <p>
        Le système LOKA a enregistré une confirmation de signature
        associée au contrat indiqué dans ce document.
    </p>

    <div class="mention">
        <strong>Statut enregistré :</strong>
        <?= h($statut) ?>
        <p>
            Ce document constitue une confirmation générée par
            l'application. Il ne remplace pas le contrat de location
            original et ne garantit pas, à lui seul, la validité
            juridique de la signature.
        </p>
    </div>

    <p style="margin-top:40px">
        Document généré par LOKA.
    </p>
</main>

<div class="actions">
    <button type="button" onclick="window.print()">
        Télécharger / enregistrer en PDF
    </button>

    <a class="retour"
       href="detail.php?id=<?= (int) $idContrat ?>">
        Retour au contrat
    </a>
</div>

</body>
</html>
