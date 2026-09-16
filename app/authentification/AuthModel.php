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
				'INSERT INTO locataire (id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays,
					date_naissance, profession, revenu_mensuel_fourchette, statut)
				 VALUES (NULL, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays,
					:date_naissance, :profession, :revenu_mensuel_fourchette, \'ACTIVE\')'
			);
			$tenantStmt->execute([
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
}
