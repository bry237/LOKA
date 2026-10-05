<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: /LOKA/app/paiements/');
    exit;
}

$model = new PaiementModel();
$paiement = $model->find($agencyId, $id);

if (!$paiement) {
    header('Location: /LOKA/app/paiements/');
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail du paiement | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    
    <div class="content">
        <header class="topbar">
            <div>
                <a href="/LOKA/app/paiements/" class="back-link">← Retour aux paiements</a>
                <h1>Détail du paiement #<?= $paiement['id_paiement'] ?></h1>
            </div>
        </header>
        
        <main class="page">
            <div class="card">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h3>Informations générales</h3>
                        <p><strong>Montant :</strong> <?= number_format((float)$paiement['montant'], 2, ',', ' ') ?> €</p>
                        <p><strong>Date :</strong> <?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></p>
                        <p><strong>Mode de paiement :</strong> <?= htmlspecialchars($paiement['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Statut :</strong> <?= htmlspecialchars($paiement['statut'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Référence :</strong> <?= htmlspecialchars($paiement['reference_transaction'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($paiement['commentaire'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                    <div>
                        <h3>Contrat & Locataire</h3>
                        <p><strong>Locataire :</strong> <?= htmlspecialchars($paiement['locataire_prenom'] . ' ' . $paiement['locataire_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Bien :</strong> <?= htmlspecialchars($paiement['bien_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Contrat Réf. :</strong> <?= htmlspecialchars($paiement['contrat_reference'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <?php if ($paiement['id_echeance']): ?>
                        <h3 style="margin-top: 1.5rem;">Échéance liée</h3>
                        <p><strong>Période :</strong> <?= date('d/m/Y', strtotime($paiement['periode_debut'])) ?> au <?= date('d/m/Y', strtotime($paiement['periode_fin'])) ?></p>
                        <p><strong>Montant échéance :</strong> <?= number_format((float)$paiement['echeance_montant'], 2, ',', ' ') ?> €</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
