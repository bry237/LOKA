<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class DocumentAgenceModel
{
	/**
	 * @param array{stored_name: string, original_name: string, mime_type: string, size: int} $uploadResult
	 */
	public static function create(int $idAgence, string $type, array $uploadResult): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO document_agence (id_agence, type, nom_original, mime_type, taille_octets, chemin_stockage)
			 VALUES (:id_agence, :type, :nom_original, :mime_type, :taille_octets, :chemin_stockage)'
		);
		$stmt->execute([
			'id_agence' => $idAgence,
			'type' => $type,
			'nom_original' => $uploadResult['original_name'],
			'mime_type' => $uploadResult['mime_type'],
			'taille_octets' => $uploadResult['size'],
			'chemin_stockage' => $uploadResult['stored_name'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function listForAgence(int $idAgence): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_document_agence, type, nom_original, mime_type, taille_octets, statut_verification,
					analyse_ia_resume, analyse_ia_alertes, analyse_ia_date, date_validation, motif_rejet, created_at
			 FROM document_agence
			 WHERE id_agence = :id_agence
			 ORDER BY created_at ASC'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return array_map(static function (array $row): array {
			$row['analyse_ia_alertes'] = $row['analyse_ia_alertes'] ? json_decode((string) $row['analyse_ia_alertes'], true) : [];
			return $row;
		}, $stmt->fetchAll());
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_document_agence, id_agence, type, nom_original, mime_type, chemin_stockage, statut_verification
			 FROM document_agence WHERE id_document_agence = :id LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function setStatus(int $id, string $statut, ?int $validateurId, ?string $motif = null): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE document_agence
			 SET statut_verification = :statut, id_utilisateur_validateur = :validateur, date_validation = NOW(), motif_rejet = :motif
			 WHERE id_document_agence = :id'
		);
		$stmt->execute([
			'statut' => $statut,
			'validateur' => $validateurId,
			'motif' => $motif,
			'id' => $id,
		]);
	}

	public static function saveAnalysis(int $id, array $analysis): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE document_agence SET analyse_ia_resume = :resume, analyse_ia_alertes = :alertes, analyse_ia_date = NOW()
			 WHERE id_document_agence = :id'
		);
		$stmt->execute([
			'resume' => $analysis['resume'],
			'alertes' => json_encode($analysis['alertes'], JSON_UNESCAPED_UNICODE),
			'id' => $id,
		]);
	}
}
