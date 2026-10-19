<?php

declare(strict_types=1);

ob_start();

try {
    require_once dirname(__DIR__) . '/LocataireController.php';
    require_once __DIR__ . '/QuittancePdf.php';

    $idPaiement = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idPaiement) {
        throw new RuntimeException('Paiement invalide (id manquant).');
    }

    $controller = new LocataireController();
    $donnees = $controller->getDonneesQuittance((int) $idPaiement);

    if (!$donnees) {
        throw new RuntimeException('Aucune quittance : paiement introuvable, non effectué, ou pas à ce locataire.');
    }

    $pdf = QuittancePdf::generate($donnees);
    $nom = $donnees['numero_quittance'] . '.pdf';

    ob_end_clean(); // supprime tout texte/avertissement avant le PDF

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nom . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erreur : ' . $e->getMessage() . "\n" . basename($e->getFile()) . ' ligne ' . $e->getLine();
    exit;
}