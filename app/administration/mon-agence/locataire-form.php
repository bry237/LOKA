<?php
declare(strict_types=1);

/**
 * Formulaire partagé par locataire-ajouter.php et locataire-modifier.php.
 * Variables attendues : $errors, $values, $action, $isModal, $csrfToken, $idLocataire (null en création), $cancelHref.
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
    </div>
    <div class="form-field">
      <label for="profession">Profession</label>
      <input type="text" id="profession" name="profession" value="<?= htmlspecialchars($values['profession'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="revenu_mensuel">Revenu mensuel (€)</label>
      <input type="number" step="0.01" min="0" id="revenu_mensuel" name="revenu_mensuel" value="<?= htmlspecialchars($values['revenu_mensuel'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="nombre_occupants">Nombre d'occupants</label>
      <input type="number" min="1" id="nombre_occupants" name="nombre_occupants" value="<?= htmlspecialchars($values['nombre_occupants'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="statut">Statut</label>
      <select id="statut" name="statut">
        <option value="ACTIVE"<?= $values['statut'] === 'ACTIVE' ? ' selected' : '' ?>>Actif</option>
        <option value="INACTIVE"<?= $values['statut'] === 'INACTIVE' ? ' selected' : '' ?>>Inactif</option>
      </select>
      <?php if (!empty($errors['statut'])): ?><span class="field-error"><?= htmlspecialchars($errors['statut'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
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

    <div class="form-field">
      <label for="type_identite">Type d'identité</label>
      <select id="type_identite" name="type_identite">
        <option value="">— Choisir —</option>
        <option value="CNI"<?= $values['type_identite'] === 'CNI' ? ' selected' : '' ?>>Carte d'identité</option>
        <option value="PASSEPORT"<?= $values['type_identite'] === 'PASSEPORT' ? ' selected' : '' ?>>Passeport</option>
        <option value="TITRE_SEJOUR"<?= $values['type_identite'] === 'TITRE_SEJOUR' ? ' selected' : '' ?>>Titre de séjour</option>
        <option value="AUTRE"<?= $values['type_identite'] === 'AUTRE' ? ' selected' : '' ?>>Autre</option>
      </select>
    </div>
    <div class="form-field">
      <label for="numero_identite">Numéro d'identité</label>
      <input type="text" id="numero_identite" name="numero_identite" value="<?= htmlspecialchars($values['numero_identite'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="situation_familiale">Situation familiale</label>
      <select id="situation_familiale" name="situation_familiale">
        <option value="">— Choisir —</option>
        <option value="CELIBATAIRE"<?= $values['situation_familiale'] === 'CELIBATAIRE' ? ' selected' : '' ?>>Célibataire</option>
        <option value="MARIE"<?= $values['situation_familiale'] === 'MARIE' ? ' selected' : '' ?>>Marié(e)</option>
        <option value="PACSE"<?= $values['situation_familiale'] === 'PACSE' ? ' selected' : '' ?>>Pacsé(e)</option>
        <option value="DIVORCE"<?= $values['situation_familiale'] === 'DIVORCE' ? ' selected' : '' ?>>Divorcé(e)</option>
        <option value="VEUF"<?= $values['situation_familiale'] === 'VEUF' ? ' selected' : '' ?>>Veuf/Veuve</option>
      </select>
    </div>

    <div class="form-field">
      <label for="garant_nom">Nom du garant</label>
      <input type="text" id="garant_nom" name="garant_nom" value="<?= htmlspecialchars($values['garant_nom'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="garant_telephone">Téléphone du garant</label>
      <input type="text" id="garant_telephone" name="garant_telephone" value="<?= htmlspecialchars($values['garant_telephone'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="form-field">
      <label for="garant_email">Email du garant</label>
      <input type="email" id="garant_email" name="garant_email" value="<?= htmlspecialchars($values['garant_email'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-field span-2">
      <label for="notes">Notes</label>
      <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($values['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $idLocataire ? 'Enregistrer les modifications' : 'Créer le locataire' ?></button>
    <?php if ($isModal): ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>" data-modal-cancel>Annuler</a>
    <?php else: ?>
      <a class="btn" href="<?= htmlspecialchars($cancelHref, ENT_QUOTES, 'UTF-8') ?>">Annuler</a>
    <?php endif; ?>
  </div>
</form>
