<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$locataire = $controller->getCurrentLocataire();
$housing = $controller->getCurrentHousing();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priorite = $_POST['priorite'] ?? 'MEDIUM';

    if ($titre === '') {
        $error = 'Veuillez renseigner le titre du problème.';
    } elseif ($description === '') {
        $error = 'Veuillez décrire le problème.';
    } else {
        $success = $controller->createRequest($titre, $description, $priorite);

        if ($success) {
            header('Location: index.php');
            exit;
        }

        $error = 'Impossible de créer la demande de maintenance.';
    }
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Signaler un problème';
$activeNav = 'Maintenance';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Signaler un problème';
$greetingSubtitle = 'Décrivez le problème rencontré dans votre logement afin que nous puissions intervenir.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'LO';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel" style="max-width:720px;">
      <div class="panel-header"><div class="panel-title">Nouvelle demande</div></div>
      <?php if ($error !== ''): ?>
        <div class="feedback error" style="margin:0 18px 0;"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if (!$housing): ?>
        <div class="feedback error" style="margin:14px 18px 0;">Aucun logement actif n'est associé à votre compte.</div>
      <?php endif; ?>
      <form method="POST" action="">
        <div class="form-grid full">
          <div class="form-field">
            <label for="titre">Titre du problème</label>
            <input type="text" id="titre" name="titre" maxlength="255" value="<?= e($_POST['titre'] ?? '') ?>" placeholder="Ex : Fuite d'eau dans la salle de bain" required>
          </div>
          <div class="form-field">
            <label for="priorite">Priorité</label>
            <select id="priorite" name="priorite">
              <option value="LOW" <?= (($_POST['priorite'] ?? '') === 'LOW') ? 'selected' : '' ?>>Faible</option>
              <option value="MEDIUM" <?= (($_POST['priorite'] ?? 'MEDIUM') === 'MEDIUM') ? 'selected' : '' ?>>Moyenne</option>
              <option value="HIGH" <?= (($_POST['priorite'] ?? '') === 'HIGH') ? 'selected' : '' ?>>Élevée</option>
              <option value="URGENT" <?= (($_POST['priorite'] ?? '') === 'URGENT') ? 'selected' : '' ?>>Urgente</option>
            </select>
          </div>
          <div class="form-field">
            <label for="description">Description du problème</label>
            <textarea id="description" name="description" rows="6" placeholder="Décrivez précisément le problème rencontré..." required><?= e($_POST['description'] ?? '') ?></textarea>
          </div>
        </div>

        <?php if ($housing): ?>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Logement concerné</span><span class="info-value"><?= e($housing['titre'] ?? 'Mon logement') ?></span></div>
            <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= e($housing['ligne1'] ?? '') ?><?php if (!empty($housing['ligne2'])): ?>, <?= e($housing['ligne2']) ?><?php endif; ?>, <?= e($housing['code_postal'] ?? '') ?> <?= e($housing['ville'] ?? '') ?></span></div>
          </div>
        <?php endif; ?>

        <div class="form-actions">
          <a href="index.php" class="btn">Annuler</a>
          <button type="submit" class="btn btn-primary" <?= !$housing ? 'disabled' : '' ?>>Signaler le problème</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
