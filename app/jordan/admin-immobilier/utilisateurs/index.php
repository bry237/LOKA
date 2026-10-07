<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

$model = new UtilisateurModel();
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$statut = $_GET['statut'] ?? '';
$agence = $_GET['agence'] ?? '';

$utilisateurs = $model->findAll($search, $role, $statut, $agence);
$stats = $model->getStats();

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($utilisateurs): void {
    ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Agence</th><th>Statut</th><th class="actions-col">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td class="cell-title"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$u['role_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$u['agence_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge badge-<?= strtolower($u['statut']) === 'active' ? 'green' : (strtolower($u['statut']) === 'locked' ? 'danger' : 'gray') ?>"><?= htmlspecialchars($u['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="actions-col">
                            <a href="detail.php?id=<?= $u['id_utilisateur'] ?>" class="btn btn-sm btn-outline">Détail</a>
                            <a href="modifier.php?id=<?= $u['id_utilisateur'] ?>" class="btn btn-sm btn-outline">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($utilisateurs)): ?>
                    <tr class="empty-row"><td colspan="6">Aucun utilisateur trouvé.</td></tr>
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
    <title>Utilisateurs | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'utilisateurs'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Administration</span>
                <h1>Utilisateurs</h1>
            </div>
            <div class="topbar-right">
                <a href="ajouter.php" class="btn btn-primary">+ Ajouter un utilisateur</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total</h3>
                    <div class="value"><?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="stat-card">
                    <h3>Actifs</h3>
                    <div class="value"><?= htmlspecialchars((string)$stats['actifs'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="filters-card">
                <form method="GET" class="filters-form" data-live-search data-live-target="#utilisateursResults">
                    <label class="live-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Nom, email..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                        <span class="live-search-spinner"></span>
                    </label>
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut">
                            <option value="">Tous les statuts</option>
                            <option value="ACTIVE" <?= $statut === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                            <option value="INACTIVE" <?= $statut === 'INACTIVE' ? 'selected' : '' ?>>Inactif</option>
                            <option value="LOCKED" <?= $statut === 'LOCKED' ? 'selected' : '' ?>>Bloqué</option>
                        </select>
                    </div>
                    <div class="form-actions" style="margin:0;padding:0;border:none;">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div id="utilisateursResults">
                <?php $renderResults(); ?>
            </div>
        </main>
    </div>
</div>
<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>
</html>
