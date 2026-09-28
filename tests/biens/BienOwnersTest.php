<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/biens/BienModel.php';
require_once dirname(__DIR__, 2) . '/app/proprietaires/ProprietaireModel.php';

function assertBienTest(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

assertBienTest(method_exists(BienModel::class, 'owners'), 'BienModel::owners est requis.');
assertBienTest(method_exists(ProprietaireModel::class, 'propertyWithOwners'), 'La lecture bien-propriétaires est requise.');

$db = Database::connection();
foreach (['bien', 'proprietaire', 'bien_proprietaire'] as $table) {
	assertBienTest($db->query("SHOW TABLES LIKE '" . $table . "'")->fetchColumn() === $table, 'Table manquante : ' . $table);
}

echo "BienOwnersTest: OK\n";
