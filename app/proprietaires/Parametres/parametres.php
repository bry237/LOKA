<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();

$messageSucces = '';
$messageErreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'modifier_profil') {
        $erreur = $controller->updateProfil($_POST);
        if ($erreur === null) {
            $messageSucces = 'Vos informations ont été mises à jour avec succès.';
        } else {
            $messageErreur = $erreur;
        }
    } elseif ($action === 'modifier_mot_de_passe') {
        $erreur = $controller->updateMotDePasse(
            (string) ($_POST['ancien_mot_de_passe'] ?? ''),
            (string) ($_POST['nouveau_mot_de_passe'] ?? ''),
            (string) ($_POST['confirmation_mot_de_passe'] ?? '')
        );
        if ($erreur === null) {
            $messageSucces = 'Votre mot de passe a été modifié avec succès.';
        } else {
            $messageErreur = $erreur;
        }
    }
}

$proprietaire = $controller->getCurrentProprietaire();

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Paramètres';
$activeNav = 'Paramètres';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Paramètres';
$greetingSubtitle = 'Gérez vos informations personnelles et la sécurité de votre compte.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if ($messageSucces !== ''): ?>
      <div class="feedback success"><?= e($messageSucces) ?></div>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
      <div class="feedback error"><?= e($messageErreur) ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Informations personnelles</div></div>
      <form method="post" action="parametres.php" class="form-grid">
        <input type="hidden" name="action" value="modifier_profil">
        <div class="form-field">
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" value="<?= e($proprietaire['prenom'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" value="<?= e($proprietaire['nom'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" value="<?= e($proprietaire['email'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label for="telephone">Téléphone</label>
          <input type="text" id="telephone" name="telephone" value="<?= e($proprietaire['telephone'] ?? '') ?>">
        </div>
        <div class="form-field span-2">
          <label for="adresse">Adresse</label>
          <input type="text" id="adresse" name="adresse" value="<?= e($proprietaire['adresse'] ?? '') ?>">
        </div>
        <div class="form-field">
          <label for="code_postal">Code postal</label>
          <input type="text" id="code_postal" name="code_postal" value="<?= e($proprietaire['code_postal'] ?? '') ?>">
        </div>
        <div class="form-field">
          <label for="ville">Ville</label>
          <input type="text" id="ville" name="ville" value="<?= e($proprietaire['ville'] ?? '') ?>">
        </div>
        <div class="form-field span-2">
          <label for="pays">Pays</label>
          <input type="text" id="pays" name="pays" value="<?= e($proprietaire['pays'] ?? '') ?>">
        </div>
        <div class="form-actions" style="grid-column:span 2;">
          <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>
      </form>
    </div>

    <div class="panel" style="margin-top:18px;">
      <div class="panel-header"><div class="panel-title">Sécurité</div></div>
      <form method="post" action="parametres.php" class="form-grid">
        <input type="hidden" name="action" value="modifier_mot_de_passe">
        <div class="form-field span-2">
          <label for="ancien_mot_de_passe">Ancien mot de passe</label>
          <input type="password" id="ancien_mot_de_passe" name="ancien_mot_de_passe" autocomplete="current-password" required>
        </div>
        <div class="form-field">
          <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
          <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="form-field">
          <label for="confirmation_mot_de_passe">Confirmer le nouveau mot de passe</label>
          <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="form-actions" style="grid-column:span 2;">
          <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
        </div>
      </form>
    </div>

    <div class="panel" style="margin-top:18px;">
      <div class="panel-header"><div class="panel-title">Informations du compte</div></div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= e(trim($prenom . ' ' . $nom)) ?></span></div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= e($proprietaire['email'] ?? '') ?></span></div>
        <div class="info-row"><span class="info-label">Rôle</span><span class="status-badge actif">Propriétaire</span></div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
