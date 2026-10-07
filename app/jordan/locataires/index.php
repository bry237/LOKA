<?php declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/LocataireModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}

$model = new LocataireModel();
$stats = $model->stats($agencyId);

$query = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';

$locataires = $model->list($agencyId, $query, $status);

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($locataires): void {
    ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Locataire</th>
                        <th>Contact</th>
                        <th>Ville</th>
                        <th>Contrats actifs</th>
                        <th>Statut</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locataires as $loc): ?>
                        <tr>
                            <td>
                                <div class="cell-title"><?= htmlspecialchars($loc['nom'] . ' ' . $loc['prenom'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cell-muted">#<?= (int) $loc['id_locataire'] ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($loc['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cell-muted"><?= htmlspecialchars($loc['telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><?= htmlspecialchars($loc['ville'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $loc['contrats_actifs'] ?></td>
                            <?php $locBadge = ['ACTIVE' => 'green', 'INACTIVE' => 'gray', 'ARCHIVED' => 'danger']; ?>
                            <td>
                                <span class="badge badge-<?= $locBadge[$loc['statut']] ?? 'gray' ?>"><?= htmlspecialchars($loc['statut'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="actions-col">
                                <a href="detail.php?id=<?= (int) $loc['id_locataire'] ?>" class="btn btn-sm btn-outline">Détails</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($locataires === []): ?>
                        <tr class="empty-row"><td colspan="6">Aucun locataire trouvé.</td></tr>
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
    <title>Locataires | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'locataires'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Location</span>
                <h1>Locataires</h1>
            </div>
            <a href="ajouter.php" class="btn btn-primary" data-modal>＋ Nouveau locataire</a>
        </header>
        <main class="page">
            <div class="stats-row">
                <div class="stat-card">
                    <h3>Total</h3>
                    <span class="stat-value"><?= (int) $stats['total'] ?></span>
                </div>
                <div class="stat-card">
                    <h3>Actifs</h3>
                    <span class="stat-value"><?= (int) $stats['actifs'] ?></span>
                </div>
                <div class="stat-card">
                    <h3>Inactifs</h3>
                    <span class="stat-value"><?= (int) $stats['inactifs'] ?></span>
                </div>
            </div>

            <div class="filters-card">
                <form class="filters-form" method="GET" action="index.php" data-live-search data-live-target="#locatairesResults">
                    <label class="live-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="q" placeholder="Rechercher par nom, prénom, email..." value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                        <span class="live-search-spinner"></span>
                    </label>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="status">
                            <option value="">Tous les statuts</option>
                            <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                            <option value="INACTIVE" <?= $status === 'INACTIVE' ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div id="locatairesResults">
                <?php $renderResults(); ?>
            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>
</html>
