<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/NotificationController.php';

$user = Auth::requireLogin();
$userId = (int) $user['id_utilisateur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['action']) && $_POST['action'] === 'mark_all') {
        NotificationController::markAllAsRead($userId);
    } elseif (isset($_POST['id_notification'])) {
        $id = (int) $_POST['id_notification'];
        NotificationController::markAsRead($userId, $id);
    }
}

header('Location: index.php');
exit;
