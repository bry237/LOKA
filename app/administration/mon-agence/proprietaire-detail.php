<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$proprietaire = MonAgenceController::proprietaireDetail($idAgence, $id);
if (!$proprietaire) {
	header('Location: proprietaires.php?error=' . urlencode('Propriétaire introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$successMessage = (string) ($_GET['success'] ?? '');
$attachError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$action = (string) ($_POST['action'] ?? '');
	$idUtilisateur = (int) $currentUser['id_utilisateur'];

	if ($action === 'attach') {
		$attachError = MonAgenceController::attachProprietaireProperty($idAgence, $id, $idUtilisateur, $_POST);
		$successMessage = $attachError === null ? 'Bien rattaché.' : '';
	} elseif ($action === 'detach') {
		MonAgenceController::detachProprietaireProperty($idAgence, $id, (int) ($_POST['id_bien'] ?? 0), $idUtilisateur);
		$successMessage = 'Bien détaché.';
	} elseif ($action === 'archive') {
		MonAgenceController::archiveProprietaire($idAgence, $id, $idUtilisateur);
		header('Location: proprietaires.php?success=' . urlencode('Propriétaire archivé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}

	$proprietaire = MonAgenceController::proprietaireDetail($idAgence, $id);
}

$icons = teamIcons();
$today = (new DateTimeImmutable())->format('Y-m-d');
$bienStatusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance'];

$pageTitle = $proprietaire['prenom'] . ' ' . $proprietaire['nom'];
$activeNav = 'Propriétaires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du propriétaire.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="proprietaires.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux propriétaires
    </a>
    <?php endif; ?>

    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <h1><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
      <a class="btn btn-secondary" href="proprietaire-modifier.php?id=<?= $id ?>" data-modal>Modifier</a>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($attachError): ?>
      <div class="feedback error"><?= htmlspecialchars($attachError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Informations</div></div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= $proprietaire['email'] ? htmlspecialchars($proprietaire['email'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
        <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= $proprietaire['telephone'] ? htmlspecialchars($proprietaire['telephone'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
        <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= $proprietaire['adresse'] ? htmlspecialchars($proprietaire['adresse'] . ', ' . $proprietaire['code_postal'] . ' ' . $proprietaire['ville'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
        <div class="info-row"><span class="info-label">Date de naissance</span><span class="info-value"><?= $proprietaire['date_naissance'] ? (new DateTimeImmutable($proprietaire['date_naissance']))->format('d/m/Y') : '—' ?></span></div>
      </div>
      <div style="padding:14px 18px 18px;border-top:1px solid var(--border);">
        <form method="post" action="proprietaire-detail.php?id=<?= $id ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
          <input type="hidden" name="action" value="archive">
          <button type="submit" class="btn btn-danger" onclick="return confirm('Archiver ce propriétaire ?');">Archiver ce propriétaire</button>
        </form>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-header"><div class="panel-title">Biens rattachés</div></div>
      <?php if (!$proprietaire['biens']): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['home'] ?></div>
          <p>Aucun bien rattaché pour le moment.</p>
        </div>
      <?php else: ?>
        <div class="info-list">
          <?php foreach ($proprietaire['biens'] as $b): ?>
            <div class="info-row">
              <span class="info-value" style="text-align:left;">
                <span class="cell-title" style="display:block;"><?= htmlspecialchars($b['titre'] . ' (' . $b['reference'] . ')', ENT_QUOTES, 'UTF-8') ?></span>
                <span class="cell-sub"><?= number_format((float) $b['quote_part'], 0) ?> % · depuis le <?= (new DateTimeImmutable($b['date_debut']))->format('d/m/Y') ?></span>
              </span>
              <form method="post" action="proprietaire-detail.php?id=<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
                <input type="hidden" name="action" value="detach">
                <input type="hidden" name="id_bien" value="<?= (int) $b['id_bien'] ?>">
                <button type="submit" class="icon-action danger" title="Détacher"><?= $icons['trash'] ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($proprietaire['biens_disponibles']): ?>
      <form method="post" action="proprietaire-detail.php?id=<?= $id ?>" class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <input type="hidden" name="action" value="attach">
        <div class="form-field">
          <label for="id_bien">Rattacher un bien</label>
          <select id="id_bien" name="id_bien">
            <?php foreach ($proprietaire['biens_disponibles'] as $b): ?>
              <option value="<?= (int) $b['id_bien'] ?>"><?= htmlspecialchars($b['titre'] . ' (' . $b['reference'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="quote_part">Quote-part (%)</label>
          <input type="number" step="0.01" min="0.01" max="100" id="quote_part" name="quote_part" value="100">
        </div>
        <div class="form-field">
          <label for="date_debut">Depuis le</label>
          <input type="date" id="date_debut" name="date_debut" value="<?= $today ?>">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-secondary">Rattacher</button>
        </div>
      </form>
      <?php elseif (!$proprietaire['biens']): ?>
        <p class="cell-sub" style="padding:0 18px 18px;">Aucun bien disponible — <a href="biens.php">créez-en un</a> pour pouvoir le rattacher.</p>
      <?php endif; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
