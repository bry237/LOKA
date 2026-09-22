<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

final class ProprietaireModel
{
	public static function list(int $agencyId, string $query = ''): array
	{
		$where = ['p.id_agence = :id_agence', 'p.deleted_at IS NULL'];
		$params = ['id_agence' => $agencyId];
		if (trim($query) !== '') {
			$where[] = '(p.nom LIKE :query OR p.prenom LIKE :query OR p.email LIKE :query)';
			$params['query'] = '%' . trim($query) . '%';
		}
		$stmt = Database::connection()->prepare(
			'SELECT p.id_proprietaire, p.nom, p.prenom, p.email, p.telephone, p.ville,
					COUNT(DISTINCT bp.id_bien) AS biens_count
			 FROM proprietaire p
			 LEFT JOIN bien_proprietaire bp ON bp.id_proprietaire = p.id_proprietaire AND bp.date_fin IS NULL
			 WHERE ' . implode(' AND ', $where) . '
			 GROUP BY p.id_proprietaire
			 ORDER BY p.nom, p.prenom'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function find(int $agencyId, int $ownerId): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT * FROM proprietaire
			 WHERE id_proprietaire = :id_proprietaire AND id_agence = :id_agence AND deleted_at IS NULL LIMIT 1'
		);
		$stmt->execute(['id_proprietaire' => $ownerId, 'id_agence' => $agencyId]);
		$owner = $stmt->fetch();
		return $owner ?: null;
	}

	public static function create(int $agencyId, array $input): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO proprietaire (id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays, date_naissance)
			 VALUES (:id_agence, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays, :date_naissance)'
		);
		$stmt->execute(self::params($agencyId, $input));
		return (int) Database::connection()->lastInsertId();
	}

	public static function update(int $agencyId, int $ownerId, array $input): bool
	{
		$params = self::params($agencyId, $input);
		$params['id_proprietaire'] = $ownerId;
		$stmt = Database::connection()->prepare(
			'UPDATE proprietaire SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
				adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays,
				date_naissance = :date_naissance
			 WHERE id_proprietaire = :id_proprietaire AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public static function archive(int $agencyId, int $ownerId): bool
	{
		$stmt = Database::connection()->prepare(
			'UPDATE proprietaire SET deleted_at = NOW()
			 WHERE id_proprietaire = :id_proprietaire AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute(['id_proprietaire' => $ownerId, 'id_agence' => $agencyId]);
		return $stmt->rowCount() > 0;
	}

	public static function properties(int $agencyId, int $ownerId): array
	{
		self::assertOwner($agencyId, $ownerId);
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre, b.statut, b.surface, b.loyer, bp.quote_part, bp.date_debut
			 FROM bien_proprietaire bp
			 INNER JOIN bien b ON b.id_bien = bp.id_bien AND b.id_agence = :id_agence
			 WHERE bp.id_proprietaire = :id_proprietaire AND bp.date_fin IS NULL AND b.deleted_at IS NULL
			 ORDER BY b.titre'
		);
		$stmt->execute(['id_agence' => $agencyId, 'id_proprietaire' => $ownerId]);
		return $stmt->fetchAll();
	}

	public static function availableProperties(int $agencyId, int $ownerId): array
	{
		self::assertOwner($agencyId, $ownerId);
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre
			 FROM bien b
			 WHERE b.id_agence = :id_agence AND b.deleted_at IS NULL
			 AND NOT EXISTS (
				SELECT 1 FROM bien_proprietaire bp
				WHERE bp.id_bien = b.id_bien AND bp.id_proprietaire = :id_proprietaire AND bp.date_fin IS NULL
			 )
			 ORDER BY b.titre'
		);
		$stmt->execute(['id_agence' => $agencyId, 'id_proprietaire' => $ownerId]);
		return $stmt->fetchAll();
	}

	public static function attachProperty(int $agencyId, int $ownerId, array $input): void
	{
		self::assertOwner($agencyId, $ownerId);
		$propertyId = (int) $input['id_bien'];
		$db = Database::connection();
		$property = $db->prepare('SELECT 1 FROM bien WHERE id_bien = :id_bien AND id_agence = :id_agence AND deleted_at IS NULL');
		$property->execute(['id_bien' => $propertyId, 'id_agence' => $agencyId]);
		if (!$property->fetchColumn()) {
			throw new InvalidArgumentException('Le bien sélectionné appartient à une autre agence.');
		}
		$existing = $db->prepare('SELECT 1 FROM bien_proprietaire WHERE id_bien = :id_bien AND id_proprietaire = :id_proprietaire AND date_fin IS NULL');
		$existing->execute(['id_bien' => $propertyId, 'id_proprietaire' => $ownerId]);
		if ($existing->fetchColumn()) {
			throw new InvalidArgumentException('Ce propriétaire est déjà associé à ce bien.');
		}
		$stmt = $db->prepare(
			'INSERT INTO bien_proprietaire (id_bien, id_proprietaire, quote_part, date_debut)
			 VALUES (:id_bien, :id_proprietaire, :quote_part, :date_debut)'
		);
		$stmt->execute(['id_bien' => $propertyId, 'id_proprietaire' => $ownerId, 'quote_part' => (float) $input['quote_part'], 'date_debut' => $input['date_debut']]);
	}

	public static function detachProperty(int $agencyId, int $ownerId, int $propertyId): void
	{
		self::assertOwner($agencyId, $ownerId);
		$stmt = Database::connection()->prepare(
			'UPDATE bien_proprietaire bp INNER JOIN bien b ON b.id_bien = bp.id_bien
			 SET bp.date_fin = CURRENT_DATE
			 WHERE bp.id_bien = :id_bien AND bp.id_proprietaire = :id_proprietaire
			 AND bp.date_fin IS NULL AND b.id_agence = :id_agence'
		);
		$stmt->execute(['id_bien' => $propertyId, 'id_proprietaire' => $ownerId, 'id_agence' => $agencyId]);
	}

	private static function assertOwner(int $agencyId, int $ownerId): void
	{
		if (!self::find($agencyId, $ownerId)) {
			throw new RuntimeException('Le propriétaire est introuvable ou inaccessible.');
		}
	}

	private static function params(int $agencyId, array $input): array
	{
		return [
			'id_agence' => $agencyId,
			'nom' => trim((string) $input['nom']),
			'prenom' => trim((string) $input['prenom']),
			'email' => self::nullable($input['email'] ?? null),
			'telephone' => self::nullable($input['telephone'] ?? null),
			'adresse' => self::nullable($input['adresse'] ?? null),
			'code_postal' => self::nullable($input['code_postal'] ?? null),
			'ville' => self::nullable($input['ville'] ?? null),
			'pays' => trim((string) ($input['pays'] ?? 'France')) ?: 'France',
			'date_naissance' => self::nullable($input['date_naissance'] ?? null),
		];
	}

	private static function nullable(mixed $value): ?string
	{
		$value = trim((string) ($value ?? ''));
		return $value === '' ? null : $value;
	}
}