<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/PaiementController.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole(['ADMIN_AGENCE', 'AGENT']);
$agencyId = (int)$user['id_agence'];

$model = new PaiementModel();
$echeances = $model->scheduleList($agencyId, ['statut' => 'PENDING']);

$errors = [];
$input = [
    'id_echeance' => $_GET['id_echeance'] ?? '',
    'id_contrat' => '',
    'id_locataire' => '',
    'montant' => '',
    'date_paiement' => date('Y-m-d'),
    'mode_paiement' => 'BANK_TRANSFER',
    'reference_transaction' => '',
    'commentaire' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $input = array_merge($input, $_POST);
        
        if (!empty($input['id_echeance'])) {
            foreach ($echeances as $e) {
                if ($e['id_echeance'] == $input['id_echeance']) {
                    $input['id_contrat'] = $e['id_contrat'];
                    $input['id_locataire'] = $e['id_locataire'];
                }
            }
        }

        $controller = new PaiementController();
        [$errors, $id] = $controller->create($agencyId, $input);
        
        if (empty($errors) && $id) {
            header('Location: /LOKA/app/paiements/detail.php?id=' . $id);
            exit;
        }
    } else {
        $errors['general'] = "Token CSRF invalide.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encaisser | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
    <script>
        const echeances = <?= json_encode($echeances) ?>;
        function fillFromEcheance(select) {
            const id = select.value;
            const echeance = echeances.find(e => e.id_echeance == id);
            if (echeance) {
                document.getElementById('montant').value = echeance.montant_total;
                document.getElementById('id_contrat').value = echeance.id_contrat;
                document.getElementById('id_locataire').value = echeance.id_locataire;
            }
        }
    </script>
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    
    <div class="content">
        <header class="topbar">
            <div>
                <a href="/LOKA/app/paiements/" class="back-link">← Retour</a>
                <h1>Nouvel encaissement</h1>
            </div>
        </header>
        
        <main class="page">
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" class="card form-container">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                
                <div class="form-group">
                    <label>Lier à une échéance (optionnel)</label>
                    <select name="id_echeance" class="form-control" onchange="fillFromEcheance(this)">
                        <option value="">Sélectionner une échéance</option>
                        <?php foreach ($echeances as $e): ?>
                            <option value="<?= $e['id_echeance'] ?>" <?= $input['id_echeance'] == $e['id_echeance'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['bien_nom'], ENT_QUOTES, 'UTF-8') ?> - <?= date('d/m/Y', strtotime($e['date_echeance'])) ?> - <?= number_format((float)$e['montant_total'], 2, ',', ' ') ?> €
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ID Contrat</label>
                    <input type="number" name="id_contrat" id="id_contrat" class="form-control" value="<?= htmlspecialchars((string)$input['id_contrat'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['id_contrat'])): ?><span class="text-danger"><?= $errors['id_contrat'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>ID Locataire</label>
                    <input type="number" name="id_locataire" id="id_locataire" class="form-control" value="<?= htmlspecialchars((string)$input['id_locataire'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['id_locataire'])): ?><span class="text-danger"><?= $errors['id_locataire'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Montant (€) <span class="required">*</span></label>
                    <input type="number" step="0.01" name="montant" id="montant" class="form-control" value="<?= htmlspecialchars((string)$input['montant'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['montant'])): ?><span class="text-danger"><?= $errors['montant'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Date de paiement <span class="required">*</span></label>
                    <input type="date" name="date_paiement" class="form-control" value="<?= htmlspecialchars($input['date_paiement'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <?php if (isset($errors['date_paiement'])): ?><span class="text-danger"><?= $errors['date_paiement'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Mode de paiement <span class="required">*</span></label>
                    <select name="mode_paiement" class="form-control" required>
                        <option value="BANK_TRANSFER" <?= $input['mode_paiement'] === 'BANK_TRANSFER' ? 'selected' : '' ?>>Virement bancaire</option>
                        <option value="CASH" <?= $input['mode_paiement'] === 'CASH' ? 'selected' : '' ?>>Espèces</option>
                        <option value="CARD" <?= $input['mode_paiement'] === 'CARD' ? 'selected' : '' ?>>Carte bancaire</option>
                        <option value="CHEQUE" <?= $input['mode_paiement'] === 'CHEQUE' ? 'selected' : '' ?>>Chèque</option>
                        <option value="DIRECT_DEBIT" <?= $input['mode_paiement'] === 'DIRECT_DEBIT' ? 'selected' : '' ?>>Prélèvement</option>
                        <option value="OTHER" <?= $input['mode_paiement'] === 'OTHER' ? 'selected' : '' ?>>Autre</option>
                    </select>
                    <?php if (isset($errors['mode_paiement'])): ?><span class="text-danger"><?= $errors['mode_paiement'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Référence transaction</label>
                    <input type="text" name="reference_transaction" class="form-control" value="<?= htmlspecialchars($input['reference_transaction'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label>Commentaire</label>
                    <textarea name="commentaire" class="form-control" rows="3"><?= htmlspecialchars($input['commentaire'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="/LOKA/app/paiements/" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                </div>
            </form>
        </main>
    </div>
</div>
</body>
</html>
