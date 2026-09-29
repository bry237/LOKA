<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole(['ADMIN_AGENCE', 'AGENT']);
$agencyId = (int)$user['id_agence'];

$model = new PaiementModel();
$impayes = $model->overdueList($agencyId);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impayés | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    
    <div class="content">
        <header class="topbar">
            <div>
                <span class="eyebrow">Finances</span>
                <h1>Loyers Impayés</h1>
            </div>
            <div class="topbar-actions">
                <a href="/LOKA/app/paiements/" class="btn btn-secondary">Retour aux paiements</a>
            </div>
        </header>
        
        <main class="page">
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Échéance</th>
                            <th>Locataire</th>
                            <th>Contact</th>
                            <th>Bien</th>
                            <th>Montant dû</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impayes as $e): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($e['date_echeance'])) ?></td>
                            <td><?= htmlspecialchars($e['locataire_prenom'] . ' ' . $e['locataire_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div><?= htmlspecialchars($e['locataire_telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size: 0.8rem; color: #666;"><?= htmlspecialchars($e['locataire_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><?= htmlspecialchars($e['bien_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: bold; color: #ef4444;"><?= number_format((float)$e['montant_total'], 2, ',', ' ') ?> €</td>
                            <td>
                                <span class="badge" style="background: #fee2e2; color: #991b1b;">Retard</span>
                            </td>
                            <td>
                                <a href="/LOKA/app/paiements/ajouter.php?id_echeance=<?= $e['id_echeance'] ?>" class="btn btn-primary btn-sm">Encaisser</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($impayes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">Aucun impayé constaté. Super !</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
