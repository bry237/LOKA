<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/LocataireModel.php';

$user = Authorization::requireRole(['AGENCE_ADMIN', 'GESTIONNAIRE']);
$agencyId = $user['id_agence'];

$model = new LocataireModel();
$stats = $model->stats($agencyId);

$query = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';

$locataires = $model->list($agencyId, $query, $status);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Locataires | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'locataires'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Location</span>
                <h1>Locataires</h1>
            </div>
            <a href="ajouter.php" class="btn btn-primary">Nouveau locataire</a>
        </header>
        <main class="page">
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-label">Total</span>
                    <span class="stat-value"><?= (int)$stats['total'] ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Actifs</span>
                    <span class="stat-value"><?= (int)$stats['actifs'] ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Inactifs</span>
                    <span class="stat-value"><?= (int)$stats['inactifs'] ?></span>
                </div>
            </div>

            <form class="filters" method="GET" action="index.php">
                <input type="text" name="q" placeholder="Rechercher par nom, prénom, email..." value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" class="input">
                <select name="status" class="input" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                    <option value="INACTIVE" <?= $status === 'INACTIVE' ? 'selected' : '' ?>>Inactif</option>
                </select>
                <button type="submit" class="btn">Filtrer</button>
            </form>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Locataire</th>
                            <th>Contact</th>
                            <th>Ville</th>
                            <th>Contrats actifs</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($locataires)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucun locataire trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($locataires as $loc): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($loc['nom'] . ' ' . $loc['prenom'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($loc['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                        <div><?= htmlspecialchars($loc['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($loc['ville'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)$loc['contrats_actifs'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($loc['statut']) ?>">
                                            <?= htmlspecialchars($loc['statut'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="detail.php?id=<?= (int)$loc['id_locataire'] ?>" class="btn btn-sm">Détails</a>
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
