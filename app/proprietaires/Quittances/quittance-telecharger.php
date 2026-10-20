<?php

declare(strict_types=1);

ob_start();

try {
    require_once dirname(__DIR__) . '/ProprietaireController.php';
    require_once dirname(__DIR__, 3) . '/core/QuittancePdf.php';

    $idPaiement = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$idPaiement) {
        throw new RuntimeException('Paiement invalide (id manquant).');
    }

    $controller = new ProprietaireController();
    $donnees = $controller->getDonneesQuittance((int) $idPaiement);

    if (!$donnees) {
        throw new RuntimeException('Aucune quittance : paiement introuvable, non effectué, ou pas sur l’un de vos biens.');
    }

    $pdf = QuittancePdf::generate($donnees);
    $nom = $donnees['numero_quittance'] . '.pdf';

    ob_end_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nom . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Erreur : ' . $e->getMessage();
    exit;
}
