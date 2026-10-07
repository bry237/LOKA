<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['titre' => '', 'description' => '', 'priorite' => 'MEDIUM', 'id_bien' => '', 'id_locataire' => '', 'cout_estime' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::createIntervention($idAgence, (int) $currentUser['id_utilisateur'], $_POST);
	if (!$errors) {
		header('Location: interventions.php?success=' . urlencode('Intervention créée.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$biens = MonAgenceModel::biensForIntervention($idAgence);
$locataires = MonAgenceModel::locatairesForIntervention($idAgence);

$pageTitle = 'Nouvelle intervention';
$activeNav = 'Interventions';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Déclarez une nouvelle intervention de maintenance.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="interventions.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux interventions
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Nouvelle intervention</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="intervention-ajouter.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="titre">Titre</label>
            <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($values['titre'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['titre'])): ?><span class="field-error"><?= htmlspecialchars($errors['titre'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field span-2">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($values['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="form-field">
            <label for="id_bien">Bien concerné</label>
            <select id="id_bien" name="id_bien">
              <option value="">— Choisir —</option>
              <?php foreach ($biens as $b): ?>
                <option value="<?= (int) $b['id_bien'] ?>"<?= (string) $values['id_bien'] === (string) $b['id_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($b['titre'] . ' (' . $b['reference'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_bien'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_bien'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="id_locataire">Locataire (optionnel)</label>
            <select id="id_locataire" name="id_locataire">
              <option value="">— Aucun —</option>
              <?php foreach ($locataires as $l): ?>
                <option value="<?= (int) $l['id_locataire'] ?>"<?= (string) $values['id_locataire'] === (string) $l['id_locataire'] ? ' selected' : '' ?>><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_locataire'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_locataire'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="priorite">Priorité</label>
            <select id="priorite" name="priorite">
              <option value="LOW"<?= $values['priorite'] === 'LOW' ? ' selected' : '' ?>>Faible</option>
              <option value="MEDIUM"<?= $values['priorite'] === 'MEDIUM' ? ' selected' : '' ?>>Moyenne</option>
              <option value="HIGH"<?= $values['priorite'] === 'HIGH' ? ' selected' : '' ?>>Haute</option>
              <option value="URGENT"<?= $values['priorite'] === 'URGENT' ? ' selected' : '' ?>>Urgente</option>
            </select>
            <?php if (!empty($errors['priorite'])): ?><span class="field-error"><?= htmlspecialchars($errors['priorite'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="cout_estime">Coût estimé (€)</label>
            <input type="number" step="0.01" min="0" id="cout_estime" name="cout_estime" value="<?= htmlspecialchars($values['cout_estime'], ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Créer l’intervention</button>
          <?php if ($isModal): ?>
            <a class="btn" href="interventions.php" data-modal-cancel>Annuler</a>
          <?php else: ?>
            <a class="btn" href="interventions.php">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
