<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Phone.php';

final class UtilisateurValidator
{
	/**
	 * Rôles qu'un Administrateur plateforme peut attribuer depuis cette page. Propriétaire et
	 * Locataire sont volontairement exclus : ces comptes sont créés via leurs parcours dédiés
	 * (inscription propriétaire, ajout d'un locataire par une agence), qui créent aussi la fiche
	 * métier correspondante — les créer ici laisserait un utilisateur sans fiche associée.
	 */
	public const CREATABLE_ROLE_IDS = [1, 2, 3, 4, 7];

	/**
	 * @return array{0: array<string,string>, 1: array{nom:string,prenom:string,email:string,telephone:?string,id_role:int,id_agence:?int}}
	 */
	public static function utilisateur(array $input, ?int $excludeId = null): array
	{
		$idRole = (int) ($input['id_role'] ?? 0);
		$data = [
			'nom' => trim((string) ($input['nom'] ?? '')),
			'prenom' => trim((string) ($input['prenom'] ?? '')),
			'email' => trim((string) ($input['email'] ?? '')),
			'telephone' => trim((string) ($input['telephone'] ?? '')),
			'indicatif_pays' => trim((string) ($input['indicatif_pays'] ?? '+33')),
			'id_role' => $idRole,
			'id_agence' => (int) ($input['id_agence'] ?? 0) ?: null,
		];

		$errors = [];
		foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom', 'email' => 'L’email'] as $key => $label) {
			if ($data[$key] === '') $errors[$key] = "$label est requis.";
		}
		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Adresse email invalide.';
		}

		if (!in_array($idRole, self::CREATABLE_ROLE_IDS, true)) {
			$errors['id_role'] = 'Rôle invalide.';
		}

		// Un Administrateur plateforme n'est rattaché à aucune agence ; tous les autres rôles
		// disponibles ici sont des rôles d'agence et doivent en désigner une.
		if ($idRole === 1) {
			$data['id_agence'] = null;
		} elseif ($data['id_agence'] === null) {
			$errors['id_agence'] = 'L’agence est requise pour ce rôle.';
		}

		if ($data['telephone'] !== '') {
			$e164 = Phone::toE164($data['indicatif_pays'], $data['telephone']);
			if ($e164 === null) {
				$errors['telephone'] = 'Numéro de téléphone invalide.';
			} else {
				$data['telephone'] = $e164;
			}
		} elseif ($idRole !== 1) {
			// Rôles d'agence : le téléphone sert à l'OTP de vérification au premier login
			// (voir AuthController::handleLogin), sans quoi le compte ne peut jamais se connecter.
			$errors['telephone'] = 'Le téléphone est requis pour ce rôle.';
		} else {
			$data['telephone'] = null;
		}

		return [$errors, $data];
	}
}
