<?php
declare(strict_types=1);

require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';

final class MonAgenceController
{
	private const INVITABLE_ROLE_IDS = [2, 3, 4, 7];

	public static function data(int $idAgence): array
	{
		return [
			'agency' => MonAgenceModel::agency($idAgence),
			'subscription' => MonAgenceModel::subscription($idAgence),
			'kpis' => MonAgenceModel::kpis($idAgence),
			'portfolio' => MonAgenceModel::propertyPortfolio($idAgence),
			'contractsToWatch' => MonAgenceModel::contractsToWatch($idAgence, 5),
			'todayTasks' => MonAgenceModel::todayTasks($idAgence),
			'activity' => MonAgenceModel::recentActivity($idAgence, 8),
		];
	}

	/**
	 * @return array{members: array, roles: array, usage: array}
	 */
	public static function team(int $idAgence): array
	{
		return [
			'members' => MonAgenceModel::team($idAgence),
			'roles' => MonAgenceModel::teamRoles(),
			'usage' => SubscriptionLimiter::usage($idAgence),
		];
	}

	/**
	 * Validation partagée invitation/modification : nom, prénom, email (unicité hors le membre
	 * lui-même en cas de modification), téléphone normalisé en E.164, rôle autorisé pour une agence.
	 *
	 * @return array{0: array<string,string>, 1: array{nom:string,prenom:string,email:string,telephone:string,id_role:int}}
	 */
	private static function validateMemberInput(array $input, ?int $excludeIdUtilisateur = null): array
	{
		$data = [
			'nom' => trim((string) ($input['nom'] ?? '')),
			'prenom' => trim((string) ($input['prenom'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['telephone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['indicatif_pays'] ?? '+33')),
			'id_role' => (int) ($input['id_role'] ?? 0),
		];

		$errors = [];
		foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom', 'email' => 'L’email', 'telephone' => 'Le téléphone'] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label est requis.";
		}
		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Adresse email invalide.';
		} elseif ($data['email'] !== '' && MonAgenceModel::emailExists($data['email'], $excludeIdUtilisateur)) {
			$errors['email'] = 'Cet email est déjà utilisé.';
		}
		if (!isset($errors['telephone'])) {
			// Numéro normalisé en E.164 (indicatif + national) : indispensable pour que le SMS
			// d'invitation et, surtout, l'OTP de vérification au premier login (voir
			// AuthController::handleLogin) partent vers le bon pays plutôt qu'un numéro local
			// incomplet envoyé tel quel au provider SMS.
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) {
				$errors['telephone'] = 'Numéro de téléphone invalide.';
			} else {
				$data['telephone'] = $e164;
			}
		}
		if (!in_array($data['id_role'], self::INVITABLE_ROLE_IDS, true)) {
			$errors['id_role'] = 'Rôle invalide.';
		}

		return [$errors, $data];
	}

	/**
	 * @return array<string,string> Erreurs de validation (dont '_global' pour la limite de plan), vide si succès.
	 */
	public static function invite(int $idAgence, array $input): array
	{
		[$errors, $data] = self::validateMemberInput($input);
		if ($errors) {
			return $errors;
		}

		if (!SubscriptionLimiter::canAddUser($idAgence)) {
			return ['_global' => 'Limite d’utilisateurs de votre plan atteinte. Passez à un plan supérieur pour ajouter des membres.'];
		}

		$result = MonAgenceModel::inviteMember($idAgence, $data);

		$currentUser = Auth::user();
		Logger::audit(
			'CREATE',
			'utilisateur',
			$result['id_utilisateur'],
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			null,
			['nom' => $data['nom'], 'prenom' => $data['prenom'], 'email' => $data['email']]
		);

		if ($data['telephone']) {
			SmsSender::send(
				$result['id_utilisateur'],
				$data['telephone'],
				"Vous avez été ajouté à l'équipe LOKA. Identifiant : {$data['email']} / mot de passe temporaire : {$result['mot_de_passe_temporaire']}"
			);
		}

		return [];
	}

	public static function memberDetail(int $idAgence, int $idUtilisateur): ?array
	{
		return MonAgenceModel::findMember($idAgence, $idUtilisateur);
	}

	/**
	 * @return array<string,string> Erreurs de validation (dont '_global'), vide si succès.
	 */
	public static function updateMember(int $idAgence, int $idUtilisateur, array $input): array
	{
		$existing = MonAgenceModel::findMember($idAgence, $idUtilisateur);
		if (!$existing) {
			return ['_global' => 'Membre introuvable.'];
		}

		[$errors, $data] = self::validateMemberInput($input, $idUtilisateur);
		if ($errors) {
			return $errors;
		}

		MonAgenceModel::updateMember($idAgence, $idUtilisateur, $data);

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'utilisateur',
			$idUtilisateur,
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			['nom' => $existing['nom'], 'prenom' => $existing['prenom'], 'email' => $existing['email'], 'telephone' => $existing['telephone'], 'id_role' => $existing['id_role']],
			$data
		);

		return [];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function deleteMember(int $idAgence, int $idUtilisateur, int $currentUserId): ?string
	{
		if ($idUtilisateur === $currentUserId) {
			return 'Vous ne pouvez pas retirer votre propre compte de l’équipe.';
		}

		$existing = MonAgenceModel::findMember($idAgence, $idUtilisateur);
		if (!$existing) {
			return 'Membre introuvable.';
		}

		MonAgenceModel::deleteMember($idAgence, $idUtilisateur);

		Logger::audit(
			'DELETE',
			'utilisateur',
			$idUtilisateur,
			$currentUserId,
			$idAgence,
			['nom' => $existing['nom'], 'prenom' => $existing['prenom'], 'email' => $existing['email']],
			null
		);

		return null;
	}

	/* --------------------------------------------------------------
	 * Interventions (maintenance)
	 * -------------------------------------------------------------- */

	/** @return array{list: array, stats: array} */
	public static function interventions(int $idAgence, array $filters): array
	{
		return [
			'list' => MonAgenceModel::interventions($idAgence, $filters),
			'stats' => MonAgenceModel::interventionStats($idAgence),
		];
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createIntervention(int $idAgence, int $idUtilisateur, array $input): array
	{
		$data = [
			'titre' => trim((string) ($input['titre'] ?? '')),
			'description' => trim((string) ($input['description'] ?? '')),
			'priorite' => (string) ($input['priorite'] ?? 'MEDIUM'),
			'id_bien' => (int) ($input['id_bien'] ?? 0),
			'id_locataire' => !empty($input['id_locataire']) ? (int) $input['id_locataire'] : null,
			'cout_estime' => isset($input['cout_estime']) && $input['cout_estime'] !== '' ? (float) $input['cout_estime'] : 0.0,
		];

		$errors = [];
		if ($data['titre'] === '') {
			$errors['titre'] = 'Le titre est requis.';
		} elseif (mb_strlen($data['titre']) > 200) {
			$errors['titre'] = 'Le titre ne doit pas dépasser 200 caractères.';
		}
		if (!in_array($data['priorite'], ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], true)) {
			$errors['priorite'] = 'La priorité est invalide.';
		}
		if ($data['id_bien'] < 1) {
			$errors['id_bien'] = 'Le bien est requis.';
		} elseif (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $data['id_bien'])) {
			$errors['id_bien'] = 'Bien introuvable pour votre agence.';
		}
		if ($data['id_locataire'] !== null && !self::belongsToAgence($idAgence, 'locataire', 'id_locataire', $data['id_locataire'])) {
			$errors['id_locataire'] = 'Locataire introuvable pour votre agence.';
		}
		if ($errors) {
			return $errors;
		}

		$id = MonAgenceModel::createIntervention($idAgence, $idUtilisateur, $data);

		Logger::audit('CREATE', 'intervention', $id, $idUtilisateur, $idAgence, null, ['titre' => $data['titre'], 'priorite' => $data['priorite']]);

		return [];
	}

	public static function interventionDetail(int $idAgence, int $idIntervention): ?array
	{
		return MonAgenceModel::interventionFind($idAgence, $idIntervention);
	}

	private const INTERVENTION_STATUSES = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'WAITING', 'RESOLVED', 'CLOSED', 'CANCELLED'];

	public static function updateInterventionStatus(int $idAgence, int $idIntervention, string $statut, int $idUtilisateur): bool
	{
		if (!in_array($statut, self::INTERVENTION_STATUSES, true)) {
			return false;
		}
		$ok = MonAgenceModel::updateInterventionStatus($idAgence, $idIntervention, $statut);
		if ($ok) {
			Logger::audit('UPDATE', 'intervention', $idIntervention, $idUtilisateur, $idAgence, null, ['statut' => $statut]);
		}
		return $ok;
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function closeIntervention(int $idAgence, int $idIntervention, array $input, int $idUtilisateur): ?string
	{
		$compteRendu = trim((string) ($input['compte_rendu'] ?? ''));
		if ($compteRendu === '') {
			return 'Le compte rendu est requis pour clôturer l’intervention.';
		}
		$coutFinal = $input['cout_final'] !== '' && isset($input['cout_final']) ? (float) $input['cout_final'] : null;

		$ok = MonAgenceModel::closeIntervention($idAgence, $idIntervention, $coutFinal, $compteRendu);
		if (!$ok) {
			return 'Intervention introuvable pour votre agence.';
		}
		Logger::audit('UPDATE', 'intervention', $idIntervention, $idUtilisateur, $idAgence, null, ['statut' => 'CLOSED']);
		return null;
	}

	/** Vérifie qu'une ligne appartient bien à l'agence, avant de l'utiliser comme clé étrangère. */
	private static function belongsToAgence(int $idAgence, string $table, string $idColumn, int $id): bool
	{
		$stmt = Database::connection()->prepare("SELECT 1 FROM {$table} WHERE {$idColumn} = :id AND id_agence = :a LIMIT 1");
		$stmt->execute(['id' => $id, 'a' => $idAgence]);
		return (bool) $stmt->fetchColumn();
	}

	/* --------------------------------------------------------------
	 * Documents
	 * -------------------------------------------------------------- */

	private const DOCUMENT_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/documents';
	private const DOCUMENT_ALLOWED_MIMES = [
		'application/pdf' => 'pdf',
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
		'text/plain' => 'txt',
	];
	private const DOCUMENT_MAX_BYTES = 10 * 1024 * 1024;

	/** @return array{list: array, types: array, total: int} */
	public static function documents(int $idAgence, array $filters): array
	{
		$list = MonAgenceModel::documents($idAgence, $filters);
		return [
			'list' => $list,
			'types' => MonAgenceModel::documentTypes(),
			'entities' => MonAgenceModel::documentEntities($idAgence),
		];
	}

	/**
	 * @param array $post
	 * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $file
	 * @return array<string,string> Erreurs de validation, vide si succès.
	 */
	public static function createDocument(int $idAgence, array $post, ?array $file): array
	{
		$errors = [];
		$nom = trim((string) ($post['nom'] ?? ''));
		if ($nom === '') {
			$errors['nom'] = 'Le nom du document est requis.';
		} elseif (mb_strlen($nom) > 255) {
			$errors['nom'] = 'Le nom ne peut pas dépasser 255 caractères.';
		}

		$idTypeDocument = (int) ($post['id_type_document'] ?? 0);
		if ($idTypeDocument < 1) {
			$errors['id_type_document'] = 'Le type de document est requis.';
		}

		$entityType = (string) ($post['entity_type'] ?? '');
		$entityColumn = match ($entityType) {
			'bien' => 'id_bien', 'contrat' => 'id_contrat', 'proprietaire' => 'id_proprietaire',
			'locataire' => 'id_locataire', 'intervention' => 'id_intervention', default => null,
		};
		$entityTable = match ($entityType) {
			'bien' => 'bien', 'contrat' => 'contrat', 'proprietaire' => 'proprietaire',
			'locataire' => 'locataire', 'intervention' => 'intervention', default => null,
		};
		$entityId = $entityColumn !== null ? (int) ($post[$entityColumn] ?? 0) : 0;
		if ($entityColumn === null || $entityId < 1) {
			$errors['entity'] = 'Le document doit être rattaché à une entité de votre agence.';
		} elseif (!self::belongsToAgence($idAgence, $entityTable, $entityColumn, $entityId)) {
			$errors['entity'] = 'Cette entité n’appartient pas à votre agence.';
		}

		if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			$errors['fichier'] = 'Le fichier est requis.';
		} elseif ($file['error'] !== UPLOAD_ERR_OK) {
			$errors['fichier'] = 'Erreur lors du téléchargement du fichier.';
		} elseif ((int) $file['size'] > self::DOCUMENT_MAX_BYTES) {
			$errors['fichier'] = 'Le fichier ne doit pas dépasser 10 Mo.';
		} else {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$mimeType = (string) $finfo->file($file['tmp_name']);
			if (!isset(self::DOCUMENT_ALLOWED_MIMES[$mimeType])) {
				$errors['fichier'] = 'Type de fichier non autorisé (formats acceptés : PDF, JPEG, PNG, DOCX, XLSX, TXT).';
			}
		}

		if ($errors) {
			return $errors;
		}

		$destinationDir = self::DOCUMENT_UPLOAD_DIR;
		if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
			return ['_global' => 'Impossible de créer le dossier de stockage.'];
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = (string) $finfo->file($file['tmp_name']);
		$storedName = bin2hex(random_bytes(16)) . '.' . self::DOCUMENT_ALLOWED_MIMES[$mimeType];
		$destination = $destinationDir . DIRECTORY_SEPARATOR . $storedName;

		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			return ['_global' => 'Impossible d’enregistrer le fichier envoyé.'];
		}

		$data = [
			'id_type_document' => $idTypeDocument,
			'id_bien' => $entityColumn === 'id_bien' ? $entityId : null,
			'id_contrat' => $entityColumn === 'id_contrat' ? $entityId : null,
			'id_proprietaire' => $entityColumn === 'id_proprietaire' ? $entityId : null,
			'id_locataire' => $entityColumn === 'id_locataire' ? $entityId : null,
			'id_intervention' => $entityColumn === 'id_intervention' ? $entityId : null,
			'nom' => $nom,
			'nom_original' => basename((string) $file['name']),
			'mime_type' => $mimeType,
			'taille_octets' => (int) $file['size'],
			'chemin_stockage' => 'public/uploads/documents/' . $storedName,
		];

		$id = MonAgenceModel::createDocument($data);

		$currentUser = Auth::user();
		Logger::audit('CREATE', 'document', $id, $currentUser['id_utilisateur'] ?? null, $idAgence, null, ['nom' => $nom]);

		return [];
	}

	public static function documentFind(int $idAgence, int $idDocument): ?array
	{
		return MonAgenceModel::documentFind($idAgence, $idDocument);
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function deleteDocument(int $idAgence, int $idDocument): ?string
	{
		$path = MonAgenceModel::deleteDocument($idAgence, $idDocument);
		if ($path === null) {
			return 'Document introuvable pour votre agence.';
		}

		$fullPath = dirname(__DIR__, 3) . '/' . $path;
		if (file_exists($fullPath)) {
			unlink($fullPath);
		}

		$currentUser = Auth::user();
		Logger::audit('DELETE', 'document', $idDocument, $currentUser['id_utilisateur'] ?? null, $idAgence, null, null);

		return null;
	}

	/**
	 * @return array{biens: array, team: array}
	 */
	public static function biensForAssignment(int $idAgence): array
	{
		return [
			'biens' => MonAgenceModel::biensForAssignment($idAgence),
			'team' => MonAgenceModel::team($idAgence),
		];
	}

	/**
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function assignResponsable(int $idAgence, int $idBien, ?int $idResponsable): ?string
	{
		$ok = MonAgenceModel::assignResponsable($idAgence, $idBien, $idResponsable);
		if (!$ok) {
			return 'Bien introuvable pour votre agence.';
		}

		$currentUser = Auth::user();
		Logger::audit(
			'UPDATE',
			'bien',
			$idBien,
			$currentUser['id_utilisateur'] ?? null,
			$idAgence,
			null,
			['id_responsable' => $idResponsable]
		);

		return null;
	}
}
