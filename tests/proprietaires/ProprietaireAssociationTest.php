<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/proprietaires/ProprietaireValidator.php';
require_once dirname(__DIR__, 2) . '/app/proprietaires/ProprietaireModel.php';

function assertOwnerTest(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

assertOwnerTest(
	ProprietaireValidator::association([
		'id_bien' => '12',
		'quote_part' => '50',
		'date_debut' => '2026-09-22',
	]) === [],
	'Une association valide doit être acceptée.'
);
assertOwnerTest(
	ProprietaireValidator::association([
		'id_bien' => '12',
		'quote_part' => '100.01',
		'date_debut' => '2026-09-22',
	]) !== [],
	'Une quote-part supérieure à 100 doit être refusée.'
);
assertOwnerTest(
	ProprietaireValidator::association([
		'id_bien' => '12',
		'quote_part' => '50',
		'date_debut' => '2026-02-31',
	]) !== [],
	'Une date invalide doit être refusée.'
);
assertOwnerTest(method_exists(ProprietaireModel::class, 'propertyWithOwners'), 'La lecture pour les futurs modules est requise.');

echo "ProprietaireAssociationTest: OK\n";
