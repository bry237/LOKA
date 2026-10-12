<?php
declare(strict_types=1);

require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/Logger.php';
require_once dirname(__DIR__, 3) . '/core/SmsSender.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';
require_once dirname(__DIR__, 3) . '/core/Phone.php';
require_once dirname(__DIR__, 3) . '/core/StripeClient.php';

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
	 * Biens (parc immobilier)
	 * -------------------------------------------------------------- */

	private const BIEN_STATUSES = ['CREATED', 'AVAILABLE', 'OCCUPIED', 'MAINTENANCE'];
	private const DPE_CLASSES = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
	private const BIEN_PHOTO_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/biens/photos';
	private const BIEN_PHOTO_ALLOWED_MIMES = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp',
	];
	private const BIEN_PHOTO_MAX_BYTES = 5 * 1024 * 1024;

	/** @return array{list: array, stats: array} */
	public static function biens(int $idAgence, array $filters): array
	{
		return [
			'list' => MonAgenceModel::biens($idAgence, $filters),
			'stats' => MonAgenceModel::bienStats($idAgence),
		];
	}

	/** @return array{types: array, addresses: array, buildings: array} */
	public static function bienFormOptions(int $idAgence): array
	{
		return [
			'types' => MonAgenceModel::bienTypes(),
			'addresses' => MonAgenceModel::bienAddresses($idAgence),
			'buildings' => MonAgenceModel::bienBuildings($idAgence),
		];
	}

	public static function bienDetail(int $idAgence, int $idBien): ?array
	{
		$bien = MonAgenceModel::bienFind($idAgence, $idBien);
		if (!$bien) {
			return null;
		}
		$bien['equipements'] = MonAgenceModel::bienEquipments();
		$bien['equipements_assignes'] = MonAgenceModel::bienEquipmentAssignments($idBien);
		$bien['photos'] = MonAgenceModel::bienPhotos($idBien);
		$bien['proprietaires'] = MonAgenceModel::bienOwners($idBien);
		$bien['proprietaires_disponibles'] = MonAgenceModel::bienAvailableOwners($idAgence, $idBien);
		return $bien;
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createBien(int $idAgence, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateBienInput($idAgence, $input);
		if ($errors) {
			return $errors;
		}

		if (!SubscriptionLimiter::canAddBien($idAgence)) {
			return ['_global' => 'Limite de biens de votre plan atteinte. Passez à un plan supérieur pour ajouter des biens.'];
		}

		$id = MonAgenceModel::createBien($idAgence, $data);
		Logger::audit('CREATE', 'bien', $id, $idUtilisateur, $idAgence, null, ['reference' => $data['reference'], 'titre' => $data['titre']]);
		return [];
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function updateBien(int $idAgence, int $idBien, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateBienInput($idAgence, $input, $idBien);
		if ($errors) {
			return $errors;
		}

		if (!MonAgenceModel::updateBien($idAgence, $idBien, $data)) {
			return ['_global' => 'Bien introuvable pour votre agence.'];
		}

		Logger::audit('UPDATE', 'bien', $idBien, $idUtilisateur, $idAgence, null, ['reference' => $data['reference'], 'titre' => $data['titre']]);
		return [];
	}

	public static function archiveBien(int $idAgence, int $idBien, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::archiveBien($idAgence, $idBien);
		if ($ok) {
			Logger::audit('DELETE', 'bien', $idBien, $idUtilisateur, $idAgence, null, null);
		}
		return $ok;
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function updateBienEquipments(int $idAgence, int $idBien, int $idUtilisateur, array $equipmentIds): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		$validIds = array_values(array_unique(array_map('intval', $equipmentIds)));
		MonAgenceModel::updateBienEquipmentAssignments($idBien, $validIds);
		Logger::audit('UPDATE', 'bien', $idBien, $idUtilisateur, $idAgence, null, ['equipements' => $validIds]);
		return null;
	}

	/**
	 * @param array{name:string,type:string,tmp_name:string,error:int,size:int}|null $file
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function addBienPhoto(int $idAgence, int $idBien, ?array $file, bool $main, int $idUtilisateur): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			return 'La photo est requise.';
		}
		if ($file['error'] !== UPLOAD_ERR_OK) {
			return 'Erreur lors du téléchargement de la photo.';
		}
		if ((int) $file['size'] > self::BIEN_PHOTO_MAX_BYTES) {
			return 'La photo ne doit pas dépasser 5 Mo.';
		}
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = (string) $finfo->file($file['tmp_name']);
		if (!isset(self::BIEN_PHOTO_ALLOWED_MIMES[$mimeType])) {
			return 'Format non autorisé (JPEG, PNG ou WEBP).';
		}

		$destinationDir = self::BIEN_PHOTO_UPLOAD_DIR;
		if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
			return 'Impossible de créer le dossier de stockage.';
		}

		$storedName = bin2hex(random_bytes(16)) . '.' . self::BIEN_PHOTO_ALLOWED_MIMES[$mimeType];
		$destination = $destinationDir . DIRECTORY_SEPARATOR . $storedName;
		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			return 'Impossible d’enregistrer la photo envoyée.';
		}

		MonAgenceModel::addBienPhoto($idBien, basename((string) $file['name']), 'public/uploads/biens/photos/' . $storedName, $main);
		Logger::audit('UPDATE', 'bien', $idBien, $idUtilisateur, $idAgence, null, ['photo' => 'ajoutée']);
		return null;
	}

	public static function setBienMainPhoto(int $idAgence, int $idBien, int $idPhoto): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		MonAgenceModel::setBienMainPhoto($idBien, $idPhoto);
		return null;
	}

	public static function deleteBienPhoto(int $idAgence, int $idBien, int $idPhoto): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		$path = MonAgenceModel::deleteBienPhoto($idBien, $idPhoto);
		if ($path === null) {
			return 'Photo introuvable.';
		}
		$fullPath = dirname(__DIR__, 3) . '/' . $path;
		if (file_exists($fullPath)) {
			unlink($fullPath);
		}
		return null;
	}

	/**
	 * Rattache un propriétaire existant de l'agence à ce bien (symétrique de
	 * attachProprietaireProperty(), déclenché depuis la fiche Bien plutôt que la fiche Propriétaire).
	 * @return string|null Message d'erreur, ou null si l'opération a réussi.
	 */
	public static function attachBienOwner(int $idAgence, int $idBien, int $idUtilisateur, array $input): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		$idProprietaire = filter_var($input['id_proprietaire'] ?? null, FILTER_VALIDATE_INT);
		$quotePart = filter_var($input['quote_part'] ?? null, FILTER_VALIDATE_FLOAT);
		$dateDebut = (string) ($input['date_debut'] ?? '');
		$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebut);

		if ($idProprietaire === false || $idProprietaire < 1 || !self::belongsToAgence($idAgence, 'proprietaire', 'id_proprietaire', $idProprietaire)) {
			return 'Le propriétaire sélectionné est invalide.';
		}
		if ($quotePart === false || $quotePart <= 0 || $quotePart > 100) {
			return 'La quote-part doit être comprise entre 0,01 et 100.';
		}
		if (!$date || $date->format('Y-m-d') !== $dateDebut) {
			return 'La date de début est obligatoire.';
		}
		if (MonAgenceModel::bienQuotePartTotal($idBien, $idProprietaire) + $quotePart > 100.0) {
			return 'La somme des quotes-parts actives de ce bien dépasserait 100 %.';
		}

		MonAgenceModel::attachProprietaireProperty($idProprietaire, $idBien, $quotePart, $dateDebut);
		Logger::audit('UPDATE', 'bien', $idBien, $idUtilisateur, $idAgence, null, ['id_proprietaire' => $idProprietaire, 'quote_part' => $quotePart]);
		return null;
	}

	public static function detachBienOwner(int $idAgence, int $idBien, int $idProprietaire, int $idUtilisateur): ?string
	{
		if (!self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Bien introuvable pour votre agence.';
		}
		MonAgenceModel::detachProprietaireProperty($idAgence, $idProprietaire, $idBien);
		Logger::audit('UPDATE', 'bien', $idBien, $idUtilisateur, $idAgence, null, ['id_proprietaire_detache' => $idProprietaire]);
		return null;
	}

	/**
	 * @return array{0: array<string,string>, 1: array} [erreurs, données normalisées — données vides si erreurs non vide]
	 */
	private static function validateBienInput(int $idAgence, array $input, ?int $idBienExclu = null): array
	{
		$errors = [];
		$reference = trim((string) ($input['reference'] ?? ''));
		$titre = trim((string) ($input['titre'] ?? ''));
		$idTypeBien = (int) ($input['id_type_bien'] ?? 0);
		$surface = (string) ($input['surface'] ?? '');
		$statut = (string) ($input['statut'] ?? 'CREATED');
		$dpe = (string) ($input['dpe_classe'] ?? '');
		$ges = (string) ($input['ges_classe'] ?? '');

		if ($reference === '') {
			$errors['reference'] = 'La référence est requise.';
		} elseif (mb_strlen($reference) > 100) {
			$errors['reference'] = 'La référence ne doit pas dépasser 100 caractères.';
		} elseif (MonAgenceModel::referenceExists($reference, $idBienExclu)) {
			$errors['reference'] = 'Cette référence est déjà utilisée par un autre bien.';
		}

		if ($titre === '') {
			$errors['titre'] = 'Le titre est requis.';
		} elseif (mb_strlen($titre) > 200) {
			$errors['titre'] = 'Le titre ne doit pas dépasser 200 caractères.';
		}

		if (!MonAgenceModel::bienTypeExists($idTypeBien)) {
			$errors['id_type_bien'] = 'Le type de bien est invalide.';
		}

		if (!is_numeric($surface) || (float) $surface <= 0) {
			$errors['surface'] = 'La surface doit être un nombre positif.';
		}

		if (!in_array($statut, self::BIEN_STATUSES, true)) {
			$errors['statut'] = 'Le statut est invalide.';
		}

		if ($dpe !== '' && !in_array($dpe, self::DPE_CLASSES, true)) {
			$errors['dpe_classe'] = 'Classe DPE invalide.';
		}
		if ($ges !== '' && !in_array($ges, self::DPE_CLASSES, true)) {
			$errors['ges_classe'] = 'Classe GES invalide.';
		}

		$idAdresse = null;
		if ((string) ($input['id_adresse'] ?? '') === '__new__') {
			$ligne1 = trim((string) ($input['nouvelle_ligne1'] ?? ''));
			$ville = trim((string) ($input['nouvelle_ville'] ?? ''));
			if ($ligne1 === '' || $ville === '') {
				$errors['id_adresse'] = 'L’adresse et la ville sont requises pour une nouvelle adresse.';
			} else {
				$idAdresse = MonAgenceModel::createAddress([
					'ligne1' => $ligne1,
					'ligne2' => self::nullableTrim($input['nouvelle_ligne2'] ?? null),
					'code_postal' => self::nullableTrim($input['nouveau_code_postal'] ?? null),
					'ville' => $ville,
					'region' => null,
					'pays' => 'France',
				]);
			}
		} else {
			$idAdresse = (int) ($input['id_adresse'] ?? 0);
			if ($idAdresse < 1) {
				$errors['id_adresse'] = 'L’adresse est requise.';
			} elseif (!self::addressUsableByAgency($idAgence, $idAdresse)) {
				$errors['id_adresse'] = 'Cette adresse appartient à une autre agence.';
			}
		}

		$idImmeuble = null;
		if ((string) ($input['id_immeuble'] ?? '') !== '') {
			$idImmeuble = (int) $input['id_immeuble'];
			if (!self::belongsToAgence($idAgence, 'immeuble', 'id_immeuble', $idImmeuble)) {
				$errors['id_immeuble'] = 'Cet immeuble appartient à une autre agence.';
			}
		}

		if ($errors) {
			return [$errors, []];
		}

		return [[], [
			'id_type_bien' => $idTypeBien,
			'id_adresse' => $idAdresse,
			'id_immeuble' => $idImmeuble,
			'reference' => $reference,
			'titre' => $titre,
			'surface' => $surface,
			'nombre_pieces' => (int) ($input['nombre_pieces'] ?? 0),
			'etage' => (string) ($input['etage'] ?? '') !== '' ? (int) $input['etage'] : null,
			'loyer' => (string) ($input['loyer'] ?? '') !== '' ? (string) $input['loyer'] : '0',
			'charges' => (string) ($input['charges'] ?? '') !== '' ? (string) $input['charges'] : '0',
			'caution' => (string) ($input['caution'] ?? '') !== '' ? (string) $input['caution'] : '0',
			'statut' => $statut,
			'description' => self::nullableTrim($input['description'] ?? null),
			'meuble' => ((string) ($input['meuble'] ?? '0')) === '1' ? '1' : '0',
			'date_disponibilite' => self::nullableTrim($input['date_disponibilite'] ?? null),
			'dpe_classe' => $dpe !== '' ? $dpe : null,
			'ges_classe' => $ges !== '' ? $ges : null,
		]];
	}

	private static function addressUsableByAgency(int $idAgence, int $idAdresse): bool
	{
		$stmt = Database::connection()->prepare(
			'SELECT 1 FROM adresse a
			 WHERE a.id_adresse = :id_adresse
			 AND NOT EXISTS (SELECT 1 FROM immeuble i WHERE i.id_adresse = a.id_adresse AND i.id_agence <> :a1)
			 AND NOT EXISTS (SELECT 1 FROM bien b WHERE b.id_adresse = a.id_adresse AND b.id_agence <> :a2)
			 LIMIT 1'
		);
		$stmt->execute(['id_adresse' => $idAdresse, 'a1' => $idAgence, 'a2' => $idAgence]);
		return (bool) $stmt->fetchColumn();
	}

	private static function nullableTrim(mixed $value): ?string
	{
		$value = trim((string) ($value ?? ''));
		return $value === '' ? null : $value;
	}

	/* --------------------------------------------------------------
	 * Locataires
	 * -------------------------------------------------------------- */

	private const LOCATAIRE_STATUSES = ['ACTIVE', 'INACTIVE', 'ARCHIVED'];
	private const LOCATAIRE_TYPES_IDENTITE = ['CNI', 'PASSEPORT', 'TITRE_SEJOUR', 'AUTRE'];
	private const LOCATAIRE_SITUATIONS = ['CELIBATAIRE', 'MARIE', 'PACSE', 'DIVORCE', 'VEUF'];

	/** @return array{list: array, stats: array} */
	public static function locataires(int $idAgence, array $filters): array
	{
		return [
			'list' => MonAgenceModel::locataires($idAgence, $filters),
			'stats' => MonAgenceModel::locataireStats($idAgence),
		];
	}

	/** @return array{0: array, 1: array}|null null si le locataire n'appartient pas à l'agence. */
	public static function locataireDetail(int $idAgence, int $idLocataire): ?array
	{
		$locataire = MonAgenceModel::locataireFind($idAgence, $idLocataire);
		if (!$locataire) {
			return null;
		}
		$locataire['contrats'] = MonAgenceModel::locataireContracts($idAgence, $idLocataire);
		return $locataire;
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createLocataire(int $idAgence, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateLocataireInput($input);
		if ($errors) {
			return $errors;
		}

		$id = MonAgenceModel::createLocataire($idAgence, $data);
		Logger::audit('CREATE', 'locataire', $id, $idUtilisateur, $idAgence, null, ['nom' => $data['nom'], 'prenom' => $data['prenom']]);
		return [];
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function updateLocataire(int $idAgence, int $idLocataire, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateLocataireInput($input);
		if ($errors) {
			return $errors;
		}

		if (!MonAgenceModel::updateLocataire($idAgence, $idLocataire, $data)) {
			return ['_global' => 'Locataire introuvable pour votre agence.'];
		}

		Logger::audit('UPDATE', 'locataire', $idLocataire, $idUtilisateur, $idAgence, null, ['nom' => $data['nom'], 'prenom' => $data['prenom']]);
		return [];
	}

	public static function archiveLocataire(int $idAgence, int $idLocataire, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::archiveLocataire($idAgence, $idLocataire);
		if ($ok) {
			Logger::audit('DELETE', 'locataire', $idLocataire, $idUtilisateur, $idAgence, null, null);
		}
		return $ok;
	}

	/** @return array{0: array<string,string>, 1: array} [erreurs, données normalisées — données vides si erreurs non vide] */
	private static function validateLocataireInput(array $input): array
	{
		$errors = [];
		$nom = trim((string) ($input['nom'] ?? ''));
		$prenom = trim((string) ($input['prenom'] ?? ''));
		$email = trim((string) ($input['email'] ?? ''));
		$statut = (string) ($input['statut'] ?? 'ACTIVE');
		$typeIdentite = (string) ($input['type_identite'] ?? '');
		$situation = (string) ($input['situation_familiale'] ?? '');

		if ($nom === '') {
			$errors['nom'] = 'Le nom est requis.';
		} elseif (mb_strlen($nom) > 150) {
			$errors['nom'] = 'Le nom ne doit pas dépasser 150 caractères.';
		}
		if ($prenom === '') {
			$errors['prenom'] = 'Le prénom est requis.';
		} elseif (mb_strlen($prenom) > 150) {
			$errors['prenom'] = 'Le prénom ne doit pas dépasser 150 caractères.';
		}
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'L’adresse email n’est pas valide.';
		}
		if (!in_array($statut, self::LOCATAIRE_STATUSES, true)) {
			$errors['statut'] = 'Le statut est invalide.';
		}
		if ($typeIdentite !== '' && !in_array($typeIdentite, self::LOCATAIRE_TYPES_IDENTITE, true)) {
			$errors['type_identite'] = 'Le type d’identité est invalide.';
		}
		if ($situation !== '' && !in_array($situation, self::LOCATAIRE_SITUATIONS, true)) {
			$errors['situation_familiale'] = 'La situation familiale est invalide.';
		}

		if ($errors) {
			return [$errors, []];
		}

		return [[], [
			'nom' => $nom,
			'prenom' => $prenom,
			'email' => $email !== '' ? $email : null,
			'telephone' => self::nullableTrim($input['telephone'] ?? null),
			'adresse' => self::nullableTrim($input['adresse'] ?? null),
			'code_postal' => self::nullableTrim($input['code_postal'] ?? null),
			'ville' => self::nullableTrim($input['ville'] ?? null),
			'pays' => self::nullableTrim($input['pays'] ?? null) ?? 'France',
			'date_naissance' => self::nullableTrim($input['date_naissance'] ?? null),
			'profession' => self::nullableTrim($input['profession'] ?? null),
			'revenu_mensuel' => !empty($input['revenu_mensuel']) ? (float) $input['revenu_mensuel'] : null,
			'revenu_mensuel_fourchette' => self::nullableTrim($input['revenu_mensuel_fourchette'] ?? null),
			'statut' => $statut,
			'numero_identite' => self::nullableTrim($input['numero_identite'] ?? null),
			'type_identite' => $typeIdentite !== '' ? $typeIdentite : null,
			'situation_familiale' => $situation !== '' ? $situation : null,
			'nombre_occupants' => !empty($input['nombre_occupants']) ? (int) $input['nombre_occupants'] : 1,
			'garant_nom' => self::nullableTrim($input['garant_nom'] ?? null),
			'garant_telephone' => self::nullableTrim($input['garant_telephone'] ?? null),
			'garant_email' => self::nullableTrim($input['garant_email'] ?? null),
			'notes' => self::nullableTrim($input['notes'] ?? null),
		]];
	}

	/* --------------------------------------------------------------
	 * Propriétaires
	 * -------------------------------------------------------------- */

	/** @return array{list: array, stats: array} */
	public static function proprietaires(int $idAgence, array $filters): array
	{
		return [
			'list' => MonAgenceModel::proprietaires($idAgence, $filters),
			'stats' => MonAgenceModel::proprietaireStats($idAgence),
		];
	}

	public static function proprietaireDetail(int $idAgence, int $idProprietaire): ?array
	{
		$proprietaire = MonAgenceModel::proprietaireFind($idAgence, $idProprietaire);
		if (!$proprietaire) {
			return null;
		}
		$proprietaire['biens'] = MonAgenceModel::proprietaireProperties($idAgence, $idProprietaire);
		$proprietaire['biens_disponibles'] = MonAgenceModel::proprietaireAvailableProperties($idAgence, $idProprietaire);
		return $proprietaire;
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createProprietaire(int $idAgence, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateProprietaireInput($input);
		if ($errors) {
			return $errors;
		}

		$id = MonAgenceModel::createProprietaire($idAgence, $data);
		Logger::audit('CREATE', 'proprietaire', $id, $idUtilisateur, $idAgence, null, ['nom' => $data['nom'], 'prenom' => $data['prenom']]);
		return [];
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function updateProprietaire(int $idAgence, int $idProprietaire, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateProprietaireInput($input);
		if ($errors) {
			return $errors;
		}

		if (!MonAgenceModel::updateProprietaire($idAgence, $idProprietaire, $data)) {
			return ['_global' => 'Propriétaire introuvable pour votre agence.'];
		}

		Logger::audit('UPDATE', 'proprietaire', $idProprietaire, $idUtilisateur, $idAgence, null, ['nom' => $data['nom'], 'prenom' => $data['prenom']]);
		return [];
	}

	public static function archiveProprietaire(int $idAgence, int $idProprietaire, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::archiveProprietaire($idAgence, $idProprietaire);
		if ($ok) {
			Logger::audit('DELETE', 'proprietaire', $idProprietaire, $idUtilisateur, $idAgence, null, null);
		}
		return $ok;
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function attachProprietaireProperty(int $idAgence, int $idProprietaire, int $idUtilisateur, array $input): ?string
	{
		if (!MonAgenceModel::proprietaireFind($idAgence, $idProprietaire)) {
			return 'Propriétaire introuvable pour votre agence.';
		}
		$idBien = filter_var($input['id_bien'] ?? null, FILTER_VALIDATE_INT);
		$quotePart = filter_var($input['quote_part'] ?? null, FILTER_VALIDATE_FLOAT);
		$dateDebut = (string) ($input['date_debut'] ?? '');
		$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebut);

		if ($idBien === false || $idBien < 1 || !self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			return 'Le bien sélectionné est invalide.';
		}
		if ($quotePart === false || $quotePart <= 0 || $quotePart > 100) {
			return 'La quote-part doit être comprise entre 0,01 et 100.';
		}
		if (!$date || $date->format('Y-m-d') !== $dateDebut) {
			return 'La date de début est obligatoire.';
		}
		if (MonAgenceModel::bienQuotePartTotal($idBien, $idProprietaire) + $quotePart > 100.0) {
			return 'La somme des quotes-parts actives de ce bien dépasserait 100 %.';
		}

		MonAgenceModel::attachProprietaireProperty($idProprietaire, $idBien, $quotePart, $dateDebut);
		Logger::audit('UPDATE', 'proprietaire', $idProprietaire, $idUtilisateur, $idAgence, null, ['id_bien' => $idBien, 'quote_part' => $quotePart]);
		return null;
	}

	public static function detachProprietaireProperty(int $idAgence, int $idProprietaire, int $idBien, int $idUtilisateur): ?string
	{
		if (!MonAgenceModel::proprietaireFind($idAgence, $idProprietaire)) {
			return 'Propriétaire introuvable pour votre agence.';
		}
		MonAgenceModel::detachProprietaireProperty($idAgence, $idProprietaire, $idBien);
		Logger::audit('UPDATE', 'proprietaire', $idProprietaire, $idUtilisateur, $idAgence, null, ['id_bien_detache' => $idBien]);
		return null;
	}

	/** @return array{0: array<string,string>, 1: array} [erreurs, données normalisées — données vides si erreurs non vide] */
	private static function validateProprietaireInput(array $input): array
	{
		$errors = [];
		$nom = trim((string) ($input['nom'] ?? ''));
		$prenom = trim((string) ($input['prenom'] ?? ''));
		$email = trim((string) ($input['email'] ?? ''));

		if ($nom === '') {
			$errors['nom'] = 'Le nom est requis.';
		} elseif (mb_strlen($nom) > 150) {
			$errors['nom'] = 'Le nom ne doit pas dépasser 150 caractères.';
		}
		if ($prenom === '') {
			$errors['prenom'] = 'Le prénom est requis.';
		} elseif (mb_strlen($prenom) > 150) {
			$errors['prenom'] = 'Le prénom ne doit pas dépasser 150 caractères.';
		}
		if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'L’adresse email n’est pas valide.';
		}
		if (($input['date_naissance'] ?? '') !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', (string) $input['date_naissance'])) {
			$errors['date_naissance'] = 'La date de naissance est invalide.';
		}

		if ($errors) {
			return [$errors, []];
		}

		return [[], [
			'nom' => $nom,
			'prenom' => $prenom,
			'email' => $email !== '' ? $email : null,
			'telephone' => self::nullableTrim($input['telephone'] ?? null),
			'adresse' => self::nullableTrim($input['adresse'] ?? null),
			'code_postal' => self::nullableTrim($input['code_postal'] ?? null),
			'ville' => self::nullableTrim($input['ville'] ?? null),
			'pays' => self::nullableTrim($input['pays'] ?? null) ?? 'France',
			'date_naissance' => self::nullableTrim($input['date_naissance'] ?? null),
		]];
	}

	/* --------------------------------------------------------------
	 * Contrats
	 * -------------------------------------------------------------- */

	private const CONTRAT_STATUSES = ['DRAFT', 'ACTIVE', 'EXPIRED', 'RENEWED', 'TERMINATED', 'ARCHIVED'];
	private const CONTRAT_TYPES = ['HABITATION_VIDE', 'HABITATION_MEUBLE', 'COMMERCIAL', 'PROFESSIONNEL', 'SAISONNIER'];
	private const CONTRAT_FREQUENCES = ['MONTHLY', 'QUARTERLY', 'YEARLY'];

	/** @return array{list: array, stats: array} */
	public static function contrats(int $idAgence, array $filters): array
	{
		return [
			'list' => MonAgenceModel::contrats($idAgence, $filters),
			'stats' => MonAgenceModel::contratStats($idAgence),
		];
	}

	public static function contratDetail(int $idAgence, int $idContrat): ?array
	{
		$contrat = MonAgenceModel::contratFind($idAgence, $idContrat);
		if (!$contrat) {
			return null;
		}
		$contrat['locataires'] = MonAgenceModel::contratTenants($idContrat);
		$contrat['locataires_disponibles'] = MonAgenceModel::contratAvailableTenants($idAgence, $idContrat);
		return $contrat;
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createContrat(int $idAgence, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateContratInput($idAgence, $input);
		if ($errors) {
			return $errors;
		}

		$id = MonAgenceModel::createContrat($idAgence, $data);
		Logger::audit('CREATE', 'contrat', $id, $idUtilisateur, $idAgence, null, ['numero' => $data['numero']]);
		self::regenerateContratDocument($idAgence, $id);
		return [];
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function updateContrat(int $idAgence, int $idContrat, int $idUtilisateur, array $input): array
	{
		[$errors, $data] = self::validateContratInput($idAgence, $input, $idContrat);
		if ($errors) {
			return $errors;
		}

		if (!MonAgenceModel::updateContrat($idAgence, $idContrat, $data)) {
			return ['_global' => 'Contrat introuvable pour votre agence.'];
		}

		Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['numero' => $data['numero']]);
		self::regenerateContratDocument($idAgence, $idContrat);
		return [];
	}

	public static function updateContratStatus(int $idAgence, int $idContrat, string $statut, int $idUtilisateur): bool
	{
		if (!in_array($statut, self::CONTRAT_STATUSES, true)) {
			return false;
		}
		$ok = MonAgenceModel::updateContratStatus($idAgence, $idContrat, $statut);
		if ($ok) {
			Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['statut' => $statut]);
		}
		return $ok;
	}

	public static function terminateContrat(int $idAgence, int $idContrat, string $motif, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::terminateContrat($idAgence, $idContrat, trim($motif));
		if ($ok) {
			Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['statut' => 'TERMINATED']);
			self::regenerateContratDocument($idAgence, $idContrat);
		}
		return $ok;
	}

	public static function archiveContrat(int $idAgence, int $idContrat, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::archiveContrat($idAgence, $idContrat);
		if ($ok) {
			Logger::audit('DELETE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, null);
		}
		return $ok;
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function attachContratTenant(int $idAgence, int $idContrat, int $idUtilisateur, array $input): ?string
	{
		if (!self::belongsToAgence($idAgence, 'contrat', 'id_contrat', $idContrat)) {
			return 'Contrat introuvable pour votre agence.';
		}
		$idLocataire = filter_var($input['id_locataire'] ?? null, FILTER_VALIDATE_INT);
		if ($idLocataire === false || $idLocataire < 1 || !self::belongsToAgence($idAgence, 'locataire', 'id_locataire', $idLocataire)) {
			return 'Le locataire sélectionné est invalide.';
		}
		$dateEntree = trim((string) ($input['date_entree'] ?? ''));
		MonAgenceModel::attachContratTenant($idContrat, $idLocataire, !empty($input['titulaire_principal']), $dateEntree !== '' ? $dateEntree : null);
		Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['id_locataire' => $idLocataire]);
		self::regenerateContratDocument($idAgence, $idContrat);
		return null;
	}

	public static function detachContratTenant(int $idAgence, int $idContrat, int $idLocataire, int $idUtilisateur): ?string
	{
		if (!self::belongsToAgence($idAgence, 'contrat', 'id_contrat', $idContrat)) {
			return 'Contrat introuvable pour votre agence.';
		}
		MonAgenceModel::detachContratTenant($idContrat, $idLocataire);
		Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['id_locataire_detache' => $idLocataire]);
		self::regenerateContratDocument($idAgence, $idContrat);
		return null;
	}

	/** @return array{0: array<string,string>, 1: array} [erreurs, données normalisées — données vides si erreurs non vide] */
	private static function validateContratInput(int $idAgence, array $input, ?int $idContratExclu = null): array
	{
		$errors = [];
		$numero = trim((string) ($input['numero'] ?? ''));
		$idBien = (int) ($input['id_bien'] ?? 0);
		$dateDebut = trim((string) ($input['date_debut'] ?? ''));
		$dateFin = trim((string) ($input['date_fin'] ?? ''));
		$loyer = (string) ($input['loyer'] ?? '');
		$typeContrat = (string) ($input['type_contrat'] ?? 'HABITATION_VIDE');
		$frequence = (string) ($input['frequence_paiement'] ?? 'MONTHLY');
		$statut = (string) ($input['statut'] ?? 'DRAFT');

		if ($numero === '') {
			$errors['numero'] = 'Le numéro de contrat est requis.';
		} elseif (mb_strlen($numero) > 100) {
			$errors['numero'] = 'Le numéro ne doit pas dépasser 100 caractères.';
		} elseif (MonAgenceModel::numeroContratExists($numero, $idContratExclu)) {
			$errors['numero'] = 'Ce numéro de contrat est déjà utilisé.';
		}

		if ($idBien < 1 || !self::belongsToAgence($idAgence, 'bien', 'id_bien', $idBien)) {
			$errors['id_bien'] = 'Le bien est requis et doit appartenir à votre agence.';
		}

		if ($dateDebut === '') {
			$errors['date_debut'] = 'La date de début est requise.';
		}
		if ($dateFin !== '' && $dateDebut !== '' && $dateFin < $dateDebut) {
			$errors['date_fin'] = 'La date de fin doit être postérieure à la date de début.';
		}

		if (!is_numeric($loyer) || (float) $loyer < 0) {
			$errors['loyer'] = 'Le loyer doit être un nombre positif.';
		}
		if (!in_array($typeContrat, self::CONTRAT_TYPES, true)) {
			$errors['type_contrat'] = 'Le type de contrat est invalide.';
		}
		if (!in_array($frequence, self::CONTRAT_FREQUENCES, true)) {
			$errors['frequence_paiement'] = 'La fréquence de paiement est invalide.';
		}
		if (!in_array($statut, self::CONTRAT_STATUSES, true)) {
			$errors['statut'] = 'Le statut est invalide.';
		}

		if ($errors) {
			return [$errors, []];
		}

		return [[], [
			'id_bien' => $idBien,
			'numero' => $numero,
			'type_contrat' => $typeContrat,
			'date_debut' => $dateDebut,
			'date_fin' => $dateFin !== '' ? $dateFin : null,
			'loyer' => $loyer,
			'charges' => (string) ($input['charges'] ?? '') !== '' ? (string) $input['charges'] : '0',
			'depot_garantie' => (string) ($input['depot_garantie'] ?? '') !== '' ? (string) $input['depot_garantie'] : '0',
			'frequence_paiement' => $frequence,
			'jour_echeance' => (string) ($input['jour_echeance'] ?? '') !== '' ? (int) $input['jour_echeance'] : 5,
			'statut' => $statut,
		]];
	}

	private const CONTRAT_DOCUMENT_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/contrats';

	/**
	 * Régénère le document du contrat (bail) à partir de l'état courant du contrat, du bien et des
	 * locataires rattachés — appelé après chaque opération qui change l'un de ces éléments, pour que
	 * le document reste toujours cohérent avec les données réelles. Échoue silencieusement si le
	 * contrat est introuvable ou le dossier d'upload inaccessible : ce n'est jamais l'opération
	 * principale (création/modification/rattachement) qui doit échouer à cause du document.
	 */
	private static function regenerateContratDocument(int $idAgence, int $idContrat): void
	{
		$data = MonAgenceModel::contratDocumentData($idAgence, $idContrat);
		if ($data === null) {
			return;
		}

		$destinationDir = self::CONTRAT_DOCUMENT_UPLOAD_DIR;
		if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
			return;
		}

		$existingPath = (string) ($data['contrat']['chemin_fichier_pdf'] ?? '');
		if ($existingPath !== '') {
			$storedName = basename($existingPath);
		} else {
			$storedName = bin2hex(random_bytes(16)) . '.html';
		}

		$html = self::renderContratDocumentHtml($data);
		file_put_contents($destinationDir . DIRECTORY_SEPARATOR . $storedName, $html);

		if ($existingPath === '') {
			MonAgenceModel::setContratDocumentPath($idContrat, 'public/uploads/contrats/' . $storedName);
		}
	}

	/** @param array{agence: ?array, contrat: array, bien: array, locataires: array, proprietaires: array} $data */
	private static function renderContratDocumentHtml(array $data): string
	{
		$esc = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
		$money = static fn (mixed $v): string => number_format((float) $v, 2, ',', ' ') . ' €';
		$date = static fn (?string $v): string => $v ? (new DateTimeImmutable($v))->format('d/m/Y') : '—';

		$agence = $data['agence'];
		$contrat = $data['contrat'];
		$bien = $data['bien'];

		$typeLabels = ['HABITATION_VIDE' => 'Habitation vide', 'HABITATION_MEUBLE' => 'Habitation meublée', 'COMMERCIAL' => 'Commercial', 'PROFESSIONNEL' => 'Professionnel', 'SAISONNIER' => 'Saisonnier'];
		$frequenceLabels = ['MONTHLY' => 'mensuelle', 'QUARTERLY' => 'trimestrielle', 'YEARLY' => 'annuelle'];

		$bailleurHtml = '';
		if ($data['proprietaires']) {
			foreach ($data['proprietaires'] as $p) {
				$bailleurHtml .= '<p><strong>' . $esc($p['prenom'] . ' ' . $p['nom']) . '</strong>'
					. ($p['quote_part'] !== null ? ' (quote-part ' . number_format((float) $p['quote_part'], 0) . ' %)' : '')
					. '<br>' . $esc(trim(($p['adresse'] ?? '') . ', ' . ($p['code_postal'] ?? '') . ' ' . ($p['ville'] ?? ''), ' ,'))
					. ($p['telephone'] ? '<br>Tél. ' . $esc($p['telephone']) : '')
					. ($p['email'] ? '<br>' . $esc($p['email']) : '')
					. '</p>';
			}
			$bailleurHtml .= '<p>Représenté(s) par l’agence <strong>' . $esc($agence['nom'] ?? '') . '</strong>, mandataire de gestion.</p>';
		} else {
			$bailleurHtml = '<p><strong>' . $esc($agence['nom'] ?? '') . '</strong>, agissant en qualité de mandataire'
				. ($agence['adresse'] ? '<br>' . $esc($agence['adresse'] . ', ' . $agence['code_postal'] . ' ' . $agence['ville']) : '')
				. '</p>';
		}

		$locatairesHtml = '';
		if ($data['locataires']) {
			foreach ($data['locataires'] as $l) {
				$locatairesHtml .= '<p><strong>' . $esc($l['prenom'] . ' ' . $l['nom']) . '</strong>'
					. ($l['titulaire_principal'] ? ' (titulaire principal)' : '')
					. ($l['adresse'] ? '<br>' . $esc(trim($l['adresse'] . ', ' . $l['code_postal'] . ' ' . $l['ville'], ' ,')) : '')
					. ($l['telephone'] ? '<br>Tél. ' . $esc($l['telephone']) : '')
					. ($l['email'] ? '<br>' . $esc($l['email']) : '')
					. '</p>';
			}
		} else {
			$locatairesHtml = '<p class="muted">Aucun locataire rattaché pour le moment — cette section sera complétée dès le rattachement d’un locataire au contrat.</p>';
		}

		$pageTitle = 'Contrat de location ' . $esc($contrat['numero']);

		return <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{$pageTitle}</title>
<style>
  body{font-family:Georgia,'Times New Roman',serif;color:#1a2b2b;max-width:760px;margin:40px auto;padding:0 24px;line-height:1.55;}
  h1{font-size:20px;text-align:center;margin:0 0 4px;letter-spacing:.03em;}
  .subtitle{text-align:center;color:#5a6b6b;font-size:13px;margin:0 0 28px;}
  h2{font-size:14px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #d8e2e2;padding-bottom:6px;margin:28px 0 12px;}
  .parties{display:flex;gap:24px;}
  .parties > div{flex:1;}
  .party-label{font-size:11px;text-transform:uppercase;color:#5a6b6b;margin-bottom:6px;}
  p{margin:0 0 10px;font-size:13.5px;}
  .muted{color:#5a6b6b;font-style:italic;}
  table{width:100%;border-collapse:collapse;font-size:13.5px;}
  td{padding:6px 0;border-bottom:1px solid #eef2f2;}
  td:first-child{color:#5a6b6b;width:45%;}
  td:last-child{text-align:right;font-weight:600;}
  .signatures{display:flex;gap:40px;margin-top:48px;}
  .signatures > div{flex:1;border-top:1px solid #1a2b2b;padding-top:8px;font-size:12px;color:#5a6b6b;}
  .print-bar{text-align:center;margin-bottom:24px;}
  .print-bar button{font-family:inherit;font-size:13px;padding:8px 16px;border-radius:8px;border:1px solid #0f8b8d;background:#0f8b8d;color:#fff;cursor:pointer;}
  @media print{.print-bar{display:none;}body{margin:0;max-width:none;}}
</style>
</head>
<body>
  <div class="print-bar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>

  <h1>Contrat de location</h1>
  <p class="subtitle">N° {$esc($contrat['numero'])} · {$esc($typeLabels[$contrat['type_contrat']] ?? $contrat['type_contrat'])} · généré par {$esc($agence['nom'] ?? 'LOKA')}</p>

  <h2>Entre les soussignés</h2>
  <div class="parties">
    <div>
      <div class="party-label">Le bailleur</div>
      {$bailleurHtml}
    </div>
    <div>
      <div class="party-label">Le(s) locataire(s)</div>
      {$locatairesHtml}
    </div>
  </div>

  <h2>Désignation du bien loué</h2>
  <p><strong>{$esc($bien['titre'])}</strong> (réf. {$esc($bien['reference'])})<br>
  {$esc($bien['ligne1'] . ($bien['ligne2'] ? ' ' . $bien['ligne2'] : '') . ', ' . $bien['code_postal'] . ' ' . $bien['ville'])}<br>
  {$esc($bien['type_bien'])} · {$esc(number_format((float) $bien['surface'], 0))} m² · {$esc((string) $bien['nombre_pieces'])} pièce(s)
  {$esc($bien['meuble'] === '1' ? ' · meublé' : '')}</p>

  <h2>Conditions financières</h2>
  <table>
    <tr><td>Loyer mensuel hors charges</td><td>{$money($contrat['loyer'])}</td></tr>
    <tr><td>Charges mensuelles</td><td>{$money($contrat['charges'])}</td></tr>
    <tr><td>Total mensuel</td><td>{$money((float) $contrat['loyer'] + (float) $contrat['charges'])}</td></tr>
    <tr><td>Dépôt de garantie</td><td>{$money($contrat['depot_garantie'])}</td></tr>
    <tr><td>Périodicité de paiement</td><td>{$esc($frequenceLabels[$contrat['frequence_paiement']] ?? $contrat['frequence_paiement'])}</td></tr>
    <tr><td>Jour d'échéance</td><td>le {$esc((string) $contrat['jour_echeance'])} de chaque période</td></tr>
  </table>

  <h2>Durée</h2>
  <table>
    <tr><td>Date de prise d'effet</td><td>{$date($contrat['date_debut'])}</td></tr>
    <tr><td>Date de fin</td><td>{$esc($contrat['date_fin'] ? $date($contrat['date_fin']) : 'Durée indéterminée')}</td></tr>
    <tr><td>Préavis</td><td>{$esc((string) $contrat['preavis_mois'])} mois</td></tr>
  </table>

  <div class="signatures">
    <div>Le bailleur (ou son mandataire)<br>Date et signature</div>
    <div>Le(s) locataire(s)<br>Date et signature</div>
  </div>
</body>
</html>
HTML;
	}

	/* --------------------------------------------------------------
	 * Paiements, échéances et quittances
	 * -------------------------------------------------------------- */

	private const PAIEMENT_MODES = ['CASH', 'BANK_TRANSFER', 'CARD', 'CHEQUE', 'DIRECT_DEBIT', 'OTHER'];
	private const PAIEMENT_STATUSES = ['PENDING', 'PAID', 'LATE', 'CANCELLED', 'REFUNDED'];
	private const QUITTANCE_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/quittances';

	/** @return array{list: array, stats: array} */
	public static function echeances(int $idAgence, array $filters): array
	{
		$list = MonAgenceModel::echeances($idAgence, $filters);
		$pendingDemandes = MonAgenceModel::pendingDemandesByEcheances(array_column($list, 'id_echeance'));
		foreach ($list as &$echeance) {
			$echeance['demande_pending'] = $pendingDemandes[(int) $echeance['id_echeance']] ?? null;
		}
		unset($echeance);
		return [
			'list' => $list,
			'stats' => MonAgenceModel::echeanceStats($idAgence),
		];
	}

	public static function echeanceDetail(int $idAgence, int $idEcheance): ?array
	{
		$echeance = MonAgenceModel::echeanceFind($idAgence, $idEcheance);
		if (!$echeance) {
			return null;
		}
		$echeance['paiements'] = MonAgenceModel::echeancePaiements($idEcheance);
		$echeance['total_paye'] = MonAgenceModel::echeanceTotalPaid($idEcheance);
		$echeance['solde'] = max(0.0, (float) $echeance['montant_total'] - $echeance['total_paye']);
		$echeance['locataires'] = MonAgenceModel::echeanceTenants($idEcheance);
		$demande = MonAgenceModel::demandePaiementLatestForEcheance($idEcheance);
		$echeance['demande_pending'] = ($demande && $demande['statut'] === 'PENDING' && (string) $demande['checkout_url'] !== '')
			? $demande
			: null;
		return $echeance;
	}

	/** @return array<string,string> Erreurs de validation, vide si succès. */
	public static function createEcheance(int $idAgence, int $idUtilisateur, array $input): array
	{
		$errors = [];
		$idContrat = (int) ($input['id_contrat'] ?? 0);
		$periodeDebut = trim((string) ($input['periode_debut'] ?? ''));
		$periodeFin = trim((string) ($input['periode_fin'] ?? ''));
		$dateEcheance = trim((string) ($input['date_echeance'] ?? ''));
		$montantLoyer = (string) ($input['montant_loyer'] ?? '');
		$montantCharges = (string) ($input['montant_charges'] ?? '') !== '' ? (string) $input['montant_charges'] : '0';

		if ($idContrat < 1 || !self::belongsToAgence($idAgence, 'contrat', 'id_contrat', $idContrat)) {
			$errors['id_contrat'] = 'Le contrat est requis et doit appartenir à votre agence.';
		}
		if ($periodeDebut === '') {
			$errors['periode_debut'] = 'La date de début de période est requise.';
		}
		if ($periodeFin === '') {
			$errors['periode_fin'] = 'La date de fin de période est requise.';
		} elseif ($periodeDebut !== '' && $periodeFin < $periodeDebut) {
			$errors['periode_fin'] = 'La fin de période doit être postérieure au début.';
		}
		if ($dateEcheance === '') {
			$errors['date_echeance'] = 'La date d’échéance est requise.';
		}
		if (!is_numeric($montantLoyer) || (float) $montantLoyer < 0) {
			$errors['montant_loyer'] = 'Le loyer doit être un nombre positif.';
		}
		if (!is_numeric($montantCharges) || (float) $montantCharges < 0) {
			$errors['montant_charges'] = 'Les charges doivent être un nombre positif.';
		}

		if ($errors) {
			return $errors;
		}

		try {
			$id = MonAgenceModel::createEcheance([
				'id_contrat' => $idContrat,
				'periode_debut' => $periodeDebut,
				'periode_fin' => $periodeFin,
				'montant_loyer' => $montantLoyer,
				'montant_charges' => $montantCharges,
				'date_echeance' => $dateEcheance,
			]);
		} catch (PDOException $exception) {
			if ((int) $exception->getCode() === 23000) {
				return ['_global' => 'Une échéance existe déjà pour ce contrat sur cette période.'];
			}
			throw $exception;
		}

		Logger::audit('CREATE', 'echeance', $id, $idUtilisateur, $idAgence, null, ['id_contrat' => $idContrat, 'periode_debut' => $periodeDebut]);
		return [];
	}

	/** @return string|null Message d'erreur, ou null si l'opération a réussi. */
	public static function createPaiement(int $idAgence, int $idEcheance, int $idUtilisateur, array $input): ?string
	{
		$echeance = MonAgenceModel::echeanceFind($idAgence, $idEcheance);
		if (!$echeance) {
			return 'Échéance introuvable pour votre agence.';
		}

		$montant = (string) ($input['montant'] ?? '');
		$datePaiement = trim((string) ($input['date_paiement'] ?? ''));
		$mode = (string) ($input['mode_paiement'] ?? '');
		$statut = (string) ($input['statut'] ?? 'PAID');
		$idLocataire = filter_var($input['id_locataire'] ?? null, FILTER_VALIDATE_INT) ?: null;
		$reference = trim((string) ($input['reference_transaction'] ?? ''));

		if (!is_numeric($montant) || (float) $montant <= 0) {
			return 'Le montant doit être un nombre positif.';
		}
		if ($datePaiement === '') {
			return 'La date de paiement est requise.';
		}
		if (!in_array($mode, self::PAIEMENT_MODES, true)) {
			return 'Le mode de paiement est invalide.';
		}
		if (!in_array($statut, self::PAIEMENT_STATUSES, true)) {
			return 'Le statut est invalide.';
		}
		if ($idLocataire !== null && !self::belongsToAgence($idAgence, 'locataire', 'id_locataire', $idLocataire)) {
			return 'Le locataire sélectionné est invalide.';
		}

		try {
			$idPaiement = MonAgenceModel::createPaiement([
				'id_echeance' => $idEcheance,
				'id_contrat' => (int) $echeance['id_contrat'],
				'id_locataire' => $idLocataire,
				'montant' => $montant,
				'date_paiement' => $datePaiement,
				'mode_paiement' => $mode,
				'reference_transaction' => $reference !== '' ? $reference : null,
				'statut' => $statut,
				'commentaire' => self::nullableTrim($input['commentaire'] ?? null),
			]);
		} catch (PDOException $exception) {
			if ((int) $exception->getCode() === 23000) {
				return 'Cette référence de transaction est déjà utilisée.';
			}
			throw $exception;
		}

		MonAgenceModel::refreshEcheanceStatut($idEcheance);
		Logger::audit('CREATE', 'paiement', $idPaiement, $idUtilisateur, $idAgence, null, ['montant' => $montant, 'id_echeance' => $idEcheance]);

		if ($statut === 'PAID') {
			self::generateQuittance($idAgence, $idPaiement, $idUtilisateur);
		}

		return null;
	}

	/** @return array{list: array} */
	public static function quittances(int $idAgence): array
	{
		return ['list' => MonAgenceModel::quittances($idAgence)];
	}

	/* --------------------------------------------------------------
	 * Demande de paiement en ligne (Stripe Checkout, mode paiement unique).
	 * -------------------------------------------------------------- */

	/** @return array{error: ?string, url: ?string} */
	public static function createDemandePaiement(int $idAgence, int $idEcheance, int $idUtilisateur): array
	{
		$echeance = MonAgenceModel::echeanceFind($idAgence, $idEcheance);
		if (!$echeance) {
			return ['error' => 'Échéance introuvable pour votre agence.', 'url' => null];
		}
		$totalPaye = MonAgenceModel::echeanceTotalPaid($idEcheance);
		$solde = round((float) $echeance['montant_total'] - $totalPaye, 2);
		if ($solde <= 0) {
			return ['error' => 'Cette échéance est déjà réglée.', 'url' => null];
		}

		$existing = MonAgenceModel::demandePaiementLatestForEcheance($idEcheance);
		if ($existing && $existing['statut'] === 'PENDING' && (string) $existing['stripe_checkout_session_id'] !== '') {
			try {
				$session = StripeClient::retrieveCheckoutSession((string) $existing['stripe_checkout_session_id']);
				if (($session['status'] ?? '') === 'open') {
					return ['error' => null, 'url' => (string) $session['url']];
				}
			} catch (Throwable $exception) {
				// La session Stripe n'est plus récupérable (expirée côté Stripe) : on en recrée une ci-dessous.
			}
		}

		$tenants = MonAgenceModel::echeanceTenants($idEcheance);
		$principal = $tenants[0] ?? null;
		$idLocataire = $principal ? (int) $principal['id_locataire'] : null;

		$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/LOKA/app/administration/mon-agence/paiement-locataire-retour.php';

		$idDemande = MonAgenceModel::createDemandePaiement([
			'id_agence' => $idAgence,
			'id_echeance' => $idEcheance,
			'id_locataire' => $idLocataire,
			'montant' => $solde,
			'stripe_checkout_session_id' => null,
			'id_utilisateur_createur' => $idUtilisateur,
		]);

		try {
			$session = StripeClient::createOneTimeCheckoutSession(
				null,
				'Loyer — ' . $echeance['bien_titre'] . ' (' . (new DateTimeImmutable($echeance['periode_debut']))->format('d/m/Y') . ' — ' . (new DateTimeImmutable($echeance['periode_fin']))->format('d/m/Y') . ')',
				$solde,
				$baseUrl . '?success=1&session_id={CHECKOUT_SESSION_ID}',
				$baseUrl . '?cancel=1',
				['kind' => 'loyer', 'id_demande' => $idDemande]
			);
		} catch (Throwable $exception) {
			return ['error' => 'Impossible de créer le paiement en ligne : ' . $exception->getMessage(), 'url' => null];
		}

		MonAgenceModel::setDemandePaiementSession($idDemande, (string) $session['id'], (string) $session['url']);
		Logger::audit('CREATE', 'demande_paiement', $idDemande, $idUtilisateur, $idAgence, null, ['id_echeance' => $idEcheance, 'montant' => $solde]);

		return ['error' => null, 'url' => (string) $session['url']];
	}

	public static function cancelDemandePaiement(int $idAgence, int $idDemande, int $idUtilisateur): bool
	{
		$ok = MonAgenceModel::cancelDemandePaiement($idAgence, $idDemande);
		if ($ok) {
			Logger::audit('UPDATE', 'demande_paiement', $idDemande, $idUtilisateur, $idAgence, null, ['statut' => 'CANCELLED']);
		}
		return $ok;
	}

	/** Appelé par le webhook Stripe (app/administration/abonnements/webhook.php) pour une session `kind=loyer`. */
	public static function handleRentPaymentWebhookEvent(array $event): void
	{
		$type = (string) ($event['type'] ?? '');
		$session = $event['data']['object'] ?? [];
		if ($type === 'checkout.session.completed') {
			self::processRentCheckoutSession($session);
		} elseif ($type === 'checkout.session.expired') {
			$demande = MonAgenceModel::demandePaiementBySessionId((string) ($session['id'] ?? ''));
			if ($demande) {
				MonAgenceModel::markDemandePaiementExpired((int) $demande['id_demande']);
			}
		}
	}

	/**
	 * Confirme une session directement auprès de Stripe au retour du locataire sur la page de
	 * remerciement, sans dépendre du webhook — indispensable en local XAMPP sans `stripe listen`
	 * (même pattern que AbonnementController::confirmCheckoutSession).
	 */
	public static function confirmRentCheckoutSession(string $sessionId): string
	{
		$demande = MonAgenceModel::demandePaiementBySessionId($sessionId);
		if (!$demande) {
			return 'unknown';
		}
		if ($demande['statut'] === 'PAID') {
			return 'already';
		}
		try {
			$session = StripeClient::retrieveCheckoutSession($sessionId);
		} catch (Throwable $exception) {
			return 'pending';
		}
		if (($session['payment_status'] ?? '') !== 'paid') {
			return 'pending';
		}
		self::processRentCheckoutSession($session);
		return 'confirmed';
	}

	/**
	 * Enregistre un paiement de loyer reçu en ligne et enchaîne toute l'automatisation :
	 * échéance mise à jour, quittance + document générés, contrat activé si besoin. Idempotent :
	 * ignore une session déjà traitée (la demande n'est alors plus PENDING).
	 */
	private static function processRentCheckoutSession(array $session): void
	{
		$sessionId = (string) ($session['id'] ?? '');
		if ($sessionId === '') {
			return;
		}
		$demande = MonAgenceModel::demandePaiementBySessionId($sessionId);
		if (!$demande || $demande['statut'] !== 'PENDING') {
			return;
		}
		$idAgence = (int) $demande['id_agence'];
		$echeance = MonAgenceModel::echeanceFind($idAgence, (int) $demande['id_echeance']);
		if (!$echeance) {
			return;
		}
		$idUtilisateur = (int) ($demande['id_utilisateur_createur'] ?? 0);
		$reference = (string) ($session['payment_intent'] ?? $sessionId);

		$idPaiement = MonAgenceModel::createPaiement([
			'id_echeance' => $demande['id_echeance'],
			'id_contrat' => (int) $echeance['id_contrat'],
			'id_locataire' => $demande['id_locataire'],
			'montant' => $demande['montant'],
			'date_paiement' => date('Y-m-d H:i:s'),
			'mode_paiement' => 'CARD',
			'reference_transaction' => $reference,
			'statut' => 'PAID',
			'commentaire' => 'Paiement en ligne via demande de paiement.',
		]);

		MonAgenceModel::refreshEcheanceStatut((int) $demande['id_echeance']);
		Logger::audit('CREATE', 'paiement', $idPaiement, $idUtilisateur, $idAgence, null, ['montant' => $demande['montant'], 'via' => 'stripe']);

		self::generateQuittance($idAgence, $idPaiement, $idUtilisateur);
		MonAgenceModel::markDemandePaiementPaid((int) $demande['id_demande'], $idPaiement);

		$idContrat = (int) $echeance['id_contrat'];
		$contrat = MonAgenceModel::contratFind($idAgence, $idContrat);
		if ($contrat && $contrat['statut'] === 'DRAFT' && MonAgenceModel::updateContratStatus($idAgence, $idContrat, 'ACTIVE')) {
			Logger::audit('UPDATE', 'contrat', $idContrat, $idUtilisateur, $idAgence, null, ['statut' => 'ACTIVE', 'via' => 'premier_paiement_en_ligne']);
			self::regenerateContratDocument($idAgence, $idContrat);
		}
	}

	private static function generateQuittance(int $idAgence, int $idPaiement, int $idUtilisateur): void
	{
		if (MonAgenceModel::quittanceByPaiement($idPaiement)) {
			return;
		}
		$numero = 'QUIT-' . date('Y') . '-' . str_pad((string) $idPaiement, 6, '0', STR_PAD_LEFT);
		$db = Database::connection();
		$periode = $db->prepare(
			'SELECT e.periode_debut, e.periode_fin FROM paiement p INNER JOIN echeance e ON e.id_echeance = p.id_echeance WHERE p.id_paiement = :id'
		);
		$periode->execute(['id' => $idPaiement]);
		$p = $periode->fetch();

		$idQuittance = MonAgenceModel::createQuittance($idPaiement, $numero, $p['periode_debut'] ?? null, $p['periode_fin'] ?? null);
		Logger::audit('CREATE', 'quittance', $idQuittance, $idUtilisateur, $idAgence, null, ['numero' => $numero]);
		self::regenerateQuittanceDocument($idAgence, $idQuittance);

		// La quittance est aussi enregistrée comme document du contrat, pour qu'elle apparaisse
		// automatiquement dans l'onglet Documents de l'agence sans action manuelle supplémentaire.
		$documentData = MonAgenceModel::quittanceDocumentData($idAgence, $idQuittance);
		$path = (string) ($documentData['quittance']['chemin_fichier_pdf'] ?? '');
		if ($documentData !== null && $path !== '') {
			MonAgenceModel::createDocument([
				'id_type_document' => 3,
				'id_bien' => null,
				'id_contrat' => (int) $documentData['paiement']['id_contrat'],
				'id_proprietaire' => null,
				'id_locataire' => null,
				'id_intervention' => null,
				'nom' => 'Quittance ' . $numero,
				'nom_original' => basename($path),
				'mime_type' => 'text/html',
				'taille_octets' => null,
				'chemin_stockage' => $path,
			]);
		}
	}

	private static function regenerateQuittanceDocument(int $idAgence, int $idQuittance): void
	{
		$data = MonAgenceModel::quittanceDocumentData($idAgence, $idQuittance);
		if ($data === null) {
			return;
		}
		$destinationDir = self::QUITTANCE_UPLOAD_DIR;
		if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
			return;
		}
		$existingPath = (string) ($data['quittance']['chemin_fichier_pdf'] ?? '');
		$storedName = $existingPath !== '' ? basename($existingPath) : bin2hex(random_bytes(16)) . '.html';

		$html = self::renderQuittanceDocumentHtml($data);
		file_put_contents($destinationDir . DIRECTORY_SEPARATOR . $storedName, $html);

		if ($existingPath === '') {
			MonAgenceModel::setQuittanceDocumentPath((int) $data['quittance']['id_quittance'], 'public/uploads/quittances/' . $storedName);
		}
	}

	/** @param array{agence: ?array, quittance: array, paiement: array, bien: ?array} $data */
	private static function renderQuittanceDocumentHtml(array $data): string
	{
		$esc = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
		$money = static fn (mixed $v): string => number_format((float) $v, 2, ',', ' ') . ' €';
		$date = static fn (?string $v): string => $v ? (new DateTimeImmutable($v))->format('d/m/Y') : '—';

		$agence = $data['agence'] ?? [];
		$quittance = $data['quittance'];
		$paiement = $data['paiement'];
		$bien = $data['bien'] ?? [];

		$modeLabels = ['CASH' => 'Espèces', 'BANK_TRANSFER' => 'Virement', 'CARD' => 'Carte', 'CHEQUE' => 'Chèque', 'DIRECT_DEBIT' => 'Prélèvement', 'OTHER' => 'Autre'];

		$locataireLine = $paiement['locataire_nom'] ? $esc($paiement['locataire_nom']) : 'Locataire';
		$adresseLocataire = $paiement['adresse'] ? '<br>' . $esc(trim($paiement['adresse'] . ', ' . $paiement['code_postal'] . ' ' . $paiement['ville'], ' ,')) : '';
		$bienLine = $bien ? $esc($bien['titre'] . ' (' . $bien['reference'] . ')') : '—';
		$agenceAdresse = !empty($agence['adresse']) ? $esc(', ' . $agence['adresse'] . ', ' . $agence['code_postal'] . ' ' . $agence['ville']) : '';

		$pageTitle = 'Quittance de loyer ' . $esc($quittance['numero']);

		return <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{$pageTitle}</title>
<style>
  body{font-family:Georgia,'Times New Roman',serif;color:#1a2b2b;max-width:680px;margin:40px auto;padding:0 24px;line-height:1.6;}
  h1{font-size:19px;text-align:center;margin:0 0 4px;letter-spacing:.03em;}
  .subtitle{text-align:center;color:#5a6b6b;font-size:13px;margin:0 0 28px;}
  p{margin:0 0 10px;font-size:13.5px;}
  table{width:100%;border-collapse:collapse;font-size:13.5px;margin-top:12px;}
  td{padding:6px 0;border-bottom:1px solid #eef2f2;}
  td:first-child{color:#5a6b6b;width:50%;}
  td:last-child{text-align:right;font-weight:600;}
  .statement{margin-top:24px;font-size:13.5px;color:#5a6b6b;}
  .print-bar{text-align:center;margin-bottom:24px;}
  .print-bar button{font-family:inherit;font-size:13px;padding:8px 16px;border-radius:8px;border:1px solid #0f8b8d;background:#0f8b8d;color:#fff;cursor:pointer;}
  @media print{.print-bar{display:none;}body{margin:0;max-width:none;}}
</style>
</head>
<body>
  <div class="print-bar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>

  <h1>Quittance de loyer</h1>
  <p class="subtitle">N° {$esc($quittance['numero'])} · émise le {$date($quittance['date_emission'])} · {$esc($agence['nom'] ?? '')}</p>

  <p><strong>{$esc($agence['nom'] ?? '')}</strong>{$agenceAdresse}</p>

  <p>Reçu de <strong>{$locataireLine}</strong>{$adresseLocataire}</p>

  <table>
    <tr><td>Bien concerné</td><td>{$bienLine}</td></tr>
    <tr><td>Période</td><td>{$date($quittance['periode_debut'])} — {$date($quittance['periode_fin'])}</td></tr>
    <tr><td>Montant réglé</td><td>{$money($paiement['montant'])}</td></tr>
    <tr><td>Mode de règlement</td><td>{$esc($modeLabels[$paiement['mode_paiement']] ?? $paiement['mode_paiement'])}</td></tr>
    <tr><td>Date de règlement</td><td>{$date($paiement['date_paiement'])}</td></tr>
  </table>

  <p class="statement">La présente quittance est délivrée sous réserve d’encaissement effectif et annule tout décompte antérieur pour la période concernée.</p>
</body>
</html>
HTML;
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
