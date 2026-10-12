<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$controller = new RapportController();

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

$agencyId = $user['id_agence'];
$data = $controller->getMaintenanceReport($agencyId, $dateFrom, $dateTo);

$totalInterventions = 0;
$resolues = 0;
$enCours = 0;
$coutTotal = 0;

foreach($data as $row) {
    $totalInterventions += $row['count'];
    if ($row['statut'] === 'cloture' || $row['statut'] === 'resolu') {
        $resolues += $row['count'];
    } else {
        $enCours += $row['count'];
    }
    $coutTotal += $row['total_cout'];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport - Maintenance | LOKA</title>
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
                <h1>Maintenance</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=maintenance<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" class="btn btn-primary">Exporter CSV</a>
            </div>
        </header>
        <main class="page">
            <form class="filter-form" method="get">
                <div class="form-group">
                    <label>Du</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                    <label>Au</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?? '', ENT_QUOTES) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total interventions</h3>
                    <div class="value"><?= $totalInterventions ?></div>
                </div>
                <div class="stat-card">
                    <h3>Résolues</h3>
                    <div class="value" style="color:var(--success);"><?= $resolues ?></div>
                </div>
                <div class="stat-card">
                    <h3>En cours</h3>
                    <div class="value" style="color:var(--warning);"><?= $enCours ?></div>
                </div>
                <div class="stat-card">
                    <h3>Coût total</h3>
                    <div class="value"><?= number_format((float)$coutTotal, 2, ',', ' ') ?> €</div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Nombre</th>
                            <th>Coût</th>
                            <th>Temps de résolution (moyen)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$row['statut'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)$row['priorite'], ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string)$row['count'], ENT_QUOTES) ?></td>
                            <td><?= number_format((float)$row['total_cout'], 2, ',', ' ') ?> €</td>
                            <td><?= $row['avg_resolution_days'] !== null ? round((float)$row['avg_resolution_days'], 1) . ' jours' : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="5">Aucune donnée trouvée.</td>
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
