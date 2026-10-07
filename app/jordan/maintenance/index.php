<?php declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/MaintenanceController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}

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

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($interventions): void {
    ?>
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
                    <tr class="empty-row"><td colspan="7">Aucune intervention trouvée.</td></tr>
                <?php else: ?>
                    <?php foreach ($interventions as $i): ?>
                        <tr>
                            <td class="cell-title"><?= htmlspecialchars($i['titre'], ENT_QUOTES, 'UTF-8') ?></td>
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
    <?php
};

if ($isAjax) {
    $renderResults();
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'maintenance'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="eyebrow">Technique</div>
                <h1>Maintenance</h1>
            </div>
            <div class="topbar-right">
                <a href="ajouter.php" class="btn btn-primary" data-modal>＋ Nouvelle intervention</a>
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
                    <div class="stat-value" style="color: var(--danger);"><?= htmlspecialchars((string)$stats['urgentes'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">Urgentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= htmlspecialchars((string)$stats['resolues_ce_mois'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="stat-label">Résolues ce mois</div>
                </div>
            </div>

            <div class="filters-card">
                <form method="get" action="index.php" class="filters-form" data-live-search data-live-target="#maintenanceResults">
                    <label class="live-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Rechercher par titre..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                        <span class="live-search-spinner"></span>
                    </label>
                    <div class="form-group">
                        <select name="priorite">
                            <option value="">Toutes les priorités</option>
                            <option value="LOW" <?= $filters['priorite'] === 'LOW' ? 'selected' : '' ?>>Faible</option>
                            <option value="MEDIUM" <?= $filters['priorite'] === 'MEDIUM' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="HIGH" <?= $filters['priorite'] === 'HIGH' ? 'selected' : '' ?>>Haute</option>
                            <option value="URGENT" <?= $filters['priorite'] === 'URGENT' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="statut">
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
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                        <?php if (array_filter($filters)): ?>
                            <a href="index.php" class="btn btn-outline">Réinitialiser</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div id="maintenanceResults">
                <?php $renderResults(); ?>
            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>
</html>
