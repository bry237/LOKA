<?php
declare(strict_types=1);

require_once __DIR__ . '/AbonnementModel.php';
require_once __DIR__ . '/AbonnementValidator.php';
require_once dirname(__DIR__) . '/agences/AgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/StripeClient.php';

final class AbonnementController
{
	public static function list(): array
	{
		return AbonnementModel::all();
	}

	public static function find(int $id): ?array
	{
		return AbonnementModel::find($id);
	}

	/**
	 * @return array{0: array<string,string>, 1: int|null} [erreurs de validation, id créé ou null]
	 */
	public static function create(array $input): array
	{
		[$errors, $data] = AbonnementValidator::abonnement($input);
		if (!$errors && AbonnementModel::nomExists($data['nom'])) {
			$errors['nom'] = 'Un plan porte déjà ce nom.';
		}
		if ($errors) {
			return [$errors, null];
		}

		$id = AbonnementModel::create($data);
		self::syncStripePrice($id, $data['nom'], $data['prix_mensuel']);

		$currentUser = Auth::user();
		Logger::audit('CREATE', 'abonnement', $id, $currentUser['id_utilisateur'] ?? null, null, null, $data);

		return [[], $id];
	}

	/**
	 * @return array<string,string> Erreurs de validation, vide si l'opération a réussi.
	 */
	public static function update(int $id, array $input): array
	{
		$existing = AbonnementModel::find($id);
		if (!$existing) {
			return ['_global' => 'Plan introuvable.'];
		}

		[$errors, $data] = AbonnementValidator::abonnement($input);
		if (!$errors && AbonnementModel::nomExists($data['nom'], $id)) {
			$errors['nom'] = 'Un plan porte déjà ce nom.';
		}
		if ($errors) {
			return $errors;
		}

		AbonnementModel::update($id, $data);
		$priceChanged = (float) $existing['prix_mensuel'] !== $data['prix_mensuel'];
		if ($priceChanged || !$existing['stripe_price_id']) {
			// Les prix Stripe sont immuables : un changement de tarif crée un nouveau Price.
			// Un plan payant qui n'a encore jamais été synchronisé (créé avant la mise en place
			// de Stripe, ou créé sans clé configurée) est rattrapé ici.
			self::syncStripePrice($id, $data['nom'], $data['prix_mensuel']);
		}

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'abonnement',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			null,
			array_intersect_key($existing, $data),
			$data
		);

		return [];
	}

	public static function setActif(int $id, bool $actif): ?string
	{
		$target = AbonnementModel::find($id);
		if (!$target) {
			return 'Plan introuvable.';
		}

		AbonnementModel::setActif($id, $actif);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'abonnement',
			$id,
			$currentUser['id_utilisateur'] ?? null,
			null,
			['actif' => (bool) $target['actif']],
			['actif' => $actif]
		);

		return null;
	}

	/**
	 * Crée le Product/Price Stripe correspondant à un plan payant (silencieux sur un plan
	 * gratuit ou si Stripe n'est pas configuré : la clé agence/catalogue reste utilisable
	 * sans paiement, seul le passage par Stripe Checkout échouera tant que la clé manque).
	 */
	private static function syncStripePrice(int $id, string $nom, float $prixMensuel): void
	{
		if ($prixMensuel <= 0) {
			return;
		}
		try {
			$productId = StripeClient::createProduct($nom);
			$priceId = StripeClient::createPrice($productId, $prixMensuel);
			AbonnementModel::setStripePriceId($id, $priceId);
		} catch (Throwable $exception) {
			// Pas de clé Stripe configurée ou API indisponible : le plan reste créé/modifié,
			// seul le changement de plan payant par Stripe échouera proprement plus tard.
		}
	}

	/**
	 * Démarre un paiement Stripe Checkout pour qu'une agence passe sur un plan payant.
	 *
	 * @return array{0: ?string, 1: ?string} [message d'erreur, URL de redirection Stripe]
	 */
	public static function startCheckout(array $agence, int $idAbonnement, string $successUrl, string $cancelUrl): array
	{
		$plan = AbonnementModel::find($idAbonnement);
		if (!$plan || !$plan['actif']) {
			return ['Ce plan n’est pas disponible.', null];
		}

		if ((float) $plan['prix_mensuel'] <= 0) {
			// Plan gratuit : pas de paiement, affectation immédiate.
			AbonnementModel::assignToAgence((int) $agence['id_agence'], $idAbonnement);
			$currentUser = Auth::user();
			Logger::audit(
				'UPDATE',
				'agence_abonnement',
				(int) $agence['id_agence'],
				$currentUser['id_utilisateur'] ?? null,
				(int) $agence['id_agence'],
				null,
				['id_abonnement' => $idAbonnement]
			);
			return [null, null];
		}

		if (!$plan['stripe_price_id']) {
			return ['Ce plan n’est pas encore configuré pour le paiement en ligne. Contactez un administrateur plateforme.', null];
		}

		try {
			$customerId = (string) ($agence['stripe_customer_id'] ?? '');
			if ($customerId === '') {
				$customerId = StripeClient::createCustomer($agence['email'], $agence['nom']);
				AgenceModel::setStripeCustomerId((int) $agence['id_agence'], $customerId);
			}

			$factureId = AbonnementModel::createFacture((int) $agence['id_agence'], $idAbonnement, (float) $plan['prix_mensuel'], '');
			$session = StripeClient::createCheckoutSession(
				$customerId,
				$plan['stripe_price_id'],
				$successUrl,
				$cancelUrl,
				['id_agence' => $agence['id_agence'], 'id_abonnement' => $idAbonnement, 'id_facture' => $factureId]
			);
			AbonnementModel::setFactureSessionId($factureId, $session['id']);

			return [null, $session['url']];
		} catch (Throwable $exception) {
			return ['Le paiement n’a pas pu être initié : ' . $exception->getMessage(), null];
		}
	}

	/**
	 * Traite un événement webhook Stripe déjà vérifié (signature) et décodé.
	 * Idempotent : une facture déjà marquée PAID est ignorée, pour pouvoir être rejouée sans
	 * risque par la confirmation au retour de Checkout (voir confirmCheckoutSession) ou par
	 * Stripe lui-même (qui réessaie un webhook non acquitté).
	 */
	public static function handleWebhookEvent(array $event): void
	{
		$type = (string) ($event['type'] ?? '');
		$session = $event['data']['object'] ?? [];
		$metadata = $session['metadata'] ?? [];
		$idFacture = (int) ($metadata['id_facture'] ?? 0);
		if (!$idFacture) {
			return;
		}

		if ($type === 'checkout.session.completed') {
			$facture = AbonnementModel::findFacture($idFacture);
			if (!$facture || $facture['statut'] === 'PAID') {
				return;
			}
			$idAgence = (int) ($metadata['id_agence'] ?? 0);
			$idAbonnement = (int) ($metadata['id_abonnement'] ?? 0);
			if (!$idAgence || !$idAbonnement) {
				return;
			}
			$idAgenceAbonnement = AbonnementModel::assignToAgence($idAgence, $idAbonnement);
			// Mode "subscription" : pas de payment_intent direct sur la session, l'identifiant
			// de référence est l'abonnement Stripe créé (ou, à défaut, le payment_intent s'il existe).
			$reference = (string) ($session['subscription'] ?? $session['payment_intent'] ?? '');
			AbonnementModel::markFacturePaid($idFacture, $idAgenceAbonnement, $reference);
			Logger::audit('UPDATE', 'agence_abonnement', $idAgenceAbonnement, null, $idAgence, null, ['id_abonnement' => $idAbonnement, 'via' => 'stripe']);
		} elseif ($type === 'checkout.session.expired') {
			AbonnementModel::markFactureFailed($idFacture);
		}
	}

	/**
	 * Confirme une session Checkout directement auprès de l'API Stripe au retour sur
	 * success_url, en secours du webhook : en local (XAMPP), Stripe ne peut pas appeler
	 * notre webhook sur localhost sans `stripe listen`, donc le paiement passait côté Stripe
	 * sans jamais affecter le plan en base. Réutilise handleWebhookEvent (idempotent) pour que
	 * le traitement reste identique, que ce soit le webhook ou ce retour qui arrive en premier.
	 *
	 * @return 'confirmed'|'already'|'pending'|'unknown'
	 */
	public static function confirmCheckoutSession(string $sessionId): string
	{
		$facture = AbonnementModel::findFactureBySessionId($sessionId);
		if (!$facture) {
			return 'unknown';
		}
		if ($facture['statut'] === 'PAID') {
			return 'already';
		}

		try {
			$session = StripeClient::retrieveCheckoutSession($sessionId);
		} catch (Throwable $exception) {
			return 'pending';
		}

		if (($session['payment_status'] ?? '') !== 'paid') {
			return 'pending';
		}

		self::handleWebhookEvent(['type' => 'checkout.session.completed', 'data' => ['object' => $session]]);
		return 'confirmed';
	}
}
