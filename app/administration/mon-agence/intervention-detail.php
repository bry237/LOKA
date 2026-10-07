<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$intervention = MonAgenceController::interventionDetail($idAgence, $id);
if (!$intervention) {
	header('Location: interventions.php?error=' . urlencode('Intervention introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$action = (string) ($_POST['action'] ?? '');
	if ($action === 'update_status') {
		if (MonAgenceController::updateInterventionStatus($idAgence, $id, (string) ($_POST['statut'] ?? ''), (int) $currentUser['id_utilisateur'])) {
			$successMessage = 'Statut mis à jour.';
			$intervention = MonAgenceController::interventionDetail($idAgence, $id);
		}
	} elseif ($action === 'close') {
		$error = MonAgenceController::closeIntervention($idAgence, $id, $_POST, (int) $currentUser['id_utilisateur']);
		if ($error === null) {
			$successMessage = 'Intervention clôturée.';
			$intervention = MonAgenceController::interventionDetail($idAgence, $id);
		} else {
			$errors['compte_rendu'] = $error;
		}
	}
}

$icons = teamIcons();
$priorityLabels = ['LOW' => 'Faible', 'MEDIUM' => 'Moyenne', 'HIGH' => 'Haute', 'URGENT' => 'Urgente'];
$priorityTones = ['LOW' => 'low', 'MEDIUM' => 'medium', 'HIGH' => 'high', 'URGENT' => 'urgent'];
$statusLabels = ['OPEN' => 'Ouverte', 'ASSIGNED' => 'Assignée', 'IN_PROGRESS' => 'En cours', 'WAITING' => 'En attente', 'RESOLVED' => 'Résolue', 'CLOSED' => 'Clôturée', 'CANCELLED' => 'Annulée'];
$statusTones = ['OPEN' => 'inactif', 'ASSIGNED' => 'attente', 'IN_PROGRESS' => 'attente', 'WAITING' => 'attente', 'RESOLVED' => 'actif', 'CLOSED' => 'inactif', 'CANCELLED' => 'suspendu'];

$pageTitle = $intervention['titre'];
$activeNav = 'Interventions';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail de l’intervention.';
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
      <h1><?= htmlspecialchars($intervention['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['compte_rendu'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['compte_rendu'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-grid">
      <div class="detail-col">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Informations</div></div>
        <div class="info-list">
          <div class="info-row"><span class="info-label">Bien</span><span class="info-value"><?= htmlspecialchars($intervention['bien_titre'] . ' (' . $intervention['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?></span></div>
          <div class="info-row"><span class="info-label">Locataire</span><span class="info-value"><?= $intervention['locataire_nom'] ? htmlspecialchars($intervention['locataire_prenom'] . ' ' . $intervention['locataire_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
          <div class="info-row"><span class="info-label">Priorité</span><span class="priority-badge <?= $priorityTones[$intervention['priorite']] ?? 'low' ?>"><?= $priorityLabels[$intervention['priorite']] ?? $intervention['priorite'] ?></span></div>
          <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$intervention['statut']] ?? '' ?>"><?= $statusLabels[$intervention['statut']] ?? $intervention['statut'] ?></span></div>
          <div class="info-row"><span class="info-label">Coût estimé</span><span class="info-value"><?= number_format((float) $intervention['cout_estime'], 2, ',', ' ') ?> €</span></div>
          <div class="info-row"><span class="info-label">Coût final</span><span class="info-value"><?= $intervention['cout_final'] !== null ? number_format((float) $intervention['cout_final'], 2, ',', ' ') . ' €' : '—' ?></span></div>
          <div class="info-row"><span class="info-label">Assigné à</span><span class="info-value"><?= $intervention['assigne_nom'] ? htmlspecialchars($intervention['assigne_prenom'] . ' ' . $intervention['assigne_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
          <div class="info-row"><span class="info-label">Créée le</span><span class="info-value"><?= (new DateTimeImmutable($intervention['created_at']))->format('d/m/Y H:i') ?></span></div>
        </div>
        <?php if ($intervention['description']): ?>
          <div class="form-field" style="padding:0 18px 18px;">
            <label>Description</label>
            <p style="margin:0;font-size:13px;color:var(--text);white-space:pre-line;"><?= htmlspecialchars($intervention['description'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endif; ?>
        <?php if ($intervention['compte_rendu']): ?>
          <div class="form-field" style="padding:0 18px 18px;">
            <label>Compte rendu</label>
            <p style="margin:0;font-size:13px;color:var(--text);white-space:pre-line;"><?= htmlspecialchars($intervention['compte_rendu'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        <?php endif; ?>
      </div>
      </div>

      <div class="detail-col">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Actions</div></div>
        <div style="padding:18px;">
          <?php if (!in_array($intervention['statut'], ['CLOSED', 'CANCELLED'], true)): ?>
            <form method="post" action="intervention-detail.php?id=<?= $id ?>" class="status-actions">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
              <input type="hidden" name="action" value="update_status">
              <?php if ($intervention['statut'] === 'OPEN'): ?>
                <button type="submit" name="statut" value="ASSIGNED" class="btn btn-secondary">Assigner</button>
              <?php endif; ?>
              <?php if (in_array($intervention['statut'], ['OPEN', 'ASSIGNED'], true)): ?>
                <button type="submit" name="statut" value="IN_PROGRESS" class="btn btn-primary">Commencer</button>
              <?php endif; ?>
              <?php if (in_array($intervention['statut'], ['IN_PROGRESS', 'WAITING'], true)): ?>
                <button type="submit" name="statut" value="RESOLVED" class="btn btn-success">Marquer comme résolue</button>
                <button type="submit" name="statut" value="WAITING" class="btn btn-secondary">Mettre en attente</button>
              <?php endif; ?>
              <button type="submit" name="statut" value="CANCELLED" class="btn btn-danger" onclick="return confirm('Annuler cette intervention ?');">Annuler</button>
            </form>
          <?php endif; ?>

          <?php if ($intervention['statut'] === 'RESOLVED'): ?>
            <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">
            <h4 style="margin:0 0 12px;font-size:13px;color:var(--heading);">Clôturer l’intervention</h4>
            <form method="post" action="intervention-detail.php?id=<?= $id ?>" class="form-grid full" style="padding:0;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
              <input type="hidden" name="action" value="close">
              <div class="form-field">
                <label for="cout_final">Coût final (€)</label>
                <input type="number" step="0.01" min="0" id="cout_final" name="cout_final">
              </div>
              <div class="form-field">
                <label for="compte_rendu">Compte rendu</label>
                <textarea id="compte_rendu" name="compte_rendu" rows="3" required></textarea>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Clôturer</button>
              </div>
            </form>
          <?php endif; ?>

          <?php if (in_array($intervention['statut'], ['CLOSED', 'CANCELLED'], true)): ?>
            <p class="cell-sub" style="margin:0;">Cette intervention est terminée.</p>
          <?php endif; ?>
        </div>
      </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
