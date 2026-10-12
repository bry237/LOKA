<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$controller = new RapportController();

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

$agencyId = $user['id_agence'];
$data = $controller->getRevenueReport($agencyId, $dateFrom, $dateTo);

$totalEncaisse = array_sum(array_column($data, 'total_encaisse'));
$moyenneMensuelle = count($data) > 0 ? $totalEncaisse / count($data) : 0;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport - Revenus | LOKA</title>
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
                <h1>Revenus</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=revenus<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>" class="btn btn-primary">Exporter CSV</a>
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
                    <h3>Total encaissé</h3>
                    <div class="value"><?= number_format((float)$totalEncaisse, 2, ',', ' ') ?> €</div>
                </div>
                <div class="stat-card">
                    <h3>Moyenne mensuelle</h3>
                    <div class="value"><?= number_format((float)$moyenneMensuelle, 2, ',', ' ') ?> €</div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Montant encaissé</th>
                            <th>Nombre de paiements</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['mois'], ENT_QUOTES) ?></td>
                            <td><?= number_format((float)$row['total_encaisse'], 2, ',', ' ') ?> €</td>
                            <td><?= htmlspecialchars((string)$row['nb_paiements'], ENT_QUOTES) ?></td>
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
