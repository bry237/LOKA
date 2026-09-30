<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Phone.php';

final class AuthValidator
{
	public static function login(array $input): array
	{
		$email = trim((string) ($input['email'] ?? ''));
		$password = (string) ($input['password'] ?? '');
		$errors = [];
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse email invalide.';
		if ($password === '') $errors['password'] = 'Le mot de passe est requis.';
		return [$errors, ['email' => $email, 'password' => $password]];
	}

	public static function registration(array $input, array $files = []): array
	{
		$data = [
			'nom' => trim((string) ($input['last_name'] ?? '')), 'prenom' => trim((string) ($input['first_name'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')), 'telephone' => trim((string) ($input['phone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['phone_country_code'] ?? '+33')),
			'adresse' => trim((string) ($input['address'] ?? '')), 'code_postal' => trim((string) ($input['postal_code'] ?? '')),
			'ville' => trim((string) ($input['city'] ?? '')), 'pays' => trim((string) ($input['country'] ?? 'France')),
			'date_naissance' => trim((string) ($input['birth_date'] ?? '')), 'profession' => trim((string) ($input['profession'] ?? '')),
			'revenu_mensuel_fourchette' => trim((string) ($input['monthly_income'] ?? '')),
			'password' => (string) ($input['password'] ?? ''),
		];
		$errors = [];
		foreach (['nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'Email', 'telephone' => 'Téléphone', 'adresse' => 'Adresse', 'code_postal' => 'Code postal', 'ville' => 'Ville', 'pays' => 'Pays', 'date_naissance' => 'Date de naissance', 'profession' => 'Profession', 'revenu_mensuel_fourchette' => 'Revenu mensuel'] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label requis.";
		}
		if (!isset($errors['telephone'])) {
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) $errors['telephone'] = 'Numéro de téléphone invalide.';
			else $data['telephone'] = $e164;
		}
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse email invalide.';
		if (strlen($data['password']) < 8) $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
		if ($data['password'] !== (string) ($input['password_confirm'] ?? '')) $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
		if (empty($input['terms'])) $errors['terms'] = 'Vous devez accepter les conditions d’utilisation.';
		$date = DateTimeImmutable::createFromFormat('Y-m-d', $data['date_naissance']);
		if (!$date || $date->format('Y-m-d') !== $data['date_naissance']) $errors['date_naissance'] = 'Date de naissance invalide.';
		self::requireDocument($files, 'piece_identite', 'La pièce d’identité', $errors);
		self::requireDocument($files, 'justificatif_domicile', 'Le justificatif de domicile', $errors);
		return [$errors, $data];
	}

	public static function proprietorRegistration(array $input, array $files = []): array
	{
		$data = [
			'nom' => trim((string) ($input['last_name'] ?? '')),
			'prenom' => trim((string) ($input['first_name'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['phone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['phone_country_code'] ?? '+33')),
			'adresse' => trim((string) ($input['address'] ?? '')),
			'code_postal' => trim((string) ($input['postal_code'] ?? '')),
			'ville' => trim((string) ($input['city'] ?? '')),
			'pays' => trim((string) ($input['country'] ?? 'France')),
			'date_naissance' => trim((string) ($input['birth_date'] ?? '')),
			'projet' => trim((string) ($input['project'] ?? '')),
			'password' => (string) ($input['password'] ?? ''),
		];
		$errors = self::required($data, [
			'nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'Email', 'telephone' => 'Téléphone',
			'adresse' => 'Adresse', 'code_postal' => 'Code postal', 'ville' => 'Ville', 'pays' => 'Pays',
			'date_naissance' => 'Date de naissance', 'projet' => 'Projet',
		]);
		if (!isset($errors['telephone'])) {
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) $errors['telephone'] = 'Numéro de téléphone invalide.';
			else $data['telephone'] = $e164;
		}
		self::validateCommonAccount($data, $input, $errors);
		if (!in_array($data['projet'], ['rent', 'sell', 'rent-sell'], true)) $errors['project'] = 'Projet invalide.';
		$date = DateTimeImmutable::createFromFormat('Y-m-d', $data['date_naissance']);
		if (!$date || $date->format('Y-m-d') !== $data['date_naissance']) $errors['birth_date'] = 'Date de naissance invalide.';
		self::requireDocument($files, 'piece_identite', 'La pièce d’identité', $errors);
		self::requireDocument($files, 'justificatif_domicile', 'Le justificatif de domicile', $errors);
		return [$errors, $data];
	}

	private const MAX_DOCUMENT_BYTES = 5 * 1024 * 1024;
	private const ALLOWED_DOCUMENT_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];

	public static function agencyRegistration(array $input, array $files = []): array
	{
		$data = [
			'agence_nom' => trim((string) ($input['agency_name'] ?? '')),
			'agence_email' => trim((string) ($input['agency_email'] ?? '')),
			'agence_telephone' => trim((string) ($input['agency_phone'] ?? '')),
			'agence_indicatif_pays' => trim((string) ($input['agency_phone_country_code'] ?? '+33')),
			'agence_adresse' => trim((string) ($input['agency_address'] ?? '')),
			'agence_code_postal' => trim((string) ($input['agency_postal_code'] ?? '')),
			'agence_ville' => trim((string) ($input['agency_city'] ?? '')),
			'agence_pays' => trim((string) ($input['agency_country'] ?? 'France')),
			'nom' => trim((string) ($input['manager_last_name'] ?? '')),
			'prenom' => trim((string) ($input['manager_first_name'] ?? '')),
			'email' => trim((string) ($input['manager_email'] ?? '')),
			'telephone' => trim((string) ($input['manager_phone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['manager_phone_country_code'] ?? '+33')),
			'password' => (string) ($input['manager_password'] ?? ''),
		];
		$errors = self::required($data, [
			'agence_nom' => 'Nom de l’agence', 'agence_email' => 'Email professionnel',
			'agence_telephone' => 'Téléphone de l’agence', 'agence_adresse' => 'Adresse de l’agence',
			'agence_code_postal' => 'Code postal de l’agence', 'agence_ville' => 'Ville de l’agence',
			'agence_pays' => 'Pays de l’agence', 'nom' => 'Nom du responsable', 'prenom' => 'Prénom du responsable',
			'email' => 'Email du responsable', 'telephone' => 'Téléphone du responsable',
		]);
		if (!isset($errors['agence_telephone'])) {
			$e164 = Phone::toE164($data['agence_indicatif_pays'], $data['agence_telephone']);
			if ($e164 === null) $errors['agency_phone'] = 'Numéro de téléphone de l’agence invalide.';
			else $data['agence_telephone'] = $e164;
		}
		if (!isset($errors['telephone'])) {
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) $errors['manager_phone'] = 'Numéro de téléphone du responsable invalide.';
			else $data['telephone'] = $e164;
		}
		if (!filter_var($data['agence_email'], FILTER_VALIDATE_EMAIL)) $errors['agency_email'] = 'Email professionnel invalide.';
		self::validateCommonAccount($data, $input, $errors, 'password', 'manager_password_confirm');
		self::requireDocument($files, 'piece_identite_responsable', 'La pièce d’identité du responsable', $errors);
		self::requireDocument($files, 'justificatif_agence', 'Le justificatif d’immatriculation de l’agence', $errors);
		return [$errors, $data];
	}

	private static function requireDocument(array $files, string $field, string $label, array &$errors): void
	{
		$file = $files[$field] ?? null;
		if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			$errors[$field] = "$label est obligatoire.";
			return;
		}
		if ($file['error'] !== UPLOAD_ERR_OK) {
			$errors[$field] = "$label n’a pas pu être envoyé.";
			return;
		}
		if ((int) $file['size'] > self::MAX_DOCUMENT_BYTES) {
			$errors[$field] = "$label dépasse la taille maximale autorisée (5 Mo).";
			return;
		}
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (!in_array($finfo->file($file['tmp_name']), self::ALLOWED_DOCUMENT_MIMES, true)) {
			$errors[$field] = "$label doit être une image (JPEG/PNG) ou un PDF.";
		}
	}

	private static function required(array $data, array $labels): array
	{
		$errors = [];
		foreach ($labels as $key => $label) if (($data[$key] ?? '') === '') $errors[$key] = "$label requis.";
		return $errors;
	}

	private static function validateCommonAccount(array $data, array $input, array &$errors, string $passwordKey = 'password', string $confirmationKey = 'password_confirm'): void
	{
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse email invalide.';
		if (strlen($data[$passwordKey] ?? '') < 8) $errors[$passwordKey] = 'Le mot de passe doit contenir au moins 8 caractères.';
		if (($data[$passwordKey] ?? '') !== (string) ($input[$confirmationKey] ?? '')) $errors[$confirmationKey] = 'Les mots de passe ne correspondent pas.';
	}
}
