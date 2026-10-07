<?php declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/PaiementController.php';
require_once __DIR__ . '/PaiementModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int)$user['id_agence'];
$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$model = new PaiementModel();
$echeances = $model->scheduleList($agencyId, ['statut' => ['PENDING', 'PARTIAL', 'LATE']]);
$contractRows = $model->contractsForPayment($agencyId);

// Regroupe les lignes contrat/locataire en { id_contrat: { numero, bien, tenants: [...] } } pour le formulaire et le JS.
$contracts = [];
foreach ($contractRows as $row) {
    $cid = (int) $row['id_contrat'];
    if (!isset($contracts[$cid])) {
        $contracts[$cid] = ['numero' => $row['numero'], 'bien' => $row['bien_nom'], 'tenants' => []];
    }
    $contracts[$cid]['tenants'][] = [
        'id' => (int) $row['id_locataire'],
        'label' => $row['locataire_prenom'] . ' ' . $row['locataire_nom'],
    ];
}

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

// Pré-remplissage depuis une échéance (lien "Encaisser" depuis la liste des impayés), avant tout POST.
if (!empty($input['id_echeance'])) {
    foreach ($echeances as $e) {
        if ((string) $e['id_echeance'] === (string) $input['id_echeance']) {
            $input['id_contrat'] = $e['id_contrat'];
            $input['id_locataire'] = $e['id_locataire'];
            $input['montant'] = $e['montant_total'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $input = array_merge($input, $_POST);

        $controller = new PaiementController();
        [$errors, $id] = $controller->create($agencyId, $input);

        if (empty($errors) && $id) {
            header('Location: /LOKA/app/jordan/paiements/detail.php?id=' . $id . ($isModal ? '&modal=1' : ''));
            exit;
        }
    } else {
        $errors['general'] = "Token CSRF invalide.";
    }
}

$modeLabels = ['BANK_TRANSFER' => 'Virement bancaire', 'CASH' => 'Espèces', 'CARD' => 'Carte bancaire', 'CHEQUE' => 'Chèque', 'DIRECT_DEBIT' => 'Prélèvement', 'OTHER' => 'Autre'];

if (!$isModal): ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encaisser | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'paiements'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>

    <div class="content">
        <header class="topbar">
            <a href="/LOKA/app/jordan/paiements/" class="back-link">← Retour</a>
            <h1>Nouvel encaissement</h1>
        </header>

        <main class="page">
<?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card__body">
                <form method="POST" action="ajouter.php" class="form-grid" id="paiementForm">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>

                    <div class="form-group full">
                        <label>Lier à une échéance impayée (optionnel)</label>
                        <select name="id_echeance" id="id_echeance" onchange="fillFromEcheance(this)">
                            <option value="">— Aucune —</option>
                            <?php foreach ($echeances as $e): ?>
                                <option value="<?= $e['id_echeance'] ?>" <?= (string) $input['id_echeance'] === (string) $e['id_echeance'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['bien_nom'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($e['locataire_prenom'] . ' ' . $e['locataire_nom'], ENT_QUOTES, 'UTF-8') ?> — <?= date('d/m/Y', strtotime($e['date_echeance'])) ?> — <?= number_format((float)$e['montant_total'], 2, ',', ' ') ?> €
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Contrat <span class="required">*</span></label>
                        <select name="id_contrat" id="id_contrat" onchange="populateLocataires(this.value, null)" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($contracts as $cid => $c): ?>
                                <option value="<?= $cid ?>" <?= (string) $input['id_contrat'] === (string) $cid ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['numero'] . ' — ' . $c['bien'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['id_contrat'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_contrat'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Locataire <span class="required">*</span></label>
                        <select name="id_locataire" id="id_locataire" required>
                            <option value="">— Sélectionner un contrat —</option>
                            <?php if (!empty($input['id_contrat']) && isset($contracts[(int) $input['id_contrat']])): ?>
                                <?php foreach ($contracts[(int) $input['id_contrat']]['tenants'] as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= (string) $input['id_locataire'] === (string) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['id_locataire'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_locataire'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Montant (€) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="montant" id="montant" value="<?= htmlspecialchars((string)$input['montant'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['montant'])): ?><span class="field-error"><?= htmlspecialchars($errors['montant'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Date de paiement <span class="required">*</span></label>
                        <input type="date" name="date_paiement" value="<?= htmlspecialchars($input['date_paiement'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['date_paiement'])): ?><span class="field-error"><?= htmlspecialchars($errors['date_paiement'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Mode de paiement <span class="required">*</span></label>
                        <select name="mode_paiement" required>
                            <?php foreach ($modeLabels as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $input['mode_paiement'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['mode_paiement'])): ?><span class="field-error"><?= htmlspecialchars($errors['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Référence transaction</label>
                        <input type="text" name="reference_transaction" value="<?= htmlspecialchars($input['reference_transaction'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group full">
                        <label>Commentaire</label>
                        <textarea name="commentaire" rows="3"><?= htmlspecialchars($input['commentaire'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-actions full">
                        <?php if ($isModal): ?>
                            <a href="index.php" class="btn btn-secondary" data-modal-cancel>Annuler</a>
                        <?php else: ?>
                            <a href="/LOKA/app/jordan/paiements/" class="btn btn-secondary">Annuler</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
                </div>
            </div>
<?php if (!$isModal): ?>
        </main>
    </div>
</div>
<?php endif; ?>
<script>
    const lokaContracts = <?= json_encode($contracts) ?>;
    const lokaEcheances = <?= json_encode($echeances) ?>;

    function populateLocataires(idContrat, selectedLocataire) {
        const sel = document.getElementById('id_locataire');
        sel.innerHTML = '';
        const contract = lokaContracts[idContrat];
        if (!contract) {
            sel.innerHTML = '<option value="">— Sélectionner un contrat —</option>';
            return;
        }
        contract.tenants.forEach((t) => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.label;
            if (selectedLocataire !== null && String(selectedLocataire) === String(t.id)) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    function fillFromEcheance(select) {
        const echeance = lokaEcheances.find((e) => String(e.id_echeance) === String(select.value));
        if (!echeance) return;
        document.getElementById('montant').value = echeance.montant_total;
        document.getElementById('id_contrat').value = echeance.id_contrat;
        populateLocataires(echeance.id_contrat, echeance.id_locataire);
    }
</script>
<?php if (!$isModal): ?>
</body>
</html>
<?php endif; ?>
