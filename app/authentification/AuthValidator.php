<?php
declare(strict_types=1);

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

	public static function registration(array $input): array
	{
		$data = [
			'nom' => trim((string) ($input['last_name'] ?? '')), 'prenom' => trim((string) ($input['first_name'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')), 'telephone' => trim((string) ($input['phone'] ?? '')),
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
		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse email invalide.';
		if (strlen($data['password']) < 8) $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
		if ($data['password'] !== (string) ($input['password_confirm'] ?? '')) $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
		if (empty($input['terms'])) $errors['terms'] = 'Vous devez accepter les conditions d’utilisation.';
		$date = DateTimeImmutable::createFromFormat('Y-m-d', $data['date_naissance']);
		if (!$date || $date->format('Y-m-d') !== $data['date_naissance']) $errors['date_naissance'] = 'Date de naissance invalide.';
		return [$errors, $data];
	}

	public static function proprietorRegistration(array $input): array
	{
		$data = [
			'nom' => trim((string) ($input['last_name'] ?? '')),
			'prenom' => trim((string) ($input['first_name'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['phone'] ?? '')),
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
		self::validateCommonAccount($data, $input, $errors);
		if (!in_array($data['projet'], ['rent', 'sell', 'rent-sell'], true)) $errors['project'] = 'Projet invalide.';
		$date = DateTimeImmutable::createFromFormat('Y-m-d', $data['date_naissance']);
		if (!$date || $date->format('Y-m-d') !== $data['date_naissance']) $errors['birth_date'] = 'Date de naissance invalide.';
		return [$errors, $data];
	}

	public static function agencyRegistration(array $input): array
	{
		$data = [
			'agence_nom' => trim((string) ($input['agency_name'] ?? '')),
			'agence_email' => trim((string) ($input['agency_email'] ?? '')),
			'agence_telephone' => trim((string) ($input['agency_phone'] ?? '')),
			'agence_adresse' => trim((string) ($input['agency_address'] ?? '')),
			'agence_code_postal' => trim((string) ($input['agency_postal_code'] ?? '')),
			'agence_ville' => trim((string) ($input['agency_city'] ?? '')),
			'agence_pays' => trim((string) ($input['agency_country'] ?? 'France')),
			'nom' => trim((string) ($input['manager_last_name'] ?? '')),
			'prenom' => trim((string) ($input['manager_first_name'] ?? '')),
			'email' => trim((string) ($input['manager_email'] ?? '')),
			'telephone' => trim((string) ($input['manager_phone'] ?? '')),
			'password' => (string) ($input['manager_password'] ?? ''),
		];
		$errors = self::required($data, [
			'agence_nom' => 'Nom de l’agence', 'agence_email' => 'Email professionnel',
			'agence_telephone' => 'Téléphone de l’agence', 'agence_adresse' => 'Adresse de l’agence',
			'agence_code_postal' => 'Code postal de l’agence', 'agence_ville' => 'Ville de l’agence',
			'agence_pays' => 'Pays de l’agence', 'nom' => 'Nom du responsable', 'prenom' => 'Prénom du responsable',
			'email' => 'Email du responsable', 'telephone' => 'Téléphone du responsable',
		]);
		if (!filter_var($data['agence_email'], FILTER_VALIDATE_EMAIL)) $errors['agency_email'] = 'Email professionnel invalide.';
		self::validateCommonAccount($data, $input, $errors, 'password', 'manager_password_confirm');
		return [$errors, $data];
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
