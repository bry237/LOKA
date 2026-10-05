<?php
declare(strict_types=1);

final class AgenceValidator
{
	public static function agence(array $input): array
	{
		$data = [
			'nom' => trim((string) ($input['nom'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['telephone'] ?? '')),
			'adresse' => trim((string) ($input['adresse'] ?? '')),
			'code_postal' => trim((string) ($input['code_postal'] ?? '')),
			'ville' => trim((string) ($input['ville'] ?? '')),
			'pays' => trim((string) ($input['pays'] ?? 'France')),
		];

		$errors = [];
		foreach ([
			'nom' => 'Le nom de l’agence',
			'email' => 'L’email',
			'telephone' => 'Le téléphone',
			'adresse' => 'L’adresse',
			'code_postal' => 'Le code postal',
			'ville' => 'La ville',
			'pays' => 'Le pays',
		] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label est requis.";
		}
		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Adresse email invalide.';
		}

		return [$errors, $data];
	}
}
