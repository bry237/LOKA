<?php

declare(strict_types=1);

/**
 * Client Stripe dédié au paiement des échéances par les locataires (paiement unique).
 *
 * core/StripeClient.php ne gère que les abonnements d'agence (mode "subscription") et sa
 * méthode request() est privée : ce client vit donc ici, dans le module locataires, et
 * appelle l'API Stripe directement en cURL (pas de SDK installé dans le projet).
 * Toute erreur lève une RuntimeException : un problème de paiement ne doit jamais être avalé.
 */
final class StripePaiementClient
{
    private const BASE = 'https://api.stripe.com/v1';

    /** Tolérance (secondes) entre l'horodatage signé du webhook et l'heure du serveur. */
    private const WEBHOOK_TOLERANCE = 300;

    /**
     * Crée une session Checkout en mode paiement unique pour une échéance.
     *
     * @param array<string,scalar> $metadata
     * @return array{id: string, url: string}
     */
    public static function createCheckoutSession(
        int $montantCentimes,
        string $description,
        string $customerEmail,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
        string $idempotencyKey
    ): array {
        $params = [
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $montantCentimes,
                    'product_data' => ['name' => $description],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) ($metadata['id_paiement'] ?? ''),
            'metadata' => $metadata,
            // Les mêmes métadonnées sur le PaymentIntent facilitent le suivi dans le dashboard Stripe.
            'payment_intent_data' => ['metadata' => $metadata],
        ];

        if ($customerEmail !== '') {
            $params['customer_email'] = $customerEmail;
        }

        $response = self::request('POST', '/checkout/sessions', $params, $idempotencyKey);

        return ['id' => (string) $response['id'], 'url' => (string) $response['url']];
    }

    /**
     * Relit une session Checkout auprès de Stripe (confirmation au retour, sans dépendre du webhook).
     *
     * @return array<string,mixed>
     */
    public static function retrieveCheckoutSession(string $sessionId): array
    {
        return self::request('GET', '/checkout/sessions/' . rawurlencode($sessionId), []);
    }

    /**
     * Expire une session ouverte (ex. le locataire relance un paiement) : empêche de payer deux fois.
     * Échec silencieux : la session est peut-être déjà expirée ou payée.
     */
    public static function expireCheckoutSession(string $sessionId): void
    {
        try {
            self::request('POST', '/checkout/sessions/' . rawurlencode($sessionId) . '/expire', []);
        } catch (Throwable $exception) {
            // Déjà expirée, complétée ou introuvable : rien à faire.
        }
    }

    /**
     * Vérifie la signature du header `Stripe-Signature` (format `t=...,v1=...`), avec une
     * tolérance sur l'horodatage pour empêcher le rejeu d'un ancien payload capturé.
     */
    public static function verifyWebhookSignature(string $payload, string $sigHeader, string $secret): bool
    {
        if ($secret === '' || $sigHeader === '') {
            return false;
        }

        $timestamp = '';
        $signatures = [];
        foreach (explode(',', $sigHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === '' || !ctype_digit($timestamp) || !$signatures) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::WEBHOOK_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Secret du webhook dédié aux paiements locataires.
     */
    public static function webhookSecret(): string
    {
        return self::configValue('webhook_secret');
    }

    /**
     * Lit une valeur de config Stripe : config/services.php (variables d'environnement) en priorité,
     * sinon config/stripe_local.php (fichier local non versionné), en lecture seule.
     */
    private static function configValue(string $key): string
    {
        $root = dirname(__DIR__, 3);

        $services = require $root . '/config/services.php';
        $value = (string) ($services['stripe'][$key] ?? '');
        if ($value !== '') {
            return $value;
        }

        $localFile = $root . '/config/stripe_local.php';
        if (is_file($localFile)) {
            $local = require $localFile;
            return is_array($local) ? (string) ($local[$key] ?? '') : '';
        }

        return '';
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    private static function request(string $method, string $path, array $params, ?string $idempotencyKey = null): array
    {
        $secretKey = self::configValue('secret_key');
        if ($secretKey === '') {
            throw new RuntimeException('Clé Stripe non configurée (STRIPE_SECRET_KEY).');
        }

        $url = self::BASE . $path;
        $headers = [];
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => $secretKey . ':',
        ];

        if ($method === 'GET') {
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $options[CURLOPT_POSTFIELDS] = http_build_query($params);
            if ($idempotencyKey !== null) {
                $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
            }
        }

        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Impossible de contacter Stripe.');
        }

        $body = json_decode((string) $response, true);
        $body = is_array($body) ? $body : [];

        if ($status >= 400) {
            throw new RuntimeException((string) ($body['error']['message'] ?? "Erreur Stripe (HTTP $status)."));
        }

        return $body;
    }
}