<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Database.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);

if ($agencyId < 1) {
    http_response_code(400);
    exit('Aucune agence n’est associée à ce compte.');
}

$statusFilter = filter_input(INPUT_GET, 'statut') ?: '';
$model = new PaiementModel();
$echeances = $model->scheduleList($agencyId, ['statut' => $statusFilter]);

$total = count($echeances);
$pending = count(array_filter($echeances, static fn(array $e): bool => $e['statut'] === 'PENDING'));
$late = count(array_filter($echeances, static fn(array $e): bool => $e['statut'] === 'LATE'));
$statutBadge = ['PENDING' => 'gray', 'PARTIAL' => 'warning', 'PAID' => 'green', 'LATE' => 'danger', 'CANCELLED' => 'gray'];
$statutLabel = ['PENDING' => 'En attente', 'PARTIAL' => 'Partiel', 'PAID' => 'Payé', 'LATE' => 'En retard', 'CANCELLED' => 'Annulé'];

$currentPage = 'encaissements';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encaissements | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Finances</span>
                <h1>Encaissements</h1>
            </div>
        </header>
        <main class="page">
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Échéances</h3>
                    <span class="value"><?= $total ?></span>
                </div>
                <div class="stat-card">
                    <h3>En attente</h3>
                    <span class="value"><?= $pending ?></span>
                </div>
                <div class="stat-card danger">
                    <h3>En retard</h3>
                    <span class="value"><?= $late ?></span>
                </div>
            </div>

            <div class="filters-card">
                <form method="get" class="filters-form">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                            <option value="PAID" <?= $statusFilter === 'PAID' ? 'selected' : '' ?>>Payé</option>
                            <option value="LATE" <?= $statusFilter === 'LATE' ? 'selected' : '' ?>>En retard</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
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
                                    <td class="cell-title"><?= htmlspecialchars($echeance['contrat_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($echeance['bien_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($echeance['periode_debut'] ?? '', ENT_QUOTES, 'UTF-8') ?> au <?= htmlspecialchars($echeance['periode_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float) ($echeance['montant_total'] ?? 0), 2, ',', ' ') ?> €</td>
                                    <td><?= htmlspecialchars($echeance['date_echeance'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-<?= $statutBadge[$echeance['statut']] ?? 'gray' ?>"><?= $statutLabel[$echeance['statut']] ?? htmlspecialchars((string) $echeance['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($echeances === []): ?>
                                <tr class="empty-row"><td colspan="6">Aucune échéance trouvée.</td></tr>
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
