<?php declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

$model = new PaiementModel();
$stats = $model->stats($agencyId);

$filters = [
    'statut' => $_GET['statut'] ?? '',
    'mode_paiement' => $_GET['mode_paiement'] ?? '',
    'date_debut' => $_GET['date_debut'] ?? '',
    'date_fin' => $_GET['date_fin'] ?? ''
];

$paiements = $model->list($agencyId, $filters);
$modeLabels = ['BANK_TRANSFER' => 'Virement', 'CARD' => 'Carte', 'CASH' => 'Espèces', 'CHEQUE' => 'Chèque', 'DIRECT_DEBIT' => 'Prélèvement', 'OTHER' => 'Autre'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiements | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>

    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Finances</span>
                <h1>Paiements</h1>
            </div>
            <div class="topbar-actions">
                <a href="/LOKA/app/jordan/paiements/impayes.php" class="btn btn-secondary">Voir les impayés</a>
                <a href="/LOKA/app/jordan/paiements/ajouter.php" class="btn btn-primary" data-modal>＋ Encaisser</a>
            </div>
        </header>

        <main class="page">
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Encaissé ce mois</h3>
                    <span class="value" style="color:var(--success);"><?= number_format($stats['collected_month'], 2, ',', ' ') ?> €</span>
                </div>
                <div class="stat-card danger">
                    <h3>Impayés (<?= $stats['overdue_count'] ?>)</h3>
                    <span class="value"><?= number_format($stats['overdue_amount'], 2, ',', ' ') ?> €</span>
                </div>
                <div class="stat-card">
                    <h3>Échéances en attente</h3>
                    <span class="value"><?= $stats['pending_echeances'] ?></span>
                </div>
            </div>

            <div class="filters-card">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut">
                            <option value="">Tous</option>
                            <option value="PAID" <?= $filters['statut'] === 'PAID' ? 'selected' : '' ?>>Payé</option>
                            <option value="PENDING" <?= $filters['statut'] === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mode</label>
                        <select name="mode_paiement">
                            <option value="">Tous</option>
                            <option value="BANK_TRANSFER" <?= $filters['mode_paiement'] === 'BANK_TRANSFER' ? 'selected' : '' ?>>Virement</option>
                            <option value="CARD" <?= $filters['mode_paiement'] === 'CARD' ? 'selected' : '' ?>>Carte</option>
                            <option value="CASH" <?= $filters['mode_paiement'] === 'CASH' ? 'selected' : '' ?>>Espèces</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Du</label>
                        <input type="date" name="date_debut" value="<?= htmlspecialchars($filters['date_debut'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label>Au</label>
                        <input type="date" name="date_fin" value="<?= htmlspecialchars($filters['date_fin'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Locataire</th>
                                <th>Bien</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Statut</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paiements as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
                                <td class="cell-title"><?= htmlspecialchars($p['locataire_prenom'] . ' ' . $p['locataire_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($p['bien_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float)$p['montant'], 2, ',', ' ') ?> €</td>
                                <td><?= htmlspecialchars($modeLabels[$p['mode_paiement']] ?? $p['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($p['statut'] === 'PAID'): ?>
                                        <span class="badge badge-green">Payé</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= htmlspecialchars($p['statut'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-col">
                                    <a href="/LOKA/app/jordan/paiements/detail.php?id=<?= $p['id_paiement'] ?>" class="btn btn-sm btn-outline" data-modal>Détails</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($paiements)): ?>
                            <tr class="empty-row">
                                <td colspan="7">Aucun paiement trouvé.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
</body>
</html>
