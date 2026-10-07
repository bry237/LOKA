<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
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
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'rapports'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="/LOKA/app/jordan/rapports/" class="back-link">&larr; Retour</a>
                <span class="eyebrow">Rapports</span>
                <h1>Fiabilité des locataires</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=fiabilite_locataires" class="btn btn-primary">Exporter CSV</a>
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
                            $color = $taux >= 90 ? 'var(--success)' : ($taux >= 70 ? 'var(--warning)' : 'var(--danger)');
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
