<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

$model = new UtilisateurModel();
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$statut = $_GET['statut'] ?? '';
$agence = $_GET['agence'] ?? '';

$utilisateurs = $model->findAll($search, $role, $statut, $agence);
$stats = $model->getStats();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Utilisateurs | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'utilisateurs'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Gestion des Utilisateurs</h1></header>
        <main class="page">
            <div class="stats-cards">
                <div class="card">Total: <?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="card">Actifs: <?= htmlspecialchars((string)$stats['actifs'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            
            <div class="actions" style="margin-top: 1rem; margin-bottom: 1rem;">
                <a href="ajouter.php" class="btn btn-primary">Ajouter un utilisateur</a>
            </div>

            <form method="GET" class="filter-form" style="margin-bottom: 1rem; display: flex; gap: 1rem;">
                <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="ACTIVE" <?= $statut === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                    <option value="INACTIVE" <?= $statut === 'INACTIVE' ? 'selected' : '' ?>>Inactif</option>
                    <option value="LOCKED" <?= $statut === 'LOCKED' ? 'selected' : '' ?>>Bloqué</option>
                </select>
                <button type="submit" class="btn">Filtrer</button>
            </form>

            <table class="data-table">
                <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Agence</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$u['role_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$u['agence_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="detail.php?id=<?= $u['id_utilisateur'] ?>">Détail</a> | 
                            <a href="modifier.php?id=<?= $u['id_utilisateur'] ?>">Modifier</a>
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
