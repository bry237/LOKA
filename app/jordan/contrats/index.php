<?php declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/ContratModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}

$model = new ContratModel();
$stats = $model->stats($agencyId);

$filters = [
    'search' => $_GET['search'] ?? '',
    'statut' => $_GET['statut'] ?? '',
    'type_contrat' => $_GET['type_contrat'] ?? ''
];

$contrats = $model->list($agencyId, $filters);

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($contrats): void {
    ?>
    <div class="card table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Bien</th>
                        <th>Type</th>
                        <th>Période</th>
                        <th>Loyer (CC)</th>
                        <th>Statut</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contrats)): ?>
                        <tr class="empty-row">
                            <td colspan="7">Aucun contrat trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contrats as $c):
                            $loyerCC = floatval($c['loyer']) + floatval($c['charges']);
                        ?>
                        <tr>
                            <td class="cell-title"><?= htmlspecialchars($c['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($c['bien_nom'] ?? $c['bien_reference'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($c['type_contrat'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars($c['date_debut'], ENT_QUOTES, 'UTF-8') ?>
                                <?= $c['date_fin'] ? ' au ' . htmlspecialchars($c['date_fin'], ENT_QUOTES, 'UTF-8') : ' (en cours)' ?>
                            </td>
                            <td><?= htmlspecialchars(number_format($loyerCC, 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge badge-<?= strtolower($c['statut']) ?>"><?= htmlspecialchars($c['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="actions-col">
                                <a href="/LOKA/app/jordan/contrats/detail.php?id=<?= $c['id_contrat'] ?>" class="btn btn-sm btn-outline">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
    <title>Contrats | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'contrats'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Location</span>
                <h1>Contrats</h1>
            </div>
            <div class="actions">
                <a href="/LOKA/app/jordan/contrats/ajouter.php" class="btn btn-primary" data-modal>＋ Nouveau contrat</a>
            </div>
        </header>

        <main class="page">
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total</h3>
                    <span class="value"><?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-card">
                    <h3>Actifs</h3>
                    <span class="value"><?= htmlspecialchars((string)$stats['active'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-card">
                    <h3>Brouillons</h3>
                    <span class="value"><?= htmlspecialchars((string)$stats['draft'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="stat-card danger">
                    <h3>Expirés</h3>
                    <span class="value"><?= htmlspecialchars((string)$stats['expired'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="filters-card">
                <form method="GET" class="filters-form" data-live-search data-live-target="#contratsResults">
                    <label class="live-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Recherche (numéro, bien)..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                        <span class="live-search-spinner"></span>
                    </label>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut">
                            <option value="">Tous les statuts</option>
                            <option value="DRAFT" <?= $filters['statut'] === 'DRAFT' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="ACTIVE" <?= $filters['statut'] === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                            <option value="EXPIRED" <?= $filters['statut'] === 'EXPIRED' ? 'selected' : '' ?>>Expiré</option>
                            <option value="TERMINATED" <?= $filters['statut'] === 'TERMINATED' ? 'selected' : '' ?>>Résilié</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type_contrat">
                            <option value="">Tous les types</option>
                            <option value="HABITATION_VIDE" <?= $filters['type_contrat'] === 'HABITATION_VIDE' ? 'selected' : '' ?>>Habitation vide</option>
                            <option value="HABITATION_MEUBLE" <?= $filters['type_contrat'] === 'HABITATION_MEUBLE' ? 'selected' : '' ?>>Habitation meublée</option>
                            <option value="COMMERCIAL" <?= $filters['type_contrat'] === 'COMMERCIAL' ? 'selected' : '' ?>>Commercial</option>
                        </select>
                    </div>
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div id="contratsResults">
                <?php $renderResults(); ?>
            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>
</html>
