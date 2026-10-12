<?php
declare(strict_types=1);

/**
 * Formulaire partagé par proprietaire-ajouter.php et proprietaire-modifier.php.
 * Variables attendues : $errors, $values, $action, $isModal, $csrfToken, $idProprietaire (null en création), $cancelHref.
 */
?>
<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>

  <div class="form-grid">
    <div class="form-field">
      <label for="nom">Nom</label>
      <input type="text" id="nom" name="nom" maxlength="150" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="prenom">Prénom</label>
      <input type="text" id="prenom" name="prenom" maxlength="150" value="<?= htmlspecialchars($values['prenom'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['prenom'])): ?><span class="field-error"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['email'])): ?><span class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>
    <div class="form-field">
      <label for="telephone">Téléphone</label>
      <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($values['telephone'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="date_naissance">Date de naissance</label>
      <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($values['date_naissance'], ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errors['date_naissance'])): ?><span class="field-error"><?= htmlspecialchars($errors['date_naissance'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
    </div>

    <div class="form-field span-2">
      <label for="adresse">Adresse</label>
      <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($values['adresse'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="code_postal">Code postal</label>
      <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($values['code_postal'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="ville">Ville</label>
      <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($values['ville'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="pays">Pays</label>
      <input type="text" id="pays" name="pays" value="<?= htmlspecialchars($values['pays'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $idProprietaire ? 'Enregistrer les modifications' : 'Créer le propriétaire' ?></button>
    <?php if ($isModal): ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>" data-modal-cancel>Annuler</a>
    <?php else: ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    <?php endif; ?>
  </div>
</form>
