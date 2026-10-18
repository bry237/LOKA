<?php

declare(strict_types=1);

/**
 * Webhook Stripe des paiements de loyers — appelé par Stripe, jamais par un utilisateur connecté.
 * Pas de session ni de rôle ici : la confiance vient uniquement de la signature vérifiée ci-dessous.
 *
 * À déclarer dans Stripe (ou via `stripe listen --forward-to .../app/locataires/paiements/webhook.php`)
 * avec les événements : checkout.session.completed, checkout.session.expired,
 * checkout.session.async_payment_succeeded, checkout.session.async_payment_failed.
 */
require_once __DIR__ . '/PaiementController.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$payload = (string) file_get_contents('php://input');
$sigHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

if (!StripePaiementClient::verifyWebhookSignature($payload, $sigHeader, StripePaiementClient::webhookSecret())) {
    http_response_code(400);
    echo json_encode(['error' => 'Signature invalide.']);
    exit;
}

try {
    $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    (new PaiementController())->handleWebhookEvent(is_array($event) ? $event : []);
    echo json_encode(['received' => true]);
} catch (Throwable $exception) {
    // 500 : Stripe réessaiera automatiquement l'événement.
    error_log('[LOKA] Webhook paiement locataire : ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Traitement du webhook impossible.']);
}