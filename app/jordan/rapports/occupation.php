<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/RapportController.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$controller = new RapportController();

$agencyId = $user['id_agence'];
$data = $controller->getOccupancyReport($agencyId);

$total = array_sum(array_column($data, 'count'));

// Fetch property list grouped by status directly for display
$db = Database::connection();
$stmt = $db->prepare("SELECT id_bien, titre, statut FROM bien WHERE id_agence = :agence_id AND deleted_at IS NULL ORDER BY statut, titre");
$stmt->execute([':agence_id' => $agencyId]);
$biens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

$biensParStatut = [];
foreach($biens as $b) {
    $biensParStatut[$b['statut']][] = $b;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport - Occupation | LOKA</title>
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
                <h1>Occupation</h1>
            </div>
            <div class="topbar-right">
                <a href="export.php?type=occupation" class="btn btn-primary">Exporter CSV</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-cards">
                <?php foreach($data as $stat): 
                    $pct = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
                ?>
                <div class="stat-card">
                    <h3><?= htmlspecialchars(str_replace('_', ' ', $stat['statut']), ENT_QUOTES) ?></h3>
                    <div class="value"><?= htmlspecialchars((string)$stat['count'], ENT_QUOTES) ?></div>
                    <div class="pct"><?= $pct ?>%</div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($data)): ?>
                <p>Aucun bien trouvé.</p>
                <?php endif; ?>
            </div>

            <?php foreach($biensParStatut as $statut => $liste): ?>
            <div class="status-section">
                <h2><?= htmlspecialchars(str_replace('_', ' ', (string)$statut), ENT_QUOTES) ?> (<?= count($liste) ?>)</h2>
                <ul class="property-list">
                    <?php foreach($liste as $bien): ?>
                    <li class="property-item"><?= htmlspecialchars($bien['titre'], ENT_QUOTES) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </main>
    </div>
</div>
</body>
</html>
