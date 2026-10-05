<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Vérifie les limites du plan actif d'une agence (limite_utilisateurs / limite_biens).
 * Volontairement indépendant du module app/administration/abonnements/ pour pouvoir être
 * appelé par n'importe quel module (y compris le futur module Biens) sans dépendance croisée.
 */
final class SubscriptionLimiter
{
	/**
	 * @return array{users: int, biens: int, limitUsers: ?int, limitBiens: ?int}
	 */
	public static function usage(int $idAgence): array
	{
		$db = Database::connection();

		$planStmt = $db->prepare(
			'SELECT ab.limite_utilisateurs, ab.limite_biens
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.id_agence = :id_agence AND aa.statut = \'ACTIVE\'
			 ORDER BY aa.date_debut DESC
			 LIMIT 1'
		);
		$planStmt->execute(['id_agence' => $idAgence]);
		$plan = $planStmt->fetch();

		$usersStmt = $db->prepare('SELECT COUNT(*) FROM utilisateur WHERE id_agence = :id_agence AND deleted_at IS NULL');
		$usersStmt->execute(['id_agence' => $idAgence]);

		$biensStmt = $db->prepare('SELECT COUNT(*) FROM bien WHERE id_agence = :id_agence AND deleted_at IS NULL');
		$biensStmt->execute(['id_agence' => $idAgence]);

		return [
			'users' => (int) $usersStmt->fetchColumn(),
			'biens' => (int) $biensStmt->fetchColumn(),
			'limitUsers' => $plan && $plan['limite_utilisateurs'] !== null ? (int) $plan['limite_utilisateurs'] : null,
			'limitBiens' => $plan && $plan['limite_biens'] !== null ? (int) $plan['limite_biens'] : null,
		];
	}

	public static function canAddUser(int $idAgence): bool
	{
		$usage = self::usage($idAgence);
		return $usage['limitUsers'] === null || $usage['users'] < $usage['limitUsers'];
	}

	public static function canAddBien(int $idAgence): bool
	{
		$usage = self::usage($idAgence);
		return $usage['limitBiens'] === null || $usage['biens'] < $usage['limitBiens'];
	}
}
