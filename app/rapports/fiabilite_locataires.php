<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole(['administrateur', 'gestionnaire']);
$controller = new RapportController();

$agencyId = $user['id_agence'];
$data = $controller->getTenantReport($agencyId);

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport - Fiabilité des locataires | LOKA</title>
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
                <h1>Fiabilité des locataires</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=fiabilite_locataires" class="btn-primary" style="background:#3498db;color:white;padding:8px 16px;border-radius:4px;text-decoration:none;">Exporter CSV</a>
            </div>
        </header>
        <main class="page">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Locataire</th>
                            <th>Total des échéances</th>
                            <th>Échéances payées</th>
                            <th>Taux de fiabilité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): 
                            $taux = $row['total_echeances'] > 0 ? round(((float)$row['payees'] / (float)$row['total_echeances']) * 100, 1) : 0;
                            $color = $taux >= 90 ? '#27ae60' : ($taux >= 70 ? '#f39c12' : '#e74c3c');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['locataire'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)$row['total_echeances'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)$row['payees'], ENT_QUOTES) ?></td>
                            <td style="color:<?= $color ?>;font-weight:bold;"><?= $taux ?> %</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="4">Aucune donnée trouvée.</td>
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
