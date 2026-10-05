<?php
declare(strict_types=1);

/**
 * Webhook Stripe — appelé par Stripe, jamais par un utilisateur connecté.
 * Pas d'Authorization::requireRole ici (il n'y a pas de session) : la confiance vient
 * uniquement de la vérification de signature ci-dessous, pas d'un rôle applicatif.
 */
require_once __DIR__ . '/AbonnementController.php';
require_once dirname(__DIR__, 3) . '/core/StripeClient.php';

header('Content-Type: application/json; charset=UTF-8');

$payload = (string) file_get_contents('php://input');
$sigHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$config = require dirname(__DIR__, 3) . '/config/services.php';
$webhookSecret = (string) ($config['stripe']['webhook_secret'] ?? '');

if ($webhookSecret === '' || !StripeClient::verifyWebhookSignature($payload, $sigHeader, $webhookSecret)) {
	http_response_code(400);
	echo json_encode(['error' => 'Signature invalide.']);
	exit;
}

try {
	$event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
	AbonnementController::handleWebhookEvent(is_array($event) ? $event : []);
	echo json_encode(['received' => true]);
} catch (Throwable $exception) {
	http_response_code(500);
	echo json_encode(['error' => 'Traitement du webhook impossible.']);
}
