<?php

declare(strict_types=1);

/**
 * Lance un paiement Stripe Checkout pour une échéance (POST depuis payer.php).
 */
require_once __DIR__ . '/PaiementController.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';

$controller = new PaiementController();

// Vérifie la connexion et le rôle Locataire avant toute chose.
$controller->getCurrentLocataire();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)
) {
    http_response_code(400);
    exit('Requête invalide.');
}

$idEcheance = filter_var($_POST['id_echeance'] ?? null, FILTER_VALIDATE_INT);

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

// Stripe exige des URLs absolues. On part du dossier courant plutôt que d'un chemin codé en dur.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/retour.php';

// {CHECKOUT_SESSION_ID} est remplacé par Stripe : il ne doit surtout pas être encodé.
[$error, $checkoutUrl] = $controller->startCheckout(
    $idEcheance,
    $base . '?id=' . $idEcheance . '&session_id={CHECKOUT_SESSION_ID}',
    $base . '?id=' . $idEcheance . '&cancel=1'
);

if ($error !== null || $checkoutUrl === null) {
    header('Location: payer.php?id=' . $idEcheance . '&error=' . urlencode($error ?? 'Paiement impossible.'));
    exit;
}

header('Location: ' . $checkoutUrl, true, 303);
exit;