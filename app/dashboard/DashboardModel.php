<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

final class DashboardModel
{
	public static function propertyStats(int $agencyId): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN statut = 'AVAILABLE' THEN 1 ELSE 0 END) AS disponibles,
				SUM(CASE WHEN statut = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupes,
				SUM(CASE WHEN statut = 'MAINTENANCE' THEN 1 ELSE 0 END) AS maintenance,
				COALESCE(SUM(surface), 0) AS surface_totale,
				COALESCE(SUM(CASE WHEN statut = 'OCCUPIED' THEN loyer ELSE 0 END), 0) AS revenus_loyers
			 FROM bien
			 WHERE id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetch() ?: [
			'total' => 0,
			'disponibles' => 0,
			'occupes' => 0,
			'maintenance' => 0,
			'surface_totale' => 0,
			'revenus_loyers' => 0,
		];
	}

	public static function occupancyRate(int $agencyId): float
	{
		$stats = self::propertyStats($agencyId);
		$total = (int) $stats['total'];
		if ($total === 0) {
			return 0.0;
		}
		return round((int) $stats['occupes'] / $total * 100, 1);
	}

	public static function contractStats(int $agencyId): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) AS actifs,
				SUM(CASE WHEN statut = 'DRAFT' THEN 1 ELSE 0 END) AS brouillons,
				SUM(CASE WHEN statut IN ('EXPIRED','TERMINATED') THEN 1 ELSE 0 END) AS termines
			 FROM contrat
			 WHERE id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetch() ?: ['total' => 0, 'actifs' => 0, 'brouillons' => 0, 'termines' => 0];
	}

	public static function tenantCount(int $agencyId): int
	{
		$stmt = Database::connection()->prepare(
			"SELECT COUNT(*) FROM locataire WHERE id_agence = :id_agence AND statut = 'ACTIVE' AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return (int) $stmt->fetchColumn();
	}

	public static function ownerCount(int $agencyId): int
	{
		$stmt = Database::connection()->prepare(
			'SELECT COUNT(*) FROM proprietaire WHERE id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return (int) $stmt->fetchColumn();
	}

	public static function overduePayments(int $agencyId): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT COUNT(*) AS nombre,
				COALESCE(SUM(e.montant_total), 0) AS montant
			 FROM echeance e
			 INNER JOIN contrat c ON c.id_contrat = e.id_contrat
			 WHERE c.id_agence = :id_agence
			   AND e.statut IN ('PENDING','LATE')
			   AND e.date_echeance < CURRENT_DATE
			   AND c.deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetch() ?: ['nombre' => 0, 'montant' => 0];
	}

	public static function monthlyRevenue(int $agencyId): float
	{
		$stmt = Database::connection()->prepare(
			"SELECT COALESCE(SUM(p.montant), 0) AS total
			 FROM paiement p
			 INNER JOIN contrat c ON c.id_contrat = p.id_contrat
			 WHERE c.id_agence = :id_agence
			   AND p.statut = 'PAID'
			   AND MONTH(p.date_paiement) = MONTH(CURRENT_DATE)
			   AND YEAR(p.date_paiement) = YEAR(CURRENT_DATE)"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return (float) $stmt->fetchColumn();
	}

	public static function openInterventions(int $agencyId): int
	{
		$stmt = Database::connection()->prepare(
			"SELECT COUNT(*) FROM intervention
			 WHERE id_agence = :id_agence AND statut IN ('OPEN','ASSIGNED','IN_PROGRESS','WAITING')"
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return (int) $stmt->fetchColumn();
	}

	public static function recentProperties(int $agencyId, int $limit = 5): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre, b.statut, b.loyer, t.nom AS type_bien, a.ville
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse a ON a.id_adresse = b.id_adresse
			 WHERE b.id_agence = :id_agence AND b.deleted_at IS NULL
			 ORDER BY b.created_at DESC
			 LIMIT ' . $limit
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetchAll();
	}

	public static function recentInterventions(int $agencyId, int $limit = 5): array
	{
		$stmt = Database::connection()->prepare(
			"SELECT i.id_intervention, i.titre, i.priorite, i.statut, i.created_at,
				b.reference AS bien_reference, b.titre AS bien_titre
			 FROM intervention i
			 INNER JOIN bien b ON b.id_bien = i.id_bien
			 WHERE i.id_agence = :id_agence AND i.statut NOT IN ('CLOSED','CANCELLED')
			 ORDER BY FIELD(i.priorite, 'URGENT','HIGH','MEDIUM','LOW'), i.created_at DESC
			 LIMIT " . $limit
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetchAll();
	}
}
