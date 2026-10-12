<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$locataire = MonAgenceController::locataireDetail($idAgence, $id);
if (!$locataire) {
	header('Location: locataires.php?error=' . urlencode('Locataire introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$successMessage = (string) ($_GET['success'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	if ((string) ($_POST['action'] ?? '') === 'archive') {
		MonAgenceController::archiveLocataire($idAgence, $id, (int) $currentUser['id_utilisateur']);
		header('Location: locataires.php?success=' . urlencode('Locataire archivé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$icons = teamIcons();
$statusLabels = ['ACTIVE' => 'Actif', 'INACTIVE' => 'Inactif', 'ARCHIVED' => 'Archivé'];
$statusTones = ['ACTIVE' => 'actif', 'INACTIVE' => 'inactif', 'ARCHIVED' => 'inactif'];
$identiteLabels = ['CNI' => "Carte d'identité", 'PASSEPORT' => 'Passeport', 'TITRE_SEJOUR' => 'Titre de séjour', 'AUTRE' => 'Autre'];
$situationLabels = ['CELIBATAIRE' => 'Célibataire', 'MARIE' => 'Marié(e)', 'PACSE' => 'Pacsé(e)', 'DIVORCE' => 'Divorcé(e)', 'VEUF' => 'Veuf/Veuve'];
$contratStatusLabels = ['ACTIVE' => 'Actif', 'ENDED' => 'Terminé', 'TERMINATED' => 'Résilié', 'DRAFT' => 'Brouillon'];

$pageTitle = $locataire['prenom'] . ' ' . $locataire['nom'];
$activeNav = 'Locataires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du locataire.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="locataires.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux locataires
    </a>
    <?php endif; ?>

    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <h1><?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
      <a class="btn btn-secondary" href="locataire-modifier.php?id=<?= $id ?>" data-modal>Modifier</a>
    </div>

    <?php if ($successMessage): ?>
      <div class="feedback success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= $locataire['email'] ? htmlspecialchars($locataire['email'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= $locataire['telephone'] ? htmlspecialchars($locataire['telephone'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= $locataire['adresse'] ? htmlspecialchars($locataire['adresse'] . ', ' . $locataire['code_postal'] . ' ' . $locataire['ville'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Profession</span><span class="info-value"><?= $locataire['profession'] ? htmlspecialchars($locataire['profession'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Revenu mensuel</span><span class="info-value"><?= $locataire['revenu_mensuel'] !== null ? number_format((float) $locataire['revenu_mensuel'], 2, ',', ' ') . ' €' : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Occupants</span><span class="info-value"><?= (int) $locataire['nombre_occupants'] ?></span></div>
            <div class="info-row"><span class="info-label">Identité</span><span class="info-value"><?= $locataire['type_identite'] ? htmlspecialchars($identiteLabels[$locataire['type_identite']] . ($locataire['numero_identite'] ? ' · ' . $locataire['numero_identite'] : ''), ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Situation familiale</span><span class="info-value"><?= $locataire['situation_familiale'] ? htmlspecialchars($situationLabels[$locataire['situation_familiale']] ?? $locataire['situation_familiale'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$locataire['statut']] ?? '' ?>"><?= $statusLabels[$locataire['statut']] ?? $locataire['statut'] ?></span></div>
          </div>
          <?php if ($locataire['notes']): ?>
            <div class="form-field" style="padding:0 18px 18px;">
              <label>Notes</label>
              <p style="margin:0;font-size:13px;color:var(--text);white-space:pre-line;"><?= htmlspecialchars($locataire['notes'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($locataire['garant_nom'] || $locataire['garant_telephone'] || $locataire['garant_email']): ?>
        <div class="panel" style="margin-top:16px;">
          <div class="panel-header"><div class="panel-title">Garant</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= $locataire['garant_nom'] ? htmlspecialchars($locataire['garant_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= $locataire['garant_telephone'] ? htmlspecialchars($locataire['garant_telephone'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= $locataire['garant_email'] ? htmlspecialchars($locataire['garant_email'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($locataire['statut'] !== 'ARCHIVED'): ?>
        <div class="panel" style="margin-top:16px;">
          <div class="panel-header"><div class="panel-title">Actions</div></div>
          <div style="padding:18px;">
            <form method="post" action="locataire-detail.php?id=<?= $id ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
              <input type="hidden" name="action" value="archive">
              <button type="submit" class="btn btn-danger" onclick="return confirm('Archiver ce locataire ?');">Archiver ce locataire</button>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Contrats</div></div>
          <?php if (!$locataire['contrats']): ?>
            <div class="empty-state">
              <div class="empty-state-icon"><?= $icons['card'] ?></div>
              <p>Aucun contrat pour ce locataire.</p>
            </div>
          <?php else: ?>
            <div class="info-list">
              <?php foreach ($locataire['contrats'] as $contrat): ?>
                <div class="info-row">
                  <span class="info-label"><?= htmlspecialchars($contrat['numero'] . ' · ' . $contrat['bien_titre'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="status-badge <?= $contrat['statut'] === 'ACTIVE' ? 'actif' : 'inactif' ?>"><?= $contratStatusLabels[$contrat['statut']] ?? $contrat['statut'] ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
