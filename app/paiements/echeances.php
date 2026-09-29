<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);

if ($agencyId < 1) {
    http_response_code(400);
    exit('Aucune agence n’est associée à ce compte.');
}

$statusFilter = filter_input(INPUT_GET, 'statut') ?: null;
$echeances = PaiementModel::scheduleList($agencyId, $statusFilter);

$currentPage = 'paiements';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Échéances | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="topbar-eyebrow">Finances</span>
                <h1 class="topbar-title">Échéances</h1>
            </div>
        </header>
        <main class="page">
            <form method="get" class="filters">
                <label>Statut:
                    <select name="statut" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                        <option value="PAID" <?= $statusFilter === 'PAID' ? 'selected' : '' ?>>Payé</option>
                        <option value="LATE" <?= $statusFilter === 'LATE' ? 'selected' : '' ?>>En retard</option>
                    </select>
                </label>
            </form>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contrat</th>
                        <th>Bien</th>
                        <th>Période</th>
                        <th>Montant</th>
                        <th>Date échéance</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($echeances as $echeance): ?>
                        <tr>
                            <td><?= htmlspecialchars($echeance['contrat_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($echeance['bien_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($echeance['periode_debut'] ?? '', ENT_QUOTES, 'UTF-8') ?> au <?= htmlspecialchars($echeance['periode_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($echeance['montant'] ?? ''), ENT_QUOTES, 'UTF-8') ?> €</td>
                            <td><?= htmlspecialchars($echeance['date_echeance'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($echeance['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
