<?php

declare(strict_types=1);

require_once __DIR__ . '/ContratController.php';

function afficherErreur(string $message, int $code = 404): void
{
    http_response_code($code);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document indisponible | LOKA</title>
        <link rel="stylesheet" href="../locataire.css">
        <link rel="stylesheet" href="contrats.css">
    </head>
    <body>
        <main style="max-width:700px;margin:80px auto;padding:30px;">
            <div class="housing-card-empty">
                <h2>Document indisponible</h2>
                <p><?= htmlspecialchars(
                    $message,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?></p>
                <p>
                    <a class="button" href="index.php">
                        Retour à mes contrats
                    </a>
                </p>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

try {
    $controller = new ContratController();

    $idContrat = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$idContrat || $idContrat < 1) {
        afficherErreur('Aucun contrat valide n’a été sélectionné.');
    }

    $contrat = $controller->getContrat($idContrat);

    if (!$contrat) {
        afficherErreur(
            'Ce contrat est introuvable ou vous n’êtes pas autorisé à le consulter.'
        );
    }

    $chemin = trim((string) ($contrat['chemin_fichier_pdf'] ?? ''));

    if ($chemin === '') {
        afficherErreur(
            'Le document du contrat n’a pas encore été ajouté par l’agence ou le propriétaire.'
        );
    }

    $racine = dirname(__DIR__, 3);

    // Accepte un chemin relatif au projet LOKA.
    $cheminFichier = $racine . DIRECTORY_SEPARATOR
        . str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            ltrim($chemin, '/\\')
        );

    if (!is_file($cheminFichier) || !is_readable($cheminFichier)) {
        afficherErreur(
            'Le fichier PDF du contrat est momentanément indisponible. Veuillez contacter votre agence.'
        );
    }

    if (strtolower(pathinfo($cheminFichier, PATHINFO_EXTENSION)) !== 'pdf') {
        afficherErreur('Le document associé à ce contrat n’est pas un PDF valide.');
    }

    // Aucun affichage HTML ne doit précéder l'envoi du fichier.
    header('Content-Type: application/pdf');
    header(
        'Content-Disposition: attachment; filename="contrat-'
        . (int) $contrat['id_contrat']
        . '.pdf"'
    );
    header('Content-Length: ' . filesize($cheminFichier));
    header('X-Content-Type-Options: nosniff');

    readfile($cheminFichier);
    exit;

} catch (Throwable $e) {
    error_log('Téléchargement contrat LOKA : ' . $e->getMessage());

    afficherErreur(
        'Une erreur est survenue lors du téléchargement. Veuillez réessayer.',
        500
    );
}
