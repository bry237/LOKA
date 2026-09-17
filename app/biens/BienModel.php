<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

final class BienModel
{
	public static function types(): array
	{
		return Database::connection()
			->query('SELECT id_type_bien, nom, description FROM type_bien ORDER BY nom')
			->fetchAll();
	}

	public static function equipments(): array
	{
		return Database::connection()
			->query('SELECT id_equipement, nom, description FROM equipement ORDER BY nom')
			->fetchAll();
	}

	public static function buildings(int $agencyId): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT i.id_immeuble, i.id_agence, i.id_adresse, i.nom, i.description, i.nombre_etages,
					a.ligne1, a.ligne2, a.code_postal, a.ville, a.region, a.pays
			 FROM immeuble i
			 INNER JOIN adresse a ON a.id_adresse = i.id_adresse
			 WHERE i.id_agence = :id_agence
			 ORDER BY a.ville, i.nom, i.id_immeuble'
		);
		$stmt->execute(['id_agence' => $agencyId]);
		return $stmt->fetchAll();
	}

	public static function addresses(int $agencyId): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT DISTINCT a.id_adresse, a.ligne1, a.ligne2, a.code_postal, a.ville, a.region, a.pays
			 FROM adresse a
			 LEFT JOIN immeuble i ON i.id_adresse = a.id_adresse AND i.id_agence = :id_agence_immeuble
			 LEFT JOIN bien b ON b.id_adresse = a.id_adresse AND b.id_agence = :id_agence_bien
			 WHERE i.id_immeuble IS NOT NULL OR b.id_bien IS NOT NULL
			 ORDER BY a.ville, a.ligne1'
		);
		$stmt->execute([
			'id_agence_immeuble' => $agencyId,
			'id_agence_bien' => $agencyId,
		]);
		return $stmt->fetchAll();
	}

	public static function createAddress(array $input): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO adresse (ligne1, ligne2, code_postal, ville, region, pays)
			 VALUES (:ligne1, :ligne2, :code_postal, :ville, :region, :pays)'
		);
		$stmt->execute([
			'ligne1' => trim((string) $input['ligne1']),
			'ligne2' => self::nullable($input['ligne2'] ?? null),
			'code_postal' => self::nullable($input['code_postal'] ?? null),
			'ville' => trim((string) $input['ville']),
			'region' => self::nullable($input['region'] ?? null),
			'pays' => trim((string) ($input['pays'] ?? 'France')) ?: 'France',
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function createBuilding(int $agencyId, array $input): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$addressId = filter_var($input['id_adresse'] ?? null, FILTER_VALIDATE_INT);
			if ($addressId === false || $addressId === null || $addressId < 1) {
				$addressId = self::createAddress($input);
			}

			$addressCheck = $db->prepare(
				'SELECT 1
				 FROM adresse a
				 WHERE a.id_adresse = :id_adresse
				 AND NOT EXISTS (
					SELECT 1 FROM immeuble i
					WHERE i.id_adresse = a.id_adresse AND i.id_agence <> :id_agence
				 )
				 AND NOT EXISTS (
					SELECT 1 FROM bien b
					WHERE b.id_adresse = a.id_adresse AND b.id_agence <> :id_agence_bien
				 )
				 LIMIT 1'
			);
			$addressCheck->execute([
				'id_adresse' => $addressId,
				'id_agence' => $agencyId,
				'id_agence_bien' => $agencyId,
			]);
			if (!$addressCheck->fetchColumn()) {
				throw new InvalidArgumentException('Cette adresse appartient déjà à une autre agence.');
			}

			$stmt = $db->prepare(
				'INSERT INTO immeuble (id_agence, id_adresse, nom, description, nombre_etages)
				 VALUES (:id_agence, :id_adresse, :nom, :description, :nombre_etages)'
			);
			$stmt->execute([
				'id_agence' => $agencyId,
				'id_adresse' => $addressId,
				'nom' => self::nullable($input['nom'] ?? null),
				'description' => self::nullable($input['description'] ?? null),
				'nombre_etages' => (int) ($input['nombre_etages'] ?? 0),
			]);
			$db->commit();
			return (int) $db->lastInsertId();
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}

	public static function createEquipment(array $input): int
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO equipement (nom, description) VALUES (:nom, :description)'
		);
		$stmt->execute([
			'nom' => trim((string) $input['nom']),
			'description' => self::nullable($input['description'] ?? null),
		]);
		return (int) Database::connection()->lastInsertId();
	}

	public static function list(int $agencyId, array $filters = []): array
	{
		$where = ['b.id_agence = :id_agence'];
		$params = ['id_agence' => $agencyId];
		if (($filters['q'] ?? '') !== '') {
			$where[] = '(b.reference LIKE :query OR b.titre LIKE :query OR a.ville LIKE :query)';
			$params['query'] = '%' . trim((string) $filters['q']) . '%';
		}
		if (($filters['statut'] ?? '') !== '') {
			$where[] = 'b.statut = :statut';
			$params['statut'] = $filters['statut'];
		} else {
			$where[] = 'b.deleted_at IS NULL';
		}
		if (($filters['id_type_bien'] ?? '') !== '') {
			$where[] = 'b.id_type_bien = :id_type_bien';
			$params['id_type_bien'] = (int) $filters['id_type_bien'];
		}
		if (($filters['loyer_max'] ?? '') !== '') {
			$where[] = 'b.loyer <= :loyer_max';
			$params['loyer_max'] = (float) $filters['loyer_max'];
		}
		if (($filters['surface_min'] ?? '') !== '') {
			$where[] = 'b.surface >= :surface_min';
			$params['surface_min'] = (float) $filters['surface_min'];
		}
		$stmt = Database::connection()->prepare(
			'SELECT b.id_bien, b.reference, b.titre, b.surface, b.nombre_pieces, b.loyer, b.charges,
					b.statut, t.nom AS type_bien, a.ville
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse a ON a.id_adresse = b.id_adresse
			 WHERE ' . implode(' AND ', $where) . '
			 ORDER BY b.updated_at DESC, b.id_bien DESC'
		);
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public static function find(int $agencyId, int $propertyId): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT b.*, t.nom AS type_bien, a.ligne1, a.ligne2, a.code_postal, a.ville, a.region, a.pays,
					i.nom AS immeuble_nom
			 FROM bien b
			 INNER JOIN type_bien t ON t.id_type_bien = b.id_type_bien
			 INNER JOIN adresse a ON a.id_adresse = b.id_adresse
			 LEFT JOIN immeuble i ON i.id_immeuble = b.id_immeuble AND i.id_agence = b.id_agence
			 WHERE b.id_bien = :id_bien AND b.id_agence = :id_agence AND b.deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id_bien' => $propertyId, 'id_agence' => $agencyId]);
		$property = $stmt->fetch();
		return $property ?: null;
	}

	public static function create(int $agencyId, array $input): int
	{
		$db = Database::connection();
		self::assertReferencesBelongToAgency($agencyId, $input);

		$stmt = $db->prepare(
			'INSERT INTO bien (id_agence, id_type_bien, id_adresse, id_immeuble, reference, titre, surface,
				nombre_pieces, etage, loyer, charges, caution, statut, description)
			 VALUES (:id_agence, :id_type_bien, :id_adresse, :id_immeuble, :reference, :titre, :surface,
				:nombre_pieces, :etage, :loyer, :charges, :caution, :statut, :description)'
		);
		$stmt->execute(self::propertyParams($agencyId, $input));
		return (int) $db->lastInsertId();
	}

	public static function update(int $agencyId, int $propertyId, array $input): bool
	{
		self::assertReferencesBelongToAgency($agencyId, $input);
		$params = self::propertyParams($agencyId, $input);
		$params['id_bien'] = $propertyId;
		$stmt = Database::connection()->prepare(
			'UPDATE bien SET id_type_bien = :id_type_bien, id_adresse = :id_adresse, id_immeuble = :id_immeuble,
				reference = :reference, titre = :titre, surface = :surface, nombre_pieces = :nombre_pieces,
				etage = :etage, loyer = :loyer, charges = :charges, caution = :caution, statut = :statut,
				description = :description
			 WHERE id_bien = :id_bien AND id_agence = :id_agence AND deleted_at IS NULL'
		);
		$stmt->execute($params);
		return $stmt->rowCount() > 0;
	}

	public static function archive(int $agencyId, int $propertyId): bool
	{
		$stmt = Database::connection()->prepare(
			"UPDATE bien SET statut = 'ARCHIVED', deleted_at = NOW()
			 WHERE id_bien = :id_bien AND id_agence = :id_agence AND deleted_at IS NULL"
		);
		$stmt->execute(['id_bien' => $propertyId, 'id_agence' => $agencyId]);
		return $stmt->rowCount() > 0;
	}

	private static function propertyParams(int $agencyId, array $input): array
	{
		return [
			'id_agence' => $agencyId,
			'id_type_bien' => (int) $input['id_type_bien'],
			'id_adresse' => (int) $input['id_adresse'],
			'id_immeuble' => ($input['id_immeuble'] ?? '') === '' ? null : (int) $input['id_immeuble'],
			'reference' => trim((string) $input['reference']),
			'titre' => trim((string) $input['titre']),
			'surface' => (float) $input['surface'],
			'nombre_pieces' => (int) $input['nombre_pieces'],
			'etage' => ($input['etage'] ?? '') === '' ? null : (int) $input['etage'],
			'loyer' => (float) $input['loyer'],
			'charges' => (float) $input['charges'],
			'caution' => (float) $input['caution'],
			'statut' => $input['statut'] ?? 'CREATED',
			'description' => self::nullable($input['description'] ?? null),
		];
	}

	private static function assertReferencesBelongToAgency(int $agencyId, array $input): void
	{
		$db = Database::connection();
		$type = $db->prepare('SELECT 1 FROM type_bien WHERE id_type_bien = :id');
		$type->execute(['id' => (int) $input['id_type_bien']]);
		if (!$type->fetchColumn()) {
			throw new InvalidArgumentException('Le type de bien sélectionné est invalide.');
		}

		$address = $db->prepare(
			'SELECT 1 FROM adresse a
			 WHERE a.id_adresse = :id_adresse
			 AND NOT EXISTS (SELECT 1 FROM bien b WHERE b.id_adresse = a.id_adresse AND b.id_agence <> :id_agence)
			 LIMIT 1'
		);
		$address->execute(['id_adresse' => (int) $input['id_adresse'], 'id_agence' => $agencyId]);
		if (!$address->fetchColumn()) {
			throw new InvalidArgumentException('L’adresse est invalide ou appartient à une autre agence.');
		}

		if (($input['id_immeuble'] ?? '') !== '') {
			$building = $db->prepare('SELECT 1 FROM immeuble WHERE id_immeuble = :id AND id_agence = :agency');
			$building->execute(['id' => (int) $input['id_immeuble'], 'agency' => $agencyId]);
			if (!$building->fetchColumn()) {
				throw new InvalidArgumentException('L’immeuble sélectionné appartient à une autre agence.');
			}
		}
	}

	private static function nullable(mixed $value): ?string
	{
		$value = trim((string) ($value ?? ''));
		return $value === '' ? null : $value;
	}
}