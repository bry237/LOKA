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
}
