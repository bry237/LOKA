<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

$id = $_POST['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $model = new UtilisateurModel();
    $model->updateStatus($id, 'INACTIVE');
}
header('Location: detail.php?id=' . $id);
exit;
