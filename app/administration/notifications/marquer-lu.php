<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/NotificationModel.php';

$currentUser = Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Requête invalide.');
}

$idUtilisateur = (int) $currentUser['id_utilisateur'];

if (!empty($_POST['all'])) {
	NotificationModel::markAllAsRead($idUtilisateur);
} else {
	$id = (int) ($_POST['id'] ?? 0);
	if ($id > 0) {
		NotificationModel::markAsRead($idUtilisateur, $id);
	}
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['unread' => NotificationModel::unreadCount($idUtilisateur)]);
