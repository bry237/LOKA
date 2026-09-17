<?php
declare(strict_types=1);

final class BienValidator
{
	public static function address(array $input): array
	{
		$errors = [];
		$line1 = trim((string) ($input['ligne1'] ?? ''));
		$city = trim((string) ($input['ville'] ?? ''));
		$postalCode = trim((string) ($input['code_postal'] ?? ''));

		if ($line1 === '' || mb_strlen($line1) > 255) {
			$errors['ligne1'] = 'La première ligne de l’adresse est obligatoire (255 caractères maximum).';
		}
		if ($city === '' || mb_strlen($city) > 100) {
			$errors['ville'] = 'La ville est obligatoire (100 caractères maximum).';
		}
		if (mb_strlen($postalCode) > 20) {
			$errors['code_postal'] = 'Le code postal ne peut pas dépasser 20 caractères.';
		}
		if (mb_strlen(trim((string) ($input['ligne2'] ?? ''))) > 255) {
			$errors['ligne2'] = 'La seconde ligne ne peut pas dépasser 255 caractères.';
		}
		if (mb_strlen(trim((string) ($input['region'] ?? ''))) > 100) {
			$errors['region'] = 'La région ne peut pas dépasser 100 caractères.';
		}
		if (mb_strlen(trim((string) ($input['pays'] ?? 'France'))) > 100) {
			$errors['pays'] = 'Le pays ne peut pas dépasser 100 caractères.';
		}

		return $errors;
	}

	public static function building(array $input): array
	{
		$addressId = filter_var($input['id_adresse'] ?? null, FILTER_VALIDATE_INT);
		$errors = ($addressId !== false && $addressId !== null && $addressId > 0)
			? []
			: self::address($input);
		$name = trim((string) ($input['nom'] ?? ''));
		$floorCount = filter_var($input['nombre_etages'] ?? 0, FILTER_VALIDATE_INT);

		if ($name !== '' && mb_strlen($name) > 150) {
			$errors['nom'] = 'Le nom de l’immeuble ne peut pas dépasser 150 caractères.';
		}
		if (($input['id_adresse'] ?? '') !== '' && ($addressId === false || $addressId < 1)) {
			$errors['id_adresse'] = 'L’adresse sélectionnée est invalide.';
		}
		if ($floorCount === false || $floorCount < 0) {
			$errors['nombre_etages'] = 'Le nombre d’étages doit être un entier positif ou nul.';
		}
		if (mb_strlen(trim((string) ($input['description'] ?? ''))) > 65535) {
			$errors['description'] = 'La description est trop longue.';
		}

		return $errors;
	}

	public static function equipment(array $input): array
	{
		$name = trim((string) ($input['nom'] ?? ''));
		$errors = [];
		if ($name === '' || mb_strlen($name) > 150) {
			$errors['nom'] = 'Le nom est obligatoire (150 caractères maximum).';
		}
		if (mb_strlen(trim((string) ($input['description'] ?? ''))) > 65535) {
			$errors['description'] = 'La description est trop longue.';
		}
		return $errors;
	}

	public static function property(array $input): array
	{
		$errors = [];
		$requiredText = [
			'reference' => [100, 'La référence est obligatoire.'],
			'titre' => [200, 'Le titre est obligatoire.'],
		];
		foreach ($requiredText as $field => [$maxLength, $message]) {
			$value = trim((string) ($input[$field] ?? ''));
			if ($value === '' || mb_strlen($value) > $maxLength) {
				$errors[$field] = $value === '' ? $message : 'La valeur saisie est trop longue.';
			}
		}

		foreach (['id_type_bien', 'id_adresse', 'id_immeuble'] as $field) {
			if ($field === 'id_immeuble' && ($input[$field] ?? '') === '') {
				continue;
			}
			$value = filter_var($input[$field] ?? null, FILTER_VALIDATE_INT);
			if ($value === false || $value < 1) {
				$errors[$field] = 'La valeur sélectionnée est invalide.';
			}
		}

		foreach (['surface', 'loyer', 'charges', 'caution'] as $field) {
			$value = filter_var($input[$field] ?? null, FILTER_VALIDATE_FLOAT);
			if ($value === false || $value < 0) {
				$errors[$field] = 'La valeur doit être un nombre positif ou nul.';
			}
		}
		foreach (['nombre_pieces', 'etage'] as $field) {
			if (($field === 'etage') && ($input[$field] ?? '') === '') {
				continue;
			}
			$value = filter_var($input[$field] ?? null, FILTER_VALIDATE_INT);
			if ($value === false || $value < 0) {
				$errors[$field] = 'La valeur doit être un entier positif ou nul.';
			}
		}

		$statuses = ['CREATED', 'AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'ARCHIVED'];
		if (!in_array($input['statut'] ?? 'CREATED', $statuses, true)) {
			$errors['statut'] = 'Le statut sélectionné est invalide.';
		}
		if (mb_strlen(trim((string) ($input['description'] ?? ''))) > 65535) {
			$errors['description'] = 'La description est trop longue.';
		}

		return $errors;
	}
}