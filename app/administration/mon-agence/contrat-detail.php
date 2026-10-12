<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$contrat = MonAgenceController::contratDetail($idAgence, $id);
if (!$contrat) {
	header('Location: contrats.php?error=' . urlencode('Contrat introuvable.') . ($isModal ? '&modal=1' : ''));
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

	if ($action === 'update_status') {
		if (MonAgenceController::updateContratStatus($idAgence, $id, (string) ($_POST['statut'] ?? ''), $idUtilisateur)) {
			$successMessage = 'Statut mis à jour.';
		}
	} elseif ($action === 'terminate') {
		$motif = trim((string) ($_POST['motif_resiliation'] ?? ''));
		if ($motif === '') {
			$errors['_global'] = 'Le motif de résiliation est requis.';
		} elseif (MonAgenceController::terminateContrat($idAgence, $id, $motif, $idUtilisateur)) {
			$successMessage = 'Contrat résilié.';
		}
	} elseif ($action === 'archive') {
		MonAgenceController::archiveContrat($idAgence, $id, $idUtilisateur);
		header('Location: contrats.php?success=' . urlencode('Contrat archivé.') . ($isModal ? '&modal=1' : ''));
		exit;
	} elseif ($action === 'attach_tenant') {
		$error = MonAgenceController::attachContratTenant($idAgence, $id, $idUtilisateur, $_POST);
		$successMessage = $error === null ? 'Locataire rattaché.' : '';
		if ($error !== null) {
			$errors['_global'] = $error;
		}
	} elseif ($action === 'detach_tenant') {
		MonAgenceController::detachContratTenant($idAgence, $id, (int) ($_POST['id_locataire'] ?? 0), $idUtilisateur);
		$successMessage = 'Locataire détaché.';
	}

	$contrat = MonAgenceController::contratDetail($idAgence, $id);
}

$icons = teamIcons();
$statusLabels = ['DRAFT' => 'Brouillon', 'ACTIVE' => 'Actif', 'EXPIRED' => 'Expiré', 'RENEWED' => 'Renouvelé', 'TERMINATED' => 'Résilié', 'ARCHIVED' => 'Archivé'];
$statusTones = ['DRAFT' => 'attente', 'ACTIVE' => 'actif', 'EXPIRED' => 'suspendu', 'RENEWED' => 'actif', 'TERMINATED' => 'inactif', 'ARCHIVED' => 'inactif'];
$typeLabels = ['HABITATION_VIDE' => 'Habitation vide', 'HABITATION_MEUBLE' => 'Habitation meublée', 'COMMERCIAL' => 'Commercial', 'PROFESSIONNEL' => 'Professionnel', 'SAISONNIER' => 'Saisonnier'];
$frequenceLabels = ['MONTHLY' => 'Mensuelle', 'QUARTERLY' => 'Trimestrielle', 'YEARLY' => 'Annuelle'];

$pageTitle = 'Contrat ' . $contrat['numero'];
$activeNav = 'Contrats';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du contrat.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="contrats.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux contrats
    </a>
    <?php endif; ?>

    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <h1>Contrat <?= htmlspecialchars($contrat['numero'], ENT_QUOTES, 'UTF-8') ?></h1>
      <div style="display:flex;gap:10px;">
        <?php if (!empty($contrat['chemin_fichier_pdf'])): ?>
          <a class="btn" href="contrat-document.php?id=<?= $id ?>" target="_blank" rel="noopener">Voir le document</a>
        <?php endif; ?>
        <?php if (!in_array($contrat['statut'], ['TERMINATED', 'ARCHIVED'], true)): ?>
          <a class="btn btn-secondary" href="contrat-modifier.php?id=<?= $id ?>" data-modal>Modifier</a>
        <?php endif; ?>
      </div>
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
        <div class="info-row"><span class="info-label">Bien</span><span class="info-value"><?= htmlspecialchars($contrat['bien_titre'] . ' (' . $contrat['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= $typeLabels[$contrat['type_contrat']] ?? $contrat['type_contrat'] ?></span></div>
        <div class="info-row"><span class="info-label">Période</span><span class="info-value"><?= (new DateTimeImmutable($contrat['date_debut']))->format('d/m/Y') ?> — <?= $contrat['date_fin'] ? (new DateTimeImmutable($contrat['date_fin']))->format('d/m/Y') : 'indéterminée' ?></span></div>
        <div class="info-row"><span class="info-label">Loyer</span><span class="info-value"><?= number_format((float) $contrat['loyer'], 2, ',', ' ') ?> € + <?= number_format((float) $contrat['charges'], 2, ',', ' ') ?> € de charges</span></div>
        <div class="info-row"><span class="info-label">Dépôt de garantie</span><span class="info-value"><?= number_format((float) $contrat['depot_garantie'], 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Paiement</span><span class="info-value"><?= $frequenceLabels[$contrat['frequence_paiement']] ?? $contrat['frequence_paiement'] ?> · échéance le <?= (int) $contrat['jour_echeance'] ?></span></div>
        <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$contrat['statut']] ?? '' ?>"><?= $statusLabels[$contrat['statut']] ?? $contrat['statut'] ?></span></div>
      </div>
      <?php if ($contrat['motif_resiliation']): ?>
        <div class="form-field" style="padding:0 18px 18px;">
          <label>Motif de résiliation</label>
          <p style="margin:0;font-size:13px;color:var(--text);white-space:pre-line;"><?= htmlspecialchars($contrat['motif_resiliation'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      <?php endif; ?>

      <?php if (!in_array($contrat['statut'], ['TERMINATED', 'ARCHIVED'], true)): ?>
      <div style="padding:14px 18px 18px;border-top:1px solid var(--border);">
        <form method="post" action="contrat-detail.php?id=<?= $id ?>" style="display:flex;gap:10px;flex-wrap:wrap;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
          <input type="hidden" name="action" value="update_status">
          <?php if ($contrat['statut'] === 'DRAFT'): ?>
            <button type="submit" name="statut" value="ACTIVE" class="btn btn-primary">Activer</button>
          <?php endif; ?>
          <?php if ($contrat['statut'] === 'ACTIVE'): ?>
            <button type="submit" name="statut" value="RENEWED" class="btn btn-secondary">Marquer comme renouvelé</button>
          <?php endif; ?>
        </form>
        <?php if ($contrat['statut'] === 'ACTIVE'): ?>
          <form method="post" action="contrat-detail.php?id=<?= $id ?>" class="form-grid full" style="padding:14px 0 0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
            <input type="hidden" name="action" value="terminate">
            <div class="form-field">
              <label for="motif_resiliation">Résilier le contrat — motif</label>
              <textarea id="motif_resiliation" name="motif_resiliation" rows="2"></textarea>
            </div>
            <div class="form-actions" style="padding:0;">
              <button type="submit" class="btn btn-danger" onclick="return confirm('Résilier ce contrat ?');">Résilier</button>
            </div>
          </form>
        <?php endif; ?>
        <form method="post" action="contrat-detail.php?id=<?= $id ?>" style="margin-top:14px;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
          <input type="hidden" name="action" value="archive">
          <button type="submit" class="btn btn-danger" onclick="return confirm('Archiver ce contrat ?');">Archiver ce contrat</button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <div class="panel" style="margin-top:16px;">
      <div class="panel-header"><div class="panel-title">Locataires</div></div>
      <?php if (!$contrat['locataires']): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['key'] ?></div>
          <p>Aucun locataire rattaché pour le moment.</p>
        </div>
      <?php else: ?>
        <div class="info-list">
          <?php foreach ($contrat['locataires'] as $l): ?>
            <div class="info-row">
              <span class="info-value" style="text-align:left;">
                <span class="cell-title" style="display:block;"><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?><?= $l['titulaire_principal'] ? ' · titulaire principal' : '' ?></span>
                <span class="cell-sub"><?= $l['date_entree'] ? 'entré le ' . (new DateTimeImmutable($l['date_entree']))->format('d/m/Y') : 'date d’entrée non renseignée' ?></span>
              </span>
              <form method="post" action="contrat-detail.php?id=<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
                <input type="hidden" name="action" value="detach_tenant">
                <input type="hidden" name="id_locataire" value="<?= (int) $l['id_locataire'] ?>">
                <button type="submit" class="icon-action danger" title="Détacher"><?= $icons['trash'] ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($contrat['locataires_disponibles']): ?>
      <form method="post" action="contrat-detail.php?id=<?= $id ?>" class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <input type="hidden" name="action" value="attach_tenant">
        <div class="form-field">
          <label for="id_locataire">Rattacher un locataire</label>
          <select id="id_locataire" name="id_locataire">
            <?php foreach ($contrat['locataires_disponibles'] as $l): ?>
              <option value="<?= (int) $l['id_locataire'] ?>"><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-field">
          <label for="date_entree">Date d'entrée</label>
          <input type="date" id="date_entree" name="date_entree" value="<?= (new DateTimeImmutable())->format('Y-m-d') ?>">
        </div>
        <div class="form-field">
          <label style="display:flex;align-items:center;gap:6px;">
            <input type="checkbox" name="titulaire_principal" value="1"> Titulaire principal
          </label>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-secondary">Rattacher</button>
        </div>
      </form>
      <?php elseif (!$contrat['locataires']): ?>
        <p class="cell-sub" style="padding:0 18px 18px;">Aucun locataire disponible — <a href="locataires.php">créez-en un</a> pour pouvoir le rattacher.</p>
      <?php endif; ?>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
