<?php declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];
$id = (int)($_GET['id'] ?? 0);
$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

if (!$id) {
    header('Location: /LOKA/app/jordan/paiements/');
    exit;
}

$model = new PaiementModel();
$paiement = $model->find($agencyId, $id);

if (!$paiement) {
    header('Location: /LOKA/app/jordan/paiements/');
    exit;
}

$modeLabels = ['BANK_TRANSFER' => 'Virement', 'CARD' => 'Carte', 'CASH' => 'Espèces', 'CHEQUE' => 'Chèque', 'DIRECT_DEBIT' => 'Prélèvement', 'OTHER' => 'Autre'];
if (!$isModal): ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail du paiement | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>

    <div class="content">
        <header class="topbar">
            <div>
                <a href="/LOKA/app/jordan/paiements/" class="back-link" style="margin:0;"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour aux paiements</a>
            </div>
        </header>

        <main class="page">
<?php endif; ?>
            <div class="page-header">
                <h1>Paiement #<?= $paiement['id_paiement'] ?></h1>
                <p>
                    <?php if ($paiement['statut'] === 'PAID'): ?>
                        <span class="badge badge-green">Payé</span>
                    <?php else: ?>
                        <span class="badge badge-warning"><?= htmlspecialchars($paiement['statut'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="card">
                <div class="card__body">
                    <div class="detail-grid detail-grid--even">
                        <div>
                            <h3 class="form-section-title">Informations générales</h3>
                            <dl class="data-list">
                                <dt>Montant</dt><dd><?= number_format((float)$paiement['montant'], 2, ',', ' ') ?> €</dd>
                                <dt>Date</dt><dd><?= date('d/m/Y', strtotime($paiement['date_paiement'])) ?></dd>
                                <dt>Mode de paiement</dt><dd><?= htmlspecialchars($modeLabels[$paiement['mode_paiement']] ?? $paiement['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Référence</dt><dd><?= htmlspecialchars($paiement['reference_transaction'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                        </div>
                        <div>
                            <h3 class="form-section-title">Contrat &amp; locataire</h3>
                            <dl class="data-list">
                                <dt>Locataire</dt><dd><?= htmlspecialchars($paiement['locataire_prenom'] . ' ' . $paiement['locataire_nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Bien</dt><dd><?= htmlspecialchars($paiement['bien_nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Contrat</dt><dd><?= htmlspecialchars($paiement['contrat_reference'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <?php if ($paiement['id_echeance']): ?>
                                    <dt>Période échéance</dt><dd><?= date('d/m/Y', strtotime($paiement['periode_debut'])) ?> au <?= date('d/m/Y', strtotime($paiement['periode_fin'])) ?></dd>
                                    <dt>Montant échéance</dt><dd><?= number_format((float)$paiement['echeance_montant'], 2, ',', ' ') ?> €</dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                    <?php if (!empty($paiement['commentaire'])): ?>
                        <h3 class="form-section-title mt-4">Commentaire</h3>
                        <p style="font-size:13px;color:var(--text);"><?= nl2br(htmlspecialchars($paiement['commentaire'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isModal): ?>
                <div class="form-actions" style="padding:0;">
                    <button type="button" class="btn btn-outline" data-modal-cancel>Fermer</button>
                </div>
            <?php endif; ?>
<?php if (!$isModal): ?>
        </main>
    </div>
</div>
</body>
</html>
<?php endif; ?>
