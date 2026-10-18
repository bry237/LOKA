<?php

declare(strict_types=1);

/**
 * Page de retour de Stripe Checkout (success_url et cancel_url).
 * Ne s'affiche pas : confirme (ou annule) le paiement puis redirige vers le détail de l'échéance.
 */
require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

$idEcheance = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idEcheance) {
    header('Location: paiement.php');
    exit;
}

$resultat = 'pending';

if (!empty($_GET['cancel'])) {
    $idPaiement = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
    if ($idPaiement) {
        $controller->cancelCheckout($idPaiement);
    }
    $resultat = 'cancel';
} else {
    $sessionId = (string) ($_GET['session_id'] ?? '');

    // Les identifiants de session Checkout Stripe ont toujours la forme cs_test_... / cs_live_...
    if (preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId) === 1) {
        try {
            $resultat = match ($controller->confirmCheckoutSession($sessionId)) {
                'confirmed', 'already' => 'success',
                'unknown' => 'error',
                default => 'pending',
            };
        } catch (Throwable $exception) {
            $resultat = 'error';
        }
    } else {
        $resultat = 'error';
    }
}

header('Location: detail.php?id=' . $idEcheance . '&paiement=' . $resultat);
exit;