<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        $model = new UtilisateurModel();
        $model->updateStatus($id, 'ACTIVE');
    }
}
header('Location: detail.php?id=' . $id);
exit;
