<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class AbonnementModel
{
	public static function all(): array
	{
		return Database::connection()
			->query('SELECT * FROM abonnement ORDER BY prix_mensuel ASC')
			->fetchAll();
	}

	public static function activePlans(): array
	{
		return Database::connection()
			->query("SELECT * FROM abonnement WHERE actif = 1 ORDER BY prix_mensuel ASC")
			->fetchAll();
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare('SELECT * FROM abonnement WHERE id_abonnement = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function nomExists(string $nom, ?int $excludeId = null): bool
	{
		$sql = 'SELECT 1 FROM abonnement WHERE LOWER(nom) = LOWER(:nom)';
		$params = ['nom' => $nom];
		if ($excludeId !== null) {
			$sql .= ' AND id_abonnement != :excludeId';
			$params['excludeId'] = $excludeId;
		}
		$stmt = Database::connection()->prepare($sql . ' LIMIT 1');
		$stmt->execute($params);
		return (bool) $stmt->fetchColumn();
	}

	/**
	 * @param array{nom: string, description: ?string, prix_mensuel: float, limite_utilisateurs: ?int, limite_biens: ?int} $data
	 */
	public static function create(array $data): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO abonnement (nom, description, prix_mensuel, limite_utilisateurs, limite_biens, actif)
			 VALUES (:nom, :description, :prix_mensuel, :limite_utilisateurs, :limite_biens, 1)'
		);
		$stmt->execute($data);
		return (int) Database::connection()->lastInsertId();
	}

	/**
	 * @param array{nom: string, description: ?string, prix_mensuel: float, limite_utilisateurs: ?int, limite_biens: ?int} $data
	 */
	public static function update(int $id, array $data): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE abonnement SET nom = :nom, description = :description, prix_mensuel = :prix_mensuel,
				limite_utilisateurs = :limite_utilisateurs, limite_biens = :limite_biens
			 WHERE id_abonnement = :id'
		);
		$stmt->execute($data + ['id' => $id]);
	}

	public static function setActif(int $id, bool $actif): void
	{
		$stmt = Database::connection()->prepare('UPDATE abonnement SET actif = :actif WHERE id_abonnement = :id');
		$stmt->execute(['actif' => $actif ? 1 : 0, 'id' => $id]);
	}

	public static function setStripePriceId(int $id, string $stripePriceId): void
	{
		$stmt = Database::connection()->prepare('UPDATE abonnement SET stripe_price_id = :price_id WHERE id_abonnement = :id');
		$stmt->execute(['price_id' => $stripePriceId, 'id' => $id]);
	}

	/**
	 * Ferme l'abonnement actif de l'agence (le cas échéant) et affecte le nouveau plan.
	 * Utilisé pour un changement de plan gratuit, une affectation manuelle (platform admin),
	 * ou la confirmation d'un paiement Stripe (webhook).
	 *
	 * @return int id_agence_abonnement créé
	 */
	public static function assignToAgence(int $idAgence, int $idAbonnement): int
	{
		$db = Database::connection();
		$closeStmt = $db->prepare(
			"UPDATE agence_abonnement SET statut = 'EXPIRED', date_fin = CURDATE()
			 WHERE id_agence = :id_agence AND statut = 'ACTIVE'"
		);
		$closeStmt->execute(['id_agence' => $idAgence]);

		$insertStmt = $db->prepare(
			"INSERT INTO agence_abonnement (id_agence, id_abonnement, date_debut, statut)
			 VALUES (:id_agence, :id_abonnement, CURDATE(), 'ACTIVE')"
		);
		$insertStmt->execute(['id_agence' => $idAgence, 'id_abonnement' => $idAbonnement]);

		return (int) $db->lastInsertId();
	}

	/**
	 * @return array{nom: string, limite_utilisateurs: ?int, limite_biens: ?int, date_debut: string, date_fin: ?string}|null
	 */
	public static function activeSubscription(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			"SELECT ab.*, aa.id_agence_abonnement, aa.date_debut, aa.date_fin
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.id_agence = :id_agence AND aa.statut = 'ACTIVE'
			 ORDER BY aa.date_debut DESC
			 LIMIT 1"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	/* --------------------------------------------------------------
	 * Facturation (paiement Stripe d'un changement de plan payant)
	 * -------------------------------------------------------------- */

	public static function createFacture(int $idAgence, int $idAbonnement, float $montant, string $checkoutSessionId): int
	{
		$stmt = Database::connection()->prepare(
			"INSERT INTO facture_abonnement (id_agence, id_abonnement, montant, stripe_checkout_session_id, statut)
			 VALUES (:id_agence, :id_abonnement, :montant, :session_id, 'PENDING')"
		);
		$stmt->execute([
			'id_agence' => $idAgence,
			'id_abonnement' => $idAbonnement,
			'montant' => $montant,
			'session_id' => $checkoutSessionId,
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function setFactureSessionId(int $idFacture, string $checkoutSessionId): void
	{
		$stmt = Database::connection()->prepare('UPDATE facture_abonnement SET stripe_checkout_session_id = :session_id WHERE id_facture = :id');
		$stmt->execute(['session_id' => $checkoutSessionId, 'id' => $idFacture]);
	}

	public static function markFacturePaid(int $idFacture, int $idAgenceAbonnement, string $paymentIntentId): void
	{
		$stmt = Database::connection()->prepare(
			"UPDATE facture_abonnement
			 SET statut = 'PAID', id_agence_abonnement = :id_agence_abonnement, stripe_payment_intent_id = :payment_intent_id, paid_at = NOW()
			 WHERE id_facture = :id"
		);
		$stmt->execute(['id_agence_abonnement' => $idAgenceAbonnement, 'payment_intent_id' => $paymentIntentId, 'id' => $idFacture]);
	}

	public static function markFactureFailed(int $idFacture): void
	{
		$stmt = Database::connection()->prepare("UPDATE facture_abonnement SET statut = 'FAILED' WHERE id_facture = :id");
		$stmt->execute(['id' => $idFacture]);
	}

	public static function findFacture(int $id): ?array
	{
		$stmt = Database::connection()->prepare('SELECT * FROM facture_abonnement WHERE id_facture = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function findFactureBySessionId(string $sessionId): ?array
	{
		$stmt = Database::connection()->prepare('SELECT * FROM facture_abonnement WHERE stripe_checkout_session_id = :session_id LIMIT 1');
		$stmt->execute(['session_id' => $sessionId]);
		$row = $stmt->fetch();
		return $row ?: null;
	}
}
