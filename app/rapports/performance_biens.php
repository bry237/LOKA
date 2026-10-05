<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$controller = new RapportController();

$agencyId = $user['id_agence'];
$data = $controller->getPropertyPerformance($agencyId);

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport - Performance des biens | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'rapports'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="/LOKA/app/rapports/" class="back-link">&larr; Retour</a>
                <span class="eyebrow">Rapports</span>
                <h1>Performance des biens</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=performance_biens" class="btn-primary" style="background:#3498db;color:white;padding:8px 16px;border-radius:4px;text-decoration:none;">Exporter CSV</a>
            </div>
        </header>
        <main class="page">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bien</th>
                            <th>Statut</th>
                            <th>Revenu total généré</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars(str_replace('_', ' ', $row['statut']), ENT_QUOTES) ?></td>
                            <td><?= number_format((float)$row['total_revenu'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="3">Aucune donnée trouvée.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
