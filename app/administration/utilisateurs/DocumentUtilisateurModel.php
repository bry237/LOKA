<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class DocumentUtilisateurModel
{
	/**
	 * @param array{stored_name: string, original_name: string, mime_type: string, size: int} $uploadResult
	 */
	public static function create(int $idUtilisateur, string $type, array $uploadResult): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO document_utilisateur (id_utilisateur, type, nom_original, mime_type, taille_octets, chemin_stockage)
			 VALUES (:id_utilisateur, :type, :nom_original, :mime_type, :taille_octets, :chemin_stockage)'
		);
		$stmt->execute([
			'id_utilisateur' => $idUtilisateur,
			'type' => $type,
			'nom_original' => $uploadResult['original_name'],
			'mime_type' => $uploadResult['mime_type'],
			'taille_octets' => $uploadResult['size'],
			'chemin_stockage' => $uploadResult['stored_name'],
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function listForUtilisateur(int $idUtilisateur): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_document_utilisateur, type, nom_original, mime_type, taille_octets, statut_verification,
					analyse_ia_resume, analyse_ia_alertes, analyse_ia_date, date_validation, motif_rejet, created_at
			 FROM document_utilisateur
			 WHERE id_utilisateur = :id_utilisateur
			 ORDER BY created_at ASC'
		);
		$stmt->execute(['id_utilisateur' => $idUtilisateur]);
		return array_map(static function (array $row): array {
			$row['analyse_ia_alertes'] = $row['analyse_ia_alertes'] ? json_decode((string) $row['analyse_ia_alertes'], true) : [];
			return $row;
		}, $stmt->fetchAll());
	}

	public static function find(int $id): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_document_utilisateur, id_utilisateur, type, nom_original, mime_type, chemin_stockage, statut_verification
			 FROM document_utilisateur WHERE id_document_utilisateur = :id LIMIT 1'
		);
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function setStatus(int $id, string $statut, ?int $validateurId, ?string $motif = null): void
	{
		$stmt = Database::connection()->prepare(
			'UPDATE document_utilisateur
			 SET statut_verification = :statut, id_utilisateur_validateur = :validateur, date_validation = NOW(), motif_rejet = :motif
			 WHERE id_document_utilisateur = :id'
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
			'UPDATE document_utilisateur SET analyse_ia_resume = :resume, analyse_ia_alertes = :alertes, analyse_ia_date = NOW()
			 WHERE id_document_utilisateur = :id'
		);
		$stmt->execute([
			'resume' => $analysis['resume'],
			'alertes' => json_encode($analysis['alertes'], JSON_UNESCAPED_UNICODE),
			'id' => $id,
		]);
	}
}
