<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';
require_once dirname(__DIR__, 3) . '/core/UploadHelper.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$types = $controller->getTypesDocuments();
$biens = $controller->getMesBiens();
$contrats = $controller->getContrats();
$locataires = $controller->getLocatairesDeMesBiens();

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$input = ['id_type_document' => '', 'id_bien' => '', 'id_contrat' => '', 'id_locataire' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Votre session a expiré. Rechargez la page.';
    } else {
        $input = array_merge($input, $_POST);
        [$errors, $idDocument] = $controller->creerDocument($input, UploadHelper::resolve('fichier'));
        if (empty($errors)) {
            header('Location: documents.php?success=added');
            exit;
        }
    }
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Ajouter un document';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Ajouter un document';
$greetingSubtitle = 'Importez un document et associez-le à un type, un bien, un contrat ou un locataire.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="documents.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes documents
    </a>

    <?php if (!empty($errors['general'])): ?>
      <div class="feedback error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <div class="panel" style="max-width:640px;">
      <div class="panel-header"><div class="panel-title">Nouveau document</div></div>
      <form method="post" enctype="multipart/form-data" data-upload-fallback class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">

        <div class="form-field full">
          <label for="fichier">Fichier</label>
          <input type="file" id="fichier" name="fichier" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
          <?php if (!empty($errors['fichier'])): ?><span class="field-error"><?= e($errors['fichier']) ?></span><?php endif; ?>
          <p class="cell-sub" style="margin:4px 0 0;">PDF, JPG, PNG, DOC(X) ou XLS(X) — 10 Mo maximum.</p>
        </div>

        <div class="form-field">
          <label for="id_type_document">Type de document</label>
          <select id="id_type_document" name="id_type_document" required>
            <option value="">— Choisir un type —</option>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id_type_document'] ?>" <?= (string) $input['id_type_document'] === (string) $type['id_type_document'] ? 'selected' : '' ?>><?= e($type['nom']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($errors['id_type_document'])): ?><span class="field-error"><?= e($errors['id_type_document']) ?></span><?php endif; ?>
        </div>

        <div class="form-field">
          <label for="id_bien">Bien concerné (optionnel)</label>
          <select id="id_bien" name="id_bien">
            <option value="">— Aucun —</option>
            <?php foreach ($biens as $bien): ?>
              <option value="<?= (int) $bien['id_bien'] ?>" <?= (string) $input['id_bien'] === (string) $bien['id_bien'] ? 'selected' : '' ?>><?= e($bien['titre']) ?> (<?= e($bien['reference']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="id_contrat">Contrat concerné (optionnel)</label>
          <select id="id_contrat" name="id_contrat">
            <option value="">— Aucun —</option>
            <?php foreach ($contrats as $contrat): ?>
              <option value="<?= (int) $contrat['id_contrat'] ?>" <?= (string) $input['id_contrat'] === (string) $contrat['id_contrat'] ? 'selected' : '' ?>>Contrat <?= e($contrat['numero']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="id_locataire">Locataire concerné (optionnel)</label>
          <select id="id_locataire" name="id_locataire">
            <option value="">— Aucun —</option>
            <?php foreach ($locataires as $locataire): ?>
              <option value="<?= (int) $locataire['id_locataire'] ?>" <?= (string) $input['id_locataire'] === (string) $locataire['id_locataire'] ? 'selected' : '' ?>><?= e($locataire['prenom'] . ' ' . $locataire['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-actions full">
          <a href="documents.php" class="btn">Annuler</a>
          <button type="submit" class="btn btn-primary">Ajouter le document</button>
        </div>
      </form>
    </div>
<script src="/LOKA/public/assets/js/upload-fallback.js"></script>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
