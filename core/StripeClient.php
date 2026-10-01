<?php
declare(strict_types=1);

/**
 * Appels bruts à l'API Stripe (pas de SDK/Composer dans ce projet, cf. ClaudeVisionAnalyzer).
 * Contrairement à ClaudeVisionAnalyzer (échec doux), ce client lève une RuntimeException sur
 * toute erreur : un problème de paiement doit être visible, jamais avalé silencieusement.
 */
final class StripeClient
{
	private const BASE = 'https://api.stripe.com/v1';

	public static function createProduct(string $nom): string
	{
		$response = self::request('POST', '/products', ['name' => $nom]);
		return (string) $response['id'];
	}

	public static function createPrice(string $productId, float $prixMensuel): string
	{
		$response = self::request('POST', '/prices', [
			'product' => $productId,
			'unit_amount' => (int) round($prixMensuel * 100),
			'currency' => 'eur',
			'recurring' => ['interval' => 'month'],
		]);
		return (string) $response['id'];
	}

	public static function createCustomer(string $email, string $nom): string
	{
		$response = self::request('POST', '/customers', ['email' => $email, 'name' => $nom]);
		return (string) $response['id'];
	}

	/**
	 * @param array<string,scalar> $metadata
	 * @return array{id: string, url: string}
	 */
	public static function createCheckoutSession(
		string $customerId,
		string $priceId,
		string $successUrl,
		string $cancelUrl,
		array $metadata
	): array {
		$response = self::request('POST', '/checkout/sessions', [
			'mode' => 'subscription',
			'customer' => $customerId,
			'line_items' => [['price' => $priceId, 'quantity' => 1]],
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
			'metadata' => $metadata,
		]);
		return ['id' => (string) $response['id'], 'url' => (string) $response['url']];
	}

	/**
	 * Vérifie manuellement la signature du header `Stripe-Signature` (format `t=...,v1=...`),
	 * sans dépendance au SDK officiel.
	 */
	public static function verifyWebhookSignature(string $payload, string $sigHeader, string $secret): bool
	{
		$parts = [];
		foreach (explode(',', $sigHeader) as $pair) {
			[$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
			$parts[$key][] = $value;
		}
		$timestamp = $parts['t'][0] ?? '';
		$signatures = $parts['v1'] ?? [];
		if ($timestamp === '' || !$signatures || $secret === '') {
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
	 * @param array<string,mixed> $params
	 * @return array<string,mixed>
	 */
	private static function request(string $method, string $path, array $params): array
	{
		$config = require dirname(__DIR__) . '/config/services.php';
		$secretKey = (string) ($config['stripe']['secret_key'] ?? '');
		if ($secretKey === '') {
			throw new RuntimeException('Clé Stripe non configurée (STRIPE_SECRET_KEY).');
		}

		$ch = curl_init(self::BASE . $path);
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_USERPWD => $secretKey . ':',
			CURLOPT_POSTFIELDS => http_build_query($params),
		]);
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
