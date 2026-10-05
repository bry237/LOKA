<?php
declare(strict_types=1);

final class AbonnementValidator
{
	/**
	 * @return array{0: array<string,string>, 1: array{nom: string, description: ?string, prix_mensuel: float, limite_utilisateurs: ?int, limite_biens: ?int}}
	 */
	public static function abonnement(array $input): array
	{
		$nom = trim((string) ($input['nom'] ?? ''));
		$description = trim((string) ($input['description'] ?? ''));
		$prixMensuel = trim((string) ($input['prix_mensuel'] ?? ''));
		$limiteUtilisateurs = trim((string) ($input['limite_utilisateurs'] ?? ''));
		$limiteBiens = trim((string) ($input['limite_biens'] ?? ''));

		$errors = [];
		if ($nom === '') {
			$errors['nom'] = 'Le nom du plan est requis.';
		}
		if (!is_numeric($prixMensuel) || (float) $prixMensuel < 0) {
			$errors['prix_mensuel'] = 'Le prix mensuel doit être un nombre positif ou nul.';
		}
		foreach (['limite_utilisateurs' => $limiteUtilisateurs, 'limite_biens' => $limiteBiens] as $key => $value) {
			if ($value !== '' && (!ctype_digit($value) || (int) $value < 1)) {
				$errors[$key] = 'Doit être un nombre entier positif, ou vide pour illimité.';
			}
		}

		return [$errors, [
			'nom' => $nom,
			'description' => $description !== '' ? $description : null,
			'prix_mensuel' => (float) $prixMensuel,
			'limite_utilisateurs' => $limiteUtilisateurs !== '' ? (int) $limiteUtilisateurs : null,
			'limite_biens' => $limiteBiens !== '' ? (int) $limiteBiens : null,
		]];
	}
}
