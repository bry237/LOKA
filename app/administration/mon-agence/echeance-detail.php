<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$echeance = MonAgenceController::echeanceDetail($idAgence, $id);
if (!$echeance) {
	header('Location: paiements.php?error=' . urlencode('Échéance introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$successMessage = (string) ($_GET['success'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$error = MonAgenceController::createPaiement($idAgence, $id, (int) $currentUser['id_utilisateur'], $_POST);
	if ($error === null) {
		$successMessage = 'Paiement enregistré.';
	} else {
		$errors['_global'] = $error;
	}
	$echeance = MonAgenceController::echeanceDetail($idAgence, $id);
}

$icons = teamIcons();
$statusLabels = ['PENDING' => 'En attente', 'PARTIAL' => 'Partiel', 'PAID' => 'Payée', 'LATE' => 'En retard', 'CANCELLED' => 'Annulée'];
$statusTones = ['PENDING' => 'attente', 'PARTIAL' => 'attente', 'PAID' => 'actif', 'LATE' => 'suspendu', 'CANCELLED' => 'inactif'];
$paiementStatusLabels = ['PENDING' => 'En attente', 'PAID' => 'Payé', 'LATE' => 'En retard', 'CANCELLED' => 'Annulé', 'REFUNDED' => 'Remboursé'];
$modeLabels = ['CASH' => 'Espèces', 'BANK_TRANSFER' => 'Virement', 'CARD' => 'Carte', 'CHEQUE' => 'Chèque', 'DIRECT_DEBIT' => 'Prélèvement', 'OTHER' => 'Autre'];

$pageTitle = 'Échéance ' . (new DateTimeImmutable($echeance['date_echeance']))->format('d/m/Y');
$activeNav = 'Paiements';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail de l’échéance.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="paiements.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux paiements
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1><?= htmlspecialchars($echeance['contrat_numero'] . ' — ' . $echeance['bien_titre'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Informations</div></div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Période</span><span class="info-value"><?= (new DateTimeImmutable($echeance['periode_debut']))->format('d/m/Y') ?> — <?= (new DateTimeImmutable($echeance['periode_fin']))->format('d/m/Y') ?></span></div>
        <div class="info-row"><span class="info-label">Date d'échéance</span><span class="info-value"><?= (new DateTimeImmutable($echeance['date_echeance']))->format('d/m/Y') ?></span></div>
        <div class="info-row"><span class="info-label">Loyer + charges</span><span class="info-value"><?= number_format((float) $echeance['montant_loyer'], 2, ',', ' ') ?> € + <?= number_format((float) $echeance['montant_charges'], 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Montant total</span><span class="info-value"><?= number_format((float) $echeance['montant_total'], 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Déjà réglé</span><span class="info-value"><?= number_format($echeance['total_paye'], 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Solde restant</span><span class="info-value"><?= number_format($echeance['solde'], 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$echeance['statut']] ?? '' ?>"><?= $statusLabels[$echeance['statut']] ?? $echeance['statut'] ?></span></div>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-header"><div class="panel-title">Paiements enregistrés</div></div>
      <?php if (!$echeance['paiements']): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['card'] ?></div>
          <p>Aucun paiement enregistré pour cette échéance.</p>
        </div>
      <?php else: ?>
        <div class="info-list">
          <?php foreach ($echeance['paiements'] as $p): ?>
            <div class="info-row">
              <span class="info-value" style="text-align:left;">
                <span class="cell-title" style="display:block;"><?= number_format((float) $p['montant'], 2, ',', ' ') ?> € · <?= $modeLabels[$p['mode_paiement']] ?? $p['mode_paiement'] ?></span>
                <span class="cell-sub"><?= $p['date_paiement'] ? (new DateTimeImmutable($p['date_paiement']))->format('d/m/Y') : '—' ?><?= $p['locataire_nom'] ? ' · ' . htmlspecialchars($p['locataire_nom'], ENT_QUOTES, 'UTF-8') : '' ?><?= $p['reference_transaction'] ? ' · réf. ' . htmlspecialchars($p['reference_transaction'], ENT_QUOTES, 'UTF-8') : '' ?></span>
              </span>
              <span class="status-badge <?= $p['statut'] === 'PAID' ? 'actif' : 'attente' ?>"><?= $paiementStatusLabels[$p['statut']] ?? $p['statut'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($echeance['statut'] !== 'CANCELLED' && $echeance['solde'] > 0): ?>
      <form method="post" action="echeance-detail.php?id=<?= $id ?>" class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-field">
          <label for="montant">Montant (€)</label>
          <input type="number" step="0.01" min="0.01" id="montant" name="montant" value="<?= htmlspecialchars((string) $echeance['solde'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-field">
          <label for="date_paiement">Date de paiement</label>
          <input type="date" id="date_paiement" name="date_paiement" value="<?= (new DateTimeImmutable())->format('Y-m-d') ?>">
        </div>
        <div class="form-field">
          <label for="mode_paiement">Mode de paiement</label>
          <select id="mode_paiement" name="mode_paiement">
            <?php foreach ($modeLabels as $key => $label): ?>
              <option value="<?= $key ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="id_locataire">Locataire (optionnel)</label>
          <select id="id_locataire" name="id_locataire">
            <option value="">— Non précisé —</option>
            <?php foreach ($echeance['locataires'] as $l): ?>
              <option value="<?= (int) $l['id_locataire'] ?>"><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="reference_transaction">Référence (optionnel)</label>
          <input type="text" id="reference_transaction" name="reference_transaction">
        </div>
        <div class="form-field">
          <label for="statut">Statut du paiement</label>
          <select id="statut" name="statut">
            <option value="PAID">Payé</option>
            <option value="PENDING">En attente</option>
          </select>
        </div>
        <div class="form-field span-2">
          <label for="commentaire">Commentaire (optionnel)</label>
          <textarea id="commentaire" name="commentaire" rows="2"></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
