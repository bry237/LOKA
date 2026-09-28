<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/Authorization.php';
require_once __DIR__ . '/../../core/Database.php';

Authorization::requireRole('Administrateur plateforme');

header('Content-Type: application/json; charset=UTF-8');

$q = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
	echo json_encode(['agences' => [], 'utilisateurs' => []]);
	exit;
}

$db = Database::connection();
$like = '%' . $q . '%';

$agenceStmt = $db->prepare(
	'SELECT id_agence, nom, ville FROM agence
	 WHERE deleted_at IS NULL AND (nom LIKE :q1 OR ville LIKE :q2 OR email LIKE :q3)
	 ORDER BY nom LIMIT 5'
);
$agenceStmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);

$userStmt = $db->prepare(
	'SELECT id_utilisateur, nom, prenom, email FROM utilisateur
	 WHERE deleted_at IS NULL AND (nom LIKE :q1 OR prenom LIKE :q2 OR email LIKE :q3)
	 ORDER BY nom LIMIT 5'
);
$userStmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);

echo json_encode([
	'agences' => array_map(static fn (array $a): array => [
		'id' => (int) $a['id_agence'],
		'title' => $a['nom'],
		'sub' => $a['ville'] ?? '',
		'url' => '/LOKA/app/administration/agences/detail.php?id=' . (int) $a['id_agence'],
	], $agenceStmt->fetchAll()),
	'utilisateurs' => array_map(static fn (array $u): array => [
		'id' => (int) $u['id_utilisateur'],
		'title' => $u['prenom'] . ' ' . $u['nom'],
		'sub' => $u['email'],
		'url' => '/LOKA/app/administration/utilisateurs/detail.php?id=' . (int) $u['id_utilisateur'],
	], $userStmt->fetchAll()),
]);
