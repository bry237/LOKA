<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bien = MonAgenceController::bienDetail($idAgence, $id);
if (!$bien) {
	header('Location: biens.php?error=' . urlencode('Bien introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$successMessage = (string) ($_GET['success'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$action = (string) ($_POST['action'] ?? '');
	$idUtilisateur = (int) $currentUser['id_utilisateur'];

	if ($action === 'update_equipments') {
		$error = MonAgenceController::updateBienEquipments($idAgence, $id, $idUtilisateur, $_POST['equipements'] ?? []);
		$successMessage = $error === null ? 'Équipements mis à jour.' : '';
		if ($error !== null) {
			$errors['_global'] = $error;
		}
	} elseif ($action === 'add_photo') {
		$error = MonAgenceController::addBienPhoto($idAgence, $id, $_FILES['photo'] ?? null, !empty($_POST['principale']), $idUtilisateur);
		$successMessage = $error === null ? 'Photo ajoutée.' : '';
		if ($error !== null) {
			$errors['_global'] = $error;
		}
	} elseif ($action === 'set_main_photo') {
		MonAgenceController::setBienMainPhoto($idAgence, $id, (int) ($_POST['id_photo'] ?? 0));
		$successMessage = 'Photo principale mise à jour.';
	} elseif ($action === 'delete_photo') {
		MonAgenceController::deleteBienPhoto($idAgence, $id, (int) ($_POST['id_photo'] ?? 0));
		$successMessage = 'Photo supprimée.';
	} elseif ($action === 'attach_owner') {
		$error = MonAgenceController::attachBienOwner($idAgence, $id, $idUtilisateur, $_POST);
		$successMessage = $error === null ? 'Propriétaire rattaché.' : '';
		if ($error !== null) {
			$errors['_global'] = $error;
		}
	} elseif ($action === 'detach_owner') {
		MonAgenceController::detachBienOwner($idAgence, $id, (int) ($_POST['id_proprietaire'] ?? 0), $idUtilisateur);
		$successMessage = 'Propriétaire détaché.';
	} elseif ($action === 'archive') {
		MonAgenceController::archiveBien($idAgence, $id, $idUtilisateur);
		header('Location: biens.php?success=' . urlencode('Bien archivé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}

	$bien = MonAgenceController::bienDetail($idAgence, $id);
}

$icons = teamIcons();
$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusTones = ['CREATED' => 'attente', 'AVAILABLE' => 'actif', 'OCCUPIED' => 'attente', 'MAINTENANCE' => 'suspendu', 'ARCHIVED' => 'inactif'];
$assignedEquipments = $bien['equipements_assignes'];

$pageTitle = $bien['titre'];
$activeNav = 'Biens';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du bien.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="biens.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux biens
    </a>
    <?php endif; ?>

    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <h1><?= htmlspecialchars($bien['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
      <a class="btn btn-secondary" href="bien-modifier.php?id=<?= $id ?>" data-modal>Modifier</a>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Référence</span><span class="info-value"><?= htmlspecialchars($bien['reference'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= htmlspecialchars($bien['type_bien'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= htmlspecialchars($bien['ligne1'] . ', ' . $bien['code_postal'] . ' ' . $bien['ville'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php if ($bien['immeuble_nom']): ?>
              <div class="info-row"><span class="info-label">Immeuble</span><span class="info-value"><?= htmlspecialchars($bien['immeuble_nom'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Surface</span><span class="info-value"><?= number_format((float) $bien['surface'], 0) ?> m² · <?= (int) $bien['nombre_pieces'] ?> pièce(s)</span></div>
            <div class="info-row"><span class="info-label">Loyer</span><span class="info-value"><?= number_format((float) $bien['loyer'], 2, ',', ' ') ?> € + <?= number_format((float) $bien['charges'], 2, ',', ' ') ?> € de charges</span></div>
            <div class="info-row"><span class="info-label">Caution</span><span class="info-value"><?= number_format((float) $bien['caution'], 2, ',', ' ') ?> €</span></div>
            <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$bien['statut']] ?? '' ?>"><?= $statusLabels[$bien['statut']] ?? $bien['statut'] ?></span></div>
          </div>
          <?php if ($bien['description']): ?>
            <div class="form-field" style="padding:0 18px 18px;">
              <label>Description</label>
              <p style="margin:0;font-size:13px;color:var(--text);white-space:pre-line;"><?= htmlspecialchars($bien['description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!in_array($bien['statut'], ['ARCHIVED'], true)): ?>
        <div class="panel" style="margin-top:16px;">
          <div class="panel-header"><div class="panel-title">Actions</div></div>
          <div style="padding:18px;">
            <form method="post" action="bien-detail.php?id=<?= $id ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
              <input type="hidden" name="action" value="archive">
              <button type="submit" class="btn btn-danger" onclick="return confirm('Archiver ce bien ? Il n’apparaîtra plus dans la liste active.');">Archiver ce bien</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Photos</div></div>
          <?php if (!$bien['photos']): ?>
            <div class="empty-state">
              <div class="empty-state-icon"><?= $icons['camera'] ?></div>
              <p>Aucune photo pour ce bien.</p>
            </div>
          <?php else: ?>
            <div class="photo-grid">
              <?php foreach ($bien['photos'] as $photo): ?>
                <div class="photo-thumb">
                  <img src="/LOKA/<?= htmlspecialchars($photo['chemin_stockage'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                  <?php if ((int) $photo['est_principale'] === 1): ?>
                    <span class="photo-thumb-main"><?= $icons['star'] ?></span>
                  <?php endif; ?>
                  <div class="photo-thumb-actions">
                    <?php if ((int) $photo['est_principale'] !== 1): ?>
                      <form method="post" action="bien-detail.php?id=<?= $id ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
                        <input type="hidden" name="action" value="set_main_photo">
                        <input type="hidden" name="id_photo" value="<?= (int) $photo['id_photo'] ?>">
                        <button type="submit" title="Définir comme principale"><?= $icons['star'] ?></button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="bien-detail.php?id=<?= $id ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                      <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
                      <input type="hidden" name="action" value="delete_photo">
                      <input type="hidden" name="id_photo" value="<?= (int) $photo['id_photo'] ?>">
                      <button type="submit" title="Supprimer"><?= $icons['trash'] ?></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="post" action="bien-detail.php?id=<?= $id ?>" enctype="multipart/form-data" class="photo-upload-field" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
            <input type="hidden" name="action" value="add_photo">
            <div class="form-field" style="flex:1;min-width:160px;">
              <label for="photo">Ajouter une photo (JPEG, PNG, WEBP — 5 Mo max)</label>
              <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;white-space:nowrap;">
              <input type="checkbox" name="principale" value="1"> Principale
            </label>
            <button type="submit" class="btn btn-secondary">Ajouter</button>
          </form>
        </div>

        <div class="panel" style="margin-top:16px;">
          <div class="panel-header"><div class="panel-title">Équipements</div></div>
          <form method="post" action="bien-detail.php?id=<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
            <input type="hidden" name="action" value="update_equipments">
            <div class="equipment-checklist">
              <?php foreach ($bien['equipements'] as $equipment): ?>
                <label>
                  <input type="checkbox" name="equipements[]" value="<?= (int) $equipment['id_equipement'] ?>"<?= in_array((int) $equipment['id_equipement'], $assignedEquipments, true) ? ' checked' : '' ?>>
                  <?= htmlspecialchars($equipment['nom'], ENT_QUOTES, 'UTF-8') ?>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">Enregistrer les équipements</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-header"><div class="panel-title">Propriétaires</div></div>
      <?php if (!$bien['proprietaires']): ?>
        <div class="empty-state"><p>Aucun propriétaire rattaché pour le moment.</p></div>
      <?php else: ?>
        <div class="info-list">
          <?php foreach ($bien['proprietaires'] as $owner): ?>
            <div class="info-row">
              <span class="info-value" style="text-align:left;">
                <span class="cell-title" style="display:block;"><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="cell-sub"><?= number_format((float) $owner['quote_part'], 0) ?> % · depuis le <?= (new DateTimeImmutable($owner['date_debut']))->format('d/m/Y') ?></span>
              </span>
              <form method="post" action="bien-detail.php?id=<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
                <input type="hidden" name="action" value="detach_owner">
                <input type="hidden" name="id_proprietaire" value="<?= (int) $owner['id_proprietaire'] ?>">
                <button type="submit" class="icon-action danger" title="Détacher"><?= $icons['trash'] ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($bien['proprietaires_disponibles']): ?>
      <form method="post" action="bien-detail.php?id=<?= $id ?>" class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <input type="hidden" name="action" value="attach_owner">
        <div class="form-field">
          <label for="id_proprietaire">Rattacher un propriétaire</label>
          <select id="id_proprietaire" name="id_proprietaire">
            <?php foreach ($bien['proprietaires_disponibles'] as $owner): ?>
              <option value="<?= (int) $owner['id_proprietaire'] ?>"><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="quote_part">Quote-part (%)</label>
          <input type="number" step="0.01" min="0.01" max="100" id="quote_part" name="quote_part" value="100">
        </div>
        <div class="form-field">
          <label for="date_debut">Depuis le</label>
          <input type="date" id="date_debut" name="date_debut" value="<?= (new DateTimeImmutable())->format('Y-m-d') ?>">
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-secondary">Rattacher</button>
        </div>
      </form>
      <?php elseif (!$bien['proprietaires']): ?>
        <p class="cell-sub" style="padding:0 18px 18px;">Aucun propriétaire disponible — <a href="proprietaires.php">créez-en un</a> pour pouvoir le rattacher.</p>
      <?php endif; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
