<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AbonnementController.php';
require_once dirname(__DIR__) . '/agences/AgenceModel.php';

$currentUser = Authorization::requireRole('Administrateur agence');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$idAbonnement = (int) ($_POST['id_abonnement'] ?? 0);
$agence = AgenceModel::findFull((int) $currentUser['id_agence']);

if (!$agence) {
	header('Location: ../mon-agence/abonnement.php?error=' . urlencode('Agence introuvable.'));
	exit;
}

// Stripe exige des URLs absolues (schéma + hôte) pour success_url/cancel_url.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/LOKA/app/administration/mon-agence/abonnement.php';
// {CHECKOUT_SESSION_ID} est substitué par Stripe : permet de confirmer le paiement
// directement via l'API au retour, sans dépendre du webhook (voir confirmCheckoutSession).
[$error, $checkoutUrl] = AbonnementController::startCheckout(
	$agence,
	$idAbonnement,
	$baseUrl . '?success=1&session_id={CHECKOUT_SESSION_ID}',
	$baseUrl . '?cancel=1'
);

if ($error !== null) {
	header('Location: ../mon-agence/abonnement.php?error=' . urlencode($error));
	exit;
}

if ($checkoutUrl !== null) {
	header('Location: ' . $checkoutUrl);
	exit;
}

// Plan gratuit : affecté immédiatement, pas de paiement.
header('Location: ../mon-agence/abonnement.php?success=' . urlencode('Plan mis à jour.'));
