<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/DocumentModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];

$filters = [
    'id_type_document' => $_GET['id_type_document'] ?? '',
    'entity_type' => $_GET['entity_type'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$documents = DocumentModel::list($agencyId, $filters);
$types = DocumentModel::types();
$stats = DocumentModel::stats($agencyId);
$total = array_sum(array_column($stats, 'count'));

$entityTypes = [
    'bien' => 'Bien',
    'contrat' => 'Contrat',
    'proprietaire' => 'Propriétaire',
    'locataire' => 'Locataire',
    'intervention' => 'Intervention'
];

function formatBytes($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documents | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'documents'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Outils</span>
                <h1>Documents</h1>
            </div>
            <div class="topbar-right">
                <a href="/LOKA/app/documents/importer.php" class="btn btn-primary">Importer un document</a>
            </div>
        </header>
        <main class="page">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?= $total ?></div>
                    <div class="stat-label">Total documents</div>
                </div>
                <?php $i = 0; foreach ($stats as $stat): if ($i++ >= 3) break; ?>
                <div class="stat-card">
                    <div class="stat-value"><?= $stat['count'] ?></div>
                    <div class="stat-label"><?= htmlspecialchars($stat['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="filters-card">
                <form method="GET" action="index.php" class="filters-form">
                    <div class="form-group">
                        <label for="search">Recherche</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars((string)$filters['search'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom du document...">
                    </div>
                    <div class="form-group">
                        <label for="id_type_document">Type</label>
                        <select id="id_type_document" name="id_type_document">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= $type['id_type_document'] ?>" <?= ($filters['id_type_document'] == $type['id_type_document']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="entity_type">Entité</label>
                        <select id="entity_type" name="entity_type">
                            <option value="">Toutes les entités</option>
                            <?php foreach ($entityTypes as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($filters['entity_type'] === $key) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                        <a href="index.php" class="btn btn-outline">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Entité rattachée</th>
                            <th>Taille</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Aucun document trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?= htmlspecialchars($doc['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($doc['type_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    if ($doc['id_bien']) {
                                        echo 'Bien: ' . htmlspecialchars($doc['bien_nom'], ENT_QUOTES, 'UTF-8');
                                    } elseif ($doc['id_contrat']) {
                                        echo 'Contrat: ' . htmlspecialchars((string)$doc['contrat_numero'], ENT_QUOTES, 'UTF-8');
                                    } elseif ($doc['id_proprietaire']) {
                                        echo 'Propriétaire: ' . htmlspecialchars($doc['proprietaire_prenom'] . ' ' . $doc['proprietaire_nom'], ENT_QUOTES, 'UTF-8');
                                    } elseif ($doc['id_locataire']) {
                                        echo 'Locataire: ' . htmlspecialchars($doc['locataire_prenom'] . ' ' . $doc['locataire_nom'], ENT_QUOTES, 'UTF-8');
                                    } elseif ($doc['id_intervention']) {
                                        echo 'Intervention: ' . htmlspecialchars($doc['intervention_titre'], ENT_QUOTES, 'UTF-8');
                                    }
                                    ?>
                                </td>
                                <td><?= formatBytes($doc['taille_octets']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="/LOKA/app/documents/telecharger.php?id=<?= $doc['id_document'] ?>" class="btn-icon" target="_blank" title="Télécharger">⬇️</a>
                                        <form method="POST" action="/LOKA/app/documents/supprimer.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                            <?= \core\Auth::csrfToken() ?>
                                            <input type="hidden" name="id_document" value="<?= $doc['id_document'] ?>">
                                            <button type="submit" class="btn-icon text-danger" title="Supprimer">🗑️</button>
                                        </form>
                                    </div>
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
