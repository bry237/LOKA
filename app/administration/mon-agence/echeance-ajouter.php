<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['id_contrat' => '', 'periode_debut' => '', 'periode_fin' => '', 'montant_loyer' => '', 'montant_charges' => '0', 'date_echeance' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::createEcheance($idAgence, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: paiements.php?success=' . urlencode('Échéance créée.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$contrats = MonAgenceModel::contratsForEcheance($idAgence);

$pageTitle = 'Nouvelle échéance';
$activeNav = 'Paiements';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Ajoutez une échéance de loyer pour un contrat.';
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
      <h1>Nouvelle échéance</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="echeance-ajouter.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="id_contrat">Contrat</label>
            <select id="id_contrat" name="id_contrat" onchange="const o=this.selectedOptions[0];document.getElementById('montant_loyer').value=o.dataset.loyer||'';document.getElementById('montant_charges').value=o.dataset.charges||'0';">
              <option value="">— Choisir —</option>
              <?php foreach ($contrats as $c): ?>
                <option value="<?= (int) $c['id_contrat'] ?>" data-loyer="<?= htmlspecialchars((string) $c['loyer'], ENT_QUOTES, 'UTF-8') ?>" data-charges="<?= htmlspecialchars((string) $c['charges'], ENT_QUOTES, 'UTF-8') ?>"<?= (string) $values['id_contrat'] === (string) $c['id_contrat'] ? ' selected' : '' ?>><?= htmlspecialchars($c['numero'] . ' — ' . $c['bien_titre'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_contrat'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_contrat'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="periode_debut">Début de période</label>
            <input type="date" id="periode_debut" name="periode_debut" value="<?= htmlspecialchars($values['periode_debut'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['periode_debut'])): ?><span class="field-error"><?= htmlspecialchars($errors['periode_debut'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="periode_fin">Fin de période</label>
            <input type="date" id="periode_fin" name="periode_fin" value="<?= htmlspecialchars($values['periode_fin'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['periode_fin'])): ?><span class="field-error"><?= htmlspecialchars($errors['periode_fin'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="date_echeance">Date d'échéance</label>
            <input type="date" id="date_echeance" name="date_echeance" value="<?= htmlspecialchars($values['date_echeance'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['date_echeance'])): ?><span class="field-error"><?= htmlspecialchars($errors['date_echeance'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="montant_loyer">Loyer (€)</label>
            <input type="number" step="0.01" min="0" id="montant_loyer" name="montant_loyer" value="<?= htmlspecialchars($values['montant_loyer'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['montant_loyer'])): ?><span class="field-error"><?= htmlspecialchars($errors['montant_loyer'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="montant_charges">Charges (€)</label>
            <input type="number" step="0.01" min="0" id="montant_charges" name="montant_charges" value="<?= htmlspecialchars($values['montant_charges'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['montant_charges'])): ?><span class="field-error"><?= htmlspecialchars($errors['montant_charges'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Créer l’échéance</button>
          <?php if ($isModal): ?>
            <a class="btn" href="paiements.php" data-modal-cancel>Annuler</a>
          <?php else: ?>
            <a class="btn" href="paiements.php">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
