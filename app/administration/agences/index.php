<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';

$model = new AgenceModel();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$agences = $model->findAll($search, $status);
$stats = $model->getStats();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agences | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Gestion des Agences</h1></header>
        <main class="page">
            <div class="stats-cards">
                <div class="card">Total: <?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card">Actives: <?= htmlspecialchars((string)$stats['actives'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card">Suspendues: <?= htmlspecialchars((string)$stats['suspendues'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            
            <div class="actions" style="margin-top: 1rem; margin-bottom: 1rem;">
                <a href="ajouter.php" class="btn btn-primary">Ajouter une agence</a>
            </div>

            <form method="GET" class="filter-form" style="margin-bottom: 1rem; display: flex; gap: 1rem;">
                <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <select name="status">
                    <option value="">Tous les statuts</option>
                    <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="SUSPENDED" <?= $status === 'SUSPENDED' ? 'selected' : '' ?>>Suspendue</option>
                </select>
                <button type="submit" class="btn">Filtrer</button>
            </form>

            <table class="data-table">
                <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($agences as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($a['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$a['telephone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($a['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="detail.php?id=<?= $a['id_agence'] ?>">Détail</a> | 
                            <a href="modifier.php?id=<?= $a['id_agence'] ?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
