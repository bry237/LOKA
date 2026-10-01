<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/RoleModel.php';
$model = new RoleModel();
$roles = $model->findAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rôles | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'roles'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Gestion des Rôles</h1></header>
        <main class="page">
            <table class="data-table">
                <thead><tr><th>Nom</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($roles as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$r['description'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
