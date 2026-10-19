<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$controller = new RapportController();

$agencyId = $user['id_agence'];
$data = $controller->getOverdueReport($agencyId);

$totalImpaye = array_sum(array_column($data, 'montant_du'));
$locatairesConcernes = count(array_unique(array_column($data, 'locataire_nom')));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport · Impayés | LOKA</title>
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
                <h1>Impayés</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=impayes" class="btn btn-primary">Exporter CSV</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-cards">
                <div class="stat-card danger">
                    <h3>Total impayé</h3>
                    <div class="value"><?= number_format((float)$totalImpaye, 2, ',', ' ') ?> €</div>
                </div>
                <div class="stat-card">
                    <h3>Locataires concernés</h3>
                    <div class="value"><?= $locatairesConcernes ?></div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Locataire</th>
                            <th>Bien</th>
                            <th>Contrat</th>
                            <th>Montant dû</th>
                            <th>Retard (jours)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['locataire_nom'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars($row['bien_nom'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)$row['contrat_ref'], ENT_QUOTES) ?></td>
                            <td style="color:var(--danger); font-weight:700;"><?= number_format((float)$row['montant_du'], 2, ',', ' ') ?> €</td>
                            <td><?= htmlspecialchars((string)$row['retard_jours'], ENT_QUOTES) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="5">Aucun impayé trouvé.</td>
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
