<?php
declare(strict_types=1);

final class ProprietaireValidator
{
	public static function validate(array $input): array
	{
		$errors = [];
		foreach (['nom' => 150, 'prenom' => 150] as $field => $max) {
			$value = trim((string) ($input[$field] ?? ''));
			if ($value === '' || mb_strlen($value) > $max) {
				$errors[$field] = $value === '' ? 'Ce champ est obligatoire.' : 'Cette valeur est trop longue.';
			}
		}
		$email = trim((string) ($input['email'] ?? ''));
		if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255)) {
			$errors['email'] = 'L’adresse e-mail est invalide.';
		}
		foreach (['telephone' => 30, 'adresse' => 255, 'code_postal' => 20, 'ville' => 100, 'pays' => 100] as $field => $max) {
			if (mb_strlen(trim((string) ($input[$field] ?? ''))) > $max) {
				$errors[$field] = 'Cette valeur est trop longue.';
			}
		}
		if (($input['date_naissance'] ?? '') !== '' && !DateTimeImmutable::createFromFormat('Y-m-d', (string) $input['date_naissance'])) {
			$errors['date_naissance'] = 'La date de naissance est invalide.';
		}
		return $errors;
	}

	public static function association(array $input): array
	{
		$errors = [];
		$propertyId = filter_var($input['id_bien'] ?? null, FILTER_VALIDATE_INT);
		$share = filter_var($input['quote_part'] ?? 100, FILTER_VALIDATE_FLOAT);
		$date = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($input['date_debut'] ?? ''));
		if ($propertyId === false || $propertyId < 1) {
			$errors['id_bien'] = 'Le bien sélectionné est invalide.';
		}
		if ($share === false || $share <= 0 || $share > 100) {
			$errors['quote_part'] = 'La quote-part doit être comprise entre 0,01 et 100.';
		}
		if (!$date) {
			$errors['date_debut'] = 'La date de début est obligatoire.';
		}
		return $errors;
	}
}