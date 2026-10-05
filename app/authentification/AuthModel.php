<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Logger.php';
require_once dirname(__DIR__, 2) . '/core/FileUpload.php';
require_once dirname(__DIR__, 2) . '/core/ClaudeVisionAnalyzer.php';
require_once dirname(__DIR__) . '/administration/agences/DocumentAgenceModel.php';
require_once dirname(__DIR__) . '/administration/utilisateurs/DocumentUtilisateurModel.php';

final class AuthModel
{
	public static function emailExists(string $email): bool
	{
		$stmt = Database::connection()->prepare('SELECT 1 FROM utilisateur WHERE LOWER(email) = LOWER(:email) LIMIT 1');
		$stmt->execute(['email' => $email]);
		return (bool) $stmt->fetchColumn();
	}

	public static function registerTenant(array $data, array $files = []): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = $db->query("SELECT id_role FROM role WHERE nom = 'Locataire' LIMIT 1")->fetchColumn();
			if (!$role) {
				throw new RuntimeException('Le rôle locataire est introuvable.');
			}

			$userStmt = $db->prepare(
				'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, indicatif_pays, statut)
				 VALUES (NULL, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, :indicatif_pays, \'PENDING\')'
			);
			$userStmt->execute([
				'id_role' => $role,
				'nom' => $data['nom'],
				'prenom' => $data['prenom'],
				'email' => $data['email'],
				'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
				'telephone' => $data['telephone'],
				'indicatif_pays' => $data['indicatif_pays'] ?? null,
			]);
			$userId = (int) $db->lastInsertId();

			$tenantStmt = $db->prepare(
				'INSERT INTO locataire (id_utilisateur, id_agence, nom, prenom, email, telephone, indicatif_pays, adresse, code_postal, ville, pays,
					date_naissance, profession, revenu_mensuel_fourchette, statut)
				 VALUES (:id_utilisateur, NULL, :nom, :prenom, :email, :telephone, :indicatif_pays, :adresse, :code_postal, :ville, :pays,
					:date_naissance, :profession, :revenu_mensuel_fourchette, \'ACTIVE\')'
			);
			$tenantStmt->execute([
				'id_utilisateur' => $userId,
<<<<<<< HEAD
				'nom' => $data['nom'],
				'prenom' => $data['prenom'],
				'email' => $data['email'],
				'telephone' => $data['telephone'],
				'adresse' => $data['adresse'],
				'code_postal' => $data['code_postal'],
				'ville' => $data['ville'],
				'pays' => $data['pays'],
				'date_naissance' => $data['date_naissance'],
				'profession' => $data['profession'],
				'revenu_mensuel_fourchette' => $data['revenu_mensuel_fourchette'],
=======
				'nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'],
				'telephone' => $data['telephone'], 'indicatif_pays' => $data['indicatif_pays'] ?? null,
				'adresse' => $data['adresse'], 'code_postal' => $data['code_postal'],
				'ville' => $data['ville'], 'pays' => $data['pays'], 'date_naissance' => $data['date_naissance'],
				'profession' => $data['profession'], 'revenu_mensuel_fourchette' => $data['revenu_mensuel_fourchette'],
>>>>>>> main
			]);

<<<<<<< HEAD
	public static function registerProprietor(array $data): int
	{
		return self::registerProfile($data, 'Proprietaire', 'proprietaire', [
			'nom',
			'prenom',
			'email',
			'telephone',
			'adresse',
			'code_postal',
			'ville',
			'pays',
			'date_naissance',
			'projet'
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
				'nom' => $data['agence_nom'],
				'email' => $data['agence_email'],
				'telephone' => $data['agence_telephone'],
				'adresse' => $data['agence_adresse'],
				'ville' => $data['agence_ville'],
				'code_postal' => $data['agence_code_postal'],
				'pays' => $data['agence_pays'],
=======
			Logger::audit('CREATE', 'utilisateur', $userId, $userId, null, null, [
				'nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'], 'role' => 'Locataire',
>>>>>>> main
			]);

			$db->commit();

			$fullName = trim($data['prenom'] . ' ' . $data['nom']);
			self::storeUserDocument($userId, 'PIECE_IDENTITE', $files['piece_identite'] ?? null, $fullName);
			self::storeUserDocument($userId, 'JUSTIFICATIF_DOMICILE', $files['justificatif_domicile'] ?? null, $fullName);

			return $userId;
		} catch (Throwable $exception) {
			if ($db->inTransaction()) $db->rollBack();
			throw $exception;
		}
	}

	public static function registerProprietor(array $data, array $files = []): int
	{
		$userId = self::registerProfile($data, 'Proprietaire', 'proprietaire', [
			'nom', 'prenom', 'email', 'telephone', 'indicatif_pays', 'adresse', 'code_postal', 'ville', 'pays', 'date_naissance', 'projet'
		], false, 'PENDING');

		$fullName = trim($data['prenom'] . ' ' . $data['nom']);
		self::storeUserDocument($userId, 'PIECE_IDENTITE', $files['piece_identite'] ?? null, $fullName);
		self::storeUserDocument($userId, 'JUSTIFICATIF_DOMICILE', $files['justificatif_domicile'] ?? null, $fullName);

		return $userId;
	}

	public static function registerAgency(array $data, array $files = []): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = self::roleId($db, 'Administrateur agence');
			$agency = $db->prepare(
				'INSERT INTO agence (nom, email, telephone, indicatif_pays, adresse, ville, code_postal, pays, statut)
				 VALUES (:nom, :email, :telephone, :indicatif_pays, :adresse, :ville, :code_postal, :pays, \'PENDING\')'
			);
			$agency->execute([
				'nom' => $data['agence_nom'], 'email' => $data['agence_email'], 'telephone' => $data['agence_telephone'],
				'indicatif_pays' => $data['agence_indicatif_pays'] ?? null,
				'adresse' => $data['agence_adresse'], 'ville' => $data['agence_ville'],
				'code_postal' => $data['agence_code_postal'], 'pays' => $data['agence_pays'],
			]);
			$agencyId = (int) $db->lastInsertId();
			$userId = self::insertUser($db, $role, $data, $agencyId);

			$freePlan = $db->query("SELECT id_abonnement FROM abonnement WHERE nom = 'Gratuit' LIMIT 1")->fetchColumn();
			if ($freePlan) {
				$subscription = $db->prepare(
					'INSERT INTO agence_abonnement (id_agence, id_abonnement, date_debut, statut)
					 VALUES (:id_agence, :id_abonnement, CURDATE(), \'ACTIVE\')'
				);
				$subscription->execute(['id_agence' => $agencyId, 'id_abonnement' => $freePlan]);
			}

			Logger::audit('CREATE', 'agence', $agencyId, $userId, $agencyId, null, [
				'nom' => $data['agence_nom'], 'email' => $data['agence_email'],
			]);
			Logger::audit('CREATE', 'utilisateur', $userId, $userId, $agencyId, null, [
				'nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email'], 'role' => 'Administrateur agence',
			]);

			$db->commit();

			self::storeAgencyDocument($agencyId, 'PIECE_IDENTITE', $files['piece_identite_responsable'] ?? null, trim($data['prenom'] . ' ' . $data['nom']));
			self::storeAgencyDocument($agencyId, 'JUSTIFICATIF_IMMATRICULATION', $files['justificatif_agence'] ?? null, $data['agence_nom']);

			return $userId;
		} catch (Throwable $exception) {
			if ($db->inTransaction()) $db->rollBack();
			throw $exception;
		}
	}

	private static function storeAgencyDocument(int $agencyId, string $type, ?array $file, string $expectedName): void
	{
		if (!$file) {
			return;
		}
		try {
			$storage = require dirname(__DIR__, 2) . '/config/storage.php';
			$destinationDir = rtrim($storage['agence_documents_path'], '/\\') . '/agence-' . $agencyId;
			$uploadResult = FileUpload::store($file, $destinationDir, ['image/jpeg', 'image/png', 'application/pdf'], 5 * 1024 * 1024);
			$documentId = DocumentAgenceModel::create($agencyId, $type, $uploadResult);

			$analysis = ClaudeVisionAnalyzer::analyze(
				$destinationDir . DIRECTORY_SEPARATOR . $uploadResult['stored_name'],
				$uploadResult['mime_type'],
				$type,
				$expectedName
			);
			DocumentAgenceModel::saveAnalysis($documentId, $analysis);
		} catch (Throwable $exception) {
			// Le compte agence existe déjà à ce stade : une erreur de stockage/analyse ne doit pas
			// faire échouer l'inscription, l'admin verra simplement "aucun document" en revue.
		}
	}

	private static function storeUserDocument(int $userId, string $type, ?array $file, string $expectedName): void
	{
		if (!$file) {
			return;
		}
		try {
			$storage = require dirname(__DIR__, 2) . '/config/storage.php';
			$destinationDir = rtrim($storage['utilisateur_documents_path'], '/\\') . '/utilisateur-' . $userId;
			$uploadResult = FileUpload::store($file, $destinationDir, ['image/jpeg', 'image/png', 'application/pdf'], 5 * 1024 * 1024);
			$documentId = DocumentUtilisateurModel::create($userId, $type, $uploadResult);

			$analysis = ClaudeVisionAnalyzer::analyze(
				$destinationDir . DIRECTORY_SEPARATOR . $uploadResult['stored_name'],
				$uploadResult['mime_type'],
				$type,
				$expectedName
			);
			DocumentUtilisateurModel::saveAnalysis($documentId, $analysis);
		} catch (Throwable $exception) {
			// Le compte existe déjà à ce stade : une erreur de stockage/analyse ne doit pas
			// faire échouer l'inscription, l'admin verra simplement "aucun document" en revue.
		}
	}

	private static function registerProfile(array $data, string $roleName, string $table, array $profileFields, bool $hasStatus = true, string $statut = 'ACTIVE'): int
	{
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$role = self::roleId($db, $roleName);
			$userId = self::insertUser($db, $role, $data, null, $statut);
			$columns = implode(', ', $profileFields);
			$parameters = implode(', ', array_map(static fn(string $field): string => ':' . $field, $profileFields));
			$statusColumns = $hasStatus ? ', statut' : '';
			$statusValues = $hasStatus ? ", 'ACTIVE'" : '';
			$profile = $db->prepare("INSERT INTO {$table} (id_utilisateur, id_agence, {$columns}{$statusColumns}) VALUES (:id_utilisateur, NULL, {$parameters}{$statusValues})");
			$values = ['id_utilisateur' => $userId];
			foreach ($profileFields as $field) $values[$field] = $data[$field];
			$profile->execute($values);

			Logger::audit('CREATE', 'utilisateur', $userId, $userId, null, null, [
				'nom' => $data['nom'] ?? null, 'prenom' => $data['prenom'] ?? null, 'email' => $data['email'] ?? null, 'role' => $roleName,
			]);

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

	private static function insertUser(PDO $db, int $roleId, array $data, ?int $agencyId, string $statut = 'ACTIVE'): int
	{
		$stmt = $db->prepare(
			'INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, indicatif_pays, statut)
			 VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, :indicatif_pays, :statut)'
		);
		$stmt->execute([
<<<<<<< HEAD
			'id_agence' => $agencyId,
			'id_role' => $roleId,
			'nom' => $data['nom'],
			'prenom' => $data['prenom'],
			'email' => $data['email'],
			'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
			'telephone' => $data['telephone'],
=======
			'id_agence' => $agencyId, 'id_role' => $roleId, 'nom' => $data['nom'], 'prenom' => $data['prenom'],
			'email' => $data['email'], 'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
			'telephone' => $data['telephone'], 'indicatif_pays' => $data['indicatif_pays'] ?? null, 'statut' => $statut,
>>>>>>> main
		]);
		return (int) $db->lastInsertId();
	}

	public static function createResetToken(string $email): ?string
	{
		$db = Database::connection();
		$stmt = $db->prepare("SELECT id_utilisateur FROM utilisateur WHERE LOWER(email) = LOWER(:email) AND statut = 'ACTIVE' AND deleted_at IS NULL LIMIT 1");
		$stmt->execute(['email' => $email]);
		$userId = $stmt->fetchColumn();
		if (!$userId) {
			return null;
		}
		$token = bin2hex(random_bytes(32));
		$hash = hash('sha256', $token);
		$expiration = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
		// Stocker le token dans session_utilisateur avec un marqueur spécial
		$insert = $db->prepare(
			"INSERT INTO session_utilisateur (id_utilisateur, token_hash, adresse_ip, user_agent, date_expiration, date_derniere_activite)
			 VALUES (:id_utilisateur, :token_hash, 'RESET', 'password_reset', :date_expiration, NOW())"
		);
		$insert->execute([
			'id_utilisateur' => $userId,
			'token_hash' => $hash,
			'date_expiration' => $expiration,
		]);
		return $token;
	}

	public static function findByResetToken(string $token): ?array
	{
		$hash = hash('sha256', $token);
		$stmt = Database::connection()->prepare(
			"SELECT s.id_utilisateur, u.email, u.nom, u.prenom
			 FROM session_utilisateur s
			 INNER JOIN utilisateur u ON u.id_utilisateur = s.id_utilisateur
			 WHERE s.token_hash = :hash AND s.date_expiration > NOW() AND s.adresse_ip = 'RESET'
			   AND u.statut = 'ACTIVE' AND u.deleted_at IS NULL
			 LIMIT 1"
		);
		$stmt->execute(['hash' => $hash]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function resetPassword(string $token, string $newPassword): bool
	{
		$user = self::findByResetToken($token);
		if (!$user) {
			return false;
		}
		$db = Database::connection();
		$db->beginTransaction();
		try {
			$update = $db->prepare('UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id_utilisateur');
			$update->execute([
				'mot_de_passe' => password_hash($newPassword, PASSWORD_DEFAULT),
				'id_utilisateur' => $user['id_utilisateur'],
			]);
			// Supprimer le token de reset
			$delete = $db->prepare("DELETE FROM session_utilisateur WHERE token_hash = :hash AND adresse_ip = 'RESET'");
			$delete->execute(['hash' => hash('sha256', $token)]);
			$db->commit();
			return true;
		} catch (Throwable $exception) {
			$db->rollBack();
			throw $exception;
		}
	}
}
