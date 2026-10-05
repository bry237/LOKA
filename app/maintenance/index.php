<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/MaintenanceController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

$controller = new MaintenanceController();

$filters = [
    'search' => $_GET['search'] ?? '',
    'priorite' => $_GET['priorite'] ?? '',
    'statut' => $_GET['statut'] ?? ''
];

$interventions = $controller->list($agencyId, $filters);
$stats = $controller->stats($agencyId);

function getPrioriteBadge(string $priorite): string {
    return match ($priorite) {
        'LOW' => '<span class="badge badge-green">Faible</span>',
        'MEDIUM' => '<span class="badge badge-blue">Moyenne</span>',
        'HIGH' => '<span class="badge badge-warning">Haute</span>',
        'URGENT' => '<span class="badge badge-danger">Urgente</span>',
        default => '<span class="badge">Inconnue</span>'
    };
}

function getStatutBadge(string $statut): string {
    return match ($statut) {
        'OPEN' => '<span class="badge badge-gray">Ouverte</span>',
        'ASSIGNED' => '<span class="badge badge-blue">Assignée</span>',
        'IN_PROGRESS' => '<span class="badge badge-warning">En cours</span>',
        'WAITING' => '<span class="badge badge-warning">En attente</span>',
        'RESOLVED' => '<span class="badge badge-green">Résolue</span>',
        'CLOSED' => '<span class="badge badge-gray">Clôturée</span>',
        'CANCELLED' => '<span class="badge badge-danger">Annulée</span>',
        default => '<span class="badge">Inconnu</span>'
    };
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .stat-card .stat-value { font-size: 2rem; font-weight: bold; color: var(--primary-color); }
        .stat-card .stat-label { color: var(--text-muted); font-size: 0.875rem; }
        .badge-green { background: #dcfce7; color: #166534; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-blue { background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-warning { background: #fef08a; color: #854d0e; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-danger { background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge-gray { background: #f3f4f6; color: #374151; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
        .badge { display: inline-block; font-weight: 500; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'maintenance'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="eyebrow">Technique</div>
                <h1>Maintenance</h1>
            </div>
            <div class="topbar-right">
                <a href="ajouter.php" class="btn btn-primary">Nouvelle intervention</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= htmlspecialchars((string)$stats['ouvertes'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">Ouvertes / Assignées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= htmlspecialchars((string)$stats['en_cours'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">En cours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #991b1b;"><?= htmlspecialchars((string)$stats['urgentes'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">Urgentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= htmlspecialchars((string)$stats['resolues_ce_mois'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">Résolues ce mois</div>
                </div>
            </div>

            <div class="list-filters">
                <form method="get" action="index.php" class="filter-form">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Rechercher par titre..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <select name="priorite" class="form-control">
                            <option value="">Toutes les priorités</option>
                            <option value="LOW" <?= $filters['priorite'] === 'LOW' ? 'selected' : '' ?>>Faible</option>
                            <option value="MEDIUM" <?= $filters['priorite'] === 'MEDIUM' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="HIGH" <?= $filters['priorite'] === 'HIGH' ? 'selected' : '' ?>>Haute</option>
                            <option value="URGENT" <?= $filters['priorite'] === 'URGENT' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="statut" class="form-control">
                            <option value="">Tous les statuts</option>
                            <option value="OPEN" <?= $filters['statut'] === 'OPEN' ? 'selected' : '' ?>>Ouverte</option>
                            <option value="ASSIGNED" <?= $filters['statut'] === 'ASSIGNED' ? 'selected' : '' ?>>Assignée</option>
                            <option value="IN_PROGRESS" <?= $filters['statut'] === 'IN_PROGRESS' ? 'selected' : '' ?>>En cours</option>
                            <option value="WAITING" <?= $filters['statut'] === 'WAITING' ? 'selected' : '' ?>>En attente</option>
                            <option value="RESOLVED" <?= $filters['statut'] === 'RESOLVED' ? 'selected' : '' ?>>Résolue</option>
                            <option value="CLOSED" <?= $filters['statut'] === 'CLOSED' ? 'selected' : '' ?>>Clôturée</option>
                            <option value="CANCELLED" <?= $filters['statut'] === 'CANCELLED' ? 'selected' : '' ?>>Annulée</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Filtrer</button>
                    <?php if (array_filter($filters)): ?>
                        <a href="index.php" class="btn btn-secondary">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Bien</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Assigné à</th>
                            <th>Créé le</th>
                            <th class="actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($interventions)): ?>
                            <tr><td colspan="7" class="text-center">Aucune intervention trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($interventions as $i): ?>
                                <tr>
                                    <td><?= htmlspecialchars($i['titre'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($i['bien_nom']): ?>
                                            <?= htmlspecialchars($i['bien_nom'] . ' (' . $i['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getPrioriteBadge($i['priorite']) ?></td>
                                    <td><?= getStatutBadge($i['statut']) ?></td>
                                    <td>
                                        <?php if ($i['assigne_nom']): ?>
                                            <?= htmlspecialchars($i['assigne_prenom'] . ' ' . $i['assigne_nom'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($i['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="actions-col">
                                        <a href="detail.php?id=<?= $i['id_intervention'] ?>" class="btn btn-sm btn-secondary">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
