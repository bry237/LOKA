<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

final class AuthModel
{
	public static function emailExists(string $email): bool
	{
		$stmt = Database::connection()->prepare('SELECT 1 FROM utilisateur WHERE LOWER(email) = LOWER(:email) LIMIT 1');
		$stmt->execute(['email' => $email]);
		return (bool) $stmt->fetchColumn();
	}

	public static function registerTenant(array $data): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = $db->query("SELECT id_role FROM role WHERE nom = 'Locataire' LIMIT 1")->fetchColumn();
			if (!$role) {
				throw new RuntimeException('Le rôle locataire est introuvable.');
			}

			$userStmt = $db->prepare(
				'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut)
				 VALUES (NULL, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, \'ACTIVE\')'
			);
			$userStmt->execute([
				'id_role' => $role,
				'nom' => $data['nom'],
				'prenom' => $data['prenom'],
				'email' => $data['email'],
				'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
				'telephone' => $data['telephone'],
			]);
			$userId = (int) $db->lastInsertId();

			$tenantStmt = $db->prepare(
				'INSERT INTO locataire (id_utilisateur, id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays,
					date_naissance, profession, revenu_mensuel_fourchette, statut)
				 VALUES (:id_utilisateur, NULL, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays,
					:date_naissance, :profession, :revenu_mensuel_fourchette, \'ACTIVE\')'
			);
			$tenantStmt->execute([
				'id_utilisateur' => $userId,
				'nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'],
				'telephone' => $data['telephone'], 'adresse' => $data['adresse'], 'code_postal' => $data['code_postal'],
				'ville' => $data['ville'], 'pays' => $data['pays'], 'date_naissance' => $data['date_naissance'],
				'profession' => $data['profession'], 'revenu_mensuel_fourchette' => $data['revenu_mensuel_fourchette'],
			]);
			$db->commit();
			return $userId;
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}

	public static function registerProprietor(array $data): int
	{
		return self::registerProfile($data, 'Proprietaire', 'proprietaire', [
			'nom', 'prenom', 'email', 'telephone', 'adresse', 'code_postal', 'ville', 'pays', 'date_naissance', 'projet'
		], false);
	}

	public static function registerAgency(array $data): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = self::roleId($db, 'Administrateur agence');
			$agency = $db->prepare(
				'INSERT INTO agence (nom, email, telephone, adresse, ville, code_postal, pays, statut)
				 VALUES (:nom, :email, :telephone, :adresse, :ville, :code_postal, :pays, \'ACTIVE\')'
			);
			$agency->execute([
				'nom' => $data['agence_nom'], 'email' => $data['agence_email'], 'telephone' => $data['agence_telephone'],
				'adresse' => $data['agence_adresse'], 'ville' => $data['agence_ville'],
				'code_postal' => $data['agence_code_postal'], 'pays' => $data['agence_pays'],
			]);
			$agencyId = (int) $db->lastInsertId();
			$userId = self::insertUser($db, $role, $data, $agencyId);
			$db->commit();
			return $userId;
		} catch (Throwable $exception) {
			if ($db->inTransaction()) $db->rollBack();
			throw $exception;
		}
	}

	private static function registerProfile(array $data, string $roleName, string $table, array $profileFields, bool $hasStatus = true): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = self::roleId($db, $roleName);
			$userId = self::insertUser($db, $role, $data, null);
			$columns = implode(', ', $profileFields);
			$parameters = implode(', ', array_map(static fn (string $field): string => ':' . $field, $profileFields));
			$statusColumns = $hasStatus ? ', statut' : '';
			$statusValues = $hasStatus ? ", 'ACTIVE'" : '';
			$profile = $db->prepare("INSERT INTO {$table} (id_utilisateur, id_agence, {$columns}{$statusColumns}) VALUES (:id_utilisateur, NULL, {$parameters}{$statusValues})");
			$values = ['id_utilisateur' => $userId];
			foreach ($profileFields as $field) $values[$field] = $data[$field];
			$profile->execute($values);
			$db->commit();
			return $userId;
		} catch (Throwable $exception) {
			if ($db->inTransaction()) $db->rollBack();
			throw $exception;
		}
	}

	private static function roleId(PDO $db, string $roleName): int
	{
		$stmt = $db->prepare('SELECT id_role FROM role WHERE nom = :nom LIMIT 1');
		$stmt->execute(['nom' => $roleName]);
		$role = $stmt->fetchColumn();
		if (!$role) throw new RuntimeException('Rôle introuvable.');
		return (int) $role;
	}

	private static function insertUser(PDO $db, int $roleId, array $data, ?int $agencyId): int
	{
		$stmt = $db->prepare(
			'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut)
			 VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, \'ACTIVE\')'
		);
		$stmt->execute([
			'id_agence' => $agencyId, 'id_role' => $roleId, 'nom' => $data['nom'], 'prenom' => $data['prenom'],
			'email' => $data['email'], 'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
			'telephone' => $data['telephone'],
		]);
		return (int) $db->lastInsertId();
	}
}
