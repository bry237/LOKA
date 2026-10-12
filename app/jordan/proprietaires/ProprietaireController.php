<?php
declare(strict_types=1);

require_once __DIR__ . '/ProprietaireModel.php';
require_once __DIR__ . '/ProprietaireValidator.php';

final class ProprietaireController
{
	public static function save(int $agencyId, array $input, ?int $ownerId = null): array
	{
		$errors = ProprietaireValidator::validate($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		$id = $ownerId === null
			? ProprietaireModel::create($agencyId, $input)
			: (ProprietaireModel::update($agencyId, $ownerId, $input) ? $ownerId : null);
		if ($id === null) {
			throw new RuntimeException('Le propriétaire est introuvable ou inaccessible.');
		}
		return [[], $id];
	}

	public static function attach(int $agencyId, int $ownerId, array $input): array
	{
		$errors = ProprietaireValidator::association($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		ProprietaireModel::attachProperty($agencyId, $ownerId, $input);
		return [[], $ownerId];
	}
}