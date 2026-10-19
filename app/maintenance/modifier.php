<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id_intervention'] ?? 0);

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}

$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}

if ($intervention['statut'] !== 'OPEN') {
    header('Location: detail.php?id=' . $idIntervention . '&error=non_modifiable');
    exit;
}

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}

$errors = [];

$titre = $intervention['titre'];
$description = $intervention['description'];
$priorite = $intervention['priorite'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorite = $_POST['priorite'] ?? 'MEDIUM';

    if ($titre === '') {
        $errors[] = 'Le titre est obligatoire.';
    }

    if ($description === '') {
        $errors[] = 'La description est obligatoire.';
    }

    $prioritesAutorisees = ['LOW', 'MEDIUM', 'HIGH', 'URGENT'];

    if (!in_array($priorite, $prioritesAutorisees, true)) {
        $errors[] = 'La priorité sélectionnée est invalide.';
    }

    if (empty($errors)) {
        $success = $controller->updateRequest($idIntervention, $titre, $description, $priorite);

        if ($success) {
            header('Location: detail.php?id=' . $idIntervention . '&success=modifiee');
            exit;
        }

        $errors[] = 'La demande n\'a pas pu être modifiée. Elle a peut-être déjà été prise en charge.';
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = 'Modifier la demande';
$activeNav = 'Maintenance';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Maintenance';
$greetingSubtitle = 'Modifier votre demande de maintenance.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="detail.php?id=<?= $idIntervention ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au détail
    </a>

    <div class="panel" style="max-width:720px;">
      <div class="panel-header"><div class="panel-title">Modifier la demande</div></div>
      <?php if (!empty($errors)): ?>
        <div class="feedback error" style="margin:0 18px 0;">
          <?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="id_intervention" value="<?= $idIntervention ?>">
        <div class="form-grid full">
          <div class="form-field">
            <label for="titre">Titre du problème</label>
            <input type="text" id="titre" name="titre" maxlength="200" value="<?= e($titre) ?>" required>
          </div>
          <div class="form-field">
            <label for="priorite">Priorité</label>
            <select id="priorite" name="priorite" required>
              <option value="LOW" <?= $priorite === 'LOW' ? 'selected' : '' ?>>Faible</option>
              <option value="MEDIUM" <?= $priorite === 'MEDIUM' ? 'selected' : '' ?>>Moyenne</option>
              <option value="HIGH" <?= $priorite === 'HIGH' ? 'selected' : '' ?>>Élevée</option>
              <option value="URGENT" <?= $priorite === 'URGENT' ? 'selected' : '' ?>>Urgente</option>
            </select>
          </div>
          <div class="form-field">
            <label for="description">Description du problème</label>
            <textarea id="description" name="description" rows="6" required><?= e($description) ?></textarea>
          </div>
        </div>
        <div class="form-actions">
          <a href="detail.php?id=<?= $idIntervention ?>" class="btn">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
