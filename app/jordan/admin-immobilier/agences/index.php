<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';

$model = new AgenceModel();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$agences = $model->findAll($search, $status);
$stats = $model->getStats();

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($agences): void {
    ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Statut</th><th class="actions-col">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($agences as $a): ?>
                    <tr>
                        <td class="cell-title"><?= htmlspecialchars($a['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$a['telephone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge badge-<?= strtolower($a['statut']) === 'active' ? 'green' : 'danger' ?>"><?= htmlspecialchars($a['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="actions-col">
                            <a href="detail.php?id=<?= $a['id_agence'] ?>" class="btn btn-sm btn-outline">Détail</a>
                            <a href="modifier.php?id=<?= $a['id_agence'] ?>" class="btn btn-sm btn-outline">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($agences)): ?>
                    <tr class="empty-row"><td colspan="5">Aucune agence trouvée.</td></tr>
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
    <title>Agences | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'agences'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Administration</span>
                <h1>Agences</h1>
            </div>
            <div class="topbar-right">
                <a href="ajouter.php" class="btn btn-primary">+ Ajouter une agence</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total</h3>
                    <div class="value"><?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="stat-card">
                    <h3>Actives</h3>
                    <div class="value"><?= htmlspecialchars((string)$stats['actives'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="stat-card danger">
                    <h3>Suspendues</h3>
                    <div class="value"><?= htmlspecialchars((string)$stats['suspendues'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="filters-card">
                <form method="GET" class="filters-form" data-live-search data-live-target="#agencesResults">
                    <label class="live-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Nom, email..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                        <span class="live-search-spinner"></span>
                    </label>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status">
                            <option value="">Tous les statuts</option>
                            <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                            <option value="SUSPENDED" <?= $status === 'SUSPENDED' ? 'selected' : '' ?>>Suspendue</option>
                        </select>
                    </div>
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div id="agencesResults">
                <?php $renderResults(); ?>
            </div>
        </main>
    </div>
</div>
<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>
</html>
