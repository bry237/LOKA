<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole(['ADMIN_AGENCE', 'AGENT', 'PROPRIETAIRE']);
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
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiements | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Finances</span>
                <h1>Paiements</h1>
            </div>
            <div class="topbar-actions">
                <a href="/LOKA/app/paiements/impayes.php" class="btn btn-secondary">Voir les impayés</a>
                <a href="/LOKA/app/paiements/ajouter.php" class="btn btn-primary">Encaisser</a>
            </div>
        </header>
        
        <main class="page">
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="stat-label" style="color: #6b7280; font-size: 0.875rem;">Encaissé ce mois</div>
                    <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: #10b981;"><?= number_format($stats['collected_month'], 2, ',', ' ') ?> €</div>
                </div>
                <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="stat-label" style="color: #6b7280; font-size: 0.875rem;">Impayés (<?= $stats['overdue_count'] ?>)</div>
                    <div class="stat-value" style="font-size: 1.5rem; font-weight: 600; color: #ef4444;"><?= number_format($stats['overdue_amount'], 2, ',', ' ') ?> €</div>
                </div>
                <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="stat-label" style="color: #6b7280; font-size: 0.875rem;">Échéances en attente</div>
                    <div class="stat-value" style="font-size: 1.5rem; font-weight: 600;"><?= $stats['pending_echeances'] ?></div>
                </div>
            </div>

            <div class="filters card">
                <form method="GET" class="filter-form" style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex:1">
                        <label>Statut</label>
                        <select name="statut" class="form-control">
                            <option value="">Tous</option>
                            <option value="PAID" <?= $filters['statut'] === 'PAID' ? 'selected' : '' ?>>Payé</option>
                            <option value="PENDING" <?= $filters['statut'] === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>Mode</label>
                        <select name="mode_paiement" class="form-control">
                            <option value="">Tous</option>
                            <option value="BANK_TRANSFER" <?= $filters['mode_paiement'] === 'BANK_TRANSFER' ? 'selected' : '' ?>>Virement</option>
                            <option value="CARD" <?= $filters['mode_paiement'] === 'CARD' ? 'selected' : '' ?>>Carte</option>
                            <option value="CASH" <?= $filters['mode_paiement'] === 'CASH' ? 'selected' : '' ?>>Espèces</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Filtrer</button>
                </form>
            </div>

            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Locataire</th>
                            <th>Bien</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paiements as $p): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
                            <td><?= htmlspecialchars($p['locataire_prenom'] . ' ' . $p['locataire_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($p['bien_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$p['montant'], 2, ',', ' ') ?> €</td>
                            <td><?= htmlspecialchars($p['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($p['statut'] === 'PAID'): ?>
                                    <span class="badge" style="background: #d1fae5; color: #065f46;">Payé</span>
                                <?php else: ?>
                                    <span class="badge"><?= htmlspecialchars($p['statut'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/LOKA/app/paiements/detail.php?id=<?= $p['id_paiement'] ?>">Détails</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($paiements)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">Aucun paiement trouvé.</td>
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
