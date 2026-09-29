<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/ContratModel.php';

$user = Authorization::requireRole(['AGENCY_ADMIN', 'MANAGER', 'AGENT']);
$agencyId = $user['id_agence'];

$model = new ContratModel();
$stats = $model->stats($agencyId);

$filters = [
    'search' => $_GET['search'] ?? '',
    'statut' => $_GET['statut'] ?? '',
    'type_contrat' => $_GET['type_contrat'] ?? ''
];

$contrats = $model->list($agencyId, $filters);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrats | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'contrats'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="breadcrumbs">
                <span class="eyebrow">Location</span>
                <h1>Contrats</h1>
            </div>
            <div class="actions">
                <a href="/LOKA/app/contrats/ajouter.php" class="btn btn-primary">Nouveau contrat</a>
            </div>
        </header>

        <main class="page">
            <div class="stats-cards">
                <div class="card stat">
                    <span class="label">Total</span>
                    <span class="value"><?= htmlspecialchars((string)$stats['total'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card stat">
                    <span class="label">Actifs</span>
                    <span class="value"><?= htmlspecialchars((string)$stats['active'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card stat">
                    <span class="label">Brouillons</span>
                    <span class="value"><?= htmlspecialchars((string)$stats['draft'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="card stat">
                    <span class="label">Expirés</span>
                    <span class="value"><?= htmlspecialchars((string)$stats['expired'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <div class="card table-card">
                <div class="table-toolbar">
                    <form method="GET" class="filters-form">
                        <input type="text" name="search" placeholder="Recherche (numéro, bien)..." value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                        <select name="statut">
                            <option value="">Tous les statuts</option>
                            <option value="DRAFT" <?= $filters['statut'] === 'DRAFT' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="ACTIVE" <?= $filters['statut'] === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                            <option value="EXPIRED" <?= $filters['statut'] === 'EXPIRED' ? 'selected' : '' ?>>Expiré</option>
                            <option value="TERMINATED" <?= $filters['statut'] === 'TERMINATED' ? 'selected' : '' ?>>Résilié</option>
                        </select>
                        <select name="type_contrat">
                            <option value="">Tous les types</option>
                            <option value="HABITATION_VIDE" <?= $filters['type_contrat'] === 'HABITATION_VIDE' ? 'selected' : '' ?>>Habitation vide</option>
                            <option value="HABITATION_MEUBLE" <?= $filters['type_contrat'] === 'HABITATION_MEUBLE' ? 'selected' : '' ?>>Habitation meublée</option>
                            <option value="COMMERCIAL" <?= $filters['type_contrat'] === 'COMMERCIAL' ? 'selected' : '' ?>>Commercial</option>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Numéro</th>
                                <th>Bien</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Loyer (CC)</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contrats)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun contrat trouvé.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contrats as $c): 
                                    $loyerCC = floatval($c['loyer']) + floatval($c['charges']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['numero'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($c['bien_nom'] ?? $c['bien_reference'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($c['type_contrat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?= htmlspecialchars($c['date_debut'], ENT_QUOTES, 'UTF-8') ?>
                                        <?= $c['date_fin'] ? ' au ' . htmlspecialchars($c['date_fin'], ENT_QUOTES, 'UTF-8') : ' (en cours)' ?>
                                    </td>
                                    <td><?= htmlspecialchars(number_format($loyerCC, 2, ',', ' ') . ' €', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge badge-<?= strtolower($c['statut']) ?>"><?= htmlspecialchars($c['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <a href="/LOKA/app/contrats/detail.php?id=<?= $c['id_contrat'] ?>" class="btn btn-sm btn-secondary">Voir</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
