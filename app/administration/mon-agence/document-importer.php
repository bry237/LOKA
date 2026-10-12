<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['nom' => '', 'id_type_document' => '', 'entity_type' => '', 'entity_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values['nom'] = trim((string) ($_POST['nom'] ?? ''));
	$values['id_type_document'] = (string) ($_POST['id_type_document'] ?? '');
	$values['entity_type'] = (string) ($_POST['entity_type'] ?? '');
	$entityColumn = match ($values['entity_type']) {
		'bien' => 'id_bien', 'contrat' => 'id_contrat', 'proprietaire' => 'id_proprietaire',
		'locataire' => 'id_locataire', 'intervention' => 'id_intervention', default => null,
	};
	$values['entity_id'] = $entityColumn !== null ? (string) ($_POST[$entityColumn] ?? '') : '';

	$errors = MonAgenceController::createDocument($idAgence, $_POST, $_FILES['fichier'] ?? null);
	if (!$errors) {
		header('Location: documents.php?success=' . urlencode('Document importé.') . ($isModal ? '&modal=1' : ''));
		exit;
	}
}

$entities = MonAgenceModel::documentEntities($idAgence);
$types = MonAgenceModel::documentTypes();
$entityLabels = ['bien' => 'Bien', 'contrat' => 'Contrat', 'proprietaire' => 'Propriétaire', 'locataire' => 'Locataire', 'intervention' => 'Intervention'];

$pageTitle = 'Importer un document';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Importez un document pour un bien, un contrat, un propriétaire, un locataire ou une intervention.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="documents.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux documents
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Importer un document</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['entity'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['entity'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="document-importer.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="nom">Nom du document</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="id_type_document">Type de document</label>
            <select id="id_type_document" name="id_type_document">
              <option value="">— Choisir —</option>
              <?php foreach ($types as $type): ?>
                <option value="<?= (int) $type['id_type_document'] ?>"<?= $values['id_type_document'] === (string) $type['id_type_document'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_type_document'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_type_document'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="fichier">Fichier (PDF, JPEG, PNG, DOCX, XLSX, TXT — 10 Mo max)</label>
            <input type="file" id="fichier" name="fichier">
            <?php if (!empty($errors['fichier'])): ?><span class="field-error"><?= htmlspecialchars($errors['fichier'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="entity_type">Rattacher à</label>
            <select id="entity_type" name="entity_type" onchange="document.querySelectorAll('[data-entity-group]').forEach(g => g.hidden = g.dataset.entityGroup !== this.value); this.value === '' && document.querySelectorAll('[data-entity-group]').forEach(g => g.hidden = true);">
              <option value="">— Choisir une entité —</option>
              <?php foreach ($entityLabels as $key => $label): ?>
                <option value="<?= $key ?>"<?= $values['entity_type'] === $key ? ' selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php foreach (['bien' => 'id_bien', 'contrat' => 'id_contrat', 'proprietaire' => 'id_proprietaire', 'locataire' => 'id_locataire', 'intervention' => 'id_intervention'] as $entityKey => $column): ?>
            <div class="form-field" data-entity-group="<?= $entityKey ?>"<?= $values['entity_type'] !== $entityKey ? ' hidden' : '' ?>>
              <label for="<?= $column ?>"><?= $entityLabels[$entityKey] ?></label>
              <select id="<?= $column ?>" name="<?= $column ?>">
                <option value="">— Choisir —</option>
                <?php foreach ($entities[$entityKey] as $row): ?>
                  <?php
                    $label = match ($entityKey) {
                    	'bien' => $row['titre'] . ' (' . $row['reference'] . ')',
                    	'contrat' => $row['numero'],
                    	'intervention' => $row['titre'],
                    	default => $row['prenom'] . ' ' . $row['nom'],
                    };
                    $value = $row[$column] ?? $row['id_' . $entityKey];
                  ?>
                  <option value="<?= (int) $value ?>"<?= $values['entity_id'] === (string) $value ? ' selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Importer</button>
          <?php if ($isModal): ?>
            <a class="btn" href="documents.php" data-modal-cancel>Annuler</a>
          <?php else: ?>
            <a class="btn" href="documents.php">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
