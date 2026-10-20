<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$errors = [];
$values = ['id_bien' => '', 'titre' => '', 'description' => '', 'priorite' => 'MEDIUM'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Requête invalide.');
    }
    $values = array_merge($values, array_intersect_key($_POST, $values));

    $idBien = (int) $values['id_bien'];
    $titre = trim((string) $values['titre']);
    $description = trim((string) $values['description']);
    $priorite = in_array($values['priorite'], ['LOW', 'MEDIUM', 'HIGH', 'URGENT'], true) ? $values['priorite'] : 'MEDIUM';

    if ($idBien < 1) {
        $errors['id_bien'] = 'Sélectionnez le bien concerné.';
    }
    if ($titre === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    }
    if ($description === '') {
        $errors['description'] = 'Décrivez le problème rencontré.';
    }

    if (!$errors) {
        try {
            $controller->signalerIncident($idBien, $titre, $description, $priorite);
            header('Location: index.php?success=' . urlencode('Votre signalement a été transmis à l’agence.') . ($isModal ? '&modal=1' : ''));
            exit;
        } catch (Throwable $e) {
            $errors['_global'] = 'Une erreur est survenue. Réessayez.';
        }
    }
}

$biens = $controller->getBiensPourSignalement();

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$pageTitle = 'Signaler un incident';
$activeNav = 'Vérification';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Signaler un incident';
$greetingSubtitle = 'Problème technique, fuite, document manquant…';
$profileName = '';
$profileRole = 'Propriétaire';
$profileInitials = '';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

$csrfToken = Auth::csrfToken();

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au centre de vérification
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1>Signaler un incident</h1>
    </div>

    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= e($errors['_global']) ?></div>
    <?php endif; ?>

    <div class="panel">
      <form method="post" action="signaler.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
        <div class="form-grid">
          <div class="form-field span-2">
            <label for="id_bien">Bien concerné</label>
            <select id="id_bien" name="id_bien">
              <option value="">— Choisir —</option>
              <?php foreach ($biens as $b): ?>
                <option value="<?= (int) $b['id_bien'] ?>"<?= (string) $values['id_bien'] === (string) $b['id_bien'] ? ' selected' : '' ?>><?= e($b['titre']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_bien'])): ?><span class="field-error"><?= e($errors['id_bien']) ?></span><?php endif; ?>
          </div>
          <div class="form-field span-2">
            <label for="titre">Titre</label>
            <input type="text" id="titre" name="titre" value="<?= e($values['titre']) ?>" placeholder="Ex : Fuite d'eau dans la salle de bain">
            <?php if (!empty($errors['titre'])): ?><span class="field-error"><?= e($errors['titre']) ?></span><?php endif; ?>
          </div>
          <div class="form-field span-2">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Décrivez le problème rencontré…"><?= e($values['description']) ?></textarea>
            <?php if (!empty($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="priorite">Priorité</label>
            <select id="priorite" name="priorite">
              <option value="LOW"<?= $values['priorite'] === 'LOW' ? ' selected' : '' ?>>Faible</option>
              <option value="MEDIUM"<?= $values['priorite'] === 'MEDIUM' ? ' selected' : '' ?>>Moyenne</option>
              <option value="HIGH"<?= $values['priorite'] === 'HIGH' ? ' selected' : '' ?>>Haute</option>
              <option value="URGENT"<?= $values['priorite'] === 'URGENT' ? ' selected' : '' ?>>Urgente</option>
            </select>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Envoyer le signalement</button>
          <?php if ($isModal): ?>
            <a class="btn" href="index.php" data-modal-cancel>Annuler</a>
          <?php else: ?>
            <a class="btn" href="index.php">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
