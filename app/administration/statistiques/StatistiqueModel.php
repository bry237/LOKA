<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

/** Analyses plateforme : revenus, occupation du parc, croissance, classement des agences. */
final class StatistiqueModel
{
	public static function kpis(): array
	{
		$db = Database::connection();

		$revenueTotal = (float) $db->query(
			"SELECT COALESCE(SUM(montant), 0) FROM facture_abonnement WHERE statut = 'PAID'"
		)->fetchColumn();

		$mrr = (float) $db->query(
			"SELECT COALESCE(SUM(ab.prix_mensuel), 0)
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.statut = 'ACTIVE'"
		)->fetchColumn();

		$biens = $db->query(
			"SELECT COUNT(*) AS total, SUM(statut = 'OCCUPIED') AS occupes
			 FROM bien WHERE deleted_at IS NULL"
		)->fetch();
		$totalBiens = (int) ($biens['total'] ?? 0);
		$occupes = (int) ($biens['occupes'] ?? 0);
		$occupancyRate = $totalBiens > 0 ? round($occupes * 100 / $totalBiens, 1) : 0.0;

		$usersNow = (int) $db->query(
			"SELECT COUNT(*) FROM utilisateur WHERE deleted_at IS NULL AND created_at >= NOW() - INTERVAL 30 DAY"
		)->fetchColumn();
		$usersPrev = (int) $db->query(
			"SELECT COUNT(*) FROM utilisateur WHERE deleted_at IS NULL
			 AND created_at >= NOW() - INTERVAL 60 DAY AND created_at < NOW() - INTERVAL 30 DAY"
		)->fetchColumn();
		$userGrowth = $usersPrev > 0 ? round(($usersNow - $usersPrev) * 100 / $usersPrev, 1) : ($usersNow > 0 ? 100.0 : 0.0);

		return [
			'revenueTotal' => $revenueTotal,
			'mrr' => $mrr,
			'occupancyRate' => $occupancyRate,
			'totalBiens' => $totalBiens,
			'userGrowth' => $userGrowth,
			'usersNow30j' => $usersNow,
		];
	}

	/** Revenus encaissés (factures PAID) regroupés par mois, sur les N derniers mois. */
	public static function revenueByMonth(int $months = 12): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT DATE_FORMAT(paid_at, '%Y-%m') AS mois, SUM(montant) AS total
			 FROM facture_abonnement
			 WHERE statut = 'PAID' AND paid_at >= :since
			 GROUP BY mois ORDER BY mois"
		);
		$stmt->execute(['since' => (new DateTimeImmutable("-{$months} months"))->format('Y-m-01')]);
		$rows = $stmt->fetchAll();

		return self::fillMonths($rows, $months, 'mois', 'total');
	}

	/** Nouveaux utilisateurs par mois, sur les N derniers mois. */
	public static function userGrowthByMonth(int $months = 12): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS total
			 FROM utilisateur
			 WHERE deleted_at IS NULL AND created_at >= :since
			 GROUP BY mois ORDER BY mois"
		);
		$stmt->execute(['since' => (new DateTimeImmutable("-{$months} months"))->format('Y-m-01')]);
		$rows = $stmt->fetchAll();

		return self::fillMonths($rows, $months, 'mois', 'total');
	}

	/** Comble les mois sans données avec des zéros, pour un axe de temps continu. */
	private static function fillMonths(array $rows, int $months, string $keyCol, string $valueCol): array
	{
		$byMonth = [];
		foreach ($rows as $row) {
			$byMonth[$row[$keyCol]] = (float) $row[$valueCol];
		}

		$labels = [];
		$values = [];
		for ($i = $months - 1; $i >= 0; $i--) {
			$key = (new DateTimeImmutable("-{$i} months"))->format('Y-m');
			$labels[] = (new DateTimeImmutable($key . '-01'))->format('M Y');
			$values[] = $byMonth[$key] ?? 0.0;
		}

		return ['labels' => $labels, 'values' => $values];
	}

	/** Classement des agences par revenu encaissé (lifetime), avec quelques indicateurs. */
	public static function topAgences(int $limit = 10): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT a.id_agence, a.nom, a.ville, a.statut,
					COALESCE((SELECT SUM(f.montant) FROM facture_abonnement f WHERE f.id_agence = a.id_agence AND f.statut = 'PAID'), 0) AS revenu,
					(SELECT COUNT(*) FROM bien b WHERE b.id_agence = a.id_agence AND b.deleted_at IS NULL) AS nb_biens,
					(SELECT COUNT(*) FROM utilisateur u WHERE u.id_agence = a.id_agence AND u.deleted_at IS NULL) AS nb_utilisateurs,
					(SELECT ab.nom FROM agence_abonnement aa
					 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
					 WHERE aa.id_agence = a.id_agence AND aa.statut = 'ACTIVE'
					 ORDER BY aa.date_debut DESC LIMIT 1) AS plan_nom
			 FROM agence a
			 WHERE a.deleted_at IS NULL
			 ORDER BY revenu DESC, nb_biens DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	/** Répartition du parc (biens) et des agences par ville, top N villes. */
	public static function geographicDistribution(int $limit = 8): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT ad.ville,
					COUNT(DISTINCT b.id_bien) AS nb_biens,
					COUNT(DISTINCT a.id_agence) AS nb_agences
			 FROM bien b
			 INNER JOIN adresse ad ON ad.id_adresse = b.id_adresse
			 INNER JOIN agence a ON a.id_agence = b.id_agence
			 WHERE b.deleted_at IS NULL
			 GROUP BY ad.ville
			 ORDER BY nb_biens DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
