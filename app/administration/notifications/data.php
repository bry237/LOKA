<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once dirname(__DIR__, 3) . '/core/NotificationModel.php';

$currentUser = Auth::requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$items = NotificationModel::listForUser((int) $currentUser['id_utilisateur']);

echo json_encode([
	'unread' => NotificationModel::unreadCount((int) $currentUser['id_utilisateur']),
	'items' => array_map(static fn (array $n): array => [
		'id' => (int) $n['id_notification'],
		'titre' => $n['titre'],
		'message' => $n['message'],
		'type' => $n['type'],
		'lu' => $n['statut'] === 'READ',
		'date' => $n['created_at'],
	], $items),
]);
