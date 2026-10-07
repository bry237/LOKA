<?php
declare(strict_types=1);

require_once __DIR__ . '/BienModel.php';
require_once __DIR__ . '/BienValidator.php';

final class BienController
{
	public static function references(int $agencyId): array
	{
		if ($agencyId < 1) {
			throw new InvalidArgumentException('Une agence valide est nécessaire.');
		}
		return [
			'types' => BienModel::types(),
			'equipements' => BienModel::equipments(),
			'adresses' => BienModel::addresses($agencyId),
			'immeubles' => BienModel::buildings($agencyId),
		];
	}

	public static function createBuilding(int $agencyId, array $input): array
	{
		$errors = BienValidator::building($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		return [[], BienModel::createBuilding($agencyId, $input)];
	}

	public static function createEquipment(array $input): array
	{
		$errors = BienValidator::equipment($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		return [[], BienModel::createEquipment($input)];
	}

	public static function save(int $agencyId, array $input, ?int $propertyId = null): array
	{
		$errors = BienValidator::property($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		$id = $propertyId === null
			? BienModel::create($agencyId, $input)
			: (BienModel::update($agencyId, $propertyId, $input) ? $propertyId : null);
		if ($id === null) {
			throw new RuntimeException('Le bien est introuvable ou inaccessible.');
		}
		return [[], $id];
	}

	public static function updateEquipment(int $agencyId, int $propertyId, array $input): array
	{
		$errors = BienValidator::equipmentAssignments($input);
		if ($errors !== []) {
			return [$errors, null];
		}
		BienModel::updateEquipmentAssignments($agencyId, $propertyId, array_map('intval', $input['equipements'] ?? []));
		return [[], $propertyId];
	}
}