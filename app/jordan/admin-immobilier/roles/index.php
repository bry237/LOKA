<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/RoleModel.php';
$model = new RoleModel();
$roles = $model->findAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rôles | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'roles'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Configuration</span>
                <h1>Rôles &amp; permissions</h1>
            </div>
        </header>
        <main class="page">
            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nom</th><th>Description</th></tr></thead>
                        <tbody>
                        <?php foreach ($roles as $r): ?>
                            <tr>
                                <td class="cell-title"><?= htmlspecialchars($r['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$r['description'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($roles)): ?>
                            <tr class="empty-row"><td colspan="2">Aucun rôle enregistré.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
